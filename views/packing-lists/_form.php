<?php
/**
 * Shared create/edit form for a Packing List (Documento di Trasporto).
 * Expects: $fd, $errors, $csrfToken, $businesses, $formAction, $submitLabel, $nextNumber
 */
$fd     = $fd     ?? [];
$errors = $errors ?? [];
$items  = $fd['items'] ?? [['quantity' => '', 'description' => '']];
if (empty($items)) { $items = [['quantity' => '', 'description' => '']]; }

$transportBy = $fd['transport_by'] ?? 'cedente';
$deliveryBy  = $fd['delivery_by']  ?? '';
$account     = $fd['account_type'] ?? '';
?>
<style>
.form-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.form-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem}
@media(max-width:600px){.form-grid-2,.form-grid-3{grid-template-columns:1fr}}
.pl-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.5rem}
@media(max-width:900px){.pl-form-grid{grid-template-columns:1fr}}
.items-table{width:100%;border-collapse:collapse}
.items-table th{background:var(--bg-tertiary);font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:var(--text-secondary);padding:.5rem .75rem;border-bottom:1px solid var(--border);text-align:left}
.items-table td{padding:.4rem .35rem;vertical-align:top;border-bottom:1px solid var(--border)}
.items-table tr:last-child td{border-bottom:none}
.items-table .form-input{font-size:.84rem;padding:.35rem .6rem}
.item-qty{width:22%;min-width:90px}
.item-desc{width:auto}
.item-rm{width:32px}
.radio-row{display:flex;gap:1.25rem;align-items:center;flex-wrap:wrap}
.radio-row label{display:inline-flex;align-items:center;gap:.4rem;font-size:.88rem;color:var(--text-primary);cursor:pointer;font-weight:500}
.rm-btn{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:var(--radius);border:1px solid var(--border);background:none;color:var(--text-muted);cursor:pointer;font-size:1rem;line-height:1;transition:all var(--transition)}
.rm-btn:hover{background:var(--error-bg);color:var(--error);border-color:var(--error)}
</style>

<?php if (!empty($errors)): ?>
<div class="flash flash-error" style="margin-bottom:1rem">
    <ul style="margin:0;padding-left:1.25rem">
        <?php foreach ($errors as $e): ?><li><?= Utils::e($e) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" action="<?= $formAction ?>" id="plForm">
    <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken) ?>">

    <div class="pl-form-grid" style="margin-bottom:1.5rem">

        <!-- Left column -->
        <div style="display:flex;flex-direction:column;gap:1.5rem">

            <!-- Document details -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Document (Documento di Trasporto)</h2></div>
                <div class="card-body">
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label" for="pl_number">N° (Number) <span class="required">*</span></label>
                            <input type="number" id="pl_number" name="pl_number" class="form-input <?= isset($errors['pl_number']) ? 'is-invalid' : '' ?>"
                                   value="<?= Utils::e($fd['pl_number'] ?? $nextNumber) ?>" min="1" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="pl_date">Del (Date) <span class="required">*</span></label>
                            <input type="date" id="pl_date" name="pl_date" class="form-input <?= isset($errors['pl_date']) ? 'is-invalid' : '' ?>"
                                   value="<?= Utils::e($fd['pl_date'] ?? date('Y-m-d')) ?>" required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">A mezzo (Transport by)</label>
                        <div class="radio-row">
                            <label><input type="radio" name="transport_by" value="cedente" <?= $transportBy !== 'cessionario' ? 'checked' : '' ?>> Cedente (sender)</label>
                            <label><input type="radio" name="transport_by" value="cessionario" <?= $transportBy === 'cessionario' ? 'checked' : '' ?>> Cessionario (recipient)</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cedente / company -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Cedente — Sender / Company</h2></div>
                <div class="card-body">
                    <?php if (!empty($businesses)): ?>
                    <div class="form-group">
                        <label class="form-label" for="biz_picker">Company Header</label>
                        <select id="biz_picker" class="form-input">
                            <option value="">— Choose a company to fill the header —</option>
                            <?php foreach ($businesses as $b): ?>
                            <option value="<?= (int)$b['business_id'] ?>"
                                    data-name="<?= Utils::e($b['name']) ?>"
                                    data-address="<?= Utils::e($b['address']) ?>"
                                    data-phone="<?= Utils::e($b['phone']) ?>"
                                    data-email="<?= Utils::e($b['email']) ?>"
                                    data-vat="<?= Utils::e($b['vat_number']) ?>"
                                    data-tax="<?= Utils::e($b['tax_id']) ?>">
                                <?= Utils::e($b['name']) ?><?= $b['is_default'] ? ' (default)' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p style="font-size:.72rem;color:var(--text-muted);margin-top:.35rem">
                            Pick MSP, Folmix or Tracia to fill the header, or type manually.
                            <a href="<?= BASE_URL ?>/admin/businesses" target="_blank" style="color:var(--accent)">Manage companies</a>
                        </p>
                    </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label class="form-label" for="company_name">Company Name <span class="required">*</span></label>
                        <input type="text" id="company_name" name="company_name" class="form-input <?= isset($errors['company_name']) ? 'is-invalid' : '' ?>"
                               value="<?= Utils::e($fd['company_name'] ?? '') ?>" maxlength="200" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="company_address">Address</label>
                        <textarea id="company_address" name="company_address" class="form-input" rows="2"><?= Utils::e($fd['company_address'] ?? '') ?></textarea>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label" for="company_phone">Phone</label>
                            <input type="text" id="company_phone" name="company_phone" class="form-input"
                                   value="<?= Utils::e($fd['company_phone'] ?? '') ?>" maxlength="50">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="company_email">Email</label>
                            <input type="text" id="company_email" name="company_email" class="form-input"
                                   value="<?= Utils::e($fd['company_email'] ?? '') ?>" maxlength="150">
                        </div>
                    </div>
                    <div class="form-grid-2" style="margin-bottom:0">
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" for="company_vat">Partita IVA (VAT)</label>
                            <input type="text" id="company_vat" name="company_vat" class="form-input"
                                   value="<?= Utils::e($fd['company_vat'] ?? '') ?>" maxlength="50">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" for="company_tax_id">Codice Fiscale</label>
                            <input type="text" id="company_tax_id" name="company_tax_id" class="form-input"
                                   value="<?= Utils::e($fd['company_tax_id'] ?? '') ?>" maxlength="50">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cessionario / customer -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Cessionario — Recipient</h2></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="customer_name">Recipient Name <span class="required">*</span></label>
                        <input type="text" id="customer_name" name="customer_name" class="form-input <?= isset($errors['customer_name']) ? 'is-invalid' : '' ?>"
                               value="<?= Utils::e($fd['customer_name'] ?? '') ?>" maxlength="200" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="customer_address">Address</label>
                        <input type="text" id="customer_address" name="customer_address" class="form-input"
                               value="<?= Utils::e($fd['customer_address'] ?? '') ?>" maxlength="500">
                    </div>
                    <div class="form-grid-2" style="margin-bottom:0">
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" for="customer_vat">VAT / C.F.</label>
                            <input type="text" id="customer_vat" name="customer_vat" class="form-input"
                                   value="<?= Utils::e($fd['customer_vat'] ?? '') ?>" maxlength="50">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" for="destination">Luogo di destinazione</label>
                            <input type="text" id="destination" name="destination" class="form-input"
                                   value="<?= Utils::e($fd['destination'] ?? '') ?>" maxlength="500"
                                   placeholder="If different from address">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Causale / order -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Causale del trasporto</h2></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="causale">Reason for transport</label>
                        <input type="text" id="causale" name="causale" class="form-input"
                               value="<?= Utils::e($fd['causale'] ?? '') ?>" maxlength="300"
                               placeholder="e.g. Vendita, Reso, Riparazione, Conto visione">
                    </div>
                    <div class="form-grid-3">
                        <div class="form-group">
                            <label class="form-label" for="order_number">N° ordine</label>
                            <input type="text" id="order_number" name="order_number" class="form-input"
                                   value="<?= Utils::e($fd['order_number'] ?? '') ?>" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="order_date">Del (order date)</label>
                            <input type="date" id="order_date" name="order_date" class="form-input"
                                   value="<?= Utils::e($fd['order_date'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Conto</label>
                            <div class="radio-row" style="padding-top:.4rem">
                                <label><input type="radio" name="account_type" value="in_conto" <?= $account === 'in_conto' ? 'checked' : '' ?>> in conto</label>
                                <label><input type="radio" name="account_type" value="a_saldo" <?= $account === 'a_saldo' ? 'checked' : '' ?>> a saldo</label>
                            </div>
                        </div>
                    </div>
                    <p style="font-size:.72rem;color:var(--text-muted);margin:.25rem 0 0">
                        <a href="#" id="clearAccount" style="color:var(--accent)">Clear conto selection</a>
                    </p>
                </div>
            </div>

        </div>

        <!-- Right column -->
        <div style="display:flex;flex-direction:column;gap:1.5rem">

            <!-- Line items -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Descrizione dei beni</h2>
                    <button type="button" id="addItemBtn" class="btn btn-sm btn-secondary">+ Add Row</button>
                </div>
                <div class="card-body" style="padding:0;overflow-x:auto">
                    <table class="items-table" id="itemsTable">
                        <thead>
                            <tr>
                                <th class="item-qty">Quantità</th>
                                <th class="item-desc">Descrizione (natura e qualità)</th>
                                <th class="item-rm"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <?php foreach ($items as $idx => $item): ?>
                            <tr class="item-row">
                                <td class="item-qty">
                                    <input type="text" name="items[<?= $idx ?>][quantity]" class="form-input"
                                           value="<?= Utils::e($item['quantity'] ?? '') ?>" placeholder="e.g. 5">
                                </td>
                                <td class="item-desc">
                                    <input type="text" name="items[<?= $idx ?>][description]" class="form-input item-desc-inp"
                                           value="<?= Utils::e($item['description'] ?? '') ?>" placeholder="Description of goods…">
                                </td>
                                <td class="item-rm">
                                    <button type="button" class="rm-btn rm-item" title="Remove row">×</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Shipping details -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Aspetto & Spedizione</h2></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label" for="aspetto">Aspetto esteriore dei beni</label>
                        <input type="text" id="aspetto" name="aspetto" class="form-input"
                               value="<?= Utils::e($fd['aspetto'] ?? '') ?>" maxlength="200"
                               placeholder="e.g. Colli / Scatole / A vista">
                    </div>
                    <div class="form-grid-3">
                        <div class="form-group">
                            <label class="form-label" for="n_colli">N° colli</label>
                            <input type="text" id="n_colli" name="n_colli" class="form-input"
                                   value="<?= Utils::e($fd['n_colli'] ?? '') ?>" maxlength="50">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="peso_kg">Peso kg.</label>
                            <input type="text" id="peso_kg" name="peso_kg" class="form-input"
                                   value="<?= Utils::e($fd['peso_kg'] ?? '') ?>" maxlength="50">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="porto">Porto</label>
                            <input type="text" id="porto" name="porto" class="form-input"
                                   value="<?= Utils::e($fd['porto'] ?? '') ?>" maxlength="100"
                                   placeholder="Franco / Assegnato">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transport -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Trasporto</h2></div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Consegna o inizio trasporto a mezzo</label>
                        <div class="radio-row">
                            <label><input type="radio" name="delivery_by" value="cedente" <?= $deliveryBy === 'cedente' ? 'checked' : '' ?>> cedente</label>
                            <label><input type="radio" name="delivery_by" value="cessionario" <?= $deliveryBy === 'cessionario' ? 'checked' : '' ?>> cessionario</label>
                            <a href="#" id="clearDelivery" style="color:var(--accent);font-size:.75rem">clear</a>
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label" for="transport_date">Data trasporto</label>
                            <input type="date" id="transport_date" name="transport_date" class="form-input"
                                   value="<?= Utils::e($fd['transport_date'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="transport_time">Ora trasporto</label>
                            <input type="text" id="transport_time" name="transport_time" class="form-input"
                                   value="<?= Utils::e($fd['transport_time'] ?? '') ?>" maxlength="20" placeholder="e.g. 14:30">
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="carrier">Generalità del trasportatore</label>
                        <input type="text" id="carrier" name="carrier" class="form-input"
                               value="<?= Utils::e($fd['carrier'] ?? '') ?>" maxlength="500"
                               placeholder="Carrier name / vehicle">
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Annotazioni</h2></div>
                <div class="card-body">
                    <div class="form-group" style="margin-bottom:0">
                        <textarea id="notes" name="notes" class="form-input" rows="3" maxlength="2000"
                                  placeholder="(Optional)"><?= Utils::e($fd['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <div style="display:flex;gap:.75rem;justify-content:flex-end;padding-top:.5rem">
        <a href="<?= BASE_URL ?>/packing-lists" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                <polyline points="17 21 17 13 7 13 7 21"/>
            </svg><?= Utils::e($submitLabel) ?>
        </button>
    </div>
</form>

<script>
(function () {
    var tbody    = document.getElementById('itemsBody');
    var addBtn   = document.getElementById('addItemBtn');
    var rowIndex = tbody.querySelectorAll('.item-row').length;

    function bindRow(row) {
        row.querySelector('.rm-item').addEventListener('click', function () {
            if (tbody.querySelectorAll('.item-row').length > 1) {
                row.remove();
            } else {
                row.querySelectorAll('input').forEach(function (i) { i.value = ''; });
            }
        });
    }

    tbody.querySelectorAll('.item-row').forEach(bindRow);

    addBtn.addEventListener('click', function () {
        var idx = rowIndex++;
        var tr  = document.createElement('tr');
        tr.className = 'item-row';
        tr.innerHTML =
            '<td class="item-qty"><input type="text" name="items[' + idx + '][quantity]" class="form-input" placeholder="e.g. 5"></td>' +
            '<td class="item-desc"><input type="text" name="items[' + idx + '][description]" class="form-input item-desc-inp" placeholder="Description of goods…"></td>' +
            '<td class="item-rm"><button type="button" class="rm-btn rm-item" title="Remove row">\xd7</button></td>';
        tbody.appendChild(tr);
        bindRow(tr);
        tr.querySelector('.item-desc-inp').focus();
    });

    // Auto-fill company header from a saved business
    var picker = document.getElementById('biz_picker');
    if (picker) {
        picker.addEventListener('change', function () {
            var opt = picker.options[picker.selectedIndex];
            if (!opt || !opt.value) return;
            var set = function (id, val) { var el = document.getElementById(id); if (el) el.value = val || ''; };
            set('company_name',    opt.getAttribute('data-name'));
            set('company_address', opt.getAttribute('data-address'));
            set('company_phone',   opt.getAttribute('data-phone'));
            set('company_email',   opt.getAttribute('data-email'));
            set('company_vat',     opt.getAttribute('data-vat'));
            set('company_tax_id',  opt.getAttribute('data-tax'));
        });
    }

    // "Clear" links for the optional radio groups
    function clearRadios(name) {
        document.querySelectorAll('input[name="' + name + '"]').forEach(function (r) { r.checked = false; });
    }
    var ca = document.getElementById('clearAccount');
    if (ca) ca.addEventListener('click', function (e) { e.preventDefault(); clearRadios('account_type'); });
    var cd = document.getElementById('clearDelivery');
    if (cd) cd.addEventListener('click', function (e) { e.preventDefault(); clearRadios('delivery_by'); });
})();
</script>
