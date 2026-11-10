<?php
defined( 'ABSPATH' ) || exit;

get_template_part(
	'template-parts/content-news-single',
	null,
	array(
		'crumb_label'       => function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'news' ) : 'Tin tức',
		'hero_kicker'       => function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'news_kicker' ) : 'TIN TỨC & SỰ KIỆN',
		'back_label'        => function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'news_back_list' ) : 'Về danh sách tin tức',
		'back_url'          => qp_holdings_page_url( 'news' ),
		'related_title'     => function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'news_related' ) : 'Tin tức liên quan',
		'related_post_type' => 'qp_news',
		'related_taxonomy'  => 'qp_news_category',
	)
);
