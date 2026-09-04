<?php
/**
 * CSRF protection cho các form admin.
 * Gọi csrf_token() để sinh/trả token; nhúng vào form; verify csrf bằng verify_csrf().
 */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    return isset($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Dùng ở đầu các handler POST admin. Nếu CSRF sai thì chặn.
 */
function require_csrf(): void
{
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Mã xác thực không hợp lệ. Vui lòng quay lại và tải lại trang.');
    }
}
