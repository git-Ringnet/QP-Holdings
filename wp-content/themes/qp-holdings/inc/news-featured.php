<?php
/**
 * Tin nổi bật (yêu cầu #4, 11/2026).
 *
 * The large card on the left of the first group ("Tin dự án") on /tintuc/
 * used to be simply the newest post. Editors can now tick "Tin nổi bật" in
 * the publish box of a news post; that post takes the large card. Only one
 * post is featured at a time (ticking a new one unticks the previous one).
 * With none ticked, the newest post is used as before.
 *
 * The flag lives on the Vietnamese post; EN / ZH copies follow their source
 * (looked up through _qp_llm_source_id), so nothing has to be ticked twice.
 */

defined( 'ABSPATH' ) || exit;

const QP_HOLDINGS_NEWS_FEATURED_META  = 'qp_news_featured';
const QP_HOLDINGS_NEWS_FEATURED_NONCE = 'qp_news_featured_nonce';

/** Vietnamese original of a news post (the post itself when it is the original). */
function qp_holdings_news_featured_source_id( int $post_id ): int {
	$source = (int) get_post_meta( $post_id, '_qp_llm_source_id', true );
	return ( $source && get_post( $source ) ) ? $source : $post_id;
}

function qp_holdings_news_is_featured( int $post_id ): bool {
	return (bool) get_post_meta( qp_holdings_news_featured_source_id( $post_id ), QP_HOLDINGS_NEWS_FEATURED_META, true );
}

/**
 * Move the featured post (if any) to the front of a date-ordered list.
 *
 * @param WP_Post[] $posts
 * @return WP_Post[]
 */
function qp_holdings_news_featured_first( array $posts ): array {
	foreach ( $posts as $i => $post ) {
		if ( $post instanceof WP_Post && qp_holdings_news_is_featured( (int) $post->ID ) ) {
			unset( $posts[ $i ] );
			array_unshift( $posts, $post );
			break;
		}
	}
	return array_values( $posts );
}

/* -------------------------------------------------------------------------
 * Admin: checkbox in the publish box, star column in the list
 * ---------------------------------------------------------------------- */

function qp_holdings_news_featured_checkbox( WP_Post $post ): void {
	if ( 'qp_news' !== $post->post_type ) {
		return;
	}
	$is_copy = (int) get_post_meta( $post->ID, '_qp_llm_source_id', true ) > 0;
	echo '<div class="misc-pub-section qp-news-featured">';
	if ( $is_copy ) {
		$source = qp_holdings_news_featured_source_id( (int) $post->ID );
		echo '<span class="dashicons dashicons-star-' . ( qp_holdings_news_is_featured( (int) $post->ID ) ? 'filled' : 'empty' ) . '" style="color:#d3a049"></span> ';
		echo 'Tin nổi bật: theo <a href="' . esc_url( (string) get_edit_post_link( $source ) ) . '">bài tiếng Việt</a>.';
	} else {
		wp_nonce_field( QP_HOLDINGS_NEWS_FEATURED_NONCE, QP_HOLDINGS_NEWS_FEATURED_NONCE );
		printf(
			'<label><input type="checkbox" name="%1$s" value="1" %2$s> <strong>Tin nổi bật</strong></label><br><span class="description">Hiện ở ô lớn trang Tin tức. Chỉ một tin nổi bật: tích tin này sẽ bỏ tích tin cũ.</span>',
			esc_attr( QP_HOLDINGS_NEWS_FEATURED_META ),
			checked( (bool) get_post_meta( $post->ID, QP_HOLDINGS_NEWS_FEATURED_META, true ), true, false )
		);
	}
	echo '</div>';
}
add_action( 'post_submitbox_misc_actions', 'qp_holdings_news_featured_checkbox' );

function qp_holdings_news_featured_save( int $post_id, WP_Post $post ): void {
	if ( ! isset( $_POST[ QP_HOLDINGS_NEWS_FEATURED_NONCE ] ) || ! wp_verify_nonce( sanitize_key( (string) wp_unslash( $_POST[ QP_HOLDINGS_NEWS_FEATURED_NONCE ] ) ), QP_HOLDINGS_NEWS_FEATURED_NONCE ) ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$on = ! empty( $_POST[ QP_HOLDINGS_NEWS_FEATURED_META ] );
	if ( ! $on ) {
		delete_post_meta( $post_id, QP_HOLDINGS_NEWS_FEATURED_META );
		return;
	}
	// Only one featured post: untick the others.
	$others = get_posts(
		array(
			'post_type'        => 'qp_news',
			'post_status'      => 'any',
			'posts_per_page'   => -1,
			'fields'           => 'ids',
			'post__not_in'     => array( $post_id ),
			'suppress_filters' => true,
			'lang'             => '',
			'meta_key'         => QP_HOLDINGS_NEWS_FEATURED_META, // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	foreach ( $others as $other ) {
		delete_post_meta( (int) $other, QP_HOLDINGS_NEWS_FEATURED_META );
	}
	update_post_meta( $post_id, QP_HOLDINGS_NEWS_FEATURED_META, 1 );
}
add_action( 'save_post_qp_news', 'qp_holdings_news_featured_save', 10, 2 );

function qp_holdings_news_featured_column( array $columns ): array {
	$out = array();
	foreach ( $columns as $key => $label ) {
		$out[ $key ] = $label;
		if ( 'title' === $key ) {
			$out['qp_featured'] = '<span class="dashicons dashicons-star-filled" title="Tin nổi bật" style="color:#d3a049"></span><span class="screen-reader-text">Tin nổi bật</span>';
		}
	}
	return $out;
}
add_filter( 'manage_qp_news_posts_columns', 'qp_holdings_news_featured_column' );

function qp_holdings_news_featured_column_content( string $column, int $post_id ): void {
	if ( 'qp_featured' === $column && qp_holdings_news_is_featured( $post_id ) ) {
		echo '<span class="dashicons dashicons-star-filled" style="color:#d3a049" title="Tin nổi bật"></span>';
	}
}
add_action( 'manage_qp_news_posts_custom_column', 'qp_holdings_news_featured_column_content', 10, 2 );

function qp_holdings_news_featured_column_width(): void {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && 'edit-qp_news' === $screen->id ) {
		echo '<style>.column-qp_featured{width:32px;text-align:center}</style>';
	}
}
add_action( 'admin_head', 'qp_holdings_news_featured_column_width' );
