<?php

/**
 * Test tich hop Model + HTTP Controller User tren CSDL moi, tach biet.
 * Chay bang PHP XAMPP: D:\data\php\php.exe support/tests/test_luong.php
 * Khong import lai hay xoa bang cua CSDL ung dung.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Chi chay test bang PHP CLI');
}
date_default_timezone_set('Asia/Ho_Chi_Minh');
define('BASE_PATH', dirname(__DIR__, 2));
require_once BASE_PATH . '/model/donhang.php';
require_once BASE_PATH . '/model/danhmuc.php';
require_once BASE_PATH . '/model/nhanvien.php';
require_once BASE_PATH . '/model/danhgia.php';
require_once BASE_PATH . '/model/email.php';
require_once BASE_PATH . '/controller/giohang.php';
require_once BASE_PATH . '/controller/xuly_email.php';

$results = [];
$server = null;
$adminPdo = null;
$testDb = null;
$cfg = csdl::cauHinh();
$mainDb = $cfg['dbname'];
$oldEnv = [];
foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS', 'SMTP_USER', 'SMTP_PASS', 'GOOGLE_CLIENT_ID', 'APP_URL'] as $key) {
    $oldEnv[$key] = getenv($key);
}

function check(string $name, bool $ok, mixed $detail = null): void
{
    global $results;
    $results[] = ['ten' => $name, 'ok' => $ok];
    echo ($ok ? 'PASS  ' : 'FAIL  ') . $name . PHP_EOL;
    if (!$ok && $detail !== null) {
        echo '      ' . json_encode($detail, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
}

function khTest(string $email): int
{
    $r = (new khachhang())->themKhachHang(['ho_ten' => 'Khach Test', 'email' => $email,
        'mat_khau' => '123456', 'ngay_sinh' => '2000-01-01']);
    if (empty($r['ok'])) {
        throw new RuntimeException(json_encode($r));
    }
    return $r['id'];
}

function spTest(int $dm, string $ma, int $ton = 10, float $gia = 2000000): int
{
    $r = (new sanpham())->them(['ma_sp' => $ma, 'ten_sp' => 'San pham ' . $ma,
        'id_danhmuc' => $dm, 'gia' => $gia, 'so_luong_goc' => max(1, $ton), 'so_luong_ton' => $ton]);
    if (empty($r['ok'])) {
        throw new RuntimeException(json_encode($r));
    }
    return $r['id'];
}

function vcTest(string $code, array $them = []): array
{
    return (new voucher())->them(array_replace(['ma_code' => $code, 'loai' => 'tien', 'gia_tri' => 100000,
        'so_luong' => 5, 'ngay_bd' => date('Y-m-d', strtotime('-1 day')),
        'ngay_kt' => date('Y-m-d', strtotime('+1 day')), 'hang_ap_dung' => null], $them));
}

function ton(int $id): int
{
    return (int) (new sanpham())->layChiTiet($id)['so_luong_ton'];
}

function dat(int $kh, int $sp, int $sl = 1, ?string $vc = null): array
{
    return (new donhang())->taoDon($kh, [$sp => $sl], ['dia_chi' => '1 Nguyen Hue', 'sdt' => '0900000000'], $vc);
}

function toiTrangThai(int $id, string $cuoi): void
{
    foreach ([donhang::XAC_NHAN, donhang::DANG_GIAO, donhang::DA_GIAO, donhang::THANH_CONG] as $tt) {
        $r = (new donhang())->capNhatTrangThai($id, $tt);
        if (!$r['ok']) {
            throw new RuntimeException(json_encode($r));
        }
        if ($tt === $cuoi) {
            return;
        }
    }
}

function race(string $action, array $args): array
{
    $children = [];
    $start = microtime(true) + 0.6;
    foreach ($args as $arg) {
        $cmd = [PHP_BINARY, '-c', php_ini_loaded_file(), BASE_PATH . '/support/tests/test_worker.php',
            $action, (string) $start, json_encode($arg, JSON_UNESCAPED_UNICODE)];
        $proc = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, BASE_PATH);
        if (!is_resource($proc)) {
            throw new RuntimeException('Khong tao duoc test worker');
        }
        fclose($pipes[0]);
        $children[] = [$proc, $pipes];
    }
    $out = [];
    foreach ($children as [$proc, $pipes]) {
        $raw = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($proc);
        if ($exit !== 0) {
            throw new RuntimeException('Worker: ' . $err);
        }
        $out[] = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    }
    return $out;
}

function request(string $base, string &$cookie, string $act = '', ?array $post = null, string $path = '/controller/index.php'): array
{
    if ($act === 'dangnhap' && str_contains($path, 'khu=admin')) {
        $path = '/controller/index.php';
        $post['email'] = $post['admin_user'] ?? $post['email'] ?? '';
        $post['mat_khau'] = $post['admin_pass'] ?? $post['mat_khau'] ?? '';
        unset($post['admin_user'], $post['admin_pass']);
    }
    if ($post !== null && !array_key_exists('_csrf', $post)) {
        $isAdmin = str_contains($path, 'khu=admin');
        $tokenPage = request($base, $cookie, $isAdmin ? 'sanpham' : 'dangnhap', null, $isAdmin ? '/controller/index.php?khu=admin' : '/controller/index.php');
        $post['_csrf'] = (string)($tokenPage['data'][$isAdmin ? 'csrfToken' : 'userCsrf'] ?? '');
    }
    if ($post !== null && $act === 'thanhtoan' && !array_key_exists('_checkout_csrf', $post)) {
        $checkoutPage = request($base, $cookie, 'thanhtoan');
        $post['_checkout_csrf'] = $checkoutPage['data']['checkoutToken'] ?? '';
        if (!isset($post['_order_key'])) $post['_order_key'] = $checkoutPage['data']['codCheckoutKey'] ?? '';
    }
    $separator = str_contains($path, '?') ? '&' : '?';
    $url = $base . $path . ($act !== '' ? $separator . 'act=' . $act : '');
    $headers = 'Cookie: ' . $cookie . "\r\n";
    if ($post !== null) {
        $headers .= "Content-Type: application/x-www-form-urlencoded\r\n";
    }
    $ctx = stream_context_create(['http' => ['method' => $post === null ? 'GET' : 'POST',
        'header' => $headers, 'content' => $post === null ? '' : http_build_query($post),
        'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 10]]);
    $raw = file_get_contents($url, false, $ctx);
    $status = 0;
    $location = '';
    foreach ($http_response_header ?? [] as $header) {
        if (preg_match('/^HTTP\/\S+ (\d+)/', $header, $m)) {
            $status = (int) $m[1];
        } elseif (preg_match('/^Set-Cookie:\s*([^;]+)/i', $header, $m)) {
            $cookie = $m[1];
        } elseif (stripos($header, 'Location: ') === 0) {
            $location = substr($header, 10);
        }
    }
    if ($raw === false || $raw === '') {
        $data = null;
    } else {
        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $data = $raw;
        }
    }
    return ['status' => $status, 'data' => $data, 'location' => $location];
}

function requestHtml(string $base, string &$cookie, string $path): array
{
    $ctx = stream_context_create(['http' => ['method' => 'GET',
        'header' => "User-Agent: PHPWebIntegrationTest\r\nCookie: {$cookie}\r\n",
        'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 10]]);
    $body = file_get_contents($base . $path, false, $ctx);
    $status = 0;
    foreach ($http_response_header ?? [] as $header) {
        if (preg_match('/^HTTP\/\S+ (\d+)/', $header, $m)) {
            $status = (int) $m[1];
        } elseif (preg_match('/^Set-Cookie:\s*([^;]+)/i', $header, $m)) {
            $cookie = $m[1];
        }
    }
    return ['status' => $status, 'body' => $body === false ? '' : $body];
}

function renderAdminView(array $data): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    require BASE_PATH . '/view/admin/sanpham.php';
    return (string) ob_get_clean();
}

function counts(PDO $pdo, string $db): array
{
    $out = [];
    $stmt = $pdo->prepare('SELECT table_name FROM information_schema.tables WHERE table_schema = ? ORDER BY table_name');
    $stmt->execute([$db]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $table) {
        if (preg_match('/^[a-z0-9_]+$/i', $table)) {
            $out[$table] = (int) $pdo->query("SELECT COUNT(*) FROM {$db}.{$table}")->fetchColumn();
        }
    }
    return $out;
}

function flowDatabaseDigest(PDO $pdo, string $db): string
{
    $hash=hash_init('sha256');
    foreach (array_keys(counts($pdo,$db)) as $table) {
        hash_update($hash,$table);
        $stmt=$pdo->query("SELECT * FROM `$db`.`$table` ORDER BY 1");
        while ($row=$stmt->fetch(PDO::FETCH_ASSOC)) hash_update($hash,json_encode($row));
    }
    return hash_final($hash);
}

try {
    $adminPdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};charset=utf8mb4", $cfg['user'], $cfg['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $mainBefore = counts($adminPdo, $cfg['dbname']);
    $mainDigestBefore = flowDatabaseDigest($adminPdo,$mainDb);
    $testDb = 'webbanhang_mvc_test_' . bin2hex(random_bytes(6));
    $adminPdo->exec("CREATE DATABASE {$testDb} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    foreach (['HOST' => $cfg['host'], 'PORT' => $cfg['port'], 'NAME' => $testDb, 'USER' => $cfg['user'], 'PASS' => $cfg['pass']] as $key => $value) {
        putenv("DB_{$key}={$value}");
    }
    // Test khong gui email ra ngoai, ke ca may da co cau hinh Gmail.
    putenv('SMTP_USER=');
    putenv('SMTP_PASS=');
    csdl::resetKetNoi();
    $pdo = csdl::shared();
    $pdo->exec(file_get_contents(BASE_PATH . '/database/schema.sql'));
    ob_start();
    require BASE_PATH . '/support/scripts/khoitao_csdl.php';
    $setupOutput = ob_get_clean();
    preg_match('/Mat khau khoi tao \(chi hien thi lan nay\): ([a-f0-9]{32})/', $setupOutput, $adminPassword);
    $adminPassword = $adminPassword[1] ?? '';
    // Import lan hai khong ghi de tai khoan quan tri da duoc khoi tao an toan.
    $pdo->exec(file_get_contents(BASE_PATH . '/database/schema.sql'));
    check('CSDL rieng + import SQL lap lai giu tai khoan quan tri', counts($pdo, $testDb)['nhanvien'] === 1
        && $adminPassword !== ''
        && (new nhanvien())->kiemTraDangNhap('admin@local.test', $adminPassword)['ok']
        && ((new nhanvien())->selectOne("SELECT vai_tro FROM nhanvien WHERE email = 'admin@local.test'")['vai_tro'] ?? '')
            === nhanvien::ROLE_SUPER_ADMIN);

    $dm = new danhmuc();
    $sp = new sanpham();
    $kh = new khachhang();
    $dh = new donhang();
    $vc = new voucher();
    $km = new khuyenmai();
    $dg = new danhgia();
    $idDm = $dm->them('Danh muc test');
    $idSp = spTest($idDm, 'SP001');
    $idHet = spTest($idDm, 'HETHANG', 0);
    $idKh = khTest('model@test.local');
    $idKh2 = khTest('khac@test.local');
    check('Dang ky: hash mat khau, Silver va diem 0', password_verify('123456', $kh->layTheoId($idKh)['mat_khau'])
        && $kh->layTheoId($idKh)['hang'] === 'Silver' && (int) $kh->layTheoId($idKh)['diem'] === 0);
    check('Chan email trung, email sai, ngay sinh sai',
        !$kh->themKhachHang(['email' => 'model@test.local', 'ho_ten' => 'A', 'mat_khau' => 'a'])['ok']
        && !$kh->themKhachHang(['email' => 'sai', 'ho_ten' => 'A', 'mat_khau' => 'a'])['ok']
        && !$kh->themKhachHang(['email' => 'ngay@test.local', 'ho_ten' => 'A', 'mat_khau' => 'a', 'ngay_sinh' => '2025-02-30'])['ok']);
    $kh->khoa($idKh);
    check('Khach bi khoa khong dang nhap hoac dat don truc tiep', !$kh->kiemTraDangNhap('model@test.local', '123456')['ok']
        && !dat($idKh, $idSp)['ok']);
    $kh->moKhoa($idKh);
    check('Dang nhap dung / sai mat khau', $kh->kiemTraDangNhap('model@test.local', '123456')['ok']
        && !$kh->kiemTraDangNhap('model@test.local', 'sai')['ok']);
    $legacyCustomerId = khTest('legacy-plain@test.local');
    $kh->update('khachhang', ['mat_khau' => 'LegacyPlainPass'], 'id = ?', [$legacyCustomerId]);
    $legacyCustomerLogin = $kh->kiemTraDangNhap('legacy-plain@test.local', 'LegacyPlainPass');
    check('Dang nhap tai khoan cu tu dong nang cap mat khau ro sang hash', !empty($legacyCustomerLogin['ok'])
        && password_verify('LegacyPlainPass', $kh->layTheoId($legacyCustomerId)['mat_khau'])
        && $kh->layTheoId($legacyCustomerId)['mat_khau'] !== 'LegacyPlainPass');
    $legacyMd5CustomerId = khTest('legacy-md5@test.local');
    $kh->update('khachhang', ['mat_khau' => md5('LegacyMd5Pass')], 'id = ?', [$legacyMd5CustomerId]);
    $legacyMd5Login = $kh->kiemTraDangNhap('legacy-md5@test.local', 'LegacyMd5Pass');
    check('Dang nhap tai khoan cu MD5 tu dong nang cap sang hash', !empty($legacyMd5Login['ok'])
        && password_verify('LegacyMd5Pass', $kh->layTheoId($legacyMd5CustomerId)['mat_khau']));
    check('Chan gia am, ton am, ton vuot goc va so luong le',
        !$sp->sua($idSp, ['gia' => -1])['ok'] && !$sp->sua($idSp, ['so_luong_ton' => -1])['ok']
        && !$sp->sua($idSp, ['so_luong_ton' => 11])['ok'] && !$sp->sua($idSp, ['so_luong_ton' => '1.5'])['ok']);
    check('Khong doi so luong goc de vuot gioi han ton', !$sp->sua($idSp, ['so_luong_goc' => 20, 'so_luong_ton' => 15])['ok']);
    check('San pham het hang van tim thay, khong duoc them gio', count($sp->timKiem('HETHANG')) === 1 && !giohang::them($idHet)['ok']);
    $_SESSION = [];
    check('Gio hang them/sua/xoa va chan am/vuot ton', giohang::them($idSp, 2)['ok']
        && !giohang::sua($idSp, 99)['ok'] && !giohang::sua($idSp, -1)['ok']
        && giohang::sua($idSp, 3)['ok'] && giohang::get()[$idSp] === 3
        && giohang::xoa($idSp)['ok'] && giohang::get() === []);

    vcTest('SALE100');
    vcTest('GOLD', ['hang_ap_dung' => 'Gold']);
    vcTest('EXPIRED', ['ngay_bd' => '2000-01-01', 'ngay_kt' => '2000-01-02']);
    vcTest('FUTURE', ['ngay_bd' => '2099-01-01', 'ngay_kt' => '2099-01-02']);
    vcTest('EMPTY', ['so_luong' => 0]);
    check('Voucher sai han, het luot, sai hang deu bi chan',
        !$vc->kiemTra('EXPIRED', 'Silver', 1000000, 30000)['ok'] && !$vc->kiemTra('FUTURE', 'Silver', 1000000, 30000)['ok']
        && !$vc->kiemTra('EMPTY', 'Silver', 1000000, 30000)['ok'] && !$vc->kiemTra('GOLD', 'Silver', 1000000, 30000)['ok']);
    check('Chan voucher am, % vuot 100, thoi han nguoc', !vcTest('NEG', ['gia_tri' => -1])['ok']
        && !vcTest('OVER', ['loai' => 'phantram', 'gia_tri' => 101])['ok']
        && !vcTest('DATE', ['ngay_bd' => '2026-10-02', 'ngay_kt' => '2026-10-01'])['ok']);
    $kmData = ['ten_km' => 'Test 10%', 'loai' => 'sanpham', 'id_sanpham' => $idSp, 'phan_tram' => 10,
        'ngay_bd' => date('Y-m-d'), 'ngay_kt' => date('Y-m-d', strtotime('+1 day'))];
    $km->them($kmData);
    $km->them(array_replace($kmData, ['ten_km' => 'Test 20%', 'loai' => 'danhmuc', 'id_danhmuc' => $idDm, 'phan_tram' => 20]));
    check('Khuyen mai chon muc tot nhat theo SP/danh muc', $km->giaSauKM($sp->layChiTiet($idSp))['gia_km'] === 1600000.0
        && !$km->them(array_replace($kmData, ['phan_tram' => 101]))['ok']);
    vcTest('PERCENT', ['loai' => 'phantram', 'gia_tri' => 10]);
    vcTest('SHIP', ['loai' => 'mienship']);
    check('Voucher % va mien ship tinh dung', $vc->kiemTra('PERCENT', 'Silver', 1000000, 30000)['tong'] === 930000.0
        && $vc->kiemTra('SHIP', 'Silver', 1000000, 30000)['tong'] === 1000000.0);
    $don = dat($idKh, $idSp, 2, ' sale100 ');
    $idDon = (int) ($don['id'] ?? 0);
    check('Checkout: gia KM + voucher + ship + chi tiet + tru kho', $don['ok'] && $don['tong_tien'] === 3130000.0
        && ton($idSp) === 8 && (int) $vc->layTheoCode('SALE100')['so_luong'] === 4
        && count($dh->layTrangThai($idDon)['chitiet']) === 1, $don);
    check('Chi chu don moi duoc huy', !$dh->huyDon($idDon, $idKh2, 'Sai chu')['ok'] && ton($idSp) === 8);
    $huy = $dh->huyDon($idDon, $idKh, 'Doi y');
    check('Huy: giu hoa don, ly do/thoi gian, hoan kho va tang huy', $huy['ok'] && ton($idSp) === 10
        && $dh->layTheoId($idDon)['trang_thai'] === donhang::DA_HUY
        && str_contains($dh->layTheoId($idDon)['ly_do_huy'], 'Doi y')
        && (int) $kh->layTheoId($idKh)['so_lan_huy'] === 1);
    check('Huy lap khong hoan kho hay tang vi pham lan hai', !$dh->huyDon($idDon, $idKh)['ok']
        && ton($idSp) === 10 && (int) $kh->layTheoId($idKh)['so_lan_huy'] === 1);

    $nDon = (int) $pdo->query('SELECT COUNT(*) FROM donhang')->fetchColumn();
    $pdo->exec("CREATE TRIGGER test_fail_detail BEFORE INSERT ON chitietdonhang FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Test rollback'");
    $fail = dat($idKh, $idSp, 1, 'SALE100');
    $pdo->exec('DROP TRIGGER test_fail_detail');
    check('Rollback khi ghi chi tiet loi: khong luu don, khong mat kho/voucher', !$fail['ok']
        && (int) $pdo->query('SELECT COUNT(*) FROM donhang')->fetchColumn() === $nDon
        && ton($idSp) === 10 && (int) $vc->layTheoCode('SALE100')['so_luong'] === 5);
    $failTon = $dh->taoDon($idKh, [$idSp => 1, $idHet => 1], ['dia_chi' => '1 Test', 'sdt' => '0900000000']);
    check('Don nhieu SP thieu hang: khong tru mot phan', !$failTon['ok'] && ton($idSp) === 10);
    $pdo->exec("CREATE TRIGGER test_fail_voucher BEFORE UPDATE ON voucher FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Test rollback stock voucher'");
    $fail = dat($idKh, $idSp, 1, 'SALE100');
    $pdo->exec('DROP TRIGGER test_fail_voucher');
    check('Loi sau khi tru kho: rollback ca don/chi tiet/ton/voucher', !$fail['ok']
        && (int) $pdo->query('SELECT COUNT(*) FROM donhang')->fetchColumn() === $nDon
        && (int) $pdo->query('SELECT COUNT(*) FROM chitietdonhang')->fetchColumn() === 1
        && ton($idSp) === 10 && (int) $vc->layTheoCode('SALE100')['so_luong'] === 5);

    $don2 = dat($idKh, $idSp);
    check('Chan nhay coc va cho huy truoc khi giao', !$dh->capNhatTrangThai($don2['id'], donhang::THANH_CONG)['ok']
        && $dh->capNhatTrangThai($don2['id'], donhang::XAC_NHAN)['ok']
        && $dh->layTheoId($don2['id'])['trang_thai'] === donhang::XAC_NHAN);
    foreach ([donhang::DANG_GIAO, donhang::DA_GIAO, donhang::THANH_CONG] as $tt) {
        $dh->capNhatTrangThai($don2['id'], $tt);
    }
    $diem = (int) $kh->layTheoId($idKh)['diem'];
    check('Thanh cong cong diem dung va len Gold', $diem === 1630 && $kh->layTheoId($idKh)['hang'] === 'Gold');
    check('Lap trang thai thanh cong khong cong diem lai', !$dh->capNhatTrangThai($don2['id'], donhang::THANH_CONG)['ok']
        && (int) $kh->layTheoId($idKh)['diem'] === $diem);
    foreach ([donhang::BOM_HANG => 'so_lan_bom', donhang::TRA_HANG => 'so_lan_tra'] as $tt => $cot) {
        $d = dat($idKh, $idSp);
        toiTrangThai($d['id'], donhang::DANG_GIAO);
        $tonTruoc = ton($idSp);
        check($tt . ': hoan kho va tang dung bo dem', $dh->capNhatTrangThai($d['id'], $tt, 'Đã nhận lại hàng', true)['ok']
            && ton($idSp) === $tonTruoc + 1 && (int) $kh->layTheoId($idKh)[$cot] === ($tt === donhang::BOM_HANG ? 1 : 0));
    }
    $donGold = dat($idKh, $idSp, 1, 'GOLD');
    check('Gold: dung voucher dung hang va mien ship', $donGold['ok'] && $donGold['phi_ship'] === 0.0);
    toiTrangThai($donGold['id'], donhang::THANH_CONG);
    check('Diem tich luy len Diamond', $kh->layTheoId($idKh)['hang'] === 'Diamond');
    $dt = $dh->thongKeDoanhThu('thang');
    check('Doanh thu chi tinh don Thanh cong', (int) $dt['doanh_thu'][0]['so_don'] === 2
        && (float) $dt['doanh_thu'][0]['doanh_thu'] === 3130000.0);
    check('Thong ke tuan va nam chay duoc', count($dh->thongKeDoanhThu('tuan')['doanh_thu']) === 1
        && count($dh->thongKeDoanhThu('nam')['doanh_thu']) === 1);
    $atomicDon = dat($idKh, $idSp);
    toiTrangThai($atomicDon['id'], donhang::DA_GIAO);
    $diemTruoc = (int) $kh->layTheoId($idKh)['diem'];
    $pdo->exec("CREATE TRIGGER test_fail_points BEFORE UPDATE ON khachhang FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Test rollback points'");
    $fail = $dh->capNhatTrangThai($atomicDon['id'], donhang::THANH_CONG);
    $pdo->exec('DROP TRIGGER test_fail_points');
    check('Loi cong diem rollback ca trang thai don', !$fail['ok'] && $dh->layTheoId($atomicDon['id'])['trang_thai'] === donhang::DA_GIAO
        && (int) $kh->layTheoId($idKh)['diem'] === $diemTruoc);
    $archiveResult = $sp->xoa($idSp);
    check('Xoa SP da giao dich chuyen ngung ban, giu danh muc va don cu', $archiveResult['ok'] && $archiveResult['ngung_ban']
        && $sp->layChiTiet($idSp) === null && !$dm->xoa($idDm)['ok'] && $dh->layTheoId($idDon) !== null);
    check('Mo ban lai SP da ngung ban', $sp->moBanLai($idSp)['ok'] && $sp->layChiTiet($idSp) !== null);
    $idChuaBan = spTest($idDm, 'CHUABAN', 1);
    $dg->them($idChuaBan, $idKh, 5, 'Danh gia truoc khi mua');
    $km->them(array_replace($kmData, ['id_sanpham' => $idChuaBan]));
    check('Xoa SP chua co giao dich', $sp->xoa($idChuaBan)['ok'] && $sp->layChiTiet($idChuaBan) === null);
    $dgR = $dg->them($idSp, $idKh, 5, 'Tot');
    $dg->khoa($dgR['id']);
    check('Danh gia bi khoa an khoi du lieu cong khai', $dg->layDSTheoSP($idSp) === []);
    $mailCountBefore=count((new email())->layThuCuaKhach($idKh));
    $mail = (new xuly_email())->guiDonHang($idKh, $idDon, $dh->layTheoId($idDon), $don['dong']);
    check('Chua cau hinh SMTP: van luu hop thu va tra trang thai ro rang', $mail['ok'] && !$mail['smtp']['ok']
        && count((new email())->layThuCuaKhach($idKh)) === $mailCountBefore+1);
    $nv = new nhanvien();
    $nvR = $nv->them(['ho_ten' => 'Nhan vien', 'email' => 'nv@test.local', 'mat_khau' => 'TestPassword123']);
    $nv->doiTrangThai($nvR['id'], 0);
    check('Model nhan vien bi khoa khong dang nhap duoc', !$nv->kiemTraDangNhap('nv@test.local', 'TestPassword123')['ok']);
    $legacyEmployee = $nv->them([
        'ho_ten' => 'Legacy Employee', 'email' => 'legacy-employee@test.local', 'mat_khau' => 'LegacyEmployee123',
    ]);
    $nv->update('nhanvien', ['mat_khau' => 'LegacyEmployee123'], 'id = ?', [$legacyEmployee['id']]);
    $legacyEmployeeLogin = $nv->kiemTraDangNhap('legacy-employee@test.local', 'LegacyEmployee123');
    check('Dang nhap nhan vien co mat khau cu tu dong nang cap sang hash', !empty($legacyEmployeeLogin['ok'])
        && password_verify('LegacyEmployee123', $nv->selectOne(
            'SELECT mat_khau FROM nhanvien WHERE id = ?',
            [$legacyEmployee['id']]
        )['mat_khau']));
    check('Doi mat khau Admin tu choi mat khau ngan', !$nv->doiMatKhau($legacyEmployee['id'], 'short')['ok']);

    $googleNew = $kh->dangNhapGoogle('google-sub-new', 'google-new@test.local', 'Google New');
    $googleAgain = $kh->dangNhapGoogle('google-sub-new', 'google-new@test.local', 'Google New');
    check('Google tao tai khoan moi va nhan dien lai theo sub', !empty($googleNew['ok'])
        && !empty($googleNew['created']) && !isset($googleNew['khachhang']['mat_khau'])
        && !empty($googleAgain['ok']) && empty($googleAgain['created'])
        && (int) $googleNew['khachhang']['id'] === (int) $googleAgain['khachhang']['id']);
    $googleExistingId = khTest('google-link@test.local');
    $googleLink = $kh->dangNhapGoogle('google-sub-link', 'google-link@test.local', 'Linked');
    $googleCollision = $kh->dangNhapGoogle('google-sub-other', 'google-link@test.local', 'Other');
    check('Google lien ket email co san va tu choi xung dot sub', !empty($googleLink['ok'])
        && (int) $googleLink['khachhang']['id'] === $googleExistingId && empty($googleCollision['ok']));
    $googleLockedId = khTest('google-locked@test.local');
    $kh->khoa($googleLockedId);
    check('Google tu choi tai khoan da bi khoa', empty($kh->dangNhapGoogle(
        'google-sub-locked',
        'google-locked@test.local',
        'Locked'
    )['ok']));

    $idRace = spTest($idDm, 'LAST', 1);
    $race = race('checkout', [['kh' => $idKh, 'sp' => $idRace], ['kh' => $idKh2, 'sp' => $idRace]]);
    check('Dong thoi mua SP cuoi: chi mot don duoc tao, ton khong am', count(array_filter($race, fn($r) => $r['ok'])) === 1 && ton($idRace) === 0, $race);
    $idRace2 = spTest($idDm, 'REFUND', 5);
    $rDon = dat($idKh2, $idRace2, 2);
    $before = (int) $kh->layTheoId($idKh2)['so_lan_huy'];
    $race = race('huy', array_fill(0, 2, ['don' => $rDon['id'], 'kh' => $idKh2]));
    check('Dong thoi huy: hoan kho va tang huy dung mot lan', count(array_filter($race, fn($r) => $r['ok'])) === 1
        && ton($idRace2) === 5 && (int) $kh->layTheoId($idKh2)['so_lan_huy'] === $before + 1, $race);
    $rDon = dat($idKh2, $idRace2);
    toiTrangThai($rDon['id'], donhang::DA_GIAO);
    $before = (int) $kh->layTheoId($idKh2)['diem'];
    $race = race('trangthai', array_fill(0, 2, ['don' => $rDon['id'], 'trangthai' => donhang::THANH_CONG]));
    check('Dong thoi hoan tat: cong diem dung mot lan', count(array_filter($race, fn($r) => $r['ok'])) === 1
        && (int) $kh->layTheoId($idKh2)['diem'] === $before + 1630, $race);
    vcTest('LASTVC', ['so_luong' => 1]);
    $race = race('checkout', [['kh' => $idKh, 'sp' => $idSp, 'voucher' => 'LASTVC'],
        ['kh' => $idKh2, 'sp' => $idRace2, 'voucher' => 'LASTVC']]);
    check('Dong thoi dung voucher cuoi: chi mot don thanh cong', count(array_filter($race, fn($r) => $r['ok'])) === 1
        && (int) $vc->layTheoCode('LASTVC')['so_luong'] === 0, $race);

    // Server PHP thuc su, moi client giu cookie rieng; khong gia lap $_POST trong test.
    putenv('GOOGLE_CLIENT_ID=local-regression.apps.googleusercontent.com');
    putenv('APP_URL=');
    $socket = stream_socket_server('tcp://127.0.0.1:0');
    $addr = stream_socket_get_name($socket, false);
    fclose($socket);
    $log = sys_get_temp_dir() . '/' . $testDb . '-http.log';
    $server = proc_open([PHP_BINARY, '-c', php_ini_loaded_file(), '-S', $addr, '-t', BASE_PATH],
        [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes, BASE_PATH);
    fclose($pipes[0]);
    $ready = false;
    for ($i = 0; $i < 50; $i++) {
        $conn = @stream_socket_client('tcp://' . $addr, $errno, $errstr, 0.1);
        if ($conn !== false) {
            fclose($conn);
            $ready = true;
            break;
        }
        usleep(100000);
    }
    if (!$ready) {
        throw new RuntimeException('Server HTTP khong khoi dong');
    }
    $base = 'http://' . $addr;
    $adminCookie = '';
    $adminPath = '/controller/index.php?khu=admin';
    $anonymousCookie = '';
    $r = request($base, $anonymousCookie, 'csdl', null, $adminPath);
    check('HTTP du lieu CSDL chi cho phep admin da dang nhap', $r['status'] === 302
        && str_contains($r['location'], 'dangnhap'));
    $r = request($base, $anonymousCookie, 'themnhanvien', [
        'ho_ten' => 'Unauthorized', 'email' => 'unauthorized@test.local', 'mat_khau' => 'TestPassword123',
    ], $adminPath);
    check('HTTP khach vang lai khong tao duoc nhan vien', $r['status'] === 302
        && $nv->kiemTraDangNhap('unauthorized@test.local', 'TestPassword123')['ok'] === false);
    $r = request($base, $adminCookie, 'dangnhap', [
        '_csrf' => '', 'admin_user' => 'admin@local.test', 'admin_pass' => $adminPassword,
    ], $adminPath);
    check('HTTP dang nhap Admin tu choi request thieu CSRF token', $r['status'] === 403);
    $r = request($base, $adminCookie, 'dangnhap', [
        'admin_user' => 'admin@local.test', 'admin_pass' => $adminPassword,
    ], $adminPath);
    check('HTTP dang nhap Admin khong can thong tin dien san', $r['status'] === 302 && $adminCookie !== '');
    $adminId = (int) $nv->selectOne("SELECT id FROM nhanvien WHERE email = 'admin@local.test'")['id'];
    $adminRole = (string) $nv->layTheoId($adminId)['vai_tro'];
    $nv->update('nhanvien', ['vai_tro' => 'NhanVien'], 'id = ?', [$adminId]);
    $r = request($base, $adminCookie, 'themnhanvien', ['vai_tro'=>'SuperAdmin'], $adminPath);
    check('HTTP Admin bi thu hoi quyen sau khi vai tro thay doi trong CSDL',
        $r['status'] === 403);
    $nv->update('nhanvien', ['vai_tro' => $adminRole], 'id = ?', [$adminId]);
    $r = request($base, $adminCookie, 'dangnhap', [
        'admin_user' => 'admin@local.test', 'admin_pass' => $adminPassword,
    ], $adminPath);
    check('HTTP Admin dang nhap lai sau khi quyen duoc khoi phuc', $r['status'] === 302);
    $nv->update('nhanvien', ['trang_thai' => 0], 'id = ?', [$adminId]);
    $r = request($base, $adminCookie, 'csdl', null, $adminPath);
    check('HTTP Admin bi thu hoi phien ngay sau khi tai khoan bi khoa',
        $r['status'] === 302 && str_contains($r['location'], 'dangnhap'));
    $nv->update('nhanvien', ['trang_thai' => 1], 'id = ?', [$adminId]);
    $r = request($base, $adminCookie, 'dangnhap', [
        'admin_user' => 'admin@local.test', 'admin_pass' => $adminPassword,
    ], $adminPath);
    check('HTTP Admin dang nhap lai sau khi tai khoan duoc mo khoa', $r['status'] === 302);
    $r = request($base, $adminCookie, 'themnhanvien', [
        '_csrf' => '', 'ho_ten' => 'No CSRF', 'email' => 'no-csrf@test.local',
        'mat_khau' => 'NoCsrfPassword123',
    ], $adminPath);
    check('HTTP tu choi POST admin thieu CSRF token', $r['status'] === 403
        && $nv->kiemTraDangNhap('no-csrf@test.local', 'NoCsrfPassword123')['ok'] === false);
    $r = request($base, $adminCookie, 'themnhanvien', [
        'ho_ten' => 'HTTP Employee', 'email' => 'employee-http@test.local', 'mat_khau' => 'EmployeePass123',
    ], $adminPath);
    $employee = $nv->kiemTraDangNhap('employee-http@test.local', 'EmployeePass123');
    check('HTTP Admin tao nhan vien thuong, khong tao them Admin', $r['status'] === 200
        && !empty($employee['ok']) && ($employee['nhanvien']['vai_tro'] ?? '') === 'NhanVien');
    $employeeCookie = '';
    $r = request($base, $employeeCookie, 'dangnhap', [
        'admin_user' => 'employee-http@test.local', 'admin_pass' => 'EmployeePass123',
    ], $adminPath);
    $employeeAccess = request($base, $employeeCookie, 'csdl', null, $adminPath);
    check('HTTP nhan vien dang nhap va tra cuu du lieu duoc',
        $r['status'] === 302 && $employeeAccess['status'] === 200);
    $employeeProducts = requestHtml($base, $employeeCookie, '/controller/index.php?khu=admin&act=sanpham');
    $employeeTheme = request($base, $employeeCookie, 'giaodien', null, $adminPath);
    $employeeOrders = request($base, $employeeCookie, 'donhang', null, $adminPath);
    check('HTTP nhan vien duoc sua san pham va vao nghiep vu cua hang',
        $employeeProducts['status'] === 200 && str_contains($employeeProducts['body'], 'Danh sách sản phẩm')
        && str_contains($employeeProducts['body'], 'id="spForm"')
        && $employeeTheme['status'] === 200 && $employeeOrders['status'] === 200);

    $managerCreate = request($base, $adminCookie, 'themnhanvien', [
        'ho_ten' => 'HTTP Manager',
        'email' => 'manager-http@test.local',
        'mat_khau' => 'ManagerPassword123',
        'vai_tro' => nhanvien::ROLE_MANAGER,
    ], $adminPath);
    $manager = $nv->kiemTraDangNhap('manager-http@test.local', 'ManagerPassword123');
    $managerCookie = '';
    $managerLogin = request($base, $managerCookie, 'dangnhap', [
        'admin_user' => 'manager-http@test.local', 'admin_pass' => 'ManagerPassword123',
    ], $adminPath);
    $managerProducts = requestHtml($base, $managerCookie, '/controller/index.php?khu=admin&act=sanpham');
    $managerOrders = requestHtml($base, $managerCookie, '/controller/index.php?khu=admin&act=donhang');
    $managerCustomers = requestHtml($base, $managerCookie, '/controller/index.php?khu=admin&act=khachhang');
    $managerTheme = request($base, $managerCookie, 'giaodien', null, $adminPath);
    $managerStaff = request($base, $managerCookie, 'nhanvien', null, $adminPath);
    $managerDatabase = request($base, $managerCookie, 'csdl', null, $adminPath);
    check('HTTP Super Admin tao va dang nhap tai khoan QuanLy',
        $managerCreate['status'] === 200 && !empty($manager['ok'])
        && ($manager['nhanvien']['vai_tro'] ?? '') === nhanvien::ROLE_MANAGER
        && $managerLogin['status'] === 302);
    check('HTTP QuanLy duoc xu ly san pham don khach va quan ly nhan vien',
        $managerProducts['status'] === 200 && str_contains($managerProducts['body'], 'Danh sách sản phẩm')
        && str_contains($managerProducts['body'], 'id="spForm"')
        && $managerOrders['status'] === 200 && str_contains($managerOrders['body'], 'act=capnhattrangthai')
        && $managerCustomers['status'] === 200 && str_contains($managerCustomers['body'], 'act=khoakhachhang')
        && $managerTheme['status'] === 200
        && $managerStaff['status'] === 200 && $managerDatabase['status'] === 200);
    $managerAttemptStaff = request($base, $managerCookie, 'themnhanvien', [
        'ho_ten' => 'Escalation Attempt', 'email' => 'escalation@test.local',
        'mat_khau' => 'EscalationPass123', 'vai_tro' => nhanvien::ROLE_SUPER_ADMIN,
    ], $adminPath);
    check('HTTP QuanLy khong the tao tai khoan hay tu nang cap len Super Admin',
        $managerAttemptStaff['status'] === 403
        && !$nv->kiemTraDangNhap('escalation@test.local', 'EscalationPass123')['ok']);

    $themeFile = BASE_PATH . '/support/config/theme.json';
    $themePreviouslyExisted = is_file($themeFile);
    $oldThemeContents = $themePreviouslyExisted ? file_get_contents($themeFile) : false;
    $managerThemeSave = request($base, $managerCookie, 'giaodien', [
        'season' => 'theme-summer', 'font' => 'font-segoe', 'bg' => 'bg-light-gray',
    ], $adminPath);
    $themeSavedForTest = is_file($themeFile)
        ? json_decode((string) file_get_contents($themeFile), true)
        : null;
    if ($themePreviouslyExisted) {
        if (!is_string($oldThemeContents) || file_put_contents($themeFile, $oldThemeContents) === false) {
            throw new RuntimeException('Khong the khoi phuc cau hinh giao dien sau test');
        }
    } elseif (is_file($themeFile) && !unlink($themeFile)) {
        throw new RuntimeException('Khong the don cau hinh giao dien test');
    }
    check('HTTP QuanLy doi duoc giao dien theo mua', $managerThemeSave['status'] === 200
        && is_array($themeSavedForTest) && ($themeSavedForTest['season'] ?? '') === 'theme-summer');
    $employeeId = (int) ($employee['nhanvien']['id'] ?? 0);
    $assignManager = request($base, $adminCookie, 'doivaitronhanvien', [
        'id' => $employeeId, 'vai_tro' => nhanvien::ROLE_MANAGER,
    ], $adminPath);
    $assignedEmployee = $nv->layTheoId($employeeId);
    check('HTTP Super Admin gan va doi duoc vai tro nhan vien',
        $assignManager['status'] === 200 && ($assignedEmployee['vai_tro'] ?? '') === nhanvien::ROLE_MANAGER);
    $lastSuperAdminDemotion = $nv->doiVaiTro($adminId, nhanvien::ROLE_MANAGER, $employeeId);
    check('CSDL khong cho ha cap Super Admin cuoi cung',
        empty($lastSuperAdminDemotion['ok'])
        && ($nv->layTheoId($adminId)['vai_tro'] ?? '') === $adminRole);
    $reassignEmployee = request($base, $adminCookie, 'doivaitronhanvien', [
        'id' => $employeeId, 'vai_tro' => nhanvien::ROLE_EMPLOYEE,
    ], $adminPath);
    check('HTTP Super Admin doi lai vai tro NhanVien duoc tao', $reassignEmployee['status'] === 200
        && ($nv->layTheoId($employeeId)['vai_tro'] ?? '') === nhanvien::ROLE_EMPLOYEE);
    $r = request($base, $adminCookie, 'khoanhanvien&id=' . $employeeId, null, $adminPath);
    check('HTTP doi trang thai nhan vien tu choi GET', $r['status'] === 405
        && (int) $nv->layTheoId($employeeId)['trang_thai'] === 1);
    $r = request($base, $adminCookie, 'khoanhanvien', ['id' => $employeeId, 'trang_thai' => 0], $adminPath);
    check('HTTP Admin khoa nhan vien bang POST va DB cap nhat', $r['status'] === 200
        && (int) $nv->layTheoId($employeeId)['trang_thai'] === 0);
    $r = request($base, $adminCookie, 'khoanhanvien', ['id' => $employeeId, 'trang_thai' => 1], $adminPath);
    check('HTTP Admin mo khoa nhan vien bang POST va DB cap nhat', $r['status'] === 200
        && (int) $nv->layTheoId($employeeId)['trang_thai'] === 1);
    $databaseHtml = requestHtml($base, $adminCookie, '/controller/index.php?khu=admin&act=csdl&bang=khachhang');
    check('HTTP admin xem duoc bang khach hang nhung khong lo cot mat khau', $databaseHtml['status'] === 200
        && str_contains($databaseHtml['body'], 'Tra cứu dữ liệu gốc')
        && str_contains($databaseHtml['body'], 'model@test.local')
        && !str_contains($databaseHtml['body'], '>mat_khau<'));
    $revenueHtml = requestHtml($base, $adminCookie, '/controller/index.php?khu=admin&act=doanhthu');
    $voucherHtml = requestHtml($base, $adminCookie, '/controller/index.php?khu=admin&act=voucher');
    check('HTTP cac trang admin co du lieu render khong bi loi escape', $revenueHtml['status'] === 200
        && str_contains($revenueHtml['body'], 'Doanh thu')
        && $voucherHtml['status'] === 200 && str_contains($voucherHtml['body'], 'Voucher (mã giảm giá)'));
    $r = request($base, $adminCookie, 'sanpham', null, $adminPath);
    $_SESSION['admin'] = ['hoten' => 'Admin test'];
    $adminHtml = is_array($r['data']) ? renderAdminView($r['data']) : '';
    check('HTTP trang Admin hien san pham/danh muc tu CSDL', $r['status'] === 200
        && str_contains($adminHtml, 'Quản lý danh mục') && str_contains($adminHtml, 'Danh sách sản phẩm'));
    $r = request($base, $adminCookie, 'themsanpham', [
        'ma_sp' => 'ADMIN-HTTP', 'ten_sp' => 'Sản phẩm Admin HTTP', 'id_danhmuc' => $idDm,
        'gia' => 900000, 'so_luong_goc' => 10, 'so_luong_ton' => 4, 'mo_ta' => 'Thêm qua trang quản trị',
    ], $adminPath);
    $adminSp = $sp->layTheoMa('ADMIN-HTTP');
    check('HTTP Admin them san pham ghi dung du lieu CSDL', $r['status'] === 200
        && $adminSp !== null && (int) $adminSp['so_luong_goc'] === 10 && (int) $adminSp['so_luong_ton'] === 4);
    $databaseHtml = requestHtml($base, $adminCookie, '/controller/index.php?khu=admin&act=csdl&bang=sanpham');
    check('HTTP du lieu CSDL cap nhat sau thao tac them san pham cua admin', $databaseHtml['status'] === 200
        && str_contains($databaseHtml['body'], 'ADMIN-HTTP')
        && str_contains($databaseHtml['body'], 'Tổng 5 bản ghi'));
    $r = request($base, $adminCookie, 'suasanpham', [
        'id' => $adminSp['id'], 'ma_sp' => 'ADMIN-HTTP', 'ten_sp' => 'Sản phẩm đã sửa',
        'id_danhmuc' => $idDm, 'gia' => 950000, 'so_luong_goc' => 10, 'so_luong_ton' => 11,
    ], $adminPath);
    check('HTTP Admin chan sua ton vuot so luong goc', $r['status'] === 200
        && $sp->layChiTiet((int) $adminSp['id'])['ten_sp'] === 'Sản phẩm Admin HTTP');
    $r = request($base, $adminCookie, 'danhmuc', [
        'hanh_dong' => 'them', 'ten_danhmuc' => 'Danh mục Admin HTTP', 'mo_ta' => 'Tạo từ trang quản trị',
    ], $adminPath);
    $adminDm = $dm->selectOne('SELECT id FROM danhmuc WHERE ten_danhmuc = ?', ['Danh mục Admin HTTP']);
    check('HTTP Admin them danh muc ghi vao CSDL', $r['status'] === 200 && $adminDm !== null);
    $r = request($base, $adminCookie, 'danhmuc', [
        'hanh_dong' => 'sua', 'id' => $adminDm['id'],
        'ten_danhmuc' => 'Danh mục Admin HTTP sửa', 'mo_ta' => 'Đã cập nhật',
    ], $adminPath);
    check('HTTP Admin sua danh muc cap nhat CSDL', $r['status'] === 200
        && $dm->layTheoId((int) $adminDm['id'])['ten_danhmuc'] === 'Danh mục Admin HTTP sửa');
    $r = request($base, $adminCookie, 'danhmuc', [
        'hanh_dong' => 'xoa', 'id' => $adminDm['id'],
    ], $adminPath);
    check('HTTP Admin xoa danh muc trong CSDL', $r['status'] === 200
        && $dm->layTheoId((int) $adminDm['id']) === null);
    $r = request($base, $adminCookie, 'xoasanpham', ['id' => $adminSp['id']], $adminPath);
    check('HTTP Admin xoa san pham chua giao dich', $r['status'] === 200
        && $sp->layChiTiet((int) $adminSp['id']) === null);

    $cookie = '';
    $r = request($base, $cookie, 'timkiem', null, '/index.php');
    check('HTTP index goc chuyen huong dung controller va giu act', $r['status'] === 302 && $r['location'] === 'controller/index.php?act=timkiem');
    $r = request($base, $cookie, 'thanhtoan');
    check('HTTP chua dang nhap khong duoc thanh toan', $r['status'] === 302 && str_contains($r['location'], 'dangnhap'));
    $r = request($base, $cookie, 'dangky', ['ho_ten' => 'Mismatch User', 'email' => 'mismatch@test.local',
        'mat_khau' => '123456', 're_password' => '654321']);
    check('HTTP dang ky tu choi mat khau xac nhan khong khop', !empty($r['data']['loi'])
        && $kh->layTheoEmail('mismatch@test.local') === null);
    $r = request($base, $cookie, 'google_login', [
        'google_email' => 'forged@test.local',
        'google_name' => 'Forged User',
    ]);
    check('HTTP Google tu choi email gia mao va khong tao tai khoan', $r['status'] === 302
        && str_contains($r['location'], 'google_error')
        && $kh->layTheoEmail('forged@test.local') === null);
    $googleCookie = '';
    $googlePage = request($base, $googleCookie, 'dangnhap');
    $googleCsrf = $googlePage['data']['googleCsrfToken'] ?? '';
    $googleNonce = $googlePage['data']['googleNonce'] ?? '';
    $googleHtml = requestHtml($base, $googleCookie, '/controller/index.php?act=dangnhap');
    $googleSignupHtml = requestHtml($base, $googleCookie, '/controller/index.php?act=dangky');
    check('HTTP Google popup gui token bang form cung origin, co CSRF va nonce', strlen($googleCsrf) === 64
        && strlen($googleNonce) === 64
        && str_contains($googleHtml['body'], 'data-ux_mode="popup"')
        && str_contains($googleSignupHtml['body'], 'data-ux_mode="popup"')
        && str_contains($googleHtml['body'], 'action="index.php?act=google_login" method="POST"')
        && str_contains($googleHtml['body'], 'data-nonce="' . $googleNonce . '"'));
    $r = request($base, $googleCookie, 'google_login');
    check('HTTP Google chan callback GET bi mat POST', $r['status'] === 302
        && str_contains($r['location'], 'google_error=request'));
    $r = request($base, $googleCookie, 'google_login', ['_google_csrf' => 'wrong', 'credential' => 'forged']);
    check('HTTP Google popup tu choi CSRF sai', $r['status'] === 302
        && str_contains($r['location'], 'google_error=csrf'));
    $googleOtherCookie = '';
    $r = request($base, $googleOtherCookie, 'google_login', ['_google_csrf' => $googleCsrf, 'credential' => 'forged']);
    check('HTTP Google popup khong dung duoc CSRF cua phien khac', $r['status'] === 302
        && str_contains($r['location'], 'google_error=csrf'));
    $r = request($base, $googleCookie, 'google_login', ['_google_csrf' => $googleCsrf]);
    check('HTTP Google popup co CSRF dung van can credential', $r['status'] === 302
        && str_contains($r['location'], 'google_error=credential'));
    $r = request($base, $cookie, 'dangky', [
        'action' => 'gui_otp', 'ho_ten' => 'OTP User', 'email' => 'otp@test.local',
        'mat_khau' => 'TestPassword123', 're_password' => 'TestPassword123',
    ]);
    check('HTTP OTP khong gui duoc thi bao loi va khong tiet lo ma', !empty($r['data']['loi'])
        && !str_contains((string) $r['data']['loi'], 'Mã xác thực của bạn là')
        && $kh->layTheoEmail('otp@test.local') === null);
    $r = request($base, $cookie, 'dangky', ['ho_ten' => 'HTTP User', 'email' => 'http@test.local',
        'mat_khau' => '123456', 'ngay_sinh' => '2001-02-03']);
    $httpKh = (int) $kh->layTheoEmail('http@test.local')['id'];
    check('HTTP dang ky tao khach hang trong CSDL', $r['status'] === 200 && $httpKh > 0 && $r['data']['loi'] === null);
    $databaseHtml = requestHtml($base, $adminCookie, '/controller/index.php?khu=admin&act=csdl&bang=khachhang');
    check('HTTP du lieu CSDL cap nhat sau thao tac dang ky cua user', $databaseHtml['status'] === 200
        && str_contains($databaseHtml['body'], 'http@test.local'));
    $r = request($base, $cookie, 'dangnhap', ['email' => 'http@test.local', 'mat_khau' => 'sai']);
    check('HTTP dang nhap sai bi tu choi', !empty($r['data']['loi']));
    $oldCookie = $cookie;
    $r = request($base, $cookie, 'dangnhap', ['email' => 'http@test.local', 'mat_khau' => '123456']);
    check('HTTP dang nhap dung va doi session ID', $r['status'] === 302 && $cookie !== $oldCookie);
    $r = request($base, $cookie, 'trangchu&moi_trang=1');
    $p1 = $r['data'];
    $r = request($base, $cookie, 'trangchu&moi_trang=1&trang=2');
    $p2 = $r['data'];
    check('HTTP phan trang/Xem them khong lap SP, co tong va con_tiep', count($p1['ds_sanpham']) === 1
        && $p1['ds_sanpham'][0]['id'] !== $p2['ds_sanpham'][0]['id'] && $p1['con_tiep']
        && isset($p1['sp_km'][0]['gia_km'], $p1['sp_moi'][0]['gia_km']));
    $storefront = requestHtml($base, $cookie, '/controller/index.php?act=trangchu');
    check('HTTP storefront render san pham that tu CSDL', $storefront['status'] === 200
        && str_contains($storefront['body'], 'San pham SP001')
        && str_contains($storefront['body'], 'ai-phone-elegance.svg'));
    $illustrations = [];
    foreach (['ai-phone-elegance.svg', 'ai-phone-innovation.svg', 'ai-phone-portrait.svg', 'ai-accessories.svg'] as $image) {
        $illustrations[] = requestHtml($base, $cookie, '/view/image/' . $image);
    }
    check('HTTP bon minh hoa SVG danh muc duoc phuc vu hop le', count(array_filter($illustrations, static fn(array $image): bool =>
        $image['status'] === 200 && str_starts_with(ltrim($image['body']), '<svg')
    )) === 4);
    $r = request($base, $cookie, 'timkiem&q=HETHANG');
    check('HTTP van hien SP het hang va co co het_hang', $r['data']['ds_sanpham'][0]['het_hang'] === true);
    $r = request($base, $cookie, 'chitiet&id=' . $idSp);
    check('HTTP chi tiet co gia KM va an danh gia bi khoa', (float) $r['data']['chitiet']['gia_km'] === 1600000.0 && $r['data']['ds_danhgia'] === []);
    $r = request($base, $cookie, 'themdanhgia', ['id_sanpham' => $idSp, 'so_sao' => 4, 'noi_dung' => 'Danh gia HTTP']);
    check('HTTP khach chua mua khong duoc danh gia', !empty($r['data']['loi']) && count($r['data']['ds_danhgia']) === 0);
    $httpSp = spTest($idDm, 'HTTP', 10, 1000000);
    request($base, $cookie, 'themgiohang', ['id' => $httpSp, 'sl' => 2]);
    $checkout = requestHtml($base, $cookie, '/controller/index.php?act=thanhtoan');
    check('HTTP form checkout gui dung cac truong backend chap nhan', $checkout['status'] === 200
        && str_contains($checkout['body'], 'name="dia_chi"')
        && str_contains($checkout['body'], 'name="sdt"')
        && str_contains($checkout['body'], 'name="ma_voucher"'));
    $r = request($base, $cookie, 'suagiohang', ['id' => $httpSp, 'sl' => 3]);
    check('HTTP gio hang luu qua nhieu request bang cookie', $r['data']['giohang']['items'][0]['so_luong'] === 3);
    $r = request($base, $cookie, 'suagiohang', ['id' => $httpSp, 'sl' => -1]);
    check('HTTP sua gio am bi chan va giu so luong cu', !empty($r['data']['loi']) && $r['data']['giohang']['items'][0]['so_luong'] === 3);
    $r = request($base, $cookie, 'suagiohang', ['id' => $httpSp, 'sl' => '1.5']);
    check('HTTP khong ep so luong le thanh so nguyen', $r['status'] === 400);
    request($base, $cookie, 'xoagiohang', ['id' => $httpSp]);
    $r = request($base, $cookie, 'giohang');
    check('HTTP xoa san pham khoi gio', $r['data']['giohang']['items'] === []);
    request($base, $cookie, 'themgiohang', ['id' => $httpSp, 'sl' => 2]);
    $before = count($dh->layTheoKhach($httpKh));
    $r = request($base, $cookie, 'thanhtoan', ['dia_chi' => '1 HTTP', 'sdt' => '0900000000', 'ma_voucher' => 'EXPIRED']);
    check('HTTP voucher sai: khong tao don, giu gio', !empty($r['data']['loi']) && count($dh->layTheoKhach($httpKh)) === $before
        && count($r['data']['giohang']['items']) === 1);
    vcTest('HTTPVC');
    $r = request($base, $cookie, 'thanhtoan', ['dia_chi' => '1 HTTP', 'sdt' => '0900000000', 'ma_voucher' => 'HTTPVC']);
    $httpDon = (int) ($r['data']['id_don'] ?? 0);
    check('HTTP checkout day du: don/chi tiet/ton/voucher/mail/gio trong', !empty($r['data']['ok'])
        && $httpDon > 0 && ton($httpSp) === 8 && (int) $vc->layTheoCode('HTTPVC')['so_luong'] === 4
        && count($dh->layTrangThai($httpDon)['chitiet']) === 1 && $r['data']['giohang']['items'] === []
        && $r['data']['email_result']['ok'] && !$r['data']['email_result']['smtp']['ok'], $r);
    $r = request($base, $cookie, 'thanhtoan', ['dia_chi' => '1 HTTP', 'sdt' => '0900000000']);
    check('HTTP gui lai checkout khong tao don trung', !empty($r['data']['loi']) && count($dh->layTheoKhach($httpKh)) === 1);
    $r = request($base, $cookie, 'hopthu');
    check('HTTP hop thu co ban sao don vua dat', count($r['data']['ds_thu']) === 1 && str_contains($r['data']['ds_thu'][0]['tieu_de'], '#' . $httpDon));
    $historyHtml = requestHtml($base, $cookie, '/controller/index.php?act=lichsumuahang');
    $trackingHtml = requestHtml($base, $cookie, '/controller/index.php?act=theodoidonhang&id=' . $httpDon);
    $mailboxHtml = requestHtml($base, $cookie, '/controller/index.php?act=hopthu');
    check('HTTP lich su/theo doi/hop thu render du lieu DB cua khach', $historyHtml['status'] === 200
        && $trackingHtml['status'] === 200 && $mailboxHtml['status'] === 200
        && str_contains($historyHtml['body'], '#' . $httpDon)
        && str_contains($trackingHtml['body'], '#' . $httpDon)
        && str_contains($mailboxHtml['body'], '#' . $httpDon));
    $cookieKhac = '';
    request($base, $cookieKhac, 'dangnhap', ['email' => 'khac@test.local', 'mat_khau' => '123456']);
    $r = request($base, $cookieKhac, 'theodoidonhang&id=' . $httpDon);
    check('HTTP khong xem duoc don cua khach khac', $r['data']['donhang'] === null && !empty($r['data']['loi']));
    $r = request($base, $cookieKhac, 'huydonhang', ['id' => $httpDon, 'ly_do' => 'Sai chu']);
    check('HTTP khong huy duoc don cua khach khac', !empty($r['data']['loi']) && ton($httpSp) === 8);
    $r = request($base, $cookieKhac, 'khieunai', ['id' => $httpDon, 'noi_dung' => 'Sai chu']);
    check('HTTP khong khieu nai don cua khach khac', !empty($r['data']['loi']) && count((new email())->layThuCuaKhach($httpKh)) === 1);
    $r = request($base, $cookie, 'theodoidonhang&id=' . $httpDon);
    check('HTTP chu don xem duoc chi tiet va timeline', count($r['data']['donhang']['timeline']) === 5 && count($r['data']['donhang']['chitiet']) === 1);
    $r = request($base, $cookie, 'khieunai', ['id' => $httpDon, 'noi_dung' => 'Can ho tro']);
    check('HTTP khieu nai luu vao CSDL', empty($r['data']['loi']) && count((new email())->layThuCuaKhach($httpKh)) === 2);
    $r = request($base, $cookie, 'huydonhang', ['id' => $httpDon, 'ly_do' => 'Khong con nhu cau']);
    check('HTTP huy don hoan kho va giu lich su', empty($r['data']['loi']) && ton($httpSp) === 10
        && $r['data']['ds_donhang'][0]['trang_thai'] === donhang::DA_HUY);
    request($base, $cookie, 'themgiohang', ['id' => $httpSp, 'sl' => 1]);
    $kh->khoa($httpKh);
    $r = request($base, $cookie, 'thanhtoan', ['dia_chi' => '1 HTTP', 'sdt' => '0900000000']);
    check('HTTP khoa khach sau dang nhap: phien cu khong dat duoc', $r['status'] === 302 && count($dh->layTheoKhach($httpKh)) === 1);
    $kh->moKhoa($httpKh);
    request($base, $cookie, 'dangnhap', ['email' => 'http@test.local', 'mat_khau' => '123456']);
    request($base, $cookie, 'themgiohang', ['id' => $httpSp, 'sl' => 1]);
    request($base, $cookie, 'dangxuat', []);
    $r = request($base, $cookie, 'giohang');
    $history = request($base, $cookie, 'lichsumuahang');
    check('HTTP dang xuat that su xoa ca tai khoan va gio hang', $r['data']['giohang']['items'] === [] && $history['status'] === 302);
    require __DIR__ . '/flow_scenarios.php';
    require __DIR__ . '/bank_scenarios.php';
    require __DIR__ . '/voucher_scope_scenarios.php';
    require __DIR__ . '/voucher_value_scenarios.php';
    require __DIR__ . '/admin_usability_scenarios.php';
    require __DIR__ . '/product_archive_scenarios.php';
    require __DIR__ . '/store_tools_scenarios.php';
    require __DIR__ . '/oop_member_scenarios.php';
    require __DIR__ . '/oop_refactor_scenarios.php';
    $r = request($base, $cookie, 'khongco');
    check('HTTP act khong ton tai tra 400', $r['status'] === 400);
    $r = request($base, $cookie, 'dangnhap', ['email' => ['x'], 'mat_khau' => '123456']);
    check('HTTP tham so mang bi tu choi co kiem soat', $r['status'] === 400);
} catch (Throwable $e) {
    check('Test khong bi gian doan', false, $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
} finally {
    if (is_resource($server)) {
        proc_terminate($server);
        proc_close($server);
    }
    csdl::resetKetNoi();
    if ($adminPdo !== null && $testDb !== null && preg_match('/^webbanhang_mvc_test_[a-f0-9]{12}$/', $testDb)) {
        $adminPdo->exec("DROP DATABASE {$testDb}");
    }
    if ($adminPdo !== null && isset($mainBefore)) {
        check('CSDL ung dung giu nguyen toan bo gia tri sau test', $mainDigestBefore === flowDatabaseDigest($adminPdo,$mainDb));
        check('CSDL ung dung giu nguyen so ban ghi sau toan bo test', $mainBefore === counts($adminPdo, $mainDb));
    }
    foreach ($oldEnv as $key => $value) {
        putenv($value === false ? $key : "{$key}={$value}");
    }
}
$pass = count(array_filter($results, fn($r) => $r['ok']));
$fail = count($results) - $pass;
$report = ['thoi_gian' => date(DATE_ATOM), 'php' => PHP_VERSION, 'moi_truong' => 'PHP/MySQL XAMPP',
    'pass' => $pass, 'fail' => $fail, 'kiem_tra' => $results];
file_put_contents(__DIR__ . '/ketqua_test.json', json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "\nKet qua: {$pass} PASS, {$fail} FAIL\n";
exit($fail === 0 ? 0 : 1);
