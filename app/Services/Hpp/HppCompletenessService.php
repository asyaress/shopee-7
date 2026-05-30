<?php

namespace App\Services\Hpp;

use App\Models\Product;
use App\Support\ShopeeShopContext;
use Illuminate\Support\Collection;

class HppCompletenessService
{
    public function shopSummary(?int $shopId = null): array
    {
        $shopId = $shopId ?? ShopeeShopContext::shopId();
        $products = $this->productsForShop($shopId);

        $total = $products->count();
        $complete = $products->filter(fn (Product $p) => $this->isProductComplete($p))->count();
        $missing = $total - $complete;
        $pct = $total > 0 ? $complete / $total : 1.0;

        $gateMin = (float) config('monitoring.hpp_gate.min_complete_pct', 0.85);
        $blockBelow = (float) config('monitoring.hpp_gate.block_recommendations_below_pct', 0.70);

        return [
            'shop_id' => $shopId,
            'total_products' => $total,
            'complete' => $complete,
            'missing' => $missing,
            'complete_pct' => $pct,
            'complete_pct_label' => number_format($pct * 100, 1) . '%',
            'gate_ok' => $pct >= $gateMin,
            'recommendations_allowed' => $pct >= $blockBelow,
            'priority_products' => $this->priorityMissing($products)->take(10)->values()->all(),
        ];
    }

    public function isProductComplete(Product $product): bool
    {
        if ($product->variants->isNotEmpty()) {
            return $product->variants->contains(fn ($v) => $v->hpp_amount !== null && (float) $v->hpp_amount >= 0);
        }

        return $product->hpp_amount !== null;
    }

    public function flagProductRow(array $row, Product $product): array
    {
        $hpp = $product->hpp_amount;
        $avgPrice = (float) ($product->base_price ?? 0);

        $issues = [];
        if ($row['missing_cost'] ?? false) {
            $issues[] = 'hpp_missing';
        }
        if ($hpp !== null && $avgPrice > 0 && ((float) $hpp / $avgPrice) > 0.7) {
            $issues[] = 'hpp_suspicious_high';
        }
        if ($hpp !== null && (float) $hpp <= 0 && ($row['qty'] ?? 0) > 0) {
            $issues[] = 'hpp_zero';
        }

        $row['hpp_issues'] = $issues;

        return $row;
    }

    private function productsForShop(int $shopId): Collection
    {
        $q = Product::query()->with(['variants:id,product_id,hpp_amount']);

        if ($shopId > 0) {
            ShopeeShopContext::scopeProducts($q);
        }

        return $q->orderBy('name')->get();
    }

    private function priorityMissing(Collection $products): Collection
    {
        return $products
            ->filter(fn (Product $p) => !$this->isProductComplete($p))
            ->map(fn (Product $p) => [
                'product_id' => $p->id,
                'name' => $p->name,
                'external_item_id' => $p->external_item_id,
            ]);
    }
}
