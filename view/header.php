<?php
require_once __DIR__ . '/helpers.php';
$base_url = view_helper::shopBase() . '/view';
$body_classes = [];
if (!empty($theme_cfg['season']) && $theme_cfg['season'] !== 'default') {
    $body_classes[] = htmlspecialchars($theme_cfg['season']);
}
if (!empty($theme_cfg['font'])) {
    $body_classes[] = htmlspecialchars($theme_cfg['font']);
}
if (!empty($theme_cfg['bg'])) {
    $body_classes[] = htmlspecialchars($theme_cfg['bg']);
}
$body_classes[] = 'bpstore-theme';
$page_key = pathinfo(basename($view ?? 'trangchu.php'), PATHINFO_FILENAME);
$body_classes[] = 'page-' . preg_replace('/[^a-z0-9_-]/i', '', $page_key);
$page_titles = [
    'dangnhap'=>'Đăng nhập', 'dangky'=>'Đăng ký', 'giohang'=>'Giỏ hàng',
    'thanhtoan'=>'Thanh toán', 'hoso'=>'Hồ sơ của tôi', 'yeuthich'=>'Sản phẩm yêu thích',
    'diem_thanhvien'=>'Điểm và hạng thành viên', 'lichsu_muahang'=>'Lịch sử đơn hàng',
    'theodoi_donhang'=>'Theo dõi đơn hàng', 'hopthu'=>'Hộp thư', 'chinhsach'=>'Chính sách mua hàng',
    'bank'=>'Chuyển khoản ngân hàng', 'paypal'=>'Thanh toán PayPal',
];
$page_title = $seoMetaTitle ?? (isset($page_titles[$page_key]) ? $page_titles[$page_key] . ' · BP Store' : 'BP Store · Điện thoại và phụ kiện');
if ($page_key === 'chitiet_sanpham' && !empty($chitiet['ten_sp'])) $page_title = $chitiet['ten_sp'] . ' · BP Store';
$body_class_attr = ' class="' . implode(' ', $body_classes) . '"';

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$e($page_title)?></title>
    <?php if (!empty($seoMetaDescription)): ?>
    <meta name="description" content="<?=$e($seoMetaDescription)?>">
    <meta name="keywords" content="iPhone 14 128GB Xanh dương, mua iPhone 14 128GB, iPhone 14 màu xanh dương, BP Store">
    <meta property="og:title" content="<?=$e($seoMetaTitle)?>">
    <meta property="og:description" content="<?=$e($seoMetaDescription)?>">
    <meta property="og:type" content="product">
    <?php if (!empty($seoProductSchema)): ?>
    <script type="application/ld+json"><?=json_encode($seoProductSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?></script>
    <?php endif; ?>
    <?php endif; ?>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?=$base_url?>/image/favicon.ico">
    <!-- Nhúng Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Nhúng FontAwesome Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Nhúng CSS tùy chỉnh (theme.css) -->
    <link rel="stylesheet" href="<?=$base_url?>/css/theme.css?v=20261023-seo-iphone14">
    <link rel="stylesheet" href="<?=$base_url?>/css/storefront.css?v=20261027-design-1">
    <!-- Nhúng CSS Hiệu Ứng 4 Mùa Đẹp Mắt (Hoa Đào, Bãi Biển Cây Dừa, Lá Phong, Tuyết & Ông Già Noel) -->
</head>
<body<?=$body_class_attr?>>
<a class="bpstore-skip" href="#store-content">Đến nội dung chính</a>
