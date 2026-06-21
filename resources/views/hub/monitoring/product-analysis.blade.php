@extends('layouts.hub')

@section('title', ($sku['name'] ?? $product->name) . ' — Analisis Produk')

@push('styles')
<style>
.pa-section { margin-bottom: 1.25rem; }
.pa-grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; }
.pa-variant-missing { opacity: .75; }
</style>
@endpush

@section('content')
@php
    $s = $sku;
    $r = $roas ?? [];
    $bcg = $bcg ?? null;
    $action = $s['action'] ?? [];
    $q = request()->query();
    $meta = $report['meta'] ?? [];
    $ceoActionSkip = true;
    $pageMeta = [
        ['icon' => 'fas fa-box', 'label' => 'SKU', 'value' => $s['name'] ?? $product->name],
    ];
    $pageActions = [
        ['label' => 'Ganti produk', 'url' => route('monitoring.product-analysis.index', $q), 'icon' => 'fa-arrow-left', 'variant' => 'outline'],
    ];
@endphp

@include('hub.partials.ceo.shell-open')

    @include('hub.partials.hub-zone-nav')
    @include('hub.partials.monitoring-filter')

    @if($s['missing_cost'] ?? false)
    <div class="hub-alert mb-3" style="background:#fef3c7;border-color:#fcd34d;color:#92400e;">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>HPP belum lengkap</strong> — angka laba dan simulasi harga belum akurat.
        <a href="{{ route('hpp.index') }}" class="ms-2">Lengkapi HPP →</a>
    </div>
    @endif

    {{-- Keputusan utama --}}
    <div class="mon-decision-card mon-action-{{ $action['severity'] ?? 'info' }} mb-3 pa-section">
        <h2 class="h5 mb-2"><i class="fas fa-lightbulb me-2"></i>{{ $action['title'] ?? 'Rekomendasi' }}</h2>
        <p class="mb-0">{{ $action['summary'] ?? '' }}</p>
        @if(!empty($action['reasons']))
        <ul class="mb-0 mt-2">@foreach($action['reasons'] as $reason)<li>{{ $reason }}</li>@endforeach</ul>
        @endif
    </div>

    {{-- KPI ringkas --}}
    <div class="mon-kpi-row mb-3 pa-section">
        <div class="mon-kpi"><div class="label">Laba bersih</div><div class="value {{ ($s['net_profit'] ?? 0) >= 0 ? '' : 'amt-neg' }}">{{ hub_rp($s['net_profit'] ?? 0, true) }}</div></div>
        <div class="mon-kpi"><div class="label">Penjualan kotor</div><div class="value">{{ hub_rp($s['gross'] ?? 0, true) }}</div><div class="sub">{{ (int)($s['qty'] ?? 0) }} pcs</div></div>
        <div class="mon-kpi"><div class="label">Margin bersih</div><div class="value">{{ hub_pct($s['margin'] ?? null) }}</div></div>
        <div class="mon-kpi"><div class="label">Spend iklan</div><div class="value">{{ hub_rp($s['ads_spend'] ?? 0) }}</div></div>
        <div class="mon-kpi"><div class="label">Shopee ROAS</div><div class="value">{{ isset($r['shopee_roas']) ? number_format($r['shopee_roas'], 2).'x' : '—' }}</div></div>
        <div class="mon-kpi"><div class="label">Set ROAS (rekom.)</div><div class="value">{{ isset($r['set_roas_shopee']) ? number_format($r['set_roas_shopee'], 2).'x' : '—' }}</div></div>
    </div>

    <div class="pa-grid-2 pa-section">
        {{-- Keuangan & harga --}}
        <div class="hub-card h-100">
            <div class="hub-card-header"><h2 class="report-section-title">Keuangan & Harga</h2></div>
            <div class="hub-card-body">
                <table class="report-table report-table-compact">
                    <tbody>
                        <tr><td>Penjualan kotor</td><td class="num">{{ hub_rp($s['gross'] ?? 0, true) }}</td></tr>
                        <tr><td>Net (alokasi)</td><td class="num">{{ hub_rp($s['net'] ?? 0, true) }}</td></tr>
                        <tr><td>HPP + packaging</td><td class="num">{{ hub_rp($s['cogs'] ?? 0, true) }}</td></tr>
                        <tr><td>Laba kotor</td><td class="num">{{ hub_rp($s['gross_profit'] ?? 0, true) }}</td></tr>
                        <tr><td>Iklan (alokasi)</td><td class="num">{{ hub_rp($s['ads_spend'] ?? 0, true) }}</td></tr>
                        <tr><td>Operasional (alokasi)</td><td class="num">{{ hub_rp($s['operational'] ?? 0, true) }}</td></tr>
                        <tr><td><strong>Laba bersih</strong></td><td class="num"><strong class="{{ ($s['net_profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg' }}">{{ hub_rp($s['net_profit'] ?? 0, true) }}</strong></td></tr>
                    </tbody>
                </table>
                <hr>
                <p class="small text-muted mb-2">Per unit (estimasi rata-rata periode)</p>
                <div class="mon-rec-metrics small">
                    <span>HPP+pack {{ hub_rp($costing['per_unit']['cogs'] ?? 0) }}</span>
                    <span>Iklan {{ hub_rp($costing['per_unit']['ads'] ?? 0) }}</span>
                    <span>Ops {{ hub_rp($costing['per_unit']['operational'] ?? 0) }}</span>
                    <span>Laba {{ hub_rp($costing['per_unit']['net_profit'] ?? 0) }}</span>
                </div>
                @include('hub.partials.product-recommendations', ['row' => $s])
            </div>
        </div>

        {{-- Iklan & ROAS --}}
        <div class="hub-card h-100">
            <div class="hub-card-header"><h2 class="report-section-title">Iklan & ROAS (AMS)</h2></div>
            <div class="hub-card-body">
                <table class="report-table report-table-compact">
                    <tbody>
                        <tr><td>Spend iklan</td><td class="num">{{ hub_rp($r['spend'] ?? 0, true) }}</td></tr>
                        <tr><td>GMV atribusi (AMS)</td><td class="num">{{ hub_rp($r['gmv_ams'] ?? 0, true) }}</td></tr>
                        <tr><td>Shopee ROAS (GMV÷spend)</td><td class="num">{{ isset($r['shopee_roas']) ? number_format($r['shopee_roas'], 2).'x' : '—' }}</td></tr>
                        <tr><td>Business ROAS (kotor÷spend)</td><td class="num">{{ isset($r['business_roas']) ? number_format($r['business_roas'], 2).'x' : '—' }}</td></tr>
                        <tr><td>Target ROAS bisnis</td><td class="num">{{ isset($r['target_business']) ? number_format($r['target_business'], 2).'x' : '—' }}</td></tr>
                        <tr><td>Impas ROAS bisnis</td><td class="num">{{ isset($r['breakeven_business']) ? number_format($r['breakeven_business'], 2).'x' : '—' }}</td></tr>
                        <tr><td class="fw-bold">Rekomendasi Set ROAS Shopee</td><td class="num fw-bold">{{ isset($r['set_roas_shopee']) ? number_format($r['set_roas_shopee'], 2).'x' : '—' }}</td></tr>
                        <tr><td>ACOS (spend÷kotor)</td><td class="num">{{ isset($r['acos']) ? hub_pct($r['acos']) : '—' }}</td></tr>
                        <tr><td>CPC</td><td class="num">{{ isset($r['cpc']) ? hub_rp($r['cpc']) : '—' }}</td></tr>
                        <tr><td>CTR</td><td class="num">{{ isset($r['ctr']) ? $r['ctr'].'%' : '—' }}</td></tr>
                        <tr><td>CPA</td><td class="num">{{ isset($r['cpa']) ? hub_rp($r['cpa']) : '—' }}</td></tr>
                    </tbody>
                </table>
                @if(isset($r['gap_business']) && $r['gap_business'] < 0)
                <p class="small text-danger mt-2 mb-0"><i class="fas fa-arrow-down"></i> ROAS bisnis di bawah target {{ number_format(abs($r['gap_business']), 2) }}x — pertimbangkan kurangi iklan atau naikkan harga.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- BCG & performa --}}
    <div class="pa-grid-2 pa-section">
        <div class="hub-card h-100">
            <div class="hub-card-header"><h2 class="report-section-title">BCG & Trafik</h2></div>
            <div class="hub-card-body">
                @if($bcg)
                <table class="report-table report-table-compact">
                    <tbody>
                        <tr><td>Quadrant</td><td><strong>{{ $bcg['quadrant_label'] }}</strong></td></tr>
                        <tr><td>Pengunjung</td><td class="num">{{ number_format($bcg['visitors']) }}</td></tr>
                        <tr><td>Halaman dilihat</td><td class="num">{{ number_format($bcg['page_views']) }}</td></tr>
                        <tr><td>Konversi</td><td class="num">{{ $bcg['conversion_rate_pct'] }}%</td></tr>
                        <tr><td>Units sold (SC)</td><td class="num">{{ number_format($bcg['units_sold']) }}</td></tr>
                        <tr><td>GMV performa (SC)</td><td class="num">{{ hub_rp($bcg['sales_gmv'], true) }}</td></tr>
                        <tr><td>Baseline trafik</td><td class="num">{{ number_format($bcg['traffic_baseline']) }}</td></tr>
                        <tr><td>Periode data</td><td class="small">{{ $bcg['period'] }}</td></tr>
                    </tbody>
                </table>
                @else
                <p class="text-muted mb-2">Belum ada data performa produk. Import Excel BCG atau sync otomatis.</p>
                <a href="{{ route('monitoring.bcg', $q) }}" class="hub-btn hub-btn-sm hub-btn-outline">Buka BCG & Trafik</a>
                @endif
            </div>
        </div>

        <div class="hub-card h-100">
            <div class="hub-card-header"><h2 class="report-section-title">HPP & Master Biaya</h2></div>
            <div class="hub-card-body">
                <table class="report-table report-table-compact">
                    <tbody>
                        <tr><td>HPP produk (induk)</td><td class="num">{{ $costing['product_hpp'] !== null ? hub_rp($costing['product_hpp']) : '—' }}</td></tr>
                        <tr><td>Packaging</td><td class="num">{{ $costing['packaging_type'] === 'percent' ? $costing['packaging_value'].'%' : hub_rp($costing['packaging_value'] ?? 0) }}</td></tr>
                        <tr><td>Harga katalog</td><td class="num">{{ hub_rp($costing['base_price'] ?? 0) }}</td></tr>
                        <tr><td>Jumlah varian</td><td class="num">{{ $product->variants->count() }}</td></tr>
                    </tbody>
                </table>
                <a href="{{ route('hpp.index') }}" class="hub-btn hub-btn-sm hub-btn-primary mt-2"><i class="fas fa-tags"></i> Kelola HPP & Varian</a>
            </div>
        </div>
    </div>

    {{-- Varian --}}
    <div class="hub-card pa-section">
        <div class="hub-card-header">
            <h2 class="report-section-title">Breakdown Varian</h2>
            <p class="report-section-desc mb-0">Penjualan, laba, dan HPP per varian — iklan dialokasikan proporsional omzet varian.</p>
        </div>
        <div class="hub-card-body p-0">
            <div class="table-responsive">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Varian</th>
                            <th>Model ID</th>
                            <th>HPP</th>
                            <th class="num">Qty</th>
                            <th class="num">Kotor</th>
                            <th class="num">HPP total</th>
                            <th class="num">Iklan*</th>
                            <th class="num">Laba bersih*</th>
                            <th class="num">Margin</th>
                            <th class="num">Harga avg</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($variants as $v)
                    <tr class="{{ !empty($v['no_sales']) ? 'pa-variant-missing' : '' }}">
                        <td>
                            <strong>{{ $v['name'] }}</strong>
                            @if(!empty($v['no_sales']))<span class="hub-pill hub-pill-warning ms-1">Belum ada penjualan</span>@endif
                            @if($v['hpp_missing'] ?? false)<span class="hub-pill hub-pill-warning ms-1">HPP?</span>@endif
                        </td>
                        <td>{{ $v['model_id'] ?? '—' }}</td>
                        <td class="num">{{ ($v['hpp'] ?? null) !== null ? hub_rp($v['hpp']) : '—' }}</td>
                        <td class="num">{{ $v['qty'] }}</td>
                        <td class="num">{{ hub_rp($v['gross'], true) }}</td>
                        <td class="num">{{ hub_rp($v['cogs'], true) }}</td>
                        <td class="num">{{ hub_rp($v['ads'], true) }}</td>
                        <td class="num {{ ($v['net_profit'] ?? 0) >= 0 ? '' : 'amt-neg' }}">{{ hub_rp($v['net_profit'], true) }}</td>
                        <td class="num">{{ hub_pct($v['margin'] ?? null) }}</td>
                        <td class="num">{{ isset($v['avg_price']) ? hub_rp($v['avg_price']) : '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center text-muted py-3">Tidak ada penjualan varian pada periode ini.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <p class="small text-muted px-3 py-2 mb-0">* Iklan & operasional varian = alokasi proporsi omzet varian terhadap total produk.</p>
        </div>
    </div>

    {{-- Tren bulanan --}}
    @if(!empty($monthly))
    <div class="fc-chart-stack pa-section">
        @include('hub.partials.chart-panel', ['id' => 'paTrend', 'title' => 'Tren penjualan produk', 'subtitle' => 'Area — omzet & iklan 6 bulan', 'size' => 'hero'])
        @include('hub.partials.chart-panel', ['id' => 'paRoasLine', 'title' => 'ROAS bisnis produk', 'subtitle' => 'Line — 1 bulan tampil sebagai gauge', 'size' => 'compact'])
    </div>
    @endif

    {{-- Simulasi --}}
    @if(!empty($simulations))
    <div class="hub-card pa-section">
        <div class="hub-card-header"><h2 class="report-section-title">Simulasi Cepat</h2></div>
        <div class="hub-card-body">
            <div class="row g-3">
                @foreach($simulations as $key => $sim)
                <div class="col-md-4">
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
    @endif

    {{-- Log keputusan --}}
    <div class="hub-card pa-section">
        <div class="hub-card-header"><h2 class="report-section-title">Catat Keputusan</h2></div>
        <div class="hub-card-body">
            <form method="POST" action="{{ route('ceo.decisions.store') }}">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="decision_type" value="{{ $action['code'] ?? 'product_analysis' }}">
                <input type="hidden" name="title" value="{{ $action['title'] ?? 'Keputusan produk' }}">
                <textarea name="note" class="hub-form-control mb-2" rows="2" placeholder="Contoh: set ROAS 5.5x, potong iklan varian merah 30%"></textarea>
                <button type="submit" class="hub-btn hub-btn-primary btn-sm"><i class="fas fa-save"></i> Simpan ke log CEO</button>
            </form>
        </div>
    </div>
@include('hub.partials.ceo.shell-close')
@endsection

@if(!empty($monthly))
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const monthly = @json($monthly);
    HubCharts.renderPreset('paTrend', 'product_trend', {
        labels: monthly.map(m => m.label),
        columns: monthly.map(m => m.gross),
        lines: monthly.map(m => m.ads),
        colLabel: 'Penjualan kotor',
        lineLabel: 'Spend iklan',
    });
    HubCharts.render('paRoasLine', 'line', {
        labels: monthly.map(m => m.label),
        datasets: [{ label: 'ROAS', data: monthly.map(m => m.roas || 0) }],
        format: 'roas',
    });
});
</script>
@endpush
@endif
