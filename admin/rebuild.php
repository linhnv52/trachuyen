<?php
/**
 * Endpoint cập nhật website (rebuild + commit + push).
 * Chỉ hoạt động khi đã đăng nhập admin và là yêu cầu POST.
 */
require_once __DIR__ . '/includes/auth.php';
require_login();

require_once __DIR__ . '/includes/build-trigger.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Phương thức không hợp lệ.']);
    exit;
}

if (empty($_POST['csrf_token']) || !hash_equals(csrf_token(), (string)$_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Mã xác thực không hợp lệ. Vui lòng tải lại trang.']);
    exit;
}

flush();

// 1. Rebuild bản tĩnh
$build = buildRebuild();

// 2. Dọn trang chi tiết cũ
$cleanup = [];
try {
    $cleanup = buildCleanupOrphans();
} catch (Throwable $e) {
    $cleanup = ['error' => $e->getMessage()];
}

// 3. Git commit + push
$git = ['skipped' => true];
if ($build['ok']) {
    $git  = buildGitStageCommitPush();
}

$success = $build['ok'] && ($git['ok'] ?? false);
$messages = [];
if ($build['ok']) {
    $messages[] = 'Rebuild thành công.';
} else {
    $messages[] = 'Rebuild thất bại: ' . ($build['error'] ?: 'lỗi không xác định');
}
if (!empty($cleanup['removed'])) {
    $messages[] = 'Đã dọn ' . count($cleanup['removed']) . ' trang cũ.';
}
if (isset($git['skipped'])) {
    $messages[] = 'Rebuild lỗi nên bỏ qua git push.';
} elseif ($git['ok'] && !$git['changed']) {
    $messages[] = 'Website đã đồng bộ (không có thay đổi).';
} elseif ($git['ok']) {
    $messages[] = 'Đã commit và push lên GitHub.';
} else {
    $messages[] = 'Git: ' . ($git['error'] ?? 'lỗi');
}

$result = [
    'ok'      => $success,
    'message' => implode(' ', $messages),
    'details' => [
        'build_output' => $build['output'],
        'build_error'  => $build['error'] ?? '',
        'removed'      => $cleanup['removed'] ?? [],
        'git'          => $git,
    ],
];

buildWriteStatus($result);

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
