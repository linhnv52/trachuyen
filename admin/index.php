<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_once __DIR__ . '/product/model.php';

$admin = current_admin();
$logoError = '';
$logoSuccess = '';
$autoRebuild = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_logo') {
    require_csrf();
    try {
        $saveMode = $_POST['save_mode'] ?? '';
        $file = $_FILES['logo'] ?? [];
        $logoUrl = trim($_POST['logo_url'] ?? '');

        if ($saveMode === 'file') {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                throw new RuntimeException('Vui lòng chọn một tệp logo.');
            }
            if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Không thể tải tệp logo lên.');
            }
            if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
                throw new RuntimeException('Logo quá lớn. Kích thước tối đa là 3MB.');
            }
            if (!is_uploaded_file($file['tmp_name'] ?? '')) {
                throw new RuntimeException('Tệp logo không hợp lệ.');
            }
            $mime = mime_content_type($file['tmp_name']);
            if (@getimagesize($file['tmp_name']) === false) {
                throw new RuntimeException('Tệp logo không phải ảnh hợp lệ.');
            }
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            if (!isset($allowed[$mime])) {
                throw new RuntimeException('Chỉ chấp nhận JPG, PNG hoặc WEBP.');
            }
            $logoDir = __DIR__ . '/../img/logo';
            if (!is_dir($logoDir) && !mkdir($logoDir, 0777, true)) {
                throw new RuntimeException('Không tạo được thư mục lưu logo.');
            }
            $fileName = 'site-logo-' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $allowed[$mime];
            if (!move_uploaded_file($file['tmp_name'], $logoDir . '/' . $fileName)) {
                throw new RuntimeException('Không thể lưu logo lên máy chủ.');
            }
            setSetting('site_logo', 'img/logo/' . $fileName);
        } elseif ($saveMode === 'url') {
            if ($logoUrl === '') {
                throw new RuntimeException('Vui lòng dán link logo.');
            }
            if (!filter_var($logoUrl, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $logoUrl)) {
                throw new RuntimeException('Link logo không hợp lệ. Hãy dùng link bắt đầu bằng http:// hoặc https://.');
            }
            if (preg_match('~drive\.google\.com/file/d/([^/]+)~i', $logoUrl, $matches)) {
                $logoUrl = 'https://drive.google.com/uc?export=view&id=' . $matches[1];
            }
            setSetting('site_logo', $logoUrl);
        } else {
            throw new RuntimeException('Hãy chọn đúng nút Lưu tệp hoặc Lưu link.');
        }
        $logoSuccess = 'Logo đã được cập nhật.';
    } catch (Throwable $e) {
        $logoError = $e->getMessage();
    }
}

$siteLogo = getSetting('site_logo', 'https://drive.google.com/uc?export=view&id=1m-0-hXczkfAv8wzQGyb55N3DJlhQTW3Z');
$videoError = '';
$videoSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_video') {
    require_csrf();
    try {
        $videoUrl = trim($_POST['video_url'] ?? '');
        if ($videoUrl === '' || !filter_var($videoUrl, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $videoUrl)) {
            throw new RuntimeException('Link video không hợp lệ.');
        }
        $videoUrl = normalizeVideoUrl($videoUrl);
        setSetting('homepage_video_url', $videoUrl);
        $videoSuccess = 'Video trang chủ đã được cập nhật.';
    } catch (Throwable $e) {
        $videoError = $e->getMessage();
    }
}

$homepageVideoUrl = getSetting('homepage_video_url', '');
$galleryError = '';
$gallerySuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_gallery') {
    require_csrf();
    try {
        $slot = (int)($_POST['slot'] ?? 0);
        if ($slot < 1 || $slot > 4) {
            throw new RuntimeException('Vị trí ảnh không hợp lệ.');
        }
        $file = $_FILES['gallery'] ?? [];
        $galleryUrl = trim($_POST['gallery_url'] ?? '');
        $current = (string)getSetting('gallery_img_' . $slot, '');

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            // Upload file mới
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Không thể tải ảnh lên.');
            }
            if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
                throw new RuntimeException('Ảnh quá lớn. Kích thước tối đa là 5MB.');
            }
            if (!is_uploaded_file($file['tmp_name'] ?? '')) {
                throw new RuntimeException('Tệp ảnh không hợp lệ.');
            }
            $mime = mime_content_type($file['tmp_name']);
            if (@getimagesize($file['tmp_name']) === false) {
                throw new RuntimeException('Tệp ảnh không phải ảnh hợp lệ.');
            }
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
            if (!isset($allowed[$mime])) {
                throw new RuntimeException('Chỉ chấp nhận JPG, PNG, WEBP hoặc GIF.');
            }
            $galleryDir = __DIR__ . '/../img/gallery';
            if (!is_dir($galleryDir) && !mkdir($galleryDir, 0777, true)) {
                throw new RuntimeException('Không tạo được thư mục lưu ảnh.');
            }
            $fileName = 'gallery-' . $slot . '-' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $allowed[$mime];
            if (!move_uploaded_file($file['tmp_name'], $galleryDir . '/' . $fileName)) {
                throw new RuntimeException('Không thể lưu ảnh lên máy chủ.');
            }
            setSetting('gallery_img_' . $slot, 'img/gallery/' . $fileName);
        } elseif ($galleryUrl !== '') {
            // Dán link ảnh
            if (!filter_var($galleryUrl, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $galleryUrl)) {
                throw new RuntimeException('Link ảnh không hợp lệ. Hãy dùng link bắt đầu bằng http:// hoặc https://.');
            }
            if (preg_match('~drive\.google\.com/file/d/([^/]+)~i', $galleryUrl, $matches)) {
                $galleryUrl = 'https://drive.google.com/uc?export=view&id=' . $matches[1];
            }
            setSetting('gallery_img_' . $slot, $galleryUrl);
        } elseif ($current === '') {
            throw new RuntimeException('Chọn tệp ảnh hoặc dán link cho vị trí #' . $slot . '.');
        }

        // Tự động rebuild + push lên website (chạy nền qua AJAX ở footer)
        $autoRebuild = true;
        $gallerySuccess = 'Đã lưu ảnh trưng bày #' . $slot . '. Website đang được cập nhật trong nền...';
    } catch (Throwable $e) {
        $galleryError = $e->getMessage();
    }
}

$galleryImages = [];
for ($gi = 1; $gi <= 4; $gi++) {
    $galleryImages[$gi] = getSetting('gallery_img_' . $gi, 'img/placeholder.svg');
}

$pageTitle = 'Bảng điều khiển';
$pageSubtitle = 'Tổng quan hoạt động của cửa hàng';
$activeMenu = 'dashboard';

$totalProducts  = (int)db()->query('SELECT COUNT(*) FROM products')->fetchColumn();
$activeProducts = (int)db()->query('SELECT COUNT(*) FROM products WHERE is_active = 1')->fetchColumn();
$totalCategories = (int)db()->query('SELECT COUNT(*) FROM categories WHERE is_active = 1')->fetchColumn();
$bestSellers    = (int)db()->query('SELECT COUNT(*) FROM products WHERE is_best_seller = 1')->fetchColumn();

$recent = db()->query('SELECT p.*, c.name AS category_name
                       FROM products p
                       LEFT JOIN categories c ON c.id = p.category_id
                       ORDER BY p.created_at DESC
                       LIMIT 6')->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<style>
    .stats-grid { grid-template-columns: repeat(4, 1fr); }
    @media (max-width: 768px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    .stat-card .icon.purple { background: #f3e5f5; color: #7b1fa2; }
    .logo-settings { display:grid; grid-template-columns:minmax(180px,260px) 1fr; gap:24px; align-items:center; margin:0 0 30px; padding:24px; background:#fff; border:1px solid var(--gray); border-radius:var(--radius); box-shadow:var(--shadow); }
    .logo-preview { min-height:130px; display:flex; align-items:center; justify-content:center; padding:20px; background:#faf9f7; border:1px dashed var(--gray); }
    .logo-preview img { max-width:100%; max-height:96px; object-fit:contain; }
    .logo-copy h2 { margin:0 0 6px; font-family:'Playfair Display',serif; color:var(--primary-dark); }
    .logo-copy p { margin:0 0 16px; color:var(--text-light); font-size:.9rem; }
    .logo-picker { display:flex; flex-wrap:wrap; align-items:center; gap:12px; }
    .logo-picker input[type=file], .logo-picker input[type=url] { max-width:100%; padding:10px; border:1px solid var(--gray); background:#fff; }
    .logo-picker input[type=url] { min-width:260px; flex:1; }
    .logo-message { margin:0 0 16px; padding:12px 14px; border-radius:8px; font-size:.9rem; }
    .logo-message.success { background:#eaf7ef; color:#176b3a; }
    .logo-message.error { background:#fff0f0; color:#a32222; }
    .video-settings { margin:0 0 30px; padding:24px; background:#fff; border:1px solid var(--gray); border-radius:var(--radius); box-shadow:var(--shadow); }
    .video-settings h2 { margin:0 0 6px; font-family:'Playfair Display',serif; color:var(--primary-dark); }
    .video-settings p { margin:0 0 16px; color:var(--text-light); font-size:.9rem; }
    .video-url-form { display:flex; flex-wrap:wrap; gap:12px; }
    .video-url-form input { flex:1; min-width:260px; padding:10px; border:1px solid var(--gray); background:#fff; }
    .gallery-settings { margin:0 0 30px; padding:24px; background:#fff; border:1px solid var(--gray); border-radius:var(--radius); box-shadow:var(--shadow); }
    .gallery-settings h2 { margin:0 0 6px; font-family:'Playfair Display',serif; color:var(--primary-dark); }
    .gallery-settings p { margin:0 0 16px; color:var(--text-light); font-size:.9rem; }
    .gallery-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:18px; }
    .gallery-item { background:#faf9f7; border:1px solid var(--gray); border-radius:var(--radius); padding:14px; }
    .gallery-item .thumb { min-height:110px; display:flex; align-items:center; justify-content:center; background:#fff; border:1px dashed var(--gray); border-radius:8px; margin-bottom:10px; overflow:hidden; }
    .gallery-item .thumb img { max-width:100%; max-height:110px; object-fit:cover; }
    .gallery-item h3 { margin:0 0 2px; font-size:.95rem; color:var(--primary-dark); }
    .gallery-item .curl { font-size:.72rem; color:var(--text-light); word-break:break-all; margin-bottom:8px; min-height:2.4em; }
    .gallery-item form { display:flex; flex-direction:column; gap:8px; }
    .gallery-item input[type=file] { font-size:.8rem; padding:6px; border:1px solid var(--gray); background:#fff; border-radius:6px; }
    .gallery-item input[type=url] { width:100%; padding:8px 10px; border:1px solid var(--gray); background:#fff; border-radius:6px; box-sizing:border-box; font-size:.85rem; }
    @media (max-width: 1100px) { .gallery-grid { grid-template-columns:repeat(2,1fr); } }
    @media (max-width: 560px) { .gallery-grid { grid-template-columns:1fr; } }
    @media (max-width: 700px) { .logo-settings { grid-template-columns:1fr; } }
</style>

<section class="logo-settings" aria-labelledby="logo-settings-title">
    <div class="logo-preview">
        <img src="<?= e($siteLogo) ?>" alt="Logo hiện tại" id="adminLogoPreview">
    </div>
    <div class="logo-copy">
        <h2 id="logo-settings-title">Logo website</h2>
        <p>Chọn tệp hoặc dán link ảnh logo. Nên dùng ảnh nền trong suốt, tối đa 3MB.</p>
        <?php if ($logoSuccess): ?><div class="logo-message success" role="status"><?= e($logoSuccess) ?></div><?php endif; ?>
        <?php if ($logoError): ?><div class="logo-message error" role="alert"><?= e($logoError) ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data" class="logo-picker logo-file-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_logo">
            <input type="file" name="logo" id="adminLogoInput" accept="image/jpeg,image/png,image/webp">
            <button type="submit" name="save_mode" value="file" class="btn btn-primary"><i class="fas fa-upload"></i> Lưu tệp</button>
        </form>
        <form method="post" class="logo-picker logo-url-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_logo">
            <input type="url" name="logo_url" id="adminLogoUrl" placeholder="https://.../logo.png" autocomplete="url" value="<?= e(str_starts_with($siteLogo, 'http') ? $siteLogo : '') ?>">
            <button type="submit" name="save_mode" value="url" class="btn btn-secondary"><i class="fas fa-link"></i> Lưu link</button>
        </form>
    </div>
</section>

<script>
    document.getElementById('adminLogoInput')?.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file || !file.type.startsWith('image/')) return;
        const preview = document.getElementById('adminLogoPreview');
        const reader = new FileReader();
        reader.onload = function (event) { preview.src = event.target.result; };
        reader.readAsDataURL(file);
    });
    document.getElementById('adminLogoUrl')?.addEventListener('input', function () {
        if (document.getElementById('adminLogoInput')?.files?.length) return;
        if (this.value.trim()) document.getElementById('adminLogoPreview').src = this.value.trim();
    });
    for (let gi = 1; gi <= 4; gi++) {
        const input = document.getElementById('galleryInput' + gi);
        const url = document.getElementById('galleryUrl' + gi);
        const preview = document.getElementById('galleryPreview' + gi);
        input?.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file || !file.type.startsWith('image/')) return;
            const reader = new FileReader();
            reader.onload = function (event) { preview.src = event.target.result; };
            reader.readAsDataURL(file);
        });
        url?.addEventListener('input', function () {
            if (input?.files?.length) return;
            if (this.value.trim()) preview.src = this.value.trim();
        });
    }
</script>

<section class="video-settings" aria-labelledby="video-settings-title">
    <h2 id="video-settings-title">Video trang chủ</h2>
    <p>Nên dùng link YouTube (có thể đặt Không công khai). Google Drive thường chặn nhúng video từ website khác; nếu dùng Drive, file phải được chia sẻ công khai và vẫn có thể không phát được do chính sách của Google.</p>
    <?php if ($videoSuccess): ?><div class="logo-message success" role="status"><?= e($videoSuccess) ?></div><?php endif; ?>
    <?php if ($videoError): ?><div class="logo-message error" role="alert"><?= e($videoError) ?></div><?php endif; ?>
    <form method="post" class="video-url-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_video">
        <input type="url" name="video_url" placeholder="https://www.youtube.com/watch?v=..." value="<?= e($homepageVideoUrl) ?>" required>
        <button type="submit" class="btn btn-primary"><i class="fas fa-video"></i> Lưu video</button>
    </form>
</section>

<section class="gallery-settings" aria-labelledby="gallery-settings-title">
    <h2 id="gallery-settings-title">Ảnh trưng bày trang chủ</h2>
    <p>4 ảnh đều nhau hiện ở cột trái mục trưng bày trang chủ (grid 2x2). Mỗi ảnh có thể chọn tệp mới hoặc dán link. Tối đa 5MB/ảnh (JPG, PNG, WEBP, GIF).</p>
    <?php if ($gallerySuccess): ?><div class="logo-message success" role="status"><?= e($gallerySuccess) ?></div><?php endif; ?>
    <?php if ($galleryError): ?><div class="logo-message error" role="alert"><?= e($galleryError) ?></div><?php endif; ?>
    <div class="gallery-grid">
        <?php for ($gi = 1; $gi <= 4; $gi++): ?>
            <div class="gallery-item">
                <div class="thumb">
                    <img src="<?= e($galleryImages[$gi]) ?>" alt="Ảnh trưng bày <?= $gi ?>" id="galleryPreview<?= $gi ?>">
                </div>
                <h3>Ảnh #<?= $gi ?></h3>
                <div class="curl"><?= e($galleryImages[$gi]) ?></div>
                <form method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_gallery">
                    <input type="hidden" name="slot" value="<?= $gi ?>">
                    <input type="file" name="gallery" id="galleryInput<?= $gi ?>" accept="image/jpeg,image/png,image/webp,image/gif">
                    <input type="url" name="gallery_url" id="galleryUrl<?= $gi ?>" value="<?= e(str_starts_with($galleryImages[$gi], 'http') ? $galleryImages[$gi] : '') ?>" placeholder="https://.../anh.jpg">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Lưu ảnh #<?= $gi ?></button>
                </form>
            </div>
        <?php endfor; ?>
    </div>
</section>

<section class="stats-grid">
    <div class="stat-card">
        <div class="icon blue"><i class="fas fa-box"></i></div>
        <div class="info"><h3><?= $totalProducts ?></h3><p>Tổng sản phẩm</p></div>
    </div>
    <div class="stat-card">
        <div class="icon green"><i class="fas fa-check-circle"></i></div>
        <div class="info"><h3><?= $activeProducts ?></h3><p>Đang hoạt động</p></div>
    </div>
    <div class="stat-card">
        <div class="icon gold"><i class="fas fa-tags"></i></div>
        <div class="info"><h3><?= $totalCategories ?></h3><p>Danh mục</p></div>
    </div>
    <div class="stat-card">
        <div class="icon purple"><i class="fas fa-fire"></i></div>
        <div class="info"><h3><?= $bestSellers ?></h3><p>Bán chạy</p></div>
    </div>
</section>

<div class="toolbar" style="justify-content:space-between;">
    <div class="toolbar-left">
        <h2 style="font-family:'Playfair Display',serif; color:var(--primary-dark);">Sản phẩm mới nhất</h2>
    </div>
    <div class="toolbar-right">
        <a href="<?= e(url('/admin/product/add.php')) ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm sản phẩm mới</a>
    </div>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>Danh mục</th>
                <th>Giá</th>
                <th>Tồn kho</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$recent): ?>
                <tr><td colspan="6" style="text-align:center; padding:40px; color:var(--text-light);">Chưa có sản phẩm nào.</td></tr>
            <?php else: foreach ($recent as $p): ?>
                <tr>
                    <td>
                        <div class="product-cell">
                            <img src="<?= e(productImage($p['image_url'])) ?>" alt="">
                            <div>
                                <div class="name"><?= e($p['name']) ?></div>
                                <div class="sku"><?= e($p['code']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= e($p['category_name'] ?: 'Chưa phân loại') ?></td>
                    <td><?= formatPrice($p['price']) ?>đ</td>
                    <td><?= (int)$p['stock_quantity'] ?></td>
                    <td>
                        <span class="status-badge <?= $p['is_active'] ? 'active' : 'inactive' ?>">
                            <?= $p['is_active'] ? 'Hoạt động' : 'Tạm dừng' ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <a class="btn-icon edit" href="<?= e(url('/admin/product/update.php?id=' . $p['id'])) ?>" title="Sửa"><i class="fas fa-edit"></i></a>
                            <a class="btn-icon view" href="<?= e(url('/productdetal.php?id=' . $p['id'])) ?>" target="_blank" title="Xem"><i class="fas fa-eye"></i></a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    <div class="table-footer">
        <div class="info"><a href="<?= e(url('/admin/product/list.php')) ?>" style="color:var(--gold); text-decoration:none;">Xem tất cả sản phẩm →</a></div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
