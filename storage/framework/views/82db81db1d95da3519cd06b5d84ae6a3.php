<?php $__env->startSection('title', ($sku['name'] ?? 'Produk') . ' — Keputusan SKU'); ?>

<?php $__env->startPush('styles'); ?>
<link href="<?php echo e(asset('css/hub-monitoring.css')); ?>?v=2" rel="stylesheet">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php
    $action = $sku['action'] ?? [];
    $q = request()->query();
?>
<div class="report-shell">
    <div class="report-hero">
        <div class="d-flex justify-content-between flex-wrap gap-2">
            <div>
                <h1 class="h4 mb-1"><?php echo e($sku['name'] ?? $product->name); ?></h1>
                <div class="report-hero-meta">
                    <span class="sku-tier <?php echo e($sku['tier'] ?? ''); ?>"><?php echo e(strtoupper($sku['tier'] ?? '—')); ?></span>
                    <span>SKU <?php echo e($sku['sku'] ?? '—'); ?></span>
                    <span><?php echo e($shop['label'] ?? ''); ?></span>
                    <?php echo $__env->make('hub.partials.product-shopee-links', ['links' => $sku['links'] ?? []], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </div>
            </div>
            <a href="<?php echo e(route('monitoring.profit', $q)); ?>" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <?php echo $__env->make('hub.partials.monitoring-filter', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <div class="mon-decision-card mon-action-<?php echo e($action['severity'] ?? 'info'); ?> mb-3">
        <h2 class="h5 mb-2"><i class="fas fa-lightbulb me-2"></i><?php echo e($action['title'] ?? 'Rekomendasi utama'); ?></h2>
        <p><?php echo e($action['summary'] ?? ''); ?></p>
        <?php if(!empty($action['reasons'])): ?>
        <ul class="mb-0">
            <?php $__currentLoopData = $action['reasons']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($r); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
        <?php endif; ?>
        <?php if(!empty($action['meta']['recommended_price'])): ?>
        <p class="mt-2 mb-0"><strong>Harga jual disarankan:</strong> <?php echo e(hub_rp($action['meta']['recommended_price'])); ?> / unit (kotor)</p>
        <?php endif; ?>
        <?php if(!empty($action['route'])): ?>
        <a href="<?php echo e(route($action['route'])); ?>" class="hub-btn hub-btn-primary mt-3">Buka Input HPP</a>
        <?php endif; ?>
    </div>

    <?php echo $__env->make('hub.partials.product-recommendations', ['row' => $sku], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <div class="mon-kpi-row mb-3">
        <div class="mon-kpi"><div class="label">Laba bersih</div><div class="value <?php echo e(($sku['net_profit'] ?? 0) >= 0 ? '' : 'amt-neg'); ?>"><?php echo e(hub_rp($sku['net_profit'] ?? 0, true)); ?></div></div>
        <div class="mon-kpi"><div class="label">Margin</div><div class="value"><?php echo e(hub_pct($sku['margin'] ?? null)); ?></div></div>
        <div class="mon-kpi"><div class="label">Spend iklan</div><div class="value"><?php echo e(hub_rp($sku['ads_spend'] ?? 0)); ?></div></div>
        <div class="mon-kpi"><div class="label">ROAS</div><div class="value"><?php echo e(isset($sku['roas']) && $sku['roas'] ? number_format($sku['roas'], 2).'x' : '—'); ?></div></div>
    </div>

    <div class="hub-card mb-3">
        <div class="hub-card-header"><h2 class="report-section-title">Catat keputusan</h2></div>
        <div class="hub-card-body">
            <form method="POST" action="<?php echo e(route('ceo.decisions.store')); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="product_id" value="<?php echo e($product->id); ?>">
                <input type="hidden" name="decision_type" value="<?php echo e($action['code'] ?? 'other'); ?>">
                <input type="hidden" name="title" value="<?php echo e($action['title'] ?? 'Keputusan SKU'); ?>">
                <textarea name="note" class="hub-form-control mb-2" rows="2" placeholder="Apa yang Anda lakukan di Shopee? (contoh: potong iklan 50%)"></textarea>
                <button type="submit" class="hub-btn hub-btn-primary btn-sm">Simpan ke log CEO</button>
            </form>
        </div>
    </div>

    <div class="hub-card">
        <div class="hub-card-header"><h2 class="report-section-title">Simulasi cepat</h2></div>
        <div class="hub-card-body">
            <p class="small text-muted">Estimasi sederhana — asumsi volume penjualan tetap.</p>
            <div class="row g-3">
                <?php $__currentLoopData = $simulations ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $sim): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="col-md-6">
                    <div class="mon-sim-box">
                        <strong><?php echo e($sim['label'] ?? $key); ?></strong>
                        <div>Laba bersih → <span class="<?php echo e(($sim['net_profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg'); ?>"><?php echo e(hub_rp($sim['net_profit'] ?? 0, true)); ?></span></div>
                        <div class="small">Margin <?php echo e(hub_pct($sim['margin'] ?? null)); ?></div>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views\hub\monitoring\product.blade.php ENDPATH**/ ?>