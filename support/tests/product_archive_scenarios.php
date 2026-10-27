<?php
// Runs only from test_luong.php in its disposable database.
$paDm = $dm->them('Archive workflow phones');
$paKh = khTest('product-archive@example.test');
$paId = spTest($paDm, 'ARCHIVE-WORKFLOW', 5, 100000);
vcTest('ARCHIVE-VC', ['gia_tri' => 10000, 'so_luong' => 2]);
$paOrder = dat($paKh, $paId, 1, 'ARCHIVE-VC');
$paBefore = $dh->layTrangThai($paOrder['id']);
$paLedger = $sp->select('SELECT * FROM lichsu_kho WHERE id_sanpham=? ORDER BY id', [$paId]);
$paArchive = request($base, $adminCookie, 'xoasanpham', ['id' => $paId], $adminPath);
$paRaw = $sp->layTheoMa('ARCHIVE-WORKFLOW');
check('ARCHIVE HTTP xoa hang da dat: ngung ban, giu nguyen don/kho/voucher',
    $paArchive['status'] === 200 && empty($paArchive['data']['loi']) && (int)$paRaw['ngung_ban'] === 1
    && (int)$paRaw['so_luong_ton'] === 4 && (int)$vc->layTheoCode('ARCHIVE-VC')['so_luong'] === 1
    && $paBefore === $dh->layTrangThai($paOrder['id'])
    && $paLedger === $sp->select('SELECT * FROM lichsu_kho WHERE id_sanpham=? ORDER BY id', [$paId]));
check('ARCHIVE an khoi catalog, tim kiem, chi tiet va goi y', $sp->layChiTiet($paId) === null
    && $sp->demTimKiem('ARCHIVE-WORKFLOW') === 0
    && !in_array($paId, array_map('intval', array_column($sp->layDS(), 'id')), true)
    && !in_array($paId, array_map('intval', array_column($sp->lienQuan(0, $paDm), 'id')), true));
$paList = request($base, $adminCookie, 'sanpham', null, $adminPath . '&trang_thai=ngungban');
$paActive = request($base, $adminCookie, 'sanpham', null, $adminPath);
check('ARCHIVE admin tach danh sach dang ban va da ngung ban', !empty($paList['data']['xem_ngung_ban'])
    && in_array($paId, array_map('intval', array_column($paList['data']['ds_sanpham'], 'id')), true)
    && !in_array($paId, array_map('intval', array_column($paActive['data']['ds_sanpham'], 'id')), true));
$paHtml = requestHtml($base, $adminCookie, '/index.php?khu=admin&act=sanpham&trang_thai=ngungban');
check('ARCHIVE HTML co nut mo ban lai va khong dan khach toi chi tiet da an',
    str_contains($paHtml['body'], 'act=mobanlaisanpham') && !str_contains($paHtml['body'], 'act=chitiet&amp;id=' . $paId . '"'));
check('ARCHIVE chan them gio va checkout moi cho hang da ngung ban', !giohang::them($paId, 1)['ok'] && !dat($paKh, $paId)['ok']);
$paTwice = $sp->xoa($paId);
check('ARCHIVE lap thao tac khong sua ton/voucher hoac xoa don', $paTwice['ok'] && $paTwice['ngung_ban']
    && (int)$sp->layTheoMa('ARCHIVE-WORKFLOW')['so_luong_ton'] === 4 && $paBefore === $dh->layTrangThai($paOrder['id']));
$paCancel = $dh->huyDon($paOrder['id'], $paKh, 'Cancel archived product test');
check('ARCHIVE huy don cu van hoan kho va voucher, khong tu mo ban lai', $paCancel['ok']
    && (int)$sp->layTheoMa('ARCHIVE-WORKFLOW')['so_luong_ton'] === 5
    && (int)$sp->layTheoMa('ARCHIVE-WORKFLOW')['ngung_ban'] === 1
    && (int)$vc->layTheoCode('ARCHIVE-VC')['so_luong'] === 2);
check('ARCHIVE huy lap khong hoan kho/luot lan hai', !$dh->huyDon($paOrder['id'], $paKh, 'Again')['ok']
    && (int)$sp->layTheoMa('ARCHIVE-WORKFLOW')['so_luong_ton'] === 5
    && (int)$vc->layTheoCode('ARCHIVE-VC')['so_luong'] === 2);
$paGet = request($base, $adminCookie, 'mobanlaisanpham', null, $adminPath);
$paCsrf = request($base, $adminCookie, 'mobanlaisanpham', ['id' => $paId, '_csrf' => ''], $adminPath);
$paManager = request($base, $managerCookie, 'mobanlaisanpham', ['id' => $paId], $adminPath);
$paEmployee = request($base, $employeeCookie, 'mobanlaisanpham', ['id' => $paId], $adminPath);
check('ARCHIVE mo ban lai cho nhan vien/quan ly, van bat buoc POST va CSRF', $paGet['status'] === 405 && $paCsrf['status'] === 403
    && $paManager['status'] === 200 && $paEmployee['status'] === 200 && $sp->layChiTiet($paId) !== null);
$paRestore = request($base, $adminCookie, 'mobanlaisanpham', ['id' => $paId], $adminPath);
check('ARCHIVE HTTP mo ban lai hien hang voi ton hien tai, giu don cu', $paRestore['status'] === 200
    && empty($paRestore['data']['loi']) && (int)$sp->layChiTiet($paId)['so_luong_ton'] === 5
    && $dh->layTrangThai($paOrder['id'])['trang_thai'] === donhang::DA_HUY);
// A return can arrive after the product has stopped selling too.
$paReturn = dat($paKh, $paId);
toiTrangThai($paReturn['id'], donhang::THANH_CONG);
$dh->yeuCauTra($paReturn['id'], $paKh, 'Return archived product');
$sp->xoa($paId);
check('ARCHIVE tra don cu hoan kho ma van giu ngung ban',
    $dh->capNhatTrangThai($paReturn['id'], donhang::TRA_HANG, 'Received return', true, true)['ok']
    && (int)$sp->layTheoMa('ARCHIVE-WORKFLOW')['so_luong_ton'] === 5 && $sp->layChiTiet($paId) === null);
$pdo->prepare('UPDATE danhmuc SET ngung_ban=1 WHERE id=?')->execute([$paDm]);
check('ARCHIVE khong mo ban lai khi danh muc da ngung ban', !$sp->moBanLai($paId)['ok'] && $sp->layChiTiet($paId) === null);
check('ARCHIVE ID khong ton tai tra loi co kiem soat', !$sp->moBanLai(2147483647)['ok'] && !$sp->xoa(2147483647)['ok']);
