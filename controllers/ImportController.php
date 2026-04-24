<?php
class ImportController
{
    private Database $db;
    private Customer $customerModel;
    private Repair   $repairModel;
    private Invoice  $invoiceModel;
    private Staff    $staffModel;

    public function __construct()
    {
        Auth::requireRole('admin');
        $this->db            = Database::getInstance();
        $this->customerModel = new Customer();
        $this->repairModel   = new Repair();
        $this->invoiceModel  = new Invoice();
        $this->staffModel    = new Staff();
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

        if ($file['size'] > 50 * 1024 * 1024) {
            Utils::flash('error', 'File too large. Maximum 50 MB.');
            Utils::redirect('/import');
        }

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
        if (!$result) Utils::redirect('/import');
        require VIEWS_PATH . '/imports/summary.php';
    }

    // ── GET /import/template/:type ─────────────────────────────────────────────

    public function downloadTemplate(string $type): void
    {
        $type = basename($type);

        $templates = [

            // ── Customers ──────────────────────────────────────────────────────
            'customers' => [
                'columns' => [
                    'full_name', 'client_type', 'email',
                    'phone_mobile', 'phone_landline',
                    'address', 'city', 'postal_code', 'province',
                    'vat_number', 'tax_id', 'notes',
                ],
                'sample' => [
                    ['Mario Rossi',      'individual', 'mario@email.com',   '+39 333 1111111', '+39 02 1234567',  'Via Roma 1',     'Roma',       '00100', 'RM', '',             'RSSMRA80A01H501Z', ''],
                    ['Tech Solutions Srl','company',   'info@techsrl.it',   '+39 333 2222222', '+39 06 9876543',  'Via Milano 42',  'Milano',     '20100', 'MI', 'IT12345678901', '',                'VIP customer'],
                    ['Anna Bianchi',     'colleague',  'anna@workshop.it',  '+39 333 3333333', '',                'Via Napoli 5',   'Napoli',     '80100', 'NA', '',             '',                'Colleague technician'],
                ],
            ],

            // ── Repairs ────────────────────────────────────────────────────────
            'repairs' => [
                'columns' => [
                    'customer_id', 'device_brand', 'device_model',
                    'device_serial_number', 'problem_description',
                    'diagnosis_notes', 'status', 'priority',
                    'date_in', 'date_expected_out', 'actual_amount',
                    'staff_id', 'notes',
                ],
                'sample' => [
                    [1, 'Apple',   'iPhone 13',       'SN123456789', 'Screen broken',          'Replaced LCD',          'completed',   'normal', '2026-01-10', '2026-01-15', '150.00', 1, ''],
                    [2, 'Samsung', 'Galaxy S22',       '',            'Battery draining fast',  '',                      'in_progress', 'high',   '2026-01-20', '2026-01-25', '',       '', 'Customer called twice'],
                    [3, 'Vorwerk', 'Folletto VK200',   '',            'Motore non funziona',    'Sostituito condensatore','completed',   'normal', '2026-02-01', '2026-02-05', '80.00',  2, ''],
                ],
            ],

            // ── Invoices ───────────────────────────────────────────────────────
            'invoices' => [
                'columns' => [
                    'customer_id', 'repair_id', 'invoice_date', 'status',
                    'total_amount', 'amount_paid', 'due_date', 'notes',
                ],
                'sample' => [
                    [1, 1, '2026-01-15', 'paid',  '150.00', '150.00', '2026-01-20', ''],
                    [2, 2, '2026-02-01', 'draft', '80.00',  '0.00',   '2026-02-10', 'Payment terms net 30'],
                ],
            ],

            // ── Staff ──────────────────────────────────────────────────────────
            'staff' => [
                'columns' => ['first_name', 'last_name', 'email', 'phone', 'role'],
                'sample'  => [
                    ['Alice', 'Smith',   'alice@workshop.it', '+39 333 1111111', 'technician'],
                    ['Bob',   'Johnson', 'bob@workshop.it',   '+39 333 2222222', 'staff'],
                    ['Carol', 'Rossi',   'carol@workshop.it', '+39 333 3333333', 'manager'],
                ],
            ],
        ];

        if (!isset($templates[$type])) {
            http_response_code(404);
            die('Template not found');
        }

        $tpl      = $templates[$type];
        $filename = "import_{$type}_template.csv";

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel

        $out = fopen('php://output', 'w');
        fputcsv($out, $tpl['columns']);
        foreach ($tpl['sample'] as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    // ── IMPORT: Customers ──────────────────────────────────────────────────────

    private function importCustomers(string $filepath): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'errors' => []];

        if (!($handle = fopen($filepath, 'r'))) {
            $result['errors'][] = 'Could not open file';
            return $result;
        }

        $rawHeader    = fgetcsv($handle);
        if (!$rawHeader) {
            $result['errors'][] = 'Empty file or missing header row';
            fclose($handle);
            return $result;
        }
        $rawHeader[0] = ltrim($rawHeader[0], "\xEF\xBB\xBF");
        $col          = array_flip(array_map('trim', $rawHeader));
        $g            = fn(array $data, string $key) => trim($data[$col[$key] ?? -1] ?? '');

        $typeMap = [
            'individual' => 'individual', 'privato'  => 'individual',
            'company'    => 'company',    'azienda'  => 'company',
            'colleague'  => 'colleague',  'collega'  => 'colleague',
        ];

        $row = 0;
        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            if (empty(array_filter($data))) continue;

            $full_name = $g($data, 'full_name');
            if (!$full_name) {
                $result['errors'][] = "Row {$row}: full_name is required — skipped";
                $result['skipped']++;
                continue;
            }

            $email = strtolower($g($data, 'email'));
            if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $result['errors'][] = "Row {$row}: invalid email ignored";
                $email = '';
            }

            $rawType     = strtolower($g($data, 'client_type')) ?: 'individual';
            $client_type = $typeMap[$rawType] ?? 'individual';

            try {
                $this->db->insert('customers', [
                    'full_name'      => $full_name,
                    'client_type'    => $client_type,
                    'email'          => $email ?: null,
                    'phone_mobile'   => $g($data, 'phone_mobile')   ?: null,
                    'phone_landline' => $g($data, 'phone_landline')  ?: null,
                    'address'        => $g($data, 'address')         ?: null,
                    'city'           => $g($data, 'city')            ?: null,
                    'postal_code'    => $g($data, 'postal_code')     ?: null,
                    'province'       => strtoupper($g($data, 'province')) ?: null,
                    'vat_number'     => strtoupper($g($data, 'vat_number')) ?: null,
                    'tax_id'         => strtoupper($g($data, 'tax_id'))     ?: null,
                    'notes'          => $g($data, 'notes')           ?: null,
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

    // ── IMPORT: Repairs ────────────────────────────────────────────────────────

    private function importRepairs(string $filepath): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'errors' => []];

        if (!($handle = fopen($filepath, 'r'))) {
            $result['errors'][] = 'Could not open file';
            return $result;
        }

        $rawHeader    = fgetcsv($handle);
        if (!$rawHeader) {
            $result['errors'][] = 'Empty file or missing header row';
            fclose($handle);
            return $result;
        }
        $rawHeader[0] = ltrim($rawHeader[0], "\xEF\xBB\xBF");
        $col          = array_flip(array_map('trim', $rawHeader));
        $g            = fn(array $data, string $key) => trim($data[$col[$key] ?? -1] ?? '');

        $statusMap = [
            'in_progress'       => 'in_progress',  'in corso'          => 'in_progress',
            'on_hold'           => 'on_hold',       'in sospeso'        => 'on_hold',
            'waiting_for_parts' => 'waiting_for_parts', 'attesa ricambi' => 'waiting_for_parts',
            'ready_for_pickup'  => 'ready_for_pickup',  'pronto'        => 'ready_for_pickup',
            'completed'         => 'completed',     'completato'        => 'completed',
            'collected'         => 'collected',     'ritirato'          => 'collected',
            'cancelled'         => 'cancelled',     'annullato'         => 'cancelled',
        ];

        $row = 0;
        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            if (empty(array_filter($data))) continue;

            $problem = $g($data, 'problem_description');
            if (!$problem) {
                $result['errors'][] = "Row {$row}: problem_description is required — skipped";
                $result['skipped']++;
                continue;
            }

            $customerId = (int)$g($data, 'customer_id') ?: null;
            $staffId    = (int)$g($data, 'staff_id')    ?: null;

            $rawStatus = strtolower($g($data, 'status'));
            $status    = $statusMap[$rawStatus] ?? 'in_progress';

            $priority = $g($data, 'priority');
            if (!in_array($priority, ['low', 'normal', 'high', 'urgent'])) {
                $priority = 'normal';
            }

            $amount = $g($data, 'actual_amount');
            $amount = is_numeric($amount) ? (float)$amount : null;

            $dateIn  = $this->parseDate($g($data, 'date_in'))           ?: date('Y-m-d H:i:s');
            $dateOut = $this->parseDate($g($data, 'date_expected_out'));

            try {
                $this->db->insert('repairs', [
                    'customer_id'          => $customerId,
                    'staff_id'             => $staffId,
                    'device_brand'         => $g($data, 'device_brand')         ?: null,
                    'device_model'         => $g($data, 'device_model')         ?: '',
                    'device_serial_number' => $g($data, 'device_serial_number') ?: null,
                    'problem_description'  => $problem,
                    'diagnosis'            => $g($data, 'diagnosis_notes')      ?: null,
                    'status'               => $status,
                    'priority'             => $priority,
                    'collection_date'      => $dateOut ? substr($dateOut, 0, 10) : null,
                    'actual_amount'        => $amount,
                    'notes'                => $g($data, 'notes')                ?: null,
                    'date_in'              => $dateIn,
                    'created_at'           => $dateIn,
                    'updated_at'           => date('Y-m-d H:i:s'),
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

    // ── IMPORT: Invoices ───────────────────────────────────────────────────────

    private function importInvoices(string $filepath): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'errors' => []];

        if (!($handle = fopen($filepath, 'r'))) {
            $result['errors'][] = 'Could not open file';
            return $result;
        }

        $rawHeader    = fgetcsv($handle);
        if (!$rawHeader) {
            $result['errors'][] = 'Empty file or missing header row';
            fclose($handle);
            return $result;
        }
        $rawHeader[0] = ltrim($rawHeader[0], "\xEF\xBB\xBF");
        $col          = array_flip(array_map('trim', $rawHeader));
        $g            = fn(array $data, string $key) => trim($data[$col[$key] ?? -1] ?? '');

        $row = 0;
        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            if (empty(array_filter($data))) continue;

            $customerId = (int)$g($data, 'customer_id');
            $total      = (float)$g($data, 'total_amount');

            if (!$customerId || $total <= 0) {
                $result['errors'][] = "Row {$row}: customer_id and total_amount are required — skipped";
                $result['skipped']++;
                continue;
            }

            $repairId    = (int)$g($data, 'repair_id') ?: null;
            $status      = $g($data, 'status') ?: 'draft';
            $amountPaid  = (float)$g($data, 'amount_paid');
            $invoiceDate = $g($data, 'invoice_date') ?: date('Y-m-d');
            $dueDate     = $g($data, 'due_date') ?: null;
            $notes       = $g($data, 'notes') ?: null;

            try {
                $invoiceNumber = $this->invoiceModel->generateInvoiceNumber();
                $this->db->insert('invoices', [
                    'customer_id'    => $customerId,
                    'repair_id'      => $repairId,
                    'invoice_number' => $invoiceNumber,
                    'invoice_date'   => $invoiceDate,
                    'status'         => $status,
                    'total_amount'   => $total,
                    'amount_paid'    => $amountPaid,
                    'due_date'       => $dueDate,
                    'notes'          => $notes,
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

    // ── IMPORT: Staff ──────────────────────────────────────────────────────────

    private function importStaff(string $filepath): array
    {
        $result = ['success' => 0, 'skipped' => 0, 'errors' => []];

        if (!($handle = fopen($filepath, 'r'))) {
            $result['errors'][] = 'Could not open file';
            return $result;
        }

        $rawHeader    = fgetcsv($handle);
        if (!$rawHeader) {
            $result['errors'][] = 'Empty file or missing header row';
            fclose($handle);
            return $result;
        }
        $rawHeader[0] = ltrim($rawHeader[0], "\xEF\xBB\xBF");
        $col          = array_flip(array_map('trim', $rawHeader));
        $g            = fn(array $data, string $key) => trim($data[$col[$key] ?? -1] ?? '');

        $validRoles = ['admin', 'manager', 'technician', 'staff'];

        $row = 0;
        while (($data = fgetcsv($handle)) !== false) {
            $row++;
            if (empty(array_filter($data))) continue;

            $firstName = $g($data, 'first_name');
            $lastName  = $g($data, 'last_name');

            if (!$firstName || !$lastName) {
                $result['errors'][] = "Row {$row}: first_name and last_name are required — skipped";
                $result['skipped']++;
                continue;
            }

            $role = $g($data, 'role');
            if (!in_array($role, $validRoles)) $role = 'technician';

            try {
                $this->db->insert('staff', [
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'email'      => $g($data, 'email') ?: null,
                    'phone'      => $g($data, 'phone') ?: null,
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

    // ── Helper: parse various date formats ────────────────────────────────────

    private function parseDate(string $raw): ?string
    {
        if ($raw === '' || $raw === '0') return null;

        // Already MySQL format
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $raw)) {
            return date('Y-m-d H:i:s', strtotime($raw)) ?: null;
        }

        // Excel serial number
        if (is_numeric($raw)) {
            return date('Y-m-d H:i:s', (int)(($raw - 25569) * 86400));
        }

        // DD/MM/YYYY or DD-MM-YYYY
        $raw = str_replace('-', '/', $raw);
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{2,4})$#', $raw, $m)) {
            $year = strlen($m[3]) === 2 ? '20' . $m[3] : $m[3];
            $ts   = mktime(0, 0, 0, (int)$m[2], (int)$m[1], (int)$year);
            return $ts ? date('Y-m-d H:i:s', $ts) : null;
        }

        $ts = strtotime($raw);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }
}
