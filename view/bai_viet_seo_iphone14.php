<?php
$seoOriginalPrice = (float)($seoProduct['gia_goc'] ?? $seoProduct['gia']);
$seoCurrentPrice = (float)($seoProduct['gia_km'] ?? $seoProduct['gia']);
$seoStock = (int)($seoProduct['so_luong_ton'] ?? 0);
$seoDiscount = $seoOriginalPrice > $seoCurrentPrice
    ? (int)round(($seoOriginalPrice - $seoCurrentPrice) / $seoOriginalPrice * 100) : 0;
?>
<section class="container my-4" aria-labelledby="seo-iphone14-title">
    <article class="bpstore-seo-article highlight-section">
        <header class="section-title-wrap">
            <div>
                <p class="bpstore-seo-eyebrow mb-2">Góc tư vấn · Điện thoại iPhone</p>
                <h2 id="seo-iphone14-title" class="section-title">iPhone 14 128GB Xanh dương</h2>
            </div>
            <span class="bpstore-seo-label">Thông tin sản phẩm</span>
        </header>

        <div class="bpstore-seo-lead">
            <div class="bpstore-seo-lead-copy">
                <h3>Thiết kế thanh lịch, hiệu năng bền bỉ cho nhu cầu mỗi ngày</h3>
                <p>iPhone 14 128GB · Xanh dương kết hợp màn hình OLED 6,1 inch, chip Apple A15 Bionic và hệ thống camera kép 12MP. Phiên bản 128GB phù hợp để lưu ảnh, video và ứng dụng thường dùng trong một thiết kế gọn nhẹ.</p>
                <div class="bpstore-seo-buybox">
                    <div class="bpstore-seo-price">
                        <?php if ($seoDiscount > 0): ?>
                            <span class="bpstore-seo-old-price">Giá gốc <del><?=view_helper::money($seoOriginalPrice)?></del></span>
                            <span class="bpstore-seo-current-price">Giá ưu đãi <strong><?=view_helper::money($seoCurrentPrice)?></strong></span>
                            <span class="bpstore-seo-discount">Giảm <?=$seoDiscount?>%</span>
                        <?php else: ?>
                            <span class="bpstore-seo-current-price">Giá bán <strong><?=view_helper::money($seoCurrentPrice)?></strong></span>
                        <?php endif; ?>
                    </div>
                    <p class="bpstore-seo-stock <?=$seoStock > 0 ? 'is-available' : 'is-unavailable'?>">
                        <i class="fas <?=$seoStock > 0 ? 'fa-circle-check' : 'fa-circle-xmark'?>" aria-hidden="true"></i>
                        <?=$seoStock > 0 ? 'Còn hàng' : 'Tạm hết hàng'?>
                    </p>
                    <a class="btn btn-primary" href="index.php?act=chitiet&amp;id=<?=(int)$seoProduct['id']?>">Xem sản phẩm <i class="fas fa-arrow-right ms-2" aria-hidden="true"></i></a>
                </div>
            </div>
            <a class="bpstore-seo-image" href="index.php?act=chitiet&amp;id=<?=(int)$seoProduct['id']?>" aria-label="Xem <?=$e($seoProduct['ten_sp'])?>">
                <img src="<?=$e(view_helper::productImage($seoProduct['hinh'] ?? null))?>" alt="iPhone 14 128GB Xanh dương tại BP Store" loading="lazy">
            </a>
        </div>

        <div class="bpstore-seo-content">
            <details class="advice-panel" open>
                <summary>Điểm nổi bật</summary>
                <div class="advice-panel-body">
                    <ul>
                        <li>Sắc xanh nhẹ nhàng, dễ phối</li>
                        <li>Super Retina XDR 6,1 inch</li>
                        <li>Chip A15 Bionic · 128GB</li>
                        <li>Camera kép 12MP</li>
                        <li>Pin tối đa 20 giờ · MagSafe · IP68</li>
                    </ul>
                    <details class="advice-long-copy">
                        <summary>Thông tin chi tiết</summary>
            <div class="bpstore-seo-feature-grid">
                <section>
                    <h4>Sắc xanh nhẹ nhàng, dễ phối</h4>
                    <p>Tông Xanh dương trẻ trung đi cùng khung viền nhôm và mặt lưng kính. Mặt trước dùng Ceramic Shield, tạo nên tổng thể gọn gàng để mang theo và sử dụng hằng ngày.</p>
                </section>
                <section>
                    <h4>Màn hình Super Retina XDR 6,1 inch</h4>
                    <p>Tấm nền OLED độ phân giải 2532 × 1170 hiển thị chi tiết, màu sắc rõ nét và hỗ trợ HDR với độ sáng tối đa 1.200 nit.</p>
                </section>
                <section>
                    <h4>Chip A15 Bionic và bộ nhớ 128GB</h4>
                    <p>Chip A15 Bionic với GPU 5 lõi xử lý mượt các tác vụ thường ngày, ứng dụng và nhiều tựa game. Bộ nhớ trong 128GB dành cho ảnh, video và dữ liệu cá nhân.</p>
                </section>
                <section>
                    <h4>Camera kép 12MP, quay video ổn định</h4>
                    <p>Camera chính và camera siêu rộng 12MP kết hợp Photonic Engine hỗ trợ chụp trong điều kiện thiếu sáng. Action mode giúp video cầm tay ổn định hơn; Cinematic mode hỗ trợ quay 4K.</p>
                </section>
                <section>
                    <h4>Pin và tính năng an toàn</h4>
                    <p>Thời lượng xem video tối đa theo công bố của Apple là 20 giờ. Máy hỗ trợ Phát hiện va chạm, IP68 và sạc không dây MagSafe.</p>
                </section>
            </div>

                    </details>
                </div>
            </details>
            <details class="advice-panel">
                <summary>Thông số kỹ thuật iPhone 14 128GB</summary>
                <div class="advice-panel-body">
            <div class="table-responsive">
                <table class="table table-sm bpstore-seo-specs mb-0">
                    <tbody>
                        <tr><th scope="row">Màn hình</th><td>OLED 6,1 inch, Super Retina XDR, 2532 × 1170 pixel</td></tr>
                        <tr><th scope="row">Chip xử lý</th><td>Apple A15 Bionic, CPU 6 lõi, GPU 5 lõi</td></tr>
                        <tr><th scope="row">Bộ nhớ trong</th><td>128GB</td></tr>
                        <tr><th scope="row">Camera sau</th><td>Camera chính 12MP và camera siêu rộng 12MP</td></tr>
                        <tr><th scope="row">Camera trước</th><td>TrueDepth 12MP, tự động lấy nét</td></tr>
                        <tr><th scope="row">Kết nối</th><td>5G, MagSafe, sạc nhanh có dây</td></tr>
                        <tr><th scope="row">Kháng nước, bụi</th><td>IP68</td></tr>
                        <tr><th scope="row">Màu sắc</th><td>Xanh dương</td></tr>
                    </tbody>
                </table>
            </div>

                </div>
            </details>
            <?php if (!empty($seoAccessoryProducts)): ?>
            <details class="advice-panel" open>
                <summary>Phụ kiện phù hợp</summary>
                <div class="advice-panel-body">
                <div class="bpstore-seo-cross-sell">
                    <div class="row g-3">
                        <?php foreach ($seoAccessoryProducts as $accessory): ?>
                            <div class="col-12 col-sm-6">
                                <a class="bpstore-seo-accessory" href="index.php?act=chitiet&amp;id=<?=(int)$accessory['id']?>">
                                    <span><?=$e($accessory['ten_sp'])?></span>
                                    <span class="bpstore-seo-accessory-price">
                                        <?php if ((float)($accessory['gia_goc'] ?? $accessory['gia']) > (float)($accessory['gia_km'] ?? $accessory['gia'])): ?>
                                            <del>Giá gốc <?=view_helper::money($accessory['gia_goc'])?></del>
                                            <strong>Giá ưu đãi <?=view_helper::money($accessory['gia_km'])?></strong>
                                        <?php else: ?>
                                            <strong>Giá bán <?=view_helper::money($accessory['gia_km'] ?? $accessory['gia'])?></strong>
                                        <?php endif; ?>
                                    </span>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                </div>
            </details>
            <?php endif; ?>
        </div>
    </article>
</section>
