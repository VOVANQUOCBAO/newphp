<?php
$e = static fn(mixed $value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$order = is_array($donhang ?? null) ? $donhang : null;
$timeline = $order['timeline'] ?? [];
$currentIndex = array_search($order['trang_thai'] ?? '', $timeline, true);
?>
<main class="container my-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h1 class="h2 fw-bold text-primary mb-1">Theo dõi đơn hàng</h1><p class="text-muted mb-0">Tra cứu đơn hàng trong tài khoản đang đăng nhập.</p></div>
        <a class="btn btn-outline-secondary" href="index.php?act=lichsumuahang">Quay lại lịch sử đơn</a>
    </div>
    <?php if (!empty($loi)): ?><div class="alert alert-danger"><?=$e($loi)?></div><?php endif; ?>
    <section class="card border-0 shadow-sm mb-4"><div class="card-body">
        <form action="index.php" method="get" class="row g-3 align-items-end">
            <input type="hidden" name="act" value="theodoidonhang">
            <div class="col-sm-8"><label class="form-label" for="orderId">Mã đơn hàng (ID)</label><input class="form-control" id="orderId" type="number" name="id" min="1" value="<?=$order ? (int) $order['id'] : $e($_GET['id'] ?? '')?>" required></div>
            <div class="col-sm-4"><button class="btn btn-primary w-100" type="submit">Tra cứu đơn</button></div>
        </form>
    </div></section>

    <?php if ($order): ?>
        <article class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-primary text-white d-flex flex-wrap justify-content-between gap-2">
                <strong>Đơn hàng #<?=(int) $order['id']?></strong>
                <span><?= $e($order['trang_thai']) ?></span>
            </div>
            <div class="card-body p-4">
                <p class="fw-semibold"><?=$e((!empty($order['thanhtoan_qr']) ? thanhtoan_qr::label($order['thanhtoan_qr']) : thanhtoan_paypal::label($order['thanhtoan'] ?? null)))?>
                    <?php if (!empty($order['thanhtoan_qr'])): ?><a class="ms-2" href="index.php?act=bank&amp;id=<?=(int)$order['id']?>">Chi tiết chuyển khoản</a><?php endif; ?><?php if (!empty($order['thanhtoan'])): ?><a class="ms-2" href="index.php?act=paypal&amp;id=<?=(int)$order['id']?>">Chi tiết thanh toán</a><?php endif; ?></p>
                <?php if ($currentIndex !== false): ?>
                    <ol class="list-group list-group-numbered mb-4">
                        <?php foreach ($timeline as $index => $step): ?>
                            <li class="list-group-item <?=$index < $currentIndex ? 'list-group-item-success' : ($index === $currentIndex ? 'list-group-item-primary fw-bold' : '')?>">
                                <?=$e($step)?><?=$index === $currentIndex ? ' — trạng thái hiện tại' : ''?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php else: ?>
                    <div class="alert alert-warning">Đơn hàng đã kết thúc với trạng thái: <?=$e($order['trang_thai'])?>.</div>
                <?php endif; ?>
                <?php if (!empty($order['ly_do_huy'])): ?><p><strong>Ghi chú trạng thái:</strong> <?=$e($order['ly_do_huy'])?></p><?php endif; ?>
                <p><strong>Ngày đặt:</strong> <?=$e($order['ngay_dat'])?> · <strong>Cập nhật:</strong> <?=$e($order['ngay_cap_nhat'])?></p>
                <p><strong>Địa chỉ giao hàng:</strong> <?=$e($order['dia_chi'])?> · <strong>Điện thoại:</strong> <?=$e($order['sdt'])?></p>
                <?php if (!empty($order['lichsu'])): ?><details class="mb-3"><summary>Lịch sử xử lý</summary><ul class="mt-2"><?php foreach ($order['lichsu'] as $event): ?><li><?=$e($event['ngay_tao'])?> · <?=$e($event['trang_thai_moi'])?> · <?=$e($event['ghi_chu'])?></li><?php endforeach; ?></ul></details><?php endif; ?>
                <h2 class="h5 mt-4">Sản phẩm</h2>
                <?php if (!empty($order['voucher_pham_vi'])): ?><p class="small">Voucher <?=$e($order['ma_voucher'])?> · <?=$e($order['voucher_doi_tuong'])?><br>Tiền hàng được áp dụng: <?=money($order['voucher_tien_ap_dung'])?> · Giảm <?=money($order['tien_giam'])?></p><?php endif; ?>
                <ul class="list-group mb-3">
                    <?php foreach ($order['chitiet'] as $item): ?>
                        <li class="list-group-item d-flex justify-content-between gap-3">
                            <span><?=$e($item['ten_sp'])?> × <?=$e($item['so_luong'])?></span>
                            <strong><?=number_format((float) $item['don_gia'] * (int) $item['so_luong'], 0, ',', '.')?>đ</strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="text-end"><?php if ($order['tien_giam'] !== null): ?><div>Tạm tính: <?=money($order['tam_tinh'])?> · Giảm voucher: <?=money($order['tien_giam'])?></div><?php endif; ?><div>Phí giao hàng: <?=number_format((float) $order['phi_ship'], 0, ',', '.')?>đ</div><strong class="text-danger fs-5">Tổng: <?=number_format((float) $order['tong_tien'], 0, ',', '.')?>đ</strong></div>
            </div>
        </article>
    <?php endif; ?>
</main>
