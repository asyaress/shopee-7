
<div class="report-hero mb-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h1>
                <?php if(!empty($icon)): ?><i class="fas <?php echo e($icon); ?> me-2"></i><?php endif; ?>
                <?php echo e($title ?? 'Halaman'); ?>

            </h1>
            <?php if(!empty($subtitle)): ?>
                <div class="report-hero-meta"><span><?php echo e($subtitle); ?></span></div>
            <?php endif; ?>
            <?php if(!empty($meta) && is_array($meta)): ?>
                <div class="report-hero-meta mt-1">
                    <?php $__currentLoopData = $meta; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <span><i class="fas <?php echo e($m['icon'] ?? 'fa-circle'); ?>"></i> <?php echo e($m['text']); ?></span>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php if(!empty($actions)): ?>
            <div class="hub-btn-group"><?php echo $actions; ?></div>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH D:\A. SHOPEE-7\resources\views/hub/partials/page-hero.blade.php ENDPATH**/ ?>