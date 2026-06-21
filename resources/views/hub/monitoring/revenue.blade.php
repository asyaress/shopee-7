@extends('layouts.hub')

@section('title', 'Pendapatan')

@section('content')
@php
    $s = $summary ?? [];
    $meta = $meta ?? [];
    $charts = $charts ?? [];
    $heroExtra = '<span class="small text-muted"><i class="far fa-calendar-alt"></i> '.e($meta['period_label'] ?? '—').' · '.hub_num($s['orders_count'] ?? 0).' order</span>';
@endphp

@include('hub.partials.ceo.shell-open')

    @include('hub.partials.hub-zone-nav')
    @include('hub.partials.monitoring-filter')

    <div class="mon-kpi-row" data-ceo="main-kpi">
        <div class="mon-kpi"><div class="label">Penjualan kotor</div><div class="value">{{ hub_rp($s['gross'] ?? 0) }}</div></div>
        <div class="mon-kpi"><div class="label">Net penghasilan</div><div class="value">{{ hub_rp($s['net'] ?? 0) }}</div></div>
        <div class="mon-kpi"><div class="label">Laba kotor</div><div class="value">{{ hub_rp($s['gross_profit'] ?? 0) }}</div></div>
        <div class="mon-kpi"><div class="label">Laba bersih</div><div class="value {{ ($s['net_profit'] ?? 0) >= 0 ? '' : 'amt-neg' }}">{{ hub_rp($s['net_profit'] ?? 0, true) }}</div></div>
    </div>

    <div class="fc-chart-stack">
        @include('hub.partials.chart-panel', ['id' => 'chRevenue', 'title' => 'Tren pendapatan', 'subtitle' => 'Penjualan kotor vs net penghasilan per bulan', 'size' => 'hero'])
        @include('hub.partials.chart-panel', ['id' => 'chProfitStack', 'title' => 'Laba kotor vs bersih', 'subtitle' => 'Setelah HPP, iklan & biaya operasional', 'size' => 'hero'])
        @include('hub.partials.chart-panel', ['id' => 'chOrders', 'title' => 'Volume pesanan', 'subtitle' => 'Jumlah order terkonfirmasi per bulan', 'size' => 'compact'])
        @include('hub.partials.chart-panel', ['id' => 'chSummaryBar', 'title' => 'Perbandingan periode', 'subtitle' => 'Ringkasan alur nilai agregat', 'size' => 'default'])
    </div>

    @if(!empty($monthly))
    <div class="hub-card mt-3">
        <div class="hub-card-header"><h2 class="report-section-title">Rekap bulanan</h2></div>
        <div class="hub-card-body p-0">
            <div class="hub-table-wrap">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th class="num">Pesanan</th>
                            <th class="num">Kotor</th>
                            <th class="num">Net</th>
                            <th class="num">Laba bersih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($monthly as $m)
                        <tr>
                            <td><strong>{{ $m['label'] }}</strong></td>
                            <td class="num">{{ $m['orders'] ?? 0 }}</td>
                            <td class="num">{{ hub_rp($m['gross'] ?? 0) }}</td>
                            <td class="num">{{ hub_rp($m['net'] ?? 0) }}</td>
                            <td class="num {{ ($m['net_profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg' }}">{{ hub_rp($m['net_profit'] ?? 0, true) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
@include('hub.partials.ceo.shell-close')
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const c = @json($charts);
    HubCharts.renderPreset('chRevenue', 'revenue_trend', c.revenue_trend || {});
    HubCharts.renderPreset('chOrders', 'revenue_orders', {
        labels: (c.orders_bar || {}).labels,
        data: (c.orders_bar || {}).data,
        label: 'Pesanan',
    });
    HubCharts.renderPreset('chProfitStack', 'revenue_profit', c.profit_stack || {});
    HubCharts.renderPreset('chSummaryBar', 'revenue_summary', {
        labels: (c.summary_compare || {}).labels,
        data: (c.summary_compare || {}).data,
        label: 'Nilai (Rp)',
    });
});
</script>
@endpush
