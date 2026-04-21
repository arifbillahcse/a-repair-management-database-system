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

        require VIEWS_PATH . '/dashboard/index.php';
    }
}
