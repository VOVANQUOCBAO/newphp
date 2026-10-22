<?php
// Offline payment-state tests. Uses a disposable database and an injected PayPal double; never calls Live/Sandbox APIs.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
date_default_timezone_set('Asia/Ho_Chi_Minh');
define('BASE_PATH', dirname(__DIR__, 2));
require BASE_PATH . '/model/donhang.php';
$baseCfg = csdl::cauHinh();
$db = 'webbanhang_mvc_test_' . bin2hex(random_bytes(6));
$saved = [];
foreach (['DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASS','SMTP_USER','SMTP_PASS','PAYPAL_ENABLED','PAYPAL_MODE',
    'PAYPAL_CLIENT_ID','PAYPAL_CLIENT_SECRET','PAYPAL_MERCHANT_ID','PAYPAL_APP_URL','PAYPAL_VND_PER_USD'] as $k) $saved[$k] = getenv($k);
$results = [];
function ppCheck(string $name, bool $ok): void {
    global $results;
    $results[] = ['name' => $name, 'ok' => $ok];
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
}
function ppReject(callable $fn, string $reason): bool {
    try { $fn(); return false; } catch (paypal_error $e) { return $e->reason === $reason; }
}
class paypal_double extends paypal_client {
    public array $remote = [];
    public int $captures = 0;
    public array $keys = [];
    public bool $timeoutCreate = false;
    public bool $timeoutCapture = false;
    public bool $lostReads = false;
    public string $captureStatus = 'COMPLETED';
    public function request(string $method, string $path, ?array $body = null, ?string $requestId = null): array {
        if ($method === 'POST' && $path === '/v2/checkout/orders') {
            $this->keys['create'][] = $requestId;
            if ($this->remote === []) $this->remote = [
                'id' => 'O' . strtoupper(bin2hex(random_bytes(8))), 'intent' => 'CAPTURE', 'status' => 'CREATED',
                'purchase_units' => $body['purchase_units'], 'links' => [['rel' => 'payer-action','href' => 'https://www.sandbox.paypal.com/checkoutnow?token=TEST']],
            ];
            if ($this->timeoutCreate) { $this->timeoutCreate = false; throw new paypal_error('connection','offline timeout'); }
            return $this->remote;
        }
        if ($method === 'GET') {
            if ($this->lostReads && $this->captures > 0) throw new paypal_error('connection','offline lost response');
            if (str_starts_with($path,'/v2/payments/captures/')) return $this->remote['purchase_units'][0]['payments']['captures'][0];
            return $this->remote;
        }
        if ($method === 'POST' && str_ends_with($path, '/capture')) {
            $this->keys['capture'][] = $requestId;
            $this->captures++;
            $this->remote['status'] = 'COMPLETED';
            $this->remote['purchase_units'][0]['payments']['captures'] = [[
                'id' => 'C' . strtoupper(bin2hex(random_bytes(8))), 'status' => $this->captureStatus,
                'amount' => $this->remote['purchase_units'][0]['amount'], 'final_capture' => true,
            ]];
            if ($this->timeoutCapture) { $this->timeoutCapture = false; throw new paypal_error('connection','offline timeout after charge'); }
            return $this->remote;
        }
        throw new RuntimeException('Unexpected test API request');
    }
}
$cfg = ['enabled'=>true,'mode'=>'sandbox','client_id'=>'TEST_CLIENT','client_secret'=>'TEST_SECRET',
    'merchant_id'=>'TESTMERCHANT1','app_url'=>'http://127.0.0.1:8000','vnd_per_usd'=>25000];
$adminPdo = null;
$server = null;
try {
    $adminPdo = new PDO("mysql:host={$baseCfg['host']};port={$baseCfg['port']};charset=utf8mb4", $baseCfg['user'], $baseCfg['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $adminPdo->exec("CREATE DATABASE `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    foreach (['HOST'=>$baseCfg['host'],'PORT'=>$baseCfg['port'],'NAME'=>$db,'USER'=>$baseCfg['user'],'PASS'=>$baseCfg['pass']] as $k=>$v) putenv("DB_$k=$v");
    csdl::resetKetNoi();
    $pdo = csdl::shared();
    $pdo->exec(file_get_contents(BASE_PATH . '/database/schema.sql'));
    $pdo->exec("INSERT INTO danhmuc (ten_danhmuc) VALUES ('PayPal tests')");
    $pdo->exec("INSERT INTO khachhang (ho_ten,email,mat_khau) VALUES ('Test','paypal@test.local','unused'),('Other','other@test.local','unused')");
    function ppFixture(?string $key = null): array {
        global $pdo, $cfg;
        $code = 'PP' . bin2hex(random_bytes(6));
        $s = $pdo->prepare('INSERT INTO sanpham(ma_sp,ten_sp,id_danhmuc,gia,so_luong_goc,so_luong_ton) VALUES (?,?,1,220000,10,10)');
        $s->execute([$code,'PayPal test item']); $sp = (int)$pdo->lastInsertId();
        $key ??= bin2hex(random_bytes(32));
        $r = (new donhang())->taoDon(1,[$sp=>1],['dia_chi'=>'1 Test','sdt'=>'0900000000',
            'paypal'=>['checkout_key'=>$key,'rate'=>['value'=>25000,'source'=>'manual','at'=>time()],'config'=>$cfg]]);
        if (empty($r['ok'])) throw new RuntimeException($r['loi']);
        $fake = new paypal_double($cfg); $m = new thanhtoan_paypal($fake,$cfg);
        return ['id'=>(int)$r['id'],'sp'=>$sp,'fake'=>$fake,'model'=>$m,'key'=>$key];
    }
    ppCheck('USD rounding and invalid amount rejected', paypal_config::usd(525000,25000)==='21.00'
        && ppReject(fn()=>paypal_config::usd(0,25000),'amount') && ppReject(fn()=>paypal_config::usd(10,0),'amount'));
    ppCheck('Live requires HTTPS and receiving merchant configuration', !paypal_config::ready(array_replace($cfg,['mode'=>'live']))
        && !paypal_config::ready(array_replace($cfg,['merchant_id'=>''])));
    $f=ppFixture(); $m=$f['model']; $id=$f['id']; $fake=$f['fake'];
    ppCheck('Reserved order has locked VND/USD rate, stock reduced once', $m->lay($id)['amount_usd']==='10.00'
        && (int)$pdo->query('SELECT so_luong_ton FROM sanpham WHERE id='.$f['sp'])->fetchColumn()===9);
    ppCheck('Wrong owner cannot open or capture an order', $m->lay($id,2)===null && ppReject(fn()=>$m->capture($id,2),'owner') && $fake->captures===0);
    ppCheck('Unpaid PayPal order cannot be shipped', empty((new donhang())->capNhatTrangThai($id,donhang::XAC_NHAN)['ok']));
    $m->taoPayPal($id,1,str_repeat('a',64));
    ppCheck('Server creates provider order with expected merchant and amount', $fake->remote['purchase_units'][0]['payee']['merchant_id']===$cfg['merchant_id']
        && $fake->remote['purchase_units'][0]['amount']['value']==='10.00');
    ppCheck('Unapproved order cannot be charged', ppReject(fn()=>$m->capture($id,1),'approval') && $fake->captures===0);
    $fake->remote['status']='APPROVED';
    ppCheck('APPROVED is not a paid capture', $m->kiemTra($id,1)['status']==='approved'
        && empty((new donhang())->capNhatTrangThai($id,donhang::XAC_NHAN)['ok']));
    $p=$m->capture($id,1); $again=$m->capture($id,1);
    ppCheck('COMPLETED capture verified and repeat does not charge twice', $p['status']==='paid' && $p['capture_id']===$again['capture_id'] && $fake->captures===1);
    ppCheck('Paid PayPal cancellation requires refund; stock stays reserved', empty((new donhang())->huyDon($id,1)['ok'])
        && (int)$pdo->query('SELECT so_luong_ton FROM sanpham WHERE id='.$f['sp'])->fetchColumn()===9);
    ppCheck('Paid PayPal order can enter fulfilment', !empty((new donhang())->capNhatTrangThai($id,donhang::XAC_NHAN)['ok']));
    foreach (['merchant','currency','amount'] as $type) {
        $f=ppFixture(); $m=$f['model']; $fake=$f['fake']; $m->taoPayPal($f['id'],1,str_repeat('a',64)); $fake->remote['status']='APPROVED';
        if ($type==='merchant') $fake->remote['purchase_units'][0]['payee']['merchant_id']='OTHER';
        elseif ($type==='currency') $fake->remote['purchase_units'][0]['amount']['currency_code']='EUR';
        else $fake->remote['purchase_units'][0]['amount']['value']='0.01';
        ppCheck('Reject '.$type.' mismatch before capture', ppReject(fn()=>$m->capture($f['id'],1),'mismatch')
            && $fake->captures===0 && $m->lay($f['id'])['status']==='review');
    }
    $f=ppFixture(); $m=$f['model']; $fake=$f['fake']; $fake->timeoutCreate=true;
    ppCheck('Create timeout stays unresolved', ppReject(fn()=>$m->taoPayPal($f['id'],1,str_repeat('a',64)),'connection'));
    $m->taoPayPal($f['id'],1,str_repeat('a',64));
    ppCheck('Create retry reuses PayPal idempotency key', count($fake->keys['create'])===2 && count(array_unique($fake->keys['create']))===1);
    $fake->remote['status']='APPROVED'; $fake->timeoutCapture=true;
    ppCheck('Timeout after capture recovers via trusted GET without new charge', $m->capture($f['id'],1)['status']==='paid' && $fake->captures===1);
    $f=ppFixture(); $m=$f['model']; $fake=$f['fake']; $m->taoPayPal($f['id'],1,str_repeat('a',64));
    $fake->remote['status']='APPROVED'; $fake->timeoutCapture=true; $fake->lostReads=true;
    ppCheck('Unknown capture outcome stays processing', ppReject(fn()=>$m->capture($f['id'],1),'connection') && $m->lay($f['id'])['status']==='processing');
    ppCheck('Unknown outcome cannot cancel/release stock', empty((new donhang())->huyDon($f['id'],1)['ok']));
    $fake->lostReads=false;
    ppCheck('Later reconcile resolves paid outcome exactly once', $m->kiemTra($f['id'],1)['status']==='paid' && $m->capture($f['id'],1)['status']==='paid' && $fake->captures===1);
    $f=ppFixture(); $m=$f['model']; $fake=$f['fake']; $m->taoPayPal($f['id'],1,str_repeat('a',64));
    $fake->remote['status']='APPROVED'; $fake->captureStatus='PENDING';
    ppCheck('PENDING capture is not paid or shippable', $m->capture($f['id'],1)['status']==='pending' && empty((new donhang())->capNhatTrangThai($f['id'],donhang::XAC_NHAN)['ok']));
    $fake->remote['purchase_units'][0]['payments']['captures'][0]['status']='COMPLETED';
    ppCheck('Pending capture becomes paid only after provider confirmation', $m->kiemTra($f['id'],1)['status']==='paid');
    $fake->remote['purchase_units'][0]['payments']['captures'][0]['status']='REFUNDED';
    ppCheck('Provider refund recorded separately from fulfilment', $m->kiemTra($f['id'],1)['status']==='refunded');
    $f=ppFixture(); $m=$f['model'];
    $pdo->exec("UPDATE thanhtoan_paypal SET status='processing',lease_until=DATE_ADD(NOW(),INTERVAL 60 SECOND) WHERE id_donhang=".$f['id']);
    ppCheck('Concurrent active payment lease prevents another capture', ppReject(fn()=>$m->capture($f['id'],1),'busy') && $f['fake']->captures===0);
    $pdo->exec("UPDATE thanhtoan_paypal SET status='created',lease_until=NULL,expires_at=DATE_SUB(NOW(),INTERVAL 1 MINUTE) WHERE id_donhang=".$f['id']);
    $before=(int)$pdo->query('SELECT so_lan_huy FROM khachhang WHERE id=1')->fetchColumn();
    ppCheck('Expired unpaid reservation cancels and restores stock once', !empty((new donhang())->hetHanPayPal($f['id'])['ok'])
        && $m->lay($f['id'])['status']==='cancelled' && (int)$pdo->query('SELECT so_luong_ton FROM sanpham WHERE id='.$f['sp'])->fetchColumn()===10
        && (int)$pdo->query('SELECT so_lan_huy FROM khachhang WHERE id=1')->fetchColumn()===$before
        && empty((new donhang())->hetHanPayPal($f['id'])['ok']));
    ppCheck('Cancelled order cannot capture', ppReject(fn()=>$m->capture($f['id'],1),'cancelled'));
    $f=ppFixture(); $before=(int)$pdo->query('SELECT COUNT(*) FROM donhang')->fetchColumn();
    $r=(new donhang())->taoDon(1,[$f['sp']=>1],['dia_chi'=>'1 Test','sdt'=>'0900000000','paypal'=>[
        'checkout_key'=>$f['key'],'rate'=>['value'=>25000,'source'=>'manual','at'=>time()],'config'=>$cfg]]);
    ppCheck('Duplicate checkout rolls back duplicate order and stock', empty($r['ok']) && (int)$pdo->query('SELECT COUNT(*) FROM donhang')->fetchColumn()===$before
        && (int)$pdo->query('SELECT so_luong_ton FROM sanpham WHERE id='.$f['sp'])->fetchColumn()===9);
    $returned=ppFixture(); $rm=$returned['model']; $rf=$returned['fake'];
    $rm->taoPayPal($returned['id'],1,str_repeat('a',64)); $rf->remote['status']='APPROVED'; $rm->capture($returned['id'],1);
    $orders=new donhang();
    foreach([donhang::XAC_NHAN,donhang::DANG_GIAO,donhang::DA_GIAO,donhang::THANH_CONG] as $state) $orders->capNhatTrangThai($returned['id'],$state);
    $orders->yeuCauTra($returned['id'],1,'Return offline fixture');
    ppCheck('Paid PayPal return cannot release stock before refund',empty($orders->capNhatTrangThai($returned['id'],donhang::TRA_HANG,'Received',true)['ok']));
    $rf->remote['purchase_units'][0]['payments']['captures'][0]['status']='REFUNDED'; $rm->kiemTra($returned['id'],1);
    ppCheck('Reconciled full PayPal refund permits checked return once',!empty($orders->capNhatTrangThai($returned['id'],donhang::TRA_HANG,'Received',true)['ok'])
        && empty($orders->capNhatTrangThai($returned['id'],donhang::TRA_HANG,'Repeat',true)['ok'])
        && (int)$pdo->query('SELECT so_luong_ton FROM sanpham WHERE id='.$returned['sp'])->fetchColumn()===10);
    ppCheck('PayPal refunded return is visible as refunded and cannot use COD confirmation',$orders->layTrangThai($returned['id'])['tra_hang']['trang_thai_hoan']==='Đã hoàn tiền'
        && empty($orders->xacNhanHoanCOD($returned['id'],'fake')['ok']));
    // Exercise real controller/session/CSRF boundaries with no provider API calls.
    foreach (['enabled'=>1,'mode'=>'sandbox','client_id'=>'TEST_CLIENT','client_secret'=>'TEST_SECRET','merchant_id'=>'TESTMERCHANT1',
        'app_url'=>'http://127.0.0.1','vnd_per_usd'=>25000] as $k=>$v) putenv('PAYPAL_'.strtoupper($k).'='.$v);
    putenv('SMTP_USER='); putenv('SMTP_PASS=');
    $s=$pdo->prepare('UPDATE khachhang SET mat_khau=?'); $s->execute([password_hash('Test-password-123!',PASSWORD_DEFAULT)]);
    $socket=stream_socket_server('tcp://127.0.0.1:0'); $addr=stream_socket_get_name($socket,false); fclose($socket);
    $log=sys_get_temp_dir().'/'.$db.'-http.log';
    $server=proc_open([PHP_BINARY,'-c',php_ini_loaded_file(),'-S',$addr,'-t',BASE_PATH],
        [0=>['pipe','r'],1=>['file',$log,'a'],2=>['file',$log,'a']],$pipes,BASE_PATH); fclose($pipes[0]);
    for($i=0;$i<50;$i++){ $conn=@stream_socket_client('tcp://'.$addr,$errno,$errstr,.1); if($conn){fclose($conn);break;} usleep(100000); }
    function ppHttp(string $act, string &$cookie, ?array $post=null): array {
        global $addr;
        if ($post !== null && in_array($act, ['dangnhap', 'themgiohang'], true) && !isset($post['_csrf'])) {
            $page = ppHttp('dangnhap', $cookie);
            $post['_csrf'] = $page['data']['userCsrf'] ?? '';
        }
        $ctx=stream_context_create(['http'=>['method'=>$post===null?'GET':'POST','header'=>"Accept: application/json\r\nCookie: $cookie\r\nContent-Type: application/x-www-form-urlencoded\r\n",
            'content'=>$post===null?'':http_build_query($post),'ignore_errors'=>true,'follow_location'=>0,'timeout'=>8]]);
        $raw=file_get_contents('http://'.$addr.'/controller/index.php?act='.$act,false,$ctx);
        $status=0;$location='';foreach($http_response_header as $h){if(preg_match('/^HTTP\/\S+ (\d+)/',$h,$m))$status=(int)$m[1];
            elseif(preg_match('/^Set-Cookie:\s*([^;]+)/i',$h,$m))$cookie=$m[1];elseif(stripos($h,'Location: ')===0)$location=substr($h,10);}
        return ['status'=>$status,'data'=>json_decode($raw?:'',true),'location'=>$location];
    }
    $abandoned=ppFixture();
    $pdo->exec("UPDATE thanhtoan_paypal SET expires_at=DATE_SUB(NOW(),INTERVAL 1 MINUTE) WHERE id_donhang=".$abandoned['id']);
    $publicCookie=''; ppHttp('trangchu',$publicCookie);
    ppCheck('Abandoned unpaid reservation releases on storefront request without buyer returning',$abandoned['model']->lay($abandoned['id'])['status']==='cancelled'
        && (int)$pdo->query('SELECT so_luong_ton FROM sanpham WHERE id='.$abandoned['sp'])->fetchColumn()===10);
    $cookie='';$otherCookie='';
    ppCheck('HTTP anonymous cannot access payment',ppHttp('paypal&id='.$f['id'],$cookie)['status']===302);
    ppHttp('dangnhap',$cookie,['email'=>'paypal@test.local','mat_khau'=>'Test-password-123!']);
    ppHttp('dangnhap',$otherCookie,['email'=>'other@test.local','mat_khau'=>'Test-password-123!']);
    ppCheck('HTTP other customer cannot access payment',ppHttp('paypal&id='.$f['id'],$otherCookie)['status']===404);
    $current=ppHttp('paypal&id='.$f['id'],$cookie); $token=$current['data']['checkoutToken'];
    ppCheck('HTTP public payment contains no credentials or idempotency keys',!isset($current['data']['payment']['client_id'])
        && !isset($current['data']['payment']['checkout_key']) && !isset($current['data']['payment']['capture_request_id']));
    ppCheck('HTTP GET cannot trigger capture',ppHttp('paypal_capture&id='.$f['id'],$cookie)['status']===403);
    ppCheck('HTTP missing CSRF cannot create provider order',ppHttp('paypal_create',$cookie,['id'=>$f['id']])['status']===403);
    ppCheck('HTTP forged return does not mark paid',ppHttp('paypal_return&id='.$f['id'].'&state=forged&token=forged',$cookie)['status']===403
        && $f['model']->lay($f['id'])['status']==='created');
    ppCheck('HTTP legacy cancel endpoint also enforces PayPal CSRF',ppHttp('huydonhang',$cookie,['id'=>$f['id']])['status']===403);
    $cancel=ppHttp('paypal_abandon',$cookie,['id'=>$f['id'],'_checkout_csrf'=>$token]);
    ppCheck('HTTP owner can cancel unpaid order with valid CSRF',$cancel['status']===200 && $cancel['data']['payment']['status']==='cancelled');
    ppHttp('themgiohang',$cookie,['id'=>$f['sp'],'sl'=>1]);
    $checkout=ppHttp('thanhtoan',$cookie);$token=$checkout['data']['checkoutToken'];
    $before=(int)$pdo->query('SELECT COUNT(*) FROM donhang')->fetchColumn();
    $post=['dia_chi'=>'HTTP test address','sdt'=>'0900000000','phuong_thuc'=>'paypal','amount_usd'=>'0.01','tong_tien'=>'1'];
    ppCheck('HTTP checkout without CSRF cannot reserve order',ppHttp('thanhtoan',$cookie,$post)['status']===403
        && (int)$pdo->query('SELECT COUNT(*) FROM donhang')->fetchColumn()===$before);
    $post['_checkout_csrf']=$token;
    ppCheck('HTTP missing checkout key cannot reserve order',ppHttp('thanhtoan',$cookie,$post)['status']===409
        && (int)$pdo->query('SELECT COUNT(*) FROM donhang')->fetchColumn()===$before);
    $post['_order_key']=$checkout['data']['codCheckoutKey'];
    $created=ppHttp('thanhtoan',$cookie,$post);$again=ppHttp('thanhtoan',$cookie,$post);
    $p=$pdo->query('SELECT * FROM thanhtoan_paypal ORDER BY id_donhang DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    ppCheck('HTTP amount tampering ignored; total recalculated by server',$created['status']===302 && $p['amount_usd']==='10.00' && $p['amount_vnd']==='250000.00');
    ppCheck('HTTP repeated checkout reuses same reserved order',$again['location']===$created['location']
        && (int)$pdo->query('SELECT COUNT(*) FROM donhang')->fetchColumn()===$before+1);
} catch (Throwable $e) {
    ppCheck('Suite completed: '.$e->getMessage().' @ '.$e->getLine(),false);
} finally {
    if (is_resource($server)) {proc_terminate($server);proc_close($server);}
    csdl::resetKetNoi();
    if ($adminPdo && preg_match('/^webbanhang_mvc_test_[a-f0-9]{12}$/',$db)) $adminPdo->exec("DROP DATABASE `$db`");
    foreach($saved as $k=>$v) putenv($v===false?$k:"$k=$v");
}
$pass=count(array_filter($results,fn($r)=>$r['ok'])); $fail=count($results)-$pass;
file_put_contents(__DIR__.'/ketqua_paypal.json',json_encode(['pass'=>$pass,'fail'=>$fail,'checks'=>$results],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo "\nPayPal: $pass PASS, $fail FAIL (offline, no payments)\n";
exit($fail?1:0);
