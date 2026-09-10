<?php
/**
 * Xác thực phiên đăng nhập admin
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/csrf.php';

if (session_status() === PHP_SESSION_NONE) session_start();

function require_login(): void
{
    if (empty($_SESSION['admin_id'])) {
        redirect(url('/admin/login.php'));
    }
}

function current_admin(): ?array
{
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    static $admin = null;
    if ($admin === null) {
        $stmt = db()->prepare('SELECT * FROM admin_users WHERE id = ?');
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch() ?: null;
    }
    return $admin;
}
