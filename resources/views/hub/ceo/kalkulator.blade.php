@extends('layouts.hub')
@section('title', 'Kalkulator Harga')
@section('content')
@php
    $d = $defaults ?? [];
    $r = $results['results'] ?? [];
    $cat = $results['margin_category'] ?? [];
    $q = request()->query();
    $setRoas = $r['set_roas_shopee'] ?? null;
    $marginPct = $r['margin_profit_pct'] ?? null;
    $ceoActionSkip = true;
    $sev = match($cat['class'] ?? 'info') {
        'danger' => 'danger', 'warning' => 'warning', 'success' => 'success', default => 'info',
    };
    $ceoAction = [
        'severity' => $sev,
        'title' => 'Margin '.($cat['label'] ?? '—'),
        'headline' => match($cat['class'] ?? 'info') {
            'danger' => 'Margin terlalu tipis — naikkan harga atau turunkan HPP dulu.',
            'warning' => 'Margin kecil — hati-hati tambah budget iklan.',
            'success' => 'Margin sehat — angka Set ROAS di bawah aman dipakai.',
            default => 'Sesuaikan harga & HPP, lalu cek Set ROAS.',
        },
        'steps' => array_values(array_filter([
            $setRoas ? 'Set ROAS Shopee: '.number_format($setRoas, 1).'x' : null,
            'Laba per unit: '.hub_rp($r['net_profit_unit'] ?? 0),
            ($r['net_profit_unit'] ?? 0) < 0 ? 'Harga impas: '.hub_rp($r['breakeven_gross_unit'] ?? 0) : null,
        ])),
        'cta' => ['label' => 'Analisa iklan', 'route' => 'ceo.roas'],
    ];
@endphp
@include('hub.partials.ceo.shell-open')

    <form method="GET" action="{{ route('ceo.kalkulator') }}" class="hub-card mb-3" data-ceo="form">
        <div class="hub-card-header"><h2 class="h6 mb-0 fw-semibold">Isi angka produk</h2></div>
        <div class="hub-card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small">Pilih produk <span class="text-muted">(isi otomatis)</span></label>
                    <select name="product_id" class="hub-form-control" onchange="this.form.submit()">
                        <option value="">— Ketik manual —</option>
                        @foreach($products as $p)
                        <option value="{{ $p['id'] }}" @selected(($d['product_id'] ?? null) == $p['id'])>{{ $p['name'] }} @if($p['sku'])({{ $p['sku'] }})@endif</option>
                        @endforeach
                    </select>
                </div>
                @if($selectedProduct)
                <div class="col-md-6 d-flex align-items-end gap-2">
                    <a href="{{ route('monitoring.product-analysis.show', ['product' => $selectedProduct->id] + $q) }}" class="hub-btn hub-btn-outline hub-btn-sm"><i class="fas fa-microscope"></i> Detail produk</a>
                </div>
                @endif
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small">Biaya barang / HPP (Rp)</label>
                    <input type="number" name="hpp" class="hub-form-control" value="{{ request('hpp', $d['hpp'] ?? 0) }}" min="0" step="100">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Kemasan per unit (Rp)</label>
                    <input type="number" name="packaging" class="hub-form-control" value="{{ request('packaging', $d['packaging'] ?? 0) }}" min="0" step="100">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Harga jual (Rp)</label>
                    <input type="number" name="sell_price" class="hub-form-control" value="{{ request('sell_price', $d['sell_price'] ?? 0) }}" min="0" step="100">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Potongan Shopee (%)</label>
                    <input type="number" name="admin_pct" class="hub-form-control" value="{{ request('admin_pct', $d['admin_pct'] ?? 18) }}" min="0" max="50" step="0.1">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Biaya ops (% omzet)</label>
                    <input type="number" name="operational_pct" class="hub-form-control" value="{{ request('operational_pct', $d['operational_pct'] ?? 8) }}" min="0" max="50" step="0.1">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Target untung bersih (%)</label>
                    <input type="number" name="target_net_margin_pct" class="hub-form-control" value="{{ request('target_net_margin_pct', $d['target_net_margin_pct'] ?? 15) }}" min="0" max="40" step="0.5">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Iklan per unit (Rp)</label>
                    <input type="number" name="ads_per_unit" class="hub-form-control" value="{{ request('ads_per_unit', $d['ads_per_unit'] ?? 0) }}" min="0" step="100">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Target omzet bulan (Rp)</label>
                    <input type="number" name="target_gross_monthly" class="hub-form-control" value="{{ request('target_gross_monthly', $d['target_gross_monthly'] ?? 0) }}" min="0" step="100000">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Biaya ops bulan (Rp)</label>
                    <input type="number" name="operational_monthly" class="hub-form-control" value="{{ request('operational_monthly', $d['operational_monthly'] ?? 0) }}" min="0" step="100000">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Buffer Set ROAS (%)</label>
                    <input type="number" name="shopee_roas_discount" class="hub-form-control" value="{{ request('shopee_roas_discount', $d['shopee_roas_discount'] ?? 70) }}" min="50" max="90" step="1">
                </div>
            </div>
            <button type="submit" class="hub-btn hub-btn-primary mt-3"><i class="fas fa-sync"></i> Hitung</button>
        </div>
    </form>

    <div class="roas-hero-card mb-3" data-ceo="main-kpi">
        <div class="roas-hero-label">Set ROAS di Shopee Ads</div>
        <div class="roas-hero-value">{{ $setRoas ? number_format($setRoas, 1).'x' : '—' }}</div>
        <div class="roas-hero-sub">Hasil simulasi — pastikan HPP & harga sudah benar</div>
        @if($setRoas && $marginPct !== null)
        <div class="roas-hero-compare">
            Sisa setelah HPP: <strong>{{ $marginPct }}%</strong>
            · Target ROAS: <strong>{{ isset($r['target_roas']) ? number_format($r['target_roas'], 1).'x' : '—' }}</strong>
        </div>
        @endif
    </div>

    @include('hub.partials.ceo.action', ['action' => $ceoAction])

    <div class="roas-mini-grid mb-3">
        <div class="roas-mini"><span class="roas-mini-label">Modal barang / unit</span><span class="roas-mini-val">{{ hub_rp($r['cogs_unit'] ?? 0) }}</span></div>
        <div class="roas-mini"><span class="roas-mini-label">Untung / unit</span><span class="roas-mini-val {{ ($r['net_profit_unit'] ?? 0) >= 0 ? '' : 'amt-neg' }}">{{ hub_rp($r['net_profit_unit'] ?? 0) }}</span></div>
        <div class="roas-mini"><span class="roas-mini-label">Est. untung bulan</span><span class="roas-mini-val">{{ hub_rp($r['est_profit_monthly'] ?? 0, true) }}</span></div>
        <div class="roas-mini"><span class="roas-mini-label">Est. iklan / hari</span><span class="roas-mini-val">{{ hub_rp($r['est_ads_daily'] ?? 0, true) }}</span></div>
    </div>

@include('hub.partials.ceo.shell-close')
@endsection
