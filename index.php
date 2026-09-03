<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/admin/product/model.php';

$pageTitle = 'Trà Chuyện - Trang chủ';
$active = 'home';

$allCategories = getAllCategories();
// Loại danh mục cha (nhóm trên header) khỏi khối danh mục
$_parentIds = array_map('intval', array_filter(array_column($allCategories, 'parent_id')));
$displayCategories = array_values(array_filter($allCategories, fn ($c) => !in_array((int)$c['id'], $_parentIds, true)));
$countStmt = db()->query('SELECT category_id, COUNT(*) AS c FROM products WHERE is_active = 1 GROUP BY category_id');
$catCounts = [];
foreach ($countStmt->fetchAll() as $row) {
    $catCounts[(int)$row['category_id']] = (int)$row['c'];
}
$bestSellers = db()->query('SELECT p.*, c.name AS category_name, c.slug AS category_slug
                            FROM products p
                            JOIN categories c ON c.id = p.category_id
                            WHERE p.is_active = 1 AND p.badge = "hot"
                            ORDER BY p.rating_avg DESC, p.created_at DESC
                            LIMIT 12')->fetchAll();

// Banner slider (quản lý từ admin); fallback ảnh mặc định nếu chưa có dữ liệu
$banners = db()->query('SELECT image_url FROM banners WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll(PDO::FETCH_COLUMN);
if (!$banners) {
    $banners = ['img/1.png', 'img/2.png', 'img/3.png', 'img/4.png', 'img/5.png'];
}

require __DIR__ . '/includes/header.php';
?>

<!-- ========== SLIDER BANNER ========== -->
<div class="container hero-slider-shell">
    <div class="slider-container" id="slider">
        <div class="slider-wrapper" id="sliderWrapper">
            <?php foreach ($banners as $i => $bannerUrl): ?>
                <div class="slide slide-<?= ($i % 5) + 1 ?>"><img src="<?= e($bannerUrl) ?>" alt=""></div>
            <?php endforeach; ?>
        </div>

        <button class="slider-btn prev" id="prevBtn"><i class="fas fa-chevron-left"></i></button>
        <button class="slider-btn next" id="nextBtn"><i class="fas fa-chevron-right"></i></button>

        <div class="dots-container" id="dotsContainer"></div>
    </div>
</div>

<div class="container body-container">

    <!-- ====== 1. DANH MỤC SẢN PHẨM ====== -->
    <section class="section-category">
        <h2 class="section-title">Danh mục sản phẩm</h2>
        <div class="grid-6col-2row">
            <?php foreach ($displayCategories as $c): ?>
                <a class="category-item" href="<?= e(categoryPageUrl($c['slug'])) ?>">
                    <img src="<?= e(categoryImage($c['image_url'])) ?>" alt="<?= e($c['name']) ?>">
                    <h3><?= e($c['name']) ?></h3>
                    <p><?= $catCounts[$c['id']] ?? 0 ?> sản phẩm</p>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="view-all">
            <a href="product.php" class="btn-view-all">Xem tất cả danh mục →</a>
        </div>
    </section>

    <!-- ====== 2. TRƯNG BÀY ẢNH & VIDEO ====== -->
    <section class="section-gallery">
        <div class="gallery-container">
            <div class="gallery-images">
                <div class="gallery-img-item"><img src="img/placeholder.svg" alt="Ấm tử sa 1"></div>
                <div class="gallery-img-item"><img src="img/placeholder.svg" alt="Ấm tử sa 2"></div>
                <div class="gallery-img-item"><img src="img/placeholder.svg" alt="Ấm tử sa 3"></div>
                <div class="gallery-img-item"><img src="img/placeholder.svg" alt="Ấm tử sa 4"></div>
            </div>

            <div class="gallery-video">
                <div class="video-wrapper">
                    <iframe
                        src="https://drive.google.com/file/d//preview"
                        title="Video giới thiệu ấm tử sa"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== 3. SẢN PHẨM BÁN CHẠY ====== -->
    <section class="section-best-seller">
        <h2 class="section-title">Sản phẩm bán chạy</h2>
        <div class="grid-6col-2row">
            <?php if (!$bestSellers): ?>
                <p style="grid-column:1/-1; text-align:center; color:#8d6e63; padding:30px;">Chưa có sản phẩm bán chạy.</p>
            <?php else: foreach ($bestSellers as $p):
                $badge = '';
                if (!empty($p['badge'])) {
                    $cls = match ($p['badge']) { 'hot' => '', 'sale' => 'sale', 'new' => 'new', default => '' };
                    $label = match ($p['badge']) { 'hot' => 'Hot', 'sale' => 'Sale', 'new' => 'Mới', default => '' };
                    $badge = '<div class="product-badge ' . $cls . '">' . $label . '</div>';
                }
                $img = productImage($p['image_url']);
                ?>
                <div class="product-item" data-id="<?= (int)$p['id'] ?>">
                    <?= $badge ?>
                    <a href="productdetal.php?id=<?= $p['id'] ?>">
                        <img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>">
                        <h3><?= e($p['name']) ?></h3>
                    </a>
                    <div class="product-price">
                        <span class="current-price"><?= formatPrice($p['price']) ?>đ</span>
                        <?php if ($p['old_price']): ?><span class="old-price"><?= formatPrice($p['old_price']) ?>đ</span><?php endif; ?>
                    </div>
                    <a class="btn-add-cart" href="productdetal.php?id=<?= $p['id'] ?>" title="Xem chi tiết"><i class="fas fa-arrow-right"></i></a>
                </div>
            <?php endforeach; endif; ?>
        </div>
        <div class="view-all">
            <a href="product.php" class="btn-view-all">Xem tất cả sản phẩm →</a>
        </div>
    </section>

    <!-- ====== 4. HÀNG DỊCH VỤ ====== -->
    <section class="services-row">
        <div class="service-item">
            <i class="fas fa-headset"></i>
            <h3>Tư vấn sản phẩm</h3>
        </div>
        <div class="service-item">
            <i class="fas fa-shield-alt"></i>
            <h3>Bảo hiểm rơi vỡ</h3>
        </div>
        <div class="service-item">
            <i class="fas fa-box-open"></i>
            <h3>Được kiểm hàng</h3>
        </div>
        <div class="service-item">
            <i class="fas fa-truck-fast"></i>
            <h3>Giao hàng nhanh chóng</h3>
        </div>
    </section>

</div>

<?php
$extraScript = <<<'HTML'
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sliderWrapper = document.getElementById('sliderWrapper');
        const slides = sliderWrapper.querySelectorAll('.slide');
        const totalSlides = slides.length;
        let currentIndex = 0;
        let autoPlayInterval;

        const dotsContainer = document.getElementById('dotsContainer');
        for (let i = 0; i < totalSlides; i++) {
            const dot = document.createElement('span');
            dot.className = 'dot' + (i === 0 ? ' active' : '');
            dot.addEventListener('click', function() { goToSlide(i); resetAutoPlay(); });
            dotsContainer.appendChild(dot);
        }
        const dots = dotsContainer.querySelectorAll('.dot');

        function goToSlide(index) {
            currentIndex = index;
            sliderWrapper.style.transform = 'translateX(-' + (currentIndex * 100) + '%)';
            dots.forEach(function(d, i) { d.classList.toggle('active', i === currentIndex); });
        }

        document.getElementById('prevBtn').addEventListener('click', function() {
            goToSlide((currentIndex - 1 + totalSlides) % totalSlides);
            resetAutoPlay();
        });
        document.getElementById('nextBtn').addEventListener('click', function() {
            goToSlide((currentIndex + 1) % totalSlides);
            resetAutoPlay();
        });

        function startAutoPlay() { autoPlayInterval = setInterval(function() { goToSlide((currentIndex + 1) % totalSlides); }, 4000); }
        function resetAutoPlay() { clearInterval(autoPlayInterval); startAutoPlay(); }

        const sliderContainer = document.getElementById('slider');
        sliderContainer.addEventListener('mouseenter', function() { clearInterval(autoPlayInterval); });
        sliderContainer.addEventListener('mouseleave', function() { startAutoPlay(); });

        startAutoPlay();
    });
</script>
HTML;
require __DIR__ . '/includes/footer.php';
?>
