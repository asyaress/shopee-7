<?php

namespace App\Exports\Hub;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HubDataSheetExport implements FromArray, WithHeadings, WithTitle, WithStyles, ShouldAutoSize, WithColumnFormatting
{
    /** @param list<string> $headings @param list<list<mixed>> $rows @param list<string> $columnTypes */
    public function __construct(
        private readonly string $sheetTitle,
        private readonly array $headings,
        private readonly array $rows,
        private readonly array $columnTypes = [],
    ) {
    }

    public function title(): string
    {
        $title = preg_replace('/[\\\\\\/*\\[\\]:?]/', ' ', $this->sheetTitle) ?: 'Data';
        return mb_substr($title, 0, 31);
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function columnFormats(): array
    {
        $formats = [];
        foreach ($this->columnTypes as $idx => $type) {
            $code = match ($type) {
                'rp' => '#,##0',
                'pct' => '0.00%',
                'num' => '#,##0',
                'x' => '0.00',
                default => null,
            };
            if ($code) {
                $formats[$this->columnLetter($idx + 1)] = $code;
            }
        }

        return $formats;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = $this->columnLetter(max(1, count($this->headings)));
        $headerRange = "A1:{$lastCol}1";

        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('9A2542');
        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($headerRange);

        $lastRow = max(2, count($this->rows) + 1);
        if ($lastRow > 2) {
            for ($r = 2; $r <= $lastRow; $r++) {
                if ($r % 2 === 0) {
                    $sheet->getStyle("A{$r}:{$lastCol}{$r}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('FAFAFA');
                }
            }
        }

        return [];
    }

    private function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }
}
