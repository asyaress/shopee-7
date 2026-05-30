<?php $__env->startSection('title', 'CEO Brief — Monitoring'); ?>

<?php $__env->startPush('styles'); ?>
<link href="<?php echo e(asset('css/hub-monitoring.css')); ?>?v=2" rel="stylesheet">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php
    $s = $summary ?? [];
    $ac = $action_center ?? [];
    $compare = $shop_compare ?? [];
?>

<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-briefcase me-2"></i>CEO Brief</h1>
        <div class="report-hero-meta">
            <span><?php echo e($meta['period_label'] ?? '—'); ?></span>
            <span>Toko aktif: <strong><?php echo e($shop['label'] ?? '—'); ?></strong></span>
        </div>
    </div>

    <?php echo $__env->make('hub.partials.monitoring-nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('hub.partials.monitoring-filter', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <div class="report-kpi-hero mb-3">
        <div class="report-kpi-card <?php echo e(($s['net_profit'] ?? 0) >= 0 ? 'positive' : 'negative'); ?>">
            <div class="label">Laba bersih (toko aktif)</div>
            <div class="value"><?php echo e(hub_rp($s['net_profit'] ?? 0, true)); ?></div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Margin</div>
            <div class="value"><?php echo e(hub_pct($s['margin'] ?? null)); ?></div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Iklan / ROAS</div>
            <div class="value"><?php echo e(hub_rp($s['ads_total'] ?? 0)); ?></div>
            <div class="sub"><?php echo e(isset($s['roas']) && $s['roas'] ? number_format($s['roas'], 2).'x' : '—'); ?></div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Urgent / Bleeder</div>
            <div class="value"><?php echo e($ac['counts']['urgent'] ?? 0); ?> / <?php echo e($ac['counts']['bleeders'] ?? 0); ?></div>
        </div>
    </div>

    <?php if(count($compare) > 1): ?>
    <div class="hub-card mb-3">
        <div class="hub-card-header"><h2 class="report-section-title">Bandingkan toko</h2></div>
        <div class="hub-card-body p-0">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Toko</th>
                        <th class="num">Laba bersih</th>
                        <th class="num">Margin</th>
                        <th class="num">Iklan</th>
                        <th class="num">Bleeder</th>
                        <th class="num">Star</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $compare; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><strong><?php echo e($row['shop_label']); ?></strong></td>
                        <td class="num <?php echo e(($row['net_profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg'); ?>"><?php echo e(hub_rp($row['net_profit'] ?? 0, true)); ?></td>
                        <td class="num"><?php echo e(hub_pct($row['margin'] ?? null)); ?></td>
                        <td class="num"><?php echo e(hub_rp($row['ads_total'] ?? 0)); ?></td>
                        <td class="num"><?php echo e($row['bleeders'] ?? 0); ?></td>
                        <td class="num"><?php echo e($row['stars'] ?? 0); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="<?php echo e(route('monitoring.actions', request()->query())); ?>" class="hub-btn hub-btn-primary">
            <i class="fas fa-bolt"></i> Pusat Aksi
        </a>
        <a href="<?php echo e(route('ceo.targets')); ?>" class="hub-btn hub-btn-outline">Target</a>
        <a href="<?php echo e(route('ceo.roas', request()->query())); ?>" class="hub-btn hub-btn-outline">ROAS Advisor</a>
        <a href="<?php echo e(route('ceo.settlement')); ?>" class="hub-btn hub-btn-outline">Arus kas</a>
        <a href="<?php echo e(route('ceo.export.journal', request()->query())); ?>" class="hub-btn hub-btn-outline">Export jurnal</a>
    </div>
    <form method="POST" action="<?php echo e(route('ceo.alerts.run')); ?>" class="d-inline">
        <?php echo csrf_field(); ?>
        <button type="submit" class="hub-btn hub-btn-sm hub-btn-outline">Jalankan cek alert</button>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views\hub\monitoring\executive.blade.php ENDPATH**/ ?>