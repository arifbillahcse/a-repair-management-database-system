/**
 * models/repair.js — port of models/Repair.php
 * The JOINs onto customers/staff are done with DB.join().
 */
'use strict';

const Repair = {

    /** Repair::findById() — with customer + technician columns joined on. */
    findById(id) {
        const row = DB.findById('repairs', id);
        if (!row) return null;
        return this.withRelations([row])[0];
    },

    withRelations(rows) {
        let out = DB.join(rows, 'customers', 'customer_id', {
            full_name: 'customer_name', phone_mobile: 'customer_phone',
            email: 'customer_email', city: 'customer_city', client_type: 'customer_type',
        });
        out = DB.join(out, 'staff', 'staff_id', { full_name: 'technician_name' });
        return out.map(r => ({
            ...r,
            days_in_lab: Utils.daysBetween(r.date_in, r.date_out || new Date()),
        }));
    },

    /** Repair::getAll() — filters + sort + pagination. */
    getAll(filters = {}, page = 1, perPage = PAGE_SIZE_REPAIRS) {
        let rows = DB.table('repairs');

        const q = Utils.sanitize(filters.search).toLowerCase();
        if (q) {
            const custIds = new Set(Customer.search(q, 9999).map(c => c.customer_id));
            rows = rows.filter(r =>
                String(r.device_model ?? '').toLowerCase().includes(q) ||
                String(r.device_serial_number ?? '').toLowerCase().includes(q) ||
                String(r.problem_description ?? '').toLowerCase().includes(q) ||
                String(r.qr_code ?? '').toLowerCase().includes(q) ||
                String(r.repair_id) === q ||
                custIds.has(r.customer_id));
        }
        if (filters.status)   rows = rows.filter(r => r.status === filters.status);
        if (filters.staff_id) rows = rows.filter(r => Utils.intVal(r.staff_id) === Utils.intVal(filters.staff_id));
        if (filters.customer_id) rows = rows.filter(r => Utils.intVal(r.customer_id) === Utils.intVal(filters.customer_id));
        if (filters.date_from) rows = rows.filter(r => Utils.toDbDate(r.date_in) >= filters.date_from);
        if (filters.date_to)   rows = rows.filter(r => Utils.toDbDate(r.date_in) <= filters.date_to);
        if (filters.open === '1') rows = rows.filter(r => !['collected', 'cancelled'].includes(r.status));

        rows = this.withRelations(rows);
        rows = Utils.sortBy(rows, filters.sort || 'date_in', filters.dir || 'DESC');

        const meta = Utils.paginate(rows.length, page, perPage);
        return { data: rows.slice(meta.offset, meta.offset + perPage), pagination: meta, total: rows.length };
    },

    /** Repair::getStatusCounts() — GROUP BY status. */
    getStatusCounts() {
        const counts = Object.fromEntries(Object.keys(REPAIR_STATUS).map(k => [k, 0]));
        DB.table('repairs').forEach(r => { counts[r.status] = (counts[r.status] ?? 0) + 1; });
        counts.total = DB.table('repairs').length;
        counts.open  = counts.total - counts.collected - counts.cancelled;
        return counts;
    },

    /** Repair::getStatistics() — the dashboard stat cards. */
    getStatistics() {
        const rows  = DB.table('repairs');
        const c     = this.getStatusCounts();
        const today = Utils.toDbDate(new Date());
        const month = today.slice(0, 7);

        const done = rows.filter(r => r.date_out);
        return {
            ...c,
            in_today:       rows.filter(r => Utils.toDbDate(r.date_in) === today).length,
            in_this_month:  rows.filter(r => Utils.toDbDate(r.date_in).startsWith(month)).length,
            revenue_month:  Utils.sum(
                rows.filter(r => r.date_out && Utils.toDbDate(r.date_out).startsWith(month)), 'actual_amount'),
            revenue_total:  Utils.sum(rows, 'actual_amount'),
            avg_turnaround: done.length
                ? Utils.round2(Utils.sum(done, r => Utils.daysBetween(r.date_in, r.date_out)) / done.length)
                : 0,
            total_customers: DB.table('customers').length,
        };
    },

    getReadyForPickup() {
        return this.withRelations(DB.where('repairs', { status: 'ready_for_pickup' }))
            .sort((a, b) => String(a.date_out ?? '').localeCompare(String(b.date_out ?? '')));
    },

    /** Repair::getOverduePickups() — ready but not collected for N+ days. */
    getOverduePickups(daysThreshold = 7) {
        return this.getReadyForPickup()
            .filter(r => Utils.daysBetween(r.date_out || r.date_in) >= daysThreshold);
    },

    getRecentRepairs(limit = 10) {
        return this.withRelations(Utils.sortBy(DB.table('repairs'), 'date_in', 'DESC').slice(0, limit));
    },

    getByCustomerId(customerId) {
        return this.withRelations(DB.where('repairs', { customer_id: customerId }))
            .sort((a, b) => String(b.date_in).localeCompare(String(a.date_in)));
    },

    /** Repair::getMonthlyRevenue() — 12-point series for the dashboard chart. */
    getMonthlyRevenue(months = 12) {
        const rows = DB.table('repairs');
        const out  = [];
        const now  = new Date();

        for (let i = months - 1; i >= 0; i--) {
            const d  = new Date(now.getFullYear(), now.getMonth() - i, 1);
            const ym = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
            const inMonth = rows.filter(r => r.date_out && Utils.toDbDate(r.date_out).startsWith(ym));
            out.push({ month: ym, label: Utils.monthLabel(ym), revenue: Utils.sum(inMonth, 'actual_amount'), jobs: inMonth.length });
        }
        return out;
    },

    /* ── Status transitions ───────────────────────────────────────────────── */

    allowedTransitions(status) { return REPAIR_STATUS_FLOW[status] ?? []; },

    canTransition(from, to) { return this.allowedTransitions(from).includes(to); },

    updateStatus(id, newStatus) {
        const r = DB.findById('repairs', id);
        if (!r) return { ok: false, error: 'Repair not found.' };
        if (!this.canTransition(r.status, newStatus)) {
            return { ok: false, error: `Cannot move from "${REPAIR_STATUS[r.status]}" to "${REPAIR_STATUS[newStatus]}".` };
        }

        const patch = { status: newStatus };
        // Completing the job stamps the exit date, mirroring the PHP.
        if (newStatus === 'completed' && !r.date_out) patch.date_out = Utils.now();
        if (newStatus === 'collected') patch.collected_at = Utils.now();

        DB.update('repairs', id, patch);
        DB.log('status_changed', 'repair', id, `Repair #${id}: ${REPAIR_STATUS[r.status]} → ${REPAIR_STATUS[newStatus]}`);
        return { ok: true };
    },

    /* ── QR ───────────────────────────────────────────────────────────────── */

    generateQRCode(repairId) {
        return `RMS-${String(repairId).padStart(6, '0')}`;
    },

    findByQRCode(code) {
        const row = DB.findOneBy('repairs', 'qr_code', String(code).trim().toUpperCase());
        return row ? this.findById(row.repair_id) : null;
    },

    /* ── Photos (base64 in localStorage, capped for the demo) ─────────────── */

    getPhotos(repairId) {
        const r = DB.findById('repairs', repairId);
        try { return JSON.parse(r?.photo_path ?? '[]'); } catch { return []; }
    },

    addPhoto(repairId, dataUrl) {
        const photos = this.getPhotos(repairId);
        if (photos.length >= 4) return { ok: false, error: 'Demo limit: 4 photos per repair.' };
        photos.push(dataUrl);
        DB.update('repairs', repairId, { photo_path: JSON.stringify(photos) });
        return { ok: true };
    },

    removePhoto(repairId, index) {
        const photos = this.getPhotos(repairId);
        photos.splice(index, 1);
        DB.update('repairs', repairId, { photo_path: JSON.stringify(photos) });
    },

    /* ── Validation + writes ──────────────────────────────────────────────── */

    validate(data) {
        const errors = {};
        if (!Utils.intVal(data.customer_id))       errors.customer_id = 'Select a client.';
        if (!Utils.sanitize(data.device_model))    errors.device_model = 'Device model is required.';
        if (!Utils.sanitize(data.problem_description)) errors.problem_description = 'Describe the problem.';
        if (data.estimate_amount && Utils.floatVal(data.estimate_amount) < 0) errors.estimate_amount = 'Estimate cannot be negative.';
        if (data.actual_amount && Utils.floatVal(data.actual_amount) < 0) errors.actual_amount = 'Amount cannot be negative.';
        return { ok: Object.keys(errors).length === 0, errors };
    },

    create(data) {
        const user = Auth.user();
        const id = DB.create('repairs', {
            ...data,
            date_in:    data.date_in || Utils.now(),
            status:     data.status || 'in_progress',
            created_by: user?.user_id ?? null,
            photo_path: '[]',
        });
        DB.update('repairs', id, { qr_code: this.generateQRCode(id) });
        DB.log('created', 'repair', id, `Repair #${id} created for ${data.device_model}`);
        return id;
    },

    update(id, data) {
        DB.update('repairs', id, data);
        DB.log('updated', 'repair', id, `Repair #${id} updated`);
        return id;
    },

    canDelete(id) {
        const invoices = DB.count('invoices', { repair_id: id });
        return invoices
            ? { ok: false, reason: `This repair has ${invoices} invoice(s) attached. Delete those first.` }
            : { ok: true };
    },

    delete(id) {
        DB.delete('repairs', id);
        DB.log('deleted', 'repair', id, `Repair #${id} deleted`);
    },
};
