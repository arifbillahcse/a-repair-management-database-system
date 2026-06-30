<?php
/**
 * Business — issuing-business profiles.
 *
 * The buyer manages several businesses (company name, address, VAT, etc.)
 * and selects which one issues each invoice / credit note (Option B).
 *
 * The table is created and seeded on demand via ensureSchema() so no manual
 * migration step is required.
 */
class Business extends BaseModel
{
    protected string $table      = 'businesses';
    protected string $primaryKey = 'business_id';

    public function __construct()
    {
        parent::__construct();
        $this->ensureSchema();
    }

    // ── Schema bootstrap ──────────────────────────────────────────────────────

    public function ensureSchema(): void
    {
        $pdo = $this->db->getPdo();

        $exists = $pdo->query("SHOW TABLES LIKE 'businesses'")->fetch();
        if (!$exists) {
            $pdo->exec(
                "CREATE TABLE `businesses` (
                    `business_id`  INT UNSIGNED   NOT NULL AUTO_INCREMENT,
                    `name`         VARCHAR(200)   NOT NULL,
                    `address`      VARCHAR(500)   NOT NULL DEFAULT '',
                    `phone`        VARCHAR(50)    NOT NULL DEFAULT '',
                    `email`        VARCHAR(150)   NOT NULL DEFAULT '',
                    `vat_number`   VARCHAR(50)    NOT NULL DEFAULT '',
                    `tax_id`       VARCHAR(50)    NOT NULL DEFAULT '',
                    `bank_details` VARCHAR(1000)  NOT NULL DEFAULT '',
                    `signature`    VARCHAR(300)   NOT NULL DEFAULT '',
                    `is_default`   TINYINT(1)     NOT NULL DEFAULT 0,
                    `sort_order`   INT            NOT NULL DEFAULT 0,
                    `is_active`    TINYINT(1)     NOT NULL DEFAULT 1,
                    `created_at`   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at`   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`business_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            $this->seed();
        }
    }

    /**
     * Seed initial businesses from the existing single-company settings
     * plus the three signature blocks (which already represent the three
     * businesses the buyer operates).
     */
    private function seed(): void
    {
        $cs = $this->db->fetchOne("SELECT * FROM company_settings LIMIT 1") ?? [];

        $sort = 0;

        // 1. Default business from company_settings
        $defaultName = trim($cs['company_name'] ?? '')
            ?: $this->nameFromSignature($cs['signature1'] ?? '')
            ?: 'My Business';

        $this->db->insert('businesses', [
            'name'         => $defaultName,
            'address'      => $cs['company_address'] ?? '',
            'phone'        => $cs['company_phone']   ?? '',
            'email'        => $cs['company_email']   ?? '',
            'vat_number'   => $cs['vat_number']      ?? '',
            'tax_id'       => $cs['tax_id']          ?? '',
            'bank_details' => '',
            'signature'    => $cs['signature1']      ?? '',
            'is_default'   => 1,
            'sort_order'   => $sort++,
            'is_active'    => 1,
        ]);

        // 2 & 3. Additional businesses from signature2 / signature3
        foreach (['signature2', 'signature3'] as $sigKey) {
            $sig = trim($cs[$sigKey] ?? '');
            if ($sig === '') {
                continue;
            }
            $name = $this->nameFromSignature($sig);
            if ($name === '') {
                continue;
            }
            $this->db->insert('businesses', [
                'name'       => $name,
                'signature'  => $sig,
                'is_default' => 0,
                'sort_order' => $sort++,
                'is_active'  => 1,
            ]);
        }
    }

    /** Extract a business name from a signature block (first meaningful line). */
    private function nameFromSignature(string $sig): string
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($sig));
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strcasecmp($line, 'Approved') === 0) {
                continue;
            }
            return mb_substr($line, 0, 200);
        }
        return '';
    }

    // ── Queries ───────────────────────────────────────────────────────────────

    /** All active businesses ordered for display. */
    public function allActive(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM businesses WHERE is_active = 1 ORDER BY is_default DESC, sort_order ASC, name ASC"
        );
    }

    /** Every business (including inactive) for the management page. */
    public function allForAdmin(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM businesses ORDER BY is_default DESC, sort_order ASC, name ASC"
        );
    }

    /** The default business, or the first active one as a fallback. */
    public function getDefault(): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM businesses WHERE is_active = 1 ORDER BY is_default DESC, sort_order ASC LIMIT 1"
        );
    }

    /** Clear the default flag on every row (used before setting a new default). */
    public function clearDefault(): void
    {
        $this->db->update('businesses', ['is_default' => 0], '1 = 1');
    }

    public function countActive(): int
    {
        return (int)$this->db->fetchScalar("SELECT COUNT(*) FROM businesses WHERE is_active = 1");
    }
}
