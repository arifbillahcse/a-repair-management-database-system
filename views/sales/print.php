<?php
/**
 * Printable sale receipt — styled to match the Credit Note document design
 * (boxed meta rows, amount-in-words, note/signature footer).
 * Expects: $sale (with items), $business (default business or null), $signatureText
 */
function saleAmountToWords(float $amount): string
{
    $n = (int)abs(floor($amount));
    if ($n === 0) return 'ZERO';

    $units = ['','ONE','TWO','THREE','FOUR','FIVE','SIX','SEVEN','EIGHT','NINE',
              'TEN','ELEVEN','TWELVE','THIRTEEN','FOURTEEN','FIFTEEN','SIXTEEN',
              'SEVENTEEN','EIGHTEEN','NINETEEN'];
    $tens  = ['','','TWENTY','THIRTY','FORTY','FIFTY','SIXTY','SEVENTY','EIGHTY','NINETY'];

    $toWords = function (int $n) use ($units, $tens, &$toWords): string {
        if ($n === 0)       return '';
        if ($n < 20)        return $units[$n];
        if ($n < 100)       return $tens[(int)($n / 10)] . ($n % 10 ? ' ' . $units[$n % 10] : '');
        if ($n < 1000)      return $units[(int)($n / 100)] . ' HUNDRED' . ($n % 100 ? ' AND ' . $toWords($n % 100) : '');
        if ($n < 1000000)   return $toWords((int)($n / 1000)) . ' THOUSAND' . ($n % 1000 ? ' ' . $toWords($n % 1000) : '');
        return $toWords((int)($n / 1000000)) . ' MILLION' . ($n % 1000000 ? ' ' . $toWords($n % 1000000) : '');
    };

    return $toWords($n);
}

$custLabel = $sale['linked_customer_name'] ?: ($sale['customer_name'] ?: 'Walk-in customer');
$total     = (float)$sale['total_amount'];
$paid      = (float)$sale['amount_paid'];
$balance   = round($total - $paid, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt <?= Utils::e($sale['sale_number']) ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { font-family: Arial, Helvetica, sans-serif; font-size: 11pt; color: #1a1a1a; background: #fff; }

        .page { width: 210mm; min-height: 297mm; margin: 0 auto; padding: 12mm 14mm; }
        @media print {
            html, body { font-size: 10pt; }
            .page { padding: 8mm 10mm; }
            .no-print { display: none !important; }
        }

        /* ── Company header ── */
        .doc-header { text-align: center; border-bottom: 2px solid #10b981; padding-bottom: 6mm; margin-bottom: 7mm; }
        .company-name { font-size: 16pt; font-weight: 700; color: #10b981; margin-bottom: 1mm; }
        .company-sub  { font-size: 9pt; color: #555; line-height: 1.6; }

        /* ── Title + meta ── */
        .doc-title-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6mm; }
        .doc-title { font-size: 15pt; font-weight: 700; text-decoration: underline; letter-spacing: .03em; }
        .doc-date-box { text-align: right; font-size: 9.5pt; }
        .doc-date-box .label { color: #555; }
        .doc-date-box .value { font-weight: 700; }
        .status-stamp { display: inline-block; padding: 1.5mm 4mm; border-radius: 100px; font-size: 7.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; margin-top: 2mm; }
        .stamp-paid { background: #e8f7ef; color: #10b981; border: 1px solid #b8e8cf; }
        .stamp-due  { background: #fbeaea; color: #c0392b; border: 1px solid #f2c2c2; }

        /* ── Meta boxes (mirrors credit-note meta table) ── */
        .doc-meta { margin-bottom: 7mm; border: 1px solid #d4d4d4; border-radius: 2mm; overflow: hidden; }
        .doc-meta-row { display: grid; grid-template-columns: 130px 1fr; border-bottom: 1px solid #e8e8e8; }
        .doc-meta-row:last-child { border-bottom: none; }
        .doc-meta-key { background: #f5f5f5; padding: 2.5mm 4mm; font-size: 8.5pt; font-weight: 700; color: #555; display: flex; align-items: center; }
        .doc-meta-val { padding: 2.5mm 4mm; font-size: 9.5pt; color: #1a1a1a; display: flex; align-items: center; }

        /* ── Items table ── */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 5mm; }
        .items-table thead tr { background: #fef9e7; }
        .items-table th { border: 1px solid #d4d4d4; padding: 2.5mm 3mm; font-size: 8pt; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: .03em; }
        .items-table th.r, .items-table td.r { text-align: right; }
        .items-table td { border: 1px solid #d4d4d4; padding: 3mm 3mm; font-size: 9pt; }
        .items-table .desc-col { width: 38%; text-align: left; }
        .totals-row { background: #f5f5f5; }
        .totals-row td { font-weight: 700; font-size: 9.5pt; }
        .grand-net { color: #10b981; }

        /* ── Balance summary ── */
        .bal-wrap { display: flex; justify-content: flex-end; margin-bottom: 5mm; }
        .bal-table { width: 78mm; border-collapse: collapse; }
        .bal-table td { padding: 1.8mm 3mm; font-size: 9pt; border-bottom: 1px solid #ebebeb; }
        .bal-table tr:last-child td { border-bottom: none; font-weight: 700; font-size: 10.5pt; padding-top: 2.5mm; }
        .bal-table .tl { color: #555; }
        .bal-table .tv { text-align: right; }
        .tv-paid   { color: #22c55e; }
        .tv-due    { color: #c0392b; }
        .tv-due-ok { color: #22c55e; }

        /* ── In words ── */
        .in-words { border: 1px solid #d4d4d4; border-radius: 2mm; padding: 3mm 5mm; margin-bottom: 7mm; font-size: 9pt; }
        .in-words .label { font-weight: 700; color: #555; margin-right: 4mm; }
        .in-words .value { font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }

        /* ── Footer: note + signature ── */
        .doc-footer-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10mm; margin-top: 10mm; }
        .footer-card { border: 1px solid #d4d4d4; border-radius: 2mm; padding: 3mm 4mm; min-height: 25mm; }
        .footer-card-title { font-size: 7.5pt; font-weight: 700; text-transform: uppercase; color: #555; letter-spacing: .05em; margin-bottom: 2mm; }
        .sig-line { border-bottom: 1px solid #333; margin-top: 14mm; }
        .sig-label { font-size: 7.5pt; color: #777; text-align: center; margin-top: 1mm; }

        /* ── Page footer ── */
        .page-footer { border-top: 1px solid #d4d4d4; margin-top: 8mm; padding-top: 2.5mm; font-size: 7.5pt; color: #aaa; display: flex; justify-content: space-between; }

        /* ── Print button ── */
        .print-btn { position: fixed; top: 10px; right: 10px; padding: 8px 18px; background: #10b981; color: #fff; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; box-shadow: 0 2px 8px rgba(16,185,129,.4); }
        .print-btn:hover { background: #059669; }
    </style>
</head>
<body>
<button class="print-btn no-print" onclick="window.print()">&#x1F5A8; Print</button>

<div class="page">

    <!-- Company header -->
    <div class="doc-header">
        <div class="company-name"><?= Utils::e($business['name'] ?? APP_NAME) ?></div>
        <div class="company-sub">
            <?php if (!empty($business['address'])): ?><?= nl2br(Utils::e($business['address'])) ?><br><?php endif; ?>
            <?php if (!empty($business['phone'])): ?>Tel: <?= Utils::e($business['phone']) ?><?php endif; ?>
            <?php if (!empty($business['phone']) && !empty($business['email'])): ?> &nbsp;·&nbsp; <?php endif; ?>
            <?php if (!empty($business['email'])): ?>Email: <?= Utils::e($business['email']) ?><?php endif; ?>
            <?php if (!empty($business['vat_number'])): ?><br>VAT: <?= Utils::e($business['vat_number']) ?><?php endif; ?>
        </div>
    </div>

    <!-- Title + date -->
    <div class="doc-title-row">
        <div>
            <div class="doc-title">Sale Receipt</div>
            <?php if ($sale['status'] === 'paid'): ?>
            <div><span class="status-stamp stamp-paid">Paid</span></div>
            <?php elseif ($balance > 0): ?>
            <div><span class="status-stamp stamp-due">Balance Due</span></div>
            <?php endif; ?>
        </div>
        <div class="doc-date-box">
            <span class="label">Date: </span>
            <span class="value"><?= Utils::formatDate($sale['sale_date']) ?></span>
        </div>
    </div>

    <!-- Sale meta -->
    <div class="doc-meta">
        <div class="doc-meta-row">
            <div class="doc-meta-key">Receipt Num:</div>
            <div class="doc-meta-val"><strong><?= Utils::e($sale['sale_number']) ?></strong></div>
        </div>
        <div class="doc-meta-row">
            <div class="doc-meta-key">Sold To:</div>
            <div class="doc-meta-val"><?= Utils::e($custLabel) ?></div>
        </div>
        <?php if (!empty($sale['customer_address'])): ?>
        <div class="doc-meta-row">
            <div class="doc-meta-key">Address:</div>
            <div class="doc-meta-val"><?= Utils::e($sale['customer_address']) ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($sale['customer_vat'])): ?>
        <div class="doc-meta-row">
            <div class="doc-meta-key">Vat N°:</div>
            <div class="doc-meta-val" style="font-family:monospace"><?= Utils::e($sale['customer_vat']) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Line items -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="desc-col">Description</th>
                <th class="r">Qty</th>
                <th class="r">Unit Price</th>
                <th class="r">Disc.%</th>
                <th class="r">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($sale['items'] as $item): ?>
        <tr>
            <td class="desc-col"><?= Utils::e($item['description']) ?></td>
            <td class="r"><?= rtrim(rtrim(number_format((float)$item['quantity'], 3), '0'), '.') ?></td>
            <td class="r"><?= Utils::formatCurrency($item['unit_price']) ?></td>
            <td class="r"><?= (float)$item['discount_pct'] > 0 ? rtrim(rtrim(number_format((float)$item['discount_pct'], 2), '0'), '.') . '%' : '—' ?></td>
            <td class="r"><?= Utils::formatCurrency($item['line_total']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td colspan="4"><strong>Subtotal</strong></td>
                <td class="r"><?= Utils::formatCurrency($sale['subtotal']) ?></td>
            </tr>
            <tr class="totals-row">
                <td colspan="4"><strong>Tax (<?= rtrim(rtrim(number_format((float)$sale['tax_percentage'], 2), '0'), '.') ?>%)</strong></td>
                <td class="r"><?= Utils::formatCurrency($sale['tax_amount']) ?></td>
            </tr>
            <tr class="totals-row">
                <td colspan="4"><strong>Total</strong></td>
                <td class="r grand-net"><?= Utils::formatCurrency($total) ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- Balance -->
    <?php if ($paid > 0 || $balance != $total): ?>
    <div class="bal-wrap">
        <table class="bal-table">
            <?php if ($paid > 0): ?>
            <tr><td class="tl">Amount Paid</td><td class="tv tv-paid">-<?= Utils::formatCurrency($paid) ?></td></tr>
            <?php endif; ?>
            <tr><td class="tl">Balance Due</td><td class="tv <?= $balance > 0 ? 'tv-due' : 'tv-due-ok' ?>"><?= Utils::formatCurrency($balance) ?></td></tr>
        </table>
    </div>
    <?php endif; ?>

    <!-- In words -->
    <div class="in-words">
        <span class="label">In Words:</span>
        <span class="value"><?= Utils::e(saleAmountToWords($total)) ?></span>
    </div>

    <!-- Note + Signature -->
    <div class="doc-footer-grid">
        <div class="footer-card">
            <div class="footer-card-title">Note</div>
            <div style="font-size:8.5pt;color:#555;line-height:1.6;white-space:pre-wrap"><?= Utils::e($sale['notes'] ?? '') ?></div>
        </div>
        <div class="footer-card">
            <div class="footer-card-title">Signature / Date</div>
            <?php if (!empty($signatureText)): ?>
            <div style="font-size:9pt;font-weight:600;margin-bottom:6mm;padding-top:2mm"><?= Utils::e($signatureText) ?></div>
            <div class="sig-line" style="margin-top:6mm"></div>
            <?php else: ?>
            <div class="sig-line"></div>
            <?php endif; ?>
            <div class="sig-label">Authorised Signature &amp; Date</div>
        </div>
    </div>

    <!-- Page footer -->
    <div class="page-footer">
        <span><?= Utils::e($business['name'] ?? APP_NAME) ?></span>
        <span>Receipt <?= Utils::e($sale['sale_number']) ?> &nbsp;·&nbsp; Printed <?= date('d/m/Y H:i') ?></span>
    </div>

</div>
</body>
</html>
