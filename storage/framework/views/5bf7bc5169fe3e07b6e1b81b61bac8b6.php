<?php $__env->startSection('title', 'Arus Kas — CEO'); ?>

<?php $__env->startSection('content'); ?>
<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-wallet me-2"></i>Estimasi Arus Kas</h1>
        <p class="small opacity-90 mb-0"><?php echo e($cashflow['note'] ?? ''); ?></p>
    </div>
    <?php echo $__env->make('hub.partials.ceo-nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <div class="mon-kpi-row mb-3">
        <div class="mon-kpi"><div class="label">Dana pending</div><div class="value"><?php echo e(hub_rp($cashflow['pending_settlement'] ?? 0)); ?></div></div>
        <div class="mon-kpi"><div class="label">Sudah dilepas (periode)</div><div class="value"><?php echo e(hub_rp($cashflow['released_total'] ?? 0)); ?></div></div>
        <div class="mon-kpi"><div class="label">Hold estimasi</div><div class="value"><?php echo e($cashflow['hold_days'] ?? 3); ?> hari</div></div>
    </div>

    <div class="hub-card mb-3">
        <div class="hub-card-header"><h2 class="report-section-title">Import Data Income</h2></div>
        <div class="hub-card-body">
            <p class="small text-muted"><?php echo e($cashflow['import_hint'] ?? ''); ?></p>
            <form method="POST" action="<?php echo e(route('ceo.settlement.import')); ?>" enctype="multipart/form-data" class="hub-filter-bar mt-2">
                <?php echo csrf_field(); ?>
                <div class="filter-item">
                    <label class="hub-form-label">CSV export Data Income Shopee</label>
                    <input type="file" name="file" class="hub-form-control" accept=".csv,.txt" required>
                </div>
                <div class="filter-item" style="align-self:flex-end">
                    <button type="submit" class="hub-btn hub-btn-primary"><i class="fas fa-upload me-1"></i> Import dana dilepaskan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="fc-chart-stack mb-3">
        <?php echo $__env->make('hub.partials.chart-panel', [
            'id' => 'cashChart',
            'title' => 'Arus kas mingguan',
            'subtitle' => 'Net masuk vs biaya iklan keluar',
            'size' => 'hero',
            'badge' => 'Estimasi',
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>
    <div class="hub-card">
        <div class="hub-card-body p-0">
            <table class="report-table">
                <thead><tr><th>Minggu</th><th class="num">Net masuk</th><th class="num">Iklan keluar</th><th class="num">Selisih</th></tr></thead>
                <tbody>
                <?php $__currentLoopData = $cashflow['weeks'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($w['label']); ?></td>
                    <td class="num"><?php echo e(hub_rp($w['net_in'])); ?></td>
                    <td class="num"><?php echo e(hub_rp($w['ads_out'])); ?></td>
                    <td class="num <?php echo e(($w['net_cash'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg'); ?>"><?php echo e(hub_rp($w['net_cash'], true)); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const weeks = <?php echo json_encode($cashflow['weeks'] ?? [], 15, 512) ?>;
    HubCharts.render('cashChart', 'bar', {
        labels: weeks.map(w => w.label),
        datasets: [
            { label: 'Net masuk', data: weeks.map(w => w.net_in) },
            { label: 'Iklan', data: weeks.map(w => w.ads_out) },
        ]
    });
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views/hub/ceo/settlement.blade.php ENDPATH**/ ?>