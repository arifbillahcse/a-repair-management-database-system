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
                        ${Icon.wrench('login-logo')}
                        <h1>${Utils.e(DB.setting('company_name', APP_NAME))}</h1>
                        <p>Repair &amp; service management — interactive demo</p>
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
            technician: 'Repairs and clients only',
            staff:      'Front desk — clients & invoices',
        }[role] ?? '';
    },

    logout() {
        Auth.logout();
        Layout.unmount();
        Router.go('/login');
    },

    forbidden() {
        Layout.render(`
            <div class="error-page">
                <div class="error-code">403</div>
                <h1 class="error-title">Access denied</h1>
                <p class="error-message">
                    Your role (${Utils.e(USER_ROLES[Auth.role()] ?? 'Guest')}) cannot open this page.
                    Use the role switcher in the top-right to try it as an Admin or Manager.
                </p>
                <div class="error-actions">
                    <a href="#/" class="btn btn-primary">Back to dashboard</a>
                </div>
            </div>`);
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
