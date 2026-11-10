<?php
/**
 * Template Name: QP Holdings - Hồ sơ doanh nghiệp
 * Template Post Type: page
 */
defined( 'ABSPATH' ) || exit;

add_filter( 'body_class', static function ( array $classes ): array {
	$classes[] = 'page-profile';
	return $classes;
} );

get_header();

$qp_assets = get_template_directory_uri() . '/assets/site';
$page_id   = get_queried_object_id();

$page_title_custom = $page_id ? trim( (string) get_post_field( 'post_title', $page_id ) ) : '';
$hero_title        = $page_title_custom ?: ( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'profile_page_title' ) : 'Hồ sơ doanh nghiệp' );

$page_excerpt_custom = $page_id ? trim( (string) get_post_field( 'post_excerpt', $page_id ) ) : '';
$hero_desc           = $page_excerpt_custom ?: ( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'profile_hero_desc' ) : 'Tổng quan về năng lực, tầm nhìn chiến lược, cơ cấu tổ chức và hành trình phát triển của Tập đoàn QP Holdings.' );
?>

<main class="profile-page">

	<!-- ═══ PAGE HERO ═══ -->
	<section class="page-hero profile-hero" aria-label="<?php echo esc_attr( $hero_title ); ?>">
		<div class="page-hero-bg" aria-hidden="true">
			<?php echo qp_holdings_hero_picture( 'profile', 'assets/site/projects-hero-bg-79abd3.png', array( 'alt' => '', 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' ), '100vw' ); ?>
			<div class="page-hero-overlay"></div>
		</div>
		<div class="container page-hero-breadcrumb-container">
			<nav class="page-breadcrumb reveal" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="page-breadcrumb-link"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'home' ) : 'Trang chủ' ); ?></a>
				<img src="<?php echo esc_url( $qp_assets . '/breadcrumb-arrow.svg' ); ?>" alt="" width="20" height="20" aria-hidden="true">
				<span class="page-breadcrumb-current"><?php echo esc_html( $hero_title ); ?></span>
			</nav>
		</div>

		<div class="container page-hero-content page-hero-content--centered">
			<h1 class="page-hero-title page-hero-title--gold reveal reveal-delay-1"<?php if ( ! $page_title_custom ) { qp_holdings_te_attrs( 'profile_page_title' ); } ?>><?php echo esc_html( $hero_title ); ?></h1>
			<p class="page-hero-desc reveal reveal-delay-2"<?php if ( ! $page_excerpt_custom ) { qp_holdings_te_attrs( 'profile_hero_desc', array( 'multiline' => true ) ); } ?>>
				<?php echo esc_html( $hero_desc ); ?>
			</p>
		</div>
	</section>

	<!-- ═══ PAGE CONTENT ═══ -->
	<section class="profile-content-section" style="padding: 60px 0 100px;">
		<div class="container">
			<?php while ( have_posts() ) : the_post(); ?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry-content' ); ?>>
					<?php the_content(); ?>
				</article>
			<?php endwhile; ?>
		</div>
	</section>

</main>

<?php get_footer(); ?>
