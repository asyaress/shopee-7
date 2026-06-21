@php $zone = $navZone ?? null; @endphp
@if($zone === 'harian')
    @include('hub.partials.harian-nav')
@elseif($zone === 'laporan')
    @include('hub.partials.report-tabs')
@elseif($zone === 'marketing')
    @include('hub.partials.marketing-tabs')
@elseif($zone === 'tools')
    @include('hub.partials.tools-nav')
@endif
