/**
 * Shopee Profit Hub — ApexCharts (fintech theme)
 * Smart fallback: 1 titik → radial/metric, 0 → empty state
 */
(function (global) {
    'use strict';

    const C = {
        maroon: '#9a2542',
        maroonDark: '#6b1528',
        maroonLight: '#d14a6f',
        teal: '#0d9488',
        slate: '#64748b',
        positive: '#059669',
        warning: '#d97706',
        negative: '#dc2626',
        palette: ['#9a2542', '#0d9488', '#6b1528', '#d14a6f', '#0369a1', '#14b8a6', '#7f1d35', '#b45309'],
    };

    const _instances = {};

    function fmtRp(n) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n || 0));
    }

    /** Axis / label ringkas tetap prefix Rp */
    function fmtRpAxis(n) {
        const v = Number(n) || 0;
        const abs = Math.abs(v);
        if (abs >= 1e9) {
            return 'Rp ' + (v / 1e9).toFixed(1).replace('.', ',') + ' M';
        }
        if (abs >= 1e6) {
            return 'Rp ' + (v / 1e6).toFixed(1).replace('.', ',') + ' jt';
        }
        if (abs >= 1e4) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(v));
        }
        return fmtRp(v);
    }

    function fmtCompact(n) {
        return fmtRpAxis(n);
    }

    function fmtPct(n) {
        return Number(n).toFixed(1) + '%';
    }

    function hostEl(id) {
        let el = document.getElementById(id);
        if (!el) return null;
        if (el.tagName === 'CANVAS') {
            const div = document.createElement('div');
            div.id = id;
            div.className = 'hub-apex-host';
            div.setAttribute('role', 'img');
            el.replaceWith(div);
            return div;
        }
        el.classList.add('hub-apex-host');
        return el;
    }

    function destroy(id) {
        if (_instances[id]) {
            try { _instances[id].destroy(); } catch (e) { /* noop */ }
            delete _instances[id];
        }
    }

    function panelReady(el) {
        el.closest('.fc-chart-panel')?.classList.add('fc-chart-panel--ready');
    }

    function showEmpty(el, msg) {
        el.innerHTML = '<div class="hub-chart-empty"><i class="fas fa-chart-line"></i><p>' + (msg || 'Belum ada data untuk periode ini.') + '</p></div>';
        panelReady(el);
    }

    function countPoints(payload) {
        if (payload.labels?.length) return payload.labels.length;
        if (payload.datasets?.[0]?.data?.length) return payload.datasets[0].data.length;
        if (payload.data?.length) return payload.data.length;
        if (payload.series?.length && Array.isArray(payload.series[0])) return payload.series[0].length;
        return 0;
    }

    function baseChartOpts(height) {
        return {
            fontFamily: "'DM Sans', system-ui, sans-serif",
            foreColor: C.slate,
            toolbar: { show: false },
            zoom: { enabled: false },
            animations: { enabled: true, easing: 'easeinout', speed: 650 },
            height: height || '100%',
        };
    }

    function apexTooltipY(isPct, isCount) {
        return {
            theme: 'dark',
            y: {
                formatter(v) {
                    if (isPct) return fmtPct(v);
                    if (isCount) return new Intl.NumberFormat('id-ID').format(Math.round(v));
                    return fmtRp(v);
                },
            },
        };
    }

    function columnWidth(n) {
        if (n <= 1) return '36%';
        if (n <= 2) return '42%';
        if (n <= 4) return '55%';
        return '62%';
    }

    function yAxisRp() {
        return {
            labels: { formatter: (v) => fmtRpAxis(v) },
            min: 0,
            forceNiceScale: true,
        };
    }

    /** Single month / single bar — radial gauge + big number */
    function renderSingleMetric(id, payload) {
        const el = hostEl(id);
        if (!el) return null;
        destroy(id);

        const label = payload.labels?.[0] || payload.label || 'Periode ini';
        const raw = payload.data?.[0] ?? payload.datasets?.[0]?.data?.[0] ?? payload.value ?? 0;
        const val = Number(raw) || 0;
        const fmt = payload.format || 'rp';
        const max = Math.max(val * 1.25, payload.max || val * 1.5, fmt === 'roas' ? 10 : 1);
        const pct = Math.min(100, Math.round((val / max) * 100));
        const display = fmt === 'roas' ? val.toFixed(1) + 'x' : fmt === 'pct' ? fmtPct(val) : fmtCompact(val);
        const sub = fmt === 'roas' ? val.toFixed(2) + 'x ROAS' : fmt === 'pct' ? fmtPct(val) : fmtRp(val);

        const opts = {
            series: [pct],
            chart: { ...baseChartOpts(280), type: 'radialBar' },
            plotOptions: {
                radialBar: {
                    hollow: { size: '62%' },
                    track: { background: '#f1f5f9', strokeWidth: '100%' },
                    dataLabels: {
                        name: { fontSize: '13px', color: C.slate, offsetY: -8 },
                        value: {
                            fontSize: '22px',
                            fontWeight: 700,
                            color: C.maroonDark,
                            formatter: () => display,
                        },
                    },
                },
            },
            labels: [label],
            colors: [C.maroon],
            subtitle: {
                text: sub,
                align: 'center',
                style: { fontSize: '12px', color: '#94a3b8' },
            },
        };

        const chart = new ApexCharts(el, opts);
        chart.render();
        _instances[id] = chart;
        panelReady(el);
        return chart;
    }

    function renderRadialBar(id, payload) {
        const el = hostEl(id);
        if (!el) return null;
        destroy(id);

        const series = payload.series || payload.data || [0];
        const labels = payload.labels || ['Progress'];
        const max = payload.max || 100;

        const opts = {
            series: series.map((v) => Math.min(max, Math.max(0, Number(v)))),
            chart: { ...baseChartOpts(260), type: 'radialBar' },
            plotOptions: {
                radialBar: {
                    offsetY: 0,
                    hollow: { size: '30%' },
                    track: { background: '#e2e8f0' },
                    dataLabels: {
                        name: { fontSize: '11px' },
                        value: { fontSize: '14px', formatter: (v) => fmtPct(v) },
                        total: {
                            show: series.length > 1,
                            label: payload.totalLabel || 'Rata-rata',
                            formatter: (w) => {
                                const s = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                return fmtPct(s / series.length);
                            },
                        },
                    },
                },
            },
            labels,
            colors: C.palette.slice(0, labels.length),
        };

        const chart = new ApexCharts(el, opts);
        chart.render();
        _instances[id] = chart;
        panelReady(el);
        return chart;
    }

    function renderTreemap(id, payload) {
        const el = hostEl(id);
        if (!el) return null;
        destroy(id);

        const items = payload.data || payload.items || [];
        if (!items.length) {
            showEmpty(el);
            return null;
        }

        const opts = {
            series: [{ data: items.map((d) => ({ x: d.label || d.x, y: Number(d.value ?? d.y) || 0 })) }],
            chart: { ...baseChartOpts(320), type: 'treemap' },
            legend: { show: false },
            dataLabels: {
                enabled: true,
                style: { fontSize: '11px', fontWeight: 600 },
                formatter(t, o) {
                    return [t, fmtCompact(o.value)];
                },
                offsetY: -2,
            },
            plotOptions: {
                treemap: {
                    distributed: true,
                    enableShades: true,
                    shadeIntensity: 0.15,
                },
            },
            colors: C.palette,
            tooltip: { y: { formatter: (v) => fmtRp(v) } },
        };

        const chart = new ApexCharts(el, opts);
        chart.render();
        _instances[id] = chart;
        panelReady(el);
        return chart;
    }

    function renderSparkline(id, payload) {
        const el = hostEl(id);
        if (!el) return null;
        destroy(id);

        const data = payload.data || payload.datasets?.[0]?.data || [];
        const n = data.length;
        if (!n) {
            showEmpty(el);
            return null;
        }

        const opts = {
            series: [{ name: payload.label || 'Trend', data }],
            chart: {
                ...baseChartOpts(120),
                type: 'area',
                sparkline: { enabled: true },
            },
            stroke: { curve: 'smooth', width: 2 },
            fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05 },
            },
            colors: [C.maroon],
            tooltip: apexTooltipY(payload.isPct, payload.isCount),
        };

        const chart = new ApexCharts(el, opts);
        chart.render();
        _instances[id] = chart;
        panelReady(el);
        return chart;
    }

    function renderDonut(id, payload) {
        const el = hostEl(id);
        if (!el) return null;
        destroy(id);

        const labels = payload.labels || [];
        const data = payload.data || [];
        const total = data.reduce((a, b) => a + (Number(b) || 0), 0);
        if (!total) {
            showEmpty(el);
            return null;
        }

        const opts = {
            series: data.map(Number),
            labels,
            chart: { ...baseChartOpts(300), type: 'donut' },
            colors: C.palette,
            stroke: { width: 2, colors: ['#fff'] },
            dataLabels: { enabled: false },
            plotOptions: {
                pie: {
                    donut: {
                        size: '68%',
                        labels: {
                            show: true,
                            name: { fontSize: '12px' },
                            value: { fontSize: '18px', fontWeight: 700, formatter: (v) => fmtCompact(v) },
                            total: {
                                show: true,
                                label: payload.centerLabel || 'Total',
                                formatter: () => fmtCompact(total),
                            },
                        },
                    },
                },
            },
            legend: { position: 'bottom', fontSize: '11px' },
            tooltip: { y: { formatter: (v) => fmtRp(v) } },
        };

        const chart = new ApexCharts(el, opts);
        chart.render();
        _instances[id] = chart;
        panelReady(el);
        return chart;
    }

    function renderPolar(id, payload) {
        const el = hostEl(id);
        if (!el) return null;
        destroy(id);

        const labels = payload.labels || [];
        const data = payload.data || [];
        if (!data.length) {
            showEmpty(el);
            return null;
        }

        const opts = {
            series: data.map(Number),
            labels,
            chart: { ...baseChartOpts(300), type: 'polarArea' },
            stroke: { colors: ['#fff'] },
            fill: { opacity: 0.85 },
            colors: C.palette,
            legend: { position: 'bottom', fontSize: '11px' },
            yaxis: { show: false },
            tooltip: { y: { formatter: (v) => fmtRp(v) } },
        };

        const chart = new ApexCharts(el, opts);
        chart.render();
        _instances[id] = chart;
        panelReady(el);
        return chart;
    }

    function renderLineArea(id, type, payload) {
        const el = hostEl(id);
        if (!el) return null;
        destroy(id);

        const labels = payload.labels || [];
        const datasets = payload.datasets || [];
        const n = labels.length;

        if (!n) {
            showEmpty(el);
            return null;
        }

        const fmt = payload.format || '';
        const isPct = fmt === 'pct' || /ctr|rate|margin|%/i.test((datasets[0]?.label || '') + (payload.label || ''));
        const isRoas = fmt === 'roas' || /roas/i.test((datasets[0]?.label || '') + (payload.label || ''));

        if (n === 1 && datasets.length === 1) {
            return renderSingleMetric(id, {
                labels,
                data: datasets[0].data,
                label: datasets[0].label,
                format: payload.format || (isRoas ? 'roas' : isPct ? 'pct' : 'rp'),
            });
        }

        const valueFmt = (v) => {
            if (isRoas) return Number(v).toFixed(1) + 'x';
            if (isPct) return fmtPct(v);
            return fmtCompact(v);
        };

        const opts = {
            series: datasets.map((ds) => ({
                name: ds.label,
                data: ds.data.map(Number),
            })),
            chart: { ...baseChartOpts(), type: type === 'area' ? 'area' : 'line' },
            colors: C.palette,
            stroke: { curve: 'smooth', width: 2.5 },
            fill: type === 'area'
                ? { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.04 } }
                : { type: 'solid', opacity: type === 'line' ? 0.08 : 0.3 },
            markers: {
                size: n <= 6 ? 5 : 0,
                hover: { size: 7 },
                strokeWidth: 2,
                strokeColors: '#fff',
            },
            dataLabels: {
                enabled: n <= 6,
                offsetY: -6,
                style: { fontSize: '10px', fontWeight: 600 },
                formatter: (v) => valueFmt(v),
            },
            xaxis: {
                categories: labels,
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: { style: { fontSize: '11px' } },
            },
            yaxis: isRoas
                ? { labels: { formatter: (v) => v.toFixed(1) + 'x' }, min: 0 }
                : isPct
                ? { labels: { formatter: (v) => fmtPct(v) }, min: 0 }
                : yAxisRp(),
            grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
            tooltip: {
                theme: 'dark',
                y: {
                    formatter(v) {
                        if (isRoas) return Number(v).toFixed(2) + 'x';
                        if (isPct) return fmtPct(v);
                        return fmtRp(v);
                    },
                },
            },
            legend: { position: 'bottom', fontSize: '11px' },
        };

        const chart = new ApexCharts(el, opts);
        chart.render();
        _instances[id] = chart;
        panelReady(el);
        return chart;
    }

    function renderBar(id, payload, horizontal) {
        const el = hostEl(id);
        if (!el) return null;
        destroy(id);

        const labels = payload.labels || [];
        const datasets = payload.datasets;
        const singleData = payload.data;
        const n = labels.length;

        if (!n) {
            showEmpty(el);
            return null;
        }

        const isCount = /pesanan|order|qty|unit/i.test(payload.label || '');
        const isPct = /rate|acos|ctr|margin/i.test(payload.label || '');

        if (n === 1 && !datasets) {
            return renderSingleMetric(id, payload);
        }

        let series;
        if (datasets?.length) {
            series = datasets.map((ds) => ({ name: ds.label, data: ds.data.map(Number) }));
        } else {
            series = [{ name: payload.label || 'Nilai', data: (singleData || []).map(Number) }];
        }

        const opts = {
            series,
            chart: {
                ...baseChartOpts(),
                type: 'bar',
                stacked: payload.stacked || false,
            },
            colors: C.palette,
            plotOptions: {
                bar: {
                    horizontal: !!horizontal,
                    borderRadius: horizontal ? 6 : 8,
                    columnWidth: horizontal ? undefined : columnWidth(n),
                    barHeight: horizontal ? '68%' : undefined,
                    dataLabels: { position: horizontal ? 'center' : 'top' },
                },
            },
            dataLabels: {
                enabled: n <= 8,
                offsetY: horizontal ? 0 : -18,
                style: { fontSize: '10px', colors: horizontal ? ['#fff'] : [C.maroonDark] },
                formatter: (v) => {
                    if (isPct) return fmtPct(v);
                    if (isCount) return new Intl.NumberFormat('id-ID').format(v);
                    return fmtRp(v);
                },
            },
            xaxis: horizontal
                ? { labels: { formatter: (v) => fmtCompact(v) } }
                : { categories: labels, axisBorder: { show: false } },
            yaxis: horizontal
                ? { labels: { style: { fontSize: '10px' } } }
                : (isPct ? { labels: { formatter: (v) => fmtPct(v) }, min: 0 } : yAxisRp()),
            grid: { borderColor: '#f1f5f9' },
            tooltip: apexTooltipY(isPct, isCount),
            legend: { show: series.length > 1, position: 'bottom', fontSize: '11px' },
        };

        const chart = new ApexCharts(el, opts);
        chart.render();
        _instances[id] = chart;
        panelReady(el);
        return chart;
    }

    function renderMixed(id, payload) {
        const el = hostEl(id);
        if (!el) return null;
        destroy(id);

        const labels = payload.labels || [];
        if (!labels.length) {
            showEmpty(el);
            return null;
        }

        const cols = payload.columns || payload.datasets?.[0]?.data || [];
        const lines = payload.lines || payload.datasets?.[1]?.data || [];

        const opts = {
            series: [
                { name: payload.colLabel || 'Kolom', type: 'column', data: cols.map(Number) },
                { name: payload.lineLabel || 'Garis', type: 'line', data: lines.map(Number) },
            ],
            chart: { ...baseChartOpts(), type: 'line', stacked: false },
            stroke: { width: [0, 3], curve: 'smooth' },
            plotOptions: { bar: { columnWidth: columnWidth(labels.length), borderRadius: 6 } },
            colors: [C.maroon, C.teal],
            dataLabels: { enabled: labels.length <= 8, enabledOnSeries: [0],
                formatter: (v) => fmtRp(v),
                style: { fontSize: '10px', colors: [C.maroonDark] },
                offsetY: -16,
            },
            xaxis: { categories: labels },
            yaxis: yAxisRp(),
            tooltip: apexTooltipY(false, false),
            legend: { position: 'bottom' },
        };

        const chart = new ApexCharts(el, opts);
        chart.render();
        _instances[id] = chart;
        panelReady(el);
        return chart;
    }

    function renderHeatmap(id, payload) {
        const el = hostEl(id);
        if (!el) return null;
        destroy(id);

        const series = payload.series || [];
        if (!series.length) {
            showEmpty(el, 'Belum ada data fee untuk periode ini. Perluas rentang tanggal atau sync order Shopee.');
            return null;
        }

        const hasValue = series.some((s) => (s.data || []).some((d) => Number(d.y ?? 0) > 0));
        if (!hasValue) {
            showEmpty(el, 'Fee platform nol di periode ini — pastikan order Shopee sudah tersinkron dengan data escrow.');
            return null;
        }

        const maxVal = Math.max(1, Number(payload.max) || 0);
        const mid = Math.round(maxVal * 0.35);
        const high = Math.round(maxVal * 0.7);

        const opts = {
            series,
            chart: { ...baseChartOpts(380), type: 'heatmap' },
            plotOptions: {
                heatmap: {
                    shadeIntensity: 0.45,
                    radius: 4,
                    useFillColorAsStroke: false,
                    colorScale: {
                        ranges: [
                            { from: 0, to: 0, name: 'Nol', color: '#f1f5f9' },
                            { from: 1, to: mid, name: 'Rendah', color: '#f8e8ed' },
                            { from: mid + 1, to: high, name: 'Sedang', color: '#d14a6f' },
                            { from: high + 1, to: maxVal * 10, name: 'Tinggi', color: '#6b1528' },
                        ],
                    },
                },
            },
            dataLabels: {
                enabled: true,
                style: { fontSize: '9px', colors: ['#1e293b'] },
                formatter(val, opts) {
                    const y = opts?.w?.config?.series?.[opts.seriesIndex]?.data?.[opts.dataPointIndex]?.y;
                    const n = Number(y ?? val ?? 0);
                    return n > 0 ? fmtRpAxis(n) : '';
                },
            },
            stroke: { width: 1, colors: ['#fff'] },
            xaxis: { labels: { rotate: -35, style: { fontSize: '9px' } } },
            yaxis: { labels: { style: { fontSize: '10px' } } },
            legend: { show: false },
            tooltip: {
                theme: 'dark',
                y: { formatter: (v) => fmtRp(v) },
            },
        };

        const chart = new ApexCharts(el, opts);
        chart.render();
        _instances[id] = chart;
        panelReady(el);
        return chart;
    }

    function render(id, type, payload, options) {
        if (typeof ApexCharts === 'undefined') {
            console.warn('ApexCharts not loaded');
            return null;
        }

        payload = payload || {};
        options = options || {};
        const n = countPoints(payload);

        if (n === 0 && !['radialBar', 'treemap', 'sparkline'].includes(type)) {
            const el = hostEl(id);
            if (el) showEmpty(el, options.emptyMessage);
            return null;
        }

        const map = {
            line: () => renderLineArea(id, 'line', payload),
            area: () => renderLineArea(id, 'area', payload),
            bar: () => renderBar(id, payload, false),
            column: () => renderBar(id, payload, false),
            bar_horizontal: () => renderBar(id, payload, true),
            doughnut: () => renderDonut(id, payload),
            donut: () => renderDonut(id, payload),
            pie: () => renderDonut(id, payload),
            radialBar: () => renderRadialBar(id, payload),
            treemap: () => renderTreemap(id, payload),
            sparkline: () => renderSparkline(id, payload),
            polarArea: () => renderPolar(id, payload),
            polar: () => renderPolar(id, payload),
            mixed: () => renderMixed(id, payload),
            heatmap: () => renderHeatmap(id, payload),
            single_metric: () => renderSingleMetric(id, payload),
        };

        const fn = map[type] || map.line;
        return fn();
    }

    function renderMonthly(canvasId, monthly) {
        if (!monthly?.length) {
            const el = hostEl(canvasId);
            if (el) showEmpty(el);
            return null;
        }

        if (monthly.length === 1) {
            const m = monthly[0];
            return render(canvasId, 'single_metric', {
                labels: [m.label],
                data: [m.net_profit ?? m.net ?? 0],
                label: 'Laba bersih',
            });
        }

        return render(canvasId, 'area', {
            labels: monthly.map((m) => m.label),
            datasets: [
                { label: 'Net penghasilan', data: monthly.map((m) => m.net ?? 0) },
                { label: 'HPP', data: monthly.map((m) => m.cogs ?? 0) },
                { label: 'Laba bersih', data: monthly.map((m) => m.net_profit ?? 0) },
            ],
            stacked: false,
        });
    }

    /** Preset per halaman — type chart profesional */
    const pagePresets = {
        overview_gross_net: { type: 'area', fallback: 'area' },
        overview_fee: { type: 'donut' },
        overview_revenue: { type: 'line' },
        overview_ads: { type: 'column' },
        revenue_trend: { type: 'area' },
        revenue_orders: { type: 'column' },
        revenue_profit: { type: 'area' },
        revenue_summary: { type: 'bar_horizontal' },
        shopee_gross: { type: 'area' },
        shopee_fee_month: { type: 'column' },
        shopee_fee_pie: { type: 'donut' },
        shopee_fee_heatmap: { type: 'heatmap' },
        shopee_fee_stacked: { type: 'bar' },
        shopee_take_rate: { type: 'line' },
        ads_daily: { type: 'area' },
        ads_monthly: { type: 'column' },
        ads_top: { type: 'bar_horizontal' },
        ads_ctr: { type: 'sparkline' },
        profit_monthly: { type: 'area' },
        profit_fee_tree: { type: 'treemap' },
        profit_sku_tree: { type: 'treemap' },
        product_trend: { type: 'mixed' },
        product_roas: { type: 'radialBar' },
        targets_progress: { type: 'radialBar' },
        settlement_cash: { type: 'column' },
        roas_gauge: { type: 'radialBar' },
        bcg_mix: { type: 'polarArea' },
    };

    function renderPreset(id, presetKey, payload) {
        const p = pagePresets[presetKey];
        if (!p) return render(id, 'line', payload);
        return render(id, p.type, payload);
    }

    global.HubCharts = {
        colors: C,
        fmtRp,
        fmtCompact,
        fmtPct,
        render,
        renderPreset,
        renderMonthly,
        destroy,
        pagePresets,
    };
})(typeof window !== 'undefined' ? window : this);
