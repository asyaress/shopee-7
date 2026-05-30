<?php

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class SampleReadFilter implements IReadFilter
{
    public function __construct(private int $maxRow = 15, private string $maxCol = 'J') {}

    public function readCell($columnAddress, $row, $worksheetName = ''): bool
    {
        if ($row > $this->maxRow) {
            return false;
        }
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($columnAddress)
            <= \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($this->maxCol);
    }
}

$dir = __DIR__ . '/../template-excel';
foreach (glob($dir . '/*.xlsx') ?: [] as $path) {
    echo "\n" . str_repeat('=', 70) . "\n";
    echo basename($path) . "\n";
    echo str_repeat('=', 70) . "\n";

    try {
        $reader = IOFactory::createReaderForFile($path);
        $names = $reader->listWorksheetNames($path);
        echo 'Sheets (' . count($names) . "):\n  - " . implode("\n  - ", $names) . "\n";

        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $reader->setReadFilter(new SampleReadFilter(12, 'J'));

        $spreadsheet = $reader->load($path);
        $sampleSheets = array_slice($names, 0, min(6, count($names)));

        foreach ($sampleSheets as $name) {
            $sheet = $spreadsheet->getSheetByName($name);
            if (!$sheet) {
                continue;
            }
            echo "\n  [$name]\n";
            $maxRow = min(12, (int) $sheet->getHighestRow());
            $maxColIdx = min(10, \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn() ?: 'A'));

            for ($r = 1; $r <= $maxRow; $r++) {
                $cells = [];
                for ($c = 1; $c <= $maxColIdx; $c++) {
                    try {
                        $v = $sheet->getCellByColumnAndRow($c, $r)->getValue();
                    } catch (Throwable) {
                        $v = null;
                    }
                    if ($v !== null && $v !== '') {
                        $cells[] = is_string($v) ? mb_substr(trim($v), 0, 45) : $v;
                    }
                }
                if ($cells) {
                    echo '    R' . $r . ': ' . implode(' | ', $cells) . "\n";
                }
            }
        }

        if (count($names) > 6) {
            echo "\n  ... +" . (count($names) - 6) . " sheet lain: " . implode(', ', array_slice($names, 6)) . "\n";
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    } catch (Throwable $e) {
        echo 'ERR: ' . $e->getMessage() . "\n";
    }
}
