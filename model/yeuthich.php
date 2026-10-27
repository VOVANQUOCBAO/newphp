<?php
require_once __DIR__.'/xl_database.php';
class yeuthich extends xl_database
{
    public function dat(int $customer,int $product,bool $save): array
    {
        if (!$this->selectOne('SELECT id FROM khachhang WHERE id=? AND trang_thai=1',[$customer])) return ['ok'=>false,'loi'=>'Cần đăng nhập tài khoản đang hoạt động.'];
        if (!$save) { $this->delete('yeuthich','id_khachhang=? AND id_sanpham=?',[$customer,$product]);return ['ok'=>true]; }
        $n=$this->execute('INSERT IGNORE INTO yeuthich (id_khachhang,id_sanpham) SELECT ?,id FROM sanpham WHERE id=? AND ngung_ban=0',[$customer,$product]);
        return $n>0 || $this->daLuu($customer,$product) ? ['ok'=>true] : ['ok'=>false,'loi'=>'Sản phẩm không còn được bán.'];
    }
    public function daLuu(int $customer,int $product): bool
    {
        return $this->selectOne('SELECT id_sanpham FROM yeuthich WHERE id_khachhang=? AND id_sanpham=?',[$customer,$product])!==null;
    }
    public function layDS(int $customer): array
    {
        return $this->select('SELECT sp.* FROM yeuthich y JOIN sanpham sp ON sp.id=y.id_sanpham WHERE y.id_khachhang=? AND sp.ngung_ban=0 ORDER BY y.ngay_tao DESC,sp.id DESC',[$customer]);
    }
}
