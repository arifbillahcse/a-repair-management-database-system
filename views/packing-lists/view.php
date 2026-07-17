<?php
$pageTitle = 'Packing List #' . $pl['pl_number'];
require VIEWS_PATH . '/layouts/header.php';

$accountLabel = ['in_conto' => 'In conto', 'a_saldo' => 'A saldo'][$pl['account_type']] ?? '—';
?>
<style>
.pl-detail-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;margin-bottom:1.5rem}
@media(max-width:1100px){.pl-detail-grid{grid-template-columns:1fr 1fr}}
@media(max-width:700px){.pl-detail-grid{grid-template-columns:1fr}}
.info-list{list-style:none;padding:0;margin:0}
.info-item{display:flex;gap:.75rem;padding:.6rem 1.25rem;border-bottom:1px solid var(--border)}
.info-item:last-child{border-bottom:none}
.info-label{font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:.15rem}
.info-value{font-size:.88rem;color:var(--text-primary)}
.pl-items-table{width:100%;border-collapse:collapse}
.pl-items-table th{background:var(--bg-tertiary);font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:var(--text-secondary);padding:.6rem 1rem;border-bottom:1px solid var(--border);text-align:left}
.pl-items-table td{padding:.65rem 1rem;border-bottom:1px solid var(--border);font-size:.86rem}
.pl-items-table tr:last-child td{border-bottom:none}
.pl-items-table .qty-col{width:120px}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Packing List #<?= Utils::e($pl['pl_number']) ?></h1>
        <p class="page-subtitle"><?= Utils::formatDate($pl['pl_date']) ?> · Documento di Trasporto</p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <a href="<?= BASE_URL ?>/packing-lists/<?= $pl['pl_id'] ?>/print" target="_blank" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>
            </svg>Print
        </a>
        <a href="<?= BASE_URL ?>/packing-lists/<?= $pl['pl_id'] ?>/edit" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>Edit
        </a>
        <a href="<?= BASE_URL ?>/packing-lists" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
            </svg>Back
        </a>
    </div>
</div>

<div class="pl-detail-grid">
    <div class="card">
        <div class="card-header"><h2 class="card-title">Cedente (Sender)</h2></div>
        <ul class="info-list">
            <li class="info-item"><div><span class="info-label">Company</span><span class="info-value" style="font-weight:600"><?= Utils::e($pl['company_name'] ?: '—') ?></span></div></li>
            <?php if (!empty($pl['company_address'])): ?><li class="info-item"><div><span class="info-label">Address</span><span class="info-value" style="white-space:pre-wrap"><?= Utils::e($pl['company_address']) ?></span></div></li><?php endif; ?>
            <?php if (!empty($pl['company_vat'])): ?><li class="info-item"><div><span class="info-label">P.IVA</span><span class="info-value" style="font-family:var(--font-mono)"><?= Utils::e($pl['company_vat']) ?></span></div></li><?php endif; ?>
            <?php if (!empty($pl['company_tax_id'])): ?><li class="info-item"><div><span class="info-label">Cod. Fiscale</span><span class="info-value" style="font-family:var(--font-mono)"><?= Utils::e($pl['company_tax_id']) ?></span></div></li><?php endif; ?>
        </ul>
    </div>
    <div class="card">
        <div class="card-header"><h2 class="card-title">Cessionario (Recipient)</h2></div>
        <ul class="info-list">
            <li class="info-item"><div><span class="info-label">Name</span><span class="info-value" style="font-weight:600"><?= Utils::e($pl['customer_name'] ?: '—') ?></span></div></li>
            <?php if (!empty($pl['customer_address'])): ?><li class="info-item"><div><span class="info-label">Address</span><span class="info-value"><?= Utils::e($pl['customer_address']) ?></span></div></li><?php endif; ?>
            <?php if (!empty($pl['customer_vat'])): ?><li class="info-item"><div><span class="info-label">VAT / C.F.</span><span class="info-value" style="font-family:var(--font-mono)"><?= Utils::e($pl['customer_vat']) ?></span></div></li><?php endif; ?>
            <?php if (!empty($pl['destination'])): ?><li class="info-item"><div><span class="info-label">Destinazione</span><span class="info-value"><?= Utils::e($pl['destination']) ?></span></div></li><?php endif; ?>
        </ul>
    </div>
    <div class="card">
        <div class="card-header"><h2 class="card-title">Trasporto</h2></div>
        <ul class="info-list">
            <li class="info-item"><div><span class="info-label">Causale</span><span class="info-value"><?= Utils::e($pl['causale'] ?: '—') ?></span></div></li>
            <li class="info-item"><div><span class="info-label">A mezzo</span><span class="info-value" style="text-transform:capitalize"><?= Utils::e($pl['transport_by']) ?></span></div></li>
            <?php if (!empty($pl['order_number'])): ?><li class="info-item"><div><span class="info-label">N° ordine</span><span class="info-value"><?= Utils::e($pl['order_number']) ?><?= !empty($pl['order_date']) ? ' · ' . Utils::formatDate($pl['order_date']) : '' ?></span></div></li><?php endif; ?>
            <li class="info-item"><div><span class="info-label">Conto</span><span class="info-value"><?= Utils::e($accountLabel) ?></span></div></li>
            <?php if (!empty($pl['carrier'])): ?><li class="info-item"><div><span class="info-label">Trasportatore</span><span class="info-value"><?= Utils::e($pl['carrier']) ?></span></div></li><?php endif; ?>
            <?php if (!empty($pl['n_colli']) || !empty($pl['peso_kg']) || !empty($pl['porto'])): ?>
            <li class="info-item"><div><span class="info-label">Colli / Peso / Porto</span><span class="info-value"><?= Utils::e(($pl['n_colli'] ?: '—') . ' · ' . ($pl['peso_kg'] ?: '—') . ' · ' . ($pl['porto'] ?: '—')) ?></span></div></li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<div class="card" style="margin-bottom:1.5rem">
    <div class="card-header"><h2 class="card-title">Descrizione dei beni</h2></div>
    <?php if (empty($pl['items'])): ?>
    <div class="empty-state" style="padding:1.5rem">No items recorded.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="pl-items-table">
            <thead><tr><th class="qty-col">Quantità</th><th>Descrizione (natura e qualità)</th></tr></thead>
            <tbody>
            <?php foreach ($pl['items'] as $item): ?>
            <tr>
                <td><?= Utils::e($item['quantity'] ?: '—') ?></td>
                <td><?= Utils::e($item['description']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($pl['notes'])): ?>
<div class="card">
    <div class="card-header"><h2 class="card-title">Annotazioni</h2></div>
    <div style="padding:1rem 1.25rem;font-size:.86rem;color:var(--text-secondary);line-height:1.7;white-space:pre-wrap"><?= Utils::e($pl['notes']) ?></div>
</div>
<?php endif; ?>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
