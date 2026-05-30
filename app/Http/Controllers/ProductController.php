<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Order;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request)
    {
        // Query dasar (menghitung total pesanan per produk)
        $query = Product::withCount('orders');

        // Filter Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter kategori
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter status aktif/tidak
        if ($request->filled('status')) {
            if ($request->status == 'active') {
                $query->where('is_active', true);
            } elseif ($request->status == 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Filter harga
        if ($request->filled('price_range')) {
            $priceRange = explode('-', $request->price_range);
            if (count($priceRange) == 2) {
                $query->whereBetween('base_price', [$priceRange[0], $priceRange[1]]);
            }
        }

        // Sorting (jika DataTables client-side, ini tidak terlalu penting, tapi agar default tetap benar)
        switch ($request->get('sort', 'latest')) {
            case 'name':
                $query->orderBy('name');
                break;
            case 'price_low':
                $query->orderBy('base_price');
                break;
            case 'price_high':
                $query->orderBy('base_price', 'desc');
                break;
            case 'popular':
                $query->orderBy('orders_count', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        // Ambil semua produk untuk DataTables (client-side, semua produk dikirim ke blade)
        $products = $query->get();

        // List kategori untuk filter (plus count tiap kategori)
        $categories = Product::selectRaw('category, COUNT(*) as count')
            ->whereNotNull('category')
            ->groupBy('category')
            ->orderBy('category')
            ->get();

        // Total semua produk & pesanan (untuk statistik)
        $allProductsCount = Product::count();
        $totalOrders = Order::count();

        return view('products.index', compact(
            'products',
            'categories',
            'allProductsCount',
            'totalOrders'
        ));
    }


    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        // Get existing categories for dropdown
        $categories = Product::select('category')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('products.create', compact('categories'));
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'base_price' => 'nullable|numeric|min:0',
            'unit' => 'required|string|max:50',
            'specifications' => 'nullable|string',
        ]);

        $productData = $request->all();

        // Handle specifications as JSON
        if ($request->specifications) {
            $productData['specifications'] = json_encode([
                'specifications' => $request->specifications
            ]);
        }

        Product::create($productData);

        return redirect()->route('products.index')
            ->with('success', 'Produk berhasil ditambahkan!');
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product)
    {
        $product->load([
            'variants',
            'orders' => function ($query) {
                $query->with('customer')->latest();
            }
        ]);

        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product)
    {
        if ($product->external_platform === 'shopee') {
            return redirect()->route('products.index')
                ->with('error', 'Produk Shopee bersifat read-only karena disync dari API.');
        }

        // Get existing categories for dropdown
        $categories = Product::select('category')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, Product $product)
    {
        if ($product->external_platform === 'shopee') {
            return redirect()->route('products.index')
                ->with('error', 'Produk Shopee bersifat read-only karena disync dari API.');
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'base_price' => 'nullable|numeric|min:0',
            'unit' => 'required|string|max:50',
            'specifications' => 'nullable|string',
        ]);

        $productData = $request->all();

        // Handle specifications as JSON
        if ($request->specifications) {
            $productData['specifications'] = json_encode([
                'specifications' => $request->specifications
            ]);
        }

        $product->update($productData);

        return redirect()->route('products.index')
            ->with('success', 'Produk berhasil diupdate!');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product)
    {
        if ($product->external_platform === 'shopee') {
            return redirect()->route('products.index')
                ->with('error', 'Produk Shopee tidak bisa dihapus dari panel ini (akan muncul lagi saat sync).');
        }
        // Check if product has orders
        if ($product->orders()->count() > 0) {
            return redirect()->route('products.index')
                ->with('error', 'Produk tidak dapat dihapus karena masih ada pesanan yang menggunakan produk ini!');
        }

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Produk berhasil dihapus!');
    }
    
public function updateCosts(Request $request, Product $product)
{
    // Shopee boleh update biaya internal, jadi JANGAN di-block
    $request->validate([
        'hpp_amount' => 'nullable|numeric|min:0',
        'packaging_type' => 'nullable|in:fixed,percent',
        'packaging_value' => 'nullable|numeric|min:0',

        'variants' => 'array',
        'variants.*.hpp_amount' => 'nullable|numeric|min:0',
        'variants.*.packaging_type' => 'nullable|in:fixed,percent',
        'variants.*.packaging_value' => 'nullable|numeric|min:0',
    ]);

    // 1) simpan default di produk
    $product->update([
        'hpp_amount' => $request->input('hpp_amount'),
        'packaging_type' => $request->input('packaging_type', 'fixed'),
        'packaging_value' => $request->input('packaging_value'),
    ]);

    // 2) simpan override per variant (nullable = inherit)
    $variants = $request->input('variants', []);
    if (!empty($variants)) {
        $ids = array_keys($variants);

        $rows = ProductVariant::where('product_id', $product->id)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        foreach ($variants as $variantId => $data) {
            if (!isset($rows[$variantId])) continue;

            $rows[$variantId]->update([
                // kalau kosong => null => inherit default product
                'hpp_amount' => $data['hpp_amount'] !== '' ? $data['hpp_amount'] : null,
                'packaging_type' => $data['packaging_type'] !== '' ? $data['packaging_type'] : null,
                'packaging_value' => $data['packaging_value'] !== '' ? $data['packaging_value'] : null,
            ]);
        }
    }

    return back()->with('success', 'Biaya internal berhasil disimpan.');
}

public function costsIndex(Request $request)
{
    $query = Product::with('variants');

    if ($request->filled('search')) {
        $s = $request->search;
        $query->where(function ($q) use ($s) {
            $q->where('name', 'like', "%{$s}%")
              ->orWhere('description', 'like', "%{$s}%");
        });
    }

    if ($request->filled('category')) {
        $query->where('category', $request->category);
    }

    // optional: sort by name biar enak
    $products = $query->orderBy('name')->get();

    // buat filter dropdown kategori
    $categories = Product::select('category')
        ->whereNotNull('category')
        ->distinct()
        ->orderBy('category')
        ->pluck('category');

    return view('products.costs', compact('products', 'categories'));
}


}
