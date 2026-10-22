<?php
require_once __DIR__ . '/helpers.php';
$base_url = shopBase() . '/view';
// Tải cấu hình giao diện do Quản trị viên thiết lập từ Admin
$theme_file = dirname(__DIR__) . '/support/config/theme.json';
$theme_cfg = ['season' => 'default', 'font' => 'font-segoe', 'bg' => 'bg-light-gray'];
if (is_file($theme_file)) {
    $theme_saved = json_decode((string)file_get_contents($theme_file), true);
    if (is_array($theme_saved)) {
        $theme_cfg = array_merge($theme_cfg, $theme_saved);
    }
}

// Chỉ Quản trị viên (Admin) mới có quyền xem thử mùa qua tham số GET (?season=theme-spring, summer, autumn, winter)
if (!empty($_GET['season']) && isset($_SESSION['admin'])) {
    $s = trim($_GET['season']);
    if (!str_starts_with($s, 'theme-') && $s !== 'default') {
        $s = 'theme-' . $s;
    }
    if (in_array($s, ['default', 'theme-spring', 'theme-summer', 'theme-autumn', 'theme-winter'], true)) {
        $theme_cfg['season'] = $s;
    }
}

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
$body_class_attr = !empty($body_classes) ? ' class="' . implode(' ', $body_classes) . '"' : '';

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ĐiệnThoạiZ - Thế Giới Smartphone Chính Hãng Giá Tốt</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?=$base_url?>/image/favicon.ico">
    <!-- Nhúng Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Nhúng FontAwesome Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Nhúng CSS tùy chỉnh (theme.css) -->
    <link rel="stylesheet" href="<?=$base_url?>/css/theme.css">
    <!-- Nhúng CSS Hiệu Ứng 4 Mùa Đẹp Mắt (Hoa Đào, Bãi Biển Cây Dừa, Lá Phong, Tuyết & Ông Già Noel) -->
</head>
<body<?=$body_class_attr?>>
    <div class="bg-light border-bottom py-2"><div class="container small d-flex justify-content-between"><span>ĐiệnThoạiZ</span><a href="index.php?act=chinhsach">Mua hàng, hủy và trả hàng</a></div></div>
