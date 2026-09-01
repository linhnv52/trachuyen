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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <?= $extraCssLinks ?>
    <style><?= $extraCss ?></style>
</head>
<body>

<header>
    <div class="container">
        <div class="logo">
            <a href="index.php" aria-label="Trà Chuyện">
                <img src="https://lh3.googleusercontent.com/d/1m-0-hXczkfAv8wzQGyb55N3DJlhQTW3Z=w800" alt="Trà Chuyện">
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
</header>