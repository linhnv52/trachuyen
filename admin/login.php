<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!empty($_SESSION['admin_id'])) {
    redirect(url('/admin/index.php'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } else {
        $stmt = db()->prepare('SELECT * FROM admin_users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$admin['id'];
            redirect(url('/admin/index.php'));
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Admin Trà Chuyện</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #5d4037;
            --primary-dark: #3e2723;
            --gold: #b8860b;
            --gold-light: #d4af37;
        }
        body {
            font-family: 'Roboto', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #3e2723 0%, #5d4037 60%, #8d6e63 100%);
            padding: 20px;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.35);
            padding: 40px 36px;
            text-align: center;
        }
        .login-logo h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            color: var(--primary-dark);
        }
        .login-logo h1 span { color: var(--gold); }
        .login-logo .sub {
            font-size: 0.72rem;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: var(--gold);
            margin-top: 4px;
            margin-bottom: 24px;
        }
        .form-group { margin-bottom: 18px; text-align: left; }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--primary-dark);
            margin-bottom: 6px;
        }
        .form-group .input-wrap {
            position: relative;
        }
        .form-group .input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #b49a8a;
        }
        .form-group input {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border: 1px solid #e0d6cc;
            border-radius: 10px;
            font-size: 0.95rem;
            color: var(--primary-dark);
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
            font-family: 'Roboto', sans-serif;
        }
        .form-group input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(184,134,11,0.12);
        }
        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #b8860b, #d4af37);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            font-family: 'Roboto', sans-serif;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(184,134,11,0.35);
        }
        .alert {
            background: #fdecea;
            color: #c62828;
            padding: 11px 14px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 16px;
            text-align: left;
        }
        .back-home {
            display: inline-block;
            margin-top: 18px;
            color: #8d6e63;
            font-size: 0.85rem;
            text-decoration: none;
        }
        .back-home:hover { color: var(--gold); }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo">
            <h1>Trà <span>Chuyện</span></h1>
            <div class="sub">Admin</div>
        </div>

        <?php if ($error): ?>
            <div class="alert"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('/admin/login.php')) ?>">
            <div class="form-group">
                <label for="username">Tên đăng nhập</label>
                <div class="input-wrap">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Nhập tên đăng nhập" required autofocus>
                </div>
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <div class="input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required>
                </div>
            </div>
            <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Đăng nhập</button>
        </form>

        <a href="<?= e(url('/index.php')) ?>" class="back-home"><i class="fas fa-arrow-left"></i> Về trang chủ</a>
    </div>
</body>
</html>