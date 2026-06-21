@extends('layouts.hub')

@section('title', 'Kelola Data')
@section('content')
@php
    $st = $stats ?? [];
    $hppPct = $st['hpp_complete_pct'] ?? 0;
@endphp

@include('hub.partials.ceo.shell-open')

    <div class="report-kpi-hero mb-3" style="grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));" data-ceo="main-kpi">
        <div class="report-kpi-card">
            <div class="label">Total Produk</div>
            <div class="value">{{ hub_num($st['products_total'] ?? 0) }}</div>
            <div class="sub">Master katalog</div>
        </div>
        <div class="report-kpi-card {{ $hppPct >= 80 ? 'positive' : 'warn' }}">
            <div class="label">HPP Terisi</div>
            <div class="value">{{ $hppPct }}%</div>
            <div class="sub">{{ $st['with_hpp'] ?? 0 }} / {{ $st['products_total'] ?? 0 }} produk</div>
        </div>
        <div class="report-kpi-card {{ ($st['missing_hpp'] ?? 0) > 0 ? 'warn' : 'positive' }}">
            <div class="label">Tanpa HPP</div>
            <div class="value">{{ $st['missing_hpp'] ?? 0 }}</div>
            <div class="sub">Perlu dilengkapi</div>
        </div>
        <div class="report-kpi-card {{ ($st['unmapped_items'] ?? 0) > 0 ? 'negative' : 'positive' }}">
            <div class="label">Item Unmapped</div>
            <div class="value">{{ $st['unmapped_items'] ?? 0 }}</div>
            <div class="sub">Bulan berjalan</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Pesanan Shopee</div>
            <div class="value">{{ hub_num($st['shopee_orders'] ?? 0) }}</div>
            <div class="sub">dari {{ hub_num($st['orders_total'] ?? 0) }} total</div>
        </div>
    </div>

    @if(($st['missing_hpp'] ?? 0) > 0 || ($st['unmapped_items'] ?? 0) > 0)
    <div class="report-insights mb-3">
        @if(($st['missing_hpp'] ?? 0) > 0)
        <div class="report-insight warning">
            <div class="icon"><i class="fas fa-tags"></i></div>
            <div><strong>{{ $st['missing_hpp'] }} produk belum punya HPP</strong><p>Lengkapi di tabel bawah agar laporan laba di Monitoring akurat.</p></div>
        </div>
        @endif
        @if(($st['unmapped_items'] ?? 0) > 0)
        <div class="report-insight danger">
            <div class="icon"><i class="fas fa-link-slash"></i></div>
            <div><strong>{{ $st['unmapped_items'] }} item order belum terpetakan</strong><p>Sync produk Shopee atau periksa mapping SKU / item_id.</p></div>
        </div>
        @endif
    </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="hub-card h-100">
                <div class="hub-card-header">
                    <div>
                        <h2 class="report-section-title"><i class="fas fa-plug me-2"></i>Integrasi & Sinkronisasi Shopee</h2>
                        <p class="report-section-desc">Tarik order, finansial, produk, dan performa iklan</p>
                    </div>
                    <span class="hub-pill {{ $token ? 'hub-pill-success' : 'hub-pill-danger' }}">{{ $token ? 'Terhubung' : 'Offline' }}</span>
                </div>
                <div class="hub-card-body">
                    @if($token)
                    <div class="report-pl mb-3" style="font-size:0.85rem;">
                        <table class="w-100">
                            <tr><td class="text-muted py-1">Shop ID</td><td class="text-end fw-bold">{{ $token->shop_id }}</td></tr>
                            <tr><td class="text-muted py-1">Environment</td><td class="text-end"><span class="hub-pill hub-pill-muted">{{ strtoupper($env) }}</span></td></tr>
                            @if($mainToken?->expire_at)
                            <tr><td class="text-muted py-1">Main App expire</td><td class="text-end">{{ $mainToken->expire_at->format('d M Y H:i') }}</td></tr>
                            @endif
                            @if(($adsToken ?? null)?->expire_at)
                            <tr><td class="text-muted py-1">Ads App expire</td><td class="text-end">{{ $adsToken->expire_at->format('d M Y H:i') }}</td></tr>
                            @endif
                            @if(($amsToken ?? null)?->expire_at)
                            <tr><td class="text-muted py-1">AMS App expire</td><td class="text-end">{{ $amsToken->expire_at->format('d M Y H:i') }}</td></tr>
                            @endif
                        </table>
                    </div>
                    <div class="d-flex gap-2 flex-wrap mb-3">
                        <a href="{{ route('shopee.connect') }}" class="hub-btn hub-btn-outline hub-btn-sm"><i class="fas fa-link"></i> Reconnect Main App</a>
                        @if($adsConfigured ?? false)
                        <a href="{{ route('shopee.connect.app', ['appType' => 'ads']) }}" class="hub-btn hub-btn-outline hub-btn-sm"><i class="fas fa-bullhorn"></i> {{ ($adsToken ?? null) ? 'Reconnect Ads App' : 'Connect Ads App' }}</a>
                        @endif
                        @if($amsConfigured ?? false)
                        <a href="{{ route('shopee.connect.app', ['appType' => 'ams']) }}" class="hub-btn hub-btn-outline hub-btn-sm"><i class="fas fa-chart-line"></i> {{ ($amsToken ?? null) ? 'Reconnect AMS App' : 'Connect AMS App' }}</a>
                        @endif
                    </div>
                    <div class="sync-action-grid">
                        <form method="POST" action="{{ route('manage.sync.orders') }}" class="sync-action-card">
                            @csrf<input type="hidden" name="days" value="7">
                            <i class="fas fa-shopping-cart"></i>
                            <strong>Sync Order</strong>
                            <span>7 hari terakhir</span>
                            <button type="submit" class="hub-btn hub-btn-outline hub-btn-sm w-100 mt-2">Jalankan</button>
                        </form>
                        <form method="POST" action="{{ route('manage.sync.products') }}" class="sync-action-card">
                            @csrf
                            <i class="fas fa-box"></i>
                            <strong>Sync Produk</strong>
                            <span>Katalog + varian</span>
                            <button type="submit" class="hub-btn hub-btn-outline hub-btn-sm w-100 mt-2">Jalankan</button>
                        </form>
                        <form method="POST" action="{{ route('manage.sync.ads') }}" class="sync-action-card">
                            @csrf<input type="hidden" name="ads_days" value="30">
                            <i class="fas fa-bullhorn"></i>
                            <strong>Sync Iklan</strong>
                            <span>30 hari · pakai Main/Ads/AMS sesuai endpoint</span>
                            <button type="submit" class="hub-btn hub-btn-outline hub-btn-sm w-100 mt-2">Jalankan</button>
                        </form>
                        <form method="POST" action="{{ route('manage.sync.all') }}" class="sync-action-card highlight">
                            @csrf
                            <input type="hidden" name="days" value="7"><input type="hidden" name="ads_days" value="30">
                            <i class="fas fa-rotate"></i>
                            <strong>Sync Semua</strong>
                            <span>Order + produk + iklan</span>
                            <button type="submit" class="hub-btn hub-btn-primary hub-btn-sm w-100 mt-2">Jalankan</button>
                        </form>
                    </div>
                    <p class="small text-muted mb-0 mt-2"><i class="fas fa-info-circle me-1"></i> Order/produk tetap pakai Main App. Sync iklan akan memakai Ads App untuk endpoint marketing dan AMS App untuk endpoint affiliate bila credential/token keduanya tersedia. Penyimpanan ads saat ini masih level item produk, bukan level variasi/model.</p>
                    @else
                    <p class="mb-3">Hubungkan toko Shopee untuk mulai menarik data otomatis.</p>
                    <a href="{{ route('shopee.connect') }}" class="hub-btn hub-btn-primary"><i class="fas fa-link"></i> Connect Main App</a>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="hub-card h-100">
                <div class="hub-card-header">
                    <h2 class="report-section-title"><i class="fas fa-building me-2"></i>Biaya Operasional</h2>
                </div>
                <div class="hub-card-body">
                    <form method="POST" action="{{ route('manage.operational.save') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="hub-form-label">Periode bulan</label>
                            <input type="month" name="year_month" class="hub-form-control" value="{{ $yearMonth }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="hub-form-label">Nominal (Rp)</label>
                            <input type="number" name="operational_amount" class="hub-form-control" min="0" step="1000"
                                value="{{ old('operational_amount', $operational->operational_amount ?? '') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="hub-form-label">Catatan</label>
                            <input type="text" name="notes" class="hub-form-control" placeholder="Mis. gaji, sewa, listrik"
                                value="{{ old('notes', $operational->notes ?? '') }}">
                        </div>
                        @if($operational)
                        <div class="alert alert-info py-2 small mb-3">
                            Tersimpan: <strong>{{ hub_rp($operational->operational_amount) }}</strong>
                            @if($operational->notes) - {{ $operational->notes }} @endif
                        </div>
                        @endif
                        <button type="submit" class="hub-btn hub-btn-primary w-100"><i class="fas fa-save"></i> Simpan Operasional</button>
                    </form>
                    <p class="small text-muted mt-2 mb-0">Dialokasikan proporsional ke produk di laporan Monitoring.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="hub-card">
        <div class="hub-card-header flex-wrap">
            <div>
                <h2 class="report-section-title"><i class="fas fa-tags me-2"></i>Master HPP & Packaging</h2>
                <p class="report-section-desc">{{ $products->count() }} SKU - input manual per produk</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <input type="search" id="hppSearch" class="hub-form-control report-search" placeholder="Cari produk...">
                <a href="{{ route('hpp.index') }}" class="hub-btn hub-btn-sm hub-btn-primary">Input HPP</a>
                <a href="{{ route('products.costs') }}" class="hub-btn hub-btn-sm hub-btn-outline">Varian</a>
            </div>
        </div>
        <div class="hub-card-body p-0">
            <form method="POST" action="{{ route('manage.costs.save') }}">
                @csrf
                <div class="report-table-scroll" style="max-height:520px;">
                    <table class="report-table" id="hppTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Produk</th>
                                <th class="num">HPP (Rp)</th>
                                <th>Packaging</th>
                                <th class="num">Nilai</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $i => $product)
                            @php
                                $hasHpp = $product->hpp_amount !== null;
                                $hasPack = $product->packaging_value !== null;
                            @endphp
                            <input type="hidden" name="products[{{ $i }}][id]" value="{{ $product->id }}">
                            <tr data-search="{{ strtolower($product->name . ' ' . ($product->external_item_id ?? '')) }}">
                                <td class="text-muted">{{ $i + 1 }}</td>
                                <td class="product-cell">
                                    <span class="name" title="{{ $product->name }}">{{ Str::limit($product->name, 45) }}</span>
                                    @if($product->external_item_id)<span class="sku">ID {{ $product->external_item_id }}</span>@endif
                                </td>
                                <td class="num" style="min-width:110px;">
                                    <input type="number" name="products[{{ $i }}][hpp_amount]" class="hub-form-control hub-form-control-sm"
                                        min="0" step="100" value="{{ $product->hpp_amount }}" placeholder="0">
                                </td>
                                <td style="min-width:100px;">
                                    <select name="products[{{ $i }}][packaging_type]" class="hub-form-select hub-form-control-sm">
                                        <option value="fixed" @selected(($product->packaging_type ?? 'fixed') === 'fixed')>Fixed (Rp)</option>
                                        <option value="percent" @selected($product->packaging_type === 'percent')>% Harga</option>
                                    </select>
                                </td>
                                <td class="num" style="min-width:90px;">
                                    <input type="number" name="products[{{ $i }}][packaging_value]" class="hub-form-control hub-form-control-sm"
                                        min="0" step="0.01" value="{{ $product->packaging_value }}">
                                </td>
                                <td>
                                    @if($hasHpp || $hasPack)
                                        <span class="hub-pill hub-pill-success">OK</span>
                                    @else
                                        <span class="hub-pill hub-pill-warning">Kosong</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($products->isNotEmpty())
                <div class="p-3 border-top bg-light">
                    <button type="submit" class="hub-btn hub-btn-primary"><i class="fas fa-save"></i> Simpan Semua HPP & Packaging</button>
                </div>
                @else
                <p class="text-center text-muted py-5">Belum ada produk. Jalankan <strong>Sync Produk</strong> terlebih dahulu.</p>
                @endif
            </form>
        </div>
    </div>
@include('hub.partials.ceo.shell-close')
@endsection

@push('scripts')
<script>
(function () {
    const bindSearch = (inputId, tableId) => {
        const input = document.getElementById(inputId);
        const rows = document.querySelectorAll(`#${tableId} tbody tr[data-search]`);
        if (!input || !rows.length) return;
        input.addEventListener('input', () => {
            const q = input.value.toLowerCase().trim();
            rows.forEach(tr => { tr.style.display = !q || tr.dataset.search.includes(q) ? '' : 'none'; });
        });
    };
    bindSearch('hppSearch', 'hppTable');
})();
</script>
@endpush
