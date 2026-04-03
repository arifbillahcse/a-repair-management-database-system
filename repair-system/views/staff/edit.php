<?php
$pageTitle  = 'Edit — ' . Utils::e($staff['first_name'] . ' ' . $staff['last_name']);
require VIEWS_PATH . '/layouts/header.php';

// Use session flash data on validation failure, otherwise use DB row
$fd  = $formData ?: $staff;
$err = $errors   ?? [];

$staffRoles = ['technician' => 'Technician', 'receptionist' => 'Receptionist', 'manager' => 'Manager', 'admin' => 'Admin'];
$userRoles  = ['technician' => 'Technician', 'staff' => 'Staff', 'manager' => 'Manager', 'admin' => 'Admin'];
?>
<style>
.form-grid-2{display:grid;grid-template-columns:1fr;gap:1rem}
@media(min-width:600px){.form-grid-2{grid-template-columns:1fr 1fr}}
.pw-toggle-wrap{position:relative}
.pw-toggle-wrap input{padding-right:2.5rem}
.pw-toggle{position:absolute;right:.65rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);padding:0}
.pw-toggle:hover{color:var(--text-primary)}
.pw-toggle svg{width:16px;height:16px}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Edit Staff Member</h1>
        <p class="page-subtitle"><?= Utils::e($staff['first_name'] . ' ' . $staff['last_name']) ?></p>
    </div>
    <div style="display:flex;gap:.5rem">
        <a href="<?= BASE_URL ?>/staff/<?= $staff['staff_id'] ?>" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
            </svg>Back
        </a>
    </div>
</div>

<form method="POST" action="<?= BASE_URL ?>/staff/<?= $staff['staff_id'] ?>" style="max-width:760px" data-validate="1" novalidate>
    <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken) ?>">

    <div style="display:flex;flex-direction:column;gap:1.5rem">

        <!-- Personal info -->
        <div class="card">
            <div class="card-header"><h2 class="card-title">Personal Information</h2></div>
            <div class="card-body">
                <div class="form-grid-2" style="margin-bottom:1rem">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="firstName">First Name <span class="required">*</span></label>
                        <input type="text" id="firstName" name="first_name"
                               class="form-input <?= isset($err['first_name'])?'is-invalid':'' ?>"
                               value="<?= Utils::e($fd['first_name'] ?? '') ?>" maxlength="80" required>
                        <?php if (isset($err['first_name'])): ?><div class="invalid-feedback"><?= Utils::e($err['first_name']) ?></div><?php endif; ?>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="lastName">Last Name <span class="required">*</span></label>
                        <input type="text" id="lastName" name="last_name"
                               class="form-input <?= isset($err['last_name'])?'is-invalid':'' ?>"
                               value="<?= Utils::e($fd['last_name'] ?? '') ?>" maxlength="80" required>
                        <?php if (isset($err['last_name'])): ?><div class="invalid-feedback"><?= Utils::e($err['last_name']) ?></div><?php endif; ?>
                    </div>
                </div>
                <div class="form-grid-2" style="margin-bottom:1rem">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email"
                               class="form-input <?= isset($err['email'])?'is-invalid':'' ?>"
                               value="<?= Utils::e($fd['email'] ?? '') ?>" maxlength="150">
                        <?php if (isset($err['email'])): ?><div class="invalid-feedback"><?= Utils::e($err['email']) ?></div><?php endif; ?>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" class="form-input"
                               value="<?= Utils::e($fd['phone'] ?? '') ?>" maxlength="30">
                    </div>
                </div>
                <div class="form-grid-2" style="margin-bottom:1rem">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="staffRole">Staff Role</label>
                        <select id="staffRole" name="staff_role" class="form-select">
                            <?php foreach ($staffRoles as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= ($fd['staff_role'] ?? $staff['role']) === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Status</label>
                        <label style="display:flex;align-items:center;gap:.5rem;margin-top:.5rem;cursor:pointer">
                            <input type="checkbox" name="is_active" value="1"
                                   <?= ($fd['is_active'] ?? $staff['is_active']) ? 'checked' : '' ?>
                                   style="accent-color:var(--accent);width:16px;height:16px">
                            <span style="font-size:.86rem">Active (can log in and be assigned repairs)</span>
                        </label>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" for="notes">Notes</label>
                    <textarea id="notes" name="notes" class="form-input" rows="2" maxlength="500"><?= Utils::e($fd['notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Login account -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Login Account</h2>
                <?php if ($userAccount): ?>
                <span class="badge badge-green" style="font-size:.7rem">Linked</span>
                <?php else: ?>
                <span class="badge badge-gray" style="font-size:.7rem">None</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($userAccount): ?>
                <div style="background:var(--bg-tertiary);border:1px solid var(--border);border-radius:var(--radius);padding:.6rem .9rem;margin-bottom:1rem;font-size:.84rem;color:var(--text-secondary)">
                    Linked as <strong style="color:var(--text-primary);font-family:var(--font-mono)">@<?= Utils::e($userAccount['username']) ?></strong>
                    &nbsp;·&nbsp; Last login:
                    <?= !empty($userAccount['last_login']) ? Utils::formatDateTime($userAccount['last_login']) : 'Never' ?>
                </div>
                <div class="form-grid-2" style="margin-bottom:1rem">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="userRole">System Role</label>
                        <select id="userRole" name="user_role" class="form-select">
                            <?php foreach ($userRoles as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= ($userAccount['role'] ?? 'technician') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <p style="font-size:.78rem;color:var(--text-secondary);margin-bottom:.6rem">
                    Leave password blank to keep the current password.
                </p>
                <div class="form-grid-2">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="newPassword">New Password</label>
                        <div class="pw-toggle-wrap">
                            <input type="password" id="newPassword" name="new_password"
                                   class="form-input <?= isset($err['new_password'])?'is-invalid':'' ?>"
                                   maxlength="100" autocomplete="new-password" placeholder="Leave blank to keep current">
                            <button type="button" class="pw-toggle" data-target="newPassword" aria-label="Toggle">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <?php if (isset($err['new_password'])): ?><div class="invalid-feedback"><?= Utils::e($err['new_password']) ?></div><?php endif; ?>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="newPasswordConfirm">Confirm New Password</label>
                        <input type="password" id="newPasswordConfirm" class="form-input"
                               maxlength="100" autocomplete="new-password">
                    </div>
                </div>

                <?php else: ?>
                <p style="font-size:.82rem;color:var(--text-secondary);margin-bottom:1rem">
                    No login account linked. Fill in credentials to create one now.
                </p>
                <div class="form-grid-2" style="margin-bottom:1rem">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-input"
                               value="<?= Utils::e($fd['username'] ?? '') ?>" maxlength="60" autocomplete="off"
                               style="font-family:var(--font-mono)">
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="userRole">System Role</label>
                        <select id="userRole" name="user_role" class="form-select">
                            <?php foreach ($userRoles as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= ($fd['user_role'] ?? 'technician') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="password">Password</label>
                        <div class="pw-toggle-wrap">
                            <input type="password" id="password" name="password" class="form-input"
                                   maxlength="100" autocomplete="new-password" placeholder="Min. 8 characters">
                            <button type="button" class="pw-toggle" data-target="password" aria-label="Toggle">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="passwordConfirm">Confirm Password</label>
                        <input type="password" id="passwordConfirm" class="form-input"
                               maxlength="100" autocomplete="new-password">
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Danger zone -->
        <?php if ($staff['is_active']): ?>
        <div class="card" style="border-color:var(--error-bg)">
            <div class="card-header" style="background:var(--error-bg)">
                <h2 class="card-title" style="color:var(--error)">Danger Zone</h2>
            </div>
            <div style="padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
                <p style="font-size:.82rem;color:var(--text-secondary);margin:0">
                    Deactivate this staff member and their login account. They will no longer be able to log in.
                </p>
                <form method="POST" action="<?= BASE_URL ?>/staff/<?= $staff['staff_id'] ?>/delete"
                      data-confirm="Deactivate <?= Utils::e(addslashes($staff['first_name'] . ' ' . $staff['last_name'])) ?>?">
                    <input type="hidden" name="csrf_token" value="<?= Utils::e(Auth::generateCSRFToken()) ?>">
                    <button type="submit" class="btn btn-danger">Deactivate</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div style="display:flex;gap:.75rem;justify-content:flex-end">
            <a href="<?= BASE_URL ?>/staff/<?= $staff['staff_id'] ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                    <polyline points="7 3 7 8 15 8"/>
                </svg>Save Changes
            </button>
        </div>

    </div>
</form>

<script>
document.querySelectorAll('.pw-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var inp = document.getElementById(btn.dataset.target);
        if (!inp) return;
        inp.type = inp.type === 'password' ? 'text' : 'password';
    });
});

document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('submit', function (e) {
        if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
});

// Confirm new password match before submit
document.querySelector('form[method=POST][action*="/staff/"]').addEventListener('submit', function (e) {
    var pw  = document.getElementById('newPassword')        || document.getElementById('password');
    var pwc = document.getElementById('newPasswordConfirm') || document.getElementById('passwordConfirm');
    if (pw && pwc && pw.value && pwc.value && pw.value !== pwc.value) {
        e.preventDefault();
        alert('Passwords do not match.');
        pwc.focus();
    }
});
</script>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
