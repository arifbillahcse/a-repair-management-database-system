<?php
$pageTitle = 'User Accounts';
require VIEWS_PATH . '/layouts/header.php';

$roleColors = ['admin' => 'badge-red', 'manager' => 'badge-blue', 'staff' => 'badge-gray', 'technician' => 'badge-green'];
$currentId  = Auth::id();
?>
<style>
.settings-nav{display:flex;gap:.35rem;flex-wrap:wrap;margin-bottom:1.5rem}
.snav-btn{padding:.4rem .9rem;border-radius:var(--radius-full);font-size:.82rem;font-weight:500;border:1px solid var(--border);background:none;color:var(--text-secondary);cursor:pointer;text-decoration:none;transition:all var(--transition)}
.snav-btn:hover{background:var(--bg-tertiary);color:var(--text-primary)}
.snav-btn.active{background:var(--accent-dim);border-color:var(--accent);color:var(--accent)}
.act-btns{display:flex;gap:.35rem;justify-content:flex-end;flex-wrap:wrap}
.il-form{display:inline;margin:0}
.pw-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:200;align-items:center;justify-content:center}
.pw-modal.open{display:flex}
.pw-modal-box{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.5rem;width:360px;max-width:90vw}
.pw-modal-title{font-size:1rem;font-weight:600;margin:0 0 1rem}
.pw-toggle-wrap{position:relative}
.pw-toggle-wrap input{padding-right:2.5rem}
.pw-toggle{position:absolute;right:.65rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);padding:0}
.pw-toggle svg{width:16px;height:16px}
.hide-mobile{display:none}
@media(min-width:700px){.hide-mobile{display:table-cell}}
.hide-t{display:none}
@media(min-width:1000px){.hide-t{display:table-cell}}
</style>

<div class="page-header">
    <h1 class="page-title">User Accounts</h1>
</div>

<!-- Settings nav -->
<div class="settings-nav">
    <a href="<?= BASE_URL ?>/admin/settings" class="snav-btn">Company</a>
    <a href="<?= BASE_URL ?>/admin/users"    class="snav-btn active">User Accounts</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:46px">#</th>
                    <th>Username</th>
                    <th class="hide-mobile">Full Name</th>
                    <th class="hide-mobile">Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th class="hide-t">Last Login</th>
                    <th style="text-align:right;width:180px">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="8" class="empty-state">No user accounts found.</td></tr>
            <?php else: ?>
                <?php foreach ($users as $u):
                    $isSelf    = (int)$u['user_id'] === (int)$currentId;
                    $fullName  = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
                ?>
                <tr style="<?= !$u['is_active'] ? 'opacity:.6' : '' ?>">
                    <td style="color:var(--text-muted);font-size:.78rem"><?= $u['user_id'] ?></td>
                    <td>
                        <span style="font-family:var(--font-mono);font-weight:600">@<?= Utils::e($u['username']) ?></span>
                        <?php if ($isSelf): ?>
                        <span style="font-size:.7rem;color:var(--accent);margin-left:.35rem">(you)</span>
                        <?php endif; ?>
                    </td>
                    <td class="hide-mobile" style="font-size:.85rem">
                        <?= $fullName ? Utils::e($fullName) : '<span style="color:var(--text-muted)">—</span>' ?>
                    </td>
                    <td class="hide-mobile" style="font-size:.82rem;color:var(--text-secondary)">
                        <?= !empty($u['email']) ? Utils::e($u['email']) : '<span style="color:var(--text-muted)">—</span>' ?>
                    </td>
                    <td>
                        <span class="badge <?= $roleColors[$u['role']] ?? 'badge-gray' ?>" style="text-transform:capitalize">
                            <?= Utils::e($u['role']) ?>
                        </span>
                    </td>
                    <td>
                        <?= $u['is_active']
                            ? '<span class="badge badge-green">Active</span>'
                            : '<span class="badge badge-gray">Disabled</span>' ?>
                    </td>
                    <td class="hide-t" style="font-size:.78rem;color:var(--text-secondary)">
                        <?= !empty($u['last_login']) ? Utils::formatDateTime($u['last_login']) : '<span style="color:var(--text-muted)">Never</span>' ?>
                    </td>
                    <td>
                        <div class="act-btns">
                            <!-- Reset password -->
                            <button type="button" class="btn btn-xs btn-secondary open-pw-modal"
                                    data-user-id="<?= $u['user_id'] ?>"
                                    data-username="<?= Utils::e($u['username']) ?>"
                                    title="Reset password">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px" aria-hidden="true">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>Password
                            </button>

                            <!-- Toggle active -->
                            <?php if (!$isSelf): ?>
                            <form method="POST" action="<?= BASE_URL ?>/admin/users/<?= $u['user_id'] ?>/toggle" class="il-form"
                                  data-confirm="<?= $u['is_active'] ? 'Disable' : 'Enable' ?> @<?= Utils::e(addslashes($u['username'])) ?>?">
                                <input type="hidden" name="csrf_token" value="<?= Utils::e(Auth::generateCSRFToken()) ?>">
                                <button type="submit" class="btn btn-xs <?= $u['is_active'] ? 'btn-secondary' : 'btn-primary' ?>">
                                    <?= $u['is_active'] ? 'Disable' : 'Enable' ?>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Password reset modal -->
<div class="pw-modal" id="pwModal" role="dialog" aria-modal="true" aria-labelledby="pwModalTitle">
    <div class="pw-modal-box">
        <h2 class="pw-modal-title" id="pwModalTitle">Reset Password — <span id="pwModalUser"></span></h2>
        <form method="POST" id="pwModalForm">
            <input type="hidden" name="csrf_token" value="<?= Utils::e(Auth::generateCSRFToken()) ?>">
            <div class="form-group">
                <label class="form-label" for="modalNewPw">New Password</label>
                <div class="pw-toggle-wrap">
                    <input type="password" id="modalNewPw" name="new_password" class="form-input"
                           minlength="8" required placeholder="Min. 8 characters" autocomplete="new-password">
                    <button type="button" class="pw-toggle" data-target="modalNewPw" aria-label="Toggle">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem">
                <button type="button" id="pwModalCancel" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Password</button>
            </div>
        </form>
    </div>
</div>

<script>
// Confirm toggle forms
document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('submit', function (e) {
        if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
});

// Password reset modal
var modal    = document.getElementById('pwModal');
var form     = document.getElementById('pwModalForm');
var userSpan = document.getElementById('pwModalUser');
var baseUrl  = '<?= BASE_URL ?>';

document.querySelectorAll('.open-pw-modal').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var uid  = btn.dataset.userId;
        var uname = btn.dataset.username;
        form.action = baseUrl + '/admin/users/' + uid + '/reset-password';
        userSpan.textContent = '@' + uname;
        document.getElementById('modalNewPw').value = '';
        modal.classList.add('open');
        document.getElementById('modalNewPw').focus();
    });
});

document.getElementById('pwModalCancel').addEventListener('click', function () {
    modal.classList.remove('open');
});

modal.addEventListener('click', function (e) {
    if (e.target === modal) modal.classList.remove('open');
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') modal.classList.remove('open');
});

// Password visibility toggle
document.querySelectorAll('.pw-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var inp = document.getElementById(btn.dataset.target);
        if (inp) inp.type = inp.type === 'password' ? 'text' : 'password';
    });
});

// hide-mobile / hide-t responsive helpers (already in CSS from style.css)
</script>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
