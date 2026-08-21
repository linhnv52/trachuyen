<?php
/**
 * Cấu hình kết nối cơ sở dữ liệu (PDO)
 */
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'trachuyen_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('BASE_URL', '');          // Để trống nếu chạy ở thư mục gốc domain
define('UPLOAD_DIR', __DIR__ . '/../img/products/');
define('UPLOAD_URL', 'img/products/');
define('CATEGORY_UPLOAD_DIR', __DIR__ . '/../img/categories/');
define('CATEGORY_UPLOAD_URL', 'img/categories/');
define('BANNER_UPLOAD_DIR', __DIR__ . '/../img/banners/');
define('BANNER_UPLOAD_URL', 'img/banners/');

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('Không thể kết nối cơ sở dữ liệu: ' . htmlspecialchars($e->getMessage()));
        }
    }
    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Chuẩn hóa văn bản để tìm kiếm không dấu tiếng Việt
 */
function normalizeText(?string $s): string
{
    $s = mb_strtolower((string)$s, 'UTF-8');
    $map = [
        'à'=>'a','á'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a','ă'=>'a','ắ'=>'a','ằ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a',
        'â'=>'a','ấ'=>'a','ầ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a',
        'đ'=>'d','è'=>'e','é'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e','ê'=>'e','ế'=>'e','ề'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e',
        'ì'=>'i','í'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i',
        'ò'=>'o','ó'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o','ô'=>'o','ố'=>'o','ồ'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o',
        'ơ'=>'o','ớ'=>'o','ờ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o',
        'ù'=>'u','ú'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u','ư'=>'u','ứ'=>'u','ừ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u',
        'ỳ'=>'y','ý'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y',
    ];
    return strtr($s, $map);
}

function formatPrice($price): string
{
    return number_format((float)$price, 0, ',', '.');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function url(string $path): string
{
    return BASE_URL . $path;
}

/**
 * Trả về ảnh danh mục; nếu rỗng hoặc file không tồn tại thì dùng ảnh mặc định.
 */
function categoryImage(?string $imageUrl): string
{
    $placeholder = url('/img/placeholder.svg');
    if (!$imageUrl) {
        return $placeholder;
    }
    // Ảnh nội bộ (relative URL) thì kiểm tra file thực tế
    if (str_starts_with($imageUrl, '/') || str_starts_with($imageUrl, 'img/')) {
        $path = __DIR__ . '/..' . ($imageUrl[0] === '/' ? $imageUrl : '/' . $imageUrl);
        if (!file_exists($path)) {
            return $placeholder;
        }
    }
    return $imageUrl;
}

/* ---------- Settings key-value ---------- */

function getSettings(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT skey, svalue FROM settings')->fetchAll() as $row) {
            $cache[$row['skey']] = (string)$row['svalue'];
        }
    }
    return $cache;
}

function getSetting(string $key, ?string $default = null): ?string
{
    $all = getSettings();
    return (isset($all[$key]) && trim($all[$key]) !== '') ? $all[$key] : $default;
}

function setSetting(string $key, string $value): void
{
    $stmt = db()->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?)
                           ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)');
    $stmt->execute([$key, $value]);
}