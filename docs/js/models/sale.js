/**
 * models/sale.js — counter sales.
 *
 * Selling parts or accessories over the counter, with no repair job behind
 * it. The invoices schema already allows this: repair_id is nullable. A sale
 * is therefore an ordinary invoice flagged with source = 'sale', so it flows
 * through the same totals, VAT, payment and print code as everything else —
 * no second billing path to keep in step.
 *
 * The one thing a sale does that a repair invoice does not is move stock.
 */
'use strict';

const Sale = {

    isSale(inv) { return inv?.source === 'sale'; },

    findById(id) {
        const inv = Invoice.findById(id);
        return inv && this.isSale(inv) ? inv : null;
    },

    getAll(filters = {}, page = 1, perPage = PAGE_SIZE) {
        // Reuse the invoice query, then keep only counter sales.
        const all = Invoice.getAll({ ...filters }, 1, 1e9).data.filter(i => this.isSale(i));
        const sorted = Utils.sortBy(all, filters.sort || 'invoice_date', filters.dir || 'DESC');
        const meta = Utils.paginate(sorted.length, page, perPage);
        return { data: sorted.slice(meta.offset, meta.offset + perPage), pagination: meta, total: sorted.length };
    },

    stats() {
        const sales = DB.table('invoices').filter(i => this.isSale(i));
        const month = Utils.toDbDate(new Date()).slice(0, 7);
        const today = Utils.toDbDate(new Date());
        const thisMonth = sales.filter(s => String(s.invoice_date).startsWith(month));
        return {
            count_total:  sales.length,
            count_today:  sales.filter(s => s.invoice_date === today).length,
            count_month:  thisMonth.length,
            total_month:  Utils.sum(thisMonth, 'total_amount'),
            total_all:    Utils.sum(sales, 'total_amount'),
            avg_sale:     sales.length ? Utils.round2(Utils.sum(sales, 'total_amount') / sales.length) : 0,
        };
    },

    /**
     * Ring up a sale. Validates stock for the whole basket first, so a
     * partially-fulfilled sale can never be written.
     *
     * lines: [{ product_id, quantity, unit_price, discount_pct }]
     */
    create({ customer_id, lines, payment = 'paid', notes = '' }) {
        if (!Utils.intVal(customer_id)) return { ok: false, error: `Select a ${L.clientOne.toLowerCase()}.` };

        const basket = lines.filter(l => Utils.intVal(l.product_id) && Utils.floatVal(l.quantity) > 0);
        if (!basket.length) return { ok: false, error: 'Add at least one item to the sale.' };

        const stock = Product.checkStock(basket);
        if (!stock.ok) return stock;

        const items = basket.map(l => {
            const p = Product.findById(l.product_id);
            return {
                product_id:   p.product_id,
                description:  p.name,
                quantity:     Utils.floatVal(l.quantity),
                unit_price:   l.unit_price === undefined ? Utils.floatVal(p.unit_price) : Utils.floatVal(l.unit_price),
                discount_pct: Utils.floatVal(l.discount_pct),
            };
        });

        const invoiceId = Invoice.create({
            repair_id:      null,
            customer_id:    Utils.intVal(customer_id),
            invoice_date:   Utils.toDbDate(new Date()),
            due_date:       Utils.toDbDate(new Date()),
            tax_percentage: DEFAULT_TAX_PCT,
            status:         'draft',
            notes,
        }, items);

        // Flag it as a counter sale, then settle it if it was paid at the till.
        DB.update('invoices', invoiceId, { source: 'sale' });
        if (payment === 'paid') Invoice.markAsPaid(invoiceId);
        else                    Invoice.markAsSent(invoiceId);

        basket.forEach(l => Product.adjustStock(l.product_id, -Utils.intVal(l.quantity), 'counter sale'));

        const inv = Invoice.findById(invoiceId);
        DB.log('created', 'sale', invoiceId,
            `Counter sale ${inv.invoice_number} — ${Utils.formatCurrency(inv.total_amount)}`);

        return { ok: true, invoice_id: invoiceId, invoice: inv };
    },

    /** Deleting a sale puts the stock back. */
    delete(id) {
        const inv = this.findById(id);
        if (!inv) return;
        Invoice.getItems(id).forEach(it => {
            if (it.product_id) Product.adjustStock(it.product_id, Utils.intVal(it.quantity), 'sale reversed');
        });
        Invoice.delete(id);
        DB.log('deleted', 'sale', id, `Counter sale ${inv.invoice_number} reversed`);
    },
};
