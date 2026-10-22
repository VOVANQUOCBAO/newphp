<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once __DIR__ . '/../../model/donhang.php';
$model = new donhang();
$total = 0;
do { $count = $model->donPayPalHetHan(); $total += $count; } while ($count === 50);
echo "Đã giải phóng $total đơn PayPal hết hạn, chưa thanh toán.\n";
