<?php
/**
 * Editor integration:
 *   - first publish of a Vietnamese post → EN / ZH created in the background;
 *   - "Cập nhật" on a published post → only new / changed paragraphs translated;
 *   - saving drafts never calls a translation API;
 *   - shared data (featured image, categories, tags...) synced without API;
 *   - editing an EN / ZH copy never touches the Vietnamese post;
 *   - the "Dịch tự động" box: VI / EN / ZH links, states, review of paragraphs.
 */

defined( 'ABSPATH' ) || exit;

final class QPAT_Editor {
	const NONCE = 'qpat_editor';

	private static ?QPAT_Editor $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		add_action( 'wp_after_insert_post', array( $this, 'on_save' ), 40, 4 );
		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_qpat_queue_now', array( $this, 'ajax_queue_now' ) );
		add_action( 'wp_ajax_qpat_job_status', array( $this, 'ajax_job_status' ) );
		add_action( 'wp_ajax_qpat_review', array( $this, 'ajax_review' ) );
	}

	/**
	 * QP LLM Translate also queues on publish; running both would translate
	 * every post twice, so ours stays off while the old one does it.
	 */
	public static function legacy_auto_active(): bool {
		if ( ! class_exists( 'QP_LLM_Plugin' ) ) {
			return false;
		}
		$legacy = get_option( QPAT_Settings::LEGACY_OPTION, array() );
		return is_array( $legacy ) && ! empty( $legacy['auto_on_publish'] );
	}

	public function on_save( int $post_id, WP_Post $post, bool $update, ?WP_Post $post_before ): void {
		if ( ! empty( $GLOBALS['qpat_suppress_queue'] ) ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'auto-draft' === $post->post_status ) {
			return;
		}
		if ( QPAT_Sync::is_translation_copy( $post_id ) ) {
			$this->on_copy_saved( $post );
			return;
		}
		if ( ! QPAT_Sync::is_source_post( $post ) ) {
			return;
		}

		$was = $post_before instanceof WP_Post ? $post_before->post_status : '';
		if ( $update && $was !== $post->post_status ) {
			QPAT_Sync::sync_status( $post );
		}
		if ( $update ) {
			QPAT_Sync::sync_shared( $post );
		}

		$s = QPAT_Settings::get();
		if ( in_array( $post->post_type, $s['post_types'], true ) && ! self::legacy_auto_active() && 'publish' === $post->post_status ) {
			$first   = 'publish' !== $was && ! ( $update && ! $post_before );
			$updated = 'publish' === $was;
			if ( ( $first && ! empty( $s['auto_on_publish'] ) ) || ( $updated && ! empty( $s['auto_update'] ) ) ) {
				if ( QPAT_Queue::instance()->enqueue_post( $post_id, false ) ) {
					QPAT_Queue::instance()->spawn_worker();
				}
			}
		}
		QPAT_Status::refresh( $post_id );
	}

	/** An editor saved an EN / ZH copy: remember that it now contains hand edits. */
	private function on_copy_saved( WP_Post $copy ): void {
		$state = QPAT_Updater::state( (int) $copy->ID );
		if ( ! in_array( $state, array( 'review', 'legacy', 'outdated' ), true ) ) {
			QPAT_Updater::set_state( (int) $copy->ID, QPAT_Updater::has_hand_edits( (int) $copy->ID ) ? 'edited' : 'auto' );
		}
		$source = (int) get_post_meta( $copy->ID, QPAT_Sync::META_SOURCE_ID, true );
		if ( $source ) {
			QPAT_Status::refresh( $source );
		}
	}

	public function register_metabox(): void {
		foreach ( QPAT_Settings::get()['post_types'] as $type ) {
			add_meta_box( 'qpat-box', 'Dịch tự động (VI → EN / ZH)', array( $this, 'render_metabox' ), $type, 'side', 'high' );
		}
	}

	public function render_metabox( WP_Post $post ): void {
		$family  = QPAT_Sync::family( (int) $post->ID );
		$is_copy = QPAT_Sync::is_translation_copy( (int) $post->ID );
		$labels  = array( 'vi' => 'VI', 'en' => 'EN', 'zh' => 'ZH' );
		$source  = isset( $family['vi'] ) ? get_post( (int) $family['vi'] ) : null;

		echo '<ul class="qpat-family">';
		foreach ( $labels as $lang => $label ) {
			$id = (int) ( $family[ $lang ] ?? 0 );
			echo '<li><span class="qpat-lang">' . esc_html( $label ) . '</span> ';
			if ( $id && $id === (int) $post->ID ) {
				echo '<em>bài đang mở</em>';
			} elseif ( $id ) {
				$p = get_post( $id );
				printf(
					'<a href="%s">%s</a> · <a href="%s" target="_blank" rel="noopener">Xem</a>%s',
					esc_url( (string) get_edit_post_link( $id ) ),
					esc_html( wp_html_excerpt( get_the_title( $id ), 48, '…' ) ),
					esc_url( (string) get_permalink( $id ) ),
					$p && 'publish' !== $p->post_status ? ' <span class="qpat-muted">(' . esc_html( (string) get_post_status_object( $p->post_status )->label ) . ')</span>' : ''
				);
			} else {
				echo '<span class="qpat-muted">chưa có</span>';
			}
			if ( 'vi' !== $lang && $source ) {
				$st = QPAT_Status::lang_state( $source, $lang );
				echo ' <span class="qpat-state qpat-state--' . esc_attr( $st['state'] ) . '">' . esc_html( QPAT_Status::label( $st['state'] ) ) . '</span>';
			}
			echo '</li>';
		}
		echo '</ul>';

		if ( $is_copy ) {
			$this->render_copy_box( $post, $source );
			return;
		}

		$has_any = isset( $family['en'] ) || isset( $family['zh'] );
		$s       = QPAT_Settings::get();
		echo '<p class="qpat-actions">';
		printf(
			'<button type="button" class="button button-primary qpat-queue-now" data-post="%1$d" data-overwrite="0">%2$s</button> ',
			(int) $post->ID,
			$has_any ? 'Cập nhật bản dịch' : 'Dịch ngay'
		);
		if ( $has_any ) {
			printf( '<button type="button" class="button qpat-queue-now" data-post="%d" data-overwrite="1">Dịch lại toàn bộ</button>', (int) $post->ID );
		}
		echo '</p>';
		echo '<p class="description">Công cụ: <strong>' . esc_html( QPAT_Settings::ENGINES[ $s['engine'] ] ) . '</strong>. ';
		echo $has_any
			? '“Cập nhật bản dịch” chỉ dịch các đoạn mới / đã sửa và giữ phần đã sửa tay ở bản EN/ZH. “Dịch lại toàn bộ” ghi đè cả bài.'
			: ( ! empty( $s['auto_on_publish'] ) && ! self::legacy_auto_active() ? 'Bài sẽ tự dịch khi bấm Đăng. Lưu nháp không gọi dịch.' : 'Tự dịch khi đăng bài đang tắt.' );
		echo '</p><div class="qpat-queue-status" aria-live="polite"></div>';

		$jobs = QPAT_Queue::instance()->jobs_for_post( (int) $post->ID );
		if ( $jobs ) {
			echo '<ul class="qpat-jobs">';
			foreach ( $jobs as $job ) {
				printf(
					'<li><strong>%s</strong> %s · %s%s</li>',
					esc_html( strtoupper( (string) $job['target_lang'] ) ),
					esc_html( self::status_label( (string) $job['status'] ) ),
					esc_html( mysql2date( 'd/m H:i', get_date_from_gmt( (string) $job['updated_at'] ) ) ),
					'' !== (string) $job['message'] ? '<br><span class="qpat-muted">' . esc_html( (string) $job['message'] ) . '</span>' : ''
				);
			}
			echo '</ul>';
		}
	}

	private function render_copy_box( WP_Post $copy, ?WP_Post $source ): void {
		$lang  = strtoupper( (string) get_post_meta( $copy->ID, QPAT_Sync::META_LANG, true ) );
		$state = QPAT_Updater::state( (int) $copy->ID );
		$time  = (string) get_post_meta( $copy->ID, QPAT_Sync::META_TIME, true );
		$rev   = (string) get_post_meta( $copy->ID, QPAT_Updater::META_REV, true );

		echo '<p class="description">Bản ' . esc_html( $lang ) . ' dịch từ bài tiếng Việt. Sửa ở đây không làm thay đổi bài tiếng Việt.</p>';
		echo '<p class="qpat-meta">Trạng thái: <strong>' . esc_html( QPAT_Updater::STATES[ $state ] ?? $state ) . '</strong>';
		if ( '' !== $time ) {
			echo '<br>Dịch lúc: ' . esc_html( mysql2date( 'd/m/Y H:i', $time ) );
		}
		if ( '' !== $rev ) {
			echo '<br>Từ phiên bản tiếng Việt: ' . esc_html( 0 === strpos( $rev, 'm:' ) ? mysql2date( 'd/m/Y H:i', get_date_from_gmt( substr( $rev, 2 ) ) ) : '#' . $rev );
		}
		if ( $source && QPAT_Status::is_outdated( $source, (int) $copy->ID ) ) {
			echo '<br><span class="qpat-warn">Bài tiếng Việt đã thay đổi sau lần dịch này.</span>';
		}
		echo '</p>';

		$items = QPAT_Updater::reviews( (int) $copy->ID );
		if ( $items ) {
			echo '<div class="qpat-review"><p><strong>' . esc_html( count( $items ) . ' phần cần duyệt' ) . '</strong> — bạn đã sửa tay phần này, sau đó bài tiếng Việt cũng thay đổi.</p>';
			foreach ( $items as $i => $item ) {
				echo '<div class="qpat-review__item">';
				if ( 'field' === ( $item['type'] ?? '' ) ) {
					echo '<p class="qpat-muted">Trường: ' . esc_html( (string) $item['key'] ) . '</p>';
				}
				echo '<p><span class="qpat-review__lbl">Tiếng Việt mới</span>' . esc_html( wp_html_excerpt( wp_strip_all_tags( (string) $item['src'] ), 220, '…' ) ) . '</p>';
				echo '<p><span class="qpat-review__lbl">Đang dùng (sửa tay)</span>' . esc_html( wp_html_excerpt( wp_strip_all_tags( (string) $item['kept'] ), 220, '…' ) ) . '</p>';
				echo '<p><span class="qpat-review__lbl">Bản dịch mới</span>' . esc_html( wp_html_excerpt( wp_strip_all_tags( (string) $item['suggest'] ), 220, '…' ) ) . '</p>';
				printf(
					'<p><button type="button" class="button button-small qpat-review-do" data-copy="%1$d" data-index="%2$d" data-do="accept">Dùng bản dịch mới</button> <button type="button" class="button button-small qpat-review-do" data-copy="%1$d" data-index="%2$d" data-do="keep">Giữ bản đang dùng</button></p>',
					(int) $copy->ID,
					(int) $i
				);
				echo '</div>';
			}
			echo '</div>';
		} elseif ( 'edited' === $state ) {
			printf( '<p><button type="button" class="button qpat-review-do" data-copy="%d" data-index="-1" data-do="approve">Đánh dấu đã duyệt</button></p>', (int) $copy->ID );
		}
		if ( $source ) {
			$button = in_array( $state, array( 'legacy', 'outdated' ), true ) && null === QPAT_Updater::map( (int) $copy->ID ) ? 'Dịch lại toàn bộ' : 'Cập nhật bản dịch';
			echo '<p class="description">Muốn dịch lại: mở <a href="' . esc_url( (string) get_edit_post_link( $source->ID ) ) . '">bài tiếng Việt</a> và bấm “' . esc_html( $button ) . '”.</p>';
		}
	}

	public static function status_label( string $status ): string {
		return QPAT_Queue::STATUSES[ $status ] ?? $status;
	}

	public function enqueue_assets( string $hook ): void {
		$ours = false !== strpos( $hook, 'qpat-' ) || false !== strpos( $hook, QPAT_Admin::PAGE );
		if ( ( ! $ours && ! in_array( $hook, array( 'post.php', 'post-new.php', 'edit.php' ), true ) ) || ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		$css = QPAT_DIR . 'assets/editor.css';
		$js  = QPAT_DIR . 'assets/editor.js';
		wp_enqueue_style( 'qpat-editor', QPAT_URL . 'assets/editor.css', array(), (string) filemtime( $css ) );
		wp_enqueue_script( 'qpat-editor', QPAT_URL . 'assets/editor.js', array( 'jquery' ), (string) filemtime( $js ), true );
		wp_localize_script(
			'qpat-editor',
			'qpatEditor',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::NONCE ),
				'i18n'    => array(
					'confirmOverwrite' => 'Dịch lại toàn bộ sẽ GHI ĐÈ nội dung bản tiếng Anh / tiếng Trung hiện có, kể cả phần đã sửa tay và ảnh đã thay. Tiếp tục?',
					'queued'           => 'Đã đưa vào hàng đợi, đang dịch ở nền. Có thể tiếp tục làm việc khác.',
					'nothing'          => 'Không có gì cần dịch: bản dịch đã khớp với bài tiếng Việt.',
					'error'            => 'Không thể thực hiện.',
					'busy'             => 'Đang dịch',
					'done'             => 'Đã dịch xong',
					'failed'           => 'Có bản dịch bị lỗi hoặc tạm dừng',
					'status'           => QPAT_Queue::STATUSES,
				),
			)
		);
	}

	public function ajax_queue_now(): void {
		check_ajax_referer( self::NONCE, 'nonce' );
		$post_id   = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		$overwrite = ! empty( $_POST['overwrite'] );
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => 'Không có quyền.' ), 403 );
		}
		$queued = QPAT_Queue::instance()->enqueue_post( $post_id, $overwrite );
		if ( $queued ) {
			QPAT_Queue::instance()->spawn_worker();
		}
		QPAT_Status::refresh( $post_id );
		wp_send_json_success(
			array(
				'queued' => $queued,
				'jobs'   => QPAT_Queue::instance()->status_jobs(),
			)
		);
	}

	public function ajax_job_status(): void {
		check_ajax_referer( self::NONCE, 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Không có quyền.' ), 403 );
		}
		wp_send_json_success( array( 'jobs' => QPAT_Queue::instance()->status_jobs() ) );
	}

	public function ajax_review(): void {
		check_ajax_referer( self::NONCE, 'nonce' );
		$copy_id = isset( $_POST['copy_id'] ) ? (int) $_POST['copy_id'] : 0;
		$index   = isset( $_POST['index'] ) ? (int) $_POST['index'] : -1;
		$do      = isset( $_POST['do'] ) ? sanitize_key( (string) $_POST['do'] ) : '';
		if ( ! $copy_id || ! QPAT_Sync::is_translation_copy( $copy_id ) || ! current_user_can( 'edit_post', $copy_id ) ) {
			wp_send_json_error( array( 'message' => 'Không có quyền.' ), 403 );
		}
		if ( 'approve' === $do ) {
			QPAT_Updater::set_state( $copy_id, 'approved' );
			$ok = true;
		} elseif ( in_array( $do, array( 'accept', 'keep' ), true ) ) {
			$ok = QPAT_Updater::resolve( $copy_id, $index, $do );
		} else {
			$ok = false;
		}
		$source = (int) get_post_meta( $copy_id, QPAT_Sync::META_SOURCE_ID, true );
		if ( $source ) {
			QPAT_Status::refresh( $source );
		}
		if ( ! $ok ) {
			wp_send_json_error( array( 'message' => 'Không áp dụng được: nội dung đã thay đổi, hãy sửa trực tiếp trong bài.' ), 409 );
		}
		wp_send_json_success();
	}
}
