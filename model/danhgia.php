<?php

require_once __DIR__ . '/xl_database.php';

class danhgia extends xl_database
{
    public function layDSTheoSP(int $idSanPham): array
    {
        return $this->select(
            'SELECT dg.*, kh.ho_ten
             FROM danhgia dg
             JOIN khachhang kh ON kh.id = dg.id_khachhang
             WHERE dg.id_sanpham = ? AND dg.trang_thai = 1
               AND EXISTS (SELECT 1 FROM chitietdonhang ct JOIN donhang dh ON dh.id=ct.id_donhang
                   WHERE ct.id_sanpham=dg.id_sanpham AND dh.id_khachhang=dg.id_khachhang
                   AND dh.trang_thai IN (\'Đã giao\',\'Thành công\'))
             ORDER BY dg.ngay_tao DESC',
            [$idSanPham]
        );
    }

    public function layAllAdmin(): array
    {
        return $this->select(
            'SELECT dg.*, kh.ho_ten, kh.email, sp.ten_sp
             FROM danhgia dg
             JOIN khachhang kh ON kh.id = dg.id_khachhang
             JOIN sanpham sp ON sp.id = dg.id_sanpham
             ORDER BY dg.ngay_tao DESC'
        );
    }

    public function them(int $idSanPham, int $idKhachHang, int $soSao, string $noiDung): array
    {
        if ($soSao < 1 || $soSao > 5) {
            return ['ok' => false, 'loi' => 'So sao phai tu 1 den 5'];
        }
        $noiDung = trim($noiDung);
        if ($noiDung === '' || mb_strlen($noiDung) > 5000) {
            return ['ok' => false, 'loi' => 'Noi dung danh gia khong duoc rong'];
        }
        if ($this->selectOne('SELECT id FROM sanpham WHERE id = ?', [$idSanPham]) === null
            || $this->selectOne('SELECT id FROM khachhang WHERE id = ? AND trang_thai = 1', [$idKhachHang]) === null) {
            return ['ok' => false, 'loi' => 'San pham hoac khach hang khong hop le'];
        }
        try {
        $this->begin();
        $this->selectOne('SELECT id FROM khachhang WHERE id=? FOR UPDATE', [$idKhachHang]);
        if (!$this->duocDanhGia($idSanPham, $idKhachHang)) {
            $this->rollback();
            return ['ok' => false, 'loi' => 'Chỉ khách đã nhận sản phẩm mới được đánh giá. Mỗi sản phẩm được đánh giá một lần.'];
        }
        $id = $this->insert('danhgia', [
            'id_sanpham' => $idSanPham,
            'id_khachhang' => $idKhachHang,
            'so_sao' => $soSao,
            'noi_dung' => $noiDung,
            'trang_thai' => 1,
        ]);
        $this->commit();
        return ['ok' => true, 'id' => $id];
        } catch (Throwable $e) {
            $this->rollback();
            return ['ok' => false, 'loi' => 'Không thể lưu đánh giá lúc này'];
        }
    }

    public function duocDanhGia(int $sp, int $kh): bool
    {
        return $this->selectOne("SELECT dh.id FROM donhang dh JOIN chitietdonhang ct ON ct.id_donhang=dh.id
            WHERE dh.id_khachhang=? AND ct.id_sanpham=? AND dh.trang_thai IN ('Đã giao','Thành công') LIMIT 1", [$kh, $sp]) !== null
            && $this->selectOne('SELECT id FROM danhgia WHERE id_sanpham=? AND id_khachhang=?', [$sp, $kh]) === null;
    }

    public function khoa(int $id): array
    {
        return $this->datHienThi($id,0);
    }

    public function datHienThi(int $id, int $trangThai): array
    {
        if (!in_array($trangThai,[0,1],true)) return ['ok'=>false,'loi'=>'Trạng thái đánh giá không hợp lệ.'];
        if (!$this->selectOne('SELECT id FROM danhgia WHERE id=?',[$id])) return ['ok'=>false,'loi'=>'Không tìm thấy đánh giá'];
        $this->update('danhgia', ['trang_thai' => $trangThai], 'id = ?', [$id]);
        return ['ok' => true];
    }
}
