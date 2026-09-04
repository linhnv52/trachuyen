<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/model.php';

$admin = current_admin();
$pageTitle = 'Sửa sản phẩm';
$pageSubtitle = 'Cập nhật thông tin sản phẩm';
$activeMenu = 'products';

$id = (int)($_GET['id'] ?? 0);
$product = getProductById($id, true);

if (!$product) {
    redirect(url('/admin/product/list.php'));
}

$categories = getAllCategories();
$errors = [];
$old = $_POST ?: $product;

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
            $imageUrl = uploadProductImage($_FILES['image'] ?? [], $product['image_url']);

            // Gallery: (ảnh cũ - ảnh tick Xóa) + ảnh mới upload
            $existingGallery = productGallery($product['gallery'] ?? null);
            $removeGallery = array_filter((array)($_POST['remove_gallery'] ?? []));
            if ($removeGallery) {
                $existingGallery = array_values(array_diff($existingGallery, $removeGallery));
            }
            $uploadedGallery = uploadGalleryImages($_FILES['gallery'] ?? []);
            $finalGallery = array_merge($existingGallery, $uploadedGallery);

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
                'gallery' => $finalGallery ? json_encode($finalGallery, JSON_UNESCAPED_SLASHES) : null,
                'rating_avg' => $_POST['rating_avg'] ?? 0,
                'review_count' => $_POST['review_count'] ?? 0,
                'is_best_seller' => $_POST['is_best_seller'] ?? 0,
                'stock_quantity' => $_POST['stock_quantity'] ?? 0,
                'capacity' => trim($_POST['capacity'] ?? '') === '' ? null : (int)$_POST['capacity'],
                'is_active' => $_POST['is_active'] ?? 0,
            ];
            updateProduct($id, $data);
            $product = getProductById($id, true);
            $old = $product;
            $saved = true;
        } catch (RuntimeException $ex) {
            $errors['image'] = $ex->getMessage();
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>

<?php if (!empty($saved) || !empty($_GET['saved'])): ?>
    <div style="background:#e8f5e9; color:#2e7d32; padding:14px 18px; border-radius:10px; margin-bottom:20px;">
        <i class="fas fa-check-circle"></i> Đã lưu thông tin sản phẩm thành công!
    </div>
<?php endif; ?>

<?php require __DIR__ . '/_form.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>