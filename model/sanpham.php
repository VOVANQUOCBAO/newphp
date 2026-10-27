<?php

require_once __DIR__ . '/xl_database.php';
require_once __DIR__ . '/nhapkho.php';

class sanpham extends xl_database
{
    public function chiTietAdmin(int $id): ?array
    {
        $product = $this->selectOne('SELECT sp.*,dm.ten_danhmuc FROM sanpham sp JOIN danhmuc dm ON dm.id=sp.id_danhmuc WHERE sp.id=?', [$id]);
        if ($product === null) return null;
        $this->boSungThongTinReal($product);
        $product['lichsu_kho'] = $this->select('SELECT thay_doi,ton_sau,ly_do,id_donhang,ngay_tao FROM lichsu_kho WHERE id_sanpham=? ORDER BY id DESC LIMIT 50', [$id]);
        require_once dirname(__DIR__).'/support/scripts/migrate_store_tools.php';
        $product['lichsu_nhap'] = migration_store_tools::ready($this->pdo) ? $this->select('SELECT so_luong,ngay_nhap,nguoi_nhap,ghi_chu FROM lichsu_nhap WHERE id_sanpham=? ORDER BY id DESC LIMIT 50', [$id]) : [];
        return $product;
    }

    public function layDSAdmin(bool $ngungBan = false): array
    {
        return $this->select(
            'SELECT sp.*, dm.ten_danhmuc,
                    COALESCE(ban.da_ban, 0) AS da_ban
             FROM sanpham sp
             JOIN danhmuc dm ON dm.id = sp.id_danhmuc
             LEFT JOIN (
                 SELECT c.id_sanpham, SUM(c.so_luong) AS da_ban
                 FROM chitietdonhang c JOIN donhang d ON d.id=c.id_donhang
                 WHERE d.trang_thai=\'Thành công\'
                 GROUP BY c.id_sanpham
             ) ban ON ban.id_sanpham = sp.id
             WHERE sp.ngung_ban = ?
             ORDER BY sp.id ASC',
            [(int) $ngungBan]
        );
    }

    public function layDS(string $sapXep = 'moi'): array
    {
        $order = $sapXep === 'cu' ? 'ngay_tao ASC, id ASC' : 'ngay_tao DESC, id DESC';
        return $this->select("SELECT * FROM sanpham WHERE ngung_ban=0 ORDER BY {$order}");
    }

    public function layDSMoi(int $limit = 12): array
    {
        $limit = max(1, min(100, $limit));
        return $this->select("SELECT * FROM sanpham WHERE ngung_ban=0 ORDER BY ngay_tao DESC, id DESC LIMIT {$limit}");
    }

    public function timKiem(?string $tuKhoa = null, ?int $idDm = null, string $sapXep = 'moi', ?int $limit = null, int $offset = 0): array
    {
        $order = $sapXep === 'cu' ? 'ngay_tao ASC, id ASC' : 'ngay_tao DESC, id DESC';
        $sql = 'SELECT * FROM sanpham WHERE ngung_ban=0';
        $params = [];
        if ($tuKhoa !== null && trim($tuKhoa) !== '') {
            $sql .= ' AND (ten_sp LIKE ? OR ma_sp LIKE ? OR mo_ta LIKE ?)';
            $kw = '%' . trim($tuKhoa) . '%';
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
        }
        if ($idDm !== null && $idDm > 0) {
            $sql .= ' AND id_danhmuc = ?';
            $params[] = $idDm;
        }
        $sql .= " ORDER BY {$order}";
        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, min(100, $limit)) . ' OFFSET ' . max(0, $offset);
        }
        return $this->select($sql, $params);
    }

    public function demTimKiem(?string $tuKhoa = null, ?int $idDm = null): int
    {
        $sql = 'SELECT COUNT(*) AS tong FROM sanpham WHERE ngung_ban=0';
        $params = [];
        if ($tuKhoa !== null && trim($tuKhoa) !== '') {
            $sql .= ' AND (ten_sp LIKE ? OR ma_sp LIKE ? OR mo_ta LIKE ?)';
            $kw = '%' . trim($tuKhoa) . '%';
            $params = [$kw, $kw, $kw];
        }
        if ($idDm !== null && $idDm > 0) {
            $sql .= ' AND id_danhmuc = ?';
            $params[] = $idDm;
        }
        return (int) $this->selectOne($sql, $params)['tong'];
    }

    public function layDSKhuyenMai(int $limit = 12): array
    {
        $limit = max(1, min(100, $limit));
        return $this->select("SELECT sp.* FROM sanpham sp WHERE sp.ngung_ban=0 AND EXISTS (
            SELECT 1 FROM khuyenmai km WHERE CURDATE() BETWEEN km.ngay_bd AND km.ngay_kt
            AND ((km.loai = 'sanpham' AND km.id_sanpham = sp.id)
              OR (km.loai = 'danhmuc' AND km.id_danhmuc = sp.id_danhmuc))
            ) ORDER BY sp.ngay_tao DESC, sp.id DESC LIMIT {$limit}");
    }

    public function boSungThongTinReal(array &$sp): void
    {
        $sp = $this->boSungDanhSach([$sp])[0] ?? $sp;
    }

    public function boSungDanhSach(array $products): array
    {
        if (!$products) return [];
        $ids = array_values(array_unique(array_map('intval',array_column($products,'id'))));
        $holders = implode(',',array_fill(0,count($ids),'?'));
        $sales = $this->select("SELECT ct.id_sanpham, SUM(ct.so_luong) da_ban FROM chitietdonhang ct
            JOIN donhang dh ON dh.id=ct.id_donhang WHERE dh.trang_thai='Thành công' AND ct.id_sanpham IN ($holders) GROUP BY ct.id_sanpham",$ids);
        $sold = array_column($sales,'da_ban','id_sanpham');
        $reviews = $this->select("SELECT dg.id_sanpham, AVG(dg.so_sao) sao_tb, COUNT(*) tong_dg FROM danhgia dg
            WHERE dg.trang_thai=1 AND dg.id_sanpham IN ($holders) AND EXISTS (
                SELECT 1 FROM donhang d JOIN chitietdonhang c ON c.id_donhang=d.id
                WHERE d.id_khachhang=dg.id_khachhang AND c.id_sanpham=dg.id_sanpham AND d.trang_thai IN ('Đã giao','Thành công')) GROUP BY dg.id_sanpham",$ids);
        $ratings = array_column($reviews,null,'id_sanpham');
        $promotions = $this->select('SELECT * FROM khuyenmai WHERE CURDATE() BETWEEN ngay_bd AND ngay_kt ORDER BY phan_tram DESC,id DESC');
        foreach ($products as &$sp) {
            $id = (int)$sp['id'];
            $sp['so_luong_ton'] = (int)$sp['so_luong_ton'];
            $sp['het_hang'] = $sp['so_luong_ton']<=0;
            $sp['da_ban'] = (int)($sold[$id] ?? 0);
            $sp['sao_tb'] = round((float)($ratings[$id]['sao_tb'] ?? 0),1);
            $sp['so_danh_gia'] = (int)($ratings[$id]['tong_dg'] ?? 0);
            $sp['gia_goc'] = (float)$sp['gia'];
            $sp['gia_km'] = (float)$sp['gia'];
            foreach ($promotions as $promo) {
                if (($promo['loai']==='sanpham' && (int)$promo['id_sanpham']===$id)
                    || ($promo['loai']==='danhmuc' && (int)$promo['id_danhmuc']===(int)$sp['id_danhmuc'])) {
                    $sp['gia_km'] = max(0,round((float)$sp['gia']*(1-(float)$promo['phan_tram']/100),2));
                    break;
                }
            }
        }
        unset($sp);
        return $products;
    }

    public function layChiTiet(int $id): ?array
    {
        $sp = $this->selectOne('SELECT * FROM sanpham WHERE id = ? AND ngung_ban=0', [$id]);
        if ($sp !== null) {
            $this->boSungThongTinReal($sp);
        }
        return $sp;
    }

    public function layTheoMa(string $maSp): ?array
    {
        return $this->selectOne('SELECT * FROM sanpham WHERE ma_sp = ?', [$maSp]);
    }

    public function them(array $data): array
    {
        $loi = $this->kiemTraDuLieu($data);
        if ($loi !== null) {
            return ['ok' => false, 'loi' => $loi];
        }
        $ma = trim((string) ($data['ma_sp'] ?? ''));
        if ($ma === '') {
            return ['ok' => false, 'loi' => 'Bat buoc nhap ma san pham'];
        }
        if ($this->layTheoMa($ma) !== null) {
            return ['ok' => false, 'loi' => 'Ma san pham da ton tai'];
        }
        $goc = (int) ($data['so_luong_goc'] ?? 0);
        $ton = (int) ($data['so_luong_ton'] ?? $goc);
        $check = $this->kiemTraSoLuong($goc, $ton);
        if ($check !== null) {
            return ['ok' => false, 'loi' => $check];
        }
        try {
            $this->begin();
            $id = $this->insert('sanpham', [
                'ma_sp' => $ma,
                'ten_sp' => trim((string) ($data['ten_sp'] ?? '')),
                'id_danhmuc' => (int) ($data['id_danhmuc'] ?? 0),
                'gia' => (float) ($data['gia'] ?? 0),
                'so_luong_goc' => $goc,
                'so_luong_ton' => $ton,
                'hinh' => !empty($data['hinh']) ? $data['hinh'] : null,
                'mo_ta' => !empty($data['mo_ta']) ? $data['mo_ta'] : null,
                'han_su_dung' => !empty($data['han_su_dung']) ? $data['han_su_dung'] : null,
                'thuong_hieu' => trim($data['thuong_hieu'] ?? ''),
                'thong_so' => trim($data['thong_so'] ?? ''),
                'bao_hanh' => trim($data['bao_hanh'] ?? ''),
                'anh_phu' => trim($data['anh_phu'] ?? ''),
                'ngay_nhap' => $data['ngay_nhap'] ?? date('Y-m-d'),
            ]);
            $this->ghiKho($id, $ton, 'Tạo sản phẩm');
            (new nhapkho())->ghi($id,$goc,$data['ngay_nhap'] ?? date('Y-m-d'),'Nhập ban đầu');
            $this->commit();
        } catch (PDOException $e) {
            $this->rollback();
            error_log('Them san pham: ' . $e->getMessage());
            return ['ok' => false, 'loi' => $e->getCode() === '23000'
                ? 'Mã sản phẩm đã tồn tại' : 'Không thể lưu sản phẩm lúc này'];
        }
        return ['ok' => true, 'id' => $id];
    }

    public function sua(int $id, array $data): array
    {
        try {
            $this->begin();
            $sp = $this->selectOne('SELECT * FROM sanpham WHERE id = ? FOR UPDATE', [$id]);
            if ($sp === null) {
                $this->rollback();
                return ['ok' => false, 'loi' => 'Không tìm thấy sản phẩm'];
            }
            if (isset($data['ton_cu']) && (int)$data['ton_cu'] !== (int)$sp['so_luong_ton']) {
                $this->rollback();
                return ['ok' => false, 'loi' => 'Tồn kho đã thay đổi do giao dịch khác. Hãy tải lại trang trước khi sửa.'];
            }
            $data = array_replace($sp, $data);
            if ((int) $data['so_luong_goc'] !== (int) $sp['so_luong_goc']) {
                $this->rollback();
                return ['ok' => false, 'loi' => 'Không được thay đổi số lượng tạo ban đầu'];
            }
            $loi = $this->kiemTraDuLieu($data);
            if ($loi !== null) {
                $this->rollback();
                return ['ok' => false, 'loi' => $loi];
            }
            $goc = (int) $data['so_luong_goc'];
            $ton = (int) $data['so_luong_ton'];
            $check = $this->kiemTraSoLuong($goc, $ton);
            if ($check !== null) {
                $this->rollback();
                return ['ok' => false, 'loi' => $check];
            }
            if (!$this->soNguyenHopLe($data['nhap_them'] ?? 0)) { $this->rollback(); return ['ok' => false, 'loi' => 'Số lượng nhập thêm không hợp lệ']; }
            if ($ton > (int)$sp['so_luong_ton']) {
                $this->rollback(); return ['ok'=>false,'loi'=>'Dùng ô Nhập thêm để tăng kho; tồn khả dụng chỉ dùng để điều chỉnh giảm khi kiểm kê.'];
            }
            $nhap = (int)($data['nhap_them'] ?? 0);
            $ngayNhap = $data['ngay_nhap_dot'] ?? date('Y-m-d');
            if ($nhap>0 && (!$this->ngayHopLe($ngayNhap) || $ngayNhap>date('Y-m-d'))) {
                $this->rollback(); return ['ok'=>false,'loi'=>'Ngày nhập hàng không hợp lệ hoặc ở tương lai.'];
            }
            $goc += $nhap;
            $ton += $nhap;
            if ($goc > 2147483647 || $ton > 2147483647) { $this->rollback(); return ['ok' => false, 'loi' => 'Số lượng vượt giới hạn lưu trữ']; }
            $fields = [
                'ten_sp' => trim((string) $data['ten_sp']),
                'id_danhmuc' => (int) $data['id_danhmuc'],
                'gia' => (float) $data['gia'],
                'so_luong_goc' => $goc,
                'so_luong_ton' => $ton,
                'hinh' => $data['hinh'] ?? null,
                'mo_ta' => $data['mo_ta'] ?? null,
                'han_su_dung' => $data['han_su_dung'] ?: null,
                'thuong_hieu' => trim($data['thuong_hieu'] ?? ''),
                'thong_so' => trim($data['thong_so'] ?? ''),
                'bao_hanh' => trim($data['bao_hanh'] ?? ''),
                'anh_phu' => trim($data['anh_phu'] ?? ''),
            ];
            $ma = trim((string) $data['ma_sp']);
            if ($ma !== $sp['ma_sp']) {
                $existing = $this->layTheoMa($ma);
                if ($existing !== null && (int) $existing['id'] !== $id) {
                    $this->rollback();
                    return ['ok' => false, 'loi' => 'Mã sản phẩm đã tồn tại'];
                }
                $fields['ma_sp'] = $ma;
            }
            $this->update('sanpham', $fields, 'id = ?', [$id]);
            if ($nhap>0) (new nhapkho())->ghi($id,$nhap,$ngayNhap,'Nhập thêm hàng');
            if ($ton !== (int)$sp['so_luong_ton']) $this->ghiKho($id, $ton - (int)$sp['so_luong_ton'], 'Kiểm kê bởi nhân viên #' . ($_SESSION['admin']['id'] ?? 0));
            $this->commit();
        } catch (PDOException $e) {
            $this->rollback();
            error_log('Sua san pham: ' . $e->getMessage());
            return ['ok' => false, 'loi' => $e->getCode() === '23000'
                ? 'Mã sản phẩm đã tồn tại' : 'Không thể cập nhật sản phẩm lúc này'];
        } catch (Throwable $e) {
            $this->rollback();
            error_log('Sua san pham: ' . $e->getMessage());
            return ['ok' => false, 'loi' => 'Không thể cập nhật sản phẩm lúc này'];
        }
        return ['ok' => true];
    }

    public function xoa(int $id): array
    {
        try {
            $this->begin();
            if ($this->selectOne('SELECT id FROM sanpham WHERE id = ? FOR UPDATE', [$id]) === null) {
                $this->rollback();
                return ['ok' => false, 'loi' => 'Khong tim thay san pham'];
            }
            $daBan = $this->selectOne(
                'SELECT COUNT(*) AS c FROM chitietdonhang WHERE id_sanpham = ?',
                [$id]
            );
            if ((int) ($daBan['c'] ?? 0) > 0) {
                $this->update('sanpham', ['ngung_ban' => 1], 'id = ?', [$id]);
                $this->commit();
                return ['ok' => true, 'ngung_ban' => true,
                    'thong_bao' => 'Đã ngừng bán sản phẩm. Lịch sử đơn hàng, thanh toán và tồn kho được giữ nguyên.'];
            }
            $this->delete('danhgia', 'id_sanpham = ?', [$id]);
            $this->delete('khuyenmai', 'id_sanpham = ?', [$id]);
            $this->delete('lichsu_kho', 'id_sanpham = ?', [$id]);
            $this->delete('lichsu_nhap', 'id_sanpham = ?', [$id]);
            $this->delete('yeuthich', 'id_sanpham = ?', [$id]);
            $this->delete('sanpham', 'id = ?', [$id]);
            $this->commit();
            return ['ok' => true, 'ngung_ban' => false, 'thong_bao' => 'Đã xóa sản phẩm chưa có giao dịch.'];
        } catch (Throwable $e) {
            $this->rollback();
            error_log('Xoa san pham: ' . $e->getMessage());
            return ['ok' => false, 'loi' => 'Không thể xóa sản phẩm vì dữ liệu đang được sử dụng'];
        }
    }

    public function moBanLai(int $id): array
    {
        try {
            $this->begin();
            $sp = $this->selectOne('SELECT id, id_danhmuc FROM sanpham WHERE id = ? FOR UPDATE', [$id]);
            if ($sp === null) {
                $this->rollback();
                return ['ok' => false, 'loi' => 'Không tìm thấy sản phẩm'];
            }
            if ($this->selectOne('SELECT id FROM danhmuc WHERE id = ? AND ngung_ban = 0', [$sp['id_danhmuc']]) === null) {
                $this->rollback();
                return ['ok' => false, 'loi' => 'Danh mục đã ngừng bán; chưa thể mở bán lại sản phẩm.'];
            }
            $this->update('sanpham', ['ngung_ban' => 0], 'id = ?', [$id]);
            $this->commit();
            return ['ok' => true];
        } catch (Throwable $e) {
            $this->rollback();
            error_log('Mo ban lai san pham: ' . $e->getMessage());
            return ['ok' => false, 'loi' => 'Không thể mở bán lại sản phẩm lúc này.'];
        }
    }

    public function truTon(int $id, int $sl): array
    {
        if ($sl <= 0) {
            return ['ok' => false, 'loi' => 'So luong khong hop le'];
        }
        $n = $this->execute(
            'UPDATE sanpham SET so_luong_ton = so_luong_ton - ? WHERE id = ? AND so_luong_ton >= ?',
            [$sl, $id, $sl]
        );
        if ($n === 0) {
            return ['ok' => false, 'loi' => 'Khong du so luong ton kho'];
        }
        return ['ok' => true];
    }

    public function congTon(int $id, int $sl): array
    {
        if ($sl <= 0) {
            return ['ok' => false, 'loi' => 'So luong khong hop le'];
        }
        $this->execute('UPDATE sanpham SET so_luong_ton = so_luong_ton + ? WHERE id = ?', [$sl, $id]);
        // Hàng ngừng bán vẫn có thể được hoàn kho khi hủy/trả đơn cũ.
        $sp = $this->selectOne('SELECT so_luong_ton FROM sanpham WHERE id = ?', [$id]);
        return $sp === null ? ['ok' => false, 'loi' => 'Khong tim thay san pham']
            : ['ok' => true, 'so_luong_ton' => (int) $sp['so_luong_ton']];
    }

    public function canhBaoSapHet(int $nguong = 5): array
    {
        return $this->select(
            'SELECT * FROM sanpham WHERE ngung_ban=0 AND so_luong_ton <= ?',
            [$nguong]
        );
    }

    public function hangE(int $soNgay = 30, int $nguongBan = 2): array
    {
        return $this->select(
            'SELECT sp.*, COALESCE(ban.da_ban, 0) AS da_ban
             FROM sanpham sp
             LEFT JOIN (
                SELECT ct.id_sanpham, SUM(ct.so_luong) AS da_ban
                FROM chitietdonhang ct
                JOIN donhang dh ON dh.id = ct.id_donhang
                WHERE dh.trang_thai = ?
                GROUP BY ct.id_sanpham
             ) ban ON ban.id_sanpham = sp.id
             WHERE sp.ngung_ban=0 AND sp.ngay_tao <= DATE_SUB(NOW(), INTERVAL ? DAY)
               AND COALESCE(ban.da_ban, 0) <= ?',
            ['Thành công', $soNgay, $nguongBan]
        );
    }

    public function canHan(int $soNgay = 14): array
    {
        return $this->select(
            'SELECT * FROM sanpham
             WHERE ngung_ban=0 AND han_su_dung IS NOT NULL
               AND han_su_dung <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
               AND han_su_dung >= CURDATE()',
            [$soNgay]
        );
    }

    public function kiemTraSoLuong(int $goc, int $ton): ?string
    {
        if ($goc < 0 || $ton < 0) {
            return 'So luong khong duoc am';
        }
        if ($ton > $goc) {
            return 'So luong ton khong duoc vuot so luong goc';
        }
        return null;
    }

    public function ghiKho(int $id, int $delta, string $reason, ?int $order = null): void
    {
        $stock = $this->selectOne('SELECT so_luong_ton FROM sanpham WHERE id=?', [$id]);
        $this->insert('lichsu_kho', ['id_sanpham' => $id, 'id_donhang' => $order, 'thay_doi' => $delta,
            'ton_sau' => $stock['so_luong_ton'], 'ly_do' => $reason]);
    }

    public function lienQuan(int $id, int $category): array
    {
        return $this->select('SELECT * FROM sanpham WHERE ngung_ban=0 AND id<>? AND id_danhmuc=? ORDER BY (so_luong_ton>0) DESC, ngay_tao DESC, id DESC LIMIT 4', [$id, $category]);
    }

    private function kiemTraDuLieu(array $data): ?string
    {
        $ma = trim((string) ($data['ma_sp'] ?? ''));
        $ten = trim((string) ($data['ten_sp'] ?? ''));
        if ($ma === '' || mb_strlen($ma) > 50) {
            return 'Mã sản phẩm bắt buộc và tối đa 50 ký tự';
        }
        if ($ten === '' || mb_strlen($ten) > 255) {
            return 'Tên sản phẩm bắt buộc và tối đa 255 ký tự';
        }
        if (!$this->soTienHopLe($data['gia'] ?? null)) {
            return 'Giá sản phẩm không hợp lệ';
        }
        if (!$this->soNguyenHopLe($data['so_luong_goc'] ?? null)
            || !$this->soNguyenHopLe($data['so_luong_ton'] ?? $data['so_luong_goc'])) {
            return 'So luong phai la so nguyen khong am';
        }
        if (!$this->soNguyenHopLe($data['id_danhmuc'] ?? null, 1)
            || $this->selectOne('SELECT id FROM danhmuc WHERE id = ?', [$data['id_danhmuc']]) === null) {
            return 'Danh muc khong ton tai';
        }
        if (!empty($data['han_su_dung']) && !$this->ngayHopLe($data['han_su_dung'])) {
            return 'Han su dung khong hop le';
        }
        if (isset($data['ngay_nhap']) && (!$this->ngayHopLe($data['ngay_nhap']) || $data['ngay_nhap']>date('Y-m-d'))) return 'Ngày nhập hàng không hợp lệ hoặc ở tương lai.';
        foreach (['thuong_hieu' => 150, 'bao_hanh' => 255] as $field => $max) {
            if (mb_strlen((string)($data[$field] ?? '')) > $max) return 'Thông tin sản phẩm vượt độ dài cho phép';
        }
        foreach (['mo_ta', 'thong_so', 'anh_phu'] as $field) {
            if (strlen((string)($data[$field] ?? '')) > 60000) return 'Nội dung sản phẩm quá dài';
        }
        $images = array_merge([(string)($data['hinh'] ?? '')], preg_split('/\R/', (string)($data['anh_phu'] ?? '')));
        foreach ($images as $image) {
            if ($image !== '' && (!preg_match('~^(?:(?:uploads|catalog)/)?[a-zA-Z0-9_-]+\.(?:jpg|jpeg|png|webp|gif|svg)$~D', $image)
                || !is_file(dirname(__DIR__) . '/view/image/' . $image))) return 'Ảnh không tồn tại hoặc tên ảnh không hợp lệ';
        }
        return null;
    }
}
