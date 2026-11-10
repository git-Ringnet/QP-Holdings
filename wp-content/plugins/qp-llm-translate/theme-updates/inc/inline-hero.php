<?php
/**
 * Inline hero-image editing for QP Holdings.
 *
 * Lets users with `edit_theme_options` replace the hard-coded hero banner
 * images directly on the frontend: hover a hero, click the upload icon, pick a
 * file from the Media Library, and the banner is swapped live and persisted.
 *
 * Storage: one autoloaded option, `qp_holdings_hero_overrides`, mapping a hero
 * key to an attachment ID. Overrides apply to every visitor; the editor-only
 * affordances (data-* attributes, overlay button) are emitted only for capable
 * users, so guest markup for a non-overridden hero is byte-identical to the
 * no-feature baseline (page caches and the attack surface are unaffected).
 *
 * @package QP_Holdings
 */

defined( 'ABSPATH' ) || exit;

/**
 * Capability required to replace hero images.
 *
 * @return string
 */
function qp_holdings_hero_edit_cap(): string {
	return 'edit_theme_options';
}

/**
 * Whether the current request should expose hero-editing affordances.
 *
 * @return bool
 */
function qp_holdings_can_edit_hero(): bool {
	return is_user_logged_in() && current_user_can( qp_holdings_hero_edit_cap() );
}

/**
 * Registry of editable heroes: key => [ label, default ] where `default` is the
 * theme-relative path to the hard-coded banner. New heroes must be added here
 * (the AJAX handler validates keys against this list).
 *
 * @return array<string,array{label:string,default:string}>
 */
function qp_holdings_hero_registry(): array {
	return array(
		'home'     => array(
			'label'   => __( 'Trang chủ', 'qp-holdings' ),
			'default' => 'assets/site/hero-bg-1b89f1.png',
		),
		'about'    => array(
			'label'   => __( 'Giới thiệu', 'qp-holdings' ),
			'default' => 'assets/site/about-hero-bg-62c368.png',
		),
		'projects' => array(
			'label'   => __( 'Dự án', 'qp-holdings' ),
			'default' => 'assets/site/projects-hero-bg-79abd3.png',
		),
		'news'     => array(
			'label'   => __( 'Tin tức', 'qp-holdings' ),
			'default' => 'assets/site/about-hero-bg-62c368.png',
		),
		'terms'    => array(
			'label'   => __( 'Điều khoản sử dụng', 'qp-holdings' ),
			'default' => 'assets/site/terms-hero-bg-55c050.png',
		),
		'privacy'  => array(
			'label'   => __( 'Chính sách bảo mật', 'qp-holdings' ),
			'default' => 'assets/site/terms-hero-bg-55c050.png',
		),
		'contact'  => array(
			'label'   => __( 'Liên hệ', 'qp-holdings' ),
			'default' => 'assets/site/contact-hero-bg-3476c8.png',
		),
		'investor' => array(
			'label'   => __( 'Quan hệ nhà đầu tư', 'qp-holdings' ),
			'default' => 'assets/site/about-hero-bg-62c368.png',
		),
		'careers'  => array(
			'label'   => __( 'Tuyển dụng', 'qp-holdings' ),
			'default' => 'assets/site/careers-hero-team-5fdb60.png',
		),
		'faq'      => array(
			'label'   => __( 'FAQ', 'qp-holdings' ),
			'default' => 'assets/site/careers-hero-team-5fdb60.png',
		),
		'profile'  => array(
			'label'   => __( 'Hồ sơ doanh nghiệp', 'qp-holdings' ),
			'default' => 'assets/site/projects-hero-bg-79abd3.png',
		),
	);
}

/**
 * The full override map (key => attachment ID).
 *
 * @return array<string,int>
 */
function qp_holdings_hero_overrides(): array {
	$overrides = get_option( 'qp_holdings_hero_overrides', array() );
	return is_array( $overrides ) ? $overrides : array();
}

/**
 * Resolve a hero key to its override image URL, or '' when not overridden.
 *
 * @param string $key  Hero key.
 * @param string $size Image size.
 * @return string
 */
function qp_holdings_hero_override_url( string $key, string $size = 'full' ): string {
	$overrides = qp_holdings_hero_overrides();
	if ( empty( $overrides[ $key ] ) ) {
		return '';
	}
	$url = wp_get_attachment_image_url( (int) $overrides[ $key ], $size );
	return $url ? $url : '';
}

/**
 * Build the editor-only attribute string for a hero slot. Returns '' for
 * non-editors so guest markup never changes.
 *
 * @param string $key         Hero key.
 * @param string $default_url Absolute URL of the hard-coded default image.
 * @param bool   $is_override Whether an override is currently active.
 * @return string Leading-space-prefixed attribute string, or ''.
 */
function qp_holdings_hero_attr_string( string $key, string $default_url, bool $is_override ): string {
	if ( ! qp_holdings_can_edit_hero() ) {
		return '';
	}

	$attrs = array(
		'data-qp-hero-key'     => $key,
		'data-qp-hero-default' => $default_url,
	);
	if ( $is_override ) {
		$attrs['data-qp-hero-override'] = '1';
	}

	$out = '';
	foreach ( $attrs as $name => $value ) {
		$out .= ' ' . $name . '="' . esc_attr( $value ) . '"';
	}
	return $out;
}

/**
 * Render a hero banner image with inline-replace support.
 *
 * Drop-in replacement for direct qp_holdings_theme_picture() calls in hero
 * markup. Behaviour:
 *  - Override set  → a plain <img> pointing at the uploaded file.
 *  - No override   → the responsive <picture> from qp_holdings_theme_picture().
 *  - Editors       → the markup is wrapped in a .qp-hero-slot span carrying the
 *                    data-* hooks the JS needs; guests get the bare markup.
 *
 * @param string $key           Hero key (must exist in the registry).
 * @param string $relative_path Theme-relative default image path.
 * @param array  $img_args      <img> attributes passed through to the helper.
 * @param string $sizes         `sizes` attribute for the responsive sources.
 * @return string
 */
function qp_holdings_hero_picture( string $key, string $relative_path, array $img_args = array(), string $sizes = '100vw' ): string {
	$override_url = qp_holdings_hero_override_url( $key );
	$is_override  = ( '' !== $override_url );

	if ( $is_override ) {
		$attrs = $img_args;
		$attrs['src'] = $override_url;
		if ( ! isset( $attrs['alt'] ) ) {
			$attrs['alt'] = '';
		}
		unset( $attrs['width'], $attrs['height'] );

		$parts = array();
		foreach ( $attrs as $name => $value ) {
			if ( null === $value || '' === $value ) {
				// Keep an explicit empty alt; drop other empties.
				if ( 'alt' !== $name ) {
					continue;
				}
				$value = '';
			}
			$attr_value = ( 'src' === $name ) ? esc_url( $value ) : esc_attr( (string) $value );
			$parts[]    = sanitize_key( $name ) . '="' . $attr_value . '"';
		}
		$img_markup = '<img ' . implode( ' ', $parts ) . '>';
	} else {
		$img_markup = qp_holdings_theme_picture( $relative_path, $img_args, $sizes );
	}

	if ( ! qp_holdings_can_edit_hero() ) {
		return $img_markup;
	}

	$default_url = qp_holdings_normalize_theme_asset_url( $relative_path );
	$attr_string = qp_holdings_hero_attr_string( $key, $default_url, $is_override );

	return '<span class="qp-hero-slot"' . $attr_string . '>' . $img_markup . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inner markup is built escaped above.
}

/**
 * Resolve the background-image URL for a CSS-background hero (legacy pages):
 * the override when set, otherwise the absolute URL of the default asset.
 *
 * @param string $key           Hero key.
 * @param string $relative_path Theme-relative default image path.
 * @return string
 */
function qp_holdings_hero_bg_url( string $key, string $relative_path ): string {
	$override_url = qp_holdings_hero_override_url( $key );
	if ( '' !== $override_url ) {
		return $override_url;
	}
	return qp_holdings_normalize_theme_asset_url( $relative_path );
}

/**
 * Echo the editor-only data attributes for a CSS-background hero element
 * (e.g. a `.page-header` whose hero is an inline `background-image`).
 *
 * @param string $key           Hero key.
 * @param string $relative_path Theme-relative default image path.
 * @return void
 */
function qp_holdings_hero_bg_attrs( string $key, string $relative_path ): void {
	if ( ! qp_holdings_can_edit_hero() ) {
		return;
	}
	$default_url = qp_holdings_normalize_theme_asset_url( $relative_path );
	$is_override = ( '' !== qp_holdings_hero_override_url( $key ) );
	echo ' data-qp-hero-bg="1"' . qp_holdings_hero_attr_string( $key, $default_url, $is_override ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attrs are esc_attr'd individually.
}

/**
 * Enqueue editor assets (frontend, editors only): the Media Library, plus the
 * hero-editor JS/CSS and its localized config.
 *
 * @return void
 */
function qp_holdings_inline_hero_enqueue(): void {
	if ( is_admin() || ! qp_holdings_can_edit_hero() ) {
		return;
	}

	wp_enqueue_media();

	$css_rel = 'assets/css/inline-hero.css';
	$js_rel  = 'assets/js/inline-hero.js';
	$css_abs = get_template_directory() . '/' . $css_rel;
	$js_abs  = get_template_directory() . '/' . $js_rel;

	if ( file_exists( $css_abs ) ) {
		wp_enqueue_style(
			'qp-inline-hero',
			get_template_directory_uri() . '/' . $css_rel,
			array(),
			(string) filemtime( $css_abs )
		);
	}

	if ( file_exists( $js_abs ) ) {
		wp_enqueue_script(
			'qp-inline-hero',
			get_template_directory_uri() . '/' . $js_rel,
			array( 'jquery' ),
			(string) filemtime( $js_abs ),
			true
		);

		$lang = function_exists( 'qp_holdings_current_language' ) ? qp_holdings_current_language() : 'vi';
		wp_localize_script(
			'qp-inline-hero',
			'qpInlineHero',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'qp_inline_hero' ),
				'action'  => 'qp_save_inline_hero',
				'i18n'    => array(
					'change'    => qp_holdings_inline_hero_ui_text( 'change', $lang ),
					'reset'     => qp_holdings_inline_hero_ui_text( 'reset', $lang ),
					'title'     => qp_holdings_inline_hero_ui_text( 'title', $lang ),
					'button'    => qp_holdings_inline_hero_ui_text( 'button', $lang ),
					'saved'     => qp_holdings_inline_hero_ui_text( 'saved', $lang ),
					'reseted'   => qp_holdings_inline_hero_ui_text( 'reseted', $lang ),
					'saveError' => qp_holdings_inline_hero_ui_text( 'save_error', $lang ),
					'expired'   => qp_holdings_inline_hero_ui_text( 'expired', $lang ),
					'confirm'   => qp_holdings_inline_hero_ui_text( 'confirm', $lang ),
				),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'qp_holdings_inline_hero_enqueue', 20 );

/**
 * Small UI strings for the hero-editor chrome, localized vi/en/zh.
 *
 * @param string $slug UI string slug.
 * @param string $lang Language code.
 * @return string
 */
function qp_holdings_inline_hero_ui_text( string $slug, string $lang ): string {
	$strings = array(
		'change'     => array(
			'vi' => 'Đổi ảnh',
			'en' => 'Change image',
			'zh' => '更换图片',
		),
		'reset'      => array(
			'vi' => 'Khôi phục',
			'en' => 'Reset',
			'zh' => '恢复默认',
		),
		'title'      => array(
			'vi' => 'Chọn ảnh banner',
			'en' => 'Choose banner image',
			'zh' => '选择横幅图片',
		),
		'button'     => array(
			'vi' => 'Dùng ảnh này',
			'en' => 'Use this image',
			'zh' => '使用此图片',
		),
		'saved'      => array(
			'vi' => 'Đã cập nhật ảnh banner',
			'en' => 'Banner image updated',
			'zh' => '横幅图片已更新',
		),
		'reseted'    => array(
			'vi' => 'Đã khôi phục ảnh mặc định',
			'en' => 'Restored default image',
			'zh' => '已恢复默认图片',
		),
		'save_error' => array(
			'vi' => 'Cập nhật thất bại',
			'en' => 'Update failed',
			'zh' => '更新失败',
		),
		'expired'    => array(
			'vi' => 'Phiên hết hạn, vui lòng tải lại trang',
			'en' => 'Session expired, please reload the page',
			'zh' => '会话已过期，请重新加载页面',
		),
		'confirm'    => array(
			'vi' => 'Khôi phục ảnh banner về mặc định?',
			'en' => 'Restore this banner to the default image?',
			'zh' => '将此横幅恢复为默认图片？',
		),
	);

	return $strings[ $slug ][ $lang ] ?? $strings[ $slug ]['vi'] ?? '';
}

/**
 * AJAX handler: save (set or reset) a single hero-image override.
 *
 * @return void
 */
function qp_holdings_handle_save_inline_hero(): void {
	if ( ! qp_holdings_can_edit_hero() ) {
		wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
	}

	check_ajax_referer( 'qp_inline_hero', 'nonce' );

	$registry = qp_holdings_hero_registry();
	$key      = isset( $_POST['key'] ) ? sanitize_key( wp_unslash( $_POST['key'] ) ) : '';
	if ( '' === $key || ! isset( $registry[ $key ] ) ) {
		wp_send_json_error( array( 'message' => 'bad_key' ), 400 );
	}

	$attachment_id = isset( $_POST['attachment_id'] ) ? absint( wp_unslash( $_POST['attachment_id'] ) ) : 0;
	$reset         = ! empty( $_POST['reset'] ) || 0 === $attachment_id;

	$overrides = qp_holdings_hero_overrides();

	if ( $reset ) {
		unset( $overrides[ $key ] );
		$effective_url = qp_holdings_normalize_theme_asset_url( $registry[ $key ]['default'] );
		$is_override   = false;
	} else {
		if ( 'attachment' !== get_post_type( $attachment_id ) || ! wp_attachment_is_image( $attachment_id ) ) {
			wp_send_json_error( array( 'message' => 'bad_attachment' ), 400 );
		}
		$overrides[ $key ] = $attachment_id;
		$url               = wp_get_attachment_image_url( $attachment_id, 'full' );
		$effective_url     = $url ? $url : '';
		$is_override       = true;
	}

	if ( empty( $overrides ) ) {
		delete_option( 'qp_holdings_hero_overrides' );
	} else {
		update_option( 'qp_holdings_hero_overrides', $overrides, true );
	}

	wp_send_json_success(
		array(
			'url'        => $effective_url,
			'isOverride' => $is_override,
		)
	);
}
add_action( 'wp_ajax_qp_save_inline_hero', 'qp_holdings_handle_save_inline_hero' );

// Admin management screen ("Ảnh banner").
$qp_holdings_inline_hero_admin = get_template_directory() . '/inc/inline-hero-admin.php';
if ( is_admin() && file_exists( $qp_holdings_inline_hero_admin ) ) {
	require_once $qp_holdings_inline_hero_admin;
}
