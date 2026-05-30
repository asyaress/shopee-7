@extends('layouts.hub')

@section('title', 'Monitoring — Ringkasan')

@push('styles')
<link href="{{ asset('css/hub-monitoring.css') }}?v=1" rel="stylesheet">
@endpush

@section('content')
@php
    $s = $summary ?? [];
    $meta = $meta ?? [];
    $charts = $charts ?? [];
    $health = ($analysis ?? [])['health_score'] ?? 0;
@endphp

<div class="report-shell">
    <div class="report-hero">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h1><i class="fas fa-gauge-high me-2"></i>Monitoring Toko</h1>
                <div class="report-hero-meta">
                    <span><i class="far fa-calendar-alt"></i> {{ $meta['period_label'] ?? '—' }}</span>
                    <span><i class="far fa-clock"></i> {{ $meta['days'] ?? 0 }} hari</span>
                    <span><i class="fas fa-sync-alt"></i> {{ $meta['generated_at'] ?? now()->format('d M Y H:i') }}</span>
                </div>
            </div>
            <div class="report-health">
                <div class="report-health-ring" style="--score: {{ $health }}"><span>{{ $health }}</span></div>
                <div><strong>Skor kesehatan</strong><div class="small opacity-90">Data, margin & iklan</div></div>
            </div>
        </div>
    </div>

    @include('hub.partials.monitoring-nav')
    @include('hub.partials.monitoring-filter')

    <div class="mb-3">
        <a href="{{ route('monitoring.actions', request()->query()) }}" class="hub-btn hub-btn-primary">
            <i class="fas fa-bolt"></i> Buka Pusat Aksi — rekomendasi hari ini
        </a>
    </div>

    @if(count($shop_compare ?? []) > 1)
    <div class="hub-card mb-3">
        <div class="hub-card-header"><h2 class="report-section-title">Perbandingan toko</h2></div>
        <div class="hub-card-body p-0">
            <table class="report-table">
                <thead><tr><th>Toko</th><th class="num">Laba bersih</th><th class="num">Bleeder</th></tr></thead>
                <tbody>
                @foreach($shop_compare as $row)
                <tr>
                    <td>{{ $row['shop_label'] }}</td>
                    <td class="num {{ ($row['net_profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg' }}">{{ hub_rp($row['net_profit'] ?? 0, true) }}</td>
                    <td class="num">{{ $row['bleeders'] ?? 0 }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="mon-section-cards">
        <a href="{{ route('monitoring.shopee', request()->query()) }}" class="mon-section-card shopee">
            <div class="icon-wrap"><i class="fas fa-percent"></i></div>
            <h3>Potongan Shopee</h3>
            <div class="kpi">{{ hub_rp($s['fee_total'] ?? 0) }}</div>
            <div class="sub">Take rate {{ hub_pct($s['take_rate'] ?? null) }} · Fee platform</div>
        </a>
        <a href="{{ route('monitoring.revenue', request()->query()) }}" class="mon-section-card revenue">
            <div class="icon-wrap"><i class="fas fa-coins"></i></div>
            <h3>Pendapatan</h3>
            <div class="kpi">{{ hub_rp($s['gross'] ?? 0) }}</div>
            <div class="sub">Net {{ hub_rp($s['net'] ?? 0) }} · {{ hub_num($s['orders_count'] ?? 0) }} pesanan</div>
        </a>
        <a href="{{ route('monitoring.ads', request()->query()) }}" class="mon-section-card ads">
            <div class="icon-wrap"><i class="fas fa-bullhorn"></i></div>
            <h3>Iklan Shopee</h3>
            <div class="kpi">{{ hub_rp($s['ads_total'] ?? 0) }}</div>
            <div class="sub">ROAS {{ isset($s['roas']) && $s['roas'] ? number_format($s['roas'], 2).'x' : '—' }} · ACOS {{ hub_pct($s['acos'] ?? null) }}</div>
        </a>
        <a href="{{ route('monitoring.profit', request()->query()) }}" class="mon-section-card profit">
            <div class="icon-wrap"><i class="fas fa-chart-pie"></i></div>
            <h3>Laba & Produk</h3>
            <div class="kpi {{ ($s['net_profit'] ?? 0) >= 0 ? '' : 'amt-neg' }}">{{ hub_rp($s['net_profit'] ?? 0, true) }}</div>
            <div class="sub">Laporan lengkap P&amp;L & tabel produk</div>
        </a>
    </div>

    <div class="mon-kpi-row">
        <div class="mon-kpi"><div class="label">Laba kotor</div><div class="value">{{ hub_rp($s['gross_profit'] ?? 0) }}</div></div>
        <div class="mon-kpi"><div class="label">HPP + Pack</div><div class="value">{{ hub_rp($s['cogs'] ?? 0) }}</div></div>
        <div class="mon-kpi"><div class="label">Operasional</div><div class="value">{{ hub_rp($s['operational_total'] ?? 0) }}</div></div>
        <div class="mon-kpi"><div class="label">Margin bersih</div><div class="value">{{ hub_pct($s['margin'] ?? null) }}</div></div>
    </div>

    <div class="fc-chart-stack">
        @include('hub.partials.chart-panel', ['id' => 'ovGrossNet', 'title' => 'Kotor vs net (bulanan)', 'subtitle' => 'Perbandingan penghasilan sebelum & sesudah fee platform', 'size' => 'hero', 'badge' => 'Shopee'])
        @include('hub.partials.chart-panel', ['id' => 'ovFeePie', 'title' => 'Komposisi fee', 'subtitle' => 'Administrasi, layanan, proses, program hemat', 'size' => 'square'])
        @include('hub.partials.chart-panel', ['id' => 'ovRevenue', 'title' => 'Tren pendapatan', 'subtitle' => 'Penjualan kotor & net per bulan', 'size' => 'hero'])
        @include('hub.partials.chart-panel', ['id' => 'ovAds', 'title' => 'Spend iklan', 'subtitle' => 'Agregat biaya promosi per bulan', 'size' => 'default', 'badge' => 'Ads'])
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sh = @json($charts['shopee'] ?? []);
    const rev = @json($charts['revenue'] ?? []);
    const ads = @json($charts['ads'] ?? []);

    HubCharts.render('ovGrossNet', 'line', sh.gross_vs_net || {});
    HubCharts.render('ovFeePie', 'doughnut', sh.fee_doughnut || {});
    HubCharts.render('ovRevenue', 'line', rev.revenue_trend || {});
    HubCharts.render('ovAds', 'bar', { labels: (ads.ads_monthly || {}).labels, data: (ads.ads_monthly || {}).data, label: 'Iklan (Rp)' });
});
</script>
@endpush
