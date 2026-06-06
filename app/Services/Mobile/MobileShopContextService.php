<?php

namespace App\Services\Mobile;

use App\Models\MobileUserContext;
use App\Models\User;
use App\Support\ShopeeShopContext;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MobileShopContextService
{
    public function availableShops(User $user): array
    {
        $activeShopId = $this->activeShopIdFor($user);
        $shops = [];

        foreach (ShopeeShopContext::shopOptions() as $shopId => $label) {
            $shops[] = [
                'shop_id' => (int) $shopId,
                'label' => $label,
                'is_active' => (int) $shopId === $activeShopId,
            ];
        }

        return $shops;
    }

    public function activeShopIdFor(User $user): int
    {
        $shopId = (int) ($user->mobileContext?->active_shop_id ?? 0);
        if ($shopId > 0 && ShopeeShopContext::isValidShop($shopId)) {
            return $shopId;
        }

        return ShopeeShopContext::shopId();
    }

    public function setActiveShopId(User $user, int $shopId): int
    {
        $this->validateShopId($shopId);

        MobileUserContext::query()->updateOrCreate(
            ['user_id' => $user->id],
            ['active_shop_id' => $shopId]
        );

        return $shopId;
    }

    public function resolveRequestedShopId(User $user, Request $request): int
    {
        $requested = (int) ($request->input('shop_id', $request->query('shop_id', 0)));
        if ($requested > 0) {
            $this->validateShopId($requested);

            return $requested;
        }

        return $this->activeShopIdFor($user);
    }

    public function applyForRequest(User $user, Request $request): int
    {
        $shopId = $this->resolveRequestedShopId($user, $request);
        ShopeeShopContext::forceShopId($shopId);

        return $shopId;
    }

    private function validateShopId(int $shopId): void
    {
        if (!ShopeeShopContext::isValidShop($shopId)) {
            throw ValidationException::withMessages([
                'shop_id' => ['Shop tidak valid atau belum terhubung ke Shopee.'],
            ]);
        }
    }
}
