<?php $tableEscape=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); ?>
<div class="admin-table-tools">
    <div><label for="<?=$tableEscape($tableId)?>-search"><?=$tableEscape($searchLabel ?? 'Tìm kiếm')?></label><input type="search" class="form-control" id="<?=$tableEscape($tableId)?>-search" data-admin-search="<?=$tableEscape($tableId)?>" placeholder="<?=$tableEscape($searchPlaceholder ?? 'Nhập từ khóa…')?>"></div>
    <?php if(!empty($statusOptions)): ?><div><label for="<?=$tableEscape($tableId)?>-status">Trạng thái</label><select class="form-select" id="<?=$tableEscape($tableId)?>-status" data-admin-status="<?=$tableEscape($tableId)?>"><option value="">Tất cả trạng thái</option><?php foreach($statusOptions as $value=>$label): ?><option value="<?=$tableEscape($value)?>"><?=$tableEscape($label)?></option><?php endforeach; ?></select></div><?php endif; ?>
    <span role="status" aria-live="polite" data-admin-count="<?=$tableEscape($tableId)?>"></span>
</div>
