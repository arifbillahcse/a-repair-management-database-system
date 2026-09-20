/**
 * modules/auth.js — port of views/auth/login.php + AuthController
 * Demo login: any password works, plus one-click role buttons.
 */
'use strict';

const AuthView = {

    login() {
        if (Auth.check()) return Router.go('/');

        Layout.unmount();
        document.getElementById('viewRoot').innerHTML = `
            <div class="login-page">
                <div class="login-card">
                    <div class="login-brand">
                        ${Icon.brand('login-logo')}
                        <h1>${Utils.e(DB.setting('company_name', APP_NAME))}</h1>
                        <p>${Utils.e(VARIANT.industry)} — interactive demo</p>
                    </div>

                    <form id="loginForm" class="login-form" novalidate>
                        <div class="form-group">
                            <label class="form-label" for="identifier">Username or email</label>
                            <input class="form-input" id="identifier" name="identifier" value="admin"
                                   autocomplete="username" required>
                            <span class="field-error" id="err-identifier"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Password</label>
                            <input class="form-input" id="password" name="password" type="password"
                                   value="demo1234" autocomplete="current-password">
                            <span class="form-hint">Any password is accepted in the demo.</span>
                        </div>

                        <button type="submit" class="btn btn-primary btn-full">Sign in</button>
                    </form>

                    <div class="login-divider"><span>or jump straight in as</span></div>

                    <div class="role-buttons">
                        ${Object.entries(USER_ROLES).map(([key, label]) => `
                            <button class="btn btn-secondary role-btn" data-role="${key}">
                                ${Icon.user('')}<span>${Utils.e(label)}</span>
                                <em>${Utils.e(AuthView._roleHint(key))}</em>
                            </button>`).join('')}
                    </div>

                    <p class="login-note">
                        This is a front-end prototype. Data lives in your browser only —
                        there is no server and no database behind it.
                    </p>
                </div>
            </div>`;

        document.getElementById('loginForm').onsubmit = e => {
            e.preventDefault();
            const id  = document.getElementById('identifier').value;
            const pwd = document.getElementById('password').value;
            const res = Auth.login(id, pwd);
            if (!res.ok) {
                document.getElementById('err-identifier').textContent = res.error;
                document.getElementById('identifier').classList.add('input-error');
                return;
            }
            Toast.success(`Welcome back, ${Dashboard._firstName(Auth.user().full_name)}.`);
            Router.go('/');
        };

        document.querySelectorAll('.role-btn').forEach(b => {
            b.onclick = () => {
                const res = Auth.loginAs(b.dataset.role);
                if (!res.ok) return Toast.error(res.error);
                Toast.success(`Signed in as ${USER_ROLES[b.dataset.role]}.`);
                Router.go('/');
            };
        });
    },

    _roleHint(role) {
        return {
            admin:      'Everything, incl. settings & import',
            manager:    'Reports and staff management',
            technician: `${L.jobMany} and ${L.clientMany.toLowerCase()} only`,
            staff:      `Front desk — ${L.clientMany.toLowerCase()} & invoices`,
        }[role] ?? '';
    },

    logout() {
        Auth.logout();
        Layout.unmount();
        Router.go('/login');
    },

    /**
     * A plain 403 is a dead end: the visitor is told no and left there. This
     * one names the role the page needs and switches to it in one click, then
     * returns to the page that was blocked — which is also the clearest way to
     * demonstrate that the permission gates are real.
     */
    forbidden() {
        const denied  = Auth.denied;
        const minRole = denied?.minRole ?? 'admin';
        const options = Auth.rolesFor(minRole);
        const target  = denied ? { path: denied.path, query: denied.query ?? {} } : { path: '/', query: {} };

        Layout.render(`
            <div class="error-page">
                <div class="error-code">403</div>
                <h1 class="error-title">That page needs a different role</h1>
                <p class="error-message">
                    You are signed in as <strong>${Utils.e(USER_ROLES[Auth.role()] ?? 'Guest')}</strong>.
                    ${denied ? `<code>${Utils.e(denied.path)}</code> is limited to` : 'This page is limited to'}
                    <strong>${Utils.e(USER_ROLES[minRole] ?? minRole)}</strong> and above.
                </p>
                <p class="error-message text-muted small">
                    This is the app's real permission system, not a demo limitation — switch role
                    and you will be taken straight to the page.
                </p>

                <div class="error-actions">
                    ${options.map((r, i) => `
                        <button class="btn ${i === 0 ? 'btn-primary' : 'btn-secondary'}" data-as="${r}">
                            ${Icon.user('')} View as ${Utils.e(USER_ROLES[r])}
                        </button>`).join('')}
                    <a href="#/" class="btn btn-secondary">Back to dashboard</a>
                </div>
            </div>`);

        document.querySelectorAll('[data-as]').forEach(b => {
            b.onclick = () => {
                if (!Auth.switchRole(b.dataset.as)) return Toast.error('That demo account is unavailable.');
                Auth.denied = null;
                Toast.success(`Now viewing as ${USER_ROLES[b.dataset.as]}.`);
                Layout.mounted = false;
                Layout.mount();
                Router.go(target.path, target.query);
            };
        });
    },

    notFound(path) {
        Layout.render(`
            <div class="error-page">
                <div class="error-code">404</div>
                <h1 class="error-title">Page not found</h1>
                <p class="error-message">Nothing matches <code>${Utils.e(path ?? '')}</code>.</p>
                <div class="error-actions">
                    <a href="#/" class="btn btn-primary">Back to dashboard</a>
                </div>
            </div>`);
    },
};
