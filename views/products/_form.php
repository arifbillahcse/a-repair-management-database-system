<?php
/**
 * Shared product form fields.
 * Expects: $fd (form data), $errors, $categories, $isEdit (bool)
 */
$isEdit = $isEdit ?? false;
?>
<div class="card">
    <div class="card-header"><h2 class="card-title">Product Details</h2></div>
    <div class="card-body">

        <div style="display:grid;grid-template-columns:2fr 1fr;gap:1rem">
            <div class="form-group">
                <label class="form-label" for="pName">Product Name <span class="required">*</span></label>
                <input type="text" id="pName" name="name" class="form-input <?= isset($errors['name']) ? 'input-error' : '' ?>"
                       value="<?= Utils::e($fd['name'] ?? '') ?>" required maxlength="200" placeholder="e.g. Display iPhone 13 OEM">
                <?php if (isset($errors['name'])): ?><span class="form-error"><?= Utils::e($errors['name']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="pSku">SKU / Code</label>
                <input type="text" id="pSku" name="sku" class="form-input"
                       value="<?= Utils::e($fd['sku'] ?? '') ?>" maxlength="60" placeholder="optional">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem">
            <div class="form-group">
                <label class="form-label" for="pCat">Category</label>
                <select id="pCat" name="category_id" class="form-input">
                    <option value="">— None —</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int)$cat['category_id'] ?>"
                        <?= (int)($fd['category_id'] ?? 0) === (int)$cat['category_id'] ? 'selected' : '' ?>>
                        <?= Utils::e($cat['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="pPrice">Selling Price (€) <span class="required">*</span></label>
                <input type="number" id="pPrice" name="selling_price" step="0.01" min="0"
                       class="form-input <?= isset($errors['selling_price']) ? 'input-error' : '' ?>"
                       value="<?= Utils::e($fd['selling_price'] ?? '') ?>" required>
                <?php if (isset($errors['selling_price'])): ?><span class="form-error"><?= Utils::e($errors['selling_price']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label" for="pCost">Cost Price (€)</label>
                <input type="number" id="pCost" name="cost_price" step="0.01" min="0" class="form-input"
                       value="<?= Utils::e($fd['cost_price'] ?? '') ?>" placeholder="optional">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="pDesc">Description</label>
            <textarea id="pDesc" name="description" class="form-input" rows="3"
                      placeholder="Optional description…"><?= Utils::e($fd['description'] ?? '') ?></textarea>
        </div>

    </div>
</div>

<div class="card" style="margin-top:1.25rem">
    <div class="card-header"><h2 class="card-title">Stock</h2></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <?php if (!$isEdit): ?>
            <div class="form-group">
                <label class="form-label" for="pInitial">Opening Stock (units)</label>
                <input type="number" id="pInitial" name="initial_stock" step="1" min="0" class="form-input"
                       value="<?= Utils::e($fd['initial_stock'] ?? '0') ?>">
                <p style="font-size:.72rem;color:var(--text-muted);margin-top:.35rem">
                    Recorded as a "Stock Received" movement.
                </p>
            </div>
            <?php else: ?>
            <div class="form-group">
                <label class="form-label">Current Stock</label>
                <input type="text" class="form-input" value="<?= (int)($fd['quantity_on_hand'] ?? 0) ?> units" readonly
                       style="background:var(--bg-tertiary)">
                <p style="font-size:.72rem;color:var(--text-muted);margin-top:.35rem">
                    Adjust stock from the product page so every change is logged.
                </p>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label class="form-label" for="pThreshold">Low-Stock Alert Threshold</label>
                <input type="number" id="pThreshold" name="low_stock_threshold" step="1" min="0" class="form-input"
                       value="<?= Utils::e($fd['low_stock_threshold'] ?? '0') ?>">
                <p style="font-size:.72rem;color:var(--text-muted);margin-top:.35rem">
                    Alert when stock falls to this level. 0 = no alert.
                </p>
            </div>
        </div>

        <div class="form-group" style="margin-bottom:0">
            <label style="display:inline-flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.88rem">
                <input type="checkbox" name="is_active" value="1"
                    <?= !isset($fd['is_active']) || $fd['is_active'] ? 'checked' : '' ?>>
                Product is active (available for sale)
            </label>
        </div>
    </div>
</div>
