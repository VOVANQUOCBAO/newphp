<?php

require_once __DIR__ . '/xl_database.php';

class email extends xl_database
{
    public function khieuNaiAdmin(): array
    {
        $complaints = $this->select("SELECT e.*,k.ho_ten,k.email AS email_khach FROM email e JOIN khachhang k ON k.id=e.id_khachhang WHERE e.loai='khieunai' ORDER BY e.id DESC LIMIT 50");
        if ($complaints === []) return [];

        $customerIds = array_values(array_unique(array_map('intval', array_column($complaints, 'id_khachhang'))));
        $holders = implode(',', array_fill(0, count($customerIds), '?'));
        $replies = $this->select("SELECT id,id_khachhang,tieu_de,noi_dung,ngay_gui FROM email WHERE loai='phanhoi_khieunai' AND id_khachhang IN ($holders) ORDER BY id ASC LIMIT 1000", $customerIds);
        $complaintCustomers = [];
        foreach ($complaints as $complaint) $complaintCustomers[(int)$complaint['id']] = (int)$complaint['id_khachhang'];
        $replyMap = [];
        foreach ($replies as $reply) {
            if (preg_match('/^Phản hồi khiếu nại #(\d+) · BP Store$/u', (string)$reply['tieu_de'], $match) !== 1) continue;
            $complaintId = (int)$match[1];
            if (($complaintCustomers[$complaintId] ?? 0) !== (int)$reply['id_khachhang']) continue;
            $replyMap[$complaintId][] = $reply;
        }
        foreach ($complaints as &$complaint) $complaint['phan_hoi'] = $replyMap[(int)$complaint['id']] ?? [];
        unset($complaint);
        return $complaints;
    }

    public function luuPhanHoiKhieuNai(int $complaintId, string $replyText): array
    {
        $replyText = trim($replyText);
        if ($complaintId < 1 || $replyText === '' || mb_strlen($replyText) > 5000) {
            return ['ok' => false, 'loi' => 'Nội dung phản hồi phải từ 1 đến 5.000 ký tự.'];
        }
        $complaint = $this->selectOne(
            "SELECT e.id,e.id_khachhang,k.ho_ten,k.email FROM email e JOIN khachhang k ON k.id=e.id_khachhang WHERE e.id=? AND e.loai='khieunai'",
            [$complaintId]
        );
        if ($complaint === null) return ['ok' => false, 'loi' => 'Không tìm thấy khiếu nại của khách hàng.'];

        $title = 'Phản hồi khiếu nại #' . $complaintId . ' · BP Store';
        $name = trim((string)$complaint['ho_ten']) ?: 'Quý khách';
        $body = "Xin chào {$name},\n\nBP Store phản hồi khiếu nại #{$complaintId} của bạn:\n\n{$replyText}\n\nBạn có thể xem lại nội dung này trong Hộp thư cá nhân trên website BP Store.\n\nBP Store · Chăm sóc khách hàng";
        $mailId = $this->luuThu((int)$complaint['id_khachhang'], $title, $body, 'phanhoi_khieunai');
        return ['ok' => true, 'id_thu' => $mailId, 'email' => (string)$complaint['email'],
            'ho_ten' => $name, 'tieu_de' => $title, 'noi_dung' => $body, 'noi_dung_tra_loi' => $replyText];
    }

    public function cauHinhSmtp(): array
    {
        $cfg = [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'user' => getenv('SMTP_USER') ?: '',
            'pass' => getenv('SMTP_PASS') ?: '',
            'from' => getenv('SMTP_FROM') ?: (getenv('SMTP_USER') ?: 'noreply@localhost'),
            'from_name' => 'ĐiệnThoạiZ - Thế Giới Smartphone',
        ];
        $file = dirname(__DIR__) . '/support/config/smtp.local.php';
        if (is_file($file)) {
            $local = require $file;
            if (is_array($local)) {
                $cfg = array_replace($cfg, $local);
            }
        }
        return $cfg;
    }

    public function luuCauHinh(array $data): array
    {
        $old=$this->cauHinhSmtp();
        $cfg=['host'=>trim((string)($data['host'] ?? '')),'port'=>(int)($data['port'] ?? 587),
            'user'=>trim((string)($data['user'] ?? '')),'pass'=>(string)($data['smtp_pass'] ?? ''),
            'from'=>trim((string)($data['from'] ?? '')),'from_name'=>trim((string)($data['from_name'] ?? 'ĐiệnThoạiZ'))];
        if ($cfg['pass']==='') $cfg['pass']=$old['pass'];
        if (!preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/D',$cfg['host']) || !in_array($cfg['port'],[465,587],true)
            || !filter_var($cfg['user'],FILTER_VALIDATE_EMAIL) || !filter_var($cfg['from'],FILTER_VALIDATE_EMAIL)
            || $cfg['pass']==='' || mb_strlen($cfg['from_name'])>150) return ['ok'=>false,'loi'=>'Điền máy chủ SMTP, cổng 465/587, email gửi và mật khẩu ứng dụng hợp lệ.'];
        $path=dirname(__DIR__).'/support/config/smtp.local.php';
        if (file_put_contents($path,"<?php\nreturn ".var_export($cfg,true).";\n",LOCK_EX)===false) return ['ok'=>false,'loi'=>'Không lưu được cấu hình gửi thư.'];
        @chmod($path,0600);
        return ['ok'=>true];
    }

    public function luuThu(int $idKhachHang, string $tieuDe, string $noiDung, string $loai = 'hethong'): int
    {
        return $this->insert('email', [
            'id_khachhang' => $idKhachHang,
            'tieu_de' => $tieuDe,
            'noi_dung' => $noiDung,
            'loai' => $loai,
        ]);
    }

    public function layThuCuaKhach(int $idKhachHang): array
    {
        return $this->select(
            'SELECT * FROM email WHERE id_khachhang = ? ORDER BY ngay_gui DESC',
            [$idKhachHang]
        );
    }
}
