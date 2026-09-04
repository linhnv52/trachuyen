<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/admin/product/model.php';

// Trang Ấm tử sa: khoá cứng một danh mục
$PL_baseFile        = 'am-tu-sa.php';
$PL_pageTitle       = 'Ấm tử sa - Trà Chuyện';
$PL_title           = '🫖 Ấm tử sa';
$PL_subtitle        = 'Ấm đất tử sa chính gốc, chưng cất tinh hoa nghệ nhân Trung Hoa';
$PL_breadcrumbLabel = 'Ấm tử sa';
$PL_active          = 'teapot';
$PL_fixedSlug       = '';
$PL_groupIds        = null;
$PL_rootSlug        = 'amtusa';
$PL_showTabs        = true;

require __DIR__ . '/includes/product-listing.php';
