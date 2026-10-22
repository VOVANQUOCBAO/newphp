<?php

class controller
{
    public function render(string $view, array $data = []): void
    {
        if ($view === 'thanhtoan.php' && !empty($data['user']) && empty($data['ok'])) {
            $data['bankAvailable'] = thanhtoan_qr::ready() && thanhtoan_qr::schemaReady();
            $data['codCheckoutKey'] = $_SESSION['cod_checkout_key'] ?? '';
            $code = trim((string)($_POST['ma_voucher'] ?? ''));
            $cart = $data['giohang'];
            $shipping = in_array($data['user']['hang'], ['Gold','Diamond'], true) ? 0.0 : donhang::PHI_SHIP_MAC_DINH;
            $data['voucherQuote'] = $code === '' ? ['ok' => true, 'giam' => 0, 'phi_ship' => $shipping, 'tong' => $cart['tong'] + $shipping]
                : (new voucher())->kiemTra($code, $data['user']['hang'], (float)$cart['tong'], $shipping, false, $cart['items']);
            $data['availableVouchers'] = array_values(array_filter((new voucher())->layDS(),static fn($v)=>(int)$v['so_luong']>0 && date('Y-m-d')>=$v['ngay_bd'] && date('Y-m-d')<=$v['ngay_kt']));
            usort($data['availableVouchers'],static fn($a,$b)=>[$a['ngay_kt'],$a['id']]<=>[$b['ngay_kt'],$b['id']]);
            foreach ($data['availableVouchers'] as &$vc) {
                $quote=(new voucher())->kiemTra($vc['ma_code'],$data['user']['hang'],(float)$cart['tong'],$shipping,false,$cart['items']);
                $vc['pham_vi_label']=voucher::scopeLabel($vc);
                $vc['du_dieu_kien']=$quote['ok']; $vc['ly_do']=$quote['loi'] ?? null;
            }
            unset($vc);
        }
        if (!headers_sent()) header('Cache-Control: no-store, private');
        $data['userCsrf'] = $this->userCsrf();
        if (str_starts_with(ltrim($view, '/'), 'admin/')) {
            $data['csrfToken'] = $this->csrfToken();
            $role = (string) ($_SESSION['admin']['vai_tro'] ?? '');
            $data['adminRole'] = $role;
            $data['isSuperAdmin'] = in_array($role, ['Admin', 'SuperAdmin'], true);
            $data['isManager'] = $role === 'QuanLy';
            $data['isEmployee'] = $role === 'NhanVien';
        }

        // 1. Kiểm tra nếu là request kiểm thử / API JSON
        $isJson = (
            (isset($_GET['format']) && $_GET['format'] === 'json') ||
            (isset($_GET['ajax']) && $_GET['ajax'] == '1') ||
            (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            empty($_SERVER['HTTP_USER_AGENT'])
        );

        if ($isJson) {
            $status = http_response_code();
            $this->json($data, $status >= 100 ? $status : 200);
            return;
        }

        // 2. Render HTML cho trình duyệt (Web Browser)
        $path = BASE_PATH . '/view/' . ltrim($view, '/');
        if (is_file($path)) {
            $e = static fn(mixed $value): string => htmlspecialchars(
                (string) ($value ?? ''),
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );
            extract($data, EXTR_SKIP);

            // View Admin tự nhúng header_admin.php
            if (str_starts_with(ltrim($view, '/'), 'admin/')) {
                require $path;
                if ($view !== 'admin/dangnhap_admin.php') echo '</div></main><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script></body></html>';
                return;
            }

            // View Khách hàng: bọc bởi header, menu và footer
            $header = BASE_PATH . '/view/header.php';
            $menu = BASE_PATH . '/view/menu.php';
            $footer = BASE_PATH . '/view/footer.php';

            if (is_file($header)) {
                require $header;
            }
            if (is_file($menu)) {
                require $menu;
            }
            require $path;
            if (is_file($footer)) {
                require $footer;
            }
            return;
        }

        $this->json($data);
    }

    public function json(array $data, int $code = 200): void
    {
        if (!headers_sent()) {
            http_response_code($code);
            header('Content-Type: application/json; charset=utf-8');
        }
        $clean = function ($value) use (&$clean) {
            if (!is_array($value)) return $value;
            $result = [];
            foreach ($value as $key => $item) {
                if (is_string($key) && preg_match('/(?:mat_khau|password|secret|google_sub|credential|access_token|refresh_token)/i', $key)) continue;
                $result[$key] = $clean($item);
            }
            return $result;
        };
        echo json_encode($clean($data), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function redirect(string $act, array $query = [], string $khu = ''): void
    {
        $query['act'] = $act;
        if ($khu !== '') {
            $query['khu'] = $khu;
        }
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $base = preg_replace('~/(?:controller/(?:admin/)?)?index\.php$~', '', $script);
        $target = $base . '/index.php';
        $url = $target . '?' . http_build_query($query);
        if (!headers_sent()) {
            header('Location: ' . $url);
            exit;
        }
        echo json_encode(['redirect' => $url], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function act(): string
    {
        $act = $_GET['act'] ?? $_GET['case'] ?? $_POST['act'] ?? 'trangchu';
        return is_string($act) && $act !== '' ? $act : 'trangchu';
    }

    public function requireUser(): ?array
    {
        if (empty($_SESSION['user']['id'])) {
            return null;
        }
        require_once dirname(__DIR__) . '/model/khachhang.php';
        $user = (new khachhang())->layTheoId((int) $_SESSION['user']['id']);
        if ($user === null || (int) $user['trang_thai'] !== 1) {
            unset($_SESSION['user'], $_SESSION['giohang'], $_SESSION['mua_ngay'], $_SESSION['checkout_source'], $_SESSION['cod_checkout_key'], $_SESSION['paypal_checkout_key']);
            return null;
        }
        unset($user['mat_khau']);
        if (!isset($user['hoten']) && isset($user['ho_ten'])) {
            $user['hoten'] = $user['ho_ten'];
        }
        $_SESSION['user'] = $user;
        return $user;
    }

    public function requireAdmin(): ?array
    {
        if (empty($_SESSION['admin']['id'])) {
            unset($_SESSION['admin']);
            return null;
        }

        require_once BASE_PATH . '/model/nhanvien.php';
        $admin = (new nhanvien())->layTheoId((int) $_SESSION['admin']['id']);
        if ($admin === null
            || (int) $admin['trang_thai'] !== 1
            || !in_array($admin['vai_tro'], ['Admin', 'SuperAdmin', 'QuanLy', 'NhanVien'], true)) {
            unset($_SESSION['admin'], $_SESSION['admin_csrf']);
            return null;
        }

        unset($admin['mat_khau']);
        $admin['hoten'] = $admin['ho_ten'];
        $_SESSION['admin'] = $admin;
        return $_SESSION['admin'];
    }

    public function canAccessAdminAction(string $action, string $role): bool
    {
        if (in_array($role, ['Admin', 'SuperAdmin'], true)) {
            return true;
        }

        $allowedActions = match ($role) {
            'QuanLy' => ['sanpham', 'donhang', 'chitietdonhang', 'khachhang', 'giaodien', 'doimatkhau'],
            'NhanVien' => ['sanpham', 'doimatkhau'],
            default => [],
        };

        return in_array($action, $allowedActions, true);
    }

    public function userCsrf(): string
    {
        if (!is_string($_SESSION['user_csrf'] ?? null)) $_SESSION['user_csrf'] = bin2hex(random_bytes(32));
        return $_SESSION['user_csrf'];
    }

    public function verifyUserCsrf(mixed $token): bool
    {
        return is_string($token) && hash_equals($this->userCsrf(), $token);
    }

    public function csrfToken(): string
    {
        if (!isset($_SESSION['admin_csrf']) || !is_string($_SESSION['admin_csrf'])) {
            $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['admin_csrf'];
    }

    public function verifyCsrfToken(mixed $token): bool
    {
        return is_string($token)
            && isset($_SESSION['admin_csrf'])
            && is_string($_SESSION['admin_csrf'])
            && hash_equals($_SESSION['admin_csrf'], $token);
    }
}
