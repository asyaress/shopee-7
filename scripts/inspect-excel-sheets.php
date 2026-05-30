<?php
require __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

foreach (glob(__DIR__ . '/../template-excel/*.xlsx') ?: [] as $path) {
    $r = IOFactory::createReaderForFile($path);
    echo basename($path) . ":\n  " . implode("\n  ", $r->listWorksheetNames($path)) . "\n\n";
}

// Sample key metrics from HASIL sheets
$targets = [
    'Salinan Salinan dari Salinan Salinan HASIL REKAP.xlsx' => ['HASIL', 'REKAP'],
    'RASIO KEUANGAN SHOPEE TERBARU (1).xlsx' => ['DASHBOARD INCOME', 'BEST SELLER', 'HSL HELPER'],
];

class SampleReadFilter implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
    public function __construct(private int $maxRow = 25, private string $maxCol = 'H') {}
    public function readCell($columnAddress, $row, $worksheetName = ''): bool {
        return $row <= $this->maxRow && \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($columnAddress)
            <= \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($this->maxCol);
    }
}

foreach ($targets as $file => $sheets) {
    $path = __DIR__ . '/../template-excel/' . $file;
    if (!is_file($path)) continue;
    echo "=== SAMPLE: $file ===\n";
    $reader = IOFactory::createReaderForFile($path);
    $reader->setReadDataOnly(true);
    $reader->setReadFilter(new SampleReadFilter(20, 'G'));
    $ss = $reader->load($path);
    foreach ($sheets as $name) {
        $sheet = $ss->getSheetByName($name);
        if (!$sheet) { echo "  [$name] not found\n"; continue; }
        echo "  [$name]\n";
        for ($r = 1; $r <= 20; $r++) {
            $cells = [];
            for ($c = 1; $c <= 7; $c++) {
                $v = $sheet->getCellByColumnAndRow($c, $r)->getValue();
                if ($v !== null && $v !== '') $cells[] = is_string($v) ? mb_substr(trim($v), 0, 50) : $v;
            }
            if ($cells) echo '    R'.$r.': '.implode(' | ', $cells)."\n";
        }
    }
    $ss->disconnectWorksheets();
}
