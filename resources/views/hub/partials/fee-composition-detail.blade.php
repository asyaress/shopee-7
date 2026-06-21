@php
    $feeDetail = $fee_detail ?? [];
    $fb = $fee_breakdown ?? [];
    $fbPct = $fee_breakdown_pct ?? [];
    $s = $summary ?? [];
    $feeLabels = \App\Services\Finance\ShopeeFinancialExtractor::feeLabels();
    $feeDesc = \App\Services\Finance\ShopeeFinancialExtractor::feeDescriptions();
    $rows = $feeDetail !== [] ? $feeDetail : [];
    if ($rows === []) {
        foreach ($feeLabels as $key => $label) {
            $rows[] = [
                'key' => $key,
                'label' => $label,
                'description' => $feeDesc[$key] ?? '',
                'amount' => (int) ($fb[$key] ?? 0),
                'pct_of_fee' => $fbPct[$key] ?? 0,
                'pct_of_gross' => ($s['gross'] ?? 0) > 0 ? ($fb[$key] ?? 0) / max(1, $s['gross']) : 0,
                'avg_per_order' => ($s['orders_count'] ?? 0) > 0 ? (int) round(($fb[$key] ?? 0) / max(1, $s['orders_count'])) : 0,
                'has_value' => ($fb[$key] ?? 0) != 0,
            ];
        }
        usort($rows, fn ($a, $b) => ($b['amount'] ?? 0) <=> ($a['amount'] ?? 0));
    }
    $visibleRows = collect($rows)->filter(fn ($r) => $r['has_value'] ?? ($r['amount'] ?? 0) != 0);
    $zeroCount = count($rows) - $visibleRows->count();
@endphp

<div class="fee-detail-grid mb-3">
    <div class="fee-detail-stat">
        <span class="fee-detail-stat-label">Total potongan platform</span>
        <strong class="fee-detail-stat-val">{{ hub_rp($s['fee_total'] ?? 0, true) }}</strong>
    </div>
    <div class="fee-detail-stat">
        <span class="fee-detail-stat-label">Take rate</span>
        <strong class="fee-detail-stat-val">{{ hub_pct($s['take_rate'] ?? null) }}</strong>
    </div>
    <div class="fee-detail-stat">
        <span class="fee-detail-stat-label">Komponen aktif</span>
        <strong class="fee-detail-stat-val">{{ $visibleRows->count() }} / {{ count($rows) }}</strong>
    </div>
    <div class="fee-detail-stat">
        <span class="fee-detail-stat-label">Rata-rata fee / order</span>
        <strong class="fee-detail-stat-val">{{ hub_rp(($s['orders_count'] ?? 0) > 0 ? ($s['fee_total'] ?? 0) / max(1, $s['orders_count']) : 0) }}</strong>
    </div>
</div>

<div class="table-responsive">
    <table class="report-table fee-detail-table">
        <thead>
            <tr>
                <th>Komponen biaya</th>
                <th class="num">Nominal</th>
                <th class="num">% dari total fee</th>
                <th class="num">% dari kotor</th>
                <th class="num">Rata-rata / order</th>
                <th>Visual</th>
            </tr>
        </thead>
        <tbody>
        @foreach($rows as $row)
        @if($row['has_value'] ?? ($row['amount'] ?? 0) != 0)
        <tr>
            <td>
                <strong>{{ $row['label'] }}</strong>
                @if(!empty($row['description']))
                <div class="fee-detail-desc">{{ $row['description'] }}</div>
                @endif
            </td>
            <td class="num">{{ hub_rp($row['amount'] ?? 0, true) }}</td>
            <td class="num">{{ hub_pct($row['pct_of_fee'] ?? null) }}</td>
            <td class="num">{{ hub_pct($row['pct_of_gross'] ?? null) }}</td>
            <td class="num">{{ hub_rp($row['avg_per_order'] ?? 0) }}</td>
            <td class="fee-detail-bar-cell">
                <div class="fee-bar-track fee-bar-track--inline">
                    <div class="fee-bar-fill" style="width: {{ min(100, ($row['pct_of_fee'] ?? 0) * 100) }}%"></div>
                </div>
            </td>
        </tr>
        @endif
        @endforeach
        </tbody>
        <tfoot>
            <tr class="fee-detail-total">
                <td><strong>Total fee platform</strong></td>
                <td class="num"><strong>{{ hub_rp($s['fee_total'] ?? 0, true) }}</strong></td>
                <td class="num"><strong>100%</strong></td>
                <td class="num"><strong>{{ hub_pct($s['take_rate'] ?? null) }}</strong></td>
                <td class="num"><strong>{{ hub_rp(($s['orders_count'] ?? 0) > 0 ? ($s['fee_total'] ?? 0) / max(1, $s['orders_count']) : 0) }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

@if($zeroCount > 0)
<details class="fee-detail-zero mt-2">
    <summary class="small text-muted">{{ $zeroCount }} komponen tidak ada di periode ini (klik untuk lihat)</summary>
    <div class="table-responsive mt-2">
        <table class="report-table report-table-compact">
            <tbody>
            @foreach($rows as $row)
            @if(!($row['has_value'] ?? ($row['amount'] ?? 0) != 0))
            <tr class="text-muted">
                <td>{{ $row['label'] }}</td>
                <td class="num">Rp 0</td>
                <td>{{ $row['description'] ?? '' }}</td>
            </tr>
            @endif
            @endforeach
            </tbody>
        </table>
    </div>
</details>
@endif

<p class="fee-heatmap-legend small text-muted mb-0 mt-3">
    <span class="fee-legend-swatch fee-legend-swatch--0"></span> Nol
    <span class="fee-legend-swatch fee-legend-swatch--1"></span> Rendah
    <span class="fee-legend-swatch fee-legend-swatch--2"></span> Sedang
    <span class="fee-legend-swatch fee-legend-swatch--3"></span> Tinggi
    — heatmap menunjukkan intensitas nominal per komponen × bulan.
</p>
