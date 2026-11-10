<?php
/**
 * Extend the TinyMCE classic editor (used by the Classic block in Gutenberg
 * and by Classic Editor) with more formatting tools.
 *
 * Scope: post types returned by qp_holdings_editor_enhanced_post_types()
 *        — currently qp_project and qp_news.
 *
 * Additions:
 *   • Row 2:   "Formats" dropdown (custom styles) prepended
 *   • Row 3:   font family, font size, background color, sub/super, strike,
 *              horizontal rule, table
 *   • Row 4:   reserved for future tools
 *   • Style formats dropdown:
 *       - Văn bản: Lead paragraph, văn bản nhỏ, tô vàng, gạch chân vàng
 *       - Khối nội dung: callout box, blockquote, info box, khối nền navy
 *       - Danh sách: list with check icons, list with arrow icons
 *   • Block formats: Vietnamese labels for Paragraph / Heading 1–6 / Preformatted /
 *     Blockquote (TinyMCE Block dropdown)
 *   • Font sizes: 12 → 72 px
 *   • Font families: Noto Sans, Inter, Roboto, System, Serif, Monospace
 *   • Plugins enabled: textcolor + hr (bundled) and table (shipped in
 *     assets/tinymce/, registered via mce_external_plugins).
 *   • Editor preview CSS so the custom styles look right inside the editor.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post types that get the enhanced editor toolbar/formats.
 *
 * @return string[]
 */
function qp_holdings_editor_enhanced_post_types(): array {
	/**
	 * Filter the list of post types that receive the enhanced TinyMCE editor.
	 *
	 * @param string[] $post_types Post type slugs.
	 */
	return (array) apply_filters(
		'qp_holdings_editor_enhanced_post_types',
		array( 'qp_project', 'qp_news' )
	);
}

/**
 * Are we editing (or creating) a post of an enhanced post type in the admin?
 * The check works during the early TinyMCE filter chain when get_current_screen()
 * is not yet available, by falling back to the request parameters.
 */
function qp_holdings_is_editor_enhanced(): bool {
	if ( ! is_admin() ) {
		return false;
	}

	$enhanced = qp_holdings_editor_enhanced_post_types();

	if ( function_exists( 'get_current_screen' ) ) {
		$screen = get_current_screen();
		if ( is_object( $screen ) && isset( $screen->post_type ) && in_array( $screen->post_type, $enhanced, true ) ) {
			return true;
		}
	}

	// New post screen — ?post_type=qp_news.
	if ( isset( $_GET['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$pt = sanitize_key( wp_unslash( $_GET['post_type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( in_array( $pt, $enhanced, true ) ) {
			return true;
		}
	}

	// Edit screen — ?post=ID&action=edit.
	if ( isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_id = (int) $_GET['post']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $post_id > 0 && in_array( get_post_type( $post_id ), $enhanced, true ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Backward-compatible alias kept for any external callers.
 *
 * @deprecated Use qp_holdings_is_editor_enhanced().
 */
function qp_holdings_is_editing_project(): bool {
	return qp_holdings_is_editor_enhanced();
}

/**
 * Row 1 of the TinyMCE toolbar: Add alignjustify button.
 */
function qp_holdings_mce_buttons_project( array $buttons ): array {
	if ( ! qp_holdings_is_editor_enhanced() ) {
		return $buttons;
	}

	$pos = array_search( 'alignright', $buttons, true );
	if ( false !== $pos ) {
		array_splice( $buttons, $pos + 1, 0, 'alignjustify' );
	} elseif ( ! in_array( 'alignjustify', $buttons, true ) ) {
		$buttons[] = 'alignjustify';
	}
	return $buttons;
}
add_filter( 'mce_buttons', 'qp_holdings_mce_buttons_project' );

/**
 * Row 2 of the TinyMCE toolbar.
 */
function qp_holdings_mce_buttons_2_project( array $buttons ): array {
	if ( ! qp_holdings_is_editor_enhanced() ) {
		return $buttons;
	}

	// Add "Formats" dropdown at the very beginning of row 2.
	array_unshift( $buttons, 'styleselect' );
	return $buttons;
}
add_filter( 'mce_buttons_2', 'qp_holdings_mce_buttons_2_project' );

/**
 * Row 3 of the TinyMCE toolbar (empty by default).
 */
function qp_holdings_mce_buttons_3_project( array $buttons ): array {
	if ( ! qp_holdings_is_editor_enhanced() ) {
		return $buttons;
	}

	return array_merge(
		$buttons,
		array(
			'fontselect',
			'fontsizeselect',
			'backcolor',
			'strikethrough',
			'subscript',
			'superscript',
			'hr',
			'table',
		)
	);
}
add_filter( 'mce_buttons_3', 'qp_holdings_mce_buttons_3_project' );

/**
 * Row 4 of the TinyMCE toolbar (empty by default).
 */
function qp_holdings_mce_buttons_4_project( array $buttons ): array {
	if ( ! qp_holdings_is_editor_enhanced() ) {
		return $buttons;
	}

	return $buttons;
}
add_filter( 'mce_buttons_4', 'qp_holdings_mce_buttons_4_project' );

/**
 * Enable additional bundled TinyMCE plugins required by the new toolbar buttons.
 */
function qp_holdings_tiny_mce_plugins_project( array $plugins ): array {
	if ( ! qp_holdings_is_editor_enhanced() ) {
		return $plugins;
	}

	foreach ( array( 'textcolor', 'hr' ) as $plugin ) {
		if ( ! in_array( $plugin, $plugins, true ) ) {
			$plugins[] = $plugin;
		}
	}
	return $plugins;
}
add_filter( 'tiny_mce_plugins', 'qp_holdings_tiny_mce_plugins_project' );

/**
 * Register external TinyMCE plugins that WordPress core does not bundle.
 *
 * WordPress ships a trimmed TinyMCE 4.9.x build without the `table` plugin, so
 * we serve a version-matched copy from the theme. The file in
 * assets/tinymce/table/plugin.min.js is the official TinyMCE 4.9.11 table
 * plugin (matching this WP's $tinymce_version = 49110-*).
 */
function qp_holdings_mce_external_plugins( array $plugins ): array {
	if ( ! qp_holdings_is_editor_enhanced() ) {
		return $plugins;
	}

	$base = get_template_directory_uri() . '/assets/tinymce/';
	$path = get_template_directory() . '/assets/tinymce/table/plugin.min.js';

	$url = $base . 'table/plugin.min.js';
	if ( file_exists( $path ) ) {
		$url = add_query_arg( 'v', (string) filemtime( $path ), $url );
	}
	$plugins['table'] = $url;

	return $plugins;
}
add_filter( 'mce_external_plugins', 'qp_holdings_mce_external_plugins' );

/**
 * Configure TinyMCE: block formats, style formats, font sizes, font families.
 */
function qp_holdings_tinymce_init_project( array $init ): array {
	if ( ! qp_holdings_is_editor_enhanced() ) {
		return $init;
	}

	// Block formats dropdown labels (Vietnamese).
	$init['block_formats'] =
		'Đoạn văn=p;' .
		'Tiêu đề 1=h1;' .
		'Tiêu đề 2=h2;' .
		'Tiêu đề 3=h3;' .
		'Tiêu đề 4=h4;' .
		'Tiêu đề 5=h5;' .
		'Tiêu đề 6=h6;' .
		'Định dạng sẵn=pre;' .
		'Trích dẫn=blockquote;';

	// Font sizes (px).
	$init['fontsize_formats'] = '12px 13px 14px 15px 16px 18px 20px 22px 24px 26px 28px 32px 36px 40px 48px 56px 64px 72px';

	// Font families — keep aligned with the theme stack.
	$init['font_formats'] =
		'Arial=Arial, Helvetica, sans-serif;' .
		'Noto Sans=Noto Sans, sans-serif;' .
		'Inter=Inter, sans-serif;' .
		'Roboto=Roboto, sans-serif;' .
		'System=-apple-system, BlinkMacSystemFont, Segoe UI, sans-serif;' .
		'Serif=Georgia, Times New Roman, serif;' .
		'Monospace=Menlo, Consolas, monospace;';

	// Custom "Formats" dropdown content.
	$style_formats = array(
		array(
			'title' => 'Văn bản',
			'items' => array(
				array(
					'title'   => 'Lead paragraph',
					'block'   => 'p',
					'classes' => 'qp-content-lead',
				),
				array(
					'title'   => 'Văn bản nhỏ',
					'inline'  => 'span',
					'classes' => 'qp-content-small',
				),
				array(
					'title'   => 'Tô vàng',
					'inline'  => 'span',
					'classes' => 'qp-content-highlight-gold',
				),
				array(
					'title'   => 'Gạch chân vàng',
					'inline'  => 'span',
					'classes' => 'qp-content-underline-gold',
				),
			),
		),
		array(
			'title' => 'Khối nội dung',
			'items' => array(
				array(
					'title'   => 'Khối nổi bật',
					'block'   => 'div',
					'classes' => 'qp-content-callout',
					'wrapper' => true,
				),
				array(
					'title'   => 'Khối trích dẫn',
					'block'   => 'blockquote',
					'classes' => 'qp-content-quote',
					'wrapper' => true,
				),
				array(
					'title'   => 'Khối thông tin',
					'block'   => 'div',
					'classes' => 'qp-content-info-box',
					'wrapper' => true,
				),
				array(
					'title'   => 'Khối nền navy',
					'block'   => 'div',
					'classes' => 'qp-content-navy-block',
					'wrapper' => true,
				),
			),
		),
		array(
			'title' => 'Danh sách',
			'items' => array(
				array(
					'title'    => 'Danh sách có icon check',
					'selector' => 'ul',
					'classes'  => 'qp-content-list-check',
				),
				array(
					'title'    => 'Danh sách dấu mũi tên',
					'selector' => 'ul',
					'classes'  => 'qp-content-list-arrow',
				),
			),
		),
	);

	$init['style_formats']          = wp_json_encode( $style_formats );
	$init['style_formats_merge']    = false;
	$init['style_formats_autohide'] = true;

	// Use CSS margin for indentation so the saved HTML works on the frontend.
	$init['indent_use_margin'] = true;

	// Make sure TinyMCE keeps span/div/list class+style attributes and table
	// markup when switching between Visual and Text modes.
	$extended = 'span[id|class|style|lang|title],div[id|class|style|lang|title],i[id|class|style],ul[id|class|style],ol[id|class|style],li[id|class|style],'
		. 'table[id|class|style|border|cellpadding|cellspacing|width],thead,tbody,tfoot,'
		. 'tr[id|class|style],th[id|class|style|scope|colspan|rowspan|width],td[id|class|style|colspan|rowspan|width],caption,col[span|width],colgroup[span|width]';
	$init['extended_valid_elements'] = isset( $init['extended_valid_elements'] )
		? $init['extended_valid_elements'] . ',' . $extended
		: $extended;

	// Default appearance for tables inserted via the table plugin.
	$init['table_default_attributes'] = wp_json_encode( array( 'class' => 'qp-content-table' ) );
	$init['table_default_styles']     = wp_json_encode( array( 'width' => '100%' ) );

	return $init;
}
add_filter( 'tiny_mce_before_init', 'qp_holdings_tinymce_init_project' );

/**
 * Inject the editor preview stylesheet so the custom formats look right
 * in the visual editor (Classic block / Classic Editor).
 */
function qp_holdings_mce_css_project( string $stylesheets ): string {
	if ( ! qp_holdings_is_editor_enhanced() ) {
		return $stylesheets;
	}

	$url = get_template_directory_uri() . '/assets/editor-style.css';
	// Bust browser cache when the file changes.
	$path = get_template_directory() . '/assets/editor-style.css';
	if ( file_exists( $path ) ) {
		$url = add_query_arg( 'v', (string) filemtime( $path ), $url );
	}

	return $stylesheets ? $stylesheets . ',' . $url : $url;
}
add_filter( 'mce_css', 'qp_holdings_mce_css_project' );

