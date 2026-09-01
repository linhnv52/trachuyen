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
    if (!isset($map[normalizeText($name)])) {
        return 'product.php';
    }
    return categoryPageUrl($map[normalizeText($name)]);
}

require __DIR__ . '/includes/header.php';
?>

<div class="container body-container tea-info-page">
    <h2 class="section-title">THÔNG TIN VỀ TRÀ</h2>

    <!-- ====== LAYOUT 2 CỘT: DANH MỤC | NỘI DUNG ====== -->
    <div class="tea-info-split">

        <!-- Cột trái: danh mục -->
        <aside class="tea-info-nav">
            <button type="button" class="tea-nav-btn active" data-cat="tra"><i class="fas fa-leaf"></i> TRÀ</button>
            <button type="button" class="tea-nav-btn" data-cat="gomsu"><i class="fas fa-mug-saucer"></i> GỐM SỨ</button>
            <button type="button" class="tea-nav-btn" data-cat="amtusa"><i class="fas fa-wine-bottle"></i> ẤM TỬ SA</button>
        </aside>

        <!-- Cột phải: nội dung -->
        <div class="tea-info-content">

            <!-- ====== PANEL TRÀ ====== -->
            <section class="tea-info-panel active" data-panel="tra">
                <div class="tea-info-section">
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
                </div>

                <div class="tea-info-section">
                    <h3 class="tea-info-heading"><i class="fas fa-magnifying-glass"></i> <?= e($tea['tea_s2_title']) ?></h3>
                    <ul class="tea-info-list">
                        <?php foreach (teaLines($tea['tea_s2_items']) as $item): ?>
                            <li><?= teaLine($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="tea-info-section">
                    <h3 class="tea-info-heading"><i class="fas fa-fire-flame-simple"></i> <?= e($tea['tea_s3_title']) ?></h3>
                    <ol class="tea-info-steps">
                        <?php foreach (teaLines($tea['tea_s3_items']) as $step): ?>
                            <li><?= teaLine($step) ?></li>
                        <?php endforeach; ?>
                    </ol>
                </div>

                <div class="tea-info-section">
                    <h3 class="tea-info-heading"><i class="fas fa-box-open"></i> <?= e($tea['tea_s4_title']) ?></h3>
                    <ul class="tea-info-list">
                        <?php foreach (teaLines($tea['tea_s4_items']) as $item): ?>
                            <li><?= teaLine($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>

            <!-- ====== PANEL GỐM SỨ ====== -->
            <section class="tea-info-panel" data-panel="gomsu">
                <div class="tea-info-section">
                    <h3 class="tea-info-heading"><i class="fas fa-mug-saucer"></i> <?= e($tea['gomsu_s1_title']) ?></h3>
                    <ul class="tea-info-list">
                        <?php foreach (teaLines($tea['gomsu_s1_items']) as $item): ?>
                            <li><?= teaLine($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="tea-info-section">
                    <h3 class="tea-info-heading"><i class="fas fa-hand-holding-droplet"></i> <?= e($tea['gomsu_s2_title']) ?></h3>
                    <ol class="tea-info-steps">
                        <?php foreach (teaLines($tea['gomsu_s2_items']) as $step): ?>
                            <li><?= teaLine($step) ?></li>
                        <?php endforeach; ?>
                    </ol>
                </div>

                <div class="tea-info-section">
                    <h3 class="tea-info-heading"><i class="fas fa-soap"></i> <?= e($tea['gomsu_s3_title']) ?></h3>
                    <ul class="tea-info-list">
                        <?php foreach (teaLines($tea['gomsu_s3_items']) as $item): ?>
                            <li><?= teaLine($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>

            <!-- ====== PANEL ẤM TỬ SA ====== -->
            <section class="tea-info-panel" data-panel="amtusa">
                <div class="tea-info-section">
                    <h3 class="tea-info-heading"><i class="fas fa-wine-bottle"></i> <?= e($tea['amtusa_s1_title']) ?></h3>
                    <ul class="tea-info-list">
                        <?php foreach (teaLines($tea['amtusa_s1_items']) as $item): ?>
                            <li><?= teaLine($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="tea-info-section">
                    <h3 class="tea-info-heading"><i class="fas fa-hand-holding-droplet"></i> <?= e($tea['amtusa_s2_title']) ?></h3>
                    <ol class="tea-info-steps">
                        <?php foreach (teaLines($tea['amtusa_s2_items']) as $step): ?>
                            <li><?= teaLine($step) ?></li>
                        <?php endforeach; ?>
                    </ol>
                </div>

                <div class="tea-info-section">
                    <h3 class="tea-info-heading"><i class="fas fa-triangle-exclamation"></i> <?= e($tea['amtusa_s3_title']) ?></h3>
                    <ul class="tea-info-list">
                        <?php foreach (teaLines($tea['amtusa_s3_items']) as $item): ?>
                            <li><?= teaLine($item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>

        </div>
    </div>
</div>

<?php
$extraScript = <<<'HTML'
<script>
    (function () {
        var cats = ['tra', 'gomsu', 'amtusa'];
        var btns = Array.prototype.slice.call(document.querySelectorAll('.tea-nav-btn'));
        var panels = Array.prototype.slice.call(document.querySelectorAll('.tea-info-panel'));

        function activate(cat) {
            btns.forEach(function (b) { b.classList.toggle('active', b.getAttribute('data-cat') === cat); });
            panels.forEach(function (p) { p.classList.toggle('active', p.getAttribute('data-panel') === cat); });
        }

        btns.forEach(function (b) {
            b.addEventListener('click', function () { activate(b.getAttribute('data-cat')); });
        });

        var params = new URLSearchParams(window.location.search);
        var cat = params.get('cat');
        if (cats.indexOf(cat) !== -1) activate(cat);
    })();
</script>
HTML;
require __DIR__ . '/includes/footer.php';
?>
