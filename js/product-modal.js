/**
 * Quick view - popup chi tiết sản phẩm khi click vào card
 *
 * Cách dùng:
 *   initProductModal({
 *       overlay: element,   // #productModalOverlay
 *       modal:   element,   // #productModal
 *       body:    element,   // container nội dung (#productModalBody)
 *       endpoint: 'api/product.php'
 *   });
 *
 * Click vào .product-item[data-id] (không phải nút .btn-add-cart) sẽ mở popup.
 */
function initProductModal(config) {
    var overlay = config.overlay;
    var modal = config.modal;
    var bodyEl = config.body;
    var endpoint = config.endpoint;

    var closeBtn = modal.querySelector('[data-modal-close]');

    function formatPrice(n) {
        return Number(n || 0).toLocaleString('vi-VN');
    }

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined && text !== null) node.textContent = text;
        return node;
    }

    function open() {
        overlay.classList.add('active');
        modal.classList.add('active');
        document.body.classList.add('modal-open');
    }

    function close() {
        overlay.classList.remove('active');
        modal.classList.remove('active');
        document.body.classList.remove('modal-open');
        bodyEl.innerHTML = '';
    }

    function showLoading() {
        bodyEl.innerHTML = '';
        var wrap = el('div', 'product-modal-loading');
        wrap.appendChild(el('i', 'fas fa-spinner fa-spin'));
        wrap.appendChild(el('p', '', 'Đang tải...'));
        bodyEl.appendChild(wrap);
    }

    function showError(msg) {
        bodyEl.innerHTML = '';
        var wrap = el('div', 'product-modal-error');
        wrap.appendChild(el('i', 'fas fa-leaf'));
        wrap.appendChild(el('p', '', msg || 'Không tìm thấy sản phẩm.'));
        bodyEl.appendChild(wrap);
    }

    function render(p) {
        bodyEl.innerHTML = '';

        var badge = '';
        if (p.badge_label) {
            badge = '<div class="product-modal-badge ' + p.badge_class + '">' + p.badge_label + '</div>';
        }
        var imgSrc = p.image_url || '/img/placeholder.svg';
        var stockText = p.stock_quantity > 0
            ? 'Còn hàng (' + p.stock_quantity + ')'
            : 'Hết hàng';
        var stars = '';
        for (var i = 0; i < 5; i++) {
            stars += i < Math.round(Number(p.rating_avg) || 0) ? '★' : '☆';
        }
        var priceBlock = '';
        if (Number(p.old_price) > 0 && Number(p.old_price) > Number(p.price)) {
            priceBlock = '<span class="current">' + formatPrice(p.price) + 'đ</span>'
                + '<span class="old">' + formatPrice(p.old_price) + 'đ</span>';
        } else {
            priceBlock = '<span class="current">' + formatPrice(p.price) + 'đ</span>';
        }
        if (p.discount > 0) {
            priceBlock += '<span class="discount">-' + p.discount + '%</span>';
        }

        var grid = el('div', 'product-modal-grid');

        var imgCol = el('div', 'product-modal-images');
        imgCol.innerHTML = badge + '<img src="' + imgSrc + '" alt="">';
        grid.appendChild(imgCol);

        var infoCol = el('div', 'product-modal-info');

        var name = el('h2', 'product-modal-name', p.name);
        infoCol.appendChild(name);

        var rating = el('div', 'product-modal-rating');
        var starsSpan = el('span', 'stars', stars);
        rating.appendChild(starsSpan);
        rating.appendChild(el('span', 'review-count', '(' + p.review_count + ' đánh giá)'));
        infoCol.appendChild(rating);

        var priceDiv = el('div', 'product-modal-price');
        priceDiv.innerHTML = priceBlock;
        infoCol.appendChild(priceDiv);

        var meta = el('div', 'product-modal-meta');
        var metaItems = [
            ['fa-tag', 'Mã sản phẩm: ' + (p.code || '---')],
            ['fa-folder', 'Danh mục: ' + (p.category_name || '---')],
            ['fa-box', 'Tình trạng: ' + stockText],
            ['fa-eye', 'Lượt xem: ' + p.views],
        ];
        metaItems.forEach(function (m) {
            var item = el('div', 'product-modal-meta-item');
            item.appendChild(el('i', 'fas ' + m[0]));
            item.appendChild(el('span', '', m[1]));
            meta.appendChild(item);
        });
        infoCol.appendChild(meta);

        var desc = el('div', 'product-modal-description');
        desc.appendChild(el('h4', '', 'Mô tả sản phẩm'));
        desc.appendChild(el('p', '', p.description || 'Chưa có mô tả cho sản phẩm này.'));
        infoCol.appendChild(desc);

        var policies = el('div', 'product-modal-policies');
        var policyItems = [
            ['fa-shield-alt', 'Chính hãng 100%'],
            ['fa-truck', 'Miễn phí vận chuyển'],
            ['fa-undo', 'Đổi trả trong 7 ngày'],
        ];
        policyItems.forEach(function (m) {
            var item = el('div', 'policy-item');
            item.appendChild(el('i', 'fas ' + m[0]));
            item.appendChild(el('span', '', m[1]));
            policies.appendChild(item);
        });
        infoCol.appendChild(policies);

        var footer = el('div', 'product-modal-footer');
        var detailLink = el('a', 'btn-view-full', 'Xem chi tiết đầy đủ');
        detailLink.href = 'productdetal.php?id=' + p.id;
        footer.appendChild(detailLink);
        infoCol.appendChild(footer);

        grid.appendChild(infoCol);
        bodyEl.appendChild(grid);
    }

    function load(id) {
        showLoading();
        open();
        var sep = endpoint.indexOf('?') !== -1 ? '&' : '?';
        fetch(endpoint + sep + 'id=' + encodeURIComponent(id))
            .then(function (res) {
                if (!res.ok) throw new Error('not found');
                return res.json();
            })
            .then(function (data) {
                render(data);
            })
            .catch(function () {
                showError();
            });
    }

    document.addEventListener('click', function (e) {
        var card = e.target.closest('.product-item[data-id]');
        if (!card) return;
        if (e.target.closest('.btn-add-cart')) return; // giữ hành vi điều hướng bình thường
        e.preventDefault();
        load(card.getAttribute('data-id'));
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', close);
    }
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) close();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('active')) {
            close();
        }
    });
}