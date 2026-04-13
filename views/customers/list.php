<?php
$pageTitle = 'Customers';
require VIEWS_PATH . '/layouts/header.php';

$search = Utils::e($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$type   = $_GET['type']   ?? '';
$sort   = $_GET['sort']   ?? 'full_name';
$dir    = $_GET['dir']    ?? 'ASC';
$pg     = $pagination;

function cust_sortUrl(string $col): string
{
    global $search, $status, $type, $sort, $dir;
    $d = ($sort === $col && $dir === 'ASC') ? 'DESC' : 'ASC';
    return Utils::url('/customers', array_filter([
        'search' => htmlspecialchars_decode($search),
        'status' => $status, 'type' => $type, 'sort' => $col, 'dir' => $d, 'page' => 1,
    ]));
}
function cust_sortIcon(string $col): string
{
    global $sort, $dir;
    $s = '<svg style="width:10px;height:10px;vertical-align:middle;margin-left:2px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">';
    if ($sort !== $col) return $s . '<line x1="12" y1="5" x2="12" y2="19"/><polyline points="8 9 12 5 16 9"/><polyline points="16 15 12 19 8 15"/></svg>';
    return $dir === 'ASC' ? $s . '<polyline style="stroke:var(--accent)" points="8 15 12 19 16 15"/></svg>'
                          : $s . '<polyline style="stroke:var(--accent)" points="8 9 12 5 16 9"/></svg>';
}
?>
<style>
.header-actions{display:flex;gap:.5rem;flex-wrap:wrap;align-items:center}
.filter-bar{display:flex;gap:.5rem;padding:.75rem 1rem;flex-wrap:wrap;align-items:center}
.filter-bar .search-input-wrap{flex:1;min-width:200px}
.filter-select{width:auto;min-width:130px}
.sort-lnk{color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:1px;white-space:nowrap}
.sort-lnk:hover{color:var(--accent)}
.cust-name-link{font-weight:600;color:var(--text-primary);text-decoration:none}
.cust-name-link:hover{color:var(--accent)}
.ph-lnk,.em-lnk{color:var(--text-secondary);text-decoration:none;font-size:.83rem}
.ph-lnk:hover,.em-lnk:hover{color:var(--accent)}
.act-btns{display:flex;gap:.2rem;justify-content:flex-end}
.act-btn{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:var(--radius);background:none;border:1px solid transparent;color:var(--text-muted);cursor:pointer;text-decoration:none;transition:all var(--transition)}
.act-btn svg{width:14px;height:14px}
.act-btn:hover{background:var(--bg-tertiary);color:var(--text-primary);border-color:var(--border)}
.act-btn-g:hover{background:var(--accent-dim);color:var(--accent);border-color:var(--accent)}
.act-btn-d:hover{background:var(--error-bg);color:var(--error);border-color:var(--error)}
.il-form{display:inline;margin:0;padding:0}
.tbl-footer{display:flex;align-items:center;justify-content:space-between;padding:.6rem 1rem;border-top:1px solid var(--border);flex-wrap:wrap;gap:.5rem}
.tbl-footer-info{font-size:.78rem;color:var(--text-secondary)}
.empty-big{display:flex;flex-direction:column;align-items:center;gap:.75rem;padding:3rem 1rem;text-align:center}
.empty-big svg{width:48px;height:48px;stroke:var(--text-muted)}
.empty-big p{color:var(--text-secondary)}
.hide-t{display:none}
@media(min-width:900px){.hide-t{display:table-cell}}
.vat-sub{display:block;font-size:.72rem;color:var(--text-muted)}
</style>

<!-- Page header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Customers</h1>
        <p class="page-subtitle">
            <?= number_format($counts['total']) ?> total &nbsp;·&nbsp;
            <span style="color:var(--success)"><?= number_format($counts['active']) ?> active</span>
            <?php if ($counts['inactive'] > 0): ?>
            &nbsp;·&nbsp; <span style="color:var(--text-muted)"><?= number_format($counts['inactive']) ?> inactive</span>
            <?php endif; ?>
            <?php if ($counts['colleagues'] > 0): ?>
            &nbsp;·&nbsp; <span style="color:#7c3aed"><?= number_format($counts['colleagues']) ?> colleagues</span>
            <?php endif; ?>
        </p>
    </div>
    <div class="header-actions">
        <?php if (Auth::can('manager')): ?>
        <a href="<?= Utils::url('/customers/export', array_filter(['status' => $status])) ?>" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>Export CSV
        </a>
        <?php endif; ?>
        <?php if (Auth::isAdmin()): ?>
        <a href="<?= BASE_URL ?>/import?type=customers" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>Import CSV
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/customers/create" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>New Customer
        </a>
    </div>
</div>

<!-- Quick-filter tabs -->
<div style="display:flex;gap:.4rem;margin-bottom:.5rem;flex-wrap:wrap">
    <?php
    $tabBase = array_filter(['search' => htmlspecialchars_decode($search), 'sort' => $sort, 'dir' => $dir]);
    $tabs = [
        ''           => 'All',
        'active'     => 'Active',
        'inactive'   => 'Inactive',
    ];
    foreach ($tabs as $tabVal => $tabLabel):
        $isActive = ($type === '' && $status === $tabVal) || ($tabVal === '' && $type === '' && $status === '');
        // "All" tab is active only when both status and type are empty
        if ($tabVal === '') $isActive = ($type === '' && $status === '');
        $href = Utils::url('/customers', array_filter(array_merge($tabBase, ['status' => $tabVal])));
    ?>
    <a href="<?= $href ?>" class="btn btn-sm <?= $isActive ? 'btn-primary' : 'btn-secondary' ?>"><?= $tabLabel ?></a>
    <?php endforeach; ?>
    <?php
    $colleagueActive = ($type === 'colleague');
    $colleagueHref   = Utils::url('/customers', array_filter(array_merge($tabBase, ['type' => 'colleague'])));
    ?>
    <a href="<?= $colleagueHref ?>" class="btn btn-sm" style="<?= $colleagueActive ? 'background:#7c3aed;color:#fff;border-color:#7c3aed' : 'border-color:#c4b5fd;color:#7c3aed' ?>">Colleagues<?php if ($counts['colleagues'] > 0): ?> <span style="opacity:.8">(<?= (int)$counts['colleagues'] ?>)</span><?php endif; ?></a>
</div>

<!-- Search bar -->
<div class="card" style="margin-bottom:1rem">
    <form method="GET" action="<?= BASE_URL ?>/customers" class="filter-bar" role="search">
        <div class="search-input-wrap">
            <svg class="search-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="search" name="search" class="form-input"
                   placeholder="Search name, phone, email, city, VAT…"
                   value="<?= $search ?>" autocomplete="off" aria-label="Search customers">
        </div>
        <select name="status" class="form-select filter-select" aria-label="Filter by status" onchange="this.form.submit()">
            <option value=""         <?= $status===''         ?'selected':'' ?>>All Statuses</option>
            <option value="active"   <?= $status==='active'   ?'selected':'' ?>>Active</option>
            <option value="inactive" <?= $status==='inactive' ?'selected':'' ?>>Inactive</option>
        </select>
        <input type="hidden" name="sort" value="<?= Utils::e($sort) ?>">
        <input type="hidden" name="dir"  value="<?= Utils::e($dir) ?>">
        <?php if ($type !== ''): ?><input type="hidden" name="type" value="<?= Utils::e($type) ?>"><?php endif; ?>
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($search!==''||$status!==''||$type!==''): ?>
        <a href="<?= BASE_URL ?>/customers" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Table -->
<div class="card" style="margin-bottom:0">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:46px">#</th>
                    <th><a href="<?= cust_sortUrl('full_name') ?>" class="sort-lnk">Name <?= cust_sortIcon('full_name') ?></a></th>
                    <th class="hide-mobile">Type</th>
                    <th class="hide-t"><a href="<?= cust_sortUrl('city') ?>" class="sort-lnk">City <?= cust_sortIcon('city') ?></a></th>
                    <th class="hide-mobile"><a href="<?= cust_sortUrl('phone_mobile') ?>" class="sort-lnk">Phone <?= cust_sortIcon('phone_mobile') ?></a></th>
                    <th class="hide-t"><a href="<?= cust_sortUrl('email') ?>" class="sort-lnk">Email <?= cust_sortIcon('email') ?></a></th>
                    <th><a href="<?= cust_sortUrl('status') ?>" class="sort-lnk">Status <?= cust_sortIcon('status') ?></a></th>
                    <th style="width:130px;text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($customers)): ?>
                <tr><td colspan="8">
                    <div class="empty-big">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        <p><?= $search!=='' ? "No customers match \"<strong>".Utils::e(htmlspecialchars_decode($search))."</strong>\"." : 'No customers found.' ?></p>
                        <a href="<?= BASE_URL ?>/customers/create" class="btn btn-primary btn-sm">Add First Customer</a>
                    </div>
                </td></tr>
            <?php else: ?>
                <?php foreach ($customers as $c): ?>
                <tr<?= $c['client_type'] === 'colleague' ? ' style="border-left:3px solid #7c3aed"' : '' ?>>
                    <td style="color:var(--text-muted);font-size:.78rem"><?= $c['customer_id'] ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/customers/<?= $c['customer_id'] ?>" class="cust-name-link"><?= Utils::e($c['full_name']) ?></a>
                        <?php if ($c['vat_number']): ?><span class="vat-sub"><?= Utils::e($c['vat_number']) ?></span><?php endif; ?>
                    </td>
                    <td class="hide-mobile">
                        <?php if ($c['client_type'] === 'individual'): ?>
                        <span class="badge badge-gray" style="display:inline-flex;align-items:center;gap:.25rem">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Individual
                        </span>
                        <?php elseif ($c['client_type'] === 'company'): ?>
                        <span class="badge badge-red" style="display:inline-flex;align-items:center;gap:.25rem">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M3 21h18M3 7v14M21 7v14M3 7l9-4 9 4M9 21V12h6v9"/></svg>Company
                        </span>
                        <?php elseif ($c['client_type'] === 'colleague'): ?>
                        <span class="badge badge-purple" style="display:inline-flex;align-items:center;gap:.25rem">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>Colleague
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="hide-t" style="color:var(--text-secondary)">
                        <?= $c['city'] ? Utils::e($c['city']).($c['province']?' <small style="color:var(--text-muted)">('.Utils::e($c['province']).')</small>':'') : '<span style="color:var(--text-muted)">—</span>' ?>
                    </td>
                    <td class="hide-mobile">
                        <?php $ph = $c['phone_mobile'] ?: $c['phone_landline']; ?>
                        <?= $ph ? '<a href="tel:'.Utils::e($ph).'" class="ph-lnk">'.Utils::e($ph).'</a>' : '<span style="color:var(--text-muted)">—</span>' ?>
                    </td>
                    <td class="hide-t">
                        <?= $c['email'] ? '<a href="mailto:'.Utils::e($c['email']).'" class="em-lnk">'.Utils::e(Utils::truncate($c['email'],28)).'</a>' : '<span style="color:var(--text-muted)">—</span>' ?>
                    </td>
                    <td>
                        <?= $c['status']==='active' ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-gray">Inactive</span>' ?>
                    </td>
                    <td>
                        <div class="act-btns">
                            <a href="<?= BASE_URL ?>/customers/<?= $c['customer_id'] ?>" class="act-btn" title="View profile">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            <a href="<?= BASE_URL ?>/customers/<?= $c['customer_id'] ?>/edit" class="act-btn" title="Edit customer">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            <a href="<?= BASE_URL ?>/repairs/create?customer_id=<?= $c['customer_id'] ?>" class="act-btn act-btn-g" title="New repair">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                            </a>
                            <?php if (Auth::can('manager')): ?>
                            <form method="POST" action="<?= BASE_URL ?>/customers/<?= $c['customer_id'] ?>/delete"
                                  class="il-form" data-confirm="Deactivate &quot;<?= Utils::e(addslashes($c['full_name'])) ?>&quot;?">
                                <input type="hidden" name="csrf_token" value="<?= Utils::e(Auth::generateCSRFToken()) ?>">
                                <button type="submit" class="act-btn act-btn-d" title="Deactivate">
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
        $bp = array_filter(['search' => htmlspecialchars_decode($search), 'status' => $status, 'type' => $type, 'sort' => $sort, 'dir' => $dir]);
    ?>
    <div class="tbl-footer">
        <span class="tbl-footer-info">
            <?= number_format($pg['offset']+1) ?>–<?= number_format(min($pg['offset']+$pg['perPage'],$pg['total'])) ?>
            of <?= number_format($pg['total']) ?>
        </span>
        <nav class="pagination" aria-label="Pagination">
            <a href="<?= $pg['hasPrev'] ? Utils::url('/customers', array_merge($bp,['page'=>$pg['page']-1])) : '#' ?>"
               class="page-link <?= !$pg['hasPrev']?'disabled':'' ?>">&laquo;</a>
            <?php
            $s2 = max(1, $pg['page']-2); $e2 = min($pg['totalPages'], $pg['page']+2);
            if ($s2>1) echo '<span class="page-link disabled">…</span>';
            for ($p=$s2;$p<=$e2;$p++): ?>
            <a href="<?= Utils::url('/customers', array_merge($bp,['page'=>$p])) ?>"
               class="page-link <?= $p===$pg['page']?'current':'' ?>"><?= $p ?></a>
            <?php endfor;
            if ($e2<$pg['totalPages']) echo '<span class="page-link disabled">…</span>';
            ?>
            <a href="<?= $pg['hasNext'] ? Utils::url('/customers', array_merge($bp,['page'=>$pg['page']+1])) : '#' ?>"
               class="page-link <?= !$pg['hasNext']?'disabled':'' ?>">&raquo;</a>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
