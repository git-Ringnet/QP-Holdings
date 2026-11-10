<?php
/**
 * Template Name: QP Holdings - Bản tin nhà đầu tư
 * Template Post Type: page
 */
defined( 'ABSPATH' ) || exit;

add_filter( 'body_class', static function ( array $classes ): array {
	$classes[] = 'page-investor-modern';
	return $classes;
} );

get_header();
get_template_part(
	'template-parts/investor-page',
	null,
	array(
		'page_key' => 'newsletter',
		'section'  => 'ban-tin',
	)
);
get_footer();
