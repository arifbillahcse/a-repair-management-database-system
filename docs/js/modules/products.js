/**
 * modules/products.js — the catalogue screen.
 * List, add, edit, delete and restock the parts and services that invoices
 * and counter sales draw from.
 */
'use strict';

const Products = {

    /* ── GET /products ────────────────────────────────────────────────────── */

    index({ query }) {
        if (!Auth.requireAuth()) return;

        const filters = {
            search: query.search ?? '', category: query.category ?? '',
            status: query.status ?? '', stock: query.stock ?? '',
            sort: query.sort ?? 'name', dir: query.dir ?? 'ASC',
        };
        const page   = Utils.intVal(query.page) || 1;
        const result = Product.getAll(filters, page);
        const c      = Product.counts();
        const canEdit = Auth.can('manager');

        Layout.render(`
            ${UI.pageHeader(L.catalogueMany,
                `${Utils.numberFormat(c.total)} items &nbsp;·&nbsp; ${c.parts} stocked &nbsp;·&nbsp; ${c.services} services
                 ${c.low ? `&nbsp;·&nbsp; <span style="color:var(--warning)">${c.low} low on stock</span>` : ''}`,
                `<button class="btn btn-secondary" id="exportBtn">${Icon.download('')} Export CSV</button>
                 ${canEdit ? `<a href="#/products/create" class="btn btn-primary">${Icon.plus('')} New item</a>` : ''}`
            )}

            <div class="stats-grid">
                ${UI.statCard({ label: 'Catalogue items', value: Utils.numberFormat(c.total),        icon: 'box',   tone: 'accent' })}
                ${UI.statCard({ label: 'Stock value',     value: Utils.formatCurrencyShort(c.value), icon: 'money', tone: 'green' })}
                ${UI.statCard({ label: 'Low on stock',    value: Utils.numberFormat(c.low),          icon: 'alert', tone: 'orange', link: '#/products?stock=low' })}
                ${UI.statCard({ label: 'Services',        value: Utils.numberFormat(c.services),     icon: 'wrench',tone: 'blue' })}
            </div>

            <div class="card">
                ${UI.filterPills([
                    { value: '',  label: 'All',      count: c.total },
                    { value: '1', label: 'Parts',    count: c.parts },
                    { value: '2', label: 'Services', count: c.services },
                ], filters.category, v => Router.setQuery({ category: v, page: 1 }))}

                <div class="filter-bar">
                    <div class="search-input-wrap">
                        ${Icon.search('search-input-icon')}
                        <input class="form-input" id="searchInput" type="search" placeholder="Search name, SKU or barcode…"
                               value="${Utils.e(filters.search)}" autocomplete="off">
                    </div>
                    <select class="form-select filter-select" id="stockFilter">
                        <option value="">All stock levels</option>
                        <option value="low" ${filters.stock === 'low' ? 'selected' : ''}>Low stock</option>
                        <option value="out" ${filters.stock === 'out' ? 'selected' : ''}>Out of stock</option>
                    </select>
                    <select class="form-select filter-select" id="statusFilter">
                        <option value="">Active &amp; inactive</option>
                        <option value="active"   ${filters.status === 'active'   ? 'selected' : ''}>Active only</option>
                        <option value="inactive" ${filters.status === 'inactive' ? 'selected' : ''}>Inactive only</option>
                    </select>
                    ${Object.values(filters).some(v => v && v !== 'name' && v !== 'ASC')
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
                { key: 'name', label: 'Item', sortable: true, render: p => `
                    <span class="cust-name-link">${Utils.e(p.name)}</span>
                    ${p.sku ? `<span class="vat-sub">${Utils.e(p.sku)}</span>` : ''}` },
                { key: 'category_id', label: 'Type', sortable: true,
                  render: p => Utils.intVal(p.category_id) === 1
                    ? '<span class="badge badge-blue">Part</span>'
                    : '<span class="badge badge-purple">Service</span>' },
                { key: 'cost_price', label: 'Cost', sortable: true, align: 'right', hideOnTablet: true,
                  render: p => Utils.formatCurrency(p.cost_price) },
                { key: 'unit_price', label: 'Price', sortable: true, align: 'right',
                  render: p => `<strong>${Utils.formatCurrency(p.unit_price)}</strong>` },
                { key: 'margin', label: 'Margin', align: 'right', hideOnTablet: true,
                  render: p => `${Product.margin(p)}%` },
                { key: 'stock_quantity', label: 'Stock', sortable: true, align: 'right',
                  render: p => !Product.isStocked(p)
                    ? '<span class="text-muted">—</span>'
                    : Utils.intVal(p.stock_quantity) === 0
                        ? '<span class="badge badge-red">Out</span>'
                        : Product.isLowStock(p)
                            ? `<span class="badge badge-orange">${p.stock_quantity} left</span>`
                            : Utils.numberFormat(p.stock_quantity) },
                { key: 'status', label: 'Status', sortable: true, render: p => Badge.active(p.status) },
            ],
            actions: p => (canEdit
                ? `<button class="act-btn act-btn-g" data-restock="${p.product_id}" title="Adjust stock">${Icon.plus('')}</button>` +
                  DataTable.act.edit(`#/products/${p.product_id}/edit`) +
                  DataTable.act.del(p.product_id)
                : ''),
            empty: {
                message: filters.search ? `No items match "${filters.search}".` : 'The catalogue is empty.',
                icon: 'box',
                action: canEdit ? '<a href="#/products/create" class="btn btn-primary">Add the first item</a>' : '',
            },
            onSort: (col, dir) => Router.setQuery({ sort: col, dir, page: 1 }),
            onPage: p => Router.setQuery({ page: p }),
        });

        const search = document.getElementById('searchInput');
        search.oninput = UI.debounce(() => Router.setQuery({ search: search.value, page: 1 }), 350);
        document.getElementById('stockFilter').onchange  = e => Router.setQuery({ stock: e.target.value, page: 1 });
        document.getElementById('statusFilter').onchange = e => Router.setQuery({ status: e.target.value, page: 1 });
        document.getElementById('clearFilters')?.addEventListener('click', () => Router.go('/products'));
        document.getElementById('exportBtn').onclick = () => this.export();
        UI.bindFilterPills();

        document.querySelectorAll('[data-restock]').forEach(b => {
            b.onclick = () => this.restock(Utils.intVal(b.dataset.restock));
        });

        DataTable.bindDelete('#listMount', {
            guard: id => Product.canDelete(id),
            blockedTitle: 'Cannot delete this item',
            message: id => `Delete "${Product.findById(id)?.name}" from the catalogue?`,
            onConfirm: id => { Product.delete(id); Toast.success('Catalogue item deleted.'); Router.reload(); },
        });

        if (filters.search) { search.focus(); search.setSelectionRange(search.value.length, search.value.length); }
    },

    /* ── Create / edit ────────────────────────────────────────────────────── */

    create() { if (Auth.requireRole('manager')) this._form(null); },

    edit({ params }) {
        if (!Auth.requireRole('manager')) return;
        const p = Product.findById(params.id);
        if (!p) return AuthView.notFound(`/products/${params.id}`);
        this._form(p);
    },

    _form(item = null) {
        const isEdit = !!item;
        const v = f => Utils.e(item?.[f] ?? '');

        Layout.render(`
            ${UI.backLink('#/products', `All ${L.catalogueMany.toLowerCase()}`)}
            ${UI.pageHeader(isEdit ? `Edit ${item.name}` : 'New catalogue item',
                isEdit ? 'Update pricing, stock or availability.'
                       : `Add a part or service that ${L.jobMany.toLowerCase()} and sales can draw from.`)}

            <form id="productForm" class="card" novalidate>
                <div class="card-body">
                    <div class="form-grid-2">

                        <div class="form-group form-col-full">
                            <label class="form-label required" for="name">Item name</label>
                            <input class="form-input" id="name" name="name" value="${v('name')}"
                                   placeholder="${Utils.e(L.catalogueExample)}" required>
                            <span class="field-error" id="err-name"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label required" for="category_id">Type</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="1" ${Utils.intVal(item?.category_id) === 1 ? 'selected' : ''}>Part / Material (tracks stock)</option>
                                <option value="2" ${Utils.intVal(item?.category_id) === 2 ? 'selected' : ''}>Service (no stock)</option>
                            </select>
                            <span class="field-error" id="err-category_id"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active"   ${(item?.status ?? 'active') === 'active'   ? 'selected' : ''}>Active</option>
                                <option value="inactive" ${(item?.status ?? '')       === 'inactive' ? 'selected' : ''}>Inactive</option>
                            </select>
                            <span class="form-hint">Inactive items stay on past invoices but disappear from the pickers.</span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="sku">SKU</label>
                            <input class="form-input" id="sku" name="sku" value="${v('sku')}" placeholder="LAP-BAT-01">
                            <span class="field-error" id="err-sku"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="barcode">Barcode</label>
                            <input class="form-input" id="barcode" name="barcode" value="${v('barcode')}" inputmode="numeric">
                        </div>

                        <div class="form-group">
                            <label class="form-label required" for="unit_price">Selling price (${CURRENCY_SYMBOL})</label>
                            <input class="form-input" id="unit_price" name="unit_price" type="number" min="0" step="1"
                                   value="${v('unit_price')}" required>
                            <span class="field-error" id="err-unit_price"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="cost_price">Cost price (${CURRENCY_SYMBOL})</label>
                            <input class="form-input" id="cost_price" name="cost_price" type="number" min="0" step="1"
                                   value="${v('cost_price')}">
                            <span class="form-hint" id="marginHint"></span>
                            <span class="field-error" id="err-cost_price"></span>
                        </div>

                        <div class="form-group" id="stockGroup">
                            <label class="form-label" for="stock_quantity">Stock on hand</label>
                            <input class="form-input" id="stock_quantity" name="stock_quantity" type="number" min="0" step="1"
                                   value="${item ? Utils.intVal(item.stock_quantity) : 0}">
                            <span class="field-error" id="err-stock_quantity"></span>
                        </div>

                        <div class="form-group" id="reorderGroup">
                            <label class="form-label" for="reorder_level">Warn below</label>
                            <input class="form-input" id="reorder_level" name="reorder_level" type="number" min="0" step="1"
                                   value="${item ? Utils.intVal(item.reorder_level ?? 5) : 5}">
                            <span class="form-hint">Flags the item as low on stock.</span>
                        </div>

                        <div class="form-group form-col-full">
                            <label class="form-label" for="notes">Notes</label>
                            <textarea class="form-textarea" id="notes" name="notes" rows="2">${Utils.e(item?.notes ?? '')}</textarea>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="#/products" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">${isEdit ? 'Save changes' : 'Add to catalogue'}</button>
                </div>
            </form>
        `);

        const cat   = document.getElementById('category_id');
        const price = document.getElementById('unit_price');
        const cost  = document.getElementById('cost_price');

        // Services never carry stock, so hide those fields entirely.
        const syncStock = () => {
            const isPart = Utils.intVal(cat.value) === 1;
            document.getElementById('stockGroup').style.display   = isPart ? '' : 'none';
            document.getElementById('reorderGroup').style.display = isPart ? '' : 'none';
        };
        const syncMargin = () => {
            const p = Utils.floatVal(price.value), c = Utils.floatVal(cost.value);
            document.getElementById('marginHint').textContent =
                p > 0 && c >= 0 ? `Margin: ${Utils.round2((p - c) / p * 100)}% · ${Utils.formatCurrency(p - c)} per unit` : '';
        };
        cat.onchange = syncStock;
        price.oninput = cost.oninput = syncMargin;
        syncStock(); syncMargin();

        document.getElementById('productForm').onsubmit = e => {
            e.preventDefault();
            const data = Forms.collect(e.target);
            const { ok, errors } = Product.validate(data, item?.product_id ?? null);
            Forms.clearErrors(e.target);
            if (!ok) { Forms.showErrors(errors); Toast.error('Please fix the highlighted fields.'); return; }

            if (isEdit) { Product.update(item.product_id, data); Toast.success('Catalogue item updated.'); }
            else        { Product.create(data);                  Toast.success('Catalogue item added.'); }
            Router.go('/products');
        };
    },

    /* ── Stock adjustment ─────────────────────────────────────────────────── */

    restock(id) {
        const p = Product.findById(id);
        if (!p) return;
        if (!Product.isStocked(p)) return Toast.info('Services do not track stock.');

        Modal.open({
            title: `Adjust stock — ${p.name}`,
            body: `
                <p class="confirm-text">Currently <strong>${Utils.numberFormat(p.stock_quantity)}</strong> in stock.</p>
                <div class="form-group">
                    <label class="form-label" for="delta">Change by</label>
                    <input class="form-input" id="delta" type="number" step="1" value="10">
                    <span class="form-hint">Use a negative number to remove stock (damage, write-off).</span>
                </div>`,
            footer: `
                <button class="btn btn-secondary" onclick="Modal.close()">Cancel</button>
                <button class="btn btn-primary" id="stockOk">Apply</button>`,
        });

        document.getElementById('stockOk').onclick = () => {
            const delta = Utils.intVal(document.getElementById('delta').value);
            if (!delta) return Toast.error('Enter a non-zero amount.');
            const res = Product.adjustStock(id, delta, 'manual adjustment');
            if (!res.ok) return Toast.error(res.error);
            Modal.close();
            Toast.success(`Stock updated to ${Product.findById(id).stock_quantity}.`);
            Router.reload();
        };
    },

    async destroy(id) {
        const check = Product.canDelete(id);
        if (!check.ok) {
            Modal.open({
                title: 'Cannot delete this item',
                body: `<p class="confirm-text">${Utils.e(check.reason)}</p>`,
                footer: '<button class="btn btn-secondary" onclick="Modal.close()">Close</button>',
            });
            return;
        }
        const ok = await Modal.confirm({
            title: 'Delete catalogue item',
            message: `Delete "${Product.findById(id)?.name}"? This cannot be undone.`,
        });
        if (!ok) return;
        Product.delete(id);
        Toast.success('Catalogue item deleted.');
        Router.reload();
    },

    export() {
        const rows = Utils.sortBy(DB.table('products'), 'name', 'ASC');
        const csv  = Utils.toCsv(rows, [
            { label: 'SKU',      key: 'sku' },
            { label: 'Name',     key: 'name' },
            { label: 'Type',     value: p => Product.CATEGORIES[Utils.intVal(p.category_id)] ?? '' },
            { label: 'Cost',     key: 'cost_price' },
            { label: 'Price',    key: 'unit_price' },
            { label: 'Margin %', value: p => Product.margin(p) },
            { label: 'Stock',    value: p => Product.isStocked(p) ? p.stock_quantity : '' },
            { label: 'Status',   key: 'status' },
        ]);
        Utils.download(`catalogue-${Utils.toDbDate(new Date())}.csv`, csv);
        DB.log('exported', 'product', null, `${rows.length} catalogue items exported`);
        Toast.success(`${rows.length} items exported.`);
    },
};
