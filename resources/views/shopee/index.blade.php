@extends('layouts.hub')

@section('title', 'Integrasi Shopee — Shopee Profit Hub')

@section('content')
<div class="report-shell">
    @include('hub.partials.page-hero', [
        'icon' => 'fa-plug',
        'title' => 'Integrasi Shopee Open Platform',
        'subtitle' => 'Koneksi API v2 — order, produk, finansial, dan sinkronisasi data',
        'meta' => [
            ['icon' => 'fa-server', 'text' => 'ENV: ' . strtoupper($env)],
            ['icon' => 'fa-circle', 'text' => $token ? 'Status: Terhubung' : 'Status: Belum connect'],
        ],
        'actions' => ($token ? '' : '<a href="' . route('shopee.connect') . '" class="hub-btn hub-btn-primary" style="background:#fff;color:var(--maroon-800)!important;"><i class="fas fa-link"></i> Connect</a>')
            . '<a href="' . route('manage.index') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-database"></i> Kelola Data</a>',
    ])

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="hub-card h-100">
                <div class="hub-card-header"><h2 class="report-section-title">Status Koneksi</h2></div>
                <div class="hub-card-body">
                    @if($token)
                    <table class="report-pl w-100" style="font-size:0.875rem;">
                        <tr><td class="text-muted py-2">Shop ID</td><td class="text-end fw-bold">{{ $token->shop_id }}</td></tr>
                        <tr><td class="text-muted py-2">Partner ID</td><td class="text-end">{{ $token->partner_id }}</td></tr>
                        <tr><td class="text-muted py-2">Token diperoleh</td><td class="text-end">{{ optional($token->obtained_at)->format('d M Y H:i') ?? '—' }}</td></tr>
                        <tr><td class="text-muted py-2">Kadaluarsa</td><td class="text-end">{{ optional($token->expire_at)->format('d M Y H:i') ?? '—' }}</td></tr>
                    </table>
                    <p class="small text-muted mt-2 mb-0">Disimpan di tabel <code>shopee_tokens</code>.</p>
                    @else
                    <div class="report-insight warning mb-0">
                        <div class="icon"><i class="fas fa-unlink"></i></div>
                        <div><strong>Belum terotorisasi</strong><p>Klik Connect untuk menghubungkan toko Shopee Anda.</p></div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="hub-card h-100">
                <div class="hub-card-header"><h2 class="report-section-title">Sinkronisasi Cepat</h2></div>
                <div class="hub-card-body">
                    <p class="text-muted small">Untuk kontrol lengkap, gunakan halaman <a href="{{ route('manage.index') }}">Kelola Data</a>.</p>
                    <div class="sync-action-grid">
                        <form method="POST" action="{{ route('shopee.sync') }}" class="sync-action-card">
                            @csrf
                            <i class="fas fa-shopping-cart"></i><strong>Order</strong>
                            <input type="number" name="days" value="7" min="1" max="90" class="hub-form-control hub-form-control-sm mt-1" {{ $token ? '' : 'disabled' }} title="Rentang hari (API Shopee max 15 hari per request; otomatis di-chunk)">
                            <span class="text-muted" style="font-size:0.7rem;">hari (max 90, auto-chunk)</span>
                            <button class="hub-btn hub-btn-outline hub-btn-sm w-100 mt-2" {{ $token ? '' : 'disabled' }}>Sync</button>
                        </form>
                        <form method="POST" action="{{ route('shopee.sync-products') }}" class="sync-action-card">
                            @csrf
                            <i class="fas fa-box"></i><strong>Produk</strong>
                            <input type="number" name="page_size" value="100" min="1" max="100" class="hub-form-control hub-form-control-sm mt-1" {{ $token ? '' : 'disabled' }}>
                            <button class="hub-btn hub-btn-outline hub-btn-sm w-100 mt-2" {{ $token ? '' : 'disabled' }}>Sync</button>
                        </form>
                        <form method="POST" action="{{ route('shopee.sync-all') }}" class="sync-action-card highlight">
                            @csrf
                            <i class="fas fa-rocket"></i><strong>Semua</strong>
                            <span>Order + produk</span>
                            <button class="hub-btn hub-btn-primary hub-btn-sm w-100 mt-2" {{ $token ? '' : 'disabled' }}>Sync ALL</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="hub-card mt-3">
        <div class="hub-card-header"><h2 class="report-section-title">Pemetaan Data ke Sistem</h2></div>
        <div class="hub-card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <h6 class="fw-bold text-maroon">Order</h6>
                    <ul class="small text-muted mb-0">
                        <li><code>order_sn</code> → <code>orders.order_number</code></li>
                        <li><code>buyer_username</code> → nama customer</li>
                        <li><code>item_list</code> → <code>order_items</code></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-bold text-maroon">Finansial & Produk</h6>
                    <ul class="small text-muted mb-0">
                        <li><code>get_escrow_detail</code> → fee & net penghasilan</li>
                        <li><code>item_id</code> → <code>products.external_item_id</code></li>
                        <li>Ads API → <code>shopee_product_ads_daily</code></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
