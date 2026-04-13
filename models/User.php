<?php
class User extends BaseModel
{
    protected string $table      = 'users';
    protected string $primaryKey = 'user_id';

    // ── Lookup ────────────────────────────────────────────────────────────────

    public function findByUsername(string $username): ?array
    {
        return $this->db->fetchOne(
            "SELECT u.*, s.first_name, s.last_name
             FROM users u
             LEFT JOIN staff s ON s.staff_id = u.staff_id
             WHERE u.username = ? LIMIT 1",
            [$username]
        );
    }

    public function findByEmail(string $email): ?array
    {
        return $this->findOneBy('email', $email);
    }

    public function findByIdentifier(string $identifier): ?array
    {
        return $this->db->fetchOne(
            "SELECT u.*, s.first_name, s.last_name
             FROM users u
             LEFT JOIN staff s ON s.staff_id = u.staff_id
             WHERE (u.username = ? OR u.email = ?) AND u.is_active = 1
             LIMIT 1",
            [$identifier, $identifier]
        );
    }

    // ── All users with staff info ─────────────────────────────────────────────

    public function getAllWithStaff(): array
    {
        return $this->db->fetchAll(
            "SELECT u.user_id, u.username, u.email, u.role, u.is_active, u.last_login, u.created_at,
                    s.first_name, s.last_name, s.phone
             FROM users u
             LEFT JOIN staff s ON s.staff_id = u.staff_id
             ORDER BY u.role DESC, s.last_name"
        );
    }

    // ── Create / update ───────────────────────────────────────────────────────

    /** Create a new user account with hashed password. */
    public function createUser(array $data): int
    {
        if (isset($data['password'])) {
            $data['password_hash'] = Auth::hashPassword($data['password']);
            unset($data['password']);
        }
        return $this->create($data);
    }

    public function changePassword(int $userId, string $newPassword): int
    {
        return $this->update($userId, [
            'password_hash' => Auth::hashPassword($newPassword),
        ]);
    }

    public function setActive(int $userId, bool $active): int
    {
        return $this->update($userId, ['is_active' => $active ? 1 : 0]);
    }

    public function updateLastLogin(int $userId): void
    {
        $this->update($userId, ['last_login' => date('Y-m-d H:i:s')]);
    }

    // ── Validation helpers ────────────────────────────────────────────────────

    public function isUsernameTaken(string $username, ?int $excludeId = null): bool
    {
        $sql    = "SELECT COUNT(*) FROM users WHERE username = ?";
        $params = [$username];
        if ($excludeId) {
            $sql    .= " AND user_id != ?";
            $params[] = $excludeId;
        }
        return (int)$this->db->fetchScalar($sql, $params) > 0;
    }

    public function isEmailTaken(string $email, ?int $excludeId = null): bool
    {
        $sql    = "SELECT COUNT(*) FROM users WHERE email = ?";
        $params = [$email];
        if ($excludeId) {
            $sql    .= " AND user_id != ?";
            $params[] = $excludeId;
        }
        return (int)$this->db->fetchScalar($sql, $params) > 0;
    }
}
