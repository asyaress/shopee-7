<?php

namespace App\Exports\Hub;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class HubWorkbookExport implements WithMultipleSheets
{
    /** @param array<string, mixed> $package */
    public function __construct(private readonly array $package)
    {
    }

    public function sheets(): array
    {
        $sheets = [new HubMetaSheetExport($this->package)];

        foreach ($this->package['sheets'] ?? [] as $sheet) {
            $sheets[] = new HubDataSheetExport(
                (string) ($sheet['title'] ?? 'Data'),
                (array) ($sheet['headings'] ?? []),
                (array) ($sheet['rows'] ?? []),
                (array) ($sheet['column_types'] ?? []),
            );
        }

        return $sheets;
    }
}
