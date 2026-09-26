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

        // ── Private vs Colleague revenue, selected period ──────────────────────
        // Income = repair's own Actual Amount (counts as soon as priced, no
        // invoice required) — the same basis used by the Colleague Report.
        // Attributed by Out/Delivery date, not date_in — a repair checked in
        // long ago but only finished/collected in this period should count
        // as this period's revenue.
        // Uses COALESCE(date_out, collection_date): date_out is only set by
        // the app's own status-change flow, but CSV-imported repairs only
        // ever get collection_date populated — the same fallback already
        // used by the Repairs list's "Out Date/Delivery" column, so revenue
        // matches what's actually shown on screen.
        // Invoiced = formal invoice totals for the same split, shown as a
        // secondary figure. Repairs/invoices aggregated separately and merged
        // to avoid the join fan-out bug.
        $clientIncomeRow = $db->fetchOne(
            "SELECT
                COALESCE(SUM(CASE WHEN c.client_type =  'colleague' THEN r.actual_amount END),0) AS colleague_income,
                COALESCE(SUM(CASE WHEN c.client_type <> 'colleague' THEN r.actual_amount END),0) AS private_income
             FROM repairs r
             JOIN customers c ON c.customer_id = r.customer_id
             WHERE DATE(COALESCE(r.date_out, r.collection_date)) BETWEEN ? AND ?",
            [$start, $end]
        ) ?? [];
        $privateIncome   = (float)($clientIncomeRow['private_income']   ?? 0);
        $colleagueIncome = (float)($clientIncomeRow['colleague_income'] ?? 0);

        $clientInvoicedRow = $db->fetchOne(
            "SELECT
                COALESCE(SUM(CASE WHEN c.client_type =  'colleague' THEN i.total_amount END),0) AS colleague_billed,
                COALESCE(SUM(CASE WHEN c.client_type <> 'colleague' THEN i.total_amount END),0) AS private_billed
             FROM invoices i
             JOIN customers c ON c.customer_id = i.customer_id
             WHERE DATE(i.invoice_date) BETWEEN ? AND ? AND i.status != 'cancelled'",
            [$start, $end]
        ) ?? [];
        $privateBilled   = (float)($clientInvoicedRow['private_billed']   ?? 0);
        $colleagueBilled = (float)($clientInvoicedRow['colleague_billed'] ?? 0);

        // ── Monthly trend (last 12 months), Private vs Colleague income ────────
        $monthlyClientRows = $db->fetchAll(
            "SELECT DATE_FORMAT(COALESCE(r.date_out, r.collection_date),'%Y-%m') AS ym,
                    COALESCE(SUM(CASE WHEN c.client_type =  'colleague' THEN r.actual_amount END),0) AS colleague_income,
                    COALESCE(SUM(CASE WHEN c.client_type <> 'colleague' THEN r.actual_amount END),0) AS private_income
             FROM repairs r
             JOIN customers c ON c.customer_id = r.customer_id
             WHERE COALESCE(r.date_out, r.collection_date) >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
             GROUP BY ym"
        );
        $monthlyClientTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-{$i} months"));
            $monthlyClientTrend[$ym] = [
                'label'            => date('M Y', strtotime("-{$i} months")),
                'private_income'   => 0.0,
                'colleague_income' => 0.0,
            ];
        }
        foreach ($monthlyClientRows as $row) {
            if (isset($monthlyClientTrend[$row['ym']])) {
                $monthlyClientTrend[$row['ym']]['private_income']   = (float)$row['private_income'];
                $monthlyClientTrend[$row['ym']]['colleague_income'] = (float)$row['colleague_income'];
            }
        }

        // ── Yearly summary (all years), Private vs Colleague income ────────────
        $yearlyClientTrend = $db->fetchAll(
            "SELECT YEAR(COALESCE(r.date_out, r.collection_date)) AS yr,
                    COALESCE(SUM(CASE WHEN c.client_type =  'colleague' THEN r.actual_amount END),0) AS colleague_income,
                    COALESCE(SUM(CASE WHEN c.client_type <> 'colleague' THEN r.actual_amount END),0) AS private_income
             FROM repairs r
             JOIN customers c ON c.customer_id = r.customer_id
             WHERE COALESCE(r.date_out, r.collection_date) IS NOT NULL
             GROUP BY yr
             ORDER BY yr DESC"
        );

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

    // ── GET /reports/colleagues ─────────────────────────────────────────────
    /**
     * Colleague Report: colleague-only data for a chosen period (last 30 days
     * / current month / previous month) — never mixes in private/individual
     * customers. Two sections:
     *   1. Per-colleague summary — repairs, bills generated, and income for
     *      each individual colleague (e.g. "Arif: 50 repairs, 12 bills").
     *   2. Full itemized list of every colleague repair in the period.
     *
     * Repairs and invoices are aggregated in SEPARATE queries and merged in
     * PHP by colleague — joining both in one query would cross-multiply the
     * figures (the same fan-out bug fixed earlier in Customer::getStats()).
     */
    public function colleagueReport(): void
    {
        Auth::requireRole('manager');
        $db = Database::getInstance();

        $period = $_GET['period'] ?? '30days';
        switch ($period) {
            case 'current_month':
                $start = date('Y-m-01');
                $end   = date('Y-m-t');
                $label = 'Current Month (' . date('F Y') . ')';
                break;
            case 'previous_month':
                $start = date('Y-m-01', strtotime('first day of last month'));
                $end   = date('Y-m-t',  strtotime('last day of last month'));
                $label = 'Previous Month (' . date('F Y', strtotime('last month')) . ')';
                break;
            default:
                $period = '30days';
                $start  = date('Y-m-d', strtotime('-29 days'));
                $end    = date('Y-m-d');
                $label  = 'Last 30 Days';
        }

        // ── Repairs per colleague in the period ────────────────────────────
        // Attributed by Out/Delivery date — a repair checked in long ago but
        // only finished/collected in this period should count here, not
        // toward whatever period it happened to be checked in.
        // Uses COALESCE(date_out, collection_date): date_out is only set by
        // the app's own status-change flow, but CSV-imported repairs only
        // ever get collection_date populated — the same fallback already
        // used by the Repairs list's "Out Date/Delivery" column.
        $repRows = $db->fetchAll(
            "SELECT c.customer_id, c.full_name, c.phone_mobile,
                    COUNT(r.repair_id)                AS repairs_count,
                    COALESCE(SUM(r.actual_amount), 0) AS total_income,
                    MAX(COALESCE(r.date_out, r.collection_date)) AS last_repair
             FROM repairs r
             JOIN customers c ON c.customer_id = r.customer_id
             WHERE c.client_type = 'colleague'
               AND DATE(COALESCE(r.date_out, r.collection_date)) BETWEEN ? AND ?
             GROUP BY c.customer_id, c.full_name, c.phone_mobile",
            [$start, $end]
        );

        // ── Bills (invoices) generated per colleague in the period ─────────
        $billRows = $db->fetchAll(
            "SELECT c.customer_id, c.full_name, c.phone_mobile,
                    COUNT(DISTINCT i.invoice_id) AS bills_generated
             FROM invoices i
             JOIN customers c ON c.customer_id = i.customer_id
             WHERE c.client_type = 'colleague'
               AND DATE(i.invoice_date) BETWEEN ? AND ?
               AND i.status != 'cancelled'
             GROUP BY c.customer_id, c.full_name, c.phone_mobile",
            [$start, $end]
        );

        // Merge into one row per colleague (a colleague may have bills but no
        // repair check-in in this exact window, or vice versa).
        $byColleague = [];
        foreach ($repRows as $r) {
            $id = (int)$r['customer_id'];
            $byColleague[$id] = [
                'customer_id'     => $id,
                'full_name'       => $r['full_name'],
                'phone_mobile'    => $r['phone_mobile'],
                'repairs_count'   => (int)$r['repairs_count'],
                'total_income'    => (float)$r['total_income'],
                'bills_generated' => 0,
                'last_repair'     => $r['last_repair'],
            ];
        }
        foreach ($billRows as $r) {
            $id = (int)$r['customer_id'];
            if (!isset($byColleague[$id])) {
                $byColleague[$id] = [
                    'customer_id'     => $id,
                    'full_name'       => $r['full_name'],
                    'phone_mobile'    => $r['phone_mobile'],
                    'repairs_count'   => 0,
                    'total_income'    => 0.0,
                    'bills_generated' => 0,
                    'last_repair'     => null,
                ];
            }
            $byColleague[$id]['bills_generated'] = (int)$r['bills_generated'];
        }
        usort($byColleague, fn($a, $b) => $b['repairs_count'] <=> $a['repairs_count'] ?: $b['total_income'] <=> $a['total_income']);

        $totals = ['repairs_count' => 0, 'total_income' => 0.0, 'bills_generated' => 0, 'colleagues' => count($byColleague)];
        foreach ($byColleague as $r) {
            $totals['repairs_count']   += $r['repairs_count'];
            $totals['total_income']    += $r['total_income'];
            $totals['bills_generated'] += $r['bills_generated'];
        }

        // ── Full itemized list of every colleague repair in the period ─────
        $repairRows = $db->fetchAll(
            "SELECT r.repair_id, r.device_model, r.actual_amount, r.estimate_amount, r.status, r.date_in,
                    COALESCE(r.date_out, r.collection_date) AS date_out,
                    c.full_name AS customer_name
             FROM repairs r
             JOIN customers c ON c.customer_id = r.customer_id
             WHERE c.client_type = 'colleague'
               AND DATE(COALESCE(r.date_out, r.collection_date)) BETWEEN ? AND ?
             ORDER BY COALESCE(r.date_out, r.collection_date) DESC",
            [$start, $end]
        );

        require VIEWS_PATH . '/reports/colleague-report.php';
    }
}
