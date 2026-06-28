<?php
$pageTitle  = 'Dashboard';
$loadCharts = true;
require VIEWS_PATH . '/layouts/header.php';

$stats        = $stats        ?? [];
$monthlyStats = $monthlyStats ?? [];

$inProgress    = (int)($stats['in_progress']      ?? 0);
$onHold        = (int)($stats['on_hold']           ?? 0);
$waitingParts  = (int)($stats['waiting_for_parts'] ?? 0);
$readyCount    = (int)($stats['ready_for_pickup']  ?? 0);
$thisMonth     = (int)($stats['this_month']        ?? 0);
$totalAll      = (int)($stats['total']             ?? 0);
$completed     = (int)($stats['completed']         ?? 0);

$mRevenue  = (float)($monthlyStats['total_revenue'] ?? 0);
$mPaid     = (float)($monthlyStats['total_paid']    ?? 0);
$mInvoices = (int)($monthlyStats['invoice_count']   ?? 0);

$revenueByType  = $revenueByType ?? [];
$indRevenue     = (float)($revenueByType['individual']['revenue'] ?? 0);
$indPaid        = (float)($revenueByType['individual']['paid']    ?? 0);
$indCount       = (int)($revenueByType['individual']['cnt']       ?? 0);
$colRevenue     = (float)($revenueByType['colleague']['revenue']  ?? 0);
$colPaid        = (float)($revenueByType['colleague']['paid']     ?? 0);
$colCount       = (int)($revenueByType['colleague']['cnt']        ?? 0);
?>
<style>
/* ── KPI grid ──────────────────────────────────────────── */
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(175px,1fr));gap:1rem;margin-bottom:1.5rem}

/* ── Chart card (re-use from reports) ─────────────────── */
.chart-card{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-sm)}
.chart-header{display:flex;align-items:center;justify-content:space-between;padding:.85rem 1.25rem;border-bottom:1px solid var(--border)}
.chart-title{font-size:.9rem;font-weight:600;margin:0}
.chart-body{padding:1.25rem}

/* ── Dashboard grid ───────────────────────────────────── */
.db-grid-main{display:grid;grid-template-columns:1fr;gap:1.25rem;margin-bottom:1.25rem}
@media(min-width:960px){.db-grid-main{grid-template-columns:2fr 1fr}}
.db-grid-bot{display:grid;grid-template-columns:1fr;gap:1.25rem;margin-bottom:1.25rem}
@media(min-width:960px){.db-grid-bot{grid-template-columns:1fr 1fr}}

/* ── Quick actions ────────────────────────────────────── */
.quick-actions{display:grid;grid-template-columns:1fr 1fr;gap:.55rem;padding:1.1rem 1.25rem}
.qa-btn{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.35rem;padding:.75rem .5rem;border-radius:var(--radius);border:1px solid var(--border);background:var(--bg-tertiary);color:var(--text-secondary);text-decoration:none;font-size:.75rem;font-weight:600;text-align:center;transition:all var(--transition);line-height:1.2}
.qa-btn:hover{border-color:var(--accent);color:var(--accent);background:var(--accent-dim);transform:translateY(-1px);box-shadow:var(--shadow-sm)}
.qa-btn svg{width:20px;height:20px;stroke:currentColor;fill:none;stroke-width:2}
.qa-btn.qa-primary{background:var(--accent);border-color:var(--accent);color:#fff}
.qa-btn.qa-primary:hover{opacity:.9;transform:translateY(-1px)}

/* ── Pickup / overdue list ────────────────────────────── */
.plist{list-style:none;padding:0;margin:0}
.plist-item{display:flex;align-items:center;gap:.7rem;padding:.6rem 1.25rem;border-bottom:1px solid var(--border)}
.plist-item:last-child{border-bottom:none}
.plist-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.plist-dot-green{background:var(--success)}
.plist-dot-red{background:var(--error)}
.plist-info{flex:1;min-width:0}
.plist-name{font-size:.84rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.plist-sub{font-size:.74rem;color:var(--text-muted)}

/* ── Staff list ───────────────────────────────────────── */
.staff-list{list-style:none;padding:0;margin:0}
.staff-item{display:flex;align-items:center;gap:.75rem;padding:.6rem 1.25rem;border-bottom:1px solid var(--border)}
.staff-item:last-child{border-bottom:none}
.staff-av{width:32px;height:32px;border-radius:50%;background:var(--accent-dim);color:var(--accent);font-size:.82rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.staff-name{font-size:.85rem;font-weight:600}
.staff-sub{font-size:.73rem;color:var(--text-secondary)}
.staff-num{font-size:1.05rem;font-weight:700;color:var(--accent);margin-left:auto}

/* ── Improved data-table for dashboard ───────────────── */
.db-table{width:100%;border-collapse:collapse;font-size:.84rem}
.db-table th{padding:.55rem 1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);border-bottom:1px solid var(--border);text-align:left;background:var(--bg-secondary);white-space:nowrap}
.db-table td{padding:.6rem 1rem;border-bottom:1px solid var(--border);vertical-align:middle}
.db-table tbody tr:last-child td{border-bottom:none}
.db-table tbody tr:hover{background:var(--bg-tertiary)}
</style>

<!-- ── Page header ──────────────────────────────────────────────────────────── -->
<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">
            <?= date('l, d F Y') ?> &nbsp;·&nbsp; <?= Utils::e(Auth::user()['full_name'] ?: Auth::user()['username']) ?>
        </p>
    </div>
</div>

<!-- ── KPI cards ────────────────────────────────────────────────────────────── -->
<div class="kpi-grid">

    <div class="stat-card">
        <div class="stat-icon stat-icon-blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="stat-value" style="color:var(--info, #3b82f6)"><?= $inProgress ?></div>
            <div class="stat-label">In Progress</div>
            <div style="font-size:.73rem;color:var(--text-muted);margin-top:.1rem"><?= $onHold ?> on hold · <?= $waitingParts ?> waiting parts</div>
        </div>
        <a href="<?= BASE_URL ?>/repairs?status=in_progress" class="stat-link" title="View in-progress">&#x2197;</a>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="stat-value" style="color:var(--success)"><?= $readyCount ?></div>
            <div class="stat-label">Ready for Pickup</div>
            <div style="font-size:.73rem;color:var(--text-muted);margin-top:.1rem">awaiting collection</div>
        </div>
        <a href="<?= BASE_URL ?>/repairs?status=ready_for_pickup" class="stat-link" title="View ready">&#x2197;</a>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="stat-value"><?= $thisMonth ?></div>
            <div class="stat-label">This Month</div>
            <div style="font-size:.73rem;color:var(--text-muted);margin-top:.1rem"><?= $totalAll ?> total all-time</div>
        </div>
        <a href="<?= BASE_URL ?>/repairs" class="stat-link" title="View all">&#x2197;</a>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="stat-value"><?= (int)($totalCustomers ?? 0) ?></div>
            <div class="stat-label">Total Clients</div>
            <div style="font-size:.73rem;color:var(--text-muted);margin-top:.1rem">registered in system</div>
        </div>
        <a href="<?= BASE_URL ?>/customers" class="stat-link" title="View clients">&#x2197;</a>
    </div>

    <?php if (Auth::can('manager')): ?>
    <div class="stat-card">
        <div class="stat-icon stat-icon-green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="stat-value" style="color:var(--success)"><?= Utils::formatCurrency($indRevenue) ?></div>
            <div class="stat-label">Individual Revenue</div>
            <div style="font-size:.73rem;color:var(--text-muted);margin-top:.1rem">
                <?= $indCount > 0
                    ? $indCount . ' invoice' . ($indCount !== 1 ? 's' : '') . ' · paid ' . Utils::formatCurrency($indPaid)
                    : 'no invoices this month' ?>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/customers?type=individual" class="stat-link" title="View individual clients">&#x2197;</a>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="stat-value" style="color:var(--accent)"><?= Utils::formatCurrency($colRevenue) ?></div>
            <div class="stat-label">Colleague Revenue</div>
            <div style="font-size:.73rem;color:var(--text-muted);margin-top:.1rem">
                <?= $colCount > 0
                    ? $colCount . ' invoice' . ($colCount !== 1 ? 's' : '') . ' · paid ' . Utils::formatCurrency($colPaid)
                    : 'no invoices this month' ?>
            </div>
        </div>
        <a href="<?= BASE_URL ?>/customers?type=colleague" class="stat-link" title="View colleague clients">&#x2197;</a>
    </div>
    <?php endif; ?>

</div>

<!-- ── Main grid: recent repairs + sidebar ──────────────────────────────────── -->
<div class="db-grid-main">

    <!-- Recent repairs table -->
    <div class="chart-card">
        <div class="chart-header">
            <h2 class="chart-title">Recent Repairs</h2>
            <a href="<?= BASE_URL ?>/repairs" class="btn btn-xs btn-secondary">View All</a>
        </div>
        <div class="table-responsive">
            <table class="db-table">
                <thead>
                    <tr>
                        <th style="width:56px">ID</th>
                        <th>Client</th>
                        <th>Device</th>
                        <th>Status</th>
                        <th style="width:90px">Date In</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($recentRepairs)): ?>
                    <tr><td colspan="5" class="empty-state" style="padding:2rem;text-align:center;color:var(--text-muted)">No repairs yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentRepairs as $r): ?>
                    <tr>
                        <td>
                            <a href="<?= BASE_URL ?>/repairs/<?= $r['repair_id'] ?>" class="table-link" style="font-weight:600">
                                #<?= $r['repair_id'] ?>
                            </a>
                        </td>
                        <td style="max-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= Utils::e($r['customer_name']) ?></td>
                        <td style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--text-secondary)"><?= Utils::e($r['device_model']) ?></td>
                        <td>
                            <span class="badge <?= REPAIR_STATUS_CLASS[$r['status']] ?? 'badge-gray' ?>">
                                <?= Utils::e(REPAIR_STATUS[$r['status']] ?? $r['status']) ?>
                            </span>
                        </td>
                        <td style="color:var(--text-muted);font-size:.8rem;white-space:nowrap"><?= Utils::formatDate($r['date_in']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right sidebar -->
    <div style="display:flex;flex-direction:column;gap:1.25rem">

        <!-- Quick actions -->
        <div class="chart-card">
            <div class="chart-header">
                <h2 class="chart-title">Quick Actions</h2>
            </div>
            <div class="quick-actions">
                <a href="<?= BASE_URL ?>/repairs/create" class="qa-btn qa-primary">
                    <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    New Repair
                </a>
                <a href="<?= BASE_URL ?>/customers/create" class="qa-btn">
                    <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    New Client
                </a>
                <a href="<?= BASE_URL ?>/repairs?status=in_progress" class="qa-btn">
                    <svg viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    In Progress
                </a>
                <a href="<?= BASE_URL ?>/invoices" class="qa-btn">
                    <svg viewBox="0 0 24 24"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/></svg>
                    Invoices
                </a>
                <a href="<?= BASE_URL ?>/customers" class="qa-btn">
                    <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    All Clients
                </a>
                <?php if (Auth::can('manager')): ?>
                <a href="<?= BASE_URL ?>/reports" class="qa-btn">
                    <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    Reports
                </a>
                <?php else: ?>
                <a href="<?= BASE_URL ?>/repairs?status=ready_for_pickup" class="qa-btn">
                    <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    Ready Pickup
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Ready for pickup -->
        <div class="chart-card">
            <div class="chart-header">
                <h2 class="chart-title">Ready for Pickup</h2>
                <span class="badge badge-green"><?= count($readyPickup ?? []) ?></span>
            </div>
            <?php if (empty($readyPickup)): ?>
                <div style="padding:1.5rem 1.25rem;text-align:center;color:var(--text-muted);font-size:.85rem">No devices waiting.</div>
            <?php else: ?>
            <ul class="plist">
                <?php foreach (array_slice($readyPickup ?? [], 0, 6) as $p): ?>
                <li class="plist-item">
                    <span class="plist-dot plist-dot-green"></span>
                    <div class="plist-info">
                        <div class="plist-name"><?= Utils::e($p['customer_name']) ?></div>
                        <div class="plist-sub"><?= Utils::e(Utils::truncate($p['device_model'], 35)) ?></div>
                    </div>
                    <a href="<?= BASE_URL ?>/repairs/<?= $p['repair_id'] ?>" class="btn btn-xs btn-secondary">View</a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>

        <!-- Overdue pickups -->
        <?php if (!empty($overdueItems)): ?>
        <div class="chart-card" style="border-color:var(--error)">
            <div class="chart-header" style="background:rgba(239,68,68,.06)">
                <h2 class="chart-title" style="color:var(--error)">Overdue Pickups</h2>
                <span class="badge badge-red"><?= count($overdueItems) ?></span>
            </div>
            <ul class="plist">
                <?php foreach ($overdueItems as $o): ?>
                <li class="plist-item">
                    <span class="plist-dot plist-dot-red"></span>
                    <div class="plist-info">
                        <div class="plist-name"><?= Utils::e($o['customer_name']) ?></div>
                        <div class="plist-sub" style="color:var(--error)"><?= (int)$o['days_waiting'] ?> days overdue</div>
                    </div>
                    <a href="<?= BASE_URL ?>/repairs/<?= $o['repair_id'] ?>" class="btn btn-xs btn-danger">View</a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

    </div>

</div>

<!-- ── Bottom grid: revenue chart + staff (manager only) ────────────────────── -->
<?php if (Auth::can('manager')): ?>
<div class="db-grid-bot">

    <!-- Revenue chart -->
    <div class="chart-card">
        <div class="chart-header">
            <h2 class="chart-title">Revenue &amp; Collections</h2>
            <span style="font-size:.76rem;color:var(--text-muted)">Last 12 months</span>
        </div>
        <div class="chart-body" style="height:240px">
            <?php if (empty($monthlyRev)): ?>
            <div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--text-muted);font-size:.85rem">No invoice data yet</div>
            <?php else: ?>
            <canvas id="revenueChart" aria-label="Monthly revenue chart" role="img"></canvas>
            <?php endif; ?>
        </div>
    </div>

    <!-- Staff performance -->
    <div class="chart-card">
        <div class="chart-header">
            <h2 class="chart-title">Technician Performance</h2>
            <a href="<?= BASE_URL ?>/staff" class="btn btn-xs btn-secondary">All Staff</a>
        </div>
        <?php
        $activeStaff = array_filter($staffStats ?? [], fn($s) => (int)$s['total_repairs'] > 0);
        ?>
        <?php if (empty($activeStaff)): ?>
        <div style="padding:2rem 1.25rem;text-align:center;color:var(--text-muted);font-size:.85rem">No staff data yet</div>
        <?php else: ?>
        <ul class="staff-list">
            <?php foreach (array_slice(array_values($activeStaff), 0, 7) as $s): ?>
            <li class="staff-item">
                <div class="staff-av"><?= strtoupper(substr($s['full_name'], 0, 1)) ?></div>
                <div>
                    <div class="staff-name"><?= Utils::e($s['full_name']) ?></div>
                    <div class="staff-sub">
                        <?= (int)$s['in_progress'] ?> active ·
                        <?= (int)$s['completed'] ?> completed
                    </div>
                </div>
                <div class="staff-num"><?= (int)$s['total_repairs'] ?></div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

</div>
<?php endif; ?>

<?php
// Chart data
$chartLabels  = json_encode(array_column($monthlyRev ?? [], 'month'));
$chartRevenue = json_encode(array_map('floatval', array_column($monthlyRev ?? [], 'revenue')));
$chartPaid    = json_encode(array_map('floatval', array_column($monthlyRev ?? [], 'paid')));

$inlineJs = <<<JS
document.addEventListener('DOMContentLoaded', function () {
    var ctx = document.getElementById('revenueChart');
    if (!ctx || !window.Chart) return;

    var dark      = document.documentElement.getAttribute('data-theme') === 'dark';
    var gridColor = dark ? 'rgba(255,255,255,.07)' : 'rgba(0,0,0,.06)';
    var tickColor = dark ? '#a0a6b0' : '#666';

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {$chartLabels},
            datasets: [
                {
                    label: 'Revenue',
                    data: {$chartRevenue},
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,.12)',
                    tension: .4, fill: true, borderWidth: 2,
                    pointBackgroundColor: '#10b981', pointRadius: 3
                },
                {
                    label: 'Collected',
                    data: {$chartPaid},
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99,102,241,.08)',
                    tension: .4, fill: false, borderWidth: 2,
                    pointBackgroundColor: '#6366f1', pointRadius: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: tickColor, font: { size: 12 }, boxWidth: 14 } }
            },
            scales: {
                x: { ticks: { color: tickColor, font: { size: 11 } }, grid: { color: gridColor } },
                y: { ticks: { color: tickColor, font: { size: 11 } }, grid: { color: gridColor }, beginAtZero: true }
            }
        }
    });
});
JS;
?>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
