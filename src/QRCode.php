<?php
/**
 * QRCode — lightweight QR code generation wrapper
 *
 * Strategy (in order of availability):
 *  1. endroid/qr-code (Composer package — preferred)
 *  2. Google Charts API fallback (requires internet, for dev only)
 *  3. Simple text-only stub
 *
 * For production, install via Composer:
 *   composer require endroid/qr-code
 */
class QRCode
{
    // ── Generate a repair QR code ─────────────────────────────────────────────

    /**
     * Build the data string embedded in the QR code.
     * Format: REPAIR:{repair_id}|CUST:{customer_id}|SN:{serial}
     */
    public static function buildData(int $repairId, int $customerId, string $serial = ''): string
    {
        return sprintf(
            'REPAIR:%d|CUST:%d|SN:%s|URL:%s',
            $repairId,
            $customerId,
            $serial ?: 'N/A',
            BASE_URL . '/repairs/' . $repairId
        );
    }

    /**
     * Generate a unique QR code identifier for a repair ticket.
     * Stored in repairs.qr_code column.
     */
    public static function generateRepairCode(int $repairId): string
    {
        return 'QR-' . date('Y') . '-' . str_pad($repairId, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Return an <img> tag pointing to the QR code image.
     * Uses endroid/qr-code if available, otherwise Google Charts API.
     *
     * @param  string $data    The string to encode
     * @param  int    $size    Image size in pixels
     * @return string HTML img tag
     */
    public static function imgTag(string $data, int $size = 200): string
    {
        $src = self::getSrc($data, $size);
        $alt = 'QR Code';
        return sprintf(
            '<img src="%s" alt="%s" width="%d" height="%d" class="qr-code-img">',
            Utils::e($src), $alt, $size, $size
        );
    }

    /**
     * Return the URL/data-URI for the QR code image.
     */
    public static function getSrc(string $data, int $size = 200): string
    {
        // Strategy 1: endroid/qr-code (Composer)
        if (class_exists('Endroid\QrCode\QrCode')) {
            return self::generateWithEndroid($data, $size);
        }

        // Strategy 2: Google Charts API (fallback for dev)
        return self::googleChartsUrl($data, $size);
    }

    /**
     * Save a QR code PNG to disk and return the relative path.
     *
     * @param  string $data
     * @param  int    $repairId
     * @return string|null  Relative path under /public/uploads/ or null on failure
     */
    public static function saveToFile(string $data, int $repairId): ?string
    {
        if (!class_exists('Endroid\QrCode\QrCode')) {
            return null; // Can only save files with the Composer package
        }

        try {
            $dir = UPLOAD_PATH . '/qrcodes';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $filename = 'repair_' . $repairId . '.png';
            $path     = $dir . '/' . $filename;

            $qr = \Endroid\QrCode\QrCode::create($data)
                ->setSize(300)
                ->setMargin(10);

            $writer = new \Endroid\QrCode\Writer\PngWriter();
            $result = $writer->write($qr);
            $result->saveToFile($path);

            return 'uploads/qrcodes/' . $filename;
        } catch (Throwable $e) {
            Logger::error('QR save failed: ' . $e->getMessage());
            return null;
        }
    }

    // ── Private drivers ───────────────────────────────────────────────────────

    private static function generateWithEndroid(string $data, int $size): string
    {
        try {
            $qr = \Endroid\QrCode\QrCode::create($data)
                ->setSize($size)
                ->setMargin(10);

            $writer = new \Endroid\QrCode\Writer\PngWriter();
            $result = $writer->write($qr);

            return 'data:image/png;base64,' . base64_encode($result->getString());
        } catch (Throwable $e) {
            Logger::error('endroid/qr-code failed: ' . $e->getMessage());
            return self::googleChartsUrl($data, $size);
        }
    }

    private static function googleChartsUrl(string $data, int $size): string
    {
        return sprintf(
            'https://chart.googleapis.com/chart?chs=%dx%d&cht=qr&chl=%s&choe=UTF-8',
            $size, $size, urlencode($data)
        );
    }
}
