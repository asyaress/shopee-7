<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="theme-color" content="#6b1528">
    <title><?php echo $__env->yieldContent('title', 'Shopee Profit Hub'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700&display=swap" rel="stylesheet">
    <link href="<?php echo e(asset('css/hub.css')); ?>?v=4" rel="stylesheet">
    <link href="<?php echo e(asset('css/hub-report.css')); ?>?v=3" rel="stylesheet">
    <link href="<?php echo e(asset('css/hub-pages.css')); ?>?v=2" rel="stylesheet">
    <link href="<?php echo e(asset('css/hub-datatables.css')); ?>?v=3" rel="stylesheet">
    <link href="<?php echo e(asset('css/hub-monitoring.css')); ?>?v=5" rel="stylesheet">
    <link href="<?php echo e(asset('css/hub-charts.css')); ?>?v=2" rel="stylesheet">
    <link href="<?php echo e(asset('css/hub-mobile.css')); ?>?v=1" rel="stylesheet">
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body class="hub-body">
    <header class="hub-topbar">
        <button type="button" class="hub-menu-btn" id="hubSidebarToggle" aria-label="Menu">
            <i class="fas fa-bars"></i>
        </button>
        <a href="<?php echo e(route('monitoring.index')); ?>" class="hub-brand">
            <i class="fas fa-chart-pie"></i>
            <div>
                Shopee Hub
                <span>Profit & Analytics</span>
            </div>
        </a>
        <?php echo $__env->make('hub.partials.shop-switcher', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <nav class="hub-topnav">
            <a href="<?php echo e(route('monitoring.actions')); ?>" class="<?php echo e(request()->routeIs('monitoring.*') ? 'active' : ''); ?>">
                <i class="fas fa-bolt me-1"></i> Aksi
            </a>
            <a href="<?php echo e(route('monitoring.index')); ?>">
                <i class="fas fa-chart-line me-1"></i> Monitoring
            </a>
            <a href="<?php echo e(route('manage.index')); ?>" class="<?php echo e(request()->routeIs('manage.*') ? 'active' : ''); ?>">
                <i class="fas fa-sliders me-1"></i> Kelola Data
            </a>
            <form method="POST" action="<?php echo e(route('logout')); ?>" class="d-inline">
                <?php echo csrf_field(); ?>
                <button type="submit" class="hub-btn hub-btn-sm hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.4);">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </form>
        </nav>
    </header>

    <div class="hub-shell">
        <div class="hub-sidebar-backdrop" id="hubSidebarBackdrop" aria-hidden="true"></div>
        <aside class="hub-sidebar" id="hubSidebar">
            <div class="nav-label">Monitoring</div>
            <a href="<?php echo e(route('monitoring.actions')); ?>" class="nav-sublink <?php echo e(request()->routeIs('monitoring.actions') ? 'active' : ''); ?>">
                <i class="fas fa-bolt"></i> Pusat Aksi
            </a>
            <a href="<?php echo e(route('monitoring.index')); ?>" class="<?php echo e(request()->routeIs('monitoring.index') ? 'active' : ''); ?>">
                <i class="fas fa-gauge-high"></i> Ringkasan
            </a>
            <a href="<?php echo e(route('monitoring.executive')); ?>" class="nav-sublink <?php echo e(request()->routeIs('monitoring.executive') ? 'active' : ''); ?>">
                <i class="fas fa-briefcase"></i> CEO Brief
            </a>
            <a href="<?php echo e(route('monitoring.shopee')); ?>" class="nav-sublink <?php echo e(request()->routeIs('monitoring.shopee') ? 'active' : ''); ?>">
                <i class="fas fa-percent"></i> Potongan Shopee
            </a>
            <a href="<?php echo e(route('monitoring.revenue')); ?>" class="nav-sublink <?php echo e(request()->routeIs('monitoring.revenue') ? 'active' : ''); ?>">
                <i class="fas fa-coins"></i> Pendapatan
            </a>
            <a href="<?php echo e(route('monitoring.ads')); ?>" class="nav-sublink <?php echo e(request()->routeIs('monitoring.ads') ? 'active' : ''); ?>">
                <i class="fas fa-bullhorn"></i> Iklan
            </a>
            <a href="<?php echo e(route('monitoring.profit')); ?>" class="nav-sublink <?php echo e(request()->routeIs('monitoring.profit') ? 'active' : ''); ?>">
                <i class="fas fa-chart-pie"></i> Laba & Produk
            </a>
            <a href="<?php echo e(route('monitoring.matrix')); ?>" class="nav-sublink <?php echo e(request()->routeIs('monitoring.matrix') ? 'active' : ''); ?>">
                <i class="fas fa-th"></i> Matrix SKU
            </a>

            <div class="nav-label">CEO Tools</div>
            <a href="<?php echo e(route('ceo.targets')); ?>" class="nav-sublink <?php echo e(request()->routeIs('ceo.targets') ? 'active' : ''); ?>">
                <i class="fas fa-bullseye"></i> Target Bulanan
            </a>
            <a href="<?php echo e(route('ceo.roas')); ?>" class="nav-sublink <?php echo e(request()->routeIs('ceo.roas') ? 'active' : ''); ?>">
                <i class="fas fa-chart-line"></i> ROAS Advisor
            </a>
            <a href="<?php echo e(route('ceo.settlement')); ?>" class="nav-sublink <?php echo e(request()->routeIs('ceo.settlement') ? 'active' : ''); ?>">
                <i class="fas fa-wallet"></i> Arus Kas
            </a>
            <a href="<?php echo e(route('ceo.promo')); ?>" class="nav-sublink <?php echo e(request()->routeIs('ceo.promo') ? 'active' : ''); ?>">
                <i class="fas fa-tags"></i> Promo & Fee
            </a>
            <a href="<?php echo e(route('ceo.decisions')); ?>" class="nav-sublink <?php echo e(request()->routeIs('ceo.decisions') ? 'active' : ''); ?>">
                <i class="fas fa-clipboard-list"></i> Log Keputusan
            </a>

            <div class="nav-label">Utama</div>
            <a href="<?php echo e(route('manage.index')); ?>" class="<?php echo e(request()->routeIs('manage.*') ? 'active' : ''); ?>">
                <i class="fas fa-database"></i> Kelola Data
            </a>
            <a href="<?php echo e(route('hpp.index')); ?>" class="<?php echo e(request()->routeIs('hpp.*') ? 'active' : ''); ?>">
                <i class="fas fa-tags"></i> Input HPP
            </a>

            <div class="nav-label">Data</div>
            <a href="<?php echo e(route('orders.index')); ?>" class="<?php echo e(request()->routeIs('orders.*') ? 'active' : ''); ?>">
                <i class="fas fa-shopping-bag"></i> Pesanan
            </a>
            <a href="<?php echo e(route('products.index')); ?>" class="<?php echo e(request()->routeIs('products.*') ? 'active' : ''); ?>">
                <i class="fas fa-box"></i> Produk
            </a>
            <a href="<?php echo e(route('customers.index')); ?>" class="<?php echo e(request()->routeIs('customers.*') ? 'active' : ''); ?>">
                <i class="fas fa-users"></i> Customer
            </a>
            <a href="<?php echo e(route('reports.profit')); ?>" class="<?php echo e(request()->routeIs('reports.*') ? 'active' : ''); ?>">
                <i class="fas fa-file-invoice-dollar"></i> Laporan Legacy
            </a>

            <div class="nav-label">Shopee</div>
            <a href="<?php echo e(route('shopee.index')); ?>" class="<?php echo e(request()->routeIs('shopee.*') ? 'active' : ''); ?>">
                <i class="fas fa-plug"></i> Integrasi
            </a>
        </aside>

        <main class="hub-main">
            <?php if(session('success')): ?>
                <div class="hub-alert hub-alert-info">
                    <i class="fas fa-check-circle me-2"></i><?php echo e(session('success')); ?>

                </div>
            <?php endif; ?>
            <?php if(session('error')): ?>
                <div class="hub-alert" style="background:#fee2e2;border-color:#fecaca;color:#991b1b;">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo e(session('error')); ?>

                </div>
            <?php endif; ?>

            <?php echo $__env->yieldContent('content'); ?>
        </main>
    </div>

    <nav class="hub-bottomnav" aria-label="Navigasi utama">
        <a href="<?php echo e(route('monitoring.actions')); ?>" class="<?php echo e(request()->routeIs('monitoring.actions') ? 'active' : ''); ?>">
            <i class="fas fa-bolt"></i>
            Aksi
        </a>
        <a href="<?php echo e(route('monitoring.index')); ?>" class="<?php echo e(request()->routeIs('monitoring.*') && !request()->routeIs('monitoring.actions') ? 'active' : ''); ?>">
            <i class="fas fa-chart-line"></i>
            Monitor
        </a>
        <a href="<?php echo e(route('manage.index')); ?>" class="<?php echo e(request()->routeIs('manage.*') ? 'active' : ''); ?>">
            <i class="fas fa-sliders"></i>
            Kelola
        </a>
        <a href="<?php echo e(route('hpp.index')); ?>" class="<?php echo e(request()->routeIs('hpp.*') ? 'active' : ''); ?>">
            <i class="fas fa-tags"></i>
            HPP
        </a>
        <a href="#" id="hubBottomMenu" aria-label="Menu lengkap">
            <i class="fas fa-bars"></i>
            Menu
        </a>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="<?php echo e(asset('js/hub-charts.js')); ?>?v=2"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo e(asset('js/hub-mobile.js')); ?>?v=1"></script>
    <script>
        window.fmtRp = (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(n || 0));
        window.fmtPct = (r) => r == null ? '—' : (r * 100).toFixed(1) + '%';
    </script>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH D:\A. SHOPEE-7\resources\views/layouts/hub.blade.php ENDPATH**/ ?>