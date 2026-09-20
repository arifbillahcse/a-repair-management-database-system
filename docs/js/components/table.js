/**
 * components/table.js
 *
 * One reusable data table. In the PHP app the same sort-link + filter-bar +
 * pagination + action-button markup was repeated across five list views
 * (customers, repairs, invoices, staff, admin/users) — roughly 90 KB of
 * near-identical code. This component replaces all of it.
 *
 * Usage:
 *     DataTable.render({
 *         mount:   '#listMount',
 *         rows:    data,
 *         total:   all.length,
 *         columns: [
 *             { key: 'full_name', label: 'Name', sortable: true,
 *               render: r => `<a href="#/customers/${r.customer_id}">…</a>` },
 *             { key: 'city', label: 'City', hideOnTablet: true },
 *         ],
 *         sort: 'full_name', dir: 'ASC', page: 1, perPage: 20,
 *         onSort: (col, dir) => Router.setQuery({ sort: col, dir, page: 1 }),
 *         onPage: page => Router.setQuery({ page }),
 *         actions: r => `…buttons…`,
 *         empty:   { message: 'No clients yet', action: '…' },
 *     });
 */
'use strict';

const DataTable = {

    _sortIcon(col, sort, dir) {
        const s = '<svg class="sort-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">';
        if (sort !== col) {
            return s + '<line x1="12" y1="5" x2="12" y2="19"/><polyline points="8 9 12 5 16 9"/><polyline points="16 15 12 19 8 15"/></svg>';
        }
        return dir === 'ASC'
            ? s + '<polyline style="stroke:var(--accent)" points="8 15 12 19 16 15"/></svg>'
            : s + '<polyline style="stroke:var(--accent)" points="8 9 12 5 16 9"/></svg>';
    },

    html(cfg) {
        const {
            rows = [], columns = [], sort = '', dir = 'ASC',
            actions = null, empty = {}, rowClass = null,
        } = cfg;

        if (!rows.length) {
            return UI.emptyState(
                empty.message ?? 'Nothing to show yet.',
                empty.action ?? '',
                empty.icon ?? 'box'
            );
        }

        const head = columns.map(c => {
            const cls = [c.hideOnTablet ? 'hide-t' : '', c.align === 'right' ? 'ta-right' : ''].filter(Boolean).join(' ');
            const inner = c.sortable
                ? `<a href="javascript:void(0)" class="sort-lnk" data-sort="${Utils.e(c.key)}">${Utils.e(c.label)}${this._sortIcon(c.key, sort, dir)}</a>`
                : Utils.e(c.label);
            return `<th class="${cls}"${c.width ? ` style="width:${c.width}"` : ''}>${inner}</th>`;
        }).join('');

        const body = rows.map(r => {
            const cells = columns.map(c => {
                const cls = [c.hideOnTablet ? 'hide-t' : '', c.align === 'right' ? 'ta-right' : ''].filter(Boolean).join(' ');
                const val = c.render ? c.render(r) : Utils.e(r[c.key] ?? '—');
                return `<td class="${cls}">${val}</td>`;
            }).join('');
            const acts = actions ? `<td class="ta-right"><div class="act-btns">${actions(r)}</div></td>` : '';
            return `<tr class="${rowClass ? rowClass(r) : ''}">${cells}${acts}</tr>`;
        }).join('');

        return `
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr>${head}${actions ? '<th class="ta-right">Actions</th>' : ''}</tr></thead>
                    <tbody>${body}</tbody>
                </table>
            </div>`;
    },

    /** Footer strip: "Showing 1–20 of 138" + pager. */
    footer(meta, onPage) {
        if (!meta || meta.total === 0) return '';
        const pager = UI.pagination(meta, onPage);
        return `
            <div class="tbl-footer">
                <span class="tbl-footer-info">
                    Showing ${Utils.numberFormat(meta.from)}–${Utils.numberFormat(meta.to)}
                    of ${Utils.numberFormat(meta.total)}
                </span>
                ${pager}
            </div>`;
    },

    /** Render into the DOM and wire up sort links + pagination. */
    render(cfg) {
        const mount = typeof cfg.mount === 'string' ? document.querySelector(cfg.mount) : cfg.mount;
        if (!mount) return;

        mount.innerHTML = this.html(cfg) + (cfg.pagination ? this.footer(cfg.pagination, cfg.onPage) : '');

        if (cfg.onSort) {
            mount.querySelectorAll('.sort-lnk[data-sort]').forEach(a => {
                a.onclick = () => {
                    const col = a.dataset.sort;
                    const nextDir = (cfg.sort === col && cfg.dir === 'ASC') ? 'DESC' : 'ASC';
                    cfg.onSort(col, nextDir);
                };
            });
        }
        UI.bindPagination(mount);
    },

    /* ── Action buttons (the .act-btn set from the PHP list views) ────────── */

    act: {
        view(href)  { return `<a href="${href}" class="act-btn" title="View">${Icon.eye('')}</a>`; },
        edit(href)  { return `<a href="${href}" class="act-btn act-btn-g" title="Edit">${Icon.edit('')}</a>`; },
        print(href) { return `<a href="${href}" class="act-btn" title="Print">${Icon.print('')}</a>`; },
        /** data-del carries the id; the module binds a single delegated handler. */
        del(id, label = 'Delete') {
            return `<button class="act-btn act-btn-d" data-del="${Utils.e(id)}" title="${Utils.e(label)}">${Icon.trash('')}</button>`;
        },
    },

    /**
     * Bind every [data-del] in a container to one confirm+delete handler.
     *
     * `guard(id)` runs BEFORE the confirm and returns { ok, reason }. Without
     * it the user gets asked to confirm a delete that the model then refuses —
     * a confirm-then-refuse sequence that reads like the app is broken.
     */
    bindDelete(root, { message, onConfirm, guard = null, blockedTitle = 'Cannot delete this' }) {
        (typeof root === 'string' ? document.querySelector(root) : root)
            ?.querySelectorAll('[data-del]').forEach(btn => {
                btn.onclick = async () => {
                    const id = Utils.intVal(btn.dataset.del);

                    if (guard) {
                        const check = guard(id);
                        if (!check.ok) {
                            Modal.open({
                                title: blockedTitle,
                                body: `<p class="confirm-text">${Utils.e(check.reason)}</p>`,
                                footer: '<button class="btn btn-secondary" onclick="Modal.close()">Close</button>',
                            });
                            return;
                        }
                    }

                    const ok = await Modal.confirm({
                        title: 'Confirm delete',
                        message: typeof message === 'function' ? message(btn.dataset.del) : message,
                    });
                    if (ok) onConfirm(id);
                };
            });
    },
};
