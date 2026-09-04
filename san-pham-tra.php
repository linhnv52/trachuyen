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
// Trang Sản phẩm trà: chỉ hiển thị các danh mục thuộc gốc cấp trang "tra".
$PL_rootSlug        = 'tra';
$PL_showTabs        = true;

require __DIR__ . '/includes/product-listing.php';
