@extends('layouts.hub')

@section('title', 'Input HPP — Shopee Profit Hub')

@section('content')
@php
    $st = $stats ?? [];
    $f = $filters ?? [];
@endphp
<div class="report-shell">
    @include('hub.partials.page-hero', [
        'icon' => 'fa-tags',
        'title' => 'Input HPP & Packaging',
        'subtitle' => 'Isi biaya pokok per produk — dipakai di laporan Monitoring',
        'meta' => [
            ['icon' => 'fa-chart-pie', 'text' => ($st['pct'] ?? 0) . '% lengkap'],
            ['icon' => 'fa-exclamation-triangle', 'text' => ($st['missing'] ?? 0) . ' belum HPP'],
        ],
        'actions' => '<a href="' . route('products.costs') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-layer-group"></i> Editor Varian</a>'
            . '<a href="' . route('manage.index') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-database"></i> Kelola</a>',
    ])

    <div class="report-kpi-hero" style="grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));">
        <div class="report-kpi-card"><div class="label">Total SKU</div><div class="value">{{ hub_num($st['total'] ?? 0) }}</div></div>
        <div class="report-kpi-card positive"><div class="label">HPP Terisi</div><div class="value">{{ hub_num($st['with_hpp'] ?? 0) }}</div></div>
        <div class="report-kpi-card {{ ($st['missing'] ?? 0) > 0 ? 'warn' : 'positive' }}"><div class="label">Belum HPP</div><div class="value">{{ hub_num($st['missing'] ?? 0) }}</div></div>
        <div class="report-kpi-card"><div class="label">Kelengkapan</div><div class="value">{{ $st['pct'] ?? 0 }}%</div></div>
    </div>

    @if(($st['missing'] ?? 0) > 0)
    <div class="report-insights mb-3">
        <div class="report-insight warning">
            <div class="icon"><i class="fas fa-tags"></i></div>
            <div><strong>{{ $st['missing'] }} produk belum punya HPP</strong><p>Filter "Belum HPP" di bawah, isi kolom HPP lalu simpan.</p></div>
        </div>
    </div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach(['all' => 'Semua', 'missing' => 'Belum HPP', 'complete' => 'Sudah Lengkap'] as $key => $label)
            <a href="{{ route('hpp.index', array_merge(request()->except('fill', 'page'), ['fill' => $key])) }}"
                class="hub-btn hub-btn-sm {{ ($f['fill'] ?? 'all') === $key ? 'hub-btn-primary' : 'hub-btn-outline' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="report-filter-card">
        <form method="GET" action="{{ route('hpp.index') }}" class="row g-2 align-items-end">
            <input type="hidden" name="fill" value="{{ $f['fill'] ?? 'all' }}">
            <div class="col-md-4">
                <label class="hub-form-label">Cari produk</label>
                <input type="search" name="search" class="hub-form-control" value="{{ $f['search'] ?? '' }}" placeholder="Nama, ID Shopee…">
            </div>
            <div class="col-md-3">
                <label class="hub-form-label">Kategori</label>
                <select name="category" class="hub-form-select hub-form-control">
                    <option value="">Semua</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" @selected(($f['category'] ?? '') === $cat)>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="hub-form-label">Platform</label>
                <select name="platform" class="hub-form-select hub-form-control">
                    <option value="">Semua</option>
                    <option value="shopee" @selected(($f['platform'] ?? '') === 'shopee')>Shopee</option>
                    <option value="internal" @selected(($f['platform'] ?? '') === 'internal')>Internal</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="hub-btn hub-btn-primary w-100"><i class="fas fa-filter"></i> Terapkan</button>
            </div>
        </form>
    </div>

    <form method="POST" action="{{ route('hpp.save') }}" id="hppForm">
        @csrf
        @if($f['search'] ?? false)<input type="hidden" name="search" value="{{ $f['search'] }}">@endif
        @if($f['category'] ?? false)<input type="hidden" name="category" value="{{ $f['category'] }}">@endif
        @if($f['platform'] ?? false)<input type="hidden" name="platform" value="{{ $f['platform'] }}">@endif
        @if(($f['fill'] ?? 'all') !== 'all')<input type="hidden" name="fill" value="{{ $f['fill'] }}">@endif

        <div class="hub-card">
            <div class="hub-card-header flex-wrap">
                <h2 class="report-section-title"><i class="fas fa-table me-2"></i>Tabel HPP</h2>
                <span class="hub-pill hub-pill-muted">{{ $products->count() }} baris</span>
            </div>
            <div class="hub-card-body p-0 hub-dt-wrap">
                @if($products->isNotEmpty())
                <div class="table-responsive">
                    <table id="hppTable" class="table hub-dt-table mb-0" style="width:100%">
                        <thead>
                            <tr>
                                <th style="width:40px">#</th>
                                <th>Produk</th>
                                <th class="num">Harga jual</th>
                                <th class="num" style="min-width:120px">HPP (Rp)</th>
                                <th style="min-width:100px">Pack</th>
                                <th class="num" style="min-width:90px">Nilai</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $i => $product)
                            @php
                                $hasHpp = $product->hpp_amount !== null;
                                $price = (float) ($product->base_price ?? 0);
                                $hpp = (float) ($product->hpp_amount ?? 0);
                                $packType = $product->packaging_type ?? 'fixed';
                                $packVal = (float) ($product->packaging_value ?? 0);
                                $packCost = $packType === 'percent' && $price > 0 ? $price * $packVal / 100 : $packVal;
                                $margin = $price > 0 ? (($price - $hpp - $packCost) / $price) * 100 : null;
                            @endphp
                            <input type="hidden" name="products[{{ $i }}][id]" value="{{ $product->id }}">
                            <tr class="{{ $hasHpp ? '' : 'row-missing-hpp' }}" data-hpp-row>
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td>
                                    <div class="product-cell">
                                        <span class="name" title="{{ $product->name }}">{{ Str::limit($product->name, 50) }}</span>
                                        @if($product->external_item_id)
                                            <span class="sku">ID {{ $product->external_item_id }}</span>
                                        @endif
                                        @if($product->external_platform === 'shopee')
                                            <span class="hub-pill hub-pill-muted hub-pill-sm mt-1">Shopee</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="num">
                                    @if($price > 0)
                                        {{ hub_rp($price) }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="num">
                                    <input type="number" name="products[{{ $i }}][hpp_amount]" class="hub-form-control hub-form-control-sm hpp-input"
                                        min="0" step="100" value="{{ $product->hpp_amount }}" placeholder="0"
                                        data-price="{{ $price }}">
                                </td>
                                <td>
                                    <select name="products[{{ $i }}][packaging_type]" class="hub-form-select hub-form-control-sm pack-type-input">
                                        <option value="fixed" @selected($packType === 'fixed')>Rp</option>
                                        <option value="percent" @selected($packType === 'percent')>%</option>
                                    </select>
                                </td>
                                <td class="num">
                                    <input type="number" name="products[{{ $i }}][packaging_value]" class="hub-form-control hub-form-control-sm pack-val-input"
                                        min="0" step="0.01" value="{{ $product->packaging_value }}">
                                </td>
                                <td>
                                    @if($hasHpp)
                                        <span class="hub-pill hub-pill-success">OK</span>
                                        @if($margin !== null)
                                            <span class="hub-dt-sub {{ $margin >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($margin, 1) }}% margin</span>
                                        @endif
                                    @else
                                        <span class="hub-pill hub-pill-warning">Kosong</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="hub-dt-empty">
                    <i class="fas fa-box-open fa-3x mb-3 opacity-50"></i>
                    <p>Tidak ada produk. Jalankan <strong>Sync Produk</strong> di Kelola Data.</p>
                    <a href="{{ route('manage.index') }}" class="hub-btn hub-btn-primary mt-2">Ke Kelola Data</a>
                </div>
                @endif
            </div>
        </div>
    </form>

    <div class="hpp-save-bar" id="hppSaveBar">
        <div>
            <strong><i class="fas fa-pen me-2"></i>Ada perubahan belum disimpan</strong>
            <span class="opacity-75 ms-2 small">Tekan Simpan untuk update laporan Monitoring</span>
        </div>
        <button type="submit" form="hppForm" class="hub-btn hub-btn-primary" style="background:#fff;color:var(--maroon-800)!important;">
            <i class="fas fa-save"></i> Simpan Semua HPP
        </button>
    </div>
</div>
@endsection

@push('styles')
    @include('hub.partials.datatables-assets')
@endpush

@push('scripts')
    @include('hub.partials.datatables-scripts')
<script>
(function () {
    const form = document.getElementById('hppForm');
    const bar = document.getElementById('hppSaveBar');
    let dirty = false;

    const markDirty = () => {
        if (!dirty) {
            dirty = true;
            bar?.classList.add('show');
            document.body.style.paddingBottom = '72px';
        }
    };

    form?.querySelectorAll('input, select').forEach(el => {
        el.addEventListener('change', markDirty);
        el.addEventListener('input', markDirty);
    });

    if (document.getElementById('hppTable')) {
        HubDataTable.init('#hppTable', {
            pageLength: 25,
            order: [[1, 'asc']],
            columnDefs: [
                { orderable: false, targets: [3, 4, 5, 6] },
            ],
        });
    }
})();
</script>
@endpush
