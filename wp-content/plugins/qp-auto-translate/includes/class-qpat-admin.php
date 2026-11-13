<?php
/**
 * Admin screens (menu "Dịch tự động"):
 *   Bản dịch  – every Vietnamese post with its EN / ZH state, filters
 *   Nhật ký   – background jobs, errors, retry / cancel
 *   Cài đặt   – engines, keys, limits, glossary, scope
 */

defined( 'ABSPATH' ) || exit;

final class QPAT_Admin {
	const PAGE             = 'qp-auto-translate';
	const PAGE_CONTENT     = 'qpat-content';
	const PAGE_LOG         = 'qpat-log';
	const TEST_ACTION      = 'qpat_test_connection';
	const MODELS_ACTION    = 'qpat_fetch_models';
	const JOB_ACTION       = 'qpat_job_action';
	const ROW_ACTION       = 'qpat_row_translate';
	const MODELS_OPTION    = 'qpat_gemini_models';
	const NOTICE_TRANSIENT = 'qpat_test_result_';

	/** Capability for retry / cancel in the log and for the settings. */
	const MANAGE_CAP = 'manage_options';

	private static ?QPAT_Admin $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register' ) );
		add_action( 'admin_post_' . self::TEST_ACTION, array( $this, 'handle_test' ) );
		add_action( 'admin_post_' . self::MODELS_ACTION, array( $this, 'handle_models' ) );
		add_action( 'admin_post_' . self::JOB_ACTION, array( $this, 'handle_job_action' ) );
		add_action( 'admin_post_' . self::ROW_ACTION, array( $this, 'handle_row_translate' ) );
		add_action( 'admin_notices', array( $this, 'notices' ) );
		add_action( 'update_option_' . QPAT_Settings::OPTION, array( $this, 'settings_changed' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( QPAT_FILE ), array( $this, 'action_links' ) );
	}

	public static function url( string $page = self::PAGE, array $args = array() ): string {
		return add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
	}

	public function menu(): void {
		add_menu_page( 'Dịch tự động', 'Dịch tự động', 'edit_posts', self::PAGE_CONTENT, array( $this, 'render_content' ), 'dashicons-translation', 58 );
		add_submenu_page( self::PAGE_CONTENT, 'Bản dịch', 'Bản dịch', 'edit_posts', self::PAGE_CONTENT, array( $this, 'render_content' ) );
		add_submenu_page( self::PAGE_CONTENT, 'Nhật ký dịch', 'Nhật ký', self::MANAGE_CAP, self::PAGE_LOG, array( $this, 'render_log' ) );
		add_submenu_page( self::PAGE_CONTENT, 'Cài đặt dịch tự động', 'Cài đặt', self::MANAGE_CAP, self::PAGE, array( $this, 'render' ) );
	}

	public function register(): void {
		register_setting(
			'qpat',
			QPAT_Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'QPAT_Settings', 'sanitize' ),
				'default'           => QPAT_Settings::defaults(),
			)
		);
	}

	/** Saving the settings (e.g. a higher limit) resumes jobs paused by the quota. */
	public function settings_changed(): void {
		QPAT_Queue::instance()->resume_paused();
		delete_option( QPAT_Status::BACKFILL_OPT );
	}

	public function action_links( array $links ): array {
		array_unshift( $links, '<a href="' . esc_url( self::url() ) . '">Cài đặt</a>' );
		return $links;
	}

	/** Quota warning (Google) and old-plugin conflict, on every admin screen. */
	public function notices(): void {
		if ( ! current_user_can( self::MANAGE_CAP ) ) {
			return;
		}
		if ( QPAT_Editor::legacy_auto_active() ) {
			printf(
				'<div class="notice notice-warning"><p>%s <a href="%s">Mở trang Plugin</a></p></div>',
				esc_html( 'Plugin cũ “QP LLM Translate” đang bật và đang tự dịch khi đăng bài, nên “QP Auto Translate” tạm KHÔNG tự dịch để tránh mỗi bài bị dịch 2 lần. Khi muốn chuyển sang plugin mới: tắt QP LLM Translate (bản dịch cũ vẫn giữ nguyên).' ),
				esc_url( admin_url( 'plugins.php' ) )
			);
		}

		$paused = QPAT_Queue::instance()->count_jobs( 'paused' );
		if ( $paused ) {
			printf(
				'<div class="notice notice-error"><p>%s <a href="%s">Xem nhật ký</a></p></div>',
				esc_html( sprintf( 'Dịch tự động đang TẠM DỪNG %d tác vụ vì đã đạt giới hạn ký tự Google Cloud Translation tháng này. Tác vụ chỉ chạy lại khi cấu hình được thay đổi (tăng giới hạn hoặc đổi công cụ dịch).', $paused ) ),
				esc_url( self::url( self::PAGE_LOG, array( 'status' => 'paused' ) ) )
			);
		}

		$s = QPAT_Settings::get();
		if ( 'google' !== $s['engine'] && empty( $s['fallback_google'] ) ) {
			return;
		}
		$st = QPAT_Usage::status();
		if ( 'warn' !== $st['state'] && 'paused' !== $st['state'] ) {
			return;
		}
		$class = 'paused' === $st['state'] ? 'notice-error' : 'notice-warning';
		$text  = 'paused' === $st['state']
			? 'Google Cloud Translation đã đạt giới hạn ký tự tháng này (%1$s / %2$s): các bài mới sẽ tạm dừng. Chỉ tăng hạn mức khi đã được xác nhận chi phí.'
			: 'Google Cloud Translation: đã dùng %3$s%% hạn mức ký tự tháng này (%1$s / %2$s).';
		printf(
			'<div class="notice %1$s"><p>%2$s <a href="%3$s">Xem chi tiết</a></p></div>',
			esc_attr( $class ),
			esc_html( sprintf( $text, number_format_i18n( $st['used'] ), number_format_i18n( $st['limit'] ), $st['percent'] ) ),
			esc_url( self::url() )
		);
	}

	public function handle_test(): void {
		if ( ! current_user_can( self::MANAGE_CAP ) ) {
			wp_die( 'Không có quyền.' );
		}
		check_admin_referer( self::TEST_ACTION );
		$engine = isset( $_POST['engine'] ) && 'google' === $_POST['engine'] ? 'google' : 'gemini';
		$name   = QPAT_Settings::ENGINES[ $engine ];

		$result = array();
		foreach ( QPAT_Settings::get()['target_langs'] as $lang ) {
			try {
				if ( 'google' === $engine ) {
					$t = QPAT_Google_Client::translate( array( 'title' => 'Xin chào, đây là QP Holdings.' ), $lang, 'vi', 'text' );
				} else {
					$t = QPAT_Gemini_Client::translate( array( 'title' => 'Xin chào, đây là QP Holdings.' ), $lang );
				}
				$result[] = array( 'ok' => true, 'msg' => $name . ' – ' . strtoupper( $lang ) . ': ' . $t['title'] );
			} catch ( Throwable $e ) {
				$result[] = array( 'ok' => false, 'msg' => $name . ' – ' . strtoupper( $lang ) . ': ' . $e->getMessage() );
			}
		}
		if ( ! $result ) {
			$result[] = array( 'ok' => false, 'msg' => 'Chưa chọn ngôn ngữ đích.' );
		}
		set_transient( self::NOTICE_TRANSIENT . get_current_user_id(), $result, 120 );
		wp_safe_redirect( self::url() );
		exit;
	}

	public function handle_models(): void {
		if ( ! current_user_can( self::MANAGE_CAP ) ) {
			wp_die( 'Không có quyền.' );
		}
		check_admin_referer( self::MODELS_ACTION );
		try {
			$models = QPAT_Gemini_Client::list_models();
			update_option( self::MODELS_OPTION, $models, false );
			$result = array( array( 'ok' => true, 'msg' => 'Đã tải ' . count( $models ) . ' model Gemini mà key này dùng được. Chọn model trong ô “Model” rồi bấm Lưu.' ) );
		} catch ( Throwable $e ) {
			$result = array( array( 'ok' => false, 'msg' => 'Không tải được danh sách model: ' . $e->getMessage() ) );
		}
		set_transient( self::NOTICE_TRANSIENT . get_current_user_id(), $result, 120 );
		wp_safe_redirect( self::url() );
		exit;
	}

	/** Retry / cancel a job from the log (administrators only). */
	public function handle_job_action(): void {
		if ( ! current_user_can( self::MANAGE_CAP ) ) {
			wp_die( 'Không có quyền thử lại / huỷ tác vụ dịch.', '', array( 'response' => 403 ) );
		}
		$id = isset( $_POST['job'] ) ? (int) $_POST['job'] : 0;
		check_admin_referer( self::JOB_ACTION . $id );
		$do = isset( $_POST['do'] ) ? sanitize_key( (string) $_POST['do'] ) : '';
		$q  = QPAT_Queue::instance();
		$ok = 'retry' === $do ? $q->retry_job( $id ) : ( 'cancel' === $do ? $q->cancel_job( $id ) : false );
		if ( $ok && 'retry' === $do ) {
			$q->spawn_worker();
		}
		$back = wp_get_referer() ? wp_get_referer() : self::url( self::PAGE_LOG );
		wp_safe_redirect( add_query_arg( 'qpat_done', $ok ? $do : 'fail', $back ) );
		exit;
	}

	/** "Dịch / cập nhật" from the content list. */
	public function handle_row_translate(): void {
		$id = isset( $_POST['post'] ) ? (int) $_POST['post'] : 0;
		check_admin_referer( self::ROW_ACTION . $id );
		if ( ! $id || ! current_user_can( 'edit_post', $id ) ) {
			wp_die( 'Không có quyền.', '', array( 'response' => 403 ) );
		}
		$n = QPAT_Queue::instance()->enqueue_post( $id, false );
		if ( $n ) {
			QPAT_Queue::instance()->spawn_worker();
		}
		QPAT_Status::refresh( $id );
		$back = wp_get_referer() ? wp_get_referer() : self::url( self::PAGE_CONTENT );
		wp_safe_redirect( add_query_arg( 'qpat_queued', $n, $back ) );
		exit;
	}

	// ------------------------------------------------------------------ Bản dịch

	public function render_content(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification
		$s      = QPAT_Settings::get();
		$types  = $s['post_types'];
		$flag   = isset( $_GET['flag'] ) ? sanitize_key( (string) $_GET['flag'] ) : '';
		$type   = isset( $_GET['ptype'] ) ? sanitize_key( (string) $_GET['ptype'] ) : '';
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['s'] ) ) : '';
		$paged  = max( 1, isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1 );
		$queued = isset( $_GET['qpat_queued'] ) ? (int) $_GET['qpat_queued'] : null;
		// phpcs:enable WordPress.Security.NonceVerification
		if ( '' !== $type && in_array( $type, $types, true ) ) {
			$types = array( $type );
		}

		$extra = array();
		if ( isset( QPAT_Status::FLAGS[ $flag ] ) ) {
			$extra = array(
				'key'   => QPAT_Status::META_FLAG,
				'value' => $flag,
			);
		}
		$q = $types ? new WP_Query(
			array(
				'post_type'        => $types,
				'post_status'      => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'posts_per_page'   => 25,
				'paged'            => $paged,
				's'                => $search,
				'orderby'          => 'modified',
				'order'            => 'DESC',
				'suppress_filters' => true,
				'lang'             => '',
				'meta_query'       => QPAT_Status::source_meta_query( $extra ),
			)
		) : null;
		?>
		<div class="wrap">
			<h1>Bản dịch</h1>
			<p>Mỗi bài tiếng Việt và trạng thái bản tiếng Anh / tiếng Trung. Bấm vào trạng thái để mở bản dịch.</p>
			<?php if ( null !== $queued ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $queued ? 'Đã đưa ' . $queued . ' bản dịch vào hàng đợi.' : 'Không có gì cần dịch: bản dịch đã khớp với bài tiếng Việt.' ); ?></p></div>
			<?php endif; ?>
			<?php if ( ! get_option( QPAT_Status::BACKFILL_OPT ) ) : ?>
				<div class="notice notice-info"><p>Đang tính trạng thái cho các bài đăng trước khi cài plugin (chạy ở nền, vài phút). Bộ lọc có thể chưa đủ bài.</p></div>
			<?php endif; ?>

			<ul class="subsubsub">
				<?php
				$links = array( '' => 'Tất cả' ) + QPAT_Status::FLAGS;
				$i     = 0;
				foreach ( $links as $key => $label ) {
					printf(
						'<li><a href="%s"%s>%s</a>%s</li>',
						esc_url( self::url( self::PAGE_CONTENT, array_filter( array( 'flag' => $key, 'ptype' => $type ) ) ) ),
						$flag === $key ? ' class="current" aria-current="page"' : '',
						esc_html( $label ),
						++$i < count( $links ) ? ' |' : ''
					);
				}
				?>
			</ul>
			<form method="get" style="clear:both;margin:8px 0">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_CONTENT ); ?>">
				<input type="hidden" name="flag" value="<?php echo esc_attr( $flag ); ?>">
				<select name="ptype">
					<option value="">Mọi loại nội dung</option>
					<?php foreach ( $s['post_types'] as $pt ) : ?>
						<?php $obj = get_post_type_object( $pt ); ?>
						<option value="<?php echo esc_attr( $pt ); ?>" <?php selected( $type, $pt ); ?>><?php echo esc_html( $obj ? $obj->labels->singular_name : $pt ); ?></option>
					<?php endforeach; ?>
				</select>
				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Tìm tiêu đề">
				<?php submit_button( 'Lọc', 'secondary', '', false ); ?>
			</form>

			<table class="widefat striped">
				<thead><tr><th>Bài tiếng Việt</th><th>Loại</th><th>EN</th><th>ZH</th><th>Sửa lần cuối</th><th></th></tr></thead>
				<tbody>
				<?php if ( ! $q || ! $q->have_posts() ) : ?>
					<tr><td colspan="6">Không có bài nào.</td></tr>
				<?php else : ?>
					<?php foreach ( $q->posts as $p ) : ?>
						<?php $o = get_post_type_object( $p->post_type ); ?>
						<tr>
							<td><a href="<?php echo esc_url( (string) get_edit_post_link( $p->ID ) ); ?>"><strong><?php echo esc_html( get_the_title( $p ) ?: '(không tiêu đề)' ); ?></strong></a>
								<?php if ( 'publish' !== $p->post_status ) : ?> <span class="qpat-muted">— <?php echo esc_html( (string) get_post_status_object( $p->post_status )->label ); ?></span><?php endif; ?></td>
							<td><?php echo esc_html( $o ? $o->labels->singular_name : $p->post_type ); ?></td>
							<?php foreach ( array( 'en', 'zh' ) as $lang ) : ?>
								<td><?php $this->state_cell( $p, $lang, $s['target_langs'] ); ?></td>
							<?php endforeach; ?>
							<td><?php echo esc_html( mysql2date( 'd/m/Y H:i', $p->post_modified ) ); ?></td>
							<td>
								<?php if ( current_user_can( 'edit_post', $p->ID ) ) : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
										<input type="hidden" name="action" value="<?php echo esc_attr( self::ROW_ACTION ); ?>">
										<input type="hidden" name="post" value="<?php echo (int) $p->ID; ?>">
										<?php wp_nonce_field( self::ROW_ACTION . $p->ID ); ?>
										<button class="button button-small">Dịch / cập nhật</button>
									</form>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
			<?php
			if ( $q ) {
				self::pagination( $paged, (int) $q->max_num_pages );
			}
			?>
			<p class="description">Trạng thái: <strong>Dịch tự động</strong> (chưa ai sửa) · <strong>Đã sửa tay</strong> · <strong>Đã duyệt</strong> · <strong>Cần duyệt</strong> (đã sửa tay, sau đó bài tiếng Việt đổi ở cùng đoạn) · <strong>Cần cập nhật</strong> (bài tiếng Việt đổi sau lần dịch) · <strong>Đã dịch (plugin cũ)</strong>.</p>
		</div>
		<?php
	}

	private function state_cell( WP_Post $p, string $lang, array $targets ): void {
		if ( ! in_array( $lang, $targets, true ) ) {
			echo '—';
			return;
		}
		$st    = QPAT_Status::lang_state( $p, $lang );
		$badge = '<span class="qpat-state qpat-state--' . esc_attr( $st['state'] ) . '">' . esc_html( QPAT_Status::label( $st['state'] ) ) . '</span>';
		if ( $st['copy_id'] ) {
			echo '<a href="' . esc_url( (string) get_edit_post_link( $st['copy_id'] ) ) . '">' . $badge . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
		} else {
			echo $badge; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		if ( in_array( $st['state'], array( 'error', 'paused' ), true ) && $st['job'] ) {
			echo '<br><span class="qpat-muted">' . esc_html( wp_html_excerpt( (string) $st['job']['message'], 90, '…' ) ) . '</span>';
		}
	}

	private static function pagination( int $current, int $total ): void {
		if ( $total < 2 ) {
			return;
		}
		echo '<div class="tablenav"><div class="tablenav-pages">';
		echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput
			array(
				'base'    => add_query_arg( 'paged', '%#%' ),
				'format'  => '',
				'current' => $current,
				'total'   => $total,
			)
		);
		echo '</div></div>';
	}

	// ------------------------------------------------------------------ Nhật ký

	public function render_log(): void {
		if ( ! current_user_can( self::MANAGE_CAP ) ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification
		$status = isset( $_GET['status'] ) ? sanitize_key( (string) $_GET['status'] ) : '';
		$paged  = max( 1, isset( $_GET['paged'] ) ? (int) $_GET['paged'] : 1 );
		$done   = isset( $_GET['qpat_done'] ) ? sanitize_key( (string) $_GET['qpat_done'] ) : '';
		// phpcs:enable WordPress.Security.NonceVerification
		if ( ! isset( QPAT_Queue::STATUSES[ $status ] ) ) {
			$status = '';
		}
		$q     = QPAT_Queue::instance();
		$jobs  = $q->list_jobs( $status, $paged, 30 );
		$total = $q->count_jobs( $status );
		?>
		<div class="wrap">
			<h1>Nhật ký dịch</h1>
			<?php if ( '' !== $done ) : ?>
				<div class="notice <?php echo 'fail' === $done ? 'notice-error' : 'notice-success'; ?> is-dismissible"><p><?php echo esc_html( 'retry' === $done ? 'Đã đưa lại vào hàng đợi.' : ( 'cancel' === $done ? 'Đã huỷ tác vụ.' : 'Không thực hiện được (trạng thái tác vụ đã thay đổi).' ) ); ?></p></div>
			<?php endif; ?>
			<ul class="subsubsub">
				<?php
				$links = array( '' => 'Tất cả' ) + QPAT_Queue::STATUSES;
				$i     = 0;
				foreach ( $links as $key => $label ) {
					printf(
						'<li><a href="%s"%s>%s <span class="count">(%d)</span></a>%s</li>',
						esc_url( self::url( self::PAGE_LOG, array_filter( array( 'status' => $key ) ) ) ),
						$status === $key ? ' class="current"' : '',
						esc_html( $label ),
						(int) $q->count_jobs( (string) $key ),
						++$i < count( $links ) ? ' |' : ''
					);
				}
				?>
			</ul>
			<table class="widefat striped" style="clear:both">
				<thead><tr><th>#</th><th>Bài tiếng Việt</th><th>Ngôn ngữ</th><th>Trạng thái</th><th>Loại lỗi</th><th>Công cụ</th><th>Ký tự</th><th>Lần thử</th><th>Cập nhật</th><th>Ghi chú</th><th></th></tr></thead>
				<tbody>
				<?php if ( ! $jobs ) : ?>
					<tr><td colspan="11">Chưa có tác vụ nào.</td></tr>
				<?php endif; ?>
				<?php foreach ( $jobs as $job ) : ?>
					<?php
					$pid    = (int) $job['source_post_id'];
					$target = (int) $job['target_post_id'];
					$lang   = strtoupper( (string) $job['target_lang'] );
					?>
					<tr>
						<td><?php echo (int) $job['id']; ?></td>
						<td><a href="<?php echo esc_url( (string) get_edit_post_link( $pid ) ); ?>"><?php echo esc_html( get_the_title( $pid ) ?: '#' . $pid ); ?></a></td>
						<td><?php echo $target ? '<a href="' . esc_url( (string) get_edit_post_link( $target ) ) . '">' . esc_html( $lang ) . '</a>' : esc_html( $lang ); // phpcs:ignore WordPress.Security.EscapeOutput ?></td>
						<td><span class="qpat-state qpat-state--<?php echo esc_attr( (string) $job['status'] ); ?>"><?php echo esc_html( QPAT_Editor::status_label( (string) $job['status'] ) ); ?></span><?php echo ! empty( $job['overwrite'] ) ? ' <span class="qpat-muted">(toàn bộ)</span>' : ''; ?></td>
						<td><?php echo esc_html( QPAT_Queue::ERROR_KINDS[ (string) ( $job['error_kind'] ?? '' ) ] ?? '' ); ?></td>
						<td><?php echo esc_html( (string) ( QPAT_Settings::ENGINES[ (string) $job['engine'] ] ?? '' ) ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) ( $job['chars'] ?? 0 ) ) ); ?></td>
						<td><?php echo (int) $job['attempts']; ?></td>
						<td><?php echo esc_html( mysql2date( 'd/m/Y H:i', get_date_from_gmt( (string) $job['updated_at'] ) ) ); ?></td>
						<td style="max-width:360px;overflow-wrap:anywhere"><?php echo esc_html( (string) $job['message'] ); ?></td>
						<td>
							<?php if ( in_array( $job['status'], array( 'error', 'paused', 'cancelled', 'skipped' ), true ) ) : ?>
								<?php self::job_button( (int) $job['id'], 'retry', 'Thử lại' ); ?>
							<?php endif; ?>
							<?php if ( in_array( $job['status'], array( 'queued', 'paused' ), true ) ) : ?>
								<?php self::job_button( (int) $job['id'], 'cancel', 'Huỷ' ); ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php self::pagination( $paged, (int) ceil( $total / 30 ) ); ?>
			<p class="description">Lỗi tạm thời (Gemini quá tải, mất kết nối) được tự thử lại sau 2, 4, 8, 16, 30 phút. Tác vụ “Tạm dừng” do hết hạn mức chỉ chạy lại khi cấu hình được thay đổi hoặc bấm “Thử lại”. Chỉ quản trị viên được thử lại / huỷ. Tác vụ đã xong được xoá khỏi nhật ký sau 60 ngày.</p>
		</div>
		<?php
	}

	private static function job_button( int $id, string $do, string $label ): void {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
			<input type="hidden" name="action" value="<?php echo esc_attr( self::JOB_ACTION ); ?>">
			<input type="hidden" name="job" value="<?php echo (int) $id; ?>">
			<input type="hidden" name="do" value="<?php echo esc_attr( $do ); ?>">
			<?php wp_nonce_field( self::JOB_ACTION . $id ); ?>
			<button class="button button-small"><?php echo esc_html( $label ); ?></button>
		</form>
		<?php
	}

	// ------------------------------------------------------------------ Cài đặt

	private static function key_input( string $field, string $clear_field, string $stored, bool $from_constant, string $constant ): void {
		$name = QPAT_Settings::OPTION;
		if ( $from_constant ) {
			echo '<p><code>' . esc_html( $constant ) . '</code> đang được khai báo trong <code>wp-config.php</code> — key này được dùng.</p>';
			return;
		}
		printf(
			'<input type="password" class="regular-text" id="qpat_%1$s" name="%2$s[%1$s]" value="%3$s" autocomplete="off">',
			esc_attr( $field ),
			esc_attr( $name ),
			esc_attr( QPAT_Settings::mask( $stored ) )
		);
		if ( '' !== $stored ) {
			printf(
				'<label style="margin-left:8px"><input type="checkbox" name="%1$s[%2$s]" value="1"> Xoá key</label>',
				esc_attr( $name ),
				esc_attr( $clear_field )
			);
		}
	}

	private static function test_button( string $engine, string $label ): void {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:8px">
			<input type="hidden" name="action" value="<?php echo esc_attr( self::TEST_ACTION ); ?>">
			<input type="hidden" name="engine" value="<?php echo esc_attr( $engine ); ?>">
			<?php wp_nonce_field( self::TEST_ACTION ); ?>
			<?php submit_button( $label, 'secondary', 'submit', false ); ?>
		</form>
		<?php
	}

	public function render(): void {
		if ( ! current_user_can( self::MANAGE_CAP ) ) {
			return;
		}
		$s       = QPAT_Settings::get();
		$st      = QPAT_Usage::status();
		$by_lang = QPAT_Usage::by_lang();
		$gemini  = QPAT_Usage::by_lang( '', 'gemini' );
		$name    = QPAT_Settings::OPTION;
		$models  = (array) get_option( self::MODELS_OPTION, array() );
		if ( ! in_array( QPAT_Settings::DEFAULT_GEMINI_MODEL, $models, true ) ) {
			$models[] = QPAT_Settings::DEFAULT_GEMINI_MODEL;
		}

		$test = get_transient( self::NOTICE_TRANSIENT . get_current_user_id() );
		if ( $test ) {
			delete_transient( self::NOTICE_TRANSIENT . get_current_user_id() );
		}
		?>
		<div class="wrap">
			<h1>Cài đặt dịch tự động</h1>
			<p>Tiếng Việt là ngôn ngữ gốc. Bài tiếng Việt được dịch sang tiếng Anh và tiếng Trung khi bấm <strong>Đăng</strong> hoặc <strong>Cập nhật</strong> (chỉ các đoạn mới / đã sửa). Việc dịch chạy ở nền; bản dịch là bài riêng, sửa được như bài thường và không đồng bộ ngược về tiếng Việt.</p>

			<?php if ( is_array( $test ) ) : ?>
				<?php foreach ( $test as $row ) : ?>
					<div class="notice <?php echo $row['ok'] ? 'notice-success' : 'notice-error'; ?>"><p><?php echo esc_html( ( $row['ok'] ? 'Thành công – ' : 'Lỗi – ' ) . $row['msg'] ); ?></p></div>
				<?php endforeach; ?>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'qpat' ); ?>

				<h2>Công cụ dịch</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Dùng để dịch</th>
						<td>
							<fieldset>
								<label style="display:block;margin-bottom:6px"><input type="radio" name="<?php echo esc_attr( $name ); ?>[engine]" value="gemini" <?php checked( 'gemini', $s['engine'] ); ?>> <strong>Google Gemini (AI)</strong> — mặc định, dịch tự nhiên, sát văn phong bài viết.</label>
								<label style="display:block"><input type="radio" name="<?php echo esc_attr( $name ); ?>[engine]" value="google" <?php checked( 'google', $s['engine'] ); ?>> <strong>Google Cloud Translation (NMT)</strong> — có giới hạn ký tự hằng tháng; dùng khi Gemini gặp sự cố.</label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">Dự phòng</th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[fallback_google]" value="1" <?php checked( ! empty( $s['fallback_google'] ) ); ?>> Khi Gemini lỗi (hết lượt, sai key, model ngừng hoạt động…), tự dịch bài đó bằng Google Cloud Translation</label>
							<p class="description">Cần nhập Google Cloud API key bên dưới. Không bật thì bài lỗi sẽ được tự thử lại sau ít phút (lỗi tạm thời) hoặc ghi vào Nhật ký.</p></td>
					</tr>
				</table>

				<h2>Google Gemini</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="qpat_gemini_api_key">Gemini API key</label></th>
						<td>
							<?php self::key_input( 'gemini_api_key', 'clear_gemini_api_key', (string) $s['gemini_api_key'], QPAT_Settings::gemini_key_from_constant(), 'QPAT_GEMINI_API_KEY' ); ?>
							<p class="description">Tạo key tại Google AI Studio (aistudio.google.com → Get API key). Để nguyên giá trị đã che để giữ key hiện tại.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="qpat_gemini_model">Model</label></th>
						<td>
							<input type="text" class="regular-text" id="qpat_gemini_model" list="qpat_models" name="<?php echo esc_attr( $name ); ?>[gemini_model]" value="<?php echo esc_attr( $s['gemini_model'] ); ?>">
							<datalist id="qpat_models">
								<?php foreach ( $models as $m ) : ?>
									<option value="<?php echo esc_attr( (string) $m ); ?>"></option>
								<?php endforeach; ?>
							</datalist>
							<p class="description">Bấm vào ô để chọn trong danh sách, hoặc gõ tên model. Mặc định: <code><?php echo esc_html( QPAT_Settings::DEFAULT_GEMINI_MODEL ); ?></code>. Dùng nút “Tải danh sách model” bên dưới để lấy đúng các model key của bạn dùng được.</p>
						</td>
					</tr>
				</table>

				<h2>Google Cloud Translation</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="qpat_api_key">Google Cloud API key</label></th>
						<td>
							<?php self::key_input( 'api_key', 'clear_api_key', (string) $s['api_key'], QPAT_Settings::api_key_from_constant(), 'QPAT_GOOGLE_API_KEY' ); ?>
							<p class="description">Bật <strong>Cloud Translation API</strong> trong Google Cloud Console rồi tạo API key (nên giới hạn key chỉ dùng cho Cloud Translation API).</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="qpat_limit">Giới hạn ký tự / tháng</label></th>
						<td>
							<input type="number" min="0" step="1000" id="qpat_limit" name="<?php echo esc_attr( $name ); ?>[monthly_limit]" value="<?php echo esc_attr( (string) $s['monthly_limit'] ); ?>" class="regular-text">
							<p class="description">Mặc định 500.000 (mức miễn phí của Google). Khi đạt giới hạn, các tác vụ dịch bằng Google tạm dừng và không gọi API nữa cho tới khi cấu hình được thay đổi. Chỉ tăng khi đã được xác nhận chi phí. 0 = không giới hạn (không khuyến nghị).</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="qpat_warn">Cảnh báo khi đạt</label></th>
						<td><input type="number" min="1" max="100" id="qpat_warn" name="<?php echo esc_attr( $name ); ?>[warn_percent]" value="<?php echo esc_attr( (string) $s['warn_percent'] ); ?>" class="small-text"> % hạn mức</td>
					</tr>
				</table>

				<h2>Thuật ngữ và nội dung không dịch</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="qpat_glossary">Thuật ngữ, bản dịch ưu tiên</label></th>
						<td>
							<textarea id="qpat_glossary" name="<?php echo esc_attr( $name ); ?>[glossary]" rows="6" class="large-text code" placeholder="Khu đô thị QP Green Park | QP Green Park Urban Area | QP Green Park 新城区"><?php echo esc_textarea( (string) $s['glossary'] ); ?></textarea>
							<p class="description">Mỗi dòng: <code>Tiếng Việt | English | 中文</code>. Khi bài có cụm tiếng Việt này, bản dịch luôn dùng đúng cách viết đã khai báo.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="qpat_no_translate">Từ khoá, tên riêng không dịch</label></th>
						<td>
							<textarea id="qpat_no_translate" name="<?php echo esc_attr( $name ); ?>[no_translate]" rows="4" class="large-text code"><?php echo esc_textarea( (string) $s['no_translate'] ); ?></textarea>
							<p class="description">Mỗi dòng một từ / tên (thương hiệu, tên dự án, mã chứng khoán…), giữ nguyên trong mọi ngôn ngữ. Ảnh, video, mã nhúng, shortcode luôn được giữ nguyên. Trong bài, đoạn đặt trong <code>&lt;span class="notranslate"&gt;…&lt;/span&gt;</code> hoặc giữa <code>&lt;!--notranslate--&gt;</code> và <code>&lt;!--/notranslate--&gt;</code> cũng không bị dịch.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="qpat_skip_fields">Trường dữ liệu không dịch</label></th>
						<td>
							<textarea id="qpat_skip_fields" name="<?php echo esc_attr( $name ); ?>[skip_fields]" rows="3" class="large-text code" placeholder="author_name"><?php echo esc_textarea( (string) $s['skip_fields'] ); ?></textarea>
							<p class="description">Mỗi dòng một tên trường (ví dụ <code>author_name</code>, <code>excerpt</code>). Trường này được chép nguyên văn từ bài tiếng Việt sang bản dịch.</p>
						</td>
					</tr>
				</table>

				<h2>Phạm vi dịch</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Ngôn ngữ</th>
						<td>
							Tiếng Việt (gốc) →
							<?php foreach ( array( 'en', 'zh' ) as $lang ) : ?>
								<label style="margin:0 12px 0 6px"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[target_langs][]" value="<?php echo esc_attr( $lang ); ?>" <?php checked( in_array( $lang, $s['target_langs'], true ) ); ?>> <?php echo esc_html( QPAT_Settings::LANGUAGES[ $lang ] ); ?></label>
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th scope="row">Loại nội dung được dịch</th>
						<td>
							<fieldset>
								<?php foreach ( QPAT_Settings::selectable_post_types() as $type => $label ) : ?>
									<label style="display:block;margin:3px 0"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[post_types][]" value="<?php echo esc_attr( $type ); ?>" <?php checked( in_array( $type, $s['post_types'], true ) ); ?>> <?php echo esc_html( $label ); ?></label>
								<?php endforeach; ?>
							</fieldset>
							<p class="description">Tin nhắn liên hệ, hồ sơ ứng tuyển, đăng ký tư vấn và đăng ký nhận tin không bao giờ được gửi đi dịch (dữ liệu cá nhân).</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Chế độ xuất bản</th>
						<td>
							<label style="display:block;margin-bottom:6px"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[auto_on_publish]" value="1" <?php checked( ! empty( $s['auto_on_publish'] ) ); ?>> Tự động tạo bản tiếng Anh, tiếng Trung khi <strong>đăng</strong> bài tiếng Việt</label>
							<label style="display:block"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[auto_update]" value="1" <?php checked( ! empty( $s['auto_update'] ) ); ?>> Tự động cập nhật bản dịch khi bấm <strong>Cập nhật</strong> bài tiếng Việt đã đăng (chỉ dịch đoạn mới / đã sửa)</label>
							<p class="description">Lưu nháp không bao giờ gọi dịch. Bản EN / ZH được đăng / gỡ theo bài tiếng Việt. Đoạn đã sửa tay ở bản dịch được giữ nguyên; nếu đoạn tiếng Việt tương ứng cũng đổi, đoạn đó được đánh dấu “Cần duyệt”. Bản dịch tạo bởi plugin cũ không tự cập nhật (tránh mất phần đã sửa tay) — bấm “Dịch lại toàn bộ” khi cần.</p>
						</td>
					</tr>
				</table>
				<?php submit_button( 'Lưu cấu hình' ); ?>
			</form>

			<h2>Kiểm tra</h2>
			<p>Dịch thử câu “Xin chào, đây là QP Holdings.” sang các ngôn ngữ đã chọn. Lưu cấu hình trước khi kiểm tra.</p>
			<p>
				<?php self::test_button( 'gemini', 'Kiểm tra Gemini' ); ?>
				<?php self::test_button( 'google', 'Kiểm tra Google Cloud Translation' ); ?>
			</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::MODELS_ACTION ); ?>">
				<?php wp_nonce_field( self::MODELS_ACTION ); ?>
				<?php submit_button( 'Tải danh sách model Gemini', 'secondary', 'submit', false ); ?>
			</form>

			<h2>Ký tự đã gửi dịch tháng <?php echo esc_html( QPAT_Usage::period() ); ?></h2>
			<table class="widefat striped" style="max-width:720px">
				<thead><tr><th>Ngôn ngữ đích</th><th style="text-align:right">Google Cloud Translation</th><th style="text-align:right">Gemini</th></tr></thead>
				<tbody>
					<?php foreach ( array( 'en', 'zh' ) as $lang ) : ?>
						<tr>
							<td><?php echo esc_html( 'Tiếng Việt → ' . QPAT_Settings::LANGUAGES[ $lang ] ); ?></td>
							<td style="text-align:right"><?php echo esc_html( number_format_i18n( (int) ( $by_lang[ $lang ] ?? 0 ) ) ); ?></td>
							<td style="text-align:right"><?php echo esc_html( number_format_i18n( (int) ( $gemini[ $lang ] ?? 0 ) ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					<tr>
						<td><strong>Tổng</strong></td>
						<td style="text-align:right">
							<strong><?php echo esc_html( number_format_i18n( $st['used'] ) ); ?></strong>
							<?php if ( $st['limit'] > 0 ) : ?>
								/ <?php echo esc_html( number_format_i18n( $st['limit'] ) ); ?> (<?php echo esc_html( (string) $st['percent'] ); ?>%)
							<?php else : ?>
								(không giới hạn)
							<?php endif; ?>
						</td>
						<td style="text-align:right"><strong><?php echo esc_html( number_format_i18n( (int) array_sum( $gemini ) ) ); ?></strong></td>
					</tr>
				</tbody>
			</table>
			<?php if ( $st['limit'] > 0 ) : ?>
				<?php $color = 'paused' === $st['state'] ? '#d63638' : ( 'warn' === $st['state'] ? '#dba617' : '#00a32a' ); ?>
				<div style="max-width:720px;height:10px;background:#dcdcde;border-radius:5px;margin:8px 0 4px;overflow:hidden">
					<div style="height:100%;width:<?php echo esc_attr( (string) min( 100, $st['percent'] ) ); ?>%;background:<?php echo esc_attr( $color ); ?>"></div>
				</div>
			<?php endif; ?>
			<p class="description">Ký tự được tính riêng cho từng ngôn ngữ đích (4.000 ký tự dịch sang 2 ngôn ngữ ≈ 8.000). Giới hạn tháng áp dụng cho Google Cloud Translation; Gemini chỉ thống kê. Số liệu tính phí chính thức xem trong Google Cloud Console. Đây là số gần đúng.</p>

			<?php $this->render_recent_jobs(); ?>
		</div>
		<?php
	}

	private function render_recent_jobs(): void {
		$jobs = QPAT_Queue::instance()->recent_jobs( 10 );
		echo '<h2>Lịch sử dịch gần đây</h2>';
		if ( ! $jobs ) {
			echo '<p>Chưa có bài nào được dịch bằng plugin này.</p>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr><th>Bài tiếng Việt</th><th>Ngôn ngữ</th><th>Trạng thái</th><th>Công cụ</th><th>Cập nhật</th><th>Ghi chú</th></tr></thead><tbody>';
		foreach ( $jobs as $job ) {
			$pid    = (int) $job['source_post_id'];
			$target = (int) $job['target_post_id'];
			printf(
				'<tr><td><a href="%s">%s</a></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
				esc_url( (string) get_edit_post_link( $pid ) ),
				esc_html( get_the_title( $pid ) ?: '#' . $pid ),
				$target ? '<a href="' . esc_url( (string) get_edit_post_link( $target ) ) . '">' . esc_html( strtoupper( (string) $job['target_lang'] ) ) . '</a>' : esc_html( strtoupper( (string) $job['target_lang'] ) ),
				esc_html( QPAT_Editor::status_label( (string) $job['status'] ) ),
				esc_html( (string) ( QPAT_Settings::ENGINES[ (string) $job['engine'] ] ?? '' ) ),
				esc_html( mysql2date( 'd/m/Y H:i', get_date_from_gmt( (string) $job['updated_at'] ) ) ),
				esc_html( (string) $job['message'] )
			);
		}
		echo '</tbody></table>';
		echo '<p><a href="' . esc_url( self::url( self::PAGE_LOG ) ) . '">Xem toàn bộ nhật ký →</a></p>';
	}
}
