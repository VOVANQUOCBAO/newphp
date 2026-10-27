<?php require __DIR__.'/header_admin.php'; ?>
<div class="container-fluid"><a href="index.php?khu=admin&amp;act=khachhang" class="btn btn-outline-secondary mb-4">← Danh sách khách hàng</a><h1 class="h3 mb-4">Điểm thành viên · <?=$e($bao_cao['khach']['ho_ten'])?> (#<?=(int)$bao_cao['khach']['id']?>)</h1>
<?php $pointsAdmin=true; require dirname(__DIR__).'/_diem_thanhvien.php'; ?>
</div>
