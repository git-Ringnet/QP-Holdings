<?php
/**
 * Template Name: QP Holdings - Điều khoản sử dụng
 * Template Post Type: page
 */
defined( 'ABSPATH' ) || exit;

add_filter( 'body_class', static function ( array $classes ): array {
	$classes[] = 'page-terms';
	return $classes;
} );
?>
<?php get_header(); ?>
<?php $qp_assets = get_template_directory_uri() . '/assets/site'; ?>

<main class="terms-page">

	<!-- ═══ PAGE HERO ═══ -->
	<section class="page-hero terms-hero" aria-label="<?php echo esc_attr( qp_holdings_t( 'terms' ) ); ?>">
		<div class="page-hero-bg" aria-hidden="true">
			<?php echo qp_holdings_hero_picture( 'terms', 'assets/site/terms-hero-bg-55c050.png', array( 'alt' => '', 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' ), '100vw' ); ?>
			<div class="page-hero-overlay terms-hero-overlay"></div>
		</div>
		<div class="container page-hero-breadcrumb-container">
			<nav class="page-breadcrumb reveal" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( qp_holdings_page_url( 'home' ) ); ?>" class="page-breadcrumb-link"><?php echo esc_html( qp_holdings_t( 'home' ) ); ?></a>
				<img src="<?php echo esc_url( $qp_assets . '/breadcrumb-arrow.svg' ); ?>" alt="" width="20" height="20" aria-hidden="true">
				<span class="page-breadcrumb-current"><?php echo esc_html( qp_holdings_t( 'terms_page_title' ) ); ?></span>
			</nav>
		</div>
		<div class="container page-hero-content terms-hero-content">
			<h1 class="page-hero-title terms-hero-title reveal">
				<span class="gold-gradient-text"><?php echo esc_html( qp_holdings_t( 'terms_page_title' ) ); ?></span>
			</h1>
		</div>
	</section>

	<!-- ═══ TERMS CONTENT ═══ -->
	<section class="terms-content-section" aria-label="<?php echo esc_attr( qp_holdings_t( 'terms_page_title' ) ); ?>">
		<div class="container">
			<div class="terms-content reveal">
				<h2 class="terms-content-title"><?php echo esc_html( qp_holdings_t( 'terms_page_title' ) ); ?></h2>
				<div class="terms-body">
					<?php qp_holdings_render_legal_body( 'terms' ); ?>
				</div>
			</div>
		</div>
	</section>

</main>

<?php get_footer(); ?>
