<?php
require_once BASE_PATH.'/model/yeuthich.php';
require_once BASE_PATH.'/model/thongbao_thanhtoan.php';
require_once BASE_PATH.'/support/scripts/migrate_store_tools.php';
$toolsPreview=static function(string $name,string $html): void {
    $html=preg_replace('/name="_csrf" value="[^"]*"/','name="_csrf" value=""',$html);
    $html=str_replace('<head>','<head><base href="/">',$html);
    $html=str_replace('</body>','<script>document.addEventListener("submit",e=>e.preventDefault(),true);</script></body>',$html);
    file_put_contents(__DIR__.'/previews/'.$name.'.html',$html);
};
$toolsCounts=counts($adminPdo,$testDb);
migration_store_tools::run($pdo);migration_store_tools::run($pdo);
check('TOOLS migration lap lai giu nguyen tat ca ban ghi',migration_store_tools::ready($pdo) && counts($adminPdo,$testDb)===$toolsCounts);
$toolsSetup=request($base,$adminCookie,'khoitaotienich',null,$adminPath);
$toolsSetupCsrf=request($base,$adminCookie,'khoitaotienich',['_csrf'=>''],$adminPath);
check('TOOLS migration GET chi hien form, POST bat buoc CSRF',$toolsSetup['status']===200 && $toolsSetupCsrf['status']===403 && counts($adminPdo,$testDb)===$toolsCounts);
$toolsDm=$dm->them('Store tools');$toolsKh=khTest('store-tools@example.test');$toolsOther=khTest('store-tools-other@example.test');
$toolsCookie='';request($base,$toolsCookie,'dangnhap',['email'=>'store-tools@example.test','mat_khau'=>'123456']);
$toolsProduct=['ma_sp'=>'TOOLS-NEW','ten_sp'=>'Tools new product','id_danhmuc'=>$toolsDm,'gia'=>100000,'so_luong_goc'=>10,'ngay_nhap'=>date('Y-m-d',strtotime('-2 days'))];
$toolsCreate=request($base,$employeeCookie,'themsanpham',$toolsProduct,$adminPath);$toolsSp=$sp->layTheoMa('TOOLS-NEW');$toolsId=(int)$toolsSp['id'];
$toolsList=$sp->layDSAdmin();$last=end($toolsList);
check('TOOLS nhan vien them hang, hang moi xuat hien cuoi bang',empty($toolsCreate['data']['loi']) && (int)$last['id']===$toolsId && $toolsSp['ngay_nhap']===$toolsProduct['ngay_nhap']);
$receipts=$sp->select('SELECT * FROM lichsu_nhap WHERE id_sanpham=? ORDER BY id',[$toolsId]);
check('TOOLS nhap ban dau luu ngay so luong va nguoi nhap',count($receipts)===1 && (int)$receipts[0]['so_luong']===10 && $receipts[0]['ngay_nhap']===$toolsProduct['ngay_nhap'] && $receipts[0]['nguoi_nhap']==='HTTP Employee');
$toolsRestock=request($base,$managerCookie,'suasanpham',$toolsProduct+['id'=>$toolsId,'nhap_them'=>3,'ton_cu'=>10,'ngay_nhap_dot'=>date('Y-m-d')],$adminPath);
$receipts=$sp->select('SELECT * FROM lichsu_nhap WHERE id_sanpham=? ORDER BY id',[$toolsId]);
check('TOOLS nhap them ghi dot rieng va khong ghi de ngay nhap dau',empty($toolsRestock['data']['loi']) && count($receipts)===2 && (int)$receipts[1]['so_luong']===3 && $sp->layChiTiet($toolsId)['ngay_nhap']===$toolsProduct['ngay_nhap'] && ton($toolsId)===13);
$toolsEdit=request($base,$employeeCookie,'suasanpham',array_replace($toolsProduct,['so_luong_goc'=>13,'id'=>$toolsId,'ton_cu'=>13]),$adminPath);
check('TOOLS sua noi dung khong tao lich su nhap gia',empty($toolsEdit['data']['loi']) && count($sp->select('SELECT id FROM lichsu_nhap WHERE id_sanpham=?',[$toolsId]))===2);
$futureReceipt=$sp->sua($toolsId,['nhap_them'=>1,'ngay_nhap_dot'=>date('Y-m-d',strtotime('+1 day'))]);
check('TOOLS chan ngay nhap tuong lai, khong tang kho',!$futureReceipt['ok'] && ton($toolsId)===13);
$pdo->exec("CREATE TRIGGER fail_receipt BEFORE INSERT ON lichsu_nhap FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Receipt rollback'");
$failedReceipt=$sp->sua($toolsId,['nhap_them'=>2]);$pdo->exec('DROP TRIGGER fail_receipt');
check('TOOLS loi luu dot nhap rollback luon ton va tong nhap',!$failedReceipt['ok'] && ton($toolsId)===13 && (int)$sp->layChiTiet($toolsId)['so_luong_goc']===13);
$toolsRaw=request($base,$employeeCookie,'csdl',null,$adminPath.'&bang=sanpham');$rawIds=array_map('intval',array_column($toolsRaw['data']['rows'],'id'));$sorted=$rawIds;sort($sorted);
check('TOOLS tra cuu sanpham sap theo ID tang dan',$toolsRaw['status']===200 && $rawIds===$sorted);
$profile=['ho_ten'=>'Khách hàng cập nhật','sdt'=>'0901234567','ngay_sinh'=>'2000-05-15','dia_chi'=>'123 Nguyễn Huệ','id'=>$toolsOther,'email'=>'forged@example.test','hang'=>'Diamond','diem'=>99999];
$profileSave=request($base,$toolsCookie,'luuhoso',$profile);$profileDb=$kh->layTheoId($toolsKh);
check('TOOLS ho so luu dung chu tai khoan, khong sua email hang diem hoac nguoi khac',empty($profileSave['data']['loi']) && $profileDb['ho_ten']===$profile['ho_ten'] && $profileDb['dia_chi']===$profile['dia_chi'] && $profileDb['email']==='store-tools@example.test' && $profileDb['hang']==='Silver' && (int)$profileDb['diem']===0 && $kh->layTheoId($toolsOther)['ho_ten']==='Khach Test');
$profileCsrf=request($base,$toolsCookie,'luuhoso',$profile+['_csrf'=>'']);$profileGet=request($base,$toolsCookie,'luuhoso');
$badProfile=request($base,$toolsCookie,'luuhoso',array_replace($profile,['ngay_sinh'=>'2099-01-01']));
check('TOOLS ho so bat buoc POST CSRF va ngay sinh hop le',$profileCsrf['status']===403 && $profileGet['status']===405 && !empty($badProfile['data']['loi']) && $kh->layTheoId($toolsKh)['ngay_sinh']==='2000-05-15');
request($base,$toolsCookie,'themgiohang',['id'=>$toolsId,'sl'=>1]);$profileCheckout=requestHtml($base,$toolsCookie,'/controller/index.php?act=thanhtoan');
check('TOOLS thanh toan dien san dia chi va SDT tu ho so',str_contains($profileCheckout['body'],'123 Nguyễn Huệ') && str_contains($profileCheckout['body'],'0901234567'));
$toolsProfileHtml=requestHtml($base,$toolsCookie,'/controller/index.php?act=hoso');$toolsPreview('customer-profile',$toolsProfileHtml['body']);
$favorite=new yeuthich();$favPost=['id_sanpham'=>$toolsId,'luu'=>'1','id_khachhang'=>$toolsOther];
$favSave=request($base,$toolsCookie,'luuyeuthich',$favPost);request($base,$toolsCookie,'luuyeuthich',$favPost);
check('TOOLS yeu thich luu theo tai khoan, khong trung va khong gia mao chu',empty($favSave['data']['loi']) && count($favorite->layDS($toolsKh))===1 && $favorite->layDS($toolsOther)===[] && ton($toolsId)===13);
$favCsrf=request($base,$toolsCookie,'luuyeuthich',$favPost+['_csrf'=>'']);
$favGet=request($base,$toolsCookie,'luuyeuthich');$favPage=requestHtml($base,$toolsCookie,'/controller/index.php?act=yeuthich');
check('TOOLS yeu thich bat buoc POST CSRF, hien dung gia hien tai',$favCsrf['status']===403 && $favGet['status']===405 && str_contains($favPage['body'],'Tools new product') && str_contains($favPage['body'],'100.000'));
$toolsPreview('customer-favorites',$favPage['body']);
request($base,$toolsCookie,'luuyeuthich',array_replace($favPost,['luu'=>'0']));
check('TOOLS bo yeu thich khong xoa san pham',!$favorite->daLuu($toolsKh,$toolsId) && $sp->layChiTiet($toolsId)!==null);
$favorite->dat($toolsKh,$toolsId,true);$pdo->prepare('UPDATE sanpham SET ngung_ban=1 WHERE id=?')->execute([$toolsId]);
check('TOOLS hang ngung ban an khoi yeu thich va khong dat lai duoc',$favorite->layDS($toolsKh)===[] && !$favorite->dat($toolsOther,$toolsId,true)['ok']);
$sp->moBanLai($toolsId);
$mailModel=new thongbao_thanhtoan();$toolsOrder=dat($toolsKh,$toolsId);
check('TOOLS QR da thu tien da tao thu cam on dung mot don',count($mailModel->select('SELECT id_donhang FROM email_thanhtoan WHERE id_donhang=?',[$qrId]))===1);
check('TOOLS chua thu tien khong tao thu cam on',!$mailModel->ghiNhan($toolsOrder['id']) && !$mailModel->selectOne('SELECT id_donhang FROM email_thanhtoan WHERE id_donhang=?',[$toolsOrder['id']]));
toiTrangThai($toolsOrder['id'],donhang::THANH_CONG);$paidLetter=$mailModel->selectOne('SELECT * FROM email_thanhtoan WHERE id_donhang=?',[$toolsOrder['id']]);
check('TOOLS COD thanh cong tao mau cam on dung nguoi nhan tong tien va ten hang',$paidLetter && $paidLetter['nguoi_nhan']==='store-tools@example.test' && str_contains($paidLetter['noi_dung'],'130.000đ') && str_contains($paidLetter['noi_dung'],'Tools new product') && $paidLetter['trang_thai']==='loi');
$mailCount=$mailModel->selectOne('SELECT COUNT(*) c FROM email WHERE tieu_de LIKE ?', ['%Xác nhận thanh toán đơn #'.$toolsOrder['id']])['c'];
$mailModel->ghiNhan($toolsOrder['id']);$mailModel->ghiNhan($toolsOrder['id']);
check('TOOLS thanh toan lap khong tao nhieu thu cam on',count($mailModel->select('SELECT id_donhang FROM email_thanhtoan WHERE id_donhang=?',[$toolsOrder['id']]))===1 && $mailCount===$mailModel->selectOne('SELECT COUNT(*) c FROM email WHERE tieu_de LIKE ?', ['%Xác nhận thanh toán đơn #'.$toolsOrder['id']])['c']);
$testSender=new class extends thongbao_thanhtoan {
    public int $calls=0; public string $html='';
    protected function deliver(string $to,string $title,string $body,string $html): array { $this->calls++;$this->html=$html;return ['ok'=>true]; }
};
check('TOOLS gui lai thu loi qua transport ghi da gui va ngay gui',$testSender->gui($toolsOrder['id'])['ok']
    && $mailModel->selectOne('SELECT trang_thai FROM email_thanhtoan WHERE id_donhang=?',[$toolsOrder['id']])['trang_thai']==='da_gui'
    && str_contains($testSender->html,'BP Store') && str_contains($testSender->html,'THANH TOÁN THÀNH CÔNG')
    && str_contains($testSender->html,'Tools new product') && str_contains($testSender->html,'Theo dõi đơn hàng'));
check('TOOLS thu da gui khong gui lai va khong thu SMTP lan nua',!$testSender->gui($toolsOrder['id'])['ok'] && $testSender->calls===1);
$dh->yeuCauTra($toolsOrder['id'],$toolsKh,'Return test');$dh->capNhatTrangThai($toolsOrder['id'],donhang::TRA_HANG,'Received',true,true);
check('TOOLS don da tra khong gui thu thanh toan muon',!$mailModel->gui($toolsOrder['id'])['ok']);
foreach ([$managerCookie,$employeeCookie] as $toolsRoleCookie) {
    $toolsEscalate=request($base,$toolsRoleCookie,'themnhanvien',['ho_ten'=>'Forbidden admin','email'=>'forbidden-admin@example.test','mat_khau'=>'Forbidden12345','vai_tro'=>'SuperAdmin'],$adminPath);
    $toolsGrant=request($base,$toolsRoleCookie,'doivaitronhanvien',['id'=>$employeeId,'vai_tro'=>'SuperAdmin'],$adminPath);
    $toolsOwnerChange=request($base,$toolsRoleCookie,'doivaitronhanvien',['id'=>$adminId,'vai_tro'=>'NhanVien'],$adminPath);
    $toolsLockOwner=request($base,$toolsRoleCookie,'khoanhanvien',['id'=>$adminId,'trang_thai'=>0],$adminPath);
    $toolsSmtp=request($base,$toolsRoleCookie,'luucauhinhmail',[],$adminPath);
    check('TOOLS nhan vien/quan ly khong tao cap sua khoa admin',$toolsEscalate['status']===403 && $toolsGrant['status']===403 && !empty($toolsOwnerChange['data']['loi']) && !empty($toolsLockOwner['data']['loi']));
    check('TOOLS SMTP van kiem tra du lieu cau hinh khi nhan vien co quyen',$toolsSmtp['status']===200 && !empty($toolsSmtp['data']['loi']));
}
$toolsStaffCreated=request($base,$employeeCookie,'themnhanvien',['ho_ten'=>'Team manager','email'=>'team-manager@example.test','mat_khau'=>'TeamManager12345','vai_tro'=>'QuanLy'],$adminPath);
check('TOOLS nhan vien duoc tao tai khoan nhan vien/quan ly',empty($toolsStaffCreated['data']['loi']) && $nv->kiemTraDangNhap('team-manager@example.test','TeamManager12345')['nhanvien']['vai_tro']==='QuanLy');
foreach (['nhapkho','thongbao','nhanvien','voucher','danhgia'] as $toolsPage) {
    $toolsHtml=requestHtml($base,$employeeCookie,'/index.php?khu=admin&act='.$toolsPage);
    check('TOOLS nhan vien vao duoc trang '.$toolsPage,$toolsHtml['status']===200 && !str_contains($toolsHtml['body'],'Fatal error'));
    $toolsPreview('staff-'.$toolsPage,$toolsHtml['body']);
    if ($toolsPage==='nhanvien') check('TOOLS UI nhan vien khong co lua chon tao/cap SuperAdmin',!str_contains($toolsHtml['body'],'value="SuperAdmin"'));
}
$anonymousTools='';$anonymousProfile=request($base,$anonymousTools,'hoso');$anonymousFavorites=request($base,$anonymousTools,'yeuthich');
check('TOOLS ho so va yeu thich yeu cau dang nhap',$anonymousProfile['status']===302 && $anonymousFavorites['status']===302);
$mailTestBefore=counts($adminPdo,$testDb);
$mailTestGet=request($base,$adminCookie,'guithunghiemmail',null,$adminPath);
$mailTestCsrf=request($base,$adminCookie,'guithunghiemmail',['_csrf'=>''],$adminPath);
$mailTestGuest=request($base,$anonymousTools,'guithunghiemmail',[],$adminPath);
check('MAIL thu SMTP yeu cau dang nhap POST va CSRF',$mailTestGet['status']===405 && $mailTestCsrf['status']===403 && $mailTestGuest['status']===302);
$mailTestMissing=request($base,$employeeCookie,'guithunghiemmail',[],$adminPath);
$mailTestRepeat=request($base,$employeeCookie,'guithunghiemmail',[],$adminPath);
check('MAIL chua cau hinh SMTP khong bao thanh cong, gioi han gui lap',str_contains($mailTestMissing['data']['loi'] ?? '','Lưu cấu hình') && str_contains($mailTestRepeat['data']['loi'] ?? '','60 giây'));
check('MAIL kiem tra SMTP khong tao hay sua du lieu nghiep vu',counts($adminPdo,$testDb)===$mailTestBefore);
