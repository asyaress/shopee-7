<?php

namespace App\Services\Ceo;

use App\Models\BusinessDecisionLog;
use App\Support\ShopeeShopContext;
use Illuminate\Support\Collection;

class DecisionLogService
{
    public function log(array $data): BusinessDecisionLog
    {
        return BusinessDecisionLog::create([
            'shop_id' => $data['shop_id'] ?? ShopeeShopContext::shopId(),
            'product_id' => $data['product_id'] ?? null,
            'decision_type' => $data['decision_type'],
            'title' => $data['title'],
            'note' => $data['note'] ?? null,
            'context' => $data['context'] ?? null,
        ]);
    }

    public function recent(int $limit = 50): Collection
    {
        return BusinessDecisionLog::query()
            ->where('shop_id', ShopeeShopContext::shopId())
            ->with('product:id,name')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}
