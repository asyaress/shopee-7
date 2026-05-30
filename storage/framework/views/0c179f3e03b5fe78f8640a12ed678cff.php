<?php
    $map = [
        'pending' => ['Menunggu', 'hub-pill-warning'],
        'in_progress' => ['Proses', 'hub-pill-muted'],
        'completed' => ['Selesai', 'hub-pill-success'],
        'cancelled' => ['Batal', 'hub-pill-danger'],
    ];
    $row = $map[$status ?? ''] ?? [ucfirst($status ?? '-'), 'hub-pill-muted'];
?>
<span class="hub-pill <?php echo e($row[1]); ?>"><?php echo e($row[0]); ?></span>
<?php /**PATH D:\A. SHOPEE-7\resources\views\hub\partials\status-pill.blade.php ENDPATH**/ ?>