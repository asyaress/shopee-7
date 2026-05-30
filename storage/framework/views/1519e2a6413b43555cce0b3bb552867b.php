<?php $__env->startSection('title', 'Target Bulanan — CEO'); ?>
<?php $__env->startPush('styles'); ?><link href="<?php echo e(asset('css/hub-monitoring.css')); ?>?v=2" rel="stylesheet"><?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
<?php $p = $progress ?? []; $pace = $pace ?? []; ?>
<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-bullseye me-2"></i>Target vs Actual</h1>
        <div class="report-hero-meta"><span><?php echo e($shop['label'] ?? ''); ?></span><span>Bulan <?php echo e($year_month); ?></span></div>
    </div>
    <?php echo $__env->make('hub.partials.ceo-nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <div class="hub-card mb-3">
        <div class="hub-card-body">
            <form method="POST" action="<?php echo e(route('ceo.targets.save')); ?>" class="hub-filter-bar">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="year_month" value="<?php echo e($year_month); ?>">
                <div class="filter-item"><label class="hub-form-label">Target laba bersih</label>
                    <input type="number" name="target_net_profit" class="hub-form-control" value="<?php echo e($targets['net_profit'] ?? ''); ?>"></div>
                <div class="filter-item"><label class="hub-form-label">Target penjualan kotor</label>
                    <input type="number" name="target_gross" class="hub-form-control" value="<?php echo e($targets['gross'] ?? ''); ?>"></div>
                <div class="filter-item"><label class="hub-form-label">Target unit terjual</label>
                    <input type="number" name="target_units" class="hub-form-control" value="<?php echo e($targets['units'] ?? ''); ?>"></div>
                <div class="filter-item"><label class="hub-form-label">Budget iklan bulanan</label>
                    <input type="number" name="ad_budget_cap" class="hub-form-control" value="<?php echo e($targets['ad_budget'] ?? ''); ?>"></div>
                <div class="filter-item" style="align-self:flex-end"><button type="submit" class="hub-btn hub-btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
    <div class="report-kpi-hero">
        <div class="report-kpi-card <?php echo e(($pace['on_track_net'] ?? false) ? 'positive' : 'warn'); ?>">
            <div class="label">Laba bersih</div>
            <div class="value"><?php echo e(hub_rp($actual['net_profit'] ?? 0, true)); ?></div>
            <div class="sub">Target <?php echo e(hub_rp($targets['net_profit'] ?? 0)); ?> · <?php echo e(isset($p['net_pct']) ? number_format($p['net_pct']*100,1).'%' : '—'); ?></div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Pace (hari <?php echo e($pace['day'] ?? 0); ?>/<?php echo e($pace['days_in_month'] ?? 30); ?>)</div>
            <div class="value"><?php echo e(hub_rp($pace['expected_net_by_today'] ?? 0)); ?></div>
            <div class="sub">Seharusnya sudah mencapai</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Unit terjual / target</div>
            <div class="value"><?php echo e(number_format($actual['units'] ?? 0)); ?></div>
            <div class="sub"><?php echo e(isset($p['units_pct']) && ($targets['units'] ?? 0) > 0 ? number_format($p['units_pct']*100,1).'% target' : 'Set di form atau BCG'); ?></div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Iklan / budget</div>
            <div class="value"><?php echo e(hub_rp($actual['ads_total'] ?? 0)); ?></div>
            <div class="sub"><?php echo e(isset($p['ads_pct']) && ($targets['ad_budget'] ?? 0) > 0 ? number_format($p['ads_pct']*100,1).'% budget' : '—'); ?></div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views\hub\ceo\targets.blade.php ENDPATH**/ ?>