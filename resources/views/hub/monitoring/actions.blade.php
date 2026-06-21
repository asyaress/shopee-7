@extends('layouts.hub')

@section('title', 'Pusat Aksi — Monitoring')

@push('styles')
<link href="{{ asset('css/hub-monitoring.css') }}?v=2" rel="stylesheet">
@endpush

@section('content')
@php
    $ac = $action_center ?? [];
    $hpp = $ac['hpp_quality'] ?? [];
    $cash = $ac['cash_guard'] ?? [];
    $shop = $shop ?? [];
@endphp

<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-bolt me-2"></i>Pusat Aksi</h1>
        <div class="report-hero-meta">
            <span><i class="fas fa-store"></i> {{ $shop['label'] ?? $activeShopeeShopLabel ?? 'Toko' }}</span>
            <span><i class="far fa-calendar-alt"></i> {{ $meta['period_label'] ?? '—' }}</span>
            <span>Kelengkapan HPP: <strong>{{ $hpp['complete_pct_label'] ?? '—' }}</strong></span>
        </div>
    </div>

    @include('hub.partials.hub-zone-nav')
    @include('hub.partials.monitoring-filter')

    @if(!($hpp['recommendations_allowed'] ?? true))
    <div class="hub-alert" style="background:#fef3c7;border-color:#fcd34d;color:#92400e;">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Data HPP belum cukup.</strong> Lengkapi minimal 70% SKU sebelum mengikuti rekomendasi iklan/harga.
        <a href="{{ route('hpp.index', ['fill' => 'missing']) }}" class="ms-2">Perbaiki HPP →</a>
    </div>
    @endif

  <div class="mon-kpi-row">
        <div class="mon-kpi"><div class="label">Prioritas urgent</div><div class="value">{{ $ac['counts']['urgent'] ?? 0 }}</div></div>
        <div class="mon-kpi"><div class="label">Peluang scale</div><div class="value">{{ $ac['counts']['opportunities'] ?? 0 }}</div></div>
        <div class="mon-kpi"><div class="label">SKU bleeder</div><div class="value text-danger">{{ $ac['counts']['bleeders'] ?? 0 }}</div></div>
        <div class="mon-kpi"><div class="label">Pace iklan aman/minggu</div><div class="value">{{ hub_rp($cash['safe_weekly_ads_suggest'] ?? 0) }}</div></div>
    </div>

    @if(!empty($ac['data_blockers']))
    <div class="hub-card mb-3">
        <div class="hub-card-header"><h2 class="report-section-title">Blocker data</h2></div>
        <div class="hub-card-body">
            @foreach($ac['data_blockers'] as $b)
            <div class="report-insight {{ $b['type'] }} mb-2">
                <div><strong>{{ $b['title'] }}</strong><p class="mb-0 small">{{ $b['text'] }}</p></div>
                @if(!empty($b['route']))
                <a href="{{ route($b['route']) }}" class="hub-btn hub-btn-sm hub-btn-outline">Perbaiki</a>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="hub-card mb-3">
        <div class="hub-card-header">
            <h2 class="report-section-title">Cash guard</h2>
            <p class="report-section-desc mb-0">{{ $cash['message'] ?? '' }}</p>
        </div>
        <div class="hub-card-body small">
            Net masuk periode: <strong>{{ hub_rp($cash['net_income_period'] ?? 0) }}</strong> ·
            Spend iklan ({{ $cash['period_weeks'] ?? 4 }} minggu): <strong>{{ hub_rp($cash['ads_spend_period'] ?? 0) }}</strong> ·
            Laba bersih: <strong class="{{ ($cash['net_profit_period'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg' }}">{{ hub_rp($cash['net_profit_period'] ?? 0, true) }}</strong>
            @if(($cash['budget_monthly'] ?? 0) > 0)
            · Budget iklan: {{ hub_pct($cash['budget_used_pct'] ?? null) }} terpakai
            @endif
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="hub-card h-100">
                <div class="hub-card-header"><h2 class="report-section-title">Tindakan urgent</h2></div>
                <div class="hub-card-body">
                    @forelse($ac['urgent'] ?? [] as $item)
                        @include('hub.partials.action-item', ['item' => $item])
                    @empty
                        <p class="text-muted mb-0">Tidak ada item urgent — bagus!</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="hub-card h-100">
                <div class="hub-card-header"><h2 class="report-section-title">Peluang (scale iklan)</h2></div>
                <div class="hub-card-body">
                    @forelse($ac['opportunities'] ?? [] as $item)
                        @include('hub.partials.action-item', ['item' => $item])
                    @empty
                        <p class="text-muted mb-0">Belum ada peluang terdeteksi pada periode ini.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
