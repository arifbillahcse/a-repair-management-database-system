<?php
$pageTitle  = 'Reports';
$loadCharts = true;
require VIEWS_PATH . '/layouts/header.php';

$pr  = $periodRepairs  ?? [];
$prv = $periodRevenue  ?? [];
$la  = $liveActive     ?? [];

$totalRep  = (int)($pr['total']     ?? 0);
$completed = (int)($pr['completed'] ?? 0);
$compRate  = $totalRep > 0 ? round($completed / $totalRep * 100) : 0;
$revenue   = (float)($prv['revenue'] ?? 0);
$paid      = (float)($prv['paid']    ?? 0);
$outstanding = round($revenue - $paid, 2);

// Preset link helper
$qs = fn(string $r) => BASE_URL . '/reports?range=' . $r;
?>
<style>
/* ── Range selector ───────────────────────────────── */
.range-bar {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: .75rem 1.1rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--shadow-sm);
}
.range-presets {
    display: flex;
    gap: .35rem;
    flex-wrap: wrap;
}
.range-btn {
    padding: .35rem .85rem;
    border-radius: var(--radius-full);
    font-size: .8rem;
    font-weight: 600;
    color: var(--text-secondary);
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    text-decoration: none;
    transition: all var(--transition);
    white-space: nowrap;
}
.range-btn:hover { color: var(--accent); border-color: var(--accent); background: var(--accent-dim); }
.range-btn.active { background: var(--accent); color: #fff; border-color: var(--accent); }
.range-divider { width: 1px; height: 28px; background: var(--border); flex-shrink: 0; }
.range-custom-form {
    display: flex;
    align-items: center;
    gap: .5rem;
    flex-wrap: wrap;
}
.range-custom-form .form-input {
    width: auto;
    padding: .32rem .65rem;
    font-size: .8rem;
}
.range-label-badge {
    margin-left: auto;
    font-size: .78rem;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: .3rem;
    white-space: nowrap;
}
.range-label-badge strong { color: var(--text-primary); }

/* ── KPI grid ─────────────────────────────────────── */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

/* ── Chart cards ──────────────────────────────────── */
.rep-grid-3 { display: grid; grid-template-columns: 1fr; gap: 1.25rem; margin-bottom: 1.25rem; }
@media (min-width: 900px) { .rep-grid-3 { grid-template-columns: 2fr 1fr; } }
.rep-grid-2 { display: grid; grid-template-columns: 1fr; gap: 1.25rem; margin-bottom: 1.25rem; }
@media (min-width: 900px) { .rep-grid-2 { grid-template-columns: 1fr 1fr; } }

.chart-card { background: var(--bg-secondary); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm); }
.chart-header { display: flex; align-items: center; justify-content: space-between; padding: .85rem 1.25rem; border-bottom: 1px solid var(--border); }
.chart-title { font-size: .9rem; font-weight: 600; margin: 0; }
.chart-body { padding: 1.25rem; }

/* ── Status bars ──────────────────────────────────── */
.status-wrap { display: flex; flex-direction: column; gap: .55rem; padding: 1rem 1.25rem; }
.status-row { display: flex; align-items: center; gap: .75rem; }
.status-row-label { font-size: .81rem; min-width: 150px; }
.status-track { flex: 1; height: 8px; background: var(--bg-tertiary); border-radius: 4px; overflow: hidden; }
.status-fill { height: 100%; border-radius: 4px; transition: width .45s ease; }
.status-count { font-size: .78rem; color: var(--text-muted); min-width: 36px; text-align: right; font-weight: 600; }

/* ── Staff list ───────────────────────────────────── */
.staff-list { list-style: none; padding: 0; margin: 0; }
.staff-item { display: flex; align-items: center; gap: .75rem; padding: .65rem 1.25rem; border-bottom: 1px solid var(--border); }
.staff-item:last-child { border-bottom: none; }
.staff-av { width: 34px; height: 34px; border-radius: 50%; background: var(--accent-dim); color: var(--accent); font-size: .85rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.staff-name { font-size: .86rem; font-weight: 600; }
.staff-sub { font-size: .74rem; color: var(--text-secondary); }
.staff-num { font-size: 1.1rem; font-weight: 700; color: var(--accent); margin-left: auto; }

/* ── Top clients table ────────────────────────────── */
.clients-table { width: 100%; border-collapse: collapse; }
.clients-table th { padding: .55rem 1rem; font-size: .72rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: .06em; border-bottom: 1px solid var(--border); text-align: left; background: var(--bg-secondary); }
.clients-table td { padding: .6rem 1rem; font-size: .84rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
.clients-table tbody tr:last-child td { border-bottom: none; }
.clients-table tbody tr:hover { background: var(--bg-tertiary); }
.rank { font-size: .72rem; color: var(--text-muted); font-weight: 700; width: 28px; text-align: center; }
.bar-track { height: 4px; background: var(--bg-tertiary); border-radius: 2px; margin-top: 4px; overflow: hidden; }
.bar-fill { height: 100%; background: var(--accent); border-radius: 2px; }
</style>

<!-- ── Page header ─────────────────────────────────────────────────────────── -->
<div class="page-header">
    <div>
        <h1 class="page-title">Reports</h1>
        <p class="page-subtitle">Analytics &amp; performance overview</p>
    </div>
</div>

<!-- ── Date range selector ────────────────────────────────────────────────── -->
<div class="range-bar">
    <div class="range-presets">
        <a href="<?= $qs('this_month') ?>" class="range-btn <?= $range === 'this_month' ? 'active' : '' ?>">This Month</a>
        <a href="<?= $qs('this_year')  ?>" class="range-btn <?= $range === 'this_year'  ? 'active' : '' ?>">This Year</a>
        <a href="<?= $qs('last_3m')    ?>" class="range-btn <?= $range === 'last_3m'    ? 'active' : '' ?>">Last 3M</a>
        <a href="<?= $qs('last_6m')    ?>" class="range-btn <?= $range === 'last_6m'    ? 'active' : '' ?>">Last 6M</a>
        <a href="<?= $qs('last_12m')   ?>" class="range-btn <?= $range === 'last_12m'   ? 'active' : '' ?>">Last 12M</a>
    </div>
    <div class="range-divider"></div>
    <form class="range-custom-form" method="GET" action="<?= BASE_URL ?>/reports">
        <input type="hidden" name="range" value="custom">
        <input type="date" name="date_from" class="form-input"
               value="<?= Utils::e($range === 'custom' ? ($dateFrom ?? $start) : $start) ?>"
               max="<?= date('Y-m-d') ?>">
        <span style="font-size:.8rem;color:var(--text-muted)">→</span>
        <input type="date" name="date_to" class="form-input"
               value="<?= Utils::e($range === 'custom' ? ($dateTo ?? $end) : $end) ?>"
               max="<?= date('Y-m-d') ?>">
        <button type="submit" class="btn btn-primary btn-sm">Apply</button>
    </form>
    <div class="range-label-badge">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <strong><?= Utils::e($rangeLabel) ?></strong>
    </div>
</div>

<!-- ── KPI cards ──────────────────────────────────────────────────────────── -->
<div class="kpi-grid">

    <!-- Total Repairs -->
    <div class="stat-card">
        <div class="stat-icon stat-icon-blue">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="stat-value"><?= $totalRep ?></div>
            <div class="stat-label">Total Repairs</div>
            <div style="font-size:.74rem;color:var(--text-muted);margin-top:.15rem">in selected period</div>
        </div>
        <a href="<?= BASE_URL ?>/repairs" class="stat-link" title="View all repairs">&#x2197;</a>
    </div>

    <!-- Completed -->
    <div class="stat-card">
        <div class="stat-icon stat-icon-green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="stat-value" style="color:var(--success)"><?= $completed ?></div>
            <div class="stat-label">Completed</div>
            <div style="font-size:.74rem;color:var(--text-muted);margin-top:.15rem">
                <?= $totalRep > 0 ? $compRate . '% completion rate' : 'No data' ?>
            </div>
        </div>
    </div>

    <!-- Active (live queue) -->
    <div class="stat-card">
        <div class="stat-icon stat-icon-orange">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="stat-value" style="color:var(--warning)"><?= (int)($la['total'] ?? 0) ?></div>
            <div class="stat-label">Active Right Now</div>
            <div style="font-size:.74rem;color:var(--text-muted);margin-top:.15rem">
                <?= (int)($la['in_progress'] ?? 0) ?> in progress ·
                <?= (int)($la['on_hold'] ?? 0) ?> on hold ·
                <?= (int)($la['waiting_for_parts'] ?? 0) ?> waiting
            </div>
        </div>
    </div>

    <!-- Period Revenue -->
    <div class="stat-card">
        <div class="stat-icon stat-icon-purple">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="1" x2="12" y2="23"/>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="stat-value"><?= Utils::formatCurrency($revenue) ?></div>
            <div class="stat-label">Revenue</div>
            <div style="font-size:.74rem;color:var(--text-muted);margin-top:.15rem">
                <?= (int)($prv['count'] ?? 0) ?> invoices issued
            </div>
        </div>
    </div>

    <!-- Collected -->
    <div class="stat-card">
        <div class="stat-icon stat-icon-green">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="5" width="20" height="14" rx="2"/>
                <line x1="2" y1="10" x2="22" y2="10"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="stat-value" style="color:var(--success)"><?= Utils::formatCurrency($paid) ?></div>
            <div class="stat-label">Collected</div>
            <div style="font-size:.74rem;color:var(--text-muted);margin-top:.15rem">
                <?= $revenue > 0 ? round($paid / $revenue * 100) . '% of revenue' : 'No invoices' ?>
            </div>
        </div>
    </div>

    <!-- Outstanding -->
    <div class="stat-card">
        <div class="stat-icon <?= $outstanding > 0 ? 'stat-icon-orange' : 'stat-icon-green' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="stat-value" style="color:<?= $outstanding > 0 ? 'var(--warning)' : 'var(--success)' ?>">
                <?= Utils::formatCurrency($outstanding) ?>
            </div>
            <div class="stat-label">Outstanding</div>
            <div style="font-size:.74rem;color:var(--text-muted);margin-top:.15rem">
                <?= $outstanding > 0 ? 'Pending collection' : 'Fully collected' ?>
            </div>
        </div>
    </div>

</div>

<!-- ── Revenue + status charts ────────────────────────────────────────────── -->
<div class="rep-grid-3">

    <!-- Revenue line chart -->
    <div class="chart-card">
        <div class="chart-header">
            <h2 class="chart-title">Revenue &amp; Collections</h2>
            <span style="font-size:.76rem;color:var(--text-muted)"><?= Utils::e($rangeLabel) ?></span>
        </div>
        <div class="chart-body" style="height:250px">
            <?php if (empty($monthlyRev)): ?>
            <div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--text-muted);font-size:.85rem">No invoice data for this period</div>
            <?php else: ?>
            <canvas id="revenueChart" aria-label="Revenue chart" role="img"></canvas>
            <?php endif; ?>
        </div>
    </div>

    <!-- Repairs by status bars -->
    <div class="chart-card">
        <div class="chart-header">
            <h2 class="chart-title">All-Time Status</h2>
            <span class="badge badge-gray"><?= array_sum($statusCounts) ?> total</span>
        </div>
        <?php
        $totalAll = max(1, array_sum($statusCounts));
        $statusColors = [
            'in_progress'       => '#3b82f6',
            'on_hold'           => '#f59e0b',
            'waiting_for_parts' => '#8b5cf6',
            'ready_for_pickup'  => '#14b8a6',
            'completed'         => '#10b981',
            'collected'         => '#059669',
            'cancelled'         => '#ef4444',
        ];
        ?>
        <div class="status-wrap">
            <?php foreach (REPAIR_STATUS as $key => $label):
                $cnt = (int)($statusCounts[$key] ?? 0);
                if (!$cnt) continue;
                $pct   = round($cnt / $totalAll * 100);
                $color = $statusColors[$key] ?? '#6b7280';
            ?>
            <div class="status-row">
                <span class="status-row-label">
                    <span class="badge <?= REPAIR_STATUS_CLASS[$key] ?? 'badge-gray' ?>"><?= Utils::e($label) ?></span>
                </span>
                <div class="status-track">
                    <div class="status-fill" style="width:<?= $pct ?>%;background:<?= $color ?>"></div>
                </div>
                <span class="status-count"><?= $cnt ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<!-- ── Repairs-by-month + staff ──────────────────────────────────────────── -->
<div class="rep-grid-2">

    <!-- Repairs bar chart -->
    <div class="chart-card">
        <div class="chart-header">
            <h2 class="chart-title">Repairs Received</h2>
            <span style="font-size:.76rem;color:var(--text-muted)"><?= Utils::e($rangeLabel) ?></span>
        </div>
        <div class="chart-body" style="height:230px">
            <?php if (empty($repairsByMonth)): ?>
            <div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--text-muted);font-size:.85rem">No repair data for this period</div>
            <?php else: ?>
            <canvas id="repairsChart" aria-label="Repairs chart" role="img"></canvas>
            <?php endif; ?>
        </div>
    </div>

    <!-- Staff performance -->
    <div class="chart-card">
        <div class="chart-header">
            <h2 class="chart-title">Technician Performance</h2>
            <span style="font-size:.76rem;color:var(--text-muted)"><?= Utils::e($rangeLabel) ?></span>
        </div>
        <?php if (empty(array_filter($staffStats, fn($s) => $s['total_repairs'] > 0))): ?>
        <div style="padding:2rem 1.25rem;text-align:center;color:var(--text-muted);font-size:.85rem">No staff data for this period</div>
        <?php else: ?>
        <ul class="staff-list">
            <?php foreach (array_slice($staffStats, 0, 8) as $s):
                if (!(int)$s['total_repairs']) continue;
            ?>
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

<!-- ── Top clients ────────────────────────────────────────────────────────── -->
<?php if (!empty($topCustomers)): ?>
<div class="chart-card" style="margin-bottom:1.5rem">
    <div class="chart-header">
        <h2 class="chart-title">Top Clients by Revenue</h2>
        <a href="<?= BASE_URL ?>/customers" class="btn btn-xs btn-secondary">All Clients</a>
    </div>
    <?php $maxBilled = max(1, max(array_column($topCustomers, 'total_billed'))); ?>
    <div class="table-responsive">
        <table class="clients-table">
            <thead>
                <tr>
                    <th style="width:32px">#</th>
                    <th>Client</th>
                    <th style="width:70px;text-align:center">Repairs</th>
                    <th style="width:130px;text-align:right">Billed</th>
                    <th style="width:130px;text-align:right">Collected</th>
                    <th style="width:120px;text-align:right">Balance</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($topCustomers as $i => $c):
                $billed  = (float)$c['total_billed'];
                $cpaid   = (float)$c['total_paid'];
                $bal     = round($billed - $cpaid, 2);
                $barW    = round($billed / $maxBilled * 100);
            ?>
            <tr>
                <td class="rank"><?= $i + 1 ?></td>
                <td>
                    <a href="<?= BASE_URL ?>/customers/<?= (int)$c['customer_id'] ?>"
                       class="table-link"><?= Utils::e($c['full_name']) ?></a>
                    <div class="bar-track"><div class="bar-fill" style="width:<?= $barW ?>%"></div></div>
                </td>
                <td style="text-align:center;color:var(--text-secondary)"><?= (int)$c['total_repairs'] ?></td>
                <td style="text-align:right;font-weight:500"><?= Utils::formatCurrency($billed) ?></td>
                <td style="text-align:right;color:var(--success)"><?= Utils::formatCurrency($cpaid) ?></td>
                <td style="text-align:right;color:<?= $bal > 0 ? 'var(--warning)' : 'var(--success)' ?>;font-weight:<?= $bal > 0 ? '600' : '400' ?>">
                    <?= Utils::formatCurrency($bal) ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ══ Inventory & Sales ══════════════════════════════════════════════════════ -->
<div class="grid-2col" style="display:grid;grid-template-columns:1fr;gap:1.5rem;margin-bottom:1.5rem">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1rem">
        <div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);padding:.85rem 1.1rem">
            <div style="font-size:1.25rem;font-weight:700"><?= Utils::formatCurrency($salesPeriod['total_revenue'] ?? 0) ?></div>
            <div style="font-size:.75rem;color:var(--text-secondary)">Product Sales (<?= Utils::e($rangeLabel) ?>)</div>
        </div>
        <div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);padding:.85rem 1.1rem">
            <div style="font-size:1.25rem;font-weight:700;color:var(--success)"><?= Utils::formatCurrency($salesPeriod['total_paid'] ?? 0) ?></div>
            <div style="font-size:.75rem;color:var(--text-secondary)">Sales Collected</div>
        </div>
        <div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);padding:.85rem 1.1rem">
            <div style="font-size:1.25rem;font-weight:700"><?= Utils::formatCurrency($stockStats['cost_value'] ?? 0) ?></div>
            <div style="font-size:.75rem;color:var(--text-secondary)">Stock Value (cost)</div>
        </div>
        <div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);padding:.85rem 1.1rem">
            <div style="font-size:1.25rem;font-weight:700;color:<?= ($stockStats['low_stock_count'] ?? 0) > 0 ? 'var(--warning)' : 'var(--text-primary)' ?>">
                <?= (int)($stockStats['low_stock_count'] ?? 0) ?>
            </div>
            <div style="font-size:.75rem;color:var(--text-secondary)">Low-Stock Products</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr;gap:1.5rem;margin-bottom:1.5rem" class="inv-2col">
<style>@media(min-width:960px){.inv-2col{grid-template-columns:1fr 1fr !important}}</style>

    <!-- Best sellers -->
    <div class="chart-card">
        <div class="chart-header">
            <h2 class="chart-title">Best-Selling Products</h2>
            <a href="<?= BASE_URL ?>/sales" class="btn btn-xs btn-secondary">All Sales</a>
        </div>
        <?php if (empty($bestSellers)): ?>
        <p style="padding:1.5rem;text-align:center;color:var(--text-muted);font-size:.85rem;margin:0">
            No product sales in this period.
        </p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="clients-table">
                <thead>
                    <tr>
                        <th style="width:32px">#</th>
                        <th>Product</th>
                        <th style="width:80px;text-align:center">Units</th>
                        <th style="width:120px;text-align:right">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($bestSellers as $i => $bs): ?>
                <tr>
                    <td class="rank"><?= $i + 1 ?></td>
                    <td>
                        <?php if (!empty($bs['product_id'])): ?>
                        <a href="<?= BASE_URL ?>/products/<?= (int)$bs['product_id'] ?>" class="table-link"><?= Utils::e($bs['name']) ?></a>
                        <?php else: ?>
                        <?= Utils::e($bs['name']) ?>
                        <?php endif; ?>
                        <?php if (!empty($bs['sku'])): ?>
                        <div style="font-size:.72rem;color:var(--text-muted);font-family:var(--font-mono)"><?= Utils::e($bs['sku']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;font-weight:600"><?= rtrim(rtrim(number_format((float)$bs['units_sold'], 3), '0'), '.') ?></td>
                    <td style="text-align:right;color:var(--success);font-weight:500"><?= Utils::formatCurrency($bs['revenue']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Low stock -->
    <div class="chart-card">
        <div class="chart-header">
            <h2 class="chart-title">Low Stock</h2>
            <a href="<?= BASE_URL ?>/products?stock=low" class="btn btn-xs btn-secondary">View All</a>
        </div>
        <?php if (empty($lowStock)): ?>
        <p style="padding:1.5rem;text-align:center;color:var(--text-muted);font-size:.85rem;margin:0">
            No products below their alert threshold. 👍
        </p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="clients-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th style="width:90px;text-align:center">On Hand</th>
                        <th style="width:90px;text-align:center">Threshold</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lowStock as $ls): ?>
                <tr>
                    <td>
                        <a href="<?= BASE_URL ?>/products/<?= (int)$ls['product_id'] ?>" class="table-link"><?= Utils::e($ls['name']) ?></a>
                        <?php if (!empty($ls['sku'])): ?>
                        <div style="font-size:.72rem;color:var(--text-muted);font-family:var(--font-mono)"><?= Utils::e($ls['sku']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;font-weight:700;color:<?= (int)$ls['quantity_on_hand'] === 0 ? 'var(--error)' : 'var(--warning)' ?>">
                        <?= (int)$ls['quantity_on_hand'] ?>
                    </td>
                    <td style="text-align:center;color:var(--text-secondary)"><?= (int)$ls['low_stock_threshold'] ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php
// Chart.js data
$revLabels  = json_encode(array_column($monthlyRev     ?? [], 'month'));
$revRev     = json_encode(array_map('floatval', array_column($monthlyRev ?? [], 'revenue')));
$revPaid    = json_encode(array_map('floatval', array_column($monthlyRev ?? [], 'paid')));
$repLabels  = json_encode(array_column($repairsByMonth ?? [], 'month'));
$repCounts  = json_encode(array_map('intval',   array_column($repairsByMonth ?? [], 'count')));

$inlineJs = <<<JS
document.addEventListener('DOMContentLoaded', function () {
    if (!window.Chart) return;

    var dark      = document.documentElement.getAttribute('data-theme') === 'dark';
    var gridColor = dark ? 'rgba(255,255,255,.07)' : 'rgba(0,0,0,.06)';
    var tickColor = dark ? '#a0a6b0' : '#666';

    var commonScales = {
        x: { ticks: { color: tickColor, font: { size: 11 } }, grid: { color: gridColor } },
        y: { ticks: { color: tickColor, font: { size: 11 } }, grid: { color: gridColor }, beginAtZero: true }
    };

    // ── Revenue chart ─────────────────────────────────────
    var rCtx = document.getElementById('revenueChart');
    if (rCtx) {
        new Chart(rCtx, {
            type: 'line',
            data: {
                labels: {$revLabels},
                datasets: [
                    {
                        label: 'Revenue',
                        data: {$revRev},
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,.12)',
                        tension: .4, fill: true, borderWidth: 2,
                        pointBackgroundColor: '#10b981', pointRadius: 3
                    },
                    {
                        label: 'Collected',
                        data: {$revPaid},
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99,102,241,.08)',
                        tension: .4, fill: false, borderWidth: 2,
                        pointBackgroundColor: '#6366f1', pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: tickColor, font: { size: 12 }, boxWidth: 14 } }
                },
                scales: commonScales
            }
        });
    }

    // ── Repairs per month chart ───────────────────────────
    var mCtx = document.getElementById('repairsChart');
    if (mCtx) {
        new Chart(mCtx, {
            type: 'bar',
            data: {
                labels: {$repLabels},
                datasets: [{
                    label: 'Repairs',
                    data: {$repCounts},
                    backgroundColor: 'rgba(99,102,241,.65)',
                    borderColor: '#6366f1',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: commonScales.x,
                    y: { ...commonScales.y, ticks: { ...commonScales.y.ticks, precision: 0 } }
                }
            }
        });
    }

    // Redraw charts when theme changes
    document.getElementById('themeToggle') && document.getElementById('themeToggle').addEventListener('click', function () {
        setTimeout(function () {
            document.querySelectorAll('canvas').forEach(function (c) {
                if (c.chart) { c.chart.destroy(); }
            });
        }, 300);
    });
});
JS;
?>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
