<?php
$id = (int)$sp['id'];
$ten = htmlspecialchars($sp['ten_sp'], ENT_QUOTES, 'UTF-8');
$hinh = htmlspecialchars(view_helper::productImage($sp['hinh'] ?? null), ENT_QUOTES, 'UTF-8');
$giaGoc = (float)($sp['gia_goc'] ?? $sp['gia']);
$giaKm = (float)($sp['gia_km'] ?? $sp['gia']);
$hetHang = (int)($sp['so_luong_ton'] ?? 0) <= 0 || !empty($sp['het_hang']);
$phanTramGiam = $giaGoc > $giaKm && $giaGoc > 0 ? (int)round(($giaGoc - $giaKm) / $giaGoc * 100) : 0;
$favorite = !empty($sp['yeu_thich']);
?>
<div class="col-6 col-md-4 col-lg-3">
    <article class="card h-100 product-card <?=$hetHang ? 'out-of-stock' : ''?>">
        <?php if (!$hetHang && $phanTramGiam > 0): ?>
            <span class="badge product-badge badge-tag-sale">-<?=$phanTramGiam?>%</span>
        <?php elseif (!$hetHang && $badgeType === 'new'): ?>
            <span class="badge product-badge badge-tag-new">Mới</span>
        <?php elseif (!$hetHang && (int)($sp['da_ban'] ?? 0) > 0): ?>
            <span class="badge product-badge badge-tag-hot">Bán chạy</span>
        <?php endif; ?>
        <form action="index.php?act=luuyeuthich" method="post" class="product-favorite">
            <input type="hidden" name="_csrf" value="<?=htmlspecialchars($userCsrf, ENT_QUOTES, 'UTF-8')?>">
            <input type="hidden" name="id_sanpham" value="<?=$id?>">
            <button type="submit" name="luu" value="<?=$favorite ? '0' : '1'?>" aria-label="<?=$favorite ? 'Bỏ yêu thích' : 'Yêu thích'?>: <?=$ten?>" title="<?=$favorite ? 'Bỏ yêu thích' : 'Yêu thích'?>" aria-pressed="<?=$favorite ? 'true' : 'false'?>">
                <i class="<?=$favorite ? 'fas' : 'far'?> fa-heart" aria-hidden="true"></i>
            </button>
        </form>
        <a href="index.php?act=chitiet&amp;id=<?=$id?>" class="product-img-wrapper">
            <img src="<?=$hinh?>" alt="<?=$ten?>" loading="lazy" width="240" height="190">
        </a>
        <div class="card-body d-flex flex-column">
            <a href="index.php?act=chitiet&amp;id=<?=$id?>" class="product-title" title="<?=$ten?>"><?=$ten?></a>
            <div class="product-price-details mt-auto" aria-label="Giá sản phẩm">
                <?php if ($phanTramGiam > 0): ?>
                    <span class="product-price-row-original"><span class="visually-hidden">Giá gốc </span><del><?=view_helper::money($giaGoc)?></del></span>
                <?php endif; ?>
                <span class="product-price-row-current"><span class="visually-hidden"><?=$phanTramGiam > 0 ? 'Giá khuyến mãi' : 'Giá bán'?> </span><strong><?=view_helper::money($giaKm)?></strong></span>
            </div>
            <p class="product-availability <?=$hetHang ? 'is-unavailable' : 'is-available'?>"><span aria-hidden="true"></span><?=$hetHang ? 'Tạm hết hàng' : 'Còn hàng'?></p>
        </div>
        <div class="card-footer product-actions">
            <?php if ($hetHang): ?>
                <button type="button" class="btn btn-secondary w-100" disabled>Tạm hết hàng</button>
            <?php else: ?>
                <form action="index.php?act=muangay" method="post">
                    <input type="hidden" name="_csrf" value="<?=htmlspecialchars($userCsrf, ENT_QUOTES, 'UTF-8')?>">
                    <input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="sl" value="1">
                    <button class="btn btn-primary w-100">Mua ngay</button>
                </form>
                <a href="index.php?act=chitiet&amp;id=<?=$id?>" class="product-detail-link">Xem sản phẩm</a>
            <?php endif; ?>
        </div>
    </article>
</div>
