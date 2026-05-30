<?php
    $pricing = $row['pricing'] ?? null;
    $adsM = $row['ads_metrics'] ?? null;
    $adsRec = $row['ads_rec'] ?? null;
    $pr = $pricing['recommendation'] ?? [];
?>
<?php if($pricing || $adsM): ?>
<div class="mon-rec-block mb-3">
    <?php if($pricing): ?>
    <div class="mon-rec-section">
        <h4><i class="fas fa-tag me-1"></i> Harga jual — <?php echo e($pricing['status_label'] ?? ''); ?></h4>
        <p class="mb-1 fw-semibold"><?php echo e($pr['title'] ?? ''); ?></p>
        <?php $__currentLoopData = $pr['lines'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <p class="small text-muted mb-1"><?php echo preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', e($line)); ?></p>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <div class="mon-rec-metrics small">
            <span>Rata-rata jual: <strong><?php echo e(hub_rp($pricing['prices']['avg_selling'] ?? 0)); ?></strong></span>
            <span>Impas: <strong><?php echo e(hub_rp($pricing['prices']['breakeven_gross'] ?? 0)); ?></strong></span>
            <span>Disarankan: <strong><?php echo e(hub_rp($pricing['prices']['recommended_gross'] ?? 0)); ?></strong></span>
        </div>
        <div class="mon-rec-metrics small text-muted">
            Per unit → HPP <?php echo e(hub_rp($pricing['per_unit']['cogs'] ?? 0)); ?>

            + iklan <?php echo e(hub_rp($pricing['per_unit']['ads'] ?? 0)); ?>

            + ops <?php echo e(hub_rp($pricing['per_unit']['operational'] ?? 0)); ?>

            = <?php echo e(hub_rp($pricing['per_unit']['total_cost'] ?? 0)); ?>

        </div>
    </div>
    <?php endif; ?>
    <?php if($adsM): ?>
    <div class="mon-rec-section">
        <h4><i class="fas fa-bullhorn me-1"></i> Iklan (data aktual)</h4>
        <div class="mon-rec-metrics small">
            <span>Spend <?php echo e(hub_rp($adsM['spend'] ?? 0)); ?></span>
            <?php if($adsM['cpc'] ?? null): ?><span>CPC <?php echo e(hub_rp($adsM['cpc'])); ?></span><?php endif; ?>
            <?php if($adsM['ctr'] ?? null): ?><span>CTR <?php echo e($adsM['ctr']); ?>%</span><?php endif; ?>
            <?php if($adsM['business_roas'] ?? null): ?><span>ROAS bisnis <?php echo e($adsM['business_roas']); ?>x</span><?php endif; ?>
            <?php if($adsM['target_roas'] ?? null): ?><span>Target <?php echo e($adsM['target_roas']); ?>x</span><?php endif; ?>
            <?php if($adsM['shopee_roas'] ?? null): ?><span>ROAS GMV <?php echo e($adsM['shopee_roas']); ?>x</span><?php endif; ?>
        </div>
        <?php if($adsRec): ?>
        <p class="small mb-0 mt-2 mon-action-<?php echo e($adsRec['severity'] ?? 'info'); ?>"><strong><?php echo e($adsRec['title']); ?></strong>
            <?php $__currentLoopData = $adsRec['lines'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> — <?php echo e($l); ?> <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php /**PATH D:\A. SHOPEE-7\resources\views\hub\partials\product-recommendations.blade.php ENDPATH**/ ?>