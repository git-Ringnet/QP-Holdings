<?php
/**
 * Import Investor Documents from qpxanh.com.vn → WordPress.
 *
 * Crawls 5 QHCD category listing pages (with pagination), resolves
 * detail-page links into individual PDF files, downloads them into
 * the WordPress Media Library, and creates qp_investor_doc posts.
 *
 * VIP: lang = vi only — English files are skipped.
 *
 * Usage (inside wpcli container):
 *   wp --allow-root eval-file bin/import-investor-docs.php -- --dry-run
 *   wp --allow-root eval-file bin/import-investor-docs.php -- --import
 */

defined( 'ABSPATH' ) || exit;

/* ── Helpers (defined inline so the eval-file is self-contained) ── */

/**
 * Assign taxonomy terms to a post.
 */
function qp_iid_assign_terms( int $post_id, array $map, bool $append = false ): void {
	foreach ( $map as $taxonomy => $slugs ) {
		$ids = array();
		foreach ( (array) $slugs as $slug ) {
			$term = get_term_by( 'slug', $slug, $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				$ids[] = (int) $term->term_id;
			}
		}
		if ( $ids ) {
			wp_set_post_terms( $post_id, $ids, $taxonomy, $append );
		}
	}
}

/**
 * Ensure a year term exists in qp_doc_year.
 */
function qp_iid_ensure_year_term( string $year ): void {
	if ( ! term_exists( $year, 'qp_doc_year' ) ) {
		wp_insert_term( $year, 'qp_doc_year', array( 'slug' => $year ) );
	}
}

/* ── Parser helpers ── */

/**
 * Parse a Vietnamese-format date string to Y-m-d.
 */
function qp_iid_parse_date( string $raw ): string {
	if ( preg_match( '/(\d{2})\/(\d{2})\/(\d{4})/', $raw, $m ) ) {
		return $m[3] . '-' . $m[2] . '-' . $m[1];
	}
	return date( 'Y-m-d' );
}

/**
 * Determine file language from filename.
 */
function qp_iid_file_language( string $filename ): string {
	$name = strtoupper( $filename );
	$vi   = (bool) preg_match( '/(^|[_\-.])(?:VI|VIE|VN)(?:[_\-.]|$)/', $name );
	$en   = (bool) preg_match( '/(^|[_\-.])(?:EN|ENG)(?:[_\-.]|$)|ANNUAL[-_. ]?REPORT|FINANCIAL[-_. ]?STATEMENT|CONSOLIDATED[-_. ]?FINANCIAL|SEPARATE[-_. ]?FINANCIAL|SEPERATE[-_. ]?FINANCIAL|EXPLANATION|DECISION[-_. ]?OF[-_. ]?HNX|REPORT[-_. ]?ON[-_. ]?CORPORATE[-_. ]?GOVERNANCE/', $name );

	if ( $vi && ! $en ) {
		return 'vi';
	}
	if ( ! $vi && $en ) {
		return 'en';
	}
	if ( $vi && $en ) {
		return 'bilingual';
	}
	return 'vi';
}

/**
 * Is this file in Vietnamese (or bilingual)?
 */
function qp_iid_is_vietnamese( string $filename ): bool {
	return 'en' !== qp_iid_file_language( $filename );
}

/**
 * Clean up a filename or link text into a human-readable title.
 */
function qp_iid_format_title( string $raw ): string {
	$text = preg_replace( '/^\d+\.\s*/', '', $raw );
	$text = pathinfo( $text, PATHINFO_FILENAME );
	$text = (string) preg_replace( '/[_-]+/', ' ', $text );
	$text = (string) preg_replace( '/\s+/', ' ', $text );
	return trim( $text );
}

/**
 * Generate a unique slug.
 */
function qp_iid_generate_slug( string $title, string $unique_seed ): string {
	$slug = sanitize_title( $title );
	if ( mb_strlen( $slug ) > 180 ) {
		$slug = mb_substr( $slug, 0, 180 );
	}
	$slug = rtrim( $slug, '-' );

	$existing = get_page_by_path( $slug, OBJECT, 'qp_investor_doc' );
	if ( $existing ) {
		$slug .= '-' . substr( md5( $unique_seed ), 0, 8 );
	}
	return $slug;
}

/* ── DOM helpers ── */

/**
 * Create DOMXPath from HTML string.
 */
function qp_iid_xpath( string $html ): ?DOMXPath {
	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	if ( ! $dom->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) ) ) {
		libxml_clear_errors();
		return null;
	}
	libxml_clear_errors();
	return new DOMXPath( $dom );
}

/* ═════════════════════════════════════════════════════════════════
 * Category config
 * ═════════════════════════════════════════════════════════════════ */

define( 'QP_IID_SOURCE_BASE', 'https://qpxanh.com.vn' );

$qp_iid_categories = array(
	'dai-hoi-co-dong'         => 'agm',
	'bao-cao-thuong-nien'      => 'annual-report',
	'bctc-quy'                 => 'financial-report',
	'bao-cao-quan-tri-cong-ty' => 'governance-report',
	'cong-bo-thong-tin'        => 'disclosure',
);

/* Optional filter — restrict to a comma-separated list of source category
 * slugs via QP_IID_ONLY env (e.g. QP_IID_ONLY=dai-hoi-co-dong). Lets us
 * resync a single section without re-crawling everything. */
$qp_iid_only_env = getenv( 'QP_IID_ONLY' );
if ( is_string( $qp_iid_only_env ) && '' !== trim( $qp_iid_only_env ) ) {
	$qp_iid_only = array_filter( array_map( 'trim', explode( ',', $qp_iid_only_env ) ) );
	if ( $qp_iid_only ) {
		$qp_iid_categories = array_intersect_key( $qp_iid_categories, array_flip( $qp_iid_only ) );
	}
}

/* ═════════════════════════════════════════════════════════════════
 * Crawl helpers
 * ═════════════════════════════════════════════════════════════════ */

/**
 * Fetch a URL and return the body.
 */
function qp_iid_fetch( string $url ): string {
	$response = wp_remote_get( $url, array( 'timeout' => 45 ) );
	if ( is_wp_error( $response ) ) {
		WP_CLI::warning( '  HTTP error: ' . $response->get_error_message() );
		return '';
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	if ( $code >= 400 ) {
		WP_CLI::warning( "  HTTP {$code}" );
		return '';
	}
	return (string) wp_remote_retrieve_body( $response );
}

/**
 * Parse listing items from a category page HTML.
 *
 * @return array<int, array{title:string, date:string, link:string}>
 */
function qp_iid_parse_listing( string $html ): array {
	$xpath = qp_iid_xpath( $html );
	if ( ! $xpath ) {
		return array();
	}

	$items  = array();
	$nodes  = $xpath->query( "//div[contains(@class, 'qhcd-item')]" );

	if ( false === $nodes ) {
		return array();
	}

	foreach ( $nodes as $node ) {
		$title_node = $xpath->query( ".//*[contains(@class, 'qhcd-item-title')]", $node )->item( 0 );
		$date_node  = $xpath->query( ".//*[contains(@class, 'qhcd-item-date')]", $node )->item( 0 );
		$link_node  = $xpath->query( './/a', $node )->item( 0 );

		if ( ! $title_node || ! $date_node || ! $link_node ) {
			continue;
		}

		$items[] = array(
			'title' => trim( $title_node->textContent ),
			'date'  => qp_iid_parse_date( trim( $date_node->textContent ) ),
			'link'  => $link_node->getAttribute( 'href' ),
		);
	}

	return $items;
}

/**
 * Check if a listing page has a "next page" link.
 */
function qp_iid_has_next_page( string $html ): bool {
	$xpath = qp_iid_xpath( $html );
	if ( ! $xpath ) {
		return false;
	}
	$links = $xpath->query( "//a[contains(@class, 'next') and contains(@class, 'page-number')]" );
	return $links && $links->length > 0;
}

/**
 * Extract all PDF links from a detail-page HTML.
 *
 * @return array<int, array{filename:string, url:string, link_text:string}>
 */
function qp_iid_parse_detail_pdfs( string $html ): array {
	$xpath = qp_iid_xpath( $html );
	if ( ! $xpath ) {
		return array();
	}

	$pdfs  = array();
	$links = $xpath->query( "//a[contains(@href, '.pdf') or contains(@href, '/uploads/')]" );

	if ( false === $links ) {
		return array();
	}

	$seen = array();
	foreach ( $links as $a ) {
		$href = $a->getAttribute( 'href' );
		if ( false === strpos( $href, '.pdf' ) && false === strpos( $href, '/uploads/' ) ) {
			continue;
		}
		if ( isset( $seen[ $href ] ) ) {
			continue;
		}
		$seen[ $href ] = true;

		$filename  = basename( wp_parse_url( $href, PHP_URL_PATH ) ?: '' );
		$link_text = trim( $a->textContent );

		$pdfs[] = array(
			'filename'  => $filename,
			'url'       => $href,
			'link_text' => $link_text,
		);
	}

	return $pdfs;
}

/* ═════════════════════════════════════════════════════════════════
 * Crawl all categories
 * ═════════════════════════════════════════════════════════════════ */

/**
 * Crawl one category: all listing pages → items.
 */
function qp_iid_crawl_category( string $cat_slug ): array {
	$all = array();
	$p   = 1;

	while ( true ) {
		$url  = QP_IID_SOURCE_BASE . "/qhcd-dm/{$cat_slug}/";
		if ( $p > 1 ) {
			$url .= "page/{$p}/";
		}

		WP_CLI::log( "  Page {$p}: {$url}" );

		$html = qp_iid_fetch( $url );
		if ( '' === $html ) {
			break;
		}

		$items = qp_iid_parse_listing( $html );
		if ( empty( $items ) ) {
			WP_CLI::log( '    (empty page, stopping)' );
			break;
		}

		WP_CLI::log( '    → ' . count( $items ) . ' items' );
		$all = array_merge( $all, $items );

		if ( ! qp_iid_has_next_page( $html ) ) {
			break;
		}
		$p++;
	}

	return $all;
}

/**
 * Resolve a listing item into zero-or-more PDF records.
 *
 * - Direct PDF link → 1 PDF.
 * - /qhcd/ detail page → N PDFs.
 */
function qp_iid_resolve_pdfs( array $item, string $section ): array {
	$link = $item['link'];
	$out  = array();

	if ( preg_match( '/\.pdf$/i', $link ) ) {
		$filename = basename( wp_parse_url( $link, PHP_URL_PATH ) ?: '' );
		$out[]    = array(
			'listing_title' => $item['title'],
			'listing_date'  => $item['date'],
			'section'       => $section,
			'filename'      => $filename,
			'pdf_url'       => $link,
			'detail_url'    => $link,
			'source_url'    => $link,
			'link_text'     => '',
		);
		return $out;
	}

	if ( false !== strpos( $link, '/qhcd/' ) ) {
		$html = qp_iid_fetch( $link );
		if ( '' === $html ) {
			return $out;
		}

		$pdfs = qp_iid_parse_detail_pdfs( $html );
		foreach ( $pdfs as $p ) {
			$out[] = array(
				'listing_title' => $item['title'],
				'listing_date'  => $item['date'],
				'section'       => $section,
				'filename'      => $p['filename'],
				'pdf_url'       => $p['url'],
				'detail_url'    => $link,
				'source_url'    => $p['url'],
				'link_text'     => $p['link_text'],
			);
		}
	}

	return $out;
}

/* ═════════════════════════════════════════════════════════════════
 * Import
 * ═════════════════════════════════════════════════════════════════ */

/**
 * Find an existing qp_investor_doc by its source PDF URL.
 */
function qp_iid_find_by_source( string $source_url ): int {
	$posts = get_posts(
		array(
			'post_type'      => 'qp_investor_doc',
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'meta_key'       => '_qp_source_url',
			'meta_value'     => $source_url,
			'fields'         => 'ids',
		)
	);
	return ! empty( $posts ) ? (int) $posts[0] : 0;
}

/**
 * Append an additional section term to an existing post.
 */
function qp_iid_append_section( int $post_id, string $section ): void {
	$term = get_term_by( 'slug', $section, 'qp_investor_section' );
	if ( ! $term || is_wp_error( $term ) ) {
		return;
	}
	wp_set_post_terms( $post_id, array( (int) $term->term_id ), 'qp_investor_section', true );
}

/**
 * Download a PDF URL into the WordPress Media Library.
 */
function qp_iid_download_pdf( string $url, string $title ): int {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = download_url( $url, 120 );
	if ( is_wp_error( $tmp ) ) {
		WP_CLI::warning( '    Download failed: ' . $tmp->get_error_message() );
		return 0;
	}

	$file_array = array(
		'name'     => basename( wp_parse_url( $url, PHP_URL_PATH ) ?: 'document.pdf' ),
		'tmp_name' => $tmp,
	);

	$id = media_handle_sideload( $file_array, 0, $title );

	if ( is_wp_error( $id ) ) {
		WP_CLI::warning( '    Sideload failed: ' . $id->get_error_message() );
		@unlink( $tmp ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_unlink
		return 0;
	}

	return (int) $id;
}

/**
 * Import a single PDF record into WordPress.
 */
function qp_iid_import_doc( array $pdf ): void {
	$existing_id = qp_iid_find_by_source( $pdf['source_url'] );

	if ( $existing_id ) {
		WP_CLI::log( '  EXISTS: ' . $pdf['filename'] . " (post {$existing_id})" );
		qp_iid_append_section( $existing_id, $pdf['section'] );
		return;
	}

	WP_CLI::log( '  IMPORT: ' . $pdf['filename'] );

	$title      = qp_iid_format_title( $pdf['link_text'] ?: $pdf['filename'] );
	$slug       = qp_iid_generate_slug( $title, $pdf['source_url'] );
	$year       = substr( $pdf['listing_date'], 0, 4 );
	$language   = qp_iid_file_language( $pdf['filename'] );

	$attachment_id = qp_iid_download_pdf( $pdf['pdf_url'], $title );

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'qp_investor_doc',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_date'    => $pdf['listing_date'] . ' 09:00:00',
			'post_content' => '',
		),
		true
	);

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		WP_CLI::warning( '    Post insert failed' );
		return;
	}

	$pid = (int) $post_id;

	update_post_meta( $pid, 'doc_date', $pdf['listing_date'] );
	update_post_meta( $pid, 'doc_year', (int) $year );
	update_post_meta( $pid, 'doc_language', $language );
	update_post_meta( $pid, '_qp_source_url', $pdf['source_url'] );

	if ( $pdf['detail_url'] ) {
		update_post_meta( $pid, '_qp_source_detail_url', $pdf['detail_url'] );
	}

	if ( $attachment_id ) {
		update_post_meta( $pid, 'doc_file', $attachment_id );
		update_post_meta( $pid, 'doc_url', '' );
	} else {
		update_post_meta( $pid, 'doc_url', $pdf['pdf_url'] );
	}

	qp_iid_ensure_year_term( $year );
	qp_iid_assign_terms(
		$pid,
		array(
			'qp_investor_section' => array( $pdf['section'] ),
			'qp_doc_year'         => array( $year ),
			'qp_doc_type'         => array( 'pdf' ),
		)
	);

	WP_CLI::log( "    → Post ID: {$pid}" . ( $attachment_id ? " (attachment {$attachment_id})" : ' (external URL fallback)' ) );
}

/**
 * Delete all mock investor docs (those using sample.pdf).
 */
function qp_iid_delete_mocks(): int {
	$mock_url = 'https://www.africau.edu/images/default/sample.pdf';

	$posts = get_posts(
		array(
			'post_type'      => 'qp_investor_doc',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'meta_key'       => 'doc_url',
			'meta_value'     => $mock_url,
			'fields'         => 'ids',
		)
	);

	$count = 0;
	foreach ( $posts as $post_id ) {
		wp_delete_post( (int) $post_id, true );
		$count++;
	}

	return $count;
}

/* ═════════════════════════════════════════════════════════════════
 * Main
 * ═════════════════════════════════════════════════════════════════ */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	fwrite( STDERR, "This script must be run via WP-CLI.\n" );
	exit( 1 );
}

$qp_iid_import = ( getenv( 'QP_IMPORT' ) === '1' );
$qp_iid_dry    = ! $qp_iid_import;

WP_CLI::log( $qp_iid_dry ? '=== DRY RUN ===' : '=== IMPORT MODE ===' );

/* ── 1. Crawl listing pages ── */
WP_CLI::log( '' );
WP_CLI::log( 'Phase 1 — Crawl listing pages' );
WP_CLI::log( str_repeat( '-', 50 ) );

$qp_iid_all_listings = array();
$qp_iid_category_stat = array();

foreach ( $qp_iid_categories as $cat_slug => $section_slug ) {
	WP_CLI::log( "Category: {$cat_slug} → {$section_slug}" );
	$listings = qp_iid_crawl_category( $cat_slug );
	WP_CLI::log( '  Total: ' . count( $listings ) . ' listing items' );
	$qp_iid_category_stat[ $cat_slug ] = count( $listings );

	foreach ( $listings as &$l ) {
		$l['section'] = $section_slug;
	}
	unset( $l );

	$qp_iid_all_listings[ $cat_slug ] = $listings;
}

/* ── 2. Resolve detail pages → PDF URLs ── */
WP_CLI::log( '' );
WP_CLI::log( 'Phase 2 — Resolve detail pages → PDF URLs' );
WP_CLI::log( str_repeat( '-', 50 ) );

$qp_iid_all_pdfs = array();

foreach ( $qp_iid_all_listings as $cat_slug => $listings ) {
	$section = $qp_iid_categories[ $cat_slug ];
	WP_CLI::log( "Resolving: {$cat_slug}" );
	foreach ( $listings as $item ) {
		$pdfs = qp_iid_resolve_pdfs( $item, $section );
		foreach ( $pdfs as $pdf ) {
			$qp_iid_all_pdfs[] = $pdf;
		}
	}
	WP_CLI::log( '  PDFs so far: ' . count( $qp_iid_all_pdfs ) );
}

WP_CLI::log( 'Total raw PDFs: ' . count( $qp_iid_all_pdfs ) );

/* ── 3. Filter: Vietnamese only ── */
WP_CLI::log( '' );
WP_CLI::log( 'Phase 3 — Filter Vietnamese files' );
WP_CLI::log( str_repeat( '-', 50 ) );

$qp_iid_vi_pdfs   = array();
$qp_iid_en_count   = 0;

foreach ( $qp_iid_all_pdfs as $pdf ) {
	if ( qp_iid_is_vietnamese( $pdf['filename'] ) ) {
		$qp_iid_vi_pdfs[] = $pdf;
	} else {
		WP_CLI::log( '  SKIP (EN): ' . $pdf['filename'] );
		$qp_iid_en_count++;
	}
}

WP_CLI::log( '' );
WP_CLI::log( "VI files to import: " . count( $qp_iid_vi_pdfs ) );
WP_CLI::log( "EN files skipped:   {$qp_iid_en_count}" );

/* Dedup count by source_url */
$qp_iid_unique_sources = array();
foreach ( $qp_iid_vi_pdfs as $pdf ) {
	$qp_iid_unique_sources[ $pdf['source_url'] ] = true;
}
WP_CLI::log( "Unique source URLs: " . count( $qp_iid_unique_sources ) );

/* Per-section breakdown */
$qp_iid_section_counts = array();
foreach ( $qp_iid_vi_pdfs as $pdf ) {
	$s = $pdf['section'];
	$qp_iid_section_counts[ $s ] = ( $qp_iid_section_counts[ $s ] ?? 0 ) + 1;
}
WP_CLI::log( 'Per section:' );
foreach ( $qp_iid_section_counts as $s => $c ) {
	WP_CLI::log( "  {$s}: {$c}" );
}

/* ── 4. Import ── */
if ( $qp_iid_dry ) {
	WP_CLI::log( '' );
	WP_CLI::log( 'Dry run complete. Run with --import to actually import.' );
	exit( 0 );
}

WP_CLI::log( '' );
WP_CLI::log( 'Phase 4 — Delete mocks' );
WP_CLI::log( str_repeat( '-', 50 ) );

$qp_iid_killed = qp_iid_delete_mocks();
WP_CLI::log( "Deleted {$qp_iid_killed} mock posts (sample.pdf)." );

WP_CLI::log( '' );
WP_CLI::log( 'Phase 5 — Import PDFs into WordPress' );
WP_CLI::log( str_repeat( '-', 50 ) );

$qp_iid_created   = 0;
$qp_iid_existed    = 0;
$qp_iid_total      = count( $qp_iid_vi_pdfs );

foreach ( $qp_iid_vi_pdfs as $i => $pdf ) {
	WP_CLI::log( sprintf( '[%d/%d]', $i + 1, $qp_iid_total ) );
	$before = qp_iid_find_by_source( $pdf['source_url'] );
	qp_iid_import_doc( $pdf );
	$after  = qp_iid_find_by_source( $pdf['source_url'] );
	if ( $before ) {
		$qp_iid_existed++;
	} elseif ( $after ) {
		$qp_iid_created++;
	}
}

WP_CLI::log( '' );
WP_CLI::log( str_repeat( '=', 50 ) );
WP_CLI::log( 'IMPORT COMPLETE' );
WP_CLI::log( "  Created:  {$qp_iid_created}" );
WP_CLI::log( "  Existed:  {$qp_iid_existed}" );
WP_CLI::log( "  Mocks deleted: {$qp_iid_killed}" );
WP_CLI::log( "  Per section:" );
foreach ( $qp_iid_section_counts as $s => $c ) {
	WP_CLI::log( "    {$s}: {$c} PDFs" );
}
WP_CLI::log( str_repeat( '=', 50 ) );
