<?php
/**
 * =====================================================================
 * REPAIR MANAGEMENT SYSTEM — Application Entry Point
 * =====================================================================
 * All web requests are routed here by .htaccess.
 * Boot sequence:
 *  1. Define APP_ROOT
 *  2. Load .env
 *  3. Load constants
 *  4. Autoload src/ classes
 *  5. Start session
 *  6. Register routes
 *  7. Dispatch
 */

declare(strict_types=1);

// ── 0. Early error capture (logs to file before exception handler is ready) ───
$_earlyLogPath = dirname(__DIR__) . '/logs/app.log';
set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) use ($_earlyLogPath): bool {
    if ($errno === E_ERROR || $errno === E_PARSE || $errno === E_CORE_ERROR || $errno === E_COMPILE_ERROR) {
        @file_put_contents($_earlyLogPath,
            '[' . date('Y-m-d H:i:s') . '] [PHP_ERROR] ' . $errstr . ' in ' . $errfile . ':' . $errline . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
    return false; // let PHP's own handler also run
});

// ── 1. App root ───────────────────────────────────────────────────────────────
define('APP_ROOT', dirname(__DIR__));

// ── 2. Load .env ──────────────────────────────────────────────────────────────
$envFile = APP_ROOT . '/config/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $val] = array_map('trim', explode('=', $line, 2));
            $_ENV[$key]  = $val;
            putenv("{$key}={$val}");
        }
    }
}

// ── 3. Constants ──────────────────────────────────────────────────────────────
require APP_ROOT . '/config/constants.php';

// ── 4. PHP error handling (show in dev, hide in prod) ────────────────────────
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
    set_exception_handler(function (Throwable $e) {
        Logger::error($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        http_response_code(500);
        echo '<!doctype html><html><head><title>Server Error</title>
              <link rel="stylesheet" href="' . BASE_URL . '/css/style.css"></head><body>
              <div class="error-page">
              <div class="error-code">500</div>
              <h1 class="error-title">Internal Server Error</h1>
              <p class="error-message">Something went wrong. Please try again later.</p>
              <a href="' . BASE_URL . '/" class="btn btn-primary">Go Home</a>
              </div></body></html>';
        exit;
    });
}

// ── 5. Autoload (src/, models/, controllers/) ─────────────────────────────────
spl_autoload_register(function (string $class): void {
    $dirs = [SRC_PATH, MODELS_PATH, CTRL_PATH];
    foreach ($dirs as $dir) {
        $file = $dir . '/' . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// ── 6. Session ────────────────────────────────────────────────────────────────
Auth::startSession();

// ── 7. Security headers ───────────────────────────────────────────────────────
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// ── 8. Router ─────────────────────────────────────────────────────────────────
$router = new Router();

// Auth
$router->get( '/login',    'AuthController@showLogin');
$router->post('/login',    'AuthController@login');
$router->get( '/logout',   'AuthController@logout');
$router->get( '/register', 'AuthController@showRegister');
$router->post('/register', 'AuthController@register');

// Dashboard
$router->get('/', 'DashboardController@index');

// Repairs
$router->get( '/repairs',                  'RepairController@index');
$router->get( '/repairs/create',           'RepairController@create');
$router->post('/repairs',                  'RepairController@store');
$router->get( '/repairs/:id',              'RepairController@show');
$router->get( '/repairs/:id/edit',         'RepairController@edit');
$router->post('/repairs/:id',              'RepairController@update');
$router->post('/repairs/:id/delete',       'RepairController@destroy');
$router->post('/repairs/:id/status',       'RepairController@updateStatus');
$router->post('/repairs/:id/photo',        'RepairController@uploadPhoto');
$router->post('/repairs/:id/photo/delete', 'RepairController@deletePhoto');
$router->get( '/repairs/:id/print',        'RepairController@printRepair');
$router->get( '/api/customers/search',     'RepairController@customerSearch');
$router->get( '/api/repairs/search',       'RepairController@repairSearch');
// $router->get( '/api/repairs/qr',           'RepairController@qrLookup'); // QR disabled

// Customers
$router->get( '/customers',                'CustomerController@index');
$router->get( '/customers/export',         'CustomerController@export');       // CSV download
$router->get( '/customers/create',         'CustomerController@create');
$router->post('/customers',                'CustomerController@store');
$router->get( '/customers/:id',            'CustomerController@show');
$router->get( '/customers/:id/edit',       'CustomerController@edit');
$router->post('/customers/:id',            'CustomerController@update');
$router->post('/customers/:id/delete',     'CustomerController@destroy');
$router->get( '/api/customers/autocomplete','CustomerController@autocomplete'); // AJAX

// Credit Notes
$router->get( '/credit-notes',                 'CreditNoteController@index');
$router->get( '/credit-notes/create',          'CreditNoteController@create');
$router->post('/credit-notes',                 'CreditNoteController@store');
$router->get( '/credit-notes/:id',             'CreditNoteController@show');
$router->get( '/credit-notes/:id/edit',        'CreditNoteController@edit');
$router->post('/credit-notes/:id',             'CreditNoteController@update');
$router->post('/credit-notes/:id/delete',      'CreditNoteController@destroy');
$router->get( '/credit-notes/:id/print',       'CreditNoteController@printCN');

// Personal Notes
$router->get( '/personal-notes',               'PersonalNoteController@index');
$router->get( '/personal-notes/create',        'PersonalNoteController@create');
$router->post('/personal-notes',               'PersonalNoteController@store');
$router->get( '/personal-notes/:id',           'PersonalNoteController@show');
$router->get( '/personal-notes/:id/edit',      'PersonalNoteController@edit');
$router->post('/personal-notes/:id',           'PersonalNoteController@update');
$router->post('/personal-notes/:id/delete',    'PersonalNoteController@destroy');
$router->post('/personal-notes/:id/toggle',    'PersonalNoteController@toggle');

// Invoices
$router->get( '/invoices',                 'InvoiceController@index');
$router->post('/invoices',                 'InvoiceController@store');
$router->get( '/invoices/:id',             'InvoiceController@show');
$router->get( '/invoices/:id/print',       'InvoiceController@printInvoice');
$router->post('/invoices/:id/paid',        'InvoiceController@markPaid');
$router->post('/invoices/:id/send',        'InvoiceController@markSent');
$router->post('/invoices/:id/delete',      'InvoiceController@destroy');
$router->get( '/repairs/:id/invoice',      'InvoiceController@createFromRepair');

// Reports
$router->get( '/reports',                  'ReportController@index');

// Staff
$router->get( '/staff',                    'StaffController@index');
$router->get( '/staff/create',             'StaffController@create');
$router->post('/staff',                    'StaffController@store');
$router->get( '/staff/:id',                'StaffController@show');
$router->get( '/staff/:id/edit',           'StaffController@edit');
$router->post('/staff/:id',                'StaffController@update');
$router->post('/staff/:id/delete',         'StaffController@destroy');

// Import (Admin only)
$router->get(  '/import',                            'ImportController@index');
$router->post( '/import/upload',                     'ImportController@upload');
$router->get(  '/import/summary',                    'ImportController@summary');
$router->get(  '/import/template/:type',             'ImportController@downloadTemplate');

// Admin
$router->get(  '/admin/settings',                    'AdminController@settings');
$router->post( '/admin/settings',                    'AdminController@settings');
$router->get(  '/admin/sysinfo',                     'AdminController@sysinfo');
$router->get(  '/admin/users',                       'AdminController@users');
$router->post( '/admin/users/:id/toggle',            'AdminController@toggleUser');
$router->post( '/admin/users/:id/reset-password',    'AdminController@resetPassword');

// ── 9. Dispatch ───────────────────────────────────────────────────────────────
$router->dispatch();
