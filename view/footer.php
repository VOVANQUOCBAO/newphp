<footer class="bpstore-footer mt-auto">
    <div class="container bpstore-footer-main">
        <section class="bpstore-footer-about">
            <a class="bpstore-footer-brand" href="index.php?act=trangchu">
                <img src="<?=$base_url?>/image/bpstore-logo.png" alt="BP Store" width="82" height="64">
                <span><strong>BP Store</strong><small>Điện thoại & phụ kiện</small></span>
            </a>
            <p>Khám phá điện thoại và phụ kiện, xem thông tin sản phẩm và đặt hàng trực tuyến.</p>
            <a class="bpstore-footer-address" href="https://www.google.com/maps/search/?api=1&amp;query=92+B%C3%B9i+T%C6%B0+To%C3%A0n%2C+B%C3%ACnh+T%C3%A2n" target="_blank" rel="noopener noreferrer">
                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                <span><strong>Địa chỉ cửa hàng</strong><br>92 Bùi Tư Toàn, Bình Tân</span>
                <i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i>
            </a>
            <a class="bpstore-footer-contact" href="tel:+84338008284">
                <i class="fas fa-phone" aria-hidden="true"></i>
                <span><strong>Điện thoại</strong><br>0338 008 284</span>
            </a>
            <a class="bpstore-footer-contact" href="mailto:qbaor39@gmail.com">
                <i class="fas fa-envelope" aria-hidden="true"></i>
                <span><strong>Email</strong><br>qbaor39@gmail.com</span>
            </a>
        </section>
        <nav class="bpstore-footer-column" aria-label="Mua sắm">
            <h2>Mua sắm</h2>
            <a href="index.php?act=trangchu"><i class="fas fa-chevron-right" aria-hidden="true"></i>Sản phẩm</a>
            <a href="index.php?act=giohang"><i class="fas fa-chevron-right" aria-hidden="true"></i>Giỏ hàng</a>
            <a href="index.php?act=yeuthich"><i class="fas fa-chevron-right" aria-hidden="true"></i>Sản phẩm yêu thích</a>
        </nav>
        <nav class="bpstore-footer-column" aria-label="Đơn hàng và hỗ trợ">
            <h2>Đơn hàng & hỗ trợ</h2>
            <a href="index.php?act=lichsumuahang"><i class="fas fa-chevron-right" aria-hidden="true"></i>Lịch sử đơn hàng</a>
            <a href="index.php?act=theodoidonhang"><i class="fas fa-chevron-right" aria-hidden="true"></i>Theo dõi đơn hàng</a>
            <a href="index.php?act=hopthu"><i class="fas fa-chevron-right" aria-hidden="true"></i>Hộp thư</a>
            <a href="index.php?act=chinhsach"><i class="fas fa-chevron-right" aria-hidden="true"></i>Chính sách mua hàng</a>
        </nav>
        <section class="bpstore-footer-column bpstore-footer-payment">
            <h2>Thanh toán</h2>
            <div class="bpstore-payment-badges">
                <span><i class="fas fa-qrcode" aria-hidden="true"></i>Chuyển khoản QR</span>
                <span><i class="fab fa-paypal" aria-hidden="true"></i>PayPal</span>
                <span><i class="fas fa-truck" aria-hidden="true"></i>COD</span>
            </div>
            <h3>BP Store</h3>
            <p>Chọn phương thức thanh toán khi hoàn tất đặt hàng.</p>
        </section>
    </div>
    <div class="bpstore-footer-bottom">
        <div class="container">
            <span>© <?=date('Y')?> BP Store</span>
            <nav aria-label="Liên kết nhanh cuối trang">
                <a href="index.php?act=trangchu">Sản phẩm</a>
                <a href="index.php?act=lichsumuahang">Đơn hàng</a>
                <a href="index.php?act=chinhsach">Chính sách</a>
            </nav>
        </div>
    </div>
</footer>

<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1065;">
    <div id="cartToast" class="toast align-items-center text-white bg-success border-0 shadow-lg" role="status" aria-live="polite" aria-atomic="true" data-bs-delay="5000">
        <div class="d-flex align-items-center p-2">
            <div class="fs-3 me-2 ps-2 text-white"><i class="fas fa-check-circle"></i></div>
            <div class="toast-body fw-semibold py-2"><span id="toastMessage">Đã thêm sản phẩm vào giỏ hàng.</span></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Đóng"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    restoreUserPreferences();
    const carouselElement = document.getElementById('bpStorePromoCarousel');
    if (carouselElement && window.bootstrap && window.bootstrap.Carousel) {
        const carousel = window.bootstrap.Carousel.getOrCreateInstance(carouselElement, {
            interval: 3000,
            pause: false,
            wrap: true,
            touch: true
        });
        carousel.cycle();
    }
});

function changeThemeSeason(season) {
    document.body.classList.remove('theme-spring', 'theme-summer', 'theme-autumn', 'theme-winter');
    if (season && season !== 'default') document.body.classList.add(season);
    localStorage.setItem('selected_season', season);
}

function changeFontFamily(font) {
    document.body.classList.remove('font-segoe', 'font-bevietnam', 'font-roboto', 'font-montserrat');
    if (font) document.body.classList.add(font);
    localStorage.setItem('selected_font', font);
}

function changeBackgroundMode(bg) {
    document.body.classList.remove('bg-light-gray', 'bg-white-pure', 'bg-soft-blue');
    if (bg) document.body.classList.add(bg);
    localStorage.setItem('selected_bg', bg);
}

function restoreUserPreferences() {
    const savedFont = localStorage.getItem('selected_font');
    if (savedFont) {
        changeFontFamily(savedFont);
        const select = document.getElementById('fontSelect');
        if (select) select.value = savedFont;
    }

    const savedBg = localStorage.getItem('selected_bg');
    if (savedBg) {
        changeBackgroundMode(savedBg);
        const select = document.getElementById('bgSelect');
        if (select) select.value = savedBg;
    }
}
</script>
</body>
</html>
