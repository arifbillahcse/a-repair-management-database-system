<?php
$pageTitle = 'New Invoice — Repair #' . $repair['repair_id'];
require VIEWS_PATH . '/layouts/header.php';

$taxPct     = (float)($_ENV['DEFAULT_TAX_PCT'] ?? DEFAULT_TAX_PCT);
$dateToday  = date('Y-m-d');
$dateDue    = date('Y-m-d', strtotime('+30 days'));

// Pre-populate items from repair
$preItems = [];
if (!empty($repair['actual_amount']) && (float)$repair['actual_amount'] > 0) {
    $preItems[] = [
        'description' => 'Repair: ' . ($repair['device_brand'] ? $repair['device_brand'] . ' ' : '') . ($repair['device_model'] ?? ''),
        'quantity'    => 1,
        'unit_price'  => (float)$repair['actual_amount'],
        'discount_pct'=> 0,
        'tax_pct'     => $taxPct,
    ];
}
if (!empty($repair['deposit_paid']) && (float)$repair['deposit_paid'] > 0) {
    $preItems[] = [
        'description' => 'Deposit previously paid',
        'quantity'    => 1,
        'unit_price'  => -(float)$repair['deposit_paid'],
        'discount_pct'=> 0,
        'tax_pct'     => 0,
    ];
}
?>
<style>
.inv-grid{display:grid;grid-template-columns:1fr;gap:1.5rem}
@media(min-width:960px){.inv-grid{grid-template-columns:1fr 340px}}
.items-table{width:100%;border-collapse:collapse}
.items-table th{padding:.5rem .6rem;font-size:.72rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid var(--border);text-align:left}
.items-table td{padding:.4rem .5rem;border-bottom:1px solid var(--border);vertical-align:top}
.items-table tr:last-child td{border-bottom:none}
.item-desc-input{width:100%;min-width:180px}
.item-num-input{width:90px}
.item-small-input{width:75px}
.line-total-cell{text-align:right;font-size:.85rem;font-weight:500;white-space:nowrap;padding-top:.7rem}
.remove-row-btn{background:none;border:none;cursor:pointer;color:var(--text-muted);padding:0;line-height:1;font-size:1.1rem}
.remove-row-btn:hover{color:var(--error)}
.totals-table{width:100%;border-collapse:collapse}
.totals-table td{padding:.45rem .75rem;font-size:.88rem;border-bottom:1px solid var(--border)}
.totals-table tr:last-child td{border-bottom:none;font-weight:700;font-size:.95rem;padding-top:.65rem}
.totals-table .t-label{color:var(--text-secondary)}
.totals-table .t-val{text-align:right}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">New Invoice</h1>
        <p class="page-subtitle">
            For repair <a href="<?= BASE_URL ?>/repairs/<?= $repair['repair_id'] ?>" style="color:var(--accent)">#<?= $repair['repair_id'] ?></a>
            &nbsp;·&nbsp; <?= Utils::e($repair['customer_name'] ?? '') ?>
        </p>
    </div>
    <a href="<?= BASE_URL ?>/repairs/<?= $repair['repair_id'] ?>" class="btn btn-secondary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
        </svg>Back
    </a>
</div>

<form method="POST" action="<?= BASE_URL ?>/invoices" id="invoiceForm">
    <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken) ?>">
    <input type="hidden" name="repair_id"    value="<?= (int)$repair['repair_id'] ?>">
    <input type="hidden" name="customer_id"  value="<?= (int)$repair['customer_id'] ?>">
    <input type="hidden" name="invoice_number" value="<?= Utils::e($invoiceNumber) ?>">

    <div class="inv-grid">

        <!-- ── Left: line items ─────────────────────────────────────── -->
        <div>

            <!-- Invoice meta -->
            <div class="card" style="margin-bottom:1.25rem">
                <div class="card-header"><h2 class="card-title">Invoice Details</h2></div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;flex-wrap:wrap">
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">Invoice Number</label>
                            <input type="text" class="form-input" value="<?= Utils::e($invoiceNumber) ?>" readonly
                                   style="background:var(--bg-tertiary);font-family:var(--font-mono)">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" for="invoiceDate">Invoice Date <span class="required">*</span></label>
                            <input type="date" id="invoiceDate" name="invoice_date" class="form-input"
                                   value="<?= $dateToday ?>" required>
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" for="dueDate">Due Date</label>
                            <input type="date" id="dueDate" name="due_date" class="form-input"
                                   value="<?= $dateDue ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Line items -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Line Items</h2>
                    <button type="button" id="addItem" class="btn btn-sm btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px" aria-hidden="true">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>Add Row
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="items-table" id="itemsTable">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th style="width:80px">Qty</th>
                                <th style="width:110px">Unit Price (€)</th>
                                <th style="width:75px">Disc. %</th>
                                <th style="width:75px">Tax %</th>
                                <th style="width:100px;text-align:right">Total</th>
                                <th style="width:32px"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <?php foreach ($preItems as $i => $item): ?>
                            <tr class="item-row">
                                <td><input type="text" name="items[<?= $i ?>][description]" class="form-input item-desc-input" value="<?= Utils::e($item['description']) ?>" required placeholder="Description…"></td>
                                <td><input type="number" name="items[<?= $i ?>][quantity]"     class="form-input item-num-input item-qty"   value="<?= $item['quantity'] ?>"    step="0.001" min="0.001"></td>
                                <td><input type="number" name="items[<?= $i ?>][unit_price]"   class="form-input item-num-input item-price" value="<?= $item['unit_price'] ?>"  step="0.01"></td>
                                <td><input type="number" name="items[<?= $i ?>][discount_pct]" class="form-input item-small-input item-disc" value="<?= $item['discount_pct'] ?>" step="0.1" min="0" max="100"></td>
                                <td><input type="number" name="items[<?= $i ?>][tax_pct]"      class="form-input item-small-input item-tax"  value="<?= $item['tax_pct'] ?>"    step="0.1" min="0" max="100"></td>
                                <td class="line-total-cell" data-line-total>
                                    <?= Utils::formatCurrency($item['unit_price'] * $item['quantity'] * (1 - $item['discount_pct'] / 100)) ?>
                                </td>
                                <td><button type="button" class="remove-row-btn" title="Remove row">&#x2715;</button></td>
                            </tr>
                            <?php endforeach; ?>
                            <!-- Empty row if no pre-items -->
                            <?php if (empty($preItems)): ?>
                            <tr class="item-row">
                                <td><input type="text" name="items[0][description]" class="form-input item-desc-input" placeholder="Description…" required></td>
                                <td><input type="number" name="items[0][quantity]"     class="form-input item-num-input item-qty"   value="1"    step="0.001" min="0.001"></td>
                                <td><input type="number" name="items[0][unit_price]"   class="form-input item-num-input item-price" value=""     step="0.01"></td>
                                <td><input type="number" name="items[0][discount_pct]" class="form-input item-small-input item-disc" value="0"   step="0.1" min="0" max="100"></td>
                                <td><input type="number" name="items[0][tax_pct]"      class="form-input item-small-input item-tax"  value="<?= $taxPct ?>" step="0.1" min="0" max="100"></td>
                                <td class="line-total-cell" data-line-total>€0.00</td>
                                <td><button type="button" class="remove-row-btn" title="Remove row">&#x2715;</button></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ── Right: totals + notes ────────────────────────────────── -->
        <div style="display:flex;flex-direction:column;gap:1.25rem">

            <!-- Client (read-only) -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Bill To</h2>
                    <a href="<?= BASE_URL ?>/customers/<?= $repair['customer_id'] ?>" class="btn btn-xs btn-secondary">Profile</a>
                </div>
                <div class="card-body" style="font-size:.86rem;line-height:1.7;color:var(--text-secondary)">
                    <strong style="color:var(--text-primary)"><?= Utils::e($repair['customer_name'] ?? '') ?></strong><br>
                    <?php if (!empty($repair['customer_phone'])): ?>
                    <?= Utils::e($repair['customer_phone']) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($repair['customer_email'])): ?>
                    <?= Utils::e($repair['customer_email']) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($repair['customer_address'])): ?>
                    <?= Utils::e($repair['customer_address']) ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tax rate -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Tax</h2></div>
                <div class="card-body">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="taxPct">Default Tax Rate (%)</label>
                        <input type="number" id="taxPct" name="tax_percentage" class="form-input"
                               value="<?= $taxPct ?>" step="0.1" min="0" max="100"
                               id="globalTaxPct">
                        <p style="font-size:.72rem;color:var(--text-muted);margin-top:.35rem">
                            Applied per line item unless overridden.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Live totals -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Totals</h2></div>
                <div class="card-body" style="padding:0">
                    <table class="totals-table">
                        <tr><td class="t-label">Subtotal</td><td class="t-val" id="totSubtotal">€0.00</td></tr>
                        <tr><td class="t-label">Tax</td><td class="t-val" id="totTax">€0.00</td></tr>
                        <tr><td class="t-label">Total</td><td class="t-val" id="totTotal">€0.00</td></tr>
                    </table>
                </div>
            </div>

            <!-- Notes -->
            <div class="card">
                <div class="card-header"><h2 class="card-title">Notes</h2></div>
                <div class="card-body">
                    <textarea name="notes" class="form-input" rows="3"
                              placeholder="Payment terms, bank details, additional info…"></textarea>
                </div>
            </div>

            <!-- Actions -->
            <div style="display:flex;gap:.75rem;flex-direction:column">
                <button type="submit" class="btn btn-primary" style="justify-content:center">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px" aria-hidden="true">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>Create Invoice
                </button>
                <a href="<?= BASE_URL ?>/repairs/<?= $repair['repair_id'] ?>" class="btn btn-secondary" style="justify-content:center">Cancel</a>
            </div>

        </div>
    </div>
</form>

<script>
(function () {
    var itemIdx  = <?= max(count($preItems), 1) ?>;
    var defaultTax = <?= $taxPct ?>;

    // ── Add row ────────────────────────────────────────────────────────────
    document.getElementById('addItem').addEventListener('click', function () {
        var i    = itemIdx++;
        var tr   = document.createElement('tr');
        tr.className = 'item-row';
        tr.innerHTML =
            '<td><input type="text" name="items[' + i + '][description]" class="form-input item-desc-input" placeholder="Description…" required></td>' +
            '<td><input type="number" name="items[' + i + '][quantity]"     class="form-input item-num-input item-qty"   value="1"    step="0.001" min="0.001"></td>' +
            '<td><input type="number" name="items[' + i + '][unit_price]"   class="form-input item-num-input item-price" value=""     step="0.01"></td>' +
            '<td><input type="number" name="items[' + i + '][discount_pct]" class="form-input item-small-input item-disc" value="0"   step="0.1" min="0" max="100"></td>' +
            '<td><input type="number" name="items[' + i + '][tax_pct]"      class="form-input item-small-input item-tax"  value="' + defaultTax + '" step="0.1" min="0" max="100"></td>' +
            '<td class="line-total-cell" data-line-total>€0.00</td>' +
            '<td><button type="button" class="remove-row-btn" title="Remove">&#x2715;</button></td>';
        document.getElementById('itemsBody').appendChild(tr);
        attachRowListeners(tr);
        recalc();
    });

    // ── Remove row ─────────────────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        if (!e.target.classList.contains('remove-row-btn')) return;
        var row = e.target.closest('tr');
        if (document.querySelectorAll('#itemsBody .item-row').length <= 1) return;
        row.remove();
        recalc();
    });

    // ── Attach listeners to a row ──────────────────────────────────────────
    function attachRowListeners(row) {
        row.querySelectorAll('.item-qty,.item-price,.item-disc,.item-tax').forEach(function (inp) {
            inp.addEventListener('input', recalc);
        });
    }

    // Attach to pre-existing rows
    document.querySelectorAll('#itemsBody .item-row').forEach(attachRowListeners);

    // ── Recalculate totals ─────────────────────────────────────────────────
    function fmt(n) { return '€' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); }

    function recalc() {
        var subtotal = 0, totalTax = 0;
        document.querySelectorAll('#itemsBody .item-row').forEach(function (row) {
            var qty   = parseFloat(row.querySelector('.item-qty').value)   || 0;
            var price = parseFloat(row.querySelector('.item-price').value) || 0;
            var disc  = parseFloat(row.querySelector('.item-disc').value)  || 0;
            var tax   = parseFloat(row.querySelector('.item-tax').value)   || 0;
            var net   = qty * price * (1 - disc / 100);
            var taxAmt = net * tax / 100;
            subtotal += net;
            totalTax += taxAmt;
            var cell = row.querySelector('[data-line-total]');
            if (cell) cell.textContent = fmt(net);
        });
        document.getElementById('totSubtotal').textContent = fmt(subtotal);
        document.getElementById('totTax').textContent      = fmt(totalTax);
        document.getElementById('totTotal').textContent    = fmt(subtotal + totalTax);
    }

    recalc();
})();
</script>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
