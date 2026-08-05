<?php
class SaleController
{
    private Sale     $model;
    private Product  $productModel;
    private Business $businessModel;

    public function __construct()
    {
        $this->model         = new Sale();      // bootstraps sales tables (incl. business_id)
        $this->productModel  = new Product();   // bootstraps stock tables
        $this->businessModel = new Business();  // bootstraps businesses table
    }

    // ── GET /sales ────────────────────────────────────────────────────────────

    public function index(): void
    {
        Auth::requireAuth();

        $filters = [
            'status'    => $_GET['status']    ?? '',
            'search'    => $_GET['search']    ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to'   => $_GET['date_to']   ?? '',
        ];

        $page       = max(1, (int)($_GET['page'] ?? 1));
        $data       = $this->model->getAll(array_filter($filters, fn($v) => $v !== ''), $page);
        $sales      = $data['rows'];
        $pagination = $data['pagination'];
        $monthStats = $this->model->getMonthlyStats();

        require VIEWS_PATH . '/sales/list.php';
    }

    // ── GET /sales/report ─────────────────────────────────────────────────────

    public function report(): void
    {
        Auth::requireRole('manager');

        // Date-range detection (mirrors the main Reports page)
        $range    = $_GET['range']     ?? 'this_month';
        $status   = $_GET['status']    ?? '';
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo   = trim($_GET['date_to']   ?? '');

        switch ($range) {
            case 'this_year':
                $start = date('Y-01-01'); $end = date('Y-12-31');
                $rangeLabel = 'Year ' . date('Y');
                break;
            case 'last_3m':
                $start = date('Y-m-d', strtotime('-3 months')); $end = date('Y-m-d');
                $rangeLabel = 'Last 3 Months';
                break;
            case 'last_6m':
                $start = date('Y-m-d', strtotime('-6 months')); $end = date('Y-m-d');
                $rangeLabel = 'Last 6 Months';
                break;
            case 'last_12m':
                $start = date('Y-m-d', strtotime('-12 months')); $end = date('Y-m-d');
                $rangeLabel = 'Last 12 Months';
                break;
            case 'custom':
                $start = $dateFrom ?: date('Y-m-01');
                $end   = $dateTo   ?: date('Y-m-d');
                if ($start > $end) { [$start, $end] = [$end, $start]; }
                $rangeLabel = date('d M Y', strtotime($start)) . ' – ' . date('d M Y', strtotime($end));
                break;
            default:
                $range = 'this_month';
                $start = date('Y-m-01'); $end = date('Y-m-t');
                $rangeLabel = date('F Y');
        }

        if ($status !== '' && !array_key_exists($status, SALE_STATUS)) {
            $status = '';
        }

        $sales   = $this->model->getForReport($start, $end, $status);
        $summary = $this->model->getReportSummary($start, $end, $status);

        require VIEWS_PATH . '/sales/report.php';
    }

    // ── GET /sales/create ─────────────────────────────────────────────────────

    public function create(): void
    {
        Auth::requireAuth();

        $saleNumber = $this->model->generateSaleNumber();
        $csrfToken  = Auth::generateCSRFToken();
        $products   = $this->productModel->allActive();
        $businesses = $this->businessModel->allActive();
        $defaultBiz = $this->businessModel->getDefault();
        $signatures = Database::getInstance()->fetchOne(
            "SELECT signature1, signature2, signature3 FROM company_settings LIMIT 1"
        ) ?? [];
        $errors     = $_SESSION['_form_errors'] ?? [];
        $fd         = $_SESSION['_form_data']   ?? [];
        unset($_SESSION['_form_errors'], $_SESSION['_form_data']);

        require VIEWS_PATH . '/sales/create.php';
    }

    /**
     * Resolve the customer_id to save on the sale.
     *
     * - If an existing profile was picked via the autocomplete, its id is
     *   posted directly — use it as-is.
     * - Otherwise, if a name was typed AND at least one contact detail
     *   (phone/address/city/notes) was filled in via the inline "new
     *   customer" panel, create a real customer profile on the fly and
     *   link the sale to it.
     * - A bare walk-in name with no extra details stays a free-text label
     *   only (unchanged behaviour) — we don't create empty junk profiles.
     */
    private function resolveCustomerId(): ?int
    {
        $existingId = (int)($_POST['customer_id'] ?? 0);
        if ($existingId) {
            return $existingId;
        }

        $name = trim(Utils::sanitize($_POST['customer_name'] ?? ''));
        if ($name === '') {
            return null;
        }

        $phone   = trim(Utils::sanitize($_POST['new_customer_phone']   ?? ''));
        $address = trim(Utils::sanitize($_POST['new_customer_address'] ?? ''));
        $city    = trim(Utils::sanitize($_POST['new_customer_city']    ?? ''));
        $notes   = trim(Utils::sanitize($_POST['new_customer_notes']   ?? ''));

        if ($phone === '' && $address === '' && $city === '' && $notes === '') {
            return null;
        }

        $customerId = (new Customer())->create([
            'full_name'    => $name,
            'phone_mobile' => $phone   ?: null,
            'address'      => $address ?: null,
            'city'         => $city    ?: null,
            'notes'        => $notes   ?: null,
        ]);

        Logger::log('created', 'customer', $customerId, null, ['source' => 'sale_quick_add']);

        return $customerId;
    }

    // ── POST /sales ───────────────────────────────────────────────────────────

    public function store(): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $items = $this->cleanItems($_POST['items'] ?? []);
        if (empty($items)) {
            $_SESSION['_form_errors'] = ['items' => 'Add at least one item.'];
            $_SESSION['_form_data']   = $_POST;
            Utils::redirect('/sales/create');
        }

        // Pre-check stock availability per product
        $needed = [];
        foreach ($items as $item) {
            if (!empty($item['product_id'])) {
                $needed[$item['product_id']] = ($needed[$item['product_id']] ?? 0) + (int)round($item['quantity']);
            }
        }
        foreach ($needed as $productId => $qty) {
            $product = $this->productModel->findById((int)$productId);
            if (!$product) {
                continue;
            }
            if ((int)$product['quantity_on_hand'] < $qty) {
                $_SESSION['_form_errors'] = ['items' =>
                    'Not enough stock for "' . $product['name'] . '" — only '
                    . (int)$product['quantity_on_hand'] . ' unit(s) on hand.'];
                $_SESSION['_form_data'] = $_POST;
                Utils::redirect('/sales/create');
            }
        }

        $saleNumber = trim($_POST['sale_number'] ?? '') ?: $this->model->generateSaleNumber();

        $id = $this->model->create([
            'sale_number'    => Utils::sanitize($saleNumber),
            'customer_id'    => $this->resolveCustomerId(),
            'business_id'    => (int)($_POST['business_id'] ?? 0) ?: null,
            'customer_name'  => trim(Utils::sanitize($_POST['customer_name'] ?? '')),
            'sale_date'      => $_POST['sale_date'] ?? date('Y-m-d'),
            'tax_percentage' => (float)($_POST['tax_percentage'] ?? DEFAULT_TAX_PCT),
            'status'         => 'unpaid',
            'notes'          => Utils::sanitize($_POST['notes'] ?? '') ?: null,
            'signature_id'   => (int)($_POST['signature_id'] ?? 0),
            'created_by'     => Auth::id() ?: null,
        ]);

        foreach ($items as $item) {
            $this->model->addItem($id, [
                'product_id'   => !empty($item['product_id']) ? (int)$item['product_id'] : null,
                'description'  => Utils::sanitize($item['description']),
                'quantity'     => $item['quantity'],
                'unit_price'   => $item['unit_price'],
                'discount_pct' => $item['discount_pct'],
            ]);
        }

        $this->model->recalculateTotals($id);

        // Deduct stock (movement logged per product)
        foreach ($needed as $productId => $qty) {
            $this->productModel->adjustStock(
                (int)$productId, -$qty, 'sold', 'Sale ' . $saleNumber, Auth::id() ?: null
            );
        }

        // Optional immediate payment
        $paid = (float)($_POST['amount_paid'] ?? 0);
        if ($paid > 0) {
            $this->model->markAsPaid($id, $paid);
        }

        Logger::log('created', 'sale', $id);
        Utils::flashSuccess('Sale ' . $saleNumber . ' recorded.');
        Utils::redirect('/sales/' . $id);
    }

    // ── GET /sales/:id ────────────────────────────────────────────────────────

    public function show(int $id): void
    {
        Auth::requireAuth();
        $sale = $this->model->findById($id);
        if (!$sale) { $this->notFound(); }

        $csrfToken = Auth::generateCSRFToken();
        require VIEWS_PATH . '/sales/view.php';
    }

    // ── GET /sales/:id/edit ───────────────────────────────────────────────────

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $sale = $this->model->findById($id);
        if (!$sale) { $this->notFound(); }

        $csrfToken  = Auth::generateCSRFToken();
        $products   = $this->productModel->allActive();
        $businesses = $this->businessModel->allActive();
        $signatures = Database::getInstance()->fetchOne(
            "SELECT signature1, signature2, signature3 FROM company_settings LIMIT 1"
        ) ?? [];

        // Treat this sale's own items as "available" again for the stock hints —
        // matches what update() does server-side (return old stock, then re-check).
        $heldByThisSale = [];
        foreach ($sale['items'] as $item) {
            if (!empty($item['product_id'])) {
                $pid = (int)$item['product_id'];
                $heldByThisSale[$pid] = ($heldByThisSale[$pid] ?? 0) + (int)round((float)$item['quantity']);
            }
        }
        foreach ($products as &$p) {
            $pid = (int)$p['product_id'];
            if (isset($heldByThisSale[$pid])) {
                $p['quantity_on_hand'] = (int)$p['quantity_on_hand'] + $heldByThisSale[$pid];
            }
        }
        unset($p);

        $errors = $_SESSION['_form_errors'] ?? [];
        $fd     = $_SESSION['_form_data']   ?? $sale;
        unset($_SESSION['_form_errors'], $_SESSION['_form_data']);

        require VIEWS_PATH . '/sales/edit.php';
    }

    // ── POST /sales/:id ────────────────────────────────────────────────────────

    public function update(int $id): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $sale = $this->model->findById($id);
        if (!$sale) { $this->notFound(); }

        $items = $this->cleanItems($_POST['items'] ?? []);
        if (empty($items)) {
            $_SESSION['_form_errors'] = ['items' => 'Add at least one item.'];
            $_SESSION['_form_data']   = $_POST;
            Utils::redirect('/sales/' . $id . '/edit');
        }

        // Return stock held by the sale's current items before validating the new set
        foreach ($sale['items'] as $oldItem) {
            if (!empty($oldItem['product_id'])) {
                $this->productModel->adjustStock(
                    (int)$oldItem['product_id'],
                    (int)round((float)$oldItem['quantity']),
                    'returned',
                    'Sale ' . $sale['sale_number'] . ' edited',
                    Auth::id() ?: null
                );
            }
        }

        // Pre-check stock availability per product for the new item set
        $needed = [];
        foreach ($items as $item) {
            if (!empty($item['product_id'])) {
                $needed[$item['product_id']] = ($needed[$item['product_id']] ?? 0) + (int)round($item['quantity']);
            }
        }
        foreach ($needed as $productId => $qty) {
            $product = $this->productModel->findById((int)$productId);
            if (!$product) {
                continue;
            }
            if ((int)$product['quantity_on_hand'] < $qty) {
                // Roll back: re-deduct what was just returned so stock isn't left inflated
                foreach ($sale['items'] as $oldItem) {
                    if (!empty($oldItem['product_id'])) {
                        $this->productModel->adjustStock(
                            (int)$oldItem['product_id'],
                            -(int)round((float)$oldItem['quantity']),
                            'sold',
                            'Sale ' . $sale['sale_number'] . ' edit reverted',
                            Auth::id() ?: null
                        );
                    }
                }
                $_SESSION['_form_errors'] = ['items' =>
                    'Not enough stock for "' . $product['name'] . '" — only '
                    . (int)$product['quantity_on_hand'] . ' unit(s) available.'];
                $_SESSION['_form_data'] = $_POST;
                Utils::redirect('/sales/' . $id . '/edit');
            }
        }

        $this->model->update($id, [
            'customer_id'    => $this->resolveCustomerId(),
            'business_id'    => (int)($_POST['business_id'] ?? 0) ?: null,
            'customer_name'  => trim(Utils::sanitize($_POST['customer_name'] ?? '')),
            'sale_date'      => $_POST['sale_date'] ?? $sale['sale_date'],
            'tax_percentage' => (float)($_POST['tax_percentage'] ?? DEFAULT_TAX_PCT),
            'notes'          => Utils::sanitize($_POST['notes'] ?? '') ?: null,
            'signature_id'   => (int)($_POST['signature_id'] ?? 0),
        ]);

        $this->model->deleteItems($id);
        foreach ($items as $item) {
            $this->model->addItem($id, [
                'product_id'   => !empty($item['product_id']) ? (int)$item['product_id'] : null,
                'description'  => Utils::sanitize($item['description']),
                'quantity'     => $item['quantity'],
                'unit_price'   => $item['unit_price'],
                'discount_pct' => $item['discount_pct'],
            ]);
        }

        $this->model->recalculateTotals($id);

        // Deduct stock for the new item set
        foreach ($needed as $productId => $qty) {
            $this->productModel->adjustStock(
                (int)$productId, -$qty, 'sold', 'Sale ' . $sale['sale_number'] . ' edited', Auth::id() ?: null
            );
        }

        Logger::log('updated', 'sale', $id);
        Utils::flashSuccess('Sale ' . $sale['sale_number'] . ' updated.');
        Utils::redirect('/sales/' . $id);
    }

    // ── POST /sales/:id/paid ──────────────────────────────────────────────────

    public function markPaid(int $id): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $sale = $this->model->findById($id);
        if (!$sale) { $this->notFound(); }

        $amount = (float)($_POST['amount_paid'] ?? 0);
        $this->model->markAsPaid($id, $amount);

        Logger::log('updated', 'sale', $id, null, ['amount_paid' => $amount]);
        Utils::flashSuccess('Payment recorded.');
        Utils::redirect('/sales/' . $id);
    }

    // ── GET /sales/:id/print ──────────────────────────────────────────────────

    public function printSale(int $id): void
    {
        Auth::requireAuth();
        $sale = $this->model->findById($id);
        if (!$sale) { $this->notFound(); }

        // Use the business the sale was issued from; fall back to the default.
        $business = !empty($sale['business_id'])
            ? $this->businessModel->findById((int)$sale['business_id'])
            : null;
        if (!$business) {
            $business = $this->businessModel->getDefault();
        }

        $settings      = Database::getInstance()->fetchOne(
            "SELECT signature1, signature2, signature3 FROM company_settings LIMIT 1"
        ) ?? [];
        $signatureText = '';
        if (!empty($sale['signature_id'])) {
            $sigKey        = 'signature' . (int)$sale['signature_id'];
            $signatureText = $settings[$sigKey] ?? '';
        }

        require VIEWS_PATH . '/sales/print.php';
    }

    // ── POST /sales/:id/delete ────────────────────────────────────────────────

    public function destroy(int $id): void
    {
        Auth::requireRole('manager');
        Auth::checkCSRF();

        $sale = $this->model->findById($id);
        if (!$sale) { $this->notFound(); }

        // Return sold units to stock
        foreach ($sale['items'] as $item) {
            if (!empty($item['product_id'])) {
                $this->productModel->adjustStock(
                    (int)$item['product_id'],
                    (int)round((float)$item['quantity']),
                    'returned',
                    'Sale ' . $sale['sale_number'] . ' deleted',
                    Auth::id() ?: null
                );
            }
        }

        $this->model->delete($id);   // items cascade
        Logger::log('deleted', 'sale', $id);
        Utils::flashSuccess('Sale deleted and stock returned.');
        Utils::redirect('/sales');
    }

    // ── Private ───────────────────────────────────────────────────────────────

    /** Normalise posted item rows; drop empty ones. */
    private function cleanItems(array $raw): array
    {
        $items = [];
        foreach ($raw as $item) {
            $desc = trim($item['description'] ?? '');
            $qty  = (float)($item['quantity'] ?? 0);
            if ($desc === '' || $qty <= 0) {
                continue;
            }
            $items[] = [
                'product_id'   => (int)($item['product_id'] ?? 0) ?: null,
                'description'  => $desc,
                'quantity'     => $qty,
                'unit_price'   => max(0, (float)($item['unit_price'] ?? 0)),
                'discount_pct' => min(100, max(0, (float)($item['discount_pct'] ?? 0))),
            ];
        }
        return $items;
    }

    private function notFound(): never
    {
        http_response_code(404);
        require VIEWS_PATH . '/errors/404.php';
        exit;
    }
}
