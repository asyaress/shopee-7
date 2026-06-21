@extends('layouts.hub')
@section('title', 'Kalkulator Harga')
@push('styles')<link href="{{ asset('css/hub-monitoring.css') }}?v=6" rel="stylesheet">@endpush
@section('content')
@php
    $d = $defaults ?? [];
    $r = $results['results'] ?? [];
    $cat = $results['margin_category'] ?? [];
    $q = request()->query();
@endphp
<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-calculator me-2"></i>Kalkulator Harga</h1>
        <p class="small mb-0 opacity-90">Simulasi margin, target ROAS, dan Set ROAS Shopee — mirror Excel ROAS HLP dengan data aktual toko</p>
    </div>

    @include('hub.partials.hub-zone-nav')

    <form method="GET" action="{{ route('ceo.kalkulator') }}" class="hub-card mb-3">
        <div class="hub-card-header"><h2 class="report-section-title mb-0">Input & produk</h2></div>
        <div class="hub-card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small">Pilih produk (opsional — auto-fill dari data aktual)</label>
                    <select name="product_id" class="hub-form-control" onchange="this.form.submit()">
                        <option value="">— Manual / tanpa produk —</option>
                        @foreach($products as $p)
                        <option value="{{ $p['id'] }}" @selected(($d['product_id'] ?? null) == $p['id'])>{{ $p['name'] }} @if($p['sku'])({{ $p['sku'] }})@endif</option>
                        @endforeach
                    </select>
                </div>
                @if($selectedProduct)
                <div class="col-md-6 d-flex align-items-end">
                    <a href="{{ route('monitoring.product-analysis.show', ['product' => $selectedProduct->id] + $q) }}" class="hub-btn hub-btn-outline">
                        <i class="fas fa-microscope"></i> Analisis produk ini
                    </a>
                </div>
                @endif
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small">HPP (Rp)</label>
                    <input type="number" name="hpp" class="hub-form-control" value="{{ request('hpp', $d['hpp'] ?? 0) }}" min="0" step="100">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Packaging (Rp)</label>
                    <input type="number" name="packaging" class="hub-form-control" value="{{ request('packaging', $d['packaging'] ?? 0) }}" min="0" step="100">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Harga jual (Rp)</label>
                    <input type="number" name="sell_price" class="hub-form-control" value="{{ request('sell_price', $d['sell_price'] ?? 0) }}" min="0" step="100">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Admin & fee (%)</label>
                    <input type="number" name="admin_pct" class="hub-form-control" value="{{ request('admin_pct', $d['admin_pct'] ?? 18) }}" min="0" max="50" step="0.1">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Operasional (% omzet)</label>
                    <input type="number" name="operational_pct" class="hub-form-control" value="{{ request('operational_pct', $d['operational_pct'] ?? 8) }}" min="0" max="50" step="0.1">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Target laba bersih (%)</label>
                    <input type="number" name="target_net_margin_pct" class="hub-form-control" value="{{ request('target_net_margin_pct', $d['target_net_margin_pct'] ?? 15) }}" min="0" max="40" step="0.5">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Iklan per unit (Rp)</label>
                    <input type="number" name="ads_per_unit" class="hub-form-control" value="{{ request('ads_per_unit', $d['ads_per_unit'] ?? 0) }}" min="0" step="100">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Target omzet bulanan (Rp)</label>
                    <input type="number" name="target_gross_monthly" class="hub-form-control" value="{{ request('target_gross_monthly', $d['target_gross_monthly'] ?? 0) }}" min="0" step="100000">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Operasional bulanan (Rp)</label>
                    <input type="number" name="operational_monthly" class="hub-form-control" value="{{ request('operational_monthly', $d['operational_monthly'] ?? 0) }}" min="0" step="100000">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Diskon Set ROAS Shopee (%)</label>
                    <input type="number" name="shopee_roas_discount" class="hub-form-control" value="{{ request('shopee_roas_discount', $d['shopee_roas_discount'] ?? 70) }}" min="50" max="90" step="1">
                </div>
            </div>
            <button type="submit" class="hub-btn hub-btn-primary mt-3"><i class="fas fa-sync"></i> Hitung ulang</button>
        </div>
    </form>

    <div class="report-kpi-hero mb-3">
        <div class="report-kpi-card">
            <div class="label">Margin profit</div>
            <div class="value">{{ $r['margin_profit_pct'] ?? '—' }}%</div>
            <div class="sub"><span class="hub-pill hub-pill-{{ $cat['class'] ?? 'info' }}">{{ $cat['label'] ?? '' }}</span></div>
        </div>
        <div class="report-kpi-card positive">
            <div class="label">Target ROAS</div>
            <div class="value">{{ isset($r['target_roas']) ? number_format($r['target_roas'], 2).'x' : '—' }}</div>
            <div class="sub">ACOS max {{ $r['target_acos_pct'] ?? '—' }}%</div>
        </div>
        <div class="report-kpi-card highlight">
            <div class="label">Set ROAS Shopee</div>
            <div class="value">{{ isset($r['set_roas_shopee']) ? number_format($r['set_roas_shopee'], 2).'x' : '—' }}</div>
            <div class="sub">Input di dashboard iklan</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Laba bersih / unit</div>
            <div class="value">{{ hub_rp($r['net_profit_unit'] ?? 0) }}</div>
            <div class="sub">Impas kotor {{ hub_rp($r['breakeven_gross_unit'] ?? 0) }}</div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="hub-card h-100">
                <div class="hub-card-header"><h2 class="report-section-title">Hasil per unit</h2></div>
                <div class="hub-card-body">
                    <table class="report-table report-table-compact">
                        <tbody>
                            <tr><td>COGS (HPP + pack)</td><td class="num">{{ hub_rp($r['cogs_unit'] ?? 0) }}</td></tr>
                            <tr><td>Net setelah admin</td><td class="num">{{ $r['net_after_admin_pct'] ?? '—' }}%</td></tr>
                            <tr><td>Net setelah operasional</td><td class="num">{{ $r['net_after_ops_pct'] ?? '—' }}%</td></tr>
                            <tr><td>Harga impas (kotor)</td><td class="num">{{ hub_rp($r['breakeven_gross_unit'] ?? 0) }}</td></tr>
                            <tr><td><strong>Laba bersih / unit</strong></td><td class="num"><strong>{{ hub_rp($r['net_profit_unit'] ?? 0) }}</strong></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="hub-card h-100">
                <div class="hub-card-header"><h2 class="report-section-title">Estimasi bulanan</h2></div>
                <div class="hub-card-body">
                    <table class="report-table report-table-compact">
                        <tbody>
                            <tr><td>Estimasi laba bulan</td><td class="num">{{ hub_rp($r['est_profit_monthly'] ?? 0, true) }}</td></tr>
                            <tr><td>Estimasi budget iklan</td><td class="num">{{ hub_rp($r['est_ads_monthly'] ?? 0, true) }}</td></tr>
                            <tr><td>Estimasi iklan / hari</td><td class="num">{{ hub_rp($r['est_ads_daily'] ?? 0, true) }}</td></tr>
                        </tbody>
                    </table>
                    <p class="small text-muted mt-2 mb-0">Fee admin diambil dari take rate rata-rata toko bulan ini jika produk dipilih.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
