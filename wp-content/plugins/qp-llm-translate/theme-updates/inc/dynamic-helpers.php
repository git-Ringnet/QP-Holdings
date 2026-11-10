<?php
/**
 * Shared helpers for dynamic content (used by renderers and seeders).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return an ACF value when ACF is active, otherwise fall back to post meta.
 */
function qp_holdings_acf_or_meta( int $post_id, string $acf_key, string $meta_key ) {
	if ( function_exists( 'get_field' ) ) {
		$value = get_field( $acf_key, $post_id );
		// `false` is also treated as empty: an ACF image/file field returns
		// false when its stored value is a theme-relative string path it can't
		// resolve to an attachment, which would otherwise shadow the usable
		// raw meta path below.
		if ( null !== $value && false !== $value && '' !== $value && array() !== $value ) {
			return $value;
		}
	}
	return get_post_meta( $post_id, $meta_key, true );
}

/**
 * Resolve an image identifier (URL string, attachment ID, or ACF image array)
 * to a usable URL. Falls back to the post's featured image.
 */
function qp_holdings_image_url( $image, int $post_id, string $size = 'large' ): string {
	if ( is_array( $image ) && ! empty( $image['url'] ) ) {
		return qp_holdings_normalize_theme_asset_url( (string) $image['url'] );
	}
	if ( is_numeric( $image ) ) {
		$seed_url = qp_holdings_seed_media_attachment_url( (int) $image );
		if ( $seed_url ) {
			return $seed_url;
		}
		$url = wp_get_attachment_image_url( (int) $image, $size );
		return $url ? $url : '';
	}
	if ( is_string( $image ) && $image ) {
		$url = qp_holdings_normalize_theme_asset_url( $image );
		if ( filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return $url;
		}
	}
	$url = get_the_post_thumbnail_url( $post_id, $size );
	return $url ? $url : '';
}

/**
 * Convert theme-relative asset paths to absolute theme URLs.
 *
 * Language-prefixed routes such as /en/news/ break relative paths like
 * `assets/site/foo.svg` by resolving them under the current page URL. This
 * helper keeps all theme assets rooted at the active theme directory.
 */
function qp_holdings_normalize_theme_asset_url( string $url ): string {
	$url = trim( $url );
	if ( '' === $url ) {
		return '';
	}

	$url = qp_holdings_normalize_seed_media_url( $url );
	if ( filter_var( $url, FILTER_VALIDATE_URL ) ) {
		return $url;
	}

	$path = preg_replace( '#^\./#', '', $url );
	$path = ltrim( (string) $path, '/' );
	if ( 0 !== strpos( $path, 'assets/' ) ) {
		return $url;
	}

	return get_template_directory_uri() . '/' . $path;
}

function qp_holdings_normalize_content_theme_asset_urls( string $html ): string {
	if ( '' === $html || false === strpos( $html, 'assets/' ) ) {
		return $html;
	}

	$html = preg_replace_callback(
		'#\b(src|href)=(["\'])(\.?/?assets/[^"\']+)\2#i',
		static function ( array $match ): string {
			return $match[1] . '=' . $match[2] . esc_url( qp_holdings_normalize_theme_asset_url( $match[3] ) ) . $match[2];
		},
		$html
	);

	$html = preg_replace_callback(
		'#\bsrcset=(["\'])([^"\']*assets/[^"\']*)\1#i',
		static function ( array $match ): string {
			$candidates = array_map( 'trim', explode( ',', $match[2] ) );
			foreach ( $candidates as &$candidate ) {
				$parts = preg_split( '/\s+/', $candidate, 2 );
				if ( ! empty( $parts[0] ) && preg_match( '#^\.?/?assets/#i', $parts[0] ) ) {
					$parts[0]  = esc_url( qp_holdings_normalize_theme_asset_url( $parts[0] ) );
					$candidate = trim( implode( ' ', $parts ) );
				}
			}
			unset( $candidate );

			return 'srcset=' . $match[1] . implode( ', ', $candidates ) . $match[1];
		},
		(string) $html
	);

	return preg_replace_callback(
		'#url\((["\']?)(\.?/?assets/[^)"\']+)\1\)#i',
		static function ( array $match ): string {
			$quote = $match[1] ?: '';
			return 'url(' . $quote . esc_url( qp_holdings_normalize_theme_asset_url( $match[2] ) ) . $quote . ')';
		},
		(string) $html
	);
}

add_filter( 'the_content', 'qp_holdings_normalize_content_theme_asset_urls', 99 );

/**
 * Convert a seed-media path or URL segment to a theme-relative path.
 */
function qp_holdings_seed_media_relative_path( string $path ): string {
	$normalized = wp_normalize_path( $path );
	$marker     = 'assets/seed-media/';
	$marker_pos = strrpos( $normalized, $marker );

	if ( false !== $marker_pos ) {
		return ltrim( substr( $normalized, $marker_pos + strlen( $marker ) ), '/\\' );
	}

	return ltrim( $normalized, '/\\' );
}

/**
 * Normalize any seeded asset URL back to the current theme URL.
 */
function qp_holdings_normalize_seed_media_url( string $url ): string {
	if ( '' === $url || ! str_contains( wp_normalize_path( $url ), 'assets/seed-media/' ) ) {
		return $url;
	}

	$relative = qp_holdings_seed_media_relative_path( $url );
	if ( '' === $relative ) {
		return $url;
	}

	return trailingslashit( get_template_directory_uri() . '/assets/seed-media' ) . $relative;
}

/**
 * Resolve a seeded theme asset attachment to its public URL.
 */
function qp_holdings_seed_media_attachment_url( int $attachment_id ): string {
	if ( $attachment_id <= 0 ) {
		return '';
	}

	$source = get_post_meta( $attachment_id, '_qp_theme_asset_source', true );
	if ( ! is_string( $source ) || '' === $source ) {
		return '';
	}

	$attached_file = get_post_meta( $attachment_id, '_wp_attached_file', true );
	if ( ! is_string( $attached_file ) || '' === $attached_file ) {
		$attached_file = basename( $source );
	}

	return qp_holdings_normalize_seed_media_url(
		trailingslashit( get_template_directory_uri() . '/assets/seed-media' ) . qp_holdings_seed_media_relative_path( (string) $attached_file )
	);
}

/**
 * Make seeded attachments return the theme asset URL rather than uploads.
 */
function qp_holdings_filter_seed_media_attachment_url( string $url, int $post_id ): string {
	$seed_url = qp_holdings_seed_media_attachment_url( $post_id );
	return $seed_url ? $seed_url : $url;
}

add_filter( 'wp_get_attachment_url', 'qp_holdings_filter_seed_media_attachment_url', 10, 2 );

/**
 * Pretty Vietnamese date — e.g. "15/04/2026".
 */
function qp_holdings_format_date( $value ): string {
	if ( ! $value ) {
		return '';
	}
	if ( is_numeric( $value ) ) {
		$ts = (int) $value;
	} else {
		$ts = strtotime( (string) $value );
	}
	if ( ! $ts ) {
		// ACF date_picker may return d/m/Y already.
		if ( is_string( $value ) && preg_match( '#^\d{2}/\d{2}/\d{4}$#', $value ) ) {
			return $value;
		}
		return is_string( $value ) ? $value : '';
	}
	return wp_date( 'd/m/Y', $ts );
}

/**
 * Year extracted from a date-ish value (used as the qp_doc_year fallback).
 */
function qp_holdings_year_from( $value ): string {
	if ( ! $value ) {
		return '';
	}
	if ( is_string( $value ) && preg_match( '#(\d{4})#', $value, $m ) ) {
		return $m[1];
	}
	$ts = is_numeric( $value ) ? (int) $value : strtotime( (string) $value );
	return $ts ? wp_date( 'Y', $ts ) : '';
}

/**
 * Human-readable file size: 1.2 MB, 320 KB, …
 */
function qp_holdings_format_filesize( $bytes ): string {
	$bytes = (int) $bytes;
	if ( $bytes <= 0 ) {
		return '';
	}
	$units = array( 'B', 'KB', 'MB', 'GB' );
	$i = 0;
	$v = $bytes;
	while ( $v >= 1024 && $i < count( $units ) - 1 ) {
		$v /= 1024;
		$i++;
	}
	return ( $v >= 10 || $i === 0 )
		? number_format( $v, 0 ) . ' ' . $units[ $i ]
		: number_format( $v, 1 ) . ' ' . $units[ $i ];
}

/**
 * Map a file URL/extension to a doc type slug (pdf, doc, xls, other).
 */
function qp_holdings_doc_type_from( $value ): string {
	if ( is_array( $value ) && ! empty( $value['url'] ) ) {
		$value = $value['url'];
	}
	if ( is_numeric( $value ) ) {
		$value = wp_get_attachment_url( (int) $value );
	}
	if ( ! is_string( $value ) || ! $value ) {
		return 'other';
	}
	$ext = strtolower( (string) pathinfo( wp_parse_url( $value, PHP_URL_PATH ) ?? '', PATHINFO_EXTENSION ) );
	if ( in_array( $ext, array( 'pdf' ), true ) ) {
		return 'pdf';
	}
	if ( in_array( $ext, array( 'doc', 'docx' ), true ) ) {
		return 'doc';
	}
	if ( in_array( $ext, array( 'xls', 'xlsx', 'csv' ), true ) ) {
		return 'xls';
	}
	return 'other';
}

/**
 * Inline icon (SVG) for a doc-type slug. Returns a string of SVG markup.
 */
function qp_holdings_doc_icon_svg( string $type ): string {
	if ( 'pdf' === $type ) {
		return '<img class="qp-doc-icon" src="' . esc_url( get_template_directory_uri() . '/assets/site/investor-doc-pdf.svg' ) . '" alt="" aria-hidden="true">';
	}

	$color = '#C9A96E';
	$paths = array(
		'pdf'   => '<path d="M8 14h8M8 18h5" stroke="' . $color . '" stroke-width="1.5" stroke-linecap="round"/><text x="12" y="11" text-anchor="middle" font-size="6" font-weight="700" fill="' . $color . '">PDF</text>',
		'doc'   => '<path d="M8 14h8M8 18h5" stroke="' . $color . '" stroke-width="1.5" stroke-linecap="round"/><text x="12" y="11" text-anchor="middle" font-size="6" font-weight="700" fill="' . $color . '">DOC</text>',
		'xls'   => '<path d="M8 14h8M8 18h5" stroke="' . $color . '" stroke-width="1.5" stroke-linecap="round"/><text x="12" y="11" text-anchor="middle" font-size="6" font-weight="700" fill="' . $color . '">XLS</text>',
		'other' => '<path d="M8 14h8M8 18h5" stroke="' . $color . '" stroke-width="1.5" stroke-linecap="round"/>',
	);
	$body = $paths[ $type ] ?? $paths['other'];
	return '<svg class="qp-doc-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M6 2h8l6 6v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" stroke="' . $color . '" stroke-width="1.5"/><path d="M14 2v6h6" stroke="' . $color . '" stroke-width="1.5"/>' . $body . '</svg>';
}

/**
 * The first taxonomy term name for a post (for badges/labels).
 */
function qp_holdings_first_term_name( int $post_id, string $taxonomy ): string {
	$terms = get_the_terms( $post_id, $taxonomy );
	if ( is_array( $terms ) && ! empty( $terms ) && ! is_wp_error( $terms ) ) {
		$term = $terms[0];
		if ( function_exists( 'qp_holdings_t' ) ) {
			$vi_slug = $term->slug;
			if ( function_exists( 'pll_get_term' ) ) {
				$vi_term_id = pll_get_term( (int) $term->term_id, 'vi' );
				if ( $vi_term_id ) {
					$vi_term = get_term( $vi_term_id, $taxonomy );
					if ( $vi_term instanceof WP_Term ) {
						$vi_slug = $vi_term->slug;
					}
				}
			}
			$trans = qp_holdings_t( $vi_slug );
			if ( $trans !== $vi_slug ) {
				return $trans;
			}
		}
		return (string) $term->name;
	}
	return '';
}

/**
	* Keep custom front-end filters away from WordPress reserved query vars.
	*
	* @return array<string, string>
	*/
function qp_holdings_filter_query_aliases(): array {
	return array(
		'year' => 'qp_year',
		'cat'  => 'qp_cat',
	);
}

/**
	* Return the preferred public query var for a front-end filter.
	*/
function qp_holdings_filter_query_var( string $query_var ): string {
	$aliases = qp_holdings_filter_query_aliases();
	return isset( $aliases[ $query_var ] ) ? $aliases[ $query_var ] : $query_var;
}

/**
	* Return all supported query-var names for a filter (preferred + legacy).
	*
	* @return string[]
	*/
function qp_holdings_filter_query_keys( string $query_var ): array {
	$preferred = qp_holdings_filter_query_var( $query_var );
	return $preferred === $query_var ? array( $query_var ) : array( $preferred, $query_var );
}

/**
	* Build a filter base URL without the active filter query vars and pagination.
	*/
function qp_holdings_filter_base_url( array $query_vars = array() ): string {
	$remove = array( 'paged' );
	foreach ( $query_vars as $query_var ) {
		$remove = array_merge( $remove, qp_holdings_filter_query_keys( $query_var ) );
	}

	return remove_query_arg( array_values( array_unique( $remove ) ) );
}

/**
	* Normalize active query args so generated links only use preferred keys.
	*
	* @return array<string, mixed>
	*/
function qp_holdings_active_query_args(): array {
	if ( empty( $_GET ) ) {
		return array();
	}

	$query_args = array();
	foreach ( $_GET as $key => $value ) {
		$query_var = qp_holdings_filter_query_var( sanitize_key( $key ) );

		if ( $query_var !== $key && isset( $_GET[ $query_var ] ) ) {
			continue;
		}

		$value = wp_unslash( $value );
		$query_args[ $query_var ] = is_array( $value )
			? array_map( 'sanitize_text_field', $value )
			: sanitize_text_field( $value );
	}

	return $query_args;
}

/**
	* Current front-end URL without the query string.
	*/
function qp_holdings_current_url_path(): string {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
	$path        = (string) wp_parse_url( $request_uri, PHP_URL_PATH );

	if ( '' === $path ) {
		$path = '/';
	}

	return home_url( $path );
}

/**
	* Translate legacy reserved filter vars (year/cat) before WP main query runs.
	*/
function qp_holdings_normalize_reserved_filter_query_vars( WP $wp ): void {
	if ( is_admin() ) {
		return;
	}

	$path = trim( (string) $wp->request, '/' );
	$map  = array();

	if ( 'nha-dau-tu' === $path || 0 === strpos( $path, 'nha-dau-tu/' ) ) {
		$map['year'] = qp_holdings_filter_query_var( 'year' );
	}

	if ( 'tintuc' === $path || 0 === strpos( $path, 'tintuc/page/' ) ) {
		$map['cat'] = qp_holdings_filter_query_var( 'cat' );
	}

	foreach ( $map as $legacy_query_var => $preferred_query_var ) {
		if ( ! isset( $_GET[ $legacy_query_var ] ) ) {
			continue;
		}

		if ( ! isset( $_GET[ $preferred_query_var ] ) ) {
			$value = wp_unslash( $_GET[ $legacy_query_var ] );
			$_GET[ $preferred_query_var ] = is_array( $value )
				? array_map( 'sanitize_text_field', $value )
				: sanitize_text_field( $value );
		}

		unset( $wp->query_vars[ $legacy_query_var ] );
	}
}
add_action( 'parse_request', 'qp_holdings_normalize_reserved_filter_query_vars', 1 );

/**
 * Helper: paginate links HTML using a clean pagination markup.
 */
function qp_holdings_pagination( WP_Query $query, string $base_url = '' ): void {
	$total = (int) $query->max_num_pages;
	if ( $total < 2 ) {
		return;
	}
	$current = max( 1, (int) ( $query->get( 'paged' ) ?: get_query_var( 'paged' ) ?: 1 ) );
	$base_url   = $base_url ? strtok( $base_url, '?' ) : qp_holdings_current_url_path();
	$query_args = qp_holdings_active_query_args();
	unset( $query_args['paged'] );

	if ( ! empty( $query_args ) ) {
		$base_url = add_query_arg( $query_args, $base_url );
	}

	$base   = $base_url . '%_%';
	$format = false === strpos( $base_url, '?' ) ? '?paged=%#%' : '&paged=%#%';

	$links = paginate_links(
		array(
			'base'      => $base,
			'format'    => $format,
			'current'   => $current,
			'total'     => $total,
			'prev_text' => '«',
			'next_text' => '»',
			'type'      => 'array',
		)
	);
	if ( ! $links ) {
		return;
	}
	echo '<nav class="qp-pagination" aria-label="Pagination">';
	foreach ( $links as $link ) {
		echo wp_kses_post( str_replace( 'page-numbers', 'qp-page', $link ) );
	}
	echo '</nav>';
}

/**
 * Filter bar: render a horizontal pill list for a taxonomy. The currently
 * active pill is determined by a $_GET query var named $query_var.
 *
 * @param string $taxonomy   Taxonomy slug.
 * @param string $query_var  Query var to read from $_GET.
 * @param string $all_label  Label for the "all" pill.
 */
function qp_holdings_taxonomy_filter( string $taxonomy, string $query_var, string $all_label = '' ): void {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return;
	}
	if ( '' === $all_label ) {
		$all_label = function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'all' ) : 'Tất cả';
	}

	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
		)
	);
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return;
	}
	$active            = qp_holdings_get_query_string( $query_var );
	$filter_query_var  = qp_holdings_filter_query_var( $query_var );

	$base = qp_holdings_filter_base_url( array( $query_var ) );
	echo '<div class="qp-filter">';
	echo '<a href="' . esc_url( $base ) . '" class="qp-filter__pill' . ( '' === $active ? ' is-active' : '' ) . '">' . esc_html( $all_label ) . '</a>';
	foreach ( $terms as $term ) {
		$url = add_query_arg( $filter_query_var, $term->slug, $base );
		$cls = $active === $term->slug ? ' is-active' : '';
		$term_name = $term->name;
		if ( function_exists( 'qp_holdings_t' ) ) {
			$vi_slug = $term->slug;
			if ( function_exists( 'pll_get_term' ) ) {
				$vi_term_id = pll_get_term( (int) $term->term_id, 'vi' );
				if ( $vi_term_id ) {
					$vi_term = get_term( $vi_term_id, $taxonomy );
					if ( $vi_term instanceof WP_Term ) {
						$vi_slug = $vi_term->slug;
					}
				}
			}
			$trans = qp_holdings_t( $vi_slug );
			if ( $trans !== $vi_slug ) {
				$term_name = $trans;
			}
		}
		echo '<a href="' . esc_url( $url ) . '" class="qp-filter__pill' . esc_attr( $cls ) . '">' . esc_html( $term_name ) . '</a>';
	}
	echo '</div>';
}

/**
 * Helpers for seed runners.
 */
function qp_holdings_seed_term_id( string $taxonomy, string $slug ): int {
	$term = get_term_by( 'slug', $slug, $taxonomy );
	return $term ? (int) $term->term_id : 0;
}

function qp_holdings_seed_assign_terms( int $post_id, array $map ): void {
	foreach ( $map as $taxonomy => $slugs ) {
		$ids = array();
		foreach ( (array) $slugs as $slug ) {
			$id = qp_holdings_seed_term_id( $taxonomy, $slug );
			if ( $id ) {
				$ids[] = $id;
			}
		}
		if ( $ids ) {
			wp_set_post_terms( $post_id, $ids, $taxonomy, false );
		}
	}
}

/**
 * Insert or update a post by slug+type. Returns the post ID.
 */
function qp_holdings_seed_upsert_post( array $args, bool $force = false ): int {
	$slug = $args['post_name'] ?? '';
	$type = $args['post_type'] ?? 'post';
	$existing = $slug ? get_page_by_path( $slug, OBJECT, $type ) : null;
	if ( $existing instanceof WP_Post ) {
		if ( ! $force ) {
			return (int) $existing->ID;
		}
		$args['ID'] = (int) $existing->ID;
	}
	if ( empty( $args['post_status'] ) ) {
		$args['post_status'] = 'publish';
	}
	$id = wp_insert_post( $args, true );
	return is_wp_error( $id ) ? 0 : (int) $id;
}

/**
 * Resolve the company capability-profile ("Hồ sơ năng lực") URL.
 *
 * The PDF/link is managed via ACF on the About (Giới thiệu) page
 * (`capability_profile_file` / `capability_profile_url`). This helper finds the
 * About page(s) and returns the first non-empty value so both the About page
 * CTA and the footer link can share one source of truth. Returns '' when no
 * file/link has been set yet (footer then hides the link).
 *
 * Memoized per request.
 */
function qp_holdings_capability_profile_url(): string {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}

	$cache = '';
	if ( ! function_exists( 'get_field' ) ) {
		return $cache;
	}

	// All pages using the About template (covers VI + EN/ZH translations).
	$about_pages = get_posts(
		array(
			'post_type'        => 'page',
			'post_status'      => 'publish',
			'posts_per_page'   => -1,
			'fields'           => 'ids',
			'lang'             => '',
			'suppress_filters' => false,
			'meta_query'       => array(
				array(
					'key'     => '_wp_page_template',
					'value'   => 'page-gioi-thieu.php',
					'compare' => '=',
				),
			),
		)
	);

	foreach ( $about_pages as $about_id ) {
		$about_id = (int) $about_id;

		$file = get_field( 'capability_profile_file', $about_id );
		if ( is_array( $file ) && ! empty( $file['url'] ) ) {
			$cache = (string) $file['url'];
			return $cache;
		}
		if ( is_string( $file ) && '' !== $file ) {
			$cache = $file;
			return $cache;
		}

		$link = get_field( 'capability_profile_url', $about_id );
		if ( is_string( $link ) && '' !== $link ) {
			$cache = $link;
			return $cache;
		}
	}

	return $cache;
}

/**
 * Map of dynamic Header menu slot translation group: array( 'vi' => ID, 'en' => ID, 'zh' => ID ).
 *
 * Resolves the primary flagged page (Vietnamese anchor) and its assigned
 * English & Chinese target pages from ACF `header_menu_target_{en,zh}`
 * (with fallback to Polylang translations if present).
 *
 * @return array{vi:int,en:int,zh:int}
 */
function qp_holdings_header_menu_slot_group_map(): array {
	static $group = null;
	if ( null !== $group ) {
		return $group;
	}

	$group = array(
		'vi' => 0,
		'en' => 0,
		'zh' => 0,
	);

	$flagged = array_map(
		'intval',
		get_posts(
			array(
				'post_type'              => 'page',
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'orderby'                => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ),
				'meta_key'               => 'header_menu_slot',
				'meta_value'             => '1',
				'fields'                 => 'ids',
				'lang'                   => '',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
			)
		)
	);

	if ( empty( $flagged ) ) {
		return $group;
	}

	$anchor_id   = (int) $flagged[0];
	$group['vi'] = $anchor_id;

	$en_id = function_exists( 'get_field' ) ? (int) get_field( 'header_menu_target_en', $anchor_id ) : 0;
	if ( ! $en_id ) {
		$en_id = (int) get_post_meta( $anchor_id, 'header_menu_target_en', true );
	}
	if ( ! $en_id && function_exists( 'pll_get_post' ) ) {
		$en_id = (int) pll_get_post( $anchor_id, 'en' );
	}
	$group['en'] = $en_id;

	$zh_id = function_exists( 'get_field' ) ? (int) get_field( 'header_menu_target_zh', $anchor_id ) : 0;
	if ( ! $zh_id ) {
		$zh_id = (int) get_post_meta( $anchor_id, 'header_menu_target_zh', true );
	}
	if ( ! $zh_id && function_exists( 'pll_get_post' ) ) {
		$zh_id = (int) pll_get_post( $anchor_id, 'zh' );
	}
	$group['zh'] = $zh_id;

	return $group;
}

/**
 * Resolve the dynamic Header menu slot page according to current language.
 *
 * For Vietnamese, points to the flagged anchor page.
 * For English, points to the assigned English page (header_menu_target_en).
 * For Chinese, points to the assigned Chinese page (header_menu_target_zh).
 * The label is the optional `header_menu_label` or the target page's title.
 *
 * @return array{url:string,label:string,ids:int[]}|array{}
 */
function qp_holdings_header_menu_slot(): array {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}

	$cache = array();
	$group = qp_holdings_header_menu_slot_group_map();
	if ( empty( $group['vi'] ) ) {
		return $cache;
	}

	$lang      = function_exists( 'qp_holdings_current_language' ) ? qp_holdings_current_language() : 'vi';
	$anchor_id = (int) $group['vi'];

	$page_id = 0;
	if ( 'en' === $lang && ! empty( $group['en'] ) && 'publish' === get_post_status( $group['en'] ) ) {
		$page_id = (int) $group['en'];
	} elseif ( 'zh' === $lang && ! empty( $group['zh'] ) && 'publish' === get_post_status( $group['zh'] ) ) {
		$page_id = (int) $group['zh'];
	} else {
		$page_id = $anchor_id;
	}

	$label = function_exists( 'get_field' ) ? (string) get_field( 'header_menu_label', $page_id ) : '';
	if ( '' === trim( $label ) && $page_id !== $anchor_id && function_exists( 'get_field' ) ) {
		$label = (string) get_field( 'header_menu_label', $anchor_id );
	}
	if ( '' === trim( $label ) ) {
		$label = (string) get_the_title( $page_id );
	}

	$all_ids = array_values( array_unique( array_filter( array( $group['vi'], $group['en'], $group['zh'] ) ) ) );
	if ( function_exists( 'pll_get_post' ) ) {
		foreach ( array( 'vi', 'en', 'zh' ) as $alt_lang ) {
			$alt_id = (int) pll_get_post( $anchor_id, $alt_lang );
			if ( $alt_id && ! in_array( $alt_id, $all_ids, true ) ) {
				$all_ids[] = $alt_id;
			}
		}
	}

	$url = (string) get_permalink( $page_id );
	if ( 'en' === $lang && 0 !== strpos( (string) wp_parse_url( $url, PHP_URL_PATH ), '/en/' ) ) {
		$slug = (string) get_post_field( 'post_name', $page_id );
		if ( $slug ) {
			$url = home_url( '/en/' . $slug . '/' );
		}
	} elseif ( 'zh' === $lang && 0 !== strpos( (string) wp_parse_url( $url, PHP_URL_PATH ), '/zh/' ) ) {
		$slug = (string) get_post_field( 'post_name', $page_id );
		if ( $slug ) {
			$url = home_url( '/zh/' . $slug . '/' );
		}
	}

	$cache = array(
		'url'   => $url,
		'label' => $label,
		'ids'   => $all_ids,
	);

	return $cache;
}

/**
 * Whether the current request is the Header menu slot page (any language).
 */
function qp_holdings_header_menu_slot_active(): bool {
	$slot = qp_holdings_header_menu_slot();
	if ( empty( $slot['ids'] ) || ! is_singular( 'page' ) ) {
		return false;
	}

	return in_array( (int) get_queried_object_id(), $slot['ids'], true );
}

/**
 * Read-only / safe access to $_GET sanitized string.
 */
function qp_holdings_get_query_string( string $key, string $default = '' ): string {
	foreach ( qp_holdings_filter_query_keys( $key ) as $query_key ) {
		if ( ! isset( $_GET[ $query_key ] ) ) {
			continue;
		}

		return sanitize_text_field( wp_unslash( $_GET[ $query_key ] ) );
	}

	return $default;
}
