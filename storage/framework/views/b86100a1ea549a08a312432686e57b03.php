<?php $__env->startSection('title', 'Laporan Profit (Pro)'); ?>

<?php $__env->startSection('content'); ?>
<?php
  // Defensive defaults
  $filtersMeta = $filtersMeta ?? [
      'status_options' => ['all','pending','in_progress','completed','cancelled'],
      'jenis_options'   => ['all','shopee','non_shopee'],
      'products'        => $products ?? collect(),
  ];

  $filters = $filters ?? [
      'start' => now()->subDays(30)->format('Y-m-d'),
      'end' => now()->format('Y-m-d'),
      'status' => 'completed',
      'jenis' => 'all',
      'product_id' => null,
  ];

  $summary = $summary ?? [
      'gross_sales' => 0,
      'orders_count' => 0,
      'fees_total' => 0,
      'take_rate' => 0, // ratio
      'net_income' => 0,
      'cogs' => 0,
      'missing_cost_orders' => 0,
      'profit' => 0,
      'margin' => 0, // ratio
  ];

  $fees_breakdown = $fees_breakdown ?? ['admin' => 0, 'layanan' => 0, 'proses' => 0, 'lainnya' => 0];
  $top_products = $top_products ?? [];
  $orders = $orders ?? collect();
  $chart_trend = $chart_trend ?? ['labels' => [], 'gross' => [], 'net' => [], 'profit' => []];
  $chart_top_products = $chart_top_products ?? ['labels' => [], 'profit' => [], 'net' => [], 'cogs' => []];
?>

<div class="report-shell">
    <?php echo $__env->make('hub.partials.page-hero', [
        'icon' => 'fa-chart-line',
        'title' => 'Laporan Profit (Pro)',
        'subtitle' => 'Net − HPP − Packaging · data escrow Shopee',
        'actions' => '<a href="' . route('monitoring.index') . '" class="hub-btn hub-btn-primary" style="background:#fff;color:var(--maroon-800)!important;"><i class="fas fa-chart-line"></i> Monitoring</a>',
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <div class="report-filter-card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted mb-1">Start</label>
                    <input type="date" name="start" value="<?php echo e($filters['start']); ?>" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted mb-1">End</label>
                    <input type="date" name="end" value="<?php echo e($filters['end']); ?>" class="form-control form-control-sm">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <?php $__currentLoopData = $filtersMeta['status_options']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($opt); ?>" <?php echo e(($filters['status'] ?? 'completed')===$opt ? 'selected' : ''); ?>>
                                <?php echo e($opt === 'all' ? 'All' : $opt); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label small text-muted mb-1">Jenis</label>
                    <select name="jenis" class="form-select form-select-sm">
                        <?php $__currentLoopData = $filtersMeta['jenis_options']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($opt); ?>" <?php echo e(($filters['jenis'] ?? 'all')===$opt ? 'selected' : ''); ?>>
                                <?php echo e($opt === 'all' ? 'All' : ($opt === 'shopee' ? 'Shopee' : 'Non-Shopee')); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted mb-1">Produk</label>
                    <select name="product_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php $__currentLoopData = $filtersMeta['products']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($p->id); ?>" <?php echo e((string)($filters['product_id'] ?? '')===(string)$p->id ? 'selected' : ''); ?>>
                                <?php echo e($p->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-12 col-md-1 d-grid">
                    <button class="btn btn-sm btn-primary">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    
    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Gross Sales (Harga Produk)</div>
                    <div class="fs-5 fw-bold">Rp <?php echo e(number_format($summary['gross_sales'], 0, ',', '.')); ?></div>
                    <div class="text-muted small">Orders: <?php echo e($summary['orders_count']); ?></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Fees Total</div>
                    <div class="fs-5 fw-bold">Rp <?php echo e(number_format($summary['fees_total'], 0, ',', '.')); ?></div>
                    <div class="text-muted small">Take rate: <?php echo e(number_format($summary['take_rate'] * 100, 1)); ?>%</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Net Income (Penghasilan)</div>
                    <div class="fs-5 fw-bold">Rp <?php echo e(number_format($summary['net_income'], 0, ',', '.')); ?></div>
                    <div class="text-muted small">Net = setelah fee (Shopee)</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">COGS (HPP + Packaging)</div>
                    <div class="fs-5 fw-bold">Rp <?php echo e(number_format($summary['cogs'], 0, ',', '.')); ?></div>
                    <div class="text-muted small">Missing cost orders: <?php echo e($summary['missing_cost_orders']); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Profit</div>
                    <div class="fs-4 fw-bold">Rp <?php echo e(number_format($summary['profit'], 0, ',', '.')); ?></div>
                    <div class="text-muted small">Profit = Net - COGS</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Margin</div>
                    <div class="fs-4 fw-bold"><?php echo e(number_format($summary['margin'] * 100, 1)); ?>%</div>
                    <div class="text-muted small">Margin = Profit / Net</div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <div class="fw-semibold">Trend (Gross / Net / Profit)</div>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="110"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <div class="fw-semibold">Fee Breakdown</div>
                </div>
                <div class="card-body">
                    <canvas id="feeChart" height="180"></canvas>
                    <div class="small text-muted mt-2">
                        Admin: Rp <?php echo e(number_format($fees_breakdown['admin'], 0, ',', '.')); ?> •
                        Layanan: Rp <?php echo e(number_format($fees_breakdown['layanan'], 0, ',', '.')); ?> •
                        Proses: Rp <?php echo e(number_format($fees_breakdown['proses'], 0, ',', '.')); ?> •
                        Lainnya: Rp <?php echo e(number_format($fees_breakdown['lainnya'], 0, ',', '.')); ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <div class="fw-semibold">Top Products by Profit (alokasi)</div>
                    <div class="small text-muted">Catatan: Untuk order multi-produk, Net dialokasikan proporsional berdasarkan gross line item.</div>
                </div>
                <div class="card-body">
                    <canvas id="topProductsChart" height="260"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <div class="fw-semibold">Ringkasan Produk</div>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-sm align-middle mb-0">
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
                        <?php $__empty_1 = true; $__currentLoopData = $top_products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td style="min-width: 260px;">
                                    <div class="fw-semibold"><?php echo e($p['name']); ?></div>
                                    <div class="small text-muted"><?php echo e(!empty($p['missing_cost_lines']) ? 'Ada line cost kosong' : 'OK'); ?></div>
                                </td>
                                <td class="text-end"><?php echo e(number_format((int)($p['qty'] ?? 0), 0, ',', '.')); ?></td>
                                <td class="text-end">Rp <?php echo e(number_format((float)($p['net'] ?? 0), 0, ',', '.')); ?></td>
                                <td class="text-end">Rp <?php echo e(number_format((float)($p['cogs'] ?? 0), 0, ',', '.')); ?></td>
                                <td class="text-end fw-bold">Rp <?php echo e(number_format((float)($p['profit'] ?? 0), 0, ',', '.')); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <div class="fw-semibold">Orders (detail)</div>

                <button id="btnOpenAll" type="button" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-angle-down me-1"></i>Buka semua
                </button>
                <button id="btnCloseAll" type="button" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-angle-up me-1"></i>Tutup semua
                </button>
            </div>

            <div class="small text-muted">
                Menampilkan <?php echo e(min(count($orders), 50)); ?> dari total <?php echo e(count($orders)); ?> orders.
            </div>
        </div>

        <div class="card-body table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead>
                <tr>
                    <th style="width: 84px;">Aksi</th>
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
                    <th>Cost</th>
                </tr>
                </thead>

                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $orders->take(50); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $collapseId = 'orderDetail_' . $loop->index . '_' . preg_replace('/[^a-zA-Z0-9_]/', '_', (string)($o['order_number'] ?? ''));
                        $items = $o['items'] ?? [];
                        $isShopee = strtolower((string)($o['jenis'] ?? '')) === 'shopee';
                        $shopee = $o['shopee'] ?? null;
                    ?>

                    
                    <tr>
                        <td class="text-nowrap">
                            
                            <button class="btn btn-sm btn-outline-primary"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#<?php echo e($collapseId); ?>"
                                    data-toggle="collapse"
                                    data-target="#<?php echo e($collapseId); ?>"
                                    aria-controls="<?php echo e($collapseId); ?>"
                                    aria-expanded="false">
                                Detail
                            </button>
                        </td>

                        <td class="text-nowrap"><?php echo e($o['date']); ?></td>

                        <td class="text-nowrap" style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace;">
                            <?php echo e($o['order_number']); ?>

                        </td>

                        <td class="text-nowrap"><?php echo e($o['jenis']); ?></td>

                        <td class="text-nowrap">
                            <span class="badge bg-light text-dark"><?php echo e($o['status']); ?></span>
                        </td>

                        <td class="text-end">Rp <?php echo e(number_format((float)$o['gross'], 0, ',', '.')); ?></td>
                        <td class="text-end">Rp <?php echo e(number_format((float)$o['fee'], 0, ',', '.')); ?></td>
                        <td class="text-end">Rp <?php echo e(number_format((float)$o['net'], 0, ',', '.')); ?></td>
                        <td class="text-end">Rp <?php echo e(number_format((float)$o['cogs'], 0, ',', '.')); ?></td>
                        <td class="text-end fw-bold">Rp <?php echo e(number_format((float)$o['profit'], 0, ',', '.')); ?></td>
                        <td class="text-end"><?php echo e(number_format(((float)$o['margin']) * 100, 1)); ?>%</td>
                        <td class="text-end"><?php echo e(number_format(((float)$o['take_rate']) * 100, 1)); ?>%</td>

                        <td class="text-nowrap">
                            <?php if(!empty($o['missing_cost'])): ?>
                                <span class="badge bg-warning text-dark">
                                    Missing<?php echo e(!empty($o['missing_cost_lines']) ? ' ('.(int)$o['missing_cost_lines'].')' : ''); ?>

                                </span>
                            <?php else: ?>
                                <span class="badge bg-success">OK</span>
                            <?php endif; ?>
                        </td>
                    </tr>

                    
                    <tr class="table-light">
                        <td colspan="13" class="p-0 border-0">
                            <div id="<?php echo e($collapseId); ?>" class="collapse order-detail-collapse">
                                <div class="p-3">
                                    <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-2">
                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            <span class="badge bg-dark">
                                                Items: <?php echo e((int)($o['items_count'] ?? count($items))); ?>

                                            </span>

                                            <?php if(!empty($o['missing_cost_lines'])): ?>
                                                <span class="badge bg-warning text-dark">
                                                    Ada line cost kosong: <?php echo e((int)$o['missing_cost_lines']); ?>

                                                </span>
                                            <?php endif; ?>

                                            <?php if(!empty($o['detail_url'])): ?>
                                                <a href="<?php echo e($o['detail_url']); ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">
                                                    <i class="fas fa-external-link-alt me-1"></i>Buka halaman order
                                                </a>
                                            <?php endif; ?>
                                        </div>

                                        <?php if($isShopee && is_array($shopee)): ?>
                                            <div class="small text-muted">
                                                Shopee Net: <strong>Rp <?php echo e(number_format((float)($shopee['net'] ?? 0), 0, ',', '.')); ?></strong> •
                                                Fee Total: <strong>Rp <?php echo e(number_format((float)($shopee['fee_total'] ?? 0), 0, ',', '.')); ?></strong>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if($isShopee && is_array($shopee)): ?>
                                        <div class="row g-2 mb-3">
                                            <div class="col-12 col-lg-6">
                                                <div class="border rounded p-2 bg-white">
                                                    <div class="fw-semibold mb-1">Shopee Breakdown</div>
                                                    <div class="small text-muted">
                                                        Gross Produk: Rp <?php echo e(number_format((float)($shopee['gross_product'] ?? 0), 0, ',', '.')); ?><br>
                                                        Admin: Rp <?php echo e(number_format((float)($shopee['fee_admin'] ?? 0), 0, ',', '.')); ?> •
                                                        Program Hemat: Rp <?php echo e(number_format((float)($shopee['fee_program_hemat'] ?? 0), 0, ',', '.')); ?> •
                                                        Layanan: Rp <?php echo e(number_format((float)($shopee['fee_service'] ?? 0), 0, ',', '.')); ?> •
                                                        Proses: Rp <?php echo e(number_format((float)($shopee['fee_process'] ?? 0), 0, ',', '.')); ?>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0 bg-white">
                                            <thead>
                                            <tr>
                                                <th>Produk</th>
                                                <th class="text-end">Qty</th>
                                                <th class="text-end">Harga</th>
                                                <th class="text-end">Subtotal</th>
                                                <th class="text-end">HPP/Unit</th>
                                                <th class="text-end">Pack/Unit</th>
                                                <th class="text-end">COGS</th>
                                                <th style="width: 90px;">Cost</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php $__empty_2 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $it): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                                                <tr>
                                                    <td style="min-width: 260px;">
                                                        <div class="fw-semibold"><?php echo e($it['product_name'] ?? '-'); ?></div>
                                                    </td>
                                                    <td class="text-end"><?php echo e(number_format((int)($it['qty'] ?? 0), 0, ',', '.')); ?></td>
                                                    <td class="text-end">Rp <?php echo e(number_format((float)($it['unit_price'] ?? 0), 0, ',', '.')); ?></td>
                                                    <td class="text-end">Rp <?php echo e(number_format((float)($it['subtotal'] ?? 0), 0, ',', '.')); ?></td>

                                                    <td class="text-end">Rp <?php echo e(number_format((float)($it['hpp_unit'] ?? 0), 0, ',', '.')); ?></td>
                                                    <td class="text-end">Rp <?php echo e(number_format((float)($it['pack_unit'] ?? 0), 0, ',', '.')); ?></td>
                                                    <td class="text-end fw-semibold">Rp <?php echo e(number_format((float)($it['cogs'] ?? 0), 0, ',', '.')); ?></td>

                                                    <td class="text-nowrap">
                                                        <?php if(!empty($it['missing_cost'])): ?>
                                                            <span class="badge bg-warning text-dark">Missing</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success">OK</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                                                <tr>
                                                    <td colspan="8" class="text-center text-muted py-3">Tidak ada item untuk order ini</td>
                                                </tr>
                                            <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="13" class="text-center text-muted py-4">Tidak ada data untuk filter ini</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<style>
  .order-detail-collapse .table { border-radius: 10px; overflow: hidden; }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(function(){
  'use strict';

  // ---- Chart payloads (safe) ----
  const trendData = <?php echo json_encode($chart_trend, 15, 512) ?>;
  const feesData  = <?php echo json_encode($fees_breakdown, 15, 512) ?>;
  const topProductsData = <?php echo json_encode($chart_top_products, 15, 512) ?>;

  const toArray = (v) => Array.isArray(v) ? v : (v && typeof v === 'object' ? Object.values(v) : []);
  const toNumberArray = (v) => toArray(v).map(x => {
      const n = Number(x);
      return Number.isFinite(n) ? n : 0;
  });

  const trendLabels = toArray(trendData?.labels);
  const trendGross  = toNumberArray(trendData?.gross);
  const trendNet    = toNumberArray(trendData?.net);
  const trendProfit = toNumberArray(trendData?.profit);

  const topLabels = toArray(topProductsData?.labels);
  const topProfit = toNumberArray(topProductsData?.profit);

  if (typeof window.Chart !== 'undefined') {
      const ctxTrend = document.getElementById('trendChart');
      const ctxTrend2d = ctxTrend && ctxTrend.getContext ? ctxTrend.getContext('2d') : null;
      if (ctxTrend2d) {
          new Chart(ctxTrend2d, {
              type: 'line',
              data: {
                  labels: trendLabels,
                  datasets: [
                      { label: 'Gross', data: trendGross },
                      { label: 'Net', data: trendNet },
                      { label: 'Profit', data: trendProfit },
                  ]
              },
              options: {
                  responsive: true,
                  interaction: { mode: 'index', intersect: false },
                  plugins: {
                      legend: { position: 'bottom' },
                      tooltip: {
                          callbacks: {
                              label: function(ctx) {
                                  const v = Number(ctx.raw || 0);
                                  return `${ctx.dataset.label}: Rp ${v.toLocaleString('id-ID')}`;
                              }
                          }
                      }
                  },
                  scales: {
                      y: {
                          ticks: {
                              callback: (v) => 'Rp ' + Number(v).toLocaleString('id-ID')
                          }
                      }
                  }
              }
          });
      }

      const ctxFee = document.getElementById('feeChart');
      const ctxFee2d = ctxFee && ctxFee.getContext ? ctxFee.getContext('2d') : null;
      if (ctxFee2d) {
          new Chart(ctxFee2d, {
              type: 'doughnut',
              data: {
                  labels: ['Admin', 'Layanan', 'Proses', 'Lainnya'],
                  datasets: [{ data: [feesData.admin, feesData.layanan, feesData.proses, feesData.lainnya] }]
              },
              options: {
                  responsive: true,
                  plugins: {
                      legend: { position: 'bottom' },
                      tooltip: {
                          callbacks: {
                              label: function(ctx) {
                                  const v = Number(ctx.raw || 0);
                                  return `${ctx.label}: Rp ${v.toLocaleString('id-ID')}`;
                              }
                          }
                      }
                  }
              }
          });
      }

      const ctxTop = document.getElementById('topProductsChart');
      const ctxTop2d = ctxTop && ctxTop.getContext ? ctxTop.getContext('2d') : null;
      if (ctxTop2d) {
          new Chart(ctxTop2d, {
              type: 'bar',
              data: { labels: topLabels, datasets: [{ label: 'Profit', data: topProfit }] },
              options: {
                  responsive: true,
                  plugins: {
                      legend: { display: false },
                      tooltip: {
                          callbacks: {
                              label: function(ctx) {
                                  const v = Number(ctx.raw || 0);
                                  return `Profit: Rp ${v.toLocaleString('id-ID')}`;
                              }
                          }
                      }
                  },
                  scales: {
                      y: { ticks: { callback: (v) => 'Rp ' + Number(v).toLocaleString('id-ID') } }
                  }
              }
          });
      }
  } else {
      console.warn('Chart.js belum ter-load. Pastikan layout memuat Chart.js.');
  }

  // ---- Open/Close all collapse (Bootstrap 5 + fallback Bootstrap 4 jQuery) ----
  const openBtn = document.getElementById('btnOpenAll');
  const closeBtn = document.getElementById('btnCloseAll');
  if (!openBtn || !closeBtn) return;

  const nodes = () => Array.from(document.querySelectorAll('.order-detail-collapse'));

  const bs5 = (typeof window.bootstrap !== 'undefined' && window.bootstrap.Collapse);
  const bs4 = (typeof window.jQuery !== 'undefined' && window.jQuery.fn && window.jQuery.fn.collapse);

  openBtn.addEventListener('click', () => {
      if (bs5) {
          nodes().forEach(el => window.bootstrap.Collapse.getOrCreateInstance(el, { toggle: false }).show());
      } else if (bs4) {
          nodes().forEach(el => window.jQuery(el).collapse('show'));
      }
  });

  closeBtn.addEventListener('click', () => {
      if (bs5) {
          nodes().forEach(el => window.bootstrap.Collapse.getOrCreateInstance(el, { toggle: false }).hide());
      } else if (bs4) {
          nodes().forEach(el => window.jQuery(el).collapse('hide'));
      }
  });

})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views\reports\profit_pro.blade.php ENDPATH**/ ?>