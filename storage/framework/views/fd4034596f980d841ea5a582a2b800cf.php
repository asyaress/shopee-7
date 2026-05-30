<?php if(!empty($links['product']) || !empty($links['ads'])): ?>
<span class="shopee-links ms-1">
    <?php if(!empty($links['product'])): ?>
    <a href="<?php echo e($links['product']); ?>" target="_blank" rel="noopener" class="shopee-link" title="Produk Shopee"><i class="fas fa-store"></i></a>
    <?php endif; ?>
    <?php if(!empty($links['ads'])): ?>
    <a href="<?php echo e($links['ads']); ?>" target="_blank" rel="noopener" class="shopee-link shopee-link-ads" title="Iklan Shopee"><i class="fas fa-bullhorn"></i></a>
    <?php endif; ?>
</span>
<?php endif; ?>
<?php /**PATH D:\A. SHOPEE-7\resources\views\hub\partials\product-shopee-links.blade.php ENDPATH**/ ?>