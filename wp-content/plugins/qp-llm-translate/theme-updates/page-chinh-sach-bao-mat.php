<?php
/**
 * Template Name: QP Holdings - Chính sách bảo mật
 * Template Post Type: page
 */
defined( 'ABSPATH' ) || exit;

add_filter( 'body_class', static function ( array $classes ): array {
	$classes[] = 'page-privacy';
	return $classes;
} );
?>
<?php get_header(); ?>
<?php $qp_assets = get_template_directory_uri() . '/assets/site'; ?>

<main class="privacy-page">

	<!-- ═══ PAGE HERO ═══ -->
	<section class="page-hero privacy-hero" aria-label="<?php echo esc_attr( qp_holdings_t( 'privacy_page_title' ) ); ?>">
		<div class="page-hero-bg" aria-hidden="true">
			<?php echo qp_holdings_hero_picture( 'privacy', 'assets/site/terms-hero-bg-55c050.png', array( 'alt' => '', 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' ), '100vw' ); ?>
			<div class="page-hero-overlay privacy-hero-overlay"></div>
		</div>
		<div class="container page-hero-breadcrumb-container">
			<nav class="page-breadcrumb reveal" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( qp_holdings_page_url( 'home' ) ); ?>" class="page-breadcrumb-link"><?php echo esc_html( qp_holdings_t( 'home' ) ); ?></a>
				<img src="<?php echo esc_url( $qp_assets . '/breadcrumb-arrow.svg' ); ?>" alt="" width="20" height="20" aria-hidden="true">
				<span class="page-breadcrumb-current"><?php echo esc_html( qp_holdings_t( 'privacy_page_title' ) ); ?></span>
			</nav>
		</div>
		<div class="container page-hero-content privacy-hero-content">
			<h1 class="page-hero-title privacy-hero-title reveal">
				<span class="gold-gradient-text"<?php qp_holdings_te_attrs( 'privacy_page_title' ); ?>><?php echo esc_html( qp_holdings_t( 'privacy_page_title' ) ); ?></span>
			</h1>
			<p class="page-hero-desc privacy-hero-desc reveal reveal-delay-1"<?php qp_holdings_te_attrs( 'privacy_hero_desc' ); ?>>
				<?php echo esc_html( qp_holdings_t( 'privacy_hero_desc' ) ); ?>
			</p>
		</div>
	</section>

	<!-- ═══ PRIVACY CONTENT ═══ -->
	<section class="privacy-content-section" aria-label="<?php echo esc_attr( qp_holdings_t( 'privacy_page_title' ) ); ?>">
		<div class="container">
			<div class="privacy-content reveal">
				<h2 class="privacy-content-title"><?php echo esc_html( qp_holdings_t( 'privacy_content_title' ) ); ?></h2>
				<div class="privacy-body">
					<?php qp_holdings_render_legal_body( 'privacy' ); ?>
				</div>
			</div>
		</div>
	</section>

</main>

<?php get_footer(); ?>
