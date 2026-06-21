<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#6b1528">
    <title>@yield('title', 'Shopee Profit Hub')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <link href="{{ asset('css/hub.css') }}?v=5" rel="stylesheet">
    <link href="{{ asset('css/hub-report.css') }}?v=4" rel="stylesheet">
    <link href="{{ asset('css/hub-pages.css') }}?v=2" rel="stylesheet">
    <link href="{{ asset('css/hub-datatables.css') }}?v=3" rel="stylesheet">
    <link href="{{ asset('css/hub-monitoring.css') }}?v=10" rel="stylesheet">
    <link href="{{ asset('css/hub-charts.css') }}?v=4" rel="stylesheet">
    <link href="{{ asset('css/hub-mobile.css') }}?v=2" rel="stylesheet">
    <link href="{{ asset('css/hub-chatbot.css') }}?v=2" rel="stylesheet">
    @stack('styles')
</head>
<body class="hub-body">
    <header class="hub-topbar">
        <button type="button" class="hub-menu-btn" id="hubSidebarToggle" aria-label="Menu">
            <i class="fas fa-bars"></i>
        </button>
        <a href="{{ route('monitoring.index') }}" class="hub-brand">
            <i class="fas fa-chart-pie"></i>
            <div>
                Shopee Hub
                <span>Profit & Analytics</span>
            </div>
        </a>
        @include('hub.partials.shop-switcher')
        <nav class="hub-topnav">
            <a href="{{ route('monitoring.actions') }}" class="{{ request()->routeIs('monitoring.*') ? 'active' : '' }}">
                <i class="fas fa-bolt me-1"></i> Aksi
            </a>
            <a href="{{ route('monitoring.index') }}">
                <i class="fas fa-gauge-high me-1"></i> Ringkasan
            </a>
            <a href="{{ route('manage.index') }}" class="{{ request()->routeIs('manage.*') ? 'active' : '' }}">
                <i class="fas fa-sliders me-1"></i> Kelola Data
            </a>
            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="hub-btn hub-btn-sm hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.4);">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </form>
        </nav>
    </header>

    <div class="hub-shell">
        <div class="hub-sidebar-backdrop" id="hubSidebarBackdrop" aria-hidden="true"></div>
        <aside class="hub-sidebar" id="hubSidebar">
            <div class="nav-label">Harian</div>
            <a href="{{ route('monitoring.index') }}" class="{{ request()->routeIs('monitoring.index') ? 'active' : '' }}">
                <i class="fas fa-gauge-high"></i> Ringkasan
            </a>
            <a href="{{ route('monitoring.actions') }}" class="nav-sublink {{ request()->routeIs('monitoring.actions') ? 'active' : '' }}">
                <i class="fas fa-bolt"></i> Pusat Aksi
            </a>
            <a href="{{ route('ceo.targets') }}" class="nav-sublink {{ request()->routeIs('ceo.targets') ? 'active' : '' }}">
                <i class="fas fa-bullseye"></i> Target Bulanan
            </a>

            <div class="nav-label">Laporan</div>
            <a href="{{ route('monitoring.profit') }}" class="nav-sublink {{ request()->routeIs('monitoring.profit') ? 'active' : '' }}">
                <i class="fas fa-chart-pie"></i> Laba Detail
            </a>
            <a href="{{ route('monitoring.rekap') }}" class="nav-sublink {{ request()->routeIs('monitoring.rekap') ? 'active' : '' }}">
                <i class="fas fa-table"></i> Rekap Bulanan
            </a>
            <a href="{{ route('monitoring.revenue') }}" class="nav-sublink {{ request()->routeIs('monitoring.revenue') ? 'active' : '' }}">
                <i class="fas fa-coins"></i> Pendapatan
            </a>
            <a href="{{ route('monitoring.shopee') }}" class="nav-sublink {{ request()->routeIs('monitoring.shopee') ? 'active' : '' }}">
                <i class="fas fa-percent"></i> Potongan Shopee
            </a>
            <a href="{{ route('ceo.settlement') }}" class="nav-sublink {{ request()->routeIs('ceo.settlement') ? 'active' : '' }}">
                <i class="fas fa-wallet"></i> Arus Kas
            </a>

            <div class="nav-label">Marketing</div>
            <a href="{{ route('monitoring.ads') }}" class="nav-sublink {{ request()->routeIs('monitoring.ads') ? 'active' : '' }}">
                <i class="fas fa-bullhorn"></i> Iklan
            </a>
            <a href="{{ route('ceo.roas') }}" class="nav-sublink {{ request()->routeIs('ceo.roas') ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i> Analisa Iklan
            </a>
            <a href="{{ route('monitoring.bcg') }}" class="nav-sublink {{ request()->routeIs('monitoring.bcg') ? 'active' : '' }}">
                <i class="fas fa-chart-scatter"></i> BCG & Trafik
            </a>
            <a href="{{ route('monitoring.matrix') }}" class="nav-sublink {{ request()->routeIs('monitoring.matrix') ? 'active' : '' }}">
                <i class="fas fa-th"></i> Laba per SKU
            </a>

            <div class="nav-label">Tools</div>
            <a href="{{ route('ceo.kalkulator') }}" class="nav-sublink {{ request()->routeIs('ceo.kalkulator') ? 'active' : '' }}">
                <i class="fas fa-calculator"></i> Kalkulator Harga
            </a>
            <a href="{{ route('ceo.promo') }}" class="nav-sublink {{ request()->routeIs('ceo.promo') ? 'active' : '' }}">
                <i class="fas fa-tags"></i> Promo & Diskon
            </a>
            <a href="{{ route('monitoring.product-analysis.index') }}" class="nav-sublink {{ request()->routeIs('monitoring.product-analysis.*') ? 'active' : '' }}">
                <i class="fas fa-microscope"></i> Analisis Produk
            </a>
            <a href="{{ route('ceo.decisions') }}" class="nav-sublink {{ request()->routeIs('ceo.decisions') ? 'active' : '' }}">
                <i class="fas fa-clipboard-list"></i> Log Keputusan
            </a>

            <div class="nav-label">Utama</div>
            <a href="{{ route('manage.index') }}" class="{{ request()->routeIs('manage.*') ? 'active' : '' }}">
                <i class="fas fa-database"></i> Kelola Data
            </a>
            <a href="{{ route('hpp.index') }}" class="{{ request()->routeIs('hpp.*') ? 'active' : '' }}">
                <i class="fas fa-tags"></i> Input HPP
            </a>

            <div class="nav-label">Data</div>
            <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') ? 'active' : '' }}">
                <i class="fas fa-shopping-bag"></i> Pesanan
            </a>
            <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'active' : '' }}">
                <i class="fas fa-box"></i> Produk
            </a>
            <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'active' : '' }}">
                <i class="fas fa-users"></i> Customer
            </a>
            <a href="{{ route('reports.profit') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="fas fa-file-invoice-dollar"></i> Laporan Legacy
            </a>

            <div class="nav-label">Shopee</div>
            <a href="{{ route('shopee.index') }}" class="{{ request()->routeIs('shopee.*') ? 'active' : '' }}">
                <i class="fas fa-plug"></i> Integrasi
            </a>
        </aside>

        <main class="hub-main">
            @if(session('success'))
                <div class="hub-alert hub-alert-info">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="hub-alert" style="background:#fee2e2;border-color:#fecaca;color:#991b1b;">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <nav class="hub-bottomnav" aria-label="Navigasi utama">
        <a href="{{ route('monitoring.actions') }}" class="{{ request()->routeIs('monitoring.actions') ? 'active' : '' }}">
            <i class="fas fa-bolt"></i>
            Aksi
        </a>
        <a href="{{ route('monitoring.index') }}" class="{{ request()->routeIs('monitoring.index') || request()->routeIs('ceo.targets') ? 'active' : '' }}">
            <i class="fas fa-gauge-high"></i>
            Ringkas
        </a>
        <a href="{{ route('manage.index') }}" class="{{ request()->routeIs('manage.*') ? 'active' : '' }}">
            <i class="fas fa-sliders"></i>
            Kelola
        </a>
        <a href="{{ route('hpp.index') }}" class="{{ request()->routeIs('hpp.*') ? 'active' : '' }}">
            <i class="fas fa-tags"></i>
            HPP
        </a>
        <a href="#" id="hubBottomMenu" aria-label="Menu lengkap">
            <i class="fas fa-bars"></i>
            Menu
        </a>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.49.1/dist/apexcharts.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="{{ asset('js/hub-charts.js') }}?v=3"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/hub-mobile.js') }}?v=2"></script>
    <script>
        window.fmtRp = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n || 0));
        window.fmtPct = (r) => r == null ? '—' : (r * 100).toFixed(1) + '%';
    </script>
    @stack('scripts')
    @include('hub.partials.ceo.chatbot')
    <script src="{{ asset('js/hub-chatbot.js') }}?v=2"></script>
</body>
</html>
