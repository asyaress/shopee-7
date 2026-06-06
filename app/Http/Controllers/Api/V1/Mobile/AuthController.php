<?php

namespace App\Http\Controllers\Api\V1\Mobile;

use App\Models\User;
use App\Services\Mobile\MobileCeoAccessService;
use App\Services\Mobile\MobileShopContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseMobileController
{
    public function __construct(
        private readonly MobileCeoAccessService $access,
        private readonly MobileShopContextService $shops,
    ) {
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if (!$this->access->allowed($user)) {
            return response()->json([
                'message' => 'Akses mobile CEO belum diizinkan untuk akun ini.',
                'meta' => [
                    'server_time' => now()->toIso8601String(),
                ],
            ], 403);
        }

        $tokenName = 'mobile-ceo:' . $validated['device_name'];
        $user->tokens()->where('name', $tokenName)->delete();
        $token = $user->createToken($tokenName, ['mobile:ceo'])->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'active_shop_id' => $this->shops->activeShopIdFor($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->success([
            'message' => 'Logout berhasil.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'active_shop_id' => $this->shops->activeShopIdFor($user),
        ]);
    }
}
