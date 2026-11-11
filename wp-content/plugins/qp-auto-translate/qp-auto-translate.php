<?php
/**
 * Plugin Name: QP Auto Translate
 * Plugin URI:  https://qpholdings.vn
 * Description: Tự động dịch bài viết tiếng Việt sang tiếng Anh và tiếng Trung bằng Google Cloud Translation (NMT), có kiểm soát hạn mức ký tự hằng tháng.
 * Version:     0.1.0
 * Author:      Ringnet
 * Text Domain: qp-auto-translate
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'QPAT_VERSION', '0.1.0' );
define( 'QPAT_FILE', __FILE__ );
define( 'QPAT_DIR', plugin_dir_path( __FILE__ ) );
define( 'QPAT_URL', plugin_dir_url( __FILE__ ) );

require_once QPAT_DIR . 'includes/class-qpat-settings.php';
require_once QPAT_DIR . 'includes/class-qpat-usage.php';
require_once QPAT_DIR . 'includes/class-qpat-google-client.php';
require_once QPAT_DIR . 'includes/class-qpat-admin.php';

register_activation_hook( __FILE__, array( 'QPAT_Usage', 'install' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		QPAT_Usage::maybe_upgrade();
		if ( is_admin() ) {
			QPAT_Admin::instance()->boot();
		}
	}
);
