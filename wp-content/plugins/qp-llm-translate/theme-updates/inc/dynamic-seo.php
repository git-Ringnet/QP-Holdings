<?php
/**
 * Per-page SEO controls + structured data (JSON-LD).
 *
 * This module adds three things on top of the theme's SEO fallback that lives
 * in functions.php (qp_holdings_build_seo_fallback_markup):
 *
 *  1. A simple, Vietnamese-labelled "Tối ưu SEO" meta box on every page/post/CPT
 *     so the client can override the SEO title (thẻ <title>), meta description
 *     and meta keywords without touching code or learning a plugin. Stored as
 *     post meta:
 *       _qp_seo_title        => string  (overrides <title> + og:title)
 *       _qp_seo_description   => string  (overrides <meta name="description">)
 *       _qp_seo_keywords      => string  (comma separated, <meta name="keywords">)
 *     Empty fields fall back to the theme defaults (and the default keyword list
 *     seeded from the marketing keyword sheet), so SEO works out-of-the-box.
 *
 *  2. Default keyword lists per page (from the List_keywords sheet) that are
 *     emitted automatically as <meta name="keywords"> even before the client
 *     edits anything.
 *
 *  3. A richer JSON-LD @graph: Organization + Corporation (tickerSymbol HKT),
 *     WebSite, BreadcrumbList and WebPage/NewsArticle. functions.php calls
 *     qp_holdings_seo_schema_graph() when this module is present.
 *
 * @package QP_Holdings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================================
 * 1. Company facts (single source of truth for schema + contact data)
 * ====================================================================== */

/**
 * Canonical company facts used by the Organization/Corporation schema.
 *
 * Override any value with the `qp_holdings_company_info` filter (e.g. to add a
 * verified stock-exchange URL or LEI once available).
 *
 * @return array<string,mixed>
 */
function qp_holdings_company_info(): array {
	return apply_filters(
		'qp_holdings_company_info',
		array(
			'name'         => 'QP Holdings',
			'legal_name'   => 'Công ty Cổ phần QP Holdings',
			'alternate'    => 'CTCP QP Holdings',
			'ticker'       => 'HKT',
			'email'        => 'info@qpholdings.vn',
			'telephone'    => '19000066',
			'street'       => 'Tầng L16, Tòa nhà Vietcombank, Số 5 Công Trường Mê Linh, Phường Sài Gòn',
			'locality'     => 'Thành phố Hồ Chí Minh',
			'country'      => 'VN',
			'same_as'      => function_exists( 'qp_holdings_social_same_as_urls' )
				? qp_holdings_social_same_as_urls()
				: array(
					'https://www.facebook.com/qpholdings.vn',
					'https://www.tiktok.com/@qp.holdings',
					'https://www.instagram.com/qpholdings.vn/',
				),
		)
	);
}

/* =========================================================================
 * 2. Default keyword lists per page (source: List_keywords sheet)
 * ====================================================================== */

/**
 * Default meta keywords keyed by page key (see qp_holdings_page_definitions()).
 * Combines the "Keyword Chính" + "Keyword Phụ" columns from the sheet.
 *
 * @return array<string,string>
 */
function qp_holdings_seo_default_keyword_map(): array {
	return array(
		'home'              => 'QP Holdings, công ty QP Holdings, QP Holdings là gì, QP Holdings Việt Nam, QP Holdings mã HKT, HKT',
		'about'             => 'QP Holdings là gì, công ty cổ phần QP Holdings, ban lãnh đạo QP Holdings, giới thiệu QP Holdings, lịch sử QP Holdings, QP Holdings Bình Dương',
		'projects'          => 'dự án bất động sản QP Holdings, khu dân cư QP Holdings, khu công nghiệp QP Holdings, An Bình 7 QP Holdings, dự án BĐS nghìn tỷ TP.HCM, dự án BĐS TP.HCM, dự án BĐS Bình Dương, căn hộ cao tầng QP, văn phòng thương mại QP Holdings, cụm công nghiệp, nhà xưởng cho thuê, hạ tầng logistics, khu công nghiệp An Bình, đầu tư BĐS TP.HCM 2026, M&A bất động sản',
		'news'              => 'tin tức QP Holdings, tin HKT mới nhất, QP Holdings news, M&A bất động sản 2026, mua bán sáp nhập BĐS Việt Nam, thâu tóm dự án BĐS, VN-Index thị trường chứng khoán, chứng khoán Việt Nam 2026, QP Holdings đổi tên công ty, HKT đổi tên, rebranding QP Holdings 2026',
		'careers'           => 'tuyển dụng QP Holdings, việc làm QP Holdings, tuyển dụng HKT 2026, việc làm bất động sản TP.HCM, tuyển dụng ngành BĐS, nhân sự BĐS 2026, tuyển dụng khu công nghiệp, việc làm quản lý KCN, tuyển nhân sự logistics',
		'contact'           => 'liên hệ QP Holdings, hotline QP Holdings, số điện thoại QP Holdings, văn phòng QP Holdings TP.HCM, địa chỉ QP Holdings, email QP Holdings',
		'investor'          => 'quan hệ nhà đầu tư HKT, investor relations QP Holdings, cổ đông HKT, thông tin doanh nghiệp niêm yết HKT',
		'stock'             => 'thông tin cổ phiếu HKT, giá cổ phiếu HKT hôm nay, lịch sử giá HKT',
		'agm'               => 'ĐHCĐ QP Holdings 2026, đại hội đồng cổ đông HKT, nghị quyết ĐHCĐ 2026',
		'financial_reports' => 'báo cáo tài chính QP Holdings, BCTC HKT, kết quả kinh doanh HKT 2025 2026',
		'annual_reports'    => 'báo cáo thường niên HKT, annual report QP Holdings, báo cáo quản trị HKT',
		'disclosures'       => 'công bố thông tin HKT, thông báo cổ đông, thông tin doanh nghiệp niêm yết HKT',
		'governance'        => 'quản trị công ty HKT, corporate governance QP Holdings, điều lệ công ty HKT',
		'governance_reports' => 'báo cáo quản trị công ty HKT, báo cáo quản trị QP Holdings',
	);
}

/**
 * Default keywords for a given page key (filterable).
 */
function qp_holdings_seo_default_keywords( string $key ): string {
	$map = qp_holdings_seo_default_keyword_map();
	$value = $map[ $key ] ?? '';

	return (string) apply_filters( 'qp_holdings_seo_default_keywords', $value, $key );
}

/* =========================================================================
 * 3. Page-key resolution for the admin editor (no main query available)
 * ====================================================================== */

/**
 * Resolve the page key for a post being edited in wp-admin.
 *
 * Mirrors qp_holdings_current_page_key() but works from a WP_Post instead of
 * the front-end query conditionals.
 */
function qp_holdings_seo_post_page_key( WP_Post $post ): string {
	switch ( $post->post_type ) {
		case 'qp_news':
			return 'news';
		case 'qp_project':
			return 'projects';
		case 'qp_job':
			return 'careers';
	}

	if ( 'page' !== $post->post_type || ! function_exists( 'qp_holdings_page_definitions' ) ) {
		return '';
	}

	$defs        = qp_holdings_page_definitions();
	$slug        = (string) $post->post_name;
	$parent_id   = (int) $post->post_parent;
	$parent_slug = $parent_id ? (string) get_post_field( 'post_name', $parent_id ) : '';

	foreach ( $defs as $key => $def ) {
		foreach ( array( 'vi', 'en', 'zh' ) as $lang ) {
			$def_slug          = $def[ $lang ]['internal_slug'] ?? ( $def[ $lang ]['slug'] ?? '' );
			$def_slug_external = $def[ $lang ]['slug'] ?? '';
			if ( $slug !== $def_slug && $slug !== $def_slug_external ) {
				continue;
			}
			if ( empty( $def['parent'] ) ) {
				return $key;
			}
			$parent_key      = $def['parent'];
			$parent_def_slug = $defs[ $parent_key ][ $lang ]['internal_slug'] ?? ( $defs[ $parent_key ][ $lang ]['slug'] ?? '' );
			if ( $parent_slug === $parent_def_slug || $parent_slug === ( $defs[ $parent_key ][ $lang ]['slug'] ?? '' ) ) {
				return $key;
			}
		}
	}

	return '';
}

/**
 * Best-effort language of a post for choosing default-description language in
 * the admin: Polylang first, then the page-definition slug, then 'vi'.
 */
function qp_holdings_seo_post_language( WP_Post $post ): string {
	if ( function_exists( 'pll_get_post_language' ) ) {
		$lang = (string) pll_get_post_language( $post->ID, 'slug' );
		if ( $lang ) {
			return $lang;
		}
	}

	if ( function_exists( 'qp_holdings_page_definitions' ) ) {
		$defs = qp_holdings_page_definitions();
		$slug = (string) $post->post_name;
		foreach ( $defs as $def ) {
			foreach ( array( 'en', 'zh', 'vi' ) as $lang ) {
				$def_slug = $def[ $lang ]['internal_slug'] ?? ( $def[ $lang ]['slug'] ?? '' );
				if ( $slug && ( $slug === $def_slug || $slug === ( $def[ $lang ]['slug'] ?? '' ) ) ) {
					return $lang;
				}
			}
		}
	}

	return 'vi';
}

/* =========================================================================
 * 4. The "Tối ưu SEO" meta box
 * ====================================================================== */

/**
 * Post types that get the SEO meta box.
 *
 * @return string[]
 */
function qp_holdings_seo_meta_box_post_types(): array {
	return (array) apply_filters(
		'qp_holdings_seo_meta_box_post_types',
		array( 'page', 'post', 'qp_news', 'qp_project', 'qp_job' )
	);
}

function qp_holdings_seo_register_meta_box(): void {
	foreach ( qp_holdings_seo_meta_box_post_types() as $type ) {
		add_meta_box(
			'qp_seo_box',
			'Tối ưu SEO (Google & mạng xã hội)',
			'qp_holdings_seo_render_meta_box',
			$type,
			'normal',
			'high'
		);
	}
}
add_action( 'add_meta_boxes', 'qp_holdings_seo_register_meta_box' );

/**
 * Render the SEO meta box. Placeholders show the live default so the editor can
 * see exactly what will be used when a field is left blank.
 */
function qp_holdings_seo_render_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'qp_seo_save', 'qp_seo_nonce' );

	$seo_title   = (string) get_post_meta( $post->ID, '_qp_seo_title', true );
	$seo_desc    = (string) get_post_meta( $post->ID, '_qp_seo_description', true );
	$seo_kw      = (string) get_post_meta( $post->ID, '_qp_seo_keywords', true );

	$page_key    = qp_holdings_seo_post_page_key( $post );
	$lang        = qp_holdings_seo_post_language( $post );

	$title_ph    = $post->post_title ? ( $post->post_title . ' - ' . get_bloginfo( 'name' ) ) : get_bloginfo( 'name' );
	$desc_ph     = qp_holdings_seo_default_description_for_post( $post, $page_key, $lang );
	$kw_ph        = $seo_kw ? $seo_kw : qp_holdings_seo_default_keywords( $page_key );
	if ( '' === $kw_ph ) {
		$kw_ph = 'Ví dụ: QP Holdings, bất động sản, HKT';
	}
	?>
	<style>
		.qp-seo-field{margin:0 0 18px}
		.qp-seo-field:last-child{margin-bottom:0}
		.qp-seo-field>label{display:block;font-weight:600;font-size:13px;color:#1d2327;margin-bottom:4px}
		.qp-seo-field input,.qp-seo-field textarea{width:100%}
		.qp-seo-help{margin:4px 0 0;color:#646970;font-size:12px}
		.qp-seo-count{font-weight:600}
		.qp-seo-count.over{color:#b32d2e}
		.qp-seo-intro{margin:0 0 16px;padding:10px 12px;background:#f0f6fc;border-left:4px solid #2271b1;color:#1d2327;font-size:13px;line-height:1.5}
	</style>
	<p class="qp-seo-intro">Để trống bất kỳ ô nào sẽ tự dùng nội dung mặc định mà website đã thiết lập sẵn (hiển thị mờ trong ô). Chỉ cần nhập khi bạn muốn thay đổi cho riêng trang này.</p>

	<div class="qp-seo-field">
		<label for="qp-seo-title">Tiêu đề SEO <span style="font-weight:400;color:#646970">(thẻ tiêu đề hiển thị trên Google &amp; tab trình duyệt)</span></label>
		<input type="text" id="qp-seo-title" name="qp_seo_title" value="<?php echo esc_attr( $seo_title ); ?>" placeholder="<?php echo esc_attr( $title_ph ); ?>" maxlength="120">
		<p class="qp-seo-help">Khuyến nghị 50–60 ký tự. Đang dùng: <span class="qp-seo-count" data-for="qp-seo-title" data-rec="60">0</span> ký tự.</p>
	</div>

	<div class="qp-seo-field">
		<label for="qp-seo-desc">Mô tả meta <span style="font-weight:400;color:#646970">(đoạn mô tả ngắn dưới tiêu đề trên Google)</span></label>
		<textarea id="qp-seo-desc" name="qp_seo_description" rows="3" placeholder="<?php echo esc_attr( $desc_ph ); ?>"><?php echo esc_textarea( $seo_desc ); ?></textarea>
		<p class="qp-seo-help">Khuyến nghị 120–160 ký tự. Đang dùng: <span class="qp-seo-count" data-for="qp-seo-desc" data-rec="160">0</span> ký tự.</p>
	</div>

	<div class="qp-seo-field">
		<label for="qp-seo-kw">Từ khóa SEO <span style="font-weight:400;color:#646970">(cách nhau bằng dấu phẩy)</span></label>
		<textarea id="qp-seo-kw" name="qp_seo_keywords" rows="2" placeholder="<?php echo esc_attr( $kw_ph ); ?>"><?php echo esc_textarea( $seo_kw ); ?></textarea>
		<p class="qp-seo-help">Bộ từ khóa mặc định đã được cài sẵn cho trang này. Để trống sẽ tự dùng danh sách mặc định ở trên (hiển thị mờ).</p>
	</div>

	<script>
	(function(){
		document.querySelectorAll('.qp-seo-count').forEach(function(span){
			var field = document.getElementById(span.getAttribute('data-for'));
			var rec   = parseInt(span.getAttribute('data-rec'), 10) || 0;
			if(!field){ return; }
			function update(){
				var len = field.value.length;
				span.textContent = len;
				if(rec && len > rec){ span.classList.add('over'); } else { span.classList.remove('over'); }
			}
			field.addEventListener('input', update);
			update();
		});
	})();
	</script>
	<?php
}

/**
 * Compute the default meta description for the post being edited (admin context).
 * Reuses the front-end description map keyed by page slug.
 */
function qp_holdings_seo_default_description_for_post( WP_Post $post, string $page_key, string $lang ): string {
	if ( function_exists( 'qp_holdings_seo_page_descriptions' ) ) {
		$map  = qp_holdings_seo_page_descriptions();
		$slug = (string) $post->post_name;
		if ( isset( $map[ $slug ] ) ) {
			return $map[ $slug ][ $lang ] ?? ( $map[ $slug ]['vi'] ?? '' );
		}
	}

	if ( has_excerpt( $post ) ) {
		return function_exists( 'qp_holdings_clean_meta_text' )
			? qp_holdings_clean_meta_text( (string) get_the_excerpt( $post ) )
			: wp_strip_all_tags( (string) get_the_excerpt( $post ) );
	}

	return 'Để trống sẽ tự dùng mô tả mặc định của website cho trang này.';
}

/**
 * Persist the SEO meta-box fields.
 */
function qp_holdings_seo_save_meta_box( int $post_id ): void {
	if ( ! isset( $_POST['qp_seo_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['qp_seo_nonce'] ) ), 'qp_seo_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$fields = array(
		'_qp_seo_title'       => array( 'qp_seo_title', 'text' ),
		'_qp_seo_description' => array( 'qp_seo_description', 'textarea' ),
		'_qp_seo_keywords'    => array( 'qp_seo_keywords', 'textarea' ),
	);

	foreach ( $fields as $meta_key => $config ) {
		list( $field, $type ) = $config;
		$raw   = isset( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : '';
		$value = 'textarea' === $type ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
		$value = trim( (string) $value );

		if ( '' !== $value ) {
			update_post_meta( $post_id, $meta_key, $value );
		} else {
			delete_post_meta( $post_id, $meta_key );
		}
	}
}
add_action( 'save_post', 'qp_holdings_seo_save_meta_box' );

/* =========================================================================
 * 5. Front-end: SEO title override + effective keyword resolution
 * ====================================================================== */

/**
 * Effective per-post SEO title override (empty when none set).
 */
function qp_holdings_seo_title_override(): string {
	if ( ! is_singular() ) {
		return '';
	}
	return trim( (string) get_post_meta( get_queried_object_id(), '_qp_seo_title', true ) );
}

/**
 * Short-circuit the document title when the page has a custom SEO title.
 * Runs late so the manual override can beat plugin-provided titles when the
 * theme owns frontend SEO.
 */
function qp_holdings_seo_pre_document_title( $title ) {
	$override = qp_holdings_seo_title_override();
	if ( '' !== $override ) {
		return $override;
	}

	// Returning empty lets WordPress build the normal document title from parts,
	// after any plugin-level pre-title override (Rank Math included) has run.
	return qp_holdings_theme_owns_frontend_seo() ? '' : $title;
}
add_filter( 'pre_get_document_title', 'qp_holdings_seo_pre_document_title', 999 );

/**
 * Effective meta keywords for the current request: per-post override, then the
 * page-key default list, then empty.
 */
function qp_holdings_seo_effective_keywords(): string {
	if ( is_singular() ) {
		$override = trim( (string) get_post_meta( get_queried_object_id(), '_qp_seo_keywords', true ) );
		if ( '' !== $override ) {
			return $override;
		}
	}

	$key = function_exists( 'qp_holdings_current_page_key' ) ? qp_holdings_current_page_key() : '';
	return $key ? qp_holdings_seo_default_keywords( $key ) : '';
}

/**
 * The theme is the authoritative frontend SEO layer. Rank Math can remain active
 * for admin tools, sitemaps and robots.txt, but must not print duplicate/conflicting
 * title, meta, Open Graph or schema tags.
 */
function qp_holdings_theme_owns_frontend_seo(): bool {
	return (bool) apply_filters( 'qp_holdings_theme_owns_frontend_seo', true );
}

/**
 * Suppress Rank Math's frontend <head> output when the theme owns SEO.
 *
 * Rank Math prints all head tags via the internal `rank_math/head` action. Clearing
 * that action at `wp_head` priority 0 removes its title, description, canonical,
 * OG/Twitter and schema output, while leaving non-head features such as sitemaps
 * available. Rank Math also moves WordPress' title renderer into `rank_math/head`,
 * so we add it back to `wp_head` to keep a normal <title> tag.
 */
function qp_holdings_suppress_rank_math_frontend_head(): void {
	if ( ! qp_holdings_theme_owns_frontend_seo() || ! qp_holdings_seo_plugin_active() ) {
		return;
	}

	remove_all_actions( 'rank_math/head' );

	if ( ! has_action( 'wp_head', '_wp_render_title_tag' ) ) {
		add_action( 'wp_head', '_wp_render_title_tag', 1 );
	}
}
add_action( 'wp_head', 'qp_holdings_suppress_rank_math_frontend_head', 0 );

/* =========================================================================
 * 6. JSON-LD @graph builder
 * ====================================================================== */

/**
 * Localised "Home" label for the breadcrumb root.
 */
function qp_holdings_seo_home_label( string $lang ): string {
	$labels = array( 'vi' => 'Trang chủ', 'en' => 'Home', 'zh' => '首页' );
	return $labels[ $lang ] ?? $labels['vi'];
}

/**
 * Build the breadcrumb item list for the current request.
 * Returns an empty array on the home page (a single-item trail is pointless).
 *
 * @return array<int,array{0:string,1:string}> [url, name] pairs.
 */
function qp_holdings_seo_breadcrumb_items( string $lang ): array {
	if ( is_front_page() || is_home() ) {
		return array();
	}

	$items = array( array( home_url( '/' ), qp_holdings_seo_home_label( $lang ) ) );
	$defs  = function_exists( 'qp_holdings_page_definitions' ) ? qp_holdings_page_definitions() : array();

	$listing = static function ( string $key ) use ( $defs, $lang ): array {
		$slug = function_exists( 'qp_holdings_page_storage_slug' ) ? qp_holdings_page_storage_slug( $key, $lang ) : '';
		$page = $slug ? get_page_by_path( $slug ) : null;
		$url  = $page ? get_permalink( $page ) : home_url( '/' );
		$name = $defs[ $key ][ $lang ]['title'] ?? ( $defs[ $key ]['vi']['title'] ?? '' );
		return array( $url, $name );
	};

	if ( is_singular() ) {
		$post = get_post();
		if ( ! $post instanceof WP_Post ) {
			return array();
		}

		switch ( $post->post_type ) {
			case 'qp_news':
				$items[] = $listing( 'news' );
				break;
			case 'qp_project':
				$items[] = $listing( 'projects' );
				break;
			case 'qp_job':
				$items[] = $listing( 'careers' );
				break;
			case 'page':
				foreach ( array_reverse( get_post_ancestors( $post->ID ) ) as $ancestor_id ) {
					$items[] = array( get_permalink( $ancestor_id ), get_the_title( $ancestor_id ) );
				}
				break;
		}

		$items[] = array( get_permalink( $post ), get_the_title( $post ) );

		return $items;
	}

	// Archives, taxonomies, search, etc.
	$items[] = array( qp_holdings_canonical_url(), wp_strip_all_tags( wp_get_document_title() ) );

	return $items;
}

/**
 * Build the full JSON-LD @graph for the current request.
 *
 * @param array{title:string,description:string,canonical:string,image:string,is_article:bool} $ctx
 * @return array<int,array<string,mixed>>
 */
function qp_holdings_seo_schema_graph( array $ctx ): array {
	$info       = qp_holdings_company_info();
	// JSON-LD values must be plain text; wp_get_document_title() returns HTML entities.
	$title      = html_entity_decode( (string) $ctx['title'], ENT_QUOTES, 'UTF-8' );
	$ctx['title'] = $title;
	$locale     = function_exists( 'qp_holdings_language_locale' ) ? qp_holdings_language_locale( 'schema' ) : 'vi-VN';
	$lang       = function_exists( 'qp_holdings_current_language' ) ? qp_holdings_current_language() : 'vi';
	$logo       = ( function_exists( 'has_site_icon' ) && has_site_icon() )
		? get_site_icon_url( 192 )
		: get_template_directory_uri() . '/assets/index_files/favicon-192.png';
	$org_id     = home_url( '/#organization' );
	$website_id = home_url( '/#website' );
	$logo_id    = home_url( '/#logo' );

	// --- Organization + Corporation (carries the HKT ticker) ----------------
	$organization = array(
		'@type'         => array( 'Organization', 'Corporation' ),
		'@id'           => $org_id,
		'name'          => $info['name'],
		'legalName'     => $info['legal_name'],
		'alternateName' => $info['alternate'],
		'url'           => home_url( '/' ),
		'logo'          => array(
			'@type'      => 'ImageObject',
			'@id'        => $logo_id,
			'url'        => $logo,
			'contentUrl' => $logo,
			'caption'    => $info['name'],
		),
		'image'         => array( '@id' => $logo_id ),
		'tickerSymbol'  => $info['ticker'],
		'email'         => $info['email'],
		'telephone'     => $info['telephone'],
		'address'       => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $info['street'],
			'addressLocality' => $info['locality'],
			'addressCountry'  => $info['country'],
		),
		'contactPoint'  => array(
			'@type'             => 'ContactPoint',
			'telephone'         => $info['telephone'],
			'email'             => $info['email'],
			'contactType'       => 'customer service',
			'areaServed'        => $info['country'],
			'availableLanguage' => array( 'Vietnamese', 'English', 'Chinese' ),
		),
		'sameAs'        => array_values( (array) $info['same_as'] ),
	);

	/** Allow per-site extension (verified exchange URL, foundingDate, LEI, ...). */
	$organization = apply_filters( 'qp_holdings_organization_schema', $organization, $info );

	// --- WebSite ------------------------------------------------------------
	$website = array(
		'@type'       => 'WebSite',
		'@id'         => $website_id,
		'url'         => home_url( '/' ),
		'name'        => get_bloginfo( 'name' ),
		'description' => $ctx['description'],
		'publisher'   => array( '@id' => $org_id ),
		'inLanguage'  => $locale,
	);

	$graph = array( $organization, $website );

	// --- BreadcrumbList -----------------------------------------------------
	$breadcrumb_items = qp_holdings_seo_breadcrumb_items( $lang );
	$has_breadcrumb   = count( $breadcrumb_items ) > 1;
	if ( $has_breadcrumb ) {
		$list = array();
		foreach ( $breadcrumb_items as $position => $item ) {
			$list[] = array(
				'@type'    => 'ListItem',
				'position' => $position + 1,
				'name'     => html_entity_decode( (string) $item[1], ENT_QUOTES, 'UTF-8' ),
				'item'     => $item[0],
			);
		}
		$graph[] = array(
			'@type'           => 'BreadcrumbList',
			'@id'             => $ctx['canonical'] . '#breadcrumb',
			'itemListElement' => $list,
		);
	}

	// --- WebPage / NewsArticle ---------------------------------------------
	$is_article = ! empty( $ctx['is_article'] );
	$webpage    = array(
		'@type'       => $is_article ? 'NewsArticle' : 'WebPage',
		'@id'         => $ctx['canonical'] . '#webpage',
		'url'         => $ctx['canonical'],
		'name'        => $ctx['title'],
		'description' => $ctx['description'],
		'isPartOf'    => array( '@id' => $website_id ),
		'inLanguage'  => $locale,
	);

	if ( $has_breadcrumb ) {
		$webpage['breadcrumb'] = array( '@id' => $ctx['canonical'] . '#breadcrumb' );
	}

	if ( $is_article ) {
		$post           = get_post();
		$author_name    = $post instanceof WP_Post ? get_the_author_meta( 'display_name', (int) $post->post_author ) : '';
		$author_name    = $author_name ? $author_name : $info['name'];

		// Use the clean post title (no "– QP Holdings" suffix) for the article headline.
		if ( $post instanceof WP_Post ) {
			$post_title       = html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' );
			$webpage['name']  = $post_title;
		} else {
			$post_title = $ctx['title'];
		}

		$webpage['headline']         = $post_title;
		$webpage['datePublished']    = get_the_date( DATE_W3C );
		$webpage['dateModified']     = get_the_modified_date( DATE_W3C );
		$webpage['image']            = array( $ctx['image'] );
		$webpage['mainEntityOfPage'] = array( '@id' => $ctx['canonical'] . '#webpage' );
		$webpage['author']           = array(
			'@type' => 'Organization',
			'@id'   => $org_id,
			'name'  => $author_name === $info['name'] ? $info['name'] : $author_name,
		);
		$webpage['publisher'] = array( '@id' => $org_id );

		$section = $post instanceof WP_Post ? wp_strip_all_tags( (string) get_the_term_list( $post->ID, 'qp_news_category', '', ', ' ) ) : '';
		if ( $section ) {
			$webpage['articleSection'] = $section;
		}
	}

	$graph[] = $webpage;

	return (array) apply_filters( 'qp_holdings_seo_schema_graph', $graph, $ctx );
}
