<?php
$h = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$id = (int) $payment['id_donhang'];
$paid = $payment['status'] === 'paid';
$canPay = in_array($payment['status'], ['created', 'awaiting'], true) && $payment['order_status'] === 'Chờ xác nhận';
?>
<main class="container py-5" style="max-width:850px">
    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center mb-4">
        <h1 class="h3 mb-0">Thanh toán PayPal · Đơn #<?=$id?></h1>
        <a class="btn btn-outline-secondary" href="index.php?act=lichsumuahang">Lịch sử đơn hàng</a>
    </div>
    <?php if ($payment['mode'] === 'sandbox'): ?><div class="alert alert-warning">Môi trường thử PayPal Sandbox — không thu tiền thật.</div><?php endif; ?>
    <?php if (!empty($loi)): ?><div class="alert alert-danger" role="alert"><?=$h($loi)?></div><?php endif; ?>
    <?php if (!empty($thongBao)): ?><div class="alert alert-info"><?=$h($thongBao)?></div><?php endif; ?>
    <section class="card shadow-sm border-0 rounded-4 p-4">
        <h2 class="h5 <?=$paid ? 'text-success' : ''?>"><?=$h(thanhtoan_paypal::label($payment))?></h2>
        <div class="d-flex justify-content-between border-bottom py-3"><span>Tổng đơn hàng</span><strong><?=number_format((float)$payment['amount_vnd'],0,',','.')?>đ</strong></div>
        <div class="d-flex justify-content-between py-3"><span>Số tiền thu qua PayPal</span><strong class="fs-3 text-primary">$<?=$h($payment['amount_usd'])?> USD</strong></div>
        <p class="small text-muted mb-1">Tỷ giá chốt cho đơn: 1 USD = <?=number_format((float)$payment['vnd_per_usd'],2,',','.')?> VND. Số tiền USD này được giữ nguyên cho đơn hàng.</p>
        <?php if ($payment['rate_source'] === 'ExchangeRate-API'): ?><p class="small text-muted"><a href="https://www.exchangerate-api.com" target="_blank" rel="noopener noreferrer">Rates By Exchange Rate API</a> · <?=$h($payment['rate_at'])?></p><?php endif; ?>
        <?php if ($paid): ?>
            <p class="text-success fw-semibold mt-3">PayPal đã xác nhận nhận đủ thanh toán. Bạn không cần thanh toán lại.</p>
            <p class="small">Mã giao dịch: <?=$h($payment['capture_id'])?> · <?=$h($payment['paid_at'])?></p>
            <a class="btn btn-primary" href="index.php?act=theodoidonhang&amp;id=<?=$id?>">Theo dõi giao hàng</a>
        <?php else: ?>
            <p class="small mt-3">PayPal thu bằng USD. Nếu nguồn tiền của bạn dùng ngoại tệ khác, PayPal hoặc ngân hàng sẽ hiển thị phần quy đổi của họ trước khi bạn chấp thuận.</p>
            <?php if ($canPay): ?>
                <form action="index.php?act=paypal_create" method="post" class="mb-3">
                    <input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="_checkout_csrf" value="<?=$h($checkoutToken)?>">
                    <button class="btn btn-warning btn-lg w-100 fw-bold">Thanh toán $<?=$h($payment['amount_usd'])?> USD qua PayPal</button>
                </form>
                <p class="small text-muted">Giữ hàng đến <?=$h($payment['expires_at'])?>. Đơn hết hạn được hủy và hoàn kho khi hệ thống kiểm tra lại.</p>
            <?php elseif (in_array($payment['status'], ['creating','processing','pending','review'],true)): ?>
                <p class="alert alert-warning">Giao dịch đang xử lý hoặc cần đối soát. Hãy kiểm tra trạng thái hoặc liên hệ cửa hàng; không tạo khoản thanh toán mới.</p>
            <?php elseif ($payment['status'] === 'failed'): ?>
                <p class="alert alert-warning">PayPal chưa thu được tiền. Bạn có thể kiểm tra trạng thái, hủy đơn này và đặt lại bằng phương thức khác.</p>
            <?php endif; ?>
            <div class="d-flex flex-wrap gap-2">
                <?php if ($payment['provider_order_id'] || $payment['status'] === 'creating'): ?><form action="index.php?act=paypal_check" method="post">
                    <input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="_checkout_csrf" value="<?=$h($checkoutToken)?>">
                    <button class="btn btn-outline-primary">Kiểm tra trạng thái PayPal</button>
                </form><?php endif; ?>
                <?php if ($canPay || in_array($payment['status'], ['failed','refunded','approved'], true)): ?><form action="index.php?act=paypal_abandon" method="post">
                    <input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="_checkout_csrf" value="<?=$h($checkoutToken)?>">
                    <button class="btn btn-outline-danger">Hủy đơn chưa thanh toán</button>
                </form><?php endif; ?>
                <?php if (in_array($payment['status'], ['processing','approved'], true)): ?><form action="index.php?act=paypal_capture" method="post">
                    <input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="_checkout_csrf" value="<?=$h($checkoutToken)?>">
                    <button class="btn btn-primary">Hoàn tất khoản thu $<?=$h($payment['amount_usd'])?> USD đã duyệt</button>
                </form><?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php if ($autoCapture): ?>
        <p class="text-center text-muted mt-4" role="status">Đang xác nhận khoản thanh toán với PayPal…</p>
        <form id="paypal-capture-form" action="index.php?act=paypal_capture" method="post">
            <input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="_checkout_csrf" value="<?=$h($checkoutToken)?>">
            <noscript><button class="btn btn-primary mt-3">Hoàn tất khoản thanh toán đã duyệt</button></noscript>
        </form>
        <script>document.getElementById('paypal-capture-form').submit();</script>
    <?php endif; ?>
</main>
