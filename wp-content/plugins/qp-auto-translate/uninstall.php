<?php
/**
 * Remove plugin settings and the job queue on uninstall.
 * Translated posts and the character usage history are kept.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'qpat_settings' );
delete_option( 'qpat_worker_token' );
delete_option( 'qpat_gemini_models' );
delete_option( 'qpat_jobs_schema_version' );
delete_option( 'qpat_backfill_done' );
wp_clear_scheduled_hook( 'qpat_tick' );

global $wpdb;
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'qpat_jobs' ); // phpcs:ignore WordPress.DB
