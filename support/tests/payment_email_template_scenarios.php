<?php
declare(strict_types=1);

require_once __DIR__ . '/../../model/thongbao_thanhtoan.php';

function assertPaymentEmail(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$order = [
    'id' => 2042,
    'ho_ten' => 'Nguyễn An',
    'ngay_dat' => '2026-10-23 09:30:00',
    'trang_thai' => 'Thành công',
    'dia_chi' => '92 Bùi Tư Toàn, Bình Tân',
    'sdt' => '0338008284',
    'tam_tinh' => 19990000,
    'tien_giam' => 1000000,
    'ma_voucher' => 'BP10',
    'phi_ship' => 30000,
    'tong_tien' => 19020000,
];
$items = [[
    'ten' => 'Điện thoại <Pro> & Max',
    'ma_sp' => 'BP-PRO-1',
    'so_luong' => 1,
    'don_gia' => 19990000,
]];
$html = thongbao_thanhtoan::renderHtml($order, $items, 'Chuyển khoản QR');

assertPaymentEmail(str_contains($html, 'BP Store') && str_contains($html, 'THANH TOÁN THÀNH CÔNG'), 'email uses BP Store branding and paid status');
assertPaymentEmail(str_contains($html, '#2042') && str_contains($html, 'Chuyển khoản QR'), 'email includes order and payment details');
assertPaymentEmail(str_contains($html, 'Điện thoại &lt;Pro&gt; &amp; Max') && !str_contains($html, '<Pro>'), 'product names are HTML escaped');
assertPaymentEmail(str_contains($html, 'Tạm tính') && str_contains($html, 'Giảm giá') && str_contains($html, 'Phí giao hàng') && str_contains($html, '19.020.000đ'), 'email includes an itemized payment summary');
assertPaymentEmail(str_contains($html, '0338 008 284') && str_contains($html, 'mailto:qbaor39@gmail.com'), 'email footer provides the store contact details');
assertPaymentEmail(str_contains($html, 'theodoidonhang&amp;id=2042'), 'email links directly to order tracking');

echo "Payment email template scenarios passed.\n";
