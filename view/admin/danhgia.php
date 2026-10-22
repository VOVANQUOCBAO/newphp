<?php
include __DIR__ . '/header_admin.php';
$admin_url = 'index.php?khu=admin';
?>
<div class="container-fluid">
    <?php if ($loi !== null): ?><div class="alert alert-danger"><?=$e($loi)?></div><?php endif; ?>
    <?php if ($thongBao !== null): ?><div class="alert alert-success"><?=$e($thongBao)?></div><?php endif; ?>
    <?php $tableId='reviews-table';$searchPlaceholder='Khách hàng, sản phẩm hoặc nội dung';$statusOptions=['1'=>'Hiển thị','0'=>'Đã ẩn'];require __DIR__.'/_table_tools.php'; ?>
    <section class="card"><div class="card-header fw-bold">Đánh giá từ khách hàng (<?=count($ds_danhgia)?>)</div><div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="reviews-table"><thead><tr><th>Ngày</th><th>Khách hàng</th><th>Sản phẩm</th><th>Điểm</th><th>Nội dung</th><th>Trạng thái</th><th>Kiểm duyệt</th></tr></thead>
            <tbody><?php foreach ($ds_danhgia as $dg): ?><tr data-admin-row data-status="<?=(int)$dg['trang_thai']?>">
                <td><?=$e($dg['ngay_tao'])?></td><td><?=$e($dg['ho_ten'])?><br><small><?=$e($dg['email'])?></small></td>
                <td><?=$e($dg['ten_sp'])?></td><td><?=$e($dg['so_sao'])?>/5</td><td><?=$e($dg['noi_dung'])?></td>
                <td><?=$dg['trang_thai'] ? 'Hiển thị' : 'Đã ẩn'?></td><td>
                        <form action="<?=$admin_url?>&amp;act=khoadanhgia" method="post" class="d-flex gap-1">
                            <input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>">
                            <input type="hidden" name="id" value="<?=$e($dg['id'])?>"><input type="hidden" name="trang_thai" value="<?=$dg['trang_thai']?'0':'1'?>"><button class="btn btn-sm btn-outline-secondary"><?=$dg['trang_thai']?'Ẩn nhận xét':'Hiển thị lại'?></button>
                        </form>
                </td>
            </tr><?php endforeach; ?></tbody>
        </table></div>
        <?php if ($ds_danhgia === []): ?><p class="text-muted p-3 mb-0">Chưa có đánh giá.</p><?php endif; ?>
    </section>
</div>
