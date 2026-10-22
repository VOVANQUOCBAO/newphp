<?php
include __DIR__ . '/header_admin.php';
$admin_url = 'index.php?khu=admin';
$tongDoanhThu = array_sum(array_map(static fn(array $row): float => (float)$row['doanh_thu'], $doanh_thu));
$tongDon = array_sum(array_map(static fn(array $row): int => (int)$row['so_don'], $doanh_thu));
?>
<div class="container-fluid">
    <?php if ($loi !== null): ?><div class="alert alert-danger"><?=$e($loi)?></div><?php endif; ?>
    <p class="small text-muted mb-2">Nhóm toàn bộ dữ liệu theo:</p><nav class="btn-group mb-4" aria-label="Nhóm doanh thu">
        <?php foreach (['tuan' => 'Tuần', 'thang' => 'Tháng', 'nam' => 'Năm'] as $period => $label): ?>
            <a class="btn <?=$ky === $period ? 'btn-primary' : 'btn-outline-primary'?>" href="<?=$admin_url?>&amp;act=doanhthu&amp;ky=<?=$period?>"><?=$label?></a>
        <?php endforeach; ?>
    </nav>
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card p-3"><div class="text-muted">Doanh thu đơn thành công</div><strong><?=number_format($tongDoanhThu, 0, ',', '.')?>đ</strong></div></div>
        <div class="col-md-4"><div class="card p-3"><div class="text-muted">Số đơn thành công</div><strong><?=$e($tongDon)?></strong></div></div>
        <div class="col-md-4"><div class="card p-3"><div class="text-muted">Giá trị trung bình</div><strong><?=number_format($tongDon ? $tongDoanhThu / $tongDon : 0, 0, ',', '.')?>đ</strong></div></div>
    </div>
    <section class="card mb-4"><div class="card-header fw-bold">Doanh thu theo kỳ</div><div class="table-responsive">
        <table class="table table-hover mb-0"><thead><tr><th>Kỳ</th><th>Số đơn</th><th>Doanh thu</th></tr></thead>
            <tbody><?php foreach ($doanh_thu as $row): ?><tr><td><?=$e($row['nhan'])?></td><td><?=$e($row['so_don'])?></td><td><?=number_format((float)$row['doanh_thu'], 0, ',', '.')?>đ</td></tr><?php endforeach; ?></tbody>
        </table></div>
        <?php if ($doanh_thu === []): ?><p class="text-muted p-3 mb-0">Chưa có đơn hàng thành công trong dữ liệu.</p><?php endif; ?>
    </section>
    <div class="row g-3">
        <div class="col-lg-6"><section class="card"><div class="card-header fw-bold">Sản phẩm bán chạy</div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Mã</th><th>Sản phẩm</th><th>Đã bán</th></tr></thead><tbody>
            <?php foreach ($ban_chay as $sp): ?><tr><td><?=$e($sp['ma_sp'])?></td><td><?=$e($sp['ten_sp'])?></td><td><?=$e($sp['da_ban'])?></td></tr><?php endforeach; ?>
        </tbody></table></div><?php if ($ban_chay === []): ?><p class="text-muted p-3 mb-0">Chưa có sản phẩm bán thành công.</p><?php endif; ?></section></div>
        <div class="col-lg-6"><section class="card"><div class="card-header fw-bold">Sản phẩm bán chậm</div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Mã</th><th>Sản phẩm</th><th>Tồn</th><th>Đã bán</th></tr></thead><tbody>
            <?php foreach ($ban_e as $sp): ?><tr><td><?=$e($sp['ma_sp'])?></td><td><?=$e($sp['ten_sp'])?></td><td><?=$e($sp['so_luong_ton'])?></td><td><?=$e($sp['da_ban'])?></td></tr><?php endforeach; ?>
        </tbody></table></div><?php if ($ban_e === []): ?><p class="text-muted p-3 mb-0">Chưa có sản phẩm bán chậm.</p><?php endif; ?></section></div>
    </div>
</div>
