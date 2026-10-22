<?php

require_once dirname(__DIR__) . '/model/email.php';
require_once dirname(__DIR__) . '/model/khachhang.php';
require_once dirname(__DIR__) . '/model/chitietdonhang.php';

// Ban tai tu GitHub co san PHPMailer; Composer van duoc ho tro trong support/.
$mailAutoload = dirname(__DIR__) . '/support/vendor/autoload.php';
if (is_file($mailAutoload)) {
    require_once $mailAutoload;
} else {
    foreach (['Exception', 'PHPMailer', 'SMTP'] as $mailClass) {
        $mailFile = dirname(__DIR__) . '/support/lib/PHPMailer/' . $mailClass . '.php';
        if (is_file($mailFile)) {
            require_once $mailFile;
        }
    }
}

class xuly_email
{
    public function guiDonHang(int $idKh, int $idDon, array $don, array $dong): array
    {
        $kh = (new khachhang())->layTheoId($idKh);
        $dongText = '';
        foreach ($dong as $item) {
            $dongText .= '- SP#' . $item['id_sanpham'] . ' x' . $item['so_luong']
                . ' @ ' . $item['don_gia'] . "\n";
        }
        $qr = class_exists('thanhtoan_qr') ? (new thanhtoan_qr())->lay($idDon) : null;
        $tieuDe = 'Xac nhan don hang #' . $idDon;
        $noiDung = "Xin chao " . ($kh['ho_ten'] ?? '') . ",\n"
            . "Ngay dat: " . date('Y-m-d H:i:s') . "\n"
            . "Trang thai: " . ($don['trang_thai'] ?? 'Chờ xác nhận') . "\n"
            . "Tong tien: " . ($don['tong_tien'] ?? '') . "\n"
            . "Thanh toan: " . ($qr ? thanhtoan_qr::label($qr) : (class_exists('thanhtoan_paypal') && thanhtoan_paypal::schemaReady()
                ? thanhtoan_paypal::label((new thanhtoan_paypal())->lay($idDon)) : 'COD · thu tiền khi giao hàng')) . "\n"
            . "Chi tiet:\n{$dongText}";

        $idThu = (new email())->luuThu($idKh, $tieuDe, $noiDung, 'donhang');
        $smtp = $this->guiSmtp($kh['email'] ?? '', $tieuDe, $noiDung);
        return ['ok' => true, 'id_thu' => $idThu, 'smtp' => $smtp];
    }

    public function guiChung(int $idKh, string $tieuDe, string $noiDung, string $loai = 'hethong'): array
    {
        $kh = (new khachhang())->layTheoId($idKh);
        $idThu = (new email())->luuThu($idKh, $tieuDe, $noiDung, $loai);
        $smtp = $this->guiSmtp($kh['email'] ?? '', $tieuDe, $noiDung);
        return ['ok' => true, 'id_thu' => $idThu, 'smtp' => $smtp];
    }

    public function guiOtp(string $toEmail, string $otp, string $hoTen = ''): array
    {
        $tieuDe = "[ĐiệnThoạiZ] Mã xác thực đăng ký tài khoản: " . $otp;
        $hoTenStr = $hoTen ?: 'Quý khách';
        $html = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #ffffff;'>
            <div style='background: linear-gradient(135deg, #0d6efd, #0b5ed7); padding: 24px; text-align: center; color: #ffffff;'>
                <h2 style='margin: 0; font-size: 24px; letter-spacing: 1px;'>ĐiệnThoại<span style='color: #ffc107;'>Z</span></h2>
                <p style='margin: 6px 0 0; opacity: 0.9; font-size: 14px;'>Thế Giới Smartphone Chính Hãng Hàng Đầu</p>
            </div>
            <div style='padding: 30px 24px; color: #334155; line-height: 1.6;'>
                <p style='font-size: 16px; margin-top: 0;'>Xin chào <strong>" . htmlspecialchars($hoTenStr) . "</strong>,</p>
                <p>Cảm ơn bạn đã lựa chọn đăng ký tài khoản thành viên tại hệ thống ĐiệnThoạiZ.</p>
                <p>Dưới đây là <strong>Mã xác thực (OTP)</strong> để kích hoạt tài khoản Gmail của bạn:</p>
                <div style='text-align: center; margin: 28px 0;'>
                    <span style='display: inline-block; background: #eff6ff; border: 2px dashed #3b82f6; color: #1d4ed8; font-size: 32px; font-weight: bold; letter-spacing: 8px; padding: 12px 28px; border-radius: 8px;'>
                        {$otp}
                    </span>
                </div>
                <p style='font-size: 13px; color: #64748b; text-align: center;'>Mã này có hiệu lực trong vòng <strong>15 phút</strong>. Tuyệt đối không chia sẻ mã này cho bất kỳ ai.</p>
                <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;'>
                <p style='font-size: 12px; color: #94a3b8; margin-bottom: 0;'>Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này. Hỗ trợ hotline miễn phí: 1800.6868.</p>
            </div>
        </div>";
        $text = "Xin chào {$hoTenStr},\nMã OTP xác thực Gmail của bạn là: {$otp}\nMã có hiệu lực trong 15 phút. ĐiệnThoạiZ xin cảm ơn!";
        return $this->guiSmtp($toEmail, $tieuDe, $text, $html);
    }

    public function guiChaoMung(string $toEmail, string $hoTen = ''): array
    {
        $tieuDe = "[ĐiệnThoạiZ] Chúc mừng bạn đã đăng ký tài khoản thành công!";
        $hoTenStr = $hoTen ?: 'Quý khách';
        $html = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #ffffff;'>
            <div style='background: linear-gradient(135deg, #198754, #157347); padding: 24px; text-align: center; color: #ffffff;'>
                <h2 style='margin: 0; font-size: 24px;'>ĐiệnThoại<span style='color: #ffc107;'>Z</span></h2>
                <p style='margin: 6px 0 0; font-size: 14px;'>Đăng ký thành viên thành công 🎉</p>
            </div>
            <div style='padding: 30px 24px; color: #334155; line-height: 1.6;'>
                <p style='font-size: 16px; margin-top: 0;'>Kính chào <strong>" . htmlspecialchars($hoTenStr) . "</strong>,</p>
                <p>Tài khoản Gmail của bạn đã được kích hoạt thành công trên hệ thống <strong>ĐiệnThoạiZ</strong>.</p>
                <div style='background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin: 20px 0;'>
                    <div style='font-weight: bold; color: #0d6efd; margin-bottom: 8px;'>Đặc quyền thành viên mới:</div>
                    <ul style='margin: 0; padding-left: 20px; font-size: 14px; color: #475569;'>
                        <li>Hạng thành viên: <strong>Silver (Bạc)</strong></li>
                        <li>Tích lũy điểm thưởng trên mỗi đơn hàng</li>
                        <li>Nhận quà tặng và Voucher đặc biệt vào ngày sinh nhật</li>
                        <li>Bảo hành chính hãng 12 tháng - Giao hàng siêu tốc 1h</li>
                    </ul>
                </div>
                <p>Chúc bạn có trải nghiệm mua sắm tuyệt vời tại ĐiệnThoạiZ!</p>
            </div>
        </div>";
        $text = "Kính chào {$hoTenStr},\nTài khoản của bạn đã được tạo thành công tại ĐiệnThoạiZ. Cảm ơn bạn!";
        return $this->guiSmtp($toEmail, $tieuDe, $text, $html);
    }

    public function guiSmtp(string $to, string $tieuDe, string $noiDung, ?string $htmlContent = null): array
    {
        $cfg = (new email())->cauHinhSmtp();
        if ($cfg['user'] === '' || $cfg['pass'] === '') {
            return ['ok' => false, 'loi' => 'Chua cau hinh SMTP'];
        }
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return ['ok' => false, 'loi' => 'Thieu thu vien PHPMailer'];
        }
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $cfg['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $cfg['user'];
            $mail->Password = $cfg['pass'];
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int) $cfg['port'];
            $mail->Timeout = 10;
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($cfg['from'], $cfg['from_name']);
            $mail->addAddress($to);
            $mail->Subject = $tieuDe;
            if ($htmlContent !== null) {
                $mail->isHTML(true);
                $mail->Body = $htmlContent;
                $mail->AltBody = $noiDung;
            } else {
                $mail->Body = $noiDung;
            }
            $mail->send();
            return ['ok' => true];
        } catch (Throwable $e) {
            return ['ok' => false, 'loi' => $e->getMessage()];
        }
    }
}
