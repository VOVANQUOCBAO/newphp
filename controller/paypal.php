<?php

// Included by the front controller after loading models and validating scalar inputs.
require_once BASE_PATH . '/model/thanhtoan_paypal.php';


class paypal_controller
{
    public static function checkoutToken(): string
    {
        if (!is_string($_SESSION['checkout_csrf'] ?? null)) $_SESSION['checkout_csrf'] = bin2hex(random_bytes(32));
        return $_SESSION['checkout_csrf'];
    }

    public static function checkPost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !is_string($_POST['_checkout_csrf'] ?? null)
            || !hash_equals(self::checkoutToken(), $_POST['_checkout_csrf'])) {
            http_response_code(403);
            throw new paypal_error('csrf', 'Phiên thanh toán không hợp lệ. Vui lòng tải lại trang.');
        }
    }

    public static function expireFor(int $owner): void
    {
        (new donhang())->donPayPalHetHan($owner);
    }

    public static function startCheckout(controller $ctrl, array $user): void
    {
        $cfg = paypal_config::load();
        $cart = giohang::chiTietThanhToan();
        $error = null;
        try {
            self::checkPost();
            if (!paypal_config::ready($cfg) || !thanhtoan_paypal::schemaReady()) {
                throw new paypal_error('configuration', 'PayPal chưa được cửa hàng bật nhận tiền.');
            }
            $key = $_SESSION['paypal_checkout_key'] ?? '';
            if (!is_string($key) || !preg_match('/^[a-f0-9]{64}$/', $key)) throw new paypal_error('session', 'Phiên đặt hàng hết hạn. Hãy tải lại trang.');
            $model = new thanhtoan_paypal();
            $existing = $model->theoKey($key, (int) $user['id']);
            if ($existing && $existing['status'] !== 'cancelled') $ctrl->redirect('paypal', ['id' => $existing['id_donhang']]);
            $rate = paypal_config::rate($cfg);
            $result = (new donhang())->taoDon((int) $user['id'], giohang::gioThanhToan(), [
                'dia_chi' => $_POST['dia_chi'] ?? '', 'sdt' => $_POST['sdt'] ?? '', 'checkout_key'=>$key,
                'paypal' => ['checkout_key' => $key, 'rate' => $rate],
            ], $_POST['ma_voucher'] ?? null);
            if (empty($result['ok'])) {
                // A concurrent repeat can lose the unique-key race; reuse the committed order.
                $duplicate = $model->theoKey($key, (int) $user['id']);
                if ($duplicate) $ctrl->redirect('paypal', ['id' => $duplicate['id_donhang']]);
                throw new paypal_error('order', (string) $result['loi']);
            }
            $id = (int) $result['id'];
            $_SESSION['paypal_pending_id'] = $id;
            $_SESSION['paypal_return_state'][$id] = bin2hex(random_bytes(32));
            // This is a reserved, unpaid order. It remains visible in history until paid/cancelled/expired.
            giohang::xoaDaThanhToan();
            try {
                (new xuly_email())->guiDonHang((int) $user['id'], $id, (new donhang())->layTheoId($id), $result['dong']);
            } catch (Throwable $e) { error_log('PayPal pending-order email failed'); }
            $ctrl->redirect('paypal', ['id' => $id]);
        } catch (paypal_error $e) {
            $error = $e->getMessage();
        }
        $ctrl->render('thanhtoan.php', ['giohang' => $cart, 'user' => $user, 'act' => 'thanhtoan',
            'loi' => $error, 'thongBao' => null, 'paypalAvailable' => paypal_config::ready($cfg),
            'checkoutToken' => self::checkoutToken(), 'pendingPayPal' => $_SESSION['paypal_pending_id'] ?? null]);
    }

    public static function route(controller $ctrl, string $act): void
    {
        $user = $ctrl->requireUser();
        if ($user === null) $ctrl->redirect('dangnhap');
        $owner = (int) $user['id'];
        $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
        if (!thanhtoan_paypal::schemaReady()) {
            http_response_code(503);
            $ctrl->json(['ok' => false, 'loi' => 'PayPal chưa sẵn sàng.']);
            return;
        }
        $model = new thanhtoan_paypal();
        $payment = $model->lay($id, $owner);
        if (!$payment) {
            $ctrl->json(['ok' => false, 'loi' => 'Không tìm thấy đơn thanh toán.'], 404);
            return;
        }
        $error = null;
        $notice = null;
        try {
            if (in_array($act, ['paypal_return', 'paypal_cancel'], true)) {
                $state = $_SESSION['paypal_return_state'][$id] ?? '';
                if (!is_string($_GET['state'] ?? null) || !is_string($state) || $state === '' || !hash_equals($state, $_GET['state'])
                    || ($act === 'paypal_return' && ($_GET['token'] ?? '') !== $payment['provider_order_id'])) {
                    http_response_code(403);
                    throw new paypal_error('state', 'Phiên quay về từ PayPal không hợp lệ. Hãy kiểm tra trạng thái đơn hàng.');
                }
                if ($act === 'paypal_return') $_SESSION['paypal_auto_capture'][$id] = true;
                else $_SESSION['paypal_cancel_notice'][$id] = true;
                $ctrl->redirect('paypal', ['id' => $id]);
            }
            if ($act !== 'paypal') self::checkPost();
            if ($act === 'paypal_create') {
                if (!is_string($_SESSION['paypal_return_state'][$id] ?? null)) $_SESSION['paypal_return_state'][$id] = bin2hex(random_bytes(32));
                $approval = $model->taoPayPal($id, $owner, $_SESSION['paypal_return_state'][$id]);
                header('Location: ' . $approval, true, 303);
                return;
            }
            if ($act === 'paypal_capture') {
                $payment = $model->capture($id, $owner);
            } elseif ($act === 'paypal_check') {
                $payment = $model->kiemTra($id, $owner);
            } elseif ($act === 'paypal_abandon') {
                $result = (new donhang())->huyDon($id, $owner, 'Khách hủy đơn PayPal chưa thanh toán');
                if (empty($result['ok'])) throw new paypal_error('cancel', (string) $result['loi']);
                $payment = $model->lay($id, $owner);
            }
            if (in_array($payment['status'], ['created', 'awaiting', 'approved', 'failed'], true) && strtotime($payment['expires_at']) <= time()) {
                (new donhang())->hetHanPayPal($id);
                $payment = $model->lay($id, $owner);
            }
        } catch (paypal_error $e) {
            $error = $e->getMessage();
            $payment = $model->lay($id, $owner);
        } catch (Throwable $e) {
            error_log('PayPal controller order #' . $id . ': internal');
            $error = 'Chưa xác định được kết quả thanh toán. Hãy kiểm tra trạng thái hoặc liên hệ cửa hàng trước khi thanh toán lại.';
            $payment = $model->lay($id, $owner);
        }
        if (in_array($payment['status'], ['paid', 'cancelled', 'refunded'], true)) {
            unset($_SESSION['paypal_checkout_key'], $_SESSION['paypal_pending_id'], $_SESSION['paypal_return_state'][$id]);
        }
        $autoCapture = !empty($_SESSION['paypal_auto_capture'][$id]) && !in_array($payment['status'], ['paid', 'cancelled', 'refunded', 'review'], true);
        unset($_SESSION['paypal_auto_capture'][$id]);
        if (!empty($_SESSION['paypal_cancel_notice'][$id])) {
            $notice = 'Bạn đã đóng PayPal. Nếu chưa thanh toán, bạn có thể thử lại hoặc hủy đơn này.';
            unset($_SESSION['paypal_cancel_notice'][$id]);
        }
        $public = thanhtoan_paypal::publicData($payment);
        $ctrl->render('paypal.php', ['payment' => $public, 'checkoutToken' => self::checkoutToken(), 'autoCapture' => $autoCapture,
            'loi' => $error, 'thongBao' => $notice, 'act' => 'paypal']);
    }
}
