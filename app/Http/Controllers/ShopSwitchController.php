<?php

namespace App\Http\Controllers;

use App\Support\ShopeeShopContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ShopSwitchController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $shopId = (int) $request->input('shop_id', 0);

        if (ShopeeShopContext::isValidShop($shopId)) {
            ShopeeShopContext::setShopId($shopId);
        }

        return redirect()->back();
    }
}
