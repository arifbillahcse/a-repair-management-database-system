<?php
/**
 * RepairController — stub wired to the router.
 * Full implementation delivered in Prompt 4.
 */
class RepairController
{
    private Repair   $model;
    private Customer $customerModel;
    private Staff    $staffModel;

    public function __construct()
    {
        $this->model         = new Repair();
        $this->customerModel = new Customer();
        $this->staffModel    = new Staff();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $filters = [
            'status'    => $_GET['status']    ?? '',
            'staff_id'  => $_GET['staff_id']  ?? '',
            'search'    => $_GET['search']    ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to'   => $_GET['date_to']   ?? '',
        ];
        $page    = Utils::currentPage();
        $data    = $this->model->getAll($filters, $page);
        $repairs  = $data['rows'];
        $pagination = $data['pagination'];
        $staffList  = $this->staffModel->getTechnicians();

        require VIEWS_PATH . '/repairs/list.php';
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $repair = $this->model->findById($id);
        if (!$repair) { $this->notFound(); }
        Logger::log('viewed', 'repair', $id);
        require VIEWS_PATH . '/repairs/view.php';
    }

    public function create(): void
    {
        Auth::requireAuth();
        $customers = [];
        $staffList = $this->staffModel->getTechnicians();
        $csrfToken = Auth::generateCSRFToken();
        require VIEWS_PATH . '/repairs/create.php';
    }

    public function store(): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $errors = $this->validateRepair($_POST);
        if ($errors) {
            $_SESSION['_form_errors'] = $errors;
            $_SESSION['_form_data']   = $_POST;
            Utils::redirect('/repairs/create');
        }

        $data = $this->prepareData($_POST);
        $id   = $this->model->create($data);

        // Generate and save QR code
        $qrCode = $this->model->generateQRCode($id);
        $this->model->update($id, ['qr_code' => $qrCode]);

        Logger::log('created', 'repair', $id, null, $data);
        Utils::flashSuccess('Repair ticket #' . $id . ' created successfully.');
        Utils::redirect('/repairs/' . $id);
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $repair    = $this->model->findById($id);
        if (!$repair) { $this->notFound(); }
        $staffList = $this->staffModel->getTechnicians();
        $csrfToken = Auth::generateCSRFToken();
        require VIEWS_PATH . '/repairs/edit.php';
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $repair = $this->model->findById($id);
        if (!$repair) { $this->notFound(); }

        $errors = $this->validateRepair($_POST);
        if ($errors) {
            $_SESSION['_form_errors'] = $errors;
            $_SESSION['_form_data']   = $_POST;
            Utils::redirect('/repairs/' . $id . '/edit');
        }

        $data = $this->prepareData($_POST);
        Logger::log('updated', 'repair', $id, $repair, $data);
        $this->model->update($id, $data);

        Utils::flashSuccess('Repair #' . $id . ' updated.');
        Utils::redirect('/repairs/' . $id);
    }

    public function destroy(int $id): void
    {
        Auth::requireRole('admin');
        Auth::checkCSRF();

        $repair = $this->model->findById($id);
        if (!$repair) { $this->notFound(); }

        Logger::log('deleted', 'repair', $id, $repair);
        $this->model->delete($id);

        Utils::flashSuccess('Repair #' . $id . ' deleted.');
        Utils::redirect('/repairs');
    }

    public function updateStatus(int $id): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $newStatus = $_POST['status'] ?? '';
        if (!array_key_exists($newStatus, REPAIR_STATUS)) {
            Utils::flashError('Invalid status.');
            Utils::redirect('/repairs/' . $id);
        }

        $old = $this->model->findById($id);
        $this->model->updateStatus($id, $newStatus);
        Logger::log('status_changed', 'repair', $id,
            ['status' => $old['status']],
            ['status' => $newStatus]
        );

        Utils::flashSuccess('Status updated to: ' . REPAIR_STATUS[$newStatus]);
        Utils::redirect('/repairs/' . $id);
    }

    // ── AJAX: customer autocomplete ────────────────────────────────────────────
    public function customerSearch(): void
    {
        Auth::requireAuth();
        $q    = trim($_GET['q'] ?? '');
        $rows = $this->customerModel->autocomplete($q);
        header('Content-Type: application/json');
        echo json_encode($rows);
        exit;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function validateRepair(array $data): array
    {
        $errors = [];
        if (empty($data['customer_id']))           { $errors['customer_id']          = 'Customer is required.'; }
        if (empty(trim($data['device_model'] ?? ''))) { $errors['device_model']       = 'Device model is required.'; }
        if (empty($data['date_in']))               { $errors['date_in']              = 'Date in is required.'; }
        if (empty(trim($data['problem_description'] ?? ''))) { $errors['problem_description'] = 'Problem description is required.'; }
        return $errors;
    }

    private function prepareData(array $post): array
    {
        return [
            'customer_id'          => (int)$post['customer_id'],
            'staff_id'             => !empty($post['staff_id']) ? (int)$post['staff_id'] : null,
            'device_model'         => Utils::sanitize($post['device_model'] ?? ''),
            'device_serial_number' => Utils::sanitize($post['device_serial_number'] ?? ''),
            'date_in'              => $post['date_in'] ?? date('Y-m-d H:i:s'),
            'date_out'             => !empty($post['date_out']) ? $post['date_out'] : null,
            'collection_date'      => !empty($post['collection_date']) ? $post['collection_date'] : null,
            'problem_description'  => Utils::sanitize($post['problem_description'] ?? ''),
            'diagnosis'            => Utils::sanitize($post['diagnosis'] ?? ''),
            'work_done'            => Utils::sanitize($post['work_done'] ?? ''),
            'estimate_amount'      => !empty($post['estimate_amount']) ? (float)$post['estimate_amount'] : null,
            'actual_amount'        => !empty($post['actual_amount'])   ? (float)$post['actual_amount']   : null,
            'status'               => $post['status'] ?? 'in_progress',
            'notes'                => Utils::sanitize($post['notes'] ?? ''),
            'created_by'           => Auth::id(),
        ];
    }

    private function notFound(): never
    {
        http_response_code(404);
        require VIEWS_PATH . '/errors/404.php';
        exit;
    }
}
