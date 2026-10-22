<?php
include __DIR__ . '/header_admin.php';
$admin_url = 'index.php?khu=admin';
$birthdayPosted=!empty($loi)&&isset($_POST['tieu_de'])?$_POST:[];
?>
<div class="container-fluid">
    <?php if ($loi !== null): ?><div class="alert alert-danger"><?=$e($loi)?></div><?php endif; ?>
    <?php if ($thongBao !== null): ?><div class="alert alert-success"><?=$e($thongBao)?></div><?php endif; ?>
    <?php if ($isSuperAdmin): ?><details class="card mb-4" <?=$birthdayPosted?'open':''?>><summary class="card-header">Gửi thư theo ngày sinh</summary><div class="card-body"><p class="small text-muted">Chọn ít nhất một điều kiện ngày sinh. Thư được gửi đến các khách phù hợp; thao tác này không tạo mã giảm giá hay chuyển quà.</p>
        <form action="<?=$admin_url?>&amp;act=guiquatang" method="post" class="row g-3">
            <input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>">
            <div class="col-md-2"><label class="form-label">Ngày</label><input class="form-control" type="number" min="1" max="31" name="ngay" value="<?=$e($birthdayPosted['ngay'] ?? '')?>"></div>
            <div class="col-md-2"><label class="form-label">Tháng</label><input class="form-control" type="number" min="1" max="12" name="thang" value="<?=$e($birthdayPosted['thang'] ?? '')?>"></div>
            <div class="col-md-2"><label class="form-label">Năm</label><input class="form-control" type="number" min="1900" max="<?=date('Y')?>" name="nam" value="<?=$e($birthdayPosted['nam'] ?? '')?>"></div>
            <div class="col-md-6"><label class="form-label">Tiêu đề</label><input class="form-control" name="tieu_de" value="<?=$e($birthdayPosted['tieu_de'] ?? '')?>" required></div>
            <div class="col-12"><label class="form-label">Nội dung</label><textarea class="form-control" name="noi_dung" rows="3" required><?=$e($birthdayPosted['noi_dung'] ?? '')?></textarea></div>
            <div class="col-12"><button class="btn btn-primary">Gửi thư cho nhóm đã chọn</button></div>
        </form>
    </div></details><?php endif; ?>
    <?php $tableId='customers-table';$searchPlaceholder='Tên, email hoặc hạng thành viên';$statusOptions=['1'=>'Đang hoạt động','0'=>'Đã khóa'];require __DIR__.'/_table_tools.php'; ?>
    <section class="card mb-4"><div class="card-header fw-bold">Khách hàng (<?=count($ds_khachhang)?>)</div><div class="table-responsive">
        <table class="table table-hover mb-0" id="customers-table"><thead><tr><th>ID</th><th>Khách hàng</th><th>Ngày sinh</th><th>Hạng / điểm</th><th>Đơn đã tạo / tổng giá trị</th><th>Trạng thái</th><?php if ($isSuperAdmin): ?><th></th><?php endif; ?></tr></thead>
            <tbody><?php foreach ($ds_khachhang as $kh): ?><tr data-admin-row data-status="<?=(int)$kh['trang_thai']?>">
                <td><?=$e($kh['id'])?></td><td><?=$e($kh['ho_ten'])?><br><small><?=$e($kh['email'])?></small></td>
                <td><?=$e($kh['ngay_sinh'] ?? '—')?></td><td><?=$e($kh['hang'])?> / <?=$e($kh['diem'])?></td>
                <td><?=$e($kh['tong_don'])?> đơn<br><?=number_format((float)$kh['tong_tien'], 0, ',', '.')?>đ</td>
                <td><?=$kh['trang_thai'] ? 'Đang hoạt động' : 'Đã khóa'?></td>
                <?php if ($isSuperAdmin): ?><td><form action="<?=$admin_url?>&amp;act=khoakhachhang" method="post">
                    <input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>">
                    <input type="hidden" name="id" value="<?=$e($kh['id'])?>"><input type="hidden" name="trang_thai" value="<?=$kh['trang_thai'] ? '0' : '1'?>">
                    <button class="btn btn-sm <?=$kh['trang_thai'] ? 'btn-outline-danger' : 'btn-outline-success'?>"><?=$kh['trang_thai'] ? 'Khóa' : 'Mở khóa'?></button>
                </form></td><?php endif; ?>
            </tr><?php endforeach; ?></tbody>
        </table></div>
        <?php if ($ds_khachhang === []): ?><p class="text-muted p-3 mb-0">Chưa có khách hàng.</p><?php endif; ?>
        <p class="small text-muted px-3 mb-3">Tổng giá trị các đơn đã tạo gồm cả đơn hủy/trả, không phải doanh thu hoặc chi tiêu thành công. Hạng và điểm được xử lý theo đơn thành công.</p>
    </section>
    <section class="card"><div class="card-header fw-bold">Khách hàng có lần hủy/bom/trả hàng (<?=count($ds_bom_huy_tra)?>)</div><div class="table-responsive">
        <table class="table table-sm mb-0"><thead><tr><th>Khách hàng</th><th>Hủy</th><th>Bom</th><th>Trả</th></tr></thead>
            <tbody><?php foreach ($ds_bom_huy_tra as $kh): ?><tr><td><?=$e($kh['ho_ten'])?> (<?=$e($kh['email'])?>)</td><td><?=$e($kh['so_lan_huy'])?></td><td><?=$e($kh['so_lan_bom'])?></td><td><?=$e($kh['so_lan_tra'])?></td></tr><?php endforeach; ?></tbody>
        </table></div>
        <?php if ($ds_bom_huy_tra === []): ?><p class="text-muted p-3 mb-0">Chưa có lịch sử hủy, bom hoặc trả hàng.</p><?php endif; ?>
    </section>
</div>
