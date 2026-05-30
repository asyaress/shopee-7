<?php
    $action = $item['action'] ?? [];
    $sev = $action['severity'] ?? 'info';
    $pid = $item['product_id'] ?? 0;
    $q = request()->query();
?>
<div class="mon-action-item mon-action-<?php echo e($sev); ?>">
    <div class="mon-action-head">
        <span class="sku-tier <?php echo e($item['tier'] ?? ''); ?>"><?php echo e(strtoupper($item['tier'] ?? '—')); ?></span>
        <a href="<?php echo e($pid ? route('monitoring.product', ['product' => $pid] + $q) : '#'); ?>" class="mon-action-name"><?php echo e($item['name'] ?? '—'); ?></a>
    </div>
    <div class="mon-action-title"><?php echo e($action['title'] ?? ''); ?></div>
    <p class="mon-action-summary small text-muted mb-1"><?php echo e($action['summary'] ?? ''); ?></p>
    <?php if(!empty($action['reasons'])): ?>
    <ul class="mon-action-reasons small mb-2">
        <?php $__currentLoopData = $action['reasons']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($r); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
    <?php endif; ?>
    <div class="small">
        Laba <?php echo e(hub_rp($item['net_profit'] ?? 0, true)); ?> · Iklan <?php echo e(hub_rp($item['ads_spend'] ?? 0)); ?>

        <?php if(isset($item['roas']) && $item['roas']): ?> · ROAS <?php echo e(number_format($item['roas'], 2)); ?>x <?php endif; ?>
        <?php if(!empty($item['pricing']['prices']['recommended_gross'])): ?>
        · Harga disarankan <?php echo e(hub_rp($item['pricing']['prices']['recommended_gross'])); ?>

        <?php endif; ?>
        <?php if(!empty($item['ads_metrics']['cpc'])): ?>
        · CPC <?php echo e(hub_rp($item['ads_metrics']['cpc'])); ?>

        <?php endif; ?>
    </div>
</div>
<?php /**PATH D:\A. SHOPEE-7\resources\views/hub/partials/action-item.blade.php ENDPATH**/ ?>