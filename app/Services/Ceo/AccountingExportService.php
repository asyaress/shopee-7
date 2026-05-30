<?php

namespace App\Services\Ceo;

use App\Services\Reports\ProductProfitReportService;
use Illuminate\Http\Request;

class AccountingExportService
{
    public function __construct(
        private readonly ProductProfitReportService $reportService,
    ) {
    }

    public function journalRows(Request $request): array
    {
        $report = $this->reportService->build($request);
        $s = $report['summary'] ?? [];
        $fb = $report['fee_breakdown'] ?? [];
        $filters = $report['filters'] ?? [];

        $period = ($filters['start'] ?? '') . ' s/d ' . ($filters['end'] ?? '');

        return [
            ['Akun', 'Keterangan', 'Debit', 'Kredit', 'Periode'],
            ['4-1000', 'Penjualan kotor', (int) ($s['gross'] ?? 0), 0, $period],
            ['5-1000', 'Biaya platform Shopee', 0, (int) ($s['fee_total'] ?? 0), $period],
            ['5-1101', '  Administrasi', 0, (int) ($fb['admin'] ?? 0), $period],
            ['5-1102', '  Layanan', 0, (int) ($fb['layanan'] ?? 0), $period],
            ['5-1103', '  Proses', 0, (int) ($fb['proses'] ?? 0), $period],
            ['5-1104', '  Program hemat', 0, (int) ($fb['program_hemat'] ?? 0), $period],
            ['5-2000', 'HPP + packaging', 0, (int) ($s['cogs'] ?? 0), $period],
            ['5-3000', 'Biaya iklan', 0, (int) ($s['ads_total'] ?? 0), $period],
            ['5-4000', 'Biaya operasional', 0, (int) ($s['operational_total'] ?? 0), $period],
            ['', 'Laba bersih (setelah semua)', 0, 0, $period],
            ['', 'Net profit', max(0, (int) ($s['net_profit'] ?? 0)), 0, $period],
            ['', 'Rugi', max(0, -(int) ($s['net_profit'] ?? 0)), 0, $period],
        ];
    }
}
