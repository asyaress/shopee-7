<?php $__env->startSection('title', 'Edit Pesanan - Toedjoe Order System'); ?>

<?php $__env->startSection('content'); ?>
<div class="report-shell hub-form-page">
    <?php echo $__env->make('hub.partials.page-hero', [
        'icon' => 'fa-edit',
        'title' => 'Edit Pesanan',
        'subtitle' => $order->order_number,
        'actions' => '<a href="' . route('orders.show', $order) . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-eye"></i></a>'
            . '<a href="' . route('orders.index') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-arrow-left"></i></a>',
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <form action="<?php echo e(route('orders.update', $order)); ?>" method="POST" id="orderForm">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>
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
                                    value="<?php echo e(old('order_number', $order->order_number)); ?>"
                                    placeholder="Contoh: TSG-2025-001" required>
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
                                    value="<?php echo e(old('order_date', $order->order_date->format('Y-m-d'))); ?>" required>
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
        <div class="input-group">
            <select name="customer_id" id="customer_id"
                class="hub-form-control <?php $__errorArgs = ['customer_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                required>
                <option value="">Pilih Customer</option>
                <?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $customer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($customer->id); ?>"
                        data-name="<?php echo e($customer->name); ?>"
                        data-company="<?php echo e($customer->company); ?>"
                        data-phone="<?php echo e($customer->phone); ?>"
                        <?php echo e(old('customer_id', $order->customer_id ?? '') == $customer->id ? 'selected' : (
                            (empty($order->customer_id) && old('customer_name', $order->customer_name ?? '') == $customer->name ? 'selected' : '')
                        )); ?>>
                        <?php echo e($customer->name); ?><?php if($customer->company): ?> - <?php echo e($customer->company); ?><?php endif; ?>
                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <input type="hidden" name="customer_name" id="customer_name"
                value="<?php echo e(old('customer_name', $order->customer_name ?? '')); ?>">
            <button type="button" class="hub-btn hub-btn-outline" onclick="openCustomerModal()" title="Tambah Customer Baru">
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
            value="<?php echo e(old('completion_date', $order->completion_date->format('Y-m-d'))); ?>" required>
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
                            <select name="jenis_pengiriman" class="hub-form-control <?php $__errorArgs = ['jenis_pengiriman'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                <option value="">Pilih Jenis Pengiriman</option>
                                <option value="Gosend" <?php echo e(old('jenis_pengiriman', $order->jenis_pengiriman) == 'Gosend' ? 'selected' : ''); ?>>Gosend</option>
                                <option value="JNE" <?php echo e(old('jenis_pengiriman', $order->jenis_pengiriman) == 'JNE' ? 'selected' : ''); ?>>JNE</option>
                                <option value="SPX" <?php echo e(old('jenis_pengiriman', $order->jenis_pengiriman) == 'SPX' ? 'selected' : ''); ?>>SPX</option>
                                <option value="Anter Aja" <?php echo e(old('jenis_pengiriman', $order->jenis_pengiriman) == 'Anter Aja' ? 'selected' : ''); ?>>Anter Aja</option>
                                <option value="POS" <?php echo e(old('jenis_pengiriman', $order->jenis_pengiriman) == 'POS' ? 'selected' : ''); ?>>POS</option>
                                <option value="JTR" <?php echo e(old('jenis_pengiriman', $order->jenis_pengiriman) == 'JTR' ? 'selected' : ''); ?>>JTR</option>
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
                            <select name="jenis_transaksi" class="hub-form-control <?php $__errorArgs = ['jenis_transaksi'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                <option value="">Pilih Jenis Transaksi</option>
                                <option value="Shopee" <?php echo e(old('jenis_transaksi', $order->jenis_transaksi) == 'Shopee' ? 'selected' : ''); ?>>Shopee</option>
                                <option value="Website" <?php echo e(old('jenis_transaksi', $order->jenis_transaksi) == 'Website' ? 'selected' : ''); ?>>Website</option>
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

                        <!-- STATUS -->
                        <div class="mb-3">
                            <label class="hub-form-label">Status *</label>
                            <select name="status" class="hub-form-control <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                <option value="pending" <?php echo e(old('status', $order->status) == 'pending' ? 'selected' : ''); ?>>Menunggu</option>
                                <option value="in_progress" <?php echo e(old('status', $order->status) == 'in_progress' ? 'selected' : ''); ?>>Sedang Proses</option>
                                <option value="completed" <?php echo e(old('status', $order->status) == 'completed' ? 'selected' : ''); ?>>Selesai</option>
                                <option value="cancelled" <?php echo e(old('status', $order->status) == 'cancelled' ? 'selected' : ''); ?>>Dibatalkan</option>
                            </select>
                            <?php $__errorArgs = ['status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            <small class="text-muted">Update status pesanan</small>
                        </div>

                        <!-- PRODUK MULTIPLE -->
                        <div class="mb-3">
                            <label class="hub-form-label mb-2">Daftar Produk *</label>
                            <table class="table table-bordered align-middle" id="productTable">
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
                                    <?php $productsList = old('product_id', collect($order->orderItems)->pluck('product_id')->toArray()); ?>
                                    <?php for($i = 0; $i < count($productsList); $i++): ?>
                                    <tr>
                                        <td>
                                            <select name="product_id[]" class="hub-form-control product-select" required>
                                                <option value="">Pilih Produk/Custom</option>
                                                <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($product->id); ?>"
                                                        data-name="<?php echo e($product->name); ?>"
                                                        data-price="<?php echo e($product->base_price); ?>"
                                                        data-unit="<?php echo e($product->unit); ?>"
                                                        <?php echo e(old('product_id.' . $i, $order->orderItems[$i]->product_id ?? '') == $product->id ? 'selected' : ''); ?>>
                                                        <?php echo e($product->name); ?> - <?php echo e($product->category); ?>

                                                        <?php if($product->base_price): ?>
                                                            (Rp <?php echo e(number_format($product->base_price, 0, ',', '.')); ?>)
                                                        <?php endif; ?>
                                                    </option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="product_name[]" class="hub-form-control product-name"
                                                placeholder="Nama produk..."
                                                value="<?php echo e(old('product_name.' . $i, $order->orderItems[$i]->product_name ?? '')); ?>" required>
                                        </td>
                                        <td>
                                            <input type="number" name="quantity[]" class="hub-form-control quantity" value="<?php echo e(old('quantity.' . $i, $order->orderItems[$i]->quantity ?? 1)); ?>" min="1" required>
                                        </td>
                                        <td>
                                            <input type="number" name="price[]" class="hub-form-control price" value="<?php echo e(old('price.' . $i, $order->orderItems[$i]->price ?? 0)); ?>" min="0" step="100" required>
                                        </td>
                                        <td>
                                            <input type="text" class="hub-form-control subtotal"
                                            value="<?php echo e(old('quantity.' . $i, $order->orderItems[$i]->quantity ?? 1) * old('price.' . $i, $order->orderItems[$i]->price ?? 0)); ?>"
                                            readonly style="background: #fef3f2;">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-outline-danger btn-sm remove-product" title="Hapus">
                                                <i class="fas fa-minus-circle"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endfor; ?>
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
                            <textarea name="notes" class="hub-form-control <?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                rows="3" placeholder="Tambahkan catatan khusus untuk pesanan ini..."><?php echo e(old('notes', $order->notes)); ?></textarea>
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
                            <div class="col-6 text-end"><span id="summary-customer" class="text-muted">Belum dipilih</span></div>
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
                            <div class="col-6 text-end"><strong id="summary-total" style="color: var(--primary-red);">Rp 0</strong></div>
                        </div>
                        <hr>
                        <div class="row mb-3">
                            <div class="col-6"><strong>Durasi:</strong></div>
                            <div class="col-6 text-end"><span id="summary-duration" class="text-muted">0 hari</span></div>
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
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i><small>Pastikan tanggal selesai realistis</small></li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i><small>Cek info customer sebelum konfirmasi</small></li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i><small>Tulis catatan detail untuk produk custom</small></li>
                            <li><i class="fas fa-check-circle text-success me-2"></i><small>Review semua data sebelum simpan</small></li>
                        </ul>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="hub-card">
                    <div class="card-body">
                        <button type="submit" class="hub-btn hub-btn-primary w-100 mb-3">
                            <i class="fas fa-save me-2"></i>Update Pesanan
                        </button>
                        <a href="<?php echo e(route('orders.index')); ?>" class="hub-btn hub-btn-outline w-100">
                            <i class="fas fa-times me-2"></i>Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>

<!-- Customer Modal (copy dari create, sama persis) -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true"
     data-bs-backdrop="false" data-bs-keyboard="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border: 3px solid var(--primary-red); box-shadow: 0 20px 40px rgba(220, 38, 38, 0.3);">
            <div class="modal-header" style="background: var(--primary-red); color: white;">
                <h5 class="modal-title" id="customerModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Tambah Customer Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
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
                            <select name="type" class="hub-form-control">
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
<script>
    // --- CUSTOMER_NAME SYNC ---
    function syncCustomerName() {
        var select = document.getElementById('customer_id');
        var name = select.options[select.selectedIndex]?.getAttribute('data-name') || '';
        document.getElementById('customer_name').value = name;
    }
    document.getElementById('customer_id').addEventListener('change', function() {
        syncCustomerName();
        // update summary/info customer
        const selectedOption = this.options[this.selectedIndex];
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
    document.addEventListener('DOMContentLoaded', function() {
        syncCustomerName();
        // Trigger change biar info customer langsung tampil
        document.getElementById('customer_id').dispatchEvent(new Event('change'));
        // Inisialisasi produk/summary
        updateSummary();
        calculateTotals();
        calculateDuration();
        Array.from(productTable.rows).forEach(tr => {
            if(tr.querySelector('.price') && tr.querySelector('.quantity') && tr.querySelector('.subtotal')) {
                calculateRowSubtotal(tr);
            }
        });
    });

    // Modal: Tambah customer baru, update select + hidden field
    document.getElementById('customerForm').addEventListener('submit', function(e) {
        e.preventDefault();
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
                // Add new customer to select
                const customerSelect = document.getElementById('customer_id');
                const option = new Option(
                    data.customer.name + (data.customer.company ? ' - ' + data.customer.company : ''),
                    data.customer.id
                );
                option.dataset.phone = data.customer.phone || '';
                option.dataset.company = data.customer.company || '';
                option.dataset.name = data.customer.name || '';
                customerSelect.add(option);
                customerSelect.value = data.customer.id;
                // Set customer_name juga
                document.getElementById('customer_name').value = data.customer.name || '';
                customerSelect.dispatchEvent(new Event('change'));
                updateSummary();
                // Tutup modal, reset form
                var modal = bootstrap.Modal.getInstance(document.getElementById('customerModal'));
                modal.hide();
                this.reset();
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

    // Modal show
    function openCustomerModal() {
        var modal = new bootstrap.Modal(document.getElementById('customerModal'));
        modal.show();
    }

    // --- MULTI PRODUK ---
    function formatRupiah(number) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
    }
    const productTable = document.getElementById('productTable').getElementsByTagName('tbody')[0];
    document.getElementById('addProductBtn').addEventListener('click', function() {
        const newRow = productTable.rows[0].cloneNode(true);
        Array.from(newRow.querySelectorAll('input, select')).forEach(function(el) {
            if(el.type === 'select-one') el.selectedIndex = 0;
            else if(el.classList.contains('quantity')) el.value = 1;
            else el.value = '';
        });
        newRow.querySelector('.subtotal').value = 0;
        productTable.appendChild(newRow);
        calculateRowSubtotal(newRow);
        updateSummary();
        calculateTotals();
    });
    productTable.addEventListener('click', function(e) {
        if(e.target.closest('.remove-product')) {
            if(productTable.rows.length > 1) {
                e.target.closest('tr').remove();
                updateSummary();
                calculateTotals();
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
    productTable.addEventListener('change', function(e) {
        const tr = e.target.closest('tr');
        if(e.target.classList.contains('product-select')) {
            const selected = e.target.options[e.target.selectedIndex];
            tr.querySelector('.product-name').value = selected.dataset.name || '';
            tr.querySelector('.price').value = selected.dataset.price || 0;
            calculateRowSubtotal(tr);
        }
        updateSummary();
        calculateTotals();
    });
    productTable.addEventListener('input', function(e) {
        const tr = e.target.closest('tr');
        if(e.target.classList.contains('quantity') || e.target.classList.contains('price')) {
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
    }
    function calculateTotals() {
        let total = 0;
        Array.from(productTable.rows).forEach(tr => {
            const subtotalInput = tr.querySelector('.subtotal');
            if(subtotalInput) {
                const subtotal = parseFloat(subtotalInput.value) || 0;
                total += subtotal;
            }
        });
        document.getElementById('summary-total').textContent = formatRupiah(total);
    }
    function updateSummary() {
        // Customer
        const customerSelect = document.getElementById('customer_id');
        const customerText = customerSelect.value ? customerSelect.options[customerSelect.selectedIndex].text : 'Belum dipilih';
        document.getElementById('summary-customer').textContent = customerText;
        // Product
        let productList = [];
        let totalQty = 0;
        let totalPrice = 0;
        Array.from(productTable.rows).forEach(tr => {
            const pname = tr.querySelector('.product-name')?.value || '-';
            const qty = parseFloat(tr.querySelector('.quantity')?.value) || 0;
            const price = parseFloat(tr.querySelector('.price')?.value) || 0;
            productList.push(pname);
            totalQty += qty;
            totalPrice += price;
        });
        document.getElementById('summary-product').textContent = productList.join(', ');
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
    document.getElementById('orderForm').addEventListener('submit', function(e) {
        const requiredFields = [
            { field: 'order_number', name: 'Nomor Pesanan' },
            { field: 'customer_id', name: 'Customer' },
            { field: 'order_date', name: 'Tanggal Pemesanan' },
            { field: 'completion_date', name: 'Tanggal Selesai' },
            { field: 'status', name: 'Status' }
        ];
        let missingFields = [];
        requiredFields.forEach(item => {
            const field = document.querySelector('[name="'+item.field+'"]');
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
            Swal.fire({
                title: 'Data Belum Lengkap!',
                text: 'Field berikut harus diisi: ' + (missingFields.join(', ') || '') + (productMissing ? ', Data Produk' : ''),
                icon: 'warning',
                confirmButtonColor: '#dc2626'
            });
            return;
        }
        // Validate dates
        // const orderDate = new Date(document.querySelector('[name="order_date"]').value);
        // const completionDate = new Date(document.querySelector('[name="completion_date"]').value);
        // if (completionDate <= orderDate) {
        //     e.preventDefault();
        //     Swal.fire({
        //         title: 'Tanggal Tidak Valid!',
        //         text: 'Tanggal selesai harus setelah tanggal pemesanan',
        //         icon: 'error',
        //         confirmButtonColor: '#dc2626'
        //     });
        //     return;
        // }
    });
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views\orders\edit.blade.php ENDPATH**/ ?>