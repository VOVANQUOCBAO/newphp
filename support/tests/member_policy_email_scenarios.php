<?php
declare(strict_types=1);
require_once __DIR__.'/../../model/chinhsach_thanhvien.php';
require_once __DIR__.'/../../model/thongbao_thanhtoan.php';
require_once __DIR__.'/../../controller/xuly_email.php';

$checks=0;
$assert=static function(bool $ok,string $label) use (&$checks): void {
    if (!$ok) throw new RuntimeException($label);
    $checks++;
};
foreach ([0=>0,999=>0,1000=>1,490500=>490,500000=>500,2000000=>2000] as $money=>$points) {
    $assert(chinhsach_thanhvien::tinhDiem((float)$money)===$points,'Rounding at '.$money);
}
foreach ([0=>'Silver',499=>'Silver',500=>'Gold',1999=>'Gold',2000=>'Diamond',50000=>'Diamond'] as $points=>$tier) {
    $assert(chinhsach_thanhvien::hang($points)===$tier,'Tier at '.$points);
}
$assert(chinhsach_thanhvien::tienDo(499)['con_thieu']===1,'One point to Gold');
$assert(chinhsach_thanhvien::tienDo(1999)['con_thieu']===1,'One point to Diamond');
$assert(chinhsach_thanhvien::tienDo(2000)['hang_tiep']===null,'Highest tier');
foreach ([-1.0,INF,NAN] as $bad) {
    try { chinhsach_thanhvien::tinhDiem($bad); $assert(false,'Invalid money accepted'); }
    catch (InvalidArgumentException $e) { $checks++; }
}
$items=[['ten'=>'iPhone <14> & 128GB','ma_sp'=>'BP14','hinh'=>'catalog/iphone14.webp','so_luong'=>2,'don_gia'=>500000]];
$html=thongbao_thanhtoan::renderHtml(['id'=>42,'tong_tien'=>1000000],$items,'COD');
$assert(str_contains($html,'https://bpstore.gt.tc/view/image/catalog/iphone14.webp'),'Absolute preview image');
$assert(str_contains($html,'alt="iPhone &lt;14&gt; &amp; 128GB"'),'Safe image alt');
$mail=new PHPMailer\PHPMailer\PHPMailer(true);
$mail->CharSet='UTF-8';
$mail->setFrom('store@example.test','BP Store');
$mail->addAddress('customer@example.test');
$mail->Subject='Ảnh sản phẩm';
$mail->isHTML(true);
$mail->Body=anh_sanpham::nhungVaoThu($mail,$html.$html);
$mail->AltBody='Order #42';
$assert(count($mail->getAttachments())===1,'Deduplicated inline attachment');
$assert(str_contains($mail->Body,'src="cid:product-') && !str_contains($mail->Body,'data-product-image='),'CID image in final email');
$assert($mail->preSend(),'Build complete MIME without sending');
$mime=$mail->getSentMIMEMessage();
$assert(str_contains($mime,'Content-ID: <product-') && str_contains($mime,'Content-Disposition: inline;'),'Inline MIME headers');
foreach (['../../support/config/database.local.php','https://evil.example/x.png','catalog/iphone14.webp" onerror="alert(1)','uploads/missing.png'] as $bad) {
    $assert(anh_sanpham::ten($bad)===null,'Reject unsafe or missing image');
}
$assert(str_contains(anh_sanpham::theEmail(null,'Missing'),'bpstore-logo.png'),'Missing photo fallback');
echo "Member policy and inline email: {$checks} checks passed.\n";
