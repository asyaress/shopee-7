<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

abstract class BaseMobileController extends Controller
{
    protected function success(array $data = [], int $status = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => array_merge([
                'server_time' => now()->toIso8601String(),
            ], $meta),
        ], $status);
    }
}
