<?php

require_once __DIR__ . '/xl_database.php';

class chitietdonhang extends xl_database
{
    public function themNhieu(int $idDonHang, array $dong): void
    {
        foreach ($dong as $item) {
            $this->insert('chitietdonhang', [
                'id_donhang' => $idDonHang,
                'id_sanpham' => (int) $item['id_sanpham'],
                'so_luong' => (int) $item['so_luong'],
                'don_gia' => (float) $item['don_gia'],
                'ten_sp_snapshot' => $item['ten_sp'] ?? null,
                'ma_sp_snapshot' => $item['ma_sp'] ?? null,
                'hinh_snapshot' => $item['hinh'] ?? null,
            ]);
        }
    }

    public function layTheoDon(int $idDonHang): array
    {
        return $this->select(
            'SELECT ct.*, COALESCE(ct.ten_sp_snapshot, sp.ten_sp) AS ten_sp, COALESCE(ct.ma_sp_snapshot, sp.ma_sp) AS ma_sp, COALESCE(ct.hinh_snapshot, sp.hinh) AS hinh
             FROM chitietdonhang ct
             JOIN sanpham sp ON sp.id = ct.id_sanpham
             WHERE ct.id_donhang = ?',
            [$idDonHang]
        );
    }

    public function sanPhamDaBan(int $idSanPham): bool
    {
        $row = $this->selectOne(
            'SELECT COUNT(*) AS c FROM chitietdonhang WHERE id_sanpham = ?',
            [$idSanPham]
        );
        return (int) ($row['c'] ?? 0) > 0;
    }
}
