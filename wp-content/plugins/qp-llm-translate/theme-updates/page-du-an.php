<?php
/**
 * Template Name: QP Holdings - Dự án
 * Template Post Type: page
 */
defined( 'ABSPATH' ) || exit;

add_filter( 'body_class', static function ( array $classes ): array {
	$classes[] = 'page-projects';
	return $classes;
} );
?>
<?php get_header(); ?>
<?php $qp_assets = get_template_directory_uri() . '/assets/site'; ?>

<main class="projects-page">

	<!-- ═══ PAGE HERO ═══ -->
	<section class="page-hero projects-hero" aria-label="<?php echo esc_attr( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'home_about_title' ) : 'Dự án tiêu biểu' ); ?>">
		<div class="page-hero-bg" aria-hidden="true">
			<?php echo qp_holdings_hero_picture( 'projects', 'assets/site/projects-hero-bg-79abd3.png', array( 'alt' => '', 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' ), '100vw' ); ?>
			<div class="page-hero-overlay"></div>
		</div>
		<div class="container page-hero-breadcrumb-container">
			<nav class="page-breadcrumb reveal" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="page-breadcrumb-link"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'home' ) : 'Trang chủ' ); ?></a>
				<img src="<?php echo esc_url( $qp_assets . '/breadcrumb-arrow.svg' ); ?>" alt="" width="20" height="20" aria-hidden="true">
				<span class="page-breadcrumb-current"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'projects' ) : 'Dự án' ); ?></span>
			</nav>
		</div>

		<div class="container page-hero-content page-hero-content--centered">
			<h1 class="page-hero-title page-hero-title--gold reveal reveal-delay-1"<?php qp_holdings_te_attrs( 'projects' ); ?>><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'projects' ) : 'Dự án' ); ?></h1>
			<p class="page-hero-desc reveal reveal-delay-2"<?php qp_holdings_te_attrs( 'projects_hero_desc', array( 'multiline' => true ) ); ?>>
				<?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'projects_hero_desc' ) : 'Khám phá hệ sinh thái dự án bất động sản, khu đô thị, khu công nghiệp và các dự án tiêu biểu của QP Holdings.' ); ?>
			</p>
		</div>
	</section>

	<!-- ═══ PROJECT ARCHIVE ═══ -->
	<section class="projects-archive" aria-label="<?php echo esc_attr( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'projects_archive_label' ) : 'Danh sách dự án' ); ?>">
		<div class="container">
			<?php
			if ( function_exists( 'qp_holdings_render_project_archive' ) ) {
				qp_holdings_render_project_archive();
			}
			?>
		</div>
	</section>

</main>

<?php get_footer(); ?>
