<?php
/**
 * "Đối tác chiến lược" (Strategic Partners) logo strip.
 *
 * Partners are managed through the `qp_partner` CPT (registered in
 * dynamic-content.php). Each partner has:
 *   - partner_logo : the logo image (URL via ACF or featured image fallback)
 *   - partner_url  : optional outbound link wrapped around the logo
 *   - menu_order   : drag-drop order in admin
 *   - post_title   : partner name (used as alt text / aria-label)
 *
 * The same logo strip renders on the home page and the About page via
 * qp_holdings_render_partners_slides(). When no partners exist yet the renderer
 * falls back to the bundled 8 static logos so the section is never empty.
 */

defined( 'ABSPATH' ) || exit;

/**
 * ACF fields for each partner (logo + optional link).
 */
function qp_holdings_register_partner_acf(): void {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'      => 'group_qp_partner',
			'title'    => 'Thông tin đối tác',
			'fields'   => array(
				array(
					'key'           => 'field_qp_partner_logo',
					'label'         => 'Logo đối tác',
					'name'          => 'partner_logo',
					'type'          => 'image',
					'return_format' => 'url',
					'instructions'  => 'Nên dùng logo nền trong suốt (PNG). <strong>Logo ngang đẹp nhất, cao khoảng 80–120px</strong>. Có thể dùng "Ảnh đại diện" (featured image) bên phải thay cho ô này.',
				),
				array(
					'key'          => 'field_qp_partner_url',
					'label'        => 'Liên kết (tuỳ chọn)',
					'name'         => 'partner_url',
					'type'         => 'url',
					'instructions' => 'Khi nhập, logo sẽ trở thành liên kết mở website của đối tác trong tab mới. Để trống nếu không cần.',
				),
			),
			'location' => array(
				array(
					array( 'param' => 'post_type', 'operator' => '==', 'value' => 'qp_partner' ),
				),
			),
		)
	);
}
add_action( 'acf/init', 'qp_holdings_register_partner_acf' );

/**
 * Admin list columns: logo preview + link + order.
 */
function qp_holdings_partner_admin_columns( array $columns ): array {
	$new = array();
	foreach ( $columns as $key => $label ) {
		if ( 'title' === $key ) {
			$new['qp_partner_logo'] = 'Logo';
		}
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['qp_partner_url']   = 'Liên kết';
			$new['qp_partner_order'] = 'Thứ tự';
		}
	}
	return $new;
}
add_filter( 'manage_qp_partner_posts_columns', 'qp_holdings_partner_admin_columns' );

function qp_holdings_partner_admin_column_content( string $column, int $post_id ): void {
	if ( 'qp_partner_logo' === $column ) {
		$url = qp_holdings_partner_logo_url( $post_id );
		echo $url
			? '<img src="' . esc_url( $url ) . '" alt="" style="width:120px;height:48px;object-fit:contain;background:#f6f7f7;border-radius:4px;padding:4px;">'
			: '—'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} elseif ( 'qp_partner_url' === $column ) {
		$link = (string) qp_holdings_acf_or_meta( $post_id, 'partner_url', 'partner_url' );
		echo '' !== $link
			? '<a href="' . esc_url( $link ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $link ) . '</a>'
			: '<span style="color:#8c8f94;">—</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} elseif ( 'qp_partner_order' === $column ) {
		echo '<span class="dashicons dashicons-menu" style="color:#8c8f94;cursor:move;" title="Kéo để sắp xếp"></span> '
			. '<span style="color:#8c8f94;">' . (int) get_post_field( 'menu_order', $post_id ) . '</span>';
	}
}
add_action( 'manage_qp_partner_posts_custom_column', 'qp_holdings_partner_admin_column_content', 10, 2 );

/**
 * Default the partner list to ascending menu_order in admin.
 */
function qp_holdings_partner_admin_default_order( $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( 'qp_partner' !== $query->get( 'post_type' ) ) {
		return;
	}
	if ( ! $query->get( 'orderby' ) ) {
		$query->set( 'orderby', 'menu_order' );
		$query->set( 'order', 'ASC' );
	}
}
add_action( 'pre_get_posts', 'qp_holdings_partner_admin_default_order' );

/**
 * Drag-and-drop ordering on the partner list table (mirrors board/gallery).
 */
function qp_holdings_partner_admin_sortable_assets( string $hook ): void {
	if ( 'edit.php' !== $hook ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'qp_partner' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_script( 'jquery-ui-sortable' );

	$nonce  = wp_create_nonce( 'qp_partner_reorder' );
	$inline = <<<JS
jQuery(function ($) {
	var \$tbody = $('#the-list');
	if (!\$tbody.length) { return; }
	\$tbody.find('tr').css('cursor', 'move');

	var \$status = $('<div style="display:none;position:fixed;top:46px;right:24px;z-index:9999;padding:10px 16px;border-radius:6px;background:#0B1B2A;color:#fff;font-size:13px;box-shadow:0 4px 16px rgba(0,0,0,0.25);"></div>').appendTo('body');
	function flash(msg, ok) {
		\$status.text(msg).css('background', ok ? '#1e7e34' : '#b32d2e').fadeIn(120);
		clearTimeout(\$status.data('t'));
		\$status.data('t', setTimeout(function () { \$status.fadeOut(300); }, 1800));
	}

	\$tbody.sortable({
		items: 'tr', axis: 'y', cursor: 'move', opacity: 0.7,
		placeholder: 'qp-partner-sort-placeholder',
		helper: function (e, tr) {
			var \$orig = tr.children();
			var \$helper = tr.clone();
			\$helper.children().each(function (i) { $(this).width(\$orig.eq(i).width()); });
			return \$helper;
		},
		update: function () {
			var ids = [];
			\$tbody.find('tr').each(function () {
				var id = ($(this).attr('id') || '').replace('post-', '');
				if (id) { ids.push(id); }
			});
			if (!ids.length) { return; }
			\$tbody.sortable('disable');
			$.post(ajaxurl, { action: 'qp_partner_reorder', nonce: '{$nonce}', order: ids })
				.done(function (res) { flash((res && res.data && res.data.message) ? res.data.message : 'Đã lưu thứ tự', !!(res && res.success)); })
				.fail(function () { flash('Lưu thứ tự thất bại', false); })
				.always(function () { \$tbody.sortable('enable'); });
		}
	});
});
JS;
	wp_add_inline_script( 'jquery-ui-sortable', $inline );
	wp_add_inline_style( 'wp-admin', '.qp-partner-sort-placeholder{outline:2px dashed #D3A049;background:#fff8e9;}.qp-partner-sort-placeholder td{visibility:hidden;}' );
}
add_action( 'admin_enqueue_scripts', 'qp_holdings_partner_admin_sortable_assets' );

/**
 * Persist drag-and-drop order from the partner list table.
 */
function qp_holdings_partner_reorder_ajax(): void {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'Không có quyền.' ), 403 );
	}
	check_ajax_referer( 'qp_partner_reorder', 'nonce' );

	$order = isset( $_POST['order'] ) ? (array) wp_unslash( $_POST['order'] ) : array();
	$order = array_values( array_filter( array_map( 'absint', $order ) ) );
	if ( empty( $order ) ) {
		wp_send_json_error( array( 'message' => 'Danh sách trống.' ) );
	}

	$position = 0;
	foreach ( $order as $post_id ) {
		if ( 'qp_partner' !== get_post_type( $post_id ) ) {
			continue;
		}
		$position += 10;
		wp_update_post( array( 'ID' => $post_id, 'menu_order' => $position ) );
	}

	wp_send_json_success( array( 'message' => 'Đã lưu thứ tự mới.' ) );
}
add_action( 'wp_ajax_qp_partner_reorder', 'qp_holdings_partner_reorder_ajax' );

/**
 * Resolve a partner's logo URL (ACF image → meta → featured image).
 */
function qp_holdings_partner_logo_url( int $post_id ): string {
	$logo = qp_holdings_acf_or_meta( $post_id, 'partner_logo', 'partner_logo' );
	return qp_holdings_image_url( $logo, $post_id, 'large' );
}

/**
 * Fetch published partners ordered by menu_order.
 *
 * @return WP_Post[]
 */
function qp_holdings_get_partners(): array {
	$items = get_posts(
		array(
			'post_type'      => 'qp_partner',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
		)
	);
	return is_array( $items ) ? $items : array();
}

/**
 * Render the partner logo slides (.partner-item.swiper-slide). Shared by the
 * home page and the About page. Falls back to the bundled static logos when no
 * partners have been added yet.
 */
function qp_holdings_render_partners_slides(): string {
	$partners = qp_holdings_get_partners();

	if ( ! empty( $partners ) ) {
		$slides = '';
		foreach ( $partners as $partner ) {
			$logo = qp_holdings_partner_logo_url( (int) $partner->ID );
			if ( '' === $logo ) {
				continue;
			}
			$name = get_the_title( $partner );
			$url  = (string) qp_holdings_acf_or_meta( (int) $partner->ID, 'partner_url', 'partner_url' );
			$img  = sprintf(
				'<img src="%s" alt="%s" loading="lazy" decoding="async">',
				esc_url( $logo ),
				esc_attr( $name )
			);
			$inner = ( '' !== $url )
				? sprintf(
					'<a href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s">%s</a>',
					esc_url( $url ),
					esc_attr( $name ),
					$img
				)
				: $img;
			$slides .= '<div class="partner-item swiper-slide">' . $inner . '</div>';
		}
		if ( '' !== $slides ) {
			return $slides;
		}
	}

	return qp_holdings_render_partners_fallback_slides();
}

/**
 * Bundled static logo strip used before any partner is added in admin.
 */
function qp_holdings_render_partners_fallback_slides(): string {
	$suffix = array( 1 => '', 2 => '', 3 => '', 4 => '69d53d', 5 => '2237e8', 6 => '4a8d46', 7 => '', 8 => '' );
	$out    = '';
	for ( $i = 1; $i <= 8; $i++ ) {
		$rel = 'assets/site/partner-' . $i . ( $suffix[ $i ] ? '-' . $suffix[ $i ] : '' ) . '.png';
		$alt = function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'Doi tac ' . $i ) : 'Doi tac ' . $i;
		$pic = function_exists( 'qp_holdings_theme_picture' )
			? qp_holdings_theme_picture( $rel, array( 'alt' => $alt, 'loading' => 'lazy', 'decoding' => 'async' ), '(max-width: 640px) 38vw, 12vw' )
			: '<img src="' . esc_url( get_template_directory_uri() . '/' . $rel ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async">';
		$out .= '<div class="partner-item swiper-slide">' . $pic . '</div>';
	}
	return $out;
}

/**
 * Seed the 8 strategic partners from the bundled logos (idempotent).
 *
 * Titles follow the rendered home-page order. Editors can rename, replace the
 * logo, add a link, reorder, or remove any of them afterwards.
 */
function qp_holdings_seed_partners( bool $force = false ): void {
	$partners = array(
		array( 'slug' => 'agribank',     'name' => 'Agribank',             'img' => 'assets/site/partner-1.png' ),
		array( 'slug' => 'bidv',         'name' => 'BIDV',                 'img' => 'assets/site/partner-2.png' ),
		array( 'slug' => 'tien-phong',   'name' => 'Tiền Phong Plastic',   'img' => 'assets/site/partner-3.png' ),
		array( 'slug' => 'mb',           'name' => 'MB Bank',              'img' => 'assets/site/partner-4-69d53d.png' ),
		array( 'slug' => 'hyans',        'name' => 'HYANS',                'img' => 'assets/site/partner-5-2237e8.png' ),
		array( 'slug' => 'phuoc-thanh',  'name' => 'Phước Thành',          'img' => 'assets/site/partner-6-4a8d46.png' ),
		array( 'slug' => 'bcons',        'name' => 'Bcons',                'img' => 'assets/site/partner-7.png' ),
		array( 'slug' => 'vietcombank',  'name' => 'Vietcombank',          'img' => 'assets/site/partner-8.png' ),
	);

	$order = 0;
	foreach ( $partners as $p ) {
		$order  += 10;
		$post_id = qp_holdings_seed_upsert_post(
			array(
				'post_type'   => 'qp_partner',
				'post_name'   => 'doi-tac-' . $p['slug'],
				'post_title'  => $p['name'],
				'post_status' => 'publish',
				'menu_order'  => $order,
			),
			$force
		);
		if ( ! $post_id ) {
			continue;
		}
		update_post_meta( $post_id, 'partner_logo', $p['img'] );
	}
}
