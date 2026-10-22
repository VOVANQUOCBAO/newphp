<?php
require_once BASE_PATH . '/model/thanhtoan_qr.php';

function bankRoute(controller $ctrl, string $act): void
{
    $user = $ctrl->requireUser();
    if ($user === null) $ctrl->redirect('dangnhap');
    $model = new thanhtoan_qr();
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    $payment = $model->lay($id,(int)$user['id']);
    if (!$payment) { http_response_code(404); $ctrl->render('bank.php',['payment'=>null,'loi'=>'Không tìm thấy thanh toán của bạn.']); return; }
    if ($act === 'baochuyenkhoan') {
        $result = $model->baoDaChuyen($id,(int)$user['id']);
        $_SESSION['bank_notice'] = ['id'=>$id,'loi'=>$result['loi'] ?? null,'thongBao'=>$result['ok'] ? 'Đã báo cửa hàng kiểm tra tiền. Đơn chưa được xác nhận thanh toán.' : null];
        $ctrl->redirect('bank',['id'=>$id]);
    }
    (new donhang())->donQRHetHan();
    $payment = $model->lay($id,(int)$user['id']);
    $notice = $_SESSION['bank_notice'] ?? [];
    unset($_SESSION['bank_notice']);
    $ctrl->render('bank.php',['payment'=>$payment,'qrUrl'=>thanhtoan_qr::qrUrl($payment),
        'loi'=>($notice['id'] ?? null)===$id ? ($notice['loi'] ?? null) : null,
        'thongBao'=>($notice['id'] ?? null)===$id ? ($notice['thongBao'] ?? null) : null]);
}
