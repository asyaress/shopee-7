@extends('layouts.hub')
@section('title', 'Analisis Produk')
@section('content')
@include('hub.partials.ceo.shell-open')

    @include('hub.partials.hub-zone-nav')

    <div class="hub-card mb-3" data-ceo="form">
        <div class="hub-card-body">
            <form method="GET" action="{{ route('monitoring.product-analysis.index') }}" class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="form-label small text-muted">Cari nama atau SKU produk</label>
                    <input type="search" name="q" value="{{ $search ?? '' }}" class="hub-form-control" placeholder="Contoh: sticker, SKU-001">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="hub-btn hub-btn-primary w-100"><i class="fas fa-search me-1"></i> Cari</button>
                </div>
            </form>
        </div>
    </div>

    <div class="hub-card">
        <div class="hub-card-header">
            <h2 class="report-section-title mb-0">Pilih produk — {{ $shop['label'] ?? '' }}</h2>
        </div>
        <div class="hub-card-body p-0">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>ID Shopee</th>
                        <th class="num">Varian</th>
                        <th class="num">Harga</th>
                        <th>HPP</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($products as $p)
                <tr>
                    <td><strong>{{ $p['name'] }}</strong>@if($p['sku'] ?? null)<div class="small text-muted">{{ $p['sku'] }}</div>@endif</td>
                    <td>{{ $p['item_id'] ?: '—' }}</td>
                    <td class="num">{{ $p['variant_count'] ?? 0 }}</td>
                    <td class="num">{{ hub_rp($p['base_price'] ?? 0) }}</td>
                    <td>@if($p['hpp_ok'] ?? false)<span class="text-success">✓</span>@else<span class="text-danger">Kosong</span>@endif</td>
                    <td><a href="{{ route('monitoring.product-analysis.show', ['product' => $p['id']]) }}" class="hub-btn hub-btn-sm hub-btn-outline">Analisis →</a></td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada produk ditemukan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@include('hub.partials.ceo.shell-close')
@endsection
