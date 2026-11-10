<?php
/**
 * qp_news_category archive template.
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'body_class',
	static function ( array $classes ): array {
		$classes[] = 'page-news';
		$classes[] = 'tax-news-category';
		return $classes;
	}
);

$term = get_queried_object();
if ( ! $term instanceof WP_Term ) {
	$term = null;
}

$qp_assets        = get_template_directory_uri() . '/assets/site';
$term_name = '';
if ( $term ) {
	if ( function_exists( 'qp_holdings_t' ) ) {
		$vi_slug = $term->slug;
		if ( function_exists( 'pll_get_term' ) ) {
			$vi_term_id = pll_get_term( (int) $term->term_id, 'vi' );
			if ( $vi_term_id ) {
				$vi_term = get_term( $vi_term_id, 'qp_news_category' );
				if ( $vi_term instanceof WP_Term ) {
					$vi_slug = $vi_term->slug;
				}
			}
		}
		$term_name = qp_holdings_t( $vi_slug );
	}
	if ( ! $term_name || $term_name === $term->slug ) {
		$term_name = $term->name;
	}
} else {
	$term_name = function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'news' ) : 'Tin tức';
}
$term_description = $term ? trim( wp_strip_all_tags( term_description( $term->term_id, $term->taxonomy ) ) ) : '';
if ( '' === $term_description ) {
	$term_description = function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'news_hero_desc' ) : 'Cập nhật tin tức, hoạt động doanh nghiệp, thị trường và quan hệ nhà đầu tư từ QP Holdings.';
}

get_header();
?>

<main class="news-page news-category-page">
	<section class="page-hero news-hero" aria-label="<?php echo esc_attr( $term_name ); ?>">
		<div class="page-hero-bg" aria-hidden="true">
			<?php echo qp_holdings_theme_picture( 'assets/site/about-hero-bg-62c368.png', array( 'alt' => '', 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' ), '100vw' ); ?>
			<div class="page-hero-overlay"></div>
		</div>
		<div class="container page-hero-content page-hero-content--centered">
			<nav class="page-breadcrumb reveal" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="page-breadcrumb-link"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'home' ) : 'Trang chủ' ); ?></a>
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
					<path d="M7.5 4.167L13.333 10 7.5 15.833" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
				<a href="<?php echo esc_url( function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'news' ) : home_url( '/tintuc/' ) ); ?>" class="page-breadcrumb-link"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'news' ) : 'Tin tức' ); ?></a>
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
					<path d="M7.5 4.167L13.333 10 7.5 15.833" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
				<span class="page-breadcrumb-current"><?php echo esc_html( $term_name ); ?></span>
			</nav>

			<h1 class="page-hero-title page-hero-title--gold reveal reveal-delay-1">
				<?php echo esc_html( $term_name ); ?>
			</h1>
			<p class="page-hero-desc reveal reveal-delay-2">
				<?php echo esc_html( $term_description ); ?>
			</p>
		</div>
	</section>

	<section class="news-category-archive" aria-label="<?php echo esc_attr( $term_name ); ?>">
		<div class="container">
			<?php
			if ( $term && function_exists( 'qp_holdings_render_news_category_archive' ) ) {
				qp_holdings_render_news_category_archive( $term );
			} else {
				echo '<p class="news-empty">' . esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'no_posts' ) : 'Chưa có bài viết nào.' ) . '</p>';
			}
			?>
		</div>
	</section>
</main>

<?php get_footer(); ?>
