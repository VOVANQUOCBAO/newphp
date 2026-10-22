<?php
require_once __DIR__ . '/helpers.php';
$img_path = shopBase() . '/view/image/';
// Hàm hiển thị Card Sản phẩm từ DỮ LIỆU THẬT trong CSDL
if (!function_exists('renderCardTrangChu')) {
    function renderCardTrangChu(array $sp, string $img_path, string $badgeType = ''): void {
        $id = (int)$sp['id'];
        $ten = htmlspecialchars($sp['ten_sp']);
        $hinh = htmlspecialchars(productImage($sp['hinh'] ?? null), ENT_QUOTES, 'UTF-8');
        $giaGoc = (float)($sp['gia_goc'] ?? $sp['gia']);
        $giaKm = (float)($sp['gia_km'] ?? $sp['gia']);
        $tonKho = (int)($sp['so_luong_ton'] ?? 0);
        $daBan = (int)($sp['da_ban'] ?? 0);
        $saoTb = number_format((float)($sp['sao_tb'] ?? 5.0), 1);
        $tongDg = (int)($sp['so_danh_gia'] ?? 0);
        $hetHang = $tonKho <= 0 || !empty($sp['het_hang']);

        // % giảm giá
        $phanTramGiam = 0;
        if ($giaGoc > $giaKm) {
            $phanTramGiam = round((($giaGoc - $giaKm) / $giaGoc) * 100);
        }
?>
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="card h-100 product-card shadow-sm <?=$hetHang ? 'out-of-stock' : ''?>">
                <?php if ($hetHang): ?>
                    <span class="badge product-badge badge-tag-stock"><i class="fas fa-ban me-1"></i> Hết hàng</span>
                <?php elseif ($phanTramGiam > 0): ?>
                    <span class="badge product-badge badge-tag-sale"><i class="fas fa-bolt me-1"></i> -<?=$phanTramGiam?>%</span>
                <?php elseif ($badgeType === 'new'): ?>
                    <span class="badge product-badge badge-tag-new"><i class="fas fa-sparkles me-1"></i> Mới</span>
                <?php elseif ($daBan > 0): ?>
                    <span class="badge product-badge badge-tag-hot"><i class="fas fa-fire me-1"></i> Bán Chạy</span>
                <?php endif; ?>

                <a href="index.php?act=chitiet&id=<?=$id?>" class="product-img-wrapper">
                    <img src="<?=$hinh?>" alt="<?=$ten?>">
                </a>
                <div class="card-body p-3 d-flex flex-column">
                    <a href="index.php?act=chitiet&id=<?=$id?>" class="product-title" title="<?=$ten?>"><?=$ten?></a>
                    
                    <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
                        <span>Đã bán: <strong class="text-dark"><?=number_format($daBan)?></strong></span>
                    </div>

                    <p class="mb-2 small text-secondary">
                        Tồn kho: 
                        <?php if ($hetHang): ?>
                            <span class="fw-bold text-danger">0 (Hết hàng)</span>
                        <?php else: ?>
                            <span class="fw-bold text-success"><?=$tonKho?></span>
                        <?php endif; ?>
                    </p>

                    <div class="mt-auto pt-2">
                        <?php if ($phanTramGiam > 0): ?>
                            <div class="text-muted text-decoration-line-through small"><?=number_format($giaGoc, 0, ',', '.')?>đ</div>
                            <div class="text-danger fw-bold fs-5"><?=number_format($giaKm, 0, ',', '.')?>đ</div>
                        <?php else: ?>
                            <div class="text-danger fw-bold fs-5"><?=number_format($giaGoc, 0, ',', '.')?>đ</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 p-3 pt-0">
                    <?php if ($hetHang): ?>
                        <button class="btn btn-secondary w-100 fw-bold rounded-3 py-2 disabled" style="cursor: not-allowed;" title="Sản phẩm tạm hết hàng">
                            <i class="fas fa-times-circle me-1"></i> Tạm hết hàng
                        </button>
                    <?php else: ?>
                        <div class="d-grid gap-2">
                            <form action="index.php?act=muangay" method="post">
                                <input type="hidden" name="_csrf" value="<?=htmlspecialchars((new controller())->userCsrf(), ENT_QUOTES, 'UTF-8')?>">
                                <input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="sl" value="1">
                                <button class="btn btn-primary w-100">Mua ngay</button>
                            </form>
                            <a href="index.php?act=chitiet&amp;id=<?=$id?>" class="btn btn-outline-primary">Thêm vào giỏ hàng</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
<?php
    }
}
?>

<?php if (!empty($sp_moi[0]) && empty($tuKhoa) && empty($idDm) && (int)$trang===1): $featured = $sp_moi[0]; ?>
<section class="container my-4"><div class="card border-0 bg-light p-4 p-md-5"><div class="row align-items-center g-3">
    <div class="col-md-7"><p class="text-primary fw-semibold">Sản phẩm mới</p><h1 class="h2 fw-bold"><?=$e($featured['ten_sp'])?></h1>
    <p class="fs-4 text-danger fw-bold"><?=money($featured['gia_km'] ?? $featured['gia'])?></p><a class="btn btn-primary" href="index.php?act=chitiet&amp;id=<?=(int)$featured['id']?>">Xem chi tiết</a></div>
    <div class="col-md-5 text-center"><img src="<?=$e(productImage($featured['hinh']))?>" alt="<?=$e($featured['ten_sp'])?>" style="width:100%;max-height:240px;object-fit:contain"></div>
</div></div></section>
<?php endif; ?>
<div class="container mb-5">

    <!-- THANH BỘ LỌC NHANH & SẮP XẾP SẢN PHẨM TỪ CSDL -->
    <div class="bg-white p-3 rounded-4 shadow-sm mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <!-- Lọc theo danh mục thực tế từ bảng danhmuc -->
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="fw-bold small text-muted"><i class="fas fa-filter text-primary me-1"></i> Danh mục:</span>
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
            <span class="fw-bold small text-muted"><i class="fas fa-sort-amount-down me-1"></i> Sắp xếp:</span>
            <select class="form-select form-select-sm rounded-pill" style="width: auto;" onchange="location.href='index.php?<?=$e(http_build_query(['act'=>'trangchu','id_danhmuc'=>$idDm,'q'=>$tuKhoa,'moi_trang'=>$moi_trang]))?>&amp;sort='+this.value">
                <option value="moi" <?=($sapXep ?? '') !== 'cu' ? 'selected' : ''?>>Mới nhất → Cũ nhất</option>
                <option value="cu" <?=($sapXep ?? '') === 'cu' ? 'selected' : ''?>>Cũ nhất → Mới nhất</option>
            </select>
        </div>
    </div>

    <!-- ========================================================
         KHỐI NỔI BẬT 1: 🔥 SẢN PHẨM KHUYẾN MÃI HOT (DỮ LIỆU THẬT CSDL)
         ======================================================== -->
    <?php if (!empty($sp_km)): ?>
    <section id="block-khuyenmai" class="highlight-section mb-5 p-4 border-blue">
        <div class="section-title-wrap">
            <h2 class="section-title text-danger">
                <span>🔥 SẢN PHẨM KHUYẾN MÃI HOT</span>
                <span class="badge bg-danger fs-6 text-white text-uppercase py-1 px-2 ms-2">Giảm Giá Sốc</span>
            </h2>
        </div>

        <div class="row g-4">
            <?php foreach ($sp_km as $sp): ?>
                <?php renderCardTrangChu($sp, $img_path, 'sale'); ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ========================================================
         KHỐI 3: 📱 TẤT CẢ SẢN PHẨM / TÌM KIẾM / DANH MỤC (DỮ LIỆU THẬT CSDL)
         ======================================================== -->
    <section id="block-tatca" class="mb-5 bg-white p-4 rounded-4 shadow-sm">
        <div class="section-title-wrap">
            <h2 class="section-title">
                <i class="fas fa-mobile-alt text-primary me-2"></i>
                <?php if (!empty($tuKhoa)): ?>
                    Kết quả tìm kiếm cho: "<?=htmlspecialchars($tuKhoa)?>"
                <?php elseif (!empty($idDm)): ?>
                    Danh mục sản phẩm đã chọn
                <?php else: ?>
                    Tất cả sản phẩm
                <?php endif; ?>
                <span class="badge bg-secondary fs-6 ms-2"><?=(int)$tong_sanpham?> sản phẩm</span>
            </h2>
        </div>

        <div class="row g-4">
            <?php if (!empty($ds_sanpham)): ?>
                <?php foreach ($ds_sanpham as $sp): ?>
                    <?php renderCardTrangChu($sp, $img_path); ?>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-box-open fs-1 text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">Không tìm thấy sản phẩm nào trong hệ thống!</h5>
                    <a href="index.php?act=trangchu" class="btn btn-primary rounded-pill px-4 mt-2">Xem tất cả sản phẩm</a>
                </div>
            <?php endif; ?>
        </div>

        <nav class="d-flex justify-content-center align-items-center gap-3 mt-4" aria-label="Trang sản phẩm">
            <?php $pageQuery = ['act'=>'trangchu','id_danhmuc'=>$idDm,'q'=>$tuKhoa,'sort'=>$sapXep,'moi_trang'=>$moi_trang]; ?>
            <?php if ($trang > 1): ?><a class="btn btn-outline-primary" href="index.php?<?=$e(http_build_query($pageQuery + ['trang'=>$trang-1]))?>">Trang trước</a><?php endif; ?>
            <span>Trang <?=(int)$trang?></span>
            <?php if ($con_tiep): ?><a class="btn btn-outline-primary" href="index.php?<?=$e(http_build_query($pageQuery + ['trang'=>$trang+1]))?>">Trang sau</a><?php endif; ?>
        </nav>
    </section>

</div>
