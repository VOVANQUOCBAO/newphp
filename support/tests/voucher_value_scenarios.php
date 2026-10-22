<?php
// Uses test_luong.php's disposable database, never the live store.
$valueModel=new voucher();
foreach ([['tien',0],['phantram',0],['tien',0.004],['phantram',101],['tien',-1],['tien','NaN']] as $i=>$case) {
    $invalid=vcTest('VALUE-INVALID-'.$i,['loai'=>$case[0],'gia_tri'=>$case[1]]);
    check('VALUE tu choi muc giam khong hop le '.$i,empty($invalid['ok']) && $valueModel->layTheoCode('VALUE-INVALID-'.$i)===null);
}
$valueDm=$dm->them('Voucher value tests');
$valueSp=spTest($valueDm,'VALUE-PHONE',20,200000);
$valueKh=khTest('voucher-value@test.local');
$legacy=vcTest('VALUE-LEGACY');
$pdo->exec("UPDATE voucher SET gia_tri=0 WHERE id=".(int)$legacy['id']);
$valueCookie='';request($base,$valueCookie,'dangnhap',['email'=>'voucher-value@test.local','mat_khau'=>'123456']);
request($base,$valueCookie,'themgiohang',['id'=>$valueSp,'sl'=>1]);
$beforeLegacyOrders=count($dh->layTheoKhach($valueKh));$beforeLegacyStock=ton($valueSp);
$legacyPreview=request($base,$valueCookie,'thanhtoan',['intent'=>'voucher','phuong_thuc'=>'bank','ma_voucher'=>'VALUE-LEGACY']);
$legacyOrder=request($base,$valueCookie,'thanhtoan',['intent'=>'order','phuong_thuc'=>'cod','ma_voucher'=>'VALUE-LEGACY','dia_chi'=>'Voucher test','sdt'=>'0900000000']);
check('VALUE ma cu giam 0 bi chan o preview va dat don, khong mat luot/kho',empty($legacyPreview['data']['voucherQuote']['ok']) && !empty($legacyOrder['data']['loi'])
    && count($dh->layTheoKhach($valueKh))===$beforeLegacyOrders && ton($valueSp)===$beforeLegacyStock && (int)$valueModel->layTheoCode('VALUE-LEGACY')['so_luong']===5);
$adminInvalid=request($base,$adminCookie,'themvoucher',['ma_code'=>'VALUE-ADMIN-ZERO','loai'=>'tien','gia_tri'=>0,'so_luong'=>10,'ngay_bd'=>date('Y-m-d'),'ngay_kt'=>date('Y-m-d',strtotime('+1 day'))],$adminPath);
check('VALUE admin POST khong vuot duoc kiem tra giam 0',!empty($adminInvalid['data']['loi']) && $valueModel->layTheoCode('VALUE-ADMIN-ZERO')===null);
$repair=request($base,$adminCookie,'suaphamvivoucher',['id'=>$legacy['id'],'pham_vi'=>'tatca','gia_tri'=>99000],$adminPath);
check('VALUE admin sua muc giam ma cu, giu nguyen luot',$repair['status']===200 && (float)$valueModel->layTheoCode('VALUE-LEGACY')['gia_tri']===99000.0 && (int)$valueModel->layTheoCode('VALUE-LEGACY')['so_luong']===5);
foreach ([['tien',99000,99000,30000,131000],['phantram',20,40000,30000,190000],['mienship',0,0,0,200000]] as $i=>$case) {
    $code='VALUE-FLOW-'.$i;vcTest($code,['loai'=>$case[0],'gia_tri'=>$case[1]]);
    $q=$valueModel->kiemTra($code,'Silver',200000,30000);
    check('VALUE preview '.$case[0].' giam dung, chua tru luot',$q['ok'] && $q['giam']==$case[2] && $q['phi_ship']==$case[3] && $q['tong']==$case[4] && (int)$valueModel->layTheoCode($code)['so_luong']===5);
    $stock=ton($valueSp);$placed=dat($valueKh,$valueSp,1,$code);$order=$dh->layTheoId((int)($placed['id'] ?? 0));
    check('VALUE luu don '.$case[0].' khop preview va tru 1 luot',!empty($placed['ok']) && (float)$order['tong_tien']==$case[4] && (float)$order['tien_giam']==$case[2] && (float)$order['phi_ship']==$case[3]
        && (int)$valueModel->layTheoCode($code)['so_luong']===4 && ton($valueSp)===$stock-1);
    if ($i===0) {
        $edit=$valueModel->suaPhamVi((int)$order['id_voucher'],['pham_vi'=>'tatca','gia_tri'=>50000]);
        $oldOrder=$dh->layTheoId((int)$order['id']);
        check('VALUE sua gia tri khong tinh lai don cu hay reset luot',$edit['ok'] && (float)$oldOrder['tien_giam']===99000.0 && (float)$oldOrder['tong_tien']===131000.0 && (int)$valueModel->layTheoCode($code)['so_luong']===4);
        $invalidEdit=$valueModel->suaPhamVi((int)$order['id_voucher'],['pham_vi'=>'tatca','gia_tri'=>0]);
        check('VALUE sua ve 0 bi tu choi, giu du lieu cu',empty($invalidEdit['ok']) && (float)$valueModel->layTheoCode($code)['gia_tri']===50000.0);
    }
    $cancel=$i===1 ? $dh->capNhatTrangThai((int)$order['id'],donhang::DA_HUY,'Admin cancels voucher test') : $dh->huyDon((int)$order['id'],$valueKh,'Customer cancels voucher test');
    $again=$dh->huyDon((int)$order['id'],$valueKh,'Repeat cancellation');
    check('VALUE huy '.$case[0].' hoan dung 1 luot, lap lai khong tang them',!empty($cancel['ok']) && empty($again['ok']) && (int)$valueModel->layTheoCode($code)['so_luong']===5 && ton($valueSp)===$stock);
}
