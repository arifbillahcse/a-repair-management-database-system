<?php
/**
 * BaseModel — shared CRUD foundation for all model classes.
 *
 * Each model sets:
 *   protected string $table      — DB table name
 *   protected string $primaryKey — PK column (default: auto-detected as {table}_id)
 */
abstract class BaseModel
{
    protected Database $db;
    protected string $table;
    protected string $primaryKey;

    public function __construct()
    {
        $this->db = Database::getInstance();

        // Auto-derive PK from table name if not explicitly set (e.g. 'customers' → 'customer_id')
        if (empty($this->primaryKey)) {
            $this->primaryKey = rtrim($this->table, 's') . '_id';
        }
    }

    // ── READ ──────────────────────────────────────────────────────────────────

    /** Find a single row by primary key. Returns null if not found. */
    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? LIMIT 1",
            [$id]
        );
    }

    /**
     * Fetch all rows with optional WHERE, ORDER BY, LIMIT, OFFSET.
     *
     * @param  array  $conditions  ['column = ?' => value, …] pairs — key is raw SQL fragment
     * @param  string $orderBy     e.g. 'last_name ASC'
     * @param  int    $limit       0 = no limit
     * @param  int    $offset
     * @return array
     */
    public function findAll(
        array  $conditions = [],
        string $orderBy    = '',
        int    $limit      = 0,
        int    $offset     = 0
    ): array {
        [$where, $params] = $this->buildWhere($conditions);

        $sql  = "SELECT * FROM `{$this->table}`";
        $sql .= $where   ? " WHERE {$where}"    : '';
        $sql .= $orderBy ? " ORDER BY {$orderBy}" : '';
        $sql .= $limit   ? " LIMIT {$limit}"    : '';
        $sql .= $offset  ? " OFFSET {$offset}"  : '';

        return $this->db->fetchAll($sql, $params);
    }

    /** Count rows matching $conditions. */
    public function count(array $conditions = []): int
    {
        [$where, $params] = $this->buildWhere($conditions);

        $sql  = "SELECT COUNT(*) FROM `{$this->table}`";
        $sql .= $where ? " WHERE {$where}" : '';

        return (int)$this->db->fetchScalar($sql, $params);
    }

    // ── WRITE ─────────────────────────────────────────────────────────────────

    /**
     * Insert a new row.
     *
     * @param  array $data ['column' => value, …]
     * @return int   Inserted primary key
     */
    public function create(array $data): int
    {
        // Automatically set created_at if the table has it and it's not provided
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        return $this->db->insert($this->table, $data);
    }

    /**
     * Update row(s) by primary key.
     *
     * @param  int   $id
     * @param  array $data
     * @return int   Affected rows
     */
    public function update(int $id, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update($this->table, $data, "`{$this->primaryKey}` = ?", [$id]);
    }

    /**
     * Soft-delete by setting status = 'inactive' (if column exists).
     * Hard-delete with hardDelete().
     */
    public function delete(int $id): int
    {
        return $this->db->delete($this->table, "`{$this->primaryKey}` = ?", [$id]);
    }

    // ── Pagination helper ─────────────────────────────────────────────────────

    /**
     * Paginated find.
     *
     * @return array{rows: array, pagination: array}
     */
    public function paginate(
        array  $conditions = [],
        int    $page       = 1,
        int    $perPage    = PAGE_SIZE,
        string $orderBy    = ''
    ): array {
        $total   = $this->count($conditions);
        $paging  = Utils::paginate($total, $page, $perPage);

        $rows = $this->findAll(
            $conditions,
            $orderBy,
            $paging['perPage'],
            $paging['offset']
        );

        return ['rows' => $rows, 'pagination' => $paging];
    }

    // ── Internal helpers ──────────────────────────────────────────────────────

    /**
     * Build a WHERE clause and params array from a conditions map.
     *
     * Keys are raw SQL fragments: 'status = ?' or 'name LIKE ?'
     * Values are the bound parameters.
     *
     * @return array{0: string, 1: array}
     */
    protected function buildWhere(array $conditions): array
    {
        if (empty($conditions)) {
            return ['', []];
        }

        $clauses = array_keys($conditions);
        $params  = array_values($conditions);

        return [implode(' AND ', $clauses), $params];
    }

    /** Fetch one row by a single column value. */
    protected function findOneBy(string $column, mixed $value): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM `{$this->table}` WHERE `{$column}` = ? LIMIT 1",
            [$value]
        );
    }
}
