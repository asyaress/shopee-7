@props([
    'id',
    'title',
    'subtitle' => null,
    'size' => 'default',
    'badge' => null,
    'kpis' => [],
])

<div class="fc-chart-panel">
    <div class="fc-chart-panel__head">
        <div>
            <h3>{{ $title }}</h3>
            @if($subtitle)<p>{{ $subtitle }}</p>@endif
        </div>
        @if($badge)<span class="fc-chart-panel__badge">{{ $badge }}</span>@endif
    </div>
    @if(!empty($kpis))
    <div class="fc-chart-kpis">
        @foreach($kpis as $kpi)
        <span class="fc-chart-kpi">{{ $kpi['label'] }}<strong>{{ $kpi['value'] }}</strong></span>
        @endforeach
    </div>
    @endif
    <div @class([
        'fc-chart-panel__canvas',
        'fc-chart-panel__canvas--hero' => $size === 'hero',
        'fc-chart-panel__canvas--compact' => $size === 'compact',
        'fc-chart-panel__canvas--square' => $size === 'square',
    ])>
        <canvas id="{{ $id }}" role="img" aria-label="{{ $title }}"></canvas>
    </div>
</div>
