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

        // Revenue this month split Individual vs Colleague — same basis as
        // the Reports page: repair's own Actual Amount (counts as soon as
        // priced, no invoice required). "Individual" is strictly
        // client_type = 'individual' — Company customers are not counted in
        // either bucket here.
        // Attributed strictly by Completed/Out date (date_out), not date_in
        // and NOT collection_date (Expected Out is only a plan, not a fact —
        // a repair without a real date_out yet doesn't count at all).
        $db = Database::getInstance();
        $clientIncomeRow = $db->fetchOne(
            "SELECT
                COALESCE(SUM(CASE WHEN c.client_type = 'colleague'  THEN r.actual_amount END),0) AS colleague_income,
                COALESCE(SUM(CASE WHEN c.client_type = 'individual' THEN r.actual_amount END),0) AS individual_income
             FROM repairs r
             JOIN customers c ON c.customer_id = r.customer_id
             WHERE MONTH(r.date_out) = MONTH(NOW()) AND YEAR(r.date_out) = YEAR(NOW())",
            []
        ) ?? [];
        $individualIncome = (float)($clientIncomeRow['individual_income'] ?? 0);
        $colleagueIncome  = (float)($clientIncomeRow['colleague_income']  ?? 0);

        $clientInvoicedRow = $db->fetchOne(
            "SELECT
                COALESCE(SUM(CASE WHEN c.client_type = 'colleague'  THEN i.total_amount END),0) AS colleague_billed,
                COALESCE(SUM(CASE WHEN c.client_type = 'individual' THEN i.total_amount END),0) AS individual_billed
             FROM invoices i
             JOIN customers c ON c.customer_id = i.customer_id
             WHERE MONTH(i.invoice_date) = MONTH(NOW()) AND YEAR(i.invoice_date) = YEAR(NOW())
               AND i.status != 'cancelled'",
            []
        ) ?? [];
        $individualBilled = (float)($clientInvoicedRow['individual_billed'] ?? 0);
        $colleagueBilled  = (float)($clientInvoicedRow['colleague_billed']  ?? 0);

        // Stock & sales KPIs
        $stockStats = (new Product())->getStockStats();
        $salesStats = (new Sale())->getMonthlyStats();

        require VIEWS_PATH . '/dashboard/index.php';
    }
}
