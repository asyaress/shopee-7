<?php $__env->startSection('title', 'Kelola Pesanan — Shopee Profit Hub'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $orderTotal = $orders->count();
    $completed = $orders->where('status', 'completed')->count();
    $pending = $orders->where('status', 'pending')->count();
    $inProgress = $orders->whereIn('status', ['in_progress', 'processing'])->count();
?>
<div class="report-shell">
    <?php echo $__env->make('hub.partials.page-hero', [
        'icon' => 'fa-shopping-cart',
        'title' => 'Kelola Pesanan',
        'subtitle' => 'Semua pesanan toko — manual & sinkron Shopee',
        'meta' => [
            ['icon' => 'fa-list', 'text' => $orderTotal . ' pesanan (filter aktif)'],
            ['icon' => 'fa-check', 'text' => $completed . ' selesai'],
        ],
        'actions' => '<a href="' . route('orders.create') . '" class="hub-btn hub-btn-primary" style="background:#fff;color:var(--maroon-800)!important;"><i class="fas fa-plus"></i> Tambah</a>'
            . '<button type="button" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)" onclick="exportData()"><i class="fas fa-download"></i> Export</button>',
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <div class="report-kpi-hero" style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));">
        <div class="report-kpi-card"><div class="label">Total</div><div class="value"><?php echo e(hub_num($orderTotal)); ?></div></div>
        <div class="report-kpi-card positive"><div class="label">Selesai</div><div class="value"><?php echo e(hub_num($completed)); ?></div></div>
        <div class="report-kpi-card warn"><div class="label">Menunggu</div><div class="value"><?php echo e(hub_num($pending)); ?></div></div>
        <div class="report-kpi-card"><div class="label">Proses</div><div class="value"><?php echo e(hub_num($inProgress)); ?></div></div>
    </div>

    <div class="report-filter-card">
        <form method="GET" action="<?php echo e(route('orders.index')); ?>" class="row g-3">
            <div class="col-12 col-sm-6 col-lg-3">
                <label class="hub-form-label">Cari Pesanan</label>
                <input type="text" name="search" class="hub-form-control" placeholder="No. pesanan, produk, customer..." value="<?php echo e(request('search')); ?>">
            </div>
            <div class="col-6 col-lg-2">
                <label class="hub-form-label">Status</label>
                <select name="status" class="hub-form-select hub-form-control">
                    <option value="">Semua Status</option>
                    <option value="pending" <?php if(request('status') == 'pending'): echo 'selected'; endif; ?>>Menunggu</option>
                    <option value="in_progress" <?php if(request('status') == 'in_progress'): echo 'selected'; endif; ?>>Sedang Proses</option>
                    <option value="completed" <?php if(request('status') == 'completed'): echo 'selected'; endif; ?>>Selesai</option>
                    <option value="cancelled" <?php if(request('status') == 'cancelled'): echo 'selected'; endif; ?>>Dibatalkan</option>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="hub-form-label">Customer</label>
                <select name="customer_name" class="hub-form-select hub-form-control">
                    <option value="">Semua Customer</option>
                    <?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($customer->name); ?>" <?php if(request('customer_name') == $customer->name): echo 'selected'; endif; ?>><?php echo e($customer->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="hub-form-label">Dari Tanggal</label>
                <input type="date" name="date_from" class="hub-form-control" value="<?php echo e(request('date_from')); ?>">
            </div>
            <div class="col-6 col-lg-2">
                <label class="hub-form-label">Sampai Tanggal</label>
                <input type="date" name="date_to" class="hub-form-control" value="<?php echo e(request('date_to')); ?>">
            </div>
            <div class="col-12 col-lg-1 d-flex align-items-end">
                <button type="submit" class="hub-btn hub-btn-primary w-100"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>

    <div class="hub-card">
        <div class="hub-card-header">
            <h2 class="report-section-title"><i class="fas fa-table me-2"></i>Daftar Pesanan</h2>
            <span class="hub-pill hub-pill-muted"><?php echo e($orderTotal); ?> baris</span>
        </div>
        <div class="hub-card-body p-0 hub-dt-wrap">
                        <?php if($orders->count() > 0): ?>
                            <div class="table-responsive">
                                <table id="ordersTable" class="table hub-dt-table mb-0 nowrap" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th class="border-0 ps-4">No. Pesanan</th>
                                            <th class="border-0">Customer</th>
                                            <th class="border-0">Jenis Pengiriman</th>
                                            <th class="border-0">Jenis Transaksi</th>
                                            <th class="border-0">Produk</th>
                                            <th class="border-0">Qty</th>
                                            <th class="border-0">Tanggal</th>
                                            <th class="border-0">Deadline</th>
                                            <th class="border-0">Status</th>
                                            <th class="border-0">Total</th>
                                            <th class="border-0 pe-4 text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="hub-dt-primary"><?php echo e($order->order_number); ?></span>
                                                    <span class="hub-dt-sub"><?php echo e($order->created_at->diffForHumans()); ?></span>
                                                </td>
                                                <td>
                                                    <div class="hub-dt-user">
                                                        <div class="hub-dt-avatar"><i class="fas fa-user"></i></div>
                                                        <span class="fw-semibold"><?php echo e($order->customer_name); ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if($order->jenis_pengiriman): ?>
                                                        <span class="hub-pill hub-pill-muted"><?php echo e($order->jenis_pengiriman); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($order->jenis_transaksi): ?>
                                                        <span class="hub-pill <?php echo e(strtolower($order->jenis_transaksi) === 'shopee' ? 'hub-pill-warning' : ''); ?>"><?php echo e($order->jenis_transaksi); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($order->orderItems->count() > 0): ?>
                                                        <ul class="hub-dt-product-list">
                                                            <?php $__currentLoopData = $order->orderItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <li>
                                                                    <span class="name"><?php echo e($item->product_name); ?></span>
                                                                    <?php if($item->product && $item->product->category): ?>
                                                                        <span class="meta">(<?php echo e($item->product->category); ?>)</span>
                                                                    <?php endif; ?>
                                                                    <?php if($item->price): ?>
                                                                        <span class="meta">@ <?php echo e(hub_rp($item->price)); ?></span>
                                                                    <?php endif; ?>
                                                                </li>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </ul>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($order->orderItems->count() > 0): ?>
                                                        <ul class="hub-dt-product-list mb-0">
                                                            <?php $__currentLoopData = $order->orderItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <li><?php echo e($item->quantity); ?> pcs</li>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </ul>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div><?php echo e($order->order_date->format('d M Y')); ?></div>
                                                    <small class="text-muted"><?php echo e($order->order_date->diffForHumans()); ?></small>
                                                </td>
                                                <td>
                                                    <div><?php echo e($order->completion_date->format('d M Y')); ?></div>
                                                    <?php
                                                        $daysLeft = now()->diffInDays($order->completion_date, false);
                                                    ?>
                                                    <?php if($daysLeft < 0): ?>
                                                        <small class="text-danger">Terlambat <?php echo e(abs($daysLeft)); ?> hari</small>
                                                    <?php elseif($daysLeft == 0): ?>
                                                        <small class="text-warning">Hari ini!</small>
                                                    <?php elseif($daysLeft <= 3): ?>
                                                        <small class="text-warning"><?php echo e($daysLeft); ?> hari lagi</small>
                                                    <?php else: ?>
                                                        <small class="text-muted"><?php echo e($daysLeft); ?> hari lagi</small>
                                                    <?php endif; ?>
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
                                                    <select class="hub-form-select hub-form-control-sm status-select" data-order-id="<?php echo e($order->id); ?>">
                                                        <option value="pending" <?php echo e($order->status == 'pending' ? 'selected' : ''); ?>>
                                                            Menunggu</option>
                                                        <option value="in_progress" <?php echo e($order->status == 'in_progress' ? 'selected' : ''); ?>>Sedang Proses</option>
                                                        <option value="completed" <?php echo e($order->status == 'completed' ? 'selected' : ''); ?>>Selesai</option>
                                                        <option value="cancelled" <?php echo e($order->status == 'cancelled' ? 'selected' : ''); ?>>Dibatalkan</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <?php if($order->total_amount): ?>
                                                        <span class="hub-dt-amount"><?php echo e(hub_rp($order->total_amount)); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="pe-4 text-center">
                                                    <div class="hub-dt-actions">
                                                        <a href="<?php echo e(route('orders.show', $order)); ?>" class="hub-btn hub-btn-sm hub-btn-outline" title="Detail"><i class="fas fa-eye"></i></a>
                                                        <a href="<?php echo e(route('orders.edit', $order)); ?>" class="hub-btn hub-btn-sm hub-btn-outline" title="Edit"><i class="fas fa-edit"></i></a>
                                                        <button type="button" class="hub-btn hub-btn-sm hub-btn-outline" onclick="deleteOrder(<?php echo e($order->id); ?>)" title="Hapus"><i class="fas fa-trash"></i></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">Tidak ada pesanan ditemukan</h5>
                                <p class="text-muted">Coba ubah filter pencarian atau tambah pesanan baru</p>
                                <a href="<?php echo e(route('orders.create')); ?>" class="hub-btn hub-btn-primary">
                                    <i class="fas fa-plus me-2"></i>Tambah Pesanan Pertama
                                </a>
                            </div>
                        <?php endif; ?>
        </div>
    </div>

    <form id="deleteForm" method="POST" style="display: none;">
        <?php echo csrf_field(); ?>
        <?php echo method_field('DELETE'); ?>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
    <?php echo $__env->make('hub.partials.datatables-assets', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
    <?php echo $__env->make('hub.partials.datatables-scripts', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        HubDataTable.init('#ordersTable', {
            ordering: false,
            pageLength: 10,
            columnDefs: [
                { responsivePriority: 1, targets: 0 },
                { responsivePriority: 2, targets: -1 },
                { responsivePriority: 3, targets: 1 },
                { orderable: false, targets: [2, 3, 4, 5, 7, 8, 10] },
            ],
        });
    });

    // Update status via AJAX
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function () {
            const orderId = this.dataset.orderId;
            const status = this.value;

            fetch(`/orders/${orderId}/update-status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ status: status })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: data.message,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        throw new Error(data.message || 'Terjadi kesalahan');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Gagal mengupdate status: ' + error.message,
                        icon: 'error',
                            confirmButtonColor: '#7f1d1d'
                    });
                    location.reload();
                });
        });
    });

    // Delete order function
    function deleteOrder(orderId) {
        Swal.fire({
            title: 'Hapus Pesanan?',
            text: 'Data pesanan akan dihapus permanen dan tidak dapat dikembalikan!',
            icon: 'warning',
            showCancelButton: true,
                            confirmButtonColor: '#7f1d1d',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('deleteForm');
                form.action = `/orders/${orderId}`;
                form.submit();
            }
        });
    }

    // Export data function
    function exportData() {
        const currentUrl = new URL(window.location.href);
        const exportUrl = new URL('<?php echo e(route('orders.export')); ?>', window.location.origin);
        currentUrl.searchParams.forEach((value, key) => {
            if (key !== 'page') {
                exportUrl.searchParams.append(key, value);
            }
        });
        Swal.fire({
            title: 'Export Data Pesanan',
            text: 'Data akan diexport sesuai dengan filter yang aktif',
            icon: 'info',
            showCancelButton: true,
                            confirmButtonColor: '#7f1d1d',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Export Excel',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = exportUrl.toString();
            }
        });
    }
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views\orders\index.blade.php ENDPATH**/ ?>