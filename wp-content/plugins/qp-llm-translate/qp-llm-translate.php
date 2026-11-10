<?php
/**
 * Plugin Name: QP LLM Translate
 * Plugin URI: https://qpholdings.vn
 * Description: Translate news posts into other languages with DeepSeek, OpenAI, or Gemini. Jobs run in the background via WP-Cron and a loopback worker, without blocking the editor.
 * Version: 1.3.0
 * Author: QP Holdings
 * Text Domain: qp-llm-translate
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined('ABSPATH') || exit;

if (isset($_GET['qp_debug'])) {
	@ini_set('display_errors', '1');
	error_reporting(E_ALL);
}

register_shutdown_function(static function (): void {
	$err = error_get_last();
	if ($err && in_array($err['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR), true)) {
		echo '<div style="background:#fff;color:#d63638;padding:24px;margin:20px;border:2px solid #d63638;font-family:monospace;font-size:14px;z-index:999999;position:relative;">';
		echo '<h3>QP Diagnostic - Fatal Error Detected</h3>';
		echo '<pre>' . htmlspecialchars(print_r($err, true)) . '</pre>';
		echo '</div>';
	}
});

define('QP_LLM_TRANSLATE_VERSION', '1.3.0');
define('QP_LLM_TRANSLATE_FILE', __FILE__);
define('QP_LLM_TRANSLATE_DIR', plugin_dir_path(__FILE__));
define('QP_LLM_TRANSLATE_URL', plugin_dir_url(__FILE__));

require_once QP_LLM_TRANSLATE_DIR . 'includes/class-qp-llm-client.php';
require_once QP_LLM_TRANSLATE_DIR . 'includes/class-qp-llm-sync.php';
require_once QP_LLM_TRANSLATE_DIR . 'includes/class-qp-llm-queue.php';
require_once QP_LLM_TRANSLATE_DIR . 'includes/class-qp-llm-settings.php';
require_once QP_LLM_TRANSLATE_DIR . 'includes/class-qp-llm-theme-sync.php';
require_once QP_LLM_TRANSLATE_DIR . 'includes/class-qp-llm-plugin.php';

register_activation_hook(__FILE__, array('QP_LLM_Queue', 'activate'));
register_deactivation_hook(__FILE__, array('QP_LLM_Queue', 'deactivate'));

add_action('plugins_loaded', static function (): void {
	QP_LLM_Theme_Sync::init();
	QP_LLM_Plugin::instance()->boot();
});
