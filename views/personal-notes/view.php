<?php
$pageTitle = 'Note: ' . $note['title'];
require VIEWS_PATH . '/layouts/header.php';
$csrfToken = Auth::generateCSRFToken();
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= Utils::e($note['title']) ?></h1>
        <p class="page-subtitle">
            Created: <?= Utils::formatDate($note['created_at']) ?>
            <?php if ($note['updated_at'] !== $note['created_at']): ?>
            — Updated: <?= Utils::formatDate($note['updated_at']) ?>
            <?php endif; ?>
        </p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <a href="<?= BASE_URL ?>/personal-notes/<?= $note['note_id'] ?>/edit" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>Edit
        </a>
        <a href="<?= BASE_URL ?>/personal-notes" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
            </svg>Back
        </a>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:1.5rem;max-width:900px">
    <!-- Main content -->
    <div class="card">
        <div class="card-body" style="white-space:pre-wrap;line-height:1.8">
            <?= Utils::e($note['description']) ?>
        </div>
    </div>

    <!-- Sidebar -->
    <div style="display:flex;flex-direction:column;gap:1.5rem">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Status</h2></div>
            <div class="card-body">
                <div style="margin-bottom:1rem">
                    <span style="display:inline-block;padding:0.5rem 1rem;border-radius:var(--radius);<?= $note['is_completed'] ? 'background:#d1fae5;color:#065f46' : 'background:#fef3c7;color:#92400e' ?>;font-weight:700;font-size:0.875rem">
                        <?= $note['is_completed'] ? '✓ Completed' : 'Pending' ?>
                    </span>
                </div>
                <form method="POST" action="<?= BASE_URL ?>/personal-notes/<?= $note['note_id'] ?>/toggle" style="display:flex;gap:0.5rem">
                    <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken ?? '') ?>">
                    <button type="submit" class="btn btn-sm <?= $note['is_completed'] ? 'btn-secondary' : 'btn-primary' ?>" style="flex:1">
                        <?= $note['is_completed'] ? 'Reopen' : 'Complete' ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Actions</h2></div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>/personal-notes/<?= $note['note_id'] ?>/delete" onsubmit="return confirm('Delete this note permanently?')">
                    <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken ?? '') ?>">
                    <button type="submit" class="btn btn-sm btn-secondary" style="width:100%;background:var(--error-bg);color:var(--error);border-color:var(--error)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>
                        </svg>Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
