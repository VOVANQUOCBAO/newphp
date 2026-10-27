<?php
$e = static fn(mixed $value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$mailList = is_array($ds_thu ?? null) ? $ds_thu : [];
$selectedMail = is_array($thuDangXem ?? null) ? $thuDangXem : null;
$mailTypeLabel = static fn(string $type): string => match ($type) {
    'phanhoi_khieunai' => 'Phản hồi khiếu nại',
    'khieunai' => 'Khiếu nại',
    default => $type,
};
?>
<main class="container my-5">
    <div class="mb-4">
        <h1 class="h2 fw-bold text-primary mb-1"><i class="fas fa-inbox me-2"></i>Hộp thư cá nhân</h1>
        <p class="text-muted mb-0">Thông báo đã gửi đến tài khoản của bạn.</p>
    </div>
    <?php if (!empty($loi)): ?><div class="alert alert-danger"><?=$e($loi)?></div><?php endif; ?>
    <div class="row g-4">
        <div class="col-lg-5">
            <section class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Thư (<?=count($mailList)?>)</div>
                <div class="list-group list-group-flush">
                    <?php foreach ($mailList as $mail): ?>
                        <a class="list-group-item list-group-item-action <?=$selectedMail && (int) $selectedMail['id'] === (int) $mail['id'] ? 'active' : ''?>" href="index.php?act=hopthu&amp;id=<?=(int) $mail['id']?>">
                            <div class="d-flex justify-content-between gap-2"><strong><?=$e($mail['tieu_de'])?></strong><small><?=$e($mail['ngay_gui'])?></small></div>
                            <div class="small text-truncate mt-1"><?=$e(mb_substr((string) $mail['noi_dung'], 0, 140))?></div>
                            <span class="badge text-bg-light mt-2"><?=$e($mailTypeLabel((string)$mail['loai']))?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if ($mailList === []): ?><p class="text-muted p-3 mb-0">Hộp thư chưa có thư nào.</p><?php endif; ?>
                </div>
            </section>
        </div>
        <div class="col-lg-7">
            <section class="card border-0 shadow-sm h-100">
                <?php if ($selectedMail): ?>
                    <div class="card-header bg-white">
                        <h2 class="h5 fw-bold mb-1"><?=$e($selectedMail['tieu_de'])?></h2>
                        <small class="text-muted"><?=$e($selectedMail['ngay_gui'])?> · <?=$e($mailTypeLabel((string)$selectedMail['loai']))?></small>
                    </div>
                    <div class="card-body"><div class="text-break"><?=nl2br($e($selectedMail['noi_dung']))?></div></div>
                <?php else: ?>
                    <div class="card-body d-flex align-items-center justify-content-center text-muted">Chọn một thư để xem nội dung.</div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</main>
