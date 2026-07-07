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

        // Revenue this month split by client type
        $db = Database::getInstance();
        $typeRevRows = $db->fetchAll(
            "SELECT c.client_type,
                    COALESCE(SUM(i.total_amount), 0) AS revenue,
                    COALESCE(SUM(i.amount_paid),  0) AS paid,
                    COUNT(*) AS cnt
             FROM invoices i
             JOIN customers c ON c.customer_id = i.customer_id
             WHERE MONTH(i.invoice_date) = MONTH(NOW())
               AND YEAR(i.invoice_date)  = YEAR(NOW())
               AND i.status != 'cancelled'
             GROUP BY c.client_type",
            []
        );
        $revenueByType = [];
        foreach ($typeRevRows as $row) {
            $revenueByType[$row['client_type']] = $row;
        }

        // Stock & sales KPIs
        $stockStats = (new Product())->getStockStats();
        $salesStats = (new Sale())->getMonthlyStats();

        require VIEWS_PATH . '/dashboard/index.php';
    }
}
