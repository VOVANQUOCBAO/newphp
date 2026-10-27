<?php

/** Đọc và lưu giao diện ở một nơi để trang quản trị và cửa hàng dùng cùng cấu hình. */
final class giaodien
{
    private const SEASONS = ['default', 'theme-spring', 'theme-summer', 'theme-autumn', 'theme-winter'];
    private const FONTS = ['font-segoe', 'font-bevietnam', 'font-roboto', 'font-montserrat'];
    private const BACKGROUNDS = ['bg-light-gray', 'bg-white-pure', 'bg-soft-blue'];

    public function doc(): array
    {
        $file = $this->duongDan();
        $saved = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
        return $this->chuanHoa(is_array($saved) ? $saved : []);
    }

    public function luu(array $data): array
    {
        $config = $this->chuanHoa($data);
        $config['updated_at'] = date('Y-m-d H:i:s');
        $written = file_put_contents($this->duongDan(), json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        return $written === false ? ['ok' => false, 'loi' => 'Không thể lưu giao diện.'] : ['ok' => true, 'theme' => $config];
    }

    public function xemTruoc(array $config, string $season): array
    {
        if ($season !== 'default' && !str_starts_with($season, 'theme-')) $season = 'theme-' . $season;
        if (in_array($season, self::SEASONS, true)) $config['season'] = $season;
        return $config;
    }

    private function chuanHoa(array $data): array
    {
        return [
            'season' => in_array($data['season'] ?? '', self::SEASONS, true) ? $data['season'] : 'default',
            'font' => in_array($data['font'] ?? '', self::FONTS, true) ? $data['font'] : 'font-segoe',
            'bg' => in_array($data['bg'] ?? '', self::BACKGROUNDS, true) ? $data['bg'] : 'bg-light-gray',
        ];
    }

    private function duongDan(): string
    {
        return dirname(__DIR__) . '/support/config/theme.json';
    }
}
