<?php
/**
 * Làm sạch một bản sao database production để dùng cho máy dev (đưa vào git).
 *
 * KHÔNG BAO GIỜ chạy trên database production.
 *
 * Usage: php tools/sanitize-dev-db.php <db_name> [local_url] [prod_url]
 *   db_name   : database tạm đã import bản dump production (vd. qp_devbuild)
 *   local_url : mặc định http://localhost/qpholdings
 *   prod_url  : mặc định https://qpholdings.vn
 *
 * Kết nối MySQL: root / mật khẩu rỗng / localhost (mặc định XAMPP), đổi bằng biến môi trường
 * QP_DB_USER, QP_DB_PASSWORD, QP_DB_HOST.
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}
$db = $argv[1] ?? '';
if ( '' === $db || ! preg_match( '/^[A-Za-z0-9_]+$/', $db ) ) {
	fwrite( STDERR, "Usage: php sanitize-dev-db.php <db_name> [local_url] [prod_url]\n" );
	exit( 1 );
}
if ( in_array( strtolower( $db ), array( 'qph90680_db' ), true ) ) {
	fwrite( STDERR, "Từ chối: '$db' trùng tên database production.\n" );
	exit( 1 );
}
$local = rtrim( $argv[2] ?? 'http://localhost/qpholdings', '/' );
$prod  = rtrim( $argv[3] ?? 'https://qpholdings.vn', '/' );

mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
$m = new mysqli( getenv( 'QP_DB_HOST' ) ?: 'localhost', getenv( 'QP_DB_USER' ) ?: 'root', getenv( 'QP_DB_PASSWORD' ) ?: '', $db );
$m->set_charset( 'utf8mb4' );
$p = 'wp_';

function q( mysqli $m, string $sql ): void {
	$m->query( $sql );
}
function n( mysqli $m ): int {
	return $m->affected_rows;
}
function tables( mysqli $m, string $like ): array {
	$out = array();
	$res = $m->query( "SHOW TABLES LIKE '" . $m->real_escape_string( $like ) . "'" );
	while ( $row = $res->fetch_row() ) {
		$out[] = $row[0];
	}
	return $out;
}
function get_option_raw( mysqli $m, string $name ): ?string {
	$st = $m->prepare( 'SELECT option_value FROM wp_options WHERE option_name = ?' );
	$st->bind_param( 's', $name );
	$st->execute();
	$r = $st->get_result()->fetch_row();
	return $r ? $r[0] : null;
}
function set_option_raw( mysqli $m, string $name, string $value ): void {
	$st = $m->prepare( 'UPDATE wp_options SET option_value = ? WHERE option_name = ?' );
	$st->bind_param( 'ss', $value, $name );
	$st->execute();
}
function say( string $msg ): void {
	echo $msg, "\n";
}

$dev_email = 'dev@example.test';
const YEAR_IN_SECONDS_DEV = 31536000;

// 1. Dữ liệu cá nhân từ form: xoá bài + meta + quan hệ.
$pii_types = array( 'qp_contact_msg', 'qp_job_app', 'qp_consult_msg', 'qp_newsletter', 'flamingo_contact', 'flamingo_inbound', 'flamingo_outbound' );
$in        = "'" . implode( "','", $pii_types ) . "'";
q( $m, "DELETE pm FROM {$p}postmeta pm JOIN {$p}posts po ON po.ID = pm.post_id WHERE po.post_type IN ($in)" );
q( $m, "DELETE tr FROM {$p}term_relationships tr JOIN {$p}posts po ON po.ID = tr.object_id WHERE po.post_type IN ($in)" );
q( $m, "DELETE FROM {$p}posts WHERE post_type IN ($in)" );
say( 'Xoá bài chứa dữ liệu cá nhân (form): ' . n( $m ) );

// Revision không cần cho dev.
q( $m, "DELETE pm FROM {$p}postmeta pm JOIN {$p}posts po ON po.ID = pm.post_id WHERE po.post_type = 'revision'" );
q( $m, "DELETE FROM {$p}posts WHERE post_type = 'revision'" );
say( 'Xoá revision: ' . n( $m ) );

// Bình luận: bỏ email/IP người bình luận.
q( $m, "UPDATE {$p}comments SET comment_author_email = '', comment_author_IP = '', comment_agent = ''" );

// 2. Bảng log / bảo mật / backup của plugin: làm rỗng.
$truncate = array_merge(
	tables( $m, "{$p}wf%" ),
	tables( $m, "{$p}wsal%" ),
	tables( $m, "{$p}fsmpt%" ),
	tables( $m, "{$p}duplicator%" ),
	tables( $m, "{$p}wpfm%" ),
	tables( $m, "{$p}litespeed%" ),
	tables( $m, "{$p}actionscheduler_logs" ),
	tables( $m, "{$p}rank_math_internal_links" ) // cache, Rank Math tự tạo lại
);
foreach ( $truncate as $t ) {
	q( $m, "TRUNCATE TABLE `$t`" );
}
say( 'Làm rỗng ' . count( $truncate ) . ' bảng log/bảo mật/backup' );

// 3. Option chứa khoá, token, license, transient.
$del_options = array( 'qp_llm_translate_worker_token', 'fm_key', 'secret_key', 'recovery_keys', 'duplicator_pro_package_active', 'mailserver_login' );
foreach ( $del_options as $o ) {
	$st = $m->prepare( "DELETE FROM {$p}options WHERE option_name = ?" );
	$st->bind_param( 's', $o );
	$st->execute();
}
q( $m, "DELETE FROM {$p}options WHERE option_name LIKE 'rpm-wpc-license%' OR option_name LIKE '\\_transient\\_%' OR option_name LIKE '\\_site\\_transient\\_%' OR option_name LIKE 'wordfence%' OR option_name LIKE 'filemanager_email_verified%'" );
say( 'Xoá option khoá/token/license/transient' );

// API key của plugin dịch cũ.
$raw = get_option_raw( $m, 'qp_llm_translate_settings' );
if ( null !== $raw ) {
	$v = @unserialize( $raw );
	if ( is_array( $v ) ) {
		$v['api_key'] = '';
		set_option_raw( $m, 'qp_llm_translate_settings', serialize( $v ) );
		say( 'Xoá API key qp_llm_translate_settings' );
	}
}

// Email nhận form → email giả (máy dev không gửi mail tới khách thật).
$raw = get_option_raw( $m, 'qp_holdings_mail_settings' );
if ( null !== $raw ) {
	$v = @unserialize( $raw );
	if ( is_array( $v ) ) {
		foreach ( array( 'from_email', 'contact_to', 'consult_to', 'newsletter_to' ) as $k ) {
			if ( isset( $v[ $k ] ) ) {
				$v[ $k ] = $dev_email;
			}
		}
		set_option_raw( $m, 'qp_holdings_mail_settings', serialize( $v ) );
	}
}
foreach ( array( 'admin_email', 'new_admin_email' ) as $o ) {
	set_option_raw( $m, $o, $dev_email );
}
// Không hiện màn hình "xác nhận email quản trị" sau khi đăng nhập trên máy dev.
q( $m, "DELETE FROM {$p}options WHERE option_name = 'admin_email_lifespan'" );
q( $m, "INSERT INTO {$p}options (option_name, option_value, autoload) VALUES ('admin_email_lifespan', '" . ( time() + 10 * YEAR_IN_SECONDS_DEV ) . "', 'yes')" );
say( "Email nhận form/quản trị → $dev_email" );

// 4. Plugin không cần ở local.
$off = array( 'wordfence/wordfence.php', 'litespeed-cache/litespeed-cache.php', 'wp-security-audit-log/wp-security-audit-log.php', 'duplicator-pro/duplicator-pro.php' );
$ap  = @unserialize( (string) get_option_raw( $m, 'active_plugins' ) );
if ( is_array( $ap ) ) {
	$ap = array_values( array_diff( $ap, $off ) );
	// Plugin dịch đang phát triển: bật sẵn trên máy dev.
	if ( ! in_array( 'qp-auto-translate/qp-auto-translate.php', $ap, true ) ) {
		$ap[] = 'qp-auto-translate/qp-auto-translate.php';
	}
	set_option_raw( $m, 'active_plugins', serialize( $ap ) );
	say( 'Plugin bật: ' . implode( ', ', $ap ) );
}

// 5. Tài khoản: mật khẩu chung cho local, email giả, xoá phiên đăng nhập.
q( $m, "UPDATE {$p}users SET user_pass = MD5('admin123'), user_email = CONCAT(user_login, '@example.test'), user_url = '', user_activation_key = ''" );
q( $m, "DELETE FROM {$p}usermeta WHERE meta_key IN ('session_tokens', '_application_passwords', 'wfls-last-login') OR meta_key LIKE 'wfls%'" );
$res = $m->query( "SELECT user_login FROM {$p}users ORDER BY ID" );
$logins = array();
while ( $r = $res->fetch_row() ) {
	$logins[] = $r[0];
}
say( 'Tài khoản (mật khẩu admin123): ' . implode( ', ', $logins ) );

// 6. Đổi URL production → local (giữ đúng dữ liệu serialize).
$pairs = array(
	$prod                                   => $local,
	str_replace( 'https://', 'http://', $prod ) => $local,
	str_replace( '://', '://www.', $prod )  => $local,
	str_replace( '/', '\\/', $prod )        => str_replace( '/', '\\/', $local ),
);

function replace_deep( $data, array $pairs ) {
	if ( is_string( $data ) ) {
		$u = @unserialize( $data );
		if ( false !== $u || 'b:0;' === $data ) {
			return serialize( replace_deep( $u, $pairs ) );
		}
		return strtr( $data, $pairs );
	}
	if ( is_array( $data ) ) {
		$out = array();
		foreach ( $data as $k => $v ) {
			$out[ is_string( $k ) ? strtr( $k, $pairs ) : $k ] = replace_deep( $v, $pairs );
		}
		return $out;
	}
	if ( is_object( $data ) && ! ( $data instanceof __PHP_Incomplete_Class ) ) {
		foreach ( get_object_vars( $data ) as $k => $v ) {
			$data->$k = replace_deep( $v, $pairs );
		}
	}
	return $data;
}

$targets = array(
	array( "{$p}posts", 'ID', array( 'post_content', 'post_excerpt', 'guid', 'post_content_filtered' ) ),
	array( "{$p}postmeta", 'meta_id', array( 'meta_value' ) ),
	array( "{$p}options", 'option_id', array( 'option_value' ) ),
	array( "{$p}termmeta", 'meta_id', array( 'meta_value' ) ),
	array( "{$p}usermeta", 'umeta_id', array( 'meta_value' ) ),
	array( "{$p}comments", 'comment_ID', array( 'comment_content', 'comment_author_url' ) ),
	array( "{$p}term_taxonomy", 'term_taxonomy_id', array( 'description' ) ),
);
$host = parse_url( $prod, PHP_URL_HOST );
foreach ( $targets as $t ) {
	list( $table, $pk, $cols ) = $t;
	$changed = 0;
	foreach ( $cols as $col ) {
		$res = $m->query( "SELECT `$pk`, `$col` FROM `$table` WHERE `$col` LIKE '%" . $m->real_escape_string( $host ) . "%'" );
		while ( $row = $res->fetch_row() ) {
			$new = replace_deep( $row[1], $pairs );
			if ( $new !== $row[1] ) {
				$st = $m->prepare( "UPDATE `$table` SET `$col` = ? WHERE `$pk` = ?" );
				$st->bind_param( 'si', $new, $row[0] );
				$st->execute();
				++$changed;
			}
		}
	}
	say( "Đổi URL trong $table: $changed dòng" );
}
set_option_raw( $m, 'siteurl', $local );
set_option_raw( $m, 'home', $local );

say( 'XONG' );
