<?php $__env->startSection('title', 'Rekap CEO — Monitoring'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $s = $summary ?? [];
    $rek = $rekap ?? [];
    $months = $rek['months'] ?? [];
    $columns = $rek['columns'] ?? [];
    $metrics = $rek['metrics'] ?? [];
    $best = $rek['best_sellers'] ?? [];
?>
<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-table me-2"></i>Rekap CEO</h1>
        <div class="report-hero-meta">
            <span>Grid metrik <?php echo e(count($months)); ?> bulan — setara Excel HASIL/REKAP</span>
            <span><?php echo e($shop['label'] ?? ''); ?></span>
        </div>
    </div>

    <?php echo $__env->make('hub.partials.monitoring-nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <div class="mon-kpi-row">
        <div class="mon-kpi"><div class="label">AOV kotor (periode)</div><div class="value"><?php echo e(hub_rp($s['aov_gross'] ?? 0)); ?></div></div>
        <div class="mon-kpi"><div class="label">Basket size</div><div class="value"><?php echo e($s['basket_size'] ?? '—'); ?> item/order</div></div>
        <div class="mon-kpi"><div class="label">Gross margin</div><div class="value"><?php echo e(hub_pct($s['gross_margin_pct'] ?? null)); ?></div></div>
        <div class="mon-kpi"><div class="label">Net margin</div><div class="value"><?php echo e(hub_pct($s['net_margin_pct'] ?? null)); ?></div></div>
    </div>

    <div class="hub-card mb-3">
        <div class="hub-card-header">
            <h2 class="report-section-title">Rekap multi-bulan</h2>
            <p class="report-section-desc mb-0">Semua rasio sejajar per bulan — scroll horizontal di mobile</p>
        </div>
        <div class="hub-card-body p-0">
            <div class="rekap-grid-wrap">
                <table class="rekap-grid">
                    <thead>
                        <tr>
                            <th class="rekap-sticky">Metrik</th>
                            <?php $__currentLoopData = $months; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mk): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <th><?php echo e($columns[$mk]['short'] ?? $mk); ?></th>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $metrics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="rekap-sticky"><strong><?php echo e($m['label']); ?></strong></td>
                            <?php $__currentLoopData = $months; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mk): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php $val = $columns[$mk][$m['key']] ?? null; ?>
                            <td class="num">
                                <?php if($val === null): ?> —
                                <?php elseif($m['format'] === 'rp'): ?> <?php echo e(hub_rp($val)); ?>

                                <?php elseif($m['format'] === 'pct'): ?> <?php echo e(hub_pct($val)); ?>

                                <?php elseif($m['format'] === 'x'): ?> <?php echo e(is_numeric($val) ? number_format($val, 2).'x' : '—'); ?>

                                <?php else: ?> <?php echo e(is_numeric($val) ? number_format($val, 2) : $val); ?>

                                <?php endif; ?>
                            </td>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="hub-card">
        <div class="hub-card-header">
            <h2 class="report-section-title">Best seller per bulan</h2>
            <p class="report-section-desc mb-0">Top 8 SKU by qty — 3 bulan terakhir</p>
        </div>
        <div class="hub-card-body">
            <div class="best-seller-mom">
                <?php $__empty_1 = true; $__currentLoopData = $best; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mk => $period): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="best-seller-col">
                    <h3 class="h6"><?php echo e($period['label'] ?? $mk); ?></h3>
                    <ol class="best-seller-list">
                        <?php $__currentLoopData = $period['products'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li>
                            <span class="rank"><?php echo e($i + 1); ?></span>
                            <span class="name"><?php echo e(\Illuminate\Support\Str::limit($p['name'] ?? '—', 28)); ?></span>
                            <strong><?php echo e($p['qty'] ?? 0); ?></strong>
                        </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ol>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-muted mb-0">Belum ada data best seller.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views/hub/monitoring/rekap.blade.php ENDPATH**/ ?>