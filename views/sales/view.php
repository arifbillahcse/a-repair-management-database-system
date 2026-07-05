<?php
$pageTitle = 'Sale ' . $sale['sale_number'];
require VIEWS_PATH . '/layouts/header.php';

$total   = (float)$sale['total_amount'];
$paid    = (float)$sale['amount_paid'];
$balance = round($total - $paid, 2);
$custLabel = $sale['linked_customer_name'] ?: ($sale['customer_name'] ?: 'Walk-in customer');
?>
<style>
.sv-grid{display:grid;grid-template-columns:1fr;gap:1.5rem}
@media(min-width:960px){.sv-grid{grid-template-columns:1fr 340px}}
.items-view{width:100%;border-collapse:collapse;font-size:.86rem}
.items-view th{padding:.55rem .9rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);border-bottom:1px solid var(--border);text-align:left}
.items-view td{padding:.6rem .9rem;border-bottom:1px solid var(--border)}
.items-view tbody tr:last-child td{border-bottom:none}
.totals-table{width:100%;border-collapse:collapse}
.totals-table td{padding:.45rem .75rem;font-size:.88rem;border-bottom:1px solid var(--border)}
.totals-table tr:last-child td{border-bottom:none;font-weight:700;font-size:.95rem}
.totals-table .t-label{color:var(--text-secondary)}
.totals-table .t-val{text-align:right}
.sku-txt{font-family:var(--font-mono);font-size:.73rem;color:var(--text-muted)}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title" style="display:flex;align-items:center;gap:.6rem">
            Sale <?= Utils::e($sale['sale_number']) ?>
            <span class="badge <?= SALE_STATUS_CLASS[$sale['status']] ?? 'badge-gray' ?>">
                <?= Utils::e(SALE_STATUS[$sale['status']] ?? $sale['status']) ?>
            </span>
        </h1>
        <p class="page-subtitle">
            <?= Utils::formatDate($sale['sale_date']) ?> &nbsp;·&nbsp; <?= Utils::e($custLabel) ?>
        </p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <a href="<?= BASE_URL ?>/sales/<?= (int)$sale['sale_id'] ?>/print" target="_blank" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px" aria-hidden="true">
                <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>
            </svg>Print Receipt
        </a>
        <a href="<?= BASE_URL ?>/sales" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
            </svg>All Sales
        </a>
    </div>
</div>

<div class="sv-grid">

    <!-- ── Left: items ──────────────────────────────────────────── -->
    <div style="display:flex;flex-direction:column;gap:1.5rem">

        <div class="card">
            <div class="card-header"><h2 class="card-title">Items</h2></div>
            <div class="table-responsive">
                <table class="items-view">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th style="text-align:center">Qty</th>
                            <th style="text-align:right">Unit Price</th>
                            <th style="text-align:right">Disc.</th>
                            <th style="text-align:right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($sale['items'] as $item): ?>
                        <tr>
                            <td>
                                <?= Utils::e($item['description']) ?>
                                <?php if (!empty($item['product_id'])): ?>
                                <div class="sku-txt">
                                    <a href="<?= BASE_URL ?>/products/<?= (int)$item['product_id'] ?>" style="color:var(--text-muted)">
                                        <?= Utils::e($item['sku'] ?: 'product #' . $item['product_id']) ?>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center"><?= rtrim(rtrim(number_format((float)$item['quantity'], 3), '0'), '.') ?></td>
                            <td style="text-align:right"><?= Utils::formatCurrency($item['unit_price']) ?></td>
                            <td style="text-align:right;color:var(--text-secondary)">
                                <?= (float)$item['discount_pct'] > 0 ? rtrim(rtrim(number_format((float)$item['discount_pct'], 2), '0'), '.') . '%' : '—' ?>
                            </td>
                            <td style="text-align:right;font-weight:600"><?= Utils::formatCurrency($item['line_total']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (!empty($sale['notes'])): ?>
        <div class="card">
            <div class="card-header"><h2 class="card-title">Notes</h2></div>
            <div class="card-body" style="font-size:.87rem;color:var(--text-secondary)">
                <?= nl2br(Utils::e($sale['notes'])) ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- ── Right: totals + payment ──────────────────────────────── -->
    <div style="display:flex;flex-direction:column;gap:1.5rem">

        <div class="card">
            <div class="card-header"><h2 class="card-title">Totals</h2></div>
            <div class="card-body" style="padding:0">
                <table class="totals-table">
                    <tr><td class="t-label">Subtotal</td><td class="t-val"><?= Utils::formatCurrency($sale['subtotal']) ?></td></tr>
                    <tr><td class="t-label">Tax (<?= rtrim(rtrim(number_format((float)$sale['tax_percentage'], 2), '0'), '.') ?>%)</td>
                        <td class="t-val"><?= Utils::formatCurrency($sale['tax_amount']) ?></td></tr>
                    <tr><td class="t-label">Total</td><td class="t-val"><?= Utils::formatCurrency($total) ?></td></tr>
                    <tr><td class="t-label">Paid</td>
                        <td class="t-val" style="color:var(--success)"><?= Utils::formatCurrency($paid) ?></td></tr>
                    <tr><td class="t-label">Balance</td>
                        <td class="t-val" style="color:<?= $balance > 0 ? 'var(--error)' : 'var(--success)' ?>">
                            <?= Utils::formatCurrency($balance) ?>
                        </td></tr>
                </table>
            </div>
        </div>

        <?php if ($sale['status'] !== 'paid' && $sale['status'] !== 'cancelled'): ?>
        <div class="card">
            <div class="card-header"><h2 class="card-title">Record Payment</h2></div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>/sales/<?= (int)$sale['sale_id'] ?>/paid">
                    <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken) ?>">
                    <div class="form-group">
                        <label class="form-label" for="payAmount">Total Amount Paid (€)</label>
                        <input type="number" id="payAmount" name="amount_paid" class="form-input"
                               value="<?= $total ?>" step="0.01" min="0" required>
                        <p style="font-size:.72rem;color:var(--text-muted);margin-top:.35rem">
                            Enter the cumulative amount paid so far.
                        </p>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                        Save Payment
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($sale['customer_id'])): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Customer</h2>
                <a href="<?= BASE_URL ?>/customers/<?= (int)$sale['customer_id'] ?>" class="btn btn-xs btn-secondary">Profile</a>
            </div>
            <div class="card-body" style="font-size:.86rem;line-height:1.7;color:var(--text-secondary)">
                <strong style="color:var(--text-primary)"><?= Utils::e($sale['linked_customer_name']) ?></strong><br>
                <?php if (!empty($sale['customer_phone'])): ?><?= Utils::e($sale['customer_phone']) ?><br><?php endif; ?>
                <?php if (!empty($sale['customer_email'])): ?><?= Utils::e($sale['customer_email']) ?><br><?php endif; ?>
                <?php if (!empty($sale['customer_address'])): ?><?= Utils::e($sale['customer_address']) ?><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (Auth::can('manager')): ?>
        <form method="POST" action="<?= BASE_URL ?>/sales/<?= (int)$sale['sale_id'] ?>/delete"
              data-confirm="Delete sale <?= Utils::e(addslashes($sale['sale_number'])) ?>? Sold units return to stock.">
            <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken) ?>">
            <button type="submit" class="btn btn-secondary" style="width:100%;justify-content:center;color:var(--error)">
                Delete Sale
            </button>
        </form>
        <?php endif; ?>

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
