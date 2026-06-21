@extends('layouts.hub')

@section('title', 'Analisis Produk — Pilih Produk')

@push('styles')
<link href="{{ asset('css/hub-monitoring.css') }}?v=6" rel="stylesheet">
@endpush

@section('content')
<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-microscope me-2"></i>Analisis Produk</h1>
        <p class="small mb-0 opacity-90">Pilih satu produk untuk melihat performa lengkap: laba, iklan, ROAS, BCG, HPP, dan breakdown varian.</p>
    </div>

    @include('hub.partials.hub-zone-nav')

    <div class="hub-card mb-3">
        <div class="hub-card-body">
            <form method="GET" action="{{ route('monitoring.product-analysis.index') }}" class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="form-label small text-muted">Cari nama, SKU, atau kode produk Shopee</label>
                    <input type="search" name="q" value="{{ $search ?? '' }}" class="hub-form-control" placeholder="Contoh: sticker custom, SKU-001, 123456789">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="hub-btn hub-btn-primary w-100"><i class="fas fa-search me-1"></i> Cari</button>
                </div>
            </form>
        </div>
    </div>

    <div class="hub-card">
        <div class="hub-card-header">
            <h2 class="report-section-title mb-0">Daftar produk — {{ $shop['label'] ?? '' }}</h2>
        </div>
        <div class="hub-card-body p-0">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Item ID</th>
                        <th class="num">Varian</th>
                        <th class="num">Harga katalog</th>
                        <th>HPP</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($products as $p)
                <tr>
                    <td>
                        <strong>{{ $p['name'] }}</strong>
                        @if($p['sku'])<div class="small text-muted">SKU {{ $p['sku'] }}</div>@endif
                    </td>
                    <td>{{ $p['item_id'] ?: '—' }}</td>
                    <td class="num">{{ $p['variant_count'] }}</td>
                    <td class="num">{{ $p['base_price'] > 0 ? hub_rp($p['base_price']) : '—' }}</td>
                    <td>
                        @if($p['hpp_ok'])
                            <span class="hub-pill hub-pill-success">OK</span>
                        @else
                            <span class="hub-pill hub-pill-warning">Missing</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('monitoring.product-analysis.show', ['product' => $p['id']] + request()->query()) }}" class="hub-btn hub-btn-sm hub-btn-primary">
                            Analisis <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada produk. Sync katalog Shopee atau ubah kata kunci pencarian.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
