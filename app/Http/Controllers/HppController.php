<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\ShopeeShopContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class HppController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::query()
            ->with(['variants' => fn ($q) => $q->orderBy('name')->orderBy('id')])
            ->orderBy('name');
        ShopeeShopContext::scopeProducts($query);

        $allProducts = $query->get();
        $products = $this->applyFilters($allProducts, $request);

        $total = $allProducts->count();
        $complete = $allProducts->filter(fn (Product $product) => $this->isCostComplete($product))->count();
        $variantTotal = $allProducts->sum(fn (Product $product) => $product->variants->count());
        $productsWithVariants = $allProducts->filter(fn (Product $product) => $product->variants->isNotEmpty())->count();
        $variantOverrides = $allProducts->sum(fn (Product $product) => $product->variants
            ->filter(fn ($variant) => $variant->hpp_amount !== null || $variant->packaging_type !== null)
            ->count());

        return view('hub.hpp', [
            'products' => $products->values(),
            'categories' => $allProducts->pluck('category')->filter()->unique()->sort()->values(),
            'stats' => [
                'total' => $total,
                'complete' => $complete,
                'missing' => $total - $complete,
                'pct' => $total > 0 ? (int) round(($complete / $total) * 100) : 0,
                'variants' => $variantTotal,
                'products_with_variants' => $productsWithVariants,
                'variant_overrides' => $variantOverrides,
            ],
            'filters' => [
                'search' => (string) $request->query('search', ''),
                'category' => (string) $request->query('category', ''),
                'platform' => (string) $request->query('platform', ''),
                'fill' => (string) $request->query('fill', 'all'),
            ],
        ]);
    }

    public function save(Request $request): RedirectResponse|JsonResponse
    {
        $productsInput = $request->input('products');
        if ($request->filled('payload')) {
            $productsInput = json_decode((string) $request->input('payload'), true);
        }

        $validated = Validator::make(['products' => $productsInput], [
            'products' => 'required|array',
            'products.*.id' => 'required|integer|exists:products,id',
            'products.*.hpp_amount' => 'nullable|numeric|min:0',
            'products.*.packaging_type' => 'nullable|in:fixed,percent',
            'products.*.packaging_value' => 'nullable|numeric|min:0',
            'products.*.variants' => 'nullable|array',
            'products.*.variants.*.id' => 'required|integer|exists:product_variants,id',
            'products.*.variants.*.hpp_amount' => 'nullable|numeric|min:0',
            'products.*.variants.*.packaging_type' => 'nullable|in:fixed,percent',
            'products.*.variants.*.packaging_value' => 'nullable|numeric|min:0',
        ])->validate();

        $ids = collect($validated['products'])->pluck('id')->map(fn ($id) => (int) $id)->all();
        $query = Product::query()->with('variants')->whereIn('id', $ids);
        ShopeeShopContext::scopeProducts($query);
        $allowedProducts = $query->get()->keyBy('id');

        $savedProducts = 0;
        $savedVariants = 0;

        DB::transaction(function () use ($validated, $allowedProducts, &$savedProducts, &$savedVariants): void {
            foreach ($validated['products'] as $row) {
                $product = $allowedProducts->get((int) $row['id']);
                if (!$product) {
                    continue;
                }

                $product->update([
                    'hpp_amount' => $this->nullableNumber($row['hpp_amount'] ?? null),
                    'packaging_type' => $row['packaging_type'] ?: 'fixed',
                    'packaging_value' => $this->nullableNumber($row['packaging_value'] ?? null),
                ]);
                $savedProducts++;

                $variants = $product->variants->keyBy('id');
                foreach (($row['variants'] ?? []) as $variantRow) {
                    $variant = $variants->get((int) $variantRow['id']);
                    if (!$variant) {
                        continue;
                    }

                    $packagingType = $variantRow['packaging_type'] ?: null;
                    $variant->update([
                        'hpp_amount' => $this->nullableNumber($variantRow['hpp_amount'] ?? null),
                        'packaging_type' => $packagingType,
                        'packaging_value' => $packagingType
                            ? $this->nullableNumber($variantRow['packaging_value'] ?? null)
                            : null,
                    ]);
                    $savedVariants++;
                }
            }
        });

        $message = "Biaya tersimpan untuk {$savedProducts} produk dan {$savedVariants} varian.";

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'saved_products' => $savedProducts,
                'saved_variants' => $savedVariants,
            ]);
        }

        return redirect()
            ->route('hpp.index', array_filter($request->only(['search', 'category', 'platform', 'fill'])))
            ->with('success', $message);
    }

    private function applyFilters(Collection $products, Request $request): Collection
    {
        $search = mb_strtolower(trim((string) $request->query('search', '')));
        if ($search !== '') {
            $products = $products->filter(function (Product $product) use ($search): bool {
                $haystack = mb_strtolower(implode(' ', [
                    $product->name,
                    $product->external_item_id,
                    $product->external_sku,
                    $product->category,
                ]));

                if (str_contains($haystack, $search)) {
                    return true;
                }

                return $product->variants->contains(function ($variant) use ($search): bool {
                    return str_contains(mb_strtolower(($variant->name ?? '') . ' ' . ($variant->sku ?? '')), $search);
                });
            });
        }

        if ($request->filled('category')) {
            $products = $products->where('category', $request->query('category'));
        }

        if ($request->query('platform') === 'shopee') {
            $products = $products->where('external_platform', 'shopee');
        } elseif ($request->query('platform') === 'internal') {
            $products = $products->filter(fn (Product $product) => $product->external_platform !== 'shopee');
        }

        return match ((string) $request->query('fill', 'all')) {
            'missing' => $products->reject(fn (Product $product) => $this->isCostComplete($product)),
            'complete' => $products->filter(fn (Product $product) => $this->isCostComplete($product)),
            'variants' => $products->filter(fn (Product $product) => $product->variants->isNotEmpty()),
            default => $products,
        };
    }

    private function isCostComplete(Product $product): bool
    {
        if ($product->variants->isEmpty()) {
            return $product->hpp_amount !== null;
        }

        return $product->variants->every(
            fn ($variant) => $variant->hpp_amount !== null || $product->hpp_amount !== null
        );
    }

    private function nullableNumber(mixed $value): int|float|null
    {
        return $value === '' || $value === null ? null : (float) $value;
    }
}
