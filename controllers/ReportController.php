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
}
