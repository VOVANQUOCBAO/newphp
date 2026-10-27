<?php
declare(strict_types=1);

require_once __DIR__ . '/../../view/helpers.php';

function assertHomepage(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

if (!class_exists('controller')) {
    class controller
    {
        public function userCsrf(): string { return 'test-csrf'; }
    }
}

$e = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$money = static fn(mixed $value): string => number_format((float)$value, 0, ',', '.') . 'đ';
assertHomepage(method_exists('view_helper', 'bannerProductImage'), 'banner images have a background-free asset helper');
assertHomepage(str_contains(view_helper::bannerProductImage('catalog/iphone16.webp'), 'banner-iphone16.png'), 'banner uses the transparent product cutout');
$userCsrf = 'test-csrf';
$ds_danhmuc = [];
$idDm = 0;
$tuKhoa = '';
$sapXep = 'moi';
$moi_trang = 8;
$sp_km = [[
    'id' => 4,
    'ten_sp' => 'Sản phẩm giảm giá kiểm thử',
    'hinh' => 'catalog/iphone16.webp',
    'gia' => 10000,
    'gia_goc' => 10000,
    'gia_km' => 9500,
    'so_luong_ton' => 2,
]];
$ds_sanpham = [[
    'id' => 5,
    'ten_sp' => 'Sản phẩm giá thường kiểm thử',
    'hinh' => 'catalog/s24ultra.webp',
    'gia' => 7000,
    'gia_goc' => 7000,
    'gia_km' => 7000,
    'so_luong_ton' => 2,
]];
$trang = 1;
$tong_sanpham = 12;
$tong_trang = 2;
$con_tiep = true;
$sp_moi = [
    ['id' => 1, 'ten_sp' => 'Sản phẩm banner 1', 'hinh' => 'catalog/iphone16.webp', 'gia' => 1000000, 'gia_km' => 1000000],
    ['id' => 2, 'ten_sp' => 'Sản phẩm banner 2', 'hinh' => 'catalog/s24ultra.webp', 'gia' => 2000000, 'gia_km' => 2000000],
    ['id' => 3, 'ten_sp' => 'Sản phẩm banner 3', 'hinh' => 'catalog/nokia235.webp', 'gia' => 3000000, 'gia_km' => 3000000],
];
$seoProduct = [
    'id' => 10, 'ten_sp' => 'iPhone 14 128GB · Xanh dương', 'ma_sp' => 'IP14-128-BLUE',
    'hinh' => 'catalog/iphone14.webp', 'gia' => 13990000, 'gia_goc' => 13990000,
    'gia_km' => 12591000, 'so_luong_ton' => 4, 'thuong_hieu' => 'Apple',
];
$seoAccessoryProducts = [
    ['id' => 14, 'ten_sp' => 'AirPods 4 · Hộp sạc USB-C', 'gia' => 3290000, 'gia_goc' => 3290000, 'gia_km' => 3125500],
];
$seoMetaTitle = 'iPhone 14 128GB Xanh dương giảm 10% | BP Store';
$seoMetaDescription = 'Giá hiện tại 12.591.000đ, còn hàng.';
$seoProductSchema = ['@type' => 'Product', 'name' => 'iPhone 14 128GB · Xanh dương'];

ob_start();
require __DIR__ . '/../../view/trangchu.php';
$homepage = (string)ob_get_clean();

assertHomepage(substr_count($homepage, 'class="carousel-item') === 3, 'homepage renders three rotating product banners');
assertHomepage(str_contains($homepage, 'data-bs-ride="carousel"'), 'homepage carousel rotates automatically');
assertHomepage(str_contains($homepage, 'data-bs-interval="3000"'), 'homepage carousel changes every three seconds');
assertHomepage(str_contains($homepage, 'data-bs-wrap="true"') && str_contains($homepage, 'data-bs-pause="false"'), 'homepage carousel loops continuously without requiring a click');
assertHomepage(str_contains($homepage, 'Sản phẩm banner 3'), 'third banner uses a real product record');
assertHomepage(strpos($homepage, 'bpStorePromoCarousel') < strpos($homepage, 'seo-iphone14-title'), 'SEO product article is placed below the banner');
assertHomepage(str_contains($homepage, 'iPhone 14 128GB · Xanh dương') && str_contains($homepage, 'Super Retina XDR'), 'SEO article contains the supplied product content');
assertHomepage(str_contains($homepage, '13.990.000đ') && str_contains($homepage, '12.591.000đ') && str_contains($homepage, 'Giảm 10%'), 'SEO article uses the current product price and calculated discount');
assertHomepage(str_contains($homepage, 'AirPods 4 · Hộp sạc USB-C') && str_contains($homepage, 'index.php?act=chitiet&amp;id=14'), 'SEO cross-sell points to a real accessory product');
assertHomepage(str_contains($homepage, 'Giá gốc 3.290.000đ') && str_contains($homepage, 'Giá ưu đãi 3.125.500đ'), 'cross-sell distinguishes original and discounted accessory prices');
assertHomepage(str_contains($homepage, 'Giá gốc') && str_contains($homepage, 'Giá khuyến mãi'), 'discounted product card labels original and sale prices');
assertHomepage(str_contains($homepage, '10.000đ') && str_contains($homepage, '9.500đ'), 'discounted product card shows both correct prices');
assertHomepage(str_contains($homepage, 'Giá bán') && str_contains($homepage, '7.000đ'), 'regular product card labels its single selling price');
assertHomepage(str_contains($homepage, 'trang=2'), 'pagination links directly to page two');
assertHomepage(str_contains($homepage, '>2</a>'), 'pagination shows a numeric page-two button');

$trang = 2;
$seoProduct = null;
$seoAccessoryProducts = [];
$sp_moi = [];
$ds_sanpham = [];
for ($id = 9; $id <= 12; $id++) {
    $ds_sanpham[] = ['id' => $id, 'ten_sp' => 'Trang 2 - sản phẩm ' . ($id - 8), 'hinh' => 'catalog/iphone16.webp', 'gia' => 1000000, 'gia_km' => 1000000, 'so_luong_ton' => 2];
}
ob_start();
require __DIR__ . '/../../view/trangchu.php';
$secondPage = (string)ob_get_clean();
assertHomepage(str_contains($secondPage, 'Trang 2 / 2'), 'second page shows its current page number');
assertHomepage(str_contains($secondPage, 'Trang 2 - sản phẩm 1') && str_contains($secondPage, 'Trang 2 - sản phẩm 4'), 'second page renders its own product records');
assertHomepage(!str_contains($secondPage, 'seo-iphone14-title'), 'SEO feature article is limited to the first homepage page');

$chitiet = [
    'id' => 6,
    'ten_sp' => 'Điện thoại kiểm thử',
    'ma_sp' => 'TEST-6',
    'hinh' => 'catalog/iphone16.webp',
    'anh_phu' => '',
    'gia' => 10000,
    'gia_goc' => 10000,
    'gia_km' => 9500,
    'so_luong_ton' => 2,
    'da_ban' => 0,
    'thuong_hieu' => 'BP Store',
];
$userCsrf = 'test-csrf';
$ds_danhgia = [];
$ds_lienquan = [];
$duocDanhGia = false;
$_SESSION = [];
ob_start();
require __DIR__ . '/../../view/chitiet_sanpham.php';
$productDetail = (string)ob_get_clean();
assertHomepage(str_contains($productDetail, 'Giá gốc') && str_contains($productDetail, 'Giá khuyến mãi'), 'product detail distinguishes original and discounted prices');
assertHomepage(str_contains($productDetail, 'Tiết kiệm 500đ'), 'product detail explains the discount amount');

$base_url = view_helper::shopBase() . '/view';
$_GET = [];
$_SESSION = [];
ob_start();
require __DIR__ . '/../../view/header.php';
$head = (string)ob_get_clean();
assertHomepage(str_contains($head, '<title>iPhone 14 128GB Xanh dương giảm 10% | BP Store</title>'), 'homepage emits the product SEO title');
assertHomepage(str_contains($head, 'name="description"') && str_contains($head, 'application/ld+json'), 'homepage emits product metadata and structured data');
ob_start();
require __DIR__ . '/../../view/footer.php';
$footer = (string)ob_get_clean();
assertHomepage(str_contains($footer, 'Chuyển khoản QR') && str_contains($footer, 'PayPal') && str_contains($footer, 'COD'), 'footer only advertises current payment methods');
assertHomepage(str_contains($footer, 'Chính sách mua hàng') && str_contains($footer, 'Theo dõi đơn hàng'), 'footer provides policy and order support links');
assertHomepage(str_contains($footer, '92 Bùi Tư Toàn, Bình Tân') && str_contains($footer, 'maps/search/?api=1'), 'footer address opens its location in Google Maps');
assertHomepage(str_contains($footer, '0338 008 284') && str_contains($footer, 'tel:+84338008284'), 'footer phone number can be called directly');
assertHomepage(str_contains($footer, 'qbaor39@gmail.com') && str_contains($footer, 'mailto:qbaor39@gmail.com'), 'footer email opens a new email');
assertHomepage(str_contains($footer, 'carousel.cycle()'), 'homepage carousel is explicitly started without user interaction');
assertHomepage(str_contains($footer, 'interval: 3000'), 'homepage carousel starts with a three-second interval');

echo "Homepage presentation scenarios passed.\n";
