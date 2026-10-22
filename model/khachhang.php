<?php

require_once __DIR__ . '/xl_database.php';

class khachhang extends xl_database
{
    public const HANG_SILVER = 'Silver';
    public const HANG_GOLD = 'Gold';
    public const HANG_DIAMOND = 'Diamond';
    public const NGUONG_GOLD = 500;
    public const NGUONG_DIAMOND = 2000;

    public function layDS(): array
    {
        return $this->select(
            'SELECT kh.*,
                    COALESCE(dh.c_don, 0) AS tong_don,
                    COALESCE(dh.tong_tien, 0) AS tong_tien
             FROM khachhang kh
             LEFT JOIN (
                SELECT id_khachhang, COUNT(*) AS c_don, SUM(tong_tien) AS tong_tien
                FROM donhang
                GROUP BY id_khachhang
             ) dh ON dh.id_khachhang = kh.id
             ORDER BY kh.id DESC'
        );
    }

    public function layTheoId(int $id): ?array
    {
        return $this->selectOne('SELECT * FROM khachhang WHERE id = ?', [$id]);
    }

    public function layTheoEmail(string $email): ?array
    {
        return $this->selectOne('SELECT * FROM khachhang WHERE email = ?', [trim($email)]);
    }

    public function dangNhapGoogle(string $sub, string $email, string $hoTen): array
    {
        $sub = trim($sub);
        $email = trim($email);
        $hoTen = trim($hoTen);
        if ($sub === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'loi' => 'Thông tin xác thực Google không hợp lệ'];
        }

        $created = false;
        try {
            $kh = $this->selectOne('SELECT * FROM khachhang WHERE google_sub = ?', [$sub]);
            if ($kh === null) {
                $kh = $this->layTheoEmail($email);
                if ($kh !== null) {
                    if ((int) $kh['trang_thai'] !== 1) {
                        return ['ok' => false, 'loi' => 'Tài khoản của bạn đã bị tạm khóa'];
                    }
                    if (!empty($kh['google_sub']) && !hash_equals((string) $kh['google_sub'], $sub)) {
                        return ['ok' => false, 'loi' => 'Email này đã được liên kết với một tài khoản Google khác'];
                    }
                    $this->update('khachhang', ['google_sub' => $sub], 'id = ?', [(int) $kh['id']]);
                    $kh['google_sub'] = $sub;
                } else {
                    $id = $this->insert('khachhang', [
                        'ho_ten' => $hoTen !== '' ? $hoTen : explode('@', $email)[0],
                        'email' => $email,
                        'google_sub' => $sub,
                        'mat_khau' => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
                        'hang' => self::HANG_SILVER,
                        'diem' => 0,
                        'trang_thai' => 1,
                    ]);
                    $kh = $this->layTheoId($id);
                    $created = true;
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            $kh = $this->selectOne('SELECT * FROM khachhang WHERE google_sub = ?', [$sub]);
            if ($kh === null) {
                $kh = $this->layTheoEmail($email);
            }
        }

        if ($kh === null || (int) $kh['trang_thai'] !== 1) {
            return ['ok' => false, 'loi' => 'Tài khoản Google chưa được liên kết hoặc đang bị khóa'];
        }
        if (empty($kh['google_sub']) || !hash_equals((string) $kh['google_sub'], $sub)) {
            return ['ok' => false, 'loi' => 'Không thể liên kết tài khoản Google này'];
        }
        unset($kh['mat_khau']);
        return ['ok' => true, 'khachhang' => $kh, 'created' => $created];
    }

    public function themKhachHang(array $data): array
    {
        $email = trim((string) ($data['email'] ?? ''));
        $hoTen = trim((string) ($data['ho_ten'] ?? ''));
        $matKhau = (string) ($data['mat_khau'] ?? '');
        if ($email === '' || $hoTen === '' || $matKhau === '') {
            return ['ok' => false, 'loi' => 'Thiếu họ tên, email hoặc mật khẩu'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'loi' => 'Địa chỉ email không hợp lệ'];
        }
        $ngaySinh = $data['ngay_sinh'] ?? null;
        if ($ngaySinh === '') {
            $ngaySinh = null;
        }
        if ($ngaySinh !== null && (!$this->ngayHopLe($ngaySinh) || $ngaySinh > date('Y-m-d'))) {
            return ['ok' => false, 'loi' => 'Ngày sinh không hợp lệ'];
        }
        if ($this->layTheoEmail($email) !== null) {
            return ['ok' => false, 'loi' => 'Email này đã được đăng ký tài khoản'];
        }
        $insertData = [
            'ho_ten' => $hoTen,
            'email' => $email,
            'mat_khau' => password_hash($matKhau, PASSWORD_DEFAULT),
            'ngay_sinh' => $ngaySinh,
            'hang' => self::HANG_SILVER,
            'diem' => 0,
            'trang_thai' => 1,
        ];
        $username = trim((string)($data['ten_dang_nhap'] ?? $data['username'] ?? ''));
        if ($username !== '') {
            $insertData['ten_dang_nhap'] = $username;
        }
        $sdt = trim((string)($data['sdt'] ?? $data['so_dien_thoai'] ?? ''));
        if ($sdt !== '') {
            $insertData['sdt'] = $sdt;
        }
        $id = $this->insert('khachhang', $insertData);
        return ['ok' => true, 'id' => $id];
    }

    public function kiemTraDangNhap(string $identifier, string $matKhau): array
    {
        $idClean = trim($identifier);
        $kh = $this->layTheoEmail($idClean);
        if ($kh === null) {
            try {
                $kh = $this->selectOne('SELECT * FROM khachhang WHERE ten_dang_nhap = ?', [$idClean]);
            } catch (Throwable $e) {
                // column might not exist in some environments
            }
        }
        if ($kh === null && !str_contains($idClean, '@')) {
            $kh = $this->selectOne('SELECT * FROM khachhang WHERE email LIKE ?', [$idClean . '@%']);
        }
        if ($kh === null) {
            $kh = $this->selectOne('SELECT * FROM khachhang WHERE ho_ten = ?', [$idClean]);
        }
        if ($kh === null) {
            $kh = $this->selectOne('SELECT * FROM khachhang WHERE sdt = ?', [$idClean]);
        }
        if ($kh === null) {
            return ['ok' => false, 'loi' => 'Email hoặc mật khẩu không chính xác'];
        }
        if ((int) $kh['trang_thai'] !== 1) {
            return ['ok' => false, 'loi' => 'Tài khoản của bạn đã bị tạm khóa'];
        }
        $matKhauDb = (string) ($kh['mat_khau'] ?? '');
        if (!$this->xacThucVaNangCapMatKhau($matKhau, $matKhauDb, 'khachhang', (int) $kh['id'])) {
            return ['ok' => false, 'loi' => 'Email hoặc mật khẩu không chính xác'];
        }
        unset($kh['mat_khau']);
        return ['ok' => true, 'khachhang' => $kh];
    }

    public function doiMatKhau(int $id, string $matKhauMoi): array
    {
        $matKhauMoi = trim($matKhauMoi);
        if ($matKhauMoi === '') {
            return ['ok' => false, 'loi' => 'Mật khẩu mới không được để trống'];
        }
        $hash = password_hash($matKhauMoi, PASSWORD_DEFAULT);
        $this->update('khachhang', ['mat_khau' => $hash], 'id = ?', [$id]);
        return ['ok' => true];
    }

    public function khoa(int $id): array
    {
        if (!$this->layTheoId($id)) return ['ok'=>false,'loi'=>'Không tìm thấy khách hàng'];
        $this->update('khachhang', ['trang_thai' => 0], 'id = ?', [$id]);
        return ['ok' => true];
    }

    public function moKhoa(int $id): array
    {
        if (!$this->layTheoId($id)) return ['ok'=>false,'loi'=>'Không tìm thấy khách hàng'];
        $this->update('khachhang', ['trang_thai' => 1], 'id = ?', [$id]);
        return ['ok' => true];
    }

    public function congDiemVaXetHang(int $id, float $tongTien): array
    {
        if (!$this->soTienHopLe($tongTien)) {
            return ['ok' => false, 'loi' => 'Tong tien tinh diem khong hop le'];
        }
        // Mot lenh UPDATE nguyen tu, khong lam mat diem khi hai don hoan tat cung luc.
        $cong = (int) floor($tongTien / 1000);
        $this->execute('UPDATE khachhang SET hang = CASE WHEN diem + ? >= ? THEN ?
            WHEN diem + ? >= ? THEN ? ELSE ? END, diem = diem + ? WHERE id = ?',
            [$cong, self::NGUONG_DIAMOND, self::HANG_DIAMOND, $cong, self::NGUONG_GOLD,
                self::HANG_GOLD, self::HANG_SILVER, $cong, $id]);
        $kh = $this->layTheoId($id);
        return $kh === null ? ['ok' => false, 'loi' => 'Khong tim thay khach hang']
            : ['ok' => true, 'diem' => (int) $kh['diem'], 'hang' => $kh['hang']];
    }

    public function tangViPham(int $id, string $loai): void
    {
        $map = [
            'huy' => 'so_lan_huy',
            'bom' => 'so_lan_bom',
            'tra' => 'so_lan_tra',
        ];
        if (!isset($map[$loai])) {
            throw new InvalidArgumentException('Loai vi pham khong hop le');
        }
        $cot = $map[$loai];
        $this->execute("UPDATE khachhang SET `{$cot}` = `{$cot}` + 1 WHERE id = ?", [$id]);
    }

    public function dsBomHuyTra(): array
    {
        return $this->select(
            'SELECT * FROM khachhang
             WHERE so_lan_huy > 0 OR so_lan_bom > 0 OR so_lan_tra > 0
             ORDER BY so_lan_bom DESC, so_lan_tra DESC, so_lan_huy DESC'
        );
    }

    public function locTheoNgaySinh(?int $ngay = null, ?int $thang = null, ?int $nam = null): array
    {
        $sql = 'SELECT * FROM khachhang WHERE ngay_sinh IS NOT NULL';
        $params = [];
        if ($ngay !== null) {
            $sql .= ' AND DAY(ngay_sinh) = ?';
            $params[] = $ngay;
        }
        if ($thang !== null) {
            $sql .= ' AND MONTH(ngay_sinh) = ?';
            $params[] = $thang;
        }
        if ($nam !== null) {
            $sql .= ' AND YEAR(ngay_sinh) = ?';
            $params[] = $nam;
        }
        return $this->select($sql, $params);
    }

    public function quyenLoiHang(string $hang): array
    {
        return match ($hang) {
            self::HANG_DIAMOND => ['mien_ship' => true, 'mo_ta' => 'Kim cuong: mien ship, nhieu voucher'],
            self::HANG_GOLD => ['mien_ship' => true, 'mo_ta' => 'Vang: mien ship, uu dai voucher'],
            default => ['mien_ship' => false, 'mo_ta' => 'Bac: hang mac dinh'],
        };
    }
}
