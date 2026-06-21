<?php

namespace App\Http\Controllers;

use App\Exports\Hub\HubWorkbookExport;
use App\Services\Reports\HubReportExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HubExportController extends Controller
{
    public function __construct(
        private readonly HubReportExportService $exports,
    ) {
    }

    public function download(Request $request, string $type): BinaryFileResponse|\Illuminate\Http\Response
    {
        $format = strtolower((string) $request->query('format', 'xlsx'));
        if (!in_array($format, ['xlsx', 'pdf'], true)) {
            abort(400, 'Format export harus xlsx atau pdf.');
        }

        try {
            $package = $this->exports->build($type, $request);
        } catch (\InvalidArgumentException $e) {
            abort(422, $e->getMessage());
        }

        $filename = ($package['filename'] ?? 'laporan') . '.' . $format;

        if ($format === 'pdf') {
            return Pdf::loadView('exports.hub.report', [
                'report' => $package,
                'generated_at' => now()->translatedFormat('d F Y H:i'),
            ])
                ->setPaper('a4', 'portrait')
                ->download($filename);
        }

        return Excel::download(new HubWorkbookExport($package), $filename);
    }
}
