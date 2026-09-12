<?php
/**
 * Cấu hình + hàm dùng chung cho trang Thông tin về trà (site + admin).
 *
 * - $teaGroups: 3 nhóm bài viết (titleKey/itemsKey/defaultTitle/defaultItems)
 * - $brewDefaults: nội dung mặc định Pha Nham Trà (bài chứa từ "nham" tự lấy nội dung này)
 * - teaGroupArticles(): nạp bài viết — JSON [{title,body}], fallback format cũ "Tiêu đề|Nội dung"
 * - teaArticleBody(): nội dung thật của bài (tự điền nội dung Pha Nham Trà cho bài "nham" trống body)
 * - teaIsNham(): bài có phải "nham trà" hay không
 */

/** Cấu hình 3 nhóm bài viết. */
$teaGroups = [
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

/** Mặc định nội dung Pha Nham Trà. */
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

/** Tách dòng bỏ dòng rỗng. */
function teaLines(string $text): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text)), fn ($l) => $l !== ''));
}

/** Nạp danh sách bài viết của 1 nhóm: JSON [{title, body}] nếu có, fallback format cũ "Tiêu đề|Nội dung". */
function teaGroupArticles(string $key, string $raw, array $defaultItems): array
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
        foreach (teaLines($raw) as $line) {
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

/** Nội dung thật của bài viết: nếu bài có từ "nham" (không dấu) và để trống nội dung thì dùng nội dung Pha Nham Trà. */
function teaArticleBody(array $info, string $title, string $body): string
{
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

/** Có phải bài "nham trà" (hiển thị dạng 3 bước đánh số) hay không. */
function teaIsNham(string $title): bool
{
    return preg_match('/nham/i', normalizeText($title)) === 1;
}