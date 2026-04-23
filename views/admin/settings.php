<?php
$pageTitle = 'Settings';
require VIEWS_PATH . '/layouts/header.php';
$c = $company ?? [];
?>
<style>
.settings-grid{display:grid;grid-template-columns:1fr;gap:1.5rem;max-width:820px}
.form-grid-2{display:grid;grid-template-columns:1fr;gap:1rem}
@media(min-width:600px){.form-grid-2{grid-template-columns:1fr 1fr}}
.settings-nav{display:flex;gap:.35rem;flex-wrap:wrap;margin-bottom:1.5rem}
.snav-btn{padding:.4rem .9rem;border-radius:var(--radius-full);font-size:.82rem;font-weight:500;border:1px solid var(--border);background:none;color:var(--text-secondary);cursor:pointer;text-decoration:none;transition:all var(--transition)}
.snav-btn:hover{background:var(--bg-tertiary);color:var(--text-primary)}
.snav-btn.active{background:var(--accent-dim);border-color:var(--accent);color:var(--accent)}
</style>

<div class="page-header">
    <h1 class="page-title">Settings</h1>
</div>

<!-- Settings nav -->
<div class="settings-nav">
    <a href="<?= BASE_URL ?>/admin/settings" class="snav-btn active">Company</a>
    <a href="<?= BASE_URL ?>/admin/users"    class="snav-btn">User Accounts</a>
    <a href="<?= BASE_URL ?>/admin/sysinfo"  class="snav-btn">System Information</a>
</div>

<form method="POST" action="<?= BASE_URL ?>/admin/settings">
    <input type="hidden" name="csrf_token" value="<?= Utils::e(Auth::generateCSRFToken()) ?>">

    <div class="settings-grid">

        <!-- Company identity -->
        <div class="card">
            <div class="card-header"><h2 class="card-title">Company Information</h2></div>
            <div class="card-body">
                <div class="form-group">
                    <label class="form-label" for="companyName">Company / Shop Name</label>
                    <input type="text" id="companyName" name="company_name" class="form-input"
                           value="<?= Utils::e($c['company_name'] ?? '') ?>" maxlength="200"
                           placeholder="<?= Utils::e(APP_NAME) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="companyAddress">Address</label>
                    <textarea id="companyAddress" name="company_address" class="form-input" rows="3"
                              placeholder="Street, City, Postcode"><?= Utils::e($c['company_address'] ?? '') ?></textarea>
                </div>
                <div class="form-grid-2" style="margin-bottom:1rem">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="companyPhone">Phone</label>
                        <input type="tel" id="companyPhone" name="company_phone" class="form-input"
                               value="<?= Utils::e($c['company_phone'] ?? '') ?>" maxlength="30">
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="companyEmail">Email</label>
                        <input type="email" id="companyEmail" name="company_email" class="form-input"
                               value="<?= Utils::e($c['company_email'] ?? '') ?>" maxlength="150">
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="vatNumber">VAT Number (P.IVA)</label>
                        <input type="text" id="vatNumber" name="vat_number" class="form-input"
                               value="<?= Utils::e($c['vat_number'] ?? '') ?>" maxlength="30"
                               style="font-family:var(--font-mono)" placeholder="IT01234567890">
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="taxId">Fiscal Code (C.F.)</label>
                        <input type="text" id="taxId" name="tax_id" class="form-input"
                               value="<?= Utils::e($c['tax_id'] ?? '') ?>" maxlength="20"
                               style="font-family:var(--font-mono)" placeholder="RSSMRA80A01H501Z">
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice settings -->
        <div class="card">
            <div class="card-header"><h2 class="card-title">Invoice Settings</h2></div>
            <div class="card-body">
                <div class="form-grid-2" style="margin-bottom:1rem">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="invoicePrefix">Invoice Number Prefix</label>
                        <input type="text" id="invoicePrefix" name="invoice_prefix" class="form-input"
                               value="<?= Utils::e($c['invoice_prefix'] ?? 'INV') ?>" maxlength="10"
                               style="font-family:var(--font-mono)" placeholder="INV">
                        <p style="font-size:.72rem;color:var(--text-muted);margin-top:.3rem">
                            Example: <strong><?= Utils::e($c['invoice_prefix'] ?? 'INV') ?>-<?= date('Y') ?>-00001</strong>
                        </p>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" for="taxPercentage">Default Tax Rate (%)</label>
                        <input type="number" id="taxPercentage" name="tax_percentage" class="form-input"
                               value="<?= Utils::e($c['tax_percentage'] ?? '22') ?>"
                               step="0.1" min="0" max="100">
                        <p style="font-size:.72rem;color:var(--text-muted);margin-top:.3rem">
                            Applied to new invoices. Italian standard VAT is 22%.
                        </p>
                    </div>
                </div>
                <div style="background:var(--bg-tertiary);border:1px solid var(--border);border-radius:var(--radius);padding:.6rem .85rem;font-size:.8rem;color:var(--text-secondary)">
                    <strong>Next invoice number:</strong>
                    <?= Utils::e($c['invoice_prefix'] ?? 'INV') ?>-<?= date('Y') ?>-<?= str_pad($c['invoice_next_number'] ?? 1, 5, '0', STR_PAD_LEFT) ?>
                    &nbsp;·&nbsp; Changing prefix takes effect on the next invoice.
                </div>
            </div>
        </div>

        <!-- Credit Note Signatures -->
        <div class="card">
            <div class="card-header"><h2 class="card-title">Authorized Signatures</h2></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:1.5rem">
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" for="sig1">Signature 1</label>
                    <textarea id="sig1" name="signature1" class="form-input" rows="2" placeholder="e.g. Anna Lisa Giannini, Malta Spare Parts Ltd."><?= Utils::e($c['signature1'] ?? '') ?></textarea>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" for="sig2">Signature 2</label>
                    <textarea id="sig2" name="signature2" class="form-input" rows="2" placeholder="e.g. Alessio Meo, Electroclean di Meo Alessio"><?= Utils::e($c['signature2'] ?? '') ?></textarea>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" for="sig3">Signature 3</label>
                    <textarea id="sig3" name="signature3" class="form-input" rows="2" placeholder="e.g. ТРАК ИЯ ИНВЕСТМЕНТ ЕООД, Tracia Investment Ltd"><?= Utils::e($c['signature3'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Save -->
        <div style="display:flex;gap:.75rem;justify-content:flex-end">
            <button type="submit" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                    <polyline points="7 3 7 8 15 8"/>
                </svg>Save Settings
            </button>
        </div>

    </div>
</form>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
