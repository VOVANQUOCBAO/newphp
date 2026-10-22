<?php

require_once __DIR__ . '/xl_database.php';
require_once __DIR__ . '/khachhang.php';

class voucher extends xl_database
{
    public function layDS(): array
    {
        if (!self::scopeReady()) return $this->select('SELECT * FROM voucher ORDER BY id DESC');
        return $this->select('SELECT v.*,dm.ten_danhmuc,sp.ten_sp,sp.ma_sp FROM voucher v LEFT JOIN danhmuc dm ON dm.id=v.id_danhmuc LEFT JOIN sanpham sp ON sp.id=v.id_sanpham ORDER BY v.id DESC');
    }

    public static function scopeReady(): bool
    {
        return (int)csdl::shared()->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND ((table_name='voucher' AND column_name IN ('pham_vi','id_danhmuc','id_sanpham')) OR (table_name='donhang' AND column_name IN ('voucher_pham_vi','voucher_id_doi_tuong','voucher_doi_tuong','voucher_tien_ap_dung')))")->fetchColumn()===7;
    }

    private function duLieuPhamVi(array $data): array
    {
        $scope=$data['pham_vi'] ?? 'tatca';
        if (!in_array($scope,['tatca','danhmuc','sanpham'],true)) return ['ok'=>false,'loi'=>'Phạm vi voucher không hợp lệ.'];
        $fields=['pham_vi'=>$scope,'id_danhmuc'=>null,'id_sanpham'=>null];
        if ($scope!=='tatca') {
            if (!self::scopeReady()) return ['ok'=>false,'loi'=>'Cần khởi tạo phạm vi voucher trước khi chọn sản phẩm hoặc danh mục.'];
            $key=$scope==='danhmuc' ? 'id_danhmuc' : 'id_sanpham';
            $id=$data[$key] ?? null;
            if (!$this->soNguyenHopLe($id,1) || !$this->selectOne("SELECT id FROM `$scope` WHERE id=? AND ngung_ban=0",[(int)$id]))
                return ['ok'=>false,'loi'=>'Chọn đúng '.($scope==='danhmuc' ? 'danh mục' : 'sản phẩm').' đang bán cho voucher.'];
            $fields[$key]=(int)$id;
        }
        return ['ok'=>true,'fields'=>$fields];
    }

    public static function scopeLabel(array $vc): string
    {
        return match ($vc['pham_vi'] ?? 'tatca') {
            'danhmuc'=>'Danh mục: '.($vc['ten_danhmuc'] ?? '#'.($vc['id_danhmuc'] ?? '?')),
            'sanpham'=>'Sản phẩm: '.($vc['ten_sp'] ?? '#'.($vc['id_sanpham'] ?? '?')).(!empty($vc['ma_sp']) ? ' · '.$vc['ma_sp'] : ''),
            'tatca'=>'Toàn cửa hàng', default=>'Phạm vi không hợp lệ',
        };
    }

    public function suaPhamVi(int $id, array $data): array
    {
        if (!self::scopeReady()) return ['ok'=>false,'loi'=>'Chưa khởi tạo phạm vi voucher.'];
        $scope=$this->duLieuPhamVi($data);
        if (!$scope['ok']) return $scope;
        try {
            $this->begin();
            $old=$this->selectOne('SELECT * FROM voucher WHERE id=? FOR UPDATE',[$id]);
            if (!$old) throw new DomainException('Không tìm thấy voucher.');
            $fields=$scope['fields'];
            if (array_key_exists('gia_tri',$data)) {
                $error=self::loiGiaTri($old['loai'],$data['gia_tri']);
                if ($error!==null) throw new DomainException($error);
                $fields['gia_tri']=round((float)$data['gia_tri'],2);
            }
            $this->update('voucher',$fields,'id=?',[$id]);
            $this->commit();
            return ['ok'=>true];
        } catch (Throwable $e) {
            $this->rollback();
            return ['ok'=>false,'loi'=>$e instanceof DomainException ? $e->getMessage() : 'Không lưu được voucher.'];
        }
    }

    public static function loiGiaTri(string $loai, mixed $value): ?string
    {
        if (!in_array($loai,['tien','phantram','mienship'],true)
            || !(is_int($value) || is_float($value) || is_string($value))
            || !is_numeric($value) || !is_finite((float)$value)
            || (float)$value<0 || (float)$value>9999999999.99)
            return 'Giá trị voucher không hợp lệ.';
        if ($loai!=='mienship' && round((float)$value,2)<=0)
            return 'Giá trị giảm tiền hoặc phần trăm phải lớn hơn 0. Tên mã không tự xác định mức giảm.';
        if ($loai==='phantram' && (float)$value>100)
            return 'Phần trăm giảm phải lớn hơn 0 và không vượt quá 100.';
        return null;
    }

    public function layTheoCode(string $code): ?array
    {
        return $this->selectOne('SELECT * FROM voucher WHERE ma_code = ?', [trim($code)]);
    }

    public function them(array $data): array
    {
        $code = strtoupper(trim((string) ($data['ma_code'] ?? '')));
        if ($code === '') {
            return ['ok' => false, 'loi' => 'Thieu ma voucher'];
        }
        if ($this->layTheoCode($code) !== null) {
            return ['ok' => false, 'loi' => 'Ma voucher da ton tai'];
        }
        $loai = $data['loai'] ?? 'tien';
        if (!in_array($loai, ['tien', 'phantram', 'mienship'], true)) {
            return ['ok' => false, 'loi' => 'Loai voucher khong hop le'];
        }
        $error=self::loiGiaTri($loai,$data['gia_tri'] ?? null);
        if ($error!==null) return ['ok'=>false,'loi'=>$error];
        if (!$this->soTienHopLe($data['gia_tri'] ?? 0)
            || !$this->soTienHopLe($data['don_toi_thieu'] ?? 0)
            || ($loai === 'phantram' && (float) ($data['gia_tri'] ?? 0) > 100)
            || !$this->soNguyenHopLe($data['so_luong'] ?? 0)) {
            return ['ok' => false, 'loi' => 'Muc giam hoac so luong voucher khong hop le'];
        }
        if (!$this->ngayHopLe($data['ngay_bd'] ?? null) || !$this->ngayHopLe($data['ngay_kt'] ?? null)
            || $data['ngay_bd'] > $data['ngay_kt']) {
            return ['ok' => false, 'loi' => 'Thoi han voucher khong hop le'];
        }
        $hang = ($data['hang_ap_dung'] ?? '') === '' ? null : $data['hang_ap_dung'];
        if ($hang !== null && !in_array($hang, ['Silver', 'Gold', 'Diamond'], true)) {
            return ['ok' => false, 'loi' => 'Hang ap dung voucher khong hop le'];
        }
        if (strlen($code) > 50) {
            return ['ok' => false, 'loi' => 'Ma voucher toi da 50 ky tu'];
        }
        $scope=$this->duLieuPhamVi($data);
        if (!$scope['ok']) return $scope;
        $id = $this->insert('voucher', [
            'ma_code' => $code,
            'loai' => $loai,
            'gia_tri' => round((float) ($data['gia_tri'] ?? 0),2),
            'so_luong' => (int) ($data['so_luong'] ?? 0),
            'ngay_bd' => $data['ngay_bd'],
            'ngay_kt' => $data['ngay_kt'],
            'hang_ap_dung' => $hang,
            'don_toi_thieu' => (float) ($data['don_toi_thieu'] ?? 0),
        ] + (self::scopeReady() ? $scope['fields'] : []));
        return ['ok' => true, 'id' => $id];
    }

    public function xoa(int $id): array
    {
        if ($this->selectOne('SELECT id FROM donhang WHERE id_voucher=? LIMIT 1', [$id])) {
            return ['ok' => false, 'loi' => 'Voucher đã gắn với đơn hàng; hãy giữ để đối soát.'];
        }
        return $this->delete('voucher', 'id = ?', [$id]) > 0
            ? ['ok' => true] : ['ok' => false, 'loi' => 'Không tìm thấy voucher'];
    }

    public function kiemTra(string $code, string $hangKh, float $tamTinh, float $phiShip, bool $khoa = false, ?array $items = null): array
    {
        $vc = $this->selectOne('SELECT * FROM voucher WHERE ma_code = ?' . ($khoa ? ' FOR UPDATE' : ''), [strtoupper(trim($code))]);
        if ($vc === null) {
            return ['ok' => false, 'loi' => 'Voucher khong ton tai'];
        }
        if (self::loiGiaTri($vc['loai'],$vc['gia_tri'])!==null)
            return ['ok'=>false,'loi'=>'Voucher chưa có mức giảm hợp lệ. Vui lòng chọn mã khác.'];
        $homNay = date('Y-m-d');
        if ($homNay < $vc['ngay_bd'] || $homNay > $vc['ngay_kt']) {
            return ['ok' => false, 'loi' => 'Voucher het han hoac chua den ngay'];
        }
        if ((int) $vc['so_luong'] <= 0) {
            return ['ok' => false, 'loi' => 'Voucher da het luot su dung'];
        }
        $scope=$vc['pham_vi'] ?? 'tatca';
        $eligible=$tamTinh;
        $targetId=null;
        $targetName='Toàn cửa hàng';
        if ($scope!=='tatca') {
            if (!in_array($scope,['danhmuc','sanpham'],true)) return ['ok'=>false,'loi'=>'Phạm vi voucher không hợp lệ.'];
            $targetId=(int)($vc[$scope==='danhmuc' ? 'id_danhmuc' : 'id_sanpham'] ?? 0);
            $target=$this->selectOne("SELECT * FROM `$scope` WHERE id=? AND ngung_ban=0",[$targetId]);
            if (!$target) return ['ok'=>false,'loi'=>'Đối tượng áp dụng voucher đã ngừng bán hoặc không còn tồn tại.'];
            $targetName=$scope==='danhmuc' ? 'Danh mục: '.$target['ten_danhmuc'] : 'Sản phẩm: '.$target['ten_sp'].' · '.$target['ma_sp'];
            $eligible=0.0;$matched=false;
            foreach ($items ?? [] as $item) {
                $itemId=(int)($scope==='danhmuc' ? ($item['id_danhmuc'] ?? 0) : ($item['id_sanpham'] ?? $item['id'] ?? 0));
                if ($itemId!==$targetId) continue;
                $price=$item['don_gia'] ?? $item['gia'] ?? null;
                $qty=$item['so_luong'] ?? null;
                if (!is_numeric($price) || (float)$price<0 || !$this->soNguyenHopLe($qty,1)) continue;
                $matched=true;$eligible=round($eligible+(float)$price*(int)$qty,2);
            }
            if (!$matched) return ['ok'=>false,'loi'=>'Voucher chỉ áp dụng cho '.$targetName.'. Giỏ hàng chưa có sản phẩm phù hợp.'];
            $eligible=min($eligible,$tamTinh);
        }
        if ($eligible < (float)$vc['don_toi_thieu']) {
            return ['ok' => false, 'loi' => $scope==='tatca' ? 'Đơn chưa đạt giá trị tối thiểu của voucher' : 'Tiền hàng thuộc phạm vi voucher chưa đạt mức tối thiểu.'];
        }
        $ranks = ['Silver' => 1, 'Gold' => 2, 'Diamond' => 3];
        if (!empty($vc['hang_ap_dung']) && ($ranks[$hangKh] ?? 0) < ($ranks[$vc['hang_ap_dung']] ?? 99)) {
            return ['ok' => false, 'loi' => 'Hang khach hang chua du dieu kien voucher'];
        }
        $giam = 0.0;
        $phiConLai = $phiShip;
        if ($vc['loai'] === 'tien') {
            $giam = min($eligible, (float) $vc['gia_tri']);
        } elseif ($vc['loai'] === 'phantram') {
            $giam = round($eligible * ((float) $vc['gia_tri'] / 100), 2);
        } elseif ($vc['loai'] === 'mienship') {
            if ($phiShip <= 0) return ['ok'=>false,'loi'=>'Đơn đã được miễn phí giao hàng; hãy chọn mã giảm tiền khác.'];
            $phiConLai = 0.0;
        }
        if ($vc['loai']!=='mienship' && $giam<=0)
            return ['ok'=>false,'loi'=>'Voucher không làm giảm tiền hàng của đơn này. Vui lòng chọn mã khác.'];
        if ($hangKh === khachhang::HANG_GOLD || $hangKh === khachhang::HANG_DIAMOND) {
            $phiConLai = 0.0;
        }
        return [
            'ok' => true,
            'voucher' => $vc,
            'pham_vi'=>$scope,'id_doi_tuong'=>$targetId,'doi_tuong'=>$targetName,'tien_ap_dung'=>$eligible,
            'giam' => $giam,
            'phi_ship' => $phiConLai,
            'tong' => max(0, $tamTinh - $giam) + $phiConLai,
        ];
    }

    public function truSoLuong(string $code): array
    {
        $n = $this->execute(
            'UPDATE voucher SET so_luong = so_luong - 1 WHERE ma_code = ? AND so_luong > 0',
            [trim($code)]
        );
        if ($n === 0) {
            return ['ok' => false, 'loi' => 'Khong tru duoc so luong voucher'];
        }
        return ['ok' => true];
    }
}
