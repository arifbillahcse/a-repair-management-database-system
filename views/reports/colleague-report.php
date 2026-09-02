<?php
$pageTitle = 'Colleague Report';
require VIEWS_PATH . '/layouts/header.php';

$c = fn(float $v): string => Utils::formatCurrency($v);
$periods = [
    '30days'         => 'Last 30 Days',
    'current_month'  => 'Current Month',
    'previous_month' => 'Previous Month',
];
?>
<style>
.cp-tabs{display:flex;gap:.5rem;margin-bottom:1.5rem;flex-wrap:wrap}
.cp-tab{padding:.5rem 1rem;border-radius:var(--radius);border:1px solid var(--border);background:var(--bg-secondary);color:var(--text-secondary);text-decoration:none;font-size:.85rem;font-weight:600;transition:all var(--transition)}
.cp-tab:hover{background:var(--bg-tertiary);color:var(--text-primary)}
.cp-tab.active{background:var(--accent);color:#fff;border-color:var(--accent)}
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem}
.stat-card{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.1rem 1.25rem;box-shadow:var(--shadow-sm)}
.stat-value{font-size:1.5rem;font-weight:700;line-height:1.1}
.stat-label{font-size:.8rem;color:var(--text-secondary);margin-top:.2rem}
.stat-sub{font-size:.72rem;color:var(--text-muted);margin-top:.15rem}
.card{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-sm);margin-bottom:1.5rem}
.card-header{padding:.85rem 1.25rem;border-bottom:1px solid var(--border)}
.card-title{font-size:.9rem;font-weight:600;margin:0}
.cp-table{width:100%;border-collapse:collapse}
.cp-table th,.cp-table td{padding:.6rem .9rem;font-size:.85rem;border-bottom:1px solid var(--border);text-align:right}
.cp-table th:first-child,.cp-table td:first-child{text-align:left}
.cp-table thead th{background:var(--bg-tertiary);font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:var(--text-secondary);font-weight:600}
.cp-table tbody tr:hover{background:var(--bg-tertiary)}
.cp-table .muted{color:var(--text-muted)}
.cp-rank{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:var(--bg-tertiary);font-size:.72rem;font-weight:700;color:var(--text-secondary);margin-right:.5rem}
.legend{font-size:.72rem;color:var(--text-muted);padding:.6rem 1.25rem;border-top:1px solid var(--border)}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Colleague Report</h1>
        <p class="page-subtitle">Colleague repairs only — no private/individual customer data on this page</p>
    </div>
    <a href="<?= BASE_URL ?>/reports" class="btn btn-secondary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
        </svg>Repairs Report
    </a>
</div>

<!-- Period tabs -->
<div class="cp-tabs">
    <?php foreach ($periods as $key => $labelText): ?>
    <a href="<?= BASE_URL ?>/reports/colleagues?period=<?= $key ?>" class="cp-tab<?= $period === $key ? ' active' : '' ?>"><?= Utils::e($labelText) ?></a>
    <?php endforeach; ?>
</div>

<!-- KPI cards -->
<div class="kpi-grid">
    <div class="stat-card">
        <div class="stat-value"><?= (int)$totals['repairs_count'] ?></div>
        <div class="stat-label">Total Colleague Repairs</div>
        <div class="stat-sub"><?= Utils::e($label) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= (int)$totals['bills_generated'] ?></div>
        <div class="stat-label">Bills Generated</div>
        <div class="stat-sub"><?= Utils::e($label) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:#a855f7"><?= $c($totals['total_income']) ?></div>
        <div class="stat-label">Total Colleague Income</div>
        <div class="stat-sub"><?= Utils::e($label) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= (int)$totals['colleagues'] ?></div>
        <div class="stat-label">Active Colleagues</div>
        <div class="stat-sub">Brought in at least 1 repair or bill</div>
    </div>
</div>

<!-- Section A: per-colleague summary -->
<div class="card">
    <div class="card-header"><h2 class="card-title">By colleague — <?= Utils::e($label) ?></h2></div>
    <div class="table-responsive">
        <table class="cp-table">
            <thead>
                <tr>
                    <th>Colleague</th>
                    <th>Repairs</th>
                    <th>Bills Generated</th>
                    <th>Total Income</th>
                    <th>Avg / Repair</th>
                    <th>Last Repair</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($byColleague)): ?>
                <tr><td colspan="6" style="text-align:center;padding:1.5rem" class="muted">No colleague activity in this period.</td></tr>
            <?php else: ?>
                <?php foreach ($byColleague as $i => $r): ?>
                <?php $avg = $r['repairs_count'] > 0 ? ($r['total_income'] / $r['repairs_count']) : 0.0; ?>
                <tr>
                    <td>
                        <span class="cp-rank"><?= $i + 1 ?></span>
                        <a href="<?= BASE_URL ?>/customers/<?= (int)$r['customer_id'] ?>" style="color:var(--text-primary);text-decoration:none;font-weight:600"><?= Utils::e($r['full_name']) ?></a>
                        <?php if (!empty($r['phone_mobile'])): ?>
                        <div style="font-size:.72rem;color:var(--text-muted);margin-left:1.85rem"><?= Utils::e($r['phone_mobile']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= (int)$r['repairs_count'] ?></td>
                    <td><?= (int)$r['bills_generated'] ?></td>
                    <td style="font-weight:600"><?= $c($r['total_income']) ?></td>
                    <td class="muted"><?= $c($avg) ?></td>
                    <td class="muted"><?= $r['last_repair'] ? Utils::formatDate($r['last_repair']) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="legend">Repairs / Income are counted by repair check-in date. Bills Generated counts invoices by their invoice date (excluding cancelled) — a repair invoiced outside this window won't add to that count here.</div>
</div>

<!-- Section B: itemized list of every colleague repair -->
<div class="card">
    <div class="card-header"><h2 class="card-title">All colleague repairs — <?= Utils::e($label) ?></h2></div>
    <div class="table-responsive">
        <table class="cp-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th style="text-align:left">Colleague</th>
                    <th style="text-align:left">Device</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Date In</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($repairRows)): ?>
                <tr><td colspan="6" style="text-align:center;padding:1.5rem" class="muted">No colleague repairs in this period.</td></tr>
            <?php else: ?>
                <?php foreach ($repairRows as $rr): ?>
                <tr>
                    <td><a href="<?= BASE_URL ?>/repairs/<?= (int)$rr['repair_id'] ?>" style="color:var(--text-primary);text-decoration:none;font-weight:600">#<?= (int)$rr['repair_id'] ?></a></td>
                    <td style="text-align:left"><?= Utils::e($rr['customer_name'] ?? '—') ?></td>
                    <td style="text-align:left"><?= Utils::e($rr['device_model'] ?? '—') ?></td>
                    <td>
                        <?php if (!empty($rr['actual_amount'])): ?>
                            <?= $c((float)$rr['actual_amount']) ?>
                        <?php elseif (!empty($rr['estimate_amount'])): ?>
                            <span class="muted">~<?= $c((float)$rr['estimate_amount']) ?></span>
                        <?php else: ?>
                            <span class="muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= REPAIR_STATUS_CLASS[$rr['status']] ?? 'badge-gray' ?>"><?= Utils::e(REPAIR_STATUS[$rr['status']] ?? $rr['status']) ?></span></td>
                    <td class="muted"><?= Utils::formatDate($rr['date_in']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="legend"><?= count($repairRows) ?> repair<?= count($repairRows) === 1 ? '' : 's' ?> — click any row's # to open that repair.</div>
</div>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
