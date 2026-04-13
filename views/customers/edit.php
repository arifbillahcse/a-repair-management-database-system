<?php
$pageTitle = 'Edit Client — ' . Utils::e($customer['full_name']);
require VIEWS_PATH . '/layouts/header.php';

$e = $formErrors ?? [];
$v = $formData   ?? $customer;   // $formData on validation fail, else DB row

function efe(array $errors, string $field): string {
    return isset($errors[$field])
        ? '<div class="field-error" role="alert">' . Utils::e($errors[$field]) . '</div>'
        : '<div class="field-error"></div>';
}
function eec(array $errors, string $field): string {
    return isset($errors[$field]) ? ' input-error' : '';
}
function efv(array $v, string $field, string $default = ''): string {
    return Utils::e($v[$field] ?? $default);
}
?>

<style>
.form-section{margin-bottom:1.5rem}
.form-section-title{font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);padding:.75rem 1.25rem;border-bottom:1px solid var(--border);margin:0}
.form-section-body{padding:1.25rem}
.form-hint{font-size:.75rem;color:var(--text-muted);margin-top:.25rem}
.meta-chip{display:inline-flex;align-items:center;gap:.35rem;font-size:.75rem;color:var(--text-muted);background:var(--bg-tertiary);border:1px solid var(--border);border-radius:var(--radius-full);padding:.2rem .6rem}
</style>

<!-- Page header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Client</h1>
        <p class="page-subtitle" style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
            <span><?= Utils::e($customer['full_name']) ?></span>
            <span class="meta-chip">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:11px;height:11px">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
                Modified <?= Utils::timeAgo($customer['updated_at'] ?? $customer['created_at']) ?>
            </span>
            <span class="meta-chip">ID #<?= $customer['customer_id'] ?></span>
        </p>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <a href="<?= BASE_URL ?>/customers/<?= $customer['customer_id'] ?>" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>View Profile
        </a>
        <a href="<?= BASE_URL ?>/customers" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
            </svg>Back
        </a>
    </div>
</div>

<?php if (!empty($e)): ?>
<div class="alert alert-error" role="alert" style="margin-bottom:1rem">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
    </svg>
    Please fix the errors below before saving.
</div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>/customers/<?= $customer['customer_id'] ?>"
      id="customerForm" data-validate novalidate>
    <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken) ?>">

    <!-- ── Section 1: Personal / Company Info ───────────────────────────── -->
    <div class="card form-section">
        <h2 class="form-section-title">Personal / Company Information</h2>
        <div class="form-section-body">
            <div class="form-grid-2">

                <div class="form-group form-col-full">
                    <label for="full_name" class="form-label">
                        Name / Company Name <span class="required">*</span>
                    </label>
                    <input type="text" id="full_name" name="full_name"
                           class="form-input<?= eec($e,'full_name') ?>"
                           value="<?= efv($v,'full_name') ?>"
                           autocomplete="name"
                           data-rules="required" data-error-for="full_name" required>
                    <?= efe($e,'full_name') ?>
                </div>

                <div class="form-group">
                    <label for="client_type" class="form-label">Client Type</label>
                    <select id="client_type" name="client_type" class="form-select">
                        <?php foreach (CLIENT_TYPES as $val => $label): ?>
                        <option value="<?= $val ?>" <?= (($v['client_type'] ?? 'individual') === $val) ? 'selected' : '' ?>>
                            <?= Utils::e($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="active"   <?= ($v['status'] ?? 'active') === 'active'   ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($v['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="customer_since" class="form-label">Customer Since</label>
                    <input type="date" id="customer_since" name="customer_since"
                           class="form-input"
                           value="<?= efv($v,'customer_since') ?>"
                           max="<?= date('Y-m-d') ?>">
                </div>

            </div>
        </div>
    </div>

    <!-- ── Section 2: Address ────────────────────────────────────────────── -->
    <div class="card form-section">
        <h2 class="form-section-title">Address</h2>
        <div class="form-section-body">
            <div class="form-grid-2">

                <div class="form-group form-col-full">
                    <label for="address" class="form-label">Street Address</label>
                    <input type="text" id="address" name="address"
                           class="form-input<?= eec($e,'address') ?>"
                           value="<?= efv($v,'address') ?>"
                           autocomplete="street-address">
                    <?= efe($e,'address') ?>
                </div>

                <div class="form-group">
                    <label for="postal_code" class="form-label">Postal Code (CAP)</label>
                    <input type="text" id="postal_code" name="postal_code"
                           class="form-input<?= eec($e,'postal_code') ?>"
                           value="<?= efv($v,'postal_code') ?>"
                           maxlength="5" inputmode="numeric" autocomplete="postal-code">
                    <?= efe($e,'postal_code') ?>
                </div>

                <div class="form-group">
                    <label for="city" class="form-label">City <span class="required">*</span></label>
                    <input type="text" id="city" name="city"
                           class="form-input<?= eec($e,'city') ?>"
                           value="<?= efv($v,'city') ?>"
                           data-rules="required" data-error-for="city" required
                           autocomplete="address-level2">
                    <?= efe($e,'city') ?>
                </div>

                <div class="form-group">
                    <label for="province" class="form-label">Province</label>
                    <input type="text" id="province" name="province"
                           class="form-input"
                           value="<?= efv($v,'province') ?>"
                           maxlength="5" style="text-transform:uppercase">
                </div>

            </div>
        </div>
    </div>

    <!-- ── Section 3: Contact ────────────────────────────────────────────── -->
    <div class="card form-section">
        <h2 class="form-section-title">Contact Information</h2>
        <div class="form-section-body">
            <div class="form-grid-2">

                <div class="form-group">
                    <label for="phone_mobile" class="form-label">
                        Mobile Phone <span class="required">*</span>
                    </label>
                    <input type="tel" id="phone_mobile" name="phone_mobile"
                           class="form-input<?= eec($e,'phone_mobile') ?>"
                           value="<?= efv($v,'phone_mobile') ?>"
                           data-rules="required|phone" data-error-for="phone_mobile" required>
                    <?= efe($e,'phone_mobile') ?>
                </div>

                <div class="form-group">
                    <label for="phone_landline" class="form-label">Landline Phone</label>
                    <input type="tel" id="phone_landline" name="phone_landline"
                           class="form-input<?= eec($e,'phone_landline') ?>"
                           value="<?= efv($v,'phone_landline') ?>"
                           data-rules="phone" data-error-for="phone_landline">
                    <?= efe($e,'phone_landline') ?>
                </div>

                <div class="form-group form-col-full">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email"
                           class="form-input<?= eec($e,'email') ?>"
                           value="<?= efv($v,'email') ?>"
                           data-rules="email" data-error-for="email"
                           autocomplete="email">
                    <?= efe($e,'email') ?>
                </div>

            </div>
        </div>
    </div>

    <!-- ── Section 4: Business / Tax ─────────────────────────────────────── -->
    <div class="card form-section">
        <h2 class="form-section-title">Business / Tax Information</h2>
        <div class="form-section-body">
            <div class="form-grid-2">

                <div class="form-group">
                    <label for="vat_number" class="form-label">VAT Number (Partita IVA)</label>
                    <input type="text" id="vat_number" name="vat_number"
                           class="form-input<?= eec($e,'vat_number') ?>"
                           value="<?= efv($v,'vat_number') ?>"
                           maxlength="13" style="text-transform:uppercase">
                    <?= efe($e,'vat_number') ?>
                </div>

                <div class="form-group">
                    <label for="tax_id" class="form-label">Tax ID (Codice Fiscale)</label>
                    <input type="text" id="tax_id" name="tax_id"
                           class="form-input<?= eec($e,'tax_id') ?>"
                           value="<?= efv($v,'tax_id') ?>"
                           maxlength="16" style="text-transform:uppercase">
                    <?= efe($e,'tax_id') ?>
                </div>

            </div>
        </div>
    </div>

    <!-- ── Section 5: Notes ──────────────────────────────────────────────── -->
    <div class="card form-section" style="margin-bottom:1rem">
        <h2 class="form-section-title">Notes</h2>
        <div class="form-section-body">
            <div class="form-group" style="margin-bottom:0">
                <label for="notes" class="form-label">Internal Notes</label>
                <textarea id="notes" name="notes" class="form-textarea" rows="4"
                          maxlength="2000"><?= efv($v,'notes') ?></textarea>
            </div>
        </div>
    </div>

    <!-- ── Actions ───────────────────────────────────────────────────────── -->
    <div class="form-actions" style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1rem 1.25rem">
        <div style="display:flex;gap:.5rem">
            <a href="<?= BASE_URL ?>/customers/<?= $customer['customer_id'] ?>" class="btn btn-secondary">Cancel</a>
            <?php if (Auth::can('manager')): ?>
            <form method="POST"
                  action="<?= BASE_URL ?>/customers/<?= $customer['customer_id'] ?>/delete"
                  style="display:inline"
                  data-confirm="Deactivate this client?">
                <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken) ?>">
                <button type="submit" class="btn btn-danger">Deactivate</button>
            </form>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                <polyline points="17 21 17 13 7 13 7 21"/>
                <polyline points="7 3 7 8 15 8"/>
            </svg>Save Changes
        </button>
    </div>

</form>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
