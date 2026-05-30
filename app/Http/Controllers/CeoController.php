<?php

namespace App\Http\Controllers;

use App\Services\Ceo\AccountingExportService;
use App\Services\Ceo\CeoAlertService;
use App\Services\Ceo\DecisionLogService;
use App\Services\Ceo\MonthlyTargetService;
use App\Services\Ceo\PromoAnalysisService;
use App\Services\Ceo\RoasAdvisorService;
use App\Services\Ceo\SettlementEstimateService;
use App\Support\ShopeeShopContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CeoController extends Controller
{
    public function __construct(
        private readonly MonthlyTargetService $targets,
        private readonly SettlementEstimateService $settlement,
        private readonly PromoAnalysisService $promo,
        private readonly RoasAdvisorService $roasAdvisor,
        private readonly DecisionLogService $decisions,
        private readonly AccountingExportService $accounting,
        private readonly CeoAlertService $alerts,
    ) {
    }

    public function targets(Request $request): View
    {
        $data = $this->targets->dashboard($request, $request->query('month'));

        return view('hub.ceo.targets', array_merge($data, [
            'activeSection' => 'targets',
            'shop' => ['id' => ShopeeShopContext::shopId(), 'label' => ShopeeShopContext::shopLabel(ShopeeShopContext::shopId())],
        ]));
    }

    public function saveTargets(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year_month' => 'required|date_format:Y-m',
            'target_net_profit' => 'nullable|numeric|min:0',
            'target_gross' => 'nullable|numeric|min:0',
            'target_units' => 'nullable|integer|min:0',
            'ad_budget_cap' => 'nullable|numeric|min:0',
            'operational_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ]);

        $this->targets->save(ShopeeShopContext::shopId(), $validated['year_month'], $validated);

        return redirect()->route('ceo.targets', ['month' => $validated['year_month']])
            ->with('success', 'Target bulanan disimpan.');
    }

    public function settlement(Request $request): View
    {
        return view('hub.ceo.settlement', [
            'cashflow' => $this->settlement->build(8),
            'activeSection' => 'settlement',
            'shop' => ['label' => ShopeeShopContext::shopLabel(ShopeeShopContext::shopId())],
        ]);
    }

    public function promo(Request $request): View
    {
        return view('hub.ceo.promo', [
            'promo' => $this->promo->analyze($request),
            'activeSection' => 'promo',
            'filters' => $request->query(),
        ]);
    }

    public function roas(Request $request): View
    {
        $report = app(\App\Services\Reports\ProductProfitReportService::class)->build($request);

        return view('hub.ceo.roas', [
            'roas' => $this->roasAdvisor->shopAdvice($report),
            'report' => $report,
            'activeSection' => 'roas',
        ]);
    }

    public function decisions(Request $request): View
    {
        return view('hub.ceo.decisions', [
            'logs' => $this->decisions->recent(100),
            'activeSection' => 'decisions',
        ]);
    }

    public function storeDecision(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'decision_type' => 'required|string|max:64',
            'title' => 'required|string|max:255',
            'note' => 'nullable|string|max:5000',
            'product_id' => 'nullable|integer|exists:products,id',
        ]);

        $this->decisions->log($validated);

        return redirect()->back()->with('success', 'Keputusan dicatat.');
    }

    public function exportJournal(Request $request): StreamedResponse
    {
        $rows = $this->accounting->journalRows($request);
        $filename = 'jurnal_' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function runAlerts(): RedirectResponse
    {
        $n = $this->alerts->checkAllShops();

        return redirect()->back()->with('success', "Pengecekan alert selesai untuk {$n} toko.");
    }
}
