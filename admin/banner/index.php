<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/model.php';

$admin = current_admin();
$pageTitle = 'Banner trang chủ';
$pageSubtitle = 'Quản lý ảnh slider hiển thị trên trang chủ website';
$activeMenu = 'banners';

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'save') {
            $banner = getBannerById((int)($_POST['id'] ?? 0));
            if (!$banner) {
                throw new RuntimeException('Không tìm thấy banner.');
            }
            $imageUrl = uploadBannerImage($_FILES['image'] ?? []) ?? $banner['image_url'];
            updateBanner((int)$banner['id'], [
                'image_url'  => $imageUrl,
                'sort_order' => $_POST['sort_order'] ?? 0,
                'is_active'  => isset($_POST['is_active']),
            ]);
            $flash = ['type' => 'success', 'msg' => 'Đã lưu banner #' . $banner['id'] . '.'];
        } elseif ($action === 'add') {
            $imageUrl = uploadBannerImage($_FILES['image'] ?? []);
            if (!$imageUrl) {
                throw new RuntimeException('Vui lòng chọn ảnh cho banner mới.');
            }
            createBanner([
                'image_url'  => $imageUrl,
                'sort_order' => $_POST['sort_order'] ?? 0,
                'is_active'  => isset($_POST['is_active']),
            ]);
            $flash = ['type' => 'success', 'msg' => 'Đã thêm banner mới.'];
        } elseif ($action === 'delete') {
            $banner = getBannerById((int)($_POST['id'] ?? 0));
            if (!$banner) {
                throw new RuntimeException('Không tìm thấy banner.');
            }
            deleteBanner((int)$banner['id']);
            $flash = ['type' => 'success', 'msg' => 'Đã xóa banner #' . $banner['id'] . '.'];
        }
    } catch (RuntimeException $ex) {
        $flash = ['type' => 'error', 'msg' => $ex->getMessage()];
    }
}

$banners = getBanners();

require __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?>
    <div style="padding:14px 18px; border-radius:10px; margin-bottom:20px; <?= $flash['type'] === 'success' ? 'background:#e8f5e9; color:#2e7d32;' : 'background:#fdecea; color:#c62828;' ?>">
        <?= e($flash['msg']) ?>
    </div>
<?php endif; ?>

<div class="toolbar">
    <div class="toolbar-left" style="color:var(--text-light);">
        <i class="fas fa-circle-info"></i> Banner được hiển thị theo thứ tự từ trái sang phải trên slider trang chủ.
        <a href="<?= e(url('/index.php')) ?>" target="_blank" style="margin-left:6px;">Xem trang chủ <i class="fas fa-external-link-alt"></i></a>
    </div>
</div>

<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(340px, 1fr)); gap:20px;">
    <?php foreach ($banners as $b): ?>
        <div class="table-container" style="padding:16px; display:flex; flex-direction:column; gap:12px;">
            <img src="<?= e(categoryImage($b['image_url'])) ?>" alt="Banner #<?= (int)$b['id'] ?>"
                 style="width:100%; height:130px; object-fit:cover; border-radius:8px; background:#f5f0eb;">

            <form method="post" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:10px;">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">

                <label style="font-size:0.85rem; color:var(--text-light);">Ảnh hiện tại: <span style="color:var(--text);"><?= e($b['image_url']) ?></span></label>
                <input type="file" name="image" accept="image/*" style="font-size:0.85rem;">

                <div style="display:flex; gap:12px; align-items:center;">
                    <div>
                        <label style="display:block; font-size:0.85rem; color:var(--text-light); margin-bottom:4px;">Thứ tự</label>
                        <input type="number" name="sort_order" value="<?= (int)$b['sort_order'] ?>" min="0"
                               style="width:80px; padding:8px 10px; border:1px solid #e0d6cc; border-radius:8px;">
                    </div>
                    <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem; margin-top:18px; cursor:pointer;">
                        <input type="checkbox" name="is_active" <?= $b['is_active'] ? 'checked' : '' ?>> Hiển thị
                    </label>
                    <button type="submit" class="btn btn-primary btn-sm" style="margin-top:18px; margin-left:auto;">
                        <i class="fas fa-save"></i> Lưu
                    </button>
                </div>
            </form>

            <form method="post" onsubmit="return confirm('Xóa banner #<?= (int)$b['id'] ?>?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm" style="width:100%;">
                    <i class="fas fa-trash"></i> Xóa banner này
                </button>
            </form>
        </div>
    <?php endforeach; ?>

    <?php if (!$banners): ?>
        <div class="table-container" style="padding:50px; grid-column:1/-1; text-align:center; color:var(--text-light);">
            <i class="fas fa-images" style="font-size:3rem; color:#d4c5b5; margin-bottom:12px; display:block;"></i>
            Chưa có banner nào. Thêm banner đầu tiên bên dưới.
        </div>
    <?php endif; ?>

    <!-- Thêm banner mới -->
    <div class="table-container" style="padding:16px; display:flex; flex-direction:column; gap:12px; border-style:dashed;">
        <h3 style="margin:0; font-size:1rem;"><i class="fas fa-plus"></i> Thêm banner mới</h3>
        <form method="post" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:10px;">
            <input type="hidden" name="action" value="add">

            <input type="file" name="image" accept="image/*" required style="font-size:0.85rem;">

            <div style="display:flex; gap:12px; align-items:center;">
                <div>
                    <label style="display:block; font-size:0.85rem; color:var(--text-light); margin-bottom:4px;">Thứ tự</label>
                    <input type="number" name="sort_order" value="<?= count($banners) + 1 ?>" min="0"
                           style="width:80px; padding:8px 10px; border:1px solid #e0d6cc; border-radius:8px;">
                </div>
                <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem; margin-top:18px; cursor:pointer;">
                    <input type="checkbox" name="is_active" checked> Hiển thị
                </label>
                <button type="submit" class="btn btn-success btn-sm" style="margin-top:18px; margin-left:auto;">
                    <i class="fas fa-plus"></i> Thêm
                </button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
