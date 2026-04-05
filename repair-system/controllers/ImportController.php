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

        // Read header row and map column names to indexes
        $rawHeader = fgetcsv($handle);
        if (!$rawHeader) {
            $result['errors'][] = 'Empty file or missing header row';
            fclose($handle);
            return $result;
        }

        // Strip UTF-8 BOM from first column if present
        $rawHeader[0] = ltrim($rawHeader[0], "\xEF\xBB\xBF");
        $header = array_map('trim', $rawHeader);
        $col = array_flip($header); // column name → index

        // Handle duplicate tax_id columns: first=tax_id, second=vat_number
        $taxIdIndexes = array_keys($header, 'tax_id');
        $taxIdCol     = $taxIdIndexes[0] ?? null;
        $vatCol       = $taxIdIndexes[1] ?? ($col['vat_number'] ?? null);

        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            if (empty(array_filter($data))) continue;

            $g = fn(string $key) => trim($data[$col[$key] ?? -1] ?? '');

            $full_name   = $g('full_name');
            $legacy_id   = $g('ClientID');
            $client_type = $g('client_type') ?: 'individual';
            $address     = $g('address');
            $phone_land  = $g('phone_landline');
            $phone_mob   = $g('phone_mobile');
            $email       = strtolower($g('email'));
            $tax_id      = strtoupper($g('tax_id'));
            $vat_number  = $vatCol !== null ? strtoupper(trim($data[$vatCol] ?? '')) : '';
            $postal_code = $g('postal_code');
            $city        = $g('city');
            $province    = strtoupper($g('province'));
            $notes       = $g('notes');

            if (!$full_name) {
                $result['errors'][] = "Row {$row}: Name (full_name) is required";
                $result['skipped']++;
                continue;
            }

            if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $result['errors'][] = "Row {$row}: Invalid email — skipping email only";
                $email = '';
            }

            // Skip duplicate legacy_id if already imported
            if ($legacy_id) {
                $exists = $this->db->fetchOne(
                    'SELECT customer_id FROM customers WHERE legacy_id = ? LIMIT 1',
                    [$legacy_id]
                );
                if ($exists) {
                    $result['errors'][] = "Row {$row}: ClientID {$legacy_id} already imported — skipped";
                    $result['skipped']++;
                    continue;
                }
            }

            // Normalize client_type to our values
            $typeMap = [
                'privato'  => 'individual', 'individual' => 'individual',
                'azienda'  => 'company',    'company'    => 'company',
                'freelancer' => 'freelancer',
            ];
            $client_type = $typeMap[strtolower($client_type)] ?? 'individual';

            try {
                $this->db->insert('customers', [
                    'legacy_id'      => $legacy_id ?: null,
                    'full_name'      => $full_name,
                    'client_type'    => $client_type,
                    'address'        => $address ?: null,
                    'phone_landline' => $phone_land ?: null,
                    'phone_mobile'   => $phone_mob ?: null,
                    'email'          => $email ?: null,
                    'tax_id'         => $tax_id ?: null,
                    'vat_number'     => $vat_number ?: null,
                    'postal_code'    => $postal_code ?: null,
                    'city'           => $city ?: null,
                    'province'       => $province ?: null,
                    'notes'          => $notes ?: null,
                    'status'         => 'active',
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s'),
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

        // Read and normalize header row
        $rawHeader = fgetcsv($handle);
        if (!$rawHeader) {
            $result['errors'][] = 'Empty file or missing header row';
            fclose($handle);
            return $result;
        }
        $rawHeader[0] = ltrim($rawHeader[0], "\xEF\xBB\xBF");
        $header = array_map('trim', $rawHeader);
        $col    = array_flip($header);

        // Italian status → our status
        $statusMap = [
            'completato'        => 'completed',
            'in sospeso'        => 'on_hold',
            'in corso'          => 'in_progress',
            'pronto'            => 'ready_for_pickup',
            'ritirato'          => 'collected',
            'annullato'         => 'cancelled',
            'attesa ricambi'    => 'waiting_for_parts',
            // English fallbacks
            'completed'         => 'completed',
            'on_hold'           => 'on_hold',
            'in_progress'       => 'in_progress',
            'ready_for_pickup'  => 'ready_for_pickup',
            'collected'         => 'collected',
            'cancelled'         => 'cancelled',
            'waiting_for_parts' => 'waiting_for_parts',
        ];

        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            if (empty(array_filter($data))) continue;

            $g = fn(string $key) => trim($data[$col[$key] ?? -1] ?? '');

            $clientIdFk      = $g('ClientID_FK');
            $deviceName      = $g('DeviceName');
            $deviceSerial    = $g('DeviceSerial');
            $problemDesc     = $g('ProblemDescription');
            $workDone        = $g('WorkDone');
            $statusRaw       = strtolower($g('Status'));
            $notes           = $g('Notes');
            $dateIn          = $g('DateIn');
            $dateOut         = $g('DateOut');
            $repairDate      = $g('RepairDate');

            // Resolve customer via legacy_id
            $customerId = null;
            if ($clientIdFk !== '') {
                $customer = $this->db->fetchOne(
                    'SELECT customer_id FROM customers WHERE legacy_id = ? LIMIT 1',
                    [$clientIdFk]
                );
                $customerId = $customer['customer_id'] ?? null;
                if (!$customerId) {
                    $result['errors'][] = "Row {$row}: ClientID_FK {$clientIdFk} not found in customers — imported without customer link";
                }
            }

            // Map status
            $status = $statusMap[$statusRaw] ?? 'in_progress';

            // Parse dates safely
            $parsedDateIn  = $this->parseDate($dateIn)  ?: $this->parseDate($repairDate) ?: date('Y-m-d H:i:s');
            $parsedDateOut = $this->parseDate($dateOut);

            // Combine Notes + WorkDone
            $combinedNotes = trim(implode("\n\n", array_filter([
                $notes    ? "Notes: {$notes}"     : '',
                $workDone ? "Work Done: {$workDone}" : '',
            ])));

            try {
                $this->db->insert('repairs', [
                    'customer_id'           => $customerId,
                    'staff_id'              => null,
                    'device_brand'          => $deviceName ?: null,
                    'device_model'          => null,
                    'device_serial_number'  => $deviceSerial ?: null,
                    'problem_description'   => $problemDesc ?: null,
                    'work_done'             => $workDone ?: null,
                    'status'                => $status,
                    'notes'                 => $combinedNotes ?: null,
                    'date_in'               => $parsedDateIn,
                    'date_out'              => $parsedDateOut,
                    'created_at'            => $parsedDateIn,
                    'updated_at'            => date('Y-m-d H:i:s'),
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

    /**
     * Parse various date formats from Excel/CSV safely.
     * Returns MySQL datetime string or null.
     */
    private function parseDate(string $raw): ?string
    {
        if ($raw === '' || $raw === '0') return null;

        // Already MySQL format: 2025-12-15 00:00:00
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $raw)) {
            return date('Y-m-d H:i:s', strtotime($raw)) ?: null;
        }

        // Excel serial number (numeric only)
        if (is_numeric($raw)) {
            $ts = ($raw - 25569) * 86400; // Excel epoch to Unix
            return date('Y-m-d H:i:s', (int)$ts);
        }

        // Short formats: 9/12/25, 10/12/2025, 12-12-25
        $raw = str_replace('-', '/', $raw);
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{2,4})$#', $raw, $m)) {
            $year = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
            $ts   = mktime(0, 0, 0, (int)$m[2], (int)$m[1], (int)$year); // DD/MM/YYYY
            return $ts ? date('Y-m-d H:i:s', $ts) : null;
        }

        // Try PHP strtotime as last resort
        $ts = strtotime($raw);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
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
