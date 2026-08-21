<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/admin/product/model.php';

$pageTitle = 'Thông tin về trà - Trà Chuyện';
$active = 'about';

// Nội dung: settings (admin sửa) đè lên mặc định
$teaDefaults = require __DIR__ . '/includes/tea-info-defaults.php';
$tea = [];
foreach ($teaDefaults as $key => $val) {
    $tea[$key] = getSetting($key, $val);
}

/** Tách dòng bỏ dòng rỗng */
function teaLines(string $text): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text)), fn ($l) => $l !== ''));
}

/** Escape 1 dòng, in đậm phần trước dấu ":" đầu tiên nếu có */
function teaLine(string $line): string
{
    if (preg_match('/^([^:]{1,24}):\s*(.+)$/u', $line, $m)) {
        return '<strong>' . e($m[1]) . ':</strong> ' . e($m[2]);
    }
    return e($line);
}

/** Map tên danh mục (không dấu) => slug để card trà tự gắn link */
$teaCatMap = [];
foreach (getAllCategories() as $c) {
    $teaCatMap[normalizeText($c['name'])] = $c['slug'];
}
function teaCardLink(string $name, array $map): string
{
    return isset($map[normalizeText($name)]) ? 'product.php?category=' . urlencode($map[normalizeText($name)]) : 'product.php';
}

require __DIR__ . '/includes/header.php';
?>

<div class="container body-container tea-info-page">
    <h2 class="section-title">THÔNG TIN VỀ TRÀ</h2>

    <!-- ====== CÁC LOẠI TRÀ ====== -->
    <section class="tea-info-section">
        <h3 class="tea-info-heading"><i class="fas fa-mug-hot"></i> <?= e($tea['tea_s1_title']) ?></h3>
        <div class="tea-type-grid">
            <?php foreach (teaLines($tea['tea_s1_cards']) as $card):
                [$cardName, $cardDesc] = array_pad(explode('|', $card, 2), 2, '');
                ?>
                <div class="tea-type-card">
                    <h4><?= e(trim($cardName)) ?></h4>
                    <p><?= e(trim($cardDesc)) ?></p>
                    <a href="<?= e(teaCardLink(trim($cardName), $teaCatMap)) ?>">Xem sản phẩm →</a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ====== CHỌN TRÀ ====== -->
    <section class="tea-info-section">
        <h3 class="tea-info-heading"><i class="fas fa-leaf"></i> <?= e($tea['tea_s2_title']) ?></h3>
        <ul class="tea-info-list">
            <?php foreach (teaLines($tea['tea_s2_items']) as $item): ?>
                <li><?= teaLine($item) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>

    <!-- ====== PHA TRÀ ====== -->
    <section class="tea-info-section">
        <h3 class="tea-info-heading"><i class="fas fa-fire-flame-simple"></i> <?= e($tea['tea_s3_title']) ?></h3>
        <ol class="tea-info-steps">
            <?php foreach (teaLines($tea['tea_s3_items']) as $step): ?>
                <li><?= teaLine($step) ?></li>
            <?php endforeach; ?>
        </ol>
    </section>

    <!-- ====== BẢO QUẢN ====== -->
    <section class="tea-info-section">
        <h3 class="tea-info-heading"><i class="fas fa-box-open"></i> <?= e($tea['tea_s4_title']) ?></h3>
        <ul class="tea-info-list">
            <?php foreach (teaLines($tea['tea_s4_items']) as $item): ?>
                <li><?= teaLine($item) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>

<?php
$extraScript = '';
require __DIR__ . '/includes/footer.php';
?>
