@php
    $pricing = $row['pricing'] ?? null;
    $adsM = $row['ads_metrics'] ?? null;
    $adsRec = $row['ads_rec'] ?? null;
    $pr = $pricing['recommendation'] ?? [];
@endphp
@if($pricing || $adsM)
<div class="mon-rec-block mb-3">
    @if($pricing)
    <div class="mon-rec-section">
        <h4><i class="fas fa-tag me-1"></i> Harga jual — {{ $pricing['status_label'] ?? '' }}</h4>
        <p class="mb-1 fw-semibold">{{ $pr['title'] ?? '' }}</p>
        @foreach($pr['lines'] ?? [] as $line)
        <p class="small text-muted mb-1">{!! preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', e($line)) !!}</p>
        @endforeach
        <div class="mon-rec-metrics small">
            <span>Rata-rata jual: <strong>{{ hub_rp($pricing['prices']['avg_selling'] ?? 0) }}</strong></span>
            <span>Impas: <strong>{{ hub_rp($pricing['prices']['breakeven_gross'] ?? 0) }}</strong></span>
            <span>Disarankan: <strong>{{ hub_rp($pricing['prices']['recommended_gross'] ?? 0) }}</strong></span>
        </div>
        <div class="mon-rec-metrics small text-muted">
            Per unit → HPP {{ hub_rp($pricing['per_unit']['cogs'] ?? 0) }}
            + iklan {{ hub_rp($pricing['per_unit']['ads'] ?? 0) }}
            + ops {{ hub_rp($pricing['per_unit']['operational'] ?? 0) }}
            = {{ hub_rp($pricing['per_unit']['total_cost'] ?? 0) }}
        </div>
    </div>
    @endif
    @if($adsM)
    <div class="mon-rec-section">
        <h4><i class="fas fa-bullhorn me-1"></i> Iklan (data aktual)</h4>
        <div class="mon-rec-metrics small">
            <span>Spend {{ hub_rp($adsM['spend'] ?? 0) }}</span>
            @if($adsM['cpc'] ?? null)<span>CPC {{ hub_rp($adsM['cpc']) }}</span>@endif
            @if($adsM['ctr'] ?? null)<span>CTR {{ $adsM['ctr'] }}%</span>@endif
            @if($adsM['business_roas'] ?? null)<span>ROAS bisnis {{ $adsM['business_roas'] }}x</span>@endif
            @if($adsM['target_roas'] ?? null)<span>Target {{ $adsM['target_roas'] }}x</span>@endif
            @if($adsM['shopee_roas'] ?? null)<span>ROAS GMV {{ $adsM['shopee_roas'] }}x</span>@endif
        </div>
        @if($adsRec)
        <p class="small mb-0 mt-2 mon-action-{{ $adsRec['severity'] ?? 'info' }}"><strong>{{ $adsRec['title'] }}</strong>
            @foreach($adsRec['lines'] ?? [] as $l) — {{ $l }} @endforeach
        </p>
        @endif
    </div>
    @endif
</div>
@endif
