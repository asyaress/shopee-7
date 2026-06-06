<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Models\BusinessDecisionLog;
use App\Models\CeoAlertLog;
use App\Models\MobileAlertRead;
use App\Models\Product;
use App\Models\ShopMonthlyCost;
use App\Services\Ceo\CeoAlertService;
use App\Services\Ceo\DecisionLogService;
use App\Services\Ceo\MonthlyTargetService;
use App\Services\Mobile\MobileShopContextService;
use App\Services\Reports\ActionCenterService;
use App\Services\Reports\ProductProfitReportService;
use App\Services\Reports\ProductSkuClassifier;
use App\Services\Reports\RetailRekapService;
use App\Support\ShopeeShopContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CeoController extends BaseMobileController
{
    public function __construct(
        private readonly MobileShopContextService $shops,
        private readonly MonthlyTargetService $targets,
        private readonly ActionCenterService $actionCenter,
        private readonly ProductProfitReportService $reportService,
        private readonly ProductSkuClassifier $classifier,
        private readonly RetailRekapService $retailRekap,
        private readonly CeoAlertService $alerts,
        private readonly DecisionLogService $decisions,
    ) {
    }

    public function shops(Request $request): JsonResponse
    {
        $user = $request->user();
        $activeShopId = $this->shops->activeShopIdFor($user);

        return $this->success([
            'active_shop_id' => $activeShopId,
            'shops' => $this->shops->availableShops($user),
        ]);
    }

    public function setActiveShop(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shop_id' => ['required', 'integer', 'min:1'],
        ]);

        $shopId = $this->shops->setActiveShopId($request->user(), (int) $validated['shop_id']);

        return $this->success([
            'active_shop_id' => $shopId,
            'shop' => [
                'shop_id' => $shopId,
                'label' => ShopeeShopContext::shopLabel($shopId),
            ],
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $shopId = $this->shops->applyForRequest($user, $request);

        try {
            $month = (string) $request->query('month', now()->format('Y-m'));
            $dashboardRequest = clone $request;
            $dashboardData = $this->targets->dashboard($dashboardRequest, $month);
            $report = $dashboardData['report'] ?? $this->reportService->build($dashboardRequest);
            $actions = $this->actionCenter->build($report);

            return $this->success([
                'shop' => [
                    'shop_id' => $shopId,
                    'label' => ShopeeShopContext::shopLabel($shopId),
                ],
                'period' => [
                    'month' => $dashboardData['year_month'] ?? $month,
                    'label' => $report['meta']['period_label'] ?? null,
                    'generated_at' => $report['meta']['generated_at'] ?? null,
                ],
                'summary' => $report['summary'] ?? [],
                'targets' => $dashboardData['targets'] ?? [],
                'actual' => $dashboardData['actual'] ?? [],
                'progress' => $dashboardData['progress'] ?? [],
                'pace' => $dashboardData['pace'] ?? [],
                'alerts' => $this->buildAlerts($report, $actions),
                'urgent_actions' => $this->mapProducts($actions['urgent'] ?? []),
                'top_profit_products' => $this->mapProducts($report['top_products'] ?? []),
                'top_bleeder_products' => $this->mapProducts($actions['bleeders'] ?? []),
                'cash_guard' => $actions['cash_guard'] ?? [],
                'hpp_quality' => $actions['hpp_quality'] ?? [],
            ], meta: [
                'active_shop_id' => $shopId,
            ]);
        } finally {
            ShopeeShopContext::clearForcedShopId();
        }
    }

    public function rekap(Request $request): JsonResponse
    {
        $user = $request->user();
        $shopId = $this->shops->applyForRequest($user, $request);

        try {
            $validated = $request->validate([
                'months' => ['nullable', 'integer', 'min:3', 'max:12'],
            ]);

            $requestedMonths = (int) ($validated['months'] ?? 6);
            $rekap = $this->retailRekap->build($request, $requestedMonths);
            $monthKeys = array_values($rekap['months'] ?? []);
            $columns = $rekap['columns'] ?? [];
            $targets = $rekap['targets'] ?? [];
            $latestMonth = !empty($monthKeys) ? end($monthKeys) : null;
            $previousMonth = count($monthKeys) > 1 ? $monthKeys[count($monthKeys) - 2] : null;

            return $this->success([
                'shop' => [
                    'shop_id' => $shopId,
                    'label' => ShopeeShopContext::shopLabel($shopId),
                ],
                'requested_months' => $requestedMonths,
                'active_month' => $latestMonth,
                'summary' => $this->buildRekapSummary(
                    $latestMonth,
                    $latestMonth ? ($columns[$latestMonth] ?? []) : [],
                    $previousMonth ? ($columns[$previousMonth] ?? []) : [],
                    $latestMonth ? ($targets[$latestMonth] ?? []) : [],
                ),
                'monthly_cards' => collect(array_reverse($monthKeys))
                    ->map(fn (string $monthKey) => $this->buildRekapMonthCard(
                        $monthKey,
                        $columns[$monthKey] ?? [],
                        $targets[$monthKey] ?? [],
                        $monthKey === $latestMonth,
                    ))
                    ->values()
                    ->all(),
                'metric_sections' => $this->buildRekapMetricSections(
                    $monthKeys,
                    $columns,
                ),
                'best_sellers' => collect($rekap['best_sellers'] ?? [])
                    ->map(fn (array $period, string $monthKey) => [
                        'month' => $monthKey,
                        'label' => $period['label'] ?? $monthKey,
                        'products' => array_map(fn (array $product) => [
                            'name' => (string) ($product['name'] ?? ''),
                            'qty' => (int) ($product['qty'] ?? 0),
                        ], $period['products'] ?? []),
                    ])
                    ->values()
                    ->all(),
            ], meta: [
                'active_shop_id' => $shopId,
            ]);
        } finally {
            ShopeeShopContext::clearForcedShopId();
        }
    }

    public function targets(Request $request): JsonResponse
    {
        $user = $request->user();
        $shopId = $this->shops->applyForRequest($user, $request);

        try {
            $validated = $request->validate([
                'month' => ['nullable', 'date_format:Y-m'],
            ]);

            $yearMonth = (string) ($validated['month'] ?? now()->format('Y-m'));
            $dashboardRequest = clone $request;
            $dashboardData = $this->targets->dashboard($dashboardRequest, $yearMonth);
            $target = ShopMonthlyCost::query()
                ->where('shop_id', $shopId)
                ->where('year_month', $yearMonth)
                ->first();

            return $this->success([
                'shop' => [
                    'shop_id' => $shopId,
                    'label' => ShopeeShopContext::shopLabel($shopId),
                ],
                'year_month' => $yearMonth,
                'form' => [
                    'operational_amount' => $this->toIntOrNull($target?->operational_amount),
                    'target_net_profit' => $this->toIntOrNull($target?->target_net_profit),
                    'target_gross' => $this->toIntOrNull($target?->target_gross),
                    'target_units' => $target?->target_units,
                    'ad_budget_cap' => $this->toIntOrNull($target?->ad_budget_cap),
                    'notes' => $target?->notes,
                ],
                'actual' => $dashboardData['actual'] ?? [],
                'progress' => $dashboardData['progress'] ?? [],
                'pace' => $dashboardData['pace'] ?? [],
            ], meta: [
                'active_shop_id' => $shopId,
            ]);
        } finally {
            ShopeeShopContext::clearForcedShopId();
        }
    }

    public function saveTargets(Request $request): JsonResponse
    {
        $user = $request->user();
        $shopId = $this->shops->applyForRequest($user, $request);

        try {
            $validated = $request->validate([
                'year_month' => ['required', 'date_format:Y-m'],
                'operational_amount' => ['nullable', 'numeric', 'min:0'],
                'target_net_profit' => ['nullable', 'numeric', 'min:0'],
                'target_gross' => ['nullable', 'numeric', 'min:0'],
                'target_units' => ['nullable', 'integer', 'min:0'],
                'ad_budget_cap' => ['nullable', 'numeric', 'min:0'],
                'notes' => ['nullable', 'string', 'max:3000'],
            ]);

            $target = $this->targets->save($shopId, $validated['year_month'], $validated);
            $dashboardRequest = clone $request;
            $dashboardData = $this->targets->dashboard($dashboardRequest, $validated['year_month']);

            return $this->success([
                'message' => 'Target bulanan berhasil disimpan.',
                'shop' => [
                    'shop_id' => $shopId,
                    'label' => ShopeeShopContext::shopLabel($shopId),
                ],
                'year_month' => $validated['year_month'],
                'form' => [
                    'operational_amount' => $this->toIntOrNull($target->operational_amount),
                    'target_net_profit' => $this->toIntOrNull($target->target_net_profit),
                    'target_gross' => $this->toIntOrNull($target->target_gross),
                    'target_units' => $target->target_units,
                    'ad_budget_cap' => $this->toIntOrNull($target->ad_budget_cap),
                    'notes' => $target->notes,
                ],
                'actual' => $dashboardData['actual'] ?? [],
                'progress' => $dashboardData['progress'] ?? [],
                'pace' => $dashboardData['pace'] ?? [],
            ], meta: [
                'active_shop_id' => $shopId,
            ]);
        } finally {
            ShopeeShopContext::clearForcedShopId();
        }
    }

    public function hppPriority(Request $request): JsonResponse
    {
        $user = $request->user();
        $shopId = $this->shops->applyForRequest($user, $request);

        try {
            $validated = $request->validate([
                'search' => ['nullable', 'string', 'max:255'],
                'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            ]);

            $limit = (int) ($validated['limit'] ?? 20);
            $search = trim((string) ($validated['search'] ?? ''));

            $statsQuery = Product::query();
            ShopeeShopContext::scopeProducts($statsQuery);

            $priorityIds = $this->priorityProductIds($request);

            $query = Product::query()->orderBy('name');
            ShopeeShopContext::scopeProducts($query);

            if ($search !== '') {
                $query->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', '%' . $search . '%')
                        ->orWhere('external_sku', 'like', '%' . $search . '%')
                        ->orWhere('category', 'like', '%' . $search . '%');
                });
            }

            $products = $query->get()
                ->sortBy([
                    fn (Product $product) => !in_array($product->id, $priorityIds, true),
                    fn (Product $product) => $product->hpp_amount !== null,
                    fn (Product $product) => strtolower((string) $product->name),
                ])
                ->take($limit)
                ->values();

            $total = (clone $statsQuery)->count();
            $withHpp = (clone $statsQuery)->whereNotNull('hpp_amount')->count();

            return $this->success([
                'shop' => [
                    'shop_id' => $shopId,
                    'label' => ShopeeShopContext::shopLabel($shopId),
                ],
                'summary' => [
                    'total' => $total,
                    'with_hpp' => $withHpp,
                    'missing' => max(0, $total - $withHpp),
                    'complete_pct' => $total > 0 ? round($withHpp / $total, 4) : null,
                ],
                'products' => $products->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->external_sku,
                    'category' => $product->category,
                    'base_price' => $this->toIntOrNull($product->base_price),
                    'hpp_amount' => $this->toIntOrNull($product->hpp_amount),
                    'packaging_type' => $product->packaging_type ?: 'fixed',
                    'packaging_value' => $this->toIntOrNull($product->packaging_value),
                    'missing_hpp' => $product->hpp_amount === null,
                    'is_priority' => in_array($product->id, $priorityIds, true),
                ])->all(),
            ], meta: [
                'active_shop_id' => $shopId,
            ]);
        } finally {
            ShopeeShopContext::clearForcedShopId();
        }
    }

    public function saveHppBulk(Request $request): JsonResponse
    {
        $user = $request->user();
        $shopId = $this->shops->applyForRequest($user, $request);

        try {
            $validated = $request->validate([
                'products' => ['required', 'array', 'min:1'],
                'products.*.id' => ['required', 'integer', 'exists:products,id'],
                'products.*.hpp_amount' => ['nullable', 'numeric', 'min:0'],
                'products.*.packaging_type' => ['nullable', 'in:fixed,percent'],
                'products.*.packaging_value' => ['nullable', 'numeric', 'min:0'],
            ]);

            $productIds = collect($validated['products'])->pluck('id')->all();
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            ShopeeShopContext::scopeProducts($scopedProducts = Product::query()->whereIn('id', $productIds));
            $allowedIds = $scopedProducts->pluck('id')->all();

            $updated = 0;

            foreach ($validated['products'] as $row) {
                if (!in_array($row['id'], $allowedIds, true)) {
                    continue;
                }

                /** @var Product|null $product */
                $product = $products->get($row['id']);
                if (!$product) {
                    continue;
                }

                $product->update([
                    'hpp_amount' => $row['hpp_amount'] !== '' && $row['hpp_amount'] !== null ? $row['hpp_amount'] : null,
                    'packaging_type' => $row['packaging_type'] ?? 'fixed',
                    'packaging_value' => $row['packaging_value'] !== '' && $row['packaging_value'] !== null ? $row['packaging_value'] : null,
                ]);

                $updated++;
            }

            return $this->success([
                'message' => 'Quick HPP berhasil disimpan.',
                'updated_count' => $updated,
            ], meta: [
                'active_shop_id' => $shopId,
            ]);
        } finally {
            ShopeeShopContext::clearForcedShopId();
        }
    }

    public function alerts(Request $request): JsonResponse
    {
        $user = $request->user();
        $shopId = $this->shops->applyForRequest($user, $request);

        try {
            $validated = $request->validate([
                'month' => ['nullable', 'date_format:Y-m'],
                'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            ]);

            $month = (string) ($validated['month'] ?? now()->format('Y-m'));
            $limit = (int) ($validated['limit'] ?? 30);

            $this->alerts->checkShop($shopId);

            $dashboardRequest = clone $request;
            $dashboardData = $this->targets->dashboard($dashboardRequest, $month);
            $report = $dashboardData['report'] ?? $this->reportService->build($dashboardRequest);
            $actions = $this->actionCenter->build($report);

            $alerts = CeoAlertLog::query()
                ->where('shop_id', $shopId)
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();

            $readMap = MobileAlertRead::query()
                ->where('user_id', $user->id)
                ->whereIn('ceo_alert_log_id', $alerts->pluck('id'))
                ->get()
                ->keyBy('ceo_alert_log_id');

            $mappedAlerts = $alerts->map(function (CeoAlertLog $alert) use ($readMap): array {
                $read = $readMap->get($alert->id);

                return [
                    'id' => $alert->id,
                    'severity' => $alert->severity,
                    'title' => $alert->title,
                    'message' => $alert->message,
                    'sent_at' => optional($alert->sent_at)->toIso8601String(),
                    'created_at' => optional($alert->created_at)->toIso8601String(),
                    'is_read' => $read !== null,
                    'read_at' => $read ? optional($read->read_at)->toIso8601String() : null,
                ];
            })->values();

            return $this->success([
                'shop' => [
                    'shop_id' => $shopId,
                    'label' => ShopeeShopContext::shopLabel($shopId),
                ],
                'month' => $month,
                'summary' => [
                    'total' => $mappedAlerts->count(),
                    'unread' => $mappedAlerts->where('is_read', false)->count(),
                    'danger' => $mappedAlerts->where('severity', 'danger')->count(),
                    'warning' => $mappedAlerts->where('severity', 'warning')->count(),
                    'info' => $mappedAlerts->where('severity', 'info')->count(),
                ],
                'alerts' => $mappedAlerts->all(),
                'recommended_actions' => $this->mapRecommendedActions($actions),
            ], meta: [
                'active_shop_id' => $shopId,
            ]);
        } finally {
            ShopeeShopContext::clearForcedShopId();
        }
    }

    public function markAlertsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $shopId = $this->shops->applyForRequest($user, $request);

        try {
            $validated = $request->validate([
                'alert_ids' => ['nullable', 'array'],
                'alert_ids.*' => ['integer', 'exists:ceo_alert_logs,id'],
                'mark_all' => ['nullable', 'boolean'],
            ]);

            $alertIds = collect($validated['alert_ids'] ?? [])->map(fn ($id) => (int) $id);
            if ((bool) ($validated['mark_all'] ?? false)) {
                $alertIds = CeoAlertLog::query()
                    ->where('shop_id', $shopId)
                    ->pluck('id');
            }

            $alertIds = CeoAlertLog::query()
                ->where('shop_id', $shopId)
                ->whereIn('id', $alertIds->all())
                ->pluck('id');

            $updated = 0;
            foreach ($alertIds as $alertId) {
                MobileAlertRead::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'ceo_alert_log_id' => $alertId,
                    ],
                    [
                        'read_at' => now(),
                    ],
                );
                $updated++;
            }

            return $this->success([
                'message' => 'Alert berhasil ditandai sudah dibaca.',
                'updated_count' => $updated,
            ], meta: [
                'active_shop_id' => $shopId,
            ]);
        } finally {
            ShopeeShopContext::clearForcedShopId();
        }
    }

    public function decisions(Request $request): JsonResponse
    {
        $user = $request->user();
        $shopId = $this->shops->applyForRequest($user, $request);

        try {
            $validated = $request->validate([
                'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            ]);

            $limit = (int) ($validated['limit'] ?? 30);
            $logs = BusinessDecisionLog::query()
                ->where('shop_id', $shopId)
                ->with('product:id,name')
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();

            return $this->success([
                'shop' => [
                    'shop_id' => $shopId,
                    'label' => ShopeeShopContext::shopLabel($shopId),
                ],
                'decision_types' => [
                    'pricing',
                    'ads',
                    'inventory',
                    'hpp',
                    'budget',
                    'strategy',
                    'other',
                ],
                'decisions' => $logs->map(fn (BusinessDecisionLog $log) => [
                    'id' => $log->id,
                    'decision_type' => $log->decision_type,
                    'title' => $log->title,
                    'note' => $log->note,
                    'created_at' => optional($log->created_at)->toIso8601String(),
                    'product' => $log->product ? [
                        'id' => $log->product->id,
                        'name' => $log->product->name,
                    ] : null,
                ])->all(),
            ], meta: [
                'active_shop_id' => $shopId,
            ]);
        } finally {
            ShopeeShopContext::clearForcedShopId();
        }
    }

    public function storeDecision(Request $request): JsonResponse
    {
        $user = $request->user();
        $shopId = $this->shops->applyForRequest($user, $request);

        try {
            $validated = $request->validate([
                'decision_type' => ['required', 'string', 'max:64'],
                'title' => ['required', 'string', 'max:255'],
                'note' => ['nullable', 'string', 'max:5000'],
                'product_id' => ['nullable', 'integer', 'exists:products,id'],
            ]);

            $decision = $this->decisions->log([
                ...$validated,
                'shop_id' => $shopId,
            ])->load('product:id,name');

            return $this->success([
                'message' => 'Keputusan berhasil dicatat.',
                'decision' => [
                    'id' => $decision->id,
                    'decision_type' => $decision->decision_type,
                    'title' => $decision->title,
                    'note' => $decision->note,
                    'created_at' => optional($decision->created_at)->toIso8601String(),
                    'product' => $decision->product ? [
                        'id' => $decision->product->id,
                        'name' => $decision->product->name,
                    ] : null,
                ],
            ], meta: [
                'active_shop_id' => $shopId,
            ]);
        } finally {
            ShopeeShopContext::clearForcedShopId();
        }
    }

    private function buildAlerts(array $report, array $actions): array
    {
        $alerts = [];

        foreach (($report['analysis']['insights'] ?? []) as $item) {
            $alerts[] = [
                'type' => $item['type'] ?? 'info',
                'title' => $item['title'] ?? '',
                'text' => $item['text'] ?? '',
            ];
        }

        foreach (($actions['data_blockers'] ?? []) as $item) {
            $alerts[] = [
                'type' => $item['type'] ?? 'warning',
                'title' => $item['title'] ?? '',
                'text' => $item['text'] ?? '',
            ];
        }

        return array_slice($alerts, 0, 10);
    }

    private function mapProducts(array $rows): array
    {
        return array_map(function (array $row): array {
            $tier = $row['tier'] ?? $this->classifier->classify($row);

            return [
                'product_id' => (int) ($row['product_id'] ?? 0),
                'name' => (string) ($row['name'] ?? ''),
                'sku' => (string) ($row['sku'] ?? ''),
                'tier' => $tier,
                'qty' => (int) ($row['qty'] ?? 0),
                'gross' => (int) round((float) ($row['gross'] ?? 0)),
                'net_profit' => (int) round((float) ($row['net_profit'] ?? 0)),
                'margin' => (float) ($row['margin'] ?? 0),
                'roas' => isset($row['roas']) ? (float) $row['roas'] : null,
                'ads_spend' => (int) round((float) ($row['ads_spend'] ?? 0)),
                'missing_cost' => (bool) ($row['missing_cost'] ?? false),
                'action' => isset($row['action']) ? [
                    'code' => $row['action']['code'] ?? null,
                    'title' => $row['action']['title'] ?? null,
                    'severity' => $row['action']['severity'] ?? null,
                ] : null,
            ];
        }, $rows);
    }

    private function mapRecommendedActions(array $actions): array
    {
        $items = [];

        foreach (($actions['data_blockers'] ?? []) as $item) {
            $items[] = [
                'type' => 'data_blocker',
                'severity' => $item['type'] ?? 'warning',
                'title' => $item['title'] ?? '',
                'text' => $item['text'] ?? '',
                'product_id' => null,
                'product_name' => null,
                'action_code' => $item['route'] ?? null,
            ];
        }

        foreach (($actions['urgent'] ?? []) as $item) {
            $items[] = [
                'type' => 'product',
                'severity' => $item['action']['severity'] ?? ($item['missing_cost'] ?? false ? 'warning' : 'info'),
                'title' => $item['name'] ?? '',
                'text' => $item['action']['title'] ?? 'Review product action',
                'product_id' => (int) ($item['product_id'] ?? 0),
                'product_name' => $item['name'] ?? null,
                'action_code' => $item['action']['code'] ?? null,
            ];
        }

        return array_slice($items, 0, 10);
    }

    private function buildRekapSummary(?string $monthKey, array $current, array $previous, array $targets): array
    {
        return [
            'month' => $monthKey,
            'label' => $current['label'] ?? $monthKey,
            'gross' => $current['gross'] ?? null,
            'net_profit' => $current['net_profit'] ?? null,
            'orders' => $current['orders'] ?? null,
            'units' => $current['units'] ?? null,
            'roas' => $current['roas'] ?? null,
            'net_margin_pct' => $current['net_margin_pct'] ?? null,
            'target_gross' => $targets['target_gross'] ?? null,
            'target_net_profit' => $targets['target_net_profit'] ?? null,
            'target_units' => $targets['target_units'] ?? null,
            'ad_budget' => $targets['ad_budget'] ?? null,
            'deltas' => [
                [
                    'key' => 'gross',
                    'label' => 'Gross',
                    'format' => 'rp',
                    'value' => $this->deltaValue($current['gross'] ?? null, $previous['gross'] ?? null),
                ],
                [
                    'key' => 'net_profit',
                    'label' => 'Net profit',
                    'format' => 'rp',
                    'value' => $this->deltaValue($current['net_profit'] ?? null, $previous['net_profit'] ?? null),
                ],
                [
                    'key' => 'orders',
                    'label' => 'Orders',
                    'format' => 'num',
                    'value' => $this->deltaValue($current['orders'] ?? null, $previous['orders'] ?? null),
                ],
                [
                    'key' => 'roas',
                    'label' => 'ROAS',
                    'format' => 'x',
                    'value' => $this->deltaValue($current['roas'] ?? null, $previous['roas'] ?? null),
                ],
            ],
        ];
    }

    private function buildRekapMonthCard(
        string $monthKey,
        array $column,
        array $targets,
        bool $isCurrent,
    ): array {
        $gross = $column['gross'] ?? null;
        $netProfit = $column['net_profit'] ?? null;
        $units = $column['units'] ?? null;
        $ads = $column['ads'] ?? null;

        return [
            'month' => $monthKey,
            'label' => $column['label'] ?? $monthKey,
            'is_current' => $isCurrent,
            'gross' => $gross,
            'net_profit' => $netProfit,
            'orders' => $column['orders'] ?? null,
            'units' => $units,
            'roas' => $column['roas'] ?? null,
            'net_margin_pct' => $column['net_margin_pct'] ?? null,
            'ads' => $ads,
            'ads_ratio' => $column['ads_ratio'] ?? null,
            'aov_gross' => $column['aov_gross'] ?? null,
            'basket_size' => $column['basket_size'] ?? null,
            'target_gross' => $targets['target_gross'] ?? null,
            'target_net_profit' => $targets['target_net_profit'] ?? null,
            'target_units' => $targets['target_units'] ?? null,
            'ad_budget' => $targets['ad_budget'] ?? null,
            'gross_progress_pct' => $this->progressValue($gross, $targets['target_gross'] ?? null),
            'net_progress_pct' => $this->progressValue($netProfit, $targets['target_net_profit'] ?? null),
            'units_progress_pct' => $this->progressValue($units, $targets['target_units'] ?? null),
            'ads_budget_used_pct' => $this->progressValue($ads, $targets['ad_budget'] ?? null),
        ];
    }

    private function buildRekapMetricSections(array $monthKeys, array $columns): array
    {
        $groups = [
            [
                'key' => 'profitability',
                'title' => 'Profitability',
                'subtitle' => 'Pendapatan, laba, dan margin per bulan.',
                'metrics' => [
                    ['key' => 'gross', 'label' => 'Total pendapatan', 'format' => 'rp'],
                    ['key' => 'net', 'label' => 'Total penghasilan net', 'format' => 'rp'],
                    ['key' => 'gross_profit', 'label' => 'Laba kotor', 'format' => 'rp'],
                    ['key' => 'net_profit', 'label' => 'Laba bersih', 'format' => 'rp'],
                    ['key' => 'gross_margin_pct', 'label' => 'Gross margin', 'format' => 'pct'],
                    ['key' => 'net_margin_pct', 'label' => 'Net margin', 'format' => 'pct'],
                ],
            ],
            [
                'key' => 'efficiency',
                'title' => 'Efficiency',
                'subtitle' => 'Biaya, rasio, dan efektivitas iklan.',
                'metrics' => [
                    ['key' => 'fee_total', 'label' => 'Admin & layanan', 'format' => 'rp'],
                    ['key' => 'fee_ratio', 'label' => 'Rasio admin', 'format' => 'pct'],
                    ['key' => 'operational', 'label' => 'Operasional', 'format' => 'rp'],
                    ['key' => 'operational_ratio', 'label' => 'Rasio operasional', 'format' => 'pct'],
                    ['key' => 'ads', 'label' => 'Iklan', 'format' => 'rp'],
                    ['key' => 'ads_ratio', 'label' => 'Rasio iklan', 'format' => 'pct'],
                    ['key' => 'roas', 'label' => 'ROAS', 'format' => 'x'],
                    ['key' => 'acos', 'label' => 'ACOS', 'format' => 'pct'],
                ],
            ],
            [
                'key' => 'demand',
                'title' => 'Demand',
                'subtitle' => 'Pesanan, unit, dan kualitas keranjang belanja.',
                'metrics' => [
                    ['key' => 'orders', 'label' => 'Orders', 'format' => 'num'],
                    ['key' => 'units', 'label' => 'Units sold', 'format' => 'num'],
                    ['key' => 'aov_gross', 'label' => 'AOV kotor', 'format' => 'rp'],
                    ['key' => 'basket_size', 'label' => 'Basket size', 'format' => 'num'],
                ],
            ],
        ];

        return array_map(function (array $group) use ($monthKeys, $columns): array {
            return [
                'key' => $group['key'],
                'title' => $group['title'],
                'subtitle' => $group['subtitle'],
                'rows' => array_map(function (array $metric) use ($monthKeys, $columns): array {
                    return [
                        'key' => $metric['key'],
                        'label' => $metric['label'],
                        'format' => $metric['format'],
                        'points' => array_map(function (string $monthKey) use ($columns, $metric): array {
                            $column = $columns[$monthKey] ?? [];

                            return [
                                'month' => $monthKey,
                                'label' => $column['short'] ?? $monthKey,
                                'value' => $column[$metric['key']] ?? null,
                            ];
                        }, $monthKeys),
                    ];
                }, $group['metrics']),
            ];
        }, $groups);
    }

    private function deltaValue(mixed $current, mixed $previous): ?float
    {
        if (!is_numeric($current) || !is_numeric($previous)) {
            return null;
        }

        return round((float) $current - (float) $previous, 4);
    }

    private function progressValue(mixed $actual, mixed $target): ?float
    {
        if (!is_numeric($actual) || !is_numeric($target) || (float) $target <= 0.0) {
            return null;
        }

        return round((float) $actual / (float) $target, 4);
    }

    private function priorityProductIds(Request $request): array
    {
        $month = (string) $request->query('month', now()->format('Y-m'));
        $dashboardRequest = clone $request;
        $dashboardData = $this->targets->dashboard($dashboardRequest, $month);
        $report = $dashboardData['report'] ?? $this->reportService->build($dashboardRequest);
        $actions = $this->actionCenter->build($report);

        return collect([
            ...($actions['urgent'] ?? []),
            ...($actions['bleeders'] ?? []),
            ...($report['top_products'] ?? []),
        ])
            ->pluck('product_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function toIntOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) round((float) $value);
    }
}
