<?php
/**
 * Custom code injection (head / body-open / footer / body-close).
 *
 * Provides an Administrator-only settings page (Settings → Mã tùy chỉnh) that
 * lets admins paste raw HTML/script into four site-wide slots for SEO tags,
 * Google Analytics / Tag Manager, verification meta, etc.
 *
 * Because admins legitimately need real <script> tags, the input is NOT run
 * through wp_kses (which strips scripts). Instead a structural validator
 * (mask-then-stack tokenizer) rejects fragments that would break the page
 * (unbalanced tags, unterminated script/style/comment, stray document tags)
 * on save, reverting to the previously stored value. Output is gated by both
 * an admin "enabled" checkbox and the stored "valid" flag.
 *
 * @package QP_Holdings
 */

defined( 'ABSPATH' ) || exit;

/** Single option key holding the whole config array. */
const QP_HOLDINGS_CUSTOM_CODE_OPTION = 'qp_holdings_custom_code';

/** One-time seed flag (migrates the previously hard-coded GTM block). */
const QP_HOLDINGS_CUSTOM_CODE_SEED_FLAG = 'qp_holdings_custom_code_seed_v1';

/**
 * Slot definitions: key => human label (Vietnamese).
 *
 * @return array<string,string>
 */
function qp_holdings_custom_code_slots(): array {
	return array(
		'head'       => 'Trong thẻ <head>',
		'body_open'  => 'Ngay sau thẻ mở <body>',
		'footer'     => 'Vùng footer (sau nội dung chân trang)',
		'body_close' => 'Cuối trang (trước thẻ đóng </body>)',
	);
}

/**
 * Read the full config, normalised to a list of snippets per slot.
 *
 * Each slot maps to a 0-indexed list of snippets, every snippet having
 * label/content/enabled/valid. Transparently migrates the legacy single-entry
 * format (one {content,enabled,valid} per slot) into a one-snippet list.
 *
 * @return array<string,array<int,array{label:string,content:string,enabled:bool,valid:bool}>>
 */
function qp_holdings_custom_code_get(): array {
	$raw = get_option( QP_HOLDINGS_CUSTOM_CODE_OPTION, array() );
	$raw = is_array( $raw ) ? $raw : array();
	$out = array();

	foreach ( array_keys( qp_holdings_custom_code_slots() ) as $slot ) {
		$out[ $slot ] = array();
		$slot_data    = isset( $raw[ $slot ] ) && is_array( $raw[ $slot ] ) ? $raw[ $slot ] : array();

		// Legacy single-entry format: slot held one assoc array with 'content'.
		if ( isset( $slot_data['content'] ) || isset( $slot_data['enabled'] ) || isset( $slot_data['valid'] ) ) {
			$content = isset( $slot_data['content'] ) ? (string) $slot_data['content'] : '';
			if ( '' !== trim( $content ) ) {
				$out[ $slot ][] = array(
					'label'   => 'Mã đã lưu',
					'content' => $content,
					'enabled' => ! empty( $slot_data['enabled'] ),
					'valid'   => ! empty( $slot_data['valid'] ),
				);
			}
			continue;
		}

		// New format: a list of snippet arrays.
		foreach ( $slot_data as $snippet ) {
			if ( ! is_array( $snippet ) ) {
				continue;
			}
			$out[ $slot ][] = array(
				'label'   => isset( $snippet['label'] ) ? (string) $snippet['label'] : '',
				'content' => isset( $snippet['content'] ) ? (string) $snippet['content'] : '',
				'enabled' => ! empty( $snippet['enabled'] ),
				'valid'   => ! empty( $snippet['valid'] ),
			);
		}
	}

	return $out;
}

/**
 * Structural validator for a pasted HTML/script fragment.
 *
 * Does NOT sanitize or validate JS/CSS syntax — it only catches markup that
 * would break the document tree. Permissive by design: blocks the clear
 * hazards, warns (does not fail) on ambiguous patterns.
 *
 * @param string $html Raw fragment.
 * @return array{valid:bool,error:string}
 */
function qp_holdings_validate_custom_code( string $html ): array {
	$html = (string) $html;

	if ( '' === trim( $html ) ) {
		return array(
			'valid' => true,
			'error' => '',
		);
	}

	// Rule 1: document-structure tags must never appear in an injected fragment.
	if ( preg_match( '/<\s*\/?\s*(html|head|body)\b/i', $html )
		|| preg_match( '/<!doctype\b/i', $html ) ) {
		return array(
			'valid' => false,
			'error' => 'Không được chứa thẻ cấu trúc tài liệu (<html>, <head>, <body>, <!doctype>) vì chúng sẽ phá vỡ cấu trúc trang.',
		);
	}

	// --- Pass A: raw-text & comment termination, then mask those regions. ---
	$masked = $html;

	// HTML comments.
	$open_comments  = preg_match_all( '/<!--/', $masked );
	$close_comments = preg_match_all( '/-->/', $masked );
	if ( $open_comments !== $close_comments ) {
		return array(
			'valid' => false,
			'error' => 'Có chú thích HTML (<!-- -->) chưa được đóng đúng cách.',
		);
	}
	$masked = preg_replace( '/<!--.*?-->/s', '', $masked );

	// <script> ... </script>
	$script_check = qp_holdings_custom_code_mask_rawtext( $masked, 'script' );
	if ( ! $script_check['ok'] ) {
		return array(
			'valid' => false,
			'error' => 'Có thẻ <script> chưa được đóng (thiếu </script>).',
		);
	}
	$masked = $script_check['masked'];

	// <style> ... </style>
	$style_check = qp_holdings_custom_code_mask_rawtext( $masked, 'style' );
	if ( ! $style_check['ok'] ) {
		return array(
			'valid' => false,
			'error' => 'Có thẻ <style> chưa được đóng (thiếu </style>).',
		);
	}
	$masked = $style_check['masked'];

	// --- Pass B: container-tag balance on the masked text. ---
	$balance = qp_holdings_custom_code_check_balance( $masked );
	if ( ! $balance['ok'] ) {
		return array(
			'valid' => false,
			'error' => $balance['error'],
		);
	}

	return array(
		'valid' => true,
		'error' => '',
	);
}

/**
 * Mask every `<tag> ... </tag>` raw-text region (script/style) with a
 * placeholder, ensuring each opener has a matching closer.
 *
 * @param string $html Input.
 * @param string $tag  Tag name (script|style).
 * @return array{ok:bool,masked:string}
 */
function qp_holdings_custom_code_mask_rawtext( string $html, string $tag ): array {
	$lower    = strtolower( $html );
	$open_re  = '<' . $tag;
	$close_re = '</' . $tag;
	$offset   = 0;
	$out      = '';

	while ( true ) {
		$open_pos = strpos( $lower, $open_re, $offset );
		if ( false === $open_pos ) {
			$out .= substr( $html, $offset );
			break;
		}

		// Confirm it is a real tag start (next char is whitespace, > or /).
		$after = $lower[ $open_pos + strlen( $open_re ) ] ?? '';
		if ( '' !== $after && ! preg_match( '/[\s\/>]/', $after ) ) {
			// e.g. <scripts-of-mine> — not our tag; copy one char and continue.
			$out   .= substr( $html, $offset, $open_pos - $offset + strlen( $open_re ) );
			$offset = $open_pos + strlen( $open_re );
			continue;
		}

		$close_pos = strpos( $lower, $close_re, $open_pos );
		if ( false === $close_pos ) {
			return array(
				'ok'     => false,
				'masked' => $html,
			);
		}

		// Advance past the closing `>` of the close tag.
		$gt = strpos( $lower, '>', $close_pos );
		if ( false === $gt ) {
			return array(
				'ok'     => false,
				'masked' => $html,
			);
		}

		$out   .= substr( $html, $offset, $open_pos - $offset );
		$out   .= '<' . $tag . '></' . $tag . '>'; // balanced placeholder.
		$offset = $gt + 1;
	}

	return array(
		'ok'     => true,
		'masked' => $out,
	);
}

/** Void elements that never need a closing tag. */
function qp_holdings_custom_code_void_elements(): array {
	return array(
		'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input',
		'link', 'meta', 'param', 'source', 'track', 'wbr',
	);
}

/**
 * Stack-based container-tag balance check on masked (script/style/comment-free)
 * text. Quoted attribute values are stripped first so `>` inside an attribute
 * (e.g. data-x="a > b") does not fool the tag scanner.
 *
 * @param string $html Masked fragment.
 * @return array{ok:bool,error:string}
 */
function qp_holdings_custom_code_check_balance( string $html ): array {
	$void  = qp_holdings_custom_code_void_elements();
	$stack = array();

	// Match each tag, capturing optional leading slash, the name, and whether
	// it self-closes. Attribute values are matched loosely; we only care about
	// tag name + slashes here.
	if ( ! preg_match_all( '/<(\/?)\s*([a-zA-Z][a-zA-Z0-9:-]*)([^<>]*?)(\/?)>/s', $html, $matches, PREG_SET_ORDER ) ) {
		return array(
			'ok'    => true,
			'error' => '',
		);
	}

	foreach ( $matches as $m ) {
		$is_close = '/' === $m[1];
		$name     = strtolower( $m[2] );
		$self     = '/' === $m[4];

		if ( in_array( $name, $void, true ) || $self ) {
			continue;
		}

		if ( ! $is_close ) {
			$stack[] = $name;
			continue;
		}

		// Closing tag: must match the top of the stack.
		if ( empty( $stack ) ) {
			return array(
				'ok'    => false,
				'error' => sprintf( 'Thẻ đóng </%s> không có thẻ mở tương ứng.', $name ),
			);
		}
		$top = array_pop( $stack );
		if ( $top !== $name ) {
			return array(
				'ok'    => false,
				'error' => sprintf( 'Thẻ không khớp: mở <%s> nhưng đóng </%s>.', $top, $name ),
			);
		}
	}

	if ( ! empty( $stack ) ) {
		return array(
			'ok'    => false,
			'error' => sprintf( 'Thẻ <%s> chưa được đóng.', end( $stack ) ),
		);
	}

	return array(
		'ok'    => true,
		'error' => '',
	);
}

/* -------------------------------------------------------------------------
 * Settings page (Settings → Mã tùy chỉnh) — Administrators only.
 * ---------------------------------------------------------------------- */

/** Capability required: real unfiltered HTML/script editing. */
function qp_holdings_custom_code_capability(): string {
	return 'unfiltered_html';
}

/**
 * Register the settings page under the Settings menu.
 */
function qp_holdings_custom_code_register_page(): void {
	add_options_page(
		'Mã tùy chỉnh (Header & Footer)',
		'Mã tùy chỉnh',
		qp_holdings_custom_code_capability(),
		'qp-custom-code',
		'qp_holdings_custom_code_render_page'
	);
}
add_action( 'admin_menu', 'qp_holdings_custom_code_register_page' );

/**
 * Register the setting + its sanitiser with the Settings API.
 */
function qp_holdings_custom_code_register_setting(): void {
	register_setting(
		'qp_custom_code_group',
		QP_HOLDINGS_CUSTOM_CODE_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'qp_holdings_custom_code_sanitize',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'qp_holdings_custom_code_register_setting' );

/**
 * Sanitise + validate on save. Each slot holds a list of snippets; every
 * snippet is validated independently. Reject-and-revert per snippet: a snippet
 * whose content fails structural validation keeps its previously stored
 * content and raises a settings error, so the DB never holds page-breaking
 * markup. Snippets with empty content are dropped (acts as delete).
 *
 * @param mixed $input Raw POSTed array.
 * @return array<string,array<int,array{label:string,content:string,enabled:bool,valid:bool}>>
 */
function qp_holdings_custom_code_sanitize( $input ): array {
	$existing = qp_holdings_custom_code_get();

	if ( ! current_user_can( qp_holdings_custom_code_capability() ) ) {
		return $existing;
	}

	$input  = is_array( $input ) ? $input : array();
	$result = array();

	foreach ( qp_holdings_custom_code_slots() as $slot => $label ) {
		$result[ $slot ] = array();
		$rows            = isset( $input[ $slot ] ) && is_array( $input[ $slot ] ) ? $input[ $slot ] : array();

		foreach ( $rows as $idx => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$snippet_label = isset( $row['label'] ) ? sanitize_text_field( (string) wp_unslash( $row['label'] ) ) : '';
			$content       = isset( $row['content'] ) ? (string) wp_unslash( $row['content'] ) : '';
			$enabled       = ! empty( $row['enabled'] );

			// Empty content + empty label => treat as removed row, skip.
			if ( '' === trim( $content ) && '' === trim( $snippet_label ) ) {
				continue;
			}

			$check = qp_holdings_validate_custom_code( $content );

			if ( $check['valid'] ) {
				$result[ $slot ][] = array(
					'label'   => '' !== $snippet_label ? $snippet_label : 'Mã không tên',
					'content' => $content,
					'enabled' => $enabled,
					'valid'   => true,
				);
			} else {
				// Keep the previous (known-good) version of this snippet if any.
				$prev = isset( $existing[ $slot ][ $idx ] ) ? $existing[ $slot ][ $idx ] : null;
				$result[ $slot ][] = array(
					'label'   => '' !== $snippet_label ? $snippet_label : 'Mã không tên',
					'content' => $prev ? $prev['content'] : '',
					'enabled' => $enabled,
					'valid'   => $prev ? $prev['valid'] : false,
				);
				add_settings_error(
					QP_HOLDINGS_CUSTOM_CODE_OPTION,
					'invalid_' . $slot . '_' . $idx,
					sprintf( 'Mã "%s" (vị trí "%s") chưa được lưu: %s', $snippet_label, $label, $check['error'] ),
					'error'
				);
			}
		}
	}

	return $result;
}

/**
 * Render a single snippet row (used both server-side and as a JS template).
 *
 * @param string $field   Top-level option field name.
 * @param string $slot     Slot key.
 * @param string $row_key  Index/placeholder for the row name attributes.
 * @param array  $snippet  Snippet data (label/content/enabled/valid).
 */
function qp_holdings_custom_code_render_row( string $field, string $slot, string $row_key, array $snippet ): void {
	$label   = isset( $snippet['label'] ) ? $snippet['label'] : '';
	$content = isset( $snippet['content'] ) ? $snippet['content'] : '';
	$enabled = ! empty( $snippet['enabled'] );
	$valid   = ! empty( $snippet['valid'] );
	$base    = sprintf( '%s[%s][%s]', $field, $slot, $row_key );
	?>
	<div class="qp-cc-snippet" style="border:1px solid #c3c4c7;border-radius:4px;padding:12px 14px;margin-bottom:12px;background:#fff;">
		<div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;flex-wrap:wrap;">
			<input type="text"
				name="<?php echo esc_attr( $base ); ?>[label]"
				value="<?php echo esc_attr( $label ); ?>"
				placeholder="Tên mã (vd: Google Analytics)"
				class="regular-text" style="flex:1 1 240px;" />
			<label style="white-space:nowrap;">
				<input type="checkbox" name="<?php echo esc_attr( $base ); ?>[enabled]" value="1" <?php checked( $enabled ); ?> />
				Kích hoạt
			</label>
			<button type="button" class="button button-link-delete qp-cc-remove" style="margin-left:auto;">Xóa</button>
		</div>
		<?php if ( '' !== trim( $content ) && ! $valid ) : ?>
			<p style="color:#b32d2e;margin:4px 0;">⚠ Nội dung hiện tại bị đánh dấu không hợp lệ và sẽ không được xuất ra.</p>
		<?php endif; ?>
		<textarea name="<?php echo esc_attr( $base ); ?>[content]"
			rows="5" class="large-text code" spellcheck="false"
			placeholder="Dán mã HTML/script tại đây"><?php echo esc_textarea( $content ); ?></textarea>
	</div>
	<?php
}

/**
 * Render the settings page.
 */
function qp_holdings_custom_code_render_page(): void {
	if ( ! current_user_can( qp_holdings_custom_code_capability() ) ) {
		wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'qp-holdings' ) );
	}

	$config = qp_holdings_custom_code_get();
	$field  = QP_HOLDINGS_CUSTOM_CODE_OPTION;
	?>
	<div class="wrap">
		<h1>Mã tùy chỉnh (Header &amp; Footer)</h1>
		<p>Thêm các đoạn mã HTML/script (thẻ SEO, Google Analytics, Google Tag Manager, mã xác minh…) vào từng vị trí. Mỗi vị trí có thể chứa nhiều đoạn mã riêng biệt — đặt tên, bật/tắt hoặc xóa từng đoạn độc lập. Mã được kiểm tra cấu trúc trước khi lưu để tránh phá vỡ giao diện trang.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'qp_custom_code_group' ); ?>
			<?php foreach ( qp_holdings_custom_code_slots() as $slot => $label ) : ?>
				<h2 style="margin-top:28px;"><?php echo esc_html( $label ); ?></h2>
				<div class="qp-cc-slot" data-slot="<?php echo esc_attr( $slot ); ?>">
					<div class="qp-cc-rows">
						<?php
						$snippets = $config[ $slot ];
						if ( ! empty( $snippets ) ) {
							foreach ( $snippets as $idx => $snippet ) {
								qp_holdings_custom_code_render_row( $field, $slot, (string) $idx, $snippet );
							}
						}
						?>
					</div>
					<button type="button" class="button qp-cc-add" data-slot="<?php echo esc_attr( $slot ); ?>">+ Thêm đoạn mã</button>
				</div>
			<?php endforeach; ?>

			<?php // Hidden row templates (one per slot) for the JS "add" button. ?>
			<?php foreach ( array_keys( qp_holdings_custom_code_slots() ) as $slot ) : ?>
				<script type="text/template" id="qp-cc-tpl-<?php echo esc_attr( $slot ); ?>">
					<?php
					qp_holdings_custom_code_render_row(
						$field,
						$slot,
						'__INDEX__',
						array(
							'label'   => '',
							'content' => '',
							'enabled' => true,
							'valid'   => true,
						)
					);
					?>
				</script>
			<?php endforeach; ?>

			<?php submit_button( 'Lưu thay đổi' ); ?>
		</form>
	</div>
	<script>
	( function () {
		var page = document.querySelector( '.wrap' );
		if ( ! page ) { return; }
		page.addEventListener( 'click', function ( e ) {
			var addBtn = e.target.closest( '.qp-cc-add' );
			if ( addBtn ) {
				e.preventDefault();
				var slot = addBtn.getAttribute( 'data-slot' );
				var tpl  = document.getElementById( 'qp-cc-tpl-' + slot );
				var rows = addBtn.parentNode.querySelector( '.qp-cc-rows' );
				if ( ! tpl || ! rows ) { return; }
				var uid  = 'new_' + Date.now() + '_' + Math.floor( Math.random() * 1000 );
				rows.insertAdjacentHTML( 'beforeend', tpl.innerHTML.replace( /__INDEX__/g, uid ) );
				return;
			}
			var rmBtn = e.target.closest( '.qp-cc-remove' );
			if ( rmBtn ) {
				e.preventDefault();
				var snip = rmBtn.closest( '.qp-cc-snippet' );
				if ( snip ) { snip.parentNode.removeChild( snip ); }
			}
		} );
	} )();
	</script>
	<?php
}

/* -------------------------------------------------------------------------
 * Frontend output. Each slot prints only when enabled AND structurally valid.
 * ---------------------------------------------------------------------- */

/**
 * Echo every active snippet for a slot (intentionally unescaped) in order.
 * A snippet prints only when enabled AND structurally valid AND non-empty.
 *
 * @param string $slot Slot key.
 */
function qp_holdings_custom_code_output( string $slot ): void {
	$config = qp_holdings_custom_code_get();
	if ( empty( $config[ $slot ] ) ) {
		return;
	}
	foreach ( $config[ $slot ] as $snippet ) {
		if ( empty( $snippet['enabled'] ) || empty( $snippet['valid'] ) || '' === trim( $snippet['content'] ) ) {
			continue;
		}
		// Trusted unfiltered_html output by design — do not escape.
		echo "\n" . $snippet['content'] . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

add_action( 'wp_head', static function (): void {
	qp_holdings_custom_code_output( 'head' );
}, 3 );

add_action( 'wp_body_open', static function (): void {
	qp_holdings_custom_code_output( 'body_open' );
}, 10 );

add_action( 'qp_holdings_custom_footer', static function (): void {
	qp_holdings_custom_code_output( 'footer' );
}, 10 );

add_action( 'wp_footer', static function (): void {
	qp_holdings_custom_code_output( 'body_close' );
}, 99 );

/* -------------------------------------------------------------------------
 * One-time seed: migrate the formerly hard-coded GTM block into the slots so
 * tracking continues unchanged after it is removed from the templates.
 * ---------------------------------------------------------------------- */

/**
 * Seed default GTM markup the first time the feature runs (idempotent).
 */
function qp_holdings_custom_code_seed(): void {
	if ( get_option( QP_HOLDINGS_CUSTOM_CODE_SEED_FLAG ) ) {
		return;
	}

	// If an admin already entered any snippet, never overwrite it.
	foreach ( qp_holdings_custom_code_get() as $snippets ) {
		foreach ( $snippets as $snippet ) {
			if ( '' !== trim( $snippet['content'] ) ) {
				update_option( QP_HOLDINGS_CUSTOM_CODE_SEED_FLAG, 1 );
				return;
			}
		}
	}

	$gtm_head = <<<'HTML'
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-W7RCVC65');</script>
<!-- End Google Tag Manager -->
HTML;

	$gtm_body = <<<'HTML'
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-W7RCVC65"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
HTML;

	$seed = array(
		'head'       => array(
			array(
				'label'   => 'Google Tag Manager',
				'content' => $gtm_head,
				'enabled' => true,
				'valid'   => true,
			),
		),
		'body_open'  => array(
			array(
				'label'   => 'Google Tag Manager (noscript)',
				'content' => $gtm_body,
				'enabled' => true,
				'valid'   => true,
			),
		),
		'footer'     => array(),
		'body_close' => array(),
	);

	update_option( QP_HOLDINGS_CUSTOM_CODE_OPTION, $seed );
	update_option( QP_HOLDINGS_CUSTOM_CODE_SEED_FLAG, 1 );
}
add_action( 'admin_init', 'qp_holdings_custom_code_seed' );
add_action( 'after_switch_theme', 'qp_holdings_custom_code_seed' );
