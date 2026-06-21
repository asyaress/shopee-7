<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] ?? 'Laporan' }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1e293b;
            margin: 0;
            padding: 24px 28px;
            line-height: 1.45;
        }
        .hdr {
            border-bottom: 3px solid #9a2542;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .brand {
            font-size: 11px;
            color: #9a2542;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        h1 {
            font-size: 20px;
            color: #6b1528;
            margin: 4px 0 2px;
        }
        .subtitle {
            font-size: 12px;
            color: #64748b;
            margin: 0;
        }
        .meta-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 9px;
        }
        .meta-grid td {
            padding: 4px 8px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .meta-grid td:first-child {
            background: #f8e8ed;
            font-weight: bold;
            width: 28%;
            color: #6b1528;
        }
        .kpi-row {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin: 0 -8px 18px;
        }
        .kpi {
            background: #fafafa;
            border: 1px solid #e2e8f0;
            border-top: 3px solid #9a2542;
            padding: 10px 12px;
            text-align: center;
        }
        .kpi-label {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .kpi-value {
            font-size: 14px;
            font-weight: bold;
            color: #6b1528;
            margin-top: 2px;
        }
        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #6b1528;
            margin: 0 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e2e8f0;
        }
        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
        }
        table.data th {
            background: #9a2542;
            color: #fff;
            font-weight: bold;
            padding: 6px 5px;
            text-align: left;
        }
        table.data th.num,
        table.data td.num { text-align: right; }
        table.data td {
            padding: 5px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        table.data tr:nth-child(even) td { background: #fafafa; }
        .footer {
            position: fixed;
            bottom: 16px;
            left: 28px;
            right: 28px;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
        .footer span { float: right; }
    </style>
</head>
<body>
    <div class="hdr">
        <div class="brand">Shopee Profit Hub</div>
        <h1>{{ $report['title'] ?? 'Laporan' }}</h1>
        @if(!empty($report['subtitle']))
        <p class="subtitle">{{ $report['subtitle'] }}</p>
        @endif
    </div>

    @if(!empty($report['meta']))
    <table class="meta-grid">
        @foreach($report['meta'] as $item)
        <tr>
            <td>{{ $item['label'] ?? '' }}</td>
            <td>{{ $item['value'] ?? '—' }}</td>
        </tr>
        @endforeach
        <tr>
            <td>Dicetak pada</td>
            <td>{{ $generated_at ?? now()->format('d/m/Y H:i') }}</td>
        </tr>
    </table>
    @endif

    @if(!empty($report['kpis']))
    <table class="kpi-row">
        <tr>
            @foreach($report['kpis'] as $kpi)
            <td class="kpi" style="width: {{ round(100 / max(1, count($report['kpis']))) }}%;">
                <div class="kpi-label">{{ $kpi['label'] ?? '' }}</div>
                <div class="kpi-value">{{ $kpi['value'] ?? '—' }}</div>
            </td>
            @endforeach
        </tr>
    </table>
    @endif

    @foreach($report['sections'] ?? [] as $section)
    <div class="section">
        <h2 class="section-title">{{ $section['title'] ?? 'Data' }}</h2>
        @if(!empty($section['headings']) && !empty($section['rows']))
        <table class="data">
            <thead>
                <tr>
                    @foreach($section['headings'] as $hIdx => $heading)
                    <th class="{{ $hIdx > 0 ? 'num' : '' }}">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($section['rows'] as $row)
                <tr>
                    @foreach($row as $cIdx => $cell)
                    <td class="{{ $cIdx > 0 ? 'num' : '' }}">{{ $cell }}</td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color:#94a3b8;">Tidak ada data.</p>
        @endif
    </div>
    @endforeach

    <div class="footer">
        Shopee Profit Hub — Laporan internal · {{ $generated_at ?? '' }}
    </div>
</body>
</html>
