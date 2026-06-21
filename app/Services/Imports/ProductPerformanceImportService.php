<?php

namespace App\Services\Imports;

use App\Models\Product;
use App\Models\ShopeeProductPerformance;
use App\Support\ShopeeShopContext;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Import export Performa Produk Shopee (format BCG ROAS sheet "Data").
 */
class ProductPerformanceImportService
{
    /** @var array<string, string> */
    private array $columnMap = [
        'kode produk' => 'item_id',
        'produk' => 'name',
        'sku induk' => 'parent_sku',
        'pengunjung produk (kunjungan)' => 'visitors',
        'pengunjung produk' => 'visitors',
        'halaman produk dilihat' => 'page_views',
        'total penjualan (pesanan dibuat) (idr)' => 'sales_gmv',
        'penjualan (pesanan siap dikirim) (idr)' => 'sales_gmv_alt',
        'jumlah produk terjual' => 'units_sold',
        'produk terjual' => 'units_sold',
        'tingkat konversi' => 'conversion_rate',
    ];

    public function import(UploadedFile $file, ?Carbon $periodStart = null, ?Carbon $periodEnd = null): array
    {
        $shopId = ShopeeShopContext::shopId();
        if ($shopId <= 0) {
            throw new \RuntimeException('Pilih toko aktif terlebih dahulu.');
        }

        $periodEnd = $periodEnd ?? now()->endOfDay();
        $periodStart = $periodStart ?? now()->startOfMonth()->startOfDay();

        $path = $file->getRealPath();
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($path)->getActiveSheet();

        $headers = [];
        $headerRow = 1;
        foreach ($sheet->getRowIterator(1, 1) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $headers[$cell->getColumn()] = $this->normHeader((string) $cell->getValue());
            }
        }

        $colIndex = [];
        foreach ($headers as $col => $label) {
            if (isset($this->columnMap[$label])) {
                $colIndex[$this->columnMap[$label]] = $col;
            }
        }

        if (!isset($colIndex['item_id'])) {
            throw new \RuntimeException('Kolom "Kode Produk" tidak ditemukan. Pastikan file export Performa Produk Shopee.');
        }

        $imported = 0;
        $aggregated = [];

        $maxRow = min((int) $sheet->getHighestRow(), 50000);
        for ($r = $headerRow + 1; $r <= $maxRow; $r++) {
            $itemId = $this->cellInt($sheet, $colIndex['item_id'] . $r);
            if ($itemId <= 0) {
                continue;
            }

            if (!isset($aggregated[$itemId])) {
                $aggregated[$itemId] = [
                    'item_id' => $itemId,
                    'name' => $this->cellStr($sheet, ($colIndex['name'] ?? 'B') . $r),
                    'parent_sku' => $this->cellStr($sheet, ($colIndex['parent_sku'] ?? '') . $r),
                    'visitors' => 0,
                    'page_views' => 0,
                    'units_sold' => 0,
                    'sales_gmv' => 0.0,
                    'conversion_rate' => null,
                ];
            }

            if (isset($colIndex['visitors'])) {
                $aggregated[$itemId]['visitors'] = max(
                    $aggregated[$itemId]['visitors'],
                    $this->cellInt($sheet, $colIndex['visitors'] . $r)
                );
            }
            if (isset($colIndex['page_views'])) {
                $aggregated[$itemId]['page_views'] = max(
                    $aggregated[$itemId]['page_views'],
                    $this->cellInt($sheet, $colIndex['page_views'] . $r)
                );
            }
            if (isset($colIndex['units_sold'])) {
                $aggregated[$itemId]['units_sold'] += $this->cellInt($sheet, $colIndex['units_sold'] . $r);
            }
            if (isset($colIndex['sales_gmv'])) {
                $aggregated[$itemId]['sales_gmv'] = max(
                    $aggregated[$itemId]['sales_gmv'],
                    $this->cellFloat($sheet, $colIndex['sales_gmv'] . $r)
                );
            }
            if (isset($colIndex['conversion_rate'])) {
                $aggregated[$itemId]['conversion_rate'] = $this->cellFloat($sheet, $colIndex['conversion_rate'] . $r);
            }
        }

        foreach ($aggregated as $data) {
            $product = Product::query()
                ->where('external_platform', 'shopee')
                ->where('external_shop_id', $shopId)
                ->where('external_item_id', $data['item_id'])
                ->first();

            $conv = $data['conversion_rate'];
            if ($conv !== null && $conv > 1) {
                $conv = $conv / 100;
            }
            if (($conv === null || $conv <= 0) && $data['visitors'] > 0) {
                $conv = $data['units_sold'] / $data['visitors'];
            }

            ShopeeProductPerformance::updateOrCreate(
                [
                    'shop_id' => $shopId,
                    'external_item_id' => $data['item_id'],
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                ],
                [
                    'source' => ShopeeProductPerformance::SOURCE_IMPORT,
                    'product_id' => $product?->id,
                    'product_name' => $data['name'],
                    'parent_sku' => $data['parent_sku'] ?: null,
                    'visitors' => $data['visitors'],
                    'page_views' => $data['page_views'],
                    'units_sold' => $data['units_sold'],
                    'sales_gmv' => $data['sales_gmv'],
                    'conversion_rate' => $conv,
                ]
            );
            $imported++;
        }

        return [
            'imported' => $imported,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'source' => ShopeeProductPerformance::SOURCE_IMPORT,
        ];
    }

    public function importSettlementCsv(UploadedFile $file): array
    {
        $shopId = ShopeeShopContext::shopId();
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            throw new \RuntimeException('File tidak bisa dibaca.');
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            throw new \RuntimeException('File CSV kosong.');
        }

        $map = [];
        foreach ($header as $i => $h) {
            $map[$this->normHeader($h)] = $i;
        }

        $snIdx = $map['no. pesanan'] ?? $map['no pesanan'] ?? null;
        $dateIdx = $map['tanggal dana dilepaskan'] ?? $map['tanggal dana dilepas'] ?? null;
        $netIdx = $map['total penghasilan'] ?? $map['jumlah total penghasilan'] ?? null;

        if ($snIdx === null) {
            fclose($handle);
            throw new \RuntimeException('Kolom No. Pesanan tidak ditemukan.');
        }

        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $orderSn = trim($row[$snIdx] ?? '');
            if ($orderSn === '') {
                continue;
            }

            $releasedAt = null;
            if ($dateIdx !== null && !empty($row[$dateIdx])) {
                try {
                    $releasedAt = Carbon::parse($row[$dateIdx]);
                } catch (\Throwable) {
                    $releasedAt = null;
                }
            }

            $net = $netIdx !== null ? (float) preg_replace('/[^\d.-]/', '', (string) ($row[$netIdx] ?? 0)) : 0;

            \App\Models\ShopeeSettlementRelease::updateOrCreate(
                ['shop_id' => $shopId, 'order_sn' => $orderSn],
                [
                    'released_at' => $releasedAt,
                    'net_amount' => $net,
                    'source' => 'income_csv',
                ]
            );
            $count++;
        }
        fclose($handle);

        return ['imported' => $count];
    }

    private function normHeader(string $h): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $h)));
    }

    private function cellStr($sheet, string $coord): string
    {
        if ($coord === '') {
            return '';
        }
        $v = $sheet->getCell($coord)->getValue();

        return trim((string) ($v ?? ''));
    }

    private function cellInt($sheet, string $coord): int
    {
        return (int) round($this->cellFloat($sheet, $coord));
    }

    private function cellFloat($sheet, string $coord): float
    {
        $v = $sheet->getCell($coord)->getValue();
        if (is_numeric($v)) {
            return (float) $v;
        }

        return (float) preg_replace('/[^\d.-]/', '', (string) $v);
    }
}
