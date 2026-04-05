<?php
/**
 * Utils — global helper functions
 *
 * All methods are static for convenience; no instantiation needed.
 */
class Utils
{
    // ── Redirects ─────────────────────────────────────────────────────────────

    /**
     * Redirect to an internal path (relative to BASE_URL) or full URL.
     *
     * @param string $path    e.g. '/customers' or 'https://…'
     * @param int    $code    HTTP status code (302 by default)
     */
    public static function redirect(string $path, int $code = 302): never
    {
        $url = str_starts_with($path, 'http') ? $path : BASE_URL . $path;
        header('Location: ' . $url, true, $code);
        exit;
    }

    /** Redirect back to the referring page (or $fallback). */
    public static function redirectBack(string $fallback = '/'): never
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        self::redirect($ref ?: $fallback);
    }

    // ── Input sanitisation ────────────────────────────────────────────────────

    /** Strip tags, trim whitespace. Safe for display (not for DB — use PDO params). */
    public static function sanitize(?string $value): string
    {
        return htmlspecialchars(trim((string)$value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Sanitise a whole array of POST/GET values. */
    public static function sanitizeArray(array $data): array
    {
        return array_map([self::class, 'sanitize'], $data);
    }

    /** Cast to int; reject non-numeric inputs (return 0). */
    public static function intVal(?string $value): int
    {
        return (int)filter_var($value, FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);
    }

    /** Cast to float; reject non-numeric inputs (return 0.0). */
    public static function floatVal(?string $value): float
    {
        return (float)filter_var($value, FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0.0]]);
    }

    // ── Validation ────────────────────────────────────────────────────────────

    public static function isValidEmail(string $email): bool
    {
        return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /** Validate Italian mobile/landline numbers (very permissive — strips spaces/dashes). */
    public static function sanitizePhone(?string $phone): string
    {
        if ($phone === null || $phone === '') return '';
        return preg_replace('/[^\d+]/', '', $phone);
    }

    /** Italian VAT number: IT + 11 digits (or just 11 digits). */
    public static function isValidVat(?string $vat): bool
    {
        if (!$vat) return true; // optional field
        $clean = preg_replace('/^IT/i', '', $vat);
        return (bool)preg_match('/^\d{11}$/', $clean);
    }

    /** Italian fiscal code (codice fiscale): 16 alphanumeric characters. */
    public static function isValidTaxId(?string $taxId): bool
    {
        if (!$taxId) return true; // optional field
        return (bool)preg_match('/^[A-Z0-9]{16}$/i', $taxId);
    }

    public static function isValidPostalCode(?string $code): bool
    {
        if (!$code) return true; // optional
        return (bool)preg_match('/^\d{5}$/', $code);
    }

    // ── Formatting ────────────────────────────────────────────────────────────

    /**
     * Format a date string or timestamp.
     *
     * @param string|int|null $date   DB date string or Unix timestamp
     * @param string          $format PHP date format (defaults to app DATE_FORMAT)
     */
    public static function formatDate(mixed $date, string $format = DATE_FORMAT): string
    {
        if (!$date) return '—';
        $ts = is_int($date) ? $date : strtotime($date);
        return $ts ? date($format, $ts) : '—';
    }

    public static function formatDateTime(mixed $date): string
    {
        return self::formatDate($date, DATETIME_FORMAT);
    }

    /**
     * Format a monetary amount.
     * e.g. 1234.5 → "€ 1.234,50"  (Italian locale)
     */
    public static function formatCurrency(mixed $amount): string
    {
        if ($amount === null || $amount === '') return '—';
        return CURRENCY_SYMBOL . ' ' . number_format((float)$amount, 2, ',', '.');
    }

    /** How many days between two dates (positive = $b is in the future). */
    public static function daysBetween(string $dateA, string $dateB = 'now'): int
    {
        $a = new DateTime($dateA);
        $b = new DateTime($dateB);
        return (int)$a->diff($b)->days;
    }

    /** Human-friendly "2 days ago" relative time. */
    public static function timeAgo(string $date): string
    {
        $diff = time() - strtotime($date);
        return match (true) {
            $diff < 60      => 'just now',
            $diff < 3600    => floor($diff / 60)   . ' min ago',
            $diff < 86400   => floor($diff / 3600)  . ' h ago',
            $diff < 604800  => floor($diff / 86400)  . ' days ago',
            default         => date(DATE_FORMAT, strtotime($date)),
        };
    }

    // ── Flash messages ────────────────────────────────────────────────────────

    /**
     * Set a one-time flash message.
     *
     * @param string $type  'success' | 'error' | 'warning' | 'info'
     */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type][] = $message;
    }

    public static function flashSuccess(string $msg): void { self::flash('success', $msg); }
    public static function flashError(string $msg): void   { self::flash('error',   $msg); }
    public static function flashWarning(string $msg): void { self::flash('warning', $msg); }
    public static function flashInfo(string $msg): void    { self::flash('info',    $msg); }

    /**
     * Get and clear all flash messages.
     * Call this once in the layout (header.php) to render notifications.
     */
    public static function getFlashMessages(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }

    // ── Pagination ────────────────────────────────────────────────────────────

    /**
     * Build pagination data.
     *
     * @param  int   $total     Total record count
     * @param  int   $page      Current page (1-based)
     * @param  int   $perPage
     * @return array{total, page, perPage, totalPages, offset, hasPrev, hasNext}
     */
    public static function paginate(int $total, int $page = 1, int $perPage = PAGE_SIZE): array
    {
        $page       = max(1, $page);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page       = min($page, $totalPages);

        return [
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => $totalPages,
            'offset'     => ($page - 1) * $perPage,
            'hasPrev'    => $page > 1,
            'hasNext'    => $page < $totalPages,
        ];
    }

    // ── URL helpers ───────────────────────────────────────────────────────────

    /** Build a URL by appending query params. */
    public static function url(string $path, array $params = []): string
    {
        $base = BASE_URL . $path;
        return $params ? $base . '?' . http_build_query($params) : $base;
    }

    /** Return the current page number from GET['page']. */
    public static function currentPage(): int
    {
        return max(1, (int)($_GET['page'] ?? 1));
    }

    // ── String helpers ────────────────────────────────────────────────────────

    public static function truncate(?string $text, int $maxLen = 80): string
    {
        if ($text === null || $text === '') return '';
        return mb_strlen($text) > $maxLen
            ? mb_substr($text, 0, $maxLen) . '…'
            : $text;
    }

    public static function slugify(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }

    // ── File / upload helpers ─────────────────────────────────────────────────

    /** Generate a unique filename for an upload. */
    public static function uniqueFilename(string $originalName): string
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        return uniqid('file_', true) . '.' . $ext;
    }

    public static function formatFileSize(int $bytes): string
    {
        return match (true) {
            $bytes >= 1048576 => round($bytes / 1048576, 2) . ' MB',
            $bytes >= 1024    => round($bytes / 1024, 1)    . ' KB',
            default           => $bytes . ' B',
        };
    }

    // ── Security ──────────────────────────────────────────────────────────────

    /** Generate a cryptographically random token (hex string). */
    public static function generateToken(int $bytes = 16): string
    {
        return bin2hex(random_bytes($bytes));
    }

    /** Output an HTML-escaped value (shorthand for views). */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
