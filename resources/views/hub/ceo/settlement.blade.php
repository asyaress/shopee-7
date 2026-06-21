@extends('layouts.hub')

@section('title', 'Arus Kas — CEO')

@section('content')
<div class="report-shell">
    <div class="report-hero">
        <h1><i class="fas fa-wallet me-2"></i>Estimasi Arus Kas</h1>
        <p class="small opacity-90 mb-0">{{ $cashflow['note'] ?? '' }}</p>
    </div>
    @include('hub.partials.hub-zone-nav')

    <div class="mon-kpi-row mb-3">
        <div class="mon-kpi"><div class="label">Dana pending</div><div class="value">{{ hub_rp($cashflow['pending_settlement'] ?? 0) }}</div></div>
        <div class="mon-kpi"><div class="label">Sudah dilepas (periode)</div><div class="value">{{ hub_rp($cashflow['released_total'] ?? 0) }}</div></div>
        <div class="mon-kpi"><div class="label">Hold estimasi</div><div class="value">{{ $cashflow['hold_days'] ?? 3 }} hari</div></div>
    </div>

    <div class="hub-card mb-3">
        <div class="hub-card-header"><h2 class="report-section-title">Import Data Income</h2></div>
        <div class="hub-card-body">
            <p class="small text-muted">{{ $cashflow['import_hint'] ?? '' }}</p>
            <form method="POST" action="{{ route('ceo.settlement.import') }}" enctype="multipart/form-data" class="hub-filter-bar mt-2">
                @csrf
                <div class="filter-item">
                    <label class="hub-form-label">CSV export Data Income Shopee</label>
                    <input type="file" name="file" class="hub-form-control" accept=".csv,.txt" required>
                </div>
                <div class="filter-item" style="align-self:flex-end">
                    <button type="submit" class="hub-btn hub-btn-primary"><i class="fas fa-upload me-1"></i> Import dana dilepaskan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="fc-chart-stack mb-3">
        @include('hub.partials.chart-panel', [
            'id' => 'cashChart',
            'title' => 'Arus kas mingguan',
            'subtitle' => 'Net masuk vs biaya iklan keluar',
            'size' => 'hero',
            'badge' => 'Estimasi',
        ])
    </div>
    <div class="hub-card">
        <div class="hub-card-body p-0">
            <table class="report-table">
                <thead><tr><th>Minggu</th><th class="num">Net masuk</th><th class="num">Iklan keluar</th><th class="num">Selisih</th></tr></thead>
                <tbody>
                @foreach($cashflow['weeks'] ?? [] as $w)
                <tr>
                    <td>{{ $w['label'] }}</td>
                    <td class="num">{{ hub_rp($w['net_in']) }}</td>
                    <td class="num">{{ hub_rp($w['ads_out']) }}</td>
                    <td class="num {{ ($w['net_cash'] ?? 0) >= 0 ? 'amt-pos' : 'amt-neg' }}">{{ hub_rp($w['net_cash'], true) }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const weeks = @json($cashflow['weeks'] ?? []);
    HubCharts.render('cashChart', 'bar', {
        labels: weeks.map(w => w.label),
        datasets: [
            { label: 'Net masuk', data: weeks.map(w => w.net_in) },
            { label: 'Iklan', data: weeks.map(w => w.ads_out) },
        ]
    });
});
</script>
@endpush
