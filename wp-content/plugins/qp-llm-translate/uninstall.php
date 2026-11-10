<?php
/**
 * Uninstall QP LLM Translate.
 */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'qp_llm_translate_settings' );
delete_option( 'qp_llm_translate_worker_token' );

global $wpdb;
$table = $wpdb->prefix . 'qp_llm_translate_jobs';
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
