<?php $__env->startSection('title', 'Laporan Profit'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $marginPct = ($summary['net_income'] ?? 0) > 0 ? (($summary['profit'] ?? 0) / ($summary['net_income'] ?? 1)) * 100 : 0;
    $takeRatePct = ($summary['gross_sales'] ?? 0) > 0 ? (($summary['fees_total'] ?? 0) / ($summary['gross_sales'] ?? 1)) * 100 : 0;

    $fmt = function($n){
        return 'Rp ' . number_format((float)$n, 0, ',', '.');
    };
?>

<div class="report-shell">
    <?php echo $__env->make('hub.partials.page-hero', [
        'icon' => 'fa-chart-pie',
        'title' => 'Laporan Profit',
        'subtitle' => 'Net − HPP − Packaging (Shopee: seller income setelah fee)',
        'actions' => '<a href="' . route('monitoring.index') . '" class="hub-btn hub-btn-primary" style="background:#fff;color:var(--maroon-800)!important;"><i class="fas fa-chart-line"></i> Monitoring Baru</a>'
            . '<a href="' . route('reports.profit.export.orders', request()->query()) . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-file-excel"></i> Orders</a>'
            . '<a href="' . route('products.costs') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-tags"></i> HPP</a>',
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    
    <div class="hub-card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label fw-semibold mb-1">Start</label>
                    <input type="date" class="form-control" name="start_date"
                           value="<?php echo e($filters['start']->toDateString()); ?>">
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label fw-semibold mb-1">End</label>
                    <input type="date" class="form-control" name="end_date"
                           value="<?php echo e($filters['end']->toDateString()); ?>">
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label fw-semibold mb-1">Status</label>
                    <select class="form-select" name="status">
                        <?php $st = $filters['status'] ?? 'completed'; ?>
                        <option value="completed" <?php echo e($st==='completed' ? 'selected':''); ?>>completed</option>
                        <option value="pending" <?php echo e($st==='pending' ? 'selected':''); ?>>pending</option>
                        <option value="in_progress" <?php echo e($st==='in_progress' ? 'selected':''); ?>>in_progress</option>
                        <option value="cancelled" <?php echo e($st==='cancelled' ? 'selected':''); ?>>cancelled</option>
                        <option value="all" <?php echo e($st==='all' ? 'selected':''); ?>>all</option>
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label fw-semibold mb-1">Jenis</label>
                    <select class="form-select" name="jenis_transaksi">
                        <?php $jt = $filters['jenis'] ?? 'All'; ?>
                        <option value="All" <?php echo e($jt==='All' ? 'selected':''); ?>>All</option>
                        <?php $__currentLoopData = $jenisList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $j): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($j); ?>" <?php echo e($jt===$j ? 'selected':''); ?>><?php echo e($j); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label fw-semibold mb-1">Produk</label>
                    <select class="form-select" name="product_id">
                        <option value="">All</option>
                        <?php $__currentLoopData = $productsForFilter; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($p->id); ?>" <?php echo e((int)($filters['product_id'] ?? 0) === (int)$p->id ? 'selected':''); ?>>
                                <?php echo e($p->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-1 d-grid">
                    <button class="hub-btn hub-btn-primary">
                        <i class="fas fa-filter me-1"></i>Apply
                    </button>
                </div>
            </form>
        </div>
    </div>

    
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="hub-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Gross Sales (Harga Produk)</div>
                    <div class="h4 fw-bold mb-0"><?php echo e($fmt($summary['gross_sales'] ?? 0)); ?></div>
                    <div class="text-muted small mt-1">
                        Orders: <b><?php echo e(number_format($summary['orders_count'] ?? 0)); ?></b>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="hub-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Fees Total</div>
                    <div class="h4 fw-bold mb-0"><?php echo e($fmt($summary['fees_total'] ?? 0)); ?></div>
                    <div class="text-muted small mt-1">
                        Take rate: <b><?php echo e(number_format($takeRatePct, 1)); ?>%</b>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="hub-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Net Income (Penghasilan)</div>
                    <div class="h4 fw-bold mb-0"><?php echo e($fmt($summary['net_income'] ?? 0)); ?></div>
                    <div class="text-muted small mt-1">Net = setelah fee (Shopee)</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="hub-card h-100">
                <div class="card-body">
                    <div class="text-muted small">COGS (HPP + Packaging)</div>
                    <div class="h4 fw-bold mb-0"><?php echo e($fmt($summary['cogs'] ?? 0)); ?></div>
                    <div class="text-muted small mt-1">
                        Missing cost orders: <b><?php echo e(number_format($summary['missing_cost_orders'] ?? 0)); ?></b>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="hub-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Profit</div>
                    <div class="h3 fw-bold mb-0 <?php echo e(($summary['profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger'); ?>">
                        <?php echo e($fmt($summary['profit'] ?? 0)); ?>

                    </div>
                    <div class="text-muted small mt-1">Profit = Net - COGS</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="hub-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Margin</div>
                    <div class="h3 fw-bold mb-0"><?php echo e(number_format($marginPct, 1)); ?>%</div>
                    <div class="text-muted small mt-1">Margin = Profit / Net</div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-8">
            <div class="hub-card">
                <div class="card-header bg-light">
                    <div class="fw-bold"><i class="fas fa-chart-area me-2"></i>Trend (Gross / Net / Profit)</div>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="110"></canvas>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="hub-card h-100">
                <div class="card-header bg-light">
                    <div class="fw-bold"><i class="fas fa-percentage me-2"></i>Fee Breakdown</div>
                </div>
                <div class="card-body">
                    <canvas id="feeChart" height="220"></canvas>
                    <div class="text-muted small mt-2">
                        Admin: <?php echo e($fmt($summary['fee_commission'] ?? 0)); ?> •
                        Layanan: <?php echo e($fmt($summary['fee_service'] ?? 0)); ?> •
                        Proses: <?php echo e($fmt($summary['fee_transaction'] ?? 0)); ?> •
                        Lainnya: <?php echo e($fmt($summary['fee_other'] ?? 0)); ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-7">
            <div class="hub-card">
                <div class="card-header bg-light">
                    <div class="fw-bold"><i class="fas fa-trophy me-2"></i>Top Products by Profit (alokasi)</div>
                </div>
                <div class="card-body">
                    <canvas id="topProductsChart" height="160"></canvas>
                    <div class="text-muted small mt-2">
                        Catatan: Untuk order multi-produk, Net dialokasikan proporsional berdasarkan gross line item.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="hub-card h-100">
                <div class="card-header bg-light">
                    <div class="fw-bold"><i class="fas fa-list me-2"></i>Ringkasan Produk</div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Net</th>
                                <th class="text-end">COGS</th>
                                <th class="text-end">Profit</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $topProductsTable; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="text-truncate" style="max-width:240px;"><?php echo e($r['name']); ?></td>
                                    <td class="text-end"><?php echo e(number_format($r['qty'] ?? 0)); ?></td>
                                    <td class="text-end"><?php echo e($fmt($r['net'] ?? 0)); ?></td>
                                    <td class="text-end"><?php echo e($fmt($r['cogs'] ?? 0)); ?></td>
                                    <td class="text-end fw-bold <?php echo e(($r['profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger'); ?>">
                                        <?php echo e($fmt($r['profit'] ?? 0)); ?>

                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="5" class="text-muted text-center py-3">Belum ada data.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="hub-card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="fw-bold"><i class="fas fa-receipt me-2"></i>Orders (detail)</div>
            <div class="text-muted small">
                Menampilkan <?php echo e($orders->count()); ?> dari total <?php echo e($orders->total()); ?> orders.
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Order</th>
                        <th>Jenis</th>
                        <th>Status</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">Fee</th>
                        <th class="text-end">Net</th>
                        <th class="text-end">COGS</th>
                        <th class="text-end">Profit</th>
                        <th class="text-end">Margin</th>
                        <th class="text-end">Take Rate</th>
                        <th>Cost Data</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $m = $orderRows[$o->id] ?? null;
                            $profit = $m['profit'] ?? 0;
                            $missing = !empty($m['missing_cost']);
                        ?>
                        <tr>
                            <td class="text-nowrap"><?php echo e(optional($o->order_date)->format('d M Y')); ?></td>
                            <td class="text-nowrap">
                                <a href="<?php echo e(route('orders.show', $o)); ?>" class="fw-semibold">
                                    <?php echo e($o->order_number); ?>

                                </a>
                            </td>
                            <td class="text-nowrap"><?php echo e($o->jenis_transaksi ?? '-'); ?></td>
                            <td><span class="badge bg-secondary"><?php echo e($o->status); ?></span></td>

                            <td class="text-end"><?php echo e($fmt($m['gross'] ?? 0)); ?></td>
                            <td class="text-end"><?php echo e($fmt(($m['fees']['total'] ?? 0))); ?></td>
                            <td class="text-end"><?php echo e($fmt($m['net'] ?? 0)); ?></td>
                            <td class="text-end"><?php echo e($fmt($m['cogs'] ?? 0)); ?></td>

                            <td class="text-end fw-bold <?php echo e($profit >= 0 ? 'text-success' : 'text-danger'); ?>">
                                <?php echo e($fmt($profit)); ?>

                            </td>
                            <td class="text-end"><?php echo e(number_format($m['margin_pct'] ?? 0, 1)); ?>%</td>
                            <td class="text-end"><?php echo e(number_format($m['take_rate_pct'] ?? 0, 1)); ?>%</td>

                            <td class="text-nowrap">
                                <?php if($missing): ?>
                                    <span class="badge bg-warning text-dark" title="Ada item yang belum punya HPP/Packaging atau % tapi harga 0">Missing</span>
                                <?php else: ?>
                                    <span class="badge bg-success">OK</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="12" class="text-muted text-center py-4">Belum ada order untuk filter ini.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="p-3">
                <div class="hub-pagination">
                    <span class="hub-pagination-info">
                        <?php if($orders->total()): ?>
                            Menampilkan <?php echo e($orders->firstItem()); ?>–<?php echo e($orders->lastItem()); ?> dari <?php echo e($orders->total()); ?> data
                        <?php endif; ?>
                    </span>
                    <?php echo e($orders->withQueryString()->links()); ?>

                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(() => {
  const chartData = <?php echo json_encode($chart, 15, 512) ?>;

  // Trend
  const ctxTrend = document.getElementById('trendChart');
  if (ctxTrend) {
    new Chart(ctxTrend, {
      type: 'line',
      data: {
        labels: chartData.labels || [],
        datasets: [
          { label: 'Gross', data: chartData.gross || [], tension: 0.25 },
          { label: 'Net', data: chartData.net || [], tension: 0.25 },
          { label: 'Profit', data: chartData.profit || [], tension: 0.25 },
        ]
      },
      options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'bottom' } },
        scales: {
          y: { ticks: { callback: (v) => new Intl.NumberFormat('id-ID').format(v) } }
        }
      }
    });
  }

  // Fee breakdown
  const ctxFee = document.getElementById('feeChart');
  if (ctxFee) {
    const f = chartData.fee || {};
    new Chart(ctxFee, {
      type: 'doughnut',
      data: {
        labels: ['Admin', 'Layanan', 'Proses', 'Lainnya'],
        datasets: [{
          data: [f.commission || 0, f.service || 0, f.transaction || 0, f.other || 0],
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
      }
    });
  }

  // Top products
  const ctxTop = document.getElementById('topProductsChart');
  if (ctxTop) {
    const t = chartData.top || {};
    new Chart(ctxTop, {
      type: 'bar',
      data: {
        labels: t.labels || [],
        datasets: [
          { label: 'Profit', data: t.profit || [] },
          { label: 'Net', data: t.net || [] },
          { label: 'COGS', data: t.cogs || [] },
        ]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: {
          y: { ticks: { callback: (v) => new Intl.NumberFormat('id-ID').format(v) } }
        }
      }
    });
  }
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views\reports\profit.blade.php ENDPATH**/ ?>