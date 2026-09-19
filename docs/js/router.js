/**
 * router.js — port of src/Router.php for the browser.
 *
 * Hash-based (#/customers/42) because GitHub Pages serves static files only:
 * a path-based route would 404 on refresh or deep link.
 *
 * Same registration API as the PHP:
 *     Router.add('/customers/:id', ctx => Customers.show(ctx));
 */
'use strict';

const Router = {

    routes: [],
    notFoundHandler: null,
    current: null,

    add(path, handler) {
        this.routes.push({
            path,
            handler,
            // '/repairs/:id/edit' → ^/repairs/([^/]+)/edit$
            regex:  new RegExp('^' + path.replace(/:[^/]+/g, '([^/]+)') + '$'),
            params: (path.match(/:([^/]+)/g) ?? []).map(p => p.slice(1)),
        });
        return this;
    },

    setNotFound(handler) { this.notFoundHandler = handler; return this; },

    /* ── Dispatch ─────────────────────────────────────────────────────────── */

    /** '#/customers?search=x&page=2' → { path:'/customers', query:{...} } */
    parse() {
        const raw   = location.hash.replace(/^#/, '') || '/';
        const [p,q] = raw.split('?');
        const path  = (p.replace(/\/+$/, '') || '/');
        return { path, query: Object.fromEntries(new URLSearchParams(q ?? '')) };
    },

    dispatch() {
        const { path, query } = this.parse();

        for (const route of this.routes) {
            const m = path.match(route.regex);
            if (!m) continue;

            const params = {};
            route.params.forEach((name, i) => { params[name] = decodeURIComponent(m[i + 1]); });

            this.current = { path, route: route.path, params, query };
            window.scrollTo(0, 0);

            try {
                route.handler({ params, query, path });
            } catch (err) {
                console.error('[Router] handler failed for ' + path, err);
                if (typeof Toast !== 'undefined') Toast.error('Something went wrong rendering this page.');
            }
            return;
        }

        this.current = { path, route: null, params: {}, query };
        if (this.notFoundHandler) this.notFoundHandler({ path });
    },

    /* ── Navigation helpers ───────────────────────────────────────────────── */

    go(path, params = {}) {
        const target = Utils.url(path, params);
        if (location.hash === target) this.dispatch();   // force re-render
        else location.hash = target;
    },

    /** Re-render the current route — used after a create/update/delete. */
    reload() { this.dispatch(); },

    /** Change one query param while keeping the rest (sorting, filters, paging). */
    setQuery(patch) {
        const { path, query } = this.parse();
        this.go(path, { ...query, ...patch });
    },

    back(fallback = '/') {
        if (history.length > 1) history.back();
        else this.go(fallback);
    },

    start() {
        window.addEventListener('hashchange', () => this.dispatch());
        this.dispatch();
    },
};
