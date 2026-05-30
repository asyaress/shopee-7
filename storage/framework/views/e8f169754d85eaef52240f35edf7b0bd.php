<?php $__env->startSection('title', 'Laporan Profit (Pro)'); ?>

<?php $__env->startSection('content'); ?>
    <div class="container-fluid px-3 px-md-4 py-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <div>
                <h1 class="h4 mb-1">
                    <i class="fas fa-chart-line me-2"></i>
                    Laporan Profit (Pro)
                </h1>
                <div class="text-muted small">
                    Profit dihitung: <strong>Net (Penghasilan) - HPP - Packaging</strong>.
                    Untuk Shopee, <strong>Net</strong> diambil dari <code>escrow_amount_after_adjustment</code> (atau fallback <code>seller_income</code>) — sama seperti di halaman detail pesanan.
                </div>
            </div>

            <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary btn-sm" href="<?php echo e(request()->fullUrlWithQuery(['export' => 'xlsx'])); ?>">
                    <i class="fas fa-file-excel me-1"></i> Export XLSX
                </a>
                <a class="btn btn-outline-secondary btn-sm" href="<?php echo e(request()->fullUrlWithQuery(['export' => null])); ?>">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </a>
            </div>
        </div>

        
        <div class="card border-0 shadow-sm mb-3">
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
                                <option value="<?php echo e($opt); ?>" <?php echo e($filters['status']===$opt ? 'selected' : ''); ?>>
                                    <?php echo e($opt === 'all' ? 'All' : $opt); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label small text-muted mb-1">Jenis</label>
                        <select name="jenis" class="form-select form-select-sm">
                            <?php $__currentLoopData = $filtersMeta['jenis_options']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($opt); ?>" <?php echo e($filters['jenis']===$opt ? 'selected' : ''); ?>>
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
                                <option value="<?php echo e($p->id); ?>" <?php echo e((string)$filters['product_id']===(string)$p->id ? 'selected' : ''); ?>>
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
                                        <div class="small text-muted"><?php echo e($p['missing_cost_lines'] > 0 ? 'Ada line cost kosong' : 'OK'); ?></div>
                                    </td>
                                    <td class="text-end"><?php echo e(number_format($p['qty'], 0, ',', '.')); ?></td>
                                    <td class="text-end">Rp <?php echo e(number_format($p['net'], 0, ',', '.')); ?></td>
                                    <td class="text-end">Rp <?php echo e(number_format($p['cogs'], 0, ',', '.')); ?></td>
                                    <td class="text-end fw-bold">Rp <?php echo e(number_format($p['profit'], 0, ',', '.')); ?></td>
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
                <div class="fw-semibold">Orders (detail)</div>
                <div class="small text-muted">Menampilkan <?php echo e(min(count($orders), 50)); ?> dari total <?php echo e(count($orders)); ?> orders.</div>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
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
                    <?php $__empty_1 = true; $__currentLoopData = $orders->take(50); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $o): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="text-nowrap"><?php echo e($o['date']); ?></td>
                            <td class="text-nowrap" style="font-family: ui-monospace, SFMono-Regular, Menlo, monospace;">
                                <?php echo e($o['order_number']); ?>

                            </td>
                            <td><?php echo e($o['jenis']); ?></td>
                            <td><span class="badge bg-light text-dark"><?php echo e($o['status']); ?></span></td>
                            <td class="text-end">Rp <?php echo e(number_format($o['gross'], 0, ',', '.')); ?></td>
                            <td class="text-end">Rp <?php echo e(number_format($o['fee'], 0, ',', '.')); ?></td>
                            <td class="text-end">Rp <?php echo e(number_format($o['net'], 0, ',', '.')); ?></td>
                            <td class="text-end">Rp <?php echo e(number_format($o['cogs'], 0, ',', '.')); ?></td>
                            <td class="text-end fw-bold">Rp <?php echo e(number_format($o['profit'], 0, ',', '.')); ?></td>
                            <td class="text-end"><?php echo e(number_format($o['margin'] * 100, 1)); ?>%</td>
                            <td class="text-end"><?php echo e(number_format($o['take_rate'] * 100, 1)); ?>%</td>
                            <td>
                                <?php if($o['missing_cost']): ?>
                                    <span class="badge bg-warning text-dark">Missing</span>
                                <?php else: ?>
                                    <span class="badge bg-success">OK</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="12" class="text-center text-muted py-4">Tidak ada data untuk filter ini</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script>
        const trend = <?php echo json_encode($chart_trend, 15, 512) ?>;
        const fees = <?php echo json_encode($fees_breakdown, 15, 512) ?>;
        const top = <?php echo json_encode($chart_top_products, 15, 512) ?>;

        const ctxTrend = document.getElementById('trendChart');
        if (ctxTrend) {
            new Chart(ctxTrend, {
                type: 'line',
                data: {
                    labels: trend.labels,
                    datasets: [
                        { label: 'Gross', data: trend.gross },
                        { label: 'Net', data: trend.net },
                        { label: 'Profit', data: trend.profit },
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
        if (ctxFee) {
            new Chart(ctxFee, {
                type: 'doughnut',
                data: {
                    labels: ['Admin', 'Layanan', 'Proses', 'Lainnya'],
                    datasets: [{ data: [fees.admin, fees.layanan, fees.proses, fees.lainnya] }]
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
        if (ctxTop) {
            new Chart(ctxTop, {
                type: 'bar',
                data: {
                    labels: top.labels,
                    datasets: [{ label: 'Profit', data: top.profit }]
                },
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
                        y: {
                            ticks: {
                                callback: (v) => 'Rp ' + Number(v).toLocaleString('id-ID')
                            }
                        }
                    }
                }
            });
        }
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views\customers\reports\profit_pro.blade.php ENDPATH**/ ?>