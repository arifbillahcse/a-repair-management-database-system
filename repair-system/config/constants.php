<?php
/**
 * Application-wide constants
 * Loaded once at bootstrap from public/index.php
 */

// ── Path constants ────────────────────────────────────────────────────────────
define('APP_ROOT',    dirname(__DIR__));                        // repair-system/
define('CONFIG_PATH', APP_ROOT . '/config');
define('SRC_PATH',    APP_ROOT . '/src');
define('VIEWS_PATH',  APP_ROOT . '/views');
define('MODELS_PATH', APP_ROOT . '/models');
define('CTRL_PATH',   APP_ROOT . '/controllers');
define('PUBLIC_PATH', APP_ROOT . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');

// ── URL base (resolved from .env or fallback) ─────────────────────────────────
define('BASE_URL', rtrim($_ENV['APP_URL'] ?? 'http://localhost/repair-system/public', '/'));

// ── Application meta ──────────────────────────────────────────────────────────
define('APP_NAME',    $_ENV['APP_NAME']  ?? 'Repair Management System');
define('APP_VERSION', '1.0.0');
define('APP_ENV',     $_ENV['APP_ENV']   ?? 'production');
define('APP_DEBUG',   filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN));

// ── Session ────────────────────────────────────────────────────────────────────
define('SESSION_TIMEOUT', (int)($_ENV['SESSION_TIMEOUT'] ?? 1800));   // 30 minutes
define('SESSION_NAME',    $_ENV['SESSION_NAME'] ?? 'repair_sys_sess');

// ── Pagination ────────────────────────────────────────────────────────────────
define('PAGE_SIZE',         20);
define('PAGE_SIZE_REPAIRS', 30);

// ── Upload limits ─────────────────────────────────────────────────────────────
define('UPLOAD_MAX_SIZE',      (int)($_ENV['UPLOAD_MAX_SIZE'] ?? 5242880));  // 5 MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// ── Repair statuses ────────────────────────────────────────────────────────────
define('REPAIR_STATUS', [
    'in_progress'       => 'In Progress',
    'on_hold'           => 'On Hold',
    'waiting_for_parts' => 'Waiting for Parts',
    'ready_for_pickup'  => 'Ready for Pickup',
    'completed'         => 'Completed',
    'collected'         => 'Collected',
    'cancelled'         => 'Cancelled',
]);

// Allowed forward-only status transitions
define('REPAIR_STATUS_FLOW', [
    'in_progress'       => ['on_hold', 'waiting_for_parts', 'completed', 'cancelled'],
    'on_hold'           => ['in_progress', 'waiting_for_parts', 'cancelled'],
    'waiting_for_parts' => ['in_progress', 'on_hold', 'cancelled'],
    'completed'         => ['ready_for_pickup'],
    'ready_for_pickup'  => ['collected', 'on_hold'],
    'collected'         => [],
    'cancelled'         => [],
]);

// Status badge CSS classes (mapped to style.css)
define('REPAIR_STATUS_CLASS', [
    'in_progress'       => 'badge-gray',
    'on_hold'           => 'badge-red',
    'waiting_for_parts' => 'badge-orange',
    'ready_for_pickup'  => 'badge-blue',
    'completed'         => 'badge-green',
    'collected'         => 'badge-green-dim',
    'cancelled'         => 'badge-dark',
]);

// ── Invoice statuses ────────────────────────────────────────────────────────────
define('INVOICE_STATUS', [
    'draft'           => 'Draft',
    'sent'            => 'Sent',
    'paid'            => 'Paid',
    'partially_paid'  => 'Partially Paid',
    'overdue'         => 'Overdue',
    'cancelled'       => 'Cancelled',
]);

define('INVOICE_STATUS_CLASS', [
    'draft'          => 'badge-gray',
    'sent'           => 'badge-blue',
    'paid'           => 'badge-green',
    'partially_paid' => 'badge-orange',
    'overdue'        => 'badge-red',
    'cancelled'      => 'badge-dark',
]);

// ── User roles ─────────────────────────────────────────────────────────────────
define('USER_ROLES', [
    'admin'       => 'Admin',
    'manager'     => 'Manager',
    'technician'  => 'Technician',
    'staff'       => 'Staff',
]);

// Role hierarchy (higher = more permissions)
define('ROLE_HIERARCHY', [
    'technician' => 1,
    'staff'      => 2,
    'manager'    => 3,
    'admin'      => 4,
]);

// ── Client types ───────────────────────────────────────────────────────────────
define('CLIENT_TYPES', [
    'individual'  => 'Individual',
    'company'     => 'Company',
    'freelancer'  => 'Freelancer',
    'colleague'   => 'Colleague',
]);

// ── Date / locale ─────────────────────────────────────────────────────────────
define('DATE_FORMAT',      'd/m/Y');
define('DATETIME_FORMAT',  'd/m/Y H:i');
define('DB_DATE_FORMAT',   'Y-m-d');
define('CURRENCY_SYMBOL',  '€');
define('CURRENCY_CODE',    'EUR');
define('DEFAULT_TAX_PCT',  22.00);

// ── Activity log actions ───────────────────────────────────────────────────────
define('LOG_ACTIONS', [
    'created',
    'updated',
    'deleted',
    'viewed',
    'login',
    'logout',
    'status_changed',
    'exported',
]);

// ── HTTP status codes used by the router ──────────────────────────────────────
define('HTTP_OK',                200);
define('HTTP_FOUND',             302);
define('HTTP_BAD_REQUEST',       400);
define('HTTP_UNAUTHORIZED',      401);
define('HTTP_FORBIDDEN',         403);
define('HTTP_NOT_FOUND',         404);
define('HTTP_SERVER_ERROR',      500);
