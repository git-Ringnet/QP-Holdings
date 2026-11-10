<?php
defined( 'ABSPATH' ) || exit;

final class QP_LLM_Client {
	public static function translate( array $fields, string $source, string $target, array $settings ): array {
		$key = trim( (string) ( $settings['api_key'] ?? '' ) );
		if ( '' === $key ) {
			throw new RuntimeException( 'API key is not set.' );
		}

		$names = array(
			'vi' => 'Vietnamese',
			'en' => 'English',
			'zh' => 'Simplified Chinese',
			'ja' => 'Japanese',
			'ko' => 'Korean',
			'fr' => 'French',
		);
		$from = $names[ $source ] ?? $source;
		$to   = $names[ $target ] ?? $target;

		$payload_fields = array();
		foreach ( $fields as $field_name => $value ) {
			if ( ! is_string( $field_name ) || ! is_scalar( $value ) ) {
				continue;
			}
			$payload_fields[ $field_name ] = (string) $value;
		}
		if ( ! isset( $payload_fields['title'] ) ) {
			$payload_fields['title'] = '';
		}
		if ( ! isset( $payload_fields['excerpt'] ) ) {
			$payload_fields['excerpt'] = '';
		}
		if ( ! isset( $payload_fields['content'] ) ) {
			$payload_fields['content'] = '';
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
			. "6. Return ONLY valid JSON with keys: {$keys}. No markdown code fences.\n\n"
			. $payload;

		$provider = (string) ( $settings['provider'] ?? 'deepseek' );
		$model    = trim( (string) ( $settings['model'] ?? '' ) );
		$text     = self::complete( $provider, $key, $model, $prompt );
		$parsed   = self::parse_json( $text );

		$out = array();
		foreach ( $payload_fields as $field_key => $field_value ) {
			if ( in_array( $field_key, array( 'category_info', 'source' ), true )
				&& '' !== trim( $field_value )
				&& ( ! isset( $parsed[ $field_key ] ) || ! is_string( $parsed[ $field_key ] ) || '' === trim( $parsed[ $field_key ] ) ) ) {
				throw new RuntimeException( 'Model did not translate news metadata: ' . $field_key );
			}
			$out[ $field_key ] = ( isset( $parsed[ $field_key ] ) && is_scalar( $parsed[ $field_key ] ) )
				? (string) $parsed[ $field_key ]
				: $field_value;
		}
		self::assert_translated_title( $out, $target );
		$out = self::post_process_translated_fields( $out, $target );
		return $out;
	}

	/**
	 * Normalize standard corporate footer and metadata strings that LLMs might partially preserve.
	 *
	 * @param array<string, string> $out
	 * @param string $target
	 * @return array<string, string>
	 */
	public static function post_process_translated_fields( array $out, string $target ): array {
		if ( ! in_array( $target, array( 'en', 'zh' ), true ) ) {
			return $out;
		}

		$replacements_en = array(
			// Taglines & Titles
			'/QP\s+HOLDINGS\s*[-–—]\s*NHÀ\s+PHÁT\s+TRIỂN\s+BẤT\s+ĐỘNG\s+SẢN/ui' => 'QP HOLDINGS – REAL ESTATE DEVELOPER',

			// Address terms & Address string
			'/Tầng\s+L?16,\s*Tòa\s+nhà\s+Vietcombank,\s*Số\s+5\s+(?:Công\s+Trường|Cong\s+Truong)\s*(?:Mê|Me)\s*Linh,\s*(?:P\.|Phường)\s*(?:Sài\s+Gòn|Sai\s+Gon),\s*(?:TP\.\s*Hồ\s+Chí\s+Minh|Thành\s+phố\s+Hồ\s+Chí\s+Minh|Ho\s+Chi\s+Minh\s+City)(?:,\s*(?:Việt\s+Nam|Vietnam))?/ui' => 'Level L16, Vietcombank Tower, 5 Cong Truong Me Linh, Sai Gon Ward, Ho Chi Minh City',
			'/Tầng\s+L?16,\s*Tòa\s+nhà\s+Vietcombank/ui' => 'Level L16, Vietcombank Tower',
			'/\b(?:Địa\s+chỉ|Address)\s*:/ui' => 'Address:',
			'/\bTrang\s+web\s*:/ui' => 'Website:',
			'/\bĐường\s+dây\s+nóng\s*:/ui' => 'Hotline:',
			'/\bThư\s+điện\s+tử\s*:/ui' => 'Email:',

			// Stock ticker
			'/\bMã\s+chứng\s+khoán\s*:\s*HKT/ui' => 'Stock ticker: HKT',
			'/\bMã\s+chứng\s+khoán\s*:/ui' => 'Stock ticker:',

			// Reference source
			'/\bNguồn\s+(?:số\s+liệu\s+)?tham\s+khảo\s*:/ui' => 'Reference sources:',

			// Editorial credits
			'/\bTổng\s+hợp\s+và\s+biên\s+soạn\s+(?:nội\s+dung\s*)?:/ui' => 'Compiled and edited by:',
			'/Ban\s+Truyền\s+Thông\s*[-–—]\s*QP\s+HOLDINGS/ui' => 'Communications Department — QP HOLDINGS',
			'/Ban\s+Truyền\s+thông\s*[-–—]\s*QP\s+Holdings/ui' => 'Communications Department — QP Holdings',
			'/Ban\s+Quan\s+hệ\s+Nhà\s+đầu\s+tư\s*[-–—]\s*QP\s+HOLDINGS/ui' => 'Investor Relations Department — QP HOLDINGS',
			'/Ban\s+Quan\s+hệ\s+Nhà\s+đầu\s+tư/ui' => 'Investor Relations Department',
			'/Ban\s+Truyền\s+[tT]hông/ui' => 'Communications Department',
		);

		$replacements_zh = array(
			// Taglines & Titles
			'/QP\s+HOLDINGS\s*[-–—]\s*(?:NHÀ\s+PHÁT\s+TRIỂN\s+BẤT\s+ĐỘNG\s+SẢN|REAL\s+ESTATE\s+DEVELOPER)/ui' => 'QP HOLDINGS – 房地产开发商',

			// Address terms & Address string
			'/Tầng\s+L?16,\s*Tòa\s+nhà\s+Vietcombank,\s*Số\s+5\s+(?:Công\s+Trường|Cong\s+Truong)\s*(?:Mê|Me)\s*Linh,\s*(?:P\.|Phường)\s*(?:Sài\s+Gòn|Sai\s+Gon),\s*(?:TP\.\s*Hồ\s+Chí\s+Minh|Thành\s+phố\s+Hồ\s+Chí\s+Minh|Ho\s+Chi\s+Minh\s+City)(?:,\s*(?:Việt\s+Nam|Vietnam))?/ui' => 'L16层，Vietcombank Tower，Công Trường Mê Linh 5号，Sài Gòn坊，胡志明市',
			'/Level\s+L?16,\s*Vietcombank\s+Tower,\s*5\s+Cong\s+Truong\s+Me\s+Linh,\s*Sai\s+Gon\s+Ward,\s*Ho\s+Chi\s+Minh\s+City(?:,\s*Vietnam)?/ui' => 'L16层，Vietcombank Tower，Công Trường Mê Linh 5号，Sài Gòn坊，胡志明市',
			'/Tầng\s+L?16,\s*Tòa\s+nhà\s+Vietcombank/ui' => 'L16层，Vietcombank Tower',

			// Contact Labels in Chinese Characters (Hanzi)
			'/\b(?:Địa\s+chỉ|Address)\s*:/ui' => '地址：',
			'/\b(?:Website|Trang\s+web)\s*:/ui' => '官方网站：',
			'/\b(?:Hotline|Đường\s+dây\s+nóng)\s*:/ui' => '服务热线：',
			'/\b(?:Email|Thư\s+điện\s+tử)\s*:/ui' => '电子邮箱：',
			'/\b(?:Fanpage\s*FB|Fanpage\s*Facebook|Fanpage|Facebook)\s*:/ui' => 'Facebook 官方专页：',

			// Stock ticker
			'/\b(?:Mã\s+chứng\s+khoán|Stock\s+ticker|Stock\s+code)\s*:\s*HKT/ui' => '证券代码：HKT',
			'/\b(?:Mã\s+chứng\s+khoán|Stock\s+ticker|Stock\s+code)\s*:/ui' => '证券代码：',

			// Reference source
			'/\b(?:Nguồn\s+(?:số\s+liệu\s+)?tham\s+khảo|Reference\s+sources?)\s*:/ui' => '参考数据来源：',

			// Editorial credits
			'/\b(?:Tổng\s+hợp\s+và\s+biên\s+soạn\s+(?:nội\s+dung\s*)?|Compiled\s+(?:and|&)\s+edited\s+by)\s*:/ui' => '内容编辑与整理：',
			'/(?:Ban\s+Truyền\s+[tT]hông|Communications\s+Department)\s*[-–—]\s*QP\s+HOLDINGS/ui' => '传媒部 — QP HOLDINGS',
			'/(?:Ban\s+Truyền\s+[tT]hông|Communications\s+Department)\s*[-–—]\s*QP\s+Holdings/ui' => '传媒部 — QP Holdings',
			'/(?:Ban\s+Quan\s+hệ\s+Nhà\s+đầu\s+tư|Investor\s+Relations\s+Department)\s*[-–—]\s*QP\s+HOLDINGS/ui' => '投资者关系部 — QP HOLDINGS',
			'/Ban\s+Quan\s+hệ\s+Nhà\s+đầu\s+tư/ui' => '投资者关系部',
			'/Ban\s+Truyền\s+[tT]hông/ui' => '传媒部',
		);

		$map = ( 'zh' === $target ) ? $replacements_zh : $replacements_en;

		foreach ( $out as $k => $val ) {
			if ( ! is_string( $val ) || '' === trim( $val ) ) {
				continue;
			}
			foreach ( $map as $pattern => $replacement ) {
				$val = preg_replace( $pattern, $replacement, $val ) ?? $val;
			}
			$out[ $k ] = $val;
		}

		return $out;
	}

	/**
	 * @param array<string, string> $out
	 */
	private static function assert_translated_title( array $out, string $target ): void {
		if ( 'zh' !== $target ) {
			return;
		}
		$title = isset( $out['title'] ) ? trim( (string) $out['title'] ) : '';
		if ( '' === $title ) {
			return;
		}
		if ( preg_match( '/\p{Han}/u', $title ) ) {
			return;
		}
		$latin = preg_match_all( '/[A-Za-z]/', $title );
		if ( is_int( $latin ) && $latin >= 12 ) {
			throw new RuntimeException( 'Model did not translate the title into Chinese.' );
		}
	}

	public static function is_transient_error( string $message ): bool {
		return (bool) preg_match( '/cURL error (28|35|52|56)|unexpected eof|Connection reset|timed out|TLS|SSL/i', $message );
	}

	private static function complete( string $provider, string $key, string $model, string $prompt ): string {
		if ( 'gemini' === $provider ) {
			return self::gemini( $key, $model ?: 'gemini-2.0-flash', $prompt );
		}
		if ( 'openai' === $provider ) {
			return self::openai_compat( 'https://api.openai.com/v1/chat/completions', $key, $model ?: 'gpt-4o-mini', $prompt );
		}
		return self::openai_compat( 'https://api.deepseek.com/chat/completions', $key, $model ?: 'deepseek-chat', $prompt );
	}

	private static function openai_compat( string $url, string $key, string $model, string $prompt ): string {
		$body = array(
			'model'       => $model,
			'temperature' => 0.2,
			'messages'    => array(
				array(
					'role'    => 'system',
					'content' => 'You translate news JSON. Reply with JSON only.',
				),
				array(
					'role'    => 'user',
					'content' => $prompt,
				),
			),
		);
		if ( false === strpos( $url, 'deepseek.com' ) ) {
			$body['response_format'] = array( 'type' => 'json_object' );
		}

		$data = self::post_json(
			$url,
			array(
				'Authorization' => 'Bearer ' . $key,
				'Content-Type'  => 'application/json',
			),
			$body
		);
		$text = $data['choices'][0]['message']['content'] ?? '';
		if ( ! is_string( $text ) || '' === trim( $text ) ) {
			throw new RuntimeException( 'Empty model response.' );
		}
		return $text;
	}

	private static function gemini( string $key, string $model, string $prompt ): string {
		$url  = sprintf(
			'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
			rawurlencode( $model ),
			rawurlencode( $key )
		);
		$data = self::post_json(
			$url,
			array( 'Content-Type' => 'application/json' ),
			array(
				'contents'         => array(
					array(
						'parts' => array( array( 'text' => $prompt ) ),
					),
				),
				'generationConfig' => array(
					'temperature'      => 0.2,
					'responseMimeType' => 'application/json',
				),
			)
		);
		$text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
		if ( ! is_string( $text ) || '' === trim( $text ) ) {
			throw new RuntimeException( 'Empty Gemini response.' );
		}
		return $text;
	}

	private static function post_json( string $url, array $headers, array $payload ): array {
		$last = null;
		for ( $attempt = 1; $attempt <= 4; $attempt++ ) {
			$hook = static function ( $handle ) use ( $url ): void {
				if ( ! is_resource( $handle ) && ! ( $handle instanceof CurlHandle ) ) {
					return;
				}
				if ( ! self::is_llm_url( $url ) ) {
					return;
				}
				if ( defined( 'CURL_HTTP_VERSION_1_1' ) ) {
					curl_setopt( $handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
				}
				if ( defined( 'CURLOPT_SSLVERSION' ) && defined( 'CURL_SSLVERSION_TLSv1_2' ) ) {
					curl_setopt( $handle, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2 );
				}
				curl_setopt( $handle, CURLOPT_FORBID_REUSE, true );
				curl_setopt( $handle, CURLOPT_FRESH_CONNECT, true );
				if ( defined( 'CURLOPT_TCP_KEEPALIVE' ) ) {
					curl_setopt( $handle, CURLOPT_TCP_KEEPALIVE, 1 );
				}
			};
			add_action( 'http_api_curl', $hook, 20, 1 );

			$response = wp_remote_post(
				$url,
				array(
					'timeout'     => 120,
					'redirection' => 0,
					'httpversion' => '1.1',
					'sslverify'   => true,
					'headers'     => $headers + array( 'Connection' => 'close' ),
					'body'        => wp_json_encode( $payload ),
				)
			);
			remove_action( 'http_api_curl', $hook, 20 );

			if ( is_wp_error( $response ) ) {
				$last = new RuntimeException( $response->get_error_message() );
				if ( $attempt < 4 && self::is_transient_error( $last->getMessage() ) ) {
					sleep( $attempt * 2 );
					continue;
				}
				throw $last;
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = (string) wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );
			if ( in_array( $code, array( 408, 409, 425, 429, 500, 502, 503, 504 ), true ) && $attempt < 4 ) {
				sleep( $attempt * 2 );
				$last = new RuntimeException( 'HTTP ' . $code . ' ' . substr( $body, 0, 300 ) );
				continue;
			}
			if ( $code >= 300 ) {
				$msg = is_array( $data ) ? wp_json_encode( $data['error'] ?? $data ) : $body;
				throw new RuntimeException( 'HTTP ' . $code . ' ' . $msg );
			}
			if ( ! is_array( $data ) ) {
				throw new RuntimeException( 'Invalid JSON from provider.' );
			}
			return $data;
		}

		throw $last instanceof Throwable ? $last : new RuntimeException( 'LLM request failed.' );
	}

	private static function is_llm_url( string $url ): bool {
		$host = (string) wp_parse_url( $url, PHP_URL_HOST );
		return (bool) preg_match( '/(^|\.)(deepseek\.com|openai\.com|googleapis\.com)$/i', $host );
	}

	private static function parse_json( string $text ): array {
		$text = trim( $text );
		$text = preg_replace( '/^```(?:json)?\s*|\s*```$/', '', $text );
		$data = json_decode( (string) $text, true );
		if ( ! is_array( $data ) ) {
			throw new RuntimeException( 'Model did not return JSON.' );
		}
		return $data;
	}
}
