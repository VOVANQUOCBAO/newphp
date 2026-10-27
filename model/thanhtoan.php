<?php

require_once __DIR__ . '/voucher.php';
require_once __DIR__ . '/donhang.php';

/** Chuẩn bị báo giá và voucher để xem trước; không tạo đơn hay trừ kho. */
final class thanhtoan
{
    public function duLieuHienThi(array $data): array
    {
        if (empty($data['user']) || !empty($data['ok'])) return $data;
        $data['bankAvailable'] = thanhtoan_qr::ready() && thanhtoan_qr::schemaReady();
        $data['codCheckoutKey'] = $_SESSION['cod_checkout_key'] ?? '';
        $code = trim((string)($_POST['ma_voucher'] ?? ''));
        $cart = $data['giohang'];
        $shipping = in_array($data['user']['hang'], ['Gold','Diamond'], true) ? 0.0 : donhang::PHI_SHIP_MAC_DINH;
        $data['voucherQuote'] = $code === '' ? ['ok' => true, 'giam' => 0, 'phi_ship' => $shipping, 'tong' => $cart['tong'] + $shipping]
            : (new voucher())->kiemTra($code, $data['user']['hang'], (float)$cart['tong'], $shipping, false, $cart['items']);
        $data['availableVouchers'] = array_values(array_filter((new voucher())->layDS(),static fn($v)=>(int)$v['so_luong']>0 && date('Y-m-d')>=$v['ngay_bd'] && date('Y-m-d')<=$v['ngay_kt']));
        usort($data['availableVouchers'],static fn($a,$b)=>[$a['ngay_kt'],$a['id']]<=>[$b['ngay_kt'],$b['id']]);
        foreach ($data['availableVouchers'] as &$vc) {
            $quote=(new voucher())->kiemTra($vc['ma_code'],$data['user']['hang'],(float)$cart['tong'],$shipping,false,$cart['items']);
            $vc['pham_vi_label']=voucher::scopeLabel($vc);
            $vc['du_dieu_kien']=$quote['ok']; $vc['ly_do']=$quote['loi'] ?? null;
        }
        unset($vc);
        return $data;
    }
}
