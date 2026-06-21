<?php

namespace App\Services\Ceo;

use App\Services\Hpp\HppCompletenessService;
use App\Services\Reports\ActionCenterService;
use App\Services\Reports\ProductProfitReportService;
use App\Support\ShopeeShopContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Ringkasan toko ringan untuk chatbot — di-cache agar tidak membebani setiap chat.
 */
class CeoChatbotSnapshotService
{
    public function __construct(
        private readonly ProductProfitReportService $reportService,
        private readonly ActionCenterService $actionCenter,
        private readonly HppCompletenessService $hppCompleteness,
    ) {
    }

    public function get(?int $shopId = null): array
    {
        $shopId = $shopId ?? ShopeeShopContext::shopId();
        $key = 'ceo_chat_snapshot:' . ($shopId ?: 'all') . ':' . now()->format('Y-m');

        return Cache::remember($key, now()->addMinutes(5), fn () => $this->build($shopId));
    }

    private function build(int $shopId): array
    {
        $request = Request::create('/', 'GET', [
            'start' => Carbon::now()->startOfMonth()->toDateString(),
            'end' => Carbon::now()->toDateString(),
            'status' => 'completed',
            'jenis' => 'shopee',
        ]);

        $report = $this->reportService->build($request);
        $summary = $report['summary'] ?? [];
        $insights = $report['insights'] ?? [];
        $action = $this->actionCenter->build($report);
        $hpp = $this->hppCompleteness->shopSummary($shopId);

        $netProfit = (float) ($summary['net_profit'] ?? 0);
        $gross = (float) ($summary['gross'] ?? 0);
        $health = (int) ($insights['health_score'] ?? 0);

        return [
            'period_label' => Carbon::now()->translatedFormat('F Y'),
            'shop_label' => ShopeeShopContext::shopLabel($shopId),
            'gross' => (int) round($gross),
            'net_profit' => (int) round($netProfit),
            'profit_positive' => $netProfit >= 0,
            'health_score' => $health,
            'hpp_pct' => round(($hpp['complete_pct'] ?? 0) * 100, 1),
            'hpp_ok' => (bool) ($hpp['recommendations_allowed'] ?? false),
            'urgent_count' => (int) ($action['counts']['urgent'] ?? 0),
            'bleeder_count' => (int) ($action['counts']['bleeders'] ?? 0),
            'opportunity_count' => (int) ($action['counts']['opportunities'] ?? 0),
            'orders_count' => (int) ($summary['orders_count'] ?? 0),
            'cached_at' => now()->toIso8601String(),
        ];
    }

    public function contextBlock(array $snapshot): string
    {
        $profitLabel = $snapshot['profit_positive']
            ? 'positif'
            : '**minus — perlu tindakan**';

        $lines = [
            '📊 **Snapshot toko (' . ($snapshot['period_label'] ?? 'bulan ini') . '):**',
            '• Laba bersih: **Rp ' . number_format($snapshot['net_profit'] ?? 0, 0, ',', '.') . '** (' . $profitLabel . ')',
            '• Penjualan kotor: Rp ' . number_format($snapshot['gross'] ?? 0, 0, ',', '.'),
            '• Skor kesehatan: **' . ($snapshot['health_score'] ?? 0) . '/100**',
            '• HPP terisi: **' . ($snapshot['hpp_pct'] ?? 0) . '%**',
        ];

        if (($snapshot['urgent_count'] ?? 0) > 0) {
            $lines[] = '• ⚠️ **' . $snapshot['urgent_count'] . ' item urgent** di Pusat Aksi — sebaiknya dicek hari ini.';
        } elseif ($snapshot['profit_positive'] ?? false) {
            $lines[] = '• ✅ Tidak ada urgent kritis saat ini — lanjut pantau target & iklan.';
        }

        return implode("\n", $lines);
    }
}
