@extends('layouts.hub')

@section('title', 'Monitoring — Potongan Shopee')

@push('styles')
<link href="{{ asset('css/hub-monitoring.css') }}?v=1" rel="stylesheet">
@endpush

@section('content')
@php
    $s = $summary ?? [];
    $meta = $meta ?? [];
    $fb = $fee_breakdown ?? [];
    $fbPct = $fee_breakdown_pct ?? [];
    $charts = $charts ?? [];
@endphp

<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-percent me-2"></i>Potongan & Fee Shopee</h1>
        <div class="report-hero-meta">
            <span><i class="far fa-calendar-alt"></i> {{ $meta['period_label'] ?? '—' }}</span>
            <span>Total fee <strong>{{ hub_rp($s['fee_total'] ?? 0) }}</strong></span>
            <span>Take rate <strong>{{ hub_pct($s['take_rate'] ?? null) }}</strong></span>
        </div>
    </div>

    @include('hub.partials.monitoring-nav')
    @include('hub.partials.monitoring-filter')

    <div class="mon-kpi-row">
        <div class="mon-kpi"><div class="label">Pendapatan kotor</div><div class="value">{{ hub_rp($s['gross'] ?? 0) }}</div></div>
        <div class="mon-kpi"><div class="label">Setelah fee (net)</div><div class="value">{{ hub_rp($s['net'] ?? 0) }}</div></div>
        <div class="mon-kpi"><div class="label">Total potongan</div><div class="value amt-neg">{{ hub_rp($s['fee_total'] ?? 0) }}</div></div>
        <div class="mon-kpi"><div class="label">Take rate</div><div class="value">{{ hub_pct($s['take_rate'] ?? null) }}</div></div>
    </div>

    <div class="fc-chart-stack">
        @include('hub.partials.chart-panel', ['id' => 'chGrossNet', 'title' => 'Kotor vs net', 'subtitle' => 'Tren penghasilan sebelum & sesudah fee Shopee', 'size' => 'hero'])
        @include('hub.partials.chart-panel', ['id' => 'chFeeMonthly', 'title' => 'Biaya platform per bulan', 'subtitle' => 'Selisih pendapatan kotor − net', 'size' => 'default'])
        @include('hub.partials.chart-panel', ['id' => 'chFeePie', 'title' => 'Komposisi fee platform', 'subtitle' => 'Breakdown administrasi, layanan, proses', 'size' => 'square'])
        @include('hub.partials.chart-panel', ['id' => 'chTakeRate', 'title' => 'Take rate (%)', 'subtitle' => 'Persentase fee dari penjualan kotor', 'size' => 'compact'])
    </div>

    <div class="hub-card mt-3">
        <div class="hub-card-header"><h2 class="report-section-title">Detail komponen fee</h2></div>
        <div class="hub-card-body">
            @php $feeLabels = \App\Services\Finance\ShopeeFinancialExtractor::feeLabels(); @endphp
            @foreach($feeLabels as $key => $label)
            @if(($fb[$key] ?? 0) != 0)
            <div class="fee-bar-row">
                <div class="fee-label">
                    <span>{{ $label }}</span>
                    <strong>{{ hub_rp($fb[$key] ?? 0) }} · {{ hub_pct($fbPct[$key] ?? 0) }}</strong>
                </div>
                <div class="fee-bar-track">
                    <div class="fee-bar-fill" style="width: {{ min(100, ($fbPct[$key] ?? 0) * 100) }}%"></div>
                </div>
            </div>
            @endif
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const c = @json($charts);
    HubCharts.render('chFeePie', 'doughnut', c.fee_doughnut || {});
    HubCharts.render('chFeeMonthly', 'bar', c.fee_monthly || {});
    HubCharts.render('chTakeRate', 'line', {
        labels: (c.take_rate || {}).labels,
        datasets: [{ label: 'Take rate %', data: (c.take_rate || {}).data }]
    });
    HubCharts.render('chGrossNet', 'line', c.gross_vs_net || {});
});
</script>
@endpush
