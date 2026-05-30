@extends('layouts.hub')

@section('title', 'Detail Customer — Shopee Profit Hub')

@section('content')
<div class="report-shell">
    @include('hub.partials.page-hero', [
        'icon' => 'fa-user',
        'title' => $customer->name,
        'subtitle' => $customer->company ?: ($customer->type == 'company' ? 'Perusahaan' : 'Individual'),
        'meta' => [
            ['icon' => 'fa-shopping-cart', 'text' => $orders->count() . ' pesanan'],
            ['icon' => 'fa-calendar', 'text' => 'Sejak ' . $customer->created_at->format('d M Y')],
        ],
        'actions' => '<a href="' . route('customers.edit', $customer) . '" class="hub-btn hub-btn-primary" style="background:#fff;color:var(--maroon-800)!important;"><i class="fas fa-edit"></i> Edit</a>'
            . '<a href="' . route('customers.index') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-arrow-left"></i> Daftar</a>',
    ])
    <div class="row g-3">
            <!-- Left Column - Customer Info -->
            <div class="col-lg-4">
                <!-- Customer Profile Card -->
                <div class="hub-card mb-4">
                    <div class="hub-card-header text-center">
                        <div class="avatar mx-auto mb-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto"
                                style="width: 100px; height: 100px; background: rgba(255,255,255,0.2); color: white;">
                                <i class="fas fa-{{ $customer->type == 'company' ? 'building' : 'user' }} fa-3x"></i>
                            </div>
                        </div>
                        <h4 class="mb-1">{{ $customer->name }}</h4>
                        @if($customer->company)
                            <p class="mb-2" style="opacity: 0.9;">{{ $customer->company }}</p>
                        @endif
                        <span class="badge {{ $customer->type == 'company' ? 'bg-info' : 'bg-secondary' }} fs-6">
                            <i class="fas fa-{{ $customer->type == 'company' ? 'building' : 'user' }} me-1"></i>
                            {{ $customer->type == 'company' ? 'Perusahaan' : 'Individual' }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row text-center mb-3">
                            <div class="col-6">
                                <div class="stat-number text-primary">{{ $orders->count() }}</div>
                                <small class="text-muted">Total Pesanan</small>
                            </div>
                            <div class="col-6">
                                <div class="stat-number text-success">
                                    Rp {{ number_format($orders->sum('total_amount'), 0, ',', '.') }}
                                </div>
                                <small class="text-muted">Total Transaksi</small>
                            </div>
                        </div>

                        <hr>

                        <!-- Contact Information -->
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-address-card me-2 text-primary"></i>Informasi Kontak
                        </h6>

                        @if($customer->phone)
                            <div class="mb-2">
                                <i class="fas fa-phone text-muted me-2"></i>
                                <a href="tel:{{ $customer->phone }}" class="text-decoration-none">{{ $customer->phone }}</a>
                            </div>
                        @endif

                        @if($customer->email)
                            <div class="mb-2">
                                <i class="fas fa-envelope text-muted me-2"></i>
                                <a href="mailto:{{ $customer->email }}" class="text-decoration-none">{{ $customer->email }}</a>
                            </div>
                        @endif

                        @if($customer->address)
                            <div class="mb-2">
                                <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                <span>{{ $customer->address }}</span>
                            </div>
                        @endif

                        @if(!$customer->phone && !$customer->email && !$customer->address)
                            <p class="text-muted mb-0">
                                <i class="fas fa-info-circle me-2"></i>Tidak ada informasi kontak
                            </p>
                        @endif

                        <hr>

                        <!-- Customer Status -->
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-info-circle me-2 text-primary"></i>Status & Info
                        </h6>

                        <div class="mb-2">
                            <strong>Status:</strong>
                            @if($customer->is_active)
                                <span class="badge bg-success ms-2">
                                    <i class="fas fa-check-circle me-1"></i>Aktif
                                </span>
                            @else
                                <span class="badge bg-danger ms-2">
                                    <i class="fas fa-times-circle me-1"></i>Tidak Aktif
                                </span>
                            @endif
                        </div>

                        <div class="mb-2">
                            <strong>Bergabung:</strong>
                            <span class="ms-2">{{ $customer->created_at->format('d M Y') }}</span>
                        </div>

                        <div class="mb-2">
                            <strong>Update Terakhir:</strong>
                            <span class="ms-2">{{ $customer->updated_at->format('d M Y H:i') }}</span>
                        </div>

                        @if($customer->notes)
                            <hr>
                            <h6 class="fw-bold mb-2">
                                <i class="fas fa-sticky-note me-2 text-primary"></i>Catatan
                            </h6>
                            <p class="text-muted mb-0">{{ $customer->notes }}</p>
                        @endif
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="hub-card">
                    <div class="card-header" style="background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
                        <h6 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Aksi Cepat
                        </h6>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('orders.create') }}?customer_name={{ urlencode($customer->name) }}"
                            class="hub-btn hub-btn-primary w-100 mb-2">
                            <i class="fas fa-plus-circle me-2"></i>Buat Pesanan Baru
                        </a>
                        <a href="{{ route('customers.edit', $customer) }}" class="hub-btn hub-btn-outline w-100 mb-2">
                            <i class="fas fa-edit me-2"></i>Edit Data Customer
                        </a>
                        @if($customer->phone)
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customer->phone) }}" target="_blank"
                                class="btn btn-outline-success w-100 mb-2">
                                <i class="fab fa-whatsapp me-2"></i>Chat WhatsApp
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column - Orders History -->
            <div class="col-lg-8">
                <div class="hub-card">
                    <div class="hub-card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>Riwayat Pesanan ({{ $orders->count() }})
                        </h5>
                        @if($orders->count() > 0)
                            <span class="badge bg-light text-dark">
                                Total: Rp {{ number_format($orders->sum('total_amount'), 0, ',', '.') }}
                            </span>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        @if($orders->count() > 0)
                            <div class="table-responsive">
                                <table class="report-table table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th class="border-0 ps-4">No. Pesanan</th>
                                            <th class="border-0">Produk</th>
                                            <th class="border-0">Qty</th>
                                            <th class="border-0">Tanggal</th>
                                            <th class="border-0">Status</th>
                                            <th class="border-0">Total</th>
                                            <th class="border-0 pe-4 text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($orders->sortByDesc('created_at') as $order)
                                            <tr>
                                                <td class="ps-4">
                                                    <strong class="text-maroon">{{ $order->order_number }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $order->created_at->diffForHumans() }}</small>
                                                </td>
                                                <td>
                                                    <div class="fw-bold">
                                                        @if($order->orderItems && $order->orderItems->count())
                                                            <ul class="mb-0 ps-3">
                                                                @foreach($order->orderItems as $item)
                                                                    <li>{{ $item->product_name }}</li>
                                                                @endforeach
                                                            </ul>
                                                        @else
                                                            {{ $order->product_name ?? '-' }}
                                                        @endif
                                                    </div>
                                                    @if($order->product && $order->product->category)
                                                        <small class="text-muted">{{ $order->product->category }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        @if($order->orderItems && $order->orderItems->count())
                                                            {{ $order->orderItems->sum('quantity') }} pcs
                                                        @else
                                                            {{ $order->quantity ?? '-' }} pcs
                                                        @endif
                                                    </span>
                                                </td>
                                                <td>
                                                    <div>{{ $order->order_date->format('d M Y') }}</div>
                                                    <small class="text-muted">
                                                        Selesai: {{ $order->completion_date->format('d M') }}
                                                    </small>
                                                </td>
                                                <td>
                                                    @php
                                                        $statusColors = [
                                                            'pending' => 'warning',
                                                            'in_progress' => 'info',
                                                            'completed' => 'success',
                                                            'cancelled' => 'danger'
                                                        ];
                                                    @endphp
                                                    <span class="badge bg-{{ $statusColors[$order->status] ?? 'secondary' }}">
                                                        {{ $order->status_indonesian }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($order->total_amount)
                                                        <div class="fw-bold" class="text-maroon">
                                                            Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="pe-4 text-center">
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('orders.show', $order) }}"
                                                            class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="{{ route('orders.edit', $order) }}"
                                                            class="btn btn-sm btn-outline-warning" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">Belum Ada Pesanan</h5>
                                <p class="text-muted">Customer ini belum pernah melakukan pesanan</p>
                                <a href="{{ route('orders.create') }}?customer_name={{ urlencode($customer->name) }}"
                                    class="hub-btn hub-btn-primary">
                                    <i class="fas fa-plus-circle me-2"></i>Buat Pesanan Pertama
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        // Auto-select customer in order create if coming from customer detail
        document.addEventListener('DOMContentLoaded', function () {
            // Any additional customer detail page scripts can go here
            console.log('Customer detail page loaded for: {{ $customer->name }}');
        });
    </script>
@endpush
