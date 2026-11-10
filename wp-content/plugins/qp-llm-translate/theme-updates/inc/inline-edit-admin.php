<?php
/**
 * Admin screen: "Nội dung tĩnh" — view/edit/reset inline-text overrides.
 *
 * Lists currently-overridden keys across vi/en/zh, lets editors change or
 * reset them, search the full dictionary to pre-seed a not-yet-overridden
 * string, and export all overrides as JSON for backup.
 *
 * @package QP_Holdings
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the admin page under Appearance (Giao diện).
 *
 * @return void
 */
function qp_holdings_inline_edit_admin_menu(): void {
	add_theme_page(
		__( 'Nội dung tĩnh', 'qp-holdings' ),
		__( 'Nội dung tĩnh', 'qp-holdings' ),
		qp_holdings_inline_edit_cap(),
		'qp-static-content',
		'qp_holdings_inline_edit_admin_render'
	);
}
add_action( 'admin_menu', 'qp_holdings_inline_edit_admin_menu' );

/**
 * Handle the admin-side save/reset/export POST actions.
 *
 * @return void
 */
function qp_holdings_inline_edit_admin_handle(): void {
	if ( empty( $_POST['qp_static_action'] ) ) {
		return;
	}
	if ( ! current_user_can( qp_holdings_inline_edit_cap() ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'qp-holdings' ) );
	}
	check_admin_referer( 'qp_static_content' );

	$action    = sanitize_key( wp_unslash( $_POST['qp_static_action'] ) );
	$languages = qp_holdings_supported_languages();

	if ( 'save' === $action ) {
		$key_b64 = isset( $_POST['key'] ) ? wp_unslash( $_POST['key'] ) : '';
		$key     = is_string( $key_b64 ) ? base64_decode( $key_b64, true ) : false;
		if ( false !== $key && '' !== $key ) {
			$is_html   = ! empty( $_POST['is_html'] );
			$multiline = ! empty( $_POST['multiline'] );
			foreach ( array_keys( $languages ) as $lang ) {
				if ( ! isset( $_POST['value'][ $lang ] ) ) {
					continue;
				}
				$raw   = wp_unslash( $_POST['value'][ $lang ] );
				$raw   = is_string( $raw ) ? $raw : '';
				$value = qp_holdings_inline_edit_sanitize_value( $raw, $is_html, $multiline );
				qp_holdings_inline_edit_admin_store( $key, $lang, $value, $is_html );
			}
			qp_holdings_inline_edit_touch_meta();
			qp_holdings_inline_edit_admin_redirect( 'saved' );
		}
	}

	if ( 'reset_row' === $action ) {
		$key_b64 = isset( $_POST['key'] ) ? wp_unslash( $_POST['key'] ) : '';
		$key     = is_string( $key_b64 ) ? base64_decode( $key_b64, true ) : false;
		if ( false !== $key && '' !== $key ) {
			foreach ( array_keys( $languages ) as $lang ) {
				qp_holdings_inline_edit_admin_store( $key, $lang, '', false ); // empty = delete
			}
			qp_holdings_inline_edit_touch_meta();
			qp_holdings_inline_edit_admin_redirect( 'reset' );
		}
	}
}
add_action( 'admin_init', 'qp_holdings_inline_edit_admin_handle' );

/**
 * Store (or delete, on empty/default) one override and prune empty options.
 *
 * @param string $key     Translation key.
 * @param string $lang    Language code.
 * @param string $value   Sanitized value (empty deletes).
 * @param bool   $is_html Whether the key carries inline HTML.
 * @return void
 */
function qp_holdings_inline_edit_admin_store( string $key, string $lang, string $value, bool $is_html ): void {
	$option_name = 'qp_holdings_text_overrides_' . $lang;
	$overrides   = (array) get_option( $option_name, array() );
	$default     = qp_holdings_t_default( $key, $lang );

	$reset = ( '' === $value )
		|| ( ! $is_html && trim( $value ) === trim( $default ) )
		|| ( $is_html && $value === $default );

	if ( $reset ) {
		unset( $overrides[ $key ] );
	} else {
		$overrides[ $key ] = $value;
	}

	if ( empty( $overrides ) ) {
		delete_option( $option_name );
	} else {
		update_option( $option_name, $overrides, true );
	}
}

/**
 * Redirect back to the admin page with a status flag.
 *
 * @param string $status Status slug.
 * @return void
 */
function qp_holdings_inline_edit_admin_redirect( string $status ): void {
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'      => 'qp-static-content',
				'qp_status' => $status,
			),
			admin_url( 'themes.php' )
		)
	);
	exit;
}

/**
 * Export all overrides as a JSON download.
 *
 * @return void
 */
function qp_holdings_inline_edit_admin_export(): void {
	if ( empty( $_GET['qp_export'] ) || 'json' !== $_GET['qp_export'] ) {
		return;
	}
	if ( ! current_user_can( qp_holdings_inline_edit_cap() ) ) {
		wp_die( esc_html__( 'Bạn không có quyền tải xuống.', 'qp-holdings' ) );
	}
	check_admin_referer( 'qp_static_export' );

	$languages = qp_holdings_supported_languages();
	$payload   = array(
		'meta'      => (array) get_option( 'qp_holdings_text_overrides_meta', array() ),
		'overrides' => array(),
	);
	foreach ( array_keys( $languages ) as $lang ) {
		$payload['overrides'][ $lang ] = (array) get_option( 'qp_holdings_text_overrides_' . $lang, array() );
	}

	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="qp-static-content-' . gmdate( 'Ymd-His' ) . '.json"' );
	echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
	exit;
}
add_action( 'admin_init', 'qp_holdings_inline_edit_admin_export' );

/**
 * Render the admin page.
 *
 * @return void
 */
function qp_holdings_inline_edit_admin_render(): void {
	if ( ! current_user_can( qp_holdings_inline_edit_cap() ) ) {
		return;
	}

	$languages = qp_holdings_supported_languages();
	$lang_keys = array_keys( $languages );

	// Collect the union of all overridden keys across languages.
	$overrides_by_lang = array();
	$all_keys          = array();
	foreach ( $lang_keys as $lang ) {
		$over                      = (array) get_option( 'qp_holdings_text_overrides_' . $lang, array() );
		$overrides_by_lang[ $lang ] = $over;
		$all_keys                   = array_merge( $all_keys, array_keys( $over ) );
	}
	$all_keys = array_values( array_unique( $all_keys ) );
	sort( $all_keys );

	// Optional dictionary search to pre-seed a not-yet-overridden key.
	$search       = isset( $_GET['qp_search'] ) ? sanitize_text_field( wp_unslash( $_GET['qp_search'] ) ) : '';
	$search_hits  = array();
	if ( '' !== $search ) {
		$dict   = qp_holdings_translation_dict();
		$needle = function_exists( 'mb_strtolower' ) ? mb_strtolower( $search ) : strtolower( $search );
		foreach ( $dict as $dkey => $vals ) {
			$hay = $dkey . ' ' . implode( ' ', array_map( 'strval', $vals ) );
			$hay = function_exists( 'mb_strtolower' ) ? mb_strtolower( $hay ) : strtolower( $hay );
			if ( false !== strpos( $hay, $needle ) ) {
				$search_hits[ $dkey ] = $vals;
			}
			if ( count( $search_hits ) >= 50 ) {
				break;
			}
		}
	}

	$status = isset( $_GET['qp_status'] ) ? sanitize_key( wp_unslash( $_GET['qp_status'] ) ) : '';
	?>
	<div class="wrap qp-static-admin">
		<h1><?php esc_html_e( 'Nội dung tĩnh', 'qp-holdings' ); ?></h1>
		<p class="description">
			<?php esc_html_e( 'Quản lý các đoạn văn bản tĩnh đã chỉnh sửa (tiêu đề, đoạn văn, tiểu đề). Bạn cũng có thể nhấp đúp trực tiếp trên trang để sửa.', 'qp-holdings' ); ?>
		</p>

		<?php if ( 'saved' === $status ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Đã lưu thay đổi.', 'qp-holdings' ); ?></p></div>
		<?php elseif ( 'reset' === $status ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Đã khôi phục về mặc định.', 'qp-holdings' ); ?></p></div>
		<?php endif; ?>

		<p>
			<a class="button"
				href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'qp-static-content', 'qp_export' => 'json' ), admin_url( 'themes.php' ) ), 'qp_static_export' ) ); ?>">
				<?php esc_html_e( 'Tải JSON sao lưu', 'qp-holdings' ); ?>
			</a>
		</p>

		<h2><?php esc_html_e( 'Nội dung đã chỉnh sửa', 'qp-holdings' ); ?></h2>
		<?php if ( empty( $all_keys ) ) : ?>
			<p><em><?php esc_html_e( 'Chưa có nội dung nào được chỉnh sửa. Dùng ô tìm kiếm bên dưới để tìm và chỉnh sửa một chuỗi.', 'qp-holdings' ); ?></em></p>
		<?php else : ?>
			<?php
			foreach ( $all_keys as $key ) {
				qp_holdings_inline_edit_admin_row( $key, $lang_keys, $languages, $overrides_by_lang );
			}
			?>
		<?php endif; ?>

		<hr>

		<h2><?php esc_html_e( 'Tìm chuỗi để chỉnh sửa', 'qp-holdings' ); ?></h2>
		<form method="get" action="<?php echo esc_url( admin_url( 'themes.php' ) ); ?>">
			<input type="hidden" name="page" value="qp-static-content">
			<p>
				<input type="search" name="qp_search" value="<?php echo esc_attr( $search ); ?>"
					class="regular-text" placeholder="<?php esc_attr_e( 'Nhập từ khóa (vi/en/zh hoặc khóa)…', 'qp-holdings' ); ?>">
				<button type="submit" class="button"><?php esc_html_e( 'Tìm', 'qp-holdings' ); ?></button>
			</p>
		</form>

		<?php if ( '' !== $search ) : ?>
			<?php if ( empty( $search_hits ) ) : ?>
				<p><em><?php esc_html_e( 'Không tìm thấy chuỗi phù hợp.', 'qp-holdings' ); ?></em></p>
			<?php else : ?>
				<p class="description"><?php printf( esc_html__( 'Tìm thấy %d kết quả (tối đa 50). Chỉnh sửa và lưu để tạo bản ghi đè.', 'qp-holdings' ), count( $search_hits ) ); ?></p>
				<?php
				foreach ( $search_hits as $key => $vals ) {
					if ( in_array( $key, $all_keys, true ) ) {
						continue; // already shown above
					}
					qp_holdings_inline_edit_admin_row( $key, $lang_keys, $languages, $overrides_by_lang );
				}
				?>
			<?php endif; ?>
		<?php endif; ?>
	</div>

	<style>
		.qp-static-admin .qp-static-card{background:#fff;border:1px solid #ccd0d4;border-radius:6px;padding:14px 16px;margin:0 0 14px;max-width:1100px}
		.qp-static-admin .qp-static-card__key{font-family:monospace;font-size:12px;color:#50575e;word-break:break-word;margin:0 0 10px}
		.qp-static-admin .qp-static-card__badge{display:inline-block;margin-left:8px;padding:1px 7px;border-radius:999px;background:#d4af37;color:#0b1f3a;font-family:-apple-system,sans-serif;font-size:11px;font-weight:700;vertical-align:middle}
		.qp-static-admin .qp-static-field{display:flex;gap:10px;align-items:flex-start;margin:0 0 8px}
		.qp-static-admin .qp-static-field__lang{flex:0 0 56px;font-weight:600;padding-top:6px}
		.qp-static-admin .qp-static-field textarea{flex:1;min-height:38px;font-size:13px}
		.qp-static-admin .qp-static-card__actions{margin-top:6px;display:flex;gap:8px;align-items:center}
	</style>
	<?php
}

/**
 * Render one editable card for a key (all languages).
 *
 * @param string $key               Translation key.
 * @param array  $lang_keys         Ordered language codes.
 * @param array  $languages         Language meta map.
 * @param array  $overrides_by_lang Per-language override maps.
 * @return void
 */
function qp_holdings_inline_edit_admin_row( string $key, array $lang_keys, array $languages, array $overrides_by_lang ): void {
	$is_html   = ( '_html' === substr( $key, -5 ) );
	$is_overri = false;
	foreach ( $lang_keys as $lang ) {
		if ( isset( $overrides_by_lang[ $lang ][ $key ] ) ) {
			$is_overri = true;
			break;
		}
	}
	?>
	<div class="qp-static-card">
		<form method="post" action="<?php echo esc_url( admin_url( 'themes.php?page=qp-static-content' ) ); ?>">
			<?php wp_nonce_field( 'qp_static_content' ); ?>
			<input type="hidden" name="qp_static_action" value="save">
			<input type="hidden" name="key" value="<?php echo esc_attr( base64_encode( $key ) ); ?>">
			<?php if ( $is_html ) : ?>
				<input type="hidden" name="is_html" value="1">
			<?php endif; ?>
			<input type="hidden" name="multiline" value="1">

			<p class="qp-static-card__key">
				<?php echo esc_html( $key ); ?>
				<?php if ( $is_html ) : ?><span class="qp-static-card__badge">HTML</span><?php endif; ?>
				<?php if ( $is_overri ) : ?><span class="qp-static-card__badge"><?php esc_html_e( 'Đã sửa', 'qp-holdings' ); ?></span><?php endif; ?>
			</p>

			<?php foreach ( $lang_keys as $lang ) : ?>
				<?php
				$current = $overrides_by_lang[ $lang ][ $key ] ?? qp_holdings_t_default( $key, $lang );
				$short   = $languages[ $lang ]['short'] ?? strtoupper( $lang );
				?>
				<div class="qp-static-field">
					<label class="qp-static-field__lang" for="qp-f-<?php echo esc_attr( $lang . '-' . md5( $key ) ); ?>"><?php echo esc_html( $short ); ?></label>
					<textarea id="qp-f-<?php echo esc_attr( $lang . '-' . md5( $key ) ); ?>"
						name="value[<?php echo esc_attr( $lang ); ?>]" rows="2"><?php echo esc_textarea( $current ); ?></textarea>
				</div>
			<?php endforeach; ?>

			<div class="qp-static-card__actions">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Lưu', 'qp-holdings' ); ?></button>
			</div>
		</form>

		<?php if ( $is_overri ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'themes.php?page=qp-static-content' ) ); ?>" style="margin-top:6px"
				onsubmit="return confirm('<?php echo esc_js( __( 'Khôi phục chuỗi này về mặc định cho tất cả ngôn ngữ?', 'qp-holdings' ) ); ?>');">
				<?php wp_nonce_field( 'qp_static_content' ); ?>
				<input type="hidden" name="qp_static_action" value="reset_row">
				<input type="hidden" name="key" value="<?php echo esc_attr( base64_encode( $key ) ); ?>">
				<button type="submit" class="button button-link-delete"><?php esc_html_e( 'Khôi phục mặc định', 'qp-holdings' ); ?></button>
			</form>
		<?php endif; ?>
	</div>
	<?php
}
