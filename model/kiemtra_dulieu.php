<?php
require_once __DIR__ . '/xl_database.php';
require_once __DIR__ . '/thanhtoan_qr.php';
require_once __DIR__ . '/voucher.php';
class kiemtra_dulieu extends xl_database
{
    public function baoCao(): array
    {
        $queries = [
            'Tồn kho hoặc giá không hợp lệ' => 'SELECT id FROM sanpham WHERE so_luong_ton<0 OR so_luong_goc<0 OR gia<0 OR so_luong_ton>so_luong_goc',
            'Sản phẩm thiếu mô tả' => "SELECT id FROM sanpham WHERE mo_ta IS NULL OR TRIM(mo_ta)=''",
            'Sản phẩm chưa nhập thương hiệu/thông số/bảo hành' => "SELECT id FROM sanpham WHERE COALESCE(TRIM(thuong_hieu),'')='' OR COALESCE(TRIM(thong_so),'')='' OR COALESCE(TRIM(bao_hanh),'')=''",
            'Đơn mới lệch tổng tiền' => 'SELECT d.id FROM donhang d LEFT JOIN (SELECT id_donhang,SUM(so_luong*don_gia) tong FROM chitietdonhang GROUP BY id_donhang) c ON c.id_donhang=d.id WHERE d.tien_giam IS NOT NULL AND (ABS(d.tong_tien-(COALESCE(c.tong,0)-d.tien_giam+d.phi_ship))>0.01 OR ABS(d.tam_tinh-COALESCE(c.tong,0))>0.01)',
            'Đơn cũ thiếu bản chụp giảm giá (cần đối chiếu thủ công)' => 'SELECT id FROM donhang WHERE tien_giam IS NULL OR tam_tinh IS NULL',
            'Chi tiết đơn cũ thiếu tên tại thời điểm mua' => 'SELECT DISTINCT id_donhang AS id FROM chitietdonhang WHERE ten_sp_snapshot IS NULL',
            'Đơn giao cũ thiếu ngày giao' => "SELECT id FROM donhang WHERE trang_thai IN ('Đã giao','Thành công') AND ngay_giao IS NULL",
            'Đánh giá chưa xác minh nhận hàng (được ẩn trên trang khách)' => "SELECT dg.id FROM danhgia dg WHERE NOT EXISTS (SELECT 1 FROM donhang d JOIN chitietdonhang c ON c.id_donhang=d.id WHERE d.id_khachhang=dg.id_khachhang AND c.id_sanpham=dg.id_sanpham AND d.trang_thai IN ('Đã giao','Thành công'))",
            'Đánh giá trùng khách/sản phẩm' => 'SELECT MIN(id) id FROM danhgia GROUP BY id_khachhang,id_sanpham HAVING COUNT(*)>1',
            'Voucher sai ngày, số lượng hoặc mức giảm' => "SELECT id FROM voucher WHERE so_luong<0 OR ngay_bd>ngay_kt OR gia_tri<0 OR don_toi_thieu<0 OR (loai='phantram' AND gia_tri>100)",
            'Hạng thành viên không khớp điểm' => "SELECT id FROM khachhang WHERE hang<>CASE WHEN diem>=2000 THEN 'Diamond' WHEN diem>=500 THEN 'Gold' ELSE 'Silver' END",
            'Điểm khác tổng đơn thành công (cần kiểm tra số dư cũ)' => "SELECT k.id FROM khachhang k LEFT JOIN (SELECT id_khachhang,SUM(FLOOR(tong_tien/1000)) diem FROM donhang WHERE trang_thai='Thành công' GROUP BY id_khachhang) d ON d.id_khachhang=k.id WHERE k.diem<>COALESCE(d.diem,0)",
            'Đơn PayPal chờ thanh toán đã hết hạn' => "SELECT d.id FROM donhang d JOIN thanhtoan_paypal p ON p.id_donhang=d.id WHERE d.trang_thai='Chờ xác nhận' AND p.expires_at<NOW() AND p.status IN ('created','awaiting','approved','failed')",
            'Đơn kết thúc nhưng PayPal chưa hoàn tiền/đối soát' => "SELECT d.id FROM donhang d JOIN thanhtoan_paypal p ON p.id_donhang=d.id WHERE d.trang_thai IN ('Đã hủy','Bom hàng','Trả hàng') AND p.status NOT IN ('cancelled','refunded')",
            'Đơn đang xử lý sau khi PayPal đã hoàn tiền' => "SELECT d.id FROM donhang d JOIN thanhtoan_paypal p ON p.id_donhang=d.id WHERE d.trang_thai NOT IN ('Đã hủy','Bom hàng','Trả hàng') AND p.status='refunded'",
            'Tồn kho lệch bản ghi biến động mới nhất' => 'SELECT sp.id FROM sanpham sp JOIN lichsu_kho k ON k.id=(SELECT MAX(k2.id) FROM lichsu_kho k2 WHERE k2.id_sanpham=sp.id) WHERE sp.so_luong_ton<>k.ton_sau',
            'Sản phẩm cũ chưa có mốc kiểm kê (chưa đủ chứng cứ đối soát kho)' => 'SELECT id FROM sanpham sp WHERE NOT EXISTS(SELECT 1 FROM lichsu_kho k WHERE k.id_sanpham=sp.id)',
        ];
        $out = [];
        if (voucher::scopeReady()) {
            $queries['Phạm vi voucher không hợp lệ']="SELECT id FROM voucher WHERE pham_vi NOT IN ('tatca','danhmuc','sanpham') OR (pham_vi='tatca' AND (id_danhmuc IS NOT NULL OR id_sanpham IS NOT NULL)) OR (pham_vi='danhmuc' AND (id_danhmuc IS NULL OR id_sanpham IS NOT NULL)) OR (pham_vi='sanpham' AND (id_sanpham IS NULL OR id_danhmuc IS NOT NULL))";
            $queries['Voucher phạm vi riêng còn hiệu lực nhưng đối tượng không bán']="SELECT v.id FROM voucher v LEFT JOIN danhmuc dm ON dm.id=v.id_danhmuc LEFT JOIN sanpham sp ON sp.id=v.id_sanpham WHERE v.so_luong>0 AND CURDATE() BETWEEN v.ngay_bd AND v.ngay_kt AND ((v.pham_vi='danhmuc' AND (dm.id IS NULL OR dm.ngung_ban=1)) OR (v.pham_vi='sanpham' AND (sp.id IS NULL OR sp.ngung_ban=1)))";
            $queries['Bản chụp voucher của đơn lệch tiền hàng áp dụng']='SELECT id FROM donhang WHERE voucher_pham_vi IS NOT NULL AND (voucher_tien_ap_dung<0 OR voucher_tien_ap_dung>tam_tinh OR tien_giam>voucher_tien_ap_dung)';
        }
        if (thanhtoan_qr::schemaReady()) {
            $queries['QR lệch tổng tiền đơn']="SELECT q.id_donhang id FROM thanhtoan_qr q JOIN donhang d ON d.id=q.id_donhang WHERE q.amount_vnd<>d.tong_tien";
            $queries['QR chưa nhận tiền nhưng đã xử lý giao hàng']="SELECT q.id_donhang id FROM thanhtoan_qr q JOIN donhang d ON d.id=q.id_donhang WHERE d.trang_thai IN ('Xác nhận đơn hàng','Đang giao','Đã giao','Thành công') AND q.status NOT IN ('paid','refunded')";
            $queries['QR chờ hoàn tiền']="SELECT id_donhang id FROM thanhtoan_qr WHERE status='refund_due'";
            $queries['QR đã hoàn, cần hủy đơn để giải phóng kho']="SELECT q.id_donhang id FROM thanhtoan_qr q JOIN donhang d ON d.id=q.id_donhang WHERE q.status='refunded' AND d.trang_thai IN ('Chờ xác nhận','Xác nhận đơn hàng')";
        }
        foreach ($queries as $label => $sql) {
            $rows = $this->select($sql);
            $out[] = ['noi_dung' => $label, 'so_luong' => count($rows), 'ids' => array_column($rows, 'id')];
        }
        return $out;
    }

    public function xemBang(array $tables, string $table, int $page): array
    {
        if (!isset($tables[$table])) throw new InvalidArgumentException('Bảng không hợp lệ');
        $tableCounts = [];
        foreach ($tables as $name => $_) $tableCounts[$name] = (int)$this->pdo->query("SELECT COUNT(*) FROM `$name`")->fetchColumn();
        $columns = $this->pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
        $visibleColumns = array_values(array_filter($columns, static fn($c) => !preg_match('/(?:mat_khau|password|token|secret|otp|checkout_key|request_id|client_id|merchant_id)/i', $c)));
        $pageSize = 25;
        $totalRows = $tableCounts[$table];
        $totalPages = max(1, (int)ceil($totalRows/$pageSize));
        $page = max(1, min($page, $totalPages));
        $offset = ($page-1)*$pageSize;
        $fields = implode(',', array_map(static fn($c) => "`$c`", $visibleColumns));
        $direction=$table==='sanpham'?'ASC':'DESC';
        $rows = $this->select("SELECT $fields FROM `$table` ORDER BY 1 $direction LIMIT $pageSize OFFSET $offset");
        return compact('tableCounts','columns','visibleColumns','pageSize','totalRows','totalPages','page','rows');
    }
}
