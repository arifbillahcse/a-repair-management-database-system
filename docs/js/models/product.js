/**
 * models/product.js — the catalogue.
 *
 * The `products` table existed in schema.sql from the start but nothing ever
 * managed it: the PHP app only ever read it for invoice line items. This
 * model gives it a full life cycle — search, stock, and the guards that stop
 * you deleting something an invoice still references.
 */
'use strict';

const Product = {

    CATEGORIES: { 1: 'Part / Material', 2: 'Service' },

    findById(id)   { return DB.findById('products', id); },
    findBySku(sku) { return DB.findOneBy('products', 'sku', String(sku).trim().toUpperCase()); },

    /** List with search, category/status filters, sorting and pagination. */
    getAll(filters = {}, page = 1, perPage = PAGE_SIZE) {
        let rows = DB.table('products');

        const q = Utils.sanitize(filters.search).toLowerCase();
        if (q) {
            rows = rows.filter(p =>
                String(p.name ?? '').toLowerCase().includes(q) ||
                String(p.sku ?? '').toLowerCase().includes(q) ||
                String(p.barcode ?? '').toLowerCase().includes(q));
        }
        if (filters.category) rows = rows.filter(p => String(p.category_id) === String(filters.category));
        if (filters.status)   rows = rows.filter(p => p.status === filters.status);
        if (filters.stock === 'low')  rows = rows.filter(p => this.isLowStock(p));
        if (filters.stock === 'out')  rows = rows.filter(p => this.isStocked(p) && Utils.intVal(p.stock_quantity) === 0);

        rows = Utils.sortBy(rows, filters.sort || 'name', filters.dir || 'ASC');

        const meta = Utils.paginate(rows.length, page, perPage);
        return { data: rows.slice(meta.offset, meta.offset + perPage), pagination: meta, total: rows.length };
    },

    /** Services have no stock — only parts do. */
    isStocked(p)   { return Utils.intVal(p.category_id) === 1; },
    isLowStock(p)  { return this.isStocked(p) && Utils.intVal(p.stock_quantity) <= Utils.intVal(p.reorder_level ?? 5); },

    /** Active items, used by the invoice and counter-sale pickers. */
    sellable() {
        return Utils.sortBy(DB.where('products', { status: 'active' }), 'name', 'ASC');
    },

    counts() {
        const rows = DB.table('products');
        return {
            total:    rows.length,
            active:   rows.filter(p => p.status === 'active').length,
            inactive: rows.filter(p => p.status !== 'active').length,
            parts:    rows.filter(p => Utils.intVal(p.category_id) === 1).length,
            services: rows.filter(p => Utils.intVal(p.category_id) === 2).length,
            low:      rows.filter(p => this.isLowStock(p)).length,
            value:    Utils.sum(rows.filter(p => this.isStocked(p)),
                                p => Utils.intVal(p.stock_quantity) * Utils.floatVal(p.cost_price)),
        };
    },

    /** Margin per unit, shown on the list and detail views. */
    margin(p) {
        const price = Utils.floatVal(p.unit_price), cost = Utils.floatVal(p.cost_price);
        if (!price) return 0;
        return Utils.round2((price - cost) / price * 100);
    },

    /* ── Stock ────────────────────────────────────────────────────────────── */

    /** delta is negative for a sale, positive for a restock. */
    adjustStock(id, delta, reason = '') {
        const p = this.findById(id);
        if (!p || !this.isStocked(p)) return { ok: true };       // services never track stock

        const next = Utils.intVal(p.stock_quantity) + Utils.intVal(delta);
        if (next < 0) {
            return { ok: false, error: `Only ${p.stock_quantity} × "${p.name}" left in stock.` };
        }
        DB.update('products', id, { stock_quantity: next });
        DB.log('updated', 'product', id,
            `${p.name}: stock ${p.stock_quantity} → ${next}${reason ? ' (' + reason + ')' : ''}`);
        return { ok: true };
    },

    /** Check a whole basket before committing any of it. */
    checkStock(lines) {
        for (const l of lines) {
            const p = this.findById(l.product_id);
            if (!p || !this.isStocked(p)) continue;
            if (Utils.intVal(p.stock_quantity) < Utils.intVal(l.quantity)) {
                return { ok: false, error: `Not enough stock for "${p.name}" — ${p.stock_quantity} available.` };
            }
        }
        return { ok: true };
    },

    /* ── Validation + writes ──────────────────────────────────────────────── */

    validate(data, excludeId = null) {
        const errors = {};
        if (!Utils.sanitize(data.name)) errors.name = 'Name is required.';
        if (!data.category_id)          errors.category_id = 'Choose a category.';

        if (Utils.floatVal(data.unit_price) <= 0) errors.unit_price = 'Selling price must be greater than zero.';
        if (Utils.floatVal(data.cost_price) < 0)  errors.cost_price = 'Cost price cannot be negative.';
        if (Utils.floatVal(data.cost_price) > Utils.floatVal(data.unit_price)) {
            errors.cost_price = 'Cost price is higher than the selling price.';
        }
        if (data.stock_quantity !== '' && Utils.intVal(data.stock_quantity) < 0) {
            errors.stock_quantity = 'Stock cannot be negative.';
        }
        const sku = Utils.sanitize(data.sku).toUpperCase();
        if (sku && DB.table('products').some(p =>
                String(p.sku ?? '').toUpperCase() === sku &&
                Utils.intVal(p.product_id) !== Utils.intVal(excludeId))) {
            errors.sku = 'That SKU is already used by another item.';
        }
        return { ok: Object.keys(errors).length === 0, errors };
    },

    _clean(data) {
        return {
            sku:            Utils.sanitize(data.sku).toUpperCase() || null,
            barcode:        Utils.sanitize(data.barcode) || null,
            name:           Utils.sanitize(data.name),
            category_id:    Utils.intVal(data.category_id),
            category_name:  this.CATEGORIES[Utils.intVal(data.category_id)] ?? '',
            unit_price:     Utils.round2(data.unit_price),
            cost_price:     Utils.round2(data.cost_price),
            stock_quantity: Utils.intVal(data.category_id) === 1 ? Utils.intVal(data.stock_quantity) : 0,
            reorder_level:  Utils.intVal(data.reorder_level) || 5,
            tax_percentage: data.tax_percentage === '' || data.tax_percentage === undefined
                                ? DEFAULT_TAX_PCT : Utils.floatVal(data.tax_percentage),
            status:         data.status || 'active',
            notes:          Utils.sanitize(data.notes) || null,
        };
    },

    create(data) {
        const id = DB.create('products', this._clean(data));
        DB.log('created', 'product', id, `Catalogue item "${data.name}" added`);
        return id;
    },

    update(id, data) {
        DB.update('products', id, this._clean(data));
        DB.log('updated', 'product', id, `Catalogue item "${data.name}" updated`);
        return id;
    },

    /** Invoice line items reference products — refuse rather than orphan them. */
    canDelete(id) {
        const used = DB.count('invoice_items', { product_id: id });
        return used
            ? { ok: false, reason: `This item appears on ${used} invoice line(s). Set it to inactive instead of deleting it.` }
            : { ok: true };
    },

    delete(id) {
        const p = this.findById(id);
        DB.delete('products', id);
        DB.log('deleted', 'product', id, `Catalogue item "${p?.name ?? id}" deleted`);
    },

    /** Best sellers, used by the catalogue page and reports. */
    topSelling(limit = 8) {
        const byProduct = Utils.groupBy(
            DB.table('invoice_items').filter(i => i.product_id), 'product_id');
        return Object.entries(byProduct)
            .map(([id, lines]) => ({
                product_id: Utils.intVal(id),
                name: this.findById(id)?.name ?? `#${id}`,
                qty: Utils.sum(lines, 'quantity'),
                revenue: Utils.sum(lines, 'line_total'),
            }))
            .sort((a, b) => b.revenue - a.revenue)
            .slice(0, limit);
    },
};
