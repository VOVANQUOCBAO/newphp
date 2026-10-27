<?php
require __DIR__.'/header_admin.php';
require_once dirname(__DIR__).'/helpers.php';
$sp=$sanpham;
?>
<div class="container-fluid">
    <a class="btn btn-outline-secondary mb-4" href="index.php?khu=admin&amp;act=sanpham">← Danh sách sản phẩm</a>
    <?php if ($sp===null): ?><div class="alert alert-warning">Không tìm thấy sản phẩm.</div><?php else:
        $images=array_values(array_unique(array_filter(array_merge([$sp['hinh']],preg_split('/\R/', $sp['anh_phu'] ?? '')))));
    ?>
    <div class="d-flex justify-content-between flex-wrap gap-2 mb-4"><div><p class="text-muted mb-1">Sản phẩm #<?=(int)$sp['id']?> · <?=$e($sp['ma_sp'])?></p><h1 class="h3"><?=$e($sp['ten_sp'])?></h1></div><span class="badge align-self-start <?=$sp['ngung_ban']?'text-bg-secondary':'text-bg-success'?>"><?=$sp['ngung_ban']?'Đã ngừng bán':'Đang kinh doanh'?></span></div>
    <div class="row g-4 mb-4"><section class="col-lg-5"><div class="card p-3 h-100">
        <img src="<?=$e(view_helper::productImage($sp['hinh']))?>" alt="<?=$e($sp['ten_sp'])?>" style="height:320px;width:100%;object-fit:contain">
        <div class="d-flex flex-wrap gap-2 mt-3"><?php foreach($images as $image): ?><a href="<?=$e(view_helper::productImage($image))?>" target="_blank" rel="noopener" title="Mở ảnh đầy đủ"><img src="<?=$e(view_helper::productImage($image))?>" width="88" height="88" class="border rounded p-1" style="object-fit:contain" alt="<?=$e($sp['ten_sp'])?>"></a><?php endforeach; ?></div>
    </div></section><section class="col-lg-7"><div class="card p-4 h-100"><h2 class="h5">Thông tin kinh doanh</h2>
        <dl class="row mb-0">
            <?php foreach(['Mã sản phẩm'=>$sp['ma_sp'],'Danh mục'=>$sp['ten_danhmuc'],'Thương hiệu'=>$sp['thuong_hieu'] ?? '', 'Bảo hành'=>$sp['bao_hanh'] ?? '', 'Giá niêm yết'=>view_helper::money($sp['gia']), 'Giá bán hiện tại'=>view_helper::money($sp['gia_km']), 'Tổng số lượng nhập'=>$sp['so_luong_goc'], 'Tồn kho có thể bán'=>$sp['so_luong_ton'], 'Hàng lỗi / cách ly'=>$sp['so_luong_loi'] ?? 0, 'Đã bán (đơn thành công)'=>$sp['da_ban'], 'Ngày nhập đầu tiên'=>$sp['ngay_nhap'] ?? '', 'Ngày tạo'=>$sp['ngay_tao'] ?? '', 'Hạn sử dụng'=>$sp['han_su_dung'] ?? '', 'Đánh giá hiển thị'=>$sp['sao_tb'].' / 5 · '.$sp['so_danh_gia'].' lượt'] as $label=>$value): ?>
            <dt class="col-sm-6 py-2 border-bottom"><?=$e($label)?></dt><dd class="col-sm-6 py-2 border-bottom mb-0"><?=$e($value===''?'Chưa có thông tin':$value)?></dd><?php endforeach; ?>
        </dl></div></section></div>
    <div class="row g-4 mb-4"><?php foreach(['Mô tả sản phẩm'=>'mo_ta','Thông số kỹ thuật'=>'thong_so'] as $label=>$key): ?><section class="col-lg-6"><div class="card p-4 h-100"><h2 class="h5"><?=$e($label)?></h2><div style="white-space:pre-wrap;overflow-wrap:anywhere"><?=$e(trim($sp[$key] ?? '') ?: 'Chưa có thông tin.')?></div></div></section><?php endforeach; ?></div>
    <section class="card mb-4"><div class="card-header fw-bold">Các đợt nhập gần nhất (tối đa 50)</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Ngày nhập</th><th>Số lượng</th><th>Người nhập</th><th>Ghi chú</th></tr></thead><tbody>
        <?php foreach($sp['lichsu_nhap'] as $row): ?><tr><td><?=$e($row['ngay_nhap'])?></td><td><?=(int)$row['so_luong']?></td><td><?=$e($row['nguoi_nhap'])?></td><td><?=$e($row['ghi_chu'])?></td></tr><?php endforeach; ?>
        <?php if(!$sp['lichsu_nhap']): ?><tr><td colspan="4" class="text-muted">Chưa có đợt nhập được ghi nhận.</td></tr><?php endif; ?>
    </tbody></table></div></section>
    <section class="card"><div class="card-header fw-bold">Biến động kho gần nhất (tối đa 50)</div><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Thời gian</th><th>Thay đổi</th><th>Tồn sau</th><th>Đơn hàng</th><th>Lý do</th></tr></thead><tbody>
        <?php foreach($sp['lichsu_kho'] as $row): ?><tr><td><?=$e($row['ngay_tao'])?></td><td><?=((int)$row['thay_doi']>0?'+':'').(int)$row['thay_doi']?></td><td><?=(int)$row['ton_sau']?></td><td><?php if($row['id_donhang']): ?><a href="index.php?khu=admin&amp;act=chitietdonhang&amp;id=<?=(int)$row['id_donhang']?>">#<?=(int)$row['id_donhang']?></a><?php else: ?>—<?php endif; ?></td><td><?=$e($row['ly_do'])?></td></tr><?php endforeach; ?>
        <?php if(!$sp['lichsu_kho']): ?><tr><td colspan="5" class="text-muted">Chưa có biến động kho.</td></tr><?php endif; ?>
    </tbody></table></div></section>
    <?php endif; ?>
</div>
