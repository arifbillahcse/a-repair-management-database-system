<?php
$pageTitle = 'New Product';
require VIEWS_PATH . '/layouts/header.php';
?>
<div class="page-header">
    <div>
        <h1 class="page-title">New Product</h1>
        <p class="page-subtitle">Add a product or spare part to the catalog</p>
    </div>
    <a href="<?= BASE_URL ?>/products" class="btn btn-secondary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
        </svg>Back
    </a>
</div>

<form method="POST" action="<?= BASE_URL ?>/products" style="max-width:820px">
    <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken) ?>">

    <?php $isEdit = false; require VIEWS_PATH . '/products/_form.php'; ?>

    <div style="display:flex;gap:.75rem;margin-top:1.25rem">
        <button type="submit" class="btn btn-primary">Create Product</button>
        <a href="<?= BASE_URL ?>/products" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
