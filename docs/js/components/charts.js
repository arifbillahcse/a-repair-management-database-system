/**
 * components/charts.js — every chart in the demo.
 *
 * Design decisions, made deliberately rather than by default:
 *
 *  · Every chart here is SINGLE-SERIES, so no categorical palette is needed
 *    and there is no colour-blindness adjacency risk at all. One validated
 *    hue per mode: #059669 on light, #34d399 on dark — both clear 3:1
 *    contrast against their surface, and both come from the app's existing
 *    green accent ramp, so the charts match the rest of the UI.
 *  · "Repairs by status" is a horizontal BAR, not a doughnut. Seven pie
 *    slices is unreadable, and the reader's job here is comparing magnitude,
 *    which a sorted bar does far better. The status name sits on the axis,
 *    so identity never depends on colour.
 *  · Values are direct-labelled on the bars; axes and grid stay recessive.
 *  · Colours are read from CSS custom properties, so the dark-mode toggle
 *    repaints the charts with no extra code.
 */
'use strict';

const Charts = {

    _instances: {},

    /** Read a CSS custom property off :root so charts follow the theme. */
    _css(name, fallback) {
        const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        return v || fallback;
    },

    theme() {
        return {
            mark:    this._css('--chart-mark',    '#059669'),
            markDim: this._css('--chart-mark-dim','rgba(5,150,105,.14)'),
            grid:    this._css('--border',        '#e0e0e0'),
            text:    this._css('--text-secondary','#666666'),
            muted:   this._css('--text-muted',    '#999999'),
            surface: this._css('--bg-primary',    '#ffffff'),
            font:    "'Segoe UI', system-ui, -apple-system, sans-serif",
        };
    },

    /** Destroy an existing chart before re-rendering into the same canvas. */
    _reset(id) {
        this._instances[id]?.destroy();
        delete this._instances[id];
    },

    _ready(id) {
        if (typeof Chart === 'undefined') {
            const el = document.getElementById(id);
            if (el) el.closest('.chart-wrap').innerHTML =
                '<p class="chart-fallback">Chart library unavailable offline — the numbers are in the table below.</p>';
            return null;
        }
        return document.getElementById(id);
    },

    _baseOptions(t) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },          // single series — the title names it
                tooltip: {
                    backgroundColor: t.surface,
                    titleColor: this._css('--text-primary', '#1a1a1a'),
                    bodyColor: t.text,
                    borderColor: t.grid,
                    borderWidth: 1,
                    padding: 10,
                    cornerRadius: 6,
                    displayColors: false,
                    titleFont: { family: t.font, size: 12, weight: '600' },
                    bodyFont:  { family: t.font, size: 12 },
                },
            },
        };
    },

    /* ── Revenue over time — line, one series ─────────────────────────────── */

    revenueLine(id, series) {
        const el = this._ready(id);
        if (!el) return;
        this._reset(id);
        const t = this.theme();

        this._instances[id] = new Chart(el, {
            type: 'line',
            data: {
                labels: series.map(p => p.label),
                datasets: [{
                    data: series.map(p => p.revenue),
                    borderColor: t.mark,
                    backgroundColor: t.markDim,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 0,
                    pointHoverRadius: 5,          // >= 8px hit target once stroked
                    pointHoverBorderWidth: 2,
                    pointHoverBackgroundColor: t.mark,
                    pointHoverBorderColor: t.surface,
                }],
            },
            options: {
                ...this._baseOptions(t),
                plugins: {
                    ...this._baseOptions(t).plugins,
                    tooltip: {
                        ...this._baseOptions(t).plugins.tooltip,
                        callbacks: {
                            label: c => `${Utils.formatCurrency(c.parsed.y)} · ${series[c.dataIndex].jobs} jobs`,
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { color: t.grid },
                        ticks: { color: t.muted, font: { family: t.font, size: 11 }, maxRotation: 0, autoSkipPadding: 12 },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: t.grid, drawTicks: false },
                        border: { display: false },
                        ticks: {
                            color: t.muted, font: { family: t.font, size: 11 }, padding: 8, maxTicksLimit: 5,
                            callback: v => Utils.formatCurrencyShort(v),
                        },
                    },
                },
            },
        });
    },

    /* ── Repairs by status — sorted horizontal bar, direct labels ─────────── */

    statusBar(id, counts) {
        const el = this._ready(id);
        if (!el) return;
        this._reset(id);
        const t = this.theme();

        const rows = Object.keys(REPAIR_STATUS)
            .map(k => ({ key: k, label: REPAIR_STATUS[k], value: counts[k] ?? 0 }))
            .filter(r => r.value > 0)
            .sort((a, b) => b.value - a.value);

        if (!rows.length) {
            el.closest('.chart-wrap').innerHTML = '<p class="chart-fallback">No repairs logged yet.</p>';
            return;
        }

        this._instances[id] = new Chart(el, {
            type: 'bar',
            data: {
                labels: rows.map(r => r.label),
                datasets: [{
                    data: rows.map(r => r.value),
                    backgroundColor: t.mark,
                    borderRadius: 4,               // rounded data-end, anchored to baseline
                    borderSkipped: 'start',
                    barThickness: 14,
                }],
            },
            options: {
                ...this._baseOptions(t),
                indexAxis: 'y',
                layout: { padding: { right: 28 } },   // room for the direct labels
                plugins: {
                    ...this._baseOptions(t).plugins,
                    tooltip: {
                        ...this._baseOptions(t).plugins.tooltip,
                        callbacks: { label: c => `${c.parsed.x} repair${c.parsed.x === 1 ? '' : 's'}` },
                    },
                },
                scales: {
                    x: { display: false, beginAtZero: true, grace: '8%' },
                    y: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: { color: t.text, font: { family: t.font, size: 11.5 } },
                    },
                },
            },
            plugins: [this._valueLabels(t, 'x')],
        });
    },

    /* ── Generic single-hue bar (reports: jobs/month, top techs, top clients) ── */

    bar(id, rows, { horizontal = false, money = false, padRight = 34 } = {}) {
        const el = this._ready(id);
        if (!el) return;
        this._reset(id);
        const t = this.theme();

        if (!rows.length) {
            el.closest('.chart-wrap').innerHTML = '<p class="chart-fallback">Not enough data for this chart yet.</p>';
            return;
        }

        const fmt = v => money ? Utils.formatCurrencyShort(v) : Utils.numberFormat(v);

        this._instances[id] = new Chart(el, {
            type: 'bar',
            data: {
                labels: rows.map(r => r.label),
                datasets: [{
                    data: rows.map(r => r.value),
                    backgroundColor: t.mark,
                    borderRadius: 4,
                    borderSkipped: horizontal ? 'start' : 'bottom',
                    barThickness: horizontal ? 14 : undefined,
                    maxBarThickness: 34,
                }],
            },
            options: {
                ...this._baseOptions(t),
                indexAxis: horizontal ? 'y' : 'x',
                layout: { padding: horizontal ? { right: padRight } : { top: 18 } },
                plugins: {
                    ...this._baseOptions(t).plugins,
                    tooltip: {
                        ...this._baseOptions(t).plugins.tooltip,
                        callbacks: { label: c => fmt(horizontal ? c.parsed.x : c.parsed.y) },
                    },
                },
                scales: horizontal
                    ? {
                        x: { display: false, beginAtZero: true, grace: '10%' },
                        y: { grid: { display: false }, border: { display: false },
                             ticks: { color: t.text, font: { family: t.font, size: 11.5 } } },
                      }
                    : {
                        x: { grid: { display: false }, border: { color: t.grid },
                             ticks: { color: t.muted, font: { family: t.font, size: 11 }, maxRotation: 0, autoSkipPadding: 10 } },
                        y: { display: false, beginAtZero: true, grace: '12%' },
                      },
            },
            plugins: [this._valueLabels(t, horizontal ? 'x' : 'y', fmt)],
        });
    },

    /**
     * Direct value labels. These are what make the low-contrast relief rule
     * unnecessary and let the charts drop their legends entirely.
     */
    _valueLabels(t, axis, fmt = Utils.numberFormat.bind(Utils)) {
        return {
            id: 'valueLabels',
            afterDatasetsDraw(chart) {
                const { ctx } = chart;
                ctx.save();
                ctx.font = `600 11px ${t.font}`;
                ctx.fillStyle = t.text;
                chart.getDatasetMeta(0).data.forEach((bar, i) => {
                    const v = chart.data.datasets[0].data[i];
                    if (v === null || v === undefined) return;
                    if (axis === 'x') {
                        ctx.textAlign = 'left'; ctx.textBaseline = 'middle';
                        ctx.fillText(fmt(v), bar.x + 8, bar.y);
                    } else {
                        ctx.textAlign = 'center'; ctx.textBaseline = 'bottom';
                        ctx.fillText(fmt(v), bar.x, bar.y - 6);
                    }
                });
                ctx.restore();
            },
        };
    },

    /** Repaint every live chart after a theme change. */
    refreshAll() {
        Object.values(this._instances).forEach(c => c.destroy());
        this._instances = {};
        Router.reload();
    },
};
