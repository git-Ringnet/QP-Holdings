<?php
/**
 * Splits post content into paragraphs/blocks ("segments") so that only new or
 * changed parts are sent for translation, and aligns two lists of segments.
 */

defined( 'ABSPATH' ) || exit;

final class QPAT_Segments {
	/** Block containers: never split inside them. */
	const CONTAINERS = 'div|ul|ol|table|blockquote|figure|pre|section|dl|video|audio|iframe|object|script|style';

	/** Blocks that end a segment when they close at top level. */
	const BLOCKS = 'p|h[1-6]';

	/**
	 * Top-level paragraphs / blocks of an HTML (classic editor) document.
	 *
	 * @return string[]
	 */
	public static function split( string $html ): array {
		$html = str_replace( array( "\r\n", "\r" ), "\n", $html );
		if ( '' === trim( $html ) ) {
			return array();
		}
		$re    = '#(\n[ \t]*\n\s*|</?(?:' . self::CONTAINERS . '|' . self::BLOCKS . ')\b[^>]*>)#i';
		$parts = preg_split( $re, $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
		if ( ! is_array( $parts ) ) {
			return array( trim( $html ) );
		}

		$out   = array();
		$buf   = '';
		$depth = 0;
		$flush = static function () use ( &$buf, &$out ): void {
			$seg = trim( $buf );
			if ( '' !== $seg ) {
				$out[] = $seg;
			}
			$buf = '';
		};

		foreach ( $parts as $part ) {
			if ( preg_match( '#^\n[ \t]*\n#', $part ) && '' === trim( $part ) ) {
				if ( 0 === $depth ) {
					$flush();
				} else {
					$buf .= $part;
				}
				continue;
			}
			if ( preg_match( '#^<(/?)(' . self::CONTAINERS . ')\b#i', $part, $m ) ) {
				if ( '' === $m[1] ) {
					if ( 0 === $depth && '' !== trim( $buf ) && ! self::opens_inline( $buf ) ) {
						$flush();
					}
					$buf .= $part;
					if ( ! preg_match( '#/>$#', $part ) ) {
						++$depth;
					}
				} else {
					$buf  .= $part;
					$depth = max( 0, $depth - 1 );
					if ( 0 === $depth ) {
						$flush();
					}
				}
				continue;
			}
			if ( preg_match( '#^<(/?)(?:' . self::BLOCKS . ')\b#i', $part, $m ) ) {
				if ( 0 === $depth && '' === $m[1] && '' !== trim( $buf ) ) {
					$flush();
				}
				$buf .= $part;
				if ( 0 === $depth && '/' === $m[1] ) {
					$flush();
				}
				continue;
			}
			$buf .= $part;
		}
		$flush();
		return $out;
	}

	/** Buffer ends with an unclosed inline wrapper such as "<a href=..>". */
	private static function opens_inline( string $buf ): bool {
		return (bool) preg_match( '#<(a|span|strong|em|b|i)\b[^>]*>\s*$#i', $buf );
	}

	/** @param string[] $segments */
	public static function join( array $segments ): string {
		return implode( "\n\n", array_values( array_filter( $segments, static fn( $s ) => '' !== trim( (string) $s ) ) ) );
	}

	public static function hash( string $text ): string {
		return substr( hash( 'sha256', str_replace( array( "\r\n", "\r" ), "\n", trim( $text ) ) ), 0, 20 );
	}

	/**
	 * Comparable form: the editor (TinyMCE) rewrites markup on save — <p>,
	 * <br>, &nbsp;, "/>" — without the text really changing.
	 */
	public static function norm( string $html ): string {
		$s = str_replace( array( "\r\n", "\r" ), "\n", $html );
		$s = (string) preg_replace( '#</?p\b[^>]*>#i', ' ', $s );
		$s = (string) preg_replace( '#<br\s*/?>#i', ' ', $s );
		$s = (string) preg_replace( '#\s*/>#', '>', $s );
		$s = html_entity_decode( $s, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$s = str_replace( "\xC2\xA0", ' ', $s );
		$s = (string) preg_replace( '#\s+#u', ' ', $s );
		$s = (string) preg_replace( '#\s*(<[^>]+>)\s*#', '$1', $s );
		return trim( $s );
	}

	/** Does the text contain anything a translator would change (letters)? */
	public static function translatable( string $html ): bool {
		$s = (string) preg_replace( '/\{\{QPAT_\d+\}\}/', ' ', $html );
		$s = (string) preg_replace( '#<!--.*?-->#s', ' ', $s );
		$s = (string) preg_replace( '#<(script|style|iframe|video|audio|object)\b.*?</\1>#is', ' ', $s );
		$s = (string) preg_replace( '#\[/?[A-Za-z][^\]]*\]#', ' ', $s );
		$s = wp_strip_all_tags( $s );
		$s = html_entity_decode( $s, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return (bool) preg_match( '/\p{L}/u', $s );
	}

	/**
	 * Longest common subsequence of two lists, as matched index pairs.
	 *
	 * @param string[] $a
	 * @param string[] $b
	 * @return array<int, array{0:int,1:int}>
	 */
	public static function lcs( array $a, array $b ): array {
		$a = array_values( $a );
		$b = array_values( $b );
		$n = count( $a );
		$m = count( $b );
		if ( 0 === $n || 0 === $m ) {
			return array();
		}
		// Very long documents: match by position only (keeps memory bounded).
		if ( $n * $m > 400000 ) {
			$pairs = array();
			for ( $i = 0, $len = min( $n, $m ); $i < $len; $i++ ) {
				if ( $a[ $i ] === $b[ $i ] ) {
					$pairs[] = array( $i, $i );
				}
			}
			return $pairs;
		}
		$t = array_fill( 0, $n + 1, array_fill( 0, $m + 1, 0 ) );
		for ( $i = $n - 1; $i >= 0; $i-- ) {
			for ( $j = $m - 1; $j >= 0; $j-- ) {
				$t[ $i ][ $j ] = $a[ $i ] === $b[ $j ] ? $t[ $i + 1 ][ $j + 1 ] + 1 : max( $t[ $i + 1 ][ $j ], $t[ $i ][ $j + 1 ] );
			}
		}
		$pairs = array();
		$i     = 0;
		$j     = 0;
		while ( $i < $n && $j < $m ) {
			if ( $a[ $i ] === $b[ $j ] ) {
				$pairs[] = array( $i, $j );
				++$i;
				++$j;
			} elseif ( $t[ $i + 1 ][ $j ] >= $t[ $i ][ $j + 1 ] ) {
				++$i;
			} else {
				++$j;
			}
		}
		return $pairs;
	}

	/**
	 * Walk two lists as alternating matched pairs and unmatched gaps.
	 *
	 * @param string[] $a
	 * @param string[] $b
	 * @return array<int, array{type:string, a:int[], b:int[]}>
	 *         type 'same' (one index each side) or 'gap' (any number, either side may be empty)
	 */
	public static function diff( array $a, array $b ): array {
		$pairs   = self::lcs( $a, $b );
		$pairs[] = array( count( $a ), count( $b ) ); // Sentinel.
		$ops     = array();
		$pi      = -1;
		$pj      = -1;
		foreach ( $pairs as $k => $pair ) {
			list( $i, $j ) = $pair;
			$ga = $i > $pi + 1 ? range( $pi + 1, $i - 1 ) : array();
			$gb = $j > $pj + 1 ? range( $pj + 1, $j - 1 ) : array();
			if ( $ga || $gb ) {
				$ops[] = array( 'type' => 'gap', 'a' => $ga, 'b' => $gb );
			}
			if ( $k < count( $pairs ) - 1 ) {
				$ops[] = array( 'type' => 'same', 'a' => array( $i ), 'b' => array( $j ) );
			}
			$pi = $i;
			$pj = $j;
		}
		return $ops;
	}
}
