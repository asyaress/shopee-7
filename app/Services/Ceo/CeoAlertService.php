<?php

namespace App\Services\Ceo;

use App\Models\CeoAlertLog;
use App\Models\ShopMonthlyCost;
use App\Services\Reports\ActionCenterService;
use App\Services\Reports\ProductProfitReportService;
use App\Services\Reports\ProductSkuClassifier;
use App\Support\ShopeeShopContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CeoAlertService
{
    public function __construct(
        private readonly ProductProfitReportService $reportService,
        private readonly ActionCenterService $actionCenter,
    ) {
    }

    public function checkShop(int $shopId): array
    {
        ShopeeShopContext::setShopId($shopId);
        $request = Request::create('/', 'GET', [
            'start' => now()->startOfMonth()->toDateString(),
            'end' => now()->toDateString(),
        ]);

        $report = $this->reportService->build($request);
        $ac = $this->actionCenter->build($report);
        $s = $report['summary'] ?? [];
        $alerts = [];

        $bleeders = (int) ($ac['counts']['bleeders'] ?? 0);
        if ($bleeders >= 3) {
            $alerts[] = $this->record($shopId, 'bleeders_high', 'warning',
                "{$bleeders} SKU bleeder",
                'Periksa Pusat Aksi — pertimbangkan potong iklan atau naikkan harga.');
        }

        $ym = now()->format('Y-m');
        $monthly = ShopMonthlyCost::query()->where('shop_id', $shopId)->where('year_month', $ym)->first();
        $budget = (float) ($monthly?->ad_budget_cap ?? config('monitoring.ad_budget_monthly')[$shopId] ?? 0);
        $ads = (float) ($s['ads_total'] ?? 0);
        if ($budget > 0 && $ads / $budget >= 0.8) {
            $alerts[] = $this->record($shopId, "budget_80_{$ym}", 'warning',
                'Budget iklan ≥80%',
                'Spend ' . number_format($ads) . ' dari budget ' . number_format($budget));
        }

        if (($s['net_profit'] ?? 0) < 0) {
            $alerts[] = $this->record($shopId, "net_loss_{$ym}", 'danger',
                'Laba bersih bulan ini negatif',
                'Laba ' . number_format($s['net_profit'] ?? 0));
        }

        if (($s['missing_cost_orders'] ?? 0) > 5) {
            $alerts[] = $this->record($shopId, 'hpp_missing', 'danger',
                'Banyak order tanpa HPP',
                ($s['missing_cost_orders'] ?? 0) . ' pesanan — data laba tidak valid.');
        }

        $this->notifyEmail($alerts);

        return $alerts;
    }

    public function checkAllShops(): int
    {
        $count = 0;
        foreach (ShopeeShopContext::tokens() as $token) {
            $shopId = (int) $token->shop_id;
            if ($shopId > 0) {
                $this->checkShop($shopId);
                $count++;
            }
        }

        return $count;
    }

    private function record(int $shopId, string $key, string $severity, string $title, string $message): array
    {
        $todayKey = $key . '_' . now()->format('Y-m-d');
        $exists = CeoAlertLog::query()
            ->where('shop_id', $shopId)
            ->where('alert_key', $todayKey)
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if (!$exists) {
            CeoAlertLog::create([
                'shop_id' => $shopId,
                'alert_key' => $todayKey,
                'severity' => $severity,
                'title' => $title,
                'message' => $message,
                'sent_at' => now(),
            ]);
        }

        return compact('severity', 'title', 'message');
    }

    private function notifyEmail(array $alerts): void
    {
        $to = config('monitoring.alerts.email');
        if (!$to || empty($alerts) || !config('mail.default')) {
            return;
        }

        try {
            $body = collect($alerts)->map(fn ($a) => "[{$a['severity']}] {$a['title']}: {$a['message']}")->implode("\n");
            Mail::raw($body, fn ($m) => $m->to($to)->subject('Shopee Hub — CEO Alert'));
        } catch (\Throwable $e) {
            Log::warning('CEO alert email failed', ['error' => $e->getMessage()]);
        }
    }
}
