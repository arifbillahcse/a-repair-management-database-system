<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= Utils::e($invoice['invoice_number']) ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { font-family: Arial, Helvetica, sans-serif; font-size: 11pt; color: #1a1a1a; background: #fff; }
        .page { width: 210mm; min-height: 297mm; margin: 0 auto; padding: 14mm 16mm; }
        @media print {
            html, body { font-size: 10pt; }
            .page { padding: 10mm 12mm; }
            .no-print { display: none !important; }
            a { color: inherit !important; text-decoration: none !important; }
        }

        /* Header */
        .doc-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #10b981; padding-bottom: 8mm; margin-bottom: 8mm; }
        .company-name { font-size: 17pt; font-weight: 700; color: #10b981; }
        .company-info { font-size: 8.5pt; color: #555; margin-top: 2.5mm; line-height: 1.6; }
        .doc-right { text-align: right; }
        .doc-title { font-size: 11pt; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: .1em; }
        .doc-number { font-size: 20pt; font-weight: 800; color: #1a1a1a; line-height: 1.1; margin-top: 1mm; font-family: 'Courier New', Courier, monospace; }
        .status-badge { display: inline-block; padding: 1.5mm 4mm; border-radius: 100px; font-size: 7.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin-top: 2mm; }
        .s-draft          { background: #2a2a2a; color: #a0a0a0; border: 1px solid #404040; }
        .s-sent           { background: #1e3a4a; color: #67b3dd; border: 1px solid #2a5a7a; }
        .s-paid           { background: #1a3020; color: #22c55e; border: 1px solid #2a5a3a; }
        .s-partially_paid { background: #1a3a2a; color: #10b981; border: 1px solid #2a6a4a; }
        .s-overdue        { background: #3a1e1e; color: #ef4444; border: 1px solid #6a2a2a; }
        .s-cancelled      { background: #2a2a2a; color: #a3a3a3; border: 1px solid #404040; }

        /* Bill to / from */
        .addr-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8mm; margin-bottom: 8mm; }
        .addr-block h3 { font-size: 7.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #888; margin-bottom: 2mm; }
        .addr-block p { font-size: 9.5pt; line-height: 1.6; color: #1a1a1a; }

        /* Dates row */
        .dates-row { display: flex; gap: 8mm; margin-bottom: 8mm; flex-wrap: wrap; }
        .date-block { flex: 1; min-width: 50mm; }
        .date-label { font-size: 7.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #888; margin-bottom: 1mm; }
        .date-value { font-size: 10pt; color: #1a1a1a; font-weight: 600; }

        /* Items table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .items-table thead th { background: #f5f5f5; padding: 2.5mm 3mm; font-size: 7.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #555; border-bottom: 2px solid #d4d4d4; text-align: left; }
        .items-table tbody td { padding: 2.5mm 3mm; font-size: 9pt; border-bottom: 1px solid #ebebeb; vertical-align: top; }
        .items-table tbody tr:last-child td { border-bottom: none; }
        .items-table th:not(:first-child), .items-table td:not(:first-child) { text-align: right; }
        .items-table .desc-col { text-align: left !important; }

        /* Totals */
        .totals-wrap { display: flex; justify-content: flex-end; margin-top: 0; border-top: 2px solid #d4d4d4; }
        .totals-table { width: 68mm; border-collapse: collapse; }
        .totals-table td { padding: 2mm 3mm; font-size: 9pt; border-bottom: 1px solid #ebebeb; }
        .totals-table tr:last-child td { border-bottom: none; font-weight: 700; font-size: 11pt; padding-top: 3mm; padding-bottom: 3mm; background: #f9f9f9; }
        .totals-table .tl { color: #555; }
        .totals-table .tv { text-align: right; }
        .tv-paid { color: #22c55e; }
        .tv-due  { color: #ef4444; }
        .tv-due-ok { color: #22c55e; }

        /* Notes */
        .notes-section { margin-top: 8mm; border-top: 1px solid #d4d4d4; padding-top: 5mm; }
        .notes-label { font-size: 7.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #888; margin-bottom: 2mm; }
        .notes-text { font-size: 9pt; color: #555; line-height: 1.6; white-space: pre-wrap; }

        /* Payment info */
        .payment-section { margin-top: 5mm; padding: 3mm 4mm; background: #f5f5f5; border-radius: 2mm; border-left: 3px solid #10b981; }
        .payment-title { font-size: 8pt; font-weight: 700; color: #10b981; margin-bottom: 1.5mm; }
        .payment-info { font-size: 8.5pt; color: #555; line-height: 1.6; }

        /* Footer */
        .doc-footer { margin-top: 10mm; border-top: 1px solid #d4d4d4; padding-top: 3mm; font-size: 7.5pt; color: #aaa; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 2mm; }

        /* Print button */
        .print-btn { position: fixed; top: 10px; right: 10px; padding: 8px 18px; background: #10b981; color: #fff; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; box-shadow: 0 2px 8px rgba(16,185,129,.4); }
        .print-btn:hover { background: #059669; }
    </style>
</head>
<body>
<button class="print-btn no-print" onclick="window.print()">&#x1F5A8; Print</button>

<div class="page">

    <!-- Header -->
    <div class="doc-header">
        <div>
            <div class="company-name"><?= Utils::e($company['company_name'] ?? APP_NAME) ?></div>
            <div class="company-info">
                <?php if (!empty($company['company_address'])): ?><?= nl2br(Utils::e($company['company_address'])) ?><br><?php endif; ?>
                <?php if (!empty($company['company_phone'])): ?>Tel: <?= Utils::e($company['company_phone']) ?><?php endif; ?>
                <?php if (!empty($company['company_email'])): ?><?= !empty($company['company_phone']) ? ' &nbsp;·&nbsp; ' : '' ?>Email: <?= Utils::e($company['company_email']) ?><?php endif; ?>
                <?php if (!empty($company['vat_number'])): ?><br>VAT: <?= Utils::e($company['vat_number']) ?><?php endif; ?>
            </div>
        </div>
        <div class="doc-right">
            <div class="doc-title">Invoice</div>
            <div class="doc-number"><?= Utils::e($invoice['invoice_number']) ?></div>
            <div>
                <span class="status-badge s-<?= Utils::e($invoice['status']) ?>">
                    <?= Utils::e(INVOICE_STATUS[$invoice['status']] ?? $invoice['status']) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Addresses -->
    <div class="addr-grid">
        <div class="addr-block">
            <h3>Bill To</h3>
            <p>
                <strong><?= Utils::e($invoice['customer_name'] ?? '—') ?></strong><br>
                <?php if (!empty($invoice['customer_address'])): ?><?= nl2br(Utils::e($invoice['customer_address'])) ?><br><?php endif; ?>
                <?php if (!empty($invoice['customer_city'])): ?>
                <?= Utils::e(trim(($invoice['customer_postal_code'] ?? '') . ' ' . ($invoice['customer_city'] ?? ''))) ?>
                <?php if (!empty($invoice['customer_province'])): ?> (<?= Utils::e($invoice['customer_province']) ?>)<?php endif; ?><br>
                <?php endif; ?>
                <?php if (!empty($invoice['customer_email'])): ?><?= Utils::e($invoice['customer_email']) ?><br><?php endif; ?>
                <?php if (!empty($invoice['customer_phone'])): ?><?= Utils::e($invoice['customer_phone']) ?><br><?php endif; ?>
                <?php if (!empty($invoice['customer_vat'])): ?>VAT: <?= Utils::e($invoice['customer_vat']) ?><?php endif; ?>
            </p>
        </div>
        <?php if (!empty($invoice['repair_id'])): ?>
        <div class="addr-block">
            <h3>Reference</h3>
            <p>Repair #<?= $invoice['repair_id'] ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Dates -->
    <div class="dates-row">
        <div class="date-block">
            <div class="date-label">Invoice Date</div>
            <div class="date-value"><?= Utils::formatDate($invoice['invoice_date']) ?></div>
        </div>
        <?php if (!empty($invoice['due_date'])): ?>
        <div class="date-block">
            <div class="date-label">Due Date</div>
            <div class="date-value"><?= Utils::formatDate($invoice['due_date']) ?></div>
        </div>
        <?php endif; ?>
        <div class="date-block">
            <div class="date-label">Tax Rate</div>
            <div class="date-value"><?= number_format((float)($invoice['tax_percentage'] ?? 0), 1) ?>%</div>
        </div>
    </div>

    <!-- Line items -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="desc-col">Description</th>
                <th style="width:55pt">Qty</th>
                <th style="width:80pt">Unit Price</th>
                <th style="width:55pt">Disc.%</th>
                <th style="width:55pt">Tax%</th>
                <th style="width:80pt">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($invoice['items'] ?? [] as $item): ?>
        <tr>
            <td class="desc-col"><?= Utils::e($item['description']) ?></td>
            <td><?= (float)$item['quantity'] ?></td>
            <td><?= Utils::formatCurrency($item['unit_price']) ?></td>
            <td><?= (float)($item['discount_pct'] ?? 0) > 0 ? number_format((float)$item['discount_pct'], 1) . '%' : '—' ?></td>
            <td><?= number_format((float)($item['tax_percentage'] ?? 0), 1) ?>%</td>
            <td><?= Utils::formatCurrency($item['line_total']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totals -->
    <?php
    $total   = (float)($invoice['total_amount'] ?? 0);
    $paid    = (float)($invoice['amount_paid']  ?? 0);
    $balance = round($total - $paid, 2);
    ?>
    <div class="totals-wrap">
        <table class="totals-table">
            <tr><td class="tl">Subtotal</td><td class="tv"><?= Utils::formatCurrency($invoice['subtotal'] ?? 0) ?></td></tr>
            <?php if ((float)($invoice['tax_amount'] ?? 0) > 0): ?>
            <tr><td class="tl">Tax (<?= number_format((float)($invoice['tax_percentage'] ?? 0), 1) ?>%)</td><td class="tv"><?= Utils::formatCurrency($invoice['tax_amount'] ?? 0) ?></td></tr>
            <?php endif; ?>
            <?php if ($paid > 0): ?>
            <tr><td class="tl">Amount Paid</td><td class="tv tv-paid">-<?= Utils::formatCurrency($paid) ?></td></tr>
            <?php endif; ?>
            <tr>
                <td class="tl">Balance Due</td>
                <td class="tv <?= $balance > 0 ? 'tv-due' : 'tv-due-ok' ?>"><?= Utils::formatCurrency($balance) ?></td>
            </tr>
        </table>
    </div>

    <!-- Notes -->
    <?php if (!empty($invoice['notes'])): ?>
    <div class="notes-section">
        <div class="notes-label">Notes</div>
        <div class="notes-text"><?= Utils::e($invoice['notes']) ?></div>
    </div>
    <?php endif; ?>

    <!-- Payment info from company settings -->
    <?php if (!empty($company['payment_info'] ?? $company['notes'] ?? '')): ?>
    <div class="payment-section">
        <div class="payment-title">Payment Information</div>
        <div class="payment-info"><?= nl2br(Utils::e($company['payment_info'] ?? $company['notes'] ?? '')) ?></div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="doc-footer">
        <span><?= Utils::e($company['company_name'] ?? APP_NAME) ?></span>
        <span>Invoice <?= Utils::e($invoice['invoice_number']) ?> &nbsp;·&nbsp; Printed <?= date('d/m/Y H:i') ?></span>
        <?php if (!empty($company['company_email'])): ?>
        <span><?= Utils::e($company['company_email']) ?></span>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
