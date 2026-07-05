<?php
class SaleController
{
    private Sale    $model;
    private Product $productModel;

    public function __construct()
    {
        $this->model        = new Sale();      // bootstraps sales tables
        $this->productModel = new Product();   // bootstraps stock tables
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

    // ── GET /sales/create ─────────────────────────────────────────────────────

    public function create(): void
    {
        Auth::requireAuth();

        $saleNumber = $this->model->generateSaleNumber();
        $csrfToken  = Auth::generateCSRFToken();
        $products   = $this->productModel->allActive();
        $errors     = $_SESSION['_form_errors'] ?? [];
        $fd         = $_SESSION['_form_data']   ?? [];
        unset($_SESSION['_form_errors'], $_SESSION['_form_data']);

        require VIEWS_PATH . '/sales/create.php';
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
            'customer_id'    => (int)($_POST['customer_id'] ?? 0) ?: null,
            'customer_name'  => trim(Utils::sanitize($_POST['customer_name'] ?? '')),
            'sale_date'      => $_POST['sale_date'] ?? date('Y-m-d'),
            'tax_percentage' => (float)($_POST['tax_percentage'] ?? DEFAULT_TAX_PCT),
            'status'         => 'unpaid',
            'notes'          => Utils::sanitize($_POST['notes'] ?? '') ?: null,
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

        $business = (new Business())->getDefault();
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
