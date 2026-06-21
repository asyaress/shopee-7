@extends('layouts.hub')

@section('title', 'Rekap Bulanan')

@section('content')
@php
    $s = $summary ?? [];
    $rek = $rekap ?? [];
    $months = $rek['months'] ?? [];
    $columns = $rek['columns'] ?? [];
    $metrics = $rek['metrics'] ?? [];
    $best = $rek['best_sellers'] ?? [];
    $heroExtra = '<span class="small text-muted">'.count($months).' bulan · '.e($shop['label'] ?? '').'</span>';
    $rekChartLabels = [];
    $rekGross = [];
    $rekNetProfit = [];
    $rekRoas = [];
    foreach ($months as $mk) {
        $rekChartLabels[] = $columns[$mk]['short'] ?? $mk;
        $rekGross[] = (int) ($columns[$mk]['gross'] ?? 0);
        $rekNetProfit[] = (int) ($columns[$mk]['net_profit'] ?? 0);
        $rekRoas[] = (float) ($columns[$mk]['roas'] ?? 0);
    }
@endphp
@include('hub.partials.ceo.shell-open')

    @include('hub.partials.hub-zone-nav')

    <div class="mon-kpi-row" data-ceo="main-kpi">
        <div class="mon-kpi"><div class="label">Rata-rata order</div><div class="value">{{ hub_rp($s['aov_gross'] ?? 0) }}</div></div>
        <div class="mon-kpi"><div class="label">Item per order</div><div class="value">{{ $s['basket_size'] ?? '—' }}</div></div>
        <div class="mon-kpi"><div class="label">Margin kotor</div><div class="value">{{ hub_pct($s['gross_margin_pct'] ?? null) }}</div></div>
        <div class="mon-kpi"><div class="label">Margin bersih</div><div class="value">{{ hub_pct($s['net_margin_pct'] ?? null) }}</div></div>
    </div>

    @if(count($rekChartLabels) > 0)
    <div class="fc-chart-stack mb-3">
        @include('hub.partials.chart-panel', ['id' => 'rekTrend', 'title' => 'Tren penjualan & laba', 'subtitle' => 'Area chart — kotor vs laba bersih', 'size' => 'hero'])
        @include('hub.partials.chart-panel', ['id' => 'rekRoas', 'title' => 'ROAS per bulan', 'subtitle' => 'Line + label — 1 bulan tampil sebagai gauge', 'size' => 'compact'])
    </div>
    @endif

    <div class="hub-card mb-3">
        <div class="hub-card-header">
            <h2 class="report-section-title">Rekap multi-bulan</h2>
            <p class="report-section-desc mb-0">Semua rasio sejajar per bulan — scroll horizontal di mobile</p>
        </div>
        <div class="hub-card-body p-0">
            <div class="rekap-grid-wrap">
                <table class="rekap-grid">
                    <thead>
                        <tr>
                            <th class="rekap-sticky">Metrik</th>
                            @foreach($months as $mk)
                            <th>{{ $columns[$mk]['short'] ?? $mk }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($metrics as $m)
                        <tr>
                            <td class="rekap-sticky"><strong>{{ $m['label'] }}</strong></td>
                            @foreach($months as $mk)
                            @php $val = $columns[$mk][$m['key']] ?? null; @endphp
                            <td class="num">
                                @if($val === null) —
                                @elseif($m['format'] === 'rp') {{ hub_rp($val) }}
                                @elseif($m['format'] === 'pct') {{ hub_pct($val) }}
                                @elseif($m['format'] === 'x') {{ is_numeric($val) ? number_format($val, 2).'x' : '—' }}
                                @else {{ is_numeric($val) ? number_format($val, 2) : $val }}
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="hub-card">
        <div class="hub-card-header">
            <h2 class="report-section-title">Best seller per bulan</h2>
            <p class="report-section-desc mb-0">Top 8 SKU by qty — 3 bulan terakhir</p>
        </div>
        <div class="hub-card-body">
            <div class="best-seller-mom">
                @forelse($best as $mk => $period)
                <div class="best-seller-col">
                    <h3 class="h6">{{ $period['label'] ?? $mk }}</h3>
                    <ol class="best-seller-list">
                        @foreach($period['products'] ?? [] as $i => $p)
                        <li>
                            <span class="rank">{{ $i + 1 }}</span>
                            <span class="name">{{ \Illuminate\Support\Str::limit($p['name'] ?? '—', 28) }}</span>
                            <strong>{{ $p['qty'] ?? 0 }}</strong>
                        </li>
                        @endforeach
                    </ol>
                </div>
                @empty
                <p class="text-muted mb-0">Belum ada data best seller.</p>
                @endforelse
            </div>
        </div>
    </div>
@include('hub.partials.ceo.shell-close')
@endsection

@if(count($rekChartLabels ?? []) > 0)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const labels = @json($rekChartLabels);
    HubCharts.renderPreset('rekTrend', 'revenue_trend', {
        labels,
        datasets: [
            { label: 'Penjualan kotor', data: @json($rekGross) },
            { label: 'Laba bersih', data: @json($rekNetProfit) },
        ],
    });
    HubCharts.render('rekRoas', 'line', {
        labels,
        datasets: [{ label: 'ROAS', data: @json($rekRoas) }],
        format: 'roas',
    });
});
</script>
@endpush
@endif
