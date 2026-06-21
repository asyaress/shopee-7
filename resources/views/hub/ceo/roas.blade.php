@extends('layouts.hub')
@section('title', 'ROAS Center')
@push('styles')<link href="{{ asset('css/hub-monitoring.css') }}?v=6" rel="stylesheet">@endpush
@section('content')
@php
    $m = $roas['metrics'] ?? [];
    $rec = $roas['recommendation'] ?? [];
    $defs = $roas['definitions'] ?? [];
    $q = request()->query();
@endphp
<div class="report-shell">
    <div class="report-hero">
        <div class="d-flex flex-wrap justify-content-between gap-2">
            <div>
                <h1><i class="fas fa-chart-line me-2"></i>ROAS Center</h1>
                <p class="small mb-0 opacity-90">Analisis ROAS AMS + bisnis · rekomendasi Set ROAS per produk</p>
            </div>
            <div class="text-end">
                <span class="hub-pill hub-pill-light">{{ $shop['label'] ?? '' }}</span>
            </div>
        </div>
    </div>

    @include('hub.partials.monitoring-filter')

    <div class="hub-card mb-3">
        <div class="hub-card-body small row g-2">
            @foreach($defs as $key => $text)
            <div class="col-md-6"><strong>{{ str_replace('_', ' ', ucfirst($key)) }}:</strong> {{ $text }}</div>
            @endforeach
        </div>
    </div>

    <div class="report-kpi-hero mb-3">
        <div class="report-kpi-card">
            <div class="label">ROAS Shopee (GMV AMS)</div>
            <div class="value">{{ isset($m['shopee_ads_roas']) ? number_format($m['shopee_ads_roas'], 2).'x' : '—' }}</div>
            <div class="sub">GMV {{ hub_rp($m['ads_gmv'] ?? 0) }}</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">ROAS bisnis</div>
            <div class="value">{{ isset($m['business_roas']) ? number_format($m['business_roas'], 2).'x' : '—' }}</div>
            <div class="sub">Kotor ÷ spend</div>
        </div>
        <div class="report-kpi-card positive">
            <div class="label">Target ROAS bisnis</div>
            <div class="value">{{ isset($m['target_roas_gross']) ? number_format($m['target_roas_gross'], 2).'x' : '—' }}</div>
            <div class="sub">Impas {{ isset($m['breakeven_roas_gross']) ? number_format($m['breakeven_roas_gross'], 2).'x' : '—' }}</div>
        </div>
        <div class="report-kpi-card highlight">
            <div class="label">Set ROAS Shopee (rekom.)</div>
            <div class="value">{{ isset($m['set_roas_shopee']) ? number_format($m['set_roas_shopee'], 2).'x' : '—' }}</div>
            <div class="sub">Input di dashboard iklan</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Spend AMS</div>
            <div class="value">{{ hub_rp($m['ads_spend'] ?? 0, true) }}</div>
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
        <div class="hub-card-header">
            <h2 class="report-section-title">Per produk — AMS & Set ROAS</h2>
            <p class="report-section-desc mb-0">Diurutkan spend tertinggi · klik nama untuk analisis lengkap</p>
        </div>
        <div class="hub-card-body p-0">
            <div class="table-responsive">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="num">Spend</th>
                            <th class="num">GMV AMS</th>
                            <th class="num">Shopee ROAS</th>
                            <th class="num">Business ROAS</th>
                            <th class="num">Set ROAS</th>
                            <th class="num">Gap</th>
                            <th class="num">Laba</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($roas['products'] ?? [] as $p)
                    <tr>
                        <td>
                            <a href="{{ route('monitoring.product-analysis.show', ['product' => $p['product_id']] + $q) }}">{{ $p['name'] }}</a>
                            @if($p['tier'] ?? null)<span class="sku-tier {{ $p['tier'] }} ms-1">{{ $p['tier'] }}</span>@endif
                        </td>
                        <td class="num">{{ hub_rp($p['spend'], true) }}</td>
                        <td class="num">{{ hub_rp($p['gmv_ams'], true) }}</td>
                        <td class="num">{{ $p['shopee_roas'] !== null ? number_format($p['shopee_roas'], 2).'x' : '—' }}</td>
                        <td class="num">{{ $p['business_roas'] !== null ? number_format($p['business_roas'], 2).'x' : '—' }}</td>
                        <td class="num fw-bold">{{ $p['set_roas_shopee'] !== null ? number_format($p['set_roas_shopee'], 2).'x' : '—' }}</td>
                        <td class="num {{ ($p['gap_business'] ?? 0) < 0 ? 'amt-neg' : 'amt-pos' }}">{{ $p['gap_business'] !== null ? number_format($p['gap_business'], 2) : '—' }}</td>
                        <td class="num {{ ($p['net_profit'] ?? 0) >= 0 ? '' : 'amt-neg' }}">{{ hub_rp($p['net_profit'], true) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-muted text-center py-4">Sync iklan AMS dulu atau pilih periode dengan spend iklan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
