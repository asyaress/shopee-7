@extends('layouts.hub')
@section('title', 'Promo & Diskon')
@section('content')
@include('hub.partials.ceo.shell-open')
    @include('hub.partials.hub-zone-nav')
    @include('hub.partials.monitoring-filter')
    @if(!empty($promo['insight']))
    <div class="hub-alert mb-3" style="background:#eff6ff;border-color:#bfdbfe;color:#1e40af;" data-ceo="alerts">
        <i class="fas fa-info-circle me-2"></i>{{ $promo['insight'] }}
    </div>
    @endif
    @if(!empty($promo['monthly']))
    <div class="fc-chart-stack mb-3">
        @include('hub.partials.chart-panel', ['id' => 'promoTake', 'title' => 'Potongan Shopee per bulan', 'subtitle' => 'Line — take rate naik = promo/diskon besar', 'size' => 'hero'])
        @include('hub.partials.chart-panel', ['id' => 'promoProfit', 'title' => 'Untung bersih per bulan', 'subtitle' => 'Column + label — cek bulan promo rugi', 'size' => 'default'])
    </div>
    @endif
    <div class="hub-card" data-ceo="main-kpi">
        <div class="hub-card-body p-0">
            <table class="report-table">
                <thead>
                    <tr><th>Bulan</th><th class="num">Penjualan</th><th class="num">Potongan Shopee</th><th class="num">Untung bersih</th></tr>
                </thead>
                <tbody>
                @foreach($promo['monthly'] ?? [] as $m)
                <tr>
                    <td>{{ $m['label'] }}</td>
                    <td class="num">{{ hub_rp($m['gross']) }}</td>
                    <td class="num">{{ hub_pct($m['take_rate']) }}</td>
                    <td class="num {{ ($m['net_profit'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg' }}">{{ hub_rp($m['net_profit'], true) }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if(($promo['program_hemat'] ?? 0) > 0)
    <p class="small text-muted mt-2">Program Hemat periode ini: {{ hub_rp($promo['program_hemat']) }}</p>
    @endif
@include('hub.partials.ceo.shell-close')
@endsection

@if(!empty($promo['monthly']))
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rows = @json($promo['monthly']);
    HubCharts.render('promoTake', 'line', {
        labels: rows.map(r => r.label),
        datasets: [{ label: 'Take rate %', data: rows.map(r => (r.take_rate || 0) * 100) }],
        format: 'pct',
    });
    HubCharts.renderPreset('promoProfit', 'revenue_orders', {
        labels: rows.map(r => r.label),
        data: rows.map(r => r.net_profit),
        label: 'Laba bersih',
    });
});
</script>
@endpush
@endif
