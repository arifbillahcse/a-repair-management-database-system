<?php
class RepairController
{
    private Repair   $model;
    private Customer $customerModel;
    private Staff    $staffModel;

    private const ALLOWED_MIME = ['image/jpeg','image/png','image/gif','image/webp'];
    private const MAX_UPLOAD   = 5242880; // 5 MB

    public function __construct()
    {
        $this->model         = new Repair();
        $this->customerModel = new Customer();
        $this->staffModel    = new Staff();
    }

    // ── GET /repairs ──────────────────────────────────────────────────────────

    public function index(): void
    {
        Auth::requireAuth();

        $filters = [
            'status'      => $_GET['status']      ?? '',
            'staff_id'    => $_GET['staff_id']    ?? '',
            'customer_id' => $_GET['customer_id'] ?? '',
            'client_type' => $_GET['client_type'] ?? '',
            'search'      => $_GET['search']      ?? '',
            'date_from'   => $_GET['date_from']   ?? '',
            'date_to'     => $_GET['date_to']     ?? '',
            'order_by'    => $_GET['order_by']    ?? 'r.date_in DESC',
        ];

        $page        = Utils::currentPage();
        $data        = $this->model->getAll($filters, $page);
        $repairs     = $data['rows'];
        $pagination  = $data['pagination'];
        $staffList   = $this->staffModel->getTechnicians();
        $statusCounts = $this->model->getStatusCounts();

        $customerFilter = null;
        if (!empty($filters['customer_id'])) {
            $customerFilter = $this->customerModel->findById((int)$filters['customer_id']);
        }

        require VIEWS_PATH . '/repairs/list.php';
    }

    // ── GET /repairs/:id ──────────────────────────────────────────────────────

    public function show(int $id): void
    {
        Auth::requireAuth();

        $repair = $this->model->findById($id);
        if (!$repair) { $this->notFound(); }

        // Load customer's full record for the sidebar card
        $customer  = $this->customerModel->findById((int)$repair['customer_id']);
        $csrfToken = Auth::generateCSRFToken();

        Logger::log('viewed', 'repair', $id);
        require VIEWS_PATH . '/repairs/view.php';
    }

    // ── GET /repairs/create ───────────────────────────────────────────────────

    public function create(): void
    {
        Auth::requireAuth();

        $staffList = $this->staffModel->getTechnicians();
        $csrfToken = Auth::generateCSRFToken();
        $errors    = $_SESSION['_form_errors'] ?? [];
        $formData  = $_SESSION['_form_data']   ?? [];
        unset($_SESSION['_form_errors'], $_SESSION['_form_data']);

        // Pre-fill customer if coming from customer profile
        $preloadCustomer = null;
        if (!empty($formData['customer_id']) || !empty($_GET['customer_id'])) {
            $cid = (int)($formData['customer_id'] ?? $_GET['customer_id']);
            $preloadCustomer = $this->customerModel->findById($cid);
        }

        require VIEWS_PATH . '/repairs/create.php';
    }

    // ── POST /repairs ─────────────────────────────────────────────────────────

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

        /* QR code generation disabled
        // Generate and persist QR code
        $qrCode = $this->model->generateQRCode($id);
        $this->model->update($id, ['qr_code' => $qrCode]);
        */

        // Handle photo upload if submitted with creation
        $this->handlePhotoUploads($id);

        Logger::log('created', 'repair', $id, null, $data);
        Utils::flashSuccess("Repair ticket #{$id} created successfully.");
        Utils::redirect('/repairs/' . $id);
    }

    // ── GET /repairs/:id/edit ─────────────────────────────────────────────────

    public function edit(int $id): void
    {
        Auth::requireAuth();

        $repair    = $this->model->findById($id);
        if (!$repair) { $this->notFound(); }

        $staffList = $this->staffModel->getTechnicians();
        $csrfToken = Auth::generateCSRFToken();
        $errors    = $_SESSION['_form_errors'] ?? [];
        $formData  = $_SESSION['_form_data']   ?? $repair;
        unset($_SESSION['_form_errors'], $_SESSION['_form_data']);

        // Always load the linked customer for the card
        $preloadCustomer = $this->customerModel->findById((int)$repair['customer_id']);

        require VIEWS_PATH . '/repairs/edit.php';
    }

    // ── POST /repairs/:id ─────────────────────────────────────────────────────

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

        // Handle any newly uploaded photos
        $this->handlePhotoUploads($id);

        Utils::flashSuccess("Repair #{$id} updated.");
        Utils::redirect('/repairs/' . $id);
    }

    // ── POST /repairs/:id/delete ──────────────────────────────────────────────

    public function destroy(int $id): void
    {
        Auth::requireRole('admin');
        Auth::checkCSRF();

        $repair = $this->model->findById($id);
        if (!$repair) { $this->notFound(); }

        // Delete photo directory
        $photoDir = UPLOAD_PATH . '/photos/repair_' . $id;
        if (is_dir($photoDir)) {
            array_map('unlink', glob($photoDir . '/*'));
            @rmdir($photoDir);
        }

        Logger::log('deleted', 'repair', $id, $repair);
        $this->model->delete($id);

        Utils::flashSuccess("Repair #{$id} deleted.");
        Utils::redirect('/repairs');
    }

    // ── POST /repairs/:id/status ──────────────────────────────────────────────

    public function updateStatus(int $id): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $newStatus = $_POST['status'] ?? '';
        if (!array_key_exists($newStatus, REPAIR_STATUS)) {
            Utils::flashError('Invalid status value.');
            Utils::redirect('/repairs/' . $id);
        }

        $old = $this->model->findById($id);
        if (!$old) { $this->notFound(); }

        $this->model->updateStatus($id, $newStatus);
        Logger::log('status_changed', 'repair', $id,
            ['status' => $old['status']],
            ['status' => $newStatus]
        );

        Utils::flashSuccess('Status updated to: ' . REPAIR_STATUS[$newStatus]);

        // If called via AJAX, return JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'status' => $newStatus, 'label' => REPAIR_STATUS[$newStatus]]);
            exit;
        }

        Utils::redirect('/repairs/' . $id);
    }

    // ── POST /repairs/:id/photo ───────────────────────────────────────────────

    public function uploadPhoto(int $id): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        if ($this->handlePhotoUploads($id) > 0) {
            Utils::flashSuccess('Photo uploaded successfully.');
        } else {
            Utils::flashError('No valid photo was uploaded. Accepted formats: JPG, PNG, GIF, WEBP. Max 5 MB.');
        }

        Utils::redirect('/repairs/' . $id);
    }

    // ── POST /repairs/:id/photo/delete ────────────────────────────────────────

    public function deletePhoto(int $id): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $path = $_POST['photo_path'] ?? '';
        if (empty($path)) {
            Utils::redirect('/repairs/' . $id);
        }

        // Remove from DB
        $this->model->removePhoto($id, $path);

        // Delete physical file
        $fullPath = PUBLIC_PATH . '/' . ltrim($path, '/');
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }

        Logger::log('updated', 'repair', $id, null, ['deleted_photo' => $path]);
        Utils::flashSuccess('Photo removed.');
        Utils::redirect('/repairs/' . $id);
    }

    // ── GET /repairs/:id/print ────────────────────────────────────────────────

    public function printRepair(int $id): void
    {
        Auth::requireAuth();

        $repair   = $this->model->findById($id);
        if (!$repair) { $this->notFound(); }

        $customer = $this->customerModel->findById((int)$repair['customer_id']);
        $company  = Database::getInstance()->fetchOne("SELECT * FROM company_settings LIMIT 1") ?? [];

        Logger::log('exported', 'repair', $id);
        require VIEWS_PATH . '/repairs/print.php';
    }

    // ── AJAX: customer autocomplete ───────────────────────────────────────────

    public function customerSearch(): void
    {
        Auth::requireAuth();
        $q    = trim($_GET['q'] ?? '');
        $rows = $this->customerModel->autocomplete($q, 12);
        header('Content-Type: application/json');
        echo json_encode($rows);
        exit;
    }

    // ── AJAX: repair autocomplete (repairs list page search) ─────────────────

    public function repairSearch(): void
    {
        Auth::requireAuth();
        $q    = trim($_GET['q'] ?? '');
        $rows = $this->model->autocomplete($q, 12);
        header('Content-Type: application/json');
        echo json_encode($rows);
        exit;
    }

    /* QR code scan lookup disabled
    // ── AJAX: QR code scan lookup ─────────────────────────────────────────────

    public function qrLookup(): void
    {
        Auth::requireAuth();
        $code   = trim($_GET['code'] ?? '');
        $repair = $this->model->findByQRCode($code);

        header('Content-Type: application/json');
        if ($repair) {
            echo json_encode(['found' => true, 'repair_id' => $repair['repair_id'], 'url' => BASE_URL . '/repairs/' . $repair['repair_id']]);
        } else {
            echo json_encode(['found' => false]);
        }
        exit;
    }
    */

    // ── Private helpers ───────────────────────────────────────────────────────

    private function validateRepair(array $data): array
    {
        $errors = [];
        // Customer is optional - allows importing repairs without customer link
        if (empty($data['date_in']))                          { $errors['date_in']             = 'Date received is required.'; }
        if (empty(trim($data['problem_description'] ?? '')))  { $errors['problem_description'] = 'Problem description is required.'; }
        if (!empty($data['estimate_amount']) && !is_numeric($data['estimate_amount'])) {
            $errors['estimate_amount'] = 'Estimate must be a number.';
        }
        return $errors;
    }

    private function prepareData(array $post): array
    {
        $status = array_key_exists($post['status'] ?? '', REPAIR_STATUS)
            ? $post['status']
            : 'in_progress';

        return [
            'customer_id'          => !empty($post['customer_id']) ? (int)$post['customer_id'] : null,
            'staff_id'             => !empty($post['staff_id']) ? (int)$post['staff_id'] : null,
            'device_model'         => Utils::sanitize($post['device_model']         ?? ''),
            'device_serial_number' => Utils::sanitize($post['device_serial_number'] ?? ''),
            'date_in'              => $post['date_in']         ?: date('Y-m-d H:i:s'),
            'date_out'             => !empty($post['date_out'])         ? $post['date_out']         : null,
            'collection_date'      => !empty($post['collection_date'])  ? $post['collection_date']  : null,
            'problem_description'  => Utils::sanitize($post['problem_description'] ?? ''),
            'diagnosis'            => Utils::sanitize($post['diagnosis']  ?? ''),
            'work_done'            => Utils::sanitize($post['work_done']  ?? ''),
            'estimate_amount'      => is_numeric($post['estimate_amount'] ?? '') ? (float)$post['estimate_amount'] : null,
            'actual_amount'        => is_numeric($post['actual_amount']   ?? '') ? (float)$post['actual_amount']   : null,
            'status'               => $status,
            'notes'                => Utils::sanitize($post['notes'] ?? ''),
            'created_by'           => Auth::id(),
        ];
    }

    /**
     * Process uploaded files from $_FILES['photos'].
     * Returns the count of successfully saved photos.
     */
    private function handlePhotoUploads(int $repairId): int
    {
        if (empty($_FILES['photos']['name'][0])) {
            return 0;
        }

        $dir = UPLOAD_PATH . '/photos/repair_' . $repairId;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $saved = 0;
        $files = $_FILES['photos'];
        $count = count($files['name']);

        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) { continue; }
            if ($files['size'][$i]  >  self::MAX_UPLOAD) { continue; }

            // Validate MIME type via finfo
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $files['tmp_name'][$i]);
            finfo_close($finfo);

            if (!in_array($mime, self::ALLOWED_MIME, true)) { continue; }

            $ext      = match($mime) {
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
                default      => 'jpg',
            };
            $filename = 'photo_' . uniqid('', true) . '.' . $ext;
            $dest     = $dir . '/' . $filename;

            if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
                $relativePath = 'uploads/photos/repair_' . $repairId . '/' . $filename;
                $this->model->addPhoto($repairId, $relativePath);
                $saved++;
            }
        }

        return $saved;
    }

    private function notFound(): never
    {
        http_response_code(404);
        require VIEWS_PATH . '/errors/404.php';
        exit;
    }
}
