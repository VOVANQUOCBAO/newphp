<?php
require_once __DIR__ . '/xl_database.php';

class thanhtoan_qr extends xl_database
{
    public static function config(): array
    {
        $file = dirname(__DIR__) . '/support/config/bank.local.php';
        $cfg = is_file($file) ? require $file : [];
        return is_array($cfg) ? $cfg : [];
    }

    public static function ready(?array $cfg = null): bool
    {
        $cfg ??= self::config();
        return !empty($cfg['enabled']) && preg_match('/^[0-9]{6}$/D', (string)($cfg['bank_bin'] ?? ''))
            && preg_match('/^[0-9]{6,19}$/D', (string)($cfg['account_number'] ?? ''))
            && preg_match('/^[A-Z][A-Z ]{2,99}$/D', (string)($cfg['account_name'] ?? ''))
            && trim((string)($cfg['bank_name'] ?? '')) !== '' && mb_strlen($cfg['bank_name']) <= 100;
    }

    public static function schemaReady(): bool
    {
        return (bool)csdl::shared()->query("SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='thanhtoan_qr'")->fetchColumn();
    }

    public function lay(int $id, ?int $owner = null, bool $lock = false): ?array
    {
        if (!self::schemaReady()) return null;
        return $this->selectOne('SELECT q.*,d.id_khachhang,d.trang_thai AS order_status FROM thanhtoan_qr q JOIN donhang d ON d.id=q.id_donhang WHERE q.id_donhang=?'
            . ($owner !== null ? ' AND d.id_khachhang=?' : '') . ($lock ? ' FOR UPDATE' : ''), $owner !== null ? [$id,$owner] : [$id]);
    }

    // Runs inside the order transaction, with the total calculated by the server.
    public function ganDon(int $id, float $amount, ?array $cfg = null): void
    {
        $cfg ??= self::config();
        if (!self::ready($cfg) || !self::schemaReady()) throw new DomainException('Chuyển khoản QR chưa được bật. Vui lòng chọn phương thức khác.');
        if ($amount <= 0 || $amount !== floor($amount)) throw new DomainException('QR chỉ nhận tổng tiền nguyên dương theo VND. Đơn 0đ vui lòng chọn COD.');
        $this->insert('thanhtoan_qr', ['id_donhang'=>$id,'bank_bin'=>$cfg['bank_bin'],'bank_name'=>$cfg['bank_name'],
            'account_number'=>$cfg['account_number'],'account_name'=>$cfg['account_name'],'amount_vnd'=>$amount,
            'transfer_content'=>'BP'.$id.strtoupper(bin2hex(random_bytes(4))), 'status'=>'waiting','expires_at'=>date('Y-m-d H:i:s',time()+1800)]);
    }

    public static function qrUrl(array $payment): string
    {
        return 'https://img.vietqr.io/image/'.rawurlencode($payment['bank_bin']).'-'.rawurlencode($payment['account_number']).'-compact2.svg?'
            . http_build_query(['amount'=>number_format((float)$payment['amount_vnd'],0,'',''),'addInfo'=>$payment['transfer_content'],'accountName'=>$payment['account_name']], '', '&', PHP_QUERY_RFC3986);
    }

    public static function label(?array $payment): string
    {
        return 'Chuyển khoản QR · '.match($payment['status'] ?? '') {
            'reported'=>'Chờ admin kiểm tra tiền','paid'=>'Đã nhận tiền','cancelled'=>'Đơn đã hủy',
            'refund_due'=>'Chờ hoàn tiền','refunded'=>'Đã hoàn tiền',default=>'Chờ chuyển khoản',
        };
    }

    private function history(int $id, string $status, string $actor, string $note): void
    {
        $this->insert('lichsu_donhang',['id_donhang'=>$id,'trang_thai_cu'=>$status,'trang_thai_moi'=>$status,'tac_nhan'=>$actor,'ghi_chu'=>$note]);
    }

    public function baoDaChuyen(int $id, int $owner): array
    {
        return $this->change($id, function(array $order, array $q) use ($id,$owner) {
            if ((int)$order['id_khachhang'] !== $owner) throw new DomainException('Không tìm thấy thanh toán của bạn.');
            if ($q['status'] === 'reported') return;
            if ($q['status'] !== 'waiting' || $order['trang_thai'] !== donhang::CHO_XAC_NHAN || strtotime($q['expires_at']) <= time())
                throw new DomainException('Đơn không còn nhận thông báo chuyển khoản. Nếu đã chuyển tiền, vui lòng liên hệ cửa hàng.');
            if ($q['customer_reported_at'] !== null) throw new DomainException('Cửa hàng đã đối soát thông báo trước. Vui lòng liên hệ nếu cần kiểm tra lại.');
            $this->update('thanhtoan_qr',['status'=>'reported','customer_reported_at'=>date('Y-m-d H:i:s')],'id_donhang=?',[$id]);
            $this->history($id,$order['trang_thai'],'Khách #'.$owner,'Khách báo đã chuyển khoản; chưa xác nhận nhận tiền.');
        });
    }

    private function admin(int $actor): void
    {
        $user = $this->selectOne('SELECT vai_tro,trang_thai FROM nhanvien WHERE id=?',[$actor]);
        if (!$user || (int)$user['trang_thai'] !== 1 || !in_array($user['vai_tro'],['Admin','SuperAdmin'],true)) throw new DomainException('Chỉ quản trị viên được xác nhận tiền.');
    }

    public function khongNhanTien(int $id, int $actor): array
    {
        return $this->change($id,function(array $order,array $q) use ($id,$actor) {
            $this->admin($actor);
            if ($q['status'] !== 'reported' || $order['trang_thai'] !== donhang::CHO_XAC_NHAN) throw new DomainException('Không có thông báo chuyển khoản đang chờ đối soát.');
            $this->update('thanhtoan_qr',['status'=>'waiting'],'id_donhang=?',[$id]);
            $this->history($id,$order['trang_thai'],'Nhân viên #'.$actor,'Đã kiểm tra ngân hàng, chưa nhận tiền. Đơn có thể hủy hoặc hết hạn.');
        });
    }

    private function reference(string $reference): string
    {
        $reference = strtoupper(trim($reference));
        if (!preg_match('~^[A-Z0-9][A-Z0-9./_-]{2,99}$~D',$reference)) throw new DomainException('Nhập mã giao dịch ngân hàng từ 3 đến 100 ký tự, không dấu hoặc khoảng trắng.');
        return $reference;
    }

    public function xacNhanNhan(int $id, int $actor, string $reference, mixed $amount): array
    {
        return $this->change($id,function(array $order,array $q) use ($id,$actor,$reference,$amount) {
            $this->admin($actor);
            $ref = $this->reference($reference);
            if (!$this->soTienHopLe($amount) || number_format((float)$amount,2,'.','') !== $q['amount_vnd']) throw new DomainException('Số tiền thực nhận phải khớp toàn bộ tổng thanh toán. Khoản thiếu/thừa cần đối soát riêng.');
            if ($q['received_reference'] !== null) {
                if ($q['received_reference'] === $ref) return;
                throw new DomainException('Đơn đã có giao dịch nhận tiền; không ghi nhận thêm.');
            }
            if (!in_array($q['status'],['waiting','reported','cancelled'],true)) throw new DomainException('Thanh toán không ở trạng thái nhận tiền.');
            $late = $order['trang_thai'] === donhang::DA_HUY;
            if (!$late && $order['trang_thai'] !== donhang::CHO_XAC_NHAN) throw new DomainException('Trạng thái đơn không hợp lệ để nhận tiền.');
            $this->update('thanhtoan_qr',['status'=>$late ? 'refund_due' : 'paid','received_reference'=>$ref,'confirmed_by'=>$actor,'received_at'=>date('Y-m-d H:i:s')],'id_donhang=?',[$id]);
            $this->history($id,$order['trang_thai'],'Nhân viên #'.$actor,($late ? 'Tiền đến sau khi hủy; chờ hoàn, không khôi phục đơn. ' : 'Đã kiểm tra và nhận đủ chuyển khoản. ').'Mã giao dịch: '.$ref);
        });
    }

    public function xacNhanHoan(int $id, int $actor, string $reference): array
    {
        return $this->change($id,function(array $order,array $q) use ($id,$actor,$reference) {
            $this->admin($actor);
            $ref = $this->reference($reference);
            if ($q['status'] === 'refunded' && $q['refund_reference'] === $ref) return;
            if (!in_array($q['status'],['paid','refund_due'],true) || !in_array($order['trang_thai'],[donhang::CHO_XAC_NHAN,donhang::XAC_NHAN,donhang::DA_HUY,donhang::TRA_HANG,donhang::BOM_HANG],true))
                throw new DomainException('Chỉ xác nhận hoàn khoản đã nhận cho đơn chờ hủy hoặc đã nhận lại hàng.');
            if ($ref === $q['received_reference']) throw new DomainException('Mã giao dịch hoàn phải khác mã nhận tiền.');
            $this->update('thanhtoan_qr',['status'=>'refunded','refund_reference'=>$ref,'refunded_by'=>$actor,'refunded_at'=>date('Y-m-d H:i:s')],'id_donhang=?',[$id]);
            $this->execute("UPDATE trahang SET trang_thai_hoan='Đã hoàn tiền',tham_chieu_hoan=? WHERE id_donhang=? AND trang_thai_hoan='Chờ hoàn tiền'",[$ref,$id]);
            $this->history($id,$order['trang_thai'],'Nhân viên #'.$actor,'Đã hoàn chuyển khoản theo chứng từ: '.$ref. (in_array($order['trang_thai'],[donhang::CHO_XAC_NHAN,donhang::XAC_NHAN],true) ? '. Cần hủy đơn để giải phóng kho.' : ''));
        });
    }

    private function change(int $id, callable $apply): array
    {
        try {
            $this->begin();
            $order = $this->selectOne('SELECT * FROM donhang WHERE id=? FOR UPDATE',[$id]);
            $q = $order ? $this->lay($id,null,true) : null;
            if (!$q) throw new DomainException('Không tìm thấy đơn chuyển khoản.');
            $apply($order,$q);
            $this->commit();
            return ['ok'=>true];
        } catch (Throwable $e) {
            $this->rollback();
            if ($e instanceof DomainException) return ['ok'=>false,'loi'=>$e->getMessage()];
            error_log('QR payment: '.$e->getMessage());
            return ['ok'=>false,'loi'=>$e instanceof PDOException && $e->getCode()==='23000' ? 'Mã giao dịch đã được ghi nhận. Vui lòng kiểm tra lại.' : 'Không lưu được thanh toán. Vui lòng thử lại.'];
        }
    }
}
