<?php
$pageTitle = 'My Profile';
require VIEWS_PATH . '/layouts/header.php';

$u         = $user ?? [];
$errors    = $errors ?? [];
$firstName = $u['first_name'] ?? '';
$lastName  = $u['last_name']  ?? '';
$fullName  = trim("$firstName $lastName") ?: ($u['username'] ?? '');
$initials  = strtoupper(substr($firstName ?: $u['username'], 0, 1) . substr($lastName, 0, 1));
$roleLabel = ['admin' => 'Administrator', 'manager' => 'Manager', 'staff' => 'Staff'][$u['role'] ?? ''] ?? ucfirst($u['role'] ?? '');
?>
<style>
.profile-grid{display:grid;grid-template-columns:1fr;gap:1.25rem}
@media(min-width:900px){.profile-grid{grid-template-columns:260px 1fr}}

.profile-card{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-sm)}
.profile-header-block{display:flex;flex-direction:column;align-items:center;padding:2rem 1.5rem 1.25rem;border-bottom:1px solid var(--border);text-align:center}
.profile-avatar{width:80px;height:80px;border-radius:50%;background:var(--accent);color:#fff;font-size:2rem;font-weight:700;display:flex;align-items:center;justify-content:center;margin-bottom:1rem;flex-shrink:0}
.profile-name{font-size:1.05rem;font-weight:700;margin:0 0 .2rem}
.profile-role{font-size:.78rem;color:var(--text-muted)}

.meta-list{list-style:none;padding:0;margin:0}
.meta-item{display:flex;align-items:center;gap:.65rem;padding:.65rem 1.25rem;border-bottom:1px solid var(--border);font-size:.83rem}
.meta-item:last-child{border-bottom:none}
.meta-item svg{width:15px;height:15px;stroke:var(--text-muted);flex-shrink:0}
.meta-label{color:var(--text-muted);font-size:.73rem;text-transform:uppercase;letter-spacing:.05em;display:block}
.meta-val{color:var(--text-primary);font-weight:500}

.tab-bar{display:flex;gap:.3rem;border-bottom:2px solid var(--border);margin-bottom:1.5rem}
.tab-btn{padding:.55rem 1.1rem;font-size:.85rem;font-weight:600;color:var(--text-muted);background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;transition:all var(--transition)}
.tab-btn.active,.tab-btn:hover{color:var(--accent);border-bottom-color:var(--accent)}
.tab-panel{display:none}.tab-panel.active{display:block}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">My Profile</h1>
        <p class="page-subtitle">Manage your account details and password</p>
    </div>
</div>

<?php if ($saved ?? false): ?>
<div class="alert alert-success" style="margin-bottom:1.25rem">Changes saved successfully.</div>
<?php endif; ?>

<div class="profile-grid">

    <!-- ── Left: identity card ─────────────────────────────────────────────── -->
    <div>
        <div class="profile-card">
            <div class="profile-header-block">
                <div class="profile-avatar"><?= Utils::e($initials) ?></div>
                <p class="profile-name"><?= Utils::e($fullName) ?></p>
                <p class="profile-role">
                    <span class="badge <?= $u['role'] === 'admin' ? 'badge-red' : ($u['role'] === 'manager' ? 'badge-blue' : 'badge-gray') ?>">
                        <?= Utils::e($roleLabel) ?>
                    </span>
                </p>
            </div>
            <ul class="meta-list">
                <li class="meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <div>
                        <span class="meta-label">Username</span>
                        <span class="meta-val"><?= Utils::e($u['username'] ?? '—') ?></span>
                    </div>
                </li>
                <li class="meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    <div>
                        <span class="meta-label">Email</span>
                        <span class="meta-val"><?= Utils::e($u['email'] ?? '—') ?></span>
                    </div>
                </li>
                <li class="meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <div>
                        <span class="meta-label">Last Login</span>
                        <span class="meta-val"><?= $u['last_login'] ? Utils::formatDateTime($u['last_login']) : 'This session' ?></span>
                    </div>
                </li>
                <li class="meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <div>
                        <span class="meta-label">Member Since</span>
                        <span class="meta-val"><?= $u['created_at'] ? Utils::formatDate($u['created_at']) : '—' ?></span>
                    </div>
                </li>
            </ul>
        </div>
    </div>

    <!-- ── Right: edit forms ───────────────────────────────────────────────── -->
    <div>
        <div class="tab-bar">
            <button class="tab-btn active" data-tab="info">Account Info</button>
            <button class="tab-btn"        data-tab="password">Change Password</button>
        </div>

        <!-- Account Info tab -->
        <div class="tab-panel active" id="tab-info">
            <form method="POST" action="<?= BASE_URL ?>/profile">
                <input type="hidden" name="csrf_token" value="<?= Utils::e(Auth::generateCSRFToken()) ?>">
                <input type="hidden" name="action" value="info">

                <?php if (!empty($u['staff_id'])): ?>
                <div class="form-grid-2" style="margin-bottom:1.25rem">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="firstName">First Name</label>
                        <input type="text" id="firstName" name="first_name" class="form-input"
                               value="<?= Utils::e($firstName) ?>" maxlength="80" placeholder="First name">
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="lastName">Last Name</label>
                        <input type="text" id="lastName" name="last_name" class="form-input"
                               value="<?= Utils::e($lastName) ?>" maxlength="80" placeholder="Last name">
                    </div>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email"
                           class="form-input <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                           value="<?= Utils::e($_POST['email'] ?? $u['email'] ?? '') ?>"
                           maxlength="200" placeholder="your@email.com">
                    <?php if (isset($errors['email'])): ?>
                    <div class="invalid-feedback"><?= Utils::e($errors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-input" value="<?= Utils::e($u['username'] ?? '') ?>"
                           disabled style="opacity:.6;cursor:not-allowed">
                    <div style="font-size:.74rem;color:var(--text-muted);margin-top:.3rem">Username cannot be changed. Contact an administrator if needed.</div>
                </div>

                <div style="margin-top:1.5rem">
                    <button type="submit" class="btn btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                            <polyline points="17 21 17 13 7 13 7 21"/>
                        </svg>Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Change Password tab -->
        <div class="tab-panel" id="tab-password">
            <form method="POST" action="<?= BASE_URL ?>/profile">
                <input type="hidden" name="csrf_token" value="<?= Utils::e(Auth::generateCSRFToken()) ?>">
                <input type="hidden" name="action" value="password">

                <div class="form-group">
                    <label class="form-label" for="currentPw">Current Password</label>
                    <input type="password" id="currentPw" name="current_password"
                           class="form-input <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>"
                           placeholder="Enter current password" autocomplete="current-password">
                    <?php if (isset($errors['current_password'])): ?>
                    <div class="invalid-feedback"><?= Utils::e($errors['current_password']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="newPw">New Password</label>
                    <input type="password" id="newPw" name="new_password"
                           class="form-input <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>"
                           placeholder="At least 8 characters" autocomplete="new-password">
                    <?php if (isset($errors['new_password'])): ?>
                    <div class="invalid-feedback"><?= Utils::e($errors['new_password']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" for="confirmPw">Confirm New Password</label>
                    <input type="password" id="confirmPw" name="confirm_password"
                           class="form-input <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                           placeholder="Repeat new password" autocomplete="new-password">
                    <?php if (isset($errors['confirm_password'])): ?>
                    <div class="invalid-feedback"><?= Utils::e($errors['confirm_password']) ?></div>
                    <?php endif; ?>
                </div>

                <div style="margin-top:1.5rem">
                    <button type="submit" class="btn btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
(function () {
    var tabs   = document.querySelectorAll('.tab-btn');
    var panels = document.querySelectorAll('.tab-panel');

    // If there are password errors, switch to password tab automatically
    <?php if (isset($errors['current_password']) || isset($errors['new_password']) || isset($errors['confirm_password'])): ?>
    switchTab('password');
    <?php endif; ?>

    tabs.forEach(function (btn) {
        btn.addEventListener('click', function () { switchTab(btn.dataset.tab); });
    });

    function switchTab(name) {
        tabs.forEach(function (b) { b.classList.toggle('active', b.dataset.tab === name); });
        panels.forEach(function (p) { p.classList.toggle('active', p.id === 'tab-' + name); });
    }
})();
</script>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
