<?php
/**
 * Monthly character usage, counted per engine and per target language.
 *
 * Google bills Cloud Translation by characters sent, per target language
 * (4,000 VI characters to EN + ZH = 8,000 billed characters). The monthly
 * limit applies to Google only; Gemini is counted for information.
 */

defined( 'ABSPATH' ) || exit;

final class QPAT_Usage {
	const SCHEMA_VERSION = 2;
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

		// v1 → v2: add the engine column and widen the primary key.
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
		if ( $exists && ! $wpdb->get_var( "SHOW COLUMNS FROM {$table} LIKE 'engine'" ) ) { // phpcs:ignore WordPress.DB.PreparedSQL
			$wpdb->query( "ALTER TABLE {$table} ADD engine varchar(20) NOT NULL DEFAULT 'google' AFTER period, DROP PRIMARY KEY, ADD PRIMARY KEY (period, engine, lang)" ); // phpcs:ignore WordPress.DB
		}

		dbDelta(
			"CREATE TABLE {$table} (
				period char(7) NOT NULL,
				engine varchar(20) NOT NULL DEFAULT 'google',
				lang varchar(12) NOT NULL,
				chars bigint(20) unsigned NOT NULL DEFAULT 0,
				requests int(10) unsigned NOT NULL DEFAULT 0,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (period, engine, lang)
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
	public static function by_lang( string $period = '', string $engine = 'google' ): array {
		global $wpdb;
		$period = $period ?: self::period();
		$rows   = $wpdb->get_results(
			$wpdb->prepare( 'SELECT lang, chars FROM ' . self::table() . ' WHERE period = %s AND engine = %s', $period, $engine ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		);
		$out = array();
		foreach ( (array) $rows as $row ) {
			$out[ (string) $row['lang'] ] = (int) $row['chars'];
		}
		return $out;
	}

	/** Google characters this month (the figure the limit applies to). */
	public static function used( string $period = '', string $engine = 'google' ): int {
		return (int) array_sum( self::by_lang( $period, $engine ) );
	}

	public static function add( string $lang, int $chars, string $engine = 'google' ): void {
		global $wpdb;
		if ( $chars <= 0 ) {
			return;
		}
		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO ' . self::table() . ' (period, engine, lang, chars, requests, updated_at) VALUES (%s, %s, %s, %d, 1, %s)
				 ON DUPLICATE KEY UPDATE chars = chars + VALUES(chars), requests = requests + 1, updated_at = VALUES(updated_at)', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				self::period(),
				$engine,
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
