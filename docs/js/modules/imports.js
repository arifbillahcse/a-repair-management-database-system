/**
 * modules/imports.js — port of ImportController + views/imports/*.php
 *
 * The PHP version parsed an uploaded CSV server-side. Here FileReader +
 * Utils.parseCsv() do the same work in the browser, which means the demo
 * genuinely imports a real file the visitor drops on it.
 */
'use strict';

const Imports = {

    TYPES: {
        customers: {
            label: L.clientMany,
            columns: ['full_name', 'client_type', 'phone_mobile', 'email', 'address', 'city', 'postal_code', 'vat_number', 'notes'],
            required: ['full_name'],
            sample: [
                ['Rahim Trading House', 'company',    '01712345678', 'info@rahimtrading.com.bd', 'House 12, Road 4, Banani', 'Dhaka',       '1213', '123456789', 'Corporate client'],
                ['Tasnim Akter',        'individual', '01898765432', 'tasnim.a@gmail.com',       'House 7, Zindabazar',      'Sylhet',      '3100', '',          ''],
                ['Gadget Care BD',      'colleague',  '01911223344', 'hello@gadgetcarebd.com',   'Shop 22, Agrabade',        'Chattogram',  '4100', '',          'Trade partner'],
            ],
        },
        repairs: {
            label: L.jobMany,
            columns: ['customer_phone', 'device_model', 'device_serial_number', 'problem_description', 'estimate_amount', 'status'],
            required: ['customer_phone', 'device_model'],
            sample: [
                ['01712345678', 'Dell Latitude 5420', 'DEL482913BD', 'Not powering on, no display',   '4500',  'in_progress'],
                ['01898765432', 'iPhone 12',          'APL771230BD', 'Screen cracked after drop',     '12000', 'waiting_for_parts'],
            ],
        },
    },

    index({ query }) {
        if (!Auth.requireRole('admin')) return;

        const type = this.TYPES[query.type] ? query.type : 'customers';
        const cfg  = this.TYPES[type];

        Layout.render(`
            ${UI.pageHeader('Import data',
                `Bulk-load ${L.clientMany.toLowerCase()} or ${L.jobMany.toLowerCase()} from a CSV file. Everything is parsed in your browser.`)}

            <div class="card">
                <div class="card-header"><h2 class="card-title">1. Choose what to import</h2></div>
                <div class="card-body">
                    <div class="import-types">
                        ${Object.entries(this.TYPES).map(([k, t]) => `
                            <button class="sf-btn ${k === type ? 'active' : ''}" data-type="${k}">${Utils.e(t.label)}</button>`).join('')}
                    </div>

                    <h3 class="form-section-title">Expected columns</h3>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead><tr><th>Column</th><th>Required</th></tr></thead>
                            <tbody>
                                ${cfg.columns.map(c => `
                                    <tr>
                                        <td><code>${Utils.e(c)}</code></td>
                                        <td>${cfg.required.includes(c)
                                            ? '<span class="badge badge-red">Required</span>'
                                            : '<span class="badge badge-gray">Optional</span>'}</td>
                                    </tr>`).join('')}
                            </tbody>
                        </table>
                    </div>

                    <button class="btn btn-secondary" id="templateBtn">${Icon.download('')} Download CSV template</button>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">2. Upload your file</h2></div>
                <div class="card-body">
                    <label class="dropzone" id="dropzone">
                        ${Icon.upload('dz-icon')}
                        <span class="dz-title">Drop a CSV here, or click to browse</span>
                        <span class="dz-hint">First row must be the header. Max 2 MB.</span>
                        <input type="file" id="fileInput" accept=".csv,text/csv" hidden>
                    </label>
                    <div id="previewMount"></div>
                </div>
            </div>
        `);

        document.querySelectorAll('[data-type]').forEach(b => {
            b.onclick = () => Router.setQuery({ type: b.dataset.type });
        });
        document.getElementById('templateBtn').onclick = () => this.downloadTemplate(type);

        const dz    = document.getElementById('dropzone');
        const input = document.getElementById('fileInput');

        dz.onclick = () => input.click();
        input.onchange = e => e.target.files[0] && this.handleFile(e.target.files[0], type);

        ['dragenter', 'dragover'].forEach(ev => dz.addEventListener(ev, e => {
            e.preventDefault(); dz.classList.add('dz-over');
        }));
        ['dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, e => {
            e.preventDefault(); dz.classList.remove('dz-over');
        }));
        dz.addEventListener('drop', e => {
            const file = e.dataTransfer.files[0];
            if (file) this.handleFile(file, type);
        });
    },

    downloadTemplate(type) {
        const cfg = this.TYPES[type];
        const csv = [cfg.columns.join(','), ...cfg.sample.map(r =>
            r.map(v => /[",]/.test(v) ? `"${v.replace(/"/g, '""')}"` : v).join(','))].join('\n');
        Utils.download(`${type}-import-template.csv`, csv);
        Toast.success('Template downloaded.');
    },

    handleFile(file, type) {
        if (file.size > 2 * 1024 * 1024) return Toast.error('File is too large (2 MB max).');
        if (!/\.csv$/i.test(file.name))  return Toast.error('Please choose a .csv file.');

        const reader = new FileReader();
        reader.onload = () => {
            const rows = Utils.parseCsv(reader.result);
            if (rows.length < 2) return Toast.error('That file has no data rows.');
            this.preview(rows, type, file.name);
        };
        reader.onerror = () => Toast.error('Could not read that file.');
        reader.readAsText(file);
    },

    /** Validate every row, show what would happen, then let the user commit. */
    preview(rows, type, filename) {
        const cfg    = this.TYPES[type];
        const header = rows[0].map(h => h.trim().toLowerCase().replace(/\s+/g, '_'));
        const body   = rows.slice(1);

        const missing = cfg.required.filter(c => !header.includes(c));
        if (missing.length) {
            document.getElementById('previewMount').innerHTML = `
                <div class="alert alert-error">
                    <strong>Missing required column${missing.length > 1 ? 's' : ''}:</strong>
                    ${missing.map(m => `<code>${Utils.e(m)}</code>`).join(', ')}.
                    Download the template above to see the expected layout.
                </div>`;
            return;
        }

        const parsed = body.map((cells, i) => {
            const row = {};
            header.forEach((h, j) => { if (cfg.columns.includes(h)) row[h] = (cells[j] ?? '').trim(); });

            let error = null;
            if (type === 'customers') {
                const check = Customer.validate(row);
                if (!check.ok) error = Object.values(check.errors)[0];
            } else {
                if (!row.customer_phone) error = `${L.clientOne} phone is required.`;
                else if (!Customer.findByPhone(row.customer_phone)) error = `No ${L.clientOne.toLowerCase()} with that phone number.`;
                else if (!row.device_model) error = `${L.itemField} is required.`;
            }
            return { line: i + 2, row, error };
        });

        const valid   = parsed.filter(p => !p.error);
        const invalid = parsed.filter(p => p.error);

        document.getElementById('previewMount').innerHTML = `
            <h3 class="form-section-title">3. Review — ${Utils.e(filename)}</h3>

            <div class="stats-grid compact">
                ${UI.statCard({ label: 'Rows found', value: Utils.numberFormat(parsed.length), icon: 'box',   tone: 'accent' })}
                ${UI.statCard({ label: 'Ready to import', value: Utils.numberFormat(valid.length), icon: 'check', tone: 'green' })}
                ${UI.statCard({ label: 'Will be skipped', value: Utils.numberFormat(invalid.length), icon: 'alert', tone: 'orange' })}
            </div>

            ${invalid.length ? `
                <div class="alert alert-error">
                    <strong>${invalid.length} row${invalid.length > 1 ? 's' : ''} cannot be imported.</strong>
                    They are listed below and will be skipped.
                </div>` : ''}

            <div class="table-responsive import-preview">
                <table class="data-table">
                    <thead>
                        <tr><th>Line</th>${cfg.columns.map(c => `<th>${Utils.e(c)}</th>`).join('')}<th>Result</th></tr>
                    </thead>
                    <tbody>
                        ${parsed.slice(0, 25).map(p => `
                            <tr class="${p.error ? 'row-error' : ''}">
                                <td>${p.line}</td>
                                ${cfg.columns.map(c => `<td>${Utils.e(Utils.truncate(p.row[c] ?? '', 22))}</td>`).join('')}
                                <td>${p.error
                                    ? `<span class="badge badge-red" title="${Utils.e(p.error)}">${Utils.e(Utils.truncate(p.error, 28))}</span>`
                                    : '<span class="badge badge-green">OK</span>'}</td>
                            </tr>`).join('')}
                    </tbody>
                </table>
                ${parsed.length > 25 ? `<p class="text-muted small ta-center">…and ${parsed.length - 25} more rows.</p>` : ''}
            </div>

            <div class="form-actions">
                <button class="btn btn-secondary" id="cancelImport">Cancel</button>
                <button class="btn btn-primary" id="commitImport" ${valid.length ? '' : 'disabled'}>
                    Import ${valid.length} row${valid.length === 1 ? '' : 's'}
                </button>
            </div>`;

        document.getElementById('cancelImport').onclick = () => {
            document.getElementById('previewMount').innerHTML = '';
        };

        document.getElementById('commitImport').onclick = async () => {
            const ok = await Modal.confirm({
                title: 'Run import',
                message: `Import ${valid.length} ${cfg.label.toLowerCase()} into the demo? Use "Reset demo data" later to undo it.`,
                confirmLabel: 'Import',
                danger: false,
            });
            if (!ok) return;
            this.commit(valid, type);
        };
    },

    commit(valid, type) {
        let created = 0;

        valid.forEach(({ row }) => {
            if (type === 'customers') {
                Customer.create({
                    full_name:    row.full_name,
                    client_type:  CLIENT_TYPES[row.client_type] ? row.client_type : 'individual',
                    phone_mobile: row.phone_mobile || null,
                    email:        row.email || null,
                    address:      row.address || null,
                    city:         row.city || null,
                    postal_code:  row.postal_code || null,
                    vat_number:   row.vat_number || null,
                    notes:        row.notes || null,
                    status:       'active',
                });
            } else {
                const c = Customer.findByPhone(row.customer_phone);
                Repair.create({
                    customer_id:          c.customer_id,
                    device_model:         row.device_model,
                    device_serial_number: row.device_serial_number || null,
                    problem_description:  row.problem_description || 'Imported — no description supplied.',
                    estimate_amount:      row.estimate_amount ? Utils.floatVal(row.estimate_amount) : null,
                    status:               REPAIR_STATUS[row.status] ? row.status : 'in_progress',
                    staff_id:             null,
                });
            }
            created++;
        });

        DB.log('created', type, null, `${created} ${type} imported from CSV`);
        Toast.success(`${created} ${this.TYPES[type].label.toLowerCase()} imported.`);
        Router.go(type === 'customers' ? '/customers' : '/repairs');
    },
};
