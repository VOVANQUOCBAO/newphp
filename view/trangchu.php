<?php
require_once __DIR__ . '/helpers.php';
?>


<?php if (empty($tuKhoa) && empty($idDm) && (int)$trang === 1): ?>
<?php
    $bannerProducts = array_values(array_slice($sp_moi ?? [], 0, 3));
    $bannerFallbacks = [
        ['title' => 'Điện thoại và phụ kiện cho nhu cầu hằng ngày', 'copy' => 'Khám phá danh mục sản phẩm tại BP Store.'],
        ['title' => 'Tìm mẫu phù hợp với bạn', 'copy' => 'Xem thông tin chi tiết và giá bán trước khi chọn mua.'],
        ['title' => 'Sản phẩm nổi bật tại BP Store', 'copy' => 'Duyệt thêm nhiều lựa chọn điện thoại và phụ kiện.'],
    ];
?>
<section class="container my-4" aria-label="Sản phẩm giới thiệu">
    <div id="bpStorePromoCarousel" class="carousel slide bpstore-carousel" data-bs-ride="carousel" data-bs-interval="3000" data-bs-pause="false" data-bs-wrap="true" data-bs-touch="true" data-bs-keyboard="true">
        <div class="carousel-indicators">
            <?php for ($i = 0; $i < 3; $i++): ?>
                <button type="button" data-bs-target="#bpStorePromoCarousel" data-bs-slide-to="<?=$i?>" class="<?=$i === 0 ? 'active' : ''?>" <?=$i === 0 ? 'aria-current="true"' : ''?> aria-label="Banner <?=$i + 1?>"></button>
            <?php endfor; ?>
        </div>
        <div class="carousel-inner">
            <?php for ($i = 0; $i < 3; $i++): $bannerProduct = $bannerProducts[$i] ?? null; ?>
                <div class="carousel-item <?=$i === 0 ? 'active' : ''?>">
                    <div class="bpstore-banner bpstore-banner-<?=$i + 1?>">
                        <div class="bpstore-banner-copy">
                            <p class="bpstore-banner-label"><span></span>BP Store · Gợi ý sản phẩm</p>
                            <?php if ($bannerProduct): ?>
                                <h1><?=$e($bannerProduct['ten_sp'])?></h1>
                                <p class="bpstore-banner-description">Xem thông tin sản phẩm, giá bán và tình trạng hàng hiện tại.</p>
                                <p class="bpstore-banner-price"><?=view_helper::money($bannerProduct['gia_km'] ?? $bannerProduct['gia'])?></p>
                                <a class="btn btn-primary" href="index.php?act=chitiet&amp;id=<?=(int)$bannerProduct['id']?>">Xem sản phẩm <i class="fas fa-arrow-right ms-2" aria-hidden="true"></i></a>
                            <?php else: ?>
                                <h1><?=$e($bannerFallbacks[$i]['title'])?></h1>
                                <p class="bpstore-banner-description"><?=$e($bannerFallbacks[$i]['copy'])?></p>
                                <a class="btn btn-primary" href="index.php?act=trangchu">Khám phá sản phẩm <i class="fas fa-arrow-right ms-2" aria-hidden="true"></i></a>
                            <?php endif; ?>
                        </div>
                        <?php if ($bannerProduct): ?>
                            <a class="bpstore-banner-visual" href="index.php?act=chitiet&amp;id=<?=(int)$bannerProduct['id']?>" aria-label="Xem <?=$e($bannerProduct['ten_sp'])?>">
                                <img src="<?=$e(view_helper::bannerProductImage($bannerProduct['hinh'] ?? null))?>" alt="<?=$e($bannerProduct['ten_sp'])?>" loading="<?=$i === 0 ? 'eager' : 'lazy'?>">
                            </a>
                        <?php else: ?>
                            <div class="bpstore-banner-mark" aria-hidden="true"><span>BP</span><small>STORE</small></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#bpStorePromoCarousel" data-bs-slide="prev" aria-label="Banner trước">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#bpStorePromoCarousel" data-bs-slide="next" aria-label="Banner tiếp theo">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
        </button>
    </div>
</section>
<?php if (!empty($seoProduct)): ?>
    <?php require __DIR__ . '/bai_viet_seo_iphone14.php'; ?>
<?php endif; ?>
<?php endif; ?>
<div class="container mb-5">

    <div class="bpstore-filter mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <!-- Lọc theo danh mục thực tế từ bảng danhmuc -->
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="fw-semibold small text-muted"><i class="fas fa-filter me-1"></i>Danh mục</span>
            <a href="index.php?act=trangchu" class="btn btn-outline-primary btn-sm rounded-pill px-3 <?=empty($idDm) ? 'active' : ''?>">Tất cả</a>
            <?php foreach (($ds_danhmuc ?? []) as $dm): ?>
                <a href="index.php?act=trangchu&id_danhmuc=<?=$dm['id']?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 <?=(($idDm ?? 0) == $dm['id']) ? 'active' : ''?>">
                    <?=htmlspecialchars($dm['ten_danhmuc'])?>
                </a>
            <?php endforeach; ?>
            <?php if (!empty($sp_km)): ?><a href="#block-khuyenmai" class="btn btn-outline-danger btn-sm rounded-pill px-3">Khuyến mãi</a><?php endif; ?>
        </div>

        <!-- Bộ lọc sắp xếp -->
        <div class="d-flex align-items-center gap-2">
            <label class="fw-semibold small text-muted" for="productSort"><i class="fas fa-sort me-1"></i>Sắp xếp</label>
            <select id="productSort" class="form-select form-select-sm" style="width: auto;" onchange="location.href='index.php?<?=$e(http_build_query(['act'=>'trangchu','id_danhmuc'=>$idDm,'q'=>$tuKhoa,'moi_trang'=>$moi_trang]))?>&amp;sort='+this.value">
                <option value="moi" <?=($sapXep ?? '') !== 'cu' ? 'selected' : ''?>>Mới nhất → Cũ nhất</option>
                <option value="cu" <?=($sapXep ?? '') === 'cu' ? 'selected' : ''?>>Cũ nhất → Mới nhất</option>
            </select>
        </div>
    </div>

    <?php if (!empty($sp_km)): ?>
    <section id="block-khuyenmai" class="highlight-section mb-5 p-4 border-blue">
        <div class="section-title-wrap">
            <h2 class="section-title text-danger">
                <span>Khuyến mãi</span>
                <span class="badge bg-danger fs-6 text-white py-1 px-2 ms-2">Giá ưu đãi</span>
            </h2>
        </div>

        <div class="row g-4">
            <?php foreach ($sp_km as $sp): ?>
                <?php view_helper::productCard($sp, $userCsrf, 'sale'); ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section id="block-tatca" class="mb-5 bg-white p-4 rounded-4 shadow-sm">
        <div class="section-title-wrap">
            <h2 class="section-title">
                <i class="fas fa-mobile-alt text-primary me-2"></i>
                <?php if (!empty($tuKhoa)): ?>
                    Kết quả tìm kiếm cho: "<?=htmlspecialchars($tuKhoa)?>"
                <?php elseif (!empty($idDm)): ?>
                    Danh mục sản phẩm đã chọn
                <?php else: ?>
                    Sản phẩm
                <?php endif; ?>
                <span class="badge bg-secondary fs-6 ms-2"><?=(int)$tong_sanpham?> sản phẩm</span>
            </h2>
        </div>

        <div class="row g-4">
            <?php if (!empty($ds_sanpham)): ?>
                <?php foreach ($ds_sanpham as $sp): ?>
                    <?php view_helper::productCard($sp, $userCsrf); ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-box-open fs-1 text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">Không tìm thấy sản phẩm nào trong hệ thống!</h5>
                    <a href="index.php?act=trangchu" class="btn btn-primary rounded-pill px-4 mt-2">Xem tất cả sản phẩm</a>
                </div>
            <?php endif; ?>
        </div>

        <?php $tongTrangHienThi = max(1, (int)($tong_trang ?? ceil(max(0, (int)$tong_sanpham) / max(1, (int)$moi_trang)))); ?>
        <nav class="mt-4" aria-label="Trang sản phẩm">
            <?php $pageQuery = ['act'=>'trangchu','id_danhmuc'=>$idDm,'q'=>$tuKhoa,'sort'=>$sapXep,'moi_trang'=>$moi_trang]; ?>
            <?php if ($tongTrangHienThi > 1): ?>
                <?php $pageStart = max(1, min((int)$trang - 2, $tongTrangHienThi - 4)); $pageEnd = min($tongTrangHienThi, $pageStart + 4); ?>
                <ul class="pagination bpstore-pagination justify-content-center mb-0">
                    <li class="page-item <?=$trang <= 1 ? 'disabled' : ''?>"><a class="page-link" href="index.php?<?=$e(http_build_query($pageQuery + ['trang'=>max(1, (int)$trang - 1)]))?>" aria-label="Trang trước">‹</a></li>
                    <?php if ($pageStart > 1): ?>
                        <li class="page-item"><a class="page-link" href="index.php?<?=$e(http_build_query($pageQuery + ['trang'=>1]))?>">1</a></li>
                        <?php if ($pageStart > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <?php endif; ?>
                    <?php for ($pageNumber = $pageStart; $pageNumber <= $pageEnd; $pageNumber++): ?>
                        <li class="page-item <?=$pageNumber === (int)$trang ? 'active' : ''?>"><a class="page-link" href="index.php?<?=$e(http_build_query($pageQuery + ['trang'=>$pageNumber]))?>" <?=$pageNumber === (int)$trang ? 'aria-current="page"' : ''?>><?=$pageNumber?></a></li>
                    <?php endfor; ?>
                    <?php if ($pageEnd < $tongTrangHienThi): ?>
                        <?php if ($pageEnd < $tongTrangHienThi - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                        <li class="page-item"><a class="page-link" href="index.php?<?=$e(http_build_query($pageQuery + ['trang'=>$tongTrangHienThi]))?>"><?=$tongTrangHienThi?></a></li>
                    <?php endif; ?>
                    <li class="page-item <?=$trang >= $tongTrangHienThi ? 'disabled' : ''?>"><a class="page-link" href="index.php?<?=$e(http_build_query($pageQuery + ['trang'=>min($tongTrangHienThi, (int)$trang + 1)]))?>" aria-label="Trang sau">›</a></li>
                </ul>
                <p class="bpstore-pagination-note text-center small text-muted mt-2 mb-0">Trang <?=(int)$trang?> / <?=$tongTrangHienThi?> · <?=$moi_trang?> sản phẩm mỗi trang</p>
            <?php endif; ?>
        </nav>
    </section>

</div>
