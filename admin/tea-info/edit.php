<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../product/model.php';

$admin = current_admin();
$pageTitle = 'Chỉnh sửa mục THÔNG TIN VỀ TRÀ';
$pageSubtitle = 'Sửa danh sách bài viết và nội dung hướng dẫn pha trà';
$activeMenu = 'tea-info';

/**
 * Mặc định cho từng vùng soạn thảo (dùng khi chưa lưu trong settings).
 */
$defaults = [
    // ===== DANH SÁCH BÀI VIẾT =====
    'art_vc_title'  => 'Về chúng tôi',
    'art_vc_items'  => "TRÀ
Danh sách các loại trà
Từ Đại Danh Nham
Vũ Di Nham Trà",

    'art_gs_title'  => 'Gốm sứ',
    'art_gs_items'  => "Các loại Gốm sứ TQ
Lịch sử Gốm sứ TQ",

    'art_as_title'  => 'Ấm Tử Sa',
    'art_as_items'  => "Các loại đất tử sa
Các dạng ấm tử sa
Cách khai ấm tử sa",

    // ===== PHA NHAM TRÀ (WUYI ROCK TEA) =====
    'brew_title' => 'Pha Nham Trà (Wuyi Rock Tea)',
    'brew_desc'  => 'là một nghệ thuật, và để trà đạt chất lượng tốt nhất, từng chi tiết đều rất quan trọng. Dưới đây là 6 điều bạn cần lưu ý khi pha nham trà và cách chọn trà chất lượng.',

    'brew_1_title' => 'Chọn Trà Nham Tốt',
    'brew_1_desc'  => 'Chi tiết về cách chọn trà: hãy ưu tiên những búp trà được hái từ vùng núi đá (nham) có độ cao, hái những búp non, đều và còn nguyên vẹn. Trà nham thật có hương thơm đá quyến rũ, vị đậm, hậu ngọt và khi pha nước trà trong, màu đẹp. Tránh trà quá vụn hoặc có mùi lạ.',

    'brew_2_title' => 'Sử Dụng Nước Sôi 100°C',
    'brew_2_desc'  => 'Chi tiết về nhiệt độ nước: nham trà cần nước thật sôi (khoảng 100°C) để đánh thức và chiết xuất trọn vẹn hương vị đặc trưng. Nước sôi đúng độ sẽ giúp lá trà nở đều, tránh vị chát gắt hoặc nước trà nhạt, thiếu hậu vị.',

    'brew_3_title' => 'Cách rót nước',
    'brew_3_desc'  => 'Chi tiết về cách rót nước: rót nước theo vòng tròn quanh thành ấm để trà ngấm đều, sau đó đậy nắp ngắn và rót nước thấp, dứt khoát để tránh làm nguội nước. Các lần pha sau có thể kéo dài thời gian ngâm nhẹ để giữ hương vị cân bằng từ lần đầu đến lần cuối.',
];

// Nạp giá trị hiệu lực (settings đè lên mặc định)
$values = [];
foreach ($defaults as $key => $val) {
    $values[$key] = getSetting($key, $val);
}

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    try {
        foreach ($defaults as $key => $val) {
            if (isset($_POST[$key])) {
                setSetting($key, trim((string)$_POST[$key]));
            }
        }
        $flash = ['type' => 'success', 'msg' => 'Đã lưu nội dung trang Thông tin về trà.'];
        foreach ($defaults as $key => $val) {
            $values[$key] = getSetting($key, $val);
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
        margin: 0 0 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid rgba(87, 49, 0, 0.18);
    }
    .article-col textarea {
        width: 100%;
        min-height: 150px;
        padding: 10px 12px;
        border: 1px solid #e5d9c8;
        border-radius: 8px;
        font-size: 0.9rem;
        line-height: 1.7;
        resize: vertical;
        font-family: inherit;
        background: #fff;
        color: #000;
    }
    .article-col .hint {
        display: block;
        font-size: 0.78rem;
        color: #8a7c6b;
        margin-bottom: 8px;
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
        font-family: inherit;
    }

    .tea-editor__actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 6px;
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

        <!-- DANH SÁCH BÀI VIẾT -->
        <div class="tea-editor__card">
            <h2 class="tea-editor__h2">Danh sách bài viết</h2>
            <div class="article-row">
                <div class="article-col">
                    <h3>Về chúng tôi</h3>
                    <span class="hint">Mỗi dòng là một mục trong bài. Dòng đầu (in đậm) là tiêu đề bài.</span>
                    <textarea name="art_vc_items"><?= e($values['art_vc_items']) ?></textarea>
                </div>
                <div class="article-col">
                    <h3>Gốm sứ</h3>
                    <span class="hint">Mỗi dòng là một mục trong bài. Dòng đầu (in đậm) là tiêu đề bài.</span>
                    <textarea name="art_gs_items"><?= e($values['art_gs_items']) ?></textarea>
                </div>
                <div class="article-col">
                    <h3>Ấm Tử Sa</h3>
                    <span class="hint">Mỗi dòng là một mục trong bài. Dòng đầu (in đậm) là tiêu đề bài.</span>
                    <textarea name="art_as_items"><?= e($values['art_as_items']) ?></textarea>
                </div>
            </div>
        </div>

        <!-- PHA NHAM TRÀ -->
        <div class="tea-editor__card">
            <h2 class="tea-editor__h2">Pha Nham Trà (Wuyi Rock Tea)</h2>

            <input type="text" name="brew_title" value="<?= e($values['brew_title']) ?>"
                   style="width:100%; padding:10px 12px; border:1px solid #e0d6cc; border-radius:8px; font-size:1.25rem; font-weight:700; color:#573100; font-family:inherit; margin-bottom:14px;">
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
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu toàn bộ</button>
            <a href="<?= e(url('/admin/index.php')) ?>" class="btn btn-outline">Về bảng điều khiển</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
