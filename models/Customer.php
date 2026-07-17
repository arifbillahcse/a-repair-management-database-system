<?php
class Customer extends BaseModel
{
    protected string $table      = 'customers';
    protected string $primaryKey = 'customer_id';

    // Allowed sort columns (whitelist prevents SQL injection)
    private const SORTABLE = [
        'full_name', 'city', 'province', 'phone_mobile',
        'email', 'status', 'created_at', 'customer_since',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->migrateDropLegacyNameCols();
    }

    // Backfill full_name from first_name/last_name then drop the old columns
    private function migrateDropLegacyNameCols(): void
    {
        $pdo = $this->db->getPdo();
        $existing = array_column(
            $pdo->query("SHOW COLUMNS FROM `customers`")->fetchAll(PDO::FETCH_ASSOC),
            'Field'
        );
        if (!in_array('first_name', $existing)) return;

        $pdo->exec("UPDATE customers
                    SET full_name = TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')))
                    WHERE full_name IS NULL OR full_name = ''");

        $pdo->exec("ALTER TABLE customers DROP COLUMN first_name, DROP COLUMN last_name");
    }

    // ── Lookup ────────────────────────────────────────────────────────────────

    public function findByEmail(string $email): ?array
    {
        return $this->findOneBy('email', $email);
    }

    public function findByPhone(string $phone): ?array
    {
        $clean = preg_replace('/[^\d]/', '', $phone);
        return $this->db->fetchOne(
            "SELECT * FROM customers
             WHERE REGEXP_REPLACE(phone_mobile,   '[^0-9]', '') = ?
                OR REGEXP_REPLACE(phone_landline, '[^0-9]', '') = ?
             LIMIT 1",
            [$clean, $clean]
        );
    }

    // ── Paginated list with sort ──────────────────────────────────────────────

    public function getAll(
        int    $page    = 1,
        string $status  = '',
        string $sort    = 'full_name',
        string $dir     = 'ASC',
        string $type    = ''
    ): array {
        $clauses = [];
        $params  = [];

        if ($status !== '') { $clauses[] = 'status = ?';      $params[] = $status; }
        if ($type   !== '') { $clauses[] = 'client_type = ?'; $params[] = $type;   }

        $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';

        $col     = in_array($sort, self::SORTABLE, true) ? $sort : 'full_name';
        $dir     = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $orderBy = "`{$col}` {$dir}";

        $total  = (int)$this->db->fetchScalar("SELECT COUNT(*) FROM customers {$where}", $params);
        $paging = Utils::paginate($total, $page);

        $rows = $this->db->fetchAll(
            "SELECT * FROM customers {$where} ORDER BY {$orderBy} LIMIT ? OFFSET ?",
            array_merge($params, [$paging['perPage'], $paging['offset']])
        );

        return ['rows' => $rows, 'pagination' => $paging];
    }

    // ── Full-text search ──────────────────────────────────────────────────────

    public function search(
        string $query,
        int    $page   = 1,
        string $status = '',
        string $sort   = 'full_name',
        string $dir    = 'ASC',
        string $type   = ''
    ): array {
        // Collapse repeated/odd whitespace in the typed query.
        $query = trim(preg_replace('/\s+/u', ' ', $query));
        $like  = '%' . $query . '%';

        // Split into individual words and require EVERY word to appear in
        // full_name, in ANY order — so "CARMELA SCAROLA" finds "SCAROLA CARMELA"
        // and also tolerates extra/irregular spacing inside stored names.
        $words = $query === '' ? [] : preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY);

        $nameClauses = [];
        $nameParams  = [];
        foreach ($words as $w) {
            $nameClauses[] = 'full_name LIKE ?';
            $nameParams[]  = '%' . $w . '%';
        }
        // Fallback for empty query (shouldn't occur but keeps the SQL valid)
        if (!$nameClauses) { $nameClauses[] = 'full_name LIKE ?'; $nameParams[] = $like; }

        $nameWhere = implode(' AND ', $nameClauses);
        $params    = array_merge($nameParams, [$like, $like, $like, $like, $like]);

        $where = "(({$nameWhere})
                   OR email LIKE ? OR phone_mobile LIKE ? OR phone_landline LIKE ? OR city LIKE ?
                   OR vat_number LIKE ?)";

        if ($status !== '') { $where .= " AND status = ?";      $params[] = $status; }
        if ($type   !== '') { $where .= " AND client_type = ?"; $params[] = $type;   }

        $total = (int)$this->db->fetchScalar(
            "SELECT COUNT(*) FROM customers WHERE {$where}", $params
        );

        $paging = Utils::paginate($total, $page);

        $col     = in_array($sort, self::SORTABLE, true) ? $sort : 'full_name';
        $dir     = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';

        // When searching by name with default sort, use relevance ordering
        // so partial matches (e.g. "CHIARA") surface the best results first
        if ($query !== '' && $col === 'full_name' && $dir === 'ASC') {
            $orderBy   = "CASE WHEN full_name LIKE ? THEN 0 ELSE 1 END, LOCATE(?, full_name), full_name ASC";
            $extraParams = [$query . '%', $query];
        } else {
            $orderBy   = "`{$col}` {$dir}";
            $extraParams = [];
        }

        $rows = $this->db->fetchAll(
            "SELECT * FROM customers WHERE {$where}
             ORDER BY {$orderBy} LIMIT ? OFFSET ?",
            array_merge($params, $extraParams, [$paging['perPage'], $paging['offset']])
        );

        return ['rows' => $rows, 'pagination' => $paging];
    }

    // ── Related records ───────────────────────────────────────────────────────

    public function getRepairHistory(int $customerId, int $limit = 0): array
    {
        $sql = "SELECT r.*,
                       CONCAT(s.first_name,' ',s.last_name) AS technician_name,
                       DATEDIFF(COALESCE(r.date_out, NOW()), r.date_in) AS days_in_lab
                FROM repairs r
                LEFT JOIN staff s ON s.staff_id = r.staff_id
                WHERE r.customer_id = ?
                ORDER BY r.date_in DESC";
        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
        }
        return $this->db->fetchAll($sql, [$customerId]);
    }

    public function getInvoices(int $customerId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM invoices
             WHERE customer_id = ?
             ORDER BY invoice_date DESC",
            [$customerId]
        );
    }

    public function getStats(int $customerId): array
    {
        // Repairs and invoices are aggregated in separate subqueries — joining
        // both tables directly to customers in one query would fan them out
        // into a cross product (e.g. 3 repairs x 2 invoices = 6 rows), silently
        // multiplying every SUM()/COUNT() whenever a customer has more than
        // one row on each side.
        $row = $this->db->fetchOne(
            "SELECT
                (SELECT COUNT(*) FROM repairs r WHERE r.customer_id = c.customer_id)
                    AS total_repairs,
                (SELECT COUNT(*) FROM repairs r WHERE r.customer_id = c.customer_id
                    AND r.status IN ('completed','collected'))
                    AS completed_repairs,
                (SELECT COUNT(*) FROM repairs r WHERE r.customer_id = c.customer_id
                    AND r.status = 'in_progress')
                    AS active_repairs,
                (SELECT COALESCE(SUM(i.total_amount), 0) FROM invoices i
                    WHERE i.customer_id = c.customer_id AND i.status != 'cancelled')
                    AS total_billed,
                (SELECT COALESCE(SUM(i.amount_paid), 0) FROM invoices i
                    WHERE i.customer_id = c.customer_id AND i.status != 'cancelled')
                    AS total_paid,
                (SELECT MIN(r.date_in) FROM repairs r WHERE r.customer_id = c.customer_id)
                    AS first_repair,
                (SELECT MAX(r.date_in) FROM repairs r WHERE r.customer_id = c.customer_id)
                    AS last_repair
             FROM customers c
             WHERE c.customer_id = ?",
            [$customerId]
        );

        if ($row) {
            $row['balance_due'] = round((float)$row['total_billed'] - (float)$row['total_paid'], 2);
        }

        return $row ?? [
            'total_repairs'     => 0,
            'completed_repairs' => 0,
            'active_repairs'    => 0,
            'total_billed'      => 0,
            'total_paid'        => 0,
            'balance_due'       => 0,
            'first_repair'      => null,
            'last_repair'       => null,
        ];
    }

    // ── Autocomplete (AJAX) ───────────────────────────────────────────────────

    public function autocomplete(string $query, int $limit = 20): array
    {
        $query     = trim(preg_replace('/\s+/u', ' ', $query));
        $like      = '%' . $query . '%';
        $startLike = $query . '%';

        // Match every word independently so "CARMELA SCAROLA" finds "SCAROLA CARMELA"
        $words = $query === '' ? [] : preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY);
        $nameClauses = [];
        $nameParams  = [];
        foreach ($words as $w) {
            $nameClauses[] = 'full_name LIKE ?';
            $nameParams[]  = '%' . $w . '%';
        }
        if (!$nameClauses) { $nameClauses[] = 'full_name LIKE ?'; $nameParams[] = $like; }
        $nameWhere = implode(' AND ', $nameClauses);

        return $this->db->fetchAll(
            "SELECT customer_id, full_name, phone_mobile AS phone, email, city
             FROM customers
             WHERE status = 'active'
               AND (({$nameWhere}) OR phone_mobile LIKE ? OR phone_landline LIKE ? OR email LIKE ?)
             ORDER BY
               CASE WHEN full_name LIKE ? THEN 0 ELSE 1 END,
               LOCATE(?, full_name),
               full_name
             LIMIT ?",
            array_merge($nameParams, [$like, $like, $like, $startLike, $query, $limit])
        );
    }

    // ── Duplicate detection ───────────────────────────────────────────────────

    /** Check if another customer has the same email (excluding $excludeId). */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        if (empty($email)) return false;
        $sql    = "SELECT COUNT(*) FROM customers WHERE email = ?";
        $params = [$email];
        if ($excludeId) {
            $sql    .= " AND customer_id != ?";
            $params[] = $excludeId;
        }
        return (int)$this->db->fetchScalar($sql, $params) > 0;
    }

    /** Check if another customer has the same mobile phone. */
    public function phoneExists(string $phone, ?int $excludeId = null): bool
    {
        if (empty($phone)) return false;
        $sql    = "SELECT COUNT(*) FROM customers WHERE phone_mobile = ?";
        $params = [$phone];
        if ($excludeId) {
            $sql    .= " AND customer_id != ?";
            $params[] = $excludeId;
        }
        return (int)$this->db->fetchScalar($sql, $params) > 0;
    }

    // ── CSV Export ────────────────────────────────────────────────────────────

    /** Fetch ALL customers for CSV export (no pagination). */
    public function getForExport(string $status = ''): array
    {
        $where  = $status ? 'WHERE status = ?' : '';
        $params = $status ? [$status] : [];
        return $this->db->fetchAll(
            "SELECT customer_id, full_name, client_type,
                    address, postal_code, city, province,
                    phone_landline, phone_mobile, email,
                    vat_number, tax_id, status, customer_since, created_at
             FROM customers {$where}
             ORDER BY full_name",
            $params
        );
    }

    // ── Aggregate stats (for dashboard / list header) ─────────────────────────

    public function getCounts(): array
    {
        return $this->db->fetchOne(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'active')              AS active,
                SUM(status = 'inactive')             AS inactive,
                SUM(client_type = 'colleague')       AS colleagues
             FROM customers"
        ) ?? ['total' => 0, 'active' => 0, 'inactive' => 0, 'colleagues' => 0];
    }

    // ── Batch import (used by import script) ──────────────────────────────────

    /**
     * Upsert a customer row from CSV import.
     * Returns 'created' | 'updated' | 'skipped'.
     */
    public function importRow(array $data): string
    {
        // Try to find by email or phone
        $existing = null;

        if (!empty($data['email'])) {
            $existing = $this->findByEmail($data['email']);
        }
        if (!$existing && !empty($data['phone_mobile'])) {
            $existing = $this->findByPhone($data['phone_mobile']);
        }

        if ($existing) {
            // Update only if data has changed
            $this->update($existing['customer_id'], $data);
            return 'updated';
        }

        $this->create($data);
        return 'created';
    }
}
