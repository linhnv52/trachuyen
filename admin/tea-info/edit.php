<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../product/model.php';
require_once __DIR__ . '/../includes/build-trigger.php';

$admin = current_admin();
$pageTitle = 'Chỉnh sửa mục THÔNG TIN VỀ TRÀ';
$pageSubtitle = 'Quản lý 3 nhóm bài viết hiển thị ở trang Thông tin về trà (tiêu đề nhóm + danh sách bài viết + nội dung Pha Nham Trà)';
$activeMenu = 'tea-info';

/**
 * Cấu hình 3 nhóm bài viết.
 */
$groups = [
    'vc' => [
        'titleKey'     => 'art_vc_title',
        'itemsKey'     => 'art_vc_items',
        'defaultTitle' => 'Về chúng tôi',
        'defaultItems' => [
            ['title' => 'TRÀ',               'body' => ''],
            ['title' => 'Danh sách các loại trà', 'body' => ''],
            ['title' => 'Từ Đại Danh Nham',      'body' => ''],
            ['title' => 'Vũ Di Nham Trà',        'body' => ''],
        ],
    ],
    'gs' => [
        'titleKey'     => 'art_gs_title',
        'itemsKey'     => 'art_gs_items',
        'defaultTitle' => 'Gốm sứ',
        'defaultItems' => [
            ['title' => 'Các loại Gốm sứ TQ', 'body' => ''],
            ['title' => 'Lịch sử Gốm sứ TQ', 'body' => ''],
        ],
    ],
    'as' => [
        'titleKey'     => 'art_as_title',
        'itemsKey'     => 'art_as_items',
        'defaultTitle' => 'Ấm Tử Sa',
        'defaultItems' => [
            ['title' => 'Các loại đất tử sa', 'body' => ''],
            ['title' => 'Các dạng ấm tử sa',  'body' => ''],
            ['title' => 'Cách khai ấm tử sa', 'body' => ''],
        ],
    ],
];

/**
 * Mặc định nội dung Pha Nham Trà (bài có từ "nham" sẽ tự lấy nội dung này).
 */
$brewDefaults = [
    'brew_title'   => 'Pha Nham Trà (Wuyi Rock Tea)',
    'brew_desc'    => 'là một nghệ thuật, và để trà đạt chất lượng tốt nhất, từng chi tiết đều rất quan trọng. Dưới đây là 6 điều bạn cần lưu ý khi pha nham trà và cách chọn trà chất lượng.',
    'brew_1_title' => 'Chọn Trà Nham Tốt',
    'brew_1_desc'  => 'Chi tiết về cách chọn trà: hãy ưu tiên những búp trà được hái từ vùng núi đá (nham) có độ cao, hái những búp non, đều và còn nguyên vẹn. Trà nham thật có hương thơm đá quyến rũ, vị đậm, hậu ngọt và khi pha nước trà trong, màu đẹp. Tránh trà quá vụn hoặc có mùi lạ.',
    'brew_2_title' => 'Sử Dụng Nước Sôi 100°C',
    'brew_2_desc'  => 'Chi tiết về nhiệt độ nước: nham trà cần nước thật sôi (khoảng 100°C) để đánh thức và chiết xuất trọn vẹn hương vị đặc trưng. Nước sôi đúng độ sẽ giúp lá trà nở đều, tránh vị chát gắt hoặc nước trà nhạt, thiếu hậu vị.',
    'brew_3_title' => 'Cách rót nước',
    'brew_3_desc'  => 'Chi tiết về cách rót nước: rót nước theo vòng tròn quanh thành ấm để trà ngấm đều, sau đó đậy nắp ngắn và rót nước thấp, dứt khoát để tránh làm nguội nước. Các lần pha sau có thể kéo dài thời gian ngâm nhẹ để giữ hương vị cân bằng từ lần đầu đến lần cuối.',
];

/**
 * Nạp danh sách bài viết hiệu lực: ưu tiên JSON [{title, body}], fallback format cũ (mỗi dòng "Tiêu đề|Nội dung").
 */
function teaInfoLoadItems(string $raw, array $defaultItems): array
{
    $raw = trim($raw);
    if ($raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $out = [];
            foreach ($decoded as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $title = trim((string)($row['title'] ?? ''));
                if ($title === '') {
                    continue;
                }
                $out[] = ['title' => $title, 'body' => trim((string)($row['body'] ?? ''))];
            }
            return $out;
        }
        $out = [];
        foreach (array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw)), fn ($l) => $l !== '') as $line) {
            [$title, $body] = array_pad(explode('|', $line, 2), 2, '');
            $title = trim($title);
            if ($title === '') {
                continue;
            }
            $out[] = ['title' => $title, 'body' => trim($body)];
        }
        if ($out) {
            return $out;
        }
    }
    return $defaultItems;
}

// Nạp giá trị hiệu lực
$values = [];
foreach ($groups as $code => $g) {
    $values['group_title_' . $code] = getSetting($g['titleKey'], $g['defaultTitle']);
    $values['group_items_' . $code] = teaInfoLoadItems((string)getSetting($g['itemsKey'], ''), $g['defaultItems']);
}
foreach ($brewDefaults as $key => $val) {
    $values[$key] = getSetting($key, $val);
}

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    try {
        foreach ($groups as $code => $g) {
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
        foreach ($groups as $code => $g) {
            $values['group_title_' . $code] = getSetting($g['titleKey'], $g['defaultTitle']);
            $values['group_items_' . $code] = teaInfoLoadItems((string)getSetting($g['itemsKey'], ''), $g['defaultItems']);
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

require __DIR__ . '/../includes/header.php';
?>

<style>
    .tea-editor {
        max-width: 1100px;
    }
    .tea-editor__title {
        text-align: center;
        font-size: 1.9rem;
        font-weight: 700;
        color: #000;
        margin: 8px 0 34px;
        letter-spacing: 0.02em;
    }
    .tea-editor__card {
        background: #fff;
        border: 1px solid var(--gray, #e0d6cc);
        border-radius: 14px;
        box-shadow: var(--shadow, 0 6px 18px rgba(0,0,0,0.06));
        padding: 30px 32px;
        margin-bottom: 34px;
    }
    .tea-editor__h2 {
        color: #573100;
        font-size: 1.25rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        border-bottom: 2px solid #573100;
        padding-bottom: 10px;
        margin-bottom: 22px;
    }

    .article-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
        align-items: start;
    }
    .article-col {
        background: #fff6ea;
        border-radius: 12px;
        padding: 22px;
    }
    .article-col h3 {
        color: #573100;
        font-size: 1.05rem;
        font-weight: 700;
        margin: 0 0 6px;
    }
    .article-col .group-title-hint {
        display: block;
        font-size: 0.75rem;
        color: #8a7c6b;
        margin: 0 0 12px;
    }
    .article-col input.group-title {
        width: 100%;
        padding: 9px 12px;
        border: 1px solid #e5d9c8;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 700;
        color: #573100;
        font-family: inherit;
        box-sizing: border-box;
    }
    .article-col .hint {
        display: block;
        font-size: 0.78rem;
        color: #8a7c6b;
        margin-bottom: 8px;
    }

    .article-list {
        margin-top: 14px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .article-list__empty {
        font-size: 0.85rem;
        color: #a89a89;
        padding: 10px;
        text-align: center;
        border: 1px dashed #e0d6cc;
        border-radius: 8px;
        margin: 0;
    }
    .article-entry {
        background: #fff;
        border: 1px solid #e5d9c8;
        border-radius: 10px;
        padding: 12px;
    }
    .article-entry input {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #e0d6cc;
        border-radius: 8px;
        font-size: 0.92rem;
        font-weight: 600;
        color: #000;
        margin-bottom: 8px;
        box-sizing: border-box;
        font-family: inherit;
    }
    .article-entry textarea {
        width: 100%;
        min-height: 120px;
        padding: 8px 10px;
        border: 1px solid #e0d6cc;
        border-radius: 8px;
        font-size: 0.88rem;
        line-height: 1.6;
        resize: vertical;
        box-sizing: border-box;
        font-family: inherit;
        color: #000;
    }
    .article-entry .entry-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 8px;
    }
    .btn-remove {
        background: #fdecea;
        color: #c62828;
        border: 1px solid #f3c1bc;
        border-radius: 8px;
        padding: 6px 12px;
        font-size: 0.82rem;
        cursor: pointer;
        font-family: inherit;
    }
    .btn-remove:hover {
        background: #fbd7d4;
    }
    .btn-add {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 12px;
        background: #573100;
        color: #fff;
        border: 0;
        border-radius: 8px;
        padding: 9px 14px;
        font-size: 0.88rem;
        font-weight: 600;
        cursor: pointer;
        font-family: inherit;
    }
    .btn-add:hover {
        background: #6d3f05;
    }

    .brew-desc-field {
        width: 100%;
        min-height: 70px;
        padding: 10px 12px;
        border: 1px solid #e0d6cc;
        border-radius: 8px;
        font-size: 0.92rem;
        line-height: 1.7;
        resize: vertical;
        font-family: inherit;
        margin-bottom: 26px;
        box-sizing: border-box;
        color: #000;
    }
    .brew-steps {
        display: flex;
        flex-direction: column;
        gap: 22px;
    }
    .brew-step {
        display: flex;
        align-items: flex-start;
        gap: 16px;
    }
    .brew-step .number {
        flex-shrink: 0;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #573100;
        color: #fff;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .brew-step .body {
        flex: 1;
    }
    .brew-step .body input {
        width: 100%;
        padding: 9px 12px;
        border: 1px solid #e0d6cc;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 700;
        color: #573100;
        margin-bottom: 10px;
        box-sizing: border-box;
        font-family: inherit;
    }
    .brew-step .body textarea {
        width: 100%;
        min-height: 90px;
        padding: 10px 12px;
        border: 1px solid #e0d6cc;
        border-radius: 8px;
        font-size: 0.9rem;
        line-height: 1.7;
        resize: vertical;
        box-sizing: border-box;
        font-family: inherit;
        color: #000;
    }

    .tea-editor__actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 6px;
    }
    .tea-editor__note {
        background: #fff8e1;
        border: 1px solid #f0e1b8;
        border-radius: 10px;
        padding: 12px 16px;
        font-size: 0.85rem;
        color: #6d5a1f;
        margin-bottom: 22px;
    }

    @media (max-width: 820px) {
        .article-row { grid-template-columns: 1fr; }
        .tea-editor__title { font-size: 1.5rem; }
        .tea-editor__card { padding: 22px 18px; }
    }
</style>

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
                <?php foreach ($groups as $code => $g): ?>
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

<script>
    (function () {
        function esc(s) {
            return String(s == null ? '' : s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function toggleEmpty(list) {
            var empty = list.querySelector('.article-list__empty');
            if (empty) {
                empty.style.display = list.querySelector('.article-entry') ? 'none' : '';
            }
        }

        function buildEntry(group, index, title, body) {
            var div = document.createElement('div');
            div.className = 'article-entry';
            div.innerHTML =
                '<input type="text" name="article[' + esc(group) + '][' + index + '][title]" value="' + esc(title) +
                '" placeholder="Tiêu đề bài viết">' +
                '<textarea name="article[' + esc(group) + '][' + index + '][body]" placeholder="Nội dung bài viết (mỗi đoạn cách nhau 1 dòng trống)">' +
                esc(body) + '</textarea>' +
                '<div class="entry-actions"><button type="button" class="btn-remove"><i class="fas fa-trash"></i> Xóa bài</button></div>';
            div.querySelector('.btn-remove').addEventListener('click', function () {
                div.remove();
                toggleEmpty(list);
            });
            return div;
        }

        var lists = document.querySelectorAll('.article-list');
        var list = null;
        lists.forEach(function (l) {
            if (l.querySelector('.article-entry')) {
                list = l;
            }
        });

        document.querySelectorAll('.btn-add').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var group = btn.getAttribute('data-group');
                var list = document.getElementById('articleList-' + group);
                var count = list.querySelectorAll('.article-entry').length;
                var empty = list.querySelector('.article-list__empty');
                if (empty) empty.remove();
                list.appendChild(buildEntry(group, count, '', ''));
                toggleEmpty(list);
            });
        });

        document.querySelectorAll('.article-entry .btn-remove').forEach(function (b) {
            b.addEventListener('click', function () {
                var entry = b.closest('.article-entry');
                var list = entry ? entry.closest('.article-list') : null;
                if (entry) entry.remove();
                if (list) toggleEmpty(list);
            });
        });
    })();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>