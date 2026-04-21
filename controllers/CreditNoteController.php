<?php
class CreditNoteController
{
    private CreditNote $model;

    public function __construct()
    {
        $this->model = new CreditNote();
    }

    // ── GET /credit-notes ─────────────────────────────────────────────────────

    public function index(): void
    {
        Auth::requireAuth();

        $filters = [
            'search'    => $_GET['search']    ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to'   => $_GET['date_to']   ?? '',
        ];

        $page        = Utils::currentPage();
        $data        = $this->model->getAll($filters, $page);
        $creditNotes = $data['rows'];
        $pagination  = $data['pagination'];

        require VIEWS_PATH . '/credit-notes/list.php';
    }

    // ── GET /credit-notes/create ──────────────────────────────────────────────

    public function create(): void
    {
        Auth::requireAuth();

        $nextNumber = $this->model->getNextNumber();
        $csrfToken  = Auth::generateCSRFToken();
        $errors     = $_SESSION['_form_errors'] ?? [];
        $fd         = $_SESSION['_form_data']   ?? [];
        unset($_SESSION['_form_errors'], $_SESSION['_form_data']);

        require VIEWS_PATH . '/credit-notes/create.php';
    }

    // ── POST /credit-notes ────────────────────────────────────────────────────

    public function store(): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $errors = $this->validate($_POST);
        if ($errors) {
            $_SESSION['_form_errors'] = $errors;
            $_SESSION['_form_data']   = $_POST;
            Utils::redirect('/credit-notes/create');
        }

        $id = $this->model->create([
            'cn_number'        => (int)$_POST['cn_number'],
            'cn_date'          => $_POST['cn_date'],
            'company_name'     => Utils::sanitize($_POST['company_name']     ?? ''),
            'company_address'  => Utils::sanitize($_POST['company_address']  ?? ''),
            'company_phone'    => Utils::sanitize($_POST['company_phone']    ?? ''),
            'company_email'    => Utils::sanitize($_POST['company_email']    ?? ''),
            'company_vat'      => Utils::sanitize($_POST['company_vat']      ?? ''),
            'customer_name'    => Utils::sanitize($_POST['customer_name']    ?? ''),
            'customer_address' => Utils::sanitize($_POST['customer_address'] ?? ''),
            'customer_vat'     => Utils::sanitize($_POST['customer_vat']     ?? ''),
            'note'             => Utils::sanitize($_POST['note']             ?? ''),
            'created_by'       => Auth::id(),
        ]);

        foreach ($_POST['items'] ?? [] as $item) {
            if (empty(trim($item['description'] ?? ''))) continue;
            $this->model->addItem($id, $item);
        }

        Logger::log('created', 'credit_note', $id);
        Utils::flashSuccess('Credit Note #' . (int)$_POST['cn_number'] . ' created.');
        Utils::redirect('/credit-notes/' . $id);
    }

    // ── GET /credit-notes/:id ─────────────────────────────────────────────────

    public function show(int $id): void
    {
        Auth::requireAuth();
        $cn = $this->model->findById($id);
        if (!$cn) { $this->notFound(); }
        require VIEWS_PATH . '/credit-notes/view.php';
    }

    // ── GET /credit-notes/:id/edit ────────────────────────────────────────────

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $cn = $this->model->findById($id);
        if (!$cn) { $this->notFound(); }

        $csrfToken = Auth::generateCSRFToken();
        $errors    = $_SESSION['_form_errors'] ?? [];
        $fd        = $_SESSION['_form_data']   ?? $cn;
        unset($_SESSION['_form_errors'], $_SESSION['_form_data']);

        require VIEWS_PATH . '/credit-notes/edit.php';
    }

    // ── POST /credit-notes/:id ────────────────────────────────────────────────

    public function update(int $id): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $cn = $this->model->findById($id);
        if (!$cn) { $this->notFound(); }

        $errors = $this->validate($_POST);
        if ($errors) {
            $_SESSION['_form_errors'] = $errors;
            $_SESSION['_form_data']   = $_POST;
            Utils::redirect('/credit-notes/' . $id . '/edit');
        }

        $this->model->update($id, [
            'cn_number'        => (int)$_POST['cn_number'],
            'cn_date'          => $_POST['cn_date'],
            'company_name'     => Utils::sanitize($_POST['company_name']     ?? ''),
            'company_address'  => Utils::sanitize($_POST['company_address']  ?? ''),
            'company_phone'    => Utils::sanitize($_POST['company_phone']    ?? ''),
            'company_email'    => Utils::sanitize($_POST['company_email']    ?? ''),
            'company_vat'      => Utils::sanitize($_POST['company_vat']      ?? ''),
            'customer_name'    => Utils::sanitize($_POST['customer_name']    ?? ''),
            'customer_address' => Utils::sanitize($_POST['customer_address'] ?? ''),
            'customer_vat'     => Utils::sanitize($_POST['customer_vat']     ?? ''),
            'note'             => Utils::sanitize($_POST['note']             ?? ''),
        ]);

        $this->model->deleteItems($id);
        foreach ($_POST['items'] ?? [] as $item) {
            if (empty(trim($item['description'] ?? ''))) continue;
            $this->model->addItem($id, $item);
        }

        Logger::log('updated', 'credit_note', $id);
        Utils::flashSuccess('Credit Note #' . (int)$_POST['cn_number'] . ' updated.');
        Utils::redirect('/credit-notes/' . $id);
    }

    // ── POST /credit-notes/:id/delete ─────────────────────────────────────────

    public function destroy(int $id): void
    {
        Auth::requireRole('manager');
        Auth::checkCSRF();

        $cn = $this->model->findById($id);
        if (!$cn) { $this->notFound(); }

        $this->model->deleteItems($id);
        $this->model->delete($id);
        Logger::log('deleted', 'credit_note', $id);
        Utils::flashSuccess('Credit Note deleted.');
        Utils::redirect('/credit-notes');
    }

    // ── GET /credit-notes/:id/print ───────────────────────────────────────────

    public function printCN(int $id): void
    {
        Auth::requireAuth();
        $cn = $this->model->findById($id);
        if (!$cn) { $this->notFound(); }
        require VIEWS_PATH . '/credit-notes/print.php';
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['cn_number']) || !is_numeric($data['cn_number'])) {
            $errors['cn_number'] = 'CN number is required.';
        }
        if (empty($data['cn_date'])) {
            $errors['cn_date'] = 'Date is required.';
        }
        if (empty(trim($data['company_name'] ?? ''))) {
            $errors['company_name'] = 'Company name is required.';
        }
        if (empty(trim($data['customer_name'] ?? ''))) {
            $errors['customer_name'] = 'Customer name is required.';
        }
        return $errors;
    }

    private function notFound(): never
    {
        http_response_code(404);
        require VIEWS_PATH . '/errors/404.php';
        exit;
    }
}
