<?php
/**
 * CustomerController — stub; full implementation in Prompt 3.
 */
class CustomerController
{
    private Customer $model;

    public function __construct()
    {
        $this->model = new Customer();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? '';
        $page   = Utils::currentPage();

        if ($search) {
            $data = $this->model->search($search, $page, $status);
        } else {
            $data = $this->model->getAll($page, $status);
        }

        $customers  = $data['rows'];
        $pagination = $data['pagination'];
        require VIEWS_PATH . '/customers/list.php';
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $customer = $this->model->findById($id);
        if (!$customer) { $this->notFound(); }
        $repairs  = $this->model->getRepairHistory($id, 5);
        $invoices = $this->model->getInvoices($id);
        $stats    = $this->model->getStats($id);
        Logger::log('viewed', 'customer', $id);
        require VIEWS_PATH . '/customers/view.php';
    }

    public function create(): void
    {
        Auth::requireAuth();
        $csrfToken = Auth::generateCSRFToken();
        require VIEWS_PATH . '/customers/create.php';
    }

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

        $id = $this->model->create($this->prepareData($_POST));
        Logger::log('created', 'customer', $id, null, ['full_name' => $_POST['full_name'] ?? '']);
        Utils::flashSuccess('Customer created successfully.');
        Utils::redirect('/customers/' . $id);
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $customer  = $this->model->findById($id);
        if (!$customer) { $this->notFound(); }
        $csrfToken = Auth::generateCSRFToken();
        require VIEWS_PATH . '/customers/edit.php';
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $customer = $this->model->findById($id);
        if (!$customer) { $this->notFound(); }

        $errors = $this->validateCustomer($_POST, $id);
        if ($errors) {
            $_SESSION['_form_errors'] = $errors;
            $_SESSION['_form_data']   = $_POST;
            Utils::redirect('/customers/' . $id . '/edit');
        }

        $data = $this->prepareData($_POST);
        Logger::log('updated', 'customer', $id, $customer, $data);
        $this->model->update($id, $data);

        Utils::flashSuccess('Customer updated.');
        Utils::redirect('/customers/' . $id);
    }

    public function destroy(int $id): void
    {
        Auth::requireRole('manager');
        Auth::checkCSRF();

        $customer = $this->model->findById($id);
        if (!$customer) { $this->notFound(); }

        Logger::log('deleted', 'customer', $id, $customer);
        $this->model->delete($id);

        Utils::flashSuccess('Customer deleted.');
        Utils::redirect('/customers');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validateCustomer(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        if (empty(trim($data['full_name'] ?? ''))) {
            $errors['full_name'] = 'Full name is required.';
        }
        if (empty(trim($data['city'] ?? ''))) {
            $errors['city'] = 'City is required.';
        }
        if (empty(trim($data['phone_mobile'] ?? ''))) {
            $errors['phone_mobile'] = 'Mobile phone is required.';
        }
        if (!empty($data['email']) && !Utils::isValidEmail($data['email'])) {
            $errors['email'] = 'Invalid email format.';
        }
        if (!empty($data['vat_number']) && !Utils::isValidVat($data['vat_number'])) {
            $errors['vat_number'] = 'Invalid VAT number format.';
        }
        if (!empty($data['tax_id']) && !Utils::isValidTaxId($data['tax_id'])) {
            $errors['tax_id'] = 'Invalid fiscal code format.';
        }

        return $errors;
    }

    private function prepareData(array $post): array
    {
        $firstName = Utils::sanitize($post['first_name'] ?? '');
        $lastName  = Utils::sanitize($post['last_name']  ?? '');
        $fullName  = Utils::sanitize($post['full_name']  ?? trim("$firstName $lastName"));

        return [
            'first_name'     => $firstName,
            'last_name'      => $lastName,
            'full_name'      => $fullName,
            'client_type'    => $post['client_type']    ?? 'individual',
            'address'        => Utils::sanitize($post['address']       ?? ''),
            'postal_code'    => Utils::sanitize($post['postal_code']   ?? ''),
            'city'           => Utils::sanitize($post['city']          ?? ''),
            'province'       => strtoupper(Utils::sanitize($post['province'] ?? '')),
            'phone_landline' => Utils::sanitizePhone($post['phone_landline'] ?? ''),
            'phone_mobile'   => Utils::sanitizePhone($post['phone_mobile']   ?? ''),
            'email'          => strtolower(trim($post['email'] ?? '')),
            'vat_number'     => strtoupper(Utils::sanitize($post['vat_number'] ?? '')),
            'tax_id'         => strtoupper(Utils::sanitize($post['tax_id']     ?? '')),
            'notes'          => Utils::sanitize($post['notes'] ?? ''),
            'status'         => $post['status'] ?? 'active',
            'customer_since' => !empty($post['customer_since']) ? $post['customer_since'] : null,
        ];
    }

    private function notFound(): never
    {
        http_response_code(404);
        require VIEWS_PATH . '/errors/404.php';
        exit;
    }
}
