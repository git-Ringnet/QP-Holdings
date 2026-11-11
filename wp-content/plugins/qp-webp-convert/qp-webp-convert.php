<?php
/**
 * Plugin Name: QP WebP Convert
 * Plugin URI:  https://qpholdings.vn
 * Description: Tự động chuyển ảnh JPG/PNG sang WebP khi tải lên, chuyển hàng loạt ảnh cũ, phát WebP ra website bằng thẻ picture. Giữ nguyên ảnh gốc.
 * Version:     1.0.0
 * Author:      Ringnet
 * Text Domain: qp-webp-convert
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'QPWC_VERSION', '1.0.0' );
define( 'QPWC_FILE', __FILE__ );
define( 'QPWC_DIR', plugin_dir_path( __FILE__ ) );
define( 'QPWC_URL', plugin_dir_url( __FILE__ ) );

require_once QPWC_DIR . 'includes/class-qpwc-converter.php';
require_once QPWC_DIR . 'includes/class-qpwc-settings.php';
require_once QPWC_DIR . 'includes/class-qpwc-media.php';
require_once QPWC_DIR . 'includes/class-qpwc-frontend.php';
require_once QPWC_DIR . 'includes/class-qpwc-bulk.php';
require_once QPWC_DIR . 'includes/class-qpwc-diagnostics.php';
require_once QPWC_DIR . 'includes/class-qpwc-admin.php';

add_action(
	'plugins_loaded',
	static function (): void {
		QPWC_Media::init();
		QPWC_Frontend::init();
		QPWC_Bulk::init();
		if ( is_admin() ) {
			QPWC_Admin::instance()->boot();
		}
	}
);
