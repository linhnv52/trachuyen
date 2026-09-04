<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/admin/product/model.php';

// Trang Hộp quà tặng: khoá cứng một danh mục
$PL_baseFile        = 'hop-qua-tang.php';
$PL_pageTitle       = 'Hộp quà tặng - Trà Chuyện';
$PL_title           = '🎁 Hộp quà tặng';
$PL_subtitle        = 'Những hộp quà trà sang trọng, tinh tế dành cho người thân yêu';
$PL_breadcrumbLabel = 'Hộp quà tặng';
$PL_active          = 'gift';
$PL_fixedSlug       = '';
$PL_groupIds        = null;
$PL_rootSlug        = 'hop-qua-tang';
$PL_showTabs        = true;

require __DIR__ . '/includes/product-listing.php';
