/**
 * Smart search (autocomplete gợi ý sản phẩm)
 * Dùng chung cho trang công khai và trang admin.
 *
 * Cách dùng (2 chế độ):
 *   A) Chạy có server PHP — truyền endpoint:
 *   initSmartSearch({
 *       input:    element,       // <input> tìm kiếm
 *       resultsEl: element,      // container hiển thị gợi ý
 *       endpoint:  'api/search.php',   // endpoint (có thể kèm ?admin=1)
 *       minChars:  2,
 *       onPick:    function(item) { ... }
 *   });
 *
 *   B) Bản tĩnh (GitHub Pages) — truyền dataUrl để lọc cục bộ:
 *   initSmartSearch({
 *       input:    element,
 *       resultsEl: element,
 *       dataUrl:  'data/products.json',  // JSON tĩnh: {products:[{...}], ...}
 *       minChars:  2,
 *       onPick:    function(item) { ... }
 *   });
 */
function initSmartSearch(config) {
    var input = config.input;
    var resultsEl = config.resultsEl;
    var endpoint = config.endpoint;
    var dataUrl = config.dataUrl;
    var minChars = config.minChars || 2;
    var onPick = config.onPick || function () {};
    var localCache = null;
    var localLoaded = false;

    var debounceTimer = null;
    var currentItems = [];
    var activeIndex = -1;
    var isOpen = false;

    function formatPrice(n) {
        return Number(n || 0).toLocaleString('vi-VN');
    }

    function close() {
        isOpen = false;
        resultsEl.classList.remove('open');
        resultsEl.innerHTML = '';
        currentItems = [];
        activeIndex = -1;
    }

    function setActive(index) {
        activeIndex = index;
        var children = resultsEl.children;
        for (var i = 0; i < children.length; i++) {
            children[i].classList.toggle('active', i === index);
        }
    }

    function render(items) {
        resultsEl.innerHTML = '';
        currentItems = items;
        activeIndex = -1;

        if (!items.length) {
            var empty = document.createElement('div');
            empty.className = 'smart-search-status';
            empty.textContent = 'Không tìm thấy sản phẩm phù hợp';
            resultsEl.appendChild(empty);
            resultsEl.classList.add('open');
            isOpen = true;
            return;
        }

        items.forEach(function (item, idx) {
            var row = document.createElement('a');
            row.className = 'smart-search-item';
            row.href = '#';
            row.dataset.index = idx;

            var img = document.createElement('img');
            img.src = item.image_url || '/img/placeholder.svg';
            img.alt = item.name;
            img.loading = 'lazy';

            var info = document.createElement('div');
            info.className = 'info';
            var name = document.createElement('div');
            name.className = 'name';
            name.textContent = item.name;
            var meta = document.createElement('div');
            meta.className = 'meta';
            meta.textContent = (item.code || '') + ' · ' + (item.category_name || '');
            info.appendChild(name);
            info.appendChild(meta);

            var price = document.createElement('div');
            price.className = 'price';
            price.textContent = formatPrice(item.price) + 'đ';

            row.appendChild(img);
            row.appendChild(info);
            row.appendChild(price);

            row.addEventListener('click', function (ev) {
                ev.preventDefault();
                onPick(item);
                close();
            });
            row.addEventListener('mousemove', function () {
                setActive(idx);
            });

            resultsEl.appendChild(row);
        });

        resultsEl.classList.add('open');
        isOpen = true;
    }

    function doSearch(q) {
        if (dataUrl) {
            if (!localLoaded) {
                fetch(dataUrl)
                    .then(function (res) { return res.json(); })
                    .then(function (data) { localCache = data; localLoaded = true; matchLocal(q); })
                    .catch(function () { close(); });
                return;
            }
            matchLocal(q);
            return;
        }

        var sep = endpoint.indexOf('?') !== -1 ? '&' : '?';
        fetch(endpoint + sep + 'q=' + encodeURIComponent(q))
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (input.value.trim() !== q) return; // tránh race condition
                render(Array.isArray(data) ? data : []);
            })
            .catch(function () {
                if (input.value.trim() === q) close();
            });
    }

    function normalizeS(s) {
        var t = String(s || '').toLowerCase();
        var map = {'à':'a','á':'a','ả':'a','ã':'a','ạ':'a','ă':'a','ắ':'a','ằ':'a','ẳ':'a','ẵ':'a','ặ':'a','â':'a','ấ':'a','ầ':'a','ẩ':'a','ẫ':'a','ậ':'a','đ':'d','è':'e','é':'e','ẻ':'e','ẽ':'e','ẹ':'e','ê':'e','ế':'e','ề':'e','ể':'e','ễ':'e','ệ':'e','ì':'i','í':'i','ỉ':'i','ĩ':'i','ị':'i','ò':'o','ó':'o','ỏ':'o','õ':'o','ọ':'o','ô':'o','ố':'o','ồ':'o','ổ':'o','ỗ':'o','ộ':'o','ơ':'o','ớ':'o','ờ':'o','ở':'o','ỡ':'o','ợ':'o','ù':'u','ú':'u','ủ':'u','ũ':'u','ụ':'u','ư':'u','ứ':'u','ừ':'u','ử':'u','ữ':'u','ự':'u','ỳ':'y','ý':'y','ỷ':'y','ỹ':'y','ỵ':'y'};
        var out = '';
        for (var i = 0; i < t.length; i++) {
            out += map[t[i]] || t[i];
        }
        return out;
    }

    function matchLocal(q) {
        if (input.value.trim() !== q) return; // tránh race condition
        var key = normalizeS(q);
        var data = localCache || {};
        var items = (data.products || []).map(function (p) {
            return {
                id: p.id,
                code: p.code,
                name: p.name,
                price: p.price,
                image_url: p.image_url,
                category_name: p.category_name,
                detail_url: p.detail_url,
            };
        });
        var parts = key.split(/\s+/).filter(Boolean);
        var filtered = items.filter(function (item) {
            var hay = normalizeS(item.name + ' ' + item.code + ' ' + item.category_name);
            return parts.every(function (part) { return hay.indexOf(part) !== -1; });
        }).slice(0, 8);
        render(filtered);
    }

    input.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        var q = input.value.trim();
        if (q.length < minChars) {
            close();
            return;
        }
        debounceTimer = setTimeout(function () { doSearch(q); }, 250);
    });

    input.addEventListener('keydown', function (e) {
        if (!isOpen || !currentItems.length) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setActive(activeIndex < currentItems.length - 1 ? activeIndex + 1 : 0);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setActive(activeIndex > 0 ? activeIndex - 1 : currentItems.length - 1);
        } else if (e.key === 'Enter') {
            if (activeIndex >= 0 && currentItems[activeIndex]) {
                e.preventDefault();
                onPick(currentItems[activeIndex]);
                close();
            }
        } else if (e.key === 'Escape') {
            e.preventDefault();
            close();
        }
    });

    // Đóng khi bấm ra ngoài
    document.addEventListener('click', function (e) {
        if (!resultsEl.contains(e.target) && e.target !== input) {
            close();
        }
    });

    input.addEventListener('blur', function () {
        setTimeout(close, 150);
    });
}