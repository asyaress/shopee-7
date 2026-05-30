@extends('layouts.hub')

@section('title', 'Edit Customer — Shopee Profit Hub')

@section('content')
<div class="report-shell hub-form-page">
    @include('hub.partials.page-hero', [
        'icon' => 'fa-user-edit',
        'title' => 'Edit Customer',
        'subtitle' => $customer->name,
        'actions' => '<a href="' . route('customers.show', $customer) . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-eye"></i> Detail</a>'
            . '<a href="' . route('customers.index') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-arrow-left"></i> Daftar</a>',
    ])
    <form action="{{ route('customers.update', $customer) }}" method="POST" id="customerForm">
            @csrf
            @method('PUT')
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
                                        value="{{ old('name', $customer->name) }}" placeholder="Masukkan nama lengkap"
                                        required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="hub-form-label">Tipe Customer *</label>
                                    <select name="type"
                                        class="hub-form-control @error('type') is-invalid @enderror"
                                        required>
                                        <option value="individual" {{ old('type', $customer->type) == 'individual' ? 'selected' : '' }}>Individual</option>
                                        <option value="company" {{ old('type', $customer->type) == 'company' ? 'selected' : '' }}>Perusahaan</option>
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
                                        value="{{ old('phone', $customer->phone) }}" placeholder="Contoh: 081234567890">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="hub-form-label">Email</label>
                                    <input type="email" name="email"
                                        class="hub-form-control @error('email') is-invalid @enderror"
                                        value="{{ old('email', $customer->email) }}" placeholder="customer@email.com">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="hub-form-label">Nama Perusahaan</label>
                                <input type="text" name="company"
                                    class="hub-form-control @error('company') is-invalid @enderror"
                                    value="{{ old('company', $customer->company) }}"
                                    placeholder="Nama perusahaan (jika ada)">
                                @error('company')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Kosongkan jika customer individual</small>
                            </div>

                            <div class="mb-3">
                                <label class="hub-form-label">Alamat Lengkap</label>
                                <textarea name="address"
                                    class="hub-form-control @error('address') is-invalid @enderror" rows="4"
                                    placeholder="Masukkan alamat lengkap customer...">{{ old('address', $customer->address) }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="hub-form-label">Status Customer</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                        id="is_active" {{ old('is_active', $customer->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        Customer Aktif
                                    </label>
                                </div>
                                <small class="text-muted">Customer non-aktif tidak akan muncul di dropdown pemilihan</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Info & Actions -->
                <div class="col-lg-4">
                    <!-- Current Data Preview -->
                    <div class="hub-card mb-4">
                        <div class="card-header" style="background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                            <h6 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Data Saat Ini
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <div class="avatar mx-auto mb-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto"
                                        style="width: 60px; height: 60px; background: var(--light-red); color: var(--primary-red);">
                                        <i
                                            class="fas fa-{{ $customer->type == 'company' ? 'building' : 'user' }} fa-lg"></i>
                                    </div>
                                </div>
                                <h6 class="mb-1">{{ $customer->name }}</h6>
                                @if($customer->company)
                                    <small class="text-muted">{{ $customer->company }}</small>
                                @endif
                            </div>

                            <hr>

                            @php
                                // Ambil data orders dari database (manual, bukan relasi)
                                $ordersCount = \App\Models\Order::where('customer_name', $customer->name)->count();
                            @endphp
                            <div class="mb-2">
                                <strong>Total Pesanan:</strong>
                                <span class="badge bg-primary ms-2">{{ $ordersCount }}</span>
                            </div>

                            <div class="mb-2">
                                <strong>Bergabung:</strong>
                                <span class="ms-2">{{ $customer->created_at->format('d M Y') }}</span>
                            </div>

                            <div class="mb-2">
                                <strong>Status:</strong>
                                @if($customer->is_active)
                                    <span class="badge bg-success ms-2">Aktif</span>
                                @else
                                    <span class="badge bg-danger ms-2">Tidak Aktif</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Live Preview -->
                    <div class="hub-card mb-4">
                        <div class="hub-card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-eye me-2"></i>Live Preview
                            </h6>
                        </div>
                        <div class="card-body text-center">
                            <div class="avatar mx-auto mb-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto"
                                    style="width: 80px; height: 80px; background: var(--light-red); color: var(--primary-red);"
                                    id="previewAvatar">
                                    <i class="fas fa-user fa-2x" id="avatarIcon"></i>
                                </div>
                            </div>
                            <h5 id="previewName" class="mb-1">{{ $customer->name }}</h5>
                            <p id="previewCompany" class="text-muted mb-2"
                                style="{{ $customer->company ? '' : 'display: none;' }}">{{ $customer->company }}</p>
                            <div id="previewContact" class="mb-3">
                                @if($customer->phone || $customer->email)
                                    @if($customer->phone)
                                        <div><i class="fas fa-phone text-muted me-1"></i>{{ $customer->phone }}</div>
                                    @endif
                                    @if($customer->email)
                                        <div><i class="fas fa-envelope text-muted me-1"></i>{{ $customer->email }}</div>
                                    @endif
                                @else
                                    <small class="text-muted">Kontak akan muncul di sini</small>
                                @endif
                            </div>
                            <span id="previewType"
                                class="badge {{ $customer->type == 'company' ? 'bg-info' : 'bg-secondary' }}">
                                {{ $customer->type == 'company' ? 'Perusahaan' : 'Individual' }}
                            </span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="hub-card">
                        <div class="card-body">
                            <button type="submit" class="hub-btn hub-btn-primary w-100 mb-3">
                                <i class="fas fa-save me-2"></i>Update Customer
                            </button>
                            <a href="{{ route('customers.show', $customer) }}" class="hub-btn hub-btn-outline w-100 mb-2">
                                <i class="fas fa-eye me-2"></i>Lihat Detail
                            </a>
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
        // Update preview when form changes (same as create page)
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
