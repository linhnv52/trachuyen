<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/model.php';

$admin = current_admin();
$pageTitle = 'Sửa danh mục';
$pageSubtitle = 'Cập nhật danh mục hiển thị trên website';
$activeMenu = 'categories';

$id = (int)($_GET['id'] ?? 0);
$category = $id ? getCategoryById($id) : null;

if (!$category) {
    redirect(url('/admin/category/list.php'));
}

$parentCategories = array_values(array_filter(getAllCategories(true), fn($c) => (int)$c['id'] !== $id));
$errors = [];
$old = $_POST ?: $category;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        $errors['name'] = 'Vui lòng nhập tên danh mục.';
    }

    if (!$errors) {
        try {
            $imageUrl = uploadCategoryImage($_FILES['image'] ?? [], $category['image_url']);
            $data = [
                'name'        => $name,
                'slug'        => trim($_POST['slug'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'image_url'   => $imageUrl,
                'parent_id'   => $_POST['parent_id'] ?? '',
                'is_active'   => $_POST['is_active'] ?? 0,
                'sort_order'  => $_POST['sort_order'] ?? 0,
            ];
            updateCategory($id, $data);
            $category = getCategoryById($id);
            $old = $category;
            $saved = true;
        } catch (RuntimeException $ex) {
            $errors['image'] = $ex->getMessage();
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>
<?php if (!empty($saved)): ?>
    <div style="padding:14px 18px; border-radius:10px; margin-bottom:20px; background:#e8f5e9; color:#2e7d32;">
        <i class="fas fa-check-circle"></i> Đã lưu danh mục: <strong><?= e($category['name']) ?></strong>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/_form.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>