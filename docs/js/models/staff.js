/**
 * models/staff.js — port of models/Staff.php + models/User.php
 */
'use strict';

const Staff = {

    findById(id) { return DB.findById('staff', id); },

    getAll(filters = {}) {
        let rows = DB.table('staff');
        const q = Utils.sanitize(filters.search).toLowerCase();
        if (q) rows = rows.filter(s =>
            String(s.full_name ?? '').toLowerCase().includes(q) ||
            String(s.email ?? '').toLowerCase().includes(q) ||
            String(s.role ?? '').toLowerCase().includes(q));
        if (filters.status) rows = rows.filter(s => s.status === filters.status);
        if (filters.role)   rows = rows.filter(s => s.role === filters.role);
        return Utils.sortBy(rows, filters.sort || 'full_name', filters.dir || 'ASC');
    },

    /** Staff::getRepairStats() — the dashboard technician leaderboard. */
    getRepairStats() {
        return this.getAll({ status: 'active' }).map(s => {
            const jobs = DB.where('repairs', { staff_id: s.staff_id });
            const open = jobs.filter(r => !['collected', 'cancelled'].includes(r.status));
            const done = jobs.filter(r => r.date_out);
            return {
                ...s,
                total_repairs: jobs.length,
                open_repairs:  open.length,
                revenue:       Utils.sum(jobs, 'actual_amount'),
                avg_days:      done.length
                    ? Utils.round2(Utils.sum(done, r => Utils.daysBetween(r.date_in, r.date_out)) / done.length)
                    : 0,
            };
        }).sort((a, b) => b.total_repairs - a.total_repairs);
    },

    getStats(staffId) {
        const jobs = DB.where('repairs', { staff_id: staffId });
        return {
            total_repairs: jobs.length,
            open_repairs:  jobs.filter(r => !['collected', 'cancelled'].includes(r.status)).length,
            completed:     jobs.filter(r => ['completed', 'collected'].includes(r.status)).length,
            revenue:       Utils.sum(jobs, 'actual_amount'),
        };
    },

    getRepairs(staffId, limit = 0) {
        const rows = Repair.withRelations(DB.where('repairs', { staff_id: staffId }))
            .sort((a, b) => String(b.date_in).localeCompare(String(a.date_in)));
        return limit ? rows.slice(0, limit) : rows;
    },

    /** Technicians available for assignment on the repair form. */
    technicians() {
        return this.getAll({ status: 'active' });
    },

    validate(data, excludeId = null) {
        const errors = {};
        if (!Utils.sanitize(data.full_name)) errors.full_name = 'Name is required.';
        if (!data.role)                      errors.role = 'Select a role.';
        if (data.email && !Utils.isValidEmail(data.email)) errors.email = 'Enter a valid email address.';
        if (data.email && DB.table('staff').some(s =>
            String(s.email ?? '').toLowerCase() === String(data.email).toLowerCase() &&
            Utils.intVal(s.staff_id) !== Utils.intVal(excludeId))) {
            errors.email = 'This email is already used by another staff member.';
        }
        if (data.phone && !Utils.isValidPhone(data.phone)) errors.phone = 'Enter a valid Bangladeshi mobile number.';
        return { ok: Object.keys(errors).length === 0, errors };
    },

    create(data) {
        const id = DB.create('staff', { ...data, status: data.status || 'active', hire_date: data.hire_date || Utils.toDbDate(new Date()) });
        DB.log('created', 'staff', id, `Staff "${data.full_name}" added`);
        return id;
    },

    update(id, data) {
        DB.update('staff', id, data);
        DB.log('updated', 'staff', id, `Staff "${data.full_name}" updated`);
        return id;
    },

    /** ON DELETE SET NULL — repairs survive, they just lose the assignee. */
    delete(id) {
        const s = this.findById(id);
        DB.where('repairs', { staff_id: id }).forEach(r => DB.update('repairs', r.repair_id, { staff_id: null }));
        DB.delete('staff', id);
        DB.log('deleted', 'staff', id, `Staff "${s?.full_name ?? id}" removed`);
    },
};

const User = {

    findById(id) { return DB.findById('users', id); },

    getAll() { return Utils.sortBy(DB.table('users'), 'full_name', 'ASC'); },

    toggleStatus(id) {
        const u = this.findById(id);
        if (!u) return { ok: false, error: 'User not found.' };
        if (Utils.intVal(id) === Utils.intVal(Auth.user()?.user_id)) {
            return { ok: false, error: 'You cannot disable your own account.' };
        }
        const next = u.status === 'active' ? 'inactive' : 'active';
        DB.update('users', id, { status: next });
        DB.log('updated', 'user', id, `User "${u.username}" ${next === 'active' ? 'enabled' : 'disabled'}`);
        return { ok: true, status: next };
    },

    /** Demo only — no password is ever stored, so this just logs the action. */
    resetPassword(id) {
        const u = this.findById(id);
        if (!u) return { ok: false, error: 'User not found.' };
        DB.update('users', id, { password_reset_at: Utils.now() });
        DB.log('updated', 'user', id, `Password reset for "${u.username}"`);
        return { ok: true };
    },
};
