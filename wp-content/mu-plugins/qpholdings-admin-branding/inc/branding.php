<?php
/**
 * Login screen + admin bar + footer branding for QP Holdings.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue the branded login stylesheet and inject the dynamic asset URLs.
 */
function qph_admin_branding_login_assets(): void {
	wp_enqueue_style(
		'qph-login',
		qph_admin_branding_asset_url( 'login.css' ),
		array(),
		qph_admin_branding_asset_version( 'login.css' )
	);

	// Use the same logo as the site header (white text reads well on the dark login bg).
	$logo_url = get_template_directory_uri() . '/assets/site/logo-header-figma-2305-12808-logoheader.svg';
	$bg_url   = qph_admin_branding_asset_url( 'login-bg.webp' );

	$inline = sprintf(
		':root{--qph-login-logo:url(%1$s);--qph-login-bg:url(%2$s);}',
		wp_json_encode( $logo_url ),
		wp_json_encode( $bg_url )
	);

	wp_add_inline_style( 'qph-login', $inline );
}
add_action( 'login_enqueue_scripts', 'qph_admin_branding_login_assets' );

/**
 * Point the login logo to the public website, not wordpress.org.
 */
function qph_admin_branding_login_logo_url(): string {
	return home_url( '/' );
}
add_filter( 'login_headerurl', 'qph_admin_branding_login_logo_url' );

/**
 * Use the website name as login logo text.
 */
function qph_admin_branding_login_logo_text(): string {
	return get_bloginfo( 'name' ) ?: 'QP Holdings';
}
add_filter( 'login_headertext', 'qph_admin_branding_login_logo_text' );

/**
 * Replace the WordPress admin-bar mark with the website icon.
 */
function qph_admin_branding_admin_bar_logo(): void {
	if ( ! is_admin_bar_showing() ) {
		return;
	}

	$icon_url = qph_admin_branding_asset_url( 'admin-bar-icon.png' );
	?>
	<style>
		#wpadminbar #wp-admin-bar-wp-logo > .ab-item {
			width: 42px;
		}

		#wpadminbar #wp-admin-bar-wp-logo > .ab-item .ab-icon {
			width: 22px;
			height: 22px;
			margin: 5px 0 0 0;
			background-image: url('<?php echo esc_url( $icon_url ); ?>') !important;
			background-position: center !important;
			background-repeat: no-repeat !important;
			background-size: contain !important;
		}

		#wpadminbar #wp-admin-bar-wp-logo > .ab-item .ab-icon:before {
			content: '' !important;
		}
	</style>
	<?php
}
add_action( 'admin_head', 'qph_admin_branding_admin_bar_logo' );
add_action( 'wp_head', 'qph_admin_branding_admin_bar_logo' );

/**
 * Make the admin-bar logo link back to the website and remove WordPress.org submenu links.
 */
function qph_admin_branding_admin_bar_menu( WP_Admin_Bar $wp_admin_bar ): void {
	$wp_admin_bar->remove_node( 'about' );
	$wp_admin_bar->remove_node( 'wporg' );
	$wp_admin_bar->remove_node( 'documentation' );
	$wp_admin_bar->remove_node( 'support-forums' );
	$wp_admin_bar->remove_node( 'feedback' );

	$wp_admin_bar->add_node(
		array(
			'id'    => 'wp-logo',
			'title' => '<span class="ab-icon" aria-hidden="true"></span><span class="screen-reader-text">QP Holdings</span>',
			'href'  => home_url( '/' ),
			'meta'  => array(
				'title' => 'QP Holdings',
			),
		)
	);
}
add_action( 'admin_bar_menu', 'qph_admin_branding_admin_bar_menu', 11 );

/**
 * Hide the "Excerpt" (Tóm tắt) meta box on every post type's edit screen.
 *
 * Runs late (priority 20) so it removes the box even after CPTs that declare
 * `excerpt` support register their own metabox.
 */
function qph_admin_branding_remove_excerpt_metabox(): void {
	foreach ( get_post_types( array(), 'names' ) as $post_type ) {
		remove_meta_box( 'postexcerpt', $post_type, 'normal' );
	}
}
add_action( 'add_meta_boxes', 'qph_admin_branding_remove_excerpt_metabox', 20 );

/**
 * Replace the generic WordPress footer text in wp-admin.
 */
function qph_admin_branding_admin_footer_text(): string {
	return 'QP Holdings Admin';
}
add_filter( 'admin_footer_text', 'qph_admin_branding_admin_footer_text' );

/**
 * Hide the WordPress version prompt from the admin footer.
 */
function qph_admin_branding_admin_footer_version(): string {
	return '';
}
add_filter( 'update_footer', 'qph_admin_branding_admin_footer_version', 20 );
