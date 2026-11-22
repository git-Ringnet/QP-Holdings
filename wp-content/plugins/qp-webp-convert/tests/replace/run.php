<?php
/**
 * Kiểm thử tích hợp "Thay ảnh gốc bằng WebP" trên WordPress local (ghi tạm vào database dev rồi dọn sạch).
 *
 *   C:\xampp\php\php.exe wp-content\plugins\qp-webp-convert\tests\replace\run.php
 */

if ( PHP_SAPI !== 'cli' ) {
	exit;
}
$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 5 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/admin.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';

if ( ! class_exists( 'QPWC_Replace' ) ) {
	require_once dirname( __DIR__, 2 ) . '/qp-webp-convert.php';
	QPWC_Media::init();
	QPWC_Frontend::init();
	QPWC_Bulk::init();
	QPWC_Replace::init();
}
wp_set_current_user( 1 );
$GLOBALS['qpat_suppress_queue'] = true; // không để plugin dịch tự tạo bản EN/ZH cho bài thử

$pass = 0;
$fail = 0;
function ok( bool $cond, string $msg ): void {
	global $pass, $fail;
	if ( $cond ) {
		++$pass;
		echo "PASS {$msg}\n";
	} else {
		++$fail;
		echo "FAIL {$msg}\n";
	}
}

$tag     = 'qpwc-test-' . substr( md5( uniqid( '', true ) ), 0, 6 );
$created = array( 'posts' => array(), 'att' => array(), 'opts' => array() );
$saved_settings = get_option( QPWC_Settings::OPTION );
$saved_state    = get_option( QPWC_Replace::STATE );
QPWC_Settings::update( array( 'enabled' => 1, 'replace_new' => 0, 'backup_at' => time() ) );

/** Tạo ảnh JPG (nhiều chi tiết để WebP nhỏ hơn) hoặc PNG trong suốt rồi đưa vào thư viện. */
function make_attachment( string $name, string $type, int $w = 1800, int $h = 1200 ): int {
	$tmp = wp_tempnam( $name );
	$im  = imagecreatetruecolor( $w, $h );
	if ( 'png' === $type ) {
		imagealphablending( $im, false );
		imagesavealpha( $im, true );
		imagefill( $im, 0, 0, imagecolorallocatealpha( $im, 0, 0, 0, 127 ) );
	}
	for ( $i = 0; $i < 400; $i++ ) {
		$c = imagecolorallocatealpha( $im, mt_rand( 0, 255 ), mt_rand( 0, 255 ), mt_rand( 0, 255 ), 'png' === $type ? mt_rand( 0, 60 ) : 0 );
		imagefilledellipse( $im, mt_rand( 0, $w ), mt_rand( 0, $h ), mt_rand( 20, 300 ), mt_rand( 20, 300 ), $c );
	}
	if ( 'png' === $type ) {
		// Nhiễu giống ảnh chụp: PNG lưu không nén mất dữ liệu nên nặng, WebP nhỏ hơn rõ (như ảnh PNG thật trên website).
		for ( $y = 0; $y < $h; $y += 2 ) {
			for ( $x = 0; $x < $w; $x += 2 ) {
				imagesetpixel( $im, $x, $y, imagecolorallocatealpha( $im, mt_rand( 0, 255 ), mt_rand( 0, 255 ), mt_rand( 0, 255 ), mt_rand( 0, 40 ) ) );
			}
		}
	}
	'png' === $type ? imagepng( $im, $tmp ) : imagejpeg( $im, $tmp, 92 );
	imagedestroy( $im );
	$file = array( 'name' => $name . '.' . $type, 'tmp_name' => $tmp );
	$id   = media_handle_sideload( $file, 0 );
	return is_wp_error( $id ) ? 0 : (int) $id;
}

global $wpdb;
$base = QPWC_Replace::basedir();
$url  = static function ( string $rel ): string {
	return wp_get_upload_dir()['baseurl'] . '/' . $rel;
};

try {
	// ---------------------------------------------------------------- chuẩn bị
	$jpg = make_attachment( $tag . '-anh', 'jpg' );
	$png = make_attachment( $tag . '-logo', 'png', 900, 600 );
	ok( $jpg > 0 && $png > 0, 'tạo 2 ảnh thử (JPG, PNG trong suốt)' );
	$created['att'] = array( $jpg, $png );

	$f      = QPWC_Replace::files_of( $jpg );
	$main   = $f['main'];
	$size1  = $f['sizes'][0] ?? '';
	$pf     = QPWC_Replace::files_of( $png );
	ok( '' !== $size1 && is_file( $base . '/' . $main ), 'ảnh có file gốc và các kích thước' );

	// Bản ghi media thứ 2 dùng chung file (như Polylang tạo bản EN)
	$dup = wp_insert_attachment( array( 'post_title' => 'dup', 'post_mime_type' => 'image/jpeg', 'post_status' => 'inherit' ), $base . '/' . $main );
	update_post_meta( $dup, '_wp_attachment_metadata', wp_get_attachment_metadata( $jpg ) );
	$created['att'][] = $dup;

	$content = '<p><img class="wp-image-' . $jpg . '" src="' . $url( $main ) . '" srcset="' . $url( $main ) . ' 1800w, ' . $url( $size1 ) . ' 300w" alt=""></p>'
		. '<p><a href="' . $url( $main ) . '">tải ảnh</a> <img src="' . $url( $pf['main'] ) . '"></p>'
		. '<!-- wp:image {"url":"' . str_replace( '/', '\\/', $url( $main ) ) . '"} -->'
		. '<p>Tên file dễ nhầm: ' . $url( $main ) . '.bak và x' . basename( $main ) . '</p>';
	$post = wp_insert_post( array( 'post_title' => $tag, 'post_content' => $content, 'post_status' => 'draft', 'post_type' => 'post' ) );
	$created['posts'][] = $post;
	// Lưu thẳng nội dung (wp_insert_post bỏ dấu \ của JSON) để kiểm tra cả dạng đường dẫn \/ như Gutenberg lưu.
	$wpdb->update( $wpdb->posts, array( 'post_content' => $content ), array( 'ID' => $post ) );
	clean_post_cache( $post );
	$content = get_post( $post )->post_content;
	update_post_meta( $post, 'card_image', $url( $size1 ) );
	update_post_meta( $post, $tag . '_arr', array( 'img' => $url( $main ), 'list' => array( $url( $size1 ), 'khác' ) ) );
	update_post_meta( $post, $tag . '_json', wp_json_encode( array( 'src' => $url( $main ) ) ) );
	update_option( $tag . '_opt', array( 'logo' => $url( $pf['main'] ) ) );
	$created['opts'][] = $tag . '_opt';

	// ---------------------------------------------------------------- thay ảnh
	$r = QPWC_Replace::replace_attachment( $jpg );
	ok( 'replaced' === $r['status'], 'thay ảnh JPG: ' . $r['status'] . ' ' . $r['reason'] );
	ok( $r['rows'] >= 4, 'đổi đường dẫn trong database: ' . $r['rows'] . ' dòng' );
	$r2 = QPWC_Replace::replace_attachment( $png );
	ok( 'replaced' === $r2['status'], 'thay ảnh PNG trong suốt: ' . $r2['status'] . ' ' . $r2['reason'] );

	clean_post_cache( $post );
	$c = get_post( $post )->post_content;
	ok( false !== strpos( $c, $url( $main ) . '.webp"' ) && false !== strpos( $c, $url( $size1 ) . '.webp 300w' ), 'nội dung bài: src + srcset đổi sang .webp' );
	ok( false !== strpos( $c, $url( $pf['main'] ) . '.webp' ), 'nội dung bài: ảnh PNG đổi sang .webp' );
	ok( false !== strpos( $c, str_replace( '/', '\\/', $url( $main ) ) . '.webp' ), 'nội dung bài: đường dẫn JSON (\/) cũng đổi' );
	ok( false !== strpos( $c, $url( $main ) . '.bak' ) && false !== strpos( $c, 'x' . basename( $main ) ), 'không đổi nhầm tên file gần giống (.bak, tiền tố x)' );
	ok( substr_count( $c, '.webp.webp' ) === 0, 'không bị lặp .webp.webp' );
	ok( get_post_meta( $post, 'card_image', true ) === $url( $size1 ) . '.webp', 'trường card_image (URL) đổi sang .webp' );
	$arr = get_post_meta( $post, $tag . '_arr', true );
	ok( is_array( $arr ) && $arr['img'] === $url( $main ) . '.webp' && $arr['list'][0] === $url( $size1 ) . '.webp', 'dữ liệu serialize trong postmeta đổi đúng, vẫn đọc được' );
	$js = json_decode( (string) get_post_meta( $post, $tag . '_json', true ), true );
	ok( is_array( $js ) && $js['src'] === $url( $main ) . '.webp', 'JSON trong postmeta đổi đúng' );
	wp_cache_delete( $tag . '_opt', 'options' );
	$op = get_option( $tag . '_opt' );
	ok( is_array( $op ) && $op['logo'] === $url( $pf['main'] ) . '.webp', 'option serialize đổi đúng' );

	clean_post_cache( $jpg );
	ok( 'image/webp' === get_post_mime_type( $jpg ), 'bản ghi ảnh: mime image/webp' );
	ok( substr( wp_get_attachment_url( $jpg ), -9 ) === '.jpg.webp', 'wp_get_attachment_url trả .jpg.webp' );
	$src = wp_get_attachment_image_src( $jpg, 'medium' );
	ok( is_array( $src ) && '.webp' === substr( $src[0], -5 ), 'ảnh cỡ medium trả .webp' );
	clean_post_cache( $dup );
	ok( 'image/webp' === get_post_mime_type( $dup ) && get_post_meta( $dup, '_wp_attached_file', true ) === $main . '.webp', 'bản ghi media dùng chung file (Polylang) cũng được đổi' );
	ok( ! is_file( $base . '/' . $main ) && is_file( $base . '/' . $main . '.webp' ), 'file gốc đã rời thư mục ảnh, file WebP ở đúng chỗ' );
	ok( is_file( QPWC_Replace::backup_root() . '/' . $main ), 'file gốc nằm trong thư mục tạm qpwc-originals' );
	ok( QPWC_Replace::backup_bytes() > 0, 'thống kê dung lượng chờ xóa: ' . size_format( QPWC_Replace::backup_bytes() ) );

	$again = QPWC_Replace::replace_attachment( $jpg );
	ok( 'skipped' === $again['status'], 'chạy lại: ảnh đã thay được bỏ qua' );
	ok( -1 === QPWC_Bulk::cleanup(), 'nút "Xóa toàn bộ file WebP" bị chặn khi đã thay ảnh' );
	ok( is_file( $base . '/' . $main . '.webp' ), 'file WebP vẫn còn sau khi bấm nút bị chặn' );

	// Link cũ → 301 sang .webp
	global $wp_query;
	$wp_query->is_404 = true;
	$_SERVER['REQUEST_URI'] = wp_parse_url( $url( $main ), PHP_URL_PATH );
	$to = '';
	add_filter( 'wp_redirect', static function ( $loc ) use ( &$to ) {
		$to = $loc;
		throw new RuntimeException( 'redirect' );
	} );
	try {
		QPWC_Replace::redirect_old_url();
	} catch ( RuntimeException $e ) {
		// đã chặn exit
	}
	remove_all_filters( 'wp_redirect' );
	$wp_query->is_404 = false;
	ok( $to === $url( $main ) . '.webp', 'link cũ .jpg chuyển hướng 301 sang .jpg.webp' );

	// Frontend không bọc <picture> lần nữa cho ảnh đã là .webp
	$html = QPWC_Frontend::transform( '<img src="' . $url( $main ) . '.webp">', array( 'QPWC_Frontend', 'resolve_webp_url' ) );
	ok( false === strpos( $html, '<picture' ), 'ảnh đã thay không bị bọc <picture> lần nữa' );

	// ---------------------------------------------------------------- hoàn tác
	$u = QPWC_Replace::undo_attachment( $jpg );
	ok( 'restored' === $u['status'], 'hoàn tác ảnh JPG: ' . $u['status'] . ' ' . $u['reason'] );
	QPWC_Replace::undo_attachment( $png );
	clean_post_cache( $post );
	ok( get_post( $post )->post_content === $content, 'hoàn tác: nội dung bài trở về y hệt ban đầu' );
	ok( get_post_meta( $post, 'card_image', true ) === $url( $size1 ), 'hoàn tác: card_image trở về .jpg' );
	clean_post_cache( $jpg );
	ok( 'image/jpeg' === get_post_mime_type( $jpg ) && is_file( $base . '/' . $main ), 'hoàn tác: bản ghi ảnh và file gốc trở về' );
	clean_post_cache( $dup );
	ok( 'image/jpeg' === get_post_mime_type( $dup ), 'hoàn tác: bản ghi media dùng chung cũng trở về' );

	// ---------------------------------------------------------------- chạy hàng loạt + xóa vĩnh viễn
	QPWC_Replace::start( 'replace' );
	$s = QPWC_Replace::state();
	ok( (bool) QPWC_Settings::get( 'replace_new' ), 'bắt đầu thay ảnh → tự bật "ảnh mới tải lên thay luôn bằng WebP"' );
	// chỉ cho lô chạy qua 2 ảnh thử: đặt last_id ngay trước ảnh thử nhỏ nhất
	$s['last_id'] = min( $jpg, $png ) - 1;
	update_option( QPWC_Replace::STATE, $s, false );
	$guard = 0;
	do {
		$s = QPWC_Replace::step( 2, 30 );
	} while ( 'running' === $s['status'] && $s['last_id'] < max( $jpg, $png ) && ++$guard < 20 );
	QPWC_Replace::pause();
	clean_post_cache( $jpg );
	ok( 'image/webp' === get_post_mime_type( $jpg ) && 'image/webp' === get_post_mime_type( $png ), 'chạy theo lô: 2 ảnh thử đã thay' );
	$freed = QPWC_Replace::purge_originals();
	ok( $freed['files'] > 0 && ! is_dir( QPWC_Replace::backup_root() ), 'xóa vĩnh viễn: ' . $freed['files'] . ' file, ' . size_format( $freed['bytes'] ) );
	ok( 'skipped' === QPWC_Replace::undo_attachment( $jpg )['status'], 'sau khi xóa vĩnh viễn: không hoàn tác nữa' );
	ok( is_file( $base . '/' . $main . '.webp' ), 'sau khi xóa vĩnh viễn: ảnh WebP vẫn hiển thị' );

	// ---------------------------------------------------------------- ảnh mới tải lên
	$new = make_attachment( $tag . '-moi', 'jpg', 1600, 1000 );
	$created['att'][] = $new;
	clean_post_cache( $new );
	$nf  = (string) get_post_meta( $new, '_wp_attached_file', true );
	$nm  = wp_get_attachment_metadata( $new );
	ok( 'image/webp' === get_post_mime_type( $new ) && '.jpg.webp' === substr( $nf, -9 ), 'ảnh mới tải lên: tự thành WebP' );
	ok( ! is_file( $base . '/' . substr( $nf, 0, -5 ) ) && is_file( $base . '/' . $nf ), 'ảnh mới tải lên: file JPG gốc đã xóa, file WebP còn' );
	$all_webp = true;
	foreach ( (array) ( $nm['sizes'] ?? array() ) as $sz ) {
		$all_webp = $all_webp && '.webp' === substr( $sz['file'], -5 ) && 'image/webp' === $sz['mime-type'] && is_file( dirname( $base . '/' . $nf ) . '/' . $sz['file'] );
	}
	ok( $all_webp && ! empty( $nm['sizes'] ), 'ảnh mới tải lên: mọi kích thước là WebP và có file' );
} catch ( \Throwable $e ) {
	ok( false, 'Lỗi: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() );
} finally {
	foreach ( $created['posts'] as $p ) {
		wp_delete_post( $p, true );
	}
	foreach ( $created['att'] as $a ) {
		wp_delete_attachment( $a, true );
	}
	foreach ( $created['opts'] as $o ) {
		delete_option( $o );
	}
	// dọn file sót lại của ảnh thử (bản .jpg / .webp / thư mục tạm)
	$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $it as $fi ) {
		if ( $fi->isFile() && false !== strpos( $fi->getFilename(), $tag ) ) {
			@unlink( $fi->getPathname() );
		}
	}
	if ( is_dir( QPWC_Replace::backup_root() ) ) {
		QPWC_Replace::purge_originals();
	}
	false === $saved_settings ? delete_option( QPWC_Settings::OPTION ) : update_option( QPWC_Settings::OPTION, $saved_settings, false );
	false === $saved_state ? delete_option( QPWC_Replace::STATE ) : update_option( QPWC_Replace::STATE, $saved_state, false );
}

echo "\n{$pass} đạt, {$fail} lỗi\n";
echo $fail ? "CÓ LỖI\n" : "TẤT CẢ ĐẠT\n";
exit( $fail ? 1 : 0 );
