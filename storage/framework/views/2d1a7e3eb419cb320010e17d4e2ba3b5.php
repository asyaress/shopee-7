<?php $filters = $filters ?? []; ?>
<div class="report-filter-card">
    <div class="filter-title"><i class="fas fa-sliders-h me-1"></i> Parameter Laporan</div>
    <form method="GET" class="hub-filter-bar">
        <div class="filter-item">
            <label class="hub-form-label">Tanggal mulai</label>
            <input type="date" name="start" class="hub-form-control" value="<?php echo e($filters['start'] ?? ''); ?>">
        </div>
        <div class="filter-item">
            <label class="hub-form-label">Tanggal akhir</label>
            <input type="date" name="end" class="hub-form-control" value="<?php echo e($filters['end'] ?? ''); ?>">
        </div>
        <div class="filter-item">
            <label class="hub-form-label">Status pesanan</label>
            <select name="status" class="hub-form-select">
                <?php $__currentLoopData = ['completed','in_progress','pending','cancelled','all']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($st); ?>" <?php if(($filters['status'] ?? 'completed') === $st): echo 'selected'; endif; ?>><?php echo e(ucfirst(str_replace('_',' ',$st))); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="filter-item">
            <label class="hub-form-label">Kanal</label>
            <select name="jenis" class="hub-form-select">
                <option value="shopee" <?php if(($filters['jenis'] ?? 'shopee') === 'shopee'): echo 'selected'; endif; ?>>Shopee</option>
                <option value="all" <?php if(($filters['jenis'] ?? '') === 'all'): echo 'selected'; endif; ?>>Semua</option>
            </select>
        </div>
        <div class="filter-item" style="flex:0 0 auto;align-self:flex-end;">
            <button type="submit" class="hub-btn hub-btn-primary w-100"><i class="fas fa-search"></i> Tampilkan</button>
        </div>
    </form>
</div>
<?php /**PATH D:\A. SHOPEE-7\resources\views/hub/partials/monitoring-filter.blade.php ENDPATH**/ ?>