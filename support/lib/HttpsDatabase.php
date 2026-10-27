<?php

/** PDO subset used by this application, transported to a single HTTPS bridge instance. */
final class HttpsDatabase extends PDO
{
    private string $session = '';
    private bool $transaction = false;
    private string $insertId = '0';
    private bool $broken = false;
    private ?CurlHandle $curl = null;

    public function __construct(private string $url, private string $token, string $database)
    {
        $parts = parse_url($url);
        $local = getenv('DB_BRIDGE_LOCAL_TEST') === '1'
            && ($parts['scheme'] ?? '') === 'http' && ($parts['host'] ?? '') === '127.0.0.1';
        if (!$parts || (!$local && ($parts['scheme'] ?? '') !== 'https')
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
            || (!$local && isset($parts['port']) && $parts['port'] !== 443)
            || !preg_match('/^[A-Za-z0-9_-]{32,}$/', $token)) {
            throw new InvalidArgumentException('Cau hinh HTTPS database bridge khong hop le');
        }
        if (!function_exists('curl_init')) throw new RuntimeException('HTTPS database can PHP cURL');
        $this->url = rtrim($url, '/') . '/rpc';
        $result = $this->rpc(['op' => 'connect', 'database' => $database]);
        $this->session = $result['session'];
    }

    public function rpc(array $payload): array
    {
        if ($this->broken) throw new PDOException('Database bridge connection is no longer usable');
        $curl = $this->curl ??= curl_init($this->url);
        curl_setopt_array($curl, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload + ['session' => $this->session], JSON_THROW_ON_ERROR),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $this->token],
            CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => ($payload['op'] ?? '') === 'connect' ? 50 : 30,
            CURLOPT_FOLLOWLOCATION => false, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
            // A stale reused TCP connection can make cURL replay a POST internally.
            // Keep the handle's TLS cache, but require a fresh connection per RPC.
            CURLOPT_FRESH_CONNECT => true, CURLOPT_FORBID_REUSE => true,
        ]);
        $raw = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        if ($raw === false) {
            $this->broken = true;
            throw new PDOException('Mat ket noi database bridge; khong tu dong gui lai thao tac ghi');
        }
        try { $result = json_decode($raw, true, 512, JSON_THROW_ON_ERROR); }
        catch (JsonException) { $this->broken = true; throw new PDOException('Database bridge tra ve du lieu khong hop le'); }
        if ($status !== 200 || !is_array($result) || ($result['ok'] ?? false) !== true) {
            if ($status < 500 || !isset($result['sqlstate']) || !empty($result['connectionLost'])) $this->broken = true;
            $state = $result['sqlstate'] ?? 'HY000';
            throw new HttpsDatabaseException('Database bridge: ' . ($result['error'] ?? 'request failed'),
                $state, (int)($result['errno'] ?? 0));
        }
        return $result;
    }

    public function run(string $sql, array $params = [], bool $exec = false): array
    {
        $result = $this->rpc(['op' => $exec ? 'exec' : 'query', 'sql' => $sql, 'params' => array_values($params)]);
        $this->insertId = $result['insertId'] ?? '0';
        return $result;
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        if ($options) throw new LogicException('HTTPS database does not support statement options');
        return new HttpsStatement($this, $query);
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        $stmt = $this->prepare($query);
        if ($fetchMode !== null) $stmt->setFetchMode($fetchMode, ...$fetchModeArgs);
        $stmt->execute();
        return $stmt;
    }

    public function exec(string $statement): int|false { return $this->run($statement, [], true)['affected']; }
    public function lastInsertId(?string $name = null): string|false { return $this->insertId; }
    public function beginTransaction(): bool
    {
        if ($this->transaction) throw new PDOException('There is already an active transaction');
        $this->rpc(['op' => 'begin']); $this->transaction = true; return true;
    }
    public function commit(): bool
    {
        if (!$this->transaction) throw new PDOException('There is no active transaction');
        $this->rpc(['op' => 'commit']); $this->transaction = false; return true;
    }
    public function rollBack(): bool
    {
        if (!$this->transaction) throw new PDOException('There is no active transaction');
        if (!$this->broken) $this->rpc(['op' => 'rollback']);
        $this->transaction = false; return true;
    }
    public function inTransaction(): bool { return $this->transaction; }
    public function getAttribute(int $attribute): mixed
    {
        return match ($attribute) { PDO::ATTR_DRIVER_NAME => 'https-mysql', PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            default => throw new LogicException('Unsupported PDO attribute') };
    }
    public function __destruct()
    {
        if ($this->session !== '' && !$this->broken) {
            try { $this->rpc(['op' => 'close']); } catch (Throwable) { /* Server TTL closes abandoned sessions. */ }
        }
        if ($this->curl !== null) { curl_close($this->curl); $this->curl = null; }
    }
}

final class HttpsDatabaseException extends PDOException
{
    public function __construct(string $message, string $state, int $errno)
    {
        parent::__construct($message);
        $this->code = $state;
        $this->errorInfo = [$state, $errno, 'Database operation failed'];
    }
}

final class HttpsStatement extends PDOStatement
{
    private array $rows = [];
    private array $columns = [];
    private int $position = 0;
    private int $affected = 0;
    private int $mode = PDO::FETCH_ASSOC;
    private int $column = 0;
    public function __construct(private HttpsDatabase $db, private string $sql) {}
    public function execute(?array $params = null): bool
    {
        $result = $this->db->run($this->sql, $params ?? []);
        $this->rows = $result['rows']; $this->columns = $result['columns'];
        $this->affected = $result['affected']; $this->position = 0;
        return true;
    }
    public function setFetchMode(int $mode, mixed ...$args): bool
    {
        if (!in_array($mode, [PDO::FETCH_ASSOC, PDO::FETCH_NUM, PDO::FETCH_BOTH, PDO::FETCH_COLUMN], true)) {
            throw new LogicException('Unsupported HTTPS database fetch mode');
        }
        $this->mode = $mode; $this->column = (int)($args[0] ?? 0); return true;
    }
    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
    {
        if ($cursorOrientation !== PDO::FETCH_ORI_NEXT) throw new LogicException('Forward cursor only');
        if (!array_key_exists($this->position, $this->rows)) return false;
        $row = $this->rows[$this->position++];
        $mode = $mode === PDO::FETCH_DEFAULT ? $this->mode : $mode;
        return match ($mode) {
            PDO::FETCH_ASSOC => array_combine($this->columns, $row),
            PDO::FETCH_NUM => $row,
            PDO::FETCH_BOTH => array_combine($this->columns, $row) + $row,
            PDO::FETCH_COLUMN => $row[$this->column] ?? false,
            default => throw new LogicException('Unsupported HTTPS database fetch mode'),
        };
    }
    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        if ($mode === PDO::FETCH_COLUMN) $this->column = (int)($args[0] ?? 0);
        $result = [];
        while ($this->position < count($this->rows)) $result[] = $this->fetch($mode);
        return $result;
    }
    public function fetchColumn(int $column = 0): mixed
    {
        if (!array_key_exists($this->position, $this->rows)) return false;
        return $this->rows[$this->position++][$column] ?? false;
    }
    public function rowCount(): int { return $this->affected; }
    public function columnCount(): int { return count($this->columns); }
    public function closeCursor(): bool { $this->rows = []; $this->position = 0; return true; }
}
