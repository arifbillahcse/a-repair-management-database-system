<?php
class PersonalNote extends BaseModel
{
    protected string $table      = 'personal_notes';
    protected string $primaryKey = 'note_id';

    public function __construct()
    {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $pdo = $this->db->getPdo();
        $pdo->exec("CREATE TABLE IF NOT EXISTS `personal_notes` (
            `note_id`     INT UNSIGNED    NOT NULL AUTO_INCREMENT,
            `title`       VARCHAR(255)    NOT NULL,
            `description` TEXT            NOT NULL,
            `is_completed` TINYINT(1)     NOT NULL DEFAULT 0,
            `created_by`  INT UNSIGNED    NOT NULL,
            `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`note_id`),
            KEY `idx_pn_user` (`created_by`),
            KEY `idx_pn_completed` (`is_completed`),
            KEY `idx_pn_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function getByUser(int $userId, array $filters = [], int $page = 1): array
    {
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;

        $query = "SELECT * FROM personal_notes WHERE created_by = ?";
        $params = [$userId];

        // Filter by completion status
        if ($filters['status'] === 'completed') {
            $query .= " AND is_completed = 1";
        } elseif ($filters['status'] === 'pending') {
            $query .= " AND is_completed = 0";
        }

        // Search by title/description
        if (!empty($filters['search'])) {
            $query .= " AND (title LIKE ? OR description LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        $countQuery = "SELECT COUNT(*) as cnt FROM personal_notes WHERE created_by = ?";
        $countParams = [$userId];
        if ($filters['status'] === 'completed') {
            $countQuery .= " AND is_completed = 1";
        } elseif ($filters['status'] === 'pending') {
            $countQuery .= " AND is_completed = 0";
        }
        if (!empty($filters['search'])) {
            $countQuery .= " AND (title LIKE ? OR description LIKE ?)";
            $countParams[] = '%' . $filters['search'] . '%';
            $countParams[] = '%' . $filters['search'] . '%';
        }

        $total = $this->db->fetchOne($countQuery, $countParams)['cnt'] ?? 0;

        $query .= " ORDER BY is_completed ASC, created_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $notes = $this->db->fetchAll($query, $params);

        return [
            'rows' => $notes,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'pages' => ceil($total / $perPage),
            ]
        ];
    }

    public function create(array $data): int
    {
        return $this->db->insert('personal_notes', [
            'title'       => $data['title'],
            'description' => $data['description'],
            'is_completed' => 0,
            'created_by'  => $data['created_by'],
        ]);
    }

    public function toggleCompletion(int $id): bool
    {
        $note = $this->findById($id);
        if (!$note) return false;
        return (bool)$this->db->update(
            $this->table,
            ['is_completed' => $note['is_completed'] ? 0 : 1],
            "`{$this->primaryKey}` = ?",
            [$id]
        );
    }
}
