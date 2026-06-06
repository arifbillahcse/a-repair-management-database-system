<?php
$pageTitle = 'Export Database';
require VIEWS_PATH . '/layouts/header.php';
?>
<style>
.settings-nav{display:flex;gap:.35rem;flex-wrap:wrap;margin-bottom:1.5rem}
.snav-btn{padding:.4rem .9rem;border-radius:var(--radius-full);font-size:.82rem;font-weight:500;border:1px solid var(--border);background:none;color:var(--text-secondary);cursor:pointer;text-decoration:none;transition:all var(--transition)}
.snav-btn:hover{background:var(--bg-tertiary);color:var(--text-primary)}
.snav-btn.active{background:var(--accent-dim);border-color:var(--accent);color:var(--accent)}

.export-grid{display:grid;grid-template-columns:1fr;gap:1.25rem}
@media(min-width:960px){.export-grid{grid-template-columns:2fr 1fr}}

.info-row{display:flex;align-items:baseline;justify-content:space-between;padding:.6rem 1.25rem;border-bottom:1px solid var(--border);font-size:.85rem}
.info-row:last-child{border-bottom:none}
.info-label{color:var(--text-muted);font-size:.78rem;text-transform:uppercase;letter-spacing:.05em}
.info-val{font-family:var(--font-mono);color:var(--text-primary);font-size:.84rem}

.table-list{width:100%;border-collapse:collapse;font-size:.82rem}
.table-list th{padding:.5rem 1.1rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);border-bottom:1px solid var(--border);text-align:left;background:var(--bg-tertiary)}
.table-list td{padding:.55rem 1.1rem;border-bottom:1px solid var(--border);vertical-align:middle}
.table-list tbody tr:last-child td{border-bottom:none}
.table-list tbody tr:hover{background:var(--bg-tertiary)}

.export-box{background:linear-gradient(135deg,var(--accent-dim) 0%,var(--bg-secondary) 100%);border:1px solid var(--accent);border-radius:var(--radius-lg);padding:1.75rem 1.5rem;text-align:center}
.export-icon{width:56px;height:56px;background:var(--accent);border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:1rem}
.export-icon svg{width:26px;height:26px;stroke:#fff;stroke-width:2;fill:none}
.export-title{font-size:1rem;font-weight:700;margin:0 0 .35rem}
.export-sub{font-size:.8rem;color:var(--text-muted);margin:0 0 1.25rem;line-height:1.5}
.export-filename{font-family:var(--font-mono);font-size:.75rem;color:var(--text-secondary);background:var(--bg-primary);border:1px solid var(--border);border-radius:var(--radius);padding:.3rem .7rem;display:inline-block;margin-bottom:1.25rem;word-break:break-all}

.what-list{list-style:none;padding:0;margin:0;text-align:left}
.what-list li{display:flex;align-items:center;gap:.5rem;font-size:.8rem;color:var(--text-secondary);padding:.3rem 0}
.what-list li svg{width:14px;height:14px;stroke:var(--success);flex-shrink:0}
</style>

<div class="page-header">
    <h1 class="page-title">Settings</h1>
</div>

<!-- Settings nav -->
<div class="settings-nav">
    <a href="<?= BASE_URL ?>/admin/settings"  class="snav-btn">Company</a>
    <a href="<?= BASE_URL ?>/admin/users"     class="snav-btn">User Accounts</a>
    <a href="<?= BASE_URL ?>/admin/db-export" class="snav-btn active">Export Database</a>
    <a href="<?= BASE_URL ?>/admin/sysinfo"   class="snav-btn">System Information</a>
</div>

<div class="export-grid">

    <!-- ── Left: DB info + table list ──────────────────────────────────────── -->
    <div style="display:flex;flex-direction:column;gap:1.25rem">

        <!-- Database overview -->
        <div class="card" style="overflow:hidden">
            <div class="section-head" style="display:flex;align-items:center;gap:.6rem;padding:.75rem 1.25rem;border-bottom:1px solid var(--border)">
                <svg style="width:18px;height:18px;stroke:var(--accent);fill:none;stroke-width:2" viewBox="0 0 24 24">
                    <ellipse cx="12" cy="5" rx="9" ry="3"/>
                    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                </svg>
                <span style="font-size:.9rem;font-weight:600">Database Overview</span>
            </div>
            <div class="info-row"><span class="info-label">Database</span><span class="info-val"><?= Utils::e($dbName) ?></span></div>
            <div class="info-row"><span class="info-label">MySQL Version</span><span class="info-val"><?= Utils::e($version) ?></span></div>
            <div class="info-row"><span class="info-label">Character Set</span><span class="info-val"><?= Utils::e($charset) ?></span></div>
            <div class="info-row"><span class="info-label">Total Size</span><span class="info-val"><?= $sizeMb ?> MB</span></div>
            <div class="info-row"><span class="info-label">Tables</span><span class="info-val"><?= $tableCount ?></span></div>
        </div>

        <!-- Table list -->
        <div class="card" style="overflow:hidden">
            <div style="display:flex;align-items:center;gap:.6rem;padding:.75rem 1.25rem;border-bottom:1px solid var(--border)">
                <svg style="width:18px;height:18px;stroke:var(--accent);fill:none;stroke-width:2" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M3 9h18M3 15h18M9 3v18"/>
                </svg>
                <span style="font-size:.9rem;font-weight:600">Tables to Export</span>
                <span class="badge badge-gray" style="margin-left:auto"><?= $tableCount ?> tables</span>
            </div>
            <div class="table-responsive">
                <table class="table-list">
                    <thead>
                        <tr>
                            <th>Table Name</th>
                            <th style="text-align:right;width:90px">~Rows</th>
                            <th style="text-align:right;width:90px">Size</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($tableRows as $t): ?>
                    <tr>
                        <td style="font-family:var(--font-mono)">`<?= Utils::e($t['table_name']) ?>`</td>
                        <td style="text-align:right;color:var(--text-secondary)"><?= number_format((int)$t['table_rows']) ?></td>
                        <td style="text-align:right;color:var(--text-muted);font-size:.78rem"><?= $t['size_kb'] ?> KB</td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ── Right: Download panel ──────────────────────────────────────────── -->
    <div style="display:flex;flex-direction:column;gap:1.25rem">

        <div class="card" style="overflow:hidden">
            <div style="padding:.75rem 1.25rem;border-bottom:1px solid var(--border)">
                <span style="font-size:.9rem;font-weight:600">Download SQL Backup</span>
            </div>
            <div style="padding:1.5rem 1.25rem">
                <div class="export-box">
                    <div class="export-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="7 10 12 15 17 10"/>
                            <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                    </div>
                    <p class="export-title">Full SQL Dump</p>
                    <p class="export-sub">
                        Exports all <?= $tableCount ?> tables including structure and data.<br>
                        Total size on disk: ~<?= $sizeMb ?> MB
                    </p>
                    <div class="export-filename">
                        db_backup_<?= Utils::e($dbName) ?>_<?= date('Y-m-d') ?>_*.sql
                    </div>
                    <form method="POST" action="<?= BASE_URL ?>/admin/db-export">
                        <input type="hidden" name="csrf_token" value="<?= Utils::e(Auth::generateCSRFToken()) ?>">
                        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;gap:.5rem">
                            <svg style="width:15px;height:15px;stroke:currentColor;fill:none;stroke-width:2" viewBox="0 0 24 24">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Download Backup
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- What's included -->
        <div class="card" style="overflow:hidden">
            <div style="padding:.75rem 1.25rem;border-bottom:1px solid var(--border)">
                <span style="font-size:.9rem;font-weight:600">What's Included</span>
            </div>
            <div style="padding:1rem 1.25rem">
                <ul class="what-list">
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        DROP &amp; CREATE TABLE statements for every table
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        All row data as INSERT statements (batched)
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        UTF-8 / utf8mb4 character encoding
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Foreign key checks disabled during restore
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Compatible with MySQL &amp; MariaDB
                    </li>
                </ul>
            </div>
        </div>

        <!-- Restore tip -->
        <div style="background:var(--bg-secondary);border:1px solid var(--border);border-left:3px solid var(--accent);border-radius:var(--radius);padding:.9rem 1.1rem;font-size:.8rem;line-height:1.6;color:var(--text-secondary)">
            <strong style="color:var(--text-primary)">To restore:</strong> run
            <code style="font-size:.78rem;background:var(--bg-primary);padding:.1rem .35rem;border-radius:3px;color:var(--accent)">mysql -u user -p dbname &lt; backup.sql</code>
            or import via phpMyAdmin / DBeaver.
        </div>

    </div>

</div>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
