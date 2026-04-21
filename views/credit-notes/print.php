<?php
/**
 * Converts a positive float to uppercase English words (integer part only).
 * e.g.  280.00  →  "TWO HUNDRED AND EIGHTY"
 */
function cnAmountToWords(float $amount): string
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Note #<?= $cn['cn_number'] ?></title>
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
        .cn-title-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6mm; }
        .cn-title { font-size: 15pt; font-weight: 700; text-decoration: underline; letter-spacing: .03em; }
        .cn-date-box { text-align: right; font-size: 9.5pt; }
        .cn-date-box .label { color: #555; }
        .cn-date-box .value { font-weight: 700; }

        /* ── Customer block ── */
        .cn-meta { margin-bottom: 7mm; border: 1px solid #d4d4d4; border-radius: 2mm; overflow: hidden; }
        .cn-meta-row { display: grid; grid-template-columns: 130px 1fr; border-bottom: 1px solid #e8e8e8; }
        .cn-meta-row:last-child { border-bottom: none; }
        .cn-meta-key { background: #f5f5f5; padding: 2.5mm 4mm; font-size: 8.5pt; font-weight: 700; color: #555; display: flex; align-items: center; }
        .cn-meta-val { padding: 2.5mm 4mm; font-size: 9.5pt; color: #1a1a1a; display: flex; align-items: center; }

        /* ── Items table ── */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 5mm; }
        .items-table thead tr { background: #fef9e7; }
        .items-table th { border: 1px solid #d4d4d4; padding: 2.5mm 4mm; font-size: 8.5pt; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: .04em; }
        .items-table th.r { text-align: right; }
        .items-table td { border: 1px solid #d4d4d4; padding: 3mm 4mm; font-size: 9.5pt; }
        .items-table td.r { text-align: right; }
        .items-table .desc-col { width: 52%; }
        .items-table .amt-col  { width: 16%; }
        .totals-row { background: #f5f5f5; }
        .totals-row td { font-weight: 700; font-size: 10pt; }
        .grand-net { color: #10b981; }

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
        <div class="company-name"><?= Utils::e($cn['company_name'] ?: APP_NAME) ?></div>
        <div class="company-sub">
            <?php if (!empty($cn['company_address'])): ?><?= nl2br(Utils::e($cn['company_address'])) ?><br><?php endif; ?>
            <?php if (!empty($cn['company_phone'])): ?>Tel: <?= Utils::e($cn['company_phone']) ?><?php endif; ?>
            <?php if (!empty($cn['company_phone']) && !empty($cn['company_email'])): ?> &nbsp;·&nbsp; <?php endif; ?>
            <?php if (!empty($cn['company_email'])): ?>Email: <?= Utils::e($cn['company_email']) ?><?php endif; ?>
            <?php if (!empty($cn['company_vat'])): ?><br>VAT: <?= Utils::e($cn['company_vat']) ?><?php endif; ?>
        </div>
    </div>

    <!-- Title + date -->
    <div class="cn-title-row">
        <div class="cn-title">Credit Note</div>
        <div class="cn-date-box">
            <span class="label">Date: </span>
            <span class="value"><?= Utils::formatDate($cn['cn_date']) ?></span>
        </div>
    </div>

    <!-- Customer meta -->
    <div class="cn-meta">
        <div class="cn-meta-row">
            <div class="cn-meta-key">CN Num:</div>
            <div class="cn-meta-val"><strong><?= Utils::e($cn['cn_number']) ?></strong></div>
        </div>
        <div class="cn-meta-row">
            <div class="cn-meta-key">Customer's Name:</div>
            <div class="cn-meta-val"><?= Utils::e($cn['customer_name']) ?></div>
        </div>
        <?php if (!empty($cn['customer_address'])): ?>
        <div class="cn-meta-row">
            <div class="cn-meta-key">Address:</div>
            <div class="cn-meta-val"><?= Utils::e($cn['customer_address']) ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($cn['customer_vat'])): ?>
        <div class="cn-meta-row">
            <div class="cn-meta-key">Vat N°:</div>
            <div class="cn-meta-val" style="font-family:monospace"><?= Utils::e($cn['customer_vat']) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Line items -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="desc-col">Description</th>
                <th class="amt-col r">Basic Amount</th>
                <th class="amt-col r">VAT Amount</th>
                <th class="amt-col r">Net Amount</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($cn['items'] as $item): ?>
        <tr>
            <td><?= Utils::e($item['description']) ?></td>
            <td class="r"><?= Utils::formatCurrency((float)$item['basic_amount']) ?></td>
            <td class="r"><?= Utils::formatCurrency((float)$item['vat_amount']) ?></td>
            <td class="r"><?= Utils::formatCurrency((float)$item['net_amount']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td><strong>Total</strong></td>
                <td class="r"><?= Utils::formatCurrency((float)$cn['total_basic']) ?></td>
                <td class="r"><?= Utils::formatCurrency((float)$cn['total_vat']) ?></td>
                <td class="r grand-net"><?= Utils::formatCurrency((float)$cn['total_net']) ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- In words -->
    <div class="in-words">
        <span class="label">In Words:</span>
        <span class="value"><?= Utils::e(cnAmountToWords((float)$cn['total_net'])) ?></span>
    </div>

    <!-- Note + Signature -->
    <div class="doc-footer-grid">
        <div class="footer-card">
            <div class="footer-card-title">Note</div>
            <div style="font-size:8.5pt;color:#555;line-height:1.6;white-space:pre-wrap"><?= Utils::e($cn['note'] ?? '') ?></div>
        </div>
        <div class="footer-card">
            <div class="footer-card-title">Signature / Date</div>
            <div class="sig-line"></div>
            <div class="sig-label">Authorised Signature &amp; Date</div>
        </div>
    </div>

    <!-- Page footer -->
    <div class="page-footer">
        <span><?= Utils::e($cn['company_name'] ?: APP_NAME) ?></span>
        <span>Credit Note #<?= $cn['cn_number'] ?> &nbsp;·&nbsp; Printed <?= date('d/m/Y H:i') ?></span>
    </div>

</div>
</body>
</html>
