<?php $__env->startSection('title', 'Monitoring — Ringkasan'); ?>

<?php $__env->startPush('styles'); ?>
<link href="<?php echo e(asset('css/hub-monitoring.css')); ?>?v=1" rel="stylesheet">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php
    $s = $summary ?? [];
    $meta = $meta ?? [];
    $charts = $charts ?? [];
    $health = ($analysis ?? [])['health_score'] ?? 0;
?>

<div class="report-shell">
    <div class="report-hero">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h1><i class="fas fa-gauge-high me-2"></i>Monitoring Toko</h1>
                <div class="report-hero-meta">
                    <span><i class="far fa-calendar-alt"></i> <?php echo e($meta['period_label'] ?? '—'); ?></span>
                    <span><i class="far fa-clock"></i> <?php echo e($meta['days'] ?? 0); ?> hari</span>
                    <span><i class="fas fa-sync-alt"></i> <?php echo e($meta['generated_at'] ?? now()->format('d M Y H:i')); ?></span>
                </div>
            </div>
            <div class="report-health">
                <div class="report-health-ring" style="--score: <?php echo e($health); ?>"><span><?php echo e($health); ?></span></div>
                <div><strong>Skor kesehatan</strong><div class="small opacity-90">Data, margin & iklan</div></div>
            </div>
        </div>
    </div>

    <?php echo $__env->make('hub.partials.monitoring-nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('hub.partials.monitoring-filter', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <div class="mb-3">
        <a href="<?php echo e(route('monitoring.actions', request()->query())); ?>" class="hub-btn hub-btn-primary">
            <i class="fas fa-bolt"></i> Buka Pusat Aksi — rekomendasi hari ini
        </a>
    </div>

    <?php if(count($shop_compare ?? []) > 1): ?>
    <div class="hub-card mb-3">
        <div class="hub-card-header"><h2 class="report-section-title">Perbandingan toko</h2></div>
        <div class="hub-card-body p-0">
            <table class="report-table">
                <thead><tr><th>Toko</th><th class="num">Laba bersih</th><th class="num">Bleeder</th></tr></thead>
                <tbody>
                <?php $__currentLoopData = $shop_compare; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($row['shop_label']); ?></td>
                    <td class="num <?php echo e(($row['net_profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg'); ?>"><?php echo e(hub_rp($row['net_profit'] ?? 0, true)); ?></td>
                    <td class="num"><?php echo e($row['bleeders'] ?? 0); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="mon-section-cards">
        <a href="<?php echo e(route('monitoring.shopee', request()->query())); ?>" class="mon-section-card shopee">
            <div class="icon-wrap"><i class="fas fa-percent"></i></div>
            <h3>Potongan Shopee</h3>
            <div class="kpi"><?php echo e(hub_rp($s['fee_total'] ?? 0)); ?></div>
            <div class="sub">Take rate <?php echo e(hub_pct($s['take_rate'] ?? null)); ?> · Fee platform</div>
        </a>
        <a href="<?php echo e(route('monitoring.revenue', request()->query())); ?>" class="mon-section-card revenue">
            <div class="icon-wrap"><i class="fas fa-coins"></i></div>
            <h3>Pendapatan</h3>
            <div class="kpi"><?php echo e(hub_rp($s['gross'] ?? 0)); ?></div>
            <div class="sub">Net <?php echo e(hub_rp($s['net'] ?? 0)); ?> · <?php echo e(hub_num($s['orders_count'] ?? 0)); ?> pesanan</div>
        </a>
        <a href="<?php echo e(route('monitoring.ads', request()->query())); ?>" class="mon-section-card ads">
            <div class="icon-wrap"><i class="fas fa-bullhorn"></i></div>
            <h3>Iklan Shopee</h3>
            <div class="kpi"><?php echo e(hub_rp($s['ads_total'] ?? 0)); ?></div>
            <div class="sub">ROAS <?php echo e(isset($s['roas']) && $s['roas'] ? number_format($s['roas'], 2).'x' : '—'); ?> · ACOS <?php echo e(hub_pct($s['acos'] ?? null)); ?></div>
        </a>
        <a href="<?php echo e(route('monitoring.profit', request()->query())); ?>" class="mon-section-card profit">
            <div class="icon-wrap"><i class="fas fa-chart-pie"></i></div>
            <h3>Laba & Produk</h3>
            <div class="kpi <?php echo e(($s['net_profit'] ?? 0) >= 0 ? '' : 'amt-neg'); ?>"><?php echo e(hub_rp($s['net_profit'] ?? 0, true)); ?></div>
            <div class="sub">Laporan lengkap P&amp;L & tabel produk</div>
        </a>
    </div>

    <div class="mon-kpi-row">
        <div class="mon-kpi"><div class="label">Laba kotor</div><div class="value"><?php echo e(hub_rp($s['gross_profit'] ?? 0)); ?></div></div>
        <div class="mon-kpi"><div class="label">HPP + Pack</div><div class="value"><?php echo e(hub_rp($s['cogs'] ?? 0)); ?></div></div>
        <div class="mon-kpi"><div class="label">Operasional</div><div class="value"><?php echo e(hub_rp($s['operational_total'] ?? 0)); ?></div></div>
        <div class="mon-kpi"><div class="label">Margin bersih</div><div class="value"><?php echo e(hub_pct($s['margin'] ?? null)); ?></div></div>
    </div>

    <div class="fc-chart-stack">
        <?php echo $__env->make('hub.partials.chart-panel', ['id' => 'ovGrossNet', 'title' => 'Kotor vs net (bulanan)', 'subtitle' => 'Perbandingan penghasilan sebelum & sesudah fee platform', 'size' => 'hero', 'badge' => 'Shopee'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo $__env->make('hub.partials.chart-panel', ['id' => 'ovFeePie', 'title' => 'Komposisi fee', 'subtitle' => 'Administrasi, layanan, proses, program hemat', 'size' => 'square'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo $__env->make('hub.partials.chart-panel', ['id' => 'ovRevenue', 'title' => 'Tren pendapatan', 'subtitle' => 'Penjualan kotor & net per bulan', 'size' => 'hero'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo $__env->make('hub.partials.chart-panel', ['id' => 'ovAds', 'title' => 'Spend iklan', 'subtitle' => 'Agregat biaya promosi per bulan', 'size' => 'default', 'badge' => 'Ads'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sh = <?php echo json_encode($charts['shopee'] ?? [], 15, 512) ?>;
    const rev = <?php echo json_encode($charts['revenue'] ?? [], 15, 512) ?>;
    const ads = <?php echo json_encode($charts['ads'] ?? [], 15, 512) ?>;

    HubCharts.render('ovGrossNet', 'line', sh.gross_vs_net || {});
    HubCharts.render('ovFeePie', 'doughnut', sh.fee_doughnut || {});
    HubCharts.render('ovRevenue', 'line', rev.revenue_trend || {});
    HubCharts.render('ovAds', 'bar', { labels: (ads.ads_monthly || {}).labels, data: (ads.ads_monthly || {}).data, label: 'Iklan (Rp)' });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views/hub/monitoring/overview.blade.php ENDPATH**/ ?>