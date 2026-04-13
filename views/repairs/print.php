<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repair Sheet #<?= $repair['repair_id'] ?></title>
    <style>
        /* ── Reset & base ────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { font-family: Arial, Helvetica, sans-serif; font-size: 11pt; color: #1a1a1a; background: #fff; }

        /* ── Page layout ─────────────────────────────────────────────── */
        .page { width: 210mm; min-height: 297mm; margin: 0 auto; padding: 12mm 14mm; }
        @media print {
            html, body { font-size: 10pt; }
            .page { padding: 8mm 10mm; }
            .no-print { display: none !important; }
            a { color: inherit !important; text-decoration: none !important; }
        }

        /* ── Header ──────────────────────────────────────────────────── */
        .doc-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #10b981; padding-bottom: 8mm; margin-bottom: 7mm; }
        .company-name { font-size: 16pt; font-weight: 700; color: #10b981; }
        .company-info { font-size: 8.5pt; color: #555; margin-top: 2mm; line-height: 1.5; }
        .doc-meta { text-align: right; }
        .doc-title { font-size: 14pt; font-weight: 700; color: #1a1a1a; }
        .doc-id { font-size: 18pt; font-weight: 800; color: #10b981; line-height: 1; margin-top: 1mm; }
        .doc-date { font-size: 8.5pt; color: #555; margin-top: 2mm; }

        /* ── Status badge ─────────────────────────────────────────────── */
        .status-badge { display: inline-block; padding: 1.5mm 4mm; border-radius: 100px; font-size: 8pt; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
        .status-in_progress     { background: #1e3a4a; color: #67b3dd; border: 1px solid #2a5a7a; }
        .status-on_hold         { background: #3a2e1e; color: #f59e0b; border: 1px solid #5a4a2a; }
        .status-waiting_for_parts { background: #2a2a1e; color: #d4b84a; border: 1px solid #4a4a2a; }
        .status-ready_for_pickup{ background: #1a3a2a; color: #10b981; border: 1px solid #2a6a4a; }
        .status-completed       { background: #1a3020; color: #22c55e; border: 1px solid #2a5a3a; }
        .status-collected       { background: #2a2a2a; color: #a3a3a3; border: 1px solid #404040; }
        .status-cancelled       { background: #3a1e1e; color: #ef4444; border: 1px solid #6a2a2a; }

        /* ── Two-column grid ─────────────────────────────────────────── */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 5mm; margin-bottom: 5mm; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 5mm; margin-bottom: 5mm; }

        /* ── Section card ─────────────────────────────────────────────── */
        .card { border: 1px solid #d4d4d4; border-radius: 3mm; overflow: hidden; margin-bottom: 5mm; page-break-inside: avoid; }
        .card-header { background: #f5f5f5; border-bottom: 1px solid #d4d4d4; padding: 2mm 4mm; font-size: 8pt; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #555; }
        .card-body { padding: 3mm 4mm; }

        /* ── Field rows ───────────────────────────────────────────────── */
        .field { margin-bottom: 2.5mm; }
        .field:last-child { margin-bottom: 0; }
        .field-label { font-size: 7.5pt; color: #777; text-transform: uppercase; letter-spacing: .05em; margin-bottom: .5mm; }
        .field-value { font-size: 9.5pt; color: #1a1a1a; line-height: 1.4; white-space: pre-wrap; word-break: break-word; }
        .field-value.mono { font-family: 'Courier New', Courier, monospace; }
        .field-empty { color: #aaa; font-style: italic; }

        /* ── Amounts table ────────────────────────────────────────────── */
        .amount-table { width: 100%; border-collapse: collapse; }
        .amount-table td { padding: 1.5mm 0; font-size: 9pt; border-bottom: 1px solid #ebebeb; }
        .amount-table tr:last-child td { border-bottom: none; font-weight: 700; font-size: 10pt; padding-top: 2.5mm; }
        .amount-table .label { color: #555; }
        .amount-table .val { text-align: right; }
        .amount-due { color: #ef4444; }
        .amount-paid { color: #22c55e; }

        /* ── Notes / text areas ───────────────────────────────────────── */
        .notes-box { border: 1px solid #d4d4d4; border-radius: 2mm; padding: 3mm; font-size: 9pt; line-height: 1.55; color: #1a1a1a; min-height: 16mm; white-space: pre-wrap; }

        /* ── Signature area ───────────────────────────────────────────── */
        .sig-area { display: grid; grid-template-columns: 1fr 1fr; gap: 10mm; margin-top: 8mm; }
        .sig-box { border-bottom: 1px solid #333; padding-bottom: 10mm; margin-bottom: 2mm; }
        .sig-label { font-size: 8pt; color: #555; text-align: center; }

        /* ── QR code ──────────────────────────────────────────────────── */
        .qr-block { display: flex; align-items: flex-start; gap: 4mm; }
        .qr-img { width: 28mm; height: 28mm; flex-shrink: 0; border: 1px solid #d4d4d4; border-radius: 2mm; padding: 1.5mm; background: #fff; }
        .qr-img img { width: 100%; height: auto; display: block; }
        .qr-info { font-size: 8pt; color: #555; line-height: 1.5; }

        /* ── Footer ───────────────────────────────────────────────────── */
        .doc-footer { border-top: 1px solid #d4d4d4; padding-top: 3mm; margin-top: 6mm; font-size: 7.5pt; color: #999; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 2mm; }

        /* ── Print button ─────────────────────────────────────────────── */
        .print-btn { position: fixed; top: 10px; right: 10px; padding: 8px 18px; background: #10b981; color: #fff; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; box-shadow: 0 2px 8px rgba(16,185,129,.4); }
        .print-btn:hover { background: #059669; }
    </style>
</head>
<body>
<button class="print-btn no-print" onclick="window.print()">&#x1F5A8; Print</button>

<div class="page">

    <!-- ── Document header ─────────────────────────────────────────── -->
    <div class="doc-header">
        <div>
            <div class="company-name"><?= Utils::e($company['name'] ?? APP_NAME) ?></div>
            <div class="company-info">
                <?php if (!empty($company['address'])): ?><?= Utils::e($company['address']) ?><br><?php endif; ?>
                <?php if (!empty($company['phone'])): ?>Tel: <?= Utils::e($company['phone']) ?><?php endif; ?>
                <?php if (!empty($company['email'])): ?><?= !empty($company['phone']) ? ' &nbsp;·&nbsp; ' : '' ?>Email: <?= Utils::e($company['email']) ?><?php endif; ?>
                <?php if (!empty($company['vat'])): ?><br>VAT: <?= Utils::e($company['vat']) ?><?php endif; ?>
            </div>
        </div>
        <div class="doc-meta">
            <div class="doc-title">REPAIR SHEET</div>
            <div class="doc-id">#<?= $repair['repair_id'] ?></div>
            <div class="doc-date">Printed: <?= date('d/m/Y H:i') ?></div>
            <div style="margin-top:2mm">
                <span class="status-badge status-<?= Utils::e($repair['status']) ?>">
                    <?= Utils::e(REPAIR_STATUS[$repair['status']] ?? $repair['status']) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- ── Client + Device ──────────────────────────────────────────── -->
    <div class="grid-2">

        <!-- Client -->
        <div class="card">
            <div class="card-header">Client</div>
            <div class="card-body">
                <div class="field">
                    <div class="field-label">Name</div>
                    <div class="field-value" style="font-weight:600"><?= Utils::e($repair['customer_name'] ?? '—') ?></div>
                </div>
                <?php $phone = $repair['customer_phone'] ?? ($repair['customer_phone_mobile'] ?? ''); ?>
                <?php if ($phone): ?>
                <div class="field">
                    <div class="field-label">Phone</div>
                    <div class="field-value"><?= Utils::e($phone) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($repair['customer_email'])): ?>
                <div class="field">
                    <div class="field-label">Email</div>
                    <div class="field-value"><?= Utils::e($repair['customer_email']) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($repair['customer_address'])): ?>
                <div class="field">
                    <div class="field-label">Address</div>
                    <div class="field-value"><?= Utils::e($repair['customer_address']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Device -->
        <div class="card">
            <div class="card-header">Device</div>
            <div class="card-body">
                <div class="field">
                    <div class="field-label">Brand / Model</div>
                    <div class="field-value" style="font-weight:600">
                        <?= Utils::e(trim(($repair['device_brand'] ?? '') . ' ' . ($repair['device_model'] ?? ''))) ?>
                    </div>
                </div>
                <?php if (!empty($repair['device_serial_number'])): ?>
                <div class="field">
                    <div class="field-label">Serial / IMEI</div>
                    <div class="field-value mono"><?= Utils::e($repair['device_serial_number']) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($repair['device_password'])): ?>
                <div class="field">
                    <div class="field-label">Password / PIN</div>
                    <div class="field-value mono"><?= Utils::e($repair['device_password']) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($repair['device_condition'])): ?>
                <div class="field">
                    <div class="field-label">Condition / Accessories</div>
                    <div class="field-value"><?= Utils::e($repair['device_condition']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- ── Dates + Priority + Assignment ───────────────────────────── -->
    <div class="grid-3">
        <div class="card">
            <div class="card-header">Dates</div>
            <div class="card-body">
                <div class="field">
                    <div class="field-label">Date In</div>
                    <div class="field-value"><?= Utils::formatDate($repair['date_in']) ?></div>
                </div>
                <?php if (!empty($repair['date_expected_out'])): ?>
                <div class="field">
                    <div class="field-label">Expected Out</div>
                    <div class="field-value"><?= Utils::formatDate($repair['date_expected_out']) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($repair['date_out'])): ?>
                <div class="field">
                    <div class="field-label">Completed</div>
                    <div class="field-value"><?= Utils::formatDate($repair['date_out']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Priority</div>
            <div class="card-body">
                <div class="field" style="margin-bottom:0">
                    <div class="field-value" style="font-weight:700;text-transform:capitalize">
                        <?= Utils::e(ucfirst($repair['priority'] ?? 'Normal')) ?>
                    </div>
                </div>
                <?php if (!empty($repair['assigned_to'])): ?>
                <div class="field" style="margin-top:2mm;margin-bottom:0">
                    <div class="field-label">Technician</div>
                    <div class="field-value"><?= Utils::e($repair['assigned_to']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Amounts</div>
            <div class="card-body">
                <?php
                $est = (float)($repair['estimate_amount'] ?? 0);
                $act = (float)($repair['actual_amount']   ?? 0);
                $dep = (float)($repair['deposit_paid']    ?? 0);
                $due = $act > 0 ? max(0, $act - $dep) : ($est > 0 ? max(0, $est - $dep) : 0);
                ?>
                <table class="amount-table">
                    <?php if ($est > 0): ?>
                    <tr><td class="label">Estimate</td><td class="val"><?= Utils::formatCurrency($est) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($act > 0): ?>
                    <tr><td class="label">Amount</td><td class="val"><?= Utils::formatCurrency($act) ?></td></tr>
                    <?php endif; ?>
                    <?php if ($dep > 0): ?>
                    <tr><td class="label">Deposit</td><td class="val amount-paid">-<?= Utils::formatCurrency($dep) ?></td></tr>
                    <?php endif; ?>
                    <tr>
                        <td class="label">Balance Due</td>
                        <td class="val <?= $due > 0 ? 'amount-due' : 'amount-paid' ?>"><?= Utils::formatCurrency($due) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- ── Problem Description ──────────────────────────────────────── -->
    <div class="card">
        <div class="card-header">Problem Description (reported by client)</div>
        <div class="card-body">
            <div class="notes-box"><?= Utils::e($repair['problem_description'] ?? '') ?></div>
        </div>
    </div>

    <!-- ── Diagnosis / Work Done ────────────────────────────────────── -->
    <div class="card">
        <div class="card-header">Diagnosis / Work Performed</div>
        <div class="card-body">
            <div class="notes-box" style="min-height:20mm"><?= Utils::e($repair['diagnosis_notes'] ?? '') ?></div>
        </div>
    </div>

    <!-- ── QR + Terms ───────────────────────────────────────────────── -->
    <div class="grid-2">

        <div class="card">
            <div class="card-header">Repair Reference</div>
            <div class="card-body">
                <div class="qr-block">
                    <?php if (!empty($qrCode)): ?>
                    <div class="qr-img">
                        <img src="<?= Utils::e($qrCode) ?>" alt="QR Code">
                    </div>
                    <?php endif; ?>
                    <div class="qr-info">
                        <strong>Repair #<?= $repair['repair_id'] ?></strong><br>
                        <?php if (!empty($repair['qr_code'])): ?>
                        Code: <span style="font-family:monospace"><?= Utils::e($repair['qr_code']) ?></span><br>
                        <?php endif; ?>
                        <?php if (!empty($company['phone'])): ?>
                        Call us: <?= Utils::e($company['phone']) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Terms & Conditions</div>
            <div class="card-body">
                <div style="font-size:7.5pt;color:#555;line-height:1.6">
                    <?php if (!empty($company['terms'])): ?>
                    <?= nl2br(Utils::e($company['terms'])) ?>
                    <?php else: ?>
                    By leaving this device for repair, the client accepts our service terms.
                    Uncollected devices after 90 days may be disposed of.
                    No responsibility is accepted for pre-existing damage or data loss.
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- ── Signatures ───────────────────────────────────────────────── -->
    <div class="sig-area">
        <div>
            <div class="sig-box"></div>
            <div class="sig-label">Client Signature &amp; Date</div>
        </div>
        <div>
            <div class="sig-box"></div>
            <div class="sig-label">Technician Signature &amp; Date</div>
        </div>
    </div>

    <!-- ── Document footer ─────────────────────────────────────────── -->
    <div class="doc-footer">
        <span><?= Utils::e($company['name'] ?? APP_NAME) ?></span>
        <span>Repair #<?= $repair['repair_id'] ?> &nbsp;·&nbsp; Printed <?= date('d/m/Y H:i') ?></span>
        <?php if (!empty($company['website'])): ?>
        <span><?= Utils::e($company['website']) ?></span>
        <?php endif; ?>
    </div>

</div><!-- /page -->
</body>
</html>
