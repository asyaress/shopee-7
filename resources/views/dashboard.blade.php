@extends('layouts.hub')

@section('title', 'Dashboard - Toedjoe Order System')

@section('content')
<div class="report-shell">
    @include('hub.partials.page-hero', [
        'icon' => 'fa-tachometer-alt',
        'title' => 'Dashboard Operasional',
        'subtitle' => 'Ringkasan pesanan & analitik toko',
        'meta' => [['icon' => 'fa-calendar', 'text' => date('d M Y')]],
        'actions' => '<a href="' . route('monitoring.index') . '" class="hub-btn hub-btn-outline" style="color:#fff;border-color:rgba(255,255,255,.5)"><i class="fas fa-chart-line"></i> Monitoring</a>',
    ])

    <div class="report-kpi-hero">
        <div class="report-kpi-card"><div class="label">Total Pesanan</div><div class="value">{{ hub_num($stats['total_orders']) }}</div></div>
        <div class="report-kpi-card"><div class="label">Hari Ini</div><div class="value">{{ hub_num($stats['today_orders']) }}</div></div>
        <div class="report-kpi-card warn"><div class="label">Sedang Proses</div><div class="value">{{ hub_num($stats['pending_orders']) }}</div></div>
        <div class="report-kpi-card"><div class="label">Customer</div><div class="value">{{ hub_num($stats['total_customers']) }}</div></div>
    </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="hub-card">
                    <div class="hub-card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Quick Actions
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <a href="{{ route('orders.create') }}" class="hub-btn hub-btn-primary w-100 py-3 quick-action-btn">
                                    <i class="fas fa-plus-circle me-2"></i>
                                    <div>Tambah Pesanan Baru</div>
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="{{ route('customers.create') }}"
                                    class="hub-btn hub-btn-outline w-100 py-3 quick-action-btn">
                                    <i class="fas fa-user-plus me-2"></i>
                                    <div>Tambah Customer</div>
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="{{ route('orders.index') }}" class="hub-btn hub-btn-primary w-100 py-3 quick-action-btn">
                                    <i class="fas fa-list me-2"></i>
                                    <div>Lihat Semua Pesanan</div>
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <button class="btn btn-outline-secondary w-100 py-3 quick-action-btn"
                                    onclick="exportToday()">
                                    <i class="fas fa-download me-2"></i>
                                    <div>Export Hari Ini</div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="hub-card">
                    <div class="hub-card-header filter-header">
                        <h5 class="mb-0">
                            <i class="fas fa-filter me-2"></i>Filter Analisis
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-2">
                                <label class="form-label">Tahun</label>
                                <select class="form-select" id="yearFilter">
                                    @for($year = date('Y'); $year >= 2020; $year--)
                                        <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>{{ $year }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Bulan</label>
                                <select class="form-select" id="monthFilter">
                                    <option value="">Semua Bulan</option>
                                    @for($month = 1; $month <= 12; $month++)
                                        <option value="{{ $month }}">
                                            {{ DateTime::createFromFormat('!m', $month)->format('F') }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Tanggal Mulai</label>
                                <input type="date" class="form-control" id="startDateFilter">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Tanggal Akhir</label>
                                <input type="date" class="form-control" id="endDateFilter">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Jenis Transaksi</label>
                                <select class="form-select" id="transactionTypeFilter">
                                    <option value="All">Semua</option>
                                    <option value="Website">Website</option>
                                    <option value="Shopee">Shopee</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid gap-2">
                                    <button class="hub-btn hub-btn-primary" onclick="applyFilters()">
                                        <i class="fas fa-search me-1"></i>Filter
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="resetFilters()">
                                        <i class="fas fa-refresh me-1"></i>Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-info" role="alert">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Tips:</strong> Gunakan filter tanggal untuk range khusus, atau kombinasi
                                    tahun/bulan untuk periode tertentu.
                                    Filter tanggal akan mengabaikan filter tahun/bulan.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row mb-4">
            <!-- Orders Chart -->
            <div class="col-lg-8 mb-4">
                <div class="hub-card">
                    <div class="hub-card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>Grafik Pesanan
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="ordersChart" height="300"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top Products -->
            <div class="col-lg-4 mb-4">
                <div class="hub-card">
                    <div class="hub-card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-box me-2"></i>Produk Terlaris
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="productsChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analysis Charts -->
        <div class="row mb-4">
            <!-- Weekly Analysis -->
            <div class="col-lg-4 mb-4">
                <div class="hub-card">
                    <div class="hub-card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-week me-2"></i>Analisis Harian
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="weeklyChart" height="300"></canvas>
                    </div>
                </div>
            </div>

            <!-- Weekly in Month Analysis -->
            <div class="col-lg-4 mb-4">
                <div class="hub-card">
                    <div class="hub-card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>Analisis Mingguan
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="weeklyInMonthChart" height="300"></canvas>
                    </div>
                </div>
            </div>

            <!-- Monthly Analysis -->
            <div class="col-lg-4 mb-4">
                <div class="hub-card">
                    <div class="hub-card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar me-2"></i>Analisis Bulanan
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Customers -->
        <div class="row mb-4">
            <div class="col-lg-12">
                <div class="hub-card">
                    <div class="hub-card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-star me-2"></i>Top Customer
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @forelse($topCustomers as $customer)
                                <div class="col-md-4 mb-3">
                                    <div class="d-flex align-items-center p-3 rounded top-customer-card">
                                        <div class="avatar me-3">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                                style="width: 50px; height: 50px; background: var(--accent-green); color: white;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">{{ $customer->customer_name ?? $customer->name }}</h6>
                                            <small class="text-muted">
                                                {{ $customer->orders_count }} pesanan
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge badge-green">
                                                #{{ $loop->iteration }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="text-center py-4">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Belum ada customer</p>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="row">
            <div class="col-12">
                <div class="hub-card">
                    <div class="hub-card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>Pesanan Terbaru
                        </h5>
                        <a href="{{ route('orders.index') }}" class="btn btn-sm"
                            style="background: rgba(255,255,255,0.2); color: white; border-radius: 20px;">
                            Lihat Semua
                        </a>
                    </div>
                    <div class="card-body p-0">
                        @if($recentOrders->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead style="background: var(--light-red); color: var(--text-dark);">
                                        <tr>
                                            <th class="border-0 ps-4">No. Pesanan</th>
                                            <th class="border-0">Customer</th>
                                            <th class="border-0">Produk</th>
                                            <th class="border-0">Qty</th>
                                            <th class="border-0">Status</th>
                                            <th class="border-0">Tanggal</th>
                                            <th class="border-0">Pengiriman</th>
                                            <th class="border-0">Jenis Transaksi</th>
                                            <th class="border-0 pe-4">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentOrders as $order)
                                            <tr>
                                                <td class="ps-4">
                                                    <strong style="color: var(--primary-red);">{{ $order->order_number }}</strong>
                                                </td>
                                                <td>
                                                    <div class="fw-bold">{{ $order->customer_name }}</div>
                                                </td>
                                                <td>
                                                    @if($order->orderItems->count())
                                                        <ul class="mb-0 ps-3">
                                                            @foreach($order->orderItems as $item)
                                                                <li>
                                                                    <strong>{{ $item->product_name }}</strong>
                                                                    <span class="text-muted small">x{{ $item->quantity }}</span>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        {{ $order->orderItems->sum('quantity') }} pcs
                                                    </span>
                                                </td>
                                                <td>
                                                    @php
                                                        $statusColors = [
                                                            'pending' => 'warning',
                                                            'in_progress' => 'info',
                                                            'completed' => 'success',
                                                            'cancelled' => 'danger'
                                                        ];
                                                    @endphp
                                                    <span class="badge bg-{{ $statusColors[$order->status] ?? 'secondary' }}">
                                                        {{ $order->status_indonesian ?? ucfirst($order->status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div>{{ \Carbon\Carbon::parse($order->order_date)->format('d M Y') }}</div>
                                                    <small
                                                        class="text-muted">{{ \Carbon\Carbon::parse($order->order_date)->diffForHumans() }}</small>
                                                </td>
                                                <td>
                                                    @if($order->jenis_pengiriman)
                                                        <span class="badge bg-info">{{ $order->jenis_pengiriman }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
<td>
    @if($order->jenis_transaksi)
        <span class="badge"
            style="background: {{ $order->jenis_transaksi === 'Shopee' ? 'var(--maroon-600)' : 'var(--maroon-400)' }}; color: #fff;">
            {{ $order->jenis_transaksi }}
        </span>
    @else
        <span class="text-muted">-</span>
    @endif
</td>
                                                <td class="pe-4">
                                                    @if($order->total_amount)
                                                        <div class="fw-bold" style="color: var(--primary-red);">
                                                            Rp {{ number_format($order->total_amount, 0, ',', '.') }}
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">Belum ada pesanan</h5>
                                <p class="text-muted">Mulai dengan menambah pesanan pertama!</p>
                                <a href="{{ route('orders.create') }}" class="hub-btn hub-btn-primary">
                                    <i class="fas fa-plus me-2"></i>Tambah Pesanan
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let ordersChartInstance = null;
        let productsChartInstance = null;
        let weeklyChartInstance = null;
        let weeklyInMonthChartInstance = null;
        let monthlyChartInstance = null;

        function renderOrdersChart(chartData, chartType = 'monthly', meta = {}) {
            const ctx = document.getElementById('ordersChart').getContext('2d');
            let labels = [];
            let data = [];
            let chartTitle = 'Grafik Pesanan';

            switch (chartType) {
                case 'monthly':
                    // Data bulanan dalam tahun
                    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    for (let i = 1; i <= 12; i++) {
                        labels.push(monthNames[i - 1]);
                        data.push(chartData[i] || 0);
                    }
                    chartTitle = `Grafik Pesanan Bulanan ${meta.year || ''}`;
                    break;

                case 'daily_in_month':
                    // Data harian dalam bulan
                    const monthName = meta.month ? new Date(meta.year, meta.month - 1).toLocaleString('id-ID', { month: 'long' }) : '';
                    Object.keys(chartData).forEach(day => {
                        labels.push(day);
                        data.push(chartData[day]);
                    });
                    chartTitle = `Grafik Pesanan Harian - ${monthName} ${meta.year || ''}`;
                    break;

                case 'daily':
                    // Data harian dalam range tanggal
                    Object.keys(chartData).forEach(date => {
                        labels.push(date);
                        data.push(chartData[date]);
                    });
                    chartTitle = `Grafik Pesanan Harian (${meta.startDate} s/d ${meta.endDate})`;
                    break;

                case 'weekly':
                    // Data mingguan
                    Object.keys(chartData).forEach(week => {
                        labels.push(week);
                        data.push(chartData[week]);
                    });
                    chartTitle = `Grafik Pesanan Mingguan (${meta.startDate} s/d ${meta.endDate})`;
                    break;

                case 'monthly_range':
                    // Data bulanan dalam range panjang
                    Object.keys(chartData).forEach(month => {
                        labels.push(month);
                        data.push(chartData[month]);
                    });
                    chartTitle = `Grafik Pesanan Bulanan (${meta.startDate} s/d ${meta.endDate})`;
                    break;

                default:
                    // Fallback ke monthly
                    const defaultMonthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    for (let i = 1; i <= 12; i++) {
                        labels.push(defaultMonthNames[i - 1]);
                        data.push(chartData[i] || 0);
                    }
                    break;
            }

            if (ordersChartInstance) ordersChartInstance.destroy();

            ordersChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Jumlah Pesanan',
                        data: data,
                        borderColor: '#9a2542',
                        backgroundColor: 'rgba(154, 37, 66, 0.12)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#9a2542',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: chartTitle,
                            font: {
                                size: 14,
                                weight: 'bold'
                            },
                            color: '#374151',
                            padding: {
                                bottom: 20
                            }
                        },
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(154, 37, 66, 0.9)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            callbacks: {
                                title: function (context) {
                                    return context[0].label;
                                },
                                label: function (context) {
                                    return `Pesanan: ${context.parsed.y}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 },
                            grid: {
                                color: 'rgba(154, 37, 66, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                maxRotation: chartType === 'daily' ? 45 : 0,
                                minRotation: 0
                            }
                        }
                    }
                }
            });
        }

        function renderProductsChart(productData) {
            const ctx = document.getElementById('productsChart').getContext('2d');

            // Check if productData is empty or null
            if (!productData || productData.length === 0) {
                if (productsChartInstance) productsChartInstance.destroy();

                // Show empty state
                ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
                ctx.font = '16px Arial';
                ctx.fillStyle = '#6b7280';
                ctx.textAlign = 'center';
                ctx.fillText('Tidak ada data produk', ctx.canvas.width / 2, ctx.canvas.height / 2);
                return;
            }

            const labels = productData.map(item => item.product_name);
            const data = productData.map(item => parseInt(item.total_sold));

            // Green color palette for products
            const colors = [
                '#9a2542', '#7f1d35', '#b83256', '#d14a6f', '#6b1528',
                '#4a0e1a', '#c45a7a', '#e8a0b4', '#fce8ee', '#d9779a'
            ];

            if (productsChartInstance) productsChartInstance.destroy();

            productsChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors.slice(0, data.length),
                        borderColor: '#fff',
                        borderWidth: 2,
                        hoverBackgroundColor: colors.map(color => color + 'CC'),
                        hoverBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                usePointStyle: true,
                                font: {
                                    size: 11
                                },
                                generateLabels: function (chart) {
                                    const data = chart.data;
                                    if (data.labels.length && data.datasets.length) {
                                        return data.labels.map((label, i) => {
                                            const dataset = data.datasets[0];
                                            const value = dataset.data[i];
                                            return {
                                                text: `${label} (${value})`,
                                                fillStyle: dataset.backgroundColor[i],
                                                strokeStyle: dataset.borderColor,
                                                lineWidth: dataset.borderWidth,
                                                pointStyle: 'circle',
                                                hidden: false,
                                                index: i
                                            };
                                        });
                                    }
                                    return [];
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(154, 37, 66, 0.9)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            callbacks: {
                                label: function (context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    return `${label}: ${value} terjual`;
                                }
                            }
                        }
                    }
                }
            });
        }

        function renderWeeklyChart(weeklyData) {
            const ctx = document.getElementById('weeklyChart').getContext('2d');

            const labels = Object.keys(weeklyData);
            const data = Object.values(weeklyData);

            if (weeklyChartInstance) weeklyChartInstance.destroy();
            weeklyChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Jumlah Pesanan',
                        data: data,
                        backgroundColor: 'rgba(154, 37, 66, 0.75)',
                        borderColor: '#9a2542',
                        borderWidth: 1,
                        hoverBackgroundColor: 'rgba(154, 37, 66, 1)',
                        hoverBorderColor: '#7f1d35'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(154, 37, 66, 0.9)',
                            titleColor: '#fff',
                            bodyColor: '#fff'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 },
                            grid: {
                                color: 'rgba(154, 37, 66, 0.15)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        function renderWeeklyInMonthChart(weeklyInMonthData) {
            const ctx = document.getElementById('weeklyInMonthChart').getContext('2d');

            const labels = Object.keys(weeklyInMonthData);
            const data = Object.values(weeklyInMonthData);

            if (weeklyInMonthChartInstance) weeklyInMonthChartInstance.destroy();
            weeklyInMonthChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Jumlah Pesanan',
                        data: data,
                        backgroundColor: 'rgba(154, 37, 66, 0.75)',
                        borderColor: '#9a2542',
                        borderWidth: 1,
                        hoverBackgroundColor: 'rgba(154, 37, 66, 1)',
                        hoverBorderColor: '#7f1d35'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(154, 37, 66, 0.9)',
                            titleColor: '#fff',
                            bodyColor: '#fff'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 },
                            grid: {
                                color: 'rgba(154, 37, 66, 0.15)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        function renderMonthlyChart(monthlyData) {
            const ctx = document.getElementById('monthlyChart').getContext('2d');

            const labels = Object.keys(monthlyData);
            const data = Object.values(monthlyData);

            if (monthlyChartInstance) monthlyChartInstance.destroy();
            monthlyChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Jumlah Pesanan',
                        data: data,
                        backgroundColor: 'rgba(154, 37, 66, 0.75)',
                        borderColor: '#9a2542',
                        borderWidth: 1,
                        hoverBackgroundColor: 'rgba(154, 37, 66, 1)',
                        hoverBorderColor: '#7f1d35'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(154, 37, 66, 0.9)',
                            titleColor: '#fff',
                            bodyColor: '#fff'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 },
                            grid: {
                                color: 'rgba(154, 37, 66, 0.15)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        function applyFilters() {
            const year = document.getElementById('yearFilter').value;
            const month = document.getElementById('monthFilter').value;
            const startDate = document.getElementById('startDateFilter').value;
            const endDate = document.getElementById('endDateFilter').value;
            const transactionType = document.getElementById('transactionTypeFilter').value;

            // Validasi filter tanggal
            if (startDate && !endDate) {
                alert('Mohon pilih tanggal akhir juga');
                return;
            }
            if (!startDate && endDate) {
                alert('Mohon pilih tanggal mulai juga');
                return;
            }
            if (startDate && endDate && startDate > endDate) {
                alert('Tanggal mulai tidak boleh lebih besar dari tanggal akhir');
                return;
            }

            // Update orders chart dengan parameter lengkap
            let chartParams = `year=${year}&jenis_transaksi=${transactionType}`;
            if (startDate && endDate) {
                chartParams += `&start_date=${startDate}&end_date=${endDate}`;
            } else if (month) {
                chartParams += `&month=${month}`;
            }

            fetch(`{{ route('dashboard.chart-data') }}?${chartParams}`)
                .then(response => response.json())
                .then(res => {
                    // Menggunakan chartType dan meta dari response
                    renderOrdersChart(res.chartData, res.chartType, res.meta);
                })
                .catch(error => {
                    console.error('Error fetching orders chart data:', error);
                });

            // Update products chart dengan filter yang sama
            let productParams = `year=${year}&jenis_transaksi=${transactionType}`;
            if (startDate && endDate) {
                productParams += `&start_date=${startDate}&end_date=${endDate}`;
            } else if (month) {
                productParams += `&month=${month}`;
            }

            fetch(`{{ route('dashboard.product-chart-data') }}?${productParams}`)
                .then(response => response.json())
                .then(res => {
                    renderProductsChart(res.productData);
                })
                .catch(error => {
                    console.error('Error fetching products chart data:', error);
                });

            // Update weekly analysis
            let weeklyParams = `year=${year}`;
            if (startDate && endDate) {
                weeklyParams += `&start_date=${startDate}&end_date=${endDate}`;
            } else if (month) {
                weeklyParams += `&month=${month}`;
            }

            fetch(`{{ route('dashboard.weekly-analysis') }}?${weeklyParams}`)
                .then(response => response.json())
                .then(res => {
                    renderWeeklyChart(res.weeklyData);
                })
                .catch(error => {
                    console.error('Error fetching weekly analysis data:', error);
                });

            // Update weekly in month analysis (only if month is selected or date range)
            if (month || (startDate && endDate)) {
                let weeklyInMonthParams = `year=${year}`;
                if (startDate && endDate) {
                    weeklyInMonthParams += `&start_date=${startDate}&end_date=${endDate}`;
                } else if (month) {
                    weeklyInMonthParams += `&month=${month}`;
                }

                fetch(`{{ route('dashboard.weekly-in-month-analysis') }}?${weeklyInMonthParams}`)
                    .then(response => response.json())
                    .then(res => {
                        renderWeeklyInMonthChart(res.weeklyInMonthData);
                    })
                    .catch(error => {
                        console.error('Error fetching weekly in month analysis data:', error);
                    });
            }

            // Update monthly analysis
            let monthlyParams = `year=${year}`;
            if (startDate && endDate) {
                monthlyParams += `&start_date=${startDate}&end_date=${endDate}`;
            }

            fetch(`{{ route('dashboard.monthly-analysis') }}?${monthlyParams}`)
                .then(response => response.json())
                .then(res => {
                    renderMonthlyChart(res.monthlyData);
                })
                .catch(error => {
                    console.error('Error fetching monthly analysis data:', error);
                });
        }

        function resetFilters() {
            // Reset semua filter ke nilai default
            document.getElementById('yearFilter').value = new Date().getFullYear();
            document.getElementById('monthFilter').value = '';
            document.getElementById('startDateFilter').value = '';
            document.getElementById('endDateFilter').value = '';
            document.getElementById('transactionTypeFilter').value = 'All';

            // Terapkan filter default
            applyFilters();
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Initial charts render dengan format yang konsisten
            const initialMeta = {
                year: new Date().getFullYear(),
                month: null,
                startDate: null,
                endDate: null
            };

            // Render grafik utama dengan data initial (monthly)
            renderOrdersChart(@json($chartData), 'monthly', initialMeta);
            renderProductsChart(@json($topProducts));
            renderWeeklyChart(@json($weeklyData));
            renderWeeklyInMonthChart(@json($weeklyInMonthData));
            renderMonthlyChart(@json($monthlyData));

            // Add event listeners for automatic filtering
            document.getElementById('startDateFilter').addEventListener('change', function () {
                const startDate = this.value;
                const endDate = document.getElementById('endDateFilter').value;

                if (startDate) {
                    if (endDate && startDate <= endDate) {
                        applyFilters();
                    }
                }
            });

            document.getElementById('endDateFilter').addEventListener('change', function () {
                const endDate = this.value;
                const startDate = document.getElementById('startDateFilter').value;

                if (endDate) {
                    if (startDate && startDate <= endDate) {
                        applyFilters();
                    }
                }
            });

            // Clear date range when year or month is changed
            document.getElementById('yearFilter').addEventListener('change', function () {
                document.getElementById('startDateFilter').value = '';
                document.getElementById('endDateFilter').value = '';
            });

            document.getElementById('monthFilter').addEventListener('change', function () {
                document.getElementById('startDateFilter').value = '';
                document.getElementById('endDateFilter').value = '';
            });
        });

        function exportToday() {
            Swal.fire({
                title: 'Export Pesanan Hari Ini',
                text: 'Fitur ini akan mengekspor semua pesanan hari ini ke Excel',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#7f1d1d',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Export',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // TODO: Implement actual export functionality
                    Swal.fire({
                        title: 'Coming Soon!',
                        text: 'Fitur export akan segera tersedia di update berikutnya',
                        icon: 'success',
                        confirmButtonColor: '#dc2626'
                    });
                }
            });
        }
    </script>
@endpush
