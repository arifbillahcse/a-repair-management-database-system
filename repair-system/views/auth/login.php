<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= Utils::e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <style>
        /* ── Login-page-only overrides ─────────────────────────────────── */
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #111111;
            padding: 1rem;
        }

        .login-wrap {
            width: 100%;
            max-width: 420px;
            animation: fadeInUp .35s ease both;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .login-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .5rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .login-logo svg {
            width: 52px;
            height: 52px;
            stroke: var(--accent);
        }

        .login-logo h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -.02em;
            margin: 0;
        }

        .login-logo p {
            font-size: .85rem;
            color: var(--text-secondary);
            margin: 0;
        }

        .login-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,.5);
        }

        .login-card h2 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0 0 1.5rem;
        }

        .login-footer-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        .login-footer-links a {
            font-size: .82rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: color .2s;
        }

        .login-footer-links a:hover { color: var(--accent); }

        .login-footer-info {
            text-align: center;
            margin-top: 2rem;
            font-size: .78rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

<div class="login-wrap" role="main">

    <!-- Logo -->
    <div class="login-logo">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
        </svg>
        <h1><?= Utils::e(APP_NAME) ?></h1>
        <p>Repair Management System</p>
    </div>

    <!-- Login card -->
    <div class="login-card">
        <h2>Sign in to your account</h2>

        <!-- Server-side error -->
        <?php if (!empty($error)): ?>
        <div class="alert alert-error" role="alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <?= Utils::e($error) ?>
        </div>
        <?php endif; ?>

        <!-- Login form -->
        <form id="loginForm" method="POST" action="<?= BASE_URL ?>/login" novalidate>
            <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken) ?>">

            <!-- Username / Email -->
            <div class="form-group">
                <label for="identifier" class="form-label">
                    Username or Email <span class="required" aria-hidden="true">*</span>
                </label>
                <div class="input-icon-wrap">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" aria-hidden="true">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    <input type="text"
                           id="identifier"
                           name="identifier"
                           class="form-input"
                           placeholder="admin or user@example.com"
                           autocomplete="username"
                           autofocus
                           required
                           value="<?= Utils::e($_POST['identifier'] ?? '') ?>">
                </div>
                <div class="field-error" id="err-identifier" role="alert" aria-live="polite"></div>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password" class="form-label">
                    Password <span class="required" aria-hidden="true">*</span>
                </label>
                <div class="input-icon-wrap">
                    <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" aria-hidden="true">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-input"
                           placeholder="••••••••"
                           autocomplete="current-password"
                           required>
                    <button type="button" class="input-toggle-pwd" aria-label="Show/hide password"
                            data-target="password">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
                <div class="field-error" id="err-password" role="alert" aria-live="polite"></div>
            </div>

            <!-- Remember me + Forgot -->
            <div class="login-footer-links">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" value="1"
                           <?= isset($_POST['remember']) ? 'checked' : '' ?>>
                    <span class="checkbox-custom"></span>
                    Remember me
                </label>
                <a href="<?= BASE_URL ?>/forgot-password">Forgot password?</a>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn btn-primary btn-full" style="margin-top:1.5rem">
                Sign in
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </button>
        </form>
    </div>

    <p class="login-footer-info">
        &copy; <?= date('Y') ?> <?= Utils::e(APP_NAME) ?> &mdash; All rights reserved.
    </p>
</div>

<script>
// Minimal inline JS for the login page (no external dependencies)
(function () {
    // Password show/hide toggle
    document.querySelectorAll('.input-toggle-pwd').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.target);
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
            btn.setAttribute('aria-label', input.type === 'password' ? 'Show password' : 'Hide password');
        });
    });

    // Client-side validation
    var form = document.getElementById('loginForm');
    form && form.addEventListener('submit', function (e) {
        var ok = true;
        var id = document.getElementById('identifier');
        var pw = document.getElementById('password');

        function setError(el, errId, msg) {
            el.classList.toggle('input-error', !!msg);
            var err = document.getElementById(errId);
            if (err) err.textContent = msg;
            if (msg) ok = false;
        }

        setError(id, 'err-identifier', id.value.trim() ? '' : 'Please enter your username or email.');
        setError(pw, 'err-password',   pw.value       ? '' : 'Please enter your password.');

        if (!ok) e.preventDefault();
    });
})();
</script>
</body>
</html>
