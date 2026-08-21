<?php
/**
 * Footer dùng chung cho trang công khai
 * Khai báo trước: $extraScript
 */
$extraScript = $extraScript ?? '';
?>
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-col footer-about">
                <h2 class="footer-logo">TRÀ<span>CHUYỆN</span></h2>
                <p class="footer-slogan">Ấm tử sa Nghi Hưng & trà cụ cao cấp chính hãng</p>
                <p class="footer-desc">
                    Chuyên ấm tử sa Nghi Hưng chính hãng và trà cụ cao cấp, tuyển chọn kỹ lưỡng để nâng tầm trải nghiệm thưởng trà của bạn.
                </p>
                <div class="social-icons">
                    <a href="#" class="social-icon facebook" title="Facebook" aria-label="Facebook">
                        <svg viewBox="0 0 320 512" fill="currentColor"><path d="M279.14 288l14.22-92.66h-88.91v-60.13c0-25.35 12.42-50.06 52.24-50.06h40.42V6.26S260.43 0 225.36 0c-73.22 0-121.08 44.38-121.08 124.72v70.62H22.89V288h81.39v224h100.17V288z"/></svg>
                    </a>
                    <a href="#" class="social-icon tiktok" title="TikTok" aria-label="TikTok">
                        <svg viewBox="0 0 448 512" fill="currentColor"><path d="M448,209.91a210.06,210.06,0,0,1-122.77-39.25V349.38A162.55,162.55,0,1,1,185,188.31V278.2a74.62,74.62,0,1,0,52.23,71.18V0l88,0a121.18,121.18,0,0,0,1.86,22.17h0A122.18,122.18,0,0,0,381,102.39a121.43,121.43,0,0,0,67,20.14Z"/></svg>
                    </a>
                    <a href="#" class="social-icon zalo" title="Zalo" aria-label="Zalo">
                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.49 10.04V5.5h1.53v4.54h-1.53zm-3.34 0h1.53V8.08c0-1.05-.3-1.9-1.04-2.47-.58-.44-1.35-.66-2.32-.66H4.5v4.06H6.02V6.62h.77c.66 0 1.1.4 1.1 1.22v2.2h1.26zm6.58-3.66v1.23h2.63v1.15h-2.63v2.3h-1.53V5.5h4.17v.88h-2.64zm-8.4 6.54l5.66-5.9h-5.6V7.9h3.35l-5.66 5.9h5.6v-.88H7.33z"/></svg>
                    </a>
                </div>
            </div>

            <div class="footer-col footer-contact">
                <h3 class="footer-heading">TƯ VẤN & ĐẶT HÀNG</h3>
                <a href="tel:0889018999" class="footer-phone">
                    <i class="fas fa-phone-alt"></i> 0889.018.999
                </a>
            </div>

            <div class="footer-col footer-policy">
                <h3 class="footer-heading">QUY ĐỊNH & CHÍNH SÁCH</h3>
                <ul class="footer-links">
                    <li><a href="#">Điều khoản & quy định</a></li>
                    <li><a href="#">Chính sách bảo mật</a></li>
                    <li><a href="#">Phương thức thanh toán</a></li>
                    <li><a href="#">Vận chuyển & kiểm hàng</a></li>
                    <li><a href="#">Bảo hành & đổi trả</a></li>
                </ul>
            </div>

            <div class="footer-col footer-payment-contact">
                <div class="payment-methods">
                    <h3 class="footer-heading">THANH TOÁN</h3>
                    <div class="payment-icons">
                        <span class="payment-badge">COD</span>
                        <span class="payment-badge">Chuyển khoản</span>
                        <span class="payment-badge">VietQR</span>
                    </div>
                </div>
                <div class="contact-info">
                    <h3 class="footer-heading">LIÊN HỆ</h3>
                    <p class="company-name">CÔNG TY TNHH TRÀ CHUYỆN</p>
                    <p class="company-tax">Mã số thuế: </p>
                    <p class="company-address">
                        <i class="fas fa-map-marker-alt"></i> Trụ sở chính: Lô 28.100 Khu đô thị phía Tây, P. Từ Minh, TP Hải Phòng
                    </p>
                    <p class="company-address">
                        <i class="fas fa-store"></i> Cửa hàng: Số 5 ngõ 50 Lê Hiến, P. Từ Minh, TP Hải Phòng
                    </p>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="footer-zalo">
                <a href="#" class="zalo-link">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/1a/Zalo_Logo.png/600px-Zalo_Logo.png" alt="Zalo" style="height: 35px;">
                </a>
            </div>
            <div class="footer-copyright">
                Bản quyền © 2026 TraChuyen.com
            </div>
        </div>
    </div>
</footer>

<?= $extraScript ?>

<script>
    // Ảnh không tải được (VD via.placeholder.com lỗi) -> thay bằng placeholder cục bộ
    document.addEventListener('error', function (e) {
        if (e.target && e.target.tagName === 'IMG') {
            e.target.onerror = null;
            e.target.src = '/img/placeholder.svg';
        }
    }, true);
</script>

<!-- ====== POPUP CHI TIẾT SẢN PHẨM ====== -->
<div class="modal-overlay" id="productModalOverlay">
    <div class="modal" id="productModal" role="dialog" aria-modal="true" aria-label="Chi tiết sản phẩm">
        <button type="button" class="modal-close" data-modal-close title="Đóng"><i class="fas fa-times"></i></button>
        <div class="modal-body" id="productModalBody"></div>
    </div>
</div>

<script src="<?= url('/js/smart-search.js') ?>"></script>
<script>
(function () {
    var input = document.getElementById('searchInput');
    var results = document.getElementById('searchResults');
    if (!input || !results) return;
    initSmartSearch({
        input: input,
        resultsEl: results,
        endpoint: '<?= url('/api/search.php') ?>',
        minChars: 2,
        onPick: function (item) {
            window.location.href = '<?= url('/productdetal.php') ?>?id=' + item.id;
        }
    });
})();
</script>
<script src="<?= url('/js/product-modal.js') ?>"></script>
<script>
    initProductModal({
        overlay: document.getElementById('productModalOverlay'),
        modal: document.getElementById('productModal'),
        body: document.getElementById('productModalBody'),
        endpoint: '<?= url('/api/product.php') ?>'
    });
</script>
</body>
</html>