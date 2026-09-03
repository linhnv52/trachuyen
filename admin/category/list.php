<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/model.php';

$admin = current_admin();
$pageTitle = 'Quản lý danh mục';
$pageSubtitle = 'Thêm, sửa, xóa danh mục hiển thị trên website';
$activeMenu = 'categories';

$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $category = getCategoryById((int)$_POST['delete_id']);
        if ($category) {
            try {
                deleteCategory((int)$category['id']);
                $flash = ['type' => 'success', 'msg' => 'Đã xóa danh mục: ' . $category['name']];
            } catch (RuntimeException $ex) {
                $flash = ['type' => 'error', 'msg' => $ex->getMessage()];
            }
        } else {
            $flash = ['type' => 'error', 'msg' => 'Không tìm thấy danh mục.'];
        }
    } elseif (isset($_POST['toggle_id'])) {
        $category = getCategoryById((int)$_POST['toggle_id']);
        if ($category) {
            $newActive = $category['is_active'] ? 0 : 1;
            db()->prepare('UPDATE categories SET is_active = ? WHERE id = ?')
                ->execute([$newActive, $category['id']]);
            $flash = [
                'type' => 'success',
                'msg'  => $newActive ? 'Đã hiện danh mục: ' . $category['name'] : 'Đã tạm ẩn danh mục: ' . $category['name'],
            ];
        } else {
            $flash = ['type' => 'error', 'msg' => 'Không tìm thấy danh mục.'];
        }
    }
}

$search    = trim($_GET['search'] ?? '');
$status    = $_GET['status'] ?? '';
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 8;

$where  = [];
$params = [];
if ($search !== '') {
    $where[] = '(name LIKE ? OR slug LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}
if ($status !== '') {
    $where[] = 'is_active = ?';
    $params[] = (int)$status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = db()->prepare("SELECT COUNT(*) FROM categories $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$offset = ($page - 1) * $perPage;
$stmt = db()->prepare("SELECT * FROM categories $whereSql ORDER BY sort_order, id LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$categories = $stmt->fetchAll();

$counts = categoryProductCounts();
$parentNames = [];
foreach (db()->query('SELECT id, name FROM categories')->fetchAll() as $row) {
    $parentNames[(int)$row['id']] = $row['name'];
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
        <div class="search-box">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Tìm theo tên / slug danh mục...">
            <button type="submit" title="Tìm kiếm"><i class="fas fa-search"></i></button>
        </div>
        <select name="status" class="filter-select" onchange="this.form.submit()">
            <option value="">Tất cả trạng thái</option>
            <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Hoạt động</option>
            <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Tạm dừng</option>
        </select>
        <?php if ($search !== '' || $status !== ''): ?>
            <a href="<?= e(url('/admin/category/list.php')) ?>" class="btn btn-outline btn-sm"><i class="fas fa-times"></i> Bỏ lọc</a>
        <?php endif; ?>
    </div>
    <div class="toolbar-right">
        <a href="<?= e(url('/admin/category/layout.php')) ?>" class="btn btn-outline"><i class="fas fa-layer-group"></i> Bố cục trang chủ</a>
        <a href="<?= e(url('/admin/category/add.php')) ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm danh mục</a>
    </div>
</form>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Danh mục</th>
                <th>Slug</th>
                <th>Danh mục cha</th>
                <th>Số SP</th>
                <th>Thứ tự</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$categories): ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:50px; color:var(--text-light);">
                        <i class="fas fa-tags" style="font-size:3rem; color:#d4c5b5; margin-bottom:12px; display:block;"></i>
                        Không tìm thấy danh mục phù hợp.
                    </td>
                </tr>
            <?php else: foreach ($categories as $c): ?>
                <tr>
                    <td>
                        <div class="product-cell">
                            <img src="<?= e(categoryImage($c['image_url'])) ?>" alt="">
                            <div class="name"><?= e($c['name']) ?></div>
                        </div>
                    </td>
                    <td><span style="color:#9b8a7a;">/<?= e($c['slug']) ?></span></td>
                    <td><?= $c['parent_id'] ? e($parentNames[(int)$c['parent_id']] ?? '') : '<span style="color:#aaa;">—</span>' ?></td>
                    <td><span class="status-badge" style="background:#f5f0eb; color:#5d4037;"><?= $counts[(int)$c['id']] ?? 0 ?></span></td>
                    <td><?= (int)$c['sort_order'] ?></td>
                    <td>
                        <span class="status-badge <?= $c['is_active'] ? 'active' : 'inactive' ?>">
                            <?= $c['is_active'] ? 'Hoạt động' : 'Tạm dừng' ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a class="btn-icon edit" href="<?= e(url('/admin/category/update.php?id=' . $c['id'])) ?>" title="Sửa"><i class="fas fa-edit"></i></a>
                            <a class="btn-icon view" href="<?= e(url('/product.php?category=' . $c['slug'])) ?>" target="_blank" title="Xem trên web"><i class="fas fa-eye"></i></a>
                            <form method="post" style="display:inline;" onsubmit="return confirmHideShow(this, <?= $c['is_active'] ? 1 : 0 ?>, '<?= addslashes($c['name']) ?>');">
                                <input type="hidden" name="toggle_id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn-icon <?= $c['is_active'] ? 'hide' : 'show' ?>" title="<?= $c['is_active'] ? 'Tạm ẩn' : 'Hiện' ?>">
                                    <i class="fas <?= $c['is_active'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                </button>
                            </form>
                            <form method="post" style="display:inline;" onsubmit="return confirmDelete(this, 'Xóa danh mục \'<?= addslashes($c['name']) ?>\'?');">
                                <input type="hidden" name="delete_id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn-icon delete" title="Xóa"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <div class="table-footer">
        <div class="info">Hiển thị <?= count($categories) ?> / <?= $total ?> danh mục</div>
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php
                $qs = http_build_query(array_filter([
                    'search' => $search,
                    'status' => $status,
                ], fn($v) => $v !== ''));
                $base = '/admin/category/list.php' . ($qs ? '?' . $qs . '&' : '?');
                ?>
                <a class="<?= $page <= 1 ? 'disabled' : '' ?>" href="<?= e($base . 'page=' . ($page - 1)) ?>"><i class="fas fa-chevron-left"></i></a>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === $page): ?>
                        <a class="active"><?= $i ?></a>
                    <?php elseif ($i === 1 || $i === $totalPages || abs($i - $page) <= 2): ?>
                        <a href="<?= e($base . 'page=' . $i) ?>"><?= $i ?></a>
                    <?php elseif ($i === $page - 3 || $i === $page + 3): ?>
                        <span>...</span>
                    <?php endif; ?>
                <?php endfor; ?>
                <a class="<?= $page >= $totalPages ? 'disabled' : '' ?>" href="<?= e($base . 'page=' . ($page + 1)) ?>"><i class="fas fa-chevron-right"></i></a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
