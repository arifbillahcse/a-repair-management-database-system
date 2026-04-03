<?php
/**
 * ImportController — CSV Data Import
 * Handles bulk import of customers, repairs, invoices, and staff
 */
class ImportController
{
    private Database $db;
    private Customer $customerModel;
    private Repair $repairModel;
    private Invoice $invoiceModel;
    private Staff $staffModel;

    public function __construct()
    {
        Auth::requireRole('admin');
        $this->db = Database::getInstance();
        $this->customerModel = new Customer();
        $this->repairModel = new Repair();
        $this->invoiceModel = new Invoice();
        $this->staffModel = new Staff();
    }

    // ── GET /import ────────────────────────────────────────────────────────────

    public function index(): void
    {
        $csrfToken = Auth::generateCSRFToken();
        require VIEWS_PATH . '/imports/index.php';
    }

    // ── POST /import/upload ────────────────────────────────────────────────────

    public function upload(): void
    {
        Auth::checkCSRF();

        if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            Utils::flash('error', 'No file uploaded or file upload error.');
            Utils::redirect('/import');
        }

        $file = $_FILES['csv_file'];
        $type = trim($_POST['import_type'] ?? '');

        if (!$type || !in_array($type, ['customers', 'repairs', 'invoices', 'staff'])) {
            Utils::flash('error', 'Invalid import type selected.');
            Utils::redirect('/import');
        }

        // Validate file
        if ($file['size'] > 50 * 1024 * 1024) { // 50 MB max
            Utils::flash('error', 'File too large. Maximum 50 MB.');
            Utils::redirect('/import');
        }

        if (!in_array($file['type'], ['text/csv', 'text/plain', 'application/vnd.ms-excel'])) {
            Utils::flash('error', 'File must be CSV format.');
            Utils::redirect('/import');
        }

        // Process CSV
        $result = match ($type) {
            'customers' => $this->importCustomers($file['tmp_name']),
            'repairs'   => $this->importRepairs($file['tmp_name']),
            'invoices'  => $this->importInvoices($file['tmp_name']),
            'staff'     => $this->importStaff($file['tmp_name']),
            default     => ['success' => 0, 'skipped' => 0, 'errors' => []]
        };

        $_SESSION['_import_result'] = $result;
        Utils::redirect('/import/summary');
    }

    // ── GET /import/summary ────────────────────────────────────────────────────

    public function summary(): void
    {
        $result = $_SESSION['_import_result'] ?? null;
        unset($_SESSION['_import_result']);

        if (!$result) {
            Utils::redirect('/import');
        }

        require VIEWS_PATH . '/imports/summary.php';
    }

    // ── GET /import/template/:type ────────────────────────────────────────────

    public function downloadTemplate(string $type): void
    {
        $type = basename($type); // Sanitize

        $templates = [
            'customers' => [
                'columns' => ['first_name', 'last_name', 'email', 'phone', 'client_type', 'address'],
                'sample'  => [
                    ['John', 'Doe', 'john@example.com', '555-1234', 'individual', '123 Main St'],
                    ['Tech Corp', 'Tech Corp', 'contact@techcorp.com', '555-5678', 'company', '456 Business Ave'],
                ]
            ],
            'repairs' => [
                'columns' => ['customer_id', 'device_brand', 'device_model', 'device_issue', 'status', 'staff_id', 'actual_amount', 'notes'],
                'sample'  => [
                    [1, 'Apple', 'iPhone 13', 'Screen broken', 'in_progress', 1, '150.00', 'Customer notes here'],
                ]
            ],
            'invoices' => [
                'columns' => ['customer_id', 'repair_id', 'status', 'total_amount', 'paid_amount', 'due_date', 'notes'],
                'sample'  => [
                    [1, 1, 'draft', '150.00', '0.00', '2026-05-03', 'Payment terms net 30'],
                ]
            ],
            'staff' => [
                'columns' => ['first_name', 'last_name', 'email', 'phone', 'role'],
                'sample'  => [
                    ['Alice', 'Smith', 'alice@example.com', '555-9999', 'technician'],
                    ['Bob', 'Johnson', 'bob@example.com', '555-8888', 'staff'],
                ]
            ]
        ];

        if (!isset($templates[$type])) {
            http_response_code(404);
            die('Template not found');
        }

        $template = $templates[$type];
        $filename = "import_{$type}_template.csv";

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");

        $output = fopen('php://output', 'w');
        fputcsv($output, $template['columns']);

        foreach ($template['sample'] as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    // ── Private: Import Methods ────────────────────────────────────────────────

    private function importCustomers(string $filepath): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'errors' => []];
        $row = 0;

        if (!($handle = fopen($filepath, 'r'))) {
            $result['errors'][] = 'Could not open file';
            return $result;
        }

        // Skip header
        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== false) {
            $row++;

            if (empty(array_filter($data))) continue;

            $first_name = trim($data[0] ?? '');
            $last_name  = trim($data[1] ?? '');
            $email      = trim($data[2] ?? '');
            $phone      = trim($data[3] ?? '');
            $client_type = trim($data[4] ?? 'individual');
            $address    = trim($data[5] ?? '');

            if (!$first_name || !$last_name) {
                $result['errors'][] = "Row {$row}: First and last name required";
                $result['skipped']++;
                continue;
            }

            if ($email && !Utils::isValidEmail($email)) {
                $result['errors'][] = "Row {$row}: Invalid email format";
                $result['skipped']++;
                continue;
            }

            try {
                $this->db->insert('customers', [
                    'first_name'  => $first_name,
                    'last_name'   => $last_name,
                    'email'       => $email ?: null,
                    'phone'       => $phone ?: null,
                    'client_type' => $client_type,
                    'address'     => $address ?: null,
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);
                $result['success']++;
            } catch (Exception $e) {
                $result['errors'][] = "Row {$row}: " . $e->getMessage();
                $result['skipped']++;
            }
        }

        fclose($handle);
        return $result;
    }

    private function importRepairs(string $filepath): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'errors' => []];
        $row = 0;

        if (!($handle = fopen($filepath, 'r'))) {
            $result['errors'][] = 'Could not open file';
            return $result;
        }

        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== false) {
            $row++;

            if (empty(array_filter($data))) continue;

            $customer_id  = (int)($data[0] ?? 0);
            $device_brand = trim($data[1] ?? '');
            $device_model = trim($data[2] ?? '');
            $device_issue = trim($data[3] ?? '');
            $status       = trim($data[4] ?? 'in_progress');
            $staff_id     = !empty($data[5]) ? (int)$data[5] : null;
            $amount       = (float)($data[6] ?? 0);
            $notes        = trim($data[7] ?? '');

            if (!$customer_id || !$device_brand || !$device_issue) {
                $result['errors'][] = "Row {$row}: Customer ID, device brand, and issue required";
                $result['skipped']++;
                continue;
            }

            // Verify customer exists
            $customer = $this->db->fetchOne('SELECT customer_id FROM customers WHERE customer_id = ?', [$customer_id]);
            if (!$customer) {
                $result['errors'][] = "Row {$row}: Customer ID {$customer_id} not found";
                $result['skipped']++;
                continue;
            }

            try {
                $this->db->insert('repairs', [
                    'customer_id'   => $customer_id,
                    'device_brand'  => $device_brand,
                    'device_model'  => $device_model ?: null,
                    'device_issue'  => $device_issue,
                    'status'        => $status,
                    'staff_id'      => $staff_id,
                    'actual_amount' => $amount,
                    'notes'         => $notes ?: null,
                    'created_at'    => date('Y-m-d H:i:s'),
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);
                $result['success']++;
            } catch (Exception $e) {
                $result['errors'][] = "Row {$row}: " . $e->getMessage();
                $result['skipped']++;
            }
        }

        fclose($handle);
        return $result;
    }

    private function importInvoices(string $filepath): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'errors' => []];
        $row = 0;

        if (!($handle = fopen($filepath, 'r'))) {
            $result['errors'][] = 'Could not open file';
            return $result;
        }

        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== false) {
            $row++;

            if (empty(array_filter($data))) continue;

            $customer_id  = (int)($data[0] ?? 0);
            $repair_id    = !empty($data[1]) ? (int)$data[1] : null;
            $status       = trim($data[2] ?? 'draft');
            $total        = (float)($data[3] ?? 0);
            $paid         = (float)($data[4] ?? 0);
            $due_date     = !empty($data[5]) ? $data[5] : null;
            $notes        = trim($data[6] ?? '');

            if (!$customer_id || $total <= 0) {
                $result['errors'][] = "Row {$row}: Customer ID and total amount required";
                $result['skipped']++;
                continue;
            }

            try {
                $invoiceNumber = $this->invoiceModel->generateInvoiceNumber();

                $this->db->insert('invoices', [
                    'customer_id'   => $customer_id,
                    'repair_id'     => $repair_id,
                    'invoice_number' => $invoiceNumber,
                    'status'        => $status,
                    'total_amount'  => $total,
                    'paid_amount'   => $paid,
                    'due_date'      => $due_date ?: null,
                    'notes'         => $notes ?: null,
                    'created_at'    => date('Y-m-d H:i:s'),
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);
                $result['success']++;
            } catch (Exception $e) {
                $result['errors'][] = "Row {$row}: " . $e->getMessage();
                $result['skipped']++;
            }
        }

        fclose($handle);
        return $result;
    }

    private function importStaff(string $filepath): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'errors' => []];
        $row = 0;

        if (!($handle = fopen($filepath, 'r'))) {
            $result['errors'][] = 'Could not open file';
            return $result;
        }

        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== false) {
            $row++;

            if (empty(array_filter($data))) continue;

            $first_name = trim($data[0] ?? '');
            $last_name  = trim($data[1] ?? '');
            $email      = trim($data[2] ?? '');
            $phone      = trim($data[3] ?? '');
            $role       = trim($data[4] ?? 'technician');

            if (!$first_name || !$last_name) {
                $result['errors'][] = "Row {$row}: First and last name required";
                $result['skipped']++;
                continue;
            }

            try {
                $this->db->insert('staff', [
                    'first_name' => $first_name,
                    'last_name'  => $last_name,
                    'email'      => $email ?: null,
                    'phone'      => $phone ?: null,
                    'role'       => $role,
                    'is_active'  => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $result['success']++;
            } catch (Exception $e) {
                $result['errors'][] = "Row {$row}: " . $e->getMessage();
                $result['skipped']++;
            }
        }

        fclose($handle);
        return $result;
    }
}
