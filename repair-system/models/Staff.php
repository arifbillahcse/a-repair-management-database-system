<?php
class Staff extends BaseModel
{
    protected string $table      = 'staff';
    protected string $primaryKey = 'staff_id';

    /** All active staff members with their user account status. */
    public function getAllActive(): array
    {
        return $this->db->fetchAll(
            "SELECT s.*, u.username, u.role AS user_role, u.is_active AS user_active
             FROM staff s
             LEFT JOIN users u ON u.staff_id = s.staff_id
             WHERE s.is_active = 1
             ORDER BY s.last_name, s.first_name"
        );
    }

    public function getAll(): array
    {
        return $this->db->fetchAll(
            "SELECT s.*, u.username, u.role AS user_role
             FROM staff s
             LEFT JOIN users u ON u.staff_id = s.staff_id
             ORDER BY s.role, s.last_name"
        );
    }

    /** Staff members eligible to be assigned to repairs (technicians + managers). */
    public function getTechnicians(): array
    {
        return $this->db->fetchAll(
            "SELECT staff_id,
                    CONCAT(first_name, ' ', last_name) AS full_name,
                    role
             FROM staff
             WHERE is_active = 1 AND role IN ('technician', 'manager', 'admin')
             ORDER BY last_name"
        );
    }

    public function getFullName(int $staffId): string
    {
        $row = $this->findById($staffId);
        return $row ? trim($row['first_name'] . ' ' . $row['last_name']) : '';
    }

    /** Repair count per staff member (for reports). */
    public function getRepairStats(): array
    {
        return $this->db->fetchAll(
            "SELECT s.staff_id,
                    CONCAT(s.first_name, ' ', s.last_name) AS full_name,
                    COUNT(r.repair_id) AS total_repairs,
                    SUM(r.status = 'completed') AS completed,
                    SUM(r.status = 'in_progress') AS in_progress
             FROM staff s
             LEFT JOIN repairs r ON r.staff_id = s.staff_id
             WHERE s.is_active = 1
             GROUP BY s.staff_id
             ORDER BY total_repairs DESC"
        );
    }
}
