<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Chi chay bang PHP CLI');
}
require_once dirname(__DIR__, 2) . '/model/csdl.php';
try {
    $cfg = csdl::cauHinh();
    $pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};charset=utf8mb4", $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$cfg['dbname']}`");
    $pdo->exec(file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql'));
    require_once __DIR__.'/migrate_store_tools.php';
    migration_store_tools::run($pdo);
    require_once __DIR__ . '/migrate_flow.php';
    migration_flow::run($pdo);
    $customerColumns = $pdo->query('SHOW COLUMNS FROM khachhang')->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('ten_dang_nhap', $customerColumns, true)) {
        $pdo->exec('ALTER TABLE khachhang ADD COLUMN ten_dang_nhap VARCHAR(100) NULL AFTER ho_ten');
    }
    if (!in_array('sdt', $customerColumns, true)) {
        $pdo->exec('ALTER TABLE khachhang ADD COLUMN sdt VARCHAR(20) NULL AFTER email');
    }
    if (!in_array('google_sub', $customerColumns, true)) {
        $pdo->exec('ALTER TABLE khachhang ADD COLUMN google_sub VARCHAR(255) NULL UNIQUE AFTER email');
    }
    $admin = $pdo->query("SELECT id, mat_khau FROM nhanvien WHERE email = 'admin@local.test' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $initialPassword = getenv('ADMIN_INITIAL_PASSWORD');
    $hasConfiguredPassword = $initialPassword !== false && $initialPassword !== '';
    $rotatePassword = $admin === false || $hasConfiguredPassword;
    if ($rotatePassword) {
        $generatedPassword = !$hasConfiguredPassword;
        if ($generatedPassword) {
            $initialPassword = bin2hex(random_bytes(16));
        } elseif (strlen($initialPassword) < 12) {
            throw new RuntimeException('ADMIN_INITIAL_PASSWORD phai co it nhat 12 ky tu');
        }
        $hash = password_hash($initialPassword, PASSWORD_DEFAULT);
        if ($admin === false) {
            $stmt = $pdo->prepare(
                'INSERT INTO nhanvien (ho_ten, email, mat_khau, vai_tro, trang_thai)
                 VALUES (?, ?, ?, ?, 1)'
            );
            $stmt->execute(['Quản trị viên', 'admin@local.test', $hash, 'SuperAdmin']);
        } else {
            $stmt = $pdo->prepare('UPDATE nhanvien SET mat_khau = ?, vai_tro = ?, trang_thai = 1 WHERE id = ?');
            $stmt->execute([$hash, 'SuperAdmin', $admin['id']]);
        }
        echo "Da tao/cap nhat tai khoan quan tri admin@local.test.\n";
        if ($generatedPassword) {
            echo "Mat khau khoi tao (chi hien thi lan nay): {$initialPassword}\n";
            echo "Hay luu mat khau nay o noi an toan.\n";
        } else {
            echo "Da dung mat khau duoc cung cap qua ADMIN_INITIAL_PASSWORD.\n";
        }
    }
    echo "Da khoi tao {$cfg['dbname']} tai {$cfg['host']}:{$cfg['port']}; giu nguyen du lieu cu.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Khong khoi tao duoc CSDL: {$e->getMessage()}\n");
    exit(1);
}
