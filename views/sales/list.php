<?php
$pageTitle = 'Sales';
require VIEWS_PATH . '/layouts/header.php';

$search   = Utils::e($_GET['search'] ?? '');
$filterSt = $_GET['status']    ?? '';
$dateFrom = Utils::e($_GET['date_from'] ?? '');
$dateTo   = Utils::e($_GET['date_to']   ?? '');
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
.sale-num-link{font-weight:700;color:var(--text-primary);text-decoration:none;font-family:var(--font-mono)}
.sale-num-link:hover{color:var(--accent)}
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
.hide-mobile{display:none}
@media(min-width:700px){.hide-mobile{display:table-cell}}
</style>

<!-- Page header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Sales</h1>
        <p class="page-subtitle"><?= number_format($pg['total']) ?> total sales</p>
    </div>
    <a href="<?= BASE_URL ?>/sales/create" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px" aria-hidden="true">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>New Sale
    </a>
</div>

<!-- Monthly stats -->
<div class="stats-row">
    <div class="stat-mini">
        <div class="stat-mini-val"><?= Utils::formatCurrency($monthStats['today_revenue'] ?? 0) ?></div>
        <div class="stat-mini-lbl">Sales Today</div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-val"><?= Utils::formatCurrency($monthStats['total_revenue'] ?? 0) ?></div>
        <div class="stat-mini-lbl">Sales This Month</div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-val" style="color:var(--success)"><?= Utils::formatCurrency($monthStats['total_paid'] ?? 0) ?></div>
        <div class="stat-mini-lbl">Collected This Month</div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-val"><?= (int)($monthStats['sale_count'] ?? 0) ?></div>
        <div class="stat-mini-lbl">Sales Count (Month)</div>
    </div>
</div>

<div class="card" style="margin-bottom:0">

    <!-- Status filters -->
    <div class="status-filters">
        <?php $bp = array_filter(['search' => htmlspecialchars_decode($search), 'date_from' => $dateFrom, 'date_to' => $dateTo]); ?>
        <a href="<?= Utils::url('/sales', $bp) ?>" class="sf-btn <?= $filterSt === '' ? 'active' : '' ?>">All</a>
        <?php foreach (SALE_STATUS as $key => $label): ?>
        <a href="<?= Utils::url('/sales', array_merge($bp, ['status' => $key, 'page' => 1])) ?>"
           class="sf-btn <?= $filterSt === $key ? 'active' : '' ?>">
            <?= Utils::e($label) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Search & date filter -->
    <form method="GET" action="<?= BASE_URL ?>/sales" class="filter-bar" role="search">
        <div class="search-input-wrap">
            <svg class="search-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="search" name="search" class="form-input"
                   placeholder="Sale number or customer name…"
                   value="<?= $search ?>" autocomplete="off">
        </div>
        <input type="date" name="date_from" class="form-input" style="width:auto" value="<?= $dateFrom ?>" title="From date">
        <input type="date" name="date_to"   class="form-input" style="width:auto" value="<?= $dateTo ?>"   title="To date">
        <input type="hidden" name="status" value="<?= Utils::e($filterSt) ?>">
        <button type="submit" class="btn btn-primary">Filter</button>
        <?php if ($search !== '' || $filterSt !== '' || $dateFrom !== '' || $dateTo !== ''): ?>
        <a href="<?= BASE_URL ?>/sales" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>

    <!-- Table -->
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:130px">Sale #</th>
                    <th>Customer</th>
                    <th class="hide-mobile">Date</th>
                    <th class="hide-mobile" style="text-align:center">Items</th>
                    <th>Status</th>
                    <th style="text-align:right">Total</th>
                    <th style="width:90px;text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($sales)): ?>
                <tr><td colspan="7">
                    <div class="empty-big">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                        </svg>
                        <p>
                            <?php if ($search !== ''): ?>
                                No sales match "<strong><?= Utils::e(htmlspecialchars_decode($search)) ?></strong>".
                            <?php else: ?>
                                No sales recorded yet.
                            <?php endif; ?>
                        </p>
                        <a href="<?= BASE_URL ?>/sales/create" class="btn btn-primary">Record First Sale</a>
                    </div>
                </td></tr>
            <?php else: ?>
                <?php foreach ($sales as $s):
                    $custLabel = $s['linked_customer_name'] ?: ($s['customer_name'] ?: 'Walk-in');
                ?>
                <tr>
                    <td>
                        <a href="<?= BASE_URL ?>/sales/<?= $s['sale_id'] ?>" class="sale-num-link">
                            <?= Utils::e($s['sale_number']) ?>
                        </a>
                    </td>
                    <td>
                        <?php if (!empty($s['customer_id'])): ?>
                        <a href="<?= BASE_URL ?>/customers/<?= $s['customer_id'] ?>" style="color:var(--text-primary);text-decoration:none">
                            <?= Utils::e($custLabel) ?>
                        </a>
                        <?php else: ?>
                        <span style="color:var(--text-secondary)"><?= Utils::e($custLabel) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="hide-mobile" style="font-size:.82rem;color:var(--text-secondary);white-space:nowrap">
                        <?= Utils::formatDate($s['sale_date']) ?>
                    </td>
                    <td class="hide-mobile" style="text-align:center;color:var(--text-secondary)"><?= (int)$s['item_count'] ?></td>
                    <td>
                        <span class="badge <?= SALE_STATUS_CLASS[$s['status']] ?? 'badge-gray' ?>">
                            <?= Utils::e(SALE_STATUS[$s['status']] ?? $s['status']) ?>
                        </span>
                    </td>
                    <td style="text-align:right;font-size:.85rem;font-weight:600"><?= Utils::formatCurrency($s['total_amount']) ?></td>
                    <td>
                        <div class="act-btns">
                            <a href="<?= BASE_URL ?>/sales/<?= $s['sale_id'] ?>" class="act-btn" title="View sale">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            <a href="<?= BASE_URL ?>/sales/<?= $s['sale_id'] ?>/print" target="_blank" class="act-btn act-btn-p" title="Print receipt">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                            </a>
                            <?php if (Auth::can('manager')): ?>
                            <form method="POST" action="<?= BASE_URL ?>/sales/<?= $s['sale_id'] ?>/delete"
                                  class="il-form" data-confirm="Delete sale <?= Utils::e(addslashes($s['sale_number'])) ?>? Sold units return to stock.">
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
        $bpP = array_filter(['search' => htmlspecialchars_decode($search), 'status' => $filterSt, 'date_from' => $dateFrom, 'date_to' => $dateTo]);
    ?>
    <div class="tbl-footer">
        <span class="tbl-footer-info">
            <?= number_format($pg['offset'] + 1) ?>–<?= number_format(min($pg['offset'] + $pg['perPage'], $pg['total'])) ?>
            of <?= number_format($pg['total']) ?>
        </span>
        <nav class="pagination" aria-label="Pagination">
            <a href="<?= $pg['hasPrev'] ? Utils::url('/sales', array_merge($bpP, ['page' => $pg['page'] - 1])) : '#' ?>"
               class="page-link <?= !$pg['hasPrev'] ? 'disabled' : '' ?>">&laquo;</a>
            <?php
            $s2 = max(1, $pg['page'] - 2);
            $e2 = min($pg['totalPages'], $pg['page'] + 2);
            if ($s2 > 1) echo '<span class="page-link disabled">…</span>';
            for ($p = $s2; $p <= $e2; $p++): ?>
            <a href="<?= Utils::url('/sales', array_merge($bpP, ['page' => $p])) ?>"
               class="page-link <?= $p === $pg['page'] ? 'current' : '' ?>"><?= $p ?></a>
            <?php endfor;
            if ($e2 < $pg['totalPages']) echo '<span class="page-link disabled">…</span>';
            ?>
            <a href="<?= $pg['hasNext'] ? Utils::url('/sales', array_merge($bpP, ['page' => $pg['page'] + 1])) : '#' ?>"
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
