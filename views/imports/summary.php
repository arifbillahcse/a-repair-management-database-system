<?php
$pageTitle = 'Import Summary';
require VIEWS_PATH . '/layouts/header.php';
?>
<style>
.summary-container { max-width: 700px; }
.summary-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.summary-stat {
    padding: 1.25rem;
    background: var(--bg-secondary);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    border-left: 4px solid var(--border);
    text-align: center;
}
.summary-stat.success { border-left-color: var(--success); }
.summary-stat.warning { border-left-color: var(--warning); }
.summary-stat.danger  { border-left-color: var(--error); }
.summary-stat-value { font-size: 2rem; font-weight: 700; margin-bottom: .25rem; }
.summary-stat.success .summary-stat-value { color: var(--success); }
.summary-stat.warning .summary-stat-value { color: var(--warning); }
.summary-stat.danger .summary-stat-value  { color: var(--error); }
.summary-stat-label { font-size: .82rem; color: var(--text-secondary); }
.error-list { list-style: none; padding: 0; margin: 0; max-height: 300px; overflow-y: auto; }
.error-item { padding: .55rem 1rem; border-bottom: 1px solid var(--border); font-size: .82rem; color: var(--error); }
.error-item:last-child { border-bottom: none; }
</style>

<!-- Page header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Import Complete</h1>
        <p class="page-subtitle">Review the import results below</p>
    </div>
</div>

<div class="summary-container">
    <div class="summary-stats">
        <div class="summary-stat success">
            <div class="summary-stat-value"><?= number_format($result['success']) ?></div>
            <div class="summary-stat-label">Imported</div>
        </div>
        <?php if ($result['skipped'] > 0): ?>
        <div class="summary-stat warning">
            <div class="summary-stat-value"><?= number_format($result['skipped']) ?></div>
            <div class="summary-stat-label">Skipped</div>
        </div>
        <?php endif; ?>
        <?php if (!empty($result['errors'])): ?>
        <div class="summary-stat danger">
            <div class="summary-stat-value"><?= count($result['errors']) ?></div>
            <div class="summary-stat-label">Errors</div>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($result['success'] > 0): ?>
    <div class="card">
        <div class="card-header"><h2 class="card-title">Import Successful</h2></div>
        <div class="card-body">
            <p style="color:var(--text-secondary);margin:0">
                <strong><?= number_format($result['success']) ?></strong> records were imported successfully.
            </p>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($result['errors'])): ?>
    <div class="card card-danger">
        <div class="card-header"><h2 class="card-title">Rows with Issues</h2></div>
        <div class="card-body">
            <p style="color:var(--text-secondary);font-size:.88rem;margin-bottom:1rem">
                The following rows were skipped due to validation errors:
            </p>
            <ul class="error-list">
                <?php foreach ($result['errors'] as $error): ?>
                <li class="error-item"><?= Utils::e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <div style="display:flex;gap:1rem;justify-content:flex-end;margin-top:1rem">
        <a href="<?= BASE_URL ?>/import" class="btn btn-secondary">Import More</a>
        <a href="<?= BASE_URL ?>/" class="btn btn-primary">Go to Dashboard</a>
    </div>
</div>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
