<?php
$pageTitle = 'Edit Credit Note #' . $cn['cn_number'];
require VIEWS_PATH . '/layouts/header.php';
$items = $cn['items'] ?? [['description'=>'','basic_amount'=>'','vat_amount'=>'','net_amount'=>'']];
?>
<style>
.form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
@media(max-width:600px){.form-grid-2{grid-template-columns:1fr}}
.cn-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}
@media(max-width:900px){.cn-form-grid{grid-template-columns:1fr}}
.items-table{width:100%;border-collapse:collapse}
.items-table th{background:var(--bg-tertiary);font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:var(--text-secondary);padding:.5rem .75rem;border-bottom:1px solid var(--border);text-align:left}
.items-table th.num{text-align:right}
.items-table td{padding:.4rem .35rem;vertical-align:top;border-bottom:1px solid var(--border)}
.items-table .form-input{font-size:.84rem;padding:.35rem .6rem}
.item-desc{width:45%}
.item-num{width:17%;min-width:100px}
.totals-row td{font-weight:600;font-size:.85rem;padding:.5rem .75rem;background:var(--bg-tertiary)}
.totals-row .lbl{color:var(--text-secondary)}
.rm-btn{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:var(--radius);border:1px solid var(--border);background:none;color:var(--text-muted);cursor:pointer;font-size:1rem;line-height:1;transition:all var(--transition)}
.rm-btn:hover{background:var(--error-bg);color:var(--error);border-color:var(--error)}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Edit Credit Note #<?= Utils::e($cn['cn_number']) ?></h1>
    </div>
    <div style="display:flex;gap:.5rem">
        <a href="<?= BASE_URL ?>/credit-notes/<?= $cn['cn_id'] ?>" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
            </svg>Back
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="flash flash-error" style="margin-bottom:1rem">
    <ul style="margin:0;padding-left:1.25rem">
        <?php foreach ($errors as $e): ?><li><?= Utils::e($e) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>/credit-notes/<?= $cn['cn_id'] ?>" id="cnForm">
    <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken) ?>">

    <div class="cn-form-grid" style="margin-bottom:1.5rem">

        <div style="display:flex;flex-direction:column;gap:1.5rem">

            <div class="card">
                <div class="card-header"><h2 class="card-title">Credit Note Details</h2></div>
                <div class="card-body">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label" for="cn_number">CN Number <span class="required">*</span></label>
                            <input type="number" id="cn_number" name="cn_number" class="form-input <?= isset($errors['cn_number']) ? 'is-invalid' : '' ?>"
                                   value="<?= Utils::e($fd['cn_number'] ?? $cn['cn_number']) ?>" min="1" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="cn_date">Date <span class="required">*</span></label>
                            <input type="date" id="cn_date" name="cn_date" class="form-input <?= isset($errors['cn_date']) ? 'is-invalid' : '' ?>"
                                   value="<?= Utils::e($fd['cn_date'] ?? $cn['cn_date']) ?>" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">Company / Issuer</h2></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="company_name">Company Name <span class="required">*</span></label>
                        <input type="text" id="company_name" name="company_name" class="form-input <?= isset($errors['company_name']) ? 'is-invalid' : '' ?>"
                               value="<?= Utils::e($fd['company_name'] ?? $cn['company_name'] ?? '') ?>" maxlength="200" placeholder="Your company or shop name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="company_address">Address</label>
                        <textarea id="company_address" name="company_address" class="form-input" rows="2"
                                  placeholder="Street, City, Country"><?= Utils::e($fd['company_address'] ?? $cn['company_address'] ?? '') ?></textarea>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label" for="company_phone">Phone</label>
                            <input type="text" id="company_phone" name="company_phone" class="form-input"
                                   value="<?= Utils::e($fd['company_phone'] ?? $cn['company_phone'] ?? '') ?>" maxlength="50" placeholder="+1 234 567 8900">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="company_email">Email</label>
                            <input type="text" id="company_email" name="company_email" class="form-input"
                                   value="<?= Utils::e($fd['company_email'] ?? $cn['company_email'] ?? '') ?>" maxlength="150" placeholder="info@company.com">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="company_vat">VAT N°</label>
                        <input type="text" id="company_vat" name="company_vat" class="form-input"
                               value="<?= Utils::e($fd['company_vat'] ?? $cn['company_vat'] ?? '') ?>" maxlength="50">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">Customer Details</h2></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="customer_name">Customer Name <span class="required">*</span></label>
                        <input type="text" id="customer_name" name="customer_name" class="form-input <?= isset($errors['customer_name']) ? 'is-invalid' : '' ?>"
                               value="<?= Utils::e($fd['customer_name'] ?? $cn['customer_name']) ?>" maxlength="200" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="customer_address">Address</label>
                        <input type="text" id="customer_address" name="customer_address" class="form-input"
                               value="<?= Utils::e($fd['customer_address'] ?? $cn['customer_address']) ?>" maxlength="500">
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="customer_vat">VAT N°</label>
                        <input type="text" id="customer_vat" name="customer_vat" class="form-input"
                               value="<?= Utils::e($fd['customer_vat'] ?? $cn['customer_vat']) ?>" maxlength="50">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">Note & Signature</h2></div>
                <div class="card-body" style="display:flex;flex-direction:column;gap:1rem">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="cn_note">Footer Note</label>
                        <textarea id="cn_note" name="note" class="form-input" rows="3" maxlength="2000"
                                  placeholder="(Optional)"><?= Utils::e($fd['note'] ?? $cn['note']) ?></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="signature_id">Authorized Signature</label>
                        <select id="signature_id" name="signature_id" class="form-input">
                            <option value="0">— No signature —</option>
                            <?php if (!empty($signatures['signature1'])): ?>
                            <option value="1" <?= ($fd['signature_id'] ?? $cn['signature_id'] ?? 0) == 1 ? 'selected' : '' ?>>
                                <?= Utils::e($signatures['signature1']) ?>
                            </option>
                            <?php endif; ?>
                            <?php if (!empty($signatures['signature2'])): ?>
                            <option value="2" <?= ($fd['signature_id'] ?? $cn['signature_id'] ?? 0) == 2 ? 'selected' : '' ?>>
                                <?= Utils::e($signatures['signature2']) ?>
                            </option>
                            <?php endif; ?>
                            <?php if (!empty($signatures['signature3'])): ?>
                            <option value="3" <?= ($fd['signature_id'] ?? $cn['signature_id'] ?? 0) == 3 ? 'selected' : '' ?>>
                                <?= Utils::e($signatures['signature3']) ?>
                            </option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right: Line items -->
        <div>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Line Items</h2>
                    <button type="button" id="addItemBtn" class="btn btn-sm btn-secondary">+ Add Line</button>
                </div>
                <div class="card-body" style="padding:0;overflow-x:auto">
                    <table class="items-table" id="itemsTable">
                        <thead>
                            <tr>
                                <th class="item-desc">Description</th>
                                <th class="item-num num">Basic €</th>
                                <th class="item-num num">VAT €</th>
                                <th class="item-num num">Net €</th>
                                <th style="width:32px"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <?php foreach ($items as $idx => $item): ?>
                            <tr class="item-row">
                                <td class="item-desc">
                                    <input type="text" name="items[<?= $idx ?>][description]" class="form-input item-desc-inp"
                                           value="<?= Utils::e($item['description'] ?? '') ?>" placeholder="Description…">
                                </td>
                                <td class="item-num">
                                    <input type="number" name="items[<?= $idx ?>][basic_amount]" class="form-input item-basic"
                                           value="<?= Utils::e($item['basic_amount'] ?? '') ?>" step="0.01" min="0" placeholder="0.00">
                                </td>
                                <td class="item-num">
                                    <input type="number" name="items[<?= $idx ?>][vat_amount]" class="form-input item-vat"
                                           value="<?= Utils::e($item['vat_amount'] ?? '') ?>" step="0.01" placeholder="0.00">
                                </td>
                                <td class="item-num">
                                    <input type="number" name="items[<?= $idx ?>][net_amount]" class="form-input item-net"
                                           value="<?= Utils::e($item['net_amount'] ?? '') ?>" step="0.01" readonly
                                           style="background:var(--bg-tertiary);color:var(--text-secondary)">
                                </td>
                                <td><button type="button" class="rm-btn rm-item" title="Remove">×</button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="totals-row">
                                <td class="lbl" style="padding:.5rem .75rem">Totals</td>
                                <td class="num" style="text-align:right;padding:.5rem .75rem" id="totalBasic">0.00</td>
                                <td class="num" style="text-align:right;padding:.5rem .75rem" id="totalVat">0.00</td>
                                <td class="num" style="text-align:right;padding:.5rem .75rem;color:var(--accent)" id="totalNet">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <div style="display:flex;gap:.75rem;justify-content:flex-end;padding-top:.5rem">
        <a href="<?= BASE_URL ?>/credit-notes/<?= $cn['cn_id'] ?>" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                <polyline points="17 21 17 13 7 13 7 21"/>
            </svg>Save Changes
        </button>
    </div>
</form>

<script>
(function () {
    var tbody    = document.getElementById('itemsBody');
    var addBtn   = document.getElementById('addItemBtn');
    var rowIndex = tbody.querySelectorAll('.item-row').length;

    function recalcRow(row) {
        var basic = parseFloat(row.querySelector('.item-basic').value) || 0;
        var vat   = parseFloat(row.querySelector('.item-vat').value)   || 0;
        row.querySelector('.item-net').value = (basic + vat).toFixed(2);
        recalcTotals();
    }

    function recalcTotals() {
        var tBasic = 0, tVat = 0, tNet = 0;
        tbody.querySelectorAll('.item-row').forEach(function (r) {
            tBasic += parseFloat(r.querySelector('.item-basic').value) || 0;
            tVat   += parseFloat(r.querySelector('.item-vat').value)   || 0;
            tNet   += parseFloat(r.querySelector('.item-net').value)   || 0;
        });
        document.getElementById('totalBasic').textContent = tBasic.toFixed(2);
        document.getElementById('totalVat').textContent   = tVat.toFixed(2);
        document.getElementById('totalNet').textContent   = tNet.toFixed(2);
    }

    function bindRow(row) {
        row.querySelectorAll('.item-basic, .item-vat').forEach(function (inp) {
            inp.addEventListener('input', function () { recalcRow(row); });
        });
        row.querySelector('.rm-item').addEventListener('click', function () {
            if (tbody.querySelectorAll('.item-row').length > 1) {
                row.remove();
                recalcTotals();
            }
        });
    }

    tbody.querySelectorAll('.item-row').forEach(function (row) {
        bindRow(row);
        recalcRow(row);
    });

    addBtn.addEventListener('click', function () {
        var idx = rowIndex++;
        var tr  = document.createElement('tr');
        tr.className = 'item-row';
        tr.innerHTML =
            '<td class="item-desc"><input type="text" name="items[' + idx + '][description]" class="form-input item-desc-inp" placeholder="Description…"></td>' +
            '<td class="item-num"><input type="number" name="items[' + idx + '][basic_amount]" class="form-input item-basic" step="0.01" min="0" placeholder="0.00"></td>' +
            '<td class="item-num"><input type="number" name="items[' + idx + '][vat_amount]" class="form-input item-vat" step="0.01" placeholder="0.00"></td>' +
            '<td class="item-num"><input type="number" name="items[' + idx + '][net_amount]" class="form-input item-net" step="0.01" readonly style="background:var(--bg-tertiary);color:var(--text-secondary)"></td>' +
            '<td><button type="button" class="rm-btn rm-item" title="Remove">\xd7</button></td>';
        tbody.appendChild(tr);
        bindRow(tr);
        tr.querySelector('.item-desc-inp').focus();
    });

    recalcTotals();
})();
</script>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
