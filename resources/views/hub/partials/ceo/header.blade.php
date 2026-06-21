@php $g = $ceoGuide ?? null; @endphp
@if($g)
<div class="ceo-page-header report-hero report-hero--compact mb-2" data-ceo="hero">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h1 class="h4 mb-0"><i class="fas {{ $g['icon'] }} me-2"></i>{{ $g['title'] }}</h1>
            @if(!empty($g['subtitle']))
            <p class="ceo-page-sub mb-0">{{ $g['subtitle'] }}</p>
            @endif
        </div>
        <button type="button" class="ceo-help-btn" data-ceo-reopen title="Buka panduan halaman">
            <i class="fas fa-circle-question"></i> Panduan
        </button>
    </div>
    @if(!empty($heroExtra))
    <div class="ceo-page-extra mt-2">{!! $heroExtra !!}</div>
    @endif
</div>
@endif
