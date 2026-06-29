<?php
class AdminController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    // ── GET /admin/settings  POST /admin/settings ─────────────────────────────

    public function settings(): void
    {
        Auth::requireRole('manager');

        $db = Database::getInstance();
        $this->migrateCompanySettings($db);
        $company = $db->fetchOne("SELECT * FROM company_settings LIMIT 1") ?? [];
        $saved   = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::checkCSRF();

            $fields = [
                'company_name'        => Utils::sanitize($_POST['company_name']    ?? ''),
                'company_address'     => Utils::sanitize($_POST['company_address'] ?? ''),
                'company_phone'       => Utils::sanitize($_POST['company_phone']   ?? ''),
                'company_email'       => Utils::sanitize($_POST['company_email']   ?? ''),
                'vat_number'          => Utils::sanitize($_POST['vat_number']      ?? ''),
                'tax_id'              => Utils::sanitize($_POST['tax_id']          ?? ''),
                'invoice_prefix'      => Utils::sanitize($_POST['invoice_prefix']  ?? 'INV'),
                'tax_percentage'      => (float)($_POST['tax_percentage'] ?? 22),
                'signature1'          => Utils::sanitize($_POST['signature1']      ?? ''),
                'signature2'          => Utils::sanitize($_POST['signature2']      ?? ''),
                'signature3'          => Utils::sanitize($_POST['signature3']      ?? ''),
            ];

            if (!empty($company['setting_id'])) {
                $db->update('company_settings', $fields, 'setting_id = ?', [$company['setting_id']]);
            } else {
                $db->insert('company_settings', $fields);
            }

            Logger::log('updated', 'company_settings', 1);
            Utils::flashSuccess('Settings saved.');
            Utils::redirect('/admin/settings');
        }

        require VIEWS_PATH . '/admin/settings.php';
    }

    // ── GET /admin/businesses ─────────────────────────────────────────────────

    public function businesses(): void
    {
        Auth::requireRole('manager');

        $model      = new Business();
        $businesses = $model->allForAdmin();
        $csrfToken  = Auth::generateCSRFToken();
        $editId     = (int)($_GET['edit'] ?? 0);
        $editing    = $editId ? $model->findById($editId) : null;

        require VIEWS_PATH . '/admin/businesses.php';
    }

    // ── POST /admin/businesses ────────────────────────────────────────────────

    public function businessSave(): void
    {
        Auth::requireRole('manager');
        Auth::checkCSRF();

        $model = new Business();
        $id    = (int)($_POST['business_id'] ?? 0);

        $name = Utils::sanitize($_POST['name'] ?? '');
        if ($name === '') {
            Utils::flashError('Business name is required.');
            Utils::redirect('/admin/businesses' . ($id ? '?edit=' . $id : ''));
        }

        $fields = [
            'name'         => $name,
            'address'      => Utils::sanitize($_POST['address']      ?? ''),
            'phone'        => Utils::sanitize($_POST['phone']        ?? ''),
            'email'        => Utils::sanitize($_POST['email']        ?? ''),
            'vat_number'   => Utils::sanitize($_POST['vat_number']   ?? ''),
            'tax_id'       => Utils::sanitize($_POST['tax_id']       ?? ''),
            'bank_details' => Utils::sanitize($_POST['bank_details'] ?? ''),
            'signature'    => Utils::sanitize($_POST['signature']    ?? ''),
            'is_active'    => isset($_POST['is_active']) ? 1 : 0,
        ];

        $makeDefault = isset($_POST['is_default']);

        if ($id) {
            $model->update($id, $fields);
            if ($makeDefault) {
                $model->clearDefault();
                $model->update($id, ['is_default' => 1]);
            }
            Logger::log('updated', 'business', $id);
            Utils::flashSuccess('Business updated.');
        } else {
            $newId = $model->create($fields);
            if ($makeDefault) {
                $model->clearDefault();
                $model->update($newId, ['is_default' => 1]);
            }
            Logger::log('created', 'business', $newId);
            Utils::flashSuccess('Business added.');
        }

        Utils::redirect('/admin/businesses');
    }

    // ── POST /admin/businesses/:id/delete ─────────────────────────────────────

    public function businessDelete(int $id): void
    {
        Auth::requireRole('manager');
        Auth::checkCSRF();

        $model = new Business();
        $biz   = $model->findById($id);
        if ($biz) {
            $model->delete($id);
            Logger::log('deleted', 'business', $id);
            Utils::flashSuccess('Business deleted.');
        }

        Utils::redirect('/admin/businesses');
    }

    // ── GET /admin/sysinfo ────────────────────────────────────────────────────

    public function sysinfo(): void
    {
        Auth::requireRole('admin');

        $db = Database::getInstance();

        // PHP info
        $phpInfo = [
            'version'            => PHP_VERSION,
            'sapi'               => PHP_SAPI,
            'os'                 => PHP_OS_FAMILY . ' (' . php_uname('r') . ')',
            'max_execution_time' => ini_get('max_execution_time') . 's',
            'memory_limit'       => ini_get('memory_limit'),
            'upload_max_size'    => ini_get('upload_max_filesize'),
            'post_max_size'      => ini_get('post_max_size'),
            'max_input_vars'     => ini_get('max_input_vars'),
            'display_errors'     => ini_get('display_errors') ? 'On' : 'Off',
            'error_reporting'    => ini_get('error_reporting'),
            'timezone'           => ini_get('date.timezone') ?: date_default_timezone_get(),
            'opcache'            => extension_loaded('Zend OPcache') ? 'Enabled' : 'Disabled',
            'extensions'         => implode(', ', array_filter(['pdo_mysql', 'mbstring', 'json', 'gd', 'zip', 'curl'], 'extension_loaded')),
        ];

        // Database info
        $dbInfo = [];
        try {
            $dbInfo['version']    = $db->fetchScalar("SELECT VERSION()") ?? '—';
            $dbInfo['database']   = $db->fetchScalar("SELECT DATABASE()") ?? '—';
            $dbInfo['charset']    = $db->fetchScalar("SELECT @@character_set_database") ?? '—';
            $dbInfo['collation']  = $db->fetchScalar("SELECT @@collation_database") ?? '—';
            $dbInfo['max_packet'] = $db->fetchScalar("SELECT @@max_allowed_packet") ?? '—';
            $size = $db->fetchOne(
                "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()"
            );
            $dbInfo['size_mb']    = ($size['size_mb'] ?? '0') . ' MB';

            $tableRows = $db->fetchAll(
                "SELECT table_name, table_rows, ROUND((data_length + index_length)/1024/1024,2) AS size_mb
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                 ORDER BY table_name"
            );
        } catch (Exception $e) {
            $dbInfo['error'] = $e->getMessage();
            $tableRows = [];
        }

        // Disk / path info
        $uploadPath = UPLOAD_PATH;
        $diskInfo = [
            'upload_path'  => $uploadPath,
            'upload_writable' => is_writable($uploadPath) ? 'Writable' : 'Not Writable',
            'disk_free'    => function_exists('disk_free_space') ? round(disk_free_space('/') / 1073741824, 2) . ' GB' : '—',
            'disk_total'   => function_exists('disk_total_space') ? round(disk_total_space('/') / 1073741824, 2) . ' GB' : '—',
        ];

        // App info
        $appInfo = [
            'name'        => APP_NAME,
            'version'     => APP_VERSION,
            'environment' => APP_ENV,
            'debug'       => APP_DEBUG ? 'On' : 'Off',
            'base_url'    => BASE_URL,
            'php_path'    => PHP_BINARY,
        ];

        require VIEWS_PATH . '/admin/sysinfo.php';
    }

    // ── GET /admin/users ──────────────────────────────────────────────────────

    public function users(): void
    {
        Auth::requireRole('admin');
        $users = $this->userModel->getAllWithStaff();
        require VIEWS_PATH . '/admin/users.php';
    }

    // ── POST /admin/users/:id/toggle ──────────────────────────────────────────

    public function toggleUser(int $id): void
    {
        Auth::requireRole('admin');
        Auth::checkCSRF();

        // Prevent disabling own account
        if ($id === Auth::id()) {
            Utils::flashError('You cannot disable your own account.');
            Utils::redirect('/admin/users');
        }

        $user = $this->userModel->findById($id);
        if (!$user) {
            Utils::redirect('/admin/users');
        }

        $newState = $user['is_active'] ? 0 : 1;
        $this->userModel->setActive($id, (bool)$newState);

        Logger::log('updated', 'user', $id, null, ['is_active' => $newState]);
        Utils::flashSuccess($newState ? 'User account enabled.' : 'User account disabled.');
        Utils::redirect('/admin/users');
    }

    // ── GET /admin/db-export ──────────────────────────────────────────────────

    public function dbExportPage(): void
    {
        Auth::requireRole('admin');

        $db = Database::getInstance();

        $dbName  = $db->fetchScalar("SELECT DATABASE()");
        $version = $db->fetchScalar("SELECT VERSION()");
        $charset = $db->fetchScalar("SELECT @@character_set_database");

        $sizeRow = $db->fetchOne(
            "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb,
                    COUNT(*) AS table_count
             FROM information_schema.tables
             WHERE table_schema = DATABASE()"
        );
        $sizeMb     = $sizeRow['size_mb']     ?? 0;
        $tableCount = (int)($sizeRow['table_count'] ?? 0);

        $tableRows = $db->fetchAll(
            "SELECT table_name,
                    table_rows,
                    ROUND((data_length + index_length) / 1024, 1) AS size_kb
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
             ORDER BY table_name"
        );

        require VIEWS_PATH . '/admin/db-export.php';
    }

    // ── POST /admin/db-export ─────────────────────────────────────────────────

    public function dbExport(): void
    {
        Auth::requireRole('admin');
        Auth::checkCSRF();

        $db  = Database::getInstance();
        $pdo = $db->getPdo();

        $dbName   = $db->fetchScalar("SELECT DATABASE()");
        $filename = 'db_backup_' . $dbName . '_' . date('Y-m-d_His') . '.sql';

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Open output stream
        $out = fopen('php://output', 'w');

        fwrite($out, "-- ============================================================\n");
        fwrite($out, "-- Database Backup: {$dbName}\n");
        fwrite($out, "-- Generated:       " . date('Y-m-d H:i:s') . "\n");
        fwrite($out, "-- ============================================================\n\n");
        fwrite($out, "SET FOREIGN_KEY_CHECKS = 0;\n");
        fwrite($out, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
        fwrite($out, "SET NAMES utf8mb4;\n\n");

        // Get all tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            fwrite($out, "-- ------------------------------------------------------------\n");
            fwrite($out, "-- Table: `{$table}`\n");
            fwrite($out, "-- ------------------------------------------------------------\n\n");

            // DROP + CREATE
            fwrite($out, "DROP TABLE IF EXISTS `{$table}`;\n");
            $createRow = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
            $createSql = $createRow['Create Table'] ?? $createRow[1] ?? '';
            fwrite($out, $createSql . ";\n\n");

            // Data rows — batch in chunks to keep memory low
            $stmt = $pdo->query("SELECT * FROM `{$table}`");
            $cols = null;
            $batch = [];
            $batchSize = 100;

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($cols === null) {
                    $cols = '`' . implode('`, `', array_keys($row)) . '`';
                }

                $values = array_map(function ($val) use ($pdo): string {
                    if ($val === null) return 'NULL';
                    return $pdo->quote($val);
                }, array_values($row));

                $batch[] = '(' . implode(', ', $values) . ')';

                if (count($batch) >= $batchSize) {
                    fwrite($out, "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $batch) . ";\n");
                    $batch = [];
                }
            }

            if ($batch) {
                fwrite($out, "INSERT INTO `{$table}` ({$cols}) VALUES\n" . implode(",\n", $batch) . ";\n");
            }

            fwrite($out, "\n");
        }

        fwrite($out, "SET FOREIGN_KEY_CHECKS = 1;\n");
        fwrite($out, "-- End of backup\n");
        fclose($out);

        Logger::log('exported', 'database', null, null, ['tables' => count($tables)]);
        exit;
    }



    private function migrateCompanySettings(Database $db): void
    {
        $pdo = $db->getPdo();

        // Add signature columns if missing
        $existing = array_column(
            $pdo->query("SHOW COLUMNS FROM `company_settings`")->fetchAll(PDO::FETCH_ASSOC),
            'Field'
        );
        $cols = [
            'signature1' => "VARCHAR(300) NOT NULL DEFAULT ''",
            'signature2' => "VARCHAR(300) NOT NULL DEFAULT ''",
            'signature3' => "VARCHAR(300) NOT NULL DEFAULT ''",
        ];
        foreach ($cols as $col => $def) {
            if (!in_array($col, $existing)) {
                $pdo->exec("ALTER TABLE `company_settings` ADD COLUMN `{$col}` {$def}");
            }
        }

        // Seed default signatures if no row or signatures are all empty
        $row = $db->fetchOne("SELECT setting_id, signature1, signature2, signature3 FROM company_settings LIMIT 1");
        $defaults = [
            'signature1' => "Malta Spare Parts Ltd.\nApproved",
            'signature2' => "ТРАКИЯ ИНВЕСТМЕНТ ЕООД\nTracia Investment Ltd.\nApproved",
            'signature3' => "Electroclean di Meo Alessio\nAlessio Meo",
        ];
        if (!$row) {
            $db->insert('company_settings', array_merge(['company_name' => ''], $defaults));
        } elseif (empty($row['signature1']) && empty($row['signature2']) && empty($row['signature3'])) {
            $db->update('company_settings', $defaults, 'setting_id = ?', [$row['setting_id']]);
        }
    }

    // ── POST /admin/users/:id/reset-password ──────────────────────────────────

    public function resetPassword(int $id): void
    {
        Auth::requireRole('admin');
        Auth::checkCSRF();

        $newPassword = trim($_POST['new_password'] ?? '');
        if (strlen($newPassword) < 8) {
            Utils::flashError('Password must be at least 8 characters.');
            Utils::redirect('/admin/users');
        }

        $this->userModel->changePassword($id, $newPassword);
        Logger::log('updated', 'user', $id, null, ['password_reset' => true]);
        Utils::flashSuccess('Password updated.');
        Utils::redirect('/admin/users');
    }

    // ── POST /admin/migrate-landline ──────────────────────────────────────────

    public function migrateLandlineToMobile(): void
    {
        Auth::requireRole('admin');
        Auth::checkCSRF();

        $db  = Database::getInstance();
        $pdo = $db->getPdo();

        // Copy landline → mobile only where mobile is empty and landline has a value
        $stmt = $pdo->exec(
            "UPDATE customers
             SET phone_mobile   = phone_landline,
                 phone_landline = ''
             WHERE (phone_mobile IS NULL OR phone_mobile = '')
               AND phone_landline IS NOT NULL
               AND phone_landline != ''"
        );

        Logger::log('updated', 'customers', null, null, ['landline_migrated' => $stmt]);
        Utils::flashSuccess("Done — {$stmt} client(s) had their landline number moved to mobile.");
        Utils::redirect('/admin/settings');
    }
}
