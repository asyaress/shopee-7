<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\ShopeeShopContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HppController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::query()->orderBy('name');
        ShopeeShopContext::scopeProducts($query);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('external_item_id', 'like', "%{$s}%")
                    ->orWhere('category', 'like', "%{$s}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('platform')) {
            if ($request->platform === 'shopee') {
                $query->where('external_platform', 'shopee');
            } elseif ($request->platform === 'internal') {
                $query->where(function ($q) {
                    $q->whereNull('external_platform')
                        ->orWhere('external_platform', '!=', 'shopee');
                });
            }
        }

        $products = $query->get();

        $filter = $request->get('fill', 'all');
        if ($filter === 'missing') {
            $products = $products->filter(fn ($p) => $p->hpp_amount === null);
        } elseif ($filter === 'complete') {
            $products = $products->filter(fn ($p) => $p->hpp_amount !== null);
        }

        $statsQuery = Product::query();
        ShopeeShopContext::scopeProducts($statsQuery);
        $total = (clone $statsQuery)->count();
        $withHpp = (clone $statsQuery)->whereNotNull('hpp_amount')->count();
        $missing = $total - $withHpp;
        $pct = $total > 0 ? round(($withHpp / $total) * 100) : 0;

        $categories = Product::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('hub.hpp', [
            'products' => $products->values(),
            'categories' => $categories,
            'stats' => [
                'total' => $total,
                'with_hpp' => $withHpp,
                'missing' => $missing,
                'pct' => $pct,
            ],
            'filters' => [
                'search' => $request->search,
                'category' => $request->category,
                'platform' => $request->platform,
                'fill' => $filter,
            ],
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|integer|exists:products,id',
            'products.*.hpp_amount' => 'nullable|numeric|min:0',
            'products.*.packaging_type' => 'nullable|in:fixed,percent',
            'products.*.packaging_value' => 'nullable|numeric|min:0',
        ]);

        foreach ($data['products'] as $row) {
            $product = Product::find($row['id']);
            if (!$product) {
                continue;
            }
            $product->update([
                'hpp_amount' => $row['hpp_amount'] !== '' && $row['hpp_amount'] !== null ? $row['hpp_amount'] : null,
                'packaging_type' => $row['packaging_type'] ?: 'fixed',
                'packaging_value' => $row['packaging_value'] !== '' && $row['packaging_value'] !== null ? $row['packaging_value'] : null,
            ]);
        }

        return redirect()
            ->route('hpp.index', array_filter($request->only(['search', 'category', 'platform', 'fill'])))
            ->with('success', 'HPP & packaging berhasil disimpan.');
    }
}
