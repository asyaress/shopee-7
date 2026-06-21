@extends('layouts.hub')

@section('title', 'Potongan Shopee')

@section('content')
@php
    $s = $summary ?? [];
    $meta = $meta ?? [];
    $fb = $fee_breakdown ?? [];
    $fbPct = $fee_breakdown_pct ?? [];
    $feeDetail = $fee_detail ?? [];
    $charts = $charts ?? [];
    $q = request()->query();
    $pageMeta = [
        ['icon' => 'fas fa-percent', 'label' => 'Fee total', 'value' => hub_rp($s['fee_total'] ?? 0)],
        ['icon' => 'fas fa-chart-pie', 'label' => 'Take rate', 'value' => hub_pct($s['take_rate'] ?? null)],
    ];
    $pageActions = hub_export_page_actions('revenue', $q);
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
        <div class="fc-chart-duo">
            @include('hub.partials.chart-panel', ['id' => 'chFeeHeatmap', 'title' => 'Heatmap komposisi fee', 'subtitle' => 'Intensitas biaya per komponen × bulan — warna lebih gelap = nominal lebih besar', 'size' => 'default'])
            @include('hub.partials.chart-panel', ['id' => 'chFeePie', 'title' => 'Komposisi fee platform', 'subtitle' => 'Proporsi administrasi, layanan, proses, AMS, dll.', 'size' => 'default'])
        </div>
        <div class="fc-chart-duo">
            @include('hub.partials.chart-panel', ['id' => 'chFeeStacked', 'title' => 'Stack fee per bulan', 'subtitle' => 'Kontribusi tiap komponen biaya per bulan', 'size' => 'default'])
            @include('hub.partials.chart-panel', ['id' => 'chFeeMonthly', 'title' => 'Total biaya platform per bulan', 'subtitle' => 'Agregat semua komponen fee Shopee', 'size' => 'default'])
        </div>
        @include('hub.partials.chart-panel', ['id' => 'chTakeRate', 'title' => 'Take rate (%)', 'subtitle' => 'Persentase fee dari penjualan kotor per bulan', 'size' => 'compact'])
    </div>

    <div class="hub-card mt-3">
        <div class="hub-card-header">
            <div>
                <h2 class="report-section-title">Detail komponen biaya platform</h2>
                <p class="report-section-desc mb-0">Rincian lengkap dari data escrow Shopee — nominal, proporsi, dan rata-rata per order.</p>
            </div>
        </div>
        <div class="hub-card-body">
            @include('hub.partials.fee-composition-detail')
        </div>
    </div>
@include('hub.partials.ceo.shell-close')
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const c = @json($charts);
    HubCharts.renderPreset('chGrossNet', 'shopee_gross', c.gross_vs_net || {});
    HubCharts.renderPreset('chFeeHeatmap', 'shopee_fee_heatmap', c.fee_heatmap || {});
    HubCharts.renderPreset('chFeePie', 'shopee_fee_pie', c.fee_doughnut || {});
    HubCharts.renderPreset('chFeeStacked', 'shopee_fee_stacked', Object.assign({ stacked: true }, c.fee_stacked || {}));
    HubCharts.renderPreset('chFeeMonthly', 'shopee_fee_month', c.fee_monthly || {});
    HubCharts.renderPreset('chTakeRate', 'shopee_take_rate', {
        labels: (c.take_rate || {}).labels,
        datasets: [{ label: 'Take rate %', data: (c.take_rate || {}).data }],
    });
});
</script>
@endpush
