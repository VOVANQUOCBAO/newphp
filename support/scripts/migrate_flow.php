<?php
// Additive migration, also used by setup and isolated integration databases.
function migrateFlow(PDO $pdo): void
{
    $pdo->exec(file_get_contents(__DIR__ . '/../../database/flow_tables.sql'));
    $pdo->exec(file_get_contents(__DIR__ . '/../../database/bank.sql'));
    require_once __DIR__.'/migrate_voucher_scope.php';
    migrateVoucherScope($pdo);
    $columns = [
        'danhmuc' => ['ngung_ban' => 'TINYINT NOT NULL DEFAULT 0'],
        'sanpham' => ['ngung_ban' => 'TINYINT NOT NULL DEFAULT 0', 'so_luong_loi' => 'INT NOT NULL DEFAULT 0', 'thuong_hieu' => 'VARCHAR(150) NULL', 'thong_so' => 'TEXT NULL', 'bao_hanh' => 'VARCHAR(255) NULL', 'anh_phu' => 'TEXT NULL'],
        'trahang' => ['so_tien_hoan'=>'DECIMAL(12,2) NULL','trang_thai_hoan'=>'VARCHAR(30) NULL','tham_chieu_hoan'=>'VARCHAR(255) NULL','nhap_lai_kho'=>'TINYINT NULL'],
        'voucher' => ['don_toi_thieu' => 'DECIMAL(12,2) NOT NULL DEFAULT 0'],
        'donhang' => ['tam_tinh' => 'DECIMAL(12,2) NULL', 'tien_giam' => 'DECIMAL(12,2) NULL', 'id_voucher' => 'INT NULL', 'ngay_giao' => 'DATETIME NULL', 'diem_da_cong' => 'INT NOT NULL DEFAULT 0', 'checkout_key' => 'CHAR(64) NULL UNIQUE'],
        'chitietdonhang' => ['ten_sp_snapshot' => 'VARCHAR(255) NULL', 'ma_sp_snapshot' => 'VARCHAR(50) NULL', 'hinh_snapshot' => 'VARCHAR(255) NULL'],
    ];
    foreach ($columns as $table => $fields) {
        $existing = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($fields as $name => $definition) {
            if (!in_array($name, $existing, true)) $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$name` $definition");
        }
    }
    $checks = [
        'sanpham' => ['ck_sp_flow' => 'gia>=0 AND so_luong_goc>=0 AND so_luong_ton>=0 AND so_luong_ton<=so_luong_goc AND so_luong_loi>=0'],
        'voucher' => ['ck_vc_flow' => "gia_tri>=0 AND so_luong>=0 AND don_toi_thieu>=0 AND ngay_bd<=ngay_kt AND (loai<>'phantram' OR gia_tri<=100)"],
        'danhgia' => ['ck_dg_flow' => 'so_sao BETWEEN 1 AND 5'],
        'chitietdonhang' => ['ck_ct_flow' => 'so_luong>0 AND don_gia>=0'],
        'donhang' => ['ck_dh_flow' => 'tong_tien>=0 AND phi_ship>=0'],
        'khuyenmai' => ['ck_km_flow' => 'phan_tram BETWEEN 0 AND 100 AND ngay_bd<=ngay_kt'],
    ];
    $existing = $pdo->query('SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE()')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($checks as $table => $constraints) foreach ($constraints as $name => $condition) {
        if (!in_array($name, $existing, true)) {
            if ((int)$pdo->query("SELECT COUNT(*) FROM `$table` WHERE NOT ($condition)")->fetchColumn() === 0) {
                $pdo->exec("ALTER TABLE `$table` ADD CONSTRAINT `$name` CHECK ($condition)");
            } else error_log("Chưa thêm $name: cần đối chiếu dữ liệu cũ của $table");
        }
    }
    foreach (['danhmuc' => ['uq_dm_ten_flow','ten_danhmuc'], 'danhgia' => ['uq_dg_owner_flow','id_sanpham,id_khachhang']] as $table => [$name,$fields]) {
        if (!in_array($name,$existing,true) && !$pdo->query("SELECT 1 FROM `$table` GROUP BY $fields HAVING COUNT(*)>1 LIMIT 1")->fetchColumn()) {
            $pdo->exec("ALTER TABLE `$table` ADD CONSTRAINT `$name` UNIQUE ($fields)");
        }
    }
}
