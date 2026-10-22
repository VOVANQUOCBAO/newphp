<?php

require_once __DIR__ . '/xl_database.php';

class khuyenmai extends xl_database
{
    public function layDS(): array
    {
        return $this->select('SELECT km.*,sp.ten_sp,sp.ma_sp,dm.ten_danhmuc FROM khuyenmai km LEFT JOIN sanpham sp ON sp.id=km.id_sanpham LEFT JOIN danhmuc dm ON dm.id=km.id_danhmuc ORDER BY km.id DESC');
    }

    public function dsDangChay(): array
    {
        return $this->select(
            'SELECT * FROM khuyenmai WHERE CURDATE() BETWEEN ngay_bd AND ngay_kt ORDER BY phan_tram DESC'
        );
    }

    public function them(array $data): array
    {
        return $this->luu($data);
    }

    public function sua(int $id, array $data): array
    {
        if ($id<=0) return ['ok'=>false,'loi'=>'Không tìm thấy chương trình.'];
        return $this->luu($data,$id);
    }

    private function luu(array $data, int $editId=0): array
    {
        $loai = $data['loai'] ?? 'sanpham';
        if (!in_array($loai, ['sanpham', 'danhmuc'], true)) {
            return ['ok' => false, 'loi' => 'Loai khuyen mai khong hop le'];
        }
        if (trim((string) ($data['ten_km'] ?? '')) === '' || mb_strlen(trim((string)$data['ten_km']))>150
            || !$this->soTienHopLe($data['phan_tram'] ?? null) || round((float)$data['phan_tram'],2)<=0 || (float) $data['phan_tram'] > 100
            || mb_strlen((string)($data['ghi_chu'] ?? ''))>255) {
            return ['ok' => false, 'loi' => 'Nhập tên tối đa 150 ký tự, mức giảm lớn hơn 0 và tối đa 100%, ghi chú tối đa 255 ký tự.'];
        }
        if (!$this->ngayHopLe($data['ngay_bd'] ?? null) || !$this->ngayHopLe($data['ngay_kt'] ?? null)
            || $data['ngay_bd'] > $data['ngay_kt']) {
            return ['ok' => false, 'loi' => 'Thoi han khuyen mai khong hop le'];
        }
        $bang = $loai === 'sanpham' ? 'sanpham' : 'danhmuc';
        $id = $data[$loai === 'sanpham' ? 'id_sanpham' : 'id_danhmuc'] ?? null;
        if (!$this->soNguyenHopLe($id, 1)) {
            return ['ok' => false, 'loi' => 'Doi tuong ap dung khuyen mai khong ton tai'];
        }
        $fields=[
            'ten_km' => trim((string) ($data['ten_km'] ?? '')),
            'loai' => $loai,
            'id_sanpham' => $loai === 'sanpham' ? (int) ($data['id_sanpham'] ?? 0) : null,
            'id_danhmuc' => $loai === 'danhmuc' ? (int) ($data['id_danhmuc'] ?? 0) : null,
            'phan_tram' => round((float) ($data['phan_tram'] ?? 0),2),
            'ngay_bd' => $data['ngay_bd'],
            'ngay_kt' => $data['ngay_kt'],
            'ghi_chu' => $data['ghi_chu'] ?? null,
        ];
        try {
            $this->begin();
            if ($editId && !$this->selectOne('SELECT id FROM khuyenmai WHERE id=? FOR UPDATE',[$editId])) throw new DomainException('Không tìm thấy chương trình.');
            if (!$this->selectOne("SELECT id FROM `$bang` WHERE id=? AND ngung_ban=0 FOR UPDATE",[$id])) throw new DomainException('Chọn sản phẩm hoặc danh mục đang bán.');
            $target=$loai==='sanpham'?'id_sanpham':'id_danhmuc';
            if ($this->selectOne("SELECT id FROM khuyenmai WHERE loai=? AND `$target`=? AND phan_tram=? AND ngay_bd=? AND ngay_kt=? AND id<>? LIMIT 1 FOR UPDATE",[$loai,$id,$fields['phan_tram'],$fields['ngay_bd'],$fields['ngay_kt'],$editId]))
                throw new DomainException('Đã có chương trình cùng đối tượng, mức giảm và thời hạn. Hãy sửa chương trình hiện có.');
            if ($editId) $this->update('khuyenmai',$fields,'id=?',[$editId]);
            else $editId=$this->insert('khuyenmai',$fields);
            $this->commit();return ['ok'=>true,'id'=>$editId];
        } catch(Throwable $error) {
            $this->rollback();return ['ok'=>false,'loi'=>$error instanceof DomainException?$error->getMessage():'Không lưu được khuyến mãi.'];
        }
    }

    public function giaSauKM(array $sp): array
    {
        $giaGoc = (float) $sp['gia'];
        $km = $this->selectOne(
            'SELECT * FROM khuyenmai
             WHERE CURDATE() BETWEEN ngay_bd AND ngay_kt
               AND (
                    (loai = \'sanpham\' AND id_sanpham = ?)
                 OR (loai = \'danhmuc\' AND id_danhmuc = ?)
               )
             ORDER BY phan_tram DESC
             LIMIT 1',
            [(int) $sp['id'], (int) $sp['id_danhmuc']]
        );
        if ($km === null) {
            return ['gia_goc' => $giaGoc, 'gia_km' => $giaGoc, 'khuyenmai' => null];
        }
        $giaKm = max(0, round($giaGoc * (1 - ((float) $km['phan_tram'] / 100)), 2));
        return ['gia_goc' => $giaGoc, 'gia_km' => $giaKm, 'khuyenmai' => $km];
    }
}
