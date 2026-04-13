<?php
// Disable output buffering
if (ob_get_level()) ob_end_clean();

// Force errors to display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

echo "=== PHP DIAGNOSTIC TEST ===\n\n";

echo "PHP is executing: YES\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "PHP SAPI: " . PHP_SAPI . "\n";
echo "Server software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "\n\n";

echo "=== EXTENSIONS ===\n";
$needed = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'session', 'fileinfo', 'openssl'];
foreach ($needed as $ext) {
    echo str_pad($ext, 15) . ": " . (extension_loaded($ext) ? "LOADED" : "MISSING") . "\n";
}

echo "\n=== PATHS ===\n";
echo "Script file  : " . __FILE__ . "\n";
echo "Document root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'unknown') . "\n";
$parentDir = dirname(__DIR__);
echo "Parent dir   : " . $parentDir . "\n";

echo "\n=== .ENV FILE ===\n";
$envPath = $parentDir . '/.env';
if (file_exists($envPath)) {
    echo ".env exists  : YES\n";
    echo ".env readable: " . (is_readable($envPath) ? "YES" : "NO") . "\n";
} else {
    echo ".env exists  : NO (looking at: $envPath)\n";
}

echo "\n=== KEY FILES ===\n";
$files = [
    $parentDir . '/config/constants.php',
    $parentDir . '/config/database.php',
    $parentDir . '/src/Database.php',
    $parentDir . '/controllers/RepairController.php',
    $parentDir . '/views/repairs/create.php',
];
foreach ($files as $f) {
    $short = str_replace($parentDir, '', $f);
    echo str_pad($short, 40) . ": " . (file_exists($f) ? (is_readable($f) ? "OK" : "NOT READABLE") : "MISSING") . "\n";
}

echo "\n=== DATABASE CONNECTION TEST ===\n";
$dbConfigPath = $parentDir . '/config/database.php';
if (!file_exists($dbConfigPath)) {
    echo "database.php not found\n";
} else {
    $cfg = require $dbConfigPath;
    echo "Host  : " . ($cfg['host'] ?? 'not set') . "\n";
    echo "DBName: " . ($cfg['dbname'] ?? 'not set') . "\n";
    echo "User  : " . ($cfg['username'] ?? 'not set') . "\n";
    try {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $cfg['host'], $cfg['port'] ?? 3306, $cfg['dbname'], $cfg['charset'] ?? 'utf8mb4');
        $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "Connection: SUCCESS\n";
        $ver = $pdo->query('SELECT VERSION()')->fetchColumn();
        echo "MySQL version: $ver\n";
        // Check repairs table columns
        $cols = $pdo->query("SHOW COLUMNS FROM repairs")->fetchAll(PDO::FETCH_COLUMN);
        echo "repairs columns: " . implode(', ', $cols) . "\n";
    } catch (Exception $e) {
        echo "Connection: FAILED — " . $e->getMessage() . "\n";
    }
}

echo "\n=== PHP ERROR LOG (last 20 lines) ===\n";
$logPath = $parentDir . '/logs/app.log';
if (file_exists($logPath) && is_readable($logPath)) {
    $lines = array_slice(file($logPath), -20);
    echo implode('', $lines) ?: "(empty)\n";
} else {
    echo "No app.log found at: $logPath\n";
}

echo "\n=== END OF DIAGNOSTIC ===\n";
