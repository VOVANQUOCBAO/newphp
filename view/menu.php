<?php
// Tính tổng số lượng món hàng trong session giỏ hàng
$cart_count = 0;
if (isset($_SESSION['giohang']) && is_array($_SESSION['giohang'])) {
    foreach ($_SESSION['giohang'] as $item) {
        if (is_numeric($item)) {
            $cart_count += (int)$item;
        } elseif (is_array($item)) {
            $cart_count += isset($item[3]) ? (int)$item[3] : (isset($item['so_luong']) ? (int)$item['so_luong'] : 1);
        }
    }
}
$current_act = $_REQUEST['act'] ?? 'trangchu';
?>
<!-- THANH ĐIỀU HƯỚNG CHÍNH (THEO PHONG CÁCH SAMSUNG & THẾ GIỚI DI ĐỘNG) -->
<nav class="navbar navbar-expand-xl navbar-dark navbar-main sticky-top">
    <div class="container">
        <!-- Logo thương hiệu -->
        <a class="navbar-brand fw-bold fs-4 d-flex align-items-center gap-2" href="index.php?act=trangchu">
            <span class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                <i class="fas fa-mobile-alt"></i>
            </span>
            <span class="text-white tracking-wide">ĐiệnThoại<span class="text-warning">Z</span></span>
        </a>

        <!-- Nút toggle trên thiết bị di động -->
        <button class="navbar-toggler" type="button" data-bs-target="#navMainContent" data-bs-toggle="collapse" aria-controls="navMainContent" aria-expanded="false" aria-label="Mở menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Nội dung Menu & Tìm kiếm -->
        <div class="collapse navbar-collapse" id="navMainContent">
            <!-- Ô tìm kiếm trung tâm -->
            <form action="index.php" method="GET" class="d-flex my-2 my-xl-0 mx-auto" style="max-width: 300px; width: 100%;">
                <input type="hidden" name="act" value="timkiem">
                <div class="input-group">
                    <input class="form-control rounded-start-pill border-0 ps-3" type="search" aria-label="Tìm sản phẩm" name="keyword" placeholder="Bạn tìm điện thoại gì? (iPhone 16, S24...)" value="<?=htmlspecialchars($_GET['keyword'] ?? '')?>" required>
                    <button class="btn btn-warning rounded-end-pill px-3 fw-bold text-dark" type="submit" aria-label="Tìm kiếm">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <!-- Các nút điều hướng gọi các case trong controller/index.php -->
            <ul class="navbar-nav ms-auto align-items-xl-center">
                <li class="nav-item">
                    <a class="nav-link <?=($current_act == 'trangchu' || $current_act == '') ? 'active' : ''?>" href="index.php?act=trangchu">
                        <i class="fas fa-home me-1"></i> Trang chủ
                    </a>
                </li>

                <!-- Link Giỏ Hàng theo đúng Lab 3.1 -->
                <li class="nav-item">
                    <a class="nav-link position-relative <?=($current_act == 'giohang' || $current_act == 'themgiohang') ? 'active' : ''?>" href="index.php?act=giohang" id="menuCartLink">
                        <i class="fas fa-shopping-cart me-1"></i> Giỏ hàng
                        <span class="badge-cart-count" id="headerCartBadge"><?=$cart_count?></span>
                    </a>
                </li>

                <!-- Lịch sử đơn hàng [U7] -->
                <li class="nav-item">
                    <a class="nav-link <?=($current_act == 'lichsumuahang') ? 'active' : ''?>" href="index.php?act=lichsumuahang">
                        <i class="fas fa-receipt me-1"></i> Lịch sử đơn
                    </a>
                </li>

                <!-- Theo dõi đơn hàng [U8] -->
                <li class="nav-item">
                    <a class="nav-link <?=($current_act == 'theodoidonhang') ? 'active' : ''?>" href="index.php?act=theodoidonhang">
                        <i class="fas fa-shipping-fast me-1"></i> Tra cứu
                    </a>
                </li>

                <!-- Hộp thư Admin gửi [U9] -->
                <li class="nav-item">
                    <a class="nav-link <?=($current_act == 'hopthu') ? 'active' : ''?>" href="index.php?act=hopthu">
                        <i class="fas fa-envelope me-1"></i> Hộp thư
                    </a>
                </li>

                <!-- Khu vực Tài khoản Khách hàng [U1, U2] -->
                <?php if (isset($_SESSION['user'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-warning fw-bold" href="#" id="userDrop" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i> <?=$e($_SESSION['user']['hoten'] ?? 'Khách hàng')?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3" aria-labelledby="userDrop">
                            <li><h6 class="dropdown-header text-primary">Hạng thành viên: <?=htmlspecialchars($_SESSION['user']['hang'] ?? 'Silver')?></h6></li>
                            <li><span class="dropdown-item-text small text-muted">Điểm tích lũy: <?=htmlspecialchars($_SESSION['user']['diem'] ?? 0)?> điểm</span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="index.php?act=lichsumuahang"><i class="fas fa-box-open me-2 text-primary"></i> Đơn hàng của tôi</a></li>
                            <li><a class="dropdown-item" href="index.php?act=hopthu"><i class="fas fa-envelope-open me-2 text-info"></i> Hộp thư quà tặng</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><form action="index.php?act=dangxuat" method="post"><input type="hidden" name="_csrf" value="<?=$e($userCsrf)?>"><button class="dropdown-item text-danger">Đăng xuất</button></form></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?=($current_act == 'dangnhap') ? 'active' : ''?>" href="index.php?act=dangnhap">
                            <i class="fas fa-sign-in-alt me-1"></i> Đăng nhập
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?=($current_act == 'dangky') ? 'active' : ''?>" href="index.php?act=dangky">
                            <i class="fas fa-user-plus me-1"></i> Đăng ký
                        </a>
                    </li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>

