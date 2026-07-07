<?php
/**
 * Sale — direct product sales (not tied to a repair).
 *
 * Tables (sales, sale_items) are created on demand via ensureSchema()
 * so no manual migration step is required.
 */
class Sale extends BaseModel
{
    protected string $table      = 'sales';
    protected string $primaryKey = 'sale_id';

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    // ── Schema bootstrap ──────────────────────────────────────────────────────

    public function ensureSchema(): void
    {
        $pdo = $this->db->getPdo();

        if (!$pdo->query("SHOW TABLES LIKE 'sales'")->fetch()) {
            $pdo->exec(
                "CREATE TABLE `sales` (
                    `sale_id`        INT UNSIGNED  NOT NULL AUTO_INCREMENT,
                    `sale_number`    VARCHAR(30)   NOT NULL,
                    `customer_id`    INT UNSIGNED           DEFAULT NULL,
                    `customer_name`  VARCHAR(200)  NOT NULL DEFAULT '',
                    `sale_date`      DATE          NOT NULL,
                    `subtotal`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `tax_percentage` DECIMAL(5,2)  NOT NULL DEFAULT 22.00,
                    `tax_amount`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `total_amount`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `amount_paid`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `status`         ENUM('unpaid','partial','paid','cancelled')
                                                   NOT NULL DEFAULT 'unpaid',
                    `notes`          TEXT                   DEFAULT NULL,
                    `created_by`     INT UNSIGNED           DEFAULT NULL,
                    `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`sale_id`),
                    UNIQUE KEY `uq_sales_number` (`sale_number`),
                    KEY `idx_sales_customer` (`customer_id`),
                    KEY `idx_sales_date`     (`sale_date`),
                    KEY `idx_sales_status`   (`status`),
                    CONSTRAINT `fk_sales_customer`
                        FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`)
                        ON DELETE SET NULL ON UPDATE CASCADE,
                    CONSTRAINT `fk_sales_created_by`
                        FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
                        ON DELETE SET NULL ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        }

        if (!$pdo->query("SHOW TABLES LIKE 'sale_items'")->fetch()) {
            $pdo->exec(
                "CREATE TABLE `sale_items` (
                    `sale_item_id` INT UNSIGNED  NOT NULL AUTO_INCREMENT,
                    `sale_id`      INT UNSIGNED  NOT NULL,
                    `product_id`   INT UNSIGNED           DEFAULT NULL,
                    `description`  VARCHAR(500)  NOT NULL,
                    `quantity`     DECIMAL(10,3) NOT NULL DEFAULT 1.000,
                    `unit_price`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `discount_pct` DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
                    `line_total`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`sale_item_id`),
                    KEY `idx_sale_items_sale`    (`sale_id`),
                    KEY `idx_sale_items_product` (`product_id`),
                    CONSTRAINT `fk_sale_items_sale`
                        FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`)
                        ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `fk_sale_items_product`
                        FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
                        ON DELETE SET NULL ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        }
    }

    // ── Number generation ─────────────────────────────────────────────────────

    public function generateSaleNumber(): string
    {
        $year = date('Y');
        $max  = (int)$this->db->fetchScalar(
            "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(sale_number, '-', -1) AS UNSIGNED)), 0)
             FROM sales WHERE sale_number LIKE ?",
            ['S-' . $year . '-%']
        );
        return 'S-' . $year . '-' . str_pad((string)($max + 1), 5, '0', STR_PAD_LEFT);
    }

    // ── Read ──────────────────────────────────────────────────────────────────

    public function findById(int $id): ?array
    {
        $sale = $this->db->fetchOne(
            "SELECT s.*, c.full_name AS linked_customer_name, c.phone_mobile AS customer_phone,
                    c.email AS customer_email, c.address AS customer_address, c.vat_number AS customer_vat
             FROM sales s
             LEFT JOIN customers c ON c.customer_id = s.customer_id
             WHERE s.sale_id = ?",
            [$id]
        );

        if ($sale) {
            $sale['items'] = $this->getItems($id);
        }
        return $sale;
    }

    public function getAll(array $filters = [], int $page = 1): array
    {
        [$where, $params] = $this->buildFilters($filters);

        $total = (int)$this->db->fetchScalar(
            "SELECT COUNT(*) FROM sales s
             LEFT JOIN customers c ON c.customer_id = s.customer_id
             {$where}",
            $params
        );
        $paging = Utils::paginate($total, $page);

        $rows = $this->db->fetchAll(
            "SELECT s.*, c.full_name AS linked_customer_name,
                    (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.sale_id) AS item_count
             FROM sales s
             LEFT JOIN customers c ON c.customer_id = s.customer_id
             {$where}
             ORDER BY s.sale_date DESC, s.sale_id DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$paging['perPage'], $paging['offset']])
        );

        return ['rows' => $rows, 'pagination' => $paging];
    }

    // ── Items ─────────────────────────────────────────────────────────────────

    public function getItems(int $saleId): array
    {
        return $this->db->fetchAll(
            "SELECT si.*, p.name AS product_name, p.sku
             FROM sale_items si
             LEFT JOIN products p ON p.product_id = si.product_id
             WHERE si.sale_id = ?
             ORDER BY si.sale_item_id",
            [$saleId]
        );
    }

    public function deleteItems(int $saleId): void
    {
        $this->db->delete('sale_items', 'sale_id = ?', [$saleId]);
    }

    public function addItem(int $saleId, array $item): int
    {
        $item['sale_id']    = $saleId;
        $item['line_total'] = round(
            $item['quantity'] * $item['unit_price'] * (1 - ($item['discount_pct'] ?? 0) / 100),
            2
        );
        return $this->db->insert('sale_items', $item);
    }

    // ── Totals / payment ──────────────────────────────────────────────────────

    public function recalculateTotals(int $saleId): void
    {
        $items  = $this->getItems($saleId);
        $sale   = $this->db->fetchOne("SELECT tax_percentage FROM sales WHERE sale_id = ?", [$saleId]);
        $taxPct = (float)($sale['tax_percentage'] ?? DEFAULT_TAX_PCT);

        $subtotal = array_sum(array_column($items, 'line_total'));
        $taxAmt   = round($subtotal * $taxPct / 100, 2);

        $this->db->update('sales', [
            'subtotal'     => $subtotal,
            'tax_amount'   => $taxAmt,
            'total_amount' => round($subtotal + $taxAmt, 2),
        ], 'sale_id = ?', [$saleId]);
    }

    public function markAsPaid(int $saleId, float $amount): int
    {
        $sale   = $this->db->fetchOne("SELECT total_amount FROM sales WHERE sale_id = ?", [$saleId]);
        $status = abs($amount - (float)($sale['total_amount'] ?? 0)) < 0.01 || $amount > (float)($sale['total_amount'] ?? 0)
            ? 'paid'
            : 'partial';

        return $this->update($saleId, ['status' => $status, 'amount_paid' => $amount]);
    }

    // ── Stats ─────────────────────────────────────────────────────────────────

    public function getMonthlyStats(): array
    {
        $month = $this->db->fetchOne(
            "SELECT COALESCE(SUM(total_amount), 0) AS total_revenue,
                    COALESCE(SUM(amount_paid),  0) AS total_paid,
                    COUNT(*)                       AS sale_count
             FROM sales
             WHERE MONTH(sale_date) = MONTH(NOW())
               AND YEAR(sale_date)  = YEAR(NOW())
               AND status != 'cancelled'"
        ) ?? [];

        $month['today_revenue'] = (float)$this->db->fetchScalar(
            "SELECT COALESCE(SUM(total_amount), 0) FROM sales
             WHERE sale_date = CURDATE() AND status != 'cancelled'"
        );

        return $month;
    }

    /** Sales aggregated within a range (for reports). */
    public function getRangeStats(string $dateFrom, string $dateTo): array
    {
        return $this->db->fetchOne(
            "SELECT COALESCE(SUM(total_amount), 0) AS total_revenue,
                    COALESCE(SUM(amount_paid),  0) AS total_paid,
                    COUNT(*)                       AS sale_count
             FROM sales
             WHERE sale_date BETWEEN ? AND ? AND status != 'cancelled'",
            [$dateFrom, $dateTo]
        ) ?? [];
    }

    /**
     * Every sale within a date range (unpaginated), newest first, for the
     * dedicated sales report. Optionally filtered by status.
     */
    public function getForReport(string $dateFrom, string $dateTo, string $status = ''): array
    {
        $sql = "SELECT s.*, c.full_name AS linked_customer_name,
                       (SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.sale_id) AS item_count
                FROM sales s
                LEFT JOIN customers c ON c.customer_id = s.customer_id
                WHERE s.sale_date BETWEEN ? AND ?";
        $params = [$dateFrom, $dateTo];

        if ($status !== '' && array_key_exists($status, SALE_STATUS)) {
            $sql        .= " AND s.status = ?";
            $params[]    = $status;
        }
        $sql .= " ORDER BY s.sale_date DESC, s.sale_id DESC";

        return $this->db->fetchAll($sql, $params);
    }

    /** Full totals (incl. balance & item count) for a set of sales in a range. */
    public function getReportSummary(string $dateFrom, string $dateTo, string $status = ''): array
    {
        $sql = "SELECT COUNT(*)                                    AS sale_count,
                       COALESCE(SUM(subtotal), 0)                  AS subtotal,
                       COALESCE(SUM(tax_amount), 0)                AS tax_amount,
                       COALESCE(SUM(total_amount), 0)              AS total_amount,
                       COALESCE(SUM(amount_paid), 0)               AS total_paid,
                       COALESCE(SUM(total_amount - amount_paid),0) AS balance
                FROM sales
                WHERE sale_date BETWEEN ? AND ?";
        $params = [$dateFrom, $dateTo];

        if ($status !== '' && array_key_exists($status, SALE_STATUS)) {
            $sql     .= " AND status = ?";
            $params[] = $status;
        } else {
            // Default report excludes cancelled sales from the money totals
            $sql .= " AND status != 'cancelled'";
        }

        return $this->db->fetchOne($sql, $params) ?? [];
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function buildFilters(array $filters): array
    {
        $clauses = [];
        $params  = [];

        if (!empty($filters['status'])) {
            $clauses[] = 's.status = ?';
            $params[]  = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $clauses[] = 's.sale_date >= ?';
            $params[]  = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $clauses[] = 's.sale_date <= ?';
            $params[]  = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $like      = '%' . $filters['search'] . '%';
            $clauses[] = '(s.sale_number LIKE ? OR s.customer_name LIKE ? OR c.full_name LIKE ?)';
            array_push($params, $like, $like, $like);
        }

        $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';
        return [$where, $params];
    }
}
