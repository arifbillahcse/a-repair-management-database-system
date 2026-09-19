/**
 * models/invoice.js — port of models/Invoice.php
 * Line-item totals and VAT are recalculated the same way the PHP did,
 * only with the Bangladesh 15% standard rate instead of 22%.
 */
'use strict';

const Invoice = {

    findById(id) {
        const row = DB.findById('invoices', id);
        if (!row) return null;
        return this.withRelations([row])[0];
    },

    withRelations(rows) {
        let out = DB.join(rows, 'customers', 'customer_id', {
            full_name: 'customer_name', email: 'customer_email',
            phone_mobile: 'customer_phone', address: 'customer_address',
            city: 'customer_city', postal_code: 'customer_postal',
            vat_number: 'customer_vat', client_type: 'customer_type',
        });
        out = DB.join(out, 'repairs', 'repair_id', { device_model: 'device_model' });
        return out.map(i => ({
            ...i,
            balance:    Utils.round2(Utils.floatVal(i.total_amount) - Utils.floatVal(i.amount_paid)),
            is_overdue: this.isOverdue(i),
        }));
    },

    isOverdue(inv) {
        if (['paid', 'cancelled', 'draft'].includes(inv.status)) return false;
        return !!inv.due_date && Utils.toDbDate(inv.due_date) < Utils.toDbDate(new Date());
    },

    getAll(filters = {}, page = 1, perPage = PAGE_SIZE) {
        let rows = DB.table('invoices');

        const q = Utils.sanitize(filters.search).toLowerCase();
        if (q) {
            const custIds = new Set(Customer.search(q, 9999).map(c => c.customer_id));
            rows = rows.filter(i =>
                String(i.invoice_number ?? '').toLowerCase().includes(q) || custIds.has(i.customer_id));
        }
        if (filters.status === 'overdue') rows = rows.filter(i => this.isOverdue(i));
        else if (filters.status)          rows = rows.filter(i => i.status === filters.status);
        if (filters.customer_id) rows = rows.filter(i => Utils.intVal(i.customer_id) === Utils.intVal(filters.customer_id));
        if (filters.date_from)   rows = rows.filter(i => i.invoice_date >= filters.date_from);
        if (filters.date_to)     rows = rows.filter(i => i.invoice_date <= filters.date_to);

        rows = this.withRelations(rows);
        rows = Utils.sortBy(rows, filters.sort || 'invoice_date', filters.dir || 'DESC');

        const meta = Utils.paginate(rows.length, page, perPage);
        return { data: rows.slice(meta.offset, meta.offset + perPage), pagination: meta, total: rows.length };
    },

    /** Invoice::generateInvoiceNumber() — INV-2026-00042 */
    generateInvoiceNumber() {
        const year = new Date().getFullYear();
        const seq  = DB.table('invoices').filter(i => String(i.invoice_number).includes(`-${year}-`)).length + 1;
        return `INV-${year}-${String(seq).padStart(5, '0')}`;
    },

    getItems(invoiceId) {
        return DB.findAll('invoice_items', { invoice_id: invoiceId }, 'sort_order ASC');
    },

    /** line_total = qty × unit_price × (1 − discount%) */
    lineTotal(item) {
        const gross = Utils.floatVal(item.quantity) * Utils.floatVal(item.unit_price);
        return Utils.round2(gross * (1 - Utils.floatVal(item.discount_pct) / 100));
    },

    addItem(invoiceId, item) {
        const order = this.getItems(invoiceId).length;
        return DB.create('invoice_items', {
            invoice_id:     invoiceId,
            product_id:     item.product_id ?? null,
            description:    item.description,
            quantity:       Utils.floatVal(item.quantity) || 1,
            unit_price:     Utils.floatVal(item.unit_price),
            tax_percentage: item.tax_percentage ?? DEFAULT_TAX_PCT,
            discount_pct:   Utils.floatVal(item.discount_pct),
            line_total:     this.lineTotal(item),
            sort_order:     order,
        });
    },

    deleteItems(invoiceId) { DB.deleteWhere('invoice_items', { invoice_id: invoiceId }); },

    /** Invoice::recalculateTotals() — subtotal, VAT, grand total, status. */
    recalculateTotals(invoiceId) {
        const inv   = DB.findById('invoices', invoiceId);
        if (!inv) return;
        const items = this.getItems(invoiceId);

        const subtotal = Utils.round2(Utils.sum(items, 'line_total'));
        const taxPct   = Utils.floatVal(inv.tax_percentage ?? DEFAULT_TAX_PCT);
        const taxAmt   = Utils.round2(subtotal * taxPct / 100);
        const total    = Utils.round2(subtotal + taxAmt);
        const paid     = Utils.floatVal(inv.amount_paid);

        let status = inv.status;
        if (!['cancelled', 'draft'].includes(status)) {
            if (paid >= total && total > 0)   status = 'paid';
            else if (paid > 0)                status = 'partially_paid';
            else if (this.isOverdue({ ...inv, total_amount: total })) status = 'overdue';
            else if (status === 'paid' || status === 'partially_paid') status = 'sent';
        }

        DB.update('invoices', invoiceId, {
            subtotal, tax_amount: taxAmt, total_amount: total, status,
        });
    },

    markAsPaid(invoiceId, amount = null) {
        const inv = DB.findById('invoices', invoiceId);
        if (!inv) return { ok: false, error: 'Invoice not found.' };

        const total = Utils.floatVal(inv.total_amount);
        const paid  = amount === null ? total : Utils.round2(Utils.floatVal(inv.amount_paid) + Utils.floatVal(amount));

        DB.update('invoices', invoiceId, {
            amount_paid: Math.min(paid, total),
            status:      paid >= total ? 'paid' : 'partially_paid',
            paid_at:     paid >= total ? Utils.now() : (inv.paid_at ?? null),
        });
        DB.log('updated', 'invoice', invoiceId, `Invoice ${inv.invoice_number} payment recorded`);
        return { ok: true };
    },

    markAsSent(invoiceId) {
        const inv = DB.findById('invoices', invoiceId);
        if (!inv) return { ok: false, error: 'Invoice not found.' };
        if (inv.status !== 'draft') return { ok: false, error: 'Only draft invoices can be sent.' };
        DB.update('invoices', invoiceId, { status: 'sent', sent_at: Utils.now() });
        DB.log('updated', 'invoice', invoiceId, `Invoice ${inv.invoice_number} marked as sent`);
        return { ok: true };
    },

    /** Invoice::getMonthlyStats() — dashboard + reports. */
    getMonthlyStats() {
        const rows  = DB.table('invoices');
        const month = Utils.toDbDate(new Date()).slice(0, 7);
        const thisMonth = rows.filter(i => String(i.invoice_date).startsWith(month));

        return {
            count_month:   thisMonth.length,
            billed_month:  Utils.sum(thisMonth, 'total_amount'),
            paid_month:    Utils.sum(thisMonth.filter(i => i.status === 'paid'), 'total_amount'),
            outstanding:   Utils.sum(
                rows.filter(i => ['sent', 'partially_paid', 'overdue'].includes(i.status)),
                i => Utils.floatVal(i.total_amount) - Utils.floatVal(i.amount_paid)),
            overdue_count: rows.filter(i => this.isOverdue(i)).length,
            total_paid:    Utils.sum(rows.filter(i => i.status === 'paid'), 'total_amount'),
        };
    },

    getStatusCounts() {
        const rows   = DB.table('invoices');
        const counts = Object.fromEntries(Object.keys(INVOICE_STATUS).map(k => [k, 0]));
        rows.forEach(i => { counts[i.status] = (counts[i.status] ?? 0) + 1; });
        counts.total   = rows.length;
        counts.overdue = rows.filter(i => this.isOverdue(i)).length;
        return counts;
    },

    /* ── Create ───────────────────────────────────────────────────────────── */

    create(data, items = []) {
        const user = Auth.user();
        const id = DB.create('invoices', {
            repair_id:      data.repair_id ?? null,
            customer_id:    Utils.intVal(data.customer_id),
            invoice_number: data.invoice_number || this.generateInvoiceNumber(),
            invoice_date:   data.invoice_date || Utils.toDbDate(new Date()),
            due_date:       data.due_date || null,
            subtotal:       0, tax_amount: 0, total_amount: 0, amount_paid: 0,
            tax_percentage: data.tax_percentage ?? DEFAULT_TAX_PCT,
            status:         data.status || 'draft',
            notes:          data.notes ?? '',
            created_by:     user?.user_id ?? null,
        });
        items.forEach(it => this.addItem(id, it));
        this.recalculateTotals(id);
        DB.log('created', 'invoice', id, `Invoice created for client #${data.customer_id}`);
        return id;
    },

    /** InvoiceController::createFromRepair() — prefills from a repair job. */
    draftFromRepair(repairId) {
        const r = Repair.findById(repairId);
        if (!r) return null;
        const amount = Utils.floatVal(r.actual_amount || r.estimate_amount);
        return {
            repair_id:   r.repair_id,
            customer_id: r.customer_id,
            customer_name: r.customer_name,
            items: [{
                description: `${L.jobOne} #${r.repair_id} — ${r.device_model}${r.work_done ? ' (' + Utils.truncate(r.work_done, 60) + ')' : ''}`,
                quantity: 1, unit_price: amount, discount_pct: 0,
            }],
        };
    },

    delete(id) {
        const inv = DB.findById('invoices', id);
        this.deleteItems(id);
        DB.delete('invoices', id);
        DB.log('deleted', 'invoice', id, `Invoice ${inv?.invoice_number ?? id} deleted`);
    },
};
