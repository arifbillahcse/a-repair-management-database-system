/**
 * constants.js — port of config/constants.php
 *
 * Localised for the Bangladesh demo:
 *   currency  €  EUR   →  ৳  BDT
 *   VAT       22.00 %  →  15.00 %   (Bangladesh standard rate)
 */
'use strict';

const APP_NAME    = VARIANT.company;
const APP_VERSION = '1.3.0';

/* ── Demo storage ─────────────────────────────────────────────────────────── */
// Namespaced per variant so the four demos never share a dataset or a session.
const DEMO_PREFIX       = `rms_${VARIANT.id}_`;
const DEMO_SEED_VERSION = 4;                       // bump to force a reseed
const DEMO_SEED_URL     = `../assets/data/${VARIANT.seed}`;

/* ── Pagination ───────────────────────────────────────────────────────────── */
const PAGE_SIZE         = 20;
const PAGE_SIZE_REPAIRS = 30;

/* ── Repair statuses ──────────────────────────────────────────────────────── */
const REPAIR_STATUS = VARIANT.statuses;

// Allowed forward-only status transitions
const REPAIR_STATUS_FLOW = {
    in_progress:       ['on_hold', 'waiting_for_parts', 'completed', 'cancelled'],
    on_hold:           ['in_progress', 'waiting_for_parts', 'cancelled'],
    waiting_for_parts: ['in_progress', 'on_hold', 'cancelled'],
    completed:         ['ready_for_pickup'],
    ready_for_pickup:  ['collected', 'on_hold'],
    collected:         [],
    cancelled:         [],
};

// Status badge CSS classes (mapped to style.css)
const REPAIR_STATUS_CLASS = {
    in_progress:       'badge-gray',
    on_hold:           'badge-red',
    waiting_for_parts: 'badge-orange',
    ready_for_pickup:  'badge-blue',
    completed:         'badge-green',
    collected:         'badge-green-dim',
    cancelled:         'badge-dark',
};

/* ── Invoice statuses ─────────────────────────────────────────────────────── */
const INVOICE_STATUS = {
    draft:          'Draft',
    sent:           'Sent',
    paid:           'Paid',
    partially_paid: 'Partially Paid',
    overdue:        'Overdue',
    cancelled:      'Cancelled',
};

const INVOICE_STATUS_CLASS = {
    draft:          'badge-gray',
    sent:           'badge-blue',
    paid:           'badge-green',
    partially_paid: 'badge-orange',
    overdue:        'badge-red',
    cancelled:      'badge-dark',
};

/* ── User roles ───────────────────────────────────────────────────────────── */
const USER_ROLES = {
    admin:      'Admin',
    manager:    'Manager',
    technician: 'Technician',
    staff:      'Staff',
};

// Role hierarchy (higher = more permissions)
const ROLE_HIERARCHY = {
    technician: 1,
    staff:      2,
    manager:    3,
    admin:      4,
};

/* ── Client types ─────────────────────────────────────────────────────────── */
const CLIENT_TYPES = {
    individual: 'Individual',
    company:    'Company',
    colleague:  'Colleague',
};

/* ── Locale / money ───────────────────────────────────────────────────────── */
const CURRENCY_SYMBOL = '৳';        // ৳
const CURRENCY_CODE   = 'BDT';
const DEFAULT_TAX_PCT = 15.00;           // Bangladesh standard VAT

const MONTH_SHORT = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

/* ── Bangladesh divisions, used by the client form + seed data ────────────── */
const BD_DIVISIONS = {
    DHA: 'Dhaka',      CTG: 'Chattogram', KHU: 'Khulna',   RAJ: 'Rajshahi',
    SYL: 'Sylhet',     BAR: 'Barishal',   RAN: 'Rangpur',  MYM: 'Mymensingh',
};
