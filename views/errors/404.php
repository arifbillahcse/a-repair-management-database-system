<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Not Found</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
<div class="error-page">
    <div class="error-code">404</div>
    <h1 class="error-title">Page Not Found</h1>
    <p class="error-message">The page you're looking for doesn't exist or has been moved.</p>
    <div class="error-actions">
        <a href="<?= BASE_URL ?>/" class="btn btn-primary">Go to Dashboard</a>
        <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
    </div>
</div>
</body>
</html>
