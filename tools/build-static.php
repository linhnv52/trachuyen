<?php
/**
 * Build bản tĩnh (GitHub Pages) từ PHP + MySQL.
 *
 * Chạy:  php tools/build-static.php
 * Đầu ra: docs/  (được phục vụ bởi GitHub Pages từ /docs)
 *
 * Các bước:
 *   1. Xuất data/products.json (sản phẩm + danh mục + đường dẫn chi tiết tĩnh).
 *   2. Copy asset tĩnh (style.css, css/, js/, img/, favicon...).
 *   3. Render từng trang PHP -> .html (chạy 1 tiến trình con mỗi trang để tránh khai báo trùng hàm).
 *   4. Render từng trang chi tiết sản phẩm -> <slug>.html.
 */

$ROOT = dirname(__DIR__);
$OUT  = $ROOT . '/docs';

require_once $ROOT . '/config/db.php';
require_once $ROOT . '/admin/product/model.php';

function ensureDir(string $dir): void
{
    if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
        fwrite(STDERR, 'Không tạo được thư mục: ' . $dir . PHP_EOL);
        exit(1);
    }
}

$php = PHP_BINARY;
if (!$php || !is_file($php)) {
    $candidates = ['C:\\laragon\\bin\\php\\php-8.1.10-Win32-vs16-x64\\php.exe'];
    foreach ($candidates as $c) {
        if (is_file($c)) { $php = $c; break; }
    }
}

// ============ 1. data/products.json ============
$products = db()->query('SELECT p.*, c.name AS category_name, c.slug AS category_slug
                         FROM products p
                         LEFT JOIN categories c ON c.id = p.category_id
                         WHERE p.is_active = 1
                         ORDER BY p.created_at DESC')->fetchAll();

$slugUsed = [];
function detailFileFor(array $p, array &$slugUsed): string
{
    $base = trim((string)($p['slug'] ?? ''));
    if ($base === '') {
        $base = 'san-pham-' . (int)$p['id'];
    }
    $base = preg_replace('/[^a-z0-9\-]+/', '-', strtolower($base));
    $base = trim($base, '-') ?: 'san-pham-' . (int)$p['id'];
    if (isset($slugUsed[$base])) {
        $base .= '-' . (int)$p['id'];
    }
    $slugUsed[$base] = true;
    return $base . '.html';
}

$prodFiles = []; // id => file
foreach ($products as $p) {
    $prodFiles[(int)$p['id']] = detailFileFor($p, $slugUsed);
}

function badgeParts(string $badge): array
{
    $label = match ($badge) { 'hot' => 'Hot', 'sale' => 'Sale', 'new' => 'Mới', default => '' };
    $class = $badge === 'sale' ? 'sale' : ($badge === 'new' ? 'new' : '');
    return [$label, $class];
}

$jsonProducts = [];
foreach ($products as $p) {
    [$label, $class] = badgeParts((string)$p['badge']);
    $discount = ($p['old_price'] && $p['old_price'] > $p['price'])
        ? (int)round((1 - $p['price'] / $p['old_price']) * 100)
        : 0;
    $img = trim((string)$p['image_url']);
    if ($img === '') $img = 'img/placeholder.svg';
    $jsonProducts[] = [
        'id'             => (int)$p['id'],
        'code'           => $p['code'],
        'name'           => $p['name'],
        'price'          => (float)$p['price'],
        'old_price'      => $p['old_price'] !== null ? (float)$p['old_price'] : null,
        'discount'       => $discount,
        'badge'          => $p['badge'],
        'badge_label'    => $label,
        'badge_class'    => $class,
        'image_url'      => $img,
        'category_id'    => (int)$p['category_id'],
        'category_name'  => $p['category_name'],
        'category_slug'  => $p['category_slug'],
        'stock_quantity' => (int)$p['stock_quantity'],
        'views'          => (int)$p['views'],
        'capacity'       => $p['capacity'] !== null ? (int)$p['capacity'] : null,
        'description'    => $p['description'],
        'detail_url'     => $prodFiles[(int)$p['id']],
    ];
}

$cats = array_map(function (array $c) {
    return ['id' => (int)$c['id'], 'name' => $c['name'], 'slug' => $c['slug']];
}, getAllCategories(true));

ensureDir($OUT . '/data');
file_put_contents(
    $OUT . '/data/products.json',
    json_encode(['products' => $jsonProducts, 'categories' => $cats], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
);
echo 'data/products.json: ' . count($jsonProducts) . ' products' . PHP_EOL;

// ============ 2. Copy assets ============
function copyDir(string $src, string $dst): void
{
    if (!is_dir($src)) return;
    if (!is_dir($dst) && !mkdir($dst, 0777, true)) return;
    foreach (scandir($src) as $item) {
        if ($item === '.' || $item === '..') continue;
        $s = $src . '/' . $item;
        $d = $dst . '/' . $item;
        if (is_dir($s)) {
            copyDir($s, $d);
        } else {
            copy($s, $d);
        }
    }
}

foreach (['style.css', 'favicon.ico'] as $fname) {
    $f = $ROOT . '/' . $fname;
    if (is_file($f)) copy($f, $OUT . '/' . $fname);
}
copyDir($ROOT . '/css', $OUT . '/css');
copyDir($ROOT . '/js', $OUT . '/js');
copyDir($ROOT . '/img', $OUT . '/img');
echo 'Copied assets' . PHP_EOL;

// ============ 3.+4. Render pages (batch 1 process) ============
function runRender(string $php, string $root, string $out, array $args): bool
{
    $cmd = escapeshellarg($php) . ' ' . escapeshellarg($root . '/tools/render-static.php');
    foreach ($args as $k => $v) {
        $cmd .= ' --' . $k . ' ' . escapeshellarg((string)$v);
    }
    $cmd .= ' 2>&1';
    $outFile = $out . '/' . $args['out'];
    $html = shell_exec($cmd);
    if ($html === null || $html === '') {
        fwrite(STDERR, 'Render thất bại: ' . $args['out'] . PHP_EOL);
        return false;
    }
    file_put_contents($outFile, $html);
    echo 'Wrote ' . $args['out'] . ' (' . strlen($html) . ' bytes)' . PHP_EOL;
    return true;
}

// Thu thập tất cả trang (nội dung + chi tiết) vào 1 batch
$allPages = [];

// Trang nội dung
$contentPages = [
    ['src' => 'index.php',                 'out' => 'index.html',                 'type' => 'index'],
    ['src' => 'product.php',               'out' => 'product.html',               'type' => 'product'],
    ['src' => 'san-pham-tra.php',          'out' => 'san-pham-tra.html',          'type' => 'product'],
    ['src' => 'khai-va-chen.php',          'out' => 'khai-va-chen.html',          'type' => 'product'],
    ['src' => 'am-tu-sa.php',              'out' => 'am-tu-sa.html',              'type' => 'product'],
    ['src' => 'hop-qua-tang.php',          'out' => 'hop-qua-tang.html',          'type' => 'product'],
    ['src' => 'thong-tin-tra.php',         'out' => 'thong-tin-tra.html',         'type' => 'page'],
];
$allPages = array_merge($allPages, $contentPages);

// Trang chi tiết sản phẩm
foreach ($products as $p) {
    $allPages[] = [
        'src'  => 'productdetal.php',
        'out'  => $prodFiles[(int)$p['id']],
        'type' => 'detail',
        'id'   => (int)$p['id'],
    ];
}

// Render batch — 1 tiến trình duy nhất
$pagesJson = base64_encode(json_encode($allPages));
$mapJson   = base64_encode(json_encode($prodFiles));
$batchCmd  = escapeshellarg($php) . ' ' . escapeshellarg($ROOT . '/tools/render-batch.php')
           . ' --pages ' . escapeshellarg($pagesJson)
           . ' --map ' . escapeshellarg($mapJson)
           . ' 2>&1';
$batchOut = shell_exec($batchCmd);
echo $batchOut ?: 'Batch render: không có output' . PHP_EOL;

echo "Done. Output: $OUT" . PHP_EOL;
