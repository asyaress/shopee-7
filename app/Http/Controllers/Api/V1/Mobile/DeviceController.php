<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Models\MobilePushDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends BaseMobileController
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platform' => ['required', 'string', 'in:android,ios'],
            'device_name' => ['required', 'string', 'max:100'],
            'push_token' => ['nullable', 'string', 'max:500'],
            'push_enabled' => ['nullable', 'boolean'],
            'app_version' => ['nullable', 'string', 'max:32'],
        ]);

        $device = MobilePushDevice::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'platform' => $validated['platform'],
                'device_name' => $validated['device_name'],
            ],
            [
                'push_token' => $validated['push_token'] ?? null,
                'push_enabled' => (bool) ($validated['push_enabled'] ?? false),
                'app_version' => $validated['app_version'] ?? null,
                'last_seen_at' => now(),
            ],
        );

        return $this->success([
            'message' => 'Device mobile berhasil diregistrasikan.',
            'device' => [
                'id' => $device->id,
                'platform' => $device->platform,
                'device_name' => $device->device_name,
                'push_enabled' => $device->push_enabled,
                'app_version' => $device->app_version,
                'last_seen_at' => optional($device->last_seen_at)->toIso8601String(),
            ],
        ]);
    }
}
