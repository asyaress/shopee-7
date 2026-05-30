<?php

namespace App\Support;

use App\Models\ShopeeToken;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ShopeeShopContext
{
    public const SESSION_KEY = 'shopee_active_shop_id';

    public static function env(): string
    {
        return (string) config('shopee.env', 'test');
    }

    /** @return Collection<int, ShopeeToken> */
    public static function tokens(): Collection
    {
        return ShopeeToken::query()
            ->where('env', static::env())
            ->orderBy('shop_id')
            ->get();
    }

    /** @return array<int, string> shop_id => label */
    public static function shopOptions(): array
    {
        $options = [];
        foreach (static::tokens() as $token) {
            $id = (int) $token->shop_id;
            if ($id > 0) {
                $options[$id] = static::shopLabel($id, $token);
            }
        }

        return $options;
    }

    public static function shopLabel(int $shopId, ?ShopeeToken $token = null): string
    {
        $names = (array) config('monitoring.shop_names', []);
        if (!empty($names[$shopId])) {
            return (string) $names[$shopId];
        }

        $token ??= static::tokenForShop($shopId);

        return 'Toko ' . $shopId;
    }

    public static function isValidShop(int $shopId): bool
    {
        if ($shopId <= 0) {
            return false;
        }

        return static::tokens()->contains(fn (ShopeeToken $t) => (int) $t->shop_id === $shopId);
    }

    public static function setShopId(int $shopId): void
    {
        if (!static::isValidShop($shopId)) {
            return;
        }

        session([static::SESSION_KEY => $shopId]);
    }

    public static function shopId(): int
    {
        $sessionId = (int) session(static::SESSION_KEY, 0);
        if ($sessionId > 0 && static::isValidShop($sessionId)) {
            return $sessionId;
        }

        $configured = (int) config('shopee.shop_id', 0);
        if ($configured > 0 && static::isValidShop($configured)) {
            return $configured;
        }

        $first = static::tokens()->first();

        return (int) ($first?->shop_id ?? 0);
    }

    public static function token(): ?ShopeeToken
    {
        $shopId = static::shopId();

        return $shopId > 0 ? static::tokenForShop($shopId) : static::tokens()->last();
    }

    public static function tokenForShop(int $shopId): ?ShopeeToken
    {
        return ShopeeToken::query()
            ->where('env', static::env())
            ->where('shop_id', $shopId)
            ->first();
    }

    public static function scopeProducts(Builder $query): Builder
    {
        $shopId = static::shopId();
        if ($shopId <= 0) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($shopId) {
            $q->where('external_shop_id', $shopId)
                ->orWhere(function (Builder $q2) use ($shopId) {
                    $q2->where('external_platform', 'shopee')
                        ->whereNull('external_shop_id');
                });
        });
    }
}
