/**
 * auth.js — port of src/Auth.php
 *
 * Demo mode: ANY password is accepted. The "session" lives in sessionStorage,
 * so closing the tab logs the visitor out and the demo always opens clean.
 * Role hierarchy and the can()/isAdmin() gates are the real ones from the PHP.
 */
'use strict';

const Auth = {

    SESSION_KEY: DEMO_PREFIX + 'user',

    /* ── Session ──────────────────────────────────────────────────────────── */

    user() {
        try {
            return JSON.parse(sessionStorage.getItem(this.SESSION_KEY) ?? 'null');
        } catch { return null; }
    },

    check() { return this.user() !== null; },

    role() { return this.user()?.role ?? null; },

    /** Auth::can('manager') — true when the current role ranks at or above. */
    can(minRole) {
        const mine = ROLE_HIERARCHY[this.role()] ?? 0;
        return mine >= (ROLE_HIERARCHY[minRole] ?? 999);
    },

    isAdmin() { return this.role() === 'admin'; },

    /**
     * Demo login. Matches on username OR email; password is not checked.
     * Returns { ok, error }.
     */
    login(identifier, _password) {
        const id   = String(identifier ?? '').trim().toLowerCase();
        if (!id) return { ok: false, error: 'Please enter your username or email.' };

        const user = DB.table('users').find(u =>
            String(u.username).toLowerCase() === id ||
            String(u.email ?? '').toLowerCase() === id
        );

        if (!user)                  return { ok: false, error: 'No such user. Try demo / admin.' };
        if (user.status !== 'active') return { ok: false, error: 'This account is disabled.' };

        this._setUser(user);
        DB.update('users', user.user_id, { last_login: Utils.now() });
        DB.log('login', 'user', user.user_id, `${user.full_name} signed in`);
        return { ok: true };
    },

    /** One-click demo entry, used by the login page buttons. */
    loginAs(role) {
        const user = DB.table('users').find(u => u.role === role && u.status === 'active');
        if (!user) return { ok: false, error: `No demo ${role} account exists.` };
        this._setUser(user);
        DB.log('login', 'user', user.user_id, `${user.full_name} signed in as ${role}`);
        return { ok: true };
    },

    /**
     * Demo-only: swap roles without logging out, so the RBAC gates
     * (Reports, Staff, Import, Settings) can be shown off live.
     */
    switchRole(role) {
        if (!(role in USER_ROLES)) return false;
        const user = DB.table('users').find(u => u.role === role && u.status === 'active');
        if (!user) return false;
        this._setUser(user);
        return true;
    },

    logout() {
        const user = this.user();
        if (user) DB.log('logout', 'user', user.user_id, `${user.full_name} signed out`);
        sessionStorage.removeItem(this.SESSION_KEY);
    },

    _setUser(user) {
        sessionStorage.setItem(this.SESSION_KEY, JSON.stringify({
            user_id:   user.user_id,
            username:  user.username,
            full_name: user.full_name,
            email:     user.email,
            role:      user.role,
            staff_id:  user.staff_id ?? null,
        }));
    },

    /* ── Guards (Auth::requireAuth / requireRole) ─────────────────────────── */

    /** Returns true when the route may render; otherwise redirects. */
    requireAuth() {
        if (this.check()) return true;
        location.hash = '#/login';
        return false;
    },

    requireRole(minRole) {
        if (!this.requireAuth()) return false;
        if (this.can(minRole)) return true;
        location.hash = '#/403';
        return false;
    },
};
