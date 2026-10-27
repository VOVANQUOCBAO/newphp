<?php

/** Cập nhật cấu trúc dữ liệu, giữ lại dữ liệu hiện có. */
final class migration_store_tools
{
    // Fixed, additive migration; no arbitrary SQL or data reset.
    public static function ready(PDO $pdo): bool
    {
        foreach (['lichsu_nhap','yeuthich','email_thanhtoan'] as $table) if (!$pdo->query("SHOW TABLES LIKE '$table'")->fetchColumn()) return false;
        return (bool)$pdo->query("SHOW COLUMNS FROM sanpham LIKE 'ngay_nhap'")->fetchColumn()
            && (bool)$pdo->query("SHOW COLUMNS FROM khachhang LIKE 'dia_chi'")->fetchColumn();
    }
    public static function run(PDO $pdo): void
    {
        foreach (['sanpham'=>['ngay_nhap'=>'DATE NULL'],'khachhang'=>['dia_chi'=>'VARCHAR(255) NULL']] as $table=>$fields) {
            $existing=$pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($fields as $name=>$definition) if (!in_array($name,$existing,true)) {
                try { $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$name` $definition"); }
                catch (PDOException $e) { if ((int)($e->errorInfo[1] ?? 0)!==1060) throw $e; }
            }
        }
        $pdo->exec(file_get_contents(dirname(__DIR__,2).'/database/store_tools.sql'));
    }
}
