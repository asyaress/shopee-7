@extends('layouts.hub')

@section('title', 'Input HPP')

@push('styles')
<link href="{{ asset('css/hub-hpp.css') }}?v=2" rel="stylesheet">
@endpush

@section('content')
@php
    $st = $stats ?? [];
    $f = $filters ?? [];
    $pageMeta = [
        ['icon' => 'fas fa-check-circle', 'label' => 'HPP terisi', 'value' => ($st['pct'] ?? 0).'%'],
        ['icon' => 'fas fa-layer-group', 'label' => 'Varian', 'value' => hub_num($st['variants'] ?? 0)],
    ];
@endphp

<div class="hpp-workspace">
@include('hub.partials.ceo.shell-open')

    @if(session('success'))
        <div class="alert alert-success hpp-alert"><i class="fas fa-circle-check"></i> {{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger hpp-alert">
            <i class="fas fa-triangle-exclamation"></i>
            <div><strong>Biaya belum tersimpan.</strong> {{ $errors->first() }}</div>
        </div>
    @endif

    <section class="hpp-kpi-grid" aria-label="Ringkasan kelengkapan HPP">
        <article class="hpp-kpi-card">
            <span class="hpp-kpi-icon"><i class="fas fa-boxes-stacked"></i></span>
            <div><small>Total produk</small><strong>{{ hub_num($st['total'] ?? 0) }}</strong></div>
        </article>
        <article class="hpp-kpi-card is-good">
            <span class="hpp-kpi-icon"><i class="fas fa-circle-check"></i></span>
            <div><small>Siap dihitung</small><strong>{{ hub_num($st['complete'] ?? 0) }}</strong></div>
        </article>
        <article class="hpp-kpi-card {{ ($st['missing'] ?? 0) > 0 ? 'is-warning' : 'is-good' }}">
            <span class="hpp-kpi-icon"><i class="fas fa-triangle-exclamation"></i></span>
            <div><small>Perlu dilengkapi</small><strong>{{ hub_num($st['missing'] ?? 0) }}</strong></div>
        </article>
        <article class="hpp-kpi-card">
            <span class="hpp-kpi-icon"><i class="fas fa-code-branch"></i></span>
            <div><small>Override varian</small><strong>{{ hub_num($st['variant_overrides'] ?? 0) }}</strong></div>
        </article>
    </section>

    <section class="hpp-priority-panel">
        <div>
            <span class="eyebrow">Alur yang disarankan</span>
            <h2>Isi default produk dahulu</h2>
            <p>Semua varian otomatis mewarisi HPP dan packaging produk. Buka varian hanya jika biayanya berbeda.</p>
        </div>
        <div class="hpp-progress-wrap">
            <div class="hpp-progress-label"><span>Kelengkapan</span><strong>{{ $st['pct'] ?? 0 }}%</strong></div>
            <div class="hpp-progress"><span style="width: {{ min(100, (int) ($st['pct'] ?? 0)) }}%"></span></div>
        </div>
    </section>

    <nav class="hpp-status-tabs" aria-label="Filter status biaya">
        @foreach([
            'all' => ['Semua', $st['total'] ?? 0],
            'missing' => ['Perlu dilengkapi', $st['missing'] ?? 0],
            'complete' => ['Sudah siap', $st['complete'] ?? 0],
            'variants' => ['Punya varian', $st['products_with_variants'] ?? 0],
        ] as $key => [$label, $count])
            <a href="{{ route('hpp.index', array_merge(request()->except('fill'), ['fill' => $key])) }}"
               class="hpp-status-tab {{ ($f['fill'] ?? 'all') === $key ? 'active' : '' }}">
                {{ $label }} <span>{{ hub_num($count) }}</span>
            </a>
        @endforeach
    </nav>

    <section class="report-filter-card hpp-filter-card">
        <form method="GET" action="{{ route('hpp.index') }}" class="row g-2 align-items-end">
            <input type="hidden" name="fill" value="{{ $f['fill'] ?? 'all' }}">
            <div class="col-lg-4">
                <label class="hub-form-label">Cari produk atau varian</label>
                <div class="hpp-search-input">
                    <i class="fas fa-search"></i>
                    <input type="search" name="search" class="hub-form-control" value="{{ $f['search'] ?? '' }}" placeholder="Nama, SKU, atau ID Shopee">
                </div>
            </div>
            <div class="col-md-4 col-lg-2">
                <label class="hub-form-label">Kategori</label>
                <select name="category" class="hub-form-select hub-form-control">
                    <option value="">Semua kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}" @selected(($f['category'] ?? '') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 col-lg-2">
                <label class="hub-form-label">Sumber</label>
                <select name="platform" class="hub-form-select hub-form-control">
                    <option value="">Semua</option>
                    <option value="shopee" @selected(($f['platform'] ?? '') === 'shopee')>Shopee</option>
                    <option value="internal" @selected(($f['platform'] ?? '') === 'internal')>Internal</option>
                </select>
            </div>
            <div class="col-md-4 col-lg-2">
                <label class="hub-form-label">Status produk</label>
                <select name="product_status" class="hub-form-select hub-form-control">
                    <option value="active" @selected(($f['product_status'] ?? 'active') === 'active')>Aktif ({{ hub_num($st['product_statuses']['active'] ?? 0) }})</option>
                    <option value="archive" @selected(($f['product_status'] ?? 'active') === 'archive')>Archive ({{ hub_num($st['product_statuses']['archive'] ?? 0) }})</option>
                    <option value="inactive" @selected(($f['product_status'] ?? 'active') === 'inactive')>Nonaktif ({{ hub_num($st['product_statuses']['inactive'] ?? 0) }})</option>
                    <option value="all" @selected(($f['product_status'] ?? 'active') === 'all')>Semua ({{ hub_num($st['product_statuses']['all'] ?? 0) }})</option>
                </select>
            </div>
            <div class="col-md-4 col-lg-2 d-flex gap-2">
                <button class="hub-btn hub-btn-primary flex-grow-1" type="submit">Terapkan</button>
                <a class="hub-btn hub-btn-outline" href="{{ route('hpp.index') }}" title="Reset filter"><i class="fas fa-rotate-left"></i></a>
            </div>
        </form>
    </section>

    <div class="hpp-list-toolbar">
        <div><strong>{{ hub_num($products->count()) }} produk ditampilkan</strong><span>Klik produk untuk mengedit biaya. Perubahan tersimpan otomatis.</span></div>
        <div class="d-flex gap-2">
            <span class="hpp-autosave-note"><i class="fas fa-cloud-arrow-up"></i> Autosave aktif</span>
            <button type="button" class="hub-btn hub-btn-sm hub-btn-outline" data-expand-all><i class="fas fa-angles-down"></i> Buka semua</button>
            <button type="button" class="hub-btn hub-btn-sm hub-btn-outline" data-collapse-all><i class="fas fa-angles-up"></i> Tutup semua</button>
        </div>
    </div>

    <form method="POST" action="{{ route('hpp.save') }}" id="hppForm">
        @csrf
        @foreach(['search', 'category', 'platform', 'fill', 'product_status'] as $filterKey)
            @if(($f[$filterKey] ?? '') !== '' && !($filterKey === 'fill' && $f[$filterKey] === 'all'))
                <input type="hidden" name="{{ $filterKey }}" value="{{ $f[$filterKey] }}">
            @endif
        @endforeach

        <div class="hpp-product-list">
            @forelse($products as $product)
                @php
                    $variants = $product->variants ?? collect();
                    $hasVariants = $variants->isNotEmpty();
                    $isComplete = $hasVariants
                        ? $variants->every(fn ($variant) => $variant->hpp_amount !== null || $product->hpp_amount !== null)
                        : $product->hpp_amount !== null;
                    $overrideCount = $variants->filter(fn ($variant) => $variant->hpp_amount !== null || $variant->packaging_type !== null)->count();
                    $packType = $product->packaging_type ?: 'fixed';
                    $hppDisplay = $product->hpp_amount !== null ? number_format((float) $product->hpp_amount, 0, ',', '.') : '';
                    $packDisplay = $product->packaging_value !== null
                        ? ($packType === 'fixed' ? number_format((float) $product->packaging_value, 0, ',', '.') : (float) $product->packaging_value)
                        : '';
                @endphp

                <article class="hpp-product-card {{ $isComplete ? 'is-complete' : 'is-missing' }}"
                         data-product-card data-product-id="{{ $product->id }}" data-price="{{ (float) ($product->base_price ?? 0) }}">
                    <header class="hpp-product-head">
                        <button type="button" class="hpp-product-toggle" data-product-toggle aria-expanded="false" aria-controls="hpp-product-{{ $product->id }}">
                            <span class="hpp-product-thumb">
                                @if($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="" loading="lazy">
                                @else
                                    <i class="fas fa-box"></i>
                                @endif
                            </span>
                            <span class="hpp-product-title">
                                <strong>{{ $product->name }}</strong>
                                <small>
                                    @if($product->external_sku) SKU {{ $product->external_sku }} @endif
                                    @if($product->external_item_id) <span>ID {{ $product->external_item_id }}</span> @endif
                                </small>
                            </span>
                            <span class="hpp-head-metric"><small>Harga</small><strong>{{ ($product->base_price ?? 0) > 0 ? hub_rp($product->base_price) : 'Custom' }}</strong></span>
                            <span class="hpp-head-metric"><small>HPP default</small><strong data-head-hpp>{{ $product->hpp_amount !== null ? hub_rp($product->hpp_amount) : 'Belum diisi' }}</strong></span>
                            <span class="hpp-variant-count"><i class="fas fa-layer-group"></i> {{ $variants->count() }} varian</span>
                            <span class="hpp-autosave-state is-saved" data-save-state aria-live="polite">
                                <i class="fas fa-cloud-check"></i><span>Tersimpan</span>
                            </span>
                            <span class="hpp-cost-status {{ $isComplete ? 'complete' : 'missing' }}" data-cost-status>
                                <i class="fas {{ $isComplete ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
                                {{ $isComplete ? 'Siap' : 'Lengkapi' }}
                            </span>
                            <i class="fas fa-chevron-down hpp-toggle-icon"></i>
                        </button>
                    </header>

                    <div class="hpp-product-body" id="hpp-product-{{ $product->id }}" hidden>
                        <div class="hpp-default-section">
                            <div class="hpp-section-heading">
                                <div><span class="eyebrow">Default produk</span><h3>Biaya yang diwarisi semua varian</h3></div>
                                @if($product->category)<span class="hub-pill hub-pill-muted">{{ $product->category }}</span>@endif
                            </div>

                            <div class="hpp-cost-grid">
                                <label class="hpp-field">
                                    <span>HPP per unit <b>*</b></span>
                                    <div class="hpp-input-group"><span>Rp</span><input type="text" inputmode="numeric" value="{{ $hppDisplay }}" placeholder="Contoh 15.000" data-money="true" data-product-field="hpp_amount"></div>
                                    <small>Harga beli atau biaya produksi satu unit.</small>
                                </label>
                                <label class="hpp-field">
                                    <span>Jenis packaging</span>
                                    <select data-product-field="packaging_type">
                                        <option value="fixed" @selected($packType === 'fixed')>Nominal per unit</option>
                                        <option value="percent" @selected($packType === 'percent')>Persentase harga</option>
                                    </select>
                                    <small>Pilih nominal untuk biaya kemasan yang tetap.</small>
                                </label>
                                <label class="hpp-field">
                                    <span>Nilai packaging</span>
                                    <div class="hpp-input-group"><span data-pack-unit>{{ $packType === 'percent' ? '%' : 'Rp' }}</span><input type="text" inputmode="{{ $packType === 'percent' ? 'decimal' : 'numeric' }}" value="{{ $packDisplay }}" placeholder="0" data-money="{{ $packType === 'fixed' ? 'true' : 'false' }}" data-product-field="packaging_value"></div>
                                    <small data-pack-help>{{ $packType === 'percent' ? 'Persen dari harga jual.' : 'Biaya kemasan per unit.' }}</small>
                                </label>
                                <div class="hpp-live-preview">
                                    <span>Estimasi unit economics</span>
                                    <div><small>Total biaya</small><strong data-preview-cost>Rp 0</strong></div>
                                    <div><small>Laba sebelum fee</small><strong data-preview-profit>Rp 0</strong></div>
                                    <div><small>Margin</small><strong data-preview-margin>-</strong></div>
                                </div>
                            </div>
                        </div>

                        @if($hasVariants)
                            <section class="hpp-variant-section">
                                <div class="hpp-section-heading">
                                    <div>
                                        <span class="eyebrow">{{ $variants->count() }} varian</span>
                                        <h3>Override hanya jika biaya berbeda</h3>
                                        <p>Kosong berarti mengikuti default produk di atas.</p>
                                    </div>
                                    @if($overrideCount > 0)<span class="hub-pill hub-pill-warning">{{ $overrideCount }} override aktif</span>@endif
                                </div>

                                <div class="hpp-variant-table-wrap">
                                    <table class="hpp-variant-table">
                                        <thead><tr><th>Varian</th><th>Harga</th><th>Override HPP</th><th>Packaging</th><th>Nilai</th><th>Efektif</th></tr></thead>
                                        <tbody>
                                            @foreach($variants as $variant)
                                                @php
                                                    $variantPackType = $variant->packaging_type;
                                                    $variantHppDisplay = $variant->hpp_amount !== null ? number_format((float) $variant->hpp_amount, 0, ',', '.') : '';
                                                    $variantPackDisplay = $variant->packaging_value !== null
                                                        ? ($variantPackType === 'fixed' ? number_format((float) $variant->packaging_value, 0, ',', '.') : (float) $variant->packaging_value)
                                                        : '';
                                                @endphp
                                                <tr data-variant-row data-variant-id="{{ $variant->id }}" data-price="{{ (float) ($variant->price ?? $product->base_price ?? 0) }}">
                                                    <td data-label="Varian"><strong>{{ $variant->name ?: 'Varian ' . $variant->id }}</strong><small>{{ $variant->sku ?: 'Tanpa SKU' }}</small></td>
                                                    <td data-label="Harga">{{ ($variant->price ?? 0) > 0 ? hub_rp($variant->price) : 'Custom' }}</td>
                                                    <td data-label="Override HPP"><div class="hpp-input-group compact"><span>Rp</span><input type="text" inputmode="numeric" value="{{ $variantHppDisplay }}" placeholder="Ikut default" data-money="true" data-variant-field="hpp_amount"></div></td>
                                                    <td data-label="Packaging">
                                                        <select data-variant-field="packaging_type">
                                                            <option value="" @selected(!$variant->packaging_type)>Ikut default</option>
                                                            <option value="fixed" @selected($variant->packaging_type === 'fixed')>Nominal</option>
                                                            <option value="percent" @selected($variant->packaging_type === 'percent')>Persentase</option>
                                                        </select>
                                                    </td>
                                                    <td data-label="Nilai"><div class="hpp-input-group compact"><span data-variant-pack-unit>{{ $variantPackType === 'percent' ? '%' : 'Rp' }}</span><input type="text" inputmode="{{ $variantPackType === 'percent' ? 'decimal' : 'numeric' }}" value="{{ $variantPackDisplay }}" placeholder="Ikut default" data-money="{{ $variantPackType === 'fixed' ? 'true' : 'false' }}" data-variant-field="packaging_value"></div></td>
                                                    <td data-label="Efektif"><strong data-variant-effective-cost>-</strong><small data-variant-margin>-</small></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        @else
                            <div class="hpp-no-variant"><i class="fas fa-circle-info"></i><span>Produk ini tidak memiliki varian. Default produk langsung digunakan dalam laporan.</span></div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="hpp-empty-state">
                    <i class="fas fa-box-open"></i><h2>Produk tidak ditemukan</h2><p>Ubah filter atau sinkronkan katalog Shopee terbaru.</p>
                    <a href="{{ route('manage.index') }}" class="hub-btn hub-btn-primary">Ke Kelola Data</a>
                </div>
            @endforelse
        </div>
    </form>

@include('hub.partials.ceo.shell-close')
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/hub-hpp.js') }}?v=2"></script>
@endpush
