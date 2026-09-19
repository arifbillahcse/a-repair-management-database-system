/**
 * modules/repairs.js — port of RepairController + views/repairs/*.php
 * Covers: list, detail (with status timeline + QR + photos), create, edit,
 * status transitions, delete, print job sheet.
 */
'use strict';

const Repairs = {

    /* ── GET /repairs ─────────────────────────────────────────────────────── */

    index({ query }) {
        if (!Auth.requireAuth()) return;

        const filters = {
            search: query.search ?? '', status: query.status ?? '', staff_id: query.staff_id ?? '',
            open:   query.open ?? '',
            sort:   query.sort ?? 'date_in', dir: query.dir ?? 'DESC',
        };
        const page   = Utils.intVal(query.page) || 1;
        const result = Repair.getAll(filters, page);
        const counts = Repair.getStatusCounts();
        const techs  = Staff.technicians();

        Layout.render(`
            ${UI.pageHeader(L.jobMany,
                `${Utils.numberFormat(counts.total)} total &nbsp;·&nbsp;
                 <span style="color:var(--accent)">${Utils.numberFormat(counts.open)} open</span> &nbsp;·&nbsp;
                 ${Utils.numberFormat(counts.ready_for_pickup)} ${Utils.e(L.queueLabel.toLowerCase())}`,
                `<button class="btn btn-secondary" id="qrBtn">${Icon.search('')} QR lookup</button>
                 <a href="#/repairs/create" class="btn btn-primary">${Icon.plus('')} ${Utils.e(L.jobNew)}</a>`
            )}

            <div class="card">
                ${UI.filterPills([
                    { value: '',                  label: 'All',        count: counts.total },
                    { value: 'in_progress',       label: REPAIR_STATUS.in_progress, count: counts.in_progress },
                    { value: 'waiting_for_parts', label: REPAIR_STATUS.waiting_for_parts, count: counts.waiting_for_parts, cls: 'sf-btn-orange' },
                    { value: 'on_hold',           label: REPAIR_STATUS.on_hold,    count: counts.on_hold },
                    { value: 'ready_for_pickup',  label: REPAIR_STATUS.ready_for_pickup, count: counts.ready_for_pickup },
                    { value: 'completed',         label: REPAIR_STATUS.completed,  count: counts.completed },
                    { value: 'collected',         label: REPAIR_STATUS.collected,  count: counts.collected },
                    { value: 'cancelled',         label: REPAIR_STATUS.cancelled,  count: counts.cancelled },
                ], filters.status, v => Router.setQuery({ status: v, open: '', page: 1 }))}

                <div class="filter-bar">
                    <div class="search-input-wrap">
                        ${Icon.search('search-input-icon')}
                        <input class="form-input" id="searchInput" type="search"
                               placeholder="${Utils.e(`Search ${L.itemLabel.toLowerCase()}, ${L.serialLabel.toLowerCase()}, problem, QR, ${L.clientOne.toLowerCase()}…`)}"
                               value="${Utils.e(filters.search)}" autocomplete="off">
                    </div>
                    <select class="form-select filter-select" id="techFilter">
                        <option value="">All ${Utils.e(L.staffMany.toLowerCase())}</option>
                        ${techs.map(t => `<option value="${t.staff_id}" ${Utils.intVal(filters.staff_id) === t.staff_id ? 'selected' : ''}>${Utils.e(t.full_name)}</option>`).join('')}
                    </select>
                    ${filters.search || filters.status || filters.staff_id || filters.open
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
                { key: 'repair_id', label: '#', sortable: true, width: '62px',
                  render: r => `<a class="table-link" href="#/repairs/${r.repair_id}">#${r.repair_id}</a>` },
                { key: 'customer_name', label: L.clientOne, sortable: true, render: r => `
                    <a class="cust-name-link" href="#/customers/${r.customer_id}">${Utils.e(Utils.truncate(r.customer_name ?? '—', 22))}</a>
                    ${r.customer_phone ? `<span class="vat-sub">${Utils.e(r.customer_phone)}</span>` : ''}` },
                { key: 'device_model', label: L.itemLabel, sortable: true, render: r => `
                    ${Utils.e(Utils.truncate(r.device_model, 26))}
                    ${r.device_serial_number ? `<span class="vat-sub">${Utils.e(r.device_serial_number)}</span>` : ''}` },
                { key: 'technician_name', label: L.staffOne, hideOnTablet: true,
                  render: r => r.technician_name ? Utils.e(Utils.truncate(r.technician_name, 18)) : '<span class="text-muted">Unassigned</span>' },
                { key: 'date_in', label: 'In', sortable: true, hideOnTablet: true,
                  render: r => `${Utils.formatDate(r.date_in)}<span class="vat-sub">${r.days_in_lab}d</span>` },
                { key: 'actual_amount', label: 'Amount', sortable: true, align: 'right',
                  render: r => r.actual_amount
                    ? Utils.formatCurrency(r.actual_amount)
                    : (r.estimate_amount ? `<span class="text-muted">est. ${Utils.formatCurrency(r.estimate_amount)}</span>` : '—') },
                { key: 'status', label: 'Status', sortable: true, render: r => Badge.repair(r.status) },
            ],
            rowClass: r => (r.status === 'ready_for_pickup' && r.days_in_lab >= 7) ? 'row-warn' : '',
            actions: r =>
                DataTable.act.view(`#/repairs/${r.repair_id}`) +
                DataTable.act.edit(`#/repairs/${r.repair_id}/edit`) +
                DataTable.act.print(`#/repairs/${r.repair_id}/print`) +
                (Auth.can('manager') ? DataTable.act.del(r.repair_id) : ''),
            empty: {
                message: filters.search
                    ? `No ${L.jobMany.toLowerCase()} match "${filters.search}".`
                    : `No ${L.jobMany.toLowerCase()} logged yet.`,
                icon: 'wrench',
                action: `<a href="#/repairs/create" class="btn btn-primary">Log the first ${Utils.e(L.jobLower)}</a>`,
            },
            onSort: (col, dir) => Router.setQuery({ sort: col, dir, page: 1 }),
            onPage: p => Router.setQuery({ page: p }),
        });

        const search = document.getElementById('searchInput');
        search.oninput = UI.debounce(() => Router.setQuery({ search: search.value, page: 1 }), 350);
        document.getElementById('techFilter').onchange = e => Router.setQuery({ staff_id: e.target.value, page: 1 });
        document.getElementById('clearFilters')?.addEventListener('click', () => Router.go('/repairs'));
        document.getElementById('qrBtn').onclick = () => this.qrLookup();
        UI.bindFilterPills();

        DataTable.bindDelete('#listMount', {
            message: id => `Delete ${L.jobLower} #${id}? This cannot be undone.`,
            onConfirm: id => this.destroy(id),
        });

        if (filters.search) { search.focus(); search.setSelectionRange(search.value.length, search.value.length); }
    },

    /* ── GET /repairs/:id ─────────────────────────────────────────────────── */

    show({ params }) {
        if (!Auth.requireAuth()) return;

        const r = Repair.findById(params.id);
        if (!r) return AuthView.notFound(`/repairs/${params.id}`);

        const invoices = DB.where('invoices', { repair_id: r.repair_id });
        const photos   = Repair.getPhotos(r.repair_id);
        const next     = Repair.allowedTransitions(r.status);

        Layout.render(`
            ${UI.backLink('#/repairs', `All ${L.jobMany.toLowerCase()}`)}

            ${UI.pageHeader(`${L.jobOne} #${r.repair_id}`,
                `${Badge.repair(r.status)} &nbsp;·&nbsp; ${Utils.e(r.device_model)}
                 &nbsp;·&nbsp; ${r.days_in_lab} ${Utils.e(L.dwellLabel)}`,
                `<a href="#/repairs/${r.repair_id}/print" class="btn btn-secondary">${Icon.print('')} ${Utils.e(L.jobSheet)}</a>
                 <a href="#/repairs/${r.repair_id}/edit" class="btn btn-secondary">${Icon.edit('')} Edit</a>
                 ${!invoices.length && ['completed','ready_for_pickup','collected'].includes(r.status)
                    ? `<a href="#/repairs/${r.repair_id}/invoice" class="btn btn-primary">${Icon.invoice('')} Create invoice</a>` : ''}`
            )}

            <div class="dashboard-grid">
                <div class="dashboard-main">

                    <div class="card">
                        <div class="card-header"><h2 class="card-title">${Utils.e(L.jobOne)} details</h2></div>
                        <div class="card-body">
                            ${UI.field(L.itemLabel, r.device_model)}
                            ${UI.field(L.serialLabel, r.device_serial_number)}
                            ${UI.field('Received', Utils.formatDateTime(r.date_in))}
                            ${UI.field('Completed', r.date_out ? Utils.formatDateTime(r.date_out) : '')}
                            ${UI.field('Expected pickup', r.collection_date ? Utils.formatDate(r.collection_date) : '')}
                            ${UI.field(L.staffOne, r.technician_name
                                ? `<a class="table-link" href="#/staff/${r.staff_id}">${Utils.e(r.technician_name)}</a>`
                                : '<span class="text-muted">Unassigned</span>', true)}
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h2 class="card-title">${Utils.e(L.diagnosisLabel)} &amp; work</h2></div>
                        <div class="card-body">
                            <div class="detail-block">
                                <h3 class="detail-block-title">${Utils.e(L.problemLabel)}</h3>
                                <p class="note-text">${Utils.e(r.problem_description ?? '—')}</p>
                            </div>
                            <div class="detail-block">
                                <h3 class="detail-block-title">${Utils.e(L.diagnosisLabel)}</h3>
                                <p class="note-text">${r.diagnosis ? Utils.e(r.diagnosis) : '<span class="text-muted">Not diagnosed yet.</span>'}</p>
                            </div>
                            <div class="detail-block">
                                <h3 class="detail-block-title">${Utils.e(L.workLabel)}</h3>
                                <p class="note-text">${r.work_done ? Utils.e(r.work_done) : '<span class="text-muted">Not completed yet.</span>'}</p>
                            </div>
                            ${r.notes ? `
                            <div class="detail-block">
                                <h3 class="detail-block-title">Internal notes</h3>
                                <p class="note-text">${Utils.e(r.notes)}</p>
                            </div>` : ''}
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Photos</h2>
                            <label class="btn btn-xs btn-secondary" for="photoInput">${Icon.plus('')} Add photo</label>
                            <input type="file" id="photoInput" accept="image/*" hidden>
                        </div>
                        <div class="card-body">
                            <div class="photo-grid" id="photoGrid">
                                ${photos.length
                                    ? photos.map((src, i) => `
                                        <figure class="photo-item">
                                            <img src="${src}" alt="Repair photo ${i + 1}" loading="lazy">
                                            <button class="photo-del" data-photo="${i}" aria-label="Remove photo">&times;</button>
                                        </figure>`).join('')
                                    : `<p class="text-muted small">No photos attached. Add one to show the ${Utils.e(L.itemLabel.toLowerCase())} condition on intake.</p>`}
                            </div>
                        </div>
                    </div>

                </div>

                <aside class="dashboard-aside">

                    <div class="card">
                        <div class="card-header"><h2 class="card-title">Status</h2></div>
                        <div class="card-body">
                            ${this._timeline(r.status)}
                            ${next.length ? `
                                <div class="status-actions">
                                    <span class="form-label">Move to</span>
                                    ${next.map(s => `
                                        <button class="btn btn-sm ${s === 'cancelled' ? 'btn-danger' : 'btn-secondary'}" data-status="${s}">
                                            ${Utils.e(REPAIR_STATUS[s])}
                                        </button>`).join('')}
                                </div>`
                                : `<p class="text-muted small">This ${Utils.e(L.jobLower)} is closed — no further transitions.</p>`}
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h2 class="card-title">${Utils.e(L.clientOne)}</h2></div>
                        <div class="card-body">
                            ${UI.field('Name', `<a class="table-link" href="#/customers/${r.customer_id}">${Utils.e(r.customer_name)}</a>`, true)}
                            ${UI.field('Mobile', r.customer_phone ? `<a class="ph-lnk" href="tel:${Utils.e(r.customer_phone)}">${Utils.e(r.customer_phone)}</a>` : '', true)}
                            ${UI.field('Email', r.customer_email)}
                            ${UI.field('City', r.customer_city)}
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h2 class="card-title">Pricing</h2></div>
                        <div class="card-body">
                            ${UI.field(L.estimateLabel, r.estimate_amount ? Utils.formatCurrency(r.estimate_amount) : '')}
                            ${UI.field('Final amount', r.actual_amount
                                ? `<strong>${Utils.formatCurrency(r.actual_amount)}</strong>` : '', true)}
                            ${r.estimate_amount && r.actual_amount ? UI.field('Difference',
                                `<span class="${r.actual_amount > r.estimate_amount ? 'text-danger' : ''}">
                                    ${Utils.formatCurrency(r.actual_amount - r.estimate_amount)}</span>`, true) : ''}
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h2 class="card-title">Tracking QR</h2></div>
                        <div class="card-body ta-center">
                            <div class="qr-code-img" id="qrBox" role="img"
                                 aria-label="QR code for repair ${r.repair_id}"></div>
                            <p class="qr-code-text">${Utils.e(r.qr_code ?? '—')}</p>
                            <p class="text-muted small">Scan it to pull this ${Utils.e(L.jobLower)} up instantly.</p>
                        </div>
                    </div>

                    ${invoices.length ? `
                    <div class="card">
                        <div class="card-header"><h2 class="card-title">Invoices</h2></div>
                        <div class="card-body">
                            ${Invoice.withRelations(invoices).map(i => `
                                <div class="detail-row">
                                    <a class="table-link" href="#/invoices/${i.invoice_id}">${Utils.e(i.invoice_number)}</a>
                                    <span>${Utils.formatCurrency(i.total_amount)} ${i.is_overdue ? Badge.invoice('overdue') : Badge.invoice(i.status)}</span>
                                </div>`).join('')}
                        </div>
                    </div>` : ''}

                    ${Auth.can('manager') ? `
                    <div class="card card-danger">
                        <div class="card-header"><h2 class="card-title">Danger zone</h2></div>
                        <div class="card-body">
                            <button class="btn btn-danger btn-full" id="deleteBtn">${Icon.trash('')} Delete ${Utils.e(L.jobLower)}</button>
                        </div>
                    </div>` : ''}

                </aside>
            </div>
        `);

        /* QR is generated locally — no third-party image service is contacted. */
        QR.render('qrBox', r.qr_code ?? '');

        /* Status transitions */
        document.querySelectorAll('[data-status]').forEach(b => {
            b.onclick = async () => {
                const to = b.dataset.status;
                if (to === 'cancelled') {
                    const ok = await Modal.confirm({
                        title: `Cancel ${L.jobLower}`,
                        message: `Cancel ${L.jobLower} #${r.repair_id}? It stays on record but is closed.`,
                        confirmLabel: `Cancel ${L.jobLower}`,
                    });
                    if (!ok) return;
                }
                const res = Repair.updateStatus(r.repair_id, to);
                if (!res.ok) return Toast.error(res.error);
                Toast.success(`Moved to "${REPAIR_STATUS[to]}".`);
                Router.reload();
            };
        });

        /* Photos */
        document.getElementById('photoInput').onchange = e => {
            const file = e.target.files[0];
            if (!file) return;
            if (file.size > 2 * 1024 * 1024) return Toast.error('Demo limit: images must be under 2 MB.');
            const reader = new FileReader();
            reader.onload = () => {
                const res = Repair.addPhoto(r.repair_id, reader.result);
                if (!res.ok) return Toast.error(res.error);
                Toast.success('Photo attached.');
                Router.reload();
            };
            reader.readAsDataURL(file);
        };

        document.querySelectorAll('[data-photo]').forEach(b => {
            b.onclick = () => {
                Repair.removePhoto(r.repair_id, Utils.intVal(b.dataset.photo));
                Toast.info('Photo removed.');
                Router.reload();
            };
        });

        document.getElementById('deleteBtn')?.addEventListener('click', () => this.destroy(r.repair_id, '/repairs'));
    },

    /** Visual status pipeline — makes the workflow obvious in a demo. */
    _timeline(current) {
        const flow = ['in_progress', 'completed', 'ready_for_pickup', 'collected'];
        if (['cancelled', 'on_hold', 'waiting_for_parts'].includes(current)) {
            return `<div class="timeline-alt">${Badge.repair(current)}
                <p class="text-muted small">This ${Utils.e(L.jobLower)} is off the main pipeline.</p></div>`;
        }
        const at = flow.indexOf(current);
        return `<ol class="timeline">${flow.map((s, i) => `
            <li class="timeline-step ${i < at ? 'done' : i === at ? 'current' : ''}">
                <span class="timeline-dot">${i < at ? Icon.check('') : ''}</span>
                <span class="timeline-label">${Utils.e(REPAIR_STATUS[s])}</span>
            </li>`).join('')}</ol>`;
    },

    /* ── Create / Edit ────────────────────────────────────────────────────── */

    create({ query }) {
        if (!Auth.requireAuth()) return;
        this._form(null, query);
    },

    edit({ params }) {
        if (!Auth.requireAuth()) return;
        const r = Repair.findById(params.id);
        if (!r) return AuthView.notFound(`/repairs/${params.id}`);
        this._form(r);
    },

    _form(r = null, query = {}) {
        const isEdit = !!r;
        const techs  = Staff.technicians();
        const preset = !isEdit && query.customer_id ? Customer.findById(query.customer_id) : null;
        const v      = f => Utils.e(r?.[f] ?? '');

        Layout.render(`
            ${UI.backLink(isEdit ? `#/repairs/${r.repair_id}` : '#/repairs',
                isEdit ? `Back to ${L.jobLower}` : `All ${L.jobMany.toLowerCase()}`)}
            ${UI.pageHeader(isEdit ? `Edit ${L.jobLower} #${r.repair_id}` : L.jobNew,
                isEdit ? `Update the ${L.jobLower} details.` : L.jobIntake)}

            <form id="repairForm" class="card" novalidate>
                <div class="card-body">
                    <div class="form-grid-2">

                        <div class="form-group form-col-full">
                            <label class="form-label required" for="customer_search">${Utils.e(L.clientOne)}</label>
                            <div class="search-input-wrap">
                                <input class="form-input" id="customer_search" autocomplete="off"
                                       placeholder="Type a name, phone or email…">
                                <input type="hidden" id="customer_id" name="customer_id" value="${Utils.e(r?.customer_id ?? preset?.customer_id ?? '')}">
                                <div class="ac-dropdown" id="customer_ac" hidden></div>
                            </div>
                            <span class="form-hint">Not on file? <a href="#/customers/create">Add a ${Utils.e(L.clientOne.toLowerCase())} first</a>.</span>
                            <span class="field-error" id="err-customer_id"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label required" for="device_model">${Utils.e(L.itemField)}</label>
                            <input class="form-input" id="device_model" name="device_model" value="${v('device_model')}"
                                   placeholder="${Utils.e(L.itemPlaceholder)}" required>
                            <span class="field-error" id="err-device_model"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="device_serial_number">${Utils.e(L.serialLabel)}</label>
                            <input class="form-input" id="device_serial_number" name="device_serial_number" value="${v('device_serial_number')}">
                        </div>

                        <div class="form-group form-col-full">
                            <label class="form-label required" for="problem_description">${Utils.e(L.problemLabel)}</label>
                            <textarea class="form-textarea" id="problem_description" name="problem_description" rows="3"
                                      placeholder="${Utils.e(L.problemHint)}" required>${Utils.e(r?.problem_description ?? '')}</textarea>
                            <span class="field-error" id="err-problem_description"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="staff_id">Assign ${Utils.e(L.staffOne.toLowerCase())}</label>
                            <select class="form-select" id="staff_id" name="staff_id">
                                <option value="">Unassigned</option>
                                ${techs.map(t => `<option value="${t.staff_id}" ${Utils.intVal(r?.staff_id) === t.staff_id ? 'selected' : ''}>
                                    ${Utils.e(t.full_name)}${t.specialization ? ' — ' + Utils.e(t.specialization) : ''}</option>`).join('')}
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select class="form-select" id="status" name="status" ${isEdit ? '' : 'disabled'}>
                                ${Object.entries(REPAIR_STATUS).map(([k, lbl]) =>
                                    `<option value="${k}" ${(r?.status ?? 'in_progress') === k ? 'selected' : ''}>${Utils.e(lbl)}</option>`).join('')}
                            </select>
                            ${isEdit ? '' : `<span class="form-hint">New ${Utils.e(L.jobMany.toLowerCase())} always start as "${Utils.e(REPAIR_STATUS.in_progress)}".</span>`}
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="estimate_amount">${Utils.e(L.estimateLabel)} (${CURRENCY_SYMBOL})</label>
                            <input class="form-input" id="estimate_amount" name="estimate_amount" type="number" min="0" step="1"
                                   value="${v('estimate_amount')}">
                            <span class="field-error" id="err-estimate_amount"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="actual_amount">Final amount (${CURRENCY_SYMBOL})</label>
                            <input class="form-input" id="actual_amount" name="actual_amount" type="number" min="0" step="1"
                                   value="${v('actual_amount')}">
                            <span class="field-error" id="err-actual_amount"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="collection_date">Expected pickup</label>
                            <input class="form-input" id="collection_date" name="collection_date" type="date"
                                   value="${Utils.e(r?.collection_date ?? '')}">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="date_in">Received on</label>
                            <input class="form-input" id="date_in" name="date_in" type="date"
                                   value="${Utils.e(Utils.toDbDate(r?.date_in ?? new Date()))}">
                        </div>

                        <div class="form-group form-col-full">
                            <label class="form-label" for="diagnosis">${Utils.e(L.diagnosisLabel)}</label>
                            <textarea class="form-textarea" id="diagnosis" name="diagnosis" rows="2">${Utils.e(r?.diagnosis ?? '')}</textarea>
                        </div>

                        <div class="form-group form-col-full">
                            <label class="form-label" for="work_done">${Utils.e(L.workLabel)}</label>
                            <textarea class="form-textarea" id="work_done" name="work_done" rows="2">${Utils.e(r?.work_done ?? '')}</textarea>
                        </div>

                        <div class="form-group form-col-full">
                            <label class="form-label" for="notes">Internal notes</label>
                            <textarea class="form-textarea" id="notes" name="notes" rows="2">${Utils.e(r?.notes ?? '')}</textarea>
                        </div>

                    </div>
                </div>

                <div class="form-actions">
                    <a href="${isEdit ? `#/repairs/${r.repair_id}` : '#/repairs'}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">${isEdit ? 'Save changes' : `Create ${Utils.e(L.jobLower)}`}</button>
                </div>
            </form>
        `);

        Forms.customerAutocomplete({
            input: 'customer_search', hidden: 'customer_id', dropdown: 'customer_ac',
            preset: preset ?? (isEdit ? { full_name: r.customer_name, customer_id: r.customer_id } : null),
        });

        document.getElementById('repairForm').onsubmit = e => {
            e.preventDefault();
            const data = Forms.collect(e.target);
            data.staff_id = data.staff_id ? Utils.intVal(data.staff_id) : null;
            data.customer_id = Utils.intVal(data.customer_id);
            data.estimate_amount = data.estimate_amount ? Utils.floatVal(data.estimate_amount) : null;
            data.actual_amount   = data.actual_amount   ? Utils.floatVal(data.actual_amount)   : null;
            if (!isEdit) data.status = 'in_progress';

            const { ok, errors } = Repair.validate(data);
            Forms.clearErrors(e.target);
            if (!ok) { Forms.showErrors(errors); Toast.error('Please fix the highlighted fields.'); return; }

            if (isEdit) {
                Repair.update(r.repair_id, data);
                Toast.success(`${L.jobOne} updated.`);
                Router.go(`/repairs/${r.repair_id}`);
            } else {
                const id = Repair.create(data);
                Toast.success(`${L.jobOne} #${id} created.`);
                Router.go(`/repairs/${id}`);
            }
        };
    },

    /* ── QR lookup (replaces GET /api/repairs/qr) ─────────────────────────── */

    qrLookup() {
        Modal.open({
            title: `QR / ${L.jobLower} lookup`,
            body: `
                <p class="confirm-text">Scan or type the code printed on the ${Utils.e(L.itemLabel.toLowerCase())} tag.</p>
                <input class="form-input" id="qrInput" placeholder="RMS-000042 or just 42" autocomplete="off">
                <div id="qrResult" class="qr-result"></div>`,
            footer: '<button class="btn btn-secondary" onclick="Modal.close()">Close</button>',
        });

        const input = document.getElementById('qrInput');
        const out   = document.getElementById('qrResult');

        const run = () => {
            const raw = input.value.trim();
            if (!raw) { out.innerHTML = ''; return; }
            const r = Repair.findByQRCode(raw.toUpperCase().startsWith('RMS-') ? raw : Repair.generateQRCode(Utils.intVal(raw)));
            out.innerHTML = r
                ? `<a class="qr-hit" href="#/repairs/${r.repair_id}" onclick="Modal.close()">
                     <strong>#${r.repair_id} — ${Utils.e(r.device_model)}</strong>
                     <span>${Utils.e(r.customer_name)} · ${Utils.e(REPAIR_STATUS[r.status])}</span>
                   </a>`
                : `<p class="text-muted small">No ${Utils.e(L.jobLower)} matches that code.</p>`;
        };

        input.oninput = UI.debounce(run, 150);
        input.onkeydown = e => { if (e.key === 'Enter') { e.preventDefault(); run(); } };
    },

    /* ── Delete ───────────────────────────────────────────────────────────── */

    async destroy(id, redirectTo = null) {
        const check = Repair.canDelete(id);
        if (!check.ok) {
            Modal.open({
                title: `Cannot delete this ${L.jobLower}`,
                body: `<p class="confirm-text">${Utils.e(check.reason)}</p>`,
                footer: '<button class="btn btn-secondary" onclick="Modal.close()">Close</button>',
            });
            return;
        }
        const ok = await Modal.confirm({
            title: `Delete ${L.jobLower}`,
            message: `Delete ${L.jobLower} #${id}? This cannot be undone.`,
        });
        if (!ok) return;
        Repair.delete(id);
        Toast.success(`${L.jobOne} deleted.`);
        redirectTo ? Router.go(redirectTo) : Router.reload();
    },

    /* ── Print job sheet ──────────────────────────────────────────────────── */

    print({ params }) {
        if (!Auth.requireAuth()) return;
        const r = Repair.findById(params.id);
        if (!r) return AuthView.notFound(`/repairs/${params.id}/print`);

        Layout.unmount();
        document.getElementById('viewRoot').innerHTML = Print.jobSheet(r);
        Print.bind(`#/repairs/${r.repair_id}`);
    },
};
