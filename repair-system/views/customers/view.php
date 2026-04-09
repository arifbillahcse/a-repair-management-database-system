<?php
$pageTitle = Utils::e($customer['full_name']) . ' — Customer Profile';
require VIEWS_PATH . '/layouts/header.php';
?>

<style>
/* ── Customer profile page ─────────────────────────────────────────────── */
.profile-header{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.profile-avatar{width:56px;height:56px;border-radius:50%;background:var(--accent-dim);color:var(--accent);font-size:1.4rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.profile-meta{flex:1;min-width:0}
.profile-name{font-size:1.5rem;font-weight:700;margin:0 0 .3rem}
.profile-badges{display:flex;gap:.4rem;align-items:center;flex-wrap:wrap}
.profile-id{font-size:.75rem;color:var(--text-muted);background:var(--bg-tertiary);border:1px solid var(--border);border-radius:var(--radius-full);padding:.15rem .5rem}
.profile-actions{display:flex;gap:.5rem;flex-wrap:wrap}

.mini-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:1.5rem}
.mini-stat{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1rem 1.25rem}
.mini-stat-value{font-size:1.35rem;font-weight:700;line-height:1;margin-bottom:.3rem}
.mini-stat-label{font-size:.75rem;color:var(--text-secondary)}

.profile-grid{display:grid;grid-template-columns:1fr;gap:1.25rem}
@media(min-width:900px){.profile-grid{grid-template-columns:340px 1fr}}

.info-list{list-style:none;padding:0;margin:0}
.info-item{display:flex;gap:.75rem;padding:.65rem 1.25rem;border-bottom:1px solid var(--border)}
.info-item:last-child{border-bottom:none}
.info-icon{width:16px;height:16px;flex-shrink:0;margin-top:.15rem;stroke:var(--text-muted)}
.info-body{min-width:0}
.info-label{display:block;font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.15rem}
.info-value{color:var(--text-primary);font-size:.88rem;word-break:break-word}
.info-value a{color:var(--text-primary);text-decoration:none}
.info-value a:hover{color:var(--accent)}
.info-empty{color:var(--text-muted);font-style:italic}

.section-card{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:0}
.section-header{display:flex;align-items:center;justify-content:space-between;padding:.75rem 1.25rem;border-bottom:1px solid var(--border)}
.section-title{font-size:.9rem;font-weight:600;margin:0}

.timeline-status{display:inline-flex;align-items:center;gap:.35rem;font-size:.75rem;color:var(--text-muted)}
.timeline-dot{width:6px;height:6px;border-radius:50%;background:currentColor;flex-shrink:0}
</style>

<!-- ── Profile header ────────────────────────────────────────────────────── -->
<div class="profile-header">
    <div style="display:flex;gap:1rem;align-items:center">
        <div class="profile-avatar">
            <?= strtoupper(mb_substr($customer['full_name'], 0, 1)) ?>
        </div>
        <div class="profile-meta">
            <h1 class="profile-name"><?= Utils::e($customer['full_name']) ?></h1>
            <div class="profile-badges">
                <?= $customer['status'] === 'active'
                    ? '<span class="badge badge-green">Active</span>'
                    : '<span class="badge badge-gray">Inactive</span>' ?>
                <span class="badge badge-gray" style="text-transform:capitalize">
                    <?= Utils::e(CLIENT_TYPES[$customer['client_type']] ?? $customer['client_type']) ?>
                </span>
                <span class="profile-id">#<?= $customer['customer_id'] ?></span>
                <?php if ($customer['customer_since']): ?>
                <span class="profile-id">
                    Customer since <?= Utils::formatDate($customer['customer_since']) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="profile-actions">
        <a href="<?= BASE_URL ?>/repairs/create?customer_id=<?= $customer['customer_id'] ?>" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>New Repair
        </a>
        <a href="<?= BASE_URL ?>/customers/<?= $customer['customer_id'] ?>/edit" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>Edit
        </a>
        <?php if (Auth::isAdmin()): ?>
        <button type="button" class="btn btn-danger" id="deleteCustomerBtn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                <path d="M10 11v6"/><path d="M14 11v6"/>
                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
            </svg>Delete
        </button>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/customers" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
            </svg>Back
        </a>
    </div>
</div>

<?php if (Auth::isAdmin()): ?>
<!-- ── Delete confirmation modal ──────────────────────────────────────────── -->
<div id="deleteModal" style="display:none;position:fixed;inset:0;z-index:200;background:rgba(0,0,0,.55);align-items:center;justify-content:center">
    <div style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);padding:2rem;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.4)">
        <h2 style="margin:0 0 .75rem;font-size:1.1rem;color:var(--error)">Delete Customer?</h2>
        <p style="margin:0 0 .5rem;font-size:.9rem;color:var(--text-primary)">
            You are about to permanently delete <strong><?= Utils::e($customer['full_name']) ?></strong>.
        </p>
        <p style="margin:0 0 1.5rem;font-size:.85rem;color:var(--text-muted)">
            This will also delete all <strong><?= (int)$stats['total_repairs'] ?> repair(s)</strong> linked to this customer. This action cannot be undone.
        </p>
        <form method="POST" action="<?= BASE_URL ?>/customers/<?= $customer['customer_id'] ?>/delete">
            <input type="hidden" name="csrf_token" value="<?= Utils::e(Auth::generateCSRFToken()) ?>">
            <div style="display:flex;gap:.75rem;justify-content:flex-end">
                <button type="button" id="cancelDeleteBtn" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-danger">Yes, Delete Everything</button>
            </div>
        </form>
    </div>
</div>
<script>
(function () {
    const modal  = document.getElementById('deleteModal');
    const openBtn  = document.getElementById('deleteCustomerBtn');
    const closeBtn = document.getElementById('cancelDeleteBtn');
    openBtn.addEventListener('click',  function () { modal.style.display = 'flex'; });
    closeBtn.addEventListener('click', function () { modal.style.display = 'none'; });
    modal.addEventListener('click', function (e) { if (e.target === modal) modal.style.display = 'none'; });
})();
</script>
<?php endif; ?>

<!-- ── Mini stat cards ───────────────────────────────────────────────────── -->
<div class="mini-stats">
    <div class="mini-stat">
        <div class="mini-stat-value"><?= (int)$stats['total_repairs'] ?></div>
        <div class="mini-stat-label">Total Repairs</div>
    </div>
    <div class="mini-stat">
        <div class="mini-stat-value" style="color:var(--success)"><?= (int)$stats['completed_repairs'] ?></div>
        <div class="mini-stat-label">Completed</div>
    </div>
    <?php if ((int)$stats['active_repairs'] > 0): ?>
    <div class="mini-stat">
        <div class="mini-stat-value" style="color:var(--info)"><?= (int)$stats['active_repairs'] ?></div>
        <div class="mini-stat-label">Active Now</div>
    </div>
    <?php endif; ?>
    <div class="mini-stat">
        <div class="mini-stat-value"><?= Utils::formatCurrency($stats['total_billed']) ?></div>
        <div class="mini-stat-label">Total Billed</div>
    </div>
    <div class="mini-stat">
        <div class="mini-stat-value" style="color:var(--success)"><?= Utils::formatCurrency($stats['total_paid']) ?></div>
        <div class="mini-stat-label">Total Paid</div>
    </div>
    <?php if ((float)$stats['balance_due'] > 0): ?>
    <div class="mini-stat">
        <div class="mini-stat-value" style="color:var(--error)"><?= Utils::formatCurrency($stats['balance_due']) ?></div>
        <div class="mini-stat-label">Balance Due</div>
    </div>
    <?php endif; ?>
</div>

<!-- ── Main profile grid ─────────────────────────────────────────────────── -->
<div class="profile-grid">

    <!-- Left: contact info -->
    <div>
        <!-- Contact card -->
        <div class="section-card" style="margin-bottom:1.25rem">
            <div class="section-header">
                <h2 class="section-title">Contact Information</h2>
                <a href="<?= BASE_URL ?>/customers/<?= $customer['customer_id'] ?>/edit" class="btn btn-xs btn-secondary">Edit</a>
            </div>
            <ul class="info-list">

                <!-- Phone mobile -->
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 11.87 19a19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Mobile Phone</span>
                        <span class="info-value">
                            <?= $customer['phone_mobile']
                                ? '<a href="tel:'.Utils::e($customer['phone_mobile']).'">'.Utils::e($customer['phone_mobile']).'</a>'
                                : '<span class="info-empty">Not provided</span>' ?>
                        </span>
                    </div>
                </li>

                <!-- Landline -->
                <?php if ($customer['phone_landline']): ?>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 11.87 19a19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Landline</span>
                        <span class="info-value">
                            <a href="tel:<?= Utils::e($customer['phone_landline']) ?>"><?= Utils::e($customer['phone_landline']) ?></a>
                        </span>
                    </div>
                </li>
                <?php endif; ?>

                <!-- Email -->
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Email</span>
                        <span class="info-value">
                            <?= $customer['email']
                                ? '<a href="mailto:'.Utils::e($customer['email']).'">'.Utils::e($customer['email']).'</a>'
                                : '<span class="info-empty">Not provided</span>' ?>
                        </span>
                    </div>
                </li>

                <!-- Address -->
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Address</span>
                        <span class="info-value">
                            <?php
                            $addrParts = array_filter([
                                $customer['address'],
                                trim(($customer['postal_code'] ?? '') . ' ' . ($customer['city'] ?? '')),
                                $customer['province'],
                            ]);
                            echo $addrParts
                                ? Utils::e(implode(', ', $addrParts))
                                : '<span class="info-empty">Not provided</span>';
                            ?>
                        </span>
                    </div>
                </li>

            </ul>
        </div>

        <!-- Business info card -->
        <?php if ($customer['vat_number'] || $customer['tax_id']): ?>
        <div class="section-card" style="margin-bottom:1.25rem">
            <div class="section-header">
                <h2 class="section-title">Business Information</h2>
            </div>
            <ul class="info-list">
                <?php if ($customer['vat_number']): ?>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">VAT Number</span>
                        <span class="info-value" style="font-family:var(--font-mono)"><?= Utils::e($customer['vat_number']) ?></span>
                    </div>
                </li>
                <?php endif; ?>
                <?php if ($customer['tax_id']): ?>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Fiscal Code</span>
                        <span class="info-value" style="font-family:var(--font-mono)"><?= Utils::e($customer['tax_id']) ?></span>
                    </div>
                </li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Notes card -->
        <?php if ($customer['notes']): ?>
        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">Notes</h2>
            </div>
            <div style="padding:1rem 1.25rem;font-size:.86rem;color:var(--text-secondary);line-height:1.6;white-space:pre-wrap"><?= Utils::e($customer['notes']) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right: repairs + invoices -->
    <div style="display:flex;flex-direction:column;gap:1.25rem">

        <!-- Recent repairs -->
        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">
                    Repair History
                    <?php if ((int)$stats['total_repairs'] > 0): ?>
                    <span class="badge badge-gray" style="margin-left:.4rem"><?= (int)$stats['total_repairs'] ?></span>
                    <?php endif; ?>
                </h2>
                <a href="<?= BASE_URL ?>/repairs?customer_id=<?= $customer['customer_id'] ?>" class="btn btn-xs btn-secondary">View All</a>
            </div>

            <?php if (empty($repairs)): ?>
            <div class="empty-state" style="padding:2rem">No repairs on record yet.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Device</th>
                            <th>Date In</th>
                            <th>Status</th>
                            <th>Days</th>
                            <th style="text-align:right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($repairs as $r): ?>
                    <tr>
                        <td>
                            <a href="<?= BASE_URL ?>/repairs/<?= $r['repair_id'] ?>"
                               style="font-weight:600;color:var(--text-primary);text-decoration:none"
                               onmouseover="this.style.color='var(--accent)'"
                               onmouseout="this.style.color='var(--text-primary)'">
                                #<?= $r['repair_id'] ?>
                            </a>
                        </td>
                        <td style="max-width:160px">
                            <span title="<?= Utils::e($r['device_model']) ?>">
                                <?= Utils::e(Utils::truncate($r['device_model'], 22)) ?>
                            </span>
                            <?php if ($r['device_serial_number']): ?>
                            <div style="font-size:.72rem;color:var(--text-muted)"><?= Utils::e($r['device_serial_number']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="white-space:nowrap"><?= Utils::formatDate($r['date_in']) ?></td>
                        <td>
                            <span class="badge <?= REPAIR_STATUS_CLASS[$r['status']] ?? 'badge-gray' ?>">
                                <?= Utils::e(REPAIR_STATUS[$r['status']] ?? $r['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php $days = (int)$r['days_in_lab']; ?>
                            <span style="color:<?= $days > 14 ? 'var(--error)' : ($days > 7 ? 'var(--warning)' : 'var(--text-secondary)') ?>">
                                <?= $days ?>d
                            </span>
                        </td>
                        <td style="text-align:right;font-size:.83rem">
                            <?= $r['actual_amount'] ? Utils::formatCurrency($r['actual_amount']) : ($r['estimate_amount'] ? '<span style="color:var(--text-muted)">~'.Utils::formatCurrency($r['estimate_amount']).'</span>' : '—') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ((int)$stats['total_repairs'] > count($repairs)): ?>
            <div style="padding:.6rem 1rem;border-top:1px solid var(--border);text-align:center">
                <a href="<?= BASE_URL ?>/repairs?customer_id=<?= $customer['customer_id'] ?>"
                   style="font-size:.8rem;color:var(--accent)">
                    View all <?= (int)$stats['total_repairs'] ?> repairs →
                </a>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Invoices -->
        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">Invoices</h2>
                <a href="<?= BASE_URL ?>/invoices?customer_id=<?= $customer['customer_id'] ?>" class="btn btn-xs btn-secondary">View All</a>
            </div>

            <?php if (empty($invoices)): ?>
            <div class="empty-state" style="padding:2rem">No invoices yet.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th style="text-align:right">Total</th>
                            <th style="text-align:right">Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach (array_slice($invoices, 0, 5) as $inv): ?>
                    <tr>
                        <td>
                            <a href="<?= BASE_URL ?>/invoices/<?= $inv['invoice_id'] ?>"
                               style="font-weight:600;color:var(--text-primary);text-decoration:none"
                               onmouseover="this.style.color='var(--accent)'"
                               onmouseout="this.style.color='var(--text-primary)'">
                                <?= Utils::e($inv['invoice_number']) ?>
                            </a>
                        </td>
                        <td><?= Utils::formatDate($inv['invoice_date']) ?></td>
                        <td>
                            <span class="badge <?= INVOICE_STATUS_CLASS[$inv['status']] ?? 'badge-gray' ?>">
                                <?= Utils::e(INVOICE_STATUS[$inv['status']] ?? $inv['status']) ?>
                            </span>
                        </td>
                        <td style="text-align:right"><?= Utils::formatCurrency($inv['total_amount'] ?? 0) ?></td>
                        <td style="text-align:right;color:var(--success)"><?= Utils::formatCurrency($inv['amount_paid'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- System info (created/updated) -->
        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">Record Information</h2>
            </div>
            <ul class="info-list">
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Created</span>
                        <span class="info-value"><?= Utils::formatDateTime($customer['created_at'] ?? '') ?></span>
                    </div>
                </li>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Last Modified</span>
                        <span class="info-value"><?= Utils::formatDateTime($customer['updated_at'] ?? $customer['created_at'] ?? '') ?></span>
                    </div>
                </li>
                <?php if ($stats['first_repair']): ?>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">First Repair</span>
                        <span class="info-value"><?= Utils::formatDate($stats['first_repair']) ?></span>
                    </div>
                </li>
                <?php endif; ?>
                <?php if ($stats['last_repair'] && $stats['total_repairs'] > 1): ?>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Last Repair</span>
                        <span class="info-value"><?= Utils::formatDate($stats['last_repair']) ?></span>
                    </div>
                </li>
                <?php endif; ?>
            </ul>
        </div>

    </div><!-- /right column -->
</div><!-- /profile-grid -->

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
