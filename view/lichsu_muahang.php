<?php
$e = static fn(mixed $value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$orders = is_array($ds_donhang ?? null) ? $ds_donhang : [];
?>
<main class="container my-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h2 fw-bold text-primary mb-1"><i class="fas fa-history me-2"></i>Lịch sử mua hàng</h1>
            <p class="text-muted mb-0">Theo dõi giao hàng, hủy đơn và yêu cầu trả hàng tại đây.</p>
        </div>
        <a class="btn btn-outline-primary rounded-pill" href="index.php?act=theodoidonhang">Tra cứu đơn hàng</a>
    </div>

    <?php if (!empty($thongBao)): ?><div class="alert alert-success"><?=$e($thongBao)?></div><?php endif; ?>
    <?php if (!empty($loi)): ?><div class="alert alert-danger"><?=$e($loi)?></div><?php endif; ?>

    <?php if ($orders === []): ?>
        <section class="card border-0 shadow-sm p-5 text-center">
            <h2 class="h5">Bạn chưa có đơn hàng nào</h2>
            <p class="text-muted">Đơn hàng mới sẽ xuất hiện ở đây sau khi đặt hàng thành công.</p>
            <a class="btn btn-primary mx-auto" href="index.php?act=trangchu">Tiếp tục mua sắm</a>
        </section>
    <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($orders as $order): ?>
                <?php
                $orderId = (int) $order['id'];
                $status = (string) $order['trang_thai'];
                $items = is_array($order['chitiet'] ?? null) ? $order['chitiet'] : [];
                ?>
                <article class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div><strong>Đơn hàng #<?=$orderId?></strong> <span class="text-muted">· <?=$e($order['ngay_dat'])?></span></div>
                        <span class="badge text-bg-primary"><?=$e($status)?></span>
                    </div>
                    <div class="card-body">
                        <p class="small fw-semibold"><?=$e((!empty($order['thanhtoan_qr']) ? thanhtoan_qr::label($order['thanhtoan_qr']) : thanhtoan_paypal::label($order['thanhtoan'] ?? null)))?>
                        <?php if (!empty($order['thanhtoan_qr'])): ?><a class="ms-2" href="index.php?act=bank&amp;id=<?=(int)$order['id']?>">Chi tiết chuyển khoản</a><?php endif; ?><?php if (!empty($order['thanhtoan'])): ?><a class="ms-2" href="index.php?act=paypal&amp;id=<?=$orderId?>">Xem thanh toán</a><?php endif; ?></p>
                        <?php foreach ($items as $item): ?>
                            <div class="d-flex justify-content-between gap-3 py-2 border-bottom">
                                <div>
                                    <span class="fw-semibold"><?=$e($item['ten_sp'])?></span>
                                    <span class="text-muted">× <?=$e($item['so_luong'])?></span>
                                </div>
                                <span class="text-nowrap"><?=number_format((float) $item['don_gia'] * (int) $item['so_luong'], 0, ',', '.')?>đ</span>
                            </div>
                        <?php endforeach; ?>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 pt-3">
                            <div>
                                <span class="text-muted">Tổng thanh toán</span>
                                <strong class="text-danger fs-5 ms-2"><?=number_format((float) $order['tong_tien'], 0, ',', '.')?>đ</strong>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-sm btn-outline-primary" href="index.php?act=theodoidonhang&amp;id=<?=$orderId?>">Theo dõi</a>
                                <?php if (in_array($status, [donhang::CHO_XAC_NHAN, donhang::XAC_NHAN], true) && (empty($order['thanhtoan_qr']) || in_array($order['thanhtoan_qr']['status'],['waiting','refunded'],true))): ?>
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalHuyDon" data-order="<?=$orderId?>">Hủy đơn</button>
                                <?php endif; ?>
                                <?php if (in_array($status, [donhang::DA_GIAO, donhang::THANH_CONG], true)): ?>
                                    <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalKhieuNai" data-order="<?=$orderId?>">Gửi khiếu nại</button>
                                <?php endif; ?>
                                <?php if ($status === donhang::THANH_CONG && isset($items[0]['id_sanpham'])): ?>
                                    <a class="btn btn-sm btn-outline-secondary" href="index.php?act=chitiet&amp;id=<?=(int) $items[0]['id_sanpham']?>#review">Đánh giá sản phẩm</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($order['tra_hang'])): ?><div class="alert alert-light mt-3 mb-0">Yêu cầu trả: <?=$e($order['tra_hang']['trang_thai'])?><br><?=$e($order['tra_hang']['phan_hoi'] ?? '')?><?php if (!empty($order['tra_hang']['trang_thai_hoan'])): ?><br><?=$e($order['tra_hang']['trang_thai_hoan'])?> · <?=view_helper::money($order['tra_hang']['so_tien_hoan'])?> · <?=$e($order['tra_hang']['tham_chieu_hoan'])?><?php endif; ?></div>
                        <?php elseif (in_array($status, [donhang::DA_GIAO,donhang::THANH_CONG], true) && !empty($order['ngay_giao']) && strtotime($order['ngay_giao'] . ' +7 days') >= time()): ?>
                        <details class="mt-3"><summary>Yêu cầu trả toàn bộ đơn hàng</summary><p class="small text-muted mt-2">Gửi trong 7 ngày từ khi giao. Chờ cửa hàng phản hồi trước khi gửi lại hàng; yêu cầu chưa làm thay đổi tồn kho.</p><form action="index.php?act=yeucautra" method="post"><input type="hidden" name="_csrf" value="<?=$e($userCsrf)?>"><input type="hidden" name="id" value="<?=$orderId?>"><textarea class="form-control mb-2" name="ly_do" maxlength="1000" required placeholder="Lý do trả hàng"></textarea><button class="btn btn-sm btn-outline-warning">Gửi yêu cầu trả</button></form></details>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<div class="modal fade" id="modalHuyDon" tabindex="-1" aria-labelledby="modalHuyDonLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form action="index.php?act=huydonhang" method="post"><input type="hidden" name="_csrf" value="<?=$e($userCsrf)?>">
            <input type="hidden" name="_checkout_csrf" value="<?=$e(paypal_controller::checkoutToken())?>">
            <div class="modal-header"><h2 class="modal-title fs-5" id="modalHuyDonLabel">Hủy đơn hàng</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
            <div class="modal-body">
                <p>Bạn xác nhận hủy đơn <strong id="cancelOrderIdText"></strong>?</p>
                <input type="hidden" name="id" id="cancelOrderIdInput">
                <label class="form-label" for="cancelReason">Lý do</label>
                <textarea class="form-control" id="cancelReason" name="ly_do" maxlength="1000" required></textarea>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button><button type="submit" class="btn btn-danger">Xác nhận hủy</button></div>
        </form>
    </div></div>
</div>

<div class="modal fade" id="modalKhieuNai" tabindex="-1" aria-labelledby="modalKhieuNaiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form action="index.php?act=khieunai" method="post"><input type="hidden" name="_csrf" value="<?=$e($userCsrf)?>">
            <div class="modal-header"><h2 class="modal-title fs-5" id="modalKhieuNaiLabel">Khiếu nại đơn hàng</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
            <div class="modal-body">
                <p>Đơn <strong id="complaintOrderIdText"></strong></p>
                <input type="hidden" name="id" id="complaintOrderIdInput">
                <label class="form-label" for="complaintMessage">Nội dung</label>
                <textarea class="form-control" id="complaintMessage" name="noi_dung" maxlength="5000" required></textarea>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button><button type="submit" class="btn btn-primary">Gửi khiếu nại</button></div>
        </form>
    </div></div>
</div>
<script>
document.getElementById('modalHuyDon')?.addEventListener('show.bs.modal', function (event) {
    const id = event.relatedTarget?.getAttribute('data-order') || '';
    document.getElementById('cancelOrderIdInput').value = id;
    document.getElementById('cancelOrderIdText').textContent = '#' + id;
});
document.getElementById('modalKhieuNai')?.addEventListener('show.bs.modal', function (event) {
    const id = event.relatedTarget?.getAttribute('data-order') || '';
    document.getElementById('complaintOrderIdInput').value = id;
    document.getElementById('complaintOrderIdText').textContent = '#' + id;
});
</script>
