@php
    $active = $activeSection ?? 'ads';
    $q = request()->query();
    $tabs = [
        'ads' => ['route' => 'monitoring.ads', 'icon' => 'fa-bullhorn', 'label' => 'Iklan'],
        'bcg' => ['route' => 'monitoring.bcg', 'icon' => 'fa-chart-scatter', 'label' => 'BCG'],
        'matrix' => ['route' => 'monitoring.matrix', 'icon' => 'fa-th', 'label' => 'Laba SKU'],
    ];
@endphp
<nav class="mon-subnav mon-subnav--scroll mon-subnav--marketing mb-3" aria-label="Navigasi marketing">
    @foreach($tabs as $key => $tab)
        <a href="{{ route($tab['route'], $q) }}" class="{{ $active === $key ? 'active' : '' }}">
            <i class="fas {{ $tab['icon'] }}"></i>
            <span>{{ $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
