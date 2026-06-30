<?php
$pageTitle = 'Businesses';
require VIEWS_PATH . '/layouts/header.php';

$list    = $businesses ?? [];
$editing = $editing    ?? null;
$isEdit  = !empty($editing);
?>
<style>
.settings-nav{display:flex;gap:.35rem;flex-wrap:wrap;margin-bottom:1.5rem}
.snav-btn{padding:.4rem .9rem;border-radius:var(--radius-full);font-size:.82rem;font-weight:500;border:1px solid var(--border);background:none;color:var(--text-secondary);cursor:pointer;text-decoration:none;transition:all var(--transition)}
.snav-btn:hover{background:var(--bg-tertiary);color:var(--text-primary)}
.snav-btn.active{background:var(--accent-dim);border-color:var(--accent);color:var(--accent)}

.biz-layout{display:grid;grid-template-columns:1fr;gap:1.5rem}
@media(min-width:980px){.biz-layout{grid-template-columns:1fr 380px}}
.form-grid-2{display:grid;grid-template-columns:1fr;gap:1rem}
@media(min-width:560px){.form-grid-2{grid-template-columns:1fr 1fr}}

.biz-card{background:var(--bg-secondary);border:1px solid var(--border);border-radius:var(--radius-lg);padding:1.1rem 1.25rem;margin-bottom:1rem}
.biz-card.is-default{border-color:var(--accent)}
.biz-card-head{display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:.5rem}
.biz-name{font-size:1rem;font-weight:700;margin:0;display:flex;align-items:center;gap:.5rem}
.biz-meta{font-size:.82rem;color:var(--text-secondary);line-height:1.6}
.biz-meta .muted{color:var(--text-muted)}
.biz-actions{display:flex;gap:.4rem;flex-shrink:0}
.empty-state{padding:2rem;text-align:center;color:var(--text-muted)}
</style>

<div class="page-header">
    <h1 class="page-title">Businesses</h1>
</div>

<!-- Settings nav -->
<div class="settings-nav">
    <a href="<?= BASE_URL ?>/admin/settings"   class="snav-btn">Company</a>
    <a href="<?= BASE_URL ?>/admin/businesses" class="snav-btn active">Businesses</a>
    <a href="<?= BASE_URL ?>/admin/sysinfo"    class="snav-btn">System Information</a>
</div>

<p style="font-size:.86rem;color:var(--text-secondary);margin-bottom:1.5rem;max-width:760px">
    Manage the businesses you issue documents from. When creating an invoice or credit note,
    you can choose which business it comes from — its name, address and VAT will appear on the printed document.
</p>

<div class="biz-layout">

    <!-- ── Left: list ──────────────────────────────────────────────────────── -->
    <div>
        <?php if (empty($list)): ?>
        <div class="biz-card">
            <div class="empty-state">No businesses yet. Add your first one using the form.</div>
        </div>
        <?php else: ?>
        <?php foreach ($list as $b): ?>
        <div class="biz-card <?= $b['is_default'] ? 'is-default' : '' ?>">
            <div class="biz-card-head">
                <h2 class="biz-name">
                    <?= Utils::e($b['name']) ?>
                    <?php if ($b['is_default']): ?>
                    <span class="badge badge-green" style="font-size:.66rem">Default</span>
                    <?php endif; ?>
                    <?php if (!$b['is_active']): ?>
                    <span class="badge badge-gray" style="font-size:.66rem">Inactive</span>
                    <?php endif; ?>
                </h2>
                <div class="biz-actions">
                    <a href="<?= BASE_URL ?>/admin/businesses?edit=<?= (int)$b['business_id'] ?>#bizForm" class="btn btn-xs btn-secondary">Edit</a>
                    <form method="POST" action="<?= BASE_URL ?>/admin/businesses/<?= (int)$b['business_id'] ?>/delete"
                          onsubmit="return confirm('Delete this business? Documents already issued keep their details.');" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken) ?>">
                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                    </form>
                </div>
            </div>
            <div class="biz-meta">
                <?php if (!empty($b['address'])): ?><?= nl2br(Utils::e($b['address'])) ?><br><?php endif; ?>
                <?php
                $line = [];
                if (!empty($b['phone'])) $line[] = 'Tel: ' . Utils::e($b['phone']);
                if (!empty($b['email'])) $line[] = Utils::e($b['email']);
                echo implode(' &nbsp;·&nbsp; ', $line);
                if ($line) echo '<br>';
                ?>
                <?php if (!empty($b['vat_number'])): ?><span class="muted">VAT:</span> <?= Utils::e($b['vat_number']) ?>&nbsp;&nbsp;<?php endif; ?>
                <?php if (!empty($b['tax_id'])): ?><span class="muted">Tax ID:</span> <?= Utils::e($b['tax_id']) ?><?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ── Right: add / edit form ──────────────────────────────────────────── -->
    <div>
        <div class="card" id="bizForm">
            <div class="card-header">
                <h2 class="card-title"><?= $isEdit ? 'Edit Business' : 'Add Business' ?></h2>
                <?php if ($isEdit): ?>
                <a href="<?= BASE_URL ?>/admin/businesses" class="btn btn-xs btn-secondary">+ New</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>/admin/businesses">
                    <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken) ?>">
                    <?php if ($isEdit): ?>
                    <input type="hidden" name="business_id" value="<?= (int)$editing['business_id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label" for="name">Business Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" class="form-input" required maxlength="200"
                               value="<?= Utils::e($editing['name'] ?? '') ?>" placeholder="Company / shop name">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="address">Address</label>
                        <textarea id="address" name="address" class="form-input" rows="2" maxlength="500"
                                  placeholder="Street, City, Country"><?= Utils::e($editing['address'] ?? '') ?></textarea>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label" for="phone">Phone</label>
                            <input type="text" id="phone" name="phone" class="form-input" maxlength="50"
                                   value="<?= Utils::e($editing['phone'] ?? '') ?>" placeholder="+39 ...">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input type="text" id="email" name="email" class="form-input" maxlength="150"
                                   value="<?= Utils::e($editing['email'] ?? '') ?>" placeholder="info@company.com">
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label" for="vat_number">VAT Number</label>
                            <input type="text" id="vat_number" name="vat_number" class="form-input" maxlength="50"
                                   value="<?= Utils::e($editing['vat_number'] ?? '') ?>" placeholder="e.g. IT04601010269">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="tax_id">Tax ID / Fiscal Code</label>
                            <input type="text" id="tax_id" name="tax_id" class="form-input" maxlength="50"
                                   value="<?= Utils::e($editing['tax_id'] ?? '') ?>" placeholder="(Optional)">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="bank_details">Bank / Payment Details</label>
                        <textarea id="bank_details" name="bank_details" class="form-input" rows="2" maxlength="1000"
                                  placeholder="IBAN, bank name, payment terms…"><?= Utils::e($editing['bank_details'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="signature">Signature Block</label>
                        <textarea id="signature" name="signature" class="form-input" rows="2" maxlength="300"
                                  placeholder="Name shown above the signature line on documents"><?= Utils::e($editing['signature'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group" style="display:flex;align-items:center;gap:.5rem">
                        <input type="checkbox" id="is_default" name="is_default" value="1"
                               <?= !empty($editing['is_default']) ? 'checked' : '' ?>>
                        <label for="is_default" style="margin:0;font-size:.85rem;cursor:pointer">Set as default business</label>
                    </div>

                    <div class="form-group" style="display:flex;align-items:center;gap:.5rem;margin-bottom:1.25rem">
                        <input type="checkbox" id="is_active" name="is_active" value="1"
                               <?= (!$isEdit || !empty($editing['is_active'])) ? 'checked' : '' ?>>
                        <label for="is_active" style="margin:0;font-size:.85rem;cursor:pointer">Active (available for selection)</label>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                        <?= $isEdit ? 'Save Changes' : 'Add Business' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

<?php require VIEWS_PATH . '/layouts/footer.php'; ?>
