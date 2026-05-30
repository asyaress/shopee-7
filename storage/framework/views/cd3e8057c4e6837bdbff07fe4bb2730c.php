<?php
    $active = $activeSection ?? 'overview';
    $q = request()->query();
    $tabs = [
        'actions' => ['route' => 'monitoring.actions', 'icon' => 'fa-bolt', 'label' => 'Pusat Aksi'],
        'overview' => ['route' => 'monitoring.index', 'icon' => 'fa-gauge-high', 'label' => 'Ringkasan'],
        'executive' => ['route' => 'monitoring.executive', 'icon' => 'fa-briefcase', 'label' => 'CEO'],
        'shopee' => ['route' => 'monitoring.shopee', 'icon' => 'fa-percent', 'label' => 'Potongan'],
        'revenue' => ['route' => 'monitoring.revenue', 'icon' => 'fa-coins', 'label' => 'Pendapatan'],
        'ads' => ['route' => 'monitoring.ads', 'icon' => 'fa-bullhorn', 'label' => 'Iklan'],
        'matrix' => ['route' => 'monitoring.matrix', 'icon' => 'fa-th', 'label' => 'Matrix'],
        'bcg' => ['route' => 'monitoring.bcg', 'icon' => 'fa-chart-scatter', 'label' => 'BCG'],
        'rekap' => ['route' => 'monitoring.rekap', 'icon' => 'fa-table', 'label' => 'Rekap'],
        'profit' => ['route' => 'monitoring.profit', 'icon' => 'fa-chart-pie', 'label' => 'Laba'],
    ];
?>
<nav class="mon-subnav" aria-label="Navigasi monitoring">
    <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $tab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route($tab['route'], $q)); ?>" class="<?php echo e($active === $key ? 'active' : ''); ?>">
            <i class="fas <?php echo e($tab['icon']); ?>"></i>
            <span class="hide-sm"><?php echo e($tab['label']); ?></span>
        </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</nav>
<?php /**PATH D:\A. SHOPEE-7\resources\views\hub\partials\monitoring-nav.blade.php ENDPATH**/ ?>