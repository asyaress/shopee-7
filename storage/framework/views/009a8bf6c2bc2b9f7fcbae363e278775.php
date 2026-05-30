<?php $__env->startSection('title', 'Data Customer — Shopee Profit Hub'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $total = $customers->total();
    $onPage = $customers->count();
?>
<div class="report-shell">
    <?php echo $__env->make('hub.partials.page-hero', [
        'icon' => 'fa-address-book',
        'title' => 'Data Customer',
        'subtitle' => 'Manajemen kontak & riwayat pesanan',
        'meta' => [
            ['icon' => 'fa-users', 'text' => $total . ' customer terdaftar'],
        ],
        'actions' => '<a href="' . route('customers.create') . '" class="hub-btn hub-btn-primary" style="background:#fff;color:var(--maroon-800)!important;"><i class="fas fa-user-plus"></i> Tambah</a>',
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <div class="report-kpi-hero" style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));">
        <div class="report-kpi-card"><div class="label">Total</div><div class="value"><?php echo e(hub_num($total)); ?></div></div>
        <div class="report-kpi-card"><div class="label">Individual</div><div class="value"><?php echo e(hub_num($customers->where('type', 'individual')->count())); ?></div><div class="sub">halaman ini</div></div>
        <div class="report-kpi-card"><div class="label">Perusahaan</div><div class="value"><?php echo e(hub_num($customers->where('type', 'company')->count())); ?></div></div>
        <div class="report-kpi-card positive"><div class="label">Aktif</div><div class="value"><?php echo e(hub_num($customers->where('is_active', true)->count())); ?></div></div>
    </div>

    <div class="report-filter-card">
        <form method="GET" action="<?php echo e(route('customers.index')); ?>" class="row g-3">
            <div class="col-md-4">
                <label class="hub-form-label">Cari Customer</label>
                <input type="text" name="search" class="hub-form-control" placeholder="Nama, perusahaan, email..." value="<?php echo e(request('search')); ?>">
            </div>
            <div class="col-md-3">
                <label class="hub-form-label">Tipe</label>
                <select name="type" class="hub-form-select hub-form-control">
                    <option value="">Semua Tipe</option>
                    <option value="individual" <?php if(request('type') == 'individual'): echo 'selected'; endif; ?>>Individual</option>
                    <option value="company" <?php if(request('type') == 'company'): echo 'selected'; endif; ?>>Perusahaan</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="hub-form-label">Status</label>
                <select name="status" class="hub-form-select hub-form-control">
                    <option value="">Semua</option>
                    <option value="active" <?php if(request('status') == 'active'): echo 'selected'; endif; ?>>Aktif</option>
                    <option value="inactive" <?php if(request('status') == 'inactive'): echo 'selected'; endif; ?>>Tidak Aktif</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="hub-form-label">Urutkan</label>
                <select name="sort" class="hub-form-select hub-form-control">
                    <option value="latest" <?php if(request('sort', 'latest') == 'latest'): echo 'selected'; endif; ?>>Terbaru</option>
                    <option value="oldest" <?php if(request('sort') == 'oldest'): echo 'selected'; endif; ?>>Terlama</option>
                    <option value="name" <?php if(request('sort') == 'name'): echo 'selected'; endif; ?>>Nama A-Z</option>
                    <option value="orders" <?php if(request('sort') == 'orders'): echo 'selected'; endif; ?>>Paling Sering Order</option>
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="hub-btn hub-btn-primary w-100"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>

    <div class="hub-card">
        <div class="hub-card-header">
            <h2 class="report-section-title"><i class="fas fa-table me-2"></i>Daftar Customer</h2>
            <span class="hub-pill hub-pill-muted"><?php echo e($onPage); ?> / <?php echo e($total); ?></span>
        </div>
        <div class="hub-card-body p-0">
            <?php if($customers->count() > 0): ?>
                <div class="table-responsive">
                    <table class="report-table table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Customer</th>
                                <th>Kontak</th>
                                <th>Alamat</th>
                                <th>Tipe</th>
                                <th>Pesanan</th>
                                <th>Status</th>
                                <th>Bergabung</th>
                                <th class="pe-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="detail-avatar" style="width:40px;height:40px;font-size:0.9rem;">
                                            <i class="fas fa-<?php echo e($customer->type == 'company' ? 'building' : 'user'); ?>"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo e($customer->name); ?></div>
                                            <?php if($customer->company): ?><small class="text-muted"><?php echo e($customer->company); ?></small><?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="small">
                                    <?php if($customer->phone): ?><div><i class="fas fa-phone text-muted me-1"></i><?php echo e($customer->phone); ?></div><?php endif; ?>
                                    <?php if($customer->email): ?><div><i class="fas fa-envelope text-muted me-1"></i><?php echo e($customer->email); ?></div><?php endif; ?>
                                    <?php if(!$customer->phone && !$customer->email): ?><span class="text-muted">—</span><?php endif; ?>
                                </td>
                                <td><span title="<?php echo e($customer->address); ?>"><?php echo e(Str::limit($customer->address ?? '—', 40)); ?></span></td>
                                <td>
                                    <span class="hub-pill <?php echo e($customer->type == 'company' ? 'hub-pill-muted' : ''); ?>">
                                        <?php echo e($customer->type == 'company' ? 'Perusahaan' : 'Individual'); ?>

                                    </span>
                                </td>
                                <td><span class="fw-bold"><?php echo e($customer->orders_count ?? 0); ?></span> <span class="text-muted small">pesanan</span></td>
                                <td>
                                    <span class="hub-pill <?php echo e($customer->is_active ? 'hub-pill-success' : 'hub-pill-danger'); ?>">
                                        <?php echo e($customer->is_active ? 'Aktif' : 'Nonaktif'); ?>

                                    </span>
                                </td>
                                <td class="small">
                                    <?php echo e($customer->created_at->format('d M Y')); ?><br>
                                    <span class="text-muted"><?php echo e($customer->created_at->diffForHumans()); ?></span>
                                </td>
                                <td class="pe-4 text-center">
                                    <div class="hub-btn-group justify-content-center">
                                        <a href="<?php echo e(route('customers.show', $customer)); ?>" class="hub-btn hub-btn-sm hub-btn-outline" title="Detail"><i class="fas fa-eye"></i></a>
                                        <a href="<?php echo e(route('customers.edit', $customer)); ?>" class="hub-btn hub-btn-sm hub-btn-outline" title="Edit"><i class="fas fa-edit"></i></a>
                                        <?php if($customer->orders_count == 0): ?>
                                            <button type="button" class="hub-btn hub-btn-sm hub-btn-outline" onclick="deleteCustomer(<?php echo e($customer->id); ?>)" title="Hapus"><i class="fas fa-trash"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
                <div class="hub-pagination">
                    <span class="hub-pagination-info">Menampilkan <?php echo e($customers->firstItem() ?? 0); ?>–<?php echo e($customers->lastItem() ?? 0); ?> dari <?php echo e($customers->total()); ?> customer</span>
                    <?php echo e($customers->withQueryString()->links()); ?>

                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3 opacity-50"></i>
                    <h5 class="text-muted">Tidak ada customer</h5>
                    <a href="<?php echo e(route('customers.create')); ?>" class="hub-btn hub-btn-primary mt-2"><i class="fas fa-user-plus"></i> Tambah Customer</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <form id="deleteForm" method="POST" style="display:none;"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?></form>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function deleteCustomer(id) {
    Swal.fire({
        title: 'Hapus Customer?',
        text: 'Data akan dihapus permanen.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#7f1d1d',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((r) => {
        if (r.isConfirmed) {
            const f = document.getElementById('deleteForm');
            f.action = '/customers/' + id;
            f.submit();
        }
    });
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views\customers\index.blade.php ENDPATH**/ ?>