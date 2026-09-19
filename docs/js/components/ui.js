/**
 * components/ui.js — shared render helpers.
 *
 * Replaces the markup that views/layouts/*.php and the five list views
 * duplicated: flash messages, badges, icons, stat cards, empty states,
 * modals and confirm dialogs. All output uses the existing style.css classes.
 */
'use strict';

/* ── Icons (lifted from the PHP views, so the demo looks identical) ───────── */

const Icon = {
    _wrap(paths, cls = '') {
        return `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths}</svg>`;
    },
    dashboard: c => Icon._wrap('<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>', c),
    wrench:    c => Icon._wrap('<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>', c),
    users:     c => Icon._wrap('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>', c),
    user:      c => Icon._wrap('<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>', c),
    invoice:   c => Icon._wrap('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>', c),
    chart:     c => Icon._wrap('<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>', c),
    upload:    c => Icon._wrap('<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>', c),
    download:  c => Icon._wrap('<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>', c),
    cog:       c => Icon._wrap('<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06A1.65 1.65 0 0 0 15 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 8.6 15a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 12 8.6a1.65 1.65 0 0 0 1.82.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 15z"/>', c),
    logout:    c => Icon._wrap('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>', c),
    bell:      c => Icon._wrap('<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>', c),
    plus:      c => Icon._wrap('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>', c),
    edit:      c => Icon._wrap('<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>', c),
    trash:     c => Icon._wrap('<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>', c),
    eye:       c => Icon._wrap('<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>', c),
    print:     c => Icon._wrap('<polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>', c),
    search:    c => Icon._wrap('<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>', c),
    phone:     c => Icon._wrap('<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>', c),
    mail:      c => Icon._wrap('<path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><polyline points="22,6 12,13 2,6"/>', c),
    clock:     c => Icon._wrap('<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>', c),
    check:     c => Icon._wrap('<polyline points="20 6 9 17 4 12"/>', c),
    alert:     c => Icon._wrap('<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>', c),
    box:       c => Icon._wrap('<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>', c),
    money:     c => Icon._wrap('<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>', c),
    back:      c => Icon._wrap('<line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>', c),
    theme:     c => Icon._wrap('<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>', c),
    refresh:   c => Icon._wrap('<polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>', c),
};

/* ── Badges ───────────────────────────────────────────────────────────────── */

const Badge = {
    repair(status) {
        const cls = REPAIR_STATUS_CLASS[status] ?? 'badge-gray';
        return `<span class="badge ${cls}">${Utils.e(REPAIR_STATUS[status] ?? status)}</span>`;
    },
    invoice(status) {
        const cls = INVOICE_STATUS_CLASS[status] ?? 'badge-gray';
        return `<span class="badge ${cls}">${Utils.e(INVOICE_STATUS[status] ?? status)}</span>`;
    },
    clientType(type) {
        const cls = { individual: 'badge-gray', company: 'badge-blue', colleague: 'badge-purple' }[type] ?? 'badge-gray';
        return `<span class="badge ${cls}">${Utils.e(CLIENT_TYPES[type] ?? type)}</span>`;
    },
    active(status) {
        return status === 'active'
            ? '<span class="badge badge-green">Active</span>'
            : '<span class="badge badge-dark">Inactive</span>';
    },
    role(role) {
        const cls = { admin: 'badge-red', manager: 'badge-blue', technician: 'badge-orange', staff: 'badge-gray' }[role] ?? 'badge-gray';
        return `<span class="badge ${cls}">${Utils.e(USER_ROLES[role] ?? role)}</span>`;
    },
};

/* ── Toast / flash messages ───────────────────────────────────────────────── */

const Toast = {
    show(type, message, ms = 4000) {
        let box = document.getElementById('flashContainer');
        if (!box) {
            box = document.createElement('div');
            box.id = 'flashContainer';
            box.className = 'flash-container';
            box.setAttribute('role', 'alert');
            box.setAttribute('aria-live', 'polite');
            document.body.appendChild(box);
        }
        const el = document.createElement('div');
        el.className = `flash flash-${type}`;
        el.innerHTML = `${Utils.e(message)}<button class="flash-close" aria-label="Dismiss">&times;</button>`;
        el.querySelector('.flash-close').onclick = () => el.remove();
        box.appendChild(el);
        setTimeout(() => {
            el.style.opacity = '0';
            el.style.transform = 'translateX(20px)';
            setTimeout(() => el.remove(), 400);
        }, ms);
    },
    success(m) { this.show('success', m); },
    error(m)   { this.show('error',   m); },
    warning(m) { this.show('warning', m); },
    info(m)    { this.show('info',    m); },
};

/* ── Modal + confirm ──────────────────────────────────────────────────────── */

const Modal = {
    open({ title, body, footer = '', size = '' }) {
        this.close();
        const wrap = document.createElement('div');
        wrap.className = 'modal-backdrop';
        wrap.id = 'appModal';
        wrap.innerHTML = `
            <div class="modal ${size}" role="dialog" aria-modal="true" aria-label="${Utils.e(title)}">
                <div class="modal-header">
                    <h2 class="modal-title">${Utils.e(title)}</h2>
                    <button class="modal-close" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">${body}</div>
                ${footer ? `<div class="modal-footer">${footer}</div>` : ''}
            </div>`;
        document.body.appendChild(wrap);
        document.body.style.overflow = 'hidden';

        wrap.querySelector('.modal-close').onclick = () => this.close();
        wrap.onclick = e => { if (e.target === wrap) this.close(); };
        document.addEventListener('keydown', this._esc);

        const focusable = wrap.querySelector('input, select, textarea, button:not(.modal-close)');
        if (focusable) setTimeout(() => focusable.focus(), 50);
        return wrap;
    },

    _esc(e) { if (e.key === 'Escape') Modal.close(); },

    close() {
        document.getElementById('appModal')?.remove();
        document.body.style.overflow = '';
        document.removeEventListener('keydown', this._esc);
    },

    /** Replaces the PHP delete-confirm forms. Returns a Promise<boolean>. */
    confirm({ title = 'Are you sure?', message, confirmLabel = 'Delete', danger = true }) {
        return new Promise(resolve => {
            const wrap = this.open({
                title,
                body: `<p class="confirm-text">${Utils.e(message)}</p>`,
                footer: `
                    <button class="btn btn-secondary" data-act="cancel">Cancel</button>
                    <button class="btn ${danger ? 'btn-danger' : 'btn-primary'}" data-act="ok">${Utils.e(confirmLabel)}</button>`,
            });
            wrap.querySelector('[data-act="cancel"]').onclick = () => { this.close(); resolve(false); };
            wrap.querySelector('[data-act="ok"]').onclick     = () => { this.close(); resolve(true);  };
        });
    },
};

/* ── Small render helpers ─────────────────────────────────────────────────── */

const UI = {

    /** Dashboard stat card — matches .stat-card in style.css. */
    statCard({ label, value, icon = 'box', tone = 'accent', link = null, sub = '' }) {
        const inner = `
            <div class="stat-icon stat-icon-${tone}">${Icon[icon] ? Icon[icon]('') : Icon.box('')}</div>
            <div class="stat-body">
                <span class="stat-label">${Utils.e(label)}</span>
                <span class="stat-value">${value}</span>
                ${sub ? `<span class="stat-sub">${sub}</span>` : ''}
            </div>`;
        return link
            ? `<a class="stat-card stat-card-link" href="${link}">${inner}</a>`
            : `<div class="stat-card">${inner}</div>`;
    },

    emptyState(message, actionHtml = '', icon = 'box') {
        return `
            <div class="empty-big">
                ${Icon[icon] ? Icon[icon]('') : Icon.box('')}
                <p>${Utils.e(message)}</p>
                ${actionHtml}
            </div>`;
    },

    pageHeader(title, subtitle = '', actions = '') {
        return `
            <div class="page-header">
                <div>
                    <h1 class="page-title">${Utils.e(title)}</h1>
                    ${subtitle ? `<p class="page-subtitle">${subtitle}</p>` : ''}
                </div>
                ${actions ? `<div class="header-actions">${actions}</div>` : ''}
            </div>`;
    },

    /** Read-only definition row used across the detail views. */
    field(label, value, isHtml = false) {
        const v = value === null || value === undefined || value === ''
            ? '<span class="text-muted">—</span>'
            : (isHtml ? value : Utils.e(value));
        return `<div class="detail-row"><span class="detail-label">${Utils.e(label)}</span><span class="detail-value">${v}</span></div>`;
    },

    backLink(href, label) {
        return `<a href="${href}" class="back-link">${Icon.back('')}${Utils.e(label)}</a>`;
    },

    /** Pagination bar — same markup as the PHP .pagination partial. */
    pagination(meta, onPage) {
        if (meta.total_pages <= 1) return '';
        const btn = (p, label, cls = '') =>
            `<a href="javascript:void(0)" class="page-link ${cls}" data-page="${p}">${label}</a>`;

        const pages = [];
        const { current_page: cur, total_pages: tp } = meta;
        let start = Math.max(1, cur - 2), end = Math.min(tp, start + 4);
        start = Math.max(1, end - 4);

        pages.push(btn(cur - 1, '‹', cur === 1 ? 'disabled' : ''));
        if (start > 1) { pages.push(btn(1, '1')); if (start > 2) pages.push('<span class="page-link disabled">…</span>'); }
        for (let p = start; p <= end; p++) pages.push(btn(p, p, p === cur ? 'current' : ''));
        if (end < tp) { if (end < tp - 1) pages.push('<span class="page-link disabled">…</span>'); pages.push(btn(tp, tp)); }
        pages.push(btn(cur + 1, '›', cur === tp ? 'disabled' : ''));

        // Deferred binding: the caller mounts the HTML, then calls bindPagination().
        UI._pageHandler = onPage;
        return `<div class="pagination">${pages.join('')}</div>`;
    },

    bindPagination(root = document) {
        const handler = UI._pageHandler;
        if (!handler) return;
        root.querySelectorAll('.pagination .page-link[data-page]').forEach(a => {
            if (a.classList.contains('disabled') || a.classList.contains('current')) return;
            a.onclick = () => handler(Utils.intVal(a.dataset.page));
        });
    },

    /** Filter pill row (.status-filters / .sf-btn) used by the list pages. */
    filterPills(items, activeValue, onPick) {
        const html = items.map(it => `
            <button class="sf-btn ${it.value === activeValue ? 'active' : ''} ${it.cls ?? ''}" data-val="${Utils.e(it.value)}">
                ${Utils.e(it.label)}
                ${it.count !== undefined ? `<span class="sf-count">${Utils.numberFormat(it.count)}</span>` : ''}
            </button>`).join('');
        UI._pillHandler = onPick;
        return `<div class="status-filters">${html}</div>`;
    },

    bindFilterPills(root = document) {
        const handler = UI._pillHandler;
        if (!handler) return;
        root.querySelectorAll('.status-filters .sf-btn').forEach(b => {
            b.onclick = () => handler(b.dataset.val);
        });
    },

    /** Debounce for search inputs (replaces the AJAX throttle in main.js). */
    debounce(fn, ms = 250) {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
    },
};
