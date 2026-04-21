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
        html, body { font-family: 'Segoe UI', Arial, Helvetica, sans-serif; font-size: 11pt; color: #1a1a1a; background: #f0f4f8; }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 8mm auto;
            padding: 0;
            background: #fff;
            border-radius: 3mm;
            box-shadow: 0 4px 24px rgba(0,0,0,.13);
            overflow: hidden;
        }
        @media print {
            html, body { font-size: 10pt; background: #fff; }
            .page { margin: 0; border-radius: 0; box-shadow: none; }
            .no-print { display: none !important; }
        }

        /* ── Top banner ── */
        .doc-banner {
            background: linear-gradient(135deg, #0d9488 0%, #10b981 60%, #34d399 100%);
            padding: 8mm 10mm 6mm;
            position: relative;
            overflow: hidden;
        }
        .doc-banner::before {
            content: '';
            position: absolute;
            top: -20mm; right: -10mm;
            width: 55mm; height: 55mm;
            border-radius: 50%;
            background: rgba(255,255,255,.08);
        }
        .doc-banner::after {
            content: '';
            position: absolute;
            bottom: -15mm; right: 20mm;
            width: 35mm; height: 35mm;
            border-radius: 50%;
            background: rgba(255,255,255,.06);
        }
        .banner-inner { display: flex; justify-content: space-between; align-items: flex-start; position: relative; z-index: 1; }
        .company-block { flex: 1; }
        .company-name  { font-size: 18pt; font-weight: 800; color: #fff; letter-spacing: .02em; margin-bottom: 1.5mm; }
        .company-sub   { font-size: 8.5pt; color: rgba(255,255,255,.85); line-height: 1.7; }
        .company-sub span { margin-right: 3mm; }

        .doc-label-block { text-align: right; }
        .doc-label {
            display: inline-block;
            background: rgba(255,255,255,.18);
            border: 1.5px solid rgba(255,255,255,.4);
            color: #fff;
            font-size: 14pt;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding: 2mm 6mm;
            border-radius: 2mm;
            margin-bottom: 2mm;
        }
        .doc-number { font-size: 9pt; color: rgba(255,255,255,.8); text-align: right; }
        .doc-number strong { color: #fff; }

        /* ── Body padding ── */
        .doc-body { padding: 7mm 10mm 4mm; }

        /* ── Date + divider ── */
        .meta-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-bottom: 5mm;
        }
        .date-badge {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 2mm;
            padding: 1.5mm 4mm;
            font-size: 9pt;
            color: #166534;
        }
        .date-badge strong { font-weight: 700; }

        /* ── Two-column info block ── */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5mm;
            margin-bottom: 6mm;
        }
        .info-card {
            border: 1px solid #e5e7eb;
            border-radius: 2mm;
            overflow: hidden;
        }
        .info-card-head {
            background: linear-gradient(90deg, #f0fdf4, #f9fafb);
            border-bottom: 1px solid #e5e7eb;
            padding: 2mm 4mm;
            font-size: 7.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #0d9488;
        }
        .info-card-body { padding: 2mm 4mm; }
        .info-row { display: grid; grid-template-columns: 90px 1fr; border-bottom: 1px solid #f3f4f6; }
        .info-row:last-child { border-bottom: none; }
        .info-key { padding: 2mm 0; font-size: 8pt; color: #6b7280; font-weight: 600; display: flex; align-items: center; }
        .info-val { padding: 2mm 0 2mm 3mm; font-size: 9pt; color: #111827; display: flex; align-items: center; font-weight: 500; }

        /* ── Items table ── */
        .section-title {
            font-size: 8pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #0d9488;
            margin-bottom: 2mm;
            padding-left: 1mm;
        }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 5mm; border-radius: 2mm; overflow: hidden; border: 1px solid #e5e7eb; }
        .items-table thead tr { background: linear-gradient(90deg, #0d9488, #10b981); }
        .items-table th { padding: 3mm 4mm; font-size: 8pt; font-weight: 700; color: #fff; text-transform: uppercase; letter-spacing: .05em; }
        .items-table th.r { text-align: right; }
        .items-table td { border-bottom: 1px solid #f3f4f6; padding: 3mm 4mm; font-size: 9.5pt; }
        .items-table td.r { text-align: right; }
        .items-table tbody tr:nth-child(even) td { background: #fafafa; }
        .items-table tbody tr:last-child td { border-bottom: none; }
        .desc-col { width: 50%; }
        .amt-col  { width: 16.67%; }
        .totals-row td {
            background: #f0fdf4;
            font-weight: 700;
            font-size: 10pt;
            border-top: 2px solid #bbf7d0;
        }
        .grand-net { color: #0d9488; font-size: 11pt; }

        /* ── In words ── */
        .in-words {
            display: flex;
            align-items: center;
            gap: 3mm;
            background: linear-gradient(90deg, #ecfdf5, #f0fdf4);
            border: 1px solid #bbf7d0;
            border-left: 4px solid #10b981;
            border-radius: 2mm;
            padding: 3mm 5mm;
            margin-bottom: 7mm;
            font-size: 9pt;
        }
        .in-words .iw-label { font-weight: 700; color: #0d9488; white-space: nowrap; }
        .in-words .iw-value { font-weight: 700; color: #111827; text-transform: uppercase; letter-spacing: .04em; }

        /* ── Footer note + signature ── */
        .doc-footer-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 5mm; margin-bottom: 7mm; }
        .footer-card { border: 1px solid #e5e7eb; border-radius: 2mm; overflow: hidden; min-height: 28mm; }
        .footer-card-head {
            background: linear-gradient(90deg, #f0fdf4, #f9fafb);
            border-bottom: 1px solid #e5e7eb;
            padding: 2mm 4mm;
            font-size: 7.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #0d9488;
        }
        .footer-card-body { padding: 3mm 4mm; }
        .sig-area { padding: 3mm 4mm; display: flex; flex-direction: column; justify-content: flex-end; height: calc(100% - 8mm); min-height: 20mm; }
        .sig-line { border-bottom: 1.5px solid #374151; margin-top: 12mm; }
        .sig-label { font-size: 7pt; color: #9ca3af; text-align: center; margin-top: 1.5mm; }

        /* ── Page footer strip ── */
        .page-footer-strip {
            background: linear-gradient(90deg, #f0fdf4, #f9fafb);
            border-top: 1px solid #d1fae5;
            padding: 2.5mm 10mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 7.5pt;
            color: #6b7280;
        }
        .page-footer-strip .pf-company { font-weight: 600; color: #0d9488; }

        /* ── Print button ── */
        .print-fab {
            position: fixed;
            bottom: 18px;
            right: 18px;
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 10px 22px;
            background: linear-gradient(135deg, #0d9488, #10b981);
            color: #fff;
            border: none;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(16,185,129,.45);
            letter-spacing: .02em;
            transition: transform .15s, box-shadow .15s;
        }
        .print-fab:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(16,185,129,.5); }
        .print-fab svg { width: 16px; height: 16px; }
    </style>
</head>
<body>
<button class="print-fab no-print" onclick="window.print()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
    Print
</button>

<div class="page">

    <!-- Top banner -->
    <div class="doc-banner">
        <div class="banner-inner">
            <div class="company-block">
                <div class="company-name"><?= Utils::e($cn['company_name'] ?: APP_NAME) ?></div>
                <div class="company-sub">
                    <?php if (!empty($cn['company_address'])): ?>
                        <span><?= nl2br(Utils::e($cn['company_address'])) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($cn['company_phone'])): ?>
                        <span>Tel: <?= Utils::e($cn['company_phone']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($cn['company_email'])): ?>
                        <span>Email: <?= Utils::e($cn['company_email']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($cn['company_vat'])): ?>
                        <span>VAT: <?= Utils::e($cn['company_vat']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="doc-label-block">
                <div class="doc-label">Credit Note</div>
                <div class="doc-number">No. <strong>#<?= Utils::e($cn['cn_number']) ?></strong></div>
            </div>
        </div>
    </div>

    <!-- Body -->
    <div class="doc-body">

        <!-- Date badge -->
        <div class="meta-row">
            <div class="date-badge">Date: <strong><?= Utils::formatDate($cn['cn_date']) ?></strong></div>
        </div>

        <!-- CN info + Customer info -->
        <div class="info-grid">
            <div class="info-card">
                <div class="info-card-head">Credit Note Info</div>
                <div class="info-card-body">
                    <div class="info-row">
                        <div class="info-key">CN Number</div>
                        <div class="info-val"><strong style="color:#0d9488">#<?= Utils::e($cn['cn_number']) ?></strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-key">Date</div>
                        <div class="info-val"><?= Utils::formatDate($cn['cn_date']) ?></div>
                    </div>
                </div>
            </div>
            <div class="info-card">
                <div class="info-card-head">Customer Details</div>
                <div class="info-card-body">
                    <div class="info-row">
                        <div class="info-key">Name</div>
                        <div class="info-val"><strong><?= Utils::e($cn['customer_name']) ?></strong></div>
                    </div>
                    <?php if (!empty($cn['customer_address'])): ?>
                    <div class="info-row">
                        <div class="info-key">Address</div>
                        <div class="info-val"><?= Utils::e($cn['customer_address']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($cn['customer_vat'])): ?>
                    <div class="info-row">
                        <div class="info-key">VAT N°</div>
                        <div class="info-val" style="font-family:monospace;font-size:8.5pt"><?= Utils::e($cn['customer_vat']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Line items -->
        <div class="section-title">Line Items</div>
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
            <span class="iw-label">In Words:</span>
            <span class="iw-value"><?= Utils::e(cnAmountToWords((float)$cn['total_net'])) ?></span>
        </div>

        <!-- Note + Signature -->
        <div class="doc-footer-grid">
            <div class="footer-card">
                <div class="footer-card-head">Note</div>
                <div class="footer-card-body" style="font-size:8.5pt;color:#555;line-height:1.7;white-space:pre-wrap"><?= Utils::e($cn['note'] ?? '') ?></div>
            </div>
            <div class="footer-card">
                <div class="footer-card-head">Signature / Date</div>
                <div class="sig-area">
                    <div class="sig-line"></div>
                    <div class="sig-label">Authorised Signature &amp; Date</div>
                </div>
            </div>
        </div>

    </div>

    <!-- Page footer strip -->
    <div class="page-footer-strip">
        <span class="pf-company"><?= Utils::e($cn['company_name'] ?: APP_NAME) ?></span>
        <span>Credit Note #<?= Utils::e($cn['cn_number']) ?> &nbsp;&middot;&nbsp; Printed <?= date('d/m/Y H:i') ?></span>
    </div>

</div>
</body>
</html>
