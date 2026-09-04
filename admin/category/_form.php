<?php
/**
 * Form danh mục dùng chung cho add.php / update.php
 * Cần khai báo trước: $category (array|null), $errors (array), $old (array), $parentCategories (array)
 */
$v = $old;
$errors = $errors ?? [];
function categoryFieldError(string $key): void
{
    global $errors;
    if (isset($errors[$key])): ?>
        <div style="color:#c62828; font-size:0.78rem; margin-top:4px;"><i class="fas fa-exclamation-circle"></i> <?= e($errors[$key]) ?></div>
    <?php endif;
}
?>
<style>
    .form-card { background:#fff; border-radius:var(--radius); box-shadow:var(--shadow); padding:28px; max-width:760px; }
    .form-group { margin-bottom:18px; }
    .form-group label { display:block; font-size:0.85rem; font-weight:600; color:var(--primary-dark); margin-bottom:6px; }
    .form-group label .required { color:#c62828; }
    .form-group input[type="text"],
    .form-group input[type="number"],
    .form-group select,
    .form-group textarea {
        width:100%; padding:11px 14px; border:1px solid var(--gray); border-radius:10px;
        font-size:0.9rem; color:var(--text); outline:none; font-family:'Roboto',sans-serif;
        transition:border-color .3s, box-shadow .3s; background:#fff;
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
        border-color:var(--gold); box-shadow:0 0 0 3px rgba(184,134,11,.12);
    }
    .form-group textarea { resize:vertical; min-height:100px; line-height:1.6; }
    .form-check { display:flex; align-items:center; gap:10px; font-size:0.9rem; color:var(--text); cursor:pointer; }
    .form-check input { width:18px; height:18px; accent-color:var(--gold); }
    .image-preview { width:180px; height:120px; border-radius:12px; overflow:hidden; border:1px dashed var(--gray);
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
    <form method="post" action="<?= e($categoryFormAction ?? '') ?>" enctype="multipart/form-data" onsubmit="prepareCategoryForm()">
        <?= csrf_field() ?>
        <?php if (!empty($section)): ?><input type="hidden" name="section" value="<?= e($section) ?>"><?php endif; ?>
        <div class="form-group">
            <label>Tên danh mục <span class="required">*</span></label>
            <input type="text" name="name" value="<?= e($v['name'] ?? '') ?>" required placeholder="VD: Trà Xanh Việt">
            <?php categoryFieldError('name'); ?>
        </div>

        <div class="form-group">
            <label>Slug (đường dẫn thân thiện)</label>
            <input type="text" name="slug" value="<?= e($v['slug'] ?? '') ?>" placeholder="Tự sinh từ tên nếu để trống">
            <?php categoryFieldError('slug'); ?>
        </div>

        <div class="form-group">
            <label>Mô tả danh mục</label>
            <textarea name="description" placeholder="Mô tả ngắn về danh mục..."><?= e($v['description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label>Ảnh danh mục</label>
            <?php if (!empty($v['image_url'])): ?>
                <div class="image-preview">
                    <img src="<?= e($v['image_url']) ?>" alt="Ảnh hiện tại">
                </div>
                <input type="hidden" name="image_url" value="<?= e($v['image_url']) ?>">
            <?php endif; ?>
            <input type="file" name="image" accept="image/*" style="padding:8px; border:1px dashed var(--gray); border-radius:10px; width:100%;">
            <div style="font-size:0.78rem; color:var(--text-light); margin-top:6px;">Chấp nhận JPG, PNG, WEBP, GIF. Tối đa 5MB. Nếu không chọn, giữ ảnh cũ.</div>
        </div>

        <div class="form-group">
            <label>Thứ tự hiển thị</label>
            <input type="number" name="sort_order" value="<?= e($v['sort_order'] ?? '0') ?>" min="0">
        </div>

        <div class="form-group">
            <label style="display:flex; gap:24px; flex-wrap:wrap;">
                <span class="form-check">
                    <input type="checkbox" name="is_active" value="1" <?= !isset($v['is_active']) || !empty($v['is_active']) ? 'checked' : '' ?>>
                    Hoạt động (hiển thị trên website)
                </span>
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu danh mục</button>
            <a href="<?= e(url('/admin/category/add.php' . (!empty($section) ? '?section=' . urlencode($section) : ''))) ?>" class="btn btn-outline"><i class="fas fa-plus"></i> Thêm mới</a>
            <a href="<?= e(url('/admin/category/list.php' . (!empty($section) ? '?section=' . urlencode($section) : ''))) ?>" class="btn btn-outline">Hủy</a>
        </div>
    </form>
</div>

<script>
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

    function prepareCategoryForm() {
        const hidden = document.querySelector('input[name="image_url"]');
        const file = document.querySelector('input[type="file"][name="image"]');
        if (file && file.files && file.files.length > 0 && hidden) {
            hidden.removeAttribute('name');
        }
    }
</script>
