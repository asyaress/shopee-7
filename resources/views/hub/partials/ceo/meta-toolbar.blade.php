@if(!empty($pageMeta) || !empty($pageActions) || !empty($heroExtra))
<div class="ceo-page-toolbar">
    <div class="ceo-page-meta">
        @foreach($pageMeta ?? [] as $chip)
            @if(($chip['type'] ?? '') === 'score')
            <div class="ceo-meta-chip ceo-meta-chip--score" title="{{ $chip['label'] ?? 'Skor' }}">
                <span class="ceo-score-ring" style="--score: {{ (int) ($chip['value'] ?? 0) }}">
                    <span>{{ (int) ($chip['value'] ?? 0) }}</span>
                </span>
                <span class="ceo-meta-chip-text">
                    <span class="ceo-meta-chip-label">{{ $chip['label'] ?? 'Skor' }}</span>
                    @if(!empty($chip['hint']))
                    <span class="ceo-meta-chip-value">{{ $chip['hint'] }}</span>
                    @endif
                </span>
            </div>
            @else
            <div class="ceo-meta-chip">
                @if(!empty($chip['icon']))
                <span class="ceo-meta-chip-icon"><i class="{{ $chip['icon'] }}"></i></span>
                @endif
                <span class="ceo-meta-chip-text">
                    @if(!empty($chip['label']))
                    <span class="ceo-meta-chip-label">{{ $chip['label'] }}</span>
                    @endif
                    <span class="ceo-meta-chip-value">{{ $chip['value'] ?? '—' }}</span>
                </span>
            </div>
            @endif
        @endforeach
        @if(!empty($heroExtra))
        <div class="ceo-page-meta-legacy">{!! $heroExtra !!}</div>
        @endif
    </div>
    @if(!empty($pageActions))
    <div class="ceo-page-actions">
        @foreach($pageActions as $action)
        <a href="{{ $action['url'] ?? '#' }}"
           class="ceo-page-action ceo-page-action--{{ $action['variant'] ?? 'outline' }}"
           @if(!empty($action['download'])) download @endif>
            @if(!empty($action['icon']))<i class="fas {{ $action['icon'] }}"></i>@endif
            {{ $action['label'] ?? 'Aksi' }}
        </a>
        @endforeach
    </div>
    @endif
</div>
@endif
