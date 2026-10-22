<?php

require_once __DIR__ . '/xl_database.php';

class nhanvien extends xl_database
{
    public const ROLE_SUPER_ADMIN = 'SuperAdmin';
    public const ROLE_MANAGER = 'QuanLy';
    public const ROLE_EMPLOYEE = 'NhanVien';

    public static function laSuperAdmin(string $vaiTro): bool
    {
        return in_array($vaiTro, ['Admin', self::ROLE_SUPER_ADMIN], true);
    }

    public function layDS(): array
    {
        return $this->select('SELECT id, ho_ten, email, vai_tro, trang_thai FROM nhanvien ORDER BY id');
    }

    public function layTheoId(int $id): ?array
    {
        return $this->selectOne(
            'SELECT id, ho_ten, email, vai_tro, trang_thai FROM nhanvien WHERE id = ?',
            [$id]
        );
    }

    public function them(array $data): array
    {
        $email = trim((string) ($data['email'] ?? ''));
        $hoTen = trim((string) ($data['ho_ten'] ?? ''));
        $matKhau = (string) ($data['mat_khau'] ?? '');
        $vaiTro = (string) ($data['vai_tro'] ?? self::ROLE_EMPLOYEE);
        if ($email === '' || $hoTen === '' || $matKhau === '') {
            return ['ok' => false, 'loi' => 'Thieu thong tin nhan vien'];
        }
        if (!in_array($vaiTro, [self::ROLE_SUPER_ADMIN, self::ROLE_MANAGER, self::ROLE_EMPLOYEE], true)) {
            return ['ok' => false, 'loi' => 'Vai trò nhân viên không hợp lệ'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'loi' => 'Email nhan vien khong hop le'];
        }
        if (mb_strlen($hoTen) > 150 || strlen($matKhau) < 12) {
            return ['ok' => false, 'loi' => 'Họ tên tối đa 150 ký tự và mật khẩu phải có ít nhất 12 ký tự'];
        }
        $tonTai = $this->selectOne('SELECT id FROM nhanvien WHERE email = ?', [$email]);
        if ($tonTai !== null) {
            return ['ok' => false, 'loi' => 'Email nhan vien da ton tai'];
        }
        $id = $this->insert('nhanvien', [
            'ho_ten' => $hoTen,
            'email' => $email,
            'mat_khau' => password_hash($matKhau, PASSWORD_DEFAULT),
            'vai_tro' => $vaiTro,
            'trang_thai' => 1,
        ]);
        return ['ok' => true, 'id' => $id];
    }

    public function doiVaiTro(int $id, string $vaiTro, int $actorId): array
    {
        if ($id <= 0 || $actorId <= 0
            || !in_array($vaiTro, [self::ROLE_SUPER_ADMIN, self::ROLE_MANAGER, self::ROLE_EMPLOYEE], true)) {
            return ['ok' => false, 'loi' => 'Mã nhân viên hoặc vai trò không hợp lệ'];
        }
        if ($id === $actorId) {
            return ['ok' => false, 'loi' => 'Không thể tự thay đổi vai trò của chính mình'];
        }

        $nhanVien = $this->layTheoId($id);
        if ($nhanVien === null) {
            return ['ok' => false, 'loi' => 'Không tìm thấy nhân viên'];
        }

        if ((int) $nhanVien['trang_thai'] === 1
            && self::laSuperAdmin((string) $nhanVien['vai_tro'])
            && !self::laSuperAdmin($vaiTro)) {
            $activeSuperAdmins = (int) $this->selectOne(
                "SELECT COUNT(*) AS total FROM nhanvien WHERE vai_tro IN ('Admin', 'SuperAdmin') AND trang_thai = 1"
            )['total'];
            if ($activeSuperAdmins <= 1) {
                return ['ok' => false, 'loi' => 'Phải luôn duy trì ít nhất một Super Admin đang hoạt động'];
            }
        }

        $this->update('nhanvien', ['vai_tro' => $vaiTro], 'id = ?', [$id]);
        return ['ok' => true];
    }

    public function kiemTraDangNhap(string $email, string $matKhau): array
    {
        $clean = trim($email);
        $nv = null;
        if (strtolower($clean) === 'admin') {
            $nv = $this->selectOne("SELECT * FROM nhanvien WHERE email = 'admin@local.test' OR ho_ten = 'admin' OR vai_tro = 'Admin' ORDER BY id ASC LIMIT 1");
        }
        if ($nv === null) {
            $nv = $this->selectOne('SELECT * FROM nhanvien WHERE email = ?', [$clean]);
        }
        if ($nv === null) {
            $nv = $this->selectOne('SELECT * FROM nhanvien WHERE ho_ten = ?', [$clean]);
        }
        if ($nv === null) {
            return ['ok' => false, 'loi' => 'Email hoac mat khau khong dung'];
        }
        if ((int) $nv['trang_thai'] !== 1) {
            return ['ok' => false, 'loi' => 'Tai khoan nhan vien dang bi khoa'];
        }
        $matKhauDb = (string) ($nv['mat_khau'] ?? '');
        if (!$this->xacThucVaNangCapMatKhau($matKhau, $matKhauDb, 'nhanvien', (int) $nv['id'])) {
            return ['ok' => false, 'loi' => 'Email hoac mat khau khong dung'];
        }
        unset($nv['mat_khau']);
        return ['ok' => true, 'nhanvien' => $nv];
    }

    public function doiMatKhau(int $id, string $matKhauMoi): array
    {
        if (strlen($matKhauMoi) < 12) {
            return ['ok' => false, 'loi' => 'Mật khẩu mới phải có ít nhất 12 ký tự'];
        }
        $hash = password_hash($matKhauMoi, PASSWORD_DEFAULT);
        $this->update('nhanvien', ['mat_khau' => $hash], 'id = ?', [$id]);
        return ['ok' => true];
    }

    public function doiTrangThai(int $id, int $trangThai): array
    {
        if ($id <= 0 || !in_array($trangThai, [0, 1], true)) {
            return ['ok' => false, 'loi' => 'Trạng thái hoặc mã nhân viên không hợp lệ'];
        }
        $nhanVien = $this->layTheoId($id);
        if ($nhanVien === null) {
            return ['ok' => false, 'loi' => 'Không tìm thấy nhân viên'];
        }
        if (self::laSuperAdmin((string) $nhanVien['vai_tro'])) {
            return ['ok' => false, 'loi' => 'Không thể khóa tài khoản Super Admin'];
        }
        $this->update('nhanvien', ['trang_thai' => $trangThai ? 1 : 0], 'id = ?', [$id]);
        return ['ok' => true];
    }
}
