@extends('layouts.hub')
@section('title', 'Target Bulanan — CEO')
@push('styles')<link href="{{ asset('css/hub-monitoring.css') }}?v=2" rel="stylesheet">@endpush
@section('content')
@php $p = $progress ?? []; $pace = $pace ?? []; @endphp
<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-bullseye me-2"></i>Target vs Actual</h1>
        <div class="report-hero-meta"><span>{{ $shop['label'] ?? '' }}</span><span>Bulan {{ $year_month }}</span></div>
    </div>
    @include('hub.partials.hub-zone-nav')
    <div class="hub-card mb-3">
        <div class="hub-card-body">
            <form method="POST" action="{{ route('ceo.targets.save') }}" class="hub-filter-bar">
                @csrf
                <input type="hidden" name="year_month" value="{{ $year_month }}">
                <div class="filter-item"><label class="hub-form-label">Target laba bersih</label>
                    <input type="number" name="target_net_profit" class="hub-form-control" value="{{ $targets['net_profit'] ?? '' }}"></div>
                <div class="filter-item"><label class="hub-form-label">Target penjualan kotor</label>
                    <input type="number" name="target_gross" class="hub-form-control" value="{{ $targets['gross'] ?? '' }}"></div>
                <div class="filter-item"><label class="hub-form-label">Target unit terjual</label>
                    <input type="number" name="target_units" class="hub-form-control" value="{{ $targets['units'] ?? '' }}"></div>
                <div class="filter-item"><label class="hub-form-label">Budget iklan bulanan</label>
                    <input type="number" name="ad_budget_cap" class="hub-form-control" value="{{ $targets['ad_budget'] ?? '' }}"></div>
                <div class="filter-item" style="align-self:flex-end"><button type="submit" class="hub-btn hub-btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
    <div class="report-kpi-hero">
        <div class="report-kpi-card {{ ($pace['on_track_net'] ?? false) ? 'positive' : 'warn' }}">
            <div class="label">Laba bersih</div>
            <div class="value">{{ hub_rp($actual['net_profit'] ?? 0, true) }}</div>
            <div class="sub">Target {{ hub_rp($targets['net_profit'] ?? 0) }} · {{ isset($p['net_pct']) ? number_format($p['net_pct']*100,1).'%' : '—' }}</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Pace (hari {{ $pace['day'] ?? 0 }}/{{ $pace['days_in_month'] ?? 30 }})</div>
            <div class="value">{{ hub_rp($pace['expected_net_by_today'] ?? 0) }}</div>
            <div class="sub">Seharusnya sudah mencapai</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Unit terjual / target</div>
            <div class="value">{{ number_format($actual['units'] ?? 0) }}</div>
            <div class="sub">{{ isset($p['units_pct']) && ($targets['units'] ?? 0) > 0 ? number_format($p['units_pct']*100,1).'% target' : 'Set di form atau BCG' }}</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Iklan / budget</div>
            <div class="value">{{ hub_rp($actual['ads_total'] ?? 0) }}</div>
            <div class="sub">{{ isset($p['ads_pct']) && ($targets['ad_budget'] ?? 0) > 0 ? number_format($p['ads_pct']*100,1).'% budget' : '—' }}</div>
        </div>
    </div>
</div>
@endsection
