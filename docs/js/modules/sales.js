/**
 * modules/sales.js — the counter-sale till.
 * Pick a client, add items from the catalogue, take payment. The sale writes
 * an ordinary invoice (repair_id null, source 'sale') and moves stock.
 */
'use strict';

const Sales = {

    /* ── GET /sales ───────────────────────────────────────────────────────── */

    index({ query }) {
        if (!Auth.requireAuth()) return;

        const filters = {
            search: query.search ?? '', status: query.status ?? '',
            sort: query.sort ?? 'invoice_date', dir: query.dir ?? 'DESC',
        };
        const page   = Utils.intVal(query.page) || 1;
        const result = Sale.getAll(filters, page);
        const s      = Sale.stats();

        Layout.render(`
            ${UI.pageHeader(L.saleMany,
                `${Utils.numberFormat(s.count_total)} recorded &nbsp;·&nbsp;
                 ${Utils.numberFormat(s.count_today)} today &nbsp;·&nbsp;
                 ${Utils.formatCurrency(s.total_month)} this month`,
                `<a href="#/sales/new" class="btn btn-primary">${Icon.plus('')} ${Utils.e(L.saleNew)}</a>`
            )}

            <div class="stats-grid">
                ${UI.statCard({ label: 'Sales this month', value: Utils.numberFormat(s.count_month),        icon: 'invoice', tone: 'accent' })}
                ${UI.statCard({ label: 'Revenue (month)',  value: Utils.formatCurrencyShort(s.total_month), icon: 'money',   tone: 'green' })}
                ${UI.statCard({ label: 'Average sale',     value: Utils.formatCurrencyShort(s.avg_sale),    icon: 'chart',   tone: 'blue' })}
                ${UI.statCard({ label: 'All-time',         value: Utils.formatCurrencyShort(s.total_all),   icon: 'box',     tone: 'orange' })}
            </div>

            <div class="card">
                <div class="filter-bar">
                    <div class="search-input-wrap">
                        ${Icon.search('search-input-icon')}
                        <input class="form-input" id="searchInput" type="search"
                               placeholder="${Utils.e(`Search receipt number or ${L.clientOne.toLowerCase()}…`)}"
                               value="${Utils.e(filters.search)}" autocomplete="off">
                    </div>
                    ${filters.search ? '<button class="btn btn-secondary btn-sm" id="clearFilters">Clear</button>' : ''}
                </div>
                <div id="listMount"></div>
            </div>
        `);

        DataTable.render({
            mount: '#listMount',
            rows: result.data,
            pagination: result.pagination,
            sort: filters.sort, dir: filters.dir,
            columns: [
                { key: 'invoice_number', label: 'Receipt', sortable: true,
                  render: i => `<a class="table-link" href="#/invoices/${i.invoice_id}">${Utils.e(i.invoice_number)}</a>` },
                { key: 'customer_name', label: L.clientOne, sortable: true,
                  render: i => `<a class="cust-name-link" href="#/customers/${i.customer_id}">${Utils.e(Utils.truncate(i.customer_name ?? '—', 24))}</a>` },
                { key: 'invoice_date', label: 'Date', sortable: true, hideOnTablet: true,
                  render: i => Utils.formatDate(i.invoice_date) },
                { key: 'items', label: 'Items', align: 'right', hideOnTablet: true,
                  render: i => Utils.numberFormat(Utils.sum(Invoice.getItems(i.invoice_id), 'quantity')) },
                { key: 'total_amount', label: 'Total', sortable: true, align: 'right',
                  render: i => `<strong>${Utils.formatCurrency(i.total_amount)}</strong>` },
                { key: 'status', label: 'Payment', sortable: true,
                  render: i => i.balance > 0
                    ? `<span class="badge badge-orange">${Utils.formatCurrency(i.balance)} due</span>`
                    : '<span class="badge badge-green">Paid</span>' },
            ],
            actions: i =>
                DataTable.act.view(`#/invoices/${i.invoice_id}`) +
                DataTable.act.print(`#/invoices/${i.invoice_id}/print`) +
                (Auth.can('manager') ? DataTable.act.del(i.invoice_id) : ''),
            empty: {
                message: 'No counter sales recorded yet.',
                icon: 'box',
                action: `<a href="#/sales/new" class="btn btn-primary">${Utils.e(L.saleNew)}</a>`,
            },
            onSort: (col, dir) => Router.setQuery({ sort: col, dir, page: 1 }),
            onPage: p => Router.setQuery({ page: p }),
        });

        const search = document.getElementById('searchInput');
        search.oninput = UI.debounce(() => Router.setQuery({ search: search.value, page: 1 }), 350);
        document.getElementById('clearFilters')?.addEventListener('click', () => Router.go('/sales'));

        DataTable.bindDelete('#listMount', {
            message: id => `Reverse sale ${DB.findById('invoices', id)?.invoice_number}? Stock goes back to the catalogue.`,
            onConfirm: id => { Sale.delete(id); Toast.success('Sale reversed, stock restored.'); Router.reload(); },
        });

        if (filters.search) { search.focus(); search.setSelectionRange(search.value.length, search.value.length); }
    },

    /* ── GET /sales/new — the till ────────────────────────────────────────── */

    create() {
        if (!Auth.requireAuth()) return;

        const stock = Product.sellable();

        Layout.render(`
            ${UI.backLink('#/sales', `All ${L.saleMany.toLowerCase()}`)}
            ${UI.pageHeader(L.saleNew, `Sell items straight from the ${L.catalogueMany.toLowerCase()} — no ${L.jobLower} needed.`)}

            <div class="dashboard-grid">
                <div class="dashboard-main">
                    <div class="card">
                        <div class="card-header"><h2 class="card-title">Add items</h2></div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label" for="productPicker">Catalogue</label>
                                <select class="form-select" id="productPicker">
                                    <option value="">Choose an item to add…</option>
                                    ${stock.map(p => `
                                        <option value="${p.product_id}" ${Product.isStocked(p) && Utils.intVal(p.stock_quantity) === 0 ? 'disabled' : ''}>
                                            ${Utils.e(p.name)} — ${Utils.formatCurrency(p.unit_price)}${
                                              Product.isStocked(p) ? ` (${p.stock_quantity} in stock)` : ''}
                                        </option>`).join('')}
                                </select>
                                <span class="form-hint">Out-of-stock parts cannot be added.</span>
                            </div>

                            <div class="table-responsive">
                                <table class="data-table items-table">
                                    <thead>
                                        <tr>
                                            <th>Item</th><th style="width:88px">Qty</th>
                                            <th style="width:120px">Unit price</th><th style="width:88px">Disc %</th>
                                            <th class="ta-right" style="width:118px">Amount</th><th style="width:40px"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="basketBody"></tbody>
                                </table>
                            </div>
                            <p class="text-muted small" id="basketEmpty">Nothing in the basket yet.</p>
                        </div>
                    </div>
                </div>

                <aside class="dashboard-aside">
                    <div class="card">
                        <div class="card-header"><h2 class="card-title">${Utils.e(L.clientOne)} &amp; payment</h2></div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label required" for="customer_search">${Utils.e(L.clientOne)}</label>
                                <div class="search-input-wrap">
                                    <input class="form-input" id="customer_search" autocomplete="off" placeholder="Type a name or phone…">
                                    <input type="hidden" id="customer_id">
                                    <div class="ac-dropdown" id="customer_ac" hidden></div>
                                </div>
                                <span class="field-error" id="err-customer_id"></span>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="payment">Payment</label>
                                <select class="form-select" id="payment">
                                    <option value="paid">Paid now</option>
                                    <option value="due">On account (unpaid)</option>
                                </select>
                            </div>

                            <div class="invoice-totals" id="saleTotals"></div>

                            <button class="btn btn-primary btn-full" id="completeSale">
                                ${Icon.check('')} Complete sale
                            </button>
                        </div>
                    </div>
                </aside>
            </div>
        `);

        Forms.customerAutocomplete({ input: 'customer_search', hidden: 'customer_id', dropdown: 'customer_ac' });

        const body = document.getElementById('basketBody');

        const readBasket = () => [...body.querySelectorAll('tr')].map(tr => ({
            product_id:   Utils.intVal(tr.dataset.pid),
            quantity:     Utils.floatVal(tr.querySelector('.it-qty').value),
            unit_price:   Utils.floatVal(tr.querySelector('.it-price').value),
            discount_pct: Utils.floatVal(tr.querySelector('.it-disc').value),
        }));

        const recalc = () => {
            const lines = readBasket();
            let subtotal = 0;
            body.querySelectorAll('tr').forEach((tr, i) => {
                const line = Invoice.lineTotal(lines[i]);
                subtotal += line;
                tr.querySelector('.it-total').textContent = Utils.formatCurrency(line);
            });
            subtotal = Utils.round2(subtotal);
            const tax   = Utils.round2(subtotal * DEFAULT_TAX_PCT / 100);
            const total = Utils.round2(subtotal + tax);

            document.getElementById('saleTotals').innerHTML =
                UI.field('Subtotal', Utils.formatCurrency(subtotal)) +
                UI.field(`VAT (${Utils.numberFormat(DEFAULT_TAX_PCT, 0)}%)`, Utils.formatCurrency(tax)) +
                UI.field('Total', `<strong class="total-big">${Utils.formatCurrency(total)}</strong>`, true);

            document.getElementById('basketEmpty').style.display = lines.length ? 'none' : '';
        };

        const addLine = p => {
            const existing = body.querySelector(`tr[data-pid="${p.product_id}"]`);
            if (existing) {                       // same item twice → bump the quantity
                const q = existing.querySelector('.it-qty');
                q.value = Utils.intVal(q.value) + 1;
                recalc();
                return;
            }
            const max = Product.isStocked(p) ? Utils.intVal(p.stock_quantity) : 9999;
            const tr = document.createElement('tr');
            tr.dataset.pid = p.product_id;
            tr.innerHTML = `
                <td>${Utils.e(p.name)}${Product.isStocked(p) ? `<span class="vat-sub">${p.stock_quantity} in stock</span>` : ''}</td>
                <td><input class="form-input it-qty" type="number" min="1" max="${max}" step="1" value="1"></td>
                <td><input class="form-input it-price" type="number" min="0" step="1" value="${Utils.floatVal(p.unit_price)}"></td>
                <td><input class="form-input it-disc" type="number" min="0" max="100" step="1" value="0"></td>
                <td class="ta-right it-total">${Utils.formatCurrency(0)}</td>
                <td><button type="button" class="act-btn act-btn-d it-del" aria-label="Remove">${Icon.trash('')}</button></td>`;
            body.appendChild(tr);

            tr.querySelectorAll('input').forEach(i => { i.oninput = recalc; });
            tr.querySelector('.it-qty').onchange = e => {
                if (Utils.intVal(e.target.value) > max) {
                    e.target.value = max;
                    Toast.warning(`Only ${max} × "${p.name}" in stock.`);
                }
                recalc();
            };
            tr.querySelector('.it-del').onclick = () => { tr.remove(); recalc(); };
            recalc();
        };

        document.getElementById('productPicker').onchange = e => {
            const p = Product.findById(e.target.value);
            if (p) addLine(p);
            e.target.value = '';
        };

        document.getElementById('completeSale').onclick = () => {
            const res = Sale.create({
                customer_id: document.getElementById('customer_id').value,
                lines: readBasket(),
                payment: document.getElementById('payment').value,
            });
            if (!res.ok) {
                if (/select a/i.test(res.error)) {
                    document.getElementById('customer_search').classList.add('input-error');
                    document.getElementById('err-customer_id').textContent = res.error;
                }
                return Toast.error(res.error);
            }
            Toast.success(`Sale ${res.invoice.invoice_number} completed — ${Utils.formatCurrency(res.invoice.total_amount)}.`);
            Router.go(`/invoices/${res.invoice_id}`);
        };

        recalc();
    },
};
