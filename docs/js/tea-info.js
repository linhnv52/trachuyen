(function () {
    var navBtns = Array.prototype.slice.call(document.querySelectorAll('.tea-info-nav .tea-nav-btn'));
    var groupHeads = Array.prototype.slice.call(document.querySelectorAll('.tea-info-nav .tea-info-nav__group'));
    var panel = document.querySelector('.tea-info-panel');

    function setupMobileGroups() {
        var mobile = window.matchMedia('(max-width: 768px)').matches;
        groupHeads.forEach(function (head, index) {
            head.setAttribute('role', 'button');
            head.setAttribute('tabindex', '0');
            head.setAttribute('aria-expanded', mobile && index === 0 ? 'true' : (mobile ? 'false' : 'true'));
            var open = !mobile || index === 0;
            var next = head.nextElementSibling;
            while (next && !next.classList.contains('tea-info-nav__group')) {
                if (next.classList.contains('tea-nav-btn')) next.hidden = !open;
                next = next.nextElementSibling;
            }
        });
    }

    groupHeads.forEach(function (head) {
        function toggleGroup() {
            if (!window.matchMedia('(max-width: 768px)').matches) return;
            var open = head.getAttribute('aria-expanded') !== 'true';
            head.setAttribute('aria-expanded', open ? 'true' : 'false');
            var next = head.nextElementSibling;
            while (next && !next.classList.contains('tea-info-nav__group')) {
                if (next.classList.contains('tea-nav-btn')) next.hidden = !open;
                next = next.nextElementSibling;
            }
        }
        head.addEventListener('click', toggleGroup);
        head.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); toggleGroup(); }
        });
    });
    setupMobileGroups();
    window.addEventListener('resize', setupMobileGroups);

    function esc(s) {
        return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function placeArticlePanel(btn) {
        var split = document.querySelector('.tea-info-split');
        var nav = document.querySelector('.tea-info-nav');
        if (window.matchMedia('(max-width: 768px)').matches) {
            nav.insertBefore(panel, btn.nextElementSibling);
            panel.classList.add('is-inline-mobile');
        } else {
            split.appendChild(panel);
            panel.classList.remove('is-inline-mobile');
        }
    }

    function renderArticle(btn) {
        var title = btn.textContent.trim();
        var nham = btn.getAttribute('data-nham') === '1';
        var body = btn.getAttribute('data-body') || '';

        navBtns.forEach(function (b) { b.classList.toggle('active', b === btn); });
        placeArticlePanel(btn);

        var html = '<article class="tea-art">';
        if (nham) {
            html += '<h3 class="tea-art__title">' + esc(title) + '</h3>';
            var lines = body.split(/\n{2,}/).filter(function (l) { return l.trim() !== ''; });
            if (lines.length) {
                // Dòng đầu là mô tả, các dòng sau là bước "Tiêu đề:Nội dung"
                html += '<p class="tea-art__desc">' + esc(lines.shift()) + '</p>';
                html += '<div class="tea-art__steps">';
                lines.forEach(function (l, i) {
                    var pos = l.indexOf(':');
                    var stepTitle, stepBody;
                    if (pos > -1) {
                        stepTitle = l.slice(0, pos);
                        stepBody = l.slice(pos + 1);
                    } else {
                        stepTitle = 'Bước ' + (i + 1);
                        stepBody = l;
                    }
                    html += '<div class="tea-art__step">'
                        + '<span class="tea-art__num">' + (i + 1) + '</span>'
                        + '<div><h4>' + esc(stepTitle) + '</h4>'
                        + '<p>' + esc(stepBody.trim()) + '</p></div></div>';
                });
                html += '</div>';
            }
        } else {
            html += '<h3 class="tea-art__title">' + esc(title) + '</h3>';
            var paras = body.split(/\n{2,}/).filter(function (l) { return l.trim() !== ''; });
            if (paras.length) {
                html += paras.map(function (p) { return '<p class="tea-art__desc">' + esc(p) + '</p>'; }).join('');
            }
        }
        html += '</article>';
        panel.innerHTML = html;
    }

    navBtns.forEach(function (b) {
        b.addEventListener('click', function () { renderArticle(b); });
    });

    window.addEventListener('resize', function () {
        var activeBtn = navBtns.find(function (btn) { return btn.classList.contains('active'); });
        if (activeBtn) placeArticlePanel(activeBtn);
    });

    // Mặc định hiện bài đầu tiên
    if (navBtns.length) {
        renderArticle(navBtns[0]);
    }
})();