<?php

require_once BASE_PATH . '/controller/controller.php';
require_once BASE_PATH . '/view/helpers.php';
require_once BASE_PATH . '/controller/giohang.php';
require_once BASE_PATH . '/controller/xuly_email.php';
require_once BASE_PATH . '/model/danhmuc.php';
require_once BASE_PATH . '/model/sanpham.php';
require_once BASE_PATH . '/model/khachhang.php';
require_once BASE_PATH . '/model/nhanvien.php';
require_once BASE_PATH . '/model/danhgia.php';
require_once BASE_PATH . '/model/donhang.php';
require_once BASE_PATH . '/model/chitietdonhang.php';
require_once BASE_PATH . '/model/voucher.php';
require_once BASE_PATH . '/model/khuyenmai.php';
require_once BASE_PATH . '/model/email.php';
require_once BASE_PATH . '/model/yeuthich.php';
require_once BASE_PATH . '/controller/paypal.php';
require_once BASE_PATH . '/controller/bank.php';
require_once BASE_PATH . '/model/thanhtoan.php';


/** Nhận yêu cầu, gọi model và chuyển dữ liệu cho view. */
final class storefront_controller extends controller
{
    // Thuộc tính dùng chung trong một yêu cầu HTTP.
    private string $act;
    private danhmuc $dmModel;
    private sanpham $spModel;
    private khachhang $khModel;
    private nhanvien $nvModel;
    private danhgia $dgModel;
    private donhang $dhModel;
    private khuyenmai $kmModel;
    private email $emModel;
    private string $googleClientId;
    private string $googleLoginUri;
    private string $googleCsrfToken;
    private string $googleNonce;

    public function render(string $view, array $data = []): void
    {
        if ($view === 'thanhtoan.php') $data = (new thanhtoan())->duLieuHienThi($data);
        parent::render($view, $data);
    }

    public function run(): void
    {
        $this->act = $this->act();
        $googleConfigPath = BASE_PATH . '/support/config/google.local.php';
        $googleConfig = is_file($googleConfigPath) ? require $googleConfigPath : [];
        $googleConfig = is_array($googleConfig) ? $googleConfig : [];
        $this->googleClientId = trim((string) (getenv('GOOGLE_CLIENT_ID') ?: ($googleConfig['client_id'] ?? '')));
        $appUrl = rtrim((string) (getenv('APP_URL') ?: ($googleConfig['app_url'] ?? '')), '/');
        if ($appUrl === '' && isset($_SERVER['HTTP_HOST'])) {
            $scheme = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
            $appUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
        }
        $this->googleLoginUri = $appUrl . '/controller/index.php?act=google_login';
        $this->googleCsrfToken = '';
        $this->googleNonce = '';
        if ($this->googleClientId !== '') {
            $googleState = $_SESSION['google_signin'] ?? null;
            if (!is_array($googleState) || !is_string($googleState['csrf'] ?? null)
                || !is_string($googleState['nonce'] ?? null)
                || (time() - (int) ($googleState['created_at'] ?? 0)) > 900) {
                $googleState = ['csrf' => bin2hex(random_bytes(32)), 'nonce' => bin2hex(random_bytes(32)), 'created_at' => time()];
                $_SESSION['google_signin'] = $googleState;
            }
            $this->googleCsrfToken = $googleState['csrf'];
            $this->googleNonce = $googleState['nonce'];
            if (in_array($this->act, ['dangnhap', 'dangky'], true)) {
                header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
            }
        }

        try {
            $mutations = ['dangnhap', 'dangky', 'dangxuat', 'themgiohang', 'muangay', 'suagiohang', 'xoagiohang', 'themdanhgia', 'huydonhang', 'khieunai', 'yeucautra', 'baochuyenkhoan','luuhoso','luuyeuthich'];
            if (in_array($this->act, $mutations, true) && !(in_array($this->act, ['dangnhap', 'dangky'], true) && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET')) {
                if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { $this->json(['ok' => false, 'loi' => 'Thao tác phải gửi bằng POST'], 405); return; }
                if (!$this->verifyUserCsrf($_POST['_csrf'] ?? null)) { $this->json(['ok' => false, 'loi' => 'Phiên biểu mẫu hết hạn. Hãy tải lại trang.'], 403); return; }
            }
            $this->validateInputs(true);
            $this->dmModel = new danhmuc();
            $this->spModel = new sanpham();
            $this->khModel = new khachhang();
            $this->nvModel = new nhanvien();
            $this->dgModel = new danhgia();
            $this->dhModel = new donhang();
            $this->kmModel = new khuyenmai();
            $this->emModel = new email();
            if (in_array($this->act,['trangchu','timkiem','chitiet','thanhtoan','lichsumuahang'],true)) { $this->dhModel->donPayPalHetHan(); $this->dhModel->donQRHetHan(); }
            if (!empty($_SESSION['user'])) $this->requireUser();

            switch ($this->act) {
                case 'dangky':
                    $this->action_dangky();
                    break;
                case 'dangnhap':
                    $this->action_dangnhap();
                    break;
                case 'google_login':
                    $this->action_google_login();
                    break;
                case 'dangxuat':
                    $this->action_dangxuat();
                    break;
                case 'hoso':
                case 'luuhoso':
                    $this->action_hoso();
                    break;
                case 'diemthanhvien':
                    $this->action_diemthanhvien();
                    break;
                case 'yeuthich':
                case 'luuyeuthich':
                    $this->action_yeuthich();
                    break;
                case 'timkiem':
                case 'trangchu':
                    $this->action_timkiem();
                    break;
                case 'chitiet':
                    $this->action_chitiet();
                    break;
                case 'themdanhgia':
                    $this->action_themdanhgia();
                    break;
                case 'giohang':
                    $this->action_giohang();
                    break;
                case 'muangay':
                case 'themgiohang':
                    $this->action_muangay();
                    break;
                case 'xoagiohang':
                    $this->action_xoagiohang();
                    break;
                case 'suagiohang':
                    $this->action_suagiohang();
                    break;
                case 'paypal':
                case 'paypal_create':
                case 'paypal_return':
                case 'paypal_cancel':
                case 'paypal_capture':
                case 'paypal_check':
                case 'paypal_abandon':
                    $this->action_paypal();
                    break;
                case 'bank':
                case 'baochuyenkhoan':
                    $this->action_bank();
                    break;
                case 'thanhtoan':
                    $this->action_thanhtoan();
                    break;
                case 'lichsumuahang':
                    $this->action_lichsumuahang();
                    break;
                case 'yeucautra':
                    $this->action_yeucautra();
                    break;
                case 'huydonhang':
                    $this->action_huydonhang();
                    break;
                case 'khieunai':
                    $this->action_khieunai();
                    break;
                case 'theodoidonhang':
                    $this->action_theodoidonhang();
                    break;
                case 'hopthu':
                    $this->action_hopthu();
                    break;
                case 'chinhsach':
                    $this->action_chinhsach();
                    break;
                case 'admin':
                    $this->action_admin();
                    break;
                default:
                    $this->unknownAction();
                    break;
            }
        } catch (paypal_error $e) {
            $this->json(['ok' => false, 'loi' => $e->getMessage()], $e->reason === 'csrf' ? 403 : 400);
        } catch (InvalidArgumentException | TypeError $e) {
            $this->json(['ok' => false, 'loi' => 'Du lieu gui len khong hop le'], 400);
        } catch (Throwable $e) {
            error_log('Controller User: ' . $e->getMessage());
            $this->json(['ok' => false, 'loi' => 'Khong the xu ly yeu cau. Vui long kiem tra ket noi CSDL.'], 500);
        }
    }

    private function action_dangky(): void
    {
        $thongBao = null;
        $loi = null;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $payload = $_POST;
            $action = $payload['action'] ?? 'dangky';
            if (!isset($payload['ho_ten']) && isset($payload['hoten'])) {
                $payload['ho_ten'] = $payload['hoten'];
            }
            if (!isset($payload['mat_khau']) && isset($payload['password'])) {
                $payload['mat_khau'] = $payload['password'];
            }
            if (!isset($payload['ngay_sinh']) && isset($payload['ngaysinh'])) {
                $payload['ngay_sinh'] = $payload['ngaysinh'];
            }

            $email = trim((string)($payload['email'] ?? ''));
            $hoTen = trim((string)($payload['ho_ten'] ?? ''));
            $matKhau = (string)($payload['mat_khau'] ?? '');
            $rePass = (string)($payload['re_password'] ?? $matKhau);

            if ($matKhau !== $rePass) {
                $loi = 'Mật khẩu xác nhận không trùng khớp';
            } elseif ($action === 'gui_otp') {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $loi = 'Địa chỉ email không hợp lệ';
                } elseif ($this->khModel->layTheoEmail($email) !== null) {
                    $loi = 'Email này đã tồn tại trong hệ thống. Vui lòng đăng nhập hoặc dùng email khác';
                } else {
                    $otp = (string)random_int(100000, 999999);
                    $mailRes = (new xuly_email())->guiOtp($email, $otp, $hoTen);
                    if ($mailRes['ok']) {
                        $_SESSION['dangky_pending'] = [
                            'otp' => $otp,
                            'time' => time(),
                            'payload' => $payload,
                        ];
                        $thongBao = "Mã xác thực OTP đã được gửi đến hộp thư Gmail <strong>" . htmlspecialchars($email) . "</strong>! Vui lòng kiểm tra Hộp thư đến (hoặc Thư rác/Spam) để lấy mã.";
                    } else {
                        unset($_SESSION['dangky_pending']);
                        $loi = 'Không thể gửi mã xác thực đến email. Vui lòng thử lại sau hoặc đăng ký bằng Google.';
                    }
                }
            } elseif ($action === 'xacnhan_otp') {
                // Bước 2: Xác nhận mã OTP
                $nhapOtp = trim((string)($payload['otp'] ?? ''));
                $pending = $_SESSION['dangky_pending'] ?? null;
                if ($pending === null || (time() - $pending['time']) > 900) {
                    $loi = 'Mã xác thực OTP đã hết hạn (15 phút). Vui lòng yêu cầu gửi mã mới.';
                } elseif ($nhapOtp !== $pending['otp']) {
                    $loi = 'Mã xác thực OTP không chính xác. Vui lòng kiểm tra lại.';
                } else {
                    $kq = $this->khModel->themKhachHang($pending['payload']);
                    if ($kq['ok']) {
                        unset($_SESSION['dangky_pending']);
                        (new xuly_email())->guiChaoMung($pending['payload']['email'], $pending['payload']['ho_ten'] ?? '');
                        $thongBao = 'Đăng ký thành công tài khoản Gmail chính hãng! Vui lòng đăng nhập.';
                        $this->render('dangnhap.php', compact('thongBao', 'loi') + ['act' => 'dangnhap']);
                        return;
                    }
                    $loi = $kq['loi'];
                }
            } else {
                // Đăng ký trực tiếp (1 bước)
                $kq = $this->khModel->themKhachHang($payload);
                if ($kq['ok']) {
                    (new xuly_email())->guiChaoMung($payload['email'], $payload['ho_ten'] ?? '');
                    $thongBao = 'Đăng ký thành công. Vui lòng đăng nhập.';
                    $this->render('dangnhap.php', compact('thongBao', 'loi') + ['act' => 'dangnhap']);
                    return;
                }
                $loi = $kq['loi'];
            }
        }
        $this->render('dangky.php', ['thongBao' => $thongBao, 'loi' => $loi, 'googleClientId' => $this->googleClientId, 'googleLoginUri' => $this->googleLoginUri, 'googleCsrfToken' => $this->googleCsrfToken, 'googleNonce' => $this->googleNonce] + ['act' => $this->act]);
        return;
    }

    private function action_dangnhap(): void
    {
        $thongBao = null;
        $loi = null;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $email = trim((string)($_POST['email'] ?? $_POST['username'] ?? ''));
            $pass = (string)($_POST['mat_khau'] ?? $_POST['password'] ?? '');

            if ($email === '' || $pass === '') {
                $loi = 'Vui lòng nhập đầy đủ tên đăng nhập/email và mật khẩu.';
            } else {
                // 1. Kiểm tra tài khoản Quản trị / Nhân viên trước (Đăng nhập chung giao diện)
                $kqAdmin = $this->nvModel->kiemTraDangNhap($email, $pass);
                if ($kqAdmin['ok'] && in_array($kqAdmin['nhanvien']['vai_tro'] ?? '', [
                    nhanvien::ROLE_SUPER_ADMIN,
                    nhanvien::ROLE_MANAGER,
                    nhanvien::ROLE_EMPLOYEE,
                    'Admin',
                ], true)) {
                    session_regenerate_id(true);
                    $nv = $kqAdmin['nhanvien'];
                    $nv['hoten'] = $nv['ho_ten'] ?? 'Quản Trị Viên';
                    unset($nv['mat_khau'], $_SESSION['user'], $_SESSION['admin_csrf']);
                    $_SESSION['admin'] = $nv;
                    $this->redirect('sanpham', [], 'admin');
                    return;
                }

                // 2. Kiểm tra tài khoản Khách hàng
                $kqKhach = $this->khModel->kiemTraDangNhap($email, $pass);
                if ($kqKhach['ok']) {
                    session_regenerate_id(true);
                    $u = $kqKhach['khachhang'];
                    $u['hoten'] = $u['ho_ten'] ?? '';
                    unset($u['mat_khau'], $_SESSION['admin']);
                    $_SESSION['user'] = $u;
                    $this->redirect('trangchu');
                    return;
                }

                $loi = 'Tài khoản hoặc mật khẩu không chính xác.';
            }
        }
        if (isset($_GET['loi_admin'])) {
            $loi = 'Khu vực này yêu cầu đăng nhập bằng tài khoản Quản trị viên.';
        }
        if (isset($_GET['msg']) && $_GET['msg'] === 'registered') {
            $thongBao = 'Đăng ký tài khoản thành công! Vui lòng đăng nhập để tiếp tục.';
        }
        if (isset($_GET['google_error'])) {
            $loi = 'Không thể xác thực tài khoản Google. Vui lòng thử lại hoặc đăng nhập bằng mật khẩu.';
        }
        $this->render('dangnhap.php', ['thongBao' => $thongBao, 'loi' => $loi, 'googleClientId' => $this->googleClientId, 'googleLoginUri' => $this->googleLoginUri, 'googleCsrfToken' => $this->googleCsrfToken, 'googleNonce' => $this->googleNonce] + ['act' => $this->act]);
        return;
    }

    private function action_google_login(): void
    {
        $csrfCookie = $_COOKIE['g_csrf_token'] ?? '';
        $csrfPost = $_POST['g_csrf_token'] ?? '';
        $googlePopup = array_key_exists('_google_csrf', $_POST);
        $googleRequestError = null;
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $googleRequestError = 'request';
        } elseif ($this->googleClientId === '') {
            $googleRequestError = 'configuration';
        } elseif ($googlePopup
            ? (!is_string($_POST['_google_csrf']) || $this->googleCsrfToken === ''
                || !hash_equals($this->googleCsrfToken, $_POST['_google_csrf']))
            : (!is_string($csrfCookie) || !is_string($csrfPost)
                || $csrfCookie === '' || !hash_equals($csrfCookie, $csrfPost))) {
            $googleRequestError = 'csrf';
        } elseif (!is_string($_POST['credential'] ?? null) || $_POST['credential'] === '') {
            $googleRequestError = 'credential';
        }
        if ($googleRequestError !== null) {
            $this->redirect('dangnhap', ['google_error' => $googleRequestError]);
        }

        $googleAutoload = BASE_PATH . '/support/vendor/autoload.php';
        if (!is_file($googleAutoload)) {
            error_log('Google sign-in is configured but Google API Client is not installed');
            $this->redirect('dangnhap', ['google_error' => 'dependency']);
        }
        require_once $googleAutoload;
        if (!class_exists(\Google\Client::class)) {
            error_log('Google sign-in requires google/apiclient');
            $this->redirect('dangnhap', ['google_error' => 'dependency']);
        }
        $googleStage = 'token';
        try {
            $googleClient = new \Google\Client(['client_id' => $this->googleClientId]);
            $claims = $googleClient->verifyIdToken($_POST['credential']);
            $googleStage = 'claims';
            if (!is_array($claims)
                || !isset($claims['sub'], $claims['email'])
                || !filter_var($claims['email'], FILTER_VALIDATE_EMAIL)
                || !filter_var($claims['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                throw new RuntimeException('Google ID token did not contain verified account claims');
            }
            if ($googlePopup && (!is_string($claims['nonce'] ?? null)
                || $this->googleNonce === '' || !hash_equals($this->googleNonce, $claims['nonce']))) {
                throw new RuntimeException('Google ID token nonce did not match the sign-in session');
            }
            $googleStage = 'account';
            $kqGoogle = $this->khModel->dangNhapGoogle(
                (string) $claims['sub'],
                (string) $claims['email'],
                (string) ($claims['name'] ?? '')
            );
            if (!$kqGoogle['ok']) {
                throw new RuntimeException((string) $kqGoogle['loi']);
            }
            session_regenerate_id(true);
            unset($_SESSION['google_signin']);
            $u = $kqGoogle['khachhang'];
            $u['hoten'] = $u['ho_ten'] ?? '';
            $_SESSION['user'] = $u;
            if (!empty($kqGoogle['created'])) {
                try {
                    (new xuly_email())->guiChaoMung((string) $u['email'], (string) $u['ho_ten']);
                } catch (Throwable $e) {
                    error_log('Google account welcome email: ' . $e->getMessage());
                }
            }
            $this->redirect('trangchu');
        } catch (Throwable $e) {
            error_log('Google sign-in verification: ' . $e->getMessage());
            // Only a fixed stage code reaches the URL; tokens and exception details stay private.
            $googleFailure = $googleStage;
            if ($googleStage === 'token' && $e instanceof \GuzzleHttp\Exception\TransferException) {
                $googleFailure = 'connection';
            } elseif ($e instanceof PDOException && $e->getCode() === '42S22') {
                $googleFailure = 'database_schema';
            }
            $this->redirect('dangnhap', ['google_error' => $googleFailure]);
        }
        return;
    }

    private function action_dangxuat(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        $this->redirect('trangchu');
        return;
    }

    private function action_hoso(): void
    {
        $thongBao = null;
        $loi = null;
        $user=$this->requireUser();
        if ($user===null) $this->redirect('dangnhap');
        if ($this->act==='luuhoso') {
            $result=$this->khModel->luuHoSo((int)$user['id'],$_POST);
            $loi=$result['loi'] ?? null;
            $thongBao=$result['ok'] ? 'Đã lưu hồ sơ. Thông tin này sẽ được điền sẵn khi thanh toán.' : null;
            $user=$this->requireUser();
        }
        $this->render('hoso.php',compact('user', 'loi', 'thongBao')+['act'=>'hoso']);
        return;
    }

    private function action_diemthanhvien(): void
    {
        $user=$this->requireUser();
        if ($user===null) $this->redirect('dangnhap');
        require_once BASE_PATH.'/model/diem_thanhvien.php';
        $bao_cao=(new diem_thanhvien())->baoCao((int)$user['id'], (int)($_GET['trang'] ?? 1));
        $this->render('diem_thanhvien.php',compact('bao_cao')+['act'=>'diemthanhvien']);
        return;
    }

    private function action_yeuthich(): void
    {
        $thongBao = null;
        $loi = null;
        $user=$this->requireUser();
        if ($user===null) $this->redirect('dangnhap');
        if ($this->act==='luuyeuthich') {
            if (!in_array($_POST['luu'] ?? '',['0','1'],true)) throw new InvalidArgumentException('Trạng thái yêu thích không hợp lệ.');
            $result=(new yeuthich())->dat((int)$user['id'],(int)($_POST['id_sanpham'] ?? 0),$_POST['luu']==='1');
            $loi=$result['loi'] ?? null;
            $thongBao=$result['ok'] ? ($_POST['luu']==='1'?'Đã thêm vào yêu thích.':'Đã bỏ khỏi yêu thích.') : null;
        }
        $products=$this->spModel->boSungDanhSach((new yeuthich())->layDS((int)$user['id']));
        $this->render('yeuthich.php',compact('products', 'loi', 'thongBao')+['act'=>'yeuthich']);
        return;
    }

    private function action_timkiem(): void
    {
        $thongBao = null;
        $loi = null;
        $tuKhoa = isset($_GET['q']) ? trim((string) $_GET['q']) : (isset($_GET['keyword']) ? trim((string) $_GET['keyword']) : '');
        $idDm = isset($_GET['id_danhmuc']) ? (int) $_GET['id_danhmuc'] : 0;
        $sapXep = ($_GET['sapxep'] ?? $_GET['sort'] ?? 'moi') === 'cu' ? 'cu' : 'moi';
        $trang = max(1, min(1000000, (int) ($_GET['trang'] ?? 1)));
        $moi_trang = max(1, min(100, (int) ($_GET['moi_trang'] ?? 8)));
        $tong_sanpham = $this->spModel->demTimKiem($tuKhoa !== '' ? $tuKhoa : null, $idDm > 0 ? $idDm : null);
        $tong_trang = max(1, (int) ceil($tong_sanpham / $moi_trang));
        $trang = min($trang, $tong_trang);
        $con_tiep = $trang < $tong_trang;
        $ds_danhmuc = $this->dmModel->layDS();
        $ds_sanpham = $this->spModel->timKiem($tuKhoa !== '' ? $tuKhoa : null, $idDm > 0 ? $idDm : null,
            $sapXep, $moi_trang, ($trang - 1) * $moi_trang);
        $sp_moi = $this->spModel->layDSMoi();
        $sp_km = $this->spModel->layDSKhuyenMai();
        $seoProduct = null;
        $seoAccessoryProducts = [];
        $seoMetaTitle = null;
        $seoMetaDescription = null;
        $seoProductSchema = null;
        if ($tuKhoa === '' && $idDm === 0 && $trang === 1) {
            $findNamedProduct = function (string $keyword, callable $matches): ?array {
                foreach ($this->spModel->timKiem($keyword, null, 'moi', 30, 0) as $candidate) {
                    if ($matches((string)($candidate['ten_sp'] ?? ''))) return $candidate;
                }
                return null;
            };
            $seoProduct = $findNamedProduct('iPhone 14 128GB', static fn(string $name): bool =>
                preg_match('/\biPhone\s*14\b/i', $name) === 1 && preg_match('/128\s*GB/i', $name) === 1);
            $seoAccessoryProducts = array_values(array_filter([
                $findNamedProduct('20', static fn(string $name): bool => preg_match('/\b20\s*W\b/i', $name) === 1),
                $findNamedProduct('AirPods 4', static fn(string $name): bool => preg_match('/AirPods\s*4/i', $name) === 1),
            ]));
            $seoDetails = array_values(array_filter(array_merge($seoProduct ? [$seoProduct] : [], $seoAccessoryProducts)));
            $seoDetails = $this->spModel->boSungDanhSach($seoDetails);
            $seoDetailsById = array_column($seoDetails, null, 'id');
            $seoProduct = $seoProduct ? ($seoDetailsById[$seoProduct['id']] ?? null) : null;
            $seoAccessoryProducts = array_values(array_filter(array_map(
                static fn(array $item): ?array => $seoDetailsById[$item['id']] ?? null,
                $seoAccessoryProducts
            )));
            if ($seoProduct !== null) {
                $seoOriginalPrice = (float)($seoProduct['gia_goc'] ?? $seoProduct['gia']);
                $seoSalePrice = (float)($seoProduct['gia_km'] ?? $seoProduct['gia']);
                $seoDiscountPercent = $seoOriginalPrice > $seoSalePrice
                    ? (int)round(($seoOriginalPrice - $seoSalePrice) / $seoOriginalPrice * 100) : 0;
                $seoMetaTitle = 'iPhone 14 128GB Xanh dương' . ($seoDiscountPercent > 0 ? ' giảm ' . $seoDiscountPercent . '%' : '') . ' | BP Store';
                $seoAvailability = (int)($seoProduct['so_luong_ton'] ?? 0) > 0 ? 'Đang có hàng' : 'Tạm hết hàng';
                $seoMetaDescription = 'Xem thông tin iPhone 14 128GB Xanh dương tại BP Store. Giá hiện tại '
                    . number_format($seoSalePrice, 0, ',', '.') . 'đ, ' . $seoAvailability . '. Chi tiết cấu hình và phụ kiện phù hợp.';
                $requestScheme = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
                $siteOrigin = $requestScheme . '://' . (string)($_SERVER['HTTP_HOST'] ?? 'bpstore.gt.tc');
                $productUrl = $siteOrigin . view_helper::shopBase() . '/controller/index.php?act=chitiet&id=' . (int)$seoProduct['id'];
                $seoProductSchema = [
                    '@context' => 'https://schema.org',
                    '@type' => 'Product',
                    'name' => (string)$seoProduct['ten_sp'],
                    'description' => $seoMetaDescription,
                    'sku' => (string)($seoProduct['ma_sp'] ?? ''),
                    'image' => $siteOrigin . view_helper::productImage((string)($seoProduct['hinh'] ?? '')),
                    'url' => $productUrl,
                    'brand' => ['@type' => 'Brand', 'name' => trim((string)($seoProduct['thuong_hieu'] ?? '')) ?: 'Apple'],
                    'offers' => [
                        '@type' => 'Offer',
                        'priceCurrency' => 'VND',
                        'price' => number_format($seoSalePrice, 0, '.', ''),
                        'availability' => (int)($seoProduct['so_luong_ton'] ?? 0) > 0
                            ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                        'url' => $productUrl,
                    ],
                ];
            }
        }
        $allProducts = $this->spModel->boSungDanhSach(array_merge($ds_sanpham,$sp_moi,$sp_km));
        $byId = array_column($allProducts,null,'id');
        $favoriteIds=!empty($_SESSION['user']['id']) ? array_fill_keys(array_column((new yeuthich())->layDS((int)$_SESSION['user']['id']),'id'),true) : [];
        foreach ($byId as &$product) $product['yeu_thich']=isset($favoriteIds[$product['id']]);
        unset($product);
        foreach (['ds_sanpham','sp_moi','sp_km'] as $listName) {
            ${$listName} = array_map(static fn($item) => $byId[$item['id']], ${$listName});
        }
        $this->render('trangchu.php', compact('ds_danhmuc', 'ds_sanpham', 'sp_moi', 'sp_km', 'tuKhoa', 'idDm', 'sapXep', 'trang', 'moi_trang', 'tong_sanpham', 'tong_trang', 'con_tiep', 'thongBao', 'loi') + ['act' => $this->act, 'seoProduct' => $seoProduct, 'seoAccessoryProducts' => $seoAccessoryProducts,
            'seoMetaTitle' => $seoMetaTitle, 'seoMetaDescription' => $seoMetaDescription,
            'seoProductSchema' => $seoProductSchema]);
        return;
    }

    private function action_chitiet(): void
    {
        $thongBao = null;
        $loi = null;
        $id = (int) ($_GET['id'] ?? $_GET['id_sp'] ?? 0);
        $chitiet = $this->spModel->layChiTiet($id);
        if ($chitiet === null) {
            $this->json(['ok' => false, 'loi' => 'Khong tim thay san pham'], 404);
            return;
        }
        $giaKm = $this->kmModel->giaSauKM($chitiet);
        $chitiet['gia_goc'] = $giaKm['gia_goc'];
        $chitiet['gia_km'] = $giaKm['gia_km'];
        $chitiet['het_hang'] = (int) $chitiet['so_luong_ton'] <= 0;
        $ds_lienquan = $this->spModel->lienQuan($id, (int)$chitiet['id_danhmuc']);
        foreach ($ds_lienquan as &$related) $related = array_merge($related, $this->kmModel->giaSauKM($related));
        unset($related);
        $reviewUser = $this->requireUser();
        $isFavorite = $reviewUser !== null && (new yeuthich())->daLuu((int) $reviewUser['id'], $id);
        $duocDanhGia = $reviewUser !== null && $this->dgModel->duocDanhGia($id, (int)$reviewUser['id']);
        $ds_danhgia = $this->dgModel->layDSTheoSP($id);
        $this->render('chitiet_sanpham.php', compact('chitiet', 'ds_danhgia', 'thongBao', 'loi', 'ds_lienquan', 'duocDanhGia', 'isFavorite') + ['act' => $this->act]);
        return;
    }

    private function action_themdanhgia(): void
    {
        $thongBao = null;
        $loi = null;
        $user = $this->requireUser();
        $idSp = (int) ($_POST['id_sanpham'] ?? $_GET['id_sanpham'] ?? $_POST['id_sp'] ?? $_GET['id_sp'] ?? 0);
        if ($user === null) {
            $this->redirect('dangnhap');
        }
        $chitiet = $this->spModel->layChiTiet($idSp);
        if ($chitiet === null) {
            $this->json(['ok' => false, 'loi' => 'Khong tim thay san pham'], 404);
            return;
        }
        $giaKm = $this->kmModel->giaSauKM($chitiet);
        $chitiet['gia_goc'] = $giaKm['gia_goc'];
        $chitiet['gia_km'] = $giaKm['gia_km'];
        $chitiet['het_hang'] = (int) $chitiet['so_luong_ton'] <= 0;
        $soSao = (int) ($_POST['so_sao'] ?? $_POST['sao'] ?? 0);
        $noiDung = (string) ($_POST['noi_dung'] ?? $_POST['noidung'] ?? '');
        $kq = $this->dgModel->them($idSp, (int) $user['id'], $soSao, $noiDung);
        if (!$kq['ok']) {
            $loi = $kq['loi'];
        } else {
            $this->redirect('chitiet', ['id' => $idSp]);
        }
        $ds_lienquan = [];
        $isFavorite = (new yeuthich())->daLuu((int) $user['id'], $idSp);
        $duocDanhGia = $this->dgModel->duocDanhGia($idSp, (int)$user['id']);
        $ds_danhgia = $this->dgModel->layDSTheoSP($idSp);
        $this->render('chitiet_sanpham.php', compact('chitiet', 'ds_danhgia', 'thongBao', 'loi', 'ds_lienquan', 'duocDanhGia', 'isFavorite') + ['act' => 'chitiet']);
        return;
    }

    private function action_giohang(): void
    {
        $thongBao = null;
        $loi = null;
        $_SESSION['checkout_source'] = 'cart';
        $this->render('giohang.php', ['giohang' => giohang::chiTiet(), 'act' => $this->act, 'thongBao' => $thongBao, 'loi' => $loi]);
        return;
    }

    private function action_muangay(): void
    {
        $thongBao = null;
        $loi = null;
        $idSp = (int) ($_GET['id'] ?? $_POST['id'] ?? $_GET['id_sp'] ?? 0);
        $sl = (int) ($_GET['sl'] ?? $_POST['sl'] ?? 1);
        if (filter_var($_GET['sl'] ?? $_POST['sl'] ?? 1, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException('So luong phai la so nguyen');
        }
        if ($this->act === 'muangay') {
            $product = $this->spModel->layChiTiet($idSp);
            $kq = $product && $sl > 0 && $sl <= (int)$product['so_luong_ton'] ? ['ok' => true] : ['ok' => false, 'loi' => 'Sản phẩm không đủ tồn kho'];
            if ($kq['ok']) {
                $_SESSION['mua_ngay'] = [$idSp => $sl];
                $_SESSION['checkout_source'] = 'direct';
                $this->redirect('thanhtoan');
            }
        } else {
            $kq = giohang::them($idSp, $sl);
            $_SESSION['checkout_source'] = 'cart';
            if ($kq['ok']) {
                if (($_GET['ajax'] ?? '') === '1') { $this->json(['ok' => true, 'giohang' => giohang::chiTiet()]); return; }
                $this->redirect('chitiet', ['id' => $idSp, 'added' => 1]);
            }
        }
        $loi = $kq['ok'] ? null : $kq['loi'];
        $thongBao = $kq['ok'] ? 'Da them vao gio' : null;
        $this->render('giohang.php', ['giohang' => giohang::chiTiet(), 'act' => 'giohang', 'thongBao' => $thongBao, 'loi' => $loi]);
        return;
    }

    private function action_xoagiohang(): void
    {
        $idSp = (int) ($_GET['id'] ?? $_POST['id'] ?? $_GET['del'] ?? 0);
        giohang::xoa($idSp);
        $this->render('giohang.php', ['giohang' => giohang::chiTiet(), 'act' => 'giohang', 'thongBao' => 'Da xoa san pham', 'loi' => null]);
        return;
    }

    private function action_suagiohang(): void
    {
        $loi = null;
        $idSp = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
        $sl = (int) ($_POST['sl'] ?? $_GET['sl'] ?? 0);
        if (filter_var($_POST['sl'] ?? $_GET['sl'] ?? 0, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException('So luong phai la so nguyen');
        }
        $kq = giohang::sua($idSp, $sl);
        $loi = $kq['ok'] ? null : $kq['loi'];
        $this->render('giohang.php', ['giohang' => giohang::chiTiet(), 'act' => 'giohang', 'thongBao' => $kq['ok'] ? 'Da cap nhat gio' : null, 'loi' => $loi]);
        return;
    }

    private function action_paypal(): void
    {
        paypal_controller::route($this, $this->act);
        return;
    }

    private function action_bank(): void
    {
        bank_controller::route($this,$this->act);
        return;
    }

    private function action_thanhtoan(): void
    {
        $user = $this->requireUser();
        if ($user === null) $this->redirect('dangnhap');
        if (($_GET['nguon'] ?? '') === 'gio') $_SESSION['checkout_source'] = 'cart';
        if (!is_string($_SESSION['cod_checkout_key'] ?? null)) $_SESSION['cod_checkout_key'] = bin2hex(random_bytes(32));
        $paypalAvailable = paypal_config::ready(paypal_config::load()) && thanhtoan_paypal::schemaReady();
        $bankAvailable = thanhtoan_qr::ready() && thanhtoan_qr::schemaReady();
        $checkoutToken = paypal_controller::checkoutToken();
        $pendingPayPal = $_SESSION['paypal_pending_id'] ?? null;
        $gio = giohang::chiTietThanhToan();
        $checkoutData = ['giohang'=>$gio,'user'=>$user,'act'=>$this->act,'loi'=>null,'thongBao'=>null,
            'paypalAvailable'=>$paypalAvailable,'bankAvailable'=>$bankAvailable,'checkoutToken'=>$checkoutToken,'pendingPayPal'=>$pendingPayPal];
        $post = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
        if (!$post) { $this->render('thanhtoan.php',$checkoutData); return; }
        try { paypal_controller::checkPost(); }
        catch (paypal_error $e) { $checkoutData['loi']=$e->getMessage(); $this->render('thanhtoan.php',$checkoutData); return; }
        // A quote never creates an order or consumes stock/voucher, regardless of payment method.
        $intent = $_POST['intent'] ?? 'order';
        if (in_array($intent,['voucher','remove_voucher'],true)) {
            if ($intent==='remove_voucher') $_POST['ma_voucher']='';
            $this->render('thanhtoan.php',$checkoutData); return;
        }
        $method = $_POST['phuong_thuc'] ?? 'cod';
        if (!in_array($method,['cod','paypal','bank'],true) || ($method==='bank' && !$bankAvailable) || ($method==='paypal' && !$paypalAvailable)) {
            $checkoutData['loi']='Phương thức thanh toán chưa khả dụng. Vui lòng chọn lại.';
            $this->render('thanhtoan.php',$checkoutData); return;
        }
        $submittedKey = $_POST['_order_key'] ?? '';
        $existing = is_string($submittedKey) ? $this->dhModel->layTheoPhienDatHang($submittedKey, (int) $user['id']) : null;
        if ($existing) {
            $id=(int)$existing['id'];
            if ((new thanhtoan_qr())->lay($id,(int)$user['id'])) $this->redirect('bank',['id'=>$id]);
            if ((new thanhtoan_paypal())->lay($id,(int)$user['id'])) $this->redirect('paypal',['id'=>$id]);
            $this->render('thanhtoan.php',['ok'=>true,'id_don'=>$id,'donhang'=>$existing,'loi'=>null,'giohang'=>['items'=>[],'tong'=>0]]); return;
        }
        if (!is_string($submittedKey) || !preg_match('/^[a-f0-9]{64}$/D',$submittedKey) || !hash_equals($_SESSION['cod_checkout_key'],$submittedKey)) {
            http_response_code(409); $checkoutData['loi']='Phiên đặt đơn đã thay đổi. Hãy kiểm tra lại thông tin rồi đặt hàng.';
            $this->render('thanhtoan.php',$checkoutData); return;
        }
        if ($method==='paypal') {
            $_SESSION['paypal_checkout_key']=$submittedKey;
            paypal_controller::startCheckout($this,$user); return;
        }
        $info = ['dia_chi'=>$_POST['dia_chi'] ?? '', 'sdt'=>$_POST['sdt'] ?? '', 'checkout_key'=>$submittedKey];
        if ($method==='bank') $info['bank']=[];
        $kq=$this->dhModel->taoDon((int)$user['id'],giohang::gioThanhToan(),$info,$_POST['ma_voucher'] ?? null);
        if (!$kq['ok']) { $checkoutData['loi']=$kq['loi']; $this->render('thanhtoan.php',$checkoutData); return; }
        $id=(int)$kq['id'];
        $don=$this->dhModel->layTheoId($id);
        giohang::xoaDaThanhToan();
        unset($_SESSION['cod_checkout_key']);
        try { $email_result=(new xuly_email())->guiDonHang((int)$user['id'],$id,$don,$kq['dong']); }
        catch (Throwable $e) { error_log('Email don hang: '.$e->getMessage()); $email_result=['ok'=>false]; }
        if ($method==='bank') $this->redirect('bank',['id'=>$id]);
        $this->render('thanhtoan.php',['ok'=>true,'id_don'=>$id,'donhang'=>$don,'email_result'=>$email_result,'loi'=>null,'giohang'=>['items'=>[],'tong'=>0]]);
        return;
    }

    private function action_lichsumuahang(): void
    {
        $thongBao = null;
        $loi = null;
        $user = $this->requireUser();
        if ($user === null) {
            $this->redirect('dangnhap');
        }
        $ds_donhang = $this->dhModel->layTheoKhach((int) $user['id']);

        $this->render('lichsu_muahang.php', compact('ds_donhang', 'thongBao', 'loi') + ['act' => $this->act]);
        return;
    }

    private function action_yeucautra(): void
    {
        $user = $this->requireUser();
        if ($user === null) $this->redirect('dangnhap');
        $kq = $this->dhModel->yeuCauTra((int)($_POST['id'] ?? 0), (int)$user['id'], (string)($_POST['ly_do'] ?? ''));
        $this->render('lichsu_muahang.php', ['ds_donhang' => $this->dhModel->layTheoKhach((int)$user['id']), 'loi' => $kq['loi'] ?? null, 'thongBao' => $kq['ok'] ? 'Đã gửi yêu cầu. Cửa hàng sẽ kiểm tra và phản hồi tại đơn hàng.' : null, 'act' => 'lichsumuahang']);
        return;
    }

    private function action_huydonhang(): void
    {
        $thongBao = null;
        $loi = null;
        $user = $this->requireUser();
        if ($user === null) {
            $this->redirect('dangnhap');
        }
        $rawId = $_GET['id'] ?? $_POST['id'] ?? ($_POST['order_id'] ?? 0);
        $idDon = is_numeric($rawId) ? (int)$rawId : (int)preg_replace('/\D/', '', (string)$rawId);
        $lyDo = (string) ($_POST['ly_do'] ?? $_POST['lydo'] ?? 'Khach huy don');
        $kq = $this->dhModel->huyDon($idDon, (int) $user['id'], $lyDo);
        $loi = $kq['ok'] ? null : $kq['loi'];
        $thongBao = $kq['ok'] ? 'Da huy don hang' : null;
        $ds_donhang = $this->dhModel->layTheoKhach((int) $user['id']);
        $this->render('lichsu_muahang.php', compact('ds_donhang', 'thongBao', 'loi') + ['act' => 'lichsumuahang']);
        return;
    }

    private function action_khieunai(): void
    {
        $thongBao = null;
        $loi = null;
        $user = $this->requireUser();
        if ($user === null) {
            $this->redirect('dangnhap');
        }
        $rawId = $_POST['id'] ?? $_GET['id'] ?? ($_POST['order_id'] ?? 0);
        $idDon = is_numeric($rawId) ? (int)$rawId : (int)preg_replace('/\D/', '', (string)$rawId);
        $noiDung = (string) ($_POST['noi_dung'] ?? $_POST['noidung'] ?? '');
        $kq = $this->dhModel->ghiKhieuNai($idDon, (int) $user['id'], $noiDung);
        $loi = $kq['ok'] ? null : $kq['loi'];
        $thongBao = $kq['ok'] ? 'Da gui khieu nai' : null;
        $ds_donhang = $this->dhModel->layTheoKhach((int) $user['id']);
        $this->render('lichsu_muahang.php', compact('ds_donhang', 'thongBao', 'loi') + ['act' => 'lichsumuahang']);
        return;
    }

    private function action_theodoidonhang(): void
    {
        $thongBao = null;
        $loi = null;
        $user = $this->requireUser();
        if ($user === null) {
            $this->redirect('dangnhap');
        }
        $idDon = (int) ($_GET['id'] ?? 0);
        $donhang = $idDon > 0 ? $this->dhModel->layTrangThai($idDon) : null;
        if ($donhang === null || (int) $donhang['id_khachhang'] !== (int) $user['id']) {
            if ($idDon > 0) {
                $loi = 'Không tìm thấy đơn hàng của bạn.';
            }
            $donhang = null;
        }
        $this->render('theodoi_donhang.php', compact('donhang', 'thongBao', 'loi') + ['act' => $this->act]);
        return;
    }

    private function action_hopthu(): void
    {
        $thongBao = null;
        $loi = null;
        $user = $this->requireUser();
        if ($user === null) {
            $this->redirect('dangnhap');
        }
        $ds_thu = $this->emModel->layThuCuaKhach((int) $user['id']);
        $thuDangXem = null;
        $thuId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        foreach ($ds_thu as $thu) {
            if (($thuId !== false && $thuId !== null && (int) $thu['id'] === $thuId)
                || (($thuId === false || $thuId === null) && $thuDangXem === null)) {
                $thuDangXem = $thu;
                if ($thuId !== false && $thuId !== null) {
                    break;
                }
            }
        }
        if ($thuId !== false && $thuId !== null && $thuDangXem === null) {
            $loi = 'Không tìm thấy thư trong hộp thư của bạn.';
        }
        $this->render('hopthu.php', compact('ds_thu', 'thuDangXem', 'thongBao', 'loi') + ['act' => $this->act]);
        return;
    }

    private function action_chinhsach(): void
    {
        $this->render('chinhsach.php', ['act' => 'chinhsach']);
        return;
    }

    private function action_admin(): void
    {
        if (!empty($_SESSION['admin'])) {
            $this->redirect('sanpham', [], 'admin');
        } else {
            $this->redirect('dangnhap', ['loi_admin' => '1']);
        }
        return;
    }

    private function unknownAction(): void
    {
        $this->json(['ok' => false, 'loi' => 'Khong ro act', 'act' => $this->act], 400);
    }
}
