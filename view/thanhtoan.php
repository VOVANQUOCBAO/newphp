<?php
$h = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$items = is_array($giohang['items'] ?? null) ? $giohang['items'] : [];
$shipping = in_array($user['hang'] ?? 'Silver', ['Gold', 'Diamond'], true) ? 0 : 30000;
$selectedMethod = $_POST['phuong_thuc'] ?? (!empty($bankAvailable) ? 'bank' : 'cod');
?>
<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Thanh toán</h1>
        <a href="index.php?act=giohang" class="btn btn-outline-secondary">Quay lại giỏ hàng</a>
    </div>
    <?php if ($loi !== null): ?><div class="alert alert-danger"><?=$h($loi)?></div><?php endif; ?>
    <?php if (!empty($pendingPayPal)): ?><div class="alert alert-info">Bạn có một đơn PayPal đang chờ xử lý. <a href="index.php?act=paypal&amp;id=<?=(int)$pendingPayPal?>">Xem thanh toán đơn #<?=(int)$pendingPayPal?></a>.</div><?php endif; ?>
    <?php if ($ok ?? false): ?>
        <section class="card p-4 text-center">
            <h2 class="h4 text-success">Đặt hàng thành công</h2>
            <p>Mã đơn: <strong>#<?=$h($id_don)?></strong></p>
            <p>Tổng thanh toán: <strong><?=number_format((float)($donhang['tong_tien'] ?? 0), 0, ',', '.')?>đ</strong></p>
            <p>Bạn có thể xem thông tin và theo dõi đơn trong lịch sử mua hàng.</p>
            <div class="d-flex justify-content-center gap-2">
                <a class="btn btn-primary" href="index.php?act=theodoidonhang&amp;id=<?=$h($id_don)?>">Theo dõi đơn hàng</a>
                <a class="btn btn-outline-primary" href="index.php?act=trangchu">Tiếp tục mua sắm</a>
            </div>
        </section>
    <?php elseif ($items === []): ?>
        <div class="alert alert-info">Giỏ hàng đang trống. <a href="index.php?act=trangchu">Quay lại cửa hàng</a>.</div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-7">
                <section class="card"><div class="card-header fw-bold">Thông tin nhận hàng</div><div class="card-body">
                    <p class="mb-3">Khách hàng: <strong><?=$h($user['ho_ten'] ?? $user['hoten'] ?? '')?></strong> · <?=$h($user['email'] ?? '')?></p>
                    <form action="index.php?act=thanhtoan" method="post">
                        <input type="hidden" name="_order_key" value="<?=$h($codCheckoutKey ?? '')?>">
                        <input type="hidden" name="_checkout_csrf" value="<?=$h($checkoutToken ?? '')?>">
                        <div class="mb-3"><label class="form-label" for="shippingAddress">Địa chỉ giao hàng</label><textarea id="shippingAddress" class="form-control" name="dia_chi" rows="3" maxlength="255" required><?=$h($_POST['dia_chi'] ?? '')?></textarea></div>
                        <div class="mb-3"><label class="form-label" for="shippingPhone">Số điện thoại</label><input id="shippingPhone" class="form-control" type="tel" name="sdt" value="<?=$h($_POST['sdt'] ?? $user['sdt'] ?? '')?>" pattern="\+?[0-9][0-9 .()\-]{7,18}" required></div>
                        <div class="mb-3"><label class="form-label" for="voucherCode">Mã voucher (không bắt buộc)</label><input id="voucherCode" class="form-control text-uppercase" name="ma_voucher" value="<?=$h($_POST['ma_voucher'] ?? '')?>" maxlength="50"></div>
                        <button type="submit" name="intent" value="voucher" formnovalidate class="btn btn-outline-primary btn-sm mb-3">Áp dụng voucher / xem tổng tiền</button>
                        <?php if (trim((string)($_POST['ma_voucher'] ?? ''))!==''): ?><button type="submit" name="intent" value="remove_voucher" formnovalidate class="btn btn-link btn-sm mb-3">Bỏ voucher</button><?php endif; ?>
                        <div class="mb-3"><label class="form-label" for="paymentMethod">Phương thức thanh toán</label>
                            <select id="paymentMethod" name="phuong_thuc" class="form-select">
                                <?php if (!empty($bankAvailable)): ?><option value="bank" <?=$selectedMethod==='bank' ? 'selected' : ''?>>Chuyển khoản QR — cửa hàng xác nhận</option><?php endif; ?>
                                <option value="cod" <?=$selectedMethod==='cod' ? 'selected' : ''?>>Tiền mặt khi nhận hàng (COD)</option>
                                <?php if (!empty($paypalAvailable)): ?><option value="paypal" <?=$selectedMethod==='paypal' ? 'selected' : ''?>>PayPal — thanh toán bằng USD</option><?php endif; ?>
                            </select>
                            <p class="form-text mb-0">Chọn QR để quét chuyển khoản bằng ứng dụng ngân hàng; cửa hàng sẽ kiểm tra tiền nhận. PayPal hiển thị số tiền USD ở bước tiếp theo.</p>
                        </div>
                        <button class="btn btn-primary w-100"><?=!empty($paypalAvailable) ? 'Tiếp tục đặt hàng' : 'Đặt hàng'?></button>
                    </form>
                </div></section>
            </div>
            <div class="col-lg-5">
                <section class="card"><div class="card-header fw-bold">Đơn hàng của bạn</div><ul class="list-group list-group-flush">
                    <?php foreach ($items as $item): ?><li class="list-group-item d-flex justify-content-between gap-2"><span><?=$h($item['ten_sp'])?> × <?=$h($item['so_luong'])?></span><strong><?=number_format((float)$item['thanh_tien'], 0, ',', '.')?>đ</strong></li><?php endforeach; ?>
                </ul><div class="card-body">
                    <div class="d-flex justify-content-between"><span>Tạm tính</span><span><?=number_format((float)($giohang['tong'] ?? 0), 0, ',', '.')?>đ</span></div>
                    <div class="d-flex justify-content-between"><span>Phí giao hàng dự kiến</span><span><?=money($voucherQuote['phi_ship'] ?? $shipping)?></span></div>
                    <?php if (!empty($voucherQuote['ok'])): ?><?php if (trim((string)($_POST['ma_voucher'] ?? ''))!==''): ?><p class="text-success mt-2 mb-1">Đã áp dụng <?=$h(strtoupper(trim($_POST['ma_voucher'])))?></p><?php endif; ?><div class="d-flex justify-content-between"><span>Giảm voucher</span><span><?=($voucherQuote['giam']>0 ? '-' : '')?><?=money($voucherQuote['giam'])?></span></div><div class="d-flex justify-content-between"><strong>Tổng dự kiến</strong><strong><?=money($voucherQuote['tong'])?></strong></div><?php else: ?><p class="text-danger mt-2" role="alert"><?=$h($voucherQuote['loi'] ?? '')?></p><div class="d-flex justify-content-between"><strong>Tổng chưa áp dụng voucher</strong><strong><?=money(($giohang['tong'] ?? 0)+$shipping)?></strong></div><?php endif; ?>
                    <small class="text-muted">Giá và điều kiện voucher được kiểm tra lại khi đặt đơn. Hạng hiện tại: <?=$h($user['hang'])?>.</small>
                    <?php if (!empty($voucherQuote['ok']) && !empty($voucherQuote['doi_tuong'])): ?><p class="small mt-2 mb-1">Phạm vi: <strong><?=$h($voucherQuote['doi_tuong'])?></strong><br>Tiền hàng được áp dụng: <?=money($voucherQuote['tien_ap_dung'])?></p><?php endif; ?>
                    <?php if (!empty($availableVouchers)): ?><details class="mt-3"><summary>Voucher còn hiệu lực</summary><?php foreach ($availableVouchers as $vc): ?><div class="small mt-3"><strong><?=$h($vc['ma_code'])?></strong> · <?=$vc['loai']==='mienship' ? 'Miễn phí giao hàng' : ('Giảm '.($vc['loai']==='phantram' ? $h($vc['gia_tri']).'%' : money($vc['gia_tri'])))?><br><?=$h($vc['hang_ap_dung'] ?: 'Silver')?> trở lên · tiền hàng áp dụng từ <?=money($vc['don_toi_thieu'])?> · đến <?=$h($vc['ngay_kt'])?><br><?=$h($vc['pham_vi_label'])?><br><span class="<?=$vc['du_dieu_kien'] ? 'text-success' : 'text-muted'?>"><?=$vc['du_dieu_kien'] ? 'Đủ điều kiện với đơn này' : $h($vc['ly_do'])?></span></div><?php endforeach; ?></details><?php endif; ?>
                </div></section>
            </div>
        </div>
    <?php endif; ?>
</main>
