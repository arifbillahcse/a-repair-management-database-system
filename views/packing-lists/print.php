<?php
/**
 * Print layout — reproduces the official Italian "Documento di Trasporto"
 * (D.P.R. 472/1996 - D.P.R. 696/1996) form the buyer provided.
 */
$items    = $pl['items'] ?? [];
$minRows  = 14;                                  // keep the goods table looking like the paper form
$padCount = max(0, $minRows - count($items));

$radio = fn(bool $on): string => $on ? '●' : '○';

$transportCedente     = ($pl['transport_by'] ?? 'cedente') === 'cedente';
$transportCessionario = ($pl['transport_by'] ?? '') === 'cessionario';
$deliveryCedente      = ($pl['delivery_by'] ?? '') === 'cedente';
$deliveryCessionario  = ($pl['delivery_by'] ?? '') === 'cessionario';
$inConto              = ($pl['account_type'] ?? '') === 'in_conto';
$aSaldo               = ($pl['account_type'] ?? '') === 'a_saldo';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DDT N° <?= Utils::e($pl['pl_number']) ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { font-family: Arial, Helvetica, sans-serif; font-size: 10.5pt; color: #111; background: #fff; }

        .page { width: 210mm; min-height: 297mm; margin: 0 auto; padding: 10mm 12mm; }
        @media print {
            html, body { font-size: 10pt; }
            .page { padding: 8mm 10mm; }
            .no-print { display: none !important; }
        }

        .ddt { width: 100%; border: 1.5px solid #111; border-collapse: collapse; }
        .ddt td { border: 1px solid #111; vertical-align: top; padding: 2mm 2.5mm; }
        .lbl { font-size: 7.5pt; color: #333; display: block; margin-bottom: 1mm; }
        .val { font-size: 10pt; min-height: 5mm; white-space: pre-wrap; }
        .val-lg { min-height: 20mm; }
        .val-md { min-height: 14mm; }

        .title-main { text-align: center; font-size: 13pt; font-weight: 700; letter-spacing: .02em; }
        .title-sub  { text-align: center; font-size: 7.5pt; color: #333; margin-top: .5mm; }

        .mezzo-row { margin-top: 2.5mm; font-size: 10pt; }
        .mezzo-row .opt { margin: 0 2mm 0 4mm; font-size: 11pt; }
        .num-row { margin-top: 2mm; font-size: 10pt; }
        .num-row .under { display: inline-block; min-width: 26mm; border-bottom: 1px solid #111; padding: 0 1mm; font-weight: 700; }

        .goods { width: 100%; border-collapse: collapse; }
        .goods th, .goods td { border: 1px solid #111; padding: 1.6mm 2.5mm; font-size: 9.5pt; }
        .goods th { background: #f0f0f0; font-size: 9pt; }
        .goods .qh { width: 24mm; text-align: center; }
        .goods .qc { width: 24mm; text-align: center; }
        .goods .dc { text-align: left; }
        .goods td.empty { height: 6.5mm; }

        .opt-line { font-size: 10pt; line-height: 1.9; }
        .sig-line { border-bottom: 1px solid #111; margin-top: 12mm; }
        .sig-cap  { font-size: 7.5pt; color: #333; }

        .print-btn { position: fixed; top: 10px; right: 10px; padding: 8px 18px; background: #10b981; color: #fff; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; box-shadow: 0 2px 8px rgba(16,185,129,.4); }
        .print-btn:hover { background: #059669; }
    </style>
</head>
<body>
<button class="print-btn no-print" onclick="window.print()">&#x1F5A8; Stampa</button>

<div class="page">
    <table class="ddt">
        <!-- Row 1: Cedente | Title + a mezzo + N./del -->
        <tr>
            <td style="width:50%">
                <span class="lbl">Cedente: Ditta, dipendenza, domicilio o residenza, codice fiscale, partita IVA</span>
                <div class="val val-lg"><strong><?= Utils::e($pl['company_name']) ?></strong><?php
                    if (!empty($pl['company_address'])) echo "\n" . Utils::e($pl['company_address']);
                    $line3 = trim(
                        (!empty($pl['company_phone']) ? 'Tel: ' . $pl['company_phone'] : '') .
                        (!empty($pl['company_email']) ? '  ' . $pl['company_email'] : '')
                    );
                    if ($line3 !== '') echo "\n" . Utils::e($line3);
                    $fisc = trim(
                        (!empty($pl['company_vat'])    ? 'P.IVA ' . $pl['company_vat'] : '') .
                        (!empty($pl['company_tax_id']) ? '  C.F. ' . $pl['company_tax_id'] : '')
                    );
                    if ($fisc !== '') echo "\n" . Utils::e($fisc);
                ?></div>
            </td>
            <td style="width:50%">
                <div class="title-main">DOCUMENTO DI TRASPORTO</div>
                <div class="title-sub">(D.P.R. 472 del 14-08-1996 - D.P.R. 696 del 21-12-1996)</div>
                <div class="mezzo-row">a mezzo:
                    <span class="opt"><?= $radio($transportCedente) ?> cedente</span>
                    <span class="opt"><?= $radio($transportCessionario) ?> cessionario</span>
                </div>
                <div class="num-row">
                    N. <span class="under"><?= Utils::e($pl['pl_number']) ?></span>
                    del <span class="under"><?= Utils::formatDate($pl['pl_date']) ?></span>
                </div>
            </td>
        </tr>

        <!-- Row 2: Cessionario | Luogo di destinazione -->
        <tr>
            <td>
                <span class="lbl">Cessionario</span>
                <div class="val val-md"><strong><?= Utils::e($pl['customer_name']) ?></strong><?php
                    if (!empty($pl['customer_address'])) echo "\n" . Utils::e($pl['customer_address']);
                    if (!empty($pl['customer_vat']))     echo "\n" . Utils::e('VAT/C.F.: ' . $pl['customer_vat']);
                ?></div>
            </td>
            <td>
                <span class="lbl">Luogo di destinazione</span>
                <div class="val val-md"><?= Utils::e($pl['destination']) ?></div>
            </td>
        </tr>

        <!-- Row 3: Causale | N. ordine / del / conto -->
        <tr>
            <td>
                <span class="lbl">Causale del trasporto</span>
                <div class="val"><?= Utils::e($pl['causale']) ?></div>
            </td>
            <td>
                <table style="width:100%;border:none">
                    <tr>
                        <td style="border:none;padding:0 2mm 0 0;width:50%">
                            <span class="lbl">N. ordine</span>
                            <div class="val"><?= Utils::e($pl['order_number']) ?></div>
                            <span class="lbl" style="margin-top:1.5mm">del</span>
                            <div class="val"><?= !empty($pl['order_date']) ? Utils::formatDate($pl['order_date']) : '' ?></div>
                        </td>
                        <td style="border:none;padding:0;width:50%" class="opt-line">
                            <?= $radio($inConto) ?> in conto<br>
                            <?= $radio($aSaldo) ?> a saldo
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Goods table -->
    <table class="goods" style="margin-top:4mm">
        <thead>
            <tr>
                <th class="qh">Quantità</th>
                <th class="dc">Descrizione dei beni (natura e qualità)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td class="qc"><?= Utils::e($item['quantity']) ?></td>
                <td class="dc"><?= Utils::e($item['description']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php for ($i = 0; $i < $padCount; $i++): ?>
            <tr><td class="qc empty">&nbsp;</td><td class="dc empty">&nbsp;</td></tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <!-- Aspetto / colli / peso / porto -->
    <table class="ddt" style="margin-top:0;border-top:none">
        <tr>
            <td style="width:52%"><span class="lbl">Aspetto esteriore dei beni</span><div class="val"><?= Utils::e($pl['aspetto']) ?></div></td>
            <td style="width:16%"><span class="lbl">N. colli</span><div class="val"><?= Utils::e($pl['n_colli']) ?></div></td>
            <td style="width:16%"><span class="lbl">Peso kg.</span><div class="val"><?= Utils::e($pl['peso_kg']) ?></div></td>
            <td style="width:16%"><span class="lbl">Porto</span><div class="val"><?= Utils::e($pl['porto']) ?></div></td>
        </tr>
    </table>

    <!-- Consegna / trasportatore / firma conducente -->
    <table class="ddt" style="margin-top:4mm">
        <tr>
            <td style="width:34%">
                <span class="lbl">Consegna o inizio trasporto a mezzo</span>
                <div class="opt-line">
                    <?= $radio($deliveryCedente) ?> cedente<br>
                    <?= $radio($deliveryCessionario) ?> cessionario
                </div>
            </td>
            <td style="width:33%">
                <span class="lbl">Data / Ora trasporto</span>
                <div class="val"><?= !empty($pl['transport_date']) ? Utils::formatDate($pl['transport_date']) : '' ?><?= !empty($pl['transport_time']) ? ' — ' . Utils::e($pl['transport_time']) : '' ?></div>
            </td>
            <td style="width:33%">
                <span class="lbl">Firma conducente</span>
                <div class="sig-line"></div>
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <span class="lbl">Generalità del trasportatore</span>
                <div class="val val-md"><?= Utils::e($pl['carrier']) ?></div>
            </td>
        </tr>
    </table>

    <!-- Annotazioni / firma cessionario -->
    <table class="ddt" style="margin-top:4mm">
        <tr>
            <td style="width:60%">
                <span class="lbl">Annotazioni</span>
                <div class="val" style="min-height:26mm"><?= Utils::e($pl['notes'] ?? '') ?></div>
            </td>
            <td style="width:40%">
                <span class="lbl">Firma cessionario</span>
                <div class="sig-line" style="margin-top:22mm"></div>
                <div class="sig-cap" style="text-align:center;margin-top:1mm">Firma per ricevuta</div>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
