<?php
/**
 * Footer chung cho các trang admin
 */
?>
</main>

<div class="toast" id="toast">
    <div style="display:flex; align-items:center; gap:10px;">
        <i class="fas fa-check-circle" id="toastIcon"></i>
        <span id="toastMessage"></span>
    </div>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('active');
    }

    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const icon = document.getElementById('toastIcon');
        const msg = document.getElementById('toastMessage');
        icon.className = 'fas ' + (type === 'error' ? 'fa-times-circle' : 'fa-check-circle');
        toast.className = 'toast ' + type + ' show';
        msg.textContent = message;
        clearTimeout(window.__toastTimer);
        window.__toastTimer = setTimeout(function() {
            toast.classList.remove('show');
        }, 3000);
    }

    function confirmDelete(form, message) {
        return window.confirm(message || 'Bạn có chắc chắn muốn xóa?');
    }

    function confirmHideShow(form, current, name) {
        const msg = current
            ? 'Tạm ẩn sản phẩm "' + name + '" khỏi website?\n(Sản phẩm sẽ không hiển thị cho khách hàng)'
            : 'Hiện sản phẩm "' + name + '" trở lại website?';
        return window.confirm(msg);
    }

    // Ảnh không tải được -> thay bằng placeholder cục bộ
    document.addEventListener('error', function (e) {
        if (e.target && e.target.tagName === 'IMG') {
            e.target.onerror = null;
            e.target.src = '/img/placeholder.svg';
        }
    }, true);

    // Cập nhật website một nút
    var __csrfToken = <?= json_encode(csrf_token(), JSON_UNESCAPED_SLASHES) ?>;
    function rebuildWebsite() {
        var btn = document.getElementById('rebuildWebsiteBtn');
        if (!btn || btn.classList.contains('busy')) return;
        btn.classList.add('busy');
        var original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Đang cập nhật...';
        btn.disabled = true;
        var data = new FormData();
        data.append('csrf_token', __csrfToken);
        fetch('<?= url('/admin/rebuild.php') ?>', {
            method: 'POST',
            credentials: 'same-origin',
            body: data
        }).then(function (res) {
            return res.json().catch(function () { return { ok: false, message: 'Phản hồi không hợp lệ.' }; });
        }).then(function (result) {
            showToast((result && result.message) || 'Đã hoàn tất.', (result && result.ok) ? 'success' : 'error');
        }).catch(function () {
            showToast('Không thể kết nối tới máy chủ.', 'error');
        }).finally(function () {
            btn.classList.remove('busy');
            btn.innerHTML = original;
            btn.disabled = false;
        });
    }
</script>
<script src="<?= url('/js/smart-search.js') ?>"></script>
<script>
    (function () {
        var input = document.getElementById('adminSearchInput');
        var results = document.getElementById('adminSearchResults');
        if (!input || !results) return;
        initSmartSearch({
            input: input,
            resultsEl: results,
            endpoint: '<?= url('/api/search.php') ?>' + '?admin=1',
            minChars: 2,
            onPick: function (item) {
                window.location.href = '<?= url('/admin/product/update.php') ?>?id=' + item.id;
            }
        });
    })();
</script>
</body>
</html>
