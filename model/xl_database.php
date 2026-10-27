<?php

require_once __DIR__ . '/csdl.php';

class xl_database extends csdl
{
    public function select(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function selectOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function insert(string $table, array $data): int
    {
        $this->assertTenCot(array_keys($data));
        $cols = implode(', ', array_map(static fn($c) => "`{$c}`", array_keys($data)));
        $holders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO `{$this->assertTenBang($table)}` ({$cols}) VALUES ({$holders})";
        $this->execute($sql, array_values($data));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $this->assertTenCot(array_keys($data));
        $sets = [];
        $params = [];
        foreach ($data as $col => $val) {
            $sets[] = "`{$col}` = ?";
            $params[] = $val;
        }
        $sql = "UPDATE `{$this->assertTenBang($table)}` SET " . implode(', ', $sets) . " WHERE {$where}";
        return $this->execute($sql, array_merge($params, $whereParams));
    }

    public function delete(string $table, string $where, array $whereParams = []): int
    {
        $sql = "DELETE FROM `{$this->assertTenBang($table)}` WHERE {$where}";
        return $this->execute($sql, $whereParams);
    }

    public function begin(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->commit();
        }
    }

    public function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    protected function soNguyenHopLe(mixed $value, int $min = 0): bool
    {
        return !is_bool($value) && (is_int($value) || is_string($value))
            && filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => 2147483647]]) !== false;
    }

    protected function soTienHopLe(mixed $value): bool
    {
        return (is_int($value) || is_float($value) || is_string($value))
            && is_numeric($value) && is_finite((float) $value)
            && (float) $value >= 0 && (float) $value <= 9999999999.99;
    }

    protected function ngayHopLe(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $ngay = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $ngay !== false && $ngay->format('Y-m-d') === $value;
    }

    protected function xacThucVaNangCapMatKhau(string $matKhau, string $matKhauDb, string $table, int $id): bool
    {
        $legacy = false;
        if (password_verify($matKhau, $matKhauDb)) {
            if (!password_needs_rehash($matKhauDb, PASSWORD_DEFAULT)) {
                return true;
            }
        } else {
            // Hash hiện đại phải được kiểm tra bằng password_verify, không so như mật khẩu thô.
            if (password_get_info($matKhauDb)['algo'] !== null) return false;
            $legacy = preg_match('/^[a-f0-9]{32}$/i', $matKhauDb) === 1
                ? hash_equals(strtolower($matKhauDb), md5($matKhau))
                : hash_equals($matKhauDb, $matKhau);
            if (!$legacy) return false;
        }

        if ($legacy || password_needs_rehash($matKhauDb, PASSWORD_DEFAULT)) {
            if (!in_array($table, ['khachhang', 'nhanvien'], true) || $id <= 0) {
                throw new LogicException('Không thể nâng cấp mật khẩu cho bản ghi này.');
            }
            $this->update($table, ['mat_khau' => password_hash($matKhau, PASSWORD_DEFAULT)], 'id = ?', [$id]);
        }
        return true;
    }

    private function assertTenBang(string $table): string
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new InvalidArgumentException('Ten bang khong hop le');
        }
        return $table;
    }

    private function assertTenCot(array $cols): void
    {
        foreach ($cols as $col) {
            if (!is_string($col) || !preg_match('/^[a-zA-Z0-9_]+$/', $col)) {
                throw new InvalidArgumentException('Ten cot khong hop le');
            }
        }
    }
}
