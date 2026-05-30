<?php $__env->startSection('title', 'Input HPP — Shopee Profit Hub'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $st = $stats ?? [];
    $f = $filters ?? [];
?>
<div class="report-shell">
    <?php echo $__env->make('hub.partials.page-hero', [
        'icon' => 'fa-tags',
        'title' => 'Input HPP & Packaging',
        'subtitle' => 'Isi biaya pokok per produk — dipakai di laporan Monitoring',
        'meta' => [
            ['icon' => 'fa-chart-pie', 'text' => ($st['pct'] ?? 0) . '% lengkap'],
            ['icon' => 'fa-exclamation-triangle', 'text' => ($st['missing'] ?? 0) . ' belum HPP'],
        ],
        'actions' => '<a href="' . route('products.costs') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-layer-group"></i> Editor Varian</a>'
            . '<a href="' . route('manage.index') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-database"></i> Kelola</a>',
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <div class="report-kpi-hero" style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));">
        <div class="report-kpi-card"><div class="label">Total SKU</div><div class="value"><?php echo e(hub_num($st['total'] ?? 0)); ?></div></div>
        <div class="report-kpi-card positive"><div class="label">HPP Terisi</div><div class="value"><?php echo e(hub_num($st['with_hpp'] ?? 0)); ?></div></div>
        <div class="report-kpi-card <?php echo e(($st['missing'] ?? 0) > 0 ? 'warn' : 'positive'); ?>"><div class="label">Belum HPP</div><div class="value"><?php echo e(hub_num($st['missing'] ?? 0)); ?></div></div>
        <div class="report-kpi-card"><div class="label">Kelengkapan</div><div class="value"><?php echo e($st['pct'] ?? 0); ?>%</div></div>
    </div>

    <?php if(($st['missing'] ?? 0) > 0): ?>
    <div class="report-insights mb-3">
        <div class="report-insight warning">
            <div class="icon"><i class="fas fa-tags"></i></div>
            <div><strong><?php echo e($st['missing']); ?> produk belum punya HPP</strong><p>Filter "Belum HPP" di bawah, isi kolom HPP lalu simpan.</p></div>
        </div>
    </div>
    <?php endif; ?>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <?php $__currentLoopData = ['all' => 'Semua', 'missing' => 'Belum HPP', 'complete' => 'Sudah Lengkap']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('hpp.index', array_merge(request()->except('fill', 'page'), ['fill' => $key]))); ?>"
                class="hub-btn hub-btn-sm <?php echo e(($f['fill'] ?? 'all') === $key ? 'hub-btn-primary' : 'hub-btn-outline'); ?>">
                <?php echo e($label); ?>

            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="report-filter-card">
        <form method="GET" action="<?php echo e(route('hpp.index')); ?>" class="row g-2 align-items-end">
            <input type="hidden" name="fill" value="<?php echo e($f['fill'] ?? 'all'); ?>">
            <div class="col-md-4">
                <label class="hub-form-label">Cari produk</label>
                <input type="search" name="search" class="hub-form-control" value="<?php echo e($f['search'] ?? ''); ?>" placeholder="Nama, ID Shopee…">
            </div>
            <div class="col-md-3">
                <label class="hub-form-label">Kategori</label>
                <select name="category" class="hub-form-select hub-form-control">
                    <option value="">Semua</option>
                    <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($cat); ?>" <?php if(($f['category'] ?? '') === $cat): echo 'selected'; endif; ?>><?php echo e($cat); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="hub-form-label">Platform</label>
                <select name="platform" class="hub-form-select hub-form-control">
                    <option value="">Semua</option>
                    <option value="shopee" <?php if(($f['platform'] ?? '') === 'shopee'): echo 'selected'; endif; ?>>Shopee</option>
                    <option value="internal" <?php if(($f['platform'] ?? '') === 'internal'): echo 'selected'; endif; ?>>Internal</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="hub-btn hub-btn-primary w-100"><i class="fas fa-filter"></i> Terapkan</button>
            </div>
        </form>
    </div>

    <form method="POST" action="<?php echo e(route('hpp.save')); ?>" id="hppForm">
        <?php echo csrf_field(); ?>
        <?php if($f['search'] ?? false): ?><input type="hidden" name="search" value="<?php echo e($f['search']); ?>"><?php endif; ?>
        <?php if($f['category'] ?? false): ?><input type="hidden" name="category" value="<?php echo e($f['category']); ?>"><?php endif; ?>
        <?php if($f['platform'] ?? false): ?><input type="hidden" name="platform" value="<?php echo e($f['platform']); ?>"><?php endif; ?>
        <?php if(($f['fill'] ?? 'all') !== 'all'): ?><input type="hidden" name="fill" value="<?php echo e($f['fill']); ?>"><?php endif; ?>

        <div class="hub-card">
            <div class="hub-card-header flex-wrap">
                <h2 class="report-section-title"><i class="fas fa-table me-2"></i>Tabel HPP</h2>
                <span class="hub-pill hub-pill-muted"><?php echo e($products->count()); ?> baris</span>
            </div>
            <div class="hub-card-body p-0 hub-dt-wrap">
                <?php if($products->isNotEmpty()): ?>
                <div class="table-responsive">
                    <table id="hppTable" class="table hub-dt-table mb-0" style="width:100%">
                        <thead>
                            <tr>
                                <th style="width:40px">#</th>
                                <th>Produk</th>
                                <th class="num">Harga jual</th>
                                <th class="num" style="min-width:120px">HPP (Rp)</th>
                                <th style="min-width:100px">Pack</th>
                                <th class="num" style="min-width:90px">Nilai</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $hasHpp = $product->hpp_amount !== null;
                                $price = (float) ($product->base_price ?? 0);
                                $hpp = (float) ($product->hpp_amount ?? 0);
                                $packType = $product->packaging_type ?? 'fixed';
                                $packVal = (float) ($product->packaging_value ?? 0);
                                $packCost = $packType === 'percent' && $price > 0 ? $price * $packVal / 100 : $packVal;
                                $margin = $price > 0 ? (($price - $hpp - $packCost) / $price) * 100 : null;
                            ?>
                            <input type="hidden" name="products[<?php echo e($i); ?>][id]" value="<?php echo e($product->id); ?>">
                            <tr class="<?php echo e($hasHpp ? '' : 'row-missing-hpp'); ?>" data-hpp-row>
                                <td class="text-muted"><?php echo e($i + 1); ?></td>
                                <td>
                                    <div class="product-cell">
                                        <span class="name" title="<?php echo e($product->name); ?>"><?php echo e(Str::limit($product->name, 50)); ?></span>
                                        <?php if($product->external_item_id): ?>
                                            <span class="sku">ID <?php echo e($product->external_item_id); ?></span>
                                        <?php endif; ?>
                                        <?php if($product->external_platform === 'shopee'): ?>
                                            <span class="hub-pill hub-pill-muted hub-pill-sm mt-1">Shopee</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="num">
                                    <?php if($price > 0): ?>
                                        <?php echo e(hub_rp($price)); ?>

                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="num">
                                    <input type="number" name="products[<?php echo e($i); ?>][hpp_amount]" class="hub-form-control hub-form-control-sm hpp-input"
                                        min="0" step="100" value="<?php echo e($product->hpp_amount); ?>" placeholder="0"
                                        data-price="<?php echo e($price); ?>">
                                </td>
                                <td>
                                    <select name="products[<?php echo e($i); ?>][packaging_type]" class="hub-form-select hub-form-control-sm pack-type-input">
                                        <option value="fixed" <?php if($packType === 'fixed'): echo 'selected'; endif; ?>>Rp</option>
                                        <option value="percent" <?php if($packType === 'percent'): echo 'selected'; endif; ?>>%</option>
                                    </select>
                                </td>
                                <td class="num">
                                    <input type="number" name="products[<?php echo e($i); ?>][packaging_value]" class="hub-form-control hub-form-control-sm pack-val-input"
                                        min="0" step="0.01" value="<?php echo e($product->packaging_value); ?>">
                                </td>
                                <td>
                                    <?php if($hasHpp): ?>
                                        <span class="hub-pill hub-pill-success">OK</span>
                                        <?php if($margin !== null): ?>
                                            <span class="hub-dt-sub <?php echo e($margin >= 0 ? 'text-success' : 'text-danger'); ?>"><?php echo e(number_format($margin, 1)); ?>% margin</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="hub-pill hub-pill-warning">Kosong</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="hub-dt-empty">
                    <i class="fas fa-box-open fa-3x mb-3 opacity-50"></i>
                    <p>Tidak ada produk. Jalankan <strong>Sync Produk</strong> di Kelola Data.</p>
                    <a href="<?php echo e(route('manage.index')); ?>" class="hub-btn hub-btn-primary mt-2">Ke Kelola Data</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <div class="hpp-save-bar" id="hppSaveBar">
        <div>
            <strong><i class="fas fa-pen me-2"></i>Ada perubahan belum disimpan</strong>
            <span class="opacity-75 ms-2 small">Tekan Simpan untuk update laporan Monitoring</span>
        </div>
        <button type="submit" form="hppForm" class="hub-btn hub-btn-primary" style="background:#fff;color:var(--maroon-800)!important;">
            <i class="fas fa-save"></i> Simpan Semua HPP
        </button>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
    <?php echo $__env->make('hub.partials.datatables-assets', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
    <?php echo $__env->make('hub.partials.datatables-scripts', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<script>
(function () {
    const form = document.getElementById('hppForm');
    const bar = document.getElementById('hppSaveBar');
    let dirty = false;

    const markDirty = () => {
        if (!dirty) {
            dirty = true;
            bar?.classList.add('show');
            document.body.style.paddingBottom = '72px';
        }
    };

    form?.querySelectorAll('input, select').forEach(el => {
        el.addEventListener('change', markDirty);
        el.addEventListener('input', markDirty);
    });

    if (document.getElementById('hppTable')) {
        HubDataTable.init('#hppTable', {
            pageLength: 25,
            order: [[1, 'asc']],
            columnDefs: [
                { orderable: false, targets: [3, 4, 5, 6] },
            ],
        });
    }
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views\hub\hpp.blade.php ENDPATH**/ ?>