<?php
$pageTitle = 'Personal Notes';
require VIEWS_PATH . '/layouts/header.php';
$csrfToken = Auth::generateCSRFToken();
?>
<style>
.notes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
@media(max-width:768px) { .notes-grid { grid-template-columns: 1fr; } }
.note-card { border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; transition: all var(--transition); cursor: pointer; display: flex; flex-direction: column; }
.note-card:hover { border-color: var(--accent); box-shadow: 0 4px 12px rgba(0,0,0,.08); }
.note-card.completed { opacity: 0.7; }
.note-card.completed .note-title { text-decoration: line-through; color: var(--text-muted); }
.note-header { padding: 1rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; }
.note-title { font-size: 1rem; font-weight: 600; color: var(--text-primary); margin: 0; }
.note-badge { display: inline-block; font-size: 0.65rem; padding: 0.25rem 0.6rem; border-radius: 0.25rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
.note-badge.completed { background: #d1fae5; color: #065f46; }
.note-badge.pending { background: #fef3c7; color: #92400e; }
.note-body { padding: 1rem; flex: 1; }
.note-desc { font-size: 0.875rem; color: var(--text-secondary); line-height: 1.6; max-height: 100px; overflow: hidden; }
.note-footer { padding: 0.75rem 1rem; border-top: 1px solid var(--border); background: var(--bg-secondary); display: flex; gap: 0.5rem; font-size: 0.85rem; }
.note-footer a { color: var(--accent); text-decoration: none; font-weight: 600; }
.note-footer a:hover { text-decoration: underline; }
.note-time { color: var(--text-muted); margin-left: auto; }
.toolbar { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center; }
.filter-group { display: flex; gap: 0.5rem; }
.filter-btn { padding: 0.5rem 1rem; border: 1px solid var(--border); background: var(--bg-secondary); color: var(--text-primary); border-radius: var(--radius); cursor: pointer; font-size: 0.875rem; font-weight: 500; transition: all var(--transition); }
.filter-btn.active { background: var(--accent); color: #fff; border-color: var(--accent); }
.empty-notes { text-align: center; padding: 3rem 1rem; }
.empty-notes svg { width: 64px; height: 64px; color: var(--text-muted); margin-bottom: 1rem; opacity: 0.5; }
.empty-notes p { color: var(--text-muted); margin-bottom: 1.5rem; }
</style>

<div class="page-header">
    <div>
        <h1 class="page-title">Personal Notes</h1>
        <p class="page-subtitle">Keep track of your tasks and ideas</p>
    </div>
    <a href="<?= BASE_URL ?>/personal-notes/create" class="btn btn-primary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M12 5v14M5 12h14"/>
        </svg>New Note
    </a>
</div>

<!-- Filters -->
<div class="toolbar">
    <div class="filter-group">
        <a href="<?= BASE_URL ?>/personal-notes?status=all" class="filter-btn <?= $filters['status'] === 'all' ? 'active' : '' ?>">All</a>
        <a href="<?= BASE_URL ?>/personal-notes?status=pending" class="filter-btn <?= $filters['status'] === 'pending' ? 'active' : '' ?>">Pending</a>
        <a href="<?= BASE_URL ?>/personal-notes?status=completed" class="filter-btn <?= $filters['status'] === 'completed' ? 'active' : '' ?>">Completed</a>
    </div>
    <div style="flex: 1; display: flex; gap: 0.5rem; min-width: 250px;">
        <input type="text" placeholder="Search notes..." id="searchInput" class="form-input" style="flex:1" value="<?= Utils::e($filters['search']) ?>">
        <button onclick="document.location.href='<?= BASE_URL ?>/personal-notes?status=<?= Utils::e($filters['status']) ?>&search=' + encodeURIComponent(document.getElementById('searchInput').value)" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
        </button>
    </div>
</div>

<?php if (empty($notes)): ?>
<div class="empty-notes">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
    </svg>
    <p>No notes yet. <a href="<?= BASE_URL ?>/personal-notes/create" style="color:var(--accent);font-weight:600">Create one</a>.</p>
</div>
<?php else: ?>
<div class="notes-grid">
    <?php foreach ($notes as $note): ?>
    <div class="note-card <?= $note['is_completed'] ? 'completed' : '' ?>">
        <div class="note-header">
            <h3 class="note-title"><?= Utils::e($note['title']) ?></h3>
            <span class="note-badge <?= $note['is_completed'] ? 'completed' : 'pending' ?>">
                <?= $note['is_completed'] ? '✓ Done' : 'Pending' ?>
            </span>
        </div>
        <div class="note-body">
            <div class="note-desc"><?= Utils::e($note['description']) ?></div>
        </div>
        <div class="note-footer">
            <a href="<?= BASE_URL ?>/personal-notes/<?= $note['note_id'] ?>/edit">Edit</a>
            <a href="javascript:void(0)" onclick="toggleNote(<?= $note['note_id'] ?>)">
                <?= $note['is_completed'] ? 'Reopen' : 'Complete' ?>
            </a>
            <a href="javascript:void(0)" onclick="deleteNote(<?= $note['note_id'] ?>)" style="color:var(--error)">Delete</a>
            <span class="note-time"><?= Utils::formatDate($note['created_at']) ?></span>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($pagination['pages'] > 1): ?>
<div style="display:flex;gap:0.5rem;justify-content:center;margin-top:2rem">
    <?php for ($p = 1; $p <= $pagination['pages']; $p++): ?>
    <a href="<?= BASE_URL ?>/personal-notes?page=<?= $p ?>&status=<?= Utils::e($filters['status']) ?>&search=<?= urlencode($filters['search']) ?>" class="btn <?= $p === $pagination['page'] ? 'btn-primary' : 'btn-secondary' ?>">
        <?= $p ?>
    </a>
    <?php endfor; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<form id="toggleForm" method="POST" style="display:none">
    <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken ?? '') ?>">
</form>

<script>
function toggleNote(id) {
    if (confirm('Toggle completion status?')) {
        const form = document.getElementById('toggleForm');
        form.action = '<?= BASE_URL ?>/personal-notes/' + id + '/toggle';
        form.submit();
    }
}

function deleteNote(id) {
    if (confirm('Delete this note permanently?')) {
        const form = document.getElementById('toggleForm');
        form.action = '<?= BASE_URL ?>/personal-notes/' + id + '/delete';
        form.submit();
    }
}

document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        document.location.href='<?= BASE_URL ?>/personal-notes?status=<?= Utils::e($filters['status']) ?>&search=' + encodeURIComponent(this.value);
    }
});
</script>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
