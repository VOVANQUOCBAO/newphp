<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../../model/kiemtra_dulieu.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');
$data = ['ngay_kiem_tra' => date(DATE_ATOM), 'ket_qua' => (new kiemtra_dulieu())->baoCao()];
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
