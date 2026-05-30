<?php if(count($shopeeShopOptions ?? []) > 0): ?>
<form method="POST" action="<?php echo e(route('shop.switch')); ?>" class="hub-shop-switcher">
    <?php echo csrf_field(); ?>
    <label class="visually-hidden" for="hubShopSelect">Pilih toko</label>
    <i class="fas fa-store"></i>
    <select name="shop_id" id="hubShopSelect" class="hub-shop-select" onchange="this.form.submit()">
        <?php $__currentLoopData = $shopeeShopOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($id); ?>" <?php if(($activeShopeeShopId ?? 0) == $id): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
</form>
<?php endif; ?>
<?php /**PATH D:\A. SHOPEE-7\resources\views\hub\partials\shop-switcher.blade.php ENDPATH**/ ?>