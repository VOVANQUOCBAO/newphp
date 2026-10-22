<?php
// Runs in the same disposable database as test_luong.php; no production changes.
require_once BASE_PATH.'/support/scripts/migrate_voucher_scope.php';
$scopeModel=new voucher();
$scopeDm=$dm->them('Scope phones');$scopeOtherDm=$dm->them('Scope accessories');
$scopePhone=spTest($scopeDm,'SCOPE-PHONE-A',30,500000);
$scopePhoneB=spTest($scopeDm,'SCOPE-PHONE-B',30,200000);
$scopeAccessory=spTest($scopeOtherDm,'SCOPE-CABLE',30,300000);
$scopeKh=khTest('scope@test.local');
$scopeBase=['loai'=>'phantram','gia_tri'=>20,'so_luong'=>10,'ngay_bd'=>date('Y-m-d'),'ngay_kt'=>date('Y-m-d',strtotime('+1 day')),
    'don_toi_thieu'=>500000,'pham_vi'=>'danhmuc','id_danhmuc'=>$scopeDm];
function scopeVoucher(string $code,array $extra=[]): array {
    global $scopeBase;
    return (new voucher())->them(array_replace($scopeBase,['ma_code'=>$code],$extra));
}
$scCreated=scopeVoucher('SCOPE20');$scId=(int)$scCreated['id'];
$scRow=$scopeModel->layTheoCode('SCOPE20');
check('SCOPE tao voucher danh muc luu dung CSDL',$scCreated['ok'] && $scRow['pham_vi']==='danhmuc' && (int)$scRow['id_danhmuc']===$scopeDm && $scRow['id_sanpham']===null);
$invalidScope=scopeVoucher('SCOPE-BAD',['pham_vi'=>'random']);
$noTarget=scopeVoucher('SCOPE-NONE',['id_danhmuc'=>'']);
$wrongTarget=scopeVoucher('SCOPE-NOTFOUND',['pham_vi'=>'sanpham','id_sanpham'=>99999999]);
check('SCOPE tu choi pham vi la, thieu doi tuong va ID khong ton tai',empty($invalidScope['ok']) && empty($noTarget['ok']) && empty($wrongTarget['ok']));
$all=scopeVoucher('SCOPE-ALL',['pham_vi'=>'tatca','id_danhmuc'=>$scopeDm,'id_sanpham'=>$scopePhone]);
$allRow=$scopeModel->layTheoCode('SCOPE-ALL');
check('SCOPE toan cua hang khong giu ID danh muc/san pham cu',$all['ok'] && $allRow['id_danhmuc']===null && $allRow['id_sanpham']===null);
$scopeCart=giohang::chiTiet([$scopePhone=>1,$scopeAccessory=>2]);
$quote=$scopeModel->kiemTra('SCOPE20','Silver',$scopeCart['tong'],30000,false,$scopeCart['items']);
check('SCOPE gio hon hop chi giam hang thuoc danh muc', $quote['ok'] && $quote['tien_ap_dung']===500000.0 && $quote['giam']===100000.0 && $quote['tong']===1030000.0);
$minimum=scopeVoucher('SCOPE-MIN',['don_toi_thieu'=>600000]);
$q=$scopeModel->kiemTra('SCOPE-MIN','Silver',$scopeCart['tong'],30000,false,$scopeCart['items']);
check('SCOPE hang khac khong duoc cong vao nguong toi thieu',empty($q['ok']));
$onlyCable=giohang::chiTiet([$scopeAccessory=>2]);
$wrong=$scopeModel->kiemTra('SCOPE20','Silver',$onlyCable['tong'],30000,false,$onlyCable['items']);
check('SCOPE khong co hang phu hop thi tu choi, khong giam toan don',empty($wrong['ok']) && str_contains($wrong['loi'],'chỉ áp dụng'));
check('SCOPE khong co dong hang tin cay thi khong tu suy doan',empty($scopeModel->kiemTra('SCOPE20','Silver',1100000,30000)['ok']));
$product=scopeVoucher('SCOPE-PRODUCT',['pham_vi'=>'sanpham','id_sanpham'=>$scopePhone,'gia_tri'=>700000,'loai'=>'tien']);
$q=$scopeModel->kiemTra('SCOPE-PRODUCT','Silver',$scopeCart['tong'],30000,false,$scopeCart['items']);
check('SCOPE voucher tien khong vuot gia tri san pham duoc chon',$product['ok'] && $q['giam']===500000.0 && $q['tong']===630000.0);
$bOnly=scopeVoucher('SCOPE-ONLY-B',['pham_vi'=>'sanpham','id_sanpham'=>$scopePhoneB,'don_toi_thieu'=>0]);
$sameCategory=giohang::chiTiet([$scopePhoneB=>3]);
check('SCOPE voucher san pham khong ap dung cho san pham khac cung danh muc',empty($scopeModel->kiemTra('SCOPE-PRODUCT','Silver',$sameCategory['tong'],30000,false,$sameCategory['items'])['ok']));
$ship=scopeVoucher('SCOPE-SHIP',['loai'=>'mienship','gia_tri'=>0]);
$q=$scopeModel->kiemTra('SCOPE-SHIP','Silver',$scopeCart['tong'],30000,false,$scopeCart['items']);
check('SCOPE mien ship can co hang phu hop va dat nguong',$ship['ok'] && $q['phi_ship']===0.0 && empty($scopeModel->kiemTra('SCOPE-SHIP','Silver',$onlyCable['tong'],30000,false,$onlyCable['items'])['ok']));
// Preview and checkout obtain category/price from DB, never from submitted IDs or totals.
$scopeCookie='';request($base,$scopeCookie,'dangnhap',['email'=>'scope@test.local','mat_khau'=>'123456']);
request($base,$scopeCookie,'themgiohang',['id'=>$scopePhone,'sl'=>1]);request($base,$scopeCookie,'themgiohang',['id'=>$scopeAccessory,'sl'=>2]);
$scopePage=request($base,$scopeCookie,'thanhtoan');
$scopeBeforeStock=ton($scopePhone);$scopeBeforeUses=(int)$scopeModel->layTheoCode('SCOPE20')['so_luong'];
$previewPost=['intent'=>'voucher','phuong_thuc'=>'paypal','ma_voucher'=>'SCOPE20','dia_chi'=>'Scope test','sdt'=>'0900000000'];
$preview=request($base,$scopeCookie,'thanhtoan',$previewPost);
check('SCOPE preview PayPal dung cung quy tac voi CSDL',($preview['data']['voucherQuote']['giam'] ?? null)===100000 && ton($scopePhone)===$scopeBeforeStock && (int)$scopeModel->layTheoCode('SCOPE20')['so_luong']===$scopeBeforeUses);
$previewHtml=qrHtmlPost($base,$scopeCookie,'thanhtoan',$previewPost+['_checkout_csrf'=>$scopePage['data']['checkoutToken']]);
check('SCOPE checkout HTML hien doi tuong va tien hang duoc giam',str_contains($previewHtml['body'],'Danh mục: Scope phones') && str_contains($previewHtml['body'],'Tiền hàng được áp dụng'));
$fake=request($base,$scopeCookie,'thanhtoan',array_replace($previewPost,['ma_voucher'=>'SCOPE-ONLY-B','id_sanpham'=>$scopePhoneB,'id_danhmuc'=>$scopeDm,'eligible_total'=>'999999999']));
check('SCOPE gia mao ID/eligible total khong thay doi hang ap dung',empty($fake['data']['voucherQuote']['ok']));
$orderPost=['intent'=>'order','phuong_thuc'=>'bank','ma_voucher'=>'SCOPE20','dia_chi'=>'Scope test, do not ship','sdt'=>'0900000000','_order_key'=>$scopePage['data']['codCheckoutKey']];
$placed=request($base,$scopeCookie,'thanhtoan',$orderPost);
parse_str(parse_url($placed['location'],PHP_URL_QUERY) ?: '',$scopeParams);$scopeOrder=(int)($scopeParams['id'] ?? 0);$order=$dh->layTheoId($scopeOrder);
check('SCOPE dat QR -> don snapshot va so tien QR khop preview',$placed['status']===302 && (float)$order['tong_tien']===1030000.0 && $order['voucher_pham_vi']==='danhmuc' && (int)$order['voucher_id_doi_tuong']===$scopeDm && (float)$order['voucher_tien_ap_dung']===500000.0 && (float)(new thanhtoan_qr())->lay($scopeOrder)['amount_vnd']===1030000.0);
$tracking=requestHtml($base,$scopeCookie,'/controller/index.php?act=theodoidonhang&id='.$scopeOrder);
check('SCOPE trang khach doc ban chup pham vi tu don',str_contains($tracking['body'],'Danh mục: Scope phones') && str_contains($tracking['body'],'Tiền hàng được áp dụng'));
$oldSnapshot=$order['voucher_doi_tuong'];$edit=$scopeModel->suaPhamVi($scId,['pham_vi'=>'sanpham','id_sanpham'=>$scopeAccessory]);
$q=$scopeModel->kiemTra('SCOPE20','Silver',$scopeCart['tong'],30000,false,$scopeCart['items']);$oldOrder=$dh->layTheoId($scopeOrder);
check('SCOPE sua voucher chi anh huong don moi, khong doi snapshot/tong don cu',$edit['ok'] && $q['giam']===120000.0 && $oldOrder['voucher_doi_tuong']===$oldSnapshot && (float)$oldOrder['tong_tien']===1030000.0);
$beforeCancelUses=(int)$scopeModel->layTheoCode('SCOPE20')['so_luong'];$cancel=$dh->huyDon($scopeOrder,$scopeKh,'Scope cancellation');
check('SCOPE huy sau khi sua pham vi van hoan dung luot va kho',$cancel['ok'] && ton($scopePhone)===$scopeBeforeStock && (int)$scopeModel->layTheoCode('SCOPE20')['so_luong']===$beforeCancelUses+1);
$scopeModel->suaPhamVi($scId,['pham_vi'=>'danhmuc','id_danhmuc'=>$scopeDm]);
// Changing scope after a successful preview invalidates a new checkout atomically.
$oldMatch=$scopeModel->kiemTra('SCOPE20','Silver',$scopeCart['tong'],30000,false,$scopeCart['items']);
$scopeModel->suaPhamVi($scId,['pham_vi'=>'sanpham','id_sanpham'=>$scopePhoneB]);$beforeMoved=ton($scopePhone);
$failOrder=$dh->taoDon($scopeKh,[$scopePhone=>1,$scopeAccessory=>2],['dia_chi'=>'Invalidated preview','sdt'=>'0900000000','bank'=>[]],'SCOPE20');
check('SCOPE doi pham vi sau preview thi kiem tra lai, rollback kho',$oldMatch['ok'] && empty($failOrder['ok']) && ton($scopePhone)===$beforeMoved);
$scopeModel->suaPhamVi($scId,['pham_vi'=>'danhmuc','id_danhmuc'=>$scopeDm]);
$pdo->exec("UPDATE sanpham SET id_danhmuc=$scopeOtherDm WHERE id=$scopePhoneB");
$movedOrder=$dh->taoDon($scopeKh,[$scopePhoneB=>3],['dia_chi'=>'Moved category','sdt'=>'0900000000'],'SCOPE20');
check('SCOPE doi danh muc san pham duoc doc lai khi dat',empty($movedOrder['ok']));
$adminData=['ma_code'=>'SCOPE-HTTP','loai'=>'phantram','gia_tri'=>10,'so_luong'=>3,'ngay_bd'=>date('Y-m-d'),'ngay_kt'=>date('Y-m-d',strtotime('+1 day')),'pham_vi'=>'sanpham','id_sanpham'=>$scopeAccessory];
$httpCreate=request($base,$adminCookie,'themvoucher',$adminData,$adminPath);$httpVc=$scopeModel->layTheoCode('SCOPE-HTTP');
check('SCOPE admin form -> model -> DB luu san pham dung',$httpCreate['status']===200 && $httpVc['pham_vi']==='sanpham' && (int)$httpVc['id_sanpham']===$scopeAccessory);
$adminHtml=requestHtml($base,$adminCookie,'/index.php?khu=admin&act=voucher');
check('SCOPE admin hien lua chon tao/sua va doi tuong da luu',str_contains($adminHtml['body'],'Theo danh mục') && str_contains($adminHtml['body'],'Theo sản phẩm') && str_contains($adminHtml['body'],'voucher-scope.js') && str_contains($adminHtml['body'],'Sản phẩm: San pham SCOPE-CABLE'));
$scopeUpdate=['id'=>$httpVc['id'],'pham_vi'=>'danhmuc','id_danhmuc'=>$scopeDm];
$managerEdit=request($base,$managerCookie,'suaphamvivoucher',$scopeUpdate,$adminPath);$badCsrf=request($base,$adminCookie,'suaphamvivoucher',$scopeUpdate+['_csrf'=>''],$adminPath);
check('SCOPE sua pham vi bat buoc quyen admin va CSRF',$managerEdit['status']===403 && $badCsrf['status']===403 && $scopeModel->layTheoCode('SCOPE-HTTP')['pham_vi']==='sanpham');
$updated=request($base,$adminCookie,'suaphamvivoucher',$scopeUpdate,$adminPath);
check('SCOPE admin sua pham vi ma cu khong can xoa ma',$updated['status']===200 && $scopeModel->layTheoCode('SCOPE-HTTP')['pham_vi']==='danhmuc' && (int)$scopeModel->layTheoCode('SCOPE-HTTP')['so_luong']===3);
$archive=spTest($scopeDm,'SCOPE-ARCHIVED',2,100000);$pdo->exec("UPDATE sanpham SET ngung_ban=1 WHERE id=$archive");
check('SCOPE khong tao voucher nham san pham ngung ban',empty(scopeVoucher('SCOPE-ARCHIVE',['pham_vi'=>'sanpham','id_sanpham'=>$archive])['ok']));
$archivedTarget=scopeVoucher('SCOPE-TARGET-LATER',['pham_vi'=>'sanpham','id_sanpham'=>$scopeAccessory,'don_toi_thieu'=>0]);
$pdo->exec("UPDATE sanpham SET ngung_ban=1 WHERE id=$scopeAccessory");
check('SCOPE doi tuong ngung ban thi voucher khong thanh toan cua hang',empty($scopeModel->kiemTra('SCOPE-TARGET-LATER','Silver',$scopeCart['tong'],30000,false,$scopeCart['items'])['ok']));
// Verify first migration against an actual legacy table layout with existing rows.
$legacyScopeDb=$testDb.'_scope';
try {
    if (!preg_match('/^webbanhang_mvc_test_[a-f0-9]{12}_scope$/',$legacyScopeDb)) throw new RuntimeException('Invalid test DB');
    $adminPdo->exec("CREATE DATABASE `$legacyScopeDb` CHARACTER SET utf8mb4");
    $legacyPdo=new PDO("mysql:host={$cfg['host']};port={$cfg['port']};dbname=$legacyScopeDb;charset=utf8mb4",$cfg['user'],$cfg['pass'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $legacyPdo->exec("CREATE TABLE voucher(id INT PRIMARY KEY,ma_code VARCHAR(50),so_luong INT); CREATE TABLE donhang(id INT PRIMARY KEY,tong_tien DECIMAL(12,2)); INSERT INTO voucher VALUES(1,'IPHONE18',3); INSERT INTO donhang VALUES(1,123000)");
    migrateVoucherScope($legacyPdo);migrateVoucherScope($legacyPdo);
    $legacyVoucher=$legacyPdo->query('SELECT * FROM voucher')->fetch(PDO::FETCH_ASSOC);$legacyOrder=$legacyPdo->query('SELECT * FROM donhang')->fetch(PDO::FETCH_ASSOC);
    check('SCOPE migration lap lai giu voucher cu toan cua hang va tong don',$legacyVoucher['pham_vi']==='tatca' && $legacyVoucher['so_luong']==3 && $legacyVoucher['id_danhmuc']===null && $legacyVoucher['id_sanpham']===null && $legacyOrder['tong_tien']==='123000.00' && $legacyOrder['voucher_pham_vi']===null);
} finally { $legacyPdo=null; if (preg_match('/^webbanhang_mvc_test_[a-f0-9]{12}_scope$/',$legacyScopeDb)) $adminPdo->exec("DROP DATABASE IF EXISTS `$legacyScopeDb`"); }
