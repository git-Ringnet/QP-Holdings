<?php
/**
 * Single template for governance documents (qp_investor_doc / company-governance).
 *
 * Matches Figma 2769:8556 — header + breadcrumb extended with the doc title,
 * then a stacked list of downloadable files (primary + repeater rows).
 *
 * Other investor sections still redirect straight to the file (see
 * qp_holdings_redirect_investor_doc_single() in inc/dynamic-content.php).
 */
defined( 'ABSPATH' ) || exit;

add_filter( 'body_class', static function ( array $classes ): array {
	$classes[] = 'page-investor-modern';
	$classes[] = 'investor-doc-single';
	return $classes;
} );

get_header();

$qp_assets    = get_template_directory_uri() . '/assets/site';
$qp_lang      = function_exists( 'qp_holdings_current_language' ) ? qp_holdings_current_language() : 'vi';
$qp_home_label = function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'home' ) : 'Trang chủ';
$qp_t = static function ( array $map ) use ( $qp_lang ): string {
	return $map[ $qp_lang ] ?? $map['vi'] ?? '';
};
$qp_hero_title = $qp_t(
	array(
		'vi' => 'Quan hệ nhà đầu tư',
		'en' => 'Investor Relations',
		'zh' => '投资者关系',
	)
);
$qp_hero_desc_key = 'investor_hero_desc';

$qp_section_label = $qp_t(
	array(
		'vi' => 'Quản trị công ty',
		'en' => 'Corporate Governance',
		'zh' => '公司治理',
	)
);
$qp_section_url = function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'governance' ) : home_url( '/' );

/**
 * Map qp_investor_section term slug → IR sub-page key + localised title.
 * Used for the breadcrumb so visitors land back on the correct listing.
 */
$qp_section_map = array(
	'investor-news' => array(
		'page_key' => 'newsletter',
		'titles'   => array( 'vi' => 'Bản tin nhà đầu tư', 'en' => 'Investor Newsletter', 'zh' => '投资者简报' ),
	),
	'ban-tin' => array(
		'page_key' => 'newsletter',
		'titles'   => array( 'vi' => 'Bản tin nhà đầu tư', 'en' => 'Investor Newsletter', 'zh' => '投资者简报' ),
	),
	'ban-tin-nha-dau-tu' => array(
		'page_key' => 'newsletter',
		'titles'   => array( 'vi' => 'Bản tin nhà đầu tư', 'en' => 'Investor Newsletter', 'zh' => '投资者简报' ),
	),
	'company-governance' => array(
		'page_key' => 'governance',
		'titles'   => array( 'vi' => 'Quản trị công ty', 'en' => 'Corporate Governance', 'zh' => '公司治理' ),
	),
	'agm' => array(
		'page_key' => 'agm',
		'titles'   => array( 'vi' => 'Đại hội đồng cổ đông', 'en' => 'General Meeting of Shareholders', 'zh' => '股东大会' ),
	),
	'annual-report' => array(
		'page_key' => 'annual_reports',
		'titles'   => array( 'vi' => 'Báo cáo thường niên', 'en' => 'Annual Reports', 'zh' => '年度报告' ),
	),
	'financial-report' => array(
		'page_key' => 'financial_reports',
		'titles'   => array( 'vi' => 'Báo cáo tài chính', 'en' => 'Financial Reports', 'zh' => '财务报告' ),
	),
	'governance-report' => array(
		'page_key' => 'governance_reports',
		'titles'   => array( 'vi' => 'Báo cáo quản trị công ty', 'en' => 'Corporate Governance Reports', 'zh' => '公司治理报告' ),
	),
	'disclosure' => array(
		'page_key' => 'disclosures',
		'titles'   => array( 'vi' => 'Công bố thông tin', 'en' => 'Disclosures', 'zh' => '信息披露' ),
	),
);

$qp_active_section_slug = '';
$post_id_for_breadcrumb = get_queried_object_id();
$doc_terms = wp_get_post_terms( $post_id_for_breadcrumb, 'qp_investor_section' );
if ( ! empty( $doc_terms ) && ! is_wp_error( $doc_terms ) ) {
	$qp_active_section_slug = $doc_terms[0]->slug;
}
if ( empty( $qp_active_section_slug ) && function_exists( 'qp_holdings_card_list_sections' ) ) {
	foreach ( qp_holdings_card_list_sections() as $candidate_slug ) {
		if ( has_term( $candidate_slug, 'qp_investor_section', $post_id_for_breadcrumb ) ) {
			$qp_active_section_slug = $candidate_slug;
			break;
		}
	}
}

if ( isset( $qp_section_map[ $qp_active_section_slug ] ) ) {
	$entry = $qp_section_map[ $qp_active_section_slug ];
	$qp_section_label = $entry['titles'][ $qp_lang ] ?? $entry['titles']['vi'];
	if ( function_exists( 'qp_holdings_page_url' ) ) {
		$qp_section_url = qp_holdings_page_url( $entry['page_key'] );
	}
}


$qp_tabs = array(
	'stock',
	'governance',
	'agm',
	'annual_reports',
	'financial_reports',
	'governance_reports',
	'disclosures',
	'newsletter',
);
$qp_defs = function_exists( 'qp_holdings_page_definitions' ) ? qp_holdings_page_definitions() : array();
$qp_page_key = $qp_section_map[ $qp_active_section_slug ]['page_key'] ?? 'newsletter';

while ( have_posts() ) :
	the_post();
	$qp_post_id = get_the_ID();
	$qp_files   = function_exists( 'qp_holdings_governance_doc_files' ) ? qp_holdings_governance_doc_files( $qp_post_id ) : array();
	$qp_title   = get_the_title( $qp_post_id );
	$qp_download_label = function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'download' ) : 'Tải';
	$qp_doc_date_raw   = qp_holdings_acf_or_meta( $qp_post_id, 'doc_date', 'doc_date' );
	$qp_doc_date       = function_exists( 'qp_holdings_format_date' ) ? qp_holdings_format_date( $qp_doc_date_raw ) : (string) $qp_doc_date_raw;
?>
<main class="investor-page investor-page--governance investor-page--detail">

	<section class="page-hero investor-hero" aria-label="<?php echo esc_attr( $qp_hero_title ); ?>">
		<div class="page-hero-bg" aria-hidden="true">
			<?php echo qp_holdings_hero_picture( 'investor', 'assets/site/about-hero-bg-62c368.png', array( 'alt' => '', 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' ), '100vw' ); ?>
			<div class="page-hero-overlay"></div>
		</div>
		<div class="container page-hero-breadcrumb-container">
			<nav class="page-breadcrumb reveal" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="page-breadcrumb-link"><?php echo esc_html( $qp_home_label ); ?></a>
				<img src="<?php echo esc_url( $qp_assets . '/breadcrumb-arrow.svg' ); ?>" alt="" width="20" height="20" aria-hidden="true">
				<a href="<?php echo esc_url( function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'investor' ) : home_url( '/' ) ); ?>" class="page-breadcrumb-link"><?php echo esc_html( $qp_hero_title ); ?></a>
				<img src="<?php echo esc_url( $qp_assets . '/breadcrumb-arrow.svg' ); ?>" alt="" width="20" height="20" aria-hidden="true">
				<a href="<?php echo esc_url( $qp_section_url ); ?>" class="page-breadcrumb-link"><?php echo esc_html( $qp_section_label ); ?></a>
				<img src="<?php echo esc_url( $qp_assets . '/breadcrumb-arrow.svg' ); ?>" alt="" width="20" height="20" aria-hidden="true">
				<span class="page-breadcrumb-current"><?php echo esc_html( $qp_title ); ?></span>
			</nav>
		</div>

		<div class="container page-hero-content page-hero-content--centered">
			<h1 class="page-hero-title page-hero-title--gold reveal reveal-delay-1"><?php echo esc_html( $qp_hero_title ); ?></h1>
			<p class="page-hero-desc reveal reveal-delay-2"<?php qp_holdings_te_attrs( $qp_hero_desc_key, array( 'multiline' => true ) ); ?>><?php echo esc_html( qp_holdings_t( $qp_hero_desc_key ) ); ?></p>
		</div>
	</section>

	<nav class="investor-tabs-wrap" aria-label="<?php echo esc_attr( $qp_hero_title ); ?>">
		<div class="container">
			<div class="investor-tabs">
				<button class="investor-tabs-mobile-trigger" id="investor-tabs-mobile-trigger"
					aria-expanded="false" aria-controls="investor-tabs-mobile-panel" type="button">
					<span class="investor-tabs-mobile-trigger__label"><?php echo esc_html( $qp_section_label ); ?></span>
					<svg class="investor-tabs-mobile-trigger__arrow" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
						<path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</button>

				<div class="investor-tabs-list" id="investor-tabs-mobile-panel">
					<?php foreach ( $qp_tabs as $qp_tab_key ) : ?>
						<?php
						$qp_tab_title = $qp_defs[ $qp_tab_key ][ $qp_lang ]['title'] ?? $qp_tab_key;
						$qp_tab_url   = function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( $qp_tab_key, $qp_lang ) : home_url( '/' );
						$is_ext       = false;
						if ( 'newsletter' === $qp_tab_key && ! empty( $qp_tab_url ) && 0 === strpos( $qp_tab_url, 'http' ) ) {
							$tab_host  = wp_parse_url( $qp_tab_url, PHP_URL_HOST );
							$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
							if ( $tab_host && $site_host && strtolower( $tab_host ) !== strtolower( $site_host ) ) {
								$is_ext = true;
							}
						}
						?>
						<a class="investor-tab<?php echo $qp_active ? ' is-active' : ''; ?>"
							href="<?php echo esc_url( $qp_tab_url ); ?>" <?php echo $qp_active ? ' aria-current="page"' : ''; ?><?php echo $is_ext ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
							<?php echo esc_html( $qp_tab_title ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</nav>

	<section class="investor-content qp-gov-detail" aria-label="<?php echo esc_attr( $qp_title ); ?>">
		<div class="container">
			<header class="qp-gov-detail__header">
				<h2 class="qp-gov-detail__title"><?php echo esc_html( $qp_title ); ?></h2>
			</header>

			<?php if ( ! empty( $qp_files ) ) : ?>
				<div class="qp-gov-detail__list">
					<?php foreach ( $qp_files as $file ) :
						$file_url   = $file['url'];
						$file_title = $file['title'] !== '' ? $file['title'] : $qp_title;
						$lang_label = qp_holdings_doc_language_label( $file['language'] );
						$size_label = ( ! empty( $file['size'] ) && function_exists( 'qp_holdings_format_filesize' ) ) ? qp_holdings_format_filesize( $file['size'] ) : '';
						?>
						<article class="qp-gov-detail-row">
							<div class="qp-gov-detail-row__icon" aria-hidden="true">
								<?php echo qp_holdings_governance_pdf_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</div>
							<div class="qp-gov-detail-row__body">
								<a class="qp-gov-detail-row__title" href="<?php echo esc_url( $file_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $file_title ); ?></a>
								<div class="qp-gov-detail-row__meta">
									<span class="qp-gov-meta-chip qp-gov-meta-chip--lang"><?php echo esc_html( $lang_label ); ?></span>
									<?php if ( '' !== $qp_doc_date ) : ?>
										<span class="qp-gov-meta-chip qp-gov-meta-chip--date">
											<svg class="qp-gov-meta-chip__icon" width="15" height="15" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
												<path d="M5.333 1.333v2M10.667 1.333v2M2.333 6.06h11.334M14 5.667v5c0 1.667-.833 2.778-2.833 2.778H4.833C2.833 13.445 2 12.334 2 10.667v-5C2 4 2.833 2.889 4.833 2.889h6.334C13.167 2.889 14 4 14 5.667Z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
											</svg>
											<time datetime="<?php echo esc_attr( $qp_doc_date ); ?>"><?php echo esc_html( $qp_doc_date ); ?></time>
										</span>
									<?php endif; ?>
									<?php if ( '' !== $size_label ) : ?>
										<span class="qp-gov-meta-chip qp-gov-meta-chip--size">
											<svg class="qp-gov-meta-chip__icon" width="15" height="15" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
												<path d="M9.333 1.333H4A1.333 1.333 0 0 0 2.667 2.667v10.666A1.333 1.333 0 0 0 4 14.667h8a1.333 1.333 0 0 0 1.333-1.334V5.333L9.333 1.333Z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
												<path d="M9.333 1.333v4h4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
											</svg>
											<?php echo esc_html( $size_label ); ?>
										</span>
									<?php endif; ?>
								</div>
							</div>
							<a class="qp-gov-detail-row__btn" href="<?php echo esc_url( $file_url ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( $qp_download_label . ' — ' . $file_title ); ?>">
								<span><?php echo esc_html( $qp_download_label ); ?></span>
								<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
									<path d="M14 10V12.6667C14 13.0203 13.8595 13.3594 13.6095 13.6095C13.3594 13.8595 13.0203 14 12.6667 14H3.33333C2.97971 14 2.64057 13.8595 2.39052 13.6095C2.14048 13.3594 2 13.0203 2 12.6667V10M4.66667 6.66667L8 10M8 10L11.3333 6.66667M8 10V2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
								</svg>
							</a>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="qp-empty">
					<?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'no_docs' ) : 'Chưa có tài liệu nào trong mục này.' ); ?>
				</p>
			<?php endif; ?>

			<?php if ( get_the_content() ) : ?>
				<div class="qp-gov-detail__content">
					<?php the_content(); ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

</main>
<?php
endwhile;
get_footer();
