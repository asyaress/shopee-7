@php
    $action = $item['action'] ?? [];
    $sev = $action['severity'] ?? 'info';
    $pid = $item['product_id'] ?? 0;
    $q = request()->query();
@endphp
<div class="mon-action-item mon-action-{{ $sev }}">
    <div class="mon-action-head">
        <span class="sku-tier {{ $item['tier'] ?? '' }}">{{ strtoupper($item['tier'] ?? '—') }}</span>
        <a href="{{ $pid ? route('monitoring.product', ['product' => $pid] + $q) : '#' }}" class="mon-action-name">{{ $item['name'] ?? '—' }}</a>
    </div>
    <div class="mon-action-title">{{ $action['title'] ?? '' }}</div>
    <p class="mon-action-summary small text-muted mb-1">{{ $action['summary'] ?? '' }}</p>
    @if(!empty($action['reasons']))
    <ul class="mon-action-reasons small mb-2">
        @foreach($action['reasons'] as $r)<li>{{ $r }}</li>@endforeach
    </ul>
    @endif
    <div class="small">
        Laba {{ hub_rp($item['net_profit'] ?? 0, true) }} · Iklan {{ hub_rp($item['ads_spend'] ?? 0) }}
        @if(isset($item['roas']) && $item['roas']) · ROAS {{ number_format($item['roas'], 2) }}x @endif
        @if(!empty($item['pricing']['prices']['recommended_gross']))
        · Harga disarankan {{ hub_rp($item['pricing']['prices']['recommended_gross']) }}
        @endif
        @if(!empty($item['ads_metrics']['cpc']))
        · CPC {{ hub_rp($item['ads_metrics']['cpc']) }}
        @endif
    </div>
</div>
