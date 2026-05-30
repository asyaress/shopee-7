<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MaintenanceController extends Controller
{
    public function run(Request $request): JsonResponse
    {
        $requestKey = (string) $request->query('key', '');
        $expectedKey = (string) config('services.internal_maintenance.key', '');

        if ($requestKey === '' || $expectedKey === '' || ! hash_equals($expectedKey, $requestKey)) {
            abort(403, 'Forbidden');
        }

        $deletedExpiredTokens = 0;
        $expirationMinutes = config('sanctum.expiration');

        if (
            is_numeric($expirationMinutes)
            && (int) $expirationMinutes > 0
            && Schema::hasTable('personal_access_tokens')
        ) {
            $deletedExpiredTokens = DB::table('personal_access_tokens')
                ->where('created_at', '<', now()->subMinutes((int) $expirationMinutes))
                ->delete();
        }

        return response()->json([
            'status' => 'ok',
            'server_time' => now()->toIso8601String(),
            'jobs' => [
                'deleted_expired_tokens' => $deletedExpiredTokens,
            ],
        ]);
    }
}
