<?php

/** Single source of truth for points, tiers and shipping benefits. */
final class chinhsach_thanhvien
{
    public const VND_MOT_DIEM = 1000;
    public const NGUONG_GOLD = 500;
    public const NGUONG_DIAMOND = 2000;

    public static function tinhDiem(float $tongTien): int
    {
        if (!is_finite($tongTien) || $tongTien < 0) {
            throw new InvalidArgumentException('Số tiền tính điểm không hợp lệ.');
        }
        return (int) floor($tongTien / self::VND_MOT_DIEM);
    }

    public static function hang(int $diem): string
    {
        return $diem >= self::NGUONG_DIAMOND ? 'Diamond' : ($diem >= self::NGUONG_GOLD ? 'Gold' : 'Silver');
    }

    public static function cacHang(): array
    {
        return [
            ['ten'=>'Silver', 'nhan'=>'Bạc', 'tu'=>0, 'den'=>self::NGUONG_GOLD-1, 'quyen_loi'=>'Phí giao hàng theo đơn; voucher theo điều kiện của từng mã.'],
            ['ten'=>'Gold', 'nhan'=>'Vàng', 'tu'=>self::NGUONG_GOLD, 'den'=>self::NGUONG_DIAMOND-1, 'quyen_loi'=>'Miễn phí giao hàng; voucher theo điều kiện của từng mã.'],
            ['ten'=>'Diamond', 'nhan'=>'Kim cương', 'tu'=>self::NGUONG_DIAMOND, 'den'=>null, 'quyen_loi'=>'Miễn phí giao hàng; voucher theo điều kiện của từng mã.'],
        ];
    }

    public static function tienDo(int $diem): array
    {
        $diem = max(0, $diem);
        $hang = self::hang($diem);
        $next = $hang === 'Silver' ? 'Gold' : ($hang === 'Gold' ? 'Diamond' : null);
        $target = $hang === 'Silver' ? self::NGUONG_GOLD : self::NGUONG_DIAMOND;
        $start = $hang === 'Gold' ? self::NGUONG_GOLD : 0;
        return ['diem'=>$diem, 'hang'=>$hang, 'hang_tiep'=>$next,
            'con_thieu'=>$next === null ? 0 : $target-$diem,
            'phan_tram'=>$next === null ? 100 : (int) floor(100*($diem-$start)/($target-$start))];
    }

    public static function quyenLoi(string $hang): array
    {
        $free = in_array($hang, ['Gold', 'Diamond'], true);
        return ['mien_ship'=>$free, 'mo_ta'=>$free ? 'Miễn phí giao hàng; voucher theo điều kiện của từng mã.' : 'Phí giao hàng theo đơn; voucher theo điều kiện của từng mã.'];
    }
}
