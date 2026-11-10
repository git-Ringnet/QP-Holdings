<?php
/**
 * Plugin Name: QP Holdings Admin Branding
 * Description: Brands the WordPress login screen, admin bar, sidebar and provides a custom full-page dashboard with charts for QP Holdings.
 * Author: QP Holdings
 * Version: 2.0.0
 */

defined( 'ABSPATH' ) || exit;

define( 'QPH_ADMIN_DIR', WPMU_PLUGIN_DIR . '/qpholdings-admin-branding' );
define( 'QPH_ADMIN_URL', WPMU_PLUGIN_URL . '/qpholdings-admin-branding' );

/**
 * Return a cache-busted URL for a plugin asset.
 */
function qph_admin_branding_asset_url( string $filename ): string {
	$relative_path = 'assets/' . ltrim( $filename, '/' );
	$file_path     = QPH_ADMIN_DIR . '/' . $relative_path;
	$url           = QPH_ADMIN_URL . '/' . $relative_path;

	if ( file_exists( $file_path ) ) {
		$url = add_query_arg( 'v', (string) filemtime( $file_path ), $url );
	}

	return $url;
}

/**
 * Return the filemtime of an asset for use as an enqueue version.
 */
function qph_admin_branding_asset_version( string $filename ): string {
	$file_path = QPH_ADMIN_DIR . '/assets/' . ltrim( $filename, '/' );

	return file_exists( $file_path ) ? (string) filemtime( $file_path ) : '2.0.0';
}

foreach ( array( 'branding.php', 'menu.php', 'dashboard.php' ) as $qph_admin_module ) {
	$qph_admin_module_path = QPH_ADMIN_DIR . '/inc/' . $qph_admin_module;
	if ( file_exists( $qph_admin_module_path ) ) {
		require_once $qph_admin_module_path;
	}
}
