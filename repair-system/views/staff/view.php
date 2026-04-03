<?php
$pageTitle = Utils::e($staff['first_name'] . ' ' . $staff['last_name']) . ' — Staff Profile';
require VIEWS_PATH . '/layouts/header.php';

$roleColors = ['admin' => 'badge-red', 'manager' => 'badge-blue', 'technician' => 'badge-green', 'receptionist' => 'badge-gray'];
$initials   = strtoupper(substr($staff['first_name'], 0, 1) . substr($staff['last_name'], 0, 1));
$rs         = $repairStats ?? [];
?>
<style>
.profile-header{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem}
.profile-avatar{width:60px;height:60px;border-radius:50%;background:var(--accent-dim);color:var(--accent);font-size:1.4rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.profile-avatar.inactive{background:var(--bg-tertiary);color:var(--text-muted)}
.profile-grid{display:grid;grid-template-columns:1fr;gap:1.25rem}
@media(min-width:900px){.profile-grid{grid-template-columns:300px 1fr}}
.mini-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:1rem;margin-bottom:1.5rem}
.mini-stat{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);padding:.9rem 1.1rem}
.mini-stat-val{font-size:1.4rem;font-weight:700;line-height:1;margin-bottom:.3rem}
.mini-stat-lbl{font-size:.74rem;color:var(--text-secondary)}
.section-card{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden}
.section-header{display:flex;align-items:center;justify-content:space-between;padding:.75rem 1.25rem;border-bottom:1px solid var(--border)}
.section-title{font-size:.9rem;font-weight:600;margin:0}
.info-list{list-style:none;padding:0;margin:0}
.info-item{display:flex;gap:.75rem;padding:.6rem 1.25rem;border-bottom:1px solid var(--border)}
.info-item:last-child{border-bottom:none}
.info-icon{width:15px;height:15px;flex-shrink:0;margin-top:.15rem;stroke:var(--text-muted)}
.info-body .info-label{display:block;font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.1rem}
.info-body .info-value{font-size:.86rem;color:var(--text-primary)}
</style>

<!-- Header -->
<div class="profile-header">
    <div style="display:flex;gap:1rem;align-items:center">
        <div class="profile-avatar <?= !$staff['is_active'] ? 'inactive' : '' ?>"><?= $initials ?></div>
        <div>
            <h1 style="font-size:1.4rem;font-weight:700;margin:0 0 .35rem">
                <?= Utils::e($staff['first_name'] . ' ' . $staff['last_name']) ?>
            </h1>
            <div style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap">
                <span class="badge <?= $roleColors[$staff['role']] ?? 'badge-gray' ?>">
                    <?= Utils::e(ucfirst($staff['role'])) ?>
                </span>
                <?php if (!$staff['is_active']): ?>
                <span class="badge badge-gray">Inactive</span>
                <?php endif; ?>
                <?php if (!empty($userAccount['username'])): ?>
                <span style="font-size:.75rem;color:var(--text-muted);background:var(--bg-tertiary);border:1px solid var(--border);border-radius:var(--radius-full);padding:.15rem .5rem">
                    @<?= Utils::e($userAccount['username']) ?>
                </span>
                <?php endif; ?>
                <span style="font-size:.75rem;color:var(--text-muted);background:var(--bg-tertiary);border:1px solid var(--border);border-radius:var(--radius-full);padding:.15rem .5rem">
                    #<?= $staff['staff_id'] ?>
                </span>
            </div>
        </div>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <?php if (Auth::isAdmin()): ?>
        <a href="<?= BASE_URL ?>/staff/<?= $staff['staff_id'] ?>/edit" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>Edit
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/staff" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
            </svg>Back
        </a>
    </div>
</div>

<!-- Repair stats -->
<div class="mini-stats">
    <div class="mini-stat">
        <div class="mini-stat-val"><?= (int)($rs['total'] ?? 0) ?></div>
        <div class="mini-stat-lbl">Total Repairs</div>
    </div>
    <div class="mini-stat">
        <div class="mini-stat-val" style="color:var(--info,#67b3dd)"><?= (int)($rs['in_progress'] ?? 0) ?></div>
        <div class="mini-stat-lbl">In Progress</div>
    </div>
    <div class="mini-stat">
        <div class="mini-stat-val" style="color:var(--success)"><?= (int)($rs['completed'] ?? 0) + (int)($rs['collected'] ?? 0) ?></div>
        <div class="mini-stat-lbl">Completed</div>
    </div>
    <div class="mini-stat">
        <div class="mini-stat-val"><?= (int)($rs['this_month'] ?? 0) ?></div>
        <div class="mini-stat-lbl">This Month</div>
    </div>
    <div class="mini-stat">
        <div class="mini-stat-val"><?= $rs['avg_days'] ? $rs['avg_days'] . 'd' : '—' ?></div>
        <div class="mini-stat-lbl">Avg. Time</div>
    </div>
</div>

<div class="profile-grid">
    <!-- Left -->
    <div style="display:flex;flex-direction:column;gap:1.25rem">

        <!-- Contact info -->
        <div class="section-card">
            <div class="section-header"><h2 class="section-title">Contact</h2></div>
            <ul class="info-list">
                <?php if (!empty($staff['email'])): ?>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Email</span>
                        <span class="info-value"><a href="mailto:<?= Utils::e($staff['email']) ?>" style="color:inherit"><?= Utils::e($staff['email']) ?></a></span>
                    </div>
                </li>
                <?php endif; ?>
                <?php if (!empty($staff['phone'])): ?>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 11.87 19a19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Phone</span>
                        <span class="info-value"><a href="tel:<?= Utils::e($staff['phone']) ?>" style="color:inherit"><?= Utils::e($staff['phone']) ?></a></span>
                    </div>
                </li>
                <?php endif; ?>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Added</span>
                        <span class="info-value"><?= Utils::formatDate($staff['created_at'] ?? '') ?></span>
                    </div>
                </li>
            </ul>
        </div>

        <!-- User account -->
        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">Login Account</h2>
                <?php if (Auth::isAdmin() && empty($userAccount)): ?>
                <a href="<?= BASE_URL ?>/staff/<?= $staff['staff_id'] ?>/edit" class="btn btn-xs btn-secondary">Create Account</a>
                <?php endif; ?>
            </div>
            <?php if (empty($userAccount)): ?>
            <div class="empty-state" style="padding:1.25rem">No login account assigned.</div>
            <?php else: ?>
            <ul class="info-list">
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Username</span>
                        <span class="info-value" style="font-family:var(--font-mono)">@<?= Utils::e($userAccount['username']) ?></span>
                    </div>
                </li>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">System Role</span>
                        <span class="info-value" style="text-transform:capitalize"><?= Utils::e($userAccount['role']) ?></span>
                    </div>
                </li>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Last Login</span>
                        <span class="info-value">
                            <?= !empty($userAccount['last_login']) ? Utils::formatDateTime($userAccount['last_login']) : '<em style="color:var(--text-muted)">Never</em>' ?>
                        </span>
                    </div>
                </li>
                <li class="info-item">
                    <svg class="info-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <polyline points="22 11.08 12 2 2 11.08"/><line x1="12" y1="22" x2="12" y2="2"/>
                    </svg>
                    <div class="info-body">
                        <span class="info-label">Account Status</span>
                        <span class="info-value">
                            <?= $userAccount['is_active'] ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-gray">Disabled</span>' ?>
                        </span>
                    </div>
                </li>
            </ul>
            <?php endif; ?>
        </div>

        <?php if (!empty($staff['notes'])): ?>
        <div class="section-card">
            <div class="section-header"><h2 class="section-title">Notes</h2></div>
            <div style="padding:1rem 1.25rem;font-size:.86rem;color:var(--text-secondary);line-height:1.7;white-space:pre-wrap">
                <?= Utils::e($staff['notes']) ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Right: recent repairs -->
    <div>
        <div class="section-card">
            <div class="section-header">
                <h2 class="section-title">Recent Repairs</h2>
                <a href="<?= BASE_URL ?>/repairs?assigned_to=<?= $staff['staff_id'] ?>" class="btn btn-xs btn-secondary">All</a>
            </div>
            <?php
            $recentRepairs = Database::getInstance()->fetchAll(
                "SELECT r.repair_id, r.device_model, r.status, r.date_in,
                        c.full_name AS customer_name
                 FROM repairs r
                 LEFT JOIN customers c ON c.customer_id = r.customer_id
                 WHERE r.staff_id = ?
                 ORDER BY r.date_in DESC LIMIT 10",
                [$staff['staff_id']]
            );
            ?>
            <?php if (empty($recentRepairs)): ?>
            <div class="empty-state" style="padding:1.5rem">No repairs assigned yet.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Device</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentRepairs as $r): ?>
                    <tr>
                        <td>
                            <a href="<?= BASE_URL ?>/repairs/<?= $r['repair_id'] ?>"
                               style="font-weight:700;color:var(--text-primary);text-decoration:none"
                               onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text-primary)'">
                                #<?= $r['repair_id'] ?>
                            </a>
                        </td>
                        <td style="font-size:.83rem"><?= Utils::e($r['customer_name'] ?? '—') ?></td>
                        <td style="font-size:.83rem;max-width:160px"><?= Utils::e(Utils::truncate($r['device_model'] ?? '', 22)) ?></td>
                        <td>
                            <span class="badge <?= REPAIR_STATUS_CLASS[$r['status']] ?? 'badge-gray' ?>">
                                <?= Utils::e(REPAIR_STATUS[$r['status']] ?? $r['status']) ?>
                            </span>
                        </td>
                        <td style="font-size:.8rem;color:var(--text-secondary)"><?= Utils::formatDate($r['date_in']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
