/**
 * modules/customers.js — port of CustomerController + views/customers/*.php
 * Covers: list, detail, create, edit, delete, CSV export.
 */
'use strict';

const Customers = {

    /* ── GET /customers ───────────────────────────────────────────────────── */

    index({ query }) {
        if (!Auth.requireAuth()) return;

        const filters = {
            search: query.search ?? '', status: query.status ?? '', type: query.type ?? '',
            sort:   query.sort   ?? 'full_name', dir: query.dir ?? 'ASC',
        };
        const page   = Utils.intVal(query.page) || 1;
        const result = Customer.getAll(filters, page);
        const counts = Customer.getCounts();

        Layout.render(`
            ${UI.pageHeader(L.clientMany,
                `${Utils.numberFormat(counts.total)} total &nbsp;·&nbsp;
                 <span style="color:var(--success)">${Utils.numberFormat(counts.active)} active</span>
                 ${counts.inactive ? `&nbsp;·&nbsp; <span class="text-muted">${counts.inactive} inactive</span>` : ''}
                 ${counts.colleagues ? `&nbsp;·&nbsp; <span style="color:#7c3aed">${counts.colleagues} colleagues</span>` : ''}`,
                `${Auth.can('manager') ? `<button class="btn btn-secondary" id="exportBtn">${Icon.download('')} Export CSV</button>` : ''}
                 <a href="#/customers/create" class="btn btn-primary">${Icon.plus('')} New ${Utils.e(L.clientOne)}</a>`
            )}

            <div class="card">
                ${UI.filterPills([
                    { value: '',           label: 'All',        count: counts.total },
                    { value: 'active',     label: 'Active',     count: counts.active },
                    { value: 'inactive',   label: 'Inactive',   count: counts.inactive },
                ], filters.status, v => Router.setQuery({ status: v, page: 1 }))}

                <div class="filter-bar">
                    <div class="search-input-wrap">
                        ${Icon.search('search-input-icon')}
                        <input class="form-input" id="searchInput" type="search" placeholder="Search name, phone, email, city, BIN…"
                               value="${Utils.e(filters.search)}" autocomplete="off">
                    </div>
                    <select class="form-select filter-select" id="typeFilter">
                        <option value="">All types</option>
                        ${Object.entries(CLIENT_TYPES).map(([k, v]) =>
                            `<option value="${k}" ${filters.type === k ? 'selected' : ''}>${Utils.e(v)}</option>`).join('')}
                    </select>
                    ${filters.search || filters.type || filters.status
                        ? '<button class="btn btn-secondary btn-sm" id="clearFilters">Clear</button>' : ''}
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
                { key: 'full_name', label: 'Name', sortable: true, render: c => `
                    <a class="cust-name-link" href="#/customers/${c.customer_id}">${Utils.e(c.full_name)}</a>
                    ${c.vat_number ? `<span class="vat-sub">BIN ${Utils.e(c.vat_number)}</span>` : ''}` },
                { key: 'client_type', label: 'Type', sortable: true, render: c => Badge.clientType(c.client_type) },
                { key: 'phone_mobile', label: 'Contact', render: c => `
                    ${c.phone_mobile ? `<a class="ph-lnk" href="tel:${Utils.e(c.phone_mobile)}">${Utils.e(c.phone_mobile)}</a><br>` : ''}
                    ${c.email ? `<a class="em-lnk" href="mailto:${Utils.e(c.email)}">${Utils.e(Utils.truncate(c.email, 26))}</a>` : ''}
                    ${!c.phone_mobile && !c.email ? '<span class="text-muted">—</span>' : ''}` },
                { key: 'city', label: 'City', sortable: true, hideOnTablet: true,
                  render: c => Utils.e(c.city ?? '—') },
                { key: 'customer_since', label: `${L.clientOne} since`, sortable: true, hideOnTablet: true,
                  render: c => Utils.formatDate(c.customer_since) },
                { key: 'status', label: 'Status', sortable: true, render: c => Badge.active(c.status) },
            ],
            actions: c =>
                DataTable.act.view(`#/customers/${c.customer_id}`) +
                DataTable.act.edit(`#/customers/${c.customer_id}/edit`) +
                (Auth.can('manager') ? DataTable.act.del(c.customer_id) : ''),
            empty: {
                message: filters.search
                    ? `No ${L.clientMany.toLowerCase()} match "${filters.search}".`
                    : `No ${L.clientMany.toLowerCase()} yet.`,
                icon: 'users',
                action: `<a href="#/customers/create" class="btn btn-primary">Add the first ${Utils.e(L.clientOne.toLowerCase())}</a>`,
            },
            onSort: (col, dir) => Router.setQuery({ sort: col, dir, page: 1 }),
            onPage: p => Router.setQuery({ page: p }),
        });

        /* Filters */
        const search = document.getElementById('searchInput');
        search.oninput = UI.debounce(() => Router.setQuery({ search: search.value, page: 1 }), 350);
        document.getElementById('typeFilter').onchange = e => Router.setQuery({ type: e.target.value, page: 1 });
        document.getElementById('clearFilters')?.addEventListener('click', () => Router.go('/customers'));
        UI.bindFilterPills();
        document.getElementById('exportBtn')?.addEventListener('click', () => this.export(filters.status));

        DataTable.bindDelete('#listMount', {
            guard: id => Customer.canDelete(id),
            blockedTitle: `Cannot delete this ${L.clientOne.toLowerCase()}`,
            message: id => `Delete "${Customer.findById(id)?.full_name}"? This cannot be undone.`,
            onConfirm: id => { Customer.delete(id); Toast.success(`${L.clientOne} deleted.`); Router.reload(); },
        });

        // Keep focus + caret in the search box across re-renders
        if (filters.search) { search.focus(); search.setSelectionRange(search.value.length, search.value.length); }
    },

    /* ── GET /customers/:id ───────────────────────────────────────────────── */

    show({ params }) {
        if (!Auth.requireAuth()) return;

        const c = Customer.findById(params.id);
        if (!c) return AuthView.notFound(`/customers/${params.id}`);

        const stats    = Customer.getStats(c.customer_id);
        const repairs  = Customer.getRepairHistory(c.customer_id);
        const invoices = Customer.getInvoices(c.customer_id);

        Layout.render(`
            ${UI.backLink('#/customers', `All ${L.clientMany.toLowerCase()}`)}

            ${UI.pageHeader(c.full_name,
                `${Badge.clientType(c.client_type)} ${Badge.active(c.status)}
                 &nbsp;·&nbsp; ${Utils.e(L.clientOne)} since ${Utils.formatDate(c.customer_since)}`,
                `<a href="#/repairs/create?customer_id=${c.customer_id}" class="btn btn-primary">${Icon.plus('')} ${Utils.e(L.jobNew)}</a>
                 <a href="#/customers/${c.customer_id}/edit" class="btn btn-secondary">${Icon.edit('')} Edit</a>`
            )}

            <div class="stats-grid">
                ${UI.statCard({ label: `Total ${L.jobMany.toLowerCase()}`, value: Utils.numberFormat(stats.total_repairs), icon: 'wrench', tone: 'accent' })}
                ${UI.statCard({ label: `Open ${L.jobMany.toLowerCase()}`, value: Utils.numberFormat(stats.open_repairs), icon: 'clock', tone: 'orange' })}
                ${UI.statCard({ label: 'Lifetime spend',value: Utils.formatCurrencyShort(stats.total_spent), icon: 'money', tone: 'green' })}
                ${UI.statCard({ label: 'Outstanding',   value: Utils.formatCurrencyShort(stats.outstanding), icon: 'invoice', tone: 'blue' })}
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-main">

                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">${Utils.e(L.jobOne)} history</h2>
                            <span class="badge badge-gray">${repairs.length}</span>
                        </div>
                        <div id="repairsMount"></div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Invoices</h2>
                            <span class="badge badge-gray">${invoices.length}</span>
                        </div>
                        <div id="invoicesMount"></div>
                    </div>

                </div>

                <aside class="dashboard-aside">
                    <div class="card">
                        <div class="card-header"><h2 class="card-title">Contact</h2></div>
                        <div class="card-body">
                            ${UI.field('Mobile', c.phone_mobile ? `<a class="ph-lnk" href="tel:${Utils.e(c.phone_mobile)}">${Utils.e(c.phone_mobile)}</a>` : '', true)}
                            ${UI.field('Landline', c.phone_landline)}
                            ${UI.field('Email', c.email ? `<a class="em-lnk" href="mailto:${Utils.e(c.email)}">${Utils.e(c.email)}</a>` : '', true)}
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h2 class="card-title">Address</h2></div>
                        <div class="card-body">
                            ${UI.field('Street', c.address)}
                            ${UI.field('City', c.city)}
                            ${UI.field('Postal code', c.postal_code)}
                            ${UI.field('Division', BD_DIVISIONS[c.province] ?? c.province)}
                        </div>
                    </div>

                    ${c.client_type !== 'individual' || c.vat_number || c.tax_id ? `
                    <div class="card">
                        <div class="card-header"><h2 class="card-title">Tax details</h2></div>
                        <div class="card-body">
                            ${UI.field('BIN / VAT', c.vat_number)}
                            ${UI.field('NID / TIN', c.tax_id)}
                        </div>
                    </div>` : ''}

                    ${c.notes ? `
                    <div class="card">
                        <div class="card-header"><h2 class="card-title">Notes</h2></div>
                        <div class="card-body"><p class="note-text">${Utils.e(c.notes)}</p></div>
                    </div>` : ''}

                    ${Auth.can('manager') ? `
                    <div class="card card-danger">
                        <div class="card-header"><h2 class="card-title">Danger zone</h2></div>
                        <div class="card-body">
                            <p class="text-muted small">Deleting a ${Utils.e(L.clientOne.toLowerCase())} is permanent.</p>
                            <button class="btn btn-danger btn-full" id="deleteBtn">${Icon.trash('')} Delete ${Utils.e(L.clientOne.toLowerCase())}</button>
                        </div>
                    </div>` : ''}
                </aside>
            </div>
        `);

        DataTable.render({
            mount: '#repairsMount',
            rows: repairs.slice(0, 10),
            columns: [
                { key: 'repair_id', label: '#', width: '56px',
                  render: r => `<a class="table-link" href="#/repairs/${r.repair_id}">#${r.repair_id}</a>` },
                { key: 'device_model', label: L.itemLabel, render: r => Utils.e(Utils.truncate(r.device_model, 28)) },
                { key: 'date_in', label: 'In', hideOnTablet: true, render: r => Utils.formatDate(r.date_in) },
                { key: 'actual_amount', label: 'Amount', align: 'right',
                  render: r => r.actual_amount ? Utils.formatCurrency(r.actual_amount) : '<span class="text-muted">—</span>' },
                { key: 'status', label: 'Status', render: r => Badge.repair(r.status) },
            ],
            empty: { message: `No ${L.jobMany.toLowerCase()} for this ${L.clientOne.toLowerCase()} yet.`, icon: 'wrench',
                     action: `<a href="#/repairs/create?customer_id=${c.customer_id}" class="btn btn-primary">Log a ${Utils.e(L.jobLower)}</a>` },
        });

        DataTable.render({
            mount: '#invoicesMount',
            rows: Invoice.withRelations(invoices).slice(0, 10),
            columns: [
                { key: 'invoice_number', label: 'Invoice',
                  render: i => `<a class="table-link" href="#/invoices/${i.invoice_id}">${Utils.e(i.invoice_number)}</a>` },
                { key: 'invoice_date', label: 'Date', hideOnTablet: true, render: i => Utils.formatDate(i.invoice_date) },
                { key: 'total_amount', label: 'Total', align: 'right', render: i => Utils.formatCurrency(i.total_amount) },
                { key: 'status', label: 'Status', render: i => i.is_overdue ? Badge.invoice('overdue') : Badge.invoice(i.status) },
            ],
            empty: { message: `No invoices for this ${L.clientOne.toLowerCase()} yet.`, icon: 'invoice' },
        });

        document.getElementById('deleteBtn')?.addEventListener('click', () => this.destroy(c.customer_id, '/customers'));
    },

    /* ── Create / Edit ────────────────────────────────────────────────────── */

    create({ query }) {
        if (!Auth.requireAuth()) return;
        this._form(null, query);
    },

    edit({ params }) {
        if (!Auth.requireAuth()) return;
        const c = Customer.findById(params.id);
        if (!c) return AuthView.notFound(`/customers/${params.id}`);
        this._form(c);
    },

    _form(c = null, query = {}) {
        const isEdit = !!c;
        const v = (field, fallback = '') => Utils.e(c?.[field] ?? query[field] ?? fallback);

        Layout.render(`
            ${UI.backLink(isEdit ? `#/customers/${c.customer_id}` : '#/customers',
                isEdit ? `Back to ${L.clientOne.toLowerCase()}` : `All ${L.clientMany.toLowerCase()}`)}
            ${UI.pageHeader(isEdit ? `Edit ${c.full_name}` : `New ${L.clientOne.toLowerCase()}`,
                isEdit ? `Update the details for this ${L.clientOne.toLowerCase()}.` : `Add a ${L.clientOne.toLowerCase()} to the system.`)}

            <form id="clientForm" class="card" novalidate>
                <div class="card-body">

                    <div class="form-grid-2">
                        <div class="form-group form-col-full">
                            <label class="form-label required" for="full_name">Full name / company name</label>
                            <input class="form-input" id="full_name" name="full_name" value="${v('full_name')}" required>
                            <span class="field-error" id="err-full_name"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="client_type">${Utils.e(L.clientOne)} type</label>
                            <select class="form-select" id="client_type" name="client_type">
                                ${Object.entries(CLIENT_TYPES).map(([k, lbl]) =>
                                    `<option value="${k}" ${(c?.client_type ?? 'individual') === k ? 'selected' : ''}>${Utils.e(lbl)}</option>`).join('')}
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active"   ${(c?.status ?? 'active') === 'active'   ? 'selected' : ''}>Active</option>
                                <option value="inactive" ${(c?.status ?? '')       === 'inactive' ? 'selected' : ''}>Inactive</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="phone_mobile">Mobile</label>
                            <input class="form-input" id="phone_mobile" name="phone_mobile" value="${v('phone_mobile')}"
                                   placeholder="01XXXXXXXXX" inputmode="tel">
                            <span class="field-error" id="err-phone_mobile"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="phone_landline">Landline</label>
                            <input class="form-input" id="phone_landline" name="phone_landline" value="${v('phone_landline')}"
                                   placeholder="02XXXXXXXX" inputmode="tel">
                        </div>

                        <div class="form-group form-col-full">
                            <label class="form-label" for="email">Email</label>
                            <input class="form-input" id="email" name="email" type="email" value="${v('email')}">
                            <span class="field-error" id="err-email"></span>
                        </div>

                        <div class="form-group form-col-full">
                            <label class="form-label" for="address">Street address</label>
                            <input class="form-input" id="address" name="address" value="${v('address')}"
                                   placeholder="House, Road, Area">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="city">City</label>
                            <input class="form-input" id="city" name="city" value="${v('city')}" list="cityList">
                            <datalist id="cityList">
                                ${['Dhaka','Chattogram','Sylhet','Khulna','Rajshahi','Barishal','Rangpur','Mymensingh','Cumilla','Gazipur']
                                    .map(x => `<option value="${x}">`).join('')}
                            </datalist>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="postal_code">Postal code</label>
                            <input class="form-input" id="postal_code" name="postal_code" value="${v('postal_code')}"
                                   placeholder="1207" inputmode="numeric" maxlength="4">
                            <span class="field-error" id="err-postal_code"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="province">Division</label>
                            <select class="form-select" id="province" name="province">
                                <option value="">—</option>
                                ${Object.entries(BD_DIVISIONS).map(([k, lbl]) =>
                                    `<option value="${k}" ${c?.province === k ? 'selected' : ''}>${Utils.e(lbl)}</option>`).join('')}
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="customer_since">${Utils.e(L.clientOne)} since</label>
                            <input class="form-input" id="customer_since" name="customer_since" type="date"
                                   value="${Utils.e(c?.customer_since ?? Utils.toDbDate(new Date()))}">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="vat_number">BIN / VAT number</label>
                            <input class="form-input" id="vat_number" name="vat_number" value="${v('vat_number')}"
                                   placeholder="9 or 13 digits" inputmode="numeric">
                            <span class="field-error" id="err-vat_number"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="tax_id">NID / TIN</label>
                            <input class="form-input" id="tax_id" name="tax_id" value="${v('tax_id')}" inputmode="numeric">
                            <span class="field-error" id="err-tax_id"></span>
                        </div>

                        <div class="form-group form-col-full">
                            <label class="form-label" for="notes">Notes</label>
                            <textarea class="form-textarea" id="notes" name="notes" rows="3">${Utils.e(c?.notes ?? '')}</textarea>
                        </div>
                    </div>

                </div>

                <div class="form-actions">
                    <a href="${isEdit ? `#/customers/${c.customer_id}` : '#/customers'}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">${isEdit ? 'Save changes' : `Create ${Utils.e(L.clientOne.toLowerCase())}`}</button>
                </div>
            </form>
        `);

        document.getElementById('clientForm').onsubmit = e => {
            e.preventDefault();
            const data = Forms.collect(e.target);
            const { ok, errors } = Customer.validate(data, c?.customer_id ?? null);

            Forms.clearErrors(e.target);
            if (!ok) { Forms.showErrors(errors); Toast.error('Please fix the highlighted fields.'); return; }

            if (isEdit) {
                Customer.update(c.customer_id, data);
                Toast.success(`${L.clientOne} updated.`);
                Router.go(`/customers/${c.customer_id}`);
            } else {
                const id = Customer.create(data);
                Toast.success(`${L.clientOne} created.`);
                Router.go(`/customers/${id}`);
            }
        };
    },

    /* ── Delete + export ──────────────────────────────────────────────────── */

    async destroy(id, redirectTo = null) {
        const c     = Customer.findById(id);
        const check = Customer.canDelete(id);

        if (!check.ok) {
            Modal.open({
                title: `Cannot delete this ${L.clientOne.toLowerCase()}`,
                body: `<p class="confirm-text">${Utils.e(check.reason)}</p>`,
                footer: '<button class="btn btn-secondary" onclick="Modal.close()">Close</button>',
            });
            return;
        }

        const ok = await Modal.confirm({
            title: `Delete ${L.clientOne.toLowerCase()}`,
            message: `Delete "${c?.full_name}"? This cannot be undone.`,
        });
        if (!ok) return;

        Customer.delete(id);
        Toast.success(`${L.clientOne} deleted.`);
        redirectTo ? Router.go(redirectTo) : Router.reload();
    },

    export(status = '') {
        const rows = Customer.getForExport(status);
        const csv  = Utils.toCsv(rows, [
            { label: 'ID',           key: 'customer_id' },
            { label: 'Name',         key: 'full_name' },
            { label: 'Type',         value: r => CLIENT_TYPES[r.client_type] ?? r.client_type },
            { label: 'Mobile',       key: 'phone_mobile' },
            { label: 'Landline',     key: 'phone_landline' },
            { label: 'Email',        key: 'email' },
            { label: 'Address',      key: 'address' },
            { label: 'City',         key: 'city' },
            { label: 'Postal code',  key: 'postal_code' },
            { label: 'Division',     value: r => BD_DIVISIONS[r.province] ?? '' },
            { label: 'BIN',          key: 'vat_number' },
            { label: 'NID/TIN',      key: 'tax_id' },
            { label: 'Status',       key: 'status' },
            { label: `${L.clientOne} since`, value: r => Utils.formatDate(r.customer_since) },
        ]);
        Utils.download(`${Utils.slugify(L.clientMany)}-${Utils.toDbDate(new Date())}.csv`, csv);
        DB.log('exported', 'customer', null, `${rows.length} ${L.clientMany.toLowerCase()} exported to CSV`);
        Toast.success(`${rows.length} ${L.clientMany.toLowerCase()} exported.`);
    },
};
