<?php
$pageTitle = 'Edit Packing List #' . $pl['pl_number'];
require VIEWS_PATH . '/layouts/header.php';

$formAction  = BASE_URL . '/packing-lists/' . $pl['pl_id'];
$submitLabel = 'Save Changes';
?>
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Packing List #<?= Utils::e($pl['pl_number']) ?></h1>
        <p class="page-subtitle">Documento di Trasporto (DDT)</p>
    </div>
    <a href="<?= BASE_URL ?>/packing-lists/<?= $pl['pl_id'] ?>" class="btn btn-secondary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
        </svg>Back
    </a>
</div>

<?php require VIEWS_PATH . '/packing-lists/_form.php'; ?>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
