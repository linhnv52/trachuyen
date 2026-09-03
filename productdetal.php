<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/admin/product/model.php';

$id = (int)($_GET['id'] ?? 0);
$product = $id ? getProductById($id) : null;

if (!$product) {
    require __DIR__ . '/includes/header.php';
    echo '<div class="container" style="padding:80px 0; text-align:center;">'
        . '<i class="fas fa-leaf" style="font-size:4rem; color:#d4c5b5;"></i>'
        . '<h2 style="color:#3e2723; margin-top:15px;">Không tìm thấy sản phẩm</h2>'
        . '<a href="product.php" style="display:inline-block; margin-top:15px; color:#b8860b;">← Về trang sản phẩm</a>'
        . '</div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Tăng lượt xem
db()->prepare('UPDATE products SET views = views + 1 WHERE id = ?')->execute([$product['id']]);

$pageTitle = $product['name'] . ' - Trà Chuyện';
$active = 'products';
$extraCssLinks = '<link rel="stylesheet" href="css/product-detail.css">';

// Sản phẩm liên quan (cùng danh mục)
$relStmt = db()->prepare('SELECT p.*, c.name AS category_name FROM products p
                          JOIN categories c ON c.id = p.category_id
                          WHERE p.is_active = 1 AND p.category_id = ? AND p.id != ?
                          ORDER BY p.rating_avg DESC LIMIT 4');
$relStmt->execute([$product['category_id'], $product['id']]);
$related = $relStmt->fetchAll();

$badge = '';
$badgeLabel = '';
if (!empty($product['badge'])) {
    $badgeLabel = match ($product['badge']) { 'hot' => 'Hot', 'sale' => 'Sale', 'new' => 'Mới', default => '' };
    $badge = '<div class="product-detail-badge ' . ($product['badge'] === 'sale' ? 'sale' : ($product['badge'] === 'new' ? 'new' : '')) . '">' . $badgeLabel . '</div>';
}

$mainImg = productImage($product['image_url']);
$gallery = productGallery($product['gallery'] ?? null);
$thumbs  = $gallery ? array_merge([$mainImg], $gallery) : [];
$discount = $product['old_price'] && $product['old_price'] > $product['price']
    ? (int)round((1 - $product['price'] / $product['old_price']) * 100)
    : 0;

require __DIR__ . '/includes/header.php';
?>

<!-- ========== CHI TIẾT SẢN PHẨM ========== -->
<div class="container product-detail-page">

    <div class="breadcrumb">
        <a href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
        <span class="separator"><i class="fas fa-chevron-right"></i></span>
        <a href="san-pham-tra.php">Sản phẩm trà</a>
        <?php if ($product['category_slug']): ?>
            <span class="separator"><i class="fas fa-chevron-right"></i></span>
            <a href="<?= e(categoryPageUrl($product['category_slug'])) ?>"><?= e($product['category_name']) ?></a>
        <?php endif; ?>
        <span class="separator"><i class="fas fa-chevron-right"></i></span>
        <span class="current"><?= e($product['name']) ?></span>
    </div>

    <div class="product-detail-grid">
        <!-- Cột trái: Hình ảnh -->
        <div class="product-detail-images">
            <?= $badge ?>
            <div class="main-image">
                <img src="<?= e($mainImg) ?>" alt="<?= e($product['name']) ?>" id="mainImage">
            </div>

            <?php if (count($thumbs) > 1): ?>
            <div class="thumbnail-wrap">
                <button type="button" class="thumb-arrow prev" aria-label="Ảnh trước"><i class="fas fa-chevron-left"></i></button>
                <div class="thumbnail-list" id="thumbList">
                    <?php foreach ($thumbs as $ti => $tImg): ?>
                        <div class="thumbnail-item <?= $ti === 0 ? 'active' : '' ?>" data-src="<?= e($tImg) ?>">
                            <img src="<?= e($tImg) ?>" alt="<?= e($product['name']) ?> <?= $ti + 1 ?>" loading="lazy">
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="thumb-arrow next" aria-label="Ảnh sau"><i class="fas fa-chevron-right"></i></button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Cột phải: Thông tin -->
        <div class="product-detail-info">
            <h1 class="product-detail-name"><?= e($product['name']) ?></h1>

            <div class="product-meta">
                <div class="product-meta-item">
                    <i class="fas fa-tag"></i>
                    <span>Mã sản phẩm: <strong><?= e($product['code']) ?></strong></span>
                </div>
                <div class="product-meta-item">
                    <i class="fas fa-folder"></i>
                    <span>Danh mục: <?= e($product['category_name']) ?></span>
                </div>
                <div class="product-meta-item">
                    <i class="fas fa-box"></i>
                    <span>Tình trạng: <?= $product['stock_quantity'] > 0 ? 'Còn hàng (' . (int)$product['stock_quantity'] . ')' : 'Hết hàng' ?></span>
                </div>
                <div class="product-meta-item">
                    <i class="fas fa-eye"></i>
                    <span>Lượt xem: <?= (int)$product['views'] ?></span>
                </div>
            </div>

            <div class="product-detail-price">
                <span class="current"><?= formatPrice($product['price']) ?>đ</span>
                <?php if ($product['old_price']): ?>
                    <span class="old"><?= formatPrice($product['old_price']) ?>đ</span>
                <?php endif; ?>
                <?php if ($discount > 0): ?>
                    <span class="discount">-<?= $discount ?>%</span>
                <?php endif; ?>
            </div>

            <div class="product-detail-description">
                <h4>Mô tả sản phẩm</h4>
                <p><?= nl2br(e($product['description'] ?: 'Chưa có mô tả cho sản phẩm này.')) ?></p>
            </div>

            <a class="btn-consult" href="tel:0877013030"><i class="fas fa-phone-alt"></i>Tư vấn mua hàng</a>
        </div>
    </div>

    <!-- ====== SẢN PHẨM LIÊN QUAN ====== -->
    <?php if ($related): ?>
    <section class="related-products">
        <h2 class="section-title">Sản phẩm liên quan</h2>
        <div class="grid-6col-2row">
            <?php foreach ($related as $rp):
                $rImg = productImage($rp['image_url']);
                ?>
                <div class="product-item" data-id="<?= (int)$rp['id'] ?>">
                    <a href="productdetal.php?id=<?= $rp['id'] ?>">
                        <img src="<?= e($rImg) ?>" alt="<?= e($rp['name']) ?>">
                        <h3><?= e($rp['name']) ?></h3>
                    </a>
                    <div class="product-price">
                        <span class="current-price"><?= formatPrice($rp['price']) ?>đ</span>
                        <?php if ($rp['old_price']): ?><span class="old-price"><?= formatPrice($rp['old_price']) ?>đ</span><?php endif; ?>
                    </div>
                    <a class="btn-add-cart" href="productdetal.php?id=<?= $rp['id'] ?>" title="Xem chi tiết"><i class="fas fa-arrow-right"></i></a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</div>

<?php
$extraScript = count($thumbs) > 1 ? <<<'HTML'
<script>
(function () {
    var list = document.getElementById('thumbList');
    if (!list) return;
    var main = document.getElementById('mainImage');
    var wrap = list.parentElement;

    // Bấm thumbnail -> đổi ảnh chính + highlight
    list.addEventListener('click', function (e) {
        var item = e.target.closest('.thumbnail-item');
        if (!item || !main) return;
        var src = item.getAttribute('data-src');
        if (!src || main.src.indexOf(src) !== -1) return;
        main.src = src;
        list.querySelectorAll('.thumbnail-item.active').forEach(function (el) {
            el.classList.remove('active');
        });
        item.classList.add('active');
    });

    // Mũi tên cuộn slider đúng 1 ô
    document.querySelectorAll('.thumb-arrow').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var first = list.firstElementChild;
            var step = first ? first.getBoundingClientRect().width + 12 : 92;
            list.scrollBy({ left: btn.classList.contains('next') ? step : -step, behavior: 'smooth' });
        });
    });

    // Chỉ hiện mũi tên khi danh sách tràn ngang
    function toggleArrows() {
        wrap.classList.toggle('has-arrows', list.scrollWidth > list.clientWidth + 4);
    }
    window.addEventListener('resize', toggleArrows);
    window.addEventListener('load', toggleArrows);
    toggleArrows();
})();
</script>
HTML : '';
require __DIR__ . '/includes/footer.php';
?>
