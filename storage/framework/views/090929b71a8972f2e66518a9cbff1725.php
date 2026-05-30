<?php $__env->startSection('title', 'Kelola Data — Shopee Profit Hub'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $st = $stats ?? [];
    $hppPct = $st['hpp_complete_pct'] ?? 0;
?>

<div class="report-shell">
    <?php echo $__env->make('hub.partials.page-hero', [
        'icon' => 'fa-database',
        'title' => 'Pusat Kelola Data',
        'subtitle' => 'Sinkronisasi Shopee, master biaya, dan kualitas data untuk laporan monitoring',
        'meta' => [
            ['icon' => 'fa-clock', 'text' => 'Diperbarui ' . ($meta['generated_at'] ?? now()->format('d M Y H:i'))],
            ['icon' => 'fa-store', 'text' => $token ? 'Shop ' . $token->shop_id : 'Belum terhubung'],
        ],
        'actions' => '<a href="' . route('monitoring.index') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-chart-line"></i> Monitoring</a>',
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

  <div class="report-kpi-hero" style="grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));">
        <div class="report-kpi-card">
            <div class="label">Total Produk</div>
            <div class="value"><?php echo e(hub_num($st['products_total'] ?? 0)); ?></div>
            <div class="sub">Master katalog</div>
        </div>
        <div class="report-kpi-card <?php echo e($hppPct >= 80 ? 'positive' : 'warn'); ?>">
            <div class="label">HPP Terisi</div>
            <div class="value"><?php echo e($hppPct); ?>%</div>
            <div class="sub"><?php echo e($st['with_hpp'] ?? 0); ?> / <?php echo e($st['products_total'] ?? 0); ?> produk</div>
        </div>
        <div class="report-kpi-card <?php echo e(($st['missing_hpp'] ?? 0) > 0 ? 'warn' : 'positive'); ?>">
            <div class="label">Tanpa HPP</div>
            <div class="value"><?php echo e($st['missing_hpp'] ?? 0); ?></div>
            <div class="sub">Perlu dilengkapi</div>
        </div>
        <div class="report-kpi-card <?php echo e(($st['unmapped_items'] ?? 0) > 0 ? 'negative' : 'positive'); ?>">
            <div class="label">Item Unmapped</div>
            <div class="value"><?php echo e($st['unmapped_items'] ?? 0); ?></div>
            <div class="sub">30 hari terakhir</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Pesanan Shopee</div>
            <div class="value"><?php echo e(hub_num($st['shopee_orders'] ?? 0)); ?></div>
            <div class="sub">dari <?php echo e(hub_num($st['orders_total'] ?? 0)); ?> total</div>
        </div>
    </div>

    <?php if(($st['missing_hpp'] ?? 0) > 0 || ($st['unmapped_items'] ?? 0) > 0): ?>
    <div class="report-insights mb-3">
        <?php if(($st['missing_hpp'] ?? 0) > 0): ?>
        <div class="report-insight warning">
            <div class="icon"><i class="fas fa-tags"></i></div>
            <div><strong><?php echo e($st['missing_hpp']); ?> produk belum punya HPP</strong><p>Lengkapi di tabel bawah agar laporan laba di Monitoring akurat.</p></div>
        </div>
        <?php endif; ?>
        <?php if(($st['unmapped_items'] ?? 0) > 0): ?>
        <div class="report-insight danger">
            <div class="icon"><i class="fas fa-link-slash"></i></div>
            <div><strong><?php echo e($st['unmapped_items']); ?> item order belum terpetakan</strong><p>Sync produk Shopee atau periksa mapping SKU / item_id.</p></div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="hub-card h-100">
                <div class="hub-card-header">
                    <div>
                        <h2 class="report-section-title"><i class="fas fa-plug me-2"></i>Integrasi & Sinkronisasi Shopee</h2>
                        <p class="report-section-desc">Tarik order, finansial, produk, dan performa iklan</p>
                    </div>
                    <span class="hub-pill <?php echo e($token ? 'hub-pill-success' : 'hub-pill-danger'); ?>"><?php echo e($token ? 'Terhubung' : 'Offline'); ?></span>
                </div>
                <div class="hub-card-body">
                    <?php if($token): ?>
                    <div class="report-pl mb-3" style="font-size:0.85rem;">
                        <table class="w-100">
                            <tr><td class="text-muted py-1">Shop ID</td><td class="text-end fw-bold"><?php echo e($token->shop_id); ?></td></tr>
                            <tr><td class="text-muted py-1">Environment</td><td class="text-end"><span class="hub-pill hub-pill-muted"><?php echo e(strtoupper($env)); ?></span></td></tr>
                            <?php if($token->expire_at): ?>
                            <tr><td class="text-muted py-1">Token expire</td><td class="text-end"><?php echo e($token->expire_at->format('d M Y H:i')); ?></td></tr>
                            <?php endif; ?>
                        </table>
                    </div>
                    <div class="sync-action-grid">
                        <form method="POST" action="<?php echo e(route('manage.sync.orders')); ?>" class="sync-action-card">
                            <?php echo csrf_field(); ?><input type="hidden" name="days" value="7">
                            <i class="fas fa-shopping-cart"></i>
                            <strong>Sync Order</strong>
                            <span>7 hari terakhir</span>
                            <button type="submit" class="hub-btn hub-btn-outline hub-btn-sm w-100 mt-2">Jalankan</button>
                        </form>
                        <form method="POST" action="<?php echo e(route('manage.sync.products')); ?>" class="sync-action-card">
                            <?php echo csrf_field(); ?>
                            <i class="fas fa-box"></i>
                            <strong>Sync Produk</strong>
                            <span>Katalog + varian</span>
                            <button type="submit" class="hub-btn hub-btn-outline hub-btn-sm w-100 mt-2">Jalankan</button>
                        </form>
                        <form method="POST" action="<?php echo e(route('manage.sync.ads')); ?>" class="sync-action-card">
                            <?php echo csrf_field(); ?><input type="hidden" name="ads_days" value="30">
                            <i class="fas fa-bullhorn"></i>
                            <strong>Sync Iklan</strong>
                            <span>30 hari · per produk</span>
                            <button type="submit" class="hub-btn hub-btn-outline hub-btn-sm w-100 mt-2">Jalankan</button>
                        </form>
                        <form method="POST" action="<?php echo e(route('manage.sync.all')); ?>" class="sync-action-card highlight">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="days" value="7"><input type="hidden" name="ads_days" value="30">
                            <i class="fas fa-rotate"></i>
                            <strong>Sync Semua</strong>
                            <span>Order + produk + iklan</span>
                            <button type="submit" class="hub-btn hub-btn-primary hub-btn-sm w-100 mt-2">Jalankan</button>
                        </form>
                    </div>
                    <p class="small text-muted mb-0 mt-2"><i class="fas fa-info-circle me-1"></i> Permission Marketing/Ads diperlukan untuk sync iklan.</p>
                    <?php else: ?>
                    <p class="mb-3">Hubungkan toko Shopee untuk mulai menarik data otomatis.</p>
                    <a href="<?php echo e(route('shopee.connect')); ?>" class="hub-btn hub-btn-primary"><i class="fas fa-link"></i> Connect Shopee</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="hub-card h-100">
                <div class="hub-card-header">
                    <h2 class="report-section-title"><i class="fas fa-building me-2"></i>Biaya Operasional</h2>
                </div>
                <div class="hub-card-body">
                    <form method="POST" action="<?php echo e(route('manage.operational.save')); ?>">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="hub-form-label">Periode bulan</label>
                            <input type="month" name="year_month" class="hub-form-control" value="<?php echo e($yearMonth); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="hub-form-label">Nominal (Rp)</label>
                            <input type="number" name="operational_amount" class="hub-form-control" min="0" step="1000"
                                value="<?php echo e(old('operational_amount', $operational->operational_amount ?? '')); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="hub-form-label">Catatan</label>
                            <input type="text" name="notes" class="hub-form-control" placeholder="Mis. gaji, sewa, listrik"
                                value="<?php echo e(old('notes', $operational->notes ?? '')); ?>">
                        </div>
                        <?php if($operational): ?>
                        <div class="alert alert-info py-2 small mb-3">
                            Tersimpan: <strong><?php echo e(hub_rp($operational->operational_amount)); ?></strong>
                            <?php if($operational->notes): ?> — <?php echo e($operational->notes); ?> <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <button type="submit" class="hub-btn hub-btn-primary w-100"><i class="fas fa-save"></i> Simpan Operasional</button>
                    </form>
                    <p class="small text-muted mt-2 mb-0">Dialokasikan proporsional ke produk di laporan Monitoring.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="hub-card">
        <div class="hub-card-header flex-wrap">
            <div>
                <h2 class="report-section-title"><i class="fas fa-tags me-2"></i>Master HPP & Packaging</h2>
                <p class="report-section-desc"><?php echo e($products->count()); ?> SKU — input manual per produk</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <input type="search" id="hppSearch" class="hub-form-control report-search" placeholder="Cari produk...">
                <a href="<?php echo e(route('hpp.index')); ?>" class="hub-btn hub-btn-sm hub-btn-primary">Input HPP</a>
                <a href="<?php echo e(route('products.costs')); ?>" class="hub-btn hub-btn-sm hub-btn-outline">Varian</a>
            </div>
        </div>
        <div class="hub-card-body p-0">
            <form method="POST" action="<?php echo e(route('manage.costs.save')); ?>">
                <?php echo csrf_field(); ?>
                <div class="report-table-scroll" style="max-height:520px;">
                    <table class="report-table" id="hppTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Produk</th>
                                <th class="num">HPP (Rp)</th>
                                <th>Packaging</th>
                                <th class="num">Nilai</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $hasHpp = $product->hpp_amount !== null;
                                $hasPack = $product->packaging_value !== null;
                            ?>
                            <input type="hidden" name="products[<?php echo e($i); ?>][id]" value="<?php echo e($product->id); ?>">
                            <tr data-search="<?php echo e(strtolower($product->name . ' ' . ($product->external_item_id ?? ''))); ?>">
                                <td class="text-muted"><?php echo e($i + 1); ?></td>
                                <td class="product-cell">
                                    <span class="name" title="<?php echo e($product->name); ?>"><?php echo e(Str::limit($product->name, 45)); ?></span>
                                    <?php if($product->external_item_id): ?><span class="sku">ID <?php echo e($product->external_item_id); ?></span><?php endif; ?>
                                </td>
                                <td class="num" style="min-width:110px;">
                                    <input type="number" name="products[<?php echo e($i); ?>][hpp_amount]" class="hub-form-control hub-form-control-sm"
                                        min="0" step="100" value="<?php echo e($product->hpp_amount); ?>" placeholder="0">
                                </td>
                                <td style="min-width:100px;">
                                    <select name="products[<?php echo e($i); ?>][packaging_type]" class="hub-form-select hub-form-control-sm">
                                        <option value="fixed" <?php if(($product->packaging_type ?? 'fixed') === 'fixed'): echo 'selected'; endif; ?>>Fixed (Rp)</option>
                                        <option value="percent" <?php if($product->packaging_type === 'percent'): echo 'selected'; endif; ?>>% Harga</option>
                                    </select>
                                </td>
                                <td class="num" style="min-width:90px;">
                                    <input type="number" name="products[<?php echo e($i); ?>][packaging_value]" class="hub-form-control hub-form-control-sm"
                                        min="0" step="0.01" value="<?php echo e($product->packaging_value); ?>">
                                </td>
                                <td>
                                    <?php if($hasHpp || $hasPack): ?>
                                        <span class="hub-pill hub-pill-success">OK</span>
                                    <?php else: ?>
                                        <span class="hub-pill hub-pill-warning">Kosong</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
                <?php if($products->isNotEmpty()): ?>
                <div class="p-3 border-top bg-light">
                    <button type="submit" class="hub-btn hub-btn-primary"><i class="fas fa-save"></i> Simpan Semua HPP & Packaging</button>
                </div>
                <?php else: ?>
                <p class="text-center text-muted py-5">Belum ada produk. Jalankan <strong>Sync Produk</strong> terlebih dahulu.</p>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function () {
    const bindSearch = (inputId, tableId) => {
        const input = document.getElementById(inputId);
        const rows = document.querySelectorAll(`#${tableId} tbody tr[data-search]`);
        if (!input || !rows.length) return;
        input.addEventListener('input', () => {
            const q = input.value.toLowerCase().trim();
            rows.forEach(tr => { tr.style.display = !q || tr.dataset.search.includes(q) ? '' : 'none'; });
        });
    };
    bindSearch('hppSearch', 'hppTable');
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views\hub\manage.blade.php ENDPATH**/ ?>