<?php
class DashboardController
{
    private Repair  $repairModel;
    private Invoice $invoiceModel;
    private Staff   $staffModel;
    private Customer $customerModel;

    public function __construct()
    {
        $this->repairModel   = new Repair();
        $this->invoiceModel  = new Invoice();
        $this->staffModel    = new Staff();
        $this->customerModel = new Customer();
    }

    // ── GET / ──────────────────────────────────────────────────────────────────

    public function index(): void
    {
        Auth::requireAuth();

        $stats         = $this->repairModel->getStatistics();
        $recentRepairs = $this->repairModel->getRecentRepairs(10);
        $readyPickup   = $this->repairModel->getReadyForPickup();
        $overdueItems  = $this->repairModel->getOverduePickups(7);
        $monthlyStats  = $this->invoiceModel->getMonthlyStats();
        $staffStats    = $this->staffModel->getRepairStats();
        $monthlyRev    = $this->repairModel->getMonthlyRevenue(12);

        $totalCustomers = $this->customerModel->count();
        $totalInvoices  = $this->invoiceModel->count();

        // Revenue this month split Private vs Colleague — same basis as the
        // Reports page: repair's own Actual Amount (counts as soon as priced,
        // no invoice required). "Private" = individual + company customers.
        // Attributed by Out/Delivery date rather than date_in — a repair
        // checked in months ago but only finished/collected this month should
        // count as this month's revenue, not the check-in month.
        // Uses COALESCE(date_out, collection_date): date_out is only set by
        // the app's own status-change flow, but CSV-imported repairs only
        // ever get collection_date populated — the same fallback already
        // used by the Repairs list's "Out Date/Delivery" column, so revenue
        // matches what's actually shown on screen.
        $db = Database::getInstance();
        $clientIncomeRow = $db->fetchOne(
            "SELECT
                COALESCE(SUM(CASE WHEN c.client_type =  'colleague' THEN r.actual_amount END),0) AS colleague_income,
                COALESCE(SUM(CASE WHEN c.client_type <> 'colleague' THEN r.actual_amount END),0) AS private_income
             FROM repairs r
             JOIN customers c ON c.customer_id = r.customer_id
             WHERE MONTH(COALESCE(r.date_out, r.collection_date)) = MONTH(NOW())
               AND YEAR(COALESCE(r.date_out, r.collection_date)) = YEAR(NOW())",
            []
        ) ?? [];
        $privateIncome   = (float)($clientIncomeRow['private_income']   ?? 0);
        $colleagueIncome = (float)($clientIncomeRow['colleague_income'] ?? 0);

        $clientInvoicedRow = $db->fetchOne(
            "SELECT
                COALESCE(SUM(CASE WHEN c.client_type =  'colleague' THEN i.total_amount END),0) AS colleague_billed,
                COALESCE(SUM(CASE WHEN c.client_type <> 'colleague' THEN i.total_amount END),0) AS private_billed
             FROM invoices i
             JOIN customers c ON c.customer_id = i.customer_id
             WHERE MONTH(i.invoice_date) = MONTH(NOW()) AND YEAR(i.invoice_date) = YEAR(NOW())
               AND i.status != 'cancelled'",
            []
        ) ?? [];
        $privateBilled   = (float)($clientInvoicedRow['private_billed']   ?? 0);
        $colleagueBilled = (float)($clientInvoicedRow['colleague_billed'] ?? 0);

        // Stock & sales KPIs
        $stockStats = (new Product())->getStockStats();
        $salesStats = (new Sale())->getMonthlyStats();

        require VIEWS_PATH . '/dashboard/index.php';
    }
}
