<?php
$cart_count = 0;
if (isset($_SESSION['giohang']) && is_array($_SESSION['giohang'])) {
    foreach ($_SESSION['giohang'] as $item) {
        $cart_count += is_array($item)
            ? (int) ($item[3] ?? $item['so_luong'] ?? 1)
            : (is_numeric($item) ? (int) $item : 0);
    }
}
$current_act = (string) ($_REQUEST['act'] ?? 'trangchu');
$menuEscape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<nav class="navbar navbar-expand-xl navbar-light navbar-main sticky-top" aria-label="Điều hướng cửa hàng">
    <div class="container">
        <a class="navbar-brand bpstore-brand" href="index.php?act=trangchu" aria-label="BP Store - Trang chủ">
            <img src="<?=$base_url?>/image/bpstore-logo.png" alt="BP Store" width="92" height="58">
        </a>
        <button class="navbar-toggler" type="button" data-bs-target="#navMainContent" data-bs-toggle="collapse" aria-controls="navMainContent" aria-expanded="false" aria-label="Mở menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMainContent">
            <form action="index.php" method="GET" class="bpstore-search my-2 my-xl-0 mx-xl-3">
                <input type="hidden" name="act" value="timkiem">
                <div class="input-group">
                    <input class="form-control" type="search" aria-label="Tìm sản phẩm" name="keyword" placeholder="Tìm sản phẩm" value="<?=$menuEscape($_GET['keyword'] ?? $_GET['q'] ?? '')?>" required>
                    <button class="btn btn-warning" type="submit" aria-label="Tìm kiếm"><i class="fas fa-search"></i></button>
                </div>
            </form>
            <ul class="navbar-nav ms-auto align-items-xl-center">
                <li class="nav-item"><a class="nav-link <?=in_array($current_act, ['trangchu', ''], true) ? 'active' : ''?>" href="index.php?act=trangchu"><i class="fas fa-house me-1"></i>Trang chủ</a></li>
                <li class="nav-item"><a class="nav-link <?=in_array($current_act, ['giohang', 'themgiohang'], true) ? 'active' : ''?>" href="index.php?act=giohang"><i class="fas fa-cart-shopping me-1"></i>Giỏ hàng <span class="badge-cart-count" id="headerCartBadge"><?=$cart_count?></span></a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?=in_array($current_act, ['lichsumuahang', 'theodoidonhang', 'hopthu'], true) ? 'active' : ''?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-box me-1"></i>Đơn hàng</a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><a class="dropdown-item" href="index.php?act=lichsumuahang">Lịch sử đơn hàng</a></li>
                        <li><a class="dropdown-item" href="index.php?act=theodoidonhang">Tra cứu đơn hàng</a></li>
                        <li><a class="dropdown-item" href="index.php?act=hopthu">Hộp thư</a></li>
                    </ul>
                </li>
                <?php if (isset($_SESSION['user'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDrop" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user-circle me-1"></i><?=$menuEscape($_SESSION['user']['hoten'] ?? 'Tài khoản')?></a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userDrop">
                            <li><a class="dropdown-item" href="index.php?act=hoso">Hồ sơ của tôi</a></li>
                            <li><a class="dropdown-item" href="index.php?act=diemthanhvien">Điểm và hạng thành viên</a></li>
                            <li><a class="dropdown-item" href="index.php?act=yeuthich">Sản phẩm yêu thích</a></li>
                            <li><a class="dropdown-item" href="index.php?act=lichsumuahang">Đơn hàng của tôi</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><form action="index.php?act=dangxuat" method="post"><input type="hidden" name="_csrf" value="<?=$menuEscape($userCsrf)?>"><button class="dropdown-item text-danger" type="submit">Đăng xuất</button></form></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link <?=($current_act === 'dangnhap') ? 'active' : ''?>" href="index.php?act=dangnhap">Đăng nhập</a></li>
                    <li class="nav-item"><a class="nav-link <?=($current_act === 'dangky') ? 'active' : ''?>" href="index.php?act=dangky">Đăng ký</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
