@extends('layouts.hub')
@section('title', 'Analisa Iklan')
@section('content')
@php
    $sc = $roas['scorecard'] ?? [];
    $ceoAction = $roas['ceo_action'] ?? [];
    $counts = $roas['counts'] ?? [];
    $q = request()->query();
    $setRoas = $sc['set_roas_shopee'] ?? null;
    $ceoActionSkip = true;
    $pageActions = hub_export_page_actions('roas', $q);
@endphp
@include('hub.partials.ceo.shell-open')

    @include('hub.partials.monitoring-filter')

    <div class="roas-hero-card mb-3" data-ceo="main-kpi">
        <div class="roas-hero-label">Set ROAS di Shopee Ads</div>
        <div class="roas-hero-value">{{ $setRoas ? number_format($setRoas, 1).'x' : '—' }}</div>
        <div class="roas-hero-sub">Ketik angka ini di dashboard iklan Shopee</div>
        @if($setRoas && ($sc['shopee_roas_now'] ?? null))
        <div class="roas-hero-compare">
            Sekarang Shopee: <strong>{{ number_format($sc['shopee_roas_now'], 1) }}x</strong>
            · Bisnis: <strong>{{ isset($sc['business_roas_now']) ? number_format($sc['business_roas_now'], 1).'x' : '—' }}</strong>
        </div>
        @endif
    </div>

    @include('hub.partials.ceo.action', ['action' => $ceoAction])

    <div class="roas-mini-grid mb-3">
        <div class="roas-mini">
            <span class="roas-mini-label">Uang keluar iklan</span>
            <span class="roas-mini-val">{{ hub_rp($sc['ads_spend'] ?? 0, true) }}</span>
        </div>
        <div class="roas-mini">
            <span class="roas-mini-label">Omzet dari iklan</span>
            <span class="roas-mini-val">{{ hub_rp($sc['ads_gmv'] ?? 0, true) }}</span>
        </div>
        <div class="roas-mini">
            <span class="roas-mini-label">Sisa setelah HPP</span>
            <span class="roas-mini-val">{{ isset($sc['margin_profit_pct']) ? $sc['margin_profit_pct'].'%' : '—' }}</span>
        </div>
        <div class="roas-mini">
            <span class="roas-mini-label">Laba bersih</span>
            <span class="roas-mini-val {{ ($sc['net_profit'] ?? 0) >= 0 ? '' : 'amt-neg' }}">{{ hub_rp($sc['net_profit'] ?? 0, true) }}</span>
        </div>
    </div>

    @if(!empty($charts))
    <div class="fc-chart-stack mb-3">
        @include('hub.partials.chart-panel', ['id' => 'roasDaily', 'title' => 'Iklan harian', 'subtitle' => 'Area — spend & omzet iklan per hari', 'size' => 'hero'])
        <div class="fc-chart-duo">
            @include('hub.partials.chart-panel', ['id' => 'roasMonth', 'title' => 'Iklan per bulan', 'subtitle' => 'Column — 1 bulan = gauge radial', 'size' => 'default'])
            @include('hub.partials.chart-panel', ['id' => 'roasTop', 'title' => 'Top produk iklan', 'subtitle' => 'Bar horizontal — spend tertinggi', 'size' => 'default'])
        </div>
    </div>
    @endif

    <div id="roas-products" class="mb-3" data-ceo="products">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <div>
                <h2 class="h6 mb-0 fw-semibold">Per produk — lakukan ini</h2>
                <p class="roas-products-sub mb-0">{{ $counts['total'] ?? 0 }} produk · {{ $counts['with_ads'] ?? 0 }} dengan iklan</p>
            </div>
            <div class="roas-count-pills">
                @if(($counts['scale'] ?? 0) > 0)<span class="roas-pill scale">{{ $counts['scale'] }} boleh tambah</span>@endif
                @if(($counts['cut'] ?? 0) > 0)<span class="roas-pill cut">{{ $counts['cut'] }} kurangi/stop</span>@endif
            </div>
        </div>

        @forelse($productsPaginator ?? [] as $p)
        @php $a = $p['action'] ?? []; @endphp
        <div class="roas-product-card roas-action-{{ $a['severity'] ?? 'info' }}">
            <div class="roas-product-main">
                <a href="{{ route('monitoring.product-analysis.show', ['product' => $p['product_id']] + $q) }}" class="roas-product-name">{{ $p['name'] }}</a>
                <span class="roas-product-action">{{ $a['label'] ?? '—' }}</span>
            </div>
            <div class="roas-product-meta">{{ $a['hint'] ?? '' }}</div>
            <div class="roas-product-stats">
                <div><span>Iklan</span><strong>{{ hub_rp($p['spend'], true) }}</strong></div>
                <div><span>Set ROAS</span><strong>{{ $p['set_roas_shopee'] ? number_format($p['set_roas_shopee'], 1).'x' : '—' }}</strong></div>
                <div><span>Shopee</span><strong>{{ $p['shopee_roas'] ? number_format($p['shopee_roas'], 1).'x' : '—' }}</strong></div>
                <div><span>Laba</span><strong class="{{ ($p['net_profit'] ?? 0) >= 0 ? '' : 'amt-neg' }}">{{ hub_rp($p['net_profit'], true) }}</strong></div>
            </div>
        </div>
        @empty
        <div class="ceo-empty-state">
            <i class="fas fa-bullhorn"></i>
            <p>Belum ada produk di toko ini.</p>
            <a href="{{ route('manage.index') }}" class="hub-btn hub-btn-sm hub-btn-primary">Sync produk & iklan</a>
        </div>
        @endforelse

        @if(isset($productsPaginator) && $productsPaginator->total() > 0)
        <div class="hub-pagination mt-3">
            <span class="hub-pagination-info">
                Menampilkan {{ $productsPaginator->firstItem() ?? 0 }}–{{ $productsPaginator->lastItem() ?? 0 }}
                dari {{ $productsPaginator->total() }} produk
            </span>
            {{ $productsPaginator->withQueryString()->links() }}
        </div>
        @endif
    </div>

@include('hub.partials.ceo.shell-close')
@endsection

@if(!empty($charts))
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const c = @json($charts);
    HubCharts.renderPreset('roasDaily', 'ads_daily', c.ads_daily || {});
    HubCharts.renderPreset('roasMonth', 'ads_monthly', {
        labels: (c.ads_monthly || {}).labels,
        data: (c.ads_monthly || {}).data,
        label: 'Spend iklan',
    });
    HubCharts.renderPreset('roasTop', 'ads_top', c.top_spend || {});
});
</script>
@endpush
@endif
