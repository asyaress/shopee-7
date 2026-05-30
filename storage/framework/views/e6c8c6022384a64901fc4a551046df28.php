<?php $__env->startSection('title', 'Tambah Pesanan - Toedjoe Order System'); ?>

<?php $__env->startPush('styles'); ?>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Custom Select2 styling to match hub-form-control */
        .select2-container .select2-selection--single {
            height: 45px !important;
            border: 2px solid #e5e7eb !important;
            border-radius: 8px !important;
            padding: 0 12px !important;
            font-size: 14px !important;
            background: white !important;
            transition: all 0.3s ease !important;
        }

        .select2-container .select2-selection--single:focus,
        .select2-container.select2-container--open .select2-selection--single {
            border-color: var(--primary-red) !important;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1) !important;
        }

        .select2-container .select2-selection--single .select2-selection__rendered {
            line-height: 41px !important;
            padding: 0 !important;
            color: #374151 !important;
        }

        .select2-container .select2-selection--single .select2-selection__arrow {
            height: 41px !important;
            right: 8px !important;
        }

        .select2-container .select2-selection--single .select2-selection__arrow b {
            border-color: #6b7280 transparent transparent transparent !important;
            border-width: 5px 4px 0 4px !important;
        }

        .select2-dropdown {
            border: 2px solid #e5e7eb !important;
            border-radius: 8px !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: var(--primary-red) !important;
            color: white !important;
        }

        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #fef3f2 !important;
            color: var(--primary-red) !important;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 2px solid #e5e7eb !important;
            border-radius: 6px !important;
            padding: 8px 12px !important;
            font-size: 14px !important;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field:focus {
            border-color: var(--primary-red) !important;
            outline: none !important;
        }

        /* Error state styling */
        .select2-container.is-invalid .select2-selection--single {
            border-color: #dc2626 !important;
        }

        /* Ensure full width */
        .select2-container {
            width: 100% !important;
        }

        /* Product table select2 styling */
        .product-table .select2-container .select2-selection--single {
            height: 38px !important;
            min-height: 38px !important;
        }

        .product-table .select2-container .select2-selection--single .select2-selection__rendered {
            line-height: 34px !important;
        }

        .product-table .select2-container .select2-selection--single .select2-selection__arrow {
            height: 34px !important;
        }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="report-shell hub-form-page">
    <?php echo $__env->make('hub.partials.page-hero', [
        'icon' => 'fa-plus-circle',
        'title' => 'Tambah Pesanan',
        'subtitle' => 'Input pesanan manual',
        'actions' => '<a href="' . route('orders.index') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-arrow-left"></i> Daftar</a>',
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <form action="<?php echo e(route('orders.store')); ?>" method="POST" id="orderForm">
            <?php echo csrf_field(); ?>
            <div class="row">
                <!-- Left Column - Main Form -->
                <div class="col-lg-8">
                    <div class="hub-card mb-4">
                        <div class="hub-card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Informasi Pesanan
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="hub-form-label">Nomor Pesanan *</label>
                                    <input type="text" name="order_number"
                                        class="hub-form-control <?php $__errorArgs = ['order_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        value="<?php echo e(old('order_number')); ?>" placeholder="Contoh: TSG-2025-001" required>
                                    <?php $__errorArgs = ['order_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    <small class="text-muted">Masukkan nomor pesanan unik</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="hub-form-label">Tanggal Pemesanan *</label>
                                    <input type="date" name="order_date"
                                        class="hub-form-control <?php $__errorArgs = ['order_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        value="<?php echo e(old('order_date', date('Y-m-d'))); ?>" required>
                                    <?php $__errorArgs = ['order_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="hub-form-label">Customer *</label>
                                    <div class="d-flex gap-2">
                                        <div class="flex-grow-1">
                                            <select name="customer_id" id="customer_id"
                                                class="hub-form-control select2-customer <?php $__errorArgs = ['customer_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                required>
                                                <option value="">Pilih Customer</option>
                                                <?php $__currentLoopData = $customers->sortBy('name'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($customer->id); ?>" <?php echo e(old('customer_id') == $customer->id ? 'selected' : ''); ?> data-name="<?php echo e($customer->name); ?>"
                                                        data-company="<?php echo e($customer->company); ?>"
                                                        data-phone="<?php echo e($customer->phone); ?>">
                                                        <?php echo e($customer->name); ?><?php if($customer->company): ?> -
                                                        <?php echo e($customer->company); ?><?php endif; ?>
                                                    </option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                            <input type="hidden" name="customer_name" id="customer_name" value="">
                                        </div>
                                        <button type="button" class="hub-btn hub-btn-outline d-flex align-items-center"
                                            onclick="openCustomerModal()" title="Tambah Customer Baru"
                                            style="height: 45px; min-width: 45px;">
                                            <i class="fas fa-user-plus"></i>
                                        </button>
                                    </div>
                                    <?php $__errorArgs = ['customer_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    <div id="customerInfo" class="mt-2" style="display: none;">
                                        <small class="text-muted">
                                            <i class="fas fa-phone me-1"></i><span id="customerPhone"></span>
                                            <span id="customerCompany"></span>
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="hub-form-label">Tanggal Selesai *</label>
                                    <input type="date" name="completion_date"
                                        class="hub-form-control <?php $__errorArgs = ['completion_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        value="<?php echo e(old('completion_date', date('Y-m-d', strtotime('+0 days')))); ?>" required>
                                    <?php $__errorArgs = ['completion_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <div class="invalid-feedback"><?php echo e($message); ?></div>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>

                            <!-- JENIS PENGIRIMAN -->
                            <div class="mb-3">
                                <label class="hub-form-label">Jenis Pengiriman *</label>
                                <select name="jenis_pengiriman" id="jenis_pengiriman"
                                    class="hub-form-control select2-shipping <?php $__errorArgs = ['jenis_pengiriman'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                    required>
                                    <option value="">Pilih Jenis Pengiriman</option>
                                    <option value="Ambil di Tempat" <?php echo e(old('jenis_pengiriman') == 'Ambil di Tempat' ? 'selected' : ''); ?>>Ambil di Tempat</option>
                                    <option value="Anter Aja" <?php echo e(old('jenis_pengiriman') == 'Anter Aja' ? 'selected' : ''); ?>>
                                        Anter Aja</option>
                                    <option value="Gosend" <?php echo e(old('jenis_pengiriman') == 'Gosend' ? 'selected' : ''); ?>>Gosend
                                    </option>
                                    <option value="JNE" <?php echo e(old('jenis_pengiriman') == 'JNE' ? 'selected' : ''); ?>>JNE</option>
                                    <option value="JTR" <?php echo e(old('jenis_pengiriman') == 'JTR' ? 'selected' : ''); ?>>JTR</option>
                                    <option value="POS" <?php echo e(old('jenis_pengiriman') == 'POS' ? 'selected' : ''); ?>>POS</option>
                                    <option value="SPX" <?php echo e(old('jenis_pengiriman') == 'SPX' ? 'selected' : ''); ?>>SPX</option>
                                </select>
                                <?php $__errorArgs = ['jenis_pengiriman'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                <small class="text-muted">Pilih kurir yang digunakan</small>
                            </div>

                            <!-- JENIS TRANSAKSI -->
                            <div class="mb-3">
                                <label class="hub-form-label">Jenis Transaksi *</label>
                                <select name="jenis_transaksi" id="jenis_transaksi"
                                    class="hub-form-control select2-transaction <?php $__errorArgs = ['jenis_transaksi'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                    required>
                                    <option value="">Pilih Jenis Transaksi</option>
                                    <option value="Shopee" <?php echo e(old('jenis_transaksi') == 'Shopee' ? 'selected' : ''); ?>>Shopee
                                    </option>
                                    <option value="Website" <?php echo e(old('jenis_transaksi') == 'Website' ? 'selected' : ''); ?>>
                                        Website</option>
                                </select>
                                <?php $__errorArgs = ['jenis_transaksi'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                <small class="text-muted">Pilih sumber transaksi</small>
                            </div>

                            <!-- PRODUK MULTIPLE -->
                            <div class="mb-3">
                                <label class="hub-form-label mb-2">Daftar Produk *</label>
                                <table class="table table-bordered align-middle product-table" id="productTable">
                                    <thead style="background: #fef3f2;">
                                        <tr>
                                            <th width="27%">Produk</th>
                                            <th width="20%">Nama Custom</th>
                                            <th width="13%">Quantity</th>
                                            <th width="20%">Harga/Unit</th>
                                            <th width="17%">Subtotal</th>
                                            <th width="3%"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr data-row-id="0">
                                            <td>
                                                <select name="product_id[]"
                                                    class="hub-form-control product-select select2-product"
                                                    data-row-id="0" required>
                                                    <option value="">Pilih Produk/Custom</option>
                                                    <?php $__currentLoopData = $products->sortBy('name'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($product->id); ?>" data-name="<?php echo e($product->name); ?>"
                                                            data-price="<?php echo e($product->base_price); ?>"
                                                            data-unit="<?php echo e($product->unit); ?>">
                                                            <?php echo e($product->name); ?> - <?php echo e($product->category); ?>

                                                            <?php if($product->base_price): ?>
                                                                (Rp <?php echo e(number_format($product->base_price, 0, ',', '.')); ?>)
                                                            <?php endif; ?>
                                                        </option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="product_name[]"
                                                    class="hub-form-control product-name"
                                                    placeholder="Nama produk..." required>
                                            </td>
                                            <td>
                                                <input type="number" name="quantity[]"
                                                    class="hub-form-control quantity" value="1" min="1"
                                                    required>
                                            </td>
                                            <td>
                                                <input type="number" name="price[]"
                                                    class="hub-form-control price" value="0" min="0"
                                                    step="100" required>
                                            </td>
                                            <td>
                                                <input type="text" class="hub-form-control subtotal"
                                                    value="0" readonly style="background: #fef3f2;">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-outline-danger btn-sm remove-product"
                                                    title="Hapus">
                                                    <i class="fas fa-minus-circle"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="6" class="text-end">
                                                <button type="button" class="hub-btn hub-btn-outline" id="addProductBtn">
                                                    <i class="fas fa-plus-circle me-2"></i>Tambah Produk
                                                </button>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                                <small class="text-muted">Tambah lebih dari satu produk jika diperlukan.</small>
                            </div>

                            <div class="mb-3">
                                <label class="hub-form-label">Catatan</label>
                                <textarea name="notes"
                                    class="hub-form-control <?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" rows="3"
                                    placeholder="Tambahkan catatan khusus untuk pesanan ini..."><?php echo e(old('notes')); ?></textarea>
                                <?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Summary & Actions -->
                <div class="col-lg-4">
                    <!-- Order Summary -->
                    <div class="hub-card mb-4">
                        <div class="hub-card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-calculator me-2"></i>Ringkasan Pesanan
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-6"><strong>Customer:</strong></div>
                                <div class="col-6 text-end"><span id="summary-customer" class="text-muted">Belum
                                        dipilih</span></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6"><strong>Produk:</strong></div>
                                <div class="col-6 text-end"><span id="summary-product" class="text-muted">-</span></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6"><strong>Quantity:</strong></div>
                                <div class="col-6 text-end"><span id="summary-quantity" class="text-muted">-</span></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6"><strong>Harga/Unit:</strong></div>
                                <div class="col-6 text-end"><span id="summary-price" class="text-muted">-</span></div>
                            </div>
                            <hr>
                            <div class="row mb-3">
                                <div class="col-6"><strong style="color: var(--primary-red);">Total:</strong></div>
                                <div class="col-6 text-end"><strong id="summary-total" style="color: var(--primary-red);">Rp
                                        0</strong></div>
                            </div>
                            <hr>
                            <div class="row mb-3">
                                <div class="col-6"><strong>Durasi:</strong></div>
                                <div class="col-6 text-end"><span id="summary-duration" class="text-muted">0 hari</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Tips -->
                    <div class="hub-card mb-4">
                        <div class="card-header" style="background: #fef3f2; color: var(--primary-red); border: none;">
                            <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Tips Cepat</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i><small>Pastikan
                                        tanggal selesai realistis</small></li>
                                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i><small>Cek info
                                        customer sebelum konfirmasi</small></li>
                                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i><small>Tulis catatan
                                        detail untuk produk custom</small></li>
                                <li><i class="fas fa-check-circle text-success me-2"></i><small>Review semua data sebelum
                                        simpan</small></li>
                            </ul>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="hub-card">
                        <div class="card-body">
                            <button type="submit" class="hub-btn hub-btn-primary w-100 mb-3">
                                <i class="fas fa-save me-2"></i>Simpan Pesanan
                            </button>
                            <a href="<?php echo e(route('orders.index')); ?>" class="hub-btn hub-btn-outline w-100">
                                <i class="fas fa-times me-2"></i>Batal
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

    <!-- Customer Modal -->
    <div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true"
        data-bs-backdrop="false" data-bs-keyboard="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content"
                style="border: 3px solid var(--primary-red); box-shadow: 0 20px 40px rgba(220, 38, 38, 0.3);">
                <div class="modal-header" style="background: var(--primary-red); color: white;">
                    <h5 class="modal-title" id="customerModalLabel">
                        <i class="fas fa-user-plus me-2"></i>Tambah Customer Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="customerForm">
                    <?php echo csrf_field(); ?>
                    <div class="modal-body" style="background: white; color: var(--text-dark);">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="hub-form-label">Nama Lengkap *</label>
                                <input type="text" name="name" class="hub-form-control" required
                                    placeholder="Masukkan nama lengkap">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="hub-form-label">No. Telepon</label>
                                <input type="text" name="phone" class="hub-form-control"
                                    placeholder="Contoh: 081234567890">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="hub-form-label">Email</label>
                                <input type="email" name="email" class="hub-form-control"
                                    placeholder="customer@email.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="hub-form-label">Tipe Customer</label>
                                <select name="type" id="customer_type"
                                    class="hub-form-control select2-customer-type">
                                    <option value="individual">Individual</option>
                                    <option value="company">Perusahaan</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="hub-form-label">Perusahaan</label>
                            <input type="text" name="company" class="hub-form-control"
                                placeholder="Nama perusahaan (jika ada)">
                        </div>
                        <div class="mb-3">
                            <label class="hub-form-label">Alamat</label>
                            <textarea name="address" class="hub-form-control" rows="3"
                                placeholder="Alamat lengkap customer"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer" style="background: #f8f9fa;">
                        <button type="button" class="hub-btn hub-btn-outline" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Batal
                        </button>
                        <button type="submit" class="hub-btn hub-btn-primary">
                            <i class="fas fa-save me-2"></i>Simpan Customer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <!-- Select2 JavaScript -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // Global variables for debugging
        let rowCounter = 0;
        const productTable = document.getElementById('productTable').getElementsByTagName('tbody')[0];

        // Debug functions - using console.log only
        function debugLog(message, data = null) {
            console.log(`[DEBUG] ${message}`, data);
        }

        // Initialize Select2 for all select elements
        function initializeSelect2() {
            console.log('[DEBUG] Initializing Select2 elements');

            // Customer select with search
            $('.select2-customer').select2({
                placeholder: 'Pilih Customer',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function () {
                        return "Tidak ada customer yang ditemukan";
                    },
                    searching: function () {
                        return "Mencari...";
                    }
                }
            });

            // Shipping select
            $('.select2-shipping').select2({
                placeholder: 'Pilih Jenis Pengiriman',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: -1
            });

            // Transaction select
            $('.select2-transaction').select2({
                placeholder: 'Pilih Jenis Transaksi',
                allowClear: true,
                width: '100%',
                minimumResultsForSearch: -1
            });

            // Initialize existing product selects
            $('.select2-product').each(function () {
                const $this = $(this);
                const rowId = $this.data('row-id') || 0;
                console.log(`[DEBUG] Initializing existing product select for row ${rowId}`);

                // Remove any existing Select2 instance
                if ($this.hasClass('select2-hidden-accessible')) {
                    $this.select2('destroy');
                }

                initializeProductSelect(this, rowId);
            });

            // Customer type in modal
            $('.select2-customer-type').select2({
                placeholder: 'Pilih Tipe Customer',
                allowClear: false,
                width: '100%',
                minimumResultsForSearch: -1,
                dropdownParent: $('#customerModal')
            });

            console.log('[DEBUG] Select2 initialization completed');
        }

        // Initialize product select for specific row
        function initializeProductSelect(selectElement, rowId) {
            console.log(`[DEBUG] Initializing product select for specific row ${rowId}`);

            // Destroy existing Select2 if present
            if ($(selectElement).hasClass('select2-hidden-accessible')) {
                $(selectElement).select2('destroy');
            }

            $(selectElement).select2({
                placeholder: 'Pilih Produk/Custom',
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function () {
                        return "Tidak ada produk yang ditemukan";
                    },
                    searching: function () {
                        return "Mencari produk...";
                    }
                }
            });

            // Bind events specifically for this select using namespace to avoid conflicts
            $(selectElement).off('select2:select.productHandler select2:clear.productHandler');

            $(selectElement).on('select2:select.productHandler', function (e) {
                const rowId = $(this).data('row-id');
                console.log(`[DEBUG] Product selected in row ${rowId}`, {
                    value: e.params.data.id,
                    text: e.params.data.text,
                    rowId: rowId,
                    element: e.params.data.element,
                    selectElement: this
                });

                handleProductSelection(this, e.params.data.element);
            });

            $(selectElement).on('select2:clear.productHandler', function (e) {
                const rowId = $(this).data('row-id');
                console.log(`[DEBUG] Product cleared in row ${rowId}`);

                handleProductClear(this);
            });
        }

        // Handle product selection
        function handleProductSelection(selectElement, selectedOption) {
            const tr = selectElement.closest('tr');
            const rowId = $(selectElement).data('row-id');

            debugLog(`Handling product selection for row ${rowId}`, {
                productName: selectedOption.dataset.name,
                productPrice: selectedOption.dataset.price,
                selectValue: selectElement.value,
                optionData: {
                    name: selectedOption.getAttribute('data-name'),
                    price: selectedOption.getAttribute('data-price'),
                    unit: selectedOption.getAttribute('data-unit')
                }
            });

            const productNameInput = tr.querySelector('.product-name');
            const priceInput = tr.querySelector('.price');

            debugLog(`Found inputs for row ${rowId}`, {
                nameInputExists: !!productNameInput,
                priceInputExists: !!priceInput,
                nameInputId: productNameInput?.id,
                priceInputId: priceInput?.id
            });

            if (productNameInput && priceInput) {
                // Get data from selected option using getAttribute for more reliable access
                const productName = selectedOption.getAttribute('data-name') || '';
                const productPrice = selectedOption.getAttribute('data-price') || 0;

                productNameInput.value = productName;
                priceInput.value = productPrice;

                debugLog(`Updated inputs for row ${rowId}`, {
                    nameSet: productName,
                    priceSet: productPrice,
                    nameInputValue: productNameInput.value,
                    priceInputValue: priceInput.value
                });

                calculateRowSubtotal(tr);
                updateSummary();
                calculateTotals();
            } else {
                debugLog(`ERROR: Missing inputs for row ${rowId}`, {
                    productNameInput: !!productNameInput,
                    priceInput: !!priceInput
                });
            }
        }

        // Handle product clear
        function handleProductClear(selectElement) {
            const tr = selectElement.closest('tr');
            const rowId = $(selectElement).data('row-id');

            debugLog(`Handling product clear for row ${rowId}`);

            const productNameInput = tr.querySelector('.product-name');
            const priceInput = tr.querySelector('.price');

            if (productNameInput && priceInput) {
                productNameInput.value = '';
                priceInput.value = 0;

                calculateRowSubtotal(tr);
                updateSummary();
                calculateTotals();
            }
        }

        // Initialize Select2 on document ready
        $(document).ready(function () {
            debugLog('Document ready - initializing');
            initializeSelect2();
        });

        // --- CUSTOMER_NAME SYNC ---
        function syncCustomerName() {
            var select = document.getElementById('customer_id');
            var name = select.options[select.selectedIndex]?.getAttribute('data-name') || '';
            document.getElementById('customer_name').value = name;
            debugLog('Customer name synced', { name: name });
        }

        // Customer select change event (using Select2 events)
        $('#customer_id').on('select2:select', function (e) {
            debugLog('Customer selected', { customerId: e.params.data.id });
            syncCustomerName();
            const selectedOption = e.params.data.element;
            const customerInfo = document.getElementById('customerInfo');
            const customerPhone = document.getElementById('customerPhone');
            const customerCompany = document.getElementById('customerCompany');

            if (this.value) {
                customerInfo.style.display = 'block';
                customerPhone.textContent = selectedOption.dataset.phone || '';
                customerCompany.textContent = selectedOption.dataset.company ? ' | ' + selectedOption.dataset.company : '';
            } else {
                customerInfo.style.display = 'none';
            }
            updateSummary();
        });

        $('#customer_id').on('select2:clear', function (e) {
            debugLog('Customer cleared');
            document.getElementById('customer_name').value = '';
            document.getElementById('customerInfo').style.display = 'none';
            updateSummary();
        });

        document.addEventListener('DOMContentLoaded', function () {
            debugLog('DOMContentLoaded fired');
            syncCustomerName();
            updateSummary();
            calculateTotals();
            calculateDuration();
            Array.from(productTable.rows).forEach((tr, index) => {
                if (tr.querySelector('.price') && tr.querySelector('.quantity') && tr.querySelector('.subtotal')) {
                    calculateRowSubtotal(tr);
                }
            });
        });

        // Modal: Tambah customer baru, update select + hidden field
        document.getElementById('customerForm').addEventListener('submit', function (e) {
            e.preventDefault();
            debugLog('Customer form submitted');
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
            submitBtn.disabled = true;
            const formData = new FormData(this);

            fetch('/customers', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        debugLog('Customer created successfully', data.customer);
                        // Add new customer to select using Select2
                        const customerSelect = $('#customer_id');
                        const newOption = new Option(
                            data.customer.name + (data.customer.company ? ' - ' + data.customer.company : ''),
                            data.customer.id,
                            true,
                            true
                        );

                        // Set data attributes
                        $(newOption).attr('data-phone', data.customer.phone || '');
                        $(newOption).attr('data-company', data.customer.company || '');
                        $(newOption).attr('data-name', data.customer.name || '');

                        customerSelect.append(newOption).trigger('change');

                        // Set customer_name also
                        document.getElementById('customer_name').value = data.customer.name || '';

                        // Update customer info display
                        const customerInfo = document.getElementById('customerInfo');
                        const customerPhone = document.getElementById('customerPhone');
                        const customerCompany = document.getElementById('customerCompany');

                        customerInfo.style.display = 'block';
                        customerPhone.textContent = data.customer.phone || '';
                        customerCompany.textContent = data.customer.company ? ' | ' + data.customer.company : '';

                        updateSummary();

                        // Close modal, reset form
                        var modal = bootstrap.Modal.getInstance(document.getElementById('customerModal'));
                        modal.hide();
                        this.reset();

                        // Reset Select2 in modal
                        $('#customer_type').val('individual').trigger('change');

                        Swal.fire({
                            title: 'Berhasil!',
                            text: 'Customer baru berhasil ditambahkan dan sudah dipilih',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        throw new Error(data.message || 'Gagal menambah customer');
                    }
                })
                .catch(error => {
                    debugLog('Error creating customer', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'Gagal menambah customer. Silakan coba lagi.',
                        icon: 'error',
                        confirmButtonColor: '#dc2626'
                    });
                })
                .finally(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
        });

        // Modal show event - reinitialize Select2 when modal opens
        $('#customerModal').on('shown.bs.modal', function () {
            debugLog('Customer modal shown');
            // Reinitialize Select2 for elements in modal
            $('.select2-customer-type').select2({
                placeholder: 'Pilih Tipe Customer',
                allowClear: false,
                width: '100%',
                minimumResultsForSearch: -1,
                dropdownParent: $('#customerModal')
            });
        });

        // Modal show
        function openCustomerModal() {
            debugLog('Opening customer modal');
            var modal = new bootstrap.Modal(document.getElementById('customerModal'));
            modal.show();
        }

        // --- MULTI PRODUK ---
        function formatRupiah(number) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
        }

        // Store product options for dynamic creation
        const productOptions = [
            <?php $__currentLoopData = $products->sortBy('name'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        {
                    id: '<?php echo e($product->id); ?>',
                    name: '<?php echo e($product->name); ?>',
                    category: '<?php echo e($product->category); ?>',
                    price: '<?php echo e($product->base_price); ?>',
                    unit: '<?php echo e($product->unit); ?>',
                    display: '<?php echo e($product->name); ?> - <?php echo e($product->category); ?><?php if($product->base_price): ?> (Rp <?php echo e(number_format($product->base_price, 0, ',', '.')); ?>)<?php endif; ?>'
                },
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            ];

        document.getElementById('addProductBtn').addEventListener('click', function () {
            rowCounter++;
            console.log(`[DEBUG] Adding new product row ${rowCounter}`);

            // Create a fresh row without cloning
            const newRow = document.createElement('tr');
            newRow.setAttribute('data-row-id', rowCounter);

            // Build select options
            let optionsHtml = '<option value="">Pilih Produk/Custom</option>';
            productOptions.forEach(product => {
                optionsHtml += `<option value="${product.id}" data-name="${product.name}" data-price="${product.price}" data-unit="${product.unit}">${product.display}</option>`;
            });

            newRow.innerHTML = `
                    <td>
                        <select name="product_id[]" class="hub-form-control product-select select2-product" data-row-id="${rowCounter}" required>
                            ${optionsHtml}
                        </select>
                    </td>
                    <td>
                        <input type="text" name="product_name[]" class="hub-form-control product-name" placeholder="Nama produk..." required>
                    </td>
                    <td>
                        <input type="number" name="quantity[]" class="hub-form-control quantity" value="1" min="1" required>
                    </td>
                    <td>
                        <input type="number" name="price[]" class="hub-form-control price" value="0" min="0" step="100" required>
                    </td>
                    <td>
                        <input type="text" class="hub-form-control subtotal" value="0" readonly style="background: #fef3f2;">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm remove-product" title="Hapus">
                            <i class="fas fa-minus-circle"></i>
                        </button>
                    </td>
                `;

            // Append the new row
            productTable.appendChild(newRow);

            // Get the select element from the new row
            const selectElement = newRow.querySelector('.product-select');

            console.log(`[DEBUG] New row created`, {
                rowId: rowCounter,
                selectElement: selectElement,
                productNameInput: newRow.querySelector('.product-name'),
                priceInput: newRow.querySelector('.price'),
                rowHTML: newRow.outerHTML.substring(0, 200)
            });

            // Initialize Select2 for the new select element
            initializeProductSelect(selectElement, rowCounter);

            console.log(`[DEBUG] New product row ${rowCounter} added successfully`);

            calculateRowSubtotal(newRow);
            updateSummary();
            calculateTotals();
        });

        productTable.addEventListener('click', function (e) {
            if (e.target.closest('.remove-product')) {
                if (productTable.rows.length > 1) {
                    const rowToRemove = e.target.closest('tr');
                    const rowId = rowToRemove.getAttribute('data-row-id');

                    debugLog(`Removing product row ${rowId}`);

                    // Destroy Select2 before removing row
                    $(rowToRemove).find('.select2-product').select2('destroy');

                    rowToRemove.remove();
                    updateSummary();
                    calculateTotals();

                    debugLog(`Product row ${rowId} removed successfully`);
                } else {
                    Swal.fire({
                        title: 'Minimal 1 Produk',
                        text: 'Pesanan harus memiliki setidaknya 1 produk.',
                        icon: 'warning',
                        confirmButtonColor: '#dc2626'
                    });
                }
            }
        });

        productTable.addEventListener('input', function (e) {
            const tr = e.target.closest('tr');
            const rowId = tr.getAttribute('data-row-id');

            if (e.target.classList.contains('quantity') || e.target.classList.contains('price')) {
                debugLog(`Input changed in row ${rowId}`, {
                    field: e.target.classList.contains('quantity') ? 'quantity' : 'price',
                    value: e.target.value
                });

                calculateRowSubtotal(tr);
                updateSummary();
                calculateTotals();
            }
        });

        function calculateRowSubtotal(tr) {
            const priceInput = tr.querySelector('.price');
            const qtyInput = tr.querySelector('.quantity');
            const subtotalInput = tr.querySelector('.subtotal');
            if (!priceInput || !qtyInput || !subtotalInput) return;

            const price = parseFloat(priceInput.value) || 0;
            const qty = parseFloat(qtyInput.value) || 0;
            const subtotal = price * qty;
            subtotalInput.value = subtotal > 0 ? subtotal : 0;

            const rowId = tr.getAttribute('data-row-id');
            debugLog(`Calculated subtotal for row ${rowId}`, {
                price: price,
                quantity: qty,
                subtotal: subtotal
            });
        }

        function calculateTotals() {
            let total = 0;
            Array.from(productTable.rows).forEach(tr => {
                const subtotalInput = tr.querySelector('.subtotal');
                if (subtotalInput) {
                    const subtotal = parseFloat(subtotalInput.value) || 0;
                    total += subtotal;
                }
            });
            document.getElementById('summary-total').textContent = formatRupiah(total);
            debugLog('Total calculated', { total: total });
        }

        function updateSummary() {
            console.log('[DEBUG] Updating summary');

            // Customer
            const customerSelect = document.getElementById('customer_id');
            const customerText = customerSelect.value ? customerSelect.options[customerSelect.selectedIndex].text : 'Belum dipilih';
            document.getElementById('summary-customer').textContent = customerText;

            // Product - get all visible values
            let productList = [];
            let totalQty = 0;
            let totalPrice = 0;

            Array.from(productTable.rows).forEach((tr, index) => {
                const productNameInput = tr.querySelector('.product-name');
                const qtyInput = tr.querySelector('.quantity');
                const priceInput = tr.querySelector('.price');

                if (productNameInput && qtyInput && priceInput) {
                    const pname = productNameInput.value || '-';
                    const qty = parseFloat(qtyInput.value) || 0;
                    const price = parseFloat(priceInput.value) || 0;

                    console.log(`[DEBUG] Summary row ${index}`, {
                        name: pname,
                        qty: qty,
                        price: price,
                        nameInputValue: productNameInput.value,
                        actualDisplayedText: productNameInput.value
                    });

                    if (pname && pname !== '-') {
                        productList.push(pname);
                    }
                    totalQty += qty;
                    totalPrice += price;
                }
            });

            document.getElementById('summary-product').textContent = productList.length > 0 ? productList.join(', ') : '-';
            document.getElementById('summary-quantity').textContent = totalQty + ' pcs';
            document.getElementById('summary-price').textContent = formatRupiah(totalPrice);
            calculateTotals();
        }

        document.querySelector('[name="order_date"]').addEventListener('change', calculateDuration);
        document.querySelector('[name="completion_date"]').addEventListener('change', calculateDuration);

        function calculateDuration() {
            const orderDate = new Date(document.querySelector('[name="order_date"]').value);
            const completionDate = new Date(document.querySelector('[name="completion_date"]').value);
            if (orderDate && completionDate && completionDate > orderDate) {
                const diffTime = Math.abs(completionDate - orderDate);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                document.getElementById('summary-duration').textContent = diffDays + ' hari';
            } else {
                document.getElementById('summary-duration').textContent = '0 hari';
            }
        }

        // --- Form Validation before submit ---
        document.getElementById('orderForm').addEventListener('submit', function (e) {
            debugLog('Form submission started');

            const requiredFields = [
                { field: 'order_number', name: 'Nomor Pesanan' },
                { field: 'customer_id', name: 'Customer' },
                { field: 'order_date', name: 'Tanggal Pemesanan' },
                { field: 'completion_date', name: 'Tanggal Selesai' },
                { field: 'jenis_pengiriman', name: 'Jenis Pengiriman' },
                { field: 'jenis_transaksi', name: 'Jenis Transaksi' }
            ];

            let missingFields = [];
            requiredFields.forEach(item => {
                const field = document.querySelector(`[name="${item.field}"]`);
                if (!field.value) missingFields.push(item.name);
            });

            // Produk at least 1
            let productMissing = false;
            Array.from(productTable.rows).forEach(tr => {
                if (
                    !tr.querySelector('.product-select')?.value ||
                    !tr.querySelector('.product-name')?.value ||
                    !tr.querySelector('.quantity')?.value ||
                    !tr.querySelector('.price')?.value
                ) productMissing = true;
            });

            if (missingFields.length > 0 || productMissing) {
                e.preventDefault();
                debugLog('Form validation failed', { missingFields, productMissing });
                Swal.fire({
                    title: 'Data Belum Lengkap!',
                    text: 'Field berikut harus diisi: ' + (missingFields.join(', ') || '') + (productMissing ? ', Data Produk' : ''),
                    icon: 'warning',
                    confirmButtonColor: '#dc2626'
                });
                return;
            }

            // Validate dates
            const orderDate = new Date(document.querySelector('[name="order_date"]').value);
            const completionDate = new Date(document.querySelector('[name="completion_date"]').value);
            if (completionDate < orderDate) {
                e.preventDefault();
                debugLog('Date validation failed', { orderDate, completionDate });
                Swal.fire({
                    title: 'Tanggal Tidak Valid!',
                    text: 'Tanggal selesai tidak boleh sebelum tanggal pemesanan',
                    icon: 'error',
                    confirmButtonColor: '#dc2626'
                });
                return;
            }

            debugLog('Form validation passed - submitting');
        });

        // Handle validation errors for Select2 elements
        function updateSelect2ValidationState() {
            debugLog('Updating Select2 validation state');

            // Check for validation errors and apply styling
            $('.select2-customer').each(function () {
                const hasError = $(this).hasClass('is-invalid');
                const container = $(this).next('.select2-container');
                if (hasError) {
                    container.addClass('is-invalid');
                } else {
                    container.removeClass('is-invalid');
                }
            });

            $('.select2-shipping, .select2-transaction').each(function () {
                const hasError = $(this).hasClass('is-invalid');
                const container = $(this).next('.select2-container');
                if (hasError) {
                    container.addClass('is-invalid');
                } else {
                    container.removeClass('is-invalid');
                }
            });
        }

        // Call validation state update after page load
        $(document).ready(function () {
            setTimeout(updateSelect2ValidationState, 100);

            // Initialize row counter and data attributes for existing rows
            Array.from(productTable.rows).forEach((row, index) => {
                row.setAttribute('data-row-id', index);
                const selectElement = row.querySelector('.product-select');
                if (selectElement) {
                    selectElement.setAttribute('data-row-id', index);

                    // If this select already has a value, ensure the inputs are populated
                    if (selectElement.value) {
                        const selectedOption = selectElement.options[selectElement.selectedIndex];
                        if (selectedOption) {
                            setTimeout(() => {
                                debugLog(`Initializing existing selection for row ${index}`, {
                                    selectedValue: selectElement.value,
                                    selectedText: selectedOption.text,
                                    dataName: selectedOption.getAttribute('data-name'),
                                    dataPrice: selectedOption.getAttribute('data-price')
                                });

                                // Force update the inputs for existing selections
                                const productNameInput = row.querySelector('.product-name');
                                const priceInput = row.querySelector('.price');

                                if (productNameInput && !productNameInput.value) {
                                    productNameInput.value = selectedOption.getAttribute('data-name') || '';
                                }
                                if (priceInput && (!priceInput.value || priceInput.value == '0')) {
                                    priceInput.value = selectedOption.getAttribute('data-price') || 0;
                                }

                                calculateRowSubtotal(row);
                            }, 200);
                        }
                    }
                }
            });
            rowCounter = productTable.rows.length - 1;

            debugLog('Initial setup completed', { rowCounter, totalRows: productTable.rows.length });
        });
    </script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views\orders\create.blade.php ENDPATH**/ ?>