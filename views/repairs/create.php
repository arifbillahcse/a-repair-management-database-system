<?php
$pageTitle = 'New Repair';
require VIEWS_PATH . '/layouts/header.php';

$fd  = $formData ?? [];
$err = $errors   ?? [];

// Pre-load customer info (from ?customer_id or previous form submission)
$preCustomer = $preloadCustomer ?? null;
$custId   = (int)($fd['customer_id']   ?? ($preCustomer['customer_id'] ?? 0));
$custName = Utils::e($fd['customer_name'] ?? ($preCustomer['full_name'] ?? ''));
$custPhone = Utils::e($preCustomer['phone_mobile'] ?? ($preCustomer['phone_landline'] ?? ''));
?>
<style>
.form-grid{display:grid;grid-template-columns:1fr;gap:1.5rem}
@media(min-width:900px){.form-grid{grid-template-columns:1fr 1fr}}
.form-grid-3{display:grid;grid-template-columns:1fr;gap:1rem}
@media(min-width:700px){.form-grid-3{grid-template-columns:1fr 1fr 1fr}}
.form-grid-2{display:grid;grid-template-columns:1fr;gap:1rem}
@media(min-width:600px){.form-grid-2{grid-template-columns:1fr 1fr}}
.section-divider{display:flex;align-items:center;gap:.75rem;margin:0 0 1.25rem;color:var(--text-secondary);font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em}
.section-divider::after{content:'';flex:1;height:1px;background:var(--border)}
.customer-autocomplete{position:relative}
#custDropdown{position:absolute;top:100%;left:0;right:0;z-index:50;background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius);box-shadow:0 8px 24px rgba(0,0,0,.35);max-height:280px;overflow-y:auto;display:none}
.cust-option{padding:.55rem .9rem;cursor:pointer;font-size:.85rem;border-bottom:1px solid var(--border)}
.cust-option:last-child{border-bottom:none}
.cust-option:hover,.cust-option.active{background:var(--accent-dim);color:var(--accent)}
.cust-option-sub{font-size:.75rem;color:var(--text-muted)}
.selected-customer{display:flex;align-items:center;gap:.65rem;padding:.55rem .75rem;background:var(--accent-dim);border:1px solid var(--accent);border-radius:var(--radius);margin-top:.4rem;font-size:.84rem}
.selected-customer strong{color:var(--accent)}
.sc-clear{margin-left:auto;background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:1rem;line-height:1;padding:0 2px}
.sc-clear:hover{color:var(--error)}
.photo-drop{border:2px dashed var(--border);border-radius:var(--radius-lg);padding:1.5rem;text-align:center;cursor:pointer;transition:border-color var(--transition),background var(--transition)}
.photo-drop:hover,.photo-drop.drag-over{border-color:var(--accent);background:var(--accent-dim)}
.photo-drop svg{width:36px;height:36px;stroke:var(--text-muted);margin-bottom:.5rem}
.photo-drop p{font-size:.82rem;color:var(--text-muted);margin:0}
.photo-drop input[type=file]{display:none}
.photo-preview-grid{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.75rem}
.photo-thumb{position:relative;width:80px;height:80px;border-radius:var(--radius);overflow:hidden;border:1px solid var(--border)}
.photo-thumb img{width:100%;height:100%;object-fit:cover}
.photo-thumb-rm{position:absolute;top:2px;right:2px;width:18px;height:18px;border-radius:50%;background:rgba(0,0,0,.7);border:none;cursor:pointer;color:#fff;font-size:.8rem;display:flex;align-items:center;justify-content:center;line-height:1}
.char-count{font-size:.72rem;color:var(--text-muted);float:right;margin-top:.2rem}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">New Repair</h1>
        <?php if ($preCustomer): ?>
        <p class="page-subtitle">For <?= Utils::e($preCustomer['full_name']) ?></p>
        <?php endif; ?>
    </div>
    <a href="<?= BASE_URL ?>/repairs" class="btn btn-secondary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
        </svg>Back
    </a>
</div>

<form method="POST" action="<?= BASE_URL ?>/repairs" enctype="multipart/form-data"
      data-validate="1" novalidate>
    <input type="hidden" name="csrf_token" value="<?= Utils::e(Auth::generateCSRFToken()) ?>">

    <div class="form-grid">

        <!-- ── LEFT COLUMN ─────────────────────────────────────────────── -->
        <div style="display:flex;flex-direction:column;gap:1.5rem">

            <!-- Client -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Client</h2></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="custSearch">
                            Client <span style="font-size:.75rem;color:var(--text-muted)">(optional)</span>
                        </label>

                        <input type="hidden" name="customer_id" id="customerId" value="<?= $custId ?: '' ?>">
                        <input type="hidden" name="customer_name" id="customerNameHidden" value="<?= $custName ?>">

                        <?php if (!$custId): ?>
                        <div class="customer-autocomplete">
                            <div class="search-input-wrap">
                                <svg class="search-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                                </svg>
                                <input type="text" id="custSearch" class="form-input <?= isset($err['customer_id']) ? 'is-invalid' : '' ?>"
                                       placeholder="Type name, phone or email…" autocomplete="off"
                                       aria-label="Search client">
                            </div>
                            <div id="custDropdown" role="listbox" aria-label="Customer suggestions"></div>
                        </div>
                        <?php endif; ?>

                        <div id="selectedCustomer" style="<?= $custId ? '' : 'display:none' ?>">
                            <div class="selected-customer">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                     style="width:16px;height:16px;flex-shrink:0" aria-hidden="true">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                                <strong id="selectedCustomerName"><?= $custName ?></strong>
                                <?php if ($custPhone): ?><span style="color:var(--text-muted)"><?= $custPhone ?></span><?php endif; ?>
                                <button type="button" class="sc-clear" id="clearCustomer" title="Change customer"
                                        <?= $preCustomer ? 'style="display:none"' : '' ?>>&#x2715;</button>
                            </div>
                        </div>

                        <?php if (isset($err['customer_id'])): ?>
                        <div class="invalid-feedback"><?= Utils::e($err['customer_id']) ?></div>
                        <?php endif; ?>

                        <div style="margin-top:.5rem">
                            <a href="<?= BASE_URL ?>/customers/create" target="_blank"
                               style="font-size:.78rem;color:var(--accent);text-decoration:none">
                                + Create new client
                            </a>
                        </div>
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
                            <input type="text" id="deviceBrand" name="device_brand" class="form-input <?= isset($err['device_brand'])?'is-invalid':'' ?>"
                                   value="<?= Utils::e($fd['device_brand'] ?? '') ?>"
                                   placeholder="Apple, Samsung…" maxlength="100">
                            <?php if (isset($err['device_brand'])): ?><div class="invalid-feedback"><?= Utils::e($err['device_brand']) ?></div><?php endif; ?>
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" for="deviceModel">
                                Model
                            </label>
                            <input type="text" id="deviceModel" name="device_model" class="form-input <?= isset($err['device_model'])?'is-invalid':'' ?>"
                                   value="<?= Utils::e($fd['device_model'] ?? '') ?>"
                                   placeholder="iPhone 14, Galaxy S23…" maxlength="150">
                        </div>
                    </div>
                    <div class="form-grid-2" style="margin-bottom:1rem">
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" for="deviceSerial">Serial Number / IMEI</label>
                            <input type="text" id="deviceSerial" name="device_serial_number" class="form-input"
                                   value="<?= Utils::e($fd['device_serial_number'] ?? '') ?>"
                                   placeholder="S/N or IMEI" maxlength="100"
                                   style="font-family:var(--font-mono)">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" for="devicePassword">Device Password / PIN</label>
                            <input type="text" id="devicePassword" name="device_password" class="form-input"
                                   value="<?= Utils::e($fd['device_password'] ?? '') ?>"
                                   placeholder="Optional unlock code" maxlength="100"
                                   style="font-family:var(--font-mono)">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="deviceCondition">Condition / Accessories</label>
                        <textarea id="deviceCondition" name="device_condition" class="form-input" rows="2"
                                  placeholder="Scratched screen, missing back panel, includes charger…"
                                  maxlength="500"><?= Utils::e($fd['device_condition'] ?? '') ?></textarea>
                    </div>
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

            <!-- Photos -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Photos</h2></div>
                <div class="card-body">
                    <div class="photo-drop" id="photoDrop">
                        <label for="photoInput" style="cursor:pointer;display:flex;flex-direction:column;align-items:center">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                <polyline points="21 15 16 10 5 21"/>
                            </svg>
                            <p>Drop photos here or <strong style="color:var(--accent)">browse</strong></p>
                            <p style="margin-top:.25rem">JPG, PNG, GIF · max 5 MB each · up to 10 photos</p>
                        </label>
                        <input type="file" id="photoInput" name="photos[]" accept="image/*" multiple>
                    </div>
                    <div class="photo-preview-grid" id="photoPreview"></div>
                </div>
            </div>

        </div>

        <!-- ── RIGHT COLUMN ────────────────────────────────────────────── -->
        <div style="display:flex;flex-direction:column;gap:1.5rem">

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
                                  placeholder="Describe the issue reported by the client…"
                                  data-validate="required" required><?= Utils::e($fd['problem_description'] ?? '') ?></textarea>
                        <span class="char-count"><span id="pdCount">0</span>/2000</span>
                        <?php if (isset($err['problem_description'])): ?><div class="invalid-feedback"><?= Utils::e($err['problem_description']) ?></div><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="diagnosisNotes">Diagnosis / Work Notes</label>
                        <textarea id="diagnosisNotes" name="diagnosis_notes" class="form-input"
                                  rows="3" maxlength="2000"
                                  placeholder="Internal notes: parts replaced, tests done…"><?= Utils::e($fd['diagnosis_notes'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="internalNotes">Internal Notes</label>
                        <textarea id="internalNotes" name="internal_notes" class="form-input"
                                  rows="2" maxlength="1000"
                                  placeholder="Staff-only notes…"><?= Utils::e($fd['internal_notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Dates & Priority -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Scheduling</h2></div>
                <div class="card-body">
                    <div class="form-grid-2" style="margin-bottom:1rem">
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" for="dateIn">
                                Date In <span class="required">*</span>
                            </label>
                            <input type="date" id="dateIn" name="date_in" class="form-input <?= isset($err['date_in'])?'is-invalid':'' ?>"
                                   value="<?= Utils::e($fd['date_in'] ?? date('Y-m-d')) ?>"
                                   data-validate="required" required>
                            <?php if (isset($err['date_in'])): ?><div class="invalid-feedback"><?= Utils::e($err['date_in']) ?></div><?php endif; ?>
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" for="dateExpected">Out Date/Delivery</label>
                            <input type="date" id="dateExpected" name="date_expected_out" class="form-input"
                                   value="<?= Utils::e($fd['date_expected_out'] ?? '') ?>">
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
                                   placeholder="0.00" step="0.01" min="0" max="999999.99">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" for="actualAmount">Actual Amount (€)</label>
                            <input type="number" id="actualAmount" name="actual_amount" class="form-input"
                                   value="<?= Utils::e($fd['actual_amount'] ?? '') ?>"
                                   placeholder="0.00" step="0.01" min="0" max="999999.99">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="depositPaid">Deposit Paid (€)</label>
                        <input type="number" id="depositPaid" name="deposit_paid" class="form-input"
                               value="<?= Utils::e($fd['deposit_paid'] ?? '') ?>"
                               placeholder="0.00" step="0.01" min="0" max="999999.99">
                    </div>
                </div>
            </div>

            <!-- Assigned to -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Assignment</h2></div>
                <div class="card-body">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="assignedTo">Assigned Technician</label>
                        <input type="text" id="assignedTo" name="assigned_to" class="form-input"
                               value="<?= Utils::e($fd['assigned_to'] ?? '') ?>"
                               placeholder="Technician name or ID" maxlength="100">
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Form actions -->
    <div style="display:flex;gap:.75rem;justify-content:flex-end;padding-top:1.5rem">
        <a href="<?= BASE_URL ?>/repairs" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                <polyline points="17 21 17 13 7 13 7 21"/>
                <polyline points="7 3 7 8 15 8"/>
            </svg>Create Repair
        </button>
    </div>
</form>

<script>
// ── Customer autocomplete ─────────────────────────────────────────────────────
(function () {
    const inp    = document.getElementById('custSearch');
    const drop   = document.getElementById('custDropdown');
    const hidId  = document.getElementById('customerId');
    const hidNm  = document.getElementById('customerNameHidden');
    const selDiv = document.getElementById('selectedCustomer');
    const selNm  = document.getElementById('selectedCustomerName');
    const clrBtn = document.getElementById('clearCustomer');

    if (!inp) return; // already pre-filled

    let timer, abortCtrl;
    let activeIdx = -1;
    let results   = [];

    function showDrop(items) {
        results = items;
        activeIdx = -1;
        if (!items.length) { drop.style.display = 'none'; return; }
        drop.innerHTML = '';
        items.forEach(function (c, i) {
            const div = document.createElement('div');
            div.className = 'cust-option';
            div.setAttribute('role', 'option');
            div.dataset.idx = i;
            div.innerHTML = '<strong>' + escHtml(c.full_name) + '</strong>'
                + (c.phone ? '<span class="cust-option-sub">' + escHtml(c.phone) + '</span>' : '')
                + (c.email ? '<span class="cust-option-sub">' + escHtml(c.email) + '</span>' : '');
            div.addEventListener('mousedown', function (e) {
                e.preventDefault();
                selectCustomer(c);
            });
            drop.appendChild(div);
        });
        drop.style.display = 'block';
    }

    function selectCustomer(c) {
        hidId.value  = c.customer_id;
        hidNm.value  = c.full_name;
        selNm.textContent = c.full_name;
        selDiv.style.display = '';
        inp.closest('.customer-autocomplete').style.display = 'none';
        drop.style.display = 'none';
    }

    if (clrBtn) {
        clrBtn.addEventListener('click', function () {
            hidId.value = '';
            hidNm.value = '';
            selDiv.style.display = 'none';
            inp.closest('.customer-autocomplete').style.display = '';
            inp.value = '';
            inp.focus();
        });
    }

    inp.addEventListener('input', function () {
        clearTimeout(timer);
        const q = inp.value.trim();
        if (q.length < 2) { drop.style.display = 'none'; return; }
        timer = setTimeout(function () {
            if (abortCtrl) abortCtrl.abort();
            abortCtrl = new AbortController();
            fetch('<?= BASE_URL ?>/api/customers/autocomplete?q=' + encodeURIComponent(q), { signal: abortCtrl.signal })
                .then(r => r.json())
                .then(showDrop)
                .catch(() => {});
        }, 220);
    });

    inp.addEventListener('keydown', function (e) {
        if (!results.length) return;
        const opts = drop.querySelectorAll('.cust-option');
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (activeIdx < opts.length - 1) activeIdx++;
            opts.forEach((o, i) => o.classList.toggle('active', i === activeIdx));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (activeIdx > 0) activeIdx--;
            opts.forEach((o, i) => o.classList.toggle('active', i === activeIdx));
        } else if (e.key === 'Enter' && activeIdx >= 0) {
            e.preventDefault();
            selectCustomer(results[activeIdx]);
        } else if (e.key === 'Escape') {
            drop.style.display = 'none';
        }
    });

    document.addEventListener('click', function (e) {
        if (!inp.contains(e.target) && !drop.contains(e.target)) drop.style.display = 'none';
    });

    function escHtml(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();

// ── Photo preview ─────────────────────────────────────────────────────────────
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
                rm.title = 'Remove';
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

    drop.addEventListener('dragover', function (e) { e.preventDefault(); drop.classList.add('drag-over'); });
    drop.addEventListener('dragleave', function () { drop.classList.remove('drag-over'); });
    drop.addEventListener('drop', function (e) {
        e.preventDefault();
        drop.classList.remove('drag-over');
        Array.from(e.dataTransfer.files).forEach(function (f) { if (f.type.startsWith('image/')) dt.items.add(f); });
        input.files = dt.files;
        refresh();
    });
})();

// ── Character count for problem description ───────────────────────────────────
(function () {
    const ta  = document.getElementById('problemDesc');
    const cnt = document.getElementById('pdCount');
    if (!ta || !cnt) return;
    function update() { cnt.textContent = ta.value.length; }
    ta.addEventListener('input', update);
    update();
})();
</script>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
