<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProfitOrdersExport implements FromArray, WithHeadings, WithStyles
{
    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $rows;

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function headings(): array
    {
        if (empty($this->rows)) {
            return [
                'Tanggal', 'Order #', 'Jenis', 'Status',
                'Gross (Harga Produk)', 'Fee Total', 'Net (Penghasilan)',
                'COGS (HPP+Packaging)', 'Profit', 'Margin', 'Take Rate',
                'Missing Cost?'
            ];
        }
        return array_keys($this->rows[0]);
    }

    public function array(): array
    {
        // Convert associative rows -> indexed rows in heading order
        $headings = $this->headings();

        return array_map(function ($row) use ($headings) {
            $out = [];
            foreach ($headings as $h) {
                $out[] = $row[$h] ?? null;
            }
            return $out;
        }, $this->rows);
    }

    public function styles(Worksheet $sheet)
    {
        // Header style
        $sheet->getStyle('A1:L1')->getFont()->setBold(true);
        $sheet->freezePane('A2');

        // Auto width
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Number formats (best-effort, depends on the column order)
        // E: Gross, F: Fee, G: Net, H: COGS, I: Profit
        $sheet->getStyle('E:I')->getNumberFormat()->setFormatCode('#,##0');
        // J: Margin, K: Take Rate
        $sheet->getStyle('J:K')->getNumberFormat()->setFormatCode('0.00%');

        return [];
    }
}
