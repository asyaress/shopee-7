<?php
    $active = $activeSection ?? '';
    $tabs = [
        'targets' => ['route' => 'ceo.targets', 'icon' => 'fa-bullseye', 'label' => 'Target'],
        'settlement' => ['route' => 'ceo.settlement', 'icon' => 'fa-wallet', 'label' => 'Arus kas'],
        'promo' => ['route' => 'ceo.promo', 'icon' => 'fa-tags', 'label' => 'Promo'],
        'roas' => ['route' => 'ceo.roas', 'icon' => 'fa-chart-line', 'label' => 'ROAS'],
        'decisions' => ['route' => 'ceo.decisions', 'icon' => 'fa-clipboard-list', 'label' => 'Log keputusan'],
    ];
?>
<nav class="mon-subnav mon-subnav--scroll ceo-nav-scroll mb-3">
    <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $tab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route($tab['route'], request()->query())); ?>" class="<?php echo e($active === $key ? 'active' : ''); ?>">
            <i class="fas <?php echo e($tab['icon']); ?>"></i>
            <span class="hide-sm"><?php echo e($tab['label']); ?></span>
        </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <a href="<?php echo e(route('ceo.export.journal', request()->query())); ?>" class="ms-auto">
        <i class="fas fa-file-csv"></i> <span class="hide-sm">Jurnal</span>
    </a>
</nav>
<?php /**PATH D:\A. SHOPEE-7\resources\views/hub/partials/ceo-nav.blade.php ENDPATH**/ ?>