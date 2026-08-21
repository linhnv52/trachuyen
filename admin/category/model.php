<?php
/**
 * Model quản lý danh mục
 */
require_once __DIR__ . '/../product/model.php';

function getCategoryById(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function uniqueCategorySlug(string $name, ?int $ignoreId = null): string
{
    $base = slugify($name);
    $slug = $base;
    $i = 1;
    while (true) {
        $stmt = db()->prepare('SELECT id FROM categories WHERE slug = ? AND id != ?');
        $stmt->execute([$slug, $ignoreId ?? 0]);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . (++$i);
    }
}

function createCategory(array $data): int
{
    $slug = uniqueCategorySlug($data['slug'] !== '' ? $data['slug'] : $data['name']);

    $stmt = db()->prepare('INSERT INTO categories
        (name, slug, description, image_url, parent_id, is_active, sort_order)
        VALUES (?,?,?,?,?,?,?)');

    $stmt->execute([
        trim($data['name']),
        $slug,
        $data['description'] ?? null,
        $data['image_url'] ?? null,
        $data['parent_id'] !== '' ? (int)$data['parent_id'] : null,
        !empty($data['is_active']) ? 1 : 0,
        (int)($data['sort_order'] ?? 0),
    ]);

    return (int)db()->lastInsertId();
}

function updateCategory(int $id, array $data): void
{
    $slug = uniqueCategorySlug($data['slug'] !== '' ? $data['slug'] : $data['name'], $id);

    $stmt = db()->prepare('UPDATE categories SET
        name = ?, slug = ?, description = ?, image_url = ?, parent_id = ?, is_active = ?, sort_order = ?
        WHERE id = ?');

    $stmt->execute([
        trim($data['name']),
        $slug,
        $data['description'] ?? null,
        $data['image_url'] ?? null,
        $data['parent_id'] !== '' ? (int)$data['parent_id'] : null,
        !empty($data['is_active']) ? 1 : 0,
        (int)($data['sort_order'] ?? 0),
        $id,
    ]);
}

function deleteCategory(int $id): void
{
    $productCount = (int)db()->query('SELECT COUNT(*) FROM products WHERE category_id = ' . (int)$id)->fetchColumn();
    if ($productCount > 0) {
        throw new RuntimeException('Không thể xóa danh mục đang chứa ' . $productCount . ' sản phẩm. Hãy chuyển hoặc xóa sản phẩm trước.');
    }

    $childCount = (int)db()->query('SELECT COUNT(*) FROM categories WHERE parent_id = ' . (int)$id)->fetchColumn();
    if ($childCount > 0) {
        throw new RuntimeException('Không thể xóa danh mục đang chứa ' . $childCount . ' danh mục con.');
    }

    db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
}

/**
 * Upload ảnh danh mục. Trả về đường dẫn tương đối lưu vào DB, hoặc giữ ảnh cũ.
 */
function uploadCategoryImage(array $file, ?string $currentImage = null): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $currentImage;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Lỗi tải lên ảnh (mã ' . $file['error'] . ').');
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime = mime_content_type($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Định dạng ảnh không hỗ trợ. Chỉ chấp nhận JPG, PNG, WEBP, GIF.');
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Ảnh quá lớn (tối đa 5MB).');
    }

    if (!is_dir(CATEGORY_UPLOAD_DIR) && !mkdir(CATEGORY_UPLOAD_DIR, 0777, true)) {
        throw new RuntimeException('Không tạo được thư mục upload.');
    }

    $name = 'c' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $dest = CATEGORY_UPLOAD_DIR . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Không thể lưu ảnh lên máy chủ.');
    }

    return CATEGORY_UPLOAD_URL . $name;
}

/**
 * Số sản phẩm theo từng danh mục (id => count)
 */
function categoryProductCounts(): array
{
    $rows = db()->query('SELECT category_id, COUNT(*) AS c FROM products GROUP BY category_id')->fetchAll();
    $map = [];
    foreach ($rows as $row) {
        $map[(int)$row['category_id']] = (int)$row['c'];
    }
    return $map;
}