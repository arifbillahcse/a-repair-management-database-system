<?php
class CustomerController
{
    private Customer $model;

    public function __construct()
    {
        $this->model = new Customer();
    }

    // ── GET /customers ────────────────────────────────────────────────────────

    public function index(): void
    {
        Auth::requireAuth();

        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? '';
        $sort   = $_GET['sort']   ?? 'full_name';
        $dir    = $_GET['dir']    ?? 'ASC';
        $page   = Utils::currentPage();

        if ($search !== '') {
            $data = $this->model->search($search, $page, $status, $sort, $dir);
        } else {
            $data = $this->model->getAll($page, $status, $sort, $dir);
        }

        $customers  = $data['rows'];
        $pagination = $data['pagination'];
        $counts     = $this->model->getCounts();

        require VIEWS_PATH . '/customers/list.php';
    }

    // ── GET /customers/:id ────────────────────────────────────────────────────

    public function show(int $id): void
    {
        Auth::requireAuth();

        $customer = $this->model->findById($id);
        if (!$customer) {
            $this->notFound();
        }

        $repairs  = $this->model->getRepairHistory($id, 5);
        $invoices = $this->model->getInvoices($id);
        $stats    = $this->model->getStats($id);

        Logger::log('viewed', 'customer', $id);
        require VIEWS_PATH . '/customers/view.php';
    }

    // ── GET /customers/create ─────────────────────────────────────────────────

    public function create(): void
    {
        Auth::requireAuth();

        $csrfToken  = Auth::generateCSRFToken();
        $formErrors = $_SESSION['_form_errors'] ?? [];
        $formData   = $_SESSION['_form_data']   ?? [];
        unset($_SESSION['_form_errors'], $_SESSION['_form_data']);

        require VIEWS_PATH . '/customers/create.php';
    }

    // ── POST /customers ───────────────────────────────────────────────────────

    public function store(): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $errors = $this->validateCustomer($_POST);

        if ($errors) {
            $_SESSION['_form_errors'] = $errors;
            $_SESSION['_form_data']   = $_POST;
            Utils::redirect('/customers/create');
        }

        $data = $this->prepareData($_POST);
        $id   = $this->model->create($data);

        Logger::log('created', 'customer', $id, null, [
            'full_name' => $data['full_name'],
            'email'     => $data['email'],
        ]);

        Utils::flashSuccess("Customer \"{$data['full_name']}\" created successfully.");

        // "Save & Add Another" button
        if (($_POST['action'] ?? '') === 'save_and_new') {
            Utils::redirect('/customers/create');
        }

        Utils::redirect('/customers/' . $id);
    }

    // ── GET /customers/:id/edit ───────────────────────────────────────────────

    public function edit(int $id): void
    {
        Auth::requireAuth();

        $customer = $this->model->findById($id);
        if (!$customer) {
            $this->notFound();
        }

        $csrfToken  = Auth::generateCSRFToken();
        $formErrors = $_SESSION['_form_errors'] ?? [];
        // Use POST data on validation failure, otherwise load from DB
        $formData   = $_SESSION['_form_data']   ?? $customer;
        unset($_SESSION['_form_errors'], $_SESSION['_form_data']);

        require VIEWS_PATH . '/customers/edit.php';
    }

    // ── POST /customers/:id ───────────────────────────────────────────────────

    public function update(int $id): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $customer = $this->model->findById($id);
        if (!$customer) {
            $this->notFound();
        }

        $errors = $this->validateCustomer($_POST, $id);

        if ($errors) {
            $_SESSION['_form_errors'] = $errors;
            $_SESSION['_form_data']   = $_POST;
            Utils::redirect('/customers/' . $id . '/edit');
        }

        $data = $this->prepareData($_POST);

        Logger::log('updated', 'customer', $id, $customer, $data);
        $this->model->update($id, $data);

        Utils::flashSuccess("Customer \"{$data['full_name']}\" updated.");
        Utils::redirect('/customers/' . $id);
    }

    // ── POST /customers/:id/delete ────────────────────────────────────────────

    public function destroy(int $id): void
    {
        Auth::requireRole('admin');
        Auth::checkCSRF();

        $customer = $this->model->findById($id);
        if (!$customer) {
            $this->notFound();
        }

        $db = Database::getInstance();

        // Get all repairs belonging to this customer
        $repairs = $db->fetchAll(
            "SELECT repair_id FROM repairs WHERE customer_id = ?", [$id]
        );

        // Delete repair photos from disk and then each repair
        foreach ($repairs as $repair) {
            $photoDir = UPLOAD_PATH . '/photos/repair_' . $repair['repair_id'];
            if (is_dir($photoDir)) {
                array_map('unlink', glob($photoDir . '/*') ?: []);
                @rmdir($photoDir);
            }
        }

        // Delete all repairs for this customer
        if ($repairs) {
            $db->execute(
                "DELETE FROM repairs WHERE customer_id = ?", [$id]
            );
        }

        // Hard-delete the customer
        Logger::log('deleted', 'customer', $id, $customer);
        $this->model->delete($id);

        Utils::flashSuccess("Customer \"{$customer['full_name']}\" and all their repairs have been permanently deleted.");
        Utils::redirect('/customers');
    }

    // ── GET /customers/export ─────────────────────────────────────────────────

    public function export(): void
    {
        Auth::requireRole('manager');

        $status = $_GET['status'] ?? '';
        $rows   = $this->model->getForExport($status);

        $filename = 'customers_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        // UTF-8 BOM for Excel compatibility
        echo "\xEF\xBB\xBF";

        $fp = fopen('php://output', 'w');

        // Header row
        fputcsv($fp, [
            'ID', 'Full Name', 'First Name', 'Last Name', 'Type',
            'Address', 'Postal Code', 'City', 'Province',
            'Landline', 'Mobile', 'Email',
            'VAT Number', 'Tax ID', 'Status', 'Customer Since', 'Created At',
        ]);

        foreach ($rows as $row) {
            fputcsv($fp, [
                $row['customer_id'],
                $row['full_name'],
                $row['first_name']     ?? '',
                $row['last_name']      ?? '',
                $row['client_type'],
                $row['address']        ?? '',
                $row['postal_code']    ?? '',
                $row['city']           ?? '',
                $row['province']       ?? '',
                $row['phone_landline'] ?? '',
                $row['phone_mobile']   ?? '',
                $row['email']          ?? '',
                $row['vat_number']     ?? '',
                $row['tax_id']         ?? '',
                $row['status'],
                $row['customer_since'] ?? '',
                $row['created_at'],
            ]);
        }

        fclose($fp);
        Logger::log('exported', 'customer', null, null, ['count' => count($rows)]);
        exit;
    }

    // ── AJAX: autocomplete ────────────────────────────────────────────────────

    public function autocomplete(): void
    {
        Auth::requireAuth();

        $q    = trim($_GET['q'] ?? '');
        $rows = $this->model->autocomplete($q);

        header('Content-Type: application/json');
        echo json_encode($rows);
        exit;
    }

    // ── Validation ────────────────────────────────────────────────────────────

    private function validateCustomer(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        // Required: full_name
        if (empty(trim($data['full_name'] ?? ''))) {
            $errors['full_name'] = 'Full name / company name is required.';
        }

        // Required: city
        if (empty(trim($data['city'] ?? ''))) {
            $errors['city'] = 'City is required.';
        }

        // Required: mobile phone
        if (empty(trim($data['phone_mobile'] ?? ''))) {
            $errors['phone_mobile'] = 'Mobile phone is required.';
        }

        // Email format (if provided)
        if (!empty($data['email']) && !Utils::isValidEmail(trim($data['email']))) {
            $errors['email'] = 'Please enter a valid email address.';
        }

        // VAT format (Italian, if provided)
        if (!empty($data['vat_number']) && !Utils::isValidVat($data['vat_number'])) {
            $errors['vat_number'] = 'Invalid VAT number format (11 digits expected).';
        }

        // Tax ID format (if provided)
        if (!empty($data['tax_id']) && !Utils::isValidTaxId($data['tax_id'])) {
            $errors['tax_id'] = 'Invalid fiscal code format (16 alphanumeric characters).';
        }

        // Postal code format (if provided)
        if (!empty($data['postal_code']) && !Utils::isValidPostalCode($data['postal_code'])) {
            $errors['postal_code'] = 'Postal code must be 5 digits.';
        }

        // Duplicate email warning (soft — controller can decide to warn rather than block)
        if (!empty($data['email'])) {
            $emailClean = strtolower(trim($data['email']));
            if ($this->model->emailExists($emailClean, $excludeId)) {
                $errors['email'] = 'This email address is already registered to another customer.';
            }
        }

        return $errors;
    }

    // ── Data preparation ──────────────────────────────────────────────────────

    private function prepareData(array $post): array
    {
        return [
            'full_name'      => Utils::sanitize($post['full_name'] ?? ''),
            'client_type'    => in_array($post['client_type'] ?? '', ['individual','company','freelancer'])
                                    ? $post['client_type']
                                    : 'individual',
            'address'        => Utils::sanitize($post['address']        ?? ''),
            'postal_code'    => Utils::sanitize($post['postal_code']    ?? ''),
            'city'           => Utils::sanitize($post['city']           ?? ''),
            'province'       => strtoupper(Utils::sanitize(substr($post['province'] ?? '', 0, 5))),
            'phone_landline' => Utils::sanitizePhone($post['phone_landline'] ?? ''),
            'phone_mobile'   => Utils::sanitizePhone($post['phone_mobile']   ?? ''),
            'email'          => strtolower(trim($post['email'] ?? '')),
            'vat_number'     => strtoupper(preg_replace('/\s/', '', $post['vat_number'] ?? '')),
            'tax_id'         => strtoupper(preg_replace('/\s/', '', $post['tax_id']     ?? '')),
            'notes'          => Utils::sanitize($post['notes'] ?? ''),
            'status'         => in_array($post['status'] ?? '', ['active','inactive'])
                                    ? $post['status']
                                    : 'active',
            'customer_since' => !empty($post['customer_since']) ? $post['customer_since'] : null,
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function notFound(): never
    {
        http_response_code(404);
        require VIEWS_PATH . '/errors/404.php';
        exit;
    }
}
