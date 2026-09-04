<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/admin/product/model.php';

$pageTitle = 'Thông tin về trà - Trà Chuyện';
$active = 'about';

// Nội dung: settings (admin sửa) đè lên mặc định
$teaNewDefaults = [
    'art_vc_items' => "TRÀ|
Danh sách các loại trà|
Từ Đại Danh Nham|
Vũ Di Nham Trà|",
    'art_gs_items' => "Các loại Gốm sứ TQ|
Lịch sử Gốm sứ TQ|",
    'art_as_items' => "Các loại đất tử sa|
Các dạng ấm tử sa|
Cách khai ấm tử sa|",
    'brew_title'   => 'Pha Nham Trà (Wuyi Rock Tea)',
    'brew_desc'    => 'là một nghệ thuật, và để trà đạt chất lượng tốt nhất, từng chi tiết đều rất quan trọng. Dưới đây là 6 điều bạn cần lưu ý khi pha nham trà và cách chọn trà chất lượng.',
    'brew_1_title' => 'Chọn Trà Nham Tốt',
    'brew_1_desc'  => 'Chi tiết về cách chọn trà: hãy ưu tiên những búp trà được hái từ vùng núi đá (nham) có độ cao, hái những búp non, đều và còn nguyên vẹn. Trà nham thật có hương thơm đá quyến rũ, vị đậm, hậu ngọt và khi pha nước trà trong, màu đẹp. Tránh trà quá vụn hoặc có mùi lạ.',
    'brew_2_title' => 'Sử Dụng Nước Sôi 100°C',
    'brew_2_desc'  => 'Chi tiết về nhiệt độ nước: nham trà cần nước thật sôi (khoảng 100°C) để đánh thức và chiết xuất trọn vẹn hương vị đặc trưng. Nước sôi đúng độ sẽ giúp lá trà nở đều, tránh vị chát gắt hoặc nước trà nhạt, thiếu hậu vị.',
    'brew_3_title' => 'Cách rót nước',
    'brew_3_desc'  => 'Chi tiết về cách rót nước: rót nước theo vòng tròn quanh thành ấm để trà ngấm đều, sau đó đậy nắp ngắn và rót nước thấp, dứt khoát để tránh làm nguội nước. Các lần pha sau có thể kéo dài thời gian ngâm nhẹ để giữ hương vị cân bằng từ lần đầu đến lần cuối.',
];

$info = [];
foreach ($teaNewDefaults as $key => $val) {
    $info[$key] = getSetting($key, $val);
}

/** Tách dòng bỏ dòng rỗng */
function teaLines(string $text): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text)), fn ($l) => $l !== ''));
}

/** Tách tiêu đề + nội dung của 1 dòng bài viết (ngăn cách bằng | đầu tiên) */
function teaArticle(string $line): array
{
    [$title, $body] = array_pad(explode('|', $line, 2), 2, '');
    return [trim($title), trim($body)];
}

/** Nội dung thật của 1 dòng bài viết: nếu bài có từ "nham" (không dấu) thì dùng nội dung Pha Nham Trà */
function teaArticleBody(array $info, string $line): string
{
    [$title, $body] = teaArticle($line);
    if ($body !== '' || !preg_match('/nham/i', normalizeText($title))) {
        return $body;
    }
    return implode("\n\n", array_merge(
        [$info['brew_desc']],
        array_map(
            fn ($n) => trim($info["brew_{$n}_title"]) . ':' . "\n" . trim($info["brew_{$n}_desc"]),
            [1, 2, 3]
        )
    ));
}

/** Có phải bài "nham trà" (hiển thị dạng 3 bước đánh số) hay không */
function teaIsNham(string $line): bool
{
    [$title] = teaArticle($line);
    return preg_match('/nham/i', normalizeText($title)) === 1;
}

/** Các nhóm bài viết hiển thị ở cột trái */
$groups = [
    ['title' => 'Về chúng tôi', 'key' => 'art_vc_items'],
    ['title' => 'Gốm sứ',       'key' => 'art_gs_items'],
    ['title' => 'Ấm Tử Sa',     'key' => 'art_as_items'],
];

$articles = [];
foreach ($groups as $g) {
    foreach (teaLines($info[$g['key']]) as $line) {
        [$title] = teaArticle($line);
        if ($title === '') {
            continue;
        }
        $articles[] = [
            'group' => $g['title'],
            'title' => $title,
            'body'  => teaArticleBody($info, $line),
            'nham'  => teaIsNham($line),
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
$extraScript = <<<'HTML'
<script>
    (function () {
        var navBtns = Array.prototype.slice.call(document.querySelectorAll('.tea-info-nav .tea-nav-btn'));
        var panel = document.querySelector('.tea-info-panel');

        function esc(s) {
            return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function renderArticle(btn) {
            var title = btn.textContent.trim();
            var nham = btn.getAttribute('data-nham') === '1';
            var body = btn.getAttribute('data-body') || '';

            navBtns.forEach(function (b) { b.classList.toggle('active', b === btn); });

            var html = '<article class="tea-art">';
            if (nham) {
                html += '<h3 class="tea-art__title">' + esc(title) + '</h3>';
                var lines = body.split(/\n{2,}/).filter(function (l) { return l.trim() !== ''; });
                if (lines.length) {
                    // Dòng đầu là mô tả, các dòng sau là bước "Tiêu đề:Nội dung"
                    html += '<p class="tea-art__desc">' + esc(lines.shift()) + '</p>';
                    html += '<div class="tea-art__steps">';
                    lines.forEach(function (l, i) {
                        var pos = l.indexOf(':');
                        var stepTitle, stepBody;
                        if (pos > -1) {
                            stepTitle = l.slice(0, pos);
                            stepBody = l.slice(pos + 1);
                        } else {
                            stepTitle = 'Bước ' + (i + 1);
                            stepBody = l;
                        }
                        html += '<div class="tea-art__step">'
                            + '<span class="tea-art__num">' + (i + 1) + '</span>'
                            + '<div><h4>' + esc(stepTitle) + '</h4>'
                            + '<p>' + esc(stepBody.trim()) + '</p></div></div>';
                    });
                    html += '</div>';
                }
            } else {
                html += '<h3 class="tea-art__title">' + esc(title) + '</h3>';
                var paras = body.split(/\n{2,}/).filter(function (l) { return l.trim() !== ''; });
                if (paras.length) {
                    html += paras.map(function (p) { return '<p class="tea-art__desc">' + esc(p) + '</p>'; }).join('');
                }
            }
            html += '</article>';
            panel.innerHTML = html;
        }

        navBtns.forEach(function (b) {
            b.addEventListener('click', function () { renderArticle(b); });
        });

        // Mặc định hiện bài đầu tiên
        if (navBtns.length) {
            renderArticle(navBtns[0]);
        }
    })();
</script>
HTML;
require __DIR__ . '/includes/footer.php';
?>
