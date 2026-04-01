<?php
/**
 * =====================================================================
 * Customer CSV Import Script — tblClients migration
 * =====================================================================
 * Run from the command line:
 *
 *   php scripts/import_customers.php /path/to/tblClients.csv
 *
 * Or with options:
 *
 *   php scripts/import_customers.php tblClients.csv --dry-run
 *   php scripts/import_customers.php tblClients.csv --skip-duplicates
 *   php scripts/import_customers.php tblClients.csv --delimiter=";"
 *
 * CSV column mapping is configured in $COLUMN_MAP below.
 * The script handles UTF-8, ISO-8859-1 (Latin-1), and Windows-1252 encodings.
 *
 * Output: a log file is written to scripts/import_log_YYYY-MM-DD_HH-MM.txt
 * =====================================================================
 */

declare(strict_types=1);

// ── Guard: CLI only ───────────────────────────────────────────────────────────
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script must be run from the command line.');
}

// ── Bootstrap ─────────────────────────────────────────────────────────────────
define('APP_ROOT', dirname(__DIR__));

// Load .env
$envFile = APP_ROOT . '/config/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$k, $v] = array_map('trim', explode('=', $line, 2));
            $_ENV[$k] = $v; putenv("{$k}={$v}");
        }
    }
}

require APP_ROOT . '/config/constants.php';
require SRC_PATH  . '/Database.php';
require SRC_PATH  . '/Utils.php';
require SRC_PATH  . '/Auth.php';
require SRC_PATH  . '/Logger.php';
require MODELS_PATH . '/BaseModel.php';
require MODELS_PATH . '/Customer.php';

// ── Parse CLI arguments ───────────────────────────────────────────────────────
$args      = $argv ?? [];
$csvFile   = null;
$dryRun    = false;
$skipDups  = false;
$delimiter = ',';
$encoding  = 'auto'; // auto-detect

foreach (array_slice($args, 1) as $arg) {
    if ($arg === '--dry-run')          { $dryRun   = true; continue; }
    if ($arg === '--skip-duplicates')  { $skipDups = true; continue; }
    if (str_starts_with($arg, '--delimiter=')) {
        $delimiter = substr($arg, strlen('--delimiter=')); continue;
    }
    if (str_starts_with($arg, '--encoding=')) {
        $encoding  = substr($arg, strlen('--encoding=')); continue;
    }
    if (!str_starts_with($arg, '--')) {
        $csvFile = $arg;
    }
}

if (!$csvFile) {
    echo "Usage: php import_customers.php <file.csv> [--dry-run] [--skip-duplicates] [--delimiter=;]\n";
    exit(1);
}

if (!file_exists($csvFile)) {
    echo "Error: File not found: {$csvFile}\n";
    exit(1);
}

// ── Column map: CSV header → DB column ───────────────────────────────────────
// Adjust these keys to match your actual CSV headers.
// Values on the right are the customers table column names.
$COLUMN_MAP = [
    // tblClients field       => customers column
    'denominazione'           => 'full_name',
    'nome'                    => 'first_name',
    'cognome'                 => 'last_name',
    'ragione_sociale'         => 'full_name',     // alias
    'indirizzo'               => 'address',
    'cap'                     => 'postal_code',
    'citta'                   => 'city',
    'localita'                => 'city',          // alias
    'prov'                    => 'province',
    'provincia'               => 'province',      // alias
    'tel'                     => 'phone_landline',
    'telefono'                => 'phone_landline', // alias
    'cell'                    => 'phone_mobile',
    'cellulare'               => 'phone_mobile',  // alias
    'email'                   => 'email',
    'partita_iva'             => 'vat_number',
    'piva'                    => 'vat_number',    // alias
    'codice_fiscale'          => 'tax_id',
    'cf'                      => 'tax_id',        // alias
    'note'                    => 'notes',
    'tipo_cliente'            => 'client_type',
    'data_inserimento'        => 'customer_since',
    // Generic fallbacks
    'full_name'               => 'full_name',
    'first_name'              => 'first_name',
    'last_name'               => 'last_name',
    'phone_mobile'            => 'phone_mobile',
    'phone_landline'          => 'phone_landline',
    'vat_number'              => 'vat_number',
    'tax_id'                  => 'tax_id',
    'postal_code'             => 'postal_code',
];

// ── Open log file ─────────────────────────────────────────────────────────────
$logFile = __DIR__ . '/import_log_' . date('Y-m-d_H-i') . '.txt';
$log = fopen($logFile, 'w');

function logLine(string $msg): void
{
    global $log;
    $line = '[' . date('H:i:s') . '] ' . $msg . PHP_EOL;
    echo $line;
    fwrite($log, $line);
}

logLine("=== Customer Import Script ===");
logLine("File      : {$csvFile}");
logLine("Dry run   : " . ($dryRun ? 'YES (no DB writes)' : 'NO'));
logLine("Skip dups : " . ($skipDups ? 'YES' : 'NO (update existing)'));
logLine("Delimiter : '{$delimiter}'");
logLine("---");

// ── Detect file encoding ──────────────────────────────────────────────────────
function detectEncoding(string $file): string
{
    $sample = file_get_contents($file, false, null, 0, 4096);
    if (mb_detect_encoding($sample, ['UTF-8'], true)) return 'UTF-8';
    return 'ISO-8859-1';
}

$fileEncoding = ($encoding === 'auto') ? detectEncoding($csvFile) : $encoding;
logLine("Encoding  : {$fileEncoding}");

// ── Open CSV ──────────────────────────────────────────────────────────────────
$fp = fopen($csvFile, 'r');
if (!$fp) {
    logLine("ERROR: Cannot open file.");
    exit(1);
}

// Read header row
$rawHeader = fgetcsv($fp, 0, $delimiter);
if (!$rawHeader) {
    logLine("ERROR: Empty file or unreadable header.");
    exit(1);
}

// Normalise header: lowercase, trim, convert encoding
$headers = array_map(function ($h) use ($fileEncoding): string {
    $h = trim($h);
    if ($fileEncoding !== 'UTF-8') {
        $h = mb_convert_encoding($h, 'UTF-8', $fileEncoding);
    }
    return strtolower(preg_replace('/\s+/', '_', $h));
}, $rawHeader);

logLine("CSV headers: " . implode(', ', $headers));

// Map CSV columns to DB columns
$colIndex = []; // db_column => csv_index
foreach ($headers as $idx => $hdr) {
    // Strip BOM from first header
    $hdr = ltrim($hdr, "\xEF\xBB\xBF");
    if (isset($COLUMN_MAP[$hdr])) {
        $dbCol = $COLUMN_MAP[$hdr];
        // First match wins
        if (!isset($colIndex[$dbCol])) {
            $colIndex[$dbCol] = $idx;
        }
    }
}

logLine("Mapped columns: " . implode(', ', array_keys($colIndex)));

if (empty($colIndex)) {
    logLine("ERROR: No recognisable columns found. Check column map in script.");
    exit(1);
}

// ── Counters ──────────────────────────────────────────────────────────────────
$created  = 0;
$updated  = 0;
$skipped  = 0;
$errors   = 0;
$rowNum   = 1;

$model = new Customer();

// ── Process rows ──────────────────────────────────────────────────────────────
while (($row = fgetcsv($fp, 0, $delimiter)) !== false) {
    $rowNum++;

    // Convert encoding
    if ($fileEncoding !== 'UTF-8') {
        $row = array_map(
            fn($v) => mb_convert_encoding((string)$v, 'UTF-8', $fileEncoding),
            $row
        );
    }

    // Skip blank rows
    if (empty(array_filter($row))) {
        continue;
    }

    // Build data array from mapped columns
    $data = [];
    foreach ($colIndex as $dbCol => $csvIdx) {
        $data[$dbCol] = trim($row[$csvIdx] ?? '');
    }

    // ── Normalise / clean values ──────────────────────────────────────────────

    // full_name fallback
    if (empty($data['full_name'])) {
        $fn = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
        $data['full_name'] = $fn ?: null;
    }
    if (empty($data['full_name'])) {
        logLine("Row {$rowNum}: SKIPPED — no name found.");
        $skipped++;
        continue;
    }

    // Phone sanitisation
    if (!empty($data['phone_mobile'])) {
        $data['phone_mobile'] = preg_replace('/[^\d+]/', '', $data['phone_mobile']);
    }
    if (!empty($data['phone_landline'])) {
        $data['phone_landline'] = preg_replace('/[^\d+]/', '', $data['phone_landline']);
    }

    // Email normalisation
    if (!empty($data['email'])) {
        $data['email'] = strtolower(trim($data['email']));
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $data['email'] = ''; // discard invalid
        }
    }

    // VAT / Tax ID — uppercase, strip spaces
    foreach (['vat_number', 'tax_id'] as $field) {
        if (!empty($data[$field])) {
            $data[$field] = strtoupper(preg_replace('/\s+/', '', $data[$field]));
        }
    }

    // Province — max 5 chars, uppercase
    if (!empty($data['province'])) {
        $data['province'] = strtoupper(substr($data['province'], 0, 5));
    }

    // Client type normalisation
    if (!empty($data['client_type'])) {
        $typeMap = [
            'privato'       => 'individual',
            'private'       => 'individual',
            'azienda'       => 'company',
            'company'       => 'company',
            'libero_professionista' => 'freelancer',
            'freelancer'    => 'freelancer',
        ];
        $data['client_type'] = $typeMap[strtolower($data['client_type'])] ?? 'individual';
    } else {
        $data['client_type'] = 'individual';
    }

    // Status default
    $data['status'] = 'active';

    // customer_since — try to parse date
    if (!empty($data['customer_since'])) {
        $ts = strtotime($data['customer_since']);
        $data['customer_since'] = $ts ? date('Y-m-d', $ts) : null;
    } else {
        $data['customer_since'] = null;
    }

    // Remove empty strings → null for optional columns
    foreach ($data as $k => $v) {
        if ($v === '') $data[$k] = null;
    }

    // ── Dry run: just validate ────────────────────────────────────────────────
    if ($dryRun) {
        logLine("Row {$rowNum}: WOULD import \"{$data['full_name']}\"");
        $created++;
        continue;
    }

    // ── Upsert ────────────────────────────────────────────────────────────────
    try {
        // Check for existing customer
        $existing = null;
        if (!empty($data['email'])) {
            $existing = $model->findByEmail($data['email']);
        }
        if (!$existing && !empty($data['phone_mobile'])) {
            $existing = $model->findByPhone($data['phone_mobile']);
        }

        if ($existing) {
            if ($skipDups) {
                logLine("Row {$rowNum}: SKIPPED duplicate — \"{$data['full_name']}\" (ID #{$existing['customer_id']})");
                $skipped++;
            } else {
                $model->update($existing['customer_id'], $data);
                logLine("Row {$rowNum}: UPDATED \"{$data['full_name']}\" (ID #{$existing['customer_id']})");
                $updated++;
            }
        } else {
            $id = $model->create($data);
            logLine("Row {$rowNum}: CREATED \"{$data['full_name']}\" (ID #{$id})");
            $created++;
        }
    } catch (Throwable $ex) {
        logLine("Row {$rowNum}: ERROR — " . $ex->getMessage() . " | Data: " . json_encode($data));
        $errors++;
    }

    // Progress indicator every 500 rows
    if ($rowNum % 500 === 0) {
        logLine("--- Progress: {$rowNum} rows processed ---");
    }
}

fclose($fp);

// ── Summary ───────────────────────────────────────────────────────────────────
logLine("=== Import Complete ===");
logLine("Total rows processed : " . ($rowNum - 1));
logLine("Created              : {$created}");
logLine("Updated              : {$updated}");
logLine("Skipped              : {$skipped}");
logLine("Errors               : {$errors}");
logLine("Log saved to         : {$logFile}");

fclose($log);
exit($errors > 0 ? 1 : 0);
