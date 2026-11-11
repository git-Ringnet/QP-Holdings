<?php
/**
 * Google Cloud Translation – Basic (v2) client, NMT model, HTML format.
 *
 * Docs: https://cloud.google.com/translate/docs/reference/rest/v2/translate
 */

defined( 'ABSPATH' ) || exit;

final class QPAT_Quota_Exception extends RuntimeException {}

final class QPAT_Google_Client {
	const ENDPOINT = 'https://translation.googleapis.com/language/translate/v2';

	/** Max segments per request (Google limit is 128). */
	const MAX_SEGMENTS = 100;

	/** Keep each request well under Google's recommended 30,000 characters. */
	const MAX_CHARS_PER_REQUEST = 25000;

	/** Site language code => Google language code. */
	const LANG_MAP = array(
		'vi' => 'vi',
		'en' => 'en',
		'zh' => 'zh-CN',
	);

	/**
	 * Translate a list of HTML/text segments. Keys are preserved.
	 *
	 * Characters are checked against the monthly limit BEFORE calling Google and
	 * recorded AFTER a successful response.
	 *
	 * @param array<string|int, string> $segments
	 * @return array<string|int, string>
	 * @throws QPAT_Quota_Exception When the monthly limit would be exceeded.
	 * @throws RuntimeException     On configuration or API errors.
	 */
	public static function translate( array $segments, string $target, string $source = 'vi' ): array {
		$key = QPAT_Settings::api_key();
		if ( '' === $key ) {
			throw new RuntimeException( 'Chưa nhập Google Cloud API key.' );
		}
		if ( ! isset( self::LANG_MAP[ $target ], self::LANG_MAP[ $source ] ) ) {
			throw new RuntimeException( 'Ngôn ngữ không được hỗ trợ: ' . $source . ' → ' . $target );
		}

		// Empty segments are returned as-is and never sent (not billed).
		$out     = array();
		$pending = array();
		foreach ( $segments as $k => $text ) {
			$text = (string) $text;
			if ( '' === trim( $text ) ) {
				$out[ $k ] = $text;
			} else {
				$pending[ $k ] = $text;
			}
		}
		if ( ! $pending ) {
			return $out;
		}

		$total = 0;
		foreach ( $pending as $text ) {
			$total += self::char_count( $text );
		}
		if ( ! QPAT_Usage::allows( $total ) ) {
			throw new QPAT_Quota_Exception(
				sprintf(
					'Đã đạt giới hạn ký tự trong tháng (%s / %s). Tạm dừng dịch để không phát sinh phí.',
					number_format_i18n( QPAT_Usage::used() ),
					number_format_i18n( (int) QPAT_Settings::get()['monthly_limit'] )
				)
			);
		}

		foreach ( self::chunk( $pending ) as $batch ) {
			$translated = self::request( array_values( $batch ), self::LANG_MAP[ $target ], self::LANG_MAP[ $source ], $key );
			$chars      = 0;
			$i          = 0;
			foreach ( $batch as $k => $text ) {
				$out[ $k ] = $translated[ $i++ ];
				$chars    += self::char_count( $text );
			}
			QPAT_Usage::add( $target, $chars );
		}

		// Preserve the caller's key order.
		$ordered = array();
		foreach ( $segments as $k => $unused ) {
			$ordered[ $k ] = $out[ $k ];
		}
		return $ordered;
	}

	/** Characters as Google counts them (Unicode code points, markup included). */
	public static function char_count( string $text ): int {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $text, 'UTF-8' ) : strlen( $text );
	}

	/**
	 * @param array<string|int, string> $pending
	 * @return array<int, array<string|int, string>>
	 */
	private static function chunk( array $pending ): array {
		$batches = array();
		$cur     = array();
		$size    = 0;
		foreach ( $pending as $k => $text ) {
			$len = self::char_count( $text );
			if ( $cur && ( count( $cur ) >= self::MAX_SEGMENTS || $size + $len > self::MAX_CHARS_PER_REQUEST ) ) {
				$batches[] = $cur;
				$cur       = array();
				$size      = 0;
			}
			$cur[ $k ] = $text;
			$size     += $len;
		}
		if ( $cur ) {
			$batches[] = $cur;
		}
		return $batches;
	}

	/**
	 * @param string[] $texts
	 * @return string[]
	 */
	private static function request( array $texts, string $target, string $source, string $key ): array {
		$response = wp_remote_post(
			add_query_arg( 'key', rawurlencode( $key ), self::ENDPOINT ),
			array(
				'timeout' => 45,
				'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'body'    => wp_json_encode(
					array(
						'q'      => $texts,
						'source' => $source,
						'target' => $target,
						'format' => 'html',
						'model'  => 'nmt',
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( 'Không kết nối được Google: ' . $response->get_error_message() );
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code ) {
			$msg = is_array( $body ) && isset( $body['error']['message'] ) ? (string) $body['error']['message'] : 'HTTP ' . $code;
			throw new RuntimeException( 'Google trả lỗi (' . $code . '): ' . $msg );
		}
		$items = is_array( $body ) ? ( $body['data']['translations'] ?? null ) : null;
		if ( ! is_array( $items ) || count( $items ) !== count( $texts ) ) {
			throw new RuntimeException( 'Phản hồi của Google không hợp lệ.' );
		}
		return array_map(
			static function ( $item ): string {
				return (string) ( $item['translatedText'] ?? '' );
			},
			$items
		);
	}
}
