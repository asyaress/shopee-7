@php
    $active = $activeSection ?? 'overview';
    $q = request()->query();
    $tabs = [
        'actions' => ['route' => 'monitoring.actions', 'icon' => 'fa-bolt', 'label' => 'Pusat Aksi', 'short' => 'Aksi'],
        'overview' => ['route' => 'monitoring.index', 'icon' => 'fa-gauge-high', 'label' => 'Ringkasan', 'short' => 'Ringkas'],
        'executive' => ['route' => 'monitoring.executive', 'icon' => 'fa-briefcase', 'label' => 'CEO', 'short' => 'CEO'],
        'shopee' => ['route' => 'monitoring.shopee', 'icon' => 'fa-percent', 'label' => 'Potongan', 'short' => 'Fee'],
        'revenue' => ['route' => 'monitoring.revenue', 'icon' => 'fa-coins', 'label' => 'Pendapatan', 'short' => 'Omzet'],
        'ads' => ['route' => 'monitoring.ads', 'icon' => 'fa-bullhorn', 'label' => 'Iklan', 'short' => 'Iklan'],
        'matrix' => ['route' => 'monitoring.matrix', 'icon' => 'fa-th', 'label' => 'Matrix', 'short' => 'Matrix'],
        'bcg' => ['route' => 'monitoring.bcg', 'icon' => 'fa-chart-scatter', 'label' => 'BCG', 'short' => 'BCG'],
        'rekap' => ['route' => 'monitoring.rekap', 'icon' => 'fa-table', 'label' => 'Rekap', 'short' => 'Rekap'],
        'profit' => ['route' => 'monitoring.profit', 'icon' => 'fa-chart-pie', 'label' => 'Laba', 'short' => 'Laba'],
    ];
@endphp
<nav class="mon-subnav mon-subnav--scroll" aria-label="Navigasi monitoring">
    @foreach($tabs as $key => $tab)
        <a href="{{ route($tab['route'], $q) }}" class="{{ $active === $key ? 'active' : '' }}" title="{{ $tab['label'] }}">
            <i class="fas {{ $tab['icon'] }}"></i>
            <span class="mon-tab-long">{{ $tab['label'] }}</span>
            <span class="mon-tab-short">{{ $tab['short'] ?? $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
