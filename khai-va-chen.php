<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/admin/product/model.php';

// Trang Gốm sứ: hiển thị + lọc 8 danh mục (bỏ "Trà xanh việt") dạng card như SẢN PHẨM TRÀ
$_skipCat = 'tra-xanh-viet';
$PL_groupIds = array_map('intval', array_column(
    array_filter(getAllCategories(), fn ($c) => $c['slug'] !== $_skipCat),
    'id'
));

$PL_baseFile        = 'khai-va-chen.php';
$PL_pageTitle       = 'Gốm sứ - Trà Chuyện';
$PL_title           = '🏺 Gốm sứ';
$PL_subtitle        = 'Bộ trà cụ truyền thống cùng các loại khải, chén ủ trà tinh xảo';
$PL_breadcrumbLabel = 'Gốm sứ';
$PL_active          = 'teaset';
$PL_fixedSlug       = '';
$PL_showTabs        = true;

require __DIR__ . '/includes/product-listing.php';
