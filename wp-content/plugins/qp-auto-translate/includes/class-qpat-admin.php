<?php
/**
 * Admin screen: Cài đặt → Dịch tự động.
 */

defined( 'ABSPATH' ) || exit;

final class QPAT_Admin {
	const PAGE          = 'qp-auto-translate';
	const TEST_ACTION   = 'qpat_test_connection';
	const NOTICE_TRANSIENT = 'qpat_test_result_';

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
		add_action( 'admin_notices', array( $this, 'quota_notice' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( QPAT_FILE ), array( $this, 'action_links' ) );
	}

	public function menu(): void {
		add_options_page( 'Dịch tự động', 'Dịch tự động', 'manage_options', self::PAGE, array( $this, 'render' ) );
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

	public function action_links( array $links ): array {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=' . self::PAGE ) ) . '">Cài đặt</a>' );
		return $links;
	}

	/** Warn every admin screen when usage reaches the warning threshold. */
	public function quota_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$st = QPAT_Usage::status();
		if ( 'warn' !== $st['state'] && 'paused' !== $st['state'] ) {
			return;
		}
		$class = 'paused' === $st['state'] ? 'notice-error' : 'notice-warning';
		$text  = 'paused' === $st['state']
			? 'Dịch tự động đã TẠM DỪNG: đã dùng hết hạn mức ký tự tháng này (%1$s / %2$s). Chỉ tăng hạn mức khi đã được xác nhận chi phí.'
			: 'Dịch tự động: đã dùng %3$s%% hạn mức ký tự tháng này (%1$s / %2$s).';
		printf(
			'<div class="notice %1$s"><p>%2$s <a href="%3$s">Xem chi tiết</a></p></div>',
			esc_attr( $class ),
			esc_html( sprintf( $text, number_format_i18n( $st['used'] ), number_format_i18n( $st['limit'] ), $st['percent'] ) ),
			esc_url( admin_url( 'options-general.php?page=' . self::PAGE ) )
		);
	}

	public function handle_test(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Không có quyền.' );
		}
		check_admin_referer( self::TEST_ACTION );

		$result = array();
		foreach ( QPAT_Settings::get()['target_langs'] as $lang ) {
			try {
				$t = QPAT_Google_Client::translate( array( 'Xin chào, đây là QP Holdings.' ), $lang );
				$result[] = array( 'ok' => true, 'msg' => strtoupper( $lang ) . ': ' . $t[0] );
			} catch ( Throwable $e ) {
				$result[] = array( 'ok' => false, 'msg' => strtoupper( $lang ) . ': ' . $e->getMessage() );
			}
		}
		if ( ! $result ) {
			$result[] = array( 'ok' => false, 'msg' => 'Chưa chọn ngôn ngữ đích.' );
		}
		set_transient( self::NOTICE_TRANSIENT . get_current_user_id(), $result, 120 );
		wp_safe_redirect( admin_url( 'options-general.php?page=' . self::PAGE ) );
		exit;
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s       = QPAT_Settings::get();
		$st      = QPAT_Usage::status();
		$by_lang = QPAT_Usage::by_lang();
		$name    = QPAT_Settings::OPTION;
		$stored  = (string) $s['api_key'];
		$masked  = '' !== $stored ? '••••••••' . substr( $stored, -4 ) : '';

		$test = get_transient( self::NOTICE_TRANSIENT . get_current_user_id() );
		if ( $test ) {
			delete_transient( self::NOTICE_TRANSIENT . get_current_user_id() );
		}
		?>
		<div class="wrap">
			<h1>Dịch tự động (Google Cloud Translation)</h1>

			<?php if ( is_array( $test ) ) : ?>
				<?php foreach ( $test as $row ) : ?>
					<div class="notice <?php echo $row['ok'] ? 'notice-success' : 'notice-error'; ?>"><p><?php echo esc_html( ( $row['ok'] ? 'Kết nối thành công – ' : 'Lỗi – ' ) . $row['msg'] ); ?></p></div>
				<?php endforeach; ?>
			<?php endif; ?>

			<h2>Hạn mức tháng <?php echo esc_html( QPAT_Usage::period() ); ?></h2>
			<table class="widefat striped" style="max-width:640px">
				<tbody>
					<?php foreach ( array( 'en', 'zh' ) as $lang ) : ?>
						<tr>
							<td><?php echo esc_html( 'Tiếng Việt → ' . QPAT_Settings::LANGUAGES[ $lang ] ); ?></td>
							<td style="text-align:right"><?php echo esc_html( number_format_i18n( (int) ( $by_lang[ $lang ] ?? 0 ) ) ); ?> ký tự</td>
						</tr>
					<?php endforeach; ?>
					<tr>
						<td><strong>Tổng đã dùng</strong></td>
						<td style="text-align:right">
							<strong><?php echo esc_html( number_format_i18n( $st['used'] ) ); ?></strong>
							<?php if ( $st['limit'] > 0 ) : ?>
								/ <?php echo esc_html( number_format_i18n( $st['limit'] ) ); ?> (<?php echo esc_html( (string) $st['percent'] ); ?>%)
							<?php else : ?>
								(không giới hạn)
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>
			<?php if ( $st['limit'] > 0 ) : ?>
				<?php $color = 'paused' === $st['state'] ? '#d63638' : ( 'warn' === $st['state'] ? '#dba617' : '#00a32a' ); ?>
				<div style="max-width:640px;height:10px;background:#dcdcde;border-radius:5px;margin:8px 0 4px;overflow:hidden">
					<div style="height:100%;width:<?php echo esc_attr( (string) min( 100, $st['percent'] ) ); ?>%;background:<?php echo esc_attr( $color ); ?>"></div>
				</div>
				<p class="description">Số ký tự được tính riêng cho từng ngôn ngữ đích (4.000 ký tự tiếng Việt dịch sang 2 ngôn ngữ ≈ 8.000 ký tự). Mức sử dụng là ước tính phía website; số liệu tính phí chính thức xem trong Google Cloud Console.</p>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'qpat' ); ?>
				<h2>Cấu hình</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="qpat_api_key">Google Cloud API key</label></th>
						<td>
							<?php if ( QPAT_Settings::api_key_from_constant() ) : ?>
								<p><code>QPAT_GOOGLE_API_KEY</code> đang được khai báo trong <code>wp-config.php</code> — key này được dùng.</p>
							<?php else : ?>
								<input type="password" class="regular-text" id="qpat_api_key" name="<?php echo esc_attr( $name ); ?>[api_key]" value="<?php echo esc_attr( $masked ); ?>" autocomplete="off">
								<?php if ( '' !== $stored ) : ?>
									<label style="margin-left:8px"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[clear_api_key]" value="1"> Xoá key</label>
								<?php endif; ?>
								<p class="description">Bật <strong>Cloud Translation API</strong> trong Google Cloud Console rồi tạo API key (nên giới hạn key chỉ dùng cho Cloud Translation API). Để nguyên giá trị đã che để giữ key hiện tại.</p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row">Ngôn ngữ gốc</th>
						<td><strong>Tiếng Việt (vi)</strong> — dịch một chiều sang các ngôn ngữ đích.</td>
					</tr>
					<tr>
						<th scope="row">Ngôn ngữ đích</th>
						<td>
							<?php foreach ( array( 'en', 'zh' ) as $lang ) : ?>
								<label style="margin-right:16px"><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[target_langs][]" value="<?php echo esc_attr( $lang ); ?>" <?php checked( in_array( $lang, $s['target_langs'], true ) ); ?>> <?php echo esc_html( QPAT_Settings::LANGUAGES[ $lang ] ); ?></label>
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
						<td><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[auto_on_publish]" value="1" <?php checked( ! empty( $s['auto_on_publish'] ) ); ?>> Tự động tạo bản dịch khi đăng bài tiếng Việt</label>
							<p class="description">Lưu nháp không gọi dịch. Xem bài ngoài website không gọi dịch.</p></td>
					</tr>
					<tr>
						<th scope="row"><label for="qpat_limit">Giới hạn ký tự / tháng</label></th>
						<td>
							<input type="number" min="0" step="1000" id="qpat_limit" name="<?php echo esc_attr( $name ); ?>[monthly_limit]" value="<?php echo esc_attr( (string) $s['monthly_limit'] ); ?>" class="regular-text">
							<p class="description">Mặc định 500.000 (mức miễn phí của Google). Khi đạt giới hạn, hệ thống tạm dừng dịch. Chỉ tăng khi đã được xác nhận chi phí. 0 = không giới hạn (không khuyến nghị).</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="qpat_warn">Cảnh báo khi đạt</label></th>
						<td><input type="number" min="1" max="100" id="qpat_warn" name="<?php echo esc_attr( $name ); ?>[warn_percent]" value="<?php echo esc_attr( (string) $s['warn_percent'] ); ?>" class="small-text"> % hạn mức</td>
					</tr>
				</table>
				<?php submit_button( 'Lưu cấu hình' ); ?>
			</form>

			<h2>Kiểm tra kết nối</h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::TEST_ACTION ); ?>">
				<?php wp_nonce_field( self::TEST_ACTION ); ?>
				<p>Dịch thử câu “Xin chào, đây là QP Holdings.” sang các ngôn ngữ đích (tốn khoảng 30 ký tự mỗi ngôn ngữ).</p>
				<?php submit_button( 'Kiểm tra kết nối Google', 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}
}
