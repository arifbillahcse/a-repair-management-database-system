<?php
$pageTitle  = 'Invoices';
require VIEWS_PATH . '/layouts/header.php';

$search    = Utils::e($_GET['search']    ?? '');
$filterSt  = $_GET['status']   ?? '';
$dateFrom  = Utils::e($_GET['date_from'] ?? '');
$dateTo    = Utils::e($_GET['date_to']   ?? '');
$custFilter = (int)($_GET['customer_id'] ?? 0);
$pg        = $pagination;

$statusCounts = array_fill_keys(array_keys(INVOICE_STATUS), 0);
foreach ($invoices as $inv) {
    $statusCounts[$inv['status']] = ($statusCounts[$inv['status']] ?? 0);
}
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
.sort-lnk{color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:1px;white-space:nowrap}
.sort-lnk:hover{color:var(--accent)}
.inv-num-link{font-weight:700;color:var(--text-primary);text-decoration:none;font-family:var(--font-mono)}
.inv-num-link:hover{color:var(--accent)}
.cust-link{color:var(--text-primary);text-decoration:none;font-size:.85rem}
.cust-link:hover{color:var(--accent)}
.act-btns{display:flex;gap:.2rem;justify-content:flex-end}
.act-btn{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:var(--radius);background:none;border:1px solid transparent;color:var(--text-muted);cursor:pointer;text-decoration:none;transition:all var(--transition)}
.act-btn svg{width:14px;height:14px}
.act-btn:hover{background:var(--bg-tertiary);color:var(--text-primary);border-color:var(--border)}
.act-btn-p:hover{background:var(--accent-dim);color:var(--accent);border-color:var(--accent)}
.act-btn-d:hover{background:var(--error-bg);color:var(--error);border-color:var(--error)}
.il-form{display:inline;margin:0;padding:0}
.tbl-footer{display:flex;align-items:center;justify-content:space-between;padding:.6rem 1rem;border-top:1px solid var(--border);flex-wrap:wrap;gap:.5rem}
.tbl-footer-info{font-size:.78rem;color:var(--text-secondary)}
.empty-big{display:flex;flex-direction:column;align-items:center;gap:.75rem;padding:3rem 1rem;text-align:center}
.empty-big svg{width:48px;height:48px;stroke:var(--text-muted)}
.empty-big p{color:var(--text-secondary)}
.overdue{color:var(--error)}
.hide-mobile{display:none}
@media(min-width:700px){.hide-mobile{display:table-cell}}
.hide-t{display:none}
@media(min-width:1000px){.hide-t{display:table-cell}}
</style>

<!-- Page header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Invoices</h1>
        <p class="page-subtitle"><?= number_format($pg['total']) ?> total invoices</p>
    </div>
</div>

<!-- Monthly stats -->
<div class="stats-row">
    <div class="stat-mini">
        <div class="stat-mini-val"><?= Utils::formatCurrency($monthStats['total_revenue'] ?? 0) ?></div>
        <div class="stat-mini-lbl">Revenue This Month</div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-val" style="color:var(--success)"><?= Utils::formatCurrency($monthStats['total_paid'] ?? 0) ?></div>
        <div class="stat-mini-lbl">Collected This Month</div>
    </div>
    <div class="stat-mini">
        <?php $outstanding = ($monthStats['total_revenue'] ?? 0) - ($monthStats['total_paid'] ?? 0); ?>
        <div class="stat-mini-val" style="color:<?= $outstanding > 0 ? 'var(--warning)' : 'var(--text-primary)' ?>"><?= Utils::formatCurrency($outstanding) ?></div>
        <div class="stat-mini-lbl">Outstanding This Month</div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-val"><?= (int)($monthStats['invoice_count'] ?? 0) ?></div>
        <div class="stat-mini-lbl">Invoices This Month</div>
    </div>
</div>

<div class="card" style="margin-bottom:0">

    <!-- Status filters -->
    <div class="status-filters">
        <?php
        $bp = array_filter(['search' => htmlspecialchars_decode($search), 'date_from' => $dateFrom, 'date_to' => $dateTo, 'customer_id' => $custFilter ?: null]);
        ?>
        <a href="<?= Utils::url('/invoices', $bp) ?>" class="sf-btn <?= $filterSt === '' ? 'active' : '' ?>">All</a>
        <?php foreach (INVOICE_STATUS as $key => $label): ?>
        <a href="<?= Utils::url('/invoices', array_merge($bp, ['status' => $key, 'page' => 1])) ?>"
           class="sf-btn <?= $filterSt === $key ? 'active' : '' ?>">
            <?= Utils::e($label) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Search & date filter -->
    <form method="GET" action="<?= BASE_URL ?>/invoices" class="filter-bar" role="search">
        <div class="search-input-wrap">
            <svg class="search-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="search" name="search" class="form-input"
                   placeholder="Invoice number or customer name…"
                   value="<?= $search ?>" autocomplete="off">
        </div>
        <input type="date" name="date_from" class="form-input" style="width:auto" value="<?= $dateFrom ?>" title="From date">
        <input type="date" name="date_to"   class="form-input" style="width:auto" value="<?= $dateTo ?>"   title="To date">
        <?php if ($custFilter): ?>
        <input type="hidden" name="customer_id" value="<?= $custFilter ?>">
        <?php endif; ?>
        <input type="hidden" name="status" value="<?= Utils::e($filterSt) ?>">
        <button type="submit" class="btn btn-primary">Filter</button>
        <?php if ($search !== '' || $filterSt !== '' || $dateFrom !== '' || $dateTo !== '' || $custFilter): ?>
        <a href="<?= BASE_URL ?>/invoices" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>

    <!-- Table -->
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:150px">Invoice #</th>
                    <th>Customer</th>
                    <th class="hide-mobile">Date</th>
                    <th class="hide-mobile">Due Date</th>
                    <th>Status</th>
                    <th class="hide-t" style="text-align:right">Total</th>
                    <th class="hide-t" style="text-align:right">Paid</th>
                    <th style="text-align:right">Balance</th>
                    <th style="width:90px;text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($invoices)): ?>
                <tr><td colspan="9">
                    <div class="empty-big">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                        <p>
                            <?php if ($search !== ''): ?>
                                No invoices match "<strong><?= Utils::e(htmlspecialchars_decode($search)) ?></strong>".
                            <?php elseif ($filterSt !== ''): ?>
                                No <?= Utils::e(INVOICE_STATUS[$filterSt] ?? $filterSt) ?> invoices.
                            <?php else: ?>
                                No invoices yet. Create one from a completed repair.
                            <?php endif; ?>
                        </p>
                        <a href="<?= BASE_URL ?>/repairs?status=completed" class="btn btn-primary">View Completed Repairs</a>
                    </div>
                </td></tr>
            <?php else: ?>
                <?php foreach ($invoices as $inv):
                    $total   = (float)($inv['total_amount'] ?? 0);
                    $paid    = (float)($inv['amount_paid']  ?? 0);
                    $balance = round($total - $paid, 2);
                    $isOverdue = $inv['status'] === 'overdue'
                        || ($inv['status'] === 'sent' && !empty($inv['due_date']) && strtotime($inv['due_date']) < time());
                ?>
                <tr>
                    <td>
                        <a href="<?= BASE_URL ?>/invoices/<?= $inv['invoice_id'] ?>" class="inv-num-link">
                            <?= Utils::e($inv['invoice_number']) ?>
                        </a>
                    </td>
                    <td>
                        <?php if (!empty($inv['customer_id'])): ?>
                        <a href="<?= BASE_URL ?>/customers/<?= $inv['customer_id'] ?>" class="cust-link">
                            <?= Utils::e($inv['customer_name'] ?? '—') ?>
                        </a>
                        <?php else: ?>
                        <span style="color:var(--text-secondary)"><?= Utils::e($inv['customer_name'] ?? '—') ?></span>
                        <?php endif; ?>
                        <?php if (!empty($inv['repair_id'])): ?>
                        <div style="font-size:.72rem;color:var(--text-muted)">
                            Repair <a href="<?= BASE_URL ?>/repairs/<?= $inv['repair_id'] ?>" style="color:var(--text-muted)">
                                #<?= $inv['repair_id'] ?>
                            </a>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="hide-mobile" style="font-size:.82rem;color:var(--text-secondary);white-space:nowrap">
                        <?= Utils::formatDate($inv['invoice_date']) ?>
                    </td>
                    <td class="hide-mobile" style="font-size:.82rem;white-space:nowrap">
                        <?php if (!empty($inv['due_date'])): ?>
                        <span class="<?= $isOverdue ? 'overdue' : '' ?>">
                            <?= Utils::formatDate($inv['due_date']) ?>
                        </span>
                        <?php else: ?>
                        <span style="color:var(--text-muted)">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= INVOICE_STATUS_CLASS[$inv['status']] ?? 'badge-gray' ?>">
                            <?= Utils::e(INVOICE_STATUS[$inv['status']] ?? $inv['status']) ?>
                        </span>
                    </td>
                    <td class="hide-t" style="text-align:right;font-size:.83rem"><?= Utils::formatCurrency($total) ?></td>
                    <td class="hide-t" style="text-align:right;font-size:.83rem;color:var(--success)"><?= $paid > 0 ? Utils::formatCurrency($paid) : '<span style="color:var(--text-muted)">—</span>' ?></td>
                    <td style="text-align:right;font-size:.83rem;font-weight:<?= $balance > 0 ? '600' : '400' ?>;color:<?= $balance > 0 ? 'var(--error)' : 'var(--success)' ?>">
                        <?= Utils::formatCurrency($balance) ?>
                    </td>
                    <td>
                        <div class="act-btns">
                            <a href="<?= BASE_URL ?>/invoices/<?= $inv['invoice_id'] ?>" class="act-btn" title="View invoice">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            <a href="<?= BASE_URL ?>/invoices/<?= $inv['invoice_id'] ?>/print" target="_blank" class="act-btn act-btn-p" title="Print invoice">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                            </a>
                            <?php if (Auth::can('manager')): ?>
                            <form method="POST" action="<?= BASE_URL ?>/invoices/<?= $inv['invoice_id'] ?>/delete"
                                  class="il-form" data-confirm="Delete invoice <?= Utils::e(addslashes($inv['invoice_number'])) ?>?">
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
        $bpP = array_filter(['search' => htmlspecialchars_decode($search), 'status' => $filterSt, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'customer_id' => $custFilter ?: null]);
    ?>
    <div class="tbl-footer">
        <span class="tbl-footer-info">
            <?= number_format($pg['offset'] + 1) ?>–<?= number_format(min($pg['offset'] + $pg['perPage'], $pg['total'])) ?>
            of <?= number_format($pg['total']) ?>
        </span>
        <nav class="pagination" aria-label="Pagination">
            <a href="<?= $pg['hasPrev'] ? Utils::url('/invoices', array_merge($bpP, ['page' => $pg['page'] - 1])) : '#' ?>"
               class="page-link <?= !$pg['hasPrev'] ? 'disabled' : '' ?>">&laquo;</a>
            <?php
            $s2 = max(1, $pg['page'] - 2);
            $e2 = min($pg['totalPages'], $pg['page'] + 2);
            if ($s2 > 1) echo '<span class="page-link disabled">…</span>';
            for ($p = $s2; $p <= $e2; $p++): ?>
            <a href="<?= Utils::url('/invoices', array_merge($bpP, ['page' => $p])) ?>"
               class="page-link <?= $p === $pg['page'] ? 'current' : '' ?>"><?= $p ?></a>
            <?php endfor;
            if ($e2 < $pg['totalPages']) echo '<span class="page-link disabled">…</span>';
            ?>
            <a href="<?= $pg['hasNext'] ? Utils::url('/invoices', array_merge($bpP, ['page' => $pg['page'] + 1])) : '#' ?>"
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
