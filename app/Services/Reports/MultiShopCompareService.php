<?php

namespace App\Services\Reports;

use App\Support\ShopeeShopContext;
use Illuminate\Http\Request;

class MultiShopCompareService
{
    public function __construct(
        private readonly ProductProfitReportService $reportService,
        private readonly ProductSkuClassifier $classifier,
    ) {
    }

    public function compare(Request $request): array
    {
        $originalShop = ShopeeShopContext::shopId();
        $rows = [];

        foreach (ShopeeShopContext::tokens() as $token) {
            $shopId = (int) $token->shop_id;
            if ($shopId <= 0) {
                continue;
            }

            ShopeeShopContext::setShopId($shopId);
            $report = $this->reportService->build($request);
            $s = $report['summary'] ?? [];
            $products = $report['products'] ?? [];

            $bleeders = 0;
            $stars = 0;
            foreach ($products as $p) {
                $tier = $p['tier'] ?? $this->classifier->classify($p);
                if ($tier === ProductSkuClassifier::BLEEDER) {
                    $bleeders++;
                }
                if ($tier === ProductSkuClassifier::STAR) {
                    $stars++;
                }
            }

            $rows[] = [
                'shop_id' => $shopId,
                'shop_label' => ShopeeShopContext::shopLabel($shopId, $token),
                'gross' => (int) ($s['gross'] ?? 0),
                'net_profit' => (int) ($s['net_profit'] ?? 0),
                'margin' => (float) ($s['margin'] ?? 0),
                'ads_total' => (int) ($s['ads_total'] ?? 0),
                'roas' => $s['roas'] ?? null,
                'orders_count' => (int) ($s['orders_count'] ?? 0),
                'bleeders' => $bleeders,
                'stars' => $stars,
                'missing_cost_orders' => (int) ($s['missing_cost_orders'] ?? 0),
            ];
        }

        if ($originalShop > 0) {
            ShopeeShopContext::setShopId($originalShop);
        }

        return $rows;
    }
}
