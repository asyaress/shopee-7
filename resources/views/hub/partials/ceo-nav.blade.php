@php
    $active = $activeSection ?? '';
    $tabs = [
        'targets' => ['route' => 'ceo.targets', 'icon' => 'fa-bullseye', 'label' => 'Target'],
        'settlement' => ['route' => 'ceo.settlement', 'icon' => 'fa-wallet', 'label' => 'Arus kas'],
        'promo' => ['route' => 'ceo.promo', 'icon' => 'fa-tags', 'label' => 'Promo'],
        'roas' => ['route' => 'ceo.roas', 'icon' => 'fa-chart-line', 'label' => 'ROAS'],
        'decisions' => ['route' => 'ceo.decisions', 'icon' => 'fa-clipboard-list', 'label' => 'Log keputusan'],
    ];
@endphp
<nav class="mon-subnav mon-subnav--scroll ceo-nav-scroll mb-3">
    @foreach($tabs as $key => $tab)
        <a href="{{ route($tab['route'], request()->query()) }}" class="{{ $active === $key ? 'active' : '' }}">
            <i class="fas {{ $tab['icon'] }}"></i>
            <span class="hide-sm">{{ $tab['label'] }}</span>
        </a>
    @endforeach
    <a href="{{ route('ceo.export.journal', request()->query()) }}" class="ms-auto">
        <i class="fas fa-file-csv"></i> <span class="hide-sm">Jurnal</span>
    </a>
</nav>
