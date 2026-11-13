<?php
/**
 * Picks the translation engine (Gemini by default, Google Cloud Translation as
 * the alternative) and returns translated fields in one shape for both.
 *
 * Before anything is sent, parts that must not be translated are replaced by
 * placeholders ({{QPAT_1}}, {{QPAT_2}}...) and put back afterwards:
 *   - images, video, iframes, embeds, scripts, shortcode tags
 *   - content the editor excluded: class="notranslate", translate="no",
 *     or <!--notranslate--> ... <!--/notranslate-->
 *   - names from the "Không dịch" list (brand, project names)
 *   - glossary terms (replaced by the preferred EN / ZH wording)
 */

defined( 'ABSPATH' ) || exit;

/** Temporary failure (rate limit, network): the job should be retried later. */
class QPAT_Transient_Exception extends RuntimeException {}

final class QPAT_Translator {
	/** Placeholder written into the text sent to the engine, e.g. {{QPAT_3}}. */
	const HOLD_FORMAT = '{{QPAT_%d}}';

	/**
	 * @param array<string, string> $fields Field name => Vietnamese text (HTML for content / seg_*).
	 * @param string                $engine_used Set to the engine that produced the result.
	 * @return array<string, string>
	 */
	public static function translate( array $fields, string $target, string &$engine_used = '' ): array {
		$s      = QPAT_Settings::get();
		$engine = $s['engine'];

		try {
			$out         = self::run( $engine, $fields, $target );
			$engine_used = $engine;
		} catch ( QPAT_Quota_Exception $e ) {
			throw $e;
		} catch ( Throwable $e ) {
			$can_fallback = 'gemini' === $engine
				&& ! empty( $s['fallback_google'] )
				&& '' !== QPAT_Settings::api_key();
			if ( ! $can_fallback ) {
				throw $e;
			}
			try {
				$out         = self::run( 'google', $fields, $target );
				$engine_used = 'google';
			} catch ( QPAT_Quota_Exception $e2 ) {
				throw $e2;
			} catch ( Throwable $e2 ) {
				throw new RuntimeException( 'Gemini lỗi: ' . $e->getMessage() . ' | Google dự phòng cũng lỗi: ' . $e2->getMessage(), 0, $e2 );
			}
		}

		return QPAT_Glossary::normalize( $out, $target );
	}

	public static function is_html_field( string $key, string $value ): bool {
		return 'content' === $key || 0 === strpos( $key, 'seg_' ) || (bool) preg_match( '#<[a-z!/][^>]*>#i', $value );
	}

	/**
	 * @param array<string, string> $fields
	 * @return array<string, string>
	 */
	private static function run( string $engine, array $fields, string $target ): array {
		$kept = array();
		$sent = array();
		foreach ( $fields as $k => $v ) {
			$v          = (string) $v;
			$held       = self::is_html_field( (string) $k, $v ) ? self::hold_media( $v, $kept ) : $v;
			$sent[ $k ] = self::hold_terms( $held, $target, $kept );
		}

		$out = 'google' === $engine ? self::google( $sent, $target ) : self::gemini( $sent, $target );

		foreach ( $out as $k => $v ) {
			$out[ $k ] = self::release_media( (string) $v, (string) ( $sent[ $k ] ?? '' ), $kept );
		}
		return $out;
	}

	/**
	 * @param array<string, string> $sent
	 * @return array<string, string>
	 */
	private static function gemini( array $sent, string $target ): array {
		$out   = QPAT_Gemini_Client::translate( $sent, $target );
		$chars = 0;
		foreach ( $sent as $v ) {
			$chars += QPAT_Google_Client::char_count( (string) $v );
		}
		QPAT_Usage::add( $target, $chars, 'gemini' );
		return $out;
	}

	/**
	 * Google translates each field as a separate segment: HTML fields as HTML,
	 * the rest as plain text so line breaks in textareas survive. Placeholders
	 * are wrapped in translate="no" so Google leaves them alone.
	 *
	 * @param array<string, string> $fields
	 * @return array<string, string>
	 */
	private static function google( array $fields, string $target ): array {
		$html     = array();
		$text     = array();
		$as_html  = array(); // Plain fields converted to HTML because they hold placeholders.
		foreach ( $fields as $k => $v ) {
			$k = (string) $k;
			if ( self::is_html_field( $k, $v ) ) {
				$html[ $k ] = self::protect_html( $v );
			} elseif ( false !== strpos( $v, '{{QPAT_' ) ) {
				$html[ $k ]  = self::protect_html( nl2br( esc_html( $v ), false ), false );
				$as_html[ $k ] = true;
			} else {
				$text[ $k ] = $v;
			}
		}

		$out = array();
		if ( $text ) {
			$out += QPAT_Google_Client::translate( $text, $target, 'vi', 'text' );
		}
		if ( $html ) {
			foreach ( QPAT_Google_Client::translate( $html, $target, 'vi', 'html' ) as $k => $v ) {
				$v = self::restore_html( $v );
				if ( isset( $as_html[ $k ] ) ) {
					$v = (string) preg_replace( '#<br\s*/?>\n?#i', "\n", $v );
					$v = html_entity_decode( $v, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
				}
				$out[ $k ] = $v;
			}
		}

		// Original field order.
		$ordered = array();
		foreach ( $fields as $k => $unused ) {
			$ordered[ $k ] = $out[ $k ] ?? '';
		}
		return $ordered;
	}

	/**
	 * Classic-editor content keeps paragraphs as blank lines / line breaks,
	 * which HTML mode would collapse: convert them to <p>/<br> first. Shortcodes
	 * and placeholders are wrapped so Google leaves them untouched.
	 */
	public static function protect_html( string $html, bool $autop = true ): string {
		if ( '' === trim( $html ) ) {
			return $html;
		}
		if ( $autop && false !== strpos( $html, "\n" ) ) {
			$html = wpautop( $html );
		}
		return (string) preg_replace_callback(
			'/\{\{QPAT_\d+\}\}|\[(\[?)([A-Za-z0-9_\-\/]+)(?![\w-])[^\]]*\](?:[^\[]*\[\/\2\])?(\]?)/',
			static function ( array $m ): string {
				return '<span class="notranslate" translate="no" data-qpat-keep="1">' . $m[0] . '</span>';
			},
			$html
		);
	}

	public static function restore_html( string $html ): string {
		return (string) preg_replace(
			'#<span\b[^>]*\bdata-qpat-keep=["\']?1["\']?[^>]*>(.*?)</span>#s',
			'$1',
			$html
		);
	}

	/**
	 * Replace media, embeds, excluded blocks and shortcode tags with
	 * placeholders. Text inside shortcodes such as [caption] stays translatable.
	 *
	 * @param array<int, string> $kept Originals by placeholder number (shared across fields).
	 */
	public static function hold_media( string $html, array &$kept ): string {
		if ( '' === $html || ( false === strpos( $html, '<' ) && false === strpos( $html, '[' ) ) ) {
			return $html;
		}
		$patterns = array(
			// Blocks the editor marked as "do not translate".
			'#<!--\s*notranslate\s*-->.*?<!--\s*/notranslate\s*-->#is',
			'#<(span|div|p|section|blockquote|strong|em|code)\b[^>]*(?:\bclass=["\'][^"\']*\bnotranslate\b[^"\']*["\']|\btranslate=["\']no["\'])[^>]*>.*?</\1>#is',
			// Linked image: keep the link together with the image.
			'#<a\b[^>]*>\s*<img\b[^>]*>\s*</a>#i',
			'#<(iframe|video|audio|object|script|style|svg)\b.*?</\1>#is',
			'#<(?:img|embed|source|track)\b[^>]*>#i',
			// Shortcode opening / closing tags only.
			'#\[/?[A-Za-z][A-Za-z0-9_\-]*(?:\s[^\]]*)?\]#',
		);
		foreach ( $patterns as $re ) {
			$html = (string) preg_replace_callback(
				$re,
				static function ( array $m ) use ( &$kept ): string {
					return self::hold( $m[0], $kept );
				},
				$html
			);
		}
		return $html;
	}

	/**
	 * Names that must stay as written, and glossary terms (which become the
	 * preferred EN / ZH wording). Only text outside HTML tags is touched.
	 *
	 * @param array<int, string> $kept
	 */
	public static function hold_terms( string $text, string $target, array &$kept ): string {
		$terms = array();
		foreach ( QPAT_Settings::glossary() as $row ) {
			$to = trim( (string) ( $row[ $target ] ?? '' ) );
			if ( '' !== $to ) {
				$terms[ $row['vi'] ] = $to;
			}
		}
		foreach ( QPAT_Settings::no_translate_terms() as $term ) {
			if ( ! isset( $terms[ $term ] ) ) {
				$terms[ $term ] = null; // Keep the matched text exactly as written.
			}
		}
		if ( ! $terms || '' === trim( $text ) ) {
			return $text;
		}
		// Longest first so "QP Green Park" wins over "QP".
		uksort( $terms, static fn( $a, $b ) => mb_strlen( (string) $b ) <=> mb_strlen( (string) $a ) );

		$alts = array();
		foreach ( array_keys( $terms ) as $term ) {
			$alts[] = preg_quote( (string) $term, '/' );
		}
		$re    = '/(?<![\p{L}\p{N}])(' . implode( '|', $alts ) . ')(?![\p{L}\p{N}])/iu';
		$lower = array();
		foreach ( $terms as $vi => $to ) {
			$lower[ mb_strtolower( (string) $vi ) ] = $to;
		}

		$parts = preg_split( '/(<[^>]*>|\{\{QPAT_\d+\}\})/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( ! is_array( $parts ) ) {
			return $text;
		}
		foreach ( $parts as $i => $part ) {
			if ( '' === $part || '<' === $part[0] || 0 === strpos( $part, '{{QPAT_' ) ) {
				continue;
			}
			$parts[ $i ] = (string) preg_replace_callback(
				$re,
				static function ( array $m ) use ( $lower, &$kept ): string {
					$to = $lower[ mb_strtolower( $m[1] ) ] ?? null;
					return self::hold( null === $to ? $m[1] : $to, $kept );
				},
				$part
			);
		}
		return implode( '', $parts );
	}

	/** @param array<int, string> $kept */
	private static function hold( string $original, array &$kept ): string {
		$n          = count( $kept ) + 1;
		$kept[ $n ] = $original;
		return sprintf( self::HOLD_FORMAT, $n );
	}

	/**
	 * Put the originals back. A placeholder the engine lost is re-inserted at
	 * the paragraph break nearest to where it was in the text that was sent,
	 * so an image is never silently removed.
	 *
	 * @param array<int, string> $kept
	 */
	public static function release_media( string $translated, string $sent, array $kept ): string {
		if ( ! $kept || false === strpos( $sent, '{{QPAT_' ) ) {
			return $translated;
		}
		preg_match_all( '/\{\{QPAT_(\d+)\}\}/', $sent, $m, PREG_OFFSET_CAPTURE );
		$sent_len = max( 1, strlen( $sent ) );

		// Tolerate small changes the model makes to the placeholder ({{ QPAT_1 }}).
		$translated = (string) preg_replace( '/\{\{\s*QPAT_(\d+)\s*\}\}/', '{{QPAT_$1}}', $translated );

		foreach ( $m[1] as $i => $match ) {
			$token = sprintf( self::HOLD_FORMAT, (int) $match[0] );
			if ( false !== strpos( $translated, $token ) ) {
				continue;
			}
			$ratio      = $m[0][ $i ][1] / $sent_len;
			$translated = self::insert_at_break( $translated, $token, $ratio );
		}

		// Placeholders can nest (a glossary term inside an excluded block): resolve until none are left.
		for ( $pass = 0; $pass < 3 && false !== strpos( $translated, '{{QPAT_' ); $pass++ ) {
			$translated = (string) preg_replace_callback(
				'/\{\{QPAT_(\d+)\}\}/',
				static function ( array $x ) use ( $kept ): string {
					return $kept[ (int) $x[1] ] ?? '';
				},
				$translated
			);
		}
		return $translated;
	}

	private static function insert_at_break( string $text, string $token, float $ratio ): string {
		$len    = strlen( $text );
		$target = (int) round( $ratio * $len );
		if ( $ratio <= 0.0 ) {
			return $token . "\n\n" . $text;
		}
		if ( $ratio >= 0.98 || 0 === $len ) {
			return rtrim( $text ) . "\n\n" . $token;
		}
		$best = null;
		if ( preg_match_all( '#\n|</p>|</h[1-6]>|</li>|<br\s*/?>#i', $text, $b, PREG_OFFSET_CAPTURE ) ) {
			foreach ( $b[0] as $hit ) {
				$pos = $hit[1] + strlen( $hit[0] );
				if ( null === $best || abs( $pos - $target ) < abs( $best - $target ) ) {
					$best = $pos;
				}
			}
		}
		if ( null === $best ) {
			return rtrim( $text ) . "\n\n" . $token;
		}
		return substr( $text, 0, $best ) . "\n" . $token . "\n" . substr( $text, $best );
	}
}
