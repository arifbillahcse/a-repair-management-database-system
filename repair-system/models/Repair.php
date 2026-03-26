<?php
class Repair extends BaseModel
{
    protected string $table      = 'repairs';
    protected string $primaryKey = 'repair_id';

    // ── Full record with joins ────────────────────────────────────────────────

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT r.*,
                    c.full_name AS customer_name, c.phone_mobile AS customer_phone,
                    c.email AS customer_email,
                    CONCAT(s.first_name,' ',s.last_name) AS technician_name,
                    CONCAT(u.first_name,' ',u.last_name) AS created_by_name
             FROM repairs r
             JOIN customers c ON c.customer_id = r.customer_id
             LEFT JOIN staff s ON s.staff_id = r.staff_id
             LEFT JOIN staff u ON u.staff_id = (SELECT staff_id FROM users WHERE user_id = r.created_by LIMIT 1)
             WHERE r.repair_id = ?",
            [$id]
        );
    }

    // ── Filtered, paginated list ──────────────────────────────────────────────

    public function getAll(array $filters = [], int $page = 1): array
    {
        [$where, $params] = $this->buildFilters($filters);

        $total  = (int)$this->db->fetchScalar(
            "SELECT COUNT(*) FROM repairs r
             JOIN customers c ON c.customer_id = r.customer_id
             {$where}",
            $params
        );

        $paging = Utils::paginate($total, $page, PAGE_SIZE_REPAIRS);

        $orderBy = $filters['order_by'] ?? 'r.date_in DESC';

        $rows = $this->db->fetchAll(
            "SELECT r.*,
                    c.full_name AS customer_name, c.phone_mobile AS customer_phone,
                    CONCAT(s.first_name,' ',s.last_name) AS technician_name,
                    DATEDIFF(COALESCE(r.date_out, NOW()), r.date_in) AS days_in_lab
             FROM repairs r
             JOIN customers c ON c.customer_id = r.customer_id
             LEFT JOIN staff s ON s.staff_id = r.staff_id
             {$where}
             ORDER BY {$orderBy}
             LIMIT ? OFFSET ?",
            array_merge($params, [$paging['perPage'], $paging['offset']])
        );

        return ['rows' => $rows, 'pagination' => $paging];
    }

    // ── Status operations ─────────────────────────────────────────────────────

    public function updateStatus(int $id, string $newStatus): int
    {
        $data = ['status' => $newStatus];

        // Auto-set date_out when repair is completed
        if (in_array($newStatus, ['completed', 'collected'], true)) {
            $data['date_out'] = date('Y-m-d H:i:s');
        }

        return $this->update($id, $data);
    }

    // ── Queries for dashboard ─────────────────────────────────────────────────

    public function getStatistics(): array
    {
        return $this->db->fetchOne(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'in_progress')       AS in_progress,
                SUM(status = 'ready_for_pickup')  AS ready_for_pickup,
                SUM(status = 'on_hold')           AS on_hold,
                SUM(status = 'waiting_for_parts') AS waiting_for_parts,
                SUM(status = 'completed' OR status = 'collected') AS completed,
                SUM(MONTH(date_in) = MONTH(NOW()) AND YEAR(date_in) = YEAR(NOW())) AS this_month,
                AVG(DATEDIFF(COALESCE(date_out, NOW()), date_in)) AS avg_days
             FROM repairs"
        ) ?? [];
    }

    public function getReadyForPickup(): array
    {
        return $this->db->fetchAll(
            "SELECT r.repair_id, r.qr_code, r.device_model, r.collection_date,
                    c.full_name AS customer_name, c.phone_mobile
             FROM repairs r
             JOIN customers c ON c.customer_id = r.customer_id
             WHERE r.status = 'ready_for_pickup'
             ORDER BY r.collection_date"
        );
    }

    public function getOverduePickups(int $daysThreshold = 7): array
    {
        return $this->db->fetchAll(
            "SELECT r.repair_id, r.qr_code, r.device_model, r.date_out,
                    c.full_name AS customer_name, c.phone_mobile,
                    DATEDIFF(NOW(), r.date_out) AS days_waiting
             FROM repairs r
             JOIN customers c ON c.customer_id = r.customer_id
             WHERE r.status = 'ready_for_pickup'
               AND r.date_out IS NOT NULL
               AND DATEDIFF(NOW(), r.date_out) > ?
             ORDER BY days_waiting DESC",
            [$daysThreshold]
        );
    }

    public function getRecentRepairs(int $limit = 10): array
    {
        return $this->db->fetchAll(
            "SELECT r.repair_id, r.device_model, r.status, r.date_in,
                    c.full_name AS customer_name
             FROM repairs r
             JOIN customers c ON c.customer_id = r.customer_id
             ORDER BY r.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    public function getByCustomerId(int $customerId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM repairs WHERE customer_id = ? ORDER BY date_in DESC",
            [$customerId]
        );
    }

    // ── QR / numbering ────────────────────────────────────────────────────────

    public function generateQRCode(int $repairId): string
    {
        return QRCode::generateRepairCode($repairId);
    }

    public function findByQRCode(string $code): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM repairs WHERE qr_code = ? LIMIT 1",
            [$code]
        );
    }

    // ── Revenue stats ─────────────────────────────────────────────────────────

    public function getMonthlyRevenue(int $months = 12): array
    {
        return $this->db->fetchAll(
            "SELECT DATE_FORMAT(i.invoice_date, '%Y-%m') AS month,
                    SUM(i.total_amount)                  AS revenue,
                    SUM(i.amount_paid)                   AS paid
             FROM invoices i
             WHERE i.invoice_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
               AND i.status != 'cancelled'
             GROUP BY month
             ORDER BY month",
            [$months]
        );
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function buildFilters(array $filters): array
    {
        $clauses = [];
        $params  = [];

        if (!empty($filters['status'])) {
            $clauses[] = 'r.status = ?';
            $params[]  = $filters['status'];
        }
        if (!empty($filters['staff_id'])) {
            $clauses[] = 'r.staff_id = ?';
            $params[]  = (int)$filters['staff_id'];
        }
        if (!empty($filters['customer_id'])) {
            $clauses[] = 'r.customer_id = ?';
            $params[]  = (int)$filters['customer_id'];
        }
        if (!empty($filters['date_from'])) {
            $clauses[] = 'DATE(r.date_in) >= ?';
            $params[]  = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $clauses[] = 'DATE(r.date_in) <= ?';
            $params[]  = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $like      = '%' . $filters['search'] . '%';
            $clauses[] = '(c.full_name LIKE ? OR r.device_model LIKE ? OR r.device_serial_number LIKE ? OR r.qr_code LIKE ?)';
            array_push($params, $like, $like, $like, $like);
        }

        $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';
        return [$where, $params];
    }
}
