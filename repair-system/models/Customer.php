<?php
class Customer extends BaseModel
{
    protected string $table      = 'customers';
    protected string $primaryKey = 'customer_id';

    // ── Lookup ────────────────────────────────────────────────────────────────

    public function findByEmail(string $email): ?array
    {
        return $this->findOneBy('email', $email);
    }

    public function findByPhone(string $phone): ?array
    {
        $clean = Utils::sanitizePhone($phone);
        return $this->db->fetchOne(
            "SELECT * FROM customers
             WHERE REPLACE(REPLACE(REPLACE(phone_mobile,' ',''),'-',''),'+','') = ?
                OR REPLACE(REPLACE(REPLACE(phone_landline,' ',''),'-',''),'+','') = ?
             LIMIT 1",
            [$clean, $clean]
        );
    }

    // ── Search ────────────────────────────────────────────────────────────────

    /**
     * Full-text style search across name, email, phone, city, VAT.
     */
    public function search(string $query, int $page = 1, string $status = ''): array
    {
        $like   = '%' . $query . '%';
        $params = [$like, $like, $like, $like, $like, $like];

        $where = "(full_name LIKE ? OR first_name LIKE ? OR last_name LIKE ?
                   OR email LIKE ? OR phone_mobile LIKE ? OR city LIKE ?)";

        if ($status) {
            $where   .= " AND status = ?";
            $params[] = $status;
        }

        $total = (int)$this->db->fetchScalar(
            "SELECT COUNT(*) FROM customers WHERE {$where}", $params
        );

        $paging = Utils::paginate($total, $page);

        $rows = $this->db->fetchAll(
            "SELECT * FROM customers WHERE {$where}
             ORDER BY full_name LIMIT ? OFFSET ?",
            array_merge($params, [$paging['perPage'], $paging['offset']])
        );

        return ['rows' => $rows, 'pagination' => $paging];
    }

    // ── Paginated list ────────────────────────────────────────────────────────

    public function getAll(int $page = 1, string $status = '', string $orderBy = 'full_name ASC'): array
    {
        $where  = '';
        $params = [];

        if ($status) {
            $where   = 'WHERE status = ?';
            $params[] = $status;
        }

        $total  = (int)$this->db->fetchScalar("SELECT COUNT(*) FROM customers {$where}", $params);
        $paging = Utils::paginate($total, $page);

        $rows = $this->db->fetchAll(
            "SELECT * FROM customers {$where} ORDER BY {$orderBy} LIMIT ? OFFSET ?",
            array_merge($params, [$paging['perPage'], $paging['offset']])
        );

        return ['rows' => $rows, 'pagination' => $paging];
    }

    // ── Related records ───────────────────────────────────────────────────────

    public function getRepairHistory(int $customerId, int $limit = 0): array
    {
        $sql = "SELECT r.*, CONCAT(s.first_name,' ',s.last_name) AS technician_name
                FROM repairs r
                LEFT JOIN staff s ON s.staff_id = r.staff_id
                WHERE r.customer_id = ?
                ORDER BY r.date_in DESC";
        if ($limit) $sql .= " LIMIT {$limit}";
        return $this->db->fetchAll($sql, [$customerId]);
    }

    public function getInvoices(int $customerId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM invoices WHERE customer_id = ? ORDER BY invoice_date DESC",
            [$customerId]
        );
    }

    public function getStats(int $customerId): array
    {
        return $this->db->fetchOne(
            "SELECT
                COUNT(r.repair_id)                               AS total_repairs,
                SUM(r.status = 'completed')                      AS completed_repairs,
                SUM(r.status = 'collected')                      AS collected_repairs,
                COALESCE(SUM(i.total_amount),0)                  AS total_billed,
                COALESCE(SUM(i.amount_paid),0)                   AS total_paid
             FROM customers c
             LEFT JOIN repairs  r ON r.customer_id = c.customer_id
             LEFT JOIN invoices i ON i.customer_id = c.customer_id
             WHERE c.customer_id = ?
             GROUP BY c.customer_id",
            [$customerId]
        ) ?? ['total_repairs' => 0, 'completed_repairs' => 0, 'collected_repairs' => 0, 'total_billed' => 0, 'total_paid' => 0];
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
}
