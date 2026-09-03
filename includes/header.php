<?php
/**
 * Header dùng chung cho trang công khai
 * Khai báo trước: $pageTitle, $active, $extraCss, $extraCssLinks
 */
if (!isset($pageTitle)) $pageTitle = 'Trà Chuyện';
if (!isset($active))    $active = '';
$extraCss = $extraCss ?? '';
$extraCssLinks = $extraCssLinks ?? '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="shortcut icon" href="favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <?= $extraCssLinks ?>
    <style><?= $extraCss ?></style>
</head>
<body>

<header>
    <div class="container">
        <button type="button" class="nav-toggle" id="navToggle" aria-label="Mở menu" aria-expanded="false" aria-controls="mobileMenu">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>

        <div class="logo">
            <a href="index.php" aria-label="Tr� Chuy?n">
                <img src="<?= e(getSetting('site_logo', 'https://drive.google.com/uc?export=view&id=1m-0-hXczkfAv8wzQGyb55N3DJlhQTW3Z')) ?>" alt="Tr� Chuy?n">
            </a>
        </div>

        <div class="search-bar-wrapper">
            <div class="smart-search">
                <form class="search-bar" action="product.php" method="get" autocomplete="off">
                    <input type="text" name="q" id="searchInput" placeholder="Tìm kiếm sản phẩm..." value="<?= e($_GET['q'] ?? '') ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
                <div class="smart-search-results" id="searchResults"></div>
            </div>
        </div>

        <nav>
            <ul>
                <li><a href="index.php" class="<?= $active === 'home' ? 'active' : '' ?>">TRANG CHỦ</a></li>
                <li><a href="san-pham-tra.php" class="<?= $active === 'products' ? 'active' : '' ?>">SẢN PHẨM TRÀ</a></li>
                <li><a href="hop-qua-tang.php" class="<?= $active === 'gift' ? 'active' : '' ?>">HỘP QUÀ TẶNG</a></li>
                <li><a href="khai-va-chen.php" class="<?= $active === 'teaset' ? 'active' : '' ?>">GỐM SỨ</a></li>
                <li><a href="am-tu-sa.php" class="<?= $active === 'teapot' ? 'active' : '' ?>">ẤM TỬ SA</a></li>
                <li><a href="thong-tin-tra.php" class="<?= $active === 'about' ? 'active' : '' ?>">THÔNG TIN VỀ TRÀ</a></li>
            </ul>
            <div class="nav-icons">
                <a href="#" title="Tài khoản"><i class="fas fa-user"></i></a>
                <a href="#" title="Giỏ hàng" class="cart-link"><i class="fas fa-shopping-bag"></i></a>
            </div>
        </nav>
    </div>

        <!-- ====== MENU DI ĐỘNG (hamburger, mở từ trái) ====== -->
        <div class="mobile-menu-backdrop" id="mobileMenuBackdrop" aria-hidden="true"></div>
        <div class="mobile-menu" id="mobileMenu" role="dialog" aria-modal="true" aria-label="Menu điều hướng">
            <button type="button" class="mobile-menu-close" id="mobileMenuClose" aria-label="Đóng menu">
                <i class="fas fa-times"></i>
            </button>
            <ul class="mobile-menu-list">
                <li><a href="index.php" class="<?= $active === 'home' ? 'active' : '' ?>">Trang chủ</a></li>
                <li><a href="san-pham-tra.php" class="<?= $active === 'products' ? 'active' : '' ?>">Sản phẩm trà</a></li>
                <li><a href="hop-qua-tang.php" class="<?= $active === 'gift' ? 'active' : '' ?>">Hộp quà tặng</a></li>
                <li><a href="khai-va-chen.php" class="<?= $active === 'teaset' ? 'active' : '' ?>">Gốm sứ</a></li>
                <li><a href="am-tu-sa.php" class="<?= $active === 'teapot' ? 'active' : '' ?>">Ấm tử sa</a></li>
                <li><a href="thong-tin-tra.php" class="<?= $active === 'about' ? 'active' : '' ?>">Thông tin về trà</a></li>
            </ul>
            <div class="mobile-menu-icons">
                <a href="#" title="Tài khoản"><i class="fas fa-user"></i><span>Tài khoản</span></a>
                <a href="#" title="Giỏ hàng" class="cart-link"><i class="fas fa-shopping-bag"></i><span>Giỏ hàng</span></a>
            </div>
        </div>
</header>
