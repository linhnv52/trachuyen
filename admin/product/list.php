<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/model.php';

$admin = current_admin();
$pageTitle = 'Quản lý sản phẩm';
$pageSubtitle = 'Thêm, sửa, xóa sản phẩm của cửa hàng';
$activeMenu = 'products';

$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    if (isset($_POST['delete_id'])) {
        $product = getProductById((int)$_POST['delete_id'], true);
        if ($product) {
            deleteProduct((int)$product['id']);
            $flash = ['type' => 'success', 'msg' => 'Đã xóa sản phẩm: ' . $product['name']];
        } else {
            $flash = ['type' => 'error', 'msg' => 'Không tìm thấy sản phẩm.'];
        }
    } elseif (isset($_POST['toggle_id'])) {
        $product = getProductById((int)$_POST['toggle_id'], true);
        if ($product) {
            $newActive = $product['is_active'] ? 0 : 1;
            db()->prepare('UPDATE products SET is_active = ? WHERE id = ?')
                ->execute([$newActive, $product['id']]);
            $flash = [
                'type' => 'success',
                'msg'  => $newActive ? 'Đã hiện sản phẩm: ' . $product['name'] : 'Đã tạm ẩn sản phẩm: ' . $product['name'],
            ];
        } else {
            $flash = ['type' => 'error', 'msg' => 'Không tìm thấy sản phẩm.'];
        }
    }
}

$search      = trim($_GET['search'] ?? '');
$categoryId  = $_GET['category_id'] ?? '';
$status      = $_GET['status'] ?? '';
$sort        = $_GET['sort'] ?? 'newest';
$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = 8;

$result  = getAllProducts([
    'search'      => $search,
    'category_id' => $categoryId,
    'status'      => $status,
    'sort'        => $sort,
], $page, $perPage);
$products    = $result['items'];
$total       = $result['total'];
$totalPages  = max(1, (int)ceil($total / $perPage));
$categories  = getAllCategories();

function buildQuery(array $overrides): string
{
    $q = $_GET;
    foreach ($overrides as $k => $v) {
        if ($v === '' || $v === null) {
            unset($q[$k]);
        } else {
            $q[$k] = $v;
        }
    }
    return '?' . http_build_query($q);
}

require __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?>
    <div style="padding:14px 18px; border-radius:10px; margin-bottom:20px; <?= $flash['type'] === 'success' ? 'background:#e8f5e9; color:#2e7d32;' : 'background:#fdecea; color:#c62828;' ?>">
        <?= e($flash['msg']) ?>
    </div>
<?php endif; ?>

<form method="get" class="toolbar">
    <div class="toolbar-left">
        <div class="search-box smart-search">
            <input type="text" name="search" id="adminSearchInput" value="<?= e($search) ?>" placeholder="Tìm theo tên / mã sản phẩm..." autocomplete="off">
            <button type="submit" title="Tìm kiếm"><i class="fas fa-search"></i></button>
            <div class="smart-search-results" id="adminSearchResults"></div>
        </div>
        <select name="category_id" class="filter-select" onchange="this.form.submit()">
            <option value="">Tất cả danh mục</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>" <?= (string)$categoryId === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" class="filter-select" onchange="this.form.submit()">
            <option value="">Tất cả trạng thái</option>
            <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Hoạt động</option>
            <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Tạm dừng</option>
        </select>
        <select name="sort" class="filter-select" onchange="this.form.submit()">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
            <option value="price-asc" <?= $sort === 'price-asc' ? 'selected' : '' ?>>Giá tăng dần</option>
            <option value="price-desc" <?= $sort === 'price-desc' ? 'selected' : '' ?>>Giá giảm dần</option>
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Tên A-Z</option>
            <option value="best" <?= $sort === 'best' ? 'selected' : '' ?>>Bán chạy</option>
        </select>
        <?php if ($search !== '' || $categoryId !== '' || $status !== '' || $sort !== 'newest'): ?>
            <a href="<?= e(url('/admin/product/list.php')) ?>" class="btn btn-outline btn-sm"><i class="fas fa-times"></i> Bỏ lọc</a>
        <?php endif; ?>
    </div>
    <div class="toolbar-right">
        <a href="<?= e(url('/admin/product/add.php')) ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm sản phẩm</a>
    </div>
</form>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>Mã SP</th>
                <th>Danh mục</th>
                <th>Giá bán</th>
                <th>Tồn kho</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$products): ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:50px; color:var(--text-light);">
                        <i class="fas fa-leaf" style="font-size:3rem; color:#d4c5b5; margin-bottom:12px; display:block;"></i>
                        Không tìm thấy sản phẩm phù hợp.
                    </td>
                </tr>
            <?php else: foreach ($products as $p): ?>
                <tr>
                    <td>
                        <div class="product-cell">
                            <img src="<?= e(productImage($p['image_url'])) ?>" alt="">
                            <div class="name"><?= e($p['name']) ?></div>
                        </div>
                    </td>
                    <td><span style="font-weight:600;"><?= e($p['code']) ?></span></td>
                    <td><?= e($p['category_name'] ?: 'Chưa phân loại') ?></td>
                    <td>
                        <strong style="color:var(--gold);"><?= formatPrice($p['price']) ?>đ</strong>
                        <?php if ($p['old_price']): ?>
                            <div style="color:#aaa; font-size:0.75rem; text-decoration:line-through;"><?= formatPrice($p['old_price']) ?>đ</div>
                        <?php endif; ?>
                    </td>
                    <td><?= (int)$p['stock_quantity'] ?></td>
                    <td>
                        <span class="status-badge <?= $p['is_active'] ? 'active' : 'inactive' ?>">
                            <?= $p['is_active'] ? 'Hoạt động' : 'Tạm dừng' ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a class="btn-icon edit" href="<?= e(url('/admin/product/update.php?id=' . $p['id'])) ?>" title="Sửa"><i class="fas fa-edit"></i></a>
                            <a class="btn-icon view" href="<?= e(url('/productdetal.php?id=' . $p['id'])) ?>" target="_blank" title="Xem"><i class="fas fa-eye"></i></a>
                            <form method="post" style="display:inline;" onsubmit="return confirmHideShow(this, <?= $p['is_active'] ? 1 : 0 ?>, '<?= addslashes($p['name']) ?>');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="toggle_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn-icon <?= $p['is_active'] ? 'hide' : 'show' ?>" title="<?= $p['is_active'] ? 'Tạm ẩn' : 'Hiện' ?>">
                                    <i class="fas <?= $p['is_active'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                </button>
                            </form>
                            <form method="post" style="display:inline;" onsubmit="return confirmDelete(this, 'Xóa sản phẩm \'<?= addslashes($p['name']) ?>\'?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="delete_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn-icon delete" title="Xóa"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <div class="table-footer">
        <div class="info">Hiển thị <?= count($products) ?> / <?= $total ?> sản phẩm</div>
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="<?= e(buildQuery(['page' => $page - 1])) ?>"><i class="fas fa-chevron-left"></i></a>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === $page): ?>
                        <a class="active"><?= $i ?></a>
                    <?php elseif ($i === 1 || $i === $totalPages || abs($i - $page) <= 2): ?>
                        <a href="<?= e(buildQuery(['page' => $i])) ?>"><?= $i ?></a>
                    <?php elseif ($i === $page - 3 || $i === $page + 3): ?>
                        <span>...</span>
                    <?php endif; ?>
                <?php endfor; ?>
                <a class="<?= $page >= $totalPages ? 'disabled' : '' ?>" href="<?= e(buildQuery(['page' => $page + 1])) ?>"><i class="fas fa-chevron-right"></i></a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
