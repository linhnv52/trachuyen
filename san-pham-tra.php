<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/admin/product/model.php';

// Trang Sản phẩm trà: chỉ gồm các dòng trà + phụ kiện trà
$_groupSlugs = ['tra-xanh', 'tra-den', 'tra-o-long', 'tra-thao-moc', 'phu-kien-tra', 'tra-xanh-viet'];
$PL_groupIds = [];
foreach (getAllCategories() as $_c) {
    if (in_array($_c['slug'], $_groupSlugs, true)) {
        $PL_groupIds[] = (int)$_c['id'];
    }
}

$PL_baseFile        = 'san-pham-tra.php';
$PL_pageTitle       = 'Sản phẩm trà - Trà Chuyện';
$PL_title           = '🍵 Sản phẩm trà';
$PL_subtitle        = 'Khám phá các dòng trà tuyển chọn từ khắp nơi trên thế giới';
$PL_breadcrumbLabel = 'Sản phẩm trà';
$PL_active          = 'products';
$PL_fixedSlug       = '';
$PL_showTabs        = true;

require __DIR__ . '/includes/product-listing.php';
