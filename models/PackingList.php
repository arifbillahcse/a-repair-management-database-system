<?php
/**
 * PackingList — standalone "Documento di Trasporto" (DDT) / packing list.
 *
 * Deliberately NOT linked to customers / repairs / sales. Every field is
 * free-text and typed fresh each time; the only structured input is the
 * company header, which is snapshotted from the businesses table so the
 * document keeps its letterhead even if the business record later changes.
 *
 * Tables are created on demand via ensureSchema() — no manual migration.
 */
class PackingList extends BaseModel
{
    protected string $table      = 'packing_lists';
    protected string $primaryKey = 'pl_id';

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    // ── Schema bootstrap ──────────────────────────────────────────────────────

    public function ensureSchema(): void
    {
        $pdo = $this->db->getPdo();

        $pdo->exec("CREATE TABLE IF NOT EXISTS `packing_lists` (
            `pl_id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
            `pl_number`        INT UNSIGNED  NOT NULL,
            `pl_date`          DATE          NOT NULL,
            `transport_by`     ENUM('cedente','cessionario') NOT NULL DEFAULT 'cedente',
            -- Cedente (sender / company header snapshot)
            `company_name`     VARCHAR(200)  NOT NULL DEFAULT '',
            `company_address`  VARCHAR(500)  NOT NULL DEFAULT '',
            `company_phone`    VARCHAR(50)   NOT NULL DEFAULT '',
            `company_email`    VARCHAR(150)  NOT NULL DEFAULT '',
            `company_vat`      VARCHAR(50)   NOT NULL DEFAULT '',
            `company_tax_id`   VARCHAR(50)   NOT NULL DEFAULT '',
            -- Cessionario (recipient)
            `customer_name`    VARCHAR(200)  NOT NULL DEFAULT '',
            `customer_address` VARCHAR(500)  NOT NULL DEFAULT '',
            `customer_vat`     VARCHAR(50)   NOT NULL DEFAULT '',
            `destination`      VARCHAR(500)  NOT NULL DEFAULT '',
            -- Causale / ordine
            `causale`          VARCHAR(300)  NOT NULL DEFAULT '',
            `order_number`     VARCHAR(100)  NOT NULL DEFAULT '',
            `order_date`       DATE                   DEFAULT NULL,
            `account_type`     ENUM('','in_conto','a_saldo') NOT NULL DEFAULT '',
            -- Aspetto / spedizione
            `aspetto`          VARCHAR(200)  NOT NULL DEFAULT '',
            `n_colli`          VARCHAR(50)   NOT NULL DEFAULT '',
            `peso_kg`          VARCHAR(50)   NOT NULL DEFAULT '',
            `porto`            VARCHAR(100)  NOT NULL DEFAULT '',
            -- Trasporto
            `delivery_by`      ENUM('','cedente','cessionario') NOT NULL DEFAULT '',
            `transport_date`   DATE                   DEFAULT NULL,
            `transport_time`   VARCHAR(20)   NOT NULL DEFAULT '',
            `carrier`          VARCHAR(500)  NOT NULL DEFAULT '',
            `notes`            TEXT                   DEFAULT NULL,
            `created_by`       INT UNSIGNED           DEFAULT NULL,
            `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`pl_id`),
            UNIQUE KEY `uk_pl_number` (`pl_number`),
            KEY `idx_pl_date` (`pl_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `packing_list_items` (
            `item_id`     INT UNSIGNED  NOT NULL AUTO_INCREMENT,
            `pl_id`       INT UNSIGNED  NOT NULL,
            `quantity`    VARCHAR(50)   NOT NULL DEFAULT '',
            `description` TEXT          NOT NULL,
            `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`item_id`),
            KEY `idx_pli_pl_id` (`pl_id`),
            CONSTRAINT `fk_pli_pl`
                FOREIGN KEY (`pl_id`) REFERENCES `packing_lists` (`pl_id`)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    // ── Read ──────────────────────────────────────────────────────────────────

    public function findById(int $id): ?array
    {
        $pl = $this->db->fetchOne(
            "SELECT * FROM packing_lists WHERE pl_id = ?",
            [$id]
        );
        if ($pl) {
            $pl['items'] = $this->getItems($id);
        }
        return $pl;
    }

    public function getAll(array $filters = [], int $page = 1): array
    {
        [$where, $params] = $this->buildFilters($filters);

        $total  = (int)$this->db->fetchScalar(
            "SELECT COUNT(*) FROM packing_lists pl {$where}",
            $params
        );
        $paging = Utils::paginate($total, $page);

        $rows = $this->db->fetchAll(
            "SELECT pl.*,
                    (SELECT COUNT(*) FROM packing_list_items i WHERE i.pl_id = pl.pl_id) AS item_count,
                    (SELECT description FROM packing_list_items
                     WHERE pl_id = pl.pl_id ORDER BY item_id LIMIT 1) AS first_desc
             FROM packing_lists pl
             {$where}
             ORDER BY pl.pl_number DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$paging['perPage'], $paging['offset']])
        );

        return ['rows' => $rows, 'pagination' => $paging];
    }

    // ── Items ─────────────────────────────────────────────────────────────────

    public function getItems(int $plId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM packing_list_items WHERE pl_id = ? ORDER BY item_id",
            [$plId]
        );
    }

    public function addItem(int $plId, array $item): int
    {
        return $this->db->insert('packing_list_items', [
            'pl_id'       => $plId,
            'quantity'    => Utils::sanitize($item['quantity']    ?? ''),
            'description' => Utils::sanitize($item['description'] ?? ''),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    public function deleteItems(int $plId): void
    {
        $this->db->delete('packing_list_items', 'pl_id = ?', [$plId]);
    }

    // ── Numbering ─────────────────────────────────────────────────────────────

    public function getNextNumber(): int
    {
        $max = $this->db->fetchScalar("SELECT MAX(pl_number) FROM packing_lists");
        return ($max ? (int)$max : 0) + 1;
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function buildFilters(array $filters): array
    {
        $clauses = [];
        $params  = [];

        if (!empty($filters['search'])) {
            $like      = '%' . $filters['search'] . '%';
            $clauses[] = '(pl.customer_name LIKE ? OR pl.destination LIKE ? OR CAST(pl.pl_number AS CHAR) LIKE ?)';
            array_push($params, $like, $like, $like);
        }
        if (!empty($filters['date_from'])) {
            $clauses[] = 'pl.pl_date >= ?';
            $params[]  = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $clauses[] = 'pl.pl_date <= ?';
            $params[]  = $filters['date_to'];
        }

        $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';
        return [$where, $params];
    }
}
