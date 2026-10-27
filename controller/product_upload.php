<?php
// Raster uploads only; store an application-generated filename, never a client path.

class product_upload
{
    public static function imageExtension(string $path): string
    {
        $info = is_file($path) ? @getimagesize($path) : false;
        $mime = $info ? (new finfo(FILEINFO_MIME_TYPE))->file($path) : '';
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!$info || !isset($extensions[$mime]) || $info[0] > 10000 || $info[1] > 10000) {
            throw new InvalidArgumentException('Chỉ nhận ảnh JPG, PNG hoặc WebP hợp lệ, tối đa 10.000 px mỗi chiều.');
        }
        return $extensions[$mime];
    }

    public static function stagePath(string $token): string
    {
        if (!preg_match('/^[a-f0-9]{32}$/D', $token)) throw new InvalidArgumentException('Mã ảnh không hợp lệ.');
        return BASE_PATH . '/support/config/product-upload-tmp/' . $token . '.part';
    }

    public static function stageCleanup(): void
    {
        $pending = $_SESSION['product_uploads'] ?? [];
        foreach (is_array($pending) ? $pending : [] as $token => $image) {
            if (!is_array($image) || time() - (int)($image['created_at'] ?? 0) > 900) {
                if (preg_match('/^[a-f0-9]{32}$/D', (string)$token)) @unlink(self::stagePath($token));
                unset($_SESSION['product_uploads'][$token]);
            }
        }
        // Clean abandoned sessions too. Only application-generated .part files are eligible.
        $dir = BASE_PATH . '/support/config/product-upload-tmp';
        foreach (array_slice(glob($dir . '/*.part') ?: [], 0, 200) as $path) {
            if (preg_match('/^[a-f0-9]{32}\.part$/D', basename($path)) && filemtime($path) < time() - 3600) @unlink($path);
        }
    }

    // Called only after the admin controller has checked role, POST and CSRF.
    public static function stageImage(mixed $encoded): array
    {
        self::stageCleanup();
        $max = 5 * 1024 * 1024;
        if (!is_string($encoded) || $encoded === '' || strlen($encoded) > 4 * (int)ceil($max / 3)
            || strspn($encoded, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_') !== strlen($encoded)) {
            throw new InvalidArgumentException('Ảnh không hợp lệ hoặc vượt quá 5 MB.');
        }
        if (count($_SESSION['product_uploads'] ?? []) >= 14) throw new InvalidArgumentException('Có quá nhiều ảnh chờ lưu. Vui lòng thử lại sau 15 phút.');
        $bytes = base64_decode(strtr($encoded, '-_', '+/'), true);
        if ($bytes === false || strlen($bytes) === 0 || strlen($bytes) > $max) throw new InvalidArgumentException('Mỗi ảnh tối đa 5 MB.');
        $token = bin2hex(random_bytes(16));
        $path = self::stagePath($token);
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0700, true)) throw new RuntimeException('Không tạo được thư mục ảnh tạm.');
        if (!is_file($dir . '/.htaccess') && file_put_contents($dir . '/.htaccess', "Require all denied\n") === false) throw new RuntimeException('Không bảo vệ được thư mục ảnh tạm.');
        try {
            if (file_put_contents($path, $bytes, LOCK_EX) !== strlen($bytes)) throw new RuntimeException('Không lưu được ảnh tạm.');
            self::imageExtension($path);
            $_SESSION['product_uploads'][$token] = ['size' => strlen($bytes), 'created_at' => time(), 'admin_id' => (int)($_SESSION['admin']['id'] ?? 0)];
        } catch (Throwable $e) {
            @unlink($path);
            throw $e;
        }
        return ['ok' => true, 'upload_id' => $token];
    }

    public static function resolveStaged(mixed $json): array
    {
        if ($json === null || $json === '') return ['main' => null, 'gallery' => []];
        if (!is_string($json) || strlen($json) > 1024) throw new InvalidArgumentException('Danh sách ảnh không hợp lệ.');
        try { $images = json_decode($json, true, 8, JSON_THROW_ON_ERROR); }
        catch (JsonException $e) { throw new InvalidArgumentException('Danh sách ảnh không hợp lệ.'); }
        if (!is_array($images) || !is_array($images['gallery'] ?? []) || !array_is_list($images['gallery'] ?? [])
            || count($images['gallery'] ?? []) > 6) throw new InvalidArgumentException('Tối đa 6 ảnh bổ sung.');
        $main = $images['main'] ?? null;
        $gallery = $images['gallery'] ?? [];
        $tokens = array_merge($main !== null ? [$main] : [], $gallery);
        $resolved = [];
        foreach ($tokens as $token) {
            if (!is_string($token) || isset($resolved[$token])) throw new InvalidArgumentException('Danh sách ảnh không hợp lệ.');
            $path = self::stagePath($token);
            $image = $_SESSION['product_uploads'][$token] ?? null;
            if (!is_array($image) || (int)($image['admin_id'] ?? 0) !== (int)($_SESSION['admin']['id'] ?? 0)
                || time() - (int)$image['created_at'] > 900 || !is_file($path)
                || filesize($path) !== (int)$image['size']) throw new InvalidArgumentException('Ảnh chờ lưu đã hết hạn. Vui lòng chọn lại ảnh.');
            $resolved[$token] = ['error' => UPLOAD_ERR_OK, 'size' => $image['size'], 'tmp_name' => $path, 'token' => $token];
        }
        return ['main' => $main !== null ? $resolved[$main] : null, 'gallery' => array_map(static fn($id) => $resolved[$id], $gallery)];
    }

    public static function upload(array $data): array
    {
        $created = [];
        try {
            if (!empty($data['anh']) || !empty($data['album'])) throw new InvalidArgumentException('Vui lòng bật JavaScript để tải ảnh từ thiết bị.');
            $staged = self::resolveStaged($data['_image_uploads'] ?? null);
            $save = static function (array $file, bool $fromStaging = false) use (&$created): ?string {
                if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
                if (($file['error'] ?? -1) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 5 * 1024 * 1024) {
                    throw new InvalidArgumentException('Mỗi ảnh tối đa 5 MB. Vui lòng chọn lại ảnh.');
                }
                $tmp = $file['tmp_name'] ?? '';
                if (!$fromStaging && !is_uploaded_file($tmp)) throw new InvalidArgumentException('Ảnh tải lên không hợp lệ.');
                $extension = self::imageExtension($tmp);
                $dir = BASE_PATH . '/view/image/uploads';
                if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new RuntimeException('Không tạo được thư mục ảnh');
                $name = 'uploads/' . bin2hex(random_bytes(16)) . '.' . $extension;
                $saved = $fromStaging ? rename($tmp, BASE_PATH . '/view/image/' . $name) : move_uploaded_file($tmp, BASE_PATH . '/view/image/' . $name);
                if (!$saved) throw new RuntimeException('Không lưu được ảnh');
                if ($fromStaging) unset($_SESSION['product_uploads'][$file['token']]);
                $created[] = $name;
                return $name;
            };
            $main = $staged['main'] !== null ? $save($staged['main'], true) : $save($_FILES['anh'] ?? []);
            if ($main !== null) $data['hinh'] = $main;
            $gallery = $_FILES['album'] ?? [];
            $names = [];
            foreach ($staged['gallery'] as $file) {
                $name = $save($file, true);
                if ($name !== null) $names[] = $name;
            }
            if (isset($gallery['name']) && is_array($gallery['name'])) {
                if (count(array_filter($gallery['name'])) + count($names) > 6) throw new InvalidArgumentException('Tối đa 6 ảnh bổ sung');
                foreach ($gallery['name'] as $i => $_) {
                    $name = $save(array_map(static fn($values) => $values[$i], $gallery));
                    if ($name !== null) $names[] = $name;
                }
            }
            if ($names !== []) $data['anh_phu'] = implode("\n", $names);
            return ['ok' => true, 'data' => $data, 'created' => $created];
        } catch (Throwable $e) {
            self::cleanup($created);
            return ['ok' => false, 'loi' => $e instanceof InvalidArgumentException ? $e->getMessage() : 'Không thể lưu ảnh sản phẩm.'];
        }
    }
    public static function cleanup(array $names): void
    {
        foreach ($names as $name) {
            if (preg_match('~^uploads/[a-f0-9]{32}\.(jpg|png|webp)$~', $name)) @unlink(BASE_PATH . '/view/image/' . $name);
        }
    }
}
