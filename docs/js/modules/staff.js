/**
 * modules/staff.js — port of StaffController + views/staff/*.php
 * Manager-and-above only, matching the PHP role gate.
 */
'use strict';

const StaffView = {

    index({ query }) {
        if (!Auth.requireRole('manager')) return;

        const filters = { search: query.search ?? '', status: query.status ?? '',
                          sort: query.sort ?? 'full_name', dir: query.dir ?? 'ASC' };
        const rows  = Staff.getRepairStats()
            .filter(s => !filters.status || s.status === filters.status)
            .filter(s => !filters.search || String(s.full_name).toLowerCase().includes(filters.search.toLowerCase()));
        const all   = Staff.getAll();

        Layout.render(`
            ${UI.pageHeader('Staff',
                `${all.length} people · ${all.filter(s => s.status === 'active').length} active`,
                `<a href="#/staff/create" class="btn btn-primary">${Icon.plus('')} Add staff</a>`)}

            <div class="card">
                ${UI.filterPills([
                    { value: '',         label: 'All',      count: all.length },
                    { value: 'active',   label: 'Active',   count: all.filter(s => s.status === 'active').length },
                    { value: 'inactive', label: 'Inactive', count: all.filter(s => s.status === 'inactive').length },
                ], filters.status, v => Router.setQuery({ status: v }))}

                <div class="filter-bar">
                    <div class="search-input-wrap">
                        ${Icon.search('search-input-icon')}
                        <input class="form-input" id="searchInput" type="search" placeholder="Search staff…"
                               value="${Utils.e(filters.search)}" autocomplete="off">
                    </div>
                </div>

                <div id="listMount"></div>
            </div>
        `);

        DataTable.render({
            mount: '#listMount',
            rows: Utils.sortBy(rows, filters.sort, filters.dir),
            sort: filters.sort, dir: filters.dir,
            columns: [
                { key: 'full_name', label: 'Name', sortable: true, render: s => `
                    <div class="staff-cell">
                        <div class="user-avatar-sm">${Utils.e(Utils.initials(s.full_name))}</div>
                        <div>
                            <a class="cust-name-link" href="#/staff/${s.staff_id}">${Utils.e(s.full_name)}</a>
                            ${s.specialization ? `<span class="vat-sub">${Utils.e(s.specialization)}</span>` : ''}
                        </div>
                    </div>` },
                { key: 'role', label: 'Role', sortable: true, render: s => Badge.role(s.role) },
                { key: 'phone', label: 'Contact', hideOnTablet: true, render: s => `
                    ${s.phone ? `<a class="ph-lnk" href="tel:${Utils.e(s.phone)}">${Utils.e(s.phone)}</a><br>` : ''}
                    ${s.email ? `<a class="em-lnk" href="mailto:${Utils.e(s.email)}">${Utils.e(Utils.truncate(s.email, 24))}</a>` : ''}` },
                { key: 'open_repairs', label: 'Open', sortable: true, align: 'right',
                  render: s => `<span class="badge ${s.open_repairs > 6 ? 'badge-orange' : 'badge-gray'}">${s.open_repairs}</span>` },
                { key: 'total_repairs', label: `Total ${L.jobMany.toLowerCase()}`, sortable: true, align: 'right',
                  render: s => Utils.numberFormat(s.total_repairs) },
                { key: 'revenue', label: 'Revenue', sortable: true, align: 'right', hideOnTablet: true,
                  render: s => Utils.formatCurrency(s.revenue) },
                { key: 'status', label: 'Status', sortable: true, render: s => Badge.active(s.status) },
            ],
            actions: s =>
                DataTable.act.view(`#/staff/${s.staff_id}`) +
                DataTable.act.edit(`#/staff/${s.staff_id}/edit`) +
                (Auth.isAdmin() ? DataTable.act.del(s.staff_id) : ''),
            empty: { message: 'No staff records.', icon: 'user',
                     action: '<a href="#/staff/create" class="btn btn-primary">Add staff</a>' },
            onSort: (col, dir) => Router.setQuery({ sort: col, dir }),
        });

        const search = document.getElementById('searchInput');
        search.oninput = UI.debounce(() => Router.setQuery({ search: search.value }), 300);
        UI.bindFilterPills();

        DataTable.bindDelete('#listMount', {
            message: id => {
                const s = Staff.findById(id);
                return `Remove ${s?.full_name}? Their ${L.jobMany.toLowerCase()} stay on record but become unassigned.`;
            },
            onConfirm: id => { Staff.delete(id); Toast.success('Staff member removed.'); Router.reload(); },
        });

        if (filters.search) { search.focus(); search.setSelectionRange(search.value.length, search.value.length); }
    },

    show({ params }) {
        if (!Auth.requireRole('manager')) return;

        const s = Staff.findById(params.id);
        if (!s) return AuthView.notFound(`/staff/${params.id}`);
        const stats   = Staff.getStats(s.staff_id);
        const repairs = Staff.getRepairs(s.staff_id, 12);

        Layout.render(`
            ${UI.backLink('#/staff', 'All staff')}

            ${UI.pageHeader(s.full_name,
                `${Badge.role(s.role)} ${Badge.active(s.status)}
                 ${s.specialization ? `&nbsp;·&nbsp; ${Utils.e(s.specialization)}` : ''}
                 &nbsp;·&nbsp; Joined ${Utils.formatDate(s.hire_date)}`,
                `<a href="#/staff/${s.staff_id}/edit" class="btn btn-secondary">${Icon.edit('')} Edit</a>`)}

            <div class="stats-grid">
                ${UI.statCard({ label: `Total ${L.jobMany.toLowerCase()}`, value: Utils.numberFormat(stats.total_repairs), icon: 'wrench', tone: 'accent' })}
                ${UI.statCard({ label: `Open ${L.jobMany.toLowerCase()}`, value: Utils.numberFormat(stats.open_repairs), icon: 'clock', tone: 'orange' })}
                ${UI.statCard({ label: 'Completed',   value: Utils.numberFormat(stats.completed),     icon: 'check',  tone: 'green' })}
                ${UI.statCard({ label: 'Revenue',     value: Utils.formatCurrencyShort(stats.revenue),icon: 'money',  tone: 'blue' })}
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-main">
                    <div class="card">
                        <div class="card-header"><h2 class="card-title">Assigned ${Utils.e(L.jobMany.toLowerCase())}</h2></div>
                        <div id="jobsMount"></div>
                    </div>
                </div>
                <aside class="dashboard-aside">
                    <div class="card">
                        <div class="card-header"><h2 class="card-title">Details</h2></div>
                        <div class="card-body">
                            ${UI.field('Role', USER_ROLES[s.role] ?? s.role)}
                            ${UI.field('Specialisation', s.specialization)}
                            ${UI.field('Mobile', s.phone)}
                            ${UI.field('Email', s.email)}
                            ${UI.field('Hired', Utils.formatDate(s.hire_date))}
                            ${UI.field('Status', Badge.active(s.status), true)}
                        </div>
                    </div>
                </aside>
            </div>
        `);

        DataTable.render({
            mount: '#jobsMount',
            rows: repairs,
            columns: [
                { key: 'repair_id', label: '#', width: '56px',
                  render: r => `<a class="table-link" href="#/repairs/${r.repair_id}">#${r.repair_id}</a>` },
                { key: 'customer_name', label: L.clientOne, render: r => Utils.e(Utils.truncate(r.customer_name ?? '—', 22)) },
                { key: 'device_model', label: L.itemLabel, hideOnTablet: true, render: r => Utils.e(Utils.truncate(r.device_model, 26)) },
                { key: 'date_in', label: 'In', hideOnTablet: true, render: r => Utils.formatDate(r.date_in) },
                { key: 'status', label: 'Status', render: r => Badge.repair(r.status) },
            ],
            empty: { message: `No ${L.jobMany.toLowerCase()} assigned to this person yet.`, icon: 'wrench' },
        });
    },

    create() { if (Auth.requireRole('manager')) this._form(null); },

    edit({ params }) {
        if (!Auth.requireRole('manager')) return;
        const s = Staff.findById(params.id);
        if (!s) return AuthView.notFound(`/staff/${params.id}`);
        this._form(s);
    },

    _form(s = null) {
        const isEdit = !!s;
        const v = f => Utils.e(s?.[f] ?? '');

        Layout.render(`
            ${UI.backLink(isEdit ? `#/staff/${s.staff_id}` : '#/staff', isEdit ? 'Back to profile' : 'All staff')}
            ${UI.pageHeader(isEdit ? `Edit ${s.full_name}` : 'Add staff member')}

            <form id="staffForm" class="card" novalidate>
                <div class="card-body">
                    <div class="form-grid-2">
                        <div class="form-group form-col-full">
                            <label class="form-label required" for="full_name">Full name</label>
                            <input class="form-input" id="full_name" name="full_name" value="${v('full_name')}" required>
                            <span class="field-error" id="err-full_name"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label required" for="role">Role</label>
                            <select class="form-select" id="role" name="role">
                                ${Object.entries(USER_ROLES).map(([k, lbl]) =>
                                    `<option value="${k}" ${(s?.role ?? 'technician') === k ? 'selected' : ''}>${Utils.e(lbl)}</option>`).join('')}
                            </select>
                            <span class="field-error" id="err-role"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active"   ${(s?.status ?? 'active') === 'active'   ? 'selected' : ''}>Active</option>
                                <option value="inactive" ${(s?.status ?? '')       === 'inactive' ? 'selected' : ''}>Inactive</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="phone">Mobile</label>
                            <input class="form-input" id="phone" name="phone" value="${v('phone')}" placeholder="01XXXXXXXXX">
                            <span class="field-error" id="err-phone"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input class="form-input" id="email" name="email" type="email" value="${v('email')}">
                            <span class="field-error" id="err-email"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="specialization">Specialisation</label>
                            <input class="form-input" id="specialization" name="specialization" value="${v('specialization')}"
                                   placeholder="${Utils.e(L.specialisation)}">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="hire_date">Hire date</label>
                            <input class="form-input" id="hire_date" name="hire_date" type="date"
                                   value="${Utils.e(s?.hire_date ?? Utils.toDbDate(new Date()))}">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="${isEdit ? `#/staff/${s.staff_id}` : '#/staff'}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">${isEdit ? 'Save changes' : 'Add staff member'}</button>
                </div>
            </form>
        `);

        document.getElementById('staffForm').onsubmit = e => {
            e.preventDefault();
            const data = Forms.collect(e.target);
            const { ok, errors } = Staff.validate(data, s?.staff_id ?? null);
            Forms.clearErrors(e.target);
            if (!ok) { Forms.showErrors(errors); Toast.error('Please fix the highlighted fields.'); return; }

            if (isEdit) { Staff.update(s.staff_id, data); Toast.success('Staff updated.'); Router.go(`/staff/${s.staff_id}`); }
            else        { const id = Staff.create(data); Toast.success('Staff member added.'); Router.go(`/staff/${id}`); }
        };
    },
};
