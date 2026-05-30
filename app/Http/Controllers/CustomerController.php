<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Order;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
    {
        // Query customer & hitung pesanan via subquery by customer_name (tidak pakai relasi)
        $query = Customer::query()
            ->selectRaw('customers.*, (SELECT COUNT(*) FROM orders WHERE orders.customer_name = customers.name) as orders_count');

        // Filter berdasarkan search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter berdasarkan tipe
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter berdasarkan status
        if ($request->filled('status')) {
            if ($request->status == 'active') {
                $query->where('is_active', true);
            } elseif ($request->status == 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Sorting
        switch ($request->get('sort', 'latest')) {
            case 'oldest':
                $query->oldest();
                break;
            case 'name':
                $query->orderBy('name');
                break;
            case 'orders':
                $query->orderBy('orders_count', 'desc');
                break;
            default:
                $query->latest();
                break;
        }

        $customers = $query->paginate(10);

        return view('customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'company' => 'nullable|string|max:255',
            'type' => 'required|in:individual,company'
        ]);

        $customer = Customer::create($request->all());

        // Return JSON response for AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer berhasil ditambahkan!',
                'customer' => $customer
            ]);
        }

        return redirect()->route('customers.index')
            ->with('success', 'Customer berhasil ditambahkan!');
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer)
    {
        // Ambil semua order dengan nama customer ini
        $orders = Order::where('customer_name', $customer->name)->latest()->get();
        return view('customers.show', compact('customer', 'orders'));
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified customer in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'company' => 'nullable|string|max:255',
            'type' => 'required|in:individual,company'
        ]);

        $customer->update($request->all());

        return redirect()->route('customers.index')
            ->with('success', 'Customer berhasil diupdate!');
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy(Customer $customer)
    {
        // Cek apakah customer memiliki pesanan (by customer_name)
        $orderCount = Order::where('customer_name', $customer->name)->count();
        if ($orderCount > 0) {
            return redirect()->route('customers.index')
                ->with('error', 'Customer tidak dapat dihapus karena masih memiliki pesanan!');
        }

        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer berhasil dihapus!');
    }

    /**
     * Search customers for select2 or autocomplete
     */
    public function search(Request $request)
    {
        $search = $request->get('q');

        $customers = Customer::active()
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get();

        return response()->json($customers);
    }
}
