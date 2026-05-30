/**
 * Chart.js — fintech-grade theme (Shopee Profit Hub)
 */
(function (global) {
    'use strict';

    const C = {
        maroon: '#9a2542',
        maroonDark: '#6b1528',
        maroonLight: '#d14a6f',
        slate: '#64748b',
        grid: 'rgba(15, 23, 42, 0.06)',
        positive: '#059669',
        warning: '#d97706',
        palette: ['#9a2542', '#0d9488', '#6b1528', '#d14a6f', '#0369a1', '#b83256', '#14b8a6', '#7f1d35'],
    };

    function fmtRp(n) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n || 0));
    }

    function fmtCompact(n) {
        const v = Number(n) || 0;
        if (Math.abs(v) >= 1e9) return (v / 1e9).toFixed(1) + ' M';
        if (Math.abs(v) >= 1e6) return (v / 1e6).toFixed(1) + ' jt';
        if (Math.abs(v) >= 1e3) return (v / 1e3).toFixed(0) + ' rb';
        return String(Math.round(v));
    }

    function fillAlpha(hex, a) {
        const h = hex.replace('#', '');
        return 'rgba(' + parseInt(h.slice(0, 2), 16) + ',' + parseInt(h.slice(2, 4), 16) + ',' + parseInt(h.slice(4, 6), 16) + ',' + a + ')';
    }

    function applyChartDefaults() {
        if (typeof Chart === 'undefined') return;
        Chart.defaults.font.family = "'DM Sans', system-ui, sans-serif";
        Chart.defaults.color = C.slate;
        Chart.defaults.animation.duration = 750;
        Chart.defaults.animation.easing = 'easeOutQuart';
        Chart.defaults.interaction.mode = 'index';
        Chart.defaults.interaction.intersect = false;
        Chart.defaults.plugins.legend.labels.usePointStyle = true;
        Chart.defaults.plugins.legend.labels.padding = 18;
        Chart.defaults.plugins.legend.labels.boxWidth = 8;
        Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.92)';
        Chart.defaults.plugins.tooltip.titleFont = { size: 13, weight: '600' };
        Chart.defaults.plugins.tooltip.bodyFont = { size: 12 };
        Chart.defaults.plugins.tooltip.padding = { x: 14, y: 12 };
        Chart.defaults.plugins.tooltip.cornerRadius = 10;
        Chart.defaults.plugins.tooltip.displayColors = true;
        Chart.defaults.plugins.tooltip.boxPadding = 6;
    }

    applyChartDefaults();

    function gradientFill(ctx, color, height) {
        const g = ctx.createLinearGradient(0, 0, 0, height || 280);
        g.addColorStop(0, fillAlpha(color, 0.28));
        g.addColorStop(0.6, fillAlpha(color, 0.08));
        g.addColorStop(1, fillAlpha(color, 0));
        return g;
    }

    function baseOptions(extra) {
        return Object.assign({
            responsive: true,
            maintainAspectRatio: false,
            layout: { padding: { top: 8, right: 12, bottom: 4, left: 8 } },
            interaction: { mode: 'index', intersect: false, axis: 'x' },
            plugins: {
                legend: {
                    position: 'bottom',
                    align: 'start',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 20,
                        font: { size: 11, weight: '500' },
                        color: '#475569',
                    },
                    onClick(e, legendItem, legend) {
                        const idx = legendItem.datasetIndex;
                        const ci = legend.chart;
                        if (ci.isDatasetVisible(idx)) {
                            ci.hide(idx);
                            legendItem.hidden = true;
                        } else {
                            ci.show(idx);
                            legendItem.hidden = false;
                        }
                    },
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        title(items) {
                            return items[0]?.label ?? '';
                        },
                        label(ctx) {
                            let v = ctx.parsed.y ?? ctx.parsed ?? ctx.raw;
                            if (typeof v === 'object' && v !== null) v = v.y ?? v.x;
                            const label = ctx.dataset?.label || '';
                            if (/%|rate|CTR|take/i.test(label)) {
                                return label + ': ' + Number(v).toFixed(1) + '%';
                            }
                            if (/pesanan|order/i.test(label)) {
                                return label + ': ' + new Intl.NumberFormat('id-ID').format(Math.round(v));
                            }
                            return label + ': ' + fmtRp(v);
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        font: { size: 11 },
                        maxRotation: 0,
                        autoSkip: true,
                        maxTicksLimit: 12,
                        color: '#94a3b8',
                    },
                },
                y: {
                    border: { display: false },
                    grid: { color: C.grid, drawBorder: false },
                    ticks: {
                        font: { size: 11 },
                        color: '#94a3b8',
                        padding: 8,
                        callback: (v) => fmtCompact(v),
                    },
                },
            },
            onHover(event, elements) {
                if (event.native?.target) {
                    event.native.target.style.cursor = elements.length ? 'pointer' : 'crosshair';
                }
            },
        }, extra || {});
    }

    function render(canvasId, type, payload) {
        const el = document.getElementById(canvasId);
        if (!el || typeof Chart === 'undefined') return null;

        const existing = Chart.getChart(el);
        if (existing) existing.destroy();

        const labels = payload.labels || [];
        const ctx2d = el.getContext('2d');
        const h = el.parentElement?.clientHeight || 300;
        let config;

        switch (type) {
            case 'doughnut':
            case 'pie':
                config = {
                    type: type,
                    data: {
                        labels,
                        datasets: [{
                            data: payload.data || [],
                            backgroundColor: C.palette.map((c, i) => fillAlpha(c, 0.88 - i * 0.05)),
                            borderWidth: 2,
                            borderColor: '#fff',
                            hoverOffset: 12,
                            spacing: 2,
                        }],
                    },
                    options: baseOptions({
                        cutout: type === 'doughnut' ? '68%' : 0,
                        scales: {},
                        plugins: {
                            legend: {
                                position: 'bottom',
                                align: 'center',
                            },
                            tooltip: {
                                callbacks: {
                                    label(ctx) {
                                        const t = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                        const pct = t ? ((ctx.raw / t) * 100).toFixed(1) : 0;
                                        return ctx.label + ': ' + fmtRp(ctx.raw) + ' (' + pct + '%)';
                                    },
                                },
                            },
                        },
                    }),
                };
                break;

            case 'bar':
                config = {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: (payload.datasets || [{ label: 'Nilai', data: payload.data || [] }]).map((ds, i) => {
                            const color = C.palette[i % C.palette.length];
                            return {
                                label: ds.label,
                                data: ds.data,
                                backgroundColor: fillAlpha(color, 0.85),
                                hoverBackgroundColor: color,
                                borderRadius: 8,
                                borderSkipped: false,
                                maxBarThickness: 56,
                            };
                        }),
                    },
                    options: baseOptions(),
                };
                break;

            case 'bar_horizontal':
                config = {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: payload.label || 'Nilai',
                            data: payload.data || [],
                            backgroundColor: fillAlpha(C.maroon, 0.75),
                            hoverBackgroundColor: C.maroon,
                            borderRadius: 6,
                            maxBarThickness: 36,
                        }],
                    },
                    options: baseOptions({
                        indexAxis: 'y',
                        scales: {
                            x: {
                                grid: { color: C.grid },
                                border: { display: false },
                                ticks: { callback: (v) => fmtCompact(v), color: '#94a3b8' },
                            },
                            y: {
                                grid: { display: false },
                                border: { display: false },
                                ticks: { font: { size: 10 }, color: '#475569' },
                            },
                        },
                    }),
                };
                break;

            case 'line':
            default:
                config = {
                    type: 'line',
                    data: {
                        labels,
                        datasets: (payload.datasets || []).map((ds, i) => {
                            const color = C.palette[i % C.palette.length];
                            return {
                                label: ds.label,
                                data: ds.data,
                                borderColor: color,
                                backgroundColor: ctx2d ? gradientFill(ctx2d, color, h) : fillAlpha(color, 0.12),
                                borderWidth: 2.5,
                                tension: 0.4,
                                fill: i === 0,
                                pointRadius: 0,
                                pointHoverRadius: 6,
                                pointHitRadius: 24,
                                pointBackgroundColor: '#fff',
                                pointBorderColor: color,
                                pointBorderWidth: 2,
                                pointHoverBorderWidth: 2,
                            };
                        }),
                    },
                    options: baseOptions({
                        elements: {
                            line: { capBezierPoints: true },
                            point: { hoverRadius: 7 },
                        },
                    }),
                };
                break;
        }

        const chart = new Chart(el, config);
        el.closest('.fc-chart-panel')?.classList.add('fc-chart-panel--ready');
        return chart;
    }

    /** Render monthly stacked-style bar via HubCharts */
    function renderMonthly(canvasId, monthly) {
        if (!monthly?.length) return null;
        return render(canvasId, 'bar', {
            labels: monthly.map((m) => m.label),
            datasets: [
                { label: 'Net penghasilan', data: monthly.map((m) => m.net) },
                { label: 'HPP', data: monthly.map((m) => m.cogs) },
                { label: 'Laba bersih', data: monthly.map((m) => m.net_profit) },
            ],
        });
    }

    global.HubCharts = {
        colors: C,
        fmtRp,
        fmtCompact,
        render,
        renderMonthly,
        applyChartDefaults,
    };
})(typeof window !== 'undefined' ? window : this);
