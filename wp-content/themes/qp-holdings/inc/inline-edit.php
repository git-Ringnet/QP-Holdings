<?php
/**
 * Inline static-text editing for QP Holdings.
 *
 * Lets users with `edit_theme_options` edit hard-coded static texts
 * (headings, paragraphs, subtitles — not links/buttons/attributes) directly on
 * the frontend via double-click, with per-language storage (vi/en/zh).
 *
 * Storage: one autoloaded option per language, `qp_holdings_text_overrides_{lang}`,
 * read through qp_holdings_t() in inc/i18n.php (override branch is first).
 *
 * Guest output is byte-identical to the no-feature baseline: editor markup
 * (data-* attributes / wrapper spans) is only emitted for capable users, so
 * page caches and the XSS surface are unaffected.
 *
 * @package QP_Holdings
 */

defined( 'ABSPATH' ) || exit;

/**
 * Capability required to use the inline editor and the admin content screen.
 *
 * @return string
 */
function qp_holdings_inline_edit_cap(): string {
	return 'edit_theme_options';
}

/**
 * Whether the current request should expose inline-editing affordances.
 *
 * @return bool
 */
function qp_holdings_can_inline_edit(): bool {
	return is_user_logged_in() && current_user_can( qp_holdings_inline_edit_cap() );
}

/**
 * Tags allowed inside an `_html` override value. Intentionally tighter than
 * wp_kses_post() so an editor can't inject <img onerror>, <iframe>, <script>…
 *
 * @return array<string,array<string,bool>>
 */
function qp_holdings_inline_edit_html_allowed(): array {
	return array(
		'strong' => array(),
		'b'      => array(),
		'em'     => array(),
		'i'      => array(),
		'br'     => array(),
		'sup'    => array(),
		'sub'    => array(),
		'span'   => array( 'class' => true ),
		'a'      => array(
			'href'   => true,
			'title'  => true,
			'target' => true,
			'rel'    => true,
		),
	);
}

/**
 * Sanitize an override value according to its kind.
 *
 * @param string $raw       Raw (unslashed) value.
 * @param bool   $is_html   Whether the key carries inline HTML.
 * @param bool   $multiline Whether plain text may contain newlines.
 * @return string
 */
function qp_holdings_inline_edit_sanitize_value( string $raw, bool $is_html, bool $multiline ): string {
	if ( $is_html ) {
		return wp_kses( $raw, qp_holdings_inline_edit_html_allowed() );
	}
	if ( $multiline ) {
		return sanitize_textarea_field( $raw );
	}
	return sanitize_text_field( $raw );
}

/**
 * Build the editor attribute string for a key (used by both helpers).
 * Returns an empty string for non-editors so guest markup never changes.
 *
 * @param string $key  Translation key.
 * @param array  $args { Optional. html: bool, multiline: bool }
 * @return string Leading-space-prefixed attribute string, or ''.
 */
function qp_holdings_te_attr_string( string $key, array $args = array() ): string {
	if ( ! qp_holdings_can_inline_edit() ) {
		return '';
	}

	$is_html    = ! empty( $args['html'] );
	$multiline  = ! empty( $args['multiline'] );
	$lang       = qp_holdings_current_language();
	$overrides  = qp_holdings_text_overrides( $lang );
	$is_override = isset( $overrides[ $key ] );
	$is_fallback = ! $is_override && qp_holdings_t_is_fallback( $key, $lang );

	$attrs = array(
		'data-qp-key'      => base64_encode( $key ),
		'data-qp-editable' => '1',
	);
	if ( $is_html ) {
		$attrs['data-qp-html'] = '1';
	}
	if ( $multiline ) {
		$attrs['data-qp-multiline'] = '1';
	}
	if ( $is_override ) {
		$attrs['data-qp-override'] = '1';
	}
	if ( $is_fallback ) {
		$attrs['data-qp-fallback'] = '1';
	}

	$out = '';
	foreach ( $attrs as $name => $value ) {
		$out .= ' ' . $name . '="' . esc_attr( $value ) . '"';
	}
	return $out;
}

/**
 * Echo a translated static string wrapped (for editors only) in an inline
 * <span> carrying inline-edit data attributes. Guests get the bare text.
 *
 * Use for paragraphs / subtitles / standalone headings where an extra inline
 * span is harmless. For elements whose CSS targets the tag directly (or that
 * must remain a single clean node), use qp_holdings_te_attrs() instead.
 *
 * @param string $key  Translation key.
 * @param array  $args { Optional. html: bool, multiline: bool }
 * @return void
 */
function qp_holdings_te( string $key, array $args = array() ): void {
	$is_html = ! empty( $args['html'] );
	$value   = qp_holdings_t( $key );

	if ( ! qp_holdings_can_inline_edit() ) {
		echo $is_html ? $value : esc_html( $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- _html values are kses-sanitized at save.
		return;
	}

	$attr_string = qp_holdings_te_attr_string( $key, $args );
	$inner       = $is_html ? $value : esc_html( $value ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<span' . $attr_string . '>' . $inner . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Echo ONLY the inline-edit attributes, to be placed on an existing element
 * opening tag. The caller is responsible for echoing the value itself.
 *
 * Example:
 *   <h2 class="x"<?php qp_holdings_te_attrs( 'k' ); ?>><?php echo esc_html( qp_holdings_t( 'k' ) ); ?></h2>
 *
 * @param string $key  Translation key.
 * @param array  $args { Optional. html: bool, multiline: bool }
 * @return void
 */
function qp_holdings_te_attrs( string $key, array $args = array() ): void {
	echo qp_holdings_te_attr_string( $key, $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attrs are esc_attr'd individually.
}

/**
 * Enqueue editor assets (admin-only). Piggybacks on the existing `qp-site`
 * handle for localization but ships its own JS/CSS files.
 *
 * @return void
 */
function qp_holdings_inline_edit_enqueue(): void {
	if ( is_admin() || ! qp_holdings_can_inline_edit() ) {
		return;
	}

	$css_rel = 'assets/css/inline-edit.css';
	$js_rel  = 'assets/js/inline-edit.js';
	$css_abs = get_template_directory() . '/' . $css_rel;
	$js_abs  = get_template_directory() . '/' . $js_rel;

	if ( file_exists( $css_abs ) ) {
		wp_enqueue_style(
			'qp-inline-edit',
			get_template_directory_uri() . '/' . $css_rel,
			array(),
			(string) filemtime( $css_abs )
		);
	}

	if ( file_exists( $js_abs ) ) {
		wp_enqueue_script(
			'qp-inline-edit',
			get_template_directory_uri() . '/' . $js_rel,
			array(),
			(string) filemtime( $js_abs ),
			true
		);

		$lang      = qp_holdings_current_language();
		$languages = qp_holdings_supported_languages();
		wp_localize_script(
			'qp-inline-edit',
			'qpInlineEdit',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'qp_inline_text' ),
				'action'    => 'qp_save_inline_text',
				'lang'      => $lang,
				'langLabel' => $languages[ $lang ]['short'] ?? strtoupper( $lang ),
				'i18n'      => array(
					'editing'   => qp_holdings_inline_edit_ui_text( 'editing', $lang ),
					'saved'     => qp_holdings_inline_edit_ui_text( 'saved', $lang ),
					'saveError' => qp_holdings_inline_edit_ui_text( 'save_error', $lang ),
					'expired'   => qp_holdings_inline_edit_ui_text( 'expired', $lang ),
					'hint'      => qp_holdings_inline_edit_ui_text( 'hint', $lang ),
				),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'qp_holdings_inline_edit_enqueue', 20 );

/**
 * Small UI strings for the editor chrome (badge/toasts), localized vi/en/zh.
 *
 * @param string $slug UI string slug.
 * @param string $lang Language code.
 * @return string
 */
function qp_holdings_inline_edit_ui_text( string $slug, string $lang ): string {
	$strings = array(
		'editing'    => array(
			'vi' => 'Đang sửa',
			'en' => 'Editing',
			'zh' => '正在编辑',
		),
		'saved'      => array(
			'vi' => 'Đã lưu',
			'en' => 'Saved',
			'zh' => '已保存',
		),
		'save_error' => array(
			'vi' => 'Lưu thất bại',
			'en' => 'Save failed',
			'zh' => '保存失败',
		),
		'expired'    => array(
			'vi' => 'Phiên hết hạn, vui lòng tải lại trang',
			'en' => 'Session expired, please reload the page',
			'zh' => '会话已过期，请重新加载页面',
		),
		'hint'       => array(
			'vi' => 'Nhấp đúp để sửa nội dung',
			'en' => 'Double-click to edit text',
			'zh' => '双击编辑文本',
		),
	);

	return $strings[ $slug ][ $lang ] ?? $strings[ $slug ]['vi'] ?? '';
}

/**
 * AJAX handler: save (or reset) a single inline-text override.
 *
 * @return void
 */
function qp_holdings_handle_save_inline_text(): void {
	if ( ! qp_holdings_can_inline_edit() ) {
		wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
	}

	check_ajax_referer( 'qp_inline_text', 'nonce' );

	$languages = qp_holdings_supported_languages();
	$lang      = isset( $_POST['lang'] ) ? sanitize_key( wp_unslash( $_POST['lang'] ) ) : '';
	if ( ! isset( $languages[ $lang ] ) ) {
		wp_send_json_error( array( 'message' => 'bad_lang' ), 400 );
	}

	$key_b64 = isset( $_POST['key'] ) ? wp_unslash( $_POST['key'] ) : '';
	$key     = is_string( $key_b64 ) ? base64_decode( $key_b64, true ) : false;
	if ( false === $key || '' === $key ) {
		wp_send_json_error( array( 'message' => 'bad_key' ), 400 );
	}

	$is_html   = ! empty( $_POST['html'] );
	$multiline = ! empty( $_POST['multiline'] );
	$raw       = isset( $_POST['value'] ) ? wp_unslash( $_POST['value'] ) : '';
	if ( ! is_string( $raw ) ) {
		$raw = '';
	}
	if ( strlen( $raw ) > 20000 ) {
		wp_send_json_error( array( 'message' => 'too_long' ), 400 );
	}

	$value = qp_holdings_inline_edit_sanitize_value( $raw, $is_html, $multiline );

	$option_name = 'qp_holdings_text_overrides_' . $lang;
	$overrides   = (array) get_option( $option_name, array() );

	$default = qp_holdings_t_default( $key, $lang );

	// Reset when empty or when the value matches the canonical default
	// (prevents accumulating no-op "phantom" overrides from whitespace drift).
	$reset = ( '' === $value )
		|| ( ! $is_html && trim( $value ) === trim( $default ) )
		|| ( $is_html && $value === $default );

	if ( $reset ) {
		unset( $overrides[ $key ] );
		$effective = $default;
		$is_override = false;
	} else {
		$overrides[ $key ] = $value;
		$effective = $value;
		$is_override = true;
	}

	if ( empty( $overrides ) ) {
		delete_option( $option_name );
	} else {
		update_option( $option_name, $overrides, true );
	}

	qp_holdings_inline_edit_touch_meta();

	wp_send_json_success(
		array(
			'value'      => $effective,
			'isOverride' => $is_override,
			'isHtml'     => $is_html,
		)
	);
}
add_action( 'wp_ajax_qp_save_inline_text', 'qp_holdings_handle_save_inline_text' );

/**
 * Record who/when last changed overrides (used for export metadata).
 *
 * @return void
 */
function qp_holdings_inline_edit_touch_meta(): void {
	update_option(
		'qp_holdings_text_overrides_meta',
		array(
			'version'    => 1,
			'updated_at' => current_time( 'mysql' ),
			'updated_by' => get_current_user_id(),
		),
		false
	);
}

// Admin management screen ("Nội dung tĩnh").
$qp_holdings_inline_edit_admin = get_template_directory() . '/inc/inline-edit-admin.php';
if ( is_admin() && file_exists( $qp_holdings_inline_edit_admin ) ) {
	require_once $qp_holdings_inline_edit_admin;
}
