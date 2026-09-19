/**
 * models/customer.js — port of models/Customer.php
 * SQL WHERE/ORDER/GROUP BY become array filter/sort/reduce.
 */
'use strict';

const Customer = {

    findById(id)      { return DB.findById('customers', id); },
    findByEmail(mail) { return DB.findOneBy('customers', 'email', mail); },

    findByPhone(phone) {
        const p = Utils.sanitizePhone(phone);
        // Guard the empty case: '' would otherwise equal the sanitised phone
        // of every customer who has no number on file.
        if (!p) return null;
        return DB.table('customers').find(c =>
            Utils.sanitizePhone(c.phone_mobile) === p ||
            Utils.sanitizePhone(c.phone_landline) === p) ?? null;
    },

    /** Customer::getAll() — search + filters + sort + pagination. */
    getAll(filters = {}, page = 1, perPage = PAGE_SIZE) {
        let rows = DB.table('customers');

        const q = Utils.sanitize(filters.search).toLowerCase();
        if (q) {
            // Only match on phone when the query actually contains digits:
            // sanitizePhone('hostorio') is '', and '' is a substring of every
            // number, which would make any text query match every row.
            const qPhone = Utils.sanitizePhone(q);
            rows = rows.filter(c =>
                String(c.full_name ?? '').toLowerCase().includes(q) ||
                String(c.email ?? '').toLowerCase().includes(q) ||
                String(c.city ?? '').toLowerCase().includes(q) ||
                String(c.vat_number ?? '').toLowerCase().includes(q) ||
                String(c.tax_id ?? '').toLowerCase().includes(q) ||
                (qPhone !== '' && (
                    Utils.sanitizePhone(c.phone_mobile).includes(qPhone) ||
                    Utils.sanitizePhone(c.phone_landline).includes(qPhone))));
        }
        if (filters.status) rows = rows.filter(c => c.status === filters.status);
        if (filters.type)   rows = rows.filter(c => c.client_type === filters.type);
        if (filters.city)   rows = rows.filter(c => c.city === filters.city);

        rows = Utils.sortBy(rows, filters.sort || 'full_name', filters.dir || 'ASC');

        const meta = Utils.paginate(rows.length, page, perPage);
        return { data: rows.slice(meta.offset, meta.offset + perPage), pagination: meta, total: rows.length };
    },

    /** Customer::search() — used by the repair form. */
    search(query, limit = 10) {
        const q = Utils.sanitize(query).toLowerCase();
        if (q.length < 2) return [];
        const qPhone = Utils.sanitize(query).replace(/[^\d+]/g, '');
        return DB.table('customers')
            .filter(c => c.status === 'active' && (
                String(c.full_name ?? '').toLowerCase().includes(q) ||
                String(c.email ?? '').toLowerCase().includes(q) ||
                // Same guard as getAll(): an all-text query must not match
                // every phone number via the empty string.
                (qPhone !== '' && Utils.sanitizePhone(c.phone_mobile).includes(qPhone))))
            .slice(0, limit);
    },

    /** Customer::autocomplete() — replaces GET /api/customers/autocomplete. */
    autocomplete(query, limit = 10) {
        return this.search(query, limit).map(c => ({
            customer_id: c.customer_id,
            full_name:   c.full_name,
            phone:       c.phone_mobile || c.phone_landline || '',
            email:       c.email || '',
            city:        c.city || '',
        }));
    },

    getRepairHistory(customerId, limit = 0) {
        const rows = DB.findAll('repairs', { customer_id: customerId }, 'date_in DESC');
        return limit ? rows.slice(0, limit) : rows;
    },

    getInvoices(customerId) {
        return DB.findAll('invoices', { customer_id: customerId }, 'invoice_date DESC');
    },

    /** Customer::getStats() — the aggregate block on the client detail page. */
    getStats(customerId) {
        const repairs  = DB.where('repairs',  { customer_id: customerId });
        const invoices = DB.where('invoices', { customer_id: customerId });
        const open     = repairs.filter(r => !['collected', 'cancelled'].includes(r.status));

        return {
            total_repairs:  repairs.length,
            open_repairs:   open.length,
            total_invoices: invoices.length,
            total_spent:    Utils.sum(invoices.filter(i => i.status === 'paid'), 'total_amount'),
            outstanding:    Utils.sum(
                invoices.filter(i => ['sent', 'partially_paid', 'overdue'].includes(i.status)),
                i => Utils.floatVal(i.total_amount) - Utils.floatVal(i.amount_paid)),
            last_visit:     repairs.length ? Utils.sortBy(repairs, 'date_in', 'DESC')[0].date_in : null,
        };
    },

    emailExists(email, excludeId = null) {
        if (!email) return false;
        return DB.table('customers').some(c =>
            String(c.email ?? '').toLowerCase() === String(email).toLowerCase() &&
            Utils.intVal(c.customer_id) !== Utils.intVal(excludeId));
    },

    phoneExists(phone, excludeId = null) {
        if (!phone) return false;
        const p = Utils.sanitizePhone(phone);
        return DB.table('customers').some(c =>
            (Utils.sanitizePhone(c.phone_mobile) === p || Utils.sanitizePhone(c.phone_landline) === p) &&
            Utils.intVal(c.customer_id) !== Utils.intVal(excludeId));
    },

    /** Customer::getCounts() — the filter pill counts. */
    getCounts() {
        const rows = DB.table('customers');
        return {
            total:      rows.length,
            active:     rows.filter(c => c.status === 'active').length,
            inactive:   rows.filter(c => c.status === 'inactive').length,
            individual: rows.filter(c => c.client_type === 'individual').length,
            company:    rows.filter(c => c.client_type === 'company').length,
            colleagues: rows.filter(c => c.client_type === 'colleague').length,
        };
    },

    /** Customer::getForExport() → CSV rows. */
    getForExport(status = '') {
        const rows = status ? DB.where('customers', { status }) : DB.table('customers');
        return Utils.sortBy(rows, 'full_name', 'ASC');
    },

    /** Validation shared by create + update. Returns { ok, errors }. */
    validate(data, excludeId = null) {
        const errors = {};
        if (!Utils.sanitize(data.full_name)) errors.full_name = 'Name is required.';
        if (data.email && !Utils.isValidEmail(data.email)) errors.email = 'Enter a valid email address.';
        if (data.email && this.emailExists(data.email, excludeId)) errors.email = 'This email is already registered.';
        if (data.phone_mobile && !Utils.isValidPhone(data.phone_mobile)) {
            errors.phone_mobile = 'Enter a valid Bangladeshi mobile number (01XXXXXXXXX).';
        }
        if (data.phone_mobile && this.phoneExists(data.phone_mobile, excludeId)) {
            errors.phone_mobile = 'This phone number is already registered.';
        }
        if (data.postal_code && !Utils.isValidPostalCode(data.postal_code)) errors.postal_code = 'Postal code must be 4 digits.';
        if (data.vat_number && !Utils.isValidVat(data.vat_number)) errors.vat_number = 'BIN must be 9 or 13 digits.';
        if (data.tax_id && !Utils.isValidTaxId(data.tax_id)) errors.tax_id = 'NID must be 10, 13 or 17 digits.';
        return { ok: Object.keys(errors).length === 0, errors };
    },

    create(data) {
        const id = DB.create('customers', {
            ...data,
            customer_since: data.customer_since || Utils.toDbDate(new Date()),
            status: data.status || 'active',
        });
        DB.log('created', 'customer', id, `Client "${data.full_name}" created`);
        return id;
    },

    update(id, data) {
        DB.update('customers', id, data);
        DB.log('updated', 'customer', id, `Client "${data.full_name}" updated`);
        return id;
    },

    /** ON DELETE RESTRICT — refuse when repairs or invoices still reference it. */
    canDelete(id) {
        const repairs  = DB.count('repairs',  { customer_id: id });
        const invoices = DB.count('invoices', { customer_id: id });
        if (repairs || invoices) {
            return { ok: false, reason: `This client has ${repairs} repair(s) and ${invoices} invoice(s). Delete those first.` };
        }
        return { ok: true };
    },

    delete(id) {
        const c = this.findById(id);
        DB.delete('customers', id);
        DB.log('deleted', 'customer', id, `Client "${c?.full_name ?? id}" deleted`);
    },
};
