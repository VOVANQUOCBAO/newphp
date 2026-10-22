<?php

require_once dirname(__DIR__) . '/model/sanpham.php';

class giohang
{
    public static function get(): array
    {
        if (!isset($_SESSION['giohang']) || !is_array($_SESSION['giohang'])) {
            $_SESSION['giohang'] = [];
        }
        return $_SESSION['giohang'];
    }

    public static function them(int $idSp, int $sl = 1): array
    {
        if ($sl <= 0) {
            return ['ok' => false, 'loi' => 'So luong khong duoc am hoac bang 0'];
        }
        $sp = (new sanpham())->layChiTiet($idSp);
        if ($sp === null) {
            return ['ok' => false, 'loi' => 'San pham khong ton tai'];
        }
        if ((int) $sp['so_luong_ton'] <= 0) {
            return ['ok' => false, 'loi' => 'San pham het hang'];
        }
        $gio = self::get();
        $hienTai = (int) ($gio[$idSp] ?? 0);
        $moi = $hienTai + $sl;
        if ($moi > (int) $sp['so_luong_ton']) {
            return ['ok' => false, 'loi' => 'Vuot qua so luong ton kho'];
        }
        $gio[$idSp] = $moi;
        $_SESSION['giohang'] = $gio;
        return ['ok' => true, 'giohang' => self::chiTiet()];
    }

    public static function sua(int $idSp, int $sl): array
    {
        if ($sl < 0) {
            return ['ok' => false, 'loi' => 'So luong khong duoc am'];
        }
        if ($sl === 0) {
            return self::xoa($idSp);
        }
        $sp = (new sanpham())->layChiTiet($idSp);
        if ($sp === null) {
            return ['ok' => false, 'loi' => 'San pham khong ton tai'];
        }
        if ($sl > (int) $sp['so_luong_ton']) {
            return ['ok' => false, 'loi' => 'Vuot qua so luong ton kho'];
        }
        $gio = self::get();
        $gio[$idSp] = $sl;
        $_SESSION['giohang'] = $gio;
        return ['ok' => true, 'giohang' => self::chiTiet()];
    }

    public static function xoa(int $idSp): array
    {
        $gio = self::get();
        unset($gio[$idSp]);
        $_SESSION['giohang'] = $gio;
        return ['ok' => true, 'giohang' => self::chiTiet()];
    }

    public static function xoaHet(): void
    {
        unset($_SESSION['giohang']);
    }

    public static function gioThanhToan(): array
    {
        return ($_SESSION['checkout_source'] ?? '') === 'direct' ? ($_SESSION['mua_ngay'] ?? []) : self::get();
    }

    public static function chiTietThanhToan(): array { return self::chiTiet(self::gioThanhToan()); }

    public static function xoaDaThanhToan(): void
    {
        if (($_SESSION['checkout_source'] ?? '') === 'direct') unset($_SESSION['mua_ngay']);
        else self::xoaHet();
    }

    public static function chiTiet(?array $selected = null): array
    {
        $gio = $selected ?? self::get();
        $spModel = new sanpham();
        $kmModel = null;
        $kmFile = dirname(__DIR__) . '/model/khuyenmai.php';
        if (is_file($kmFile)) {
            require_once $kmFile;
            $kmModel = new khuyenmai();
        }
        $rows = [];
        $tong = 0.0;
        foreach ($gio as $idSp => $sl) {
            $sp = $spModel->layChiTiet((int) $idSp);
            if ($sp === null) {
                continue;
            }
            $gia = (float) $sp['gia'];
            if ($kmModel !== null) {
                $gia = $kmModel->giaSauKM($sp)['gia_km'];
            }
            $thanhTien = $gia * (int) $sl;
            $tong += $thanhTien;
            $rows[] = [
                'id' => (int) $sp['id'],
                'id_danhmuc' => (int)$sp['id_danhmuc'],
                'ma_sp' => $sp['ma_sp'],
                'ten_sp' => $sp['ten_sp'],
                'hinh' => $sp['hinh'],
                'gia' => $gia,
                'so_luong' => (int) $sl,
                'ton' => (int) $sp['so_luong_ton'],
                'thanh_tien' => $thanhTien,
                'het_hang' => (int) $sp['so_luong_ton'] <= 0,
            ];
        }
        return ['items' => $rows, 'tong' => $tong];
    }
}

// Hàm kiểm tra hỗ trợ bài mẫu Lab 3.1 của thầy
if (!function_exists('kiemtra')) {
    function kiemtra($id): int {
        if (!isset($_SESSION['giohang']) || !is_array($_SESSION['giohang'])) {
            return -1;
        }
        $targetId = is_array($id) ? ($id[0] ?? null) : $id;
        if ($targetId === null) {
            return -1;
        }
        for ($i = 0; $i < count($_SESSION['giohang']); $i++) {
            if (isset($_SESSION['giohang'][$i][0]) && $_SESSION['giohang'][$i][0] == $targetId) {
                return $i;
            }
        }
        if (array_key_exists($targetId, $_SESSION['giohang'])) {
            return (int)$targetId;
        }
        return -1;
    }
}

