<?php
require_once __DIR__.'/xl_database.php';
class nhapkho extends xl_database
{
    public function ghi(int $product,int $quantity,string $date,string $note): void
    {
        if ($quantity<=0) return;
        $this->insert('lichsu_nhap',['id_sanpham'=>$product,'so_luong'=>$quantity,'ngay_nhap'=>$date,
            'nguoi_nhap'=>mb_substr((string)($_SESSION['admin']['ho_ten'] ?? $_SESSION['admin']['hoten'] ?? 'Hệ thống'),0,150),'ghi_chu'=>$note]);
    }
    public function layDS(): array
    {
        return $this->select('SELECT n.*,sp.ma_sp,sp.ten_sp FROM lichsu_nhap n JOIN sanpham sp ON sp.id=n.id_sanpham ORDER BY n.id DESC LIMIT 500');
    }
}
