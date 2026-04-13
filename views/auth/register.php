<?php
$pageTitle = 'Create User Account';
require VIEWS_PATH . '/layouts/header.php';
$formErrors = $_SESSION['_form_errors'] ?? [];
$formData   = $_SESSION['_form_data']   ?? [];
unset($_SESSION['_form_errors'], $_SESSION['_form_data']);
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Create User Account</h1>
        <p class="page-subtitle">Add a new login account for a staff member</p>
    </div>
    <a href="<?= BASE_URL ?>/staff" class="btn btn-secondary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"/>
            <polyline points="12 19 5 12 12 5"/>
        </svg>
        Back to Staff
    </a>
</div>

<div class="card" style="max-width: 560px">
    <form method="POST" action="<?= BASE_URL ?>/register" novalidate id="registerForm">
        <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken) ?>">

        <div class="form-grid-2">
            <!-- Username -->
            <div class="form-group form-col-full">
                <label for="username" class="form-label">Username <span class="required">*</span></label>
                <input type="text" id="username" name="username" class="form-input <?= isset($formErrors['username']) ? 'input-error' : '' ?>"
                       value="<?= Utils::e($formData['username'] ?? '') ?>" required autocomplete="off">
                <?php if (isset($formErrors['username'])): ?>
                    <div class="field-error"><?= Utils::e($formErrors['username']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Email -->
            <div class="form-group form-col-full">
                <label for="email" class="form-label">Email <span class="required">*</span></label>
                <input type="email" id="email" name="email" class="form-input <?= isset($formErrors['email']) ? 'input-error' : '' ?>"
                       value="<?= Utils::e($formData['email'] ?? '') ?>" required>
                <?php if (isset($formErrors['email'])): ?>
                    <div class="field-error"><?= Utils::e($formErrors['email']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password" class="form-label">Password <span class="required">*</span></label>
                <input type="password" id="password" name="password"
                       class="form-input <?= isset($formErrors['password']) ? 'input-error' : '' ?>"
                       required autocomplete="new-password" minlength="8">
                <?php if (isset($formErrors['password'])): ?>
                    <div class="field-error"><?= Utils::e($formErrors['password']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label for="password_confirm" class="form-label">Confirm Password <span class="required">*</span></label>
                <input type="password" id="password_confirm" name="password_confirm"
                       class="form-input <?= isset($formErrors['password_confirm']) ? 'input-error' : '' ?>"
                       required autocomplete="new-password">
                <?php if (isset($formErrors['password_confirm'])): ?>
                    <div class="field-error"><?= Utils::e($formErrors['password_confirm']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Role -->
            <div class="form-group form-col-full">
                <label for="role" class="form-label">Role <span class="required">*</span></label>
                <select id="role" name="role" class="form-select <?= isset($formErrors['role']) ? 'input-error' : '' ?>" required>
                    <?php foreach (USER_ROLES as $value => $label): ?>
                    <option value="<?= $value ?>" <?= ($formData['role'] ?? 'technician') === $value ? 'selected' : '' ?>>
                        <?= Utils::e($label) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($formErrors['role'])): ?>
                    <div class="field-error"><?= Utils::e($formErrors['role']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?= BASE_URL ?>/staff" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Account</button>
        </div>
    </form>
</div>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
