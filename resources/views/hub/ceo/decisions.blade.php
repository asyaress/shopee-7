@extends('layouts.hub')
@section('title', 'Log Keputusan')
@section('content')
@include('hub.partials.ceo.shell-open')
    @include('hub.partials.hub-zone-nav')

    <div class="hub-card mb-3" data-ceo="form">
        <div class="hub-card-header"><h2 class="report-section-title">Catat keputusan baru</h2></div>
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
                            <option value="stop_sku">Stop jual SKU</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <input type="text" name="title" class="hub-form-control" placeholder="Contoh: Naik harga sticker 5%" required>
                    </div>
                    <div class="col-md-4">
                        <input type="number" name="product_id" class="hub-form-control" placeholder="ID produk (opsional)">
                    </div>
                    <div class="col-12">
                        <textarea name="note" class="hub-form-control" rows="2" placeholder="Kenapa? Hasil yang diharapkan?"></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="hub-btn hub-btn-primary">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="hub-card">
        <div class="hub-card-header"><h2 class="report-section-title">Riwayat</h2></div>
        <div class="hub-card-body p-0">
            <table class="report-table">
                <thead><tr><th>Tanggal</th><th>Tipe</th><th>Judul</th><th>Catatan</th></tr></thead>
                <tbody>
                @forelse($logs ?? [] as $d)
                <tr>
                    <td>{{ $d->created_at?->format('d M Y') }}</td>
                    <td>{{ str_replace('_', ' ', $d->decision_type) }}</td>
                    <td>{{ $d->title }}</td>
                    <td class="small">{{ $d->note }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-muted text-center py-3">Belum ada catatan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@include('hub.partials.ceo.shell-close')
@endsection
