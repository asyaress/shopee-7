@php
    $active = $activeSection ?? 'overview';
    $q = request()->query();
    $tabs = [
        'overview' => ['route' => 'monitoring.index', 'icon' => 'fa-gauge-high', 'label' => 'Ringkasan', 'short' => 'Ringkas'],
        'actions' => ['route' => 'monitoring.actions', 'icon' => 'fa-bolt', 'label' => 'Pusat Aksi', 'short' => 'Aksi'],
        'profit' => ['route' => 'monitoring.profit', 'icon' => 'fa-chart-pie', 'label' => 'Laba', 'short' => 'Laba'],
        'rekap' => ['route' => 'monitoring.rekap', 'icon' => 'fa-table', 'label' => 'Rekap', 'short' => 'Rekap'],
        'revenue' => ['route' => 'monitoring.revenue', 'icon' => 'fa-coins', 'label' => 'Pendapatan', 'short' => 'Omzet'],
        'shopee' => ['route' => 'monitoring.shopee', 'icon' => 'fa-percent', 'label' => 'Potongan', 'short' => 'Fee'],
        'ads' => ['route' => 'monitoring.ads', 'icon' => 'fa-bullhorn', 'label' => 'Iklan', 'short' => 'Iklan'],
        'matrix' => ['route' => 'monitoring.matrix', 'icon' => 'fa-th', 'label' => 'Laba SKU', 'short' => 'SKU'],
        'bcg' => ['route' => 'monitoring.bcg', 'icon' => 'fa-chart-scatter', 'label' => 'BCG', 'short' => 'BCG'],
    ];
    $showTabs = !in_array($active, ['product-analysis'], true);
@endphp
@if($showTabs)
<nav class="mon-subnav mon-subnav--scroll" aria-label="Navigasi monitoring">
    @foreach($tabs as $key => $tab)
        <a href="{{ route($tab['route'], $q) }}" class="{{ $active === $key ? 'active' : '' }}" title="{{ $tab['label'] }}">
            <i class="fas {{ $tab['icon'] }}"></i>
            <span class="mon-tab-long">{{ $tab['label'] }}</span>
            <span class="mon-tab-short">{{ $tab['short'] ?? $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
@endif
