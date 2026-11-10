<?php
/**
 * Investor documents (qp_investor_doc) — renderer + seed.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolve an investor document file size from ACF output or raw attachment meta.
 *
 * ACF file fields store the attachment ID in post meta, but can return only the
 * URL depending on return_format. Use both values so uploaded files still show
 * their size when the "Kich thuoc" field is left blank.
 */
function qp_holdings_investor_doc_file_size( $file, $raw_file = null ): int {
	$values = array( $file, $raw_file );

	foreach ( $values as $value ) {
		if ( is_array( $value ) ) {
			if ( ! empty( $value['filesize'] ) ) {
				return (int) $value['filesize'];
			}
			if ( ! empty( $value['ID'] ) ) {
				$value = $value['ID'];
			} elseif ( ! empty( $value['id'] ) ) {
				$value = $value['id'];
			} elseif ( ! empty( $value['url'] ) ) {
				$value = $value['url'];
			}
		}

		if ( is_numeric( $value ) ) {
			$path = get_attached_file( (int) $value );
			if ( $path && file_exists( $path ) ) {
				return (int) filesize( $path );
			}
			continue;
		}

		if ( is_string( $value ) && filter_var( $value, FILTER_VALIDATE_URL ) ) {
			$attachment_id = attachment_url_to_postid( $value );
			if ( $attachment_id ) {
				$path = get_attached_file( $attachment_id );
				if ( $path && file_exists( $path ) ) {
					return (int) filesize( $path );
				}
			}
		}
	}

	return 0;
}

/**
 * Investor sections that use the new card-list layout (Figma 2769:6080).
 *
 * Stock-info has its own bespoke landing page; everything else now uses the
 * grouped card layout with a per-listing detail page.
 *
 * @return array<int,string>
 */
function qp_holdings_card_list_sections(): array {
	return array(
		'company-governance',
		'agm',
		'annual-report',
		'financial-report',
		'governance-report',
		'disclosure',
		'ban-tin',
		'ban-tin-nha-dau-tu',
		'investor-news',
	);
}

/**
 * Detect the language of a qp_investor_doc post.
 */
function qp_holdings_get_doc_post_language( int $post_id ): string {
	$meta_lang = (string) get_post_meta( $post_id, '_qp_translation_lang', true );
	if ( in_array( $meta_lang, array( 'vi', 'en', 'zh' ), true ) ) {
		return $meta_lang;
	}
	if ( function_exists( 'pll_get_post_language' ) ) {
		$pll_lang = (string) pll_get_post_language( $post_id, 'slug' );
		if ( in_array( $pll_lang, array( 'vi', 'en', 'zh' ), true ) ) {
			return $pll_lang;
		}
	}
	$post_obj = get_post( $post_id );
	$slug = $post_obj instanceof WP_Post ? $post_obj->post_name : (string) get_post_field( 'post_name', $post_id );
	if ( preg_match( '/^(?:en|zh)-/i', $slug, $m ) || preg_match( '/-(?:en|zh)$/i', $slug, $m ) ) {
		return strtolower( trim( $m[0], '-' ) );
	}
	$doc_lang = (string) get_post_meta( $post_id, 'doc_language', true );
	if ( in_array( $doc_lang, array( 'vi', 'en', 'zh' ), true ) ) {
		return $doc_lang;
	}
	$title = $post_obj instanceof WP_Post ? $post_obj->post_title : (string) get_the_title( $post_id );
	if ( preg_match( '/[\x{4e00}-\x{9fff}]/u', $title ) ) {
		return 'zh';
	}
	if ( preg_match( '/\b(?:Newsletter|Report|Letter|Financial|Statement|Minutes|Annual)\b/i', $title ) && ! preg_match( '/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/ui', $title ) ) {
		return 'en';
	}
	return 'vi';
}

/**
 * Investor docs are managed directly in Polylang, including manually-created
 * translated posts. Use Polylang's language query only; the shared helper also
 * requires seeded translation meta that manual IR documents do not carry.
 */
function qp_holdings_add_investor_doc_query_lang( array $args, string $lang ): array {
	if ( function_exists( 'pll_current_language' ) ) {
		$args['lang'] = $lang;
		if ( taxonomy_exists( 'language' ) ) {
			$args['tax_query'] = $args['tax_query'] ?? array();
			$args['tax_query'][] = array(
				'taxonomy' => 'language',
				'field'    => 'slug',
				'terms'    => $lang,
			);
		}
	}

	return $args;
}

/**
 * Build a section tax query in the active language. On translated pages,
 * Polylang filters term lookups by current language, so looking up the
 * Vietnamese canonical slug directly can fail. Use the known translated slugs
 * as a stable fallback for investor-section terms.
 */
function qp_holdings_investor_section_tax_query( string $section, string $lang ): array {
	$synonyms = array(
		'stock-info'         => array( 'stock-info', 'stock-information', 'thong-tin-co-phieu', 'stock-information-zh', 'stock-information-en', 'thong-tin-co-phieu-en', 'thong-tin-co-phieu-zh' ),
		'company-governance' => array( 'company-governance', 'corporate-governance', 'quan-tri-cong-ty', 'corporate-governance-zh', 'corporate-governance-en', 'quan-tri-cong-ty-en', 'quan-tri-cong-ty-zh' ),
		'agm'                => array( 'agm', 'general-meeting-of-shareholders', 'dai-hoi-dong-co-dong', 'general-meeting-of-shareholders-zh', 'general-meeting-of-shareholders-en', 'dai-hoi-dong-co-dong-en', 'dai-hoi-dong-co-dong-zh' ),
		'annual-report'      => array( 'annual-report', 'annual-reports', 'bao-cao-thuong-nien', 'annual-reports-zh', 'annual-reports-en', 'bao-cao-thuong-nien-en', 'bao-cao-thuong-nien-zh' ),
		'financial-report'   => array( 'financial-report', 'financial-reports', 'bao-cao-tai-chinh', 'financial-reports-zh', 'financial-reports-en', 'bao-cao-tai-chinh-en', 'bao-cao-tai-chinh-zh' ),
		'governance-report'  => array( 'governance-report', 'governance-reports', 'corporate-governance-reports', 'bao-cao-quan-tri-cong-ty', 'corporate-governance-reports-zh', 'corporate-governance-reports-en', 'bao-cao-quan-tri-cong-ty-en', 'bao-cao-quan-tri-cong-ty-zh' ),
		'disclosure'         => array( 'disclosure', 'disclosures', 'cong-bo-thong-tin', 'disclosures-zh', 'disclosures-en', 'cong-bo-thong-tin-en', 'cong-bo-thong-tin-zh' ),
		'ban-tin'            => array( 'investor-news', 'ban-tin', 'ban-tin-nha-dau-tu', 'newsletter', 'investor-newsletter', 'newsletter-zh', 'newsletter-en', 'ban-tin-nha-dau-tu-en', 'ban-tin-nha-dau-tu-zh', 'investor-news-en', 'investor-news-zh' ),
		'ban-tin-nha-dau-tu' => array( 'investor-news', 'ban-tin', 'ban-tin-nha-dau-tu', 'newsletter', 'investor-newsletter', 'newsletter-zh', 'newsletter-en', 'ban-tin-nha-dau-tu-en', 'ban-tin-nha-dau-tu-zh', 'investor-news-en', 'investor-news-zh' ),
		'investor-news'      => array( 'investor-news', 'ban-tin', 'ban-tin-nha-dau-tu', 'newsletter', 'investor-newsletter', 'newsletter-zh', 'newsletter-en', 'ban-tin-nha-dau-tu-en', 'ban-tin-nha-dau-tu-zh', 'investor-news-en', 'investor-news-zh' ),
		'newsletter'         => array( 'investor-news', 'ban-tin', 'ban-tin-nha-dau-tu', 'newsletter', 'investor-newsletter', 'newsletter-zh', 'newsletter-en', 'ban-tin-nha-dau-tu-en', 'ban-tin-nha-dau-tu-zh', 'investor-news-en', 'investor-news-zh' ),
	);

	$raw_terms = $synonyms[ $section ] ?? array( $section );
	$all_terms = array();
	foreach ( $raw_terms as $t ) {
		$all_terms[] = $t;
		$all_terms[] = $t . '-en';
		$all_terms[] = $t . '-zh';
		$all_terms[] = $t . '-vi';
		$all_terms[] = 'en-' . $t;
		$all_terms[] = 'zh-' . $t;
	}
	$all_terms = array_values( array_unique( $all_terms ) );

	return array(
		'taxonomy' => 'qp_investor_section',
		'field'    => 'slug',
		'terms'    => $all_terms,
		'operator' => 'IN',
	);
}

/**
 * Render the investor documents table for a given section.
 *
 * Section is auto-detected from the current page slug when $section is null.
 *
 * @param string|null $section Section term slug.
 */
function qp_holdings_render_investor_documents( ?string $section = null ): bool {
	if ( null === $section ) {
		$slug = get_post_field( 'post_name', get_queried_object_id() );
		$map  = array(
			'thong-tin-co-phieu'       => 'stock-info',
			'quan-tri-cong-ty'         => 'company-governance',
			'dai-hoi-dong-co-dong'     => 'agm',
			'bao-cao-thuong-nien'      => 'annual-report',
			'bao-cao-tai-chinh'        => 'financial-report',
			'bao-cao-quan-tri-cong-ty' => 'governance-report',
			'cong-bo-thong-tin'        => 'disclosure',
		);
		$section = isset( $map[ $slug ] ) ? $map[ $slug ] : null;
	}

	// All non-stock IR sections share the new grouped card layout.
	if ( in_array( (string) $section, qp_holdings_card_list_sections(), true ) ) {
		return qp_holdings_render_governance_card_list( (string) $section );
	}

	$year = qp_holdings_get_query_string( 'year' );
	$type = qp_holdings_get_query_string( 'type' );

	$paged = max( 1, (int) get_query_var( 'paged' ) );

	$args = array(
		'post_type'      => 'qp_investor_doc',
		'posts_per_page' => 20,
		'paged'          => $paged,
		'post_status'    => 'publish',
		'meta_key'       => 'doc_date',
		'orderby'        => array( 'meta_value' => 'DESC', 'date' => 'DESC' ),
	);
	$lang = function_exists( 'qp_holdings_current_language' ) ? qp_holdings_current_language() : 'vi';
	$args = qp_holdings_add_investor_doc_query_lang( $args, $lang );
	$tax = array();
	if ( $section ) {
		$tax[] = qp_holdings_investor_section_tax_query( $section, $lang );
	}
	if ( $year ) {
		$tax[] = array( 'taxonomy' => 'qp_doc_year', 'field' => 'slug', 'terms' => $year );
	}
	if ( $type ) {
		$tax[] = array( 'taxonomy' => 'qp_doc_type', 'field' => 'slug', 'terms' => $type );
	}
	if ( $tax ) {
		$args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax );
	}

	$q = new WP_Query( $args );

	echo '<div class="qp-doc-section">';

	// Year filter — only show years that actually have posts in this section.
	$years_terms = array();
	if ( $section ) {
		$section_tax = array( qp_holdings_investor_section_tax_query( $section, $lang ) );
		$section_post_ids = get_posts(
			array(
				'post_type'      => 'qp_investor_doc',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'lang'           => $lang,
				'tax_query'      => $section_tax,
			)
		);
		if ( empty( $section_post_ids ) && 'vi' !== $lang ) {
			$section_post_ids = get_posts(
				array(
					'post_type'      => 'qp_investor_doc',
					'posts_per_page' => -1,
					'post_status'    => 'publish',
					'fields'         => 'ids',
					'lang'           => 'vi',
					'tax_query'      => array(
						array(
							'taxonomy' => 'qp_investor_section',
							'field'    => 'slug',
							'terms'    => $section,
						),
					),
				)
			);
		}
		if ( ! empty( $section_post_ids ) ) {
			$years_terms = get_terms(
				array(
					'taxonomy'   => 'qp_doc_year',
					'object_ids' => $section_post_ids,
					'hide_empty' => true,
					'orderby'    => 'name',
					'order'      => 'DESC',
				)
			);
		}
	}
	if ( ! empty( $years_terms ) && ! is_wp_error( $years_terms ) ) {
		echo '<div class="qp-doc-filter">';
		echo '<div class="qp-filter-group__label">' . esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'year' ) : 'Năm' ) . '</div>';
		echo '<div class="qp-filter">';
		$base             = qp_holdings_filter_base_url( array( 'year' ) );
		$filter_query_var = qp_holdings_filter_query_var( 'year' );
		$cls              = '' === $year ? ' is-active' : '';
		echo '<a href="' . esc_url( $base ) . '" class="qp-filter__pill' . $cls . '">' . esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'all' ) : 'Tất cả' ) . '</a>';
		foreach ( $years_terms as $term ) {
			$url = add_query_arg( $filter_query_var, $term->slug, $base );
			$c   = $year === $term->slug ? ' is-active' : '';
			echo '<a href="' . esc_url( $url ) . '" class="qp-filter__pill' . esc_attr( $c ) . '">' . esc_html( $term->name ) . '</a>';
		}
		echo '</div>';
		echo '</div>';
	}

	if ( ! $q->have_posts() ) {
		echo '<p class="qp-empty">' . esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'no_docs' ) : 'Chưa có tài liệu nào trong mục này.' ) . '</p>';
		echo '</div>';
		wp_reset_postdata();
		return false;
	}

	echo '<div class="qp-doc-table">';
	echo '<div class="qp-doc-table__head">';
	echo '<div class="qp-doc-table__col-title">' . esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'document' ) : 'Tài liệu' ) . '</div>';
	echo '<div class="qp-doc-table__col-year">' . esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'year' ) : 'Năm' ) . '</div>';
	echo '<div class="qp-doc-table__col-date">' . esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'release_date' ) : 'Ngày phát hành' ) . '</div>';
	echo '<div class="qp-doc-table__col-size">' . esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'size' ) : 'Kích thước' ) . '</div>';
	echo '<div class="qp-doc-table__col-action">' . esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'action' ) : 'Hành động' ) . '</div>';
	echo '</div>';

	$current_year = '';

	while ( $q->have_posts() ) {
		$q->the_post();
		$id        = get_the_ID();
		$post_lang = qp_holdings_get_doc_post_language( $id );
		if ( $post_lang !== $lang ) {
			continue;
		}
		$date = qp_holdings_acf_or_meta( $id, 'doc_date', 'doc_date' );
		$year_meta = qp_holdings_acf_or_meta( $id, 'doc_year', 'doc_year' );
		$year_label = $year_meta ?: qp_holdings_year_from( $date );
		$size = (int) qp_holdings_acf_or_meta( $id, 'doc_size', 'doc_size' );
		$file = qp_holdings_acf_or_meta( $id, 'doc_file', 'doc_file' );
		$raw_file = get_post_meta( $id, 'doc_file', true );
		$url  = qp_holdings_acf_or_meta( $id, 'doc_url', 'doc_url' );
		$language = qp_holdings_acf_or_meta( $id, 'doc_language', 'doc_language' );

		$link = '';
		$has_external_url = is_string( $url ) && $url;
		if ( $has_external_url ) {
			$link = $url;
		} elseif ( is_array( $file ) && ! empty( $file['url'] ) ) {
			$link = $file['url'];
		} elseif ( is_numeric( $file ) ) {
			$link = wp_get_attachment_url( (int) $file );
			if ( ! $size ) {
				$path = get_attached_file( (int) $file );
				if ( $path && file_exists( $path ) ) {
					$size = filesize( $path );
				}
			}
		} elseif ( is_string( $file ) && $file ) {
			$link = $file;
		}
		if ( ! $size && ! $has_external_url && $file ) {
			$size = qp_holdings_investor_doc_file_size( $file, $raw_file );
		}
		if ( ! $link ) {
			$link = get_permalink( $id );
		}

		$type_slug = qp_holdings_first_term_name( $id, 'qp_doc_type' );
		$type_slug = $type_slug ? strtolower( $type_slug ) : qp_holdings_doc_type_from( $link );

		$disp_year = $year_label ?: '—';
		if ( $disp_year !== $current_year ) {
			if ( $current_year !== '' ) {
				echo '</div></div>'; // close .qp-doc-table-group__items and .qp-doc-table-group
			}
			$current_year = $disp_year;
			echo '<div class="qp-doc-table-group">';
			echo '<div class="qp-doc-table-group__header">';
			echo '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M19 4H5C3.89543 4 3 4.89543 3 6V20C3 21.1046 3.89543 22 5 22H19C20.1046 22 21 21.1046 21 20V6C21 4.89543 20.1046 4 19 4Z" stroke="#0B1B2A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 2V6" stroke="#0B1B2A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 2V6" stroke="#0B1B2A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 10H21" stroke="#0B1B2A" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
			echo '<span>' . esc_html( $current_year ) . '</span>';
			echo '</div>';
			echo '<div class="qp-doc-table-group__items">';
		}

		echo '<div class="qp-doc-row">';
		echo '<div class="qp-doc-row__title">';
		echo qp_holdings_doc_icon_svg( $type_slug ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<div class="qp-doc-row__title-inner">';
		echo '<a class="qp-doc-row__link" href="' . esc_url( $link ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( get_the_title( $id ) ) . '</a>';
		
		echo '<div class="qp-doc-row__mobile-meta">';
		if ( $language ) {
			if ( function_exists( 'qp_holdings_t' ) ) {
				$lang_label = array(
					'vi'        => qp_holdings_t( 'doc_lang_vi' ),
					'en'        => qp_holdings_t( 'doc_lang_en' ),
					'zh'        => qp_holdings_t( 'doc_lang_zh' ),
					'bilingual' => qp_holdings_t( 'doc_lang_bilingual' ),
				);
			} else {
				$lang_label = array( 'vi' => 'Tiếng Việt', 'en' => 'English', 'zh' => '中文', 'bilingual' => 'Song ngữ' );
			}
			$label = isset( $lang_label[ $language ] ) ? $lang_label[ $language ] : $language;
			if ( 'vi' === $language ) {
				$label = 'Tiếng Việt';
			}
			echo '<span class="qp-doc-row__lang">' . esc_html( $label ) . '</span>';
		}
		$size_str = qp_holdings_format_filesize( $size );
		if ( $size_str ) {
			echo '<span class="qp-doc-row__size-mobile"> (' . esc_html( $size_str ) . ')</span>';
		}
		echo '</div>';
		
		echo '</div></div>';

		echo '<div class="qp-doc-row__year">' . esc_html( $disp_year ) . '</div>';
		echo '<div class="qp-doc-row__date">' . esc_html( qp_holdings_format_date( $date ) ?: '—' ) . '</div>';
		echo '<div class="qp-doc-row__size">' . esc_html( $size_str ?: '—' ) . '</div>';
		$download_label = function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'download' ) : 'Dowload'; // Matching the Figma text exactly
		echo '<div class="qp-doc-row__action"><a class="qp-doc-row__btn" href="' . esc_url( $link ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr( $download_label ) . ' ' . esc_attr( get_the_title( $id ) ) . '">';
		echo '<span>' . esc_html( $download_label ) . '</span>';
		echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M14 10V12.6667C14 13.0203 13.8595 13.3594 13.6095 13.6095C13.3594 13.8595 13.0203 14 12.6667 14H3.33333C2.97971 14 2.64057 13.8595 2.39052 13.6095C2.14048 13.3594 2 13.0203 2 12.6667V10M4.66667 6.66667L8 10M8 10L11.3333 6.66667M8 10V2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
		echo '</a></div>';
		echo '</div>';
	}
	if ( $current_year !== '' ) {
		echo '</div></div>'; // close last group
	}

	echo '</div>'; // table

	qp_holdings_pagination( $q );

	echo '</div>'; // section
	wp_reset_postdata();
	return true;
}

/* ===================================================================
 * Seed: investor docs are now imported from qpxanh.com.vn via
 * bin/import-investor-docs.php. This function is a no-op to prevent
 * old mock data (sample.pdf) from being re-created on setup.
 * =================================================================== */
function qp_holdings_seed_investor_docs( bool $force = false ): void {
	// No-op — real data comes from the WP-CLI importer.
	// Keep this function signature so qp_holdings_seed_all() still works.
}

/**
 * Folder/document icon used in the governance card list (Figma 2769:6080).
 */
function qp_holdings_governance_folder_icon(): string {
	$svg = get_template_directory() . '/assets/site/investor-doc-folder.svg';
	if ( file_exists( $svg ) ) {
		$markup = file_get_contents( $svg );
		if ( $markup ) {
			return str_replace( '<svg ', '<svg class="qp-gov-card__icon-svg" aria-hidden="true" ', $markup );
		}
	}
	return '';
}

/**
 * PDF/file icon used in the governance detail page file list (Figma 2769:8556).
 */
function qp_holdings_governance_pdf_icon(): string {
	return '<svg class="qp-gov-detail-row__icon-svg" width="40" height="48" viewBox="0 0 40 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">'
		. '<path d="M4 4C4 1.79086 5.79086 0 8 0H24L40 16V44C40 46.2091 38.2091 48 36 48H8C5.79086 48 4 46.2091 4 44V4Z" fill="#FEF2F2"/>'
		. '<path d="M24 0L40 16H28C25.7909 16 24 14.2091 24 12V0Z" fill="#FECACA"/>'
		. '<rect x="4" y="28" width="22" height="14" rx="2" fill="#DC2626"/>'
		. '<text x="15" y="38" text-anchor="middle" font-family="Noto Sans, sans-serif" font-size="8" font-weight="700" fill="#fff" letter-spacing="0.5">PDF</text>'
		. '</svg>';
}

/**
 * Resolve a downloadable URL + size for a single qp_investor_doc post.
 *
 * @return array{url:string,size:int}
 */
function qp_holdings_investor_doc_link_and_size( int $post_id ): array {
	$size     = (int) qp_holdings_acf_or_meta( $post_id, 'doc_size', 'doc_size' );
	$file     = qp_holdings_acf_or_meta( $post_id, 'doc_file', 'doc_file' );
	$raw_file = get_post_meta( $post_id, 'doc_file', true );
	$url      = qp_holdings_acf_or_meta( $post_id, 'doc_url', 'doc_url' );

	$link = '';
	$has_external_url = is_string( $url ) && $url;
	if ( $has_external_url ) {
		$link = $url;
	} elseif ( is_array( $file ) && ! empty( $file['url'] ) ) {
		$link = $file['url'];
	} elseif ( is_numeric( $file ) ) {
		$link = wp_get_attachment_url( (int) $file );
		if ( ! $size ) {
			$path = get_attached_file( (int) $file );
			if ( $path && file_exists( $path ) ) {
				$size = filesize( $path );
			}
		}
	} elseif ( is_string( $file ) && $file ) {
		$link = $file;
	}
	if ( ! $size && ! $has_external_url && $file ) {
		$size = qp_holdings_investor_doc_file_size( $file, $raw_file );
	}

	return array( 'url' => (string) $link, 'size' => (int) $size );
}

/**
 * Translate a doc language slug to a human-readable label.
 */
function qp_holdings_doc_language_label( string $code ): string {
	if ( '' === $code ) {
		$code = 'vi';
	}
	if ( function_exists( 'qp_holdings_t' ) ) {
		$labels = array(
			'vi'        => qp_holdings_t( 'doc_lang_vi' ),
			'en'        => qp_holdings_t( 'doc_lang_en' ),
			'zh'        => qp_holdings_t( 'doc_lang_zh' ),
			'bilingual' => qp_holdings_t( 'doc_lang_bilingual' ),
		);
		if ( isset( $labels[ $code ] ) && $labels[ $code ] !== 'doc_lang_' . $code ) {
			return $labels[ $code ];
		}
	}
	$fallback = array(
		'vi'        => 'Tiếng việt',
		'en'        => 'English',
		'zh'        => '中文',
		'bilingual' => 'Song ngữ',
	);
	return $fallback[ $code ] ?? $code;
}

/**
 * Read the ACF "doc_files" repeater (or its raw post-meta form when ACF is
 * not active). Each row is normalised to title/language/file/url keys.
 *
 * @return array<int, array<string, mixed>>
 */
function qp_holdings_doc_files_repeater( int $post_id ): array {
	$rows = array();
	$count = (int) get_post_meta( $post_id, 'doc_files', true );
	if ( $count > 0 ) {
		for ( $i = 0; $i < $count; $i++ ) {
			$prefix = 'doc_files_' . $i . '_';
			$file   = get_post_meta( $post_id, $prefix . 'file', true );
			$file_id = (int) $file;
			if ( $file_id ) {
				$file = array(
					'ID'  => $file_id,
					'url' => wp_get_attachment_url( $file_id ),
				);
			}
			$row_url = (string) get_post_meta( $post_id, $prefix . 'url', true );
			$row_title = (string) get_post_meta( $post_id, $prefix . 'title', true );
			$row_lang = (string) get_post_meta( $post_id, $prefix . 'language', true ) ?: 'vi';
			if ( ! empty( $file ) || ! empty( $row_url ) ) {
				$rows[] = array(
					'title'    => $row_title,
					'language' => $row_lang,
					'file'     => $file,
					'url'      => $row_url,
				);
			}
		}
		if ( ! empty( $rows ) ) {
			return $rows;
		}
	}

	if ( function_exists( 'get_field' ) ) {
		$acf_rows = get_field( 'doc_files', $post_id );
		if ( is_array( $acf_rows ) && ! empty( $acf_rows ) ) {
			return $acf_rows;
		}
	}

	return $rows;
}

/**
 * Collect all downloadable files for a governance doc.
 *
 * Combines the primary doc_file/doc_url with any rows in the doc_files
 * repeater. Always returns at least one row when a primary file exists.
 *
 * @return array<int,array{title:string,language:string,url:string,size:int}>
 */
function qp_holdings_governance_doc_files( int $post_id ): array {
	$rows = array();
	$seen_urls = array();

	// 1. Check primary file/URL first
	$primary = qp_holdings_investor_doc_link_and_size( $post_id );
	if ( ! empty( $primary['url'] ) ) {
		$primary_url = $primary['url'];
		$seen_urls[ $primary_url ] = true;
		$rows[] = array(
			'title'    => get_the_title( $post_id ),
			'language' => (string) qp_holdings_acf_or_meta( $post_id, 'doc_language', 'doc_language' ) ?: 'vi',
			'url'      => $primary_url,
			'size'     => $primary['size'],
		);
	}

	// 2. Add all repeater rows
	$repeater = qp_holdings_doc_files_repeater( $post_id );
	if ( ! empty( $repeater ) ) {
		foreach ( $repeater as $row ) {
			$row_url   = '';
			$row_size  = 0;
			$row_lang  = isset( $row['language'] ) ? (string) $row['language'] : 'vi';
			$row_title = isset( $row['title'] ) ? trim( (string) $row['title'] ) : '';

			if ( ! empty( $row['url'] ) && is_string( $row['url'] ) ) {
				$row_url = $row['url'];
			} elseif ( ! empty( $row['file'] ) ) {
				$file = $row['file'];
				if ( is_array( $file ) && ! empty( $file['url'] ) ) {
					$row_url  = (string) $file['url'];
					$row_size = isset( $file['filesize'] ) ? (int) $file['filesize'] : 0;
					if ( ! $row_size && ! empty( $file['ID'] ) ) {
						$path = get_attached_file( (int) $file['ID'] );
						if ( $path && file_exists( $path ) ) {
							$row_size = filesize( $path );
						}
					}
				} elseif ( is_numeric( $file ) ) {
					$row_url  = (string) wp_get_attachment_url( (int) $file );
					$path     = get_attached_file( (int) $file );
					$row_size = ( $path && file_exists( $path ) ) ? filesize( $path ) : 0;
				} elseif ( is_string( $file ) && $file ) {
					$row_url = $file;
				}
			}

			if ( ! $row_url || isset( $seen_urls[ $row_url ] ) ) {
				continue;
			}
			$seen_urls[ $row_url ] = true;

			if ( '' === $row_title ) {
				$row_title = get_the_title( $post_id );
			}

			$rows[] = array(
				'title'    => $row_title,
				'language' => $row_lang ?: 'vi',
				'url'      => $row_url,
				'size'     => $row_size,
			);
		}
	}

	return $rows;
}

/**
 * Governance card-list renderer (matches Figma 2769:6080).
 *
 * Each post in the section becomes a single card with:
 *   – folder icon
 *   – title
 *   – "Tiếng việt" sub-label
 *   – right-aligned "Xem chi tiết" link to the detail page
 */
function qp_holdings_render_governance_card_list( string $section ): bool {
	$year  = qp_holdings_get_query_string( 'year' );
	$paged = max( 1, (int) get_query_var( 'paged' ) );
	$lang  = function_exists( 'qp_holdings_current_language' ) ? qp_holdings_current_language() : 'vi';

	$section_tax = qp_holdings_investor_section_tax_query( $section, $lang );

	$tax = array( $section_tax );
	if ( $year ) {
		$tax[] = array( 'taxonomy' => 'qp_doc_year', 'field' => 'slug', 'terms' => $year );
	}

	$args = array(
		'post_type'      => 'qp_investor_doc',
		'posts_per_page' => 12,
		'paged'          => $paged,
		'post_status'    => 'publish',
		'orderby'        => array( 'date' => 'DESC' ),
		'tax_query'      => array_merge( array( 'relation' => 'AND' ), $tax ),
	);
	$args = qp_holdings_add_investor_doc_query_lang( $args, $lang );

	$q = new WP_Query( $args );

	echo '<div class="qp-doc-section qp-gov-section">';

	if ( ! $q->have_posts() ) {
		echo '<p class="qp-empty">' . esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'no_docs' ) : 'Chưa có tài liệu nào trong mục này.' ) . '</p>';
		echo '</div>';
		wp_reset_postdata();
		return false;
	}

	echo '<div class="qp-gov-card-list">';

	$details_label = function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'view_details' ) : 'Xem chi tiết';
	$rendered_count = 0;

	while ( $q->have_posts() ) {
		$q->the_post();
		$id        = get_the_ID();
		$post_lang = qp_holdings_get_doc_post_language( $id );
		if ( $post_lang !== $lang ) {
			continue;
		}
		$rendered_count++;

		$lang_label   = qp_holdings_doc_language_label( $post_lang );
		$detail_url   = get_permalink( $id );
		$date_raw     = qp_holdings_acf_or_meta( $id, 'doc_date', 'doc_date' );
		$date_label   = qp_holdings_format_date( $date_raw );

		echo '<article class="qp-gov-card">';
		echo '<div class="qp-gov-card__icon" aria-hidden="true">' . qp_holdings_governance_folder_icon() . '</div>';
		echo '<div class="qp-gov-card__body">';
		echo '<h3 class="qp-gov-card__title"><a href="' . esc_url( $detail_url ) . '">' . esc_html( get_the_title( $id ) ) . '</a></h3>';
		echo '<p class="qp-gov-card__lang">' . esc_html( $lang_label ) . '</p>';
		echo '</div>';
		echo '<div class="qp-gov-card__meta">';
		if ( $date_label ) {
			echo '<span class="qp-gov-card__date">';
			echo '<svg class="qp-gov-card__date-icon" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">';
			echo '<path d="M5.333 1.333v2M10.667 1.333v2M2.333 6.06h11.334M14 5.667v5c0 1.667-.833 2.778-2.833 2.778H4.833C2.833 13.445 2 12.334 2 10.667v-5C2 4 2.833 2.889 4.833 2.889h6.334C13.167 2.889 14 4 14 5.667Z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>';
			echo '<path d="M7.997 9.133h.006M5.527 9.133h.006M5.527 11.133h.006" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>';
			echo '</svg>';
			echo '<time class="qp-gov-card__date-text" datetime="' . esc_attr( $date_label ) . '">' . esc_html( $date_label ) . '</time>';
			echo '</span>';
		}
		echo '<a class="qp-gov-card__cta" href="' . esc_url( $detail_url ) . '" aria-label="' . esc_attr( $details_label . ' — ' . get_the_title( $id ) ) . '">';
		echo '<span class="qp-gov-card__cta-text">' . esc_html( $details_label ) . '</span>';
		echo '<svg class="qp-gov-card__cta-icon" width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">';
		echo '<path d="M6.75 3.75 12 9l-5.25 5.25" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>';
		echo '</svg>';
		echo '</a>';
		echo '</div>';
		echo '</article>';
	}

	echo '</div>'; // .qp-gov-card-list

	qp_holdings_pagination( $q );

	echo '</div>'; // .qp-doc-section
	wp_reset_postdata();
	return true;
}

/* ==================================================================
 * Admin: "Tệp tin bổ sung" metabox (native — works without ACF Pro)
 *
 * ACF free does not ship the Repeater field, so the multi-file list
 * is implemented as a native metabox. It writes the same meta keys the
 * front-end reader (qp_holdings_doc_files_repeater) already understands:
 *   doc_files                 → row count (int)
 *   doc_files_{i}_title       → string
 *   doc_files_{i}_language    → vi|en|zh|bilingual
 *   doc_files_{i}_file        → attachment ID (int)
 *   doc_files_{i}_url         → external URL (optional)
 * ================================================================== */

/**
 * Language choices shared by the multi-file rows.
 *
 * @return array<string,string>
 */
function qp_holdings_doc_file_lang_choices(): array {
	return array(
		'vi'        => 'Tiếng Việt',
		'en'        => 'Tiếng Anh',
		'zh'        => 'Tiếng Trung',
		'bilingual' => 'Song ngữ',
	);
}

/**
 * Register the multi-file metabox on the investor-doc editor.
 */
function qp_holdings_register_doc_files_metabox(): void {
	add_meta_box(
		'qp_doc_files_box',
		'Tệp tin đính kèm',
		'qp_holdings_render_doc_files_metabox',
		'qp_investor_doc',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'qp_holdings_register_doc_files_metabox' );

/**
 * Enqueue the WP media library on the investor-doc editor screen.
 */
function qp_holdings_doc_files_admin_assets( string $hook ): void {
	if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'qp_investor_doc' !== $screen->post_type ) {
		return;
	}
	wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'qp_holdings_doc_files_admin_assets' );

/**
 * Build the normalised rows for the metabox UI from existing meta.
 *
 * @return array<int,array{title:string,language:string,file:int,url:string,filename:string}>
 */
function qp_holdings_doc_files_admin_rows( int $post_id ): array {
	$count = (int) get_post_meta( $post_id, 'doc_files', true );
	$rows  = array();
	for ( $i = 0; $i < $count; $i++ ) {
		$prefix   = 'doc_files_' . $i . '_';
		$file_id  = (int) get_post_meta( $post_id, $prefix . 'file', true );
		$filename = $file_id ? wp_basename( (string) get_attached_file( $file_id ) ) : '';
		$rows[]   = array(
			'title'    => (string) get_post_meta( $post_id, $prefix . 'title', true ),
			'language' => (string) get_post_meta( $post_id, $prefix . 'language', true ) ?: 'vi',
			'file'     => $file_id,
			'url'      => (string) get_post_meta( $post_id, $prefix . 'url', true ),
			'filename' => $filename,
		);
	}
	return $rows;
}

/**
 * Render a single editable file row. $index may be "__i__" for the JS template.
 *
 * @param array{title:string,language:string,file:int|string,url:string,filename:string} $row
 */
function qp_holdings_render_doc_files_row( $index, array $row ): void {
	$langs   = qp_holdings_doc_file_lang_choices();
	$name    = 'qp_doc_files[' . $index . ']';
	$file_id = (int) $row['file'];
	?>
	<div class="qp-dfx-row">
		<button type="button" class="qp-dfx-remove" aria-label="Xoá tệp tin">&times;</button>
		<div class="qp-dfx-grid">
			<label class="qp-dfx-field qp-dfx-field--title">
				<span>Tiêu đề</span>
				<input type="text" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( $row['title'] ); ?>" placeholder="Để trống sẽ dùng tiêu đề tài liệu">
			</label>
			<label class="qp-dfx-field qp-dfx-field--lang">
				<span>Ngôn ngữ</span>
				<select name="<?php echo esc_attr( $name ); ?>[language]">
					<?php foreach ( $langs as $code => $label ) : ?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $row['language'], $code ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<div class="qp-dfx-field qp-dfx-field--file">
				<span>Tệp tin</span>
				<div class="qp-dfx-file">
					<input type="hidden" class="qp-dfx-file-id" name="<?php echo esc_attr( $name ); ?>[file]" value="<?php echo esc_attr( $file_id ?: '' ); ?>">
					<span class="qp-dfx-file-name"><?php echo esc_html( $row['filename'] ?: 'Chưa chọn tệp tin' ); ?></span>
					<button type="button" class="button qp-dfx-file-pick"><?php echo $file_id ? 'Đổi tệp' : 'Chọn tệp'; ?></button>
					<button type="button" class="button-link qp-dfx-file-clear" <?php echo $file_id ? '' : 'style="display:none"'; ?>>Bỏ chọn</button>
				</div>
			</div>
			<label class="qp-dfx-field qp-dfx-field--url">
				<span>URL ngoài (tuỳ chọn)</span>
				<input type="url" name="<?php echo esc_attr( $name ); ?>[url]" value="<?php echo esc_attr( $row['url'] ); ?>" placeholder="https://… (ưu tiên hơn tệp đính kèm)">
			</label>
		</div>
	</div>
	<?php
}

/**
 * Render the metabox: existing rows + add button + JS template.
 */
function qp_holdings_render_doc_files_metabox( WP_Post $post ): void {
	wp_nonce_field( 'qp_doc_files_save', 'qp_doc_files_nonce' );
	$rows = qp_holdings_doc_files_admin_rows( $post->ID );
	?>
	<style>
		.qp-dfx-help{margin:0 0 12px;color:#555}
		.qp-dfx-row{position:relative;border:1px solid #dcdcde;border-radius:8px;padding:16px 40px 16px 16px;margin-bottom:12px;background:#fff}
		.qp-dfx-grid{display:grid;grid-template-columns:1fr 200px;gap:12px 16px}
		.qp-dfx-field{display:flex;flex-direction:column;gap:4px}
		.qp-dfx-field>span{font-weight:600;font-size:12px;color:#1d2327}
		.qp-dfx-field--file,.qp-dfx-field--url{grid-column:1 / -1}
		.qp-dfx-field input[type=text],.qp-dfx-field input[type=url],.qp-dfx-field select{width:100%}
		.qp-dfx-file{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
		.qp-dfx-file-name{color:#50575e;font-style:italic;word-break:break-all}
		.qp-dfx-remove{position:absolute;top:8px;right:8px;width:26px;height:26px;line-height:1;border:1px solid #dcdcde;border-radius:50%;background:#fff;color:#b32d2e;font-size:18px;cursor:pointer}
		.qp-dfx-remove:hover{background:#b32d2e;color:#fff;border-color:#b32d2e}
		.qp-dfx-add{margin-top:4px}
		.qp-dfx-empty{color:#646970;font-style:italic;margin:0 0 12px}
		@media(max-width:782px){.qp-dfx-grid{grid-template-columns:1fr}}
	</style>
	<p class="qp-dfx-help">Liệt kê tất cả tệp tin (PDF tiếng Việt, tiếng Anh, phụ lục…) sẽ hiển thị trên trang chi tiết tài liệu. Mỗi dòng là một tệp. Có thể chọn tệp từ Thư viện media hoặc dán URL ngoài.</p>

	<div id="qp-dfx-list">
		<?php
		if ( empty( $rows ) ) {
			echo '<p class="qp-dfx-empty" data-empty>Chưa có tệp tin nào. Bấm “Thêm tệp tin” để bắt đầu.</p>';
		} else {
			foreach ( $rows as $i => $row ) {
				qp_holdings_render_doc_files_row( $i, $row );
			}
		}
		?>
	</div>

	<button type="button" class="button button-secondary qp-dfx-add" id="qp-dfx-add">＋ Thêm tệp tin</button>

	<script type="text/html" id="qp-dfx-tmpl">
		<?php qp_holdings_render_doc_files_row( '__i__', array( 'title' => '', 'language' => 'vi', 'file' => '', 'url' => '', 'filename' => '' ) ); ?>
	</script>

	<script>
	(function(){
		var list = document.getElementById('qp-dfx-list');
		var addBtn = document.getElementById('qp-dfx-add');
		var tmpl = document.getElementById('qp-dfx-tmpl').innerHTML;
		var nextIndex = <?php echo (int) count( $rows ); ?>;

		function reindex(){
			var rows = list.querySelectorAll('.qp-dfx-row');
			rows.forEach(function(row, idx){
				row.querySelectorAll('input,select').forEach(function(el){
					if(!el.name) return;
					el.name = el.name.replace(/qp_doc_files\[[^\]]*\]/, 'qp_doc_files['+idx+']');
				});
			});
		}

		function addRow(){
			var empty = list.querySelector('[data-empty]');
			if(empty){ empty.remove(); }
			var wrap = document.createElement('div');
			wrap.innerHTML = tmpl.replace(/__i__/g, nextIndex);
			var node = wrap.firstElementChild;
			list.appendChild(node);
			nextIndex++;
			reindex();
			return node;
		}

		addBtn.addEventListener('click', function(){ addRow(); });

		list.addEventListener('click', function(e){
			// remove row
			if(e.target.classList.contains('qp-dfx-remove')){
				e.preventDefault();
				var row = e.target.closest('.qp-dfx-row');
				if(row){ row.remove(); reindex(); }
				if(!list.querySelector('.qp-dfx-row')){
					var p = document.createElement('p');
					p.className = 'qp-dfx-empty';
					p.setAttribute('data-empty','');
					p.textContent = 'Chưa có tệp tin nào. Bấm “Thêm tệp tin” để bắt đầu.';
					list.appendChild(p);
				}
				return;
			}
			// clear file
			if(e.target.classList.contains('qp-dfx-file-clear')){
				e.preventDefault();
				var box = e.target.closest('.qp-dfx-file');
				box.querySelector('.qp-dfx-file-id').value = '';
				box.querySelector('.qp-dfx-file-name').textContent = 'Chưa chọn tệp tin';
				box.querySelector('.qp-dfx-file-pick').textContent = 'Chọn tệp';
				e.target.style.display = 'none';
				return;
			}
			// pick file via media library
			if(e.target.classList.contains('qp-dfx-file-pick')){
				e.preventDefault();
				var fileBox = e.target.closest('.qp-dfx-file');
				var frame = wp.media({
					title: 'Chọn tệp tin',
					button: { text: 'Dùng tệp này' },
					multiple: false
				});
				frame.on('select', function(){
					var att = frame.state().get('selection').first().toJSON();
					fileBox.querySelector('.qp-dfx-file-id').value = att.id;
					fileBox.querySelector('.qp-dfx-file-name').textContent = att.filename || att.title || ('#'+att.id);
					fileBox.querySelector('.qp-dfx-file-pick').textContent = 'Đổi tệp';
					fileBox.querySelector('.qp-dfx-file-clear').style.display = '';
				});
				frame.open();
				return;
			}
		});
	})();
	</script>
	<?php
}

/**
 * Persist the multi-file metabox rows to meta the front-end can read.
 */
function qp_holdings_save_doc_files_metabox( int $post_id ): void {
	if ( ! isset( $_POST['qp_doc_files_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['qp_doc_files_nonce'] ) ), 'qp_doc_files_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( $parent_id = wp_is_post_revision( $post_id ) ) {
		$post_id = $parent_id;
	}
	if ( $parent_id = wp_is_post_autosave( $post_id ) ) {
		$post_id = $parent_id;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Remove all previous doc_files_* rows (and any leftover ACF reference keys).
	$old_count = (int) get_post_meta( $post_id, 'doc_files', true );
	for ( $i = 0; $i < $old_count; $i++ ) {
		$prefix = 'doc_files_' . $i . '_';
		foreach ( array( 'title', 'language', 'file', 'url' ) as $sub ) {
			delete_post_meta( $post_id, $prefix . $sub );
			delete_post_meta( $post_id, '_' . $prefix . $sub );
		}
	}
	delete_post_meta( $post_id, '_doc_files' );

	$submitted = isset( $_POST['qp_doc_files'] ) && is_array( $_POST['qp_doc_files'] )
		? wp_unslash( $_POST['qp_doc_files'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		: array();

	$lang_choices = qp_holdings_doc_file_lang_choices();
	$saved        = 0;

	foreach ( $submitted as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$file_id = isset( $row['file'] ) ? (int) $row['file'] : 0;
		$url     = isset( $row['url'] ) ? esc_url_raw( trim( (string) $row['url'] ) ) : '';

		// Skip rows that have neither a file nor a URL.
		if ( ! $file_id && '' === $url ) {
			continue;
		}

		$title = isset( $row['title'] ) ? sanitize_text_field( (string) $row['title'] ) : '';
		$lang  = isset( $row['language'] ) ? sanitize_key( (string) $row['language'] ) : 'vi';
		if ( ! isset( $lang_choices[ $lang ] ) ) {
			$lang = 'vi';
		}

		$prefix = 'doc_files_' . $saved . '_';
		update_post_meta( $post_id, $prefix . 'title', $title );
		update_post_meta( $post_id, $prefix . 'language', $lang );
		update_post_meta( $post_id, $prefix . 'file', $file_id );
		update_post_meta( $post_id, $prefix . 'url', $url );
		$saved++;
	}

	if ( $saved > 0 ) {
		update_post_meta( $post_id, 'doc_files', $saved );
	} else {
		delete_post_meta( $post_id, 'doc_files' );
	}
}
add_action( 'save_post_qp_investor_doc', 'qp_holdings_save_doc_files_metabox' );
