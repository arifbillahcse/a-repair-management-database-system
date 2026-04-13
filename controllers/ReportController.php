<?php
class ReportController
{
    private Repair   $repairModel;
    private Invoice  $invoiceModel;
    private Customer $customerModel;
    private Staff    $staffModel;

    public function __construct()
    {
        $this->repairModel   = new Repair();
        $this->invoiceModel  = new Invoice();
        $this->customerModel = new Customer();
        $this->staffModel    = new Staff();
    }

    // ── GET /reports ───────────────────────────────────────────────────────────

    public function index(): void
    {
        Auth::requireRole('manager');

        $db = Database::getInstance();

        // ── Repair stats ──────────────────────────────────────────────────────
        $repairStats  = $this->repairModel->getStatistics();
        $statusCounts = $this->repairModel->getStatusCounts();
        $monthlyRev   = $this->repairModel->getMonthlyRevenue(12);
        $monthStats   = $this->invoiceModel->getMonthlyStats();
        $staffStats   = $this->staffModel->getRepairStats();

        // ── Year-to-date revenue ──────────────────────────────────────────────
        $ytd = $db->fetchOne(
            "SELECT COALESCE(SUM(total_amount),0) AS revenue,
                    COALESCE(SUM(amount_paid),0)  AS paid,
                    COUNT(*)                       AS count
             FROM invoices
             WHERE YEAR(invoice_date) = YEAR(NOW())
               AND status != 'cancelled'"
        ) ?? [];

        // ── Top 10 customers by billed amount ─────────────────────────────────
        $topCustomers = $db->fetchAll(
            "SELECT c.customer_id, c.full_name,
                    COUNT(DISTINCT r.repair_id)    AS total_repairs,
                    COALESCE(SUM(i.total_amount),0) AS total_billed,
                    COALESCE(SUM(i.amount_paid),0)  AS total_paid
             FROM customers c
             LEFT JOIN invoices i ON i.customer_id = c.customer_id AND i.status != 'cancelled'
             LEFT JOIN repairs  r ON r.customer_id = c.customer_id
             GROUP BY c.customer_id
             HAVING total_billed > 0
             ORDER BY total_billed DESC
             LIMIT 10"
        );

        // ── Overdue invoices ──────────────────────────────────────────────────
        $overdueInvoices = $db->fetchAll(
            "SELECT i.invoice_id, i.invoice_number, i.due_date, i.total_amount, i.amount_paid,
                    c.full_name AS customer_name,
                    DATEDIFF(CURDATE(), i.due_date) AS days_overdue
             FROM invoices i
             JOIN customers c ON c.customer_id = i.customer_id
             WHERE i.status IN ('sent','overdue')
               AND i.due_date < CURDATE()
             ORDER BY days_overdue DESC
             LIMIT 20"
        );

        // ── Average repair time (completed repairs, last 90 days) ─────────────
        $avgTime = $db->fetchOne(
            "SELECT ROUND(AVG(DATEDIFF(date_out, date_in)), 1) AS avg_days,
                    MIN(DATEDIFF(date_out, date_in))           AS min_days,
                    MAX(DATEDIFF(date_out, date_in))           AS max_days
             FROM repairs
             WHERE status IN ('completed','collected')
               AND date_out IS NOT NULL
               AND date_in >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)"
        ) ?? [];

        // ── Repairs by month (for chart, last 12 months) ──────────────────────
        $repairsByMonth = $db->fetchAll(
            "SELECT DATE_FORMAT(date_in,'%Y-%m') AS month, COUNT(*) AS count
             FROM repairs
             WHERE date_in >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             GROUP BY month ORDER BY month"
        );

        require VIEWS_PATH . '/reports/index.php';
    }
}
