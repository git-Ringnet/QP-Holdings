<?php
/**
 * Kiểm thử QP Auto Translate với Gemini / Google GIẢ LẬP
 * (không gọi API thật, không tốn ký tự, không cần API key thật).
 *
 * Chạy (từ thư mục gốc website):
 *   C:\xampp\php\php.exe wp-content\plugins\qp-auto-translate\tests\run-tests.php
 *
 * Ghi tạm vào database local (bài thử, bảng wp_qpat_usage / wp_qpat_jobs) rồi xoá sạch khi xong.
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
	// Plugin may be inactive on this database: boot it by hand.
	QPAT_Queue::instance()->boot();
	QPAT_Editor::instance()->boot();
}

$fails = 0;
function qpat_check( string $label, bool $cond ): void {
	global $fails;
	echo ( $cond ? 'PASS ' : 'FAIL ' ) . $label . "\n";
	if ( ! $cond ) {
		$fails++;
	}
}

// ---------------------------------------------------------------- cấu hình giả
$cfg = array(
	'engine'          => 'gemini',
	'gemini_api_key'  => 'GEMINI-TEST-KEY',
	'gemini_model'    => 'gemini-test-model',
	'fallback_google' => 0,
	'api_key'         => 'TEST-KEY-1234',
	'target_langs'    => array( 'en', 'zh' ),
	'post_types'      => array( 'qp_news', 'qp_contact_msg' ),
	'auto_on_publish' => 1,
	'monthly_limit'   => 1000,
	'warn_percent'    => 80,
);
add_filter( 'pre_option_qpat_settings', function () use ( &$cfg ) {
	return $cfg;
} );
// Plugin cũ (nếu đang bật) coi như không tự dịch, để kiểm thử luồng của plugin mới.
add_filter( 'pre_option_qp_llm_translate_settings', function () {
	return array( 'auto_on_publish' => 0 );
} );
add_filter( 'qpat_gemini_retry_sleep', '__return_zero' );

// ---------------------------------------------------------------- HTTP giả lập
$calls        = array();
$gemini_calls = array();
$spawned      = 0;
$mode         = 'ok';
$gemini_mode  = 'ok';

function qpat_http( int $code, $body ): array {
	return array( 'response' => array( 'code' => $code, 'message' => '' ), 'body' => wp_json_encode( $body ), 'headers' => array(), 'cookies' => array() );
}

add_filter(
	'pre_http_request',
	function ( $pre, $args, $url ) use ( &$calls, &$gemini_calls, &$spawned, &$mode, &$gemini_mode ) {
		// Request chạy ngầm tới admin-ajax: chỉ đếm, KHÔNG chạy thật.
		if ( false !== strpos( $url, 'admin-ajax.php' ) ) {
			$spawned++;
			return qpat_http( 200, 'ok' );
		}
		if ( false !== strpos( $url, 'translation.googleapis.com' ) ) {
			$body    = json_decode( $args['body'], true );
			$calls[] = array( 'url' => $url, 'body' => $body );
			if ( 'error' === $mode ) {
				return qpat_http( 403, array( 'error' => array( 'message' => 'API key not valid.' ) ) );
			}
			$tr = array();
			foreach ( $body['q'] as $q ) {
				$tr[] = array( 'translatedText' => '[' . $body['target'] . ']' . $q );
			}
			return qpat_http( 200, array( 'data' => array( 'translations' => $tr ) ) );
		}
		if ( false !== strpos( $url, 'generativelanguage.googleapis.com' ) ) {
			$body           = json_decode( (string) ( $args['body'] ?? '' ), true );
			$gemini_calls[] = array( 'url' => $url, 'body' => $body );
			if ( 'rate' === $gemini_mode ) {
				return qpat_http( 429, array( 'error' => array( 'message' => 'Resource has been exhausted' ) ) );
			}
			if ( 'badkey' === $gemini_mode ) {
				return qpat_http( 400, array( 'error' => array( 'message' => 'API key not valid. Please pass a valid API key.' ) ) );
			}
			if ( false !== strpos( $url, '/models?' ) ) {
				return qpat_http( 200, array( 'models' => array(
					array( 'name' => 'models/gemini-b', 'supportedGenerationMethods' => array( 'generateContent' ) ),
					array( 'name' => 'models/gemini-a', 'supportedGenerationMethods' => array( 'generateContent', 'countTokens' ) ),
					array( 'name' => 'models/embedding-x', 'supportedGenerationMethods' => array( 'embedContent' ) ),
				) ) );
			}
			// Lấy JSON các trường ở cuối prompt, trả về bản "dịch" có tiền tố.
			$prompt = (string) $body['contents'][0]['parts'][0]['text'];
			$fields = json_decode( substr( $prompt, (int) strrpos( $prompt, "\n\n" ) + 2 ), true );
			$is_zh  = false !== strpos( $prompt, 'into Simplified Chinese' );
			$out    = array();
			foreach ( (array) $fields as $k => $v ) {
				$out[ $k ] = '' === $v ? '' : ( $is_zh ? '[中文]' : '[EN]' ) . $v;
				if ( 'dropimg' === $gemini_mode ) {
					// Giống lỗi thật: Gemini bỏ mất ảnh / mã giữ chỗ.
					$out[ $k ] = (string) preg_replace( array( '/\{\{QPAT_\d+\}\}/', '/<img\b[^>]*>/i' ), '', $out[ $k ] );
				}
				if ( 'spacedtoken' === $gemini_mode ) {
					$out[ $k ] = (string) preg_replace( '/\{\{QPAT_(\d+)\}\}/', '{{ QPAT_$1 }}', $out[ $k ] );
				}
			}
			return qpat_http( 200, array( 'candidates' => array( array( 'content' => array( 'parts' => array( array( 'text' => wp_json_encode( $out, JSON_UNESCAPED_UNICODE ) ) ) ) ) ) ) );
		}
		return $pre;
	},
	10,
	3
);

QPAT_Usage::install();
QPAT_Queue::install();
global $wpdb;
$wpdb->query( 'DELETE FROM ' . QPAT_Usage::table() );

// ================================================================ 1. Cấu hình
qpat_check( 'blocked post type removed (qp_contact_msg)', ! in_array( 'qp_contact_msg', QPAT_Settings::get()['post_types'], true ) );
qpat_check( 'default engine is gemini', 'gemini' === QPAT_Settings::defaults()['engine'] );
$san = QPAT_Settings::sanitize( array( 'engine' => 'evil', 'gemini_model' => 'models/gemini-x<script>', 'gemini_api_key' => '••••••••-KEY' ) );
qpat_check( 'unknown engine falls back to gemini', 'gemini' === $san['engine'] );
qpat_check( 'model name cleaned', 'gemini-xscript' === $san['gemini_model'] );
qpat_check( 'masked key keeps stored key', 'GEMINI-TEST-KEY' === $san['gemini_api_key'] );

// ================================================================ 2. Google client
$cfg['engine'] = 'google';
$in  = array( 'title' => 'Tiêu đề', 'excerpt' => '', 'content' => '<p>Xin chào <strong>QP</strong></p>' );
$out = QPAT_Google_Client::translate( $in, 'zh' );
qpat_check( 'google: keys and order preserved', array_keys( $out ) === array( 'title', 'excerpt', 'content' ) );
qpat_check( 'google: empty segment not sent', '' === $out['excerpt'] && 2 === count( $calls[0]['body']['q'] ) );
qpat_check( 'google: zh mapped to zh-CN', 'zh-CN' === $calls[0]['body']['target'] );
qpat_check( 'google: format html + model nmt', 'html' === $calls[0]['body']['format'] && 'nmt' === $calls[0]['body']['model'] );
QPAT_Google_Client::translate( array( 'a' => 'Dòng 1' ), 'en', 'vi', 'text' );
qpat_check( 'google: text format passed through', 'text' === end( $calls )['body']['format'] );

$expected = mb_strlen( 'Tiêu đề' ) + mb_strlen( '<p>Xin chào <strong>QP</strong></p>' );
qpat_check( "google: zh usage = {$expected}", QPAT_Usage::by_lang()['zh'] === $expected );

$before = count( $calls );
$thrown = false;
try {
	QPAT_Google_Client::translate( array( str_repeat( 'a', 2000 ) ), 'en' );
} catch ( QPAT_Quota_Exception $e ) {
	$thrown = true;
}
qpat_check( 'google: quota exception when over limit, no API call', $thrown && count( $calls ) === $before );

$cfg['monthly_limit'] = QPAT_Usage::used();
qpat_check( 'usage: paused when used >= limit', 'paused' === QPAT_Usage::status()['state'] );
$cfg['monthly_limit'] = 0;
$before = count( $calls );
$res    = QPAT_Google_Client::translate( array_fill( 0, 250, 'Câu' ), 'en' );
qpat_check( 'google: 250 segments split into 3 requests', 3 === count( $calls ) - $before && '[en]Câu' === $res[249] );

$mode   = 'error';
$used   = QPAT_Usage::used();
$errmsg = '';
try {
	QPAT_Google_Client::translate( array( 'Lỗi' ), 'en' );
} catch ( RuntimeException $e ) {
	$errmsg = $e->getMessage();
}
qpat_check( 'google: error surfaced and not billed', false !== strpos( $errmsg, 'API key not valid' ) && QPAT_Usage::used() === $used );
$mode = 'ok';

// ================================================================ 3. Google qua Translator (giữ đoạn văn, shortcode)
$content = "Đoạn một.\n\nĐoạn hai có [caption id=\"x\"]<img src=\"a.jpg\"> Chú thích[/caption] ở giữa.";
$t       = QPAT_Translator::translate( array( 'title' => 'Tin', 'excerpt' => "Dòng 1\nDòng 2", 'content' => $content ), 'en' );
$last    = end( $calls );
qpat_check( 'translator/google: paragraphs become <p> before sending', 2 <= substr_count( $last['body']['q'][0], '<p>' ) );
qpat_check( 'translator/google: shortcode protected when sent', false !== strpos( $last['body']['q'][0], 'data-qpat-keep="1"' ) );
qpat_check( 'translator/google: shortcode restored, no wrapper left', false !== strpos( $t['content'], '[caption id="x"]' ) && false === strpos( $t['content'], 'data-qpat-keep' ) );
qpat_check( 'translator/google: plain fields keep line breaks', "[en]Dòng 1\nDòng 2" === $t['excerpt'] );

// ================================================================ 4. Gemini
$cfg['engine']  = 'gemini';
$used           = QPAT_Usage::used();
$gemini_calls   = array();
$engine_used    = '';
$t = QPAT_Translator::translate(
	array( 'title' => 'Khai trương', 'excerpt' => '', 'content' => '<p>Địa chỉ: Tầng L16, Tòa nhà Vietcombank</p>', 'source' => 'VnExpress' ),
	'en',
	$engine_used
);
qpat_check( 'gemini: used by default', 'gemini' === $engine_used && 1 === count( $gemini_calls ) );
qpat_check( 'gemini: model + key in URL', false !== strpos( $gemini_calls[0]['url'], 'models/gemini-test-model:generateContent' ) && false !== strpos( $gemini_calls[0]['url'], 'key=GEMINI-TEST-KEY' ) );
qpat_check( 'gemini: asks for JSON', 'application/json' === $gemini_calls[0]['body']['generationConfig']['responseMimeType'] );
qpat_check( 'gemini: fields translated', '[EN]Khai trương' === $t['title'] && '[EN]VnExpress' === $t['source'] );
qpat_check( 'gemini: glossary normalises footer labels', false !== strpos( $t['content'], 'Address:' ) && false !== strpos( $t['content'], 'Level L16, Vietcombank Tower' ) );
qpat_check( 'gemini: only requested keys returned', array_keys( $t ) === array( 'title', 'excerpt', 'content', 'source' ) );
qpat_check( 'gemini: google usage not counted', QPAT_Usage::used() === $used );

$gemini_mode = 'rate';
$err         = null;
try {
	QPAT_Gemini_Client::translate( array( 'title' => 'x' ), 'en' );
} catch ( Throwable $e ) {
	$err = $e;
}
qpat_check( 'gemini: 429 becomes transient (retry later)', $err instanceof QPAT_Transient_Exception );

$gemini_mode = 'badkey';
$err         = null;
try {
	QPAT_Translator::translate( array( 'title' => 'x' ), 'en' );
} catch ( Throwable $e ) {
	$err = $e;
}
qpat_check( 'gemini: bad key is a normal error, message kept', $err && ! ( $err instanceof QPAT_Transient_Exception ) && false !== strpos( $err->getMessage(), 'API key not valid' ) );

$cfg['fallback_google'] = 1;
$engine_used            = '';
$t                      = QPAT_Translator::translate( array( 'title' => 'Dự phòng' ), 'en', $engine_used );
qpat_check( 'fallback: gemini fails → google used', 'google' === $engine_used && '[en]Dự phòng' === $t['title'] );
$cfg['fallback_google'] = 0;
$gemini_mode            = 'ok';

$models = QPAT_Gemini_Client::list_models();
qpat_check( 'gemini: model list filtered + sorted', array( 'gemini-a', 'gemini-b' ) === $models );

// ================================================================ 5. Luồng đầy đủ: đăng bài → hàng đợi → bài EN/ZH
$created = array();
$cfg['post_types'] = array( 'qp_news' );
$thumb = (int) $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%' ORDER BY ID DESC LIMIT 1" );
$src_id = wp_insert_post(
	array(
		'post_type'    => 'qp_news',
		'post_status'  => 'draft',
		'post_title'   => 'QPAT TEST bài tiếng Việt',
		'post_excerpt' => 'Tóm tắt',
		'post_content' => '<p>Nội dung thử nghiệm.</p>',
	)
);
$created[] = $src_id;
update_post_meta( $src_id, 'source', 'Báo Đầu tư' );
if ( $thumb ) {
	set_post_thumbnail( $src_id, $thumb );
}
$jobs_table = QPAT_Queue::table();
$count_jobs = function () use ( $wpdb, $jobs_table, &$src_id ): int {
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$jobs_table} WHERE source_post_id = %d", $src_id ) );
};
qpat_check( 'flow: saving a draft queues nothing', 0 === $count_jobs() );

$spawned = 0;
wp_update_post( array( 'ID' => $src_id, 'post_status' => 'publish' ) );
qpat_check( 'flow: first publish queues EN + ZH', 2 === $count_jobs() );
qpat_check( 'flow: background worker pinged (non-blocking)', $spawned >= 1 );

wp_update_post( array( 'ID' => $src_id, 'post_content' => '<p>Nội dung thử nghiệm (đã sửa).</p>' ) );
qpat_check( 'flow: editing a published post queues nothing new', 2 === $count_jobs() );

QPAT_Queue::instance()->process_batch();
$en = QPAT_Sync::existing_translation( get_post( $src_id ), 'en' );
$zh = QPAT_Sync::existing_translation( get_post( $src_id ), 'zh' );
$created[] = $en;
$created[] = $zh;
qpat_check( 'flow: EN and ZH posts created', $en > 0 && $zh > 0 );
if ( $en && $zh ) {
	$en_post = get_post( $en );
	$zh_post = get_post( $zh );
	qpat_check( 'flow: EN title translated', '[EN]QPAT TEST bài tiếng Việt' === $en_post->post_title );
	qpat_check( 'flow: ZH title translated', '[中文]QPAT TEST bài tiếng Việt' === $zh_post->post_title );
	qpat_check( 'flow: translates latest content', false !== strpos( $en_post->post_content, '(đã sửa)' ) );
	qpat_check( 'flow: published like the original', 'publish' === $en_post->post_status && $en_post->post_date === get_post( $src_id )->post_date );
	qpat_check( 'flow: slug = original slug + -en', get_post( $src_id )->post_name . '-en' === $en_post->post_name );
	qpat_check( 'flow: theme meta set', 'en' === get_post_meta( $en, '_qp_translation_lang', true ) && (int) get_post_meta( $en, '_qp_llm_source_id', true ) === $src_id && '1' === (string) get_post_meta( $en, '_qp_llm_is_translation', true ) );
	qpat_check( 'flow: engine recorded', 'gemini' === get_post_meta( $en, '_qpat_engine', true ) );
	qpat_check( 'flow: meta field translated', '[EN]Báo Đầu tư' === get_post_meta( $en, 'source', true ) );
	if ( $thumb ) {
		qpat_check( 'flow: featured image reused', (int) get_post_thumbnail_id( $en ) === $thumb );
	}
	$fam = QPAT_Sync::family( $zh );
	qpat_check( 'flow: family found from the ZH copy', array( 'vi' => $src_id, 'en' => $en, 'zh' => $zh ) == $fam );
	qpat_check( 'flow: copy is not a source post', ! QPAT_Sync::is_source_post( $zh_post ) );
	qpat_check( 'flow: copy never queues', 0 === QPAT_Queue::instance()->enqueue_post( $zh, true ) );

	// Bản dịch đã được sửa tay → tự động không được ghi đè.
	wp_update_post( array( 'ID' => $en, 'post_content' => '<p>Ảnh đã thay tay</p>' ) );
	qpat_check( 'flow: auto enqueue skips existing translations', 0 === QPAT_Queue::instance()->enqueue_post( $src_id, false ) );
	qpat_check( 'flow: manual edit kept', '<p>Ảnh đã thay tay</p>' === get_post( $en )->post_content );

	// "Dịch lại" → cập nhật đúng 2 bài cũ, không tạo bài trùng.
	qpat_check( 'flow: manual re-translate queues 2', 2 === QPAT_Queue::instance()->enqueue_post( $src_id, true ) );
	QPAT_Queue::instance()->process_batch();
	$n = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_qp_llm_source_id' AND meta_value = %s", (string) $src_id ) );
	qpat_check( 'flow: re-translate updates, no duplicates', 2 === $n && QPAT_Sync::existing_translation( get_post( $src_id ), 'en' ) === $en );
	qpat_check( 'flow: re-translate overwrote EN content', false !== strpos( get_post( $en )->post_content, '[EN]' ) );
}

// Gemini hết lượt → hàng đợi chờ rồi thử lại, không báo lỗi ngay.
$src2      = wp_insert_post( array( 'post_type' => 'qp_news', 'post_status' => 'publish', 'post_title' => 'QPAT TEST hết lượt', 'post_content' => 'x' ) );
$created[] = $src2;
$gemini_mode = 'rate';
QPAT_Queue::instance()->process_batch();
$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$jobs_table} WHERE source_post_id = %d ORDER BY id ASC LIMIT 1", $src2 ), ARRAY_A );
qpat_check( 'retry: rate-limited job goes back to queue', $row && 'queued' === $row['status'] && 1 === (int) $row['attempts'] );
qpat_check( 'retry: retry is delayed', $row && strtotime( $row['run_after'] . ' UTC' ) > time() + 60 );
$gemini_mode = 'ok';

// ================================================================ 5b. Giữ ảnh khi dịch bằng Gemini
$img      = '<img class="alignnone size-full wp-image-1" src="http://example.test/a.png" alt="" width="10" height="10" />';
$with_img = "Đoạn đầu tiên.\n\n{$img}\n\nĐoạn cuối [caption id=\"c1\"]<a href=\"http://example.test/b.jpg\"><img src=\"http://example.test/b.jpg\" /></a> Chú thích[/caption]";
$kept     = array();
$held     = QPAT_Translator::hold_media( $with_img, $kept );
qpat_check( 'media: images/shortcodes replaced by placeholders', false === strpos( $held, '<img' ) && false === strpos( $held, '[caption' ) && false !== strpos( $held, 'Chú thích' ) );
qpat_check( 'media: restore gives back the exact original', $with_img === QPAT_Translator::release_media( $held, $held, $kept ) );

$cfg['engine'] = 'gemini';
foreach ( array( 'ok', 'spacedtoken', 'dropimg' ) as $gm ) {
	$gemini_mode = $gm;
	$t           = QPAT_Translator::translate( array( 'title' => 'Ảnh', 'excerpt' => '', 'content' => $with_img ), 'en' );
	qpat_check( "media ($gm): image kept in translated content", false !== strpos( $t['content'], $img ) && false !== strpos( $t['content'], '<img src="http://example.test/b.jpg" />' ) && false === strpos( $t['content'], 'QPAT_' ) );
}
qpat_check( 'media (dropimg): lost image put back between paragraphs, not at the start', strpos( $t['content'], $img ) > strpos( $t['content'], 'Đoạn đầu' ) );
$gemini_mode = 'ok';

// ================================================================ 5c. Dịch lúc còn nháp → đăng bài thì bản dịch cũng được đăng
$src3      = wp_insert_post( array( 'post_type' => 'qp_news', 'post_status' => 'draft', 'post_title' => 'QPAT TEST nháp', 'post_content' => "Chữ\n\n{$img}" ) );
$created[] = $src3;
$wpdb->delete( $jobs_table, array( 'status' => 'queued' ) );
QPAT_Queue::instance()->enqueue_post( $src3, false ); // nút "Dịch ngay" khi còn nháp
delete_transient( 'qpat_worker_lock' );
QPAT_Queue::instance()->process_batch();
$f3 = QPAT_Sync::family( $src3 );
$created[] = $f3['en'] ?? 0;
$created[] = $f3['zh'] ?? 0;
qpat_check( 'draft: copies created as draft', ! empty( $f3['en'] ) && 'draft' === get_post_status( $f3['en'] ) );
qpat_check( 'draft: copy keeps the image', ! empty( $f3['en'] ) && false !== strpos( get_post( $f3['en'] )->post_content, $img ) );

$before_jobs = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$jobs_table} WHERE source_post_id = %d", $src3 ) );
wp_update_post( array( 'ID' => $src3, 'post_status' => 'publish' ) );
qpat_check( 'publish: EN and ZH published together with the original', ! empty( $f3['zh'] ) && 'publish' === get_post_status( $f3['en'] ) && 'publish' === get_post_status( $f3['zh'] ) );
$after_jobs = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$jobs_table} WHERE source_post_id = %d", $src3 ) );
qpat_check( 'publish: unchanged text is not translated again', $before_jobs === $after_jobs );

wp_update_post( array( 'ID' => $src3, 'post_status' => 'draft' ) );
qpat_check( 'unpublish: copies go back to draft', 'draft' === get_post_status( $f3['en'] ) );
wp_update_post( array( 'ID' => $src3, 'post_content' => "Chữ đã sửa\n\n{$img}" ) );
wp_update_post( array( 'ID' => $src3, 'post_status' => 'publish' ) );
$after_edit = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$jobs_table} WHERE source_post_id = %d AND status = 'queued'", $src3 ) );
qpat_check( 'publish: text edited since translation → EN/ZH updated', 2 === $after_edit );
$wpdb->delete( $jobs_table, array( 'source_post_id' => $src3 ) );

qpat_check( 'slug: empty slug falls back to title', 'tin-moi' === QPAT_Sync::source_slug( new WP_Post( (object) array( 'ID' => 1, 'post_name' => '', 'post_title' => 'Tin mới', 'post_type' => 'qp_news' ) ) ) );

// ================================================================ 6. Trang cấu hình
$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
if ( $admins ) {
	wp_set_current_user( (int) $admins[0] );
	$cfg['monthly_limit'] = 500000;
	ob_start();
	QPAT_Admin::instance()->render();
	$html = (string) ob_get_clean();
	foreach ( array( 'Công cụ dịch', 'Gemini API key', 'gemini-test-model', 'Google Cloud API key', 'Kiểm tra Gemini', 'Tải danh sách model Gemini', 'value="500000"', 'Lịch sử dịch gần đây' ) as $needle ) {
		qpat_check( "settings page shows: $needle", false !== strpos( $html, $needle ) );
	}
	qpat_check( 'settings page: gemini radio checked', (bool) preg_match( '/value="gemini"\s+checked=/', $html ) );
	qpat_check( 'settings page never prints keys', false === strpos( $html, 'GEMINI-TEST-KEY' ) && false === strpos( $html, 'TEST-KEY-1234' ) );
	qpat_check( 'settings page does not offer contact messages', false === strpos( $html, 'value="qp_contact_msg"' ) );

	if ( ! empty( $src_id ) ) {
		ob_start();
		QPAT_Editor::instance()->render_metabox( get_post( $src_id ) );
		$box = (string) ob_get_clean();
		qpat_check( 'metabox: links to EN and ZH', (bool) preg_match( '/post=' . $en . '(&|&amp;|&#038;)action=edit/', $box ) && (bool) preg_match( '/post=' . $zh . '(&|&amp;|&#038;)action=edit/', $box ) );
		qpat_check( 'metabox: offers re-translate with overwrite', false !== strpos( $box, 'data-overwrite="1"' ) );
	}
}

// ================================================================ 7. Theo bảng nghiệm thu (DT-xx)
$run_jobs = function ( int $pid ) use ( $wpdb, $jobs_table ): void {
	for ( $k = 0; $k < 5; $k++ ) {
		delete_transient( 'qpat_worker_lock' );
		$left = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$jobs_table} WHERE source_post_id = %d AND status = 'queued'", $pid ) );
		if ( ! $left ) {
			return;
		}
		$wpdb->query( "UPDATE {$jobs_table} SET run_after = '2000-01-01 00:00:00' WHERE status = 'queued'" );
		QPAT_Queue::instance()->process_batch();
	}
};
/** Fields sent to Gemini in the last N calls (all langs). */
$sent_fields = function ( int $from ) use ( &$gemini_calls ): array {
	$out = array();
	foreach ( array_slice( $gemini_calls, $from ) as $c ) {
		$prompt = (string) $c['body']['contents'][0]['parts'][0]['text'];
		$f      = json_decode( substr( $prompt, (int) strrpos( $prompt, "\n\n" ) + 2 ), true );
		foreach ( (array) $f as $k => $v ) {
			if ( '' !== $v ) {
				$out[] = $k . '=' . $v;
			}
		}
	}
	return $out;
};

// Phân đoạn.
$segs = QPAT_Segments::split( "Đoạn 1.\n\nĐoạn 2 dòng a\ndòng b\n\n<table>\n<tr><td>A</td></tr>\n\n<tr><td>B</td></tr>\n</table>\n<p>Đoạn 4</p><h2>Tiêu đề</h2>" );
qpat_check( 'segments: paragraphs split, table kept whole', 5 === count( $segs ) && false !== strpos( $segs[2], '<td>B</td>' ) && '<h2>Tiêu đề</h2>' === $segs[4] );
qpat_check( 'segments: editor reformat (p, nbsp, />) is not an edit', QPAT_Segments::norm( '<p>Xin&nbsp;chào <img src="a.png" /></p>' ) === QPAT_Segments::norm( "Xin chào <img src=\"a.png\">" ) );

// DT-13/14: đăng bài có đủ tiêu đề, mô tả, nội dung, mã nhúng.
$cfg['engine']     = 'gemini';
$cfg['auto_update'] = 1;
$p1 = "Đoạn A ban đầu.\n\nĐoạn B ban đầu.\n\n<iframe src=\"https://www.youtube.com/embed/x\"></iframe>\n\nĐoạn C ban đầu.";
$s7 = wp_insert_post( array( 'post_type' => 'qp_news', 'post_status' => 'publish', 'post_title' => 'QPAT NT tiêu đề', 'post_excerpt' => 'QPAT NT mô tả', 'post_content' => $p1 ) );
$created[] = $s7;
$run_jobs( $s7 );
$f7 = QPAT_Sync::family( $s7 );
$created[] = $f7['en'] ?? 0;
$created[] = $f7['zh'] ?? 0;
$e7 = (int) ( $f7['en'] ?? 0 );
qpat_check( 'DT-13: EN + ZH created on publish', $e7 && ! empty( $f7['zh'] ) );
$c7 = $e7 ? get_post( $e7 )->post_content : '';
qpat_check( 'DT-14: title, excerpt, content translated', $e7 && 0 === strpos( get_post( $e7 )->post_title, '[EN]' ) && 0 === strpos( get_post( $e7 )->post_excerpt, '[EN]' ) && false !== strpos( $c7, '[EN]Đoạn A' ) );
qpat_check( 'DT-14: embed kept, not translated', false !== strpos( $c7, '<iframe src="https://www.youtube.com/embed/x"></iframe>' ) && false === strpos( $c7, '[EN]<iframe' ) );
qpat_check( 'DT-06: link, source version and state stored', $e7 && (int) get_post_meta( $e7, '_qp_llm_source_id', true ) === $s7 && '' !== (string) get_post_meta( $e7, '_qpat_source_revision', true ) && 'auto' === QPAT_Updater::state( $e7 ) );

// DT-12: lưu nháp 3 lần không gọi dịch.
$before = count( $gemini_calls );
$d7     = wp_insert_post( array( 'post_type' => 'qp_news', 'post_status' => 'draft', 'post_title' => 'QPAT NT nháp', 'post_content' => 'x' ) );
$created[] = $d7;
for ( $k = 0; $k < 3; $k++ ) {
	wp_update_post( array( 'ID' => $d7, 'post_content' => 'nháp ' . $k ) );
}
$run_jobs( $d7 );
qpat_check( 'DT-12: saving a draft 3 times calls no API', count( $gemini_calls ) === $before );

// DT-09/10/15: sửa 1 đoạn + thêm 1 đoạn → chỉ gửi đúng 2 đoạn đó.
$before    = count( $gemini_calls );
$gem_used  = QPAT_Usage::used( '', 'gemini' );
wp_update_post( array( 'ID' => $s7, 'post_content' => "Đoạn A ban đầu.\n\nĐoạn B ĐÃ SỬA.\n\n<iframe src=\"https://www.youtube.com/embed/x\"></iframe>\n\nĐoạn C ban đầu.\n\nĐoạn D mới thêm." ) );
$run_jobs( $s7 );
$sent = $sent_fields( $before );
qpat_check( 'DT-09: only the changed and the new paragraph are sent', 4 === count( $sent ) && 2 === count( preg_grep( '/ĐÃ SỬA/u', $sent ) ) && 2 === count( preg_grep( '/mới thêm/u', $sent ) ) );
$chars = QPAT_Usage::used( '', 'gemini' ) - $gem_used;
qpat_check( 'DT-10: characters grow only by the edited part', $chars > 0 && $chars < 2 * ( mb_strlen( 'Đoạn B ĐÃ SỬA.' ) + mb_strlen( 'Đoạn D mới thêm.' ) ) + 10 );
$c7 = get_post( $e7 )->post_content;
qpat_check( 'DT-15: copy updated in place, untouched parts reused', false !== strpos( $c7, '[EN]Đoạn B ĐÃ SỬA.' ) && false !== strpos( $c7, '[EN]Đoạn D mới thêm.' ) && false !== strpos( $c7, '[EN]Đoạn A ban đầu.' ) && false === strpos( $c7, 'Đoạn B ban đầu' ) );

// DT-24/25/26: sửa tay đoạn A ở bản EN, sau đó sửa đoạn C ở bản tiếng Việt.
$vi_before = get_post( $s7 )->post_content;
$before    = count( $gemini_calls );
wp_update_post( array( 'ID' => $e7, 'post_content' => str_replace( '[EN]Đoạn A ban đầu.', 'Paragraph A fixed by hand.', get_post( $e7 )->post_content ) ) );
qpat_check( 'DT-24: hand edit saved, state = edited', false !== strpos( get_post( $e7 )->post_content, 'fixed by hand' ) && 'edited' === QPAT_Updater::state( $e7 ) );
qpat_check( 'DT-25: editing the copy changes nothing in VI, calls no API', get_post( $s7 )->post_content === $vi_before && count( $gemini_calls ) === $before && 0 === QPAT_Queue::instance()->pending_count( false ) );
wp_update_post( array( 'ID' => $s7, 'post_content' => str_replace( 'Đoạn C ban đầu.', 'Đoạn C SỬA LẦN 2.', get_post( $s7 )->post_content ) ) );
$run_jobs( $s7 );
$c7 = get_post( $e7 )->post_content;
qpat_check( 'DT-26: hand-edited paragraph A kept, C updated', false !== strpos( $c7, 'Paragraph A fixed by hand.' ) && false !== strpos( $c7, '[EN]Đoạn C SỬA LẦN 2.' ) );

// DT-27/28: sửa tay đoạn A rồi sửa đoạn A ở bản tiếng Việt → giữ bản sửa tay, đánh dấu cần duyệt.
wp_update_post( array( 'ID' => $s7, 'post_content' => str_replace( 'Đoạn A ban đầu.', 'Đoạn A ĐỔI Ở VI.', get_post( $s7 )->post_content ) ) );
$run_jobs( $s7 );
$c7 = get_post( $e7 )->post_content;
$rv = QPAT_Updater::reviews( $e7 );
qpat_check( 'DT-27: hand text not overwritten, marked for review', false !== strpos( $c7, 'Paragraph A fixed by hand.' ) && false === strpos( $c7, '[EN]Đoạn A ĐỔI' ) && 1 === count( $rv ) && false !== strpos( $rv[0]['suggest'], '[EN]Đoạn A ĐỔI Ở VI.' ) );
qpat_check( 'DT-28: state = review, source flagged', 'review' === QPAT_Updater::state( $e7 ) && in_array( 'review', get_post_meta( $s7, '_qpat_flag' ), true ) );
QPAT_Updater::resolve( $e7, 0, 'accept' );
qpat_check( 'DT-28: accept puts the new translation, state back to auto', false !== strpos( get_post( $e7 )->post_content, '[EN]Đoạn A ĐỔI Ở VI.' ) && array() === QPAT_Updater::reviews( $e7 ) && 'auto' === QPAT_Updater::state( $e7 ) );
QPAT_Updater::set_state( $e7, 'approved' );
qpat_check( 'DT-28: approved state stored', 'approved' === QPAT_Updater::state( $e7 ) );

// DT-16 / DT-11: dữ liệu dùng chung đồng bộ không cần gọi dịch.
$before = count( $gemini_calls );
$cat    = wp_insert_term( 'QPAT NT danh mục ' . wp_generate_password( 4, false ), 'category' );
$tax    = get_object_taxonomies( 'qp_news' );
if ( ! is_wp_error( $cat ) && in_array( 'category', $tax, true ) ) {
	wp_set_object_terms( $s7, array( (int) $cat['term_id'] ), 'category' );
}
update_post_meta( $s7, 'qpat_nt_shared', 'giá trị chung' );
wp_update_post( array( 'ID' => $s7, 'menu_order' => 7 ) );
$run_jobs( $s7 );
qpat_check( 'DT-16: shared field, order synced to copies without API', 'giá trị chung' === get_post_meta( $e7, 'qpat_nt_shared', true ) && 7 === (int) get_post( $e7 )->menu_order && count( $gemini_calls ) === $before );
if ( ! is_wp_error( $cat ) && in_array( 'category', $tax, true ) ) {
	qpat_check( 'DT-16: categories synced', in_array( (int) $cat['term_id'], wp_get_object_terms( $e7, 'category', array( 'fields' => 'ids' ) ), true ) );
	wp_delete_term( (int) $cat['term_id'], 'category' );
}

// DT-18: danh sách + bộ lọc.
$u7 = wp_insert_post( array( 'post_type' => 'qp_news', 'post_status' => 'draft', 'post_title' => 'QPAT NT chưa dịch', 'post_content' => 'x' ) );
$created[] = $u7;
QPAT_Status::refresh( $u7 );
qpat_check( 'DT-18: untranslated post flagged', in_array( 'untranslated', get_post_meta( $u7, '_qpat_flag' ), true ) );
qpat_check( 'DT-18: translated post not flagged untranslated', ! in_array( 'untranslated', get_post_meta( $s7, '_qpat_flag' ), true ) );

// Bản dịch cũ (plugin cũ, không có dữ liệu đoạn) không bị tự ghi đè.
$l7 = wp_insert_post( array( 'post_type' => 'qp_news', 'post_status' => 'publish', 'post_title' => 'QPAT NT bài cũ', 'post_content' => 'Bản cũ.' ) );
$created[] = $l7;
$wpdb->delete( $jobs_table, array( 'source_post_id' => $l7 ) );
$lc = wp_insert_post( array( 'post_type' => 'qp_news', 'post_status' => 'publish', 'post_title' => 'Old copy', 'post_content' => 'Old translation edited by hand.', 'meta_input' => array( '_qp_translation_lang' => 'en', '_qp_llm_source_id' => $l7, '_qp_llm_is_translation' => 1 ) ) );
$created[] = $lc;
wp_update_post( array( 'ID' => $l7, 'post_content' => 'Bản cũ đã sửa.' ) );
$run_jobs( $l7 );
qpat_check( 'legacy copy: never overwritten automatically, marked "cần cập nhật"', 'Old translation edited by hand.' === get_post( $lc )->post_content && 'outdated' === QPAT_Updater::state( $lc ) );
$f_l7 = QPAT_Sync::family( $l7 );
$created[] = $f_l7['zh'] ?? 0;

// DT-29/30: thuật ngữ + tên riêng không dịch (Google).
$cfg['engine']       = 'google';
$cfg['monthly_limit'] = 0;
$cfg['glossary']     = 'Khu đô thị Xanh | Green Urban Area | 绿色新城';
$cfg['no_translate'] = "QP Green Park\nHKT";
$t = QPAT_Translator::translate( array( 'title' => 'QP Green Park tại Khu đô thị Xanh', 'content' => '<p>Mã HKT, dự án QP Green Park.</p>' ), 'en' );
$last = end( $calls );
qpat_check( 'DT-29: glossary term uses the preferred translation', false !== strpos( $t['title'], 'Green Urban Area' ) && false === strpos( $t['title'], 'Khu đô thị Xanh' ) );
qpat_check( 'DT-30: names / codes sent as translate="no" and kept', false !== strpos( (string) wp_json_encode( $last['body']['q'] ), 'translate=\\"no\\"' ) && false !== strpos( $t['content'], 'QP Green Park' ) && false !== strpos( $t['content'], 'HKT' ) && false === strpos( $t['content'], 'QPAT_' ) );
$t = QPAT_Translator::translate( array( 'content' => '<p>Giữ <span class="notranslate">Nguyên văn</span> và <!--notranslate-->Khối này<!--/notranslate--></p>' ), 'en' );
qpat_check( 'DT-30: excluded content (notranslate) not translated', false !== strpos( $t['content'], '<span class="notranslate">Nguyên văn</span>' ) && false !== strpos( $t['content'], '<!--notranslate-->Khối này<!--/notranslate-->' ) );
$cfg['skip_fields'] = 'source';
$kept_meta          = QPAT_Settings::skip_fields();
qpat_check( 'DT-30: skip-field list read', array( 'source' ) === $kept_meta );
$cfg['skip_fields']  = '';
$cfg['glossary']     = '';
$cfg['no_translate'] = '';

// DT-19/20/21: Google đếm ký tự theo ngôn ngữ, cảnh báo, tạm dừng khi đạt giới hạn.
$wpdb->query( 'DELETE FROM ' . QPAT_Usage::table() );
$txt = str_repeat( 'Chữ ', 1000 ); // 4.000 ký tự
QPAT_Translator::translate( array( 'content' => $txt ), 'en' );
QPAT_Translator::translate( array( 'content' => $txt ), 'zh' );
$by = QPAT_Usage::by_lang();
qpat_check( 'DT-19: ~8,000 characters, counted per target language', isset( $by['en'], $by['zh'] ) && abs( ( $by['en'] + $by['zh'] ) - 8000 ) < 200 );
qpat_check( 'DT-20: defaults 500,000 / 80%', 500000 === QPAT_Settings::defaults()['monthly_limit'] && 80 === QPAT_Settings::defaults()['warn_percent'] );
$cfg['monthly_limit'] = 9000;
qpat_check( 'DT-20: warning shown at the threshold', 'warn' === QPAT_Usage::status()['state'] );
$cfg['monthly_limit'] = QPAT_Usage::used() + 10;
$q7 = wp_insert_post( array( 'post_type' => 'qp_news', 'post_status' => 'publish', 'post_title' => 'QPAT NT hạn mức', 'post_content' => $txt ) );
$created[] = $q7;
$before_calls = count( $calls );
$run_jobs( $q7 );
$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$jobs_table} WHERE source_post_id = %d", $q7 ), ARRAY_A );
qpat_check( 'DT-21: jobs paused at the limit, no API call', $rows && 'paused' === $rows[0]['status'] && 'quota' === $rows[0]['error_kind'] && count( $calls ) === $before_calls );
delete_transient( 'qpat_worker_lock' );
QPAT_Queue::instance()->process_batch();
qpat_check( 'DT-21: stays paused until the settings change', 'paused' === $wpdb->get_var( $wpdb->prepare( "SELECT status FROM {$jobs_table} WHERE id = %d", $rows[0]['id'] ) ) );
$cfg['monthly_limit'] = 0;
QPAT_Admin::instance()->settings_changed();
$run_jobs( $q7 );
qpat_check( 'DT-21: resumes after the settings change', 'done' === $wpdb->get_var( $wpdb->prepare( "SELECT status FROM {$jobs_table} WHERE id = %d", $rows[0]['id'] ) ) );
$f_q7 = QPAT_Sync::family( $q7 );
$created[] = $f_q7['en'] ?? 0;
$created[] = $f_q7['zh'] ?? 0;

// DT-07/22/23: lỗi API key → thất bại, ghi loại lỗi; thử lại / huỷ.
$mode = 'error';
$x7   = wp_insert_post( array( 'post_type' => 'qp_news', 'post_status' => 'publish', 'post_title' => 'QPAT NT lỗi key', 'post_content' => 'x' ) );
$created[] = $x7;
$run_jobs( $x7 );
$err = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$jobs_table} WHERE source_post_id = %d ORDER BY id ASC LIMIT 1", $x7 ), ARRAY_A );
qpat_check( 'DT-07/23: wrong key → failed, logged as config error', $err && 'error' === $err['status'] && 'config' === $err['error_kind'] && false !== strpos( (string) $err['message'], 'API key' ) );
$mode = 'ok';
qpat_check( 'DT-22: retry puts the job back in the queue', QPAT_Queue::instance()->retry_job( (int) $err['id'] ) && 'queued' === $wpdb->get_var( $wpdb->prepare( "SELECT status FROM {$jobs_table} WHERE id = %d", $err['id'] ) ) );
qpat_check( 'DT-22: cancel a waiting job', QPAT_Queue::instance()->cancel_job( (int) $err['id'] ) && 'cancelled' === $wpdb->get_var( $wpdb->prepare( "SELECT status FROM {$jobs_table} WHERE id = %d", $err['id'] ) ) );
$sub = wp_insert_user( array( 'user_login' => 'qpat_nt_' . wp_generate_password( 5, false ), 'user_pass' => wp_generate_password(), 'role' => 'subscriber' ) );
if ( ! is_wp_error( $sub ) ) {
	wp_set_current_user( (int) $sub );
	qpat_check( 'DT-22: a user without rights cannot retry / cancel / translate', ! current_user_can( QPAT_Admin::MANAGE_CAP ) && ! current_user_can( 'edit_post', $s7 ) );
	wp_set_current_user( 0 );
	wp_delete_user( (int) $sub );
}
$f_x7 = QPAT_Sync::family( $x7 );
$created[] = $f_x7['en'] ?? 0;
$created[] = $f_x7['zh'] ?? 0;
$cfg['engine'] = 'gemini';

// DT-17/18/23: các màn hình quản trị.
$admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
if ( $admins ) {
	wp_set_current_user( (int) $admins[0] );
	ob_start();
	QPAT_Admin::instance()->render_content();
	$html = (string) ob_get_clean();
	qpat_check( 'DT-18: content list has the 4 filters', false !== strpos( $html, 'Chưa dịch' ) && false !== strpos( $html, 'Cần duyệt' ) && false !== strpos( $html, 'Lỗi' ) && false !== strpos( $html, 'Cần cập nhật' ) );
	$_GET['flag'] = 'untranslated';
	ob_start();
	QPAT_Admin::instance()->render_content();
	$html = (string) ob_get_clean();
	unset( $_GET['flag'] );
	qpat_check( 'DT-18: filter "Chưa dịch" lists the untranslated post only', false !== strpos( $html, 'QPAT NT chưa dịch' ) && false === strpos( $html, 'QPAT NT tiêu đề' ) );
	ob_start();
	QPAT_Admin::instance()->render_log();
	$html = (string) ob_get_clean();
	qpat_check( 'DT-23: log shows status, error kind, retry', false !== strpos( $html, 'Sai cấu hình / API key' ) && false !== strpos( $html, 'Thử lại' ) && false !== strpos( $html, 'Đã huỷ' ) );
	ob_start();
	QPAT_Admin::instance()->render();
	$html = (string) ob_get_clean();
	qpat_check( 'DT-17: settings: keys, languages, publish mode, glossary', false !== strpos( $html, 'Chế độ xuất bản' ) && false !== strpos( $html, 'auto_update' ) && false !== strpos( $html, 'Thuật ngữ, bản dịch ưu tiên' ) && false !== strpos( $html, 'Trường dữ liệu không dịch' ) );
	ob_start();
	QPAT_Editor::instance()->render_metabox( get_post( $e7 ) );
	$box = (string) ob_get_clean();
	qpat_check( 'DT-06: copy box shows state and source version', false !== strpos( $box, 'Trạng thái' ) && false !== strpos( $box, 'phiên bản tiếng Việt' ) );
	wp_set_current_user( 0 );
}

// ---------------------------------------------------------------- dọn dẹp
foreach ( array_filter( $created ) as $pid ) {
	$wpdb->delete( $jobs_table, array( 'source_post_id' => $pid ) );
	wp_delete_post( (int) $pid, true );
}
$wpdb->query( 'DELETE FROM ' . QPAT_Usage::table() );
wp_clear_scheduled_hook( QPAT_Queue::CRON_HOOK );
// Test config is fake: let the real site recompute list flags with its own settings.
delete_option( QPAT_Status::BACKFILL_OPT );

echo $fails ? "\n{$fails} FAILED\n" : "\nALL PASSED\n";
exit( $fails ? 1 : 0 );
