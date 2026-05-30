<?php $__env->startSection('title', 'Pusat Aksi — Monitoring'); ?>

<?php $__env->startPush('styles'); ?>
<link href="<?php echo e(asset('css/hub-monitoring.css')); ?>?v=2" rel="stylesheet">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php
    $ac = $action_center ?? [];
    $hpp = $ac['hpp_quality'] ?? [];
    $cash = $ac['cash_guard'] ?? [];
    $shop = $shop ?? [];
?>

<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-bolt me-2"></i>Pusat Aksi</h1>
        <div class="report-hero-meta">
            <span><i class="fas fa-store"></i> <?php echo e($shop['label'] ?? $activeShopeeShopLabel ?? 'Toko'); ?></span>
            <span><i class="far fa-calendar-alt"></i> <?php echo e($meta['period_label'] ?? '—'); ?></span>
            <span>Kelengkapan HPP: <strong><?php echo e($hpp['complete_pct_label'] ?? '—'); ?></strong></span>
        </div>
    </div>

    <?php echo $__env->make('hub.partials.monitoring-nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('hub.partials.monitoring-filter', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <?php if(!($hpp['recommendations_allowed'] ?? true)): ?>
    <div class="hub-alert" style="background:#fef3c7;border-color:#fcd34d;color:#92400e;">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Data HPP belum cukup.</strong> Lengkapi minimal 70% SKU sebelum mengikuti rekomendasi iklan/harga.
        <a href="<?php echo e(route('hpp.index', ['fill' => 'missing'])); ?>" class="ms-2">Perbaiki HPP →</a>
    </div>
    <?php endif; ?>

  <div class="mon-kpi-row">
        <div class="mon-kpi"><div class="label">Prioritas urgent</div><div class="value"><?php echo e($ac['counts']['urgent'] ?? 0); ?></div></div>
        <div class="mon-kpi"><div class="label">Peluang scale</div><div class="value"><?php echo e($ac['counts']['opportunities'] ?? 0); ?></div></div>
        <div class="mon-kpi"><div class="label">SKU bleeder</div><div class="value text-danger"><?php echo e($ac['counts']['bleeders'] ?? 0); ?></div></div>
        <div class="mon-kpi"><div class="label">Pace iklan aman/minggu</div><div class="value"><?php echo e(hub_rp($cash['safe_weekly_ads_suggest'] ?? 0)); ?></div></div>
    </div>

    <?php if(!empty($ac['data_blockers'])): ?>
    <div class="hub-card mb-3">
        <div class="hub-card-header"><h2 class="report-section-title">Blocker data</h2></div>
        <div class="hub-card-body">
            <?php $__currentLoopData = $ac['data_blockers']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="report-insight <?php echo e($b['type']); ?> mb-2">
                <div><strong><?php echo e($b['title']); ?></strong><p class="mb-0 small"><?php echo e($b['text']); ?></p></div>
                <?php if(!empty($b['route'])): ?>
                <a href="<?php echo e(route($b['route'])); ?>" class="hub-btn hub-btn-sm hub-btn-outline">Perbaiki</a>
                <?php endif; ?>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="hub-card mb-3">
        <div class="hub-card-header">
            <h2 class="report-section-title">Cash guard</h2>
            <p class="report-section-desc mb-0"><?php echo e($cash['message'] ?? ''); ?></p>
        </div>
        <div class="hub-card-body small">
            Net masuk periode: <strong><?php echo e(hub_rp($cash['net_income_period'] ?? 0)); ?></strong> ·
            Spend iklan (<?php echo e($cash['period_weeks'] ?? 4); ?> minggu): <strong><?php echo e(hub_rp($cash['ads_spend_period'] ?? 0)); ?></strong> ·
            Laba bersih: <strong class="<?php echo e(($cash['net_profit_period'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg'); ?>"><?php echo e(hub_rp($cash['net_profit_period'] ?? 0, true)); ?></strong>
            <?php if(($cash['budget_monthly'] ?? 0) > 0): ?>
            · Budget iklan: <?php echo e(hub_pct($cash['budget_used_pct'] ?? null)); ?> terpakai
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="hub-card h-100">
                <div class="hub-card-header"><h2 class="report-section-title">Tindakan urgent</h2></div>
                <div class="hub-card-body">
                    <?php $__empty_1 = true; $__currentLoopData = $ac['urgent'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php echo $__env->make('hub.partials.action-item', ['item' => $item], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="text-muted mb-0">Tidak ada item urgent — bagus!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="hub-card h-100">
                <div class="hub-card-header"><h2 class="report-section-title">Peluang (scale iklan)</h2></div>
                <div class="hub-card-body">
                    <?php $__empty_1 = true; $__currentLoopData = $ac['opportunities'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php echo $__env->make('hub.partials.action-item', ['item' => $item], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="text-muted mb-0">Belum ada peluang terdeteksi pada periode ini.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views/hub/monitoring/actions.blade.php ENDPATH**/ ?>