<?php
class Invoice extends BaseModel
{
    protected string $table      = 'invoices';
    protected string $primaryKey = 'invoice_id';

    // ── Full record with joins ────────────────────────────────────────────────

    public function findById(int $id): ?array
    {
        $invoice = $this->db->fetchOne(
            "SELECT i.*,
                    c.full_name AS customer_name, c.address AS customer_address,
                    c.city AS customer_city, c.postal_code AS customer_postal_code,
                    c.province AS customer_province, c.vat_number AS customer_vat,
                    c.phone_mobile AS customer_phone, c.email AS customer_email
             FROM invoices i
             JOIN customers c ON c.customer_id = i.customer_id
             WHERE i.invoice_id = ?",
            [$id]
        );

        if ($invoice) {
            $invoice['items'] = $this->getItems($id);
        }

        return $invoice;
    }

    // ── List ──────────────────────────────────────────────────────────────────

    public function getAll(array $filters = [], int $page = 1): array
    {
        [$where, $params] = $this->buildFilters($filters);

        $total  = (int)$this->db->fetchScalar(
            "SELECT COUNT(*) FROM invoices i
             JOIN customers c ON c.customer_id = i.customer_id
             {$where}",
            $params
        );

        $paging = Utils::paginate($total, $page);

        $rows = $this->db->fetchAll(
            "SELECT i.*, c.full_name AS customer_name
             FROM invoices i
             JOIN customers c ON c.customer_id = i.customer_id
             {$where}
             ORDER BY i.invoice_date DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$paging['perPage'], $paging['offset']])
        );

        return ['rows' => $rows, 'pagination' => $paging];
    }

    // ── Invoice number generation ─────────────────────────────────────────────

    public function generateInvoiceNumber(): string
    {
        $settings = $this->db->fetchOne("SELECT invoice_prefix, invoice_next_number FROM company_settings LIMIT 1");

        $prefix = $settings['invoice_prefix']      ?? 'INV';
        $next   = (int)($settings['invoice_next_number'] ?? 1);

        // Increment in settings
        $this->db->update('company_settings', ['invoice_next_number' => $next + 1], '1 = 1');

        return $prefix . '-' . date('Y') . '-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    // ── Items (line items) ────────────────────────────────────────────────────

    public function getItems(int $invoiceId): array
    {
        return $this->db->fetchAll(
            "SELECT ii.*, p.name AS product_name, p.sku
             FROM invoice_items ii
             LEFT JOIN products p ON p.product_id = ii.product_id
             WHERE ii.invoice_id = ?
             ORDER BY ii.sort_order, ii.invoice_item_id",
            [$invoiceId]
        );
    }

    public function addItem(int $invoiceId, array $item): int
    {
        $item['invoice_id'] = $invoiceId;
        $item['line_total'] = round(
            $item['quantity'] * $item['unit_price'] * (1 - ($item['discount_pct'] ?? 0) / 100),
            2
        );
        return $this->db->insert('invoice_items', $item);
    }

    public function deleteItems(int $invoiceId): void
    {
        $this->db->delete('invoice_items', 'invoice_id = ?', [$invoiceId]);
    }

    // ── Recalculate totals ────────────────────────────────────────────────────

    public function recalculateTotals(int $invoiceId): void
    {
        $items    = $this->getItems($invoiceId);
        $invoice  = $this->db->fetchOne("SELECT tax_percentage FROM invoices WHERE invoice_id = ?", [$invoiceId]);
        $taxPct   = (float)($invoice['tax_percentage'] ?? DEFAULT_TAX_PCT);

        $subtotal = array_sum(array_column($items, 'line_total'));
        $taxAmt   = round($subtotal * $taxPct / 100, 2);
        $total    = round($subtotal + $taxAmt, 2);

        $this->db->update('invoices', [
            'subtotal'     => $subtotal,
            'tax_amount'   => $taxAmt,
            'total_amount' => $total,
        ], 'invoice_id = ?', [$invoiceId]);
    }

    // ── Status helpers ────────────────────────────────────────────────────────

    public function markAsPaid(int $invoiceId, float $amount): int
    {
        $invoice = $this->db->fetchOne(
            "SELECT total_amount FROM invoices WHERE invoice_id = ?", [$invoiceId]
        );
        $status = abs($amount - (float)($invoice['total_amount'] ?? 0)) < 0.01
            ? 'paid'
            : 'partially_paid';

        return $this->update($invoiceId, ['status' => $status, 'amount_paid' => $amount]);
    }

    // ── Stats ────────────────────────────────────────────────────────────────

    public function getMonthlyStats(): array
    {
        return $this->db->fetchOne(
            "SELECT
                COALESCE(SUM(total_amount),0)   AS total_revenue,
                COALESCE(SUM(amount_paid),0)    AS total_paid,
                COUNT(*)                         AS invoice_count
             FROM invoices
             WHERE MONTH(invoice_date) = MONTH(NOW())
               AND YEAR(invoice_date)  = YEAR(NOW())
               AND status != 'cancelled'"
        ) ?? [];
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function buildFilters(array $filters): array
    {
        $clauses = [];
        $params  = [];

        if (!empty($filters['status'])) {
            $clauses[] = 'i.status = ?';
            $params[]  = $filters['status'];
        }
        if (!empty($filters['customer_id'])) {
            $clauses[] = 'i.customer_id = ?';
            $params[]  = (int)$filters['customer_id'];
        }
        if (!empty($filters['date_from'])) {
            $clauses[] = 'i.invoice_date >= ?';
            $params[]  = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $clauses[] = 'i.invoice_date <= ?';
            $params[]  = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $like      = '%' . $filters['search'] . '%';
            $clauses[] = '(i.invoice_number LIKE ? OR c.full_name LIKE ?)';
            array_push($params, $like, $like);
        }

        $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';
        return [$where, $params];
    }
}
