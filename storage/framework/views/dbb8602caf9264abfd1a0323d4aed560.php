<?php $__env->startSection('title', 'Monitoring — Laba & Produk'); ?>

<?php $__env->startPush('styles'); ?>
<link href="<?php echo e(asset('css/hub-monitoring.css')); ?>?v=1" rel="stylesheet">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php
    $s = $summary ?? [];
    $filters = $filters ?? [];
    $meta = $meta ?? [];
    $analysis = $analysis ?? [];
    $pt = $product_totals ?? [];
    $fb = $fee_breakdown ?? [];
    $fbPct = $fee_breakdown_pct ?? [];
    $health = $analysis['health_score'] ?? 0;
?>

<div class="report-shell">
    
    <div class="report-hero">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h1><i class="fas fa-chart-line me-2"></i>Laporan Kinerja Toko Shopee</h1>
                <div class="report-hero-meta">
                    <span><i class="far fa-calendar-alt"></i> <?php echo e($meta['period_label'] ?? '—'); ?></span>
                    <span><i class="far fa-clock"></i> <?php echo e($meta['days'] ?? 0); ?> hari</span>
                    <span><i class="fas fa-sync-alt"></i> Diperbarui <?php echo e($meta['generated_at'] ?? now()->format('d M Y H:i')); ?></span>
                    <span><i class="fas fa-filter"></i> Status: <?php echo e(ucfirst(str_replace('_',' ', $filters['status'] ?? 'completed'))); ?></span>
                    <?php if(!empty($shop['label'])): ?><span><i class="fas fa-store"></i> <?php echo e($shop['label']); ?></span><?php endif; ?>
                </div>
            </div>
            <div class="hub-btn-group">
                <a href="<?php echo e(route('monitoring.profit', array_merge(request()->query(), ['export' => 'xlsx']))); ?>" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5);">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
            </div>
        </div>
        <div class="report-health">
            <div class="report-health-ring" style="--score: <?php echo e($health); ?>">
                <span><?php echo e($health); ?></span>
            </div>
            <div>
                <strong>Skor kesehatan data & profit</strong>
                <div class="small opacity-90">Berdasarkan kelengkapan HPP, margin, dan efisiensi iklan</div>
            </div>
        </div>
    </div>

    <?php echo $__env->make('hub.partials.monitoring-nav', ['activeSection' => $activeSection ?? 'profit'], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $__env->make('hub.partials.monitoring-filter', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    
    <?php if(!empty($analysis['insights'])): ?>
    <div class="hub-card mb-3">
        <div class="hub-card-header">
            <div>
                <h2 class="report-section-title">Analisis & Rekomendasi</h2>
                <p class="report-section-desc">Ringkasan otomatis berdasarkan data periode terpilih</p>
            </div>
        </div>
        <div class="hub-card-body">
            <div class="report-insights">
                <?php $__currentLoopData = $analysis['insights']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ins): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="report-insight <?php echo e($ins['type']); ?>">
                    <div class="icon">
                        <?php if($ins['type'] === 'success'): ?><i class="fas fa-check"></i>
                        <?php elseif($ins['type'] === 'danger'): ?><i class="fas fa-exclamation-triangle"></i>
                        <?php elseif($ins['type'] === 'warning'): ?><i class="fas fa-exclamation-circle"></i>
                        <?php else: ?><i class="fas fa-info"></i><?php endif; ?>
                    </div>
                    <div>
                        <strong><?php echo e($ins['title']); ?></strong>
                        <p><?php echo e($ins['text']); ?></p>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    
    <div class="report-kpi-hero">
        <div class="report-kpi-card <?php echo e(($s['net_profit'] ?? 0) >= 0 ? 'positive' : 'negative'); ?>">
            <div class="label">Laba / Rugi Bersih</div>
            <div class="value"><?php echo e(hub_rp($s['net_profit'] ?? 0, true)); ?></div>
            <div class="sub">Margin <?php echo e(hub_pct($s['margin'] ?? null)); ?> dari net</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Penghasilan Net</div>
            <div class="value"><?php echo e(hub_rp($s['net'] ?? 0)); ?></div>
            <div class="sub">Setelah fee Shopee <?php echo e(hub_rp($s['fee_total'] ?? 0)); ?></div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Pendapatan Kotor</div>
            <div class="value"><?php echo e(hub_rp($s['gross'] ?? 0)); ?></div>
            <div class="sub"><?php echo e(hub_num($s['orders_count'] ?? 0)); ?> pesanan</div>
        </div>
        <div class="report-kpi-card <?php echo e(($s['roas'] ?? 0) >= 2 ? 'positive' : (($s['ads_total'] ?? 0) > 0 ? 'warn' : '')); ?>">
            <div class="label">ROAS / ACOS</div>
            <div class="value"><?php echo e(isset($s['roas']) && $s['roas'] ? number_format($s['roas'], 2) . 'x' : '—'); ?></div>
            <div class="sub">ACOS <?php echo e(hub_pct($s['acos'] ?? null)); ?> · Iklan <?php echo e(hub_rp($s['ads_total'] ?? 0)); ?></div>
        </div>
    </div>

    
    <div class="report-kpi-secondary">
        <div class="report-kpi-mini"><div class="label">Laba kotor</div><div class="value"><?php echo e(hub_rp($s['gross_profit'] ?? 0)); ?></div></div>
        <div class="report-kpi-mini"><div class="label">HPP + Pack</div><div class="value"><?php echo e(hub_rp($s['cogs'] ?? 0)); ?></div></div>
        <div class="report-kpi-mini"><div class="label">Operasional</div><div class="value"><?php echo e(hub_rp($s['operational_total'] ?? 0)); ?></div></div>
        <div class="report-kpi-mini"><div class="label">Take rate fee</div><div class="value"><?php echo e(hub_pct($s['take_rate'] ?? null)); ?></div></div>
        <div class="report-kpi-mini"><div class="label">Rata-rata / order</div><div class="value"><?php echo e(hub_rp($s['avg_order_net'] ?? 0)); ?></div></div>
        <div class="report-kpi-mini"><div class="label">Rasio biaya</div><div class="value"><?php echo e(hub_pct($s['cost_ratio'] ?? null)); ?></div></div>
        <div class="report-kpi-mini"><div class="label">SKU aktif</div><div class="value"><?php echo e($s['products_count'] ?? 0); ?></div></div>
        <?php if(($s['missing_cost_orders'] ?? 0) > 0): ?>
        <div class="report-kpi-mini" style="border-color:#fecaca;background:#fef2f2;">
            <div class="label">Tanpa HPP</div><div class="value amt-neg"><?php echo e($s['missing_cost_orders']); ?> order</div>
        </div>
        <?php endif; ?>
    </div>

    <div class="fc-chart-stack mb-3">
        <div class="hub-card">
                <div class="hub-card-header">
                    <div>
                        <h2 class="report-section-title">Laporan Laba Rugi</h2>
                        <p class="report-section-desc">Alur pendapatan hingga laba bersih</p>
                    </div>
                </div>
                <div class="hub-card-body p-0">
                    <table class="report-pl">
                        <tbody>
                        <?php $__currentLoopData = $pl_statement ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $rowClass = match($row['type'] ?? '') {
                                    'total' => 'total',
                                    'subtotal' => 'subtotal',
                                    'fee' => 'fee',
                                    'revenue' => 'revenue',
                                    default => '',
                                };
                                $amt = (int)($row['amount'] ?? 0);
                            ?>
                            <tr class="<?php echo e($rowClass); ?>">
                                <td><?php echo e($row['label']); ?></td>
                                <td class="amount <?php echo e($amt < 0 ? 'amt-neg' : ''); ?>"><?php echo e(hub_rp($amt, true)); ?></td>
                            </tr>
                            <?php $__currentLoopData = $row['children'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="child fee">
                                <td><?php echo e($child['label']); ?></td>
                                <td class="amount amt-neg"><?php echo e(hub_rp($child['amount'] ?? 0, true)); ?></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php echo $__env->make('hub.partials.chart-panel', [
            'id' => 'chartMonthly',
            'title' => 'Tren laba bulanan',
            'subtitle' => 'Net penghasilan, HPP, dan laba bersih per bulan',
            'size' => 'hero',
            'badge' => 'P&L',
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <div class="hub-card">
                <div class="hub-card-header"><h2 class="report-section-title">Komposisi Fee Shopee</h2></div>
                <div class="hub-card-body">
                    <?php
                        $feeLabels = ['admin' => 'Administrasi', 'layanan' => 'Layanan', 'proses' => 'Proses', 'program_hemat' => 'Program Hemat'];
                    ?>
                    <?php $__currentLoopData = $feeLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="fee-bar-row">
                        <div class="fee-label">
                            <span><?php echo e($label); ?></span>
                            <strong><?php echo e(hub_rp($fb[$key] ?? 0)); ?> · <?php echo e(hub_pct($fbPct[$key] ?? 0)); ?></strong>
                        </div>
                        <div class="fee-bar-track">
                            <div class="fee-bar-fill" style="width: <?php echo e(min(100, ($fbPct[$key] ?? 0) * 100)); ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <div class="mt-3 pt-2 border-top d-flex justify-content-between">
                        <span class="text-muted small">Total fee platform</span>
                        <strong><?php echo e(hub_rp($s['fee_total'] ?? 0)); ?></strong>
                    </div>
                </div>
        </div>
    </div>

    
    <?php if(!empty($monthly)): ?>
    <div class="hub-card mb-3">
        <div class="hub-card-header"><h2 class="report-section-title">Rekap Bulanan</h2></div>
        <div class="hub-card-body p-0">
            <div class="hub-table-wrap">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th class="num">Pesanan</th>
                            <th class="num">Pendapatan</th>
                            <th class="num">Net</th>
                            <th class="num">HPP</th>
                            <th class="num">Iklan</th>
                            <th class="num">Opr.</th>
                            <th class="num">Laba Bersih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $monthly; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><strong><?php echo e($m['label']); ?></strong></td>
                            <td class="num"><?php echo e($m['orders'] ?? 0); ?></td>
                            <td class="num"><?php echo e(hub_rp($m['gross'] ?? 0)); ?></td>
                            <td class="num"><?php echo e(hub_rp($m['net'] ?? 0)); ?></td>
                            <td class="num"><?php echo e(hub_rp($m['cogs'] ?? 0)); ?></td>
                            <td class="num"><?php echo e(hub_rp($m['ads'] ?? 0)); ?></td>
                            <td class="num"><?php echo e(hub_rp($m['operational'] ?? 0)); ?></td>
                            <td class="num <?php echo e(($m['net_profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg'); ?>"><?php echo e(hub_rp($m['net_profit'] ?? 0, true)); ?></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="hub-card h-100">
                <div class="hub-card-header"><h2 class="report-section-title">Top 5 Produk (Laba)</h2></div>
                <div class="hub-card-body">
                    <ul class="rank-list">
                        <?php $__empty_1 = true; $__currentLoopData = $top_products ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <li>
                            <span class="rank-num"><?php echo e($i + 1); ?></span>
                            <div class="rank-body"><div class="rank-name"><?php echo e($p['name']); ?></div><div class="small text-muted">Qty <?php echo e($p['qty'] ?? 0); ?></div></div>
                            <span class="rank-val <?php echo e(($p['net_profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg'); ?>"><?php echo e(hub_rp($p['net_profit'] ?? 0, true)); ?></span>
                        </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <li class="text-muted">Tidak ada data</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="hub-card h-100">
                <div class="hub-card-header"><h2 class="report-section-title">Perlu Perhatian (Laba Terendah)</h2></div>
                <div class="hub-card-body">
                    <ul class="rank-list">
                        <?php $__empty_1 = true; $__currentLoopData = $bottom_products ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <li>
                            <span class="rank-num"><?php echo e($i + 1); ?></span>
                            <div class="rank-body"><div class="rank-name"><?php echo e($p['name']); ?></div></div>
                            <span class="rank-val amt-neg"><?php echo e(hub_rp($p['net_profit'] ?? 0, true)); ?></span>
                        </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <li class="text-muted">Tidak ada data</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    
    <div class="hub-card mb-3">
        <div class="hub-card-header flex-wrap">
            <div>
                <h2 class="report-section-title">Analisis per Produk</h2>
                <p class="report-section-desc"><?php echo e(count($products ?? [])); ?> SKU · alokasi net, HPP, iklan & operasional</p>
            </div>
            <input type="search" id="productSearch" class="hub-form-control report-search" placeholder="Cari produk / SKU...">
        </div>
        <div class="hub-card-body p-0">
            <div class="report-table-scroll hub-table-desktop">
                <table class="report-table" id="productTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Produk</th>
                            <th>Tier</th>
                            <th>Harga</th>
                            <th class="num">Qty</th>
                            <th class="num">Gross</th>
                            <th class="num">Net</th>
                            <th class="num">HPP</th>
                            <th class="num">Iklan</th>
                            <th class="num">Opr.</th>
                            <th class="num">Laba</th>
                            <th class="num">Margin</th>
                            <th class="num">ROAS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $products ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr data-search="<?php echo e(strtolower($p['name'] . ' ' . ($p['sku'] ?? ''))); ?>">
                            <td class="text-muted"><?php echo e($i + 1); ?></td>
                            <td class="product-cell">
                                <?php if(!empty($p['product_id'])): ?>
                                <a href="<?php echo e(route('monitoring.product', ['product' => $p['product_id']] + request()->query())); ?>" class="name text-decoration-none" title="<?php echo e($p['name']); ?>"><?php echo e($p['name']); ?></a>
                                <?php echo $__env->make('hub.partials.product-shopee-links', ['links' => $p['links'] ?? []], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                <?php else: ?>
                                <span class="name" title="<?php echo e($p['name']); ?>"><?php echo e($p['name']); ?></span>
                                <?php endif; ?>
                                <?php if(!empty($p['sku'])): ?><span class="sku"><?php echo e($p['sku']); ?></span><?php endif; ?>
                                <?php if($p['missing_cost'] ?? false): ?><span class="hub-pill hub-pill-warning">HPP?</span><?php endif; ?>
                                <?php if(!empty($p['action']['title'])): ?><span class="d-block small text-muted"><?php echo e($p['action']['title']); ?></span><?php endif; ?>
                            </td>
                            <td><span class="sku-tier <?php echo e($p['tier'] ?? ''); ?>"><?php echo e($p['tier'] ?? '—'); ?></span></td>
                            <td class="small">
                                <?php $ps = ($p['pricing'] ?? [])['status'] ?? ''; ?>
                                <?php if($ps): ?>
                                <span class="price-status-<?php echo e($ps === 'ok' ? 'ok' : (in_array($ps, ['too_low','not_covering']) ? 'low' : 'review')); ?>"><?php echo e($p['pricing']['status_label'] ?? ''); ?></span>
                                <?php else: ?> — <?php endif; ?>
                            </td>
                            <td class="num"><?php echo e(hub_num($p['qty'] ?? 0)); ?></td>
                            <td class="num"><?php echo e(hub_rp($p['gross'] ?? 0)); ?></td>
                            <td class="num"><?php echo e(hub_rp($p['net'] ?? 0)); ?></td>
                            <td class="num"><?php echo e(hub_rp($p['cogs'] ?? 0)); ?></td>
                            <td class="num"><?php echo e(hub_rp($p['ads_spend'] ?? 0)); ?></td>
                            <td class="num"><?php echo e(hub_rp($p['operational'] ?? 0)); ?></td>
                            <td class="num <?php echo e(($p['net_profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg'); ?>"><strong><?php echo e(hub_rp($p['net_profit'] ?? 0, true)); ?></strong></td>
                            <td class="num"><?php echo e(hub_pct($p['margin'] ?? null)); ?></td>
                            <td class="num"><?php echo e(isset($p['roas']) && $p['roas'] ? number_format($p['roas'], 2) : '—'); ?></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="13" class="text-center py-4 text-muted">Belum ada data produk pada periode ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if(!empty($products)): ?>
                    <tfoot>
                        <tr>
                            <td colspan="4"><strong>TOTAL</strong></td>
                            <td class="num"><?php echo e(hub_num($pt['qty'] ?? 0)); ?></td>
                            <td class="num"><?php echo e(hub_rp($pt['gross'] ?? 0)); ?></td>
                            <td class="num"><?php echo e(hub_rp($pt['net'] ?? 0)); ?></td>
                            <td class="num"><?php echo e(hub_rp($pt['cogs'] ?? 0)); ?></td>
                            <td class="num"><?php echo e(hub_rp($pt['ads_spend'] ?? 0)); ?></td>
                            <td class="num"><?php echo e(hub_rp($pt['operational'] ?? 0)); ?></td>
                            <td class="num"><?php echo e(hub_rp($pt['net_profit'] ?? 0, true)); ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <?php if(!empty($orders)): ?>
    <div class="hub-card">
        <div class="hub-card-header report-collapse-toggle" data-bs-toggle="collapse" data-bs-target="#ordersCollapse">
            <div>
                <h2 class="report-section-title"><i class="fas fa-chevron-down me-2"></i>Detail Pesanan (<?php echo e(count($orders)); ?>)</h2>
                <p class="report-section-desc">50 transaksi terbaru dalam periode</p>
            </div>
        </div>
        <div class="collapse show" id="ordersCollapse">
            <div class="hub-card-body p-0">
                <div class="report-table-scroll">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>No. Pesanan</th>
                                <th class="num">Gross</th>
                                <th class="num">Net</th>
                                <th class="num">HPP</th>
                                <th class="num">Laba Kotor</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = array_slice($orders, 0, 50); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($o['date']); ?></td>
                                <td><code class="small"><?php echo e($o['order_number']); ?></code></td>
                                <td class="num"><?php echo e(hub_rp($o['gross'] ?? 0)); ?></td>
                                <td class="num"><?php echo e(hub_rp($o['net'] ?? 0)); ?></td>
                                <td class="num"><?php echo e(hub_rp($o['cogs'] ?? 0)); ?></td>
                                <td class="num <?php echo e(($o['profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg'); ?>"><?php echo e(hub_rp($o['profit'] ?? 0, true)); ?></td>
                                <td><a href="<?php echo e($o['detail_url']); ?>" class="hub-btn hub-btn-sm hub-btn-outline">Detail</a></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const monthly = <?php echo json_encode($monthly ?? [], 15, 512) ?>;
    if (monthly.length && typeof HubCharts !== 'undefined') {
        HubCharts.renderMonthly('chartMonthly', monthly);
    }

    const search = document.getElementById('productSearch');
    const rows = document.querySelectorAll('#productTable tbody tr[data-search]');
    if (search && rows.length) {
        search.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            rows.forEach(tr => {
                tr.style.display = !q || tr.dataset.search.includes(q) ? '' : 'none';
            });
        });
    }
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views\hub\monitoring.blade.php ENDPATH**/ ?>