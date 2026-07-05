<?php
$pageTitle = 'Products';
require VIEWS_PATH . '/layouts/header.php';

$search   = Utils::e($_GET['search'] ?? '');
$catFilter   = (int)($_GET['category_id'] ?? 0);
$stockFilter = $_GET['stock'] ?? '';
$pg       = $pagination;
?>
<style>
.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem}
.stat-mini{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);padding:.85rem 1.1rem}
.stat-mini-val{font-size:1.25rem;font-weight:700;line-height:1;margin-bottom:.25rem}
.stat-mini-lbl{font-size:.75rem;color:var(--text-secondary)}
.status-filters{display:flex;gap:.35rem;flex-wrap:wrap;padding:.75rem 1rem;border-bottom:1px solid var(--border)}
.sf-btn{display:inline-flex;align-items:center;gap:.35rem;padding:.3rem .75rem;border-radius:var(--radius-full);border:1px solid var(--border);background:none;color:var(--text-secondary);font-size:.78rem;font-weight:500;cursor:pointer;text-decoration:none;transition:all var(--transition);white-space:nowrap}
.sf-btn:hover{background:var(--bg-tertiary);color:var(--text-primary)}
.sf-btn.active{background:var(--accent-dim);border-color:var(--accent);color:var(--accent)}
.filter-bar{display:flex;gap:.5rem;padding:.75rem 1rem;flex-wrap:wrap;align-items:center;border-bottom:1px solid var(--border)}
.filter-bar .search-input-wrap{flex:1;min-width:200px}
.prod-link{font-weight:600;color:var(--text-primary);text-decoration:none}
.prod-link:hover{color:var(--accent)}
.sku-txt{font-family:var(--font-mono);font-size:.75rem;color:var(--text-muted)}
.qty-badge{display:inline-block;min-width:2.4em;text-align:center;padding:.15rem .5rem;border-radius:var(--radius-full);font-size:.8rem;font-weight:700}
.qty-ok{background:var(--success-bg,rgba(16,185,129,.12));color:var(--success)}
.qty-low{background:var(--warning-bg,rgba(245,158,11,.12));color:var(--warning)}
.qty-out{background:var(--error-bg,rgba(239,68,68,.12));color:var(--error)}
.act-btns{display:flex;gap:.2rem;justify-content:flex-end}
.act-btn{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:var(--radius);background:none;border:1px solid transparent;color:var(--text-muted);cursor:pointer;text-decoration:none;transition:all var(--transition)}
.act-btn svg{width:14px;height:14px}
.act-btn:hover{background:var(--bg-tertiary);color:var(--text-primary);border-color:var(--border)}
.act-btn-d:hover{background:var(--error-bg);color:var(--error);border-color:var(--error)}
.il-form{display:inline;margin:0;padding:0}
.tbl-footer{display:flex;align-items:center;justify-content:space-between;padding:.6rem 1rem;border-top:1px solid var(--border);flex-wrap:wrap;gap:.5rem}
.tbl-footer-info{font-size:.78rem;color:var(--text-secondary)}
.empty-big{display:flex;flex-direction:column;align-items:center;gap:.75rem;padding:3rem 1rem;text-align:center}
.empty-big svg{width:48px;height:48px;stroke:var(--text-muted)}
.empty-big p{color:var(--text-secondary)}
.hide-mobile{display:none}
@media(min-width:700px){.hide-mobile{display:table-cell}}
</style>

<!-- Page header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Products</h1>
        <p class="page-subtitle"><?= number_format($pg['total']) ?> products</p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <?php if (Auth::can('manager')): ?>
        <a href="<?= BASE_URL ?>/product-categories" class="btn btn-secondary">Categories</a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/products/create" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px" aria-hidden="true">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>New Product
        </a>
    </div>
</div>

<!-- Stock stats -->
<div class="stats-row">
    <div class="stat-mini">
        <div class="stat-mini-val"><?= (int)($stockStats['active_products'] ?? 0) ?></div>
        <div class="stat-mini-lbl">Active Products</div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-val"><?= number_format((int)($stockStats['total_units'] ?? 0)) ?></div>
        <div class="stat-mini-lbl">Units in Stock</div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-val"><?= Utils::formatCurrency($stockStats['cost_value'] ?? 0) ?></div>
        <div class="stat-mini-lbl">Stock Value (cost)</div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-val" style="color:<?= ($stockStats['low_stock_count'] ?? 0) > 0 ? 'var(--warning)' : 'var(--text-primary)' ?>">
            <?= (int)($stockStats['low_stock_count'] ?? 0) ?>
        </div>
        <div class="stat-mini-lbl">Low Stock</div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-val" style="color:<?= ($stockStats['out_of_stock_count'] ?? 0) > 0 ? 'var(--error)' : 'var(--text-primary)' ?>">
            <?= (int)($stockStats['out_of_stock_count'] ?? 0) ?>
        </div>
        <div class="stat-mini-lbl">Out of Stock</div>
    </div>
</div>

<div class="card" style="margin-bottom:0">

    <!-- Stock filters -->
    <div class="status-filters">
        <?php $bp = array_filter(['search' => htmlspecialchars_decode($search), 'category_id' => $catFilter ?: null]); ?>
        <a href="<?= Utils::url('/products', $bp) ?>" class="sf-btn <?= $stockFilter === '' ? 'active' : '' ?>">All</a>
        <a href="<?= Utils::url('/products', array_merge($bp, ['stock' => 'in']))  ?>" class="sf-btn <?= $stockFilter === 'in'  ? 'active' : '' ?>">In Stock</a>
        <a href="<?= Utils::url('/products', array_merge($bp, ['stock' => 'low'])) ?>" class="sf-btn <?= $stockFilter === 'low' ? 'active' : '' ?>">Low Stock</a>
        <a href="<?= Utils::url('/products', array_merge($bp, ['stock' => 'out'])) ?>" class="sf-btn <?= $stockFilter === 'out' ? 'active' : '' ?>">Out of Stock</a>
    </div>

    <!-- Search & category filter -->
    <form method="GET" action="<?= BASE_URL ?>/products" class="filter-bar" role="search">
        <div class="search-input-wrap">
            <svg class="search-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="search" name="search" class="form-input"
                   placeholder="Product name, SKU or description…"
                   value="<?= $search ?>" autocomplete="off">
        </div>
        <select name="category_id" class="form-input" style="width:auto">
            <option value="">All categories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat['category_id'] ?>" <?= $catFilter === (int)$cat['category_id'] ? 'selected' : '' ?>>
                <?= Utils::e($cat['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="stock" value="<?= Utils::e($stockFilter) ?>">
        <button type="submit" class="btn btn-primary">Filter</button>
        <?php if ($search !== '' || $catFilter || $stockFilter !== ''): ?>
        <a href="<?= BASE_URL ?>/products" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>

    <!-- Table -->
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="hide-mobile">Category</th>
                    <th style="text-align:right">Price</th>
                    <th class="hide-mobile" style="text-align:right">Cost</th>
                    <th style="text-align:center">Stock</th>
                    <th style="width:90px;text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($products)): ?>
                <tr><td colspan="6">
                    <div class="empty-big">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                            <line x1="12" y1="22.08" x2="12" y2="12"/>
                        </svg>
                        <p>
                            <?php if ($search !== ''): ?>
                                No products match "<strong><?= Utils::e(htmlspecialchars_decode($search)) ?></strong>".
                            <?php else: ?>
                                No products yet.
                            <?php endif; ?>
                        </p>
                        <a href="<?= BASE_URL ?>/products/create" class="btn btn-primary">Add First Product</a>
                    </div>
                </td></tr>
            <?php else: ?>
                <?php foreach ($products as $p):
                    $qty  = (int)$p['quantity_on_hand'];
                    $thr  = (int)($p['low_stock_threshold'] ?? 0);
                    $qCls = $qty === 0 ? 'qty-out' : (($thr > 0 && $qty <= $thr) ? 'qty-low' : 'qty-ok');
                ?>
                <tr>
                    <td>
                        <a href="<?= BASE_URL ?>/products/<?= $p['product_id'] ?>" class="prod-link">
                            <?= Utils::e($p['name']) ?>
                        </a>
                        <?php if (!empty($p['sku'])): ?>
                        <div class="sku-txt"><?= Utils::e($p['sku']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="hide-mobile" style="font-size:.82rem;color:var(--text-secondary)">
                        <?= Utils::e($p['category_name'] ?? '—') ?>
                    </td>
                    <td style="text-align:right;font-size:.85rem"><?= Utils::formatCurrency($p['selling_price']) ?></td>
                    <td class="hide-mobile" style="text-align:right;font-size:.83rem;color:var(--text-secondary)">
                        <?= $p['cost_price'] !== null ? Utils::formatCurrency($p['cost_price']) : '—' ?>
                    </td>
                    <td style="text-align:center">
                        <span class="qty-badge <?= $qCls ?>"><?= $qty ?></span>
                    </td>
                    <td>
                        <div class="act-btns">
                            <a href="<?= BASE_URL ?>/products/<?= $p['product_id'] ?>" class="act-btn" title="View product">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            <a href="<?= BASE_URL ?>/products/<?= $p['product_id'] ?>/edit" class="act-btn" title="Edit product">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            <?php if (Auth::can('manager')): ?>
                            <form method="POST" action="<?= BASE_URL ?>/products/<?= $p['product_id'] ?>/delete"
                                  class="il-form" data-confirm="Delete product <?= Utils::e(addslashes($p['name'])) ?>?">
                                <input type="hidden" name="csrf_token" value="<?= Utils::e(Auth::generateCSRFToken()) ?>">
                                <button type="submit" class="act-btn act-btn-d" title="Delete">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6M9 6V4h6v2"/></svg>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pg['totalPages'] > 1):
        $bpP = array_filter(['search' => htmlspecialchars_decode($search), 'category_id' => $catFilter ?: null, 'stock' => $stockFilter]);
    ?>
    <div class="tbl-footer">
        <span class="tbl-footer-info">
            <?= number_format($pg['offset'] + 1) ?>–<?= number_format(min($pg['offset'] + $pg['perPage'], $pg['total'])) ?>
            of <?= number_format($pg['total']) ?>
        </span>
        <nav class="pagination" aria-label="Pagination">
            <a href="<?= $pg['hasPrev'] ? Utils::url('/products', array_merge($bpP, ['page' => $pg['page'] - 1])) : '#' ?>"
               class="page-link <?= !$pg['hasPrev'] ? 'disabled' : '' ?>">&laquo;</a>
            <?php
            $s2 = max(1, $pg['page'] - 2);
            $e2 = min($pg['totalPages'], $pg['page'] + 2);
            if ($s2 > 1) echo '<span class="page-link disabled">…</span>';
            for ($p2 = $s2; $p2 <= $e2; $p2++): ?>
            <a href="<?= Utils::url('/products', array_merge($bpP, ['page' => $p2])) ?>"
               class="page-link <?= $p2 === $pg['page'] ? 'current' : '' ?>"><?= $p2 ?></a>
            <?php endfor;
            if ($e2 < $pg['totalPages']) echo '<span class="page-link disabled">…</span>';
            ?>
            <a href="<?= $pg['hasNext'] ? Utils::url('/products', array_merge($bpP, ['page' => $pg['page'] + 1])) : '#' ?>"
               class="page-link <?= !$pg['hasNext'] ? 'disabled' : '' ?>">&raquo;</a>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('submit', function (e) {
        if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
});
</script>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
