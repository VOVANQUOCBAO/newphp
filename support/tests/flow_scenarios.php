<?php
// Included by test_luong.php after its existing checks, using the same disposable DB/server.
require_once BASE_PATH . '/controller/product_upload.php';
require_once BASE_PATH . '/model/kiemtra_dulieu.php';
$flowImages = [];
$flowTmp = tempnam(sys_get_temp_dir(), 'flow-image-');
$badTmp = tempnam(sys_get_temp_dir(), 'flow-invalid-');
file_put_contents($flowTmp, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
file_put_contents($badTmp, '<?php echo "invalid image";');
function flowMultipart(string $base, string $cookie, string $act, array $post): array
{
    $ch = curl_init($base . '/index.php?khu=admin&act=' . $act . '&format=json');
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $post, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Cookie: ' . $cookie, 'Accept: application/json'], CURLOPT_TIMEOUT => 10]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'data' => json_decode($body ?: '', true)];
}
try {
    $page = requestHtml($base, $adminCookie, '/index.php?khu=admin&act=sanpham');
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $page['body']);
    $forms = $dom->getElementsByTagName('form');
    $validActions = true;
    foreach ($forms as $form) if (str_contains($form->getAttribute('action'), 'act=') && !str_contains($form->getAttribute('action'), 'khu=admin')) $validActions = false;
    check('FLOW HTML admin: giu khu=admin, form URL-encoded va tai anh rieng', $validActions && str_contains($page['body'], 'enctype="application/x-www-form-urlencoded"') && str_contains($page['body'], 'product-upload.js') && str_contains($page['body'], 'name="album[]"'));
    if (!is_dir(__DIR__.'/previews')) mkdir(__DIR__.'/previews',0755,true);
    $preview = preg_replace('/name="_csrf" value="[^"]*"/', 'name="_csrf" value=""', $page['body']);
    $preview = str_replace('<head>', '<head><base href="/">', $preview);
    $preview = str_replace('</body>', '<script>document.addEventListener("submit",event=>event.preventDefault(),true);</script></body>', $preview);
    file_put_contents(__DIR__.'/previews/admin-products.html',$preview);
    $loginHtml = requestHtml($base, $cookie, '/controller/index.php?act=dangnhap');
    check('FLOW dang nhap chung: khong co nut vao he thong admin hoac ghi nho gia', !str_contains($loginHtml['body'], 'act=admin"') && !str_contains($loginHtml['body'], 'rememberMe'));
    request($base, $adminCookie, 'danhmuc', ['hanh_dong'=>'them','ten_danhmuc'=>'Flow category'], $adminPath);
    $flowDm = (int)$dm->selectOne('SELECT id FROM danhmuc WHERE ten_danhmuc=?', ['Flow category'])['id'];
    $home = requestHtml($base, $cookie, '/controller/index.php?act=trangchu');
    check('FLOW admin them danh muc -> DB -> bo loc trang chu', $flowDm>0 && str_contains($home['body'], 'Flow category'));
    $encodedPng = rtrim(strtr(base64_encode(file_get_contents($flowTmp)), '+/', '-_'), '=');
    $stageNoCsrf = request($base, $adminCookie, 'taianhsanpham', ['image'=>$encodedPng,'_csrf'=>''], $adminPath);
    $stageManager = request($base, $managerCookie, 'taianhsanpham', ['image'=>$encodedPng], $adminPath);
    $stageEmployee = request($base, $employeeCookie, 'taianhsanpham', ['image'=>$encodedPng], $adminPath);
    $stageAnonymous = request($base, $anonymousCookie, 'taianhsanpham', ['image'=>$encodedPng], $adminPath);
    $stageGet = request($base, $adminCookie, 'taianhsanpham', null, $adminPath);
    check('UPLOAD URL: bat buoc POST, CSRF va quyen sua san pham', $stageNoCsrf['status']===403 && $stageManager['status']===403
        && $stageEmployee['status']===403 && $stageAnonymous['status']===302 && $stageGet['status']===405);
    $stagedMain = request($base, $adminCookie, 'taianhsanpham', ['image'=>$encodedPng], $adminPath);
    $stagedGallery = request($base, $adminCookie, 'taianhsanpham', ['image'=>$encodedPng], $adminPath);
    $mainToken = $stagedMain['data']['upload_id'] ?? '';
    $galleryToken = $stagedGallery['data']['upload_id'] ?? '';
    check('UPLOAD URL: anh chinh/album duoc luu tam, khong lo duong dan', !empty($stagedMain['data']['ok']) && preg_match('/^[a-f0-9]{32}$/D',$mainToken)
        && is_file(productStagePath($mainToken)) && !isset($stagedMain['data']['path']));
    $urlPayload = ['ma_sp'=>'FLOW-URL-UP','ten_sp'=>'Flow URL encoded phone','id_danhmuc'=>$flowDm,'gia'=>500000,'so_luong_goc'=>2,'so_luong_ton'=>2,
        'mo_ta'=>'Mô tả qua URL-encoded','thong_so'=>'RAM: 8GB','thuong_hieu'=>'Flow','bao_hanh'=>'12 tháng',
        '_image_uploads'=>json_encode(['main'=>$mainToken,'gallery'=>[$galleryToken]])];
    $secondAdminCookie = '';
    request($base,$secondAdminCookie,'dangnhap',['email'=>'admin@local.test','mat_khau'=>$adminPassword]);
    $crossSession = request($base,$secondAdminCookie,'themsanpham',$urlPayload,$adminPath);
    check('UPLOAD URL: khong dung duoc anh tu phien admin khac', !empty($crossSession['data']['loi']) && $sp->layTheoMa('FLOW-URL-UP')===null && is_file(productStagePath($mainToken)));
    $forgedToken = request($base,$adminCookie,'themsanpham',array_replace($urlPayload,['_image_uploads'=>json_encode(['main'=>'../../config','gallery'=>[]])]),$adminPath);
    $tooMany = request($base,$adminCookie,'themsanpham',array_replace($urlPayload,['_image_uploads'=>json_encode(['main'=>null,'gallery'=>array_fill(0,7,$mainToken)])]),$adminPath);
    check('UPLOAD URL: chan token duong dan va album qua 6 anh', !empty($forgedToken['data']['loi']) && !empty($tooMany['data']['loi']) && is_file(productStagePath($mainToken)));
    $urlUpload = request($base,$adminCookie,'themsanpham',$urlPayload,'/index.php?khu=admin');
    $urlProduct = $sp->layTheoMa('FLOW-URL-UP');
    if ($urlProduct) $flowImages=array_merge($flowImages,[$urlProduct['hinh']],explode("\n",$urlProduct['anh_phu']));
    $urlDetail = requestHtml($base,$cookie,'/controller/index.php?act=chitiet&id='.(int)($urlProduct['id']??0));
    check('UPLOAD URL: root POST -> anh/detail trong DB -> trang khach', $urlUpload['status']===200 && empty($urlUpload['data']['loi']) && $urlProduct
        && str_contains($urlDetail['body'],'RAM: 8GB') && str_contains($urlDetail['body'],$urlProduct['hinh'])
        && is_file(BASE_PATH.'/view/image/'.$urlProduct['hinh']) && !is_file(productStagePath($mainToken)));
    $replayImage = request($base,$adminCookie,'themsanpham',array_replace($urlPayload,['ma_sp'=>'FLOW-URL-REPLAY']),$adminPath);
    check('UPLOAD URL: token anh da luu khong duoc dung lai', !empty($replayImage['data']['loi']) && $sp->layTheoMa('FLOW-URL-REPLAY')===null);
    $stageInvalid = request($base,$adminCookie,'taianhsanpham',['image'=>rtrim(strtr(base64_encode('<?php echo 1;'),'+/','-_'),'=')],$adminPath);
    $stageMalformed = request($base,$adminCookie,'taianhsanpham',['image'=>'../invalid!'],$adminPath);
    check('UPLOAD URL: chan file PHP va base64 khong hop le', $stageInvalid['status']===422 && $stageMalformed['status']===422);
    $oversize = request($base,$adminCookie,'taianhsanpham',['image'=>str_repeat('A',4*(int)ceil(5*1024*1024/3)+1)],$adminPath);
    check('UPLOAD URL: chan anh vuot gioi han 5MB', $oversize['status']===422);
    $largePng = file_get_contents($flowTmp) . str_repeat("\0",5*1024*1024-filesize($flowTmp));
    $largeStage = request($base,$adminCookie,'taianhsanpham',['image'=>rtrim(strtr(base64_encode($largePng),'+/','-_'),'=')],$adminPath);
    $largeToken = $largeStage['data']['upload_id'] ?? '';
    check('UPLOAD URL: anh dung 5MB qua duoc POST URL-encoded', !empty($largeStage['data']['ok']) && filesize(productStagePath($largeToken))===5*1024*1024);
    if ($largeToken) {
        request($base,$adminCookie,'themsanpham',array_replace($urlPayload,['_image_uploads'=>json_encode(['main'=>$largeToken,'gallery'=>[]])]),$adminPath);
    }
    unset($largePng,$largeStage);
    $rollbackImage = request($base,$adminCookie,'taianhsanpham',['image'=>$encodedPng],$adminPath);
    $publicBefore = count(glob(BASE_PATH.'/view/image/uploads/*'));
    $duplicate = request($base,$adminCookie,'themsanpham',array_replace($urlPayload,['_image_uploads'=>json_encode(['main'=>$rollbackImage['data']['upload_id'],'gallery'=>[]])]),$adminPath);
    check('UPLOAD URL: loi DB don anh moi, giu anh da luu', !empty($duplicate['data']['loi']) && count(glob(BASE_PATH.'/view/image/uploads/*'))===$publicBefore
        && is_file(BASE_PATH.'/view/image/'.$urlProduct['hinh']));
    $tokenPage = request($base, $adminCookie, 'sanpham', null, $adminPath);
    $payload = ['_csrf'=>$tokenPage['data']['csrfToken'],'ma_sp'=>'FLOW-UPLOAD','ten_sp'=>'Flow phone original',
        'id_danhmuc'=>$flowDm,'gia'=>1000000,'so_luong_goc'=>8,'so_luong_ton'=>8,
        'mo_ta'=>"Mô tả <b>nguyên văn</b>\nDòng 2",'thuong_hieu'=>'Flow Brand','thong_so'=>"RAM: 12GB\nBộ nhớ: 256GB",'bao_hanh'=>'24 tháng',
        'anh'=>new CURLFile($flowTmp, 'image/png', 'camera.png'),'album[0]'=>new CURLFile($flowTmp, 'image/png', 'gallery.png')];
    $upload = flowMultipart($base, $adminCookie, 'themsanpham', $payload);
    $flowProduct = $sp->layTheoMa('FLOW-UPLOAD');
    $flowSp = (int)($flowProduct['id'] ?? 0);
    if ($flowProduct) $flowImages = array_merge($flowImages,array_filter(array_merge([$flowProduct['hinh']], explode("\n",$flowProduct['anh_phu']))));
    check('FLOW multipart them anh chinh/album/detail -> DB', $upload['status']===200 && empty($upload['data']['loi']) && $flowSp>0
        && str_starts_with($flowProduct['hinh'],'uploads/') && is_file(BASE_PATH.'/view/image/'.$flowProduct['hinh'])
        && $flowProduct['thuong_hieu']==='Flow Brand' && $flowProduct['bao_hanh']==='24 tháng' && $flowProduct['anh_phu']!=='', $upload);
    if (!$flowSp) throw new RuntimeException('Upload fixture failed');
    $detail = requestHtml($base,$cookie,'/controller/index.php?act=chitiet&id='.$flowSp);
    check('FLOW DB -> chi tiet: dung mo ta/thong so/anh, escape HTML, bo sao va mau', $detail['status']===200
        && str_contains($detail['body'],'Flow Brand') && str_contains($detail['body'],'RAM: 12GB')
        && str_contains($detail['body'],'&lt;b&gt;nguyên văn&lt;/b&gt;') && str_contains($detail['body'],$flowProduct['hinh'])
        && !str_contains($detail['body'],'⭐⭐') && !str_contains($detail['body'],'Hoàng Quân'));
    $relatedSp = spTest($flowDm,'FLOW-RELATED',4);
    $unrelatedSp = spTest($idDm,'FLOW-OTHER',4);
    $detail = request($base,$cookie,'chitiet&id='.$flowSp);
    $relatedIds = array_map('intval',array_column($detail['data']['ds_lienquan'],'id'));
    check('FLOW lien quan: cung danh muc, loai chinh no va danh muc khac', in_array($relatedSp,$relatedIds,true) && !in_array($flowSp,$relatedIds,true) && !in_array($unrelatedSp,$relatedIds,true));
    unset($payload['anh'],$payload['album[0]']);
    $payload['id']=$flowSp; $payload['ten_sp']='Flow phone UPDATED'; $payload['thong_so']='RAM: 16GB'; $payload['ton_cu']=8;
    $edit = flowMultipart($base,$adminCookie,'suasanpham',$payload);
    $detail = requestHtml($base,$cookie,'/controller/index.php?act=chitiet&id='.$flowSp);
    $home = requestHtml($base,$cookie,'/controller/index.php?act=timkiem&q=UPDATED');
    check('FLOW sua admin -> DB -> chi tiet/trang chu ngay request ke tiep, giu anh cu', empty($edit['data']['loi'])
        && str_contains($detail['body'],'RAM: 16GB') && str_contains($home['body'],'Flow phone UPDATED')
        && $sp->layChiTiet($flowSp)['hinh']===$flowProduct['hinh']);
    $bad = $payload; unset($bad['id']); $bad['ma_sp']='FLOW-INVALID'; $bad['anh']=new CURLFile($badTmp,'image/png','forged.png');
    $invalid = flowMultipart($base,$adminCookie,'themsanpham',$bad);
    check('FLOW tu choi file gia MIME, khong tao san pham', !empty($invalid['data']['loi']) && $sp->layTheoMa('FLOW-INVALID')===null);
    $uploadsBefore = count(glob(BASE_PATH.'/view/image/uploads/*'));
    $duplicateUpload = $payload; unset($duplicateUpload['id']);
    $duplicateUpload['anh'] = new CURLFile($flowTmp,'image/png','duplicate.png');
    $failedUpload = flowMultipart($base,$adminCookie,'themsanpham',$duplicateUpload);
    check('FLOW luu DB that bai don dep anh vua upload, giu anh cu', !empty($failedUpload['data']['loi']) && count(glob(BASE_PATH.'/view/image/uploads/*'))===$uploadsBefore
        && is_file(BASE_PATH.'/view/image/'.$flowProduct['hinh']));
    $dbRejected = false;
    try { $pdo->prepare('UPDATE sanpham SET so_luong_ton=-1 WHERE id=?')->execute([$flowSp]); } catch (PDOException $e) { $dbRejected=true; }
    check('FLOW rang buoc CSDL cung chan ton am khi bo qua model', $dbRejected && ton($flowSp)===8);
    $staleOrder = dat($idKh2,$flowSp);
    $stale = flowMultipart($base,$adminCookie,'suasanpham',$payload);
    check('FLOW ton cu tren form khong ghi de giao dich mua vua phat sinh', !empty($stale['data']['loi']) && ton($flowSp)===7);
    $dh->huyDon($staleOrder['id'],$idKh2);
    $payload['nhap_them']=5;
    $restock = flowMultipart($base,$adminCookie,'suasanpham',$payload);
    check('FLOW nhap them cap nhat kho va lichsu trong cung transaction', empty($restock['data']['loi']) && ton($flowSp)===13
        && (int)$sp->layChiTiet($flowSp)['so_luong_goc']===13);
    $vc->them(['ma_code'=>'FLOW-GOLD','loai'=>'phantram','gia_tri'=>10,'so_luong'=>3,'ngay_bd'=>date('Y-m-d'),'ngay_kt'=>date('Y-m-d'),'hang_ap_dung'=>'Gold','don_toi_thieu'=>2000000]);
    check('FLOW voucher: dong thoi rang buoc ngay/hang toi thieu/gia tri don', !$vc->kiemTra('FLOW-GOLD','Silver',3000000,30000)['ok']
        && !$vc->kiemTra('FLOW-GOLD','Gold',1999999,0)['ok'] && $vc->kiemTra('FLOW-GOLD','Diamond',2000000,0)['ok']);
    $flowKh = khTest('flow-customer@test.local'); $flowCookie='';
    request($base,$flowCookie,'dangnhap',['email'=>'flow-customer@test.local','mat_khau'=>'123456']);
    $beforeReview = request($base,$flowCookie,'themdanhgia',['id_sanpham'=>$flowSp,'so_sao'=>5,'noi_dung'=>'Chưa mua']);
    check('FLOW chua mua: backend chan ca POST nhan xet', !empty($beforeReview['data']['loi']));
    check('FLOW GET va POST sai CSRF khong doi gio', request($base,$flowCookie,'themgiohang&id='.$flowSp)['status']===405
        && request($base,$flowCookie,'themgiohang',['id'=>$flowSp,'_csrf'=>'wrong'])['status']===403);
    request($base,$flowCookie,'themgiohang',['id'=>$relatedSp,'sl'=>1]);
    $buyNow = request($base,$flowCookie,'muangay',['id'=>$flowSp,'sl'=>2]);
    $quote = request($base,$flowCookie,'thanhtoan');
    check('FLOW mua ngay -> thanh toan dung san pham/so luong, khong gom gio cu', $buyNow['status']===302 && str_contains($buyNow['location'],'thanhtoan')
        && count($quote['data']['giohang']['items'])===1 && (int)$quote['data']['giohang']['items'][0]['id']===$flowSp && (int)$quote['data']['giohang']['items'][0]['so_luong']===2);
    $cod = request($base,$flowCookie,'thanhtoan',['dia_chi'=>'Flow address','sdt'=>'0900000000','phuong_thuc'=>'cod','_order_key'=>$quote['data']['codCheckoutKey']]);
    $flowOrder = (int)($cod['data']['id_don'] ?? 0);
    $cart = request($base,$flowCookie,'giohang');
    check('FLOW mua ngay COD thanh cong giu nguyen gio cu', !empty($cod['data']['ok']) && $flowOrder>0 && ton($flowSp)===11
        && count($cart['data']['giohang']['items'])===1 && (int)$cart['data']['giohang']['items'][0]['id']===$relatedSp);
    $replay = request($base,$flowCookie,'thanhtoan',['dia_chi'=>'Flow address','sdt'=>'0900000000','phuong_thuc'=>'cod','_order_key'=>$quote['data']['codCheckoutKey']]);
    $replayCart = request($base,$flowCookie,'giohang');
    check('FLOW gui lai form COD cu tra cung don, khong lay hang tu gio moi', !empty($replay['data']['ok']) && (int)$replay['data']['id_don']===$flowOrder && ton($flowSp)===11 && count($replayCart['data']['giohang']['items'])===1);
    $sp->sua($flowSp,['ten_sp'=>'Flow phone renamed after purchase','gia'=>1500000]);
    $orderItem = $dh->layTrangThai($flowOrder)['chitiet'][0];
    check('FLOW sua ten/gia san pham khong thay doi hoa don da dat', $orderItem['ten_sp']==='Flow phone UPDATED' && (float)$orderItem['don_gia']===1000000.0);
    $dh->capNhatTrangThai($flowOrder,donhang::XAC_NHAN);
    $dh->capNhatTrangThai($flowOrder,donhang::DANG_GIAO);
    check('FLOW khach/admin khong huy khi dang giao', !$dh->huyDon($flowOrder,$flowKh)['ok'] && !$dh->capNhatTrangThai($flowOrder,donhang::DA_HUY)['ok']);
    $dh->capNhatTrangThai($flowOrder,donhang::DA_GIAO);
    $review = request($base,$flowCookie,'themdanhgia',['id_sanpham'=>$flowSp,'so_sao'=>5,'noi_dung'=>'FLOW VERIFIED REVIEW']);
    $detail = requestHtml($base,$flowCookie,'/controller/index.php?act=chitiet&id='.$flowSp);
    check('FLOW da nhan hang: nhan xet duoc luu va hien tren trang khach', $review['status']===302 && str_contains($detail['body'],'FLOW VERIFIED REVIEW'));
    $duplicateReview = request($base,$flowCookie,'themdanhgia',['id_sanpham'=>$flowSp,'so_sao'=>4,'noi_dung'=>'duplicate']);
    check('FLOW moi khach chi nhan xet san pham mot lan', !empty($duplicateReview['data']['loi']) && count($dg->layDSTheoSP($flowSp))===1);
    $dh->capNhatTrangThai($flowOrder,donhang::THANH_CONG);
    $points = (int)$kh->layTheoId($flowKh)['diem'];
    $pdo->prepare('UPDATE donhang SET ngay_giao=DATE_SUB(NOW(),INTERVAL 8 DAY) WHERE id=?')->execute([$flowOrder]);
    check('FLOW qua 7 ngay khong tu yeu cau tra', !$dh->yeuCauTra($flowOrder,$flowKh,'Muốn trả')['ok']);
    $pdo->prepare('UPDATE donhang SET ngay_giao=NOW() WHERE id=?')->execute([$flowOrder]);
    check('FLOW khach khac khong yeu cau tra don', !$dh->yeuCauTra($flowOrder,$idKh2,'Sai chủ')['ok']);
    $return = request($base,$flowCookie,'yeucautra',['id'=>$flowOrder,'ly_do'=>'Hàng lỗi']);
    check('FLOW yeu cau tra -> admin nhin thay, chua hoan kho/diem', empty($return['data']['loi']) && ton($flowSp)===11 && (int)$kh->layTheoId($flowKh)['diem']===$points
        && $dh->layTrangThai($flowOrder)['tra_hang']['trang_thai']==='Chờ xử lý');
    check('FLOW khong duyet tra truoc khi nhan hang', !$dh->capNhatTrangThai($flowOrder,donhang::TRA_HANG,'Test')['ok'] && ton($flowSp)===11);
    $accepted = $dh->capNhatTrangThai($flowOrder,donhang::TRA_HANG,'Đã nhận lại hàng lỗi',true,false);
    check('FLOW tra hang loi: cach ly, khong tang ton ban, thu hoi diem va ha hang', $accepted['ok'] && ton($flowSp)===11
        && (int)$sp->layChiTiet($flowSp)['so_luong_loi']===2 && (int)$kh->layTheoId($flowKh)['diem']===0 && $kh->layTheoId($flowKh)['hang']==='Silver');
    check('FLOW tra lap khong hoan kho/diem lan hai', !$dh->capNhatTrangThai($flowOrder,donhang::TRA_HANG,'Lặp',true)['ok'] && ton($flowSp)===11);
    check('FLOW COD tra hang co khoan cho hoan rieng, can chung tu de xac nhan', $dh->layTrangThai($flowOrder)['tra_hang']['trang_thai_hoan']==='Chờ hoàn tiền'
        && !$dh->xacNhanHoanCOD($flowOrder,'')['ok'] && $dh->xacNhanHoanCOD($flowOrder,'TEST-REFUND-001')['ok'] && !$dh->xacNhanHoanCOD($flowOrder,'Lặp')['ok']);
    $detail = requestHtml($base,$flowCookie,'/controller/index.php?act=chitiet&id='.$flowSp);
    check('FLOW don da tra khong con la bang chung nhan xet cong khai', !str_contains($detail['body'],'FLOW VERIFIED REVIEW'));
    $cancelOrder = dat($flowKh,$flowSp);
    $dh->capNhatTrangThai($cancelOrder['id'],donhang::XAC_NHAN);
    $beforeCancel = (int)$kh->layTheoId($flowKh)['so_lan_huy'];
    check('FLOW admin huy don da xac nhan: hoan kho, khong phat khach', $dh->capNhatTrangThai($cancelOrder['id'],donhang::DA_HUY,'Cửa hàng hết hàng')['ok']
        && (int)$kh->layTheoId($flowKh)['so_lan_huy']===$beforeCancel);
    $userCancel = dat($flowKh,$flowSp);
    $dh->capNhatTrangThai($userCancel['id'],donhang::XAC_NHAN);
    check('FLOW khach huy duoc sau xac nhan va truoc khi giao', $dh->huyDon($userCancel['id'],$flowKh,'Đổi ý')['ok']);
    vcTest('FLOW-RETURN-VC');
    $returnGood = dat($flowKh,$flowSp,1,'FLOW-RETURN-VC');
    toiTrangThai($returnGood['id'],donhang::THANH_CONG);
    $stockBeforeGood = ton($flowSp);
    $dh->yeuCauTra($returnGood['id'],$flowKh,'Trả nguyên vẹn');
    check('FLOW tra hang ban lai: hoan kho va luot voucher dung mot lan', $dh->capNhatTrangThai($returnGood['id'],donhang::TRA_HANG,'Đã kiểm tra',true,true)['ok']
        && ton($flowSp)===$stockBeforeGood+1 && (int)$vc->layTheoCode('FLOW-RETURN-VC')['so_luong']===5
        && !$dh->capNhatTrangThai($returnGood['id'],donhang::TRA_HANG,'Lặp',true,true)['ok'] && (int)$vc->layTheoCode('FLOW-RETURN-VC')['so_luong']===5);
    $ledger = $sp->selectOne('SELECT ton_sau FROM lichsu_kho WHERE id_sanpham=? ORDER BY id DESC LIMIT 1',[$flowSp]);
    check('FLOW lich su kho khop ton hien tai va lich su don co actor', (int)$ledger['ton_sau']===ton($flowSp) && count($dh->layTrangThai($flowOrder)['lichsu'])>=7);
    $managerData=request($base,$managerCookie,'khachhang',null,$adminPath);
    check('FLOW JSON QuanLy khong lo hash mat khau', !str_contains(json_encode($managerData['data']),'mat_khau'));
    $audit = (new kiemtra_dulieu())->baoCao();
    check('FLOW doi chieu: khong lech tong don moi hoac lich su kho', array_sum(array_column(array_filter($audit,fn($row)=>in_array($row['noi_dung'],['Đơn mới lệch tổng tiền','Tồn kho lệch bản ghi biến động mới nhất'],true)),'so_luong'))===0);
    $pdo->prepare('UPDATE sanpham SET ngung_ban=1 WHERE id=?')->execute([$flowSp]);
    check('FLOW ngung ban: an khoi catalog, chi tiet va san pham lien quan',
        $sp->layChiTiet($flowSp)===null && $sp->demTimKiem('Flow phone renamed after purchase')===0
        && !in_array($flowSp,array_map('intval',array_column($sp->lienQuan($relatedSp,$flowDm),'id')),true));
    check('FLOW gio cu khong dat duoc san pham ngung ban, don cu van doc duoc',
        !dat($flowKh,$flowSp)['ok'] && $dh->layTrangThai($flowOrder)['chitiet'][0]['ten_sp']==='Flow phone UPDATED');
} finally {
    productUploadCleanup($flowImages);
    @unlink($flowTmp); @unlink($badTmp);
}
