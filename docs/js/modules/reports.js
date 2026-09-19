/**
 * modules/reports.js — port of ReportController + views/reports/index.php
 *
 * Every chart here is single-series on purpose: the reader's job is comparing
 * magnitude, which one hue plus direct value labels answers better than a
 * multi-colour palette would. Each chart is also backed by a table, so the
 * numbers are readable without relying on colour at all.
 */
'use strict';

const Reports = {

    index({ query }) {
        if (!Auth.requireRole('manager')) return;

        const months  = Utils.intVal(query.months) || 12;
        const revenue = Repair.getMonthlyRevenue(months);
        const stats   = Repair.getStatistics();
        const invStat = Invoice.getMonthlyStats();
        const techs   = Staff.getRepairStats();
        const counts  = Repair.getStatusCounts();

        /* Top clients by lifetime paid invoices */
        const paidByCustomer = Utils.groupBy(
            DB.where('invoices', { status: 'paid' }), 'customer_id');
        const topClients = Object.entries(paidByCustomer)
            .map(([id, rows]) => ({
                id: Utils.intVal(id),
                label: Utils.truncate(Customer.findById(id)?.full_name ?? `#${id}`, 20),
                value: Utils.sum(rows, 'total_amount'),
            }))
            .sort((a, b) => b.value - a.value)
            .slice(0, 8);

        /* Most-repaired devices */
        const deviceGroups = Utils.groupBy(DB.table('repairs'),
            r => String(r.device_model).split(' ')[0]);
        const topDevices = Object.entries(deviceGroups)
            .map(([brand, rows]) => ({ label: brand, value: rows.length }))
            .sort((a, b) => b.value - a.value)
            .slice(0, 8);

        Layout.render(`
            ${UI.pageHeader('Reports',
                `Business overview · last ${months} months`,
                `<select class="form-select filter-select" id="rangeSelect">
                    ${[6, 12, 24].map(m => `<option value="${m}" ${months === m ? 'selected' : ''}>Last ${m} months</option>`).join('')}
                 </select>
                 <button class="btn btn-secondary" id="exportBtn">${Icon.download('')} Export summary</button>`)}

            <div class="stats-grid">
                ${UI.statCard({ label: 'Revenue (all time)', value: Utils.formatCurrencyShort(stats.revenue_total), icon: 'money',   tone: 'green' })}
                ${UI.statCard({ label: `${L.jobMany} completed`, value: Utils.numberFormat(counts.collected + counts.completed), icon: 'check', tone: 'accent' })}
                ${UI.statCard({ label: 'Avg. turnaround',    value: `${stats.avg_turnaround} days`, icon: 'clock', tone: 'orange' })}
                ${UI.statCard({ label: 'Outstanding',        value: Utils.formatCurrencyShort(invStat.outstanding), icon: 'invoice', tone: 'blue' })}
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Revenue by month</h2>
                    <span class="text-muted small">Completed ${Utils.e(L.jobMany.toLowerCase())}, ${CURRENCY_CODE}</span>
                </div>
                <div class="card-body">
                    <div class="chart-wrap tall"><canvas id="revChart" height="120"></canvas></div>
                </div>
            </div>

            <div class="report-grid">

                <div class="card">
                    <div class="card-header"><h2 class="card-title">${Utils.e(L.jobMany)} received per month</h2></div>
                    <div class="card-body">
                        <div class="chart-wrap"><canvas id="jobsChart" height="150"></canvas></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h2 class="card-title">${Utils.e(L.jobMany)} by status</h2></div>
                    <div class="card-body">
                        <div class="chart-wrap"><canvas id="statusChart" height="150"></canvas></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h2 class="card-title">${Utils.e(L.staffOne)} output</h2></div>
                    <div class="card-body">
                        <div class="chart-wrap"><canvas id="techChart" height="150"></canvas></div>
                    </div>
                    <div id="techTable"></div>
                </div>

                <div class="card">
                    <div class="card-header"><h2 class="card-title">Top ${Utils.e(L.clientMany.toLowerCase())} by revenue</h2></div>
                    <div class="card-body">
                        <div class="chart-wrap"><canvas id="clientChart" height="150"></canvas></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h2 class="card-title">Most-serviced ${Utils.e(L.itemLabel.toLowerCase())}s</h2></div>
                    <div class="card-body">
                        <div class="chart-wrap"><canvas id="deviceChart" height="150"></canvas></div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h2 class="card-title">Billing summary</h2></div>
                    <div class="card-body">
                        ${UI.field('Invoices this month', Utils.numberFormat(invStat.count_month))}
                        ${UI.field('Billed this month',   Utils.formatCurrency(invStat.billed_month))}
                        ${UI.field('Collected this month',Utils.formatCurrency(invStat.paid_month))}
                        ${UI.field('Collected all time',  Utils.formatCurrency(invStat.total_paid))}
                        ${UI.field('Outstanding', `<span class="text-danger">${Utils.formatCurrency(invStat.outstanding)}</span>`, true)}
                        ${UI.field('Overdue invoices', invStat.overdue_count
                            ? `<a href="#/invoices?status=overdue" class="badge badge-red">${invStat.overdue_count}</a>`
                            : '<span class="badge badge-green">None</span>', true)}
                    </div>
                </div>

            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title">Monthly breakdown</h2></div>
                <div id="monthTable"></div>
            </div>
        `);

        /* Charts */
        Charts.revenueLine('revChart', revenue);
        Charts.bar('jobsChart', this._jobsPerMonth(months));
        Charts.statusBar('statusChart', counts);
        Charts.bar('techChart', techs.map(t => ({ label: Utils.truncate(t.full_name, 16), value: t.total_repairs })), { horizontal: true });
        Charts.bar('clientChart', topClients, { horizontal: true, money: true, padRight: 58 });
        Charts.bar('deviceChart', topDevices, { horizontal: true });

        /* Tables — the non-colour route to the same numbers */
        DataTable.render({
            mount: '#monthTable',
            rows: [...revenue].reverse(),
            columns: [
                { key: 'label', label: 'Month' },
                { key: 'jobs',  label: `${L.jobMany} completed`, align: 'right', render: r => Utils.numberFormat(r.jobs) },
                { key: 'revenue', label: 'Revenue', align: 'right', render: r => Utils.formatCurrency(r.revenue) },
                { key: 'avg', label: `Avg. per ${L.jobLower}`, align: 'right',
                  render: r => r.jobs ? Utils.formatCurrency(r.revenue / r.jobs) : '—' },
            ],
            empty: { message: `No completed ${L.jobMany.toLowerCase()} in this range.`, icon: 'chart' },
        });

        DataTable.render({
            mount: '#techTable',
            rows: techs,
            columns: [
                { key: 'full_name', label: L.staffOne, render: t => `<a class="table-link" href="#/staff/${t.staff_id}">${Utils.e(t.full_name)}</a>` },
                { key: 'open_repairs',  label: 'Open',  align: 'right' },
                { key: 'total_repairs', label: 'Total', align: 'right' },
                { key: 'avg_days', label: 'Avg. days', align: 'right', hideOnTablet: true,
                  render: t => t.avg_days ? `${t.avg_days}d` : '—' },
            ],
            empty: { message: `No ${L.staffMany.toLowerCase()} on record.`, icon: 'user' },
        });

        document.getElementById('rangeSelect').onchange = e => Router.setQuery({ months: e.target.value });
        document.getElementById('exportBtn').onclick = () => this.export(revenue);
    },

    _jobsPerMonth(months) {
        const rows = DB.table('repairs');
        const out  = [];
        const now  = new Date();
        for (let i = months - 1; i >= 0; i--) {
            const d  = new Date(now.getFullYear(), now.getMonth() - i, 1);
            const ym = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
            out.push({ label: Utils.monthLabel(ym), value: rows.filter(r => Utils.toDbDate(r.date_in).startsWith(ym)).length });
        }
        return out;
    },

    export(revenue) {
        const csv = Utils.toCsv(revenue, [
            { label: 'Month',   key: 'label' },
            { label: `${L.jobMany} completed`, key: 'jobs' },
            { label: 'Revenue', key: 'revenue' },
            { label: `Avg per ${L.jobLower}`, value: r => r.jobs ? Utils.round2(r.revenue / r.jobs) : 0 },
        ]);
        Utils.download(`revenue-summary-${Utils.toDbDate(new Date())}.csv`, csv);
        Toast.success('Summary exported.');
    },
};
