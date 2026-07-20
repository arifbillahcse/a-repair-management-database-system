<?php
class ReportController
{
    public function index(): void
    {
        Auth::requireRole('manager');

        $db = Database::getInstance();

        // ── Date range detection ───────────────────────────────────────────────
        $range    = $_GET['range']     ?? 'this_month';
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo   = trim($_GET['date_to']   ?? '');

        switch ($range) {
            case 'this_year':
                $start      = date('Y-01-01');
                $end        = date('Y-12-31');
                $rangeLabel = 'Year ' . date('Y');
                break;
            case 'last_3m':
                $start      = date('Y-m-d', strtotime('-3 months'));
                $end        = date('Y-m-d');
                $rangeLabel = 'Last 3 Months';
                break;
            case 'last_6m':
                $start      = date('Y-m-d', strtotime('-6 months'));
                $end        = date('Y-m-d');
                $rangeLabel = 'Last 6 Months';
                break;
            case 'last_12m':
                $start      = date('Y-m-d', strtotime('-12 months'));
                $end        = date('Y-m-d');
                $rangeLabel = 'Last 12 Months';
                break;
            case 'custom':
                $start = $dateFrom ?: date('Y-m-01');
                $end   = $dateTo   ?: date('Y-m-d');
                if ($start > $end) { [$start, $end] = [$end, $start]; }
                $rangeLabel = date('d M Y', strtotime($start)) . ' – ' . date('d M Y', strtotime($end));
                break;
            default:
                $range      = 'this_month';
                $start      = date('Y-m-01');
                $end        = date('Y-m-t');
                $rangeLabel = date('F Y');
        }

        // ── Repair counts for selected period ─────────────────────────────────
        $periodRepairs = $db->fetchOne(
            "SELECT
                COUNT(*)                                          AS total,
                SUM(status IN ('completed','collected'))           AS completed,
                SUM(status = 'in_progress')                       AS in_progress,
                SUM(status = 'on_hold')                           AS on_hold,
                SUM(status = 'waiting_for_parts')                 AS waiting_for_parts,
                SUM(status = 'ready_for_pickup')                  AS ready_for_pickup
             FROM repairs
             WHERE DATE(date_in) BETWEEN ? AND ?",
            [$start, $end]
        ) ?? [];

        // Active repairs — always live, not range-filtered (current queue)
        $liveActive = $db->fetchOne(
            "SELECT COUNT(*) AS total,
                    SUM(status = 'in_progress')      AS in_progress,
                    SUM(status = 'on_hold')           AS on_hold,
                    SUM(status = 'waiting_for_parts') AS waiting_for_parts,
                    SUM(status = 'ready_for_pickup')  AS ready_for_pickup
             FROM repairs
             WHERE status IN ('in_progress','on_hold','waiting_for_parts','ready_for_pickup')"
        ) ?? [];

        // ── Status breakdown (all-time, for the status bars) ──────────────────
        $statusRows = $db->fetchAll("SELECT status, COUNT(*) AS cnt FROM repairs GROUP BY status");
        $statusCounts = [];
        foreach ($statusRows as $r) { $statusCounts[$r['status']] = (int)$r['cnt']; }

        // ── Invoice / revenue for selected period ─────────────────────────────
        $periodRevenue = $db->fetchOne(
            "SELECT
                COALESCE(SUM(total_amount),0) AS revenue,
                COALESCE(SUM(amount_paid),0)  AS paid,
                COUNT(*)                       AS count
             FROM invoices
             WHERE DATE(invoice_date) BETWEEN ? AND ?
               AND status != 'cancelled'",
            [$start, $end]
        ) ?? [];

        // ── Top 10 customers for period ───────────────────────────────────────
        $topCustomers = $db->fetchAll(
            "SELECT c.customer_id, c.full_name,
                    COUNT(DISTINCT r.repair_id)     AS total_repairs,
                    COALESCE(SUM(i.total_amount),0)  AS total_billed,
                    COALESCE(SUM(i.amount_paid),0)   AS total_paid
             FROM customers c
             LEFT JOIN invoices i
                ON i.customer_id = c.customer_id
               AND i.status != 'cancelled'
               AND DATE(i.invoice_date) BETWEEN ? AND ?
             LEFT JOIN repairs r
                ON r.customer_id = c.customer_id
               AND DATE(r.date_in) BETWEEN ? AND ?
             GROUP BY c.customer_id
             HAVING total_billed > 0
             ORDER BY total_billed DESC
             LIMIT 10",
            [$start, $end, $start, $end]
        );

        // ── Staff performance for period ──────────────────────────────────────
        $staffStats = $db->fetchAll(
            "SELECT s.staff_id,
                    CONCAT(s.first_name,' ',s.last_name)        AS full_name,
                    COUNT(r.repair_id)                           AS total_repairs,
                    SUM(r.status IN ('completed','collected'))   AS completed,
                    SUM(r.status = 'in_progress')                AS in_progress
             FROM staff s
             LEFT JOIN repairs r
                ON r.staff_id = s.staff_id
               AND DATE(r.date_in) BETWEEN ? AND ?
             WHERE s.is_active = 1
             GROUP BY s.staff_id
             ORDER BY total_repairs DESC",
            [$start, $end]
        );

        // ── Revenue chart (month-by-month within selected period) ─────────────
        $monthlyRev = $db->fetchAll(
            "SELECT DATE_FORMAT(invoice_date,'%b %Y') AS month,
                    SUM(total_amount) AS revenue,
                    SUM(amount_paid)  AS paid
             FROM invoices
             WHERE DATE(invoice_date) BETWEEN ? AND ?
               AND status != 'cancelled'
             GROUP BY DATE_FORMAT(invoice_date,'%Y-%m')
             ORDER BY MIN(invoice_date)",
            [$start, $end]
        );

        // ── Repairs-by-month chart (within selected period) ───────────────────
        $repairsByMonth = $db->fetchAll(
            "SELECT DATE_FORMAT(date_in,'%b %Y') AS month, COUNT(*) AS count
             FROM repairs
             WHERE DATE(date_in) BETWEEN ? AND ?
             GROUP BY DATE_FORMAT(date_in,'%Y-%m')
             ORDER BY MIN(date_in)",
            [$start, $end]
        );

        // ── Inventory & sales (products module) ───────────────────────────────
        $productModel = new Product();
        $saleModel    = new Sale();
        $stockStats   = $productModel->getStockStats();
        $lowStock     = $productModel->getLowStock(10);
        $bestSellers  = $productModel->getBestSellers($start, $end, 10);
        $salesPeriod  = $saleModel->getRangeStats($start, $end);

        require VIEWS_PATH . '/reports/index.php';
    }

    // ── GET /reports/client-types ─────────────────────────────────────────────
    /**
     * Colleague vs Private breakdown: repairs (count) and revenue (€) split by
     * customer type, shown monthly (for a chosen year) and yearly (all years).
     *
     * "Private" = every customer that is NOT a colleague (individual + company).
     *
     * Repairs and invoices are aggregated in SEPARATE queries and merged in PHP
     * by period — joining both onto customers in one query would cross-multiply
     * the figures (the same fan-out bug fixed earlier in Customer::getStats()).
     */
    public function clientTypes(): void
    {
        Auth::requireRole('manager');
        $db = Database::getInstance();

        // Years that actually have data (repairs or non-cancelled invoices)
        $yearRows = $db->fetchAll(
            "SELECT yr FROM (
                SELECT YEAR(date_in) AS yr FROM repairs WHERE date_in IS NOT NULL
                UNION
                SELECT YEAR(invoice_date) AS yr FROM invoices
                    WHERE invoice_date IS NOT NULL AND status != 'cancelled'
             ) t
             WHERE yr IS NOT NULL
             ORDER BY yr DESC"
        );
        $availYears = array_map('intval', array_column($yearRows, 'yr'));
        if (!$availYears) { $availYears = [(int)date('Y')]; }

        $year = (int)($_GET['year'] ?? date('Y'));
        if (!in_array($year, $availYears, true)) { $year = $availYears[0]; }

        // ── Repairs per month (selected year), split by type ──────────────────
        $repMonthRows = $db->fetchAll(
            "SELECT MONTH(r.date_in) AS mo,
                    SUM(c.client_type =  'colleague') AS colleague_repairs,
                    SUM(c.client_type <> 'colleague') AS private_repairs
             FROM repairs r
             JOIN customers c ON c.customer_id = r.customer_id
             WHERE YEAR(r.date_in) = ?
             GROUP BY MONTH(r.date_in)",
            [$year]
        );

        // ── Revenue per month (selected year), split by type ──────────────────
        $revMonthRows = $db->fetchAll(
            "SELECT MONTH(i.invoice_date) AS mo,
                    COALESCE(SUM(CASE WHEN c.client_type =  'colleague' THEN i.total_amount END),0) AS colleague_billed,
                    COALESCE(SUM(CASE WHEN c.client_type =  'colleague' THEN i.amount_paid  END),0) AS colleague_paid,
                    COALESCE(SUM(CASE WHEN c.client_type <> 'colleague' THEN i.total_amount END),0) AS private_billed,
                    COALESCE(SUM(CASE WHEN c.client_type <> 'colleague' THEN i.amount_paid  END),0) AS private_paid
             FROM invoices i
             JOIN customers c ON c.customer_id = i.customer_id
             WHERE YEAR(i.invoice_date) = ? AND i.status != 'cancelled'
             GROUP BY MONTH(i.invoice_date)",
            [$year]
        );

        // Merge into a 12-month table (Jan..Dec) for the selected year
        $byMonth = [];
        for ($m = 1; $m <= 12; $m++) {
            $byMonth[$m] = [
                'label'             => date('M', mktime(0, 0, 0, $m, 1)),
                'colleague_repairs' => 0, 'private_repairs' => 0,
                'colleague_billed'  => 0, 'colleague_paid'  => 0,
                'private_billed'    => 0, 'private_paid'    => 0,
            ];
        }
        foreach ($repMonthRows as $r) {
            $m = (int)$r['mo'];
            if (!isset($byMonth[$m])) { continue; }
            $byMonth[$m]['colleague_repairs'] = (int)$r['colleague_repairs'];
            $byMonth[$m]['private_repairs']   = (int)$r['private_repairs'];
        }
        foreach ($revMonthRows as $r) {
            $m = (int)$r['mo'];
            if (!isset($byMonth[$m])) { continue; }
            $byMonth[$m]['colleague_billed'] = (float)$r['colleague_billed'];
            $byMonth[$m]['colleague_paid']   = (float)$r['colleague_paid'];
            $byMonth[$m]['private_billed']   = (float)$r['private_billed'];
            $byMonth[$m]['private_paid']     = (float)$r['private_paid'];
        }

        // ── Repairs per year (all years), split by type ───────────────────────
        $repYearRows = $db->fetchAll(
            "SELECT YEAR(r.date_in) AS yr,
                    SUM(c.client_type =  'colleague') AS colleague_repairs,
                    SUM(c.client_type <> 'colleague') AS private_repairs
             FROM repairs r
             JOIN customers c ON c.customer_id = r.customer_id
             WHERE r.date_in IS NOT NULL
             GROUP BY YEAR(r.date_in)"
        );

        // ── Revenue per year (all years), split by type ───────────────────────
        $revYearRows = $db->fetchAll(
            "SELECT YEAR(i.invoice_date) AS yr,
                    COALESCE(SUM(CASE WHEN c.client_type =  'colleague' THEN i.total_amount END),0) AS colleague_billed,
                    COALESCE(SUM(CASE WHEN c.client_type =  'colleague' THEN i.amount_paid  END),0) AS colleague_paid,
                    COALESCE(SUM(CASE WHEN c.client_type <> 'colleague' THEN i.total_amount END),0) AS private_billed,
                    COALESCE(SUM(CASE WHEN c.client_type <> 'colleague' THEN i.amount_paid  END),0) AS private_paid
             FROM invoices i
             JOIN customers c ON c.customer_id = i.customer_id
             WHERE i.invoice_date IS NOT NULL AND i.status != 'cancelled'
             GROUP BY YEAR(i.invoice_date)"
        );

        // Merge yearly repairs + revenue keyed by year
        $byYear = [];
        $blank  = [
            'colleague_repairs' => 0, 'private_repairs' => 0,
            'colleague_billed'  => 0, 'colleague_paid'  => 0,
            'private_billed'    => 0, 'private_paid'    => 0,
        ];
        foreach ($repYearRows as $r) {
            $y = (int)$r['yr'];
            $byYear[$y] = ($byYear[$y] ?? $blank);
            $byYear[$y]['colleague_repairs'] = (int)$r['colleague_repairs'];
            $byYear[$y]['private_repairs']   = (int)$r['private_repairs'];
        }
        foreach ($revYearRows as $r) {
            $y = (int)$r['yr'];
            $byYear[$y] = ($byYear[$y] ?? $blank);
            $byYear[$y]['colleague_billed'] = (float)$r['colleague_billed'];
            $byYear[$y]['colleague_paid']   = (float)$r['colleague_paid'];
            $byYear[$y]['private_billed']   = (float)$r['private_billed'];
            $byYear[$y]['private_paid']     = (float)$r['private_paid'];
        }
        krsort($byYear);

        // Totals for the selected year's KPI cards
        $yearTotals = $byYear[$year] ?? $blank;

        require VIEWS_PATH . '/reports/client-types.php';
    }
}
