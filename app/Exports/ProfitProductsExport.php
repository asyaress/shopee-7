<?php

namespace App\Exports;

use App\Models\Order;
use App\Services\Reports\ProfitReportService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProfitProductsExport implements FromArray, WithHeadings, ShouldAutoSize
{
    protected array $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function headings(): array
    {
        return [
            'Product ID',
            'Nama Produk',
            'Qty Terjual',
            'Gross (alokasi)',
            'Net (alokasi)',
            'COGS (HPP+Packaging)',
            'Profit',
            'Margin % (Profit/Net)',
        ];
    }

    public function array(): array
    {
        $svc = new ProfitReportService();
        $productAgg = [];

        $q = $this->buildQuery();

        $q->select(['id', 'order_date'])
            ->orderBy('id')
            ->chunkById(200, function ($chunk) use (&$productAgg, $svc) {
                $chunk->load(['orderItems.product.variants', 'shopeeFinancial']);

                foreach ($chunk as $order) {
                    $m = $svc->compute($order);

                    foreach (($m['alloc_by_product'] ?? []) as $pid => $row) {
                        if (!isset($productAgg[$pid])) {
                            $productAgg[$pid] = [
                                'product_id' => $row['product_id'],
                                'name' => $row['name'],
                                'qty' => 0,
                                'gross' => 0.0,
                                'net' => 0.0,
                                'cogs' => 0.0,
                                'profit' => 0.0,
                            ];
                        }
                        $productAgg[$pid]['qty'] += (int) ($row['qty'] ?? 0);
                        $productAgg[$pid]['gross'] += (float) ($row['gross'] ?? 0);
                        $productAgg[$pid]['net'] += (float) ($row['net'] ?? 0);
                        $productAgg[$pid]['cogs'] += (float) ($row['cogs'] ?? 0);
                        $productAgg[$pid]['profit'] += (float) ($row['profit'] ?? 0);
                    }
                }
            });

        $rows = array_values($productAgg);
        usort($rows, fn ($a, $b) => ($b['profit'] ?? 0) <=> ($a['profit'] ?? 0));

        $out = [];
        foreach ($rows as $r) {
            $net = (float) ($r['net'] ?? 0);
            $profit = (float) ($r['profit'] ?? 0);
            $margin = $net > 0 ? ($profit / $net) * 100 : 0;

            $out[] = [
                $r['product_id'],
                $r['name'],
                (int) $r['qty'],
                (float) $r['gross'],
                $net,
                (float) $r['cogs'],
                $profit,
                $margin,
            ];
        }

        return $out;
    }

    protected function buildQuery(): Builder
    {
        $start = $this->filters['start'] instanceof Carbon ? $this->filters['start'] : Carbon::parse($this->filters['start']);
        $end = $this->filters['end'] instanceof Carbon ? $this->filters['end'] : Carbon::parse($this->filters['end']);

        $q = Order::query()
            ->whereDate('order_date', '>=', $start->toDateString())
            ->whereDate('order_date', '<=', $end->toDateString());

        $status = $this->filters['status'] ?? 'completed';
        if ($status && $status !== 'all') {
            $q->where('status', $status);
        }

        $jenis = $this->filters['jenis'] ?? 'All';
        if ($jenis && $jenis !== 'All') {
            $q->where('jenis_transaksi', $jenis);
        }

        $pid = $this->filters['product_id'] ?? null;
        if ($pid) {
            $q->whereHas('orderItems', fn ($sub) => $sub->where('product_id', (int) $pid));
        }

        return $q;
    }
}
