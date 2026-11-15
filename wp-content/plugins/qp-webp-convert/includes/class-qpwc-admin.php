<?php
/**
 * Trang quản trị: Media → Chuyển WebP.
 */

defined( 'ABSPATH' ) || exit;

final class QPWC_Admin {

	const PAGE = 'qp-webp-convert';

	private static ?QPWC_Admin $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_post_qpwc_cleanup', array( $this, 'handle_cleanup' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( QPWC_FILE ), array( $this, 'action_links' ) );
	}

	public function menu(): void {
		add_media_page( 'Chuyển ảnh sang WebP', 'Chuyển WebP', 'manage_options', self::PAGE, array( $this, 'render' ) );
	}

	public function register(): void {
		register_setting(
			'qpwc',
			QPWC_Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'QPWC_Settings', 'sanitize' ),
				'default'           => QPWC_Settings::defaults(),
			)
		);
	}

	public function action_links( array $links ): array {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'upload.php?page=' . self::PAGE ) ) . '">Cài đặt</a>' );
		return $links;
	}

	public function assets( string $hook ): void {
		if ( 'media_page_' . self::PAGE !== $hook ) {
			return;
		}
		$js = QPWC_DIR . 'assets/admin.js';
		wp_enqueue_script( 'qpwc-admin', QPWC_URL . 'assets/admin.js', array(), (string) filemtime( $js ), true );
		wp_localize_script(
			'qpwc-admin',
			'QPWC',
			array(
				'ajax'  => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'qpwc_bulk' ),
			)
		);
	}

	public function handle_cleanup(): void {
		check_admin_referer( 'qpwc_cleanup' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Không đủ quyền.' );
		}
		$n = QPWC_Bulk::cleanup();
		wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE, 'qpwc_cleaned' => $n ), admin_url( 'upload.php' ) ) );
		exit;
	}

	private static function badge( string $level ): string {
		$map = array(
			'ok'   => array( '#1a7f37', 'Đạt' ),
			'warn' => array( '#9a6700', 'Xem lại' ),
			'fail' => array( '#cf222e', 'Lỗi' ),
			'info' => array( '#57606a', 'Thông tin' ),
		);
		$m   = $map[ $level ] ?? $map['info'];
		return '<span style="color:' . $m[0] . ';font-weight:600">' . esc_html( $m[1] ) . '</span>';
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s      = QPWC_Settings::all();
		$state  = QPWC_Bulk::state();
		$report = QPWC_Bulk::report();
		$rows   = QPWC_Diagnostics::collect();
		$engine = QPWC_Converter::engine();
		$size   = null;
		if ( isset( $_POST['qpwc_measure'] ) && check_admin_referer( 'qpwc_measure' ) ) { // phpcs:ignore
			$size = QPWC_Diagnostics::uploads_size();
		}
		?>
		<div class="wrap" id="qpwc-wrap">
			<h1>Chuyển ảnh sang WebP</h1>

			<?php if ( isset( $_GET['qpwc_cleaned'] ) ) : // phpcs:ignore ?>
				<div class="notice notice-success"><p>Đã xóa <?php echo (int) $_GET['qpwc_cleaned']; // phpcs:ignore ?> file WebP. Website dùng lại ảnh gốc.</p></div>
			<?php endif; ?>
			<?php if ( 'none' === $engine ) : ?>
				<div class="notice notice-error"><p><strong>Máy chủ chưa hỗ trợ tạo WebP</strong> (thiếu GD/Imagick có WebP). Liên hệ nhà hosting. Plugin sẽ không chuyển đổi cho tới khi có.</p></div>
			<?php endif; ?>

			<h2>1. Cài đặt</h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'qpwc' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Bật chuyển đổi WebP</th>
						<td>
							<label><input type="checkbox" id="qpwc-enabled" name="<?php echo esc_attr( QPWC_Settings::OPTION ); ?>[enabled]" value="1" <?php checked( $s['enabled'] ); ?>>
							Tự chuyển khi tải ảnh lên và phát WebP ra website.</label>
							<p class="description">Tắt đi: website quay về dùng ảnh gốc ngay lập tức, không mất ảnh nào.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="qpwc-quality">Chất lượng WebP (1–100)</label></th>
						<td><input type="number" id="qpwc-quality" min="1" max="100" name="<?php echo esc_attr( QPWC_Settings::OPTION ); ?>[quality]" value="<?php echo (int) $s['quality']; ?>" class="small-text">
						<p class="description">Mặc định 80: cân bằng độ nét và dung lượng. Nếu file WebP lớn hơn ảnh gốc, plugin giữ ảnh gốc.</p></td>
					</tr>
				</table>
				<?php submit_button( 'Lưu cài đặt' ); ?>
			</form>

			<h2>2. Chẩn đoán hosting (phần A của bảng nghiệm thu)</h2>
			<table class="widefat striped" id="qpwc-diag" style="max-width:980px">
				<thead><tr><th>Mã</th><th>Hạng mục</th><th>Giá trị</th><th>Đánh giá</th></tr></thead>
				<tbody>
				<?php foreach ( $rows as $r ) : ?>
					<tr><td><?php echo esc_html( $r['code'] ); ?></td><td><?php echo esc_html( $r['label'] ); ?></td><td><?php echo esc_html( $r['value'] ); ?></td><td><?php echo self::badge( $r['level'] ); // phpcs:ignore ?></td></tr>
				<?php endforeach; ?>
				<?php if ( $size ) : ?>
					<tr id="qpwc-size-row"><td>WP-03</td><td>Dung lượng thư mục uploads</td>
						<td><?php echo esc_html( size_format( (float) $size['bytes'], 2 ) . ' / ' . number_format_i18n( $size['files'] ) . ' file; trong đó file WebP do plugin tạo: ' . size_format( (float) $size['webp'], 2 ) . ( $size['complete'] ? '' : ' (chưa đo hết, thư mục quá lớn)' ) ); ?></td><td><?php echo self::badge( 'info' ); // phpcs:ignore ?></td></tr>
				<?php endif; ?>
				</tbody>
			</table>
			<form method="post" style="margin:10px 0">
				<?php wp_nonce_field( 'qpwc_measure' ); ?>
				<button class="button" name="qpwc_measure" value="1" id="qpwc-measure">Đo dung lượng uploads</button>
			</form>
			<p><strong>Văn bản để dán vào cột Ghi chú:</strong></p>
			<textarea readonly rows="8" style="width:100%;max-width:980px;font-family:monospace" id="qpwc-diag-text"><?php echo esc_textarea( QPWC_Diagnostics::as_text( $rows ) ); ?></textarea>

			<h2>3. Chuyển đổi hàng loạt ảnh đang có</h2>
			<p>Chạy theo lô nhỏ, có thể dừng và chạy tiếp. Ảnh đã xong không bị xử lý lại. <strong>Giữ nguyên ảnh gốc.</strong></p>
			<p>
				<label><input type="checkbox" id="qpwc-backup" <?php checked( (bool) $s['backup_at'] ); ?>>
				Tôi đã sao lưu thư mục ảnh và database
				<span id="qpwc-backup-at"><?php echo $s['backup_at'] ? esc_html( '(lúc ' . wp_date( 'd/m/Y H:i', (int) $s['backup_at'] ) . ')' ) : ''; ?></span></label>
			</p>
			<p>
				<button class="button button-primary" id="qpwc-start">Bắt đầu chạy hàng loạt</button>
				<button class="button" id="qpwc-pause">Tạm dừng</button>
				<button class="button" id="qpwc-resume">Chạy tiếp</button>
				<button class="button" id="qpwc-reset">Đặt lại tiến độ</button>
				<span id="qpwc-msg" style="margin-left:10px;color:#cf222e"></span>
			</p>
			<p id="qpwc-state" data-status="<?php echo esc_attr( $state['status'] ); ?>">Trạng thái: <strong id="qpwc-status"><?php echo esc_html( $state['status'] ); ?></strong>
				— đã xử lý <span id="qpwc-processed"><?php echo (int) $state['processed']; ?></span> ảnh</p>

			<table class="widefat striped" id="qpwc-report" style="max-width:640px">
				<tbody>
					<tr><th>Tổng số ảnh trong thư viện</th><td id="r-total"><?php echo (int) $report['total']; ?></td></tr>
					<tr><th>Thành công (đã có WebP)</th><td id="r-done"><?php echo (int) $report['done']; ?></td></tr>
					<tr><th>Bỏ qua (GIF/SVG hoặc WebP không nhỏ hơn)</th><td id="r-skipped"><?php echo (int) $report['skipped']; ?></td></tr>
					<tr><th>Lỗi</th><td id="r-failed"><?php echo (int) $report['failed']; ?></td></tr>
					<tr><th>Chưa xử lý</th><td id="r-pending"><?php echo (int) $report['pending']; ?></td></tr>
					<tr><th>Dung lượng ảnh gốc đã chuyển (ảnh gốc + các cỡ)</th><td id="r-src" data-bytes="<?php echo (int) $report['src_bytes']; ?>"><?php echo esc_html( size_format( (float) $report['src_bytes'], 2 ) ); ?></td></tr>
					<tr><th>Dung lượng sau khi chuyển WebP</th><td id="r-webp" data-bytes="<?php echo (int) $report['webp_bytes']; ?>"><?php echo esc_html( size_format( (float) $report['webp_bytes'], 2 ) ); ?></td></tr>
					<tr><th>Mức giảm</th><td id="r-saving"><?php echo $report['src_bytes'] ? esc_html( round( 100 - 100 * $report['webp_bytes'] / $report['src_bytes'], 1 ) . '%' ) : '—'; ?></td></tr>
				</tbody>
			</table>

			<?php $failed = QPWC_Bulk::failed_list(); ?>
			<h3>Danh sách ảnh lỗi</h3>
			<table class="widefat striped" id="qpwc-failed" style="max-width:840px">
				<thead><tr><th>ID</th><th>Tên</th><th>Lý do</th></tr></thead>
				<tbody>
				<?php if ( empty( $failed ) ) : ?>
					<tr><td colspan="3">Không có ảnh lỗi.</td></tr>
				<?php else : ?>
					<?php foreach ( $failed as $f ) : ?>
						<tr><td><a href="<?php echo esc_url( get_edit_post_link( (int) $f['ID'] ) ); ?>"><?php echo (int) $f['ID']; ?></a></td><td><?php echo esc_html( $f['post_title'] ); ?></td><td><?php echo esc_html( (string) $f['reason'] ); ?></td></tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>

			<h2>4. Khôi phục / dọn dẹp</h2>
			<p>Xóa toàn bộ file <code>.jpg.webp</code>, <code>.png.webp</code> do plugin tạo. Ảnh gốc không bị đụng tới.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Xóa toàn bộ file WebP do plugin tạo? Ảnh gốc không bị ảnh hưởng.');">
				<input type="hidden" name="action" value="qpwc_cleanup">
				<?php wp_nonce_field( 'qpwc_cleanup' ); ?>
				<button class="button" id="qpwc-cleanup">Xóa toàn bộ file WebP</button>
			</form>

			<h2>5. Nhật ký lỗi</h2>
			<table class="widefat striped" id="qpwc-log" style="max-width:840px">
				<thead><tr><th>Thời gian</th><th>Ảnh</th><th>Nội dung</th></tr></thead>
				<tbody>
				<?php $log = array_slice( QPWC_Settings::get_log(), 0, 30 ); ?>
				<?php if ( empty( $log ) ) : ?>
					<tr><td colspan="3">Chưa có lỗi nào.</td></tr>
				<?php else : ?>
					<?php foreach ( $log as $l ) : ?>
						<tr><td><?php echo esc_html( wp_date( 'd/m/Y H:i:s', (int) $l['time'] ) ); ?></td><td><?php echo $l['id'] ? '#' . (int) $l['id'] : '—'; ?></td><td><?php echo esc_html( $l['msg'] ); ?></td></tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
