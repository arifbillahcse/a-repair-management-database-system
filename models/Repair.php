<?php
class Repair extends BaseModel
{
    protected string $table      = 'repairs';
    protected string $primaryKey = 'repair_id';

    public function __construct()
    {
        parent::__construct();
        $this->ensureColumns();
    }

    private function ensureColumns(): void
    {
        $pdo = $this->db->getPdo();
        $existing = array_column(
            $pdo->query("SHOW COLUMNS FROM `repairs`")->fetchAll(PDO::FETCH_ASSOC),
            'Field'
        );
        $cols = [
            'device_brand'     => "VARCHAR(100) DEFAULT NULL AFTER `device_model`",
            'device_condition' => "TEXT DEFAULT NULL AFTER `device_serial_number`",
            'device_password'  => "VARCHAR(100) DEFAULT NULL AFTER `device_condition`",
            'priority'         => "VARCHAR(10) NOT NULL DEFAULT 'normal' AFTER `status`",
            'internal_notes'   => "TEXT DEFAULT NULL AFTER `notes`",
            'deposit_paid'     => "DECIMAL(10,2) DEFAULT NULL AFTER `actual_amount`",
        ];
        foreach ($cols as $col => $def) {
            if (!in_array($col, $existing)) {
                $pdo->exec("ALTER TABLE `repairs` ADD COLUMN `{$col}` {$def}");
            }
        }
    }

    // ── Full record with joins ────────────────────────────────────────────────

    public function findById(int $id): ?array
    {
        $repair = $this->db->fetchOne(
            "SELECT r.*,
                    c.full_name     AS customer_name,
                    c.phone_mobile  AS customer_phone,
                    c.phone_landline AS customer_landline,
                    c.email         AS customer_email,
                    c.address       AS customer_address,
                    c.city          AS customer_city,
                    c.province      AS customer_province,
                    c.vat_number    AS customer_vat,
                    CONCAT(s.first_name,' ',s.last_name)  AS technician_name,
                    CONCAT(u.first_name,' ',u.last_name)  AS created_by_name,
                    DATEDIFF(COALESCE(r.date_out, NOW()), r.date_in) AS days_in_lab
             FROM repairs r
             LEFT JOIN customers c ON c.customer_id = r.customer_id
             LEFT JOIN staff s ON s.staff_id = r.staff_id
             LEFT JOIN staff u ON u.staff_id = (
                 SELECT staff_id FROM users WHERE user_id = r.created_by LIMIT 1
             )
             WHERE r.repair_id = ?",
            [$id]
        );

        if ($repair) {
            $repair['photos'] = $this->decodePhotos($repair['photo_path'] ?? '');
        }

        return $repair;
    }

    // ── Filtered, paginated list ──────────────────────────────────────────────

    public function getAll(array $filters = [], int $page = 1): array
    {
        [$where, $params] = $this->buildFilters($filters);

        $total = (int)$this->db->fetchScalar(
            "SELECT COUNT(*) FROM repairs r
             LEFT JOIN customers c ON c.customer_id = r.customer_id
             {$where}",
            $params
        );

        $paging  = Utils::paginate($total, $page, PAGE_SIZE_REPAIRS);
        $orderBy = $this->sanitizeOrderBy($filters['order_by'] ?? 'r.date_in DESC');

        $rows = $this->db->fetchAll(
            "SELECT r.*,
                    c.full_name    AS customer_name,
                    c.phone_mobile AS customer_phone,
                    c.client_type  AS customer_type,
                    CONCAT(s.first_name,' ',s.last_name) AS technician_name,
                    DATEDIFF(COALESCE(r.date_out, NOW()), r.date_in) AS days_in_lab
             FROM repairs r
             LEFT JOIN customers c ON c.customer_id = r.customer_id
             LEFT JOIN staff s ON s.staff_id = r.staff_id
             {$where}
             ORDER BY {$orderBy}
             LIMIT ? OFFSET ?",
            array_merge($params, [$paging['perPage'], $paging['offset']])
        );

        return ['rows' => $rows, 'pagination' => $paging];
    }

    // ── Status ────────────────────────────────────────────────────────────────

    public function updateStatus(int $id, string $newStatus): int
    {
        $data = ['status' => $newStatus];

        if (in_array($newStatus, ['completed', 'ready_for_pickup'], true)) {
            $data['date_out'] = date('Y-m-d H:i:s');
        }

        return $this->update($id, $data);
    }

    // ── Photo management (stored as JSON in photo_path) ───────────────────────

    public function getPhotos(int $repairId): array
    {
        $row = $this->db->fetchOne(
            "SELECT photo_path FROM repairs WHERE repair_id = ?", [$repairId]
        );
        return $row ? $this->decodePhotos($row['photo_path'] ?? '') : [];
    }

    public function addPhoto(int $repairId, string $relativePath): void
    {
        $photos   = $this->getPhotos($repairId);
        $photos[] = $relativePath;
        $this->db->update('repairs', ['photo_path' => json_encode($photos)], 'repair_id = ?', [$repairId]);
    }

    public function removePhoto(int $repairId, string $relativePath): void
    {
        $photos = array_values(array_filter(
            $this->getPhotos($repairId),
            fn($p) => $p !== $relativePath
        ));
        $this->db->update('repairs', ['photo_path' => json_encode($photos)], 'repair_id = ?', [$repairId]);
    }

    private function decodePhotos(string $raw): array
    {
        if (empty($raw)) return [];
        // Handle legacy single-path strings (not JSON)
        if (!str_starts_with(trim($raw), '[')) {
            return [$raw];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    // ── Dashboard / stats ─────────────────────────────────────────────────────

    public function getStatistics(): array
    {
        return $this->db->fetchOne(
            "SELECT
                COUNT(*)                                                              AS total,
                SUM(status = 'in_progress')                                          AS in_progress,
                SUM(status = 'ready_for_pickup')                                     AS ready_for_pickup,
                SUM(status = 'on_hold')                                              AS on_hold,
                SUM(status = 'waiting_for_parts')                                    AS waiting_for_parts,
                SUM(status IN ('completed','collected'))                              AS completed,
                SUM(MONTH(date_in) = MONTH(NOW()) AND YEAR(date_in) = YEAR(NOW()))   AS this_month,
                ROUND(AVG(DATEDIFF(COALESCE(date_out,NOW()), date_in)), 1)           AS avg_days
             FROM repairs"
        ) ?? [];
    }

    public function getStatusCounts(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT status, COUNT(*) AS cnt FROM repairs GROUP BY status"
        );
        $map = [];
        foreach ($rows as $r) { $map[$r['status']] = (int)$r['cnt']; }
        return $map;
    }

    public function getReadyForPickup(): array
    {
        return $this->db->fetchAll(
            "SELECT r.repair_id, r.qr_code, r.device_model, r.collection_date,
                    c.full_name AS customer_name, c.phone_mobile
             FROM repairs r
             LEFT JOIN customers c ON c.customer_id = r.customer_id
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
             LEFT JOIN customers c ON c.customer_id = r.customer_id
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
             LEFT JOIN customers c ON c.customer_id = r.customer_id
             ORDER BY r.created_at DESC LIMIT ?",
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

    // ── Autocomplete search (repairs page live search) ────────────────────────

    public function autocomplete(string $q, int $limit = 12): array
    {
        $like = '%' . $q . '%';
        return $this->db->fetchAll(
            "SELECT r.repair_id,
                    CONCAT(COALESCE(c.full_name, 'No client'), ' · ', r.status)    AS label,
                    CONCAT('#', r.repair_id, ' — ', COALESCE(r.device_model, '?')) AS meta
             FROM repairs r
             LEFT JOIN customers c ON c.customer_id = r.customer_id
             WHERE c.full_name LIKE ?
                OR c.phone_mobile LIKE ?
                OR c.phone_landline LIKE ?
                OR r.device_model LIKE ?
                OR r.device_serial_number LIKE ?
                OR CAST(r.repair_id AS CHAR) LIKE ?
             ORDER BY r.date_in DESC
             LIMIT ?",
            [$like, $like, $like, $like, $like, $like, $limit]
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
            "SELECT * FROM repairs WHERE qr_code = ? LIMIT 1", [$code]
        );
    }

    // ── Revenue ───────────────────────────────────────────────────────────────

    public function getMonthlyRevenue(int $months = 12): array
    {
        return $this->db->fetchAll(
            "SELECT DATE_FORMAT(i.invoice_date,'%Y-%m') AS month,
                    SUM(i.total_amount) AS revenue,
                    SUM(i.amount_paid)  AS paid
             FROM invoices i
             WHERE i.invoice_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
               AND i.status != 'cancelled'
             GROUP BY month ORDER BY month",
            [$months]
        );
    }

    // ── Private helpers ───────────────────────────────────────────────────────

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
        if (!empty($filters['client_type']) && in_array($filters['client_type'], ['individual','company','colleague'], true)) {
            $clauses[] = 'c.client_type = ?';
            $params[]  = $filters['client_type'];
        }
        if (!empty($filters['search'])) {
            $like      = '%' . $filters['search'] . '%';
            $clauses[] = '(c.full_name LIKE ? OR c.phone_mobile LIKE ? OR c.phone_landline LIKE ? OR r.device_model LIKE ? OR r.device_serial_number LIKE ? OR r.qr_code LIKE ? OR r.problem_description LIKE ?)';
            array_push($params, $like, $like, $like, $like, $like, $like, $like);
        }

        $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';
        return [$where, $params];
    }

    private function sanitizeOrderBy(string $raw): string
    {
        $allowed = [
            'r.repair_id DESC', 'r.repair_id ASC',
            'r.date_in DESC', 'r.date_in ASC',
            'r.created_at DESC', 'r.created_at ASC',
            'days_in_lab DESC', 'days_in_lab ASC',
            'c.full_name ASC', 'c.full_name DESC',
            'r.status ASC',
        ];
        return in_array($raw, $allowed, true) ? $raw : 'r.repair_id DESC';
    }
}
