@extends('layouts.hub')

@section('title', 'Arus Kas')
@section('content')
@include('hub.partials.ceo.shell-open')
    @include('hub.partials.hub-zone-nav')

    @if(!empty($cashflow['note']))
    <div class="hub-alert mb-3" style="background:#eff6ff;border-color:#bfdbfe;color:#1e40af;">
        <i class="fas fa-info-circle me-2"></i>{{ $cashflow['note'] }}
    </div>
    @endif

    <div class="mon-kpi-row mb-3" data-ceo="main-kpi">
        <div class="mon-kpi"><div class="label">Uang belum cair</div><div class="value">{{ hub_rp($cashflow['pending_settlement'] ?? 0) }}</div></div>
        <div class="mon-kpi"><div class="label">Sudah masuk rekening</div><div class="value">{{ hub_rp($cashflow['released_total'] ?? 0) }}</div></div>
        <div class="mon-kpi"><div class="label">Tunggu Shopee</div><div class="value">{{ $cashflow['hold_days'] ?? 3 }} hari</div></div>
    </div>

    <div class="hub-card mb-3">
        <div class="hub-card-header"><h2 class="report-section-title">Import data dari Shopee</h2></div>
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
@include('hub.partials.ceo.shell-close')
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const weeks = @json($cashflow['weeks'] ?? []);
    HubCharts.renderPreset('cashChart', 'settlement_cash', {
        labels: weeks.map(w => w.label),
        datasets: [
            { label: 'Uang masuk', data: weeks.map(w => w.net_in) },
            { label: 'Iklan keluar', data: weeks.map(w => w.ads_out) },
        ],
    });
});
</script>
@endpush
