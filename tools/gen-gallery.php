<?php
/**
 * Sinh ảnh phụ (gallery) kiểu placeholder cho các sản phẩm chưa có ảnh phụ.
 * Chạy 1 lần:  php tools/gen-gallery.php
 * Sản phẩm đã có gallery (vd id=1) được giữ nguyên.
 */

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/admin/product/model.php';

function xmlEscape(string $s): string
{
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

$rows = db()->query('SELECT id, name, code FROM products ORDER BY id')->fetchAll();

/** Bảng màu nâu/trà — [nền, chữ] */
$palette = [
    ['#5d4037', '#ffffff'],
    ['#6d4c41', '#ffffff'],
    ['#8d6e63', '#ffffff'],
    ['#a1887f', '#ffffff'],
    ['#795548', '#ffffff'],
    ['#4e342e', '#ffffff'],
    ['#b8860b', '#ffffff'],
    ['#3e2723', '#ffffff'],
    ['#9c8d81', '#ffffff'],
    ['#7b5e3b', '#ffffff'],
];

$written = 0;
$updated = 0;

foreach ($rows as $p) {
    $existing = productGallery($p['gallery'] ?? null);
    if ($existing) {
        continue; // đã có ảnh phụ
    }

    $gallery = [];
    for ($n = 1; $n <= 3; $n++) {
        $file = sprintf('g%d_%d.svg', (int)$p['id'], $n);
        $dest = UPLOAD_DIR . $file;
        [$bg, $fg] = $palette[((int)$p['id'] + $n) % count($palette)];

        $name  = xmlEscape($p['name']);
        $label = 'Ảnh phụ ' . $n;
        $svg   = '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="500" viewBox="0 0 600 500">' . "\n"
               . '  <rect width="600" height="500" fill="' . $bg . '"/>' . "\n"
               . '  <g fill="' . $fg . '" font-family="Arial, sans-serif" text-anchor="middle">' . "\n"
               . '    <text x="300" y="230" font-size="44" font-weight="bold">' . $name . '</text>' . "\n"
               . '    <text x="300" y="290" font-size="28">' . $label . '</text>' . "\n"
               . '    <text x="300" y="330" font-size="22" opacity="0.75">' . xmlEscape($p['code']) . '</text>' . "\n"
               . '  </g>' . "\n"
               . '</svg>' . "\n";

        if (file_put_contents($dest, $svg) === false) {
            fwrite(STDERR, 'Lỗi ghi file ' . $dest . PHP_EOL);
            exit(1);
        }
        $gallery[] = UPLOAD_URL . $file;
        $written++;
    }

    $stmt = db()->prepare('UPDATE products SET gallery = ? WHERE id = ?');
    $stmt->execute([json_encode($gallery, JSON_UNESCAPED_SLASHES), (int)$p['id']]);
    $updated++;
    echo 'id=' . $p['id'] . ' ' . $p['name'] . PHP_EOL;
}

echo 'Sinh ' . $written . ' file ảnh phụ, cập nhật ' . $updated . ' sản phẩm.' . PHP_EOL;