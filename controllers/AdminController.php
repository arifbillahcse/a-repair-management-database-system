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
        Auth::requireRole('admin');

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
