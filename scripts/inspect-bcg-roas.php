<?php
require __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

class SampleFilter implements PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
    public function __construct(private int $maxR = 25, private int $maxC = 10) {}
    public function readCell($columnAddress, $row, $worksheetName = ''): bool {
        return $row <= $this->maxR
            && PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($columnAddress) <= $this->maxC;
    }
}

$path = __DIR__ . '/../template-excel/BCG ROAS V.20.xlsx';
$reader = IOFactory::createReaderForFile($path);
$reader->setReadDataOnly(true);
$reader->setReadFilter(new SampleFilter(30, 12));
$ss = $reader->load($path);

foreach (['ROAS', 'ROAS HLP', 'Input', 'STAR', 'CASHCOWS'] as $name) {
    echo "=== $name ===\n";
    $sheet = $ss->getSheetByName($name);
    if (!$sheet) { echo "not found\n\n"; continue; }
    for ($r = 1; $r <= 30; $r++) {
        $cells = [];
        for ($c = 1; $c <= 12; $c++) {
            $v = $sheet->getCellByColumnAndRow($c, $r)->getValue();
            if ($v !== null && $v !== '') {
                $cells[] = is_string($v) ? mb_substr(trim($v), 0, 60) : $v;
            }
        }
        if ($cells) {
            echo 'R' . $r . ': ' . implode(' | ', $cells) . "\n";
        }
    }
    echo "\n";
}
