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

/**
 * Bốn nhóm danh mục cấp cao nhất. Các bản ghi này chỉ dùng để phân nhóm;
 * admin thao tác với những danh mục con bên trong từng nhóm.
 */
function categorySectionRoots(): array
{
    return [
        'tea' => ['name' => 'Sản phẩm trà', 'slug' => 'tra', 'sort_order' => 1],
        'gift' => ['name' => 'Hộp quà tặng', 'slug' => 'hop-qua-tang', 'sort_order' => 2],
        'ceramics' => ['name' => 'Gốm sứ', 'slug' => 'gomsu', 'sort_order' => 3],
        'teapot' => ['name' => 'Ấm tử sa', 'slug' => 'amtusa', 'sort_order' => 4],
    ];
}

/** Tạo các nhóm cha còn thiếu và trả về section => id. */
function ensureCategorySectionRoots(): array
{
    $pdo = db();
    $rootIds = [];
    $createdRoot = false;
    $find = $pdo->prepare('SELECT id FROM categories WHERE slug = ? LIMIT 1');
    $insert = $pdo->prepare('INSERT INTO categories (name, slug, description, parent_id, is_active, sort_order) VALUES (?, ?, ?, NULL, 1, ?)');

    foreach (categorySectionRoots() as $section => $root) {
        $find->execute([$root['slug']]);
        $id = $find->fetchColumn();
        if (!$id) {
            $insert->execute([$root['name'], $root['slug'], 'Nhóm danh mục ' . $root['name'], $root['sort_order']]);
            $id = $pdo->lastInsertId();
            $createdRoot = true;
        }
        $rootIds[$section] = (int)$id;
    }

    // Chỉ chạy khi vừa tạo hệ thống nhóm cha: phân loại dữ liệu cấp 1 cũ.
    if ($createdRoot) {
        $rootIdList = implode(',', array_map('intval', array_values($rootIds)));
        $rows = $pdo->query("SELECT id, name, slug FROM categories WHERE parent_id IS NULL AND id NOT IN ($rootIdList)")->fetchAll();
        $move = $pdo->prepare('UPDATE categories SET parent_id = ? WHERE id = ?');
        foreach ($rows as $row) {
            $haystack = normalizeText($row['name'] . ' ' . $row['slug']);
            if (preg_match('/hop|qua/', $haystack)) {
                $parentId = $rootIds['gift'];
            } elseif (preg_match('/gom|chen|khay|coc|tra-cu/', $haystack)) {
                $parentId = $rootIds['ceramics'];
            } elseif (preg_match('/am-tu-sa|tu-sa|nghi-hung/', $haystack)) {
                $parentId = $rootIds['teapot'];
            } else {
                $parentId = $rootIds['tea'];
            }
            $move->execute([$parentId, (int)$row['id']]);
        }
    }

    return $rootIds;
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
        ($data['parent_id'] !== '' && $data['parent_id'] !== null) ? (int)$data['parent_id'] : null,
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
        ($data['parent_id'] !== '' && $data['parent_id'] !== null) ? (int)$data['parent_id'] : null,
        !empty($data['is_active']) ? 1 : 0,
        (int)($data['sort_order'] ?? 0),
        $id,
    ]);
}

function deleteCategory(int $id): void
{
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

/**
 * Các danh mục hợp lệ để chọn làm cha: cùng gốc nhóm (rootId + toàn bộ con cháu),
 * loại bỏ các id trong $excludeIds (chính nó và đời con để chống vòng lặp).
 * Dùng cho dropdown form và kiểm tra server-side.
 */
function categoryParentOptions(int $rootId, array $excludeIds = []): array
{
    $all = getAllCategories(true);
    $allowedIds = categoryWithDescendantIds($rootId, $all);
    $exclude = array_flip(array_map('intval', $excludeIds));
    return array_values(array_filter($all, static function (array $c) use ($allowedIds, $exclude): bool {
        $id = (int)$c['id'];
        return in_array($id, $allowedIds, true) && !isset($exclude[$id]);
    }));
}
