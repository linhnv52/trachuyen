/**
 * Smart search (autocomplete gợi ý sản phẩm)
 * Dùng chung cho trang công khai và trang admin.
 *
 * Cách dùng:
 *   initSmartSearch({
 *       input:    element,       // <input> tìm kiếm
 *       resultsEl: element,      // container hiển thị gợi ý
 *       endpoint:  'api/search.php',   // endpoint (có thể kèm ?admin=1)
 *       minChars:  2,
 *       onPick:    function(item) { ... }   // item: {id, code, name, price, image_url, category_name}
 *   });
 */
function initSmartSearch(config) {
    var input = config.input;
    var resultsEl = config.resultsEl;
    var endpoint = config.endpoint;
    var minChars = config.minChars || 2;
    var onPick = config.onPick || function () {};

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