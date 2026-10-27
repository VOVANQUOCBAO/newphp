<?php
require_once __DIR__ . '/xl_database.php';
require_once __DIR__ . '/chinhsach_thanhvien.php';

final class diem_thanhvien extends xl_database
{
    /** The caller must obtain this ID from the authenticated session or an admin route. */
    public function baoCao(int $idKhach, int $trang = 1): array
    {
        $khach = $this->selectOne('SELECT id,ho_ten,diem,hang FROM khachhang WHERE id=?', [$idKhach]);
        if (!$khach) throw new DomainException('Không tìm thấy khách hàng.');
        $stats = $this->selectOne('SELECT COUNT(*) tong_don, COALESCE(SUM(diem_da_cong),0) diem_don_hang FROM donhang WHERE id_khachhang=?', [$idKhach]);
        $tongTrang = max(1, (int) ceil((int)$stats['tong_don']/20));
        $trang = max(1, min($tongTrang, $trang));
        $offset = ($trang-1)*20;
        $orders = $this->select("SELECT id,ngay_dat,trang_thai,tam_tinh,tien_giam,phi_ship,tong_tien,diem_da_cong FROM donhang WHERE id_khachhang=? ORDER BY id DESC LIMIT 20 OFFSET {$offset}", [$idKhach]);
        foreach ($orders as &$order) $order['diem_du_kien'] = chinhsach_thanhvien::tinhDiem((float)$order['tong_tien']);
        unset($order);
        return ['khach'=>$khach, 'tien_do'=>chinhsach_thanhvien::tienDo((int)$khach['diem']),
            'cac_hang'=>chinhsach_thanhvien::cacHang(), 'don_hang'=>$orders,
            'diem_don_hang'=>(int)$stats['diem_don_hang'],
            'chenh_lech'=>(int)$khach['diem']-(int)$stats['diem_don_hang'],
            'trang'=>$trang, 'tong_trang'=>$tongTrang];
    }
}
