@extends('layouts.hub')

@section('title', 'Tambah Customer — Shopee Profit Hub')

@section('content')
<div class="report-shell hub-form-page">
    @include('hub.partials.page-hero', [
        'icon' => 'fa-user-plus',
        'title' => 'Tambah Customer',
        'subtitle' => 'Data kontak untuk pesanan manual',
        'actions' => '<a href="' . route('customers.index') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-arrow-left"></i> Daftar</a>',
    ])
    <form action="{{ route('customers.store') }}" method="POST" id="customerForm">
            @csrf
            <div class="row">
                <!-- Left Column - Main Form -->
                <div class="col-lg-8">
                    <div class="hub-card mb-4">
                        <div class="hub-card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Informasi Customer
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="hub-form-label">Nama Lengkap *</label>
                                    <input type="text" name="name"
                                        class="hub-form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name') }}" placeholder="Masukkan nama lengkap" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="hub-form-label">Tipe Customer *</label>
                                    <select name="type"
                                        class="hub-form-control @error('type') is-invalid @enderror"
                                        required>
                                        <option value="individual" {{ old('type') == 'individual' ? 'selected' : '' }}>
                                            Individual</option>
                                        <option value="company" {{ old('type') == 'company' ? 'selected' : '' }}>Perusahaan
                                        </option>
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="hub-form-label">No. Telepon</label>
                                    <input type="text" name="phone"
                                        class="hub-form-control @error('phone') is-invalid @enderror"
                                        value="{{ old('phone') }}" placeholder="Contoh: 081234567890">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="hub-form-label">Email</label>
                                    <input type="email" name="email"
                                        class="hub-form-control @error('email') is-invalid @enderror"
                                        value="{{ old('email') }}" placeholder="customer@email.com">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="hub-form-label">Nama Perusahaan</label>
                                <input type="text" name="company"
                                    class="hub-form-control @error('company') is-invalid @enderror"
                                    value="{{ old('company') }}" placeholder="Nama perusahaan (jika ada)">
                                @error('company')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Kosongkan jika customer individual</small>
                            </div>

                            <div class="mb-3">
                                <label class="hub-form-label">Alamat Lengkap</label>
                                <textarea name="address"
                                    class="hub-form-control @error('address') is-invalid @enderror" rows="4"
                                    placeholder="Masukkan alamat lengkap customer...">{{ old('address') }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Summary & Actions -->
                <div class="col-lg-4">
                    <!-- Preview Card -->
                    <div class="hub-card mb-4">
                        <div class="hub-card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-eye me-2"></i>Preview Customer
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="avatar mx-auto mb-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto"
                                    style="width: 80px; height: 80px; background: var(--light-red); color: var(--primary-red);"
                                    id="previewAvatar">
                                    <i class="fas fa-user fa-2x" id="avatarIcon"></i>
                                </div>
                            </div>
                            <h5 id="previewName" class="mb-1">Nama Customer</h5>
                            <p id="previewCompany" class="text-muted mb-2" style="display: none;">Nama Perusahaan</p>
                            <div id="previewContact" class="mb-3">
                                <small class="text-muted">Kontak akan muncul di sini</small>
                            </div>
                            <span id="previewType" class="badge bg-secondary">Individual</span>
                        </div>
                    </div>

                    <!-- Tips Card -->
                    <div class="hub-card mb-4">
                        <div class="card-header" style="background: #fef3f2; color: var(--primary-red); border: none;">
                            <h6 class="mb-0">
                                <i class="fas fa-lightbulb me-2"></i>Tips Mengisi Data
                            </h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <small>Nama harus diisi dengan lengkap</small>
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <small>Pilih tipe sesuai jenis customer</small>
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <small>Minimal isi nama dan satu kontak</small>
                                </li>
                                <li>
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <small>Alamat membantu pengiriman</small>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="hub-card">
                        <div class="card-body">
                            <button type="submit" class="hub-btn hub-btn-primary w-100 mb-3">
                                <i class="fas fa-user-plus me-2"></i>Simpan Customer
                            </button>
                            <a href="{{ route('customers.index') }}" class="hub-btn hub-btn-outline w-100">
                                <i class="fas fa-times me-2"></i>Batal
                            </a>
                        </div>
                    </div>
                </div>
            </div>
    </form>
</div>
@endsection

@push('scripts')
    <script>
        // Update preview when form changes
        document.addEventListener('DOMContentLoaded', function () {
            const nameInput = document.querySelector('[name="name"]');
            const companyInput = document.querySelector('[name="company"]');
            const phoneInput = document.querySelector('[name="phone"]');
            const emailInput = document.querySelector('[name="email"]');
            const typeSelect = document.querySelector('[name="type"]');

            function updatePreview() {
                // Update name
                const name = nameInput.value || 'Nama Customer';
                document.getElementById('previewName').textContent = name;

                // Update company
                const company = companyInput.value;
                const companyElement = document.getElementById('previewCompany');
                if (company) {
                    companyElement.textContent = company;
                    companyElement.style.display = 'block';
                } else {
                    companyElement.style.display = 'none';
                }

                // Update contact
                const phone = phoneInput.value;
                const email = emailInput.value;
                const contactElement = document.getElementById('previewContact');

                let contactHtml = '';
                if (phone) {
                    contactHtml += `<div><i class="fas fa-phone text-muted me-1"></i>${phone}</div>`;
                }
                if (email) {
                    contactHtml += `<div><i class="fas fa-envelope text-muted me-1"></i>${email}</div>`;
                }

                if (contactHtml) {
                    contactElement.innerHTML = contactHtml;
                } else {
                    contactElement.innerHTML = '<small class="text-muted">Kontak akan muncul di sini</small>';
                }

                // Update type and icon
                const type = typeSelect.value;
                const typeElement = document.getElementById('previewType');
                const avatarIcon = document.getElementById('avatarIcon');

                if (type === 'company') {
                    typeElement.textContent = 'Perusahaan';
                    typeElement.className = 'badge bg-info';
                    avatarIcon.className = 'fas fa-building fa-2x';
                } else {
                    typeElement.textContent = 'Individual';
                    typeElement.className = 'badge bg-secondary';
                    avatarIcon.className = 'fas fa-user fa-2x';
                }
            }

            // Add event listeners
            nameInput.addEventListener('input', updatePreview);
            companyInput.addEventListener('input', updatePreview);
            phoneInput.addEventListener('input', updatePreview);
            emailInput.addEventListener('input', updatePreview);
            typeSelect.addEventListener('change', updatePreview);

            // Initial preview update
            updatePreview();

            // Form validation
            document.getElementById('customerForm').addEventListener('submit', function (e) {
                const name = nameInput.value.trim();

                if (!name) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Nama Harus Diisi!',
                        text: 'Silakan masukkan nama customer',
                        icon: 'warning',
                        confirmButtonColor: '#7f1d1d'
                    });
                    nameInput.focus();
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
@endpush
