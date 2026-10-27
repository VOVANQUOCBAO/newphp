<?php
require_once BASE_PATH.'/model/diem_thanhvien.php';
$pointsModel=new diem_thanhvien();
$oopKh=khTest('oop-member@example.test');
$oopOther=khTest('oop-other@example.test');
$oopDm=$dm->them('OOP membership');
$oopProduct=$sp->them(['ma_sp'=>'OOP-MEMBER','ten_sp'=>'iPhone 14 kiểm tra <ảnh>','id_danhmuc'=>$oopDm,'gia'=>470500,
    'so_luong_goc'=>8,'hinh'=>'catalog/iphone14.webp','anh_phu'=>"catalog/iphone13.webp\ncatalog/iphone16.webp",'mo_ta'=>'Mô tả <script> an toàn','thong_so'=>'128GB · OLED','thuong_hieu'=>'Apple','bao_hanh'=>'12 tháng']);
$oopSp=(int)$oopProduct['id'];
$oopOrder=dat($oopKh,$oopSp);
$oopId=(int)$oopOrder['id'];
$report=$pointsModel->baoCao($oopKh);
check('OOP diem don cho xac nhan chi la du kien, chua cong', $report['tien_do']['diem']===0 && $report['don_hang'][0]['diem_du_kien']===500 && (int)$report['don_hang'][0]['diem_da_cong']===0);
toiTrangThai($oopId,donhang::THANH_CONG);
$report=$pointsModel->baoCao($oopKh);
check('OOP don thanh cong cong diem gom ship va len Gold', $report['tien_do']['diem']===500 && $report['khach']['hang']==='Gold' && $report['diem_don_hang']===500 && $report['chenh_lech']===0);
check('OOP lap thanh cong khong cong diem lan hai', !$dh->capNhatTrangThai($oopId,donhang::THANH_CONG)['ok'] && $pointsModel->baoCao($oopKh)['tien_do']['diem']===500);
$oopCookie='';request($base,$oopCookie,'dangnhap',['email'=>'oop-member@example.test','mat_khau'=>'123456']);
$oopOwn=request($base,$oopCookie,'diemthanhvien&id='.$oopOther);
check('OOP user chi xem diem minh du gia mao ID khach khac', $oopOwn['status']===200 && (int)$oopOwn['data']['bao_cao']['khach']['id']===$oopKh);
$oopGuest='';
check('OOP khach vang lai khong xem diem va admin', request($base,$oopGuest,'diemthanhvien')['status']===302 && request($base,$oopGuest,'chitietsanpham&id='.$oopSp,null,$adminPath)['status']===302);
foreach ([$adminCookie,$managerCookie,$employeeCookie] as $oopStaff) {
    $detail=request($base,$oopStaff,'chitietsanpham&id='.$oopSp,null,$adminPath);
    $points=request($base,$oopStaff,'diemthanhvien&id='.$oopKh,null,$adminPath);
    check('OOP admin/quan ly/nhan vien xem anh, kho, thong so va doi chieu diem', $detail['status']===200 && $detail['data']['sanpham']['hinh']==='catalog/iphone14.webp' && $detail['data']['sanpham']['thong_so']==='128GB · OLED' && count($detail['data']['sanpham']['lichsu_kho'])>0 && $points['status']===200 && $points['data']['bao_cao']['diem_don_hang']===500);
}
check('OOP chi tiet admin xu ly ID khong ton tai',request($base,$adminCookie,'chitietsanpham&id=2147483647',null,$adminPath)['status']===404 && request($base,$adminCookie,'diemthanhvien&id=2147483647',null,$adminPath)['status']===404);
$oopDetailHtml=requestHtml($base,$adminCookie,'/index.php?khu=admin&act=chitietsanpham&id='.$oopSp);
$oopUserHtml=requestHtml($base,$oopCookie,'/controller/index.php?act=diemthanhvien');
$oopAdminHtml=requestHtml($base,$adminCookie,'/index.php?khu=admin&act=diemthanhvien&id='.$oopKh);
check('OOP HTML admin hien album va escape mo ta', str_contains($oopDetailHtml['body'],'catalog/iphone14.webp') && str_contains($oopDetailHtml['body'],'catalog/iphone16.webp') && str_contains($oopDetailHtml['body'],'&lt;script&gt;') && !str_contains($oopDetailHtml['body'],'Mô tả <script>'));
check('OOP HTML diem hien cong thuc, tien do va diem theo don',str_contains($oopUserHtml['body'],'Cách tính điểm') && str_contains($oopUserHtml['body'],'1.500 điểm') && str_contains($oopUserHtml['body'],'Đối chiếu điểm theo đơn') && str_contains($oopAdminHtml['body'],'Gold'));
$toolsPreview('oop-product-detail',$oopDetailHtml['body']);
$toolsPreview('oop-member-points',$oopUserHtml['body']);
$toolsPreview('oop-admin-points',$oopAdminHtml['body']);
$sp->sua($oopSp,['hinh'=>'catalog/iphone13.webp']);
$oopSender=new class extends thongbao_thanhtoan {
    public string $html='';
    protected function deliver(string $to,string $title,string $body,string $html): array { $this->html=$html;return ['ok'=>true]; }
};
check('OOP email lay anh snapshot khi san pham da doi anh', $oopSender->gui($oopId)['ok'] && str_contains($oopSender->html,'catalog/iphone14.webp') && !str_contains($oopSender->html,'catalog/iphone13.webp'));
$sp->xoa($oopSp);
check('OOP admin van xem duoc chi tiet hang ngung ban',request($base,$adminCookie,'chitietsanpham&id='.$oopSp,null,$adminPath)['status']===200 && $sp->layChiTiet($oopSp)===null);
$dh->yeuCauTra($oopId,$oopKh,'Kiểm tra thu hồi điểm');
$oopReturn=$dh->capNhatTrangThai($oopId,donhang::TRA_HANG,'Đã nhận lại',true,true);
$report=$pointsModel->baoCao($oopKh);
check('OOP tra hang thu hoi diem va xuong Silver, khong tru lap', $oopReturn['ok'] && $report['tien_do']['diem']===0 && $report['khach']['hang']==='Silver' && $report['diem_don_hang']===0 && !$dh->capNhatTrangThai($oopId,donhang::TRA_HANG,'Lặp',true,true)['ok']);
check('OOP phan trang diem gioi han an toan', $pointsModel->baoCao($oopKh,PHP_INT_MAX)['trang']===1 && $pointsModel->baoCao($oopKh,-50)['trang']===1);
