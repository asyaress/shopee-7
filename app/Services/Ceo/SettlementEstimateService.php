<?php

namespace App\Services\Ceo;

use App\Models\Order;
use App\Models\ShopeeProductAdsDaily;
use App\Models\ShopeeSettlementRelease;
use App\Services\Finance\ShopeeFinancialExtractor;
use App\Support\ShopeeShopContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Arus kas & dana dilepaskan — gabungan import Data Income + estimasi hold.
 */
class SettlementEstimateService
{
    public function build(int $weeks = 8): array
    {
        $shopId = ShopeeShopContext::shopId();
        $holdDays = (int) config('monitoring.settlement.hold_days_after_complete', 3);
        $end = now()->endOfDay();
        $start = now()->subWeeks($weeks)->startOfWeek();

        $this->syncEstimatedReleases($shopId, $holdDays);

        $weekly = [];
        $cursor = $start->copy();
        $releasedTotal = 0.0;
        $pendingTotal = 0.0;

        while ($cursor <= $end) {
            $wEnd = $cursor->copy()->endOfWeek();
            $label = $cursor->format('d M') . ' – ' . $wEnd->format('d M');

            $released = $this->sumReleasedNet($shopId, $cursor, $wEnd);
            $adsOut = (float) ShopeeProductAdsDaily::query()
                ->where('shop_id', $shopId)
                ->whereBetween('report_date', [$cursor->toDateString(), $wEnd->toDateString()])
                ->sum('spend');

            $weekly[] = [
                'label' => $label,
                'net_in' => (int) round($released),
                'ads_out' => (int) round($adsOut),
                'net_cash' => (int) round($released - $adsOut),
            ];

            $releasedTotal += $released;
            $cursor->addWeek();
        }

        $pendingTotal = $this->sumPendingNet($shopId, $holdDays);

        return [
            'weeks' => $weekly,
            'note' => 'Dana masuk berdasarkan tanggal dilepaskan (import Data Income) atau estimasi +' . $holdDays . ' hari setelah order selesai. Bukan saldo wallet resmi Shopee.',
            'pending_settlement' => (int) round($pendingTotal),
            'released_total' => (int) round($releasedTotal),
            'hold_days' => $holdDays,
            'import_hint' => 'Upload export Data Income (CSV) di halaman Arus Kas untuk tanggal dana dilepaskan yang akurat.',
            'totals' => [
                'net_in' => array_sum(array_column($weekly, 'net_in')),
                'ads_out' => array_sum(array_column($weekly, 'ads_out')),
                'net_cash' => array_sum(array_column($weekly, 'net_cash')),
            ],
        ];
    }

    private function syncEstimatedReleases(int $shopId, int $holdDays): void
    {
        if ($shopId <= 0) {
            return;
        }

        $orders = Order::query()
            ->whereRaw('LOWER(COALESCE(jenis_transaksi, "")) = ?', ['shopee'])
            ->where('status', 'completed')
            ->whereHas('shopeeFinancial', fn ($f) => $f->where('shop_id', $shopId))
            ->with('shopeeFinancial')
            ->where('order_date', '>=', now()->subMonths(6))
            ->get();

        foreach ($orders as $order) {
            $fin = $order->shopeeFinancial;
            if (!$fin) {
                continue;
            }

            $existing = ShopeeSettlementRelease::query()
                ->where('shop_id', $shopId)
                ->where('order_sn', $fin->order_sn ?? $order->order_number)
                ->first();

            if ($existing && $existing->source === 'income_csv') {
                continue;
            }

            $extract = ShopeeFinancialExtractor::extract($fin);
            $net = (float) ($extract['net'] ?? $fin->seller_income ?? 0);
            $releasedAt = $order->order_date
                ? $order->order_date->copy()->addDays($holdDays)
                : null;

            ShopeeSettlementRelease::updateOrCreate(
                ['shop_id' => $shopId, 'order_sn' => $fin->order_sn ?? $order->order_number],
                [
                    'order_id' => $order->id,
                    'released_at' => $releasedAt,
                    'net_amount' => $net,
                    'source' => 'estimate',
                ]
            );
        }
    }

    private function sumReleasedNet(int $shopId, Carbon $from, Carbon $to): float
    {
        return (float) ShopeeSettlementRelease::query()
            ->where('shop_id', $shopId)
            ->whereNotNull('released_at')
            ->whereBetween('released_at', [$from->startOfDay(), $to->endOfDay()])
            ->where('released_at', '<=', now())
            ->sum('net_amount');
    }

    private function sumPendingNet(int $shopId, int $holdDays): float
    {
        return (float) ShopeeSettlementRelease::query()
            ->where('shop_id', $shopId)
            ->where(function ($q) {
                $q->whereNull('released_at')
                    ->orWhere('released_at', '>', now());
            })
            ->sum('net_amount');
    }
}
