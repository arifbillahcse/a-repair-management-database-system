<?php
$pageTitle  = 'Reports';
$loadCharts = true;
require VIEWS_PATH . '/layouts/header.php';

$rs   = $repairStats  ?? [];
$ms   = $monthStats   ?? [];
$ytd  = $ytd          ?? [];
$avg  = $avgTime      ?? [];
?>
<style>
.rep-grid-4{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem}
.rep-grid-2{display:grid;grid-template-columns:1fr;gap:1.5rem;margin-bottom:1.5rem}
@media(min-width:900px){.rep-grid-2{grid-template-columns:1fr 1fr}}
.rep-grid-3{display:grid;grid-template-columns:1fr;gap:1.5rem;margin-bottom:1.5rem}
@media(min-width:900px){.rep-grid-3{grid-template-columns:2fr 1fr}}

.stat-card2{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.1rem 1.25rem;display:flex;flex-direction:column;gap:.3rem}
.stat-card2-val{font-size:1.5rem;font-weight:700;line-height:1}
.stat-card2-lbl{font-size:.75rem;color:var(--text-secondary)}
.stat-card2-sub{font-size:.72rem;color:var(--text-muted)}

.chart-card{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden}
.chart-header{display:flex;align-items:center;justify-content:space-between;padding:.75rem 1.25rem;border-bottom:1px solid var(--border)}
.chart-title{font-size:.9rem;font-weight:600;margin:0}
.chart-body{padding:1.25rem;position:relative}

.top-cust-table{width:100%;border-collapse:collapse}
.top-cust-table th{padding:.5rem .9rem;font-size:.72rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid var(--border);text-align:left}
.top-cust-table td{padding:.6rem .9rem;font-size:.84rem;border-bottom:1px solid var(--border);vertical-align:top}
.top-cust-table tr:last-child td{border-bottom:none}
.rank-num{font-size:.75rem;color:var(--text-muted);font-weight:700;width:24px;text-align:center}
.bar-track{height:6px;background:var(--bg-tertiary);border-radius:3px;margin-top:4px;overflow:hidden}
.bar-fill{height:100%;background:var(--accent);border-radius:3px;transition:width .4s ease}

.overdue-table{width:100%;border-collapse:collapse}
.overdue-table th{padding:.5rem .9rem;font-size:.72rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid var(--border);text-align:left}
.overdue-table td{padding:.55rem .9rem;font-size:.83rem;border-bottom:1px solid var(--border)}
.overdue-table tr:last-child td{border-bottom:none}

.status-donut-wrap{display:flex;flex-direction:column;gap:.5rem;padding:1.25rem}
.status-row{display:flex;align-items:center;justify-content:space-between;gap:.75rem}
.status-bar-label{font-size:.82rem;min-width:140px}
.status-track{flex:1;height:8px;background:var(--bg-tertiary);border-radius:4px;overflow:hidden}
.status-fill{height:100%;border-radius:4px}
.status-count{font-size:.78rem;color:var(--text-muted);min-width:30px;text-align:right}

.staff-perf-list{list-style:none;padding:0;margin:0}
.staff-perf-item{display:flex;align-items:center;gap:.75rem;padding:.65rem 1.25rem;border-bottom:1px solid var(--border)}
.staff-perf-item:last-child{border-bottom:none}
.staff-avatar{width:34px;height:34px;border-radius:50%;background:var(--accent-dim);color:var(--accent);font-size:.85rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.staff-name{font-size:.86rem;font-weight:600}
.staff-sub{font-size:.74rem;color:var(--text-secondary)}
.staff-count{font-size:1.1rem;font-weight:700;color:var(--accent);margin-left:auto}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Reports</h1>
        <p class="page-subtitle">Overview for <?= date('F Y') ?></p>
    </div>
</div>

<!-- ── KPI summary cards ─────────────────────────────────────────────────── -->
<div class="rep-grid-4">

    <div class="stat-card2">
        <div class="stat-card2-val"><?= (int)($rs['total'] ?? 0) ?></div>
        <div class="stat-card2-lbl">Total Repairs</div>
        <div class="stat-card2-sub"><?= (int)($rs['this_month'] ?? 0) ?> this month</div>
    </div>

    <div class="stat-card2">
        <div class="stat-card2-val" style="color:var(--success)"><?= (int)($rs['completed'] ?? 0) ?></div>
        <div class="stat-card2-lbl">Completed</div>
        <div class="stat-card2-sub">
            <?php
            $tot = (int)($rs['total'] ?? 0);
            $cmp = (int)($rs['completed'] ?? 0);
            echo $tot > 0 ? round($cmp / $tot * 100) . '% completion rate' : '—';
            ?>
        </div>
    </div>

    <div class="stat-card2">
        <div class="stat-card2-val" style="color:var(--info,#67b3dd)"><?= (int)($rs['in_progress'] ?? 0) ?></div>
        <div class="stat-card2-lbl">Active Repairs</div>
        <div class="stat-card2-sub">
            <?= (int)($rs['on_hold'] ?? 0) ?> on hold ·
            <?= (int)($rs['waiting_for_parts'] ?? 0) ?> waiting parts
        </div>
    </div>

    <div class="stat-card2">
        <div class="stat-card2-val"><?= $avg['avg_days'] ? $avg['avg_days'] . 'd' : '—' ?></div>
        <div class="stat-card2-lbl">Avg. Repair Time</div>
        <div class="stat-card2-sub">Last 90 days · min <?= $avg['min_days'] ?? '?' ?>d / max <?= $avg['max_days'] ?? '?' ?>d</div>
    </div>

    <div class="stat-card2">
        <div class="stat-card2-val"><?= Utils::formatCurrency($ytd['revenue'] ?? 0) ?></div>
        <div class="stat-card2-lbl">YTD Revenue</div>
        <div class="stat-card2-sub"><?= (int)($ytd['count'] ?? 0) ?> invoices issued</div>
    </div>

    <div class="stat-card2">
        <div class="stat-card2-val" style="color:var(--success)"><?= Utils::formatCurrency($ytd['paid'] ?? 0) ?></div>
        <div class="stat-card2-lbl">YTD Collected</div>
        <?php $ytdBal = ($ytd['revenue'] ?? 0) - ($ytd['paid'] ?? 0); ?>
        <div class="stat-card2-sub" style="color:<?= $ytdBal > 0 ? 'var(--warning)' : 'var(--text-muted)' ?>">
            <?= Utils::formatCurrency($ytdBal) ?> outstanding
        </div>
    </div>

    <div class="stat-card2">
        <div class="stat-card2-val"><?= Utils::formatCurrency($ms['total_revenue'] ?? 0) ?></div>
        <div class="stat-card2-lbl">This Month Revenue</div>
        <div class="stat-card2-sub"><?= Utils::formatCurrency($ms['total_paid'] ?? 0) ?> collected</div>
    </div>

    <div class="stat-card2">
        <div class="stat-card2-val" style="color:var(--error)"><?= count($overdueInvoices ?? []) ?></div>
        <div class="stat-card2-lbl">Overdue Invoices</div>
        <?php
        $overdueTotal = array_sum(array_map(fn($o) => (float)$o['total_amount'] - (float)$o['amount_paid'], $overdueInvoices ?? []));
        ?>
        <div class="stat-card2-sub"><?= Utils::formatCurrency($overdueTotal) ?> outstanding</div>
    </div>

</div>

<!-- ── Revenue chart + status breakdown ────────────────────────────────── -->
<div class="rep-grid-3">

    <!-- Revenue chart -->
    <div class="chart-card">
        <div class="chart-header">
            <h2 class="chart-title">Revenue — Last 12 Months</h2>
        </div>
        <div class="chart-body" style="height:240px">
            <canvas id="revenueChart" aria-label="Monthly revenue chart" role="img"></canvas>
        </div>
    </div>

    <!-- Status breakdown -->
    <div class="chart-card">
        <div class="chart-header">
            <h2 class="chart-title">Repairs by Status</h2>
        </div>
        <?php $totalRep = max(1, array_sum($statusCounts ?? [])); ?>
        <div class="status-donut-wrap">
            <?php foreach (REPAIR_STATUS as $key => $label):
                $cnt = (int)($statusCounts[$key] ?? 0);
                if (!$cnt) continue;
                $pct  = round($cnt / $totalRep * 100);
                $cls  = REPAIR_STATUS_CLASS[$key] ?? 'badge-gray';
                // Map badge class to a fill color
                $colors = [
                    'badge-gray'   => '#6b7280',
                    'badge-red'    => '#ef4444',
                    'badge-orange' => '#f59e0b',
                    'badge-yellow' => '#eab308',
                    'badge-blue'   => '#3b82f6',
                    'badge-green'  => '#10b981',
                    'badge-teal'   => '#14b8a6',
                    'badge-purple' => '#a855f7',
                ];
                $fillColor = $colors[$cls] ?? '#6b7280';
            ?>
            <div class="status-row">
                <span class="status-bar-label">
                    <span class="badge <?= $cls ?>" style="margin-right:.4rem"><?= Utils::e($label) ?></span>
                </span>
                <div class="status-track">
                    <div class="status-fill" style="width:<?= $pct ?>%;background:<?= $fillColor ?>"></div>
                </div>
                <span class="status-count"><?= $cnt ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<!-- ── Repairs by month chart ───────────────────────────────────────────── -->
<div class="rep-grid-2" style="margin-bottom:1.5rem">

    <!-- Repairs per month chart -->
    <div class="chart-card">
        <div class="chart-header">
            <h2 class="chart-title">Repairs Received — Last 12 Months</h2>
        </div>
        <div class="chart-body" style="height:220px">
            <canvas id="repairsChart" aria-label="Monthly repairs chart" role="img"></canvas>
        </div>
    </div>

    <!-- Staff performance -->
    <?php if (!empty($staffStats)): ?>
    <div class="chart-card">
        <div class="chart-header">
            <h2 class="chart-title">Technician Performance</h2>
        </div>
        <ul class="staff-perf-list">
            <?php foreach (array_slice($staffStats, 0, 8) as $s): ?>
            <li class="staff-perf-item">
                <div class="staff-avatar"><?= strtoupper(substr($s['full_name'], 0, 1)) ?></div>
                <div>
                    <div class="staff-name"><?= Utils::e($s['full_name']) ?></div>
                    <div class="staff-sub">
                        <?= (int)$s['in_progress'] ?> active &nbsp;·&nbsp;
                        <?= (int)$s['completed'] ?> completed
                    </div>
                </div>
                <div class="staff-count"><?= (int)$s['total_repairs'] ?></div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

</div>

<!-- ── Top clients ───────────────────────────────────────────────────────── -->
<?php if (!empty($topCustomers)): ?>
<div class="chart-card" style="margin-bottom:1.5rem">
    <div class="chart-header">
        <h2 class="chart-title">Top Clients by Revenue</h2>
        <a href="<?= BASE_URL ?>/customers" class="btn btn-xs btn-secondary">All Clients</a>
    </div>
    <?php $maxBilled = max(array_column($topCustomers, 'total_billed')); ?>
    <div class="table-responsive">
        <table class="top-cust-table">
            <thead>
                <tr>
                    <th style="width:32px">#</th>
                    <th>Client</th>
                    <th style="width:80px;text-align:center">Repairs</th>
                    <th style="width:130px;text-align:right">Billed</th>
                    <th style="width:130px;text-align:right">Collected</th>
                    <th style="width:120px;text-align:right">Balance</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($topCustomers as $i => $c):
                $billed  = (float)$c['total_billed'];
                $paid    = (float)$c['total_paid'];
                $bal     = round($billed - $paid, 2);
                $barW    = $maxBilled > 0 ? round($billed / $maxBilled * 100) : 0;
            ?>
            <tr>
                <td class="rank-num"><?= $i + 1 ?></td>
                <td>
                    <a href="<?= BASE_URL ?>/customers/<?= $c['customer_id'] ?>"
                       style="font-weight:600;color:var(--text-primary);text-decoration:none"
                       onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-primary)'">
                        <?= Utils::e($c['full_name']) ?>
                    </a>
                    <div class="bar-track"><div class="bar-fill" style="width:<?= $barW ?>%"></div></div>
                </td>
                <td style="text-align:center;color:var(--text-secondary)"><?= (int)$c['total_repairs'] ?></td>
                <td style="text-align:right;font-weight:500"><?= Utils::formatCurrency($billed) ?></td>
                <td style="text-align:right;color:var(--success)"><?= Utils::formatCurrency($paid) ?></td>
                <td style="text-align:right;color:<?= $bal > 0 ? 'var(--error)' : 'var(--success)' ?>;font-weight:<?= $bal > 0 ? '600' : '400' ?>">
                    <?= Utils::formatCurrency($bal) ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ── Overdue invoices ──────────────────────────────────────────────────── -->
<?php if (!empty($overdueInvoices)): ?>
<div class="chart-card" style="margin-bottom:1.5rem;border-color:var(--error-bg)">
    <div class="chart-header" style="background:var(--error-bg)">
        <h2 class="chart-title" style="color:var(--error)">Overdue Invoices</h2>
        <span class="badge badge-red"><?= count($overdueInvoices) ?></span>
    </div>
    <div class="table-responsive">
        <table class="overdue-table">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Client</th>
                    <th>Due Date</th>
                    <th style="text-align:right">Days Over</th>
                    <th style="text-align:right">Balance</th>
                    <th style="width:80px;text-align:right">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($overdueInvoices as $ov):
                $bal = (float)$ov['total_amount'] - (float)$ov['amount_paid'];
            ?>
            <tr>
                <td>
                    <a href="<?= BASE_URL ?>/invoices/<?= $ov['invoice_id'] ?>"
                       style="font-weight:700;color:var(--text-primary);text-decoration:none;font-family:var(--font-mono)"
                       onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-primary)'">
                        <?= Utils::e($ov['invoice_number']) ?>
                    </a>
                </td>
                <td><?= Utils::e($ov['customer_name']) ?></td>
                <td style="color:var(--error)"><?= Utils::formatDate($ov['due_date']) ?></td>
                <td style="text-align:right;color:var(--error);font-weight:600"><?= (int)$ov['days_overdue'] ?>d</td>
                <td style="text-align:right;font-weight:600;color:var(--error)"><?= Utils::formatCurrency($bal) ?></td>
                <td style="text-align:right">
                    <a href="<?= BASE_URL ?>/invoices/<?= $ov['invoice_id'] ?>" class="btn btn-xs btn-danger">View</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php
// ── Chart.js data ─────────────────────────────────────────────────────────
$revLabels   = json_encode(array_column($monthlyRev    ?? [], 'month'));
$revRevenue  = json_encode(array_map('floatval', array_column($monthlyRev ?? [], 'revenue')));
$revPaid     = json_encode(array_map('floatval', array_column($monthlyRev ?? [], 'paid')));
$repLabels   = json_encode(array_column($repairsByMonth ?? [], 'month'));
$repCounts   = json_encode(array_map('intval', array_column($repairsByMonth ?? [], 'count')));

$inlineJs = <<<JS
document.addEventListener('DOMContentLoaded', function () {
    if (!window.Chart) return;

    var gridColor  = '#2a2a2a';
    var tickColor  = '#a0a0a0';

    // Revenue chart
    var rCtx = document.getElementById('revenueChart');
    if (rCtx) {
        new Chart(rCtx, {
            type: 'line',
            data: {
                labels: {$revLabels},
                datasets: [
                    {
                        label: 'Revenue',
                        data: {$revRevenue},
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,.12)',
                        tension: .4, fill: true,
                        pointBackgroundColor: '#10b981'
                    },
                    {
                        label: 'Collected',
                        data: {$revPaid},
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59,130,246,.08)',
                        tension: .4, fill: false,
                        pointBackgroundColor: '#3b82f6'
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { labels: { color: tickColor } } },
                scales: {
                    x: { ticks: { color: tickColor }, grid: { color: gridColor } },
                    y: { ticks: { color: tickColor }, grid: { color: gridColor },
                         beginAtZero: true }
                }
            }
        });
    }

    // Repairs per month chart
    var mCtx = document.getElementById('repairsChart');
    if (mCtx) {
        new Chart(mCtx, {
            type: 'bar',
            data: {
                labels: {$repLabels},
                datasets: [{
                    label: 'Repairs',
                    data: {$repCounts},
                    backgroundColor: 'rgba(16,185,129,.65)',
                    borderColor: '#10b981',
                    borderWidth: 1,
                    borderRadius: 3
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: tickColor }, grid: { color: gridColor } },
                    y: { ticks: { color: tickColor }, grid: { color: gridColor },
                         beginAtZero: true, precision: 0 }
                }
            }
        });
    }
});
JS;
?>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
