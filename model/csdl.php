<?php

class csdl
{
    protected PDO $pdo;
    private static ?PDO $shared = null;

    public function __construct()
    {
        $this->pdo = self::shared();
    }

    public static function cauHinh(): array
    {
        $cfg = ['driver' => 'mysql', 'bridge_url' => '', 'bridge_token' => '', 'host' => '127.0.0.1', 'port' => 3306, 'dbname' => 'webbanhang_mvc',
            'user' => 'root', 'pass' => '', 'charset' => 'utf8mb4'];
        $file = dirname(__DIR__) . '/support/config/database.local.php';
        if (!is_file($file)) {
            $file = dirname(__DIR__) . '/config/database.local.php'; // Tuong thich cau hinh cu khi git pull.
        }
        if (is_file($file)) {
            $local = require $file;
            if (!is_array($local)) {
                throw new RuntimeException('Cau hinh CSDL phai tra ve mot mang');
            }
            $cfg = array_replace($cfg, $local);
        }
        foreach (['driver', 'bridge_url', 'bridge_token', 'host', 'port', 'dbname', 'user', 'pass', 'charset'] as $key) {
            $env = getenv($key === 'dbname' ? 'DB_NAME' : 'DB_' . strtoupper($key));
            if ($env !== false) {
                $cfg[$key] = $env;
            }
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', (string) $cfg['dbname'])
            || !preg_match('/^[a-zA-Z0-9_]+$/', (string) $cfg['charset'])
            || !filter_var($cfg['port'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]])) {
            throw new RuntimeException('Ten CSDL, charset hoac cong ket noi khong hop le');
        }
        $cfg['port'] = (int) $cfg['port'];
        return $cfg;
    }

    public static function shared(
        ?string $host = null,
        ?string $dbname = null,
        ?string $user = null,
        ?string $pass = null,
        string $charset = 'utf8mb4',
        ?int $port = null
    ): PDO {
        if (self::$shared instanceof PDO) {
            return self::$shared;
        }
        $cfg = self::cauHinh();
        if ($cfg['driver'] === 'https') {
            require_once dirname(__DIR__) . '/support/lib/HttpsDatabase.php';
            self::$shared = new HttpsDatabase($cfg['bridge_url'], $cfg['bridge_token'], $dbname ?? $cfg['dbname']);
            return self::$shared;
        }
        if ($cfg['driver'] !== 'mysql') throw new RuntimeException('Database driver khong duoc ho tro');
        self::$shared = self::ketNoi($host ?? $cfg['host'], $dbname ?? $cfg['dbname'],
            $user ?? $cfg['user'], $pass ?? $cfg['pass'], $charset, $port ?? $cfg['port']);
        return self::$shared;
    }

    public static function ketNoi(
        string $host,
        string $dbname,
        string $user,
        string $pass,
        string $charset = 'utf8mb4',
        int $port = 3306
    ): PDO {
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        $pdo->exec("SET time_zone = '+07:00'");
        return $pdo;
    }

    public static function resetKetNoi(): void
    {
        self::$shared = null;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }
}
