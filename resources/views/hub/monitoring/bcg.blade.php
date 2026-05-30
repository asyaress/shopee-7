@extends('layouts.hub')

@section('title', 'BCG Funnel — Monitoring')

@section('content')
@php
    $bcg = $bcg ?? [];
    $settings = $bcg['settings'] ?? [];
    $counts = $bcg['counts'] ?? [];
    $q = request()->query();
    $dataSource = $bcg['data_source'] ?? null;
    $sourceCounts = $bcg['source_counts'] ?? [];
    $blocks = [
        'star' => ['key' => 'star', 'label' => 'Star', 'icon' => 'fa-star', 'class' => 'bcg-star'],
        'cash_cow' => ['key' => 'cash_cow', 'label' => 'Cash Cow', 'icon' => 'fa-coins', 'class' => 'bcg-cow'],
        'question_mark' => ['key' => 'question_mark', 'label' => 'Question Mark', 'icon' => 'fa-question', 'class' => 'bcg-qm'],
        'dog' => ['key' => 'dog', 'label' => 'Dog', 'icon' => 'fa-paw', 'class' => 'bcg-dog'],
    ];
@endphp
<div class="report-shell">
    <div class="report-hero">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
            <div>
                <h1><i class="fas fa-chart-scatter me-2"></i>BCG Funnel</h1>
                <div class="report-hero-meta">
                    <span>Trafik & konversi · {{ $bcg['period']['label'] ?? '' }}</span>
                    <span>Batas konversi ≥ {{ $settings['conversion_threshold_pct'] ?? 2 }}%</span>
                    <span>Baseline trafik: {{ number_format($settings['traffic_baseline'] ?? 0) }}</span>
                </div>
            </div>
            @if($dataSource)
            <span class="bcg-source-badge bcg-source-badge--{{ $dataSource }}">
                <i class="fas {{ $dataSource === 'import' ? 'fa-file-excel' : ($dataSource === 'mixed' ? 'fa-layer-group' : 'fa-robot') }} me-1"></i>
                {{ $bcg['data_source_label'] ?? '' }}
            </span>
            @endif
        </div>
        @if(!empty($bcg['last_auto_sync']))
        <p class="small text-muted mb-0 mt-2">
            Sync otomatis terakhir: {{ \Carbon\Carbon::parse($bcg['last_auto_sync'])->format('d M Y H:i') }}
            @if(($sourceCounts['import'] ?? 0) > 0)
            · {{ $sourceCounts['import'] }} SKU dari import Seller Center
            @endif
        </p>
        @endif
    </div>

    @include('hub.partials.monitoring-nav')

    @if(session('success'))
    <div class="hub-alert hub-alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="hub-alert hub-alert-danger mb-3">{{ session('error') }}</div>
    @endif

    @if(empty($bcg['has_data']))
    <div class="hub-alert hub-alert-info mb-3">
        <i class="fas fa-sync me-2"></i>
        Belum ada data BCG. Klik <strong>Sync otomatis</strong> (butuh toko Shopee terhubung + order tersync), atau upload Excel Performa Produk dari Seller Center.
        @if($bcg['performance_url'] ?? null)
        <a href="{{ $bcg['performance_url'] }}" target="_blank" rel="noopener" class="ms-1">Buka Seller Center</a>
        @endif
    </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="hub-card h-100">
                <div class="hub-card-header">
                    <h2 class="report-section-title mb-0">Sync otomatis</h2>
                </div>
                <div class="hub-card-body">
                    <p class="small text-muted">
                        Mengambil <strong>views</strong> (30 hari) dari Shopee API + <strong>qty terjual</strong> dari order lokal.
                        Angka bisa berbeda dari Seller Center — gunakan import untuk data resmi.
                    </p>
                    <form method="POST" action="{{ route('monitoring.bcg.sync') }}">
                        @csrf
                        <button type="submit" class="hub-btn hub-btn-primary">
                            <i class="fas fa-sync me-1"></i> Sync otomatis sekarang
                        </button>
                    </form>
                    <p class="small text-muted mb-0 mt-2">
                        Juga berjalan saat <em>Sync All</em> di Kelola Data / cron <code>shopee:sync-all</code>.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="hub-card h-100">
                <div class="hub-card-header">
                    <h2 class="report-section-title mb-0">Import Seller Center <span class="bcg-source-badge bcg-source-badge--import bcg-source-badge--sm">Akurat</span></h2>
                </div>
                <div class="hub-card-body">
                    <form method="POST" action="{{ route('monitoring.bcg.import') }}" enctype="multipart/form-data" class="hub-filter-bar">
                        @csrf
                        <div class="filter-item">
                            <label class="hub-form-label">File Excel (.xlsx)</label>
                            <input type="file" name="file" class="hub-form-control" accept=".xlsx,.xls,.csv" required>
                        </div>
                        <div class="filter-item">
                            <label class="hub-form-label">Periode mulai</label>
                            <input type="date" name="period_start" class="hub-form-control" value="{{ $bcg['period']['start'] ?? '' }}">
                        </div>
                        <div class="filter-item">
                            <label class="hub-form-label">Periode akhir</label>
                            <input type="date" name="period_end" class="hub-form-control" value="{{ $bcg['period']['end'] ?? '' }}">
                        </div>
                        <div class="filter-item" style="align-self:flex-end">
                            <button type="submit" class="hub-btn hub-btn-outline">
                                <i class="fas fa-upload me-1"></i> Import (override)
                            </button>
                        </div>
                    </form>
                    <p class="small text-muted mb-0 mt-2">
                        Seller Center → Performa Toko → Produk. Import <strong>menimpa</strong> sync otomatis untuk SKU & periode yang sama.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="bcg-quadrant-grid mb-3">
        @foreach($blocks as $b)
        @php $items = $bcg['quadrants'][$b['key']] ?? []; @endphp
        <div class="bcg-quadrant {{ $b['class'] }}">
            <div class="bcg-quadrant-head">
                <h3><i class="fas {{ $b['icon'] }} me-1"></i>{{ $b['label'] }}</h3>
                <span class="badge">{{ $counts[$b['key']] ?? count($items) }}</span>
            </div>
            <div class="bcg-table-wrap">
                <table class="report-table bcg-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="num">Trafik</th>
                            <th class="num">Conv%</th>
                            <th class="num">Terjual</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(array_slice($items, 0, 15) as $p)
                        <tr>
                            <td>
                                @if($p['product_id'])
                                <a href="{{ route('monitoring.product', ['product' => $p['product_id']] + $q) }}">{{ \Illuminate\Support\Str::limit($p['name'], 32) }}</a>
                                @else
                                {{ \Illuminate\Support\Str::limit($p['name'], 32) }}
                                @endif
                                <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                    <span class="bcg-source-badge bcg-source-badge--{{ $p['source'] ?? 'auto' }} bcg-source-badge--xs">
                                        {{ $p['source_label'] ?? 'Auto' }}
                                    </span>
                                    <span class="small text-muted">{{ $p['ads_action'] ?? '' }}</span>
                                </div>
                            </td>
                            <td class="num">{{ number_format($p['visitors'] ?? 0) }}</td>
                            <td class="num">{{ $p['conversion_rate'] ?? 0 }}%</td>
                            <td class="num">{{ $p['units_sold'] ?? 0 }}</td>
                            <td class="bcg-links">
                                @if($p['links']['product'] ?? null)
                                <a href="{{ $p['links']['product'] }}" target="_blank" rel="noopener" title="Halaman produk"><i class="fas fa-external-link-alt"></i></a>
                                @endif
                                @if($p['links']['ads'] ?? null)
                                <a href="{{ $p['links']['ads'] }}" target="_blank" rel="noopener" title="Iklan Shopee" class="text-danger"><i class="fas fa-bullhorn"></i></a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-muted">—</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
    </div>

    @if(!empty($bcg['has_data']))
    <div class="hub-card">
        <div class="hub-card-header"><h2 class="report-section-title">Target penjualan per SKU ({{ now()->format('Y-m') }})</h2></div>
        <div class="hub-card-body">
            <form method="POST" action="{{ route('monitoring.bcg.targets') }}">
                @csrf
                <input type="hidden" name="year_month" value="{{ now()->format('Y-m') }}">
                <div class="hub-table-wrap">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="num">Terjual</th>
                                <th class="num">Target unit</th>
                                <th class="num">Target omzet (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $starItems = array_merge($bcg['quadrants']['star'] ?? [], $bcg['quadrants']['cash_cow'] ?? []); @endphp
                            @foreach(array_slice($starItems, 0, 20) as $i => $p)
                            @if($p['product_id'])
                            <tr>
                                <td>{{ \Illuminate\Support\Str::limit($p['name'], 40) }}</td>
                                <td class="num">{{ $p['units_sold'] ?? 0 }}</td>
                                <td class="num">
                                    <input type="hidden" name="targets[{{ $i }}][product_id]" value="{{ $p['product_id'] }}">
                                    <input type="number" name="targets[{{ $i }}][target_units]" class="hub-form-control hub-form-control-sm" value="{{ $p['target_units'] ?? '' }}" min="0" placeholder="0">
                                </td>
                                <td class="num">
                                    <input type="number" name="targets[{{ $i }}][target_gross]" class="hub-form-control hub-form-control-sm" value="{{ $p['target_gross'] ?? '' }}" min="0" placeholder="0">
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="hub-btn hub-btn-primary mt-3">Simpan target SKU</button>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
