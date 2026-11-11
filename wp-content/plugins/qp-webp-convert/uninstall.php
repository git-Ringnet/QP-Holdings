<?php
/**
 * Gỡ plugin: chỉ xóa cài đặt/nhật ký. KHÔNG xóa ảnh WebP (dùng nút "Xóa toàn bộ file WebP" trước khi gỡ nếu muốn).
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'qpwc_settings' );
delete_option( 'qpwc_log' );
delete_option( 'qpwc_bulk' );
