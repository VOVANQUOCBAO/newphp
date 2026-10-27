<?php
require_once __DIR__.'/xl_database.php';
require_once __DIR__.'/anh_sanpham.php';
class thongbao_thanhtoan extends xl_database
{
    public static function sauThanhToan(int $id): void
    {
        try { $m=new self(); if ($m->ghiNhan($id)) $m->gui($id); }
        catch (Throwable $e) { error_log('Payment email pending for order #'.$id); }
    }

    public function ghiNhan(int $id): bool
    {
        $this->begin();
        try {
            $order=$this->selectOne('SELECT d.*,k.email,k.ho_ten FROM donhang d JOIN khachhang k ON k.id=d.id_khachhang WHERE d.id=? FOR UPDATE',[$id]);
            if (!$order || in_array($order['trang_thai'],['Đã hủy','Trả hàng','Bom hàng'],true)) { $this->rollback();return false; }
            $qr=$this->selectOne('SELECT status FROM thanhtoan_qr WHERE id_donhang=?',[$id]);
            $paypal=$this->selectOne('SELECT status FROM thanhtoan_paypal WHERE id_donhang=?',[$id]);
            $paid=$qr ? $qr['status']==='paid' : ($paypal ? $paypal['status']==='paid' : $order['trang_thai']==='Thành công');
            if (!$paid) { $this->rollback();return false; }
            $money=static fn($value)=>number_format((float)$value,0,',','.').'đ';
            $items=$this->select('SELECT c.so_luong,c.don_gia,COALESCE(c.ten_sp_snapshot,s.ten_sp) ten,COALESCE(c.ma_sp_snapshot,s.ma_sp) ma_sp,COALESCE(NULLIF(c.hinh_snapshot,\'\'),s.hinh) hinh
                FROM chitietdonhang c JOIN sanpham s ON s.id=c.id_sanpham WHERE c.id_donhang=? ORDER BY c.id',[$id]);
            $paymentMethod=$qr?'Chuyển khoản QR':($paypal?'PayPal':'COD');
            $text="BP STORE · BIÊN NHẬN THANH TOÁN\n\nXin chào ".($order['ho_ten'] ?? 'Quý khách').",\nCảm ơn bạn đã lựa chọn BP Store. Đơn hàng #{$id} đã được thanh toán thành công.\n\n";
            foreach ($items as $item) {
                $quantity=(int)$item['so_luong'];
                $unit=(float)$item['don_gia'];
                $text.='• '.$item['ten'].(!empty($item['ma_sp'])?' · Mã '.$item['ma_sp']:'')."\n  {$quantity} × ".$money($unit).' = '.$money($unit*$quantity)."\n";
            }
            $text.="\nMã đơn hàng: #{$id}\nNgày đặt: ".($order['ngay_dat'] ?? date('Y-m-d H:i:s'))."\nPhương thức thanh toán: {$paymentMethod}\nTrạng thái: Đã thanh toán\n";
            if (!empty($order['dia_chi'])) $text.="Địa chỉ nhận hàng: ".$order['dia_chi']."\n";
            if (!empty($order['sdt'])) $text.="Số điện thoại: ".$order['sdt']."\n";
            $text.="\nTạm tính: ".$money($order['tam_tinh'] ?? 0)."\n";
            if (!empty($order['ma_voucher'])) $text.="Voucher: ".$order['ma_voucher']."\n";
            $text.="Giảm giá: -".$money($order['tien_giam'] ?? 0)."\nPhí giao hàng: ".$money($order['phi_ship'] ?? 0)."\nTổng đã thanh toán: ".$money($order['tong_tien'] ?? 0)."\n\nTheo dõi đơn hàng: https://bpstore.gt.tc/controller/index.php?act=theodoidonhang&id={$id}\n\nBP Store · 0338 008 284 · qbaor39@gmail.com\n92 Bùi Tư Toàn, Bình Tân";
            $title='[BP Store] Xác nhận thanh toán đơn #'.$id;
            $created=$this->execute('INSERT IGNORE INTO email_thanhtoan (id_donhang,nguoi_nhan,tieu_de,noi_dung) VALUES (?,?,?,?)',[$id,$order['email'],$title,$text]);
            if ($created) {
                require_once __DIR__.'/email.php';
                (new email())->luuThu((int)$order['id_khachhang'],$title,$text,'thanhtoan');
            }
            $this->commit();return true;
        } catch (Throwable $e) { $this->rollback();throw $e; }
    }

    public function gui(int $id): array
    {
        if (!$this->ghiNhan($id)) return ['ok'=>false,'loi'=>'Đơn chưa nhận tiền hoặc đã hủy/trả; không gửi thư cảm ơn thanh toán.'];
        if (!$this->execute("UPDATE email_thanhtoan SET trang_thai='dang_gui',so_lan_thu=so_lan_thu+1 WHERE id_donhang=? AND trang_thai IN ('cho_gui','loi')",[$id])) {
            return ['ok'=>false,'loi'=>'Thư đã gửi hoặc đang được gửi; không gửi trùng.'];
        }
        $row=$this->selectOne('SELECT * FROM email_thanhtoan WHERE id_donhang=?',[$id]);
        try {
            $order=$this->selectOne('SELECT d.*,k.ho_ten FROM donhang d JOIN khachhang k ON k.id=d.id_khachhang WHERE d.id=?',[$id]);
            if (!$order) throw new RuntimeException('Không tìm thấy thông tin đơn hàng.');
            $items=$this->select('SELECT c.so_luong,c.don_gia,COALESCE(c.ten_sp_snapshot,s.ten_sp) ten,COALESCE(c.ma_sp_snapshot,s.ma_sp) ma_sp,COALESCE(NULLIF(c.hinh_snapshot,\'\'),s.hinh) hinh
                FROM chitietdonhang c JOIN sanpham s ON s.id=c.id_sanpham WHERE c.id_donhang=? ORDER BY c.id',[$id]);
            $html=self::renderHtml($order,$items,$this->layPhuongThuc((int)$id));
            $result=$this->deliver($row['nguoi_nhan'],$row['tieu_de'],$row['noi_dung'],$html);
        } catch (Throwable $e) { $result=['ok'=>false]; }
        $this->update('email_thanhtoan',['trang_thai'=>!empty($result['ok'])?'da_gui':'loi','loi'=>!empty($result['ok'])?null:'Chưa gửi được. Kiểm tra cấu hình và kết nối dịch vụ gửi thư.','ngay_gui'=>!empty($result['ok'])?date('Y-m-d H:i:s'):null],'id_donhang=?',[$id]);
        return !empty($result['ok']) ? ['ok'=>true] : ['ok'=>false,'loi'=>'Chưa gửi được email. Đơn và thanh toán đã được lưu; thư được giữ để gửi lại.'];
    }

    private function layPhuongThuc(int $id): string
    {
        if ($this->selectOne('SELECT id_donhang FROM thanhtoan_qr WHERE id_donhang=?',[$id])) return 'Chuyển khoản QR';
        if ($this->selectOne('SELECT id_donhang FROM thanhtoan_paypal WHERE id_donhang=?',[$id])) return 'PayPal';
        return 'COD';
    }

    public static function renderHtml(array $order,array $items,string $paymentMethod): string
    {
        $escape=static fn($value): string=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $money=static fn($value): string=>number_format((float)$value,0,',','.').'đ';
        $id=max(0,(int)($order['id'] ?? 0));
        $name=trim((string)($order['ho_ten'] ?? '')) ?: 'Quý khách';
        $dateRaw=(string)($order['ngay_dat'] ?? '');
        $dateValue=$dateRaw!=='' ? strtotime($dateRaw) : false;
        $date=$dateValue!==false ? date('d/m/Y H:i',$dateValue) : 'Đã ghi nhận';
        $subtotalFallback=0.0;
        $rows='';
        foreach ($items as $item) {
            $quantity=max(0,(int)($item['so_luong'] ?? 0));
            $unit=max(0,(float)($item['don_gia'] ?? 0));
            $lineTotal=$quantity*$unit;
            $subtotalFallback+=$lineTotal;
            $productName=$escape($item['ten'] ?? 'Sản phẩm');
            $productImage=anh_sanpham::theEmail($item['hinh'] ?? null,(string)($item['ten'] ?? 'Sản phẩm'));
            $productCode=trim((string)($item['ma_sp'] ?? ''));
            $rows.='<tr>'
                .'<td style="padding:16px 12px;border-bottom:1px solid #e8ebf0;color:#172033;font-size:14px;line-height:1.5">'.$productImage.'<strong>'.$productName.'</strong>'
                .($productCode!==''?'<br><span style="color:#7b8494;font-size:12px">Mã: '.$escape($productCode).'</span>':'')
                .'<br><span style="color:#7b8494;font-size:12px">Đơn giá: '.$money($unit).'</span></td>'
                .'<td align="center" style="padding:16px 8px;border-bottom:1px solid #e8ebf0;color:#475569;font-size:14px;white-space:nowrap">'.$quantity.'</td>'
                .'<td align="right" style="padding:16px 12px;border-bottom:1px solid #e8ebf0;color:#172033;font-size:14px;font-weight:700;white-space:nowrap">'.$money($lineTotal).'</td>'
                .'</tr>';
        }
        if ($rows==='') $rows='<tr><td colspan="3" style="padding:18px 12px;color:#64748b">Thông tin sản phẩm đang được cập nhật.</td></tr>';
        $subtotal=(float)($order['tam_tinh'] ?? $subtotalFallback);
        $discount=max(0,(float)($order['tien_giam'] ?? 0));
        $shipping=max(0,(float)($order['phi_ship'] ?? 0));
        $total=max(0,(float)($order['tong_tien'] ?? ($subtotal-$discount+$shipping)));
        $voucher=trim((string)($order['ma_voucher'] ?? ''));
        $address=trim((string)($order['dia_chi'] ?? ''));
        $phone=trim((string)($order['sdt'] ?? ''));
        $trackingUrl='https://bpstore.gt.tc/controller/index.php?act=theodoidonhang&id='.$id;
        $shippingDisplay=$shipping>0?$money($shipping):'Miễn phí';

        return '<!doctype html><html lang="vi"><body style="margin:0;padding:0;background:#f3f5f8;font-family:Arial,Helvetica,sans-serif;color:#172033">'
            .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f3f5f8"><tr><td align="center" style="padding:28px 12px">'
            .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:680px;background:#ffffff;border:1px solid #e5e9f0;border-radius:16px;overflow:hidden">'
            .'<tr><td style="padding:24px 32px;background:#111b43;color:#ffffff">'
            .'<div style="font-size:25px;font-weight:800;letter-spacing:.6px">BP <span style="color:#ff6a00">STORE</span></div>'
            .'<div style="margin-top:5px;color:#dce2f0;font-size:13px">Điện thoại &amp; phụ kiện</div></td></tr>'
            .'<tr><td style="padding:30px 32px 22px">'
            .'<div style="display:inline-block;padding:7px 11px;border-radius:20px;background:#e9f8ef;color:#167344;font-size:12px;font-weight:700;letter-spacing:.4px">✓ &nbsp;THANH TOÁN THÀNH CÔNG</div>'
            .'<h1 style="margin:18px 0 8px;color:#111b43;font-size:25px;line-height:1.3">Cảm ơn bạn đã đặt hàng</h1>'
            .'<p style="margin:0;color:#4b5565;font-size:15px;line-height:1.7">Xin chào <strong>'.$escape($name).'</strong>, BP Store đã nhận được thanh toán cho đơn hàng của bạn. Thông tin đơn được gửi bên dưới để bạn tiện theo dõi.</p>'
            .'</td></tr>'
            .'<tr><td style="padding:0 32px 22px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f7f8fb;border:1px solid #e8ebf0;border-radius:10px"><tr>'
            .'<td style="padding:15px 18px;color:#667085;font-size:13px">Mã đơn hàng<br><strong style="display:inline-block;margin-top:5px;color:#111b43;font-size:16px">#'.$id.'</strong></td>'
            .'<td style="padding:15px 18px;color:#667085;font-size:13px">Ngày đặt<br><strong style="display:inline-block;margin-top:5px;color:#111b43;font-size:14px">'.$escape($date).'</strong></td>'
            .'<td style="padding:15px 18px;color:#667085;font-size:13px">Thanh toán<br><strong style="display:inline-block;margin-top:5px;color:#111b43;font-size:14px">'.$escape($paymentMethod).'</strong></td>'
            .'</tr></table></td></tr>'
            .'<tr><td style="padding:0 32px 10px"><h2 style="margin:0;color:#111b43;font-size:17px">Chi tiết đơn hàng</h2></td></tr>'
            .'<tr><td style="padding:0 32px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse">'
            .'<tr style="background:#f7f8fb"><th align="left" style="padding:11px 12px;color:#667085;font-size:11px;text-transform:uppercase;letter-spacing:.4px">Sản phẩm</th><th align="center" style="padding:11px 8px;color:#667085;font-size:11px;text-transform:uppercase">SL</th><th align="right" style="padding:11px 12px;color:#667085;font-size:11px;text-transform:uppercase">Thành tiền</th></tr>'.$rows
            .'</table></td></tr>'
            .'<tr><td style="padding:18px 32px 22px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">'
            .'<tr><td style="padding:5px 0;color:#667085;font-size:14px">Tạm tính</td><td align="right" style="padding:5px 0;color:#344054;font-size:14px">'.$money($subtotal).'</td></tr>'
            .($voucher!==''?'<tr><td style="padding:5px 0;color:#667085;font-size:14px">Giảm giá · Voucher '.$escape($voucher).'</td><td align="right" style="padding:5px 0;color:#167344;font-size:14px">−'.$money($discount).'</td></tr>':'<tr><td style="padding:5px 0;color:#667085;font-size:14px">Giảm giá</td><td align="right" style="padding:5px 0;color:#167344;font-size:14px">−'.$money($discount).'</td></tr>')
            .'<tr><td style="padding:5px 0;color:#667085;font-size:14px">Phí giao hàng</td><td align="right" style="padding:5px 0;color:#344054;font-size:14px">'.$shippingDisplay.'</td></tr>'
            .'<tr><td style="padding:13px 0 0;border-top:1px solid #e8ebf0;color:#111b43;font-size:16px;font-weight:700">Tổng đã thanh toán</td><td align="right" style="padding:13px 0 0;border-top:1px solid #e8ebf0;color:#f05a00;font-size:21px;font-weight:800">'.$money($total).'</td></tr>'
            .'</table></td></tr>'
            .'<tr><td style="padding:0 32px 22px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#fff8f1;border-left:3px solid #ff6a00"><tr><td style="padding:14px 16px;color:#475467;font-size:13px;line-height:1.7"><strong style="color:#111b43">Thông tin nhận hàng</strong><br>'
            .($address!==''?'Địa chỉ: '.$escape($address).'<br>':'').($phone!==''?'Điện thoại: '.$escape($phone):'')
            .'</td></tr></table></td></tr>'
            .'<tr><td align="center" style="padding:2px 32px 30px"><a href="'.$escape($trackingUrl).'" style="display:inline-block;padding:13px 24px;border-radius:8px;background:#111b43;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700">Theo dõi đơn hàng</a></td></tr>'
            .'<tr><td style="padding:20px 32px;background:#f7f8fb;border-top:1px solid #e8ebf0;color:#667085;font-size:12px;line-height:1.8">'
            .'<strong style="color:#111b43">BP Store</strong> · 92 Bùi Tư Toàn, Bình Tân<br>'
            .'<a href="tel:+84338008284" style="color:#111b43;text-decoration:none">0338 008 284</a> &nbsp;·&nbsp; <a href="mailto:qbaor39@gmail.com" style="color:#111b43;text-decoration:none">qbaor39@gmail.com</a><br>'
            .'Nếu cần hỗ trợ về đơn hàng, hãy liên hệ với chúng tôi qua email hoặc số điện thoại trên.'
            .'</td></tr></table></td></tr></table></body></html>';
    }

    protected function deliver(string $to,string $title,string $body,string $html): array
    {
        require_once dirname(__DIR__).'/controller/xuly_email.php';
        return (new xuly_email())->guiSmtp($to,$title,$body,$html);
    }
    public function layDS(): array { return $this->select('SELECT id_donhang,nguoi_nhan,tieu_de,trang_thai,so_lan_thu,loi,ngay_gui,ngay_tao FROM email_thanhtoan ORDER BY id_donhang DESC LIMIT 200'); }
}
