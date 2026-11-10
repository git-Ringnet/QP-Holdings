<?php
defined( 'ABSPATH' ) || exit;

$post_type = get_post_type();

if ( 'post' === $post_type ) {
	$posts_page = (int) get_option( 'page_for_posts' );
	get_template_part(
		'template-parts/content-news-single',
		null,
		array(
			'crumb_label'       => qp_holdings_t( 'Bài viết' ),
			'hero_kicker'       => qp_holdings_t( 'TIN TỨC & CẬP NHẬT' ),
			'back_label'        => qp_holdings_t( 'Về danh sách bài viết' ),
			'back_url'          => $posts_page ? get_permalink( $posts_page ) : qp_holdings_page_url( 'news' ),
			'related_title'     => qp_holdings_t( 'Bài viết liên quan' ),
			'related_post_type' => 'post',
			'related_taxonomy'  => 'category',
		)
	);
	return;
}

get_header();
?>
<main id="main" class="qp-wp-fallback">
	<?php while ( have_posts() ) : the_post(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<h1><?php the_title(); ?></h1>
			<?php the_content(); ?>
		</article>
	<?php endwhile; ?>
</main>
<?php get_footer(); ?>
