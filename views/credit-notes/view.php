<?php
$pageTitle = 'Credit Note #' . $cn['cn_number'];
require VIEWS_PATH . '/layouts/header.php';
?>
<style>
.cn-detail-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;margin-bottom:1.5rem}
@media(max-width:1100px){.cn-detail-grid{grid-template-columns:1fr 1fr}}
@media(max-width:700px){.cn-detail-grid{grid-template-columns:1fr}}
.info-list{list-style:none;padding:0;margin:0}
.info-item{display:flex;gap:.75rem;padding:.6rem 1.25rem;border-bottom:1px solid var(--border)}
.info-item:last-child{border-bottom:none}
.info-label{font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:.15rem}
.info-value{font-size:.88rem;color:var(--text-primary)}
.cn-items-table{width:100%;border-collapse:collapse}
.cn-items-table th{background:var(--bg-tertiary);font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:var(--text-secondary);padding:.6rem 1rem;border-bottom:1px solid var(--border)}
.cn-items-table th.r{text-align:right}
.cn-items-table td{padding:.65rem 1rem;border-bottom:1px solid var(--border);font-size:.86rem}
.cn-items-table tr:last-child td{border-bottom:none}
.cn-items-table .total-row td{font-weight:700;background:var(--bg-tertiary)}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Credit Note #<?= Utils::e($cn['cn_number']) ?></h1>
        <p class="page-subtitle"><?= Utils::formatDate($cn['cn_date']) ?></p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <a href="<?= BASE_URL ?>/credit-notes/<?= $cn['cn_id'] ?>/print" target="_blank" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>
            </svg>Print
        </a>
        <a href="<?= BASE_URL ?>/credit-notes/<?= $cn['cn_id'] ?>/edit" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>Edit
        </a>
        <a href="<?= BASE_URL ?>/credit-notes" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
            </svg>Back
        </a>
    </div>
</div>

<div class="cn-detail-grid">
    <div class="card">
        <div class="card-header"><h2 class="card-title">Credit Note Details</h2></div>
        <ul class="info-list">
            <li class="info-item">
                <div><span class="info-label">CN Number</span><span class="info-value" style="font-size:1.1rem;font-weight:700">#<?= Utils::e($cn['cn_number']) ?></span></div>
            </li>
            <li class="info-item">
                <div><span class="info-label">Date</span><span class="info-value"><?= Utils::formatDate($cn['cn_date']) ?></span></div>
            </li>
        </ul>
    </div>
    <div class="card">
        <div class="card-header"><h2 class="card-title">Company / Issuer</h2></div>
        <ul class="info-list">
            <li class="info-item">
                <div><span class="info-label">Company Name</span><span class="info-value" style="font-weight:600"><?= Utils::e($cn['company_name'] ?: '—') ?></span></div>
            </li>
            <?php if (!empty($cn['company_address'])): ?>
            <li class="info-item">
                <div><span class="info-label">Address</span><span class="info-value" style="white-space:pre-wrap"><?= Utils::e($cn['company_address']) ?></span></div>
            </li>
            <?php endif; ?>
            <?php if (!empty($cn['company_phone'])): ?>
            <li class="info-item">
                <div><span class="info-label">Phone</span><span class="info-value"><?= Utils::e($cn['company_phone']) ?></span></div>
            </li>
            <?php endif; ?>
            <?php if (!empty($cn['company_email'])): ?>
            <li class="info-item">
                <div><span class="info-label">Email</span><span class="info-value"><?= Utils::e($cn['company_email']) ?></span></div>
            </li>
            <?php endif; ?>
            <?php if (!empty($cn['company_vat'])): ?>
            <li class="info-item">
                <div><span class="info-label">VAT N°</span><span class="info-value" style="font-family:var(--font-mono)"><?= Utils::e($cn['company_vat']) ?></span></div>
            </li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="card">
        <div class="card-header"><h2 class="card-title">Customer Details</h2></div>
        <ul class="info-list">
            <li class="info-item">
                <div><span class="info-label">Customer Name</span><span class="info-value" style="font-weight:600"><?= Utils::e($cn['customer_name'] ?: '—') ?></span></div>
            </li>
            <?php if (!empty($cn['customer_address'])): ?>
            <li class="info-item">
                <div><span class="info-label">Address</span><span class="info-value"><?= Utils::e($cn['customer_address']) ?></span></div>
            </li>
            <?php endif; ?>
            <?php if (!empty($cn['customer_vat'])): ?>
            <li class="info-item">
                <div><span class="info-label">VAT N°</span><span class="info-value" style="font-family:var(--font-mono)"><?= Utils::e($cn['customer_vat']) ?></span></div>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<!-- Line items -->
<div class="card" style="margin-bottom:1.5rem">
    <div class="card-header"><h2 class="card-title">Line Items</h2></div>
    <?php if (empty($cn['items'])): ?>
    <div class="empty-state" style="padding:1.5rem">No line items recorded.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="cn-items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="r">Basic Amount</th>
                    <th class="r">VAT Amount</th>
                    <th class="r">Net Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($cn['items'] as $item): ?>
            <tr>
                <td><?= Utils::e($item['description']) ?></td>
                <td style="text-align:right"><?= Utils::formatCurrency((float)$item['basic_amount']) ?></td>
                <td style="text-align:right"><?= Utils::formatCurrency((float)$item['vat_amount']) ?></td>
                <td style="text-align:right;font-weight:600"><?= Utils::formatCurrency((float)$item['net_amount']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td><strong>Total</strong></td>
                    <td style="text-align:right"><?= Utils::formatCurrency((float)$cn['total_basic']) ?></td>
                    <td style="text-align:right"><?= Utils::formatCurrency((float)$cn['total_vat']) ?></td>
                    <td style="text-align:right;color:var(--accent)"><?= Utils::formatCurrency((float)$cn['total_net']) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($cn['note'])): ?>
<div class="card">
    <div class="card-header"><h2 class="card-title">Note</h2></div>
    <div style="padding:1rem 1.25rem;font-size:.86rem;color:var(--text-secondary);line-height:1.7;white-space:pre-wrap"><?= Utils::e($cn['note']) ?></div>
</div>
<?php endif; ?>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
