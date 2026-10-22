<?php

require_once __DIR__ . '/xl_database.php';

class email extends xl_database
{
    public function khieuNaiAdmin(): array
    {
        return $this->select("SELECT e.*,k.ho_ten FROM email e JOIN khachhang k ON k.id=e.id_khachhang WHERE e.loai='khieunai' ORDER BY e.id DESC LIMIT 50");
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
