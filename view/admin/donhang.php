<?php
include __DIR__ . '/header_admin.php';
$admin_url = 'index.php?khu=admin';
$nextStates = donhang::LUONG;
?>
<div class="container-fluid">
    <?php if(!$canManageStore): ?><p class="text-muted">Tài khoản hiện tại chỉ xem đơn hàng.</p><?php endif; ?>
    <?php if ($canManageStore && !$bankSchemaReady): ?><form action="<?=$admin_url?>&amp;act=khoitaoqr" method="post" class="alert alert-info"><input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>"><p>Tạo bảng lưu giao dịch chuyển khoản QR. Không thay đổi đơn hàng hiện có.</p><button class="btn btn-primary btn-sm">Kích hoạt chuyển khoản QR</button></form><?php endif; ?>
    <?php if ($loi !== null): ?><div class="alert alert-danger"><?=$e($loi)?></div><?php endif; ?>
    <?php if ($thongBao !== null): ?><div class="alert alert-success"><?=$e($thongBao)?></div><?php endif; ?>
    <?php if (!empty($canhBao)): ?><div class="alert alert-warning"><?=$e($canhBao)?></div><?php endif; ?>
    <?php $tableId='orders-table';$searchPlaceholder='Mã đơn, tên khách, điện thoại hoặc thanh toán';$statusOptions=array_combine(array_keys($nextStates),array_keys($nextStates));require __DIR__.'/_table_tools.php'; ?>
    <section class="card"><div class="card-header fw-bold">Đơn hàng (<?=count($ds_donhang)?>)</div><div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="orders-table"><thead><tr><th>Mã đơn / Ngày</th><th>Khách hàng</th><th>Liên hệ</th><th>Tổng</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
            <tbody><?php foreach ($ds_donhang as $dh): ?><tr id="order-<?=(int)$dh['id']?>" data-admin-row data-status="<?=$e($dh['trang_thai'])?>">
                <td class="order-list-id"><a class="order-number-link" href="<?=$admin_url?>&amp;act=chitietdonhang&amp;id=<?=(int)$dh['id']?>" aria-label="Xem chi tiết đơn hàng #<?=(int)$dh['id']?>">#<?=(int)$dh['id']?><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a><small class="d-block mt-2"><?=$e(date('d/m/Y H:i',strtotime($dh['ngay_dat'])))?></small></td>
                <td><?=$e($dh['ho_ten'])?><br><small><?=$e($dh['email'])?></small></td>
                <td><?=$e($dh['sdt'])?><br><?=$e($dh['dia_chi'])?></td>
                <td class="order-list-total"><?=view_helper::money($dh['tong_tien'])?></td><td><?=$e($dh['trang_thai'])?><?php if (!empty($dh['tra_hang'])): ?><div class="small text-warning mt-2">Trả hàng: <?=$e($dh['tra_hang']['trang_thai'])?><br><?=$e($dh['tra_hang']['ly_do'])?></div><?php endif; ?></td>
                <td><div class="small mb-2"><?=$e((!empty($dh['thanhtoan_qr']) ? thanhtoan_qr::label($dh['thanhtoan_qr']) : thanhtoan_paypal::label($dh['thanhtoan'] ?? null)))?></div>
                <?php if (!empty($dh['thanhtoan_qr'])) require __DIR__.'/_bank_payment.php'; ?>
                <?php if (!empty($dh['thanhtoan'])): ?><div class="small mb-2">$<?=$e($dh['thanhtoan']['amount_usd'])?> USD<br><?=$e($dh['thanhtoan']['capture_id'] ?? '')?></div>
                    <?php if ($canManageStore): ?><form action="<?=$admin_url?>&amp;act=kiemtrapaypal" method="post" class="mb-2">
                        <input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>"><input type="hidden" name="id" value="<?=$e($dh['id'])?>">
                        <button class="btn btn-outline-primary btn-sm">Đối soát PayPal</button>
                    </form><?php endif; ?>
                <?php endif; ?>
                <?php if ($canManageStore && ($dh['tra_hang']['trang_thai_hoan'] ?? '') === 'Chờ hoàn tiền'): ?><form action="<?=$admin_url?>&amp;act=hoancod" method="post" class="my-2"><input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>"><input type="hidden" name="id" value="<?=(int)$dh['id']?>"><small>Hoàn <?=view_helper::money($dh['tra_hang']['so_tien_hoan'])?></small><input name="tham_chieu" maxlength="255" required placeholder="Mã/chứng từ đã hoàn COD" class="form-control form-control-sm"><button class="btn btn-sm btn-outline-success">Xác nhận đã hoàn COD</button></form><?php endif; ?>
                <?php if ($canManageStore): $options = donhang::trangThaiTiepTheo($dh); if ($options !== []): ?>
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

<?php if (!empty($ds_khieunai)): ?><section class="card mt-4">
    <div class="card-header fw-bold">Khiếu nại khách hàng (<?=count($ds_khieunai)?>)</div>
    <div class="card-body">
        <?php foreach ($ds_khieunai as $message): ?>
            <article class="border rounded-3 p-3 mb-3">
                <div class="d-flex flex-wrap justify-content-between gap-2">
                    <div><h2 class="h6 mb-1">#<?=(int)$message['id']?> · <?=$e($message['tieu_de'])?></h2>
                        <div class="small text-muted"><?=$e($message['ho_ten'])?> · <?=$e($message['email_khach'] ?? '')?> · <?=$e($message['ngay_gui'])?></div></div>
                    <span class="badge <?=empty($message['phan_hoi']) ? 'text-bg-warning' : 'text-bg-success'?> align-self-start"><?=empty($message['phan_hoi']) ? 'Chưa phản hồi' : 'Đã phản hồi'?></span>
                </div>
                <div class="mt-3 p-3 bg-light rounded" style="white-space:pre-wrap"><?=$e($message['noi_dung'])?></div>
                <?php foreach (($message['phan_hoi'] ?? []) as $reply): ?>
                    <div class="mt-3 ms-md-4 p-3 border-start border-3 border-success bg-light rounded-end">
                        <div class="small fw-bold text-success">Phản hồi BP Store · <?=$e($reply['ngay_gui'])?></div>
                        <div class="mt-2" style="white-space:pre-wrap"><?=$e($reply['noi_dung'])?></div>
                    </div>
                <?php endforeach; ?>
                <?php if ($canManageStore): ?>
                    <form action="<?=$admin_url?>&amp;act=phanhoikhieunai" method="post" class="mt-3">
                        <input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>">
                        <input type="hidden" name="id_khieunai" value="<?=(int)$message['id']?>">
                        <label class="form-label fw-semibold" for="reply-complaint-<?=(int)$message['id']?>"><?=empty($message['phan_hoi']) ? 'Soạn phản hồi cho khách' : 'Gửi thêm phản hồi'?></label>
                        <textarea id="reply-complaint-<?=(int)$message['id']?>" name="noi_dung" class="form-control" rows="4" maxlength="5000" required placeholder="Nhập nội dung trả lời…"></textarea>
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2"><small class="text-muted">Phản hồi được lưu trong hộp thư website; hệ thống sẽ gửi thêm email nếu có thể.</small><button class="btn btn-primary">Gửi phản hồi</button></div>
                    </form>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section><?php endif; ?>

