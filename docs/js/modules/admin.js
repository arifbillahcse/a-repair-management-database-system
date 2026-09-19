/**
 * modules/admin.js — port of AdminController + views/admin/*.php
 * Settings, user management, and a demo-flavoured System Info page.
 */
'use strict';

const Admin = {

    /* ── GET/POST /admin/settings ─────────────────────────────────────────── */

    settings() {
        if (!Auth.requireRole('admin')) return;

        const f = (key, fallback = '') => Utils.e(DB.setting(key, fallback));

        Layout.render(`
            ${UI.pageHeader('Settings', 'Company details used on invoices and job sheets.')}

            <form id="settingsForm" class="card" novalidate>
                <div class="card-body">
                    <h3 class="form-section-title">Company</h3>
                    <div class="form-grid-2">
                        <div class="form-group form-col-full">
                            <label class="form-label" for="company_name">Company name</label>
                            <input class="form-input" id="company_name" name="company_name" value="${f('company_name')}">
                        </div>
                        <div class="form-group form-col-full">
                            <label class="form-label" for="company_address">Address</label>
                            <input class="form-input" id="company_address" name="company_address" value="${f('company_address')}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="company_phone">Phone</label>
                            <input class="form-input" id="company_phone" name="company_phone" value="${f('company_phone')}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="company_email">Email</label>
                            <input class="form-input" id="company_email" name="company_email" type="email" value="${f('company_email')}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="company_vat">BIN / VAT number</label>
                            <input class="form-input" id="company_vat" name="company_vat" value="${f('company_vat')}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="company_website">Website</label>
                            <input class="form-input" id="company_website" name="company_website" value="${f('company_website')}">
                        </div>
                    </div>

                    <h3 class="form-section-title">Billing</h3>
                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label" for="tax_percentage">Default VAT %</label>
                            <input class="form-input" id="tax_percentage" name="tax_percentage" type="number"
                                   min="0" max="100" step="0.5" value="${f('tax_percentage', DEFAULT_TAX_PCT)}">
                            <span class="form-hint">Bangladesh standard rate is 15%.</span>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="invoice_prefix">Invoice prefix</label>
                            <input class="form-input" id="invoice_prefix" name="invoice_prefix" value="${f('invoice_prefix', 'INV')}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="pickup_reminder_days">Pickup reminder after (days)</label>
                            <input class="form-input" id="pickup_reminder_days" name="pickup_reminder_days" type="number"
                                   min="1" max="90" value="${f('pickup_reminder_days', '7')}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="currency">Currency</label>
                            <input class="form-input" id="currency" name="currency" value="${f('currency', CURRENCY_CODE)}" readonly>
                            <span class="form-hint">Fixed to ${CURRENCY_CODE} (${CURRENCY_SYMBOL}) in this demo.</span>
                        </div>
                        <div class="form-group form-col-full">
                            <label class="form-label" for="invoice_terms">Invoice terms</label>
                            <textarea class="form-textarea" id="invoice_terms" name="invoice_terms" rows="2">${f('invoice_terms')}</textarea>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="#/" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save settings</button>
                </div>
            </form>
        `);

        document.getElementById('settingsForm').onsubmit = e => {
            e.preventDefault();
            Object.entries(Forms.collect(e.target)).forEach(([k, v]) => DB.setSetting(k, v));
            DB.log('updated', 'settings', null, 'Company settings updated');
            Toast.success('Settings saved.');
            Layout.mounted = false;          // company name shows in the topbar + footer
            Layout.mount();
            Router.reload();
        };
    },

    /* ── GET /admin/users ─────────────────────────────────────────────────── */

    users() {
        if (!Auth.requireRole('admin')) return;

        Layout.render(`
            ${UI.pageHeader('User accounts',
                'Login accounts for the system. In the demo any password is accepted.')}
            <div class="card"><div id="listMount"></div></div>
        `);

        DataTable.render({
            mount: '#listMount',
            rows: User.getAll(),
            columns: [
                { key: 'full_name', label: 'User', render: u => `
                    <div class="staff-cell">
                        <div class="user-avatar-sm">${Utils.e(Utils.initials(u.full_name))}</div>
                        <div>
                            <span class="cust-name-link">${Utils.e(u.full_name)}</span>
                            <span class="vat-sub">@${Utils.e(u.username)}</span>
                        </div>
                    </div>` },
                { key: 'email', label: 'Email', hideOnTablet: true, render: u => Utils.e(u.email ?? '—') },
                { key: 'role',  label: 'Role',  render: u => Badge.role(u.role) },
                { key: 'last_login', label: 'Last login', hideOnTablet: true,
                  render: u => u.last_login ? Utils.timeAgo(u.last_login) : '<span class="text-muted">Never</span>' },
                { key: 'status', label: 'Status', render: u => Badge.active(u.status) },
            ],
            actions: u => `
                <button class="act-btn ${u.status === 'active' ? 'act-btn-d' : 'act-btn-g'}"
                        data-toggle="${u.user_id}" title="${u.status === 'active' ? 'Disable' : 'Enable'}">
                    ${u.status === 'active' ? Icon.logout('') : Icon.check('')}
                </button>
                <button class="act-btn" data-reset="${u.user_id}" title="Reset password">${Icon.refresh('')}</button>`,
            empty: { message: 'No user accounts.', icon: 'user' },
        });

        document.querySelectorAll('[data-toggle]').forEach(b => {
            b.onclick = () => {
                const res = User.toggleStatus(b.dataset.toggle);
                if (!res.ok) return Toast.error(res.error);
                Toast.success(`Account ${res.status === 'active' ? 'enabled' : 'disabled'}.`);
                Router.reload();
            };
        });

        document.querySelectorAll('[data-reset]').forEach(b => {
            b.onclick = async () => {
                const u  = User.findById(b.dataset.reset);
                const ok = await Modal.confirm({
                    title: 'Reset password',
                    message: `Send a password reset for "${u?.username}"? In the demo this only records the action.`,
                    confirmLabel: 'Reset',
                    danger: false,
                });
                if (!ok) return;
                User.resetPassword(b.dataset.reset);
                Toast.success('Password reset recorded.');
            };
        });
    },

    /* ── GET /admin/sysinfo ───────────────────────────────────────────────── */

    sysinfo() {
        if (!Auth.requireRole('admin')) return;

        const usage = DB.usage();
        const tables = Object.keys(DB.TABLES).map(t => ({ table: t, rows: DB.table(t).length }));

        Layout.render(`
            ${UI.pageHeader('System info', 'What is running behind this demo.')}

            <div class="dashboard-grid">
                <div class="dashboard-main">

                    <div class="card">
                        <div class="card-header"><h2 class="card-title">Demo storage</h2></div>
                        <div id="tablesMount"></div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h2 class="card-title">Recent activity log</h2></div>
                        <div id="logMount"></div>
                    </div>

                </div>

                <aside class="dashboard-aside">
                    <div class="card">
                        <div class="card-header"><h2 class="card-title">Environment</h2></div>
                        <div class="card-body">
                            ${UI.field('Application', APP_NAME)}
                            ${UI.field('Version', `v${APP_VERSION} (static demo)`)}
                            ${UI.field('Backend', 'None — runs entirely in the browser')}
                            ${UI.field('Storage', 'localStorage + sessionStorage')}
                            ${UI.field('Storage used', Utils.formatFileSize(usage))}
                            ${UI.field('Seed version', String(DEMO_SEED_VERSION))}
                            ${UI.field('Currency', `${CURRENCY_CODE} (${CURRENCY_SYMBOL})`)}
                            ${UI.field('Default VAT', `${DB.setting('tax_percentage', DEFAULT_TAX_PCT)}%`)}
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h2 class="card-title">The real system</h2></div>
                        <div class="card-body">
                            <p class="text-muted small">
                                The production build of this software runs on PHP 8.1 with a MySQL
                                database — 8 tables, session auth, file uploads and server-side
                                CSV import. This page is a front-end prototype of that system.
                            </p>
                            <a class="btn btn-secondary btn-full"
                               href="https://github.com/arifbillahcse/a-repair-management-database-system"
                               target="_blank" rel="noopener">View the PHP source</a>
                        </div>
                    </div>

                    <div class="card card-danger">
                        <div class="card-header"><h2 class="card-title">Reset</h2></div>
                        <div class="card-body">
                            <p class="text-muted small">Restore the original demo dataset and discard every change.</p>
                            <button class="btn btn-danger btn-full" id="resetBtn">${Icon.refresh('')} Reset demo data</button>
                        </div>
                    </div>
                </aside>
            </div>
        `);

        DataTable.render({
            mount: '#tablesMount',
            rows: tables,
            columns: [
                { key: 'table', label: 'Table', render: t => `<code>${Utils.e(t.table)}</code>` },
                { key: 'rows',  label: 'Rows', align: 'right', render: t => Utils.numberFormat(t.rows) },
            ],
            empty: { message: 'No tables.', icon: 'box' },
        });

        DataTable.render({
            mount: '#logMount',
            rows: DB.recentActivity(20),
            columns: [
                { key: 'description', label: 'Action', render: a => Utils.e(a.description) },
                { key: 'user_name', label: 'By', hideOnTablet: true, render: a => Utils.e(a.user_name ?? 'System') },
                { key: 'created_at', label: 'When', align: 'right', render: a => Utils.timeAgo(a.created_at) },
            ],
            empty: { message: 'Nothing logged yet in this session.', icon: 'clock' },
        });

        document.getElementById('resetBtn').onclick = async () => {
            const ok = await Modal.confirm({
                title: 'Reset demo data',
                message: 'This clears everything you have added or changed. Continue?',
                confirmLabel: 'Reset',
            });
            if (!ok) return;
            await DB.reset();
            Toast.success('Demo data restored.');
            location.hash = '#/login';
            location.reload();
        };
    },
};
