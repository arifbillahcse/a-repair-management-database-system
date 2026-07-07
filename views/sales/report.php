<?php
$pageTitle = 'Sales Report';
require VIEWS_PATH . '/layouts/header.php';

$range  = $_GET['range']  ?? 'this_month';
$status = $_GET['status'] ?? '';
$df     = Utils::e($_GET['date_from'] ?? '');
$dt     = Utils::e($_GET['date_to']   ?? '');
?>
<style>
.rep-toolbar{display:flex;gap:.5rem;flex-wrap:wrap;align-items:flex-end;padding:1rem;border-bottom:1px solid var(--border)}
.rep-toolbar .form-group{margin-bottom:0}
.rep-toolbar label{display:block;font-size:.72rem;color:var(--text-secondary);margin-bottom:.25rem}
.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:1.5rem}
.stat-mini{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);padding:.85rem 1.1rem}
.stat-mini-val{font-size:1.25rem;font-weight:700;line-height:1;margin-bottom:.25rem}
.stat-mini-lbl{font-size:.75rem;color:var(--text-secondary)}
.sale-num-link{font-weight:700;color:var(--text-primary);text-decoration:none;font-family:var(--font-mono)}
.sale-num-link:hover{color:var(--accent)}
.rep-table{width:100%;border-collapse:collapse;font-size:.85rem}
.rep-table th{padding:.55rem .8rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);border-bottom:1px solid var(--border);text-align:left;white-space:nowrap}
.rep-table td{padding:.55rem .8rem;border-bottom:1px solid var(--border);vertical-align:middle}
.rep-table tbody tr:hover{background:var(--bg-tertiary)}
.rep-table tfoot td{padding:.7rem .8rem;border-top:2px solid var(--border);font-weight:700}
.empty-big{display:flex;flex-direction:column;align-items:center;gap:.75rem;padding:3rem 1rem;text-align:center}
.empty-big svg{width:48px;height:48px;stroke:var(--text-muted)}
.empty-big p{color:var(--text-secondary)}
.hide-mobile{display:none}
@media(min-width:700px){.hide-mobile{display:table-cell}}
@media print{
    .topbar,.sidebar,.sidebar-overlay,.rep-toolbar,.no-print,.page-header .btn{display:none!important}
    .main-content{margin:0!important;padding:0!important}
    .layout-wrapper{display:block!important}
    body{background:#fff}
}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Sales Report</h1>
        <p class="page-subtitle"><?= Utils::e($rangeLabel) ?><?= $status !== '' ? ' · ' . Utils::e(SALE_STATUS[$status] ?? $status) : '' ?></p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <button type="button" onclick="window.print()" class="btn btn-secondary no-print">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px" aria-hidden="true">
                <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>
            </svg>Print
        </button>
        <a href="<?= BASE_URL ?>/sales" class="btn btn-secondary no-print">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
            </svg>Back to Sales
        </a>
    </div>
</div>

<!-- Summary KPIs -->
<div class="stats-row">
    <div class="stat-mini">
        <div class="stat-mini-val"><?= (int)($summary['sale_count'] ?? 0) ?></div>
        <div class="stat-mini-lbl">Sales</div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-val"><?= Utils::formatCurrency($summary['total_amount'] ?? 0) ?></div>
        <div class="stat-mini-lbl">Total Revenue</div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-val" style="color:var(--success)"><?= Utils::formatCurrency($summary['total_paid'] ?? 0) ?></div>
        <div class="stat-mini-lbl">Collected</div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-val" style="color:<?= ($summary['balance'] ?? 0) > 0 ? 'var(--warning)' : 'var(--text-primary)' ?>">
            <?= Utils::formatCurrency($summary['balance'] ?? 0) ?>
        </div>
        <div class="stat-mini-lbl">Outstanding</div>
    </div>
    <div class="stat-mini">
        <div class="stat-mini-val"><?= Utils::formatCurrency($summary['tax_amount'] ?? 0) ?></div>
        <div class="stat-mini-lbl">Tax Collected</div>
    </div>
</div>

<div class="card" style="margin-bottom:0">

    <!-- Filter toolbar -->
    <form method="GET" action="<?= BASE_URL ?>/sales/report" class="rep-toolbar no-print">
        <div class="form-group">
            <label for="range">Period</label>
            <select name="range" id="range" class="form-input" style="width:auto"
                    onchange="document.getElementById('customDates').style.display = this.value === 'custom' ? 'flex' : 'none'">
                <?php foreach ([
                    'this_month' => 'This Month',
                    'this_year'  => 'This Year',
                    'last_3m'    => 'Last 3 Months',
                    'last_6m'    => 'Last 6 Months',
                    'last_12m'   => 'Last 12 Months',
                    'custom'     => 'Custom Range',
                ] as $key => $label): ?>
                <option value="<?= $key ?>" <?= $range === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div id="customDates" class="form-group" style="display:<?= $range === 'custom' ? 'flex' : 'none' ?>;gap:.5rem;align-items:flex-end">
            <div>
                <label for="date_from">From</label>
                <input type="date" name="date_from" id="date_from" class="form-input" value="<?= $df ?>">
            </div>
            <div>
                <label for="date_to">To</label>
                <input type="date" name="date_to" id="date_to" class="form-input" value="<?= $dt ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-input" style="width:auto">
                <option value="">All (excl. cancelled)</option>
                <?php foreach (SALE_STATUS as $key => $label): ?>
                <option value="<?= $key ?>" <?= $status === $key ? 'selected' : '' ?>><?= Utils::e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Apply</button>
    </form>

    <!-- Report table -->
    <div class="table-responsive">
        <table class="rep-table">
            <thead>
                <tr>
                    <th>Sale #</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th class="hide-mobile" style="text-align:center">Items</th>
                    <th>Status</th>
                    <th style="text-align:right">Total</th>
                    <th class="hide-mobile" style="text-align:right">Paid</th>
                    <th style="text-align:right">Balance</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($sales)): ?>
                <tr><td colspan="8">
                    <div class="empty-big">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                        </svg>
                        <p>No sales in this period.</p>
                    </div>
                </td></tr>
            <?php else: ?>
                <?php foreach ($sales as $s):
                    $total   = (float)$s['total_amount'];
                    $paid    = (float)$s['amount_paid'];
                    $balance = round($total - $paid, 2);
                    $custLabel = $s['linked_customer_name'] ?: ($s['customer_name'] ?: 'Walk-in');
                ?>
                <tr>
                    <td>
                        <a href="<?= BASE_URL ?>/sales/<?= (int)$s['sale_id'] ?>" class="sale-num-link">
                            <?= Utils::e($s['sale_number']) ?>
                        </a>
                    </td>
                    <td style="white-space:nowrap;color:var(--text-secondary)"><?= Utils::formatDate($s['sale_date']) ?></td>
                    <td><?= Utils::e($custLabel) ?></td>
                    <td class="hide-mobile" style="text-align:center;color:var(--text-secondary)"><?= (int)$s['item_count'] ?></td>
                    <td>
                        <span class="badge <?= SALE_STATUS_CLASS[$s['status']] ?? 'badge-gray' ?>">
                            <?= Utils::e(SALE_STATUS[$s['status']] ?? $s['status']) ?>
                        </span>
                    </td>
                    <td style="text-align:right;font-weight:600"><?= Utils::formatCurrency($total) ?></td>
                    <td class="hide-mobile" style="text-align:right;color:var(--success)"><?= Utils::formatCurrency($paid) ?></td>
                    <td style="text-align:right;color:<?= $balance > 0 ? 'var(--error)' : 'var(--text-secondary)' ?>">
                        <?= Utils::formatCurrency($balance) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
            <?php if (!empty($sales)): ?>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align:right">Totals (<?= (int)($summary['sale_count'] ?? 0) ?> sales)</td>
                    <td style="text-align:right"><?= Utils::formatCurrency($summary['total_amount'] ?? 0) ?></td>
                    <td class="hide-mobile" style="text-align:right;color:var(--success)"><?= Utils::formatCurrency($summary['total_paid'] ?? 0) ?></td>
                    <td style="text-align:right;color:<?= ($summary['balance'] ?? 0) > 0 ? 'var(--error)' : 'var(--text-secondary)' ?>">
                        <?= Utils::formatCurrency($summary['balance'] ?? 0) ?>
                    </td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
