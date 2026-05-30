@extends('layouts.hub')

@section('title', 'CEO Brief — Monitoring')

@push('styles')
<link href="{{ asset('css/hub-monitoring.css') }}?v=2" rel="stylesheet">
@endpush

@section('content')
@php
    $s = $summary ?? [];
    $ac = $action_center ?? [];
    $compare = $shop_compare ?? [];
@endphp

<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-briefcase me-2"></i>CEO Brief</h1>
        <div class="report-hero-meta">
            <span>{{ $meta['period_label'] ?? '—' }}</span>
            <span>Toko aktif: <strong>{{ $shop['label'] ?? '—' }}</strong></span>
        </div>
    </div>

    @include('hub.partials.monitoring-nav')
    @include('hub.partials.monitoring-filter')

    <div class="report-kpi-hero mb-3">
        <div class="report-kpi-card {{ ($s['net_profit'] ?? 0) >= 0 ? 'positive' : 'negative' }}">
            <div class="label">Laba bersih (toko aktif)</div>
            <div class="value">{{ hub_rp($s['net_profit'] ?? 0, true) }}</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Margin</div>
            <div class="value">{{ hub_pct($s['margin'] ?? null) }}</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Iklan / ROAS</div>
            <div class="value">{{ hub_rp($s['ads_total'] ?? 0) }}</div>
            <div class="sub">{{ isset($s['roas']) && $s['roas'] ? number_format($s['roas'], 2).'x' : '—' }}</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Urgent / Bleeder</div>
            <div class="value">{{ $ac['counts']['urgent'] ?? 0 }} / {{ $ac['counts']['bleeders'] ?? 0 }}</div>
        </div>
    </div>

    @if(count($compare) > 1)
    <div class="hub-card mb-3">
        <div class="hub-card-header"><h2 class="report-section-title">Bandingkan toko</h2></div>
        <div class="hub-card-body p-0">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Toko</th>
                        <th class="num">Laba bersih</th>
                        <th class="num">Margin</th>
                        <th class="num">Iklan</th>
                        <th class="num">Bleeder</th>
                        <th class="num">Star</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($compare as $row)
                    <tr>
                        <td><strong>{{ $row['shop_label'] }}</strong></td>
                        <td class="num {{ ($row['net_profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg' }}">{{ hub_rp($row['net_profit'] ?? 0, true) }}</td>
                        <td class="num">{{ hub_pct($row['margin'] ?? null) }}</td>
                        <td class="num">{{ hub_rp($row['ads_total'] ?? 0) }}</td>
                        <td class="num">{{ $row['bleeders'] ?? 0 }}</td>
                        <td class="num">{{ $row['stars'] ?? 0 }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('monitoring.actions', request()->query()) }}" class="hub-btn hub-btn-primary">
            <i class="fas fa-bolt"></i> Pusat Aksi
        </a>
        <a href="{{ route('ceo.targets') }}" class="hub-btn hub-btn-outline">Target</a>
        <a href="{{ route('ceo.roas', request()->query()) }}" class="hub-btn hub-btn-outline">ROAS Advisor</a>
        <a href="{{ route('ceo.settlement') }}" class="hub-btn hub-btn-outline">Arus kas</a>
        <a href="{{ route('ceo.export.journal', request()->query()) }}" class="hub-btn hub-btn-outline">Export jurnal</a>
    </div>
    <form method="POST" action="{{ route('ceo.alerts.run') }}" class="d-inline">
        @csrf
        <button type="submit" class="hub-btn hub-btn-sm hub-btn-outline">Jalankan cek alert</button>
    </form>
</div>
@endsection
