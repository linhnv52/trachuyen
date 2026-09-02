/**
 * Bản tĩnh (GitHub Pages) — điều khiển trang liệt kê sản phẩm hoàn toàn client-side.
 * Thay thế phần lọc/sort/phân trang/tab mà PHP đã làm server-side.
 *
 * Cấu hình trang được build script chèn vào <script type="application/json" id="pageConfig">:
 * {
 *   "baseFile":  "san-pham-tra.html",      // file trang (để build URL lọc/phân trang)
 *   "fixedSlug": "am-tu-sa",               // trang chuyên mục cố định ('' = không)
 *   "groupIds":  [1,2,3,...],              // ([] = tất cả) giới hạn tab + sản phẩm
 *   "showTabs":  true,
 *   "perPage":   8
 * }
 *
 * Sản phẩm đọc từ data/products.json: products: [{id, code, name, price, old_price,
 * discount, badge_label, badge_class, image_url, category_id, category_name,
 * category_slug, stock_quantity, views, capacity, description, detail_url}]
 *
 * Ghi chú: slider/carousel/view-toggle đã được xử lý bởi script inline của PHP,
 * file này chỉ lo phần lọc + đổ sản phẩm.
 */
(function () {
    var grid = document.getElementById('productsGrid');
    if (!grid) return;

    var config = { baseFile: 'product.html', fixedSlug: '', groupIds: [], showTabs: true, perPage: 8 };
    var cfgEl = document.getElementById('pageConfig');
    if (cfgEl) {
        try { config = Object.assign(config, JSON.parse(cfgEl.textContent)); } catch (e) { }
    }

    var products = [];
    var categoriesMap = {};
    var capacitiesByProduct = {};

    // Mốc giá (radio) — khớp với $PRICE_BRACKETS trong includes/product-listing.php
    var PRICE_BRACKETS = {
        'duoi-500': [null, 500000],
        '500-1m':   [500000, 1000000],
        '1m-1.5m':  [1000000, 1500000],
        '1.5m-2m':  [1500000, 2000000],
        'tren-2m':  [2000000, null]
    };

    function formatPrice(n) { return Number(n || 0).toLocaleString('vi-VN'); }

    function e(s) {
        var div = document.createElement('div');
        div.textContent = s == null ? '' : String(s);
        return div.innerHTML;
    }

    function normalizeS(s) {
        var t = String(s || '').toLowerCase();
        var map = {'à':'a','á':'a','ả':'a','ã':'a','ạ':'a','ă':'a','ắ':'a','ằ':'a','ẳ':'a','ẵ':'a','ặ':'a','â':'a','ấ':'a','ầ':'a','ẩ':'a','ẫ':'a','ậ':'a','đ':'d','è':'e','é':'e','ẻ':'e','ẽ':'e','ẹ':'e','ê':'e','ế':'e','ề':'e','ể':'e','ễ':'e','ệ':'e','ì':'i','í':'i','ỉ':'i','ĩ':'i','ị':'i','ò':'o','ó':'o','ỏ':'o','õ':'o','ọ':'o','ô':'o','ố':'o','ồ':'o','ổ':'o','ỗ':'o','ộ':'o','ơ':'o','ớ':'o','ờ':'o','ở':'o','ỡ':'o','ợ':'o','ù':'u','ú':'u','ủ':'u','ũ':'u','ụ':'u','ư':'u','ứ':'u','ừ':'u','ử':'u','ữ':'u','ự':'u','ỳ':'y','ý':'y','ỷ':'y','ỹ':'y','ỵ':'y'};
        var out = '';
        for (var i = 0; i < t.length; i++) { out += map[t[i]] || t[i]; }
        return out;
    }

    function getParams() {
        var p = {};
        (window.location.search || '').replace(/^\?/, '').split('&').forEach(function (kv) {
            if (!kv) return;
            var pair = kv.split('=');
            var k = decodeURIComponent(pair[0]);
            var v = decodeURIComponent((pair[1] || '').replace(/\+/g, ' '));
            if (k === 'capacity[]') {
                p.capacity = p.capacity || [];
                var n = parseInt(v, 10);
                if (!isNaN(n)) p.capacity.push(n);
            } else if (p[k] !== undefined) {
                p[k] = [].concat(p[k], v);
            } else {
                p[k] = v;
            }
        });
        return p;
    }

    function uint(v) { var n = parseInt(v, 10); return isNaN(n) ? 0 : n; }

    function paramValue(params, k, def) {
        var v = params[k];
        return v === undefined ? def : (Array.isArray(v) ? v[0] : v);
    }

    function buildUrl(overrides) {
        var p = getParams();
        Object.keys(overrides).forEach(function (k) {
            var v = overrides[k];
            if (v === '' || v === null || v === undefined || (Array.isArray(v) && !v.length)) {
                delete p[k];
            } else {
                p[k] = v;
            }
        });
        var parts = [];
        Object.keys(p).forEach(function (k) {
            var val = p[k];
            if (Array.isArray(val)) {
                val.forEach(function (x) { parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(x)); });
            } else {
                parts.push(encodeURIComponent(k) + '=' + encodeURIComponent(val));
            }
        });
        return parts.length ? config.baseFile + '?' + parts.join('&') : config.baseFile;
    }

    function apply() {
        var params = getParams();
        var q = String(paramValue(params, 'q', '') || '').trim();
        var sort = paramValue(params, 'sort', 'default') || 'default';
        var category = paramValue(params, 'category', '') || '';
        var page = Math.max(1, uint(paramValue(params, 'page', 1)) || 1);
        var priceRange = paramValue(params, 'price_range', '') || '';
        var pr = PRICE_BRACKETS[priceRange] || null;
        var minPrice = pr ? (pr[0] || '') : '';
        var maxPrice = pr ? (pr[1] || '') : '';
        var caps = (params.capacity || []).map(uint).filter(function (x) { return x > 0; });

        var fixedId = 0;
        if (config.fixedSlug && categoriesMap[config.fixedSlug]) fixedId = categoriesMap[config.fixedSlug].id;
        var catId = fixedId;
        if (!catId && category && categoriesMap[category]) catId = categoriesMap[category].id;

        var list = products.filter(function (p) {
            if (config.groupIds.length && config.groupIds.indexOf(p.category_id) === -1) return false;
            if (catId && p.category_id !== catId) return false;
            if (q) {
                var hay = normalizeS(p.name + ' ' + p.code + ' ' + p.category_name);
                var parts = normalizeS(q).split(/\s+/).filter(Boolean);
                if (!parts.every(function (part) { return hay.indexOf(part) !== -1; })) return false;
            }
            if (minPrice !== '' && Number(p.price) < Number(minPrice)) return false;
            if (maxPrice !== '' && Number(p.price) > Number(maxPrice)) return false;
            if (caps.length && caps.indexOf(capacitiesByProduct[p.id]) === -1) return false;
            return true;
        });

        list = list.slice();
        if (sort === 'price-asc') list.sort(function (a, b) { return Number(a.price) - Number(b.price); });
        else if (sort === 'price-desc') list.sort(function (a, b) { return Number(b.price) - Number(a.price); });
        else if (sort === 'name') list.sort(function (a, b) { return String(a.name).localeCompare(String(b.name), 'vi'); });
        else list.sort(function (a, b) {
            if ((b.badge_label === 'Hot') !== (a.badge_label === 'Hot')) return (b.badge_label === 'Hot') ? 1 : -1;
            return Number(b.views || 0) - Number(a.views || 0);
        });

        var total = list.length;
        var totalPages = Math.max(1, Math.ceil(total / config.perPage));
        if (page > totalPages) page = totalPages;
        var items = list.slice((page - 1) * config.perPage, page * config.perPage);

        renderGrid(items);
        renderPagination(totalPages, page);
        updateToolbar(total);
        syncSidebar(sort, priceRange, caps);
        activateTab(catId || fixedId);
    }

    function productCardHTML(p) {
        var badge = '';
        if (p.badge_label) {
            badge = '<div class="product-badge ' + (p.badge_class || '') + '">' + e(p.badge_label) + '</div>';
        }
        var price = Number(p.old_price) > Number(p.price)
            ? '<span class="current-price">' + formatPrice(p.price) + 'đ</span><span class="old-price">' + formatPrice(p.old_price) + 'đ</span>'
            : '<span class="current-price">' + formatPrice(p.price) + 'đ</span>';
        return '<div class="product-item" data-id="' + p.id + '">' + badge +
            '<a href="' + e(p.detail_url) + '"><img src="' + e(p.image_url) + '" alt="' + e(p.name) + '"><h3>' + e(p.name) + '</h3></a>' +
            (p.capacity ? '<div class="product-capacity">Dung tích: ' + p.capacity + 'ml</div>' : '') +
            '<div class="product-price">' + price + '</div>' +
            '<a class="btn-add-cart" href="' + e(p.detail_url) + '" title="Xem chi tiết"><i class="fas fa-arrow-right"></i></a></div>';
    }

    function renderGrid(items) {
        if (!items.length) {
            grid.innerHTML = '<div class="no-products" style="grid-column: 1/-1;">' +
                '<i class="fas fa-leaf"></i><h3>Không có sản phẩm</h3>' +
                '<p>Không tìm thấy sản phẩm phù hợp với bộ lọc của bạn.</p></div>';
            return;
        }
        grid.innerHTML = items.map(productCardHTML).join('');
    }

    function renderPagination(totalPages, page) {
        var wrap = document.getElementById('pagination');
        if (!wrap) return;
        if (totalPages <= 1) { wrap.innerHTML = ''; return; }
        var html = '';
        html += '<a class="' + (page <= 1 ? 'disabled' : '') + '" href="' + e(buildUrl({ page: page - 1 })) + '"><i class="fas fa-chevron-left"></i></a>';
        for (var i = 1; i <= totalPages; i++) {
            if (i === page) html += '<a class="active">' + i + '</a>';
            else if (i === 1 || i === totalPages || Math.abs(i - page) <= 2) html += '<a href="' + e(buildUrl({ page: i })) + '">' + i + '</a>';
            else if (i === page - 3 || i === page + 3) html += '<span>...</span>';
        }
        html += '<a class="' + (page >= totalPages ? 'disabled' : '') + '" href="' + e(buildUrl({ page: page + 1 })) + '"><i class="fas fa-chevron-right"></i></a>';
        wrap.innerHTML = html;
        wrap.querySelectorAll('a:not(.disabled):not(.active)').forEach(function (a) {
            a.addEventListener('click', function (ev) {
                ev.preventDefault();
                var next = uint(new URL(a.href, location.href).searchParams.get('page'));
                updateQuery({ page: next && next > 1 ? next : '' });
            });
        });
    }

    function updateToolbar(total) {
        var el = document.querySelector('.result-count span');
        if (el) el.textContent = total;
    }

    function syncSidebar(sort, priceRange, caps) {
        var form = document.querySelector('.filter-sidebar');
        if (!form) return;
        var sortSel = form.querySelector('select[name="sort"]');
        if (sortSel) sortSel.value = sort;
        form.querySelectorAll('input[name="price_range"]').forEach(function (r) {
            r.checked = r.value === priceRange;
        });
        form.querySelectorAll('input[name="capacity[]"]').forEach(function (cb) {
            cb.checked = caps.indexOf(uint(cb.value)) !== -1;
        });
    }

    function activateTab(catId) {
        if (!config.showTabs) return;
        document.querySelectorAll('#categoryCards .category-item').forEach(function (card) {
            card.classList.toggle('active', String(uint(card.getAttribute('data-cat-id'))) === String(catId));
        });
    }

    function updateQuery(overrides) {
        var url = buildUrl(overrides);
        var target = url.indexOf('?') !== -1 ? url.slice(url.indexOf('?')) : '';
        if (location.search !== target) history.pushState({}, '', url);
        apply();
    }

    function hookCategories() {
        document.querySelectorAll('#categoryCards .category-item').forEach(function (card) {
            card.addEventListener('click', function (ev) {
                ev.preventDefault();
                var slug = card.getAttribute('data-cat-slug');
                if (!slug) { updateQuery({ category: '', page: '', sort: 'default' }); return; }
                updateQuery({ category: slug, page: '', sort: 'default' });
            });
        });
    }

    function hookSidebar() {
        var form = document.querySelector('.filter-sidebar');
        if (!form) return;
        var doSubmit = function () {
            var data = new FormData(form);
            var overrides = { page: '' };
            overrides.price_range = data.get('price_range') || '';
            overrides.sort = data.get('sort') || 'default';
            var caps = data.getAll('capacity[]').map(uint).filter(function (x) { return x > 0; });
            if (caps.length) overrides.capacity = caps; else overrides.capacity = '';
            updateQuery(overrides);
        };
        form.addEventListener('submit', function (ev) { ev.preventDefault(); doSubmit(); });
        form.addEventListener('change', function (ev) {
            if (ev.target.matches('select[name="sort"]') || ev.target.matches('input[name="capacity[]"]') || ev.target.matches('input[name="price_range"]')) doSubmit();
        });
    }

    fetch('data/products.json')
        .then(function (res) { if (!res.ok) throw new Error('no data'); return res.json(); })
        .then(function (data) {
            products = (data.products || []).slice();
            (data.categories || []).forEach(function (c) { categoriesMap[c.slug] = c; });
            var fixedId = 0;
            if (config.fixedSlug && categoriesMap[config.fixedSlug]) fixedId = categoriesMap[config.fixedSlug].id;
            products.forEach(function (p) { capacitiesByProduct[p.id] = uint(p.capacity); });

            // Dựng sidebar dung tích cho trang Ấm Tử Sa (nếu PHP chưa dựng đủ)
            if (fixedId) {
                var caps = products.map(function (p) { return capacitiesByProduct[p.id]; })
                    .filter(function (v) { return v > 0 && config.groupIds.indexOf(p) !== -1; }).length;
            }

            apply();
            hookCategories();
            hookSidebar();
            window.addEventListener('popstate', apply);
        })
        .catch(function () {});
})();