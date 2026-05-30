@extends('layouts.hub')

@section('title', 'Laporan Profit')

@section('content')
@php
    $marginPct = ($summary['net_income'] ?? 0) > 0 ? (($summary['profit'] ?? 0) / ($summary['net_income'] ?? 1)) * 100 : 0;
    $takeRatePct = ($summary['gross_sales'] ?? 0) > 0 ? (($summary['fees_total'] ?? 0) / ($summary['gross_sales'] ?? 1)) * 100 : 0;

    $fmt = function($n){
        return 'Rp ' . number_format((float)$n, 0, ',', '.');
    };
@endphp

<div class="report-shell">
    @include('hub.partials.page-hero', [
        'icon' => 'fa-chart-pie',
        'title' => 'Laporan Profit',
        'subtitle' => 'Net − HPP − Packaging (Shopee: seller income setelah fee)',
        'actions' => '<a href="' . route('monitoring.index') . '" class="hub-btn hub-btn-primary" style="background:#fff;color:var(--maroon-800)!important;"><i class="fas fa-chart-line"></i> Monitoring Baru</a>'
            . '<a href="' . route('reports.profit.export.orders', request()->query()) . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-file-excel"></i> Orders</a>'
            . '<a href="' . route('products.costs') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-tags"></i> HPP</a>',
    ])

    {{-- FILTERS --}}
    <div class="hub-card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label fw-semibold mb-1">Start</label>
                    <input type="date" class="form-control" name="start_date"
                           value="{{ $filters['start']->toDateString() }}">
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label fw-semibold mb-1">End</label>
                    <input type="date" class="form-control" name="end_date"
                           value="{{ $filters['end']->toDateString() }}">
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label fw-semibold mb-1">Status</label>
                    <select class="form-select" name="status">
                        @php $st = $filters['status'] ?? 'completed'; @endphp
                        <option value="completed" {{ $st==='completed' ? 'selected':'' }}>completed</option>
                        <option value="pending" {{ $st==='pending' ? 'selected':'' }}>pending</option>
                        <option value="in_progress" {{ $st==='in_progress' ? 'selected':'' }}>in_progress</option>
                        <option value="cancelled" {{ $st==='cancelled' ? 'selected':'' }}>cancelled</option>
                        <option value="all" {{ $st==='all' ? 'selected':'' }}>all</option>
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label fw-semibold mb-1">Jenis</label>
                    <select class="form-select" name="jenis_transaksi">
                        @php $jt = $filters['jenis'] ?? 'All'; @endphp
                        <option value="All" {{ $jt==='All' ? 'selected':'' }}>All</option>
                        @foreach($jenisList as $j)
                            <option value="{{ $j }}" {{ $jt===$j ? 'selected':'' }}>{{ $j }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label fw-semibold mb-1">Produk</label>
                    <select class="form-select" name="product_id">
                        <option value="">All</option>
                        @foreach($productsForFilter as $p)
                            <option value="{{ $p->id }}" {{ (int)($filters['product_id'] ?? 0) === (int)$p->id ? 'selected':'' }}>
                                {{ $p->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-lg-1 d-grid">
                    <button class="hub-btn hub-btn-primary">
                        <i class="fas fa-filter me-1"></i>Apply
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- KPI --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="hub-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Gross Sales (Harga Produk)</div>
                    <div class="h4 fw-bold mb-0">{{ $fmt($summary['gross_sales'] ?? 0) }}</div>
                    <div class="text-muted small mt-1">
                        Orders: <b>{{ number_format($summary['orders_count'] ?? 0) }}</b>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="hub-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Fees Total</div>
                    <div class="h4 fw-bold mb-0">{{ $fmt($summary['fees_total'] ?? 0) }}</div>
                    <div class="text-muted small mt-1">
                        Take rate: <b>{{ number_format($takeRatePct, 1) }}%</b>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="hub-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Net Income (Penghasilan)</div>
                    <div class="h4 fw-bold mb-0">{{ $fmt($summary['net_income'] ?? 0) }}</div>
                    <div class="text-muted small mt-1">Net = setelah fee (Shopee)</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="hub-card h-100">
                <div class="card-body">
                    <div class="text-muted small">COGS (HPP + Packaging)</div>
                    <div class="h4 fw-bold mb-0">{{ $fmt($summary['cogs'] ?? 0) }}</div>
                    <div class="text-muted small mt-1">
                        Missing cost orders: <b>{{ number_format($summary['missing_cost_orders'] ?? 0) }}</b>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="hub-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Profit</div>
                    <div class="h3 fw-bold mb-0 {{ ($summary['profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $fmt($summary['profit'] ?? 0) }}
                    </div>
                    <div class="text-muted small mt-1">Profit = Net - COGS</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="hub-card h-100">
                <div class="card-body">
                    <div class="text-muted small">Margin</div>
                    <div class="h3 fw-bold mb-0">{{ number_format($marginPct, 1) }}%</div>
                    <div class="text-muted small mt-1">Margin = Profit / Net</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-8">
            <div class="hub-card">
                <div class="card-header bg-light">
                    <div class="fw-bold"><i class="fas fa-chart-area me-2"></i>Trend (Gross / Net / Profit)</div>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="110"></canvas>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="hub-card h-100">
                <div class="card-header bg-light">
                    <div class="fw-bold"><i class="fas fa-percentage me-2"></i>Fee Breakdown</div>
                </div>
                <div class="card-body">
                    <canvas id="feeChart" height="220"></canvas>
                    <div class="text-muted small mt-2">
                        Admin: {{ $fmt($summary['fee_commission'] ?? 0) }} •
                        Layanan: {{ $fmt($summary['fee_service'] ?? 0) }} •
                        Proses: {{ $fmt($summary['fee_transaction'] ?? 0) }} •
                        Lainnya: {{ $fmt($summary['fee_other'] ?? 0) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Top products --}}
    <div class="row g-3 mb-3">
        <div class="col-12 col-xl-7">
            <div class="hub-card">
                <div class="card-header bg-light">
                    <div class="fw-bold"><i class="fas fa-trophy me-2"></i>Top Products by Profit (alokasi)</div>
                </div>
                <div class="card-body">
                    <canvas id="topProductsChart" height="160"></canvas>
                    <div class="text-muted small mt-2">
                        Catatan: Untuk order multi-produk, Net dialokasikan proporsional berdasarkan gross line item.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="hub-card h-100">
                <div class="card-header bg-light">
                    <div class="fw-bold"><i class="fas fa-list me-2"></i>Ringkasan Produk</div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Net</th>
                                <th class="text-end">COGS</th>
                                <th class="text-end">Profit</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($topProductsTable as $r)
                                <tr>
                                    <td class="text-truncate" style="max-width:240px;">{{ $r['name'] }}</td>
                                    <td class="text-end">{{ number_format($r['qty'] ?? 0) }}</td>
                                    <td class="text-end">{{ $fmt($r['net'] ?? 0) }}</td>
                                    <td class="text-end">{{ $fmt($r['cogs'] ?? 0) }}</td>
                                    <td class="text-end fw-bold {{ ($r['profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $fmt($r['profit'] ?? 0) }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted text-center py-3">Belum ada data.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Orders table --}}
    <div class="hub-card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="fw-bold"><i class="fas fa-receipt me-2"></i>Orders (detail)</div>
            <div class="text-muted small">
                Menampilkan {{ $orders->count() }} dari total {{ $orders->total() }} orders.
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Order</th>
                        <th>Jenis</th>
                        <th>Status</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">Fee</th>
                        <th class="text-end">Net</th>
                        <th class="text-end">COGS</th>
                        <th class="text-end">Profit</th>
                        <th class="text-end">Margin</th>
                        <th class="text-end">Take Rate</th>
                        <th>Cost Data</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($orders as $o)
                        @php
                            $m = $orderRows[$o->id] ?? null;
                            $profit = $m['profit'] ?? 0;
                            $missing = !empty($m['missing_cost']);
                        @endphp
                        <tr>
                            <td class="text-nowrap">{{ optional($o->order_date)->format('d M Y') }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('orders.show', $o) }}" class="fw-semibold">
                                    {{ $o->order_number }}
                                </a>
                            </td>
                            <td class="text-nowrap">{{ $o->jenis_transaksi ?? '-' }}</td>
                            <td><span class="badge bg-secondary">{{ $o->status }}</span></td>

                            <td class="text-end">{{ $fmt($m['gross'] ?? 0) }}</td>
                            <td class="text-end">{{ $fmt(($m['fees']['total'] ?? 0)) }}</td>
                            <td class="text-end">{{ $fmt($m['net'] ?? 0) }}</td>
                            <td class="text-end">{{ $fmt($m['cogs'] ?? 0) }}</td>

                            <td class="text-end fw-bold {{ $profit >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $fmt($profit) }}
                            </td>
                            <td class="text-end">{{ number_format($m['margin_pct'] ?? 0, 1) }}%</td>
                            <td class="text-end">{{ number_format($m['take_rate_pct'] ?? 0, 1) }}%</td>

                            <td class="text-nowrap">
                                @if($missing)
                                    <span class="badge bg-warning text-dark" title="Ada item yang belum punya HPP/Packaging atau % tapi harga 0">Missing</span>
                                @else
                                    <span class="badge bg-success">OK</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="text-muted text-center py-4">Belum ada order untuk filter ini.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3">
                <div class="hub-pagination">
                    <span class="hub-pagination-info">
                        @if($orders->total())
                            Menampilkan {{ $orders->firstItem() }}–{{ $orders->lastItem() }} dari {{ $orders->total() }} data
                        @endif
                    </span>
                    {{ $orders->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
  const chartData = @json($chart);

  // Trend
  const ctxTrend = document.getElementById('trendChart');
  if (ctxTrend) {
    new Chart(ctxTrend, {
      type: 'line',
      data: {
        labels: chartData.labels || [],
        datasets: [
          { label: 'Gross', data: chartData.gross || [], tension: 0.25 },
          { label: 'Net', data: chartData.net || [], tension: 0.25 },
          { label: 'Profit', data: chartData.profit || [], tension: 0.25 },
        ]
      },
      options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'bottom' } },
        scales: {
          y: { ticks: { callback: (v) => new Intl.NumberFormat('id-ID').format(v) } }
        }
      }
    });
  }

  // Fee breakdown
  const ctxFee = document.getElementById('feeChart');
  if (ctxFee) {
    const f = chartData.fee || {};
    new Chart(ctxFee, {
      type: 'doughnut',
      data: {
        labels: ['Admin', 'Layanan', 'Proses', 'Lainnya'],
        datasets: [{
          data: [f.commission || 0, f.service || 0, f.transaction || 0, f.other || 0],
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
      }
    });
  }

  // Top products
  const ctxTop = document.getElementById('topProductsChart');
  if (ctxTop) {
    const t = chartData.top || {};
    new Chart(ctxTop, {
      type: 'bar',
      data: {
        labels: t.labels || [],
        datasets: [
          { label: 'Profit', data: t.profit || [] },
          { label: 'Net', data: t.net || [] },
          { label: 'COGS', data: t.cogs || [] },
        ]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: {
          y: { ticks: { callback: (v) => new Intl.NumberFormat('id-ID').format(v) } }
        }
      }
    });
  }
})();
</script>
@endpush
