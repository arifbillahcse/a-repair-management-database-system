<?php
$pageTitle = 'Add Staff Member';
require VIEWS_PATH . '/layouts/header.php';

$fd  = $formData ?? [];
$err = $errors   ?? [];
$staffRoles = ['technician' => 'Technician', 'receptionist' => 'Receptionist', 'manager' => 'Manager', 'admin' => 'Admin'];
$userRoles  = ['technician' => 'Technician', 'staff' => 'Staff', 'manager' => 'Manager', 'admin' => 'Admin'];
?>
<style>
.form-grid-2{display:grid;grid-template-columns:1fr;gap:1rem}
@media(min-width:600px){.form-grid-2{grid-template-columns:1fr 1fr}}
.form-section-label{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin:0 0 .75rem;display:flex;align-items:center;gap:.5rem}
.form-section-label::after{content:'';flex:1;height:1px;background:var(--border)}
.pw-toggle-wrap{position:relative}
.pw-toggle-wrap input{padding-right:2.5rem}
.pw-toggle{position:absolute;right:.65rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);padding:0}
.pw-toggle:hover{color:var(--text-primary)}
.pw-toggle svg{width:16px;height:16px}
</style>

<div class="page-header">
    <h1 class="page-title">Add Staff Member</h1>
    <a href="<?= BASE_URL ?>/staff" class="btn btn-secondary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
        </svg>Back
    </a>
</div>

<form method="POST" action="<?= BASE_URL ?>/staff" data-validate="1" novalidate>
    <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken) ?>">

    <div style="display:grid;grid-template-columns:1fr;gap:1.5rem;max-width:760px">

        <!-- Personal info -->
        <div class="card">
            <div class="card-header"><h2 class="card-title">Personal Information</h2></div>
            <div class="card-body">
                <div class="form-grid-2" style="margin-bottom:1rem">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="firstName">First Name <span class="required">*</span></label>
                        <input type="text" id="firstName" name="first_name" class="form-input <?= isset($err['first_name'])?'is-invalid':'' ?>"
                               value="<?= Utils::e($fd['first_name'] ?? '') ?>" maxlength="80"
                               data-validate="required" required>
                        <?php if (isset($err['first_name'])): ?><div class="invalid-feedback"><?= Utils::e($err['first_name']) ?></div><?php endif; ?>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="lastName">Last Name <span class="required">*</span></label>
                        <input type="text" id="lastName" name="last_name" class="form-input <?= isset($err['last_name'])?'is-invalid':'' ?>"
                               value="<?= Utils::e($fd['last_name'] ?? '') ?>" maxlength="80"
                               data-validate="required" required>
                        <?php if (isset($err['last_name'])): ?><div class="invalid-feedback"><?= Utils::e($err['last_name']) ?></div><?php endif; ?>
                    </div>
                </div>
                <div class="form-grid-2" style="margin-bottom:1rem">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-input <?= isset($err['email'])?'is-invalid':'' ?>"
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
                        <label class="form-label" for="staffRole">Staff Role <span class="required">*</span></label>
                        <select id="staffRole" name="staff_role" class="form-select" required>
                            <?php foreach ($staffRoles as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= ($fd['staff_role'] ?? 'technician') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" for="notes">Notes</label>
                    <textarea id="notes" name="notes" class="form-input" rows="2" maxlength="500"><?= Utils::e($fd['notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Login account (optional) -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Login Account <span style="font-size:.72rem;color:var(--text-muted);font-weight:400">(optional)</span></h2>
            </div>
            <div class="card-body">
                <p style="font-size:.82rem;color:var(--text-secondary);margin-bottom:1rem">
                    Leave username and password blank to add without a system account. You can create one later.
                </p>
                <div class="form-grid-2" style="margin-bottom:1rem">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-input <?= isset($err['username'])?'is-invalid':'' ?>"
                               value="<?= Utils::e($fd['username'] ?? '') ?>" maxlength="60" autocomplete="off"
                               style="font-family:var(--font-mono)">
                        <?php if (isset($err['username'])): ?><div class="invalid-feedback"><?= Utils::e($err['username']) ?></div><?php endif; ?>
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
                            <button type="button" class="pw-toggle" data-target="password" aria-label="Toggle password visibility">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="passwordConfirm">Confirm Password</label>
                        <input type="password" id="passwordConfirm" name="password_confirm" class="form-input"
                               maxlength="100" autocomplete="new-password">
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div style="display:flex;gap:.75rem;justify-content:flex-end">
            <a href="<?= BASE_URL ?>/staff" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                    <polyline points="7 3 7 8 15 8"/>
                </svg>Save Staff Member
            </button>
        </div>
    </div>
</form>

<script>
// Password toggle visibility
document.querySelectorAll('.pw-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var inp = document.getElementById(btn.dataset.target);
        if (!inp) return;
        inp.type = inp.type === 'password' ? 'text' : 'password';
    });
});

// Confirm password match
document.querySelector('form').addEventListener('submit', function (e) {
    var pw  = document.getElementById('password').value;
    var pwc = document.getElementById('passwordConfirm').value;
    if (pw && pwc && pw !== pwc) {
        e.preventDefault();
        alert('Passwords do not match.');
        document.getElementById('passwordConfirm').focus();
    }
});
</script>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
