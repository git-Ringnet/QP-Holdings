<?php
/**
 * Translation status of each Vietnamese post, per target language, and the
 * flags used by the filters of the "Bản dịch" screen:
 *   untranslated  a target language has no copy yet
 *   review        a copy has paragraphs waiting for review
 *   error         the last job failed (or is paused by the quota)
 *   outdated      the Vietnamese text changed after the copy was made
 */

defined( 'ABSPATH' ) || exit;

final class QPAT_Status {
	const META_FLAG    = '_qpat_flag';
	const META_CHECKED = '_qpat_checked';
	const BACKFILL_OPT = 'qpat_backfill_done';

	const FLAGS = array(
		'untranslated' => 'Chưa dịch',
		'review'       => 'Cần duyệt',
		'error'        => 'Lỗi',
		'outdated'     => 'Cần cập nhật',
	);

	const LABELS = array(
		'none'     => 'Chưa dịch',
		'queued'   => 'Đang chờ',
		'running'  => 'Đang dịch',
		'error'    => 'Thất bại',
		'paused'   => 'Tạm dừng (hết hạn mức)',
		'auto'     => 'Dịch tự động',
		'edited'   => 'Đã sửa tay',
		'approved' => 'Đã duyệt',
		'review'   => 'Cần duyệt',
		'outdated' => 'Cần cập nhật',
		'legacy'   => 'Đã dịch (plugin cũ)',
	);

	/**
	 * @return array{state:string, copy_id:int, job:?array}
	 */
	public static function lang_state( WP_Post $source, string $lang ): array {
		$copy_id = QPAT_Sync::existing_translation( $source, $lang );
		$job     = QPAT_Queue::instance()->latest_job( (int) $source->ID, $lang );
		$jstatus = $job ? (string) $job['status'] : '';

		if ( in_array( $jstatus, array( 'queued', 'running', 'paused' ), true ) ) {
			return array( 'state' => $jstatus, 'copy_id' => $copy_id, 'job' => $job );
		}
		if ( 'error' === $jstatus ) {
			return array( 'state' => 'error', 'copy_id' => $copy_id, 'job' => $job );
		}
		if ( ! $copy_id ) {
			return array( 'state' => 'none', 'copy_id' => 0, 'job' => $job );
		}
		$state = QPAT_Updater::state( $copy_id );
		if ( 'review' !== $state && self::is_outdated( $source, $copy_id ) ) {
			$state = 'outdated';
		}
		return array( 'state' => $state, 'copy_id' => $copy_id, 'job' => $job );
	}

	public static function is_outdated( WP_Post $source, int $copy_id ): bool {
		if ( 'outdated' === (string) get_post_meta( $copy_id, QPAT_Updater::META_STATE, true ) ) {
			return true;
		}
		$hash = (string) get_post_meta( $copy_id, QPAT_Sync::META_HASH, true );
		if ( '' !== $hash ) {
			return ! hash_equals( $hash, QPAT_Sync::text_hash( $source ) );
		}
		// Copies from the old plugin: compare modification times.
		$copy = get_post( $copy_id );
		return $copy && strtotime( $source->post_modified_gmt . ' UTC' ) > strtotime( $copy->post_modified_gmt . ' UTC' ) + 120;
	}

	/** Recompute and store the filter flags of a Vietnamese post. */
	public static function refresh( int $source_id ): void {
		$source = get_post( $source_id );
		if ( ! $source || ! QPAT_Sync::is_source_post( $source ) ) {
			return;
		}
		$flags = array();
		foreach ( QPAT_Settings::get()['target_langs'] as $lang ) {
			$st = self::lang_state( $source, $lang )['state'];
			if ( 'none' === $st ) {
				$flags['untranslated'] = true;
			} elseif ( 'review' === $st ) {
				$flags['review'] = true;
			} elseif ( 'error' === $st || 'paused' === $st ) {
				$flags['error'] = true;
			} elseif ( 'outdated' === $st || 'legacy' === $st && self::is_outdated( $source, (int) QPAT_Sync::existing_translation( $source, $lang ) ) ) {
				$flags['outdated'] = true;
			}
		}
		delete_post_meta( $source_id, self::META_FLAG );
		foreach ( array_keys( $flags ) as $flag ) {
			add_post_meta( $source_id, self::META_FLAG, $flag );
		}
		update_post_meta( $source_id, self::META_CHECKED, 1 );
	}

	/**
	 * Compute flags for posts never checked yet (posts published before the
	 * plugin was installed). A few hundred per call, from the cron tick.
	 *
	 * @return int Posts processed.
	 */
	public static function backfill( int $limit = 150 ): int {
		if ( get_option( self::BACKFILL_OPT ) ) {
			return 0;
		}
		$types = QPAT_Settings::get()['post_types'];
		if ( ! $types ) {
			update_option( self::BACKFILL_OPT, 1, false );
			return 0;
		}
		$ids = get_posts(
			array(
				'post_type'        => $types,
				'post_status'      => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'posts_per_page'   => $limit,
				'fields'           => 'ids',
				'suppress_filters' => true,
				'lang'             => '',
				'no_found_rows'    => true,
				'meta_query'       => self::source_meta_query(
					array(
						'key'     => self::META_CHECKED,
						'compare' => 'NOT EXISTS',
					)
				),
			)
		);
		foreach ( $ids as $id ) {
			self::refresh( (int) $id );
		}
		if ( count( $ids ) < $limit ) {
			update_option( self::BACKFILL_OPT, 1, false );
		}
		return count( $ids );
	}

	/** Meta query matching Vietnamese originals only (not EN/ZH copies). */
	public static function source_meta_query( array $extra = array() ): array {
		$q = array(
			'relation' => 'AND',
			array(
				'key'     => QPAT_Sync::META_IS_COPY,
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => QPAT_Sync::META_LANG,
				'compare' => 'NOT EXISTS',
			),
		);
		if ( $extra ) {
			$q[] = $extra;
		}
		return $q;
	}

	public static function label( string $state ): string {
		return self::LABELS[ $state ] ?? $state;
	}
}
