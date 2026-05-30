<?php $__env->startSection('title', 'Analisis Promo — CEO'); ?>
<?php $__env->startPush('styles'); ?><link href="<?php echo e(asset('css/hub-monitoring.css')); ?>?v=2" rel="stylesheet"><?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-tags me-2"></i>Dampak Promo & Fee</h1>
        <p class="small mb-0"><?php echo e($promo['insight'] ?? ''); ?></p>
    </div>
    <?php echo $__env->make('hub.partials.ceo-nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('hub.partials.monitoring-filter', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <div class="hub-card">
        <div class="hub-card-body p-0">
            <table class="report-table">
                <thead>
                    <tr><th>Bulan</th><th class="num">Kotor</th><th class="num">Take rate</th><th class="num">Laba bersih</th></tr>
                </thead>
                <tbody>
                <?php $__currentLoopData = $promo['monthly'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($m['label']); ?></td>
                    <td class="num"><?php echo e(hub_rp($m['gross'])); ?></td>
                    <td class="num"><?php echo e(hub_pct($m['take_rate'])); ?></td>
                    <td class="num <?php echo e(($m['net_profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg'); ?>"><?php echo e(hub_rp($m['net_profit'], true)); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
    <p class="small text-muted mt-2">Program Hemat periode ini: <?php echo e(hub_rp($promo['program_hemat'] ?? 0)); ?> (<?php echo e(hub_pct($promo['program_hemat_pct'] ?? null)); ?> dari fee)</p>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views\hub\ceo\promo.blade.php ENDPATH**/ ?>