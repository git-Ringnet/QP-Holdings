<?php
/**
 * Quản trị Tin tức: xem và đổi danh mục nhanh (yêu cầu 27/11/2026).
 *
 * - Cột "Danh mục tin tức" trong danh sách Tin tức (bài không có danh mục hiện "—").
 * - Bộ lọc theo danh mục, có "— Chưa có danh mục —" để tìm bài cần gắn lại.
 * - Thao tác hàng loạt: "Chuyển sang danh mục…" (bỏ danh mục cũ) và "Thêm vào danh mục…" (giữ danh mục cũ).
 * - Xoá danh mục làm bài mất hết danh mục → báo số bài và link mở đúng các bài đó.
 *
 * @package QP_Holdings
 */

defined( 'ABSPATH' ) || exit;

const QP_NEWS_CAT_TAX     = 'qp_news_category';
const QP_NEWS_CAT_FILTER  = 'qp_news_cat';
const QP_NEWS_CAT_NONE    = '__none';
const QP_NEWS_CAT_ORPHANS = 'qp_news_cat_orphans_';

/** Cột danh mục trong danh sách Tin tức (WordPress tự hiện "—" khi bài không có danh mục). */
add_filter(
	'register_taxonomy_args',
	static function ( array $args, string $taxonomy ): array {
		if ( QP_NEWS_CAT_TAX === $taxonomy ) {
			$args['show_admin_column'] = true;
		}
		return $args;
	},
	10,
	2
);

function qp_holdings_news_cat_is_list_screen(): bool {
	global $pagenow;
	return is_admin() && 'edit.php' === $pagenow && 'qp_news' === ( $_GET['post_type'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
}

/** Số bài Tin tức chưa có danh mục (mọi trạng thái trừ thùng rác). */
function qp_holdings_news_cat_orphan_count(): int {
	$q = new WP_Query(
		array(
			'post_type'      => 'qp_news',
			'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => false,
			'tax_query'      => array( array( 'taxonomy' => QP_NEWS_CAT_TAX, 'operator' => 'NOT EXISTS' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return (int) $q->found_posts;
}

/** Tên danh mục để chọn; tên bị trùng thì thêm đường dẫn rút gọn (slug) để phân biệt. */
function qp_holdings_news_cat_labels(): array {
	$terms = get_terms( array( 'taxonomy' => QP_NEWS_CAT_TAX, 'hide_empty' => false, 'orderby' => 'name' ) );
	if ( ! is_array( $terms ) ) {
		return array();
	}
	$seen  = array_count_values( array_map( static fn( $t ) => mb_strtolower( $t->name ), $terms ) );
	$slugs = array_count_values( array_map( static fn( $t ) => $t->slug, $terms ) );
	$out   = array();
	foreach ( $terms as $t ) {
		$label = $t->name;
		if ( $seen[ mb_strtolower( $t->name ) ] > 1 ) {
			$label .= ' – ' . urldecode( $t->slug ) . ( $slugs[ $t->slug ] > 1 ? ' #' . $t->term_id : '' );
		}
		$out[] = array( $t, $label );
	}
	return $out;
}

/** Bộ lọc danh mục phía trên danh sách. */
add_action(
	'restrict_manage_posts',
	static function ( string $post_type ): void {
		if ( 'qp_news' !== $post_type ) {
			return;
		}
		$current = sanitize_text_field( wp_unslash( $_GET[ QP_NEWS_CAT_FILTER ] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		echo '<label class="screen-reader-text" for="qp-news-cat-filter">Lọc theo danh mục</label>';
		echo '<select name="' . esc_attr( QP_NEWS_CAT_FILTER ) . '" id="qp-news-cat-filter">';
		echo '<option value="">Tất cả danh mục</option>';
		printf(
			'<option value="%1$s" %2$s>— Chưa có danh mục — (%3$d)</option>',
			esc_attr( QP_NEWS_CAT_NONE ),
			selected( $current, QP_NEWS_CAT_NONE, false ),
			(int) qp_holdings_news_cat_orphan_count()
		);
		foreach ( qp_holdings_news_cat_labels() as [ $t, $label ] ) {
			printf(
				'<option value="%1$s" %2$s>%3$s (%4$d)</option>',
				esc_attr( (string) $t->term_id ),
				selected( $current, (string) $t->term_id, false ),
				esc_html( $label ),
				(int) $t->count
			);
		}
		echo '</select>';
	}
);

/** Áp dụng bộ lọc. */
add_action(
	'pre_get_posts',
	static function ( WP_Query $q ): void {
		if ( ! $q->is_main_query() || ! qp_holdings_news_cat_is_list_screen() ) {
			return;
		}
		$v = sanitize_text_field( wp_unslash( $_GET[ QP_NEWS_CAT_FILTER ] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		if ( '' === $v ) {
			return;
		}
		$tax = QP_NEWS_CAT_NONE === $v
			? array( 'taxonomy' => QP_NEWS_CAT_TAX, 'operator' => 'NOT EXISTS' )
			: array( 'taxonomy' => QP_NEWS_CAT_TAX, 'field' => 'term_id', 'terms' => array( (int) $v ), 'include_children' => false );
		$q->set( 'tax_query', array( $tax ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	}
);

/** Thao tác hàng loạt: mỗi danh mục là một lựa chọn trong 2 nhóm "Chuyển sang" / "Thêm vào". */
add_filter(
	'bulk_actions-edit-qp_news',
	static function ( array $actions ): array {
		$labels = qp_holdings_news_cat_labels();
		if ( ! $labels ) {
			return $actions;
		}
		$move = array();
		$add  = array();
		foreach ( $labels as [ $t, $label ] ) {
			$move[ 'qp_cat_move_' . $t->term_id ] = $label;
			$add[ 'qp_cat_add_' . $t->term_id ]   = $label;
		}
		$actions['Chuyển sang danh mục (bỏ danh mục cũ)'] = $move;
		$actions['Thêm vào danh mục (giữ danh mục cũ)']   = $add;
		return $actions;
	}
);

add_filter(
	'handle_bulk_actions-edit-qp_news',
	static function ( string $redirect, string $action, array $post_ids ): string {
		if ( ! preg_match( '/^qp_cat_(move|add)_(\d+)$/', $action, $m ) ) {
			return $redirect;
		}
		$term = get_term( (int) $m[2], QP_NEWS_CAT_TAX );
		if ( ! $term instanceof WP_Term ) {
			return $redirect;
		}
		$done = 0;
		foreach ( $post_ids as $id ) {
			$id = (int) $id;
			if ( 'qp_news' !== get_post_type( $id ) || ! current_user_can( 'edit_post', $id ) ) {
				continue;
			}
			$r = wp_set_object_terms( $id, array( (int) $term->term_id ), QP_NEWS_CAT_TAX, 'add' === $m[1] );
			if ( ! is_wp_error( $r ) ) {
				clean_post_cache( $id );
				++$done;
			}
		}
		return add_query_arg(
			array(
				'qp_cat_done' => $done,
				'qp_cat_mode' => $m[1],
				'qp_cat_term' => (int) $term->term_id,
			),
			remove_query_arg( array( 'qp_cat_done', 'qp_cat_mode', 'qp_cat_term' ), $redirect )
		);
	},
	10,
	3
);

/** Thông báo sau khi đổi danh mục hàng loạt / sau khi xoá danh mục làm bài mất danh mục. */
add_action(
	'admin_notices',
	static function (): void {
		// phpcs:disable WordPress.Security.NonceVerification
		if ( isset( $_GET['qp_cat_done'] ) && qp_holdings_news_cat_is_list_screen() ) {
			$term = get_term( (int) $_GET['qp_cat_term'], QP_NEWS_CAT_TAX );
			$verb = 'add' === ( $_GET['qp_cat_mode'] ?? '' ) ? 'Đã thêm' : 'Đã chuyển';
			printf(
				'<div class="notice notice-success is-dismissible"><p>%1$s %2$d bài vào danh mục <strong>%3$s</strong>.</p></div>',
				esc_html( $verb ),
				(int) $_GET['qp_cat_done'],
				esc_html( $term instanceof WP_Term ? $term->name : '' )
			);
		}
		// phpcs:enable

		$key = QP_NEWS_CAT_ORPHANS . get_current_user_id();
		$n   = (int) get_transient( $key );
		if ( $n > 0 ) {
			delete_transient( $key );
			$url = add_query_arg( array( 'post_type' => 'qp_news', QP_NEWS_CAT_FILTER => QP_NEWS_CAT_NONE ), admin_url( 'edit.php' ) );
			printf(
				'<div class="notice notice-warning is-dismissible"><p>Danh mục vừa xoá làm <strong>%1$d bài</strong> không còn danh mục nào. <a href="%2$s">Xem các bài này</a> để gắn danh mục mới (chọn bài → Hành động hàng loạt → Chuyển sang danh mục).</p></div>',
				(int) $n,
				esc_url( $url )
			);
		}
	}
);

/** Trước khi xoá danh mục: đếm bài sẽ không còn danh mục nào để báo lại. */
add_action(
	'pre_delete_term',
	static function ( int $term_id, string $taxonomy ): void {
		if ( QP_NEWS_CAT_TAX !== $taxonomy ) {
			return;
		}
		$ids = get_objects_in_term( $term_id, QP_NEWS_CAT_TAX );
		if ( ! is_array( $ids ) || ! $ids ) {
			return;
		}
		$orphans = 0;
		foreach ( $ids as $id ) {
			$terms = wp_get_object_terms( (int) $id, QP_NEWS_CAT_TAX, array( 'fields' => 'ids' ) );
			if ( is_array( $terms ) && array( (int) $term_id ) === array_map( 'intval', $terms ) && 'trash' !== get_post_status( (int) $id ) ) {
				++$orphans;
			}
		}
		if ( $orphans ) {
			$key = QP_NEWS_CAT_ORPHANS . get_current_user_id();
			set_transient( $key, (int) get_transient( $key ) + $orphans, HOUR_IN_SECONDS );
		}
	},
	10,
	2
);
