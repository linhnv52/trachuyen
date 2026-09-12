(function () {
    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function toggleEmpty(list) {
        var empty = list.querySelector('.article-list__empty');
        if (empty) {
            empty.style.display = list.querySelector('.article-entry') ? 'none' : '';
        }
    }

    function buildEntry(group, index, title, body) {
        var div = document.createElement('div');
        div.className = 'article-entry';
        div.innerHTML =
            '<input type="text" name="article[' + esc(group) + '][' + index + '][title]" value="' + esc(title) +
            '" placeholder="Tiêu đề bài viết">' +
            '<textarea name="article[' + esc(group) + '][' + index + '][body]" placeholder="Nội dung bài viết (mỗi đoạn cách nhau 1 dòng trống)">' +
            esc(body) + '</textarea>' +
            '<div class="entry-actions"><button type="button" class="btn-remove"><i class="fas fa-trash"></i> Xóa bài</button></div>';
        div.querySelector('.btn-remove').addEventListener('click', function () {
            div.remove();
            toggleEmpty(list);
        });
        return div;
    }

    var lists = document.querySelectorAll('.article-list');
    var list = null;
    lists.forEach(function (l) {
        if (l.querySelector('.article-entry')) {
            list = l;
        }
    });

    document.querySelectorAll('.btn-add').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var group = btn.getAttribute('data-group');
            var list = document.getElementById('articleList-' + group);
            var count = list.querySelectorAll('.article-entry').length;
            var empty = list.querySelector('.article-list__empty');
            if (empty) empty.remove();
            list.appendChild(buildEntry(group, count, '', ''));
            toggleEmpty(list);
        });
    });

    document.querySelectorAll('.article-entry .btn-remove').forEach(function (b) {
        b.addEventListener('click', function () {
            var entry = b.closest('.article-entry');
            var list = entry ? entry.closest('.article-list') : null;
            if (entry) entry.remove();
            if (list) toggleEmpty(list);
        });
    });
})();