<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Access Denied</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
<div class="error-page">
    <div class="error-code" style="color: var(--error)">403</div>
    <h1 class="error-title">Access Denied</h1>
    <p class="error-message">You don't have permission to access this page.<br>
       Contact your administrator if you believe this is a mistake.</p>
    <div class="error-actions">
        <a href="<?= BASE_URL ?>/" class="btn btn-primary">Go to Dashboard</a>
        <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
    </div>
</div>
</body>
</html>
