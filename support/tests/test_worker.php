<?php

// Tien trinh con cho cac ca canh tranh CSDL; khong cho goi qua web.
if (PHP_SAPI !== 'cli' || !preg_match('/^webbanhang_mvc_test_[a-f0-9]+$/', getenv('DB_NAME') ?: '')) {
    http_response_code(403);
    exit(1);
}
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once dirname(__DIR__, 2) . '/model/donhang.php';
$start = (float) ($argv[2] ?? 0);
while (microtime(true) < $start) {
    usleep(1000);
}
$model = new donhang();
$args = json_decode($argv[3] ?? '{}', true, 512, JSON_THROW_ON_ERROR);
$result = match ($argv[1] ?? '') {
    'checkout' => $model->taoDon($args['kh'], [$args['sp'] => 1], ['dia_chi' => '1 Nguyen Hue', 'sdt' => '0900000000'], $args['voucher'] ?? null),
    'huy' => $model->huyDon($args['don'], $args['kh'], 'Test dong thoi'),
    'trangthai' => $model->capNhatTrangThai($args['don'], $args['trangthai']),
    'qr-race' => $args['op']==='receive' ? (new thanhtoan_qr())->xacNhanNhan($args['don'],$args['admin'],'RACE-QR-'.$args['don'],130000) : $model->huyDon($args['don'],$args['kh'],'Race cancel'),
    default => ['ok' => false],
};
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
