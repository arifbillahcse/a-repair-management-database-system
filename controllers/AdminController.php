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

        $db      = Database::getInstance();
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
}
