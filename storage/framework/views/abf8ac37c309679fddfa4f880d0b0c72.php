<?php $__env->startSection('title', 'ROAS Advisor — CEO'); ?>
<?php $__env->startPush('styles'); ?><link href="<?php echo e(asset('css/hub-monitoring.css')); ?>?v=2" rel="stylesheet"><?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
<?php $m = $roas['metrics'] ?? []; $rec = $roas['recommendation'] ?? []; ?>
<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-chart-line me-2"></i>ROAS Advisor</h1>
        <p class="small mb-0">Prediksi target ROAS impas & rekomendasi set di Shopee Ads</p>
    </div>
    <?php echo $__env->make('hub.partials.ceo-nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('hub.partials.monitoring-filter', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <div class="hub-card mb-3">
        <div class="hub-card-header"><h2 class="report-section-title">Dua definisi ROAS</h2></div>
        <div class="hub-card-body small">
            <p><strong>Shopee Ads:</strong> <?php echo e($roas['definitions']['shopee_ads'] ?? ''); ?></p>
            <p class="mb-0"><strong>Bisnis (app):</strong> <?php echo e($roas['definitions']['business'] ?? ''); ?></p>
        </div>
    </div>

    <div class="report-kpi-hero mb-3">
        <div class="report-kpi-card">
            <div class="label">ROAS bisnis (kotor÷iklan)</div>
            <div class="value"><?php echo e(isset($m['business_roas']) ? number_format($m['business_roas'], 2).'x' : '—'); ?></div>
        </div>
        <div class="report-kpi-card">
            <div class="label">ROAS Shopee (GMV÷iklan)</div>
            <div class="value"><?php echo e(isset($m['shopee_ads_roas']) ? number_format($m['shopee_ads_roas'], 2).'x' : '—'); ?></div>
        </div>
        <div class="report-kpi-card positive">
            <div class="label">Target ROAS (disarankan)</div>
            <div class="value"><?php echo e(isset($m['target_roas_gross']) ? number_format($m['target_roas_gross'], 2).'x' : '—'); ?></div>
            <div class="sub">Impas <?php echo e(isset($m['breakeven_roas_gross']) ? number_format($m['breakeven_roas_gross'], 2).'x' : '—'); ?></div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Target di Shopee Ads (GMV)</div>
            <div class="value"><?php echo e(isset($m['target_roas_shopee_gmv']) ? number_format($m['target_roas_shopee_gmv'], 2).'x' : '—'); ?></div>
        </div>
    </div>

    <div class="mon-decision-card mb-3">
        <h2 class="h5"><?php echo e($rec['title'] ?? 'Rekomendasi'); ?></h2>
        <ul class="mb-0">
            <?php $__currentLoopData = $rec['lines'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li><?php echo preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', e($line)); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>

    <div class="hub-card">
        <div class="hub-card-header"><h2 class="report-section-title">Per SKU — gap ke target ROAS</h2></div>
        <div class="hub-card-body p-0">
            <table class="report-table">
                <thead><tr><th>Produk</th><th class="num">ROAS sekarang</th><th class="num">Target</th><th class="num">Gap</th><th class="num">Laba</th></tr></thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $roas['products'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><a href="<?php echo e(route('monitoring.product', ['product' => $p['product_id']] + request()->query())); ?>"><?php echo e($p['name']); ?></a></td>
                    <td class="num"><?php echo e(number_format($p['current_roas'], 2)); ?>x</td>
                    <td class="num"><?php echo e($p['target_roas'] ? number_format($p['target_roas'], 2).'x' : '—'); ?></td>
                    <td class="num <?php echo e(($p['gap'] ?? 0) < 0 ? 'amt-neg' : 'amt-pos'); ?>"><?php echo e($p['gap'] !== null ? number_format($p['gap'], 2) : '—'); ?></td>
                    <td class="num"><?php echo e(hub_rp($p['net_profit'], true)); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="5" class="text-muted text-center py-3">Sync iklan dulu atau pilih periode dengan spend iklan.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views\hub\ceo\roas.blade.php ENDPATH**/ ?>