<?php
$pageTitle = 'Packing Lists';
require VIEWS_PATH . '/layouts/header.php';

$search   = Utils::e($_GET['search']    ?? '');
$dateFrom = Utils::e($_GET['date_from'] ?? '');
$dateTo   = Utils::e($_GET['date_to']   ?? '');
$pg       = $pagination;
?>
<style>
.pl-id-link{font-weight:700;color:var(--text-primary);text-decoration:none}
.pl-id-link:hover{color:var(--accent)}
.act-btns{display:flex;gap:.2rem;justify-content:flex-end}
.act-btn{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:var(--radius);background:none;border:1px solid transparent;color:var(--text-muted);cursor:pointer;text-decoration:none;transition:all var(--transition)}
.act-btn svg{width:14px;height:14px}
.act-btn:hover{background:var(--bg-tertiary);color:var(--text-primary);border-color:var(--border)}
.act-btn-d:hover{background:var(--error-bg);color:var(--error);border-color:var(--error)}
.act-btn-p:hover{background:var(--accent-dim);color:var(--accent);border-color:var(--accent)}
.il-form{display:inline;margin:0;padding:0}
.tbl-footer{display:flex;align-items:center;justify-content:space-between;padding:.6rem 1rem;border-top:1px solid var(--border);flex-wrap:wrap;gap:.5rem}
.tbl-footer-info{font-size:.78rem;color:var(--text-secondary)}
.filter-bar{display:flex;gap:.5rem;padding:.75rem 1rem;flex-wrap:wrap;align-items:center;border-bottom:1px solid var(--border)}
.filter-bar .search-input-wrap{flex:1;min-width:200px}
.empty-big{display:flex;flex-direction:column;align-items:center;gap:.75rem;padding:3rem 1rem;text-align:center}
.empty-big svg{width:48px;height:48px;stroke:var(--text-muted)}
.empty-big p{color:var(--text-secondary)}
.hide-mobile{display:none}
@media(min-width:700px){.hide-mobile{display:table-cell}}
.hide-t{display:none}
@media(min-width:1000px){.hide-t{display:table-cell}}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Packing Lists</h1>
        <p class="page-subtitle"><?= number_format($pagination['total']) ?> total · Documento di Trasporto</p>
    </div>
    <a href="<?= BASE_URL ?>/packing-lists/create" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>New Packing List
    </a>
</div>

<div class="card" style="margin-bottom:0">

    <form method="GET" action="<?= BASE_URL ?>/packing-lists" class="filter-bar" role="search">
        <div class="search-input-wrap">
            <svg class="search-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="search" name="search" class="form-input"
                   placeholder="Search number, recipient, destination…"
                   value="<?= $search ?>" autocomplete="off">
        </div>
        <input type="date" name="date_from" class="form-input" style="width:auto" value="<?= $dateFrom ?>" title="From date">
        <input type="date" name="date_to"   class="form-input" style="width:auto" value="<?= $dateTo   ?>" title="To date">
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($search !== '' || $dateFrom !== '' || $dateTo !== ''): ?>
        <a href="<?= BASE_URL ?>/packing-lists" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:60px">N°</th>
                    <th>Recipient</th>
                    <th class="hide-mobile">Company</th>
                    <th class="hide-t">First item</th>
                    <th class="hide-mobile" style="text-align:right">Items</th>
                    <th class="hide-t">Date</th>
                    <th style="width:120px;text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($packingLists)): ?>
                <tr><td colspan="7">
                    <div class="empty-big">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
                        </svg>
                        <p>No packing lists yet.</p>
                        <a href="<?= BASE_URL ?>/packing-lists/create" class="btn btn-primary">Create First Packing List</a>
                    </div>
                </td></tr>
            <?php else: ?>
                <?php foreach ($packingLists as $pl): ?>
                <tr>
                    <td>
                        <a href="<?= BASE_URL ?>/packing-lists/<?= $pl['pl_id'] ?>" class="pl-id-link">#<?= $pl['pl_number'] ?></a>
                    </td>
                    <td style="font-size:.86rem;font-weight:500"><?= Utils::e($pl['customer_name'] ?: '—') ?></td>
                    <td class="hide-mobile" style="font-size:.82rem;color:var(--text-secondary)"><?= Utils::e($pl['company_name'] ?: '—') ?></td>
                    <td class="hide-t" style="font-size:.82rem;color:var(--text-secondary);max-width:220px">
                        <?= Utils::e(Utils::truncate($pl['first_desc'] ?? '', 40)) ?>
                    </td>
                    <td class="hide-mobile" style="text-align:right;font-size:.84rem"><?= (int)$pl['item_count'] ?></td>
                    <td class="hide-t" style="font-size:.8rem;color:var(--text-secondary)"><?= Utils::formatDate($pl['pl_date']) ?></td>
                    <td>
                        <div class="act-btns">
                            <a href="<?= BASE_URL ?>/packing-lists/<?= $pl['pl_id'] ?>" class="act-btn" title="View">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            <a href="<?= BASE_URL ?>/packing-lists/<?= $pl['pl_id'] ?>/edit" class="act-btn" title="Edit">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            </a>
                            <a href="<?= BASE_URL ?>/packing-lists/<?= $pl['pl_id'] ?>/print" target="_blank" class="act-btn act-btn-p" title="Print">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                            </a>
                            <?php if (Auth::can('manager')): ?>
                            <form method="POST" action="<?= BASE_URL ?>/packing-lists/<?= $pl['pl_id'] ?>/delete"
                                  class="il-form" data-confirm="Delete Packing List #<?= $pl['pl_number'] ?>? This cannot be undone.">
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

    <?php if ($pg['totalPages'] > 1): ?>
    <div class="tbl-footer">
        <span class="tbl-footer-info">
            <?= number_format($pg['offset'] + 1) ?>–<?= number_format(min($pg['offset'] + $pg['perPage'], $pg['total'])) ?>
            of <?= number_format($pg['total']) ?>
        </span>
        <nav class="pagination" aria-label="Pagination">
            <?php $bp = array_filter(['search' => htmlspecialchars_decode($search), 'date_from' => $dateFrom, 'date_to' => $dateTo]); ?>
            <a href="<?= $pg['hasPrev'] ? Utils::url('/packing-lists', array_merge($bp, ['page' => $pg['page'] - 1])) : '#' ?>"
               class="page-link <?= !$pg['hasPrev'] ? 'disabled' : '' ?>">&laquo;</a>
            <?php
            $s = max(1, $pg['page'] - 2);
            $e = min($pg['totalPages'], $pg['page'] + 2);
            if ($s > 1) echo '<span class="page-link disabled">…</span>';
            for ($p = $s; $p <= $e; $p++): ?>
            <a href="<?= Utils::url('/packing-lists', array_merge($bp, ['page' => $p])) ?>"
               class="page-link <?= $p === $pg['page'] ? 'current' : '' ?>"><?= $p ?></a>
            <?php endfor;
            if ($e < $pg['totalPages']) echo '<span class="page-link disabled">…</span>';
            ?>
            <a href="<?= $pg['hasNext'] ? Utils::url('/packing-lists', array_merge($bp, ['page' => $pg['page'] + 1])) : '#' ?>"
               class="page-link <?= !$pg['hasNext'] ? 'disabled' : '' ?>">&raquo;</a>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
