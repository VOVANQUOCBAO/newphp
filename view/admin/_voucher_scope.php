<?php
$scopeValue=$scopeData['pham_vi'] ?? 'tatca';
$selectedDm=(int)($scopeData['id_danhmuc'] ?? 0);
$selectedSp=(int)($scopeData['id_sanpham'] ?? 0);
?>
<div class="col-12 voucher-scope-fields">
    <div class="row g-2">
        <div class="col-md-4"><label for="<?=$e($scopeFormId)?>-scope" class="form-label">Phạm vi áp dụng</label><select id="<?=$e($scopeFormId)?>-scope" name="pham_vi" class="form-select voucher-scope-select"><option value="tatca" <?=$scopeValue==='tatca' ? 'selected' : ''?>>Toàn cửa hàng</option><option value="danhmuc" <?=$scopeValue==='danhmuc' ? 'selected' : ''?>>Theo danh mục</option><option value="sanpham" <?=$scopeValue==='sanpham' ? 'selected' : ''?>>Theo sản phẩm</option></select></div>
        <div class="col-md-8" data-voucher-target="danhmuc"><label for="<?=$e($scopeFormId)?>-dm" class="form-label">Danh mục được áp dụng</label><select id="<?=$e($scopeFormId)?>-dm" name="id_danhmuc" class="form-select"><option value="">Chọn danh mục</option><?php foreach ($ds_danhmuc as $dm): ?><option value="<?=(int)$dm['id']?>" <?=(int)$dm['id']===$selectedDm ? 'selected' : ''?>><?=$e($dm['ten_danhmuc'])?></option><?php endforeach; ?></select></div>
        <div class="col-md-8" data-voucher-target="sanpham"><label for="<?=$e($scopeFormId)?>-sp" class="form-label">Sản phẩm được áp dụng</label><select id="<?=$e($scopeFormId)?>-sp" name="id_sanpham" class="form-select"><option value="">Chọn sản phẩm</option><?php foreach ($ds_sanpham as $sp): ?><option value="<?=(int)$sp['id']?>" <?=(int)$sp['id']===$selectedSp ? 'selected' : ''?>><?=$e($sp['ma_sp'].' · '.$sp['ten_sp'])?></option><?php endforeach; ?></select></div>
    </div>
</div>
