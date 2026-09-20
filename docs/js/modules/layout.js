/**
 * modules/layout.js — port of views/layouts/{header,sidebar,footer}.php
 *
 * The shell (topbar + sidebar + footer) is rendered once and stays put;
 * only #viewRoot is replaced on navigation. That is what makes the SPA feel
 * instant compared to the PHP app's full page reloads.
 */
'use strict';

const Layout = {

    mounted: false,

    /* ── Nav definition (mirrors sidebar.php, including the role gates) ───── */

    navItems() {
        return [
            { label: 'Dashboard', icon: 'dashboard', href: '#/', match: /^\/$/ },
            {
                label: L.jobMany, icon: 'wrench', match: /^\/repairs/,
                children: [
                    { label: `+ ${L.jobNew}`,    href: '#/repairs/create', match: /^\/repairs\/create$/ },
                    { label: `All ${L.jobMany}`, href: '#/repairs',        match: /^\/repairs$/ },
                ],
            },
            {
                label: L.clientMany, icon: 'users', match: /^\/customers/,
                children: [
                    { label: `+ New ${L.clientOne}`, href: '#/customers/create', match: /^\/customers\/create$/ },
                    { label: `All ${L.clientMany}`,  href: '#/customers',        match: /^\/customers$/ },
                ],
            },
            { label: L.saleMany,      icon: 'money', href: '#/sales',    match: /^\/sales/ },
            { label: L.catalogueMany, icon: 'box',   href: '#/products', match: /^\/products/ },
            { label: 'Invoices', icon: 'invoice', href: '#/invoices', match: /^\/invoices/ },
            { label: 'Reports',  icon: 'chart',   href: '#/reports',  match: /^\/reports/,  role: 'manager' },
            { label: 'Staff',    icon: 'user',    href: '#/staff',    match: /^\/staff/,    role: 'manager' },
            { divider: true, role: 'admin' },
            { label: 'Import Data', icon: 'upload', href: '#/import',         match: /^\/import/, role: 'admin' },
            { label: 'Settings',    icon: 'cog',    href: '#/admin/settings', match: /^\/admin/,  role: 'admin' },
        ];
    },

    /* ── Render ───────────────────────────────────────────────────────────── */

    mount() {
        if (this.mounted) return;
        const user = Auth.user();
        document.getElementById('appRoot').innerHTML = `
            ${this.demoBanner()}
            ${this.topbar(user)}
            <div class="layout-wrapper">
                ${this.sidebar()}
                <main class="main-content" id="viewRoot" role="main"></main>
            </div>
            ${this.footer()}
            <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>`;
        this.bind();
        this.mounted = true;
    },

    /** Removes the shell — used by the login screen and the print views. */
    unmount() {
        document.getElementById('appRoot').innerHTML = '<main id="viewRoot"></main>';
        this.mounted = false;
    },

    demoBanner() {
        return `
            <div class="demo-banner" id="demoBanner">
                <span class="demo-dot"></span>
                <strong>Demo mode</strong>
                <span class="demo-text">— everything you change is saved in this browser only. No server, no database.</span>
                <button class="demo-reset" id="demoReset">${Icon.refresh('')} Reset demo data</button>
            </div>`;
    },

    topbar(user) {
        const roleSwitcher = Object.entries(USER_ROLES).map(([key, label]) =>
            `<button role="menuitem" data-role="${key}" class="${user?.role === key ? 'is-current' : ''}">
                ${Icon.user('')}${Utils.e(label)}${user?.role === key ? Icon.check('tick') : ''}
             </button>`).join('');

        return `
            <header class="topbar" role="banner">
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar" aria-expanded="false">
                    <span class="hamburger-line"></span><span class="hamburger-line"></span><span class="hamburger-line"></span>
                </button>

                <a href="#/" class="topbar-brand">
                    ${Icon.brand('brand-icon')}
                    <span class="brand-name">${Utils.e(DB.setting('company_name', APP_NAME))}</span>
                </a>

                <div class="topbar-right">
                    <div class="global-search-wrap">
                        ${Icon.search('gs-icon')}
                        <input type="search" id="globalSearch" class="global-search"
                               placeholder="${Utils.e(`Search ${L.clientMany.toLowerCase()}, ${L.jobMany.toLowerCase()}, invoices…`)}" autocomplete="off"
                               aria-label="Global search">
                        <div class="ac-dropdown" id="globalSearchResults" hidden></div>
                    </div>

                    <button class="topbar-icon-btn" id="themeToggle" aria-label="Switch colour theme" title="Light / dark">
                        ${Icon.theme('')}
                    </button>

                    <button class="topbar-icon-btn" id="notifBtn" aria-label="Notifications" title="${Utils.e(L.queueLabel)}">
                        ${Icon.bell('')}
                        <span class="notif-dot" id="notifDot" hidden></span>
                    </button>

                    <div class="user-menu" id="userMenu">
                        <button class="user-menu-trigger" aria-haspopup="true" aria-expanded="false" id="userMenuBtn">
                            <div class="user-avatar" aria-hidden="true">${Utils.e(Utils.initials(user?.full_name))}</div>
                            <div class="user-info">
                                <span class="user-name">${Utils.e(user?.full_name ?? 'Guest')}</span>
                                <span class="user-role">${Utils.e(USER_ROLES[user?.role] ?? '')}</span>
                            </div>
                            <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>

                        <div class="user-dropdown" id="userDropdown" role="menu" hidden>
                            <div class="dropdown-label">Demo — switch role</div>
                            ${roleSwitcher}
                            <hr class="dropdown-divider">
                            <a href="#/admin/sysinfo" role="menuitem">${Icon.cog('')} System Info</a>
                            <a href="javascript:void(0)" role="menuitem" class="logout-link" id="logoutLink">
                                ${Icon.logout('')} Logout
                            </a>
                        </div>
                    </div>
                </div>
            </header>`;
    },

    sidebar() {
        const items = this.navItems().filter(it => !it.role || Auth.can(it.role));

        const html = items.map(it => {
            if (it.divider) return '<li class="nav-divider" role="separator"></li>';

            if (it.children) {
                const subs = it.children.map(s =>
                    `<li><a href="${s.href}" class="sub-nav-link" data-match="${s.match.source}">${Utils.e(s.label)}</a></li>`).join('');
                return `
                    <li class="nav-item has-sub" data-match="${it.match.source}">
                        <button class="nav-link nav-group-toggle" aria-expanded="false">
                            ${Icon[it.icon]('nav-icon')}<span>${Utils.e(it.label)}</span>
                            <svg class="sub-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <ul class="sub-nav">${subs}</ul>
                    </li>`;
            }

            return `
                <li class="nav-item">
                    <a href="${it.href}" class="nav-link" data-match="${it.match.source}">
                        ${Icon[it.icon]('nav-icon')}<span>${Utils.e(it.label)}</span>
                    </a>
                </li>`;
        }).join('');

        return `
            <nav class="sidebar" id="sidebar" role="navigation" aria-label="Main navigation">
                <div class="sidebar-header">
                    <span class="sidebar-title">Navigation</span>
                    <button class="sidebar-close-btn" id="sidebarClose" aria-label="Close sidebar">&times;</button>
                </div>
                <ul class="nav-list">${html}</ul>
                <div class="sidebar-footer">
                    <a href="javascript:void(0)" class="sidebar-logout" id="sidebarLogout">
                        ${Icon.logout('')} Logout
                    </a>
                </div>
            </nav>`;
    },

    footer() {
        return `
            <footer class="app-footer" role="contentinfo">
                <span>&copy; ${new Date().getFullYear()} ${Utils.e(DB.setting('company_name', APP_NAME))} — v${APP_VERSION}</span>
                <span class="footer-debug">
                    <a href="https://github.com/arifbillahcse/a-repair-management-database-system" target="_blank" rel="noopener">
                        Source on GitHub
                    </a>
                    · ${DB.table('customers').length} ${Utils.e(L.clientMany.toLowerCase())} · ${DB.table('repairs').length} ${Utils.e(L.jobMany.toLowerCase())}
                </span>
            </footer>`;
    },

    /* ── Behaviour (port of public/js/main.js) ────────────────────────────── */

    bind() {
        const $ = id => document.getElementById(id);

        // Sidebar (mobile)
        const sidebar = $('sidebar'), overlay = $('sidebarOverlay'), toggle = $('sidebarToggle');
        const open  = () => { sidebar.classList.add('open');  overlay.classList.add('visible');  toggle.setAttribute('aria-expanded', 'true'); };
        const close = () => { sidebar.classList.remove('open'); overlay.classList.remove('visible'); toggle.setAttribute('aria-expanded', 'false'); };
        toggle.onclick = () => sidebar.classList.contains('open') ? close() : open();
        $('sidebarClose').onclick = close;
        overlay.onclick = close;
        document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });

        // Collapsible nav groups
        document.querySelectorAll('.nav-group-toggle').forEach(btn => {
            btn.onclick = () => {
                const sub  = btn.nextElementSibling;
                const isOpen = sub.classList.contains('open');
                sub.classList.toggle('open', !isOpen);
                btn.setAttribute('aria-expanded', String(!isOpen));
            };
        });

        // User menu
        const menuBtn = $('userMenuBtn'), dropdown = $('userDropdown');
        menuBtn.onclick = e => {
            e.stopPropagation();
            const hidden = dropdown.hasAttribute('hidden');
            dropdown.toggleAttribute('hidden', !hidden);
            menuBtn.setAttribute('aria-expanded', String(hidden));
        };
        document.addEventListener('click', () => {
            dropdown.setAttribute('hidden', '');
            menuBtn.setAttribute('aria-expanded', 'false');
        });
        dropdown.onclick = e => e.stopPropagation();

        // Demo role switcher
        dropdown.querySelectorAll('[data-role]').forEach(b => {
            b.onclick = () => {
                if (Auth.switchRole(b.dataset.role)) {
                    Toast.info(`Now viewing as ${USER_ROLES[b.dataset.role]}.`);
                    this.mounted = false;
                    this.mount();
                    Router.reload();
                } else {
                    Toast.error(`No active ${USER_ROLES[b.dataset.role]} account in the demo data.`);
                }
            };
        });

        // Logout
        const doLogout = () => { Auth.logout(); this.unmount(); Router.go('/login'); };
        $('logoutLink').onclick    = doLogout;
        $('sidebarLogout').onclick = doLogout;

        // Theme toggle
        $('themeToggle').onclick = () => Theme.toggle();

        // Notifications → ready-for-pickup queue
        const ready = Repair.getReadyForPickup();
        if (ready.length) $('notifDot').removeAttribute('hidden');
        $('notifBtn').onclick = () => this.showPickupQueue(ready);

        // Reset demo
        $('demoReset').onclick = async () => {
            const ok = await Modal.confirm({
                title: 'Reset demo data',
                message: 'This clears everything you have added or changed and restores the original demo dataset. Continue?',
                confirmLabel: 'Reset',
            });
            if (!ok) return;
            await DB.reset();
            Toast.success('Demo data restored.');
            this.mounted = false;
            location.hash = '#/login';
            location.reload();
        };

        // Global search
        this.bindGlobalSearch();

        // First-visit hint pointing at the role switcher
        this.maybeShowRoleHint();

        // Close the mobile sidebar whenever a route changes
        window.addEventListener('hashchange', close);
    },

    /**
     * Visitors reliably miss the role switcher, which means they miss the
     * permission gates entirely — the most interesting thing in the demo.
     * Point at it once per browser, then never again.
     */
    HINT_KEY: DEMO_PREFIX + 'role_hint_seen',

    maybeShowRoleHint() {
        let seen = false;
        try { seen = localStorage.getItem(this.HINT_KEY) === '1'; } catch { seen = true; }
        if (seen || document.getElementById('roleHint')) return;

        const dismiss = () => {
            document.getElementById('roleHint')?.remove();
            try { localStorage.setItem(this.HINT_KEY, '1'); } catch { /* private mode */ }
        };

        setTimeout(() => {
            // The visitor may have navigated or logged out in the meantime.
            if (!Auth.check() || document.getElementById('roleHint')) return;

            const anchor = document.getElementById('userMenuBtn');
            if (!anchor) return;

            const tip = document.createElement('div');
            tip.id = 'roleHint';
            tip.className = 'role-hint';
            tip.setAttribute('role', 'status');
            tip.innerHTML = `
                <div class="role-hint-arrow"></div>
                <strong>Try switching roles</strong>
                <p>Open this menu to view the app as a Manager, ${Utils.e(L.staffOne)} or front-desk
                   Staff member. Reports, Staff and Settings appear and disappear with the role.</p>
                <button class="role-hint-ok">Got it</button>`;
            document.body.appendChild(tip);

            const place = () => {
                const r = anchor.getBoundingClientRect();
                tip.style.top  = `${r.bottom + 10}px`;
                tip.style.right = `${Math.max(12, window.innerWidth - r.right)}px`;
            };
            place();
            window.addEventListener('resize', place);

            tip.querySelector('.role-hint-ok').onclick = dismiss;
            anchor.addEventListener('click', dismiss, { once: true });
            setTimeout(dismiss, 15000);
        // Wait out the sign-in toast (4s + fade), which occupies the same
        // top-right corner and would sit on top of the hint.
        }, 4800);
    },

    showPickupQueue(ready) {
        const body = ready.length
            ? `<ul class="pickup-list">${ready.slice(0, 12).map(r => `
                <li class="pickup-item">
                    <div class="pickup-info">
                        <a class="pickup-name" href="#/repairs/${r.repair_id}">${Utils.e(r.customer_name)}</a>
                        <span class="pickup-device">${Utils.e(r.device_model)} · #${r.repair_id}</span>
                    </div>
                    <span class="badge ${Utils.daysBetween(r.date_out || r.date_in) >= 7 ? 'badge-red' : 'badge-blue'}">
                        ${Utils.daysBetween(r.date_out || r.date_in)}d waiting
                    </span>
                </li>`).join('')}</ul>`
            : `<p class="confirm-text">Nothing is in the ${Utils.e(L.queueLabel.toLowerCase())} queue right now.</p>`;

        Modal.open({
            title: `${L.queueLabel} (${ready.length})`,
            body,
            footer: `<a href="#/repairs?status=ready_for_pickup" class="btn btn-primary" onclick="Modal.close()">View all</a>`,
        });
    },

    bindGlobalSearch() {
        const input = document.getElementById('globalSearch');
        const box   = document.getElementById('globalSearchResults');
        if (!input) return;

        const close = () => { box.setAttribute('hidden', ''); box.innerHTML = ''; };

        const run = UI.debounce(() => {
            const q = input.value.trim();
            if (q.length < 2) return close();

            const clients = Customer.search(q, 4);
            const repairs = Repair.getAll({ search: q }, 1, 4).data;
            const invs    = Invoice.getAll({ search: q }, 1, 3).data;

            if (!clients.length && !repairs.length && !invs.length) {
                box.innerHTML = '<div class="ac-empty">No matches found.</div>';
                box.removeAttribute('hidden');
                return;
            }

            const section = (title, rows, fn) => rows.length
                ? `<div class="ac-group">${title}</div>` + rows.map(fn).join('') : '';

            box.innerHTML =
                section(L.clientMany, clients, c => `
                    <a class="ac-item" href="#/customers/${c.customer_id}">
                        <span class="ac-name">${Utils.e(c.full_name)}</span>
                        <span class="ac-meta">${Utils.e(c.phone_mobile ?? '')} · ${Utils.e(c.city ?? '')}</span>
                    </a>`) +
                section(L.jobMany, repairs, r => `
                    <a class="ac-item" href="#/repairs/${r.repair_id}">
                        <span class="ac-name">#${r.repair_id} — ${Utils.e(r.device_model)}</span>
                        <span class="ac-meta">${Utils.e(r.customer_name ?? '')} · ${Utils.e(REPAIR_STATUS[r.status])}</span>
                    </a>`) +
                section('Invoices', invs, i => `
                    <a class="ac-item" href="#/invoices/${i.invoice_id}">
                        <span class="ac-name">${Utils.e(i.invoice_number)}</span>
                        <span class="ac-meta">${Utils.e(i.customer_name ?? '')} · ${Utils.formatCurrency(i.total_amount)}</span>
                    </a>`);

            box.removeAttribute('hidden');
            box.querySelectorAll('.ac-item').forEach(a => { a.onclick = () => { input.value = ''; close(); }; });
        }, 200);

        input.oninput = run;
        input.onkeydown = e => { if (e.key === 'Escape') { input.value = ''; close(); input.blur(); } };
        document.addEventListener('click', e => { if (!input.parentElement.contains(e.target)) close(); });
    },

    /* ── Active-state highlighting, re-run after every route change ───────── */

    setActive(path) {
        document.querySelectorAll('.nav-link[data-match], .sub-nav-link[data-match]').forEach(el => {
            el.classList.toggle('active', new RegExp(el.dataset.match).test(path));
        });
        document.querySelectorAll('.nav-item.has-sub[data-match]').forEach(li => {
            const on = new RegExp(li.dataset.match).test(path);
            li.classList.toggle('active', on);
            li.querySelector('.sub-nav')?.classList.toggle('open', on);
            li.querySelector('.nav-group-toggle')?.setAttribute('aria-expanded', String(on));
        });
    },

    /** Every module calls this to paint itself into the shell. */
    render(html, path) {
        this.mount();
        document.getElementById('viewRoot').innerHTML = html;
        this.setActive(path ?? Router.parse().path);
    },
};
