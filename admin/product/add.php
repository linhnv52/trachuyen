<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/model.php';

$admin = current_admin();
$pageTitle = 'Thêm sản phẩm';
$pageSubtitle = 'Tạo sản phẩm mới cho cửa hàng';
$activeMenu = 'products';

$categories = getAllCategories();
$errors = [];
$old = $_POST;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        $errors['name'] = 'Vui lòng nhập tên sản phẩm.';
    }
    if (empty($_POST['category_id'])) {
        $errors['category_id'] = 'Vui lòng chọn danh mục.';
    }
    if ($_POST['price'] === '' || (float)$_POST['price'] < 0) {
        $errors['price'] = 'Vui lòng nhập giá bán hợp lệ.';
    }

    if (!$errors) {
        try {
            $imageUrl = uploadProductImage($_FILES['image'] ?? []);
            $uploadedGallery = uploadGalleryImages($_FILES['gallery'] ?? []);
            $data = [
                'code' => $_POST['code'] ?? '',
                'category_id' => (int)$_POST['category_id'],
                'name' => $name,
                'description' => trim($_POST['description'] ?? ''),
                'short_description' => trim($_POST['short_description'] ?? ''),
                'price' => (float)$_POST['price'],
                'old_price' => $_POST['old_price'] ?? '',
                'badge' => $_POST['badge'] ?? '',
                'image_url' => $imageUrl,
                'gallery' => $uploadedGallery ? json_encode($uploadedGallery, JSON_UNESCAPED_SLASHES) : null,
                'rating_avg' => $_POST['rating_avg'] ?? 0,
                'review_count' => $_POST['review_count'] ?? 0,
                'is_best_seller' => $_POST['is_best_seller'] ?? 0,
                'stock_quantity' => $_POST['stock_quantity'] ?? 0,
                'capacity' => trim($_POST['capacity'] ?? '') === '' ? null : (int)$_POST['capacity'],
                'is_active' => $_POST['is_active'] ?? 0,
            ];
            $newId = createProduct($data);
            redirect(url('/admin/product/update.php?id=' . $newId . '&saved=1'));
        } catch (RuntimeException $ex) {
            $errors['image'] = $ex->getMessage();
        }
    }
}

require __DIR__ . '/../includes/header.php';
$product = null;
require __DIR__ . '/_form.php';
require __DIR__ . '/../includes/footer.php';