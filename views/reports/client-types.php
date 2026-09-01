<?php
$pageTitle = 'Colleague vs Private Report';
require VIEWS_PATH . '/layouts/header.php';

$c = fn(float $v): string => Utils::formatCurrency($v);

// Selected-year monthly totals (footer row)
$mt = ['colleague_repairs'=>0,'private_repairs'=>0,'colleague_income'=>0.0,'private_income'=>0.0,'colleague_billed'=>0.0,'colleague_paid'=>0.0,'private_billed'=>0.0,'private_paid'=>0.0];
foreach ($byMonth as $row) {
    foreach ($mt as $k => $_) { $mt[$k] += $row[$k]; }
}
?>
<style>
.ct-bar{display:flex;align-items:center;gap:1rem;flex-wrap:wrap;background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);padding:.75rem 1.1rem;margin-bottom:1.5rem;box-shadow:var(--shadow-sm)}
.ct-bar label{font-size:.82rem;font-weight:600;color:var(--text-secondary)}
.ct-bar select{width:auto;padding:.35rem .7rem}
.ct-bar .hint{margin-left:auto;font-size:.76rem;color:var(--text-muted)}
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem}
.stat-card{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.1rem 1.25rem;box-shadow:var(--shadow-sm)}
.stat-value{font-size:1.5rem;font-weight:700;line-height:1.1}
.stat-label{font-size:.8rem;color:var(--text-secondary);margin-top:.2rem}
.stat-sub{font-size:.72rem;color:var(--text-muted);margin-top:.15rem}
.card{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-sm);margin-bottom:1.5rem}
.card-header{padding:.85rem 1.25rem;border-bottom:1px solid var(--border)}
.card-title{font-size:.9rem;font-weight:600;margin:0}
.ct-table{width:100%;border-collapse:collapse}
.ct-table th,.ct-table td{padding:.55rem .9rem;font-size:.83rem;border-bottom:1px solid var(--border);text-align:right}
.ct-table th:first-child,.ct-table td:first-child{text-align:left}
.ct-table thead th{background:var(--bg-tertiary);font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:var(--text-secondary);font-weight:600}
.ct-table tbody tr:hover{background:var(--bg-tertiary)}
.ct-table .grp-col{color:var(--accent);font-weight:600}
.ct-table tfoot td{font-weight:700;background:var(--bg-tertiary);border-top:2px solid var(--border)}
.ct-table .yr-link{color:var(--text-primary);font-weight:600;text-decoration:none}
.ct-table .yr-link:hover{color:var(--accent)}
.ct-table .muted{color:var(--text-muted)}
.col-div{border-left:2px solid var(--border)}
.legend{font-size:.72rem;color:var(--text-muted);padding:.6rem 1.25rem;border-top:1px solid var(--border)}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Colleague vs Private</h1>
        <p class="page-subtitle">Repairs &amp; revenue split by customer type — monthly and yearly</p>
    </div>
    <a href="<?= BASE_URL ?>/reports" class="btn btn-secondary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
        </svg>Repairs Report
    </a>
</div>

<!-- Year picker -->
<form method="GET" action="<?= BASE_URL ?>/reports/client-types" class="ct-bar">
    <label for="year">Year</label>
    <select id="year" name="year" class="form-input" onchange="this.form.submit()">
        <?php foreach ($availYears as $y): ?>
        <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
        <?php endforeach; ?>
    </select>
    <noscript><button type="submit" class="btn btn-primary btn-sm">Go</button></noscript>
    <span class="hint">“Private” = individual + company customers · “Colleague” = <code>colleague</code> client type</span>
</form>

<!-- KPI cards for the selected year -->
<div class="kpi-grid">
    <div class="stat-card">
        <div class="stat-value"><?= (int)$yearTotals['private_repairs'] ?></div>
        <div class="stat-label">Private Repairs</div>
        <div class="stat-sub"><?= $year ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:#a855f7"><?= (int)$yearTotals['colleague_repairs'] ?></div>
        <div class="stat-label">Colleague Repairs</div>
        <div class="stat-sub"><?= $year ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $c((float)$yearTotals['private_income']) ?></div>
        <div class="stat-label">Private Repair Income</div>
        <div class="stat-sub">From repairs' Actual Amount</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:#a855f7"><?= $c((float)$yearTotals['colleague_income']) ?></div>
        <div class="stat-label">Colleague Repair Income</div>
        <div class="stat-sub">From repairs' Actual Amount</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:var(--success)"><?= $c((float)$yearTotals['colleague_billed']) ?></div>
        <div class="stat-label">Colleague Invoiced</div>
        <div class="stat-sub">Collected <?= $c((float)$yearTotals['colleague_paid']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?= $c((float)$yearTotals['private_billed']) ?></div>
        <div class="stat-label">Private Invoiced</div>
        <div class="stat-sub">Collected <?= $c((float)$yearTotals['private_paid']) ?></div>
    </div>
</div>

<!-- Monthly breakdown for the selected year -->
<div class="card">
    <div class="card-header"><h2 class="card-title">Monthly breakdown — <?= $year ?></h2></div>
    <div class="table-responsive">
        <table class="ct-table">
            <thead>
                <tr>
                    <th rowspan="2" style="vertical-align:bottom">Month</th>
                    <th colspan="2" style="text-align:center">Repairs</th>
                    <th colspan="2" style="text-align:center" class="col-div">Repair Income</th>
                    <th colspan="2" style="text-align:center" class="col-div">Colleague Invoiced</th>
                    <th colspan="2" style="text-align:center" class="col-div">Private Invoiced</th>
                </tr>
                <tr>
                    <th>Private</th>
                    <th>Colleague</th>
                    <th class="col-div">Private</th>
                    <th>Colleague</th>
                    <th class="col-div">Billed</th>
                    <th>Collected</th>
                    <th class="col-div">Billed</th>
                    <th>Collected</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($byMonth as $row): ?>
                <?php $empty = !$row['private_repairs'] && !$row['colleague_repairs'] && !$row['colleague_billed'] && !$row['private_billed'] && !$row['colleague_income'] && !$row['private_income']; ?>
                <tr<?= $empty ? ' class="muted"' : '' ?>>
                    <td><?= Utils::e($row['label']) ?></td>
                    <td><?= (int)$row['private_repairs'] ?></td>
                    <td class="grp-col" style="color:#a855f7"><?= (int)$row['colleague_repairs'] ?></td>
                    <td class="col-div"><?= $c((float)$row['private_income']) ?></td>
                    <td class="grp-col" style="color:#a855f7"><?= $c((float)$row['colleague_income']) ?></td>
                    <td class="col-div"><?= $c((float)$row['colleague_billed']) ?></td>
                    <td class="muted"><?= $c((float)$row['colleague_paid']) ?></td>
                    <td class="col-div"><?= $c((float)$row['private_billed']) ?></td>
                    <td class="muted"><?= $c((float)$row['private_paid']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td>Total <?= $year ?></td>
                    <td><?= (int)$mt['private_repairs'] ?></td>
                    <td><?= (int)$mt['colleague_repairs'] ?></td>
                    <td class="col-div"><?= $c($mt['private_income']) ?></td>
                    <td><?= $c($mt['colleague_income']) ?></td>
                    <td class="col-div"><?= $c($mt['colleague_billed']) ?></td>
                    <td><?= $c($mt['colleague_paid']) ?></td>
                    <td class="col-div"><?= $c($mt['private_billed']) ?></td>
                    <td><?= $c($mt['private_paid']) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="legend">Repair Income is each repair's Actual Amount, counted as soon as it's priced — no invoice required. Invoiced columns are taken from invoices (excluding cancelled). Repairs are counted by check-in date.</div>
</div>

<!-- Yearly summary across all years -->
<div class="card">
    <div class="card-header"><h2 class="card-title">Yearly summary — all years</h2></div>
    <div class="table-responsive">
        <table class="ct-table">
            <thead>
                <tr>
                    <th rowspan="2" style="vertical-align:bottom">Year</th>
                    <th colspan="2" style="text-align:center">Repairs</th>
                    <th colspan="2" style="text-align:center" class="col-div">Repair Income</th>
                    <th colspan="2" style="text-align:center" class="col-div">Colleague Invoiced</th>
                    <th colspan="2" style="text-align:center" class="col-div">Private Invoiced</th>
                </tr>
                <tr>
                    <th>Private</th>
                    <th>Colleague</th>
                    <th class="col-div">Private</th>
                    <th>Colleague</th>
                    <th class="col-div">Billed</th>
                    <th>Collected</th>
                    <th class="col-div">Billed</th>
                    <th>Collected</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($byYear)): ?>
                <tr><td colspan="9" class="muted" style="text-align:center;padding:1.5rem">No data yet.</td></tr>
            <?php else: ?>
                <?php foreach ($byYear as $y => $row): ?>
                <tr<?= $y === $year ? ' style="background:var(--accent-dim)"' : '' ?>>
                    <td>
                        <a href="<?= BASE_URL ?>/reports/client-types?year=<?= $y ?>" class="yr-link"><?= $y ?></a>
                    </td>
                    <td><?= (int)$row['private_repairs'] ?></td>
                    <td style="color:#a855f7;font-weight:600"><?= (int)$row['colleague_repairs'] ?></td>
                    <td class="col-div"><?= $c((float)$row['private_income']) ?></td>
                    <td style="color:#a855f7;font-weight:600"><?= $c((float)$row['colleague_income']) ?></td>
                    <td class="col-div"><?= $c((float)$row['colleague_billed']) ?></td>
                    <td class="muted"><?= $c((float)$row['colleague_paid']) ?></td>
                    <td class="col-div"><?= $c((float)$row['private_billed']) ?></td>
                    <td class="muted"><?= $c((float)$row['private_paid']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="legend">Click a year to load its monthly breakdown above.</div>
</div>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
