<?php

require_once dirname(__DIR__, 2) . '/model/danhmuc.php';
require_once dirname(__DIR__, 2) . '/model/sanpham.php';
require_once dirname(__DIR__, 2) . '/model/nhanvien.php';
require_once dirname(__DIR__, 2) . '/model/danhgia.php';
require_once dirname(__DIR__, 2) . '/model/khachhang.php';
require_once dirname(__DIR__, 2) . '/model/voucher.php';
require_once dirname(__DIR__, 2) . '/model/khuyenmai.php';
require_once dirname(__DIR__, 2) . '/model/donhang.php';
require_once dirname(__DIR__, 2) . '/model/chitietdonhang.php';
require_once dirname(__DIR__, 2) . '/controller/xuly_email.php';

require_once dirname(__DIR__, 2) . '/model/giaodien.php';

class admin_controller
{
    public function chiTietSanPham(int $id): ?array
    {
        return (new sanpham())->chiTietAdmin($id);
    }

    public function dangNhap(string $email, string $matKhau): array
    {
        return (new nhanvien())->kiemTraDangNhap($email, $matKhau);
    }

    public function trangSanPham(bool $ngungBan = false): array
    {
        (new donhang())->donPayPalHetHan();
        $sp = new sanpham();
        return [
            'ds_sanpham' => $sp->layDSAdmin($ngungBan),
            'xem_ngung_ban' => $ngungBan,
            'ds_danhmuc' => (new danhmuc())->layDS(),
            'sap_het' => $sp->canhBaoSapHet(5),
            'hang_e' => $sp->hangE(),
            'can_han' => $sp->canHan(),
        ];
    }

    public function luuSanPham(array $data, int $id = 0): array
    {
        require_once __DIR__ . '/../product_upload.php';
        $upload = product_upload::upload($data);
        if (!$upload['ok']) return $upload;
        $result = $id > 0 ? $this->suaSanPham($id, $upload['data']) : $this->themSanPham($upload['data']);
        if (!$result['ok']) product_upload::cleanup($upload['created']);
        return $result;
    }

    public function themSanPham(array $data): array
    {
        return (new sanpham())->them($data);
    }

    public function suaSanPham(int $id, array $data): array
    {
        return (new sanpham())->sua($id, $data);
    }

    public function xoaSanPham(int $id): array
    {
        return (new sanpham())->xoa($id);
    }

    public function xuLyDanhMuc(string $hanhDong, array $data): array
    {
        $dm = new danhmuc();
        try {
            if ($hanhDong === 'them') {
                $id = $dm->them(
                    (string) ($data['ten_danhmuc'] ?? ''),
                    isset($data['mo_ta']) ? (string) $data['mo_ta'] : null
                );
                return ['ok' => true, 'id' => $id];
            }
            if ($hanhDong === 'sua') {
                $dm->sua(
                    (int) ($data['id'] ?? 0),
                    (string) ($data['ten_danhmuc'] ?? ''),
                    isset($data['mo_ta']) ? (string) $data['mo_ta'] : null
                );
                return ['ok' => true];
            }
            if ($hanhDong === 'xoa') {
                return $dm->xoa((int) ($data['id'] ?? 0));
            }
        } catch (InvalidArgumentException $e) {
            return ['ok' => false, 'loi' => $e->getMessage()];
        } catch (Throwable $e) {
            error_log('Xu ly danh muc: ' . $e->getMessage());
            return ['ok' => false, 'loi' => 'Không thể lưu danh mục lúc này'];
        }
        return ['ok' => false, 'loi' => 'Hanh dong danh muc khong hop le'];
    }

    public function trangNhanVien(): array
    {
        return ['ds_nhanvien' => (new nhanvien())->layDS()];
    }

    public function themNhanVien(array $data): array
    {
        return (new nhanvien())->them($data);
    }

    public function khoaNhanVien(int $id, int $trangThai): array
    {
        return (new nhanvien())->doiTrangThai($id, $trangThai);
    }

    public function trangDanhGia(): array
    {
        return ['ds_danhgia' => (new danhgia())->layAllAdmin()];
    }

    public function khoaDanhGia(int $id): array
    {
        return (new danhgia())->khoa($id);
    }

    public function trangKhachHang(): array
    {
        $kh = new khachhang();
        return [
            'ds_khachhang' => $kh->layDS(),
            'ds_bom_huy_tra' => $kh->dsBomHuyTra(),
        ];
    }

    public function khoaKhachHang(int $id, int $trangThai): array
    {
        return $trangThai ? (new khachhang())->moKhoa($id) : (new khachhang())->khoa($id);
    }

    public function guiQuaTang(array $data): array
    {
        $hasFilter=false;
        foreach(['ngay'=>[1,31],'thang'=>[1,12],'nam'=>[1900,(int)date('Y')]] as $key=>[$min,$max]) {
            if (!isset($data[$key]) || $data[$key]==='') continue;
            if (filter_var($data[$key],FILTER_VALIDATE_INT,['options'=>['min_range'=>$min,'max_range'=>$max]])===false)
                return ['ok'=>false,'loi'=>'Điều kiện ngày sinh không hợp lệ.'];
            $hasFilter=true;
        }
        if (!$hasFilter) return ['ok'=>false,'loi'=>'Chọn ít nhất một điều kiện ngày sinh trước khi gửi thư.'];
        if (trim((string)($data['tieu_de'] ?? ''))==='' || trim((string)($data['noi_dung'] ?? ''))==='') return ['ok'=>false,'loi'=>'Nhập tiêu đề và nội dung thư.'];
        $ngay = isset($data['ngay']) && $data['ngay'] !== '' ? (int) $data['ngay'] : null;
        $thang = isset($data['thang']) && $data['thang'] !== '' ? (int) $data['thang'] : null;
        $nam = isset($data['nam']) && $data['nam'] !== '' ? (int) $data['nam'] : null;
        if ($ngay!==null && $thang!==null && !checkdate($thang,$ngay,$nam ?? 2000)) return ['ok'=>false,'loi'=>'Ngày sinh được chọn không tồn tại.'];
        $ds = (new khachhang())->locTheoNgaySinh($ngay, $thang, $nam);
        $mail = new xuly_email();
        $gui = [];
        foreach ($ds as $kh) {
            $gui[] = $mail->guiChung(
                (int) $kh['id'],
                (string) ($data['tieu_de'] ?? 'Tang qua / voucher'),
                (string) ($data['noi_dung'] ?? 'Chuc mung ban nhan duoc qua tang'),
                'quatang'
            );
        }
        return ['ok' => true, 'so_khach' => count($ds), 'so_thu_luu' => count(array_filter($gui, static fn($r)=>!empty($r['ok']))), 'so_smtp' => count(array_filter($gui, static fn($r)=>!empty($r['smtp']['ok']))), 'gui' => $gui];
    }

    public function trangVoucher(): array
    {
        return ['ds_voucher'=>(new voucher())->layDS(),'scopeReady'=>voucher::scopeReady(),
            'ds_danhmuc'=>(new danhmuc())->layDS(),'ds_sanpham'=>(new sanpham())->layDSAdmin()];
    }

    public function themVoucher(array $data): array
    {
        return (new voucher())->them($data);
    }

    public function xoaVoucher(int $id): array
    {
        return (new voucher())->xoa($id);
    }

    public function trangKhuyenMai(): array
    {
        $sp = new sanpham();
        return [
            'ds_khuyenmai' => (new khuyenmai())->layDS(),
            'goi_y_e' => $sp->hangE(),
            'goi_y_can_han' => $sp->canHan(),
            'ds_danhmuc' => (new danhmuc())->layDS(),
            'ds_sanpham' => $sp->layDS(),
        ];
    }

    public function themKhuyenMai(array $data): array
    {
        return (new khuyenmai())->them($data);
    }

    public function trangDonHang(): array
    {
        (new donhang())->donPayPalHetHan();
        return ['ds_donhang' => (new donhang())->layDS(), 'ds_khieunai' => (new email())->khieuNaiAdmin()];
    }

    public function chiTietDonHang(int $id): array
    {
        $dh = (new donhang())->layTrangThai($id);
        if ($dh === null) {
            return ['ok' => false, 'loi' => 'Khong tim thay don hang'];
        }
        $khach = (new khachhang())->layTheoId((int)$dh['id_khachhang']);
        $dh['ho_ten'] = $khach['ho_ten'] ?? 'Khách hàng #'.$dh['id_khachhang'];
        $dh['email'] = $khach['email'] ?? '';
        return ['ok' => true, 'donhang' => $dh];
    }

    public function capNhatTrangThai(int $id, string $trangThai, string $lyDo = '', bool $nhanHang = false, bool $nhapLai = true): array
    {
        return (new donhang())->capNhatTrangThai($id, $trangThai, $lyDo, $nhanHang, $nhapLai);
    }

    public function doanhThu(string $ky = 'thang'): array
    {
        return (new donhang())->thongKeDoanhThu($ky);
    }

    public function trangGiaoDien(): array
    {
        return ['theme' => (new giaodien())->doc()];
    }

    public function luuGiaoDien(array $data): array
    {
        return (new giaodien())->luu($data);
    }
}
