<?php
/**
 * Ảnh đại diện mặc định (yêu cầu #3, 11/2026).
 *
 * Settings → Media: the admin picks one image from the Media Library. On the
 * public site, any news / post / project without a featured image shows that
 * image instead (EN / ZH copies too, old posts too). Nothing is written into
 * the posts themselves: as soon as an editor sets a real featured image it is
 * used, and changing the default later updates every post at once.
 *
 * The fallback is applied only while rendering front-end pages, never in
 * wp-admin, cron or the REST API — otherwise save routines that copy the
 * featured image (news card image sync, translation sync) would store the
 * default image as if the editor had chosen it.
 */

defined( 'ABSPATH' ) || exit;

const QP_HOLDINGS_DEFAULT_THUMB_OPTION = 'qp_default_thumbnail_id';

/** Post types that get the default image. */
function qp_holdings_default_thumbnail_post_types(): array {
	return (array) apply_filters( 'qp_holdings_default_thumbnail_post_types', array( 'qp_news', 'post' ) );
}

/** Attachment ID of the default image, or 0. */
function qp_holdings_default_thumbnail_id(): int {
	$id = (int) get_option( QP_HOLDINGS_DEFAULT_THUMB_OPTION, 0 );
	return ( $id && wp_attachment_is_image( $id ) ) ? $id : 0;
}

/** True only while a front-end page is being rendered. */
function qp_holdings_default_thumbnail_active(): bool {
	if ( is_admin() || wp_doing_cron() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}
	return (bool) did_action( 'template_redirect' );
}

/**
 * get_post_thumbnail_id() / has_post_thumbnail() / the_post_thumbnail() all
 * go through this filter (WordPress 5.9+).
 *
 * @param int|false        $thumbnail_id
 * @param int|WP_Post|null $post
 * @return int|false
 */
function qp_holdings_default_thumbnail_filter( $thumbnail_id, $post ) {
	if ( $thumbnail_id || ! qp_holdings_default_thumbnail_active() ) {
		return $thumbnail_id;
	}
	$post = get_post( $post );
	if ( ! $post || ! in_array( $post->post_type, qp_holdings_default_thumbnail_post_types(), true ) ) {
		return $thumbnail_id;
	}
	$default = qp_holdings_default_thumbnail_id();
	return $default ? $default : $thumbnail_id;
}
add_filter( 'post_thumbnail_id', 'qp_holdings_default_thumbnail_filter', 10, 2 );

/* -------------------------------------------------------------------------
 * Settings → Media (Cài đặt → Phương tiện)
 * ---------------------------------------------------------------------- */

function qp_holdings_default_thumbnail_register_setting(): void {
	register_setting(
		'media',
		QP_HOLDINGS_DEFAULT_THUMB_OPTION,
		array(
			'type'              => 'integer',
			'sanitize_callback' => static function ( $value ): int {
				$id = absint( $value );
				return ( $id && wp_attachment_is_image( $id ) ) ? $id : 0;
			},
			'default'           => 0,
		)
	);
	add_settings_field(
		'qp_default_thumbnail',
		'Ảnh đại diện mặc định',
		'qp_holdings_default_thumbnail_field',
		'media',
		'default'
	);
}
add_action( 'admin_init', 'qp_holdings_default_thumbnail_register_setting' );

function qp_holdings_default_thumbnail_field(): void {
	$id      = qp_holdings_default_thumbnail_id();
	$preview = $id ? wp_get_attachment_image( $id, 'medium', false, array( 'style' => 'max-width:300px;height:auto;display:block;border:1px solid #dcdcde;border-radius:4px' ) ) : '';
	$types   = array();
	foreach ( qp_holdings_default_thumbnail_post_types() as $type ) {
		$obj     = get_post_type_object( $type );
		$types[] = $obj ? $obj->labels->name : $type;
	}
	?>
	<div id="qp-default-thumb">
		<input type="hidden" id="qp-default-thumb-id" name="<?php echo esc_attr( QP_HOLDINGS_DEFAULT_THUMB_OPTION ); ?>" value="<?php echo esc_attr( (string) $id ); ?>">
		<div id="qp-default-thumb-preview" style="margin-bottom:8px"><?php echo $preview; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		<button type="button" class="button" id="qp-default-thumb-pick"><?php echo $id ? 'Đổi ảnh' : 'Chọn ảnh'; ?></button>
		<button type="button" class="button-link button-link-delete" id="qp-default-thumb-clear" style="margin-left:8px<?php echo $id ? '' : ';display:none'; ?>">Bỏ ảnh mặc định</button>
		<p class="description">Bài viết chưa đặt ảnh đại diện sẽ hiển thị ảnh này ngoài website (cả bản tiếng Anh, tiếng Trung và bài cũ). Áp dụng cho: <?php echo esc_html( implode( ', ', $types ) ); ?>. Bài đã có ảnh đại diện riêng không bị ảnh hưởng. Nhớ bấm “Lưu thay đổi”.</p>
	</div>
	<?php
}

function qp_holdings_default_thumbnail_admin_assets( string $hook ): void {
	if ( 'options-media.php' !== $hook ) {
		return;
	}
	wp_enqueue_media();
	wp_add_inline_script(
		'media-editor',
		<<<'JS'
jQuery(function ($) {
	var frame;
	$('#qp-default-thumb-pick').on('click', function (e) {
		e.preventDefault();
		if (!frame) {
			frame = wp.media({ title: 'Chọn ảnh đại diện mặc định', button: { text: 'Dùng ảnh này' }, library: { type: 'image' }, multiple: false });
			frame.on('select', function () {
				var a = frame.state().get('selection').first().toJSON();
				var src = (a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url);
				$('#qp-default-thumb-id').val(a.id);
				$('#qp-default-thumb-preview').html($('<img>', { src: src, alt: '', css: { maxWidth: '300px', height: 'auto', display: 'block', border: '1px solid #dcdcde', borderRadius: '4px' } }));
				$('#qp-default-thumb-pick').text('Đổi ảnh');
				$('#qp-default-thumb-clear').show();
			});
		}
		frame.open();
	});
	$('#qp-default-thumb-clear').on('click', function (e) {
		e.preventDefault();
		$('#qp-default-thumb-id').val('0');
		$('#qp-default-thumb-preview').empty();
		$('#qp-default-thumb-pick').text('Chọn ảnh');
		$(this).hide();
	});
});
JS
	);
}
add_action( 'admin_enqueue_scripts', 'qp_holdings_default_thumbnail_admin_assets' );

/**
 * Hint in the "Ảnh đại diện" box of the edit screen when none is set.
 */
function qp_holdings_default_thumbnail_admin_hint( string $content, int $post_id, $thumbnail_id ): string {
	if ( $thumbnail_id ) {
		return $content;
	}
	$post = get_post( $post_id );
	if ( ! $post || ! in_array( $post->post_type, qp_holdings_default_thumbnail_post_types(), true ) ) {
		return $content;
	}
	$default = qp_holdings_default_thumbnail_id();
	if ( ! $default ) {
		return $content;
	}
	return $content . '<p class="description" style="margin-top:8px">Chưa chọn ảnh: ngoài website sẽ hiển thị <a href="' . esc_url( admin_url( 'options-media.php' ) ) . '">ảnh đại diện mặc định</a>.</p>';
}
add_filter( 'admin_post_thumbnail_html', 'qp_holdings_default_thumbnail_admin_hint', 10, 3 );
