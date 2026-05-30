@extends('layouts.hub')

@section('title', ($sku['name'] ?? 'Produk') . ' — Keputusan SKU')

@push('styles')
<link href="{{ asset('css/hub-monitoring.css') }}?v=2" rel="stylesheet">
@endpush

@section('content')
@php
    $action = $sku['action'] ?? [];
    $q = request()->query();
@endphp
<div class="report-shell">
    <div class="report-hero">
        <div class="d-flex justify-content-between flex-wrap gap-2">
            <div>
                <h1 class="h4 mb-1">{{ $sku['name'] ?? $product->name }}</h1>
                <div class="report-hero-meta">
                    <span class="sku-tier {{ $sku['tier'] ?? '' }}">{{ strtoupper($sku['tier'] ?? '—') }}</span>
                    <span>SKU {{ $sku['sku'] ?? '—' }}</span>
                    <span>{{ $shop['label'] ?? '' }}</span>
                    @include('hub.partials.product-shopee-links', ['links' => $sku['links'] ?? []])
                </div>
            </div>
            <a href="{{ route('monitoring.profit', $q) }}" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    @include('hub.partials.monitoring-filter')

    <div class="mon-decision-card mon-action-{{ $action['severity'] ?? 'info' }} mb-3">
        <h2 class="h5 mb-2"><i class="fas fa-lightbulb me-2"></i>{{ $action['title'] ?? 'Rekomendasi utama' }}</h2>
        <p>{{ $action['summary'] ?? '' }}</p>
        @if(!empty($action['reasons']))
        <ul class="mb-0">
            @foreach($action['reasons'] as $r)<li>{{ $r }}</li>@endforeach
        </ul>
        @endif
        @if(!empty($action['meta']['recommended_price']))
        <p class="mt-2 mb-0"><strong>Harga jual disarankan:</strong> {{ hub_rp($action['meta']['recommended_price']) }} / unit (kotor)</p>
        @endif
        @if(!empty($action['route']))
        <a href="{{ route($action['route']) }}" class="hub-btn hub-btn-primary mt-3">Buka Input HPP</a>
        @endif
    </div>

    @include('hub.partials.product-recommendations', ['row' => $sku])

    <div class="mon-kpi-row mb-3">
        <div class="mon-kpi"><div class="label">Laba bersih</div><div class="value {{ ($sku['net_profit'] ?? 0) >= 0 ? '' : 'amt-neg' }}">{{ hub_rp($sku['net_profit'] ?? 0, true) }}</div></div>
        <div class="mon-kpi"><div class="label">Margin</div><div class="value">{{ hub_pct($sku['margin'] ?? null) }}</div></div>
        <div class="mon-kpi"><div class="label">Spend iklan</div><div class="value">{{ hub_rp($sku['ads_spend'] ?? 0) }}</div></div>
        <div class="mon-kpi"><div class="label">ROAS</div><div class="value">{{ isset($sku['roas']) && $sku['roas'] ? number_format($sku['roas'], 2).'x' : '—' }}</div></div>
    </div>

    <div class="hub-card mb-3">
        <div class="hub-card-header"><h2 class="report-section-title">Catat keputusan</h2></div>
        <div class="hub-card-body">
            <form method="POST" action="{{ route('ceo.decisions.store') }}">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="decision_type" value="{{ $action['code'] ?? 'other' }}">
                <input type="hidden" name="title" value="{{ $action['title'] ?? 'Keputusan SKU' }}">
                <textarea name="note" class="hub-form-control mb-2" rows="2" placeholder="Apa yang Anda lakukan di Shopee? (contoh: potong iklan 50%)"></textarea>
                <button type="submit" class="hub-btn hub-btn-primary btn-sm">Simpan ke log CEO</button>
            </form>
        </div>
    </div>

    <div class="hub-card">
        <div class="hub-card-header"><h2 class="report-section-title">Simulasi cepat</h2></div>
        <div class="hub-card-body">
            <p class="small text-muted">Estimasi sederhana — asumsi volume penjualan tetap.</p>
            <div class="row g-3">
                @foreach($simulations ?? [] as $key => $sim)
                <div class="col-md-6">
                    <div class="mon-sim-box">
                        <strong>{{ $sim['label'] ?? $key }}</strong>
                        <div>Laba bersih → <span class="{{ ($sim['net_profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg' }}">{{ hub_rp($sim['net_profit'] ?? 0, true) }}</span></div>
                        <div class="small">Margin {{ hub_pct($sim['margin'] ?? null) }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
