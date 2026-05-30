@extends('layouts.hub')
@section('title', 'ROAS Advisor — CEO')
@push('styles')<link href="{{ asset('css/hub-monitoring.css') }}?v=2" rel="stylesheet">@endpush
@section('content')
@php $m = $roas['metrics'] ?? []; $rec = $roas['recommendation'] ?? []; @endphp
<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-chart-line me-2"></i>ROAS Advisor</h1>
        <p class="small mb-0">Prediksi target ROAS impas & rekomendasi set di Shopee Ads</p>
    </div>
    @include('hub.partials.ceo-nav')
    @include('hub.partials.monitoring-filter')

    <div class="hub-card mb-3">
        <div class="hub-card-header"><h2 class="report-section-title">Dua definisi ROAS</h2></div>
        <div class="hub-card-body small">
            <p><strong>Shopee Ads:</strong> {{ $roas['definitions']['shopee_ads'] ?? '' }}</p>
            <p class="mb-0"><strong>Bisnis (app):</strong> {{ $roas['definitions']['business'] ?? '' }}</p>
        </div>
    </div>

    <div class="report-kpi-hero mb-3">
        <div class="report-kpi-card">
            <div class="label">ROAS bisnis (kotor÷iklan)</div>
            <div class="value">{{ isset($m['business_roas']) ? number_format($m['business_roas'], 2).'x' : '—' }}</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">ROAS Shopee (GMV÷iklan)</div>
            <div class="value">{{ isset($m['shopee_ads_roas']) ? number_format($m['shopee_ads_roas'], 2).'x' : '—' }}</div>
        </div>
        <div class="report-kpi-card positive">
            <div class="label">Target ROAS (disarankan)</div>
            <div class="value">{{ isset($m['target_roas_gross']) ? number_format($m['target_roas_gross'], 2).'x' : '—' }}</div>
            <div class="sub">Impas {{ isset($m['breakeven_roas_gross']) ? number_format($m['breakeven_roas_gross'], 2).'x' : '—' }}</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Target di Shopee Ads (GMV)</div>
            <div class="value">{{ isset($m['target_roas_shopee_gmv']) ? number_format($m['target_roas_shopee_gmv'], 2).'x' : '—' }}</div>
        </div>
    </div>

    <div class="mon-decision-card mb-3">
        <h2 class="h5">{{ $rec['title'] ?? 'Rekomendasi' }}</h2>
        <ul class="mb-0">
            @foreach($rec['lines'] ?? [] as $line)
            <li>{!! preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', e($line)) !!}</li>
            @endforeach
        </ul>
    </div>

    <div class="hub-card">
        <div class="hub-card-header"><h2 class="report-section-title">Per SKU — gap ke target ROAS</h2></div>
        <div class="hub-card-body p-0">
            <table class="report-table">
                <thead><tr><th>Produk</th><th class="num">ROAS sekarang</th><th class="num">Target</th><th class="num">Gap</th><th class="num">Laba</th></tr></thead>
                <tbody>
                @forelse($roas['products'] ?? [] as $p)
                <tr>
                    <td><a href="{{ route('monitoring.product', ['product' => $p['product_id']] + request()->query()) }}">{{ $p['name'] }}</a></td>
                    <td class="num">{{ number_format($p['current_roas'], 2) }}x</td>
                    <td class="num">{{ $p['target_roas'] ? number_format($p['target_roas'], 2).'x' : '—' }}</td>
                    <td class="num {{ ($p['gap'] ?? 0) < 0 ? 'amt-neg' : 'amt-pos' }}">{{ $p['gap'] !== null ? number_format($p['gap'], 2) : '—' }}</td>
                    <td class="num">{{ hub_rp($p['net_profit'], true) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-muted text-center py-3">Sync iklan dulu atau pilih periode dengan spend iklan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
