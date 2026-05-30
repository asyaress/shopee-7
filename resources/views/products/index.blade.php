@extends('layouts.hub')

@section('title', 'Data Produk — Shopee Profit Hub')

@section('content')
@php
    $shopeeCount = $products->where('external_platform', 'shopee')->count();
    $withHpp = $products->whereNotNull('hpp_amount')->count();
@endphp
<div class="report-shell">
    @include('hub.partials.page-hero', [
        'icon' => 'fa-box',
        'title' => 'Data Produk',
        'subtitle' => 'Katalog master — Shopee sync & input biaya HPP',
        'meta' => [
            ['icon' => 'fa-cubes', 'text' => $products->count() . ' produk'],
            ['icon' => 'fa-store', 'text' => $shopeeCount . ' dari Shopee'],
        ],
        'actions' => '<a href="' . route('products.create') . '" class="hub-btn hub-btn-primary" style="background:#fff;color:var(--maroon-800)!important;"><i class="fas fa-plus"></i> Tambah</a>'
            . '<a href="' . route('hpp.index') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-tags"></i> HPP</a>'
            . '<a href="' . route('manage.index') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-database"></i> Kelola</a>',
    ])

    <div class="report-kpi-hero" style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));">
        <div class="report-kpi-card"><div class="label">Total Produk</div><div class="value">{{ hub_num($products->count()) }}</div></div>
        <div class="report-kpi-card positive"><div class="label">Aktif</div><div class="value">{{ hub_num($products->where('is_active', true)->count()) }}</div></div>
        <div class="report-kpi-card"><div class="label">Kategori</div><div class="value">{{ hub_num($categories->count()) }}</div></div>
        <div class="report-kpi-card {{ $withHpp < $products->count() ? 'warn' : 'positive' }}"><div class="label">HPP Terisi</div><div class="value">{{ $products->count() ? round($withHpp / $products->count() * 100) : 0 }}%</div></div>
    </div>

    <div class="hub-card mb-3">
        <div class="hub-card-header"><h2 class="report-section-title"><i class="fas fa-filter me-2"></i>Kategori</h2></div>
        <div class="hub-card-body">
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('products.index') }}" class="hub-btn hub-btn-sm {{ !request('category') ? 'hub-btn-primary' : 'hub-btn-outline' }}">Semua ({{ $allProductsCount }})</a>
                @foreach($categories as $category)
                    <a href="{{ route('products.index', ['category' => $category->category]) }}"
                        class="hub-btn hub-btn-sm {{ request('category') == $category->category ? 'hub-btn-primary' : 'hub-btn-outline' }}">
                        {{ $category->category }} ({{ $category->count }})
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="report-filter-card">
        <form method="GET" action="{{ route('products.index') }}" class="row g-3">
            <input type="hidden" name="category" value="{{ request('category') }}">
            <div class="col-md-4">
                <label class="hub-form-label">Cari Produk</label>
                <input type="text" name="search" class="hub-form-control" placeholder="Nama produk, deskripsi..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="hub-form-label">Status</label>
                <select name="status" class="hub-form-select hub-form-control">
                    <option value="">Semua Status</option>
                    <option value="active" @selected(request('status') == 'active')>Aktif</option>
                    <option value="inactive" @selected(request('status') == 'inactive')>Tidak Aktif</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="hub-form-label">Harga</label>
                <select name="price_range" class="hub-form-select hub-form-control">
                    <option value="">Semua Harga</option>
                    <option value="0-10000" @selected(request('price_range') == '0-10000')>&lt; Rp 10.000</option>
                    <option value="10000-50000" @selected(request('price_range') == '10000-50000')>Rp 10.000 - 50.000</option>
                    <option value="50000-100000" @selected(request('price_range') == '50000-100000')>Rp 50.000 - 100.000</option>
                    <option value="100000-999999999" @selected(request('price_range') == '100000-999999999')>&gt; Rp 100.000</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="hub-form-label">Urutkan</label>
                <select name="sort" class="hub-form-select hub-form-control">
                    <option value="latest" @selected(request('sort', 'latest') == 'latest')>Terbaru</option>
                    <option value="name" @selected(request('sort') == 'name')>Nama A-Z</option>
                    <option value="price_low" @selected(request('sort') == 'price_low')>Harga Terendah</option>
                    <option value="price_high" @selected(request('sort') == 'price_high')>Harga Tertinggi</option>
                    <option value="popular" @selected(request('sort') == 'popular')>Paling Laris</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="hub-btn hub-btn-primary w-100"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>

    <div class="hub-card" id="tableViewContent">
        <div class="hub-card-header flex-wrap">
            <div>
                <h2 class="report-section-title">Daftar Produk</h2>
                @if(request('category'))<span class="hub-pill hub-pill-muted">{{ request('category') }}</span>@endif
            </div>
            <div class="hub-btn-group">
                <button type="button" class="hub-btn hub-btn-sm hub-btn-outline" id="gridView"><i class="fas fa-th"></i> Grid</button>
                <button type="button" class="hub-btn hub-btn-sm hub-btn-primary" id="tableView"><i class="fas fa-list"></i> Tabel</button>
            </div>
        </div>
        <div class="hub-card-body p-0 hub-dt-wrap">
                        @if($products->count() > 0)
                            <div class="table-responsive">
                                <table id="productsTable" class="table hub-dt-table mb-0" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th>Kategori</th>
                                            <th>Platform</th>
                                            <th>Harga</th>
                                            <th>Unit</th>
                                            <th>Total Pesanan</th>
                                            <th>Status</th>
                                            <th>Tanggal Dibuat</th>
                                            <th>Update</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($products as $product)
                                            <tr>
                                                <td>
                                                    <span class="fw-semibold">{{ $product->name }}</span>
                                                    @if($product->description)
                                                        <span class="hub-dt-sub">{{ Str::limit($product->description, 50) }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($product->category)
                                                        <span class="hub-pill hub-pill-muted">{{ $product->category }}</span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($product->external_platform === 'shopee')
                                                        <span class="hub-pill hub-pill-warning">Shopee</span>
                                                    @else
                                                        <span class="hub-pill hub-pill-muted">Internal</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($product->base_price)
                                                        <span class="hub-dt-amount">{{ hub_rp($product->base_price) }}</span>
                                                    @else
                                                        <span class="text-muted small">Custom</span>
                                                    @endif
                                                </td>
                                                <td><span class="hub-pill hub-pill-muted">{{ $product->unit }}</span></td>
                                                <td><strong>{{ $product->orders_count ?? 0 }}</strong> <span class="text-muted small">pesanan</span></td>
                                                <td>
                                                    <span class="hub-pill {{ $product->is_active ? 'hub-pill-success' : 'hub-pill-danger' }}">
                                                        {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    {{-- Untuk sorting DataTables, gunakan value tersembunyi format Y-m-d --}}
                                                    <span
                                                        style="display:none;">{{ $product->created_at->format('Y-m-d H:i:s') }}</span>
                                                    {{ $product->created_at->format('d M Y') }}
                                                    <br>
                                                    <small class="text-muted">{{ $product->created_at->diffForHumans() }}</small>
                                                </td>
                                                <td>
                                                    <div>{{ $product->updated_at->format('d M Y') }}</div>
                                                    <small class="text-muted">{{ $product->updated_at->diffForHumans() }}</small>
                                                </td>
                                                <td class="text-center">
                                                    <div class="hub-dt-actions">
                                                        <a href="{{ route('products.show', $product) }}" class="hub-btn hub-btn-sm hub-btn-outline" title="Detail"><i class="fas fa-eye"></i></a>
                                                        @if($product->external_platform === 'shopee')
                                                            <button type="button" class="hub-btn hub-btn-sm hub-btn-outline" disabled title="Shopee"><i class="fas fa-lock"></i></button>
                                                        @else
                                                            <a href="{{ route('products.edit', $product) }}" class="hub-btn hub-btn-sm hub-btn-outline" title="Edit"><i class="fas fa-edit"></i></a>
                                                            @if($product->orders_count == 0)
                                                                <button type="button" class="hub-btn hub-btn-sm hub-btn-outline" onclick="deleteProduct({{ $product->id }})" title="Hapus"><i class="fas fa-trash"></i></button>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-box fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">Tidak ada produk ditemukan</h5>
                                <p class="text-muted">Coba ubah filter pencarian atau tambah produk baru</p>
                                <a href="{{ route('products.create') }}" class="btn btn-red">
                                    <i class="fas fa-plus me-2"></i>Tambah Produk Pertama
                                </a>
                            </div>
                        @endif
        </div>
    </div>

    <div class="row g-3" id="gridViewContent" style="display: none;">
            @if($products->count() > 0)
                @foreach($products as $product)
                    <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                        <div class="card card-modern h-100">
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <div class="avatar mx-auto mb-3">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto"
                                            style="width: 80px; height: 80px; background: var(--light-red); color: var(--primary-red);">
                                            <i class="fas fa-box fa-2x"></i>
                                        </div>
                                    </div>
                                    <h6 class="fw-bold mb-2" title="{{ $product->name }}">
                                        {{ Str::limit($product->name, 30) }}
                                    </h6>
                                    @if($product->category)
                                        <span class="badge bg-info mb-2">{{ $product->category }}</span>
                                    @endif
                                    @if($product->external_platform === 'shopee')
                                        <span class="badge bg-warning text-dark mb-2">Shopee</span>
                                    @endif
                                </div>

                                @if($product->description)
                                    <p class="text-muted small mb-3" title="{{ $product->description }}">
                                        {{ Str::limit($product->description, 80) }}
                                    </p>
                                @endif

                                <div class="row text-center mb-3">
                                    <div class="col-6">
                                        @if($product->base_price)
                                            <div class="fw-bold" style="color: var(--primary-red);">
                                                Rp {{ number_format($product->base_price, 0, ',', '.') }}
                                            </div>
                                            <small class="text-muted">per {{ $product->unit }}</small>
                                        @else
                                            <div class="text-muted">Harga Custom</div>
                                            <small class="text-muted">per {{ $product->unit }}</small>
                                        @endif
                                    </div>
                                    <div class="col-6">
                                        <div class="fw-bold text-primary">{{ $product->orders_count ?? 0 }}</div>
                                        <small class="text-muted">pesanan</small>
                                    </div>
                                </div>

                                <div class="text-center mb-3">
                                    @if($product->is_active)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Aktif
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="fas fa-times-circle me-1"></i>Tidak Aktif
                                        </span>
                                    @endif
                                </div>

                                <div class="d-grid gap-2">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('products.show', $product) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($product->external_platform === 'shopee')
                                            <button class="btn btn-outline-secondary btn-sm" disabled title="Produk Shopee (read-only)">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        @else
                                            <a href="{{ route('products.edit', $product) }}" class="btn btn-outline-warning btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($product->orders_count == 0)
                                                <button class="btn btn-outline-danger btn-sm" onclick="deleteProduct({{ $product->id }})">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @else
                                                <button class="btn btn-outline-secondary btn-sm" disabled title="Tidak bisa dihapus (ada pesanan)">
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- Pagination for Grid -->
                <div class="col-12">
                    <div class="d-flex justify-content-center">
                        {{-- Tidak pakai paginasi jika DataTables --}}
                    </div>
                </div>
            @else
                <div class="col-12">
                    <div class="card card-modern">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-box fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">Tidak ada produk ditemukan</h5>
                            <p class="text-muted">Coba ubah filter pencarian atau tambah produk baru</p>
                            <a href="{{ route('products.create') }}" class="btn btn-red">
                                <i class="fas fa-plus me-2"></i>Tambah Produk Pertama
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>

    <form id="deleteForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection

@push('styles')
    @include('hub.partials.datatables-assets')
@endpush

@push('scripts')
    @include('hub.partials.datatables-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            HubDataTable.init('#productsTable', {
                order: [[7, 'desc']],
                pageLength: 12,
            });

            $('#gridView').on('click', function () {
                $('#tableViewContent').hide();
                $('#gridViewContent').show().css('display', 'flex');
                $(this).addClass('hub-btn-primary').removeClass('hub-btn-outline');
                $('#tableView').addClass('hub-btn-outline').removeClass('hub-btn-primary');
            });
            $('#tableView').on('click', function () {
                $('#gridViewContent').hide();
                $('#tableViewContent').show();
                $(this).addClass('hub-btn-primary').removeClass('hub-btn-outline');
                $('#gridView').addClass('hub-btn-outline').removeClass('hub-btn-primary');
            });
        });

        // Delete product function
        function deleteProduct(productId) {
            Swal.fire({
                title: 'Hapus Produk?',
                text: 'Data produk akan dihapus permanen dan tidak dapat dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('deleteForm');
                    form.action = `/products/${productId}`;
                    form.submit();
                }
            });
        }

        // Export products function
        function exportProducts() {
            Swal.fire({
                title: 'Export Data Produk',
                text: 'Fitur export produk akan segera tersedia',
                icon: 'info',
                confirmButtonColor: '#dc2626'
            });
        }
    </script>
@endpush
