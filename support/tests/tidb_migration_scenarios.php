<?php
// Real TiDB checks are confined to a separate copy, never the active store database.
if(PHP_SAPI!=='cli'||!preg_match('/^bpstore_check_[a-f0-9]{6}$/D',getenv('DB_NAME') ?: ''))exit(1);
define('BASE_PATH',dirname(__DIR__,2));
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once BASE_PATH.'/model/donhang.php';
require_once BASE_PATH.'/model/danhmuc.php';
require_once BASE_PATH.'/model/nhanvien.php';
require_once BASE_PATH.'/model/yeuthich.php';
require_once BASE_PATH.'/model/thongbao_thanhtoan.php';
require_once BASE_PATH.'/model/email.php';
require_once BASE_PATH.'/controller/giohang.php';
require_once BASE_PATH.'/controller/controller.php';
if(($argv[1] ?? '')==='--worker'){
    $a=json_decode($argv[2],true,512,JSON_THROW_ON_ERROR);
    while(microtime(true)<$a['start'])usleep(1000);
    echo json_encode((new donhang())->taoDon($a['kh'],[$a['sp']=>1],['dia_chi'=>'Fixture only','sdt'=>'0900000000'],$a['voucher']),JSON_THROW_ON_ERROR);exit;
}
$checks=[];
function tidbCheck(string $name,bool $ok):void{global $checks;$checks[]=['name'=>$name,'ok'=>$ok];echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;}
function must(array $result):array{if(empty($result['ok']))throw new RuntimeException($result['loi'] ?? 'Operation failed');return $result;}
function stock(int $id):int{return (int)(new sanpham())->layTheoMa($GLOBALS['code'])['so_luong_ton'];}
try{
    $pdo=csdl::shared();$sp=new sanpham();$dh=new donhang();$kh=new khachhang();$vc=new voucher();$dm=new danhmuc();
    $smtp=(new email())->cauHinhSmtp();if($smtp['user']!==''||$smtp['pass']!=='')throw new RuntimeException('External email must be disabled for fixtures');
    tidbCheck('Real TiDB through adapter',str_contains($pdo->query('SELECT VERSION()')->fetchColumn(),'TiDB') && $pdo instanceof HttpsDatabase);
    tidbCheck('All latest 18 tables available',(int)$pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()')->fetchColumn()===18);
    $suffix=strtoupper(bin2hex(random_bytes(4)));$code='MIG-'.$suffix;
    $admin=$pdo->query("SELECT * FROM nhanvien WHERE trang_thai=1 AND vai_tro IN ('SuperAdmin','Admin') LIMIT 1")->fetch();
    $_SESSION=['admin'=>$admin];$cat=$dm->them('Migration '.$suffix);
    $p=must($sp->them(['ma_sp'=>$code,'ten_sp'=>'Điện thoại kiểm thử TiDB','id_danhmuc'=>$cat,'gia'=>100000,'so_luong_goc'=>10,'ngay_nhap'=>date('Y-m-d'),'mo_ta'=>'Mô tả tiếng Việt giữ nguyên','thong_so'=>'USB-C · 256GB','bao_hanh'=>'12 tháng']));$pid=(int)$p['id'];
    $product=$sp->layTheoMa($code);
    tidbCheck('Create product preserves Vietnamese detail and stock',(int)$product['so_luong_ton']===10 && $product['mo_ta']==='Mô tả tiếng Việt giữ nguyên');
    $list=$sp->layDSAdmin();$last=end($list);tidbCheck('New product appears last in admin list',(int)$last['id']===$pid);
    tidbCheck('Category and product queries respect strict grouping',count($dm->layDS())>0 && count($sp->timKiem('kiểm thử'))>0);
    must($sp->sua($pid,['nhap_them'=>3,'ngay_nhap_dot'=>date('Y-m-d')]));
    tidbCheck('Restock and dated receipt commit together',stock($pid)===13 && (int)$pdo->query('SELECT COUNT(*) FROM lichsu_nhap WHERE id_sanpham='.$pid)->fetchColumn()===2);
    $pdo->beginTransaction();$constraint=false;try{$pdo->exec('UPDATE sanpham SET so_luong_ton=-1 WHERE id='.$pid);}catch(PDOException){$constraint=true;}$pdo->rollBack();
    tidbCheck('TiDB CHECK rejects negative stock and rollback preserves value',$constraint && stock($pid)===13);
    $foreign=false;try{$pdo->exec('INSERT INTO yeuthich(id_khachhang,id_sanpham) VALUES (2147483647,2147483647)');}catch(PDOException){$foreign=true;}
    tidbCheck('TiDB foreign key rejects orphan favorites',$foreign);
    $password=bin2hex(random_bytes(12));$customer=must($kh->themKhachHang(['ho_ten'=>'Fixture customer','email'=>'migration-'.strtolower($suffix).'@example.invalid','mat_khau'=>$password]));$cid=(int)$customer['id'];
    tidbCheck('Existing login behavior and password hashing work',$kh->kiemTraDangNhap('migration-'.strtolower($suffix).'@example.invalid',$password)['ok']);
    must($kh->luuHoSo($cid,['ho_ten'=>'Khách kiểm thử','sdt'=>'0901234567','ngay_sinh'=>'2000-05-15','dia_chi'=>'Địa chỉ chỉ dùng kiểm thử','email'=>'ignored@example.invalid','diem'=>999999]));
    $profile=$kh->layTheoId($cid);tidbCheck('Profile keeps address while protecting email and points',$profile['dia_chi']==='Địa chỉ chỉ dùng kiểm thử' && (int)$profile['diem']===0 && str_starts_with($profile['email'],'migration-'));
    $favorites=new yeuthich();must($favorites->dat($cid,$pid,true));must($favorites->dat($cid,$pid,true));
    tidbCheck('Favorites are unique and do not reserve stock',count($favorites->layDS($cid))===1 && stock($pid)===13);
    $vcode='MIG'.$suffix;must($vc->them(['ma_code'=>$vcode,'loai'=>'tien','gia_tri'=>10000,'so_luong'=>3,'ngay_bd'=>date('Y-m-d'),'ngay_kt'=>date('Y-m-d',strtotime('+1 day')),'pham_vi'=>'sanpham','id_sanpham'=>$pid]));
    $items=giohang::chiTiet([$pid=>1]);$quote=$vc->kiemTra($vcode,'Silver',100000,30000,false,$items['items']);
    tidbCheck('Voucher preview reduces amount without consuming stock or usage',$quote['ok'] && (float)$quote['tong']===120000.0 && (int)$vc->layTheoCode($vcode)['so_luong']===3 && stock($pid)===13);
    $key=bin2hex(random_bytes(32));$info=['dia_chi'=>'Fixture only','sdt'=>'0900000000','checkout_key'=>$key];$order=must($dh->taoDon($cid,[$pid=>1],$info,$vcode));$oid=(int)$order['id'];
    tidbCheck('Checkout commits exact discounted total and consumes one voucher',stock($pid)===12 && (int)$vc->layTheoCode($vcode)['so_luong']===2 && (float)$order['tong_tien']===120000.0);
    $repeat=must($dh->taoDon($cid,[$pid=>1],$info,$vcode));tidbCheck('Repeated checkout does not consume stock or voucher twice',(int)$repeat['id']===$oid && stock($pid)===12 && (int)$vc->layTheoCode($vcode)['so_luong']===2);
    must($dh->huyDon($oid,$cid,'Fixture cancel'));$dh->huyDon($oid,$cid,'Fixture duplicate cancel');
    tidbCheck('Cancel returns stock and voucher exactly once',stock($pid)===13 && (int)$vc->layTheoCode($vcode)['so_luong']===3);
    $bad=$dh->taoDon($cid,[$pid=>14],['dia_chi'=>'Fixture','sdt'=>'0900000000'],$vcode);tidbCheck('Insufficient stock rolls back without consuming voucher',!$bad['ok'] && stock($pid)===13 && (int)$vc->layTheoCode($vcode)['so_luong']===3);
    $bank=['enabled'=>true,'bank_bin'=>'970422','bank_name'=>'FIXTURE ONLY','account_number'=>'0000000000','account_name'=>'FIXTURE ONLY'];
    $qorder=must($dh->taoDon($cid,[$pid=>1],['dia_chi'=>'Fixture only','sdt'=>'0900000000','checkout_key'=>bin2hex(random_bytes(32)),'bank'=>['config'=>$bank]]));$qid=(int)$qorder['id'];$qr=new thanhtoan_qr();
    tidbCheck('QR retains amount and account snapshot',(float)$qr->lay($qid)['amount_vnd']===130000.0 && $qr->lay($qid)['account_number']==='0000000000');
    $mail=new thongbao_thanhtoan();tidbCheck('Unpaid order cannot create thank-you email',!$mail->ghiNhan($qid));
    must($qr->baoDaChuyen($qid,$cid));tidbCheck('Customer report does not mark payment paid',$qr->lay($qid)['status']==='reported');
    must($qr->xacNhanNhan($qid,(int)$admin['id'],'MIG-'.$suffix,130000));$mail->ghiNhan($qid);$mail->ghiNhan($qid);
    tidbCheck('Verified QR payment produces one thank-you record',$qr->lay($qid)['status']==='paid' && (int)$pdo->query('SELECT COUNT(*) FROM email_thanhtoan WHERE id_donhang='.$qid)->fetchColumn()===1);
    $sender=new class extends thongbao_thanhtoan{public int $calls=0;protected function deliver(string $to,string $title,string $body,string $html):array{$this->calls++;return ['ok'=>true];}};
    must($sender->gui($qid));$again=$sender->gui($qid);tidbCheck('Payment email retry is idempotent without external delivery',!$again['ok'] && $sender->calls===1);
    must($sp->xoa($pid));tidbCheck('Archive preserves purchased product and hides favorites',(int)$sp->layTheoMa($code)['ngung_ban']===1 && $favorites->layDS($cid)===[]);
    must($sp->congTon($pid,1));tidbCheck('Archived product still supports stock restoration',stock($pid)===13);
    $permissions=new controller();tidbCheck('Staff retains business permissions without owner role creation',$permissions->canAccessAdminAction('thongbao','NhanVien') && $permissions->canAccessAdminAction('themsanpham','QuanLy') && !$permissions->canAccessAdminAction('khoitaotienich','NhanVien'));
    $employee=must((new nhanvien())->them(['ho_ten'=>'Fixture staff','email'=>'staff-'.strtolower($suffix).'@example.invalid','mat_khau'=>$password,'vai_tro'=>'NhanVien']));
    $_SESSION['admin']=['id'=>$employee['id'],'vai_tro'=>'NhanVien'];$escalate=(new nhanvien())->them(['ho_ten'=>'Forbidden','email'=>'forbidden-'.strtolower($suffix).'@example.invalid','mat_khau'=>$password,'vai_tro'=>'SuperAdmin']);
    tidbCheck('Staff cannot create an administrator on TiDB',!$escalate['ok']);
    $_SESSION['admin']=$admin;
    $raceProduct=must($sp->them(['ma_sp'=>'RACE-'.$suffix,'ten_sp'=>'Race fixture','id_danhmuc'=>$cat,'gia'=>100000,'so_luong_goc'=>1]));$rid=(int)$raceProduct['id'];$raceVoucher='LAST'.$suffix;
    must($vc->them(['ma_code'=>$raceVoucher,'loai'=>'tien','gia_tri'=>1000,'so_luong'=>1,'ngay_bd'=>date('Y-m-d'),'ngay_kt'=>date('Y-m-d',strtotime('+1 day'))]));
    $cid2=(int)must($kh->themKhachHang(['ho_ten'=>'Second fixture','email'=>'second-'.strtolower($suffix).'@example.invalid','mat_khau'=>$password]))['id'];
    $children=[];$start=microtime(true)+1;
    foreach([$cid,$cid2] as $id){$proc=proc_open([PHP_BINARY,__FILE__,'--worker',json_encode(['start'=>$start,'kh'=>$id,'sp'=>$rid,'voucher'=>$raceVoucher])],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,BASE_PATH);fclose($pipes[0]);$children[]=[$proc,$pipes];}
    $wins=0;foreach($children as [$proc,$pipes]){$body=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($proc)!==0)throw new RuntimeException('Concurrent worker failed');$wins+=(int)!empty(json_decode($body,true)['ok']);}
    tidbCheck('Concurrent TiDB checkout sells last item and voucher once',$wins===1 && (int)$pdo->query('SELECT so_luong_ton FROM sanpham WHERE id='.$rid)->fetchColumn()===0 && (int)$vc->layTheoCode($raceVoucher)['so_luong']===0);
}catch(Throwable $e){tidbCheck('Cloud suite completed: '.get_class($e).' '.($e instanceof PDOException?$e->getCode().' / '.($e->errorInfo[1] ?? 0):$e->getMessage()),false);}
$report=['time'=>date(DATE_ATOM),'database'=>getenv('DB_NAME'),'realTiDB'=>true,'pass'=>count(array_filter($checks,fn($x)=>$x['ok'])),'fail'=>count(array_filter($checks,fn($x)=>!$x['ok'])),'checks'=>$checks];
file_put_contents(BASE_PATH.'/support/tests/ketqua_tidb_migration.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));echo "TiDB: {$report['pass']} PASS, {$report['fail']} FAIL\n";exit($report['fail']?1:0);
