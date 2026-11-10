<?php
/**
 * Template Name: QP Holdings - FAQ
 * Template Post Type: page
 */
defined( 'ABSPATH' ) || exit;

add_filter(
	'body_class',
	static function ( array $classes ): array {
		$classes[] = 'page-faq';
		return $classes;
	}
);
?>
<?php get_header(); ?>
<?php $qp_assets = get_template_directory_uri() . '/assets/site'; ?>

<main class="faq-page">
	<section class="faq-hero" aria-label="<?php echo esc_attr( qp_holdings_t( 'faq_page_title' ) ); ?>">
		<div class="faq-hero__media" aria-hidden="true">
			<?php echo qp_holdings_hero_picture( 'faq', 'assets/site/careers-hero-team-5fdb60.png', array( 'alt' => '', 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' ), '100vw' ); ?>
			<span class="faq-hero__overlay"></span>
		</div>
		<div class="container faq-hero__breadcrumb-wrap">
			<nav class="page-breadcrumb reveal" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( qp_holdings_page_url( 'home' ) ); ?>" class="page-breadcrumb-link"><?php echo esc_html( qp_holdings_t( 'home' ) ); ?></a>
				<img src="<?php echo esc_url( $qp_assets . '/breadcrumb-arrow.svg' ); ?>" alt="" width="20" height="20" aria-hidden="true">
				<span class="page-breadcrumb-current"><?php echo esc_html( qp_holdings_t( 'faq_page_title' ) ); ?></span>
			</nav>
		</div>
		<div class="container faq-hero__content">
			<h1 class="faq-hero__title reveal reveal-delay-1"<?php qp_holdings_te_attrs( 'faq_page_title' ); ?>><?php echo esc_html( qp_holdings_t( 'faq_page_title' ) ); ?></h1>
		</div>
	</section>

	<section class="faq-section" aria-label="<?php echo esc_attr( qp_holdings_t( 'faq_archive_label' ) ); ?>">
		<div class="container faq-section__inner">
			<div class="faq-section__list reveal">
				<?php
				if ( function_exists( 'qp_holdings_render_faq_accordion' ) ) {
					qp_holdings_render_faq_accordion();
				}
				?>
			</div>
			<figure class="faq-section__image reveal reveal-delay-1">
				<?php
				if ( function_exists( 'qp_holdings_render_faq_image' ) ) {
					qp_holdings_render_faq_image();
				}
				?>
			</figure>
		</div>
	</section>
</main>

<?php get_footer(); ?>
