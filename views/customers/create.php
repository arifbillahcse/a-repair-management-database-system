<?php
$pageTitle  = 'New Client';
require VIEWS_PATH . '/layouts/header.php';

// Pull any server-side validation errors / repopulated data
$e  = $formErrors ?? [];   // field-level error messages
$v  = $formData   ?? [];   // repopulate values on failure

// Helper: render a field error span
function fe(array $errors, string $field): string {
    return isset($errors[$field])
        ? '<div class="field-error" role="alert">' . Utils::e($errors[$field]) . '</div>'
        : '<div class="field-error"></div>';
}
// Helper: return 'input-error' class if there's an error for $field
function ec(array $errors, string $field): string {
    return isset($errors[$field]) ? ' input-error' : '';
}
// Helper: safe value from $v array
function fv(array $v, string $field, string $default = ''): string {
    return Utils::e($v[$field] ?? $default);
}
?>

<style>
.form-section{margin-bottom:1.5rem}
.form-section-title{font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);padding:.75rem 1.25rem;border-bottom:1px solid var(--border);margin:0}
.form-section-body{padding:1.25rem}
.form-hint{font-size:.75rem;color:var(--text-muted);margin-top:.25rem}
.required-note{font-size:.75rem;color:var(--text-muted);margin-bottom:1rem}
</style>

<!-- Page header -->
<div class="page-header">
    <div>
        <h1 class="page-title">New Customer</h1>
        <p class="page-subtitle">Add a new customer to the system</p>
    </div>
    <a href="<?= BASE_URL ?>/customers" class="btn btn-secondary">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
        </svg>Back to Clients
    </a>
</div>

<?php if (!empty($e)): ?>
<div class="alert alert-error" role="alert" style="margin-bottom:1rem">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
    </svg>
    Please fix the errors below before saving.
</div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>/customers" id="customerForm" data-validate novalidate>
    <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken) ?>">

    <p class="required-note"><span style="color:var(--error)">*</span> Required fields</p>

    <!-- ── Section 1: Personal / Company Info ───────────────────────────── -->
    <div class="card form-section">
        <h2 class="form-section-title">Personal / Company Information</h2>
        <div class="form-section-body">
            <div class="form-grid-2">

                <!-- Name (single field) -->
                <div class="form-group form-col-full">
                    <label for="full_name" class="form-label">
                        Name / Company Name <span class="required">*</span>
                    </label>
                    <input type="text" id="full_name" name="full_name"
                           class="form-input<?= ec($e,'full_name') ?>"
                           value="<?= fv($v,'full_name') ?>"
                           data-rules="required"
                           data-error-for="full_name"
                           autocomplete="name"
                           placeholder="Full name or company name"
                           required>
                    <?= fe($e,'full_name') ?>
                </div>

                <!-- Client type -->
                <div class="form-group">
                    <label for="client_type" class="form-label">Client Type</label>
                    <select id="client_type" name="client_type" class="form-select">
                        <?php foreach (CLIENT_TYPES as $val => $label): ?>
                        <option value="<?= $val ?>" <?= (($v['client_type'] ?? 'individual') === $val) ? 'selected' : '' ?>>
                            <?= Utils::e($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?= fe($e,'client_type') ?>
                </div>

                <!-- Customer since -->
                <div class="form-group">
                    <label for="customer_since" class="form-label">Customer Since</label>
                    <input type="date" id="customer_since" name="customer_since"
                           class="form-input<?= ec($e,'customer_since') ?>"
                           value="<?= fv($v,'customer_since') ?>"
                           max="<?= date('Y-m-d') ?>">
                    <?= fe($e,'customer_since') ?>
                </div>

            </div>
        </div>
    </div>

    <!-- ── Section 2: Address ────────────────────────────────────────────── -->
    <div class="card form-section">
        <h2 class="form-section-title">Address</h2>
        <div class="form-section-body">
            <div class="form-grid-2">

                <!-- Street address (full width) -->
                <div class="form-group form-col-full">
                    <label for="address" class="form-label">Street Address</label>
                    <input type="text" id="address" name="address"
                           class="form-input<?= ec($e,'address') ?>"
                           value="<?= fv($v,'address') ?>"
                           placeholder="Via Roma 42"
                           autocomplete="street-address">
                    <?= fe($e,'address') ?>
                </div>

                <!-- Postal code -->
                <div class="form-group">
                    <label for="postal_code" class="form-label">Postal Code (CAP)</label>
                    <input type="text" id="postal_code" name="postal_code"
                           class="form-input<?= ec($e,'postal_code') ?>"
                           value="<?= fv($v,'postal_code') ?>"
                           placeholder="20100"
                           maxlength="5"
                           inputmode="numeric"
                           autocomplete="postal-code">
                    <?= fe($e,'postal_code') ?>
                </div>

                <!-- City -->
                <div class="form-group">
                    <label for="city" class="form-label">
                        City <span class="required">*</span>
                    </label>
                    <input type="text" id="city" name="city"
                           class="form-input<?= ec($e,'city') ?>"
                           value="<?= fv($v,'city') ?>"
                           data-rules="required"
                           data-error-for="city"
                           placeholder="Milano"
                           autocomplete="address-level2"
                           required>
                    <?= fe($e,'city') ?>
                </div>

                <!-- Province -->
                <div class="form-group">
                    <label for="province" class="form-label">Province (Prov)</label>
                    <input type="text" id="province" name="province"
                           class="form-input<?= ec($e,'province') ?>"
                           value="<?= fv($v,'province') ?>"
                           placeholder="MI"
                           maxlength="5"
                           style="text-transform:uppercase"
                           autocomplete="address-level1">
                    <?= fe($e,'province') ?>
                </div>

            </div>
        </div>
    </div>

    <!-- ── Section 3: Contact Information ───────────────────────────────── -->
    <div class="card form-section">
        <h2 class="form-section-title">Contact Information</h2>
        <div class="form-section-body">
            <div class="form-grid-2">

                <!-- Mobile phone -->
                <div class="form-group">
                    <label for="phone_mobile" class="form-label">
                        Mobile Phone <span class="required">*</span>
                    </label>
                    <input type="tel" id="phone_mobile" name="phone_mobile"
                           class="form-input<?= ec($e,'phone_mobile') ?>"
                           value="<?= fv($v,'phone_mobile') ?>"
                           data-rules="required|phone"
                           data-error-for="phone_mobile"
                           placeholder="+39 333 1234567"
                           autocomplete="mobile tel"
                           required>
                    <?= fe($e,'phone_mobile') ?>
                </div>

                <!-- Landline -->
                <div class="form-group">
                    <label for="phone_landline" class="form-label">Landline Phone</label>
                    <input type="tel" id="phone_landline" name="phone_landline"
                           class="form-input<?= ec($e,'phone_landline') ?>"
                           value="<?= fv($v,'phone_landline') ?>"
                           data-rules="phone"
                           data-error-for="phone_landline"
                           placeholder="+39 02 1234567"
                           autocomplete="work tel">
                    <?= fe($e,'phone_landline') ?>
                </div>

                <!-- Email (full width) -->
                <div class="form-group form-col-full">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email"
                           class="form-input<?= ec($e,'email') ?>"
                           value="<?= fv($v,'email') ?>"
                           data-rules="email"
                           data-error-for="email"
                           placeholder="mario.rossi@email.com"
                           autocomplete="email">
                    <?= fe($e,'email') ?>
                </div>

            </div>
        </div>
    </div>

    <!-- ── Section 4: Business / Tax Information ─────────────────────────── -->
    <div class="card form-section">
        <h2 class="form-section-title">Business / Tax Information <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:.8rem;color:var(--text-muted)">(optional)</span></h2>
        <div class="form-section-body">
            <div class="form-grid-2">

                <!-- VAT number -->
                <div class="form-group">
                    <label for="vat_number" class="form-label">VAT Number (Partita IVA)</label>
                    <input type="text" id="vat_number" name="vat_number"
                           class="form-input<?= ec($e,'vat_number') ?>"
                           value="<?= fv($v,'vat_number') ?>"
                           placeholder="12345678901"
                           maxlength="13"
                           style="text-transform:uppercase">
                    <?= fe($e,'vat_number') ?>
                    <p class="form-hint">11 digits, optionally prefixed with IT</p>
                </div>

                <!-- Tax ID / Fiscal code -->
                <div class="form-group">
                    <label for="tax_id" class="form-label">Tax ID (Codice Fiscale)</label>
                    <input type="text" id="tax_id" name="tax_id"
                           class="form-input<?= ec($e,'tax_id') ?>"
                           value="<?= fv($v,'tax_id') ?>"
                           placeholder="RSSMRA85M01H501Z"
                           maxlength="16"
                           style="text-transform:uppercase">
                    <?= fe($e,'tax_id') ?>
                    <p class="form-hint">16 alphanumeric characters</p>
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
                <textarea id="notes" name="notes"
                          class="form-textarea<?= ec($e,'notes') ?>"
                          rows="4"
                          maxlength="2000"
                          placeholder="Any notes about this client (not visible to the client)…"><?= fv($v,'notes') ?></textarea>
                <?= fe($e,'notes') ?>
            </div>
        </div>
    </div>

    <!-- ── Actions ───────────────────────────────────────────────────────── -->
    <div class="form-actions" style="background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1rem 1.25rem">
        <a href="<?= BASE_URL ?>/customers" class="btn btn-secondary">Cancel</a>
        <div style="display:flex;gap:.5rem">
            <button type="submit" name="action" value="save_and_new" class="btn btn-secondary">
                Save &amp; Add Another
            </button>
            <button type="submit" name="action" value="save" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                    <polyline points="7 3 7 8 15 8"/>
                </svg>Save Client
            </button>
        </div>
    </div>

</form>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
