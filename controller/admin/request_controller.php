<?php

require_once BASE_PATH . '/controller/controller.php';
require_once BASE_PATH . '/controller/admin/admin_controller.php';


/** Nhận yêu cầu, gọi model và chuyển dữ liệu cho view. */
final class admin_request_controller extends controller
{
    // Thuộc tính dùng chung trong một yêu cầu HTTP.
    private string $act;
    private admin_controller $admin;
    private array $currentAdmin;

    public function run(): void
    {
        try {
            $this->validateInputs();
            $this->admin = new admin_controller();
            $this->act = $this->act();
            if ($this->act === 'trangchu') {
                $this->act = empty($_SESSION['admin']) ? 'dangnhap' : 'sanpham';
            }

            date_default_timezone_set('Asia/Ho_Chi_Minh');

            if ($this->act === 'dangnhap') {
                $this->redirect('dangnhap');
            }

            if ($this->act === 'dangxuat') {
                if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
                    http_response_code(405);
                    header('Allow: POST');
                    exit('Thao tác đăng xuất phải gửi bằng POST.');
                }
                if (!$this->verifyCsrfToken($_POST['_csrf'] ?? null)) {
                    http_response_code(403);
                    exit('Phiên biểu mẫu không hợp lệ hoặc đã hết hạn.');
                }
                unset($_SESSION['admin']);
                unset($_SESSION['admin_csrf']);
                $this->redirect('dangnhap');
            }

            if ($this->requireAdmin() === null) {
                $this->redirect('dangnhap');
            }
            $this->currentAdmin = $_SESSION['admin'];
            if (!nhanvien::laSuperAdmin((string)$this->currentAdmin['vai_tro'])
                && in_array($this->act,['themnhanvien','doivaitronhanvien'],true)
                && nhanvien::laSuperAdmin((string)($_POST['vai_tro'] ?? ''))) {
                http_response_code(403); exit('Chỉ quản trị viên được tạo hoặc cấp quyền quản trị.');
            }
            if (!$this->canAccessAdminAction($this->act, (string) $this->currentAdmin['vai_tro'])) {
                http_response_code(403);
                exit('Tài khoản của bạn không có quyền thực hiện chức năng này.');
            }

            $mutationActions = [
                'taianhsanpham',
                'themsanpham',
                'suasanpham',
                'xoasanpham',
                'mobanlaisanpham',
                'luucauhinhmail', 'guilaithanhtoan', 'guithunghiemmail',
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
                'phanhoikhieunai',
                'kiemtrapaypal',
                'tuchoitra',
                'hoancod',
                'xacnhanqr', 'hoanqr', 'kiemtraqr', 'khoitaoqr',
            ];
            $isMutation = in_array($this->act, $mutationActions, true)
                || (in_array($this->act,['giaodien','doimatkhau','khoitaotienich'],true) && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST');
            if ($isMutation) {
                if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
                    http_response_code(405);
                    header('Allow: POST');
                    exit('Thao tác này phải gửi bằng POST.');
                }
                if (!$this->verifyCsrfToken($_POST['_csrf'] ?? null)) {
                    http_response_code(403);
                    exit('Phiên biểu mẫu không hợp lệ hoặc đã hết hạn.');
                }
            }

            switch ($this->act) {
                case 'chitietsanpham':
                    $this->action_chitietsanpham();
                    break;
                case 'diemthanhvien':
                    $this->action_diemthanhvien();
                    break;
                case 'khoitaotienich':
                    $this->action_khoitaotienich();
                    break;
                case 'nhapkho':
                    $this->action_nhapkho();
                    break;
                case 'thongbao':
                case 'luucauhinhmail':
                case 'guilaithanhtoan':
                case 'guithunghiemmail':
                    $this->action_thongbao();
                    break;
                case 'taianhsanpham':
                    $this->action_taianhsanpham();
                    break;
                case 'sanpham':
                    $this->action_sanpham();
                    break;
                case 'themsanpham':
                    $this->action_themsanpham();
                    break;
                case 'suasanpham':
                    $this->action_suasanpham();
                    break;
                case 'xoasanpham':
                    $this->action_xoasanpham();
                    break;
                case 'mobanlaisanpham':
                    $this->action_mobanlaisanpham();
                    break;
                case 'danhmuc':
                case 'themdanhmuc':
                    $this->action_danhmuc();
                    break;
                case 'nhanvien':
                    $this->action_nhanvien();
                    break;
                case 'themnhanvien':
                    $this->action_themnhanvien();
                    break;
                case 'khoanhanvien':
                    $this->action_khoanhanvien();
                    break;
                case 'doivaitronhanvien':
                    $this->action_doivaitronhanvien();
                    break;
                case 'danhgia':
                    $this->action_danhgia();
                    break;
                case 'khoadanhgia':
                    $this->action_khoadanhgia();
                    break;
                case 'khachhang':
                    $this->action_khachhang();
                    break;
                case 'khoakhachhang':
                    $this->action_khoakhachhang();
                    break;
                case 'guiquatang':
                    $this->action_guiquatang();
                    break;
                case 'voucher':
                    $this->action_voucher();
                    break;
                case 'themvoucher':
                    $this->action_themvoucher();
                    break;
                case 'khoitaophamvivoucher':
                    $this->action_khoitaophamvivoucher();
                    break;
                case 'suaphamvivoucher':
                    $this->action_suaphamvivoucher();
                    break;
                case 'xoavoucher':
                    $this->action_xoavoucher();
                    break;
                case 'khuyenmai':
                    $this->action_khuyenmai();
                    break;
                case 'themkhuyenmai':
                case 'suakhuyenmai':
                    $this->action_themkhuyenmai();
                    break;
                case 'khoitaoqr':
                    $this->action_khoitaoqr();
                    break;
                case 'xacnhanqr':
                case 'hoanqr':
                case 'kiemtraqr':
                    $this->action_xacnhanqr();
                    break;
                case 'kiemtrapaypal':
                    $this->action_kiemtrapaypal();
                    break;
                case 'donhang':
                    $this->action_donhang();
                    break;
                case 'phanhoikhieunai':
                    $this->action_phanhoikhieunai();
                    break;
                case 'chitietdonhang':
                    $this->action_chitietdonhang();
                    break;
                case 'capnhattrangthai':
                    $this->action_capnhattrangthai();
                    break;
                case 'hoancod':
                    $this->action_hoancod();
                    break;
                case 'tuchoitra':
                    $this->action_tuchoitra();
                    break;
                case 'doanhthu':
                    $this->action_doanhthu();
                    break;
                case 'kiemtra':
                    $this->action_kiemtra();
                    break;
                case 'csdl':
                    $this->action_csdl();
                    break;
                case 'giaodien':
                    $this->action_giaodien();
                    break;
                case 'doimatkhau':
                    $this->action_doimatkhau();
                    break;
                default:
                    $this->unknownAction();
                    break;
            }
        } catch (InvalidArgumentException | TypeError $e) {
            $this->json(['ok' => false, 'loi' => 'Dữ liệu gửi lên không hợp lệ.'], 400);
        } catch (Throwable $e) {
            error_log("Admin request: " . $e->getMessage());
            $this->json(["ok" => false, "loi" => "Không thể xử lý yêu cầu. Vui lòng tải lại trang và thử lại."], 500);
        }
    }

    private function action_chitietsanpham(): void
    {
        $product = $this->admin->chiTietSanPham((int)($_GET['id'] ?? 0));
        if ($product === null) http_response_code(404);
        $this->render('admin/chitiet_sanpham.php', ['sanpham'=>$product, 'act'=>'sanpham']);
        return;
    }

    private function action_diemthanhvien(): void
    {
        require_once BASE_PATH.'/model/diem_thanhvien.php';
        $customerId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if (!$customerId || !(new khachhang())->layTheoId($customerId)) {
            $this->json(['ok'=>false,'loi'=>'Không tìm thấy khách hàng.'],404);
            return;
        }
        $this->render('admin/diem_thanhvien.php', ['bao_cao'=>(new diem_thanhvien())->baoCao($customerId, (int)($_GET['trang'] ?? 1)), 'act'=>'khachhang']);
        return;
    }

    private function action_khoitaotienich(): void
    {
        require_once BASE_PATH.'/support/scripts/migrate_store_tools.php';
        if (($_SERVER['REQUEST_METHOD'] ?? '')==='POST') {
            migration_store_tools::run(csdl::shared());
            $this->json(['ok'=>true,'thong_bao'=>'Đã bật hồ sơ, yêu thích, ngày nhập và thông báo thanh toán.']);
        } else $this->render('admin/bat_tienich.php',['act'=>'kiemtra']);
        return;
    }

    private function action_nhapkho(): void
    {
        require_once BASE_PATH.'/model/nhapkho.php';
        $this->render('admin/nhapkho.php',['receipts'=>(new nhapkho())->layDS(),'act'=>'nhapkho']);
        return;
    }

    private function action_thongbao(): void
    {
        $thongBao = null;
        $loi = null;
        require_once BASE_PATH.'/model/thongbao_thanhtoan.php';
        if ($this->act==='luucauhinhmail') {
            $kq=(new email())->luuCauHinh($_POST);
            $loi=$kq['loi'] ?? null; $thongBao=$kq['ok']?'Đã lưu địa chỉ gửi email. Thư lỗi có thể gửi lại ở bảng bên dưới.':null;
        } elseif ($this->act==='guilaithanhtoan') {
            $kq=(new thongbao_thanhtoan())->gui((int)($_POST['id_donhang'] ?? 0));
            $loi=$kq['loi'] ?? null; $thongBao=$kq['ok']?'Dịch vụ gửi thư đã nhận email. Kiểm tra hộp thư đến và thư rác của người nhận.':null;
        } elseif ($this->act==='guithunghiemmail') {
            if (time()-(int)($_SESSION['smtp_test_at'] ?? 0)<60) {
                $loi='Chờ 60 giây giữa hai lần gửi email kiểm tra.';
            } else {
                $_SESSION['smtp_test_at']=time();
                $cfg=(new email())->cauHinhSmtp();
                if (!filter_var($cfg['from'],FILTER_VALIDATE_EMAIL) || $cfg['user']==='' || $cfg['pass']==='') {
                    $loi='Lưu cấu hình gửi thư trước khi gửi email kiểm tra.';
                } else {
                    require_once BASE_PATH.'/controller/xuly_email.php';
                    $testCode=strtoupper(bin2hex(random_bytes(4)));
                    $title='[ĐiệnThoạiZ] Kiểm tra gửi email · '.$testCode;
                    $body="Đây là email kiểm tra cấu hình gửi thư của cửa hàng ĐiệnThoạiZ.\nMã kiểm tra: ".$testCode
                        ."\nThời gian: ".date('d/m/Y H:i:s')." (Việt Nam).\nEmail này không phải xác nhận thanh toán. Không có đơn hàng hay giao dịch được tạo.";
                    $kq=(new xuly_email())->guiSmtp($cfg['from'],$title,$body);
                    $loi=$kq['loi'] ?? null;
                    $thongBao=$kq['ok']?'Máy chủ SMTP đã nhận thư kiểm tra '.$testCode.' gửi đến '.$cfg['from'].'. Hãy kiểm tra hộp thư đến và thư rác.':null;
                }
            }
        }
        $smtp=(new email())->cauHinhSmtp();
        $this->render('admin/thongbao.php',['smtp_config'=>array_intersect_key($smtp,array_flip(['host','port','user','from','from_name'])),
            'smtp_ready'=>$smtp['user']!=='' && $smtp['pass']!=='','letters'=>(new thongbao_thanhtoan())->layDS(),
            'act'=>'thongbao','loi'=>$loi,'thongBao'=>$thongBao]);
        return;
    }

    private function action_taianhsanpham(): void
    {
        require_once BASE_PATH . '/controller/product_upload.php';
        try {
            $this->json(product_upload::stageImage($_POST['image'] ?? null));
        } catch (InvalidArgumentException $e) {
            $this->json(['ok' => false, 'loi' => $e->getMessage()], 422);
        }
        return;
    }

    private function action_sanpham(): void
    {
        $thongBao = null;
        $loi = null;
        $data = $this->admin->trangSanPham(($_GET['trang_thai'] ?? '') === 'ngungban');
        $this->render('admin/sanpham.php', $data + compact('thongBao', 'loi') + ['act' => $this->act]);
        return;
    }

    private function action_themsanpham(): void
    {
        $thongBao = null;
        $loi = null;
        $kq = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
            ? (!empty($_POST['id'])
                ? $this->admin->luuSanPham($_POST, (int) $_POST['id'])
                : $this->admin->luuSanPham($_POST))
            : ['ok' => false, 'loi' => 'Thao tác sản phẩm phải gửi bằng POST'];
        $loi = $kq['ok'] ? null : $kq['loi'];
        $thongBao = $kq['ok'] ? (!empty($_POST['id']) ? 'Đã cập nhật sản phẩm' : 'Đã thêm sản phẩm') : null;
        $data = $this->admin->trangSanPham();
        $this->render('admin/sanpham.php', $data + compact('thongBao', 'loi') + ['act' => 'sanpham']);
        return;
    }

    private function action_suasanpham(): void
    {
        $thongBao = null;
        $loi = null;
        $kq = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
            ? $this->admin->luuSanPham($_POST, (int) ($_POST['id'] ?? 0))
            : ['ok' => false, 'loi' => 'Thao tác sản phẩm phải gửi bằng POST'];
        $loi = $kq['ok'] ? null : $kq['loi'];
        $thongBao = $kq['ok'] ? 'Đã cập nhật sản phẩm' : null;
        $data = $this->admin->trangSanPham();
        $this->render('admin/sanpham.php', $data + compact('thongBao', 'loi') + ['act' => 'sanpham']);
        return;
    }

    private function action_xoasanpham(): void
    {
        $thongBao = null;
        $loi = null;
        $kq = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
            ? $this->admin->xoaSanPham((int) ($_POST['id'] ?? 0))
            : ['ok' => false, 'loi' => 'Thao tác sản phẩm phải gửi bằng POST'];
        $loi = $kq['ok'] ? null : $kq['loi'];
        $thongBao = $kq['ok'] ? ($kq['thong_bao'] ?? 'Đã xóa sản phẩm') : null;
        $data = $this->admin->trangSanPham();
        $this->render('admin/sanpham.php', $data + compact('thongBao', 'loi') + ['act' => 'sanpham']);
        return;
    }

    private function action_mobanlaisanpham(): void
    {
        $kq = (new sanpham())->moBanLai((int) ($_POST['id'] ?? 0));
        $data = $this->admin->trangSanPham(true);
        $this->render('admin/sanpham.php', $data + [
            'thongBao' => $kq['ok'] ? 'Đã mở bán lại sản phẩm.' : null,
            'loi' => $kq['loi'] ?? null, 'act' => 'sanpham',
        ]);
        return;
    }

    private function action_danhmuc(): void
    {
        $thongBao = null;
        $loi = null;
        $kq = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
            ? $this->admin->xuLyDanhMuc((string) ($_POST['hanh_dong'] ?? 'them'), $_POST)
            : ['ok' => false, 'loi' => 'Thao tác danh mục phải gửi bằng POST'];
        $loi = $kq['ok'] ? null : ($kq['loi'] ?? null);
        $thongBao = $kq['ok'] ? 'Đã lưu thay đổi danh mục' : null;
        $data = $this->admin->trangSanPham();
        $this->render('admin/sanpham.php', $data + compact('thongBao', 'loi') + ['act' => 'sanpham']);
        return;
    }

    private function action_nhanvien(): void
    {
        $thongBao = null;
        $loi = null;
        $data = $this->admin->trangNhanVien();
        $this->render('admin/nhanvien.php', $data + compact('thongBao', 'loi') + ['act' => $this->act]);
        return;
    }

    private function action_themnhanvien(): void
    {
        $thongBao = null;
        $loi = null;
        $kq = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
            ? $this->admin->themNhanVien($_POST)
            : ['ok' => false, 'loi' => 'Thao tác nhân viên phải gửi bằng POST'];
        $loi = $kq['ok'] ? null : $kq['loi'];
        $thongBao = $kq['ok'] ? 'Đã thêm tài khoản nhân viên' : null;
        $data = $this->admin->trangNhanVien();
        $this->render('admin/nhanvien.php', $data + compact('thongBao', 'loi') + ['act' => 'nhanvien']);
        return;
    }

    private function action_khoanhanvien(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $kq = ['ok' => false, 'loi' => 'Thao tác trạng thái nhân viên phải gửi bằng POST'];
        } else {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $tt = filter_var($_POST['trang_thai'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1]]);
            $kq = $id === false || $tt === false
                ? ['ok' => false, 'loi' => 'Mã nhân viên hoặc trạng thái không hợp lệ']
                : $this->admin->khoaNhanVien($id, $tt);
        }
        $data = $this->admin->trangNhanVien();
        $this->render('admin/nhanvien.php', $data + [
            'thongBao' => $kq['ok'] ? 'Đã cập nhật trạng thái nhân viên' : null,
            'loi' => $kq['ok'] ? null : $kq['loi'],
            'act' => 'nhanvien',
        ]);
        return;
    }

    private function action_doivaitronhanvien(): void
    {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $vaiTro = $_POST['vai_tro'] ?? '';
        $kq = $id === false || !is_string($vaiTro)
            ? ['ok' => false, 'loi' => 'Mã nhân viên hoặc vai trò không hợp lệ']
            : (new nhanvien())->doiVaiTro($id, $vaiTro, (int) $this->currentAdmin['id']);
        $data = $this->admin->trangNhanVien();
        $this->render('admin/nhanvien.php', $data + [
            'thongBao' => $kq['ok'] ? 'Đã cập nhật vai trò nhân viên' : null,
            'loi' => $kq['ok'] ? null : $kq['loi'],
            'act' => 'nhanvien',
        ]);
        return;
    }

    private function action_danhgia(): void
    {
        $thongBao = null;
        $loi = null;
        $data = $this->admin->trangDanhGia();
        $this->render('admin/danhgia.php', $data + compact('thongBao', 'loi') + ['act' => $this->act]);
        return;
    }

    private function action_khoadanhgia(): void
    {
        $reviewState=filter_var($_POST['trang_thai'] ?? 0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>1]]);
        $kq=$reviewState===false ? ['ok'=>false,'loi'=>'Trạng thái đánh giá không hợp lệ.'] : (new danhgia())->datHienThi((int)($_POST['id'] ?? 0),$reviewState);
        $data = $this->admin->trangDanhGia();
        $this->render('admin/danhgia.php', $data + ['thongBao' => $kq['ok'] ? ($reviewState ? 'Đã hiển thị lại đánh giá' : 'Đã ẩn đánh giá') : null, 'loi' => $kq['loi'] ?? null, 'act' => 'danhgia']);
        return;
    }

    private function action_khachhang(): void
    {
        $thongBao = null;
        $loi = null;
        $data = $this->admin->trangKhachHang();
        $this->render('admin/khachhang.php', $data + compact('thongBao', 'loi') + ['act' => $this->act]);
        return;
    }

    private function action_khoakhachhang(): void
    {
        $id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        $tt = (int) ($_GET['trang_thai'] ?? $_POST['trang_thai'] ?? 0);
        $kq = $this->admin->khoaKhachHang($id, $tt);
        $data = $this->admin->trangKhachHang();
        $this->render('admin/khachhang.php', $data + ['thongBao' => $kq['ok'] ? 'Đã cập nhật trạng thái khách' : null, 'loi' => $kq['loi'] ?? null, 'act' => 'khachhang']);
        return;
    }

    private function action_guiquatang(): void
    {
        $kq = $this->admin->guiQuaTang($_POST);
        $data = $this->admin->trangKhachHang();
        $this->render('admin/khachhang.php', $data + [
            'thongBao' => !empty($kq['ok']) ? 'Đã gửi ' . ($kq['so_thu_luu'] ?? 0) . ' thư vào hộp thư; gửi email thành công ' . ($kq['so_smtp'] ?? 0) . ' thư.' : null,
            'loi' => $kq['loi'] ?? null,
            'act' => 'khachhang',
        ]);
        return;
    }

    private function action_voucher(): void
    {
        $thongBao = null;
        $loi = null;
        $data = $this->admin->trangVoucher();
        $this->render('admin/voucher.php', $data + compact('thongBao', 'loi') + ['act' => $this->act]);
        return;
    }

    private function action_themvoucher(): void
    {
        $thongBao = null;
        $loi = null;
        $kq = $this->admin->themVoucher($_POST);
        $loi = $kq['ok'] ? null : $kq['loi'];
        $thongBao = $kq['ok'] ? 'Đã tạo voucher' : null;
        $data = $this->admin->trangVoucher();
        $this->render('admin/voucher.php', $data + compact('thongBao', 'loi') + ['act' => 'voucher']);
        return;
    }

    private function action_khoitaophamvivoucher(): void
    {
        require_once BASE_PATH.'/support/scripts/migrate_voucher_scope.php';
        migration_voucher_scope::run(csdl::shared());
        $this->render('admin/voucher.php',$this->admin->trangVoucher()+['thongBao'=>'Đã khởi tạo phạm vi voucher. Mã cũ giữ nguyên toàn cửa hàng; hãy chọn phạm vi nếu cần.','loi'=>null,'act'=>'voucher']);
        return;
    }

    private function action_suaphamvivoucher(): void
    {
        $kq=(new voucher())->suaPhamVi((int)($_POST['id'] ?? 0),$_POST);
        $this->render('admin/voucher.php',$this->admin->trangVoucher()+['thongBao'=>$kq['ok'] ? 'Đã lưu voucher cho các lần đặt đơn tiếp theo. Đơn đã đặt giữ nguyên giảm giá; lượt còn lại không thay đổi.' : null,'loi'=>$kq['loi'] ?? null,'act'=>'voucher']);
        return;
    }

    private function action_xoavoucher(): void
    {
        $kq = $this->admin->xoaVoucher((int) ($_POST['id'] ?? 0));
        $data = $this->admin->trangVoucher();
        $this->render('admin/voucher.php', $data + ['thongBao' => $kq['ok'] ? 'Đã xóa voucher' : null, 'loi' => $kq['loi'] ?? null, 'act' => 'voucher']);
        return;
    }

    private function action_khuyenmai(): void
    {
        $thongBao = null;
        $loi = null;
        $data = $this->admin->trangKhuyenMai();
        $this->render('admin/khuyenmai.php', $data + compact('thongBao', 'loi') + ['act' => $this->act]);
        return;
    }

    private function action_themkhuyenmai(): void
    {
        $thongBao = null;
        $loi = null;
        $kq = $this->act==='suakhuyenmai' ? (new khuyenmai())->sua((int)($_POST['id'] ?? 0),$_POST) : $this->admin->themKhuyenMai($_POST);
        $loi = $kq['ok'] ? null : $kq['loi'];
        $thongBao = $kq['ok'] ? 'Đã lưu chương trình. Giá mới áp dụng cho lần xem và đặt đơn tiếp theo; đơn đã lưu giữ nguyên.' : null;
        $data = $this->admin->trangKhuyenMai();
        $this->render('admin/khuyenmai.php', $data + compact('thongBao', 'loi') + ['act' => 'khuyenmai']);
        return;
    }

    private function action_khoitaoqr(): void
    {
        csdl::shared()->exec(file_get_contents(BASE_PATH.'/database/bank.sql'));
        $this->render('admin/donhang.php',$this->admin->trangDonHang()+['thongBao'=>'Đã khởi tạo lưu trữ chuyển khoản QR','loi'=>null,'act'=>'donhang']);
        return;
    }

    private function action_xacnhanqr(): void
    {
        $paymentModel=new thanhtoan_qr();
        if (($_POST['da_kiem_tra'] ?? '')!=='1') $kq=['ok'=>false,'loi'=>'Cần kiểm tra giao dịch trong ngân hàng trước khi xác nhận.'];
        elseif ($this->act==='xacnhanqr') $kq=$paymentModel->xacNhanNhan((int)($_POST['id'] ?? 0),(int)$this->currentAdmin['id'],(string)($_POST['tham_chieu'] ?? ''),$_POST['so_tien'] ?? '');
        elseif ($this->act==='hoanqr') $kq=$paymentModel->xacNhanHoan((int)($_POST['id'] ?? 0),(int)$this->currentAdmin['id'],(string)($_POST['tham_chieu'] ?? ''));
        else $kq=$paymentModel->khongNhanTien((int)($_POST['id'] ?? 0),(int)$this->currentAdmin['id']);
        $this->render('admin/donhang.php',$this->admin->trangDonHang()+['thongBao'=>$kq['ok'] ? 'Đã lưu kết quả đối soát chuyển khoản' : null,'loi'=>$kq['loi'] ?? null,'act'=>'donhang']);
        return;
    }

    private function action_kiemtrapaypal(): void
    {
        $thongBao = null;
        $loi = null;
        try {
            $payment = (new thanhtoan_paypal())->kiemTra((int) ($_POST['id'] ?? 0));
            $thongBao = thanhtoan_paypal::label($payment);
        } catch (paypal_error $e) { $loi = $e->getMessage(); }
        $this->render('admin/donhang.php', $this->admin->trangDonHang() + ['thongBao' => $thongBao, 'loi' => $loi, 'act' => 'donhang']);
        return;
    }

    private function action_donhang(): void
    {
        $thongBao = null;
        $loi = null;
        $data = $this->admin->trangDonHang();
        $this->render('admin/donhang.php', $data + compact('thongBao', 'loi') + ['act' => $this->act]);
        return;
    }

    private function action_phanhoikhieunai(): void
    {
        $canhBao = null;
        $thongBao = null;
        $loi = null;
        $reply = (new email())->luuPhanHoiKhieuNai(
            (int)($_POST['id_khieunai'] ?? 0),
            (string)($_POST['noi_dung'] ?? '')
        );
        if (empty($reply['ok'])) {
            $loi = $reply['loi'] ?? 'Không lưu được phản hồi.';
        } else {
            $thongBao = 'Đã lưu phản hồi trong Hộp thư cá nhân của khách hàng.';
            if (!filter_var($reply['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
                $canhBao = 'Phản hồi đã lưu trên website nhưng tài khoản khách chưa có email hợp lệ để gửi thư.';
            } else {
                require_once BASE_PATH . '/controller/xuly_email.php';
                $safeName = htmlspecialchars((string)$reply['ho_ten'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeComplaintId = (int)($_POST['id_khieunai'] ?? 0);
                $safeReply = nl2br(htmlspecialchars((string)$reply['noi_dung_tra_loi'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
                $html = '<!doctype html><html lang="vi"><meta charset="utf-8"><body style="margin:0;background:#f4f6fa;font-family:Arial,sans-serif;color:#172033">'
                    . '<div style="max-width:620px;margin:24px auto;padding:0 12px"><div style="background:#111a3a;color:#fff;padding:22px 26px;border-radius:14px 14px 0 0">'
                    . '<div style="font-size:12px;letter-spacing:2px;color:#ff6a00;font-weight:bold">BP STORE</div><h1 style="font-size:21px;margin:10px 0 0">Phản hồi khiếu nại</h1></div>'
                    . '<div style="background:#fff;padding:26px;border:1px solid #e2e7f0;border-radius:0 0 14px 14px"><p>Xin chào <strong>' . $safeName . '</strong>,</p>'
                    . '<p>BP Store đã phản hồi khiếu nại <strong>#' . $safeComplaintId . '</strong> của bạn:</p>'
                    . '<div style="margin:20px 0;padding:18px;background:#fff7ef;border-left:4px solid #ff6a00;border-radius:6px;line-height:1.7">' . $safeReply . '</div>'
                    . '<p style="color:#526078">Bạn cũng có thể xem nội dung này trong mục <strong>Hộp thư cá nhân</strong> trên website BP Store.</p>'
                    . '<p style="margin-top:26px">Trân trọng,<br><strong>BP Store · Chăm sóc khách hàng</strong></p></div></div></body></html>';
                $sent = (new xuly_email())->guiSmtp($reply['email'], $reply['tieu_de'], $reply['noi_dung'], $html);
                if (!empty($sent['ok'])) {
                    $thongBao .= ' Email phản hồi cũng đã được gửi đến ' . $reply['email'] . '.';
                } else {
                    $canhBao = 'Email ngoài chưa gửi được. Phản hồi vẫn đã lưu trong hộp thư website để khách xem.';
                }
            }
        }
        $data = array_replace($this->admin->trangDonHang(), compact('thongBao', 'loi', 'canhBao'), ['act' => 'donhang']);
        $this->render('admin/donhang.php', $data);
        return;
    }

    private function action_chitietdonhang(): void
    {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        $kq = $this->admin->chiTietDonHang($id ?: 0);
        if (!$kq['ok']) http_response_code(404);
        $this->render('admin/chitiet_donhang.php', [
            'chitiet' => $kq['ok'] ? $kq['donhang'] : null,
            'thongBao' => null,
            'loi' => $kq['ok'] ? null : $kq['loi'],
            'act' => $this->act,
        ]);
        return;
    }

    private function action_capnhattrangthai(): void
    {
        $thongBao = null;
        $loi = null;
        $kq = $this->admin->capNhatTrangThai(
            (int) ($_POST['id'] ?? $_GET['id'] ?? 0),
            (string) ($_POST['trang_thai'] ?? $_GET['trang_thai'] ?? ''),
            (string) ($_POST['ly_do'] ?? ''),
            !empty($_POST['da_nhan_hang']),
            !empty($_POST['nhap_lai_kho'])
        );
        $loi = $kq['ok'] ? null : $kq['loi'];
        $thongBao = $kq['ok'] ? 'Da cap nhat trang thai' : null;
        $data = $this->admin->trangDonHang();
        $this->render('admin/donhang.php', $data + compact('thongBao', 'loi') + ['act' => 'donhang']);
        return;
    }

    private function action_hoancod(): void
    {
        $kq = (new donhang())->xacNhanHoanCOD((int)($_POST['id'] ?? 0), (string)($_POST['tham_chieu'] ?? ''));
        $this->render('admin/donhang.php', $this->admin->trangDonHang() + ['thongBao' => $kq['ok'] ? 'Đã ghi nhận chứng từ hoàn COD' : null, 'loi' => $kq['loi'] ?? null, 'act' => 'donhang']);
        return;
    }

    private function action_tuchoitra(): void
    {
        $kq = (new donhang())->tuChoiTra((int)($_POST['id'] ?? 0), (string)($_POST['ly_do'] ?? ''));
        $this->render('admin/donhang.php', $this->admin->trangDonHang() + ['thongBao' => $kq['ok'] ? 'Đã từ chối trả hàng' : null, 'loi' => $kq['loi'] ?? null, 'act' => 'donhang']);
        return;
    }

    private function action_doanhthu(): void
    {
        $thongBao = null;
        $loi = null;
        $ky = $_GET['ky'] ?? 'thang';
        $data = $this->admin->doanhThu($ky);
        $this->render('admin/doanhthu.php', $data + compact('thongBao', 'loi', 'ky') + ['act' => $this->act]);
        return;
    }

    private function action_kiemtra(): void
    {
        require_once BASE_PATH . '/model/kiemtra_dulieu.php';
        $this->render('admin/kiemtra.php', ['bao_cao' => (new kiemtra_dulieu())->baoCao(), 'act' => 'kiemtra']);
        return;
    }

    private function action_csdl(): void
    {
        $thongBao = null;
        $loi = null;
        require_once BASE_PATH . '/model/csdl.php';
        require_once BASE_PATH . '/support/scripts/migrate_store_tools.php';
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
        if (migration_store_tools::ready(csdl::shared())) $tables += ['lichsu_nhap'=>'Lịch sử nhập hàng','yeuthich'=>'Yêu thích','email_thanhtoan'=>'Email thanh toán'];
        if (!thanhtoan_qr::schemaReady()) unset($tables['thanhtoan_qr']);
        if (!is_string($table) || !isset($tables[$table])) {
            http_response_code(400);
            $table = 'donhang';
            $loi = 'Bảng dữ liệu được yêu cầu không hợp lệ.';
        }

        require_once BASE_PATH . '/model/kiemtra_dulieu.php';
        $data = (new kiemtra_dulieu())->xemBang($tables, $table, max(1,(int)($_GET['trang'] ?? 1)));
        $this->render('admin/csdl.php', $data + compact('tables', 'table', 'thongBao', 'loi') + ['act' => $this->act]);
        return;
    }

    private function action_giaodien(): void
    {
        $thongBao = null;
        $loi = null;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $kq = $this->admin->luuGiaoDien($_POST);
            $thongBao = $kq['ok'] ? 'Đã lưu giao diện' : null;
            $loi = $kq['loi'] ?? null;
        }
        $data = $this->admin->trangGiaoDien();
        $this->render('admin/giaodien.php', $data + compact('thongBao', 'loi') + ['act' => 'giaodien']);
        return;
    }

    private function action_doimatkhau(): void
    {
        $thongBao = null;
        $loi = null;
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
        $this->render('admin/doimatkhau.php', compact('thongBao', 'loi') + ['act' => 'doimatkhau']);
        return;
    }

    private function unknownAction(): void
    {
        $this->json(['ok' => false, 'loi' => 'Khong ro act admin', 'act' => $this->act], 400);
    }
}
