@extends('layouts.hub')
@section('title', 'Log Keputusan — CEO')
@push('styles')<link href="{{ asset('css/hub-monitoring.css') }}?v=2" rel="stylesheet">@endpush
@section('content')
<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-clipboard-list me-2"></i>Log Keputusan</h1>
        <p class="small mb-0">Catat apa yang Anda lakukan (potong iklan, naik harga, dll.) untuk evaluasi minggu depan.</p>
    </div>
    @include('hub.partials.hub-zone-nav')

    <div class="hub-card mb-3">
        <div class="hub-card-header"><h2 class="report-section-title">Tambah catatan</h2></div>
        <div class="hub-card-body">
            <form method="POST" action="{{ route('ceo.decisions.store') }}">
                @csrf
                <div class="row g-2">
                    <div class="col-md-3">
                        <select name="decision_type" class="hub-form-select" required>
                            <option value="cut_ads">Potong iklan</option>
                            <option value="scale_ads">Naikkan iklan</option>
                            <option value="raise_price">Naikkan harga</option>
                            <option value="fix_hpp">Perbaiki HPP</option>
                            <option value="stop_sku">Stop / pause SKU</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="title" class="hub-form-control" placeholder="Judul singkat" required>
                    </div>
                    <div class="col-md-4">
                        <input type="number" name="product_id" class="hub-form-control" placeholder="Product ID (opsional)">
                    </div>
                    <div class="col-12">
                        <textarea name="note" class="hub-form-control" rows="2" placeholder="Catatan / hasil yang diharapkan"></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="hub-btn hub-btn-primary">Simpan keputusan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="hub-card">
        <div class="hub-card-body p-0">
            <table class="report-table">
                <thead><tr><th>Waktu</th><th>Tipe</th><th>Judul</th><th>Produk</th><th>Catatan</th></tr></thead>
                <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="small">{{ $log->created_at->format('d M Y H:i') }}</td>
                    <td><code>{{ $log->decision_type }}</code></td>
                    <td>{{ $log->title }}</td>
                    <td>{{ $log->product?->name ?? '—' }}</td>
                    <td class="small">{{ Str::limit($log->note, 80) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada log.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
