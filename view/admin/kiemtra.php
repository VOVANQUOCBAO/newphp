<?php include __DIR__ . '/header_admin.php'; ?>
<div class="container-fluid">
    <div class="admin-shortcuts"><a href="<?=$admin_ctrl_url?>&amp;act=csdl">Mở tra cứu dữ liệu gốc</a></div>
    <div class="table-responsive"><table class="table bg-white"><thead><tr><th>Nội dung</th><th>Số lượng</th><th>ID cần kiểm tra</th></tr></thead><tbody>
        <?php foreach ($bao_cao as $row): ?><tr><td><?=$e($row['noi_dung'])?></td><td><?=(int)$row['so_luong']?></td><td><?=$e(implode(', ', array_slice($row['ids'],0,100)))?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</div>
