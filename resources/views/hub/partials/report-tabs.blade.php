@php
    $active = $activeSection ?? 'profit';
    $q = request()->query();
    $tabs = [
        'profit' => ['route' => 'monitoring.profit', 'icon' => 'fa-chart-pie', 'label' => 'Laba'],
        'rekap' => ['route' => 'monitoring.rekap', 'icon' => 'fa-table', 'label' => 'Rekap'],
        'revenue' => ['route' => 'monitoring.revenue', 'icon' => 'fa-coins', 'label' => 'Pendapatan'],
        'shopee' => ['route' => 'monitoring.shopee', 'icon' => 'fa-percent', 'label' => 'Potongan'],
        'settlement' => ['route' => 'ceo.settlement', 'icon' => 'fa-wallet', 'label' => 'Arus Kas'],
    ];
@endphp
<nav class="mon-subnav mon-subnav--scroll mon-subnav--laporan mb-3" aria-label="Navigasi laporan">
    @foreach($tabs as $key => $tab)
        <a href="{{ route($tab['route'], $q) }}" class="{{ $active === $key ? 'active' : '' }}">
            <i class="fas {{ $tab['icon'] }}"></i>
            <span>{{ $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
