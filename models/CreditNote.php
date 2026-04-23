<?php
class CreditNote extends BaseModel
{
    protected string $table      = 'credit_notes';
    protected string $primaryKey = 'cn_id';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTables();
    }

    private function ensureTables(): void
    {
        $pdo = $this->db->getPdo();
        $pdo->exec("CREATE TABLE IF NOT EXISTS `credit_notes` (
            `cn_id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            `cn_number`        INT UNSIGNED    NOT NULL,
            `cn_date`          DATE            NOT NULL,
            `company_name`     VARCHAR(200)    NOT NULL DEFAULT '',
            `company_address`  VARCHAR(500)    NOT NULL DEFAULT '',
            `company_phone`    VARCHAR(50)     NOT NULL DEFAULT '',
            `company_email`    VARCHAR(150)    NOT NULL DEFAULT '',
            `company_vat`      VARCHAR(50)     NOT NULL DEFAULT '',
            `customer_name`    VARCHAR(200)    NOT NULL DEFAULT '',
            `customer_address` VARCHAR(500)    NOT NULL DEFAULT '',
            `customer_vat`     VARCHAR(50)     NOT NULL DEFAULT '',
            `note`             TEXT                     DEFAULT NULL,
            `created_by`       INT UNSIGNED             DEFAULT NULL,
            `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`cn_id`),
            UNIQUE KEY `uk_cn_number` (`cn_number`),
            KEY `idx_cn_date` (`cn_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `credit_note_items` (
            `item_id`      INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            `cn_id`        INT UNSIGNED    NOT NULL,
            `description`  TEXT            NOT NULL,
            `basic_amount` DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
            `vat_amount`   DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
            `net_amount`   DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
            `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`item_id`),
            KEY `idx_cni_cn_id` (`cn_id`),
            CONSTRAINT `fk_cni_cn`
                FOREIGN KEY (`cn_id`) REFERENCES `credit_notes` (`cn_id`)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Add missing columns to existing tables
        $existing = array_column(
            $pdo->query("SHOW COLUMNS FROM `credit_notes`")->fetchAll(PDO::FETCH_ASSOC),
            'Field'
        );
        $newCols = [
            'company_name'    => "VARCHAR(200) NOT NULL DEFAULT '' AFTER `cn_date`",
            'company_address' => "VARCHAR(500) NOT NULL DEFAULT '' AFTER `company_name`",
            'company_phone'   => "VARCHAR(50)  NOT NULL DEFAULT '' AFTER `company_address`",
            'company_email'   => "VARCHAR(150) NOT NULL DEFAULT '' AFTER `company_phone`",
            'company_vat'     => "VARCHAR(50)  NOT NULL DEFAULT '' AFTER `company_email`",
            'invoice_number'  => "VARCHAR(100) NOT NULL DEFAULT '' AFTER `customer_vat`",
            'invoice_date'    => "DATE DEFAULT NULL AFTER `invoice_number`",
            'signature_id'    => "TINYINT NOT NULL DEFAULT 0 AFTER `note`",
        ];
        foreach ($newCols as $col => $def) {
            if (!in_array($col, $existing)) {
                $pdo->exec("ALTER TABLE `credit_notes` ADD COLUMN `{$col}` {$def}");
            }
        }
    }

    public function findById(int $id): ?array
    {
        $cn = $this->db->fetchOne(
            "SELECT cn.* FROM credit_notes cn WHERE cn.cn_id = ?",
            [$id]
        );
        if ($cn) {
            $cn['items']       = $this->getItems($id);
            $cn['total_basic'] = array_sum(array_column($cn['items'], 'basic_amount'));
            $cn['total_vat']   = array_sum(array_column($cn['items'], 'vat_amount'));
            $cn['total_net']   = array_sum(array_column($cn['items'], 'net_amount'));
        }
        return $cn;
    }

    public function getAll(array $filters = [], int $page = 1): array
    {
        [$where, $params] = $this->buildFilters($filters);

        $total = (int)$this->db->fetchScalar(
            "SELECT COUNT(*) FROM credit_notes cn {$where}",
            $params
        );

        $paging = Utils::paginate($total, $page, PAGE_SIZE);

        $rows = $this->db->fetchAll(
            "SELECT cn.*,
                    COALESCE(SUM(i.basic_amount), 0) AS total_basic,
                    COALESCE(SUM(i.vat_amount),   0) AS total_vat,
                    COALESCE(SUM(i.net_amount),   0) AS total_net,
                    (SELECT description FROM credit_note_items
                     WHERE cn_id = cn.cn_id ORDER BY item_id LIMIT 1) AS first_desc
             FROM credit_notes cn
             LEFT JOIN credit_note_items i ON i.cn_id = cn.cn_id
             {$where}
             GROUP BY cn.cn_id
             ORDER BY cn.cn_number DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$paging['perPage'], $paging['offset']])
        );

        return ['rows' => $rows, 'pagination' => $paging];
    }

    public function getItems(int $cnId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM credit_note_items WHERE cn_id = ? ORDER BY item_id",
            [$cnId]
        );
    }

    public function addItem(int $cnId, array $item): int
    {
        $basic = round((float)($item['basic_amount'] ?? 0), 2);
        $vat   = round((float)($item['vat_amount']   ?? 0), 2);
        return $this->db->insert('credit_note_items', [
            'cn_id'        => $cnId,
            'description'  => Utils::sanitize($item['description'] ?? ''),
            'basic_amount' => $basic,
            'vat_amount'   => $vat,
            'net_amount'   => round($basic + $vat, 2),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    public function deleteItems(int $cnId): void
    {
        $this->db->delete('credit_note_items', 'cn_id = ?', [$cnId]);
    }

    public function getNextNumber(): int
    {
        $max = $this->db->fetchScalar("SELECT MAX(cn_number) FROM credit_notes");
        return ($max ? (int)$max : 0) + 1;
    }

    private function buildFilters(array $filters): array
    {
        $clauses = [];
        $params  = [];

        if (!empty($filters['search'])) {
            $like      = '%' . $filters['search'] . '%';
            $clauses[] = '(cn.customer_name LIKE ? OR cn.customer_vat LIKE ? OR CAST(cn.cn_number AS CHAR) LIKE ?)';
            array_push($params, $like, $like, $like);
        }
        if (!empty($filters['date_from'])) {
            $clauses[] = 'cn.cn_date >= ?';
            $params[]  = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $clauses[] = 'cn.cn_date <= ?';
            $params[]  = $filters['date_to'];
        }

        $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';
        return [$where, $params];
    }
}
