<?php $q=$dh['thanhtoan_qr']; $qid=(int)$dh['id']; ?>
<details class="mb-3"><summary>Đối soát chuyển khoản QR</summary>
    <div class="small mt-2"><?=$e($q['bank_name'])?> · <?=$e($q['account_number'])?><br><?=$e($q['account_name'])?><br><?=view_helper::money($q['amount_vnd'])?> · Nội dung <strong><?=$e($q['transfer_content'])?></strong>
    <?php if ($q['received_reference']): ?><br>Đã nhận: <?=$e($q['received_reference'])?> · NV #<?=(int)$q['confirmed_by']?> · <?=$e($q['received_at'])?><?php endif; ?>
    <?php if ($q['refund_reference']): ?><br>Đã hoàn: <?=$e($q['refund_reference'])?> · NV #<?=(int)$q['refunded_by']?> · <?=$e($q['refunded_at'])?><?php endif; ?></div>
    <?php if ($canManageStore && in_array($q['status'],['waiting','reported','cancelled'],true)): ?>
    <form action="<?=$admin_url?>&amp;act=xacnhanqr" method="post" class="border rounded p-2 mt-2">
        <input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>"><input type="hidden" name="id" value="<?=$qid?>">
        <label class="small">Số tiền thực nhận (VND)<input name="so_tien" type="number" min="1" step="1" required class="form-control form-control-sm"></label>
        <label class="small">Mã giao dịch nhận tiền<input name="tham_chieu" minlength="3" maxlength="100" required class="form-control form-control-sm"></label>
        <label class="small d-block my-2"><input type="checkbox" name="da_kiem_tra" value="1" required> Đã kiểm tra đúng tài khoản, nội dung và đủ tiền trong ngân hàng</label>
        <button class="btn btn-success btn-sm"><?=$q['status']==='cancelled' ? 'Ghi nhận tiền đến sau hủy, chờ hoàn' : 'Xác nhận đã nhận đủ tiền'?></button>
    </form><?php endif; ?>
    <?php if ($canManageStore && $q['status']==='reported'): ?><form action="<?=$admin_url?>&amp;act=kiemtraqr" method="post" class="border rounded p-2 mt-2"><input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>"><input type="hidden" name="id" value="<?=$qid?>"><label class="small d-block mb-2"><input type="checkbox" name="da_kiem_tra" value="1" required> Đã kiểm tra ngân hàng và chưa nhận được tiền</label><button class="btn btn-outline-warning btn-sm">Ghi nhận chưa nhận tiền, cho phép hủy</button></form><?php endif; ?>
    <?php if ($canManageStore && ($q['status']==='refund_due' || ($q['status']==='paid' && in_array($dh['trang_thai'],[donhang::CHO_XAC_NHAN,donhang::XAC_NHAN],true)))): ?>
    <form action="<?=$admin_url?>&amp;act=hoanqr" method="post" class="border rounded p-2 mt-2"><input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>"><input type="hidden" name="id" value="<?=$qid?>"><p class="small mb-1">Hoàn toàn bộ <?=view_helper::money($q['amount_vnd'])?>. Chỉ ghi nhận sau khi đã chuyển lại tiền cho khách. Với đơn chưa giao, sau bước này hãy hủy đơn để hoàn kho.</p><input name="tham_chieu" minlength="3" maxlength="100" required placeholder="Mã giao dịch hoàn tiền" class="form-control form-control-sm"><label class="small d-block my-2"><input type="checkbox" name="da_kiem_tra" value="1" required> Đã kiểm tra giao dịch hoàn tiền thành công</label><button class="btn btn-outline-success btn-sm">Xác nhận đã hoàn chuyển khoản</button></form><?php endif; ?>
</details>
