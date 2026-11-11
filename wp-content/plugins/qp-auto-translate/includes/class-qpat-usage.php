<?php
/**
 * Monthly character usage, counted per target language.
 *
 * Google bills Cloud Translation by characters sent, per target language
 * (4,000 VI characters to EN + ZH = 8,000 billed characters).
 */

defined( 'ABSPATH' ) || exit;

final class QPAT_Usage {
	const SCHEMA_VERSION = 1;
	const SCHEMA_OPTION  = 'qpat_schema_version';

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'qpat_usage';
	}

	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = self::table();
		$charset = $wpdb->get_charset_collate();
		dbDelta(
			"CREATE TABLE {$table} (
				period char(7) NOT NULL,
				lang varchar(12) NOT NULL,
				chars bigint(20) unsigned NOT NULL DEFAULT 0,
				requests int(10) unsigned NOT NULL DEFAULT 0,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (period, lang)
			) {$charset};"
		);
		update_option( self::SCHEMA_OPTION, self::SCHEMA_VERSION, false );
	}

	public static function maybe_upgrade(): void {
		if ( (int) get_option( self::SCHEMA_OPTION, 0 ) < self::SCHEMA_VERSION ) {
			self::install();
		}
	}

	/** Current billing month (UTC), e.g. "2026-11". */
	public static function period(): string {
		return gmdate( 'Y-m' );
	}

	/**
	 * @return array<string, int> lang => characters used this month
	 */
	public static function by_lang( string $period = '' ): array {
		global $wpdb;
		$period = $period ?: self::period();
		$rows   = $wpdb->get_results(
			$wpdb->prepare( 'SELECT lang, chars FROM ' . self::table() . ' WHERE period = %s', $period ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		);
		$out = array();
		foreach ( (array) $rows as $row ) {
			$out[ (string) $row['lang'] ] = (int) $row['chars'];
		}
		return $out;
	}

	public static function used( string $period = '' ): int {
		return (int) array_sum( self::by_lang( $period ) );
	}

	public static function add( string $lang, int $chars ): void {
		global $wpdb;
		if ( $chars <= 0 ) {
			return;
		}
		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO ' . self::table() . ' (period, lang, chars, requests, updated_at) VALUES (%s, %s, %d, 1, %s)
				 ON DUPLICATE KEY UPDATE chars = chars + VALUES(chars), requests = requests + 1, updated_at = VALUES(updated_at)', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				self::period(),
				$lang,
				$chars,
				current_time( 'mysql', true )
			)
		);
	}

	/**
	 * @return array{used:int, limit:int, percent:float, state:string}
	 *         state: ok | warn | paused | unlimited
	 */
	public static function status(): array {
		$s     = QPAT_Settings::get();
		$used  = self::used();
		$limit = (int) $s['monthly_limit'];
		if ( $limit <= 0 ) {
			return array( 'used' => $used, 'limit' => 0, 'percent' => 0.0, 'state' => 'unlimited' );
		}
		$percent = round( $used * 100 / $limit, 1 );
		$state   = 'ok';
		if ( $used >= $limit ) {
			$state = 'paused';
		} elseif ( $percent >= (int) $s['warn_percent'] ) {
			$state = 'warn';
		}
		return array( 'used' => $used, 'limit' => $limit, 'percent' => $percent, 'state' => $state );
	}

	/**
	 * Would sending $chars more characters stay within the monthly limit?
	 */
	public static function allows( int $chars ): bool {
		$limit = (int) QPAT_Settings::get()['monthly_limit'];
		return $limit <= 0 || self::used() + $chars <= $limit;
	}
}
