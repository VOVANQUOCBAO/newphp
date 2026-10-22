<?php
include __DIR__ . '/header_admin.php';
$admin_url = 'index.php?khu=admin';
$nextStates = donhang::LUONG;
?>
<div class="container-fluid">
    <?php if(!$isSuperAdmin): ?><p class="text-muted">Tài khoản hiện tại chỉ xem đơn hàng.</p><?php endif; ?>
    <?php if ($isSuperAdmin && !thanhtoan_qr::schemaReady()): ?><form action="<?=$admin_url?>&amp;act=khoitaoqr" method="post" class="alert alert-info"><input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>"><p>Tạo bảng lưu giao dịch chuyển khoản QR. Không thay đổi đơn hàng hiện có.</p><button class="btn btn-primary btn-sm">Kích hoạt chuyển khoản QR</button></form><?php endif; ?>
    <?php if ($loi !== null): ?><div class="alert alert-danger"><?=$e($loi)?></div><?php endif; ?>
    <?php if ($thongBao !== null): ?><div class="alert alert-success"><?=$e($thongBao)?></div><?php endif; ?>
    <?php if (isset($chitiet) && $chitiet !== null): ?>
        <section class="card mb-4"><div class="card-header fw-bold">Chi tiết đơn #<?=$e($chitiet['id'])?></div>
            <div class="card-body">
                <p>Khách hàng #<?=$e($chitiet['id_khachhang'])?> · Trạng thái: <?=$e($chitiet['trang_thai'])?></p>
                <p>Địa chỉ: <?=$e($chitiet['dia_chi'])?> · Điện thoại: <?=$e($chitiet['sdt'])?></p>
                <p>Tổng: <?=number_format((float)$chitiet['tong_tien'], 0, ',', '.')?>đ · Phí ship: <?=number_format((float)$chitiet['phi_ship'], 0, ',', '.')?>đ</p>
                <?php if (!empty($chitiet['voucher_pham_vi'])): ?><p>Voucher <?=$e($chitiet['ma_voucher'])?> · <?=$e($chitiet['voucher_doi_tuong'])?> · tiền hàng áp dụng <?=money($chitiet['voucher_tien_ap_dung'])?> · giảm <?=money($chitiet['tien_giam'])?></p><?php endif; ?>
                <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Mã SP</th><th>Sản phẩm</th><th>Số lượng</th><th>Đơn giá</th></tr></thead>
                    <tbody><?php foreach ($chitiet['chitiet'] as $item): ?><tr><td><?=$e($item['ma_sp'])?></td><td><?=$e($item['ten_sp'])?></td><td><?=$e($item['so_luong'])?></td><td><?=number_format((float)$item['don_gia'], 0, ',', '.')?>đ</td></tr><?php endforeach; ?></tbody>
                </table></div>
                <?php if (!empty($chitiet['lichsu'])): ?><h4 class="h6 mt-3">Lịch sử xử lý</h4><ul><?php foreach ($chitiet['lichsu'] as $event): ?><li><?=$e($event['ngay_tao'])?> · <?=$e($event['tac_nhan'])?> · <?=$e($event['trang_thai_moi'])?> — <?=$e($event['ghi_chu'])?></li><?php endforeach; ?></ul><?php endif; ?>
                <a class="btn btn-outline-secondary btn-sm" href="<?=$admin_url?>&amp;act=donhang">Quay lại danh sách</a>
            </div>
        </section>
    <?php endif; ?>
    <?php $tableId='orders-table';$searchPlaceholder='Mã đơn, tên khách, điện thoại hoặc thanh toán';$statusOptions=array_combine(array_keys($nextStates),array_keys($nextStates));require __DIR__.'/_table_tools.php'; ?>
    <section class="card"><div class="card-header fw-bold">Đơn hàng (<?=count($ds_donhang)?>)</div><div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="orders-table"><thead><tr><th>Mã đơn / Ngày</th><th>Khách hàng</th><th>Liên hệ</th><th>Tổng</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
            <tbody><?php foreach ($ds_donhang as $dh): ?><tr data-admin-row data-status="<?=$e($dh['trang_thai'])?>">
                <td><a href="<?=$admin_url?>&amp;act=chitietdonhang&amp;id=<?=$e($dh['id'])?>">#<?=$e($dh['id'])?></a><br><small><?=$e($dh['ngay_dat'])?></small></td>
                <td><?=$e($dh['ho_ten'])?><br><small><?=$e($dh['email'])?></small></td>
                <td><?=$e($dh['sdt'])?><br><?=$e($dh['dia_chi'])?></td>
                <td><?=number_format((float)$dh['tong_tien'], 0, ',', '.')?>đ</td><td><?=$e($dh['trang_thai'])?><?php if (!empty($dh['tra_hang'])): ?><div class="small text-warning mt-2">Trả hàng: <?=$e($dh['tra_hang']['trang_thai'])?><br><?=$e($dh['tra_hang']['ly_do'])?></div><?php endif; ?></td>
                <td><div class="small mb-2"><?=$e((!empty($dh['thanhtoan_qr']) ? thanhtoan_qr::label($dh['thanhtoan_qr']) : thanhtoan_paypal::label($dh['thanhtoan'] ?? null)))?></div>
                <?php if (!empty($dh['thanhtoan_qr'])) require __DIR__.'/_bank_payment.php'; ?>
                <?php if (!empty($dh['thanhtoan'])): ?><div class="small mb-2">$<?=$e($dh['thanhtoan']['amount_usd'])?> USD<br><?=$e($dh['thanhtoan']['capture_id'] ?? '')?></div>
                    <?php if ($isSuperAdmin): ?><form action="<?=$admin_url?>&amp;act=kiemtrapaypal" method="post" class="mb-2">
                        <input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>"><input type="hidden" name="id" value="<?=$e($dh['id'])?>">
                        <button class="btn btn-outline-primary btn-sm">Đối soát PayPal</button>
                    </form><?php endif; ?>
                <?php endif; ?>
                <?php if ($isSuperAdmin && ($dh['tra_hang']['trang_thai_hoan'] ?? '') === 'Chờ hoàn tiền'): ?><form action="<?=$admin_url?>&amp;act=hoancod" method="post" class="my-2"><input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>"><input type="hidden" name="id" value="<?=(int)$dh['id']?>"><small>Hoàn <?=money($dh['tra_hang']['so_tien_hoan'])?></small><input name="tham_chieu" maxlength="255" required placeholder="Mã/chứng từ đã hoàn COD" class="form-control form-control-sm"><button class="btn btn-sm btn-outline-success">Xác nhận đã hoàn COD</button></form><?php endif; ?>
                <?php if ($isSuperAdmin): $options = $nextStates[$dh['trang_thai']] ?? []; if (!empty($dh['thanhtoan_qr'])) { $qs=$dh['thanhtoan_qr']['status']; if ($qs==='waiting' || $qs==='refunded') $options=array_values(array_intersect($options,[donhang::DA_HUY])); elseif ($qs==='reported') $options=[]; elseif ($qs==='paid') $options=array_values(array_diff($options,[donhang::DA_HUY])); } if (!empty($dh['thanhtoan'])) { $ps=$dh['thanhtoan']['status']; $options=array_values(array_filter($options,static fn($state)=>in_array($state,[donhang::DA_HUY,donhang::TRA_HANG,donhang::BOM_HANG],true) ? in_array($ps,['created','awaiting','approved','failed','refunded'],true) : $ps==='paid')); } if (in_array($dh['trang_thai'], [donhang::DA_GIAO,donhang::THANH_CONG], true) && ($dh['tra_hang']['trang_thai'] ?? '') !== 'Chờ xử lý') $options = array_values(array_diff($options, [donhang::TRA_HANG])); if ($options !== []): ?>
                    <form action="<?=$admin_url?>&amp;act=capnhattrangthai" method="post" class="d-flex flex-wrap gap-2 admin-order-actions">
                        <input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>">
                        <input type="hidden" name="id" value="<?=$e($dh['id'])?>">
                        <select class="form-select form-select-sm" name="trang_thai" data-order-action aria-label="Thao tác đơn #<?=(int)$dh['id']?>" required><option value="" selected>Chọn thao tác…</option><?php foreach ($options as $state): ?><option value="<?=$e($state)?>"><?=$e($state)?></option><?php endforeach; ?></select>
                        <input class="form-control form-control-sm" name="ly_do" placeholder="Lý do (nếu hủy/bom/trả)" maxlength="1000">
                        <div data-return-fields><label class="small d-block"><input type="checkbox" name="da_nhan_hang" value="1"> Đã nhận lại và kiểm tra hàng</label><label class="small d-block mt-1"><input type="checkbox" name="nhap_lai_kho" value="1" checked> Hàng đủ điều kiện bán lại; bỏ chọn để cách ly</label></div><button class="btn btn-sm btn-primary">Cập nhật</button>
                    </form><?php else: ?><span class="text-muted"><?=($dh['thanhtoan_qr']['status'] ?? '')==='reported' ? 'Chờ đối soát tiền' : (!empty($nextStates[$dh['trang_thai']]) ? 'Chờ xử lý thanh toán hoặc yêu cầu trả' : 'Đã kết thúc')?></span><?php endif; ?>
                <?php if (($dh['tra_hang']['trang_thai'] ?? '') === 'Chờ xử lý'): ?><form action="<?=$admin_url?>&amp;act=tuchoitra" method="post" class="mt-2"><input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>"><input type="hidden" name="id" value="<?=(int)$dh['id']?>"><input class="form-control form-control-sm mb-1" name="ly_do" placeholder="Lý do từ chối trả hàng" maxlength="1000" required><button class="btn btn-sm btn-outline-danger">Từ chối trả</button></form><?php endif; ?>
                <?php else: ?><span class="text-muted">Chỉ xem</span><?php endif; ?></td>
            </tr><?php endforeach; ?></tbody>
        </table></div>
        <?php if ($ds_donhang === []): ?><p class="text-muted p-3 mb-0">Chưa có đơn hàng.</p><?php endif; ?>
    </section>
</div>

<?php if (!empty($ds_khieunai)): ?><section class="card mt-4"><div class="card-header fw-bold">Khiếu nại khách hàng</div><div class="card-body"><?php foreach ($ds_khieunai as $message): ?><details class="border-bottom py-2"><summary><?=$e($message['tieu_de'])?> · <?=$e($message['ho_ten'])?> · <?=$e($message['ngay_gui'])?></summary><p class="mt-2" style="white-space:pre-wrap"><?=$e($message['noi_dung'])?></p></details><?php endforeach; ?></div></section><?php endif; ?>

