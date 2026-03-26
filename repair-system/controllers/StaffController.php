<?php
/**
 * StaffController — full implementation in Prompt 6.
 */
class StaffController
{
    private Staff $staffModel;
    private User  $userModel;

    public function __construct()
    {
        $this->staffModel = new Staff();
        $this->userModel  = new User();
    }

    public function index(): void
    {
        Auth::requireRole('manager');
        $staffList = $this->staffModel->getAll();
        require VIEWS_PATH . '/staff/list.php';
    }

    public function show(int $id): void
    {
        Auth::requireRole('manager');
        $staff = $this->staffModel->findById($id);
        if (!$staff) { $this->notFound(); }
        require VIEWS_PATH . '/staff/view.php';
    }

    public function create(): void
    {
        Auth::requireRole('admin');
        $csrfToken = Auth::generateCSRFToken();
        require VIEWS_PATH . '/staff/create.php';
    }

    public function store(): void
    {
        Auth::requireRole('admin');
        Auth::checkCSRF();

        $errors = $this->validateStaff($_POST);
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

        // Create login account if credentials provided
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
        Utils::redirect('/staff');
    }

    public function edit(int $id): void
    {
        Auth::requireRole('admin');
        $staff     = $this->staffModel->findById($id);
        if (!$staff) { $this->notFound(); }
        $csrfToken = Auth::generateCSRFToken();
        require VIEWS_PATH . '/staff/edit.php';
    }

    public function update(int $id): void
    {
        Auth::requireRole('admin');
        Auth::checkCSRF();

        $staff = $this->staffModel->findById($id);
        if (!$staff) { $this->notFound(); }

        $this->staffModel->update($id, [
            'first_name' => Utils::sanitize($_POST['first_name']),
            'last_name'  => Utils::sanitize($_POST['last_name']),
            'email'      => trim($_POST['email'] ?? ''),
            'phone'      => Utils::sanitizePhone($_POST['phone'] ?? ''),
            'role'       => $_POST['staff_role'] ?? 'technician',
            'is_active'  => isset($_POST['is_active']) ? 1 : 0,
            'notes'      => Utils::sanitize($_POST['notes'] ?? ''),
        ]);

        Logger::log('updated', 'staff', $id);
        Utils::flashSuccess('Staff member updated.');
        Utils::redirect('/staff');
    }

    public function destroy(int $id): void
    {
        Auth::requireRole('admin');
        Auth::checkCSRF();

        Logger::log('deleted', 'staff', $id);
        $this->staffModel->update($id, ['is_active' => 0]);  // soft-delete

        Utils::flashSuccess('Staff member deactivated.');
        Utils::redirect('/staff');
    }

    private function validateStaff(array $data): array
    {
        $errors = [];
        if (empty(trim($data['first_name'] ?? ''))) { $errors['first_name'] = 'First name is required.'; }
        if (empty(trim($data['last_name']  ?? ''))) { $errors['last_name']  = 'Last name is required.'; }
        if (!empty($data['email']) && !Utils::isValidEmail($data['email'])) {
            $errors['email'] = 'Invalid email format.';
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
