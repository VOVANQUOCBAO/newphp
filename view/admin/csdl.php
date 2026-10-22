<?php
require_once __DIR__ . '/header_admin.php';
$h = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="container-fluid px-0">

    <?php if (!empty($loi)): ?><div class="alert alert-danger"><?=$h($loi)?></div><?php endif; ?>

    <form method="get" action="index.php" class="admin-table-tools"><input type="hidden" name="khu" value="admin"><input type="hidden" name="act" value="csdl"><div><label for="database-table">Bảng cần tra cứu</label><select class="form-select" name="bang" id="database-table"><?php foreach($tables as $name=>$label): ?><option value="<?=$h($name)?>" <?=$table===$name?'selected':''?>><?=$h($label)?> · <?=$h($tableCounts[$name])?> bản ghi</option><?php endforeach; ?></select></div><button type="submit" class="btn btn-primary">Xem bảng</button><a class="btn btn-outline-secondary" href="<?=$admin_ctrl_url?>&amp;act=kiemtra">Kiểm tra sai lệch</a></form>

    <section class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
            <div>
                <h5 class="mb-1 fw-bold"><?=$h($tables[$table])?> <code class="small"><?=$h($table)?></code></h5>
                <span class="small text-muted">Tổng <?=$h($totalRows)?> bản ghi · Trang <?=$h($page)?>/<?=$h($totalPages)?></span>
            </div>
            <span class="badge text-bg-info">Chỉ xem · tối đa <?=$h($pageSize)?> dòng mỗi trang</span>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm align-middle mb-0">
                <thead class="table-light"><tr>
                    <?php foreach ($visibleColumns as $column): ?><th scope="col" class="text-nowrap"><?=$h($column)?></th><?php endforeach; ?>
                </tr></thead>
                <tbody>
                    <?php if ($rows === []): ?>
                        <tr><td colspan="<?=max(1, count($visibleColumns))?>" class="text-center text-muted py-4">Bảng này chưa có bản ghi.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?><tr>
                            <?php foreach ($visibleColumns as $column): ?>
                                <?php $value = $row[$column] ?? null; ?>
                                <td class="text-break" style="min-width:100px;max-width:360px">
                                    <?php if ($value === null): ?><span class="text-muted fst-italic">NULL</span>
                                    <?php else: ?><span class="d-inline-block text-truncate" style="max-width:340px" title="<?=$h($value)?>"><?=$h($value)?></span><?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr><?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <?php if ($page > 1): ?><a class="btn btn-outline-primary btn-sm" href="index.php?khu=admin&amp;act=csdl&amp;bang=<?=$h($table)?>&amp;trang=<?=$page - 1?>">Trang trước</a><?php else: ?><span></span><?php endif; ?>
                <?php if ($page < $totalPages): ?><a class="btn btn-outline-primary btn-sm" href="index.php?khu=admin&amp;act=csdl&amp;bang=<?=$h($table)?>&amp;trang=<?=$page + 1?>">Trang sau</a><?php endif; ?>
            </div>
        <?php endif; ?>
        <div class="card-footer small text-muted">
            Cột mật khẩu, token, secret và OTP không hiển thị. Dùng các trang quản trị nghiệp vụ để thêm hoặc cập nhật dữ liệu.
        </div>
    </section>
</div>
