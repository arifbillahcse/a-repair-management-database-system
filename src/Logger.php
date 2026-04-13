<?php
/**
 * Logger — writes to the activity_log table and optionally to a file.
 *
 * Usage:
 *   Logger::log('created', 'repair', 42, null, ['status' => 'in_progress']);
 *   Logger::log('status_changed', 'repair', 42, ['status' => 'in_progress'], ['status' => 'completed']);
 */
class Logger
{
    // ── Database activity log ─────────────────────────────────────────────────

    /**
     * @param string     $action     One of LOG_ACTIONS
     * @param string     $entityType 'repair' | 'customer' | 'invoice' | 'user' …
     * @param int|null   $entityId
     * @param array|null $oldValues  State before change (null for creates/views)
     * @param array|null $newValues  State after change  (null for deletes/views)
     */
    public static function log(
        string  $action,
        string  $entityType,
        ?int    $entityId  = null,
        ?array  $oldValues = null,
        ?array  $newValues = null
    ): void {
        try {
            $db = Database::getInstance();
            $db->insert('activity_log', [
                'user_id'     => Auth::id(),
                'action'      => $action,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'old_values'  => $oldValues  ? json_encode($oldValues,  JSON_UNESCAPED_UNICODE) : null,
                'new_values'  => $newValues  ? json_encode($newValues,  JSON_UNESCAPED_UNICODE) : null,
                'ip_address'  => self::clientIp(),
                'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            // Never let logging break the app
            self::fileLog('ERROR logging to DB: ' . $e->getMessage());
        }
    }

    // ── File / error logging ──────────────────────────────────────────────────

    public static function fileLog(string $message, string $level = 'INFO'): void
    {
        if (!filter_var($_ENV['LOG_ERRORS'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $logPath = APP_ROOT . '/logs/app.log';
        $logDir  = dirname($logPath);

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $line = sprintf(
            "[%s] [%s] [IP:%s] %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            self::clientIp(),
            $message
        );

        @file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
    }

    public static function error(string $message): void  { self::fileLog($message, 'ERROR'); }
    public static function warning(string $message): void { self::fileLog($message, 'WARN'); }
    public static function info(string $message): void    { self::fileLog($message, 'INFO'); }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function clientIp(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                // Take the first IP in a comma-separated list
                return trim(explode(',', $_SERVER[$key])[0]);
            }
        }
        return '0.0.0.0';
    }

    // ── Recent log fetch (for admin view) ────────────────────────────────────

    public static function getRecent(int $limit = 50): array
    {
        try {
            return Database::getInstance()->fetchAll(
                "SELECT l.*, u.username
                 FROM activity_log l
                 LEFT JOIN users u ON u.user_id = l.user_id
                 ORDER BY l.created_at DESC
                 LIMIT ?",
                [$limit]
            );
        } catch (Throwable) {
            return [];
        }
    }
}
