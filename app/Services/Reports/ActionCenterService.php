<?php

namespace App\Services\Reports;

use App\Services\Hpp\HppCompletenessService;
use App\Support\ShopeeShopContext;

class ActionCenterService
{
    public function __construct(
        private readonly HppCompletenessService $hppCompleteness,
        private readonly ProductSkuClassifier $classifier,
        private readonly ProductActionEngine $actionEngine,
        private readonly CashGuardService $cashGuard,
    ) {
    }

    public function build(array $report): array
    {
        $products = $report['products'] ?? [];
        $summary = $report['summary'] ?? [];
        $hpp = $this->hppCompleteness->shopSummary();
        $allowed = $hpp['recommendations_allowed'];

        $withActions = [];
        foreach ($products as $row) {
            $action = $this->actionEngine->forProduct($row, $allowed);
            if (($action['code'] ?? 'hold') !== 'hold') {
                $withActions[] = array_merge($row, ['action' => $action]);
            }
        }

        usort($withActions, function ($a, $b) {
            $sev = ['danger' => 0, 'warning' => 1, 'success' => 2, 'info' => 3];
            $sa = $sev[$a['action']['severity'] ?? 'info'] ?? 9;
            $sb = $sev[$b['action']['severity'] ?? 'info'] ?? 9;
            if ($sa !== $sb) {
                return $sa <=> $sb;
            }

            return ($a['net_profit'] ?? 0) <=> ($b['net_profit'] ?? 0);
        });

        $urgent = array_values(array_filter($withActions, fn ($r) => in_array($r['action']['severity'] ?? '', ['danger', 'warning'], true)));
        $opportunities = array_values(array_filter($withActions, fn ($r) => ($r['action']['severity'] ?? '') === 'success'));

        $bleeders = array_values(array_filter($products, fn ($p) => ($p['tier'] ?? '') === ProductSkuClassifier::BLEEDER));
        usort($bleeders, fn ($a, $b) => ($a['net_profit'] ?? 0) <=> ($b['net_profit'] ?? 0));

        return [
            'shop_id' => ShopeeShopContext::shopId(),
            'shop_label' => ShopeeShopContext::shopLabel(ShopeeShopContext::shopId()),
            'hpp_quality' => $hpp,
            'cash_guard' => $this->cashGuard->build($report),
            'urgent' => array_slice($urgent, 0, 7),
            'opportunities' => array_slice($opportunities, 0, 5),
            'bleeders' => array_slice($bleeders, 0, 10),
            'data_blockers' => $this->dataBlockers($report, $hpp),
            'counts' => [
                'urgent' => count($urgent),
                'opportunities' => count($opportunities),
                'bleeders' => count($bleeders),
            ],
        ];
    }

    private function dataBlockers(array $report, array $hpp): array
    {
        $blockers = [];
        $missing = (int) ($report['summary']['missing_cost_orders'] ?? 0);

        if ($missing > 0) {
            $blockers[] = [
                'type' => 'danger',
                'title' => "{$missing} pesanan tanpa HPP lengkap",
                'text' => 'Laba rugi bisa salah. Perbaiki di Input HPP.',
                'route' => 'hpp.index',
            ];
        }

        if (!$hpp['gate_ok']) {
            $blockers[] = [
                'type' => 'warning',
                'title' => 'Kelengkapan HPP ' . $hpp['complete_pct_label'],
                'text' => 'Target minimal 85% SKU punya HPP valid.',
                'route' => 'hpp.index',
            ];
        }

        if (($report['summary']['orders_count'] ?? 0) === 0) {
            $blockers[] = [
                'type' => 'info',
                'title' => 'Tidak ada pesanan pada periode ini',
                'text' => 'Ubah filter tanggal atau sync pesanan Shopee.',
                'route' => 'manage.index',
            ];
        }

        return $blockers;
    }
}
