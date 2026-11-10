<?php
/**
 * Multilingual helpers for QP Holdings.
 *
 * The theme was migrated from a mostly static Vietnamese export. These helpers
 * keep the templates stable while enabling Polylang-powered pages, translated
 * slugs, language switchers, SEO locale data, and broad static-text translation.
 */

defined( 'ABSPATH' ) || exit;

function qp_holdings_polylang_active(): bool {
	return function_exists( 'pll_current_language' ) && function_exists( 'pll_get_post' );
}

function qp_holdings_current_language(): string {
	if ( isset( $_GET['lang'] ) && in_array( $_GET['lang'], array( 'en', 'zh', 'vi' ), true ) ) {
		return sanitize_key( $_GET['lang'] );
	}

	if ( isset( $_GET['qp_lang'] ) && in_array( $_GET['qp_lang'], array( 'en', 'zh', 'vi' ), true ) ) {
		return sanitize_key( $_GET['qp_lang'] );
	}

	$pll_lang_query = get_query_var( 'lang' );
	if ( is_string( $pll_lang_query ) && in_array( $pll_lang_query, array( 'en', 'zh', 'vi' ), true ) ) {
		return $pll_lang_query;
	}

	$lang_query = get_query_var( 'qp_lang' );
	if ( is_string( $lang_query ) && in_array( $lang_query, array( 'en', 'zh', 'vi' ), true ) ) {
		return $lang_query;
	}

	$the_request = isset( $_SERVER['THE_REQUEST'] ) ? (string) wp_unslash( $_SERVER['THE_REQUEST'] ) : '';
	if ( preg_match( '#\s/(en|zh)(?:/|\s|\?)#', $the_request, $matches ) ) {
		return $matches[1];
	}

	$path = trim( (string) wp_parse_url( add_query_arg( array() ), PHP_URL_PATH ), '/' );
	if ( '' === $path ) {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path        = trim( (string) wp_parse_url( $request_uri, PHP_URL_PATH ), '/' );
	}
	if ( 0 === strpos( $path, 'en/' ) || 'en' === $path ) {
		return 'en';
	}
	if ( 0 === strpos( $path, 'zh/' ) || 'zh' === $path ) {
		return 'zh';
	}

	$object_id = get_queried_object_id();
	if ( ! $object_id && function_exists( 'get_the_ID' ) ) {
		$object_id = (int) get_the_ID();
	}
	if ( $object_id ) {
		if ( function_exists( 'pll_get_post_language' ) ) {
			$post_lang = (string) pll_get_post_language( $object_id, 'slug' );
			if ( in_array( $post_lang, array( 'en', 'zh', 'vi' ), true ) ) {
				return $post_lang;
			}
		}
		$meta_lang = (string) get_post_meta( $object_id, '_qp_translation_lang', true );
		if ( in_array( $meta_lang, array( 'en', 'zh', 'vi' ), true ) ) {
			return $meta_lang;
		}
		$slug = (string) get_post_field( 'post_name', $object_id );
		if ( preg_match( '/^(?:en|zh)-/i', $slug, $slug_m ) ) {
			return strtolower( trim( $slug_m[0], '-' ) );
		}
		if ( preg_match( '/-(?:en|zh)$/i', $slug, $slug_m ) ) {
			return strtolower( trim( $slug_m[0], '-' ) );
		}
		if ( in_array( $slug, array( 'investor-relations', 'corporate-governance', 'general-meeting-of-shareholders', 'annual-reports', 'financial-reports', 'corporate-governance-reports', 'disclosures', 'newsletter', 'stock-information', 'about-us' ), true ) ) {
			return 'en';
		}
		$parent_id = wp_get_post_parent_id( $object_id );
		if ( $parent_id ) {
			$parent_slug = (string) get_post_field( 'post_name', $parent_id );
			if ( in_array( $parent_slug, array( 'investor-relations', 'about-us' ), true ) ) {
				return 'en';
			}
			if ( preg_match( '/-(?:en|zh)$/i', $parent_slug, $p_m ) || preg_match( '/^(?:en|zh)-/i', $parent_slug, $p_m ) ) {
				return strtolower( trim( $p_m[0], '-' ) );
			}
		}
	}

	if ( function_exists( 'pll_current_language' ) ) {
		$lang = pll_current_language( 'slug' );
		if ( is_string( $lang ) && $lang ) {
			return $lang;
		}
	}

	// The site content is Vietnamese by default. Do not infer frontend content
	// language from the admin/site locale when Polylang has not resolved one.
	return 'vi';
}

/**
 * Decode strings imported as escaped UTF-8 byte sequences (\xE6... or xE6...).
 *
 * Keep the replacement conservative: only dense byte runs are decoded so normal
 * English/Vietnamese text containing incidental "xNN" fragments is not damaged.
 */
function qp_holdings_decode_escaped_utf8_text( string $text ): string {
	if ( '' === $text ) {
		return $text;
	}

	$decoded = preg_replace_callback(
		'/(?:\\\\x[0-9A-Fa-f]{2}){2,}/',
		static function ( array $match ): string {
			return preg_replace_callback(
				'/\\\\x([0-9A-Fa-f]{2})/',
				static function ( array $byte ): string {
					return chr( hexdec( $byte[1] ) );
				},
				$match[0]
			);
		},
		$text
	);

	$decoded = preg_replace_callback(
		'/(?:x[0-9A-Fa-f]{2}){2,}/',
		static function ( array $match ): string {
			return preg_replace_callback(
				'/x([0-9A-Fa-f]{2})/',
				static function ( array $byte ): string {
					return chr( hexdec( $byte[1] ) );
				},
				$match[0]
			);
		},
		(string) $decoded
	);

	return preg_match( '//u', (string) $decoded ) ? (string) $decoded : $text;
}

function qp_holdings_query_vars( array $vars ): array {
	$vars[] = 'qp_lang';
	return $vars;
}
add_filter( 'query_vars', 'qp_holdings_query_vars' );

function qp_holdings_page_template_map(): array {
	return array(
		'home'               => 'front-page.php',
		'about'              => 'page-gioi-thieu.php',
		'projects'           => 'page-du-an.php',
		'news'               => 'page-tintuc.php',
		'careers'            => 'page-tuyen-dung.php',
		'faq'                => 'page-cau-hoi-thuong-gap.php',
		'contact'            => 'page-lien-he.php',
		'investor'           => 'page-nha-dau-tu.php',
		'stock'              => 'page-thong-tin-co-phieu.php',
		'governance'         => 'page-quan-tri-cong-ty.php',
		'agm'                => 'page-dai-hoi-dong-co-dong.php',
		'annual_reports'     => 'page-bao-cao-thuong-nien.php',
		'financial_reports'  => 'page-bao-cao-tai-chinh.php',
		'governance_reports' => 'page-bao-cao-quan-tri-cong-ty.php',
		'disclosures'        => 'page-cong-bo-thong-tin.php',
		'newsletter'         => 'page-ban-tin.php',
		'terms'              => 'page-dieu-khoan-su-dung.php',
		'privacy'            => 'page-chinh-sach-bao-mat.php',
	);
}

function qp_holdings_language_rewrite_rules(): void {
	$defs = qp_holdings_page_definitions();
	foreach ( array( 'en', 'zh' ) as $lang ) {
		add_rewrite_rule( '^' . $lang . '/?$', 'index.php?pagename=' . qp_holdings_page_storage_slug( 'home', 'vi' ) . '&lang=' . $lang, 'top' );

		// 1. CPT single rules with language prefix (e.g. /en/du-an-cpt/..., /zh/du-an-cpt/...).
		add_rewrite_rule( '^' . $lang . '/du-an-cpt/([^/]+)/?$', 'index.php?qp_project=$matches[1]&lang=' . $lang, 'top' );
		add_rewrite_rule( '^' . $lang . '/tin-tuc/([^/]+)/?$', 'index.php?qp_news=$matches[1]&lang=' . $lang, 'top' );
		add_rewrite_rule( '^' . $lang . '/tuyen-dung-cpt/([^/]+)/?$', 'index.php?qp_job=$matches[1]&lang=' . $lang, 'top' );
		add_rewrite_rule( '^' . $lang . '/tai-lieu-nha-dau-tu/([^/]+)/?$', 'index.php?qp_investor_doc=$matches[1]&lang=' . $lang, 'top' );

		// 2. Specific core page rules (added first in extra_rules_top).
		foreach ( $defs as $key => $def ) {
			if ( 'home' === $key || empty( $def[ $lang ]['slug'] ) ) {
				continue;
			}
			$path = $def[ $lang ]['slug'];
			if ( ! empty( $def['parent'] ) ) {
				$parent_key = $def['parent'];
				$path = ( $defs[ $parent_key ][ $lang ]['slug'] ?? '' ) . '/' . $path;
			}
			$pagename = qp_holdings_page_storage_slug( $key, 'vi' );
			if ( ! empty( $def['parent'] ) ) {
				$parent_key = $def['parent'];
				$pagename = qp_holdings_page_storage_slug( $parent_key, 'vi' ) . '/' . $pagename;
			}
			add_rewrite_rule( '^' . $lang . '/' . trim( $path, '/' ) . '/?$', 'index.php?pagename=' . trim( $pagename, '/' ) . '&lang=' . $lang, 'top' );
		}

		// 3. Fallback for custom / dynamic pages with language prefix (e.g. /en/trang-custom-en/, /zh/trang-custom-zh/).
		add_rewrite_rule( '^' . $lang . '/([^/]+)/?$', 'index.php?pagename=$matches[1]&lang=' . $lang, 'top' );
	}
}
add_action( 'init', 'qp_holdings_language_rewrite_rules', 5 );

function qp_holdings_multilingual_home_template( string $template ): string {
	$lang = qp_holdings_current_language();
	$path = trim( (string) wp_parse_url( add_query_arg( array() ), PHP_URL_PATH ), '/' );
	if ( in_array( $lang, array( 'en', 'zh' ), true ) && $path === $lang ) {
		$front = get_template_directory() . '/front-page.php';
		if ( file_exists( $front ) ) {
			return $front;
		}
	}

	return $template;
}
add_filter( 'template_include', 'qp_holdings_multilingual_home_template', 5 );

function qp_holdings_supported_languages(): array {
	return array(
		'vi' => array( 'name' => 'Tiếng Việt', 'short' => 'VN', 'locale' => 'vi_VN', 'schema' => 'vi-VN' ),
		'en' => array( 'name' => 'English', 'short' => 'EN', 'locale' => 'en_US', 'schema' => 'en-US' ),
		'zh' => array( 'name' => '中文', 'short' => '中文', 'locale' => 'zh_CN', 'schema' => 'zh-CN' ),
	);
}

function qp_holdings_language_locale( string $type = 'og' ): string {
	$lang = qp_holdings_current_language();
	$defs = qp_holdings_supported_languages();
	$key  = 'schema' === $type ? 'schema' : 'locale';

	return $defs[ $lang ][ $key ] ?? $defs['vi'][ $key ];
}

function qp_holdings_page_definitions(): array {
	return array(
		'home' => array(
			'vi' => array( 'title' => 'Trang Chủ', 'slug' => 'trang-chu' ),
			'en' => array( 'title' => 'Home', 'slug' => 'home' ),
			'zh' => array( 'title' => '首页', 'slug' => 'home', 'internal_slug' => 'home-zh' ),
		),
		'about' => array(
			'vi' => array( 'title' => 'Giới thiệu', 'slug' => 'gioi-thieu' ),
			'en' => array( 'title' => 'About', 'slug' => 'about' ),
			'zh' => array( 'title' => '关于我们', 'slug' => 'about', 'internal_slug' => 'about-zh' ),
		),
		'projects' => array(
			'vi' => array( 'title' => 'Dự án', 'slug' => 'du-an' ),
			'en' => array( 'title' => 'Projects', 'slug' => 'projects' ),
			'zh' => array( 'title' => '项目', 'slug' => 'projects', 'internal_slug' => 'projects-zh' ),
		),
		'news' => array(
			'vi' => array( 'title' => 'Tin tức', 'slug' => 'tintuc' ),
			'en' => array( 'title' => 'News', 'slug' => 'news' ),
			'zh' => array( 'title' => '新闻', 'slug' => 'news', 'internal_slug' => 'news-zh' ),
		),
		'careers' => array(
			'vi' => array( 'title' => 'Tuyển dụng', 'slug' => 'tuyen-dung' ),
			'en' => array( 'title' => 'Careers', 'slug' => 'careers' ),
			'zh' => array( 'title' => '招聘', 'slug' => 'careers', 'internal_slug' => 'careers-zh' ),
		),
		'faq' => array(
			'vi' => array( 'title' => 'Câu hỏi thường gặp', 'slug' => 'cau-hoi-thuong-gap' ),
			'en' => array( 'title' => 'FAQ', 'slug' => 'faq' ),
			'zh' => array( 'title' => '常见问题', 'slug' => 'faq', 'internal_slug' => 'faq-zh' ),
		),
		'contact' => array(
			'vi' => array( 'title' => 'Liên hệ', 'slug' => 'lien-he' ),
			'en' => array( 'title' => 'Contact', 'slug' => 'contact' ),
			'zh' => array( 'title' => '联系我们', 'slug' => 'contact', 'internal_slug' => 'contact-zh' ),
		),
		'terms' => array(
			'vi' => array( 'title' => 'Điều khoản sử dụng', 'slug' => 'dieu-khoan-su-dung' ),
			'en' => array( 'title' => 'Terms of Use', 'slug' => 'terms-of-use' ),
			'zh' => array( 'title' => '使用条款', 'slug' => 'terms-of-use', 'internal_slug' => 'terms-of-use-zh' ),
		),
		'privacy' => array(
			'vi' => array( 'title' => 'Chính sách bảo mật', 'slug' => 'chinh-sach-bao-mat' ),
			'en' => array( 'title' => 'Privacy Policy', 'slug' => 'privacy-policy' ),
			'zh' => array( 'title' => '隐私政策', 'slug' => 'privacy-policy', 'internal_slug' => 'privacy-policy-zh' ),
		),
		'investor' => array(
			'vi' => array( 'title' => 'Quan hệ nhà đầu tư', 'slug' => 'nha-dau-tu' ),
			'en' => array( 'title' => 'Investor Relations', 'slug' => 'investor-relations' ),
			'zh' => array( 'title' => '投资者关系', 'slug' => 'investor-relations', 'internal_slug' => 'investor-relations-zh' ),
		),
		'stock' => array(
			'parent' => 'investor',
			'vi' => array( 'title' => 'Thông tin cổ phiếu', 'slug' => 'thong-tin-co-phieu' ),
			'en' => array( 'title' => 'Stock Information', 'slug' => 'stock-information' ),
			'zh' => array( 'title' => '股票信息', 'slug' => 'stock-information', 'internal_slug' => 'stock-information-zh' ),
		),
		'governance' => array(
			'parent' => 'investor',
			'vi' => array( 'title' => 'Quản trị công ty', 'slug' => 'quan-tri-cong-ty' ),
			'en' => array( 'title' => 'Corporate Governance', 'slug' => 'corporate-governance' ),
			'zh' => array( 'title' => '公司治理', 'slug' => 'corporate-governance', 'internal_slug' => 'corporate-governance-zh' ),
		),
		'agm' => array(
			'parent' => 'investor',
			'vi' => array( 'title' => 'Đại hội đồng cổ đông', 'slug' => 'dai-hoi-dong-co-dong' ),
			'en' => array( 'title' => 'General Meeting of Shareholders', 'slug' => 'general-meeting-of-shareholders' ),
			'zh' => array( 'title' => '股东大会', 'slug' => 'general-meeting-of-shareholders', 'internal_slug' => 'general-meeting-of-shareholders-zh' ),
		),
		'annual_reports' => array(
			'parent' => 'investor',
			'vi' => array( 'title' => 'Báo cáo thường niên', 'slug' => 'bao-cao-thuong-nien' ),
			'en' => array( 'title' => 'Annual Reports', 'slug' => 'annual-reports' ),
			'zh' => array( 'title' => '年度报告', 'slug' => 'annual-reports', 'internal_slug' => 'annual-reports-zh' ),
		),
		'financial_reports' => array(
			'parent' => 'investor',
			'vi' => array( 'title' => 'Báo cáo tài chính', 'slug' => 'bao-cao-tai-chinh' ),
			'en' => array( 'title' => 'Financial Reports', 'slug' => 'financial-reports' ),
			'zh' => array( 'title' => '财务报告', 'slug' => 'financial-reports', 'internal_slug' => 'financial-reports-zh' ),
		),
		'governance_reports' => array(
			'parent' => 'investor',
			'vi' => array( 'title' => 'Báo cáo quản trị công ty', 'slug' => 'bao-cao-quan-tri-cong-ty' ),
			'en' => array( 'title' => 'Corporate Governance Reports', 'slug' => 'corporate-governance-reports' ),
			'zh' => array( 'title' => '公司治理报告', 'slug' => 'corporate-governance-reports', 'internal_slug' => 'corporate-governance-reports-zh' ),
		),
		'disclosures' => array(
			'parent' => 'investor',
			'vi' => array( 'title' => 'Công bố thông tin', 'slug' => 'cong-bo-thong-tin' ),
			'en' => array( 'title' => 'Disclosures', 'slug' => 'disclosures' ),
			'zh' => array( 'title' => '信息披露', 'slug' => 'disclosures', 'internal_slug' => 'disclosures-zh' ),
		),
		'newsletter' => array(
			'parent' => 'investor',
			'vi' => array( 'title' => 'Bản tin nhà đầu tư', 'slug' => 'ban-tin' ),
			'en' => array( 'title' => 'Investor Newsletter', 'slug' => 'newsletter' ),
			'zh' => array( 'title' => '投资者简报', 'slug' => 'newsletter', 'internal_slug' => 'newsletter-zh' ),
		),
	);
}

function qp_holdings_page_storage_slug( string $key, string $lang ): string {
	$defs = qp_holdings_page_definitions();
	if ( empty( $defs[ $key ][ $lang ] ) ) {
		return '';
	}

	return (string) ( $defs[ $key ][ $lang ]['internal_slug'] ?? ( $defs[ $key ][ $lang ]['slug'] ?? '' ) );
}

function qp_holdings_static_t( string $key, string $lang ): string {
	static $static_cache = array();
	if ( ! isset( $static_cache[ $lang ] ) ) {
		$static_cache[ $lang ] = qp_holdings_static_translation_map( $lang );
	}
	return $static_cache[ $lang ][ $key ] ?? $key;
}

/**
 * Admin inline-edit overrides, stored sparsely per language in a single
 * autoloaded option `qp_holdings_text_overrides_{lang}`. Memoized per request.
 *
 * @param string $lang Language code (vi|en|zh).
 * @return array<string,string> key => overridden text
 */
function qp_holdings_text_overrides( string $lang ): array {
	static $cache = array();
	if ( ! isset( $cache[ $lang ] ) ) {
		$cache[ $lang ] = (array) get_option( 'qp_holdings_text_overrides_' . $lang, array() );
	}
	return $cache[ $lang ];
}

/**
 * The full static-text dictionary. Built once per request and memoized so the
 * ~1000-entry array is not reconstructed on every qp_holdings_t() call.
 *
 * @return array<string,array<string,string>> key => [ 'vi' => .., 'en' => .., 'zh' => .. ]
 */
function qp_holdings_translation_dict(): array {
	static $dict = null;
	if ( null !== $dict ) {
		return $dict;
	}
	$dict = array(
		'home' => array( 'vi' => 'Trang chủ', 'en' => 'Home', 'zh' => '首页' ),
		'about' => array( 'vi' => 'Giới thiệu', 'en' => 'About', 'zh' => '关于我们' ),
		'projects' => array( 'vi' => 'Dự án', 'en' => 'Projects', 'zh' => '项目' ),
		'investor' => array( 'vi' => 'Quan hệ nhà đầu tư', 'en' => 'Investor Relations', 'zh' => '投资者关系' ),
		'news' => array( 'vi' => 'Tin tức', 'en' => 'News', 'zh' => '新闻' ),
		'careers' => array( 'vi' => 'Tuyển dụng', 'en' => 'Careers', 'zh' => '招聘' ),
		'faq' => array( 'vi' => 'FAQ', 'en' => 'FAQ', 'zh' => 'FAQ' ),
		'contact' => array( 'vi' => 'Liên hệ', 'en' => 'Contact', 'zh' => '联系我们' ),
		'stock_code' => array( 'vi' => 'MÃ CK: HKT', 'en' => 'Ticker: HKT', 'zh' => '股票代码：HKT' ),
		'menu_open' => array( 'vi' => 'Mở menu', 'en' => 'Open menu', 'zh' => '打开菜单' ),
		'menu_close' => array( 'vi' => 'Đóng menu', 'en' => 'Close menu', 'zh' => '关闭菜单' ),
		'main_menu' => array( 'vi' => 'Menu chính', 'en' => 'Main menu', 'zh' => '主菜单' ),
		'all' => array( 'vi' => 'Tất cả', 'en' => 'All', 'zh' => '全部' ),
		'view_all' => array( 'vi' => 'Xem Tất Cả', 'en' => 'View all', 'zh' => '查看全部' ),
		'read_more' => array( 'vi' => 'Đọc tiếp →', 'en' => 'Read more →', 'zh' => '继续阅读 →' ),
		'view_apply' => array( 'vi' => 'Xem & ứng tuyển', 'en' => 'View & apply', 'zh' => '查看并申请' ),
		'deadline' => array( 'vi' => 'Hạn:', 'en' => 'Deadline:', 'zh' => '截止日期：' ),
		'download' => array( 'vi' => 'Tải', 'en' => 'Download', 'zh' => '下载' ),
		'year' => array( 'vi' => 'Năm', 'en' => 'Year', 'zh' => '年份' ),
		'document' => array( 'vi' => 'Tài liệu', 'en' => 'Document', 'zh' => '文件' ),
		'release_date' => array( 'vi' => 'Ngày phát hành', 'en' => 'Release date', 'zh' => '发布日期' ),
		'size' => array( 'vi' => 'Kích thước', 'en' => 'Size', 'zh' => '大小' ),
		'action' => array( 'vi' => 'FILE', 'en' => 'FILE', 'zh' => 'FILE' ),
		'department' => array( 'vi' => 'Phòng ban', 'en' => 'Department', 'zh' => '部门' ),
		'location' => array( 'vi' => 'Địa điểm', 'en' => 'Location', 'zh' => '地点' ),
		'level' => array( 'vi' => 'Cấp bậc', 'en' => 'Level', 'zh' => '级别' ),
		'jobs_filter_block_label' => array( 'vi' => 'Khối', 'en' => 'Division', 'zh' => '事业群' ),
		'jobs_filter_location_label' => array( 'vi' => 'Địa điểm', 'en' => 'Location', 'zh' => '地点' ),
		'jobs_filter_all_blocks' => array( 'vi' => 'Tất cả khối', 'en' => 'All divisions', 'zh' => '全部事业群' ),
		'jobs_filter_all_locations' => array( 'vi' => 'Tất cả địa điểm', 'en' => 'All locations', 'zh' => '全部地点' ),
		'jobs_filter_apply' => array( 'vi' => 'Lọc', 'en' => 'Apply filters', 'zh' => '筛选' ),
		'jobs_filter_reset' => array( 'vi' => 'Xóa bộ lọc', 'en' => 'Reset filters', 'zh' => '清除筛选' ),
		'jobs_filter_legend' => array( 'vi' => 'Bộ lọc tuyển dụng', 'en' => 'Job filters', 'zh' => '职位筛选' ),
		'project_type' => array( 'vi' => 'Loại dự án', 'en' => 'Project type', 'zh' => '项目类型' ),
		'status' => array( 'vi' => 'Trạng thái', 'en' => 'Status', 'zh' => '状态' ),
		'view_details' => array( 'vi' => 'Xem chi tiết', 'en' => 'View details', 'zh' => '查看详情' ),
		'featured_projects_title' => array( 'vi' => 'Dự án đang triển khai', 'en' => 'Projects in Progress', 'zh' => '进行中的项目' ),
		'allied_projects_title' => array( 'vi' => 'Dự án hợp tác đầu tư & phát triển', 'en' => 'Investment & Development Collaboration Projects', 'zh' => '投资与开发合作项目' ),
		'project_news_title' => array( 'vi' => 'Tin dự án', 'en' => 'Project News', 'zh' => '项目新闻' ),
		'projects_hero_desc' => array( 'vi' => 'Khám phá hệ sinh thái dự án bất động sản, khu đô thị, khu công nghiệp và các dự án tiêu biểu của QP Holdings.', 'en' => 'Discover QP Holdings’ portfolio of real estate, urban, industrial, and flagship projects.', 'zh' => '探索 QP Holdings 的房地产、城市、工业及重点项目组合。' ),
		'investor_hero_desc' => array( 'vi' => 'QP Holdings cập nhật thông tin đầy đủ về các kỳ đại hội cổ đông thường niên và bất thường, bao gồm tài liệu họp và biên bản cuộc họp và các thông tin khác liên quan báo cáo thường niên, báo cáo tài chính, báo cáo quản trị công ty và công bố thông tin.', 'en' => 'QP Holdings publishes complete information about annual and extraordinary general meetings — including meeting documents and minutes — together with annual reports, financial reports, governance reports and disclosures.', 'zh' => 'QP Holdings 公布年度及临时股东大会的完整资料，包括会议文件、会议纪要、年度报告、财务报告、治理报告及信息披露。' ),
		'projects_archive_label' => array( 'vi' => 'Danh sách dự án', 'en' => 'Project List', 'zh' => '项目列表' ),
		'news_hero_title' => array( 'vi' => 'Truyền thông & tin tức', 'en' => 'Media & News', 'zh' => '媒体与新闻' ),
		'news_hero_desc' => array( 'vi' => 'Cập nhật tin tức, hoạt động doanh nghiệp, thị trường và quan hệ nhà đầu tư từ QP Holdings.', 'en' => 'Stay updated with QP Holdings news, corporate activities, market insights, and investor relations.', 'zh' => '获取 QP Holdings 新闻、企业动态、市场观点和投资者关系信息。' ),
		'news_archive_label' => array( 'vi' => 'Tin tức QP Holdings', 'en' => 'QP Holdings News', 'zh' => 'QP Holdings 新闻' ),
		'profile' => array( 'vi' => 'Hồ sơ doanh nghiệp', 'en' => 'Corporate Profile', 'zh' => '企业概况' ),
		'profile_page_title' => array( 'vi' => 'Hồ sơ doanh nghiệp', 'en' => 'Corporate Profile', 'zh' => '企业概况' ),
		'profile_hero_desc' => array( 'vi' => 'Tổng quan về năng lực, tầm nhìn chiến lược, cơ cấu tổ chức và hành trình phát triển của Tập đoàn QP Holdings.', 'en' => 'Overview of QP Holdings’ capabilities, strategic vision, corporate structure, and development journey.', 'zh' => 'QP Holdings 集团综合实力、战略愿景、组织架构及发展历程概览。' ),
		'no_posts' => array( 'vi' => 'Chưa có bài viết nào.', 'en' => 'No posts are available yet.', 'zh' => '暂无文章。' ),
		'no_projects' => array( 'vi' => 'Chưa có dự án phù hợp với bộ lọc.', 'en' => 'No projects match the selected filters.', 'zh' => '没有符合筛选条件的项目。' ),
		'no_jobs' => array( 'vi' => 'Hiện chưa có vị trí phù hợp với bộ lọc. Vui lòng quay lại sau hoặc thử bộ lọc khác.', 'en' => 'No roles match the selected filters. Please check back later or try different filters.', 'zh' => '暂无符合筛选条件的职位。请稍后再查看或尝试其他筛选条件。' ),
		'no_docs' => array( 'vi' => 'Chưa có tài liệu nào trong mục này.', 'en' => 'No documents are available in this section yet.', 'zh' => '此栏目暂无文件。' ),
		'terms' => array( 'vi' => 'Điều khoản', 'en' => 'Terms', 'zh' => '条款' ),
		'terms_page_title' => array( 'vi' => 'Điều khoản sử dụng', 'en' => 'Terms of Use', 'zh' => '使用条款' ),
		'terms_hero_desc' => array( 'vi' => 'Xin chân thành cám ơn vì đã tin tưởng vào Sàn GD Bất Động Sản QP Holdings', 'en' => 'Thank you for trusting QP Holdings Real Estate Exchange', 'zh' => '感谢您信任QP Holdings房地产交易所' ),
		'privacy' => array( 'vi' => 'Chính sách bảo mật', 'en' => 'Privacy Policy', 'zh' => '隐私政策' ),
		'privacy_page_title' => array( 'vi' => 'Chính sách bảo mật', 'en' => 'Privacy Policy', 'zh' => '隐私政策' ),
		'privacy_hero_desc' => array( 'vi' => 'QP Holdings bảo mật tuyệt đối thông tin khách hàng', 'en' => 'QP Holdings absolutely protects customer information', 'zh' => 'QP Holdings 绝对保护客户信息' ),
		'privacy_content_title' => array( 'vi' => 'CHÍNH SÁCH BẢO MẬT THÔNG TIN', 'en' => 'INFORMATION PRIVACY POLICY', 'zh' => '信息隐私政策' ),
		'faq_page_title' => array( 'vi' => 'Câu hỏi thường gặp', 'en' => 'Frequently Asked Questions', 'zh' => '常见问题' ),
		'faq_archive_label' => array( 'vi' => 'Danh sách câu hỏi thường gặp', 'en' => 'Frequently asked questions list', 'zh' => '常见问题列表' ),
		'faq_illustration_alt' => array( 'vi' => 'Đội ngũ QP Holdings đang trao đổi trong văn phòng', 'en' => 'QP Holdings team discussing in the office', 'zh' => 'QP Holdings 团队在办公室讨论' ),
		'footer_company_name' => array( 'vi' => 'CÔNG TY CỔ PHẦN QP HOLDINGS', 'en' => 'QP HOLDINGS JOINT STOCK COMPANY', 'zh' => 'QP HOLDINGS 股份公司' ),
		'footer_head_office_label' => array( 'vi' => 'Trụ sở chính:', 'en' => 'Head Office:', 'zh' => '总部：' ),
		'footer_head_office_address' => array( 'vi' => 'Tầng L16, Tòa nhà Vietcombank, Số 5 Công Trường Mê Linh, Phường Sài Gòn, Thành phố Hồ Chí Minh, Việt Nam', 'en' => 'Level L16, Vietcombank Tower, 5 Cong Truong Me Linh, Sai Gon Ward, Ho Chi Minh City, Vietnam', 'zh' => 'L16层，Vietcombank Tower，Công Trường Mê Linh 5号，Sài Gòn坊，胡志明市，越南' ),
		'footer_binh_duong_label' => array( 'vi' => 'Văn phòng Bình Dương:', 'en' => 'Binh Duong Office:', 'zh' => '平阳办公室：' ),
		'footer_binh_duong_address' => array( 'vi' => 'Nhà phố F1, Đường Huỳnh Thúc Kháng, Phường Bình Dương, Thành phố Hồ Chí Minh, Việt Nam', 'en' => 'F1 townhouse, Huynh Thuc Khang Street, Binh Duong Ward, Ho Chi Minh City, Vietnam', 'zh' => 'F1联排别墅，Huỳnh Thúc Kháng街，Bình Dương坊，胡志明市，越南' ),
		'project_highlights' => array( 'vi' => 'Điểm nổi bật', 'en' => 'Highlights', 'zh' => '项目亮点' ),
		'project_info' => array( 'vi' => 'Thông tin dự án', 'en' => 'Project information', 'zh' => '项目信息' ),
		'project_location' => array( 'vi' => 'Vị trí', 'en' => 'Location', 'zh' => '位置' ),
		'project_scale_label' => array( 'vi' => 'Quy mô', 'en' => 'Scale', 'zh' => '规模' ),
		'project_area' => array( 'vi' => 'Diện tích', 'en' => 'Area', 'zh' => '面积' ),
		'project_investor_label' => array( 'vi' => 'Chủ đầu tư', 'en' => 'Investor', 'zh' => '投资方' ),
		'project_developer_label' => array( 'vi' => 'Phát triển', 'en' => 'Developer', 'zh' => '开发单位' ),
		'project_year_label' => array( 'vi' => 'Năm', 'en' => 'Year', 'zh' => '年份' ),
		'project_handover_label' => array( 'vi' => 'Bàn giao', 'en' => 'Handover', 'zh' => '交付' ),
		'project_related' => array( 'vi' => 'Dự án liên quan', 'en' => 'Related projects', 'zh' => '相关项目' ),
		'job_salary_label' => array( 'vi' => 'Mức lương', 'en' => 'Salary', 'zh' => '薪资' ),
		'job_experience_label' => array( 'vi' => 'Kinh nghiệm', 'en' => 'Experience', 'zh' => '经验' ),
		'job_deadline_label' => array( 'vi' => 'Hạn nộp', 'en' => 'Deadline', 'zh' => '截止日期' ),
		'job_ready_title' => array( 'vi' => 'Sẵn sàng ứng tuyển?', 'en' => 'Ready to apply?', 'zh' => '准备申请了吗？' ),
		'job_ready_desc' => array( 'vi' => 'Gửi CV và thư ứng tuyển trực tiếp cho đội ngũ tuyển dụng QP Holdings.', 'en' => 'Send your CV and cover letter directly to the QP Holdings recruitment team.', 'zh' => '请将您的简历和求职信直接发送给 QP Holdings 招聘团队。' ),
		'job_apply_now' => array( 'vi' => 'Ứng tuyển ngay', 'en' => 'Apply now', 'zh' => '立即申请' ),
		'job_share_title' => array( 'vi' => 'Chia sẻ vị trí này', 'en' => 'Share this role', 'zh' => '分享该职位' ),
		'news_kicker' => array( 'vi' => 'TIN TỨC & SỰ KIỆN', 'en' => 'NEWS & EVENTS', 'zh' => '新闻与活动' ),
		'news_back_list' => array( 'vi' => 'Về danh sách tin tức', 'en' => 'Back to news', 'zh' => '返回新闻列表' ),
		'news_related' => array( 'vi' => 'Tin tức liên quan', 'en' => 'Related news', 'zh' => '相关新闻' ),
		'news_not_found_title' => array( 'vi' => 'Không tìm thấy nội dung', 'en' => 'Content not found', 'zh' => '未找到内容' ),
		'news_not_found_desc' => array( 'vi' => 'Bài viết này hiện không còn hoặc đã được di chuyển.', 'en' => 'This article is no longer available or has been moved.', 'zh' => '该文章当前不可用或已被移动。' ),
		'news_article_info' => array( 'vi' => 'Thông tin bài viết', 'en' => 'Article details', 'zh' => '文章信息' ),
		'news_category_label' => array( 'vi' => 'Danh mục', 'en' => 'Category', 'zh' => '分类' ),
		'news_date_label' => array( 'vi' => 'Ngày đăng', 'en' => 'Published', 'zh' => '发布日期' ),
		'news_section_label' => array( 'vi' => 'Chuyên mục', 'en' => 'Section', 'zh' => '栏目' ),
		'news_share' => array( 'vi' => 'Chia sẻ', 'en' => 'Share', 'zh' => '分享' ),
		'news_continue' => array( 'vi' => 'Tiếp tục khám phá', 'en' => 'Continue exploring', 'zh' => '继续探索' ),
		'news_related_desc' => array( 'vi' => 'Các cập nhật nổi bật từ QP Holdings cùng chủ đề, được sắp xếp để giữ nhịp đọc liền mạch.', 'en' => 'Featured updates from QP Holdings on the same topic, curated to keep your reading journey seamless.', 'zh' => '围绕同一主题精选的 QP Holdings 最新动态，帮助您持续深入阅读。' ),
		'news_news_label' => array( 'vi' => 'Tin tức', 'en' => 'News', 'zh' => '新闻' ),
		'news_article_label' => array( 'vi' => 'Bài viết', 'en' => 'Article', 'zh' => '文章' ),
		'home_hero_title_html' => array( 'vi' => 'Trọn niềm tin, nhân giá trị', 'en' => 'Creating value<br>Leading prosperity', 'zh' => '创造价值<br>引领繁荣' ),
		'home_hero_desc' => array( 'vi' => 'Tự hào là nhà phát triển bất động sản uy tín và chuyên nghiệp tại Việt Nam, QP Holdings khẳng định vị thế qua ba lĩnh vực cốt lõi: Bất động sản dân dụng, Bất động sản công nghiệp và Đầu tư tài chính. Đặt "Uy tín và Chất lượng" làm nền tảng phát triển, chúng tôi kiên định với sứ mệnh kiến tạo những giá trị, đồng hành cùng sự thịnh vượng lâu dài và bền vững của khách hàng, đối tác và cổ đông.', 'en' => 'QP Holdings delivers investment, finance, and securities solutions with a focus on sustainable growth and disciplined risk management.', 'zh' => 'QP Holdings 提供投资、金融与证券解决方案，专注于可持续增长与稳健风险管理。' ),
		'home_cta_projects' => array( 'vi' => 'Khám Phá Dự Án', 'en' => 'Explore Projects', 'zh' => '探索项目' ),
		'home_cta_about' => array( 'vi' => 'Về QP Holdings', 'en' => 'About QP Holdings', 'zh' => '关于 QP Holdings' ),
		'home_stat_equity' => array( 'vi' => 'VỐN CHỦ SỞ HỮU', 'en' => 'OWNER’S EQUITY', 'zh' => '所有者权益' ),
		'home_stat_equity_value' => array( 'vi' => '320+', 'en' => '320+', 'zh' => '320+' ),
		'home_stat_equity_sub' => array( 'vi' => 'Tỷ đồng', 'en' => 'Billion VND', 'zh' => '十亿越盾' ),
		'home_stat_projects' => array( 'vi' => 'DỰ ÁN ĐANG TRIỂN KHAI', 'en' => 'ACTIVE PROJECTS', 'zh' => '在建项目' ),
		'home_stat_projects_value' => array( 'vi' => '3+', 'en' => '3+', 'zh' => '3+' ),
		'home_stat_projects_sub' => array( 'vi' => 'Trên toàn quốc', 'en' => 'Nationwide', 'zh' => '覆盖全国' ),
		'home_stat_partners' => array( 'vi' => 'ĐỐI TÁC & NHÀ ĐẦU TƯ', 'en' => 'PARTNERS & INVESTORS', 'zh' => '合作伙伴与投资者' ),
		'home_stat_partners_value' => array( 'vi' => '50+', 'en' => '50+', 'zh' => '50+' ),
		'home_stat_partners_sub' => array( 'vi' => 'Đồng hành phát triển', 'en' => 'Growing together', 'zh' => '携手成长' ),
		'home_service_finance_title' => array( 'vi' => 'Đầu Tư Tài Chính', 'en' => 'Financial Investment', 'zh' => '金融投资' ),
		'home_service_finance_desc' => array( 'vi' => 'Tối ưu nguồn vốn, quản trị rủi ro hiệu quả, gia tăng giá trị dài hạn.', 'en' => 'Optimize capital allocation, manage risk efficiently, and build long-term value.', 'zh' => '优化资本配置，高效管理风险，持续提升长期价值。' ),
		'home_service_realestate_title' => array( 'vi' => 'Phát Triển Bất Động Sản', 'en' => 'Real Estate Development', 'zh' => '房地产开发' ),
		'home_service_realestate_desc' => array( 'vi' => 'Phát triển các dự án chất lượng bền vững và khác biệt.', 'en' => 'Develop sustainable, high-quality, and differentiated projects.', 'zh' => '开发可持续、高质量且差异化的项目。' ),
		'home_service_industrial_title' => array( 'vi' => 'Phát Triển Khu Công Nghiệp', 'en' => 'Industrial Park Development', 'zh' => '工业园区开发' ),
		'home_service_industrial_desc' => array( 'vi' => 'Đồng hành cùng đối tác và nhà đầu tư trên hành trình thịnh vượng.', 'en' => 'Partner with investors and stakeholders on a shared path to prosperity.', 'zh' => '与合作伙伴及投资者同行，共创繁荣。' ),
		'home_vision_title_html' => array( 'vi' => 'Tầm nhìn - Sứ mệnh<br />- Giá trị cốt lõi', 'en' => 'Vision - Mission<br />- Core values', 'zh' => '愿景 - 使命<br />- 核心价值' ),
		'home_vision_value_1' => array( 'vi' => 'QP Holdings là tập đoàn hoạt động trong lĩnh vực đầu tư tài chính, phát triển Bất động sản, khu công nghiệp và các dịch vụ thương mại – đầu tư chuyên nghiệp tại Việt Nam.', 'en' => 'QP Holdings operates across financial investment, real estate development, industrial parks, and professional commercial-investment services in Vietnam.', 'zh' => 'QP Holdings 深耕金融投资、房地产开发、工业园区及专业商贸投资服务等领域，业务布局于越南。' ),
		'home_vision_value_2' => array( 'vi' => 'Nâng tầm chất lượng cuộc sống Việt bằng những sản phẩm – dịch vụ mang lại giá trị thực.', 'en' => 'Elevate quality of life in Vietnam through products and services that deliver real value.', 'zh' => '通过真正创造价值的产品与服务，提升越南人民的生活品质。' ),
		'home_vision_value_3' => array( 'vi' => 'Là nguyên tắc cho mọi hoạt động, là lời cam kết của QP Holdings đối với khách hàng, đối tác, nhà đầu tư và xã hội:', 'en' => 'These principles guide every activity and reflect QP Holdings’ commitment to customers, partners, investors, and society:', 'zh' => '这些原则贯穿于一切经营活动之中，也是 QP Holdings 对客户、合作伙伴、投资者与社会的承诺：' ),
		'home_about_title' => array( 'vi' => 'Các dự án tiêu biểu', 'en' => 'Featured projects', 'zh' => '精选项目' ),
		'footer_partner_projects' => array( 'vi' => 'Dự án hợp tác đầu tư', 'en' => 'Partner investment projects', 'zh' => '合作投资项目' ),
		'home_about_desc_html' => array( 'vi' => 'QP Holdings kiến tạo hệ sinh thái Bất động sản đa dạng với các dự án tiêu biểu gồm khu dân cư cao cấp QP Green Park, khu công nghiệp chiến lược QP IDC và dòng căn hộ cao tầng hiện đại QP Luxia One, QP Luxia Premier.<br><br>Mỗi dự án được phát triển theo định hướng gia tăng giá trị bền vững, kiến tạo không gian sống – đầu tư – phát triển đồng bộ, góp phần nâng tầm chuẩn mực đô thị và công nghiệp hiện đại tại Việt Nam.', 'en' => 'QP Holdings is building a diversified real estate ecosystem through flagship developments such as the premium residential township QP Green Park, the strategic industrial platform QP IDC, and the modern high-rise residences QP Luxia One and QP Luxia Premier.<br><br>Each project is designed to create sustainable value, integrated living-investment-development environments, and higher standards for modern urban and industrial destinations in Vietnam.', 'zh' => 'QP Holdings 正在构建多元化房地产生态体系，代表项目包括高端住宅社区 QP Green Park、战略型工业平台 QP IDC，以及现代高层住宅 QP Luxia One 与 QP Luxia Premier。<br><br>每个项目都以可持续价值增长为导向，打造生活、投资与发展一体化空间，持续提升越南现代城市与工业项目的标准。' ),
		'home_about_btn' => array( 'vi' => 'Xem Chi Tiết', 'en' => 'View details', 'zh' => '查看详情' ),
		'home_feature_1' => array( 'vi' => 'Khu dân cư QP Green Park', 'en' => 'QP Green Park residential township', 'zh' => 'QP Green Park 住宅社区' ),
		'home_feature_2' => array( 'vi' => 'Cụm công nghiệp QP IDC', 'en' => 'QP IDC industrial cluster', 'zh' => 'QP IDC 工业集群' ),
		'home_feature_3' => array( 'vi' => 'Chung cư QP Luxia One và QP Luxia Premier', 'en' => 'QP Luxia One and QP Luxia Premier residences', 'zh' => 'QP Luxia One 与 QP Luxia Premier 住宅' ),

		/* ── Careers page ── */
		'careers_hero_title'  => array( 'vi' => 'Cùng <span class="gold-gradient-text">QP Holdings</span> kiến tạo tương lai', 'en' => 'Shape the future with <span class="gold-gradient-text">QP Holdings</span>', 'zh' => '携手 <span class="gold-gradient-text">QP Holdings</span> 共创未来' ),
		'careers_hero_desc'   => array( 'vi' => 'Hơn 500 cán bộ nhân viên trên toàn quốc đang cùng nhau tạo nên những giá trị bền vững cho Việt Nam.', 'en' => 'More than 500 employees nationwide are working together to build lasting value for Vietnam.', 'zh' => '全国逾500名员工携手共进，为越南创造持久价值。' ),
		'careers_why_label'   => array( 'vi' => 'TẠI SAO QP HOLDINGS', 'en' => 'WHY QP HOLDINGS', 'zh' => '为什么选择 QP HOLDINGS' ),
		'careers_why_title'   => array( 'vi' => 'Nơi tài năng được trân trọng', 'en' => 'Where talent is valued', 'zh' => '人才备受珍视之地' ),
		'careers_card1_title' => array( 'vi' => 'Lộ trình phát triển', 'en' => 'Career growth path', 'zh' => '职业发展路径' ),
		'careers_card1_desc'  => array( 'vi' => 'Chương trình đào tạo và lộ trình thăng tiến rõ ràng cho từng vị trí.', 'en' => 'Structured training programmes and a clear progression path for every role.', 'zh' => '每个岗位均有系统培训计划与清晰晋升路径。' ),
		'careers_card2_title' => array( 'vi' => 'Phúc lợi toàn diện', 'en' => 'Comprehensive benefits', 'zh' => '全面员工福利' ),
		'careers_card2_desc'  => array( 'vi' => 'Bảo hiểm sức khỏe cao cấp, thưởng hiệu suất, nghỉ phép linh hoạt.', 'en' => 'Premium health insurance, performance bonuses, and flexible leave.', 'zh' => '高端医疗保险、绩效奖金及灵活假期制度。' ),
		'careers_card3_title' => array( 'vi' => 'Văn hoá hợp tác', 'en' => 'Collaborative culture', 'zh' => '协作文化' ),
		'careers_card3_desc'  => array( 'vi' => 'Môi trường đa dạng, tôn trọng sự khác biệt và đề cao tinh thần đồng đội.', 'en' => 'A diverse, inclusive environment that respects difference and champions teamwork.', 'zh' => '多元包容的环境，尊重差异，弘扬团队精神。' ),
		'careers_card4_title' => array( 'vi' => 'Dự án quy mô lớn', 'en' => 'Large-scale projects', 'zh' => '大型项目' ),
		'careers_card4_desc'  => array( 'vi' => 'Tham gia các công trình trọng điểm quốc gia và dự án quốc tế.', 'en' => 'Contribute to key national projects and international developments.', 'zh' => '参与国家重点工程与国际项目。' ),
		'careers_cta_desc'    => array( 'vi' => 'Khám phá cơ hội nghề nghiệp tại tập đoàn — nơi tài năng được trân trọng và phát triển.', 'en' => 'Explore career opportunities at the group — where talent is valued and developed.', 'zh' => '探索集团职业机会——人才备受珍视、持续成长。' ),
		'careers_cta_btn'     => array( 'vi' => 'Xem vị trí tuyển dụng', 'en' => 'View open positions', 'zh' => '查看招聘职位' ),

		/* ── Stock information page ── */
		'stock_page_title'       => array( 'vi' => 'Thông tin cổ phiếu', 'en' => 'Stock Information', 'zh' => '股票信息' ),
		'stock_page_desc'        => array( 'vi' => 'Theo dõi cập nhật thông tin cổ phiếu, dữ liệu giao dịch và các công bố quan trọng của QP Holdings trên Sở Giao dịch Chứng khoán.', 'en' => 'Track the latest stock data, trading figures, and key disclosures from QP Holdings on the Stock Exchange.', 'zh' => '跟踪 QP Holdings 在证券交易所的最新股票数据、交易信息及重要公告。' ),
		'stock_disclosure_title' => array( 'vi' => 'Thông tin công bố', 'en' => 'Disclosed Information', 'zh' => '信息披露' ),

		/* ── Search / UI ── */
		'search_placeholder'  => array( 'vi' => 'Tìm kiếm…', 'en' => 'Search…', 'zh' => '搜索…' ),
		'search_submit'       => array( 'vi' => 'Gửi', 'en' => 'Submit', 'zh' => '搜索' ),

		/* ── Investor doc language badge ── */
		'doc_lang_bilingual'  => array( 'vi' => 'Song ngữ', 'en' => 'Bilingual', 'zh' => '双语' ),
		'doc_lang_vi'         => array( 'vi' => 'Tiếng việt', 'en' => 'Vietnamese', 'zh' => '越南语' ),
		'doc_lang_en'         => array( 'vi' => 'Tiếng Anh', 'en' => 'English', 'zh' => '英语' ),
		'doc_lang_zh'         => array( 'vi' => 'Tiếng Trung', 'en' => 'Chinese', 'zh' => '中文' ),

		/* ── Form validation / messages ── */
		'consult_sent'        => array( 'vi' => 'Cảm ơn bạn đã đăng ký tư vấn. QP Holdings sẽ liên hệ lại trong thời gian sớm nhất.', 'en' => 'Thank you for your inquiry. QP Holdings will contact you as soon as possible.', 'zh' => '感谢您的咨询。QP Holdings 将尽快与您联系。' ),
		'consult_invalid'     => array( 'vi' => 'Vui lòng nhập đầy đủ thông tin bắt buộc trước khi gửi.', 'en' => 'Please fill in all required information before submitting.', 'zh' => '请在提交前填写所有必填信息。' ),
		'consult_error'       => array( 'vi' => 'Đăng ký chưa gửi được. Vui lòng thử lại sau.', 'en' => 'Submission failed. Please try again later.', 'zh' => '提交失败。请稍后再试。' ),
		'consult_title'       => array( 'vi' => 'Đăng ký tư vấn', 'en' => 'Request consultation', 'zh' => '预约咨询' ),
		'full_name'           => array( 'vi' => 'Họ tên', 'en' => 'Full name', 'zh' => '姓名' ),
		'placeholder_name'    => array( 'vi' => 'Vui lòng nhập họ tên', 'en' => 'Please enter your full name', 'zh' => '请输入姓名' ),
		'phone_number'        => array( 'vi' => 'Số điện thoại', 'en' => 'Phone number', 'zh' => '电话号码' ),
		'phone_country_code'  => array( 'vi' => 'Mã quốc gia', 'en' => 'Country code', 'zh' => '国家/地区代码' ),
		'placeholder_phone'   => array( 'vi' => 'Nhập số điện thoại', 'en' => 'Enter phone number', 'zh' => '输入电话号码' ),
		'select_project_interest' => array( 'vi' => 'Chọn dự án bạn quan tâm', 'en' => 'Select project of interest', 'zh' => '选择您感兴趣的项目' ),
		'submit'              => array( 'vi' => 'Gửi', 'en' => 'Submit', 'zh' => '提交' ),
		'related_projects'    => array( 'vi' => 'Các dự án liên quan', 'en' => 'Related projects', 'zh' => '相关项目' ),
		'related_jobs'        => array( 'vi' => 'Vị trí liên quan', 'en' => 'Related jobs', 'zh' => '相关职位' ),
		'project_scale'       => array( 'vi' => 'Quy mô dự án', 'en' => 'Project scale', 'zh' => '项目规模' ),
		'project_stat_type'   => array( 'vi' => 'Loại hình', 'en' => 'Type', 'zh' => '类型' ),
		'project_stat_scale'  => array( 'vi' => 'Quy mô', 'en' => 'Scale', 'zh' => '规模' ),
		'project_stat_investment' => array( 'vi' => 'Tổng vốn đầu tư dự kiến', 'en' => 'Estimated total investment', 'zh' => '预计总投资额' ),
		'project_stat_progress' => array( 'vi' => 'Tiến độ', 'en' => 'Progress', 'zh' => '进度' ),
		'project_stat_address' => array( 'vi' => 'Địa chỉ', 'en' => 'Address', 'zh' => '地址' ),
		'view_project_detail' => array( 'vi' => 'Xem chi tiết dự án', 'en' => 'View project details', 'zh' => '查看项目详情' ),
		'project_hero_default_heading' => array( 'vi' => "Tâm điểm thịnh vượng,\nbiểu tượng sống thời thượng", 'en' => "A focal point of prosperity,\na symbol of contemporary living", 'zh' => "繁荣焦点，\n现代生活象征" ),
		'gallery'             => array( 'vi' => 'Thư viện ảnh', 'en' => 'Gallery', 'zh' => '图库' ),
		'prev'                => array( 'vi' => 'Trước', 'en' => 'Previous', 'zh' => '上一个' ),
		'next'                => array( 'vi' => 'Sau', 'en' => 'Next', 'zh' => '下一个' ),

		/* ── Project taxonomy terms ── */
		'khu-dan-cu-thap-tang'     => array( 'vi' => 'Khu dân cư thấp tầng', 'en' => 'Low-rise Housing Area', 'zh' => '低层住宅区' ),
		'khu-phuc-hop-cao-tang'    => array( 'vi' => 'Khu phức hợp cao tầng', 'en' => 'High-rise Complex', 'zh' => '高层综合体' ),
		'mixed-use-cao-tang'       => array( 'vi' => 'Mixed-use cao tầng', 'en' => 'High-rise Mixed-use Complex', 'zh' => '高层综合体' ),
		'can-ho-phuc-hop'          => array( 'vi' => 'Căn hộ phức hợp', 'en' => 'Complex Apartment', 'zh' => '综合公寓' ),
		'khu-can-ho-phuc-hop'      => array( 'vi' => 'Khu căn hộ phức hợp', 'en' => 'Complex Apartment', 'zh' => '综合公寓' ),
		'khu-do-thi-da-chuc-nang'  => array( 'vi' => 'Khu đô thị đa chức năng', 'en' => 'Multi-functional Urban Area', 'zh' => '多功能城区' ),
		'trung-tam-thuong-mai'     => array( 'vi' => 'Trung tâm thương mại', 'en' => 'Commercial Center', 'zh' => '商业中心' ),
		'nhà-xuong-cho-thue'       => array( 'vi' => 'Nhà xưởng cho thuê', 'en' => 'Factories for Rent', 'zh' => '厂房出租' ),
		'nha-xuong-cho-thue'       => array( 'vi' => 'Nhà xưởng cho thuê', 'en' => 'Factories for Rent', 'zh' => '厂房出租' ),
		'cum-cong-nghiep'          => array( 'vi' => 'Cụm công nghiệp', 'en' => 'Industrial Cluster', 'zh' => '工业集群' ),
		'khu-cong-nghiep'          => array( 'vi' => 'Khu công nghiệp', 'en' => 'Industrial Park', 'zh' => '工业园区' ),
		'bat-dong-san-cong-nghiep' => array( 'vi' => 'Bất động sản công nghiệp', 'en' => 'Industrial Real Estate', 'zh' => '工业房地产' ),
		'bat-dong-san-thuong-mai'  => array( 'vi' => 'Bất động sản thương mại', 'en' => 'Commercial Real Estate', 'zh' => '商业地产' ),
		'bat-dong-san-nha-o'       => array( 'vi' => 'Bất động sản nhà ở', 'en' => 'Residential Real Estate', 'zh' => '住宅房地产' ),
		'thu-dau-mot'              => array( 'vi' => 'Thủ Dầu Một', 'en' => 'Thu Dau Mot', 'zh' => '土龙木' ),
		'bac-tan-uyen'             => array( 'vi' => 'Bắc Tân Uyên', 'en' => 'Bac Tan Uyen', 'zh' => '北新渊' ),
		'phu-giao'                 => array( 'vi' => 'Phú Giáo', 'en' => 'Phu Giao', 'zh' => '富教' ),
		'nam-tan-uyen'             => array( 'vi' => 'Nam Tân Uyên', 'en' => 'Nam Tan Uyen', 'zh' => '南新渊' ),
		'binh-duong'               => array( 'vi' => 'Bình Dương', 'en' => 'Binh Duong', 'zh' => '平阳' ),
		'ho-chi-minh'              => array( 'vi' => 'Hồ Chí Minh', 'en' => 'Ho Chi Minh City', 'zh' => '胡志明市' ),
		'dang-trien-khai'          => array( 'vi' => 'Đang triển khai', 'en' => 'Under Development', 'zh' => '开发中' ),
		'da-hoan-thanh'            => array( 'vi' => 'Đã hoàn thành', 'en' => 'Completed', 'zh' => '已完工' ),
		'sap-trien-khai'           => array( 'vi' => 'Sắp triển khai', 'en' => 'Upcoming', 'zh' => '即将推出' ),
		'dang-van-hanh'            => array( 'vi' => 'Đang vận hành', 'en' => 'In Operation', 'zh' => '运营中' ),

		'contact_sent'        => array( 'vi' => 'Cảm ơn bạn đã liên hệ. QP Holdings sẽ phản hồi trong thời gian sớm nhất.', 'en' => 'Thank you for contacting us. QP Holdings will respond as soon as possible.', 'zh' => '感谢您的联系。QP Holdings 将尽快回复。' ),
		'contact_invalid'     => array( 'vi' => 'Vui lòng nhập đầy đủ thông tin bắt buộc trước khi gửi.', 'en' => 'Please fill in all required fields before submitting.', 'zh' => '请在提交前填写所有必填项。' ),
		'contact_error'       => array( 'vi' => 'Tin nhắn chưa gửi được. Vui lòng thử lại sau hoặc liên hệ trực tiếp qua email.', 'en' => 'Message could not be sent. Please try again later or contact us directly via email.', 'zh' => '消息发送失败。请稍后再试，或直接通过电子邮件与我们联系。' ),
		'turnstile_error'     => array( 'vi' => 'Xác thực bảo mật không thành công. Vui lòng thử lại.', 'en' => 'Security verification failed. Please try again.', 'zh' => '安全验证失败，请重试。' ),
		'contact_with_us'     => array( 'vi' => 'Liên hệ với chúng tôi', 'en' => 'Contact Us', 'zh' => '联系我们' ),
		'contact_hero_desc'   => array( 'vi' => 'QP Holdings sẵn sàng chia sẻ và cung cấp thông tin với bạn', 'en' => 'QP Holdings is ready to share and provide information to you', 'zh' => 'QP Holdings 随时为您分享和提供信息' ),
		'company_info'        => array( 'vi' => 'Thông tin công ty', 'en' => 'Company Information', 'zh' => '公司信息' ),
		'contact_info_subtitle' => array( 'vi' => 'Hãy liên hệ với chúng tôi qua các thông tin sau :', 'en' => 'Please contact us via the following details:', 'zh' => '请通过以下方式与我们联系：' ),
		'head_office'         => array( 'vi' => 'Trụ sở chính', 'en' => 'Head Office', 'zh' => '总部' ),
		'binh_duong_office'   => array( 'vi' => 'Văn phòng Bình Dương', 'en' => 'Binh Duong Office', 'zh' => '平阳办公室' ),
		'email'               => array( 'vi' => 'Email', 'en' => 'Email', 'zh' => '电子邮件' ),
		'social_follow'       => array( 'vi' => 'Theo dõi các phương tiện truyền thông của chúng tôi', 'en' => 'Follow our media channels', 'zh' => '关注我们的媒体频道' ),
		'send_message_title'  => array( 'vi' => 'Gửi tin nhắn cho chúng tôi', 'en' => 'Send us a message', 'zh' => '向我们发送留言' ),
		'placeholder_email'   => array( 'vi' => 'Vui lòng nhập email', 'en' => 'Please enter your email address', 'zh' => '请输入您的电子邮件' ),
		'your_question'       => array( 'vi' => 'Câu hỏi của bạn', 'en' => 'Your inquiry topic', 'zh' => '您的咨询主题' ),
		'select_question'     => array( 'vi' => 'Vui lòng chọn câu hỏi', 'en' => 'Please select a topic', 'zh' => '请选择一个主题' ),
		'question_project'    => array( 'vi' => 'Thông tin dự án', 'en' => 'Project Information', 'zh' => '项目信息' ),
		'question_investor'   => array( 'vi' => 'Quan hệ nhà đầu tư', 'en' => 'Investor Relations', 'zh' => '投资者关系' ),
		'question_recruitment' => array( 'vi' => 'Tuyển dụng', 'en' => 'Recruitment', 'zh' => '招聘' ),
		'question_other'      => array( 'vi' => 'Khác', 'en' => 'Other', 'zh' => '其他' ),
		'comment'             => array( 'vi' => 'Bình luận', 'en' => 'Message', 'zh' => '留言内容' ),
		'placeholder_message' => array( 'vi' => 'Hãy cho chúng tôi biết bạn đang tìm kiếm thông tin gì', 'en' => 'Let us know what information you are looking for', 'zh' => '请告诉我们您正在寻找什么信息' ),
		'find_us_maps'        => array( 'vi' => 'Tìm chúng tôi trên google maps', 'en' => 'Find us on Google Maps', 'zh' => '在谷歌地图上找到我们' ),
		'map_desc'            => array( 'vi' => 'Khi hiện bản đồ ra, bạn có thể phóng to và thu nhỏ để có thể nhìn rõ hơn. Hãy thoải mái sử dụng nó để tìm đường đến chỗ chúng tôi', 'en' => 'You can zoom in and out of the map to see more clearly. Feel free to use it to find your way to our office.', 'zh' => '您可以放大和缩小地图以更清楚地查看。随时使用它来查找前往我们办公室的路线。' ),
		'map_iframe_title'    => array( 'vi' => 'QP Holdings — Tòa nhà Vietcombank, Số 5 Công Trường Mê Linh, TP. HCM', 'en' => 'QP Holdings — Vietcombank Tower, 5 Cong Truong Me Linh, HCMC', 'zh' => 'QP Holdings — 越南外贸银行大楼，Công Trường Mê Linh 5号，胡志明市' ),

		'careers_why_eyebrow' => array( 'vi' => 'TẠI SAO NÊN CHỌN QP HOLDINGS ĐỂ PHÁT TRIỂN', 'en' => 'WHY CHOOSE QP HOLDINGS FOR DEVELOPMENT', 'zh' => '为什么选择 QP HOLDINGS 进行发展' ),
		'careers_jobs_title'  => array( 'vi' => 'Các vị trí QP Holdings đang tuyển dụng', 'en' => 'Open positions at QP Holdings', 'zh' => 'QP Holdings 的在招职位' ),
		'apply_now'           => array( 'vi' => 'Ứng tuyển', 'en' => 'Apply', 'zh' => '申请' ),
		'no_jobs_available'   => array( 'vi' => 'Hiện chưa có vị trí tuyển dụng.', 'en' => 'No job positions available at the moment.', 'zh' => '目前暂无招聘职位。' ),
		'deadline_prefix'     => array( 'vi' => 'Hạn chót ', 'en' => 'Deadline ', 'zh' => '截止日期 ' ),

		'job_apply_sent'      => array( 'vi' => 'Cảm ơn bạn đã ứng tuyển. QP Holdings sẽ liên hệ sau khi hồ sơ được xem xét.', 'en' => 'Thank you for your application. QP Holdings will contact you once the profile is reviewed.', 'zh' => '感谢您的申请。QP Holdings 将在审核您的简历后与您联系。' ),
		'job_apply_invalid_file' => array( 'vi' => 'Vui lòng tải tối đa 3 tệp đúng định dạng, mỗi tệp không vượt quá 20MB.', 'en' => 'Please upload up to 3 files of the correct format, each not exceeding 20MB.', 'zh' => '请上传最多3个正确格式的文件，每个文件不超过 20MB。' ),
		'job_desc_heading'    => array( 'vi' => 'Mô tả công việc', 'en' => 'Job Description', 'zh' => '职位描述' ),
		'close_form'          => array( 'vi' => 'Đóng form ứng tuyển', 'en' => 'Close application form', 'zh' => '关闭申请表单' ),
		'apply_now_title'     => array( 'vi' => 'Ứng tuyển', 'en' => 'Apply Now', 'zh' => '立即申请' ),
		'job_app_message'     => array( 'vi' => 'Mô tả bản thân', 'en' => 'Cover letter / Self-description', 'zh' => '自我描述' ),
		'placeholder_job_message' => array( 'vi' => 'Hãy cho chúng tôi biết thông tin về bạn', 'en' => 'Please tell us about yourself', 'zh' => '请向我们介绍您自己' ),
		'upload_file'         => array( 'vi' => 'Upload file', 'en' => 'Upload file', 'zh' => '上传文件' ),
		'upload_file_note'    => array( 'vi' => '(*zip, *pdf, *doc, *docx, *png, *jpg, *jpeg <= 20MB/file in size, limit 3 files)', 'en' => '(*zip, *pdf, *doc, *docx, *png, *jpg, *jpeg <= 20MB/file in size, limit 3 files)', 'zh' => '(*zip, *pdf, *doc, *docx, *png, *jpg, *jpeg，每个文件不超过 20MB，最多 3 个文件)' ),

		/* ── Job application form — field-level validation errors ── */
		'err_name_required'        => array( 'vi' => 'Vui lòng nhập họ và tên đầy đủ của bạn.', 'en' => 'Please enter your full name.', 'zh' => '请输入您的全名。' ),
		'err_email_required'       => array( 'vi' => 'Vui lòng nhập địa chỉ email của bạn.', 'en' => 'Please enter your email address.', 'zh' => '请输入您的电子邮件地址。' ),
		'err_email_invalid'        => array( 'vi' => 'Địa chỉ email không hợp lệ. Vui lòng kiểm tra lại (ví dụ: ten@email.com).', 'en' => 'Invalid email address. Please check again (e.g. name@email.com).', 'zh' => '电子邮件地址无效。请检查后重试（例如：name@email.com）。' ),
		'err_phone_required'       => array( 'vi' => 'Vui lòng nhập số điện thoại liên hệ của bạn.', 'en' => 'Please enter your contact phone number.', 'zh' => '请输入您的联系电话号码。' ),
		'err_message_required'     => array( 'vi' => 'Vui lòng mô tả ngắn gọn về bản thân và lý do ứng tuyển.', 'en' => 'Please briefly describe yourself and why you are applying.', 'zh' => '请简要介绍您自己以及申请该职位的原因。' ),
		'err_supporting_required'  => array( 'vi' => 'Vui lòng cung cấp thông tin tài liệu hỗ trợ (bằng cấp, chứng chỉ, v.v.).', 'en' => 'Please provide supporting document details (degrees, certificates, etc.).', 'zh' => '请提供证明文件信息（学位、证书等）。' ),
		'err_cv_required'          => array( 'vi' => 'Vui lòng tải lên CV của bạn (định dạng PDF, DOC hoặc DOCX).', 'en' => 'Please upload your CV (PDF, DOC or DOCX format).', 'zh' => '请上传您的简历（PDF、DOC 或 DOCX 格式）。' ),
		'err_required'             => array( 'vi' => 'Vui lòng điền vào trường này.', 'en' => 'Please fill in this field.', 'zh' => '请填写此字段。' ),
		'err_select_required'      => array( 'vi' => 'Vui lòng chọn một tùy chọn.', 'en' => 'Please select an option.', 'zh' => '请选择一个选项。' ),
		'err_comment_required'     => array( 'vi' => 'Vui lòng nhập nội dung tin nhắn của bạn.', 'en' => 'Please enter your message.', 'zh' => '请输入您的留言内容。' ),

		'footer_logo_aria'    => array( 'vi' => 'QP Holdings – Trang chủ', 'en' => 'QP Holdings – Home', 'zh' => 'QP Holdings – 首页' ),
		'newsletter_form_aria' => array( 'vi' => 'Form đăng ký bản tin', 'en' => 'Newsletter subscription form', 'zh' => '简报订阅表单' ),
		'newsletter_placeholder' => array( 'vi' => 'Đăng ký nhận bản tin', 'en' => 'Subscribe to newsletter', 'zh' => '订阅简报' ),
		'subscribe'           => array( 'vi' => 'Đăng ký', 'en' => 'Subscribe', 'zh' => '订阅' ),
		'newsletter_sent'     => array( 'vi' => 'Cảm ơn bạn đã đăng ký nhận bản tin từ QP Holdings.', 'en' => 'Thank you for subscribing to the QP Holdings newsletter.', 'zh' => '感谢您订阅 QP Holdings 简报。' ),
		'newsletter_exists'   => array( 'vi' => 'Email này đã đăng ký nhận bản tin trước đó.', 'en' => 'This email is already subscribed.', 'zh' => '该邮箱已订阅。' ),
		'newsletter_invalid'  => array( 'vi' => 'Vui lòng nhập một địa chỉ email hợp lệ.', 'en' => 'Please enter a valid email address.', 'zh' => '请输入有效的电子邮件地址。' ),
		'newsletter_error'    => array( 'vi' => 'Đăng ký chưa gửi được. Vui lòng thử lại sau.', 'en' => 'Subscription failed. Please try again later.', 'zh' => '订阅失败。请稍后再试。' ),
		'cookie_consent_aria' => array( 'vi' => 'Thông báo sử dụng Cookie', 'en' => 'Cookie usage notice', 'zh' => 'Cookie 使用通知' ),
		'cookie_brand'        => array( 'vi' => 'QP Holding', 'en' => 'QP Holding', 'zh' => 'QP Holding' ),
		'cookie_body'         => array( 'vi' => ' sử dụng Cookie và các công nghệ tương tự để nâng cao trải nghiệm người dùng, ghi nhớ các tùy chọn truy cập, phân tích hành vi sử dụng website, đo lường hiệu quả hoạt động tiếp thị và cung cấp nội dung phù hợp với nhu cầu của khách hàng. Dữ liệu thu thập thông qua Cookie được xử lý theo Chính sách Bảo mật của chúng tôi và không ảnh hưởng đến quyền riêng tư của người dùng. Bằng việc tiếp tục sử dụng website, bạn đồng ý cho phép QP Holding lưu trữ và sử dụng Cookie theo các mục đích nêu trên.', 'en' => ' uses cookies and similar technologies to improve the user experience, remember access preferences, analyze website usage, measure marketing performance, and provide content relevant to customer needs. Data collected through cookies is processed according to our Privacy Policy and does not affect user privacy. By continuing to use the website, you agree to allow QP Holding to store and use cookies for the purposes stated above.', 'zh' => ' 使用 Cookie 及类似技术来提升用户体验、记住访问偏好、分析网站使用行为、衡量营销效果，并提供符合客户需求的内容。通过 Cookie 收集的数据将根据我们的隐私政策处理，不会影响用户隐私。继续使用本网站即表示您同意 QP Holding 按上述目的存储和使用 Cookie。' ),
		'cookie_learn_more'   => array( 'vi' => 'Tìm hiểu thêm', 'en' => 'Learn more', 'zh' => '了解更多' ),
		'cookie_reject'       => array( 'vi' => 'Từ chối', 'en' => 'Reject', 'zh' => '拒绝' ),
		'cookie_customize'    => array( 'vi' => 'Tùy chỉnh', 'en' => 'Customize', 'zh' => '自定义' ),
		'cookie_accept_all'   => array( 'vi' => 'Chấp nhận tất cả', 'en' => 'Accept all', 'zh' => '全部接受' ),
		'cookie_save_preferences' => array( 'vi' => 'Lưu tùy chọn', 'en' => 'Save preferences', 'zh' => '保存偏好' ),
		'cookie_required_title' => array( 'vi' => 'Cookie cần thiết', 'en' => 'Necessary cookies', 'zh' => '必要 Cookie' ),
		'cookie_required_desc' => array( 'vi' => 'Luôn bật để website hoạt động ổn định và bảo mật.', 'en' => 'Always enabled to keep the website stable and secure.', 'zh' => '始终启用，以确保网站稳定和安全运行。' ),
		'cookie_analytics_title' => array( 'vi' => 'Cookie phân tích', 'en' => 'Analytics cookies', 'zh' => '分析 Cookie' ),
		'cookie_analytics_desc' => array( 'vi' => 'Giúp chúng tôi hiểu cách người dùng tương tác với website.', 'en' => 'Help us understand how users interact with the website.', 'zh' => '帮助我们了解用户如何与网站互动。' ),
		'cookie_marketing_title' => array( 'vi' => 'Cookie tiếp thị', 'en' => 'Marketing cookies', 'zh' => '营销 Cookie' ),
		'cookie_marketing_desc' => array( 'vi' => 'Hỗ trợ đo lường hiệu quả tiếp thị và cá nhân hóa nội dung.', 'en' => 'Support marketing measurement and content personalization.', 'zh' => '支持营销效果衡量和内容个性化。' ),
		'about_us'            => array( 'vi' => 'Về chúng tôi', 'en' => 'About Us', 'zh' => '关于我们' ),
		'legal_regs'          => array( 'vi' => 'Pháp lý & quy định', 'en' => 'Legal & Regulations', 'zh' => '法律与法规' ),
		'contact_section_aria' => array( 'vi' => 'Thông tin liên hệ và biểu mẫu', 'en' => 'Contact information and form', 'zh' => '联系信息和表单' ),
		'news_group_corporate' => array( 'vi' => 'Tin tập đoàn', 'en' => 'Corporate News', 'zh' => '集团新闻' ),
		'news_group_corporate_desc' => array( 'vi' => 'Thông tin chính thức về hoạt động doanh nghiệp, chiến lược phát triển và cập nhật thị trường từ QP Holdings', 'en' => 'Official updates on business activities, development strategy, and market insights from QP Holdings', 'zh' => 'QP Holdings 关于企业运营、发展战略和市场动态的官方资讯' ),
		'news_group_project' => array( 'vi' => 'Tin dự án', 'en' => 'Project News', 'zh' => '项目新闻' ),
		'news_group_announcements' => array( 'vi' => 'Thông báo', 'en' => 'Announcements', 'zh' => '通知公告' ),
		'filter_by_category' => array( 'vi' => 'Lọc theo danh mục', 'en' => 'Filter by category', 'zh' => '按类别筛选' ),
		'project_content_label' => array( 'vi' => 'Nội dung dự án', 'en' => 'Project content', 'zh' => '项目内容' ),
		'truyen-thong' => array( 'vi' => 'Truyền thông', 'en' => 'Media', 'zh' => '媒体' ),
		'thong-cao' => array( 'vi' => 'Thông cáo báo chí', 'en' => 'Press Release', 'zh' => '新闻稿' ),
		'su-kien' => array( 'vi' => 'Sự kiện', 'en' => 'Events', 'zh' => '活动' ),
		'nha-dau-tu' => array( 'vi' => 'Nhà đầu tư', 'en' => 'Investors', 'zh' => '投资者' ),
		'thi-truong' => array( 'vi' => 'Thị trường', 'en' => 'Market', 'zh' => '市场' ),
		'Thị trường' => array( 'vi' => 'Thị trường', 'en' => 'Market', 'zh' => '市场' ),
		'Thị trường chứng khoán' => array( 'vi' => 'Thị trường chứng khoán', 'en' => 'Stock Market', 'zh' => '证券市场' ),
		'thị trường chứng khoán' => array( 'vi' => 'thị trường chứng khoán', 'en' => 'Stock Market', 'zh' => '证券市场' ),
		'Chứng khoán' => array( 'vi' => 'Chứng khoán', 'en' => 'Securities', 'zh' => '证券' ),
		'chứng khoán' => array( 'vi' => 'chứng khoán', 'en' => 'Securities', 'zh' => '证券' ),
		'Danh Mục tin tức' => array( 'vi' => 'Danh Mục tin tức', 'en' => 'News', 'zh' => '新闻' ),
		'Danh mục tin tức' => array( 'vi' => 'Danh mục tin tức', 'en' => 'News', 'zh' => '新闻' ),
		'danh mục tin tức' => array( 'vi' => 'danh mục tin tức', 'en' => 'News', 'zh' => '新闻' ),
		'Danh Mục Tin Tức' => array( 'vi' => 'Danh Mục Tin Tức', 'en' => 'News', 'zh' => '新闻' ),
		'Danh mục' => array( 'vi' => 'Danh mục', 'en' => 'Category', 'zh' => '分类' ),
		'danh mục' => array( 'vi' => 'danh mục', 'en' => 'Category', 'zh' => '分类' ),
		'Ban Truyền thông' => array( 'vi' => 'Ban Truyền thông', 'en' => 'Communications Department', 'zh' => '传媒部' ),
		'Ban truyền thông' => array( 'vi' => 'Ban truyền thông', 'en' => 'Communications Department', 'zh' => '传媒部' ),
		'ban truyền thông' => array( 'vi' => 'ban truyền thông', 'en' => 'Communications Department', 'zh' => '传媒部' ),
		'Ban Biên tập' => array( 'vi' => 'Ban Biên tập', 'en' => 'Editorial Board', 'zh' => '编辑部' ),
		'Ban biên tập' => array( 'vi' => 'Ban biên tập', 'en' => 'Editorial Board', 'zh' => '编辑部' ),
		'Phòng Truyền thông' => array( 'vi' => 'Phòng Truyền thông', 'en' => 'Communications Department', 'zh' => '传媒部' ),
		'phòng truyền thông' => array( 'vi' => 'phòng truyền thông', 'en' => 'Communications Department', 'zh' => '传媒部' ),
		'Nhà đầu tư' => array( 'vi' => 'Nhà đầu tư', 'en' => 'Investors', 'zh' => '投资者' ),
		'Truyền thông' => array( 'vi' => 'Truyền thông', 'en' => 'Media', 'zh' => '媒体' ),
		'Thông báo' => array( 'vi' => 'Thông báo', 'en' => 'Announcements', 'zh' => '公告' ),
		'kinh-te' => array( 'vi' => 'Kinh tế', 'en' => 'Economy', 'zh' => '经济' ),
		'Kinh tế' => array( 'vi' => 'Kinh tế', 'en' => 'Economy', 'zh' => '经济' ),
		'cong-nghe' => array( 'vi' => 'Công nghệ', 'en' => 'Technology', 'zh' => '科技' ),
		'Công nghệ' => array( 'vi' => 'Công nghệ', 'en' => 'Technology', 'zh' => '科技' ),
		'nang-luong' => array( 'vi' => 'Năng lượng', 'en' => 'Energy', 'zh' => '能源' ),
		'Năng lượng' => array( 'vi' => 'Năng lượng', 'en' => 'Energy', 'zh' => '能源' ),
		'moi-truong' => array( 'vi' => 'Môi trường', 'en' => 'Environment', 'zh' => '环境' ),
		'Môi trường' => array( 'vi' => 'Môi trường', 'en' => 'Environment', 'zh' => '环境' ),
		'chinh-sach' => array( 'vi' => 'Chính sách', 'en' => 'Policy', 'zh' => '政策' ),
		'Chính sách' => array( 'vi' => 'Chính sách', 'en' => 'Policy', 'zh' => '政策' ),
		'bat-dong-san' => array( 'vi' => 'Bất động sản', 'en' => 'Real Estate', 'zh' => '房地产' ),
		'Bất động sản' => array( 'vi' => 'Bất động sản', 'en' => 'Real Estate', 'zh' => '房地产' ),
		'tai-chinh' => array( 'vi' => 'Tài chính', 'en' => 'Finance', 'zh' => '金融' ),
		'Tài chính' => array( 'vi' => 'Tài chính', 'en' => 'Finance', 'zh' => '金融' ),
		'tin-tuc' => array( 'vi' => 'Tin tức', 'en' => 'News', 'zh' => '新闻' ),
		'Tin tức' => array( 'vi' => 'Tin tức', 'en' => 'News', 'zh' => '新闻' ),
		'do-thi' => array( 'vi' => 'Đô thị', 'en' => 'Urban', 'zh' => '都市' ),
		'Đô thị' => array( 'vi' => 'Đô thị', 'en' => 'Urban', 'zh' => '都市' ),
		'khu-dan-cu' => array( 'vi' => 'Khu dân cư', 'en' => 'Residential', 'zh' => '住宅' ),
		'khu-phuc-hop' => array( 'vi' => 'Khu phức hợp', 'en' => 'Mixed-use', 'zh' => '综合体' ),
		'van-phong' => array( 'vi' => 'Bất động sản văn phòng', 'en' => 'Office', 'zh' => '写字楼' ),
		'khu-cong-nghiep' => array( 'vi' => 'Khu công nghiệp', 'en' => 'Industrial', 'zh' => '工业区' ),
		'nang-luong' => array( 'vi' => 'Năng lượng', 'en' => 'Energy', 'zh' => '能源' ),
		'logistics' => array( 'vi' => 'Logistics', 'en' => 'Logistics', 'zh' => '物流' ),
		'nghi-duong' => array( 'vi' => 'Bất động sản nghỉ dưỡng', 'en' => 'Resort', 'zh' => '度假' ),
		'trung-tam-thuong-mai' => array( 'vi' => 'Trung tâm thương mại', 'en' => 'Retail / Mall', 'zh' => '商业中心' ),
		'da-ban-giao' => array( 'vi' => 'Đã bàn giao', 'en' => 'Completed', 'zh' => '已交付' ),
		'dang-trien-khai' => array( 'vi' => 'Đang triển khai', 'en' => 'Under Construction', 'zh' => '建设中' ),
		'sap-ra-mat' => array( 'vi' => 'Sắp ra mắt', 'en' => 'Upcoming', 'zh' => '即将推出' ),
		'quy-hoach' => array( 'vi' => 'Quy hoạch', 'en' => 'Planning', 'zh' => '规划中' ),
		'tp-hcm' => array( 'vi' => 'TP. Hồ Chí Minh', 'en' => 'Ho Chi Minh City', 'zh' => '胡志明市' ),
		'binh-duong' => array( 'vi' => 'Bình Dương', 'en' => 'Binh Duong', 'zh' => '平阳' ),
		'long-an' => array( 'vi' => 'Long An', 'en' => 'Long An', 'zh' => '隆安' ),
		'dong-nai' => array( 'vi' => 'Đồng Nai', 'en' => 'Dong Nai', 'zh' => '同奈' ),
		'ba-ria-vt' => array( 'vi' => 'Bà Rịa - Vũng Tàu', 'en' => 'Ba Ria - Vung Tau', 'zh' => '巴地头顿' ),
		'ninh-thuan' => array( 'vi' => 'Ninh Thuận', 'en' => 'Ninh Thuan', 'zh' => '宁顺' ),
		'ha-noi' => array( 'vi' => 'Hà Nội', 'en' => 'Ha Noi', 'zh' => '河内' ),
		'khoi-dau-tu' => array( 'vi' => 'Khối Đầu tư', 'en' => 'Investment Division', 'zh' => '投资部' ),
		'khoi-du-an' => array( 'vi' => 'Khối Dự án', 'en' => 'Project Division', 'zh' => '项目部' ),
		'khoi-tai-chinh' => array( 'vi' => 'Khối Tài chính', 'en' => 'Finance Division', 'zh' => '财务部' ),
		'khoi-phap-che' => array( 'vi' => 'Khối Pháp chế', 'en' => 'Legal Division', 'zh' => '法务部' ),
		'khoi-nhan-su' => array( 'vi' => 'Khối Nhân sự', 'en' => 'HR Division', 'zh' => '人事部' ),
		'khoi-van-phong' => array( 'vi' => 'Khối Văn phòng', 'en' => 'Office Division', 'zh' => '行政办公部' ),
		'khoi-marketing' => array( 'vi' => 'Khối Marketing', 'en' => 'Marketing Division', 'zh' => '市场营销部' ),
		'khoi-kinh-doanh' => array( 'vi' => 'Khối Kinh doanh', 'en' => 'Business Division', 'zh' => '业务部' ),
		'intern' => array( 'vi' => 'Thực tập sinh', 'en' => 'Intern', 'zh' => '实习生' ),
		'junior' => array( 'vi' => 'Junior', 'en' => 'Junior', 'zh' => '初级' ),
		'mid' => array( 'vi' => 'Trung cấp', 'en' => 'Mid-level', 'zh' => '中级' ),
		'senior' => array( 'vi' => 'Senior', 'en' => 'Senior', 'zh' => '高级' ),
		'lead' => array( 'vi' => 'Trưởng nhóm / Quản lý', 'en' => 'Team Lead / Manager', 'zh' => '组长 / 经理' ),
		'select_language' => array( 'vi' => 'Chọn ngôn ngữ', 'en' => 'Select language', 'zh' => '选择语言' ),
		'pagination' => array( 'vi' => 'Phân trang', 'en' => 'Pagination', 'zh' => '分页导航' ),
		'learn_more' => array( 'vi' => 'Tìm hiểu thêm', 'en' => 'Learn more', 'zh' => '了解更多' ),
		'learn_more_about' => array( 'vi' => 'Tìm hiểu thêm về QP Holdings', 'en' => 'Learn more about QP Holdings', 'zh' => '了解更多关于 QP Holdings' ),
		'learn_more_commitment' => array( 'vi' => 'Tìm hiểu thêm về cam kết của QP Holdings', 'en' => 'Learn more about QP Holdings commitments', 'zh' => '了解更多关于 QP Holdings 的承诺' ),
	);

	return $dict;
}

/**
 * Default (non-overridden) translation for a key. Used by the inline editor so
 * it can decide whether a stored override differs from the canonical default.
 *
 * @param string      $key  Translation key.
 * @param string|null $lang Language code; defaults to current.
 * @return string
 */
function qp_holdings_t_default( string $key, ?string $lang = null ): string {
	$lang = $lang ?: qp_holdings_current_language();
	$dict = qp_holdings_translation_dict();
	$result = $dict[ $key ][ $lang ] ?? $dict[ $key ]['vi'] ?? null;
	if ( $result !== null ) {
		return $result;
	}
	return qp_holdings_static_t( $key, $lang );
}

/**
 * Whether the default value for a key is only available as a Vietnamese
 * fallback for the requested language (i.e. no real translation exists yet).
 *
 * @param string      $key  Translation key.
 * @param string|null $lang Language code; defaults to current.
 * @return bool
 */
function qp_holdings_t_is_fallback( string $key, ?string $lang = null ): bool {
	$lang = $lang ?: qp_holdings_current_language();
	if ( 'vi' === $lang ) {
		return false;
	}
	$dict = qp_holdings_translation_dict();
	return ! isset( $dict[ $key ][ $lang ] ) && isset( $dict[ $key ]['vi'] );
}

function qp_holdings_t( string $key, string $fallback_en = '', string $fallback_zh = '' ): string {
	$lang = qp_holdings_current_language();

	$overrides = qp_holdings_text_overrides( $lang );
	if ( isset( $overrides[ $key ] ) ) {
		return $overrides[ $key ];
	}

	$dict = qp_holdings_translation_dict();
	if ( isset( $dict[ $key ][ $lang ] ) ) {
		return $dict[ $key ][ $lang ];
	}

	if ( '' !== $fallback_en || '' !== $fallback_zh ) {
		if ( 'en' === $lang && '' !== $fallback_en ) {
			return $fallback_en;
		}
		if ( 'zh' === $lang && '' !== $fallback_zh ) {
			return $fallback_zh;
		}
		if ( 'vi' === $lang ) {
			return $key;
		}
	}

	return qp_holdings_t_default( $key, $lang );
}

function qp_holdings_page_url( string $key, ?string $lang = null ): string {
	$lang = $lang ?: qp_holdings_current_language();
	$defs = qp_holdings_page_definitions();
	if ( ! isset( $defs[ $key ][ $lang ] ) ) {
		return home_url( '/' );
	}

	if ( ! empty( $defs[ $key ][ $lang ]['url'] ) ) {
		return $defs[ $key ][ $lang ]['url'];
	}

	// For newsletter, if current language URL is empty but VI has a custom URL
	if ( 'newsletter' === $key && empty( $defs[ $key ][ $lang ]['url'] ) && ! empty( $defs[ $key ]['vi']['url'] ) ) {
		$vi_url = $defs[ $key ]['vi']['url'];
		if ( 'vi' === $lang ) {
			return $vi_url;
		}
		// Try resolving translated post if vi_url points to an internal post
		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
		$url_host  = wp_parse_url( $vi_url, PHP_URL_HOST );
		if ( $site_host && $url_host && strtolower( $site_host ) === strtolower( $url_host ) ) {
			$post_id = function_exists( 'url_to_postid' ) ? (int) url_to_postid( $vi_url ) : 0;
			if ( $post_id ) {
				if ( function_exists( 'pll_get_post' ) ) {
					$trans_id = (int) pll_get_post( $post_id, $lang );
					if ( $trans_id && 'publish' === get_post_status( $trans_id ) ) {
						$trans_link = get_permalink( $trans_id );
						if ( $trans_link ) {
							return $trans_link;
						}
					}
				}
				// Return the VI article with ?qp_lang parameter instead of a 404 page
				return add_query_arg( 'qp_lang', $lang, $vi_url );
			}
		}
	}

	if ( 'home' === $key ) {
		if ( function_exists( 'pll_home_url' ) ) {
			$url = pll_home_url( $lang );
			if ( is_string( $url ) && $url ) {
				return $url;
			}
		}

		return 'vi' === $lang ? home_url( '/' ) : home_url( '/' . $lang . '/' );
	}

	$path = $defs[ $key ][ $lang ]['slug'];
	if ( ! empty( $defs[ $key ]['parent'] ) ) {
		$parent_key = $defs[ $key ]['parent'];
		$path = $defs[ $parent_key ][ $lang ]['slug'] . '/' . $path;
	}

	if ( 'vi' !== $lang ) {
		$path = $lang . '/' . $path;
	}

	return home_url( '/' . trim( $path, '/' ) . '/' );
}

function qp_holdings_current_page_key(): string {
	$defs = qp_holdings_page_definitions();

	if ( is_front_page() || is_home() ) {
		return 'home';
	}

	if ( is_page_template( 'page-ban-tin.php' ) || is_page_template( 'page-newsletter.php' ) || is_page_template( 'page-ban-tin-nha-dau-tu-v2.php' ) ) {
		return 'newsletter';
	}
	if ( is_page_template( 'page-thong-tin-co-phieu.php' ) ) {
		return 'stock';
	}
	if ( is_page_template( 'page-quan-tri-cong-ty.php' ) ) {
		return 'governance';
	}
	if ( is_page_template( 'page-dai-hoi-dong-co-dong.php' ) ) {
		return 'agm';
	}
	if ( is_page_template( 'page-bao-cao-thuong-nien.php' ) ) {
		return 'annual_reports';
	}
	if ( is_page_template( 'page-bao-cao-tai-chinh.php' ) ) {
		return 'financial_reports';
	}
	if ( is_page_template( 'page-bao-cao-quan-tri-cong-ty.php' ) ) {
		return 'governance_reports';
	}
	if ( is_page_template( 'page-cong-bo-thong-tin.php' ) ) {
		return 'disclosures';
	}
	if ( is_page_template( 'page-nha-dau-tu.php' ) ) {
		return 'investor';
	}
	if ( is_page_template( 'page-du-an.php' ) || is_singular( 'qp_project' ) ) {
		return 'projects';
	}
	if ( is_page_template( 'page-tintuc.php' ) || is_singular( 'qp_news' ) ) {
		return 'news';
	}
	if ( is_page_template( 'page-tuyen-dung.php' ) || is_singular( 'qp_job' ) ) {
		return 'careers';
	}
	if ( is_page_template( 'page-gioi-thieu.php' ) ) {
		return 'about';
	}
	if ( is_page_template( 'page-cau-hoi-thuong-gap.php' ) ) {
		return 'faq';
	}
	if ( is_page_template( 'page-lien-he.php' ) ) {
		return 'contact';
	}
	if ( is_page_template( 'page-dieu-khoan-su-dung.php' ) ) {
		return 'terms';
	}
	if ( is_page_template( 'page-chinh-sach-bao-mat.php' ) ) {
		return 'privacy';
	}

	if ( is_singular( 'qp_investor_doc' ) ) {
		$object_id = get_queried_object_id();
		$terms = wp_get_post_terms( $object_id, 'qp_investor_section' );
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$sec_slug = $terms[0]->slug;
			$sec_map = array(
				'ban-tin'            => 'newsletter',
				'ban-tin-nha-dau-tu' => 'newsletter',
				'investor-news'      => 'newsletter',
				'company-governance' => 'governance',
				'agm'                => 'agm',
				'annual-report'      => 'annual_reports',
				'financial-report'   => 'financial_reports',
				'governance-report'  => 'governance_reports',
				'disclosure'         => 'disclosures',
				'stock-info'         => 'stock',
			);
			if ( isset( $sec_map[ $sec_slug ] ) ) {
				return $sec_map[ $sec_slug ];
			}
		}
		return 'newsletter';
	}

	$object_id = get_queried_object_id();
	if ( $object_id ) {
		$slug = (string) get_post_field( 'post_name', $object_id );
		$parent_id = (int) wp_get_post_parent_id( $object_id );
		$parent_slug = $parent_id ? (string) get_post_field( 'post_name', $parent_id ) : '';

		foreach ( $defs as $key => $def ) {
			foreach ( array( 'vi', 'en', 'zh' ) as $lang ) {
				if ( empty( $def[ $lang ]['slug'] ) || $slug !== $def[ $lang ]['slug'] ) {
					continue;
				}

				if ( empty( $def['parent'] ) ) {
					return $key;
				}

				$parent_key = $def['parent'];
				if ( $parent_slug === ( $defs[ $parent_key ][ $lang ]['slug'] ?? '' ) ) {
					return $key;
				}
			}
		}
	}

	$path = trim( (string) wp_parse_url( add_query_arg( array() ), PHP_URL_PATH ), '/' );
	foreach ( $defs as $key => $def ) {
		foreach ( array( 'vi', 'en', 'zh' ) as $lang ) {
			$page_path = $def[ $lang ]['slug'] ?? '';
			if ( ! empty( $def['parent'] ) ) {
				$parent_key = $def['parent'];
				$page_path = ( $defs[ $parent_key ][ $lang ]['slug'] ?? '' ) . '/' . $page_path;
			}
			$page_path = trim( $page_path, '/' );
			if ( $page_path && ( $path === $page_path || $path === $lang . '/' . $page_path ) ) {
				return $key;
			}
		}
	}

	return 'home';
}

/**
 * Return the URL of the same page in another language.
 *
 * Looks up the current page key (via qp_holdings_current_page_key()) then
 * resolves the equivalent slug/path in the target language. For single posts
 * of translatable CPTs we ask Polylang for the translation when available.
 */
function qp_holdings_language_switch_url( string $lang ): string {
	if ( ! in_array( $lang, array( 'vi', 'en', 'zh' ), true ) ) {
		return home_url( '/' );
	}

	// 0. Check if current URL/page matches configured Custom Menu Link (Cài đặt -> Menu tùy chỉnh).
	if ( function_exists( 'qp_holdings_custom_menu_get' ) ) {
		$custom_menu = qp_holdings_custom_menu_get();
		if ( ! empty( $custom_menu['enabled'] ) && ! empty( $custom_menu['url'] ) ) {
			$custom_urls = array(
				'vi' => (string) $custom_menu['url'],
				'en' => ! empty( $custom_menu['url_en'] ) ? (string) $custom_menu['url_en'] : (string) $custom_menu['url'],
				'zh' => ! empty( $custom_menu['url_zh'] ) ? (string) $custom_menu['url_zh'] : (string) $custom_menu['url'],
			);

			if ( ! empty( $_SERVER['HTTP_HOST'] ) && ! empty( $_SERVER['REQUEST_URI'] ) ) {
				$current_request_url = ( ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] ) ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			} else {
				$raw_uri = function_exists( 'add_query_arg' ) ? add_query_arg( array() ) : '';
				if ( 0 === strpos( $raw_uri, 'http://' ) || 0 === strpos( $raw_uri, 'https://' ) ) {
					$current_request_url = $raw_uri;
				} else {
					$current_request_url = home_url( $raw_uri );
				}
			}
			$current_path      = trim( (string) wp_parse_url( $current_request_url, PHP_URL_PATH ), '/' );
			$current_page_id   = (int) get_queried_object_id();
			$current_page_slug = $current_page_id ? (string) get_post_field( 'post_name', $current_page_id ) : '';
			$current_page_link = $current_page_id ? (string) get_permalink( $current_page_id ) : '';

			$is_matched = false;
			foreach ( array( 'vi', 'en', 'zh' ) as $l_key ) {
				$check_url = trim( $custom_urls[ $l_key ] );
				if ( '' === $check_url ) {
					continue;
				}

				// 1. Direct URL match (ignoring http/https, host difference if localhost/domain, and trailing slash)
				$clean_check   = preg_replace( '#^https?://[^/]+#i', '', untrailingslashit( strtolower( $check_url ) ) );
				$clean_current = preg_replace( '#^https?://[^/]+#i', '', untrailingslashit( strtolower( $current_request_url ) ) );
				if ( '' !== $clean_check && $clean_check === $clean_current ) {
					$is_matched = true;
					break;
				}

				// Full URL match
				$full_clean_check   = preg_replace( '#^https?://#i', '//', untrailingslashit( strtolower( $check_url ) ) );
				$full_clean_current = preg_replace( '#^https?://#i', '//', untrailingslashit( strtolower( $current_request_url ) ) );
				if ( $full_clean_check === $full_clean_current ) {
					$is_matched = true;
					break;
				}

				// 2. Direct match with current singular page permalink
				if ( $current_page_link ) {
					$clean_page_link = preg_replace( '#^https?://[^/]+#i', '', untrailingslashit( strtolower( $current_page_link ) ) );
					if ( '' !== $clean_check && $clean_check === $clean_page_link ) {
						$is_matched = true;
						break;
					}
					$full_clean_page_link = preg_replace( '#^https?://#i', '//', untrailingslashit( strtolower( $current_page_link ) ) );
					if ( $full_clean_check === $full_clean_page_link ) {
						$is_matched = true;
						break;
					}
				}

				// 3. Path comparison (e.g. "ho-so-doanh-nghiep" or "corporate-profile")
				$check_path = trim( (string) wp_parse_url( $check_url, PHP_URL_PATH ), '/' );
				if ( '' !== $check_path ) {
					if ( strtolower( $check_path ) === strtolower( $current_path ) || untrailingslashit( strtolower( $check_path ) ) === untrailingslashit( strtolower( $current_path ) ) ) {
						$is_matched = true;
						break;
					}
					if ( $current_page_slug && ( strtolower( $check_path ) === strtolower( $current_page_slug ) || strtolower( $check_path ) === strtolower( 'en/' . $current_page_slug ) || strtolower( $check_path ) === strtolower( 'zh/' . $current_page_slug ) ) ) {
						$is_matched = true;
						break;
					}
				}
			}

			if ( $is_matched && ! empty( $custom_urls[ $lang ] ) ) {
				$target_url = trim( $custom_urls[ $lang ] );
				if ( 0 !== strpos( $target_url, 'http://' ) && 0 !== strpos( $target_url, 'https://' ) && 0 !== strpos( $target_url, '//' ) ) {
					$target_url = home_url( '/' . ltrim( $target_url, '/' ) );
				}
				return $target_url;
			}
		}
	}

	// Single posts: try Polylang translation first, then meta / sibling post lookup, fall back to current post with qp_lang.
	if ( is_singular( array( 'qp_news', 'qp_project', 'qp_job', 'qp_investor_doc', 'post' ) ) ) {
		$post_id = get_queried_object_id();
		if ( ! $post_id && function_exists( 'get_the_ID' ) ) {
			$post_id = (int) get_the_ID();
		}
		if ( $post_id ) {
			if ( function_exists( 'pll_get_post' ) ) {
				$translated = (int) pll_get_post( $post_id, $lang );
				if ( $translated && 'publish' === get_post_status( $translated ) ) {
					$GLOBALS['qp_holdings_skip_translated_permalink'] = true;
					$permalink = get_permalink( $translated );
					unset( $GLOBALS['qp_holdings_skip_translated_permalink'] );
					if ( $permalink ) {
						return remove_query_arg( 'qp_lang', $permalink );
					}
				}
			}

			// Check meta-based translation links (_qp_source_slug or _qp_translation_lang)
			$post_type = get_post_type( $post_id );
			$post_obj  = get_post( $post_id );
			if ( $post_obj ) {
				$curr_slug = (string) $post_obj->post_name;
				$curr_lang = (string) get_post_meta( $post_id, '_qp_translation_lang', true );
				if ( ! $curr_lang ) {
					if ( preg_match( '/^(?:en|zh)-/i', $curr_slug, $m ) ) {
						$curr_lang = strtolower( trim( $m[0], '-' ) );
					} elseif ( preg_match( '/-(?:en|zh)$/i', $curr_slug, $m ) ) {
						$curr_lang = strtolower( trim( $m[0], '-' ) );
					} else {
						$curr_lang = 'vi';
					}
				}

				if ( $curr_lang === $lang ) {
					$GLOBALS['qp_holdings_skip_translated_permalink'] = true;
					$permalink = get_permalink( $post_id );
					unset( $GLOBALS['qp_holdings_skip_translated_permalink'] );
					if ( $permalink ) {
						return remove_query_arg( 'qp_lang', $permalink );
					}
				}

				$source_slug = (string) get_post_meta( $post_id, '_qp_source_slug', true );
				if ( ! $source_slug ) {
					$source_slug = preg_replace( '/^(en|zh)-|-(en|zh)$/', '', $curr_slug );
				}

				if ( 'vi' === $lang ) {
					$target_post = get_page_by_path( $source_slug, OBJECT, $post_type );
					if ( $target_post instanceof WP_Post && 'publish' === $target_post->post_status ) {
						$GLOBALS['qp_holdings_skip_translated_permalink'] = true;
						$permalink = get_permalink( $target_post->ID );
						unset( $GLOBALS['qp_holdings_skip_translated_permalink'] );
						if ( $permalink ) {
							return remove_query_arg( 'qp_lang', $permalink );
						}
					}
				} else {
					// Target is 'en' or 'zh'
					$candidates = array(
						$lang . '-' . $source_slug,
						$source_slug . '-' . $lang,
						$source_slug . '_' . $lang,
					);
					foreach ( $candidates as $cand_slug ) {
						$target_post = get_page_by_path( $cand_slug, OBJECT, $post_type );
						if ( $target_post instanceof WP_Post && 'publish' === $target_post->post_status ) {
							$GLOBALS['qp_holdings_skip_translated_permalink'] = true;
							$permalink = get_permalink( $target_post->ID );
							unset( $GLOBALS['qp_holdings_skip_translated_permalink'] );
							if ( $permalink ) {
								return remove_query_arg( 'qp_lang', $permalink );
							}
						}
					}

					// Also try finding by meta _qp_source_slug + _qp_translation_lang
					$matches = get_posts( array(
						'post_type'      => $post_type,
						'post_status'    => 'publish',
						'posts_per_page' => 1,
						'meta_query'     => array(
							array(
								'key'   => '_qp_source_slug',
								'value' => $source_slug,
							),
							array(
								'key'   => '_qp_translation_lang',
								'value' => $lang,
							),
						),
					) );
					if ( ! empty( $matches ) ) {
						$GLOBALS['qp_holdings_skip_translated_permalink'] = true;
						$permalink = get_permalink( $matches[0]->ID );
						unset( $GLOBALS['qp_holdings_skip_translated_permalink'] );
						if ( $permalink ) {
							return remove_query_arg( 'qp_lang', $permalink );
						}
					}
				}

				// If post type is qp_investor_doc, check other posts in the same section for $lang
				if ( 'qp_investor_doc' === $post_type ) {
					$terms = wp_get_post_terms( $post_id, 'qp_investor_section' );
					if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
						$sec_term = $terms[0]->slug;
						$section_posts = get_posts( array(
							'post_type'      => 'qp_investor_doc',
							'post_status'    => 'publish',
							'posts_per_page' => 10,
							'tax_query'      => array(
								array(
									'taxonomy' => 'qp_investor_section',
									'field'    => 'slug',
									'terms'    => $sec_term,
								),
							),
						) );
						foreach ( $section_posts as $sp ) {
							if ( (int) $sp->ID === (int) $post_id ) {
								continue;
							}
							$p_lang = (string) get_post_meta( $sp->ID, '_qp_translation_lang', true );
							if ( ! $p_lang ) {
								if ( preg_match( '/^(?:en|zh)-/i', $sp->post_name, $m ) ) {
									$p_lang = strtolower( trim( $m[0], '-' ) );
								} elseif ( preg_match( '/-(?:en|zh)$/i', $sp->post_name, $m ) ) {
									$p_lang = strtolower( trim( $m[0], '-' ) );
								} else {
									$p_lang = 'vi';
								}
							}
							if ( $p_lang === $lang ) {
								$GLOBALS['qp_holdings_skip_translated_permalink'] = true;
								$permalink = get_permalink( $sp->ID );
								unset( $GLOBALS['qp_holdings_skip_translated_permalink'] );
								if ( $permalink ) {
									return remove_query_arg( 'qp_lang', $permalink );
								}
							}
						}
					}
				}
			}
		}

		// Fallback: stay on current post using proper language-prefixed CPT URL
		$post_type_obj = get_post_type_object( $post_type );
		$slug_prefix   = '';
		if ( $post_type_obj && ! empty( $post_type_obj->rewrite['slug'] ) ) {
			$slug_prefix = trim( (string) $post_type_obj->rewrite['slug'], '/' );
		}
		if ( ! $slug_prefix ) {
			$cpt_slugs = array(
				'qp_project'      => 'du-an-cpt',
				'qp_news'         => 'tin-tuc',
				'qp_job'          => 'tuyen-dung-cpt',
				'qp_investor_doc' => 'tai-lieu-nha-dau-tu',
			);
			$slug_prefix = $cpt_slugs[ $post_type ] ?? '';
		}

		$post_name = (string) get_post_field( 'post_name', $post_id );
		if ( $slug_prefix && $post_name ) {
			return 'vi' === $lang
				? home_url( '/' . $slug_prefix . '/' . $post_name . '/' )
				: home_url( '/' . $lang . '/' . $slug_prefix . '/' . $post_name . '/' );
		}

		$GLOBALS['qp_holdings_skip_translated_permalink'] = true;
		$current = get_permalink( $post_id );
		unset( $GLOBALS['qp_holdings_skip_translated_permalink'] );
		if ( $current ) {
			$current = remove_query_arg( 'qp_lang', $current );
			return 'vi' === $lang ? $current : add_query_arg( 'qp_lang', $lang, $current );
		}

		// Last resort only if the permalink could not be resolved.
		$key = 'news';
		if ( is_singular( 'qp_project' ) ) {
			$key = 'projects';
		} elseif ( is_singular( 'qp_job' ) ) {
			$key = 'careers';
		} elseif ( is_singular( 'qp_investor_doc' ) ) {
			$key = 'newsletter';
		}
		return qp_holdings_page_url( $key, $lang );
	}

	// Dynamic Header menu slot page: switch directly between mapped language versions.
	if ( is_singular( 'page' ) ) {
		$current_id = (int) get_queried_object_id();

		if ( function_exists( 'qp_holdings_header_menu_slot_group_map' ) ) {
			$slot_group = qp_holdings_header_menu_slot_group_map();
			$slot_ids   = array_filter( array_values( $slot_group ) );
			if ( in_array( $current_id, $slot_ids, true ) ) {
				$target_id = ! empty( $slot_group[ $lang ] ) ? (int) $slot_group[ $lang ] : 0;
				if ( ! $target_id && 'vi' === $lang && ! empty( $slot_group['vi'] ) ) {
					$target_id = (int) $slot_group['vi'];
				}
				if ( $target_id && 'publish' === get_post_status( $target_id ) ) {
					$permalink = get_permalink( $target_id );
					if ( $permalink ) {
						return $permalink;
					}
				}
			}
		}

		if ( function_exists( 'pll_get_post' ) ) {
			$translated = (int) pll_get_post( $current_id, $lang );
			if ( $translated && 'publish' === get_post_status( $translated ) ) {
				$permalink = get_permalink( $translated );
				if ( $permalink ) {
					return $permalink;
				}
			}
		}
	}

	$key = qp_holdings_current_page_key();
	$url = qp_holdings_page_url( $key, $lang );

	// Preserve simple query strings (?cat=, ?paged=, ?type=, …) when switching.
	$preserve = array( 'cat', 'type', 'loc', 'status', 'paged', 'dept', 'level', 's' );
	$kept = array();
	foreach ( $preserve as $q ) {
		$val = isset( $_GET[ $q ] ) ? sanitize_text_field( wp_unslash( $_GET[ $q ] ) ) : '';
		if ( $val !== '' ) {
			$kept[ $q ] = $val;
		}
	}
	if ( $kept ) {
		$url = add_query_arg( $kept, $url );
	}

	return $url;
}

function qp_holdings_language_switcher(): string {
	$current = qp_holdings_current_language();
	$langs   = qp_holdings_supported_languages();
	$out     = '<li class="has-dropdown header-language-dropdown">';
	$out    .= '<a href="#" class="header-language-dropdown__link nav-top-link" aria-expanded="false" aria-controls="ux-language-dropdown" aria-haspopup="menu">';
	$out    .= esc_html( $langs[ $current ]['short'] ?? 'VN' );
	$out    .= ' <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left: 2px; margin-top: -2px; display: inline-block; vertical-align: middle;"><path d="m6 9 6 6 6-6"></path></svg></a>';
	$out    .= '<ul id="ux-language-dropdown" class="nav-dropdown nav-dropdown-default" role="menu">';

	foreach ( $langs as $code => $data ) {
		$url = qp_holdings_language_switch_url( $code );
		$out .= '<li' . ( $code === $current ? ' class="active"' : '' ) . '><a href="' . esc_url( $url ) . '" hreflang="' . esc_attr( $code ) . '" role="menuitem">' . esc_html( $data['short'] ) . '</a></li>';
	}
	$out .= '</ul></li>';

	return $out;
}

function qp_holdings_static_translation_map( string $lang ): array {
	if ( 'vi' === $lang ) {
		return array();
	}

	$common = array(
		'Trang chủ' => array( 'en' => 'Home', 'zh' => '首页' ),
		'Giới thiệu' => array( 'en' => 'About', 'zh' => '关于我们' ),
		'Dự án' => array( 'en' => 'Projects', 'zh' => '项目' ),
		'Quan hệ nhà đầu tư' => array( 'en' => 'Investor Relations', 'zh' => '投资者关系' ),
		'Thông tin cổ phiếu' => array( 'en' => 'Stock Information', 'zh' => '股票信息' ),
		'Quản trị công ty' => array( 'en' => 'Corporate Governance', 'zh' => '公司治理' ),
		'Đại hội đồng cổ đông' => array( 'en' => 'General Meeting of Shareholders', 'zh' => '股东大会' ),
		'Báo cáo thường niên' => array( 'en' => 'Annual Reports', 'zh' => '年度报告' ),
		'Báo cáo tài chính' => array( 'en' => 'Financial Reports', 'zh' => '财务报告' ),
		'Báo cáo quản trị công ty' => array( 'en' => 'Corporate Governance Reports', 'zh' => '公司治理报告' ),
		'Công bố thông tin' => array( 'en' => 'Disclosures', 'zh' => '信息披露' ),
		'Tin tức' => array( 'en' => 'News', 'zh' => '新闻' ),
		'Tuyển dụng' => array( 'en' => 'Careers', 'zh' => '招聘' ),
		'Liên hệ' => array( 'en' => 'Contact', 'zh' => '联系我们' ),
		'MÃ CK: HKT' => array( 'en' => 'Ticker: HKT', 'zh' => '股票代码：HKT' ),
		'Đọc tiếp →' => array( 'en' => 'Read more →', 'zh' => '继续阅读 →' ),
		'Tất cả' => array( 'en' => 'All', 'zh' => '全部' ),
		'Điều khoản' => array( 'en' => 'Terms', 'zh' => '条款' ),
		'Chính sách bảo mật' => array( 'en' => 'Privacy Policy', 'zh' => '隐私政策' ),
		'Tầng L16, Tòa nhà Vietcombank, Số 5 Công Trường Mê Linh, Phường Sài Gòn, Ho Chi Minh City, Vietnam' => array( 'en' => 'Level L16, Vietcombank Tower, 5 Cong Truong Me Linh, Sai Gon Ward, Ho Chi Minh City, Vietnam', 'zh' => 'L16层，Vietcombank Tower，Công Trường Mê Linh 5号，Sài Gòn坊，胡志明市，越南' ),
		'Tầng L16, Tòa nhà Vietcombank, Số 5 Công Trường Mê Linh, Phường Sài Gòn, 胡志明市, 越南' => array( 'en' => 'Level L16, Vietcombank Tower, 5 Cong Truong Me Linh, Sai Gon Ward, Ho Chi Minh City, Vietnam', 'zh' => 'L16层，Vietcombank Tower，Công Trường Mê Linh 5号，Sài Gòn坊，胡志明市，越南' ),
		'Trang Chủ – QP Holdings' => array( 'en' => 'Home – QP Holdings', 'zh' => '首页 – QP Holdings' ),
		'Đại hội cổ đông' => array( 'en' => 'General Meeting of Shareholders', 'zh' => '股东大会' ),
		'Danh mục' => array( 'en' => 'Category', 'zh' => '分类' ),
		'Ngày đăng' => array( 'en' => 'Published', 'zh' => '发布日期' ),
		'Chuyên mục' => array( 'en' => 'Section', 'zh' => '栏目' ),
		'Chia sẻ' => array( 'en' => 'Share', 'zh' => '分享' ),
		'Tiếp tục khám phá' => array( 'en' => 'Continue exploring', 'zh' => '继续探索' ),
		'Các cập nhật nổi bật từ QP Holdings cùng chủ đề, được sắp xếp để giữ nhịp đọc liền mạch.' => array( 'en' => 'Featured updates from QP Holdings on the same topic, curated to keep your reading journey seamless.', 'zh' => '围绕同一主题精选的 QP Holdings 最新动态，帮助您持续深入阅读。' ),
		'Điểm nổi bật' => array( 'en' => 'Highlights', 'zh' => '项目亮点' ),
		'Thông tin dự án' => array( 'en' => 'Project information', 'zh' => '项目信息' ),
		'Vị trí' => array( 'en' => 'Location', 'zh' => '位置' ),
		'Quy mô' => array( 'en' => 'Scale', 'zh' => '规模' ),
		'Diện tích' => array( 'en' => 'Area', 'zh' => '面积' ),
		'Chủ đầu tư' => array( 'en' => 'Investor', 'zh' => '投资方' ),
		'Phát triển' => array( 'en' => 'Developer', 'zh' => '开发单位' ),
		'Năm' => array( 'en' => 'Year', 'zh' => '年份' ),
		'Bàn giao' => array( 'en' => 'Handover', 'zh' => '交付' ),
		'Dự án liên quan' => array( 'en' => 'Related projects', 'zh' => '相关项目' ),
		'Mức lương' => array( 'en' => 'Salary', 'zh' => '薪资' ),
		'Kinh nghiệm' => array( 'en' => 'Experience', 'zh' => '经验' ),
		'Cấp bậc' => array( 'en' => 'Level', 'zh' => '级别' ),
		'Trạng thái' => array( 'en' => 'Status', 'zh' => '状态' ),
		'8+ năm tại doanh nghiệp niêm yết' => array( 'en' => '8+ years in a listed company environment', 'zh' => '8年以上上市公司工作经验' ),
		'Nguồn dữ liệu tham khảo:' => array( 'en' => 'Reference sources:', 'zh' => '参考来源：' ),
		'Nội dung được QP Holdings biên tập, tổng hợp lại để phù hợp với chuyên mục tin tức.' => array( 'en' => 'The content has been edited and compiled by QP Holdings to fit the news section.', 'zh' => '内容由 QP Holdings 编辑整理，以适配新闻栏目。' ),
		'Tập đoàn khí LNG hàng đầu Mỹ muốn Việt Nam thành trung tâm phân phối của ASEAN' => array( 'en' => 'Leading U.S. LNG group wants Vietnam to become ASEAN distribution hub', 'zh' => '美国领先 LNG 集团希望越南成为东盟分销中心' ),
		'Mở ra cơ hội đầu tư công nghệ và phát triển dự án LNG quy mô lớn tại Việt Nam.' => array( 'en' => 'Opening up technology investment opportunities and large-scale LNG project development in Vietnam.', 'zh' => '为越南的大型 LNG 项目开发和技术投资带来新机遇。' ),
		'Hạn nộp' => array( 'en' => 'Deadline', 'zh' => '截止日期' ),
		'Sẵn sàng ứng tuyển?' => array( 'en' => 'Ready to apply?', 'zh' => '准备申请了吗？' ),
		'Gửi CV và thư ứng tuyển trực tiếp cho đội ngũ tuyển dụng QP Holdings.' => array( 'en' => 'Send your CV and cover letter directly to the QP Holdings recruitment team.', 'zh' => '请将您的简历和求职信直接发送给 QP Holdings 招聘团队。' ),
		'Ứng tuyển ngay' => array( 'en' => 'Apply now', 'zh' => '立即申请' ),
		'Chia sẻ vị trí này' => array( 'en' => 'Share this role', 'zh' => '分享该职位' ),
		'VN-Index bùng nổ với thanh khoản kỷ lục, thị trường bước vào giai đoạn mới' => array( 'en' => 'VN-Index surges on record liquidity as the market enters a new phase', 'zh' => 'VN-Index 在创纪录流动性推动下大涨，市场进入新阶段' ),
		'Thị trường đang ở nhịp tăng kéo dài hơn 1 tháng, sự điều chỉnh là cần thiết để tạo nền cho giai đoạn phát triển tiếp theo.' => array( 'en' => 'The market has been in an extended rally for more than a month, so a correction is needed to build a healthier base for the next growth phase.', 'zh' => '市场已连续上涨一个多月，阶段性调整有助于为下一轮增长建立更稳固的基础。' ),
		'VN-Index ghi nhận một phiên giao dịch đặc biệt khi dòng tiền nhập cuộc mạnh, thanh khoản khớp lệnh trên HoSE vươn lên vùng kỷ lục và nhóm cổ phiếu vốn hóa lớn tiếp tục đóng vai trò dẫn dắt. Sau nhịp tăng nhanh trong phiên, áp lực chốt lời khiến chỉ số rung lắc, nhưng lực cầu cuối ngày vẫn giúp thị trường giữ được sắc xanh.' => array( 'en' => 'VN-Index posted an exceptional session as strong capital inflows pushed matched liquidity on HoSE to record territory, with large-cap stocks continuing to lead. After a rapid intraday rise, profit-taking created volatility, but end-of-day demand still helped the market close in positive territory.', 'zh' => 'VN-Index 录得一场异常活跃的交易日，强劲资金流入推动 HoSE 撮合成交量升至历史高位，大盘股继续发挥领涨作用。盘中快速拉升后，获利回吐带来震荡，但尾盘买盘仍帮助市场守住涨势。' ),
		'Diễn biến VN-Index trong phiên bùng nổ thanh khoản được tổng hợp từ dữ liệu thị trường.' => array( 'en' => 'VN-Index movements during this high-liquidity session were compiled from market data.', 'zh' => '本次放量行情中的 VN-Index 走势根据市场数据整理而成。' ),
		'Dòng tiền mạnh nhưng phân hóa rõ' => array( 'en' => 'Strong liquidity, but clear divergence', 'zh' => '资金强劲，但分化明显' ),
		'Điểm đáng chú ý không chỉ nằm ở mức tăng của chỉ số chung mà còn ở cấu trúc dòng tiền. VN30 diễn biến tích cực hơn VN-Index, cho thấy tiền lớn ưu tiên nhóm cổ phiếu trụ như ngân hàng, thép và một số mã bất động sản. Trong khi đó, nhiều cổ phiếu đã tăng nóng bắt đầu xuất hiện trạng thái giằng co, phản ánh tâm lý thận trọng hơn của nhà đầu tư sau giai đoạn tăng kéo dài.' => array( 'en' => 'What stood out was not only the rise of the broad index, but also the structure of capital flows. VN30 outperformed VN-Index, showing that larger pools of money favored pillar stocks such as banks, steel, and selected real estate names. Meanwhile, many overheated stocks began to consolidate, reflecting more cautious investor sentiment after an extended rally.', 'zh' => '值得关注的不仅是大盘指数上涨本身，更是资金流向结构。VN30 表现优于 VN-Index，说明大资金更偏好银行、钢铁及部分地产等权重股。与此同时，许多前期涨幅过大的个股开始震荡整理，反映出在持续上涨后投资者情绪趋于谨慎。' ),
		'Theo dữ liệu được tổng hợp từ các bản tin thị trường, VN-Index từng có thời điểm tăng gần 57 điểm so với tham chiếu trong phiên, sau đó thu hẹp đà tăng khi áp lực chốt lời dâng cao. Dù vậy, việc dòng tiền quay lại nhanh cho thấy thanh khoản vẫn là điểm tựa quan trọng của xu hướng hiện tại.' => array( 'en' => 'According to aggregated market bulletins, VN-Index was at one point up nearly 57 points versus the reference level before trimming gains as profit-taking intensified. Even so, the quick return of capital showed that liquidity remains a critical support for the current trend.', 'zh' => '根据市场简报汇总数据，VN-Index 盘中一度较基准上涨近 57 点，随后因获利回吐加剧而收窄涨幅。即便如此，资金迅速回流仍表明流动性依旧是当前趋势的重要支撑。' ),
		'Rủi ro FOMO và vùng hỗ trợ cần quan sát' => array( 'en' => 'FOMO risk and support zones to watch', 'zh' => '需关注的 FOMO 风险与支撑区间' ),
		'Các chuyên gia phân tích cho rằng thị trường đã có nhịp đi lên kéo dài hơn một tháng nên một nhịp điều chỉnh kỹ thuật là cần thiết để tạo nền tích lũy mới. Vùng hỗ trợ gần được nhắc tới quanh 1.484-1.500 điểm, cũng là khu vực giúp nhà đầu tư đánh giá sức khỏe của dòng tiền sau các phiên biến động mạnh.' => array( 'en' => 'Analysts believe the market has been climbing for more than a month, so a technical pullback is necessary to build a new consolidation base. The nearest support zone is often cited around 1,484–1,500 points, which will also help investors assess the resilience of capital flows after sharp swings.', 'zh' => '分析师认为市场已持续上涨一个多月，因此技术性回调有助于构建新的整理基础。近期支撑区域通常被提及在 1,484–1,500 点附近，这一区间也将帮助投资者判断大幅波动后资金面的健康程度。' ),
		'Thanh khoản cao tạo cơ hội nhưng cũng làm gia tăng biến động ngắn hạn.' => array( 'en' => 'High liquidity creates opportunities, but it also increases short-term volatility.', 'zh' => '高流动性带来机会，但也会放大短期波动。' ),
		'Góc nhìn từ QP Holdings' => array( 'en' => 'The QP Holdings perspective', 'zh' => 'QP Holdings 视角' ),
		'Với định hướng đầu tư dài hạn, QP Holdings ưu tiên quan sát chất lượng dòng tiền thay vì chỉ bám theo mốc điểm số. Các nhóm ngành gắn với nền tảng vĩ mô như hạ tầng, bất động sản đô thị, ngân hàng và năng lượng vẫn được đánh giá có dư địa nếu kết quả kinh doanh cải thiện rõ ràng. Nhà đầu tư nên tránh mua đuổi trong các phiên hưng phấn, đồng thời quản trị tỷ trọng và đặt mốc bảo vệ lợi nhuận cho các vị thế đã tăng mạnh.' => array( 'en' => 'With a long-term investment approach, QP Holdings focuses on the quality of capital flows rather than index levels alone. Sectors supported by macro fundamentals such as infrastructure, urban real estate, banking, and energy are still considered to have upside if earnings improve clearly. Investors should avoid chasing momentum during euphoric sessions, while actively managing allocations and protecting gains on positions that have already rallied strongly.', 'zh' => '在长期投资导向下，QP Holdings 更重视资金流质量，而非单纯跟随指数点位。若业绩改善明确，基础设施、城市地产、银行与能源等受宏观基本面支撑的板块仍被认为具备上行空间。投资者应避免在情绪亢奋时追涨，同时管理仓位并为已大幅上涨的持仓设置利润保护。' ),
		'Thanh khoản kỷ lục là tín hiệu tích cực, nhưng chiến lược phù hợp hơn trong giai đoạn này là giải ngân theo nhịp điều chỉnh và ưu tiên doanh nghiệp có nền tảng tăng trưởng bền vững.' => array( 'en' => 'Record liquidity is a positive signal, but a more suitable strategy at this stage is to deploy capital on pullbacks and prioritize companies with sustainable growth fundamentals.', 'zh' => '创纪录的流动性是积极信号，但在当前阶段，更合适的策略是在回调时分批布局，并优先选择具备可持续增长基础的企业。' ),
		'Nguồn dữ liệu tham khảo: QP Xanh / Nhịp sống thị trường. Nội dung được QP Holdings biên tập, tổng hợp lại để phù hợp với chuyên mục tin tức.' => array( 'en' => 'Reference sources: QP Xanh / Market Pulse. The content has been edited and compiled by QP Holdings to fit the news section.', 'zh' => '参考来源：QP Xanh / 市场脉搏。内容由 QP Holdings 编辑整理，以适配新闻栏目。' ),
		'Khu đô thị sinh thái tích hợp tại Bến Lức, Long An' => array( 'en' => 'An integrated eco-urban township in Ben Luc, Long An', 'zh' => '位于隆安省 Bến Lức 的综合生态都市项目' ),
		'QP Green Park là khu đô thị sinh thái 35 ha với hơn 1.200 sản phẩm gồm nhà phố, shop-house, biệt thự song lập và biệt thự đơn lập, kết hợp công viên trung tâm 12 ha và hệ thống hồ điều hoà.' => array( 'en' => 'QP Green Park is a 35-hectare eco-urban township with more than 1,200 products including townhouses, shop-houses, semi-detached villas, and detached villas, complemented by a 12-hectare central park and a landscape lake system.', 'zh' => 'QP Green Park 是一个占地 35 公顷的生态都市项目，规划超过 1,200 套产品，包括联排住宅、商住联排、双拼别墅和独栋别墅，并配套 12 公顷中央公园及景观水系。' ),
		'Quy hoạch' => array( 'en' => 'Planning', 'zh' => '规划' ),
		'Dự án được quy hoạch bởi WATG (Singapore) với tỷ lệ cây xanh hơn 35% diện tích, mật độ xây dựng tối đa 38%.' => array( 'en' => 'The project was planned by WATG (Singapore) with green coverage exceeding 35% of the site and a maximum construction density of 38%.', 'zh' => '项目由 WATG（新加坡）规划设计，绿化面积占比超过 35%，最大建筑密度为 38%。' ),
		'Công viên trung tâm 12 ha + hệ thống hồ điều hoà' => array( 'en' => '12-hectare central park and integrated lake system', 'zh' => '12 公顷中央公园及综合水景系统' ),
		'Trường liên cấp song ngữ ngay trong nội khu' => array( 'en' => 'A bilingual K-12 school within the township', 'zh' => '社区内配套双语一体化学校' ),
		'Giao thông kết nối cao tốc TP.HCM — Trung Lương' => array( 'en' => 'Direct connectivity to the HCMC – Trung Luong expressway', 'zh' => '便捷接驳胡志明市—中良高速公路' ),
		'Thiết kế bởi WATG Singapore' => array( 'en' => 'Designed by WATG Singapore', 'zh' => '由 WATG Singapore 设计' ),
		'Thông tin dự án' => array( 'en' => 'Project information', 'zh' => '项目信息' ),
		'Thị trấn Bến Lức, huyện Bến Lức, Long An' => array( 'en' => 'Ben Luc Town, Ben Luc District, Long An', 'zh' => '隆安省 Bến Lức 县 Bến Lức 镇' ),
		'35 ha — 1.200 sản phẩm' => array( 'en' => '35 hectares — 1,200 products', 'zh' => '35 公顷 — 1,200 套产品' ),
		'Bàn giao theo từng giai đoạn 2026 – 2027' => array( 'en' => 'Phased handover from 2026 to 2027', 'zh' => '将于 2026–2027 年分阶段交付' ),
		'Kế toán trưởng' => array( 'en' => 'Chief Accountant', 'zh' => '首席会计师' ),
		'Vị trí Kế toán trưởng — Khối Tài chính tại TP. Hồ Chí Minh.' => array( 'en' => 'Chief Accountant role — Finance Division in Ho Chi Minh City.', 'zh' => '首席会计师职位——工作地点为胡志明市财务部门。' ),
		'Trách nhiệm chính' => array( 'en' => 'Key responsibilities', 'zh' => '主要职责' ),
		'Quản lý toàn bộ hoạt động kế toán — thuế — kiểm toán' => array( 'en' => 'Manage all accounting, tax, and audit activities', 'zh' => '统筹管理全部会计、税务与审计工作' ),
		'Ký báo cáo tài chính riêng lẻ và hợp nhất' => array( 'en' => 'Sign stand-alone and consolidated financial statements', 'zh' => '签署单体及合并财务报表' ),
		'Làm việc với Big4 và cơ quan thuế' => array( 'en' => 'Work directly with Big4 firms and tax authorities', 'zh' => '与四大会计师事务所及税务机关直接对接' ),
		'Tham mưu cho CFO về quản trị tài chính' => array( 'en' => 'Advise the CFO on financial governance matters', 'zh' => '为 CFO 提供财务治理相关建议' ),
		'Yêu cầu' => array( 'en' => 'Requirements', 'zh' => '任职要求' ),
		'Tốt nghiệp Đại học Kế toán / Kiểm toán' => array( 'en' => 'Bachelor’s degree in Accounting or Auditing', 'zh' => '本科及以上学历，会计或审计相关专业' ),
		'Chứng chỉ Kế toán trưởng, CPA Việt Nam, ACCA ưu tiên' => array( 'en' => 'Chief Accountant certification, Vietnam CPA, and ACCA are preferred', 'zh' => '优先考虑持有首席会计师资格、越南 CPA 或 ACCA 证书者' ),
		'Kinh nghiệm IFRS, hợp nhất, niêm yết' => array( 'en' => 'Hands-on experience with IFRS, consolidation, and listed-company reporting', 'zh' => '具备 IFRS、合并报表及上市公司披露经验' ),
		'Kỹ năng quản lý 10+ nhân viên' => array( 'en' => 'Experience leading teams of 10+ staff members', 'zh' => '具备管理 10 人以上团队的能力' ),
		'Quyền lợi' => array( 'en' => 'Benefits', 'zh' => '福利待遇' ),
		'Lương cao cấp, thưởng cuối năm + ESOP' => array( 'en' => 'Executive-level salary, year-end bonus, and ESOP', 'zh' => '高阶薪酬、年终奖金及员工持股激励' ),
		'Được tài trợ ACCA / CPA cho thành viên team' => array( 'en' => 'ACCA / CPA sponsorship for eligible team members', 'zh' => '为符合条件的团队成员提供 ACCA / CPA 资助' ),
		'Khối Tài chính' => array( 'en' => 'Finance Division', 'zh' => '财务部门' ),
		'TP. Hồ Chí Minh' => array( 'en' => 'Ho Chi Minh City', 'zh' => '胡志明市' ),
		'Toàn thời gian' => array( 'en' => 'Full-time', 'zh' => '全职' ),
		'Thoả thuận' => array( 'en' => 'Negotiable', 'zh' => '面议' ),
		'CÔNG TY CỔ PHẦN QP HOLDINGS' => array( 'en' => 'QP HOLDINGS JOINT STOCK COMPANY', 'zh' => 'QP HOLDINGS 股份公司' ),
		'Trụ sở chính' => array( 'en' => 'Head Office', 'zh' => '总部' ),
		'Văn phòng Bình Dương' => array( 'en' => 'Binh Duong Office', 'zh' => '平阳办公室' ),
		'Thành phố Hồ Chí Minh' => array( 'en' => 'Ho Chi Minh City', 'zh' => '胡志明市' ),
		'Việt Nam' => array( 'en' => 'Vietnam', 'zh' => '越南' ),
		'Kiến tạo giá trị' => array( 'en' => 'Creating value', 'zh' => '创造价值' ),
		'Dẫn lối thịnh vượng' => array( 'en' => 'Leading prosperity', 'zh' => '引领繁荣' ),
		'Tầm nhìn - Sứ mệnh' => array( 'en' => 'Vision - Mission', 'zh' => '愿景 - 使命' ),
		'Giá trị cốt lõi' => array( 'en' => 'Core Values', 'zh' => '核心价值' ),
		'Các dự án tiêu biểu' => array( 'en' => 'Featured Projects', 'zh' => '精选项目' ),
		'Tin tức &amp; Sự kiện' => array( 'en' => 'News &amp; Events', 'zh' => '新闻与活动' ),
		'Cập Nhật Mới Nhất' => array( 'en' => 'Latest Updates', 'zh' => '最新动态' ),
		'Đối tác' => array( 'en' => 'Partners', 'zh' => '合作伙伴' ),
		'Kết nối với QP Holdings' => array( 'en' => 'Connect with QP Holdings', 'zh' => '联系 QP Holdings' ),
		'THÔNG TIN LIÊN HỆ' => array( 'en' => 'CONTACT INFORMATION', 'zh' => '联系方式' ),
		'Không tìm thấy nội dung' => array( 'en' => 'Content not found', 'zh' => '未找到内容' ),
		'Tiếp tục khám phá' => array( 'en' => 'Continue exploring', 'zh' => '继续探索' ),
		'Chuyên mục' => array( 'en' => 'Category', 'zh' => '分类' ),
		'Bài viết' => array( 'en' => 'Article', 'zh' => '文章' ),
		'Đã bàn giao' => array( 'en' => 'Delivered', 'zh' => '已交付' ),
		'Đang triển khai' => array( 'en' => 'In progress', 'zh' => '进行中' ),
		'Sắp ra mắt' => array( 'en' => 'Coming soon', 'zh' => '即将推出' ),
		'Quy hoạch' => array( 'en' => 'Planning', 'zh' => '规划中' ),
		'Khu dân cư' => array( 'en' => 'Residential', 'zh' => '住宅' ),
		'Khu phức hợp' => array( 'en' => 'Mixed-use', 'zh' => '综合体' ),
		'Bất động sản văn phòng' => array( 'en' => 'Office real estate', 'zh' => '办公地产' ),
		'Khu công nghiệp' => array( 'en' => 'Industrial park', 'zh' => '工业园' ),
		'Năng lượng' => array( 'en' => 'Energy', 'zh' => '能源' ),
		'Bất động sản nghỉ dưỡng' => array( 'en' => 'Hospitality real estate', 'zh' => '度假地产' ),
		'Truyền thông' => array( 'en' => 'Media', 'zh' => '媒体' ),
		'Thông cáo báo chí' => array( 'en' => 'Press releases', 'zh' => '新闻稿' ),
		'Sự kiện' => array( 'en' => 'Events', 'zh' => '活动' ),
		'Thị trường' => array( 'en' => 'Market', 'zh' => '市场' ),
		'Nhà đầu tư' => array( 'en' => 'Investors', 'zh' => '投资者' ),
		'Khối Đầu tư' => array( 'en' => 'Investment Division', 'zh' => '投资部门' ),
		'Khối Dự án' => array( 'en' => 'Project Division', 'zh' => '项目部门' ),
		'Khối Tài chính' => array( 'en' => 'Finance Division', 'zh' => '财务部门' ),
		'Khối Pháp chế' => array( 'en' => 'Legal Division', 'zh' => '法务部门' ),
		'Khối Nhân sự' => array( 'en' => 'Human Resources Division', 'zh' => '人力资源部门' ),
		'Thực tập sinh' => array( 'en' => 'Intern', 'zh' => '实习生' ),
		'Trung cấp' => array( 'en' => 'Mid-level', 'zh' => '中级' ),
		'Trưởng nhóm / Quản lý' => array( 'en' => 'Lead / Manager', 'zh' => '主管 / 经理' ),

		/* ── Contact page ── */
		'LIÊN HỆ' => array( 'en' => 'CONTACT', 'zh' => '联系我们' ),
		'Địa chỉ' => array( 'en' => 'Address', 'zh' => '地址' ),
		'Giờ làm việc' => array( 'en' => 'Working hours', 'zh' => '工作时间' ),
		'Thứ 2 – Thứ 6: 8:00 – 17:30' => array( 'en' => 'Mon – Fri: 8:00 – 17:30', 'zh' => '周一至周五：8:00 – 17:30' ),
		'Gửi tin nhắn' => array( 'en' => 'Send a message', 'zh' => '发送消息' ),
		'Chúng tôi luôn sẵn sàng lắng nghe đối tác, khách hàng và cộng đồng. Liên hệ với chúng tôi theo thông tin bên dưới.' => array( 'en' => 'We are always ready to listen to partners, clients, and the community. Reach us using the information below.', 'zh' => '我们随时倾听合作伙伴、客户及社区的声音。请通过以下方式联系我们。' ),
		'Điền thông tin bên dưới, chúng tôi sẽ liên hệ trong vòng 48 giờ.' => array( 'en' => 'Fill in the form below and we will get back to you within 48 hours.', 'zh' => '请填写以下表格，我们将在48小时内与您联系。' ),

		/* ── Careers page static strings ── */
		'Cùng QP Holdings kiến tạo tương lai' => array( 'en' => 'Shape the future with QP Holdings', 'zh' => '携手 QP Holdings 共创未来' ),
		'Hơn 500 cán bộ nhân viên trên toàn quốc đang cùng nhau tạo nên những giá trị bền vững cho Việt Nam.' => array( 'en' => 'More than 500 employees nationwide are working together to build lasting value for Vietnam.', 'zh' => '全国逾500名员工携手共进，为越南创造持久价值。' ),
		'TUYỂN DỤNG' => array( 'en' => 'CAREERS', 'zh' => '招聘' ),
		'TẠI SAO QP HOLDINGS' => array( 'en' => 'WHY QP HOLDINGS', 'zh' => '为什么选择 QP HOLDINGS' ),
		'Nơi tài năng được trân trọng' => array( 'en' => 'Where talent is valued', 'zh' => '人才备受珍视之地' ),
		'Lộ trình phát triển' => array( 'en' => 'Career growth path', 'zh' => '职业发展路径' ),
		'Chương trình đào tạo và lộ trình thăng tiến rõ ràng cho từng vị trí.' => array( 'en' => 'Structured training programmes and a clear progression path for every role.', 'zh' => '每个岗位均有系统培训计划与清晰晋升路径。' ),
		'Phúc lợi toàn diện' => array( 'en' => 'Comprehensive benefits', 'zh' => '全面员工福利' ),
		'Bảo hiểm sức khỏe cao cấp, thưởng hiệu suất, nghỉ phép linh hoạt.' => array( 'en' => 'Premium health insurance, performance bonuses, and flexible leave.', 'zh' => '高端医疗保险、绩效奖金及灵活假期制度。' ),
		'Văn hoá hợp tác' => array( 'en' => 'Collaborative culture', 'zh' => '协作文化' ),
		'Môi trường đa dạng, tôn trọng sự khác biệt và đề cao tinh thần đồng đội.' => array( 'en' => 'A diverse, inclusive environment that respects difference and champions teamwork.', 'zh' => '多元包容的环境，尊重差异，弘扬团队精神。' ),
		'Dự án quy mô lớn' => array( 'en' => 'Large-scale projects', 'zh' => '大型项目' ),
		'Tham gia các công trình trọng điểm quốc gia và dự án quốc tế.' => array( 'en' => 'Contribute to key national projects and international developments.', 'zh' => '参与国家重点工程与国际项目。' ),
		'Khám phá cơ hội nghề nghiệp tại tập đoàn — nơi tài năng được trân trọng và phát triển.' => array( 'en' => 'Explore career opportunities at the group — where talent is valued and developed.', 'zh' => '探索集团职业机会——人才备受珍视、持续成长。' ),
		'Xem vị trí tuyển dụng' => array( 'en' => 'View open positions', 'zh' => '查看招聘职位' ),

		/* ── Stock page static strings ── */
		'Theo dõi cập nhật thông tin cổ phiếu, dữ liệu giao dịch và các công bố quan trọng của QP Holdings trên Sở Giao dịch Chứng khoán.' => array( 'en' => 'Track the latest stock data, trading figures, and key disclosures from QP Holdings on the Stock Exchange.', 'zh' => '跟踪 QP Holdings 在证券交易所的最新股票数据、交易信息及重要公告。' ),
		'Thông tin công bố' => array( 'en' => 'Disclosed Information', 'zh' => '信息披露' ),

		/* ══════════════════════════════════════════
		   New design templates — site-wide strings (front-page, gioi-thieu, lien-he, tintuc, du-an)
		   Longer phrases first so partial matches don't corrupt the result.
		   ══════════════════════════════════════════ */

		/* ── HOME — Hero ── */
		'QP Holdings là đơn vị tư vấn và triển khai các giải pháp đầu tư, tài chính và chứng khoán, hướng đến tăng trưởng bền vững và kiểm soát rủi ro.' => array( 'en' => 'QP Holdings advises on and delivers investment, finance, and securities solutions focused on sustainable growth and disciplined risk control.', 'zh' => 'QP Holdings 是一家提供投资、金融与证券解决方案的专业机构，致力于实现可持续增长并严格管控风险。' ),
		'Mã CK: HKT' => array( 'en' => 'Ticker: HKT', 'zh' => '股票代码：HKT' ),
		'Khám phá dự án' => array( 'en' => 'Explore projects', 'zh' => '探索项目' ),
		'Về QP Holdings' => array( 'en' => 'About QP Holdings', 'zh' => '关于 QP Holdings' ),
		'VỐN CHỦ SỞ HỮU' => array( 'en' => 'OWNER’S EQUITY', 'zh' => '所有者权益' ),
		'Tỷ đồng' => array( 'en' => 'Billion VND', 'zh' => '十亿越盾' ),
		'DỰ ÁN ĐANG TRIỂN KHAI' => array( 'en' => 'ACTIVE PROJECTS', 'zh' => '在建项目' ),
		'Trên toàn quốc' => array( 'en' => 'Nationwide', 'zh' => '覆盖全国' ),
		'ĐỐI TÁC &amp; NHÀ ĐẦU TƯ' => array( 'en' => 'PARTNERS &amp; INVESTORS', 'zh' => '合作伙伴与投资者' ),
		'ĐỐI TÁC & NHÀ ĐẦU TƯ' => array( 'en' => 'PARTNERS & INVESTORS', 'zh' => '合作伙伴与投资者' ),
		'Đồng hành phát triển' => array( 'en' => 'Growing together', 'zh' => '携手成长' ),

		/* About-page Board of Directors section (group headings + intro) */
		'Hội đồng quản trị' => array( 'en' => 'Board of Directors', 'zh' => '董事会' ),
		'Ban điều hành' => array( 'en' => 'Executive Board', 'zh' => '管理层' ),
		/* ── HOME — Hero bar (3 fields) ── */
		'Đầu Tư Tài Chính' => array( 'en' => 'Financial Investment', 'zh' => '金融投资' ),
		'Tối ưu nguồn vốn, quản trị rủi ro hiệu quả, gia tăng giá trị dài hạn.' => array( 'en' => 'Optimise capital allocation, manage risk efficiently, and build long-term value.', 'zh' => '优化资本配置，高效管理风险，持续提升长期价值。' ),
		'Đầu tư - Phát triển Bất động sản' => array( 'en' => 'Real Estate Investment & Development', 'zh' => '房地产投资与开发' ),
		'Phát triển các dự án chất lượng bền vững và khác biệt.' => array( 'en' => 'Develop sustainable, high-quality, and differentiated projects.', 'zh' => '开发可持续、高质量且差异化的项目。' ),
		'Phát triển Khu Công nghiệp' => array( 'en' => 'Industrial Park Development', 'zh' => '工业园区开发' ),
		'Đồng hành cùng đối tác và nhà đầu tư trên hành trình thịnh vượng.' => array( 'en' => 'Partner with investors and stakeholders on a shared path to prosperity.', 'zh' => '与合作伙伴及投资者同行，共创繁荣。' ),

		/* ── HOME — About section ── */
		'CHÚNG TÔI LÀ QP HOLDINGS' => array( 'en' => 'WE ARE QP HOLDINGS', 'zh' => '我们是 QP HOLDINGS' ),
		'là đơn vị' => array( 'en' => 'is a trusted' , 'zh' => '是一家' ),
		'đầu tư uy tín' => array( 'en' => 'investment partner', 'zh' => '值得信赖的投资机构' ),
		'tại Việt Nam' => array( 'en' => 'in Vietnam', 'zh' => '立足越南' ),
		'đầu tư và phát triển BĐS' => array( 'en' => 'leading Real Estate investment and development partner', 'zh' => '领先的房地产投资与开发企业' ),
		'hàng đầu tại Việt Nam' => array( 'en' => 'in Vietnam', 'zh' => '在越南' ),
		'QP Holdings hoạt động trong các lĩnh vực trọng tâm gồm: Đầu tư Tài chính, Đầu tư - Phát triển Bất động sản, Phát triển Khu Công nghiệp.' => array( 'en' => 'QP Holdings operates across three focus areas: Financial Investment, Real Estate Investment & Development, and Industrial Park Development.', 'zh' => 'QP Holdings 主要业务涵盖三大领域：金融投资、房地产投资与开发、工业园区开发。' ),
		'Đầu tư Tài chính, Đầu tư - Phát triển Bất động sản, Phát triển Khu Công nghiệp.' => array( 'en' => 'Financial Investment, Real Estate Investment & Development, Industrial Park Development.', 'zh' => '金融投资、房地产投资与开发、工业园区开发。' ),
		'Bất động sản Dân Dụng, Bất động sản Công Nghiệp, Đầu tư Tài chính.' => array(
			'en' => 'Residential Real Estate, Industrial Real Estate, Financial Investment.',
			'zh' => '住宅房地产、工业房地产、金融投资。',
		),
		'Với gần 20 năm kinh nghiệm, QP Holdings định hướng phát triển bền vững, kiến tạo giá trị lâu dài cho đối tác, khách hàng và cộng đồng thông qua các hoạt động đầu tư hiệu quả, minh bạch và trách nhiệm.' => array( 'en' => 'With nearly 20 years of experience, QP Holdings is committed to sustainable growth, creating long-term value for partners, clients, and the community through effective, transparent, and responsible investments.', 'zh' => '凭借近20年的发展经验，QP Holdings 以可持续增长为方向，通过高效、透明和负责任的投资活动，为合作伙伴、客户和社会创造长期价值。' ),
		'Minh bạch trong quản trị và vận hành' => array( 'en' => 'Transparent governance and operations', 'zh' => '治理与运营透明' ),
		'Nâng cao năng lực đầu tư và hiệu quả hoạt động' => array( 'en' => 'Strengthening investment capability and operational effectiveness', 'zh' => '不断提升投资能力与运营效率' ),
		'Đồng hành phát triển cùng đối tác và khách hàng' => array( 'en' => 'Growing alongside partners and customers', 'zh' => '与合作伙伴及客户共同成长' ),
		'Hướng đến tăng trưởng dài hạn và bền vững' => array( 'en' => 'Driving long-term, sustainable growth', 'zh' => '追求长期且可持续的增长' ),
		'Không ngừng đổi mới và nâng cao năng lực cạnh tranh' => array( 'en' => 'Continuously innovating and enhancing competitiveness', 'zh' => '持续创新，不断提升竞争力' ),
		'Tìm hiểu thêm' => array( 'en' => 'Learn more', 'zh' => '了解更多' ),
		'learn_more' => array( 'vi' => 'Tìm hiểu thêm', 'en' => 'Learn more', 'zh' => '了解更多' ),
		'learn_more_about' => array( 'vi' => 'Tìm hiểu thêm về QP Holdings', 'en' => 'Learn more about QP Holdings', 'zh' => '了解更多关于 QP Holdings' ),
		'learn_more_commitment' => array( 'vi' => 'Tìm hiểu thêm về cam kết của QP Holdings', 'en' => 'Learn more about QP Holdings commitments', 'zh' => '了解更多关于 QP Holdings 的承诺' ),

		/* ── HOME — Activity (Lĩnh vực hoạt động) ── */
		'GIỚI THIỆU CÔNG TY CỔ PHẦN QP HOLDINGS' => array( 'en' => 'ABOUT QP HOLDINGS JOINT STOCK COMPANY', 'zh' => 'QP HOLDINGS 股份公司简介' ),
		'Lĩnh vực hoạt động' => array( 'en' => 'Areas of Business', 'zh' => '业务领域' ),
		'Đầu tư - Phát triển BĐS' => array( 'en' => 'Real Estate Investment & Development', 'zh' => '房地产投资与开发' ),
		'Đầu tư – Phát triển Bất động sản' => array( 'en' => 'Real Estate Investment & Development', 'zh' => '房地产投资与开发' ),
		'Phát triển các dự án nhà ở, khu đô thị và thương mại theo định hướng đồng bộ về quy hoạch, pháp lý và hiệu quả khai thác đầu tư.' => array( 'en' => 'Developing residential, urban, and commercial projects with an integrated approach to master planning, legal frameworks, and investment performance.', 'zh' => '以规划、法律及投资效益的一体化思路，开发住宅、城市与商业项目。' ),
		'Đầu tư Tài chính' => array( 'en' => 'Financial Investment', 'zh' => '金融投资' ),
		'Tối ưu nguồn vốn, quản trị danh mục đầu tư và phát triển các cơ hội đầu tư hiệu quả, hướng đến gia tăng giá trị dài hạn cho doanh nghiệp và đối tác.' => array( 'en' => 'Optimising capital, managing investment portfolios, and developing high-impact opportunities that drive long-term value for the business and its partners.', 'zh' => '优化资本运作，管理投资组合，开发优质投资机会，为企业及合作伙伴持续创造长期价值。' ),
		'Đầu tư phát triển hạ tầng Khu công nghiệp hiện đại, nâng cao năng lực kết nối và thu hút doanh nghiệp sản xuất, logistics và dịch vụ hỗ trợ.' => array( 'en' => 'Investing in modern industrial park infrastructure, improving connectivity, and attracting manufacturing, logistics, and supporting-services tenants.', 'zh' => '投资建设现代化工业园区基础设施，提升互联互通能力，吸引制造、物流及配套服务企业入驻。' ),
		'Khám phá ngay' => array( 'en' => 'Explore now', 'zh' => '立即探索' ),

		/* ── HOME — Commitment ── */
		'TRUY XUẤT NGUỒN GỐC' => array( 'en' => 'TRACEABILITY', 'zh' => '可追溯性' ),
		'QP Holdings cam kết' => array( 'en' => 'QP Holdings commits to', 'zh' => 'QP Holdings 承诺' ),
		'đồng hành' => array( 'en' => 'partnership', 'zh' => '同行' ),
		'minh bạch' => array( 'en' => 'transparency', 'zh' => '透明' ),
		'Đồng hành dài hạn cùng đối tác và khách hàng' => array( 'en' => 'Long-term partnership with clients and partners', 'zh' => '与合作伙伴及客户长期同行' ),
		'Lấy uy tín và hiệu quả làm nền tảng, hướng đến xây dựng các mối quan hệ hợp tác bền vững và gia tăng giá trị lâu dài cho các bên.' => array( 'en' => 'Built on credibility and effectiveness, we cultivate enduring partnerships that create lasting value for all stakeholders.', 'zh' => '以信誉与成效为基础，建立长期合作关系，为各方持续创造价值。' ),
		'Minh bạch trong quản trị và hợp tác đầu tư' => array( 'en' => 'Transparent governance and investment partnerships', 'zh' => '治理与投资合作透明可视' ),
		'Đảm bảo tính rõ ràng, chuyên nghiệp và trách nhiệm trong mọi hoạt động vận hành, tài chính và hợp tác kinh doanh.' => array( 'en' => 'Ensuring clarity, professionalism, and accountability across operations, finance, and business partnerships.', 'zh' => '在运营、财务及商业合作中始终保持清晰、专业与负责任的态度。' ),
		'Cam kết của QP Holdings' => array( 'en' => 'QP Holdings commitments', 'zh' => 'QP Holdings 承诺' ),
		'QP Holdings – Cam kết đồng hành và minh bạch' => array( 'en' => 'QP Holdings — partnership and transparency commitments', 'zh' => 'QP Holdings —— 关于同行与透明的承诺' ),

		/* ── HOME — Gallery + News + Partners ── */
		'KHOẢNH KHẮC QP HOLDINGS' => array( 'en' => 'QP HOLDINGS MOMENTS', 'zh' => 'QP HOLDINGS 精彩时刻' ),
		'khoảnh khắc' => array( 'en' => 'moments', 'zh' => '精彩时刻' ),
		'Xem tất cả khoảnh khắc' => array( 'en' => 'View all moments', 'zh' => '查看全部精彩时刻' ),
		'THƯ VIỆN' => array( 'en' => 'GALLERY', 'zh' => '图库' ),
		'HÌNH ẢNH &amp; VIDEO' => array( 'en' => 'PHOTOS &amp; VIDEOS', 'zh' => '照片与视频' ),
		'HÌNH ẢNH & VIDEO' => array( 'en' => 'PHOTOS & VIDEOS', 'zh' => '照片与视频' ),
		'Xem tất cả' => array( 'en' => 'View all', 'zh' => '查看全部' ),
		'Thư viện hình ảnh và video' => array( 'en' => 'Photo and video gallery', 'zh' => '照片与视频图库' ),
		'QP Holdings – Khoảnh khắc đặc biệt 1' => array( 'en' => 'QP Holdings — featured moment 1', 'zh' => 'QP Holdings —— 精彩时刻 1' ),
		'QP Holdings – Khoảnh khắc đặc biệt 2' => array( 'en' => 'QP Holdings — featured moment 2', 'zh' => 'QP Holdings —— 精彩时刻 2' ),
		'QP Holdings – Khoảnh khắc đặc biệt 3' => array( 'en' => 'QP Holdings — featured moment 3', 'zh' => 'QP Holdings —— 精彩时刻 3' ),
		'QP Holdings – Khoảnh khắc đặc biệt 4' => array( 'en' => 'QP Holdings — featured moment 4', 'zh' => 'QP Holdings —— 精彩时刻 4' ),
		'TIN TỨC QP HOLDINGS' => array( 'en' => 'QP HOLDINGS NEWS', 'zh' => 'QP HOLDINGS 新闻' ),
		'Bài viết nổi bật' => array( 'en' => 'Featured article', 'zh' => '精选文章' ),
		'Article nổi bật' => array( 'en' => 'Featured article', 'zh' => '精选文章' ),
		'Truyền thông &amp; tin tức' => array( 'en' => 'Media &amp; News', 'zh' => '媒体与新闻' ),
		'Truyền thông & tin tức' => array( 'en' => 'Media & News', 'zh' => '媒体与新闻' ),
		'Truyền thông' => array( 'en' => 'Media', 'zh' => '媒体' ),
		' &amp; tin tức' => array( 'en' => ' &amp; News', 'zh' => '与新闻' ),
		' & tin tức' => array( 'en' => ' & News', 'zh' => '与新闻' ),
		'Chi tiết' => array( 'en' => 'Details', 'zh' => '查看详情' ),
		'Đọc chi tiết bài viết:' => array( 'en' => 'Read article details:', 'zh' => '阅读文章详情：' ),
		'Đối tác của chúng tôi' => array( 'en' => 'Our Partners', 'zh' => '我们的合作伙伴' ),
		'Đối tác của QP Holdings' => array( 'en' => 'QP Holdings partners', 'zh' => 'QP Holdings 合作伙伴' ),
		'Danh sách đối tác' => array( 'en' => 'Partner list', 'zh' => '合作伙伴列表' ),

		/* ── Footer ── */
		'Đăng ký nhận bản tin' => array( 'en' => 'Subscribe to our newsletter', 'zh' => '订阅我们的资讯' ),
		'Đăng ký' => array( 'en' => 'Subscribe', 'zh' => '订阅' ),
		'Form đăng ký bản tin' => array( 'en' => 'Newsletter signup form', 'zh' => '资讯订阅表单' ),
		'Về chúng tôi' => array( 'en' => 'About us', 'zh' => '关于我们' ),
		'Pháp lý &amp; quy định' => array( 'en' => 'Legal &amp; compliance', 'zh' => '法律与合规' ),
		'Pháp lý & quy định' => array( 'en' => 'Legal & compliance', 'zh' => '法律与合规' ),
		'Điều khoản sử dụng' => array( 'en' => 'Terms of use', 'zh' => '使用条款' ),
		'Địa chỉ email' => array( 'en' => 'Email address', 'zh' => '电子邮箱' ),
		'Mạng xã hội' => array( 'en' => 'Social media', 'zh' => '社交媒体' ),

		/* ── About page (gioi-thieu) ── */
		'THÔNG ĐIỆP QP HOLDINGS' => array( 'en' => 'QP HOLDINGS MESSAGE', 'zh' => 'QP HOLDINGS 寄语' ),
		'Thông điệp QP Holdings' => array( 'en' => 'QP Holdings message', 'zh' => 'QP Holdings 寄语' ),
		'Kiến tạo giá trị, đồng hành thịnh vượng' => array( 'en' => 'Creating value, prospering together', 'zh' => '创造价值，共享繁荣' ),
		', đồng hành thịnh vượng' => array( 'en' => ', prospering together', 'zh' => '，共享繁荣' ),
		'QP Holdings định hướng phát triển trở thành Tập đoàn đầu tư đa ngành, tập trung xây dựng hệ sinh thái đầu tư hiệu quả, tối ưu giá trị tài sản và gia tăng giá trị bền vững cho đối tác, khách hàng và nhà đầu tư.' => array( 'en' => 'QP Holdings is on track to become a multi-sector investment group focused on building an effective investment ecosystem, optimising asset value, and delivering sustainable returns for partners, clients, and investors.', 'zh' => 'QP Holdings 致力于发展成为多元化投资集团，专注于构建高效投资生态，优化资产价值，并为合作伙伴、客户与投资者持续创造可持续回报。' ),
		'QP Holdings –' => array( 'en' => 'QP Holdings –', 'zh' => 'QP Holdings ——' ),
		'Kiến tạo giá trị,' => array( 'en' => 'Creating value,', 'zh' => '创造价值，' ),
		'đồng hành thịnh vượng' => array( 'en' => 'prospering together', 'zh' => '共享繁荣' ),
		'Công ty Cổ phần QP Holdings định hướng phát triển trở thành doanh nghiệp đầu tư đa ngành uy tín tại Việt Nam, tập trung vào các lĩnh vực trọng điểm gồm: Bất động sản Dân Dụng, Bất động sản Công Nghiệp, Đầu tư Tài chính.' => array( 'en' => 'QP Holdings Joint Stock Company is on its way to becoming Vietnam’s trusted multi-sector investment group, focused on key priority areas: Residential Real Estate, Industrial Real Estate, and Financial Investment.', 'zh' => 'QP Holdings 股份公司致力于发展成为越南值得信赖的多元化投资企业，重点聚焦以下领域：住宅房地产、工业房地产、金融投资。' ),
		'Chúng tôi định vị sự phát triển dựa trên ba nền tảng cốt lõi: Tư duy đầu tư dài hạn và hiệu quả, Quản trị minh bạch và vận hành chuyên nghiệp, Đồng hành hợp tác để cùng phát triển và thịnh vượng.' => array( 'en' => 'Our growth is anchored on three core pillars: long-term and effective investment thinking, transparent governance and professional operations, and collaborative partnerships for shared prosperity.', 'zh' => '我们的发展依托三大核心支柱：长期且高效的投资思维、透明的治理与专业的运营、共同合作以实现繁荣。' ),
		'QP Holdings tập trung đầu tư các dự án linh hoạt, tối ưu nguồn lực và nâng cao hiệu quả khai thác tài sản trong từng lĩnh vực hoạt động. Đồng thời, Công ty chú trọng mở rộng mạng lưới hợp tác chiến lược với các đối tác trong và ngoài nước nhằm gia tăng năng lực cạnh tranh và tạo ra giá trị thực chất cho thị trường.' => array( 'en' => 'QP Holdings invests in agile projects, optimises resources, and improves asset utilisation across every business line. The Company also actively expands its strategic network with domestic and international partners to boost competitiveness and deliver real value to the market.', 'zh' => 'QP Holdings 重点投资具备灵活性的项目，优化资源配置，提升各业务领域的资产运营效率。公司同时积极拓展国内外战略合作网络，增强竞争力并为市场带来实质性价值。' ),
		'Với định hướng phát triển ổn định và tư duy đổi mới liên tục, QP Holdings từng bước khẳng định vị thế thương hiệu thông qua chất lượng đầu tư, uy tín hợp tác và cam kết đồng hành lâu dài cùng đối tác, khách hàng và nhà đầu tư.' => array( 'en' => 'With a stable development direction and a commitment to continuous innovation, QP Holdings progressively strengthens its brand through investment quality, partnership credibility, and a long-term commitment to clients, partners, and investors.', 'zh' => '凭借稳定的发展方向与持续创新的理念，QP Holdings 通过投资品质、合作信誉以及与合作伙伴、客户和投资者的长期承诺，逐步巩固品牌地位。' ),
		'Xem thêm' => array( 'en' => 'Show more', 'zh' => '展开' ),
		'XEM HỒ SƠ NĂNG LỰC' => array( 'en' => 'VIEW CAPABILITY PROFILE', 'zh' => '查看能力简介' ),
		'Hồ sơ năng lực' => array( 'en' => 'Capability Profile', 'zh' => '能力简介' ),
		'Xem hồ sơ năng lực QP Holdings' => array( 'en' => 'View QP Holdings capability profile', 'zh' => '查看 QP Holdings 能力简介' ),
		'Xem thêm giới thiệu QP Holdings' => array( 'en' => 'Show more QP Holdings introduction', 'zh' => '展开 QP Holdings 简介' ),
		'Xem thêm những con số ấn tượng' => array( 'en' => 'Show more key figures', 'zh' => '展开亮眼数字' ),
		'Xem thêm lịch sử hình thành' => array( 'en' => 'Show more company history', 'zh' => '展开发展历程' ),
		'Thu gọn' => array( 'en' => 'Show less', 'zh' => '收起' ),
		'NHỮNG CON SỐ ẤN TƯỢNG' => array( 'en' => 'IMPRESSIVE FIGURES', 'zh' => '亮眼数字' ),
		'Những con số ấn tượng' => array( 'en' => 'Impressive figures', 'zh' => '亮眼数字' ),
		'Lĩnh vực trọng tâm' => array( 'en' => 'Core business fields', 'zh' => '核心业务领域' ),
		'Kiến tạo giá trị, đồng hành thịnh vượng' => array( 'en' => 'Creating value, prospering together', 'zh' => '创造价值，共享繁荣' ),
		'Trải qua gần 20 năm hình thành và phát triển, QP Holdings từng bước khẳng định năng lực trong lĩnh vực đầu tư tài chính, phát triển Bất động sản thương mại và Bất động sản khu công nghiệp với nhiều dự án quy mô tại các khu vực trọng điểm.' => array( 'en' => 'Over nearly 20 years of growth, QP Holdings has progressively demonstrated its capability in financial investment, commercial real estate, and industrial real estate, with numerous large-scale projects in strategic regions.', 'zh' => '近20年的发展历程中，QP Holdings 在金融投资、商业地产及工业地产领域不断展现实力，并在战略性区域落地众多大型项目。' ),
		'Hàng ngàn sản phẩm đã được phát triển và đưa ra thị trường, góp phần kiến tạo giá trị đầu tư bền vững, nâng cao chất lượng sống và gia tăng hiệu quả khai thác cho khách hàng, đối tác và nhà đầu tư.' => array( 'en' => 'Thousands of products have been developed and brought to market, contributing to sustainable investment value, enhanced quality of life, and stronger asset performance for clients, partners, and investors.', 'zh' => '已开发并推向市场的产品数以千计，为客户、合作伙伴和投资者带来可持续的投资价值，提升生活品质并增强资产运营效益。' ),
		'QP Holdings không ngừng mở rộng quỹ đất, nâng cao năng lực quản trị và phát triển hệ sinh thái đầu tư theo định hướng chuyên nghiệp, minh bạch và tăng trưởng dài hạn.' => array( 'en' => 'QP Holdings continues to expand its land bank, enhance governance, and develop its investment ecosystem with a professional, transparent, and long-term-growth orientation.', 'zh' => 'QP Holdings 持续扩大土地储备、提升治理水平，并以专业、透明、长期增长为导向打造投资生态。' ),
		'HÀNH TRÌNH KIẾN TẠO GIÁ TRỊ - ĐỒNG HÀNH CÙNG PHÁT TRIỂN' => array( 'en' => 'A JOURNEY OF CREATING VALUE — GROWING TOGETHER', 'zh' => '创造价值 · 共同成长之路' ),
		'Lịch sử hình thành' => array( 'en' => 'Our History', 'zh' => '发展历程' ),
		'SỨ MỆNH - TẦM NHÌN - GIÁ TRỊ CỐT LÕI' => array( 'en' => 'MISSION — VISION — CORE VALUES', 'zh' => '使命 · 愿景 · 核心价值' ),
		'Không ngừng tiên phong kiến tạo giá trị đầu tư, tăng trưởng bền vững và thịnh vượng.' => array( 'en' => 'Continually pioneering investment value, sustainable growth, and shared prosperity.', 'zh' => '持续引领投资价值，实现可持续增长与共同繁荣。' ),
		'Không ngừng tiên phong' => array( 'en' => 'Continually pioneering', 'zh' => '持续引领' ),
		'kiến tạo' => array( 'en' => 'creating', 'zh' => '创造' ),
		'giá trị đầu tư,' => array( 'en' => 'investment value,', 'zh' => '投资价值，' ),
		'tăng trưởng bền vững và thịnh vượng.' => array( 'en' => 'sustainable growth and shared prosperity.', 'zh' => '可持续增长与共同繁荣。' ),
		'QP Holdings phát triển theo định hướng đầu tư bền vững, tập trung vào đầu tư tài chính, bất động sản và khu công nghiệp.' => array( 'en' => 'QP Holdings grows with a sustainable-investment direction, focused on financial investment, real estate, and industrial parks.', 'zh' => 'QP Holdings 以可持续投资为发展方向，聚焦金融投资、房地产及工业园区领域。' ),
		'Doanh nghiệp đề cao năng lực triển khai, hiệu quả đầu tư và giá trị dài hạn.' => array( 'en' => 'The Company prioritises execution capability, investment efficiency, and long-term value creation.', 'zh' => '企业重视项目执行力、投资效率以及长期价值。' ),
		'Sứ mệnh' => array( 'en' => 'Mission', 'zh' => '使命' ),
		'Kiến tạo các giá trị đầu tư hiệu quả thông qua phát triển dự án và mở rộng hệ sinh thái đầu tư. Mang đến lợi ích bền vững cho khách hàng, đối tác, cổ đông và cộng đồng.' => array( 'en' => 'Create effective investment value through project development and an expanding investment ecosystem, delivering sustainable benefits for clients, partners, shareholders, and the community.', 'zh' => '通过项目开发与投资生态的不断拓展，创造高效的投资价值，为客户、合作伙伴、股东及社会持续带来收益。' ),
		'Tầm nhìn' => array( 'en' => 'Vision', 'zh' => '愿景' ),
		'Trở thành doanh nghiệp đầu tư uy tín tại Việt Nam trong lĩnh vực Tài chính, Bất động sản và Khu công nghiệp. Không ngừng nâng cao năng lực quản trị, hiệu quả đầu tư và vị thế thương hiệu.' => array( 'en' => 'Become Vietnam’s trusted investment partner across Finance, Real Estate, and Industrial Parks while continually elevating governance, investment performance, and brand stature.', 'zh' => '成为越南金融、房地产与工业园区领域值得信赖的投资企业，持续提升治理水平、投资绩效及品牌地位。' ),
		'Lấy uy tín và năng lực thực thi làm nền tảng cho mọi hoạt động đầu tư và phát triển. Đề cao minh bạch, hiệu quả hợp tác và giá trị đồng hành dài hạn.' => array( 'en' => 'We build every investment and development activity on credibility and execution capability, championing transparency, collaborative effectiveness, and the value of long-term partnership.', 'zh' => '以信誉和执行力作为一切投资与发展活动的基础，崇尚透明、高效合作及长期同行的价值。' ),
		'Hình ảnh QP Holdings' => array( 'en' => 'QP Holdings images', 'zh' => 'QP Holdings 图像' ),
		'QP Holdings – Văn phòng và đội ngũ' => array( 'en' => 'QP Holdings — office and team', 'zh' => 'QP Holdings — 办公区与团队' ),
		'QP Holdings – Dự án bất động sản' => array( 'en' => 'QP Holdings — real estate projects', 'zh' => 'QP Holdings — 房地产项目' ),
		'QP Holdings – Hạ tầng khu công nghiệp' => array( 'en' => 'QP Holdings — industrial park infrastructure', 'zh' => 'QP Holdings — 工业园区基础设施' ),
		'QP Holdings – Đầu tư tài chính' => array( 'en' => 'QP Holdings — financial investment', 'zh' => 'QP Holdings — 金融投资' ),

		/* ── Contact page ── */
		'Liên hệ với chúng tôi' => array( 'en' => 'Contact us', 'zh' => '联系我们' ),
		'QP Holdings sẵn sàng chia sẻ và cung cấp thông tin với bạn' => array( 'en' => 'QP Holdings is ready to share information and connect with you.', 'zh' => 'QP Holdings 愿意与您分享信息、共建联系。' ),
		'Thông tin công ty' => array( 'en' => 'Company information', 'zh' => '公司信息' ),
		'Hãy liên hệ với chúng tôi qua các thông tin sau :' => array( 'en' => 'Reach out to us through the following channels:', 'zh' => '请通过以下方式与我们联系：' ),
		'Theo dõi các phương tiện truyền thông của chúng tôi' => array( 'en' => 'Follow our media channels', 'zh' => '关注我们的社交平台' ),
		'Gửi tin nhắn cho chúng tôi' => array( 'en' => 'Send us a message', 'zh' => '给我们留言' ),
		'Họ tên' => array( 'en' => 'Full name', 'zh' => '姓名' ),
		'Vui lòng nhập họ tên' => array( 'en' => 'Please enter your full name', 'zh' => '请输入您的姓名' ),
		'Vui lòng nhập email' => array( 'en' => 'Please enter your email', 'zh' => '请输入您的电子邮箱' ),
		'Số điện thoại' => array( 'en' => 'Phone number', 'zh' => '电话号码' ),
		'Nhập số điện thoại' => array( 'en' => 'Enter your phone number', 'zh' => '请输入电话号码' ),
		'Câu hỏi của bạn' => array( 'en' => 'Your enquiry', 'zh' => '您的咨询' ),
		'Vui lòng chọn câu hỏi' => array( 'en' => 'Please select an option', 'zh' => '请选择一项' ),
		'Thông tin dự án' => array( 'en' => 'Project information', 'zh' => '项目信息' ),
		'Khác' => array( 'en' => 'Other', 'zh' => '其他' ),
		'Bình luận' => array( 'en' => 'Message', 'zh' => '留言内容' ),
		'Thông tin liên hệ và biểu mẫu' => array( 'en' => 'Contact information and form', 'zh' => '联系信息与表单' ),
		'Bản đồ vị trí QP Holdings' => array( 'en' => 'QP Holdings location map', 'zh' => 'QP Holdings 位置地图' ),
		'Hãy cho chúng tôi biết bạn đang tìm kiếm thông tin gì' => array( 'en' => 'Let us know what information you’re looking for', 'zh' => '请告诉我们您希望了解的信息' ),
		'Gửi' => array( 'en' => 'Send', 'zh' => '发送' ),
		'Tìm chúng tôi trên google maps' => array( 'en' => 'Find us on Google Maps', 'zh' => '在 Google 地图上查找' ),
		'Khi hiện bản đồ ra, bạn có thể phóng to và thu nhỏ để có thể nhìn rõ hơn. Hãy thoải mái sử dụng nó để tìm đường đến chỗ chúng tôi' => array( 'en' => 'Once the map is displayed, you can zoom in and out for a clearer view. Feel free to use it to find your way to us.', 'zh' => '地图加载后，您可以自由缩放以查看详情，方便您找到我们。' ),
		'Email' => array( 'en' => 'Email', 'zh' => '电子邮箱' ),

		/* ── News page (tintuc) ── */
		'Cập nhật tin tức, hoạt động doanh nghiệp, thị trường và quan hệ nhà đầu tư từ QP Holdings.' => array( 'en' => 'Stay updated with QP Holdings news, corporate activities, market insights, and investor relations.', 'zh' => '获取 QP Holdings 新闻、企业动态、市场观点和投资者关系信息。' ),
		'Lọc theo danh mục' => array( 'en' => 'Filter by category', 'zh' => '按分类筛选' ),
		'Chưa có bài viết nào.' => array( 'en' => 'No articles are available yet.', 'zh' => '暂无文章。' ),

		/* ── Projects page (du-an) ── */
		'Dự án tiêu biểu' => array( 'en' => 'Featured Projects', 'zh' => '精选项目' ),
		'DỰ ÁN QP HOLDINGS' => array( 'en' => 'QP HOLDINGS PROJECTS', 'zh' => 'QP HOLDINGS 项目' ),
		'Khám phá hệ sinh thái dự án bất động sản, khu đô thị, khu công nghiệp và các dự án tiêu biểu của QP Holdings.' => array( 'en' => 'Discover QP Holdings’ portfolio of real estate, urban, industrial, and flagship projects.', 'zh' => '探索 QP Holdings 的房地产、城市、工业及重点项目组合。' ),
		'Khám phá ngay lĩnh vực Bất động sản' => array( 'en' => 'Explore the Real Estate field now', 'zh' => '立即探索房地产业务' ),
		'Khám phá ngay lĩnh vực Tài chính' => array( 'en' => 'Explore the Financial field now', 'zh' => '立即探索金融业务' ),
		'Khám phá ngay lĩnh vực Khu Công nghiệp' => array( 'en' => 'Explore the Industrial Park field now', 'zh' => '立即探索工业园区业务' ),

		/* ── Generic UI ── */
		'tiêu biểu' => array( 'en' => 'Featured', 'zh' => '精选' ),

		/* ── Full footer addresses (template-rendered, hardcoded VI) ── */
		'Tầng L16, Tòa nhà Vietcombank, Số 5 Công Trường Mê Linh, Phường Sài Gòn, Thành phố Hồ Chí Minh' => array(
			'en' => 'Level L16, Vietcombank Tower, 5 Cong Truong Me Linh, Sai Gon Ward, Ho Chi Minh City',
			'zh' => 'L16层，Vietcombank Tower，Công Trường Mê Linh 5号，Sài Gòn坊，胡志明市',
		),
		'Nhà phố F1, Đường Huỳnh Thúc Kháng, Phường Bình Dương, Thành phố Hồ Chí Minh' => array(
			'en' => 'Townhouse F1, Huynh Thuc Khang Street, Binh Duong Ward, Ho Chi Minh City',
			'zh' => 'F1联排别墅，Huỳnh Thúc Kháng街，Bình Dương坊，胡志明市',
		),
		/* About paragraph prefix that breaks across DOM nodes (<strong> wraps part of it). */
		'QP Holdings hoạt động trong các lĩnh vực trọng tâm gồm:' => array(
			'en' => 'QP Holdings operates across three focus areas:',
			'zh' => 'QP Holdings 主要业务涵盖三大领域：',
		),
		'Công ty Cổ phần QP Holdings định hướng phát triển trở thành doanh nghiệp đầu tư đa ngành uy tín tại Việt Nam, tập trung vào các lĩnh vực trọng điểm gồm:' => array(
			'en' => 'QP Holdings Joint Stock Company is on its way to becoming Vietnam’s trusted multi-sector investment group, focused on key priority areas:',
			'zh' => 'QP Holdings 股份公司致力于发展成为越南值得信赖的多元化投资企业，重点聚焦以下领域：',
		),

		/* News page heading "Truyền thông & tin tức" — broken across <span>s */
		'Truyền thông' => array( 'en' => 'Media', 'zh' => '媒体' ),
		' &amp; tin tức' => array( 'en' => ' &amp; News', 'zh' => '与新闻' ),
		' & tin tức' => array( 'en' => ' & News', 'zh' => '与新闻' ),

		/* About-page timeline (2007–2026 history) */
		'Tiền thân của QP Holdings được thành lập trong lĩnh vực cơ khí và xây dựng, đặt nền móng cho năng lực vận hành và phát triển doanh nghiệp.' => array(
			'en' => 'The predecessor of QP Holdings was established in the mechanical and construction sector, laying the operational foundation for the business.',
			'zh' => 'QP Holdings 的前身在机械与建筑领域成立，为日后企业运营与发展奠定基础。',
		),
		'Mở rộng sang lĩnh vực sản xuất, chế biến chè và nông sản với nhà máy công suất 2.000 tấn/năm, từng bước đa dạng hóa hoạt động kinh doanh.' => array(
			'en' => 'Expanded into tea and agricultural processing with a 2,000-ton/year factory, progressively diversifying the business.',
			'zh' => '拓展至茶叶与农产品加工领域，建成年产 2,000 吨工厂，逐步推动业务多元化。',
		),
		'Chuyển đổi sang mô hình công ty cổ phần với tên gọi Công ty Cổ phần Chè Hiệp Khánh, định hướng phát triển trong lĩnh vực chế biến và xuất khẩu chè.' => array(
			'en' => 'Converted to a joint-stock model as Hiep Khanh Tea Joint Stock Company, focused on tea processing and exports.',
			'zh' => '转型为股份公司，更名为 Hiep Khanh Tea 股份公司，专注于茶叶加工与出口业务。',
		),
		'Trở thành thành viên Hiệp hội Chè Việt Nam, mở rộng kết nối thị trường và nâng cao năng lực cạnh tranh trên thị trường trong nước và quốc tế.' => array(
			'en' => 'Became a member of the Vietnam Tea Association, expanding market connections and strengthening competitiveness in domestic and international markets.',
			'zh' => '加入越南茶叶协会，拓展市场渠道，提升在国内外市场的竞争力。',
		),
		'Đạt chứng nhận ISO 22000:2005 cho toàn bộ quy trình sản xuất, khẳng định năng lực quản lý chất lượng và vận hành chuyên nghiệp.' => array(
			'en' => 'Achieved ISO 22000:2005 certification for the entire production process, demonstrating quality management capability and professional operations.',
			'zh' => '通过 ISO 22000:2005 全流程生产认证，彰显质量管理与专业运营能力。',
		),
		'Hoàn thiện nền tảng quản trị doanh nghiệp khi hồ sơ đăng ký công ty đại chúng được Ủy ban Chứng khoán Nhà nước phê duyệt theo Văn bản số 2537/UBCK-QLPH.' => array(
			'en' => 'Completed the corporate governance framework when the public-company registration was approved by the State Securities Commission under Document No. 2537/UBCK-QLPH.',
			'zh' => '公开发行公司登记申请获国家证券委员会第 2537/UBCK-QLPH 号文批准，企业治理体系进一步完善。',
		),
		'Cổ phiếu Công ty chính thức niêm yết trên Sở Giao dịch Chứng khoán Hà Nội với mã chứng khoán HKT, đánh dấu bước phát triển mới về uy tín và năng lực huy động vốn.' => array(
			'en' => 'The Company’s shares were officially listed on the Hanoi Stock Exchange under the ticker HKT, marking a new milestone in credibility and capital-raising capability.',
			'zh' => '公司股票以 HKT 代码正式在河内证券交易所挂牌，标志着信誉与融资能力迈入新阶段。',
		),
		'Mở rộng hoạt động sang các lĩnh vực nông sản, lâm sản và hợp tác phát triển dịch vụ du lịch cùng các đối tác địa phương.' => array(
			'en' => 'Expanded into agricultural and forestry products and partnered with local players to develop tourism services.',
			'zh' => '业务延伸至农产品、林业产品领域，并与本地合作伙伴携手发展旅游服务。',
		),
		'Thực hiện tái cấu trúc toàn diện về thương hiệu, mô hình hoạt động và định hướng phát triển trong lĩnh vực Đầu tư tài chính và Bất động sản; chính thức đổi tên thành Công ty Cổ phần Đầu tư QP Xanh.' => array(
			'en' => 'Carried out a comprehensive restructuring of the brand, operating model, and strategic direction in Financial Investment and Real Estate; officially renamed QP Xanh Investment Joint Stock Company.',
			'zh' => '在金融投资与房地产领域对品牌、运营模式与发展方向进行全面重构，并正式更名为 QP Xanh 投资股份公司。',
		),
		'Ngày 01/10/2025, QP Holdings tổ chức Lễ khởi công Dự án QP Green Park quy mô hơn 13,7ha tại Phường Bình Cơ TP. Hồ Chí Minh, đánh dấu bước phát triển quan trọng trong lĩnh vực Bất động sản.' => array(
			'en' => 'On 1 October 2025, QP Holdings held the ground-breaking ceremony for the 13.7-hectare QP Green Park project in Binh Co Ward, Ho Chi Minh City, marking a key milestone in the real estate segment.',
			'zh' => '2025 年 10 月 1 日，QP Holdings 在胡志明市 Bình Cơ 坊举行 QP Green Park 项目（占地逾 13.7 公顷）开工典礼，标志着房地产业务迈出关键一步。',
		),
		'Chính thức đổi tên thành Công Ty Cổ Phần QP Holdings, đồng thời đẩy mạnh hợp tác phát triển các dự án căn hộ cao cấp QP Luxia One và QP Luxia Premier tại Phường Bình Dương TP. Hồ Chí Minh với quy mô hơn 2ha và 2.079 căn hộ.' => array(
			'en' => 'Officially renamed QP Holdings Joint Stock Company and accelerated partnerships to develop the premium QP Luxia One and QP Luxia Premier apartment projects in Binh Duong Ward, Ho Chi Minh City, totalling more than 2 hectares and 2,079 units.',
			'zh' => '正式更名为 QP Holdings 股份公司，并加速推进位于胡志明市 Bình Dương 坊的高端公寓项目 QP Luxia One 与 QP Luxia Premier，占地逾 2 公顷，规划 2,079 套住宅。',
		),

		/* ── Front-page Hero bar (3 pillars) ── */
		'Bất động sản Dân Dụng' => array(
			'en' => 'Residential Real Estate',
			'zh' => '住宅房地产',
		),
		'Đầu tư và phát triển các khu dân cư, căn hộ cao tầng, văn phòng và trung tâm thương mại hiện đại, kiến tạo không gian sống và làm việc chất lượng, bền vững.' => array(
			'en' => 'Investing in and developing residential communities, high-rise apartments, modern offices, and commercial centres to create quality, sustainable living and working spaces.',
			'zh' => '投资开发住宅社区、高层公寓、现代化办公和商业中心，打造优质、可持续的生活与工作空间。',
		),
		'Bất động sản Công Nghiệp' => array(
			'en' => 'Industrial Real Estate',
			'zh' => '工业房地产',
		),
		'Đầu tư và phát triển khu công nghiệp, cụm công nghiệp và hạ tầng logistics đồng bộ, đáp ứng nhu cầu sản xuất, kinh doanh và thu hút đầu tư.' => array(
			'en' => 'Investing in and developing industrial parks, industrial clusters, and integrated logistics infrastructure to meet production, business, and investment attraction needs.',
			'zh' => '投资开发工业园区、产业集群及一体化物流基础设施，满足生产、经营及招商引资需求。',
		),
		'Tối ưu nguồn vốn, quản trị danh mục đầu tư hiệu quả và khai thác các cơ hội tăng trưởng bền vững, gia tăng giá trị dài hạn.' => array(
			'en' => 'Optimising capital, managing investment portfolios effectively, and capturing sustainable growth opportunities to enhance long-term value.',
			'zh' => '优化资本，高效管理投资组合，把握可持续增长机遇，持续提升长期价值。',
		),

		/* ── Front-page about section heading fragments ── */
		'Bất động sản Dân dụng' => array(
			'en' => 'Residential Real Estate',
			'zh' => '住宅房地产',
		),
		'Bất động sản Công nghiệp' => array(
			'en' => 'Industrial Real Estate',
			'zh' => '工业房地产',
		),
		'QP Holdings' => array(
			'en' => 'QP Holdings',
			'zh' => 'QP Holdings',
		),

		/* ── Front-page gallery alt texts ── */
		'QP Holdings - Khoanh khac dac biet 1' => array(
			'en' => 'QP Holdings — featured moment 1',
			'zh' => 'QP Holdings — 精彩时刻 1',
		),
		'QP Holdings - Khoanh khac dac biet 2' => array(
			'en' => 'QP Holdings — featured moment 2',
			'zh' => 'QP Holdings — 精彩时刻 2',
		),
		'QP Holdings - Khoanh khac dac biet 3' => array(
			'en' => 'QP Holdings — featured moment 3',
			'zh' => 'QP Holdings — 精彩时刻 3',
		),
		'QP Holdings - Khoanh khac dac biet 4' => array(
			'en' => 'QP Holdings — featured moment 4',
			'zh' => 'QP Holdings — 精彩时刻 4',
		),

		/* ── Front-page fallback news alt texts ── */
		'Bai viet ve phan bo danh muc dau tu' => array(
			'en' => 'Article about investment portfolio allocation',
			'zh' => '关于投资组合配置的文章',
		),
		'Bai viet ve ASEANSC dinh gia' => array(
			'en' => 'Article about ASEANSC valuation',
			'zh' => '关于 ASEANSC 估值的文章',
		),
		'Bai viet le khoi cong QP Green Park' => array(
			'en' => 'Article about QP Green Park groundbreaking ceremony',
			'zh' => '关于 QP Green Park 奠基仪式的文章',
		),

		/* ── Partners alt texts ── */
		'Doi tac 1' => array( 'en' => 'Partner 1', 'zh' => '合作伙伴 1' ),
		'Doi tac 2' => array( 'en' => 'Partner 2', 'zh' => '合作伙伴 2' ),
		'Doi tac 3' => array( 'en' => 'Partner 3', 'zh' => '合作伙伴 3' ),
		'Doi tac 4' => array( 'en' => 'Partner 4', 'zh' => '合作伙伴 4' ),
		'Doi tac 5' => array( 'en' => 'Partner 5', 'zh' => '合作伙伴 5' ),
		'Doi tac 6' => array( 'en' => 'Partner 6', 'zh' => '合作伙伴 6' ),
		'Doi tac 7' => array( 'en' => 'Partner 7', 'zh' => '合作伙伴 7' ),
		'Doi tac 8' => array( 'en' => 'Partner 8', 'zh' => '合作伙伴 8' ),

		/* ── About page (gioi-thieu) additional strings ── */
		'QP Holdings - Khu cong nghiep va nha may' => array(
			'en' => 'QP Holdings — industrial park and factory',
			'zh' => 'QP Holdings — 工业园区和工厂',
		),
		'QP Holdings - Nha xuong QP' => array(
			'en' => 'QP Holdings — QP factory',
			'zh' => 'QP Holdings — QP 厂房',
		),
		'QP Holdings - Du an An Binh 7' => array(
			'en' => 'QP Holdings — An Binh 7 project',
			'zh' => 'QP Holdings — An Bình 7 项目',
		),
		'QP Holdings - Cong khu cong nghiep An Binh 7' => array(
			'en' => 'QP Holdings — An Binh 7 industrial park gate',
			'zh' => 'QP Holdings — An Bình 7 工业园区大门',
		),
		'QP Holdings - Du an' => array(
			'en' => 'QP Holdings — project',
			'zh' => 'QP Holdings — 项目',
		),
		'QP Holdings - Tang truong ben vung' => array(
			'en' => 'QP Holdings — sustainable growth',
			'zh' => 'QP Holdings — 可持续增长',
		),
		'QP Holdings - Cam kết đồng hành và minh bạch' => array(
			'en' => 'QP Holdings — partnership and transparency commitments',
			'zh' => 'QP Holdings — 同行与透明的承诺',
		),
		'Giới thiệu QP Holdings' => array(
			'en' => 'About QP Holdings',
			'zh' => '关于 QP Holdings',
		),
		'Đọc chi tiết' => array(
			'en' => 'Read details',
			'zh' => '阅读详情',
		),
		'Đọc chi tiết bài viết' => array(
			'en' => 'Read more',
			'zh' => '继续阅读',
		),
		'Cam kết của QP Holdings' => array(
			'en' => 'QP Holdings commitments',
			'zh' => 'QP Holdings 承诺',
		),
		'Dau tu - Phat trien Bat dong san' => array(
			'en' => 'Investment - Real Estate Development',
			'zh' => '投资 - 房地产开发',
		),
		'Phat trien Khu Cong nghiep' => array(
			'en' => 'Industrial Park Development',
			'zh' => '工业园区开发',
		),
		'Dau tu Tai chinh' => array(
			'en' => 'Financial Investment',
			'zh' => '金融投资',
		),
		'Tin tức QP Holdings' => array(
			'en' => 'QP Holdings News',
			'zh' => 'QP Holdings 新闻',
		),
		'Bài viết nổi bật' => array(
			'en' => 'Featured article',
			'zh' => '精选文章',
		),
		'Sứ mệnh, Tầm nhìn, Giá trị cốt lõi' => array(
			'en' => 'Mission, Vision, Core Values',
			'zh' => '使命、愿景、核心价值',
		),
		'Lịch sử hình thành' => array(
			'en' => 'History',
			'zh' => '发展历程',
		),

		/* ── Front-page fallback news article titles ── */
		'Đừng "bỏ trứng vào một giỏ": Bật mí cách phân bổ danh mục đầu tư chứng' => array(
			'en' => 'Don\'t "put all your eggs in one basket": Tips on portfolio allocation strategy',
			'zh' => '不要「把所有鸡蛋放在一个篮子里」：投资组合配置策略揭秘',
		),
		'ASEANSC: Định giá vẫn hấp dẫn so với các chu kỳ bơm tiền trong quá khứ,' => array(
			'en' => 'ASEANSC: Valuations remain attractive compared with past liquidity cycles',
			'zh' => 'ASEANSC：与历史流动性周期相比，估值依然具有吸引力',
		),
		'Lễ khởi công QP Green Park: Hành trình khởi tạo cuộc sống xanh' => array(
			'en' => 'QP Green Park groundbreaking: The journey to create a green lifestyle',
			'zh' => 'QP Green Park 奠基庆典：开启绿色生活之旅',
		),

		/* ── Single post/news template strings ── */
		'TIN TỨC & CẬP NHẬT' => array(
			'en' => 'NEWS & UPDATES',
			'zh' => '新闻与更新',
		),
		'Về danh sách bài viết' => array(
			'en' => 'Back to article list',
			'zh' => '返回文章列表',
		),
		'Bài viết liên quan' => array(
			'en' => 'Related articles',
			'zh' => '相关文章',
		),
		'Về danh sách' => array(
			'en' => 'Back to list',
			'zh' => '返回列表',
		),
	);

	$out = array();
	foreach ( $common as $source => $translations ) {
		$out[ $source ] = $translations[ $lang ] ?? $source;
	}

	return $out;
}

function qp_holdings_translate_static_html( string $html ): string {
	$lang = qp_holdings_current_language();
	$map  = qp_holdings_static_translation_map( $lang );
	if ( empty( $map ) ) {
		return $html;
	}

	if ( function_exists( 'qp_holdings_seeded_investor_doc_translations' ) ) {
		foreach ( qp_holdings_seeded_investor_doc_translations() as $slug => $translations ) {
			if ( empty( $translations[ $lang ]['title'] ) ) {
				continue;
			}
			$post = get_page_by_path( $slug, OBJECT, 'qp_investor_doc' );
			if ( ! $post instanceof WP_Post ) {
				continue;
			}
			$html = str_replace( $post->post_title, $translations[ $lang ]['title'], $html );
		}
	}

	$html = strtr( $html, $map );

	$regex_map = array(
		'/Tầng\s+L16,\s*Tòa\s+nhà\s+Vietcombank,\s*Số\s+5\s+Công\s+Trường\s+Mê\s+Linh,\s*Phường\s+Sài\s+Gòn,\s*Ho\s+Chi\s+Minh\s+City,\s*Vietnam/u' => array(
			'en' => 'Level L16, Vietcombank Tower, 5 Cong Truong Me Linh, Sai Gon Ward, Ho Chi Minh City, Vietnam',
			'zh' => 'L16层，Vietcombank Tower，Công Trường Mê Linh 5号，Sài Gòn坊，胡志明市，越南',
		),
		'/Tầng\s+L16,\s*Tòa\s+nhà\s+Vietcombank,\s*Số\s+5\s+Công\s+Trường\s+Mê\s+Linh,\s*Phường\s+Sài\s+Gòn,\s*胡志明市,\s*越南/u' => array(
			'en' => 'Level L16, Vietcombank Tower, 5 Cong Truong Me Linh, Sai Gon Ward, Ho Chi Minh City, Vietnam',
			'zh' => 'L16层，Vietcombank Tower，Công Trường Mê Linh 5号，Sài Gòn坊，胡志明市，越南',
		),
	);

	foreach ( $regex_map as $pattern => $translations ) {
		if ( isset( $translations[ $lang ] ) ) {
			$html = preg_replace( $pattern, $translations[ $lang ], $html ) ?? $html;
		}
	}

	if ( 'en' === $lang ) {
		$romanize = array(
			'Công Trường Mê Linh' => 'Cong Truong Me Linh',
			'Sài Gòn' => 'Sai Gon',
			'Huỳnh Thúc Kháng' => 'Huynh Thuc Khang',
			'Bình Dương' => 'Binh Duong',
			'Bến Lức' => 'Ben Luc',
			'Trung Lương' => 'Trung Luong',
			'Long Hậu' => 'Long Hau',
			'Cần Giuộc' => 'Can Giuoc',
			'Hiệp Phước' => 'Hiep Phuoc',
			'Long Thành' => 'Long Thanh',
			'Cửa Lấp' => 'Cua Lap',
			'Bà Rịa' => 'Ba Ria',
			'Vũng Tàu' => 'Vung Tau',
			'Long Hải' => 'Long Hai',
			'Mỹ Phước' => 'My Phuoc',
			'Bến Cát' => 'Ben Cat',
			'Tân Vạn' => 'Tan Van',
			'Ninh Thuận' => 'Ninh Thuan',
			'Thuận Bắc' => 'Thuan Bac',
			'Nhơn Trạch' => 'Nhon Trach',
			'Đồng Nai' => 'Dong Nai',
			'Hai Bà Trưng' => 'Hai Ba Trung',
			'Phạm Minh Chính' => 'Pham Minh Chinh',
			'Việt Nam' => 'Vietnam',
			'café' => 'cafe',
		);
		$html = strtr( $html, $romanize );
	}

	return $html;
}

/**
 * Heuristic: does this text contain Vietnamese-specific characters?
 *
 * Used to detect translation copies whose body is still Vietnamese (i.e. not
 * actually translated yet) so the EN/ZH listing can avoid leaking Vietnamese
 * excerpts/titles derived from that content.
 */
function qp_holdings_text_looks_vietnamese( string $text ): bool {
	$text = trim( $text );
	if ( '' === $text ) {
		return false;
	}

	// Vietnamese-only letters (diacritics + đ) that don't occur in EN/ZH text.
	return (bool) preg_match(
		'/[ăâđêôơưĂÂĐÊÔƠƯáàảãạấầẩẫậắằẳẵặéèẻẽẹếềểễệíìỉĩịóòỏõọốồổỗộớờởỡợúùủũụứừửữựỳỷỹỵýđ]/u',
		$text
	);
}

function qp_holdings_translation_source_slug( $post ): string {
	$post_obj = $post instanceof WP_Post ? $post : get_post( $post );
	if ( ! $post_obj instanceof WP_Post ) {
		return '';
	}

	$source_slug = get_post_meta( $post_obj->ID, '_qp_source_slug', true );
	return is_string( $source_slug ) && $source_slug ? $source_slug : (string) $post_obj->post_name;
}

function qp_holdings_seeded_news_translations(): array {
	$assets   = get_template_directory_uri() . '/assets/tintuc_files/';
	$assets_i = get_template_directory_uri() . '/assets/index_files/';
	$assets_c = get_template_directory_uri() . '/assets/crawled-news/';

	return array(
		'vn-index-bung-no-thanh-khoan-ky-luc' => array(
			'en' => array(
				'title' => 'VN-Index surges on record liquidity as the market enters a new phase',
				'excerpt' => 'The market has been in an extended rally for more than a month, so a correction is needed to build a healthier base for the next growth phase.',
				'content' => '<p>VN-Index posted an exceptional session as strong capital inflows pushed matched liquidity on HoSE to record territory, with large-cap stocks continuing to lead. After a rapid intraday rise, profit-taking created volatility, but end-of-day demand still helped the market close in positive territory.</p>'
					. qp_holdings_news_figure_html( $assets_c . 'vn-index-chart.png', 'VN-Index movements during this high-liquidity session were compiled from market data.' )
					. '<h2>Strong liquidity, but clear divergence</h2><p>What stood out was not only the rise of the broad index, but also the structure of capital flows. VN30 outperformed VN-Index, showing that larger pools of money favored pillar stocks such as banks, steel, and selected real estate names. Meanwhile, many overheated stocks began to consolidate, reflecting more cautious investor sentiment after an extended rally.</p>'
					. '<p>According to aggregated market bulletins, VN-Index was at one point up nearly 57 points versus the reference level before trimming gains as profit-taking intensified. Even so, the quick return of capital showed that liquidity remains a critical support for the current trend.</p>'
					. '<h2>FOMO risk and support zones to watch</h2><p>Analysts believe the market has been climbing for more than a month, so a technical pullback is necessary to build a new consolidation base. The nearest support zone is often cited around 1,484–1,500 points, which will also help investors assess the resilience of capital flows after sharp swings.</p>'
					. qp_holdings_news_figure_html( $assets_c . 'vn-index-market.webp', 'High liquidity creates opportunities, but it also increases short-term volatility.' )
					. '<h2>The QP Holdings perspective</h2><p>With a long-term investment approach, QP Holdings focuses on the quality of capital flows rather than index levels alone. Sectors supported by macro fundamentals such as infrastructure, urban real estate, banking, and energy are still considered to have upside if earnings improve clearly. Investors should avoid chasing momentum during euphoric sessions, while actively managing allocations and protecting gains on positions that have already rallied strongly.</p>'
					. '<blockquote>Record liquidity is a positive signal, but a more suitable strategy at this stage is to deploy capital on pullbacks and prioritize companies with sustainable growth fundamentals.</blockquote>'
					. qp_holdings_news_source_html( 'QP Xanh / Nhịp sống thị trường', 'https://qpxanh.com.vn/vn-index-bung-no-voi-thanh-khoan-ky-luc-thi-truong-buoc-vao-song-tang-moi-hay-chi-la-nhip-hung-phan/' ),
			),
			'zh' => array(
				'title' => 'VN-Index 在创纪录流动性推动下大涨，市场进入新阶段',
				'excerpt' => '市场已连续上涨一个多月，阶段性调整有助于为下一轮增长建立更稳固的基础。',
				'content' => '<p>VN-Index 录得一场异常活跃的交易日，强劲资金流入推动 HoSE 撮合成交量升至历史高位，大盘股继续发挥领涨作用。盘中快速拉升后，获利回吐带来震荡，但尾盘买盘仍帮助市场守住涨势。</p>'
					. qp_holdings_news_figure_html( $assets_c . 'vn-index-chart.png', '本次放量行情中的 VN-Index 走势根据市场数据整理而成。' )
					. '<h2>资金强劲，但分化明显</h2><p>值得关注的不仅是大盘指数上涨本身，更是资金流向结构。VN30 表现优于 VN-Index，说明大资金更偏好银行、钢铁及部分地产等权重股。与此同时，许多前期涨幅过大的个股开始震荡整理，反映出在持续上涨后投资者情绪趋于谨慎。</p>'
					. '<p>根据市场简报汇总数据，VN-Index 盘中一度较基准上涨近 57 点，随后因获利回吐加剧而收窄涨幅。即便如此，资金迅速回流仍表明流动性依旧是当前趋势的重要支撑。</p>'
					. '<h2>需关注的 FOMO 风险与支撑区间</h2><p>分析师认为市场已持续上涨一个多月，因此技术性回调有助于构建新的整理基础。近期支撑区域通常被提及在 1,484–1,500 点附近，这一区间也将帮助投资者判断大幅波动后资金面的健康程度。</p>'
					. qp_holdings_news_figure_html( $assets_c . 'vn-index-market.webp', '高流动性带来机会，但也会放大短期波动。' )
					. '<h2>QP Holdings 视角</h2><p>在长期投资导向下，QP Holdings 更重视资金流质量，而非单纯跟随指数点位。若业绩改善明确，基础设施、城市地产、银行与能源等受宏观基本面支撑的板块仍被认为具备上行空间。投资者应避免在情绪亢奋时追涨，同时管理仓位并为已大幅上涨的持仓设置利润保护。</p>'
					. '<blockquote>创纪录的流动性是积极信号，但在当前阶段，更合适的策略是在回调时分批布局，并优先选择具备可持续增长基础的企业。</blockquote>'
					. qp_holdings_news_source_html( 'QP Xanh / Nhịp sống thị trường', 'https://qpxanh.com.vn/vn-index-bung-no-voi-thanh-khoan-ky-luc-thi-truong-buoc-vao-song-tang-moi-hay-chi-la-nhip-hung-phan/' ),
			),
		),
		'bon-ong-lon-cong-nghe-thai-carbon-vi-ai' => array(
			'en' => array(
				'title' => 'Carbon emissions from four tech giants jump 2.5x because of AI',
				'excerpt' => 'Carbon emissions from major technology groups are rising sharply as AI infrastructure expands.',
				'content' => '<p>A report from the International Telecommunication Union (ITU), a United Nations agency, shows that direct and indirect carbon emissions from Amazon, Microsoft, Meta, and Alphabet increased sharply from 2020 to 2023. The main driver is the expansion of artificial intelligence, data centers, and high-performance computing infrastructure.</p>'
					. qp_holdings_news_figure_html( $assets . 'x2.webp', 'AI is creating substantial demand for data centers, electricity, and cooling infrastructure.' )
					. '<h2>Energy pressure behind the AI wave</h2><p>Published data indicates that Scope 1 and Scope 2 emissions at large technology companies rose by roughly 150% on average. Amazon recorded the highest increase, followed by Microsoft, Meta, and Alphabet. Microsoft alone reportedly saw emissions from electricity usage increase from 4.3 million tons in 2020 to nearly 10 million tons by 2024.</p>'
					. '<p>As AI models become larger, demand for electricity, cooling water, and semiconductor hardware is accelerating. This is no longer just a technology-sector issue; it also affects energy planning, digital infrastructure, and broader sustainability strategies across many economies.</p>'
					. '<h2>How technology companies are responding</h2><p>Several companies have increased renewable-energy purchases, carbon-credit offsets, nuclear-energy research, and liquid-cooling solutions at chip level to reduce power consumption inside data centers. Even so, the ITU stresses that many net-zero targets still need to be converted into measurable, independently verified actions.</p>'
					. '<h2>Implications for ESG strategy</h2><p>From the perspective of QP Holdings, the intersection of AI and emissions shows that technology growth must move in parallel with environmental standards, data governance, and efficient resource use. As digital infrastructure becomes part of commercial real estate, industrial parks, and smart cities, developers need to plan early for green power, cooling systems, water management, and lifecycle emissions measurement.</p>'
					. '<blockquote>AI unlocks new productivity, but long-term competitive advantage will belong to companies that balance expansion speed, energy costs, and sustainability commitments.</blockquote>'
					. qp_holdings_news_source_html( 'VnExpress / Reuters / ITU', 'https://vnexpress.net/bon-ong-lon-cong-nghe-thai-carbon-tang-gap-2-5-lan-vi-ai-4895321.html' ),
			),
			'zh' => array(
				'title' => 'AI 推动下，四大科技巨头碳排放增加 2.5 倍',
				'excerpt' => '随着人工智能基础设施持续扩张，大型科技集团的碳排放正在快速上升。',
				'content' => '<p>联合国下属机构国际电信联盟（ITU）发布的报告显示，Amazon、Microsoft、Meta 和 Alphabet 在 2020 至 2023 年期间的直接与间接碳排放显著上升。主要原因在于人工智能、数据中心以及高性能算力基础设施的持续扩张。</p>'
					. qp_holdings_news_figure_html( $assets . 'x2.webp', '人工智能正在带来对数据中心、电力和散热基础设施的巨大需求。' )
					. '<h2>AI 浪潮背后的能源压力</h2><p>公开数据显示，这些科技企业在范围 1 和范围 2 的排放平均增长约 150%。其中 Amazon 增幅最高，其后依次为 Microsoft、Meta 和 Alphabet。仅 Microsoft 一家，据称其运营用电相关排放就从 2020 年的 430 万吨增至 2024 年接近 1000 万吨。</p>'
					. '<p>随着 AI 模型规模不断扩大，对电力、冷却用水和半导体设备的需求也迅速攀升。这不仅是科技行业的问题，也将影响各国的能源规划、数字基础设施建设以及可持续发展战略。</p>'
					. '<h2>科技企业的应对方式</h2><p>部分企业正在增加可再生能源采购、碳信用抵消、核能研究以及芯片级液冷方案，以降低数据中心用电消耗。不过 ITU 强调，许多“净零排放”目标仍需转化为可量化、可独立验证的实际行动。</p>'
					. '<h2>对 ESG 战略的启示</h2><p>从 QP Holdings 的视角来看，AI 与排放问题表明，技术增长必须与环境标准、数据治理及资源利用效率并行推进。随着数字基础设施成为商业地产、工业园区和智慧城市的一部分，开发商必须尽早规划绿色电力、冷却系统、水资源管理以及全生命周期排放测量能力。</p>'
					. '<blockquote>AI 打开了新的生产力空间，但长期竞争优势将属于那些能够平衡扩张速度、能源成本与可持续承诺的企业。</blockquote>'
					. qp_holdings_news_source_html( 'VnExpress / Reuters / ITU', 'https://vnexpress.net/bon-ong-lon-cong-nghe-thai-carbon-tang-gap-2-5-lan-vi-ai-4895321.html' ),
			),
		),
		'lng-my-muon-viet-nam-thanh-trung-tam-asean' => array(
			'en' => array(
				'title' => 'Leading U.S. LNG group wants Vietnam to become ASEAN distribution hub',
				'excerpt' => 'Opening up technology investment opportunities and large-scale LNG project development in Vietnam.',
				'content' => '<p>Excelerate Energy, a U.S.-based energy group active in liquefied natural gas (LNG), has expressed interest in partnering with Vietnam to help the country become an LNG distribution hub for ASEAN. The proposal comes at a time when Vietnam is seeking stable long-term supply sources to support its energy-transition agenda.</p>'
					. qp_holdings_news_figure_html( $assets_c . 'lng-vietnam.jpg', 'A working session between Excelerate Energy representatives and the Vietnamese government on LNG cooperation. Image sourced from crawled VietnamBiz/VGP data.' )
					. '<h2>Opportunities across the LNG value chain</h2><p>According to published information, Excelerate Energy has signed an agreement with PVGas to arrange long-term LNG supply from the United States to Vietnam. The group also proposed developing LNG infrastructure, ensuring stable supply, and establishing a joint venture with Vietnamese partners to expand across the regional market.</p>'
					. '<p>Vietnam holds a favorable position within the ASEAN trade network, faces rising power demand, and is pursuing storage and distribution infrastructure that can serve both domestic and regional markets. If implemented coherently, LNG terminals, pipelines, cold-chain logistics, and gas-fired power plants could create a new value chain for cleaner energy compared with coal.</p>'
					. '<h2>Technology transfer and emissions control</h2><p>Prime Minister Pham Minh Chinh emphasized that Vietnam aims for rapid yet sustainable development without sacrificing the environment for short-term growth. That means LNG cooperation cannot stop at fuel imports; it must also include technology transfer, storage management expertise, safe operations, and emissions-reduction solutions in transport and utilization.</p>'
					. qp_holdings_news_figure_html( $assets . 'img4389-1748499461135670993135-5888-4063-1748517041.webp', 'Energy and logistics infrastructure remain strategic sectors that QP Holdings follows closely in its long-term investment strategy.' )
					. '<h2>Infrastructure investment perspective</h2><p>For QP Holdings, the LNG story opens multiple investment layers linked to industrial parks, logistics, ports, warehousing, supporting energy systems, and industrial urban development. Projects in this space require long-term capital, clear legal frameworks, and strong risk management, but when well prepared they can generate stable cash flow and enhance national competitiveness.</p>'
					. qp_holdings_news_source_html( 'VietnamBiz / VGP', 'https://vietnambiz.vn/tap-doan-nang-luong-my-muon-dua-viet-nam-tro-thanh-trung-tam-phan-phoi-lng-cua-khu-vuc-202552914520303.htm' ),
			),
			'zh' => array(
				'title' => '美国领先 LNG 集团希望越南成为东盟分销中心',
				'excerpt' => '为越南的大型 LNG 项目开发和技术投资带来新机遇。',
				'content' => '<p>美国液化天然气（LNG）领域的重要企业 Excelerate Energy 表示，希望与越南合作，推动越南成为东盟地区的 LNG 分销中心。这一提议提出之际，越南正积极寻求稳定、长期的能源供应来源，以支持其能源转型进程。</p>'
					. qp_holdings_news_figure_html( $assets_c . 'lng-vietnam.jpg', 'Excelerate Energy 与越南政府代表就 LNG 合作进行工作会谈。图片来源于抓取的 VietnamBiz/VGP 数据。' )
					. '<h2>LNG 价值链带来的机会</h2><p>根据公开信息，Excelerate Energy 已与 PVGas 签署协议，安排来自美国的长期 LNG 供应进入越南市场。该公司同时提议建设 LNG 基础设施、确保稳定供给，并与越南合作伙伴设立合资企业，以拓展区域市场。</p>'
					. '<p>越南在东盟贸易网络中具有有利区位，电力需求不断增长，并正推动仓储与分销体系建设，以同时服务国内与区域市场。如果配套推进，LNG 码头、管道、冷链物流和燃气电厂将有机会形成比煤炭更清洁的新型能源价值链。</p>'
					. '<h2>技术转移与减排要求</h2><p>越南总理 Phạm Minh Chính 强调，越南坚持快速但可持续的发展路径，不会以牺牲环境为代价换取单纯增长。因此，LNG 合作不能仅停留在燃料进口层面，还应包括技术转移、储运管理经验、安全运营和运输使用环节的减排方案。</p>'
					. qp_holdings_news_figure_html( $assets . 'img4389-1748499461135670993135-5888-4063-1748517041.webp', '能源与物流基础设施仍是 QP Holdings 长期投资战略中重点关注的方向。' )
					. '<h2>基础设施投资视角</h2><p>对 QP Holdings 而言，LNG 主题带来多层次投资机会，包括工业园区、物流、港口、仓储、配套能源系统以及工业型城市开发。这类项目需要长期资本、清晰法理基础和强风险管理能力，但准备充分后，有望形成稳定现金流并提升国家竞争力。</p>'
					. qp_holdings_news_source_html( 'VietnamBiz / VGP', 'https://vietnambiz.vn/tap-doan-nang-luong-my-muon-dua-viet-nam-tro-thanh-trung-tam-phan-phoi-lng-cua-khu-vuc-202552914520303.htm' ),
			),
		),
		'qp-holdings-cong-bo-ket-qua-quy-1-2026' => array(
			'en' => array(
				'title' => 'QP Holdings reports Q1/2026 results with revenue up 28%',
				'excerpt' => 'Consolidated Q1/2026 revenue reached VND 4,180 billion while net profit increased 31% year on year.',
				'content' => '<p>QP Holdings reported consolidated revenue of VND 4,180 billion in Q1/2026, up 28% year on year, with net profit after tax reaching VND 612 billion, up 31%. The result reflects a recovery in the real estate market, stable contributions from industrial logistics, and tighter cost discipline across the broader ecosystem.</p>'
					. qp_holdings_news_figure_html( $assets_i . 'about-banner.jpg', 'QP Holdings continues to develop a diversified ecosystem spanning real estate, construction, services, and industrial investment.' )
					. '<h2>Key revenue contributors</h2><p>Residential real estate remained the largest growth engine, contributing roughly 52% of revenue thanks to sales progress and handovers at urban projects. Logistics and industrial operations contributed 22%, supported by factory leasing, warehousing, and ancillary services in Southeast Vietnam. Renewable energy and supporting infrastructure accounted for 18%, with the remainder coming from services, building materials, and other investments.</p>'
					. '<p>Management said the first-quarter strategy centered on three priorities: preserving cash flow, completing legal work for key projects, and accelerating investments capable of generating recurring cash flow. As a result, gross margins improved, financial costs were better controlled, and working-capital efficiency outperformed the same period last year.</p>'
					. '<h2>Financial resilience and risk governance</h2><p>In a context of volatile rates and uneven market demand, QP Holdings maintained prudent leverage, prioritized longer tenors, and matched funding structure to project development cycles. The group also strengthened legal risk governance, construction-progress control, and standardized investment approval processes to improve transparency.</p>'
					. qp_holdings_news_figure_html( $assets_i . 'vietcombanktower.jpg', 'A centralized financial management model helps QP Holdings closely monitor the efficiency of each business vertical.' )
					. '<h2>2026 outlook</h2><p>The group maintains its full-year 2026 plan of VND 18,500 billion in revenue and VND 2,500 billion in net profit after tax. Key drivers include handover of existing projects, launch of new products, industrial real-estate monetization, and partnerships in smart cities, clean energy, and logistics.</p>'
					. '<blockquote>First-quarter performance suggests QP Holdings is moving in the right direction: revenue growth accompanied by disciplined leverage, stronger asset quality, and long-term cash-flow creation.</blockquote>',
			),
			'zh' => array(
				'title' => 'QP Holdings 公布 2026 年第一季度业绩，营收增长 28%',
				'excerpt' => '2026 年第一季度合并营收达 4,1800 亿越盾，税后利润同比增长 31%。',
				'content' => '<p>QP Holdings 公布 2026 年第一季度合并营收达 4,1800 亿越盾，同比增长 28%；税后利润达 6120 亿越盾，同比增长 31%。这一结果反映出房地产市场回暖、工业物流板块稳定贡献，以及集团生态体系内更严格的成本控制。</p>'
					. qp_holdings_news_figure_html( $assets_i . 'about-banner.jpg', 'QP Holdings 持续推进涵盖房地产、建筑、服务与工业投资的多元生态体系发展。' )
					. '<h2>主要收入来源</h2><p>住宅地产仍是最核心的增长引擎，约贡献 52% 的营收，受益于城市项目的销售推进与交付。物流与工业板块贡献 22%，主要来自东南部地区厂房、仓储及配套服务需求增长。可再生能源和基础设施配套占 18%，其余收入来自服务、建材与其他投资活动。</p>'
					. '<p>管理层表示，第一季度策略聚焦三项重点：保障现金流、完善重点项目法理进度，以及加快具备持续现金流能力的投资。由此，集团毛利率改善，财务成本受控，营运资金周转效率也优于去年同期。</p>'
					. '<h2>财务韧性与风险治理</h2><p>在利率和市场需求仍存在波动的背景下，QP Holdings 保持审慎杠杆水平，优先采用更长期限的融资结构，并使资金来源与项目开发周期相匹配。集团同时加强法务风险管理、工程进度控制及投资审批标准化，以提升整体透明度。</p>'
					. qp_holdings_news_figure_html( $assets_i . 'vietcombanktower.jpg', '集中式财务管理体系帮助 QP Holdings 更紧密地跟踪各业务板块效率。' )
					. '<h2>2026 年展望</h2><p>集团维持 2026 财年营收 18,5000 亿越盾、税后利润 2,5000 亿越盾的年度目标。主要驱动因素包括现有项目交付、新产品推出、工业地产开发，以及在智慧城市、清洁能源和物流领域的合作扩张。</p>'
					. '<blockquote>第一季度业绩表明，QP Holdings 正朝着正确方向前进：营收增长伴随着杠杆控制、资产质量改善以及长期现金流能力增强。</blockquote>',
			),
		),
		'qp-green-park-mo-ban-giai-doan-2' => array(
			'en' => array(
				'title' => 'QP Green Park launches phase 2 with more than 320 products',
				'excerpt' => 'Phase 2 introduces shop-houses and semi-detached villas to serve premium residential demand in Long An.',
				'content' => '<p>QP Green Park has officially launched phase 2 with more than 320 commercial townhouses, semi-detached villas, and detached villas. The new phase builds on the project’s green planning foundation, integrated infrastructure, and amenity ecosystem designed for young families, professionals, and long-term investors.</p>'
					. qp_holdings_news_figure_html( $assets_c . 'qp-green-park-khoicong-1.jpg', 'QP Green Park is planned as a green urban township that balances urban convenience with ecological living.' )
					. '<h2>Green planning and community amenities</h2><p>The project’s planning emphasizes green space, a central park, lakes, and outdoor community zones across a large land bank. These features create a healthier living environment while also supporting the long-term commercial value of homes and shophouses.</p>'
					. '<p>Products in phase 2 are designed for two needs: end-user residence and service-driven investment. Commercial townhouses are located on primary corridors suitable for business, while semi-detached and detached villas target buyers who prioritize privacy, internal amenities, and a higher standard residential community.</p>'
					. qp_holdings_news_figure_html( $assets_c . 'qp-green-park-khoicong-2.jpg', 'The event brought together project leadership, development partners, supervision consultants, and construction contractors.' )
					. '<h2>Location advantage and real housing demand</h2><p>QP Green Park benefits from its connectivity to fast-growing industrial zones in Southeast Vietnam. Demand from experts, engineers, skilled workers, and young families creates a more sustainable absorption base than projects driven purely by speculation. Its proximity to industrial clusters also unlocks opportunities for rental income, commercial services, and long-term asset appreciation.</p>'
					. '<h2>Execution backed by ecosystem partners</h2><p>To ensure quality and delivery discipline, the project is being developed with participation from planning, design, supervision, and construction partners. This approach helps QP Holdings control quality, optimize operations, and improve transparency throughout product development.</p>'
					. qp_holdings_news_figure_html( $assets_c . 'qp-green-park-khoicong-3.jpg', 'QP Green Park is positioned for a green, sustainable residential community with long-term commercial potential.' )
					. qp_holdings_news_source_html( 'QP Xanh', 'https://qpxanh.com.vn/le-khoi-cong-qp-green-park-hanh-trinh-khoi-tao-cuoc-song-xanh/' ),
			),
			'zh' => array(
				'title' => 'QP Green Park 正式推出第二阶段，新增 320 余套产品',
				'excerpt' => '项目第二阶段推出商住联排与双拼别墅，满足隆安高端居住需求。',
				'content' => '<p>QP Green Park 正式推出第二阶段，新增 320 余套商业联排、双拼别墅和独栋别墅。新阶段延续项目绿色规划、一体化基础设施和完善配套体系，面向年轻家庭、专业人士和长期投资者。</p>'
					. qp_holdings_news_figure_html( $assets_c . 'qp-green-park-khoicong-1.jpg', 'QP Green Park 按绿色城市社区理念开发，在城市便利与生态空间之间取得平衡。' )
					. '<h2>绿色规划与社区配套</h2><p>项目规划强调大面积绿地、中央公园、景观湖和户外公共活动空间。这些设计有助于打造更舒适的生活环境，同时提升住宅和商业物业的长期运营价值。</p>'
					. '<p>第二阶段产品同时面向真实居住和经营型投资两类需求。商住联排位于主要交通轴线上，适合经营使用；双拼和独栋别墅则面向重视私密性、社区配套及居住品质的客户群体。</p>'
					. qp_holdings_news_figure_html( $assets_c . 'qp-green-park-khoicong-2.jpg', '项目活动汇集了管理层、开发合作伙伴、监理顾问及施工单位代表。' )
					. '<h2>区位优势与真实居住需求</h2><p>QP Green Park 受益于其与越南东南部快速发展工业区的连接优势。来自专家、工程师、熟练工人及年轻家庭的居住需求，为项目提供了比纯投机型项目更可持续的去化基础。靠近工业区也为租赁、商业服务及资产长期升值带来空间。</p>'
					. '<h2>生态合作体系保障执行力</h2><p>为确保质量与进度，项目引入规划、设计、监理及施工等多方合作伙伴共同推进。这一模式有助于 QP Holdings 强化质量控制、优化运营并提升整个开发过程的透明度。</p>'
					. qp_holdings_news_figure_html( $assets_c . 'qp-green-park-khoicong-3.jpg', 'QP Green Park 面向绿色、可持续并具备长期商业潜力的社区发展。' )
					. qp_holdings_news_source_html( 'QP Xanh', 'https://qpxanh.com.vn/le-khoi-cong-qp-green-park-hanh-trinh-khoi-tao-cuoc-song-xanh/' ),
			),
		),
		'qp-holdings-ky-mou-doi-tac-nhat-ban' => array(
			'en' => array(
				'title' => 'QP Holdings signs MoU with Japanese partner on smart-city development',
				'excerpt' => 'A five-year strategic cooperation with Nikko Urban Group on planning and operating smart urban developments in Southeast Vietnam.',
				'content' => '<p>QP Holdings has signed a memorandum of understanding with Nikko Urban Group to develop smart-urban models, apply digital technology in operations, and elevate the resident experience at new projects. The five-year agreement focuses on planning, operations, clean energy, and urban data systems.</p>'
					. qp_holdings_news_figure_html( $assets_i . 'vietcombanktower.jpg', 'The strategic signing ceremony was held in Ho Chi Minh City with representatives from both sides.' )
					. '<h2>Core areas of cooperation</h2><p>Under the agreement, Nikko Urban Group and QP Holdings will develop smart-city standards covering energy management, security, resident data, amenity operations, and building governance. The goal is to reduce operating costs, improve service quality, and strengthen connectivity between residents, developers, and operators.</p>'
					. '<p>For new urban developments, technology should not be an afterthought added after handover. It must be integrated from the planning stage to optimize technical infrastructure, sensor placement, lighting controls, water systems, waste collection, and digital services for residents.</p>'
					. qp_holdings_news_figure_html( $assets_i . 'quang-phuc4.jpg', 'Pilot urban projects provide QP Holdings with a platform to test smart-operation solutions.' )
					. '<h2>From real estate to service platform</h2><p>QP Holdings sees smart cities as a shift from simply selling products to operating long-term service ecosystems. When residents can use digital platforms to access amenities, pay fees, report incidents, and receive notifications, the developer gains data to continuously improve operations and preserve asset value.</p>'
					. '<h2>2025–2030 roadmap</h2><p>From 2025 to 2030, QP Holdings aims to standardize its development model around three pillars: green, smart, and financially sustainable. The partnership with a Japanese operator is a strategic step toward accessing proven operational know-how, service standards, and continuous-improvement thinking from mature markets.</p>'
					. qp_holdings_news_source_html( 'QP Group', 'https://qpgroup.vn/project/khu-do-thi-quang-phuc-4/' ),
			),
			'zh' => array(
				'title' => 'QP Holdings 与日本合作伙伴签署智慧城市开发谅解备忘录',
				'excerpt' => '与 Nikko Urban Group 达成五年战略合作，共同推进越南东南部智慧城市规划与运营。',
				'content' => '<p>QP Holdings 与 Nikko Urban Group 签署合作谅解备忘录，双方将共同开发智慧城市模型，在项目运营中应用数字技术，并提升新项目居民体验。该五年合作协议聚焦于规划、运营、清洁能源与城市数据系统。</p>'
					. qp_holdings_news_figure_html( $assets_i . 'vietcombanktower.jpg', '战略合作签约仪式在胡志明市举行，双方代表共同出席。' )
					. '<h2>合作重点</h2><p>根据协议，Nikko Urban Group 将与 QP Holdings 共同建立智慧城市标准体系，涵盖能源管理、安全、居民数据、配套运营和楼宇治理。目标是降低运营成本、提升服务质量，并增强居民、开发商与运营方之间的连接效率。</p>'
					. '<p>对于新型城市项目而言，技术不应在交付后再附加，而应从规划阶段就被纳入，以优化基础设施、传感器布局、照明控制、水系统、垃圾收集以及居民数字服务体系。</p>'
					. qp_holdings_news_figure_html( $assets_i . 'quang-phuc4.jpg', '示范型城市项目为 QP Holdings 提供了测试智慧运营方案的平台。' )
					. '<h2>从房地产走向服务平台</h2><p>QP Holdings 认为，智慧城市意味着从“销售产品”转向“运营长期服务生态系统”。当居民能够通过数字平台使用配套服务、支付费用、反馈问题并接收通知时，开发商也能获得更多数据，从而持续优化运营并保持资产价值。</p>'
					. '<h2>2025–2030 发展路线</h2><p>在 2025–2030 年阶段，QP Holdings 希望围绕“绿色、智能、财务可持续”三大支柱标准化其开发模式。与日本伙伴的合作，是其引入成熟市场运营经验、服务标准和持续改进思维的重要一步。</p>'
					. qp_holdings_news_source_html( 'QP Group', 'https://qpgroup.vn/project/khu-do-thi-quang-phuc-4/' ),
			),
		),
		'esg-trach-nhiem-doanh-nghiep' => array(
			'en' => array(
				'title' => 'ESG: Corporate responsibility is no longer optional, it is the standard',
				'excerpt' => 'QP Holdings’ 2025 sustainability roadmap targets green standards on all new projects and a 22% cut in operating emissions.',
				'content' => '<p>ESG is no longer a branding choice; it is becoming an operating standard for developers in real estate, infrastructure, and industry. QP Holdings has outlined its 2025 sustainability direction with a focus on green buildings, transparent governance, community value, and lower operational emissions.</p>'
					. qp_holdings_news_figure_html( $assets_i . 'BG-2.jpg', 'Sustainable development is integrated into the planning, design, and operation of QP Holdings projects.' )
					. '<h2>Key metrics</h2><p>The group’s internal sustainability roadmap sets out three key goals: 100% of new projects will apply at least one green-building standard or equivalent framework; operational emissions intensity will be reduced by 22% versus 2023; and VND 18 billion will be allocated to education, vocational training, and community activities in project localities.</p>'
					. '<ul><li>Prioritize energy-saving materials, high-efficiency lighting, and natural ventilation solutions.</li><li>Increase green areas, water features, and community spaces across urban projects.</li><li>Standardize disclosure, risk governance, and contractor-control procedures.</li></ul>'
					. '<h2>How ESG creates economic value</h2><p>In real estate, green standards help reduce operating costs, improve leasing performance, raise asset value, and create financing advantages. For customers, environmental quality and quality of life increasingly influence decisions to buy homes, rent offices, or choose manufacturing locations.</p>'
					. qp_holdings_news_figure_html( $assets_c . 'qp-green-park-khoicong-1.jpg', 'Green urban projects help turn ESG from a paper commitment into a real resident experience.' )
					. '<h2>Governance and community commitment</h2><p>QP Holdings believes ESG must begin with governance: selecting projects that match financial capacity, ensuring legal compliance, maintaining transparency on progress, and controlling construction quality. On the social side, the group prioritizes local employment, higher labor-safety standards, and community support tied to the project lifecycle.</p>'
					. '<blockquote>Sustainability matters only when it creates value simultaneously for customers, investors, employees, local communities, and the environment.</blockquote>'
					. qp_holdings_news_source_html( 'QP Group', 'https://qpgroup.vn/en/about-us/' ),
			),
			'zh' => array(
				'title' => 'ESG：企业责任已不再是选择，而是标准',
				'excerpt' => 'QP Holdings 2025 可持续发展路线图提出：所有新项目全面采用绿色标准，并将运营排放强度降低 22%。',
				'content' => '<p>ESG 已不再只是企业形象层面的选择，而正在成为房地产、基础设施和工业开发商的运营标准。QP Holdings 公布了其 2025 年可持续发展方向，重点聚焦绿色建筑、透明治理、社区价值以及运营减排。</p>'
					. qp_holdings_news_figure_html( $assets_i . 'BG-2.jpg', '可持续发展理念已融入 QP Holdings 项目的规划、设计与运营之中。' )
					. '<h2>关键指标</h2><p>集团内部可持续发展路线图提出三项目标：100% 新项目采用至少一种绿色建筑标准或同等体系；运营排放强度较 2023 年降低 22%；并投入 180 亿越盾用于项目所在地教育、职业培训和社区活动。</p>'
					. '<ul><li>优先使用节能材料、高效照明和自然通风解决方案。</li><li>在城市项目中增加绿地、水体和公共活动空间。</li><li>标准化信息披露、风险治理与承包商管控流程。</li></ul>'
					. '<h2>ESG 如何创造经济价值</h2><p>在房地产领域，绿色标准有助于降低运营成本、提升出租表现、提高资产价值，并在融资时带来优势。对客户而言，环境质量与生活品质正在越来越直接地影响其购房、租赁办公空间或选择生产基地的决策。</p>'
					. qp_holdings_news_figure_html( $assets_c . 'qp-green-park-khoicong-1.jpg', '绿色城市项目帮助 ESG 从纸面承诺变成居民的真实体验。' )
					. '<h2>治理与社区承诺</h2><p>QP Holdings 认为 ESG 必须从治理开始：选择与财务能力相匹配的项目、严格遵守法律、透明披露进度并控制施工质量。在社会层面，集团优先支持本地就业、提高劳动安全标准，并围绕项目全生命周期开展社区支持计划。</p>'
					. '<blockquote>可持续发展只有在同时为客户、投资者、员工、地方社区和环境创造价值时，才真正具有意义。</blockquote>'
					. qp_holdings_news_source_html( 'QP Group', 'https://qpgroup.vn/en/about-us/' ),
			),
		),
		'qp-holdings-top-50-doanh-nghiep-niem-yet' => array(
			'en' => array(
				'title' => 'QP Holdings enters Vietnam’s Top 50 listed companies in 2025',
				'excerpt' => 'For the third consecutive year, QP Holdings has been recognized by Forbes Vietnam among the country’s best listed companies.',
				'content' => '<p>QP Holdings has been honored among Vietnam’s Top 50 listed companies in 2025, recognizing the group’s growth, financial governance, and long-term ecosystem strategy. This is the third consecutive year the group has appeared in the ranking, reinforcing its position in the capital market.</p>'
					. qp_holdings_news_figure_html( $assets_i . 'quang-phuc-plaza.jpg', 'The Top 50 distinction reflects QP Holdings’ project-development capability, financial governance, and brand credibility.' )
					. '<h2>Key evaluation criteria</h2><p>The Top 50 list is typically based on multiple factors including revenue, profit, return ratios, market-cap growth, disclosure transparency, and the ability to sustain business performance through cycles. For QP Holdings, a key strength lies in its focused multi-sector strategy spanning real estate, construction, services, and industrial investment.</p>'
					. '<p>In a volatile real-estate and equity market environment, sustaining stable growth suggests that the group has managed its project portfolio, leverage, and cash flow effectively. Urban, office, industrial, and support-service assets continue to create a solid revenue base for future years.</p>'
					. qp_holdings_news_figure_html( $assets_i . 'about-Bvbwf05l.jpg', 'QP Holdings continues to build an ecosystem anchored in real estate, construction, services, and industrial investment.' )
					. '<h2>Why this matters to investors</h2><p>Being included among the country’s best listed companies is more than a branding milestone; it also raises QP Holdings’ visibility with institutional investors, financial partners, and customers. The recognition provides added momentum for the group to keep improving disclosure standards, risk governance, and capital efficiency.</p>'
					. '<h2>What comes next</h2><p>In the next phase, QP Holdings aims to expand projects with stable cash-generation potential, develop greener urban assets, deepen cooperation with technology partners, and standardize post-handover operating models. The group also intends to embed ESG more deeply across each stage of project development to preserve long-term competitive advantage.</p>'
					. '<blockquote>Capital-market success is only sustainable when it is backed by financial discipline, transparent disclosure, and the ability to create real value for customers.</blockquote>'
					. qp_holdings_news_source_html( 'QP Group', 'https://qpgroup.vn/en/about-us/' ),
			),
			'zh' => array(
				'title' => 'QP Holdings 跻身 2025 年越南最佳上市企业 Top 50',
				'excerpt' => 'QP Holdings 连续第三年获 Forbes Vietnam 评选为越南最佳上市公司之一。',
				'content' => '<p>QP Holdings 入选 2025 年越南最佳上市企业 Top 50，反映了集团在增长、财务治理和长期生态战略方面所取得的成果。这已是集团连续第三年进入该榜单，进一步巩固了其在资本市场中的地位。</p>'
					. qp_holdings_news_figure_html( $assets_i . 'quang-phuc-plaza.jpg', 'Top 50 荣誉体现了 QP Holdings 在项目开发、财务治理与品牌信誉方面的综合实力。' )
					. '<h2>主要评估标准</h2><p>Top 50 榜单通常综合考量营收、利润、回报率、市值增长、信息披露透明度以及企业穿越周期保持经营效率的能力。对 QP Holdings 来说，其优势在于聚焦型多元战略，覆盖房地产、建筑、服务和工业投资等领域。</p>'
					. '<p>在房地产与资本市场波动加剧的背景下，依然保持稳定增长，说明集团在项目组合、负债水平与现金流管理方面具备较强韧性。城市项目、办公资产、工业园区及配套服务板块，也将继续为未来数年收入提供坚实支撑。</p>'
					. qp_holdings_news_figure_html( $assets_i . 'about-Bvbwf05l.jpg', 'QP Holdings 持续打造以房地产、建筑、服务与工业投资为核心的多元生态体系。' )
					. '<h2>对投资者的意义</h2><p>进入最佳上市企业榜单不仅是品牌认可，也有助于提升 QP Holdings 在机构投资者、金融合作伙伴及客户中的可见度。这一荣誉将进一步推动集团提升信息披露标准、风险治理能力及资本使用效率。</p>'
					. '<h2>下一阶段战略</h2><p>未来阶段，QP Holdings 计划继续扩大具备稳定现金流能力的项目，发展更绿色的城市资产，深化与科技伙伴合作，并标准化交付后的运营模式。集团也将进一步把 ESG 融入项目开发各阶段，以保持长期竞争优势。</p>'
					. '<blockquote>资本市场上的成果，只有建立在财务纪律、透明信息披露以及真实客户价值创造之上，才能长期持续。</blockquote>'
					. qp_holdings_news_source_html( 'QP Group', 'https://qpgroup.vn/en/about-us/' ),
			),
		),
	);
}

function qp_holdings_seeded_news_translation_for_post( $post, string $field, string $fallback = '' ): string {
	if ( is_admin() ) {
		return $fallback;
	}

	$lang = qp_holdings_current_language();
	if ( 'vi' === $lang ) {
		return $fallback;
	}

	$post_obj = $post instanceof WP_Post ? $post : get_post( $post );
	if ( ! $post_obj instanceof WP_Post || 'qp_news' !== $post_obj->post_type ) {
		return $fallback;
	}

	/*
	 * Dedicated translation copies (EN/ZH posts created by the translation
	 * importer / in admin) carry a non-empty `_qp_translation_lang` meta
	 * ('en' / 'zh'). When WE ARE that translation post — e.g. its own single
	 * article page in the matching language — the post's stored
	 * content/title/excerpt is the editable source of truth, so return
	 * $fallback (the post's own field) and let the editor govern it.
	 *
	 * NOTE: we intentionally do NOT gate on `_qp_source_slug` here. Some
	 * canonical Vietnamese originals were mistakenly stamped with a
	 * `_qp_source_slug` pointing at themselves; gating on that meta made the
	 * filter bail and leak the Vietnamese title onto EN/ZH listings even
	 * though a real translation copy existed. `_qp_translation_lang` is the
	 * reliable marker of an actual translation copy.
	 */
	$own_translation_lang = get_post_meta( $post_obj->ID, '_qp_translation_lang', true );
	if ( is_string( $own_translation_lang ) && '' !== $own_translation_lang ) {
		return $fallback;
	}

	$map = qp_holdings_seeded_news_translations();
	$source_slug = qp_holdings_translation_source_slug( $post_obj );
	if ( isset( $map[ $source_slug ][ $lang ][ $field ] ) ) {
		return $map[ $source_slug ][ $lang ][ $field ];
	}

	/*
	 * No hard-coded seeded-map entry. Listings render the canonical Vietnamese
	 * post set, so fall back to the linked Polylang translation copy's own
	 * field. This lets EN/ZH listings show translated titles/excerpts for any
	 * post that has a real translation copy (created in admin or via the
	 * translation importer) without needing a map entry. Raw post properties
	 * are used (not get_the_title()/the_content) so the *_title / the_content
	 * filters don't recurse on the translation post.
	 */
	if ( function_exists( 'pll_get_post' ) ) {
		$translated_id = (int) pll_get_post( (int) $post_obj->ID, $lang );
		if ( $translated_id && $translated_id !== (int) $post_obj->ID ) {
			$translated = get_post( $translated_id );
			if ( $translated instanceof WP_Post ) {
				switch ( $field ) {
					case 'title':
						if ( '' !== trim( (string) $translated->post_title )
							&& ! qp_holdings_text_looks_vietnamese( (string) $translated->post_title ) ) {
							return $translated->post_title;
						}
						return $fallback;
					case 'excerpt':
						// Prefer the translation's own excerpt. Translation copies
						// often have an empty excerpt while their content IS
						// translated, so derive the excerpt from the translated
						// content instead of leaking the Vietnamese fallback.
						if ( '' !== trim( (string) $translated->post_excerpt )
							&& ! qp_holdings_text_looks_vietnamese( (string) $translated->post_excerpt ) ) {
							return $translated->post_excerpt;
						}
						// Strip tags/shortcodes BEFORE the language check. Vietnamese
						// text living only in HTML attributes (e.g. an <img> alt like
						// "Biểu đồ ASEANSC 1") would otherwise trip the detector and
						// blank an otherwise-English excerpt. We display the stripped
						// text anyway, so check exactly what we will show.
						$t_plain = trim( wp_strip_all_tags( strip_shortcodes( (string) $translated->post_content ) ) );
						// Some translation copies still hold the Vietnamese body
						// (not actually translated yet). Don't derive an excerpt
						// from Vietnamese text on an EN/ZH page — return empty so
						// the card simply shows no excerpt instead of VI text.
						if ( '' !== $t_plain && ! qp_holdings_text_looks_vietnamese( $t_plain ) ) {
							return wp_trim_words( $t_plain, 40 );
						}
						return '';
					case 'content':
						$t_content = trim( (string) $translated->post_content );
						if ( '' !== $t_content && ! qp_holdings_text_looks_vietnamese( $t_content ) ) {
							return $translated->post_content;
						}
						return $fallback;
				}
			}
		}
	}

	return $fallback;
}

function qp_holdings_filter_seeded_news_title( string $title, int $post_id = 0 ): string {
	return qp_holdings_seeded_news_translation_for_post( $post_id, 'title', $title );
}
add_filter( 'the_title', 'qp_holdings_filter_seeded_news_title', 20, 2 );

function qp_holdings_filter_seeded_news_excerpt( string $excerpt, $post ): string {
	return qp_holdings_seeded_news_translation_for_post( $post, 'excerpt', $excerpt );
}
add_filter( 'get_the_excerpt', 'qp_holdings_filter_seeded_news_excerpt', 20, 2 );

function qp_holdings_filter_seeded_news_content( string $content ): string {
	$post = get_post();
	return qp_holdings_seeded_news_translation_for_post( $post, 'content', $content );
}
add_filter( 'the_content', 'qp_holdings_filter_seeded_news_content', 20 );

/* ===========================================================================
 * Investor doc (qp_investor_doc) seeded-content translation layer
 * Mirrors the pattern used for qp_news / qp_project above.
 * Only `title` is mapped because all other displayed fields (date, size,
 * year, doc_language badge) are either numeric/dates or handled by
 * qp_holdings_t() keys already wired in the renderer.
 * =========================================================================== */

function qp_holdings_seeded_investor_doc_translations(): array {
	return array(
		// Mock translations removed — investor docs are now imported from
		// qpxanh.com.vn via bin/import-investor-docs.php with real data.
		// Re-add entries here for live post slugs when EN/ZH translations
		// are needed.
	);
}

/**
 * Retrieve a translated field for a seeded qp_investor_doc post.
 *
 * @param WP_Post|int $post     Post object or ID.
 * @param string      $field    Field key (currently: title).
 * @param string      $fallback Vietnamese default value.
 */
function qp_holdings_seeded_investor_doc_translation_for_post( $post, string $field, string $fallback = '' ): string {
	if ( is_admin() ) {
		return $fallback;
	}

	$lang = qp_holdings_current_language();
	if ( 'vi' === $lang ) {
		return $fallback;
	}

	$post_obj = $post instanceof WP_Post ? $post : get_post( $post );
	if ( ! $post_obj instanceof WP_Post || 'qp_investor_doc' !== $post_obj->post_type ) {
		return $fallback;
	}

	$map = qp_holdings_seeded_investor_doc_translations();
	$source_slug = qp_holdings_translation_source_slug( $post_obj );
	return $map[ $source_slug ][ $lang ][ $field ] ?? $fallback;
}

function qp_holdings_filter_seeded_investor_doc_title( string $title, int $post_id = 0 ): string {
	return qp_holdings_seeded_investor_doc_translation_for_post( $post_id, 'title', $title );
}
add_filter( 'the_title', 'qp_holdings_filter_seeded_investor_doc_title', 20, 2 );

/* ===========================================================================
 * Project (qp_project) seeded-content translation layer
 * Mirrors the pattern used for qp_news above.
 * =========================================================================== */

function qp_holdings_seeded_project_translations(): array {
	return array(

		/* ── 1. QP Bình Dương Center ── */
		'qp-binh-duong-center' => array(
			'en' => array(
				'investment_total' => '1,800 billion VND',
				'investment'       => '1,800 billion VND',
				'title'            => 'QP Binh Duong Center',
				'subtitle'         => 'A modern commercial icon in the heart of Thu Dau Mot',
				'category'         => 'Commercial Center',
				'address'          => 'Thu Dau Mot Ward, Ho Chi Minh City',
				'scale'            => '0.6 ha — 1,800 billion VND',
				'area_total'       => '0.6 ha',
				'investor'         => 'QP Holdings',
				'developer'        => 'QP Holdings',
				'completion'       => '2027',
				'handover'         => 'Construction starts Q1/2027',
				'hero_heading'     => "Modern commercial center in the heart of Thu Dau Mot\nMulti-dimensional experience ecosystem: shopping, entertainment, dining",
				'highlights'       => "Modern commercial center in the heart of Thu Dau Mot\nMulti-dimensional experience ecosystem: shopping, entertainment, dining\nTotal investment of 1,800 billion VND\nCentral location — connecting dynamic urban life",
				'content'          => '<p>QP Binh Duong Center is positioned as a modern commercial center with a premium touch in Thu Dau Mot City, Binh Duong — where new retail development thinking, modern consumer experiences, and the vision of creating a vibrant destination in the heart of a dynamic city converge.</p><h2>Central Position</h2><p>Located in Thu Dau Mot City, Binh Duong Province, QP Binh Duong Center stands in an area that drives local economic growth, services, and urban development. Capitalizing on its connection to the city\'s dynamic pace, the project is expected to become a new landmark for residents, businesses, customers, and domestic and international brands.</p><h2>1,800 Billion VND Investment Scale</h2><p>Developed on a scale of approximately 0.6 hectares with a projected total investment of 1,800 billion VND, QP Binh Duong Center is designed to be one of the prominent commercial structures in central Thu Dau Mot. The project is built with a modern approach, focusing on the balance between architectural design, functional optimization, user experience, and commercial operational efficiency.</p><h2>Experience Ecosystem</h2><p>QP Binh Duong Center aims to develop a multi-experience commercial environment where consumption, entertainment, social interaction, and lifestyle are elevated. The project will bring together a wide array of brands across dining, fashion, and entertainment sectors.</p><h2>Vision</h2><p>With the aspiration to create long-term value, QP Binh Duong Center is not just developing a shopping mall. It is a journey to build a new experience destination, a well-invested commercial environment, and a space connecting brands, customers, and urban lifestyles.</p>',
			),
			'zh' => array(
				'investment_total' => '18,000 亿越盾',
				'investment'       => '18,000 亿越盾',
				'title'            => 'QP 平阳中心',
				'subtitle'         => '土龙木市中心的现代商业地标',
				'category'         => '商业中心',
				'address'          => '胡志明市土龙木坊',
				'scale'            => '0.6 公顷 — 18,000 亿越盾',
				'area_total'       => '0.6 公顷',
				'investor'         => 'QP Holdings',
				'developer'        => 'QP Holdings',
				'completion'       => '2027',
				'handover'         => '2027 年第一季度动工建设',
				'hero_heading'     => "土龙木市中心的现代商业中心\n多维体验生态系统：购物、娱乐、美食",
				'highlights'       => "土龙木市中心的现代商业中心\n多维体验生态系统：购物、娱乐、美食\n总投资 18,000 亿越盾\n中心位置 — 链接活力都市生活",
				'content'          => '<p>QP 平阳中心定位为平阳省土龙木市的地标级现代商业中心，融合了全新商业开发思维、现代消费体验以及在活力都市中心打造繁华地标的愿景。</p><h2>中心区位</h2><p>QP 平阳中心位于平阳省土龙木市，地处当地经济、服务和城市发展的核心引擎地带。凭借连接城市活力的优势，该项目有望成为居民、企业、客户以及国内外品牌的新聚集地。</p><h2>18,000 亿越盾 investment 规模</h2><p>项目规划面积约 0.6 公顷，预计总投资 18,000 亿越盾，旨在成为土龙木市中心最突出的商业地标之一。项目按现代化方向投资建设，注重建筑设计、功能优化、用户体验与商业运营效率之间的平衡。</p><h2>体验生态系统</h2><p>QP 平阳中心致力于打造一个多维体验的商业环境，提升消费、娱乐、社交和享受生活的品质。项目将汇聚餐饮、时尚和娱乐领域的众多知名品牌。</p><h2>项目愿景</h2><p>怀揣创造长期价值的抱负，QP 平阳中心不仅是一个商场，更是一个全新的体验目的地、一个精心投资的商业环境、以及一个链接品牌、客户与都市生活方式的空间。</p>',
			),
		),

		/* ── 2. QP Luxia One ── */
		'qp-luxia-one' => array(
			'en' => array(
				'investment_total' => '4,000 billion VND',
				'investment'       => '4,000 billion VND',
				'title'            => 'QP Luxia One & QP Luxia Premier',
				'subtitle'         => 'Premium living icon in the heart of Thu Dau Mot',
				'category'         => 'High-rise Complex',
				'address'          => 'Hoa Phu Ward, Thu Dau Mot, Ho Chi Minh City',
				'scale'            => '1.94 ha — 16,000 – 18,000 residents',
				'area_total'       => '1.94 ha',
				'investor'         => 'QP Holdings',
				'developer'        => 'QP Holdings',
				'completion'       => '2027',
				'handover'         => 'Construction starts Q1/2027',
				'hero_heading'     => "Premium living icon in the heart of Thu Dau Mot\nA symbol of contemporary and dynamic living",
				'highlights'       => "High-rise complex in the heart of Thu Dau Mot\nTotal investment of 4,000 billion VND\nExpected population of 16,000 – 18,000 residents\nPremium living standard for modern communities",
				'content'          => '<p>QP Luxia One & QP Luxia Premier is positioned as a premium high-rise apartment complex representing modern living in the heart of Thu Dau Mot — where new urban development concepts, premium living standards, and lifestyles for active modern communities converge.</p><h2>Central Location</h2><p>Located in Hoa Phu Ward, Thu Dau Mot, the project benefits from its presence in a dynamic center that concentrates urban infrastructure, administration, commerce, and key economic activities. Seamless connectivity and fast access to existing amenities unlock a flexible and modern lifestyle.</p><h2>Scale of 1.94 ha — Creating Premium Living Spaces</h2><p>Developed on a land area of approximately 1.94 hectares with an expected total investment of 4,000 billion VND, QP Luxia One & QP Luxia Premier is designed to be one of the most prominent premium apartment developments in the Thu Dau Mot area. The project is planned with a modern mindset, emphasizing the harmony between architecture, landscape, functionality, and resident experience.</p><h2>Development Philosophy</h2><p>In the spirit of high-end real estate, QP Luxia One & QP Luxia Premier focuses on resident experience, quality of life, and ownership value. The project aims to build a living environment where people can enjoy modern conveniences while finding peace, connection, and a sense of belonging in daily life.</p><h2>Investment Value</h2><p>Possessing a central location, large-scale investment, and premium development orientation, QP Luxia One & QP Luxia Premier not only satisfies housing needs but also offers strong potential for medium and long-term investment strategies.</p>',
			),
			'zh' => array(
				'investment_total' => '40,000 亿越盾',
				'investment'       => '40,000 亿越盾',
				'title'            => 'QP Luxia One 与 QP Luxia Premier',
				'subtitle'         => '土龙木市中心的高端居住地标',
				'category'         => '高层综合体',
				'address'          => '胡志明市土龙木和富坊',
				'scale'            => '1.94 公顷 — 16,000 – 18,000 名居民',
				'area_total'       => '1.94 公顷',
				'investor'         => 'QP Holdings',
				'developer'        => 'QP Holdings',
				'completion'       => '2027',
				'handover'         => '2027 年第一季度动工建设',
				'hero_heading'     => "土龙木市中心的高端居住地标\n当代活力生活的象征",
				'highlights'       => "土龙木市中心的高层综合体项目\n总投资 40,000 亿越盾\n规划人口 16,000 – 18,000 名居民\n专为现代社区打造的优质生活标准",
				'content'          => '<p>QP Luxia One & QP Luxia Premier 定位为土龙木市中心的高端现代住宅综合体，融合了全新都市开发思维、优质生活标准与现代活力社区的生活方式。</p><h2>中心区位</h2><p>项目位于土龙木市和富坊，地处集城市基础设施、行政、商业和重点经济活动于一体的活力中心。便利的交通连接与快速直达的周边配套，开创了灵活且现代的生活模式。</p><h2>1.94 公顷规模 — 塑造高品质生活空间</h2><p>项目在约 1.94 公顷的土地上开发，预计总投资 40,000 亿越盾，旨在成为土龙木地区最受瞩目的高端公寓项目之一。项目按现代规划思维进行研究，强调建筑、景观、使用功能与居住体验的同步协调。</p><h2>开发理念</h2><p>秉承高端房产的定位，QP Luxia One & QP Luxia Premier 以居住体验、生活品质和资产价值为核心。项目致力于打造一个既能享受现代都市便利，又能在日常生活中找到宁静、连接和归属感的居住环境。</p><h2>投资价值</h2><p>优越的中心位置、庞大的投资规模和高端的开发定位，使 QP Luxia One & QP Luxia Premier 不仅能满足安居需求，更具备中长期投资的巨大潜力。</p>',
			),
		),

		/* ── 3. Hiep Thanh City ── */
		'hiep-thanh-city' => array(
			'en' => array(
				'investment_total' => '11,000 billion VND',
				'investment'       => '11,000 billion VND',
				'title'            => 'Hiep Thanh City Complex Apartment',
				'subtitle'         => 'Creating premium living standards in the urban core of Thu Dau Mot',
				'category'         => 'Complex Apartment',
				'address'          => 'Thu Dau Mot Ward, Ho Chi Minh City',
				'scale'            => '7.5 ha — 11,000 billion VND',
				'area_total'       => '7.5 ha',
				'investor'         => 'QP Holdings',
				'developer'        => 'QP Holdings',
				'completion'       => '2027',
				'handover'         => 'Construction starts Q1/2027',
				'hero_heading'     => "Creating premium living standards in the urban core of Thu Dau Mot\nComprehensive living ecosystem",
				'highlights'       => "Premium apartment complex in the heart of Thu Dau Mot\nTotal investment of 11,000 billion VND\nScale of 7.5 ha — comprehensive living ecosystem\nSynchronous and luxurious internal & external amenities",
				'content'          => '<p>Hiep Thanh City Complex Apartment is developed with the vision of becoming a large-scale premium apartment complex in Thu Dau Mot City, Binh Duong — where modern planning, comprehensive living environments, and urban standards for a new generation of residents intersect.</p><h2>Central Position</h2><p>Located in Thu Dau Mot City, Binh Duong Province, Hiep Thanh City stands in the local center for economic, administrative, and urban growth. Convenient transportation and access to existing infrastructure provide a highly flexible environment for residents to balance work, family, education, and daily life.</p><h2>7.5 ha Scale</h2><p>Developed on a 7.5-hectare land area with a projected investment of 11,000 billion VND, Hiep Thanh City carries the stature of a thoroughly planned, long-term premium residential development. The master plan prioritizes the harmony of architecture, functionality, landscape, and user experience.</p><h2>Development Philosophy</h2><p>Hiep Thanh City is designed with quality of life at its core. The complex is planned with luxurious, synchronous internal and external amenities that cater to the diverse living, relaxation, and connection needs of the future community.</p>',
			),
			'zh' => array(
				'investment_total' => '110,000 亿越盾',
				'investment'       => '110,000 亿越盾',
				'title'            => '协成城综合公寓区',
				'subtitle'         => '在土龙木城市核心区塑造优质生活标准',
				'category'         => '综合公寓',
				'address'          => '胡志明市土龙木坊',
				'scale'            => '7.5 公顷 — 110,000 亿越盾',
				'area_total'       => '7.5 公顷',
				'investor'         => 'QP Holdings',
				'developer'        => 'QP Holdings',
				'completion'       => '2027',
				'handover'         => '2027 年第一季度动工建设',
				'hero_heading'     => "在土龙木城市核心区塑造优质生活标准\n全方位生活生态圈",
				'highlights'       => "土龙木市中心的高端公寓综合体\n总投资 110,000 亿越盾\n占地 7.5 公顷 — 全方位生活生态圈\n同步且奢华的社区内外部配套设施",
				'content'          => '<p>协成城综合公寓项目致力于成为平阳省土龙木市的大型高端公寓综合体，完美融合了现代规划理念、全方位居住环境 and 新一代都市居民的生活标准。</p><h2>中心区位</h2><p>协成城位于平阳省土龙木市，地处当地经济、行政和城市发展的中心区域。便利的交通网络与成熟的配套设施，为居民创造了灵活的生活环境，方便平衡工作、家庭、教育和日常娱乐需求。</p><h2>7.5 公顷规模</h2><p>项目在约 7.5 公顷的土地上开发，预计总投资达 110,000 亿越盾，具有长期且系统化投资的高端公寓综合体风范。项目规划优先考虑建筑、功能、景观和居住体验的和谐统一。</p><h2>开发理念</h2><p>协成城以居住品质为中心。整个综合区规划了现代、同步且奢华的内部与外部配套，满足未来社区多元化的生活、休闲和社交需求。</p>',
			),
		),

		/* ── 4. QP IDC ── */
		'qp-idc' => array(
			'en' => array(
				'investment_total' => '1,200 billion VND',
				'investment'       => '1,200 billion VND',
				'title'            => 'QP IDC Factory Area',
				'subtitle'         => 'Modern industrial infrastructure solutions for new-age enterprises',
				'category'         => 'Factories for Rent',
				'address'          => 'Nam Tan Uyen Industrial Park, Binh Duong',
				'scale'            => '20 ha',
				'area_total'       => '20 ha',
				'investor'         => 'QP Holdings',
				'developer'        => 'QP Holdings',
				'completion'       => '2025',
				'handover'         => 'In operation',
				'hero_heading'     => "Modern industrial infrastructure solutions for new-age enterprises\nStrategic industrial hub",
				'highlights'       => "Scale of 20 ha in Nam Tan Uyen IP — Binh Duong\nTotal investment of 1,200 billion VND\nModern rental factory model\nStrategic location — the heart of Binh Duong\'s industrial development",
				'content'          => '<p>QP IDC is developed as a modern rental factory model in Binh Duong — combining industrial advantages, professional infrastructure development, and operational solutions tailored to the needs of manufacturers in the new era.</p><h2>Strategic Location</h2><p>Located in Nam Tan Uyen Industrial Park, Binh Duong, QP IDC enjoys a prime spot in one of the most active industrial hubs of Southern Vietnam. Binh Duong has long established its position as a key industrial center of the country.</p><h2>20 ha Scale</h2><p>Developed on a scale of 20 hectares with a projected investment of 1,200 billion VND, QP IDC is set to become an exemplary industrial infrastructure model in Binh Duong. The factory spaces are engineered to optimize usage, enhance operational efficiency, and suit various manufacturing, supporting industries, and logistics sectors.</p><h2>Development Philosophy</h2><p>QP IDC focuses on modern rental factories where infrastructure does not just serve production but acts as a comprehensive solution helping businesses optimize investment resources and boost operational efficiency.</p>',
			),
			'zh' => array(
				'investment_total' => '12,000 亿越盾',
				'investment'       => '12,000 亿越盾',
				'title'            => 'QP IDC 厂房区',
				'subtitle'         => '专为新时代企业打造的现代工业基础设施解决方案',
				'category'         => '厂房出租',
				'address'          => '平阳省南新渊工业区',
				'scale'            => '20 公顷',
				'area_total'       => '20 公顷',
				'investor'         => 'QP Holdings',
				'developer'        => 'QP Holdings',
				'completion'       => '2025',
				'handover'         => '已投入运营',
				'hero_heading'     => "专为新时代企业打造的现代工业基础设施解决方案\n战略性工业枢纽",
				'highlights'       => "位于平阳南新渊工业区，占地 20 公顷\n总投资 12,000 亿越盾\n现代化出租厂房模式\n战略位置 — 平阳工业发展的核心地带",
				'content'          => '<p>QP IDC 厂房区开发注重现代化出租厂房，将工业区位优势、系统化基础设施开发以及契合新时代制造业需求的运营解决方案融为一体。</p><h2>战略位置</h2><p>QP IDC 位于平阳省南新渊工业区，地处越南南部最具活力的工业开发区之一。平阳省长期以来确立了全国重点工业中心的地位。</p><h2>20 公顷规模</h2><p>项目规划面积 20 公顷，预计总投资 12,000 亿越盾，旨在成为平阳省示范性的工业基础设施模式。厂房空间设计旨在优化使用功能，提高利用效率，适用于多种制造领域、配套工业和物流行业。</p><h2>开发理念</h2><p>QP IDC 秉承现代出租厂房的开发理念，基础设施不仅服务于生产活动，更作为一种协助企业优化投资资源、提升运营效率的综合解决方案。</p>',
			),
		),

		/* ── 5. Khu đô thị Quang Phúc 4 ── */
		'khu-do-thi-quang-phuc-4' => array(
			'en' => array(
				'investment_total' => '4,000 billion VND',
				'investment'       => '4,000 billion VND',
				'title'            => 'Quang Phuc 4 Urban Area',
				'subtitle'         => 'Model urban landmark — Shaping smart living standards for future generations',
				'category'         => 'Multi-functional Urban Area',
				'address'          => 'Bac Tan Uyen Commune, Ho Chi Minh City',
				'scale'            => '26 ha',
				'area_total'       => '26 ha',
				'investor'         => 'QP Holdings',
				'developer'        => 'QP Holdings',
				'completion'       => '2028',
				'handover'         => 'Under construction',
				'hero_heading'     => "Model urban landmark\nShaping smart living standards for future generations",
				'highlights'       => "26-hectare multi-functional urban area — 4,000 billion VND\nSmart model town in Bac Tan Uyen\nAll-inclusive amenity ecosystem\nGreen spaces — the core value of the living environment",
				'content'          => '<p>Quang Phuc 4 Urban Area is envisioned as a smart model town, blending modern living spaces, a comprehensive amenity ecosystem, and future-oriented urban development standards. The project represents the aspiration of QP Group to create high-quality, civilized, and sustainable communities in Binh Duong.</p><h2>Strategic Position</h2><p>Located in Bac Tan Uyen, Binh Duong Province, Quang Phuc 4 sits in a new growth node of the province, which concentrates strong drivers in industrial, infrastructure, residential, and commercial sectors.</p><h2>26 ha Scale</h2><p>Planned on approximately 26 hectares with a projected investment of 4,000 billion VND, Quang Phuc 4 is a well-invested, multi-functional urban area. The project integrates multiple functions into a unified living ecosystem.</p><h2>Smart City Philosophy</h2><p>At Quang Phuc 4, the "smart" element is not just about technology applications, but a philosophy guiding the entire development. It is smart in planning, smart in spatial organization, and smart in how people connect with environment, community, and city services.</p><h2>Comprehensive Amenities</h2><p>As a multi-functional urban area, Quang Phuc 4 features an excellent internal amenity system while leveraging convenient connections to the diverse external network of the region.</p>',
			),
			'zh' => array(
				'investment_total' => '40,000 亿越盾',
				'investment'       => '40,000 亿越盾',
				'title'            => '广福 4 城区',
				'subtitle'         => '示范城区地标 — 为子孙后代塑造智能生活标准',
				'category'         => '多功能城区',
				'address'          => '胡志明市北新渊社',
				'scale'            => '26 公顷',
				'area_total'       => '26 公顷',
				'investor'         => 'QP Holdings',
				'developer'        => 'QP Holdings',
				'completion'       => '2028',
				'handover'         => '建设中',
				'hero_heading'     => "示范城区地标\n为子孙后代塑造智能生活标准",
				'highlights'       => "26 公顷多功能城区 — 40,000 亿越盾\n北新渊智能示范城镇\n全方位的配套服务生态系统\n绿色空间 — 居住环境的核心价值",
				'content'          => '<p>广福 4 城区旨在打造成为智能示范城区，集现代居住空间、全方位配套生态系统和未来城市开发 standard 于一体。该项目体现了 QP 集团在平阳省建设高品质、文明和可持续发展社区的抱负。</p><h2>战略位置</h2><p>广福 4 位于平阳省北新渊，地处该省新的增长极，汇聚了工业、基础设施、人口和商业等多重发展动力。</p><h2>26 公顷规模</h2><p>项目规划占地约 26 公顷，预计总投资 40,000 亿越盾，展现了系统化和长期 investment 的多功能城区的宏伟蓝图。项目在同一生活生态系统中集成了多种城市功能。</p><h2>智慧城市理念</h2><p>在广福 4，"智慧"要素不仅是技术的应用，更是贯穿整个项目的开发理念。这体现在规划的智慧、生活空间的智慧，以及人与环境、社区和城市服务连接方式的智慧。</p><h2>全方位配套</h2><p>广福 4 城区注重投资建设完善的内部配套系统，同时充分利用与周边多元外部配套网络的便利连接优势。</p>',
			),
		),

		/* ── 6. QP Green Park ── */
		'qp-green-park' => array(
			'en' => array(
				'investment_total' => '1,300 billion VND',
				'investment'       => '1,300 billion VND',
				'title'            => 'QP Green Park Residential Area',
				'subtitle'         => 'Green urban landmark — Shaping new living standards in Bac Tan Uyen',
				'category'         => 'Low-rise Housing Area',
				'address'          => 'Bac Tan Uyen, Ho Chi Minh City',
				'scale'            => '13.7 ha — 568 townhouses + 68 shophouses',
				'area_total'       => '13.7 ha',
				'investor'         => 'QP Holdings',
				'developer'        => 'QP Holdings',
				'completion'       => '2026',
				'handover'         => 'Infrastructure completed',
				'hero_heading'     => "Green urban landmark — Shaping new living standards in Bac Tan Uyen\nSynchronous and modern township",
				'highlights'       => "13.7-hectare low-rise residential development — 1,300 billion VND\n568 townhouses + 68 commercial shophouses\nGreen, synchronous, and modern urban design\nStrategic location on DT742 route — Bac Tan Uyen",
				'content'          => '<p>QP Green Park is planned as a synchronous, modern, and human-centric urban area in Bac Tan Uyen, Binh Duong — harmoniously combining green living spaces, comprehensive amenities, and long-term growth potential.</p><h2>Development Vision</h2><p>Anticipating market trends and rising lifestyle demands, QP Green Park is designed to be a connected, modern, and sustainable township where residential values go hand-in-hand with long-term investment vision.</p><h2>Synchronous Planning</h2><p>Developed on a scale of approximately 13.7 hectares, QP Green Park offers a diverse product portfolio including 568 townhouses and 68 commercial shophouses. Each zone is designed for optimal functionality and long-term value.</p><h2>Green Urban Philosophy</h2><p>Developed with a "people-centric" spirit, QP Green Park focuses on living experience and environmental quality. Open spaces and green landscape design are prioritized to offer a natural yet modern living environment.</p><h2>Sustainable Investment Value</h2><p>QP Green Park stands in a region experiencing strong industrial, infrastructure, and population growth — key drivers for future real estate value appreciation.</p>',
			),
			'zh' => array(
				'investment_total' => '13,000 亿越盾',
				'investment'       => '13,000 亿越盾',
				'title'            => 'QP Green Park 住宅区',
				'subtitle'         => '绿色城镇地标 — 在北新渊塑造全新生活标准',
				'category'         => '低层住宅区',
				'address'          => '胡志明市北新渊',
				'scale'            => '13.7 公顷 — 568 套联排别墅 + 68 套商铺',
				'area_total'       => '13.7 公顷',
				'investor'         => 'QP Holdings',
				'developer'        => 'QP Holdings',
				'completion'       => '2026',
				'handover'         => '基础设施已完善',
				'hero_heading'     => "绿色城镇地标 — 在北新渊塑造全新生活标准\n同步且现代化的城镇",
				'highlights'       => "13.7 公顷低层住宅项目 — 13,000 亿越盾\n568 套联排别墅 + 68 套商业商铺\n绿色、同步、现代的城市导向\n北新渊 DT742 干道上的战略位置",
				'content'          => '<p>QP Green Park 规划为平阳省北新渊一个同步、 modern 且充满人文价值的城镇项目，实现了绿色居住空间、全方位配套设施与长期发展潜力的和谐统一。</p><h2>发展愿景</h2><p>顺应市场发展趋势与客户日益提升的生活需求，QP Green Park 规划以建设一个具备连接性、现代且可持续发展的城镇为目标，使安居价值与长期投资愿景并驾齐驱。</p><h2>同步规划</h2><p>项目在约 13.7 公顷的土地上开发，提供多元化的产品组合，包括 568 套联排别墅 and 68 套商业商铺。每个分区都经过精心设计，以优化使用功能、空间连接和长期开发价值。</p><h2>绿色城市理念</h2><p>QP Green Park 秉承"以人为本"的精神，重点关注居住体验和居住环境的品质。景观要素、开放空间和绿化系统在总体规划中备受重视，旨在带来亲近自然且满足现代生活需求的居住环境。</p><h2>可持续投资价值</h2><p>QP Green Park 位于工业、基础设施和人口强劲增长的区域，这些因素在未来房产价值塑造过程中发挥着重要作用。</p>',
			),
		),

		/* ── 7. Khu phức hợp cao tầng Markup ── */
		'khu-phuc-hop-cao-tang-markup' => array(
			'en' => array(
				'investment_total' => '10,400 billion VND',
				'investment'       => '10,400 billion VND',
				'title'      => 'Markup High-rise Complex',
				'subtitle'   => 'New urban icon in the dynamically developing hub of Northern HCMC',
				'category'   => 'High-rise Complex',
				'address'    => 'Thoi Hoa Ward, Ho Chi Minh City',
				'scale'      => '15.42 ha — 16,000 – 18,000 residents',
				'area_total' => '15.42 ha',
				'investor'   => 'QP Holdings',
				'developer'  => 'QP Holdings',
				'completion' => '2028',
				'handover'   => 'Construction starts Q1/2028',
				'hero_heading'     => "New urban icon in Northern Ho Chi Minh City\nDynamic high-rise complex",
				'highlights'       => "Markup high-rise complex in Northern HCMC\nTotal investment of 10,400 billion VND\nExpected population of 16,000 – 18,000 residents\nIntegrated high-rise apartments, shopping mall, preschool",
				'content'          => '<p>Markup High-rise Complex is developed as a modern, multi-functional urban development in Thoi Hoa Ward — a dynamically growing industrial, urban, and service center in Northern Ho Chi Minh City. Markup aims to set a new standard for urban development where living, commerce, community, and growth values converge in a modern, synchronous ecosystem.</p><h2>Strategic Position</h2><p>Located in Thoi Hoa Ward, Ho Chi Minh City, the project benefits from its strategic presence in a key industrial-urban development hub. Markup enjoys convenient connections to established industrial parks like My Phuoc 2 and My Phuoc 3, as well as an improving regional transportation network.</p><h2>15.42 ha Scale</h2><p>Developed on a scale of approximately 15.42 hectares with a projected investment of 10,400 billion VND, Markup High-rise Complex is designed to be a landmark large-scale project in Northern HCMC. The development integrates modern high-rise apartments, commercial services, preschool education, a central square, and synchronous internal amenities.</p><h2>Investment Value</h2><p>A strategic location, large land bank, and modern planning make Markup not only an ideal place for residence, commerce, and services, but also a source of long-term growth potential for investors and future residents.</p>',
			),
			'zh' => array(
				'investment_total' => '104,000 亿越盾',
				'investment'       => '104,000 亿越盾',
				'title'      => 'Markup 高层综合体',
				'subtitle'   => '胡志明市北部蓬勃发展区的新城市地标',
				'category'   => '高层综合体',
				'address'    => '胡志明市泰和坊',
				'scale'      => '15.42 公顷 — 16,000 – 18,000 名居民',
				'area_total' => '15.42 公顷',
				'investor'   => 'QP Holdings',
				'developer'  => 'QP Holdings',
				'completion' => '2028',
				'handover'   => '2028 年第一季度动工建设',
				'hero_heading'     => "胡志明市北部新城市地标\n活力高层综合体",
				'highlights'       => "15.42 公顷高层综合体 — 104,000 亿越盾\n规划人口 16,000 – 18,000 名居民\n集成高层公寓、商业中心、幼儿教育\n泰和坊的战略区位",
				'content'          => '<p>Markup 高层综合体是胡志明市北部一个功能完备的现代都市综合项目，位于工业、城市和服务业蓬勃发展的泰和坊。Markup 旨在树立城市开发的新标准，使居住空间、商业、社区与价值增长在现代、同步的生态系统中交汇。</p><h2>战略位置</h2><p>项目位于胡志明市泰和坊，地处重点工业与城市开发的核心地带。项目可便捷联通已成熟的工业区（如美福 2、美福 3），并受益于行业内不断完善的区域交通基础设施。</p><h2>15.42 公顷规模</h2><p>项目规划面积约 15.42 公顷，预计总投资 104,000 亿越盾，定位为胡志明市北部极具战略意义的大型高层综合体项目。项目集现代高层住宅公寓、商业服务、幼儿教育、中央广场以及同步的区内配套于一体。</p><h2>投资价值</h2><p>优越的区位优势、规模庞大的土地储备以及现代化的规划定位，使 Markup 不仅能满足安居、商业与服务需求，更能为投资者和未来的社区居民开辟长期的发展潜力。</p>',
			),
		),

		/* ── 8. Cụm công nghiệp An Bình 7 ── */
		'cum-cong-nghiep-an-binh-7' => array(
			'en' => array(
				'investment_total' => '12,000 billion VND',
				'investment'       => '12,000 billion VND',
				'title'      => 'An Binh 7 Industrial Cluster',
				'subtitle'   => 'Modern industrial complex — Driving next-generation manufacturing development',
				'category'   => 'Factories for Rent',
				'address'    => 'Phu Giao Commune, Ho Chi Minh City',
				'scale'      => '50 ha',
				'area_total' => '50 ha',
				'investor'   => 'QP Holdings',
				'developer'  => 'QP Holdings',
				'completion' => '2030',
				'handover'   => 'Operation starts Q1/2030',
				'hero_heading'     => "Modern industrial complex\nDriving next-generation manufacturing development",
				'highlights'       => "50-hectare scale — total investment of 12,000 billion VND\nModern and synchronous industrial cluster model\nIntegrated technical infrastructure, warehousing, and logistics\nStrategic connection in Northern Ho Chi Minh City",
				'content'          => '<p>An Binh 7 Industrial Cluster is developed as a large-scale rental factory complex in Northern Ho Chi Minh City — combining a strategic location, massive investment capital, and synchronously planned infrastructure aligned with modern industrial thinking.</p><h2>Strategic Connection</h2><p>Located in Phu Giao Commune, Ho Chi Minh City, the project has a strategic presence in a region with high potential for industrial development, facilitating connection to production centers, logistics hubs, and the Southern inter-regional transportation network.</p><h2>50 ha Scale</h2><p>Invested on a scale of approximately 50 hectares with a projected capital of 12,000 billion VND, An Binh 7 Industrial Cluster is oriented to be a key industrial project in the South. The master plan prioritizes the synchronization of technical infrastructure, operational spaces, and the ability to meet diverse manufacturing requirements.</p><h2>Development Philosophy</h2><p>An Binh 7 is designed as a synchronous industrial cluster where infrastructure does not just serve manufacturing but serves as a foundation to enhance operational efficiency for enterprises. The project integrates large rental factories, warehousing and logistics systems, technical infrastructure, modern internal roads, and business support service zones.</p><h2>Investment Value</h2><p>凭借区位、投资规模及清晰发展定位的多重优势，安平 7 不仅能满足企业实际的运营需求，更可为国内外企业开辟长期的发展潜力。</p>',
			),
			'zh' => array(
				'investment_total' => '120,000 亿越盾',
				'investment'       => '120,000 亿越盾',
				'title'      => '安平 7 工业集群',
				'subtitle'   => '安平 7 工业集群',
				'category'   => '厂房出租',
				'address'    => '胡志明市富教社',
				'scale'      => '50 公顷',
				'area_total' => '50 公顷',
				'investor'   => 'QP Holdings',
				'developer'  => 'QP Holdings',
				'completion' => '2030',
				'handover'   => '2030 年第一季度投入运营',
				'hero_heading'     => "现代化工业集群\n驱动新一代制造业发展",
				'highlights'       => "占地 50 公顷 — 总投资 120,000 亿越盾\n现代化、同步的工业区模式\n集成技术基础设施、仓储和物流\n胡志明市北部的战略连接点",
				'content'          => '<p>安平 7 工业集群是胡志明市北部的一个大型出租厂房综合项目，集战略区位优势、庞大投资规模以及按现代工业思维同步规划的基础设施于一体。</p><h2>战略连接位置</h2><p>项目位于胡志明市富教社，地处极具工业开发潜力的区域，方便连接至各制造中心、物流枢纽及南部跨区域交通网络。</p><h2>50 公顷规模</h2><p>项目规划面积约 50 公顷，预计总投资 120,000 亿越盾，定位为南部极具战略意义的大型工业集群。项目总体规划优先考虑技术基础设施的同步性、运营空间的组织以及满足多元化生产需求的能力。</p><h2>开发理念</h2><p>安平 7 致力于打造同步的工业集群模式，基础设施不仅服务于生产活动，更作为提升企业运营效率的基石。项目集成了大型出租厂房、仓储和 logistics 系统，配套技术设施、现代内部交通以及企业管理支持服务区。</p><h2>投资价值</h2><p>凭借区位、投资规模及清晰发展定位的多重优势，安平 7 不仅能满足企业实际的运营需求，更可为国内外企业开辟长期的发展潜力。</p>',
			),
		),
	);
}

/**
 * Resolve project source slug to canonical seed key.
 */
function qp_holdings_resolve_project_canonical_slug( string $slug ): string {
	$s = trim( strtolower( $slug ) );
	if ( '' === $s ) {
		return '';
	}
	if ( false !== strpos( $s, 'binh-duong-center' ) || false !== strpos( $s, 'binhduongcenter' ) ) {
		return 'qp-binh-duong-center';
	}
	if ( false !== strpos( $s, 'luxia' ) ) {
		return 'qp-luxia-one';
	}
	if ( false !== strpos( $s, 'hiep-thanh' ) || false !== strpos( $s, 'hiepthanh' ) ) {
		return 'hiep-thanh-city';
	}
	if ( false !== strpos( $s, 'idc' ) ) {
		return 'qp-idc';
	}
	if ( false !== strpos( $s, 'quang-phuc' ) || false !== strpos( $s, 'quangphuc' ) ) {
		return 'khu-do-thi-quang-phuc-4';
	}
	if ( false !== strpos( $s, 'green-park' ) || false !== strpos( $s, 'greenpark' ) ) {
		return 'qp-green-park';
	}
	if ( false !== strpos( $s, 'markup' ) ) {
		return 'khu-phuc-hop-cao-tang-markup';
	}
	if ( false !== strpos( $s, 'an-binh' ) || false !== strpos( $s, 'anbinh' ) ) {
		return 'cum-cong-nghiep-an-binh-7';
	}
	return $s;
}

/**
 * Translate dynamic project category strings.
 */
function qp_holdings_translate_project_category( string $text, string $lang ): string {
	$raw = trim( $text );
	if ( '' === $raw ) {
		return '';
	}

	$map = array(
		'en' => array(
			'mixed-use cao tầng'          => 'High-rise Mixed-use Complex',
			'khu phức hợp cao tầng'       => 'High-rise Complex',
			'phức hợp cao tầng'           => 'High-rise Complex',
			'khu dân cư thấp tầng'        => 'Low-rise Housing Area',
			'khu dân cư'                  => 'Residential Area',
			'dân cư thấp tầng'            => 'Low-rise Housing Area',
			'căn hộ phức hợp'             => 'Complex Apartment',
			'khu căn hộ phức hợp'         => 'Complex Apartment',
			'khu đô thị đa chức năng'     => 'Multi-functional Urban Area',
			'khu đô thị'                  => 'Urban Area',
			'trung tâm thương mại'        => 'Commercial Center',
			'nhà xưởng cho thuê'          => 'Factories for Rent',
			'nhà xưởng'                   => 'Factories',
			'cụm công nghiệp'             => 'Industrial Cluster',
			'khu công nghiệp'             => 'Industrial Park',
			'bất động sản công nghiệp'    => 'Industrial Real Estate',
			'bất động sản thương mại'     => 'Commercial Real Estate',
			'bất động sản nhà ở'          => 'Residential Real Estate',
			'căn hộ cao cấp'              => 'Luxury Apartment',
			'nhà phố thương mại'          => 'Commercial Shophouse',
		),
		'zh' => array(
			'mixed-use cao tầng'          => '高层综合体',
			'khu phức hợp cao tầng'       => '高层综合体',
			'phức hợp cao tầng'           => '高层综合体',
			'khu dân cư thấp tầng'        => '低层住宅区',
			'khu dân cư'                  => '住宅区',
			'dân cư thấp tầng'            => '低层住宅区',
			'căn hộ phức hợp'             => '综合公寓',
			'khu căn hộ phức hợp'         => '综合公寓',
			'khu đô thị đa chức năng'     => '多功能城区',
			'khu đô thị'                  => '城市区',
			'trung tâm thương mại'        => '商业中心',
			'nhà xưởng cho thuê'          => '厂房出租',
			'nhà xưởng'                   => '厂房',
			'cụm công nghiệp'             => '工业集群',
			'khu công nghiệp'             => '工业园区',
			'bất động sản công nghiệp'    => '工业房地产',
			'bất động sản thương mại'     => '商业地产',
			'bất động sản nhà ở'          => '住宅房地产',
			'căn hộ cao cấp'              => '高端公寓',
			'nhà phố thương mại'          => '商业商铺',
		),
	);

	$lower = function_exists( 'mb_strtolower' ) ? mb_strtolower( $raw, 'UTF-8' ) : strtolower( $raw );
	if ( isset( $map[ $lang ][ $lower ] ) ) {
		return $map[ $lang ][ $lower ];
	}

	foreach ( ( $map[ $lang ] ?? array() ) as $k => $v ) {
		if ( false !== mb_stripos( $raw, $k ) ) {
			return $v;
		}
	}

	return $raw;
}

/**
 * Translate project investment strings.
 */
function qp_holdings_translate_project_investment( string $text, string $lang ): string {
	$raw = trim( $text );
	if ( '' === $raw ) {
		return '';
	}

	if ( 'en' === $lang ) {
		$res = $raw;
		$res = preg_replace( '/(Dự\s*kiến|dự\s*kiến)/iu', 'Expected', $res );
		$res = preg_replace( '/(Khoảng|khoảng)/iu', 'Approx.', $res );
		$res = preg_replace( '/(Tổng\s*vốn\s*đầu\s*tư)/iu', 'Total investment', $res );
		$res = preg_replace( '/\s*tỷ\s*đồng/iu', ' billion VND', $res );
		$res = preg_replace( '/\s*tỷ/iu', ' billion VND', $res );
		$res = preg_replace( '/(billion\s*VND\s*)+/i', 'billion VND', $res );
		$res = preg_replace_callback( '/(\d+)\.(\d{3})/', static function ( $m ) {
			return $m[1] . ',' . $m[2];
		}, $res );
		return trim( preg_replace( '/\s+/', ' ', $res ) );
	}

	if ( 'zh' === $lang ) {
		$res = $raw;
		$is_expected = (bool) preg_match( '/(Dự\s*kiến|dự\s*kiến)/iu', $res );
		$is_approx   = (bool) preg_match( '/(Khoảng|khoảng)/iu', $res );

		$clean_num = preg_replace( '/[^\d.,]/', '', $res );
		$clean_num = str_replace( array( '.', ',' ), array( '', '.' ), $clean_num );
		$num = floatval( $clean_num );
		if ( $num > 0 ) {
			$zh_num = number_format( $num * 10, 0, '.', ',' );
			$prefix = $is_expected ? '预计 ' : ( $is_approx ? '约 ' : '' );
			return $prefix . $zh_num . ' 亿越盾';
		}
		$res = preg_replace( '/(Dự\s*kiến|dự\s*kiến)/iu', '预计', $res );
		$res = preg_replace( '/(Khoảng|khoảng)/iu', '约', $res );
		$res = preg_replace( '/(Tổng\s*vốn\s*đầu\s*tư)/iu', '总投资', $res );
		$res = preg_replace( '/\s*tỷ\s*đồng/iu', ' 亿越盾', $res );
		$res = preg_replace( '/\s*tỷ/iu', ' 亿越盾', $res );
		return trim( preg_replace( '/\s+/', ' ', $res ) );
	}

	return $raw;
}

/**
 * Translate project handover / progress strings.
 */
function qp_holdings_translate_project_handover( string $text, string $lang ): string {
	$raw = trim( $text );
	if ( '' === $raw ) {
		return '';
	}

	if ( 'en' === $lang ) {
		$res = $raw;
		$replacements = array(
			'/Khởi\s*công\s*xây\s*dựng/iu'     => 'Construction starts',
			'/Khởi\s*công/iu'                  => 'Construction starts',
			'/Đang\s*xây\s*dựng/iu'            => 'Under construction',
			'/Đang\s*thi\s*công/iu'            => 'Under construction',
			'/Đã\s*hoàn\s*thiện\s*hạ\s*tầng/iu' => 'Infrastructure completed',
			'/Hoàn\s*thiện\s*hạ\s*tầng/iu'     => 'Infrastructure completed',
			'/Đang\s*vận\s*hành/iu'            => 'In operation',
			'/Đã\s*vận\s*hành/iu'              => 'In operation',
			'/Đã\s*đi\s*vào\s*hoạt\s*động/iu'  => 'In operation',
			'/Vận\s*hành/iu'                   => 'Operation starts',
			'/Đưa\s*vào\s*hoạt\s*động/iu'      => 'Operation starts',
			'/Bàn\s*giao/iu'                   => 'Handover',
			'/Quý\s*I\s*[\/\-]\s*/iu'          => 'Q1/',
			'/Quý\s*II\s*[\/\-]\s*/iu'         => 'Q2/',
			'/Quý\s*III\s*[\/\-]\s*/iu'        => 'Q3/',
			'/Quý\s*IV\s*[\/\-]\s*/iu'         => 'Q4/',
			'/Quý\s*1\s*[\/\-]\s*/iu'          => 'Q1/',
			'/Quý\s*2\s*[\/\-]\s*/iu'          => 'Q2/',
			'/Quý\s*3\s*[\/\-]\s*/iu'          => 'Q3/',
			'/Quý\s*4\s*[\/\-]\s*/iu'          => 'Q4/',
			'/Quý\s*I/iu'                    => 'Q1',
			'/Quý\s*II/iu'                   => 'Q2',
			'/Quý\s*III/iu'                  => 'Q3',
			'/Quý\s*IV/iu'                   => 'Q4',
			'/Quý\s*1/iu'                    => 'Q1',
			'/Quý\s*2/iu'                    => 'Q2',
			'/Quý\s*3/iu'                    => 'Q3',
			'/Quý\s*4/iu'                    => 'Q4',
			'/Năm\s*/iu'                       => '',
		);
		foreach ( $replacements as $pattern => $replace ) {
			$res = preg_replace( $pattern, $replace, $res );
		}
		return trim( preg_replace( '/\s+/', ' ', $res ) );
	}

	if ( 'zh' === $lang ) {
		if ( preg_match( '/Khởi\s*công\s*xây\s*dựng\s*Quý\s*(I|1)[\/\-](\d{4})/iu', $raw, $m ) ) {
			return $m[2] . ' 年第一季度动工建设';
		}
		if ( preg_match( '/Khởi\s*công\s*xây\s*dựng\s*Quý\s*(II|2)[\/\-](\d{4})/iu', $raw, $m ) ) {
			return $m[2] . ' 年第二季度动工建设';
		}
		if ( preg_match( '/Khởi\s*công\s*xây\s*dựng\s*Quý\s*(III|3)[\/\-](\d{4})/iu', $raw, $m ) ) {
			return $m[2] . ' 年第三季度动工建设';
		}
		if ( preg_match( '/Khởi\s*công\s*xây\s*dựng\s*Quý\s*(IV|4)[\/\-](\d{4})/iu', $raw, $m ) ) {
			return $m[2] . ' 年第四季度动工建设';
		}
		if ( preg_match( '/(Vận\s*hành|Đưa\s*vào\s*hoạt\s*động)\s*Quý\s*(I|1)[\/\-](\d{4})/iu', $raw, $m ) ) {
			return $m[3] . ' 年第一季度投入运营';
		}
		if ( preg_match( '/(Vận\s*hành|Đưa\s*vào\s*hoạt\s*động)\s*Quý\s*(II|2)[\/\-](\d{4})/iu', $raw, $m ) ) {
			return $m[3] . ' 年第二季度投入运营';
		}
		if ( preg_match( '/(Vận\s*hành|Đưa\s*vào\s*hoạt\s*động)\s*Quý\s*(III|3)[\/\-](\d{4})/iu', $raw, $m ) ) {
			return $m[3] . ' 年第三季度投入运营';
		}
		if ( preg_match( '/(Vận\s*hành|Đưa\s*vào\s*hoạt\s*động)\s*Quý\s*(IV|4)[\/\-](\d{4})/iu', $raw, $m ) ) {
			return $m[3] . ' 年第四季度投入运营';
		}
		if ( preg_match( '/Bàn\s*giao\s*Quý\s*(I|1)[\/\-](\d{4})/iu', $raw, $m ) ) {
			return $m[2] . ' 年第一季度交付';
		}
		if ( preg_match( '/Bàn\s*giao\s*Quý\s*(II|2)[\/\-](\d{4})/iu', $raw, $m ) ) {
			return $m[2] . ' 年第二季度交付';
		}
		if ( preg_match( '/Bàn\s*giao\s*Quý\s*(III|3)[\/\-](\d{4})/iu', $raw, $m ) ) {
			return $m[2] . ' 年第三季度交付';
		}
		if ( preg_match( '/Bàn\s*giao\s*Quý\s*(IV|4)[\/\-](\d{4})/iu', $raw, $m ) ) {
			return $m[2] . ' 年第四季度交付';
		}
		if ( preg_match( '/(Đã\s*đi\s*vào\s*hoạt\s*động|Đang\s*vận\s*hành|Đã\s*vận\s*hành)/iu', $raw ) ) {
			return '已投入运营';
		}
		if ( preg_match( '/(Đã\s*hoàn\s*thiện\s*hạ\s*tầng|Hoàn\s*thiện\s*hạ\s*tầng)/iu', $raw ) ) {
			return '基础设施已完善';
		}
		if ( preg_match( '/(Đang\s*xây\s*dựng|Đang\s*thi\s*công)/iu', $raw ) ) {
			return '建设中';
		}

		$res = $raw;
		$replacements = array(
			'/Khởi\s*công\s*xây\s*dựng/iu' => '动工建设',
			'/Khởi\s*công/iu'              => '动工',
			'/Đang\s*xây\s*dựng/iu'        => '建设中',
			'/Đang\s*thi\s*công/iu'        => '施工中',
			'/Hoàn\s*thiện\s*hạ\s*tầng/iu' => '基础设施已完善',
			'/Đang\s*vận\s*hành/iu'        => '已投入运营',
			'/Vận\s*hành/iu'               => '投入运营',
			'/Bàn\s*giao/iu'               => '交付',
			'/Quý\s*I/iu'                => '第一季度',
			'/Quý\s*II/iu'               => '第二季度',
			'/Quý\s*III/iu'              => '第三季度',
			'/Quý\s*IV/iu'               => '第四季度',
			'/Quý\s*1/iu'                => '第一季度',
			'/Quý\s*2/iu'                => '第二季度',
			'/Quý\s*3/iu'                => '第三季度',
			'/Quý\s*4/iu'                => '第四季度',
		);
		foreach ( $replacements as $pattern => $replace ) {
			$res = preg_replace( $pattern, $replace, $res );
		}
		return trim( preg_replace( '/\s+/', ' ', $res ) );
	}

	return $raw;
}

/**
 * Translate project scale & area strings.
 */
function qp_holdings_translate_project_scale( string $text, string $lang ): string {
	$raw = trim( $text );
	if ( '' === $raw ) {
		return '';
	}

	if ( 'en' === $lang ) {
		$res = $raw;
		$res = preg_replace_callback( '/(\d+),(\d+)\s*(ha|hecta|m2|m²)/iu', static function ( $m ) {
			return $m[1] . '.' . $m[2] . ' ' . ( 'm2' === strtolower( $m[3] ) || 'm²' === strtolower( $m[3] ) ? 'sqm' : 'ha' );
		}, $res );
		$res = preg_replace_callback( '/^(\d+),(\d+)$/', static function ( $m ) {
			return $m[1] . '.' . $m[2];
		}, $res );

		$replacements = array(
			'/nhà\s*phố/iu'            => 'townhouses',
			'/shophouse(s)?/iu'        => 'shophouses',
			'/căn\s*hộ/iu'             => 'apartments',
			'/cư\s*dân/iu'             => 'residents',
			'/người/iu'                => 'people',
			'/hecta/iu'                => 'ha',
			'/tỷ\s*đồng/iu'            => 'billion VND',
			'/tỷ/iu'                   => 'billion VND',
			'/m²/iu'                   => 'sqm',
			'/m2/iu'                   => 'sqm',
		);
		foreach ( $replacements as $pattern => $replace ) {
			$res = preg_replace( $pattern, $replace, $res );
		}
		return trim( preg_replace( '/\s+/', ' ', $res ) );
	}

	if ( 'zh' === $lang ) {
		$res = $raw;
		$res = preg_replace_callback( '/(\d+),(\d+)\s*(ha|hecta|m2|m²|公顷)/iu', static function ( $m ) {
			return $m[1] . '.' . $m[2] . ' ' . ( 'm2' === strtolower( $m[3] ) || 'm²' === strtolower( $m[3] ) ? '平方米' : '公顷' );
		}, $res );
		$res = preg_replace( '/\s*ha/iu', ' 公顷', $res );
		$res = preg_replace( '/\s*hecta/iu', ' 公顷', $res );
		$res = preg_replace( '/\s*(m²|m2)/iu', ' 平方米', $res );

		$replacements = array(
			'/nhà\s*phố/iu'            => '套联排别墅',
			'/shophouse(s)?/iu'        => '套商铺',
			'/căn\s*hộ/iu'             => '套公寓',
			'/cư\s*dân/iu'             => '名居民',
			'/người/iu'                => '人',
			'/tỷ\s*đồng/iu'            => '亿越盾',
			'/tỷ/iu'                   => '亿越盾',
		);
		foreach ( $replacements as $pattern => $replace ) {
			$res = preg_replace( $pattern, $replace, $res );
		}
		return trim( preg_replace( '/\s+/', ' ', $res ) );
	}

	return $raw;
}

/**
 * Translate project address strings.
 */
function qp_holdings_translate_project_address( string $text, string $lang ): string {
	$raw = trim( $text );
	if ( '' === $raw ) {
		return '';
	}

	if ( 'en' === $lang ) {
		$res = $raw;
		$replacements = array(
			'/Phường\s*Bình\s*Cơ/iu'            => 'Binh Co Ward',
			'/Phường\s*Bình\s*Dương/iu'         => 'Binh Duong Ward',
			'/Phường\s*Hòa\s*Phú/iu'            => 'Hoa Phu Ward',
			'/Phường\s*Thới\s*Hòa/iu'           => 'Thoi Hoa Ward',
			'/Phường\s*Phú\s*Hòa/iu'            => 'Phu Hoa Ward',
			'/Phường\s*Thủ\s*Dầu\s*Một/iu'      => 'Thu Dau Mot Ward',
			'/TP\.?\s*Thủ\s*Dầu\s*Một/iu'       => 'Thu Dau Mot City',
			'/Thành\s*phố\s*Thủ\s*Dầu\s*Một/iu' => 'Thu Dau Mot City',
			'/Thủ\s*Dầu\s*Một/iu'               => 'Thu Dau Mot',
			'/Huyện\s*Bắc\s*Tân\s*Uyên/iu'      => 'Bac Tan Uyen District',
			'/Bắc\s*Tân\s*Uyên/iu'              => 'Bac Tan Uyen',
			'/Huyện\s*Phú\s*Giáo/iu'            => 'Phu Giao District',
			'/Phú\s*Giáo/iu'                    => 'Phu Giao',
			'/KCN\s*Nam\s*Tân\s*Uyên/iu'        => 'Nam Tan Uyen Industrial Park',
			'/Khu\s*công\s*nghiệp\s*Nam\s*Tân\s*Uyên/iu' => 'Nam Tan Uyen Industrial Park',
			'/Tỉnh\s*Bình\s*Dương/iu'           => 'Binh Duong Province',
			'/Bình\s*Dương/iu'                  => 'Binh Duong',
			'/TP\.?\s*Hồ\s*Chí\s*Minh/iu'       => 'Ho Chi Minh City',
			'/TP\.?\s*HCM/iu'                   => 'Ho Chi Minh City',
			'/Thành\s*phố\s*Hồ\s*Chí\s*Minh/iu' => 'Ho Chi Minh City',
			'/Hồ\s*Chí\s*Minh/iu'               => 'Ho Chi Minh City',
			'/Phường\s+([^\s,]+)/iu'            => '$1 Ward',
			'/Xã\s+([^\s,]+)/iu'                => '$1 Commune',
			'/Quận\s+([^\s,]+)/iu'              => '$1 District',
			'/Huyện\s+([^\s,]+)/iu'             => '$1 District',
			'/Thị\s*xã\s+([^\s,]+)/iu'          => '$1 Town',
			'/Tỉnh\s+([^\s,]+)/iu'              => '$1 Province',
		);
		foreach ( $replacements as $pattern => $replace ) {
			$res = preg_replace( $pattern, $replace, $res );
		}
		return trim( preg_replace( '/\s+/', ' ', $res ) );
	}

	if ( 'zh' === $lang ) {
		$res = $raw;
		$replacements = array(
			'/Phường\s*Bình\s*Cơ/iu'            => '平基坊',
			'/Phường\s*Bình\s*Dương/iu'         => '平阳坊',
			'/Phường\s*Hòa\s*Phú/iu'            => '和富坊',
			'/Phường\s*Thới\s*Hòa/iu'           => '泰和坊',
			'/Phường\s*Phú\s*Hòa/iu'            => '富和坊',
			'/Phường\s*Thủ\s*Dầu\s*Một/iu'      => '土龙木坊',
			'/TP\.?\s*Thủ\s*Dầu\s*Một/iu'       => '土龙木市',
			'/Thành\s*phố\s*Thủ\s*Dầu\s*Một/iu' => '土龙木市',
			'/Thủ\s*Dầu\s*Một/iu'               => '土龙木',
			'/Huyện\s*Bắc\s*Tân\s*Uyên/iu'      => '北新渊县',
			'/Bắc\s*Tân\s*Uyên/iu'              => '北新渊',
			'/Huyện\s*Phú\s*Giáo/iu'            => '富教县',
			'/Phú\s*Giáo/iu'                    => '富教',
			'/KCN\s*Nam\s*Tân\s*Uyên/iu'        => '南新渊工业区',
			'/Khu\s*công\s*nghiệp\s*Nam\s*Tân\s*Uyên/iu' => '南新渊工业区',
			'/Tỉnh\s*Bình\s*Dương/iu'           => '平阳省',
			'/Bình\s*Dương/iu'                  => '平阳',
			'/TP\.?\s*Hồ\s*Chí\s*Minh/iu'       => '胡志明市',
			'/TP\.?\s*HCM/iu'                   => '胡志明市',
			'/Thành\s*phố\s*Hồ\s*Chí\s*Minh/iu' => '胡志明市',
			'/Hồ\s*Chí\s*Minh/iu'               => '胡志明市',
		);
		foreach ( $replacements as $pattern => $replace ) {
			$res = preg_replace( $pattern, $replace, $res );
		}
		return trim( preg_replace( '/\s+/', ' ', $res ) );
	}

	return $raw;
}

/**
 * Translate project hero heading strings.
 */
function qp_holdings_translate_project_hero_heading( string $text, string $lang ): string {
	$raw = trim( $text );
	if ( '' === $raw ) {
		return '';
	}

	if ( 'en' === $lang ) {
		if ( false !== mb_stripos( $raw, 'Biểu tượng sống cao cấp' ) || false !== mb_stripos( $raw, 'trung tâm Thủ Dầu Một' ) ) {
			return "Premium living icon in the heart of Thu Dau Mot\nA symbol of contemporary and dynamic living";
		}
		if ( false !== mb_stripos( $raw, 'Tâm điểm thịnh vượng' ) ) {
			return "A focal point of prosperity,\na symbol of contemporary living";
		}
	}

	if ( 'zh' === $lang ) {
		if ( false !== mb_stripos( $raw, 'Biểu tượng sống cao cấp' ) || false !== mb_stripos( $raw, 'trung tâm Thủ Dầu Một' ) ) {
			return "土龙木市中心的高端居住地标\n当代活力生活的象征";
		}
		if ( false !== mb_stripos( $raw, 'Tâm điểm thịnh vượng' ) ) {
			return "繁荣焦点，\n现代生活象征";
		}
	}

	return $raw;
}

/**
 * Retrieve a translated field for a seeded qp_project post.
 *
 * @param WP_Post|int $post    Post object or ID.
 * @param string      $field   Field key: title|subtitle|category|address|scale|area_total|investor|developer|completion|handover|highlights|hero_heading|investment_total|investment|content.
 * @param string      $fallback Value to return when no translation exists (pass the Vietnamese default).
 */
function qp_holdings_seeded_project_translation_for_post( $post, string $field, string $fallback = '' ): string {
	if ( is_admin() ) {
		return $fallback;
	}

	$lang = qp_holdings_current_language();
	if ( 'vi' === $lang ) {
		return $fallback;
	}

	$post_obj = $post instanceof WP_Post ? $post : get_post( $post );
	if ( ! $post_obj instanceof WP_Post || 'qp_project' !== $post_obj->post_type ) {
		return $fallback;
	}

	// 1) Linked Polylang translation copy with a real field.
	if ( function_exists( 'pll_get_post' ) ) {
		$translated_id = (int) pll_get_post( (int) $post_obj->ID, $lang );
		if ( $translated_id && $translated_id !== (int) $post_obj->ID ) {
			$translated = get_post( $translated_id );
			if ( $translated instanceof WP_Post ) {
				if ( 'title' === $field ) {
					$val = trim( (string) $translated->post_title );
					if ( '' !== $val && ! qp_holdings_text_looks_vietnamese( $val ) ) {
						return $val;
					}
				} elseif ( 'content' === $field ) {
					$val = trim( (string) $translated->post_content );
					if ( '' !== $val && ! qp_holdings_text_looks_vietnamese( $val ) ) {
						return $val;
					}
				} elseif ( 'subtitle' === $field && '' !== trim( (string) $translated->post_excerpt ) && ! qp_holdings_text_looks_vietnamese( (string) $translated->post_excerpt ) ) {
					return (string) $translated->post_excerpt;
				} else {
					$meta_keys = array( $field );
					if ( 'category' === $field ) {
						$meta_keys = array( 'category_info', 'category' );
					} elseif ( 'investor' === $field ) {
						$meta_keys = array( 'investor_name', 'investor' );
					} elseif ( 'completion' === $field ) {
						$meta_keys = array( 'completion_year', 'completion' );
					}
					foreach ( $meta_keys as $mk ) {
						$m_val = get_post_meta( $translated_id, $mk, true );
						if ( is_string( $m_val ) && '' !== trim( $m_val ) && ! qp_holdings_text_looks_vietnamese( $m_val ) ) {
							return $m_val;
						}
					}
				}
			}
		}
	}

	// 2) Seeded dictionary map with canonical slug resolution
	$map            = qp_holdings_seeded_project_translations();
	$source_slug    = qp_holdings_translation_source_slug( $post_obj );
	$canonical_slug = qp_holdings_resolve_project_canonical_slug( $source_slug );

	$slugs_to_check = array_unique( array_filter( array( $source_slug, $canonical_slug ) ) );
	foreach ( $slugs_to_check as $s ) {
		if ( isset( $map[ $s ][ $lang ][ $field ] ) && '' !== trim( (string) $map[ $s ][ $lang ][ $field ] ) ) {
			return $map[ $s ][ $lang ][ $field ];
		}
		if ( in_array( $field, array( 'investment', 'investment_total' ), true ) ) {
			$alt_k = 'investment' === $field ? 'investment_total' : 'investment';
			if ( isset( $map[ $s ][ $lang ][ $alt_k ] ) && '' !== trim( (string) $map[ $s ][ $lang ][ $alt_k ] ) ) {
				return $map[ $s ][ $lang ][ $alt_k ];
			}
		}
	}

	// 3) Specialized dynamic pattern translators
	if ( in_array( $field, array( 'investment_total', 'investment' ), true ) ) {
		return qp_holdings_translate_project_investment( $fallback, $lang );
	}
	if ( 'category' === $field ) {
		return qp_holdings_translate_project_category( $fallback, $lang );
	}
	if ( 'handover' === $field ) {
		return qp_holdings_translate_project_handover( $fallback, $lang );
	}
	if ( in_array( $field, array( 'scale', 'area_total' ), true ) ) {
		return qp_holdings_translate_project_scale( $fallback, $lang );
	}
	if ( 'address' === $field ) {
		return qp_holdings_translate_project_address( $fallback, $lang );
	}
	if ( 'hero_heading' === $field ) {
		return qp_holdings_translate_project_hero_heading( $fallback, $lang );
	}

	return $fallback;
}


function qp_holdings_filter_seeded_project_title( string $title, int $post_id = 0 ): string {
	return qp_holdings_seeded_project_translation_for_post( $post_id, 'title', $title );
}
add_filter( 'the_title', 'qp_holdings_filter_seeded_project_title', 20, 2 );

function qp_holdings_filter_seeded_project_content( string $content ): string {
	$post = get_post();
	return qp_holdings_seeded_project_translation_for_post( $post, 'content', $content );
}
add_filter( 'the_content', 'qp_holdings_filter_seeded_project_content', 20 );

function qp_holdings_add_polylang_query_lang_for( array $args, string $lang ): array {
	$args['lang'] = $lang;

	/*
	 * Robust meta-based language filter as a fallback for when Polylang's
	 * built-in `lang` parameter doesn't properly filter custom post types.
	 * Translated copies carry a `_qp_translation_lang` meta ('en' or 'zh').
	 * Vietnamese originals do NOT have this meta.
	 */
	$lang_meta = array();
	if ( 'vi' === $lang ) {
		/* Vietnamese: exclude posts that are EN/ZH translations */
		$lang_meta = array(
			'key'     => '_qp_translation_lang',
			'compare' => 'NOT EXISTS',
		);
	} else {
		/* EN / ZH: only show posts tagged with the current language */
		$lang_meta = array(
			'key'     => '_qp_translation_lang',
			'compare' => '=',
			'value'   => $lang,
		);
	}

	if ( ! empty( $lang_meta ) ) {
		if ( empty( $args['meta_query'] ) ) {
			$args['meta_query'] = array( $lang_meta );
		} else {
			/* Wrap existing meta_query into an AND group with the language filter */
			$existing_meta = $args['meta_query'];
			$args['meta_query'] = array(
				'relation' => 'AND',
				$lang_meta,
				$existing_meta,
			);
		}
	}

	return $args;
}

function qp_holdings_add_polylang_query_lang( array $args ): array {
	$lang = 'vi';
	if ( function_exists( 'qp_holdings_current_language' ) ) {
		$lang = qp_holdings_current_language();
	} elseif ( function_exists( 'pll_current_language' ) ) {
		$lang = pll_current_language( 'slug' );
	}

	return qp_holdings_add_polylang_query_lang_for( $args, $lang );
}

/**
 * Listing query helper for translatable CPTs (news / projects / jobs).
 *
 * Listings always render the canonical Vietnamese post set so every language
 * shows the same items, the same images (card_image / thumbnails), and the same
 * ordering. The seeded the_title / get_the_excerpt / the_content filters then
 * translate each card's text into the active frontend language when a
 * translation exists, falling back to Vietnamese text otherwise.
 *
 * This avoids the divergence caused by per-language duplicate posts: previously
 * a non-Vietnamese listing only contained the subset of posts that had a
 * generated translation, which could surface stale posts with broken
 * (404) upload images while newer Vietnamese posts were hidden.
 */
function qp_holdings_wp_query_with_language_fallback( array $args ): WP_Query {
	/*
	 * Query across all Polylang languages and segment purely by the
	 * `_qp_translation_lang` meta. This returns the canonical Vietnamese source
	 * set (originals never carry that meta) while excluding the EN/ZH duplicate
	 * copies. It deliberately does NOT pass a Polylang `lang` filter so that
	 * source posts which were never assigned a Polylang language (e.g. newly
	 * seeded posts) are still listed instead of being silently dropped.
	 */
	$args['lang'] = '';

	$exclude_translations = array(
		'key'     => '_qp_translation_lang',
		'compare' => 'NOT EXISTS',
	);
	if ( empty( $args['meta_query'] ) ) {
		$args['meta_query'] = array( $exclude_translations );
	} else {
		$args['meta_query'] = array(
			'relation' => 'AND',
			$exclude_translations,
			$args['meta_query'],
		);
	}

	// Manually-created Polylang translations (added via the editor's language
	// box rather than the seeder) don't carry the `_qp_translation_lang` meta,
	// so the meta filter above can't drop them and they leak into the canonical
	// Vietnamese listing as duplicate English/Chinese cards. Belt-and-braces:
	// also exclude every post in this post type whose Polylang language is not
	// the default Vietnamese one. Posts with no assigned language are kept (so
	// never-assigned source posts still list).
	if ( function_exists( 'pll_get_post_language' ) ) {
		$qp_default_lang = function_exists( 'pll_default_language' ) ? pll_default_language() : 'vi';
		$qp_all_ids = get_posts(
			array(
				'post_type'        => $args['post_type'] ?? 'post',
				'post_status'      => $args['post_status'] ?? 'publish',
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'lang'             => '',
				'suppress_filters' => false,
			)
		);
		$qp_exclude_ids = array();
		foreach ( $qp_all_ids as $qp_maybe_id ) {
			$qp_post_lang = pll_get_post_language( (int) $qp_maybe_id );
			if ( $qp_post_lang && $qp_post_lang !== $qp_default_lang ) {
				$qp_exclude_ids[] = (int) $qp_maybe_id;
			}
		}
		if ( $qp_exclude_ids ) {
			$qp_existing_excludes = isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array();
			$args['post__not_in'] = array_values( array_unique( array_merge( $qp_existing_excludes, $qp_exclude_ids ) ) );
		}
	}

	/*
	 * On English/Chinese pages, hide canonical Vietnamese source posts that have
	 * no translation available. "Available" means either a hard-coded seed-map
	 * entry for the post's source slug, or a real Polylang translation copy with
	 * a non-empty title. Without this, untranslated posts would fall through the
	 * display-layer title/excerpt filters and show their Vietnamese text on the
	 * EN/ZH listings. (VI listings are unaffected.)
	 */
	$qp_lang = function_exists( 'qp_holdings_current_language' ) ? qp_holdings_current_language() : 'vi';
	if ( 'vi' !== $qp_lang ) {
		$qp_pt = $args['post_type'] ?? 'post';
		$qp_source_ids = get_posts(
			array(
				'post_type'        => $qp_pt,
				'post_status'      => $args['post_status'] ?? 'publish',
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'lang'             => '',
				'suppress_filters' => false,
				'meta_query'       => array(
					array(
						'key'     => '_qp_translation_lang',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);
		$qp_hide_ids = array();
		foreach ( $qp_source_ids as $qp_source_id ) {
			if ( ! qp_holdings_listing_post_has_translation( (int) $qp_source_id, (string) $qp_pt, $qp_lang ) ) {
				$qp_hide_ids[] = (int) $qp_source_id;
			}
		}
		if ( $qp_hide_ids ) {
			$qp_existing_hides    = isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array();
			$args['post__not_in'] = array_values( array_unique( array_merge( $qp_existing_hides, $qp_hide_ids ) ) );
		}
	}

	return new WP_Query( $args );
}

/**
 * Whether a canonical (Vietnamese source) listing post has a usable translation
 * in the given language. Used to hide untranslated posts on EN/ZH listings.
 *
 * A translation is considered available when either:
 *   1. the post type's hard-coded seed map has a non-empty title for the post's
 *      source slug in $lang, or
 *   2. a linked Polylang translation copy exists with a non-empty title.
 */
function qp_holdings_listing_post_has_translation( int $post_id, string $post_type, string $lang ): bool {
	if ( 'vi' === $lang || $post_id <= 0 ) {
		return true;
	}

	// Visual media / gallery items (photos & videos) fallback to the canonical item
	// so newly published videos in Vietnamese automatically appear on EN/ZH pages without requiring duplicate posts.
	if ( 'qp_gallery_item' === $post_type ) {
		return true;
	}

	// Projects have full dynamic dictionary + pattern translations in i18n layer
	// so all published projects appear seamlessly across all languages (VI, EN, ZH).
	if ( 'qp_project' === $post_type ) {
		return true;
	}

	$post_obj = get_post( $post_id );
	if ( ! $post_obj instanceof WP_Post ) {
		return true;
	}

	$source_slug = qp_holdings_translation_source_slug( $post_obj );

	// 1) Hard-coded seed translation map for this post type.
	$map = array();
	switch ( $post_type ) {
		case 'qp_news':
			$map = function_exists( 'qp_holdings_seeded_news_translations' ) ? qp_holdings_seeded_news_translations() : array();
			break;
		case 'qp_job':
			$map = function_exists( 'qp_holdings_seeded_job_translations' ) ? qp_holdings_seeded_job_translations() : array();
			break;
	}
	if ( isset( $map[ $source_slug ][ $lang ]['title'] ) && '' !== trim( (string) $map[ $source_slug ][ $lang ]['title'] ) ) {
		return true;
	}

	// 2) Linked Polylang translation copy with a real title.
	if ( function_exists( 'pll_get_post' ) ) {
		$translated_id = (int) pll_get_post( $post_id, $lang );
		if ( $translated_id && $translated_id !== $post_id ) {
			$translated = get_post( $translated_id );
			if ( $translated instanceof WP_Post && '' !== trim( (string) $translated->post_title ) ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Point links for translatable CPTs at the active-language translation.
 *
 * Listings render the canonical Vietnamese post set, so without this filter a
 * card on an English/Chinese page would link to the Vietnamese single post.
 * When a translation copy exists we return its permalink instead so the single
 * page keeps the reader in the same language.
 */
function qp_holdings_translated_post_permalink( string $permalink, $post ): string {
	static $guard = false;
	if ( $guard ) {
		return $permalink;
	}

	// The language switcher resolves each language's own permalink and sets
	// this flag so this redirect filter doesn't rewrite those URLs back to the
	// current page's language.
	if ( ! empty( $GLOBALS['qp_holdings_skip_translated_permalink'] ) ) {
		return $permalink;
	}

	$post_obj = $post instanceof WP_Post ? $post : get_post( $post );
	if ( ! $post_obj instanceof WP_Post || ! in_array( $post_obj->post_type, array( 'qp_news', 'qp_project', 'qp_job', 'qp_investor_doc' ), true ) ) {
		return $permalink;
	}

	$lang = qp_holdings_current_language();
	if ( 'vi' === $lang ) {
		return $permalink;
	}

	if ( function_exists( 'pll_get_post' ) ) {
		$translated_id = (int) pll_get_post( (int) $post_obj->ID, $lang );
		if ( $translated_id && $translated_id !== (int) $post_obj->ID ) {
			$guard          = true;
			$translated_url = get_permalink( $translated_id );
			$guard          = false;
			if ( $translated_url ) {
				return $translated_url;
			}
		}
	}

	/*
	 * No separate Polylang translation copy exists for this post. Listings render
	 * the canonical Vietnamese post set with frontend i18n layer translations.
	 * Generate a clean prefixed CPT permalink (e.g. /en/du-an-cpt/<slug>/ or /zh/du-an-cpt/<slug>/)
	 * so opening it keeps the reader in the active language without query-param stripping.
	 */
	$post_type_obj = get_post_type_object( $post_obj->post_type );
	$slug_prefix   = '';
	if ( $post_type_obj && ! empty( $post_type_obj->rewrite['slug'] ) ) {
		$slug_prefix = trim( (string) $post_type_obj->rewrite['slug'], '/' );
	}
	if ( ! $slug_prefix ) {
		$cpt_slugs = array(
			'qp_project'      => 'du-an-cpt',
			'qp_news'         => 'tin-tuc',
			'qp_job'          => 'tuyen-dung-cpt',
			'qp_investor_doc' => 'tai-lieu-nha-dau-tu',
		);
		$slug_prefix = $cpt_slugs[ $post_obj->post_type ] ?? '';
	}

	$post_name = (string) $post_obj->post_name;
	if ( ! $post_name && ! empty( $post_obj->ID ) ) {
		$post_name = (string) get_post_field( 'post_name', $post_obj->ID );
	}

	if ( $slug_prefix && $post_name ) {
		return home_url( '/' . $lang . '/' . $slug_prefix . '/' . $post_name . '/' );
	}

	return add_query_arg( 'qp_lang', $lang, $permalink );
}
add_filter( 'post_type_link', 'qp_holdings_translated_post_permalink', 20, 2 );
add_filter( 'post_link', 'qp_holdings_translated_post_permalink', 20, 2 );

/**
 * Automatically format permalinks for pages assigned as English or Chinese targets
 * with /en/ or /zh/ prefix (e.g. /en/trang-custom-en/, /zh/trang-custom-zh/).
 */
function qp_holdings_translated_page_permalink( string $permalink, $post_id ): string {
	static $guard = false;
	if ( $guard ) {
		return $permalink;
	}

	$id = is_numeric( $post_id ) ? (int) $post_id : ( $post_id instanceof WP_Post ? (int) $post_id->ID : 0 );
	if ( ! $id ) {
		return $permalink;
	}

	$target_lang = '';

	// 1. Check dynamic Header menu slot translation group
	if ( function_exists( 'qp_holdings_header_menu_slot_group_map' ) ) {
		$slot_group = qp_holdings_header_menu_slot_group_map();
		if ( ! empty( $slot_group['en'] ) && (int) $slot_group['en'] === $id ) {
			$target_lang = 'en';
		} elseif ( ! empty( $slot_group['zh'] ) && (int) $slot_group['zh'] === $id ) {
			$target_lang = 'zh';
		}
	}

	// 2. Check meta _qp_translation_lang
	if ( ! $target_lang ) {
		$meta_lang = get_post_meta( $id, '_qp_translation_lang', true );
		if ( in_array( $meta_lang, array( 'en', 'zh' ), true ) ) {
			$target_lang = (string) $meta_lang;
		}
	}

	// 3. Check Polylang if active
	if ( ! $target_lang && function_exists( 'pll_get_post_language' ) ) {
		$pll_lang = pll_get_post_language( $id, 'slug' );
		if ( in_array( $pll_lang, array( 'en', 'zh' ), true ) ) {
			$target_lang = (string) $pll_lang;
		}
	}

	if ( $target_lang ) {
		$slug = (string) get_post_field( 'post_name', $id );
		if ( $slug ) {
			return home_url( '/' . $target_lang . '/' . $slug . '/' );
		}
	}

	return $permalink;
}
add_filter( 'page_link', 'qp_holdings_translated_page_permalink', 20, 2 );

function qp_holdings_polylang_post_types( array $post_types, bool $is_settings ): array {
	foreach ( array( 'qp_news', 'qp_project', 'qp_job', 'qp_investor_doc', 'qp_gallery_item' ) as $post_type ) {
		$post_types[ $post_type ] = $post_type;
	}

	return $post_types;
}
add_filter( 'pll_get_post_types', 'qp_holdings_polylang_post_types', 10, 2 );

function qp_holdings_polylang_taxonomies( array $taxonomies, bool $is_settings ): array {
	foreach ( array( 'qp_news_category', 'qp_project_type', 'qp_project_location', 'qp_project_status', 'qp_job_department', 'qp_job_location', 'qp_job_level', 'qp_investor_section', 'qp_doc_year', 'qp_doc_type' ) as $taxonomy ) {
		$taxonomies[ $taxonomy ] = $taxonomy;
	}

	return $taxonomies;
}
add_filter( 'pll_get_taxonomies', 'qp_holdings_polylang_taxonomies', 10, 2 );

/**
 * Force Vietnamese as the default landing language for every visitor.
 *
 * The site content is Vietnamese first. A visitor opening the bare site URL
 * (no /en/ or /zh/ prefix) must always see Vietnamese, regardless of their
 * browser's Accept-Language header, their IP/country, or a previously stored
 * Polylang language cookie. Visitors can still switch to EN/ZH explicitly via
 * the language switcher (which uses prefixed URLs); this filter only governs
 * what Polylang auto-detects as the "preferred" language when none is given in
 * the URL. Hard-coding it here keeps the behaviour stable even if the Polylang
 * "detect browser language" option is toggled back on in wp-admin.
 */
function qp_holdings_force_default_language_vi( $preferred ) {
	return 'vi';
}
add_filter( 'pll_preferred_language', 'qp_holdings_force_default_language_vi', 99 );

function qp_holdings_disable_canonical_for_prefixed_languages( $redirect_url, $requested_url ) {
	return false;
}
add_filter( 'redirect_canonical', 'qp_holdings_disable_canonical_for_prefixed_languages', 10, 2 );

function qp_holdings_disable_polylang_canonical_redirect(): void {
	if ( ! function_exists( 'PLL' ) ) {
		return;
	}

	$pll = PLL();
	if ( is_object( $pll ) && isset( $pll->canonical ) ) {
		remove_action( 'template_redirect', array( $pll->canonical, 'check_canonical_url' ), 4 );
	}
}
add_action( 'wp', 'qp_holdings_disable_polylang_canonical_redirect', 1 );

function qp_holdings_document_title_parts( array $parts ): array {
	$lang = qp_holdings_current_language();
	if ( 'vi' === $lang ) {
		return $parts;
	}

	$defs = qp_holdings_page_definitions();
	$key  = qp_holdings_current_page_key();
	if ( isset( $defs[ $key ][ $lang ]['title'] ) ) {
		$parts['title'] = $defs[ $key ][ $lang ]['title'];
	}

	return $parts;
}
add_filter( 'document_title_parts', 'qp_holdings_document_title_parts' );


/* =========================================================================
 * Seeded qp_job translation layer — mirrors qp_news pattern.
 * ======================================================================= */

/**
 * Returns translation map for all 10 seeded qp_job posts.
 * Each entry is keyed by post slug and contains 'en' / 'zh' sub-arrays with:
 *   title, excerpt, content (HTML), dept, loc, level, experience, salary.
 */
function qp_holdings_seeded_job_translations(): array {
	return array(

		/* 1 - Investment Analysis Manager */
		'truong-phong-phan-tich-dau-tu' => array(
			'en' => array(
				'title'      => 'Investment Analysis Manager',
				'excerpt'    => 'Investment Analysis Manager role — Investment Division in Ho Chi Minh City.',
				'dept'       => 'Investment Division',
				'loc'        => 'Ho Chi Minh City',
				'level'      => 'Senior',
				'experience' => '5+ years equivalent experience',
				'salary'     => 'VND 30 \xe2\x80\x93 50M/month + project bonus',
				'content'    =>
					'<h2>Key responsibilities</h2><ul>'
					. '<li>Build and manage the group&#39;s investment opportunity pipeline</li>'
					. '<li>Develop financial models, valuation analyses, and investment reports</li>'
					. '<li>Lead and mentor a team of junior investment analysts</li>'
					. '<li>Present investment recommendations to the Executive Board</li>'
					. '</ul>'
					. '<h2>Requirements</h2><ul>'
					. '<li>Bachelor&#39;s degree in Finance, Economics, or MBA preferred</li>'
					. '<li>CFA Level 2/3; experience at an investment fund or investment bank</li>'
					. '<li>Proficient in DCF, comparable company, and transaction-multiples valuation</li>'
					. '<li>Strong team management and leadership skills</li>'
					. '</ul>'
					. '<h2>Benefits</h2><ul>'
					. '<li>Competitive remuneration: 14&#39;\xe2\x80\x9317 months&#39; salary per year</li>'
					. '<li>ESOP equity package</li>'
					. '<li>Premium healthcare coverage</li>'
					. '<li>CFA / MBA training budget sponsored by the company</li>'
					. '</ul>',
			),
			'zh' => array(
				'title'      => '\xe6\x8a\x95\xe8\xb5\x84\xe5\x88\x86\xe6\x9e\x90\xe9\x83\xa8\xe7\xbb\x8f\xe7\x90\x86',
				'excerpt'    => '\xe6\x8a\x95\xe8\xb5\x84\xe5\x88\x86\xe6\x9e\x90\xe9\x83\xa8\xe7\xbb\x8f\xe7\x90\x86\xe8\x81\x8c\xe4\xbd\x8d\xe2\x80\x94\xe2\x80\x94\xe8\x83\xa1\xe5\xbf\x97\xe6\x98\x8e\xe5\xb8\x82\xe6\x8a\x95\xe8\xb5\x84\xe9\x83\xa8\xe9\x97\xa8\xe3\x80\x82',
				'dept'       => '\xe6\x8a\x95\xe8\xb5\x84\xe9\x83\xa8\xe9\x97\xa8',
				'loc'        => '\xe8\x83\xa1\xe5\xbf\x97\xe6\x98\x8e\xe5\xb8\x82',
				'level'      => '\xe9\xab\x98\xe7\xba\xa7',
				'experience' => '5\xe5\xb9\xb4\xe4\xbb\xa5\xe4\xb8\x8a\xe5\x90\x8c\xe7\xad\x89\xe5\xb2\x97\xe4\xbd\x8d\xe7\xbb\x8f\xe9\xaa\x8c',
				'salary'     => '3000\xe2\x80\x935000\xe4\xb8\x87\xe8\xb6\x8a\xe7\x9b\xbe/\xe6\x9c\x88 + \xe9\xa1\xb9\xe7\x9b\xae\xe5\xa5\x96\xe9\x87\x91',
				'content'    =>
					'<h2>\xe4\xb8\xbb\xe8\xa6\x81\xe8\x81\x8c\xe8\xb4\xa3</h2><ul>'
					. '<li>\xe5\xbb\xba\xe7\xab\x8b\xe5\xb9\xb6\xe7\xae\xa1\xe7\x90\x86\xe9\x9b\x86\xe5\x9b\xa2\xe6\x8a\x95\xe8\xb5\x84\xe6\x9c\xba\xe4\xbc\x9a\xe9\xa1\xb9\xe7\x9b\xae\xe5\xba\x93</li>'
					. '<li>\xe6\x9e\x84\xe5\xbb\xba\xe8\xb4\xa2\xe5\x8a\xa1\xe6\xa8\xa1\xe5\x9e\x8b\xe3\x80\x81\xe5\xbc\x80\xe5\xb1\x95\xe4\xbc\xb0\xe5\x80\xbc\xe5\x88\x86\xe6\x9e\x90\xe5\xb9\xb6\xe6\x92\xb0\xe5\x86\x99\xe6\x8a\x95\xe8\xb5\x84\xe6\x8a\xa5\xe5\x91\x8a</li>'
					. '<li>\xe9\xa2\x86\xe5\xaf\xbc\xe5\xb9\xb6\xe8\xbe\x85\xe5\xaf\xbc\xe5\xb9\xb4\xe8\xbd\xbb\xe6\x8a\x95\xe8\xb5\x84\xe5\x88\x86\xe6\x9e\x90\xe5\xb8\x88\xe5\x9b\xa2\xe9\x98\x9f</li>'
					. '<li>\xe5\x90\x91\xe6\x89\xa7\xe8\xa1\x8c\xe5\xa7\x94\xe5\x91\x98\xe4\xbc\x9a\xe6\xb1\x87\xe6\x8a\xa5\xe6\x8a\x95\xe8\xb5\x84\xe5\xbb\xba\xe8\xae\xae</li>'
					. '</ul>'
					. '<h2>\xe4\xbb\xbb\xe8\x81\x8c\xe8\xa6\x81\xe6\xb1\x82</h2><ul>'
					. '<li>\xe6\x9c\xac\xe7\xa7\x91\xe5\x8f\x8a\xe4\xbb\xa5\xe4\xb8\x8a\xe5\xad\xa6\xe5\x8e\x86\xef\xbc\x8c\xe9\x87\x91\xe8\x9e\x8d\xe3\x80\x81\xe7\xbb\x8f\xe6\xb5\x8e\xe5\xad\xa6\xe4\xb8\x93\xe4\xb8\x9a\xef\xbc\x8c\xe4\xbc\x98\xe5\x85\x88\xe8\x80\x83\xe8\x99\x91 MBA</li>'
					. '<li>CFA \xe4\xba\x8c\xe7\xba\xa7/\xe4\xb8\x89\xe7\xba\xa7\xef\xbc\x9b\xe5\x85\xb7\xe5\xa4\x87\xe6\x8a\x95\xe8\xb5\x84\xe5\x9f\xba\xe9\x87\x91\xe6\x88\x96\xe6\x8a\x95\xe8\xb5\x84\xe9\x93\x80\xe8\xa1\x8c\xe5\xb7\xa5\xe4\xbd\x9c\xe7\xbb\x8f\xe9\xaa\x8c</li>'
					. '<li>\xe7\x86\x9f\xe7\xbb\x83\xe6\x8e\x8c\xe6\x8f\xa1 DCF\xe3\x80\x81\xe5\x8f\xaf\xe6\xaf\x94\xe5\x85\xac\xe5\x8f\xb8\xe5\x88\x86\xe6\x9e\x90\xe5\x8f\x8a\xe4\xba\xa4\xe6\x98\x93\xe4\xb9\x98\xe6\x95\xb0\xe4\xbc\xb0\xe5\x80\xbc\xe6\x96\xb9\xe6\xb3\x95</li>'
					. '<li>\xe5\x85\xb7\xe5\xa4\x87\xe8\xbe\x83\xe5\xbc\xba\xe7\x9a\x84\xe5\x9b\xa2\xe9\x98\x9f\xe7\xae\xa1\xe7\x90\x86\xe4\xb8\x8e\xe9\xa2\x86\xe5\xaf\xbc\xe8\x83\xbd\xe5\x8a\x9b</li>'
					. '</ul>'
					. '<h2>\xe7\xa6\x8f\xe5\x88\xa9\xe5\xbe\x85\xe9\x81\x87</h2><ul>'
					. '<li>\xe6\x9e\x81\xe5\x85\xb7\xe7\xab\x9e\xe4\xba\x89\xe5\x8a\x9b\xe7\x9a\x84\xe8\x96\xaa\xe9\x85\xac\xe4\xbd\x93\xe7\xb3\xbb\xef\xbc\x8c\xe5\xb9\xb4\xe8\x96\xaa\xe7\x9b\xb8\xe5\xbd\x93\xe4\xba\x8e14\xe2\x80\x9317\xe4\xb8\xaa\xe6\x9c\x88\xe5\xb7\xa5\xe8\xb5\x84</li>'
					. '<li>ESOP \xe5\x91\x98\xe5\xb7\xa5\xe6\x8c\x81\xe8\x82\xa1\xe8\xae\xa1\xe5\x88\x92</li>'
					. '<li>\xe9\xab\x98\xe7\xab\xaf\xe5\x8c\xbb\xe7\x96\x97\xe4\xbf\x9d\xe9\x9a\x9c</li>'
					. '<li>\xe5\x85\xac\xe5\x8f\xb8\xe8\xb5\x84\xe5\x8a\xa9 CFA / MBA \xe5\x9f\xb9\xe8\xae\xad\xe7\xbb\x8f\xe8\xb4\xb9</li>'
					. '</ul>',
			),
		),

		/* 2 - IR Specialist */
		'chuyen-vien-ir' => array(
			'en' => array(
				'title'      => 'Investor Relations Specialist (IR)',
				'excerpt'    => 'Investor Relations Specialist role — Investment Division in Ho Chi Minh City.',
				'dept'       => 'Investment Division',
				'loc'        => 'Ho Chi Minh City',
				'level'      => 'Mid-level',
				'experience' => '2\xe2\x80\x934 years in IR / Equity Research',
				'salary'     => 'Negotiable',
				'content'    =>
					'<h2>Key responsibilities</h2><ul>'
					. '<li>Manage periodic and ad-hoc disclosures in accordance with regulations</li>'
					. '<li>Produce investor presentations, reports, and newsletters</li>'
					. '<li>Organise non-deal roadshows, AGMs, and analyst meetings</li>'
					. '<li>Track share performance, peer benchmarking, and market sentiment</li>'
					. '</ul>'
					. '<h2>Requirements</h2><ul>'
					. '<li>Bachelor&#39;s degree in Finance, Economics, or Journalism</li>'
					. '<li>Strong English skills (TOEIC 800+ or IELTS 7.0)</li>'
					. '<li>Proficient in PowerPoint, Excel; Bloomberg/Refinitiv experience preferred</li>'
					. '<li>Excellent writing and public-speaking skills</li>'
					. '</ul>'
					. '<h2>Benefits</h2><ul>'
					. '<li>Competitive salary and bonus package</li>'
					. '<li>Direct exposure to Executive Board and institutional investors</li>'
					. '<li>Internal training and CFA sponsorship</li>'
					. '</ul>',
			),
			'zh' => array(
				'title'      => '\xe6\x8a\x95\xe8\xb5\x84\xe8\x80\x85\xe5\x85\xb3\xe7\xb3\xbb\xe4\xb8\x93\xe5\x91\x98 (IR)',
				'excerpt'    => '\xe6\x8a\x95\xe8\xb5\x84\xe8\x80\x85\xe5\x85\xb3\xe7\xb3\xbb\xe4\xb8\x93\xe5\x91\x98\xe8\x81\x8c\xe4\xbd\x8d\xe2\x80\x94\xe2\x80\x94\xe8\x83\xa1\xe5\xbf\x97\xe6\x98\x8e\xe5\xb8\x82\xe6\x8a\x95\xe8\xb5\x84\xe9\x83\xa8\xe9\x97\xa8\xe3\x80\x82',
				'dept'       => '\xe6\x8a\x95\xe8\xb5\x84\xe9\x83\xa8\xe9\x97\xa8',
				'loc'        => '\xe8\x83\xa1\xe5\xbf\x97\xe6\x98\x8e\xe5\xb8\x82',
				'level'      => '\xe4\xb8\xad\xe7\xba\xa7',
				'experience' => '2\xe2\x80\x934\xe5\xb9\xb4 IR / \xe8\x82\xa1\xe7\xa5\xa8\xe7\xa0\x94\xe7\xa9\xb6\xe7\xbb\x8f\xe9\xaa\x8c',
				'salary'     => '\xe9\x9d\xa2\xe8\xae\xae',
				'content'    =>
					'<h2>\xe4\xb8\xbb\xe8\xa6\x81\xe8\x81\x8c\xe8\xb4\xa3</h2><ul>'
					. '<li>\xe6\x8c\x89\xe8\xa7\x84\xe5\xae\x9a\xe7\xae\xa1\xe7\x90\x86\xe5\xae\x9a\xe6\x9c\x9f\xe5\x8f\x8a\xe4\xb8\x8d\xe5\xae\x9a\xe6\x9c\x9f\xe4\xbf\xa1\xe6\x81\xaf\xe6\x8a\xab\xe9\x9c\xb2</li>'
					. '<li>\xe5\x88\xb6\xe4\xbd\x9c\xe6\x8a\x95\xe8\xb5\x84\xe8\x80\x85\xe6\xbc\x94\xe7\xa4\xba\xe6\x9d\x90\xe6\x96\x99\xe3\x80\x81\xe6\x8a\xa5\xe5\x91\x8a\xe5\x8f\x8a\xe7\xae\x80\xe6\x8a\xa5</li>'
					. '<li>\xe7\xbb\x84\xe7\xbb\x87\xe9\x9d\x9e\xe4\xba\xa4\xe6\x98\x93\xe8\xb7\xaf\xe6\xbc\x94\xe3\x80\x81\xe8\x82\xa1\xe4\xb8\x9c\xe5\xa4\xa7\xe4\xbc\x9a\xe5\x8f\x8a\xe5\x88\x86\xe6\x9e\x90\xe5\xb8\x88\xe8\xa7\x81\xe9\x9d\xa2\xe4\xbc\x9a</li>'
					. '<li>\xe8\xb7\x9f\xe8\xb8\xaa\xe8\x82\xa1\xe4\xbb\xb7\xe8\xa1\xa8\xe7\x8e\xb0\xe3\x80\x81\xe5\x90\x8c\xe4\xb8\x9a\xe5\x9f\xba\xe5\x87\x86\xe5\xaf\xb9\xe6\xaf\x94\xe5\x8f\x8a\xe5\xb8\x82\xe5\x9c\xba\xe6\x83\x85\xe7\xb5\xae\xe5\x8f\x98\xe5\x8c\x96</li>'
					. '</ul>'
					. '<h2>\xe4\xbb\xbb\xe8\x81\x8c\xe8\xa6\x81\xe6\xb1\x82</h2><ul>'
					. '<li>\xe6\x9c\xac\xe7\xa7\x91\xe5\x8f\x8a\xe4\xbb\xa5\xe4\xb8\x8a\xe5\xad\xa6\xe5\x8e\x86\xef\xbc\x8c\xe9\x87\x91\xe8\x9e\x8d\xe3\x80\x81\xe7\xbb\x8f\xe6\xb5\x8e\xe6\x88\x96\xe6\x96\xb0\xe9\x97\xbb\xe4\xbc\xa0\xe6\x92\xad\xe4\xb8\x93\xe4\xb8\x9a</li>'
					. '<li>\xe8\x8b\xb1\xe8\xaf\xad\xe8\x83\xbd\xe5\x8a\x9b\xe8\xbe\x83\xe5\xbc\xba\xef\xbc\x88TOEIC 800+\xe6\x88\x96 IELTS 7.0\xef\xbc\x89</li>'
					. '<li>\xe7\x86\x9f\xe7\xbb\x83\xe6\x8e\x8c\xe6\x8f\xa1 PowerPoint\xe3\x80\x81Excel\xef\xbc\x9b\xe6\x9c\x89 Bloomberg/Refinitiv \xe4\xbd\xbf\xe7\x94\xa8\xe7\xbb\x8f\xe9\xaa\x8c\xe8\x80\x85\xe4\xbc\x98\xe5\x85\x88</li>'
					. '<li>\xe5\x86\x99\xe4\xbd\x9c\xe8\x83\xbd\xe5\x8a\x9b\xe7\xaa\x81\xe5\x87\xba\xef\xbc\x8c\xe5\x85\xb7\xe5\xa4\x87\xe8\x89\xaf\xe5\xa5\xbd\xe7\x9a\x84\xe5\x85\xac\xe5\xbc\x80\xe6\xbc\x94\xe8\xae\xb2\xe6\x8a\x80\xe8\x83\xbd</li>'
					. '</ul>'
					. '<h2>\xe7\xa6\x8f\xe5\x88\xa9\xe5\xbe\x85\xe9\x81\x87</h2><ul>'
					. '<li>\xe5\x85\xb7\xe6\x9c\x89\xe7\xab\x9e\xe4\xba\x89\xe5\x8a\x9b\xe7\x9a\x84\xe8\x96\xaa\xe9\x85\xac\xe4\xb8\x8e\xe5\xa5\x96\xe9\x87\x91\xe6\x96\xb9\xe6\xa1\x88</li>'
					. '<li>\xe6\x9c\x89\xe6\x9c\xba\xe4\xbc\x9a\xe7\x9b\xb4\xe6\x8e\xa5\xe6\x8e\xa5\xe8\xa7\xa6\xe9\xab\x98\xe7\xae\xa1\xe5\xb1\x82\xe5\x8f\x8a\xe6\x9c\xba\xe6\x9e\x84\xe6\x8a\x95\xe8\xb5\x84\xe8\x80\x85</li>'
					. '<li>\xe5\x86\x85\xe9\x83\xa8\xe5\x9f\xb9\xe8\xae\xad\xe5\x8f\x8a CFA \xe8\x80\x83\xe8\xaf\x95\xe8\xb5\x84\xe5\x8a\xa9</li>'
					. '</ul>',
			),
		),

		/* 3 - Senior Project Manager (Real Estate) */
		'senior-project-manager-bds' => array(
			'en' => array(
				'title'      => 'Senior Project Manager (Real Estate)',
				'excerpt'    => 'Senior Project Manager role — Project Division in Binh Duong.',
				'dept'       => 'Project Division',
				'loc'        => 'Binh Duong',
				'level'      => 'Senior',
				'experience' => '7+ years managing large-scale real estate projects',
				'salary'     => 'VND 40 \xe2\x80\x93 60M/month',
				'content'    =>
					'<h2>Key responsibilities</h2><ul>'
					. '<li>Oversee overall schedule, cost, quality, and safety of the project</li>'
					. '<li>Coordinate design, construction, and supervision contractors</li>'
					. '<li>Prepare periodic progress reports for the Board of Directors</li>'
					. '<li>Review and approve design changes and cost variations</li>'
					. '</ul>'
					. '<h2>Requirements</h2><ul>'
					. '<li>Bachelor&#39;s degree in Civil Engineering, Architecture, or equivalent</li>'
					. '<li>PMP or equivalent certification preferred</li>'
					. '<li>Experience on complex mixed-use projects of 50,000 sqm GFA or more</li>'
					. '<li>Proficient in MS Project and Primavera P6</li>'
					. '</ul>'
					. '<h2>Benefits</h2><ul>'
					. '<li>Project-based compensation package</li>'
					. '<li>Shuttle bus service on the HCMC \xe2\x80\x93 Binh Duong route</li>'
					. '<li>ESOP equity package for project management level</li>'
					. '</ul>',
			),
			'zh' => array(
				'title'      => '\xe9\xab\x98\xe7\xba\xa7\xe9\xa1\xb9\xe7\x9b\xae\xe7\xbb\x8f\xe7\x90\x86\xef\xbc\x88\xe6\x88\xbf\xe5\x9c\xb0\xe4\xba\xa7\xef\xbc\x89',
				'excerpt'    => '\xe9\xab\x98\xe7\xba\xa7\xe9\xa1\xb9\xe7\x9b\xae\xe7\xbb\x8f\xe7\x90\x86\xe8\x81\x8c\xe4\xbd\x8d\xe2\x80\x94\xe2\x80\x94\xe5\xb9\xb3\xe9\x98\xb3\xe7\x9c\x81\xe9\xa1\xb9\xe7\x9b\xae\xe9\x83\xa8\xe9\x97\xa8\xe3\x80\x82',
				'dept'       => '\xe9\xa1\xb9\xe7\x9b\xae\xe9\x83\xa8\xe9\x97\xa8',
				'loc'        => '\xe5\xb9\xb3\xe9\x98\xb3',
				'level'      => '\xe9\xab\x98\xe7\xba\xa7',
				'experience' => '7\xe5\xb9\xb4\xe4\xbb\xa5\xe4\xb8\x8a\xe5\xa4\xa7\xe5\x9e\x8b\xe6\x88\xbf\xe5\x9c\xb0\xe4\xba\xa7\xe9\xa1\xb9\xe7\x9b\xae\xe7\xae\xa1\xe7\x90\x86\xe7\xbb\x8f\xe9\xaa\x8c',
				'salary'     => '4000\xe2\x80\x936000\xe4\xb8\x87\xe8\xb6\x8a\xe7\x9b\xbe/\xe6\x9c\x88',
				'content'    =>
					'<h2>\xe4\xb8\xbb\xe8\xa6\x81\xe8\x81\x8c\xe8\xb4\xa3</h2><ul>'
					. '<li>\xe7\xbb\x9f\xe7\xad\xb9\xe7\xae\xa1\xe7\x90\x86\xe9\xa1\xb9\xe7\x9b\xae\xe6\x95\xb4\xe4\xbd\x93\xe8\xbf\x9b\xe5\xba\xa6\xe3\x80\x81\xe6\x88\x90\xe6\x9c\xac\xe3\x80\x81\xe8\xb4\xa8\xe9\x87\x8f\xe4\xb8\x8e\xe5\xae\x89\xe5\x85\xa8</li>'
					. '<li>\xe5\x8d\x8f\xe8\xb0\x83\xe8\xae\xbe\xe8\xae\xa1\xe3\x80\x81\xe6\x96\xbd\xe5\xb7\xa5\xe5\x8f\x8a\xe7\x9b\x91\xe7\x90\x86\xe6\x89\xbf\xe5\x8c\x85\xe5\x95\x86</li>'
					. '<li>\xe5\xae\x9a\xe6\x9c\x9f\xe5\x90\x91\xe8\x91\xa3\xe4\xba\x8b\xe4\xbc\x9a\xe6\xb1\x87\xe6\x8a\xa5\xe9\xa1\xb9\xe7\x9b\xae\xe8\xbf\x9b\xe5\xb1\x95</li>'
					. '<li>\xe5\xae\xa1\xe6\xa0\xb8\xe5\xb9\xb6\xe6\x89\xb9\xe5\x87\x86\xe8\xae\xbe\xe8\xae\xa1\xe5\x8f\x98\xe6\x9b\xb4\xe4\xb8\x8e\xe8\xb4\xb9\xe7\x94\xa8\xe8\xb0\x83\xe6\x95\xb4</li>'
					. '</ul>'
					. '<h2>\xe4\xbb\xbb\xe8\x81\x8c\xe8\xa6\x81\xe6\xb1\x82</h2><ul>'
					. '<li>\xe5\x9c\x9f\xe6\x9c\xa8\xe5\xb7\xa5\xe7\xa8\x8b\xe3\x80\x81\xe5\xbb\xba\xe7\xad\x91\xe5\xad\xa6\xe6\x88\x96\xe7\x9b\xb8\xe5\x85\xb3\xe4\xb8\x93\xe4\xb8\x9a\xe6\x9c\xac\xe7\xa7\x91\xe5\x8f\x8a\xe4\xbb\xa5\xe4\xb8\x8a\xe5\xad\xa6\xe5\x8e\x86</li>'
					. '<li>\xe4\xbc\x98\xe5\x85\x88\xe8\x80\x83\xe8\x99\x91\xe6\x8c\x81\xe6\x9c\x89 PMP \xe6\x88\x96\xe5\x90\x8c\xe7\xad\x89\xe8\xaf\x81\xe4\xb9\xa6\xe8\x80\x85</li>'
					. '<li>\xe5\x85\xb7\xe5\xa4\x87\xe5\xbb\xba\xe7\xad\x91\xe9\x9d\xa2\xe7\xa7\xaf\xe4\xb8\x8d\xe4\xbd\x8e\xe4\xba\x8e5\xe4\xb8\x87\xe5\xb9\xb3\xe6\x96\xb9\xe7\xb1\xb3\xe7\xbb\xbc\xe5\x90\x88\xe4\xbd\x93\xe9\xa1\xb9\xe7\x9b\xae\xe7\xbb\x8f\xe9\xaa\x8c</li>'
					. '<li>\xe7\x86\x9f\xe7\xbb\x83\xe6\x8e\x8c\xe6\x8f\xa1 MS Project \xe5\x92\x8c Primavera P6</li>'
					. '</ul>'
					. '<h2>\xe7\xa6\x8f\xe5\x88\xa9\xe5\xbe\x85\xe9\x81\x87</h2><ul>'
					. '<li>\xe5\x9f\xba\xe4\xba\x8e\xe9\xa1\xb9\xe7\x9b\xae\xe7\xbb\xa9\xe6\x95\x88\xe7\x9a\x84\xe8\x96\xaa\xe9\x85\xac\xe6\x96\xb9\xe6\xa1\x88</li>'
					. '<li>\xe6\x8f\x90\xe4\xbe\x9b\xe8\x83\xa1\xe5\xbf\x97\xe6\x98\x8e\xe5\xb8\x82\xe2\x80\x94\xe5\xb9\xb3\xe9\x98\xb3\xe9\x80\x9a\xe5\x8b\xa4\xe7\x8f\xad\xe8\xbd\xa6</li>'
					. '<li>\xe9\xa1\xb9\xe7\x9b\xae\xe7\xae\xa1\xe7\x90\x86\xe7\xba\xa7\xe5\x88\xab\xe5\x91\x98\xe5\xb7\xa5\xe4\xba\xab\xe6\x9c\x89 ESOP \xe6\x8c\x81\xe8\x82\xa1\xe8\xae\xa1\xe5\x88\x92</li>'
					. '</ul>',
			),
		),

		/* 4 - Chief Accountant */
		'ke-toan-truong' => array(
			'en' => array(
				'title'      => 'Chief Accountant',
				'excerpt'    => 'Chief Accountant role — Finance Division in Ho Chi Minh City.',
				'dept'       => 'Finance Division',
				'loc'        => 'Ho Chi Minh City',
				'level'      => 'Senior',
				'experience' => '8+ years in a listed company environment',
				'salary'     => 'Negotiable',
				'content'    =>
					'<h2>Key responsibilities</h2><ul>'
					. '<li>Manage all accounting, tax, and audit activities</li>'
					. '<li>Sign stand-alone and consolidated financial statements</li>'
					. '<li>Work directly with Big4 firms and tax authorities</li>'
					. '<li>Advise the CFO on financial governance matters</li>'
					. '</ul>'
					. '<h2>Requirements</h2><ul>'
					. '<li>Bachelor&#39;s degree in Accounting or Auditing</li>'
					. '<li>Chief Accountant certification, Vietnam CPA, and ACCA preferred</li>'
					. '<li>Hands-on experience with IFRS, consolidation, and listed-company reporting</li>'
					. '<li>Experience leading teams of 10+ staff members</li>'
					. '</ul>'
					. '<h2>Benefits</h2><ul>'
					. '<li>Executive-level salary, year-end bonus, and ESOP</li>'
					. '<li>ACCA / CPA sponsorship for eligible team members</li>'
					. '</ul>',
			),
			'zh' => array(
				'title'      => '\xe9\xa6\x96\xe5\xb8\xad\xe4\xbc\x9a\xe8\xae\xa1\xe5\xb8\x88',
				'excerpt'    => '\xe9\xa6\x96\xe5\xb8\xad\xe4\xbc\x9a\xe8\xae\xa1\xe5\xb8\x88\xe8\x81\x8c\xe4\xbd\x8d\xe2\x80\x94\xe2\x80\x94\xe8\x83\xa1\xe5\xbf\x97\xe6\x98\x8e\xe5\xb8\x82\xe8\xb4\xa2\xe5\x8a\xa1\xe9\x83\xa8\xe9\x97\xa8\xe3\x80\x82',
				'dept'       => '\xe8\xb4\xa2\xe5\x8a\xa1\xe9\x83\xa8\xe9\x97\xa8',
				'loc'        => '\xe8\x83\xa1\xe5\xbf\x97\xe6\x98\x8e\xe5\xb8\x82',
				'level'      => '\xe9\xab\x98\xe7\xba\xa7',
				'experience' => '8\xe5\xb9\xb4\xe4\xbb\xa5\xe4\xb8\x8a\xe4\xb8\x8a\xe5\xb8\x82\xe5\x85\xac\xe5\x8f\xb8\xe5\xb7\xa5\xe4\xbd\x9c\xe7\xbb\x8f\xe9\xaa\x8c',
				'salary'     => '\xe9\x9d\xa2\xe8\xae\xae',
				'content'    =>
					'<h2>\xe4\xb8\xbb\xe8\xa6\x81\xe8\x81\x8c\xe8\xb4\xa3</h2><ul>'
					. '<li>\xe7\xbb\x9f\xe7\xad\xb9\xe7\xae\xa1\xe7\x90\x86\xe5\x85\xa8\xe9\x83\xa8\xe4\xbc\x9a\xe8\xae\xa1\xe3\x80\x81\xe7\xa8\x8e\xe5\x8a\xa1\xe4\xb8\x8e\xe5\xae\xa1\xe8\xae\xa1\xe5\xb7\xa5\xe4\xbd\x9c</li>'
					. '<li>\xe7\xad\xbe\xe7\xbd\xb2\xe5\x8d\x95\xe4\xbd\x93\xe5\x8f\x8a\xe5\x90\x88\xe5\xb9\xb6\xe8\xb4\xa2\xe5\x8a\xa1\xe6\x8a\xa5\xe8\xa1\xa8</li>'
					. '<li>\xe4\xb8\x8e\xe5\x9b\x9b\xe5\xa4\xa7\xe4\xbc\x9a\xe8\xae\xa1\xe5\xb8\x88\xe4\xba\x8b\xe5\x8a\xa1\xe6\x89\x80\xe5\x8f\x8a\xe7\xa8\x8e\xe5\x8a\xa1\xe6\x9c\xba\xe5\x85\xb3\xe7\x9b\xb4\xe6\x8e\xa5\xe5\xaf\xb9\xe6\x8e\xa5</li>'
					. '<li>\xe4\xb8\xba CFO \xe6\x8f\x90\xe4\xbe\x9b\xe8\xb4\xa2\xe5\x8a\xa1\xe6\xb2\xbb\xe7\x90\x86\xe7\x9b\xb8\xe5\x85\xb3\xe5\xbb\xba\xe8\xae\xae</li>'
					. '</ul>'
					. '<h2>\xe4\xbb\xbb\xe8\x81\x8c\xe8\xa6\x81\xe6\xb1\x82</h2><ul>'
					. '<li>\xe6\x9c\xac\xe7\xa7\x91\xe5\x8f\x8a\xe4\xbb\xa5\xe4\xb8\x8a\xe5\xad\xa6\xe5\x8e\x86\xef\xbc\x8c\xe4\xbc\x9a\xe8\xae\xa1\xe6\x88\x96\xe5\xae\xa1\xe8\xae\xa1\xe7\x9b\xb8\xe5\x85\xb3\xe4\xb8\x93\xe4\xb8\x9a</li>'
					. '<li>\xe4\xbc\x98\xe5\x85\x88\xe8\x80\x83\xe8\x99\x91\xe6\x8c\x81\xe6\x9c\x89\xe9\xa6\x96\xe5\xb8\xad\xe4\xbc\x9a\xe8\xae\xa1\xe5\xb8\x88\xe8\xb5\x84\xe6\xa0\xbc\xe3\x80\x81\xe8\xb6\x8a\xe5\x8d\x97 CPA \xe6\x88\x96 ACCA \xe8\xaf\x81\xe4\xb9\xa6\xe8\x80\x85</li>'
					. '<li>\xe5\x85\xb7\xe5\xa4\x87 IFRS\xe3\x80\x81\xe5\x90\x88\xe5\xb9\xb6\xe6\x8a\xa5\xe8\xa1\xa8\xe5\x8f\x8a\xe4\xb8\x8a\xe5\xb8\x82\xe5\x85\xac\xe5\x8f\xb8\xe6\x8a\xab\xe9\x9c\xb2\xe5\xae\x9e\xe6\x93\x8d\xe7\xbb\x8f\xe9\xaa\x8c</li>'
					. '<li>\xe5\x85\xb7\xe5\xa4\x8710\xe4\xba\xba\xe4\xbb\xa5\xe4\xb8\x8a\xe5\x9b\xa2\xe9\x98\x9f\xe7\xae\xa1\xe7\x90\x86\xe8\x83\xbd\xe5\x8a\x9b</li>'
					. '</ul>'
					. '<h2>\xe7\xa6\x8f\xe5\x88\xa9\xe5\xbe\x85\xe9\x81\x87</h2><ul>'
					. '<li>\xe9\xab\x98\xe9\x98\xb6\xe8\x96\xaa\xe9\x85\xac\xe3\x80\x81\xe5\xb9\xb4\xe7\xbb\x88\xe5\xa5\x96\xe9\x87\x91\xe5\x8f\x8a\xe5\x91\x98\xe5\xb7\xa5\xe6\x8c\x81\xe8\x82\xa1\xe6\xbf\x80\xe5\x8a\xb1</li>'
					. '<li>\xe4\xb8\xba\xe7\xac\xa6\xe5\x90\x88\xe6\x9d\xa1\xe4\xbb\xb6\xe7\x9a\x84\xe5\x9b\xa2\xe9\x98\x9f\xe6\x88\x90\xe5\x91\x98\xe6\x8f\x90\xe4\xbe\x9b ACCA / CPA \xe8\xb5\x84\xe5\x8a\xa9</li>'
					. '</ul>',
			),
		),

		/* 5 - Corporate Legal Specialist */
		'chuyen-vien-phap-che-doanh-nghiep' => array(
			'en' => array(
				'title'      => 'Corporate Legal Specialist',
				'excerpt'    => 'Corporate Legal Specialist role — Legal Division in Ho Chi Minh City.',
				'dept'       => 'Legal Division',
				'loc'        => 'Ho Chi Minh City',
				'level'      => 'Mid-level',
				'experience' => '3+ years at a corporate group or law firm',
				'salary'     => 'VND 20 \xe2\x80\x93 30M/month',
				'content'    =>
					'<h2>Key responsibilities</h2><ul>'
					. '<li>Draft, review, and finalise contracts and legal documents</li>'
					. '<li>Advise on corporate law, M&amp;A, and labour matters</li>'
					. '<li>Represent the company in dealings with government authorities</li>'
					. '<li>Maintain and manage corporate legal records and filing systems</li>'
					. '</ul>'
					. '<h2>Requirements</h2><ul>'
					. '<li>Bachelor&#39;s degree in Law</li>'
					. '<li>Lawyer practice certificate preferred</li>'
					. '<li>Experience in real estate, investment, or M&amp;A is a plus</li>'
					. '<li>Proficient in legal English</li>'
					. '</ul>'
					. '<h2>Benefits</h2><ul>'
					. '<li>Competitive salary</li>'
					. '<li>Exposure to high-value investment and M&amp;A transactions</li>'
					. '<li>Lawyer certification sponsorship</li>'
					. '</ul>',
			),
			'zh' => array(
				'title'      => '\xe4\xbc\x81\xe4\xb8\x9a\xe6\xb3\x95\xe5\x8a\xa1\xe4\xb8\x93\xe5\x91\x98',
				'excerpt'    => '\xe4\xbc\x81\xe4\xb8\x9a\xe6\xb3\x95\xe5\x8a\xa1\xe4\xb8\x93\xe5\x91\x98\xe8\x81\x8c\xe4\xbd\x8d\xe2\x80\x94\xe2\x80\x94\xe8\x83\xa1\xe5\xbf\x97\xe6\x98\x8e\xe5\xb8\x82\xe6\xb3\x95\xe5\x8a\xa1\xe9\x83\xa8\xe9\x97\xa8\xe3\x80\x82',
				'dept'       => '\xe6\xb3\x95\xe5\x8a\xa1\xe9\x83\xa8\xe9\x97\xa8',
				'loc'        => '\xe8\x83\xa1\xe5\xbf\x97\xe6\x98\x8e\xe5\xb8\x82',
				'level'      => '\xe4\xb8\xad\xe7\xba\xa7',
				'experience' => '3\xe5\xb9\xb4\xe4\xbb\xa5\xe4\xb8\x8a\xe9\x9b\x86\xe5\x9b\xa2\xe6\x88\x96\xe5\xbe\x8b\xe6\x89\x80\xe5\xb7\xa5\xe4\xbd\x9c\xe7\xbb\x8f\xe9\xaa\x8c',
				'salary'     => '2000\xe2\x80\x933000\xe4\xb8\x87\xe8\xb6\x8a\xe7\x9b\xbe/\xe6\x9c\x88',
				'content'    =>
					'<h2>\xe4\xb8\xbb\xe8\xa6\x81\xe8\x81\x8c\xe8\xb4\xa3</h2><ul>'
					. '<li>\xe8\xb5\xb7\xe8\x8d\x89\xe3\x80\x81\xe5\xae\xa1\xe6\xa0\xb8\xe5\xb9\xb6\xe5\xae\x8c\xe5\x96\x84\xe5\x90\x88\xe5\x90\x8c\xe5\x8f\x8a\xe6\xb3\x95\xe5\xbe\x8b\xe6\x96\x87\xe4\xbb\xb6</li>'
					. '<li>\xe6\x8f\x90\xe4\xbe\x9b\xe5\x85\xac\xe5\x8f\xb8\xe6\xb3\x95\xe3\x80\x81\xe5\xb9\xb6\xe8\xb4\xad\xe5\x8f\x8a\xe5\x8a\xb3\xe5\x8a\xa8\xe6\xb3\x95\xe5\xbe\x8b\xe5\x92\xa8\xe8\xaf\xa2</li>'
					. '<li>\xe4\xbb\xa3\xe8\xa1\xa8\xe5\x85\xac\xe5\x8f\xb8\xe4\xb8\x8e\xe6\x94\xbf\xe5\xba\x9c\xe4\xb8\xbb\xe7\xae\xa1\xe9\x83\xa8\xe9\x97\xa8\xe5\xaf\xb9\xe6\x8e\xa5</li>'
					. '<li>\xe7\xbb\xb4\xe6\x8a\xa4\xe5\xb9\xb6\xe7\xae\xa1\xe7\x90\x86\xe4\xbc\x81\xe4\xb8\x9a\xe6\xb3\x95\xe5\x8a\xa1\xe6\xa1\xa3\xe6\xa1\x88\xe7\xb3\xbb\xe7\xbb\x9f</li>'
					. '</ul>'
					. '<h2>\xe4\xbb\xbb\xe8\x81\x8c\xe8\xa6\x81\xe6\xb1\x82</h2><ul>'
					. '<li>\xe6\xb3\x95\xe5\xad\xa6\xe6\x9c\xac\xe7\xa7\x91\xe5\x8f\x8a\xe4\xbb\xa5\xe4\xb8\x8a\xe5\xad\xa6\xe5\x8e\x86</li>'
					. '<li>\xe4\xbc\x98\xe5\x85\x88\xe8\x80\x83\xe8\x99\x91\xe6\x8c\x81\xe6\x9c\x89\xe5\xbe\x8b\xe5\xb8\x88\xe6\x89\xa7\xe4\xb8\x9a\xe8\xaf\x81\xe4\xb9\xa6\xe8\x80\x85</li>'
					. '<li>\xe5\x85\xb7\xe5\xa4\x87\xe6\x88\xbf\xe5\x9c\xb0\xe4\xba\xa7\xe3\x80\x81\xe6\x8a\x95\xe8\xb5\x84\xe6\x88\x96\xe5\xb9\xb6\xe8\xb4\xad\xe7\x9b\xb8\xe5\x85\xb3\xe7\xbb\x8f\xe9\xaa\x8c\xe8\x80\x85\xe4\xbc\x98\xe5\x85\x88</li>'
					. '<li>\xe6\xb3\x95\xe5\xbe\x8b\xe8\x8b\xb1\xe8\xaf\xad\xe8\x83\xbd\xe5\x8a\x9b\xe8\xbe\x83\xe5\xbc\xba</li>'
					. '</ul>'
					. '<h2>\xe7\xa6\x8f\xe5\x88\xa9\xe5\xbe\x85\xe9\x81\x87</h2><ul>'
					. '<li>\xe5\x85\xb7\xe6\x9c\x89\xe7\xab\x9e\xe4\xba\x89\xe5\x8a\x9b\xe7\x9a\x84\xe8\x96\xaa\xe9\x85\xac</li>'
					. '<li>\xe6\x8e\xa5\xe8\xa7\xa6\xe9\xab\x98\xe4\xbb\xb7\xe5\x80\xbc\xe6\x8a\x95\xe8\xb5\x84\xe4\xb8\x8e\xe5\xb9\xb6\xe8\xb4\xad\xe4\xba\xa4\xe6\x98\x93\xe7\x9a\x84\xe6\x9c\xba\xe4\xbc\x9a</li>'
					. '<li>\xe8\xb5\x84\xe5\x8a\xa9\xe5\xbe\x8b\xe5\xb8\x88\xe8\xb5\x84\xe6\xa0\xbc\xe8\xaf\x81\xe4\xb9\xa6\xe8\x80\x83\xe8\xaf\x95</li>'
					. '</ul>',
			),
		),

		/* 6 - Talent Acquisition Lead */
		'talent-acquisition-lead' => array(
			'en' => array(
				'title'      => 'Talent Acquisition Lead',
				'excerpt'    => 'Talent Acquisition Lead role — Human Resources Division in Ho Chi Minh City.',
				'dept'       => 'Human Resources Division',
				'loc'        => 'Ho Chi Minh City',
				'level'      => 'Senior',
				'experience' => '5+ years recruiting at Senior \xe2\x80\x93 Manager level',
				'salary'     => 'VND 25 \xe2\x80\x93 40M/month',
				'content'    =>
					'<h2>Key responsibilities</h2><ul>'
					. '<li>Own the group&#39;s end-to-end recruitment strategy (300+ roles per year)</li>'
					. '<li>Build employer branding and conduct senior-level headhunting</li>'
					. '<li>Manage and develop a team of TA Specialists</li>'
					. '<li>Partner with the COO and BU heads on workforce planning</li>'
					. '</ul>'
					. '<h2>Requirements</h2><ul>'
					. '<li>5+ years of full end-to-end recruitment experience</li>'
					. '<li>Track record of headhunting at Senior \xe2\x80\x93 Director level</li>'
					. '<li>Data skills and pipeline analytics capability</li>'
					. '<li>Good English communication skills</li>'
					. '</ul>'
					. '<h2>Benefits</h2><ul>'
					. '<li>Base salary + KPI bonus + ESOP</li>'
					. '<li>Employer-branding and technology budget</li>'
					. '</ul>',
			),
			'zh' => array(
				'title'      => '\xe6\x8b\x9b\xe8\x81\x98\xe4\xb8\xbb\xe7\xae\xa1',
				'excerpt'    => '\xe6\x8b\x9b\xe8\x81\x98\xe4\xb8\xbb\xe7\xae\xa1\xe8\x81\x8c\xe4\xbd\x8d\xe2\x80\x94\xe2\x80\x94\xe8\x83\xa1\xe5\xbf\x97\xe6\x98\x8e\xe5\xb8\x82\xe4\xba\xba\xe5\x8a\x9b\xe8\xb5\x84\xe6\xba\x90\xe9\x83\xa8\xe9\x97\xa8\xe3\x80\x82',
				'dept'       => '\xe4\xba\xba\xe5\x8a\x9b\xe8\xb5\x84\xe6\xba\x90\xe9\x83\xa8\xe9\x97\xa8',
				'loc'        => '\xe8\x83\xa1\xe5\xbf\x97\xe6\x98\x8e\xe5\xb8\x82',
				'level'      => '\xe9\xab\x98\xe7\xba\xa7',
				'experience' => '5\xe5\xb9\xb4\xe4\xbb\xa5\xe4\xb8\x8a\xe9\xab\x98\xe7\xba\xa7\xe5\x88\xab\xe6\x8b\x9b\xe5\x8b\x9f\xe7\xbb\x8f\xe9\xaa\x8c',
				'salary'     => '2500\xe2\x80\x934000\xe4\xb8\x87\xe8\xb6\x8a\xe7\x9b\xbe/\xe6\x9c\x88',
				'content'    =>
					'<h2>\xe4\xb8\xbb\xe8\xa6\x81\xe8\x81\x8c\xe8\xb4\xa3</h2><ul>'
					. '<li>\xe4\xb8\xbb\xe5\xaf\xbc\xe9\x9b\x86\xe5\x9b\xa2\xe5\x85\xa8\xe6\xb5\x81\xe7\xa8\x8b\xe6\x8b\x9b\xe8\x81\x98\xe6\x88\x98\xe7\x95\xa5\xef\xbc\x88\xe6\xaf\x8f\xe5\xb9\xb4300+\xe8\x81\x8c\xe4\xbd\x8d\xef\xbc\x89</li>'
					. '<li>\xe5\xbb\xba\xe7\xab\x8b\xe9\x9b\x87\xe4\xb8\xbb\xe5\x93\x81\xe7\x89\x8c\xef\xbc\x8c\xe8\xb4\x9f\xe8\xb4\xa3\xe9\xab\x98\xe7\xab\xaf\xe4\xba\xba\xe6\x89\x8d\xe7\x8c\x8e\xe5\xa4\xb4</li>'
					. '<li>\xe7\xae\xa1\xe7\x90\x86\xe5\xb9\xb6\xe5\x9f\xb9\xe5\x85\xa8 TA \xe4\xb8\x93\xe5\x91\x98\xe5\x9b\xa2\xe9\x98\x9f</li>'
					. '<li>\xe4\xb8\x8e COO \xe5\x8f\x8a\xe4\xb8\x9a\xe5\x8a\xa1\xe8\xb4\x9f\xe8\xb4\xa3\xe4\xba\xba\xe5\x8d\x8f\xe5\x90\x8c\xe8\xbf\x9b\xe8\xa1\x8c\xe4\xba\xba\xe5\x8a\x9b\xe8\xb5\x84\xe6\xba\x90\xe8\xa7\x84\xe5\x88\x92</li>'
					. '</ul>'
					. '<h2>\xe4\xbb\xbb\xe8\x81\x8c\xe8\xa6\x81\xe6\xb1\x82</h2><ul>'
					. '<li>5\xe5\xb9\xb4\xe4\xbb\xa5\xe4\xb8\x8a\xe5\x85\xa8\xe6\xb5\x81\xe7\xa8\x8b\xe6\x8b\x9b\xe8\x81\x98\xe7\xbb\x8f\xe9\xaa\x8c</li>'
					. '<li>\xe5\x85\xb7\xe5\xa4\x87\xe9\xab\x98\xe7\xba\xa7\xe5\x88\xab\xe8\x87\xb3\xe6\x80\xbb\xe7\x9b\x91\xe7\xba\xa7\xe7\x8c\x8e\xe5\xa4\xb4\xe6\x88\x90\xe5\x8a\x9f\xe6\xa1\x88\xe4\xbe\x8b</li>'
					. '<li>\xe5\x85\xb7\xe5\xa4\x87\xe6\x95\xb0\xe6\x8d\xae\xe5\x88\x86\xe6\x9e\x90\xe4\xb8\x8e\xe5\x80\x99\xe9\x80\x89\xe4\xba\xba\xe7\xae\xa1\xe9\x81\x93\xe7\xae\xa1\xe7\x90\x86\xe8\x83\xbd\xe5\x8a\x9b</li>'
					. '<li>\xe8\x8b\xb1\xe8\xaf\xad\xe6\xb2\x9f\xe9\x80\x9a\xe8\x83\xbd\xe5\x8a\x9b\xe8\x89\xaf\xe5\xa5\xbd</li>'
					. '</ul>'
					. '<h2>\xe7\xa6\x8f\xe5\x88\xa9\xe5\xbe\x85\xe9\x81\x87</h2><ul>'
					. '<li>\xe5\x9f\xba\xe6\x9c\xac\xe8\x96\xaa\xe8\xb5\x84 + KPI \xe5\xa5\x96\xe9\x87\x91 + ESOP \xe5\x91\x98\xe5\xb7\xa5\xe6\x8c\x81\xe8\x82\xa1</li>'
					. '<li>\xe9\x9b\x87\xe4\xb8\xbb\xe5\x93\x81\xe7\x89\x8c\xe5\xbb\xba\xe8\xae\xbe\xe5\x8f\x8a\xe6\x8a\x80\xe6\x9c\xaf\xe5\xb7\xa5\xe5\x85\xb7\xe9\xa2\x84\xe7\xae\x97\xe6\x94\xaf\xe6\x8c\x81</li>'
					. '</ul>',
			),
		),

		/* 7 - CEO Assistant */
		'tro-ly-tong-giam-doc' => array(
			'en' => array(
				'title'      => 'CEO Assistant',
				'excerpt'    => 'CEO Assistant role — General Office Division in Ho Chi Minh City.',
				'dept'       => 'General Office Division',
				'loc'        => 'Ho Chi Minh City',
				'level'      => 'Mid-level',
				'experience' => '3+ years supporting a senior executive',
				'salary'     => 'VND 18 \xe2\x80\x93 28M/month',
				'content'    =>
					'<h2>Key responsibilities</h2><ul>'
					. '<li>Support the CEO in managing schedules, correspondence, and meetings</li>'
					. '<li>Prepare reports and presentations on request</li>'
					. '<li>Coordinate Executive Board meetings and follow-ups</li>'
					. '<li>Track the progress of strategic initiatives</li>'
					. '</ul>'
					. '<h2>Requirements</h2><ul>'
					. '<li>Bachelor&#39;s degree in a relevant discipline</li>'
					. '<li>Excellent writing, presentation, and organisational skills</li>'
					. '<li>Good English communication skills</li>'
					. '<li>Proficient in Office 365, Notion, and / or Asana</li>'
					. '</ul>'
					. '<h2>Benefits</h2><ul>'
					. '<li>Direct learning opportunity alongside the Executive team</li>'
					. '<li>Competitive salary and performance bonus</li>'
					. '</ul>',
			),
			'zh' => array(
				'title'      => '\xe6\x80\xbb\xe8\xa3\x81\xe5\x8a\xa9\xe7\x90\x86',
				'excerpt'    => '\xe6\x80\xbb\xe8\xa3\x81\xe5\x8a\xa9\xe7\x90\x86\xe8\x81\x8c\xe4\xbd\x8d\xe2\x80\x94\xe2\x80\x94\xe8\x83\xa1\xe5\xbf\x97\xe6\x98\x8e\xe5\xb8\x82\xe7\xbb\xbc\xe5\x90\x88\xe5\x8a\x9e\xe5\x85\xac\xe5\xae\xa4\xe9\x83\xa8\xe9\x97\xa8\xe3\x80\x82',
				'dept'       => '\xe7\xbb\xbc\xe5\x90\x88\xe5\x8a\x9e\xe5\x85\xac\xe5\xae\xa4',
				'loc'        => '\xe8\x83\xa1\xe5\xbf\x97\xe6\x98\x8e\xe5\xb8\x82',
				'level'      => '\xe4\xb8\xad\xe7\xba\xa7',
				'experience' => '3\xe5\xb9\xb4\xe4\xbb\xa5\xe4\xb8\x8a\xe9\xab\x98\xe7\xae\xa1\xe5\x8a\xa9\xe7\x90\x86\xe7\xbb\x8f\xe9\xaa\x8c',
				'salary'     => '1800\xe2\x80\x932800\xe4\xb8\x87\xe8\xb6\x8a\xe7\x9b\xbe/\xe6\x9c\x88',
				'content'    =>
					'<h2>\xe4\xb8\xbb\xe8\xa6\x81\xe8\x81\x8c\xe8\xb4\xa3</h2><ul>'
					. '<li>\xe5\x8d\x8f\xe5\x8a\xa9\xe6\x80\xbb\xe8\xa3\x81\xe7\xae\xa1\xe7\x90\x86\xe6\x97\xa5\xe7\xa8\x8b\xe3\x80\x81\xe5\x85\xac\xe6\x96\x87\xe4\xb8\x8e\xe4\xbc\x9a\xe8\xae\xae</li>'
					. '<li>\xe6\xa0\xb9\xe6\x8d\xae\xe9\x9c\x80\xe8\xa6\x81\xe6\x92\xb0\xe5\x86\x99\xe6\x8a\xa5\xe5\x91\x8a\xe4\xb8\x8e\xe6\xbc\x94\xe7\xa4\xba\xe6\x96\x87\xe7\xa8\xbf</li>'
					. '<li>\xe5\x8d\x8f\xe8\xb0\x83\xe6\x89\xa7\xe5\xa7\x94\xe4\xbc\x9a\xe4\xbc\x9a\xe8\xae\xae\xe5\xb9\xb6\xe8\xb7\x9f\xe8\xbf\x9b\xe5\x90\x8e\xe7\xb6\x8c\xe4\xba\x8b\xe9\xa1\xb9</li>'
					. '<li>\xe8\xb7\x9f\xe8\xb8\xaa\xe6\x88\x98\xe7\x95\xa5\xe4\xb8\xbe\xe6\x8e\xaa\xe7\x9a\x84\xe6\x8e\xa8\xe8\xbf\x9b\xe8\xbf\x9b\xe5\xba\xa6</li>'
					. '</ul>'
					. '<h2>\xe4\xbb\xbb\xe8\x81\x8c\xe8\xa6\x81\xe6\xb1\x82</h2><ul>'
					. '<li>\xe7\x9b\xb8\xe5\x85\xb3\xe4\xb8\x93\xe4\xb8\x9a\xe6\x9c\xac\xe7\xa7\x91\xe5\x8f\x8a\xe4\xbb\xa5\xe4\xb8\x8a\xe5\xad\xa6\xe5\x8e\x86</li>'
					. '<li>\xe5\x85\xb7\xe5\xa4\x87\xe5\x87\xba\xe8\x89\xb2\xe7\x9a\x84\xe5\x86\x99\xe4\xbd\x9c\xe3\x80\x81\xe8\xa1\xa8\xe8\xbe\xbe\xe4\xb8\x8e\xe7\xbb\x84\xe7\xbb\x87\xe5\x8d\x8f\xe8\xb0\x83\xe8\x83\xbd\xe5\x8a\x9b</li>'
					. '<li>\xe8\x8b\xb1\xe8\xaf\xad\xe6\xb2\x9f\xe9\x80\x9a\xe8\x83\xbd\xe5\x8a\x9b\xe8\x89\xaf\xe5\xa5\xbd</li>'
					. '<li>\xe7\x86\x9f\xe7\xbb\x83\xe4\xbd\xbf\xe7\x94\xa8 Office 365\xe3\x80\x81Notion \xe5\x8f\x8a/\xe6\x88\x96 Asana</li>'
					. '</ul>'
					. '<h2>\xe7\xa6\x8f\xe5\x88\xa9\xe5\xbe\x85\xe9\x81\x87</h2><ul>'
					. '<li>\xe7\x9b\xb4\xe6\x8e\xa5\xe5\x90\x91\xe6\x89\xa7\xe8\xa1\x8c\xe5\xb1\x82\xe5\xad\xa6\xe4\xb9\xa0\xe6\x88\x90\xe9\x95\xbf\xe7\x9a\x84\xe6\x9c\xba\xe4\xbc\x9a</li>'
					. '<li>\xe5\x85\xb7\xe6\x9c\x89\xe7\xab\x9e\xe4\xba\x89\xe5\x8a\x9b\xe7\x9a\x84\xe8\x96\xaa\xe9\x85\xac\xe4\xb8\x8e\xe7\xbb\xa9\xe6\x95\x88\xe5\xa5\x96\xe9\x87\x91</li>'
					. '</ul>',
			),
		),

		/* 8 - Corporate Communications Specialist */
		'chuyen-vien-truyen-thong-doanh-nghiep' => array(
			'en' => array(
				'title'      => 'Corporate Communications Specialist',
				'excerpt'    => 'Corporate Communications Specialist role — Marketing Division in Ho Chi Minh City.',
				'dept'       => 'Marketing Division',
				'loc'        => 'Ho Chi Minh City',
				'level'      => 'Junior',
				'experience' => '1\xe2\x80\x933 years in PR / Corporate Communications',
				'salary'     => 'VND 12 \xe2\x80\x93 18M/month',
				'content'    =>
					'<h2>Key responsibilities</h2><ul>'
					. '<li>Manage corporate communication channels (website, fanpage, LinkedIn)</li>'
					. '<li>Plan and write public-relations content</li>'
					. '<li>Maintain media relationships and conduct media monitoring</li>'
					. '<li>Support event organisation</li>'
					. '</ul>'
					. '<h2>Requirements</h2><ul>'
					. '<li>Bachelor&#39;s degree in Journalism, Communications, or Marketing</li>'
					. '<li>Excellent Vietnamese writing and editing skills</li>'
					. '<li>Corporate content experience is a plus</li>'
					. '<li>Proficient in Canva and social-analytics tools</li>'
					. '</ul>'
					. '<h2>Benefits</h2><ul>'
					. '<li>Internal training and agency-partner programme</li>'
					. '<li>Listed-company corporate communications environment</li>'
					. '<li>Clear career-development path</li>'
					. '</ul>',
			),
			'zh' => array(
				'title'      => '\xe4\xbc\x81\xe4\xb8\x9a\xe4\xbc\xa0\xe6\x92\xad\xe4\xb8\x93\xe5\x91\x98',
				'excerpt'    => '\xe4\xbc\x81\xe4\xb8\x9a\xe4\xbc\xa0\xe6\x92\xad\xe4\xb8\x93\xe5\x91\x98\xe8\x81\x8c\xe4\xbd\x8d\xe2\x80\x94\xe2\x80\x94\xe8\x83\xa1\xe5\xbf\x97\xe6\x98\x8e\xe5\xb8\x82\xe5\xb8\x82\xe5\x9c\xba\xe9\x83\xa8\xe9\x97\xa8\xe3\x80\x82',
				'dept'       => '\xe5\xb8\x82\xe5\x9c\xba\xe9\x83\xa8\xe9\x97\xa8',
				'loc'        => '\xe8\x83\xa1\xe5\xbf\x97\xe6\x98\x8e\xe5\xb8\x82',
				'level'      => '\xe5\x88\x9d\xe7\xba\xa7',
				'experience' => '1\xe2\x80\x933\xe5\xb9\xb4\xe5\x85\xac\xe5\x85\xb3/\xe4\xbc\x81\xe4\xb8\x9a\xe4\xbc\xa0\xe6\x92\xad\xe7\xbb\x8f\xe9\xaa\x8c',
				'salary'     => '1200\xe2\x80\x931800\xe4\xb8\x87\xe8\xb6\x8a\xe7\x9b\xbe/\xe6\x9c\x88',
				'content'    =>
					'<h2>\xe4\xb8\xbb\xe8\xa6\x81\xe8\x81\x8c\xe8\xb4\xa3</h2><ul>'
					. '<li>\xe7\xae\xa1\xe7\x90\x86\xe4\xbc\x81\xe4\xb8\x9a\xe4\xbc\xa0\xe6\x92\xad\xe6\xb8\xa0\xe9\x81\x93\xef\xbc\x88\xe5\xae\x98\xe7\xbd\x91\xe3\x80\x81\xe7\xb2\x89\xe4\xb8\x9d\xe9\xa1\xb5\xe3\x80\x81LinkedIn\xef\xbc\x89</li>'
					. '<li>\xe8\xa7\x84\xe5\x88\x92\xe5\xb9\xb6\xe6\x92\xb0\xe5\x86\x99\xe5\x85\xac\xe5\x85\xb3\xe5\x86\x85\xe5\xae\xb9</li>'
					. '<li>\xe7\xbb\xb4\xe6\x8a\xa4\xe5\xaa\x92\xe4\xbd\x93\xe5\x85\xb3\xe7\xb3\xbb\xef\xbc\x8c\xe5\xbc\x80\xe5\xb1\x95\xe5\xaa\x92\xe4\xbd\x93\xe7\x9b\x91\xe6\xb5\x8b</li>'
					. '<li>\xe5\x8d\x8f\xe5\x8a\xa9\xe6\xb4\xbb\xe5\x8a\xa8\xe7\xbb\x84\xe7\xbb\x87\xe4\xb8\x8e\xe6\x89\xa7\xe8\xa1\x8c</li>'
					. '</ul>'
					. '<h2>\xe4\xbb\xbb\xe8\x81\x8c\xe8\xa6\x81\xe6\xb1\x82</h2><ul>'
					. '<li>\xe6\x96\xb0\xe9\x97\xbb\xe5\xad\xa6\xe3\x80\x81\xe4\xbc\xa0\xe6\x92\xad\xe5\xad\xa6\xe6\x88\x96\xe5\xb8\x82\xe5\x9c\xba\xe8\x90\xa5\xe9\x94\x80\xe4\xb8\x93\xe4\xb8\x9a\xe6\x9c\xac\xe7\xa7\x91\xe5\x8f\x8a\xe4\xbb\xa5\xe4\xb8\x8a\xe5\xad\xa6\xe5\x8e\x86</li>'
					. '<li>\xe8\xb6\x8a\xe5\x8d\x97\xe8\xaf\xad\xe5\x86\x99\xe4\xbd\x9c\xe4\xb8\x8e\xe7\xbc\x96\xe8\xbe\x91\xe8\x83\xbd\xe5\x8a\x9b\xe5\x87\xba\xe8\x89\xb2</li>'
					. '<li>\xe6\x9c\x89\xe4\xbc\x81\xe4\xb8\x9a\xe5\x86\x85\xe5\xae\xb9\xe5\x88\x9b\xe4\xbd\x9c\xe7\xbb\x8f\xe9\xaa\x8c\xe8\x80\x85\xe4\xbc\x98\xe5\x85\x88</li>'
					. '<li>\xe7\x86\x9f\xe7\xbb\x83\xe4\xbd\xbf\xe7\x94\xa8 Canva \xe5\x8f\x8a\xe7\xa4\xbe\xe4\xba\xa4\xe5\xaa\x92\xe4\xbd\x93\xe6\x95\xb0\xe6\x8d\xae\xe5\x88\x86\xe6\x9e\x90\xe5\xb7\xa5\xe5\x85\xb7</li>'
					. '</ul>'
					. '<h2>\xe7\xa6\x8f\xe5\x88\xa9\xe5\xbe\x85\xe9\x81\x87</h2><ul>'
					. '<li>\xe5\x86\x85\xe9\x83\xa8\xe5\x9f\xb9\xe8\xae\xad\xe5\x8f\x8a\xe4\xbb\xa3\xe7\x90\x86\xe5\x95\x86\xe5\x90\x88\xe4\xbd\x9c\xe9\xa1\xb9\xe7\x9b\xae</li>'
					. '<li>\xe5\x9c\xa8\xe4\xb8\x8a\xe5\xb8\x82\xe5\x85\xac\xe5\x8f\xb8\xe4\xbc\x81\xe4\xb8\x9a\xe4\xbc\xa0\xe6\x92\xad\xe7\x8e\xaf\xe5\xa2\x83\xe4\xb8\xad\xe7\xa7\xaf\xe7\xb4\xaf\xe7\xbb\x8f\xe9\xaa\x8c</li>'
					. '<li>\xe6\xb8\x85\xe6\x99\xb0\xe7\x9a\x84\xe8\x81\x8c\xe4\xb8\x9a\xe5\x8f\x91\xe5\xb1\x95\xe8\xb7\xaf\xe5\xbe\x84</li>'
					. '</ul>',
			),
		),

		/* 9 - Senior Structural Engineer */
		'ky-su-ket-cau-cap-cao' => array(
			'en' => array(
				'title'      => 'Senior Structural Engineer',
				'excerpt'    => 'Senior Structural Engineer role — Project Division in Binh Duong.',
				'dept'       => 'Project Division',
				'loc'        => 'Binh Duong',
				'level'      => 'Senior',
				'experience' => '6+ years in high-rise structural engineering',
				'salary'     => 'VND 35 \xe2\x80\x93 55M/month',
				'content'    =>
					'<h2>Key responsibilities</h2><ul>'
					. '<li>Lead structural design and review for high-rise projects</li>'
					. '<li>Collaborate with international consultants and contractors</li>'
					. '<li>Ensure structural safety throughout the project life cycle</li>'
					. '<li>Supervise and develop junior structural engineers</li>'
					. '</ul>'
					. '<h2>Requirements</h2><ul>'
					. '<li>Bachelor&#39;s degree in Civil Engineering, Structural Specialisation</li>'
					. '<li>Proficient in ETABS, SAFE, and RAM</li>'
					. '<li>Experience on projects of 30+ storeys</li>'
					. '<li>Able to read and apply ACI, BS, and TCVN standards</li>'
					. '</ul>'
					. '<h2>Benefits</h2><ul>'
					. '<li>Base salary plus project performance bonus</li>'
					. '<li>Shuttle bus service on the HCMC \xe2\x80\x93 Binh Duong route</li>'
					. '<li>Opportunity to work on large-scale landmark projects</li>'
					. '</ul>',
			),
			'zh' => array(
				'title'      => '\xe9\xab\x98\xe7\xba\xa7\xe7\xbb\x93\xe6\x9e\x84\xe5\xb7\xa5\xe7\xa8\x8b\xe5\xb8\x88',
				'excerpt'    => '\xe9\xab\x98\xe7\xba\xa7\xe7\xbb\x93\xe6\x9e\x84\xe5\xb7\xa5\xe7\xa8\x8b\xe5\xb8\x88\xe8\x81\x8c\xe4\xbd\x8d\xe2\x80\x94\xe2\x80\x94\xe5\xb9\xb3\xe9\x98\xb3\xe7\x9c\x81\xe9\xa1\xb9\xe7\x9b\xae\xe9\x83\xa8\xe9\x97\xa8\xe3\x80\x82',
				'dept'       => '\xe9\xa1\xb9\xe7\x9b\xae\xe9\x83\xa8\xe9\x97\xa8',
				'loc'        => '\xe5\xb9\xb3\xe9\x98\xb3',
				'level'      => '\xe9\xab\x98\xe7\xba\xa7',
				'experience' => '6\xe5\xb9\xb4\xe4\xbb\xa5\xe4\xb8\x8a\xe9\xab\x98\xe5\xb1\x82\xe5\xbb\xba\xe7\xad\x91\xe7\xbb\x93\xe6\x9e\x84\xe8\xae\xbe\xe8\xae\xa1\xe7\xbb\x8f\xe9\xaa\x8c',
				'salary'     => '3500\xe2\x80\x935500\xe4\xb8\x87\xe8\xb6\x8a\xe7\x9b\xbe/\xe6\x9c\x88',
				'content'    =>
					'<h2>\xe4\xb8\xbb\xe8\xa6\x81\xe8\x81\x8c\xe8\xb4\xa3</h2><ul>'
					. '<li>\xe4\xb8\xbb\xe6\x8c\x81\xe9\xab\x98\xe5\xb1\x82\xe9\xa1\xb9\xe7\x9b\xae\xe7\xbb\x93\xe6\x9e\x84\xe8\xae\xbe\xe8\xae\xa1\xe4\xb8\x8e\xe5\xae\xa1\xe6\xa0\xb8</li>'
					. '<li>\xe4\xb8\x8e\xe5\xa2\x83\xe5\xa4\x96\xe9\xa1\xbe\xe9\x97\xae\xe5\x8f\x8a\xe6\x89\xbf\xe5\x8c\x85\xe5\x95\x86\xe5\x8d\x8f\xe5\x90\x8c\xe5\xb7\xa5\xe4\xbd\x9c</li>'
					. '<li>\xe7\xa1\xae\xe4\xbf\x9d\xe9\xa1\xb9\xe7\x9b\xae\xe5\x85\xa8\xe7\x94\x9f\xe5\x91\xbd\xe5\x91\xa8\xe6\x9c\x9f\xe7\xbb\x93\xe6\x9e\x84\xe5\xae\x89\xe5\x85\xa8</li>'
					. '<li>\xe6\x8c\x87\xe5\xaf\xbc\xe5\xb9\xb6\xe5\x9f\xb9\xe5\x85\xa7\xe5\x88\x9d\xe7\xba\xa7\xe7\xbb\x93\xe6\x9e\x84\xe5\xb7\xa5\xe7\xa8\x8b\xe5\xb8\x88</li>'
					. '</ul>'
					. '<h2>\xe4\xbb\xbb\xe8\x81\x8c\xe8\xa6\x81\xe6\xb1\x82</h2><ul>'
					. '<li>\xe5\x9c\x9f\xe6\x9c\xa8\xe5\xb7\xa5\xe7\xa8\x8b\xef\xbc\x88\xe7\xbb\x93\xe6\x9e\x84\xe6\x96\xb9\xe5\x90\x91\xef\xbc\x89\xe6\x9c\xac\xe7\xa7\x91\xe5\x8f\x8a\xe4\xbb\xa5\xe4\xb8\x8a\xe5\xad\xa6\xe5\x8e\x86</li>'
					. '<li>\xe7\x86\x9f\xe7\xbb\x83\xe4\xbd\xbf\xe7\x94\xa8 ETABS\xe3\x80\x81SAFE \xe5\x92\x8c RAM</li>'
					. '<li>\xe5\x85\xb7\xe5\xa4\x8730\xe5\xb1\x82\xe4\xbb\xa5\xe4\xb8\x8a\xe9\xa1\xb9\xe7\x9b\xae\xe7\xbb\x8f\xe9\xaa\x8c</li>'
					. '<li>\xe8\x83\xbd\xe5\xa4\x9f\xe9\x98\x85\xe8\xaf\xbb\xe5\xb9\xb6\xe5\xba\x94\xe7\x94\xa8 ACI\xe3\x80\x81BS \xe5\x8f\x8a TCVN \xe6\xa0\x87\xe5\x87\x86</li>'
					. '</ul>'
					. '<h2>\xe7\xa6\x8f\xe5\x88\xa9\xe5\xbe\x85\xe9\x81\x87</h2><ul>'
					. '<li>\xe5\x9f\xba\xe6\x9c\xac\xe8\x96\xaa\xe8\xb5\x84 + \xe9\xa1\xb9\xe7\x9b\xae\xe7\xbb\xa9\xe6\x95\x88\xe5\xa5\x96\xe9\x87\x91</li>'
					. '<li>\xe6\x8f\x90\xe4\xbe\x9b\xe8\x83\xa1\xe5\xbf\x97\xe6\x98\x8e\xe5\xb8\x82\xe2\x80\x94\xe5\xb9\xb3\xe9\x98\xb3\xe9\x80\x9a\xe5\x8b\xa4\xe7\x8f\xad\xe8\xbd\xa6</li>'
					. '<li>\xe5\x8f\x82\xe4\xb8\x8e\xe5\xa4\xa7\xe5\x9e\x8b\xe6\xa0\x87\xe5\xbf\x97\xe6\x80\xa7\xe9\xa1\xb9\xe7\x9b\xae\xe7\x9a\x84\xe6\x9c\xba\xe4\xbc\x9a</li>'
					. '</ul>',
			),
		),

		/* 10 - Real Estate Business Intern */
		'thuc-tap-sinh-kinh-doanh-bds' => array(
			'en' => array(
				'title'      => 'Real Estate Business Intern',
				'excerpt'    => 'Real Estate Business Intern role — Business Division in Ho Chi Minh City.',
				'dept'       => 'Business Division',
				'loc'        => 'Ho Chi Minh City',
				'level'      => 'Intern',
				'experience' => '3rd-year or final-year student',
				'salary'     => 'VND 5 \xe2\x80\x93 7M/month',
				'content'    =>
					'<h2>Key responsibilities</h2><ul>'
					. '<li>Support customer, competitor, and project research</li>'
					. '<li>Participate in sales launch events and workshops</li>'
					. '<li>Assist with data entry and sales pipeline management</li>'
					. '<li>Work alongside senior business development specialists</li>'
					. '</ul>'
					. '<h2>Requirements</h2><ul>'
					. '<li>3rd-year or final-year student in Economics, Marketing, or Real Estate</li>'
					. '<li>Communicative English</li>'
					. '<li>Enthusiastic, fast learner, strong teamwork skills</li>'
					. '</ul>'
					. '<h2>Benefits</h2><ul>'
					. '<li>Allowance plus deal-based commission</li>'
					. '<li>Structured training on real estate products</li>'
					. '<li>Opportunity to convert to full-time employment after internship</li>'
					. '</ul>',
			),
			'zh' => array(
				'title'      => '\xe6\x88\xbf\xe5\x9c\xb0\xe4\xba\xa7\xe4\xb8\x9a\xe5\x8a\xa1\xe5\xae\x9e\xe4\xb9\xa0\xe7\x94\x9f',
				'excerpt'    => '\xe6\x88\xbf\xe5\x9c\xb0\xe4\xba\xa7\xe4\xb8\x9a\xe5\x8a\xa1\xe5\xae\x9e\xe4\xb9\xa0\xe7\x94\x9f\xe8\x81\x8c\xe4\xbd\x8d\xe2\x80\x94\xe2\x80\x94\xe8\x83\xa1\xe5\xbf\x97\xe6\x98\x8e\xe5\xb8\x82\xe4\xb8\x9a\xe5\x8a\xa1\xe9\x83\xa8\xe9\x97\xa8\xe3\x80\x82',
				'dept'       => '\xe4\xb8\x9a\xe5\x8a\xa1\xe9\x83\xa8\xe9\x97\xa8',
				'loc'        => '\xe8\x83\xa1\xe5\xbf\x97\xe6\x98\x8e\xe5\xb8\x82',
				'level'      => '\xe5\xae\x9e\xe4\xb9\xa0\xe7\x94\x9f',
				'experience' => '\xe5\xa4\xa7\xe4\xb8\x89\xe6\x88\x96\xe5\xba\x94\xe5\xb1\x8a\xe6\xaf\x95\xe4\xb8\x9a\xe5\xb9\xb4\xe7\xba\xa7\xe5\x9c\xa8\xe8\xaf\xbb',
				'salary'     => '500\xe2\x80\x93700\xe4\xb8\x87\xe8\xb6\x8a\xe7\x9b\xbe/\xe6\x9c\x88',
				'content'    =>
					'<h2>\xe4\xb8\xbb\xe8\xa6\x81\xe8\x81\x8c\xe8\xb4\xa3</h2><ul>'
					. '<li>\xe5\x8d\x8f\xe5\x8a\xa9\xe5\xbc\x80\xe5\xb1\x95\xe5\xae\xa2\xe6\x88\xb7\xe3\x80\x81\xe7\xab\x9e\xe5\x93\x81\xe5\x8f\x8a\xe9\xa1\xb9\xe7\x9b\xae\xe7\xa0\x94\xe7\xa9\xb6</li>'
					. '<li>\xe5\x8f\x82\xe4\xb8\x8e\xe9\x94\x80\xe5\x94\xae\xe6\xb4\xbb\xe5\x8a\xa8\xe5\x90\xaf\xe5\x8a\xa8\xe5\x8f\x8a\xe5\xb7\xa5\xe4\xbd\x9c\xe5\x9d\x8a</li>'
					. '<li>\xe5\x8d\x8f\xe5\x8a\xa9\xe5\xbd\x95\xe5\x85\xa5\xe6\x95\xb0\xe6\x8d\xae\xe5\xb9\xb6\xe7\xbb\xb4\xe6\x8a\xa4\xe9\x94\x80\xe5\x94\xae\xe7\xae\xa1\xe9\x81\x93</li>'
					. '<li>\xe4\xb8\x8e\xe9\xab\x98\xe7\xba\xa7\xe4\xb8\x9a\xe5\x8a\xa1\xe4\xb8\x93\xe5\x91\x98\xe5\x8d\x8f\xe5\x90\x8c\xe5\xb7\xa5\xe4\xbd\x9c</li>'
					. '</ul>'
					. '<h2>\xe4\xbb\xbb\xe8\x81\x8c\xe8\xa6\x81\xe6\xb1\x82</h2><ul>'
					. '<li>\xe7\xbb\x8f\xe6\xb5\x8e\xe5\xad\xa6\xe3\x80\x81\xe5\xb8\x82\xe5\x9c\xba\xe8\x90\xa5\xe9\x94\x80\xe6\x88\x96\xe6\x88\xbf\xe5\x9c\xb0\xe4\xba\xa7\xe7\x9b\xb8\xe5\x85\xb3\xe4\xb8\x93\xe4\xb8\x9a\xe5\xa4\xa7\xe4\xb8\x89\xe6\x88\x96\xe5\xba\x94\xe5\xb1\x8a\xe5\x9c\xa8\xe8\xaf\xbb\xe7\x94\x9f</li>'
					. '<li>\xe5\x85\xb7\xe5\xa4\x87\xe6\x97\xa5\xe5\xb8\xb8\xe8\x8b\xb1\xe8\xaf\xad\xe6\xb2\x9f\xe9\x80\x9a\xe8\x83\xbd\xe5\x8a\x9b</li>'
					. '<li>\xe7\x83\xad\xe6\x83\x85\xe7\xa7\xaf\xe6\x9e\x81\xe3\x80\x81\xe5\xad\xa6\xe4\xb9\xa0\xe8\x83\xbd\xe5\x8a\x9b\xe5\xbc\xba\xe3\x80\x81\xe5\x85\xb7\xe5\xa4\x87\xe8\x89\xaf\xe5\xa5\xbd\xe7\x9a\x84\xe5\x9b\xa2\xe9\x98\x9f\xe5\x90\x88\xe4\xbd\x9c\xe7\xb2\xbe\xe7\xa5\x9e</li>'
					. '</ul>'
					. '<h2>\xe7\xa6\x8f\xe5\x88\xa9\xe5\xbe\x85\xe9\x81\x87</h2><ul>'
					. '<li>\xe5\xae\x9e\xe4\xb9\xa0\xe8\xa1\xa5\xe8\xb4\xb4 + \xe6\x88\x90\xe4\xba\xa4\xe4\xbd\xa3\xe9\x87\x91</li>'
					. '<li>\xe7\xb3\xbb\xe7\xbb\x9f\xe7\x9a\x84\xe6\x88\xbf\xe5\x9c\xb0\xe4\xba\xa7\xe4\xba\xa7\xe5\x93\x81\xe5\x9f\xb9\xe8\xae\xad</li>'
					. '<li>\xe5\xae\x9e\xe4\xb9\xa0\xe7\xbb\x93\xe6\x9d\x9f\xe5\x90\x8e\xe6\x9c\x89\xe6\x9c\xba\xe4\xbc\x9a\xe8\xbd\xac\xe4\xb8\xba\xe5\x85\xa8\xe8\x81\x8c\xe5\x91\x98\xe5\xb7\xa5</li>'
					. '</ul>',
			),
		),
	);
}

/**
 * Lookup helper: returns the translated field for a given qp_job post.
 *
 * @param WP_Post|int $post     Post object or ID.
 * @param string      $field    One of: title, excerpt, content, dept, loc, level, experience, salary.
 * @param string      $fallback Value to return when no translation is found.
 */
function qp_holdings_seeded_job_translation_for_post( $post, string $field, string $fallback = '' ): string {
	if ( is_admin() ) {
		return $fallback;
	}

	$lang = qp_holdings_current_language();
	if ( 'vi' === $lang ) {
		return $fallback;
	}

	$post_obj = $post instanceof WP_Post ? $post : get_post( $post );
	if ( ! $post_obj instanceof WP_Post || 'qp_job' !== $post_obj->post_type ) {
		return $fallback;
	}

	$map = qp_holdings_seeded_job_translations();
	$source_slug = qp_holdings_translation_source_slug( $post_obj );
	$value = $map[ $source_slug ][ $lang ][ $field ] ?? $fallback;
	return function_exists( 'qp_holdings_decode_escaped_utf8_text' ) ? qp_holdings_decode_escaped_utf8_text( (string) $value ) : (string) $value;
}

/* -- Wire title / excerpt / content filters for qp_job -- */

function qp_holdings_filter_seeded_job_title( string $title, int $post_id = 0 ): string {
	return qp_holdings_seeded_job_translation_for_post( $post_id, 'title', $title );
}
add_filter( 'the_title', 'qp_holdings_filter_seeded_job_title', 20, 2 );

function qp_holdings_filter_seeded_job_excerpt( string $excerpt, $post ): string {
	return qp_holdings_seeded_job_translation_for_post( $post, 'excerpt', $excerpt );
}
add_filter( 'get_the_excerpt', 'qp_holdings_filter_seeded_job_excerpt', 20, 2 );

function qp_holdings_filter_seeded_job_content( string $content ): string {
	$post = get_post();
	return qp_holdings_seeded_job_translation_for_post( $post, 'content', $content );
}
add_filter( 'the_content', 'qp_holdings_filter_seeded_job_content', 20 );

function qp_holdings_find_translation_post_by_source_slug( string $post_type, string $source_slug, string $lang ): int {
	$posts = get_posts(
		array(
			'post_type'        => $post_type,
			'post_status'      => array( 'publish', 'draft', 'private' ),
			'posts_per_page'   => 1,
			'suppress_filters' => true,
			'meta_query'       => array(
				array(
					'key'   => '_qp_source_slug',
					'value' => $source_slug,
				),
				array(
					'key'   => '_qp_translation_lang',
					'value' => $lang,
				),
			),
			'fields'           => 'ids',
		)
	);

	return ! empty( $posts[0] ) ? (int) $posts[0] : 0;
}

function qp_holdings_copy_post_meta_to_translation( int $source_id, int $target_id, array $translated_meta = array() ): void {
	$skip = array( '_edit_lock', '_edit_last', '_wp_old_slug' );
	$all_meta = get_post_meta( $source_id );
	foreach ( $all_meta as $meta_key => $values ) {
		if ( in_array( $meta_key, $skip, true ) ) {
			continue;
		}
		delete_post_meta( $target_id, $meta_key );
		foreach ( (array) $values as $value ) {
			add_post_meta( $target_id, $meta_key, maybe_unserialize( $value ) );
		}
	}

	foreach ( $translated_meta as $meta_key => $value ) {
		update_post_meta( $target_id, $meta_key, $value );
	}

	update_post_meta( $target_id, '_thumbnail_id', get_post_thumbnail_id( $source_id ) );
}

function qp_holdings_copy_terms_to_translation( int $source_id, int $target_id, string $post_type ): void {
	$target_lang = function_exists( 'pll_get_post_language' ) ? pll_get_post_language( $target_id, 'slug' ) : '';
	$taxonomies = get_object_taxonomies( $post_type );
	foreach ( $taxonomies as $taxonomy ) {
		$term_ids = wp_get_object_terms( $source_id, $taxonomy, array( 'fields' => 'ids' ) );
		if ( ! is_wp_error( $term_ids ) ) {
			if ( $target_lang && function_exists( 'pll_get_term' ) ) {
				$translated_ids = array();
				foreach ( $term_ids as $term_id ) {
					$translated_term_id = pll_get_term( (int) $term_id, $target_lang );
					$translated_ids[]   = $translated_term_id ? (int) $translated_term_id : (int) $term_id;
				}
				$term_ids = $translated_ids;
			}
			wp_set_object_terms( $target_id, $term_ids, $taxonomy, false );
		}
	}
}

function qp_holdings_seeded_investor_section_term_translations(): array {
	return array(
		'stock-info'         => array( 'en' => array( 'name' => 'Stock Information', 'slug' => 'stock-information' ), 'zh' => array( 'name' => '股票信息', 'slug' => 'stock-information-zh' ) ),
		'company-governance' => array( 'en' => array( 'name' => 'Corporate Governance', 'slug' => 'corporate-governance' ), 'zh' => array( 'name' => '公司治理', 'slug' => 'corporate-governance-zh' ) ),
		'agm'                => array( 'en' => array( 'name' => 'General Meeting of Shareholders', 'slug' => 'general-meeting-of-shareholders' ), 'zh' => array( 'name' => '股东大会', 'slug' => 'general-meeting-of-shareholders-zh' ) ),
		'annual-report'      => array( 'en' => array( 'name' => 'Annual Reports', 'slug' => 'annual-reports' ), 'zh' => array( 'name' => '年度报告', 'slug' => 'annual-reports-zh' ) ),
		'financial-report'   => array( 'en' => array( 'name' => 'Financial Reports', 'slug' => 'financial-reports' ), 'zh' => array( 'name' => '财务报告', 'slug' => 'financial-reports-zh' ) ),
		'governance-report'  => array( 'en' => array( 'name' => 'Corporate Governance Reports', 'slug' => 'corporate-governance-reports' ), 'zh' => array( 'name' => '公司治理报告', 'slug' => 'corporate-governance-reports-zh' ) ),
		'disclosure'         => array( 'en' => array( 'name' => 'Disclosures', 'slug' => 'disclosures' ), 'zh' => array( 'name' => '信息披露', 'slug' => 'disclosures-zh' ) ),
	);
}

function qp_holdings_sync_investor_section_terms(): void {
	if ( ! function_exists( 'pll_set_term_language' ) || ! function_exists( 'pll_save_term_translations' ) || ! function_exists( 'pll_get_term' ) ) {
		return;
	}

	$map = qp_holdings_seeded_investor_section_term_translations();
	foreach ( $map as $source_slug => $translations ) {
		$source_term = get_term_by( 'slug', $source_slug, 'qp_investor_section' );
		if ( ! $source_term || is_wp_error( $source_term ) ) {
			continue;
		}

		pll_set_term_language( (int) $source_term->term_id, 'vi' );
		$group = array( 'vi' => (int) $source_term->term_id );
		foreach ( array( 'en', 'zh' ) as $lang ) {
			$translated_term_id = pll_get_term( (int) $source_term->term_id, $lang );
			if ( ! $translated_term_id ) {
				$target = $translations[ $lang ];
				$existing = get_term_by( 'slug', $target['slug'], 'qp_investor_section' );
				if ( $existing && ! is_wp_error( $existing ) ) {
					$translated_term_id = (int) $existing->term_id;
				} else {
					$result = wp_insert_term( $target['name'], 'qp_investor_section', array( 'slug' => $target['slug'] ) );
					if ( ! is_wp_error( $result ) && ! empty( $result['term_id'] ) ) {
						$translated_term_id = (int) $result['term_id'];
					}
				}
			}

			if ( $translated_term_id ) {
				pll_set_term_language( (int) $translated_term_id, $lang );
				$group[ $lang ] = (int) $translated_term_id;
			}
		}

		if ( count( $group ) > 1 ) {
			pll_save_term_translations( $group );
		}
	}
}

function qp_holdings_sync_seeded_polylang_translations(): void {
	if ( ! function_exists( 'pll_languages_list' ) || ! function_exists( 'pll_set_post_language' ) || ! function_exists( 'pll_save_post_translations' ) || ! function_exists( 'pll_get_post' ) ) {
		return;
	}

	$flag = 'qp_holdings_seeded_cpt_polylang_sync_v2';
	if ( get_option( $flag ) ) {
		return;
	}

	$definitions = array(
		'qp_news' => array(
			'map' => 'qp_holdings_seeded_news_translations',
			'fields' => array( 'title' => 'title', 'excerpt' => 'excerpt', 'content' => 'content' ),
			'meta_fields' => array(),
		),
		'qp_project' => array(
			'map' => 'qp_holdings_seeded_project_translations',
			'fields' => array( 'title' => 'title', 'excerpt' => 'subtitle', 'content' => 'content' ),
			'meta_fields' => array(
				'subtitle' => 'subtitle',
				'category_info' => 'category',
				'address' => 'address',
				'scale' => 'scale',
				'area_total' => 'area_total',
				'investor_name' => 'investor',
				'developer' => 'developer',
				'completion_year' => 'completion',
				'handover' => 'handover',
				'highlights' => 'highlights',
			),
		),
		'qp_job' => array(
			'map' => 'qp_holdings_seeded_job_translations',
			'fields' => array( 'title' => 'title', 'excerpt' => 'excerpt', 'content' => 'content' ),
			'meta_fields' => array(
				'department' => 'dept',
				'location' => 'loc',
				'level' => 'level',
				'experience' => 'experience',
				'salary' => 'salary',
			),
		),
		'qp_investor_doc' => array(
			'map' => 'qp_holdings_seeded_investor_doc_translations',
			'fields' => array( 'title' => 'title' ),
			'meta_fields' => array(),
		),
	);

	qp_holdings_sync_investor_section_terms();

	foreach ( $definitions as $post_type => $definition ) {
		if ( ! function_exists( $definition['map'] ) ) {
			continue;
		}

		$map = call_user_func( $definition['map'] );
		if ( empty( $map ) ) {
			continue;
		}

		$sources = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'lang'           => 'vi',
			)
		);

		foreach ( $sources as $source ) {
			$source_slug = (string) $source->post_name;
			if ( empty( $map[ $source_slug ] ) ) {
				continue;
			}

			$translations = array( 'vi' => (int) $source->ID );
			foreach ( array( 'en', 'zh' ) as $lang ) {
				$data = $map[ $source_slug ][ $lang ] ?? array();
				if ( empty( $data ) ) {
					continue;
				}

				$translated_id = (int) pll_get_post( (int) $source->ID, $lang );
				if ( ! $translated_id ) {
					$translated_id = qp_holdings_find_translation_post_by_source_slug( $post_type, $source_slug, $lang );
				}

				$post_args = array(
					'post_type'    => $post_type,
					'post_status'  => $source->post_status,
					'post_title'   => $data[ $definition['fields']['title'] ] ?? $source->post_title,
					'post_name'    => $source_slug . '-' . $lang,
					'post_excerpt' => isset( $definition['fields']['excerpt'] ) ? ( $data[ $definition['fields']['excerpt'] ] ?? $source->post_excerpt ) : $source->post_excerpt,
					'post_content' => isset( $definition['fields']['content'] ) ? ( $data[ $definition['fields']['content'] ] ?? $source->post_content ) : $source->post_content,
					'post_date'    => $source->post_date,
				);
				if ( $translated_id ) {
					$post_args['ID'] = $translated_id;
				}

				$translated_id = wp_insert_post( $post_args, true );
				if ( is_wp_error( $translated_id ) || ! $translated_id ) {
					continue;
				}

				pll_set_post_language( (int) $translated_id, $lang );
				$translated_meta = array( '_qp_source_slug' => $source_slug, '_qp_translation_lang' => $lang );
				foreach ( $definition['meta_fields'] as $meta_key => $field_key ) {
					if ( isset( $data[ $field_key ] ) ) {
						$translated_meta[ $meta_key ] = $data[ $field_key ];
					}
				}
				qp_holdings_copy_post_meta_to_translation( (int) $source->ID, (int) $translated_id, $translated_meta );
				qp_holdings_copy_terms_to_translation( (int) $source->ID, (int) $translated_id, $post_type );
				$translations[ $lang ] = (int) $translated_id;
			}

			if ( count( $translations ) > 1 ) {
				pll_save_post_translations( $translations );
			}
		}
	}

	update_option( $flag, 1 );
}
add_action( 'admin_init', 'qp_holdings_sync_seeded_polylang_translations', 20 );

function qp_holdings_repair_seeded_polylang_translation_languages(): void {
	if ( ! function_exists( 'pll_set_post_language' ) || ! function_exists( 'pll_save_post_translations' ) ) {
		return;
	}

	$flag = 'qp_holdings_seeded_cpt_polylang_repair_v1';
	if ( get_option( $flag ) ) {
		return;
	}

	foreach ( array( 'qp_news', 'qp_project', 'qp_job', 'qp_investor_doc' ) as $post_type ) {
		$translations_by_source = array();
		$translated_posts = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'posts_per_page' => -1,
				'meta_query'     => array(
					array(
						'key'     => '_qp_source_slug',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		foreach ( $translated_posts as $post ) {
			$source_slug = (string) get_post_meta( $post->ID, '_qp_source_slug', true );
			$lang = (string) get_post_meta( $post->ID, '_qp_translation_lang', true );
			if ( ! in_array( $lang, array( 'en', 'zh' ), true ) ) {
				if ( str_ends_with( (string) $post->post_name, '-en' ) ) {
					$lang = 'en';
				} elseif ( str_ends_with( (string) $post->post_name, '-zh' ) ) {
					$lang = 'zh';
				}
			}
			if ( ! $source_slug || ! in_array( $lang, array( 'en', 'zh' ), true ) ) {
				continue;
			}

			pll_set_post_language( (int) $post->ID, $lang );
			update_post_meta( (int) $post->ID, '_qp_translation_lang', $lang );
			$source_post = get_page_by_path( $source_slug, OBJECT, $post_type );
			if ( $source_post instanceof WP_Post ) {
				qp_holdings_copy_terms_to_translation( (int) $source_post->ID, (int) $post->ID, $post_type );
			}
			$translations_by_source[ $source_slug ][ $lang ] = (int) $post->ID;
		}

		foreach ( $translations_by_source as $source_slug => $translations ) {
			$source_post = get_page_by_path( $source_slug, OBJECT, $post_type );
			if ( ! $source_post instanceof WP_Post ) {
				continue;
			}
			pll_set_post_language( (int) $source_post->ID, 'vi' );
			$translations['vi'] = (int) $source_post->ID;
			pll_save_post_translations( $translations );
		}
	}

	update_option( $flag, 1 );
}
add_action( 'admin_init', 'qp_holdings_repair_seeded_polylang_translation_languages', 25 );

/**
 * Backfill the `_qp_translation_lang` meta for manually-created Polylang
 * translations that were added via the editor's language box (not the seeder)
 * and therefore never received the meta. Without it the listing query can't
 * exclude them and a foreign-language card leaks into the Vietnamese listing.
 * Runs once, gated by an option flag.
 */
function qp_holdings_backfill_translation_lang_meta(): void {
	if ( ! function_exists( 'pll_get_post_language' ) ) {
		return;
	}

	$flag = 'qp_holdings_backfill_translation_lang_meta_v1';
	if ( get_option( $flag ) ) {
		return;
	}

	$default_lang = function_exists( 'pll_default_language' ) ? pll_default_language() : 'vi';

	foreach ( array( 'qp_news', 'qp_project', 'qp_job', 'qp_investor_doc' ) as $post_type ) {
		$ids = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => array( 'publish', 'draft', 'private', 'pending' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'lang'           => '',
			)
		);
		foreach ( $ids as $id ) {
			$post_lang = pll_get_post_language( (int) $id );
			if ( $post_lang && $post_lang !== $default_lang && '' === (string) get_post_meta( (int) $id, '_qp_translation_lang', true ) ) {
				update_post_meta( (int) $id, '_qp_translation_lang', $post_lang );
			}
		}
	}

	update_option( $flag, 1 );
}
add_action( 'admin_init', 'qp_holdings_backfill_translation_lang_meta', 26 );
 