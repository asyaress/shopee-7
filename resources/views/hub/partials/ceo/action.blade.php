@php
    $act = $action ?? $ceoAction ?? ($ceoGuide['action'] ?? null);
@endphp
@if(!empty($act))
<div class="ceo-action-banner ceo-action-{{ $act['severity'] ?? 'info' }} mb-3" data-ceo="action">
    <div class="ceo-action-icon">
        @if(($act['severity'] ?? '') === 'success')<i class="fas fa-check-circle"></i>
        @elseif(($act['severity'] ?? '') === 'danger')<i class="fas fa-stop-circle"></i>
        @elseif(($act['severity'] ?? '') === 'warning')<i class="fas fa-exclamation-circle"></i>
        @else<i class="fas fa-lightbulb"></i>@endif
    </div>
    <div class="ceo-action-body">
        <div class="ceo-action-title">{{ $act['title'] ?? 'Langkah CEO' }}</div>
        @if(!empty($act['headline']))
        <div class="ceo-action-headline">{{ $act['headline'] }}</div>
        @endif
        @if(!empty($act['steps']))
        <ul class="ceo-action-steps mb-0">
            @foreach($act['steps'] as $step)
            @if($step)<li>{!! preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', e($step)) !!}</li>@endif
            @endforeach
        </ul>
        @endif
    </div>
    @if(!empty($act['cta']))
    @php
        $ctaRoute = $act['cta']['route'] ?? '';
        $ctaHref = str_starts_with($ctaRoute, '#') ? $ctaRoute : route($ctaRoute, $act['cta']['params'] ?? request()->query());
    @endphp
    <a href="{{ $ctaHref }}" class="hub-btn hub-btn-sm hub-btn-primary ceo-action-cta">{{ $act['cta']['label'] }}</a>
    @endif
</div>
@endif
