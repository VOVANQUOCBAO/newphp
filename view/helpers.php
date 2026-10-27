<?php
/** Tiện ích trình bày: đường dẫn, ảnh và số tiền. Không truy vấn CSDL. */
final class view_helper
{
    public static function shopBase(): string
    {
        return (string)preg_replace('~/(?:controller/(?:admin/)?)?index\.php$~', '', str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    }
    public static function productImage(?string $name): string
    {
        if (!$name || !preg_match('~^(?:(?:uploads|catalog)/)?[a-zA-Z0-9_-]+\.(?:jpg|jpeg|png|webp|gif|svg)$~D', $name)) $name = 'ai-phone-elegance.svg';
        return self::shopBase() . '/view/image/' . $name;
    }
    public static function bannerProductImage(?string $name): string
    {
        if ($name && preg_match('~^catalog/([a-zA-Z0-9_-]+)\.[a-zA-Z0-9]+$~D', $name, $matches)) {
            $cutout = 'banner-' . $matches[1] . '.png';
            if (is_file(__DIR__ . '/image/catalog/' . $cutout)) {
                return self::shopBase() . '/view/image/catalog/' . $cutout;
            }
        }
        return self::productImage($name);
    }
    public static function money(mixed $value): string
    {
        return number_format((float)$value, 0, ',', '.') . 'đ';
    }

    public static function productCard(array $sp, string $userCsrf, string $badgeType = ''): void
    {
        require __DIR__ . '/components/product_card.php';
    }
}
