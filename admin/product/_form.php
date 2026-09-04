<?php
/**
 * Form sản phẩm dùng chung cho add.php / update.php
 * Cần khai báo trước: $product (array|null), $categories, $errors (array), $old (array)
 */
$v = $old; // các giá trị cần điền vào form
$errors = $errors ?? [];
$categoryRoots = [];
$categoryChildren = [];
foreach (($categories ?? []) as $categoryOption) {
    $categoryParentId = $categoryOption['parent_id'] !== null ? (int)$categoryOption['parent_id'] : 0;
    if ($categoryParentId === 0) {
        $categoryRoots[(int)$categoryOption['id']] = $categoryOption;
    } else {
        $categoryChildren[$categoryParentId][] = $categoryOption;
    }
}
// Chỉ nhóm Ấm Tử Sa và toàn bộ danh mục con được nhập dung tích.
$_curCatId = (int)($v['category_id'] ?? 0);
$_curCatRootSlug = '';
if ($_curCatId > 0 && function_exists('categoryRoot')) {
    $_curCatRoot = categoryRoot($_curCatId, $categories);
    $_curCatRootSlug = (string)($_curCatRoot['slug'] ?? '');
}
$_isTeaUtensil = $_curCatRootSlug === 'amtusa';
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
    .category-picker { position:relative; }
    .category-picker-toggle { width:100%; display:flex; align-items:center; justify-content:space-between; gap:12px; padding:11px 14px; border:1px solid var(--gray); border-radius:10px; background:#fff; color:var(--text); font:inherit; text-align:left; cursor:pointer; }
    .category-picker-toggle:hover, .category-picker.is-open .category-picker-toggle { border-color:var(--gold); box-shadow:0 0 0 3px rgba(184,134,11,.12); }
    .category-picker-menu { position:absolute; z-index:20; top:calc(100% + 6px); left:0; right:0; max-height:300px; overflow:auto; padding:8px; border:1px solid var(--gray); border-radius:10px; background:#fff; box-shadow:0 12px 30px rgba(50,35,20,.16); }
    .category-picker-empty, .category-picker-group-toggle, .category-picker-option { width:100%; border:0; background:transparent; color:var(--text); font:inherit; text-align:left; cursor:pointer; }
    .category-picker-empty { padding:9px 10px; color:var(--text-light); border-bottom:1px solid #eee; }
    .category-picker-group-toggle { display:flex; align-items:center; gap:9px; padding:10px; font-weight:700; color:var(--primary-dark); }
    .category-picker-plus { width:18px; height:18px; display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--gold); border-radius:4px; color:var(--gold); font-weight:700; line-height:1; }
    .category-picker-children { padding:0 0 5px 30px; }
    .category-picker-option { padding:8px 10px; border-radius:6px; }
    .category-picker-empty:hover, .category-picker-option:hover { background:#f7efe5; color:var(--primary-dark); }
</style>

<?php if ($errors): ?>
    <div style="background:#fdecea; color:#c62828; padding:14px 18px; border-radius:10px; margin-bottom:20px;">
        <i class="fas fa-exclamation-circle"></i> Vui lòng kiểm tra lại thông tin nhập.
    </div>
<?php endif; ?>

<div class="form-card">
    <form method="post" enctype="multipart/form-data" onsubmit="prepareForm()">
        <?= csrf_field() ?>
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
                <label>Danh mục <span style="color:#888; font-weight:400;">(có thể chọn sau)</span></label>
                <?php
                $_selectedCategoryName = 'Chưa phân loại';
                $_selectedCategorySlug = '';
                foreach ($categories as $_categoryOption) {
                    if ((string)($_categoryOption['id'] ?? '') === (string)($v['category_id'] ?? '')) {
                        $_selectedCategoryName = $_categoryOption['name'];
                        $_selectedCategorySlug = $_categoryOption['slug'];
                        break;
                    }
                }
                ?>
                <input type="hidden" name="category_id" id="categorySelect" value="<?= e($v['category_id'] ?? '') ?>" data-slug="<?= e($_selectedCategorySlug) ?>" data-root-slug="<?= e($_curCatRootSlug) ?>">
                <div class="category-picker" id="categoryPicker">
                    <button type="button" class="category-picker-toggle" aria-expanded="false">
                        <span id="selectedCategoryName"><?= e($_selectedCategoryName) ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="category-picker-menu" hidden>
                        <button type="button" class="category-picker-empty" data-category-id="" data-slug="">-- Chưa phân loại --</button>
                        <?php foreach ($categoryRoots as $rootId => $root): ?>
                            <?php if (!empty($categoryChildren[$rootId])): ?>
                                <div class="category-picker-group">
                                    <button type="button" class="category-picker-group-toggle" aria-expanded="false">
                                        <span class="category-picker-plus">+</span>
                                        <span><?= e($root['name']) ?></span>
                                    </button>
                                    <div class="category-picker-children" hidden>
                                        <?php foreach ($categoryChildren[$rootId] as $c): ?>
                                            <button type="button" class="category-picker-option" data-category-id="<?= (int)$c['id'] ?>" data-slug="<?= e($c['slug']) ?>" data-root-slug="<?= e($root['slug']) ?>"><?= e($c['name']) ?></button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
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

            <div class="form-group" id="capacityField" style="<?= $_isTeaUtensil ? '' : 'display:none;' ?>">
                <label>Dung tích (ml)</label>
                <input type="number" name="capacity" value="<?= e($v['capacity'] ?? '') ?>" min="1" placeholder="VD: 200">
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
                        <img src="<?= e(productImage($v['image_url'])) ?>" alt="Ảnh hiện tại">
                    </div>
                    <input type="hidden" name="image_url" value="<?= e($v['image_url']) ?>">
                <?php endif; ?>
                <input type="file" name="image" accept="image/*" style="padding:8px; border:1px dashed var(--gray); border-radius:10px; width:100%;">
                <div style="font-size:0.78rem; color:var(--text-light); margin-top:6px;">Chấp nhận JPG, PNG, WEBP, GIF. Tối đa 5MB. Nếu không chọn, giữ ảnh cũ.</div>
            </div>

            <div class="form-group full">
                <label>Ảnh phụ (gallery)</label>
                <?php if (!empty($v['gallery'])): ?>
                    <div style="display:flex; flex-wrap:wrap; gap:12px; margin-bottom:10px;">
                        <?php foreach (productGallery($v['gallery']) as $gImg): ?>
                            <div style="width:90px; text-align:center;">
                                <div class="image-preview" style="width:90px; height:90px;"><img src="<?= e($gImg) ?>" alt="Ảnh gallery"></div>
                                <label class="form-check" style="font-size:0.75rem; margin-top:4px;">
                                    <input type="checkbox" name="remove_gallery[]" value="<?= e($gImg) ?>"> Xóa
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <input type="file" name="gallery[]" id="galleryInput" accept="image/*" multiple style="padding:8px; border:1px dashed var(--gray); border-radius:10px; width:100%;">
                <div id="galleryPreview" style="display:flex; flex-wrap:wrap; gap:8px; margin-top:8px;"></div>
                <div style="font-size:0.78rem; color:var(--text-light); margin-top:6px;">Chọn cùng lúc nhiều ảnh làm slider dưới ảnh chính. Tick "Xóa" để loại ảnh hiện tại khi lưu.</div>
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

    // Preview ảnh gallery mới chọn
    document.addEventListener('DOMContentLoaded', function() {
        const gInput = document.getElementById('galleryInput');
        const gPreview = document.getElementById('galleryPreview');
        if (!gInput || !gPreview) return;
        gInput.addEventListener('change', function() {
            gPreview.innerHTML = '';
            Array.from(this.files || []).forEach(function(f) {
                if (!f.type.startsWith('image/')) return;
                const box = document.createElement('div');
                box.className = 'image-preview';
                box.style.cssText = 'width:70px; height:70px;';
                const img = document.createElement('img');
                img.src = URL.createObjectURL(f);
                box.appendChild(img);
                gPreview.appendChild(box);
            });
        });
    });

    // Danh mục dạng accordion: bấm + để mở, - để thu nhỏ.
    document.addEventListener('DOMContentLoaded', function() {
        const picker = document.getElementById('categoryPicker');
        const categorySelect = document.getElementById('categorySelect');
        const selectedName = document.getElementById('selectedCategoryName');
        if (picker && categorySelect && selectedName) {
            const pickerToggle = picker.querySelector('.category-picker-toggle');
            const pickerMenu = picker.querySelector('.category-picker-menu');
            pickerToggle.addEventListener('click', function() {
                const open = picker.classList.toggle('is-open');
                pickerMenu.hidden = !open;
                pickerToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            picker.querySelectorAll('.category-picker-group-toggle').forEach(function(groupToggle) {
                groupToggle.addEventListener('click', function() {
                    const children = groupToggle.nextElementSibling;
                    const open = groupToggle.getAttribute('aria-expanded') !== 'true';
                    children.hidden = !open;
                    groupToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                    groupToggle.querySelector('.category-picker-plus').textContent = open ? '−' : '+';
                });
            });
            picker.querySelectorAll('[data-category-id]').forEach(function(option) {
                option.addEventListener('click', function() {
                    categorySelect.value = option.dataset.categoryId || '';
                    categorySelect.dataset.slug = option.dataset.slug || '';
                    categorySelect.dataset.rootSlug = option.dataset.rootSlug || '';
                    selectedName.textContent = option.textContent.trim();
                    picker.classList.remove('is-open');
                    pickerMenu.hidden = true;
                    pickerToggle.setAttribute('aria-expanded', 'false');
                    categorySelect.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        }

        // Hiện trường dung tích ngay khi chọn danh mục Ấm Tử Sa hoặc Bộ Trà Cụ.
        const capacityField = document.getElementById('capacityField');
        if (!categorySelect || !capacityField) return;
        const updateCapacityField = function() {
            const show = categorySelect.dataset.rootSlug === 'amtusa';
            capacityField.style.display = show ? '' : 'none';
            if (!show) capacityField.querySelector('input').value = '';
        };
        categorySelect.addEventListener('change', updateCapacityField);
        updateCapacityField();
    });
</script>
