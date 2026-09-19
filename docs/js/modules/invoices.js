/**
 * modules/invoices.js — port of InvoiceController + views/invoices/*.php
 * Covers: list, detail, create (incl. from a repair), line-item editor with
 * live VAT maths, mark sent/paid, delete, print.
 */
'use strict';

const Invoices = {

    /* ── GET /invoices ────────────────────────────────────────────────────── */

    index({ query }) {
        if (!Auth.requireAuth()) return;

        const filters = {
            search: query.search ?? '', status: query.status ?? '',
            sort:   query.sort ?? 'invoice_date', dir: query.dir ?? 'DESC',
        };
        const page   = Utils.intVal(query.page) || 1;
        const result = Invoice.getAll(filters, page);
        const counts = Invoice.getStatusCounts();
        const stats  = Invoice.getMonthlyStats();

        Layout.render(`
            ${UI.pageHeader('Invoices',
                `${Utils.numberFormat(counts.total)} total &nbsp;·&nbsp;
                 <span class="text-danger">${Utils.formatCurrency(stats.outstanding)} outstanding</span>
                 ${counts.overdue ? `&nbsp;·&nbsp; <span style="color:var(--error)">${counts.overdue} overdue</span>` : ''}`,
                `<button class="btn btn-secondary" id="exportBtn">${Icon.download('')} Export CSV</button>
                 <a href="#/invoices/create" class="btn btn-primary">${Icon.plus('')} New Invoice</a>`
            )}

            <div class="stats-grid">
                ${UI.statCard({ label: 'Billed this month', value: Utils.formatCurrencyShort(stats.billed_month), icon: 'invoice', tone: 'accent' })}
                ${UI.statCard({ label: 'Collected',         value: Utils.formatCurrencyShort(stats.paid_month),   icon: 'money',   tone: 'green' })}
                ${UI.statCard({ label: 'Outstanding',       value: Utils.formatCurrencyShort(stats.outstanding),  icon: 'clock',   tone: 'orange' })}
                ${UI.statCard({ label: 'Overdue',           value: Utils.numberFormat(counts.overdue),            icon: 'alert',   tone: 'blue', link: '#/invoices?status=overdue' })}
            </div>

            <div class="card">
                ${UI.filterPills([
                    { value: '',               label: 'All',       count: counts.total },
                    { value: 'draft',          label: 'Draft',     count: counts.draft },
                    { value: 'sent',           label: 'Sent',      count: counts.sent },
                    { value: 'partially_paid', label: 'Partial',   count: counts.partially_paid },
                    { value: 'paid',           label: 'Paid',      count: counts.paid },
                    { value: 'overdue',        label: 'Overdue',   count: counts.overdue },
                ], filters.status, v => Router.setQuery({ status: v, page: 1 }))}

                <div class="filter-bar">
                    <div class="search-input-wrap">
                        ${Icon.search('search-input-icon')}
                        <input class="form-input" id="searchInput" type="search" placeholder="Search invoice number or client…"
                               value="${Utils.e(filters.search)}" autocomplete="off">
                    </div>
                    ${filters.search || filters.status ? '<button class="btn btn-secondary btn-sm" id="clearFilters">Clear</button>' : ''}
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
                { key: 'invoice_number', label: 'Invoice', sortable: true,
                  render: i => `<a class="table-link" href="#/invoices/${i.invoice_id}">${Utils.e(i.invoice_number)}</a>` },
                { key: 'customer_name', label: 'Client', sortable: true,
                  render: i => `<a class="cust-name-link" href="#/customers/${i.customer_id}">${Utils.e(Utils.truncate(i.customer_name ?? '—', 24))}</a>` },
                { key: 'invoice_date', label: 'Date', sortable: true, hideOnTablet: true,
                  render: i => Utils.formatDate(i.invoice_date) },
                { key: 'due_date', label: 'Due', sortable: true, hideOnTablet: true,
                  render: i => i.due_date
                    ? `<span class="${i.is_overdue ? 'text-danger' : ''}">${Utils.formatDate(i.due_date)}</span>` : '—' },
                { key: 'total_amount', label: 'Total', sortable: true, align: 'right',
                  render: i => Utils.formatCurrency(i.total_amount) },
                { key: 'balance', label: 'Balance', sortable: true, align: 'right',
                  render: i => i.balance > 0
                    ? `<span class="text-danger">${Utils.formatCurrency(i.balance)}</span>`
                    : '<span class="badge badge-green">Settled</span>' },
                { key: 'status', label: 'Status', sortable: true,
                  render: i => i.is_overdue ? Badge.invoice('overdue') : Badge.invoice(i.status) },
            ],
            actions: i =>
                DataTable.act.view(`#/invoices/${i.invoice_id}`) +
                DataTable.act.print(`#/invoices/${i.invoice_id}/print`) +
                (Auth.can('manager') ? DataTable.act.del(i.invoice_id) : ''),
            empty: {
                message: filters.search ? `No invoices match "${filters.search}".` : 'No invoices raised yet.',
                icon: 'invoice',
                action: '<a href="#/invoices/create" class="btn btn-primary">Raise the first invoice</a>',
            },
            onSort: (col, dir) => Router.setQuery({ sort: col, dir, page: 1 }),
            onPage: p => Router.setQuery({ page: p }),
        });

        const search = document.getElementById('searchInput');
        search.oninput = UI.debounce(() => Router.setQuery({ search: search.value, page: 1 }), 350);
        document.getElementById('clearFilters')?.addEventListener('click', () => Router.go('/invoices'));
        document.getElementById('exportBtn').onclick = () => this.export();
        UI.bindFilterPills();

        DataTable.bindDelete('#listMount', {
            message: id => {
                const i = DB.findById('invoices', id);
                return `Delete invoice ${i?.invoice_number}? This also removes its line items.`;
            },
            onConfirm: id => this.destroy(id),
        });

        if (filters.search) { search.focus(); search.setSelectionRange(search.value.length, search.value.length); }
    },

    /* ── GET /invoices/:id ────────────────────────────────────────────────── */

    show({ params }) {
        if (!Auth.requireAuth()) return;

        const inv = Invoice.findById(params.id);
        if (!inv) return AuthView.notFound(`/invoices/${params.id}`);
        const items = Invoice.getItems(inv.invoice_id);

        Layout.render(`
            ${UI.backLink('#/invoices', 'All invoices')}

            ${UI.pageHeader(inv.invoice_number,
                `${inv.is_overdue ? Badge.invoice('overdue') : Badge.invoice(inv.status)}
                 &nbsp;·&nbsp; ${Utils.e(inv.customer_name)}
                 &nbsp;·&nbsp; ${Utils.formatDate(inv.invoice_date)}`,
                `<a href="#/invoices/${inv.invoice_id}/print" class="btn btn-secondary">${Icon.print('')} Print</a>
                 ${inv.status === 'draft' ? `<button class="btn btn-secondary" id="sendBtn">${Icon.mail('')} Mark as sent</button>` : ''}
                 ${inv.balance > 0 && inv.status !== 'cancelled' ? `<button class="btn btn-primary" id="payBtn">${Icon.money('')} Record payment</button>` : ''}`
            )}

            <div class="dashboard-grid">
                <div class="dashboard-main">
                    <div class="card">
                        <div class="card-header"><h2 class="card-title">Line items</h2></div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Description</th><th class="ta-right">Qty</th>
                                        <th class="ta-right hide-t">Unit price</th>
                                        <th class="ta-right hide-t">Discount</th>
                                        <th class="ta-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${items.map(it => `
                                        <tr>
                                            <td>${Utils.e(it.description)}</td>
                                            <td class="ta-right">${Utils.numberFormat(it.quantity, it.quantity % 1 ? 2 : 0)}</td>
                                            <td class="ta-right hide-t">${Utils.formatCurrency(it.unit_price)}</td>
                                            <td class="ta-right hide-t">${it.discount_pct ? it.discount_pct + '%' : '—'}</td>
                                            <td class="ta-right">${Utils.formatCurrency(it.line_total)}</td>
                                        </tr>`).join('')}
                                </tbody>
                            </table>
                        </div>
                        <div class="invoice-totals">
                            ${UI.field('Subtotal', Utils.formatCurrency(inv.subtotal))}
                            ${UI.field(`VAT (${Utils.numberFormat(inv.tax_percentage, 0)}%)`, Utils.formatCurrency(inv.tax_amount))}
                            ${UI.field('Total', `<strong class="total-big">${Utils.formatCurrency(inv.total_amount)}</strong>`, true)}
                            ${UI.field('Paid', Utils.formatCurrency(inv.amount_paid))}
                            ${UI.field('Balance due', inv.balance > 0
                                ? `<strong class="text-danger">${Utils.formatCurrency(inv.balance)}</strong>`
                                : '<span class="badge badge-green">Settled</span>', true)}
                        </div>
                    </div>

                    ${inv.notes ? `
                    <div class="card">
                        <div class="card-header"><h2 class="card-title">Notes</h2></div>
                        <div class="card-body"><p class="note-text">${Utils.e(inv.notes)}</p></div>
                    </div>` : ''}
                </div>

                <aside class="dashboard-aside">
                    <div class="card">
                        <div class="card-header"><h2 class="card-title">Client</h2></div>
                        <div class="card-body">
                            ${UI.field('Name', `<a class="table-link" href="#/customers/${inv.customer_id}">${Utils.e(inv.customer_name)}</a>`, true)}
                            ${UI.field('Mobile', inv.customer_phone)}
                            ${UI.field('Email', inv.customer_email)}
                            ${UI.field('Address', inv.customer_address)}
                            ${UI.field('BIN', inv.customer_vat)}
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h2 class="card-title">Invoice</h2></div>
                        <div class="card-body">
                            ${UI.field('Issued', Utils.formatDate(inv.invoice_date))}
                            ${UI.field('Due', inv.due_date ? Utils.formatDate(inv.due_date) : '')}
                            ${UI.field('Repair job', inv.repair_id
                                ? `<a class="table-link" href="#/repairs/${inv.repair_id}">#${inv.repair_id} — ${Utils.e(inv.device_model ?? '')}</a>` : '', true)}
                            ${UI.field('Status', inv.is_overdue ? Badge.invoice('overdue') : Badge.invoice(inv.status), true)}
                        </div>
                    </div>

                    ${Auth.can('manager') ? `
                    <div class="card card-danger">
                        <div class="card-header"><h2 class="card-title">Danger zone</h2></div>
                        <div class="card-body">
                            <button class="btn btn-danger btn-full" id="deleteBtn">${Icon.trash('')} Delete invoice</button>
                        </div>
                    </div>` : ''}
                </aside>
            </div>
        `);

        document.getElementById('sendBtn')?.addEventListener('click', () => {
            const res = Invoice.markAsSent(inv.invoice_id);
            if (!res.ok) return Toast.error(res.error);
            Toast.success('Invoice marked as sent.');
            Router.reload();
        });

        document.getElementById('payBtn')?.addEventListener('click', () => this.recordPayment(inv));
        document.getElementById('deleteBtn')?.addEventListener('click', () => this.destroy(inv.invoice_id, '/invoices'));
    },

    recordPayment(inv) {
        Modal.open({
            title: `Record payment — ${inv.invoice_number}`,
            body: `
                <p class="confirm-text">Balance due: <strong>${Utils.formatCurrency(inv.balance)}</strong></p>
                <div class="form-group">
                    <label class="form-label" for="payAmount">Amount received (${CURRENCY_SYMBOL})</label>
                    <input class="form-input" id="payAmount" type="number" min="0" step="1" value="${inv.balance}">
                </div>
                <p class="text-muted small">Leave as-is to settle the invoice in full.</p>`,
            footer: `
                <button class="btn btn-secondary" onclick="Modal.close()">Cancel</button>
                <button class="btn btn-primary" id="payConfirm">Record payment</button>`,
        });

        document.getElementById('payConfirm').onclick = () => {
            const amount = Utils.floatVal(document.getElementById('payAmount').value);
            if (amount <= 0) return Toast.error('Enter an amount greater than zero.');
            Invoice.markAsPaid(inv.invoice_id, amount);
            Modal.close();
            Toast.success('Payment recorded.');
            Router.reload();
        };
    },

    /* ── Create (blank, or prefilled from a repair) ───────────────────────── */

    create({ query }) {
        if (!Auth.requireAuth()) return;
        this._form(query.repair_id ? Invoice.draftFromRepair(query.repair_id) : null);
    },

    /** GET /repairs/:id/invoice — the "create invoice from this job" shortcut. */
    fromRepair({ params }) {
        if (!Auth.requireAuth()) return;
        const draft = Invoice.draftFromRepair(params.id);
        if (!draft) return AuthView.notFound(`/repairs/${params.id}/invoice`);
        this._form(draft);
    },

    _form(draft = null) {
        const today = Utils.toDbDate(new Date());
        const due   = new Date(); due.setDate(due.getDate() + 15);
        const preset = draft ? Customer.findById(draft.customer_id) : null;

        Layout.render(`
            ${UI.backLink('#/invoices', 'All invoices')}
            ${UI.pageHeader('New invoice',
                draft?.repair_id ? `Prefilled from repair #${draft.repair_id}.` : 'Raise an invoice for a client.')}

            <form id="invoiceForm" class="card" novalidate>
                <div class="card-body">
                    <div class="form-grid-2">

                        <div class="form-group form-col-full">
                            <label class="form-label required" for="customer_search">Client</label>
                            <div class="search-input-wrap">
                                <input class="form-input" id="customer_search" autocomplete="off" placeholder="Type a name, phone or email…">
                                <input type="hidden" id="customer_id" name="customer_id" value="${Utils.e(draft?.customer_id ?? '')}">
                                <div class="ac-dropdown" id="customer_ac" hidden></div>
                            </div>
                            <span class="field-error" id="err-customer_id"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="invoice_date">Invoice date</label>
                            <input class="form-input" id="invoice_date" name="invoice_date" type="date" value="${today}">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="due_date">Due date</label>
                            <input class="form-input" id="due_date" name="due_date" type="date" value="${Utils.toDbDate(due)}">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="tax_percentage">VAT %</label>
                            <input class="form-input" id="tax_percentage" name="tax_percentage" type="number"
                                   min="0" max="100" step="0.5" value="${DEFAULT_TAX_PCT}">
                            <span class="form-hint">Bangladesh standard rate is 15%.</span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="draft">Draft</option>
                                <option value="sent">Sent</option>
                            </select>
                        </div>

                        <input type="hidden" name="repair_id" value="${Utils.e(draft?.repair_id ?? '')}">

                        <div class="form-group form-col-full">
                            <label class="form-label" for="notes">Notes</label>
                            <textarea class="form-textarea" id="notes" name="notes" rows="2">${Utils.e(DB.setting('invoice_terms', ''))}</textarea>
                        </div>
                    </div>

                    <h3 class="form-section-title">Line items</h3>
                    <div class="table-responsive">
                        <table class="data-table items-table">
                            <thead>
                                <tr>
                                    <th>Description</th><th style="width:80px">Qty</th>
                                    <th style="width:120px">Unit price</th><th style="width:90px">Disc %</th>
                                    <th class="ta-right" style="width:120px">Amount</th><th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBody"></tbody>
                        </table>
                    </div>

                    <div class="items-actions">
                        <button type="button" class="btn btn-secondary btn-sm" id="addItem">${Icon.plus('')} Add line</button>
                        <select class="form-select filter-select" id="productPicker">
                            <option value="">Add from catalogue…</option>
                            ${DB.findAll('products', { status: 'active' }, 'name ASC').map(p =>
                                `<option value="${p.product_id}">${Utils.e(p.name)} — ${Utils.formatCurrency(p.unit_price)}</option>`).join('')}
                        </select>
                    </div>

                    <div class="invoice-totals" id="totalsBox"></div>
                </div>

                <div class="form-actions">
                    <a href="#/invoices" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create invoice</button>
                </div>
            </form>
        `);

        Forms.customerAutocomplete({
            input: 'customer_search', hidden: 'customer_id', dropdown: 'customer_ac',
            preset: preset ? { full_name: preset.full_name, customer_id: preset.customer_id } : null,
        });

        /* ── Line item editor ─────────────────────────────────────────────── */

        const body = document.getElementById('itemsBody');

        const addRow = (item = {}) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><input class="form-input it-desc" value="${Utils.e(item.description ?? '')}" placeholder="Part or service"></td>
                <td><input class="form-input it-qty"   type="number" min="0" step="1" value="${item.quantity ?? 1}"></td>
                <td><input class="form-input it-price" type="number" min="0" step="1" value="${item.unit_price ?? 0}"></td>
                <td><input class="form-input it-disc"  type="number" min="0" max="100" step="1" value="${item.discount_pct ?? 0}"></td>
                <td class="ta-right it-total">${Utils.formatCurrency(0)}</td>
                <td><button type="button" class="act-btn act-btn-d it-del" aria-label="Remove line">${Icon.trash('')}</button></td>`;
            body.appendChild(tr);

            tr.querySelectorAll('input').forEach(i => { i.oninput = recalc; });
            tr.querySelector('.it-del').onclick = () => { tr.remove(); recalc(); };
            recalc();
        };

        const readRows = () => [...body.querySelectorAll('tr')].map(tr => ({
            description:  tr.querySelector('.it-desc').value.trim(),
            quantity:     Utils.floatVal(tr.querySelector('.it-qty').value),
            unit_price:   Utils.floatVal(tr.querySelector('.it-price').value),
            discount_pct: Utils.floatVal(tr.querySelector('.it-disc').value),
        }));

        const recalc = () => {
            const rows   = readRows();
            const taxPct = Utils.floatVal(document.getElementById('tax_percentage').value);
            let subtotal = 0;

            body.querySelectorAll('tr').forEach((tr, i) => {
                const line = Invoice.lineTotal(rows[i]);
                subtotal += line;
                tr.querySelector('.it-total').textContent = Utils.formatCurrency(line);
            });

            subtotal = Utils.round2(subtotal);
            const tax   = Utils.round2(subtotal * taxPct / 100);
            const total = Utils.round2(subtotal + tax);

            document.getElementById('totalsBox').innerHTML =
                UI.field('Subtotal', Utils.formatCurrency(subtotal)) +
                UI.field(`VAT (${Utils.numberFormat(taxPct, 0)}%)`, Utils.formatCurrency(tax)) +
                UI.field('Total', `<strong class="total-big">${Utils.formatCurrency(total)}</strong>`, true);
        };

        document.getElementById('addItem').onclick = () => addRow();
        document.getElementById('tax_percentage').oninput = recalc;
        document.getElementById('productPicker').onchange = e => {
            const p = DB.findById('products', e.target.value);
            if (p) addRow({ description: p.name, quantity: 1, unit_price: p.unit_price, discount_pct: 0 });
            e.target.value = '';
        };

        (draft?.items ?? [{ description: '', quantity: 1, unit_price: 0, discount_pct: 0 }]).forEach(addRow);

        /* ── Submit ───────────────────────────────────────────────────────── */

        document.getElementById('invoiceForm').onsubmit = e => {
            e.preventDefault();
            const data  = Forms.collect(e.target);
            const items = readRows().filter(i => i.description && (i.unit_price > 0 || i.quantity > 0));

            Forms.clearErrors(e.target);
            if (!Utils.intVal(data.customer_id)) {
                Forms.showErrors({ customer_id: 'Select a client.' });
                document.getElementById('customer_search').classList.add('input-error');
                return Toast.error('Select a client for this invoice.');
            }
            if (!items.length) return Toast.error('Add at least one line item.');

            const id = Invoice.create({
                repair_id:      data.repair_id ? Utils.intVal(data.repair_id) : null,
                customer_id:    Utils.intVal(data.customer_id),
                invoice_date:   data.invoice_date,
                due_date:       data.due_date || null,
                tax_percentage: Utils.floatVal(data.tax_percentage),
                status:         data.status,
                notes:          data.notes,
            }, items);

            Toast.success('Invoice created.');
            Router.go(`/invoices/${id}`);
        };
    },

    /* ── Delete / export / print ──────────────────────────────────────────── */

    async destroy(id, redirectTo = null) {
        const inv = DB.findById('invoices', id);
        const ok  = await Modal.confirm({
            title: 'Delete invoice',
            message: `Delete ${inv?.invoice_number}? Its line items go too. This cannot be undone.`,
        });
        if (!ok) return;
        Invoice.delete(id);
        Toast.success('Invoice deleted.');
        redirectTo ? Router.go(redirectTo) : Router.reload();
    },

    export() {
        const rows = Invoice.withRelations(DB.table('invoices'));
        const csv  = Utils.toCsv(Utils.sortBy(rows, 'invoice_date', 'DESC'), [
            { label: 'Invoice',   key: 'invoice_number' },
            { label: 'Client',    key: 'customer_name' },
            { label: 'Date',      value: r => Utils.formatDate(r.invoice_date) },
            { label: 'Due',       value: r => r.due_date ? Utils.formatDate(r.due_date) : '' },
            { label: 'Subtotal',  key: 'subtotal' },
            { label: 'VAT',       key: 'tax_amount' },
            { label: 'Total',     key: 'total_amount' },
            { label: 'Paid',      key: 'amount_paid' },
            { label: 'Balance',   key: 'balance' },
            { label: 'Status',    value: r => r.is_overdue ? 'Overdue' : (INVOICE_STATUS[r.status] ?? r.status) },
        ]);
        Utils.download(`invoices-${Utils.toDbDate(new Date())}.csv`, csv);
        DB.log('exported', 'invoice', null, `${rows.length} invoices exported to CSV`);
        Toast.success(`${rows.length} invoices exported.`);
    },

    print({ params }) {
        if (!Auth.requireAuth()) return;
        const inv = Invoice.findById(params.id);
        if (!inv) return AuthView.notFound(`/invoices/${params.id}/print`);
        Layout.unmount();
        document.getElementById('viewRoot').innerHTML = Print.invoice(inv, Invoice.getItems(inv.invoice_id));
        Print.bind(`#/invoices/${inv.invoice_id}`);
    },
};
