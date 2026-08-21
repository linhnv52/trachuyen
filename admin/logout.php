<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$_SESSION = [];
session_destroy();
redirect(url('/admin/login.php'));