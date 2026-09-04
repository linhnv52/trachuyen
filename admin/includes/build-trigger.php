<?php
/**
 * Cập nhật website từ bảng điều khiển admin.
 *
 * Pipeline: rebuild bản tĩnh -> dọn file chi tiết cũ -> git commit docs/ -> git push.
 * Chạy qua file admin/rebuild.php (chỉ khi đăng nhập).
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/csrf.php';

const BUILD_LOG_DIR = __DIR__ . '/../logs';
const BUILD_STATUS_FILE = BUILD_LOG_DIR . '/build_status.json';

/**
 * Tìm đường dẫn PHP binary dùng được.
 */
function buildFindPhp(): string
{
    $php = defined('PHP_BINARY') ? PHP_BINARY : '';
    if ($php && is_file($php)) {
        return $php;
    }
    $candidates = [
        'C:\\laragon\\bin\\php\\php-8.1.10-Win32-vs16-x64\\php.exe',
        'C:\\laragon\\bin\\php\\php-8.0.30-Win32-vs16-x64\\php.exe',
        'C:\\laragon\\bin\\php\\php-7.4.33-Win32-vs16-x64\\php.exe',
    ];
    foreach ($candidates as $c) {
        if (is_file($c)) {
            return $c;
        }
    }
    return 'php';
}

function buildRunCommand(string $cmd): array
{
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    // Đảm bảo PATH đầy đủ được truyền vào tiến trình con (bắt buộc với git)
    $path = getenv('PATH') ? getenv('PATH') : getenv('Path');
    $env = array_merge($_ENV, [
        'PATH'                => $path,
        'SystemRoot'          => getenv('SystemRoot'),
        'GIT_TERMINAL_PROMPT' => '0',
        'GCM_INTERACTIVE'     => 'Never',
    ]);
    $proc = proc_open($cmd, $descriptors, $pipes, null, $env);
    if (!is_resource($proc)) {
        return ['ok' => false, 'output' => '', 'error' => 'Không thể khởi chạy tiến trình.'];
    }
    fclose($pipes[0]);
    $out = stream_get_contents($pipes[1]);
    $err = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);
    return [
        'ok'     => $code === 0,
        'output' => trim((string)$out),
        'error'  => trim((string)$err),
    ];
}

/**
 * Đường dẫn đầy đủ tới git executable.
 */
function buildFindGit(): string
{
    $candidates = [
        'C:\\Program Files\\Git\\cmd\\git.exe',
        'C:\\Program Files (x86)\\Git\\cmd\\git.exe',
        'C:\\Program Files\\Git\\bin\\git.exe',
        'C:\\laragon\\bin\\git\\bin\\git.exe',
    ];
    foreach ($candidates as $c) {
        if (is_file($c)) {
            return $c;
        }
    }
    // Thử dùng `where git` để tìm
    $res = buildRunCommand('where git');
    if ($res['ok'] && $res['output']) {
        $first = strtok($res['output'], "\r\n");
        if (is_string($first) && is_file($first)) {
            return $first;
        }
    }
    return 'git';
}

/**
 * Bọc một đường dẫn/đối số bằng dấu nháy kép cho Windows (cmd/proc_open).
 * `escapeshellarg()` dùng nháy đơn — không tương thích Windows, gây lỗi proc_open.
 */
function buildQuote(string $value): string
{
    return '"' . str_replace('"', '\\"', $value) . '"';
}

/**
 * Chạy tools/build-static.php.
 */
function buildRebuild(): array
{
    $root = dirname(__DIR__, 2);
    $php  = buildFindPhp();
    $cmd  = buildQuote($php) . ' ' . buildQuote($root . '/tools/build-static.php') . ' 2>&1';
    return buildRunCommand($cmd);
}

/**
 * Danh sách tập tin tĩnh cố định (trang nội dung) không thuộc chi tiết sản phẩm.
 */
function buildContentFiles(): array
{
    return ['index.html', 'product.html', 'san-pham-tra.html', 'khai-va-chen.html',
        'am-tu-sa.html', 'hop-qua-tang.html', 'thong-tin-tra.html'];
}

/**
 * Dọn các trang chi tiết cũ trong docs/ mà sản phẩm tương ứng không còn tồn tại
 * (sản phẩm đã xóa hoặc đổi slug). Không đụng trang nội dung cố định.
 */
function buildCleanupOrphans(): array
{
    $root = dirname(__DIR__, 2);
    $out  = $root . '/docs';
    $content = buildContentFiles();

    // Tập hợp các file chi tiết hiện tại (slug -> file) giống logic build-static
    try {
        $products = db()->query('SELECT id, slug FROM products WHERE is_active = 1')->fetchAll();
    } catch (Throwable $e) {
        return ['removed' => []];
    }
    $slugUsed = [];
    $detailFileFor = function (array $p) use (&$slugUsed): string {
        $base = trim((string)($p['slug'] ?? ''));
        if ($base === '') {
            $base = 'san-pham-' . (int)$p['id'];
        }
        $base = preg_replace('/[^a-z0-9\-]+/', '-', strtolower($base));
        $base = trim($base, '-') ?: 'san-pham-' . (int)$p['id'];
        if (isset($slugUsed[$base])) {
            $base .= '-' . (int)$p['id'];
        }
        $slugUsed[$base] = true;
        return $base . '.html';
    };
    $keep = [];
    foreach ($products as $p) {
        $keep[$detailFileFor($p)] = true;
    }
    foreach ($content as $f) {
        $keep[$f] = true;
    }

    $removed = [];
    if (!is_dir($out)) {
        return ['removed' => []];
    }
    foreach (scandir($out) as $f) {
        if ($f === '.' || $f === '..') {
            continue;
        }
        if (substr($f, -5) !== '.html') {
            continue;
        }
        if (isset($keep[$f])) {
            continue;
        }
        $full = $out . '/' . $f;
        if (is_file($full) && @unlink($full)) {
            $removed[] = $f;
        }
    }
    return ['removed' => $removed];
}

/**
 * Git: stage doc/, commit, push. Trả về kết quả chi tiết.
 */
function buildGitStageCommitPush(): array
{
    $root = dirname(__DIR__, 2);
    $git  = buildFindGit();
    $prefix = buildQuote($git) . ' -C ' . buildQuote($root);

    // Kiểm tra repo
    $status = buildRunCommand($prefix . ' rev-parse --is-inside-work-tree');
    if (!$status['ok']) {
        return ['ok' => false, 'error' => 'Thư mục không phải git repo: ' . $status['error']];
    }

    // Chỉ stage docs/ — giữ nguyên mọi thay đổi khác để tránh đụng chạm
    $add = buildRunCommand($prefix . ' add docs/');
    if (!$add['ok']) {
        return ['ok' => false, 'error' => 'git add thất bại: ' . $add['error']];
    }

    // Nếu không có gì thay đổi thì không cần commit/push
    $diff = buildRunCommand($prefix . ' diff --cached --quiet');
    if ($diff['ok']) {
        return ['ok' => true, 'changed' => false, 'output' => 'Không có thay đổi để cập nhật.'];
    }

    $commit = buildRunCommand($prefix . ' commit -m "Rebuild website tu admin"');
    if (!$commit['ok']) {
        return ['ok' => false, 'error' => 'git commit thất bại: ' . $commit['error']];
    }

    $push = buildRunCommand($prefix . ' push origin main 2>&1');
    if (!$push['ok']) {
        return ['ok' => false, 'error' => 'git push thất bại: ' . $push['error'] . ' | ' . $push['output'],
            'committed' => true];
    }

    return ['ok' => true, 'changed' => true, 'output' => $push['output']];
}

/**
 * Ghi trạng thái build cuối cùng ra file JSON để hiển thị lần chạy sau.
 */
function buildWriteStatus(array $status): void
{
    if (!is_dir(BUILD_LOG_DIR)) {
        @mkdir(BUILD_LOG_DIR, 0777, true);
    }
    $status['time'] = date('Y-m-d H:i:s');
    @file_put_contents(BUILD_STATUS_FILE, json_encode($status, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

/**
 * Đọc trạng thái build gần nhất (nếu có).
 */
function buildReadStatus(): ?array
{
    if (!is_file(BUILD_STATUS_FILE)) {
        return null;
    }
    $data = @file_get_contents(BUILD_STATUS_FILE);
    $arr  = $data ? json_decode($data, true) : null;
    return is_array($arr) ? $arr : null;
}
