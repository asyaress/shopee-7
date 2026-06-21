@extends('layouts.hub')

@section('title', 'Potongan Shopee')

@section('content')
@php
    $s = $summary ?? [];
    $meta = $meta ?? [];
    $fb = $fee_breakdown ?? [];
    $fbPct = $fee_breakdown_pct ?? [];
    $charts = $charts ?? [];
    $pageMeta = [
        ['icon' => 'fas fa-percent', 'label' => 'Fee total', 'value' => hub_rp($s['fee_total'] ?? 0)],
        ['icon' => 'fas fa-chart-pie', 'label' => 'Take rate', 'value' => hub_pct($s['take_rate'] ?? null)],
    ];
@endphp

@include('hub.partials.ceo.shell-open')

    @include('hub.partials.hub-zone-nav')
    @include('hub.partials.monitoring-filter')

    <div class="mon-kpi-row" data-ceo="main-kpi">
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
@include('hub.partials.ceo.shell-close')
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const c = @json($charts);
    HubCharts.renderPreset('chFeePie', 'shopee_fee_pie', c.fee_doughnut || {});
    HubCharts.renderPreset('chFeeMonthly', 'shopee_fee_month', c.fee_monthly || {});
    HubCharts.renderPreset('chTakeRate', 'shopee_take_rate', {
        labels: (c.take_rate || {}).labels,
        datasets: [{ label: 'Take rate %', data: (c.take_rate || {}).data }],
    });
    HubCharts.renderPreset('chGrossNet', 'shopee_gross', c.gross_vs_net || {});
});
</script>
@endpush
