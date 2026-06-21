@php $g = $ceoGuide ?? null; @endphp
@if($g)
<header class="ceo-page-header">
    <div class="ceo-page-top">
        <div class="ceo-page-brand">
            <span class="ceo-page-icon" aria-hidden="true"><i class="fas {{ $g['icon'] }}"></i></span>
            <div class="ceo-page-titles">
                <h1 class="ceo-page-title">{{ $g['title'] }}</h1>
                @if(!empty($g['subtitle']))
                <p class="ceo-page-sub">{{ $g['subtitle'] }}</p>
                @endif
            </div>
        </div>
    </div>
    @include('hub.partials.ceo.meta-toolbar')
</header>
@endif
