<?php $__env->startSection('title', 'Detail Customer — Shopee Profit Hub'); ?>

<?php $__env->startSection('content'); ?>
<div class="report-shell">
    <?php echo $__env->make('hub.partials.page-hero', [
        'icon' => 'fa-user',
        'title' => $customer->name,
        'subtitle' => $customer->company ?: ($customer->type == 'company' ? 'Perusahaan' : 'Individual'),
        'meta' => [
            ['icon' => 'fa-shopping-cart', 'text' => $orders->count() . ' pesanan'],
            ['icon' => 'fa-calendar', 'text' => 'Sejak ' . $customer->created_at->format('d M Y')],
        ],
        'actions' => '<a href="' . route('customers.edit', $customer) . '" class="hub-btn hub-btn-primary" style="background:#fff;color:var(--maroon-800)!important;"><i class="fas fa-edit"></i> Edit</a>'
            . '<a href="' . route('customers.index') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-arrow-left"></i> Daftar</a>',
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <div class="row g-3">
            <!-- Left Column - Customer Info -->
            <div class="col-lg-4">
                <!-- Customer Profile Card -->
                <div class="hub-card mb-4">
                    <div class="hub-card-header text-center">
                        <div class="avatar mx-auto mb-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto"
                                style="width: 100px; height: 100px; background: rgba(255,255,255,0.2); color: white;">
                                <i class="fas fa-<?php echo e($customer->type == 'company' ? 'building' : 'user'); ?> fa-3x"></i>
                            </div>
                        </div>
                        <h4 class="mb-1"><?php echo e($customer->name); ?></h4>
                        <?php if($customer->company): ?>
                            <p class="mb-2" style="opacity: 0.9;"><?php echo e($customer->company); ?></p>
                        <?php endif; ?>
                        <span class="badge <?php echo e($customer->type == 'company' ? 'bg-info' : 'bg-secondary'); ?> fs-6">
                            <i class="fas fa-<?php echo e($customer->type == 'company' ? 'building' : 'user'); ?> me-1"></i>
                            <?php echo e($customer->type == 'company' ? 'Perusahaan' : 'Individual'); ?>

                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row text-center mb-3">
                            <div class="col-6">
                                <div class="stat-number text-primary"><?php echo e($orders->count()); ?></div>
                                <small class="text-muted">Total Pesanan</small>
                            </div>
                            <div class="col-6">
                                <div class="stat-number text-success">
                                    Rp <?php echo e(number_format($orders->sum('total_amount'), 0, ',', '.')); ?>

                                </div>
                                <small class="text-muted">Total Transaksi</small>
                            </div>
                        </div>

                        <hr>

                        <!-- Contact Information -->
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-address-card me-2 text-primary"></i>Informasi Kontak
                        </h6>

                        <?php if($customer->phone): ?>
                            <div class="mb-2">
                                <i class="fas fa-phone text-muted me-2"></i>
                                <a href="tel:<?php echo e($customer->phone); ?>" class="text-decoration-none"><?php echo e($customer->phone); ?></a>
                            </div>
                        <?php endif; ?>

                        <?php if($customer->email): ?>
                            <div class="mb-2">
                                <i class="fas fa-envelope text-muted me-2"></i>
                                <a href="mailto:<?php echo e($customer->email); ?>" class="text-decoration-none"><?php echo e($customer->email); ?></a>
                            </div>
                        <?php endif; ?>

                        <?php if($customer->address): ?>
                            <div class="mb-2">
                                <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                <span><?php echo e($customer->address); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if(!$customer->phone && !$customer->email && !$customer->address): ?>
                            <p class="text-muted mb-0">
                                <i class="fas fa-info-circle me-2"></i>Tidak ada informasi kontak
                            </p>
                        <?php endif; ?>

                        <hr>

                        <!-- Customer Status -->
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-info-circle me-2 text-primary"></i>Status & Info
                        </h6>

                        <div class="mb-2">
                            <strong>Status:</strong>
                            <?php if($customer->is_active): ?>
                                <span class="badge bg-success ms-2">
                                    <i class="fas fa-check-circle me-1"></i>Aktif
                                </span>
                            <?php else: ?>
                                <span class="badge bg-danger ms-2">
                                    <i class="fas fa-times-circle me-1"></i>Tidak Aktif
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="mb-2">
                            <strong>Bergabung:</strong>
                            <span class="ms-2"><?php echo e($customer->created_at->format('d M Y')); ?></span>
                        </div>

                        <div class="mb-2">
                            <strong>Update Terakhir:</strong>
                            <span class="ms-2"><?php echo e($customer->updated_at->format('d M Y H:i')); ?></span>
                        </div>

                        <?php if($customer->notes): ?>
                            <hr>
                            <h6 class="fw-bold mb-2">
                                <i class="fas fa-sticky-note me-2 text-primary"></i>Catatan
                            </h6>
                            <p class="text-muted mb-0"><?php echo e($customer->notes); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="hub-card">
                    <div class="card-header" style="background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                        <h6 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Aksi Cepat
                        </h6>
                    </div>
                    <div class="card-body">
                        <a href="<?php echo e(route('orders.create')); ?>?customer_name=<?php echo e(urlencode($customer->name)); ?>"
                            class="hub-btn hub-btn-primary w-100 mb-2">
                            <i class="fas fa-plus-circle me-2"></i>Buat Pesanan Baru
                        </a>
                        <a href="<?php echo e(route('customers.edit', $customer)); ?>" class="hub-btn hub-btn-outline w-100 mb-2">
                            <i class="fas fa-edit me-2"></i>Edit Data Customer
                        </a>
                        <?php if($customer->phone): ?>
                            <a href="https://wa.me/<?php echo e(preg_replace('/[^0-9]/', '', $customer->phone)); ?>" target="_blank"
                                class="btn btn-outline-success w-100 mb-2">
                                <i class="fab fa-whatsapp me-2"></i>Chat WhatsApp
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column - Orders History -->
            <div class="col-lg-8">
                <div class="hub-card">
                    <div class="hub-card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>Riwayat Pesanan (<?php echo e($orders->count()); ?>)
                        </h5>
                        <?php if($orders->count() > 0): ?>
                            <span class="badge bg-light text-dark">
                                Total: Rp <?php echo e(number_format($orders->sum('total_amount'), 0, ',', '.')); ?>

                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if($orders->count() > 0): ?>
                            <div class="table-responsive">
                                <table class="report-table table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th class="border-0 ps-4">No. Pesanan</th>
                                            <th class="border-0">Produk</th>
                                            <th class="border-0">Qty</th>
                                            <th class="border-0">Tanggal</th>
                                            <th class="border-0">Status</th>
                                            <th class="border-0">Total</th>
                                            <th class="border-0 pe-4 text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $__currentLoopData = $orders->sortByDesc('created_at'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <strong class="text-maroon"><?php echo e($order->order_number); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo e($order->created_at->diffForHumans()); ?></small>
                                                </td>
                                                <td>
                                                    <div class="fw-bold">
                                                        <?php if($order->orderItems && $order->orderItems->count()): ?>
                                                            <ul class="mb-0 ps-3">
                                                                <?php $__currentLoopData = $order->orderItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                    <li><?php echo e($item->product_name); ?></li>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                            </ul>
                                                        <?php else: ?>
                                                            <?php echo e($order->product_name ?? '-'); ?>

                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if($order->product && $order->product->category): ?>
                                                        <small class="text-muted"><?php echo e($order->product->category); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php if($order->orderItems && $order->orderItems->count()): ?>
                                                            <?php echo e($order->orderItems->sum('quantity')); ?> pcs
                                                        <?php else: ?>
                                                            <?php echo e($order->quantity ?? '-'); ?> pcs
                                                        <?php endif; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div><?php echo e($order->order_date->format('d M Y')); ?></div>
                                                    <small class="text-muted">
                                                        Selesai: <?php echo e($order->completion_date->format('d M')); ?>

                                                    </small>
                                                </td>
                                                <td>
                                                    <?php
                                                        $statusColors = [
                                                            'pending' => 'warning',
                                                            'in_progress' => 'info',
                                                            'completed' => 'success',
                                                            'cancelled' => 'danger'
                                                        ];
                                                    ?>
                                                    <span class="badge bg-<?php echo e($statusColors[$order->status] ?? 'secondary'); ?>">
                                                        <?php echo e($order->status_indonesian); ?>

                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if($order->total_amount): ?>
                                                        <div class="fw-bold" class="text-maroon">
                                                            Rp <?php echo e(number_format($order->total_amount, 0, ',', '.')); ?>

                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="pe-4 text-center">
                                                    <div class="btn-group" role="group">
                                                        <a href="<?php echo e(route('orders.show', $order)); ?>"
                                                            class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="<?php echo e(route('orders.edit', $order)); ?>"
                                                            class="btn btn-sm btn-outline-warning" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">Belum Ada Pesanan</h5>
                                <p class="text-muted">Customer ini belum pernah melakukan pesanan</p>
                                <a href="<?php echo e(route('orders.create')); ?>?customer_name=<?php echo e(urlencode($customer->name)); ?>"
                                    class="hub-btn hub-btn-primary">
                                    <i class="fas fa-plus-circle me-2"></i>Buat Pesanan Pertama
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script>
        // Auto-select customer in order create if coming from customer detail
        document.addEventListener('DOMContentLoaded', function () {
            // Any additional customer detail page scripts can go here
            console.log('Customer detail page loaded for: <?php echo e($customer->name); ?>');
        });
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views\customers\show.blade.php ENDPATH**/ ?>