<?php $__currentLoopData = $products ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<?php
    $profit = (int) ($p['net_profit'] ?? 0);
    $searchKey = strtolower(($p['name'] ?? '') . ' ' . ($p['sku'] ?? ''));
?>
<article class="hub-product-card-v2 <?php echo e($profit < 0 ? 'is-negative' : ''); ?>" data-search="<?php echo e($searchKey); ?>">
    <div class="card-head">
        <div>
            <?php if(!empty($p['product_id'])): ?>
            <a href="<?php echo e(route('monitoring.product', ['product' => $p['product_id']] + request()->query())); ?>" class="name"><?php echo e($p['name']); ?></a>
            <?php else: ?>
            <span class="name"><?php echo e($p['name']); ?></span>
            <?php endif; ?>
            <?php echo $__env->make('hub.partials.product-shopee-links', ['links' => $p['links'] ?? []], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
            <div class="mt-1">
                <?php if(!empty($p['sku'])): ?><span class="sku text-muted small"><?php echo e($p['sku']); ?></span><?php endif; ?>
                <span class="sku-tier <?php echo e($p['tier'] ?? ''); ?> ms-1"><?php echo e($p['tier'] ?? '—'); ?></span>
            </div>
        </div>
        <div class="card-profit <?php echo e($profit >= 0 ? 'amt-pos' : 'amt-neg'); ?>"><?php echo e(hub_rp($profit, true)); ?></div>
    </div>
    <div class="card-grid">
        <div><span>Qty</span><strong><?php echo e(hub_num($p['qty'] ?? 0)); ?></strong></div>
        <div><span>Margin</span><strong><?php echo e(hub_pct($p['margin'] ?? null)); ?></strong></div>
        <div><span>Net</span><strong><?php echo e(hub_rp($p['net'] ?? 0)); ?></strong></div>
        <div><span>Iklan</span><strong><?php echo e(hub_rp($p['ads_spend'] ?? 0)); ?></strong></div>
        <div><span>ROAS</span><strong><?php echo e(isset($p['roas']) && $p['roas'] ? number_format($p['roas'], 2).'x' : '—'); ?></strong></div>
        <div><span>HPP</span><strong><?php echo e(hub_rp($p['cogs'] ?? 0)); ?></strong></div>
    </div>
    <?php if(!empty($p['action']['title'])): ?>
    <p class="small text-muted mb-0 mt-2"><?php echo e($p['action']['title']); ?></p>
    <?php endif; ?>
</article>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH D:\A. SHOPEE-7\resources\views/hub/partials/product-mobile-cards.blade.php ENDPATH**/ ?>