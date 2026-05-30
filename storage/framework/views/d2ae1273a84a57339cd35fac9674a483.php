<?php $__env->startSection('title', 'Monitoring — Iklan Shopee'); ?>

<?php $__env->startPush('styles'); ?>
<link href="<?php echo e(asset('css/hub-monitoring.css')); ?>?v=1" rel="stylesheet">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php
    $s = $summary ?? [];
    $meta = $meta ?? [];
    $charts = $charts ?? [];
    $roas = $charts['roas_acos'] ?? [];
?>

<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-bullhorn me-2"></i>Iklan Shopee Ads</h1>
        <div class="report-hero-meta">
            <span><i class="far fa-calendar-alt"></i> <?php echo e($meta['period_label'] ?? '—'); ?></span>
            <span>Spend <?php echo e(hub_rp($s['ads_total'] ?? 0)); ?></span>
            <span>ROAS <?php echo e(isset($s['roas']) && $s['roas'] ? number_format($s['roas'], 2).'x' : '—'); ?></span>
            <span>ACOS <?php echo e(hub_pct($s['acos'] ?? null)); ?></span>
        </div>
    </div>

    <?php echo $__env->make('hub.partials.monitoring-nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('hub.partials.monitoring-filter', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <?php $adsShop = ($recommendations ?? [])['ads_shop'] ?? []; ?>
    <div class="mon-kpi-row">
        <div class="mon-kpi"><div class="label">Total spend</div><div class="value"><?php echo e(hub_rp($s['ads_total'] ?? 0)); ?></div></div>
        <div class="mon-kpi"><div class="label">ROAS bisnis</div><div class="value"><?php echo e(isset($s['roas']) && $s['roas'] ? number_format($s['roas'], 2).'x' : '—'); ?></div></div>
        <div class="mon-kpi"><div class="label">CPC toko</div><div class="value"><?php echo e(isset($adsShop['cpc_shop']) ? hub_rp($adsShop['cpc_shop']) : '—'); ?></div></div>
        <div class="mon-kpi"><div class="label">Budget bulanan</div><div class="value">
            <?php if(($adsShop['budget_monthly'] ?? 0) > 0): ?>
                <?php echo e(hub_pct($adsShop['budget_used_pct'] ?? null)); ?>

                <div class="small text-muted">sisa <?php echo e(hub_rp($adsShop['budget_remaining'] ?? 0)); ?></div>
            <?php else: ?>
                <a href="<?php echo e(route('ceo.targets')); ?>" class="small">Set budget →</a>
            <?php endif; ?>
        </div></div>
    </div>

    <?php if(!empty($adsShop['recommendation']['lines'])): ?>
    <div class="mon-decision-card mon-action-<?php echo e($adsShop['recommendation']['severity'] ?? 'info'); ?> mb-3">
        <h2 class="h6 mb-2"><?php echo e($adsShop['recommendation']['title'] ?? 'Rekomendasi iklan'); ?></h2>
        <ul class="small mb-0">
            <?php $__currentLoopData = $adsShop['recommendation']['lines']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $line): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li><?php echo preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', e($line)); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
        <?php if($adsShop['target_roas_business'] ?? null): ?>
        <p class="small mt-2 mb-0">Target ROAS dari data aktual: <strong><?php echo e($adsShop['target_roas_business']); ?>x</strong>
            <?php if($adsShop['shopee_roas_gmv'] ?? null): ?> · ROAS GMV Shopee: <?php echo e($adsShop['shopee_roas_gmv']); ?>x <?php endif; ?>
        </p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="fc-chart-stack">
        <?php echo $__env->make('hub.partials.chart-panel', ['id' => 'chAdsDaily', 'title' => 'Spend & GMV harian', 'subtitle' => 'Biaya iklan dan GMV atribusi per hari', 'size' => 'hero', 'badge' => 'Live'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo $__env->make('hub.partials.chart-panel', ['id' => 'chAdsMonthly', 'title' => 'Spend per bulan', 'subtitle' => 'Agregat biaya promosi bulanan', 'size' => 'default'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo $__env->make('hub.partials.chart-panel', ['id' => 'chTopSpend', 'title' => 'Top produk by spend', 'subtitle' => '8 SKU dengan biaya iklan tertinggi', 'size' => 'default'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo $__env->make('hub.partials.chart-panel', ['id' => 'chCtr', 'title' => 'CTR harian (%)', 'subtitle' => 'Rasio klik terhadap impression', 'size' => 'compact'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>

    <?php
        $adsProducts = collect($products ?? [])->sortByDesc('ads_spend')->take(15);
    ?>
    <?php if($adsProducts->isNotEmpty()): ?>
    <div class="hub-card mt-3">
        <div class="hub-card-header"><h2 class="report-section-title">Performa iklan per produk</h2></div>
        <div class="hub-card-body p-0">
            <div class="hub-table-wrap">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="num">Spend</th>
                            <th class="num">CPC</th>
                            <th class="num">ROAS</th>
                            <th class="num">Target</th>
                            <th class="num">Harga</th>
                            <th class="num">Laba</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $adsProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $am = $p['ads_metrics'] ?? [];
                            $pr = $p['pricing'] ?? [];
                            $ps = $pr['status'] ?? '';
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo e(route('monitoring.product', ['product' => $p['product_id']] + request()->query())); ?>"><?php echo e($p['name'] ?? '—'); ?></a>
                                <?php if($ps): ?><br><span class="price-status-<?php echo e($ps === 'ok' ? 'ok' : ($ps === 'too_low' || $ps === 'not_covering' ? 'low' : 'review')); ?>"><?php echo e($pr['status_label'] ?? ''); ?></span><?php endif; ?>
                            </td>
                            <td class="num"><?php echo e(hub_rp($p['ads_spend'] ?? 0)); ?></td>
                            <td class="num"><?php echo e(isset($am['cpc']) ? hub_rp($am['cpc']) : '—'); ?></td>
                            <td class="num"><?php echo e(isset($p['roas']) && $p['roas'] ? number_format($p['roas'], 2).'x' : '—'); ?></td>
                            <td class="num"><?php echo e(isset($am['target_roas']) ? ($am['target_roas'].'x') : '—'); ?></td>
                            <td class="num"><?php echo e(hub_rp($pr['prices']['recommended_gross'] ?? $pr['prices']['avg_selling'] ?? 0)); ?></td>
                            <td class="num <?php echo e(($p['net_profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg'); ?>"><?php echo e(hub_rp($p['net_profit'] ?? 0, true)); ?></td>
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
    HubCharts.render('chAdsDaily', 'line', c.ads_daily || {});
    HubCharts.render('chAdsMonthly', 'bar', { labels: (c.ads_monthly || {}).labels, data: (c.ads_monthly || {}).data, label: 'Spend (Rp)' });
    HubCharts.render('chTopSpend', 'bar_horizontal', c.top_spend || {});
    HubCharts.render('chCtr', 'line', {
        labels: (c.ctr_daily || {}).labels,
        datasets: [{ label: 'CTR %', data: (c.ctr_daily || {}).data }]
    });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views/hub/monitoring/ads.blade.php ENDPATH**/ ?>