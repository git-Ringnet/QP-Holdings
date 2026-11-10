<?php
defined( 'ABSPATH' ) || exit;
get_header();
?>
<main id="main" class="qp-wp-fallback">
    <?php if ( have_posts() ) : ?>
        <?php while ( have_posts() ) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <h1><?php the_title(); ?></h1>
                <?php the_content(); ?>
            </article>
        <?php endwhile; ?>
    <?php else : ?>
        <h1><?php esc_html_e( 'QP Holdings', 'qp-holdings' ); ?></h1>
        <p><?php esc_html_e( 'Trang chưa có nội dung.', 'qp-holdings' ); ?></p>
    <?php endif; ?>
</main>
<?php get_footer(); ?>
