<?php $__env->startSection('title', 'Monitoring — Potongan Shopee'); ?>

<?php $__env->startPush('styles'); ?>
<link href="<?php echo e(asset('css/hub-monitoring.css')); ?>?v=1" rel="stylesheet">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php
    $s = $summary ?? [];
    $meta = $meta ?? [];
    $fb = $fee_breakdown ?? [];
    $fbPct = $fee_breakdown_pct ?? [];
    $charts = $charts ?? [];
?>

<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-percent me-2"></i>Potongan & Fee Shopee</h1>
        <div class="report-hero-meta">
            <span><i class="far fa-calendar-alt"></i> <?php echo e($meta['period_label'] ?? '—'); ?></span>
            <span>Total fee <strong><?php echo e(hub_rp($s['fee_total'] ?? 0)); ?></strong></span>
            <span>Take rate <strong><?php echo e(hub_pct($s['take_rate'] ?? null)); ?></strong></span>
        </div>
    </div>

    <?php echo $__env->make('hub.partials.monitoring-nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('hub.partials.monitoring-filter', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <div class="mon-kpi-row">
        <div class="mon-kpi"><div class="label">Pendapatan kotor</div><div class="value"><?php echo e(hub_rp($s['gross'] ?? 0)); ?></div></div>
        <div class="mon-kpi"><div class="label">Setelah fee (net)</div><div class="value"><?php echo e(hub_rp($s['net'] ?? 0)); ?></div></div>
        <div class="mon-kpi"><div class="label">Total potongan</div><div class="value amt-neg"><?php echo e(hub_rp($s['fee_total'] ?? 0)); ?></div></div>
        <div class="mon-kpi"><div class="label">Take rate</div><div class="value"><?php echo e(hub_pct($s['take_rate'] ?? null)); ?></div></div>
    </div>

    <div class="fc-chart-stack">
        <?php echo $__env->make('hub.partials.chart-panel', ['id' => 'chGrossNet', 'title' => 'Kotor vs net', 'subtitle' => 'Tren penghasilan sebelum & sesudah fee Shopee', 'size' => 'hero'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo $__env->make('hub.partials.chart-panel', ['id' => 'chFeeMonthly', 'title' => 'Biaya platform per bulan', 'subtitle' => 'Selisih pendapatan kotor − net', 'size' => 'default'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo $__env->make('hub.partials.chart-panel', ['id' => 'chFeePie', 'title' => 'Komposisi fee platform', 'subtitle' => 'Breakdown administrasi, layanan, proses', 'size' => 'square'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo $__env->make('hub.partials.chart-panel', ['id' => 'chTakeRate', 'title' => 'Take rate (%)', 'subtitle' => 'Persentase fee dari penjualan kotor', 'size' => 'compact'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>

    <div class="hub-card mt-3">
        <div class="hub-card-header"><h2 class="report-section-title">Detail komponen fee</h2></div>
        <div class="hub-card-body">
            <?php $feeLabels = \App\Services\Finance\ShopeeFinancialExtractor::feeLabels(); ?>
            <?php $__currentLoopData = $feeLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if(($fb[$key] ?? 0) != 0): ?>
            <div class="fee-bar-row">
                <div class="fee-label">
                    <span><?php echo e($label); ?></span>
                    <strong><?php echo e(hub_rp($fb[$key] ?? 0)); ?> · <?php echo e(hub_pct($fbPct[$key] ?? 0)); ?></strong>
                </div>
                <div class="fee-bar-track">
                    <div class="fee-bar-fill" style="width: <?php echo e(min(100, ($fbPct[$key] ?? 0) * 100)); ?>%"></div>
                </div>
            </div>
            <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const c = <?php echo json_encode($charts, 15, 512) ?>;
    HubCharts.render('chFeePie', 'doughnut', c.fee_doughnut || {});
    HubCharts.render('chFeeMonthly', 'bar', c.fee_monthly || {});
    HubCharts.render('chTakeRate', 'line', {
        labels: (c.take_rate || {}).labels,
        datasets: [{ label: 'Take rate %', data: (c.take_rate || {}).data }]
    });
    HubCharts.render('chGrossNet', 'line', c.gross_vs_net || {});
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views/hub/monitoring/shopee.blade.php ENDPATH**/ ?>