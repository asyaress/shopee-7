@extends('layouts.hub')

@section('title', 'Ringkasan Toko')

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
    $heroExtra = '<div class="d-flex flex-wrap align-items-center gap-3 small text-muted">'
        .'<span><i class="far fa-calendar-alt"></i> '.e($meta['period_label'] ?? '—').'</span>'
        .'<span><i class="fas fa-store"></i> '.e($shop['label'] ?? '').'</span>'
        .'<span class="d-inline-flex align-items-center gap-1"><span class="report-health-ring" style="--score:'.$health.';width:36px;height:36px;font-size:0.75rem"><span>'.$health.'</span></span> Skor kesehatan</span>'
        .'</div>';
@endphp

@include('hub.partials.ceo.shell-open')

    @include('hub.partials.hub-zone-nav')
    @include('hub.partials.monitoring-filter')

    <div class="cockpit-alert-row mb-3" data-ceo="alerts">
        @if(!($hpp['gate_ok'] ?? true))
        <div class="cockpit-alert warn">
            <i class="fas fa-exclamation-triangle"></i>
            HPP baru {{ $hpp['complete_pct_label'] ?? '—' }} — <a href="{{ route('hpp.index') }}">isi dulu</a>
        </div>
        @endif
        @if(($ac['counts']['urgent'] ?? 0) > 0)
        <div class="cockpit-alert danger">
            <i class="fas fa-bolt"></i>
            {{ $ac['counts']['urgent'] }} perlu ditangani hari ini — <a href="{{ route('monitoring.actions', $q) }}">lihat daftar</a>
        </div>
        @endif
        @if(isset($pace['on_track_net']) && $pace['on_track_net'] === false)
        <div class="cockpit-alert warn">
            <i class="fas fa-bullseye"></i>
            Target laba belum tercapai — <a href="{{ route('ceo.targets', $q) }}">cek target</a>
        </div>
        @endif
    </div>

    <div class="report-kpi-hero mb-3" data-ceo="main-kpi">
        <div class="report-kpi-card {{ ($s['net_profit'] ?? 0) >= 0 ? 'positive' : 'negative' }}">
            <div class="label">Laba bersih</div>
            <div class="value">{{ hub_rp($s['net_profit'] ?? 0, true) }}</div>
            <div class="sub">Uang tersisa setelah semua potongan</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Penjualan kotor</div>
            <div class="value">{{ hub_rp($s['gross'] ?? 0, true) }}</div>
            <div class="sub">{{ hub_num($s['orders_count'] ?? 0) }} order</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Iklan · ROAS bisnis</div>
            <div class="value">{{ hub_rp($s['ads_total'] ?? 0) }}</div>
            <div class="sub">ROAS {{ isset($s['roas']) && $s['roas'] ? number_format($s['roas'], 1).'x' : '—' }}</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Target net {{ $td['year_month'] ?? '' }}</div>
            <div class="value">{{ hub_pct($progress['net_pct'] ?? null) }}</div>
            <div class="sub">Hari {{ $pace['day'] ?? '—' }}/{{ $pace['days_in_month'] ?? '—' }}</div>
        </div>
    </div>

    {{-- Quick nav cards --}}
    <div class="mon-section-cards mb-3">
        <a href="{{ route('monitoring.profit', $q) }}" class="mon-section-card profit">
            <div class="icon-wrap"><i class="fas fa-chart-pie"></i></div>
            <h3>Laba Detail</h3>
            <div class="kpi">{{ hub_rp($s['gross_profit'] ?? 0) }}</div>
            <div class="sub">Laporan lengkap untung/rugi</div>
        </a>
        <a href="{{ route('monitoring.rekap', $q) }}" class="mon-section-card revenue">
            <div class="icon-wrap"><i class="fas fa-table"></i></div>
            <h3>Rekap Bulanan</h3>
            <div class="kpi">12 bln</div>
            <div class="sub">Bandingkan tiap bulan</div>
        </a>
        <a href="{{ route('ceo.roas', $q) }}" class="mon-section-card ads">
            <div class="icon-wrap"><i class="fas fa-chart-line"></i></div>
            <h3>Analisa Iklan</h3>
            <div class="kpi">{{ isset($s['roas']) && $s['roas'] ? number_format($s['roas'], 2).'x' : '—' }}</div>
            <div class="sub">Set ROAS · aksi per produk</div>
        </a>
        <a href="{{ route('monitoring.product-analysis.index', $q) }}" class="mon-section-card shopee">
            <div class="icon-wrap"><i class="fas fa-microscope"></i></div>
            <h3>Analisis Produk</h3>
            <div class="kpi">{{ $ac['counts']['bleeders'] ?? 0 }} bleeder</div>
            <div class="sub">{{ $ac['counts']['bleeders'] ?? 0 }} produk rugi</div>
        </a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="hub-card h-100">
                <div class="hub-card-header d-flex justify-content-between">
                    <h2 class="report-section-title mb-0">Yang harus dikerjakan</h2>
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
                        <p class="text-muted small mb-0">Tidak ada urgent — lanjut pantau target.</p>
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
                            <tr><td>Laba bersih</td><td class="num">{{ hub_rp($td['actual']['net_profit'] ?? 0, true) }} / {{ hub_rp($td['targets']['net_profit'] ?? 0, true) }}</td><td class="num">{{ hub_pct($progress['net_pct'] ?? null) }}</td></tr>
                            <tr><td>Penjualan</td><td class="num">{{ hub_rp($td['actual']['gross'] ?? 0, true) }} / {{ hub_rp($td['targets']['gross'] ?? 0, true) }}</td><td class="num">{{ hub_pct($progress['gross_pct'] ?? null) }}</td></tr>
                            <tr><td>Iklan</td><td class="num">{{ hub_rp($td['actual']['ads_total'] ?? 0, true) }} / {{ hub_rp($td['targets']['ad_budget'] ?? 0, true) }}</td><td class="num">{{ hub_pct($progress['ads_pct'] ?? null) }}</td></tr>
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
        @include('hub.partials.chart-panel', ['id' => 'ovGrossNet', 'title' => 'Omzet vs sisa uang', 'subtitle' => 'Sebelum & sesudah potongan Shopee', 'size' => 'hero', 'badge' => 'Shopee'])
        @include('hub.partials.chart-panel', ['id' => 'ovFeePie', 'title' => 'Potongan Shopee', 'subtitle' => 'Admin, layanan, proses', 'size' => 'square'])
        @include('hub.partials.chart-panel', ['id' => 'ovRevenue', 'title' => 'Tren penjualan', 'subtitle' => 'Kotor & net per bulan', 'size' => 'hero'])
        @include('hub.partials.chart-panel', ['id' => 'ovAds', 'title' => 'Biaya iklan', 'subtitle' => 'Per bulan', 'size' => 'default', 'badge' => 'AMS'])
    </div>
@include('hub.partials.ceo.shell-close')
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sh = @json($charts['shopee'] ?? []);
    const rev = @json($charts['revenue'] ?? []);
    const ads = @json($charts['ads'] ?? []);
    HubCharts.renderPreset('ovGrossNet', 'overview_gross_net', sh.gross_vs_net || {});
    HubCharts.renderPreset('ovFeePie', 'overview_fee', sh.fee_doughnut || {});
    HubCharts.renderPreset('ovRevenue', 'overview_revenue', rev.revenue_trend || {});
    HubCharts.renderPreset('ovAds', 'overview_ads', {
        labels: (ads.ads_monthly || {}).labels,
        data: (ads.ads_monthly || {}).data,
        label: 'Iklan (Rp)',
    });
});
</script>
@endpush
