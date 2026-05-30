<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SystemController extends Controller
{
    public function health(): JsonResponse
    {
        $databaseOk = true;
        $storageOk = true;
        $errors = [];

        try {
            DB::select('SELECT 1');
        } catch (\Throwable $exception) {
            $databaseOk = false;
            $errors[] = 'database_unreachable';
        }

        try {
            $disk = (string) config('services.buku_tamu.signature_disk', 'signatures');
            Storage::disk($disk)->makeDirectory('healthcheck');
        } catch (\Throwable $exception) {
            $storageOk = false;
            $errors[] = 'storage_unavailable';
        }

        $healthy = $databaseOk && $storageOk;

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'server_time' => now()->toIso8601String(),
            'checks' => [
                'database' => $databaseOk ? 'ok' : 'fail',
                'signature_storage' => $storageOk ? 'ok' : 'fail',
            ],
            'errors' => $errors,
        ], $healthy ? 200 : 503);
    }
}
