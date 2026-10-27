<?php
require_once __DIR__ . '/helpers.php';
$sp = $chitiet;
$isFavorite = $isFavorite ?? false;
$price = (float)($sp['gia_km'] ?? $sp['gia']);
$oldPrice = (float)($sp['gia_goc'] ?? $sp['gia']);
$stock = (int)$sp['so_luong_ton'];
$images = array_filter(array_merge([$sp['hinh']], preg_split('/\\R/', $sp['anh_phu'] ?? '')));
?>
<main class="container py-4">
    <nav class="small mb-4"><a href="index.php?act=trangchu">Sản phẩm</a> / <?=$e($sp['ten_sp'])?></nav>
    <?php if (!empty($loi)): ?><div class="alert alert-danger"><?=$e($loi)?></div><?php endif; ?>
    <?php if (isset($_GET['added'])): ?><div class="alert alert-success">Đã thêm vào giỏ hàng. <a href="index.php?act=giohang">Xem giỏ hàng</a></div><?php endif; ?>
    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <div class="card p-4"><img id="mainProductImage" src="<?=$e(view_helper::productImage($sp['hinh']))?>" alt="<?=$e($sp['ten_sp'])?>" style="width:100%;height:380px;object-fit:contain"></div>
            <?php if (count($images) > 1): ?><div class="d-flex flex-wrap gap-2 mt-3">
                <?php foreach ($images as $image): ?><button type="button" class="btn btn-light border product-thumbnail" data-image="<?=$e(view_helper::productImage($image))?>" aria-label="Xem ảnh sản phẩm"><img src="<?=$e(view_helper::productImage($image))?>" alt="" width="64" height="64" style="object-fit:contain"></button><?php endforeach; ?>
            </div><?php endif; ?>
        </div>
        <div class="col-lg-6">
            <p class="text-muted mb-2"><?=$e($sp['thuong_hieu'] ?? '')?> · Mã <?=$e($sp['ma_sp'])?></p>
            <h1 class="h2 fw-bold"><?=$e($sp['ten_sp'])?></h1>
            <p class="text-muted">Đã bán <?=(int)$sp['da_ban']?> · <?=$stock > 0 ? 'Còn ' . $stock . ' sản phẩm' : 'Tạm hết hàng'?></p>
            <div class="bg-light rounded-4 p-4 my-4">
                <?php if ($oldPrice > $price): ?>
                    <div class="product-detail-price-block">
                        <div class="product-price-row product-price-row-original">
                            <span class="product-price-label">Giá gốc</span>
                            <del><?=view_helper::money($oldPrice)?></del>
                        </div>
                        <div class="product-price-row product-price-row-current">
                            <span class="product-price-label">Giá khuyến mãi</span>
                            <strong><?=view_helper::money($price)?></strong>
                        </div>
                        <small class="product-price-saving">Tiết kiệm <?=view_helper::money($oldPrice - $price)?></small>
                    </div>
                <?php else: ?>
                    <div class="product-price-row product-price-row-current">
                        <span class="product-price-label">Giá bán</span>
                        <strong><?=view_helper::money($price)?></strong>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($sp['bao_hanh'])): ?><p><strong>Bảo hành:</strong> <?=$e($sp['bao_hanh'])?></p><?php endif; ?>
            <form action="index.php?act=themgiohang" method="post">
                <input type="hidden" name="_csrf" value="<?=$e($userCsrf)?>">
                <input type="hidden" name="id" value="<?=(int)$sp['id']?>">
                <label class="form-label" for="buyQty">Số lượng</label>
                <input type="number" id="buyQty" name="sl" class="form-control mb-3" value="1" min="1" max="<?=$stock?>" style="max-width:130px" required <?=$stock <= 0 ? 'disabled' : ''?>>
                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" formaction="index.php?act=muangay" class="btn btn-primary btn-lg" <?=$stock <= 0 ? 'disabled' : ''?>>Mua ngay</button>
                    <button type="submit" class="btn btn-outline-primary btn-lg" <?=$stock <= 0 ? 'disabled' : ''?>>Thêm vào giỏ hàng</button>
                </div>
            </form>
            <form action="index.php?act=luuyeuthich" method="post" class="mt-3"><input type="hidden" name="_csrf" value="<?=$e($userCsrf)?>"><input type="hidden" name="id_sanpham" value="<?=(int)$sp['id']?>"><button class="btn btn-outline-danger" name="luu" value="<?=$isFavorite?'0':'1'?>"><i class="<?=$isFavorite?'fas':'far'?> fa-heart me-1"></i><?=$isFavorite?'Bỏ yêu thích':'Thêm vào yêu thích'?></button></form>
        </div>
    </div>
    <div class="row g-4">
        <section class="col-lg-7"><div class="card p-4 h-100">
            <h2 class="h4">Mô tả sản phẩm</h2>
            <div style="white-space:pre-wrap;overflow-wrap:anywhere"><?=$e(trim($sp['mo_ta'] ?? '') ?: 'Chưa có mô tả bổ sung.')?></div>
        </div></section>
        <?php if (!empty($sp['thong_so'])): ?><section class="col-lg-5"><div class="card p-4 h-100">
            <h2 class="h4">Thông số kỹ thuật</h2>
            <div style="white-space:pre-wrap;overflow-wrap:anywhere"><?=$e($sp['thong_so'])?></div>
        </div></section><?php endif; ?>
    </div>
    <section id="review" class="card p-4 my-4">
        <h2 class="h4">Đánh giá &amp; nhận xét (<?=count($ds_danhgia ?? [])?>)</h2>
        <?php if ($duocDanhGia ?? false): ?>
            <form action="index.php?act=themdanhgia" method="post" class="my-3">
                <input type="hidden" name="_csrf" value="<?=$e($userCsrf)?>">
                <input type="hidden" name="id_sanpham" value="<?=(int)$sp['id']?>">
                <label for="reviewScore" class="form-label">Mức độ hài lòng</label>
                <select id="reviewScore" name="so_sao" class="form-select mb-3" style="max-width:240px"><option value="5">Rất hài lòng</option><option value="4">Hài lòng</option><option value="3">Bình thường</option><option value="2">Chưa hài lòng</option><option value="1">Không hài lòng</option></select>
                <label for="reviewContent" class="form-label">Trải nghiệm của bạn</label>
                <textarea id="reviewContent" name="noi_dung" rows="3" maxlength="5000" class="form-control mb-3" required></textarea>
                <button class="btn btn-primary">Gửi nhận xét</button>
            </form>
        <?php else: ?><p class="text-muted">Chỉ khách đã nhận sản phẩm được viết nhận xét, mỗi sản phẩm một lần.</p><?php endif; ?>
        <?php foreach (($ds_danhgia ?? []) as $review): ?>
            <article class="border-top py-3">
                <strong><?=$e($review['ho_ten'])?></strong> <span class="badge text-bg-light">Đã mua hàng</span>
                <small class="text-muted ms-2"><?=$e($review['ngay_tao'])?></small>
                <p class="mb-0 mt-2" style="white-space:pre-wrap;overflow-wrap:anywhere"><?=$e($review['noi_dung'])?></p>
            </article>
        <?php endforeach; ?>
        <?php if (empty($ds_danhgia)): ?><p class="text-muted mb-0">Chưa có nhận xét.</p><?php endif; ?>
    </section>
    <?php if (!empty($ds_lienquan)): ?><section class="my-5">
        <h2 class="h4 mb-3">Sản phẩm liên quan</h2>
        <div class="row g-3"><?php foreach ($ds_lienquan as $related): ?><div class="col-6 col-lg-3"><a class="card h-100 p-3 text-decoration-none" href="index.php?act=chitiet&amp;id=<?=(int)$related['id']?>">
            <img src="<?=$e(view_helper::productImage($related['hinh']))?>" alt="<?=$e($related['ten_sp'])?>" height="150" style="object-fit:contain">
            <h3 class="h6 mt-3"><?=$e($related['ten_sp'])?></h3><strong class="text-danger"><?=view_helper::money($related['gia_km'] ?? $related['gia'])?></strong>
        </a></div><?php endforeach; ?></div>
    </section><?php endif; ?>
</main>
<script>document.querySelectorAll('.product-thumbnail').forEach(button => button.addEventListener('click', () => {document.getElementById('mainProductImage').src = button.dataset.image;}));</script>
