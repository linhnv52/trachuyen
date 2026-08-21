<?php
/**
 * API chi tiết sản phẩm cho popup (quick view)
 * GET ?id=<id>
 * Chỉ trả về sản phẩm đang hoạt động (is_active = 1).
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../admin/product/model.php';

$id = (int)($_GET['id'] ?? 0);
$product = $id ? getProductById($id) : null;

if (!$product) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'not found'], JSON_UNESCAPED_UNICODE);
    exit;
}

$badgeLabel = '';
$badgeClass = '';
if (!empty($product['badge'])) {
    $badgeLabel = match ($product['badge']) { 'hot' => 'Hot', 'sale' => 'Sale', 'new' => 'Mới', default => '' };
    $badgeClass = $product['badge'] === 'sale' ? 'sale' : ($product['badge'] === 'new' ? 'new' : '');
}
$discount = ($product['old_price'] && $product['old_price'] > $product['price'])
    ? (int)round((1 - $product['price'] / $product['old_price']) * 100)
    : 0;

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'id'                 => (int)$product['id'],
    'code'               => $product['code'],
    'name'               => $product['name'],
    'category_name'      => $product['category_name'],
    'category_slug'      => $product['category_slug'],
    'price'              => $product['price'],
    'old_price'          => $product['old_price'],
    'discount'           => $discount,
    'rating_avg'         => $product['rating_avg'],
    'review_count'       => (int)$product['review_count'],
    'stock_quantity'     => (int)$product['stock_quantity'],
    'views'              => (int)$product['views'],
    'image_url'          => $product['image_url'],
    'badge'              => $product['badge'],
    'badge_label'        => $badgeLabel,
    'badge_class'        => $badgeClass,
    'description'        => $product['description'],
    'short_description'  => $product['short_description'],
], JSON_UNESCAPED_UNICODE);