<?php
$pageTitle = 'Product Categories';
require VIEWS_PATH . '/layouts/header.php';
?>
<style>
.cat-grid{display:grid;grid-template-columns:1fr;gap:1.5rem;max-width:900px}
@media(min-width:860px){.cat-grid{grid-template-columns:1fr 320px}}
.cat-table{width:100%;border-collapse:collapse;font-size:.87rem}
.cat-table th{padding:.55rem 1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);border-bottom:1px solid var(--border);text-align:left}
.cat-table td{padding:.6rem 1rem;border-bottom:1px solid var(--border)}
.cat-table tbody tr:last-child td{border-bottom:none}
.il-form{display:inline;margin:0;padding:0}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Product Categories</h1>
        <p class="page-subtitle">Organise products for filtering and reports</p>
    </div>
    <a href="<?= BASE_URL ?>/products" class="btn btn-secondary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
        </svg>Back to Products
    </a>
</div>

<div class="cat-grid">

    <!-- List -->
    <div class="card">
        <div class="card-header"><h2 class="card-title">Categories</h2></div>
        <div class="table-responsive">
            <table class="cat-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th style="text-align:center">Products</th>
                        <th style="width:70px;text-align:right"></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:1.5rem">
                        No categories yet — add the first one.
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td style="font-weight:600"><?= Utils::e($cat['name']) ?></td>
                        <td style="text-align:center;color:var(--text-secondary)"><?= (int)$cat['product_count'] ?></td>
                        <td style="text-align:right">
                            <form method="POST" action="<?= BASE_URL ?>/product-categories/<?= (int)$cat['category_id'] ?>/delete"
                                  class="il-form" data-confirm="Delete category <?= Utils::e(addslashes($cat['name'])) ?>? Its products become uncategorised.">
                                <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken) ?>">
                                <button type="submit" class="btn btn-xs btn-secondary" style="color:var(--error)">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add form -->
    <div class="card" style="align-self:start">
        <div class="card-header"><h2 class="card-title">Add Category</h2></div>
        <div class="card-body">
            <form method="POST" action="<?= BASE_URL ?>/product-categories">
                <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken) ?>">
                <div class="form-group">
                    <label class="form-label" for="catName">Name <span class="required">*</span></label>
                    <input type="text" id="catName" name="name" class="form-input" required maxlength="100"
                           placeholder="e.g. Screens, Batteries, Accessories">
                </div>
                <div class="form-group">
                    <label class="form-label" for="catSort">Sort Order</label>
                    <input type="number" id="catSort" name="sort_order" class="form-input" value="0" step="1">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Add Category</button>
            </form>
        </div>
    </div>

</div>

<script>
document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('submit', function (e) {
        if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
});
</script>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
