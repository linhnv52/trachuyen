<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/admin/product/model.php';

$PL_baseFile        = 'san-pham-tra.php';
$PL_pageTitle       = 'Sản phẩm trà - Trà Chuyện';
$PL_title           = '🍵 Sản phẩm trà';
$PL_subtitle        = 'Khám phá các dòng trà tuyển chọn từ khắp nơi trên thế giới';
$PL_breadcrumbLabel = 'Sản phẩm trà';
$PL_active          = 'products';
$PL_fixedSlug       = '';
// Hiển thị đúng toàn bộ danh mục đang có trong Admin > Quản lý danh mục.
// Việc chọn danh mục sẽ lọc sản phẩm tương ứng.
$PL_rootSlug        = '';
$PL_showTabs        = true;

require __DIR__ . '/includes/product-listing.php';
