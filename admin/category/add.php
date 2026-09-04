<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/model.php';

$admin = current_admin();
$section = $_POST['section'] ?? ($_GET['section'] ?? ($_SESSION['admin_category_section'] ?? 'tea'));
$sectionTitles = [
    'tea' => 'sản phẩm trà',
    'gift' => 'hộp quà tặng',
    'ceramics' => 'gốm sứ',
    'teapot' => 'ấm tử sa',
];
if (!isset($sectionTitles[$section])) $section = 'tea';
$pageTitle = 'Thêm danh mục ' . $sectionTitles[$section];
$pageSubtitle = 'Tạo danh mục mới trong nhóm ' . $sectionTitles[$section];
$categoryFormAction = url('/admin/category/add.php?section=' . urlencode($section));
$activeMenu = 'categories';

$parentCategories = getAllCategories();
$sectionRootIds = ensureCategorySectionRoots();
$sectionParentId = $sectionRootIds[$section];
$errors = [];
$old = $_POST;
$old['section'] = $section;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
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
                'parent_id'   => $sectionParentId,
                'is_active'   => $_POST['is_active'] ?? 0,
                'sort_order'  => $_POST['sort_order'] ?? 0,
            ];
            $newId = createCategory($data);
            redirect(url('/admin/category/list.php?section=' . urlencode($section) . '&added=' . $newId));
        } catch (RuntimeException $ex) {
            $errors['image'] = $ex->getMessage();
        }
    }
}

require __DIR__ . '/../includes/header.php';
$category = null;
require __DIR__ . '/_form.php';
require __DIR__ . '/../includes/footer.php';
