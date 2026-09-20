/**
 * app.js — boot + route table.
 *
 * The route list is a direct translation of public/index.php: same paths,
 * same order, same role gates. Only the transport changed (hash, not HTTP).
 */
'use strict';

const App = {

    async boot() {
        const root = document.getElementById('appRoot');

        try {
            await DB.seed();
        } catch (err) {
            console.error('[App] seeding failed', err);
            root.innerHTML = `
                <div class="boot-error">
                    <h1>Could not load the demo data</h1>
                    <p>${Utils.e(err.message)}</p>
                    <p class="text-muted small">
                        If you opened this file directly from disk, run a local server instead —
                        <code>python3 -m http.server</code> in this folder — because browsers block
                        <code>fetch()</code> on <code>file://</code> URLs.
                    </p>
                </div>`;
            return;
        }

        Theme.init();
        this.routes();
        Router.start();
    },

    routes() {
        /* ── Auth ─────────────────────────────────────────────────────────── */
        Router.add('/login',  () => AuthView.login());
        Router.add('/logout', () => AuthView.logout());
        Router.add('/403',    () => AuthView.forbidden());

        /* ── Dashboard ────────────────────────────────────────────────────── */
        Router.add('/', ctx => Dashboard.index(ctx));

        /* ── Repairs ──────────────────────────────────────────────────────── */
        Router.add('/repairs',            ctx => Repairs.index(ctx));
        Router.add('/repairs/create',     ctx => Repairs.create(ctx));
        Router.add('/repairs/:id',        ctx => Repairs.show(ctx));
        Router.add('/repairs/:id/edit',   ctx => Repairs.edit(ctx));
        Router.add('/repairs/:id/print',  ctx => Repairs.print(ctx));
        Router.add('/repairs/:id/invoice',ctx => Invoices.fromRepair(ctx));

        /* ── Clients ──────────────────────────────────────────────────────── */
        Router.add('/customers',          ctx => Customers.index(ctx));
        Router.add('/customers/create',   ctx => Customers.create(ctx));
        Router.add('/customers/:id',      ctx => Customers.show(ctx));
        Router.add('/customers/:id/edit', ctx => Customers.edit(ctx));

        /* ── Counter sales ────────────────────────────────────────────────── */
        Router.add('/sales',     ctx => Sales.index(ctx));
        Router.add('/sales/new', ctx => Sales.create(ctx));

        /* ── Catalogue ────────────────────────────────────────────────────── */
        Router.add('/products',          ctx => Products.index(ctx));
        Router.add('/products/create',   ctx => Products.create(ctx));
        Router.add('/products/:id/edit', ctx => Products.edit(ctx));

        /* ── Invoices ─────────────────────────────────────────────────────── */
        Router.add('/invoices',           ctx => Invoices.index(ctx));
        Router.add('/invoices/create',    ctx => Invoices.create(ctx));
        Router.add('/invoices/:id',       ctx => Invoices.show(ctx));
        Router.add('/invoices/:id/print', ctx => Invoices.print(ctx));

        /* ── Reports (manager+) ───────────────────────────────────────────── */
        Router.add('/reports', ctx => Reports.index(ctx));

        /* ── Staff (manager+) ─────────────────────────────────────────────── */
        Router.add('/staff',            ctx => StaffView.index(ctx));
        Router.add('/staff/create',     ctx => StaffView.create(ctx));
        Router.add('/staff/:id',        ctx => StaffView.show(ctx));
        Router.add('/staff/:id/edit',   ctx => StaffView.edit(ctx));

        /* ── Admin ────────────────────────────────────────────────────────── */
        Router.add('/import',          ctx => Imports.index(ctx));
        Router.add('/admin/settings',  ctx => Admin.settings(ctx));
        Router.add('/admin/users',     ctx => Admin.users(ctx));
        Router.add('/admin/sysinfo',   ctx => Admin.sysinfo(ctx));

        Router.setNotFound(ctx => {
            if (!Auth.check()) return Router.go('/login');
            AuthView.notFound(ctx.path);
        });
    },
};

/**
 * Theme toggle. style.css only ever defined light values, so dark mode is a
 * deliberate second set of tokens rather than an automatic inversion —
 * including the chart mark colour, which is re-validated for the dark surface.
 */
const Theme = {

    KEY: DEMO_PREFIX + 'theme',

    init() {
        this.apply(this.stored() ?? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'));

        // Follow the OS only while the visitor has not chosen for themselves.
        matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (!this.stored()) this.apply(e.matches ? 'dark' : 'light');
        });
    },

    stored() {
        try { return localStorage.getItem(this.KEY); } catch { return null; }
    },

    current() { return document.documentElement.dataset.theme ?? 'light'; },

    apply(mode) {
        document.documentElement.dataset.theme = mode;
        document.getElementById('themeToggle')
            ?.setAttribute('aria-label', mode === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
    },

    toggle() {
        const next = this.current() === 'dark' ? 'light' : 'dark';
        this.apply(next);
        try { localStorage.setItem(this.KEY, next); } catch { /* private mode */ }
        if (typeof Charts !== 'undefined') Charts.refreshAll();
    },
};

document.addEventListener('DOMContentLoaded', () => App.boot());
