@extends('layouts.hub')

@section('title', 'Pusat Aksi')

@section('content')
@php
    $ac = $action_center ?? [];
    $hpp = $ac['hpp_quality'] ?? [];
    $cash = $ac['cash_guard'] ?? [];
    $shop = $shop ?? [];
    $heroExtra = '<span class="small text-muted"><i class="fas fa-store"></i> '.e($shop['label'] ?? $activeShopeeShopLabel ?? 'Toko').' · HPP '.e($hpp['complete_pct_label'] ?? '—').'</span>';
@endphp

@include('hub.partials.ceo.shell-open')

    @include('hub.partials.hub-zone-nav')
    @include('hub.partials.monitoring-filter')

    @if(!($hpp['recommendations_allowed'] ?? true))
    <div class="hub-alert mb-3" style="background:#fef3c7;border-color:#fcd34d;color:#92400e;" data-ceo="alerts">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>HPP belum lengkap.</strong> Isi minimal 70% produk dulu — kalau tidak, saran iklan bisa salah.
        <a href="{{ route('hpp.index', ['fill' => 'missing']) }}" class="ms-2">Isi HPP →</a>
    </div>
    @endif

  <div class="mon-kpi-row" data-ceo="main-kpi">
        <div class="mon-kpi"><div class="label">Harus hari ini</div><div class="value">{{ $ac['counts']['urgent'] ?? 0 }}</div></div>
        <div class="mon-kpi"><div class="label">Boleh tambah iklan</div><div class="value">{{ $ac['counts']['opportunities'] ?? 0 }}</div></div>
        <div class="mon-kpi"><div class="label">Produk rugi</div><div class="value text-danger">{{ $ac['counts']['bleeders'] ?? 0 }}</div></div>
        <div class="mon-kpi"><div class="label">Iklan aman / minggu</div><div class="value">{{ hub_rp($cash['safe_weekly_ads_suggest'] ?? 0) }}</div></div>
    </div>

    @if(!empty($ac['data_blockers']))
    <div class="hub-card mb-3">
        <div class="hub-card-header"><h2 class="report-section-title">Data belum siap</h2></div>
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
            <h2 class="report-section-title">Jaga uang toko</h2>
            <p class="report-section-desc mb-0">{{ $cash['message'] ?? 'Pastikan iklan tidak melebihi kemampuan cash toko.' }}</p>
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
                <div class="hub-card-header"><h2 class="report-section-title">Kerjakan dulu</h2></div>
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
                <div class="hub-card-header"><h2 class="report-section-title">Boleh scale iklan</h2></div>
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
@include('hub.partials.ceo.shell-close')
@endsection
