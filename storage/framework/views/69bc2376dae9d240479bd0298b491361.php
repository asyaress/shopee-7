<?php $__env->startSection('title', 'Log Keputusan — CEO'); ?>
<?php $__env->startPush('styles'); ?><link href="<?php echo e(asset('css/hub-monitoring.css')); ?>?v=2" rel="stylesheet"><?php $__env->stopPush(); ?>
<?php $__env->startSection('content'); ?>
<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-clipboard-list me-2"></i>Log Keputusan</h1>
        <p class="small mb-0">Catat apa yang Anda lakukan (potong iklan, naik harga, dll.) untuk evaluasi minggu depan.</p>
    </div>
    <?php echo $__env->make('hub.partials.ceo-nav', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

    <div class="hub-card mb-3">
        <div class="hub-card-header"><h2 class="report-section-title">Tambah catatan</h2></div>
        <div class="hub-card-body">
            <form method="POST" action="<?php echo e(route('ceo.decisions.store')); ?>">
                <?php echo csrf_field(); ?>
                <div class="row g-2">
                    <div class="col-md-3">
                        <select name="decision_type" class="hub-form-select" required>
                            <option value="cut_ads">Potong iklan</option>
                            <option value="scale_ads">Naikkan iklan</option>
                            <option value="raise_price">Naikkan harga</option>
                            <option value="fix_hpp">Perbaiki HPP</option>
                            <option value="stop_sku">Stop / pause SKU</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="title" class="hub-form-control" placeholder="Judul singkat" required>
                    </div>
                    <div class="col-md-4">
                        <input type="number" name="product_id" class="hub-form-control" placeholder="Product ID (opsional)">
                    </div>
                    <div class="col-12">
                        <textarea name="note" class="hub-form-control" rows="2" placeholder="Catatan / hasil yang diharapkan"></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="hub-btn hub-btn-primary">Simpan keputusan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="hub-card">
        <div class="hub-card-body p-0">
            <table class="report-table">
                <thead><tr><th>Waktu</th><th>Tipe</th><th>Judul</th><th>Produk</th><th>Catatan</th></tr></thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td class="small"><?php echo e($log->created_at->format('d M Y H:i')); ?></td>
                    <td><code><?php echo e($log->decision_type); ?></code></td>
                    <td><?php echo e($log->title); ?></td>
                    <td><?php echo e($log->product?->name ?? '—'); ?></td>
                    <td class="small"><?php echo e(Str::limit($log->note, 80)); ?></td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada log.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views/hub/ceo/decisions.blade.php ENDPATH**/ ?>