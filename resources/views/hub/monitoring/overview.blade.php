@extends('layouts.hub')

@section('title', 'Ringkasan — Cockpit CEO')

@push('styles')
<link href="{{ asset('css/hub-monitoring.css') }}?v=6" rel="stylesheet">
@endpush

@section('content')
@php
    $s = $summary ?? [];
    $meta = $meta ?? [];
    $charts = $charts ?? [];
    $health = ($analysis ?? [])['health_score'] ?? 0;
    $ac = $action_center ?? [];
    $td = $target_dashboard ?? [];
    $hpp = $hpp_summary ?? [];
    $pace = $td['pace'] ?? [];
    $progress = $td['progress'] ?? [];
    $q = request()->query();
@endphp

<div class="report-shell">
    <div class="report-hero">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h1><i class="fas fa-gauge-high me-2"></i>Cockpit CEO</h1>
                <div class="report-hero-meta">
                    <span><i class="far fa-calendar-alt"></i> {{ $meta['period_label'] ?? '—' }}</span>
                    <span><i class="fas fa-store"></i> {{ $shop['label'] ?? '' }}</span>
                    <span><i class="fas fa-sync-alt"></i> {{ $meta['generated_at'] ?? now()->format('d M Y H:i') }}</span>
                </div>
            </div>
            <div class="report-health">
                <div class="report-health-ring" style="--score: {{ $health }}"><span>{{ $health }}</span></div>
                <div><strong>Skor kesehatan</strong><div class="small opacity-90">Data · margin · iklan</div></div>
            </div>
        </div>
    </div>

    @include('hub.partials.hub-zone-nav')
    @include('hub.partials.monitoring-filter')

    {{-- Alert strip --}}
    <div class="cockpit-alert-row mb-3">
        @if(!($hpp['gate_ok'] ?? true))
        <div class="cockpit-alert warn">
            <i class="fas fa-exclamation-triangle"></i>
            HPP {{ $hpp['complete_pct_label'] ?? '—' }} — <a href="{{ route('hpp.index') }}">lengkapi</a>
        </div>
        @endif
        @if(($ac['counts']['urgent'] ?? 0) > 0)
        <div class="cockpit-alert danger">
            <i class="fas fa-bolt"></i>
            {{ $ac['counts']['urgent'] }} prioritas urgent — <a href="{{ route('monitoring.actions', $q) }}">buka aksi</a>
        </div>
        @endif
        @if(isset($pace['on_track_net']) && $pace['on_track_net'] === false)
        <div class="cockpit-alert warn">
            <i class="fas fa-bullseye"></i>
            Target laba off-track — <a href="{{ route('ceo.targets', $q) }}">detail target</a>
        </div>
        @endif
    </div>

    {{-- KPI utama --}}
    <div class="report-kpi-hero mb-3">
        <div class="report-kpi-card {{ ($s['net_profit'] ?? 0) >= 0 ? 'positive' : 'negative' }}">
            <div class="label">Laba bersih</div>
            <div class="value">{{ hub_rp($s['net_profit'] ?? 0, true) }}</div>
            <div class="sub">Margin {{ hub_pct($s['margin'] ?? null) }}</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Penjualan kotor</div>
            <div class="value">{{ hub_rp($s['gross'] ?? 0, true) }}</div>
            <div class="sub">{{ hub_num($s['orders_count'] ?? 0) }} order · {{ hub_num($s['units_sold'] ?? 0) }} pcs</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Iklan · ROAS bisnis</div>
            <div class="value">{{ hub_rp($s['ads_total'] ?? 0) }}</div>
            <div class="sub">{{ isset($s['roas']) && $s['roas'] ? number_format($s['roas'], 2).'x' : '—' }} · ACOS {{ hub_pct($s['acos'] ?? null) }}</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Target net {{ $td['year_month'] ?? '' }}</div>
            <div class="value">{{ hub_pct($progress['net_pct'] ?? null) }}</div>
            <div class="sub">Pace hari {{ $pace['day'] ?? '—' }}/{{ $pace['days_in_month'] ?? '—' }}</div>
        </div>
    </div>

    {{-- Quick nav cards --}}
    <div class="mon-section-cards mb-3">
        <a href="{{ route('monitoring.profit', $q) }}" class="mon-section-card profit">
            <div class="icon-wrap"><i class="fas fa-chart-pie"></i></div>
            <h3>Laba Detail</h3>
            <div class="kpi">{{ hub_rp($s['gross_profit'] ?? 0) }}</div>
            <div class="sub">Laba kotor · P&amp;L lengkap</div>
        </a>
        <a href="{{ route('monitoring.rekap', $q) }}" class="mon-section-card revenue">
            <div class="icon-wrap"><i class="fas fa-table"></i></div>
            <h3>Rekap Bulanan</h3>
            <div class="kpi">12 bln</div>
            <div class="sub">ROAS · ACOS · margin per bulan</div>
        </a>
        <a href="{{ route('ceo.roas', $q) }}" class="mon-section-card ads">
            <div class="icon-wrap"><i class="fas fa-chart-line"></i></div>
            <h3>ROAS Center</h3>
            <div class="kpi">{{ isset($s['roas']) && $s['roas'] ? number_format($s['roas'], 2).'x' : '—' }}</div>
            <div class="sub">AMS GMV · Set ROAS rekomendasi</div>
        </a>
        <a href="{{ route('monitoring.product-analysis.index', $q) }}" class="mon-section-card shopee">
            <div class="icon-wrap"><i class="fas fa-microscope"></i></div>
            <h3>Analisis Produk</h3>
            <div class="kpi">{{ $ac['counts']['bleeders'] ?? 0 }} bleeder</div>
            <div class="sub">Drill-down per SKU + varian</div>
        </a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="hub-card h-100">
                <div class="hub-card-header d-flex justify-content-between">
                    <h2 class="report-section-title mb-0">Pusat Aksi — ringkas</h2>
                    <a href="{{ route('monitoring.actions', $q) }}" class="small">Semua →</a>
                </div>
                <div class="hub-card-body">
                    <div class="mon-kpi-row mb-2">
                        <div class="mon-kpi"><div class="label">Urgent</div><div class="value text-danger">{{ $ac['counts']['urgent'] ?? 0 }}</div></div>
                        <div class="mon-kpi"><div class="label">Scale</div><div class="value">{{ $ac['counts']['opportunities'] ?? 0 }}</div></div>
                        <div class="mon-kpi"><div class="label">Bleeder</div><div class="value text-danger">{{ $ac['counts']['bleeders'] ?? 0 }}</div></div>
                    </div>
                    @forelse(array_slice($ac['urgent'] ?? [], 0, 3) as $item)
                        @include('hub.partials.action-item', ['item' => $item])
                    @empty
                        <p class="text-muted small mb-0">Tidak ada aksi urgent — lanjut pantau target.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="hub-card h-100">
                <div class="hub-card-header d-flex justify-content-between">
                    <h2 class="report-section-title mb-0">Target bulan ini</h2>
                    <a href="{{ route('ceo.targets', $q) }}" class="small">Kelola →</a>
                </div>
                <div class="hub-card-body">
                    <table class="report-table report-table-compact">
                        <tbody>
                            <tr><td>Net profit</td><td class="num">{{ hub_rp($td['actual']['net_profit'] ?? 0, true) }} / {{ hub_rp($td['targets']['net_profit'] ?? 0, true) }}</td><td class="num">{{ hub_pct($progress['net_pct'] ?? null) }}</td></tr>
                            <tr><td>Gross</td><td class="num">{{ hub_rp($td['actual']['gross'] ?? 0, true) }} / {{ hub_rp($td['targets']['gross'] ?? 0, true) }}</td><td class="num">{{ hub_pct($progress['gross_pct'] ?? null) }}</td></tr>
                            <tr><td>Budget iklan</td><td class="num">{{ hub_rp($td['actual']['ads_total'] ?? 0, true) }} / {{ hub_rp($td['targets']['ad_budget'] ?? 0, true) }}</td><td class="num">{{ hub_pct($progress['ads_pct'] ?? null) }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="mon-kpi-row mb-3">
        <div class="mon-kpi"><div class="label">Fee Shopee</div><div class="value">{{ hub_rp($s['fee_total'] ?? 0) }}</div><div class="sub">Take {{ hub_pct($s['take_rate'] ?? null) }}</div></div>
        <div class="mon-kpi"><div class="label">HPP</div><div class="value">{{ hub_rp($s['cogs'] ?? 0) }}</div></div>
        <div class="mon-kpi"><div class="label">Operasional</div><div class="value">{{ hub_rp($s['operational_total'] ?? 0) }}</div></div>
        <div class="mon-kpi"><div class="label">HPP data</div><div class="value">{{ $hpp['complete_pct_label'] ?? '—' }}</div></div>
    </div>

    <div class="fc-chart-stack">
        @include('hub.partials.chart-panel', ['id' => 'ovGrossNet', 'title' => 'Kotor vs net (bulanan)', 'subtitle' => 'Penghasilan sebelum & sesudah fee platform', 'size' => 'hero', 'badge' => 'Shopee'])
        @include('hub.partials.chart-panel', ['id' => 'ovFeePie', 'title' => 'Komposisi fee', 'subtitle' => 'Administrasi, layanan, proses', 'size' => 'square'])
        @include('hub.partials.chart-panel', ['id' => 'ovRevenue', 'title' => 'Tren pendapatan', 'subtitle' => 'Kotor & net per bulan', 'size' => 'hero'])
        @include('hub.partials.chart-panel', ['id' => 'ovAds', 'title' => 'Spend iklan', 'subtitle' => 'Biaya promosi per bulan', 'size' => 'default', 'badge' => 'AMS'])
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sh = @json($charts['shopee'] ?? []);
    const rev = @json($charts['revenue'] ?? []);
    const ads = @json($charts['ads'] ?? []);
    HubCharts.render('ovGrossNet', 'line', sh.gross_vs_net || {});
    HubCharts.render('ovFeePie', 'doughnut', sh.fee_doughnut || {});
    HubCharts.render('ovRevenue', 'line', rev.revenue_trend || {});
    HubCharts.render('ovAds', 'bar', { labels: (ads.ads_monthly || {}).labels, data: (ads.ads_monthly || {}).data, label: 'Iklan (Rp)' });
});
</script>
@endpush
