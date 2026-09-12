<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/tea-content.php';

$pageTitle = 'Thông tin về trà - Trà Chuyện';
$active = 'about';

// Nội dung: settings (admin sửa) đè lên mặc định
$info = [];
foreach ($teaGroups as $g) {
    $info[$g['titleKey']] = getSetting($g['titleKey'], $g['defaultTitle']);
    $info[$g['itemsKey']] = (string)getSetting($g['itemsKey'], '');
}
foreach ($brewDefaults as $key => $val) {
    $info[$key] = getSetting($key, $val);
}

// Các nhóm bài viết hiển thị ở cột trái (tiêu đề đọc từ settings)
$articles = [];
foreach ($teaGroups as $g) {
    $groupTitle = $info[$g['titleKey']];
    foreach (teaGroupArticles($g['itemsKey'], $info[$g['itemsKey']], $g['defaultItems']) as $art) {
        $title = $art['title'];
        if ($title === '') {
            continue;
        }
        $articles[] = [
            'group' => $groupTitle,
            'title' => $title,
            'body'  => teaArticleBody($info, $title, $art['body']),
            'nham'  => teaIsNham($title),
        ];
    }
}

require __DIR__ . '/includes/header.php';
?>

<div class="container body-container tea-info-page">
    <h2 class="section-title">THÔNG TIN VỀ TRÀ</h2>

    <!-- ====== 2 CỘT: DANH SÁCH BÀI VIẾT | NỘI DUNG ====== -->
    <div class="tea-info-split">

        <!-- Cột trái (30%): danh sách bài viết -->
        <aside class="tea-info-nav">
            <h3 class="tea-info-nav__title">Danh sách bài viết</h3>
            <?php $currentGroup = null; foreach ($articles as $art): ?>
                <?php if ($art['group'] !== $currentGroup): $currentGroup = $art['group']; ?>
                    <h4 class="tea-info-nav__group"><?= e($currentGroup) ?></h4>
                <?php endif; ?>
                <button type="button" class="tea-nav-btn"
                        data-body="<?= e($art['body']) ?>"
                        <?= $art['nham'] ? ' data-nham="1"' : '' ?>><?= e($art['title']) ?></button>
            <?php endforeach; ?>
        </aside>

        <!-- Cột phải (70%): nội dung bài được chọn -->
        <div class="tea-info-panel"></div>

    </div>
</div>

<?php
$extraScript = '<script src="js/tea-info.js"></script>';
require __DIR__ . '/includes/footer.php';
?>
