/**
 * modules/dashboard.js — port of DashboardController + views/dashboard/index.php
 */
'use strict';

const Dashboard = {

    index() {
        if (!Auth.requireAuth()) return;

        const stats    = Repair.getStatistics();
        const recent   = Repair.getRecentRepairs(8);
        const ready    = Repair.getReadyForPickup();
        const overdue  = Repair.getOverduePickups(7);
        const invStats = Invoice.getMonthlyStats();
        const techs    = Staff.getRepairStats().slice(0, 5);
        const revenue  = Repair.getMonthlyRevenue(12);
        const user     = Auth.user();

        Layout.render(`
            ${UI.pageHeader(
                `${this._greeting()}, ${Utils.e(this._firstName(user.full_name))}`,
                `${Utils.numberFormat(stats.open)} open ${L.jobMany.toLowerCase()} · ${Utils.numberFormat(stats.in_today)} received today · ${Utils.formatCurrency(invStats.outstanding)} outstanding`,
                `<a href="#/repairs/create" class="btn btn-primary">${Icon.plus('')} ${Utils.e(L.jobNew)}</a>
                 <a href="#/customers/create" class="btn btn-secondary">${Icon.plus('')} New ${Utils.e(L.clientOne)}</a>`
            )}

            <div class="stats-grid">
                ${UI.statCard({ label: `Open ${L.jobMany}`, value: Utils.numberFormat(stats.open), icon: 'wrench', tone: 'accent', link: '#/repairs?open=1' })}
                ${UI.statCard({ label: REPAIR_STATUS.ready_for_pickup, value: Utils.numberFormat(stats.ready_for_pickup), icon: 'box', tone: 'blue', link: '#/repairs?status=ready_for_pickup' })}
                ${UI.statCard({ label: REPAIR_STATUS.waiting_for_parts, value: Utils.numberFormat(stats.waiting_for_parts), icon: 'clock', tone: 'orange', link: '#/repairs?status=waiting_for_parts' })}
                ${UI.statCard({ label: 'Revenue (month)', value: Utils.formatCurrencyShort(stats.revenue_month),icon: 'money',  tone: 'green',  link: '#/reports' })}
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-main">

                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Revenue — last 12 months</h2>
                            <a href="#/reports" class="btn btn-xs btn-secondary">Reports</a>
                        </div>
                        <div class="card-body">
                            <div class="chart-wrap"><canvas id="revenueChart" height="110"></canvas></div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Recent ${Utils.e(L.jobMany.toLowerCase())}</h2>
                            <a href="#/repairs" class="btn btn-xs btn-secondary">All ${Utils.e(L.jobMany.toLowerCase())}</a>
                        </div>
                        <div id="recentMount"></div>
                    </div>

                </div>

                <aside class="dashboard-aside">

                    ${overdue.length ? `
                    <div class="card card-danger">
                        <div class="card-header">
                            <h2 class="card-title">${Icon.alert('inline-ico')} Overdue</h2>
                            <span class="badge badge-red">${overdue.length}</span>
                        </div>
                        <div class="card-body">
                            <ul class="pickup-list">
                                ${overdue.slice(0, 5).map(r => `
                                    <li class="pickup-item">
                                        <div class="pickup-info">
                                            <a class="pickup-name" href="#/repairs/${r.repair_id}">${Utils.e(r.customer_name)}</a>
                                            <span class="pickup-device">${Utils.e(Utils.truncate(r.device_model, 28))}</span>
                                        </div>
                                        <span class="badge badge-red">${Utils.daysBetween(r.date_out || r.date_in)}d</span>
                                    </li>`).join('')}
                            </ul>
                        </div>
                    </div>` : ''}

                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">${Utils.e(L.jobMany)} by status</h2>
                        </div>
                        <div class="card-body">
                            <div class="chart-wrap"><canvas id="statusChart" height="170"></canvas></div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><h2 class="card-title">Billing this month</h2></div>
                        <div class="card-body">
                            ${UI.field('Invoices raised', Utils.numberFormat(invStats.count_month))}
                            ${UI.field('Billed',  Utils.formatCurrency(invStats.billed_month))}
                            ${UI.field('Collected', Utils.formatCurrency(invStats.paid_month))}
                            ${UI.field('Outstanding', `<span class="text-danger">${Utils.formatCurrency(invStats.outstanding)}</span>`, true)}
                            ${UI.field('Overdue invoices', invStats.overdue_count
                                ? `<a href="#/invoices?status=overdue" class="badge badge-red">${invStats.overdue_count}</a>`
                                : '<span class="badge badge-green">None</span>', true)}
                        </div>
                    </div>

                    ${Auth.can('manager') ? `
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">${Utils.e(L.staffOne)} workload</h2>
                            <a href="#/staff" class="btn btn-xs btn-secondary">Staff</a>
                        </div>
                        <div class="card-body">
                            <ul class="staff-stat-list">
                                ${techs.map(t => `
                                    <li class="staff-stat-item">
                                        <div class="user-avatar-sm">${Utils.e(Utils.initials(t.full_name))}</div>
                                        <div class="staff-stat-info">
                                            <a class="staff-stat-name" href="#/staff/${t.staff_id}">${Utils.e(t.full_name)}</a>
                                            <span class="staff-stat-count">${t.open_repairs} open · ${t.total_repairs} total</span>
                                        </div>
                                        <span class="badge ${t.open_repairs > 6 ? 'badge-orange' : 'badge-gray'}">${t.open_repairs}</span>
                                    </li>`).join('')}
                            </ul>
                        </div>
                    </div>` : ''}

                    <div class="card">
                        <div class="card-header"><h2 class="card-title">Recent activity</h2></div>
                        <div class="card-body">
                            <ul class="activity-list">
                                ${(DB.recentActivity(6).length
                                    ? DB.recentActivity(6)
                                    : [{ description: `Nothing yet — add a ${L.clientOne.toLowerCase()} or ${L.jobLower} and it shows up here.`, created_at: Utils.now() }]
                                  ).map(a => `
                                    <li class="activity-item">
                                        <span class="activity-text">${Utils.e(a.description)}</span>
                                        <span class="activity-time">${Utils.timeAgo(a.created_at)}</span>
                                    </li>`).join('')}
                            </ul>
                        </div>
                    </div>

                </aside>
            </div>
        `);

        /* Recent repairs table */
        DataTable.render({
            mount: '#recentMount',
            rows: recent,
            columns: [
                { key: 'repair_id', label: '#', width: '52px',
                  render: r => `<a class="table-link" href="#/repairs/${r.repair_id}">#${r.repair_id}</a>` },
                { key: 'customer_name', label: L.clientOne,
                  render: r => `<a class="table-link" href="#/customers/${r.customer_id}">${Utils.e(Utils.truncate(r.customer_name, 24))}</a>` },
                { key: 'device_model', label: L.itemLabel, hideOnTablet: true,
                  render: r => Utils.e(Utils.truncate(r.device_model, 26)) },
                { key: 'date_in', label: 'In', hideOnTablet: true, render: r => Utils.formatDate(r.date_in) },
                { key: 'status', label: 'Status', render: r => Badge.repair(r.status) },
            ],
            empty: { message: `No ${L.jobMany.toLowerCase()} logged yet.`, icon: 'wrench',
                     action: `<a href="#/repairs/create" class="btn btn-primary">Log the first ${Utils.e(L.jobLower)}</a>` },
        });

        Charts.revenueLine('revenueChart', revenue);
        Charts.statusBar('statusChart', Repair.getStatusCounts());
    },

    /**
     * "Md Arif Billah" → "Arif". Taking the first token would greet people by
     * their honorific, which is wrong for most Bangladeshi names.
     */
    _firstName(fullName) {
        const honorifics = ['md', 'md.', 'mohammad', 'muhammad', 'mst', 'mst.', 'most', 'most.', 'mrs', 'mr', 'ms', 'dr'];
        const parts = String(fullName ?? '').trim().split(/\s+/).filter(Boolean);
        const idx = parts.findIndex(p => !honorifics.includes(p.toLowerCase()));
        return idx === -1 ? (parts[0] ?? 'there') : parts[idx];
    },

    _greeting() {
        const h = new Date().getHours();
        return h < 12 ? 'Good morning' : h < 17 ? 'Good afternoon' : 'Good evening';
    },
};
