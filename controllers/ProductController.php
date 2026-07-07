<?php
class ProductController
{
    private Product         $model;
    private ProductCategory $categoryModel;

    public function __construct()
    {
        $this->model         = new Product();   // also bootstraps stock tables
        $this->categoryModel = new ProductCategory();
    }

    // ── GET /products ─────────────────────────────────────────────────────────

    public function index(): void
    {
        Auth::requireAuth();

        $filters = [
            'search'      => $_GET['search']      ?? '',
            'category_id' => (int)($_GET['category_id'] ?? 0) ?: null,
            'stock'       => $_GET['stock']       ?? '',
        ];

        $page       = max(1, (int)($_GET['page'] ?? 1));
        $data       = $this->model->getAll(array_filter($filters, fn($v) => $v !== null && $v !== ''), $page);
        $products   = $data['rows'];
        $pagination = $data['pagination'];
        $stockStats = $this->model->getStockStats();
        $categories = $this->categoryModel->allOrdered();

        require VIEWS_PATH . '/products/list.php';
    }

    // ── GET /products/create ──────────────────────────────────────────────────

    public function create(): void
    {
        Auth::requireAuth();

        $csrfToken  = Auth::generateCSRFToken();
        $categories = $this->categoryModel->allOrdered();
        $errors     = $_SESSION['_form_errors'] ?? [];
        $fd         = $_SESSION['_form_data']   ?? [];
        unset($_SESSION['_form_errors'], $_SESSION['_form_data']);

        require VIEWS_PATH . '/products/create.php';
    }

    // ── POST /products ────────────────────────────────────────────────────────

    public function store(): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $errors = $this->validate($_POST);
        if ($errors) {
            $_SESSION['_form_errors'] = $errors;
            $_SESSION['_form_data']   = $_POST;
            Utils::redirect('/products/create');
        }

        $id = $this->model->create($this->collectData($_POST));

        // Optional opening stock
        $initial = (int)($_POST['initial_stock'] ?? 0);
        if ($initial > 0) {
            $this->model->adjustStock($id, $initial, 'received', 'Opening stock', Auth::id() ?: null);
        }

        Logger::log('created', 'product', $id);
        Utils::flashSuccess('Product created.');
        Utils::redirect('/products/' . $id);
    }

    // ── GET /products/:id ─────────────────────────────────────────────────────

    public function show(int $id): void
    {
        Auth::requireAuth();
        $product = $this->model->findById($id);
        if (!$product) { $this->notFound(); }

        $movements = $this->model->getMovements($id);
        $csrfToken = Auth::generateCSRFToken();

        require VIEWS_PATH . '/products/view.php';
    }

    // ── GET /products/:id/edit ────────────────────────────────────────────────

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $product = $this->model->findById($id);
        if (!$product) { $this->notFound(); }

        $csrfToken  = Auth::generateCSRFToken();
        $categories = $this->categoryModel->allOrdered();
        $errors     = $_SESSION['_form_errors'] ?? [];
        $fd         = $_SESSION['_form_data']   ?? $product;
        unset($_SESSION['_form_errors'], $_SESSION['_form_data']);

        require VIEWS_PATH . '/products/edit.php';
    }

    // ── POST /products/:id ────────────────────────────────────────────────────

    public function update(int $id): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $product = $this->model->findById($id);
        if (!$product) { $this->notFound(); }

        $errors = $this->validate($_POST);
        if ($errors) {
            $_SESSION['_form_errors'] = $errors;
            $_SESSION['_form_data']   = $_POST;
            Utils::redirect('/products/' . $id . '/edit');
        }

        $this->model->update($id, $this->collectData($_POST));

        Logger::log('updated', 'product', $id);
        Utils::flashSuccess('Product updated.');
        Utils::redirect('/products/' . $id);
    }

    // ── POST /products/:id/stock ──────────────────────────────────────────────

    public function adjustStock(int $id): void
    {
        Auth::requireRole('manager');
        Auth::checkCSRF();

        $product = $this->model->findById($id);
        if (!$product) { $this->notFound(); }

        $qty       = abs((int)($_POST['qty'] ?? 0));
        $direction = ($_POST['direction'] ?? 'in') === 'out' ? -1 : 1;
        $reason    = $_POST['reason'] ?? 'correction';
        $note      = Utils::sanitize($_POST['note'] ?? '');

        if ($qty < 1) {
            Utils::flash('error', 'Quantity must be at least 1.');
            Utils::redirect('/products/' . $id);
        }

        $ok = $this->model->adjustStock($id, $qty * $direction, $reason, $note, Auth::id() ?: null);
        if (!$ok) {
            Utils::flash('error', 'Not enough stock — only ' . (int)$product['quantity_on_hand'] . ' unit(s) on hand.');
        } else {
            Logger::log('updated', 'product', $id, null, ['stock_change' => $qty * $direction, 'reason' => $reason]);
            Utils::flashSuccess('Stock updated.');
        }
        Utils::redirect('/products/' . $id);
    }

    // ── POST /products/:id/delete ─────────────────────────────────────────────

    public function destroy(int $id): void
    {
        Auth::requireRole('manager');
        Auth::checkCSRF();

        $product = $this->model->findById($id);
        if (!$product) { $this->notFound(); }

        $this->model->delete($id);
        Logger::log('deleted', 'product', $id);
        Utils::flashSuccess('Product deleted.');
        Utils::redirect('/products');
    }

    // ── GET/POST /product-categories ──────────────────────────────────────────

    public function categories(): void
    {
        Auth::requireRole('manager');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::checkCSRF();
            $name = trim(Utils::sanitize($_POST['name'] ?? ''));

            if ($name === '') {
                Utils::flash('error', 'Category name is required.');
            } elseif ($this->categoryModel->isNameTaken($name)) {
                Utils::flash('error', 'That category already exists.');
            } else {
                $this->categoryModel->create([
                    'name'       => $name,
                    'sort_order' => (int)($_POST['sort_order'] ?? 0),
                ]);
                Utils::flashSuccess('Category added.');
            }
            Utils::redirect('/product-categories');
        }

        $csrfToken  = Auth::generateCSRFToken();
        $categories = $this->categoryModel->allWithCounts();

        require VIEWS_PATH . '/products/categories.php';
    }

    // ── POST /product-categories/:id/delete ───────────────────────────────────

    public function categoryDelete(int $id): void
    {
        Auth::requireRole('manager');
        Auth::checkCSRF();

        $this->categoryModel->deleteAndUnlink($id);
        Utils::flashSuccess('Category deleted. Its products are now uncategorised.');
        Utils::redirect('/product-categories');
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function collectData(array $src): array
    {
        return [
            'sku'                 => trim(Utils::sanitize($src['sku'] ?? '')) ?: null,
            'name'                => trim(Utils::sanitize($src['name'] ?? '')),
            'category_id'         => (int)($src['category_id'] ?? 0) ?: null,
            'description'         => Utils::sanitize($src['description'] ?? '') ?: null,
            'selling_price'       => max(0, (float)($src['selling_price'] ?? 0)),
            'cost_price'          => ($src['cost_price'] ?? '') !== '' ? max(0, (float)$src['cost_price']) : null,
            'low_stock_threshold' => max(0, (int)($src['low_stock_threshold'] ?? 0)),
            'is_active'           => isset($src['is_active']) ? 1 : 0,
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty(trim($data['name'] ?? ''))) {
            $errors['name'] = 'Product name is required.';
        }
        if (($data['selling_price'] ?? '') === '' || !is_numeric($data['selling_price'])) {
            $errors['selling_price'] = 'Selling price is required.';
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
