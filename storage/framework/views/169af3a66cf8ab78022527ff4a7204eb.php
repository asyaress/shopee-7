<?php $__env->startSection('title', 'Detail Pesanan — Shopee Profit Hub'); ?>

<?php $__env->startSection('content'); ?>
<div class="report-shell order-detail-page">
    <?php echo $__env->make('hub.partials.page-hero', [
        'icon' => 'fa-file-invoice',
        'title' => 'Detail Pesanan',
        'subtitle' => $order->order_number,
        'meta' => [
            ['icon' => 'fa-circle', 'text' => $order->status_indonesian],
            ['icon' => 'fa-user', 'text' => $order->customer_name],
        ],
        'actions' => '<a href="' . route('orders.edit', $order) . '" class="hub-btn hub-btn-primary" style="background:#fff;color:var(--maroon-800)!important;"><i class="fas fa-edit"></i> Edit</a>'
            . '<button type="button" onclick="printOrder()" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-print"></i></button>'
            . '<a href="' . route('orders.index') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-arrow-left"></i></a>',
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <div class="row g-3 g-lg-4">
            
            <div class="col-12 col-lg-8 order-2 order-lg-1">
                
                <div class="hub-card mb-3 mb-md-4">
                    <div class="hub-card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>Informasi Pesanan
                        </h5>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3 g-md-4">
                            <div class="col-12 col-md-6">
                                <div class="info-group">
                                    <div class="info-item">
                                        <label class="info-label">Nomor Pesanan:</label>
                                        <div class="info-value">
                                            <span class="badge bg-primary"><?php echo e($order->order_number); ?></span>
                                            <button class="btn btn-sm btn-outline-secondary ms-2"
                                                    onclick="copyToClipboard('<?php echo e($order->order_number); ?>')"
                                                    title="Copy">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Tanggal Pemesanan:</label>
                                        <div class="info-value">
                                            <?php echo e($order->order_date->format('d F Y')); ?>

                                            <small class="text-muted d-block d-sm-inline ms-sm-1">
                                                (<?php echo e($order->order_date->diffForHumans()); ?>)
                                            </small>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Tanggal Selesai:</label>
                                        <div class="info-value">
                                            <?php echo e($order->completion_date->format('d F Y')); ?>

                                            <?php
                                                $daysLeft = now()->diffInDays($order->completion_date, false);
                                            ?>
                                            <?php if($order->status !== 'completed' && $order->status !== 'cancelled'): ?>
                                                <?php if($daysLeft < 0): ?>
                                                    <span class="badge bg-danger ms-2">Terlambat <?php echo e(abs($daysLeft)); ?> hari</span>
                                                <?php elseif($daysLeft == 0): ?>
                                                    <span class="badge bg-warning ms-2">Hari ini!</span>
                                                <?php elseif($daysLeft <= 3): ?>
                                                    <span class="badge bg-warning ms-2"><?php echo e($daysLeft); ?> hari lagi</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success ms-2"><?php echo e($daysLeft); ?> hari lagi</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Durasi Pengerjaan:</label>
                                        <div class="info-value"><?php echo e($order->duration); ?> hari</div>
                                    </div>
                                    <div class="info-item">
                                        <label class="info-label">Jenis Pengiriman:</label>
                                        <div class="info-value"><?php echo e($order->jenis_pengiriman ?? '-'); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="info-group">
                                    <div class="info-item">
                                        <label class="info-label">Status:</label>
                                        <div class="info-value">
                                            <select class="form-select form-select-sm status-select w-auto"
                                                    data-order-id="<?php echo e($order->id); ?>">
                                                <option value="pending" <?php echo e($order->status == 'pending' ? 'selected' : ''); ?>>Menunggu</option>
                                                <option value="in_progress" <?php echo e($order->status == 'in_progress' ? 'selected' : ''); ?>>Sedang Proses</option>
                                                <option value="completed" <?php echo e($order->status == 'completed' ? 'selected' : ''); ?>>Selesai</option>
                                                <option value="cancelled" <?php echo e($order->status == 'cancelled' ? 'selected' : ''); ?>>Dibatalkan</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="info-item">
                                        <label class="info-label">Dibuat:</label>
                                        <div class="info-value">
                                            <?php echo e($order->created_at->format('d F Y H:i')); ?>

                                            <small class="text-muted d-block d-sm-inline ms-sm-1">
                                                (<?php echo e($order->created_at->diffForHumans()); ?>)
                                            </small>
                                        </div>
                                    </div>

                                    <div class="info-item">
                                        <label class="info-label">Terakhir Diupdate:</label>
                                        <div class="info-value">
                                            <?php echo e($order->updated_at->format('d F Y H:i')); ?>

                                            <small class="text-muted d-block d-sm-inline ms-sm-1">
                                                (<?php echo e($order->updated_at->diffForHumans()); ?>)
                                            </small>
                                        </div>
                                    </div>

                                    <div class="info-item">
                                        <label class="info-label">Progress:</label>
                                        <div class="info-value">
                                            <?php
                                                $progress = match ($order->status) {
                                                    'pending' => 25,
                                                    'in_progress' => 75,
                                                    'completed' => 100,
                                                    'cancelled' => 0,
                                                    default => 0
                                                };
                                                $progressColor = match ($order->status) {
                                                    'pending' => 'warning',
                                                    'in_progress' => 'info',
                                                    'completed' => 'success',
                                                    'cancelled' => 'danger',
                                                    default => 'secondary'
                                                };
                                            ?>
                                            <div class="progress mb-1" style="height: 8px;">
                                                <div class="progress-bar bg-<?php echo e($progressColor); ?>"
                                                     role="progressbar"
                                                     style="width: <?php echo e($progress); ?>%"
                                                     aria-valuenow="<?php echo e($progress); ?>"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100">
                                                </div>
                                            </div>
                                            <small class="text-muted"><?php echo e($progress); ?>% selesai</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                
                <div class="hub-card mb-3 mb-md-4">
                    <div class="card-header-light">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-user me-2"></i>Informasi Customer
                        </h6>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <div class="customer-info">
                            <div class="customer-avatar">
                                <div class="avatar-circle">
                                    <i class="fas fa-user fa-lg"></i>
                                </div>
                            </div>
                            <div class="customer-details">
                                <h6 class="customer-name mb-1"><?php echo e($order->customer_name); ?></h6>
                                <?php if($order->customer_company): ?>
                                    <p class="customer-company text-muted mb-2"><?php echo e($order->customer_company); ?></p>
                                <?php endif; ?>
                                <?php if($order->customer_type): ?>
                                    <span class="badge badge-customer-type <?php echo e($order->customer_type == 'company' ? 'bg-info' : 'bg-secondary'); ?> mb-2">
                                        <?php echo e($order->customer_type == 'company' ? 'Perusahaan' : 'Individual'); ?>

                                    </span>
                                <?php endif; ?>
                                <div class="customer-contact">
                                    <?php if($order->customer_phone): ?>
                                        <div class="contact-item">
                                            <i class="fas fa-phone text-muted me-2"></i>
                                            <a href="tel:<?php echo e($order->customer_phone); ?>" class="contact-link">
                                                <?php echo e($order->customer_phone); ?>

                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    <?php if($order->customer_email): ?>
                                        <div class="contact-item">
                                            <i class="fas fa-envelope text-muted me-2"></i>
                                            <a href="mailto:<?php echo e($order->customer_email); ?>" class="contact-link">
                                                <?php echo e($order->customer_email); ?>

                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    <?php if($order->customer_address): ?>
                                        <div class="contact-item">
                                            <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                            <span class="contact-text" title="<?php echo e($order->customer_address); ?>">
                                                <?php echo e(Str::limit($order->customer_address, 100)); ?>

                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                
                <div class="hub-card mb-3 mb-md-4">
                    <div class="card-header-light">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-box me-2"></i>Informasi Produk
                        </h6>
                    </div>
                    <div class="card-body p-3 p-md-4">
                        <?php if($order->orderItems && $order->orderItems->count() > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-products mb-0">
                                    <thead>
                                    <tr>
                                        <th>Nama Produk</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end d-none d-md-table-cell">Harga Satuan</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php $__currentLoopData = $order->orderItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td>
                                                <div class="product-name"><?php echo e($item->product_name); ?></div>
                                                <small class="text-muted d-md-none">
                                                    @ Rp <?php echo e(number_format($item->price, 0, ',', '.')); ?>

                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark">
                                                    <?php echo e($item->quantity); ?> <?php echo e(optional($item->product)->unit ?? 'pcs'); ?>

                                                </span>
                                            </td>
                                            <td class="text-end d-none d-md-table-cell">
                                                Rp <?php echo e(number_format($item->price, 0, ',', '.')); ?>

                                            </td>
                                            <td class="text-end fw-bold">
                                                Rp <?php echo e(number_format($item->total_amount, 0, ',', '.')); ?>

                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-box-open fa-2x mb-3 opacity-50"></i>
                                <p class="mb-0">Tidak ada detail produk.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                
                <?php if($order->notes): ?>
                    <div class="hub-card mb-3 mb-md-4">
                        <div class="card-header-warning">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-sticky-note me-2"></i>Catatan Pesanan
                            </h6>
                        </div>
                        <div class="card-body p-3 p-md-4">
                            <p class="mb-0 notes-text"><?php echo e($order->notes); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            
            <div class="col-12 col-lg-4 order-1 order-lg-2">
                
                <div class="right-panel">
                    
                    <div class="hub-card card-price-summary mb-3 mb-md-4">
                        <div class="hub-card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-calculator me-2"></i>Ringkasan Harga
                            </h6>
                        </div>
                        <div class="card-body p-3 p-md-4">
                            <?php if($order->total_amount): ?>
                                
                                <div class="price-summary-section mb-3">
                                    <div class="summary-row">
                                        <span class="summary-label">Total Item:</span>
                                        <span class="summary-value"><?php echo e($order->orderItems->sum('quantity')); ?> pcs</span>
                                    </div>
                                </div>

                                <hr class="my-3">

                                
                                <div class="price-summary-section mb-3">
                                    <div class="summary-row summary-row-total">
                                        <span class="summary-label">Total Harga:</span>
                                        <span class="summary-value">
                                            Rp <?php echo e(number_format($order->total_amount, 0, ',', '.')); ?>

                                        </span>
                                    </div>
                                </div>

                                
                                <?php if(($order->jenis_transaksi ?? '') === 'Shopee' && $order->shopeeFinancial): ?>
                                    <?php
                                        try {
                                            /** @var \App\Models\ShopeeOrderFinancial $fin */
                                            $fin = $order->shopeeFinancial;

                                            // Parse raw data
                                            $raw = $fin->raw;
                                            if (is_string($raw)) {
                                                $decoded = json_decode($raw, true);
                                                if (json_last_error() === JSON_ERROR_NONE) {
                                                    $raw = $decoded;
                                                } else {
                                                    $raw = [];
                                                }
                                            }

                                            $pay = data_get($raw, 'buyer_payment_info', []);
                                            $income = data_get($raw, 'order_income', []);

                                            // Calculate amounts
                                            $hargaProduk = (float) data_get($income, 'order_selling_price', data_get($pay, 'merchant_subtotal', 0));
                                            $subtotalPesanan = $hargaProduk;
                                            $gross = (float) data_get($income, 'buyer_total_amount', data_get($pay, 'buyer_total_amount', $fin->buyer_total_amount ?? 0));

                                            // Fees - Biaya Lainnya
                                            $biayaAdmin = abs((float) data_get($income, 'commission_fee', 0));
                                            $biayaProgramHemat = abs((float) data_get($income, 'shipping_seller_protection_fee_amount', 0));
                                            $biayaLayanan = abs((float) data_get($income, 'service_fee', 0));
                                            $biayaProses = abs((float) data_get($income, 'seller_order_processing_fee', 0));
                                            $biayaPembayaranPembeli = abs((float) data_get($income, 'buyer_transaction_fee', data_get($income, 'credit_card_transaction_fee', 0)));

                                            $biayaLainnya = $biayaAdmin + $biayaProgramHemat + $biayaLayanan + $biayaProses;

                                            // Net income
                                            $net = (float) data_get($income, 'escrow_amount_after_adjustment', data_get($income, 'escrow_amount', $fin->seller_income ?? 0));

                                            $hasShopeeData = true;
                                        } catch (\Exception $e) {
                                            $hasShopeeData = false;
                                            \Log::error('Error parsing Shopee financial data: ' . $e->getMessage());
                                        }
                                    ?>

                                    <?php if($hasShopeeData ?? false): ?>
                                        <hr class="my-3">

                                        
                                        <div class="shopee-header mb-3">
                                            <h6 class="shopee-title mb-2">
                                                <i class="fab fa-shopify me-2 text-orange"></i>
                                                Shopee – Rincian Keuangan
                                            </h6>
                                            <p class="shopee-description text-muted small mb-0">
                                                Data diambil dari endpoint Shopee dan disesuaikan dengan format Seller Center
                                            </p>
                                        </div>

                                        
                                        <div class="shopee-financial">
                                            
                                            <div class="financial-section mb-3">
                                                <div class="financial-row">
                                                    <span class="financial-label">Subtotal Pesanan</span>
                                                    <span class="financial-value">
                                                        Rp <?php echo e(number_format($subtotalPesanan, 0, ',', '.')); ?>

                                                    </span>
                                                </div>
                                                <div class="financial-row">
                                                    <span class="financial-label">Harga Produk</span>
                                                    <span class="financial-value">
                                                        Rp <?php echo e(number_format($hargaProduk, 0, ',', '.')); ?>

                                                    </span>
                                                </div>
                                                <div class="financial-row">
                                                    <span class="financial-label">Pembeli Bayar (Gross)</span>
                                                    <span class="financial-value financial-value-highlight">
                                                        Rp <?php echo e(number_format($gross, 0, ',', '.')); ?>

                                                    </span>
                                                </div>
                                            </div>

                                            
                                            <div class="financial-section financial-section-fees mb-3">
                                                <div class="fees-header mb-2">
                                                    <h6 class="fees-title mb-0">Biaya Lainnya</h6>
                                                </div>

                                                
                                                <div class="financial-row financial-row-total-fees">
                                                    <span class="financial-label fw-bold">Total Biaya Lainnya</span>
                                                    <span class="financial-value fw-bold text-danger">
                                                        - Rp <?php echo e(number_format($biayaLainnya, 0, ',', '.')); ?>

                                                    </span>
                                                </div>

                                                
                                                <div class="fees-details">
                                                    <?php if($biayaAdmin > 0): ?>
                                                        <div class="financial-row financial-row-sm">
                                                            <span class="financial-label">Biaya Administrasi</span>
                                                            <span class="financial-value text-danger">
                                                                - Rp <?php echo e(number_format($biayaAdmin, 0, ',', '.')); ?>

                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if($biayaProgramHemat > 0): ?>
                                                        <div class="financial-row financial-row-sm">
                                                            <span class="financial-label">Biaya Program Hemat</span>
                                                            <span class="financial-value text-danger">
                                                                - Rp <?php echo e(number_format($biayaProgramHemat, 0, ',', '.')); ?>

                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if($biayaLayanan > 0): ?>
                                                        <div class="financial-row financial-row-sm">
                                                            <span class="financial-label">Biaya Layanan</span>
                                                            <span class="financial-value text-danger">
                                                                - Rp <?php echo e(number_format($biayaLayanan, 0, ',', '.')); ?>

                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if($biayaProses > 0): ?>
                                                        <div class="financial-row financial-row-sm">
                                                            <span class="financial-label">Biaya Proses Pesanan</span>
                                                            <span class="financial-value text-danger">
                                                                - Rp <?php echo e(number_format($biayaProses, 0, ',', '.')); ?>

                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            
                                            <div class="financial-section financial-section-net">
                                                <div class="financial-row financial-row-net">
                                                    <span class="financial-label fw-bold">Estimasi Total Penghasilan</span>
                                                    <span class="financial-value fw-bold text-success">
                                                        Rp <?php echo e(number_format($net, 0, ',', '.')); ?>

                                                    </span>
                                                </div>
                                            </div>

                                            
                                            <div class="shopee-raw-data mt-3">
                                                <details class="raw-data-details">
                                                    <summary class="raw-data-summary">
                                                        <i class="fas fa-code me-2"></i>
                                                        <span>Lihat raw data Shopee</span>
                                                    </summary>
                                                    <div class="raw-data-content">
                                                        <pre class="raw-data-pre"><?php echo e(json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                                                        <?php if($biayaPembayaranPembeli > 0): ?>
                                                            <div class="alert alert-info alert-sm mt-2 mb-0">
                                                                <small>
                                                                    <strong>Catatan:</strong> Biaya pembayaran (Rp <?php echo e(number_format($biayaPembayaranPembeli, 0, ',', '.')); ?>)
                                                                    adalah biaya channel di sisi pembeli dan tidak termasuk dalam "Biaya Lainnya" seller.
                                                                </small>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </details>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning alert-sm mb-0">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <small>Data Shopee tidak dapat diproses. Silakan periksa kembali.</small>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                
                                <div class="no-price-data text-center py-4">
                                    <i class="fas fa-info-circle fa-2x mb-3 text-muted opacity-50"></i>
                                    <p class="text-muted mb-0">
                                        Harga akan ditentukan setelah konsultasi dengan customer
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    
                    <div class="hub-card mb-3 mb-md-4">
                        <div class="card-header-light">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-clock me-2"></i>Timeline Pesanan
                            </h6>
                        </div>
                        <div class="card-body p-3 p-md-4">
                            <div class="timeline">
                                <div class="timeline-item <?php echo e($order->status == 'pending' ? 'active' : 'completed'); ?>">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">Pesanan Dibuat</h6>
                                        <small class="timeline-date"><?php echo e($order->created_at->format('d M Y H:i')); ?></small>
                                    </div>
                                </div>

                                <div class="timeline-item <?php echo e($order->status == 'in_progress' ? 'active' : ($order->status == 'completed' ? 'completed' : '')); ?>">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">Sedang Diproses</h6>
                                        <small class="timeline-date">
                                            <?php if($order->status == 'in_progress' || $order->status == 'completed'): ?>
                                                Dalam pengerjaan
                                            <?php else: ?>
                                                Menunggu konfirmasi
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>

                                <div class="timeline-item <?php echo e($order->status == 'completed' ? 'completed' : ''); ?>">
                                    <div class="timeline-marker"></div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">Pesanan Selesai</h6>
                                        <small class="timeline-date">
                                            <?php if($order->status == 'completed'): ?>
                                                Selesai
                                            <?php else: ?>
                                                Target: <?php echo e($order->completion_date->format('d M Y')); ?>

                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="hub-card mb-0">
                        <div class="card-header-light">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-bolt me-2"></i>Aksi Cepat
                            </h6>
                        </div>
                        <div class="card-body p-3 p-md-4">
                            <div class="d-grid gap-2">
                                <a href="<?php echo e(route('orders.edit', $order)); ?>" class="btn btn-warning">
                                    <i class="fas fa-edit me-2"></i>Edit Pesanan
                                </a>
                                <?php if($order->customer_phone): ?>
                                    <a href="https://wa.me/<?php echo e(preg_replace('/[^0-9]/', '', $order->customer_phone)); ?>?text=Halo%20<?php echo e(urlencode($order->customer_name)); ?>,%20mengenai%20pesanan%20<?php echo e(urlencode($order->order_number)); ?>"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="btn btn-success">
                                        <i class="fab fa-whatsapp me-2"></i>Chat Customer
                                    </a>
                                <?php endif; ?>
                                <button class="btn btn-outline-primary" onclick="printOrder()">
                                    <i class="fas fa-print me-2"></i>Print Detail
                                </button>
                                <a href="<?php echo e(route('orders.create')); ?>?duplicate=<?php echo e($order->id); ?>"
                                   class="btn btn-outline-secondary">
                                    <i class="fas fa-copy me-2"></i>Duplikasi Pesanan
                                </a>
                                <?php if($order->status !== 'completed' && $order->status !== 'cancelled'): ?>
                                    <button class="btn btn-outline-danger" onclick="cancelOrder(<?php echo e($order->id); ?>)">
                                        <i class="fas fa-times me-2"></i>Batalkan Pesanan
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>
</div>

    
    <style>
        :root {
            --accent-green: var(--maroon-600);
            --light-green: var(--maroon-50);
            --dark-green: var(--maroon-800);
            --text-orange: #f97316;
        }

        /* =========================
           HEADER SECTION
        ========================= */
        .order-header {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .page-title {
            color: var(--dark-green);
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0;
        }

        .order-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
        }

        .order-number {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .btn-group-custom {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        /* =========================
           CARD STYLES
        ========================= */
        .card-modern {
            border: none;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .order-detail-page .hub-card-header {
            background: linear-gradient(135deg, var(--maroon-700) 0%, var(--maroon-600) 100%);
            color: white;
            padding: 1rem 1.25rem;
            border: none;
        }

        .card-header-light {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 0.75rem 1.25rem;
        }

        .card-header-warning {
            background: #fff3cd;
            border-bottom: 1px solid #ffeaa7;
            padding: 0.75rem 1.25rem;
        }

        .card-title {
            font-size: 0.95rem;
            font-weight: 600;
            margin: 0;
        }

        /* =========================
           INFO GROUPS
        ========================= */
        .info-group {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .info-label {
            font-weight: 600;
            color: #6b7280;
            font-size: 0.875rem;
            margin: 0;
        }

        .info-value {
            color: #1f2937;
            font-size: 0.95rem;
        }

        /* =========================
           CUSTOMER INFO
        ========================= */
        .customer-info {
            display: flex;
            gap: 1rem;
        }

        .customer-avatar {
            flex-shrink: 0;
        }

        .avatar-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--light-green);
            color: var(--dark-green);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .customer-details {
            flex: 1;
            min-width: 0;
        }

        .customer-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0;
        }

        .customer-company {
            font-size: 0.9rem;
        }

        .customer-contact {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
        }

        .contact-link {
            color: #3b82f6;
            text-decoration: none;
        }

        .contact-link:hover {
            text-decoration: underline;
        }

        .contact-text {
            color: #6b7280;
        }

        /* =========================
           PRODUCT TABLE
        ========================= */
        .table-products {
            font-size: 0.9rem;
        }

        .table-products thead th {
            background: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
            padding: 0.75rem;
        }

        .table-products tbody td {
            padding: 0.75rem;
            vertical-align: middle;
        }

        .product-name {
            font-weight: 500;
        }

        /* =========================
           ✅ RIGHT PANEL (CONTAINER PUTIH KANAN)
        ========================= */
        .right-panel {
            background: rgba(255, 255, 255, 0.92);
            border-radius: 14px;
            padding: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,.08);
        }

        .right-panel > .card {
            margin-bottom: 12px !important;
        }

        .right-panel > .card:last-child {
            margin-bottom: 0 !important;
        }

        /* =========================
           PRICE SUMMARY - STICKY (DESKTOP) + SCROLL INTERNAL
        ========================= */
        /* =========================
   STICKY RIGHT PANEL (DESKTOP)
   - yang sticky wrapper kanan, bukan card ringkasan
   - scroll terjadi di dalam panel kanan
========================= */
@media (min-width: 992px) {
  .right-panel {
    position: sticky;
    top: 1rem; /* kalau ada navbar fixed, ganti misal: 80px */
    max-height: calc(100vh - 2rem);
    overflow-y: auto;
    overscroll-behavior: contain;
    scrollbar-gutter: stable;
  }

  /* pastikan ringkasan harga tidak sticky lagi */
  .card-price-summary {
    position: static;
  }

  .card-price-summary .card-body {
    max-height: none;
    overflow: visible;
  }
}

        .price-summary-section {
            padding: 0.5rem 0;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            font-size: 0.95rem;
        }

        .summary-row-total {
            padding: 0.75rem 0;
        }

        .summary-row-total .summary-label {
            font-size: 1.1rem;
            color: var(--dark-green);
            font-weight: 700;
        }

        .summary-row-total .summary-value {
            font-size: 1.3rem;
            color: var(--dark-green);
            font-weight: 700;
        }

        .summary-label {
            color: #6b7280;
            font-weight: 500;
        }

        .summary-value {
            color: #1f2937;
            font-weight: 600;
        }

        /* =========================
           SHOPEE FINANCIAL - RESPONSIVE
        ========================= */
        .shopee-header {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 6px;
            padding: 0.75rem;
        }

        .shopee-title {
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }

        .text-orange { color: var(--text-orange); }

        .shopee-description { line-height: 1.4; }

        .shopee-financial { font-size: 0.9rem; }

        .financial-section { padding: 0.5rem 0; }

        .financial-section-fees {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            padding: 0.75rem;
        }

        .financial-section-net {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 0.75rem;
        }

        .fees-header {
            border-bottom: 1px solid #fecaca;
            padding-bottom: 0.5rem;
        }

        .fees-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #991b1b;
        }

        .financial-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            gap: 1rem;
        }

        .financial-row-sm {
            padding: 0.35rem 0;
            font-size: 0.85rem;
        }

        .financial-row-total-fees {
            border-bottom: 1px solid #fecaca;
            padding-bottom: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .financial-row-net { padding: 0.75rem 0; }

        .financial-label {
            color: #6b7280;
            flex: 1;
            min-width: 0;
        }

        .financial-value {
            color: #1f2937;
            font-weight: 600;
            text-align: right;
            white-space: nowrap;
        }

        .financial-value-highlight { color: #1d4ed8; }

        .fees-details { padding-left: 0.5rem; }

        /* =========================
           RAW DATA
        ========================= */
        .shopee-raw-data { margin-top: 1rem; }

        .raw-data-details {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
        }

        .raw-data-summary {
            background: #f9fafb;
            padding: 0.75rem;
            cursor: pointer;
            user-select: none;
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 500;
        }

        .raw-data-summary:hover { background: #f3f4f6; }

        .raw-data-content {
            padding: 0.75rem;
            background: white;
        }

        .raw-data-pre {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 0.75rem;
            margin: 0;
            font-size: 0.75rem;
            line-height: 1.4;
            max-height: 300px;
            overflow: auto;
            white-space: pre;
            font-family: 'Courier New', monospace;
        }

        .alert-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            margin: 0;
        }

        /* =========================
           TIMELINE
        ========================= */
        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .timeline-item:last-child { margin-bottom: 0; }

        .timeline-item:before {
            content: '';
            position: absolute;
            left: -30px;
            top: 8px;
            bottom: -1.5rem;
            width: 2px;
            background: #dee2e6;
        }

        .timeline-item:last-child:before { display: none; }

        .timeline-marker {
            position: absolute;
            left: -36px;
            top: 8px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #dee2e6;
            border: 3px solid #fff;
            box-shadow: 0 0 0 2px #dee2e6;
        }

        .timeline-item.active .timeline-marker {
            background: #fbbf24;
            box-shadow: 0 0 0 2px #fbbf24;
        }

        .timeline-item.completed .timeline-marker {
            background: #10b981;
            box-shadow: 0 0 0 2px #10b981;
        }

        .timeline-title {
            font-size: 0.95rem;
            font-weight: 600;
            margin: 0 0 0.25rem 0;
        }

        .timeline-date {
            color: #6b7280;
            font-size: 0.85rem;
        }

        /* =========================
           STATUS BADGES
        ========================= */
        .status-badge {
            font-size: 0.85rem;
            padding: 0.35rem 0.75rem;
            font-weight: 500;
        }

        .status-pending { background-color: #fbbf24; color: #78350f; }
        .status-in_progress { background-color: #3b82f6; color: white; }
        .status-completed { background-color: #10b981; color: white; }
        .status-cancelled { background-color: #ef4444; color: white; }

        /* =========================
           BUTTONS
        ========================= */
        .btn-outline-red {
            color: var(--accent-green);
            border-color: var(--accent-green);
        }

        .btn-outline-red:hover {
            background-color: var(--accent-green);
            color: white;
        }

        /* =========================
           PRINT STYLES
        ========================= */
        @media print {
            .btn,
            .navbar,
            .sidebar,
            .header-actions,
            .card-price-summary .btn,
            .raw-data-details,
            .shopee-raw-data {
                display: none !important;
            }

            .card {
                border: 1px solid #ddd !important;
                box-shadow: none !important;
                page-break-inside: avoid;
            }

            .container-fluid {
                margin: 0 !important;
                padding: 0 !important;
            }

            .col-lg-8,
            .col-lg-4 {
                width: 100% !important;
                float: none !important;
            }

            .order-2 { order: 1 !important; }
            .order-1 { order: 2 !important; }

            .page-title { font-size: 1.5rem; }

            .financial-section { page-break-inside: avoid; }
        }

        /* =========================
           RESPONSIVE BREAKPOINTS
        ========================= */
        @media (max-width: 575.98px) {
            .page-title { font-size: 1.25rem; }
            .order-header { padding: 0.75rem; }
            .card-body { padding: 1rem !important; }

            .financial-row { gap: 0.5rem; }

            .financial-label,
            .financial-value { font-size: 0.85rem; }

            .summary-row-total .summary-label,
            .summary-row-total .summary-value { font-size: 1rem; }

            .financial-row-net .financial-label,
            .financial-row-net .financial-value { font-size: 0.95rem; }

            .shopee-header { padding: 0.5rem; }
            .shopee-title { font-size: 0.9rem; }
            .shopee-description { font-size: 0.75rem; }

            .avatar-circle { width: 50px; height: 50px; }
            .customer-name { font-size: 1rem; }

            .raw-data-pre { font-size: 0.7rem; max-height: 200px; }

            .btn-group-custom .btn { flex: 1; min-width: 0; }
        }

        @media (min-width: 576px) and (max-width: 991.98px) {
            .header-content {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
        }

        @media (min-width: 992px) {
            .header-content {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }

            .page-title { font-size: 1.75rem; }

            .info-item {
                flex-direction: row;
                justify-content: space-between;
                align-items: flex-start;
            }

            .info-label { width: 40%; flex-shrink: 0; }
            .info-value { width: 60%; text-align: right; }
        }

        @media (min-width: 1200px) {
            .financial-row { gap: 1rem; }
            .shopee-financial { font-size: 0.95rem; }
        }

        .no-price-data { padding: 2rem 1rem; }
        .notes-text { white-space: pre-wrap; word-wrap: break-word; }

        .status-select:disabled { opacity: 0.6; cursor: not-allowed; }

        .btn,
        .badge,
        .card { transition: all 0.2s ease; }

        .financial-row:hover { background-color: rgba(0, 0, 0, 0.02); }

        .btn:focus,
        .form-select:focus {
            outline: 2px solid var(--accent-green);
            outline-offset: 2px;
        }

        details[open] .raw-data-summary { border-bottom: 1px solid #e5e7eb; }
    </style>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script>
        'use strict';

        const CONFIG = {
            endpoints: {
                updateStatus: '/orders/:id/update-status'
            },
            messages: {
                success: {
                    statusUpdated: 'Status pesanan berhasil diperbarui',
                    orderCancelled: 'Pesanan telah berhasil dibatalkan',
                    copied: 'Nomor pesanan telah disalin'
                },
                error: {
                    updateFailed: 'Gagal mengupdate status',
                    cancelFailed: 'Gagal membatalkan pesanan',
                    generic: 'Terjadi kesalahan'
                },
                confirm: {
                    cancel: {
                        title: 'Batalkan Pesanan?',
                        text: 'Pesanan akan dibatalkan dan statusnya tidak dapat dikembalikan',
                        confirmText: 'Ya, Batalkan!',
                        cancelText: 'Tidak'
                    }
                }
            }
        };

        function showNotification(type, title, text, timer = 2000) {
            return Swal.fire({
                icon: type,
                title: title,
                text: text,
                timer: timer,
                showConfirmButton: timer > 3000,
                confirmButtonColor: '#7f1d1d'
            });
        }

        async function makeRequest(url, options = {}) {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    ...options.headers
                },
                ...options
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || CONFIG.messages.error.generic);
            }

            return data;
        }

        async function updateOrderStatus(orderId, status) {
            const url = CONFIG.endpoints.updateStatus.replace(':id', orderId);

            try {
                const data = await makeRequest(url, {
                    method: 'PATCH',
                    body: JSON.stringify({ status })
                });

                await showNotification(
                    'success',
                    'Berhasil!',
                    data.message || CONFIG.messages.success.statusUpdated
                );

                setTimeout(() => location.reload(), 1500);
            } catch (error) {
                await showNotification(
                    'error',
                    'Error!',
                    `${CONFIG.messages.error.updateFailed}: ${error.message}`
                );
                location.reload();
            }
        }

        async function cancelOrder(orderId) {
            try {
                const result = await Swal.fire({
                    title: CONFIG.messages.confirm.cancel.title,
                    text: CONFIG.messages.confirm.cancel.text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#7f1d1d',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: CONFIG.messages.confirm.cancel.confirmText,
                    cancelButtonText: CONFIG.messages.confirm.cancel.cancelText
                });

                if (result.isConfirmed) {
                    await updateOrderStatus(orderId, 'cancelled');
                }
            } catch (error) {
                await showNotification('error', 'Error!', CONFIG.messages.error.cancelFailed);
            }
        }

        async function copyToClipboard(text) {
            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(text);
                } else {
                    const textarea = document.createElement('textarea');
                    textarea.value = text;
                    textarea.style.position = 'fixed';
                    textarea.style.opacity = '0';
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                }

                await showNotification(
                    'success',
                    'Tersalin!',
                    `${CONFIG.messages.success.copied}: ${text}`,
                    1500
                );
            } catch (error) {
                await showNotification('error', 'Error!', 'Gagal menyalin ke clipboard', 1500);
            }
        }

        function printOrder() {
            window.print();
        }

        function initializeStatusSelects() {
            const selects = document.querySelectorAll('.status-select');

            selects.forEach(select => {
                select.addEventListener('change', async function() {
                    const orderId = this.dataset.orderId;
                    const newStatus = this.value;
                    const originalValue = this.getAttribute('data-original-value') || this.value;

                    this.disabled = true;

                    try {
                        await updateOrderStatus(orderId, newStatus);
                    } catch (error) {
                        this.value = originalValue;
                        this.disabled = false;
                    }
                });

                select.setAttribute('data-original-value', select.value);
            });
        }

        function initializePage() {
            if (typeof Swal === 'undefined') {
                console.error('SweetAlert2 is required but not loaded');
                return;
            }

            initializeStatusSelects();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializePage);
        } else {
            initializePage();
        }

        window.cancelOrder = cancelOrder;
        window.copyToClipboard = copyToClipboard;
        window.printOrder = printOrder;
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views\orders\show.blade.php ENDPATH**/ ?>