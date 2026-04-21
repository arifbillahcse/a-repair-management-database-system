<?php
/**
 * One-time migration: creates credit_notes and credit_note_items tables.
 * Visit this URL once, then DELETE this file from the server.
 */
define('APP_ROOT', dirname(__DIR__));
$envFile = APP_ROOT . '/config/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$k, $v] = array_map('trim', explode('=', $line, 2));
            $_ENV[$k] = $v;
            putenv("{$k}={$v}");
        }
    }
}
$cfg = require APP_ROOT . '/config/database.php';
try {
    $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], $cfg['options']);

    $pdo->exec("CREATE TABLE IF NOT EXISTS `credit_notes` (
        `cn_id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
        `cn_number`        INT UNSIGNED    NOT NULL,
        `cn_date`          DATE            NOT NULL,
        `customer_name`    VARCHAR(200)    NOT NULL DEFAULT '',
        `customer_address` VARCHAR(500)    NOT NULL DEFAULT '',
        `customer_vat`     VARCHAR(50)     NOT NULL DEFAULT '',
        `note`             TEXT                     DEFAULT NULL,
        `created_by`       INT UNSIGNED             DEFAULT NULL,
        `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`cn_id`),
        UNIQUE KEY `uk_cn_number` (`cn_number`),
        KEY `idx_cn_date` (`cn_date`),
        CONSTRAINT `fk_cn_created_by`
            FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`)
            ON DELETE SET NULL ON UPDATE CASCADE
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

    echo '<p style="color:green;font-family:sans-serif;font-size:1.2rem">
        ✅ Migration complete! Tables <strong>credit_notes</strong> and
        <strong>credit_note_items</strong> created successfully.<br><br>
        <strong>Please delete this file from your server now.</strong>
    </p>';
} catch (Exception $e) {
    echo '<p style="color:red;font-family:sans-serif">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
