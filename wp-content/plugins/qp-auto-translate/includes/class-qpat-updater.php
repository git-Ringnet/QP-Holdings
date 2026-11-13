<?php
/**
 * Builds / updates one EN or ZH copy paragraph by paragraph.
 *
 * Each copy remembers, for every paragraph and field, a fingerprint of the
 * Vietnamese text and the machine translation it produced (meta _qpat_tm).
 * On update:
 *   - unchanged Vietnamese paragraphs are not sent again (no characters used);
 *   - new / changed paragraphs are translated;
 *   - a paragraph edited by hand in the copy is kept as long as its
 *     Vietnamese original is unchanged;
 *   - if both changed, the hand-edited text stays and the paragraph is marked
 *     "cần duyệt" with the new machine translation as a suggestion.
 */

defined( 'ABSPATH' ) || exit;

final class QPAT_Updater {
	const META_TM     = '_qpat_tm';
	const META_REVIEW = '_qpat_review';
	const META_STATE  = '_qpat_state';
	const META_REV    = '_qpat_source_revision';

	/** Copy states. */
	const STATES = array(
		'auto'     => 'Dịch tự động',
		'edited'   => 'Đã sửa tay',
		'approved' => 'Đã duyệt',
		'review'   => 'Cần duyệt',
		'outdated' => 'Cần cập nhật',
		'legacy'   => 'Bản dịch cũ',
	);

	/**
	 * @return array{status:string, target_id:int, reviews:int, sent:int, engine:string, message:string}
	 *         status: done | review | skipped
	 */
	public static function run( WP_Post $source, string $lang, bool $full ): array {
		$copy_id = QPAT_Sync::existing_translation( $source, $lang );
		$map     = $copy_id ? self::map( $copy_id ) : null;

		// A copy made by the old plugin (or before this version) has no paragraph
		// memory: never overwrite it automatically — it may contain manual work.
		if ( $copy_id && ! $full && null === $map ) {
			self::set_state( $copy_id, 'outdated' );
			return array(
				'status'    => 'skipped',
				'target_id' => $copy_id,
				'reviews'   => 0,
				'sent'      => 0,
				'engine'    => '',
				'message'   => 'Bản dịch có từ trước, chưa có dữ liệu từng đoạn nên không tự cập nhật (tránh mất phần đã sửa tay). Bấm “Dịch lại toàn bộ” để cập nhật.',
			);
		}
		if ( $full || ! $copy_id ) {
			$map = array( 'segs' => array(), 'fields' => array() );
		}

		$src_fields  = QPAT_Sync::collect_fields( $source );
		$src_segs    = QPAT_Segments::split( (string) $src_fields['content'] );
		unset( $src_fields['content'] );
		$skip_fields = QPAT_Settings::skip_fields();

		$cur_segs   = array();
		$cur_fields = array();
		if ( $copy_id && ! $full ) {
			$copy = get_post( $copy_id );
			if ( $copy ) {
				$cf         = QPAT_Sync::collect_fields( $copy );
				$cur_segs   = QPAT_Segments::split( (string) $cf['content'] );
				unset( $cf['content'] );
				$cur_fields = $cf;
			}
		}

		// ---- A. Which paragraphs of the copy were edited by hand?
		list( $cur_by_old, $edited_old ) = self::align_copy( $map['segs'], $cur_segs );

		// ---- B. What changed in the Vietnamese text since the last translation?
		$old_hashes = array_map( static fn( $e ) => (string) $e['h'], $map['segs'] );
		$new_hashes = array_map( array( 'QPAT_Segments', 'hash' ), $src_segs );

		$result  = array_fill( 0, count( $src_segs ), '' );
		$tm_out  = array_fill( 0, count( $src_segs ), '' );
		$need    = array(); // seg index => true
		$reviews = array(); // pending review items (filled after translation)

		foreach ( QPAT_Segments::diff( $old_hashes, $new_hashes ) as $op ) {
			if ( 'same' === $op['type'] ) {
				$i            = $op['a'][0];
				$j            = $op['b'][0];
				$result[ $j ] = (string) ( $cur_by_old[ $i ] ?? $map['segs'][ $i ]['out'] );
				$tm_out[ $j ] = (string) $map['segs'][ $i ]['out'];
				continue;
			}
			if ( ! $op['b'] ) {
				continue; // Paragraphs removed from the Vietnamese post.
			}
			$hand = array();
			foreach ( $op['a'] as $i ) {
				if ( ! empty( $edited_old[ $i ] ) && '' !== trim( (string) ( $cur_by_old[ $i ] ?? '' ) ) ) {
					$hand[] = (string) $cur_by_old[ $i ];
				}
			}
			foreach ( $op['b'] as $j ) {
				$need[ $j ] = true;
			}
			if ( $hand ) {
				$reviews[] = array(
					'type' => 'seg',
					'segs' => $op['b'],
					'kept' => QPAT_Segments::join( $hand ),
				);
			}
		}

		// ---- Fields (title, excerpt, meta).
		$field_out    = array();
		$field_tm     = array();
		$field_need   = array();
		$field_review = array();
		foreach ( $src_fields as $key => $value ) {
			$value = (string) $value;
			$h     = QPAT_Segments::hash( $value );
			$old   = $map['fields'][ $key ] ?? null;
			$cur   = array_key_exists( $key, $cur_fields ) ? (string) $cur_fields[ $key ] : null;

			if ( in_array( $key, $skip_fields, true ) || '' === trim( $value ) ) {
				$field_out[ $key ] = $value;
				$field_tm[ $key ]  = array( 'h' => $h, 'out' => $value );
				continue;
			}
			if ( $old && $old['h'] === $h ) {
				$field_out[ $key ] = null !== $cur ? $cur : (string) $old['out'];
				$field_tm[ $key ]  = $old;
				continue;
			}
			$field_need[ $key ] = $value;
			if ( $old && null !== $cur && '' !== trim( $cur ) && QPAT_Segments::norm( $cur ) !== QPAT_Segments::norm( (string) $old['out'] ) ) {
				$field_review[ $key ] = $cur;
			}
		}

		// ---- Translate only what is needed.
		$send = array();
		foreach ( array_keys( $need ) as $j ) {
			if ( QPAT_Segments::translatable( $src_segs[ $j ] ) ) {
				$send[ 'seg_' . $j ] = $src_segs[ $j ];
			} else {
				$tm_out[ $j ] = $src_segs[ $j ]; // Image / number only: copied as is.
				$result[ $j ] = $src_segs[ $j ];
			}
		}
		$send += $field_need;

		$engine     = '';
		$translated = array();
		$sent       = 0;
		if ( $send ) {
			foreach ( $send as $v ) {
				$sent += QPAT_Google_Client::char_count( (string) $v );
			}
			$translated = QPAT_Translator::translate( $send, $lang, $engine );
		}

		foreach ( array_keys( $need ) as $j ) {
			if ( isset( $send[ 'seg_' . $j ] ) ) {
				$tm_out[ $j ] = (string) ( $translated[ 'seg_' . $j ] ?? '' );
				$result[ $j ] = $tm_out[ $j ];
			}
		}
		foreach ( $field_need as $key => $unused ) {
			$machine          = (string) ( $translated[ $key ] ?? '' );
			$field_tm[ $key ] = array( 'h' => QPAT_Segments::hash( (string) $src_fields[ $key ] ), 'out' => $machine );
			$field_out[ $key ] = isset( $field_review[ $key ] ) ? $field_review[ $key ] : $machine;
		}

		// ---- Hand-edited paragraphs whose original changed: keep the hand text, ask for review.
		$review_items = array();
		foreach ( $reviews as $r ) {
			$suggest = array();
			$src     = array();
			foreach ( $r['segs'] as $n => $j ) {
				$suggest[]    = $tm_out[ $j ];
				$src[]        = $src_segs[ $j ];
				$result[ $j ] = 0 === $n ? $r['kept'] : '';
			}
			$review_items[] = array(
				'type'    => 'seg',
				'src'     => QPAT_Segments::join( $src ),
				'kept'    => $r['kept'],
				'suggest' => QPAT_Segments::join( $suggest ),
			);
		}
		foreach ( $field_review as $key => $kept ) {
			$review_items[] = array(
				'type'    => 'field',
				'key'     => $key,
				'src'     => (string) $src_fields[ $key ],
				'kept'    => $kept,
				'suggest' => (string) $field_tm[ $key ]['out'],
			);
		}

		$final            = $field_out;
		$final['content'] = QPAT_Segments::join( $result );

		$prev_engine = $copy_id ? (string) get_post_meta( $copy_id, QPAT_Sync::META_ENGINE, true ) : '';
		$target_id   = QPAT_Sync::upsert_translation( $source, $lang, $final, '' !== $engine ? $engine : $prev_engine );

		$tm = array(
			'v'      => 1,
			'segs'   => array(),
			'fields' => $field_tm,
		);
		foreach ( $src_segs as $j => $seg ) {
			$tm['segs'][] = array( 'h' => $new_hashes[ $j ], 'out' => $tm_out[ $j ] );
		}
		update_post_meta( $target_id, self::META_TM, wp_slash( $tm ) );
		update_post_meta( $target_id, self::META_REV, self::source_revision( $source ) );

		// Older review items still relevant (their kept text is still in the copy).
		$open = $full ? array() : self::still_open( $target_id, (string) $final['content'], $final );
		$all  = array_merge( $open, $review_items );
		if ( $all ) {
			update_post_meta( $target_id, self::META_REVIEW, wp_slash( $all ) );
		} else {
			delete_post_meta( $target_id, self::META_REVIEW );
		}

		$prev_state = $copy_id ? (string) get_post_meta( $copy_id, self::META_STATE, true ) : '';
		if ( $all ) {
			$state = 'review';
		} elseif ( ! $full && $copy_id && self::has_hand_edits( $target_id ) ) {
			$state = 'approved' === $prev_state ? 'approved' : 'edited';
		} else {
			$state = 'auto';
		}
		self::set_state( $target_id, $state );

		return array(
			'status'    => $all ? 'review' : 'done',
			'target_id' => $target_id,
			'reviews'   => count( $all ),
			'sent'      => $sent,
			'engine'    => $engine,
			'message'   => $send
				? sprintf( 'Đã dịch %d phần (%s ký tự)%s.', count( $send ), number_format_i18n( $sent ), $all ? '; ' . count( $all ) . ' phần cần duyệt' : '' )
				: 'Không có phần nào thay đổi, không gửi dịch.' . ( $all ? ' ' . count( $all ) . ' phần cần duyệt.' : '' ),
		);
	}

	/**
	 * Pair the copy's current paragraphs with the machine output they came from.
	 *
	 * @param array<int, array{h:string, out:string}> $old
	 * @param string[]                                 $cur
	 * @return array{0: array<int, string>, 1: array<int, bool>} current text and "edited" flag per old index
	 */
	private static function align_copy( array $old, array $cur ): array {
		$text   = array();
		$edited = array();
		if ( ! $old ) {
			return array( $text, $edited );
		}
		$a = array_map( static fn( $e ) => QPAT_Segments::norm( (string) $e['out'] ), $old );
		$b = array_map( array( 'QPAT_Segments', 'norm' ), $cur );

		$orphans = array(); // Paragraphs added in the copy with no counterpart.
		$last    = -1;
		foreach ( QPAT_Segments::diff( $a, $b ) as $op ) {
			if ( 'same' === $op['type'] ) {
				$i            = $op['a'][0];
				$text[ $i ]   = $cur[ $op['b'][0] ];
				$edited[ $i ] = false;
				$last         = $i;
				if ( $orphans ) {
					$text[ $i ]   = QPAT_Segments::join( array_merge( $orphans, array( $text[ $i ] ) ) );
					$edited[ $i ] = true;
					$orphans      = array();
				}
				continue;
			}
			$ga = $op['a'];
			$gb = array_map( static fn( $k ) => $cur[ $k ], $op['b'] );
			if ( ! $ga ) {
				if ( $last >= 0 ) {
					$text[ $last ]   = QPAT_Segments::join( array_merge( array( $text[ $last ] ), $gb ) );
					$edited[ $last ] = true;
				} else {
					$orphans = array_merge( $orphans, $gb );
				}
				continue;
			}
			if ( count( $ga ) === count( $gb ) ) {
				foreach ( $ga as $n => $i ) {
					$text[ $i ]   = $gb[ $n ];
					$edited[ $i ] = true;
				}
			} else {
				foreach ( $ga as $n => $i ) {
					$text[ $i ]   = 0 === $n ? QPAT_Segments::join( $gb ) : '';
					$edited[ $i ] = true;
				}
			}
			$last = end( $ga );
		}
		return array( $text, $edited );
	}

	/** @return array{segs: array<int, array{h:string,out:string}>, fields: array<string, array{h:string,out:string}>}|null */
	public static function map( int $copy_id ): ?array {
		$tm = get_post_meta( $copy_id, self::META_TM, true );
		if ( ! is_array( $tm ) || ! isset( $tm['segs'], $tm['fields'] ) ) {
			return null;
		}
		return array(
			'segs'   => array_values( (array) $tm['segs'] ),
			'fields' => (array) $tm['fields'],
		);
	}

	/** Does the copy differ anywhere from the machine translation it was given? */
	public static function has_hand_edits( int $copy_id ): bool {
		$map  = self::map( $copy_id );
		$copy = get_post( $copy_id );
		if ( null === $map || ! $copy ) {
			return false;
		}
		$cf  = QPAT_Sync::collect_fields( $copy );
		$cur = array_map( array( 'QPAT_Segments', 'norm' ), QPAT_Segments::split( (string) $cf['content'] ) );
		$out = array_values(
			array_filter(
				array_map( static fn( $e ) => QPAT_Segments::norm( (string) $e['out'] ), $map['segs'] ),
				static fn( $s ) => '' !== $s
			)
		);
		if ( $cur !== $out ) {
			return true;
		}
		foreach ( $map['fields'] as $key => $entry ) {
			if ( isset( $cf[ $key ] ) && QPAT_Segments::norm( (string) $cf[ $key ] ) !== QPAT_Segments::norm( (string) $entry['out'] ) ) {
				return true;
			}
		}
		return false;
	}

	/** @return array<int, array<string, string>> */
	public static function reviews( int $copy_id ): array {
		$r = get_post_meta( $copy_id, self::META_REVIEW, true );
		return is_array( $r ) ? array_values( $r ) : array();
	}

	/**
	 * @param array<string, string> $fields
	 * @return array<int, array<string, string>>
	 */
	private static function still_open( int $copy_id, string $content, array $fields ): array {
		$keep = array();
		$segs = array_map( array( 'QPAT_Segments', 'norm' ), QPAT_Segments::split( $content ) );
		$all  = QPAT_Segments::norm( implode( ' ', $segs ) );
		foreach ( self::reviews( $copy_id ) as $item ) {
			$kept = QPAT_Segments::norm( (string) ( $item['kept'] ?? '' ) );
			if ( 'field' === ( $item['type'] ?? '' ) ) {
				$key = (string) ( $item['key'] ?? '' );
				if ( isset( $fields[ $key ] ) && QPAT_Segments::norm( (string) $fields[ $key ] ) === $kept ) {
					$keep[] = $item;
				}
			} elseif ( '' !== $kept && false !== strpos( $all, $kept ) ) {
				$keep[] = $item;
			}
		}
		return $keep;
	}

	/**
	 * Resolve one review item: 'accept' puts the new machine translation in
	 * place of the hand-edited text, 'keep' confirms the hand-edited text.
	 */
	public static function resolve( int $copy_id, int $index, string $action ): bool {
		$items = self::reviews( $copy_id );
		if ( ! isset( $items[ $index ] ) ) {
			return false;
		}
		$item = $items[ $index ];
		if ( 'accept' === $action ) {
			$copy = get_post( $copy_id );
			if ( ! $copy ) {
				return false;
			}
			if ( 'field' === $item['type'] ) {
				self::write_field( $copy, (string) $item['key'], (string) $item['suggest'] );
			} else {
				$segs   = QPAT_Segments::split( (string) $copy->post_content );
				$target = QPAT_Segments::norm( (string) $item['kept'] );
				$kept_n = count( QPAT_Segments::split( (string) $item['kept'] ) );
				$done   = false;
				for ( $k = 0; $k + $kept_n <= count( $segs ) && ! $done; $k++ ) {
					$slice = QPAT_Segments::norm( QPAT_Segments::join( array_slice( $segs, $k, $kept_n ) ) );
					if ( $slice === $target ) {
						array_splice( $segs, $k, $kept_n, array( (string) $item['suggest'] ) );
						$done = true;
					}
				}
				if ( ! $done ) {
					return false; // The hand-edited text was changed again: let the editor fix it by hand.
				}
				QPAT_Sync::write_content( $copy_id, QPAT_Segments::join( $segs ) );
			}
		}
		array_splice( $items, $index, 1 );
		if ( $items ) {
			update_post_meta( $copy_id, self::META_REVIEW, wp_slash( $items ) );
		} else {
			delete_post_meta( $copy_id, self::META_REVIEW );
			self::set_state( $copy_id, self::has_hand_edits( $copy_id ) ? 'approved' : 'auto' );
		}
		return true;
	}

	private static function write_field( WP_Post $copy, string $key, string $value ): void {
		if ( 'title' === $key && 'qp_job' !== $copy->post_type ) {
			QPAT_Sync::write_fields( (int) $copy->ID, array( 'post_title' => $value ) );
			return;
		}
		if ( 'excerpt' === $key ) {
			QPAT_Sync::write_fields( (int) $copy->ID, array( 'post_excerpt' => $value ) );
			return;
		}
		if ( 'title' === $key ) {
			$key = 'job_display_title';
		}
		update_post_meta( (int) $copy->ID, $key, wp_slash( $value ) );
	}

	public static function set_state( int $copy_id, string $state ): void {
		if ( isset( self::STATES[ $state ] ) ) {
			update_post_meta( $copy_id, self::META_STATE, $state );
		}
	}

	public static function state( int $copy_id ): string {
		$state = (string) get_post_meta( $copy_id, self::META_STATE, true );
		if ( '' === $state ) {
			return null === self::map( $copy_id ) ? 'legacy' : 'auto';
		}
		return $state;
	}

	/** Version of the Vietnamese text a copy was made from (latest revision ID, or modified time). */
	public static function source_revision( WP_Post $source ): string {
		$revs = wp_get_post_revisions( $source->ID, array( 'posts_per_page' => 1, 'fields' => 'ids' ) );
		$rev  = $revs ? (int) reset( $revs ) : 0;
		return $rev ? (string) $rev : 'm:' . $source->post_modified_gmt;
	}
}
