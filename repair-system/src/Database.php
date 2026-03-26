<?php
/**
 * Database — PDO Singleton Wrapper
 *
 * Usage:
 *   $db = Database::getInstance();
 *   $rows = $db->fetchAll("SELECT * FROM customers WHERE status = ?", ['active']);
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;
    private bool $logQueries;
    private array $queryLog = [];

    // ── Constructor ───────────────────────────────────────────────────────────

    private function __construct()
    {
        $cfg = require CONFIG_PATH . '/database.php';

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $cfg['host'],
            $cfg['port'],
            $cfg['dbname'],
            $cfg['charset']
        );

        try {
            $this->pdo = new PDO($dsn, $cfg['username'], $cfg['password'], $cfg['options']);
        } catch (PDOException $e) {
            $this->handleError('Connection failed: ' . $e->getMessage());
        }

        $this->logQueries = filter_var($_ENV['LOG_QUERIES'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function __clone() {}

    // ── Singleton accessor ────────────────────────────────────────────────────

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ── Core execute ──────────────────────────────────────────────────────────

    /**
     * Execute a statement and return the PDOStatement.
     *
     * @param  string $sql
     * @param  array  $params  Positional (?) or named (:key) parameters
     * @return PDOStatement
     */
    public function execute(string $sql, array $params = []): PDOStatement
    {
        $start = microtime(true);

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        } catch (PDOException $e) {
            $this->handleError('Query failed: ' . $e->getMessage(), $sql, $params);
        }

        if ($this->logQueries) {
            $this->queryLog[] = [
                'sql'      => $sql,
                'params'   => $params,
                'duration' => round((microtime(true) - $start) * 1000, 2) . ' ms',
            ];
        }

        return $stmt;
    }

    // ── Fetch helpers ─────────────────────────────────────────────────────────

    /** Fetch a single row (assoc array) or null */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->execute($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** Fetch all rows as array of assoc arrays */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->execute($sql, $params)->fetchAll();
    }

    /** Fetch a single scalar value (first column of first row) */
    public function fetchScalar(string $sql, array $params = []): mixed
    {
        return $this->execute($sql, $params)->fetchColumn();
    }

    // ── Write helpers ─────────────────────────────────────────────────────────

    /**
     * INSERT a row into $table.
     *
     * @param  string $table
     * @param  array  $data   ['column' => value, ...]
     * @return int    Last insert ID
     */
    public function insert(string $table, array $data): int
    {
        $table   = $this->quoteIdentifier($table);
        $columns = implode(', ', array_map([$this, 'quoteIdentifier'], array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->execute($sql, array_values($data));

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * UPDATE rows in $table.
     *
     * @param  string $table
     * @param  array  $data        ['column' => value, ...]
     * @param  string $where       e.g. "customer_id = ?"
     * @param  array  $whereParams Params for the WHERE clause
     * @return int    Affected rows
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $table  = $this->quoteIdentifier($table);
        $setClause = implode(', ', array_map(
            fn($col) => $this->quoteIdentifier($col) . ' = ?',
            array_keys($data)
        ));

        $sql    = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge(array_values($data), $whereParams);
        $stmt   = $this->execute($sql, $params);

        return $stmt->rowCount();
    }

    /**
     * DELETE rows from $table.
     *
     * @param  string $table
     * @param  string $where
     * @param  array  $params
     * @return int    Affected rows
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $table = $this->quoteIdentifier($table);
        $stmt  = $this->execute("DELETE FROM {$table} WHERE {$where}", $params);
        return $stmt->rowCount();
    }

    // ── Transaction support ───────────────────────────────────────────────────

    public function beginTransaction(): void  { $this->pdo->beginTransaction(); }
    public function commit(): void            { $this->pdo->commit(); }
    public function rollback(): void          { $this->pdo->rollBack(); }

    public function inTransaction(): bool     { return $this->pdo->inTransaction(); }

    /** Execute a callable inside a transaction; rolls back on exception. */
    public function transaction(callable $fn): mixed
    {
        $this->beginTransaction();
        try {
            $result = $fn($this);
            $this->commit();
            return $result;
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    // ── Misc ──────────────────────────────────────────────────────────────────

    public function lastInsertId(): int
    {
        return (int)$this->pdo->lastInsertId();
    }

    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /** Raw PDO access (use sparingly) */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function quoteIdentifier(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    private function handleError(string $message, string $sql = '', array $params = []): never
    {
        if (APP_DEBUG) {
            $detail = $sql ? "\nSQL: {$sql}\nParams: " . json_encode($params) : '';
            throw new RuntimeException($message . $detail);
        }

        // In production: log and show generic error
        error_log('[DB ERROR] ' . $message . ($sql ? " | SQL: {$sql}" : ''));
        http_response_code(500);
        // Let the error handler show the 500 page
        throw new RuntimeException('A database error occurred. Please try again later.');
    }
}
