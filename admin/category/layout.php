<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/model.php';

$admin = current_admin();
$pageTitle = 'Bố cục danh mục trà';
$pageSubtitle = 'Thiết lập ảnh lớn, mô tả và thứ tự hiển thị trên trang chủ';
$activeMenu = 'category-layout';
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $category = getCategoryById($categoryId);
    if (!$category) {
        $flash = ['type' => 'error', 'msg' => 'Không tìm thấy danh mục cần cập nhật.'];
    } else {
        try {
            $imageUrl = uploadCategoryImage($_FILES['image'] ?? [], $category['image_url'] ?? null);
            $description = trim($_POST['description'] ?? '');
            $sortOrder = max(0, (int)($_POST['sort_order'] ?? 0));
            $isActive = !empty($_POST['is_active']) ? 1 : 0;
            db()->prepare('UPDATE categories SET description = ?, image_url = ?, sort_order = ?, is_active = ? WHERE id = ?')
                ->execute([$description, $imageUrl, $sortOrder, $isActive, $categoryId]);
            $flash = ['type' => 'success', 'msg' => 'Đã lưu bố cục danh mục: ' . $category['name']];
        } catch (RuntimeException $ex) {
            $flash = ['type' => 'error', 'msg' => $ex->getMessage()];
        }
    }
}

$categories = getAllCategories();
require __DIR__ . '/../includes/header.php';
?>

<style>
    .layout-intro { background:#f7f3ef; border-left:3px solid var(--gold); padding:18px 22px; margin-bottom:22px; color:var(--text-light); line-height:1.6; }
    .layout-editor { display:grid; grid-template-columns:280px minmax(0, 1fr); gap:26px; background:#fff; border-radius:var(--radius); box-shadow:var(--shadow); padding:22px; margin-bottom:20px; }
    .layout-editor-preview { aspect-ratio:4/3; overflow:hidden; border-radius:12px; background:var(--bg); }
    .layout-editor-preview img { width:100%; height:100%; object-fit:cover; }
    .layout-editor h2 { margin:0 0 16px; font-family:'Playfair Display',serif; font-weight:400; color:var(--primary-dark); }
    .layout-editor label { display:block; font-size:.82rem; font-weight:600; color:var(--primary-dark); margin:0 0 6px; }
    .layout-editor textarea, .layout-editor input[type=number] { width:100%; border:1px solid var(--gray); border-radius:9px; padding:10px 12px; font:inherit; color:var(--text); margin-bottom:14px; }
    .layout-editor textarea { min-height:108px; resize:vertical; line-height:1.55; }
    .layout-editor textarea:focus, .layout-editor input:focus { outline:none; border-color:var(--gold); box-shadow:0 0 0 3px rgba(184,134,11,.12); }
    .layout-editor .meta { display:flex; gap:16px; align-items:center; flex-wrap:wrap; }
    .layout-editor .meta input[type=number] { width:100px; margin:0; }
    .layout-editor .check { display:flex; gap:8px; align-items:center; font-size:.86rem; color:var(--text); }
    .layout-editor .check input { width:17px; height:17px; accent-color:var(--gold); }
    .layout-editor .upload { margin:12px 0 16px; font-size:.82rem; color:var(--text-light); }
    @media (max-width:700px) { .layout-editor { grid-template-columns:1fr; padding:16px; } }
</style>

<?php if ($flash): ?>
    <div style="padding:14px 18px; border-radius:10px; margin-bottom:20px; <?= $flash['type'] === 'success' ? 'background:#e8f5e9; color:#2e7d32;' : 'background:#fdecea; color:#c62828;' ?>">
        <?= e($flash['msg']) ?>
    </div>
<?php endif; ?>

<div class="layout-intro">
    Mỗi danh mục đang hoạt động sẽ trở thành một khối ảnh lớn trên trang chủ. Tải ảnh mới, viết mô tả và điều chỉnh thứ tự tại đây; thay đổi sẽ dùng ngay trên môi trường local.
</div>

<?php foreach ($categories as $category): ?>
    <section class="layout-editor">
        <div class="layout-editor-preview">
            <img src="<?= e(categoryImage($category['image_url'])) ?>" alt="<?= e($category['name']) ?>">
        </div>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="category_id" value="<?= (int)$category['id'] ?>">
            <h2><?= e($category['name']) ?></h2>
            <label for="description-<?= (int)$category['id'] ?>">Mô tả hiển thị</label>
            <textarea id="description-<?= (int)$category['id'] ?>" name="description" placeholder="Viết mô tả ngắn cho dòng trà..."><?= e($category['description'] ?? '') ?></textarea>
            <label for="image-<?= (int)$category['id'] ?>">Ảnh bố cục</label>
            <input class="upload" id="image-<?= (int)$category['id'] ?>" type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
            <div class="meta">
                <label class="check"><input type="checkbox" name="is_active" value="1" <?= !empty($category['is_active']) ? 'checked' : '' ?>> Hiển thị trên trang chủ</label>
                <label style="display:flex; gap:8px; align-items:center;">Thứ tự <input type="number" name="sort_order" value="<?= (int)$category['sort_order'] ?>" min="0"></label>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu bố cục</button>
            </div>
        </form>
    </section>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
