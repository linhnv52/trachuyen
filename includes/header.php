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
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <?= $extraCssLinks ?>
    <style><?= $extraCss ?></style>
</head>
<body>

<header>
    <div class="container">
        <div class="logo">
            <h1>Trà <span>Chuyện</span></h1>
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
                <li><a href="product.php" class="<?= $active === 'products' ? 'active' : '' ?>">SẢN PHẨM TRÀ</a></li>
                <li><a href="product.php?category=hop-qua-tang">HỘP QUÀ TẶNG</a></li>
                <li><a href="product.php?category=bo-tra-cu">KHẢI VÀ CHÉN</a></li>
                <li><a href="product.php?category=am-tu-sa">ẤM TỬ SA</a></li>
                <li><a href="thong-tin-tra.php" class="<?= $active === 'about' ? 'active' : '' ?>">THÔNG TIN VỀ TRÀ</a></li>
            </ul>
            <div class="nav-icons">
                <a href="#" title="Tài khoản"><i class="fas fa-user"></i></a>
                <a href="#" title="Giỏ hàng" class="cart-link"><i class="fas fa-shopping-bag"></i></a>
            </div>
        </nav>
    </div>
</header>