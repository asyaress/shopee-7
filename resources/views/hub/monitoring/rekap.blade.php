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
    $mode = $rekap_mode ?? 'detail';
    $hasData = $rekap_has_data ?? false;
    $selectedMonth = $rekap_selected_month ?? null;
    $selectedCol = $selectedMonth ? ($columns[$selectedMonth] ?? []) : [];
    $pageMeta = [
        ['icon' => 'fas fa-store', 'label' => 'Toko', 'value' => $shop['label'] ?? '—'],
    ];
    if ($hasData) {
        array_unshift($pageMeta, [
            'icon' => 'far fa-calendar-alt',
            'label' => $mode === 'compare' ? 'Bandingkan' : 'Bulan',
            'value' => $mode === 'compare'
                ? count($months).' bulan'
                : ($selectedCol['label'] ?? $selectedMonth ?? '—'),
        ]);
    }

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
    @include('hub.partials.rekap-picker')

    @if(!$hasData)
    <div class="rekap-empty hub-card">
        <div class="hub-card-body text-center py-5">
            <div class="rekap-empty-icon"><i class="far fa-calendar-check"></i></div>
            <h2 class="h5 mb-2">Belum ada bulan dipilih</h2>
            <p class="text-muted mb-0 mx-auto" style="max-width:28rem">
                @if($mode === 'compare')
                    Centang minimal <strong>2 bulan</strong> lalu klik <strong>Bandingkan</strong> untuk melihat tabel & grafik perbandingan.
                @else
                    Pilih bulan di atas lalu klik <strong>Lihat Detail</strong> untuk KPI, metrik lengkap, dan best seller bulan tersebut.
                @endif
            </p>
        </div>
    </div>
    @else

    @if($mode === 'detail' && count($months) === 1)
    {{-- Detail single month --}}
    <div class="report-kpi-hero mb-3" data-ceo="main-kpi">
        <div class="report-kpi-card {{ ($selectedCol['net_profit'] ?? 0) >= 0 ? 'positive' : 'negative' }}">
            <div class="label">Laba / Rugi Bersih</div>
            <div class="value">{{ hub_rp($selectedCol['net_profit'] ?? 0, true) }}</div>
            <div class="sub">Margin {{ hub_pct($selectedCol['net_margin_pct'] ?? null) }}</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Penjualan Kotor</div>
            <div class="value">{{ hub_rp($selectedCol['gross'] ?? 0) }}</div>
            <div class="sub">{{ hub_num($selectedCol['orders'] ?? 0) }} order · {{ hub_num($selectedCol['units'] ?? 0) }} unit</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">Penghasilan Net</div>
            <div class="value">{{ hub_rp($selectedCol['net'] ?? 0) }}</div>
            <div class="sub">Fee {{ hub_rp($selectedCol['fee_total'] ?? 0) }}</div>
        </div>
        <div class="report-kpi-card">
            <div class="label">ROAS / Iklan</div>
            <div class="value">{{ isset($selectedCol['roas']) && $selectedCol['roas'] ? number_format($selectedCol['roas'], 2).'x' : '—' }}</div>
            <div class="sub">Spend {{ hub_rp($selectedCol['ads'] ?? 0) }}</div>
        </div>
    </div>

    @php $targets = $rek['targets'][$selectedMonth] ?? null; @endphp
    @if($targets && (($targets['target_gross'] ?? 0) > 0 || ($targets['target_net_profit'] ?? 0) > 0))
    <div class="hub-card mb-3">
        <div class="hub-card-header"><h2 class="report-section-title">Target vs Realisasi</h2></div>
        <div class="hub-card-body">
            <div class="mon-kpi-row">
                @if(($targets['target_gross'] ?? 0) > 0)
                <div class="mon-kpi">
                    <div class="label">Target kotor</div>
                    <div class="value">{{ hub_rp($targets['target_gross']) }}</div>
                    <div class="sub">Realisasi {{ hub_pct(($selectedCol['gross'] ?? 0) / max(1, $targets['target_gross'])) }}</div>
                </div>
                @endif
                @if(($targets['target_net_profit'] ?? 0) > 0)
                <div class="mon-kpi">
                    <div class="label">Target laba</div>
                    <div class="value">{{ hub_rp($targets['target_net_profit']) }}</div>
                    <div class="sub">Realisasi {{ hub_pct(($selectedCol['net_profit'] ?? 0) / max(1, $targets['target_net_profit'])) }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <div class="hub-card mb-3">
        <div class="hub-card-header">
            <h2 class="report-section-title">Detail metrik — {{ $selectedCol['label'] ?? '' }}</h2>
        </div>
        <div class="hub-card-body p-0">
            <div class="rekap-detail-list">
                @foreach($metrics as $m)
                @php $val = $selectedCol[$m['key']] ?? null; @endphp
                <div class="rekap-detail-row">
                    <span class="rekap-detail-label">{{ $m['label'] }}</span>
                    <span class="rekap-detail-value num">
                        @if($val === null) —
                        @elseif($m['format'] === 'rp') {{ hub_rp($val) }}
                        @elseif($m['format'] === 'pct') {{ hub_pct($val) }}
                        @elseif($m['format'] === 'x') {{ is_numeric($val) ? number_format($val, 2).'x' : '—' }}
                        @else {{ is_numeric($val) ? number_format($val, 0, ',', '.') : $val }}
                        @endif
                    </span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    @if(!empty($best[$selectedMonth]['products']))
    <div class="hub-card mb-3">
        <div class="hub-card-header">
            <h2 class="report-section-title">Best seller — {{ $selectedCol['label'] ?? '' }}</h2>
            <p class="report-section-desc mb-0">Top 8 SKU by qty</p>
        </div>
        <div class="hub-card-body">
            <ol class="best-seller-list best-seller-list--single">
                @foreach($best[$selectedMonth]['products'] as $i => $p)
                <li>
                    <span class="rank">{{ $i + 1 }}</span>
                    <span class="name">{{ $p['name'] ?? '—' }}</span>
                    <strong>{{ $p['qty'] ?? 0 }}</strong>
                </li>
                @endforeach
            </ol>
        </div>
    </div>
    @endif

    @else
    {{-- Compare multiple months --}}
    @if(count($months) >= 1)
    <div class="mon-kpi-row mb-3" data-ceo="main-kpi">
        <div class="mon-kpi"><div class="label">Total kotor ({{ count($months) }} bln)</div><div class="value">{{ hub_rp($s['gross'] ?? 0) }}</div></div>
        <div class="mon-kpi"><div class="label">Total laba bersih</div><div class="value">{{ hub_rp($s['net_profit'] ?? 0, true) }}</div></div>
        <div class="mon-kpi"><div class="label">Total order</div><div class="value">{{ hub_num($s['orders_count'] ?? 0) }}</div></div>
        <div class="mon-kpi"><div class="label">Margin bersih rata</div><div class="value">{{ hub_pct($s['net_margin_pct'] ?? null) }}</div></div>
    </div>
    @endif

    @if(count($rekChartLabels) > 0)
    <div class="fc-chart-stack mb-3">
        @include('hub.partials.chart-panel', ['id' => 'rekTrend', 'title' => 'Tren penjualan & laba', 'subtitle' => 'Bulan terpilih — kotor vs laba bersih', 'size' => 'hero'])
        @include('hub.partials.chart-panel', ['id' => 'rekRoas', 'title' => 'ROAS per bulan', 'subtitle' => count($months) === 1 ? 'Gauge single month' : 'Line chart', 'size' => 'compact'])
    </div>
    @endif

    <div class="hub-card mb-3">
        <div class="hub-card-header">
            <h2 class="report-section-title">Perbandingan metrik</h2>
            <p class="report-section-desc mb-0">{{ count($months) }} bulan · scroll horizontal di mobile</p>
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

    @if(count($best) > 0)
    <div class="hub-card">
        <div class="hub-card-header">
            <h2 class="report-section-title">Best seller per bulan</h2>
            <p class="report-section-desc mb-0">Top 8 SKU — bulan terpilih</p>
        </div>
        <div class="hub-card-body">
            <div class="best-seller-mom">
                @foreach($best as $mk => $period)
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
                @endforeach
            </div>
        </div>
    </div>
    @endif
    @endif

    @endif

@include('hub.partials.ceo.shell-close')
@endsection

@push('scripts')
<script src="{{ asset('js/rekap-picker.js') }}?v=1"></script>
@if($hasData && count($rekChartLabels ?? []) > 0 && ($mode === 'compare' || count($months) > 1))
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
@endif
@endpush
