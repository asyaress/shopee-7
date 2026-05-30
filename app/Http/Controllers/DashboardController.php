<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_orders' => Order::count(),
            'today_orders' => Order::today()->count(),
            'pending_orders' => Order::pending()->count(),
            'total_customers' => Customer::count(),
            'total_products' => Product::active()->count(),
            'total_revenue' => Order::where('status', 'completed')->sum('total_amount')
        ];

        $recentOrders = Order::with(['orderItems', 'orderItems.product'])
            ->latest()
            ->limit(10)
            ->get();

        // Versi string-based, tanpa relasi ke tabel customer:
        $topCustomers = Order::selectRaw('customer_name, COUNT(*) as orders_count')
            ->groupBy('customer_name')
            ->orderByDesc('orders_count')
            ->limit(5)
            ->get();

        // Data untuk grafik orders bulanan (format baru)
        $rawChartData = Order::selectRaw('MONTH(order_date) as month, COUNT(*) as count')
            ->whereYear('order_date', date('Y'))
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        // Fill missing months
        $chartData = [];
        for ($i = 1; $i <= 12; $i++) {
            $chartData[$i] = $rawChartData[$i] ?? 0;
        }

        // Data untuk grafik produk terlaris
        $topProducts = OrderItem::selectRaw('product_name, SUM(quantity) as total_sold')
            ->groupBy('product_name')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();

        // Data analisis hari dalam seminggu
        $weeklyData = $this->getWeeklyAnalysis();

        // Data analisis minggu dalam bulan
        $weeklyInMonthData = $this->getWeeklyInMonthAnalysis();

        // Data analisis bulanan dalam tahun
        $monthlyData = $this->getMonthlyAnalysis();

        return view('dashboard', compact(
            'stats',
            'recentOrders',
            'topCustomers',
            'chartData',
            'topProducts',
            'weeklyData',
            'weeklyInMonthData',
            'monthlyData'
        ));
    }

    public function chartData(Request $request)
    {
        $jenis = $request->jenis_transaksi;
        $year = $request->year ?? date('Y');
        $month = $request->month;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $order = Order::query();

        // Filter berdasarkan jenis transaksi
        if ($jenis && $jenis != 'All') {
            $order->where('jenis_transaksi', $jenis);
        }

        $chartType = 'monthly'; // default
        $data = [];

        // Filter berdasarkan tanggal range
        if ($startDate && $endDate) {
            $order->whereBetween('order_date', [$startDate, $endDate]);

            // Hitung selisih hari
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            $daysDiff = $start->diffInDays($end) + 1;

            if ($daysDiff <= 31) {
                // Tampilkan per hari untuk range <= 31 hari
                $chartType = 'daily';
                $data = $order->selectRaw('DATE(order_date) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [Carbon::parse($item->date)->format('d M') => $item->count];
                    })
                    ->toArray();

                // Pastikan semua tanggal dalam range ada (fill missing dates with 0)
                $current = $start->copy();
                $completeData = [];
                while ($current <= $end) {
                    $label = $current->format('d M');
                    $completeData[$label] = $data[$label] ?? 0;
                    $current->addDay();
                }
                $data = $completeData;

            } elseif ($daysDiff <= 180) { // <= 6 bulan
                // Tampilkan per minggu
                $chartType = 'weekly';
                $data = $order->selectRaw('YEARWEEK(order_date) as week, COUNT(*) as count')
                    ->groupBy('week')
                    ->orderBy('week')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        $year = substr($item->week, 0, 4);
                        $week = substr($item->week, 4, 2);
                        return ["Week {$week}/{$year}" => $item->count];
                    })
                    ->toArray();
            } else {
                // Tampilkan per bulan untuk range panjang
                $chartType = 'monthly_range';
                $data = $order->selectRaw('DATE_FORMAT(order_date, "%Y-%m") as month, COUNT(*) as count')
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [Carbon::parse($item->month . '-01')->format('M Y') => $item->count];
                    })
                    ->toArray();
            }

        } elseif ($month) {
            $order->whereYear('order_date', $year)
                ->whereMonth('order_date', $month);

            // Tampilkan data per hari dalam bulan
            $chartType = 'daily_in_month';
            $rawData = $order->selectRaw('DAY(order_date) as day, COUNT(*) as count')
                ->groupBy('day')
                ->pluck('count', 'day')
                ->toArray();

            // Fill missing days in month
            $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
            $data = [];
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $data[$i] = $rawData[$i] ?? 0;
            }

        } else {
            $order->whereYear('order_date', $year);

            // Tampilkan data per bulan dalam tahun
            $chartType = 'monthly';
            $rawData = $order->selectRaw('MONTH(order_date) as month, COUNT(*) as count')
                ->groupBy('month')
                ->pluck('count', 'month')
                ->toArray();

            // Fill missing months
            $data = [];
            for ($i = 1; $i <= 12; $i++) {
                $data[$i] = $rawData[$i] ?? 0;
            }
        }

        return response()->json([
            'chartData' => $data,
            'chartType' => $chartType,
            'meta' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'year' => $year,
                'month' => $month
            ]
        ]);
    }

    public function productChartData(Request $request)
    {
        $year = $request->year ?? date('Y');
        $month = $request->month;
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $jenis = $request->jenis_transaksi;

        $query = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id');

        // Filter berdasarkan jenis transaksi
        if ($jenis && $jenis != 'All') {
            $query->where('orders.jenis_transaksi', $jenis);
        }

        // Filter berdasarkan tanggal range
        if ($startDate && $endDate) {
            $query->whereBetween('orders.order_date', [$startDate, $endDate]);
        } elseif ($month) {
            $query->whereYear('orders.order_date', $year)
                ->whereMonth('orders.order_date', $month);
        } else {
            $query->whereYear('orders.order_date', $year);
        }

        $data = $query->selectRaw('order_items.product_name, SUM(order_items.quantity) as total_sold')
            ->groupBy('order_items.product_name')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->get();

        return response()->json(['productData' => $data]);
    }

    public function weeklyAnalysis(Request $request)
    {
        $year = $request->year ?? date('Y');
        $month = $request->month;
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $data = $this->getWeeklyAnalysis($year, $month, $startDate, $endDate);

        return response()->json(['weeklyData' => $data]);
    }

    public function weeklyInMonthAnalysis(Request $request)
    {
        $year = $request->year ?? date('Y');
        $month = $request->month ?? date('m');
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $data = $this->getWeeklyInMonthAnalysis($year, $month, $startDate, $endDate);

        return response()->json(['weeklyInMonthData' => $data]);
    }

    public function monthlyAnalysis(Request $request)
    {
        $year = $request->year ?? date('Y');
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $data = $this->getMonthlyAnalysis($year, $startDate, $endDate);

        return response()->json(['monthlyData' => $data]);
    }

    private function getWeeklyAnalysis($year = null, $month = null, $startDate = null, $endDate = null)
    {
        $query = Order::query();

        if ($startDate && $endDate) {
            $query->whereBetween('order_date', [$startDate, $endDate]);
        } elseif ($year) {
            $query->whereYear('order_date', $year);
            if ($month) {
                $query->whereMonth('order_date', $month);
            }
        }

        return $query->selectRaw('DAYOFWEEK(order_date) as day_of_week, COUNT(*) as count')
            ->groupBy('day_of_week')
            ->orderBy('day_of_week')
            ->get()
            ->mapWithKeys(function ($item) {
                $days = ['', 'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                return [$days[$item->day_of_week] => $item->count];
            });
    }

    private function getWeeklyInMonthAnalysis($year = null, $month = null, $startDate = null, $endDate = null)
    {
        $query = Order::query();

        if ($startDate && $endDate) {
            $query->whereBetween('order_date', [$startDate, $endDate]);
        } elseif ($year) {
            $query->whereYear('order_date', $year);
            if ($month) {
                $query->whereMonth('order_date', $month);
            }
        }

        return $query->selectRaw('WEEK(order_date, 1) - WEEK(DATE_SUB(order_date, INTERVAL DAYOFMONTH(order_date) - 1 DAY), 1) + 1 as week_of_month, COUNT(*) as count')
            ->groupBy('week_of_month')
            ->orderBy('week_of_month')
            ->get()
            ->mapWithKeys(function ($item) {
                return ["Minggu ke-{$item->week_of_month}" => $item->count];
            });
    }

    private function getMonthlyAnalysis($year = null, $startDate = null, $endDate = null)
    {
        $query = Order::query();

        if ($startDate && $endDate) {
            $query->whereBetween('order_date', [$startDate, $endDate]);
        } elseif ($year) {
            $query->whereYear('order_date', $year);
        }

        return $query->selectRaw('MONTH(order_date) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->mapWithKeys(function ($item) {
                $months = [
                    '',
                    'Januari',
                    'Februari',
                    'Maret',
                    'April',
                    'Mei',
                    'Juni',
                    'Juli',
                    'Agustus',
                    'September',
                    'Oktober',
                    'November',
                    'Desember'
                ];
                return [$months[$item->month] => $item->count];
            });
    }
}
