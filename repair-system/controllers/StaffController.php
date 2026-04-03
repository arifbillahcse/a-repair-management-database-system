<?php
class StaffController
{
    private Staff $staffModel;
    private User  $userModel;

    public function __construct()
    {
        $this->staffModel = new Staff();
        $this->userModel  = new User();
    }

    // ── GET /staff ────────────────────────────────────────────────────────────

    public function index(): void
    {
        Auth::requireRole('manager');
        $staffList = $this->staffModel->getAll();
        require VIEWS_PATH . '/staff/list.php';
    }

    // ── GET /staff/:id ────────────────────────────────────────────────────────

    public function show(int $id): void
    {
        Auth::requireRole('manager');
        $staff = $this->staffModel->findById($id);
        if (!$staff) $this->notFound();

        // Load linked user account
        $userAccount = Database::getInstance()->fetchOne(
            "SELECT user_id, username, email, role, is_active, last_login, created_at
             FROM users WHERE staff_id = ? LIMIT 1",
            [$id]
        );

        // Load repair stats for this technician
        $repairStats = Database::getInstance()->fetchOne(
            "SELECT COUNT(*)                                                            AS total,
                    SUM(status = 'in_progress')                                        AS in_progress,
                    SUM(status = 'completed')                                          AS completed,
                    SUM(status = 'collected')                                          AS collected,
                    SUM(MONTH(date_in) = MONTH(NOW()) AND YEAR(date_in) = YEAR(NOW())) AS this_month,
                    ROUND(AVG(DATEDIFF(COALESCE(date_out, NOW()), date_in)), 1)        AS avg_days
             FROM repairs WHERE staff_id = ?",
            [$id]
        ) ?? [];

        require VIEWS_PATH . '/staff/view.php';
    }

    // ── GET /staff/create ─────────────────────────────────────────────────────

    public function create(): void
    {
        Auth::requireRole('admin');
        $formData  = $_SESSION['_form_data']   ?? [];
        $errors    = $_SESSION['_form_errors'] ?? [];
        unset($_SESSION['_form_data'], $_SESSION['_form_errors']);
        $csrfToken = Auth::generateCSRFToken();
        require VIEWS_PATH . '/staff/create.php';
    }

    // ── POST /staff ───────────────────────────────────────────────────────────

    public function store(): void
    {
        Auth::requireRole('admin');
        Auth::checkCSRF();

        $errors = $this->validateStaff($_POST);

        // Username uniqueness check
        if (!empty($_POST['username']) && $this->userModel->isUsernameTaken(trim($_POST['username']))) {
            $errors['username'] = 'Username is already taken.';
        }

        if ($errors) {
            $_SESSION['_form_errors'] = $errors;
            $_SESSION['_form_data']   = $_POST;
            Utils::redirect('/staff/create');
        }

        $staffId = $this->staffModel->create([
            'first_name' => Utils::sanitize($_POST['first_name']),
            'last_name'  => Utils::sanitize($_POST['last_name']),
            'email'      => trim($_POST['email'] ?? ''),
            'phone'      => Utils::sanitizePhone($_POST['phone'] ?? ''),
            'role'       => $_POST['staff_role'] ?? 'technician',
            'is_active'  => 1,
            'notes'      => Utils::sanitize($_POST['notes'] ?? ''),
        ]);

        if (!empty($_POST['username']) && !empty($_POST['password'])) {
            $this->userModel->createUser([
                'username'  => trim($_POST['username']),
                'email'     => trim($_POST['email'] ?? ''),
                'password'  => $_POST['password'],
                'role'      => $_POST['user_role'] ?? 'technician',
                'staff_id'  => $staffId,
                'is_active' => 1,
            ]);
        }

        Logger::log('created', 'staff', $staffId);
        Utils::flashSuccess('Staff member created.');
        Utils::redirect('/staff/' . $staffId);
    }

    // ── GET /staff/:id/edit ───────────────────────────────────────────────────

    public function edit(int $id): void
    {
        Auth::requireRole('admin');
        $staff = $this->staffModel->findById($id);
        if (!$staff) $this->notFound();

        $userAccount = Database::getInstance()->fetchOne(
            "SELECT user_id, username, email, role, is_active FROM users WHERE staff_id = ? LIMIT 1",
            [$id]
        );

        $formData  = $_SESSION['_form_data']   ?? [];
        $errors    = $_SESSION['_form_errors'] ?? [];
        unset($_SESSION['_form_data'], $_SESSION['_form_errors']);

        $csrfToken = Auth::generateCSRFToken();
        require VIEWS_PATH . '/staff/edit.php';
    }

    // ── POST /staff/:id ───────────────────────────────────────────────────────

    public function update(int $id): void
    {
        Auth::requireRole('admin');
        Auth::checkCSRF();

        $staff = $this->staffModel->findById($id);
        if (!$staff) $this->notFound();

        $errors = $this->validateStaff($_POST);
        if ($errors) {
            $_SESSION['_form_errors'] = $errors;
            $_SESSION['_form_data']   = $_POST;
            Utils::redirect('/staff/' . $id . '/edit');
        }

        $this->staffModel->update($id, [
            'first_name' => Utils::sanitize($_POST['first_name']),
            'last_name'  => Utils::sanitize($_POST['last_name']),
            'email'      => trim($_POST['email'] ?? ''),
            'phone'      => Utils::sanitizePhone($_POST['phone'] ?? ''),
            'role'       => $_POST['staff_role'] ?? 'technician',
            'is_active'  => isset($_POST['is_active']) ? 1 : 0,
            'notes'      => Utils::sanitize($_POST['notes'] ?? ''),
        ]);

        // Update user account if linked
        $userAccount = Database::getInstance()->fetchOne(
            "SELECT user_id FROM users WHERE staff_id = ? LIMIT 1", [$id]
        );
        if ($userAccount && !empty($_POST['user_role'])) {
            Database::getInstance()->update('users', ['role' => $_POST['user_role']], 'user_id = ?', [$userAccount['user_id']]);
        }

        // Change password if provided
        if ($userAccount && !empty($_POST['new_password'])) {
            $this->userModel->changePassword($userAccount['user_id'], $_POST['new_password']);
        }

        Logger::log('updated', 'staff', $id);
        Utils::flashSuccess('Staff member updated.');
        Utils::redirect('/staff/' . $id);
    }

    // ── POST /staff/:id/delete ────────────────────────────────────────────────

    public function destroy(int $id): void
    {
        Auth::requireRole('admin');
        Auth::checkCSRF();

        // Deactivate linked user account too
        Database::getInstance()->update('users', ['is_active' => 0], 'staff_id = ?', [$id]);

        Logger::log('deleted', 'staff', $id);
        $this->staffModel->update($id, ['is_active' => 0]);

        Utils::flashSuccess('Staff member deactivated.');
        Utils::redirect('/staff');
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function validateStaff(array $data): array
    {
        $errors = [];
        if (empty(trim($data['first_name'] ?? ''))) $errors['first_name'] = 'First name is required.';
        if (empty(trim($data['last_name']  ?? ''))) $errors['last_name']  = 'Last name is required.';
        if (!empty($data['email']) && !Utils::isValidEmail($data['email'])) {
            $errors['email'] = 'Invalid email format.';
        }
        if (!empty($data['new_password']) && strlen($data['new_password']) < 8) {
            $errors['new_password'] = 'Password must be at least 8 characters.';
        }
        return $errors;
    }

    private function notFound(): never
    {
        http_response_code(404);
        require VIEWS_PATH . '/errors/404.php';
        exit;
    }
}
