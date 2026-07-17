<?php
/**
 * PackingListController — standalone "Documento di Trasporto" (DDT) / packing list.
 *
 * Fully manual entry; the only structured input is the company header, which
 * the user picks from the businesses table (MSP / Folmix / Tracia …). Nothing
 * here reads from or writes to customers / repairs / sales.
 */
class PackingListController
{
    private PackingList $model;

    public function __construct()
    {
        $this->model = new PackingList();
    }

    // ── GET /packing-lists ─────────────────────────────────────────────────────

    public function index(): void
    {
        Auth::requireAuth();

        $filters = [
            'search'    => $_GET['search']    ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to'   => $_GET['date_to']   ?? '',
        ];

        $page         = Utils::currentPage();
        $data         = $this->model->getAll($filters, $page);
        $packingLists = $data['rows'];
        $pagination   = $data['pagination'];

        require VIEWS_PATH . '/packing-lists/list.php';
    }

    // ── GET /packing-lists/create ──────────────────────────────────────────────

    public function create(): void
    {
        Auth::requireAuth();

        $nextNumber = $this->model->getNextNumber();
        $csrfToken  = Auth::generateCSRFToken();
        $businesses = (new Business())->allActive();
        $errors     = $_SESSION['_form_errors'] ?? [];
        $fd         = $_SESSION['_form_data']   ?? [];
        unset($_SESSION['_form_errors'], $_SESSION['_form_data']);

        require VIEWS_PATH . '/packing-lists/create.php';
    }

    // ── POST /packing-lists ────────────────────────────────────────────────────

    public function store(): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $errors = $this->validate($_POST);
        if ($errors) {
            $_SESSION['_form_errors'] = $errors;
            $_SESSION['_form_data']   = $_POST;
            Utils::redirect('/packing-lists/create');
        }

        $id = $this->model->create($this->collect($_POST) + ['created_by' => Auth::id() ?: null]);

        foreach ($_POST['items'] ?? [] as $item) {
            if (trim($item['description'] ?? '') === '' && trim($item['quantity'] ?? '') === '') {
                continue;
            }
            $this->model->addItem($id, $item);
        }

        Logger::log('created', 'packing_list', $id);
        Utils::flashSuccess('Packing List #' . (int)$_POST['pl_number'] . ' created.');
        Utils::redirect('/packing-lists/' . $id);
    }

    // ── GET /packing-lists/:id ─────────────────────────────────────────────────

    public function show(int $id): void
    {
        Auth::requireAuth();
        $pl = $this->model->findById($id);
        if (!$pl) { $this->notFound(); }
        require VIEWS_PATH . '/packing-lists/view.php';
    }

    // ── GET /packing-lists/:id/edit ────────────────────────────────────────────

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $pl = $this->model->findById($id);
        if (!$pl) { $this->notFound(); }

        $csrfToken  = Auth::generateCSRFToken();
        $businesses = (new Business())->allActive();
        $errors     = $_SESSION['_form_errors'] ?? [];
        $fd         = $_SESSION['_form_data']   ?? $pl;
        unset($_SESSION['_form_errors'], $_SESSION['_form_data']);

        require VIEWS_PATH . '/packing-lists/edit.php';
    }

    // ── POST /packing-lists/:id ────────────────────────────────────────────────

    public function update(int $id): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $pl = $this->model->findById($id);
        if (!$pl) { $this->notFound(); }

        $errors = $this->validate($_POST);
        if ($errors) {
            $_SESSION['_form_errors'] = $errors;
            $_SESSION['_form_data']   = $_POST;
            Utils::redirect('/packing-lists/' . $id . '/edit');
        }

        $this->model->update($id, $this->collect($_POST));

        $this->model->deleteItems($id);
        foreach ($_POST['items'] ?? [] as $item) {
            if (trim($item['description'] ?? '') === '' && trim($item['quantity'] ?? '') === '') {
                continue;
            }
            $this->model->addItem($id, $item);
        }

        Logger::log('updated', 'packing_list', $id);
        Utils::flashSuccess('Packing List #' . (int)$_POST['pl_number'] . ' updated.');
        Utils::redirect('/packing-lists/' . $id);
    }

    // ── POST /packing-lists/:id/delete ─────────────────────────────────────────

    public function destroy(int $id): void
    {
        Auth::requireRole('manager');
        Auth::checkCSRF();

        $pl = $this->model->findById($id);
        if (!$pl) { $this->notFound(); }

        $this->model->deleteItems($id);
        $this->model->delete($id);
        Logger::log('deleted', 'packing_list', $id);
        Utils::flashSuccess('Packing List deleted.');
        Utils::redirect('/packing-lists');
    }

    // ── GET /packing-lists/:id/print ───────────────────────────────────────────

    public function printPL(int $id): void
    {
        Auth::requireAuth();
        $pl = $this->model->findById($id);
        if (!$pl) { $this->notFound(); }
        require VIEWS_PATH . '/packing-lists/print.php';
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /** Map the posted form into the model's column set. */
    private function collect(array $p): array
    {
        $transportBy = ($p['transport_by'] ?? 'cedente') === 'cessionario' ? 'cessionario' : 'cedente';
        $deliveryBy  = in_array($p['delivery_by'] ?? '', ['cedente', 'cessionario'], true) ? $p['delivery_by'] : '';
        $account     = in_array($p['account_type'] ?? '', ['in_conto', 'a_saldo'], true) ? $p['account_type'] : '';

        return [
            'pl_number'        => (int)$p['pl_number'],
            'pl_date'          => $p['pl_date'],
            'transport_by'     => $transportBy,
            'company_name'     => Utils::sanitize($p['company_name']     ?? ''),
            'company_address'  => Utils::sanitize($p['company_address']  ?? ''),
            'company_phone'    => Utils::sanitize($p['company_phone']    ?? ''),
            'company_email'    => Utils::sanitize($p['company_email']    ?? ''),
            'company_vat'      => Utils::sanitize($p['company_vat']      ?? ''),
            'company_tax_id'   => Utils::sanitize($p['company_tax_id']   ?? ''),
            'customer_name'    => Utils::sanitize($p['customer_name']    ?? ''),
            'customer_address' => Utils::sanitize($p['customer_address'] ?? ''),
            'customer_vat'     => Utils::sanitize($p['customer_vat']     ?? ''),
            'destination'      => Utils::sanitize($p['destination']      ?? ''),
            'causale'          => Utils::sanitize($p['causale']          ?? ''),
            'order_number'     => Utils::sanitize($p['order_number']     ?? ''),
            'order_date'       => !empty($p['order_date']) ? $p['order_date'] : null,
            'account_type'     => $account,
            'aspetto'          => Utils::sanitize($p['aspetto']          ?? ''),
            'n_colli'          => Utils::sanitize($p['n_colli']          ?? ''),
            'peso_kg'          => Utils::sanitize($p['peso_kg']          ?? ''),
            'porto'            => Utils::sanitize($p['porto']            ?? ''),
            'delivery_by'      => $deliveryBy,
            'transport_date'   => !empty($p['transport_date']) ? $p['transport_date'] : null,
            'transport_time'   => Utils::sanitize($p['transport_time']   ?? ''),
            'carrier'          => Utils::sanitize($p['carrier']          ?? ''),
            'notes'            => Utils::sanitize($p['notes']            ?? ''),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['pl_number']) || !is_numeric($data['pl_number'])) {
            $errors['pl_number'] = 'Document number is required.';
        }
        if (empty($data['pl_date'])) {
            $errors['pl_date'] = 'Date is required.';
        }
        if (empty(trim($data['company_name'] ?? ''))) {
            $errors['company_name'] = 'Sender (Cedente) company is required.';
        }
        if (empty(trim($data['customer_name'] ?? ''))) {
            $errors['customer_name'] = 'Recipient (Cessionario) name is required.';
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
