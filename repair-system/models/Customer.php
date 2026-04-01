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
        string $dir     = 'ASC'
    ): array {
        $where  = '';
        $params = [];

        if ($status !== '') {
            $where   = 'WHERE status = ?';
            $params[] = $status;
        }

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
        string $dir    = 'ASC'
    ): array {
        $like   = '%' . $query . '%';
        $params = [$like, $like, $like, $like, $like, $like, $like];

        $where = "(full_name LIKE ? OR first_name LIKE ? OR last_name LIKE ?
                   OR email LIKE ? OR phone_mobile LIKE ? OR city LIKE ?
                   OR vat_number LIKE ?)";

        if ($status !== '') {
            $where   .= " AND status = ?";
            $params[] = $status;
        }

        $total = (int)$this->db->fetchScalar(
            "SELECT COUNT(*) FROM customers WHERE {$where}", $params
        );

        $paging = Utils::paginate($total, $page);

        $col     = in_array($sort, self::SORTABLE, true) ? $sort : 'full_name';
        $dir     = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $orderBy = "`{$col}` {$dir}";

        $rows = $this->db->fetchAll(
            "SELECT * FROM customers WHERE {$where}
             ORDER BY {$orderBy} LIMIT ? OFFSET ?",
            array_merge($params, [$paging['perPage'], $paging['offset']])
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
        $row = $this->db->fetchOne(
            "SELECT
                COUNT(DISTINCT r.repair_id)                                AS total_repairs,
                SUM(r.status IN ('completed','collected'))                 AS completed_repairs,
                SUM(r.status = 'in_progress')                             AS active_repairs,
                COALESCE(SUM(i.total_amount), 0)                          AS total_billed,
                COALESCE(SUM(i.amount_paid), 0)                           AS total_paid,
                COALESCE(SUM(i.total_amount) - SUM(i.amount_paid), 0)     AS balance_due,
                MIN(r.date_in)                                             AS first_repair,
                MAX(r.date_in)                                             AS last_repair
             FROM customers c
             LEFT JOIN repairs  r ON r.customer_id = c.customer_id
             LEFT JOIN invoices i ON i.customer_id = c.customer_id
                                  AND i.status != 'cancelled'
             WHERE c.customer_id = ?
             GROUP BY c.customer_id",
            [$customerId]
        );

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

    public function autocomplete(string $query, int $limit = 10): array
    {
        $like = '%' . $query . '%';
        return $this->db->fetchAll(
            "SELECT customer_id, full_name, phone_mobile, email, city
             FROM customers
             WHERE status = 'active'
               AND (full_name LIKE ? OR phone_mobile LIKE ? OR email LIKE ?)
             ORDER BY full_name LIMIT ?",
            [$like, $like, $like, $limit]
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
        $where  = $status ? "WHERE status = '{$status}'" : '';
        return $this->db->fetchAll(
            "SELECT customer_id, full_name, first_name, last_name, client_type,
                    address, postal_code, city, province,
                    phone_landline, phone_mobile, email,
                    vat_number, tax_id, status, customer_since, created_at
             FROM customers {$where}
             ORDER BY full_name"
        );
    }

    // ── Aggregate stats (for dashboard / list header) ─────────────────────────

    public function getCounts(): array
    {
        return $this->db->fetchOne(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'active')   AS active,
                SUM(status = 'inactive') AS inactive
             FROM customers"
        ) ?? ['total' => 0, 'active' => 0, 'inactive' => 0];
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
