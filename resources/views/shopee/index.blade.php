@extends('layouts.hub')

@section('title', 'Integrasi Shopee - Shopee Profit Hub')

@section('content')
<div class="report-shell">
    @include('hub.partials.page-hero', [
        'icon' => 'fa-plug',
        'title' => 'Integrasi Shopee Open Platform',
        'subtitle' => 'Koneksi API v2 - order, produk, finansial, dan sinkronisasi data',
        'meta' => [
            ['icon' => 'fa-server', 'text' => 'ENV: ' . strtoupper($env)],
            ['icon' => 'fa-circle', 'text' => $mainToken ? 'Main App: Terhubung' : 'Main App: Belum connect'],
            ['icon' => 'fa-bullhorn', 'text' => ($adsConfigured ?? false) ? (($adsToken ?? null) ? 'AMS App: Terhubung' : 'AMS App: Belum connect') : 'AMS App: Opsional'],
        ],
        'actions' => ($mainToken ? '' : '<a href="' . route('shopee.connect') . '" class="hub-btn hub-btn-primary" style="background:#fff;color:var(--maroon-800)!important;"><i class="fas fa-link"></i> Connect Main App</a>')
            . (($adsConfigured ?? false) ? '<a href="' . route('shopee.connect.app', ['appType' => 'ads']) . '" class="hub-btn hub-btn-primary" style="background:#fff;color:var(--maroon-800)!important;"><i class="fas fa-bullhorn"></i> ' . (($adsToken ?? null) ? 'Reconnect AMS App' : 'Connect AMS App') . '</a>' : '')
            . '<a href="' . route('manage.index') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-database"></i> Kelola Data</a>',
    ])

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="hub-card h-100">
                <div class="hub-card-header"><h2 class="report-section-title">Status Koneksi</h2></div>
                <div class="hub-card-body">
                    @if($mainToken)
                    <table class="report-pl w-100" style="font-size:0.875rem;">
                        <tr><td class="text-muted py-2">Shop ID</td><td class="text-end fw-bold">{{ $mainToken->shop_id }}</td></tr>
                        <tr><td class="text-muted py-2">Main Partner ID</td><td class="text-end">{{ $mainToken->partner_id }}</td></tr>
                        <tr><td class="text-muted py-2">Main token diperoleh</td><td class="text-end">{{ optional($mainToken->obtained_at)->format('d M Y H:i') ?? '-' }}</td></tr>
                        <tr><td class="text-muted py-2">Main kadaluarsa</td><td class="text-end">{{ optional($mainToken->expire_at)->format('d M Y H:i') ?? '-' }}</td></tr>
                        @if($adsConfigured ?? false)
                        <tr><td class="text-muted py-2">AMS status</td><td class="text-end">{{ ($adsToken ?? null) ? 'Connected' : 'Belum connect' }}</td></tr>
                        @if(($adsToken ?? null))
                        <tr><td class="text-muted py-2">AMS Partner ID</td><td class="text-end">{{ $adsToken->partner_id }}</td></tr>
                        <tr><td class="text-muted py-2">AMS kadaluarsa</td><td class="text-end">{{ optional($adsToken->expire_at)->format('d M Y H:i') ?? '-' }}</td></tr>
                        @endif
                        @endif
                    </table>
                    <p class="small text-muted mt-2 mb-0">Disimpan di tabel <code>shopee_tokens</code>.</p>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <form method="POST" action="{{ route('shopee.disconnect') }}" onsubmit="return confirm('Putus koneksi Main App? Anda bisa Connect ulang setelah ini.');">
                            @csrf
                            <input type="hidden" name="shop_id" value="{{ $mainToken->shop_id }}">
                            <button type="submit" class="hub-btn hub-btn-outline hub-btn-sm text-danger"><i class="fas fa-unlink"></i> Putus Main App</button>
                        </form>
                        @if(($adsToken ?? null))
                        <form method="POST" action="{{ route('shopee.disconnect.app', ['appType' => 'ads']) }}" onsubmit="return confirm('Putus koneksi AMS App?');">
                            @csrf
                            <input type="hidden" name="shop_id" value="{{ $adsToken->shop_id }}">
                            <button type="submit" class="hub-btn hub-btn-outline hub-btn-sm text-danger"><i class="fas fa-unlink"></i> Putus AMS App</button>
                        </form>
                        @endif
                    </div>

                    <div class="report-insight info mt-3 mb-0">
                        <div class="icon"><i class="fas fa-bullhorn"></i></div>
                        <div>
                            <strong>Affiliate / AMS App</strong>
                            @if($adsConfigured ?? false)
                                <p class="mb-2 small">Hubungkan app AMS untuk menarik data iklan Shopee. Ini terpisah dari Main App order/produk.</p>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('shopee.connect.app', ['appType' => 'ads']) }}" class="hub-btn hub-btn-outline hub-btn-sm">
                                        <i class="fas fa-bullhorn"></i> {{ ($adsToken ?? null) ? 'Reconnect AMS App' : 'Connect AMS App' }}
                                    </a>
                                    @if(($adsToken ?? null))
                                        <span class="hub-pill hub-pill-success align-self-center">AMS sudah terhubung</span>
                                    @else
                                        <span class="hub-pill hub-pill-warning align-self-center">AMS belum terhubung</span>
                                    @endif
                                </div>
                            @else
                                <p class="mb-0 small">Credential AMS belum diisi di <code>.env</code>. Isi <code>SHOPEE_ADS_PARTNER_ID</code>, <code>SHOPEE_ADS_PARTNER_KEY</code>, dan <code>SHOPEE_ADS_REDIRECT_URL</code> dulu supaya tombol connect aktif.</p>
                            @endif
                        </div>
                    </div>
                    @else
                    <div class="report-insight warning mb-0">
                        <div class="icon"><i class="fas fa-unlink"></i></div>
                        <div><strong>Belum terotorisasi</strong><p>Klik Connect untuk menghubungkan toko Shopee Anda.</p></div>
                    </div>

                    <div class="report-insight info mt-3 mb-0">
                        <div class="icon"><i class="fas fa-bullhorn"></i></div>
                        <div>
                            <strong>Affiliate / AMS App</strong>
                            @if($adsConfigured ?? false)
                                <p class="mb-2 small">Credential AMS sudah tersedia. Kamu bisa langsung hubungkan app AMS dari halaman ini.</p>
                                <a href="{{ route('shopee.connect.app', ['appType' => 'ads']) }}" class="hub-btn hub-btn-outline hub-btn-sm">
                                    <i class="fas fa-bullhorn"></i> Connect AMS App
                                </a>
                            @else
                                <p class="mb-0 small">Credential AMS belum diisi di <code>.env</code>. Isi <code>SHOPEE_ADS_*</code> dulu supaya tombol connect AMS muncul aktif.</p>
                            @endif
                        </div>
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
                            <input type="number" name="days" value="7" min="1" max="90" class="hub-form-control hub-form-control-sm mt-1" {{ $mainToken ? '' : 'disabled' }} title="Rentang hari (API Shopee max 15 hari per request; otomatis di-chunk)">
                            <span class="text-muted" style="font-size:0.7rem;">hari (max 90, auto-chunk)</span>
                            <button class="hub-btn hub-btn-outline hub-btn-sm w-100 mt-2" {{ $mainToken ? '' : 'disabled' }}>Sync</button>
                        </form>
                        <form method="POST" action="{{ route('shopee.sync-products') }}" class="sync-action-card">
                            @csrf
                            <i class="fas fa-box"></i><strong>Produk</strong>
                            <input type="number" name="page_size" value="100" min="1" max="100" class="hub-form-control hub-form-control-sm mt-1" {{ $mainToken ? '' : 'disabled' }}>
                            <button class="hub-btn hub-btn-outline hub-btn-sm w-100 mt-2" {{ $mainToken ? '' : 'disabled' }}>Sync</button>
                        </form>
                        <form method="POST" action="{{ route('shopee.sync-ads') }}" class="sync-action-card">
                            @csrf
                            <input type="hidden" name="ads_days" value="30">
                            <i class="fas fa-bullhorn"></i><strong>Ads</strong>
                            <span>30 hari terakhir</span>
                            <button class="hub-btn hub-btn-outline hub-btn-sm w-100 mt-2" {{ ($mainToken ?? null) ? '' : 'disabled' }}>Sync Ads</button>
                        </form>
                        <form method="POST" action="{{ route('shopee.sync-all') }}" class="sync-action-card highlight">
                            @csrf
                            <i class="fas fa-rocket"></i><strong>Semua</strong>
                            <span>Order + produk + ads</span>
                            <button class="hub-btn hub-btn-primary hub-btn-sm w-100 mt-2" {{ $mainToken ? '' : 'disabled' }}>Sync ALL</button>
                        </form>
                    </div>
                    @if($adsConfigured ?? false)
                    <div class="report-insight info mt-2 mb-0">
                        <div class="icon"><i class="fas fa-bullhorn"></i></div>
                        <div>
                            <strong>AMS App terpisah</strong>
                            <p class="mb-0 small">Main App untuk order/produk. App kedua untuk data iklan butuh kategori Shopee <em>Affiliate Marketing Solution Management</em> + credential <code>SHOPEE_ADS_*</code> di .env. Klik <strong>Connect AMS App</strong> sekali. Data yang tersimpan saat ini masih level <code>item_id</code> produk, belum dipecah per <code>model_id</code> variasi.</p>
                        </div>
                    </div>
                    @endif
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
                        <li><code>order_sn</code> -> <code>orders.order_number</code></li>
                        <li><code>buyer_username</code> -> nama customer</li>
                        <li><code>item_list</code> -> <code>order_items</code></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-bold text-maroon">Finansial & Produk</h6>
                    <ul class="small text-muted mb-0">
                        <li><code>get_escrow_detail</code> -> fee & net penghasilan</li>
                        <li><code>item_id</code> -> <code>products.external_item_id</code></li>
                        <li>Ads API -> <code>shopee_product_ads_daily</code></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
