<?php
include __DIR__ . '/header_admin.php';

$h = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$dsSanPham = $ds_sanpham ?? [];
$dsDanhMuc = $ds_danhmuc ?? [];
$xemNgungBan = !empty($xem_ngung_ban);
$formatTien = static fn($value): string => number_format((float) $value, 0, ',', '.') . ' đ';
?>

<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <?php if(!$canManageStore): ?><p class="text-muted mb-0">Tài khoản hiện tại có quyền xem sản phẩm.</p><?php endif; ?>

        </div>
        <?php if ($canManageStore): ?>
        <button class="btn btn-outline-primary btn-sm rounded-pill px-3" type="button"
                data-bs-toggle="modal" data-bs-target="#modalDanhMuc">
            <i class="fas fa-folder-open me-1"></i> Quản lý danh mục
        </button>
        <?php endif; ?>
    </div>

    <?php if (!empty($thongBao)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $h($thongBao) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($loi)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $h($loi) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>

    <?php if ($canManageStore && !$xemNgungBan): ?>
    <details class="card mb-4" id="product-form-card" <?=!empty($loi)&&isset($_POST['ma_sp'])?'open':''?>>
        <summary class="card-header"><span id="form-title">Thêm sản phẩm</span></summary>
        <div class="card-body p-4">
            <form action="index.php?khu=admin&act=themsanpham" method="POST" enctype="application/x-www-form-urlencoded" id="spForm">
                <input type="hidden" name="_csrf" value="<?= $h($csrfToken) ?>">
                <input type="hidden" name="_image_uploads" value="">
                <input type="hidden" name="id" id="form_id" disabled>
                <input type="hidden" name="ton_cu" id="form_ton_cu" disabled>
                <div class="row g-3">
                    <div class="col-12"><h2 class="form-section-title">Thông tin bán hàng</h2></div>
                    <div class="col-12 col-md-3">
                        <label for="form_ma_sp" class="form-label small fw-bold">Mã sản phẩm *</label>
                        <input type="text" name="ma_sp" id="form_ma_sp" class="form-control" maxlength="50" required>
                    </div>
                    <div class="col-12 col-md-5">
                        <label for="form_ten_sp" class="form-label small fw-bold">Tên sản phẩm *</label>
                        <input type="text" name="ten_sp" id="form_ten_sp" class="form-control" maxlength="255" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="form_danhmuc" class="form-label small fw-bold">Danh mục *</label>
                        <select name="id_danhmuc" id="form_danhmuc" class="form-select" required>
                            <option value="">Chọn danh mục</option>
                            <?php foreach ($dsDanhMuc as $dm): ?>
                                <option value="<?= (int) $dm['id'] ?>"><?= $h($dm['ten_danhmuc']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($dsDanhMuc === []): ?>
                            <small class="text-danger">Hãy tạo danh mục trước khi thêm sản phẩm.</small>
                        <?php endif; ?>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="form_gia" class="form-label small fw-bold">Giá bán (VNĐ) *</label>
                        <input type="number" name="gia" id="form_gia" class="form-control" min="0" step="0.01" required>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="form_sl_goc" id="stock-total-label" class="form-label small fw-bold">Số lượng nhập ban đầu *</label>
                        <input type="number" name="so_luong_goc" id="form_sl_goc" class="form-control" min="0" step="1" required>
                    </div>
                    <div class="col-12" id="product-stock-edit" hidden>
                        <div class="admin-subtle"><div class="row g-3 align-items-end"><div class="col-md-6"><p class="mb-0">Tồn khả dụng: <strong id="product-stock-current">0</strong></p><small>Chưa gồm hàng đã giữ cho đơn.</small></div><div class="col-md-6"><label for="form_nhap_them" class="form-label">Nhập thêm hàng</label><input type="number" id="form_nhap_them" name="nhap_them" disabled min="0" step="1" value="0" class="form-control"><small>Thêm số lượng này vào tổng nhập và tồn khả dụng.</small></div></div>
                        <details class="mt-3" id="product-stock-audit"><summary>Điều chỉnh giảm tồn sau kiểm kê</summary><label class="d-block my-2"><input type="checkbox" id="stock-adjust-enable"> Tôi cần ghi nhận chênh lệch sau kiểm kê</label><label for="form_sl_ton" class="form-label">Tồn khả dụng thực tế</label><input type="number" name="so_luong_ton" id="form_sl_ton" class="form-control" min="0" step="1" disabled required><small>Chỉ điều chỉnh giảm. Hàng nhập mới dùng ô Nhập thêm; không cộng hàng đang giữ cho đơn.</small></details></div>
                    </div>
                    <div class="col-md-4"><label id="receipt-date-label" for="receipt-date" class="form-label">Ngày nhập hàng</label><input id="receipt-date" class="form-control" type="date" name="ngay_nhap" max="<?=date('Y-m-d')?>" value="<?=date('Y-m-d')?>" required><small>Ghi đợt mới khi tạo sản phẩm hoặc Nhập thêm lớn hơn 0.</small></div>
                    <div class="col-12"><h2 class="form-section-title">Ảnh sản phẩm</h2></div>
                    <div class="col-12"><img id="form_image_preview" src="<?= $h(view_helper::productImage(null)) ?>" alt="Ảnh sản phẩm đang chọn" style="width:90px;height:90px;object-fit:contain" class="border rounded" hidden></div>
                    <div class="col-md-6"><label class="form-label" for="form_anh">Tải ảnh chính</label><input class="form-control" type="file" id="form_anh" name="anh" accept="image/jpeg,image/png,image/webp"><small class="text-muted">JPG, PNG, WebP; tối đa 5 MB. Để trống để giữ ảnh cũ.</small></div>
                    <div class="col-md-6"><label class="form-label" for="form_album">Ảnh bổ sung</label><input class="form-control" type="file" id="form_album" name="album[]" multiple accept="image/jpeg,image/png,image/webp"><small class="text-muted">Tối đa 6 ảnh; chọn mới để thay album hiện tại.</small></div>
                    <input type="hidden" name="anh_phu" id="form_anh_phu">
                    <div class="col-12"><h2 class="form-section-title">Nội dung hiển thị cho khách</h2></div>
                    <div class="col-md-6"><label for="form_thuong_hieu" class="form-label">Thương hiệu</label><input name="thuong_hieu" id="form_thuong_hieu" class="form-control" maxlength="150"></div>
                    <div class="col-md-6"><label for="form_bao_hanh" class="form-label">Bảo hành</label><input name="bao_hanh" id="form_bao_hanh" class="form-control" maxlength="255" placeholder="Ví dụ: 12 tháng tại cửa hàng"></div>
                    <div class="col-md-6"><label for="form_thong_so" class="form-label">Thông số kỹ thuật</label><textarea name="thong_so" id="form_thong_so" class="form-control" rows="4" placeholder="Mỗi thông số một dòng: Màn hình: ..."></textarea></div>
                    <div class="col-md-6">
                        <label for="form_mo_ta" class="form-label small fw-bold">Mô tả sản phẩm</label>
                        <textarea name="mo_ta" id="form_mo_ta" class="form-control" rows="4"></textarea>
                    </div>
                    <div class="col-12"><details><summary>Tùy chọn khác: ảnh có sẵn, hạn sử dụng</summary><div class="row g-3 mt-1"><div class="col-md-8"><label for="form_hinh" class="form-label">Tên ảnh có sẵn trong thư viện</label><input type="text" name="hinh" id="form_hinh" class="form-control" maxlength="255"><small>Để trống nếu tải ảnh từ thiết bị. Khi sửa, giữ nguyên để dùng ảnh cũ.</small></div><div class="col-md-4"><label for="form_han_su_dung" class="form-label">Hạn sử dụng (nếu có)</label><input type="date" name="han_su_dung" id="form_han_su_dung" class="form-control"><small>Điện thoại thường không cần nhập mục này.</small></div></div></details></div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary px-4 fw-bold" id="form-submit">
                        <i class="fas fa-plus-circle me-1"></i> Thêm sản phẩm
                    </button>
                    <button type="button" class="btn btn-outline-secondary px-4" onclick="resetSPForm()">Làm mới</button>
                </div>
                <div id="product-upload-status" class="alert mt-3 mb-0" role="status" aria-live="polite" hidden></div>
                <noscript><p class="text-danger mt-3">Vui lòng bật JavaScript để tải ảnh từ thiết bị.</p></noscript>
            </form>
        </div>
    </details>
    <?php endif; ?>

    <nav class="d-flex flex-wrap gap-2 mb-3" aria-label="Trạng thái sản phẩm">
        <a class="btn btn-sm <?= $xemNgungBan ? 'btn-outline-primary' : 'btn-primary' ?>" href="index.php?khu=admin&amp;act=sanpham" <?= !$xemNgungBan ? 'aria-current="page"' : '' ?>>Đang bán</a>
        <a class="btn btn-sm <?= $xemNgungBan ? 'btn-primary' : 'btn-outline-primary' ?>" href="index.php?khu=admin&amp;act=sanpham&amp;trang_thai=ngungban" <?= $xemNgungBan ? 'aria-current="page"' : '' ?>>Đã ngừng bán</a>
    </nav>
    <?php if ($xemNgungBan): ?><p class="text-muted">Sản phẩm ở đây không xuất hiện trong cửa hàng và không thể đặt mua mới. Đơn cũ vẫn được xử lý, hủy hoặc trả theo quy định.</p><?php endif; ?>
    <?php $tableId='products-table';$searchPlaceholder='Tên, mã sản phẩm hoặc danh mục';$statusOptions=[];require __DIR__.'/_table_tools.php'; ?>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 px-4">
            <h5 class="mb-0 fw-bold"><i class="fas fa-list text-primary me-2"></i><?= $xemNgungBan ? 'Sản phẩm đã ngừng bán' : 'Danh sách sản phẩm' ?> (<?= count($dsSanPham) ?>)</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center" id="products-table">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th>Mã sản phẩm</th>
                        <th class="text-start">Tên sản phẩm</th>
                        <th>Danh mục</th>
                        <th>Giá</th>
                        <th>Tổng nhập</th><th>Ngày nhập đầu</th>
                        <th>Tồn kho</th>
                        <th>Đã bán</th><th>Hàng lỗi</th>
                        <?php if ($canManageStore): ?><th>Thao tác</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($dsSanPham === []): ?>
                        <tr><td colspan="<?=$canManageStore ? 9 : 8?>" class="text-muted py-4">Chưa có sản phẩm.<?=$canManageStore ? ' Dùng biểu mẫu phía trên để thêm mới.' : ''?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($dsSanPham as $sp): ?>
                        <?php
                        $json = json_encode([
                            'id' => (int) $sp['id'],
                            'ma_sp' => $sp['ma_sp'],
                            'ten_sp' => $sp['ten_sp'],
                            'id_danhmuc' => (int) $sp['id_danhmuc'],
                            'gia' => $sp['gia'],
                            'so_luong_goc' => (int) $sp['so_luong_goc'],
                            'so_luong_ton' => (int) $sp['so_luong_ton'],
                            'han_su_dung' => $sp['han_su_dung'],
                            'hinh' => $sp['hinh'],
                            'mo_ta' => $sp['mo_ta'],
                            'thuong_hieu' => $sp['thuong_hieu'] ?? '',
                            'bao_hanh' => $sp['bao_hanh'] ?? '',
                            'thong_so' => $sp['thong_so'] ?? '',
                            'anh_phu' => $sp['anh_phu'] ?? '',
                        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);
                        $ton = (int) $sp['so_luong_ton'];
                        ?>
                        <tr data-admin-row>
                            <td><code><?= $h($sp['ma_sp']) ?></code></td>
                            <td class="text-start"><a class="d-flex align-items-center gap-2 fw-semibold" href="index.php?khu=admin&amp;act=chitietsanpham&amp;id=<?=(int)$sp['id']?>"><img src="<?=$h(view_helper::productImage($sp['hinh']))?>" width="56" height="56" loading="lazy" style="object-fit:contain" alt="<?=$h($sp['ten_sp'])?>"><span><?= $h($sp['ten_sp']) ?><small class="d-block fw-normal">Xem chi tiết</small></span></a></td>
                            <td><?= $h($sp['ten_danhmuc']) ?></td>
                            <td><?= $h($formatTien($sp['gia'])) ?></td>
                            <td><?= (int) $sp['so_luong_goc'] ?></td><td><?=!empty($sp['ngay_nhap'])?$h(date('d/m/Y',strtotime($sp['ngay_nhap']))):'Chưa ghi nhận'?></td>
                            <td><span class="badge <?= $ton <= 5 ? 'bg-danger' : 'bg-success' ?>"><?= $ton ?></span></td>
                            <td><?= (int) $sp['da_ban'] ?></td><td><?=(int)$sp['so_luong_loi']?></td>
                            <?php if ($canManageStore): ?><td>
                                <div class="d-flex justify-content-center gap-1">
                                    <?php if ($xemNgungBan): ?>
                                    <form action="index.php?khu=admin&amp;act=mobanlaisanpham" method="POST" onsubmit="return confirm('Mở bán lại sản phẩm này trên cửa hàng?')">
                                        <input type="hidden" name="_csrf" value="<?= $h($csrfToken) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $sp['id'] ?>">
                                        <button type="submit" class="btn btn-outline-primary btn-sm">Mở bán lại</button>
                                    </form>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-outline-primary btn-sm"
                                            data-product="<?= $h($json) ?>" onclick="editSP(this)">Sửa</button>
                                    <form action="index.php?khu=admin&act=xoasanpham" method="POST"
                                          onsubmit="return confirm('Gỡ sản phẩm khỏi danh sách bán? Nếu đã có giao dịch, sản phẩm sẽ chuyển sang Đã ngừng bán và giữ nguyên lịch sử đơn hàng. Sản phẩm chưa có giao dịch sẽ được xóa. Tiếp tục?')">
                                        <input type="hidden" name="_csrf" value="<?= $h($csrfToken) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $sp['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Xóa</button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td><?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canManageStore): ?>
<div class="modal fade" id="modalDanhMuc" tabindex="-1" aria-labelledby="modalDanhMucLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalDanhMucLabel">
                    <i class="fas fa-folder me-2"></i>Quản lý danh mục
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body p-4">
                <form action="index.php?khu=admin&act=danhmuc" method="POST" class="border rounded-3 p-3 mb-4">
                    <input type="hidden" name="_csrf" value="<?= $h($csrfToken) ?>">
                    <input type="hidden" name="hanh_dong" value="them">
                    <h6 class="fw-bold">Thêm danh mục</h6>
                    <div class="mb-2">
                        <label class="form-label small">Tên danh mục *</label>
                        <input type="text" name="ten_danhmuc" class="form-control" maxlength="150" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Mô tả</label>
                        <textarea name="mo_ta" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Thêm</button>
                </form>

                <h6 class="fw-bold">Danh mục hiện có</h6>
                <?php if ($dsDanhMuc === []): ?>
                    <p class="text-muted">Chưa có danh mục.</p>
                <?php endif; ?>
                <?php foreach ($dsDanhMuc as $dm): ?>
                    <div class="border rounded-3 p-3 mb-3">
                        <form action="index.php?khu=admin&act=danhmuc" method="POST">
                            <input type="hidden" name="_csrf" value="<?= $h($csrfToken) ?>">
                            <input type="hidden" name="hanh_dong" value="sua">
                            <input type="hidden" name="id" value="<?= (int) $dm['id'] ?>">
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-md-5">
                                    <label class="form-label small">Tên danh mục *</label>
                                    <input type="text" name="ten_danhmuc" class="form-control"
                                           maxlength="150" value="<?= $h($dm['ten_danhmuc']) ?>" required>
                                </div>
                                <div class="col-12 col-md-5">
                                    <label class="form-label small">Mô tả</label>
                                    <input type="text" name="mo_ta" class="form-control"
                                           value="<?= $h($dm['mo_ta']) ?>">
                                </div>
                                <div class="col-12 col-md-2 d-grid">
                                    <button type="submit" class="btn btn-outline-primary btn-sm">Lưu sửa</button>
                                </div>
                            </div>
                            <small class="text-muted d-block mt-2">Đang có <?= (int) $dm['so_san_pham'] ?> sản phẩm.</small>
                        </form>
                        <form action="index.php?khu=admin&act=danhmuc" method="POST" class="mt-2"
                              onsubmit="return confirm('Xóa danh mục này? Chỉ xóa được khi không còn sản phẩm hoặc khuyến mãi liên quan.')">
                            <input type="hidden" name="_csrf" value="<?= $h($csrfToken) ?>">
                            <input type="hidden" name="hanh_dong" value="xoa">
                            <input type="hidden" name="id" value="<?= (int) $dm['id'] ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">Xóa danh mục</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

<script>
function setProductMode(editing) {
    document.getElementById('receipt-date').name=editing?'ngay_nhap_dot':'ngay_nhap';
    document.getElementById('receipt-date-label').textContent=editing?'Ngày nhập đợt mới':'Ngày nhập hàng';
    document.getElementById('product-stock-edit').hidden = !editing;
    document.getElementById('stock-total-label').textContent = editing ? 'Tổng đã nhập' : 'Số lượng nhập ban đầu *';
    document.getElementById('stock-adjust-enable').checked = false;
    document.getElementById('form_sl_ton').disabled = true;
    document.getElementById('product-stock-audit').open = false;
}
function editSP(button) {
    const product = JSON.parse(button.dataset.product);
    document.getElementById('form_id').value = product.id;
    document.getElementById('form_ton_cu').value = product.so_luong_ton;
    document.getElementById('form_ton_cu').disabled = false;
    ['thuong_hieu','bao_hanh','thong_so','anh_phu'].forEach(key => document.getElementById('form_'+key).value = product[key] || '');
    document.getElementById('form_nhap_them').value = 0;
    document.getElementById('form_nhap_them').disabled = false;
    document.getElementById('form_anh').value = '';
    document.getElementById('form_album').value = '';
    document.getElementById('form_id').disabled = false;
    document.getElementById('form_ma_sp').value = product.ma_sp;
    document.getElementById('form_ten_sp').value = product.ten_sp;
    document.getElementById('form_danhmuc').value = product.id_danhmuc;
    document.getElementById('form_gia').value = product.gia;
    document.getElementById('form_sl_goc').value = product.so_luong_goc;
    document.getElementById('form_sl_goc').readOnly = true;
    document.getElementById('form_sl_ton').value = product.so_luong_ton;
    document.getElementById('form_sl_ton').max = product.so_luong_ton;
    document.getElementById('form_han_su_dung').value = product.han_su_dung || '';
    document.getElementById('form_hinh').value = product.hinh || '';
    document.getElementById('form_image_preview').src = <?=json_encode(view_helper::shopBase() . '/view/image/')?> + (product.hinh || 'ai-phone-elegance.svg');
    document.getElementById('form_image_preview').hidden = !product.hinh;
    document.getElementById('product-stock-current').textContent = product.so_luong_ton;
    document.getElementById('form_mo_ta').value = product.mo_ta || '';
    document.getElementById('spForm').action = 'index.php?khu=admin&act=suasanpham';
    document.getElementById('form-title').textContent = 'Chỉnh sửa sản phẩm';
    document.getElementById('form-submit').innerHTML = '<i class="fas fa-save me-1"></i> Lưu thay đổi';
    setProductMode(true);
    document.getElementById('product-form-card').open = true;
    document.getElementById('product-form-card').scrollIntoView({ behavior: 'smooth' });
}

function resetSPForm() {
    const form = document.getElementById('spForm');
    form.reset();
    form.action = 'index.php?khu=admin&act=themsanpham';
    document.getElementById('form_id').value = '';
    document.getElementById('form_id').disabled = true;
    document.getElementById('form_ton_cu').disabled = true;
    document.getElementById('form_sl_goc').readOnly = false;
    document.getElementById('form_nhap_them').disabled = true;
    document.getElementById('form_sl_ton').removeAttribute('max');
    setProductMode(false);
    document.getElementById('form_image_preview').hidden = true;
    document.getElementById('form-title').textContent = 'Thêm sản phẩm';
    document.getElementById('form-submit').innerHTML = '<i class="fas fa-plus-circle me-1"></i> Thêm sản phẩm';
}
document.getElementById('form_anh')?.addEventListener('change', event => {
    const file=event.target.files[0];
    if (file) { const url=URL.createObjectURL(file); const image=document.getElementById('form_image_preview'); image.hidden=false; image.onload=()=>URL.revokeObjectURL(url); image.src=url; }
});
document.getElementById('stock-adjust-enable')?.addEventListener('change',event=>{
    document.getElementById('form_sl_ton').disabled=!event.target.checked;
});
const failedProduct = <?=json_encode(!empty($loi) && isset($_POST['ma_sp']) ? $_POST : null, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;
if (failedProduct) {
    const form = document.getElementById('spForm');
    Object.entries(failedProduct).forEach(([key,value]) => {
        const input = form?.elements.namedItem(key);
        if (input && input.type !== 'file' && key !== '_csrf') input.value = value;
    });
    if (failedProduct.id) {
        document.getElementById('form_id').disabled = false;
        document.getElementById('form_ton_cu').disabled = false;
        document.getElementById('form_sl_goc').readOnly = true;
        document.getElementById('form_nhap_them').disabled = false;
        form.action = 'index.php?khu=admin&act=suasanpham';
        document.getElementById('form-title').textContent = 'Chỉnh sửa sản phẩm';
        document.getElementById('form-submit').textContent = 'Lưu thay đổi';
        setProductMode(true);
        document.getElementById('product-stock-current').textContent = failedProduct.ton_cu || '0';
        if (Object.hasOwn(failedProduct,'so_luong_ton')) {
            document.getElementById('stock-adjust-enable').checked=true;
            document.getElementById('form_sl_ton').disabled=false;
            document.getElementById('product-stock-audit').open=true;
        }
    }
}
</script>
<script src="<?=$h(view_helper::shopBase())?>/view/js/product-upload.js?v=20261021-403"></script>

