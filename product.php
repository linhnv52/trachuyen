<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/admin/product/model.php';

$pageTitle = 'Sản phẩm trà - Trà Chuyện';
$active = 'products';
$extraCssLinks = '<link rel="stylesheet" href="css/product.css">';

// ---------- Bộ lọc ----------
$q          = trim($_GET['q'] ?? '');
$category   = trim($_GET['category'] ?? '');
$sort       = $_GET['sort'] ?? 'default';
if (!in_array($sort, ['default','price-asc','price-desc','name','rating'], true)) {
    $sort = 'default';
}
$minPrice   = $_GET['min_price'] ?? '';
$maxPrice   = $_GET['max_price'] ?? '';
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 8;

$categoryId = 0;
$currentCategory = null;
$allCategories = getAllCategories();
// Loại danh mục cha (nhóm trên header) khỏi dải tab
$_parentIds = array_map('intval', array_filter(array_column($allCategories, 'parent_id')));
$displayCategories = array_values(array_filter($allCategories, fn ($c) => !in_array((int)$c['id'], $_parentIds, true)));

if ($category !== '') {
    foreach ($allCategories as $c) {
        if ($c['slug'] === $category) {
            $categoryId = (int)$c['id'];
            $currentCategory = $c;
            break;
        }
    }
}

// Đếm sản phẩm theo từng danh mục
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
    'rating'     => 'rating',
    default      => 'newest',
};

$result = getAllProducts([
    'search'      => $q,
    'category_id' => $categoryId,
    'min_price'   => $minPrice,
    'max_price'   => $maxPrice,
    'sort'        => $dbSort,
    'active_only' => true,
], $page, $perPage);

$products    = $result['items'];
$total       = $result['total'];
$totalPages  = max(1, (int)ceil($total / $perPage));

function buildUrl(array $overrides): string
{
    $q = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === '' || $v === null) {
            unset($q[$k]);
        } else {
            $q[$k] = $v;
        }
    }
    return 'product.php?' . http_build_query($q);
}

function productCard(array $p): string
{
    $badge = '';
    if (!empty($p['badge'])) {
        $cls = match ($p['badge']) { 'hot' => '', 'sale' => 'sale', 'new' => 'new', default => '' };
        $label = match ($p['badge']) { 'hot' => 'Hot', 'sale' => 'Sale', 'new' => 'Mới', default => '' };
        $badge = '<div class="product-badge ' . $cls . '">' . $label . '</div>';
    }
    $img = $p['image_url'] ?: url('/img/placeholder.svg');
    $rating = str_repeat('★', (int)$p['rating_avg']) . str_repeat('☆', 5 - (int)$p['rating_avg']);
    $price = $p['old_price']
        ? '<span class="current-price">' . formatPrice($p['price']) . 'đ</span><span class="old-price">' . formatPrice($p['old_price']) . 'đ</span>'
        : '<span class="current-price">' . formatPrice($p['price']) . 'đ</span>';

    return '<div class="product-item" data-id="' . (int)$p['id'] . '">
        ' . $badge . '
        <a href="productdetal.php?id=' . (int)$p['id'] . '">
            <img src="' . e($img) . '" alt="' . e($p['name']) . '">
            <h3>' . e($p['name']) . '</h3>
        </a>
        <div class="product-rating">' . $rating . ' (' . (int)$p['review_count'] . ')</div>
        <div class="product-price">' . $price . '</div>
        <a class="btn-add-cart" href="productdetal.php?id=' . (int)$p['id'] . '">Xem chi tiết</a>
    </div>';
}

require __DIR__ . '/includes/header.php';
?>

<!-- ========== TRANG SẢN PHẨM ========== -->
<div class="container products-page">

    <div class="breadcrumb">
        <a href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
        <span class="separator"><i class="fas fa-chevron-right"></i></span>
        <span class="current">Sản phẩm trà</span>
    </div>

    <div class="page-header">
        <h1>🍵 Sản phẩm trà</h1>
        <p><?= $currentCategory ? 'Danh mục: ' . e($currentCategory['name']) : 'Khám phá bộ sưu tập trà và trà cụ tuyển chọn từ khắp nơi trên thế giới' ?></p>
    </div>

    <!-- ====== DANH MỤC ====== -->
    <div class="category-tabs" id="categoryTabs">
        <a class="category-tab <?= $category === '' ? 'active' : '' ?>" href="product.php<?= $q ? '?q=' . urlencode($q) : '' ?>">
            Tất cả <span class="count">(<?= $total ?>)</span>
        </a>
        <?php foreach ($displayCategories as $c): ?>
            <a class="category-tab <?= (string)$categoryId === (string)$c['id'] ? 'active' : '' ?>"
               href="<?= e(buildUrl(['category' => $c['slug'], 'page' => '', 'sort' => 'default'])) ?>">
                <?= e($c['name']) ?> <span class="count">(<?= $catCounts[$c['id']] ?? 0 ?>)</span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- ====== FILTER BAR ====== -->
    <form class="filter-bar" method="get" action="product.php">
        <?php if ($category !== ''): ?><input type="hidden" name="category" value="<?= e($category) ?>"><?php endif; ?>
        <div class="result-count">
            Hiển thị <span><?= $total ?></span> sản phẩm
        </div>
        <div class="filter-options">
            <div class="filter-price">
                <input type="number" name="min_price" value="<?= e($minPrice) ?>" placeholder="Giá từ" min="0" style="width:110px; padding:8px 12px; border:1px solid #e0d6cc; border-radius:25px; outline:none;">
                <span style="color:#8d6e63;">-</span>
                <input type="number" name="max_price" value="<?= e($maxPrice) ?>" placeholder="Giá đến" min="0" style="width:110px; padding:8px 12px; border:1px solid #e0d6cc; border-radius:25px; outline:none;">
                <button type="submit" style="padding:8px 18px; border:none; border-radius:25px; background:#5d4037; color:#fff; cursor:pointer; font-size:0.9rem;">Lọc</button>
            </div>
            <div class="view-mode">
                <button type="button" class="active" id="gridView" title="Dạng lưới"><i class="fas fa-th"></i></button>
                <button type="button" id="listView" title="Dạng danh sách"><i class="fas fa-list"></i></button>
            </div>
            <select name="sort" onchange="this.form.submit()">
                <option value="default" <?= $sort === 'default' ? 'selected' : '' ?>>Mặc định</option>
                <option value="price-asc" <?= $sort === 'price-asc' ? 'selected' : '' ?>>Giá tăng dần</option>
                <option value="price-desc" <?= $sort === 'price-desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Tên A-Z</option>
                <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Đánh giá cao nhất</option>
            </select>
        </div>
    </form>

    <!-- ====== GRID SẢN PHẨM ====== -->
    <div class="products-grid" id="productsGrid">
        <?php if (!$products): ?>
            <div class="no-products" style="grid-column: 1/-1;">
                <i class="fas fa-leaf"></i>
                <h3>Không có sản phẩm</h3>
                <p>Không tìm thấy sản phẩm phù hợp với bộ lọc của bạn.</p>
            </div>
        <?php else: foreach ($products as $p): ?>
            <?= productCard($p) ?>
        <?php endforeach; endif; ?>
    </div>

    <!-- ====== PHÂN TRANG ====== -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination" id="pagination">
            <a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="<?= e(buildUrl(['page' => $page - 1])) ?>"><i class="fas fa-chevron-left"></i></a>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i === $page): ?>
                    <a class="active"><?= $i ?></a>
                <?php elseif ($i === 1 || $i === $totalPages || abs($i - $page) <= 2): ?>
                    <a href="<?= e(buildUrl(['page' => $i])) ?>"><?= $i ?></a>
                <?php elseif ($i === $page - 3 || $i === $page + 3): ?>
                    <span>...</span>
                <?php endif; ?>
            <?php endfor; ?>
            <a class="<?= $page >= $totalPages ? 'disabled' : '' ?>" href="<?= e(buildUrl(['page' => $page + 1])) ?>"><i class="fas fa-chevron-right"></i></a>
        </div>
    <?php endif; ?>
</div>

<?php
$extraScript = <<<'HTML'
<script>
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
HTML;
require __DIR__ . '/includes/footer.php';
?>