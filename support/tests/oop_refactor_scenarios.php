<?php
// Included by test_luong.php: all fixtures belong to its disposable database.
$refactorCustomer = khTest('oop-password@example.test');
$refactorHash = $kh->layTheoId($refactorCustomer)['mat_khau'];
check('OOP khong chap nhan hash BCrypt lam mat khau dang nhap',
    !$kh->kiemTraDangNhap('oop-password@example.test', $refactorHash)['ok']
    && $kh->kiemTraDangNhap('oop-password@example.test', '123456')['ok']);
$refactorMd5 = md5('legacy-test-password');
$kh->update('khachhang', ['mat_khau' => $refactorMd5], 'id = ?', [$refactorCustomer]);
check('OOP khong chap nhan hash MD5 lam mat khau, van nang cap mat khau cu',
    !$kh->kiemTraDangNhap('oop-password@example.test', $refactorMd5)['ok']
    && $kh->kiemTraDangNhap('oop-password@example.test', 'legacy-test-password')['ok']
    && password_verify('legacy-test-password', $kh->layTheoId($refactorCustomer)['mat_khau']));
$refactorAdmin = $adminCookie;
$refactorMalformed = request($base, $refactorAdmin, 'sanpham&id[]=1', null, $adminPath);
check('OOP admin tu choi tham so mang bang HTTP 400 co kiem soat', $refactorMalformed['status'] === 400);
$refactorGuest = '';
$refactorMalformedGuest = request($base, $refactorGuest, 'sanpham&id[]=1', null, $adminPath);
check('OOP tham so loi duoc kiem tra truoc khi doc tai khoan admin', $refactorMalformedGuest['status'] === 400);
