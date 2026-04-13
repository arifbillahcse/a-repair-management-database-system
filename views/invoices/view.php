<?php
$pageTitle = 'Invoice ' . Utils::e($invoice['invoice_number']);
require VIEWS_PATH . '/layouts/header.php';

$items   = $invoice['items'] ?? [];
$total   = (float)($invoice['total_amount'] ?? 0);
$paid    = (float)($invoice['amount_paid']  ?? 0);
$balance = round($total - $paid, 2);
$isOverdue = ($invoice['status'] === 'overdue')
    || ($invoice['status'] === 'sent' && !empty($invoice['due_date']) && strtotime($invoice['due_date']) < time());
?>
<style>
.inv-header{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.inv-num{font-size:1.6rem;font-weight:800;font-family:var(--font-mono);color:var(--text-primary);line-height:1}
.inv-meta{display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;margin-top:.35rem}
.inv-chip{font-size:.75rem;color:var(--text-muted);background:var(--bg-tertiary);border:1px solid var(--border);border-radius:var(--radius-full);padding:.15rem .6rem}
.inv-chip.overdue{background:var(--error-bg);color:var(--error);border-color:var(--error)}

.inv-grid{display:grid;grid-template-columns:1fr;gap:1.25rem}
@media(min-width:900px){.inv-grid{grid-template-columns:1fr 320px}}

.section-card{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden}
.section-header{display:flex;align-items:center;justify-content:space-between;padding:.75rem 1.25rem;border-bottom:1px solid var(--border)}
.section-title{font-size:.9rem;font-weight:600;margin:0}

.items-table{width:100%;border-collapse:collapse}
.items-table th{padding:.6rem 1rem;font-size:.72rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid var(--border);background:var(--bg-tertiary);text-align:left}
.items-table td{padding:.7rem 1rem;border-bottom:1px solid var(--border);font-size:.86rem;vertical-align:top}
.items-table tbody tr:last-child td{border-bottom:none}
.items-table th:last-child,.items-table td:last-child{text-align:right}
.items-table .desc-cell{max-width:300px}

.totals-box{background:var(--bg-tertiary);border-top:2px solid var(--border)}
.totals-row{display:flex;justify-content:space-between;align-items:baseline;padding:.5rem 1.25rem;border-bottom:1px solid var(--border);font-size:.88rem}
.totals-row:last-child{border-bottom:none;font-weight:700;font-size:1rem;padding:.75rem 1.25rem}
.totals-label{color:var(--text-secondary)}

.info-list{list-style:none;padding:0;margin:0}
.info-item{display:flex;gap:.75rem;padding:.6rem 1.25rem;border-bottom:1px solid var(--border)}
.info-item:last-child{border-bottom:none}
.info-icon{width:15px;height:15px;flex-shrink:0;margin-top:.15rem;stroke:var(--text-muted)}
.info-body .info-label{display:block;font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.1rem}
.info-body .info-value{font-size:.86rem;color:var(--text-primary)}

.pay-form{padding:1rem 1.25rem;background:var(--accent-dim);border-top:1px solid var(--accent)}
</style>

<!-- Invoice header -->
<div class="inv-header">
    <div>
        <div class="inv-num"><?= Utils::e($invoice['invoice_number']) ?></div>
        <div class="inv-meta">
            <span class="badge <?= INVOICE_STATUS_CLASS[$invoice['status']] ?? 'badge-gray' ?>">
                <?= Utils::e(INVOICE_STATUS[$invoice['status']] ?? $invoice['status']) ?>
            </span>
            <?php if ($isOverdue): ?>
            <span class="inv-chip overdue">OVERDUE</span>
            <?php endif; ?>
            <?php if (!empty($invoice['repair_id'])): ?>
            <a href="<?= BASE_URL ?>/repairs/<?= $invoice['repair_id'] ?>" class="inv-chip" style="text-decoration:none;color:inherit">
                Repair #<?= $invoice['repair_id'] ?>
            </a>
            <?php endif; ?>
            <span class="inv-chip">Issued <?= Utils::formatDate($invoice['invoice_date']) ?></span>
            <?php if (!empty($invoice['due_date'])): ?>
            <span class="inv-chip <?= $isOverdue ? 'overdue' : '' ?>">Due <?= Utils::formatDate($invoice['due_date']) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <a href="<?= BASE_URL ?>/invoices/<?= $invoice['invoice_id'] ?>/print" target="_blank" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polyline points="6 9 6 2 18 2 18 9"/>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
            </svg>Print
        </a>
        <?php if ($invoice['status'] === 'draft'): ?>
        <form method="POST" action="<?= BASE_URL ?>/invoices/<?= $invoice['invoice_id'] ?>/send" style="display:inline">
            <input type="hidden" name="csrf_token" value="<?= Utils::e(Auth::generateCSRFToken()) ?>">
            <button type="submit" class="btn btn-secondary">Mark as Sent</button>
        </form>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/invoices" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
            </svg>Back
        </a>
    </div>
</div>

<div class="inv-grid">

    <!-- Left: items + totals -->
    <div>

        <!-- Line items -->
        <div class="section-card" style="margin-bottom:1.25rem">
            <div class="section-header"><h2 class="section-title">Line Items</h2></div>
            <div class="table-responsive">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th style="width:70px;text-align:center">Qty</th>
                            <th style="width:110px;text-align:right">Unit Price</th>
                            <th style="width:70px;text-align:right">Disc.</th>
                            <th style="width:60px;text-align:right">Tax</th>
                            <th style="width:110px">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="6" class="empty-state">No line items.</td></tr>
                    <?php else: ?>
                        <?php foreach ($items as $item):
                            $lineNet = (float)($item['line_total'] ?? 0);
                            $taxAmt  = $lineNet * ((float)($item['tax_percentage'] ?? 0) / 100);
                        ?>
                        <tr>
                            <td class="desc-cell">
                                <?= Utils::e($item['description']) ?>
                                <?php if (!empty($item['sku'])): ?>
                                <div style="font-size:.72rem;color:var(--text-muted);font-family:var(--font-mono)"><?= Utils::e($item['sku']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;color:var(--text-secondary)"><?= (float)$item['quantity'] ?></td>
                            <td style="text-align:right"><?= Utils::formatCurrency($item['unit_price']) ?></td>
                            <td style="text-align:right;color:var(--text-muted)">
                                <?= (float)($item['discount_pct'] ?? 0) > 0 ? number_format((float)$item['discount_pct'], 1) . '%' : '—' ?>
                            </td>
                            <td style="text-align:right;color:var(--text-secondary);font-size:.8rem">
                                <?= (float)($item['tax_percentage'] ?? 0) > 0 ? number_format((float)$item['tax_percentage'], 1) . '%' : '0%' ?>
                            </td>
                            <td style="text-align:right;font-weight:500"><?= Utils::formatCurrency($lineNet) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div class="totals-box">
                <div class="totals-row">
                    <span class="totals-label">Subtotal</span>
                    <span><?= Utils::formatCurrency($invoice['subtotal'] ?? 0) ?></span>
                </div>
                <?php if ((float)($invoice['tax_amount'] ?? 0) > 0): ?>
                <div class="totals-row">
                    <span class="totals-label">Tax (<?= number_format((float)($invoice['tax_percentage'] ?? 0), 1) ?>%)</span>
                    <span><?= Utils::formatCurrency($invoice['tax_amount'] ?? 0) ?></span>
                </div>
                <?php endif; ?>
                <div class="totals-row">
                    <span class="totals-label">Total</span>
                    <span><?= Utils::formatCurrency($total) ?></span>
                </div>
                <?php if ($paid > 0): ?>
                <div class="totals-row" style="color:var(--success)">
                    <span>Paid</span>
                    <span>-<?= Utils::formatCurrency($paid) ?></span>
                </div>
                <?php endif; ?>
                <div class="totals-row" style="color:<?= $balance > 0 ? 'var(--error)' : 'var(--success)' ?>">
                    <span>Balance Due</span>
                    <span><?= Utils::formatCurrency($balance) ?></span>
                </div>
            </div>

            <!-- Mark paid form -->
            <?php if (!in_array($invoice['status'], ['paid', 'cancelled'], true)): ?>
            <div class="pay-form">
                <p style="font-size:.78rem;font-weight:600;color:var(--accent);margin:0 0 .6rem">Record Payment</p>
                <form method="POST" action="<?= BASE_URL ?>/invoices/<?= $invoice['invoice_id'] ?>/paid"
                      style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:flex-end">
                    <input type="hidden" name="csrf_token" value="<?= Utils::e(Auth::generateCSRFToken()) ?>">
                    <div class="form-group" style="margin:0;flex:1;min-width:140px">
                        <label class="form-label" for="amountPaid" style="font-size:.72rem">Amount Paid (€)</label>
                        <input type="number" id="amountPaid" name="amount_paid" class="form-input"
                               value="<?= number_format($balance, 2, '.', '') ?>"
                               step="0.01" min="0" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px" aria-hidden="true">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>Record
                    </button>
                </form>
            </div>
            <?php endif; ?>

        </div>

        <!-- Notes -->
        <?php if (!empty($invoice['notes'])): ?>
        <div class="section-card">
            <div class="section-header"><h2 class="section-title">Notes</h2></div>
            <div style="padding:1rem 1.25rem;font-size:.86rem;color:var(--text-secondary);line-height:1.7;white-space:pre-wrap">
                <?= Utils::e($invoice['notes']) ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Right: customer info + meta -->
    <div style="display:flex;flex-direction:column;gap:1.25rem">

        <!-- Client -->
        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">Client</h2>
                <a href="<?= BASE_URL ?>/customers/<?= $invoice['customer_id'] ?>" class="btn btn-xs btn-secondary">Profile</a>
            </div>
            <ul class="info-list">
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Name</span>
                        <span class="info-value">
                            <a href="<?= BASE_URL ?>/customers/<?= $invoice['customer_id'] ?>"
                               style="color:inherit;text-decoration:none"
                               onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='inherit'">
                                <?= Utils::e($invoice['customer_name'] ?? '—') ?>
                            </a>
                        </span>
                    </div>
                </li>
                <?php if (!empty($invoice['customer_phone'])): ?>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 11.87 19a19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Phone</span>
                        <span class="info-value"><a href="tel:<?= Utils::e($invoice['customer_phone']) ?>" style="color:inherit"><?= Utils::e($invoice['customer_phone']) ?></a></span>
                    </div>
                </li>
                <?php endif; ?>
                <?php if (!empty($invoice['customer_email'])): ?>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Email</span>
                        <span class="info-value"><a href="mailto:<?= Utils::e($invoice['customer_email']) ?>" style="color:inherit"><?= Utils::e($invoice['customer_email']) ?></a></span>
                    </div>
                </li>
                <?php endif; ?>
                <?php if (!empty($invoice['customer_vat'])): ?>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">VAT Number</span>
                        <span class="info-value" style="font-family:var(--font-mono)"><?= Utils::e($invoice['customer_vat']) ?></span>
                    </div>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Record info -->
        <div class="section-card">
            <div class="section-header"><h2 class="section-title">Record</h2></div>
            <ul class="info-list">
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Created</span>
                        <span class="info-value"><?= Utils::formatDateTime($invoice['created_at'] ?? '') ?></span>
                    </div>
                </li>
                <?php if ($paid > 0): ?>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Amount Paid</span>
                        <span class="info-value" style="color:var(--success)"><?= Utils::formatCurrency($paid) ?></span>
                    </div>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Danger zone -->
        <?php if (Auth::can('manager')): ?>
        <div class="section-card" style="border-color:var(--error-bg)">
            <div class="section-header" style="background:var(--error-bg)">
                <h2 class="section-title" style="color:var(--error)">Danger Zone</h2>
            </div>
            <div style="padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
                <p style="font-size:.82rem;color:var(--text-secondary);margin:0">Permanently delete this invoice and its line items.</p>
                <form method="POST" action="<?= BASE_URL ?>/invoices/<?= $invoice['invoice_id'] ?>/delete"
                      data-confirm="Delete invoice <?= Utils::e(addslashes($invoice['invoice_number'])) ?>? This cannot be undone.">
                    <input type="hidden" name="csrf_token" value="<?= Utils::e(Auth::generateCSRFToken()) ?>">
                    <button type="submit" class="btn btn-danger">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px" aria-hidden="true">
                            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6M9 6V4h6v2"/>
                        </svg>Delete
                    </button>
                </form>
            </div>
        </div>
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
