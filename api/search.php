<?php
/**
 * API gợi ý tìm kiếm (smart search)
 * GET ?q=<từ khóa>[&admin=1]
 * - admin=1: trả về cả sản phẩm tạm dừng (dùng cho trang quản trị)
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../admin/product/model.php';

$q = trim($_GET['q'] ?? '');
$q = mb_substr($q, 0, 60);
$includeInactive = ($_GET['admin'] ?? '') === '1';

$results = searchProducts($q, 8, $includeInactive);
foreach ($results as &$result) {
    $result['image_url'] = productImage($result['image_url'] ?? null);
}
unset($result);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($results, JSON_UNESCAPED_UNICODE);
