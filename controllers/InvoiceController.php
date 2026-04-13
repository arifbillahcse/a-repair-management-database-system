<?php
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

    // ── GET /invoices ─────────────────────────────────────────────────────────

    public function index(): void
    {
        Auth::requireAuth();
        $filters = [
            'status'      => $_GET['status']      ?? '',
            'search'      => $_GET['search']      ?? '',
            'date_from'   => $_GET['date_from']   ?? '',
            'date_to'     => $_GET['date_to']     ?? '',
            'customer_id' => (int)($_GET['customer_id'] ?? 0) ?: null,
        ];
        $page       = max(1, (int)($_GET['page'] ?? 1));
        $data       = $this->model->getAll(array_filter($filters, fn($v) => $v !== null && $v !== ''), $page);
        $invoices   = $data['rows'];
        $pagination = $data['pagination'];
        $monthStats = $this->model->getMonthlyStats();

        require VIEWS_PATH . '/invoices/list.php';
    }

    // ── GET /invoices/:id ─────────────────────────────────────────────────────

    public function show(int $id): void
    {
        Auth::requireAuth();
        $invoice = $this->model->findById($id);
        if (!$invoice) $this->notFound();

        require VIEWS_PATH . '/invoices/view.php';
    }

    // ── GET /repairs/:id/invoice ──────────────────────────────────────────────

    public function createFromRepair(int $repairId): void
    {
        Auth::requireAuth();
        $repair = $this->repairModel->findById($repairId);
        if (!$repair) $this->notFound();

        $invoiceNumber = $this->model->generateInvoiceNumber();
        $csrfToken     = Auth::generateCSRFToken();

        require VIEWS_PATH . '/invoices/create.php';
    }

    // ── POST /invoices ────────────────────────────────────────────────────────

    public function store(): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        // Use pre-generated number from form (avoids double-increment)
        $invoiceNumber = Utils::sanitize($_POST['invoice_number'] ?? '');
        if (empty($invoiceNumber)) {
            $invoiceNumber = $this->model->generateInvoiceNumber();
        }

        $id = $this->model->create([
            'repair_id'      => !empty($_POST['repair_id']) ? (int)$_POST['repair_id'] : null,
            'customer_id'    => (int)$_POST['customer_id'],
            'invoice_number' => $invoiceNumber,
            'invoice_date'   => $_POST['invoice_date'] ?? date('Y-m-d'),
            'due_date'       => !empty($_POST['due_date']) ? $_POST['due_date'] : null,
            'tax_percentage' => (float)($_POST['tax_percentage'] ?? DEFAULT_TAX_PCT),
            'status'         => 'draft',
            'notes'          => Utils::sanitize($_POST['notes'] ?? ''),
            'created_by'     => Auth::id(),
        ]);

        foreach ($_POST['items'] ?? [] as $item) {
            if (empty($item['description'])) continue;
            $this->model->addItem($id, [
                'description'    => Utils::sanitize($item['description']),
                'quantity'       => max(0.001, (float)($item['quantity'] ?? 1)),
                'unit_price'     => max(0, (float)($item['unit_price'] ?? 0)),
                'tax_percentage' => (float)($item['tax_pct'] ?? DEFAULT_TAX_PCT),
                'discount_pct'   => min(100, max(0, (float)($item['discount_pct'] ?? 0))),
                'sort_order'     => (int)($item['sort_order'] ?? 0),
            ]);
        }

        $this->model->recalculateTotals($id);

        Logger::log('created', 'invoice', $id);
        Utils::flash('success', 'Invoice ' . $invoiceNumber . ' created.');
        Utils::redirect('/invoices/' . $id);
    }

    // ── POST /invoices/:id/paid ───────────────────────────────────────────────

    public function markPaid(int $id): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $invoice = $this->model->findById($id);
        if (!$invoice) $this->notFound();

        $amount = (float)($_POST['amount_paid'] ?? 0);
        $this->model->markAsPaid($id, $amount);

        Logger::log('updated', 'invoice', $id, null, ['status' => 'paid', 'amount_paid' => $amount]);
        Utils::flash('success', 'Payment recorded.');
        Utils::redirect('/invoices/' . $id);
    }

    // ── POST /invoices/:id/send ───────────────────────────────────────────────

    public function markSent(int $id): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $invoice = $this->model->findById($id);
        if (!$invoice) $this->notFound();

        $this->model->update($id, ['status' => 'sent']);
        Logger::log('updated', 'invoice', $id, null, ['status' => 'sent']);
        Utils::flash('success', 'Invoice marked as sent.');
        Utils::redirect('/invoices/' . $id);
    }

    // ── GET /invoices/:id/print ───────────────────────────────────────────────

    public function printInvoice(int $id): void
    {
        Auth::requireAuth();
        $invoice = $this->model->findById($id);
        if (!$invoice) $this->notFound();

        $company = Database::getInstance()->fetchOne("SELECT * FROM company_settings LIMIT 1") ?? [];
        require VIEWS_PATH . '/invoices/print.php';
    }

    // ── POST /invoices/:id/delete ─────────────────────────────────────────────

    public function destroy(int $id): void
    {
        Auth::requireRole('manager');
        Auth::checkCSRF();

        Logger::log('deleted', 'invoice', $id);
        $this->model->delete($id);

        Utils::flash('success', 'Invoice deleted.');
        Utils::redirect('/invoices');
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function notFound(): never
    {
        http_response_code(404);
        require VIEWS_PATH . '/errors/404.php';
        exit;
    }
}
