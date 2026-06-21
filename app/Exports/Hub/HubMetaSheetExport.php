<?php

namespace App\Exports\Hub;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HubMetaSheetExport implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    /** @param array<string, mixed> $package */
    public function __construct(private readonly array $package)
    {
    }

    public function title(): string
    {
        return 'Info Laporan';
    }

    public function array(): array
    {
        $rows = [
            ['Shopee Profit Hub — Laporan Profesional'],
            [],
            ['Judul', $this->package['title'] ?? ''],
        ];

        if (!empty($this->package['subtitle'])) {
            $rows[] = ['Subjudul', $this->package['subtitle']];
        }

        $rows[] = [];
        $rows[] = ['Informasi', ''];

        foreach ($this->package['meta'] ?? [] as $item) {
            $rows[] = [$item['label'] ?? '', $item['value'] ?? ''];
        }

        $rows[] = [];
        $rows[] = ['Dicetak pada', now()->translatedFormat('d F Y H:i')];
        $rows[] = ['Aplikasi', 'Shopee Profit Hub v7'];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('9A2542');
        $sheet->getStyle('A4:B4')->getFont()->setBold(true);
        $sheet->getStyle('A4:B4')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('F8E8ED');
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(48);
        $sheet->getStyle('A:B')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        return [];
    }
}
