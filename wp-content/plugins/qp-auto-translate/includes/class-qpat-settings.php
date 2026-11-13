<?php
/**
 * Plugin settings (stored in one option).
 */

defined( 'ABSPATH' ) || exit;

final class QPAT_Settings {
	const OPTION       = 'qpat_settings';
	const TOKEN_OPTION = 'qpat_worker_token';

	/** Option written by the old QP LLM Translate plugin (imported once). */
	const LEGACY_OPTION = 'qp_llm_translate_settings';

	const ENGINES = array(
		'gemini' => 'Google Gemini (AI)',
		'google' => 'Google Cloud Translation (NMT)',
	);

	const DEFAULT_GEMINI_MODEL = 'gemini-3.5-flash-lite';

	/**
	 * Post types that must never be sent to an external translation service
	 * (form submissions contain customers' personal data).
	 */
	const BLOCKED_POST_TYPES = array(
		'attachment',
		'revision',
		'nav_menu_item',
		'qp_contact_msg',
		'qp_job_app',
		'qp_consult_msg',
		'qp_newsletter',
		'acf-field-group',
		'acf-field',
		'acf-post-type',
		'acf-taxonomy',
		'acf-ui-options-page',
		'wp_block',
		'wp_navigation',
		'wp_template',
		'wp_template_part',
		'wp_global_styles',
		'wp_font_family',
		'wp_font_face',
	);

	/** Languages the site supports => label. */
	const LANGUAGES = array(
		'vi' => 'Tiếng Việt',
		'en' => 'English',
		'zh' => '中文 (giản thể)',
	);

	public static function defaults(): array {
		return array(
			'engine'          => 'gemini',
			'gemini_api_key'  => '',
			'gemini_model'    => self::DEFAULT_GEMINI_MODEL,
			'fallback_google' => 0,
			'api_key'         => '', // Google Cloud Translation key.
			'source_lang'     => 'vi',
			'target_langs'    => array( 'en', 'zh' ),
			'post_types'      => array( 'post', 'page', 'qp_news' ),
			'auto_on_publish' => 1,
			'auto_update'     => 1,
			'glossary'        => '',
			'no_translate'    => "QP Holdings\nQP HOLDINGS",
			'skip_fields'     => '',
			'monthly_limit'   => 500000,
			'warn_percent'    => 80,
		);
	}

	public static function get(): array {
		$stored = get_option( self::OPTION, array() );
		$s      = array_merge( self::defaults(), is_array( $stored ) ? $stored : array() );

		$s['engine']        = isset( self::ENGINES[ $s['engine'] ] ) ? $s['engine'] : 'gemini';
		$s['gemini_model']  = '' !== trim( (string) $s['gemini_model'] ) ? trim( (string) $s['gemini_model'] ) : self::DEFAULT_GEMINI_MODEL;
		$s['target_langs']  = array_values( array_intersect( (array) $s['target_langs'], array( 'en', 'zh' ) ) );
		$s['post_types']    = array_values( array_diff( (array) $s['post_types'], self::BLOCKED_POST_TYPES ) );
		$s['monthly_limit'] = max( 0, (int) $s['monthly_limit'] );
		$s['warn_percent']  = min( 100, max( 1, (int) $s['warn_percent'] ) );
		return $s;
	}

	/**
	 * Google Cloud Translation key: a constant in wp-config.php wins over the database.
	 */
	public static function api_key(): string {
		if ( self::api_key_from_constant() ) {
			return (string) QPAT_GOOGLE_API_KEY;
		}
		return trim( (string) self::get()['api_key'] );
	}

	public static function api_key_from_constant(): bool {
		return defined( 'QPAT_GOOGLE_API_KEY' ) && '' !== (string) QPAT_GOOGLE_API_KEY;
	}

	/**
	 * Gemini key: a constant in wp-config.php wins over the database.
	 */
	public static function gemini_api_key(): string {
		if ( self::gemini_key_from_constant() ) {
			return (string) QPAT_GEMINI_API_KEY;
		}
		return trim( (string) self::get()['gemini_api_key'] );
	}

	public static function gemini_key_from_constant(): bool {
		return defined( 'QPAT_GEMINI_API_KEY' ) && '' !== (string) QPAT_GEMINI_API_KEY;
	}

	/**
	 * Shared secret for the background worker loopback request.
	 */
	public static function worker_token(): string {
		$token = (string) get_option( self::TOKEN_OPTION, '' );
		if ( '' === $token ) {
			$token = wp_generate_password( 32, false, false );
			update_option( self::TOKEN_OPTION, $token, false );
		}
		return $token;
	}

	/**
	 * First run on a site that already used QP LLM Translate: copy its Gemini key,
	 * model, post types and auto-publish switch so nothing has to be re-entered.
	 * Runs only when this plugin has never been configured.
	 */
	public static function maybe_import_legacy(): void {
		if ( false !== get_option( self::OPTION, false ) ) {
			return;
		}
		$legacy = get_option( self::LEGACY_OPTION, array() );
		if ( ! is_array( $legacy ) || ! $legacy ) {
			return;
		}

		$s = self::defaults();
		if ( 'gemini' === ( $legacy['provider'] ?? '' ) && '' !== trim( (string) ( $legacy['api_key'] ?? '' ) ) ) {
			$s['gemini_api_key'] = trim( (string) $legacy['api_key'] );
			if ( '' !== trim( (string) ( $legacy['model'] ?? '' ) ) ) {
				$s['gemini_model'] = sanitize_text_field( (string) $legacy['model'] );
			}
		}
		$langs = array_values( array_intersect( (array) ( $legacy['target_langs'] ?? array() ), array( 'en', 'zh' ) ) );
		if ( $langs ) {
			$s['target_langs'] = $langs;
		}
		$types = array_values( array_diff( (array) ( $legacy['post_types'] ?? array() ), self::BLOCKED_POST_TYPES ) );
		if ( $types ) {
			$s['post_types'] = $types;
		}
		$s['auto_on_publish'] = empty( $legacy['auto_on_publish'] ) ? 0 : 1;

		add_option( self::OPTION, $s );
	}

	public static function sanitize( $input ): array {
		$old = self::get();
		$in  = is_array( $input ) ? $input : array();
		$out = self::defaults();

		$engine        = sanitize_key( (string) ( $in['engine'] ?? 'gemini' ) );
		$out['engine'] = isset( self::ENGINES[ $engine ] ) ? $engine : 'gemini';

		$out['api_key']        = self::sanitize_key_field( $in, 'api_key', 'clear_api_key', (string) $old['api_key'] );
		$out['gemini_api_key'] = self::sanitize_key_field( $in, 'gemini_api_key', 'clear_gemini_api_key', (string) $old['gemini_api_key'] );

		$model               = preg_replace( '/[^A-Za-z0-9._\-\/]/', '', (string) ( $in['gemini_model'] ?? '' ) );
		$model               = preg_replace( '#^models/#', '', (string) $model );
		$out['gemini_model'] = '' !== $model ? $model : self::DEFAULT_GEMINI_MODEL;

		$out['fallback_google'] = empty( $in['fallback_google'] ) ? 0 : 1;
		$out['source_lang']     = 'vi';

		$langs               = array_map( 'sanitize_key', (array) ( $in['target_langs'] ?? array() ) );
		$out['target_langs'] = array_values( array_intersect( $langs, array( 'en', 'zh' ) ) );

		$types             = array_map( 'sanitize_key', (array) ( $in['post_types'] ?? array() ) );
		$out['post_types'] = array_values( array_diff( array_filter( $types, 'post_type_exists' ), self::BLOCKED_POST_TYPES ) );

		$out['auto_on_publish'] = empty( $in['auto_on_publish'] ) ? 0 : 1;
		$out['auto_update']     = empty( $in['auto_update'] ) ? 0 : 1;
		$out['glossary']        = self::clean_lines( (string) ( $in['glossary'] ?? '' ) );
		$out['no_translate']    = self::clean_lines( (string) ( $in['no_translate'] ?? '' ) );
		$out['skip_fields']     = implode( "\n", array_filter( array_map( 'sanitize_key', explode( "\n", self::clean_lines( (string) ( $in['skip_fields'] ?? '' ) ) ) ) ) );
		$out['monthly_limit']   = max( 0, (int) ( $in['monthly_limit'] ?? 500000 ) );
		$out['warn_percent']    = min( 100, max( 1, (int) ( $in['warn_percent'] ?? 80 ) ) );
		return $out;
	}

	/**
	 * Keep the stored key when the masked placeholder comes back unchanged.
	 */
	private static function sanitize_key_field( array $in, string $field, string $clear_field, string $old ): string {
		if ( ! empty( $in[ $clear_field ] ) ) {
			return '';
		}
		$key = isset( $in[ $field ] ) ? trim( (string) $in[ $field ] ) : '';
		if ( '' === $key || 0 === strpos( $key, '••••' ) ) {
			return $old;
		}
		return sanitize_text_field( $key );
	}

	private static function clean_lines( string $text ): string {
		$lines = array();
		foreach ( preg_split( '/\R/u', wp_unslash( $text ) ) as $line ) {
			$line = trim( sanitize_text_field( (string) $line ) );
			if ( '' !== $line ) {
				$lines[] = $line;
			}
		}
		return implode( "\n", array_slice( array_unique( $lines ), 0, 500 ) );
	}

	/**
	 * Preferred translations, one line each: "Tiếng Việt | English | 中文".
	 *
	 * @return array<int, array{vi:string, en:string, zh:string}>
	 */
	public static function glossary(): array {
		$out = array();
		foreach ( explode( "\n", (string) self::get()['glossary'] ) as $line ) {
			$cols = array_map( 'trim', explode( '|', $line ) );
			if ( count( $cols ) < 2 || '' === $cols[0] ) {
				continue;
			}
			$out[] = array( 'vi' => $cols[0], 'en' => $cols[1] ?? '', 'zh' => $cols[2] ?? '' );
		}
		return $out;
	}

	/** @return string[] Words / names never translated (brand, project names...). */
	public static function no_translate_terms(): array {
		return array_values( array_filter( array_map( 'trim', explode( "\n", (string) self::get()['no_translate'] ) ) ) );
	}

	/** @return string[] Field names copied unchanged instead of being translated. */
	public static function skip_fields(): array {
		return array_values( array_filter( array_map( 'trim', explode( "\n", (string) self::get()['skip_fields'] ) ) ) );
	}

	/** Masked display value for a stored key ("••••••••abcd"). */
	public static function mask( string $key ): string {
		return '' !== $key ? '••••••••' . substr( $key, -4 ) : '';
	}

	/**
	 * Post types shown as choices on the settings screen.
	 *
	 * @return array<string, string> name => label
	 */
	public static function selectable_post_types(): array {
		$out = array();
		foreach ( get_post_types( array( 'show_ui' => true ), 'objects' ) as $name => $obj ) {
			if ( in_array( $name, self::BLOCKED_POST_TYPES, true ) ) {
				continue;
			}
			$out[ $name ] = sprintf( '%s (%s)', $obj->labels->singular_name ?? $obj->label, $name );
		}
		return $out;
	}
}
