<?php
/**
 * Template Name: QP Holdings - Tin tức
 * Template Post Type: page
 */
defined( 'ABSPATH' ) || exit;

add_filter( 'body_class', static function ( array $classes ): array {
	$classes[] = 'page-news';
	return $classes;
} );
?>
<?php get_header(); ?>
<?php $qp_assets = get_template_directory_uri() . '/assets/site'; ?>
<?php
$news_cat   = function_exists( 'qp_holdings_get_query_string' ) ? qp_holdings_get_query_string( 'cat' ) : '';
$news_group = function_exists( 'qp_holdings_get_query_string' ) ? qp_holdings_get_query_string( 'group' ) : '';
$has_news_filter = (bool) ( $news_cat || $news_group );

// When filtering by a category group, surface its title in the hero so the
// page reads as "this category" rather than the generic news landing.
$active_group = ( $news_group && function_exists( 'qp_holdings_news_group_by_key' ) )
	? qp_holdings_news_group_by_key( $news_group )
	: null;
$active_term = $news_cat ? get_term_by( 'slug', $news_cat, 'qp_news_category' ) : null;
$active_term_title = '';
if ( $active_term instanceof WP_Term ) {
	$active_term_title = $active_term->name;
	if ( function_exists( 'qp_holdings_t' ) ) {
		$vi_slug = $active_term->slug;
		if ( function_exists( 'pll_get_term' ) ) {
			$vi_term_id = pll_get_term( (int) $active_term->term_id, 'vi' );
			if ( $vi_term_id ) {
				$vi_term = get_term( $vi_term_id, 'qp_news_category' );
				if ( $vi_term instanceof WP_Term ) {
					$vi_slug = $vi_term->slug;
				}
			}
		}
		$translated_term = qp_holdings_t( $vi_slug );
		if ( $translated_term !== $vi_slug ) {
			$active_term_title = $translated_term;
		}
	}
}
$hero_title = $active_group
	? $active_group['title']
	: ( $active_term_title ?: ( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'news_hero_title' ) : 'Truyền thông & tin tức' ) );
$hero_desc = function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'news_hero_desc' ) : 'Cập nhật tin tức, hoạt động doanh nghiệp, thị trường và quan hệ nhà đầu tư từ QP Holdings.';
$qp_custom_news_desc = '';
if ( ! $has_news_filter ) {
	$qp_news_page_id = get_queried_object_id();
	if ( ! $qp_news_page_id ) {
		$qp_news_page = get_page_by_path( 'tin-tuc' ) ?: get_page_by_path( 'tintuc' );
		if ( $qp_news_page instanceof WP_Post ) {
			$qp_news_page_id = (int) $qp_news_page->ID;
		}
	}
	if ( $qp_news_page_id ) {
		$qp_custom_news_desc = trim( (string) get_post_field( 'post_excerpt', $qp_news_page_id ) );
		if ( '' === $qp_custom_news_desc ) {
			$raw_content = trim( (string) get_post_field( 'post_content', $qp_news_page_id ) );
			if ( '' !== $raw_content && false === strpos( $raw_content, '[' ) ) {
				$qp_custom_news_desc = wp_strip_all_tags( $raw_content );
			}
		}
	}
	if ( '' !== $qp_custom_news_desc ) {
		$hero_desc = $qp_custom_news_desc;
	}
}
if ( $active_group && function_exists( 'qp_holdings_news_group_description' ) ) {
	$hero_desc = qp_holdings_news_group_description( $active_group['key'] );
} elseif ( $active_term instanceof WP_Term ) {
	$term_description = trim( wp_strip_all_tags( term_description( $active_term->term_id, $active_term->taxonomy ) ) );
	if ( '' !== $term_description ) {
		$hero_desc = $term_description;
	}
}
?>

<main class="news-page<?php echo $has_news_filter ? ' news-category-page' : ''; ?>">

	<!-- ═══ PAGE HERO ═══ -->
	<section class="page-hero news-hero" aria-label="<?php echo esc_attr( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'news_archive_label' ) : 'Tin tức QP Holdings' ); ?>">
		<div class="page-hero-bg" aria-hidden="true">
			<?php echo qp_holdings_hero_picture( 'news', 'assets/site/about-hero-bg-62c368.png', array( 'alt' => '', 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' ), '100vw' ); ?>
			<div class="page-hero-overlay"></div>
		</div>
		<div class="container page-hero-content page-hero-content--centered">
			<!-- Breadcrumb -->
			<nav class="page-breadcrumb reveal" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="page-breadcrumb-link"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'home' ) : 'Trang chủ' ); ?></a>
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
					<path d="M7.5 4.167L13.333 10 7.5 15.833" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
				<?php if ( $has_news_filter ) : ?>
					<a href="<?php echo esc_url( function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'news' ) : home_url( '/tintuc/' ) ); ?>" class="page-breadcrumb-link"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'news' ) : 'Tin tức' ); ?></a>
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
						<path d="M7.5 4.167L13.333 10 7.5 15.833" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<span class="page-breadcrumb-current"><?php echo esc_html( $hero_title ); ?></span>
				<?php else : ?>
					<span class="page-breadcrumb-current"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'news' ) : 'Tin tức' ); ?></span>
				<?php endif; ?>
			</nav>

			<h1 class="page-hero-title page-hero-title--gold reveal reveal-delay-1"<?php if ( ! $has_news_filter && ! $active_group && ! $active_term ) { qp_holdings_te_attrs( 'news_hero_title' ); } ?>>
				<?php echo esc_html( $hero_title ); ?>
			</h1>
			<p class="page-hero-desc reveal reveal-delay-2"<?php if ( '' === $qp_custom_news_desc && ! $active_group && ! ( $active_term instanceof WP_Term ) ) { qp_holdings_te_attrs( 'news_hero_desc', array( 'multiline' => true ) ); } ?>>
				<?php echo esc_html( $hero_desc ); ?>
			</p>
		</div>
	</section>

	<!-- ═══ NEWS SECTIONS (grouped by category) ═══ -->
	<?php
	if ( ( $news_cat || $news_group ) && function_exists( 'qp_holdings_render_site_news_archive' ) ) {
		qp_holdings_render_site_news_archive();
	} elseif ( function_exists( 'qp_holdings_render_news_by_category' ) ) {
		qp_holdings_render_news_by_category();
	}
	?>

</main>

<?php get_footer(); ?>
