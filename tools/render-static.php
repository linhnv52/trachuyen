<?php
/**
 * Render 1 trang PHP -> HTML tĩnh (chạy từng trang trong 1 tiến trình).
 *
 * Args:
 *   --src   file PHP nguồn (vd product.php)
 *   --out   file đích (vd product.html) — dùng làm baseFile cho pageConfig
 *   --type  index|product|page|detail
 *   --id    id sản phẩm (chỉ type=detail)
 *   --map   JSON {id: file} — map chi tiết sản phẩm (id -> slug.html)
 */

$ROOT = dirname(__DIR__);
require_once __DIR__ . '/argv.php';

$src  = $argv['src']  ?? '';
$out  = $argv['out']  ?? '';
$type = $argv['type'] ?? 'page';
$id   = (int)($argv['id'] ?? 0);
$map  = json_decode(base64_decode($argv['map'] ?? ''), true) ?: [];

if ($src === '') {
    fwrite(STDERR, 'Thiếu --src' . PHP_EOL);
    exit(1);
}

// Biến dùng trong template
$GLOBALS['pageStaticConfig'] = ($type === 'product')
    ? ['baseFile' => $out, 'fixedSlug' => staticFixedSlug($src), 'groupIds' => staticGroupIds($src), 'showTabs' => $src !== 'am-tu-sa.php' && $src !== 'hop-qua-tang.php', 'perPage' => 8]
    : null;

function staticFixedSlug(string $src): string
{
    return match ($src) {
        'am-tu-sa.php'     => 'am-tu-sa',
        'hop-qua-tang.php' => 'hop-qua-tang',
        default            => '',
    };
}

function staticGroupIds(string $src): array
{
    require_once dirname(__DIR__) . '/config/db.php';
    require_once dirname(__DIR__) . '/admin/product/model.php';
    $all = getAllCategories();
    if ($src === 'san-pham-tra.php') {
        $slugs = ['tra-xanh', 'tra-den', 'tra-o-long', 'tra-thao-moc', 'phu-kien-tra', 'tra-xanh-viet'];
        return array_values(array_map('intval', array_column(array_filter($all, fn ($c) => in_array($c['slug'], $slugs, true)), 'id')));
    }
    if ($src === 'khai-va-chen.php') {
        return array_values(array_map('intval', array_column(array_filter($all, fn ($c) => $c['slug'] !== 'tra-xanh-viet'), 'id')));
    }
    return []; // product.php: tất cả
}

// Thay dấu trong tham số argv
$ROOT_PATH = $ROOT;

// Bắt đầu render
$_GET = [];
$_SERVER['REQUEST_URI'] = '/' . basename($src);
$_SERVER['SCRIPT_NAME'] = '/' . basename($src);

ob_start();
if ($type === 'detail') {
    $_GET['id'] = $id;
}
require $ROOT_PATH . '/' . $src;
$html = ob_get_clean();

// ---------- Post-process ----------
// 1. Đánh dấu bản tĩnh để JS dùng dataUrl
$html = str_replace('<html lang="vi">', '<html lang="vi" data-static>', $html);

// 2. Link chi tiết sản phẩm theo map TRƯỚC: (productdetal.php?id=N -> slug.html)
$html = preg_replace_callback(
    '#productdetal\.php\?id=(\d+)#',
    function (array $m) use ($map) {
        return $map[(int)$m[1]] ?? $m[0];
    },
    $html
);

// 3. Chuyển link nội bộ .php -> .html (giữ query string)
$html = str_replace('productdetal.php', 'productdetal.html', $html);
$html = preg_replace('#href="([a-zA-Z0-9_\-]+)\.php#', 'href="\1.html', $html);
$html = preg_replace('#action="([a-zA-Z0-9_\-]+)\.php#', 'action="\1.html', $html);

// 4. Bỏ tiền tố gạch đầu ở đường dẫn tuyệt đối nội bộ (url('/img/...') -> img/...)
$html = str_replace('"/img/', '"img/', $html);
$html = str_replace("'/img/", "'img/", $html);
$html = str_replace('"/css/', '"css/', $html);
$html = str_replace('"/js/', '"js/', $html);

// 5. Nạp static-listing.js cho các trang liệt kê (carousel + lọc đã do nó đảm nhiệm)
if ($type === 'product') {
    $html = str_replace('</body>', "<script src=\"js/static-listing.js\"></script>\n</body>", $html);
}

echo $html;