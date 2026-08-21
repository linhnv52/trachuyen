<?php
/**
 * Form sản phẩm dùng chung cho add.php / update.php
 * Cần khai báo trước: $product (array|null), $categories, $errors (array), $old (array)
 */
$v = $old; // các giá trị cần điền vào form
$errors = $errors ?? [];
function fieldError(string $key): void
{
    global $errors;
    if (isset($errors[$key])): ?>
        <div style="color:#c62828; font-size:0.78rem; margin-top:4px;"><i class="fas fa-exclamation-circle"></i> <?= e($errors[$key]) ?></div>
    <?php endif;
}
?>
<style>
    .form-card { background:#fff; border-radius:var(--radius); box-shadow:var(--shadow); padding:28px; }
    .form-grid { display:grid; grid-template-columns: 1fr 1fr; gap:18px; }
    .form-grid .full { grid-column: 1 / -1; }
    @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
    .form-group { margin-bottom: 4px; }
    .form-group label { display:block; font-size:0.85rem; font-weight:600; color:var(--primary-dark); margin-bottom:6px; }
    .form-group label .required { color:#c62828; }
    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group input[type="password"],
    .form-group select,
    .form-group textarea {
        width:100%; padding:11px 14px; border:1px solid var(--gray); border-radius:10px;
        font-size:0.9rem; color:var(--text); outline:none; font-family:'Roboto',sans-serif;
        transition:border-color .3s, box-shadow .3s; background:#fff;
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
        border-color:var(--gold); box-shadow:0 0 0 3px rgba(184,134,11,.12);
    }
    .form-group textarea { resize:vertical; min-height:120px; line-height:1.6; }
    .form-check { display:flex; align-items:center; gap:10px; font-size:0.9rem; color:var(--text); cursor:pointer; }
    .form-check input { width:18px; height:18px; accent-color:var(--gold); }
    .image-preview { width:160px; height:160px; border-radius:12px; overflow:hidden; border:1px dashed var(--gray);
        display:flex; align-items:center; justify-content:center; background:var(--bg); margin-bottom:10px; }
    .image-preview img { width:100%; height:100%; object-fit:cover; }
    .form-actions { display:flex; gap:12px; margin-top:24px; }
</style>

<?php if ($errors): ?>
    <div style="background:#fdecea; color:#c62828; padding:14px 18px; border-radius:10px; margin-bottom:20px;">
        <i class="fas fa-exclamation-circle"></i> Vui lòng kiểm tra lại thông tin nhập.
    </div>
<?php endif; ?>

<div class="form-card">
    <form method="post" enctype="multipart/form-data" onsubmit="prepareForm()">
        <div class="form-grid">

            <div class="form-group">
                <label>Mã sản phẩm <span class="required">*</span></label>
                <input type="text" name="code" value="<?= e($v['code'] ?? '') ?>" placeholder="VD: TC-001 (để trống tự sinh)">
                <?php fieldError('code'); ?>
            </div>

            <div class="form-group">
                <label>Tên sản phẩm <span class="required">*</span></label>
                <input type="text" name="name" value="<?= e($v['name'] ?? '') ?>" required placeholder="VD: Trà Xanh Thái Nguyên">
                <?php fieldError('name'); ?>
            </div>

            <div class="form-group">
                <label>Danh mục <span class="required">*</span></label>
                <select name="category_id" required>
                    <option value="">-- Chọn danh mục --</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (string)($v['category_id'] ?? '') === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php fieldError('category_id'); ?>
            </div>

            <div class="form-group">
                <label>Giá bán (đ) <span class="required">*</span></label>
                <input type="number" name="price" value="<?= e($v['price'] ?? '') ?>" required min="0" step="1000" placeholder="VD: 250000">
                <?php fieldError('price'); ?>
            </div>

            <div class="form-group">
                <label>Giá niêm yết (đ) — để trống nếu không giảm giá</label>
                <input type="number" name="old_price" value="<?= e($v['old_price'] ?? '') ?>" min="0" step="1000" placeholder="VD: 300000">
            </div>

            <div class="form-group">
                <label>Nhãn dán</label>
                <select name="badge">
                    <option value="">Không có</option>
                    <option value="hot" <?= ($v['badge'] ?? '') === 'hot' ? 'selected' : '' ?>>🔥 Hot</option>
                    <option value="sale" <?= ($v['badge'] ?? '') === 'sale' ? 'selected' : '' ?>>🏷️ Sale</option>
                    <option value="new" <?= ($v['badge'] ?? '') === 'new' ? 'selected' : '' ?>>✨ Mới</option>
                </select>
            </div>

            <div class="form-group">
                <label>Tồn kho</label>
                <input type="number" name="stock_quantity" value="<?= e($v['stock_quantity'] ?? '0') ?>" min="0">
            </div>

            <div class="form-group">
                <label>Điểm đánh giá (0-5)</label>
                <input type="number" name="rating_avg" value="<?= e($v['rating_avg'] ?? '0') ?>" min="0" max="5" step="0.1">
            </div>

            <div class="form-group">
                <label>Số lượt đánh giá</label>
                <input type="number" name="review_count" value="<?= e($v['review_count'] ?? '0') ?>" min="0">
            </div>

            <div class="form-group full">
                <label>Mô tả ngắn</label>
                <input type="text" name="short_description" value="<?= e($v['short_description'] ?? '') ?>" placeholder="Tóm tắt ngắn gọn sản phẩm">
            </div>

            <div class="form-group full">
                <label>Chi tiết sản phẩm</label>
                <textarea name="description" placeholder="Mô tả chi tiết về sản phẩm..."><?= e($v['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group full">
                <label>Ảnh sản phẩm</label>
                <?php if (!empty($v['image_url'])): ?>
                    <div class="image-preview">
                        <img src="<?= e($v['image_url']) ?>" alt="Ảnh hiện tại">
                    </div>
                    <input type="hidden" name="image_url" value="<?= e($v['image_url']) ?>">
                <?php endif; ?>
                <input type="file" name="image" accept="image/*" style="padding:8px; border:1px dashed var(--gray); border-radius:10px; width:100%;">
                <div style="font-size:0.78rem; color:var(--text-light); margin-top:6px;">Chấp nhận JPG, PNG, WEBP, GIF. Tối đa 5MB. Nếu không chọn, giữ ảnh cũ.</div>
            </div>

            <div class="form-group full">
                <label style="display:flex; gap:24px; flex-wrap:wrap;">
                    <span class="form-check">
                        <input type="checkbox" name="is_best_seller" value="1" <?= !empty($v['is_best_seller']) ? 'checked' : '' ?>>
                        Bán chạy
                    </span>
                    <span class="form-check">
                        <input type="checkbox" name="is_active" value="1" <?= !isset($v['is_active']) || !empty($v['is_active']) ? 'checked' : '' ?>>
                        Đang hoạt động
                    </span>
                </label>
            </div>

        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu sản phẩm</button>
            <a href="<?= e(url('/admin/product/add.php')) ?>" class="btn btn-outline"><i class="fas fa-plus"></i> Thêm mới</a>
            <a href="<?= e(url('/admin/product/list.php')) ?>" class="btn btn-outline">Hủy</a>
        </div>
    </form>
</div>

<script>
    // Khi có ảnh mới được chọn, hiển thị preview và ghi đè image_url
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.querySelector('input[type="file"][name="image"]');
        if (!fileInput) return;
        fileInput.addEventListener('change', function() {
            const preview = document.querySelector('.image-preview');
            const hidden = document.querySelector('input[name="image_url"]');
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let img;
                    if (preview) {
                        img = preview.querySelector('img') || document.createElement('img');
                        img.src = e.target.result;
                        if (!preview.querySelector('img')) preview.appendChild(img);
                    } else {
                        const box = document.createElement('div');
                        box.className = 'image-preview';
                        img = document.createElement('img');
                        img.src = e.target.result;
                        box.appendChild(img);
                        this.closest('.form-group').insertBefore(box, this);
                    }
                    if (hidden) hidden.value = '';
                }.bind(this);
                reader.readAsDataURL(this.files[0]);
            }
        });
    });

    function prepareForm() {
        const hidden = document.querySelector('input[name="image_url"]');
        const file = document.querySelector('input[type="file"][name="image"]');
        if (file && file.files && file.files.length > 0 && hidden) {
            hidden.removeAttribute('name');
        }
    }
</script>