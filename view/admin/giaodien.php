<?php
require_once __DIR__ . '/header_admin.php';

$currTheme = $theme ?? ['season' => 'default', 'font' => 'font-segoe', 'bg' => 'bg-light-gray'];
$currSeason = $currTheme['season'] ?? 'default';
$currFont = $currTheme['font'] ?? 'font-segoe';
$currBg = $currTheme['bg'] ?? 'bg-light-gray';
?>

<div class="container-fluid px-0">

    <!-- Thông báo thành công / lỗi -->
    <?php if (!empty($thongBao)): ?>
        <div class="alert alert-success rounded-4 d-flex align-items-center gap-2 shadow-sm mb-4">
            <i class="fas fa-check-circle fs-4 text-success"></i>
            <div><strong>Thành công:</strong> <?=$thongBao?></div>
        </div>
    <?php endif; ?>

    <?php if (!empty($loi)): ?>
        <div class="alert alert-danger rounded-4 d-flex align-items-center gap-2 shadow-sm mb-4">
            <i class="fas fa-exclamation-triangle fs-4 text-danger"></i>
            <div><strong>Lỗi:</strong> <?=$loi?></div>
        </div>
    <?php endif; ?>

    <form action="<?=$admin_ctrl_url?>&act=giaodien" method="POST" id="themeAdminForm">
        <input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>">
        <div class="row g-4">
            
            <!-- CỘT TRÁI: CÁC TÙY CHỌN CHỦ ĐỀ, FONT VÀ NỀN -->
            <div class="col-12 col-xl-7">
                
                <!-- 1. CHỦ ĐỀ THEO MÙA -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-0">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary-subtle text-primary p-2 rounded-circle">
                                <i class="fas fa-cloud-sun"></i>
                            </span>
                            <h6 class="fw-bold mb-0 text-dark">1. Chủ Đề Giao Diện Theo Mùa</h6>
                        </div>
                        <small class="text-muted">Màu sắc chủ đạo, gradient banner và hiệu ứng của toàn bộ website</small>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-3">
                            
                            <!-- Mặc định: Samsung / TGDD Blue -->
                            <div class="col-12 col-md-6">
                                <label class="card theme-card border h-100 p-3 rounded-3 cursor-pointer shadow-none position-relative">
                                    <input type="radio" name="season" value="default" class="form-check-input position-absolute top-0 end-0 m-3" <?=$currSeason === 'default' ? 'checked' : ''?> onchange="updateLivePreview()">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="rounded-circle" style="width: 24px; height: 24px; background: #0056b3;"></div>
                                        <strong class="text-dark">🔵 Xanh Samsung / TGDD</strong>
                                    </div>
                                    <small class="text-muted">Chủ đề gốc mặc định, phong cách công nghệ điện thoại cao cấp hiện đại.</small>
                                </label>
                            </div>

                            <!-- Mùa Xuân: Hoa Mai Vàng & Đỏ Tết -->
                            <div class="col-12 col-md-6">
                                <label class="card theme-card border h-100 p-3 rounded-3 cursor-pointer shadow-none position-relative">
                                    <input type="radio" name="season" value="theme-spring" class="form-check-input position-absolute top-0 end-0 m-3" <?=$currSeason === 'theme-spring' ? 'checked' : ''?> onchange="updateLivePreview()">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="rounded-circle" style="width: 24px; height: 24px; background: #d70018;"></div>
                                        <strong class="text-danger">🌸 Xuân Tài Lộc & Hoa Mai Vàng (Nền Đỏ Tết)</strong>
                                    </div>
                                    <small class="text-muted">Tone màu <strong>nền đỏ hoa mai</strong> may mắn, hiệu ứng <strong>cánh hoa mai vàng rơi bay bay, Chú Lân & Ông Địa chúc Tết</strong>, nút mua hàng vàng đỏ tài lộc.</small>
                                </label>
                            </div>

                            <!-- Mùa Hạ: Biển xanh năng động -->
                            <div class="col-12 col-md-6">
                                <label class="card theme-card border h-100 p-3 rounded-3 cursor-pointer shadow-none position-relative">
                                    <input type="radio" name="season" value="theme-summer" class="form-check-input position-absolute top-0 end-0 m-3" <?=$currSeason === 'theme-summer' ? 'checked' : ''?> onchange="updateLivePreview()">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="rounded-circle" style="width: 24px; height: 24px; background: #0984e3;"></div>
                                        <strong class="text-info">☀️ Hạ Sôi Động & Biển Xanh (Nút Sóng Biển)</strong>
                                    </div>
                                    <small class="text-muted">Tone xanh mát mẻ, hiệu ứng <strong>bãi biển cát vàng, sóng biển vỗ dập dềnh và rặng dừa đung đưa</strong>, nút mua hàng sóng biển xanh ngọc.</small>
                                </label>
                            </div>

                            <!-- Mùa Thu: Lá vàng quý phái -->
                            <div class="col-12 col-md-6">
                                <label class="card theme-card border h-100 p-3 rounded-3 cursor-pointer shadow-none position-relative">
                                    <input type="radio" name="season" value="theme-autumn" class="form-check-input position-absolute top-0 end-0 m-3" <?=$currSeason === 'theme-autumn' ? 'checked' : ''?> onchange="updateLivePreview()">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="rounded-circle" style="width: 24px; height: 24px; background: #d35400;"></div>
                                        <strong class="text-warning">🍂 Thu Quý Phái & Ấm Áp (Nút Hổ Phách)</strong>
                                    </div>
                                    <small class="text-muted">Tone cam đất sang trọng, hiệu ứng <strong>lá phong rơi bay bay chao lượn</strong>, nút mua hàng mật ong hổ phách quý phái.</small>
                                </label>
                            </div>

                            <!-- Mùa Đông: Băng tuyết giáng sinh -->
                            <div class="col-12">
                                <label class="card theme-card border h-100 p-3 rounded-3 cursor-pointer shadow-none position-relative">
                                    <input type="radio" name="season" value="theme-winter" class="form-check-input position-absolute top-0 end-0 m-3" <?=$currSeason === 'theme-winter' ? 'checked' : ''?> onchange="updateLivePreview()">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="rounded-circle" style="width: 24px; height: 24px; background: #2980b9;"></div>
                                        <strong class="text-primary">❄️ Đông Băng Tuyết & Giáng Sinh (Nút Đóng Băng)</strong>
                                    </div>
                                    <small class="text-muted">Tone xanh băng giá tuyết trắng, <strong>nút mua hàng hiệu ứng đóng băng phủ tuyết</strong>, tuyết rơi lấp lánh có Ông già Noel & cỗ xe Tuần Lộc bay qua bầu trời.</small>
                                </label>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- 2. PHÔNG CHỮ (TYPOGRAPHY) -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-0">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success-subtle text-success p-2 rounded-circle">
                                <i class="fas fa-font"></i>
                            </span>
                            <h6 class="fw-bold mb-0 text-dark">2. Kiểu Phông Chữ Website (Typography)</h6>
                        </div>
                        <small class="text-muted">Áp dụng cho toàn bộ tiêu đề, mô tả và giá sản phẩm</small>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-3">
                            
                            <div class="col-12 col-md-6">
                                <label class="card border p-3 rounded-3 cursor-pointer position-relative">
                                    <input type="radio" name="font" value="font-segoe" class="form-check-input position-absolute top-0 end-0 m-3" <?=$currFont === 'font-segoe' ? 'checked' : ''?> onchange="updateLivePreview()">
                                    <div class="fw-bold text-dark" style="font-family: 'Segoe UI', sans-serif;">Segoe UI (Mặc định)</div>
                                    <small class="text-muted" style="font-family: 'Segoe UI', sans-serif;">iPhone 16 Pro Max 256GB - 29.990.000đ</small>
                                </label>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="card border p-3 rounded-3 cursor-pointer position-relative">
                                    <input type="radio" name="font" value="font-bevietnam" class="form-check-input position-absolute top-0 end-0 m-3" <?=$currFont === 'font-bevietnam' ? 'checked' : ''?> onchange="updateLivePreview()">
                                    <div class="fw-bold text-dark" style="font-family: 'Be Vietnam Pro', sans-serif;">Be Vietnam Pro</div>
                                    <small class="text-muted" style="font-family: 'Be Vietnam Pro', sans-serif;">iPhone 16 Pro Max 256GB - 29.990.000đ</small>
                                </label>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="card border p-3 rounded-3 cursor-pointer position-relative">
                                    <input type="radio" name="font" value="font-roboto" class="form-check-input position-absolute top-0 end-0 m-3" <?=$currFont === 'font-roboto' ? 'checked' : ''?> onchange="updateLivePreview()">
                                    <div class="fw-bold text-dark" style="font-family: 'Roboto', sans-serif;">Roboto (Android Style)</div>
                                    <small class="text-muted" style="font-family: 'Roboto', sans-serif;">iPhone 16 Pro Max 256GB - 29.990.000đ</small>
                                </label>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="card border p-3 rounded-3 cursor-pointer position-relative">
                                    <input type="radio" name="font" value="font-montserrat" class="form-check-input position-absolute top-0 end-0 m-3" <?=$currFont === 'font-montserrat' ? 'checked' : ''?> onchange="updateLivePreview()">
                                    <div class="fw-bold text-dark" style="font-family: 'Montserrat', sans-serif;">Montserrat (Hiện đại)</div>
                                    <small class="text-muted" style="font-family: 'Montserrat', sans-serif;">iPhone 16 Pro Max 256GB - 29.990.000đ</small>
                                </label>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- 3. CHẾ ĐỘ NỀN (BACKGROUND) -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-0">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-warning-subtle text-warning p-2 rounded-circle">
                                <i class="fas fa-fill-drip"></i>
                            </span>
                            <h6 class="fw-bold mb-0 text-dark">3. Chế Độ Nền Website (Background)</h6>
                        </div>
                        <small class="text-muted">Màu nền nền tảng cho trang chủ, chi tiết sản phẩm và giỏ hàng</small>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="card border p-3 rounded-3 cursor-pointer text-center position-relative">
                                    <input type="radio" name="bg" value="bg-light-gray" class="form-check-input position-absolute top-0 end-0 m-2" <?=$currBg === 'bg-light-gray' ? 'checked' : ''?> onchange="updateLivePreview()">
                                    <div class="p-3 rounded mb-2 border" style="background-color: #f4f6f9;"></div>
                                    <div class="small fw-bold">Xám nhạt tiêu chuẩn</div>
                                </label>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="card border p-3 rounded-3 cursor-pointer text-center position-relative">
                                    <input type="radio" name="bg" value="bg-white-pure" class="form-check-input position-absolute top-0 end-0 m-2" <?=$currBg === 'bg-white-pure' ? 'checked' : ''?> onchange="updateLivePreview()">
                                    <div class="p-3 rounded mb-2 border" style="background-color: #ffffff;"></div>
                                    <div class="small fw-bold">Trắng tinh thanh lịch</div>
                                </label>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="card border p-3 rounded-3 cursor-pointer text-center position-relative">
                                    <input type="radio" name="bg" value="bg-soft-blue" class="form-check-input position-absolute top-0 end-0 m-2" <?=$currBg === 'bg-soft-blue' ? 'checked' : ''?> onchange="updateLivePreview()">
                                    <div class="p-3 rounded mb-2 border" style="background-color: #eaf2fc;"></div>
                                    <div class="small fw-bold">Xanh pastel dịu mát</div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- NÚT LƯU CẤU HÌNH -->
                <div class="d-flex gap-2 mb-4">
                    <button type="submit" class="btn btn-primary rounded-pill py-2 px-4 fw-bold shadow">
                        <i class="fas fa-save me-1"></i> LƯU ÁP DỤNG TOÀN BỘ WEBSITE
                    </button>
                    <button type="button" class="btn btn-outline-secondary rounded-pill py-2 px-3" onclick="resetDefaultTheme()">
                        <i class="fas fa-undo me-1"></i> Khôi phục mặc định
                    </button>
                </div>

            </div>

            <!-- CỘT PHẢI: KHUNG XEM TRƯỚC THỜI GIAN THỰC (LIVE PREVIEW) -->
            <div class="col-12 col-xl-5">
                <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 90px;">
                    <div class="card-header bg-dark text-white py-3 rounded-top-4 d-flex justify-content-between align-items-center">
                        <div class="fw-bold"><i class="fas fa-eye me-2 text-warning"></i> Xem Trước Trực Quan (Live Preview)</div>
                        <span class="badge bg-secondary">Thời gian thực</span>
                    </div>
                    <div class="card-body p-4" id="previewContainer" style="background-color: #f4f6f9; min-height: 480px; border-radius: 0 0 16px 16px;">
                        
                        <!-- Giả lập thanh Menu chính -->
                        <div class="p-3 bg-primary text-white rounded-3 mb-3 d-flex justify-content-between align-items-center" id="previewNavbar">
                            <span class="fw-bold"><i class="fas fa-mobile-alt me-1"></i> ĐiệnThoại<span class="text-warning">Z</span></span>
                            <span class="small badge bg-white text-primary">Giỏ hàng: 2</span>
                        </div>

                        <!-- Giả lập Card sản phẩm -->
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
                            <div class="position-relative text-center p-3 bg-white">
                                <span class="badge bg-danger position-absolute top-0 start-0 m-2">-15%</span>
                                <div class="py-4 text-center">
                                    <i class="fas fa-mobile-screen fs-1 text-primary" id="previewIcon"></i>
                                </div>
                            </div>
                            <div class="card-body p-3 bg-white">
                                <h6 class="fw-bold text-dark mb-1" id="previewTitle">iPhone 16 Pro Max 256GB</h6>
                                <p class="small text-muted mb-2">Chip Apple A18 Pro, Viền Titan chuẩn hàng không vũ trụ...</p>
                                <div class="d-flex align-items-baseline gap-2 mb-3">
                                    <span class="text-danger fw-bold fs-5" id="previewPrice">29.990.000đ</span>
                                    <span class="text-muted text-decoration-line-through small">34.990.000đ</span>
                                </div>
                                <button type="button" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold" id="previewButton">
                                    <i class="fas fa-cart-plus me-1"></i> Chọn Mua Ngay
                                </button>
                            </div>
                        </div>

                        <!-- Thông báo quyền áp dụng -->
                        <div class="alert alert-light border small text-muted mb-0">
                            <i class="fas fa-info-circle text-primary me-1"></i> 
                            Khi bạn bấm <strong>"Lưu Áp Dụng"</strong>, toàn bộ khách hàng ghé thăm website sẽ nhìn thấy đúng chủ đề mùa, font chữ và nền này.
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </form>
<!-- Nhúng CSS & JS hiệu ứng 4 mùa cho Admin Live Preview -->

<script>
// Cập nhật xem trước thời gian thực
function updateLivePreview() {
    const season = document.querySelector('input[name="season"]:checked')?.value || 'default';
    const font = document.querySelector('input[name="font"]:checked')?.value || 'font-segoe';
    const bg = document.querySelector('input[name="bg"]:checked')?.value || 'bg-light-gray';

    const container = document.getElementById('previewContainer');
    const nav = document.getElementById('previewNavbar');
    const btn = document.getElementById('previewButton');
    const icon = document.getElementById('previewIcon');

    // Cập nhật Font
    container.classList.remove('font-segoe', 'font-bevietnam', 'font-roboto', 'font-montserrat');
    container.classList.add(font);

    // Cập nhật Nền
    let bgColor = '#f4f6f9';
    if (bg === 'bg-white-pure') bgColor = '#ffffff';
    if (bg === 'bg-soft-blue') bgColor = '#eaf2fc';
    container.style.backgroundColor = bgColor;

    // Cập nhật class mùa cho container để nút và card đổi hiệu ứng đóng băng / đỏ Tết
    container.classList.remove('theme-spring', 'theme-summer', 'theme-autumn', 'theme-winter');
    if (season && season !== 'default') {
        container.classList.add(season);
    }

    // Cập nhật Màu sắc mùa
    let primaryColor = '#0056b3';
    if (season === 'theme-spring') primaryColor = '#d70018'; // Đỏ Tết may mắn & Hoa mai vàng
    if (season === 'theme-summer') primaryColor = '#0984e3';
    if (season === 'theme-autumn') primaryColor = '#d35400';
    if (season === 'theme-winter') primaryColor = '#2980b9';

    if (nav) nav.style.backgroundColor = primaryColor;
    if (btn) btn.style.backgroundColor = primaryColor;
    if (btn) btn.style.borderColor = primaryColor;
    if (icon) icon.style.color = primaryColor;

    // Hiển thị hiệu ứng 4 mùa sinh động ngay trong khung Live Preview
    if (typeof SeasonEffects !== 'undefined') {
        SeasonEffects.applyToPreview(container, season);
    }

    // Đồng bộ localStorage để Admin duyệt tab khách hàng thấy ngay
    localStorage.setItem('selected_season', season);
    localStorage.setItem('selected_font', font);
    localStorage.setItem('selected_bg', bg);
}

// Bật thử hiệu ứng mùa toàn màn hình ngay trong trang Admin
function toggleAdminFullScreenFx() {
    const season = document.querySelector('input[name="season"]:checked')?.value || 'default';
    if (typeof SeasonEffects !== 'undefined') {
        SeasonEffects.changeSeason(season);
    }
}

function resetDefaultTheme() {
    document.querySelector('input[name="season"][value="default"]').checked = true;
    document.querySelector('input[name="font"][value="font-segoe"]').checked = true;
    document.querySelector('input[name="bg"][value="bg-light-gray"]').checked = true;
    updateLivePreview();
}

document.addEventListener("DOMContentLoaded", function() {
    updateLivePreview();
});
</script>
