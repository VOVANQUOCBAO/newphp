<?php

class paypal_error extends RuntimeException
{
    public function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }
}

class paypal_config
{
    public static function load(): array
    {
        $cfg = require dirname(__DIR__) . '/config/paypal.example.php';
        $file = dirname(__DIR__) . '/config/paypal.local.php';
        if (is_file($file)) {
            $local = require $file;
            if (is_array($local)) $cfg = array_replace($cfg, $local);
        }
        foreach (['enabled', 'mode', 'client_id', 'client_secret', 'merchant_id', 'app_url', 'vnd_per_usd', 'webhook_id'] as $key) {
            $value = getenv('PAYPAL_' . strtoupper($key));
            if ($value !== false) $cfg[$key] = $value;
        }
        $cfg['enabled'] = filter_var($cfg['enabled'], FILTER_VALIDATE_BOOLEAN);
        return $cfg;
    }

    public static function ready(array $cfg): bool
    {
        $url = parse_url((string) ($cfg['app_url'] ?? ''));
        $localSandbox = ($cfg['mode'] ?? '') === 'sandbox' && in_array($url['host'] ?? '', ['localhost', '127.0.0.1'], true);
        return !empty($cfg['enabled']) && in_array($cfg['mode'] ?? '', ['sandbox', 'live'], true)
            && trim((string) ($cfg['client_id'] ?? '')) !== '' && trim((string) ($cfg['client_secret'] ?? '')) !== ''
            && preg_match('/^[A-Z0-9]{5,32}$/', (string) ($cfg['merchant_id'] ?? '')) === 1
            && !empty($url['host']) && !isset($url['user']) && !isset($url['pass'])
            && (($url['scheme'] ?? '') === 'https' || ($localSandbox && ($url['scheme'] ?? '') === 'http'));
    }

    public static function usd(mixed $vnd, mixed $rate): string
    {
        if (!is_numeric($vnd) || !is_numeric($rate) || !is_finite((float) $vnd) || !is_finite((float) $rate)
            || (float) $vnd <= 0 || (float) $rate < 1 || (float) $rate > 1000000000) {
            throw new paypal_error('amount', 'Không thể tính số tiền PayPal.');
        }
        $usd = round((float) $vnd / (float) $rate, 2, PHP_ROUND_HALF_UP);
        if ($usd < 0.01 || $usd > 9999999999.99) throw new paypal_error('amount', 'Số tiền nằm ngoài giới hạn thanh toán PayPal.');
        return number_format($usd, 2, '.', '');
    }

    public static function rate(array $cfg): array
    {
        if (is_numeric($cfg['vnd_per_usd'] ?? null) && (float) $cfg['vnd_per_usd'] > 0) {
            self::usd(100000, $cfg['vnd_per_usd']);
            return ['value' => (float) $cfg['vnd_per_usd'], 'source' => 'manual', 'at' => time()];
        }
        if (!function_exists('curl_init')) throw new paypal_error('configuration', 'Máy chủ chưa bật kết nối HTTPS cho PayPal.');
        $cache = dirname(__DIR__) . '/config/paypal-rate-cache.php';
        $data = is_file($cache) ? require $cache : null;
        if (!is_array($data) || time() - (int) ($data['cached_at'] ?? 0) > 3600) {
            $ch = curl_init('https://open.er-api.com/v6/latest/USD');
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_TIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS]);
            $raw = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            $fresh = is_string($raw) ? json_decode($raw, true) : null;
            if ($code === 200 && is_array($fresh) && ($fresh['result'] ?? '') === 'success' && ($fresh['base_code'] ?? '') === 'USD'
                && is_numeric($fresh['rates']['VND'] ?? null) && (float) $fresh['rates']['VND'] >= 1) {
                $data = ['value' => round((float) $fresh['rates']['VND'], 6), 'source' => 'ExchangeRate-API',
                    'at' => (int) ($fresh['time_last_update_unix'] ?? 0), 'cached_at' => time()];
                // PHP cache contains public rates only; direct HTTP requests do not print its data.
                $temporary = @tempnam(dirname($cache), 'pp-rate-');
                if ($temporary !== false) {
                    if (@file_put_contents($temporary, '<?php return ' . var_export($data, true) . ';', LOCK_EX) !== false) @rename($temporary, $cache);
                    if (is_file($temporary)) @unlink($temporary);
                }
            }
        }
        if (!is_array($data) || !is_numeric($data['value'] ?? null) || (float) $data['value'] < 1
            || (int) ($data['at'] ?? 0) > time() + 300 || time() - (int) ($data['at'] ?? 0) > 172800) {
            throw new paypal_error('rate', 'Chưa lấy được tỷ giá mới. Vui lòng thử lại sau.');
        }
        return $data;
    }
}

class paypal_client
{
    private ?string $token = null;
    protected array $cfg;

    public function __construct(?array $cfg = null)
    {
        $this->cfg = $cfg ?? paypal_config::load();
        if (!paypal_config::ready($this->cfg) || !function_exists('curl_init')) throw new paypal_error('configuration', 'PayPal chưa sẵn sàng nhận thanh toán.');
    }

    public function request(string $method, string $path, ?array $body = null, ?string $requestId = null): array
    {
        if ($this->token === null) {
            $auth = $this->http('POST', '/v1/oauth2/token', 'grant_type=client_credentials', [
                'Content-Type: application/x-www-form-urlencoded',
            ], true);
            if (!is_string($auth['access_token'] ?? null)) throw new paypal_error('authentication', 'Không thể kết nối tài khoản nhận PayPal.');
            $this->token = $auth['access_token'];
        }
        $headers = ['Content-Type: application/json', 'Authorization: Bearer ' . $this->token, 'Prefer: return=representation'];
        if ($requestId !== null) $headers[] = 'PayPal-Request-Id: ' . $requestId;
        return $this->http($method, $path, $body === null ? null : json_encode($body === [] ? (object) [] : $body, JSON_THROW_ON_ERROR), $headers);
    }

    private function http(string $method, string $path, ?string $body, array $headers, bool $auth = false): array
    {
        if (!str_starts_with($path, '/v')) throw new LogicException('Invalid PayPal API path');
        $base = $this->cfg['mode'] === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
        $ch = curl_init($base . $path);
        curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 20, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS]);
        if ($auth) curl_setopt($ch, CURLOPT_USERPWD, $this->cfg['client_id'] . ':' . $this->cfg['client_secret']);
        if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        curl_close($ch);
        if ($raw === false || $code === 0) throw new paypal_error('connection', 'Chưa xác định được kết quả PayPal. Hãy kiểm tra trạng thái, không thanh toán lại.');
        $result = json_decode($raw, true);
        if ($code < 200 || $code >= 300 || !is_array($result)) {
            error_log('PayPal HTTP ' . $code . '; curl=' . $errno . '; debug_id=' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($result['debug_id'] ?? '')));
            throw new paypal_error($code >= 500 ? 'connection' : 'api', 'PayPal chưa hoàn tất yêu cầu. Vui lòng kiểm tra trạng thái hoặc liên hệ cửa hàng.');
        }
        return $result;
    }
}
