<?php require_once __DIR__.'/helpers.php'; ?>
<main class="container py-4"><h1 class="h3 mb-4">Sản phẩm yêu thích</h1>
    <?php if ($loi): ?><div class="alert alert-danger"><?=$e($loi)?></div><?php endif; ?>
    <?php if ($thongBao): ?><div class="alert alert-success"><?=$e($thongBao)?></div><?php endif; ?>
    <?php if (!$products): ?><div class="card p-4"><p>Bạn chưa lưu sản phẩm nào đang bán.</p><a href="index.php?act=trangchu">Khám phá sản phẩm</a></div><?php endif; ?>
    <div class="row g-3"><?php foreach ($products as $product): ?><article class="col-md-4 col-lg-3"><div class="card h-100 p-3">
        <a href="index.php?act=chitiet&amp;id=<?=(int)$product['id']?>"><img src="<?=$e(view_helper::productImage($product['hinh']))?>" alt="<?=$e($product['ten_sp'])?>" class="w-100" style="height:180px;object-fit:contain"><h2 class="h6 mt-3"><?=$e($product['ten_sp'])?></h2></a>
        <p class="text-danger fw-bold"><?=view_helper::money($product['gia_km'])?></p><small class="mb-3"><?=$product['het_hang']?'Tạm hết hàng':'Còn '.(int)$product['so_luong_ton'].' sản phẩm'?></small>
        <form action="index.php?act=luuyeuthich" method="post" class="mt-auto"><input type="hidden" name="_csrf" value="<?=$e($userCsrf)?>"><input type="hidden" name="id_sanpham" value="<?=(int)$product['id']?>"><button class="btn btn-outline-secondary btn-sm" name="luu" value="0">Bỏ yêu thích</button></form>
    </div></article><?php endforeach; ?></div>
</main>
