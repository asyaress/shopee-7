@php
    $active = $activeSection ?? '';
    $q = request()->query();
    $tabs = [
        'kalkulator' => ['route' => 'ceo.kalkulator', 'icon' => 'fa-calculator', 'label' => 'Kalkulator'],
        'promo' => ['route' => 'ceo.promo', 'icon' => 'fa-tags', 'label' => 'Promo'],
        'product-analysis' => ['route' => 'monitoring.product-analysis.index', 'icon' => 'fa-microscope', 'label' => 'Analisis Produk'],
        'decisions' => ['route' => 'ceo.decisions', 'icon' => 'fa-clipboard-list', 'label' => 'Log'],
    ];
@endphp
<nav class="mon-subnav mon-subnav--scroll mon-subnav--tools mb-3" aria-label="Navigasi tools">
    @foreach($tabs as $key => $tab)
        <a href="{{ route($tab['route'], $q) }}" class="{{ $active === $key ? 'active' : '' }}">
            <i class="fas {{ $tab['icon'] }}"></i>
            <span>{{ $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
