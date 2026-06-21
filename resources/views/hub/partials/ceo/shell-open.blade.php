@php $g = $ceoGuide ?? null; @endphp
<div class="report-shell ceo-clean{{ !empty($ceoShellClass) ? ' '.$ceoShellClass : '' }}"@if($g) data-ceo-page="{{ $g['id'] }}"@endif>
@if($g)
@include('hub.partials.ceo.header')
@if(empty($ceoActionSkip))
@include('hub.partials.ceo.action')
@endif
@endif
