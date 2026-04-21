<?php
$pageTitle = 'New Personal Note';
require VIEWS_PATH . '/layouts/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">New Personal Note</h1>
    </div>
    <a href="<?= BASE_URL ?>/personal-notes" class="btn btn-secondary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
        </svg>Back
    </a>
</div>

<?php if (!empty($errors)): ?>
<div class="flash flash-error" style="margin-bottom:1rem">
    <ul style="margin:0;padding-left:1.25rem">
        <?php foreach ($errors as $e): ?><li><?= Utils::e($e) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="card" style="max-width:600px">
    <form method="POST" action="<?= BASE_URL ?>/personal-notes">
        <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken) ?>">

        <div class="card-body" style="display:flex;flex-direction:column;gap:1.5rem">
            <div class="form-group">
                <label class="form-label" for="title">Title <span class="required">*</span></label>
                <input type="text" id="title" name="title" class="form-input <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                       value="<?= Utils::e($fd['title'] ?? '') ?>" maxlength="255" placeholder="Note title…" required autofocus>
                <?php if (isset($errors['title'])): ?><div class="invalid-feedback"><?= Utils::e($errors['title']) ?></div><?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description <span class="required">*</span></label>
                <textarea id="description" name="description" class="form-input <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                          rows="8" placeholder="Write your note…" required><?= Utils::e($fd['description'] ?? '') ?></textarea>
                <?php if (isset($errors['description'])): ?><div class="invalid-feedback"><?= Utils::e($errors['description']) ?></div><?php endif; ?>
            </div>
        </div>

        <div style="display:flex;gap:.75rem;justify-content:flex-end;padding:1rem">
            <a href="<?= BASE_URL ?>/personal-notes" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/>
                </svg>Create Note
            </button>
        </div>
    </form>
</div>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
