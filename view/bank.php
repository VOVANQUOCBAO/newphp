<?php $h = static fn($value) => htmlspecialchars((string)($value ?? ''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); ?>
<main class="container py-5" style="max-width:1000px">
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-4"><h1 class="h3">Chuyển khoản ngân hàng<?php if ($payment): ?> · Đơn #<?=(int)$payment['id_donhang']?><?php endif; ?></h1><a class="btn btn-outline-secondary" href="index.php?act=lichsumuahang">Lịch sử đơn hàng</a></div>
    <?php if (!empty($loi)): ?><div class="alert alert-danger" role="alert"><?=$h($loi)?></div><?php endif; ?>
    <?php if (!empty($thongBao)): ?><div class="alert alert-info" role="status"><?=$h($thongBao)?></div><?php endif; ?>
    <?php if ($payment): $canPay = $payment['status']==='waiting' && $payment['order_status']===donhang::CHO_XAC_NHAN; ?>
    <section class="card p-4 rounded-4"><h2 class="h5 mb-4"><?=$h(thanhtoan_qr::label($payment))?></h2>
        <?php if ($canPay): ?>
        <div class="row g-4 align-items-center">
            <div class="col-md-5 text-center"><img src="<?=$h($qrUrl)?>" alt="QR chuyển khoản cho đơn #<?=(int)$payment['id_donhang']?>" class="img-fluid bg-white rounded border" style="max-width:300px" referrerpolicy="no-referrer"><p class="small text-muted mt-2">Mở ứng dụng ngân hàng để quét QR hoặc nhập thông tin bên cạnh.</p></div>
            <div class="col-md-7"><p class="fs-2 fw-bold text-primary"><?=view_helper::money($payment['amount_vnd'])?></p><dl class="row"><dt class="col-sm-5">Ngân hàng</dt><dd class="col-sm-7"><?=$h($payment['bank_name'])?></dd><dt class="col-sm-5">Số tài khoản</dt><dd class="col-sm-7 user-select-all"><?=$h($payment['account_number'])?></dd><dt class="col-sm-5">Chủ tài khoản</dt><dd class="col-sm-7"><?=$h($payment['account_name'])?></dd><dt class="col-sm-5">Nội dung chuyển</dt><dd class="col-sm-7 fw-bold user-select-all"><?=$h($payment['transfer_content'])?></dd></dl>
            <p>Kiểm tra tên người nhận trên ứng dụng ngân hàng trước khi xác nhận. Chuyển đúng số tiền và giữ nguyên nội dung để cửa hàng đối soát.</p>
            <?php if ($payment['status']==='waiting' && empty($payment['customer_reported_at'])): ?><p class="small text-muted">Giữ hàng đến <?=$h($payment['expires_at'])?>. Nếu đã chuyển, hãy báo cửa hàng trước khi hủy hoặc hết hạn.</p><form action="index.php?act=baochuyenkhoan" method="post"><input type="hidden" name="_csrf" value="<?=$h($userCsrf)?>"><input type="hidden" name="id" value="<?=(int)$payment['id_donhang']?>"><button class="btn btn-primary">Tôi đã chuyển khoản</button></form>
            <?php elseif ($payment['status']==='reported'): ?><div class="alert alert-info">Cửa hàng đang kiểm tra tiền. Bạn không cần chuyển thêm hoặc tạo lại đơn.</div><?php else: ?><p class="text-warning">Cửa hàng đã kiểm tra và chưa nhận được tiền. Nếu đã chuyển, vui lòng liên hệ để đối soát trước khi chuyển lại.</p><?php endif; ?></div>
        </div>
        <?php elseif ($payment['status']==='reported'): ?><div class="alert alert-info">Cửa hàng đang kiểm tra khoản <?=view_helper::money($payment['amount_vnd'])?> với nội dung <?=$h($payment['transfer_content'])?>. Bạn không cần chuyển thêm hoặc tạo lại đơn.</div>
        <?php elseif ($payment['status']==='paid'): ?><div class="alert alert-success">Cửa hàng đã xác nhận nhận đủ <?=view_helper::money($payment['amount_vnd'])?>. Bạn có thể theo dõi giao hàng trong lịch sử đơn.</div><p>Mã giao dịch: <?=$h($payment['received_reference'])?> · <?=$h($payment['received_at'])?></p>
        <?php elseif ($payment['status']==='refund_due'): ?><div class="alert alert-warning">Khoản <?=view_helper::money($payment['amount_vnd'])?> đang chờ cửa hàng hoàn tiền. Không chuyển thêm cho đơn này.</div>
        <?php elseif ($payment['status']==='refunded'): ?><p class="text-success">Đã ghi nhận hoàn <?=view_helper::money($payment['amount_vnd'])?> · Chứng từ <?=$h($payment['refund_reference'])?>.</p>
        <?php else: ?><div class="alert alert-warning">Đơn đã hủy hoặc hết hạn. Không chuyển tiền cho đơn này. Nếu đã chuyển, liên hệ cửa hàng kèm mã đơn và giao dịch ngân hàng.</div><?php endif; ?>
        <div class="d-flex flex-wrap gap-2 mt-4"><a class="btn btn-outline-primary" href="index.php?act=bank&amp;id=<?=(int)$payment['id_donhang']?>">Kiểm tra trạng thái</a><a class="btn btn-outline-secondary" href="index.php?act=theodoidonhang&amp;id=<?=(int)$payment['id_donhang']?>">Theo dõi đơn</a></div>
    </section><?php endif; ?>
</main>
