<?php
$pageTitle = 'Edit Repair #' . $repair['repair_id'];
require VIEWS_PATH . '/layouts/header.php';

// $formData is set only on validation failure (session flash), otherwise use DB row
$fd  = $formData ?? $repair;
$err = $errors   ?? [];

$photos      = $repair['photos'] ?? [];  // decoded array of relative paths
$statusFlow  = REPAIR_STATUS_FLOW[$repair['status']] ?? [];
?>
<style>
.form-grid{display:grid;grid-template-columns:1fr;gap:1.5rem}
@media(min-width:900px){.form-grid{grid-template-columns:1fr 1fr}}
.form-grid-2{display:grid;grid-template-columns:1fr;gap:1rem}
@media(min-width:600px){.form-grid-2{grid-template-columns:1fr 1fr}}
.repair-meta{display:flex;gap:.5rem;flex-wrap:wrap;align-items:center}
.repair-meta-chip{font-size:.75rem;color:var(--text-muted);background:var(--bg-tertiary);border:1px solid var(--border);border-radius:var(--radius-full);padding:.15rem .6rem}
.photo-gallery{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem}
.gallery-item{position:relative;width:90px;height:90px;border-radius:var(--radius);overflow:hidden;border:1px solid var(--border)}
.gallery-item img{width:100%;height:100%;object-fit:cover;display:block}
.gallery-item-rm{position:absolute;top:3px;right:3px;width:20px;height:20px;border-radius:50%;background:rgba(0,0,0,.75);border:1px solid rgba(255,255,255,.2);cursor:pointer;color:#fff;font-size:.8rem;display:flex;align-items:center;justify-content:center;line-height:1;transition:background var(--transition)}
.gallery-item-rm:hover{background:var(--error)}
.photo-drop{border:2px dashed var(--border);border-radius:var(--radius-lg);padding:1.25rem;text-align:center;cursor:pointer;transition:border-color var(--transition),background var(--transition)}
.photo-drop:hover,.photo-drop.drag-over{border-color:var(--accent);background:var(--accent-dim)}
.photo-drop svg{width:28px;height:28px;stroke:var(--text-muted);margin-bottom:.35rem}
.photo-drop p{font-size:.78rem;color:var(--text-muted);margin:0}
.photo-drop input[type=file]{display:none}
.photo-preview-grid{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.5rem}
.photo-thumb{position:relative;width:80px;height:80px;border-radius:var(--radius);overflow:hidden;border:1px solid var(--border)}
.photo-thumb img{width:100%;height:100%;object-fit:cover}
.photo-thumb-rm{position:absolute;top:2px;right:2px;width:18px;height:18px;border-radius:50%;background:rgba(0,0,0,.7);border:none;cursor:pointer;color:#fff;font-size:.8rem;display:flex;align-items:center;justify-content:center;line-height:1}
.status-change-box{background:var(--bg-tertiary);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1rem}
.status-option{display:flex;align-items:center;gap:.5rem;padding:.4rem .5rem;border-radius:var(--radius);cursor:pointer;font-size:.85rem;transition:background var(--transition)}
.status-option:hover{background:var(--bg-secondary)}
.status-option input[type=radio]{accent-color:var(--accent)}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Edit Repair #<?= $repair['repair_id'] ?></h1>
        <div class="repair-meta">
            <span class="badge <?= REPAIR_STATUS_CLASS[$repair['status']] ?? 'badge-gray' ?>">
                <?= Utils::e(REPAIR_STATUS[$repair['status']] ?? $repair['status']) ?>
            </span>
            <?php if (!empty($repair['customer_name'])): ?>
            <a href="<?= BASE_URL ?>/customers/<?= $repair['customer_id'] ?>"
               class="repair-meta-chip" style="text-decoration:none;color:inherit"
               onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='inherit'">
                <?= Utils::e($repair['customer_name']) ?>
            </a>
            <?php endif; ?>
            <span class="repair-meta-chip">In: <?= Utils::formatDate($repair['date_in']) ?></span>
            <?php $days = (int)($repair['days_in_lab'] ?? 0); ?>
            <span class="repair-meta-chip" style="color:<?= $days > 14 ? 'var(--error)' : ($days > 7 ? 'var(--warning)' : '') ?>">
                <?= $days ?>d in lab
            </span>
        </div>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <a href="<?= BASE_URL ?>/repairs/<?= $repair['repair_id'] ?>/print" target="_blank" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polyline points="6 9 6 2 18 2 18 9"/>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
            </svg>Print
        </a>
        <a href="<?= BASE_URL ?>/repairs/<?= $repair['repair_id'] ?>" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
            </svg>Back
        </a>
    </div>
</div>

<form method="POST" action="<?= BASE_URL ?>/repairs/<?= $repair['repair_id'] ?>" enctype="multipart/form-data"
      data-validate="1" novalidate>
    <input type="hidden" name="csrf_token" value="<?= Utils::e(Auth::generateCSRFToken()) ?>">

    <div class="form-grid">

        <!-- ── LEFT COLUMN ─────────────────────────────────────────────── -->
        <div style="display:flex;flex-direction:column;gap:1.5rem">

            <!-- Client (read-only reference) -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Client</h2>
                    <?php if (!empty($repair['customer_id'])): ?>
                    <a href="<?= BASE_URL ?>/customers/<?= $repair['customer_id'] ?>" class="btn btn-xs btn-secondary">View Profile</a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div style="display:flex;align-items:center;gap:.65rem;padding:.55rem .75rem;background:var(--bg-tertiary);border:1px solid var(--border);border-radius:var(--radius)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             style="width:16px;height:16px;flex-shrink:0;stroke:var(--text-muted)" aria-hidden="true">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <span style="font-weight:600"><?= Utils::e($repair['customer_name'] ?? '—') ?></span>
                        <?php $ph = $repair['customer_phone'] ?? ($repair['customer_phone_mobile'] ?? ''); ?>
                        <?php if ($ph): ?>
                        <span style="color:var(--text-muted);font-size:.82rem"><?= Utils::e($ph) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Device info -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Device Information</h2></div>
                <div class="card-body">
                    <div class="form-grid-2" style="margin-bottom:1rem">
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" for="deviceBrand">Brand</label>
                            <input type="text" id="deviceBrand" name="device_brand" class="form-input"
                                   value="<?= Utils::e($fd['device_brand'] ?? '') ?>" maxlength="100"
                                   placeholder="Apple, Samsung…">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" for="deviceModel">
                                Model
                            </label>
                            <input type="text" id="deviceModel" name="device_model" class="form-input"
                                   value="<?= Utils::e($fd['device_model'] ?? '') ?>" maxlength="150"
                                   placeholder="iPhone 14, Galaxy S23…">
                        </div>
                    </div>
                    <div class="form-grid-2" style="margin-bottom:1rem">
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" for="deviceSerial">Serial / IMEI</label>
                            <input type="text" id="deviceSerial" name="device_serial_number" class="form-input"
                                   value="<?= Utils::e($fd['device_serial_number'] ?? '') ?>" maxlength="100"
                                   style="font-family:var(--font-mono)">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" for="devicePassword">Device Password / PIN</label>
                            <input type="text" id="devicePassword" name="device_password" class="form-input"
                                   value="<?= Utils::e($fd['device_password'] ?? '') ?>" maxlength="100"
                                   style="font-family:var(--font-mono)">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="deviceCondition">Condition / Accessories</label>
                        <textarea id="deviceCondition" name="device_condition" class="form-input" rows="2" maxlength="500"><?= Utils::e($fd['device_condition'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Photos -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Photos</h2></div>
                <div class="card-body">
                    <!-- Existing photos -->
                    <?php if (!empty($photos)): ?>
                    <p style="font-size:.78rem;color:var(--text-muted);margin-bottom:.5rem">Existing photos — click × to remove</p>
                    <div class="photo-gallery" id="existingPhotos">
                        <?php foreach ($photos as $photo): ?>
                        <div class="gallery-item" data-path="<?= Utils::e($photo) ?>">
                            <img src="<?= BASE_URL ?>/uploads/<?= Utils::e($photo) ?>"
                                 alt="Repair photo" loading="lazy">
                            <button type="button" class="gallery-item-rm" title="Remove photo"
                                    data-path="<?= Utils::e($photo) ?>"
                                    data-repair="<?= $repair['repair_id'] ?>"
                                    data-csrf="<?= Utils::e(Auth::generateCSRFToken()) ?>">&#x2715;</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Upload new -->
                    <div class="photo-drop" id="photoDrop">
                        <label for="photoInput" style="cursor:pointer;display:flex;flex-direction:column;align-items:center">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            <p>Add more photos</p>
                        </label>
                        <input type="file" id="photoInput" name="photos[]" accept="image/*" multiple>
                    </div>
                    <div class="photo-preview-grid" id="photoPreview"></div>
                </div>
            </div>

            <!-- Note -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Note</h2></div>
                <div class="card-body">
                    <div class="form-group" style="margin-bottom:0">
                        <textarea id="repairNote" name="notes" class="form-input" rows="3" maxlength="2000"
                                  placeholder="Add a note about this repair…"><?= Utils::e($fd['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── RIGHT COLUMN ────────────────────────────────────────────── -->
        <div style="display:flex;flex-direction:column;gap:1.5rem">

            <!-- Status -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Status</h2></div>
                <div class="card-body">
                    <div class="status-change-box">
                        <p style="font-size:.75rem;color:var(--text-muted);margin:0 0 .75rem">Current: <strong><?= Utils::e(REPAIR_STATUS[$repair['status']] ?? $repair['status']) ?></strong></p>
                        <?php if (empty($statusFlow)): ?>
                        <p style="font-size:.82rem;color:var(--text-muted)">This status has no further transitions.</p>
                        <input type="hidden" name="status" value="<?= Utils::e($repair['status']) ?>">
                        <?php else: ?>
                        <?php foreach (array_merge([$repair['status']], $statusFlow) as $st): ?>
                        <label class="status-option">
                            <input type="radio" name="status" value="<?= Utils::e($st) ?>"
                                   <?= ($fd['status'] ?? $repair['status']) === $st ? 'checked' : '' ?>>
                            <span class="badge <?= REPAIR_STATUS_CLASS[$st] ?? 'badge-gray' ?>">
                                <?= Utils::e(REPAIR_STATUS[$st] ?? $st) ?>
                            </span>
                            <?php if ($st === $repair['status']): ?><em style="font-size:.72rem;color:var(--text-muted)">(current)</em><?php endif; ?>
                        </label>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Repair details -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Repair Details</h2></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="problemDesc">
                            Problem Description <span class="required">*</span>
                        </label>
                        <textarea id="problemDesc" name="problem_description" class="form-input <?= isset($err['problem_description'])?'is-invalid':'' ?>"
                                  rows="3" maxlength="2000"
                                  data-validate="required" required><?= Utils::e($fd['problem_description'] ?? '') ?></textarea>
                        <?php if (isset($err['problem_description'])): ?><div class="invalid-feedback"><?= Utils::e($err['problem_description']) ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="diagnosisNotes">Diagnosis / Work Notes</label>
                        <textarea id="diagnosisNotes" name="diagnosis_notes" class="form-input" rows="3" maxlength="2000"><?= Utils::e($fd['diagnosis_notes'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="internalNotes">Internal Notes</label>
                        <textarea id="internalNotes" name="internal_notes" class="form-input" rows="2" maxlength="1000"><?= Utils::e($fd['internal_notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Scheduling -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Scheduling</h2></div>
                <div class="card-body">
                    <div class="form-grid-2" style="margin-bottom:1rem">
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" for="dateIn">Date In <span class="required">*</span></label>
                            <input type="date" id="dateIn" name="date_in" class="form-input <?= isset($err['date_in'])?'is-invalid':'' ?>"
                                   value="<?= Utils::e(substr($fd['date_in'] ?? '', 0, 10)) ?>"
                                   data-validate="required" required>
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" for="dateExpected">Expected Out</label>
                            <input type="date" id="dateExpected" name="date_expected_out" class="form-input"
                                   value="<?= Utils::e(substr($fd['date_expected_out'] ?? '', 0, 10)) ?>">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="priority">Priority</label>
                        <select id="priority" name="priority" class="form-select">
                            <?php foreach (['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'] as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= ($fd['priority'] ?? 'normal') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Pricing -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Pricing</h2></div>
                <div class="card-body">
                    <div class="form-grid-2" style="margin-bottom:1rem">
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" for="estimateAmount">Estimate (€)</label>
                            <input type="number" id="estimateAmount" name="estimate_amount" class="form-input"
                                   value="<?= Utils::e($fd['estimate_amount'] ?? '') ?>"
                                   placeholder="0.00" step="0.01" min="0">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" for="actualAmount">Actual Amount (€)</label>
                            <input type="number" id="actualAmount" name="actual_amount" class="form-input"
                                   value="<?= Utils::e($fd['actual_amount'] ?? '') ?>"
                                   placeholder="0.00" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="depositPaid">Deposit Paid (€)</label>
                        <input type="number" id="depositPaid" name="deposit_paid" class="form-input"
                               value="<?= Utils::e($fd['deposit_paid'] ?? '') ?>"
                               placeholder="0.00" step="0.01" min="0">
                    </div>
                </div>
            </div>

            <!-- Assignment -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Assignment</h2></div>
                <div class="card-body">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="assignedTo">Assigned Technician</label>
                        <input type="text" id="assignedTo" name="assigned_to" class="form-input"
                               value="<?= Utils::e($fd['assigned_to'] ?? '') ?>" maxlength="100">
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Form actions -->
    <div style="display:flex;gap:.75rem;justify-content:flex-end;padding-top:1.5rem">
        <a href="<?= BASE_URL ?>/repairs/<?= $repair['repair_id'] ?>" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                <polyline points="17 21 17 13 7 13 7 21"/>
                <polyline points="7 3 7 8 15 8"/>
            </svg>Save Changes
        </button>
    </div>
</form>

<script>
// ── Delete existing photo via AJAX ────────────────────────────────────────────
document.querySelectorAll('.gallery-item-rm').forEach(function (btn) {
    btn.addEventListener('click', function () {
        if (!confirm('Remove this photo?')) return;
        const item   = btn.closest('.gallery-item');
        const path   = btn.dataset.path;
        const repId  = btn.dataset.repair;
        const csrf   = btn.dataset.csrf;
        const fd     = new FormData();
        fd.append('csrf_token', csrf);
        fd.append('photo_path', path);
        fetch('<?= BASE_URL ?>/repairs/' + repId + '/photo/delete', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(function (res) {
                if (res.success) {
                    item.style.opacity = '0';
                    item.style.transition = 'opacity .2s';
                    setTimeout(function () { item.remove(); }, 200);
                } else {
                    alert(res.message || 'Could not remove photo.');
                }
            })
            .catch(function () { alert('Network error.'); });
    });
});

// ── New photo preview ─────────────────────────────────────────────────────────
(function () {
    const drop    = document.getElementById('photoDrop');
    const input   = document.getElementById('photoInput');
    const preview = document.getElementById('photoPreview');
    if (!drop || !input) return;

    let dt = new DataTransfer();

    function refresh() {
        preview.innerHTML = '';
        Array.from(dt.files).forEach(function (file, i) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const wrap = document.createElement('div');
                wrap.className = 'photo-thumb';
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = file.name;
                const rm = document.createElement('button');
                rm.type = 'button';
                rm.className = 'photo-thumb-rm';
                rm.innerHTML = '&#x2715;';
                rm.addEventListener('click', function () {
                    const newDt = new DataTransfer();
                    Array.from(dt.files).forEach(function (f, j) { if (j !== i) newDt.items.add(f); });
                    dt = newDt;
                    input.files = dt.files;
                    refresh();
                });
                wrap.appendChild(img);
                wrap.appendChild(rm);
                preview.appendChild(wrap);
            };
            reader.readAsDataURL(file);
        });
    }

    input.addEventListener('change', function () {
        Array.from(input.files).forEach(function (f) { dt.items.add(f); });
        input.files = dt.files;
        refresh();
    });

    drop.addEventListener('dragover',  function (e) { e.preventDefault(); drop.classList.add('drag-over'); });
    drop.addEventListener('dragleave', function ()  { drop.classList.remove('drag-over'); });
    drop.addEventListener('drop', function (e) {
        e.preventDefault();
        drop.classList.remove('drag-over');
        Array.from(e.dataTransfer.files).forEach(function (f) { if (f.type.startsWith('image/')) dt.items.add(f); });
        input.files = dt.files;
        refresh();
    });
})();
</script>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
