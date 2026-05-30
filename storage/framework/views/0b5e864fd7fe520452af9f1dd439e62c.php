<?php $__env->startSection('title', 'BCG Funnel — Monitoring'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $bcg = $bcg ?? [];
    $settings = $bcg['settings'] ?? [];
    $counts = $bcg['counts'] ?? [];
    $q = request()->query();
    $blocks = [
        'star' => ['key' => 'star', 'label' => 'Star', 'icon' => 'fa-star', 'class' => 'bcg-star'],
        'cash_cow' => ['key' => 'cash_cow', 'label' => 'Cash Cow', 'icon' => 'fa-coins', 'class' => 'bcg-cow'],
        'question_mark' => ['key' => 'question_mark', 'label' => 'Question Mark', 'icon' => 'fa-question', 'class' => 'bcg-qm'],
        'dog' => ['key' => 'dog', 'label' => 'Dog', 'icon' => 'fa-paw', 'class' => 'bcg-dog'],
    ];
?>
<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-chart-scatter me-2"></i>BCG Funnel</h1>
        <div class="report-hero-meta">
            <span>Trafik & konversi · <?php echo e($bcg['period']['label'] ?? ''); ?></span>
            <span>Batas konversi ≥ <?php echo e($settings['conversion_threshold_pct'] ?? 2); ?>%</span>
            <span>Baseline trafik: <?php echo e(number_format($settings['traffic_baseline'] ?? 0)); ?></span>
        </div>
    </div>

    <?php echo $__env->make('hub.partials.monitoring-nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <?php if(empty($bcg['has_data'])): ?>
    <div class="hub-alert hub-alert-info mb-3">
        <i class="fas fa-upload me-2"></i>
        Belum ada data performa produk. Download dari Seller Center → Performa Toko → Produk, lalu upload di bawah.
        <?php if($bcg['performance_url'] ?? null): ?>
        <a href="<?php echo e($bcg['performance_url']); ?>" target="_blank" rel="noopener" class="ms-1">Buka Seller Center</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="hub-card mb-3">
        <div class="hub-card-header"><h2 class="report-section-title">Import Performa Produk</h2></div>
        <div class="hub-card-body">
            <form method="POST" action="<?php echo e(route('monitoring.bcg.import')); ?>" enctype="multipart/form-data" class="hub-filter-bar">
                <?php echo csrf_field(); ?>
                <div class="filter-item">
                    <label class="hub-form-label">File Excel (.xlsx)</label>
                    <input type="file" name="file" class="hub-form-control" accept=".xlsx,.xls,.csv" required>
                </div>
                <div class="filter-item">
                    <label class="hub-form-label">Periode mulai</label>
                    <input type="date" name="period_start" class="hub-form-control" value="<?php echo e($bcg['period']['start'] ?? ''); ?>">
                </div>
                <div class="filter-item">
                    <label class="hub-form-label">Periode akhir</label>
                    <input type="date" name="period_end" class="hub-form-control" value="<?php echo e($bcg['period']['end'] ?? ''); ?>">
                </div>
                <div class="filter-item" style="align-self:flex-end">
                    <button type="submit" class="hub-btn hub-btn-primary"><i class="fas fa-upload me-1"></i> Import</button>
                </div>
            </form>
            <p class="small text-muted mb-0 mt-2">Format sama dengan sheet <strong>Data</strong> template BCG ROAS (Kode Produk, Pengunjung, Halaman Dilihat, dll.)</p>
        </div>
    </div>

    <div class="bcg-quadrant-grid mb-3">
        <?php $__currentLoopData = $blocks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php $items = $bcg['quadrants'][$b['key']] ?? []; ?>
        <div class="bcg-quadrant <?php echo e($b['class']); ?>">
            <div class="bcg-quadrant-head">
                <h3><i class="fas <?php echo e($b['icon']); ?> me-1"></i><?php echo e($b['label']); ?></h3>
                <span class="badge"><?php echo e($counts[$b['key']] ?? count($items)); ?></span>
            </div>
            <div class="bcg-table-wrap">
                <table class="report-table bcg-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="num">Trafik</th>
                            <th class="num">Conv%</th>
                            <th class="num">Terjual</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = array_slice($items, 0, 15); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td>
                                <?php if($p['product_id']): ?>
                                <a href="<?php echo e(route('monitoring.product', ['product' => $p['product_id']] + $q)); ?>"><?php echo e(\Illuminate\Support\Str::limit($p['name'], 32)); ?></a>
                                <?php else: ?>
                                <?php echo e(\Illuminate\Support\Str::limit($p['name'], 32)); ?>

                                <?php endif; ?>
                                <div class="small text-muted"><?php echo e($p['ads_action'] ?? ''); ?></div>
                            </td>
                            <td class="num"><?php echo e(number_format($p['visitors'] ?? 0)); ?></td>
                            <td class="num"><?php echo e($p['conversion_rate'] ?? 0); ?>%</td>
                            <td class="num"><?php echo e($p['units_sold'] ?? 0); ?></td>
                            <td class="bcg-links">
                                <?php if($p['links']['product'] ?? null): ?>
                                <a href="<?php echo e($p['links']['product']); ?>" target="_blank" rel="noopener" title="Halaman produk"><i class="fas fa-external-link-alt"></i></a>
                                <?php endif; ?>
                                <?php if($p['links']['ads'] ?? null): ?>
                                <a href="<?php echo e($p['links']['ads']); ?>" target="_blank" rel="noopener" title="Iklan Shopee" class="text-danger"><i class="fas fa-bullhorn"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="5" class="text-muted">—</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <?php if(!empty($bcg['has_data'])): ?>
    <div class="hub-card">
        <div class="hub-card-header"><h2 class="report-section-title">Target penjualan per SKU (<?php echo e(now()->format('Y-m')); ?>)</h2></div>
        <div class="hub-card-body">
            <form method="POST" action="<?php echo e(route('monitoring.bcg.targets')); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="year_month" value="<?php echo e(now()->format('Y-m')); ?>">
                <div class="hub-table-wrap">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="num">Terjual</th>
                                <th class="num">Target unit</th>
                                <th class="num">Target omzet (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $starItems = array_merge($bcg['quadrants']['star'] ?? [], $bcg['quadrants']['cash_cow'] ?? []); ?>
                            <?php $__currentLoopData = array_slice($starItems, 0, 20); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if($p['product_id']): ?>
                            <tr>
                                <td><?php echo e(\Illuminate\Support\Str::limit($p['name'], 40)); ?></td>
                                <td class="num"><?php echo e($p['units_sold'] ?? 0); ?></td>
                                <td class="num">
                                    <input type="hidden" name="targets[<?php echo e($i); ?>][product_id]" value="<?php echo e($p['product_id']); ?>">
                                    <input type="number" name="targets[<?php echo e($i); ?>][target_units]" class="hub-form-control hub-form-control-sm" value="<?php echo e($p['target_units'] ?? ''); ?>" min="0" placeholder="0">
                                </td>
                                <td class="num">
                                    <input type="number" name="targets[<?php echo e($i); ?>][target_gross]" class="hub-form-control hub-form-control-sm" value="<?php echo e($p['target_gross'] ?? ''); ?>" min="0" placeholder="0">
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="hub-btn hub-btn-primary mt-3">Simpan target SKU</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views\hub\monitoring\bcg.blade.php ENDPATH**/ ?>