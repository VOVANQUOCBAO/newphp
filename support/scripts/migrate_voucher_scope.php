<?php

/** Cập nhật cấu trúc dữ liệu, giữ lại dữ liệu hiện có. */
final class migration_voucher_scope
{
    // Additive and repeatable. Existing vouchers remain store-wide; old order totals stay intact.
    public static function run(PDO $pdo): void
    {
        $tables = [
            'voucher'=>['pham_vi'=>"VARCHAR(20) NOT NULL DEFAULT 'tatca'",'id_danhmuc'=>'INT NULL','id_sanpham'=>'INT NULL'],
            'donhang'=>['voucher_pham_vi'=>'VARCHAR(20) NULL','voucher_id_doi_tuong'=>'INT NULL','voucher_doi_tuong'=>'VARCHAR(255) NULL','voucher_tien_ap_dung'=>'DECIMAL(12,2) NULL'],
        ];
        foreach ($tables as $table=>$fields) {
            $existing=$pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($fields as $name=>$definition) if (!in_array($name,$existing,true)) {
                try { $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$name` $definition"); }
                catch (PDOException $e) { if ((int)($e->errorInfo[1] ?? 0)!==1060) throw $e; }
            }
        }
    }
}
