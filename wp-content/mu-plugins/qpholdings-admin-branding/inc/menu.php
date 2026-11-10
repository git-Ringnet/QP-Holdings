<?php
/**
 * Admin sidebar simplification, Vietnamese labels and custom ordering.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Keep the sidebar focused on the content areas used by QP Holdings editors.
 */
function qph_admin_branding_simplify_admin_menu(): void {
	remove_menu_page( 'edit.php' );
	remove_menu_page( 'edit-comments.php' );
	remove_menu_page( 'wpcf7' );
	remove_menu_page( 'flamingo' );
	remove_menu_page( 'admin.php?page=wpcf7' );
	remove_menu_page( 'admin.php?page=flamingo' );
}
add_action( 'admin_menu', 'qph_admin_branding_simplify_admin_menu', 999 );

/**
 * Use Vietnamese business labels for the main admin areas.
 *
 * The native dashboard (index.php) redirects to the custom overview page, so
 * hide its menu entry to avoid a duplicate "Tổng quan".
 */
function qph_admin_branding_relabel_admin_menu(): void {
	global $menu, $submenu;

	remove_menu_page( 'index.php' );

	$labels = array(
		'edit.php?post_type=page'            => 'Trang nội dung',
		'upload.php'                         => 'Thư viện media',
		'themes.php'                         => 'Giao diện',
		'plugins.php'                        => 'Plugin',
		'users.php'                          => 'Tài khoản',
		'tools.php'                          => 'Công cụ',
		'options-general.php'                => 'Cấu hình',
		'edit.php?post_type=acf-field-group' => 'Trường dữ liệu',
		'rank-math-registration'             => 'SEO',
		'admin.php?page=rank-math-registration' => 'SEO',
		'mlang'                              => 'Ngôn ngữ',
		'admin.php?page=mlang'               => 'Ngôn ngữ',
	);

	foreach ( $menu as $index => $item ) {
		$slug = $item[2] ?? '';
		if ( isset( $labels[ $slug ] ) ) {
			$menu[ $index ][0] = $labels[ $slug ];
		}
	}

	if ( isset( $submenu['edit.php?post_type=page'][5][0] ) ) {
		$submenu['edit.php?post_type=page'][5][0] = 'Tất cả trang';
	}
	if ( isset( $submenu['edit.php?post_type=page'][10][0] ) ) {
		$submenu['edit.php?post_type=page'][10][0] = 'Thêm trang mới';
	}
}
add_action( 'admin_menu', 'qph_admin_branding_relabel_admin_menu', 1000 );

/**
 * Enable explicit admin menu ordering.
 */
function qph_admin_branding_use_custom_menu_order(): bool {
	return true;
}
add_filter( 'custom_menu_order', 'qph_admin_branding_use_custom_menu_order' );

/**
 * Put editorial workflows first, technical settings second.
 */
function qph_admin_branding_admin_menu_order( array $menu_order ): array {
	return array(
		'qph-overview',
		'separator1',
		'edit.php?post_type=page',
		'edit.php?post_type=qp_news',
		'edit.php?post_type=qp_project',
		'edit.php?post_type=qp_job',
		'edit.php?post_type=qp_investor_doc',
		'edit.php?post_type=qp_board_member',
		'edit.php?post_type=qp_gallery_item',
		'edit.php?post_type=qp_partner',
		'edit.php?post_type=qp_faq',
		'edit.php?post_type=qp_contact_msg',
		'edit.php?post_type=qp_consult_msg',
		'upload.php',
		'separator2',
		'users.php',
		'mlang',
		'rank-math-registration',
		'themes.php',
		'plugins.php',
		'edit.php?post_type=acf-field-group',
		'tools.php',
		'options-general.php',
	);
}
add_filter( 'menu_order', 'qph_admin_branding_admin_menu_order' );
