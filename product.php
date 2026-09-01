<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/admin/product/model.php';

// Trang "Tất cả sản phẩm": mọi danh mục, đủ dải tab
$PL_baseFile        = 'product.php';
$PL_pageTitle       = 'Sản phẩm trà - Trà Chuyện';
$PL_title           = '🍵 Sản phẩm trà';
$PL_subtitle        = 'Khám phá bộ sưu tập trà và trà cụ tuyển chọn từ khắp nơi trên thế giới';
$PL_breadcrumbLabel = 'Sản phẩm trà';
$PL_active          = 'products';
$PL_fixedSlug       = '';
$PL_groupIds        = null;
$PL_showTabs        = true;

require __DIR__ . '/includes/product-listing.php';
