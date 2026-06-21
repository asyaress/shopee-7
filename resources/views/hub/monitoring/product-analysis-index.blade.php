@extends('layouts.hub')
@section('title', 'Analisis Produk')
@section('content')
@php
    $f = $filters ?? [];
    $st = $status_counts ?? [];
    $q = request()->query();
@endphp
@include('hub.partials.ceo.shell-open')

    @include('hub.partials.hub-zone-nav')

    <div class="hub-card mb-3" data-ceo="form">
        <div class="hub-card-body">
            <form method="GET" action="{{ route('monitoring.product-analysis.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5 col-lg-4">
                    <label class="form-label small text-muted">Cari nama atau SKU produk</label>
                    <input type="search" name="q" value="{{ $search ?? '' }}" class="hub-form-control" placeholder="Contoh: sticker, SKU-001">
                </div>
                <div class="col-md-4 col-lg-3">
                    <label class="form-label small text-muted">Status produk</label>
                    <select name="product_status" class="hub-form-select">
                        <option value="all" @selected(($f['product_status'] ?? 'all') === 'all')>Semua ({{ hub_num($st['all'] ?? 0) }})</option>
                        <option value="active" @selected(($f['product_status'] ?? 'all') === 'active')>Aktif ({{ hub_num($st['active'] ?? 0) }})</option>
                        <option value="inactive" @selected(($f['product_status'] ?? 'all') === 'inactive')>Nonaktif ({{ hub_num($st['inactive'] ?? 0) }})</option>
                        <option value="archive" @selected(($f['product_status'] ?? 'all') === 'archive')>Archive ({{ hub_num($st['archive'] ?? 0) }})</option>
                    </select>
                </div>
                <div class="col-md-3 col-lg-2">
                    <label class="form-label small text-muted">Per halaman</label>
                    <select name="per_page" class="hub-form-select">
                        @foreach([20, 30, 50] as $n)
                        <option value="{{ $n }}" @selected((int)($f['per_page'] ?? 20) === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-12 col-lg-3">
                    <button type="submit" class="hub-btn hub-btn-primary w-100"><i class="fas fa-search me-1"></i> Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="hub-card">
        <div class="hub-card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="report-section-title mb-0">Pilih produk — {{ $shop['label'] ?? '' }}</h2>
                @if(isset($productsPaginator))
                <p class="report-section-desc mb-0">{{ hub_num($productsPaginator->total()) }} produk · halaman {{ $productsPaginator->currentPage() }}/{{ $productsPaginator->lastPage() }}</p>
                @endif
            </div>
        </div>
        <div class="hub-card-body p-0">
            <div class="table-responsive">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Status</th>
                            <th>ID Shopee</th>
                            <th class="num">Varian</th>
                            <th class="num">Harga</th>
                            <th>HPP</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($productsPaginator ?? [] as $p)
                    <tr>
                        <td>
                            <strong>{{ $p['name'] }}</strong>
                            @if($p['sku'] ?? null)<div class="small text-muted">{{ $p['sku'] }}</div>@endif
                        </td>
                        <td>
                            @php
                                $statusClass = match($p['status'] ?? 'active') {
                                    'archive' => 'hub-pill-warning',
                                    'inactive' => 'hub-pill-danger',
                                    default => 'hub-pill-success',
                                };
                            @endphp
                            <span class="hub-pill {{ $statusClass }}">{{ $p['status_label'] ?? 'Aktif' }}</span>
                        </td>
                        <td>{{ $p['item_id'] ?: '—' }}</td>
                        <td class="num">{{ $p['variant_count'] ?? 0 }}</td>
                        <td class="num">{{ hub_rp($p['base_price'] ?? 0) }}</td>
                        <td>@if($p['hpp_ok'] ?? false)<span class="text-success">✓</span>@else<span class="text-danger">Kosong</span>@endif</td>
                        <td><a href="{{ route('monitoring.product-analysis.show', ['product' => $p['id']] + $q) }}" class="hub-btn hub-btn-sm hub-btn-outline">Analisis →</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada produk ditemukan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if(isset($productsPaginator) && $productsPaginator->total() > 0)
            <div class="hub-pagination px-3 py-3">
                <span class="hub-pagination-info">
                    Menampilkan {{ $productsPaginator->firstItem() ?? 0 }}–{{ $productsPaginator->lastItem() ?? 0 }}
                    dari {{ $productsPaginator->total() }} produk
                </span>
                {{ $productsPaginator->withQueryString()->links() }}
            </div>
            @endif
        </div>
    </div>
@include('hub.partials.ceo.shell-close')
@endsection
