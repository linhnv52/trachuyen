<?php
/**
 * Render nhiều trang PHP -> HTML tĩnh trong 1 tiến trình duy nhất.
 * Mỗi trang chạy trong closure để cô lập biến (tránh leak giữa các trang).
 *
 * Args:
 *   --pages  base64(JSON array) mỗi item: {src, out, type, id?}
 *   --map    base64(JSON {id: file}) — map chi tiết sản phẩm
 */

$ROOT = dirname(__DIR__);
require_once __DIR__ . '/argv.php';

$pages = json_decode(base64_decode($argv['pages'] ?? ''), true) ?: [];
$map   = json_decode(base64_decode($argv['map'] ?? ''), true) ?: [];

if (!$pages) {
    fwrite(STDERR, 'Thiếu --pages' . PHP_EOL);
    exit(1);
}

// Load functions 1 lần (require_once inside pages sẽ skip)
require_once $ROOT . '/config/db.php';
require_once $ROOT . '/admin/product/model.php';

// ---------- Helpers (copy từ render-static.php) ----------
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
    $rootSlug = match ($src) {
        'san-pham-tra.php' => 'tra',
        'khai-va-chen.php' => 'gomsu',
        default            => '',
    };
    if ($rootSlug !== '') {
        return array_values(array_map('intval', array_column(categoriesUnderRoot($rootSlug), 'id')));
    }
    return [];
}

function renderBatchPostProcess(string $html, string $type, string $src, array $map): string
{
    $html = str_replace('<html lang="vi">', '<html lang="vi" data-static>', $html);

    $html = preg_replace_callback(
        '#productdetal\.php\?id=(\d+)#',
        function (array $m) use ($map) {
            return $map[(int)$m[1]] ?? $m[0];
        },
        $html
    );

    $html = str_replace('productdetal.php', 'productdetal.html', $html);
    $html = preg_replace('#href="([a-zA-Z0-9_\-]+)\.php#', 'href="\1.html', $html);
    $html = preg_replace('#action="([a-zA-Z0-9_\-]+)\.php#', 'action="\1.html', $html);

    $html = str_replace('"/img/', '"img/', $html);
    $html = str_replace("'/img/", "'img/", $html);
    $html = str_replace('"/css/', '"css/', $html);
    $html = str_replace('"/js/', '"js/', $html);

    if ($type === 'product') {
        $html = str_replace('</body>', "<script src=\"js/static-listing.js\"></script>\n</body>", $html);
    }

    return $html;
}

// ---------- Render từng trang ----------
$count = 0;
foreach ($pages as $pg) {
    $src  = $pg['src']  ?? '';
    $out  = $pg['out']  ?? '';
    $type = $pg['type'] ?? 'page';
    $id   = (int)($pg['id'] ?? 0);

    if ($src === '' || $out === '') continue;

    $html = (function () use ($ROOT, $src, $out, $type, $id, $map) {
        $pageStaticConfig = ($type === 'product')
            ? ['baseFile' => $out, 'fixedSlug' => staticFixedSlug($src), 'groupIds' => staticGroupIds($src), 'showTabs' => $src !== 'am-tu-sa.php' && $src !== 'hop-qua-tang.php', 'perPage' => 8]
            : null;

        $_GET = [];
        $_SERVER['REQUEST_URI'] = '/' . basename($src);
        $_SERVER['SCRIPT_NAME'] = '/' . basename($src);

        if ($type === 'detail') {
            $_GET['id'] = $id;
        }

        ob_start();
        require $ROOT . '/' . $src;
        return ob_get_clean();
    })();

    $html = renderBatchPostProcess($html, $type, $src, $map);

    $outPath = $ROOT . '/docs/' . $out;
    $outDir  = dirname($outPath);
    if (!is_dir($outDir)) {
        mkdir($outDir, 0777, true);
    }
    file_put_contents($outPath, $html);
    echo 'Wrote ' . $out . ' (' . strlen($html) . ' bytes)' . PHP_EOL;
    $count++;
}

echo 'Batch rendered ' . $count . ' pages.' . PHP_EOL;
