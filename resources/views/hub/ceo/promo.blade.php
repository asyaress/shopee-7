@extends('layouts.hub')
@section('title', 'Analisis Promo — CEO')
@push('styles')<link href="{{ asset('css/hub-monitoring.css') }}?v=2" rel="stylesheet">@endpush
@section('content')
<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-tags me-2"></i>Dampak Promo & Fee</h1>
        <p class="small mb-0">{{ $promo['insight'] ?? '' }}</p>
    </div>
    @include('hub.partials.ceo-nav')
    @include('hub.partials.monitoring-filter')
    <div class="hub-card">
        <div class="hub-card-body p-0">
            <table class="report-table">
                <thead>
                    <tr><th>Bulan</th><th class="num">Kotor</th><th class="num">Take rate</th><th class="num">Laba bersih</th></tr>
                </thead>
                <tbody>
                @foreach($promo['monthly'] ?? [] as $m)
                <tr>
                    <td>{{ $m['label'] }}</td>
                    <td class="num">{{ hub_rp($m['gross']) }}</td>
                    <td class="num">{{ hub_pct($m['take_rate']) }}</td>
                    <td class="num {{ ($m['net_profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg' }}">{{ hub_rp($m['net_profit'], true) }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <p class="small text-muted mt-2">Program Hemat periode ini: {{ hub_rp($promo['program_hemat'] ?? 0) }} ({{ hub_pct($promo['program_hemat_pct'] ?? null) }} dari fee)</p>
</div>
@endsection
