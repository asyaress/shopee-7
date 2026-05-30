<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OrdersExport;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        Log::info('[ORDER][INDEX] Masuk ke index', $request->all());

        $query = Order::with('orderItems.product');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
            Log::info("[ORDER][INDEX] Filter status: {$request->status}");
        }

        if ($request->filled('customer_name')) {
            $query->where('customer_name', 'like', "%{$request->customer_name}%");
            Log::info("[ORDER][INDEX] Filter customer_name: {$request->customer_name}");
        }

        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
            Log::info("[ORDER][INDEX] Filter date_from: {$request->date_from}");
        }
        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
            Log::info("[ORDER][INDEX] Filter date_to: {$request->date_to}");
        }

        if ($request->filled('search')) {
            $search = $request->search;
            Log::info("[ORDER][INDEX] Pencarian: $search");
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('orderItems', function ($itemQuery) use ($search) {
                        $itemQuery->where('product_name', 'like', "%{$search}%");
                    })
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        // Urutkan berdasarkan id DESC (terbaru di atas)
$orders = $query
    ->orderByRaw("CASE WHEN jenis_transaksi = 'Shopee' THEN 0 ELSE 1 END")
    ->orderByDesc('order_date')
    ->orderByDesc('id')
    ->get();

        $customers = Customer::active()->get();

        Log::info('[ORDER][INDEX] Data berhasil di-load');
        return view('orders.index', compact('orders', 'customers'));
    }

    public function create()
    {
        Log::info('[ORDER][CREATE] Masuk ke form create');
        $customers = Customer::active()->get();
        $products = Product::active()->get();

        return view('orders.create', compact('customers', 'products'));
    }

    public function store(Request $request)
    {
        Log::info('[ORDER][STORE] Masuk ke store', $request->all());

        // Convert customer_id to customer_name
        if ($request->has('customer_id') && !$request->has('customer_name')) {
            $customer = Customer::find($request->customer_id);
            $request->merge(['customer_name' => $customer ? $customer->name : '']);
        }

        $request->merge(['items' => $this->buildOrderItemsArray($request)]);

        try {
            $validated = $request->validate([
                'order_number' => 'required|string|max:255|unique:orders,order_number',
                'customer_id' => 'nullable|exists:customers,id',
                'customer_name' => 'required|string|max:255',
                'order_date' => 'required|date',
                'completion_date' => 'required|date|after_or_equal:order_date',
                'jenis_pengiriman' => 'required|string|max:50',
                'jenis_transaksi' => 'required|in:Shopee,Website',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'nullable|exists:products,id',
                'items.*.product_name' => 'required|string|max:255',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'nullable|numeric|min:0',
            ]);
            Log::info('[ORDER][STORE] Validasi sukses');
        } catch (\Exception $e) {
            Log::error('[ORDER][STORE][VALIDATION ERROR]', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        DB::beginTransaction();
        try {
            $order = Order::create([
                'customer_id' => $request->customer_id,
                'order_number' => $request->order_number,
                'customer_name' => $request->customer_name,
                'order_date' => $request->order_date,
                'completion_date' => $request->completion_date,
                'jenis_pengiriman' => $request->jenis_pengiriman,
                'jenis_transaksi' => $request->jenis_transaksi,
                'status' => 'pending',
                'notes' => $request->notes,
            ]);
            Log::info('[ORDER][STORE] Order berhasil dibuat', ['order_id' => $order->id]);

            $totalAmount = 0;
            foreach ($request->items as $item) {
                $itemTotal = ($item['price'] ?? 0) * $item['quantity'];
                $totalAmount += $itemTotal;

                $order->orderItems()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'] ?? null,
                    'total_amount' => $itemTotal > 0 ? $itemTotal : null,
                ]);
                Log::info('[ORDER][STORE] OrderItem created', $item);
            }
            $order->update(['total_amount' => $totalAmount > 0 ? $totalAmount : null]);

            DB::commit();
            Log::info("[ORDER][STORE] Berhasil commit order {$order->order_number}", ['order_id' => $order->id]);
            return redirect()->route('orders.index')
                ->with('success', 'Pesanan berhasil ditambahkan!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[ORDER][STORE][ERROR] Transaction failed', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Terjadi kesalahan saat menambah pesanan: ' . $e->getMessage());
        }
    }

    public function show(Order $order)
    {
        Log::info('[ORDER][SHOW] Show order', ['order_id' => $order->id]);
        $order->load('orderItems.product');
        return view('orders.show', compact('order'));
    }

    public function edit(Order $order)
    {
        if ($order->jenis_transaksi === 'Shopee') {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Order Shopee bersifat read-only karena disync dari API.');
        }
        Log::info('[ORDER][EDIT] Edit order', ['order_id' => $order->id]);
        $customers = Customer::active()->get();
        $products = Product::active()->get();

        return view('orders.edit', compact('order', 'customers', 'products'));
    }

    public function update(Request $request, Order $order)
    {
        if ($order->jenis_transaksi === 'Shopee') {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Order Shopee bersifat read-only karena disync dari API.');
        }
        Log::info('[ORDER][UPDATE] Masuk ke update', ['order_id' => $order->id, 'request' => $request->all()]);

        // Convert customer_id to customer_name (for safety)
        if ($request->has('customer_id') && !$request->has('customer_name')) {
            $customer = Customer::find($request->customer_id);
            $request->merge(['customer_name' => $customer ? $customer->name : '']);
        }

        $request->merge(['items' => $this->buildOrderItemsArray($request)]);

        try {
            $validated = $request->validate([
                'customer_id' => 'nullable|exists:customers,id',
                'customer_name' => 'required|string|max:255',
                'order_date' => 'required|date',
                'completion_date' => 'required|date|after_or_equal:order_date',
                'jenis_pengiriman' => 'required|string|max:50',
                'jenis_transaksi' => 'required|in:Shopee,Website',
                'status' => 'required|in:pending,in_progress,completed,cancelled',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'nullable|exists:products,id',
                'items.*.product_name' => 'required|string|max:255',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'nullable|numeric|min:0',
            ]);
            Log::info('[ORDER][UPDATE] Validasi sukses', ['order_id' => $order->id]);
        } catch (\Exception $e) {
            Log::error('[ORDER][UPDATE][VALIDATION ERROR]', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        DB::beginTransaction();
        try {
            $order->update([
                'customer_id' => $request->customer_id,
                'customer_name' => $request->customer_name,
                'order_date' => $request->order_date,
                'completion_date' => $request->completion_date,
                'jenis_pengiriman' => $request->jenis_pengiriman,
                'jenis_transaksi' => $request->jenis_transaksi,
                'status' => $request->status,
                'notes' => $request->notes,
            ]);
            Log::info('[ORDER][UPDATE] Order updated', ['order_id' => $order->id]);

            $order->orderItems()->delete();
            Log::info('[ORDER][UPDATE] OrderItems deleted', ['order_id' => $order->id]);

            $totalAmount = 0;
            foreach ($request->items as $item) {
                $itemTotal = ($item['price'] ?? 0) * $item['quantity'];
                $totalAmount += $itemTotal;

                $order->orderItems()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'] ?? null,
                    'total_amount' => $itemTotal > 0 ? $itemTotal : null,
                ]);
                Log::info('[ORDER][UPDATE] OrderItem created', $item);
            }
            $order->update(['total_amount' => $totalAmount > 0 ? $totalAmount : null]);

            DB::commit();
            Log::info("[ORDER][UPDATE] Berhasil commit order {$order->order_number}", ['order_id' => $order->id]);
            return redirect()->route('orders.index')
                ->with('success', 'Pesanan berhasil diupdate!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[ORDER][UPDATE][ERROR] Transaction failed', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Terjadi kesalahan saat update pesanan: ' . $e->getMessage());
        }
    }

    public function destroy(Order $order)
    {
        if ($order->jenis_transaksi === 'Shopee') {
            return redirect()->route('orders.show', $order)
                ->with('error', 'Order Shopee tidak bisa dihapus dari panel ini (akan muncul lagi saat sync).');
        }
        Log::info("[ORDER][DESTROY] Masuk ke destroy", ['order_id' => $order->id]);
        DB::beginTransaction();
        try {
            $order->orderItems()->delete();
            $order->delete();

            DB::commit();
            Log::info("[ORDER][DESTROY] Sukses hapus order", ['order_id' => $order->id]);
            return redirect()->route('orders.index')
                ->with('success', 'Pesanan berhasil dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[ORDER][DESTROY][ERROR]', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'order_id' => $order->id,
            ]);
            return back()->with('error', 'Terjadi kesalahan saat menghapus pesanan: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        Log::info("[ORDER][EXPORT] Export data", $request->all());
        $filename = 'orders-' . date('Y-m-d-H-i-s') . '.xlsx';
        return Excel::download(new OrdersExport($request), $filename);
    }

    public function updateStatus(Request $request, Order $order)
    {
        if ($order->jenis_transaksi === 'Shopee') {
            return back()->with('error', 'Status order Shopee mengikuti status dari Shopee dan akan diperbarui saat sync.');
        }
        Log::info("[ORDER][UPDATE_STATUS] Masuk update status", ['order_id' => $order->id, 'status' => $request->status]);
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled'
        ]);
        $order->update(['status' => $request->status]);

        Log::info("[ORDER][UPDATE_STATUS] Status updated", ['order_id' => $order->id, 'status' => $request->status]);
        return response()->json([
            'success' => true,
            'message' => 'Status pesanan berhasil diupdate!',
            'status' => $order->status,
        ]);
    }

    private function buildOrderItemsArray(Request $request)
    {
        $items = [];

        // Pastikan semua variabel menjadi array (walaupun cuma 1 produk)
        $product_ids = is_array($request->product_id) ? $request->product_id : [$request->product_id];
        $product_names = is_array($request->product_name) ? $request->product_name : [$request->product_name];
        $quantities = is_array($request->quantity) ? $request->quantity : [$request->quantity];
        $prices = is_array($request->price) ? $request->price : [$request->price];

        $count = count($product_ids);

        for ($i = 0; $i < $count; $i++) {
            $items[] = [
                'product_id' => $product_ids[$i] ?? null,
                'product_name' => $product_names[$i] ?? '',
                'quantity' => $quantities[$i] ?? 1,
                'price' => $prices[$i] ?? 0,
            ];
        }
        Log::info('[ORDER][buildOrderItemsArray] Hasil array', $items);
        return $items;
    }
}
