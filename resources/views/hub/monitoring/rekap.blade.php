@extends('layouts.hub')

@section('title', 'Rekap CEO — Monitoring')

@section('content')
@php
    $s = $summary ?? [];
    $rek = $rekap ?? [];
    $months = $rek['months'] ?? [];
    $columns = $rek['columns'] ?? [];
    $metrics = $rek['metrics'] ?? [];
    $best = $rek['best_sellers'] ?? [];
@endphp
<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-table me-2"></i>Rekap CEO</h1>
        <div class="report-hero-meta">
            <span>Grid metrik {{ count($months) }} bulan — setara Excel HASIL/REKAP</span>
            <span>{{ $shop['label'] ?? '' }}</span>
        </div>
    </div>

    @include('hub.partials.hub-zone-nav')

    <div class="mon-kpi-row">
        <div class="mon-kpi"><div class="label">AOV kotor (periode)</div><div class="value">{{ hub_rp($s['aov_gross'] ?? 0) }}</div></div>
        <div class="mon-kpi"><div class="label">Basket size</div><div class="value">{{ $s['basket_size'] ?? '—' }} item/order</div></div>
        <div class="mon-kpi"><div class="label">Gross margin</div><div class="value">{{ hub_pct($s['gross_margin_pct'] ?? null) }}</div></div>
        <div class="mon-kpi"><div class="label">Net margin</div><div class="value">{{ hub_pct($s['net_margin_pct'] ?? null) }}</div></div>
    </div>

    <div class="hub-card mb-3">
        <div class="hub-card-header">
            <h2 class="report-section-title">Rekap multi-bulan</h2>
            <p class="report-section-desc mb-0">Semua rasio sejajar per bulan — scroll horizontal di mobile</p>
        </div>
        <div class="hub-card-body p-0">
            <div class="rekap-grid-wrap">
                <table class="rekap-grid">
                    <thead>
                        <tr>
                            <th class="rekap-sticky">Metrik</th>
                            @foreach($months as $mk)
                            <th>{{ $columns[$mk]['short'] ?? $mk }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($metrics as $m)
                        <tr>
                            <td class="rekap-sticky"><strong>{{ $m['label'] }}</strong></td>
                            @foreach($months as $mk)
                            @php $val = $columns[$mk][$m['key']] ?? null; @endphp
                            <td class="num">
                                @if($val === null) —
                                @elseif($m['format'] === 'rp') {{ hub_rp($val) }}
                                @elseif($m['format'] === 'pct') {{ hub_pct($val) }}
                                @elseif($m['format'] === 'x') {{ is_numeric($val) ? number_format($val, 2).'x' : '—' }}
                                @else {{ is_numeric($val) ? number_format($val, 2) : $val }}
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="hub-card">
        <div class="hub-card-header">
            <h2 class="report-section-title">Best seller per bulan</h2>
            <p class="report-section-desc mb-0">Top 8 SKU by qty — 3 bulan terakhir</p>
        </div>
        <div class="hub-card-body">
            <div class="best-seller-mom">
                @forelse($best as $mk => $period)
                <div class="best-seller-col">
                    <h3 class="h6">{{ $period['label'] ?? $mk }}</h3>
                    <ol class="best-seller-list">
                        @foreach($period['products'] ?? [] as $i => $p)
                        <li>
                            <span class="rank">{{ $i + 1 }}</span>
                            <span class="name">{{ \Illuminate\Support\Str::limit($p['name'] ?? '—', 28) }}</span>
                            <strong>{{ $p['qty'] ?? 0 }}</strong>
                        </li>
                        @endforeach
                    </ol>
                </div>
                @empty
                <p class="text-muted mb-0">Belum ada data best seller.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
