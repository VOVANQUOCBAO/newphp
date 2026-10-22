<?php
// Business-rule checks and HTTP/role smoke checks, inside the disposable test DB.
require_once BASE_PATH.'/controller/controller.php';
$usDm=$dm->them('Admin workflow phones');
$usKh=khTest('admin-workflow@test.local');
$usProduct=['ma_sp'=>'ADMIN-FLOW','ten_sp'=>'Admin workflow phone','id_danhmuc'=>$usDm,'gia'=>500000,'so_luong_goc'=>10];
$usAdd=request($base,$adminCookie,'themsanpham',$usProduct,$adminPath);$usSp=$sp->layTheoMa('ADMIN-FLOW');
check('ADMIN them hang chi nhap mot so luong, ton ban dau khop',$usAdd['status']===200 && empty($usAdd['data']['loi']) && (int)$usSp['so_luong_ton']===10 && (int)$usSp['so_luong_goc']===10);
$usId=(int)$usSp['id'];
$usEdit=request($base,$adminCookie,'suasanpham',$usProduct+['id'=>$usId,'ton_cu'=>10,'nhap_them'=>2,'mo_ta'=>'Updated without recount'],$adminPath);
$usSp=$sp->layChiTiet($usId);
check('ADMIN sua noi dung/nhap them khong bat buoc ghi de ton kiem ke',empty($usEdit['data']['loi']) && (int)$usSp['so_luong_ton']===12 && (int)$usSp['so_luong_goc']===12 && $usSp['mo_ta']==='Updated without recount');
$usPromo=['ten_km'=>'Admin workflow sale','loai'=>'sanpham','id_sanpham'=>$usId,'id_danhmuc'=>99999999,'phan_tram'=>15,'ngay_bd'=>date('Y-m-d'),'ngay_kt'=>date('Y-m-d',strtotime('+5 days'))];
$usCreated=request($base,$adminCookie,'themkhuyenmai',$usPromo,$adminPath);
$usPromotion=$km->selectOne('SELECT * FROM khuyenmai WHERE ten_km=?',[$usPromo['ten_km']]);
check('ADMIN khuyen mai chi luu dung pham vi, bo ID thua',empty($usCreated['data']['loi']) && $usPromotion && (int)$usPromotion['id_sanpham']===$usId && $usPromotion['id_danhmuc']===null);
$usZero=$km->them(array_replace($usPromo,['ten_km'=>'Zero sale','phan_tram'=>0]));
$usDuplicate=$km->them(array_replace($usPromo,['ten_km'=>'Duplicate label']));
check('ADMIN chan giam 0 va chuong trinh trung cung doi tuong/thoi han',empty($usZero['ok']) && empty($usDuplicate['ok']));
$usCategory=$km->them(array_replace($usPromo,['ten_km'=>'Category sale','loai'=>'danhmuc','id_danhmuc'=>$usDm,'phan_tram'=>20]));
$usPrice=$km->giaSauKM($sp->layChiTiet($usId));
check('ADMIN nhieu khuyen mai lay muc cao nhat, khong cong don',$usCategory['ok'] && $usPrice['gia_km']===400000.0);
$usOrder=dat($usKh,$usId);
$usChange=$usPromo+['id'=>$usPromotion['id']];$usChange['phan_tram']=30;
$usManager=request($base,$managerCookie,'suakhuyenmai',$usChange,$adminPath);
$usCsrf=request($base,$adminCookie,'suakhuyenmai',$usChange+['_csrf'=>''],$adminPath);
$usGet=request($base,$adminCookie,'suakhuyenmai',null,$adminPath);
check('ADMIN sua khuyen mai bat buoc quyen POST va CSRF',$usManager['status']===403 && $usCsrf['status']===403 && $usGet['status']===405 && $km->giaSauKM($sp->layChiTiet($usId))['gia_km']===400000.0);
$usUpdated=request($base,$adminCookie,'suakhuyenmai',$usChange,$adminPath);
$usHome=requestHtml($base,$cookie,'/controller/index.php?act=timkiem&q=ADMIN-FLOW');
$usOldOrder=$dh->layTheoId((int)$usOrder['id']);
check('ADMIN sua khuyen mai doi gia moi, giu nguyen gia don cu',empty($usUpdated['data']['loi']) && $km->giaSauKM($sp->layChiTiet($usId))['gia_km']===350000.0 && (float)$usOldOrder['tong_tien']===430000.0 && str_contains($usHome['body'],'350.000'));
$usArchived=spTest($usDm,'ADMIN-ARCHIVED',1,100000);$pdo->exec("UPDATE sanpham SET ngung_ban=1 WHERE id=$usArchived");
check('ADMIN khong tao khuyen mai cho hang ngung ban',empty($km->them(array_replace($usPromo,['id_sanpham'=>$usArchived]))['ok']));
toiTrangThai((int)$usOrder['id'],donhang::DA_GIAO);
$usReview=$dg->them($usId,$usKh,1,'Purchased customer has a low rating');
$usHide=request($base,$adminCookie,'khoadanhgia',['id'=>$usReview['id'],'trang_thai'=>0,'khoa_khach'=>$usKh],$adminPath);
check('ADMIN an nhan xet khong khoa khach du it sao va payload cu',empty($usHide['data']['loi']) && (int)$kh->layTheoId($usKh)['trang_thai']===1 && $dg->layDSTheoSP($usId)===[]);
$usRestore=request($base,$adminCookie,'khoadanhgia',['id'=>$usReview['id'],'trang_thai'=>1],$adminPath);
$usInvalid=request($base,$adminCookie,'khoadanhgia',['id'=>$usReview['id'],'trang_thai'=>2],$adminPath);
check('ADMIN hien thi lai danh gia, tu choi trang thai la',empty($usRestore['data']['loi']) && !empty($usInvalid['data']['loi']) && count($dg->layDSTheoSP($usId))===1);
$usReviewForbidden=request($base,$managerCookie,'khoadanhgia',['id'=>$usReview['id'],'trang_thai'=>0],$adminPath);
check('ADMIN quyen QuanLy khong duoc an/hien thi danh gia',$usReviewForbidden['status']===403 && count($dg->layDSTheoSP($usId))===1);
$usMailBefore=(int)$pdo->query('SELECT COUNT(*) FROM email')->fetchColumn();
$usPasswordBefore=$nv->selectOne('SELECT SHA2(mat_khau,256) AS digest FROM nhanvien WHERE id=?',[$adminId])['digest'];
$usPasswordCsrf=request($base,$adminCookie,'doimatkhau',['_csrf'=>''], $adminPath);
check('ADMIN mo trang mat khau GET, POST thieu CSRF van bi chan',$usPasswordCsrf['status']===403 && $nv->selectOne('SELECT SHA2(mat_khau,256) AS digest FROM nhanvien WHERE id=?',[$adminId])['digest']===$usPasswordBefore);
$usNoFilter=request($base,$adminCookie,'guiquatang',['tieu_de'=>'Do not send','noi_dung'=>'No recipients filter'],$adminPath);
$usBadDate=request($base,$adminCookie,'guiquatang',['ngay'=>31,'thang'=>2,'tieu_de'=>'Do not send','noi_dung'=>'Invalid date'],$adminPath);
check('ADMIN chan gui thu nham tat ca khach va ngay khong ton tai',!empty($usNoFilter['data']['loi']) && !empty($usBadDate['data']['loi']) && (int)$pdo->query('SELECT COUNT(*) FROM email')->fetchColumn()===$usMailBefore);
foreach(['sanpham','khuyenmai','voucher','donhang','khachhang','danhgia','doanhthu','nhanvien','giaodien','doimatkhau','kiemtra','csdl'] as $usPage) {
    $usHtml=requestHtml($base,$adminCookie,'/index.php?khu=admin&act='.$usPage);
    $usDom=new DOMDocument();@$usDom->loadHTML('<?xml encoding="utf-8" ?>'.$usHtml['body']);
    check('ADMIN trang '.$usPage.' tai duoc va co mot vung noi dung chinh',$usHtml['status']===200 && $usDom->getElementsByTagName('main')->length===1 && $usDom->getElementsByTagName('h1')->length===1 && !str_contains($usHtml['body'],'Fatal error'));
    $usPreview=preg_replace('/name="_csrf" value="[^"]*"/','name="_csrf" value=""',$usHtml['body']);
    $usPreview=str_replace('<head>','<head><base href="/">',$usPreview);
    $usPreview=str_replace('</body>','<script>document.addEventListener("submit",e=>e.preventDefault(),true);</script></body>',$usPreview);
    file_put_contents(__DIR__.'/previews/admin-'.$usPage.'.html',$usPreview);
}
foreach([[$managerCookie,'QuanLy'],[$employeeCookie ?? $staffCookie ?? '', 'NhanVien']] as [$usRoleCookie,$usRole]) {
    if ($usRoleCookie==='') continue;
    $usRoleHtml=requestHtml($base,$usRoleCookie,'/index.php?khu=admin&act=sanpham');
    $usRoleDom=new DOMDocument();@$usRoleDom->loadHTML('<?xml encoding="utf-8" ?>'.$usRoleHtml['body']);
    $usAllowed=true;
    foreach($usRoleDom->getElementsByTagName('a') as $usLink) {
        $usHref=html_entity_decode($usLink->getAttribute('href'));parse_str(parse_url($usHref,PHP_URL_QUERY) ?? '',$usQuery);
        if (($usQuery['khu'] ?? '')==='admin' && !(new controller())->canAccessAdminAction($usQuery['act'] ?? '',$usRole)) $usAllowed=false;
    }
    check('ADMIN sidebar '.$usRole.' chi dan den chuc nang duoc cap quyen',$usRoleHtml['status']===200 && $usAllowed);
}
