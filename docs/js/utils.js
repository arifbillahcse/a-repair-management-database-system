/**
 * utils.js — port of src/Utils.php
 * Pure helpers: escaping, formatting, validation, pagination maths.
 */
'use strict';

const Utils = {

    /* ── Escaping ─────────────────────────────────────────────────────────── */

    /** htmlspecialchars() equivalent — use on EVERY interpolated value. */
    e(value) {
        if (value === null || value === undefined) return '';
        return String(value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    },

    /** Escape a value for use inside a single-quoted JS attribute handler. */
    js(value) {
        return String(value ?? '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
    },

    sanitize(value) {
        return String(value ?? '').trim();
    },

    intVal(value) {
        const n = parseInt(value, 10);
        return Number.isNaN(n) ? 0 : n;
    },

    floatVal(value) {
        const n = parseFloat(String(value ?? '').replace(',', '.'));
        return Number.isNaN(n) ? 0 : n;
    },

    /* ── Validation ───────────────────────────────────────────────────────── */

    isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(String(email ?? '').trim());
    },

    /** Keep digits and a leading +, matching Utils::sanitizePhone(). */
    sanitizePhone(phone) {
        return String(phone ?? '').replace(/[^\d+]/g, '');
    },

    /** Bangladeshi mobile: 01XXXXXXXXX, optionally +88 prefixed. */
    isValidPhone(phone) {
        const p = this.sanitizePhone(phone);
        return /^(\+?88)?01[3-9]\d{8}$/.test(p);
    },

    /** BIN / VAT registration number — 9 or 13 digits. */
    isValidVat(vat) {
        if (!vat) return true;
        return /^\d{9}(\d{4})?$/.test(String(vat).replace(/\D/g, ''));
    },

    /** National ID — 10, 13 or 17 digits. */
    isValidTaxId(taxId) {
        if (!taxId) return true;
        const d = String(taxId).replace(/\D/g, '');
        return d.length === 10 || d.length === 13 || d.length === 17;
    },

    /** Bangladesh postal code — exactly 4 digits. */
    isValidPostalCode(code) {
        if (!code) return true;
        return /^\d{4}$/.test(String(code).trim());
    },

    /* ── Dates ────────────────────────────────────────────────────────────── */

    /** Accepts 'YYYY-MM-DD', 'YYYY-MM-DD HH:MM:SS', Date, or ISO string. */
    toDate(value) {
        if (!value) return null;
        if (value instanceof Date) return value;
        const d = new Date(String(value).replace(' ', 'T'));
        return Number.isNaN(d.getTime()) ? null : d;
    },

    /** d/m/Y — DATE_FORMAT */
    formatDate(value) {
        const d = this.toDate(value);
        if (!d) return '—';
        const p = n => String(n).padStart(2, '0');
        return `${p(d.getDate())}/${p(d.getMonth() + 1)}/${d.getFullYear()}`;
    },

    /** d/m/Y H:i — DATETIME_FORMAT */
    formatDateTime(value) {
        const d = this.toDate(value);
        if (!d) return '—';
        const p = n => String(n).padStart(2, '0');
        return `${this.formatDate(d)} ${p(d.getHours())}:${p(d.getMinutes())}`;
    },

    /** Y-m-d — the storage format, and what <input type="date"> expects. */
    toDbDate(value) {
        const d = this.toDate(value) ?? new Date();
        const p = n => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}`;
    },

    /** Y-m-d H:i:s */
    toDbDateTime(value) {
        const d = this.toDate(value) ?? new Date();
        const p = n => String(n).padStart(2, '0');
        return `${this.toDbDate(d)} ${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`;
    },

    now() {
        return this.toDbDateTime(new Date());
    },

    /** Whole days between two dates (b defaults to now). */
    daysBetween(a, b = new Date()) {
        const da = this.toDate(a), db = this.toDate(b);
        if (!da || !db) return 0;
        return Math.floor((db - da) / 86400000);
    },

    timeAgo(value) {
        const d = this.toDate(value);
        if (!d) return '—';
        const secs = Math.floor((Date.now() - d.getTime()) / 1000);
        if (secs < 60)     return 'just now';
        if (secs < 3600)   return `${Math.floor(secs / 60)} min ago`;
        if (secs < 86400)  return `${Math.floor(secs / 3600)} hr ago`;
        if (secs < 604800) return `${Math.floor(secs / 86400)} days ago`;
        return this.formatDate(d);
    },

    /** 'Mar 2026' — used on the dashboard revenue chart. */
    monthLabel(ym) {
        const [y, m] = String(ym).split('-');
        return `${MONTH_SHORT[this.intVal(m) - 1] ?? '?'} ${y}`;
    },

    /* ── Money & numbers ──────────────────────────────────────────────────── */

    /** ৳ 12,500.00 */
    formatCurrency(amount) {
        const n = this.floatVal(amount);
        return `${CURRENCY_SYMBOL} ${n.toLocaleString('en-US', {
            minimumFractionDigits: 2, maximumFractionDigits: 2,
        })}`;
    },

    /** Compact form for stat cards: ৳ 1.2M */
    formatCurrencyShort(amount) {
        const n = this.floatVal(amount);
        if (Math.abs(n) >= 1e7) return `${CURRENCY_SYMBOL} ${(n / 1e7).toFixed(2)} Cr`;
        if (Math.abs(n) >= 1e5) return `${CURRENCY_SYMBOL} ${(n / 1e5).toFixed(2)} L`;
        if (Math.abs(n) >= 1e3) return `${CURRENCY_SYMBOL} ${(n / 1e3).toFixed(1)}K`;
        return this.formatCurrency(n);
    },

    numberFormat(value, decimals = 0) {
        return this.floatVal(value).toLocaleString('en-US', {
            minimumFractionDigits: decimals, maximumFractionDigits: decimals,
        });
    },

    round2(n) {
        return Math.round((this.floatVal(n) + Number.EPSILON) * 100) / 100;
    },

    /* ── Strings ──────────────────────────────────────────────────────────── */

    truncate(text, maxLen = 80) {
        const s = String(text ?? '');
        return s.length <= maxLen ? s : s.slice(0, maxLen - 1).trimEnd() + '…';
    },

    slugify(text) {
        return String(text ?? '').toLowerCase().trim()
            .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    },

    initials(name) {
        const parts = String(name ?? '').trim().split(/\s+/).filter(Boolean);
        if (!parts.length) return 'U';
        return (parts[0][0] + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase();
    },

    generateToken(bytes = 16) {
        const a = new Uint8Array(bytes);
        crypto.getRandomValues(a);
        return Array.from(a, b => b.toString(16).padStart(2, '0')).join('');
    },

    formatFileSize(bytes) {
        const units = ['B', 'KB', 'MB', 'GB'];
        let n = this.intVal(bytes), i = 0;
        while (n >= 1024 && i < units.length - 1) { n /= 1024; i++; }
        return `${n.toFixed(i ? 1 : 0)} ${units[i]}`;
    },

    /* ── Collections ──────────────────────────────────────────────────────── */

    /** Utils::paginate() — returns the metadata a pager needs. */
    paginate(total, page = 1, perPage = PAGE_SIZE) {
        const totalPages = Math.max(1, Math.ceil(total / perPage));
        const current    = Math.min(Math.max(1, this.intVal(page) || 1), totalPages);
        const offset     = (current - 1) * perPage;
        return {
            total, per_page: perPage, current_page: current, total_pages: totalPages,
            offset,
            from: total === 0 ? 0 : offset + 1,
            to:   Math.min(offset + perPage, total),
            has_prev: current > 1,
            has_next: current < totalPages,
        };
    },

    /** Group rows by a key, like SQL GROUP BY. */
    groupBy(rows, key) {
        return rows.reduce((acc, row) => {
            const k = typeof key === 'function' ? key(row) : row[key];
            (acc[k] ??= []).push(row);
            return acc;
        }, {});
    },

    sum(rows, key) {
        return rows.reduce((t, r) => t + this.floatVal(typeof key === 'function' ? key(r) : r[key]), 0);
    },

    /** Multi-key sort: sortBy(rows, 'date_in', 'DESC') */
    sortBy(rows, key, dir = 'ASC') {
        const mult = String(dir).toUpperCase() === 'DESC' ? -1 : 1;
        return [...rows].sort((a, b) => {
            let x = a[key], y = b[key];
            if (x === null || x === undefined) return 1;
            if (y === null || y === undefined) return -1;
            if (typeof x === 'number' && typeof y === 'number') return (x - y) * mult;
            x = String(x).toLowerCase(); y = String(y).toLowerCase();
            return x < y ? -mult : x > y ? mult : 0;
        });
    },

    /* ── CSV (export + import) ────────────────────────────────────────────── */

    toCsv(rows, columns) {
        const esc = v => {
            const s = String(v ?? '');
            return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
        };
        const head = columns.map(c => esc(c.label)).join(',');
        const body = rows.map(r => columns.map(c => esc(
            typeof c.value === 'function' ? c.value(r) : r[c.key]
        )).join(',')).join('\n');
        return `${head}\n${body}`;
    },

    /** Minimal RFC-4180 parser — handles quoted fields and embedded commas. */
    parseCsv(text) {
        const rows = [];
        let row = [], field = '', inQuotes = false;
        const src = String(text).replace(/\r\n/g, '\n').replace(/\r/g, '\n');

        for (let i = 0; i < src.length; i++) {
            const ch = src[i];
            if (inQuotes) {
                if (ch === '"') {
                    if (src[i + 1] === '"') { field += '"'; i++; }
                    else inQuotes = false;
                } else field += ch;
            } else if (ch === '"') {
                inQuotes = true;
            } else if (ch === ',') {
                row.push(field); field = '';
            } else if (ch === '\n') {
                row.push(field); rows.push(row); row = []; field = '';
            } else field += ch;
        }
        if (field !== '' || row.length) { row.push(field); rows.push(row); }
        return rows.filter(r => r.some(c => String(c).trim() !== ''));
    },

    /** Trigger a browser download — replaces the PHP Content-Disposition header. */
    download(filename, content, mime = 'text/csv;charset=utf-8;') {
        const blob = new Blob(['﻿' + content], { type: mime });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href = url; a.download = filename;
        document.body.appendChild(a); a.click(); a.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    },

    /* ── URLs (hash router) ───────────────────────────────────────────────── */

    /** Utils::url('/customers', {...}) → '#/customers?search=x' */
    url(path, params = {}) {
        const clean = Object.entries(params)
            .filter(([, v]) => v !== '' && v !== null && v !== undefined);
        const qs = new URLSearchParams(clean).toString();
        return `#${path}${qs ? '?' + qs : ''}`;
    },
};
