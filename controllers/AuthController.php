<?php
class AuthController
{
    private Auth $auth;
    private User $userModel;

    public function __construct()
    {
        $this->auth      = new Auth();
        $this->userModel = new User();
    }

    // ── GET /login ────────────────────────────────────────────────────────────

    public function showLogin(): void
    {
        if (Auth::isLoggedIn()) {
            Utils::redirect('/');
        }
        $csrfToken = Auth::generateCSRFToken();
        $error     = $_SESSION['_login_error'] ?? null;
        unset($_SESSION['_login_error']);

        require VIEWS_PATH . '/auth/login.php';
    }

    // ── POST /login ───────────────────────────────────────────────────────────

    public function login(): void
    {
        Auth::checkCSRF();

        $identifier = trim($_POST['identifier'] ?? '');
        $password   = $_POST['password']         ?? '';
        $remember   = isset($_POST['remember']);

        if (empty($identifier) || empty($password)) {
            $_SESSION['_login_error'] = 'Please enter both username and password.';
            Utils::redirect('/login');
        }

        $result = $this->auth->login($identifier, $password, $remember);

        if ($result['success']) {
            Utils::flash('success', 'Welcome back, ' . Auth::user()['first_name'] . '!');
            Utils::redirect('/');
        }

        $_SESSION['_login_error'] = $result['error'];
        Utils::redirect('/login');
    }

    // ── GET /logout ───────────────────────────────────────────────────────────

    public function logout(): void
    {
        $this->auth->logout();
        Utils::redirect('/login');
    }

    // ── GET /register ─────────────────────────────────────────────────────────

    public function showRegister(): void
    {
        // Only admin can access registration page
        Auth::requireRole('admin');
        $csrfToken = Auth::generateCSRFToken();
        require VIEWS_PATH . '/auth/register.php';
    }

    // ── POST /register ────────────────────────────────────────────────────────

    public function register(): void
    {
        Auth::requireRole('admin');
        Auth::checkCSRF();

        $errors = $this->validateRegistration($_POST);

        if ($errors) {
            $_SESSION['_form_errors'] = $errors;
            $_SESSION['_form_data']   = $_POST;
            Utils::redirect('/register');
        }

        $userId = $this->userModel->createUser([
            'username'  => trim($_POST['username']),
            'email'     => trim($_POST['email']),
            'password'  => $_POST['password'],
            'role'      => $_POST['role'] ?? 'technician',
            'is_active' => 1,
        ]);

        Logger::log('created', 'user', $userId, null, ['username' => $_POST['username'], 'role' => $_POST['role']]);
        Utils::flashSuccess('User account created successfully.');
        Utils::redirect('/staff');
    }

    // ── Validation ────────────────────────────────────────────────────────────

    private function validateRegistration(array $data): array
    {
        $errors = [];

        if (empty(trim($data['username'] ?? ''))) {
            $errors['username'] = 'Username is required.';
        } elseif ($this->userModel->isUsernameTaken(trim($data['username']))) {
            $errors['username'] = 'Username already taken.';
        }

        if (empty(trim($data['email'] ?? '')) || !Utils::isValidEmail(trim($data['email']))) {
            $errors['email'] = 'A valid email address is required.';
        } elseif ($this->userModel->isEmailTaken(trim($data['email']))) {
            $errors['email'] = 'Email already registered.';
        }

        if (empty($data['password']) || strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        } elseif ($data['password'] !== ($data['password_confirm'] ?? '')) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }

        if (!array_key_exists($data['role'] ?? '', USER_ROLES)) {
            $errors['role'] = 'Invalid role selected.';
        }

        return $errors;
    }
}
