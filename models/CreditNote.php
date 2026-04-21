<?php
class CreditNote extends BaseModel
{
    protected string $table      = 'credit_notes';
    protected string $primaryKey = 'cn_id';

    public function findById(int $id): ?array
    {
        $cn = $this->db->fetchOne(
            "SELECT cn.* FROM credit_notes cn WHERE cn.cn_id = ?",
            [$id]
        );
        if ($cn) {
            $cn['items']       = $this->getItems($id);
            $cn['total_basic'] = array_sum(array_column($cn['items'], 'basic_amount'));
            $cn['total_vat']   = array_sum(array_column($cn['items'], 'vat_amount'));
            $cn['total_net']   = array_sum(array_column($cn['items'], 'net_amount'));
        }
        return $cn;
    }

    public function getAll(array $filters = [], int $page = 1): array
    {
        [$where, $params] = $this->buildFilters($filters);

        $total = (int)$this->db->fetchScalar(
            "SELECT COUNT(*) FROM credit_notes cn {$where}",
            $params
        );

        $paging = Utils::paginate($total, $page, PAGE_SIZE);

        $rows = $this->db->fetchAll(
            "SELECT cn.*,
                    COALESCE(SUM(i.basic_amount), 0) AS total_basic,
                    COALESCE(SUM(i.vat_amount),   0) AS total_vat,
                    COALESCE(SUM(i.net_amount),   0) AS total_net,
                    (SELECT description FROM credit_note_items
                     WHERE cn_id = cn.cn_id ORDER BY item_id LIMIT 1) AS first_desc
             FROM credit_notes cn
             LEFT JOIN credit_note_items i ON i.cn_id = cn.cn_id
             {$where}
             GROUP BY cn.cn_id
             ORDER BY cn.cn_number DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$paging['perPage'], $paging['offset']])
        );

        return ['rows' => $rows, 'pagination' => $paging];
    }

    public function getItems(int $cnId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM credit_note_items WHERE cn_id = ? ORDER BY item_id",
            [$cnId]
        );
    }

    public function addItem(int $cnId, array $item): int
    {
        $basic = round((float)($item['basic_amount'] ?? 0), 2);
        $vat   = round((float)($item['vat_amount']   ?? 0), 2);
        return $this->db->insert('credit_note_items', [
            'cn_id'        => $cnId,
            'description'  => Utils::sanitize($item['description'] ?? ''),
            'basic_amount' => $basic,
            'vat_amount'   => $vat,
            'net_amount'   => round($basic + $vat, 2),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    public function deleteItems(int $cnId): void
    {
        $this->db->delete('credit_note_items', 'cn_id = ?', [$cnId]);
    }

    public function getNextNumber(): int
    {
        $max = $this->db->fetchScalar("SELECT MAX(cn_number) FROM credit_notes");
        return ($max ? (int)$max : 0) + 1;
    }

    private function buildFilters(array $filters): array
    {
        $clauses = [];
        $params  = [];

        if (!empty($filters['search'])) {
            $like      = '%' . $filters['search'] . '%';
            $clauses[] = '(cn.customer_name LIKE ? OR cn.customer_vat LIKE ? OR CAST(cn.cn_number AS CHAR) LIKE ?)';
            array_push($params, $like, $like, $like);
        }
        if (!empty($filters['date_from'])) {
            $clauses[] = 'cn.cn_date >= ?';
            $params[]  = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $clauses[] = 'cn.cn_date <= ?';
            $params[]  = $filters['date_to'];
        }

        $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';
        return [$where, $params];
    }
}
