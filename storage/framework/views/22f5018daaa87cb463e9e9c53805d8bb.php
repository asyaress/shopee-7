<?php $__env->startSection('title', 'Matrix SKU — Monitoring'); ?>

<?php $__env->startPush('styles'); ?>
<link href="<?php echo e(asset('css/hub-monitoring.css')); ?>?v=2" rel="stylesheet">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php $q = request()->query(); ?>
<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-th me-2"></i>Matrix SKU</h1>
        <div class="report-hero-meta">
            <span><i class="fas fa-store"></i> <?php echo e($shop['label'] ?? '—'); ?></span>
            <span><?php echo e($meta['period_label'] ?? ''); ?></span>
        </div>
    </div>

    <?php echo $__env->make('hub.partials.monitoring-nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('hub.partials.monitoring-filter', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <?php
        $blocks = [
            'stars' => ['label' => 'Stars', 'desc' => 'Laba bagus · iklan efisien', 'class' => 'tier-star'],
            'maintain' => ['label' => 'Maintain', 'desc' => 'Pertahankan', 'class' => 'tier-maintain'],
            'fix_price' => ['label' => 'Perbaiki harga', 'desc' => 'Margin tipis, volume tinggi', 'class' => 'tier-fix'],
            'bleeders' => ['label' => 'Bleeders', 'desc' => 'Rugi — prioritaskan tindakan', 'class' => 'tier-bleeder'],
        ];
    ?>

    <div class="mon-matrix-grid">
        <?php $__currentLoopData = $blocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="mon-matrix-col <?php echo e($meta['class']); ?>">
            <h3><?php echo e($meta['label']); ?> <span class="badge bg-secondary"><?php echo e(count($quadrants[$key] ?? [])); ?></span></h3>
            <p class="small text-muted"><?php echo e($meta['desc']); ?></p>
            <ul class="mon-matrix-list">
                <?php $__currentLoopData = array_slice($quadrants[$key] ?? [], 0, 12); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li>
                    <a href="<?php echo e(route('monitoring.product', ['product' => $p['product_id']] + $q)); ?>"><?php echo e($p['name']); ?></a>
                    <?php echo $__env->make('hub.partials.product-shopee-links', ['links' => $p['links'] ?? []], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    <span class="<?php echo e(($p['net_profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg'); ?>"><?php echo e(hub_rp($p['net_profit'] ?? 0, true)); ?></span>
                </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views\hub\monitoring\matrix.blade.php ENDPATH**/ ?>