<?php
/**
 * Sinh favicon.ico (16px + 32px) từ img/favicon.jpg
 * Chạy: php tools/make-favicon.php
 */

$src  = __DIR__ . '/../img/favicon.jpg';
$out  = __DIR__ . '/../favicon.ico';
if (!is_file($src)) {
    fwrite(STDERR, "Khong tim thay {$src}\n");
    exit(1);
}

$jpg = imagecreatefromjpeg($src);
if (!$jpg) {
    fwrite(STDERR, "Loi doc file JPG\n");
    exit(1);
}

$sizes = [16, 32];
$images = [];
foreach ($sizes as $size) {
    $img = imagecreatetruecolor($size, $size);
    imagealphablending($img, false);
    imagesavealpha($img, true);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);
    imagecopyresampled($img, $jpg, 0, 0, 0, 0, $size, $size, imagesx($jpg), imagesy($jpg));
    $images[$size] = $img;
}

function build_bmp_ico_chunk(GdImage $im): string
{
    $w = imagesx($im);
    $h = imagesy($im);
    $bi = pack(
        'VVVvvVVVVvv',
        40,          // BITMAPINFOHEADER size
        $w,
        $h * 2,      // XOR + AND mask
        1,           // planes
        32,          // bitcount
        0,           // compression (BI_RGB)
        $w * $h * 4,
        0, 0, 0, 0
    );
    $pixels = '';
    for ($y = $h - 1; $y >= 0; $y--) { // bottom-up BGR(A)
        $row = '';
        for ($x = 0; $x < $w; $x++) {
            $rgb = imagecolorat($im, $x, $y);
            $row .= chr(($rgb & 0xFF) & 0xFF)          // B
                 . chr((($rgb >> 8) & 0xFF) & 0xFF)    // G
                 . chr((($rgb >> 16) & 0xFF) & 0xFF)   // R
                 . chr((($rgb >> 24) & 0xFF) & 0xFF);  // A
        }
        $pixels .= $row;
    }
    $mask = str_repeat("\x00", intdiv($w + 31, 32) * 4 * $h);
    return $bi . $pixels . $mask;
}

$count = count($images);
$offset = 6 + 16 * $count;
$header = pack('vvv', 0, 1, $count);
$entries = '';
$data = '';
foreach ($sizes as $size) {
    $chunk = build_bmp_ico_chunk($images[$size]);
    $w = $size >= 256 ? 0 : $size;
    $entries .= pack('CCCCvvVV', $w, $w, 0, 0, 1, 32, strlen($chunk), $offset);
    $data .= $chunk;
    $offset += strlen($chunk);
}

file_put_contents($out, $header . $entries . $data);
echo "Wrote {$out}\n";

foreach ($images as $img) {
    imagedestroy($img);
}
imagedestroy($jpg);