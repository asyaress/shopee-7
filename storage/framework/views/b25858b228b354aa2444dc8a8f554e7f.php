<?php $__env->startSection('title', 'Monitoring — Pendapatan'); ?>

<?php $__env->startPush('styles'); ?>
<link href="<?php echo e(asset('css/hub-monitoring.css')); ?>?v=1" rel="stylesheet">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php
    $s = $summary ?? [];
    $meta = $meta ?? [];
    $charts = $charts ?? [];
?>

<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-coins me-2"></i>Pendapatan & Penjualan</h1>
        <div class="report-hero-meta">
            <span><i class="far fa-calendar-alt"></i> <?php echo e($meta['period_label'] ?? '—'); ?></span>
            <span><?php echo e(hub_num($s['orders_count'] ?? 0)); ?> pesanan</span>
            <span>Rata-rata/order <?php echo e(hub_rp($s['avg_order_net'] ?? 0)); ?></span>
        </div>
    </div>

    <?php echo $__env->make('hub.partials.monitoring-nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('hub.partials.monitoring-filter', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <div class="mon-kpi-row">
        <div class="mon-kpi"><div class="label">Penjualan kotor</div><div class="value"><?php echo e(hub_rp($s['gross'] ?? 0)); ?></div></div>
        <div class="mon-kpi"><div class="label">Net penghasilan</div><div class="value"><?php echo e(hub_rp($s['net'] ?? 0)); ?></div></div>
        <div class="mon-kpi"><div class="label">Laba kotor</div><div class="value"><?php echo e(hub_rp($s['gross_profit'] ?? 0)); ?></div></div>
        <div class="mon-kpi"><div class="label">Laba bersih</div><div class="value <?php echo e(($s['net_profit'] ?? 0) >= 0 ? '' : 'amt-neg'); ?>"><?php echo e(hub_rp($s['net_profit'] ?? 0, true)); ?></div></div>
    </div>

    <div class="fc-chart-stack">
        <?php echo $__env->make('hub.partials.chart-panel', ['id' => 'chRevenue', 'title' => 'Tren pendapatan', 'subtitle' => 'Penjualan kotor vs net penghasilan per bulan', 'size' => 'hero'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo $__env->make('hub.partials.chart-panel', ['id' => 'chProfitStack', 'title' => 'Laba kotor vs bersih', 'subtitle' => 'Setelah HPP, iklan & biaya operasional', 'size' => 'hero'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo $__env->make('hub.partials.chart-panel', ['id' => 'chOrders', 'title' => 'Volume pesanan', 'subtitle' => 'Jumlah order terkonfirmasi per bulan', 'size' => 'compact'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo $__env->make('hub.partials.chart-panel', ['id' => 'chSummaryBar', 'title' => 'Perbandingan periode', 'subtitle' => 'Ringkasan alur nilai agregat', 'size' => 'default'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>

    <?php if(!empty($monthly)): ?>
    <div class="hub-card mt-3">
        <div class="hub-card-header"><h2 class="report-section-title">Rekap bulanan</h2></div>
        <div class="hub-card-body p-0">
            <div class="hub-table-wrap">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th class="num">Pesanan</th>
                            <th class="num">Kotor</th>
                            <th class="num">Net</th>
                            <th class="num">Laba bersih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $monthly; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><strong><?php echo e($m['label']); ?></strong></td>
                            <td class="num"><?php echo e($m['orders'] ?? 0); ?></td>
                            <td class="num"><?php echo e(hub_rp($m['gross'] ?? 0)); ?></td>
                            <td class="num"><?php echo e(hub_rp($m['net'] ?? 0)); ?></td>
                            <td class="num <?php echo e(($m['net_profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg'); ?>"><?php echo e(hub_rp($m['net_profit'] ?? 0, true)); ?></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const c = <?php echo json_encode($charts, 15, 512) ?>;
    HubCharts.render('chRevenue', 'line', c.revenue_trend || {});
    HubCharts.render('chOrders', 'bar', { labels: (c.orders_bar || {}).labels, data: (c.orders_bar || {}).data, label: 'Pesanan' });
    HubCharts.render('chProfitStack', 'line', c.profit_stack || {});
    HubCharts.render('chSummaryBar', 'bar', { labels: (c.summary_compare || {}).labels, data: (c.summary_compare || {}).data, label: 'Nilai (Rp)' });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views\hub\monitoring\revenue.blade.php ENDPATH**/ ?>