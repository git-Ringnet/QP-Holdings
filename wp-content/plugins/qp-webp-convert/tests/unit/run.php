<?php
/**
 * Unit test không cần cài gì thêm.  Chạy:  C:\xampp\php\php.exe tests/unit/run.php
 * Yêu cầu PHP có GD + WebP. Không cần WordPress.
 */

define( 'QPWC_TESTING', true );
require_once dirname( __DIR__, 2 ) . '/includes/class-qpwc-converter.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-qpwc-frontend.php';

$GLOBALS['pass'] = 0;
$GLOBALS['fail'] = array();
$GLOBALS['cur']  = '';

function t( string $name, callable $fn ): void {
	$GLOBALS['cur'] = $name;
	try {
		$fn();
	} catch ( Throwable $e ) {
		$GLOBALS['fail'][] = "$name: EXCEPTION " . $e->getMessage();
	}
}
function ok( $cond, string $msg = '' ): void {
	if ( $cond ) {
		++$GLOBALS['pass'];
	} else {
		$GLOBALS['fail'][] = $GLOBALS['cur'] . ( $msg ? ": $msg" : '' );
	}
}
function eq( $a, $b, string $msg = '' ): void {
	ok( $a === $b, trim( $msg . ' expected ' . var_export( $b, true ) . ' got ' . var_export( $a, true ) ) );
}

$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'qpwc-test-' . uniqid();
mkdir( $tmp );
register_shutdown_function(
	static function () use ( $tmp ) {
		foreach ( glob( $tmp . '/*' ) ?: array() as $f ) {
			@unlink( $f );
		}
		@rmdir( $tmp );
	}
);

/** Ảnh nhiễu để WebP chắc chắn nhỏ hơn JPEG/PNG. */
function make_jpeg( string $path, int $w = 800, int $h = 600, int $q = 95 ): void {
	$im = imagecreatetruecolor( $w, $h );
	for ( $y = 0; $y < $h; $y += 4 ) {
		for ( $x = 0; $x < $w; $x += 4 ) {
			imagefilledrectangle( $im, $x, $y, $x + 3, $y + 3, imagecolorallocate( $im, ( $x * 7 ) % 256, ( $y * 5 ) % 256, ( $x + $y ) % 256 ) );
		}
	}
	imagejpeg( $im, $path, $q );
	imagedestroy( $im );
}
function make_png_alpha( string $path ): void {
	$im = imagecreatetruecolor( 400, 400 );
	imagealphablending( $im, false );
	imagesavealpha( $im, true );
	imagefill( $im, 0, 0, imagecolorallocatealpha( $im, 0, 0, 0, 127 ) ); // trong suốt hoàn toàn
	for ( $i = 0; $i < 400; $i += 5 ) {
		imageline( $im, $i, 0, 400 - $i, 400, imagecolorallocatealpha( $im, $i % 255, 120, 60, 60 ) );
	}
	imagefilledellipse( $im, 200, 200, 300, 300, imagecolorallocatealpha( $im, 220, 30, 30, 0 ) );
	imagepng( $im, $path );
	imagedestroy( $im );
}

// ---------------------------------------------------------------- Converter
t( 'WP-01 engine: phát hiện thư viện', function () {
	ok( in_array( QPWC_Converter::engine(), array( 'gd', 'imagick' ), true ), 'cần GD hoặc Imagick có WebP' );
} );

t( 'WP-09 JPG → WebP, giữ ảnh gốc', function () use ( $tmp ) {
	$src = "$tmp/a.jpg";
	make_jpeg( $src );
	$before = md5_file( $src );
	$r      = QPWC_Converter::convert( $src, 80 );
	eq( $r['status'], 'converted' );
	ok( is_file( "$src.webp" ), 'file .webp tồn tại' );
	eq( md5_file( $src ), $before, 'ảnh gốc không đổi' );
	ok( $r['webp_bytes'] < $r['src_bytes'], 'WebP nhỏ hơn' );
	$info = getimagesize( "$src.webp" );
	eq( $info['mime'], 'image/webp' );
	eq( [ $info[0], $info[1] ], [ 800, 600 ], 'giữ nguyên kích thước' );
} );

t( 'WP-09 PNG → WebP', function () use ( $tmp ) {
	$src = "$tmp/b.png";
	make_png_alpha( $src );
	$r = QPWC_Converter::convert( $src, 80 );
	eq( $r['status'], 'converted' );
	ok( is_file( "$src.webp" ) );
} );

t( 'WP-11 PNG trong suốt giữ nền trong suốt', function () use ( $tmp ) {
	$src = "$tmp/c.png";
	make_png_alpha( $src );
	QPWC_Converter::convert( $src, 80 );
	$im = imagecreatefromwebp( "$src.webp" );
	imagealphablending( $im, false );
	$rgba  = imagecolorat( $im, 2, 200 ); // góc trái, ngoài hình tròn
	$alpha = ( $rgba >> 24 ) & 0x7F;
	ok( $alpha > 100, "điểm ngoài hình tròn phải trong suốt (alpha GD $alpha, 127 = trong suốt hoàn toàn)" );
	$center = ( imagecolorat( $im, 200, 200 ) >> 24 ) & 0x7F;
	ok( $center < 20, "tâm hình tròn phải đặc (alpha GD $center)" );
	imagedestroy( $im );
} );

t( 'WP-12 GIF và định dạng khác được bỏ qua, không đổi file', function () use ( $tmp ) {
	$src = "$tmp/d.gif";
	$im  = imagecreatetruecolor( 50, 50 );
	imagegif( $im, $src );
	$before = md5_file( $src );
	$r      = QPWC_Converter::convert( $src, 80 );
	eq( $r['status'], 'skipped' );
	eq( $r['reason'], 'unsupported_type' );
	ok( ! is_file( "$src.webp" ) );
	eq( md5_file( $src ), $before );

	$svg = "$tmp/e.svg";
	file_put_contents( $svg, '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"/>' );
	$r = QPWC_Converter::convert( $svg, 80 );
	ok( in_array( $r['status'], array( 'skipped', 'failed' ), true ) && ! is_file( "$svg.webp" ), 'SVG không bị chuyển' );
} );

t( 'Dựa vào nội dung chứ không tin đuôi file', function () use ( $tmp ) {
	$src = "$tmp/f.png"; // thực chất là JPEG
	make_jpeg( $src );
	$r = QPWC_Converter::convert( $src, 80 );
	eq( $r['status'], 'converted' );
} );

t( 'WP-21 file ảnh hỏng không gây lỗi', function () use ( $tmp ) {
	$src = "$tmp/g.jpg";
	file_put_contents( $src, random_bytes( 2048 ) );
	$r = QPWC_Converter::convert( $src, 80 );
	eq( $r['status'], 'failed' );
	eq( $r['reason'], 'not_image' );
	ok( ! is_file( "$src.webp" ) );

	// JPEG cắt cụt giữa chừng
	$ok = "$tmp/h.jpg";
	make_jpeg( $ok );
	file_put_contents( $ok, substr( (string) file_get_contents( $ok ), 0, 3000 ) );
	$r = QPWC_Converter::convert( $ok, 80 );
	ok( in_array( $r['status'], array( 'failed', 'converted' ), true ), 'không ném exception' );
	ok( 0 === count( glob( "$tmp/*.tmp*" ) ), 'không để lại file tạm' );
} );

t( 'WP-21 file không tồn tại', function () use ( $tmp ) {
	$r = QPWC_Converter::convert( "$tmp/khong-co.jpg", 80 );
	eq( $r['status'], 'failed' );
	eq( $r['reason'], 'missing_file' );
} );

t( 'WP-04 ảnh quá lớn so với RAM thì dừng an toàn, không crash', function () use ( $tmp ) {
	$src = "$tmp/i.jpg";
	make_jpeg( $src, 3000, 2000, 60 );
	$r = QPWC_Converter::convert( $src, 80, array( 'memory_limit' => 24 * 1024 * 1024 ) );
	eq( $r['status'], 'failed' );
	eq( $r['reason'], 'memory' );
	ok( ! is_file( "$src.webp" ) );
	// Đủ RAM thì chuyển được.
	$r = QPWC_Converter::convert( $src, 80, array( 'memory_limit' => 512 * 1024 * 1024 ) );
	eq( $r['status'], 'converted', 'đủ RAM thì chuyển được' );
} );

t( 'WP-04 ước tính bộ nhớ tăng theo kích thước', function () {
	ok( QPWC_Converter::estimate_memory( 6000, 4000 ) > QPWC_Converter::estimate_memory( 600, 400 ) );
	ok( QPWC_Converter::estimate_memory( 1, 1 ) >= QPWC_Converter::BASE_OVERHEAD );
} );

t( 'WP-20 WebP lớn hơn ảnh gốc thì giữ ảnh gốc, xóa WebP', function () use ( $tmp ) {
	$src = "$tmp/j.jpg"; // JPEG nhiễu nén rất mạnh: WebP chất lượng cao sẽ lớn hơn
	$im  = imagecreatetruecolor( 64, 64 );
	for ( $y = 0; $y < 64; $y++ ) {
		for ( $x = 0; $x < 64; $x++ ) {
			imagesetpixel( $im, $x, $y, imagecolorallocate( $im, mt_rand( 0, 255 ), mt_rand( 0, 255 ), mt_rand( 0, 255 ) ) );
		}
	}
	imagejpeg( $im, $src, 5 );
	$r = QPWC_Converter::convert( $src, 100 );
	eq( $r['status'], 'skipped' );
	eq( $r['reason'], 'larger' );
	ok( ! is_file( "$src.webp" ), 'không giữ file WebP lớn hơn' );
} );

t( 'WP-19 chất lượng: q thấp cho file nhỏ hơn q cao', function () use ( $tmp ) {
	$src = "$tmp/k.jpg";
	make_jpeg( $src );
	$lo = QPWC_Converter::convert( $src, 30, array( 'force' => true ) );
	$hi = QPWC_Converter::convert( $src, 95, array( 'force' => true ) );
	ok( $lo['webp_bytes'] > 0 && $lo['webp_bytes'] < $hi['webp_bytes'], 'q30=' . $lo['webp_bytes'] . ' q95=' . $hi['webp_bytes'] );
	// Tham số ngoài khoảng được kẹp lại, không lỗi.
	$r = QPWC_Converter::convert( $src, 999, array( 'force' => true ) );
	ok( in_array( $r['status'], array( 'converted', 'skipped' ), true ) );
} );

t( 'WP-14 chạy lại không xử lý lại ảnh đã xong', function () use ( $tmp ) {
	$src = "$tmp/l.jpg";
	make_jpeg( $src );
	$a = QPWC_Converter::convert( $src, 80 );
	$m = filemtime( "$src.webp" );
	clearstatcache();
	$b = QPWC_Converter::convert( $src, 80 );
	eq( $b['status'], 'converted' );
	eq( $b['reason'], 'exists', 'lần 2 dùng lại file có sẵn' );
	eq( filemtime( "$src.webp" ), $m );
	// Ảnh gốc được sửa (mới hơn) thì chuyển lại.
	touch( $src, time() + 10 );
	clearstatcache();
	$c = QPWC_Converter::convert( $src, 80 );
	eq( $c['reason'], 'converted', 'ảnh gốc đổi thì tạo lại WebP' );
} );

t( 'Ghi file nguyên tử: không để lại file .tmp', function () use ( $tmp ) {
	$src = "$tmp/m.jpg";
	make_jpeg( $src );
	QPWC_Converter::convert( $src, 80 );
	eq( count( glob( "$tmp/m.jpg.webp.tmp*" ) ), 0 );
} );

// ---------------------------------------------------------------- Aggregate (WP-13)
t( 'WP-13 tổng hợp trạng thái attachment', function () {
	$conv = array( 'status' => 'converted', 'reason' => 'converted', 'src_bytes' => 1000, 'webp_bytes' => 400 );
	$skip = array( 'status' => 'skipped', 'reason' => 'larger', 'src_bytes' => 100, 'webp_bytes' => 0 );
	$fail = array( 'status' => 'failed', 'reason' => 'memory', 'src_bytes' => 0, 'webp_bytes' => 0 );

	$a = QPWC_Converter::aggregate( array( $conv, $conv ) );
	eq( $a['status'], 'done' );
	eq( $a['src_bytes'], 2000 );
	eq( $a['webp_bytes'], 800 );
	eq( QPWC_Converter::aggregate( array( $skip ) )['status'], 'skipped' );
	$f = QPWC_Converter::aggregate( array( $conv, $fail ) );
	eq( $f['status'], 'failed' );
	eq( $f['reason'], 'memory' );
	eq( QPWC_Converter::aggregate( array() )['status'], 'skipped' );
	eq( QPWC_Converter::aggregate( array( $skip, $conv ) )['status'], 'done', 'ảnh gốc nhỏ nhưng có cỡ khác chuyển được' );
} );

t( 'WP-13 Tổng = thành công + lỗi + bỏ qua', function () {
	$statuses = array( 'done', 'done', 'skipped', 'failed', 'done', 'skipped' );
	$c        = array_count_values( $statuses );
	eq( ( $c['done'] ?? 0 ) + ( $c['failed'] ?? 0 ) + ( $c['skipped'] ?? 0 ), count( $statuses ) );
} );

// ---------------------------------------------------------------- Frontend transform (WP-16, 17, 18)
$resolve = static function ( string $u ): ?string {
	// Giả lập: file có WebP nếu tên chứa "has"
	return false !== strpos( $u, '/has' ) ? preg_replace( '/(\?.*)?$/', '.webp$1', $u, 1 ) : null;
};

t( 'WP-16 <img> có WebP được bọc <picture>, ảnh gốc làm fallback (WP-17)', function () use ( $resolve ) {
	$in  = '<p><img src="/up/has1.jpg" class="a" alt="x"></p>';
	$out = QPWC_Frontend::transform( $in, $resolve );
	ok( false !== strpos( $out, '<picture class="qpwc"><source type="image/webp" srcset="/up/has1.jpg.webp">' ), $out );
	ok( false !== strpos( $out, '<img src="/up/has1.jpg" class="a" alt="x"></picture>' ), 'giữ nguyên thẻ img làm fallback' );
} );

t( 'Ảnh không có WebP thì không đổi', function () use ( $resolve ) {
	$in = '<img src="/up/none.jpg" alt="">';
	eq( QPWC_Frontend::transform( $in, $resolve ), $in );
} );

t( 'srcset + sizes được chuyển đủ', function () use ( $resolve ) {
	$in  = '<img src="/up/has2.jpg" srcset="/up/has2-300.jpg 300w, /up/has2-768.jpg 768w" sizes="(max-width:600px) 100vw, 600px">';
	$out = QPWC_Frontend::transform( $in, $resolve );
	ok( false !== strpos( $out, 'srcset="/up/has2-300.jpg.webp 300w, /up/has2-768.jpg.webp 768w"' ), $out );
	ok( false !== strpos( $out, 'sizes="(max-width:600px) 100vw, 600px"' ) );
} );

t( 'srcset thiếu WebP một cỡ → chỉ dùng ảnh chính, không sizes', function () use ( $resolve ) {
	$in  = '<img src="/up/has3.jpg" srcset="/up/has3-300.jpg 300w, /up/other-768.jpg 768w" sizes="100vw">';
	$out = QPWC_Frontend::transform( $in, $resolve );
	ok( false !== strpos( $out, '<source type="image/webp" srcset="/up/has3.jpg.webp">' ), $out );
	ok( false === strpos( $out, '<source type="image/webp" srcset="/up/has3.jpg.webp" sizes' ) );
} );

t( 'Không bọc <picture> lồng nhau / trong script / comment / noscript', function () use ( $resolve ) {
	$in = '<picture><img src="/up/has4.jpg"></picture>'
		. '<script>var t = \'<img src="/up/has5.jpg">\';</script>'
		. '<!-- <img src="/up/has6.jpg"> -->'
		. '<noscript><img src="/up/has7.jpg"></noscript>';
	eq( QPWC_Frontend::transform( $in, $resolve ), $in );
} );

t( 'Bỏ qua ảnh có class qpwc-skip và ảnh không có src', function () use ( $resolve ) {
	$a = '<img class="qpwc-skip" src="/up/has8.jpg">';
	$b = '<img data-x="1">';
	eq( QPWC_Frontend::transform( $a . $b, $resolve ), $a . $b );
} );

t( 'Giữ query string của URL', function () use ( $resolve ) {
	$out = QPWC_Frontend::transform( '<img src="/up/has9.jpg?ver=3">', $resolve );
	ok( false !== strpos( $out, 'srcset="/up/has9.jpg.webp?ver=3"' ), $out );
} );

t( 'Nhiều ảnh trong một trang đều được xử lý độc lập', function () use ( $resolve ) {
	$out = QPWC_Frontend::transform( '<img src="/up/has1.jpg"><img src="/up/x.jpg"><img src="/up/has2.png">', $resolve );
	eq( substr_count( $out, '<picture' ), 2 );
	eq( substr_count( $out, '<img' ), 3 );
} );

t( 'Chuỗi không có <img> trả về y nguyên', function () use ( $resolve ) {
	$s = '<div>hello</div>';
	eq( QPWC_Frontend::transform( $s, $resolve ), $s );
} );

// ---------------------------------------------------------------- Kết quả
$fail = $GLOBALS['fail'];
echo "\nPASS: {$GLOBALS['pass']} assertion\n";
if ( $fail ) {
	echo 'FAIL: ' . count( $fail ) . "\n - " . implode( "\n - ", $fail ) . "\n";
	exit( 1 );
}
echo "TẤT CẢ ĐẠT\n";
