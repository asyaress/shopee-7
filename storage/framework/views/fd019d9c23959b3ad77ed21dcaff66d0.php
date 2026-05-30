<?php $__env->startSection('title', 'Data Produk — Shopee Profit Hub'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $shopeeCount = $products->where('external_platform', 'shopee')->count();
    $withHpp = $products->whereNotNull('hpp_amount')->count();
?>
<div class="report-shell">
    <?php echo $__env->make('hub.partials.page-hero', [
        'icon' => 'fa-box',
        'title' => 'Data Produk',
        'subtitle' => 'Katalog master — Shopee sync & input biaya HPP',
        'meta' => [
            ['icon' => 'fa-cubes', 'text' => $products->count() . ' produk'],
            ['icon' => 'fa-store', 'text' => $shopeeCount . ' dari Shopee'],
        ],
        'actions' => '<a href="' . route('products.create') . '" class="hub-btn hub-btn-primary" style="background:#fff;color:var(--maroon-800)!important;"><i class="fas fa-plus"></i> Tambah</a>'
            . '<a href="' . route('hpp.index') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-tags"></i> HPP</a>'
            . '<a href="' . route('manage.index') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-database"></i> Kelola</a>',
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <div class="report-kpi-hero" style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));">
        <div class="report-kpi-card"><div class="label">Total Produk</div><div class="value"><?php echo e(hub_num($products->count())); ?></div></div>
        <div class="report-kpi-card positive"><div class="label">Aktif</div><div class="value"><?php echo e(hub_num($products->where('is_active', true)->count())); ?></div></div>
        <div class="report-kpi-card"><div class="label">Kategori</div><div class="value"><?php echo e(hub_num($categories->count())); ?></div></div>
        <div class="report-kpi-card <?php echo e($withHpp < $products->count() ? 'warn' : 'positive'); ?>"><div class="label">HPP Terisi</div><div class="value"><?php echo e($products->count() ? round($withHpp / $products->count() * 100) : 0); ?>%</div></div>
    </div>

    <div class="hub-card mb-3">
        <div class="hub-card-header"><h2 class="report-section-title"><i class="fas fa-filter me-2"></i>Kategori</h2></div>
        <div class="hub-card-body">
            <div class="d-flex flex-wrap gap-2">
                <a href="<?php echo e(route('products.index')); ?>" class="hub-btn hub-btn-sm <?php echo e(!request('category') ? 'hub-btn-primary' : 'hub-btn-outline'); ?>">Semua (<?php echo e($allProductsCount); ?>)</a>
                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e(route('products.index', ['category' => $category->category])); ?>"
                        class="hub-btn hub-btn-sm <?php echo e(request('category') == $category->category ? 'hub-btn-primary' : 'hub-btn-outline'); ?>">
                        <?php echo e($category->category); ?> (<?php echo e($category->count); ?>)
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>

    <div class="report-filter-card">
        <form method="GET" action="<?php echo e(route('products.index')); ?>" class="row g-3">
            <input type="hidden" name="category" value="<?php echo e(request('category')); ?>">
            <div class="col-md-4">
                <label class="hub-form-label">Cari Produk</label>
                <input type="text" name="search" class="hub-form-control" placeholder="Nama produk, deskripsi..." value="<?php echo e(request('search')); ?>">
            </div>
            <div class="col-md-2">
                <label class="hub-form-label">Status</label>
                <select name="status" class="hub-form-select hub-form-control">
                    <option value="">Semua Status</option>
                    <option value="active" <?php if(request('status') == 'active'): echo 'selected'; endif; ?>>Aktif</option>
                    <option value="inactive" <?php if(request('status') == 'inactive'): echo 'selected'; endif; ?>>Tidak Aktif</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="hub-form-label">Harga</label>
                <select name="price_range" class="hub-form-select hub-form-control">
                    <option value="">Semua Harga</option>
                    <option value="0-10000" <?php if(request('price_range') == '0-10000'): echo 'selected'; endif; ?>>&lt; Rp 10.000</option>
                    <option value="10000-50000" <?php if(request('price_range') == '10000-50000'): echo 'selected'; endif; ?>>Rp 10.000 - 50.000</option>
                    <option value="50000-100000" <?php if(request('price_range') == '50000-100000'): echo 'selected'; endif; ?>>Rp 50.000 - 100.000</option>
                    <option value="100000-999999999" <?php if(request('price_range') == '100000-999999999'): echo 'selected'; endif; ?>>&gt; Rp 100.000</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="hub-form-label">Urutkan</label>
                <select name="sort" class="hub-form-select hub-form-control">
                    <option value="latest" <?php if(request('sort', 'latest') == 'latest'): echo 'selected'; endif; ?>>Terbaru</option>
                    <option value="name" <?php if(request('sort') == 'name'): echo 'selected'; endif; ?>>Nama A-Z</option>
                    <option value="price_low" <?php if(request('sort') == 'price_low'): echo 'selected'; endif; ?>>Harga Terendah</option>
                    <option value="price_high" <?php if(request('sort') == 'price_high'): echo 'selected'; endif; ?>>Harga Tertinggi</option>
                    <option value="popular" <?php if(request('sort') == 'popular'): echo 'selected'; endif; ?>>Paling Laris</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="hub-btn hub-btn-primary w-100"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>

    <div class="hub-card" id="tableViewContent">
        <div class="hub-card-header flex-wrap">
            <div>
                <h2 class="report-section-title">Daftar Produk</h2>
                <?php if(request('category')): ?><span class="hub-pill hub-pill-muted"><?php echo e(request('category')); ?></span><?php endif; ?>
            </div>
            <div class="hub-btn-group">
                <button type="button" class="hub-btn hub-btn-sm hub-btn-outline" id="gridView"><i class="fas fa-th"></i> Grid</button>
                <button type="button" class="hub-btn hub-btn-sm hub-btn-primary" id="tableView"><i class="fas fa-list"></i> Tabel</button>
            </div>
        </div>
        <div class="hub-card-body p-0 hub-dt-wrap">
                        <?php if($products->count() > 0): ?>
                            <div class="table-responsive">
                                <table id="productsTable" class="table hub-dt-table mb-0" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th>Kategori</th>
                                            <th>Platform</th>
                                            <th>Harga</th>
                                            <th>Unit</th>
                                            <th>Total Pesanan</th>
                                            <th>Status</th>
                                            <th>Tanggal Dibuat</th>
                                            <th>Update</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr>
                                                <td>
                                                    <span class="fw-semibold"><?php echo e($product->name); ?></span>
                                                    <?php if($product->description): ?>
                                                        <span class="hub-dt-sub"><?php echo e(Str::limit($product->description, 50)); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($product->category): ?>
                                                        <span class="hub-pill hub-pill-muted"><?php echo e($product->category); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($product->external_platform === 'shopee'): ?>
                                                        <span class="hub-pill hub-pill-warning">Shopee</span>
                                                    <?php else: ?>
                                                        <span class="hub-pill hub-pill-muted">Internal</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($product->base_price): ?>
                                                        <span class="hub-dt-amount"><?php echo e(hub_rp($product->base_price)); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted small">Custom</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="hub-pill hub-pill-muted"><?php echo e($product->unit); ?></span></td>
                                                <td><strong><?php echo e($product->orders_count ?? 0); ?></strong> <span class="text-muted small">pesanan</span></td>
                                                <td>
                                                    <span class="hub-pill <?php echo e($product->is_active ? 'hub-pill-success' : 'hub-pill-danger'); ?>">
                                                        <?php echo e($product->is_active ? 'Aktif' : 'Nonaktif'); ?>

                                                    </span>
                                                </td>
                                                <td>
                                                    
                                                    <span
                                                        style="display:none;"><?php echo e($product->created_at->format('Y-m-d H:i:s')); ?></span>
                                                    <?php echo e($product->created_at->format('d M Y')); ?>

                                                    <br>
                                                    <small class="text-muted"><?php echo e($product->created_at->diffForHumans()); ?></small>
                                                </td>
                                                <td>
                                                    <div><?php echo e($product->updated_at->format('d M Y')); ?></div>
                                                    <small class="text-muted"><?php echo e($product->updated_at->diffForHumans()); ?></small>
                                                </td>
                                                <td class="text-center">
                                                    <div class="hub-dt-actions">
                                                        <a href="<?php echo e(route('products.show', $product)); ?>" class="hub-btn hub-btn-sm hub-btn-outline" title="Detail"><i class="fas fa-eye"></i></a>
                                                        <?php if($product->external_platform === 'shopee'): ?>
                                                            <button type="button" class="hub-btn hub-btn-sm hub-btn-outline" disabled title="Shopee"><i class="fas fa-lock"></i></button>
                                                        <?php else: ?>
                                                            <a href="<?php echo e(route('products.edit', $product)); ?>" class="hub-btn hub-btn-sm hub-btn-outline" title="Edit"><i class="fas fa-edit"></i></a>
                                                            <?php if($product->orders_count == 0): ?>
                                                                <button type="button" class="hub-btn hub-btn-sm hub-btn-outline" onclick="deleteProduct(<?php echo e($product->id); ?>)" title="Hapus"><i class="fas fa-trash"></i></button>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-box fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">Tidak ada produk ditemukan</h5>
                                <p class="text-muted">Coba ubah filter pencarian atau tambah produk baru</p>
                                <a href="<?php echo e(route('products.create')); ?>" class="btn btn-red">
                                    <i class="fas fa-plus me-2"></i>Tambah Produk Pertama
                                </a>
                            </div>
                        <?php endif; ?>
        </div>
    </div>

    <div class="row g-3" id="gridViewContent" style="display: none;">
            <?php if($products->count() > 0): ?>
                <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                        <div class="card card-modern h-100">
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="avatar mx-auto mb-3">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto"
                                            style="width: 80px; height: 80px; background: var(--light-red); color: var(--primary-red);">
                                            <i class="fas fa-box fa-2x"></i>
                                        </div>
                                    </div>
                                    <h6 class="fw-bold mb-2" title="<?php echo e($product->name); ?>">
                                        <?php echo e(Str::limit($product->name, 30)); ?>

                                    </h6>
                                    <?php if($product->category): ?>
                                        <span class="badge bg-info mb-2"><?php echo e($product->category); ?></span>
                                    <?php endif; ?>
                                    <?php if($product->external_platform === 'shopee'): ?>
                                        <span class="badge bg-warning text-dark mb-2">Shopee</span>
                                    <?php endif; ?>
                                </div>

                                <?php if($product->description): ?>
                                    <p class="text-muted small mb-3" title="<?php echo e($product->description); ?>">
                                        <?php echo e(Str::limit($product->description, 80)); ?>

                                    </p>
                                <?php endif; ?>

                                <div class="row text-center mb-3">
                                    <div class="col-6">
                                        <?php if($product->base_price): ?>
                                            <div class="fw-bold" style="color: var(--primary-red);">
                                                Rp <?php echo e(number_format($product->base_price, 0, ',', '.')); ?>

                                            </div>
                                            <small class="text-muted">per <?php echo e($product->unit); ?></small>
                                        <?php else: ?>
                                            <div class="text-muted">Harga Custom</div>
                                            <small class="text-muted">per <?php echo e($product->unit); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-6">
                                        <div class="fw-bold text-primary"><?php echo e($product->orders_count ?? 0); ?></div>
                                        <small class="text-muted">pesanan</small>
                                    </div>
                                </div>

                                <div class="text-center mb-3">
                                    <?php if($product->is_active): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Aktif
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times-circle me-1"></i>Tidak Aktif
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="d-grid gap-2">
                                    <div class="btn-group" role="group">
                                        <a href="<?php echo e(route('products.show', $product)); ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if($product->external_platform === 'shopee'): ?>
                                            <button class="btn btn-outline-secondary btn-sm" disabled title="Produk Shopee (read-only)">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        <?php else: ?>
                                            <a href="<?php echo e(route('products.edit', $product)); ?>" class="btn btn-outline-warning btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if($product->orders_count == 0): ?>
                                                <button class="btn btn-outline-danger btn-sm" onclick="deleteProduct(<?php echo e($product->id); ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-outline-secondary btn-sm" disabled title="Tidak bisa dihapus (ada pesanan)">
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                <!-- Pagination for Grid -->
                <div class="col-12">
                    <div class="d-flex justify-content-center">
                        
                    </div>
                </div>
            <?php else: ?>
                <div class="col-12">
                    <div class="card card-modern">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-box fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">Tidak ada produk ditemukan</h5>
                            <p class="text-muted">Coba ubah filter pencarian atau tambah produk baru</p>
                            <a href="<?php echo e(route('products.create')); ?>" class="btn btn-red">
                                <i class="fas fa-plus me-2"></i>Tambah Produk Pertama
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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
            HubDataTable.init('#productsTable', {
                order: [[7, 'desc']],
                pageLength: 12,
            });

            $('#gridView').on('click', function () {
                $('#tableViewContent').hide();
                $('#gridViewContent').show().css('display', 'flex');
                $(this).addClass('hub-btn-primary').removeClass('hub-btn-outline');
                $('#tableView').addClass('hub-btn-outline').removeClass('hub-btn-primary');
            });
            $('#tableView').on('click', function () {
                $('#gridViewContent').hide();
                $('#tableViewContent').show();
                $(this).addClass('hub-btn-primary').removeClass('hub-btn-outline');
                $('#gridView').addClass('hub-btn-outline').removeClass('hub-btn-primary');
            });
        });

        // Delete product function
        function deleteProduct(productId) {
            Swal.fire({
                title: 'Hapus Produk?',
                text: 'Data produk akan dihapus permanen dan tidak dapat dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('deleteForm');
                    form.action = `/products/${productId}`;
                    form.submit();
                }
            });
        }

        // Export products function
        function exportProducts() {
            Swal.fire({
                title: 'Export Data Produk',
                text: 'Fitur export produk akan segera tersedia',
                icon: 'info',
                confirmButtonColor: '#dc2626'
            });
        }
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views\products\index.blade.php ENDPATH**/ ?>