<?php
/**
 * Model quản lý sản phẩm
 */

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $map = [
        'à'=>'a','á'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a','ă'=>'a','ắ'=>'a','ằ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a',
        'â'=>'a','ấ'=>'a','ầ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a',
        'đ'=>'d','è'=>'e','é'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e','ê'=>'e','ế'=>'e','ề'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e',
        'ì'=>'i','í'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i',
        'ò'=>'o','ó'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o','ô'=>'o','ố'=>'o','ồ'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o',
        'ơ'=>'o','ớ'=>'o','ờ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o',
        'ù'=>'u','ú'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u','ư'=>'u','ứ'=>'u','ừ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u',
        'ỳ'=>'y','ý'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y',
    ];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text ?: 'san-pham';
}

function uniqueSlug(string $name, ?int $ignoreId = null): string
{
    $base = slugify($name);
    $slug = $base;
    $i = 1;
    while (true) {
        $stmt = db()->prepare('SELECT id FROM products WHERE slug = ? AND id != ?');
        $stmt->execute([$slug, $ignoreId ?? 0]);
        if (!$stmt->fetch()) {
            return $slug;
        }
        $slug = $base . '-' . (++$i);
    }
}

function uniqueCode(?string $code, ?int $ignoreId = null): string
{
    $code = strtoupper(trim((string)$code));
    if ($code === '') {
        $code = 'TC-' . strtoupper(uniqid());
    }
    $stmt = db()->prepare('SELECT id FROM products WHERE code = ? AND id != ?');
    $stmt->execute([$code, $ignoreId ?? 0]);
    if ($stmt->fetch()) {
        $code = 'TC-' . strtoupper(uniqid());
    }
    return $code;
}

function getAllCategories(bool $includeInactive = false): array
{
    $sql = 'SELECT * FROM categories';
    if (!$includeInactive) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY sort_order, id';
    $stmt = db()->query($sql);
    return $stmt->fetchAll();
}

function getProductById(int $id, bool $includeInactive = false): ?array
{
    $sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM products p
            JOIN categories c ON c.id = p.category_id
            WHERE p.id = ?';
    if (!$includeInactive) {
        $sql .= ' AND p.is_active = 1';
    }
    $stmt = db()->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Tìm kiếm sản phẩm cho gợi ý (smart search), không phân trang
 */
function searchProducts(string $q, int $limit = 8, bool $includeInactive = false): array
{
    $q = trim($q);
    if ($q === '') {
        return [];
    }
    $like = '%' . $q . '%';
    $likeKey = '%' . normalizeText($q) . '%';

    $sql = 'SELECT p.id, p.code, p.name, p.price, p.old_price, p.image_url,
                   p.rating_avg, p.review_count, p.badge, c.name AS category_name
            FROM products p
            JOIN categories c ON c.id = p.category_id
            WHERE (p.name LIKE ? OR p.code LIKE ? OR p.search_key LIKE ?)';
    $params = [$like, $like, $likeKey];
    if (!$includeInactive) {
        $sql .= ' AND p.is_active = 1';
    }
    $sql .= ' ORDER BY p.is_best_seller DESC, p.rating_avg DESC, p.created_at DESC LIMIT ' . (int)$limit;

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Lấy danh sách sản phẩm kèm tìm kiếm/lọc/sắp xếp/phân trang
 *
 * @return array{items: array, total: int}
 */
function getAllProducts(array $filters = [], int $page = 1, int $perPage = 8): array
{
    $where  = [];
    $params = [];

    if (!empty($filters['search'])) {
        $where[] = '(p.name LIKE ? OR p.code LIKE ? OR p.search_key LIKE ?)';
        $like = '%' . $filters['search'] . '%';
        $likeKey = '%' . normalizeText($filters['search']) . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $likeKey;
    }
    if (!empty($filters['active_only'])) {
        $where[] = 'p.is_active = 1';
    }
    if (!empty($filters['category_id'])) {
        $where[] = 'p.category_id = ?';
        $params[] = (int)$filters['category_id'];
    }
    if (!empty($filters['category_ids']) && is_array($filters['category_ids'])) {
        $ids = array_values(array_filter(array_map('intval', $filters['category_ids'])));
        if ($ids) {
            $where[] = 'p.category_id IN (' . implode(',', $ids) . ')';
        }
    }
    if (isset($filters['status']) && $filters['status'] !== '') {
        $where[] = 'p.is_active = ?';
        $params[] = (int)$filters['status'];
    }
    if (isset($filters['min_price']) && $filters['min_price'] !== '') {
        $where[] = 'p.price >= ?';
        $params[] = (float)$filters['min_price'];
    }
    if (isset($filters['max_price']) && $filters['max_price'] !== '') {
        $where[] = 'p.price <= ?';
        $params[] = (float)$filters['max_price'];
    }
    if (isset($filters['capacity_min']) && $filters['capacity_min'] !== '' && $filters['capacity_min'] !== null) {
        $where[] = 'p.capacity >= ?';
        $params[] = (int)$filters['capacity_min'];
    }
    if (isset($filters['capacity_max']) && $filters['capacity_max'] !== '' && $filters['capacity_max'] !== null) {
        $where[] = 'p.capacity <= ?';
        $params[] = (int)$filters['capacity_max'];
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $sortMap = [
        'newest'     => 'p.created_at DESC',
        'oldest'     => 'p.created_at ASC',
        'price-asc'  => 'p.price ASC',
        'price-desc' => 'p.price DESC',
        'name'       => 'p.name ASC',
        'rating'     => 'p.rating_avg DESC',
        'best'       => 'p.is_best_seller DESC, p.created_at DESC',
    ];
    $sort = $sortMap[$filters['sort'] ?? 'newest'] ?? 'p.created_at DESC';

    $countStmt = db()->prepare("SELECT COUNT(*) FROM products p $whereSql");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $offset = ($page - 1) * $perPage;
    $stmt = db()->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug
                           FROM products p
                           JOIN categories c ON c.id = p.category_id
                           $whereSql
                           ORDER BY $sort
                           LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    return ['items' => $items, 'total' => $total];
}

function createProduct(array $data): int
{
    $slug = uniqueSlug($data['name']);
    $code = uniqueCode($data['code'] ?? '');
    $searchKey = normalizeText(trim($data['name']) . ' ' . $code . ' ' . ($data['short_description'] ?? ''));

    $stmt = db()->prepare('INSERT INTO products
        (code, search_key, category_id, name, slug, description, short_description, price, old_price, badge, image_url, gallery,
         rating_avg, review_count, is_best_seller, stock_quantity, capacity, is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');

    $stmt->execute([
        $code,
        $searchKey,
        (int)$data['category_id'],
        trim($data['name']),
        $slug,
        $data['description'] ?? null,
        $data['short_description'] ?? null,
        (float)$data['price'],
        $data['old_price'] !== '' ? (float)$data['old_price'] : null,
        $data['badge'] ?? '',
        $data['image_url'] ?? null,
        $data['gallery'] ?? null,
        (float)($data['rating_avg'] ?? 0),
        (int)($data['review_count'] ?? 0),
        !empty($data['is_best_seller']) ? 1 : 0,
        (int)($data['stock_quantity'] ?? 0),
        !empty($data['capacity']) ? (int)$data['capacity'] : null,
        !empty($data['is_active']) ? 1 : 0,
    ]);

    return (int)db()->lastInsertId();
}

function updateProduct(int $id, array $data): void
{
    $slug = uniqueSlug($data['name'], $id);
    $code = uniqueCode($data['code'] ?? '', $id);
    $searchKey = normalizeText(trim($data['name']) . ' ' . $code . ' ' . ($data['short_description'] ?? ''));

    $stmt = db()->prepare('UPDATE products SET
        code = ?, search_key = ?, category_id = ?, name = ?, slug = ?, description = ?, short_description = ?,
        price = ?, old_price = ?, badge = ?, image_url = ?, gallery = ?,
        rating_avg = ?, review_count = ?, is_best_seller = ?, stock_quantity = ?, capacity = ?, is_active = ?
        WHERE id = ?');

    $stmt->execute([
        $code,
        $searchKey,
        (int)$data['category_id'],
        trim($data['name']),
        $slug,
        $data['description'] ?? null,
        $data['short_description'] ?? null,
        (float)$data['price'],
        $data['old_price'] !== '' ? (float)$data['old_price'] : null,
        $data['badge'] ?? '',
        $data['image_url'] ?? null,
        $data['gallery'] ?? null,
        (float)($data['rating_avg'] ?? 0),
        (int)($data['review_count'] ?? 0),
        !empty($data['is_best_seller']) ? 1 : 0,
        (int)($data['stock_quantity'] ?? 0),
        !empty($data['capacity']) ? (int)$data['capacity'] : null,
        !empty($data['is_active']) ? 1 : 0,
        $id,
    ]);
}

function deleteProduct(int $id): void
{
    $stmt = db()->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$id]);
}

/**
 * Xử lý upload ảnh sản phẩm.
 * Trả về đường dẫn tương đối lưu vào DB, hoặc giữ nguyên giá trị cũ.
 */
function uploadProductImage(array $file, ?string $currentImage = null): ?string
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

    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0777, true)) {
        throw new RuntimeException('Không tạo được thư mục upload.');
    }

    $name = 'p' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $dest = UPLOAD_DIR . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Không thể lưu ảnh lên máy chủ.');
    }

    return UPLOAD_URL . $name;
}

/**
 * Giải mã gallery JSON của sản phẩm thành mảng đường dẫn ảnh.
 */
function productGallery(?string $json): array
{
    if ($json === null || $json === '') {
        return [];
    }
    $arr = json_decode($json, true);
    return is_array($arr) ? array_values(array_filter($arr)) : [];
}

/**
 * Upload nhiều ảnh phụ (gallery). Bỏ qua ô trống,
 * lỗi bất kỳ file nào sẽ dừng và ném exception. Trả về mảng đường dẫn.
 */
function uploadGalleryImages(array $files): array
{
    $paths = [];
    if (empty($files['name']) || !is_array($files['name'])) {
        return $paths;
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0777, true)) {
        throw new RuntimeException('Không tạo được thư mục upload.');
    }

    foreach ($files['name'] as $i => $name) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || $name === '' || $name === null) {
            continue;
        }

        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Lỗi tải lên "' . $name . '" (mã ' . $files['error'][$i] . ').');
        }
        $mime = mime_content_type($files['tmp_name'][$i]);
        if (!isset($allowed[$mime])) {
            throw new RuntimeException('"' . $name . '" không phải ảnh JPG/PNG/WEBP/GIF.');
        }
        if ($files['size'][$i] > 5 * 1024 * 1024) {
            throw new RuntimeException('"' . $name . '" quá lớn (tối đa 5MB).');
        }

        $stored = 'g' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($files['tmp_name'][$i], UPLOAD_DIR . $stored)) {
            throw new RuntimeException('Không thể lưu ảnh "' . $name . '".');
        }
        $paths[] = UPLOAD_URL . $stored;
    }

    return $paths;
}