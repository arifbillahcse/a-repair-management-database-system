<?php
$pageTitle = 'Staff';
require VIEWS_PATH . '/layouts/header.php';

$roleColors = [
    'admin'       => 'badge-red',
    'manager'     => 'badge-blue',
    'technician'  => 'badge-green',
    'receptionist'=> 'badge-gray',
];

$byRole = [];
foreach ($staffList as $s) {
    $byRole[$s['role']][] = $s;
}
$roleOrder = ['admin', 'manager', 'technician', 'receptionist'];
?>
<style>
.staff-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem;margin-bottom:1.5rem}
.staff-card{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.25rem;display:flex;flex-direction:column;gap:.75rem;transition:border-color var(--transition)}
.staff-card:hover{border-color:var(--accent)}
.staff-card-top{display:flex;align-items:center;gap:.85rem}
.staff-avatar{width:44px;height:44px;border-radius:50%;background:var(--accent-dim);color:var(--accent);font-size:1.1rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.staff-avatar.inactive{background:var(--bg-tertiary);color:var(--text-muted)}
.staff-name{font-weight:600;font-size:.95rem;color:var(--text-primary);text-decoration:none;display:block}
.staff-name:hover{color:var(--accent)}
.staff-meta{font-size:.75rem;color:var(--text-secondary)}
.staff-info{display:flex;flex-direction:column;gap:.3rem;font-size:.82rem;color:var(--text-secondary)}
.staff-info svg{width:13px;height:13px;flex-shrink:0;stroke:var(--text-muted)}
.staff-info-row{display:flex;align-items:center;gap:.4rem}
.staff-info a{color:inherit;text-decoration:none}
.staff-info a:hover{color:var(--accent)}
.staff-actions{display:flex;gap:.4rem;margin-top:auto;padding-top:.5rem;border-top:1px solid var(--border)}
.section-label{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin:1.5rem 0 .75rem}
.section-label:first-child{margin-top:0}
.act-btn{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:var(--radius);background:none;border:1px solid transparent;color:var(--text-muted);cursor:pointer;text-decoration:none;transition:all var(--transition)}
.act-btn svg{width:14px;height:14px}
.act-btn:hover{background:var(--bg-tertiary);color:var(--text-primary);border-color:var(--border)}
.act-btn-d:hover{background:var(--error-bg);color:var(--error);border-color:var(--error)}
.il-form{display:inline;margin:0}
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Staff</h1>
        <p class="page-subtitle"><?= count($staffList) ?> members</p>
    </div>
    <?php if (Auth::isAdmin()): ?>
    <a href="<?= BASE_URL ?>/staff/create" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>Add Staff Member
    </a>
    <?php endif; ?>
</div>

<?php foreach ($roleOrder as $role):
    $members = $byRole[$role] ?? [];
    if (empty($members)) continue;
?>
<p class="section-label"><?= ucfirst($role) ?>s (<?= count($members) ?>)</p>
<div class="staff-grid">
    <?php foreach ($members as $s):
        $initials = strtoupper(substr($s['first_name'], 0, 1) . substr($s['last_name'], 0, 1));
        $isActive = (bool)$s['is_active'];
    ?>
    <div class="staff-card <?= !$isActive ? 'opacity-50' : '' ?>" style="<?= !$isActive ? 'opacity:.55' : '' ?>">
        <div class="staff-card-top">
            <div class="staff-avatar <?= !$isActive ? 'inactive' : '' ?>"><?= $initials ?></div>
            <div>
                <a href="<?= BASE_URL ?>/staff/<?= $s['staff_id'] ?>" class="staff-name">
                    <?= Utils::e($s['first_name'] . ' ' . $s['last_name']) ?>
                </a>
                <div class="staff-meta">
                    <span class="badge <?= $roleColors[$s['role']] ?? 'badge-gray' ?>" style="font-size:.68rem">
                        <?= Utils::e(ucfirst($s['role'])) ?>
                    </span>
                    <?php if (!$isActive): ?>
                    <span class="badge badge-gray" style="font-size:.68rem;margin-left:.25rem">Inactive</span>
                    <?php endif; ?>
                    <?php if (!empty($s['username'])): ?>
                    <span style="font-size:.7rem;color:var(--text-muted);margin-left:.35rem">
                        @<?= Utils::e($s['username']) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="staff-info">
            <?php if (!empty($s['email'])): ?>
            <div class="staff-info-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
                <a href="mailto:<?= Utils::e($s['email']) ?>"><?= Utils::e($s['email']) ?></a>
            </div>
            <?php endif; ?>
            <?php if (!empty($s['phone'])): ?>
            <div class="staff-info-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 11.87 19a19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
                <a href="tel:<?= Utils::e($s['phone']) ?>"><?= Utils::e($s['phone']) ?></a>
            </div>
            <?php endif; ?>
        </div>

        <?php if (Auth::isAdmin()): ?>
        <div class="staff-actions">
            <a href="<?= BASE_URL ?>/staff/<?= $s['staff_id'] ?>" class="act-btn" title="View">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </a>
            <a href="<?= BASE_URL ?>/staff/<?= $s['staff_id'] ?>/edit" class="act-btn" title="Edit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            </a>
            <?php if ($isActive): ?>
            <form method="POST" action="<?= BASE_URL ?>/staff/<?= $s['staff_id'] ?>/delete" class="il-form"
                  data-confirm="Deactivate <?= Utils::e(addslashes($s['first_name'] . ' ' . $s['last_name'])) ?>?">
                <input type="hidden" name="csrf_token" value="<?= Utils::e(Auth::generateCSRFToken()) ?>">
                <button type="submit" class="act-btn act-btn-d" title="Deactivate">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>

<script>
document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('submit', function (e) {
        if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
});
</script>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
