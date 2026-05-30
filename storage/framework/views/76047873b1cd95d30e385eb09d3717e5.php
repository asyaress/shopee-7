<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'id',
    'title',
    'subtitle' => null,
    'size' => 'default',
    'badge' => null,
    'kpis' => [],
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'id',
    'title',
    'subtitle' => null,
    'size' => 'default',
    'badge' => null,
    'kpis' => [],
]); ?>
<?php foreach (array_filter(([
    'id',
    'title',
    'subtitle' => null,
    'size' => 'default',
    'badge' => null,
    'kpis' => [],
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div class="fc-chart-panel">
    <div class="fc-chart-panel__head">
        <div>
            <h3><?php echo e($title); ?></h3>
            <?php if($subtitle): ?><p><?php echo e($subtitle); ?></p><?php endif; ?>
        </div>
        <?php if($badge): ?><span class="fc-chart-panel__badge"><?php echo e($badge); ?></span><?php endif; ?>
    </div>
    <?php if(!empty($kpis)): ?>
    <div class="fc-chart-kpis">
        <?php $__currentLoopData = $kpis; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kpi): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <span class="fc-chart-kpi"><?php echo e($kpi['label']); ?><strong><?php echo e($kpi['value']); ?></strong></span>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>
    <div class="<?php echo \Illuminate\Support\Arr::toCssClasses([
        'fc-chart-panel__canvas',
        'fc-chart-panel__canvas--hero' => $size === 'hero',
        'fc-chart-panel__canvas--compact' => $size === 'compact',
        'fc-chart-panel__canvas--square' => $size === 'square',
    ]); ?>">
        <canvas id="<?php echo e($id); ?>" role="img" aria-label="<?php echo e($title); ?>"></canvas>
    </div>
</div>
<?php /**PATH D:\A. SHOPEE-7\resources\views/hub/partials/chart-panel.blade.php ENDPATH**/ ?>