<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/product/model.php';

$admin = current_admin();
$pageTitle = 'Bảng điều khiển';
$pageSubtitle = 'Tổng quan hoạt động của cửa hàng';
$activeMenu = 'dashboard';

$totalProducts  = (int)db()->query('SELECT COUNT(*) FROM products')->fetchColumn();
$activeProducts = (int)db()->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();
$totalCategories = (int)db()->query('SELECT COUNT(*) FROM categories WHERE is_active = 1')->fetchColumn();
$bestSellers    = (int)db()->query('SELECT COUNT(*) FROM products WHERE is_best_seller = 1')->fetchColumn();

$recent = db()->query('SELECT p.*, c.name AS category_name
                       FROM products p
                       JOIN categories c ON c.id = p.category_id
                       ORDER BY p.created_at DESC
                       LIMIT 6')->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<style>
    .stats-grid { grid-template-columns: repeat(4, 1fr); }
    @media (max-width: 768px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    .stat-card .icon.purple { background: #f3e5f5; color: #7b1fa2; }
</style>

<section class="stats-grid">
    <div class="stat-card">
        <div class="icon blue"><i class="fas fa-box"></i></div>
        <div class="info"><h3><?= $totalProducts ?></h3><p>Tổng sản phẩm</p></div>
    </div>
    <div class="stat-card">
        <div class="icon green"><i class="fas fa-check-circle"></i></div>
        <div class="info"><h3><?= $activeProducts ?></h3><p>Đang hoạt động</p></div>
    </div>
    <div class="stat-card">
        <div class="icon gold"><i class="fas fa-tags"></i></div>
        <div class="info"><h3><?= $totalCategories ?></h3><p>Danh mục</p></div>
    </div>
    <div class="stat-card">
        <div class="icon purple"><i class="fas fa-fire"></i></div>
        <div class="info"><h3><?= $bestSellers ?></h3><p>Bán chạy</p></div>
    </div>
</section>

<div class="toolbar" style="justify-content:space-between;">
    <div class="toolbar-left">
        <h2 style="font-family:'Playfair Display',serif; color:var(--primary-dark);">Sản phẩm mới nhất</h2>
    </div>
    <div class="toolbar-right">
        <a href="<?= e(url('/admin/product/add.php')) ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm sản phẩm mới</a>
    </div>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>Danh mục</th>
                <th>Giá</th>
                <th>Tồn kho</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$recent): ?>
                <tr><td colspan="6" style="text-align:center; padding:40px; color:var(--text-light);">Chưa có sản phẩm nào.</td></tr>
            <?php else: foreach ($recent as $p): ?>
                <tr>
                    <td>
                        <div class="product-cell">
                            <img src="<?= e($p['image_url'] ?: url('/img/placeholder.svg')) ?>" alt="">
                            <div>
                                <div class="name"><?= e($p['name']) ?></div>
                                <div class="sku"><?= e($p['code']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= e($p['category_name']) ?></td>
                    <td><?= formatPrice($p['price']) ?>đ</td>
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
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    <div class="table-footer">
        <div class="info"><a href="<?= e(url('/admin/product/list.php')) ?>" style="color:var(--gold); text-decoration:none;">Xem tất cả sản phẩm →</a></div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>