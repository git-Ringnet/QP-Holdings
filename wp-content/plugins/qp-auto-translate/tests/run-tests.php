<?php
/**
 * Kiểm thử QP Auto Translate với Google API GIẢ LẬP (không gọi Google thật, không tốn ký tự).
 *
 * Chạy (từ thư mục gốc website):
 *   C:\xampp\php\php.exe wp-content\plugins\qp-auto-translate\tests\run-tests.php
 *
 * Ghi tạm vào bảng wp_qpat_usage của database local rồi xoá sạch khi xong.
 */

if ( PHP_SAPI !== 'cli' ) {
	exit;
}

$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 4 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/admin.php';

if ( ! class_exists( 'QPAT_Settings' ) ) {
	require_once dirname( __DIR__ ) . '/qp-auto-translate.php';
}

$fails = 0;
function qpat_check( string $label, bool $cond ): void {
	global $fails;
	echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n";
	if ( ! $cond ) {
		$fails++;
	}
}

// Cấu hình giả, không ghi vào option thật.
$limit = 1000;
add_filter(
	'pre_option_qpat_settings',
	function () use ( &$limit ) {
		return array( 'api_key' => 'TEST-KEY-1234', 'target_langs' => array( 'en', 'zh' ), 'post_types' => array( 'qp_news', 'qp_contact_msg' ), 'monthly_limit' => $limit, 'warn_percent' => 80 );
	}
);

// Google giả lập.
$calls = array();
$mode  = 'ok';
add_filter(
	'pre_http_request',
	function ( $pre, $args, $url ) use ( &$calls, &$mode ) {
		if ( false === strpos( $url, 'translation.googleapis.com' ) ) {
			return $pre;
		}
		$body    = json_decode( $args['body'], true );
		$calls[] = array( 'url' => $url, 'body' => $body );
		if ( 'error' === $mode ) {
			return array( 'response' => array( 'code' => 403, 'message' => 'Forbidden' ), 'body' => wp_json_encode( array( 'error' => array( 'message' => 'API key not valid.' ) ) ), 'headers' => array(), 'cookies' => array() );
		}
		$tr = array();
		foreach ( $body['q'] as $q ) {
			$tr[] = array( 'translatedText' => '[' . $body['target'] . ']' . $q );
		}
		return array( 'response' => array( 'code' => 200, 'message' => 'OK' ), 'body' => wp_json_encode( array( 'data' => array( 'translations' => $tr ) ) ), 'headers' => array(), 'cookies' => array() );
	},
	10,
	3
);

QPAT_Usage::install();
global $wpdb;
$wpdb->query( 'DELETE FROM ' . QPAT_Usage::table() );

// 1. Loại nội dung chứa dữ liệu cá nhân bị loại khỏi cấu hình.
qpat_check( 'blocked post type removed (qp_contact_msg)', ! in_array( 'qp_contact_msg', QPAT_Settings::get()['post_types'], true ) );

// 2. Giữ thứ tự, bỏ qua đoạn rỗng, gửi html + nmt + zh-CN.
$in  = array( 'title' => 'Tiêu đề', 'excerpt' => '', 'content' => '<p>Xin chào <strong>QP</strong></p>' );
$out = QPAT_Google_Client::translate( $in, 'zh' );
qpat_check( 'keys and order preserved', array_keys( $out ) === array( 'title', 'excerpt', 'content' ) );
qpat_check( 'empty segment not sent', '' === $out['excerpt'] && 2 === count( $calls[0]['body']['q'] ) );
qpat_check( 'zh mapped to zh-CN', 'zh-CN' === $calls[0]['body']['target'] );
qpat_check( 'format html + model nmt', 'html' === $calls[0]['body']['format'] && 'nmt' === $calls[0]['body']['model'] );
qpat_check( 'html kept in result', '[zh-CN]<p>Xin chào <strong>QP</strong></p>' === $out['content'] );

// 3. Đếm ký tự theo từng ngôn ngữ (Unicode).
$expected = mb_strlen( 'Tiêu đề' ) + mb_strlen( '<p>Xin chào <strong>QP</strong></p>' );
qpat_check( "zh usage = {$expected}", QPAT_Usage::by_lang()['zh'] === $expected );
QPAT_Google_Client::translate( array( 'Xin chào' ), 'en' );
qpat_check( 'en counted separately', 8 === QPAT_Usage::by_lang()['en'] && QPAT_Usage::used() === $expected + 8 );

// 4. Hạn mức: chặn TRƯỚC khi gọi Google.
$before = count( $calls );
$thrown = false;
try {
	QPAT_Google_Client::translate( array( str_repeat( 'a', 2000 ) ), 'en' );
} catch ( QPAT_Quota_Exception $e ) {
	$thrown = true;
}
qpat_check( 'quota exception thrown when over limit', $thrown );
qpat_check( 'no API call when over limit', count( $calls ) === $before );

// 5. Trạng thái hạn mức.
$limit = 50;
qpat_check( 'state paused when used >= limit', 'paused' === QPAT_Usage::status()['state'] );
$limit = 55;
qpat_check( 'state warn at >= 80%', 'warn' === QPAT_Usage::status()['state'] );
$limit = 1000;
qpat_check( 'state ok below 80%', 'ok' === QPAT_Usage::status()['state'] );

// 6. Google báo lỗi: hiện lỗi, không tính ký tự.
$mode   = 'error';
$used   = QPAT_Usage::used();
$errmsg = '';
try {
	QPAT_Google_Client::translate( array( 'Lỗi' ), 'en' );
} catch ( RuntimeException $e ) {
	$errmsg = $e->getMessage();
}
qpat_check( 'google error message surfaced', false !== strpos( $errmsg, 'API key not valid' ) );
qpat_check( 'failed call not billed', QPAT_Usage::used() === $used );
$mode = 'ok';

// 7. Chia lô: 250 đoạn → 3 request.
$before = count( $calls );
$limit  = 0;
$res    = QPAT_Google_Client::translate( array_fill( 0, 250, 'Câu' ), 'en' );
qpat_check( '250 segments split into 3 requests', 3 === count( $calls ) - $before );
qpat_check( 'all 250 returned', 250 === count( $res ) && '[en]Câu' === $res[249] );

// 8. Trang cấu hình hiển thị (quyền quản trị).
$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
if ( $admins ) {
	wp_set_current_user( (int) $admins[0] );
	$limit = 500000;
	ob_start();
	QPAT_Admin::instance()->render();
	$html = (string) ob_get_clean();
	foreach ( array( 'Hạn mức tháng', 'Google Cloud API key', 'Ngôn ngữ đích', 'Giới hạn ký tự / tháng', 'Kiểm tra kết nối Google', 'value="500000"' ) as $needle ) {
		qpat_check( "settings page shows: $needle", false !== strpos( $html, $needle ) );
	}
	qpat_check( 'settings page does not offer contact messages', false === strpos( $html, 'value="qp_contact_msg"' ) );
}

$wpdb->query( 'DELETE FROM ' . QPAT_Usage::table() );
echo $fails ? "\n{$fails} FAILED\n" : "\nALL PASSED\n";
exit( $fails ? 1 : 0 );
