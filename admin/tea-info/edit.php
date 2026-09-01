<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../product/model.php';

$admin = current_admin();
$pageTitle = 'Nội dung trang Trà';
$pageSubtitle = 'Sửa thông tin hiển thị trên trang "Thông tin về trà"';
$activeMenu = 'tea-info';

// Mặc định + giá trị đang hiệu lực (settings đè lên mặc định)
$defaults = require __DIR__ . '/../../includes/tea-info-defaults.php';
$values = [];
foreach ($defaults as $key => $val) {
    $values[$key] = getSetting($key, $val);
}

$fields = [
    'tea_s1_title' => ['label' => 'TRÀ · Tiêu đề mục 1', 'type' => 'title'],
    'tea_s1_cards' => ['label' => 'TRÀ · Các loại trà (card)', 'type' => 'cards'],
    'tea_s2_title' => ['label' => 'TRÀ · Tiêu đề mục 2', 'type' => 'title'],
    'tea_s2_items' => ['label' => 'TRÀ · Cách chọn trà ngon', 'type' => 'items'],
    'tea_s3_title' => ['label' => 'TRÀ · Tiêu đề mục 3', 'type' => 'title'],
    'tea_s3_items' => ['label' => 'TRÀ · Nghệ thuật pha trà (các bước)', 'type' => 'items'],
    'tea_s4_title' => ['label' => 'TRÀ · Tiêu đề mục 4', 'type' => 'title'],
    'tea_s4_items' => ['label' => 'TRÀ · Bảo quản trà đúng cách', 'type' => 'items'],

    'gomsu_s1_title' => ['label' => 'GỐM SỨ · Giới thiệu', 'type' => 'title'],
    'gomsu_s1_items' => ['label' => 'GỐM SỨ · Giới thiệu (danh sách)', 'type' => 'items'],
    'gomsu_s2_title' => ['label' => 'GỐM SỨ · Cách sử dụng', 'type' => 'title'],
    'gomsu_s2_items' => ['label' => 'GỐM SỨ · Cách sử dụng (các bước)', 'type' => 'items'],
    'gomsu_s3_title' => ['label' => 'GỐM SỨ · Vệ sinh & bảo quản', 'type' => 'title'],
    'gomsu_s3_items' => ['label' => 'GỐM SỨ · Vệ sinh & bảo quản', 'type' => 'items'],

    'amtusa_s1_title' => ['label' => 'ẤM TỬ SA · Giới thiệu', 'type' => 'title'],
    'amtusa_s1_items' => ['label' => 'ẤM TỬ SA · Giới thiệu (danh sách)', 'type' => 'items'],
    'amtusa_s2_title' => ['label' => 'ẤM TỬ SA · Cách sử dụng & dưỡng ấm', 'type' => 'title'],
    'amtusa_s2_items' => ['label' => 'ẤM TỬ SA · Cách sử dụng & dưỡng ấm (các bước)', 'type' => 'items'],
    'amtusa_s3_title' => ['label' => 'ẤM TỬ SA · Lưu ý', 'type' => 'title'],
    'amtusa_s3_items' => ['label' => 'ẤM TỬ SA · Lưu ý khi dùng', 'type' => 'items'],
];

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($fields as $key => $meta) {
            if (isset($_POST[$key])) {
                setSetting($key, trim((string)$_POST[$key]));
            }
        }
        $flash = ['type' => 'success', 'msg' => 'Đã lưu nội dung trang Thông tin về trà.'];
        // Nạp lại giá trị vừa lưu để hiển thị trên form
        $values = [];
        foreach ($defaults as $key => $val) {
            $values[$key] = getSetting($key, $val);
        }
    } catch (RuntimeException $ex) {
        $flash = ['type' => 'error', 'msg' => $ex->getMessage()];
    }
}

require __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?>
    <div style="padding:14px 18px; border-radius:10px; margin-bottom:20px; <?= $flash['type'] === 'success' ? 'background:#e8f5e9; color:#2e7d32;' : 'background:#fdecea; color:#c62828;' ?>">
        <?= e($flash['msg']) ?>
    </div>
<?php endif; ?>

<div class="toolbar">
    <div class="toolbar-left" style="color:var(--text-light);">
        <i class="fas fa-circle-info"></i>
        Nội dung gồm 3 danh mục: <b>TRÀ · GỐM SỨ · ẤM TỬ SA</b>. Mỗi dòng là một mục riêng. Card trà soạn dạng <b>Tên | Mô tả</b>. Với danh sách, phần chữ trước dấu ":" đầu dòng sẽ tự in đậm.
        <a href="<?= e(url('/thong-tin-tra.php')) ?>" target="_blank" style="margin-left:6px;">Xem trang <i class="fas fa-external-link-alt"></i></a>
    </div>
</div>

<form method="post">
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(420px, 1fr)); gap:20px;">
        <?php foreach ($fields as $key => $meta): ?>
            <div class="table-container" style="padding:16px;">
                <label style="display:block; font-weight:600; margin-bottom:8px;"><?= e($meta['label']) ?></label>
                <?php if ($meta['type'] === 'title'): ?>
                    <input type="text" name="<?= e($key) ?>" value="<?= e($values[$key]) ?>"
                           style="width:100%; padding:10px 12px; border:1px solid #e0d6cc; border-radius:8px; font-size:0.95rem;">
                <?php else: ?>
                    <textarea name="<?= e($key) ?>" rows="<?= $meta['type'] === 'cards' ? 7 : 5 ?>"
                              style="width:100%; padding:10px 12px; border:1px solid #e0d6cc; border-radius:8px; font-size:0.9rem; line-height:1.6; resize:vertical; font-family:inherit;"><?= e($values[$key]) ?></textarea>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div style="margin-top:20px;">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu toàn bộ</button>
        <a href="<?= e(url('/admin/index.php')) ?>" class="btn btn-outline">Về bảng điều khiển</a>
    </div>
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>
