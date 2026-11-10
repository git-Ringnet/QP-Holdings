<?php
/**
 * Dynamic content orchestrator.
 *
 * Registers CPTs, taxonomies, default taxonomy terms, and includes
 * the per-CPT modules (helpers, ACF fields, renderers, seeders).
 *
 * File layout:
 *   inc/dynamic-content.php   (this file)
 *   inc/dynamic-helpers.php
 *   inc/dynamic-acf.php
 *   inc/dynamic-news.php
 *   inc/dynamic-projects.php
 *   inc/dynamic-jobs.php
 *   inc/dynamic-investor.php
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the 4 dynamic CPTs.
 */
function qp_holdings_register_cpts(): void {
	register_post_type(
		'qp_news',
		array(
			'label'        => 'Tin tức',
			'labels'       => array(
				'name'          => 'Tin tức',
				'singular_name' => 'Bài viết',
				'add_new_item'  => 'Thêm bài viết mới',
				'edit_item'     => 'Chỉnh sửa bài viết',
				'new_item'      => 'Bài viết mới',
				'view_item'     => 'Xem bài viết',
				'search_items'  => 'Tìm bài viết',
				'menu_name'     => 'Tin tức',
			),
			'public'       => true,
			'show_ui'      => true,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-megaphone',
			'menu_position' => 22,
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
			'has_archive'  => false,
			'rewrite'      => array( 'slug' => 'tin-tuc', 'with_front' => false ),
		)
	);

	register_post_type(
		'qp_project',
		array(
			'label'        => 'Dự án',
			'labels'       => array(
				'name'          => 'Dự án',
				'singular_name' => 'Dự án',
				'add_new_item'  => 'Thêm dự án mới',
				'edit_item'     => 'Chỉnh sửa dự án',
				'view_item'     => 'Xem dự án',
				'menu_name'     => 'Dự án',
			),
			'public'        => true,
			'show_ui'       => true,
			'show_in_rest'  => true,
			'menu_icon'     => 'dashicons-building',
			'menu_position' => 23,
			'supports'      => array( 'title', 'thumbnail', 'excerpt', 'custom-fields' ),
			'has_archive'   => false,
			'rewrite'       => array( 'slug' => 'du-an-cpt', 'with_front' => false ),
		)
	);

	register_post_type(
		'qp_job',
		array(
			'label'         => 'Tuyển dụng',
			'labels'        => array(
				'name'          => 'Tuyển dụng',
				'singular_name' => 'Vị trí',
				'add_new_item'  => 'Thêm vị trí mới',
				'edit_item'     => 'Chỉnh sửa vị trí',
				'view_item'     => 'Xem vị trí',
				'menu_name'     => 'Tuyển dụng',
			),
			'public'        => true,
			'show_ui'       => true,
			'show_in_rest'  => true,
			'menu_icon'     => 'dashicons-businessperson',
			'menu_position' => 24,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
			'has_archive'   => false,
			'rewrite'       => array( 'slug' => 'tuyen-dung-cpt', 'with_front' => false ),
		)
	);

	register_post_type(
		'qp_investor_doc',
		array(
			'label'         => 'Tài liệu nhà đầu tư',
			'labels'        => array(
				'name'          => 'Tài liệu nhà đầu tư',
				'singular_name' => 'Tài liệu',
				'add_new_item'  => 'Thêm tài liệu mới',
				'edit_item'     => 'Chỉnh sửa tài liệu',
				'view_item'     => 'Xem tài liệu',
				'menu_name'     => 'Tài liệu nhà đầu tư',
			),
			'public'        => true,
			'show_ui'       => true,
			'show_in_rest'  => true,
			'menu_icon'     => 'dashicons-portfolio',
			'menu_position' => 26,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
			'has_archive'   => false,
			'rewrite'       => array( 'slug' => 'tai-lieu-nha-dau-tu', 'with_front' => false ),
		)
	);

	register_post_type(
		'qp_board_member',
		array(
			'label'         => 'Hội đồng quản trị',
			'labels'        => array(
				'name'          => 'Hội đồng quản trị',
				'singular_name' => 'Thành viên',
				'add_new'       => 'Thêm thành viên',
				'add_new_item'  => 'Thêm thành viên mới',
				'edit_item'     => 'Chỉnh sửa thành viên',
				'new_item'      => 'Thành viên mới',
				'view_item'     => 'Xem thành viên',
				'search_items'  => 'Tìm thành viên',
				'menu_name'     => 'Hội đồng quản trị',
				'featured_image' => 'Ảnh chân dung',
				'set_featured_image' => 'Chọn ảnh chân dung',
				'use_featured_image' => 'Dùng làm ảnh chân dung',
			),
			'public'        => false,
			'show_ui'       => true,
			'show_in_rest'  => false,
			'menu_icon'     => 'dashicons-groups',
			'menu_position' => 25,
			'supports'      => array( 'title', 'thumbnail', 'page-attributes' ),
			'has_archive'   => false,
			'rewrite'       => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
		)
	);

	register_post_type(
		'qp_gallery_item',
		array(
			'label'         => 'Thư viện ảnh',
			'labels'        => array(
				'name'          => 'Thư viện ảnh',
				'singular_name' => 'Ảnh / Video',
				'add_new'       => 'Thêm ảnh / video',
				'add_new_item'  => 'Thêm ảnh / video mới',
				'edit_item'     => 'Chỉnh sửa ảnh / video',
				'new_item'      => 'Ảnh / Video mới',
				'view_item'     => 'Xem',
				'search_items'  => 'Tìm trong thư viện',
				'menu_name'     => 'Thư viện ảnh & video',
				'featured_image' => 'Ảnh hiển thị',
				'set_featured_image' => 'Chọn ảnh hiển thị',
				'use_featured_image' => 'Dùng làm ảnh hiển thị',
			),
			'public'        => false,
			'show_ui'       => true,
			'show_in_rest'  => false,
			'menu_icon'     => 'dashicons-format-gallery',
			'menu_position' => 27,
			'supports'      => array( 'title', 'thumbnail', 'page-attributes' ),
			'has_archive'   => false,
			'rewrite'       => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
		)
	);

	register_post_type(
		'qp_partner',
		array(
			'label'         => 'Đối tác',
			'labels'        => array(
				'name'          => 'Đối tác',
				'singular_name' => 'Đối tác',
				'add_new'       => 'Thêm đối tác',
				'add_new_item'  => 'Thêm đối tác mới',
				'edit_item'     => 'Chỉnh sửa đối tác',
				'new_item'      => 'Đối tác mới',
				'view_item'     => 'Xem đối tác',
				'search_items'  => 'Tìm đối tác',
				'menu_name'     => 'Đối tác',
				'featured_image' => 'Logo đối tác',
				'set_featured_image' => 'Chọn logo',
				'use_featured_image' => 'Dùng làm logo',
			),
			'public'        => false,
			'show_ui'       => true,
			'show_in_rest'  => false,
			'menu_icon'     => 'dashicons-awards',
			'menu_position' => 28,
			'supports'      => array( 'title', 'thumbnail', 'page-attributes' ),
			'has_archive'   => false,
			'rewrite'       => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
		)
	);

	register_post_type(
		'qp_faq',
		array(
			'label'               => 'FAQ',
			'labels'              => array(
				'name'          => 'FAQ',
				'singular_name' => 'Câu hỏi thường gặp',
				'add_new'       => 'Thêm câu hỏi',
				'add_new_item'  => 'Thêm câu hỏi mới',
				'edit_item'     => 'Chỉnh sửa câu hỏi',
				'new_item'      => 'Câu hỏi mới',
				'view_item'     => 'Xem câu hỏi',
				'search_items'  => 'Tìm FAQ',
				'menu_name'     => 'FAQ',
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_rest'        => true,
			'menu_icon'           => 'dashicons-editor-help',
			'menu_position'       => 29,
			'supports'            => array( 'title', 'editor', 'page-attributes' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
		)
	);
}
add_action( 'init', 'qp_holdings_register_cpts' );

/**
 * Register taxonomies used across CPTs.
 */
function qp_holdings_register_taxonomies(): void {
	$taxes = array(
		'qp_news_category'    => array( 'Danh mục tin tức', array( 'qp_news' ) ),
		'qp_project_type'     => array( 'Loại dự án', array( 'qp_project' ) ),
		'qp_project_location' => array( 'Vị trí dự án', array( 'qp_project' ) ),
		'qp_project_status'   => array( 'Trạng thái dự án', array( 'qp_project' ) ),
		'qp_job_department'   => array( 'Phòng ban', array( 'qp_job' ) ),
		'qp_job_location'     => array( 'Địa điểm làm việc', array( 'qp_job' ) ),
		'qp_job_level'        => array( 'Cấp bậc', array( 'qp_job' ) ),
		'qp_investor_section' => array( 'Mục nhà đầu tư', array( 'qp_investor_doc' ) ),
		'qp_doc_year'         => array( 'Năm tài liệu', array( 'qp_investor_doc' ) ),
		'qp_doc_type'         => array( 'Loại tài liệu', array( 'qp_investor_doc' ) ),
	);
	foreach ( $taxes as $taxonomy => $data ) {
		register_taxonomy(
			$taxonomy,
			$data[1],
			array(
				'label'        => $data[0],
				'public'       => true,
				'show_ui'      => true,
				'show_in_rest' => true,
				'hierarchical' => true,
				'rewrite'      => array( 'slug' => str_replace( '_', '-', $taxonomy ) ),
			)
		);
	}
}
add_action( 'init', 'qp_holdings_register_taxonomies' );

/**
 * Ensure default terms exist for each taxonomy used by the front-end filters.
 * Insert is idempotent (term_exists guard).
 */
function qp_holdings_ensure_default_terms(): void {
	$defaults = array(
		'qp_news_category'    => array(
			'truyen-thong'   => 'Truyền thông',
			'thong-cao'      => 'Thông cáo báo chí',
			'su-kien'        => 'Sự kiện',
			'nha-dau-tu'     => 'Nhà đầu tư',
			'thi-truong'     => 'Thị trường',
		),
		'qp_project_type'     => array(
			'khu-dan-cu'            => 'Khu dân cư',
			'khu-phuc-hop'         => 'Khu phức hợp',
			'van-phong'            => 'Bất động sản văn phòng',
			'khu-cong-nghiep'      => 'Khu công nghiệp',
			'nang-luong'           => 'Năng lượng',
			'logistics'            => 'Logistics',
			'nghi-duong'           => 'Bất động sản nghỉ dưỡng',
			'trung-tam-thuong-mai' => 'Trung tâm thương mại',
		),
		'qp_project_location' => array(
			'tp-hcm'         => 'TP. Hồ Chí Minh',
			'binh-duong'     => 'Bình Dương',
			'long-an'        => 'Long An',
			'dong-nai'       => 'Đồng Nai',
			'ba-ria-vt'      => 'Bà Rịa - Vũng Tàu',
			'ninh-thuan'     => 'Ninh Thuận',
			'ha-noi'         => 'Hà Nội',
		),
		'qp_project_status'   => array(
			'da-ban-giao'      => 'Đã bàn giao',
			'dang-trien-khai'  => 'Đang triển khai',
			'sap-ra-mat'       => 'Sắp ra mắt',
			'quy-hoach'        => 'Quy hoạch',
		),
		'qp_job_department'   => array(
			'khoi-dau-tu'    => 'Khối Đầu tư',
			'khoi-du-an'     => 'Khối Dự án',
			'khoi-tai-chinh' => 'Khối Tài chính',
			'khoi-phap-che'  => 'Khối Pháp chế',
			'khoi-nhan-su'   => 'Khối Nhân sự',
			'khoi-van-phong' => 'Khối Văn phòng',
			'khoi-marketing' => 'Khối Marketing',
			'khoi-kinh-doanh' => 'Khối Kinh doanh',
		),
		'qp_job_location'     => array(
			'tp-hcm'     => 'TP. Hồ Chí Minh',
			'ha-noi'     => 'Hà Nội',
			'binh-duong' => 'Bình Dương',
			'long-an'    => 'Long An',
		),
		'qp_job_level'        => array(
			'intern' => 'Thực tập sinh',
			'junior' => 'Junior',
			'mid'    => 'Trung cấp',
			'senior' => 'Senior',
			'lead'   => 'Trưởng nhóm / Quản lý',
		),
		'qp_investor_section' => array(
			'stock-info'         => 'Thông tin cổ phiếu',
			'company-governance' => 'Quản trị công ty',
			'agm'                => 'Đại hội đồng cổ đông',
			'annual-report'      => 'Báo cáo thường niên',
			'financial-report'   => 'Báo cáo tài chính',
			'governance-report'  => 'Báo cáo quản trị công ty',
			'disclosure'         => 'Công bố thông tin',
		),
		'qp_doc_type'         => array(
			'pdf'   => 'PDF',
			'doc'   => 'Word',
			'xls'   => 'Excel',
			'other' => 'Khác',
		),
	);

	foreach ( $defaults as $taxonomy => $terms ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}
		foreach ( $terms as $slug => $name ) {
			$existing = get_term_by( 'slug', $slug, $taxonomy );

			if ( ! $existing instanceof WP_Term ) {
				wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
				continue;
			}

			// Self-heal: if the default-language term drifted to a stale label
			// (e.g. an English seed name on a Vietnamese slug), restore the
			// canonical Vietnamese name. Polylang EN/ZH translations use their
			// own distinct slugs, so matching on the exact default slug never
			// touches a translation term.
			if ( $existing->name !== $name ) {
				wp_update_term( $existing->term_id, $taxonomy, array( 'name' => $name ) );
			}
		}
	}
}
add_action( 'init', 'qp_holdings_ensure_default_terms', 20 );

/**
 * Theme-switch hook: flush rewrites & run all seed functions.
 */
function qp_holdings_dynamic_after_theme_switch(): void {
	qp_holdings_register_cpts();
	qp_holdings_register_taxonomies();
	qp_holdings_ensure_default_terms();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'qp_holdings_dynamic_after_theme_switch' );

/**
 * Master seed runner. Call from WP-CLI or after_switch_theme.
 *
 * @param bool $force If true, recreate items even when content already exists.
 */
function qp_holdings_seed_all( bool $force = false ): void {
	qp_holdings_ensure_default_terms();
	if ( function_exists( 'qp_holdings_seed_news' ) ) {
		qp_holdings_seed_news( $force );
	}
	if ( function_exists( 'qp_holdings_seed_projects' ) ) {
		qp_holdings_seed_projects( $force );
	}
	if ( function_exists( 'qp_holdings_seed_jobs' ) ) {
		qp_holdings_seed_jobs( $force );
	}
	if ( function_exists( 'qp_holdings_seed_investor_docs' ) ) {
		qp_holdings_seed_investor_docs( $force );
	}
	if ( function_exists( 'qp_holdings_seed_board_members' ) ) {
		qp_holdings_seed_board_members( $force );
	}
	if ( function_exists( 'qp_holdings_seed_gallery_items' ) ) {
		qp_holdings_seed_gallery_items( $force );
	}
	if ( function_exists( 'qp_holdings_seed_partners' ) ) {
		qp_holdings_seed_partners( $force );
	}
	if ( function_exists( 'qp_holdings_seed_faqs' ) ) {
		qp_holdings_seed_faqs( $force );
	}
}
add_action( 'after_switch_theme', 'qp_holdings_seed_all' );

/**
 * Single template router for our CPTs.
 *
 * - qp_investor_doc → redirect to the underlying file/URL (if set).
 * - qp_project → single-qp_project.php
 * - qp_job     → single-qp_job.php
 * - qp_news    → single-qp_news.php (already in theme root)
 */
function qp_holdings_single_template( string $template ): string {
	if ( is_singular( 'qp_project' ) ) {
		$candidate = get_template_directory() . '/single-qp_project.php';
		if ( file_exists( $candidate ) ) {
			return $candidate;
		}
	}
	if ( is_singular( 'qp_job' ) ) {
		$candidate = get_template_directory() . '/single-qp_job.php';
		if ( file_exists( $candidate ) ) {
			return $candidate;
		}
	}
	return $template;
}
add_filter( 'template_include', 'qp_holdings_single_template' );

/**
 * Investor doc single requests.
 *
 * Sections that use the card-list layout get a real detail page rendered
 * via single-qp_investor_doc.php. Anything else (e.g. legacy direct-PDF
 * imports) keeps the original behaviour of redirecting straight to the file.
 */
function qp_holdings_redirect_investor_doc_single(): void {
	if ( ! is_singular( 'qp_investor_doc' ) ) {
		return;
	}

	$id = get_queried_object_id();

	// Posts assigned to any card-list section render the detail page.
	if ( function_exists( 'qp_holdings_card_list_sections' ) ) {
		foreach ( qp_holdings_card_list_sections() as $section_slug ) {
			if ( has_term( $section_slug, 'qp_investor_section', $id ) ) {
				return;
			}
		}
	}

	$url  = function_exists( 'qp_holdings_acf_or_meta' )
		? qp_holdings_acf_or_meta( $id, 'doc_url', 'doc_url' )
		: get_post_meta( $id, 'doc_url', true );
	$file = function_exists( 'qp_holdings_acf_or_meta' )
		? qp_holdings_acf_or_meta( $id, 'doc_file', 'doc_file' )
		: get_post_meta( $id, 'doc_file', true );

	$target = '';
	if ( is_string( $url ) && $url ) {
		$target = $url;
	} elseif ( is_array( $file ) && ! empty( $file['url'] ) ) {
		$target = $file['url'];
	} elseif ( is_numeric( $file ) ) {
		$target = wp_get_attachment_url( (int) $file );
	} elseif ( is_string( $file ) && $file ) {
		$target = $file;
	}

	if ( $target ) {
		wp_safe_redirect( $target, 302 );
		exit;
	}
}
add_action( 'template_redirect', 'qp_holdings_redirect_investor_doc_single' );

/**
 * Route singular qp_investor_doc to the detail template and newsletter pages to modern investor template.
 */
add_filter(
	'template_include',
	static function ( string $template ): string {
		if ( is_singular( 'qp_investor_doc' ) ) {
			if ( function_exists( 'qp_holdings_card_list_sections' ) ) {
				$id = get_queried_object_id();
				foreach ( qp_holdings_card_list_sections() as $section_slug ) {
					if ( has_term( $section_slug, 'qp_investor_section', $id ) ) {
						$candidate = get_template_directory() . '/single-qp_investor_doc.php';
						if ( file_exists( $candidate ) ) {
							return $candidate;
						}
						break;
					}
				}
			}
			return $template;
		}

		if ( is_page() ) {
			$page_id = get_queried_object_id();
			$slug = (string) get_post_field( 'post_name', $page_id );
			if ( in_array( $slug, array( 'ban-tin', 'ban-tin-2', 'newsletter', 'investor-newsletter', 'ban-tin-nha-dau-tu', 'newsletter-zh' ), true ) ) {
				$candidate = get_template_directory() . '/page-ban-tin.php';
				if ( file_exists( $candidate ) ) {
					return $candidate;
				}
			}
		}

		// Prevent 404 on investor newsletter multilingual routes
		$request_path = trim( (string) wp_parse_url( add_query_arg( array() ), PHP_URL_PATH ), '/' );
		if ( '' === $request_path ) {
			$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
			$request_path = trim( (string) wp_parse_url( $request_uri, PHP_URL_PATH ), '/' );
		}
		if ( preg_match( '#^(?:(?:en|zh)/)?(?:investor-relations/|nha-dau-tu/)?(?:newsletter|newsletter-zh|ban-tin)$#i', $request_path ) || ( is_404() && preg_match( '#newsletter|ban-tin#i', $request_path ) ) ) {
			$lang = function_exists( 'qp_holdings_current_language' ) ? qp_holdings_current_language() : 'vi';
			$defs = function_exists( 'qp_holdings_page_definitions' ) ? qp_holdings_page_definitions() : array();
			if ( ! empty( $defs['newsletter'][ $lang ]['url'] ) ) {
				$custom_target = $defs['newsletter'][ $lang ]['url'];
				$clean_custom  = trim( (string) wp_parse_url( $custom_target, PHP_URL_PATH ), '/' );
				if ( $clean_custom !== $request_path ) {
					wp_safe_redirect( $custom_target, 302 );
					exit;
				}
			}
			$candidate = get_template_directory() . '/page-ban-tin.php';
			if ( file_exists( $candidate ) ) {
				status_header( 200 );
				if ( isset( $GLOBALS['wp_query'] ) && is_object( $GLOBALS['wp_query'] ) ) {
					$GLOBALS['wp_query']->is_404 = false;
				}
				return $candidate;
			}
		}

		return $template;
	}
);

/* ------------------------------------------------------------------
 * Module includes.
 * ------------------------------------------------------------------ */
$qp_holdings_dynamic_modules = array(
	'dynamic-helpers.php',
	'dynamic-acf.php',
	'dynamic-submissions.php',
	'dynamic-news.php',
	'dynamic-projects.php',
	'dynamic-project-meta.php',
	'dynamic-jobs.php',
	'dynamic-investor.php',
	'dynamic-board.php',
	'dynamic-gallery.php',
	'dynamic-partners.php',
	'dynamic-faq.php',
	'dynamic-editor.php',
	'dynamic-seo.php',
);
foreach ( $qp_holdings_dynamic_modules as $qp_holdings_dynamic_module ) {
	$qp_holdings_dynamic_module_path = get_template_directory() . '/inc/' . $qp_holdings_dynamic_module;
	if ( file_exists( $qp_holdings_dynamic_module_path ) ) {
		require_once $qp_holdings_dynamic_module_path;
	}
}
unset( $qp_holdings_dynamic_modules, $qp_holdings_dynamic_module, $qp_holdings_dynamic_module_path );
