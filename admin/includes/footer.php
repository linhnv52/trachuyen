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
        if (confirm(message || 'Bạn có chắc chắn muốn xóa?')) {
            form.submit();
        }
    }

    function confirmHideShow(form, current, name) {
        const msg = current
            ? 'Tạm ẩn sản phẩm "' + name + '" khỏi website?\n(Sản phẩm sẽ không hiển thị cho khách hàng)'
            : 'Hiện sản phẩm "' + name + '" trở lại website?';
        if (confirm(msg)) {
            form.submit();
        }
    }

    // Ảnh không tải được -> thay bằng placeholder cục bộ
    document.addEventListener('error', function (e) {
        if (e.target && e.target.tagName === 'IMG') {
            e.target.onerror = null;
            e.target.src = '/img/placeholder.svg';
        }
    }, true);
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