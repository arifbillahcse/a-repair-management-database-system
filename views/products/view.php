<?php
$pageTitle = $product['name'];
require VIEWS_PATH . '/layouts/header.php';

$qty = (int)$product['quantity_on_hand'];
$thr = (int)($product['low_stock_threshold'] ?? 0);
$stockState = $qty === 0 ? 'out' : (($thr > 0 && $qty <= $thr) ? 'low' : 'ok');
?>
<style>
.pv-grid{display:grid;grid-template-columns:1fr;gap:1.5rem}
@media(min-width:960px){.pv-grid{grid-template-columns:1fr 360px}}
.meta-table{width:100%;border-collapse:collapse;font-size:.87rem}
.meta-table td{padding:.5rem .75rem;border-bottom:1px solid var(--border)}
.meta-table tr:last-child td{border-bottom:none}
.meta-table .m-label{color:var(--text-secondary);width:40%}
.stock-big{display:flex;align-items:baseline;gap:.5rem}
.stock-big-num{font-size:2.2rem;font-weight:800;line-height:1}
.stock-ok{color:var(--success)}
.stock-low{color:var(--warning)}
.stock-out{color:var(--error)}
.mv-table{width:100%;border-collapse:collapse;font-size:.83rem}
.mv-table th{padding:.5rem .75rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);border-bottom:1px solid var(--border);text-align:left;white-space:nowrap}
.mv-table td{padding:.5rem .75rem;border-bottom:1px solid var(--border);vertical-align:middle}
.mv-table tbody tr:last-child td{border-bottom:none}
.mv-plus{color:var(--success);font-weight:700}
.mv-minus{color:var(--error);font-weight:700}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= Utils::e($product['name']) ?></h1>
        <p class="page-subtitle">
            <?= !empty($product['sku']) ? 'SKU ' . Utils::e($product['sku']) . ' · ' : '' ?>
            <?= Utils::e($product['category_name'] ?? 'Uncategorised') ?>
            <?= !$product['is_active'] ? ' · <span style="color:var(--error)">inactive</span>' : '' ?>
        </p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <a href="<?= BASE_URL ?>/products/<?= (int)$product['product_id'] ?>/edit" class="btn btn-secondary">Edit</a>
        <a href="<?= BASE_URL ?>/products" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
            </svg>All Products
        </a>
    </div>
</div>

<div class="pv-grid">

    <!-- ── Left: details + movement history ─────────────────────── -->
    <div style="display:flex;flex-direction:column;gap:1.5rem">

        <div class="card">
            <div class="card-header"><h2 class="card-title">Details</h2></div>
            <table class="meta-table">
                <tr><td class="m-label">Selling Price</td><td><strong><?= Utils::formatCurrency($product['selling_price']) ?></strong></td></tr>
                <tr><td class="m-label">Cost Price</td><td><?= $product['cost_price'] !== null ? Utils::formatCurrency($product['cost_price']) : '—' ?></td></tr>
                <tr><td class="m-label">Low-Stock Threshold</td><td><?= $thr > 0 ? $thr . ' units' : 'no alert' ?></td></tr>
                <tr><td class="m-label">Stock Value (cost)</td>
                    <td><?= $product['cost_price'] !== null ? Utils::formatCurrency($qty * (float)$product['cost_price']) : '—' ?></td></tr>
                <?php if (!empty($product['description'])): ?>
                <tr><td class="m-label">Description</td><td style="color:var(--text-secondary)"><?= nl2br(Utils::e($product['description'])) ?></td></tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Stock History</h2></div>
            <div class="table-responsive">
                <table class="mv-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th style="text-align:right">Change</th>
                            <th>Reason</th>
                            <th>Note</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($movements)): ?>
                        <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:1.5rem">
                            No stock movements yet.
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($movements as $mv): $chg = (int)$mv['change_qty']; ?>
                        <tr>
                            <td style="white-space:nowrap;color:var(--text-secondary)"><?= Utils::formatDateTime($mv['created_at']) ?></td>
                            <td style="text-align:right" class="<?= $chg >= 0 ? 'mv-plus' : 'mv-minus' ?>">
                                <?= $chg >= 0 ? '+' : '' ?><?= $chg ?>
                            </td>
                            <td><?= Utils::e(STOCK_REASONS[$mv['reason']] ?? $mv['reason']) ?></td>
                            <td style="color:var(--text-secondary)"><?= Utils::e($mv['note'] ?: '—') ?></td>
                            <td style="color:var(--text-muted)"><?= Utils::e($mv['username'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ── Right: current stock + adjust form ───────────────────── -->
    <div style="display:flex;flex-direction:column;gap:1.5rem">

        <div class="card">
            <div class="card-header"><h2 class="card-title">Current Stock</h2></div>
            <div class="card-body">
                <div class="stock-big">
                    <span class="stock-big-num stock-<?= $stockState ?>"><?= $qty ?></span>
                    <span style="color:var(--text-secondary)">units on hand</span>
                </div>
                <?php if ($stockState === 'out'): ?>
                <p style="color:var(--error);font-size:.82rem;margin-top:.5rem">Out of stock.</p>
                <?php elseif ($stockState === 'low'): ?>
                <p style="color:var(--warning);font-size:.82rem;margin-top:.5rem">
                    Low stock — at or below the alert threshold of <?= $thr ?>.
                </p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (Auth::can('manager')): ?>
        <div class="card">
            <div class="card-header"><h2 class="card-title">Adjust Stock</h2></div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>/products/<?= (int)$product['product_id'] ?>/stock">
                    <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken) ?>">

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
                        <div class="form-group">
                            <label class="form-label" for="adjDir">Direction</label>
                            <select id="adjDir" name="direction" class="form-input">
                                <option value="in">Stock In (+)</option>
                                <option value="out">Stock Out (−)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="adjQty">Quantity</label>
                            <input type="number" id="adjQty" name="qty" class="form-input" min="1" step="1" value="1" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="adjReason">Reason</label>
                        <select id="adjReason" name="reason" class="form-input">
                            <?php foreach (STOCK_REASONS as $key => $label): ?>
                            <?php if ($key === 'sold') continue; // 'sold' is reserved for sales ?>
                            <option value="<?= $key ?>"><?= Utils::e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="adjNote">Note</label>
                        <input type="text" id="adjNote" name="note" class="form-input" maxlength="255"
                               placeholder="optional — e.g. order #, reason detail">
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                        Apply Adjustment
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
