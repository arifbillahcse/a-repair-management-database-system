<?php
/**
 * InvoiceController — stub; full implementation in Prompt 5.
 */
class InvoiceController
{
    private Invoice  $model;
    private Repair   $repairModel;
    private Customer $customerModel;

    public function __construct()
    {
        $this->model         = new Invoice();
        $this->repairModel   = new Repair();
        $this->customerModel = new Customer();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $filters = [
            'status'    => $_GET['status']    ?? '',
            'search'    => $_GET['search']    ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to'   => $_GET['date_to']   ?? '',
        ];
        $page        = Utils::currentPage();
        $data        = $this->model->getAll($filters, $page);
        $invoices    = $data['rows'];
        $pagination  = $data['pagination'];
        $monthStats  = $this->model->getMonthlyStats();

        require VIEWS_PATH . '/invoices/list.php';
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $invoice = $this->model->findById($id);
        if (!$invoice) { $this->notFound(); }
        require VIEWS_PATH . '/invoices/view.php';
    }

    public function createFromRepair(int $repairId): void
    {
        Auth::requireAuth();
        $repair = $this->repairModel->findById($repairId);
        if (!$repair) { $this->notFound(); }

        $invoiceNumber = $this->model->generateInvoiceNumber();
        $csrfToken     = Auth::generateCSRFToken();
        require VIEWS_PATH . '/invoices/create.php';
    }

    public function store(): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $id = $this->model->create([
            'repair_id'      => !empty($_POST['repair_id'])   ? (int)$_POST['repair_id']   : null,
            'customer_id'    => (int)$_POST['customer_id'],
            'invoice_number' => $this->model->generateInvoiceNumber(),
            'invoice_date'   => $_POST['invoice_date'] ?? date('Y-m-d'),
            'due_date'       => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
            'tax_percentage' => (float)($_POST['tax_percentage'] ?? DEFAULT_TAX_PCT),
            'status'         => 'draft',
            'notes'          => Utils::sanitize($_POST['notes'] ?? ''),
            'created_by'     => Auth::id(),
        ]);

        // Save line items
        foreach ($_POST['items'] ?? [] as $item) {
            if (empty($item['description'])) continue;
            $this->model->addItem($id, [
                'description'    => Utils::sanitize($item['description']),
                'quantity'       => (float)($item['quantity'] ?? 1),
                'unit_price'     => (float)($item['unit_price'] ?? 0),
                'tax_percentage' => (float)($item['tax_percentage'] ?? DEFAULT_TAX_PCT),
                'discount_pct'   => (float)($item['discount_pct'] ?? 0),
                'sort_order'     => (int)($item['sort_order'] ?? 0),
            ]);
        }

        $this->model->recalculateTotals($id);

        Logger::log('created', 'invoice', $id);
        Utils::flashSuccess('Invoice created.');
        Utils::redirect('/invoices/' . $id);
    }

    public function markPaid(int $id): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $amount = (float)($_POST['amount_paid'] ?? 0);
        $this->model->markAsPaid($id, $amount);

        Logger::log('updated', 'invoice', $id, null, ['status' => 'paid']);
        Utils::flashSuccess('Invoice marked as paid.');
        Utils::redirect('/invoices/' . $id);
    }

    public function destroy(int $id): void
    {
        Auth::requireRole('manager');
        Auth::checkCSRF();

        Logger::log('deleted', 'invoice', $id);
        $this->model->delete($id);

        Utils::flashSuccess('Invoice deleted.');
        Utils::redirect('/invoices');
    }

    private function notFound(): never
    {
        http_response_code(404);
        require VIEWS_PATH . '/errors/404.php';
        exit;
    }
}
