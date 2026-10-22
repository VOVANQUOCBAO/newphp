<?php

require_once __DIR__ . '/xl_database.php';

class danhmuc extends xl_database
{
    public function layDS(): array
    {
        return $this->select(
            'SELECT dm.*, COUNT(DISTINCT sp.id) AS so_san_pham
             FROM danhmuc dm
             LEFT JOIN sanpham sp ON sp.id_danhmuc = dm.id AND sp.ngung_ban=0
             WHERE dm.ngung_ban=0
             GROUP BY dm.id
             ORDER BY dm.ten_danhmuc'
        );
    }

    public function layTheoId(int $id): ?array
    {
        return $this->selectOne('SELECT * FROM danhmuc WHERE id = ?', [$id]);
    }

    public function them(string $ten, ?string $moTa = null): int
    {
        [$ten, $moTa] = $this->kiemTraDuLieu($ten, $moTa);
        if ($this->selectOne('SELECT id FROM danhmuc WHERE ten_danhmuc = ?', [$ten]) !== null) {
            throw new InvalidArgumentException('Tên danh mục đã tồn tại');
        }
        return $this->insert('danhmuc', [
            'ten_danhmuc' => $ten,
            'mo_ta' => $moTa,
        ]);
    }

    public function sua(int $id, string $ten, ?string $moTa = null): int
    {
        if ($id < 1 || $this->layTheoId($id) === null) {
            throw new InvalidArgumentException('Không tìm thấy danh mục');
        }
        [$ten, $moTa] = $this->kiemTraDuLieu($ten, $moTa);
        if ($this->selectOne(
            'SELECT id FROM danhmuc WHERE ten_danhmuc = ? AND id <> ?',
            [$ten, $id]
        ) !== null) {
            throw new InvalidArgumentException('Tên danh mục đã tồn tại');
        }
        return $this->update('danhmuc', [
            'ten_danhmuc' => $ten,
            'mo_ta' => $moTa,
        ], 'id = ?', [$id]);
    }

    public function xoa(int $id): array
    {
        if ($id < 1) {
            return ['ok' => false, 'loi' => 'Mã danh mục không hợp lệ'];
        }
        try {
            $this->begin();
            if ($this->selectOne('SELECT id FROM danhmuc WHERE id = ? FOR UPDATE', [$id]) === null) {
                $this->rollback();
                return ['ok' => false, 'loi' => 'Không tìm thấy danh mục'];
            }
            $conSp = $this->selectOne('SELECT COUNT(*) AS c FROM sanpham WHERE id_danhmuc = ?', [$id]);
            if ((int) ($conSp['c'] ?? 0) > 0) {
                $this->rollback();
                return ['ok' => false, 'loi' => 'Không thể xóa danh mục đang có sản phẩm'];
            }
            $conKm = $this->selectOne('SELECT COUNT(*) AS c FROM khuyenmai WHERE id_danhmuc = ?', [$id]);
            if ((int) ($conKm['c'] ?? 0) > 0) {
                $this->rollback();
                return ['ok' => false, 'loi' => 'Không thể xóa danh mục đang được áp dụng khuyến mãi'];
            }
            $this->delete('danhmuc', 'id = ?', [$id]);
            $this->commit();
            return ['ok' => true];
        } catch (Throwable $e) {
            $this->rollback();
            error_log('Xoa danh muc: ' . $e->getMessage());
            return ['ok' => false, 'loi' => 'Không thể xóa danh mục lúc này'];
        }
    }

    private function kiemTraDuLieu(string $ten, ?string $moTa): array
    {
        $ten = trim($ten);
        $moTa = $moTa !== null ? trim($moTa) : null;
        if ($ten === '' || mb_strlen($ten) > 150) {
            throw new InvalidArgumentException('Tên danh mục bắt buộc và không quá 150 ký tự');
        }
        if ($moTa !== null && $moTa === '') {
            $moTa = null;
        }
        return [$ten, $moTa];
    }
}
