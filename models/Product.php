<?php
/**
 * Product — catalog + stock management.
 *
 * The products table ships with schema.sql; ensureSchema() adds the
 * stock-related tables (product_categories, stock_movements) and the
 * low_stock_threshold column on demand, so no manual migration is needed.
 */
class Product extends BaseModel
{
    protected string $table      = 'products';
    protected string $primaryKey = 'product_id';

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    // ── Schema bootstrap ──────────────────────────────────────────────────────

    public function ensureSchema(): void
    {
        $pdo = $this->db->getPdo();

        if (!$pdo->query("SHOW TABLES LIKE 'product_categories'")->fetch()) {
            $pdo->exec(
                "CREATE TABLE `product_categories` (
                    `category_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `name`        VARCHAR(100) NOT NULL,
                    `sort_order`  INT          NOT NULL DEFAULT 0,
                    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`category_id`),
                    UNIQUE KEY `uq_pc_name` (`name`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        } else {
            // Sites that auto-created this table before updated_at was added
            $pcCols = array_column(
                $pdo->query("SHOW COLUMNS FROM `product_categories`")->fetchAll(PDO::FETCH_ASSOC),
                'Field'
            );
            if (!in_array('updated_at', $pcCols, true)) {
                $pdo->exec("ALTER TABLE `product_categories` ADD COLUMN `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`");
            }
        }

        if (!$pdo->query("SHOW TABLES LIKE 'stock_movements'")->fetch()) {
            $pdo->exec(
                "CREATE TABLE `stock_movements` (
                    `movement_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `product_id`  INT UNSIGNED NOT NULL,
                    `change_qty`  INT          NOT NULL,
                    `reason`      ENUM('received','sold','returned','damaged','correction')
                                               NOT NULL DEFAULT 'correction',
                    `note`        VARCHAR(255) NOT NULL DEFAULT '',
                    `created_by`  INT UNSIGNED          DEFAULT NULL,
                    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`movement_id`),
                    KEY `idx_sm_product` (`product_id`),
                    KEY `idx_sm_created` (`created_at`),
                    CONSTRAINT `fk_sm_product`
                        FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
                        ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        }

        $cols = array_column(
            $pdo->query("SHOW COLUMNS FROM `products`")->fetchAll(PDO::FETCH_ASSOC),
            'Field'
        );
        if (!in_array('low_stock_threshold', $cols, true)) {
            $pdo->exec("ALTER TABLE `products` ADD COLUMN `low_stock_threshold` INT NOT NULL DEFAULT 0 AFTER `quantity_on_hand`");
        }
    }

    // ── List ──────────────────────────────────────────────────────────────────

    public function getAll(array $filters = [], int $page = 1): array
    {
        [$where, $params] = $this->buildFilters($filters);

        $total = (int)$this->db->fetchScalar(
            "SELECT COUNT(*) FROM products p {$where}", $params
        );
        $paging = Utils::paginate($total, $page);

        $rows = $this->db->fetchAll(
            "SELECT p.*, pc.name AS category_name
             FROM products p
             LEFT JOIN product_categories pc ON pc.category_id = p.category_id
             {$where}
             ORDER BY p.name ASC
             LIMIT ? OFFSET ?",
            array_merge($params, [$paging['perPage'], $paging['offset']])
        );

        return ['rows' => $rows, 'pagination' => $paging];
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT p.*, pc.name AS category_name
             FROM products p
             LEFT JOIN product_categories pc ON pc.category_id = p.category_id
             WHERE p.product_id = ?",
            [$id]
        );
    }

    /** All active products for pickers (sale form). */
    public function allActive(): array
    {
        return $this->db->fetchAll(
            "SELECT product_id, sku, name, selling_price, quantity_on_hand
             FROM products WHERE is_active = 1 ORDER BY name ASC"
        );
    }

    // ── Stock ─────────────────────────────────────────────────────────────────

    /**
     * Change stock by $change (positive or negative) and record the movement.
     * Returns false if the change would take the stock below zero.
     */
    public function adjustStock(int $productId, int $change, string $reason, string $note = '', ?int $userId = null): bool
    {
        if ($change === 0) {
            return true;
        }

        return (bool)$this->db->transaction(function (Database $db) use ($productId, $change, $reason, $note, $userId) {
            $current = (int)$db->fetchScalar(
                "SELECT quantity_on_hand FROM products WHERE product_id = ? FOR UPDATE",
                [$productId]
            );
            if ($current + $change < 0) {
                return false;
            }

            $db->update('products',
                ['quantity_on_hand' => $current + $change],
                'product_id = ?', [$productId]
            );
            $db->insert('stock_movements', [
                'product_id' => $productId,
                'change_qty' => $change,
                'reason'     => array_key_exists($reason, STOCK_REASONS) ? $reason : 'correction',
                'note'       => mb_substr($note, 0, 255),
                'created_by' => $userId,
            ]);
            return true;
        });
    }

    /** Movement history for one product, newest first. */
    public function getMovements(int $productId, int $limit = 50): array
    {
        return $this->db->fetchAll(
            "SELECT sm.*, u.username
             FROM stock_movements sm
             LEFT JOIN users u ON u.user_id = sm.created_by
             WHERE sm.product_id = ?
             ORDER BY sm.movement_id DESC
             LIMIT {$limit}",
            [$productId]
        );
    }

    /** Active products at or below their low-stock threshold. */
    public function getLowStock(int $limit = 0): array
    {
        $sql = "SELECT product_id, sku, name, quantity_on_hand, low_stock_threshold
                FROM products
                WHERE is_active = 1
                  AND low_stock_threshold > 0
                  AND quantity_on_hand <= low_stock_threshold
                ORDER BY quantity_on_hand ASC";
        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
        }
        return $this->db->fetchAll($sql);
    }

    /** Aggregate stock KPIs for dashboard / list page. */
    public function getStockStats(): array
    {
        return $this->db->fetchOne(
            "SELECT
                COUNT(*)                                              AS active_products,
                COALESCE(SUM(quantity_on_hand), 0)                    AS total_units,
                COALESCE(SUM(quantity_on_hand * COALESCE(cost_price, 0)), 0)  AS cost_value,
                COALESCE(SUM(quantity_on_hand * selling_price), 0)    AS retail_value,
                SUM((low_stock_threshold > 0 AND quantity_on_hand <= low_stock_threshold)
                    OR quantity_on_hand = 0)                          AS low_stock_count,
                SUM(quantity_on_hand = 0)                             AS out_of_stock_count
             FROM products
             WHERE is_active = 1"
        ) ?? [];
    }

    // ── Reports ───────────────────────────────────────────────────────────────

    /** Best-selling products (by units) within a date range. */
    public function getBestSellers(string $dateFrom, string $dateTo, int $limit = 10): array
    {
        return $this->db->fetchAll(
            "SELECT si.product_id,
                    COALESCE(p.name, si.description) AS name,
                    p.sku,
                    SUM(si.quantity)   AS units_sold,
                    SUM(si.line_total) AS revenue
             FROM sale_items si
             JOIN sales s      ON s.sale_id = si.sale_id
             LEFT JOIN products p ON p.product_id = si.product_id
             WHERE s.status != 'cancelled'
               AND s.sale_date BETWEEN ? AND ?
             GROUP BY si.product_id, COALESCE(p.name, si.description), p.sku
             ORDER BY units_sold DESC
             LIMIT {$limit}",
            [$dateFrom, $dateTo]
        );
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function buildFilters(array $filters): array
    {
        $clauses = [];
        $params  = [];

        // Default: hide inactive unless explicitly requested
        if (empty($filters['include_inactive'])) {
            $clauses[] = 'p.is_active = 1';
        }
        if (!empty($filters['category_id'])) {
            $clauses[] = 'p.category_id = ?';
            $params[]  = (int)$filters['category_id'];
        }
        if (!empty($filters['stock'])) {
            if ($filters['stock'] === 'low') {
                $clauses[] = 'p.low_stock_threshold > 0 AND p.quantity_on_hand <= p.low_stock_threshold';
            } elseif ($filters['stock'] === 'out') {
                $clauses[] = 'p.quantity_on_hand = 0';
            } elseif ($filters['stock'] === 'in') {
                $clauses[] = 'p.quantity_on_hand > 0';
            }
        }
        if (!empty($filters['search'])) {
            $like      = '%' . $filters['search'] . '%';
            $clauses[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)';
            array_push($params, $like, $like, $like);
        }

        $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';
        return [$where, $params];
    }
}
