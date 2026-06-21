@foreach($products ?? [] as $i => $p)
@php
    $profit = (int) ($p['net_profit'] ?? 0);
    $searchKey = strtolower(($p['name'] ?? '') . ' ' . ($p['sku'] ?? ''));
@endphp
<article class="hub-product-card-v2 {{ $profit < 0 ? 'is-negative' : '' }}" data-search="{{ $searchKey }}">
    <div class="card-head">
        <div>
            @if(!empty($p['product_id']))
            <a href="{{ route('monitoring.product-analysis.show', ['product' => $p['product_id']] + request()->query()) }}" class="name">{{ $p['name'] }}</a>
            @else
            <span class="name">{{ $p['name'] }}</span>
            @endif
            @include('hub.partials.product-shopee-links', ['links' => $p['links'] ?? []])
            <div class="mt-1">
                @if(!empty($p['sku']))<span class="sku text-muted small">{{ $p['sku'] }}</span>@endif
                <span class="sku-tier {{ $p['tier'] ?? '' }} ms-1">{{ $p['tier'] ?? '—' }}</span>
            </div>
        </div>
        <div class="card-profit {{ $profit >= 0 ? 'amt-pos' : 'amt-neg' }}">{{ hub_rp($profit, true) }}</div>
    </div>
    <div class="card-grid">
        <div><span>Qty</span><strong>{{ hub_num($p['qty'] ?? 0) }}</strong></div>
        <div><span>Margin</span><strong>{{ hub_pct($p['margin'] ?? null) }}</strong></div>
        <div><span>Net</span><strong>{{ hub_rp($p['net'] ?? 0) }}</strong></div>
        <div><span>Iklan</span><strong>{{ hub_rp($p['ads_spend'] ?? 0) }}</strong></div>
        <div><span>ROAS</span><strong>{{ isset($p['roas']) && $p['roas'] ? number_format($p['roas'], 2).'x' : '—' }}</strong></div>
        <div><span>HPP</span><strong>{{ hub_rp($p['cogs'] ?? 0) }}</strong></div>
    </div>
    @if(!empty($p['action']['title']))
    <p class="small text-muted mb-0 mt-2">{{ $p['action']['title'] }}</p>
    @endif
</article>
@endforeach
