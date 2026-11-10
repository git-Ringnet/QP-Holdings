<?php
/**
 * Grouped importer — qpxanh.com.vn → WordPress qp_investor_doc.
 *
 * Each listing item on /qhcd-dm/<category>/ becomes ONE qp_investor_doc post
 * (matching the source layout). Every PDF link inside the detail page is saved
 * as a row of the ACF "doc_files" repeater (raw post-meta is used so the script
 * works without ACF Pro). The first VI file is also stored on the legacy
 * doc_file/doc_url meta so the existing table renderer keeps working.
 *
 * Usage (inside wpcli container):
 *   wp --allow-root eval-file bin/import-investor-grouped.php             # dry run all categories
 *   QP_IMPORT=1 QP_IID_ONLY=dai-hoi-co-dong wp --allow-root \
 *     eval-file bin/import-investor-grouped.php
 *
 * Env vars:
 *   QP_IMPORT=1            actually run (otherwise dry run)
 *   QP_IID_ONLY=slug,slug  comma-separated source-category slugs
 *   QP_RESET=1             delete existing posts that came from each crawled
 *                          listing URL before re-importing (use after a schema
 *                          change). Without it, posts are matched by listing URL
 *                          and updated in place.
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	fwrite( STDERR, "This script must be run via WP-CLI.\n" );
	exit( 1 );
}

/* ───────────────────────────── Config ───────────────────────────── */

const QP_IIG_BASE = 'https://qpxanh.com.vn';

$qp_iig_categories = array(
	'dai-hoi-co-dong'           => 'agm',
	'bao-cao-thuong-nien'       => 'annual-report',
	'bctc-quy'                  => 'financial-report',
	'bao-cao-quan-tri-cong-ty'  => 'governance-report',
	'cong-bo-thong-tin'         => 'disclosure',
	'quan-tri-cong-ty'          => 'company-governance',
);

$only_env = getenv( 'QP_IID_ONLY' );
if ( is_string( $only_env ) && '' !== trim( $only_env ) ) {
	$only = array_filter( array_map( 'trim', explode( ',', $only_env ) ) );
	if ( $only ) {
		$qp_iig_categories = array_intersect_key( $qp_iig_categories, array_flip( $only ) );
	}
}

$qp_iig_dry   = '1' !== (string) getenv( 'QP_IMPORT' );
$qp_iig_reset = '1' === (string) getenv( 'QP_RESET' );

/* ───────────────────────────── Helpers ───────────────────────────── */

function qp_iig_fetch( string $url ): string {
	// qpxanh.com.vn varies its cache by User-Agent and request fingerprint.
	// `wp_remote_get()` produces a truncated page (only ~2 .qhcd-item nodes
	// instead of 5 on the QTCT category) regardless of UA tweaks. A direct
	// PHP-cURL call with no Accept-Language / no extra headers reproduces
	// the full listing the same way `curl https://...` does from a shell.
	$ch = curl_init( $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
	curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 15 );
	curl_setopt( $ch, CURLOPT_TIMEOUT, 90 );
	curl_setopt( $ch, CURLOPT_USERAGENT, 'curl/7.88.1' );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Accept: */*' ) );
	curl_setopt( $ch, CURLOPT_ENCODING, '' );

	$body = curl_exec( $ch );
	$code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	$err  = curl_error( $ch );
	curl_close( $ch );

	if ( $err ) {
		WP_CLI::warning( '  cURL error: ' . $err );
		return '';
	}
	if ( $code >= 400 ) {
		WP_CLI::warning( "  HTTP {$code}" );
		return '';
	}
	return is_string( $body ) ? $body : '';
}

function qp_iig_xpath( string $html ): ?DOMXPath {
	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	if ( ! $dom->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) ) ) {
		libxml_clear_errors();
		return null;
	}
	libxml_clear_errors();
	return new DOMXPath( $dom );
}

function qp_iig_parse_date( string $raw ): string {
	if ( preg_match( '/(\d{2})\/(\d{2})\/(\d{4})/', $raw, $m ) ) {
		return $m[3] . '-' . $m[2] . '-' . $m[1];
	}
	return date( 'Y-m-d' );
}

function qp_iig_file_language( string $filename, string $link_text = '' ): string {
	$haystack = strtoupper( $filename . ' ' . $link_text );
	$has_vi  = (bool) preg_match( '/(^|[_\-. ])(?:VI|VIE|VN)([_\-. ]|$)/', $haystack );
	$has_en  = (bool) preg_match( '/(^|[_\-. ])(?:EN|ENG|ENGLISH)([_\-. ]|$)/', $haystack )
		|| (bool) preg_match( '/PROPOSAL|RESOLUTION|MEETING[\- ]MINUTES|INVITATION[\- ]LETTER|REPORTS[\- ]OF[\- ]BOD|CHARTER|REGULATIONS|CONSOLIDATED|SEPARATE|EXPLANATION/', strtoupper( $filename ) );

	if ( $has_vi && ! $has_en ) {
		return 'vi';
	}
	if ( $has_en && ! $has_vi ) {
		return 'en';
	}
	if ( $has_vi && $has_en ) {
		return 'bilingual';
	}
	return 'vi';
}

function qp_iig_pretty_filename_title( string $raw_text, string $filename ): string {
	$text = trim( $raw_text );
	if ( '' === $text ) {
		$text = pathinfo( $filename, PATHINFO_FILENAME );
	}
	$text = (string) preg_replace( '/^\d+\s*[\.\-]\s*/', '', $text );
	$text = (string) preg_replace( '/[_\-]+/', ' ', $text );
	$text = (string) preg_replace( '/\s+/', ' ', $text );
	return trim( $text );
}

function qp_iig_unique_slug( string $title, string $seed ): string {
	$slug = sanitize_title( $title );
	if ( '' === $slug ) {
		$slug = 'qp-' . substr( md5( $seed ), 0, 8 );
	}
	if ( mb_strlen( $slug ) > 180 ) {
		$slug = mb_substr( $slug, 0, 180 );
	}
	return rtrim( $slug, '-' );
}

function qp_iig_term_id( string $taxonomy, string $slug ): int {
	$term = get_term_by( 'slug', $slug, $taxonomy );
	return $term && ! is_wp_error( $term ) ? (int) $term->term_id : 0;
}

function qp_iig_ensure_year_term( string $year ): void {
	if ( ! term_exists( $year, 'qp_doc_year' ) ) {
		wp_insert_term( $year, 'qp_doc_year', array( 'slug' => $year ) );
	}
}

/**
 * Find a post created from a given listing URL in a given IR section.
 */
function qp_iig_find_by_listing_url( string $listing_url, string $section ): int {
	$posts = get_posts(
		array(
			'post_type'      => 'qp_investor_doc',
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'meta_query'     => array(
				array(
					'key'   => '_qp_listing_url',
					'value' => $listing_url,
				),
				array(
					'key'   => '_qp_source_section',
					'value' => $section,
				),
			),
			'fields'         => 'ids',
			'suppress_filters' => true,
		)
	);
	return ! empty( $posts ) ? (int) $posts[0] : 0;
}

/**
 * Find an attachment we already imported from a remote URL.
 */
function qp_iig_find_attachment_by_source( string $remote_url ): int {
	global $wpdb;
	$id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
			'_qp_remote_url',
			$remote_url
		)
	);
	return $id ?: 0;
}

/**
 * Sideload a remote PDF into the Media Library, returning its attachment ID.
 * Subsequent calls with the same URL return the cached attachment.
 */
function qp_iig_sideload_pdf( string $url, string $title ): int {
	$cached = qp_iig_find_attachment_by_source( $url );
	if ( $cached ) {
		return $cached;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = download_url( $url, 180 );
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

	$id = (int) $id;
	update_post_meta( $id, '_qp_remote_url', $url );
	return $id;
}

/* ──────────────────── Crawl: listing pages ──────────────────── */

function qp_iig_parse_listing( string $html ): array {
	// The qpxanh listing page has invalid HTML earlier in the document
	// (broken <header> / <svg>) which causes DOMDocument to drop
	// almost all subsequent .qhcd-item nodes. Regex matching of the
	// stable card markup is far more reliable for this fixed structure.
	$item_class  = 'class="(?:[^"]*\s)?qhcd-item(?:\s[^"]*)?"';
	$item_pattern = '/<div\s+[^>]*' . $item_class . '[^>]*>(?<block>.*?)(?=<div\s+[^>]*' . $item_class . '|<\/section>|$)/sui';

	$matches = array();
	if ( preg_match_all( $item_pattern, $html, $blocks, PREG_SET_ORDER ) ) {
		foreach ( $blocks as $block ) {
			$fragment = $block['block'];
			if (
				preg_match( '/<[a-z0-9]+\s+[^>]*class="[^"]*qhcd-item-title[^"]*"[^>]*>(?<title>.*?)<\/[a-z0-9]+>/sui', $fragment, $title )
				&& preg_match( '/<[a-z0-9]+\s+[^>]*class="[^"]*qhcd-item-date[^"]*"[^>]*>(?<date>[^<]+)<\/[a-z0-9]+>/sui', $fragment, $date )
				&& preg_match( '/<a\s+[^>]*href="(?<href>[^"]+)"[^>]*>/sui', $fragment, $href )
			) {
				$matches[] = array(
					'title' => $title['title'],
					'date'  => $date['date'],
					'href'  => $href['href'],
				);
			}
		}
	}

	if ( empty( $matches ) ) {
		$pattern = '/<[a-z0-9]+\s+[^>]*class="[^"]*qhcd-item-title[^"]*"[^>]*>(?<title>.*?)<\/[a-z0-9]+>'
			. '\s*<[a-z0-9]+\s+[^>]*class="[^"]*qhcd-item-date[^"]*"[^>]*>(?<date>[^<]+)<\/[a-z0-9]+>'
			. '.*?<a\s+[^>]*href="(?<href>[^"]+)"[^>]*>/sui';
		preg_match_all( $pattern, $html, $matches, PREG_SET_ORDER );
	}

	$items = array();
	$seen  = array();
	foreach ( $matches as $m ) {
		$title = trim( html_entity_decode( strip_tags( $m['title'] ), ENT_QUOTES, 'UTF-8' ) );
		$date  = trim( $m['date'] );
		$href  = trim( $m['href'] );
		if ( '' === $title || '' === $href || isset( $seen[ $href ] ) ) {
			continue;
		}
		$seen[ $href ] = true;
		$items[] = array(
			'title' => $title,
			'date'  => qp_iig_parse_date( $date ),
			'link'  => $href,
		);
	}
	return $items;
}

function qp_iig_has_next_page( string $html ): bool {
	$xpath = qp_iig_xpath( $html );
	if ( ! $xpath ) {
		return false;
	}
	$links = $xpath->query( "//a[contains(@class, 'next') and contains(@class, 'page-number')]" );
	return $links && $links->length > 0;
}

function qp_iig_crawl_category( string $cat_slug ): array {
	$all = array();
	$p   = 1;
	while ( true ) {
		$url = QP_IIG_BASE . "/qhcd-dm/{$cat_slug}/";
		if ( $p > 1 ) {
			$url .= "page/{$p}/";
		}
		WP_CLI::log( "  Page {$p}: {$url}" );
		$html = qp_iig_fetch( $url );
		if ( '' === $html ) {
			break;
		}
		$items = qp_iig_parse_listing( $html );
		if ( empty( $items ) ) {
			break;
		}
		WP_CLI::log( '    → ' . count( $items ) . ' items' );
		$all = array_merge( $all, $items );
		if ( ! qp_iig_has_next_page( $html ) ) {
			break;
		}
		$p++;
	}
	return $all;
}

/* ──────────────────── Crawl: detail pages ──────────────────── */

/**
 * Pull every PDF/upload link from a detail page.
 *
 * @return array<int, array{filename:string,url:string,link_text:string}>
 */
function qp_iig_parse_detail_pdfs( string $html ): array {
	$xpath = qp_iig_xpath( $html );
	if ( ! $xpath ) {
		return array();
	}

	$links = $xpath->query( "//a[contains(@href, '.pdf') or contains(@href, '/uploads/')]" );
	if ( false === $links ) {
		return array();
	}

	$out  = array();
	$seen = array();
	foreach ( $links as $a ) {
		$href = $a->getAttribute( 'href' );
		if ( false === stripos( $href, '.pdf' ) && false === stripos( $href, '/uploads/' ) ) {
			continue;
		}
		if ( isset( $seen[ $href ] ) ) {
			continue;
		}
		$seen[ $href ] = true;
		$out[] = array(
			'filename'  => basename( wp_parse_url( $href, PHP_URL_PATH ) ?: '' ),
			'url'       => $href,
			'link_text' => trim( $a->textContent ),
		);
	}
	return $out;
}

/* ──────────────────── Import a listing item ──────────────────── */

function qp_iig_save_doc_files_repeater( int $post_id, array $rows ): void {
	// Wipe existing repeater rows first.
	$prev = (int) get_post_meta( $post_id, 'doc_files', true );
	for ( $i = 0; $i < $prev; $i++ ) {
		$prefix = 'doc_files_' . $i . '_';
		delete_post_meta( $post_id, $prefix . 'title' );
		delete_post_meta( $post_id, $prefix . 'language' );
		delete_post_meta( $post_id, $prefix . 'file' );
		delete_post_meta( $post_id, $prefix . 'url' );
		delete_post_meta( $post_id, '_' . $prefix . 'title' );
		delete_post_meta( $post_id, '_' . $prefix . 'language' );
		delete_post_meta( $post_id, '_' . $prefix . 'file' );
		delete_post_meta( $post_id, '_' . $prefix . 'url' );
	}

	$count = 0;
	foreach ( $rows as $i => $row ) {
		$prefix = 'doc_files_' . $i . '_';
		update_post_meta( $post_id, $prefix . 'title', (string) ( $row['title'] ?? '' ) );
		update_post_meta( $post_id, $prefix . 'language', (string) ( $row['language'] ?? 'vi' ) );
		update_post_meta( $post_id, $prefix . 'file', (int) ( $row['file_id'] ?? 0 ) );
		update_post_meta( $post_id, $prefix . 'url', (string) ( $row['url'] ?? '' ) );

		// ACF reference keys — keeps the editor UI working when ACF is enabled later.
		update_post_meta( $post_id, '_' . $prefix . 'title', 'field_qp_doc_files_title' );
		update_post_meta( $post_id, '_' . $prefix . 'language', 'field_qp_doc_files_language' );
		update_post_meta( $post_id, '_' . $prefix . 'file', 'field_qp_doc_files_file' );
		update_post_meta( $post_id, '_' . $prefix . 'url', 'field_qp_doc_files_url' );

		$count++;
	}

	update_post_meta( $post_id, 'doc_files', $count );
	update_post_meta( $post_id, '_doc_files', 'field_qp_doc_files' );
}

function qp_iig_import_listing_item( array $item, string $section, bool $reset ): array {
	$listing_url = $item['link'];
	WP_CLI::log( '  → ' . $item['title'] );
	WP_CLI::log( '    ' . $listing_url );

	// Pull PDFs from the detail page (or treat the listing link itself as the only PDF).
	$pdfs = array();
	if ( preg_match( '/\.pdf$/i', $listing_url ) ) {
		$pdfs[] = array(
			'filename'  => basename( wp_parse_url( $listing_url, PHP_URL_PATH ) ?: '' ),
			'url'       => $listing_url,
			'link_text' => $item['title'],
		);
	} else {
		$html = qp_iig_fetch( $listing_url );
		if ( '' !== $html ) {
			$pdfs = qp_iig_parse_detail_pdfs( $html );
		}
	}

	if ( empty( $pdfs ) ) {
		WP_CLI::warning( '    no PDFs found' );
		return array( 'skipped' => 1 );
	}

	$existing_id = qp_iig_find_by_listing_url( $listing_url, $section );
	if ( $existing_id && $reset ) {
		wp_delete_post( $existing_id, true );
		$existing_id = 0;
	}

	$year = substr( $item['date'], 0, 4 );

	$post_data = array(
		'post_type'   => 'qp_investor_doc',
		'post_status' => 'publish',
		'post_title'  => $item['title'],
		'post_name'   => qp_iig_unique_slug( $item['title'], $listing_url . '|' . $section ),
		'post_date'   => $item['date'] . ' 09:00:00',
	);

	if ( $existing_id ) {
		$post_data['ID'] = $existing_id;
		$post_id = wp_update_post( $post_data, true );
	} else {
		$post_id = wp_insert_post( $post_data, true );
	}

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		WP_CLI::warning( '    Post insert failed' );
		return array( 'failed' => 1 );
	}

	$pid = (int) $post_id;

	// Sideload every PDF into the Media Library and build repeater rows.
	$rows = array();
	$primary_attachment_id = 0;
	$primary_url = '';
	$languages_present = array();
	$total_size = 0;

	foreach ( $pdfs as $idx => $pdf ) {
		$attach_id = qp_iig_sideload_pdf( $pdf['url'], $pdf['link_text'] ?: $pdf['filename'] );
		$attach_url = $attach_id ? wp_get_attachment_url( $attach_id ) : $pdf['url'];

		$lang = qp_iig_file_language( $pdf['filename'], $pdf['link_text'] );
		$languages_present[ $lang ] = true;

		$row_title = qp_iig_pretty_filename_title( $pdf['link_text'], $pdf['filename'] );

		$rows[] = array(
			'title'    => $row_title,
			'language' => $lang,
			'file_id'  => $attach_id,
			'url'      => $attach_id ? '' : $pdf['url'],
		);

		// Track size for the parent post.
		if ( $attach_id ) {
			$path = get_attached_file( $attach_id );
			if ( $path && file_exists( $path ) ) {
				$total_size += filesize( $path );
			}
		}

		// Pick a primary file: prefer the first VI/bilingual; fall back to first row.
		if ( ! $primary_attachment_id && ! $primary_url ) {
			if ( in_array( $lang, array( 'vi', 'bilingual' ), true ) ) {
				$primary_attachment_id = $attach_id;
				$primary_url           = $attach_id ? '' : $pdf['url'];
			}
		}
	}

	if ( ! $primary_attachment_id && ! $primary_url ) {
		// Nothing flagged as VI — just use the first row.
		$primary_attachment_id = (int) ( $rows[0]['file_id'] ?? 0 );
		$primary_url           = (string) ( $rows[0]['url'] ?? '' );
	}

	qp_iig_save_doc_files_repeater( $pid, $rows );

	update_post_meta( $pid, 'doc_date', $item['date'] );
	update_post_meta( $pid, 'doc_year', (int) $year );
	update_post_meta( $pid, 'doc_size', $total_size );
	update_post_meta( $pid, 'doc_pages', count( $rows ) );

	$post_lang = isset( $languages_present['vi'] ) || isset( $languages_present['bilingual'] )
		? 'vi'
		: ( isset( $languages_present['en'] ) ? 'en' : 'vi' );
	update_post_meta( $pid, 'doc_language', $post_lang );

	if ( $primary_attachment_id ) {
		update_post_meta( $pid, 'doc_file', $primary_attachment_id );
		delete_post_meta( $pid, 'doc_url' );
	} else {
		update_post_meta( $pid, 'doc_url', $primary_url );
		delete_post_meta( $pid, 'doc_file' );
	}

	update_post_meta( $pid, '_qp_listing_url', $listing_url );
	update_post_meta( $pid, '_qp_source_url', $listing_url );
	update_post_meta( $pid, '_qp_source_section', $section );

	qp_iig_ensure_year_term( $year );

	$term_ids = array();
	$section_id = qp_iig_term_id( 'qp_investor_section', $section );
	if ( $section_id ) {
		$term_ids[] = $section_id;
	}
	if ( $term_ids ) {
		wp_set_post_terms( $pid, $term_ids, 'qp_investor_section', false );
	}

	$year_id = qp_iig_term_id( 'qp_doc_year', $year );
	if ( $year_id ) {
		wp_set_post_terms( $pid, array( $year_id ), 'qp_doc_year', false );
	}

	$type_id = qp_iig_term_id( 'qp_doc_type', 'pdf' );
	if ( $type_id ) {
		wp_set_post_terms( $pid, array( $type_id ), 'qp_doc_type', false );
	}

	WP_CLI::log( "    → post {$pid}, " . count( $rows ) . ' file(s)' );

	return array(
		$existing_id ? 'updated' : 'created' => 1,
		'files'                              => count( $rows ),
	);
}

/* ──────────────────── Optional reset ──────────────────── */

function qp_iig_delete_legacy_section_posts( string $section ): int {
	// Posts whose meta `_qp_source_url` points at a direct PDF (legacy importer).
	$legacy = get_posts(
		array(
			'post_type'      => 'qp_investor_doc',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_qp_source_url',
					'value'   => '.pdf',
					'compare' => 'LIKE',
				),
			),
			'tax_query' => array(
				array(
					'taxonomy' => 'qp_investor_section',
					'field'    => 'slug',
					'terms'    => $section,
				),
			),
			'suppress_filters' => true,
		)
	);

	$count = 0;
	foreach ( $legacy as $pid ) {
		// Only delete if it does NOT have a `_qp_listing_url` (i.e. came from old importer).
		if ( ! get_post_meta( (int) $pid, '_qp_listing_url', true ) ) {
			wp_delete_post( (int) $pid, true );
			$count++;
		}
	}
	return $count;
}

/* ──────────────────── Main ──────────────────── */

WP_CLI::log( $qp_iig_dry ? '=== DRY RUN ===' : '=== IMPORT MODE ===' );
if ( $qp_iig_reset ) {
	WP_CLI::log( '   (with QP_RESET=1 — overwriting existing rows)' );
}

$summary = array();

foreach ( $qp_iig_categories as $cat_slug => $section_slug ) {
	WP_CLI::log( '' );
	WP_CLI::log( str_repeat( '=', 60 ) );
	WP_CLI::log( "Category: {$cat_slug} → section: {$section_slug}" );
	WP_CLI::log( str_repeat( '=', 60 ) );

	$listings = qp_iig_crawl_category( $cat_slug );
	WP_CLI::log( 'Total listings: ' . count( $listings ) );

	if ( $qp_iig_dry ) {
		foreach ( $listings as $l ) {
			WP_CLI::log( "  [{$l['date']}] {$l['title']}" );
			WP_CLI::log( "    {$l['link']}" );
		}
		continue;
	}

	if ( $qp_iig_reset ) {
		$killed = qp_iig_delete_legacy_section_posts( $section_slug );
		WP_CLI::log( "Removed {$killed} legacy post(s) from section {$section_slug}" );
	}

	$created = 0;
	$updated = 0;
	$failed  = 0;
	$files   = 0;

	foreach ( $listings as $idx => $item ) {
		WP_CLI::log( sprintf( '[%d/%d]', $idx + 1, count( $listings ) ) );
		$result = qp_iig_import_listing_item( $item, $section_slug, $qp_iig_reset );
		$created += $result['created'] ?? 0;
		$updated += $result['updated'] ?? 0;
		$failed  += $result['failed'] ?? 0;
		$files   += $result['files']  ?? 0;
	}

	$summary[ $cat_slug ] = compact( 'created', 'updated', 'failed', 'files' );
}

WP_CLI::log( '' );
WP_CLI::log( str_repeat( '=', 60 ) );
WP_CLI::log( $qp_iig_dry ? 'DRY RUN COMPLETE' : 'IMPORT COMPLETE' );
WP_CLI::log( str_repeat( '=', 60 ) );

if ( ! $qp_iig_dry ) {
	foreach ( $summary as $cat => $s ) {
		WP_CLI::log( "  {$cat}: created={$s['created']} updated={$s['updated']} failed={$s['failed']} total_files={$s['files']}" );
	}
} else {
	WP_CLI::log( 'Run with QP_IMPORT=1 to actually import.' );
}
