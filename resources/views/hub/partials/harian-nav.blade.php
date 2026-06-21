@php
    $active = $activeSection ?? 'overview';
    $q = request()->query();
    $tabs = [
        'overview' => ['route' => 'monitoring.index', 'icon' => 'fa-gauge-high', 'label' => 'Ringkasan'],
        'actions' => ['route' => 'monitoring.actions', 'icon' => 'fa-bolt', 'label' => 'Pusat Aksi'],
        'targets' => ['route' => 'ceo.targets', 'icon' => 'fa-bullseye', 'label' => 'Target Bulanan'],
    ];
@endphp
<nav class="mon-subnav mon-subnav--scroll mon-subnav--harian mb-3" aria-label="Navigasi harian">
    @foreach($tabs as $key => $tab)
        <a href="{{ route($tab['route'], $q) }}" class="{{ $active === $key ? 'active' : '' }}">
            <i class="fas {{ $tab['icon'] }}"></i>
            <span>{{ $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
