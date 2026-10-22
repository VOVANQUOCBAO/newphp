<?php

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 2));
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once BASE_PATH . '/controller/controller.php';
require_once BASE_PATH . '/controller/admin/admin_controller.php';

$ctrl = new controller();
$admin = new admin_controller();
$act = $ctrl->act();
if ($act === 'trangchu') {
    $act = empty($_SESSION['admin']) ? 'dangnhap' : 'sanpham';
}

date_default_timezone_set('Asia/Ho_Chi_Minh');
$thongBao = null;
$loi = null;

if ($act === 'dangnhap') {
    $ctrl->redirect('dangnhap');
}

if ($act === 'dangxuat') {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Thao tác đăng xuất phải gửi bằng POST.');
    }
    if (!$ctrl->verifyCsrfToken($_POST['_csrf'] ?? null)) {
        http_response_code(403);
        exit('Phiên biểu mẫu không hợp lệ hoặc đã hết hạn.');
    }
    unset($_SESSION['admin']);
    unset($_SESSION['admin_csrf']);
    $ctrl->redirect('dangnhap');
}

if ($ctrl->requireAdmin() === null) {
    $ctrl->redirect('dangnhap');
}
$currentAdmin = $_SESSION['admin'];
if (!$ctrl->canAccessAdminAction($act, (string) $currentAdmin['vai_tro'])) {
    http_response_code(403);
    exit('Tài khoản của bạn không có quyền thực hiện chức năng này.');
}

$mutationActions = [
    'taianhsanpham',
    'themsanpham',
    'suasanpham',
    'xoasanpham',
    'danhmuc',
    'themdanhmuc',
    'themnhanvien',
    'khoanhanvien',
    'doivaitronhanvien',
    'khoadanhgia',
    'khoakhachhang',
    'guiquatang',
    'themvoucher',
    'suaphamvivoucher', 'khoitaophamvivoucher',
    'xoavoucher',
    'themkhuyenmai',
    'suakhuyenmai',
    'capnhattrangthai',
    'kiemtrapaypal',
    'tuchoitra',
    'hoancod',
    'xacnhanqr', 'hoanqr', 'kiemtraqr', 'khoitaoqr',
];
$isMutation = in_array($act, $mutationActions, true)
    || (in_array($act,['giaodien','doimatkhau'],true) && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST');
if ($isMutation) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Thao tác này phải gửi bằng POST.');
    }
    if (!$ctrl->verifyCsrfToken($_POST['_csrf'] ?? null)) {
        http_response_code(403);
        exit('Phiên biểu mẫu không hợp lệ hoặc đã hết hạn.');
    }
}

try {
switch ($act) {
    case 'taianhsanpham':
        require_once BASE_PATH . '/controller/product_upload.php';
        try {
            $ctrl->json(productStageImage($_POST['image'] ?? null));
        } catch (InvalidArgumentException $e) {
            $ctrl->json(['ok' => false, 'loi' => $e->getMessage()], 422);
        }
        break;

    case 'sanpham':
        $data = $admin->trangSanPham();
        $ctrl->render('admin/sanpham.php', $data + compact('thongBao', 'loi') + ['act' => $act]);
        break;

    case 'themsanpham':
        $kq = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
            ? (!empty($_POST['id'])
                ? $admin->luuSanPham($_POST, (int) $_POST['id'])
                : $admin->luuSanPham($_POST))
            : ['ok' => false, 'loi' => 'Thao tác sản phẩm phải gửi bằng POST'];
        $loi = $kq['ok'] ? null : $kq['loi'];
        $thongBao = $kq['ok'] ? (!empty($_POST['id']) ? 'Đã cập nhật sản phẩm' : 'Đã thêm sản phẩm') : null;
        $data = $admin->trangSanPham();
        $ctrl->render('admin/sanpham.php', $data + compact('thongBao', 'loi') + ['act' => 'sanpham']);
        break;

    case 'suasanpham':
        $kq = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
            ? $admin->luuSanPham($_POST, (int) ($_POST['id'] ?? 0))
            : ['ok' => false, 'loi' => 'Thao tác sản phẩm phải gửi bằng POST'];
        $loi = $kq['ok'] ? null : $kq['loi'];
        $thongBao = $kq['ok'] ? 'Đã cập nhật sản phẩm' : null;
        $data = $admin->trangSanPham();
        $ctrl->render('admin/sanpham.php', $data + compact('thongBao', 'loi') + ['act' => 'sanpham']);
        break;

    case 'xoasanpham':
        $kq = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
            ? $admin->xoaSanPham((int) ($_POST['id'] ?? 0))
            : ['ok' => false, 'loi' => 'Thao tác sản phẩm phải gửi bằng POST'];
        $loi = $kq['ok'] ? null : $kq['loi'];
        $thongBao = $kq['ok'] ? 'Đã xóa sản phẩm' : null;
        $data = $admin->trangSanPham();
        $ctrl->render('admin/sanpham.php', $data + compact('thongBao', 'loi') + ['act' => 'sanpham']);
        break;

    case 'danhmuc':
    case 'themdanhmuc':
        $kq = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
            ? $admin->xuLyDanhMuc((string) ($_POST['hanh_dong'] ?? 'them'), $_POST)
            : ['ok' => false, 'loi' => 'Thao tác danh mục phải gửi bằng POST'];
        $loi = $kq['ok'] ? null : ($kq['loi'] ?? null);
        $thongBao = $kq['ok'] ? 'Đã lưu thay đổi danh mục' : null;
        $data = $admin->trangSanPham();
        $ctrl->render('admin/sanpham.php', $data + compact('thongBao', 'loi') + ['act' => 'sanpham']);
        break;

    case 'nhanvien':
        $data = $admin->trangNhanVien();
        $ctrl->render('admin/nhanvien.php', $data + compact('thongBao', 'loi') + ['act' => $act]);
        break;

    case 'themnhanvien':
        $kq = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
            ? $admin->themNhanVien($_POST)
            : ['ok' => false, 'loi' => 'Thao tác nhân viên phải gửi bằng POST'];
        $loi = $kq['ok'] ? null : $kq['loi'];
        $thongBao = $kq['ok'] ? 'Đã thêm tài khoản nhân viên' : null;
        $data = $admin->trangNhanVien();
        $ctrl->render('admin/nhanvien.php', $data + compact('thongBao', 'loi') + ['act' => 'nhanvien']);
        break;

    case 'khoanhanvien':
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $kq = ['ok' => false, 'loi' => 'Thao tác trạng thái nhân viên phải gửi bằng POST'];
        } else {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $tt = filter_var($_POST['trang_thai'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1]]);
            $kq = $id === false || $tt === false
                ? ['ok' => false, 'loi' => 'Mã nhân viên hoặc trạng thái không hợp lệ']
                : $admin->khoaNhanVien($id, $tt);
        }
        $data = $admin->trangNhanVien();
        $ctrl->render('admin/nhanvien.php', $data + [
            'thongBao' => $kq['ok'] ? 'Đã cập nhật trạng thái nhân viên' : null,
            'loi' => $kq['ok'] ? null : $kq['loi'],
            'act' => 'nhanvien',
        ]);
        break;

    case 'doivaitronhanvien':
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $vaiTro = $_POST['vai_tro'] ?? '';
        $kq = $id === false || !is_string($vaiTro)
            ? ['ok' => false, 'loi' => 'Mã nhân viên hoặc vai trò không hợp lệ']
            : (new nhanvien())->doiVaiTro($id, $vaiTro, (int) $currentAdmin['id']);
        $data = $admin->trangNhanVien();
        $ctrl->render('admin/nhanvien.php', $data + [
            'thongBao' => $kq['ok'] ? 'Đã cập nhật vai trò nhân viên' : null,
            'loi' => $kq['ok'] ? null : $kq['loi'],
            'act' => 'nhanvien',
        ]);
        break;

    case 'danhgia':
        $data = $admin->trangDanhGia();
        $ctrl->render('admin/danhgia.php', $data + compact('thongBao', 'loi') + ['act' => $act]);
        break;

    case 'khoadanhgia':
        $reviewState=filter_var($_POST['trang_thai'] ?? 0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>1]]);
        $kq=$reviewState===false ? ['ok'=>false,'loi'=>'Trạng thái đánh giá không hợp lệ.'] : (new danhgia())->datHienThi((int)($_POST['id'] ?? 0),$reviewState);
        $data = $admin->trangDanhGia();
        $ctrl->render('admin/danhgia.php', $data + ['thongBao' => $kq['ok'] ? ($reviewState ? 'Đã hiển thị lại đánh giá' : 'Đã ẩn đánh giá') : null, 'loi' => $kq['loi'] ?? null, 'act' => 'danhgia']);
        break;

    case 'khachhang':
        $data = $admin->trangKhachHang();
        $ctrl->render('admin/khachhang.php', $data + compact('thongBao', 'loi') + ['act' => $act]);
        break;

    case 'khoakhachhang':
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        $tt = (int) ($_GET['trang_thai'] ?? $_POST['trang_thai'] ?? 0);
        $kq = $admin->khoaKhachHang($id, $tt);
        $data = $admin->trangKhachHang();
        $ctrl->render('admin/khachhang.php', $data + ['thongBao' => $kq['ok'] ? 'Đã cập nhật trạng thái khách' : null, 'loi' => $kq['loi'] ?? null, 'act' => 'khachhang']);
        break;

    case 'guiquatang':
        $kq = $admin->guiQuaTang($_POST);
        $data = $admin->trangKhachHang();
        $ctrl->render('admin/khachhang.php', $data + [
            'thongBao' => !empty($kq['ok']) ? 'Đã gửi ' . ($kq['so_thu_luu'] ?? 0) . ' thư vào hộp thư; gửi email thành công ' . ($kq['so_smtp'] ?? 0) . ' thư.' : null,
            'loi' => $kq['loi'] ?? null,
            'act' => 'khachhang',
        ]);
        break;

    case 'voucher':
        $data = $admin->trangVoucher();
        $ctrl->render('admin/voucher.php', $data + compact('thongBao', 'loi') + ['act' => $act]);
        break;

    case 'themvoucher':
        $kq = $admin->themVoucher($_POST);
        $loi = $kq['ok'] ? null : $kq['loi'];
        $thongBao = $kq['ok'] ? 'Đã tạo voucher' : null;
        $data = $admin->trangVoucher();
        $ctrl->render('admin/voucher.php', $data + compact('thongBao', 'loi') + ['act' => 'voucher']);
        break;

    case 'khoitaophamvivoucher':
        require_once BASE_PATH.'/support/scripts/migrate_voucher_scope.php';
        migrateVoucherScope(csdl::shared());
        $ctrl->render('admin/voucher.php',$admin->trangVoucher()+['thongBao'=>'Đã khởi tạo phạm vi voucher. Mã cũ giữ nguyên toàn cửa hàng; hãy chọn phạm vi nếu cần.','loi'=>null,'act'=>'voucher']);
        break;

    case 'suaphamvivoucher':
        $kq=(new voucher())->suaPhamVi((int)($_POST['id'] ?? 0),$_POST);
        $ctrl->render('admin/voucher.php',$admin->trangVoucher()+['thongBao'=>$kq['ok'] ? 'Đã lưu voucher cho các lần đặt đơn tiếp theo. Đơn đã đặt giữ nguyên giảm giá; lượt còn lại không thay đổi.' : null,'loi'=>$kq['loi'] ?? null,'act'=>'voucher']);
        break;

    case 'xoavoucher':
        $kq = $admin->xoaVoucher((int) ($_POST['id'] ?? 0));
        $data = $admin->trangVoucher();
        $ctrl->render('admin/voucher.php', $data + ['thongBao' => $kq['ok'] ? 'Đã xóa voucher' : null, 'loi' => $kq['loi'] ?? null, 'act' => 'voucher']);
        break;

    case 'khuyenmai':
        $data = $admin->trangKhuyenMai();
        $ctrl->render('admin/khuyenmai.php', $data + compact('thongBao', 'loi') + ['act' => $act]);
        break;

    case 'themkhuyenmai':
    case 'suakhuyenmai':
        $kq = $act==='suakhuyenmai' ? (new khuyenmai())->sua((int)($_POST['id'] ?? 0),$_POST) : $admin->themKhuyenMai($_POST);
        $loi = $kq['ok'] ? null : $kq['loi'];
        $thongBao = $kq['ok'] ? 'Đã lưu chương trình. Giá mới áp dụng cho lần xem và đặt đơn tiếp theo; đơn đã lưu giữ nguyên.' : null;
        $data = $admin->trangKhuyenMai();
        $ctrl->render('admin/khuyenmai.php', $data + compact('thongBao', 'loi') + ['act' => 'khuyenmai']);
        break;

    case 'khoitaoqr':
        csdl::shared()->exec(file_get_contents(BASE_PATH.'/database/bank.sql'));
        $ctrl->render('admin/donhang.php',$admin->trangDonHang()+['thongBao'=>'Đã khởi tạo lưu trữ chuyển khoản QR','loi'=>null,'act'=>'donhang']);
        break;

    case 'xacnhanqr':
    case 'hoanqr':
    case 'kiemtraqr':
        $paymentModel=new thanhtoan_qr();
        if (($_POST['da_kiem_tra'] ?? '')!=='1') $kq=['ok'=>false,'loi'=>'Cần kiểm tra giao dịch trong ngân hàng trước khi xác nhận.'];
        elseif ($act==='xacnhanqr') $kq=$paymentModel->xacNhanNhan((int)($_POST['id'] ?? 0),(int)$currentAdmin['id'],(string)($_POST['tham_chieu'] ?? ''),$_POST['so_tien'] ?? '');
        elseif ($act==='hoanqr') $kq=$paymentModel->xacNhanHoan((int)($_POST['id'] ?? 0),(int)$currentAdmin['id'],(string)($_POST['tham_chieu'] ?? ''));
        else $kq=$paymentModel->khongNhanTien((int)($_POST['id'] ?? 0),(int)$currentAdmin['id']);
        $ctrl->render('admin/donhang.php',$admin->trangDonHang()+['thongBao'=>$kq['ok'] ? 'Đã lưu kết quả đối soát chuyển khoản' : null,'loi'=>$kq['loi'] ?? null,'act'=>'donhang']);
        break;

    case 'kiemtrapaypal':
        try {
            $payment = (new thanhtoan_paypal())->kiemTra((int) ($_POST['id'] ?? 0));
            $thongBao = thanhtoan_paypal::label($payment);
        } catch (paypal_error $e) { $loi = $e->getMessage(); }
        $ctrl->render('admin/donhang.php', $admin->trangDonHang() + ['thongBao' => $thongBao, 'loi' => $loi, 'act' => 'donhang']);
        break;

    case 'donhang':
        $data = $admin->trangDonHang();
        $ctrl->render('admin/donhang.php', $data + compact('thongBao', 'loi') + ['act' => $act]);
        break;

    case 'chitietdonhang':
        $kq = $admin->chiTietDonHang((int) ($_GET['id'] ?? 0));
        $ctrl->render('admin/donhang.php', [
            'ds_donhang' => $admin->trangDonHang()['ds_donhang'],
            'chitiet' => $kq['ok'] ? $kq['donhang'] : null,
            'thongBao' => null,
            'loi' => $kq['ok'] ? null : $kq['loi'],
            'act' => $act,
        ]);
        break;

    case 'capnhattrangthai':
        $kq = $admin->capNhatTrangThai(
            (int) ($_POST['id'] ?? $_GET['id'] ?? 0),
            (string) ($_POST['trang_thai'] ?? $_GET['trang_thai'] ?? ''),
            (string) ($_POST['ly_do'] ?? ''),
            !empty($_POST['da_nhan_hang']),
            !empty($_POST['nhap_lai_kho'])
        );
        $loi = $kq['ok'] ? null : $kq['loi'];
        $thongBao = $kq['ok'] ? 'Da cap nhat trang thai' : null;
        $data = $admin->trangDonHang();
        $ctrl->render('admin/donhang.php', $data + compact('thongBao', 'loi') + ['act' => 'donhang']);
        break;

    case 'hoancod':
        $kq = (new donhang())->xacNhanHoanCOD((int)($_POST['id'] ?? 0), (string)($_POST['tham_chieu'] ?? ''));
        $ctrl->render('admin/donhang.php', $admin->trangDonHang() + ['thongBao' => $kq['ok'] ? 'Đã ghi nhận chứng từ hoàn COD' : null, 'loi' => $kq['loi'] ?? null, 'act' => 'donhang']);
        break;

    case 'tuchoitra':
        $kq = (new donhang())->tuChoiTra((int)($_POST['id'] ?? 0), (string)($_POST['ly_do'] ?? ''));
        $ctrl->render('admin/donhang.php', $admin->trangDonHang() + ['thongBao' => $kq['ok'] ? 'Đã từ chối trả hàng' : null, 'loi' => $kq['loi'] ?? null, 'act' => 'donhang']);
        break;

    case 'doanhthu':
        $ky = $_GET['ky'] ?? 'thang';
        $data = $admin->doanhThu($ky);
        $ctrl->render('admin/doanhthu.php', $data + compact('thongBao', 'loi', 'ky') + ['act' => $act]);
        break;

    case 'kiemtra':
        require_once BASE_PATH . '/model/kiemtra_dulieu.php';
        $ctrl->render('admin/kiemtra.php', ['bao_cao' => (new kiemtra_dulieu())->baoCao(), 'act' => 'kiemtra']);
        break;

    case 'csdl':
        require_once BASE_PATH . '/model/csdl.php';
        $tables = [
            'danhmuc' => 'Danh mục',
            'sanpham' => 'Sản phẩm',
            'khachhang' => 'Khách hàng',
            'nhanvien' => 'Nhân viên',
            'danhgia' => 'Đánh giá',
            'voucher' => 'Voucher',
            'khuyenmai' => 'Khuyến mãi',
            'donhang' => 'Đơn hàng',
            'thanhtoan_qr' => 'Chuyển khoản QR',
            'chitietdonhang' => 'Chi tiết đơn hàng',
            'email' => 'Email',
            'trahang' => 'Trả hàng',
            'lichsu_donhang' => 'Lịch sử đơn',
            'lichsu_kho' => 'Lịch sử kho',
            'thanhtoan_paypal' => 'Thanh toán PayPal',
        ];
        $table = $_GET['bang'] ?? 'donhang';
        if (!thanhtoan_qr::schemaReady()) unset($tables['thanhtoan_qr']);
        if (!is_string($table) || !isset($tables[$table])) {
            http_response_code(400);
            $table = 'donhang';
            $loi = 'Bảng dữ liệu được yêu cầu không hợp lệ.';
        }

        require_once BASE_PATH . '/model/kiemtra_dulieu.php';
        $data = (new kiemtra_dulieu())->xemBang($tables, $table, max(1,(int)($_GET['trang'] ?? 1)));
        $ctrl->render('admin/csdl.php', $data + compact('tables','table','thongBao','loi') + ['act' => $act]);
        break;

    case 'giaodien':
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $kq = $admin->luuGiaoDien($_POST);
            $thongBao = $kq['ok'] ? 'Đã lưu giao diện' : null;
            $loi = $kq['loi'] ?? null;
        }
        $data = $admin->trangGiaoDien();
        $ctrl->render('admin/giaodien.php', $data + compact('thongBao', 'loi') + ['act' => 'giaodien']);
        break;

    case 'doimatkhau':
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $matKhauMoi = trim((string)($_POST['mat_khau_moi'] ?? ''));
            $xacNhan = trim((string)($_POST['xac_nhan_mat_khau'] ?? ''));
            if ($matKhauMoi === '') {
                $loi = 'Vui lòng nhập mật khẩu mới.';
            } elseif ($matKhauMoi !== $xacNhan) {
                $loi = 'Xác nhận mật khẩu mới không khớp.';
            } else {
                $nvModel = new nhanvien();
                $adminId = (int)($_SESSION['admin']['id'] ?? 1);
                $kq = $nvModel->doiMatKhau($adminId, $matKhauMoi);
                if ($kq['ok']) {
                    $thongBao = 'Đã đổi mật khẩu tài khoản của bạn.';
                } else {
                    $loi = $kq['loi'];
                }
            }
        }
        $ctrl->render('admin/doimatkhau.php', compact('thongBao', 'loi') + ['act' => 'doimatkhau']);
        break;

    default:
        $ctrl->json(['ok' => false, 'loi' => 'Khong ro act admin', 'act' => $act], 400);
}

} catch (Throwable $e) {
    error_log("Admin request: " . $e->getMessage());
    $ctrl->json(["ok" => false, "loi" => "Không thể xử lý yêu cầu. Vui lòng tải lại trang và thử lại."], 500);
}
