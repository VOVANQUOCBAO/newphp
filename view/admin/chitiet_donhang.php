<?php
require __DIR__.'/header_admin.php';
$order=$chitiet;
$formatDate=static function(?string $value): string {
    $time=$value ? strtotime($value) : false;
    return $time===false ? 'Chưa ghi nhận' : date('d/m/Y · H:i',$time);
};
$listUrl='index.php?khu=admin&amp;act=donhang'.($order ? '#order-'.(int)$order['id'] : '');
?>
<div class="container-fluid order-detail">
    <a href="<?=$listUrl?>" class="order-back"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Danh sách đơn hàng</a>
    <?php if(!$order): ?><div class="alert alert-warning mt-3"><?=$e($loi ?? 'Không tìm thấy đơn hàng.')?></div><?php else:
        $items=$order['chitiet'];
        $quantity=array_sum(array_column($items,'so_luong'));
        $qr=$order['thanhtoan_qr'] ?? null;
        $paypal=$order['thanhtoan'] ?? null;
        $return=$order['tra_hang'] ?? null;
        $method=$qr ? 'Chuyển khoản QR' : ($paypal ? 'PayPal' : 'COD');
        $paymentLabel=$qr ? thanhtoan_qr::label($qr) : ($paypal ? thanhtoan_paypal::label($paypal) : ($order['trang_thai']===donhang::THANH_CONG ? 'COD · Đã thu tiền' : 'COD · Thu tiền khi giao hàng'));
        $stateClass=match($order['trang_thai']) {
            donhang::THANH_CONG=>'is-success',donhang::DA_HUY,donhang::BOM_HANG=>'is-muted',donhang::TRA_HANG=>'is-return',default=>'is-processing'
        };
    ?>
    <header class="order-detail-heading">
        <div><div class="order-kicker">CHI TIẾT ĐƠN HÀNG</div><h1>Đơn hàng <span class="order-id">#<?=(int)$order['id']?></span></h1><p>Đặt lúc <time><?=$e($formatDate($order['ngay_dat'] ?? null))?></time></p></div>
        <span class="order-state <?=$stateClass?>"><?=$e($order['trang_thai'])?></span>
    </header>
    <div class="order-detail-grid">
        <div class="order-detail-main">
            <section class="card order-section"><div class="card-header"><h2>Thông tin khách hàng</h2><span class="order-muted">Khách #<?=(int)$order['id_khachhang']?></span></div><div class="card-body order-customer-grid">
                <div><span class="order-label">Khách hàng</span><strong class="order-customer-name"><?=$e($order['ho_ten'])?></strong><?php if(!empty($order['email'])): ?><span class="order-contact"><?=$e($order['email'])?></span><?php endif; ?></div>
                <div><span class="order-label">Số điện thoại</span><strong class="order-phone"><?=$e($order['sdt'])?></strong></div>
                <div class="order-address"><span class="order-label">Địa chỉ nhận hàng</span><span><?=$e($order['dia_chi'])?></span></div>
            </div></section>
            <section class="card order-section"><div class="card-header"><h2>Sản phẩm trong đơn</h2><span class="order-muted"><?=count($items)?> mặt hàng · <?=(int)$quantity?> sản phẩm</span></div>
                <div class="table-responsive"><table class="table order-items mb-0"><thead><tr><th scope="col">Sản phẩm</th><th scope="col" class="text-center">SL</th><th scope="col" class="text-end">Đơn giá</th><th scope="col" class="text-end">Thành tiền</th></tr></thead><tbody>
                    <?php foreach($items as $item): ?><tr><td><a class="order-product" href="index.php?khu=admin&amp;act=chitietsanpham&amp;id=<?=(int)$item['id_sanpham']?>"><img src="<?=$e(view_helper::productImage($item['hinh']))?>" alt="<?=$e($item['ten_sp'])?>" width="64" height="72"><span><strong><?=$e($item['ten_sp'])?></strong><span class="order-product-code">Mã SP: <?=$e($item['ma_sp'])?></span></span></a></td><td class="text-center order-quantity"><?=(int)$item['so_luong']?></td><td class="text-end order-money"><?=view_helper::money($item['don_gia'])?></td><td class="text-end order-money order-line-total"><?=view_helper::money((float)$item['don_gia']*(int)$item['so_luong'])?></td></tr><?php endforeach; ?>
                    <?php if(!$items): ?><tr><td colspan="4" class="text-muted">Chưa có thông tin sản phẩm.</td></tr><?php endif; ?>
                </tbody></table></div>
            </section>
            <section class="card order-section"><div class="card-header"><h2>Lịch sử xử lý</h2><span class="order-muted"><?=count($order['lichsu'] ?? [])?> cập nhật</span></div><div class="card-body">
                <ol class="order-timeline"><?php foreach(($order['lichsu'] ?? []) as $event): ?><li><time><?=$e($formatDate($event['ngay_tao']))?></time><div><strong><?=$e($event['trang_thai_moi'])?></strong><span class="order-actor"><?=$e($event['tac_nhan'])?></span><?php if(trim((string)($event['ghi_chu'] ?? ''))!==''): ?><p><?=$e($event['ghi_chu'])?></p><?php endif; ?></div></li><?php endforeach; ?></ol>
                <?php if(empty($order['lichsu'])): ?><p class="order-muted mb-0">Chưa có lịch sử xử lý.</p><?php endif; ?>
            </div></section>
        </div>
        <aside class="order-detail-aside" aria-label="Tổng tiền và thanh toán">
            <section class="card order-section order-summary"><div class="card-header"><h2>Chi tiết số tiền</h2></div><div class="card-body">
                <dl class="order-amounts"><div><dt>Tiền hàng</dt><dd><?=view_helper::money($order['tam_tinh'] ?? 0)?></dd></div><div><dt>Giảm giá voucher</dt><dd class="order-discount"><?=(float)($order['tien_giam'] ?? 0)>0?'− ':''?><?=view_helper::money($order['tien_giam'] ?? 0)?></dd></div><div><dt>Phí giao hàng</dt><dd><?=view_helper::money($order['phi_ship'])?></dd></div></dl>
                <div class="order-grand-total"><span>Tổng giá trị đơn</span><strong><?=view_helper::money($order['tong_tien'])?></strong></div>
                <?php if(!empty($order['ma_voucher'])): ?><div class="order-voucher"><span class="order-label">Voucher đã áp dụng</span><strong><?=$e($order['ma_voucher'])?></strong><?php if(!empty($order['voucher_doi_tuong'])): ?><span><?=$e($order['voucher_doi_tuong'])?></span><?php endif; ?><?php if(isset($order['voucher_tien_ap_dung'])): ?><span>Tiền hàng áp dụng: <b class="order-money"><?=view_helper::money($order['voucher_tien_ap_dung'])?></b></span><?php endif; ?></div><?php endif; ?>
            </div></section>
            <section class="card order-section"><div class="card-header"><h2>Thanh toán</h2></div><div class="card-body"><strong class="order-payment-method"><?=$e($method)?></strong><p class="order-payment-state"><?=$e($paymentLabel)?></p>
                <?php if($qr): ?><dl class="order-payment-fields"><dt>Nội dung chuyển khoản</dt><dd><?=$e($qr['transfer_content'])?></dd><?php if(!empty($qr['received_reference'])): ?><dt>Mã giao dịch nhận tiền</dt><dd><?=$e($qr['received_reference'])?></dd><?php endif; ?><?php if(!empty($qr['received_at'])): ?><dt>Thời gian nhận tiền</dt><dd><?=$e($formatDate($qr['received_at']))?></dd><?php endif; ?></dl><?php endif; ?>
                <?php if($paypal): ?><dl class="order-payment-fields"><dt>Số tiền PayPal</dt><dd class="order-money"><?=$e(number_format((float)$paypal['amount_usd'],2,'.',','))?> USD</dd><?php if(!empty($paypal['capture_id'])): ?><dt>Mã giao dịch</dt><dd><?=$e($paypal['capture_id'])?></dd><?php endif; ?></dl><?php endif; ?>
            </div></section>
            <?php if($return): ?><section class="card order-section"><div class="card-header"><h2>Trả hàng và hoàn tiền</h2></div><div class="card-body"><p class="order-refund-state"><?=$e($return['trang_thai_hoan'] ?: $return['trang_thai'])?></p><?php if(isset($return['so_tien_hoan'])): ?><div class="order-refund-amount"><span class="order-label">Số tiền hoàn</span><strong><?=view_helper::money($return['so_tien_hoan'])?></strong></div><?php endif; ?><dl class="order-payment-fields"><dt>Lý do trả hàng</dt><dd><?=$e($return['ly_do'])?></dd><?php if(!empty($return['phan_hoi'])): ?><dt>Kết quả xử lý</dt><dd><?=$e($return['phan_hoi'])?></dd><?php endif; ?><?php if(!empty($return['tham_chieu_hoan'])): ?><dt>Chứng từ hoàn tiền</dt><dd><?=$e($return['tham_chieu_hoan'])?></dd><?php endif; ?></dl></div></section><?php endif; ?>
        </aside>
    </div>
    <?php endif; ?>
</div>
