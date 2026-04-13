<?php
$pageTitle = 'Repairs';
require VIEWS_PATH . '/layouts/header.php';

$search       = Utils::e($_GET['search'] ?? '');
$filterSt     = $_GET['status']      ?? '';
$filterType   = $_GET['client_type'] ?? '';
$sort         = $_GET['sort']        ?? 'date_in';
$dir          = $_GET['dir']         ?? 'DESC';
$custFilter   = (int)($_GET['customer_id'] ?? 0);
$pg           = $pagination;

function rep_sortUrl(string $col): string
{
    global $search, $filterSt, $filterType, $sort, $dir, $custFilter;
    $d = ($sort === $col && $dir === 'ASC') ? 'DESC' : 'ASC';
    return Utils::url('/repairs', array_filter([
        'search'      => htmlspecialchars_decode($search),
        'status'      => $filterSt,
        'client_type' => $filterType,
        'sort'        => $col,
        'dir'         => $d,
        'page'        => 1,
        'customer_id' => $custFilter ?: null,
    ]));
}
function rep_sortIcon(string $col): string
{
    global $sort, $dir;
    $s = '<svg style="width:10px;height:10px;vertical-align:middle;margin-left:2px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">';
    if ($sort !== $col) return $s . '<line x1="12" y1="5" x2="12" y2="19"/><polyline points="8 9 12 5 16 9"/><polyline points="16 15 12 19 8 15"/></svg>';
    return $dir === 'ASC'
        ? $s . '<polyline style="stroke:var(--accent)" points="8 15 12 19 16 15"/></svg>'
        : $s . '<polyline style="stroke:var(--accent)" points="8 9 12 5 16 9"/></svg>';
}
?>
<style>
.status-filters{display:flex;gap:.35rem;flex-wrap:wrap;padding:.75rem 1rem;border-bottom:1px solid var(--border)}
.sf-btn{display:inline-flex;align-items:center;gap:.35rem;padding:.3rem .75rem;border-radius:var(--radius-full);border:1px solid var(--border);background:none;color:var(--text-secondary);font-size:.78rem;font-weight:500;cursor:pointer;text-decoration:none;transition:all var(--transition);white-space:nowrap}
.sf-btn:hover{background:var(--bg-tertiary);color:var(--text-primary)}
.sf-btn.active{background:var(--accent-dim);border-color:var(--accent);color:var(--accent)}
.sf-count{display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 4px;border-radius:var(--radius-full);background:var(--bg-tertiary);font-size:.7rem;font-weight:700}
.sf-btn.active .sf-count{background:var(--accent);color:#fff}
.sf-btn-purple{color:#a855f7;border-color:#a855f7}
.sf-btn-purple:hover,.sf-btn-purple.active{background:rgba(168,85,247,.12);border-color:#a855f7;color:#a855f7}
.filter-bar{display:flex;gap:.5rem;padding:.75rem 1rem;flex-wrap:wrap;align-items:center;border-bottom:1px solid var(--border)}
.filter-bar .search-input-wrap{flex:1;min-width:200px}
/* .qr-wrap{display:flex;gap:.35rem;align-items:center}
.qr-input{width:180px;font-size:.82rem;padding:.35rem .6rem} */
.sort-lnk{color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:1px;white-space:nowrap}
.sort-lnk:hover{color:var(--accent)}
.rep-id-link{font-weight:700;color:var(--text-primary);text-decoration:none}
.rep-id-link:hover{color:var(--accent)}
.cust-sub{display:block;font-size:.75rem;color:var(--text-muted)}
.device-sub{display:block;font-size:.72rem;color:var(--text-muted);font-family:var(--font-mono)}
.act-btns{display:flex;gap:.2rem;justify-content:flex-end}
.act-btn{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:var(--radius);background:none;border:1px solid transparent;color:var(--text-muted);cursor:pointer;text-decoration:none;transition:all var(--transition)}
.act-btn svg{width:14px;height:14px}
.act-btn:hover{background:var(--bg-tertiary);color:var(--text-primary);border-color:var(--border)}
.act-btn-d:hover{background:var(--error-bg);color:var(--error);border-color:var(--error)}
.act-btn-p:hover{background:var(--accent-dim);color:var(--accent);border-color:var(--accent)}
.il-form{display:inline;margin:0;padding:0}
.tbl-footer{display:flex;align-items:center;justify-content:space-between;padding:.6rem 1rem;border-top:1px solid var(--border);flex-wrap:wrap;gap:.5rem}
.tbl-footer-info{font-size:.78rem;color:var(--text-secondary)}
.empty-big{display:flex;flex-direction:column;align-items:center;gap:.75rem;padding:3rem 1rem;text-align:center}
.empty-big svg{width:48px;height:48px;stroke:var(--text-muted)}
.empty-big p{color:var(--text-secondary)}
.days-ok{color:var(--text-secondary)}
.days-warn{color:var(--warning)}
.days-over{color:var(--error)}
.hide-mobile{display:none}
@media(min-width:700px){.hide-mobile{display:table-cell}}
.hide-t{display:none}
@media(min-width:1000px){.hide-t{display:table-cell}}
/* #qrResult{font-size:.78rem;color:var(--text-muted);margin-left:.25rem} */
</style>

<!-- Page header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Repairs</h1>
        <p class="page-subtitle">
            <?php
            $total = array_sum($statusCounts);
            echo number_format($total) . ' total';
            $active = ($statusCounts['in_progress'] ?? 0) + ($statusCounts['on_hold'] ?? 0) + ($statusCounts['waiting_for_parts'] ?? 0);
            if ($active > 0): ?>
            &nbsp;·&nbsp; <span style="color:var(--info)"><?= number_format($active) ?> active</span>
            <?php endif; ?>
            <?php if (!empty($statusCounts['ready_for_pickup'])): ?>
            &nbsp;·&nbsp; <span style="color:var(--success)"><?= number_format($statusCounts['ready_for_pickup']) ?> ready for pickup</span>
            <?php endif; ?>
        </p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
        <?php if (Auth::isAdmin()): ?>
        <a href="<?= BASE_URL ?>/import?type=repairs" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>Import CSV
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/repairs/create" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>New Repair
        </a>
    </div>
</div>

<?php if ($custFilter && !empty($customerFilter)): ?>
<div class="card" style="margin-bottom:.75rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.6rem 1rem;flex-wrap:wrap">
    <span style="font-size:.85rem;color:var(--text-secondary)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             style="width:14px;height:14px;vertical-align:-2px;margin-right:.3rem" aria-hidden="true">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        Showing repairs for
        <a href="<?= BASE_URL ?>/customers/<?= $custFilter ?>"
           style="font-weight:600;color:var(--text-primary);text-decoration:none"
           onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-primary)'">
            <?= Utils::e($customerFilter['full_name']) ?>
        </a>
    </span>
    <a href="<?= BASE_URL ?>/repairs" class="btn btn-secondary" style="padding:.25rem .75rem;font-size:.78rem">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
             style="width:12px;height:12px" aria-hidden="true">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
        </svg>Clear filter
    </a>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom:0">

    <!-- Status filter pills -->
    <div class="status-filters">
        <?php
        $base = array_filter(['search' => htmlspecialchars_decode($search), 'client_type' => $filterType, 'sort' => $sort, 'dir' => $dir, 'customer_id' => $custFilter ?: null]);
        ?>
        <a href="<?= Utils::url('/repairs', $base) ?>" class="sf-btn <?= $filterSt === '' ? 'active' : '' ?>">
            All <span class="sf-count"><?= number_format(array_sum($statusCounts)) ?></span>
        </a>
        <?php foreach (REPAIR_STATUS as $key => $label): ?>
        <?php $cnt = $statusCounts[$key] ?? 0; ?>
        <a href="<?= Utils::url('/repairs', array_filter(array_merge($base, ['status' => $key, 'page' => 1]))) ?>"
           class="sf-btn <?= $filterSt === $key ? 'active' : '' ?>">
            <?= Utils::e($label) ?> <span class="sf-count"><?= $cnt ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Customer type filter pills -->
    <div class="status-filters" style="border-top:none;padding-top:.4rem">
        <?php
        $typeBase = array_filter(['search' => htmlspecialchars_decode($search), 'status' => $filterSt, 'sort' => $sort, 'dir' => $dir, 'customer_id' => $custFilter ?: null]);
        $types = [
            'individual' => ['label' => 'Individual', 'icon' => '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>', 'class' => ''],
            'company'    => ['label' => 'Company',    'icon' => '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 21h18M3 7v14M21 7v14M3 7l9-4 9 4M9 21V12h6v9"/></svg>',               'class' => ''],
            'colleague'  => ['label' => 'Colleague',  'icon' => '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>', 'class' => 'sf-btn-purple'],
        ];
        ?>
        <a href="<?= Utils::url('/repairs', $typeBase) ?>"
           class="sf-btn <?= $filterType === '' ? 'active' : '' ?>">All Types</a>
        <?php foreach ($types as $key => $t): ?>
        <a href="<?= Utils::url('/repairs', array_filter(array_merge($typeBase, ['client_type' => $key, 'page' => 1]))) ?>"
           class="sf-btn <?= $filterType === $key ? 'active' : '' ?> <?= $t['class'] ?>">
            <?= $t['icon'] ?> <?= $t['label'] ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Search & QR bar -->
    <form method="GET" action="<?= BASE_URL ?>/repairs" class="filter-bar" role="search">
        <div class="search-input-wrap">
            <svg class="search-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="search" name="search" class="form-input"
                   placeholder="Search ID, client, device, serial…"
                   value="<?= $search ?>" autocomplete="off" aria-label="Search repairs"
                   data-ac-url="<?= BASE_URL ?>/api/customers/autocomplete"
                   data-ac-href="<?= BASE_URL ?>/customers/{id}">
        </div>
        <?php if ($custFilter): ?>
        <input type="hidden" name="customer_id" value="<?= $custFilter ?>">
        <?php endif; ?>
        <input type="hidden" name="status"      value="<?= Utils::e($filterSt) ?>">
        <input type="hidden" name="client_type" value="<?= Utils::e($filterType) ?>">
        <input type="hidden" name="sort"        value="<?= Utils::e($sort) ?>">
        <input type="hidden" name="dir"         value="<?= Utils::e($dir) ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($search !== '' || $filterSt !== '' || $filterType !== '' || $custFilter): ?>
        <a href="<?= BASE_URL ?>/repairs" class="btn btn-secondary">Clear</a>
        <?php endif; ?>

        <?php /* QR scan disabled
        <div class="qr-wrap" style="margin-left:auto">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 style="width:16px;height:16px;color:var(--text-muted)" aria-hidden="true">
                <rect x="3" y="3" width="5" height="5"/><rect x="16" y="3" width="5" height="5"/>
                <rect x="3" y="16" width="5" height="5"/><path d="M21 16h-3v3M21 21h-2M16 16v5M11 3v2M7 11H3M11 7v4h4v4h2M11 11v4M3 11v4h4v-4"/>
            </svg>
            <input type="text" id="qrInput" class="form-input qr-input"
                   placeholder="Scan QR code…" autocomplete="off" aria-label="QR code scan">
            <span id="qrResult"></span>
        </div>
        */ ?>
    </form>

    <!-- Table -->
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:52px"><a href="<?= rep_sortUrl('repair_id') ?>" class="sort-lnk"># <?= rep_sortIcon('repair_id') ?></a></th>
                    <th><a href="<?= rep_sortUrl('device_model') ?>" class="sort-lnk">Device <?= rep_sortIcon('device_model') ?></a></th>
                    <th class="hide-mobile"><a href="<?= rep_sortUrl('customer_name') ?>" class="sort-lnk">Client <?= rep_sortIcon('customer_name') ?></a></th>
                    <th class="hide-mobile">Type</th>
                    <th><a href="<?= rep_sortUrl('status') ?>" class="sort-lnk">Status <?= rep_sortIcon('status') ?></a></th>
                    <th class="hide-t"><a href="<?= rep_sortUrl('date_in') ?>" class="sort-lnk">Date In <?= rep_sortIcon('date_in') ?></a></th>
                    <th class="hide-t"><a href="<?= rep_sortUrl('days_in_lab') ?>" class="sort-lnk">Days <?= rep_sortIcon('days_in_lab') ?></a></th>
                    <th class="hide-t" style="text-align:right"><a href="<?= rep_sortUrl('actual_amount') ?>" class="sort-lnk">Amount <?= rep_sortIcon('actual_amount') ?></a></th>
                    <th style="width:100px;text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($repairs)): ?>
                <tr><td colspan="9">
                    <div class="empty-big">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                        </svg>
                        <p>
                            <?php if ($search !== ''): ?>
                                No repairs match "<strong><?= Utils::e(htmlspecialchars_decode($search)) ?></strong>".
                            <?php elseif ($filterSt !== ''): ?>
                                No repairs with status <strong><?= Utils::e(REPAIR_STATUS[$filterSt] ?? $filterSt) ?></strong>.
                            <?php else: ?>
                                No repairs on record yet.
                            <?php endif; ?>
                        </p>
                        <a href="<?= BASE_URL ?>/repairs/create" class="btn btn-primary">Create First Repair</a>
                    </div>
                </td></tr>
            <?php else: ?>
                <?php foreach ($repairs as $r): ?>
                <tr>
                    <td>
                        <a href="<?= BASE_URL ?>/repairs/<?= $r['repair_id'] ?>" class="rep-id-link">#<?= $r['repair_id'] ?></a>
                    </td>
                    <td style="max-width:180px">
                        <span title="<?= Utils::e($r['device_model']) ?>"><?= Utils::e(Utils::truncate($r['device_model'] ?? '—', 26)) ?></span>
                        <?php if (!empty($r['device_serial_number'])): ?>
                        <span class="device-sub"><?= Utils::e($r['device_serial_number']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="hide-mobile">
                        <?php if (!empty($r['customer_id'])): ?>
                        <a href="<?= BASE_URL ?>/customers/<?= $r['customer_id'] ?>"
                           style="color:var(--text-primary);text-decoration:none;font-size:.85rem"
                           onmouseover="this.style.color='var(--accent)'"
                           onmouseout="this.style.color='var(--text-primary)'">
                            <?= Utils::e($r['customer_name'] ?? '—') ?>
                        </a>
                        <?php if (!empty($r['customer_phone'])): ?>
                        <span class="cust-sub"><?= Utils::e($r['customer_phone']) ?></span>
                        <?php endif; ?>
                        <?php else: ?>
                        <span style="color:var(--text-muted)">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="hide-mobile">
                        <?php $ct = $r['customer_type'] ?? ''; ?>
                        <?php if ($ct === 'individual'): ?>
                        <span class="badge badge-gray" style="display:inline-flex;align-items:center;gap:.25rem">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Individual
                        </span>
                        <?php elseif ($ct === 'company'): ?>
                        <span class="badge badge-red" style="display:inline-flex;align-items:center;gap:.25rem">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M3 21h18M3 7v14M21 7v14M3 7l9-4 9 4M9 21V12h6v9"/></svg>Company
                        </span>
                        <?php elseif ($ct === 'colleague'): ?>
                        <span class="badge badge-purple" style="display:inline-flex;align-items:center;gap:.25rem">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>Colleague
                        </span>
                        <?php else: ?>
                        <span style="color:var(--text-muted)">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?= REPAIR_STATUS_CLASS[$r['status']] ?? 'badge-gray' ?>">
                            <?= Utils::e(REPAIR_STATUS[$r['status']] ?? $r['status']) ?>
                        </span>
                    </td>
                    <td class="hide-t" style="font-size:.82rem;color:var(--text-secondary);white-space:nowrap">
                        <?= Utils::formatDate($r['date_in']) ?>
                    </td>
                    <td class="hide-t">
                        <?php $days = (int)($r['days_in_lab'] ?? 0); ?>
                        <span class="<?= $days > 14 ? 'days-over' : ($days > 7 ? 'days-warn' : 'days-ok') ?>" style="font-size:.83rem">
                            <?= $days ?>d
                        </span>
                    </td>
                    <td class="hide-t" style="text-align:right;font-size:.83rem">
                        <?php if (!empty($r['actual_amount'])): ?>
                            <?= Utils::formatCurrency($r['actual_amount']) ?>
                        <?php elseif (!empty($r['estimate_amount'])): ?>
                            <span style="color:var(--text-muted)">~<?= Utils::formatCurrency($r['estimate_amount']) ?></span>
                        <?php else: ?>
                            <span style="color:var(--text-muted)">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="act-btns">
                            <a href="<?= BASE_URL ?>/repairs/<?= $r['repair_id'] ?>" class="act-btn" title="View repair">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            <a href="<?= BASE_URL ?>/repairs/<?= $r['repair_id'] ?>/edit" class="act-btn" title="Edit repair">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            <a href="<?= BASE_URL ?>/repairs/<?= $r['repair_id'] ?>/print" target="_blank" class="act-btn act-btn-p" title="Print repair sheet">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                            </a>
                            <?php if (Auth::can('manager')): ?>
                            <form method="POST" action="<?= BASE_URL ?>/repairs/<?= $r['repair_id'] ?>/delete"
                                  class="il-form" data-confirm="Delete repair #<?= $r['repair_id'] ?>? This cannot be undone.">
                                <input type="hidden" name="csrf_token" value="<?= Utils::e(Auth::generateCSRFToken()) ?>">
                                <button type="submit" class="act-btn act-btn-d" title="Delete repair">
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
        $bp = array_filter(['search' => htmlspecialchars_decode($search), 'status' => $filterSt, 'client_type' => $filterType, 'sort' => $sort, 'dir' => $dir, 'customer_id' => $custFilter ?: null]);
    ?>
    <div class="tbl-footer">
        <span class="tbl-footer-info">
            <?= number_format($pg['offset'] + 1) ?>–<?= number_format(min($pg['offset'] + $pg['perPage'], $pg['total'])) ?>
            of <?= number_format($pg['total']) ?>
        </span>
        <nav class="pagination" aria-label="Pagination">
            <a href="<?= $pg['hasPrev'] ? Utils::url('/repairs', array_merge($bp, ['page' => $pg['page'] - 1])) : '#' ?>"
               class="page-link <?= !$pg['hasPrev'] ? 'disabled' : '' ?>">&laquo;</a>
            <?php
            $s2 = max(1, $pg['page'] - 2);
            $e2 = min($pg['totalPages'], $pg['page'] + 2);
            if ($s2 > 1) echo '<span class="page-link disabled">…</span>';
            for ($p = $s2; $p <= $e2; $p++): ?>
            <a href="<?= Utils::url('/repairs', array_merge($bp, ['page' => $p])) ?>"
               class="page-link <?= $p === $pg['page'] ? 'current' : '' ?>"><?= $p ?></a>
            <?php endfor;
            if ($e2 < $pg['totalPages']) echo '<span class="page-link disabled">…</span>';
            ?>
            <a href="<?= $pg['hasNext'] ? Utils::url('/repairs', array_merge($bp, ['page' => $pg['page'] + 1])) : '#' ?>"
               class="page-link <?= !$pg['hasNext'] ? 'disabled' : '' ?>">&raquo;</a>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
/* QR code scan disabled
(function () {
    const inp = document.getElementById('qrInput');
    const res = document.getElementById('qrResult');
    if (!inp) return;
    inp.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        const code = inp.value.trim();
        if (!code) return;
        res.textContent = 'Looking up…';
        fetch('<?= BASE_URL ?>/api/repairs/qr?code=' + encodeURIComponent(code))
            .then(r => r.json())
            .then(data => {
                if (data.repair_id) {
                    window.location = '<?= BASE_URL ?>/repairs/' + data.repair_id;
                } else {
                    res.style.color = 'var(--error)';
                    res.textContent = 'Not found';
                    inp.value = '';
                    setTimeout(() => { res.textContent = ''; res.style.color = ''; }, 2500);
                }
            })
            .catch(() => { res.style.color = 'var(--error)'; res.textContent = 'Error'; });
    });
})();
*/

// Confirm dangerous actions
document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('submit', function (e) {
        if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
});
</script>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
