<?php
Auth::requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Summary — <?= Utils::e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <style>
        .summary-container { max-width: 700px; margin: 0 auto; }
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .summary-stat {
            padding: 1.25rem;
            background: var(--bg-tertiary);
            border-radius: var(--radius-lg);
            border-left: 4px solid var(--border);
            text-align: center;
        }
        .summary-stat.success { border-left-color: var(--success); }
        .summary-stat.warning { border-left-color: var(--warning); }
        .summary-stat.error { border-left-color: var(--error); }
        .summary-stat-value { font-size: 2rem; font-weight: 700; margin-bottom: .25rem; }
        .summary-stat-label { font-size: .82rem; color: var(--text-secondary); }
        .error-list {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 300px;
            overflow-y: auto;
        }
        .error-item {
            padding: .6rem 1rem;
            border-bottom: 1px solid var(--border);
            font-size: .82rem;
            color: var(--error);
        }
        .error-item:last-child { border-bottom: none; }
    </style>
</head>
<body>
<?php require VIEWS_PATH . '/layouts/header.php'; ?>
<?php require VIEWS_PATH . '/layouts/sidebar.php'; ?>

<div class="layout-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Import Complete</h1>
                <p class="page-subtitle">Review the results below</p>
            </div>
        </div>

        <div class="summary-container">
            <div class="summary-stats">
                <div class="summary-stat success">
                    <div class="summary-stat-value"><?= $result['success'] ?></div>
                    <div class="summary-stat-label">Imported</div>
                </div>
                <?php if ($result['skipped'] > 0): ?>
                <div class="summary-stat warning">
                    <div class="summary-stat-value"><?= $result['skipped'] ?></div>
                    <div class="summary-stat-label">Skipped</div>
                </div>
                <?php endif; ?>
                <?php if (!empty($result['errors'])): ?>
                <div class="summary-stat error">
                    <div class="summary-stat-value"><?= count($result['errors']) ?></div>
                    <div class="summary-stat-label">Errors</div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($result['success'] > 0): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">✓ Import Successful</h2>
                </div>
                <div class="card-body">
                    <p style="color: var(--text-secondary); margin: 0;">
                        <strong><?= $result['success'] ?></strong> records have been imported successfully.
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($result['errors'])): ?>
            <div class="card card-danger">
                <div class="card-header">
                    <h2 class="card-title">⚠ Issues Found</h2>
                </div>
                <div class="card-body">
                    <p style="color: var(--text-secondary); font-size: .9rem; margin-bottom: 1rem;">
                        The following rows could not be imported:
                    </p>
                    <ul class="error-list">
                        <?php foreach ($result['errors'] as $error): ?>
                        <li class="error-item"><?= Utils::e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <a href="<?= BASE_URL ?>/import" class="btn btn-secondary">
                    Import More Data
                </a>
                <a href="<?= BASE_URL ?>" class="btn btn-primary">
                    Go to Dashboard
                </a>
            </div>
        </div>
    </main>
</div>
</body>
</html>
