<?php
class ProfileController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    // ── GET /profile ──────────────────────────────────────────────────────────

    public function index(): void
    {
        Auth::requireAuth();

        $user   = $this->userModel->findById(Auth::id());
        $errors = $_SESSION['_form_errors'] ?? [];
        $saved  = $_SESSION['_profile_saved'] ?? false;
        unset($_SESSION['_form_errors'], $_SESSION['_profile_saved']);

        require VIEWS_PATH . '/profile/index.php';
    }

    // ── POST /profile ─────────────────────────────────────────────────────────

    public function update(): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $userId = Auth::id();
        $user   = $this->userModel->findById($userId);
        $errors = [];

        $action = $_POST['action'] ?? 'info';

        if ($action === 'info') {
            $email    = trim($_POST['email'] ?? '');
            $username = trim($_POST['username'] ?? '');

            // ── Username: required, format-checked, and unique ────────────────
            if ($username === '') {
                $errors['username'] = 'Username is required.';
            } elseif (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
                $errors['username'] = 'Use 3–50 letters, numbers, dot, dash or underscore.';
            } elseif ($this->userModel->isUsernameTaken($username, $userId)) {
                $errors['username'] = 'That username is already taken.';
            }

            if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Please enter a valid email address.';
            } elseif ($email && $this->userModel->isEmailTaken($email, $userId)) {
                $errors['email'] = 'That email is already used by another account.';
            }

            if (!$errors) {
                $fields = ['email' => $email ?: null, 'username' => $username];

                // Also update linked staff name if present
                $db = Database::getInstance();
                if (!empty($user['staff_id'])) {
                    $firstName = trim($_POST['first_name'] ?? '');
                    $lastName  = trim($_POST['last_name']  ?? '');
                    if ($firstName || $lastName) {
                        $db->update('staff', [
                            'first_name' => $firstName,
                            'last_name'  => $lastName,
                        ], 'staff_id = ?', [(int)$user['staff_id']]);
                    }
                }

                $this->userModel->update($userId, $fields);

                // Refresh session
                $_SESSION['user']['email']      = $email;
                $_SESSION['user']['username']   = $username;
                if (!empty($user['staff_id'])) {
                    $fn = trim($_POST['first_name'] ?? '');
                    $ln = trim($_POST['last_name']  ?? '');
                    $_SESSION['user']['first_name'] = $fn;
                    $_SESSION['user']['last_name']  = $ln;
                    $_SESSION['user']['full_name']  = trim("$fn $ln");
                }

                Logger::log('updated', 'user', $userId, null, [
                    'email_changed'    => ($email !== ($user['email'] ?? '')),
                    'username_changed' => ($username !== ($user['username'] ?? '')),
                ]);
                $_SESSION['_profile_saved'] = true;
                Utils::redirect('/profile');
            }

        } elseif ($action === 'password') {
            $current  = $_POST['current_password']  ?? '';
            $new      = $_POST['new_password']       ?? '';
            $confirm  = $_POST['confirm_password']   ?? '';

            if (!Auth::verifyPassword($current, $user['password_hash'])) {
                $errors['current_password'] = 'Current password is incorrect.';
            } elseif (strlen($new) < 8) {
                $errors['new_password'] = 'New password must be at least 8 characters.';
            } elseif ($new !== $confirm) {
                $errors['confirm_password'] = 'Passwords do not match.';
            }

            if (!$errors) {
                $this->userModel->changePassword($userId, $new);
                Logger::log('updated', 'user', $userId, null, ['password_changed' => true]);
                $_SESSION['_profile_saved'] = true;
                Utils::redirect('/profile');
            }
        }

        if ($errors) {
            $_SESSION['_form_errors'] = $errors;
            Utils::redirect('/profile');
        }
    }
}
