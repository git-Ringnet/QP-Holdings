<?php
/**
 * Board of Directors ("Hội đồng quản trị") section for the About page.
 *
 * Members are managed through the `qp_board_member` CPT (registered in
 * dynamic-content.php). Each member has a portrait (featured image), a role
 * line (ACF/meta `board_role`), and an explicit order (menu_order via the
 * Page Attributes "Order" box). The section heading + intro paragraph are
 * editable as ACF fields on the Giới thiệu page.
 *
 * Renders to match Figma:
 *   - PC  (node 2167:6260): centered title + intro, then member cards.
 *   - Mobile/Tablet (node 3023:7690): 2-column grid of cards.
 * Each card is a navy→light-blue gradient box with the cut-out portrait
 * overflowing the top, name + role centered below.
 */

defined( 'ABSPATH' ) || exit;

/**
 * ACF fields for the board section heading/intro, shown on the About page.
 */
function qp_holdings_register_board_acf(): void {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'      => 'group_qp_board',
			'title'    => 'Hội đồng quản trị (tiêu đề mục)',
			'fields'   => array(
				array(
					'key'           => 'field_qp_board_title',
					'label'         => 'Tiêu đề mục',
					'name'          => 'board_section_title',
					'type'          => 'text',
					'default_value' => 'Hội đồng quản trị',
					'instructions'  => 'Tiêu đề lớn hiển thị phía trên danh sách thành viên.',
				),
				array(
					'key'           => 'field_qp_board_intro',
					'label'         => 'Đoạn giới thiệu',
					'name'          => 'board_section_intro',
					'type'          => 'textarea',
					'rows'          => 3,
					'new_lines'     => '',
					'instructions'  => 'Đoạn mô tả ngắn dưới tiêu đề. Quản lý danh sách thành viên ở menu "Hội đồng quản trị".',
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'page_template',
						'operator' => '==',
						'value'    => 'page-gioi-thieu.php',
					),
				),
			),
		)
	);

	/* Per-member fields: role line. Portrait uses the featured image. */
	acf_add_local_field_group(
		array(
			'key'      => 'group_qp_board_member',
			'title'    => 'Thông tin thành viên',
			'fields'   => array(
				array(
					'key'           => 'field_qp_board_member_group',
					'label'         => 'Nhóm',
					'name'          => 'board_group',
					'type'          => 'select',
					'choices'       => array(
						'hdqt'         => 'Hội đồng quản trị',
						'ban_dieu_hanh' => 'Ban điều hành',
					),
					'default_value' => 'hdqt',
					'allow_null'    => 0,
					'instructions'  => 'Thành viên thuộc nhóm nào. Mỗi nhóm hiển thị thành một hàng riêng trên trang.',
				),

				// TAB: Tiếng Việt
				array(
					'key'   => 'field_qp_board_tab_vi',
					'label' => 'Tiếng Việt',
					'type'  => 'tab',
				),
				array(
					'key'          => 'field_qp_board_member_name',
					'label'        => 'Tên hiển thị',
					'name'         => 'board_name',
					'type'         => 'text',
					'instructions' => 'Để trống sẽ dùng tiêu đề bài viết. Có thể nhập \n để ép xuống dòng, ví dụ: Bà Nguyễn Thị\nThùy Ngân.',
				),
				array(
					'key'          => 'field_qp_board_member_role',
					'label'        => 'Chức danh',
					'name'         => 'board_role',
					'type'         => 'text',
					'instructions' => 'Ví dụ: Chủ tịch hội đồng quản trị. Có thể nhập \n để ép xuống dòng.',
				),

				// TAB: English
				array(
					'key'   => 'field_qp_board_tab_en',
					'label' => 'English',
					'type'  => 'tab',
				),
				array(
					'key'          => 'field_qp_board_member_name_en',
					'label'        => 'Name (EN)',
					'name'         => 'board_name_en',
					'type'         => 'text',
					'instructions' => 'Để trống sẽ dùng tên tiếng Việt.',
				),
				array(
					'key'          => 'field_qp_board_member_role_en',
					'label'        => 'Role (EN)',
					'name'         => 'board_role_en',
					'type'         => 'text',
					'instructions' => 'Để trống sẽ dùng chức danh tiếng Việt.',
				),

				// TAB: 中文
				array(
					'key'   => 'field_qp_board_tab_zh',
					'label' => '中文',
					'type'  => 'tab',
				),
				array(
					'key'          => 'field_qp_board_member_name_zh',
					'label'        => '姓名 (ZH)',
					'name'         => 'board_name_zh',
					'type'         => 'text',
					'instructions' => 'Để trống sẽ dùng tên tiếng Việt.',
				),
				array(
					'key'          => 'field_qp_board_member_role_zh',
					'label'        => '职位 (ZH)',
					'name'         => 'board_role_zh',
					'type'         => 'text',
					'instructions' => 'Để trống sẽ dùng chức danh tiếng Việt.',
				),

				// TAB: Ảnh
				array(
					'key'   => 'field_qp_board_tab_photo',
					'label' => 'Ảnh',
					'type'  => 'tab',
				),
				array(
					'key'          => 'field_qp_board_member_photo',
					'label'        => 'Ảnh chân dung (tuỳ chọn)',
					'name'         => 'board_photo',
					'type'         => 'image',
					'return_format' => 'url',
					'instructions' => 'Ảnh nền trong suốt (PNG) sẽ đẹp nhất. <strong>Tỷ lệ chuẩn 2:3 (dọc) — khuyến nghị 1278 × 1920px</strong> (chân dung chiếm phần dưới khung, đầu hơi vượt lên trên). Để trống sẽ dùng "Ảnh đại diện" (featured image) bên phải.',
				),
			),
			'location' => array(
				array(
					array( 'param' => 'post_type', 'operator' => '==', 'value' => 'qp_board_member' ),
				),
			),
		)
	);
}
add_action( 'acf/init', 'qp_holdings_register_board_acf' );

/**
 * Show the portrait + Order columns in the board member list table so editors
 * can see the photo and reorder without ACF.
 */
function qp_holdings_board_admin_columns( array $columns ): array {
	$new = array();
	foreach ( $columns as $key => $label ) {
		if ( 'title' === $key ) {
			$new['qp_board_photo'] = 'Ảnh';
		}
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['qp_board_role']  = 'Chức danh';
			$new['qp_board_group'] = 'Nhóm';
			$new['qp_board_order'] = 'Thứ tự';
		}
	}
	return $new;
}
add_filter( 'manage_qp_board_member_posts_columns', 'qp_holdings_board_admin_columns' );

function qp_holdings_board_admin_column_content( string $column, int $post_id ): void {
	if ( 'qp_board_photo' === $column ) {
		$photo = qp_holdings_acf_or_meta( $post_id, 'board_photo', 'board_photo' );
		$url   = qp_holdings_image_url( $photo, $post_id, 'thumbnail' );
		echo $url
			? '<img src="' . esc_url( $url ) . '" alt="" style="width:42px;height:56px;object-fit:contain;">'
			: '—'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} elseif ( 'qp_board_role' === $column ) {
		echo esc_html( (string) qp_holdings_acf_or_meta( $post_id, 'board_role', 'board_role' ) );
	} elseif ( 'qp_board_group' === $column ) {
		$group  = (string) qp_holdings_acf_or_meta( $post_id, 'board_group', 'board_group' );
		$labels = qp_holdings_board_group_labels();
		echo esc_html( $labels[ $group ] ?? $labels['hdqt'] );
	} elseif ( 'qp_board_order' === $column ) {
		echo '<span class="dashicons dashicons-menu" style="color:#8c8f94;cursor:move;" title="Kéo để sắp xếp"></span> '
			. '<span style="color:#8c8f94;">' . (int) get_post_field( 'menu_order', $post_id ) . '</span>';
	}
}
add_action( 'manage_qp_board_member_posts_custom_column', 'qp_holdings_board_admin_column_content', 10, 2 );

/**
 * Admin note for manual line breaks in board member names/roles.
 */
function qp_holdings_board_admin_line_break_hint(): void {
	if ( ! is_admin() ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'qp_board_member' !== $screen->post_type || ! in_array( $screen->base, array( 'edit', 'post' ), true ) ) {
		return;
	}

	echo '<div class="notice notice-info"><p>'
		. '<strong>Gợi ý xuống dòng:</strong> '
		. 'Trong ô <em>Tên hiển thị</em> hoặc <em>Chức danh</em>, nhập <code>\n</code> tại vị trí muốn xuống dòng. '
		. 'Ví dụ: <code>Bà Nguyễn Thị\nThùy Ngân</code> sẽ hiển thị thành 2 dòng trên website.'
		. '</p></div>';
}
add_action( 'admin_notices', 'qp_holdings_board_admin_line_break_hint' );

/**
 * One-time migration: convert legacy `board_photo` values that were stored as
 * theme-relative path strings (e.g. "assets/site/board/board-xxx.png") into a
 * real Media Library attachment ID.
 *
 * Older seeds saved the relative path directly. The ACF image field can't
 * resolve a string to an attachment, so opening a member in wp-admin shows an
 * empty image field — and saving (e.g. to edit the role) overwrites the path
 * with an empty value, making the photo vanish. Importing the bundled portrait
 * as an attachment fixes the admin UI and makes the photo survive edits.
 */
function qp_holdings_board_migrate_photo_attachments(): void {
	if ( ! is_admin() ) {
		return;
	}
	if ( get_option( 'qp_holdings_board_photo_attachment_v1' ) ) {
		return;
	}
	if ( ! function_exists( 'qp_holdings_seed_import_theme_attachment' ) ) {
		return;
	}

	$members = get_posts(
		array(
			'post_type'      => 'qp_board_member',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	foreach ( $members as $member_id ) {
		$member_id = (int) $member_id;
		$stored    = get_post_meta( $member_id, 'board_photo', true );

		// Already an attachment ID — nothing to migrate.
		if ( is_numeric( $stored ) ) {
			continue;
		}
		if ( ! is_string( $stored ) || '' === $stored || false === strpos( $stored, 'assets/site/board/' ) ) {
			continue;
		}

		$filename      = basename( $stored );
		$attachment_id = qp_holdings_seed_import_theme_attachment(
			$filename,
			get_the_title( $member_id ),
			'assets/site/board'
		);

		if ( $attachment_id > 0 ) {
			update_post_meta( $member_id, 'board_photo', $attachment_id );
			if ( ! has_post_thumbnail( $member_id ) ) {
				set_post_thumbnail( $member_id, $attachment_id );
			}
		}
	}

	update_option( 'qp_holdings_board_photo_attachment_v1', 1 );
}
add_action( 'admin_init', 'qp_holdings_board_migrate_photo_attachments', 30 );

/**
 * Canonical group keys → Vietnamese labels. The renderer translates the label
 * via qp_holdings_t() so EN/ZH headings come from the i18n dictionary.
 *
 * @return array<string,string>
 */
function qp_holdings_board_group_labels(): array {
	return array(
		'hdqt'          => 'Hội đồng quản trị',
		'ban_dieu_hanh' => 'Ban điều hành',
	);
}

/**
 * Escape board text while preserving manual line breaks.
 *
 * Editors can type a literal "\n" in short text fields, or real newlines when
 * values are seeded from code. Both forms are rendered as <br> on the frontend.
 */
function qp_holdings_board_text_with_breaks( string $text ): string {
	$text = trim( $text );
	$text = str_replace( array( "\r\n", "\r" ), "\n", $text );
	$text = preg_replace( '/[ \t]*\\\\n[ \t]*/', "\n", $text );
	$text = preg_replace( "/\n{3,}/", "\n\n", (string) $text );

	return nl2br( esc_html( $text ), false );
}

/**
 * Apply approved default name line breaks for the bundled board members.
 *
 * This keeps existing seeded database rows aligned with Figma even before an
 * editor re-saves the new "Tên hiển thị" field. Manual breaks still win.
 */
function qp_holdings_board_default_name_breaks( string $name ): string {
	if ( str_contains( $name, "\n" ) || str_contains( $name, '\\n' ) ) {
		return $name;
	}

	$breaks = array(
		'Ông Nguyễn Hồng Anh Khá' => "Ông Nguyễn Hồng\nAnh Khá",
		'Bà Nguyễn Thị Thùy Ngân' => "Bà Nguyễn Thị\nThùy Ngân",
		'Ông Lê Dư Đăng Khoa'     => "Ông Lê Dư\nĐăng Khoa",
		'Mr. Nguyen Hong Anh Kha' => "Mr. Nguyen Hong\nAnh Kha",
		'Ms. Nguyen Thi Thuy Ngan' => "Ms. Nguyen Thi\nThuy Ngan",
		'Mr. Le Du Dang Khoa'     => "Mr. Le Du\nDang Khoa",
	);

	return $breaks[ $name ] ?? $name;
}

/**
 * Canonical Figma order for the bundled board portraits.
 *
 * Production content can predate the board_group/menu_order migration, so the
 * renderer keeps these seeded members aligned with the approved Figma layout.
 * Unknown manually-added members still use their stored group/menu_order.
 *
 * @return array<string,array{group:string,order:int}>
 */
function qp_holdings_board_canonical_member_map(): array {
	return array(
		'pham-tu-trong'         => array( 'group' => 'hdqt', 'order' => 10 ),
		'nguyen-hong-anh-kha'   => array( 'group' => 'hdqt', 'order' => 20 ),
		'pham-duy'              => array( 'group' => 'hdqt', 'order' => 30 ),
		'nguyen-thi-thuy-ngan'  => array( 'group' => 'hdqt', 'order' => 40 ),
		'le-minh-anh'           => array( 'group' => 'hdqt', 'order' => 50 ),
		'nguyen-manh-tuan'      => array( 'group' => 'ban_dieu_hanh', 'order' => 10 ),
		'le-du-dang-khoa'       => array( 'group' => 'ban_dieu_hanh', 'order' => 20 ),
		'tran-trac-long'        => array( 'group' => 'ban_dieu_hanh', 'order' => 30 ),
		'nguyen-minh-hai'       => array( 'group' => 'ban_dieu_hanh', 'order' => 40 ),
	);
}

/**
 * Default the board member list to ascending menu_order in admin.
 */
function qp_holdings_board_admin_default_order( $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( 'qp_board_member' !== $query->get( 'post_type' ) ) {
		return;
	}
	if ( ! $query->get( 'orderby' ) ) {
		$query->set( 'orderby', 'menu_order' );
		$query->set( 'order', 'ASC' );
	}
}
add_action( 'pre_get_posts', 'qp_holdings_board_admin_default_order' );

/**
 * Drag-and-drop ordering on the board member list table.
 *
 * Loads jQuery UI sortable on the qp_board_member list screen and makes the
 * table rows draggable. On drop, the new row order is POSTed to admin-ajax and
 * persisted to each post's menu_order (steps of 10). This replaces the need to
 * type a number in the "Thứ tự" / Page Attributes Order box.
 */
function qp_holdings_board_admin_sortable_assets( string $hook ): void {
	if ( 'edit.php' !== $hook ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'qp_board_member' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_script( 'jquery-ui-sortable' );

	$nonce = wp_create_nonce( 'qp_board_reorder' );
	$inline = <<<JS
jQuery(function ($) {
	var \$tbody = $('#the-list');
	if (!\$tbody.length) {
		return;
	}

	// Visual affordance: grab cursor on the title cell.
	\$tbody.find('tr').css('cursor', 'move');

	var \$status = $('<div class="qp-board-reorder-status" style="display:none;position:fixed;top:46px;right:24px;z-index:9999;padding:10px 16px;border-radius:6px;background:#0B1B2A;color:#fff;font-size:13px;box-shadow:0 4px 16px rgba(0,0,0,0.25);"></div>').appendTo('body');
	function flash(msg, ok) {
		\$status.text(msg).css('background', ok ? '#1e7e34' : '#b32d2e').fadeIn(120);
		clearTimeout(\$status.data('t'));
		\$status.data('t', setTimeout(function () { \$status.fadeOut(300); }, 1800));
	}

	\$tbody.sortable({
		items: 'tr',
		axis: 'y',
		cursor: 'move',
		opacity: 0.7,
		placeholder: 'qp-board-sort-placeholder',
		helper: function (e, tr) {
			// Keep column widths while dragging.
			var \$orig = tr.children();
			var \$helper = tr.clone();
			\$helper.children().each(function (i) {
				$(this).width(\$orig.eq(i).width());
			});
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
			$.post(ajaxurl, {
				action: 'qp_board_reorder',
				nonce: '{$nonce}',
				order: ids
			}).done(function (res) {
				flash((res && res.data && res.data.message) ? res.data.message : 'Đã lưu thứ tự', !!(res && res.success));
			}).fail(function () {
				flash('Lưu thứ tự thất bại', false);
			}).always(function () {
				\$tbody.sortable('enable');
			});
		}
	});
});
JS;
	wp_add_inline_script( 'jquery-ui-sortable', $inline );

	$css = '.qp-board-sort-placeholder{outline:2px dashed #D3A049;background:#fff8e9;}'
		. '.qp-board-sort-placeholder td{visibility:hidden;}';
	wp_add_inline_style( 'wp-admin', $css );
}
add_action( 'admin_enqueue_scripts', 'qp_holdings_board_admin_sortable_assets' );

/**
 * Persist the drag-and-drop order from the board member list table.
 */
function qp_holdings_board_reorder_ajax(): void {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'Không có quyền.' ), 403 );
	}
	check_ajax_referer( 'qp_board_reorder', 'nonce' );

	$order = isset( $_POST['order'] ) ? (array) wp_unslash( $_POST['order'] ) : array();
	$order = array_values( array_filter( array_map( 'absint', $order ) ) );
	if ( empty( $order ) ) {
		wp_send_json_error( array( 'message' => 'Danh sách trống.' ) );
	}

	$position = 0;
	foreach ( $order as $post_id ) {
		if ( 'qp_board_member' !== get_post_type( $post_id ) ) {
			continue;
		}
		$position += 10;
		wp_update_post(
			array(
				'ID'         => $post_id,
				'menu_order' => $position,
			)
		);
	}

	wp_send_json_success( array( 'message' => 'Đã lưu thứ tự mới.' ) );
}
add_action( 'wp_ajax_qp_board_reorder', 'qp_holdings_board_reorder_ajax' );

/**
 * Fetch published board members ordered by menu_order then title.
 *
 * @return WP_Post[]
 */
function qp_holdings_get_board_members(): array {
	$members = get_posts(
		array(
			'post_type'      => 'qp_board_member',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
		)
	);
	return is_array( $members ) ? $members : array();
}

/**
 * Render the Board of Directors section. Returns '' when no members exist.
 *
 * Members are split into two stacked groups (Hội đồng quản trị / Ban điều hành)
 * matching the Figma layout. Names and roles are localized per current language
 * via per-member ACF fields (board_name_en/zh, board_role_en/zh) with the
 * Vietnamese title/board_role as fallback.
 */
function qp_holdings_render_board_section(): string {
	$members = qp_holdings_get_board_members();
	if ( empty( $members ) ) {
		return '';
	}

	$lang = function_exists( 'qp_holdings_current_language' ) ? qp_holdings_current_language() : 'vi';

	$t = static function ( string $vi ) use ( $lang ): string {
		return function_exists( 'qp_holdings_t' ) ? qp_holdings_t( $vi ) : $vi;
	};

	// Localized value: prefer the per-language ACF field, else the VI default.
	$localized = static function ( int $post_id, string $base, string $vi_default ) use ( $lang ): string {
		$base_val = qp_holdings_acf_or_meta( $post_id, $base, $base );
		$base_val = is_string( $base_val ) ? trim( $base_val ) : '';

		if ( 'vi' === $lang ) {
			return '' !== $base_val ? $base_val : $vi_default;
		}
		$val = qp_holdings_acf_or_meta( $post_id, $base . '_' . $lang, $base . '_' . $lang );
		$val = is_string( $val ) ? trim( $val ) : '';
		return '' !== $val ? $val : ( '' !== $base_val ? $base_val : $vi_default );
	};

	// Section aria-label (not rendered as a duplicate heading): the two group
	// headings below are the real titles, matching Figma.
	$section_label = $t( 'Hội đồng quản trị' );

	// Bucket members by group, preserving menu_order.
	$group_labels = qp_holdings_board_group_labels();
	$canonical    = qp_holdings_board_canonical_member_map();
	$member_slug  = static function ( WP_Post $member ): string {
		$slug = (string) $member->post_name;
		return str_starts_with( $slug, 'tv-' ) ? substr( $slug, 3 ) : $slug;
	};
	$buckets      = array();
	foreach ( array_keys( $group_labels ) as $gkey ) {
		$buckets[ $gkey ] = array();
	}
	foreach ( $members as $member ) {
		$key  = $member_slug( $member );
		$gkey = $canonical[ $key ]['group'] ?? (string) qp_holdings_acf_or_meta( (int) $member->ID, 'board_group', 'board_group' );
		if ( ! isset( $buckets[ $gkey ] ) ) {
			$gkey = 'hdqt';
		}
		$buckets[ $gkey ][] = $member;
	}

	foreach ( $buckets as &$group_members ) {
		usort(
			$group_members,
			static function ( WP_Post $a, WP_Post $b ) use ( $canonical, $member_slug ): int {
				$a_key   = $member_slug( $a );
				$b_key   = $member_slug( $b );
				$a_order = $canonical[ $a_key ]['order'] ?? ( 10000 + (int) $a->menu_order );
				$b_order = $canonical[ $b_key ]['order'] ?? ( 10000 + (int) $b->menu_order );
				return $a_order <=> $b_order;
			}
		);
	}
	unset( $group_members );

	$render_card = static function ( WP_Post $member ) use ( $localized ): string {
		$photo     = qp_holdings_acf_or_meta( (int) $member->ID, 'board_photo', 'board_photo' );
		$photo_url = qp_holdings_image_url( $photo, (int) $member->ID, 'large' );
		$name      = qp_holdings_board_default_name_breaks( $localized( (int) $member->ID, 'board_name', get_the_title( $member ) ) );
		$role      = $localized( (int) $member->ID, 'board_role', (string) qp_holdings_acf_or_meta( (int) $member->ID, 'board_role', 'board_role' ) );
		$name_alt  = preg_replace( '/\s+/', ' ', str_replace( '\\n', ' ', $name ) );

		$img = '';
		if ( $photo_url ) {
			$img = sprintf(
				'<img class="board-card__photo" src="%s" alt="%s" loading="lazy" decoding="async">',
				esc_url( $photo_url ),
				esc_attr( (string) $name_alt )
			);
		}

		return '<figure class="board-card reveal">'
			. '<div class="board-card__frame">' . $img . '</div>'
			. '<figcaption class="board-card__info">'
			. '<span class="board-card__name">' . qp_holdings_board_text_with_breaks( $name ) . '</span>'
			. ( '' !== $role ? '<span class="board-card__role">' . qp_holdings_board_text_with_breaks( $role ) . '</span>' : '' )
			. '</figcaption>'
			. '</figure>';
	};

	// Build each non-empty group: heading + its own centered card row.
	$groups_html = '';
	foreach ( $buckets as $gkey => $group_members ) {
		if ( empty( $group_members ) ) {
			continue;
		}
		$group_title = $t( $group_labels[ $gkey ] );
		$cards       = '';
		foreach ( $group_members as $member ) {
			$cards .= $render_card( $member );
		}
		$groups_html .= '<div class="board-group board-group--' . esc_attr( $gkey ) . '">'
			. '<h3 class="board-group__title navy-gradient-text reveal">' . esc_html( $group_title ) . '</h3>'
			. '<div class="board-grid">' . $cards . '</div>'
			. '</div>';
	}

	return '<section class="board-section" aria-label="' . esc_attr( $section_label ) . '">'
		. '<div class="container">'
		. '<div class="board-header reveal">'
		. '<h2 class="section-title board-title navy-gradient-text"></h2>'
		. '<p class="board-intro"></p>'
		. '</div>'
		. $groups_html
		. '</div>'
		. '</section>';
}

/**
 * Seed the 9 board members from the bundled Figma portraits (idempotent).
 *
 * Members are split into two groups matching Figma:
 *   - hdqt          : Hội đồng quản trị (Board of Directors)
 *   - ban_dieu_hanh : Ban điều hành (Executive Board)
 * EN/ZH name + role are seeded so language switching works out of the box.
 */
function qp_holdings_seed_board_members( bool $force = false ): void {
	$members = array(
		// ── Hội đồng quản trị (thứ tự theo Figma) ──
		array(
			'slug' => 'pham-tu-trong', 'group' => 'hdqt', 'img' => 'board-pham-tu-trong.png',
			'name' => 'Ông Phạm Tự Trọng', 'role' => 'Chủ tịch hội đồng quản trị',
			'name_en' => 'Mr. Pham Tu Trong', 'role_en' => 'Chairman of the Board of Directors',
			'name_zh' => '范自重先生', 'role_zh' => '董事会主席',
		),
		array(
			'slug' => 'nguyen-hong-anh-kha', 'group' => 'hdqt', 'img' => 'board-nguyen-hong-anh-kha.png',
			'name' => 'Ông Nguyễn Hồng Anh Khá', 'display_name' => "Ông Nguyễn Hồng\nAnh Khá", 'role' => 'Thành viên hội đồng quản trị',
			'name_en' => 'Mr. Nguyen Hong Anh Kha', 'display_name_en' => "Mr. Nguyen Hong\nAnh Kha", 'role_en' => 'Member of the Board of Directors',
			'name_zh' => '阮鸿英卡先生', 'role_zh' => '董事会成员',
		),
		array(
			'slug' => 'pham-duy', 'group' => 'hdqt', 'img' => 'board-pham-duy.png',
			'name' => 'Ông Phạm Duy', 'role' => 'Chủ tịch Ủy ban kiểm toán',
			'name_en' => 'Mr. Pham Duy', 'role_en' => 'Chairman of the Audit Committee',
			'name_zh' => '范维先生', 'role_zh' => '审计委员会主席',
		),
		array(
			'slug' => 'nguyen-thi-thuy-ngan', 'group' => 'hdqt', 'img' => 'board-nguyen-thi-thuy-ngan.png',
			'name' => 'Bà Nguyễn Thị Thùy Ngân', 'display_name' => "Bà Nguyễn Thị\nThùy Ngân", 'role' => 'Thành viên Ủy ban kiểm toán',
			'name_en' => 'Ms. Nguyen Thi Thuy Ngan', 'display_name_en' => "Ms. Nguyen Thi\nThuy Ngan", 'role_en' => 'Audit Committee Member',
			'name_zh' => '阮氏垂银女士', 'role_zh' => '审计委员会成员',
		),
		array(
			'slug' => 'le-minh-anh', 'group' => 'hdqt', 'img' => 'board-le-minh-anh.png',
			'name' => 'Bà Lê Minh Anh', 'role' => 'Phụ trách quản trị',
			'name_en' => 'Ms. Le Minh Anh', 'role_en' => 'Head of Corporate Governance',
			'name_zh' => '黎明英女士', 'role_zh' => '公司治理负责人',
		),
		// ── Ban điều hành (thứ tự theo Figma) ──
		array(
			'slug' => 'nguyen-manh-tuan', 'group' => 'ban_dieu_hanh', 'img' => 'board-nguyen-manh-tuan.png',
			'name' => 'Ông Nguyễn Mạnh Tuấn', 'role' => 'Tổng giám đốc',
			'name_en' => 'Mr. Nguyen Manh Tuan', 'role_en' => 'General Director',
			'name_zh' => '阮孟俊先生', 'role_zh' => '总经理',
		),
		array(
			'slug' => 'le-du-dang-khoa', 'group' => 'ban_dieu_hanh', 'img' => 'board-le-du-dang-khoa.png',
			'name' => 'Ông Lê Dư Đăng Khoa', 'display_name' => "Ông Lê Dư\nĐăng Khoa", 'role' => 'Phó tổng giám đốc',
			'name_en' => 'Mr. Le Du Dang Khoa', 'display_name_en' => "Mr. Le Du\nDang Khoa", 'role_en' => 'Deputy General Director',
			'name_zh' => '黎余登科先生', 'role_zh' => '副总经理',
		),
		array(
			'slug' => 'tran-trac-long', 'group' => 'ban_dieu_hanh', 'img' => 'board-tran-trac-long.png',
			'name' => 'Ông Trác Văn Long', 'role' => 'Giám đốc tài chính',
			'name_en' => 'Mr. Trac Van Long', 'role_en' => 'Chief Financial Officer',
			'name_zh' => '卓文龙先生', 'role_zh' => '首席财务官',
		),
		array(
			'slug' => 'nguyen-minh-hai', 'group' => 'ban_dieu_hanh', 'img' => 'board-nguyen-minh-hai.png',
			'name' => 'Ông Nguyễn Minh Hải', 'role' => 'Kế toán trưởng',
			'name_en' => 'Mr. Nguyen Minh Hai', 'role_en' => 'Chief Accountant',
			'name_zh' => '阮明海先生', 'role_zh' => '首席会计师',
		),
	);

	$order = 0;
	foreach ( $members as $m ) {
		$order += 10;
		$post_id = qp_holdings_seed_upsert_post(
			array(
				'post_type'   => 'qp_board_member',
				'post_name'   => 'tv-' . $m['slug'],
				'post_title'  => $m['name'],
				'post_status' => 'publish',
				'menu_order'  => $order,
			),
			$force
		);
		if ( ! $post_id ) {
			continue;
		}

		update_post_meta( $post_id, 'board_name', $m['display_name'] ?? $m['name'] );
		update_post_meta( $post_id, 'board_role', $m['role'] );
		update_post_meta( $post_id, 'board_group', $m['group'] );
		update_post_meta( $post_id, 'board_name_en', $m['display_name_en'] ?? $m['name_en'] );
		update_post_meta( $post_id, 'board_role_en', $m['role_en'] );
		update_post_meta( $post_id, 'board_name_zh', $m['name_zh'] );
		update_post_meta( $post_id, 'board_role_zh', $m['role_zh'] );

		// Import the bundled portrait into the Media Library so the ACF image
		// field can resolve it (shows a thumbnail in admin, survives edits, and
		// lets editors swap the photo). Store the attachment ID, not a raw path:
		// a relative-path string can't be resolved by ACF and gets wiped to
		// empty the first time the post is saved in wp-admin.
		$attachment_id = 0;
		if ( function_exists( 'qp_holdings_seed_import_theme_attachment' ) ) {
			$attachment_id = qp_holdings_seed_import_theme_attachment(
				$m['img'],
				$m['name'],
				'assets/site/board'
			);
		}

		if ( $attachment_id > 0 ) {
			update_post_meta( $post_id, 'board_photo', $attachment_id );
			set_post_thumbnail( $post_id, $attachment_id );
		} elseif ( ! get_post_meta( $post_id, 'board_photo', true ) ) {
			// Fallback when the file can't be imported (e.g. filesystem perms):
			// keep the theme-relative path so the frontend still renders.
			update_post_meta( $post_id, 'board_photo', 'assets/site/board/' . $m['img'] );
		}
	}
}
