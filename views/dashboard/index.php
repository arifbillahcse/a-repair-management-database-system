<?php
$pageTitle  = 'Dashboard';
$loadCharts = true;
require VIEWS_PATH . '/layouts/header.php';

// Provide safe defaults if data not yet loaded
$stats        = $stats        ?? [];
$monthlyStats = $monthlyStats ?? [];
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">
            <?= Utils::formatDateTime(date('Y-m-d H:i:s')) ?> — <?= Utils::e(Auth::user()['full_name'] ?: Auth::user()['username']) ?>
        </p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
        <a href="<?= BASE_URL ?>/repairs" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>All Repairs
        </a>
        <a href="<?= BASE_URL ?>/customers" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>All Clients
        </a>
        <a href="<?= BASE_URL ?>/customers/create" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><line x1="12" y1="12" x2="12" y2="18"/><line x1="9" y1="15" x2="15" y2="15"/>
            </svg>New Client
        </a>
        <a href="<?= BASE_URL ?>/repairs/create" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>New Repair
        </a>
    </div>
</div>

<!-- ── Stat cards ──────────────────────────────────────────────────────── -->
<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-icon stat-icon-blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="stat-value"><?= (int)($stats['in_progress'] ?? 0) ?></div>
            <div class="stat-label">In Progress</div>
        </div>
        <a href="<?= BASE_URL ?>/repairs?status=in_progress" class="stat-link" aria-label="View in-progress repairs">→</a>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="stat-value"><?= (int)($stats['ready_for_pickup'] ?? 0) ?></div>
            <div class="stat-label">Ready for Pickup</div>
        </div>
        <a href="<?= BASE_URL ?>/repairs?status=ready_for_pickup" class="stat-link" aria-label="View ready repairs">→</a>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="stat-value"><?= (int)($stats['this_month'] ?? 0) ?></div>
            <div class="stat-label">Repairs This Month</div>
        </div>
        <a href="<?= BASE_URL ?>/repairs" class="stat-link" aria-label="View all repairs">→</a>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="stat-value"><?= (int)($totalCustomers ?? 0) ?></div>
            <div class="stat-label">Total Clients</div>
        </div>
        <a href="<?= BASE_URL ?>/customers" class="stat-link" aria-label="View all clients">→</a>
    </div>

    <div class="stat-card">
        <div class="stat-icon stat-icon-cyan">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="stat-value"><?= (int)($totalInvoices ?? 0) ?></div>
            <div class="stat-label">Total Invoices</div>
        </div>
        <a href="<?= BASE_URL ?>/invoices" class="stat-link" aria-label="View all invoices">→</a>
    </div>

</div>

<!-- ── Main content grid ───────────────────────────────────────────────── -->
<div class="dashboard-grid">

    <!-- Recent repairs -->
    <div class="card dashboard-main">
        <div class="card-header">
            <h2 class="card-title">Recent Repairs</h2>
            <a href="<?= BASE_URL ?>/repairs" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Device</th>
                        <th>Status</th>
                        <th>Date In</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($recentRepairs)): ?>
                    <tr><td colspan="5" class="empty-state">No repairs yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentRepairs as $r): ?>
                    <tr>
                        <td>
                            <a href="<?= BASE_URL ?>/repairs/<?= $r['repair_id'] ?>" class="table-link">
                                #<?= $r['repair_id'] ?>
                            </a>
                        </td>
                        <td><?= Utils::e($r['customer_name']) ?></td>
                        <td><?= Utils::e(Utils::truncate($r['device_model'], 30)) ?></td>
                        <td>
                            <span class="badge <?= REPAIR_STATUS_CLASS[$r['status']] ?? 'badge-gray' ?>">
                                <?= Utils::e(REPAIR_STATUS[$r['status']] ?? $r['status']) ?>
                            </span>
                        </td>
                        <td><?= Utils::formatDate($r['date_in']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right column -->
    <aside class="dashboard-aside">

        <!-- Awaiting pickup -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Ready for Pickup</h2>
                <span class="badge badge-green"><?= count($readyPickup ?? []) ?></span>
            </div>
            <?php if (empty($readyPickup)): ?>
                <p class="empty-state">No devices waiting.</p>
            <?php else: ?>
            <ul class="pickup-list">
                <?php foreach (array_slice($readyPickup ?? [], 0, 6) as $p): ?>
                <li class="pickup-item">
                    <div class="pickup-info">
                        <span class="pickup-name"><?= Utils::e($p['customer_name']) ?></span>
                        <span class="pickup-device"><?= Utils::e($p['device_model']) ?></span>
                    </div>
                    <a href="<?= BASE_URL ?>/repairs/<?= $p['repair_id'] ?>" class="btn btn-xs btn-secondary">View</a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>

        <!-- Overdue pickups -->
        <?php if (!empty($overdueItems)): ?>
        <div class="card card-danger">
            <div class="card-header">
                <h2 class="card-title">Overdue Pickups</h2>
                <span class="badge badge-red"><?= count($overdueItems) ?></span>
            </div>
            <ul class="pickup-list">
                <?php foreach ($overdueItems as $o): ?>
                <li class="pickup-item">
                    <div class="pickup-info">
                        <span class="pickup-name"><?= Utils::e($o['customer_name']) ?></span>
                        <span class="pickup-device text-danger">
                            <?= $o['days_waiting'] ?> days overdue
                        </span>
                    </div>
                    <a href="<?= BASE_URL ?>/repairs/<?= $o['repair_id'] ?>" class="btn btn-xs btn-danger">View</a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>


    </aside>

</div>

<!-- ── Revenue chart (Chart.js) ───────────────────────────────────────── -->
<?php if (!empty($monthlyRev) && Auth::can('manager')): ?>
<div class="card" style="margin-top: 1.5rem">
    <div class="card-header">
        <h2 class="card-title">Revenue — Last 12 Months</h2>
    </div>
    <div style="position: relative; height: 260px">
        <canvas id="revenueChart" aria-label="Monthly revenue chart" role="img"></canvas>
    </div>
</div>
<?php
$chartLabels  = json_encode(array_column($monthlyRev, 'month'));
$chartRevenue = json_encode(array_column($monthlyRev, 'revenue'));
$chartPaid    = json_encode(array_column($monthlyRev, 'paid'));
$inlineJs = <<<JS
document.addEventListener('DOMContentLoaded', function () {
    var ctx = document.getElementById('revenueChart');
    if (!ctx || !window.Chart) return;
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
                    tension: .4,
                    fill: true,
                    pointBackgroundColor: '#10b981'
                },
                {
                    label: 'Paid',
                    data: {$chartPaid},
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,.08)',
                    tension: .4,
                    fill: false,
                    pointBackgroundColor: '#3b82f6'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { labels: { color: '#a0a0a0' } } },
            scales: {
                x: { ticks: { color: '#a0a0a0' }, grid: { color: '#2a2a2a' } },
                y: { ticks: { color: '#a0a0a0' }, grid: { color: '#2a2a2a' } }
            }
        }
    });
});
JS;
?>
<?php endif; ?>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
