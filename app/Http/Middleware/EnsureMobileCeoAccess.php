<?php

namespace App\Http\Middleware;

use App\Services\Mobile\MobileCeoAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMobileCeoAccess
{
    public function __construct(
        private readonly MobileCeoAccessService $access,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$this->access->allowed($user)) {
            return response()->json([
                'message' => 'Akses mobile CEO belum diizinkan untuk akun ini.',
                'meta' => [
                    'server_time' => now()->toIso8601String(),
                ],
            ], 403);
        }

        return $next($request);
    }
}
