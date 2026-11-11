<?php
/**
 * Plugin settings (stored in one option).
 */

defined( 'ABSPATH' ) || exit;

final class QPAT_Settings {
	const OPTION = 'qpat_settings';

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
			'api_key'         => '',
			'source_lang'     => 'vi',
			'target_langs'    => array( 'en', 'zh' ),
			'post_types'      => array( 'qp_news' ),
			'auto_on_publish' => 1,
			'monthly_limit'   => 500000,
			'warn_percent'    => 80,
		);
	}

	public static function get(): array {
		$stored = get_option( self::OPTION, array() );
		$s      = array_merge( self::defaults(), is_array( $stored ) ? $stored : array() );

		$s['target_langs']  = array_values( array_intersect( (array) $s['target_langs'], array_keys( self::LANGUAGES ) ) );
		$s['post_types']    = array_values( array_diff( (array) $s['post_types'], self::BLOCKED_POST_TYPES ) );
		$s['monthly_limit'] = max( 0, (int) $s['monthly_limit'] );
		$s['warn_percent']  = min( 100, max( 1, (int) $s['warn_percent'] ) );
		return $s;
	}

	/**
	 * API key: a constant in wp-config.php wins over the value stored in the database.
	 */
	public static function api_key(): string {
		if ( defined( 'QPAT_GOOGLE_API_KEY' ) && '' !== (string) QPAT_GOOGLE_API_KEY ) {
			return (string) QPAT_GOOGLE_API_KEY;
		}
		return trim( (string) self::get()['api_key'] );
	}

	public static function api_key_from_constant(): bool {
		return defined( 'QPAT_GOOGLE_API_KEY' ) && '' !== (string) QPAT_GOOGLE_API_KEY;
	}

	public static function sanitize( $input ): array {
		$old = self::get();
		$in  = is_array( $input ) ? $input : array();
		$out = self::defaults();

		// Keep the stored key when the masked placeholder comes back unchanged.
		$key = isset( $in['api_key'] ) ? trim( (string) $in['api_key'] ) : '';
		$out['api_key'] = ( '' === $key || 0 === strpos( $key, '••••' ) ) ? $old['api_key'] : sanitize_text_field( $key );
		if ( ! empty( $in['clear_api_key'] ) ) {
			$out['api_key'] = '';
		}

		$out['source_lang'] = 'vi';

		$langs = array_map( 'sanitize_key', (array) ( $in['target_langs'] ?? array() ) );
		$out['target_langs'] = array_values( array_intersect( $langs, array( 'en', 'zh' ) ) );

		$types = array_map( 'sanitize_key', (array) ( $in['post_types'] ?? array() ) );
		$out['post_types'] = array_values( array_diff( array_filter( $types, 'post_type_exists' ), self::BLOCKED_POST_TYPES ) );

		$out['auto_on_publish'] = empty( $in['auto_on_publish'] ) ? 0 : 1;
		$out['monthly_limit']   = max( 0, (int) ( $in['monthly_limit'] ?? 500000 ) );
		$out['warn_percent']    = min( 100, max( 1, (int) ( $in['warn_percent'] ?? 80 ) ) );
		return $out;
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
