<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/product/model.php';

$admin = current_admin();
$logoError = '';
$logoSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_logo') {
    try {
        $saveMode = $_POST['save_mode'] ?? '';
        $file = $_FILES['logo'] ?? [];
        $logoUrl = trim($_POST['logo_url'] ?? '');

        if ($saveMode === 'file') {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                throw new RuntimeException('Vui lòng chọn một tệp logo.');
            }
            if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Không thể tải tệp logo lên.');
            }
            if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
                throw new RuntimeException('Logo quá lớn. Kích thước tối đa là 3MB.');
            }
            $mime = mime_content_type($file['tmp_name']);
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/svg+xml' => 'svg'];
            if (!isset($allowed[$mime])) {
                throw new RuntimeException('Chỉ chấp nhận JPG, PNG, WEBP hoặc SVG.');
            }
            $logoDir = __DIR__ . '/../img/logo';
            if (!is_dir($logoDir) && !mkdir($logoDir, 0777, true)) {
                throw new RuntimeException('Không tạo được thư mục lưu logo.');
            }
            $fileName = 'site-logo-' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $allowed[$mime];
            if (!move_uploaded_file($file['tmp_name'], $logoDir . '/' . $fileName)) {
                throw new RuntimeException('Không thể lưu logo lên máy chủ.');
            }
            setSetting('site_logo', 'img/logo/' . $fileName);
        } elseif ($saveMode === 'url') {
            if ($logoUrl === '') {
                throw new RuntimeException('Vui lòng dán link logo.');
            }
            if (!filter_var($logoUrl, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $logoUrl)) {
                throw new RuntimeException('Link logo không hợp lệ. Hãy dùng link bắt đầu bằng http:// hoặc https://.');
            }
            if (preg_match('~drive\.google\.com/file/d/([^/]+)~i', $logoUrl, $matches)) {
                $logoUrl = 'https://drive.google.com/uc?export=view&id=' . $matches[1];
            }
            setSetting('site_logo', $logoUrl);
        } else {
            throw new RuntimeException('Hãy chọn đúng nút Lưu tệp hoặc Lưu link.');
        }
        $logoSuccess = 'Logo đã được cập nhật.';
    } catch (Throwable $e) {
        $logoError = $e->getMessage();
    }
}

$siteLogo = getSetting('site_logo', 'https://drive.google.com/uc?export=view&id=1m-0-hXczkfAv8wzQGyb55N3DJlhQTW3Z');
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
    .logo-settings { display:grid; grid-template-columns:minmax(180px,260px) 1fr; gap:24px; align-items:center; margin:0 0 30px; padding:24px; background:#fff; border:1px solid var(--gray); border-radius:var(--radius); box-shadow:var(--shadow); }
    .logo-preview { min-height:130px; display:flex; align-items:center; justify-content:center; padding:20px; background:#faf9f7; border:1px dashed var(--gray); }
    .logo-preview img { max-width:100%; max-height:96px; object-fit:contain; }
    .logo-copy h2 { margin:0 0 6px; font-family:'Playfair Display',serif; color:var(--primary-dark); }
    .logo-copy p { margin:0 0 16px; color:var(--text-light); font-size:.9rem; }
    .logo-picker { display:flex; flex-wrap:wrap; align-items:center; gap:12px; }
    .logo-picker input[type=file], .logo-picker input[type=url] { max-width:100%; padding:10px; border:1px solid var(--gray); background:#fff; }
    .logo-picker input[type=url] { min-width:260px; flex:1; }
    .logo-message { margin:0 0 16px; padding:12px 14px; border-radius:8px; font-size:.9rem; }
    .logo-message.success { background:#eaf7ef; color:#176b3a; }
    .logo-message.error { background:#fff0f0; color:#a32222; }
    @media (max-width: 700px) { .logo-settings { grid-template-columns:1fr; } }
</style>

<section class="logo-settings" aria-labelledby="logo-settings-title">
    <div class="logo-preview">
        <img src="<?= e($siteLogo) ?>" alt="Logo hiện tại" id="adminLogoPreview">
    </div>
    <div class="logo-copy">
        <h2 id="logo-settings-title">Logo website</h2>
        <p>Chọn tệp hoặc dán link ảnh logo. Nên dùng ảnh nền trong suốt, tối đa 3MB.</p>
        <?php if ($logoSuccess): ?><div class="logo-message success" role="status"><?= e($logoSuccess) ?></div><?php endif; ?>
        <?php if ($logoError): ?><div class="logo-message error" role="alert"><?= e($logoError) ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data" class="logo-picker logo-file-form">
            <input type="hidden" name="action" value="update_logo">
            <input type="file" name="logo" id="adminLogoInput" accept="image/jpeg,image/png,image/webp,image/svg+xml">
            <button type="submit" name="save_mode" value="file" class="btn btn-primary"><i class="fas fa-upload"></i> Lưu tệp</button>
        </form>
        <form method="post" class="logo-picker logo-url-form">
            <input type="hidden" name="action" value="update_logo">
            <input type="url" name="logo_url" id="adminLogoUrl" placeholder="https://.../logo.png" autocomplete="url" value="<?= e(str_starts_with($siteLogo, 'http') ? $siteLogo : '') ?>">
            <button type="submit" name="save_mode" value="url" class="btn btn-secondary"><i class="fas fa-link"></i> Lưu link</button>
        </form>
    </div>
</section>

<script>
    document.getElementById('adminLogoInput')?.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file || !file.type.startsWith('image/')) return;
        const preview = document.getElementById('adminLogoPreview');
        const reader = new FileReader();
        reader.onload = function (event) { preview.src = event.target.result; };
        reader.readAsDataURL(file);
    });
    document.getElementById('adminLogoUrl')?.addEventListener('input', function () {
        if (document.getElementById('adminLogoInput')?.files?.length) return;
        if (this.value.trim()) document.getElementById('adminLogoPreview').src = this.value.trim();
    });
</script>

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
                            <img src="<?= e(productImage($p['image_url'])) ?>" alt="">
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
