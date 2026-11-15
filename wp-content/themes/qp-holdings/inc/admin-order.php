<?php
/**
 * Thứ tự hiển thị do admin tự sắp xếp (yêu cầu #5 Dự án, #6 Hình ảnh & video).
 *
 * - Dự án (qp_project): drag-and-drop on the list table, like the gallery
 *   and partners already had. The order replaces the order that was hard-coded
 *   by slug in dynamic-projects.php; it is copied from that hard-coded order
 *   once, so the site looks the same until the admin changes it.
 * - Both lists show only the Vietnamese posts by default (EN / ZH copies
 *   tripled every row) with a short how-to notice.
 * - EN / ZH pages always follow the order of the Vietnamese posts.
 */

defined( 'ABSPATH' ) || exit;

/** Post types whose order is set by drag-and-drop => label used in notices. */
function qp_holdings_ordered_post_types(): array {
	return array(
		'qp_project'      => 'dự án',
		'qp_gallery_item' => 'hình ảnh & video',
	);
}

/**
 * Save only the display order of a post.
 *
 * Not wp_update_post(): that fires the save hooks, so the translation plugin
 * treated every reordered post as edited (queued it again, showed a long
 * "Bỏ qua…" toast) and post_modified changed, which marked the EN / ZH copies
 * "Cần cập nhật" although no text changed.
 */
function qp_holdings_set_menu_order( int $post_id, int $order ): void {
	global $wpdb;
	if ( (int) get_post_field( 'menu_order', $post_id ) === $order ) {
		return;
	}
	$wpdb->update( $wpdb->posts, array( 'menu_order' => $order ), array( 'ID' => $post_id ), array( '%d' ), array( '%d' ) );
	clean_post_cache( $post_id );
}

/** Vietnamese original of a translated copy (the post itself otherwise). */
function qp_holdings_order_source_post( WP_Post $post ): WP_Post {
	$source_id = (int) get_post_meta( $post->ID, '_qp_llm_source_id', true );
	if ( $source_id ) {
		$source = get_post( $source_id );
		if ( $source instanceof WP_Post ) {
			return $source;
		}
	}
	return $post;
}

/**
 * Sort posts by the admin order of their Vietnamese original
 * (menu_order ascending, newest first when equal).
 *
 * @param WP_Post[] $posts
 * @return WP_Post[]
 */
function qp_holdings_sort_by_admin_order( array $posts ): array {
	$key = array();
	foreach ( $posts as $post ) {
		if ( $post instanceof WP_Post ) {
			$src              = qp_holdings_order_source_post( $post );
			$key[ $post->ID ] = array( (int) $src->menu_order, (string) $src->post_date );
		}
	}
	usort(
		$posts,
		static function ( $a, $b ) use ( $key ): int {
			$ka = $key[ $a->ID ] ?? array( 0, '' );
			$kb = $key[ $b->ID ] ?? array( 0, '' );
			if ( $ka[0] !== $kb[0] ) {
				return $ka[0] <=> $kb[0];
			}
			return strcmp( $kb[1], $ka[1] );
		}
	);
	return $posts;
}

/* -------------------------------------------------------------------------
 * Admin list: Vietnamese only, ordered, notice
 * ---------------------------------------------------------------------- */

function qp_holdings_order_is_list_screen( string $post_type = '' ): bool {
	if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
		return false;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'edit' !== $screen->base || ! isset( qp_holdings_ordered_post_types()[ $screen->post_type ] ) ) {
		return false;
	}
	return '' === $post_type || $post_type === $screen->post_type;
}

function qp_holdings_order_admin_query( $query ): void {
	if ( ! is_admin() || ! $query instanceof WP_Query || ! $query->is_main_query() ) {
		return;
	}
	$type = (string) $query->get( 'post_type' );
	if ( ! isset( qp_holdings_ordered_post_types()[ $type ] ) ) {
		return;
	}
	if ( ! $query->get( 'orderby' ) ) {
		$query->set( 'orderby', array( 'menu_order' => 'ASC', 'date' => 'DESC' ) );
	}
	// phpcs:ignore WordPress.Security.NonceVerification
	if ( empty( $_GET['qp_all_langs'] ) ) {
		$meta   = (array) $query->get( 'meta_query' );
		$meta[] = array(
			'key'     => '_qp_translation_lang',
			'compare' => 'NOT EXISTS',
		);
		$query->set( 'meta_query', $meta );
	}
}
add_action( 'pre_get_posts', 'qp_holdings_order_admin_query', 20 );

function qp_holdings_order_admin_notice(): void {
	if ( ! qp_holdings_order_is_list_screen() ) {
		return;
	}
	$screen = get_current_screen();
	$label  = qp_holdings_ordered_post_types()[ $screen->post_type ];
	$all    = ! empty( $_GET['qp_all_langs'] ); // phpcs:ignore WordPress.Security.NonceVerification
	$toggle = $all
		? remove_query_arg( 'qp_all_langs' )
		: add_query_arg( 'qp_all_langs', 1 );
	$groups = 'qp_project' === $screen->post_type
		? '<br>Trang Dự án chia <strong>2 nhóm</strong>: “Đang triển khai” ở trên, “Hợp tác đầu tư &amp; phát triển” ở dưới (xem cột “Nhóm trên trang Dự án”). Thứ tự áp dụng <strong>trong từng nhóm</strong>; muốn chuyển dự án sang nhóm khác, sửa <strong>Trạng thái dự án</strong> trong bài (“Đang triển khai” = nhóm trên).'
		: '';
	printf(
		'<div class="notice notice-info"><p><strong>Sắp xếp thứ tự %1$s:</strong> giữ chuột vào một dòng rồi <strong>kéo lên / xuống</strong>, thả ra là thứ tự được lưu ngay và áp dụng cho website (cả tiếng Anh, tiếng Trung). %2$s <a href="%3$s">%4$s</a>%5$s</p></div>',
		esc_html( $label ),
		$all ? 'Đang hiện cả bản tiếng Anh, tiếng Trung.' : 'Danh sách đang hiện bài tiếng Việt.',
		esc_url( $toggle ),
		$all ? 'Chỉ hiện bài tiếng Việt' : 'Hiện cả bản tiếng Anh, tiếng Trung',
		$groups // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static HTML above.
	);
}
add_action( 'admin_notices', 'qp_holdings_order_admin_notice' );

/**
 * Make the drag handle easy to find: "Thứ tự" becomes the first column, and
 * the Rank Math SEO columns are dropped on these two lists (their keyword
 * list made every project row about one screen tall, so rows could not be
 * dragged). SEO details stay available in the edit screen.
 * Runs on manage_edit-{type}_columns, after every plugin added its columns.
 */
function qp_holdings_order_list_columns( array $columns ): array {
	$order_key = '';
	foreach ( array( 'qp_project_order', 'qp_gallery_order' ) as $key ) {
		if ( isset( $columns[ $key ] ) ) {
			$order_key = $key;
		}
	}
	foreach ( array( 'rank_math_seo_details', 'rank_math_title', 'rank_math_description' ) as $seo ) {
		unset( $columns[ $seo ] );
	}
	if ( '' === $order_key ) {
		return $columns;
	}
	$label = 'Kéo để sắp xếp';
	unset( $columns[ $order_key ] );
	$out = array();
	if ( isset( $columns['cb'] ) ) {
		$out['cb'] = $columns['cb'];
		unset( $columns['cb'] );
	}
	$out[ $order_key ] = $label;
	$group             = $columns['qp_project_group'] ?? null;
	unset( $columns['qp_project_group'] );
	foreach ( $columns as $key => $col_label ) {
		$out[ $key ] = $col_label;
		if ( 'title' === $key && null !== $group ) {
			$out['qp_project_group'] = $group;
		}
	}
	return $out;
}

function qp_holdings_order_register_list_columns(): void {
	foreach ( array_keys( qp_holdings_ordered_post_types() ) as $type ) {
		add_filter( "manage_edit-{$type}_columns", 'qp_holdings_order_list_columns', 999 );
	}
}
add_action( 'admin_init', 'qp_holdings_order_register_list_columns' );

/** Shared look of the drag handle column on both lists. */
function qp_holdings_order_list_styles( string $hook ): void {
	if ( 'edit.php' !== $hook || ! qp_holdings_order_is_list_screen() ) {
		return;
	}
	wp_add_inline_style(
		'wp-admin',
		'.column-qp_project_order,.column-qp_gallery_order{width:96px;text-align:center}'
		. '.column-qp_project_order .dashicons,.column-qp_gallery_order .dashicons{font-size:26px;width:26px;height:26px;color:#D3A049!important;cursor:grab!important}'
		. '#the-list tr:hover .column-qp_project_order .dashicons,#the-list tr:hover .column-qp_gallery_order .dashicons{color:#193660!important}'
		. '.column-qp_project_order>span:last-child,.column-qp_gallery_order>span:last-child{display:block;font-size:11px}'
		. '.column-qp_project_group{width:170px}'
		. '.qp-project-group{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;line-height:18px;white-space:nowrap}'
		. '.qp-project-group--in_progress{background:#e7f5ea;color:#0a7a28}'
		. '.qp-project-group--investment_development{background:#e8f0fb;color:#1d4f91}'
	);
}
add_action( 'admin_enqueue_scripts', 'qp_holdings_order_list_styles' );

/* -------------------------------------------------------------------------
 * Dự án: order column + drag-and-drop (gallery has its own in dynamic-gallery.php)
 * ---------------------------------------------------------------------- */

/** The two groups of the Dự án page (qp_holdings_project_archive_section()). */
function qp_holdings_project_group_labels(): array {
	return array(
		'in_progress'            => 'Đang triển khai',
		'investment_development' => 'Hợp tác đầu tư & phát triển',
	);
}

function qp_holdings_project_order_column( array $columns ): array {
	$columns['qp_project_order'] = 'Thứ tự';
	$columns['qp_project_group'] = 'Nhóm trên trang Dự án';
	return $columns;
}
add_filter( 'manage_qp_project_posts_columns', 'qp_holdings_project_order_column' );

function qp_holdings_project_order_column_content( string $column, int $post_id ): void {
	if ( 'qp_project_order' === $column ) {
		echo '<span class="dashicons dashicons-menu" style="color:#8c8f94;cursor:move" aria-hidden="true"></span> <span style="color:#8c8f94">' . (int) get_post_field( 'menu_order', $post_id ) . '</span>';
	}
	if ( 'qp_project_group' === $column ) {
		$post    = get_post( $post_id );
		$section = ( $post && function_exists( 'qp_holdings_project_archive_section' ) ) ? qp_holdings_project_archive_section( $post ) : '';
		$labels  = qp_holdings_project_group_labels();
		if ( isset( $labels[ $section ] ) ) {
			printf(
				'<span class="qp-project-group qp-project-group--%1$s">%2$s</span>',
				esc_attr( $section ),
				esc_html( $labels[ $section ] )
			);
		}
	}
}
add_action( 'manage_qp_project_posts_custom_column', 'qp_holdings_project_order_column_content', 10, 2 );

function qp_holdings_project_order_assets( string $hook ): void {
	if ( 'edit.php' !== $hook || ! qp_holdings_order_is_list_screen( 'qp_project' ) ) {
		return;
	}
	wp_enqueue_script( 'jquery-ui-sortable' );
	$nonce  = wp_create_nonce( 'qp_project_reorder' );
	$inline = <<<JS
jQuery(function ($) {
	var \$tbody = $('#the-list');
	if (!\$tbody.length || \$tbody.find('tr.no-items').length) { return; }
	\$tbody.find('tr').css('cursor', 'move');
	var \$status = $('<div style="display:none;position:fixed;top:46px;right:24px;z-index:9999;padding:10px 16px;border-radius:6px;background:#0B1B2A;color:#fff;font-size:13px;box-shadow:0 4px 16px rgba(0,0,0,0.25);"></div>').appendTo('body');
	function flash(msg, ok) {
		\$status.text(msg).css('background', ok ? '#1e7e34' : '#b32d2e').fadeIn(120);
		clearTimeout(\$status.data('t'));
		\$status.data('t', setTimeout(function () { \$status.fadeOut(300); }, 1800));
	}
	\$tbody.sortable({
		items: '> tr',
		axis: 'y',
		cursor: 'move',
		placeholder: 'qp-project-sort-placeholder',
		helper: function (e, tr) {
			var \$orig = tr.children();
			var \$helper = tr.clone();
			\$helper.children().each(function (i) { $(this).width(\$orig.eq(i).width()); });
			return \$helper;
		},
		update: function () {
			var ids = [];
			\$tbody.find('tr').each(function () {
				var id = (this.id || '').replace('post-', '');
				if (id) { ids.push(id); }
			});
			\$tbody.sortable('disable');
			$.post(ajaxurl, { action: 'qp_project_reorder', nonce: '{$nonce}', order: ids })
				.done(function (res) { flash((res && res.data && res.data.message) ? res.data.message : 'Đã lưu thứ tự', !!(res && res.success)); })
				.fail(function () { flash('Lưu thứ tự thất bại', false); })
				.always(function () { \$tbody.sortable('enable'); });
		}
	});
});
JS;
	wp_add_inline_script( 'jquery-ui-sortable', $inline );
	wp_add_inline_style( 'wp-admin', '.qp-project-sort-placeholder{outline:2px dashed #D3A049;background:#fff8e9;}.qp-project-sort-placeholder td{visibility:hidden;}.column-qp_project_order{width:80px}' );
}
add_action( 'admin_enqueue_scripts', 'qp_holdings_project_order_assets' );

function qp_holdings_project_reorder_ajax(): void {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_send_json_error( array( 'message' => 'Không có quyền.' ), 403 );
	}
	check_ajax_referer( 'qp_project_reorder', 'nonce' );
	$order = isset( $_POST['order'] ) ? (array) wp_unslash( $_POST['order'] ) : array();
	$order = array_values( array_filter( array_map( 'absint', $order ) ) );
	if ( ! $order ) {
		wp_send_json_error( array( 'message' => 'Danh sách trống.' ) );
	}

	// Keep the positions of rows not on this page (pagination): start from the
	// smallest order currently used by the rows being sorted.
	$current = array();
	foreach ( $order as $post_id ) {
		if ( 'qp_project' === get_post_type( $post_id ) ) {
			$current[] = (int) get_post_field( 'menu_order', $post_id );
		}
	}
	$position = $current ? max( 0, min( $current ) - 10 ) : 0;
	foreach ( $order as $post_id ) {
		if ( 'qp_project' !== get_post_type( $post_id ) ) {
			continue;
		}
		$position += 10;
		qp_holdings_set_menu_order( (int) $post_id, $position );
	}
	wp_send_json_success( array( 'message' => 'Đã lưu thứ tự mới.' ) );
}
add_action( 'wp_ajax_qp_project_reorder', 'qp_holdings_project_reorder_ajax' );

/**
 * One time: write the order that used to be hard-coded (dynamic-projects.php)
 * into menu_order, so the site looks the same until the admin reorders.
 * Skipped when an order was already set on any project.
 */
function qp_holdings_project_order_seed(): void {
	if ( get_option( 'qp_project_order_seeded' ) ) {
		return;
	}
	update_option( 'qp_project_order_seeded', 1, false );

	$ids = get_posts(
		array(
			'post_type'        => 'qp_project',
			'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page'   => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
			'lang'             => '',
			'orderby'          => 'date',
			'order'            => 'DESC',
			'meta_query'       => array(
				array(
					'key'     => '_qp_translation_lang',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);
	foreach ( $ids as $id ) {
		if ( (int) get_post_field( 'menu_order', $id ) !== 0 ) {
			return; // Already ordered by someone: leave it.
		}
	}

	// The former hard-coded order: "in progress" first, then "investment & development".
	$legacy = array(
		'qp-green-park',
		'qp-binh-duong-center',
		'qp-luxia-one',
		'khu-do-thi-quang-phuc-4',
		'khu-phuc-hop-cao-tang-markup',
		'hiep-thanh-city',
		'cum-cong-nghiep-an-binh-7',
		'qp-idc',
	);
	$weights = array();
	foreach ( $ids as $id ) {
		$post = get_post( $id );
		$slug = function_exists( 'qp_holdings_translation_source_slug' ) ? qp_holdings_translation_source_slug( $post ) : (string) $post->post_name;
		if ( function_exists( 'qp_holdings_resolve_project_canonical_slug' ) ) {
			$slug = qp_holdings_resolve_project_canonical_slug( $slug );
		}
		$weight = 1000;
		foreach ( $legacy as $i => $key ) {
			if ( $key === $slug || false !== strpos( $slug, $key ) ) {
				$weight = $i;
				break;
			}
		}
		$weights[ $id ] = $weight;
	}
	// Stable: equal weights keep the newest-first order of $ids.
	$position = array_flip( $ids );
	uksort(
		$weights,
		static function ( $a, $b ) use ( $weights, $position ): int {
			return $weights[ $a ] <=> $weights[ $b ] ?: $position[ $a ] <=> $position[ $b ];
		}
	);
	$n = 0;
	foreach ( array_keys( $weights ) as $id ) {
		$n += 10;
		qp_holdings_set_menu_order( (int) $id, $n );
	}
}
add_action( 'admin_init', 'qp_holdings_project_order_seed' );
