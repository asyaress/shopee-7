@extends('layouts.hub')

@section('title', 'Monitoring — Iklan Shopee')

@push('styles')
<link href="{{ asset('css/hub-monitoring.css') }}?v=1" rel="stylesheet">
@include('hub.partials.datatables-assets')
@endpush

@section('content')
@php
    $s = $summary ?? [];
    $meta = $meta ?? [];
    $charts = $charts ?? [];
    $roas = $charts['roas_acos'] ?? [];
@endphp

<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-bullhorn me-2"></i>Iklan Shopee Ads</h1>
        <div class="report-hero-meta">
            <span><i class="far fa-calendar-alt"></i> {{ $meta['period_label'] ?? '—' }}</span>
            <span>Spend {{ hub_rp($s['ads_total'] ?? 0) }}</span>
            <span>ROAS {{ isset($s['roas']) && $s['roas'] ? number_format($s['roas'], 2).'x' : '—' }}</span>
            <span>ACOS {{ hub_pct($s['acos'] ?? null) }}</span>
        </div>
    </div>

    @include('hub.partials.monitoring-nav')
    @include('hub.partials.monitoring-filter')

    @php $adsShop = ($recommendations ?? [])['ads_shop'] ?? []; @endphp
    <div class="mon-kpi-row">
        <div class="mon-kpi"><div class="label">Total spend</div><div class="value">{{ hub_rp($s['ads_total'] ?? 0) }}</div></div>
        <div class="mon-kpi"><div class="label">ROAS bisnis</div><div class="value">{{ isset($s['roas']) && $s['roas'] ? number_format($s['roas'], 2).'x' : '—' }}</div></div>
        <div class="mon-kpi"><div class="label">CPC toko</div><div class="value">{{ isset($adsShop['cpc_shop']) ? hub_rp($adsShop['cpc_shop']) : '—' }}</div></div>
        <div class="mon-kpi"><div class="label">Budget bulanan</div><div class="value">
            @if(($adsShop['budget_monthly'] ?? 0) > 0)
                {{ hub_pct($adsShop['budget_used_pct'] ?? null) }}
                <div class="small text-muted">sisa {{ hub_rp($adsShop['budget_remaining'] ?? 0) }}</div>
            @else
                <a href="{{ route('ceo.targets') }}" class="small">Set budget →</a>
            @endif
        </div></div>
    </div>

    @if(!empty($adsShop['recommendation']['lines']))
    <div class="mon-decision-card mon-action-{{ $adsShop['recommendation']['severity'] ?? 'info' }} mb-3">
        <h2 class="h6 mb-2">{{ $adsShop['recommendation']['title'] ?? 'Rekomendasi iklan' }}</h2>
        <ul class="small mb-0">
            @foreach($adsShop['recommendation']['lines'] as $line)
            <li>{!! preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', e($line)) !!}</li>
            @endforeach
        </ul>
        @if($adsShop['target_roas_business'] ?? null)
        <p class="small mt-2 mb-0">Target ROAS dari data aktual: <strong>{{ $adsShop['target_roas_business'] }}x</strong>
            @if($adsShop['shopee_roas_gmv'] ?? null) · ROAS GMV Shopee: {{ $adsShop['shopee_roas_gmv'] }}x @endif
        </p>
        @endif
    </div>
    @endif

    <div class="fc-chart-stack">
        @include('hub.partials.chart-panel', ['id' => 'chAdsDaily', 'title' => 'Spend & GMV harian', 'subtitle' => 'Biaya iklan dan GMV atribusi per hari', 'size' => 'hero', 'badge' => 'Live'])
        @include('hub.partials.chart-panel', ['id' => 'chAdsMonthly', 'title' => 'Spend per bulan', 'subtitle' => 'Agregat biaya promosi bulanan', 'size' => 'default'])
        @include('hub.partials.chart-panel', ['id' => 'chTopSpend', 'title' => 'Top produk by spend', 'subtitle' => '8 SKU dengan biaya iklan tertinggi', 'size' => 'default'])
        @include('hub.partials.chart-panel', ['id' => 'chCtr', 'title' => 'CTR harian (%)', 'subtitle' => 'Rasio klik terhadap impression', 'size' => 'compact'])
    </div>

    @php
        $adsProducts = collect($products ?? [])->sortByDesc('ads_spend')->values();
    @endphp
    @if($adsProducts->isNotEmpty())
    <div class="hub-card mt-3">
        <div class="hub-card-header">
            <h2 class="report-section-title">Performa iklan per produk</h2>
            <span class="small text-muted">{{ number_format($adsProducts->count(), 0, ',', '.') }} produk</span>
        </div>
        <div class="hub-card-body p-0">
            <div class="hub-table-wrap">
                <table id="adsProductsTable" class="report-table w-100">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="num">Spend</th>
                            <th class="num">CPC</th>
                            <th class="num">ROAS</th>
                            <th class="num">Target</th>
                            <th class="num">Harga</th>
                            <th class="num">Laba</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($adsProducts as $p)
                        @php
                            $am = $p['ads_metrics'] ?? [];
                            $pr = $p['pricing'] ?? [];
                            $ps = $pr['status'] ?? '';
                            $recommendedPrice = $pr['prices']['recommended_gross'] ?? $pr['prices']['avg_selling'] ?? 0;
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('monitoring.product', ['product' => $p['product_id']] + request()->query()) }}">{{ $p['name'] ?? '—' }}</a>
                                @if($ps)<br><span class="price-status-{{ $ps === 'ok' ? 'ok' : ($ps === 'too_low' || $ps === 'not_covering' ? 'low' : 'review') }}">{{ $pr['status_label'] ?? '' }}</span>@endif
                            </td>
                            <td class="num" data-order="{{ (float) ($p['ads_spend'] ?? 0) }}">{{ hub_rp($p['ads_spend'] ?? 0) }}</td>
                            <td class="num" data-order="{{ (float) ($am['cpc'] ?? -1) }}">{{ isset($am['cpc']) ? hub_rp($am['cpc']) : '—' }}</td>
                            <td class="num" data-order="{{ (float) ($p['roas'] ?? -1) }}">{{ isset($p['roas']) && $p['roas'] ? number_format($p['roas'], 2).'x' : '—' }}</td>
                            <td class="num" data-order="{{ (float) ($am['target_roas'] ?? -1) }}">{{ isset($am['target_roas']) ? ($am['target_roas'].'x') : '—' }}</td>
                            <td class="num" data-order="{{ (float) $recommendedPrice }}">{{ hub_rp($recommendedPrice) }}</td>
                            <td class="num {{ ($p['net_profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg' }}" data-order="{{ (float) ($p['net_profit'] ?? 0) }}">{{ hub_rp($p['net_profit'] ?? 0, true) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
@include('hub.partials.datatables-scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const c = @json($charts);
    HubCharts.render('chAdsDaily', 'line', c.ads_daily || {});
    HubCharts.render('chAdsMonthly', 'bar', { labels: (c.ads_monthly || {}).labels, data: (c.ads_monthly || {}).data, label: 'Spend (Rp)' });
    HubCharts.render('chTopSpend', 'bar_horizontal', c.top_spend || {});
    HubCharts.render('chCtr', 'line', {
        labels: (c.ctr_daily || {}).labels,
        datasets: [{ label: 'CTR %', data: (c.ctr_daily || {}).data }]
    });

    HubDataTable.init('#adsProductsTable', {
        pageLength: 25,
        order: [[1, 'desc'], [0, 'asc']],
    });
});
</script>
@endpush
