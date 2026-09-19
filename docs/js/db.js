/**
 * db.js — the demo "database".
 *
 * Port of src/Database.php + models/BaseModel.php, backed by localStorage
 * instead of MySQL. The public API deliberately mirrors BaseModel so the
 * module code reads almost identically to the PHP controllers:
 *
 *     DB.findById('customers', 42)
 *     DB.findAll('repairs', { status: 'in_progress' }, 'date_in DESC', 20)
 *     DB.create('customers', {...})
 *     DB.update('repairs', 7, {...})
 *     DB.delete('invoices', 3)
 *     DB.paginate('customers', filters, page, 20)
 *
 * Photos are the one exception: base64 blobs go to IndexedDB (see photos.js
 * usage in the repairs module) because localStorage caps out around 5 MB.
 */
'use strict';

const DB = {

    /** Tables mirror schema.sql. Each has an id column + an auto-increment. */
    TABLES: {
        customers:     'customer_id',
        repairs:       'repair_id',
        invoices:      'invoice_id',
        invoice_items: 'invoice_item_id',
        staff:         'staff_id',
        users:         'user_id',
        products:      'product_id',
        activity_log:  'log_id',
        settings:      'setting_id',
    },

    _cache: {},

    /* ── Storage primitives ───────────────────────────────────────────────── */

    _key(table) { return DEMO_PREFIX + table; },

    /** Returns the raw row array for a table (cached in memory per page load). */
    table(name) {
        if (this._cache[name]) return this._cache[name];
        let rows = [];
        try {
            rows = JSON.parse(localStorage.getItem(this._key(name)) ?? '[]');
            if (!Array.isArray(rows)) rows = [];
        } catch { rows = []; }
        this._cache[name] = rows;
        return rows;
    },

    /** Persist a table back to localStorage. */
    save(name, rows) {
        this._cache[name] = rows;
        try {
            localStorage.setItem(this._key(name), JSON.stringify(rows));
        } catch (err) {
            // Quota exceeded — tell the user rather than failing silently.
            console.error('[DB] write failed for ' + name, err);
            if (typeof Toast !== 'undefined') {
                Toast.error('Browser storage is full. Use "Reset Demo" to clear it.');
            }
            return false;
        }
        return true;
    },

    pk(table) { return this.TABLES[table] ?? 'id'; },

    /** AUTO_INCREMENT emulation. */
    nextId(table) {
        const pk = this.pk(table);
        return this.table(table).reduce((max, r) => Math.max(max, Utils.intVal(r[pk])), 0) + 1;
    },

    /* ── Read ─────────────────────────────────────────────────────────────── */

    findById(table, id) {
        const pk = this.pk(table);
        return this.table(table).find(r => Utils.intVal(r[pk]) === Utils.intVal(id)) ?? null;
    },

    findOneBy(table, column, value) {
        return this.table(table).find(r => r[column] === value) ?? null;
    },

    count(table, conditions = {}) {
        return this.where(table, conditions).length;
    },

    /**
     * WHERE builder. Supports:
     *   { status: 'paid' }                  equality
     *   { status: ['paid', 'sent'] }        IN (...)
     *   { total: { gte: 100, lt: 500 } }    range
     *   { name: { like: 'rah' } }           LIKE %…%
     *   { date_out: null }                  IS NULL
     */
    where(table, conditions = {}) {
        const rows = this.table(table);
        const entries = Object.entries(conditions)
            .filter(([, v]) => v !== '' && v !== undefined);
        if (!entries.length) return [...rows];

        return rows.filter(row => entries.every(([col, cond]) => {
            const val = row[col];
            if (cond === null)      return val === null || val === undefined || val === '';
            if (Array.isArray(cond)) return cond.length === 0 || cond.includes(val);

            if (cond && typeof cond === 'object') {
                if ('like' in cond) {
                    return String(val ?? '').toLowerCase().includes(String(cond.like).toLowerCase());
                }
                const n = Utils.floatVal(val);
                if ('gte' in cond && !(n >= Utils.floatVal(cond.gte))) return false;
                if ('lte' in cond && !(n <= Utils.floatVal(cond.lte))) return false;
                if ('gt'  in cond && !(n >  Utils.floatVal(cond.gt)))  return false;
                if ('lt'  in cond && !(n <  Utils.floatVal(cond.lt)))  return false;
                if ('not' in cond && val === cond.not)                 return false;
                if ('dateGte' in cond && String(val ?? '') < String(cond.dateGte)) return false;
                if ('dateLte' in cond && String(val ?? '') > String(cond.dateLte)) return false;
                return true;
            }
            return String(val ?? '') === String(cond);
        }));
    },

    /**
     * BaseModel::findAll() — conditions + 'column DIR' ordering + limit.
     */
    findAll(table, conditions = {}, orderBy = '', limit = 0, offset = 0) {
        let rows = this.where(table, conditions);
        if (orderBy) {
            const [col, dir = 'ASC'] = String(orderBy).trim().split(/\s+/);
            rows = Utils.sortBy(rows, col, dir);
        }
        if (offset) rows = rows.slice(offset);
        if (limit)  rows = rows.slice(0, limit);
        return rows;
    },

    /** BaseModel::paginate() — returns { data, pagination }. */
    paginate(table, conditions = {}, page = 1, perPage = PAGE_SIZE, orderBy = '') {
        const all  = this.findAll(table, conditions, orderBy);
        const meta = Utils.paginate(all.length, page, perPage);
        return { data: all.slice(meta.offset, meta.offset + perPage), pagination: meta };
    },

    /* ── Write ────────────────────────────────────────────────────────────── */

    create(table, data) {
        const rows = this.table(table);
        const pk   = this.pk(table);
        const now  = Utils.now();
        const row  = { ...data, [pk]: this.nextId(table), created_at: now, updated_at: now };
        rows.push(row);
        this.save(table, rows);
        return row[pk];
    },

    update(table, id, data) {
        const rows = this.table(table);
        const pk   = this.pk(table);
        const i    = rows.findIndex(r => Utils.intVal(r[pk]) === Utils.intVal(id));
        if (i === -1) return 0;
        rows[i] = { ...rows[i], ...data, [pk]: rows[i][pk], updated_at: Utils.now() };
        this.save(table, rows);
        return 1;
    },

    delete(table, id) {
        const rows = this.table(table);
        const pk   = this.pk(table);
        const next = rows.filter(r => Utils.intVal(r[pk]) !== Utils.intVal(id));
        if (next.length === rows.length) return 0;
        this.save(table, next);
        return 1;
    },

    /** ON DELETE CASCADE emulation — used by invoices → invoice_items. */
    deleteWhere(table, conditions) {
        const doomed = this.where(table, conditions);
        const pk     = this.pk(table);
        const ids    = new Set(doomed.map(r => Utils.intVal(r[pk])));
        this.save(table, this.table(table).filter(r => !ids.has(Utils.intVal(r[pk]))));
        return doomed.length;
    },

    /* ── JOIN helper ──────────────────────────────────────────────────────── */

    /**
     * Emulates LEFT JOIN by copying selected columns onto each row.
     *   DB.join(repairs, 'customers', 'customer_id', { full_name: 'customer_name' })
     */
    join(rows, table, fkColumn, columnMap) {
        const pk  = this.pk(table);
        const idx = new Map(this.table(table).map(r => [Utils.intVal(r[pk]), r]));
        return rows.map(row => {
            const rel = idx.get(Utils.intVal(row[fkColumn]));
            const out = { ...row };
            for (const [src, dest] of Object.entries(columnMap)) out[dest] = rel ? rel[src] : null;
            return out;
        });
    },

    /* ── Activity log (src/Logger.php) ────────────────────────────────────── */

    log(action, entity, entityId, description = '') {
        const user = (typeof Auth !== 'undefined' && Auth.user()) || null;
        this.create('activity_log', {
            user_id:     user ? user.user_id : null,
            user_name:   user ? user.full_name : 'System',
            action, entity_type: entity, entity_id: entityId,
            description,
        });
        // Keep the demo log bounded so localStorage never fills from browsing.
        const rows = this.table('activity_log');
        if (rows.length > 300) this.save('activity_log', rows.slice(-300));
    },

    recentActivity(limit = 15) {
        return this.findAll('activity_log', {}, 'log_id DESC', limit);
    },

    /* ── Settings (company_settings table) ────────────────────────────────── */

    setting(key, fallback = '') {
        const row = this.findOneBy('settings', 'setting_key', key);
        return row ? row.setting_value : fallback;
    },

    setSetting(key, value) {
        const row = this.findOneBy('settings', 'setting_key', key);
        if (row) this.update('settings', row.setting_id, { setting_value: value });
        else     this.create('settings', { setting_key: key, setting_value: value });
    },

    /* ── Seeding / reset ──────────────────────────────────────────────────── */

    isSeeded() {
        return localStorage.getItem(DEMO_PREFIX + 'seed_version') === String(DEMO_SEED_VERSION);
    },

    async seed(force = false) {
        if (this.isSeeded() && !force) return false;

        const res = await fetch(DEMO_SEED_URL, { cache: 'no-store' });
        if (!res.ok) throw new Error(`Could not load seed data (HTTP ${res.status})`);
        const seed = await res.json();

        this.wipe();
        for (const [table, rows] of Object.entries(seed)) {
            if (!(table in this.TABLES)) continue;
            this.save(table, rows);
        }
        localStorage.setItem(DEMO_PREFIX + 'seed_version', String(DEMO_SEED_VERSION));
        return true;
    },

    /** Clear every demo key — leaves other sites' storage alone. */
    wipe() {
        this._cache = {};
        Object.keys(localStorage)
            .filter(k => k.startsWith(DEMO_PREFIX))
            .forEach(k => localStorage.removeItem(k));
    },

    /** Full reset used by the "Reset Demo" button. */
    async reset() {
        this.wipe();
        sessionStorage.removeItem(DEMO_PREFIX + 'user');
        await this.seed(true);
    },

    /** Rough storage footprint, shown on the System Info page. */
    usage() {
        let bytes = 0;
        Object.keys(localStorage)
            .filter(k => k.startsWith(DEMO_PREFIX))
            .forEach(k => { bytes += k.length + (localStorage.getItem(k) ?? '').length; });
        return bytes * 2; // UTF-16
    },
};
