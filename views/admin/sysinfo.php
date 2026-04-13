<?php
$pageTitle = 'System Information';
require VIEWS_PATH . '/layouts/header.php';
?>
<style>
.settings-nav{display:flex;gap:.35rem;flex-wrap:wrap;margin-bottom:1.5rem}
.snav-btn{padding:.4rem .9rem;border-radius:var(--radius-full);font-size:.82rem;font-weight:500;border:1px solid var(--border);background:none;color:var(--text-secondary);cursor:pointer;text-decoration:none;transition:all var(--transition)}
.snav-btn:hover{background:var(--bg-tertiary);color:var(--text-primary)}
.snav-btn.active{background:var(--accent-dim);border-color:var(--accent);color:var(--accent)}

.sysinfo-grid{display:grid;grid-template-columns:1fr;gap:1.25rem}
@media(min-width:900px){.sysinfo-grid{grid-template-columns:1fr 1fr}}

.info-table{width:100%;border-collapse:collapse}
.info-table tr{border-bottom:1px solid var(--border)}
.info-table tr:last-child{border-bottom:none}
.info-table td{padding:.6rem 1.25rem;font-size:.85rem;vertical-align:top}
.info-table td:first-child{color:var(--text-muted);width:46%;font-size:.8rem;text-transform:uppercase;letter-spacing:.04em;padding-right:.5rem}
.info-table td:last-child{color:var(--text-primary);font-family:var(--font-mono);word-break:break-all}

.status-ok  {color:var(--success)}
.status-warn{color:var(--warning)}
.status-bad {color:var(--error)}

.section-head{display:flex;align-items:center;gap:.6rem;padding:.75rem 1.25rem;border-bottom:1px solid var(--border)}
.section-icon{width:18px;height:18px;stroke:var(--accent);flex-shrink:0}
.section-title{font-size:.9rem;font-weight:600;margin:0}

.tbl-card{display:table;width:100%;border-collapse:collapse;font-size:.82rem}
.tbl-card thead td,.tbl-card thead th{padding:.5rem 1.25rem;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);background:var(--bg-tertiary);border-bottom:1px solid var(--border)}
.tbl-card tbody tr{border-bottom:1px solid var(--border)}
.tbl-card tbody tr:last-child{border-bottom:none}
.tbl-card tbody td{padding:.55rem 1.25rem;vertical-align:middle}
</style>

<div class="page-header">
    <h1 class="page-title">Settings</h1>
</div>

<!-- Settings nav -->
<div class="settings-nav">
    <a href="<?= BASE_URL ?>/admin/settings" class="snav-btn">Company</a>
    <a href="<?= BASE_URL ?>/admin/users"    class="snav-btn">User Accounts</a>
    <a href="<?= BASE_URL ?>/admin/sysinfo"  class="snav-btn active">System Information</a>
</div>

<div class="sysinfo-grid">

    <!-- ── PHP ──────────────────────────────────────────────────────────────── -->
    <div class="card" style="overflow:hidden">
        <div class="section-head">
            <svg class="section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>
            </svg>
            <h2 class="section-title">PHP Runtime</h2>
        </div>
        <table class="info-table">
            <tr>
                <td>PHP Version</td>
                <td>
                    <?= Utils::e($phpInfo['version']) ?>
                    <?php
                    $major = (int)PHP_MAJOR_VERSION;
                    $minor = (int)PHP_MINOR_VERSION;
                    if ($major >= 8 && $minor >= 2):
                    ?><span class="status-ok" style="font-family:var(--font-sans);margin-left:.4rem;font-size:.75rem">&#10003; Supported</span><?php
                    else:
                    ?><span class="status-warn" style="font-family:var(--font-sans);margin-left:.4rem;font-size:.75rem">&#9888; Upgrade recommended</span><?php
                    endif; ?>
                </td>
            </tr>
            <tr><td>Server API (SAPI)</td>         <td><?= Utils::e($phpInfo['sapi']) ?></td></tr>
            <tr><td>Operating System</td>           <td><?= Utils::e($phpInfo['os']) ?></td></tr>
            <tr><td>Max Execution Time</td>
                <td>
                    <?= Utils::e($phpInfo['max_execution_time']) ?>
                    <?php $t = (int)ini_get('max_execution_time'); ?>
                    <?php if ($t > 0 && $t < 30): ?><span class="status-warn" style="font-family:var(--font-sans);margin-left:.3rem;font-size:.75rem">&#9888; Low</span><?php endif; ?>
                </td>
            </tr>
            <tr><td>Memory Limit</td>               <td><?= Utils::e($phpInfo['memory_limit']) ?></td></tr>
            <tr><td>Upload Max Filesize</td>        <td><?= Utils::e($phpInfo['upload_max_size']) ?></td></tr>
            <tr><td>Post Max Size</td>              <td><?= Utils::e($phpInfo['post_max_size']) ?></td></tr>
            <tr><td>Max Input Vars</td>             <td><?= Utils::e($phpInfo['max_input_vars']) ?></td></tr>
            <tr><td>Display Errors</td>
                <td class="<?= $phpInfo['display_errors'] === 'On' ? 'status-warn' : 'status-ok' ?>">
                    <?= Utils::e($phpInfo['display_errors']) ?>
                </td>
            </tr>
            <tr><td>Default Timezone</td>           <td><?= Utils::e($phpInfo['timezone']) ?></td></tr>
            <tr><td>OPcache</td>
                <td class="<?= $phpInfo['opcache'] === 'Enabled' ? 'status-ok' : 'status-warn' ?>">
                    <?= Utils::e($phpInfo['opcache']) ?>
                </td>
            </tr>
            <tr><td>Loaded Extensions</td>          <td style="font-size:.78rem"><?= Utils::e($phpInfo['extensions']) ?></td></tr>
            <tr><td>PHP Binary</td>                 <td style="font-size:.78rem"><?= Utils::e($appInfo['php_path']) ?></td></tr>
        </table>
    </div>

    <!-- ── Database ─────────────────────────────────────────────────────────── -->
    <div class="card" style="overflow:hidden">
        <div class="section-head">
            <svg class="section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <ellipse cx="12" cy="5" rx="9" ry="3"/>
                <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
                <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
            </svg>
            <h2 class="section-title">Database</h2>
        </div>
        <?php if (isset($dbInfo['error'])): ?>
        <div style="padding:1rem 1.25rem;color:var(--error);font-size:.85rem">
            Connection error: <?= Utils::e($dbInfo['error']) ?>
        </div>
        <?php else: ?>
        <table class="info-table">
            <tr><td>MySQL Version</td>    <td><?= Utils::e($dbInfo['version']) ?></td></tr>
            <tr><td>Database Name</td>    <td><?= Utils::e($dbInfo['database']) ?></td></tr>
            <tr><td>Charset</td>          <td><?= Utils::e($dbInfo['charset']) ?></td></tr>
            <tr><td>Collation</td>        <td><?= Utils::e($dbInfo['collation']) ?></td></tr>
            <tr><td>Max Allowed Packet</td>
                <td><?= Utils::e(round((int)$dbInfo['max_packet'] / 1048576, 1) . ' MB') ?></td>
            </tr>
            <tr><td>Total DB Size</td>    <td><?= Utils::e($dbInfo['size_mb']) ?></td></tr>
        </table>
        <?php endif; ?>
    </div>

    <!-- ── Application ──────────────────────────────────────────────────────── -->
    <div class="card" style="overflow:hidden">
        <div class="section-head">
            <svg class="section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                <line x1="8" y1="21" x2="16" y2="21"/>
                <line x1="12" y1="17" x2="12" y2="21"/>
            </svg>
            <h2 class="section-title">Application</h2>
        </div>
        <table class="info-table">
            <tr><td>App Name</td>        <td><?= Utils::e($appInfo['name']) ?></td></tr>
            <tr><td>Version</td>         <td><?= Utils::e($appInfo['version']) ?></td></tr>
            <tr><td>Environment</td>
                <td class="<?= $appInfo['environment'] === 'production' ? 'status-ok' : 'status-warn' ?>">
                    <?= Utils::e($appInfo['environment']) ?>
                </td>
            </tr>
            <tr><td>Debug Mode</td>
                <td class="<?= $appInfo['debug'] === 'On' ? 'status-warn' : 'status-ok' ?>">
                    <?= Utils::e($appInfo['debug']) ?>
                    <?php if ($appInfo['debug'] === 'On'): ?>
                    <span style="font-family:var(--font-sans);font-size:.75rem"> — disable in production</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr><td>Base URL</td>        <td><?= Utils::e($appInfo['base_url']) ?></td></tr>
            <tr><td>Server Time</td>     <td><?= date('d/m/Y H:i:s') ?></td></tr>
            <tr><td>Session Timeout</td> <td><?= SESSION_TIMEOUT ?>s (<?= round(SESSION_TIMEOUT/60) ?> min)</td></tr>
        </table>
    </div>

    <!-- ── Disk & Paths ─────────────────────────────────────────────────────── -->
    <div class="card" style="overflow:hidden">
        <div class="section-head">
            <svg class="section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <h2 class="section-title">Disk &amp; Paths</h2>
        </div>
        <table class="info-table">
            <tr><td>Disk Free</td>       <td><?= Utils::e($diskInfo['disk_free']) ?></td></tr>
            <tr><td>Disk Total</td>      <td><?= Utils::e($diskInfo['disk_total']) ?></td></tr>
            <tr><td>Upload Path</td>     <td style="font-size:.78rem"><?= Utils::e($diskInfo['upload_path']) ?></td></tr>
            <tr><td>Upload Writable</td>
                <td class="<?= $diskInfo['upload_writable'] === 'Writable' ? 'status-ok' : 'status-bad' ?>">
                    <?= Utils::e($diskInfo['upload_writable']) ?>
                </td>
            </tr>
            <tr><td>App Root</td>        <td style="font-size:.78rem"><?= Utils::e(APP_ROOT) ?></td></tr>
        </table>
    </div>

</div>

<!-- ── Database Tables ───────────────────────────────────────────────────── -->
<?php if (!empty($tableRows)): ?>
<div class="card" style="margin-top:1.25rem;overflow:hidden">
    <div class="section-head">
        <svg class="section-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <rect x="3" y="3" width="18" height="18" rx="2"/>
            <line x1="3" y1="9" x2="21" y2="9"/>
            <line x1="3" y1="15" x2="21" y2="15"/>
            <line x1="9" y1="9" x2="9" y2="21"/>
        </svg>
        <h2 class="section-title">Database Tables</h2>
    </div>
    <div class="table-responsive">
        <table class="tbl-card">
            <thead>
                <tr>
                    <th style="text-align:left">Table</th>
                    <th style="text-align:right">Approx. Rows</th>
                    <th style="text-align:right">Size</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tableRows as $tbl): ?>
            <tr>
                <td style="font-family:var(--font-mono);font-size:.82rem"><?= Utils::e($tbl['table_name']) ?></td>
                <td style="text-align:right;color:var(--text-secondary)"><?= number_format((int)$tbl['table_rows']) ?></td>
                <td style="text-align:right;color:var(--text-secondary)"><?= $tbl['size_mb'] ?? '0' ?> MB</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div style="margin-top:1rem;text-align:right">
    <a href="<?= BASE_URL ?>/admin/sysinfo" class="btn btn-secondary" style="font-size:.8rem">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px" aria-hidden="true">
            <polyline points="23 4 23 10 17 10"/>
            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
        </svg>Refresh
    </a>
</div>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
