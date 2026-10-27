<?php
include __DIR__.'/header_admin.php';
$admin_url='index.php?khu=admin';
$posted=!empty($loi)&&isset($_POST['ten_km'])?$_POST:[];
$activeProductIds=array_map('intval',array_column($ds_sanpham,'id'));
$activeCategoryIds=array_map('intval',array_column($ds_danhmuc,'id'));
?>
<div class="container-fluid">
    <div class="admin-shortcuts"><a href="<?=$admin_url?>&amp;act=voucher">Cần mã để khách nhập? Mở Voucher</a></div>
    <?php if($loi!==null): ?><div class="alert alert-danger" role="alert"><?=$e($loi)?></div><?php endif; ?>
    <?php if($thongBao!==null): ?><div class="alert alert-success" role="status"><?=$e($thongBao)?></div><?php endif; ?>
    <details class="card mb-4" id="promotion-form-card" <?=$posted?'open':''?>>
        <summary class="card-header" id="promotion-form-title">Tạo chương trình giảm giá</summary>
        <div class="card-body">
        <?php if(!$ds_sanpham && !$ds_danhmuc): ?><p class="text-muted mb-0">Thêm danh mục và sản phẩm trước khi tạo chương trình.</p><?php else: ?>
        <form action="<?=$admin_url?>&amp;act=themkhuyenmai" method="post" class="row g-3" id="promotionForm">
            <input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>"><input type="hidden" name="id" disabled>
            <div class="col-md-6"><label class="form-label" for="promo-name">Tên chương trình</label><input class="form-control" id="promo-name" name="ten_km" maxlength="150" required></div>
            <div class="col-md-3"><label class="form-label" for="promo-type">Phạm vi áp dụng</label><select class="form-select" name="loai" id="promo-type"><option value="sanpham">Một sản phẩm</option><option value="danhmuc">Một danh mục</option></select></div>
            <div class="col-md-3"><label class="form-label" for="promo-percent">Mức giảm (%)</label><input class="form-control" id="promo-percent" type="number" min="0.01" max="100" step="0.01" name="phan_tram" placeholder="Ví dụ: 10" required></div>
            <div class="col-md-6" data-promo-target="sanpham"><label class="form-label" for="promo-product">Sản phẩm được giảm</label><select class="form-select" id="promo-product" name="id_sanpham" required><option value="">Chọn sản phẩm</option><?php foreach($ds_sanpham as $sp): ?><option value="<?=(int)$sp['id']?>"><?=$e($sp['ma_sp'])?> · <?=$e($sp['ten_sp'])?></option><?php endforeach; ?></select></div>
            <div class="col-md-6" data-promo-target="danhmuc" hidden><label class="form-label" for="promo-category">Danh mục được giảm</label><select class="form-select" id="promo-category" name="id_danhmuc" disabled><option value="">Chọn danh mục</option><?php foreach($ds_danhmuc as $dm): ?><option value="<?=(int)$dm['id']?>"><?=$e($dm['ten_danhmuc'])?></option><?php endforeach; ?></select></div>
            <div class="col-md-3"><label class="form-label" for="promo-start">Ngày bắt đầu</label><input class="form-control" type="date" id="promo-start" name="ngay_bd" value="<?=date('Y-m-d')?>" required></div>
            <div class="col-md-3"><label class="form-label" for="promo-end">Ngày kết thúc</label><input class="form-control" type="date" id="promo-end" name="ngay_kt" value="<?=date('Y-m-d',strtotime('+7 days'))?>" required></div>
            <div class="col-12"><label class="form-label" for="promo-note">Ghi chú nội bộ (không bắt buộc)</label><input class="form-control" id="promo-note" name="ghi_chu" maxlength="255"></div>
            <div class="col-12"><p class="small text-muted mb-0">Áp dụng cả ngày bắt đầu và kết thúc, theo giờ Việt Nam. Trùng nhiều chương trình thì lấy mức giảm cao nhất.</p></div>
            <div class="col-12 d-flex gap-2"><button class="btn btn-primary" id="promotion-submit">Tạo chương trình</button><button type="button" class="btn btn-outline-secondary" id="promotion-reset">Làm mới</button></div>
        </form><?php endif; ?>
        </div>
    </details>
    <?php $tableId='promotions-table';$searchPlaceholder='Tên chương trình, sản phẩm hoặc danh mục';$statusOptions=['Đang chạy'=>'Đang chạy','Sắp bắt đầu'=>'Sắp bắt đầu','Đã hết hạn'=>'Đã hết hạn','Ngừng áp dụng'=>'Ngừng áp dụng'];require __DIR__.'/_table_tools.php'; ?>
    <section class="card mb-4"><div class="card-header fw-bold">Chương trình (<?=count($ds_khuyenmai)?>)</div><div class="table-responsive">
        <table class="table table-hover mb-0" id="promotions-table"><thead><tr><th>Chương trình</th><th>Hàng được áp dụng</th><th>Mức giảm</th><th>Thời hạn</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
        <?php foreach($ds_khuyenmai as $km): $targetActive=$km['loai']==='sanpham'?in_array((int)$km['id_sanpham'],$activeProductIds,true):in_array((int)$km['id_danhmuc'],$activeCategoryIds,true); $state=!$targetActive?'Ngừng áp dụng':(date('Y-m-d')<$km['ngay_bd']?'Sắp bắt đầu':(date('Y-m-d')>$km['ngay_kt']?'Đã hết hạn':'Đang chạy')); ?>
            <tr data-admin-row data-status="<?=$e($state)?>"><td><strong><?=$e($km['ten_km'])?></strong><?php if(!empty($km['ghi_chu'])): ?><div class="small text-muted"><?=$e($km['ghi_chu'])?></div><?php endif; ?></td>
            <td><?=$km['loai']==='sanpham'?'Sản phẩm':'Danh mục'?><br><strong><?=$e($km['loai']==='sanpham'?($km['ten_sp'] ?? 'Sản phẩm không còn bán'):($km['ten_danhmuc'] ?? 'Danh mục không còn bán'))?></strong><?php if(!empty($km['ma_sp'])): ?><div class="small text-muted"><?=$e($km['ma_sp'])?></div><?php endif; ?></td>
            <td><?=$e($km['phan_tram'])?>%</td><td><?=$e(date('d/m/Y',strtotime($km['ngay_bd'])))?> – <?=$e(date('d/m/Y',strtotime($km['ngay_kt'])))?></td><td><span class="badge <?=$state==='Đang chạy'?'text-bg-success':($state==='Sắp bắt đầu'?'text-bg-primary':'text-bg-secondary')?>"><?=$state?></span></td>
            <td><button type="button" class="btn btn-outline-primary btn-sm" data-promotion="<?=$e(json_encode($km,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT))?>">Sửa</button></td></tr>
        <?php endforeach; ?></tbody></table></div>
        <?php if(!$ds_khuyenmai): ?><p class="text-muted p-3 mb-0">Chưa có chương trình giảm giá.</p><?php endif; ?>
    </section>
    <details class="card"><summary class="card-header">Gợi ý chọn hàng để khuyến mãi</summary><div class="card-body"><div class="row g-3">
        <div class="col-md-6"><h2 class="h6">Hàng bán chậm</h2><ul><?php foreach($goi_y_e as $sp): ?><li><?=$e($sp['ten_sp'])?> · <?=$e($sp['ma_sp'])?> · đã bán <?=(int)$sp['da_ban']?></li><?php endforeach; ?></ul><?php if(!$goi_y_e): ?><p class="text-muted mb-0">Chưa có gợi ý.</p><?php endif; ?></div>
        <div class="col-md-6"><h2 class="h6">Hàng gần hết hạn sử dụng</h2><ul><?php foreach($goi_y_can_han as $sp): ?><li><?=$e($sp['ten_sp'])?> · <?=$e($sp['han_su_dung'])?></li><?php endforeach; ?></ul><?php if(!$goi_y_can_han): ?><p class="text-muted mb-0">Không có hàng gần hết hạn.</p><?php endif; ?></div>
    </div><a href="<?=$admin_url?>&amp;act=doanhthu" class="d-inline-block mt-2">Xem báo cáo bán hàng</a></div></details>
</div>
<script type="application/json" id="promotion-posted"><?=json_encode($posted,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?></script>
<script src="<?=$e(view_helper::shopBase())?>/view/js/admin-promotions.js?v=20261022-clean" defer></script>
