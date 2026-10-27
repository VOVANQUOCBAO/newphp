<?php

/** Resolves store-owned product assets for the website and email attachments. */
final class anh_sanpham
{
    public static function ten(?string $name): ?string
    {
        if (!$name || !preg_match('~^(?:(?:uploads|catalog)/)?[a-zA-Z0-9_-]+\.(?:jpg|jpeg|png|webp|gif|svg)$~D', $name)) return null;
        return is_file(dirname(__DIR__).'/view/image/'.$name) ? $name : null;
    }

    public static function urlEmail(?string $name): string
    {
        $name = self::ten($name) ?? 'bpstore-logo.png';
        return 'https://bpstore.gt.tc/view/image/'.$name;
    }

    public static function theEmail(?string $name, string $product): string
    {
        $name = self::ten($name);
        // SVG is not consistently supported by mail clients.
        if ($name !== null && str_ends_with(strtolower($name), '.svg')) $name = null;
        $escape = static fn($v)=>htmlspecialchars($v, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
        $alt = $name === null ? 'BP Store · Chưa có ảnh sản phẩm' : $product;
        return '<img src="'.$escape(self::urlEmail($name)).'" data-product-image="'.$escape($name ?? 'bpstore-logo.png').'" alt="'.$escape($alt).'" width="80" height="80" style="display:block;width:80px;height:80px;object-fit:contain;border:1px solid #e8ebf0;border-radius:8px;margin-bottom:8px">';
    }

    /** Embed bytes locally so InfinityFree's browser challenge cannot block email images. */
    public static function nhungVaoThu(object $mail, string $html): string
    {
        $embedded = [];
        return preg_replace_callback('~<img\b[^>]*\bdata-product-image="([^"]+)"[^>]*>~i', static function ($match) use ($mail, &$embedded) {
            $name = self::ten(html_entity_decode($match[1], ENT_QUOTES, 'UTF-8'));
            if ($name === null) return $match[0];
            $path = dirname(__DIR__).'/view/image/'.$name;
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
            if (!in_array($mime, ['image/jpeg','image/png','image/webp','image/gif'], true)) return $match[0];
            $cid = 'product-'.substr(hash('sha256', $name),0,24).'@bpstore';
            if (!isset($embedded[$cid])) {
                $bytes = file_get_contents($path);
                if ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
                    $picture = @imagecreatefromwebp($path);
                    if ($picture !== false) {
                        ob_start(); imagepng($picture); $bytes = ob_get_clean(); imagedestroy($picture);
                        $mime = 'image/png';
                    }
                }
                if ($bytes === false) return $match[0];
                $mail->addStringEmbeddedImage($bytes, $cid, pathinfo($name, PATHINFO_FILENAME).($mime==='image/png'?'.png':'.'.pathinfo($name, PATHINFO_EXTENSION)), 'base64', $mime);
                $embedded[$cid] = true;
            }
            $tag = preg_replace('~\sdata-product-image="[^"]*"~i', '', $match[0]);
            return preg_replace('~\bsrc="[^"]*"~i', 'src="cid:'.$cid.'"', $tag, 1);
        }, $html);
    }
}
