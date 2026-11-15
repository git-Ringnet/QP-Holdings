<?php
// Tạo ảnh mẫu cho Playwright. Chạy: php make-fixtures.php <thư mục đích>
$out = $argv[1] ?? __DIR__ . '/out';
@mkdir( $out, 0777, true );

function noisy( int $w, int $h, int $seed ) {
	$im = imagecreatetruecolor( $w, $h );
	mt_srand( $seed );
	for ( $y = 0; $y < $h; $y += 8 ) {
		for ( $x = 0; $x < $w; $x += 8 ) {
			$c = imagecolorallocate( $im, ( $x * 3 + $seed ) % 256, ( $y * 5 + $seed * 7 ) % 256, ( $x + $y + mt_rand( 0, 60 ) ) % 256 );
			imagefilledrectangle( $im, $x, $y, $x + 7, $y + 7, $c );
		}
	}
	return $im;
}

// Ảnh chụp bình thường (có nhiều chi tiết)
imagejpeg( noisy( 1600, 1067, 11 ), "$out/photo.jpg", 92 );
imagejpeg( noisy( 1600, 1067, 23 ), "$out/photo2.jpg", 92 );
imagejpeg( noisy( 1200, 800, 37 ), "$out/photo3.jpg", 92 );
// Ảnh rất lớn (WP-04)
imagejpeg( noisy( 6000, 4000, 5 ), "$out/big.jpg", 85 );

// PNG nền trong suốt (WP-11)
$im = imagecreatetruecolor( 600, 600 );
imagealphablending( $im, false );
imagesavealpha( $im, true );
imagefill( $im, 0, 0, imagecolorallocatealpha( $im, 0, 0, 0, 127 ) );
for ( $i = 0; $i < 600; $i += 6 ) {
	imageline( $im, $i, 0, 600 - $i, 600, imagecolorallocatealpha( $im, $i % 255, 120, 60, 70 ) );
}
imagefilledellipse( $im, 300, 300, 400, 400, imagecolorallocatealpha( $im, 220, 30, 30, 0 ) );
imagepng( $im, "$out/alpha.png" );

// PNG không trong suốt
imagepng( noisy( 900, 600, 9 ), "$out/flat.png" );

// GIF (WP-12)
$g = imagecreatetruecolor( 120, 80 );
imagefill( $g, 0, 0, imagecolorallocate( $g, 30, 160, 90 ) );
imagegif( $g, "$out/anim.gif" );

// SVG (WP-12)
file_put_contents( "$out/vector.svg", '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="80" viewBox="0 0 120 80"><rect width="120" height="80" fill="#1a3a6b"/><circle cx="60" cy="40" r="24" fill="#d4a24c"/></svg>' );

// File ảnh hỏng (WP-21)
file_put_contents( "$out/broken.jpg", "\xFF\xD8\xFF\xE0" . random_bytes( 4096 ) );
echo "ok\n";
