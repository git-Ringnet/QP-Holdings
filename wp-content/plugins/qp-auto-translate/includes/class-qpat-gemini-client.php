<?php
/**
 * Google Gemini client (generativelanguage.googleapis.com, v1beta).
 *
 * The prompt is the one QP LLM Translate has used on production: it translates
 * a JSON object of fields in one request and keeps HTML intact.
 */

defined( 'ABSPATH' ) || exit;

final class QPAT_Gemini_Client {
	const BASE = 'https://generativelanguage.googleapis.com/v1beta/';

	const LANG_NAMES = array(
		'vi' => 'Vietnamese',
		'en' => 'English',
		'zh' => 'Simplified Chinese',
	);

	/** HTTP statuses worth retrying (rate limit, overload, gateway errors). */
	const RETRY_CODES = array( 408, 409, 425, 429, 500, 502, 503, 504 );

	/**
	 * Translate a set of named fields (title, excerpt, content, meta...).
	 *
	 * @param array<string, string> $fields
	 * @return array<string, string> Same keys as $fields.
	 * @throws QPAT_Transient_Exception On rate limits / network errors (worth retrying later).
	 * @throws RuntimeException         On configuration or model errors.
	 */
	public static function translate( array $fields, string $target, string $source = 'vi' ): array {
		$key = QPAT_Settings::gemini_api_key();
		if ( '' === $key ) {
			throw new RuntimeException( 'Chưa nhập Gemini API key.' );
		}
		$model = QPAT_Settings::get()['gemini_model'];
		$to    = self::LANG_NAMES[ $target ] ?? $target;

		$payload_fields = array();
		foreach ( $fields as $field_name => $value ) {
			if ( is_string( $field_name ) && is_scalar( $value ) ) {
				$payload_fields[ $field_name ] = (string) $value;
			}
		}
		foreach ( array( 'title', 'excerpt', 'content' ) as $required ) {
			if ( ! isset( $payload_fields[ $required ] ) ) {
				$payload_fields[ $required ] = '';
			}
		}

		$payload = wp_json_encode( $payload_fields, JSON_UNESCAPED_UNICODE );
		$keys    = implode( ', ', array_keys( $payload_fields ) );

		$prompt = "You are a professional corporate translator for QP Holdings. Translate all JSON fields into {$to}.\n"
			. "Detect the source language automatically (Vietnamese or English).\n"
			. "CRITICAL TRANSLATION INSTRUCTIONS:\n"
			. "1. Keep all HTML tags, structure, inline styles, classes, and WordPress block comments intact.\n"
			. "2. Fully translate all visible text content, including headings, paragraphs, and especially contact/information boxes at the end of articles:\n"
			. "   - Corporate Taglines: Keep brand 'QP HOLDINGS', translate taglines (e.g. 'QP HOLDINGS – NHÀ PHÁT TRIỂN BẤT ĐỘNG SẢN' -> EN: 'QP HOLDINGS – REAL ESTATE DEVELOPER', ZH: 'QP HOLDINGS – 房地产开发商').\n"
			. "   - Addresses: Translate address administrative terms (e.g. 'Tầng L16' -> 'Level L16' / 'L16层', 'Tòa nhà Vietcombank' -> 'Vietcombank Tower' / 'Vietcombank大厦', 'Số 5 Công Trường Mê Linh' -> '5 Cong Truong Me Linh', 'Phường Sài Gòn' / 'P. Sài Gòn' -> 'Sai Gon Ward' / 'Sài Gòn坊', 'TP. Hồ Chí Minh' / 'Thành phố Hồ Chí Minh' -> 'Ho Chi Minh City' / '胡志明市', 'Việt Nam' -> 'Vietnam' / '越南').\n"
			. "   - Information Box Labels in EN: 'Địa chỉ:' -> 'Address:', 'Mã chứng khoán:' -> 'Stock ticker:', 'Nguồn số liệu tham khảo:' -> 'Reference sources:', 'Tổng hợp và biên soạn nội dung:' -> 'Compiled and edited by:'.\n"
			. "   - Information Box Labels in Simplified Chinese (ZH) MUST be in Chinese characters (Hán tự): 'Địa chỉ:' / 'Address:' -> '地址：', 'Website:' -> '官方网站：', 'Hotline:' -> '服务热线：', 'Email:' -> '电子邮箱：', 'Fanpage FB:' / 'Fanpage:' -> 'Facebook 官方专页：', 'Mã chứng khoán:' / 'Stock ticker:' -> '证券代码：', 'Nguồn số liệu tham khảo:' -> '参考数据来源：', 'Tổng hợp và biên soạn nội dung:' -> '内容编辑与整理：'. Never leave English labels like Website/Hotline/Fanpage/Email in Chinese output.\n"
			. "   - Departments & Credits: Translate departmental names (e.g. 'Ban Truyền Thông — QP HOLDINGS' -> EN: 'Communications Department — QP HOLDINGS', ZH: '传媒部 — QP HOLDINGS'; 'Ban Quan hệ Nhà đầu tư' -> EN: 'Investor Relations Department', ZH: '投资者关系部').\n"
			. "3. What to preserve unchanged: URLs (e.g. qpholdings.vn, facebook.com links), email addresses (e.g. info@qpholdings.vn), phone numbers (e.g. 1900 0066), stock ticker codes (e.g. HKT), and brand names (QP Holdings, Vietcombank).\n"
			. "4. Title translation: The title MUST be fully translated into {$to}. For Simplified Chinese (zh), the title must contain Chinese characters.\n"
			. "5. Leave empty strings empty. Do not add or remove keys.\n"
			. "7. Placeholders like {{QPAT_1}} stand for images, videos and embeds: copy every placeholder exactly as written and in the same position. Never translate, change or drop them.\n"
			. "6. Return ONLY valid JSON with keys: {$keys}. No markdown code fences.\n\n"
			. $payload;

		$text   = self::generate( $key, $model, $prompt );
		$parsed = self::parse_json( $text );

		$out = array();
		foreach ( $payload_fields as $field_key => $field_value ) {
			if ( in_array( $field_key, array( 'category_info', 'source' ), true )
				&& '' !== trim( $field_value )
				&& ( ! isset( $parsed[ $field_key ] ) || ! is_string( $parsed[ $field_key ] ) || '' === trim( $parsed[ $field_key ] ) ) ) {
				throw new RuntimeException( 'Gemini không dịch trường: ' . $field_key );
			}
			$out[ $field_key ] = ( isset( $parsed[ $field_key ] ) && is_scalar( $parsed[ $field_key ] ) )
				? (string) $parsed[ $field_key ]
				: $field_value;
		}
		self::assert_translated_title( $out, $target );

		// Return only the keys the caller asked for.
		return array_intersect_key( $out, $fields );
	}

	/**
	 * Models this key can use for generateContent, e.g. ["gemini-3.5-flash-lite", ...].
	 *
	 * @return string[]
	 */
	public static function list_models(): array {
		$key = QPAT_Settings::gemini_api_key();
		if ( '' === $key ) {
			throw new RuntimeException( 'Chưa nhập Gemini API key.' );
		}
		$response = wp_remote_get(
			self::BASE . 'models?pageSize=200&key=' . rawurlencode( $key ),
			array( 'timeout' => 20 )
		);
		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( 'Không kết nối được Gemini: ' . $response->get_error_message() );
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( 200 !== $code || ! is_array( $data ) ) {
			throw new RuntimeException( 'Gemini trả lỗi (' . $code . '): ' . self::error_text( $data, $code ) );
		}
		$out = array();
		foreach ( (array) ( $data['models'] ?? array() ) as $m ) {
			$name    = preg_replace( '#^models/#', '', (string) ( $m['name'] ?? '' ) );
			$methods = (array) ( $m['supportedGenerationMethods'] ?? array() );
			if ( '' !== $name && in_array( 'generateContent', $methods, true ) && 0 === strpos( $name, 'gemini' ) ) {
				$out[] = $name;
			}
		}
		sort( $out );
		return $out;
	}

	private static function generate( string $key, string $model, string $prompt ): string {
		$url  = self::BASE . 'models/' . rawurlencode( $model ) . ':generateContent?key=' . rawurlencode( $key );
		$data = self::post_json(
			$url,
			array(
				'contents'         => array(
					array( 'parts' => array( array( 'text' => $prompt ) ) ),
				),
				'generationConfig' => array(
					'temperature'      => 0.2,
					'responseMimeType' => 'application/json',
				),
			)
		);
		$text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
		if ( ! is_string( $text ) || '' === trim( $text ) ) {
			$reason = (string) ( $data['candidates'][0]['finishReason'] ?? ( $data['promptFeedback']['blockReason'] ?? '' ) );
			throw new RuntimeException( 'Gemini trả về nội dung rỗng' . ( '' !== $reason ? ' (' . $reason . ')' : '' ) . '.' );
		}
		return $text;
	}

	/**
	 * POST with a few quick retries; persistent rate limits become a
	 * QPAT_Transient_Exception so the queue retries the job a few minutes later.
	 */
	private static function post_json( string $url, array $payload ): array {
		$attempts = (int) apply_filters( 'qpat_gemini_attempts', 3 );
		$last     = '';
		for ( $attempt = 1; $attempt <= $attempts; $attempt++ ) {
			$response = wp_remote_post(
				$url,
				array(
					'timeout'     => 120,
					'redirection' => 0,
					'headers'     => array( 'Content-Type' => 'application/json' ),
					'body'        => wp_json_encode( $payload ),
				)
			);

			if ( is_wp_error( $response ) ) {
				$last = 'Không kết nối được Gemini: ' . $response->get_error_message();
				if ( $attempt < $attempts ) {
					self::pause( $attempt );
					continue;
				}
				throw new QPAT_Transient_Exception( $last );
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

			if ( in_array( $code, self::RETRY_CODES, true ) ) {
				$last = 'Gemini tạm thời không phản hồi (HTTP ' . $code . '): ' . self::error_text( $data, $code );
				if ( $attempt < $attempts ) {
					self::pause( $attempt );
					continue;
				}
				throw new QPAT_Transient_Exception( $last );
			}
			if ( $code >= 300 ) {
				throw new RuntimeException( 'Gemini trả lỗi (HTTP ' . $code . '): ' . self::error_text( $data, $code ) );
			}
			if ( ! is_array( $data ) ) {
				throw new RuntimeException( 'Phản hồi của Gemini không phải JSON.' );
			}
			return $data;
		}
		throw new QPAT_Transient_Exception( '' !== $last ? $last : 'Gọi Gemini thất bại.' );
	}

	private static function pause( int $attempt ): void {
		$seconds = (int) apply_filters( 'qpat_gemini_retry_sleep', $attempt * 2 );
		if ( $seconds > 0 ) {
			sleep( $seconds );
		}
	}

	/** @param mixed $data */
	private static function error_text( $data, int $code ): string {
		if ( is_array( $data ) && isset( $data['error']['message'] ) ) {
			return (string) $data['error']['message'];
		}
		return 'HTTP ' . $code;
	}

	/**
	 * Gemini sometimes returns the Vietnamese/English title unchanged for ZH.
	 *
	 * @param array<string, string> $out
	 */
	private static function assert_translated_title( array $out, string $target ): void {
		if ( 'zh' !== $target ) {
			return;
		}
		$title = trim( (string) ( $out['title'] ?? '' ) );
		if ( '' === $title || preg_match( '/\p{Han}/u', $title ) ) {
			return;
		}
		$latin = preg_match_all( '/[A-Za-z]/', $title );
		if ( is_int( $latin ) && $latin >= 12 ) {
			throw new RuntimeException( 'Gemini chưa dịch tiêu đề sang tiếng Trung.' );
		}
	}

	private static function parse_json( string $text ): array {
		$text = trim( $text );
		$text = (string) preg_replace( '/^```(?:json)?\s*|\s*```$/', '', $text );
		$data = json_decode( $text, true );
		if ( ! is_array( $data ) ) {
			throw new RuntimeException( 'Gemini không trả về JSON hợp lệ.' );
		}
		return $data;
	}
}
