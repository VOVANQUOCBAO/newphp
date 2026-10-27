<?php
require_once __DIR__ . '/helpers.php';
$cart_items = [];
if (isset($giohang['items']) && is_array($giohang['items'])) {
    foreach ($giohang['items'] as $it) {
        $cart_items[] = [
            $it['id'],
            $it['ten_sp'],
            $it['gia'],
            $it['so_luong'],
            $it['hinh'] ?: 'ai-phone-elegance.svg',
            $it['thanh_tien'] ?? ($it['gia'] * $it['so_luong'])
        ];
    }
}

$tong_tien = 0;
?>

<div class="container my-5">
<?php if (!empty($loi)): ?><div class="alert alert-danger"><?=$e($loi)?></div><?php endif; ?>
    <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
        <h2 class="fw-bold text-primary mb-0">
            <i class="fas fa-shopping-cart me-2"></i> Giỏ hàng của bạn
        </h2>
        <span class="badge bg-primary fs-6"><?=count($cart_items)?> sản phẩm</span>
    </div>

    <?php if (empty($cart_items)): ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm p-4">
            <div class="mb-3 text-muted display-1">
                <i class="fas fa-cart-arrow-down"></i>
            </div>
            <h4 class="fw-bold text-secondary">Giỏ hàng của bạn đang trống</h4>
            <p class="text-muted">Hãy dạo một vòng và chọn chiếc smartphone ưng ý nhất nhé!</p>
            <a href="index.php?act=trangchu" class="btn btn-primary rounded-pill px-4 py-2 fw-bold mt-2">
                <i class="fas fa-arrow-left me-1"></i> Quay lại Mua Sắm
            </a>
        </div>
    <?php else: ?>
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-4">
            <div class="table-responsive">
                <!-- Bảng Giỏ Hàng Chuẩn Theo Bài Mẫu Của Thầy (Lab 3.1) -->
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-primary text-uppercase small fw-bold">
                        <tr>
                            <th scope="col" style="width: 5%;">STT</th>
                            <th scope="col" style="width: 12%;">Hình</th>
                            <th scope="col" class="text-start" style="width: 30%;">Tên Sản Phẩm</th>
                            <th scope="col" style="width: 15%;">Đơn Giá</th>
                            <th scope="col" style="width: 5%;">x</th>
                            <th scope="col" style="width: 12%;">Số Lượng</th>
                            <th scope="col" style="width: 5%;">=</th>
                            <th scope="col" style="width: 15%;">Thành Tiền</th>
                            <th scope="col" style="width: 8%;">Xóa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        for ($i = 0; $i < count($cart_items); $i++):
                            $item = $cart_items[$i];
                            $id = $item[0];
                            $ten = $item[1];
                            $gia = (float)$item[2];
                            $sl = (int)$item[3];
                            $hinh = $item[4] ?? 'ai-phone-elegance.svg';
                            $thanh_tien = isset($item[5]) ? (float)$item[5] : ($gia * $sl);
                            $tong_tien += $thanh_tien;
                        ?>
                        <tr>
                            <!-- 1. STT -->
                            <td class="fw-bold"><?=$i + 1?></td>

                            <!-- 2. Hình ảnh -->
                            <td>
                                <img src="<?=$e(view_helper::productImage($hinh))?>" alt="<?=htmlspecialchars($ten)?>" style="height: 55px; width: 65px; object-fit: contain;" class="rounded border p-1 bg-white">
                            </td>

                            <!-- 3. Tên sản phẩm -->
                            <td class="text-start">
                                <a href="index.php?act=chitiet&id=<?=$id?>" class="text-decoration-none fw-semibold text-dark">
                                    <?=htmlspecialchars($ten)?>
                                </a>
                                <div class="small text-muted">Mã SP: #<?=$id?></div>
                            </td>

                            <!-- 4. Đơn giá -->
                            <td class="fw-semibold text-primary">
                                <?=number_format($gia, 0, ',', '.')?>đ
                            </td>

                            <!-- 5. Ký tự x -->
                            <td class="text-muted fw-bold">×</td>

                            <!-- 6. Form Sửa số lượng (suagiohang theo đúng bài mẫu) -->
                            <td>
                                <form action="index.php?act=suagiohang" method="post" enctype="multipart/form-data" class="d-flex align-items-center justify-content-center gap-1"><input type="hidden" name="_csrf" value="<?=$e($userCsrf)?>">
                                    <input type="hidden" name="id" value="<?=$id?>">
                                    <input type="number" name="sl" value="<?=$sl?>" min="1" max="<?=(int)($giohang['items'][$i]['ton'] ?? 0)?>" class="form-control form-control-sm text-center fw-bold" style="width: 65px;">
                                    <button type="submit" class="btn btn-outline-primary btn-sm" title="Cập nhật số lượng">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            </td>

                            <!-- 7. Ký tự = -->
                            <td class="text-muted fw-bold">=</td>

                            <!-- 8. Thành tiền -->
                            <td class="fw-bold text-danger fs-6">
                                <?=number_format($thanh_tien, 0, ',', '.')?>đ
                            </td>

                            <!-- 9. Nút Delete (xoagiohang theo đúng bài mẫu) -->
                            <td>
                                <form action="index.php?act=xoagiohang" method="post">
                                    <input type="hidden" name="_csrf" value="<?=$e($userCsrf)?>"><input type="hidden" name="id" value="<?=(int)$id?>"><button class="btn btn-outline-danger btn-sm">Xóa</button>
                                </form>
                            </td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="7" class="text-end fw-bold fs-5 py-3">Tổng cộng thanh toán:</td>
                            <td colspan="2" class="text-danger fw-bold fs-4 py-3 text-center">
                                <?=number_format($tong_tien, 0, ',', '.')?>đ
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Hàng nút hành động: Đặt Hàng & Tiếp tục mua sắm -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 bg-white p-3 rounded-4 shadow-sm">
            <a href="index.php?act=trangchu" class="btn btn-outline-secondary rounded-pill px-4 py-2">
                <i class="fas fa-arrow-left me-1"></i> Tiếp tục chọn sản phẩm khác
            </a>
            <div class="d-flex gap-2">
                <a href="index.php?act=thanhtoan&amp;nguon=gio" class="btn btn-danger btn-lg rounded-pill px-5 py-2 fw-bold shadow">
                    <i class="fas fa-credit-card me-2"></i> TIẾN HÀNH ĐẶT HÀNG →
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>
