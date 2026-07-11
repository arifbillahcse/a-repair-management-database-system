<?php
$pageTitle = 'New Sale';
require VIEWS_PATH . '/layouts/header.php';

$taxPct    = (float)($fd['tax_percentage'] ?? DEFAULT_TAX_PCT);
$dateToday = $fd['sale_date'] ?? date('Y-m-d');
?>
<style>
.sale-grid{display:grid;grid-template-columns:1fr;gap:1.5rem}
@media(min-width:960px){.sale-grid{grid-template-columns:1fr 340px}}
.items-table{width:100%;border-collapse:collapse}
.items-table th{padding:.5rem .6rem;font-size:.72rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid var(--border);text-align:left}
.items-table td{padding:.4rem .5rem;border-bottom:1px solid var(--border);vertical-align:top}
.items-table tr:last-child td{border-bottom:none}
.item-prod-select{min-width:170px;max-width:220px}
.item-desc-input{width:100%;min-width:150px}
.item-num-input{width:85px}
.item-small-input{width:70px}
.line-total-cell{text-align:right;font-size:.85rem;font-weight:500;white-space:nowrap;padding-top:.7rem}
.remove-row-btn{background:none;border:none;cursor:pointer;color:var(--text-muted);padding:0;line-height:1;font-size:1.1rem}
.remove-row-btn:hover{color:var(--error)}
.stock-hint{font-size:.68rem;color:var(--text-muted);margin-top:.15rem;white-space:nowrap}
.stock-hint.err{color:var(--error)}
.totals-table{width:100%;border-collapse:collapse}
.totals-table td{padding:.45rem .75rem;font-size:.88rem;border-bottom:1px solid var(--border)}
.totals-table tr:last-child td{border-bottom:none;font-weight:700;font-size:.95rem;padding-top:.65rem}
.totals-table .t-label{color:var(--text-secondary)}
.totals-table .t-val{text-align:right}
.ac-wrap{position:relative}
.ac-list{position:absolute;top:100%;left:0;right:0;z-index:50;background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius);max-height:220px;overflow-y:auto;box-shadow:0 8px 24px rgba(0,0,0,.25);display:none}
.ac-item{padding:.5rem .75rem;cursor:pointer;font-size:.84rem;border-bottom:1px solid var(--border)}
.ac-item:last-child{border-bottom:none}
.ac-item:hover,.ac-item.hover{background:var(--bg-tertiary)}
.ac-sub{font-size:.72rem;color:var(--text-muted)}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">New Sale</h1>
        <p class="page-subtitle">Sell products directly from stock</p>
    </div>
    <a href="<?= BASE_URL ?>/sales" class="btn btn-secondary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
        </svg>Back
    </a>
</div>

<?php if (!empty($errors['items'])): ?>
<div class="flash flash-error" style="margin-bottom:1rem"><?= Utils::e($errors['items']) ?></div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>/sales" id="saleForm">
    <input type="hidden" name="csrf_token"  value="<?= Utils::e($csrfToken) ?>">
    <input type="hidden" name="sale_number" value="<?= Utils::e($saleNumber) ?>">
    <input type="hidden" name="customer_id" id="customerId" value="<?= (int)($fd['customer_id'] ?? 0) ?: '' ?>">

    <div class="sale-grid">

        <!-- ── Left: meta + line items ─────────────────────────────── -->
        <div>

            <div class="card" style="margin-bottom:1.25rem">
                <div class="card-header"><h2 class="card-title">Sale Details</h2></div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label">Sale Number</label>
                            <input type="text" class="form-input" value="<?= Utils::e($saleNumber) ?>" readonly
                                   style="background:var(--bg-tertiary);font-family:var(--font-mono)">
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label class="form-label" for="saleDate">Sale Date <span class="required">*</span></label>
                            <input type="date" id="saleDate" name="sale_date" class="form-input"
                                   value="<?= Utils::e($dateToday) ?>" required>
                        </div>
                    </div>
                    <?php $businesses = $businesses ?? []; $defaultBiz = $defaultBiz ?? null;
                          $selBiz = (int)($fd['business_id'] ?? ($defaultBiz['business_id'] ?? 0)); ?>
                    <?php if (!empty($businesses)): ?>
                    <div class="form-group" style="margin-top:1rem;margin-bottom:0">
                        <label class="form-label" for="businessId">Issued By (Company)</label>
                        <select id="businessId" name="business_id" class="form-input">
                            <?php foreach ($businesses as $b): ?>
                            <option value="<?= (int)$b['business_id'] ?>" <?= $selBiz === (int)$b['business_id'] ? 'selected' : '' ?>>
                                <?= Utils::e($b['name']) ?><?= $b['is_default'] ? ' (default)' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p style="font-size:.72rem;color:var(--text-muted);margin-top:.35rem">
                            This company's name, address and VAT appear on the printed receipt.
                            <a href="<?= BASE_URL ?>/admin/businesses" target="_blank" style="color:var(--accent)">Manage companies</a>
                        </p>
                    </div>
                    <?php endif; ?>

                    <div class="form-group" style="margin-top:1rem;margin-bottom:0">
                        <label class="form-label" for="custSearch">Customer (optional)</label>
                        <div class="ac-wrap">
                            <input type="text" id="custSearch" name="customer_name" class="form-input" autocomplete="off"
                                   placeholder="Type to search clients, or leave empty for walk-in…"
                                   value="<?= Utils::e($fd['customer_name'] ?? '') ?>">
                            <div class="ac-list" id="acList"></div>
                        </div>
                        <p style="font-size:.72rem;color:var(--text-muted);margin-top:.35rem" id="custHint">
                            Pick from the list to link the sale to a client profile, or type any name for a walk-in sale.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Line items -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Items</h2>
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
                                <th>Product</th>
                                <th>Description</th>
                                <th style="width:75px">Qty</th>
                                <th style="width:100px">Price (€)</th>
                                <th style="width:70px">Disc. %</th>
                                <th style="width:95px;text-align:right">Total</th>
                                <th style="width:32px"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody"><!-- rows injected by JS --></tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ── Right: totals + payment ─────────────────────────────── -->
        <div style="display:flex;flex-direction:column;gap:1.25rem">

            <div class="card">
                <div class="card-header"><h2 class="card-title">Tax</h2></div>
                <div class="card-body">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="taxPct">Tax Rate (%)</label>
                        <input type="number" id="taxPct" name="tax_percentage" class="form-input"
                               value="<?= $taxPct ?>" step="0.1" min="0" max="100">
                        <p style="font-size:.72rem;color:var(--text-muted);margin-top:.35rem">
                            Applied to the subtotal.
                        </p>
                    </div>
                </div>
            </div>

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

            <div class="card">
                <div class="card-header"><h2 class="card-title">Payment</h2></div>
                <div class="card-body">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="amountPaid">Amount Paid Now (€)</label>
                        <input type="number" id="amountPaid" name="amount_paid" class="form-input"
                               value="" step="0.01" min="0" placeholder="0.00">
                        <p style="font-size:.72rem;color:var(--text-muted);margin-top:.35rem">
                            Leave empty to record as unpaid. You can record payment later.
                        </p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">Notes</h2></div>
                <div class="card-body">
                    <textarea name="notes" class="form-input" rows="3"
                              placeholder="Optional notes…"><?= Utils::e($fd['notes'] ?? '') ?></textarea>
                </div>
            </div>

            <div style="display:flex;gap:.75rem;flex-direction:column">
                <button type="submit" class="btn btn-primary" style="justify-content:center">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px" aria-hidden="true">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>Record Sale
                </button>
                <a href="<?= BASE_URL ?>/sales" class="btn btn-secondary" style="justify-content:center">Cancel</a>
            </div>

        </div>
    </div>
</form>

<script>
(function () {
    var PRODUCTS = <?= json_encode(array_map(fn($p) => [
        'id'    => (int)$p['product_id'],
        'name'  => $p['name'],
        'sku'   => $p['sku'],
        'price' => (float)$p['selling_price'],
        'stock' => (int)$p['quantity_on_hand'],
    ], $products), JSON_UNESCAPED_UNICODE) ?>;

    var itemIdx = 0;

    function productOptions(selectedId) {
        var html = '<option value="">— Custom item —</option>';
        PRODUCTS.forEach(function (p) {
            html += '<option value="' + p.id + '" data-price="' + p.price + '" data-stock="' + p.stock + '"' +
                (selectedId === p.id ? ' selected' : '') +
                (p.stock === 0 ? ' disabled' : '') + '>' +
                p.name.replace(/</g, '&lt;') + (p.sku ? ' [' + p.sku + ']' : '') +
                ' (' + p.stock + ')' +
                '</option>';
        });
        return html;
    }

    function addRow() {
        var i  = itemIdx++;
        var tr = document.createElement('tr');
        tr.className = 'item-row';
        tr.innerHTML =
            '<td>' +
                '<select name="items[' + i + '][product_id]" class="form-input item-prod-select item-prod">' + productOptions(0) + '</select>' +
                '<div class="stock-hint" data-stock-hint></div>' +
            '</td>' +
            '<td><input type="text" name="items[' + i + '][description]" class="form-input item-desc-input" placeholder="Description…" required></td>' +
            '<td><input type="number" name="items[' + i + '][quantity]" class="form-input item-num-input item-qty" value="1" step="1" min="1"></td>' +
            '<td><input type="number" name="items[' + i + '][unit_price]" class="form-input item-num-input item-price" value="" step="0.01" min="0"></td>' +
            '<td><input type="number" name="items[' + i + '][discount_pct]" class="form-input item-small-input item-disc" value="0" step="0.1" min="0" max="100"></td>' +
            '<td class="line-total-cell" data-line-total>€0.00</td>' +
            '<td><button type="button" class="remove-row-btn" title="Remove row">&#x2715;</button></td>';
        document.getElementById('itemsBody').appendChild(tr);
        attachRow(tr);
        recalc();
    }

    function attachRow(row) {
        row.querySelector('.item-prod').addEventListener('change', function () {
            var opt   = this.options[this.selectedIndex];
            var desc  = row.querySelector('.item-desc-input');
            var price = row.querySelector('.item-price');
            var hint  = row.querySelector('[data-stock-hint]');
            if (this.value) {
                desc.value  = opt.textContent.replace(/\s*\(\d+\)\s*$/, '').trim();
                price.value = opt.dataset.price;
                hint.textContent = opt.dataset.stock + ' in stock';
                hint.classList.remove('err');
                row.querySelector('.item-qty').max = opt.dataset.stock;
            } else {
                hint.textContent = '';
                row.querySelector('.item-qty').removeAttribute('max');
            }
            checkStock(row);
            recalc();
        });
        row.querySelectorAll('.item-qty,.item-price,.item-disc').forEach(function (inp) {
            inp.addEventListener('input', function () { checkStock(row); recalc(); });
        });
    }

    function checkStock(row) {
        var sel  = row.querySelector('.item-prod');
        var hint = row.querySelector('[data-stock-hint]');
        if (!sel.value) return;
        var opt   = sel.options[sel.selectedIndex];
        var stock = parseInt(opt.dataset.stock, 10);
        var qty   = parseInt(row.querySelector('.item-qty').value, 10) || 0;
        if (qty > stock) {
            hint.textContent = 'Only ' + stock + ' in stock!';
            hint.classList.add('err');
        } else {
            hint.textContent = stock + ' in stock';
            hint.classList.remove('err');
        }
    }

    document.getElementById('addItem').addEventListener('click', addRow);

    document.addEventListener('click', function (e) {
        if (!e.target.classList.contains('remove-row-btn')) return;
        if (document.querySelectorAll('#itemsBody .item-row').length <= 1) return;
        e.target.closest('tr').remove();
        recalc();
    });

    function fmt(n) { return '€' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); }

    function recalc() {
        var subtotal = 0;
        document.querySelectorAll('#itemsBody .item-row').forEach(function (row) {
            var qty   = parseFloat(row.querySelector('.item-qty').value)   || 0;
            var price = parseFloat(row.querySelector('.item-price').value) || 0;
            var disc  = parseFloat(row.querySelector('.item-disc').value)  || 0;
            var net   = qty * price * (1 - disc / 100);
            subtotal += net;
            row.querySelector('[data-line-total]').textContent = fmt(net);
        });
        var taxPct = parseFloat(document.getElementById('taxPct').value) || 0;
        var tax    = subtotal * taxPct / 100;
        document.getElementById('totSubtotal').textContent = fmt(subtotal);
        document.getElementById('totTax').textContent      = fmt(tax);
        document.getElementById('totTotal').textContent    = fmt(subtotal + tax);
    }

    document.getElementById('taxPct').addEventListener('input', recalc);

    // First row
    addRow();

    // ── Customer autocomplete ──────────────────────────────────────────────
    var custInput = document.getElementById('custSearch');
    var custId    = document.getElementById('customerId');
    var acList    = document.getElementById('acList');
    var acTimer   = null;

    custInput.addEventListener('input', function () {
        custId.value = '';                       // typing breaks the link
        clearTimeout(acTimer);
        var q = custInput.value.trim();
        if (q.length < 2) { acList.style.display = 'none'; return; }
        acTimer = setTimeout(function () {
            fetch('<?= BASE_URL ?>/api/customers/autocomplete?q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(function (rows) {
                    if (!rows.length) { acList.style.display = 'none'; return; }
                    acList.innerHTML = rows.map(function (c) {
                        return '<div class="ac-item" data-id="' + c.customer_id + '" data-name="' +
                            c.full_name.replace(/"/g, '&quot;') + '">' +
                            c.full_name.replace(/</g, '&lt;') +
                            '<div class="ac-sub">' + [c.phone, c.city].filter(Boolean).join(' · ').replace(/</g, '&lt;') + '</div>' +
                            '</div>';
                    }).join('');
                    acList.style.display = 'block';
                })
                .catch(function () { acList.style.display = 'none'; });
        }, 250);
    });

    acList.addEventListener('click', function (e) {
        var item = e.target.closest('.ac-item');
        if (!item) return;
        custInput.value = item.dataset.name;
        custId.value    = item.dataset.id;
        acList.style.display = 'none';
        document.getElementById('custHint').textContent = 'Linked to client profile ✓';
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.ac-wrap')) acList.style.display = 'none';
    });
})();
</script>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
