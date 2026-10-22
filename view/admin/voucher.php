<?php
include __DIR__ . '/header_admin.php';
$admin_url = 'index.php?khu=admin';
$posted=!empty($loi) && isset($_POST['ma_code']) ? $_POST : [];
?>
<div class="container-fluid">
    <div class="admin-shortcuts"><a href="<?=$admin_url?>&amp;act=khuyenmai">Cần giảm giá sẵn trên sản phẩm? Mở Giảm giá sản phẩm</a></div>
    <?php if ($loi !== null): ?><div class="alert alert-danger"><?=$e($loi)?></div><?php endif; ?>
    <?php if ($thongBao !== null): ?><div class="alert alert-success"><?=$e($thongBao)?></div><?php endif; ?>
    <?php if (!$scopeReady): ?><form action="<?=$admin_url?>&amp;act=khoitaophamvivoucher" method="post" class="alert alert-info"><input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>"><p>Khởi tạo phạm vi sản phẩm/danh mục. Voucher cũ giữ nguyên toàn cửa hàng.</p><button class="btn btn-primary btn-sm">Bật phạm vi voucher</button></form><?php endif; ?>
    <details class="card mb-4" <?=$posted?'open':''?>><summary class="card-header">Tạo voucher</summary><div class="card-body">
        <form action="<?=$admin_url?>&amp;act=themvoucher" method="post" class="row g-3" id="createVoucherForm">
            <input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>">
            <div class="col-md-3"><label class="form-label">Mã</label><input class="form-control" name="ma_code" maxlength="50" required></div>
            <div class="col-md-2"><label class="form-label">Loại</label><select class="form-select" name="loai"><option value="tien">Giảm tiền</option><option value="phantram">Phần trăm</option><option value="mienship">Miễn ship</option></select></div>
            <div class="col-md-2"><label class="form-label">Giá trị giảm</label><input class="form-control" type="number" min="0.01" step="0.01" name="gia_tri" placeholder="Ví dụ: 99000" required><small data-voucher-value-hint>Nhập số tiền giảm bằng đồng.</small></div>
            <div class="col-md-2"><label class="form-label">Số lượt phát hành</label><input class="form-control" type="number" min="0" name="so_luong" required></div>
            <div class="col-md-3"><label class="form-label">Ngày bắt đầu</label><input class="form-control" type="date" name="ngay_bd" value="<?=date('Y-m-d')?>" required></div>
            <div class="col-md-3"><label class="form-label">Ngày kết thúc</label><input class="form-control" type="date" name="ngay_kt" value="<?=date('Y-m-d', strtotime('+30 days'))?>" required></div>
            <div class="col-md-4"><label class="form-label">Hạng tối thiểu</label><select name="hang_ap_dung" class="form-select"><option value="">Mọi hạng</option><option value="Silver">Silver trở lên</option><option value="Gold">Gold trở lên</option><option value="Diamond">Diamond</option></select></div>
            <div class="col-md-4"><label class="form-label">Tiền hàng áp dụng tối thiểu</label><input type="number" class="form-control" name="don_toi_thieu" min="0" step="0.01" value="0" required></div>
            <?php if ($scopeReady): $scopeData=$posted; $scopeFormId='new-voucher'; require __DIR__.'/_voucher_scope.php'; endif; ?>
            <p class="small text-muted mb-0">Chỉ tính giảm trên hàng thuộc phạm vi đã chọn. Tên mã không tự xác định danh mục hoặc sản phẩm.</p>
            <div class="col-12"><button class="btn btn-primary">Tạo voucher</button></div>
        </form>
    </div></details>
    <?php $tableId='vouchers-table';$searchPlaceholder='Mã, phạm vi hoặc điều kiện';$statusOptions=['Đang hiệu lực'=>'Đang hiệu lực','Sắp bắt đầu'=>'Sắp bắt đầu','Đã hết hạn'=>'Đã hết hạn','Hết lượt'=>'Hết lượt','Cần sửa'=>'Cần sửa'];require __DIR__.'/_table_tools.php'; ?>
    <section class="card"><div class="card-header fw-bold">Voucher (<?=count($ds_voucher)?>)</div><div class="table-responsive">
        <table class="table table-hover mb-0" id="vouchers-table"><thead><tr><th>Mã</th><th>Loại</th><th>Giá trị</th><th>Còn lượt</th><th>Thời hạn / trạng thái</th><th>Điều kiện</th><th></th></tr></thead>
            <tbody><?php foreach ($ds_voucher as $vc): $vcState=voucher::loiGiaTri($vc['loai'],$vc['gia_tri'])!==null?'Cần sửa':(date('Y-m-d')<$vc['ngay_bd']?'Sắp bắt đầu':(date('Y-m-d')>$vc['ngay_kt']?'Đã hết hạn':((int)$vc['so_luong']<=0?'Hết lượt':'Đang hiệu lực'))); ?><tr data-admin-row data-status="<?=$e($vcState)?>">
                <td><?=$e($vc['ma_code'])?><?php if (voucher::loiGiaTri($vc['loai'],$vc['gia_tri'])!==null): ?><div class="text-danger small">Chưa có mức giảm hợp lệ · không áp dụng được</div><?php endif; ?></td><td><?=$e(['tien'=>'Giảm tiền','phantram'=>'Phần trăm','mienship'=>'Miễn ship'][$vc['loai']] ?? $vc['loai'])?></td><td><?=$vc['loai']==='tien' ? money($vc['gia_tri']) : ($vc['loai']==='phantram' ? $e($vc['gia_tri']).'%' : 'Miễn phí giao hàng')?></td><td><?=$e($vc['so_luong'])?></td>
                <td><?=$e(date('d/m/Y',strtotime($vc['ngay_bd'])))?> – <?=$e(date('d/m/Y',strtotime($vc['ngay_kt'])))?><div class="mt-1"><span class="badge <?=$vcState==='Đang hiệu lực'?'text-bg-success':'text-bg-secondary'?>"><?=$e($vcState)?></span></div></td><td><?=$e($vc['hang_ap_dung'] ? $vc['hang_ap_dung'].' trở lên' : 'Mọi hạng')?><br>Tiền hàng áp dụng từ <?=money($vc['don_toi_thieu'])?><br><strong><?=$e(voucher::scopeLabel($vc))?></strong>
                <?php if ($scopeReady): ?><details class="mt-2"><summary>Sửa giá trị / phạm vi</summary><form action="<?=$admin_url?>&amp;act=suaphamvivoucher" method="post" class="border rounded p-2 mt-2"><input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>"><input type="hidden" name="id" value="<?=(int)$vc['id']?>"><label class="form-label" for="voucher-<?=(int)$vc['id']?>-value">Giá trị giảm (<?=$vc['loai']==='phantram' ? '%' : 'đ'?>)</label><input id="voucher-<?=(int)$vc['id']?>-value" class="form-control mb-2" type="number" name="gia_tri" min="<?=$vc['loai']==='mienship' ? '0' : '0.01'?>" <?=$vc['loai']==='phantram' ? 'max="100"' : ''?> step="0.01" value="<?=$e($vc['gia_tri'])?>" <?=$vc['loai']==='mienship' ? 'readonly' : ''?> required><?php $scopeData=$vc; $scopeFormId='voucher-'.(int)$vc['id']; require __DIR__.'/_voucher_scope.php'; ?><button class="btn btn-sm btn-primary mt-2">Lưu voucher</button><p class="small text-muted mb-0 mt-2">Đơn đã đặt và lượt còn lại không thay đổi.</p></form></details><?php endif; ?></td>
                <td><form action="<?=$admin_url?>&amp;act=xoavoucher" method="post" onsubmit="return confirm('Xóa voucher này?')"><input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>"><input type="hidden" name="id" value="<?=$e($vc['id'])?>"><button class="btn btn-sm btn-outline-danger">Xóa</button></form></td>
            </tr><?php endforeach; ?></tbody>
        </table></div>
        <?php if ($ds_voucher === []): ?><p class="text-muted p-3 mb-0">Chưa có voucher.</p><?php endif; ?>
    </section>
</div>
<script>
(() => {
    const posted=<?=json_encode($posted,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;
    const form=document.getElementById('createVoucherForm');
    Object.entries(posted).forEach(([key,value])=>{const input=form.elements.namedItem(key);if(input && input.type!=='hidden')input.value=value;});
})();
</script>
<script src="<?=$e(shopBase())?>/view/js/voucher-scope.js?v=20261022-usage"></script>
