<?php
/**
 * Auth — Session-based authentication & RBAC
 *
 * Responsibilities:
 *  - Login / logout
 *  - Session lifetime management
 *  - CSRF token generation & validation
 *  - Role-based access control
 *  - Login rate limiting (5 attempts / hour per IP)
 */
class Auth
{
    private Database $db;

    // Max failed login attempts per IP before lockout
    private const MAX_ATTEMPTS   = 5;
    private const LOCKOUT_WINDOW = 3600; // 1 hour in seconds

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Session bootstrap (call once at app startup) ──────────────────────────

    public static function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            // Use app-local session directory so it's always writable
            $sessionPath = APP_ROOT . '/sessions';
            if (!is_dir($sessionPath)) {
                mkdir($sessionPath, 0700, true);
            }
            session_save_path($sessionPath);

            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }

        // Enforce session timeout
        if (isset($_SESSION['_last_activity'])) {
            if ((time() - $_SESSION['_last_activity']) > SESSION_TIMEOUT) {
                self::destroySession();
            }
        }
        $_SESSION['_last_activity'] = time();
    }

    private static function destroySession(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // ── Login ─────────────────────────────────────────────────────────────────

    /**
     * Attempt login with username/email + password.
     *
     * @return array{success: bool, error?: string, user?: array}
     */
    public function login(string $identifier, string $password, bool $remember = false): array
    {
        // Rate limit check
        if ($this->isRateLimited()) {
            return ['success' => false, 'error' => 'Too many login attempts. Please try again in 1 hour.'];
        }

        // Find user by username or email
        $user = $this->db->fetchOne(
            "SELECT u.*, s.first_name, s.last_name, s.phone
             FROM users u
             LEFT JOIN staff s ON s.staff_id = u.staff_id
             WHERE (u.username = ? OR u.email = ?) AND u.is_active = 1
             LIMIT 1",
            [$identifier, $identifier]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->recordFailedAttempt();
            return ['success' => false, 'error' => 'Invalid username or password.'];
        }

        // Rehash if needed (future-proof when bcrypt cost changes)
        if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $this->db->update('users', ['password_hash' => $newHash], 'user_id = ?', [$user['user_id']]);
        }

        // Regenerate session ID on login to prevent session fixation
        session_regenerate_id(true);

        // Persist auth in session
        $this->clearFailedAttempts();
        $this->createUserSession($user);

        // Update last_login timestamp
        $this->db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'user_id = ?', [$user['user_id']]);

        // Activity log
        Logger::log('login', 'user', (int)$user['user_id']);

        return ['success' => true, 'user' => $user];
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function logout(): void
    {
        if ($this->isLoggedIn()) {
            Logger::log('logout', 'user', (int)$_SESSION['user']['user_id']);
        }
        self::destroySession();
    }

    // ── Check / retrieve current user ─────────────────────────────────────────

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user']['user_id']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user']['user_id']) ? (int)$_SESSION['user']['user_id'] : null;
    }

    public static function role(): ?string
    {
        return $_SESSION['user']['role'] ?? null;
    }

    // ── Role-based access control ─────────────────────────────────────────────

    /**
     * Require the user to have at least $minRole.
     * Redirects to login or 403 if not satisfied.
     */
    public static function requireRole(string $minRole): void
    {
        self::requireAuth();

        $hierarchy  = ROLE_HIERARCHY;
        $userLevel  = $hierarchy[self::role()] ?? 0;
        $minLevel   = $hierarchy[$minRole]     ?? 999;

        if ($userLevel < $minLevel) {
            http_response_code(403);
            require VIEWS_PATH . '/errors/403.php';
            exit;
        }
    }

    /** Require the user to be logged in; redirect to login page otherwise. */
    public static function requireAuth(): void
    {
        if (!self::isLoggedIn()) {
            Utils::redirect('/login');
        }
    }

    /** Return true if the current user has at least $minRole. */
    public static function can(string $minRole): bool
    {
        if (!self::isLoggedIn()) {
            return false;
        }
        $hierarchy = ROLE_HIERARCHY;
        return ($hierarchy[self::role()] ?? 0) >= ($hierarchy[$minRole] ?? 999);
    }

    public static function isAdmin(): bool    { return self::can('admin'); }
    public static function isManager(): bool  { return self::can('manager'); }

    // ── Password helpers ──────────────────────────────────────────────────────

    public static function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    // ── CSRF ──────────────────────────────────────────────────────────────────

    public static function generateCSRFToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(
                (int)($_ENV['CSRF_TOKEN_LENGTH'] ?? 32)
            ));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCSRF(string $token): bool
    {
        if (empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /** Use in controllers: aborts with 403 if CSRF is invalid. */
    public static function checkCSRF(): void
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!self::validateCSRF($token)) {
            http_response_code(403);
            die('CSRF token mismatch. Please go back and try again.');
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function createUserSession(array $user): void
    {
        // Store only what views need — never store the password hash
        $_SESSION['user'] = [
            'user_id'    => $user['user_id'],
            'username'   => $user['username'],
            'email'      => $user['email'],
            'role'       => $user['role'],
            'staff_id'   => $user['staff_id'],
            'first_name' => $user['first_name'] ?? '',
            'last_name'  => $user['last_name']  ?? '',
            'full_name'  => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
        ];
    }

    // ── Rate limiting (stored in session per IP) ──────────────────────────────

    private function isRateLimited(): bool
    {
        $attempts = $_SESSION['_login_attempts'] ?? [];
        $ip       = $this->getClientIp();
        $window   = time() - self::LOCKOUT_WINDOW;

        // Keep only attempts within the window
        $recent = array_filter(
            $attempts[$ip] ?? [],
            fn($t) => $t > $window
        );

        return count($recent) >= self::MAX_ATTEMPTS;
    }

    private function recordFailedAttempt(): void
    {
        $ip = $this->getClientIp();
        $_SESSION['_login_attempts'][$ip][] = time();
    }

    private function clearFailedAttempts(): void
    {
        $ip = $this->getClientIp();
        unset($_SESSION['_login_attempts'][$ip]);
    }

    private function getClientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
