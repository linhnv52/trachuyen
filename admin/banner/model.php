<?php
/**
 * Model quản lý banner trang chủ
 */
require_once __DIR__ . '/../includes/auth.php';

function getBanners(bool $activeOnly = false): array
{
    $sql = 'SELECT * FROM banners';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY sort_order, id';
    return db()->query($sql)->fetchAll();
}

function getBannerById(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM banners WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function createBanner(array $data): int
{
    $stmt = db()->prepare('INSERT INTO banners (image_url, sort_order, is_active) VALUES (?,?,?)');
    $stmt->execute([
        $data['image_url'],
        max(0, (int)$data['sort_order']),
        !empty($data['is_active']) ? 1 : 0,
    ]);
    return (int)db()->lastInsertId();
}

function updateBanner(int $id, array $data): void
{
    $stmt = db()->prepare('UPDATE banners SET image_url = ?, sort_order = ?, is_active = ? WHERE id = ?');
    $stmt->execute([
        $data['image_url'],
        max(0, (int)$data['sort_order']),
        !empty($data['is_active']) ? 1 : 0,
        $id,
    ]);
}

function deleteBanner(int $id): void
{
    db()->prepare('DELETE FROM banners WHERE id = ?')->execute([$id]);
}

/**
 * Upload ảnh banner. Trả về đường dẫn tương đối lưu vào DB,
 * hoặc null nếu người dùng không chọn file mới.
 */
function uploadBannerImage(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
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

    if (!is_dir(BANNER_UPLOAD_DIR) && !mkdir(BANNER_UPLOAD_DIR, 0777, true)) {
        throw new RuntimeException('Không tạo được thư mục upload.');
    }

    $name = 'b' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $dest = BANNER_UPLOAD_DIR . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Không thể lưu ảnh lên máy chủ.');
    }

    return BANNER_UPLOAD_URL . $name;
}
