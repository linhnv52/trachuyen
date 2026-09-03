<?php
/**
 * Header chung cho các trang admin
 * Cần khai báo trước: $pageTitle, $pageSubtitle, $activeMenu
 */
if (!isset($pageTitle))    $pageTitle = 'Quản trị';
if (!isset($pageSubtitle)) $pageSubtitle = '';
if (!isset($activeMenu))   $activeMenu = '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - Admin Trà Chuyện</title>
    <link rel="icon" type="image/svg+xml" href="<?= e(url('/favicon.svg')) ?>">
    <link rel="shortcut icon" href="<?= e(url('/favicon.ico')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?= e(url('/admin/assets/admin.css')) ?>">
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <h2>Trà <span>Chuyện</span></h2>
        <div class="sub">Quản trị hệ thống</div>
    </div>

    <ul class="sidebar-menu">
        <li class="section-title">Tổng quan</li>
        <li><a href="<?= e(url('/admin/index.php')) ?>" class="<?= $activeMenu === 'dashboard' ? 'active' : '' ?>"><i class="fas fa-th-large"></i> Bảng điều khiển</a></li>

        <li class="section-title">Quản lý</li>
        <li><a href="<?= e(url('/admin/product/list.php')) ?>" class="<?= $activeMenu === 'products' ? 'active' : '' ?>"><i class="fas fa-box"></i> Sản phẩm</a></li>
        <li><a href="<?= e(url('/admin/category/list.php')) ?>" class="<?= $activeMenu === 'categories' ? 'active' : '' ?>"><i class="fas fa-tags"></i> Danh mục</a></li>
        <li><a href="<?= e(url('/admin/category/layout.php')) ?>" class="<?= $activeMenu === 'category-layout' ? 'active' : '' ?>"><i class="fas fa-layer-group"></i> Bố cục danh mục trà</a></li>
        <li><a href="<?= e(url('/admin/banner/index.php')) ?>" class="<?= $activeMenu === 'banners' ? 'active' : '' ?>"><i class="fas fa-images"></i> Banner trang chủ</a></li>
        <li><a href="<?= e(url('/admin/tea-info/edit.php')) ?>" class="<?= $activeMenu === 'tea-info' ? 'active' : '' ?>"><i class="fas fa-book-open"></i> Nội dung trang Thông tin</a></li>
        <li><a href="#" onclick="alert('Đơn hàng sẽ được phát triển trong giai đoạn sau');"><i class="fas fa-shopping-cart"></i> Đơn hàng</a></li>
        <li><a href="#" onclick="alert('Khách hàng sẽ được phát triển trong giai đoạn sau');"><i class="fas fa-users"></i> Khách hàng</a></li>

        <li class="section-title">Cài đặt</li>
        <li><a href="<?= e(url('/admin/logout.php')) ?>"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
    </ul>
</aside>

<main class="main-content">

    <header class="admin-header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="page-title">
                <h1><?= e($pageTitle) ?></h1>
                <p><?= e($pageSubtitle) ?></p>
            </div>
        </div>
        <div class="header-actions">
            <div class="admin-avatar" title="<?= e($admin['full_name'] ?? 'Admin') ?>">
                <i class="fas fa-user-shield"></i>
            </div>
        </div>
    </header>
