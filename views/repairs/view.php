<?php
$pageTitle = 'Repair #' . $repair['repair_id'] . ' — ' . Utils::e($repair['device_model'] ?? '');
require VIEWS_PATH . '/layouts/header.php';

$photos     = $repair['photos'] ?? [];
$statusFlow = REPAIR_STATUS_FLOW[$repair['status']] ?? [];
$days       = (int)($repair['days_in_lab'] ?? 0);

// Status timeline order
$allStatuses = array_keys(REPAIR_STATUS);
$currentIdx  = array_search($repair['status'], $allStatuses, true);
?>
<style>
.rep-header{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.rep-id{font-size:2rem;font-weight:800;color:var(--text-primary);line-height:1}
.rep-sub{font-size:.88rem;color:var(--text-secondary);margin:.25rem 0 0}
.rep-actions{display:flex;gap:.5rem;flex-wrap:wrap}

.rep-grid{display:grid;grid-template-columns:1fr;gap:1.25rem}
@media(min-width:960px){.rep-grid{grid-template-columns:320px 1fr}}

.section-card{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;margin-bottom:0}
.section-header{display:flex;align-items:center;justify-content:space-between;padding:.75rem 1.25rem;border-bottom:1px solid var(--border)}
.section-title{font-size:.9rem;font-weight:600;margin:0}

.info-list{list-style:none;padding:0;margin:0}
.info-item{display:flex;gap:.75rem;padding:.6rem 1.25rem;border-bottom:1px solid var(--border)}
.info-item:last-child{border-bottom:none}
.info-icon{width:15px;height:15px;flex-shrink:0;margin-top:.15rem;stroke:var(--text-muted)}
.info-body{min-width:0}
.info-label{display:block;font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.1rem}
.info-value{color:var(--text-primary);font-size:.86rem;word-break:break-word;white-space:pre-wrap}
.info-empty{color:var(--text-muted);font-style:italic}

/* Status timeline */
.status-timeline{display:flex;flex-wrap:wrap;gap:.5rem;padding:1rem 1.25rem}
.st-step{display:flex;align-items:center;gap:.4rem;font-size:.78rem;color:var(--text-muted)}
.st-dot{width:10px;height:10px;border-radius:50%;background:var(--bg-tertiary);border:2px solid var(--border);flex-shrink:0}
.st-step.done .st-dot{background:var(--success);border-color:var(--success)}
.st-step.current .st-dot{background:var(--accent);border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-dim)}
.st-step.done{color:var(--success)}
.st-step.current{color:var(--accent);font-weight:600}
.st-sep{width:24px;height:1px;background:var(--border)}
.st-step.done ~ .st-step .st-sep,.st-step.current ~ .st-sep{background:var(--border)}

/* Status update form */
.status-update-wrap{padding:1rem 1.25rem;background:var(--bg-tertiary);border-top:1px solid var(--border)}
.status-btn-group{display:flex;gap:.5rem;flex-wrap:wrap}

/* Photo gallery */
.photo-gallery{display:flex;gap:.5rem;flex-wrap:wrap;padding:1rem 1.25rem}
.gallery-item{width:100px;height:100px;border-radius:var(--radius);overflow:hidden;border:1px solid var(--border);cursor:pointer;transition:border-color var(--transition)}
.gallery-item:hover{border-color:var(--accent)}
.gallery-item img{width:100%;height:100%;object-fit:cover;display:block}

/* Lightbox */
#lightbox{display:none;position:fixed;inset:0;background:rgba(0,0,0,.9);z-index:1000;align-items:center;justify-content:center}
#lightbox.active{display:flex}
#lightboxImg{max-width:90vw;max-height:90vh;border-radius:var(--radius-lg);box-shadow:0 8px 32px rgba(0,0,0,.5)}
#lightboxClose{position:absolute;top:1rem;right:1.25rem;color:#fff;font-size:2rem;cursor:pointer;line-height:1;background:none;border:none;opacity:.8}
#lightboxClose:hover{opacity:1}

/* QR code */
.qr-wrap{display:flex;flex-direction:column;align-items:center;gap:.5rem;padding:1rem 1.25rem}
.qr-code{width:140px;height:140px;border-radius:var(--radius);background:#fff;padding:6px;display:flex;align-items:center;justify-content:center}
.qr-code img{width:100%;height:auto;display:block}
.qr-value{font-size:.7rem;color:var(--text-muted);font-family:var(--font-mono)}

/* Amount summary */
.amount-row{display:flex;justify-content:space-between;align-items:baseline;padding:.5rem 1.25rem;border-bottom:1px solid var(--border);font-size:.86rem}
.amount-row:last-child{border-bottom:none;font-weight:700;font-size:.95rem;padding-top:.75rem}
.amount-label{color:var(--text-secondary)}

.priority-badge{display:inline-block;padding:.15rem .5rem;border-radius:var(--radius-full);font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em}
.priority-low{background:var(--bg-tertiary);color:var(--text-muted)}
.priority-normal{background:var(--info-bg,#1e3a4a);color:var(--info,#67b3dd)}
.priority-high{background:var(--warning-bg,#3a2e1e);color:var(--warning,#f59e0b)}
.priority-urgent{background:var(--error-bg);color:var(--error)}
</style>

<!-- ── Repair header ──────────────────────────────────────────────────────── -->
<div class="rep-header">
    <div>
        <div style="display:flex;align-items:baseline;gap:.75rem;flex-wrap:wrap">
            <span class="rep-id">#<?= $repair['repair_id'] ?></span>
            <span class="badge <?= REPAIR_STATUS_CLASS[$repair['status']] ?? 'badge-gray' ?>" style="font-size:.8rem">
                <?= Utils::e(REPAIR_STATUS[$repair['status']] ?? $repair['status']) ?>
            </span>
            <?php $pr = $repair['priority'] ?? 'normal'; ?>
            <span class="priority-badge priority-<?= Utils::e($pr) ?>"><?= Utils::e($pr) ?></span>
        </div>
        <p class="rep-sub">
            <?= Utils::e($repair['device_brand'] ?? '') ?>
            <?= Utils::e($repair['device_model'] ?? '') ?>
            <?php if (!empty($repair['device_serial_number'])): ?>
            &nbsp;·&nbsp; <span style="font-family:var(--font-mono)"><?= Utils::e($repair['device_serial_number']) ?></span>
            <?php endif; ?>
        </p>
        <p class="rep-sub" style="margin-top:.2rem">
            In: <?= Utils::formatDate($repair['date_in']) ?>
            <?php if (!empty($repair['date_expected_out'])): ?>
            &nbsp;·&nbsp; Expected: <?= Utils::formatDate($repair['date_expected_out']) ?>
            <?php endif; ?>
            &nbsp;·&nbsp;
            <span style="color:<?= $days > 14 ? 'var(--error)' : ($days > 7 ? 'var(--warning)' : 'var(--text-muted)') ?>">
                <?= $days ?> day<?= $days !== 1 ? 's' : '' ?> in lab
            </span>
        </p>
    </div>
    <div class="rep-actions">
        <?php if (!empty($repair['customer_id'])): ?>
        <a href="<?= BASE_URL ?>/repairs/create?customer_id=<?= $repair['customer_id'] ?>" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>New Repair
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/repairs/<?= $repair['repair_id'] ?>/print" target="_blank" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polyline points="6 9 6 2 18 2 18 9"/>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
            </svg>Print
        </a>
        <a href="<?= BASE_URL ?>/repairs/<?= $repair['repair_id'] ?>/edit" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>Edit
        </a>
        <a href="<?= BASE_URL ?>/repairs" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
            </svg>Back
        </a>
    </div>
</div>

<!-- ── Status timeline ────────────────────────────────────────────────────── -->
<div class="section-card" style="margin-bottom:1.25rem">
    <div class="section-header">
        <h2 class="section-title">Status Timeline</h2>
    </div>
    <div class="status-timeline">
        <?php foreach ($allStatuses as $idx => $st):
            $isDone    = $idx < $currentIdx;
            $isCurrent = $st === $repair['status'];
        ?>
        <?php if ($idx > 0): ?><div class="st-sep"></div><?php endif; ?>
        <div class="st-step <?= $isDone ? 'done' : ($isCurrent ? 'current' : '') ?>">
            <div class="st-dot"></div>
            <?= Utils::e(REPAIR_STATUS[$st] ?? $st) ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Quick status update -->
    <?php if (!empty($statusFlow)): ?>
    <div class="status-update-wrap">
        <p style="font-size:.75rem;color:var(--text-muted);margin:0 0 .6rem">Move to:</p>
        <div class="status-btn-group">
            <?php foreach ($statusFlow as $nextSt): ?>
            <form method="POST" action="<?= BASE_URL ?>/repairs/<?= $repair['repair_id'] ?>/status" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= Utils::e(Auth::generateCSRFToken()) ?>">
                <input type="hidden" name="status" value="<?= Utils::e($nextSt) ?>">
                <button type="submit" class="btn btn-sm <?= in_array($nextSt, ['completed', 'collected'], true) ? 'btn-primary' : 'btn-secondary' ?>">
                    <?= Utils::e(REPAIR_STATUS[$nextSt] ?? $nextSt) ?>
                </button>
            </form>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ── Main grid ─────────────────────────────────────────────────────────── -->
<div class="rep-grid">

    <!-- Left column -->
    <div style="display:flex;flex-direction:column;gap:1.25rem">

        <!-- Customer -->
        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">Customer</h2>
                <?php if (!empty($repair['customer_id'])): ?>
                <a href="<?= BASE_URL ?>/customers/<?= $repair['customer_id'] ?>" class="btn btn-xs btn-secondary">Profile</a>
                <?php endif; ?>
            </div>
            <ul class="info-list">
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Name</span>
                        <span class="info-value">
                            <?php if (!empty($repair['customer_id'])): ?>
                            <a href="<?= BASE_URL ?>/customers/<?= $repair['customer_id'] ?>"
                               style="color:var(--text-primary);text-decoration:none"
                               onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-primary)'">
                                <?= Utils::e($repair['customer_name'] ?? '—') ?>
                            </a>
                            <?php else: ?><?= Utils::e($repair['customer_name'] ?? '—') ?><?php endif; ?>
                        </span>
                    </div>
                </li>
                <?php $phone = $repair['customer_phone'] ?? ($repair['customer_phone_mobile'] ?? ''); ?>
                <?php if ($phone): ?>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 11.87 19a19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Phone</span>
                        <span class="info-value"><a href="tel:<?= Utils::e($phone) ?>" style="color:inherit"><?= Utils::e($phone) ?></a></span>
                    </div>
                </li>
                <?php endif; ?>
                <?php if (!empty($repair['customer_email'])): ?>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Email</span>
                        <span class="info-value"><a href="mailto:<?= Utils::e($repair['customer_email']) ?>" style="color:inherit"><?= Utils::e($repair['customer_email']) ?></a></span>
                    </div>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Device info -->
        <div class="section-card">
            <div class="section-header"><h2 class="section-title">Device</h2></div>
            <ul class="info-list">
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Brand / Model</span>
                        <span class="info-value"><?= Utils::e(trim(($repair['device_brand'] ?? '') . ' ' . ($repair['device_model'] ?? ''))) ?></span>
                    </div>
                </li>
                <?php if (!empty($repair['device_serial_number'])): ?>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="16" rx="2"/><line x1="7" y1="9" x2="17" y2="9"/><line x1="7" y1="13" x2="13" y2="13"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Serial / IMEI</span>
                        <span class="info-value" style="font-family:var(--font-mono)"><?= Utils::e($repair['device_serial_number']) ?></span>
                    </div>
                </li>
                <?php endif; ?>
                <?php if (!empty($repair['device_password'])): ?>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Password / PIN</span>
                        <span class="info-value" style="font-family:var(--font-mono)"><?= Utils::e($repair['device_password']) ?></span>
                    </div>
                </li>
                <?php endif; ?>
                <?php if (!empty($repair['device_condition'])): ?>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Condition / Accessories</span>
                        <span class="info-value"><?= Utils::e($repair['device_condition']) ?></span>
                    </div>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- QR Code -->
        <div class="section-card">
            <div class="section-header"><h2 class="section-title">QR Code</h2></div>
            <div class="qr-wrap">
                <?php if (!empty($qrCode)): ?>
                <div class="qr-code">
                    <img src="<?= Utils::e($qrCode) ?>" alt="QR Code for repair #<?= $repair['repair_id'] ?>">
                </div>
                <?php else: ?>
                <div class="qr-code" style="background:var(--bg-tertiary)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                         style="width:60px;height:60px;stroke:var(--text-muted)" aria-hidden="true">
                        <rect x="3" y="3" width="5" height="5"/><rect x="16" y="3" width="5" height="5"/>
                        <rect x="3" y="16" width="5" height="5"/>
                    </svg>
                </div>
                <?php endif; ?>
                <p class="qr-value"><?= Utils::e($repair['qr_code'] ?? '') ?></p>
                <p style="font-size:.72rem;color:var(--text-muted);text-align:center">
                    Scan to look up this repair quickly
                </p>
            </div>
        </div>

        <!-- Amounts -->
        <div class="section-card">
            <div class="section-header"><h2 class="section-title">Amounts</h2></div>
            <?php
            $est  = (float)($repair['estimate_amount'] ?? 0);
            $act  = (float)($repair['actual_amount']   ?? 0);
            $dep  = (float)($repair['deposit_paid']    ?? 0);
            $due  = $act > 0 ? max(0, $act - $dep) : 0;
            ?>
            <?php if ($est > 0): ?>
            <div class="amount-row"><span class="amount-label">Estimate</span><span><?= Utils::formatCurrency($est) ?></span></div>
            <?php endif; ?>
            <?php if ($act > 0): ?>
            <div class="amount-row"><span class="amount-label">Actual Amount</span><span><?= Utils::formatCurrency($act) ?></span></div>
            <?php endif; ?>
            <?php if ($dep > 0): ?>
            <div class="amount-row"><span class="amount-label">Deposit Paid</span><span style="color:var(--success)">-<?= Utils::formatCurrency($dep) ?></span></div>
            <?php endif; ?>
            <div class="amount-row">
                <span class="amount-label">Balance Due</span>
                <span style="color:<?= $due > 0 ? 'var(--error)' : 'var(--success)' ?>"><?= Utils::formatCurrency($due) ?></span>
            </div>

            <?php if ($act > 0 && empty($repair['invoice_id'])): ?>
            <div style="padding:.75rem 1.25rem;border-top:1px solid var(--border)">
                <a href="<?= BASE_URL ?>/repairs/<?= $repair['repair_id'] ?>/invoice" class="btn btn-primary btn-sm" style="width:100%;justify-content:center">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/>
                    </svg>Create Invoice
                </a>
            </div>
            <?php elseif (!empty($repair['invoice_id'])): ?>
            <div style="padding:.75rem 1.25rem;border-top:1px solid var(--border);font-size:.82rem;color:var(--text-secondary)">
                Invoice: <a href="<?= BASE_URL ?>/invoices/<?= $repair['invoice_id'] ?>" style="color:var(--accent)">#<?= $repair['invoice_id'] ?></a>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /left -->

    <!-- Right column -->
    <div style="display:flex;flex-direction:column;gap:1.25rem">

        <!-- Problem description -->
        <div class="section-card">
            <div class="section-header"><h2 class="section-title">Problem Description</h2></div>
            <div style="padding:1rem 1.25rem;font-size:.86rem;color:var(--text-secondary);line-height:1.7">
                <?= $repair['problem_description'] ? Utils::e($repair['problem_description']) : '<em style="color:var(--text-muted)">Not recorded.</em>' ?>
            </div>
        </div>

        <!-- Diagnosis / work notes -->
        <?php if (!empty($repair['diagnosis_notes'])): ?>
        <div class="section-card">
            <div class="section-header"><h2 class="section-title">Diagnosis / Work Notes</h2></div>
            <div style="padding:1rem 1.25rem;font-size:.86rem;color:var(--text-secondary);line-height:1.7">
                <?= Utils::e($repair['diagnosis_notes']) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Internal notes (staff only) -->
        <?php if (!empty($repair['internal_notes']) && Auth::can('staff')): ?>
        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">Internal Notes</h2>
                <span style="font-size:.72rem;color:var(--text-muted)">Staff only</span>
            </div>
            <div style="padding:1rem 1.25rem;font-size:.86rem;color:var(--text-secondary);line-height:1.7">
                <?= Utils::e($repair['internal_notes']) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Photos -->
        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">Photos
                    <?php if (!empty($photos)): ?>
                    <span class="badge badge-gray" style="margin-left:.35rem"><?= count($photos) ?></span>
                    <?php endif; ?>
                </h2>
                <a href="<?= BASE_URL ?>/repairs/<?= $repair['repair_id'] ?>/edit" class="btn btn-xs btn-secondary">Manage</a>
            </div>
            <?php if (empty($photos)): ?>
            <div class="empty-state" style="padding:1.5rem">No photos attached.</div>
            <?php else: ?>
            <div class="photo-gallery">
                <?php foreach ($photos as $i => $photo): ?>
                <div class="gallery-item" onclick="openLightbox(<?= $i ?>)" title="View photo">
                    <img src="<?= BASE_URL ?>/uploads/<?= Utils::e($photo) ?>"
                         alt="Repair photo <?= $i + 1 ?>" loading="lazy">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Assignment & dates -->
        <div class="section-card">
            <div class="section-header"><h2 class="section-title">Assignment & Dates</h2></div>
            <ul class="info-list">
                <?php if (!empty($repair['assigned_to'])): ?>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Assigned To</span>
                        <span class="info-value"><?= Utils::e($repair['assigned_to']) ?></span>
                    </div>
                </li>
                <?php endif; ?>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Date In</span>
                        <span class="info-value"><?= Utils::formatDate($repair['date_in']) ?></span>
                    </div>
                </li>
                <?php if (!empty($repair['date_expected_out'])): ?>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Expected Out</span>
                        <span class="info-value"><?= Utils::formatDate($repair['date_expected_out']) ?></span>
                    </div>
                </li>
                <?php endif; ?>
                <?php if (!empty($repair['date_out'])): ?>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Completed / Out</span>
                        <span class="info-value" style="color:var(--success)"><?= Utils::formatDate($repair['date_out']) ?></span>
                    </div>
                </li>
                <?php endif; ?>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Record Created</span>
                        <span class="info-value"><?= Utils::formatDateTime($repair['created_at'] ?? '') ?></span>
                    </div>
                </li>
            </ul>
        </div>

        <?php if (Auth::can('admin')): ?>
        <!-- Danger zone -->
        <div class="section-card" style="border-color:var(--error-bg)">
            <div class="section-header" style="background:var(--error-bg)">
                <h2 class="section-title" style="color:var(--error)">Danger Zone</h2>
            </div>
            <div style="padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
                <p style="font-size:.82rem;color:var(--text-secondary);margin:0">
                    Permanently delete this repair record and all its photos. This cannot be undone.
                </p>
                <form method="POST" action="<?= BASE_URL ?>/repairs/<?= $repair['repair_id'] ?>/delete"
                      data-confirm="Delete repair #<?= $repair['repair_id'] ?>? This cannot be undone.">
                    <input type="hidden" name="csrf_token" value="<?= Utils::e(Auth::generateCSRFToken()) ?>">
                    <button type="submit" class="btn btn-danger">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px" aria-hidden="true">
                            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/>
                            <path d="M10 11v6M14 11v6M9 6V4h6v2"/>
                        </svg>Delete Repair
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /right -->
</div><!-- /rep-grid -->

<!-- ── Lightbox ──────────────────────────────────────────────────────────── -->
<?php if (!empty($photos)): ?>
<div id="lightbox" role="dialog" aria-modal="true" aria-label="Photo viewer">
    <button id="lightboxClose" onclick="closeLightbox()" aria-label="Close">&times;</button>
    <img id="lightboxImg" src="" alt="Repair photo">
</div>
<script>
var photos = <?= json_encode(array_values($photos), JSON_HEX_TAG) ?>;
var baseUrl = '<?= BASE_URL ?>/uploads/';
var lbIdx = 0;
function openLightbox(i) {
    lbIdx = i;
    document.getElementById('lightboxImg').src = baseUrl + photos[i];
    document.getElementById('lightbox').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
    document.body.style.overflow = '';
}
document.getElementById('lightbox').addEventListener('click', function (e) {
    if (e.target === this) closeLightbox();
});
document.addEventListener('keydown', function (e) {
    if (!document.getElementById('lightbox').classList.contains('active')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowRight') openLightbox((lbIdx + 1) % photos.length);
    if (e.key === 'ArrowLeft')  openLightbox((lbIdx - 1 + photos.length) % photos.length);
});
</script>
<?php endif; ?>

<script>
// Confirm delete
document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('submit', function (e) {
        if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
});
</script>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
