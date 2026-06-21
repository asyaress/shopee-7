@extends('layouts.hub')

@section('title', 'Laba Detail — Laporan Keuangan')

@push('styles')
<link href="{{ asset('css/hub-monitoring.css') }}?v=6" rel="stylesheet">
@endpush

@section('content')
@php
    $s = $summary ?? [];
    $filters = $filters ?? [];
    $meta = $meta ?? [];
    $analysis = $analysis ?? [];
    $pt = $product_totals ?? [];
    $fb = $fee_breakdown ?? [];
    $fbPct = $fee_breakdown_pct ?? [];
    $health = $analysis['health_score'] ?? 0;
@endphp

<div class="report-shell">
    {{-- Hero --}}
    <div class="report-hero">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h1><i class="fas fa-chart-pie me-2"></i>Laba Detail</h1>
                <div class="report-hero-meta">
                    <span><i class="far fa-calendar-alt"></i> {{ $meta['period_label'] ?? '—' }}</span>
                    <span><i class="far fa-clock"></i> {{ $meta['days'] ?? 0 }} hari</span>
                    <span><i class="fas fa-sync-alt"></i> Diperbarui {{ $meta['generated_at'] ?? now()->format('d M Y H:i') }}</span>
                    <span><i class="fas fa-filter"></i> Status: {{ ucfirst(str_replace('_',' ', $filters['status'] ?? 'completed')) }}</span>
                    @if(!empty($shop['label']))<span><i class="fas fa-store"></i> {{ $shop['label'] }}</span>@endif
                </div>
            </div>
            <div class="hub-btn-group">
                <a href="{{ route('monitoring.profit', array_merge(request()->query(), ['export' => 'xlsx'])) }}" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5);">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
            </div>
        </div>
        <div class="report-health">
            <div class="report-health-ring" style="--score: {{ $health }}">
                <span>{{ $health }}</span>
            </div>
            <div>
                <strong>Skor kesehatan data & profit</strong>
                <div class="small opacity-90">Berdasarkan kelengkapan HPP, margin, dan efisiensi iklan</div>
            </div>
        </div>
    </div>

    @include('hub.partials.hub-zone-nav')
    @include('hub.partials.monitoring-filter')

    {{-- Insights --}}
    @if(!empty($analysis['insights']))
    <div class="hub-card mb-3">
        <div class="hub-card-header">
            <div>
                <h2 class="report-section-title">Analisis & Rekomendasi</h2>
                <p class="report-section-desc">Ringkasan otomatis berdasarkan data periode terpilih</p>
            </div>
        </div>
        <div class="hub-card-body">
            <div class="report-insights">
                @foreach($analysis['insights'] as $ins)
                <div class="report-insight {{ $ins['type'] }}">
                    <div class="icon">
                        @if($ins['type'] === 'success')<i class="fas fa-check"></i>
                        @elseif($ins['type'] === 'danger')<i class="fas fa-exclamation-triangle"></i>
                        @elseif($ins['type'] === 'warning')<i class="fas fa-exclamation-circle"></i>
                        @else<i class="fas fa-info"></i>@endif
                    </div>
                    <div>
                        <strong>{{ $ins['title'] }}</strong>
                        <p>{{ $ins['text'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- KPI Hero --}}
    <div class="report-kpi-hero">
        <div class="report-kpi-card {{ ($s['net_profit'] ?? 0) >= 0 ? 'positive' : 'negative' }}">
            <div class="label">Laba / Rugi Bersih</div>
            <div class="value">{{ hub_rp($s['net_profit'] ?? 0, true) }}</div>
            <div class="sub">Margin {{ hub_pct($s['margin'] ?? null) }} dari net</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Penghasilan Net</div>
            <div class="value">{{ hub_rp($s['net'] ?? 0) }}</div>
            <div class="sub">Setelah fee Shopee {{ hub_rp($s['fee_total'] ?? 0) }}</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Pendapatan Kotor</div>
            <div class="value">{{ hub_rp($s['gross'] ?? 0) }}</div>
            <div class="sub">{{ hub_num($s['orders_count'] ?? 0) }} pesanan</div>
        </div>
        <div class="report-kpi-card {{ ($s['roas'] ?? 0) >= 2 ? 'positive' : (($s['ads_total'] ?? 0) > 0 ? 'warn' : '') }}">
            <div class="label">ROAS / ACOS</div>
            <div class="value">{{ isset($s['roas']) && $s['roas'] ? number_format($s['roas'], 2) . 'x' : '—' }}</div>
            <div class="sub">ACOS {{ hub_pct($s['acos'] ?? null) }} · Iklan {{ hub_rp($s['ads_total'] ?? 0) }}</div>
        </div>
    </div>

    {{-- KPI Secondary --}}
    <div class="report-kpi-secondary">
        <div class="report-kpi-mini"><div class="label">Laba kotor</div><div class="value">{{ hub_rp($s['gross_profit'] ?? 0) }}</div></div>
        <div class="report-kpi-mini"><div class="label">HPP + Pack</div><div class="value">{{ hub_rp($s['cogs'] ?? 0) }}</div></div>
        <div class="report-kpi-mini"><div class="label">Operasional</div><div class="value">{{ hub_rp($s['operational_total'] ?? 0) }}</div></div>
        <div class="report-kpi-mini"><div class="label">Take rate fee</div><div class="value">{{ hub_pct($s['take_rate'] ?? null) }}</div></div>
        <div class="report-kpi-mini"><div class="label">Rata-rata / order</div><div class="value">{{ hub_rp($s['avg_order_net'] ?? 0) }}</div></div>
        <div class="report-kpi-mini"><div class="label">Rasio biaya</div><div class="value">{{ hub_pct($s['cost_ratio'] ?? null) }}</div></div>
        <div class="report-kpi-mini"><div class="label">SKU aktif</div><div class="value">{{ $s['products_count'] ?? 0 }}</div></div>
        @if(($s['missing_cost_orders'] ?? 0) > 0)
        <div class="report-kpi-mini" style="border-color:#fecaca;background:#fef2f2;">
            <div class="label">Tanpa HPP</div><div class="value amt-neg">{{ $s['missing_cost_orders'] }} order</div>
        </div>
        @endif
    </div>

    <div class="fc-chart-stack mb-3">
        <div class="hub-card">
                <div class="hub-card-header">
                    <div>
                        <h2 class="report-section-title">Laporan Laba Rugi</h2>
                        <p class="report-section-desc">Alur pendapatan hingga laba bersih</p>
                    </div>
                </div>
                <div class="hub-card-body p-0">
                    <table class="report-pl">
                        <tbody>
                        @foreach($pl_statement ?? [] as $row)
                            @php
                                $rowClass = match($row['type'] ?? '') {
                                    'total' => 'total',
                                    'subtotal' => 'subtotal',
                                    'fee' => 'fee',
                                    'revenue' => 'revenue',
                                    default => '',
                                };
                                $amt = (int)($row['amount'] ?? 0);
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td>{{ $row['label'] }}</td>
                                <td class="amount {{ $amt < 0 ? 'amt-neg' : '' }}">{{ hub_rp($amt, true) }}</td>
                            </tr>
                            @foreach($row['children'] ?? [] as $child)
                            <tr class="child fee">
                                <td>{{ $child['label'] }}</td>
                                <td class="amount amt-neg">{{ hub_rp($child['amount'] ?? 0, true) }}</td>
                            </tr>
                            @endforeach
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        @include('hub.partials.chart-panel', [
            'id' => 'chartMonthly',
            'title' => 'Tren laba bulanan',
            'subtitle' => 'Net penghasilan, HPP, dan laba bersih per bulan',
            'size' => 'hero',
            'badge' => 'P&L',
        ])

        <div class="hub-card">
                <div class="hub-card-header"><h2 class="report-section-title">Komposisi Fee Shopee</h2></div>
                <div class="hub-card-body">
                    @php $feeLabels = \App\Services\Finance\ShopeeFinancialExtractor::feeLabels(); @endphp
                    @foreach($feeLabels as $key => $label)
                    @if(($fb[$key] ?? 0) != 0)
                    <div class="fee-bar-row">
                        <div class="fee-label">
                            <span>{{ $label }}</span>
                            <strong>{{ hub_rp($fb[$key] ?? 0) }} · {{ hub_pct($fbPct[$key] ?? 0) }}</strong>
                        </div>
                        <div class="fee-bar-track">
                            <div class="fee-bar-fill" style="width: {{ min(100, ($fbPct[$key] ?? 0) * 100) }}%"></div>
                        </div>
                    </div>
                    @endif
                    @endforeach
                    <div class="mt-3 pt-2 border-top d-flex justify-content-between">
                        <span class="text-muted small">Total fee platform</span>
                        <strong>{{ hub_rp($s['fee_total'] ?? 0) }}</strong>
                    </div>
                </div>
        </div>
    </div>

    {{-- Monthly table --}}
    @if(!empty($monthly))
    <div class="hub-card mb-3">
        <div class="hub-card-header"><h2 class="report-section-title">Rekap Bulanan</h2></div>
        <div class="hub-card-body p-0">
            <div class="hub-table-wrap">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th class="num">Pesanan</th>
                            <th class="num">Pendapatan</th>
                            <th class="num">Net</th>
                            <th class="num">HPP</th>
                            <th class="num">Iklan</th>
                            <th class="num">Opr.</th>
                            <th class="num">Laba Bersih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($monthly as $m)
                        <tr>
                            <td><strong>{{ $m['label'] }}</strong></td>
                            <td class="num">{{ $m['orders'] ?? 0 }}</td>
                            <td class="num">{{ hub_rp($m['gross'] ?? 0) }}</td>
                            <td class="num">{{ hub_rp($m['net'] ?? 0) }}</td>
                            <td class="num">{{ hub_rp($m['cogs'] ?? 0) }}</td>
                            <td class="num">{{ hub_rp($m['ads'] ?? 0) }}</td>
                            <td class="num">{{ hub_rp($m['operational'] ?? 0) }}</td>
                            <td class="num {{ ($m['net_profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg' }}">{{ hub_rp($m['net_profit'] ?? 0, true) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="hub-card h-100">
                <div class="hub-card-header"><h2 class="report-section-title">Top 5 Produk (Laba)</h2></div>
                <div class="hub-card-body">
                    <ul class="rank-list">
                        @forelse($top_products ?? [] as $i => $p)
                        <li>
                            <span class="rank-num">{{ $i + 1 }}</span>
                            <div class="rank-body"><div class="rank-name">{{ $p['name'] }}</div><div class="small text-muted">Qty {{ $p['qty'] ?? 0 }}</div></div>
                            <span class="rank-val {{ ($p['net_profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg' }}">{{ hub_rp($p['net_profit'] ?? 0, true) }}</span>
                        </li>
                        @empty
                        <li class="text-muted">Tidak ada data</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="hub-card h-100">
                <div class="hub-card-header"><h2 class="report-section-title">Perlu Perhatian (Laba Terendah)</h2></div>
                <div class="hub-card-body">
                    <ul class="rank-list">
                        @forelse($bottom_products ?? [] as $i => $p)
                        <li>
                            <span class="rank-num">{{ $i + 1 }}</span>
                            <div class="rank-body"><div class="rank-name">{{ $p['name'] }}</div></div>
                            <span class="rank-val amt-neg">{{ hub_rp($p['net_profit'] ?? 0, true) }}</span>
                        </li>
                        @empty
                        <li class="text-muted">Tidak ada data</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Product table --}}
    <div class="hub-card mb-3">
        <div class="hub-card-header flex-wrap">
            <div>
                <h2 class="report-section-title">Analisis per Produk</h2>
                <p class="report-section-desc">{{ count($products ?? []) }} SKU · alokasi net, HPP, iklan & operasional</p>
            </div>
            <input type="search" id="productSearch" class="hub-form-control report-search" placeholder="Cari produk / SKU...">
        </div>
        <div class="hub-card-body p-0">
            <div class="report-table-scroll hub-table-desktop">
                <table class="report-table" id="productTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Produk</th>
                            <th>Tier</th>
                            <th>Harga</th>
                            <th class="num">Qty</th>
                            <th class="num">Gross</th>
                            <th class="num">Net</th>
                            <th class="num">HPP</th>
                            <th class="num">Iklan</th>
                            <th class="num">Opr.</th>
                            <th class="num">Laba</th>
                            <th class="num">Margin</th>
                            <th class="num">ROAS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products ?? [] as $i => $p)
                        <tr data-search="{{ strtolower($p['name'] . ' ' . ($p['sku'] ?? '')) }}">
                            <td class="text-muted">{{ $i + 1 }}</td>
                            <td class="product-cell">
                                @if(!empty($p['product_id']))
                                <a href="{{ route('monitoring.product-analysis.show', ['product' => $p['product_id']] + request()->query()) }}" class="name text-decoration-none" title="{{ $p['name'] }}">{{ $p['name'] }}</a>
                                @include('hub.partials.product-shopee-links', ['links' => $p['links'] ?? []])
                                @else
                                <span class="name" title="{{ $p['name'] }}">{{ $p['name'] }}</span>
                                @endif
                                @if(!empty($p['sku']))<span class="sku">{{ $p['sku'] }}</span>@endif
                                @if($p['missing_cost'] ?? false)<span class="hub-pill hub-pill-warning">HPP?</span>@endif
                                @if(!empty($p['action']['title']))<span class="d-block small text-muted">{{ $p['action']['title'] }}</span>@endif
                            </td>
                            <td><span class="sku-tier {{ $p['tier'] ?? '' }}">{{ $p['tier'] ?? '—' }}</span></td>
                            <td class="small">
                                @php $ps = ($p['pricing'] ?? [])['status'] ?? ''; @endphp
                                @if($ps)
                                <span class="price-status-{{ $ps === 'ok' ? 'ok' : (in_array($ps, ['too_low','not_covering']) ? 'low' : 'review') }}">{{ $p['pricing']['status_label'] ?? '' }}</span>
                                @else — @endif
                            </td>
                            <td class="num">{{ hub_num($p['qty'] ?? 0) }}</td>
                            <td class="num">{{ hub_rp($p['gross'] ?? 0) }}</td>
                            <td class="num">{{ hub_rp($p['net'] ?? 0) }}</td>
                            <td class="num">{{ hub_rp($p['cogs'] ?? 0) }}</td>
                            <td class="num">{{ hub_rp($p['ads_spend'] ?? 0) }}</td>
                            <td class="num">{{ hub_rp($p['operational'] ?? 0) }}</td>
                            <td class="num {{ ($p['net_profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg' }}"><strong>{{ hub_rp($p['net_profit'] ?? 0, true) }}</strong></td>
                            <td class="num">{{ hub_pct($p['margin'] ?? null) }}</td>
                            <td class="num">{{ isset($p['roas']) && $p['roas'] ? number_format($p['roas'], 2) : '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="13" class="text-center py-4 text-muted">Belum ada data produk pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                    @if(!empty($products))
                    <tfoot>
                        <tr>
                            <td colspan="4"><strong>TOTAL</strong></td>
                            <td class="num">{{ hub_num($pt['qty'] ?? 0) }}</td>
                            <td class="num">{{ hub_rp($pt['gross'] ?? 0) }}</td>
                            <td class="num">{{ hub_rp($pt['net'] ?? 0) }}</td>
                            <td class="num">{{ hub_rp($pt['cogs'] ?? 0) }}</td>
                            <td class="num">{{ hub_rp($pt['ads_spend'] ?? 0) }}</td>
                            <td class="num">{{ hub_rp($pt['operational'] ?? 0) }}</td>
                            <td class="num">{{ hub_rp($pt['net_profit'] ?? 0, true) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
            <div class="hub-product-cards">
                @include('hub.partials.product-mobile-cards', ['products' => $products ?? []])
            </div>
        </div>
    </div>

    @if(!empty($orders))
    <div class="hub-card">
        <div class="hub-card-header report-collapse-toggle" data-bs-toggle="collapse" data-bs-target="#ordersCollapse">
            <div>
                <h2 class="report-section-title"><i class="fas fa-chevron-down me-2"></i>Detail Pesanan ({{ count($orders) }})</h2>
                <p class="report-section-desc">50 transaksi terbaru dalam periode</p>
            </div>
        </div>
        <div class="collapse show" id="ordersCollapse">
            <div class="hub-card-body p-0">
                <div class="report-table-scroll">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>No. Pesanan</th>
                                <th class="num">Gross</th>
                                <th class="num">Net</th>
                                <th class="num">HPP</th>
                                <th class="num">Laba Kotor</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(array_slice($orders, 0, 50) as $o)
                            <tr>
                                <td>{{ $o['date'] }}</td>
                                <td><code class="small">{{ $o['order_number'] }}</code></td>
                                <td class="num">{{ hub_rp($o['gross'] ?? 0) }}</td>
                                <td class="num">{{ hub_rp($o['net'] ?? 0) }}</td>
                                <td class="num">{{ hub_rp($o['cogs'] ?? 0) }}</td>
                                <td class="num {{ ($o['profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg' }}">{{ hub_rp($o['profit'] ?? 0, true) }}</td>
                                <td><a href="{{ $o['detail_url'] }}" class="hub-btn hub-btn-sm hub-btn-outline">Detail</a></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const monthly = @json($monthly ?? []);
    if (monthly.length && typeof HubCharts !== 'undefined') {
        HubCharts.renderMonthly('chartMonthly', monthly);
    }
});
</script>
@endpush
