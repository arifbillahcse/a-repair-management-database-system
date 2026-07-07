<?php
/**
 * Printable sale receipt — standalone page (no app layout).
 * Expects: $sale (with items), $business (default business or null)
 */
$custLabel = $sale['linked_customer_name'] ?: ($sale['customer_name'] ?: 'Walk-in customer');
$total     = (float)$sale['total_amount'];
$paid      = (float)$sale['amount_paid'];
$balance   = round($total - $paid, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receipt <?= Utils::e($sale['sale_number']) ?></title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Segoe UI', Arial, sans-serif; font-size:13px; color:#1a1a1a; background:#fff; padding:32px; max-width:800px; margin:0 auto; }
    .head { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #1a1a1a; padding-bottom:16px; margin-bottom:20px; }
    .biz-name { font-size:20px; font-weight:800; letter-spacing:.02em; }
    .biz-info { font-size:11.5px; color:#555; line-height:1.6; margin-top:4px; }
    .doc-type { font-size:22px; font-weight:800; text-align:right; }
    .doc-meta { font-size:12px; color:#555; text-align:right; margin-top:6px; line-height:1.7; }
    .doc-meta strong { color:#1a1a1a; font-family:monospace; font-size:13px; }
    .cust-block { margin-bottom:20px; }
    .cust-label { font-size:10.5px; text-transform:uppercase; letter-spacing:.08em; color:#888; margin-bottom:4px; }
    .cust-name { font-size:14px; font-weight:700; }
    table.items { width:100%; border-collapse:collapse; margin-bottom:16px; }
    table.items th { text-align:left; font-size:10.5px; text-transform:uppercase; letter-spacing:.06em; color:#666; border-bottom:1.5px solid #1a1a1a; padding:6px 8px; }
    table.items td { padding:8px; border-bottom:1px solid #ddd; vertical-align:top; }
    .r { text-align:right; }
    .c { text-align:center; }
    table.totals { margin-left:auto; width:280px; border-collapse:collapse; }
    table.totals td { padding:5px 8px; font-size:13px; }
    table.totals tr.grand td { border-top:2px solid #1a1a1a; font-weight:800; font-size:15px; padding-top:8px; }
    .t-label { color:#555; }
    .paid-row td { color:#0a7d38; }
    .bal-row td { font-weight:700; }
    .notes { margin-top:24px; font-size:12px; color:#555; border-top:1px solid #ddd; padding-top:12px; }
    .footer { margin-top:40px; text-align:center; font-size:11px; color:#999; }
    .status-stamp { display:inline-block; padding:4px 14px; border:2px solid; border-radius:4px; font-weight:800; font-size:13px; text-transform:uppercase; letter-spacing:.1em; }
    .stamp-paid { color:#0a7d38; border-color:#0a7d38; }
    .stamp-due  { color:#c0392b; border-color:#c0392b; }
    @media print {
        body { padding:0; }
        .no-print { display:none; }
    }
    .print-btn { position:fixed; top:16px; right:16px; padding:10px 22px; background:#1a56db; color:#fff; border:none; border-radius:6px; font-size:14px; font-weight:600; cursor:pointer; }
</style>
</head>
<body>

<button class="print-btn no-print" onclick="window.print()">🖨 Print</button>

<div class="head">
    <div>
        <div class="biz-name"><?= Utils::e($business['name'] ?? APP_NAME) ?></div>
        <div class="biz-info">
            <?php if (!empty($business['address'])): ?><?= nl2br(Utils::e($business['address'])) ?><br><?php endif; ?>
            <?php if (!empty($business['phone'])): ?>Tel: <?= Utils::e($business['phone']) ?><br><?php endif; ?>
            <?php if (!empty($business['email'])): ?><?= Utils::e($business['email']) ?><br><?php endif; ?>
            <?php if (!empty($business['vat_number'])): ?>P.IVA: <?= Utils::e($business['vat_number']) ?><?php endif; ?>
        </div>
    </div>
    <div>
        <div class="doc-type">SALE RECEIPT</div>
        <div class="doc-meta">
            N. <strong><?= Utils::e($sale['sale_number']) ?></strong><br>
            Date: <?= Utils::formatDate($sale['sale_date']) ?>
        </div>
    </div>
</div>

<div class="cust-block">
    <div class="cust-label">Sold To</div>
    <div class="cust-name"><?= Utils::e($custLabel) ?></div>
    <?php if (!empty($sale['customer_address'])): ?>
    <div style="font-size:12px;color:#555"><?= Utils::e($sale['customer_address']) ?></div>
    <?php endif; ?>
    <?php if (!empty($sale['customer_vat'])): ?>
    <div style="font-size:12px;color:#555">P.IVA: <?= Utils::e($sale['customer_vat']) ?></div>
    <?php endif; ?>
</div>

<table class="items">
    <thead>
        <tr>
            <th style="width:50%">Description</th>
            <th class="c">Qty</th>
            <th class="r">Unit Price</th>
            <th class="r">Disc.</th>
            <th class="r">Total</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($sale['items'] as $item): ?>
        <tr>
            <td><?= Utils::e($item['description']) ?></td>
            <td class="c"><?= rtrim(rtrim(number_format((float)$item['quantity'], 3), '0'), '.') ?></td>
            <td class="r"><?= Utils::formatCurrency($item['unit_price']) ?></td>
            <td class="r"><?= (float)$item['discount_pct'] > 0 ? rtrim(rtrim(number_format((float)$item['discount_pct'], 2), '0'), '.') . '%' : '—' ?></td>
            <td class="r"><?= Utils::formatCurrency($item['line_total']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<table class="totals">
    <tr><td class="t-label">Subtotal</td><td class="r"><?= Utils::formatCurrency($sale['subtotal']) ?></td></tr>
    <tr><td class="t-label">Tax (<?= rtrim(rtrim(number_format((float)$sale['tax_percentage'], 2), '0'), '.') ?>%)</td>
        <td class="r"><?= Utils::formatCurrency($sale['tax_amount']) ?></td></tr>
    <tr class="grand"><td>TOTAL</td><td class="r"><?= Utils::formatCurrency($total) ?></td></tr>
    <?php if ($paid > 0): ?>
    <tr class="paid-row"><td class="t-label">Paid</td><td class="r"><?= Utils::formatCurrency($paid) ?></td></tr>
    <?php endif; ?>
    <?php if ($balance > 0): ?>
    <tr class="bal-row"><td class="t-label">Balance Due</td><td class="r"><?= Utils::formatCurrency($balance) ?></td></tr>
    <?php endif; ?>
</table>

<div style="margin-top:24px">
    <?php if ($sale['status'] === 'paid'): ?>
    <span class="status-stamp stamp-paid">Paid</span>
    <?php elseif ($balance > 0): ?>
    <span class="status-stamp stamp-due">Balance Due</span>
    <?php endif; ?>
</div>

<?php if (!empty($sale['notes'])): ?>
<div class="notes"><?= nl2br(Utils::e($sale['notes'])) ?></div>
<?php endif; ?>

<div class="footer">
    Receipt <?= Utils::e($sale['sale_number']) ?> · Generated <?= date('d/m/Y H:i') ?>
</div>

</body>
</html>
