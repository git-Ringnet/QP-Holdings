<?php
/**
 * Plugin Name: QP Auto Translate
 * Plugin URI:  https://qpholdings.vn
 * Description: Tự động dịch Trang / Bài viết tiếng Việt sang tiếng Anh và tiếng Trung khi đăng / cập nhật, chỉ dịch phần mới hoặc đã sửa, giữ phần đã sửa tay. Công cụ dịch: Google Gemini (mặc định) hoặc Google Cloud Translation NMT (có hạn mức ký tự hằng tháng).
 * Version:     0.3.0
 * Author:      Ringnet
 * Text Domain: qp-auto-translate
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'QPAT_VERSION', '0.3.0' );
define( 'QPAT_FILE', __FILE__ );
define( 'QPAT_DIR', plugin_dir_path( __FILE__ ) );
define( 'QPAT_URL', plugin_dir_url( __FILE__ ) );

require_once QPAT_DIR . 'includes/class-qpat-settings.php';
require_once QPAT_DIR . 'includes/class-qpat-usage.php';
require_once QPAT_DIR . 'includes/class-qpat-translator.php';
require_once QPAT_DIR . 'includes/class-qpat-glossary.php';
require_once QPAT_DIR . 'includes/class-qpat-google-client.php';
require_once QPAT_DIR . 'includes/class-qpat-gemini-client.php';
require_once QPAT_DIR . 'includes/class-qpat-segments.php';
require_once QPAT_DIR . 'includes/class-qpat-sync.php';
require_once QPAT_DIR . 'includes/class-qpat-updater.php';
require_once QPAT_DIR . 'includes/class-qpat-queue.php';
require_once QPAT_DIR . 'includes/class-qpat-status.php';
require_once QPAT_DIR . 'includes/class-qpat-editor.php';
require_once QPAT_DIR . 'includes/class-qpat-admin.php';

register_activation_hook(
	__FILE__,
	static function (): void {
		QPAT_Usage::install();
		QPAT_Queue::install();
		QPAT_Settings::maybe_import_legacy();
		QPAT_Settings::worker_token();
	}
);
register_deactivation_hook( __FILE__, array( 'QPAT_Queue', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		QPAT_Usage::maybe_upgrade();
		QPAT_Queue::maybe_upgrade();
		QPAT_Settings::maybe_import_legacy();

		// Queue + publish hook run everywhere (scheduled posts publish from cron).
		QPAT_Queue::instance()->boot();
		QPAT_Editor::instance()->boot();
		if ( is_admin() ) {
			QPAT_Admin::instance()->boot();
		}
	}
);
