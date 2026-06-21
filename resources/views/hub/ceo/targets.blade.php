@extends('layouts.hub')
@section('title', 'Target Bulanan')
@section('content')
@php
    $p = $progress ?? [];
    $pace = $pace ?? [];
    $heroExtra = '<span class="small text-muted">Bulan '.e($year_month).' · '.e($shop['label'] ?? '').'</span>';
@endphp
@include('hub.partials.ceo.shell-open')
    @include('hub.partials.hub-zone-nav')
    <div class="hub-card mb-3" data-ceo="form">
        <div class="hub-card-body">
            <form method="POST" action="{{ route('ceo.targets.save') }}" class="hub-filter-bar">
                @csrf
                <input type="hidden" name="year_month" value="{{ $year_month }}">
                <div class="filter-item"><label class="hub-form-label">Target untung bersih (Rp)</label>
                    <input type="number" name="target_net_profit" class="hub-form-control" value="{{ $targets['net_profit'] ?? '' }}"></div>
                <div class="filter-item"><label class="hub-form-label">Target penjualan (Rp)</label>
                    <input type="number" name="target_gross" class="hub-form-control" value="{{ $targets['gross'] ?? '' }}"></div>
                <div class="filter-item"><label class="hub-form-label">Target unit terjual</label>
                    <input type="number" name="target_units" class="hub-form-control" value="{{ $targets['units'] ?? '' }}"></div>
                <div class="filter-item"><label class="hub-form-label">Maksimal iklan / bulan (Rp)</label>
                    <input type="number" name="ad_budget_cap" class="hub-form-control" value="{{ $targets['ad_budget'] ?? '' }}"></div>
                <div class="filter-item" style="align-self:flex-end"><button type="submit" class="hub-btn hub-btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
    <div class="report-kpi-hero" data-ceo="main-kpi">
        <div class="report-kpi-card {{ ($pace['on_track_net'] ?? false) ? 'positive' : 'warn' }}">
            <div class="label">Laba bersih</div>
            <div class="value">{{ hub_rp($actual['net_profit'] ?? 0, true) }}</div>
            <div class="sub">Target {{ hub_rp($targets['net_profit'] ?? 0) }} · {{ isset($p['net_pct']) ? number_format($p['net_pct']*100,1).'%' : '—' }}</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Seharusnya sudah (hari {{ $pace['day'] ?? 0 }}/{{ $pace['days_in_month'] ?? 30 }})</div>
            <div class="value">{{ hub_rp($pace['expected_net_by_today'] ?? 0) }}</div>
            <div class="sub">Pace target laba</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Unit terjual</div>
            <div class="value">{{ number_format($actual['units'] ?? 0) }}</div>
            <div class="sub">{{ isset($p['units_pct']) && ($targets['units'] ?? 0) > 0 ? number_format($p['units_pct']*100,1).'% dari target' : 'Isi target unit di form' }}</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Iklan terpakai</div>
            <div class="value">{{ hub_rp($actual['ads_total'] ?? 0) }}</div>
            <div class="sub">{{ isset($p['ads_pct']) && ($targets['ad_budget'] ?? 0) > 0 ? number_format($p['ads_pct']*100,1).'% dari budget' : '—' }}</div>
        </div>
    </div>

    <div class="fc-chart-trio mb-3">
        @include('hub.partials.chart-panel', ['id' => 'tgtNet', 'title' => 'Progress laba bersih', 'subtitle' => 'Radial gauge — target bulan ini', 'size' => 'compact'])
        @include('hub.partials.chart-panel', ['id' => 'tgtGross', 'title' => 'Progress penjualan', 'subtitle' => 'Radial gauge — omzet kotor', 'size' => 'compact'])
        @include('hub.partials.chart-panel', ['id' => 'tgtAds', 'title' => 'Budget iklan terpakai', 'subtitle' => 'Radial gauge — jangan lewati 100%', 'size' => 'compact'])
    </div>
@include('hub.partials.ceo.shell-close')
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const pct = (v) => Math.min(100, Math.max(0, (Number(v) || 0) * 100));
    HubCharts.renderPreset('tgtNet', 'targets_progress', {
        series: [pct(@json($p['net_pct'] ?? 0))],
        labels: ['Laba bersih'],
    });
    HubCharts.renderPreset('tgtGross', 'targets_progress', {
        series: [pct(@json($p['gross_pct'] ?? 0))],
        labels: ['Penjualan'],
    });
    HubCharts.renderPreset('tgtAds', 'targets_progress', {
        series: [pct(@json($p['ads_pct'] ?? 0))],
        labels: ['Iklan'],
    });
});
</script>
@endpush
