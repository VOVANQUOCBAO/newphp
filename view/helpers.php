<?php
if (!function_exists('shopBase')) {
    function shopBase(): string
    {
        return (string)preg_replace('~/(?:controller/(?:admin/)?)?index\.php$~', '', str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    }
    function productImage(?string $name): string
    {
        if (!$name || !preg_match('~^(?:(?:uploads|catalog)/)?[a-zA-Z0-9_-]+\.(?:jpg|jpeg|png|webp|gif|svg)$~D', $name)) $name = 'ai-phone-elegance.svg';
        return shopBase() . '/view/image/' . $name;
    }
    function money(mixed $value): string { return number_format((float)$value, 0, ',', '.') . 'đ'; }
}
