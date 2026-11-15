<?php
/**
 * Cài đặt + nhật ký lỗi của QP WebP Convert.
 */

defined( 'ABSPATH' ) || exit;

final class QPWC_Settings {

	const OPTION = 'qpwc_settings';
	const LOG    = 'qpwc_log';

	/** Chất lượng WebP mặc định (khách chốt: 80). */
	const DEFAULT_QUALITY = 80;

	public static function defaults(): array {
		return array(
			'enabled'   => 1,
			'quality'   => self::DEFAULT_QUALITY,
			'backup_at' => 0, // thời điểm quản trị xác nhận đã sao lưu (WP-07)
		);
	}

	public static function all(): array {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	public static function get( string $key ) {
		$all = self::all();
		return $all[ $key ] ?? null;
	}

	public static function enabled(): bool {
		return (bool) self::get( 'enabled' );
	}

	public static function quality(): int {
		return max( 1, min( 100, (int) self::get( 'quality' ) ) );
	}

	public static function update( array $changes ): void {
		update_option( self::OPTION, array_merge( self::all(), $changes ), false );
	}

	public static function sanitize( $input ): array {
		$current = self::all();
		$input   = is_array( $input ) ? $input : array();
		return array(
			'enabled'   => empty( $input['enabled'] ) ? 0 : 1,
			'quality'   => max( 1, min( 100, (int) ( $input['quality'] ?? self::DEFAULT_QUALITY ) ) ),
			// Form cài đặt không gửi backup_at → giữ giá trị cũ; update() trong code gửi giá trị mới → dùng giá trị đó.
			'backup_at' => isset( $input['backup_at'] ) ? (int) $input['backup_at'] : (int) $current['backup_at'],
		);
	}

	/** Ghi nhật ký lỗi (giữ 100 dòng cuối) — WP-21. */
	public static function log( string $message, int $attachment_id = 0 ): void {
		$log   = get_option( self::LOG, array() );
		$log   = is_array( $log ) ? $log : array();
		$log[] = array(
			'time' => time(),
			'id'   => $attachment_id,
			'msg'  => $message,
		);
		update_option( self::LOG, array_slice( $log, -100 ), false );
		error_log( '[qp-webp-convert] ' . ( $attachment_id ? '#' . $attachment_id . ' ' : '' ) . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
	}

	public static function get_log(): array {
		$log = get_option( self::LOG, array() );
		return is_array( $log ) ? array_reverse( $log ) : array();
	}
}
