<?php

namespace App\Services\Ceo;

use App\Services\Reports\ProductProfitReportService;
use Illuminate\Http\Request;

class PromoAnalysisService
{
    public function __construct(
        private readonly ProductProfitReportService $reportService,
    ) {
    }

    public function analyze(Request $request): array
    {
        $report = $this->reportService->build($request);
        $monthly = $report['monthly'] ?? [];
        $fb = $report['fee_breakdown'] ?? [];
        $s = $report['summary'] ?? [];

        $rows = [];
        foreach ($monthly as $m) {
            $gross = (float) ($m['gross'] ?? 0);
            $net = (float) ($m['net'] ?? 0);
            $fee = $gross - $net;
            $rows[] = [
                'label' => $m['label'],
                'gross' => (int) round($gross),
                'net' => (int) round($net),
                'fee' => (int) round($fee),
                'take_rate' => $gross > 0 ? $fee / $gross : 0,
                'net_profit' => (int) round($m['net_profit'] ?? 0),
                'orders' => (int) ($m['orders'] ?? 0),
            ];
        }

        usort($rows, fn ($a, $b) => strcmp($a['label'], $b['label']));

        $programHemat = (int) ($fb['program_hemat'] ?? 0);
        $feeTotal = (int) ($s['fee_total'] ?? 0);

        return [
            'monthly' => $rows,
            'program_hemat' => $programHemat,
            'program_hemat_pct' => $feeTotal > 0 ? $programHemat / $feeTotal : 0,
            'insight' => $this->insight($rows, $programHemat, $feeTotal),
        ];
    }

    private function insight(array $rows, int $programHemat, int $feeTotal): string
    {
        if (count($rows) < 2) {
            return 'Perlu minimal 2 bulan data untuk membandingkan dampak promo.';
        }

        $worst = collect($rows)->sortBy('net_profit')->first();
        $best = collect($rows)->sortByDesc('net_profit')->first();

        $parts = [];
        if ($programHemat > 0 && $feeTotal > 0) {
            $pct = round(($programHemat / $feeTotal) * 100, 1);
            $parts[] = "Program Hemat Ongkir menyumbang {$pct}% dari total fee platform.";
        }
        if ($worst && $best) {
            $parts[] = "Bulan terlemah: {$worst['label']} (laba " . number_format($worst['net_profit']) . '). Terkuat: ' . $best['label'] . '.';
        }

        return implode(' ', $parts);
    }
}
