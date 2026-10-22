<?php

require_once __DIR__ . '/xl_database.php';
require_once dirname(__DIR__) . '/support/lib/paypal.php';

class thanhtoan_paypal extends xl_database
{
    private array $cfg;
    private ?paypal_client $client;

    public function __construct(?paypal_client $client = null, ?array $cfg = null)
    {
        parent::__construct();
        $this->cfg = $cfg ?? paypal_config::load();
        $this->client = $client;
    }

    public static function schemaReady(): bool
    {
        $stmt = csdl::shared()->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'thanhtoan_paypal'");
        return (int) $stmt->fetchColumn() === 1;
    }

    public function lay(int $id, ?int $owner = null, bool $lock = false): ?array
    {
        return $this->selectOne('SELECT p.*, d.id_khachhang, d.trang_thai AS order_status FROM thanhtoan_paypal p
            JOIN donhang d ON d.id = p.id_donhang WHERE p.id_donhang = ?'
            . ($owner !== null ? ' AND d.id_khachhang = ?' : '') . ($lock ? ' FOR UPDATE' : ''),
            $owner !== null ? [$id, $owner] : [$id]);
    }

    public function theoKey(string $key, int $owner): ?array
    {
        $row = $this->selectOne('SELECT id_donhang FROM thanhtoan_paypal WHERE checkout_key = ?', [$key]);
        return $row ? $this->lay((int) $row['id_donhang'], $owner) : null;
    }

    // Called inside the order's existing transaction, after server-side pricing.
    public function ganDon(int $orderId, float $vnd, array $options): void
    {
        if (!paypal_config::ready($this->cfg) || !preg_match('/^[a-f0-9]{64}$/', (string) ($options['checkout_key'] ?? ''))) {
            throw new DomainException('PayPal chưa sẵn sàng hoặc phiên đặt hàng không hợp lệ.');
        }
        $rate = $options['rate'] ?? [];
        $usd = paypal_config::usd($vnd, $rate['value'] ?? 0);
        $this->insert('thanhtoan_paypal', [
            'id_donhang' => $orderId, 'checkout_key' => $options['checkout_key'],
            'mode' => $this->cfg['mode'], 'merchant_id' => $this->cfg['merchant_id'], 'client_id' => $this->cfg['client_id'],
            'amount_vnd' => $vnd, 'amount_usd' => $usd, 'vnd_per_usd' => $rate['value'],
            'rate_source' => $rate['source'] ?? 'manual', 'rate_at' => date('Y-m-d H:i:s', (int) ($rate['at'] ?? time())),
            'create_request_id' => bin2hex(random_bytes(16)), 'capture_request_id' => bin2hex(random_bytes(16)),
            'status' => 'created', 'expires_at' => date('Y-m-d H:i:s', time() + 1800),
        ]);
    }

    private function api(): paypal_client
    {
        return $this->client ??= new paypal_client($this->cfg);
    }

    private function compatible(array $p): void
    {
        if (!paypal_config::ready($this->cfg) || $p['mode'] !== $this->cfg['mode']
            || !hash_equals($p['merchant_id'], (string) $this->cfg['merchant_id'])
            || !hash_equals($p['client_id'], (string) $this->cfg['client_id'])) {
            throw new paypal_error('configuration', 'Cấu hình tài khoản nhận tiền đã thay đổi. Vui lòng liên hệ cửa hàng để đối soát đơn.');
        }
    }

    private function claim(int $id, int $owner, string $kind): array
    {
        $this->begin();
        try {
            // All order/payment operations lock in this order to avoid deadlocks.
            $this->selectOne('SELECT id FROM donhang WHERE id = ? FOR UPDATE', [$id]);
            $p = $this->lay($id, $owner, true);
            if (!$p) throw new paypal_error('owner', 'Không tìm thấy giao dịch của bạn.');
            $this->compatible($p);
            if (in_array($p['status'], ['paid', 'refunded', 'review'], true)) {
                throw new paypal_error('status', 'Giao dịch đã kết thúc hoặc đang cần cửa hàng đối soát.');
            }
            if ($p['order_status'] !== 'Chờ xác nhận' || $p['status'] === 'cancelled') {
                throw new paypal_error('cancelled', 'Đơn hàng không còn nhận thanh toán.');
            }
            if ($p['lease_until'] !== null && strtotime($p['lease_until']) > time()) {
                throw new paypal_error('busy', 'Giao dịch đang được xử lý. Vui lòng chờ rồi kiểm tra trạng thái.');
            }
            if (in_array($p['status'], ['pending', 'creating'], true) || ($p['status'] === 'processing' && $kind !== 'capture')) {
                throw new paypal_error('reconcile', 'Cần kiểm tra kết quả giao dịch trước khi thử lại.');
            }
            if (strtotime($p['expires_at']) <= time()) throw new paypal_error('expired', 'Đơn chờ thanh toán đã hết hạn. Hãy hủy đơn để hoàn kho.');
            $this->update('thanhtoan_paypal', ['status' => $kind === 'create' ? 'creating' : 'processing',
                'lease_until' => date('Y-m-d H:i:s', time() + 90), 'error_code' => null], 'id_donhang = ?', [$id]);
            $this->commit();
            return $p;
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function taoPayPal(int $id, int $owner, string $returnState): string
    {
        $existing = $this->lay($id, $owner);
        if (!$existing) throw new paypal_error('owner', 'Không tìm thấy đơn hàng.');
        if (in_array($existing['status'], ['creating', 'processing', 'pending'], true)) $this->kiemTra($id, $owner);
        $p = $this->claim($id, $owner, 'create');
        try {
            if ($p['provider_order_id']) {
                $remote = $this->api()->request('GET', '/v2/checkout/orders/' . rawurlencode($p['provider_order_id']));
            } else {
                $base = rtrim($this->cfg['app_url'], '/') . '/controller/index.php?';
                $remote = $this->api()->request('POST', '/v2/checkout/orders', [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [[
                        'reference_id' => 'BPSTORE-' . $id, 'custom_id' => 'BPSTORE-' . $id,
                        'invoice_id' => 'BPSTORE-' . $p['create_request_id'],
                        'payee' => ['merchant_id' => $p['merchant_id']],
                        'amount' => ['currency_code' => 'USD', 'value' => $p['amount_usd']],
                    ]],
                    'payment_source' => ['paypal' => ['experience_context' => [
                        'brand_name' => 'ĐiệnThoạiZ', 'shipping_preference' => 'NO_SHIPPING', 'user_action' => 'PAY_NOW',
                        'return_url' => $base . http_build_query(['act' => 'paypal_return', 'id' => $id, 'state' => $returnState]),
                        'cancel_url' => $base . http_build_query(['act' => 'paypal_cancel', 'id' => $id, 'state' => $returnState]),
                    ]]],
                ], $p['create_request_id']);
            }
            $this->validateOrder($p, $remote, false);
            $remoteId = (string) ($remote['id'] ?? '');
            if (!preg_match('/^[A-Z0-9]{8,32}$/', $remoteId)) throw new paypal_error('provider', 'PayPal chưa trả mã đơn hợp lệ.');
            $approval = null;
            foreach ($remote['links'] ?? [] as $link) {
                if (in_array($link['rel'] ?? '', ['approve', 'payer-action'], true)) $approval = $link['href'] ?? null;
            }
            $u = is_string($approval) ? parse_url($approval) : [];
            $expectedHost = $p['mode'] === 'live' ? 'www.paypal.com' : 'www.sandbox.paypal.com';
            if (($u['scheme'] ?? '') !== 'https' || ($u['host'] ?? '') !== $expectedHost || isset($u['user']) || isset($u['pass'])) {
                throw new paypal_error('approval', 'PayPal chưa trả đường dẫn duyệt thanh toán hợp lệ.');
            }
            $this->update('thanhtoan_paypal', ['provider_order_id' => $remoteId, 'status' => 'awaiting', 'lease_until' => null], 'id_donhang = ?', [$id]);
            return $approval;
        } catch (Throwable $e) {
            $this->uncertain($id, $e, $p['provider_order_id'] ? 'processing' : 'creating');
            throw $e;
        }
    }

    public function capture(int $id, int $owner): array
    {
        $existing = $this->lay($id, $owner);
        if (!$existing) throw new paypal_error('owner', 'Không tìm thấy giao dịch.');
        if ($existing['status'] === 'paid') return $existing;
        if (in_array($existing['status'], ['creating', 'processing', 'pending'], true)) {
            $existing = $this->kiemTra($id, $owner);
            if ($existing['status'] === 'paid') return $existing;
        }
        $p = $this->claim($id, $owner, 'capture');
        try {
            if (!$p['provider_order_id']) throw new paypal_error('provider', 'Bạn chưa mở thanh toán PayPal cho đơn này.');
            $remote = $this->api()->request('GET', '/v2/checkout/orders/' . rawurlencode($p['provider_order_id']));
            $this->validateOrder($p, $remote);
            if (($remote['status'] ?? '') === 'COMPLETED') return $this->apply($id, $remote, true);
            if (($remote['status'] ?? '') !== 'APPROVED') {
                $this->update('thanhtoan_paypal', ['status' => 'awaiting', 'lease_until' => null], 'id_donhang = ?', [$id]);
                throw new paypal_error('approval', 'Bạn chưa chấp thuận thanh toán trên PayPal.');
            }
            try {
                $remote = $this->api()->request('POST', '/v2/checkout/orders/' . rawurlencode($p['provider_order_id']) . '/capture', [], $p['capture_request_id']);
            } catch (paypal_error $e) {
                // A timeout/error can follow a successful charge. Read the order before deciding anything.
                $remote = $this->api()->request('GET', '/v2/checkout/orders/' . rawurlencode($p['provider_order_id']));
                if (($remote['status'] ?? '') !== 'COMPLETED') throw $e;
            }
            return $this->apply($id, $remote, true);
        } catch (Throwable $e) {
            $now = $this->lay($id);
            if (($now['status'] ?? '') === 'processing') $this->uncertain($id, $e, 'processing');
            throw $e;
        }
    }

    public function kiemTra(int $id, ?int $owner = null): array
    {
        $p = $this->lay($id, $owner);
        if (!$p) throw new paypal_error('owner', 'Không tìm thấy giao dịch.');
        $this->compatible($p);
        if ($p['lease_until'] !== null && strtotime($p['lease_until']) > time()) {
            throw new paypal_error('busy', 'PayPal đang được xử lý. Vui lòng kiểm tra lại sau.');
        }
        if (!$p['provider_order_id']) {
            // Create retries reuse the same idempotency key; an unreturned order cannot have been approved by the buyer.
            if ($p['status'] === 'creating') $this->update('thanhtoan_paypal', ['status' => 'created'], 'id_donhang = ?', [$id]);
            return $this->lay($id, $owner);
        }
        try {
            $remote = $this->api()->request('GET', '/v2/checkout/orders/' . rawurlencode($p['provider_order_id']));
            $this->validateOrder($p, $remote);
            $captures = $remote['purchase_units'][0]['payments']['captures'] ?? [];
            if (count($captures) === 1 && preg_match('/^[A-Z0-9]{8,32}$/', (string) ($captures[0]['id'] ?? ''))) {
                // Capture details are the current source for later pending/refund changes.
                $capture = $this->api()->request('GET', '/v2/payments/captures/' . rawurlencode($captures[0]['id']));
                if (($capture['id'] ?? '') !== $captures[0]['id']
                    || (isset($capture['supplementary_data']['related_ids']['order_id']) && $capture['supplementary_data']['related_ids']['order_id'] !== $p['provider_order_id'])
                    || (isset($capture['payee']['merchant_id']) && $capture['payee']['merchant_id'] !== $p['merchant_id'])) {
                    throw new paypal_error('mismatch', 'Chi tiết khoản thu không khớp đơn hàng. Cửa hàng cần đối soát.');
                }
                $remote['purchase_units'][0]['payments']['captures'] = [$capture];
            }
            return $this->apply($id, $remote);
        } catch (paypal_error $e) {
            if ($e->reason === 'mismatch') $this->uncertain($id, $e, 'review');
            throw $e;
        }
    }

    private static function cents(mixed $value): int
    {
        if (!is_string($value) || !preg_match('/^(0|[1-9][0-9]{0,9})(?:\.([0-9]{1,2}))?$/', $value, $m)) return -1;
        return (int) $m[1] * 100 + (int) str_pad($m[2] ?? '', 2, '0');
    }

    private function validateOrder(array $p, array $remote, bool $checkId = true): void
    {
        $units = $remote['purchase_units'] ?? [];
        $u = $units[0] ?? [];
        if (($checkId && ($remote['id'] ?? '') !== $p['provider_order_id']) || count($units) !== 1
            || ($remote['intent'] ?? '') !== 'CAPTURE' || ($u['custom_id'] ?? '') !== 'BPSTORE-' . $p['id_donhang']
            || ($u['payee']['merchant_id'] ?? '') !== $p['merchant_id'] || ($u['amount']['currency_code'] ?? '') !== 'USD'
            || self::cents($u['amount']['value'] ?? null) !== self::cents($p['amount_usd'])) {
            throw new paypal_error('mismatch', 'Giao dịch PayPal không khớp đơn hàng. Cửa hàng cần đối soát.');
        }
    }

    private function apply(int $id, array $remote, bool $captureResult = false): array
    {
        $this->begin();
        try {
            $this->selectOne('SELECT id FROM donhang WHERE id = ? FOR UPDATE', [$id]);
            $p = $this->lay($id, null, true);
            if (!$p) throw new paypal_error('owner', 'Không tìm thấy giao dịch.');
            if (!$captureResult && $p['lease_until'] !== null && strtotime($p['lease_until']) > time()) {
                $this->commit();
                return $p;
            }
            $this->validateOrder($p, $remote);
            $captures = $remote['purchase_units'][0]['payments']['captures'] ?? [];
            $capture = $captures[0] ?? null;
            $status = ($remote['status'] ?? '') === 'APPROVED' ? 'approved' : 'awaiting';
            $fields = ['lease_until' => null, 'error_code' => null];
            if ($capture !== null) {
                if (count($captures) !== 1 || !preg_match('/^[A-Z0-9]{8,32}$/', (string) ($capture['id'] ?? ''))
                    || ($capture['amount']['currency_code'] ?? '') !== 'USD'
                    || self::cents($capture['amount']['value'] ?? null) !== self::cents($p['amount_usd'])
                    || (!empty($p['capture_id']) && $p['capture_id'] !== $capture['id'])) {
                    throw new paypal_error('mismatch', 'Khoản thu PayPal không khớp. Cửa hàng cần đối soát.');
                }
                $fields['capture_id'] = $capture['id'];
                $status = match ($capture['status'] ?? '') {
                    'COMPLETED' => ($remote['status'] ?? '') === 'COMPLETED' ? 'paid' : 'review',
                    'PENDING' => 'pending', 'DECLINED', 'DENIED', 'FAILED' => 'failed',
                    'REFUNDED' => 'refunded', 'PARTIALLY_REFUNDED', 'REVERSED' => 'review', default => 'review',
                };
                if ($status === 'paid') $fields['paid_at'] = $p['paid_at'] ?? date('Y-m-d H:i:s');
            } elseif (($remote['status'] ?? '') === 'COMPLETED') {
                $status = 'review';
            }
            if ($p['status'] === 'cancelled' || $p['order_status'] === 'Đã hủy') {
                $status = in_array($status, ['paid', 'pending', 'refunded', 'review'], true) ? 'review' : 'cancelled';
            } elseif (in_array($p['status'], ['paid', 'refunded', 'review'], true) && $status === 'awaiting') {
                $status = $p['status'];
            }
            if ($p['status'] === 'processing' && in_array($status, ['awaiting', 'approved'], true)) $status = 'processing';
            if ($p['status'] === 'paid' && !in_array($status, ['paid', 'refunded', 'review'], true)) $status = 'paid';
            if ($p['status'] === 'refunded' && $status !== 'review') $status = 'refunded';
            if ($p['status'] === 'review') $status = 'review';
            $fields['status'] = $status;
            $this->update('thanhtoan_paypal', $fields, 'id_donhang = ?', [$id]);
            $this->commit();
            return $this->lay($id);
        } catch (Throwable $e) {
            $this->rollback();
            if ($e instanceof paypal_error && $e->reason === 'mismatch') {
                $this->update('thanhtoan_paypal', ['status' => 'review', 'lease_until' => null, 'error_code' => 'mismatch'], 'id_donhang = ?', [$id]);
            }
            throw $e;
        }
    }

    private function uncertain(int $id, Throwable $e, string $status): void
    {
        $this->update('thanhtoan_paypal', ['status' => $e instanceof paypal_error && $e->reason === 'mismatch' ? 'review' : $status,
            'lease_until' => null, 'error_code' => $e instanceof paypal_error ? $e->reason : 'internal'], 'id_donhang = ?', [$id]);
        error_log('PayPal order #' . $id . ': ' . ($e instanceof paypal_error ? $e->reason : 'internal'));
    }

    public static function label(?array $p): string
    {
        if ($p === null) return 'COD · thu tiền khi giao hàng';
        return 'PayPal · ' . match ($p['status']) {
            'paid' => 'Đã thanh toán', 'pending', 'processing', 'creating' => 'Đang kiểm tra',
            'cancelled' => 'Đã hủy', 'failed' => 'Chưa thanh toán', 'refunded' => 'Đã hoàn tiền',
            'review' => 'Cần đối soát', 'approved' => 'Đã duyệt, chưa thu tiền', default => 'Chờ thanh toán',
        };
    }

    public static function publicData(?array $payment): ?array
    {
        return $payment === null ? null : array_intersect_key($payment, array_flip(['id_donhang','mode','amount_vnd','amount_usd',
            'vnd_per_usd','rate_source','rate_at','provider_order_id','capture_id','status','expires_at','paid_at','order_status']));
    }
}
