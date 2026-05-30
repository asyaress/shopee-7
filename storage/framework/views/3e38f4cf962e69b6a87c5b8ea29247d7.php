<?php $__env->startSection('title', 'Tambah Produk - Toedjoe Order System'); ?>

<?php $__env->startSection('content'); ?>
<div class="report-shell hub-form-page">
    <?php echo $__env->make('hub.partials.page-hero', [
        'icon' => 'fa-plus-square',
        'title' => 'Tambah Produk',
        'subtitle' => 'Katalog internal',
        'actions' => '<a href="' . route('products.index') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-arrow-left"></i> Daftar</a>',
    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <form action="<?php echo e(route('products.store')); ?>" method="POST" id="productForm">
        <?php echo csrf_field(); ?>
        <div class="row">
            <!-- Left Column - Main Form -->
            <div class="col-lg-8">
                <div class="hub-card mb-4">
                    <div class="hub-card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Informasi Produk
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="hub-form-label">Nama Produk *</label>
                                <input type="text" name="name"
                                       class="hub-form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('name')); ?>"
                                       placeholder="Masukkan nama produk" required>
                                <?php $__errorArgs = ['name'];
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
                            <div class="col-md-4 mb-3">
                                <label class="hub-form-label">Unit *</label>
                                <select name="unit" class="hub-form-control <?php $__errorArgs = ['unit'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                    <option value="">Pilih Unit</option>
                                    <option value="pcs" <?php echo e(old('unit') == 'pcs' ? 'selected' : ''); ?>>Pcs (Pieces)</option>
                                    <option value="lembar" <?php echo e(old('unit') == 'lembar' ? 'selected' : ''); ?>>Lembar</option>
                                    <option value="meter" <?php echo e(old('unit') == 'meter' ? 'selected' : ''); ?>>Meter</option>
                                    <option value="m2" <?php echo e(old('unit') == 'm2' ? 'selected' : ''); ?>>M² (Meter Persegi)</option>
                                    <option value="rim" <?php echo e(old('unit') == 'rim' ? 'selected' : ''); ?>>Rim</option>
                                    <option value="roll" <?php echo e(old('unit') == 'roll' ? 'selected' : ''); ?>>Roll</option>
                                    <option value="set" <?php echo e(old('unit') == 'set' ? 'selected' : ''); ?>>Set</option>
                                    <option value="paket" <?php echo e(old('unit') == 'paket' ? 'selected' : ''); ?>>Paket</option>
                                </select>
                                <?php $__errorArgs = ['unit'];
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
                                <label class="hub-form-label">Kategori</label>
                                <div class="input-group">
                                    <select name="category" id="categorySelect" class="hub-form-control <?php $__errorArgs = ['category'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                        <option value="">Pilih atau Buat Kategori Baru</option>
                                        <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($category); ?>" <?php echo e(old('category') == $category ? 'selected' : ''); ?>>
                                                <?php echo e($category); ?>

                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <option value="custom">+ Buat Kategori Baru</option>
                                    </select>
                                </div>
                                <input type="text" name="custom_category" id="customCategory"
                                       class="hub-form-control mt-2"
                                       placeholder="Masukkan kategori baru" style="display: none;">
                                <?php $__errorArgs = ['category'];
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
                            <div class="col-md-6 mb-3">
                                <label class="hub-form-label">Harga Dasar</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="base_price"
                                           class="hub-form-control <?php $__errorArgs = ['base_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                           value="<?php echo e(old('base_price')); ?>"
                                           min="0" step="100"
                                           placeholder="0">
                                </div>
                                <?php $__errorArgs = ['base_price'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                <small class="text-muted">Kosongkan jika harga bervariasi/custom</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="hub-form-label">Deskripsi Produk</label>
                            <textarea name="description"
                                      class="hub-form-control <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                      rows="4"
                                      placeholder="Deskripsikan produk ini, termasuk bahan, ukuran, atau spesifikasi lainnya..."><?php echo e(old('description')); ?></textarea>
                            <?php $__errorArgs = ['description'];
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

                        <div class="mb-3">
                            <label class="hub-form-label">Spesifikasi Teknis</label>
                            <textarea name="specifications"
                                      class="hub-form-control <?php $__errorArgs = ['specifications'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                      rows="3"
                                      placeholder="Contoh: Bahan art paper 120gsm, ukuran A4, finishing glossy/matt..."><?php echo e(old('specifications')); ?></textarea>
                            <?php $__errorArgs = ['specifications'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            <small class="text-muted">Opsional - spesifikasi detail untuk keperluan teknis</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                                       <?php echo e(old('is_active', true) ? 'checked' : ''); ?>>
                                <label class="form-check-label" for="is_active">
                                    <strong>Produk Aktif</strong>
                                </label>
                            </div>
                            <small class="text-muted">Produk aktif akan muncul di dropdown pemilihan saat buat pesanan</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Preview & Actions -->
            <div class="col-lg-4">
                <!-- Preview Card -->
                <div class="hub-card mb-4">
                    <div class="hub-card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-eye me-2"></i>Preview Produk
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="avatar mx-auto mb-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto"
                                 style="width: 80px; height: 80px; background: var(--light-red); color: var(--primary-red);">
                                <i class="fas fa-box fa-2x"></i>
                            </div>
                        </div>
                        <h5 id="previewName" class="mb-1">Nama Produk</h5>
                        <p id="previewCategory" class="text-muted mb-2" style="display: none;">
                            <span class="badge bg-info" id="categoryBadge"></span>
                        </p>
                        <div id="previewPrice" class="mb-3">
                            <div class="fw-bold" style="color: var(--primary-red);">Rp 0</div>
                            <small class="text-muted">per <span id="previewUnit">unit</span></small>
                        </div>
                        <div id="previewDescription" class="mb-3">
                            <small class="text-muted">Deskripsi akan muncul di sini</small>
                        </div>
                        <span id="previewStatus" class="badge bg-success">Aktif</span>
                    </div>
                </div>

                <!-- Quick Guide -->
                <div class="hub-card mb-4">
                    <div class="card-header" style="background: #fef3f2; color: var(--primary-red); border: none;">
                        <h6 class="mb-0">
                            <i class="fas fa-lightbulb me-2"></i>Panduan Mengisi
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <small>Nama produk harus jelas dan deskriptif</small>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <small>Pilih unit yang sesuai dengan produk</small>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <small>Kategori membantu mengelompokkan produk</small>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <small>Harga kosong untuk produk custom</small>
                            </li>
                            <li>
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <small>Deskripsi detail membantu customer</small>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="hub-card">
                    <div class="card-body">
                        <button type="submit" class="hub-btn hub-btn-primary w-100 mb-3">
                            <i class="fas fa-save me-2"></i>Simpan Produk
                        </button>
                        <a href="<?php echo e(route('products.index')); ?>" class="hub-btn hub-btn-outline w-100">
                            <i class="fas fa-times me-2"></i>Batal
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const nameInput = document.querySelector('[name="name"]');
        const categorySelect = document.getElementById('categorySelect');
        const customCategoryInput = document.getElementById('customCategory');
        const priceInput = document.querySelector('[name="base_price"]');
        const unitSelect = document.querySelector('[name="unit"]');
        const descriptionInput = document.querySelector('[name="description"]');
        const statusInput = document.querySelector('[name="is_active"]');

        // Handle custom category
        categorySelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customCategoryInput.style.display = 'block';
                customCategoryInput.required = true;
                this.name = '';
                customCategoryInput.name = 'category';
            } else {
                customCategoryInput.style.display = 'none';
                customCategoryInput.required = false;
                this.name = 'category';
                customCategoryInput.name = '';
            }
            updatePreview();
        });

        function updatePreview() {
            // Update name
            const name = nameInput.value || 'Nama Produk';
            document.getElementById('previewName').textContent = name;

            // Update category
            let category = '';
            if (categorySelect.value === 'custom') {
                category = customCategoryInput.value;
            } else {
                category = categorySelect.value;
            }

            const categoryElement = document.getElementById('previewCategory');
            const categoryBadge = document.getElementById('categoryBadge');

            if (category) {
                categoryBadge.textContent = category;
                categoryElement.style.display = 'block';
            } else {
                categoryElement.style.display = 'none';
            }

            // Update price
            const price = parseFloat(priceInput.value) || 0;
            const priceElement = document.getElementById('previewPrice');

            if (price > 0) {
                priceElement.innerHTML = `
                    <div class="fw-bold" style="color: var(--primary-red);">
                        Rp ${new Intl.NumberFormat('id-ID').format(price)}
                    </div>
                    <small class="text-muted">per ${unitSelect.value || 'unit'}</small>
                `;
            } else {
                priceElement.innerHTML = `
                    <div class="text-muted">Harga Custom</div>
                    <small class="text-muted">per ${unitSelect.value || 'unit'}</small>
                `;
            }

            // Update unit in preview
            document.getElementById('previewUnit').textContent = unitSelect.value || 'unit';

            // Update description
            const description = descriptionInput.value;
            const descriptionElement = document.getElementById('previewDescription');

            if (description) {
                descriptionElement.innerHTML = `<small class="text-muted">${description.substring(0, 100)}${description.length > 100 ? '...' : ''}</small>`;
            } else {
                descriptionElement.innerHTML = '<small class="text-muted">Deskripsi akan muncul di sini</small>';
            }

            // Update status
            const statusElement = document.getElementById('previewStatus');
            if (statusInput.checked) {
                statusElement.className = 'badge bg-success';
                statusElement.innerHTML = '<i class="fas fa-check-circle me-1"></i>Aktif';
            } else {
                statusElement.className = 'badge bg-danger';
                statusElement.innerHTML = '<i class="fas fa-times-circle me-1"></i>Tidak Aktif';
            }
        }

        // Add event listeners
        nameInput.addEventListener('input', updatePreview);
        customCategoryInput.addEventListener('input', updatePreview);
        priceInput.addEventListener('input', updatePreview);
        unitSelect.addEventListener('change', updatePreview);
        descriptionInput.addEventListener('input', updatePreview);
        statusInput.addEventListener('change', updatePreview);

        // Initial preview update
        updatePreview();

        // Form validation
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const name = nameInput.value.trim();
            const unit = unitSelect.value;

            if (!name) {
                e.preventDefault();
                Swal.fire({
                    title: 'Nama Produk Harus Diisi!',
                    text: 'Silakan masukkan nama produk',
                    icon: 'warning',
                    confirmButtonColor: '#dc2626'
                });
                nameInput.focus();
                return;
            }

            if (!unit) {
                e.preventDefault();
                Swal.fire({
                    title: 'Unit Harus Dipilih!',
                    text: 'Silakan pilih unit untuk produk',
                    icon: 'warning',
                    confirmButtonColor: '#dc2626'
                });
                unitSelect.focus();
                return;
            }

            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
            submitBtn.disabled = true;
        });
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.hub', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\A. SHOPEE-7\resources\views\products\create.blade.php ENDPATH**/ ?>