@extends('layouts.hub')

@section('title', 'Data Customer — Shopee Profit Hub')

@section('content')
@php
    $total = $customers->total();
    $onPage = $customers->count();
@endphp
<div class="report-shell">
    @include('hub.partials.page-hero', [
        'icon' => 'fa-address-book',
        'title' => 'Data Customer',
        'subtitle' => 'Manajemen kontak & riwayat pesanan',
        'meta' => [
            ['icon' => 'fa-users', 'text' => $total . ' customer terdaftar'],
        ],
        'actions' => '<a href="' . route('customers.create') . '" class="hub-btn hub-btn-primary" style="background:#fff;color:var(--maroon-800)!important;"><i class="fas fa-user-plus"></i> Tambah</a>',
    ])

    <div class="report-kpi-hero" style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));">
        <div class="report-kpi-card"><div class="label">Total</div><div class="value">{{ hub_num($total) }}</div></div>
        <div class="report-kpi-card"><div class="label">Individual</div><div class="value">{{ hub_num($customers->where('type', 'individual')->count()) }}</div><div class="sub">halaman ini</div></div>
        <div class="report-kpi-card"><div class="label">Perusahaan</div><div class="value">{{ hub_num($customers->where('type', 'company')->count()) }}</div></div>
        <div class="report-kpi-card positive"><div class="label">Aktif</div><div class="value">{{ hub_num($customers->where('is_active', true)->count()) }}</div></div>
    </div>

    <div class="report-filter-card">
        <form method="GET" action="{{ route('customers.index') }}" class="row g-3">
            <div class="col-md-4">
                <label class="hub-form-label">Cari Customer</label>
                <input type="text" name="search" class="hub-form-control" placeholder="Nama, perusahaan, email..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label class="hub-form-label">Tipe</label>
                <select name="type" class="hub-form-select hub-form-control">
                    <option value="">Semua Tipe</option>
                    <option value="individual" @selected(request('type') == 'individual')>Individual</option>
                    <option value="company" @selected(request('type') == 'company')>Perusahaan</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="hub-form-label">Status</label>
                <select name="status" class="hub-form-select hub-form-control">
                    <option value="">Semua</option>
                    <option value="active" @selected(request('status') == 'active')>Aktif</option>
                    <option value="inactive" @selected(request('status') == 'inactive')>Tidak Aktif</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="hub-form-label">Urutkan</label>
                <select name="sort" class="hub-form-select hub-form-control">
                    <option value="latest" @selected(request('sort', 'latest') == 'latest')>Terbaru</option>
                    <option value="oldest" @selected(request('sort') == 'oldest')>Terlama</option>
                    <option value="name" @selected(request('sort') == 'name')>Nama A-Z</option>
                    <option value="orders" @selected(request('sort') == 'orders')>Paling Sering Order</option>
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="hub-btn hub-btn-primary w-100"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>

    <div class="hub-card">
        <div class="hub-card-header">
            <h2 class="report-section-title"><i class="fas fa-table me-2"></i>Daftar Customer</h2>
            <span class="hub-pill hub-pill-muted">{{ $onPage }} / {{ $total }}</span>
        </div>
        <div class="hub-card-body p-0">
            @if($customers->count() > 0)
                <div class="table-responsive">
                    <table class="report-table table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Customer</th>
                                <th>Kontak</th>
                                <th>Alamat</th>
                                <th>Tipe</th>
                                <th>Pesanan</th>
                                <th>Status</th>
                                <th>Bergabung</th>
                                <th class="pe-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customers as $customer)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="detail-avatar" style="width:40px;height:40px;font-size:0.9rem;">
                                            <i class="fas fa-{{ $customer->type == 'company' ? 'building' : 'user' }}"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold">{{ $customer->name }}</div>
                                            @if($customer->company)<small class="text-muted">{{ $customer->company }}</small>@endif
                                        </div>
                                    </div>
                                </td>
                                <td class="small">
                                    @if($customer->phone)<div><i class="fas fa-phone text-muted me-1"></i>{{ $customer->phone }}</div>@endif
                                    @if($customer->email)<div><i class="fas fa-envelope text-muted me-1"></i>{{ $customer->email }}</div>@endif
                                    @if(!$customer->phone && !$customer->email)<span class="text-muted">—</span>@endif
                                </td>
                                <td><span title="{{ $customer->address }}">{{ Str::limit($customer->address ?? '—', 40) }}</span></td>
                                <td>
                                    <span class="hub-pill {{ $customer->type == 'company' ? 'hub-pill-muted' : '' }}">
                                        {{ $customer->type == 'company' ? 'Perusahaan' : 'Individual' }}
                                    </span>
                                </td>
                                <td><span class="fw-bold">{{ $customer->orders_count ?? 0 }}</span> <span class="text-muted small">pesanan</span></td>
                                <td>
                                    <span class="hub-pill {{ $customer->is_active ? 'hub-pill-success' : 'hub-pill-danger' }}">
                                        {{ $customer->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="small">
                                    {{ $customer->created_at->format('d M Y') }}<br>
                                    <span class="text-muted">{{ $customer->created_at->diffForHumans() }}</span>
                                </td>
                                <td class="pe-4 text-center">
                                    <div class="hub-btn-group justify-content-center">
                                        <a href="{{ route('customers.show', $customer) }}" class="hub-btn hub-btn-sm hub-btn-outline" title="Detail"><i class="fas fa-eye"></i></a>
                                        <a href="{{ route('customers.edit', $customer) }}" class="hub-btn hub-btn-sm hub-btn-outline" title="Edit"><i class="fas fa-edit"></i></a>
                                        @if($customer->orders_count == 0)
                                            <button type="button" class="hub-btn hub-btn-sm hub-btn-outline" onclick="deleteCustomer({{ $customer->id }})" title="Hapus"><i class="fas fa-trash"></i></button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="hub-pagination">
                    <span class="hub-pagination-info">Menampilkan {{ $customers->firstItem() ?? 0 }}–{{ $customers->lastItem() ?? 0 }} dari {{ $customers->total() }} customer</span>
                    {{ $customers->withQueryString()->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-users fa-3x text-muted mb-3 opacity-50"></i>
                    <h5 class="text-muted">Tidak ada customer</h5>
                    <a href="{{ route('customers.create') }}" class="hub-btn hub-btn-primary mt-2"><i class="fas fa-user-plus"></i> Tambah Customer</a>
                </div>
            @endif
        </div>
    </div>

    <form id="deleteForm" method="POST" style="display:none;">@csrf @method('DELETE')</form>
</div>
@endsection

@push('scripts')
<script>
function deleteCustomer(id) {
    Swal.fire({
        title: 'Hapus Customer?',
        text: 'Data akan dihapus permanen.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#7f1d1d',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then((r) => {
        if (r.isConfirmed) {
            const f = document.getElementById('deleteForm');
            f.action = '/customers/' + id;
            f.submit();
        }
    });
}
</script>
@endpush
