<?php
/**
 * Template dùng chung cho các trang liệt kê sản phẩm phía public.
 *
 * Trang gọi phải require config/db.php + admin/product/model.php và đặt các biến sau TRƯỚC khi include:
 *
 * @var string     $PL_baseFile       Tên file trang (vd 'product.php') dùng build URL lọc/phân trang
 * @var string     $PL_pageTitle      Tiêu đề tab trình duyệt
 * @var string     $PL_title          Tiêu đề lớn của trang
 * @var string     $PL_subtitle       Mô tả mặc định khi chưa chọn danh mục
 * @var string     $PL_breadcrumbLabel Nhãn cuối của breadcrumb
 * @var string     $PL_active         Key highlight menu header ('products'|'gift'|'teaset'|'teapot'...)
 * @var string     $PL_fixedSlug      Slug danh mục khoá cứng ('' = không khoá)
 * @var array|null $PL_groupIds       Mảng id danh mục được phép (giới hạn cả sản phẩm lẫn tab); null = tất cả
 * @var bool       $PL_showTabs       Có hiển thị dải tab danh mục hay không
 */

$PL_baseFile  = $PL_baseFile ?? 'product.php';
$PL_active    = $PL_active ?? 'products';
$PL_showTabs  = $PL_showTabs ?? true;
$PL_fixedSlug = $PL_fixedSlug ?? '';
$PL_groupIds  = $PL_groupIds ?? null;
$PL_catGrid   = $PL_catGrid ?? 'grid-6col-2row';

// ---------- Bộ lọc ----------
$q          = trim($_GET['q'] ?? '');
$category   = trim($_GET['category'] ?? '');
$sort       = $_GET['sort'] ?? 'default';
if (!in_array($sort, ['default','price-asc','price-desc','name','rating'], true)) {
    $sort = 'default';
}
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 8;

// Lọc giá theo mốc (radio) — value => [min, max] (null = không chặn)
$PRICE_BRACKETS = [
    'duoi-500'  => [null, 500000],
    '500-1m'    => [500000, 1000000],
    '1m-2m'     => [1000000, 2000000],
    'tren-2m'   => [2000000, null],
];
$priceRange = trim((string)($_GET['price_range'] ?? ''));
if (!isset($PRICE_BRACKETS[$priceRange])) {
    $priceRange = '';
}
$bracket  = $priceRange !== '' ? $PRICE_BRACKETS[$priceRange] : null;
$minPrice = $bracket ? ($bracket[0] !== null ? (string)$bracket[0] : '') : '';
$maxPrice = $bracket ? ($bracket[1] !== null ? (string)$bracket[1] : '') : '';

// Lọc dung tích theo mốc khoảng (chỉ trang Ấm Tử Sa) — key => [min, max] (null = không chặn)
$CAPACITY_BRACKETS = [
    'lt-150'   => [null, 150],
    '150-250'  => [150, 250],
    '250-350'  => [250, 350], // [min, max]; SQL dùng min lệch (>) để loại giá trị ở biên trên mốc trước
    'gt-350'   => [350, null],
];
$CAPACITY_LABELS = [
    'lt-150'   => 'Dưới 150ml',
    '150-250'  => '150 - 250ml',
    '250-350'  => '250 - 350ml',
    'gt-350'   => 'Trên 350ml',
];
$capacity = trim((string)($_GET['capacity'] ?? ''));
if (!isset($CAPACITY_BRACKETS[$capacity])) {
    $capacity = '';
}
$showCapacityFilter = ($PL_fixedSlug === 'am-tu-sa');

$allCategories = getAllCategories();
// Loại danh mục cha khỏi mọi dải hiển thị
$_parentIds = array_map('intval', array_filter(array_column($allCategories, 'parent_id')));

// Danh mục trên dải tab: chỉ nhóm được phép nếu có đặt, ngược lại là tất cả
$tabCategories = $allCategories;
if (is_array($PL_groupIds)) {
    $tabCategories = array_values(array_filter($allCategories, fn ($c) => in_array((int)$c['id'], $PL_groupIds, true)));
}
$tabCategories = array_values(array_filter($tabCategories, fn ($c) => !in_array((int)$c['id'], $_parentIds, true)));

// Khoá cứng danh mục theo slug (trang chuyên mục)
$fixedCategoryId = 0;
if ($PL_fixedSlug !== '') {
    foreach ($allCategories as $_c) {
        if ($_c['slug'] === $PL_fixedSlug) {
            $fixedCategoryId = (int)$_c['id'];
            break;
        }
    }
}

// Danh mục đang chọn qua ?category= (chỉ nhận danh mục thuộc dải tab)
$categoryId      = $fixedCategoryId;
$currentCategory = null;
if ($categoryId === 0 && $category !== '') {
    foreach ($tabCategories as $c) {
        if ($c['slug'] === $category) {
            $categoryId      = (int)$c['id'];
            $currentCategory = $c;
            break;
        }
    }
} elseif ($categoryId !== 0) {
    foreach ($allCategories as $c) {
        if ((int)$c['id'] === $categoryId) {
            $currentCategory = $c;
            break;
        }
    }
}

// Đếm sản phẩm theo từng danh mục (số trên tab)
$countStmt = db()->query('SELECT category_id, COUNT(*) AS c FROM products WHERE is_active = 1 GROUP BY category_id');
$catCounts = [];
foreach ($countStmt->fetchAll() as $row) {
    $catCounts[(int)$row['category_id']] = (int)$row['c'];
}

// Sắp xếp DB
$dbSort = match ($sort) {
    'price-asc'  => 'price-asc',
    'price-desc' => 'price-desc',
    'name'       => 'name',
    default      => 'newest',
};

$listFilters = [
    'search'      => $q,
    'min_price'   => $minPrice,
    'max_price'   => $maxPrice,
    'sort'        => $dbSort,
    'active_only' => true,
];
if ($showCapacityFilter && $capacity) {
    // Dung tích là số nguyên ml. Mapping rõ ràng theo từng mốc:
    //  lt-150  : < 150              => capacity <= 149
    //  150-250 : 150..250           => 150 <= capacity <= 250
    //  250-350 : >250 và <=350      => 251 <= capacity <= 350
    //  gt-350  : > 350              => capacity >= 351
    $capRange = match ($capacity) {
        'lt-150'   => [null, 149],
        '150-250'  => [150, 250],
        '250-350'  => [251, 350],
        'gt-350'   => [351, null],
    };
    $listFilters['capacity_min'] = $capRange[0];
    $listFilters['capacity_max'] = $capRange[1];
}
if ($categoryId !== 0) {
    $listFilters['category_id'] = $categoryId;
} elseif (is_array($PL_groupIds)) {
    // Chưa chọn tab cụ thể -> lấy toàn bộ nhóm
    $listFilters['category_ids'] = $PL_groupIds;
}

$result      = getAllProducts($listFilters, $page, $perPage);
$products    = $result['items'];
$total       = $result['total'];
$totalPages  = max(1, (int)ceil($total / $perPage));

// Các mốc dung tích (chỉ trang Ấm Tử Sa) — 4 khoảng cố định
$capacityOptions = $showCapacityFilter ? array_keys($CAPACITY_BRACKETS) : [];

function plBuildUrl(string $baseFile, array $overrides): string
{
    $qs = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === '' || $v === null) {
            unset($qs[$k]);
        } else {
            $qs[$k] = $v;
        }
    }
    return $baseFile . '?' . http_build_query($qs);
}

function plProductCard(array $p): string
{
    $badge = '';
    if (!empty($p['badge'])) {
        $cls = match ($p['badge']) { 'hot' => '', 'sale' => 'sale', 'new' => 'new', default => '' };
        $label = match ($p['badge']) { 'hot' => 'Hot', 'sale' => 'Sale', 'new' => 'Mới', default => '' };
        $badge = '<div class="product-badge ' . $cls . '">' . $label . '</div>';
    }
    $img = productImage($p['image_url']);
    $price = $p['old_price']
        ? '<span class="current-price">' . formatPrice($p['price']) . 'đ</span><span class="old-price">' . formatPrice($p['old_price']) . 'đ</span>'
        : '<span class="current-price">' . formatPrice($p['price']) . 'đ</span>';

    return '<div class="product-item" data-id="' . (int)$p['id'] . '">
        ' . $badge . '
        <a href="productdetal.php?id=' . (int)$p['id'] . '">
            <img src="' . e($img) . '" alt="' . e($p['name']) . '">
            <h3>' . e($p['name']) . '</h3>
        </a>
        ' . (!empty($p['capacity']) ? '<div class="product-capacity">Dung tích: ' . (int)$p['capacity'] . 'ml</div>' : '') . '
        <div class="product-price">' . $price . '</div>
        <a class="btn-add-cart" href="productdetal.php?id=' . (int)$p['id'] . '" title="Xem chi tiết"><i class="fas fa-arrow-right"></i></a>
    </div>';
}

$pageTitle      = $PL_pageTitle;
$active         = $PL_active;
$extraCssLinks  = '<link rel="stylesheet" href="css/product.css">';
require __DIR__ . '/header.php';
?>

<!-- ========== TRANG DANH SÁCH SẢN PHẨM ========== -->
<div class="container products-page">

    <div class="breadcrumb">
        <a href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
        <span class="separator"><i class="fas fa-chevron-right"></i></span>
        <span class="current"><?= e($PL_breadcrumbLabel) ?></span>
    </div>

    <?php if ($PL_showTabs): ?>
    <!-- ====== DANH MỤC (carousel 6 cột) ====== -->
    <div class="cat-carousel" id="catCarousel">
        <button type="button" class="cat-arrow cat-arrow-prev" aria-label="Danh mục trước"><i class="fas fa-chevron-left"></i></button>
        <div class="cat-viewport">
            <div class="<?= e($PL_catGrid) ?> cat-track" id="categoryCards">
                <?php foreach ($tabCategories as $c): ?>
                    <a class="category-item <?= (string)$categoryId === (string)$c['id'] ? 'active' : '' ?>"
                       data-cat-id="<?= (int)$c['id'] ?>"
                       data-cat-slug="<?= e($c['slug']) ?>"
                       href="<?= e(plBuildUrl($PL_baseFile, ['category' => $c['slug'], 'page' => '', 'sort' => 'default'])) ?>">
                        <img src="<?= e(categoryImage($c['image_url'])) ?>" alt="<?= e($c['name']) ?>" loading="lazy">
                        <h3><?= e($c['name']) ?></h3>
                        <p><?= $catCounts[$c['id']] ?? 0 ?> sản phẩm</p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <button type="button" class="cat-arrow cat-arrow-next" aria-label="Danh mục tiếp theo"><i class="fas fa-chevron-right"></i></button>
    </div>
    <?php endif; ?>

    <div class="products-layout">

        <!-- ====== SIDEBAR BỘ LỌC (trái) ====== -->
        <aside class="products-sidebar">
            <form class="filter-sidebar" method="get" action="<?= e($PL_baseFile) ?>">
                <?php if (!$fixedCategoryId && $category !== ''): ?><input type="hidden" name="category" value="<?= e($category) ?>"><?php endif; ?>

                <div class="filter-group">
                    <h4 class="filter-title">Giá</h4>
                    <?php foreach ($PRICE_BRACKETS as $_prKey => $_prRange): ?>
                        <label class="filter-option">
                            <input type="radio" name="price_range" value="<?= e($_prKey) ?>"
                                   <?= $priceRange === $_prKey ? 'checked' : '' ?>>
                            <?php if ($_prRange[0] === null): ?>
                                Dưới <?= formatPrice($_prRange[1]) ?>
                            <?php elseif ($_prRange[1] === null): ?>
                                Trên <?= formatPrice($_prRange[0]) ?>
                            <?php else: ?>
                                <?= formatPrice($_prRange[0]) ?> → <?= formatPrice($_prRange[1]) ?>
                            <?php endif; ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <?php if ($showCapacityFilter): ?>
                <div class="filter-group">
                    <h4 class="filter-title">Dung tích</h4>
                    <?php if ($capacityOptions): ?>
                        <?php foreach ($capacityOptions as $capKey): ?>
                            <label class="filter-option">
                                <input type="radio" name="capacity" value="<?= e($capKey) ?>"
                                       <?= $capacity === $capKey ? 'checked' : '' ?>>
                                <?= e($CAPACITY_LABELS[$capKey]) ?>
                            </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="filter-empty">Không có lựa chọn</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="filter-group">
                    <h4 class="filter-title">Sắp xếp</h4>
                    <select name="sort">
                        <option value="default" <?= $sort === 'default' ? 'selected' : '' ?>>Mặc định</option>
                        <option value="price-asc" <?= $sort === 'price-asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                        <option value="price-desc" <?= $sort === 'price-desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Tên A-Z</option>
                    </select>
                </div>
            </form>
        </aside>

        <!-- ====== NỘI DUNG (phải) ====== -->
        <section class="products-content">
            <div class="products-toolbar">
                <div class="result-count">
                    Hiển thị <span><?= $total ?></span> sản phẩm
                </div>
                <div class="view-mode">
                    <button type="button" class="active" id="gridView" title="Dạng lưới"><i class="fas fa-th"></i></button>
                    <button type="button" id="listView" title="Dạng danh sách"><i class="fas fa-list"></i></button>
                </div>
            </div>

            <!-- ====== GRID SẢN PHẨM ====== -->
            <div class="products-grid" id="productsGrid">
                <?php if (!$products): ?>
                    <div class="no-products" style="grid-column: 1/-1;">
                        <i class="fas fa-leaf"></i>
                        <h3>Không có sản phẩm</h3>
                        <p>Không tìm thấy sản phẩm phù hợp với bộ lọc của bạn.</p>
                    </div>
                <?php else: foreach ($products as $p): ?>
                    <?= plProductCard($p) ?>
                <?php endforeach; endif; ?>
            </div>

            <!-- ====== PHÂN TRANG ====== -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination" id="pagination">
                    <a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="<?= e(plBuildUrl($PL_baseFile, ['page' => $page - 1])) ?>"><i class="fas fa-chevron-left"></i></a>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i === $page): ?>
                            <a class="active"><?= $i ?></a>
                        <?php elseif ($i === 1 || $i === $totalPages || abs($i - $page) <= 2): ?>
                            <a href="<?= e(plBuildUrl($PL_baseFile, ['page' => $i])) ?>"><?= $i ?></a>
                        <?php elseif ($i === $page - 3 || $i === $page + 3): ?>
                            <span>...</span>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <a class="<?= $page >= $totalPages ? 'disabled' : '' ?>" href="<?= e(plBuildUrl($PL_baseFile, ['page' => $page + 1])) ?>"><i class="fas fa-chevron-right"></i></a>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php
$extraScript = <<<'HTML'
<script>
    // Carousel danh mục (6 cột + mũi tên trái/phải)
    document.querySelectorAll('.cat-carousel').forEach(function(carousel) {
        var viewport = carousel.querySelector('.cat-viewport');
        var track   = carousel.querySelector('.cat-track');
        var prevBtn = carousel.querySelector('.cat-arrow-prev');
        var nextBtn = carousel.querySelector('.cat-arrow-next');

        function refresh() {
            var canScroll = track.scrollWidth > viewport.clientWidth + 1;
            carousel.classList.toggle('has-overflow', canScroll);
            prevBtn.classList.toggle('enabled', canScroll && viewport.scrollLeft > 0);
            nextBtn.classList.toggle('enabled', canScroll && viewport.scrollLeft + viewport.clientWidth < track.scrollWidth - 1);
        }

        function scrollBy(amount) {
            viewport.scrollBy({ left: amount, behavior: 'smooth' });
        }

        prevBtn.addEventListener('click', function() { scrollBy(-viewport.clientWidth); });
        nextBtn.addEventListener('click', function() { scrollBy(viewport.clientWidth); });
        viewport.addEventListener('scroll', refresh);
        window.addEventListener('resize', refresh);
        // refresh sau khi layout ổn định
        window.addEventListener('load', refresh);
        refresh();
    });

    // View toggle (grid / list) - chỉ thay đổi CSS, không cần tải lại
    const gridViewBtn = document.getElementById('gridView');
    const listViewBtn = document.getElementById('listView');
    const productsGrid = document.getElementById('productsGrid');

    function applyView(view) {
        if (view === 'list') {
            productsGrid.classList.add('list-view');
            gridViewBtn.classList.remove('active');
            listViewBtn.classList.add('active');
            document.cookie = 'trachuyen_view=list; path=/';
        } else {
            productsGrid.classList.remove('list-view');
            listViewBtn.classList.remove('active');
            gridViewBtn.classList.add('active');
            document.cookie = 'trachuyen_view=grid; path=/';
        }
    }

    if (document.cookie.indexOf('trachuyen_view=list') !== -1) applyView('list');

    gridViewBtn.addEventListener('click', function() { applyView('grid'); });
    listViewBtn.addEventListener('click', function() { applyView('list'); });
</script>
<script>
// Cho phép bỏ chọn radio đã chọn (giá / dung tích) + tự áp dụng khi đổi filter — chỉ chạy trên PHP live (bản tĩnh do static-listing.js lo)
if (!document.documentElement.hasAttribute('data-static')) {
    (function () {
        var form = document.querySelector('.filter-sidebar');
        if (!form) return;
        var wasChecked = false;
        form.addEventListener('mousedown', function (ev) {
            var t = ev.target;
            if (t.matches('input[type="radio"][name="price_range"], input[type="radio"][name="capacity"]')) wasChecked = t.checked;
        }, true);
        form.addEventListener('click', function (ev) {
            var t = ev.target;
            if (!t.matches('input[type="radio"][name="price_range"], input[type="radio"][name="capacity"]')) return;
            if (wasChecked) { t.checked = false; wasChecked = false; form.submit(); return; }
        });
        // Auto-apply khi chạm radio / đổi sort (PHP live)
        form.addEventListener('change', function (ev) {
            if (ev.target.matches('select[name="sort"]') || ev.target.matches('input[name="price_range"]') || ev.target.matches('input[name="capacity"]')) {
                form.submit();
            }
        });
    })();
}
</script>
HTML;
require __DIR__ . '/footer.php';
