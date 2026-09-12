<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/tea-content.php';
require_once __DIR__ . '/../product/model.php';
require_once __DIR__ . '/../includes/build-trigger.php';

$admin = current_admin();
$pageTitle = 'Chỉnh sửa mục THÔNG TIN VỀ TRÀ';
$pageSubtitle = 'Quản lý 3 nhóm bài viết hiển thị ở trang Thông tin về trà (tiêu đề nhóm + danh sách bài viết + nội dung Pha Nham Trà)';
$activeMenu = 'tea-info';

// Nạp giá trị hiệu lực
$values = [];
foreach ($teaGroups as $code => $g) {
    $values['group_title_' . $code] = getSetting($g['titleKey'], $g['defaultTitle']);
    $values['group_items_' . $code] = teaGroupArticles($g['itemsKey'], (string)getSetting($g['itemsKey'], ''), $g['defaultItems']);
}
foreach ($brewDefaults as $key => $val) {
    $values[$key] = getSetting($key, $val);
}

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    try {
        foreach ($teaGroups as $code => $g) {
            $title = trim((string)($_POST['title'][$code] ?? ''));
            if ($title === '') {
                $title = $g['defaultTitle'];
            }
            $articles = [];
            $posted = $_POST['article'][$code] ?? [];
            if (is_array($posted)) {
                foreach ($posted as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $t = trim((string)($row['title'] ?? ''));
                    $b = trim((string)($row['body'] ?? ''));
                    if ($t === '' && $b === '') {
                        continue;
                    }
                    $articles[] = ['title' => $t, 'body' => $b];
                }
            }
            setSetting($g['titleKey'], $title);
            setSetting($g['itemsKey'], json_encode($articles, JSON_UNESCAPED_UNICODE));
        }
        foreach ($brewDefaults as $key => $val) {
            if (array_key_exists($key, $_POST)) {
                setSetting($key, trim((string)$_POST[$key]));
            }
        }

        // Nạp lại giá trị đã lưu
        foreach ($teaGroups as $code => $g) {
            $values['group_title_' . $code] = getSetting($g['titleKey'], $g['defaultTitle']);
            $values['group_items_' . $code] = teaGroupArticles($g['itemsKey'], (string)getSetting($g['itemsKey'], ''), $g['defaultItems']);
        }
        foreach ($brewDefaults as $key => $val) {
            $values[$key] = getSetting($key, $val);
        }

        // Tự động rebuild + push lên website
        $build = buildRebuild();
        $git = $build['ok'] ? buildGitStageCommitPush() : ['ok' => false];

        $msg = 'Đã lưu nội dung trang Thông tin về trà.';
        if ($build['ok'] && ($git['ok'] ?? false)) {
            $msg .= ($git['changed'] ?? false)
                ? ' Website đã được cập nhật và đẩy lên GitHub.'
                : ' Website đã đồng bộ (không có thay đổi).';
        } else {
            $err = $build['ok'] ? ($git['error'] ?? 'lỗi không xác định') : ($build['error'] ?: 'lỗi build');
            $msg .= ' NHƯNG cập nhật website gặp lỗi: ' . $err;
            $flash = ['type' => 'error', 'msg' => $msg];
        }
        if ($flash === null) {
            $flash = ['type' => 'success', 'msg' => $msg];
        }
    } catch (RuntimeException $ex) {
        $flash = ['type' => 'error', 'msg' => $ex->getMessage()];
    }
}

$extraCssLinks = '<link rel="stylesheet" href="' . e(url('/admin/assets/tea-editor.css')) . '">';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?>
    <div style="padding:14px 18px; border-radius:10px; margin-bottom:20px; <?= $flash['type'] === 'success' ? 'background:#e8f5e9; color:#2e7d32;' : 'background:#fdecea; color:#c62828;' ?>">
        <?= e($flash['msg']) ?>
    </div>
<?php endif; ?>

<div class="tea-editor">
    <h1 class="tea-editor__title">Chỉnh sửa mục THÔNG TIN VỀ TRÀ</h1>

    <form method="post">
        <?= csrf_field() ?>

        <div class="tea-editor__note">
            <i class="fas fa-circle-info"></i>
            Mỗi nhóm bài viết có một tiêu đề nhóm hiển thị ở cột trái trang website. Mỗi bài viết gồm <b>tiêu đề bài</b> (hiện ở cột trái) và <b>nội dung</b> (hiện ở cột phải khi bấm vào). Ngắt đoạn bằng cách chừa một dòng trống.
            Bài có tiêu đề chứa từ <b>nham</b> (vd: "Vũ Di Nham Trà") sẽ tự hiển thị nội dung Pha Nham Trà ở phần bên dưới nếu để trống nội dung.
        </div>

        <!-- 3 NHÓM BÀI VIẾT -->
        <div class="tea-editor__card">
            <h2 class="tea-editor__h2">Danh sách bài viết</h2>
            <div class="article-row">
                <?php foreach ($teaGroups as $code => $g): ?>
                    <div class="article-col" data-group="<?= e($code) ?>">
                        <h3>Tiêu đề nhóm</h3>
                        <input type="text" class="group-title" name="title[<?= e($code) ?>]"
                               value="<?= e($values['group_title_' . $code]) ?>">
                        <span class="group-title-hint">Hiển thị đầu nhóm này ở cột trái.</span>

                        <div class="article-list" id="articleList-<?= e($code) ?>">
                            <?php if (!$values['group_items_' . $code]): ?>
                                <p class="article-list__empty" data-empty="1">Chưa có bài viết nào. Bấm "Thêm bài viết" để tạo.</p>
                            <?php else: foreach ($values['group_items_' . $code] as $i => $item): ?>
                                <div class="article-entry">
                                    <input type="text" name="article[<?= e($code) ?>][<?= (int)$i ?>][title]"
                                           value="<?= e($item['title']) ?>" placeholder="Tiêu đề bài viết">
                                    <textarea name="article[<?= e($code) ?>][<?= (int)$i ?>][body]"
                                              placeholder="Nội dung bài viết (mỗi đoạn cách nhau 1 dòng trống)"><?= e($item['body']) ?></textarea>
                                    <div class="entry-actions">
                                        <button type="button" class="btn-remove"><i class="fas fa-trash"></i> Xóa bài</button>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>

                        <button type="button" class="btn-add" data-group="<?= e($code) ?>">
                            <i class="fas fa-plus"></i> Thêm bài viết
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- PHA NHAM TRÀ -->
        <div class="tea-editor__card">
            <h2 class="tea-editor__h2">Pha Nham Trà (Wuyi Rock Tea)</h2>

            <input type="text" name="brew_title" value="<?= e($values['brew_title']) ?>"
                   style="width:100%; padding:10px 12px; border:1px solid #e0d6cc; border-radius:8px; font-size:1.25rem; font-weight:700; color:#573100; font-family:inherit; box-sizing:border-box; margin-bottom:14px;">
            <textarea name="brew_desc" class="brew-desc-field"><?= e($values['brew_desc']) ?></textarea>

            <div class="brew-steps">
                <div class="brew-step">
                    <div class="number">1</div>
                    <div class="body">
                        <input type="text" name="brew_1_title" value="<?= e($values['brew_1_title']) ?>">
                        <textarea name="brew_1_desc"><?= e($values['brew_1_desc']) ?></textarea>
                    </div>
                </div>
                <div class="brew-step">
                    <div class="number">2</div>
                    <div class="body">
                        <input type="text" name="brew_2_title" value="<?= e($values['brew_2_title']) ?>">
                        <textarea name="brew_2_desc"><?= e($values['brew_2_desc']) ?></textarea>
                    </div>
                </div>
                <div class="brew-step">
                    <div class="number">3</div>
                    <div class="body">
                        <input type="text" name="brew_3_title" value="<?= e($values['brew_3_title']) ?>">
                        <textarea name="brew_3_desc"><?= e($values['brew_3_desc']) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="tea-editor__actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu và cập nhật website</button>
            <a href="<?= e(url('/admin/index.php')) ?>" class="btn btn-outline">Về bảng điều khiển</a>
        </div>
    </form>
</div>

<?php
$extraScript = '<script src="' . e(url('/admin/assets/tea-editor.js')) . '"></script>';
require __DIR__ . '/../includes/footer.php';
?>