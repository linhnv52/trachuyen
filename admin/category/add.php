<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/model.php';

$admin = current_admin();
$pageTitle = 'Thêm danh mục';
$pageSubtitle = 'Tạo danh mục mới hiển thị trên website';
$activeMenu = 'categories';

$parentCategories = getAllCategories();
$errors = [];
$old = $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');

    if ($name === '') {
        $errors['name'] = 'Vui lòng nhập tên danh mục.';
    }

    if (!$errors) {
        try {
            $imageUrl = uploadCategoryImage($_FILES['image'] ?? [], $_POST['image_url'] ?? null);
            $data = [
                'name'        => $name,
                'slug'        => $slug,
                'description' => trim($_POST['description'] ?? ''),
                'image_url'   => $imageUrl,
                'parent_id'   => $_POST['parent_id'] ?? '',
                'is_active'   => $_POST['is_active'] ?? 0,
                'sort_order'  => $_POST['sort_order'] ?? 0,
            ];
            $newId = createCategory($data);
            redirect(url('/admin/category/list.php?added=' . $newId));
        } catch (RuntimeException $ex) {
            $errors['image'] = $ex->getMessage();
        }
    }
}

require __DIR__ . '/../includes/header.php';
$category = null;
require __DIR__ . '/_form.php';
require __DIR__ . '/../includes/footer.php';