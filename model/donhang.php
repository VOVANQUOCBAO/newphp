<?php

require_once __DIR__ . '/xl_database.php';
require_once __DIR__ . '/sanpham.php';
require_once __DIR__ . '/chitietdonhang.php';
require_once __DIR__ . '/voucher.php';
require_once __DIR__ . '/khachhang.php';
require_once __DIR__ . '/khuyenmai.php';
require_once __DIR__ . '/thanhtoan_paypal.php';
require_once __DIR__ . '/thanhtoan_qr.php';

class donhang extends xl_database
{
    public const CHO_XAC_NHAN = 'Chờ xác nhận';
    public const XAC_NHAN = 'Xác nhận đơn hàng';
    public const DANG_GIAO = 'Đang giao';
    public const DA_GIAO = 'Đã giao';
    public const THANH_CONG = 'Thành công';
    public const DA_HUY = 'Đã hủy';
    public const BOM_HANG = 'Bom hàng';
    public const TRA_HANG = 'Trả hàng';
    public const PHI_SHIP_MAC_DINH = 30000.0;

    public const LUONG = [
        self::CHO_XAC_NHAN => [self::XAC_NHAN, self::DA_HUY],
        self::XAC_NHAN => [self::DANG_GIAO, self::DA_HUY],
        self::DANG_GIAO => [self::DA_GIAO, self::BOM_HANG, self::TRA_HANG],
        self::DA_GIAO => [self::THANH_CONG, self::TRA_HANG],
        self::THANH_CONG => [self::TRA_HANG], self::DA_HUY => [], self::BOM_HANG => [], self::TRA_HANG => [],
    ];

    public function layTheoKhach(int $idKh): array
    {
        return $this->kemThanhToan($this->select('SELECT * FROM donhang WHERE id_khachhang = ? ORDER BY ngay_dat DESC, id DESC', [$idKh]));
    }

    public function layDS(): array
    {
        return $this->kemThanhToan($this->select('SELECT dh.*, kh.ho_ten, kh.email FROM donhang dh
            JOIN khachhang kh ON kh.id = dh.id_khachhang ORDER BY dh.ngay_dat DESC, dh.id DESC'));
    }

    private function kemThanhToan(array $orders): array
    {
        $model = thanhtoan_paypal::schemaReady() ? new thanhtoan_paypal() : null;
        foreach ($orders as &$order) {
            $order['thanhtoan'] = $model ? thanhtoan_paypal::publicData($model->lay((int) $order['id'])) : null;
            $order['thanhtoan_qr'] = (new thanhtoan_qr())->lay((int)$order['id']);
            $order['tra_hang'] = $this->selectOne('SELECT * FROM trahang WHERE id_donhang=?', [$order['id']]);
            $order['chitiet'] = (new chitietdonhang())->layTheoDon((int)$order['id']);
        }
        unset($order);
        return $orders;
    }

    public function layTheoId(int $id): ?array
    {
        return $this->selectOne('SELECT * FROM donhang WHERE id = ?', [$id]);
    }

    public function layTrangThai(int $id): ?array
    {
        $dh = $this->layTheoId($id);
        if ($dh !== null) {
            $dh['chitiet'] = (new chitietdonhang())->layTheoDon($id);
            $dh['lichsu'] = $this->select('SELECT * FROM lichsu_donhang WHERE id_donhang=? ORDER BY id', [$id]);
            $dh['tra_hang'] = $this->selectOne('SELECT * FROM trahang WHERE id_donhang=?', [$id]);
            $dh['timeline'] = [self::CHO_XAC_NHAN, self::XAC_NHAN, self::DANG_GIAO, self::DA_GIAO, self::THANH_CONG];
            $dh['thanhtoan'] = thanhtoan_paypal::schemaReady() ? thanhtoan_paypal::publicData((new thanhtoan_paypal())->lay($id)) : null;
            $dh['thanhtoan_qr'] = (new thanhtoan_qr())->lay($id);
        }
        return $dh;
    }

    public function taoDon(int $idKh, array $gioHang, array $info, ?string $maVoucher = null): array
    {
        $diaChi = trim((string) ($info['dia_chi'] ?? ''));
        $sdt = trim((string) ($info['sdt'] ?? ''));
        if ($diaChi === '' || mb_strlen($diaChi) > 255 || !preg_match('/^\\+?[0-9][0-9 .()-]{7,18}$/', $sdt)) {
            return ['ok' => false, 'loi' => 'Dia chi hoac so dien thoai khong hop le'];
        }
        if ($gioHang === []) {
            return ['ok' => false, 'loi' => 'Gio hang trong'];
        }
        $gio = [];
        foreach ($gioHang as $idSp => $sl) {
            if (!$this->soNguyenHopLe($idSp, 1) || !$this->soNguyenHopLe($sl, 1)) {
                return ['ok' => false, 'loi' => 'Ma san pham va so luong mua phai la so nguyen duong'];
            }
            $gio[(int) $idSp] = (int) $sl;
        }
        ksort($gio, SORT_NUMERIC); // Khoa san pham theo cung thu tu giua cac giao dich.
        $maVc = strtoupper(trim($maVoucher ?? ''));

        try {
            $this->begin();
            $kh = $this->selectOne('SELECT * FROM khachhang WHERE id = ? FOR UPDATE', [$idKh]);
            if ($kh === null || (int) $kh['trang_thai'] !== 1) {
                throw new DomainException('Khach hang khong ton tai hoac da bi khoa');
            }
            $key = $info['checkout_key'] ?? null;
            if ($key !== null) {
                if (!is_string($key) || !preg_match('/^[a-f0-9]{64}$/', $key)) throw new DomainException('Phiên đặt hàng không hợp lệ');
                $existing = $this->selectOne('SELECT id, tong_tien FROM donhang WHERE checkout_key=? AND id_khachhang=?', [$key, $idKh]);
                if ($existing) { $this->rollback(); return ['ok' => true, 'id' => $existing['id'], 'tong_tien' => $existing['tong_tien'], 'dong' => []]; }
            }
            $spModel = new sanpham();
            $kmModel = new khuyenmai();
            $dong = [];
            $tamTinh = 0.0;
            foreach ($gio as $idSp => $sl) {
                $sp = $this->selectOne('SELECT * FROM sanpham WHERE id = ? FOR UPDATE', [$idSp]);
                if ($sp === null || !empty($sp['ngung_ban']) || (int) $sp['so_luong_ton'] < $sl) {
                    throw new DomainException('San pham khong ton tai hoac khong du ton kho');
                }
                $gia = $kmModel->giaSauKM($sp)['gia_km'];
                $dong[] = ['id_sanpham' => $idSp, 'so_luong' => $sl, 'don_gia' => $gia,
                    'id_danhmuc'=>(int)$sp['id_danhmuc'],
                    'ten_sp' => $sp['ten_sp'], 'ma_sp' => $sp['ma_sp'], 'hinh' => $sp['hinh']];
                $tamTinh = round($tamTinh + $gia * $sl, 2);
            }

            $phiShip = (new khachhang())->quyenLoiHang($kh['hang'])['mien_ship'] ? 0.0 : self::PHI_SHIP_MAC_DINH;
            $giam = 0.0;
            $voucherId = null;
            $voucherSnapshot=[];
            $vcModel = new voucher();
            if ($maVc !== '') {
                $vc = $vcModel->kiemTra($maVc, $kh['hang'], $tamTinh, $phiShip, true, $dong);
                if (!$vc['ok']) {
                    throw new DomainException($vc['loi']);
                }
                $voucherId = (int)$vc['voucher']['id'];
                $giam = $vc['giam'];
                $phiShip = $vc['phi_ship'];
                if (voucher::scopeReady()) $voucherSnapshot=['voucher_pham_vi'=>$vc['pham_vi'],'voucher_id_doi_tuong'=>$vc['id_doi_tuong'],
                    'voucher_doi_tuong'=>mb_substr($vc['doi_tuong'],0,255),'voucher_tien_ap_dung'=>$vc['tien_ap_dung']];
            }
            $tong = round(max(0, $tamTinh - $giam) + $phiShip, 2);
            if (!$this->soTienHopLe($tong)) {
                throw new DomainException('Tong tien vuot gioi han luu tru');
            }
            $idDon = $this->insert('donhang', [
                'id_khachhang' => $idKh, 'tong_tien' => $tong, 'phi_ship' => $phiShip,
                'ma_voucher' => $maVc !== '' ? $maVc : null, 'dia_chi' => $diaChi,
                'sdt' => $sdt, 'trang_thai' => self::CHO_XAC_NHAN,
                'tam_tinh' => $tamTinh, 'tien_giam' => $giam, 'id_voucher' => $voucherId, 'checkout_key' => $key,
            ] + $voucherSnapshot);
            (new chitietdonhang())->themNhieu($idDon, $dong);
            foreach ($dong as $item) {
                $tru = $spModel->truTon($item['id_sanpham'], $item['so_luong']);
                $spModel->ghiKho($item['id_sanpham'], -$item['so_luong'], 'Đặt đơn', $idDon);
                if (!$tru['ok']) {
                    throw new DomainException($tru['loi']);
                }
            }
            if ($maVc !== '') {
                $truVc = $vcModel->truSoLuong($maVc);
                if (!$truVc['ok']) {
                    throw new DomainException($truVc['loi']);
                }
            }
            if (isset($info['paypal'])) {
                $payment = new thanhtoan_paypal(null, $info['paypal']['config'] ?? null);
                $payment->ganDon($idDon, $tong, $info['paypal']);
            }
            if (isset($info['bank'])) {
                if (isset($info['paypal'])) throw new DomainException('Mỗi đơn chỉ dùng một phương thức thanh toán.');
                (new thanhtoan_qr())->ganDon($idDon,$tong,$info['bank']['config'] ?? null);
            }
            $this->ghiLichSu($idDon, null, self::CHO_XAC_NHAN, 'Khách #' . $idKh, 'Đặt hàng');
            $this->commit();
            return ['ok' => true, 'id' => $idDon, 'tong_tien' => $tong,
                'tam_tinh' => $tamTinh, 'tien_giam' => $giam, 'phi_ship' => $phiShip, 'dong' => $dong];
        } catch (Throwable $e) {
            $this->rollback();
            return $this->loiGiaoDich($e);
        }
    }

    public function huyDon(int $id, int $idKh, string $lyDo = ''): array
    {
        return $this->chuyenTrangThai($id, self::DA_HUY, $lyDo !== '' ? $lyDo : 'Khach huy don', $idKh);
    }

    public function capNhatTrangThai(int $id, string $trangThaiMoi, string $lyDo = '', bool $nhanHang = false, bool $nhapLai = true): array
    {
        return $this->chuyenTrangThai($id, $trangThaiMoi, $lyDo, null, false, $nhanHang, $nhapLai);
    }

    public function hetHanPayPal(int $id): array
    {
        return $this->chuyenTrangThai($id, self::DA_HUY, 'Hết hạn thanh toán PayPal', null, true);
    }

    public function donQRHetHan(): int
    {
        if (!thanhtoan_qr::schemaReady()) return 0;
        $rows = $this->select("SELECT q.id_donhang FROM thanhtoan_qr q JOIN donhang d ON d.id=q.id_donhang WHERE q.status='waiting' AND q.expires_at<NOW() AND d.trang_thai=? LIMIT 50",[self::CHO_XAC_NHAN]);
        $count = 0;
        foreach ($rows as $row) if ($this->chuyenTrangThai((int)$row['id_donhang'],self::DA_HUY,'Hết hạn chuyển khoản QR',null,true)['ok']) $count++;
        return $count;
    }

    public function donPayPalHetHan(?int $owner = null): int
    {
        if (!thanhtoan_paypal::schemaReady()) return 0;
        $rows = $this->select("SELECT d.id FROM donhang d JOIN thanhtoan_paypal p ON p.id_donhang=d.id
            WHERE d.trang_thai=? AND p.expires_at<NOW() AND p.status IN ('created','awaiting','approved','failed')"
            . ($owner === null ? '' : ' AND d.id_khachhang=?') . ' ORDER BY d.id LIMIT 50',
            $owner === null ? [self::CHO_XAC_NHAN] : [self::CHO_XAC_NHAN,$owner]);
        $count = 0;
        foreach ($rows as $row) if ($this->hetHanPayPal((int)$row['id'])['ok']) $count++;
        return $count;
    }

    private function chuyenTrangThai(int $id, string $moi, string $lyDo, ?int $idKh = null, bool $hetHan = false, bool $nhanHang = false, bool $nhapLai = true): array
    {
        try {
            $this->begin();
            // Doc va kiem tra trang thai SAU khi khoa, de chi xu ly moi buoc mot lan.
            $dh = $this->selectOne('SELECT * FROM donhang WHERE id = ? FOR UPDATE', [$id]);
            if ($dh === null || ($idKh !== null && (int) $dh['id_khachhang'] !== $idKh)) {
                throw new DomainException('Khong tim thay don hang');
            }
            if (!in_array($moi, self::LUONG[$dh['trang_thai']] ?? [], true)) {
                throw new DomainException('Khong duoc nhay coc, lui hoac lap lai trang thai');
            }
            if ($idKh !== null && ($moi !== self::DA_HUY || !in_array($dh['trang_thai'], [self::CHO_XAC_NHAN, self::XAC_NHAN], true))) {
                throw new DomainException('Chỉ được hủy trước khi đơn chuyển sang đang giao.');
            }
            if (in_array($moi, [self::DA_HUY, self::BOM_HANG, self::TRA_HANG], true) && mb_strlen(trim($lyDo)) > 1000) throw new DomainException('Lý do tối đa 1000 ký tự');
            if (in_array($moi, [self::BOM_HANG,self::TRA_HANG], true) && !$nhanHang) throw new DomainException('Cần xác nhận đã nhận lại và kiểm tra hàng.');
            if ($moi === self::TRA_HANG && in_array($dh['trang_thai'], [self::DA_GIAO, self::THANH_CONG], true)) {
                $request = $this->selectOne("SELECT id FROM trahang WHERE id_donhang=? AND trang_thai='Chờ xử lý' FOR UPDATE", [$id]);
                if (!$request || !$nhanHang) throw new DomainException('Cần yêu cầu trả hàng và xác nhận đã nhận lại, kiểm tra hàng trước khi hoàn kho.');
            }
            $payment = thanhtoan_paypal::schemaReady() ? (new thanhtoan_paypal())->lay($id, null, true) : null;
            $qr = (new thanhtoan_qr())->lay($id,null,true);
            $canExpire = $payment !== null && strtotime($payment['expires_at']) <= time() && in_array($payment['status'],['created','awaiting','approved','failed'],true);
            $canExpire = $canExpire || ($qr !== null && $qr['status']==='waiting' && strtotime($qr['expires_at'])<=time());
            if ($hetHan && !$canExpire) {
                throw new DomainException('Đơn chưa hết hạn hoặc cần đối soát thanh toán trước khi hủy.');
            }
            if ($qr !== null) {
                if ($moi === self::DA_HUY) {
                    if (!in_array($qr['status'],['waiting','refunded'],true)) throw new DomainException('Cần admin đối soát tiền chuyển khoản hoặc xác nhận hoàn tiền trước khi hủy.');
                    if ($qr['status']==='waiting') $this->update('thanhtoan_qr',['status'=>'cancelled'],'id_donhang=?',[$id]);
                } elseif ($qr['status'] !== 'paid') throw new DomainException('Chưa nhận đủ chuyển khoản; không thể xử lý giao hàng.');
                if (in_array($moi,[self::TRA_HANG,self::BOM_HANG],true)) $this->update('thanhtoan_qr',['status'=>'refund_due'],'id_donhang=?',[$id]);
            }
            if ($payment !== null) {
                if (in_array($moi, [self::DA_HUY, self::TRA_HANG, self::BOM_HANG], true) && !in_array($payment['status'], ['created', 'awaiting', 'approved', 'failed', 'refunded'], true)) {
                    throw new DomainException('Cần đối soát hoặc hoàn tiền PayPal trước khi hủy đơn.');
                }
                if (!in_array($moi, [self::DA_HUY, self::TRA_HANG, self::BOM_HANG], true) && $payment['status'] !== 'paid') {
                    throw new DomainException('Chưa nhận đủ thanh toán PayPal; không thể chuyển đơn sang giao hàng.');
                }
                if ($moi === self::DA_HUY && $payment['status'] !== 'refunded') {
                    $this->update('thanhtoan_paypal', ['status' => 'cancelled', 'lease_until' => null], 'id_donhang = ?', [$id]);
                }
            }
            $this->selectOne('SELECT id FROM khachhang WHERE id = ? FOR UPDATE', [$dh['id_khachhang']]);
            $fields = ['trang_thai' => $moi];
            $viPham = [self::DA_HUY => 'huy', self::BOM_HANG => 'bom', self::TRA_HANG => 'tra'];
            if (isset($viPham[$moi])) {
                $ct = (new chitietdonhang())->layTheoDon($id);
                usort($ct, static fn($a, $b) => (int) $a['id_sanpham'] <=> (int) $b['id_sanpham']);
                foreach ($ct as $item) {
                    if ($moi !== self::DA_HUY && !$nhapLai) {
                        $this->execute('UPDATE sanpham SET so_luong_loi=so_luong_loi+? WHERE id=?', [(int)$item['so_luong'], (int)$item['id_sanpham']]);
                        $hoan = ['ok'=>true];
                        (new sanpham())->ghiKho((int)$item['id_sanpham'], 0, $moi . ' - cách ly hàng lỗi', $id);
                    } else {
                        $hoan = (new sanpham())->congTon((int) $item['id_sanpham'], (int) $item['so_luong']);
                        (new sanpham())->ghiKho((int)$item['id_sanpham'], (int)$item['so_luong'], $moi, $id);
                    }
                    if (!$hoan['ok']) {
                        throw new DomainException($hoan['loi']);
                    }
                }
                if (!empty($dh['id_voucher'])) $this->execute('UPDATE voucher SET so_luong=so_luong+1 WHERE id=?', [$dh['id_voucher']]);
                $fields['ly_do_huy'] = (trim($lyDo) !== '' ? trim($lyDo) : $moi) . ' | ' . date('Y-m-d H:i:s');
                if (!$hetHan && ($idKh !== null || $moi === self::BOM_HANG)) (new khachhang())->tangViPham((int) $dh['id_khachhang'], $viPham[$moi]);
                if ((int)$dh['diem_da_cong'] > 0) {
                    $this->execute('UPDATE khachhang SET diem=GREATEST(0,diem-?) WHERE id=?', [(int)$dh['diem_da_cong'], $dh['id_khachhang']]);
                    $this->execute("UPDATE khachhang SET hang=CASE WHEN diem>=? THEN 'Diamond' WHEN diem>=? THEN 'Gold' ELSE 'Silver' END WHERE id=?", [khachhang::NGUONG_DIAMOND, khachhang::NGUONG_GOLD, $dh['id_khachhang']]);
                    $fields['diem_da_cong'] = 0;
                }
                if ($moi === self::TRA_HANG) $this->execute("UPDATE trahang SET trang_thai='Đã nhận hàng', phan_hoi=?, nhap_lai_kho=?, so_tien_hoan=?, trang_thai_hoan=?, tham_chieu_hoan=?, ngay_xu_ly=NOW() WHERE id_donhang=?", [$lyDo, (int)$nhapLai, $dh['tong_tien'], $payment !== null ? 'Đã hoàn tiền' : 'Chờ hoàn tiền', $payment['capture_id'] ?? null, $id]);
            }
            if ($moi === self::DA_GIAO) $fields['ngay_giao'] = date('Y-m-d H:i:s');
            if ($moi === self::THANH_CONG) $fields['diem_da_cong'] = (int)floor((float)$dh['tong_tien']/1000);
            $this->update('donhang', $fields, 'id = ?', [$id]);
            if ($moi === self::THANH_CONG) {
                $diem = (new khachhang())->congDiemVaXetHang((int) $dh['id_khachhang'], (float) $dh['tong_tien']);
                if (!$diem['ok']) {
                    throw new DomainException($diem['loi']);
                }
            }
            $this->ghiLichSu($id, $dh['trang_thai'], $moi, $hetHan ? 'Hệ thống' : ($idKh !== null ? 'Khách #' . $idKh : 'Nhân viên #' . ($_SESSION['admin']['id'] ?? 0)), $lyDo);
            $this->commit();
            return ['ok' => true];
        } catch (Throwable $e) {
            $this->rollback();
            return $this->loiGiaoDich($e);
        }
    }

    private function loiGiaoDich(Throwable $e): array
    {
        if ($e instanceof DomainException) {
            return ['ok' => false, 'loi' => $e->getMessage()];
        }
        error_log('Giao dich don hang: ' . $e->getMessage());
        return ['ok' => false, 'loi' => 'Khong the xu ly don hang luc nay, vui long thu lai'];
    }

    private function ghiLichSu(int $id, ?string $old, string $new, string $actor, string $note): void
    {
        $this->insert('lichsu_donhang', ['id_donhang' => $id, 'trang_thai_cu' => $old,
            'trang_thai_moi' => $new, 'tac_nhan' => $actor, 'ghi_chu' => $note]);
    }

    public function yeuCauTra(int $id, int $kh, string $reason): array
    {
        try {
            $this->begin();
            $order = $this->selectOne('SELECT * FROM donhang WHERE id=? AND id_khachhang=? FOR UPDATE', [$id, $kh]);
            if (!$order || !in_array($order['trang_thai'], [self::DA_GIAO, self::THANH_CONG], true)) throw new DomainException('Chỉ yêu cầu trả đơn đã nhận.');
            if (empty($order['ngay_giao']) || strtotime($order['ngay_giao'] . ' +7 days') < time()) throw new DomainException('Đơn đã quá 7 ngày kể từ lúc giao hoặc thiếu ngày giao; vui lòng liên hệ cửa hàng.');
            if (trim($reason) === '' || mb_strlen($reason) > 1000) throw new DomainException('Lý do trả hàng từ 1 đến 1000 ký tự.');
            if ($this->selectOne('SELECT id FROM trahang WHERE id_donhang=?', [$id])) throw new DomainException('Đơn đã có yêu cầu trả hàng.');
            $this->insert('trahang', ['id_donhang' => $id, 'ly_do' => trim($reason)]);
            $this->ghiLichSu($id, $order['trang_thai'], $order['trang_thai'], 'Khách #' . $kh, 'Yêu cầu trả: ' . trim($reason));
            $this->commit();
            return ['ok' => true];
        } catch (Throwable $e) { $this->rollback(); return $this->loiGiaoDich($e); }
    }

    public function tuChoiTra(int $id, string $reason): array
    {
        if (trim($reason) === '' || mb_strlen($reason) > 1000) return ['ok' => false, 'loi' => 'Cần lý do từ chối, tối đa 1000 ký tự.'];
        try {
            $this->begin();
            $order = $this->selectOne('SELECT * FROM donhang WHERE id=? FOR UPDATE', [$id]);
            if (!$order || $this->execute("UPDATE trahang SET trang_thai='Từ chối', phan_hoi=?, ngay_xu_ly=NOW() WHERE id_donhang=? AND trang_thai='Chờ xử lý'", [trim($reason), $id]) !== 1) throw new DomainException('Không có yêu cầu trả hàng đang chờ.');
            $this->ghiLichSu($id, $order['trang_thai'], $order['trang_thai'], 'Nhân viên #' . ($_SESSION['admin']['id'] ?? 0), 'Từ chối trả: ' . trim($reason));
            $this->commit(); return ['ok' => true];
        } catch (Throwable $e) { $this->rollback(); return $this->loiGiaoDich($e); }
    }

    public function xacNhanHoanCOD(int $id, string $reference): array
    {
        if (trim($reference) === '' || mb_strlen($reference)>255) return ['ok'=>false,'loi'=>'Nhập chứng từ hoàn tiền, tối đa 255 ký tự.'];
        try {
            $this->begin();
            $order = $this->selectOne('SELECT * FROM donhang WHERE id=? FOR UPDATE', [$id]);
            if (!$order || $order['trang_thai'] !== self::TRA_HANG) throw new DomainException('Đơn chưa hoàn tất trả hàng.');
            if ($this->selectOne('SELECT id_donhang FROM thanhtoan_paypal WHERE id_donhang=?', [$id])) throw new DomainException('Đơn PayPal cần đối soát qua PayPal.');
            if ((new thanhtoan_qr())->lay($id)) throw new DomainException('Đơn chuyển khoản cần ghi chứng từ hoàn chuyển khoản.');
            if ($this->execute("UPDATE trahang SET trang_thai_hoan='Đã hoàn tiền',tham_chieu_hoan=? WHERE id_donhang=? AND trang_thai_hoan='Chờ hoàn tiền'", [trim($reference),$id]) !== 1) throw new DomainException('Không có khoản hoàn COD đang chờ.');
            $this->ghiLichSu($id, $order['trang_thai'], $order['trang_thai'], 'Nhân viên #' . ($_SESSION['admin']['id'] ?? 0), 'Xác nhận hoàn COD: ' . trim($reference));
            $this->commit(); return ['ok'=>true];
        } catch (Throwable $e) { $this->rollback(); return $this->loiGiaoDich($e); }
    }

    public function thongKeDoanhThu(string $ky = 'thang'): array
    {
        $nhan = match ($ky) {
            'tuan' => "DATE_FORMAT(ngay_dat, '%x-W%v')",
            'nam' => "DATE_FORMAT(ngay_dat, '%Y')",
            default => "DATE_FORMAT(ngay_dat, '%Y-%m')",
        };
        $doanhThu = $this->select("SELECT {$nhan} AS nhan, SUM(tong_tien) AS doanh_thu, COUNT(*) AS so_don
            FROM donhang WHERE trang_thai = ? GROUP BY {$nhan} ORDER BY nhan", [self::THANH_CONG]);
        $banChay = $this->select('SELECT sp.id, sp.ma_sp, sp.ten_sp, SUM(ct.so_luong) AS da_ban
            FROM chitietdonhang ct JOIN donhang dh ON dh.id = ct.id_donhang JOIN sanpham sp ON sp.id = ct.id_sanpham
            WHERE dh.trang_thai = ? GROUP BY sp.id, sp.ma_sp, sp.ten_sp ORDER BY da_ban DESC, sp.id LIMIT 10', [self::THANH_CONG]);
        return ['doanh_thu' => $doanhThu, 'ban_chay' => $banChay, 'ban_e' => (new sanpham())->hangE()];
    }

    public function ghiKhieuNai(int $id, int $idKh, string $noiDung): array
    {
        $dh = $this->layTheoId($id);
        if ($dh === null || (int) $dh['id_khachhang'] !== $idKh) {
            return ['ok' => false, 'loi' => 'Khong tim thay don hang'];
        }
        $noiDung = trim($noiDung);
        if ($noiDung === '' || mb_strlen($noiDung) > 5000) {
            return ['ok' => false, 'loi' => 'Nội dung khiếu nại phải từ 1 đến 5000 ký tự'];
        }
        require_once __DIR__ . '/email.php';
        (new email())->luuThu($idKh, 'Khiếu nại đơn #' . $id, $noiDung, 'khieunai');
        return ['ok' => true];
    }
}
