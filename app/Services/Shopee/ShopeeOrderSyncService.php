<?php

namespace App\Services\Shopee;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShopeeToken;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShopeeOrderSyncService
{
    public function __construct(private readonly ShopeeClient $client)
    {
    }

    /**
     * Sync recent orders from Shopee into local orders + order_items.
     * Shopee API limits each get_order_list call to a 15-day window; longer ranges are chunked automatically.
     * Returns summary counters.
     */
    public function syncRecent(ShopeeToken $token, int $days = 7): array
    {
        $days = max(1, $days);
        $chunkDays = max(1, (int) config('shopee.order_list_max_days', 14));

        $timeTo = time();
        $overallFrom = $timeTo - ($days * 86400);

        $created = 0;
        $updated = 0;
        $processed = 0;

        for ($windowEnd = $timeTo; $windowEnd > $overallFrom; $windowEnd -= $chunkDays * 86400) {
            $windowStart = max($overallFrom, $windowEnd - ($chunkDays * 86400));

            $chunk = $this->syncTimeWindow($token, $windowStart, $windowEnd);
            $created += $chunk['created'];
            $updated += $chunk['updated'];
            $processed += $chunk['processed'];
        }

        return compact('created', 'updated', 'processed');
    }

    /**
     * @return array{created: int, updated: int, processed: int}
     */
    private function syncTimeWindow(ShopeeToken $token, int $timeFrom, int $timeTo): array
    {
        $cursor = '';
        $more = true;

        $created = 0;
        $updated = 0;
        $processed = 0;

        while ($more) {
            $params = [
                'time_range_field' => 'create_time',
                'time_from' => $timeFrom,
                'time_to' => $timeTo,
                'page_size' => 100,
            ];

            if ($cursor !== '') {
                $params['cursor'] = $cursor;
            }

            $listResp = $this->client->requestPrivate('GET', '/api/v2/order/get_order_list', $params, $token);

            $orderList = Arr::get($listResp, 'order_list', []);
            $orderSns = array_values(array_filter(array_map(fn ($o) => Arr::get($o, 'order_sn'), $orderList)));

            if (empty($orderSns)) {
                break;
            }

            foreach (array_chunk($orderSns, 50) as $chunk) {
                    $optionalFields = config('shopee.order_detail_optional_fields', []);
                    $optionalFields = is_array($optionalFields) ? $optionalFields : [];

                $detailParams = [
                    'order_sn_list' => implode(',', $chunk),
                ];

                // Optional fields bisa diatur via config/env (SHOPEE_ORDER_DETAIL_FIELDS)
                if (!empty($optionalFields)) {
                    $detailParams['response_optional_fields'] = implode(',', $optionalFields);
                }

                $detailResp = $this->client->requestPrivate('GET', '/api/v2/order/get_order_detail', $detailParams, $token);

                $orders = Arr::get($detailResp, 'order_list', []);

                foreach ($orders as $shopeeOrder) {
                    $processed++;

                    [$isCreated, $isUpdated] = $this->upsertOrder($shopeeOrder, $token);
                    $created += $isCreated ? 1 : 0;
                    $updated += $isUpdated ? 1 : 0;
                }
            }

            $cursor = (string) Arr::get($listResp, 'next_cursor', '');
            $more = (bool) Arr::get($listResp, 'more', false);
        }

        return compact('created', 'updated', 'processed');
    }

    private function upsertOrder(array $o, ShopeeToken $token): array
    {
        $orderSn = (string) Arr::get($o, 'order_sn');
        if ($orderSn === '') {
            return [false, false];
        }

        $orderStatus = (string) Arr::get($o, 'order_status', '');
        $mappedStatus = $this->mapStatus($orderStatus);

        // Prefer create_time; fallback to pay_time
        $ts = (int) (Arr::get($o, 'create_time') ?: Arr::get($o, 'pay_time') ?: time());
        $orderDate = Carbon::createFromTimestamp($ts, 'UTC')->timezone(config('app.timezone'));

        $buyer = (string) Arr::get($o, 'buyer_username', '');
        $recipientName = (string) Arr::get($o, 'recipient_address.name', '');
        $customerName = $buyer ?: $recipientName ?: 'Shopee Buyer';

        $shippingCarrier = (string) Arr::get($o, 'shipping_carrier', '');
        $note = (string) Arr::get($o, 'note', '');

        $items = Arr::get($o, 'item_list', []);
        $items = is_array($items) ? $items : [];

        // Hitung subtotal dari item_list (paling aman sebagai fallback)
        $itemTotal = 0.0;
        foreach ($items as $item) {
            $qty = (int) (Arr::get($item, 'model_quantity_purchased') ?? Arr::get($item, 'quantity') ?? 1);
            $qty = max(1, $qty);
            $unitPrice = $this->firstNumber($item, [
                'model_discounted_price',
                'model_original_price',
                'model_price',
                'item_price',
                'item_original_price',
                'original_price',
            ]);
            $itemTotal += $unitPrice * $qty;
        }

        // total_amount dari order_detail kadang beragam (gross / net) dan pada beberapa akun/region
        // field ini bisa muncul sebagai 0 walau item_list ada. Jadi kalau nilainya <= 0, kita fallback ke itemTotal.
        $gross = Arr::get($o, 'total_amount');
        if ($gross === null || $gross === '' || (float) $gross <= 0) {
            $gross = Arr::get($o, 'total_pay_amount');
        }
        $gross = (float) ($gross ?? 0);
        $totalAmountFallback = $gross > 0 ? $gross : $itemTotal;

        $recipient = Arr::get($o, 'recipient_address', []);
        [$customerPhone, $customerAddress] = $this->extractRecipientInfo($recipient);

        $created = false;
        $updated = false;
        $orderId = null;

        DB::transaction(function () use ($token, $orderSn, $mappedStatus, $orderDate, $customerName, $customerPhone, $customerAddress, $shippingCarrier, $note, $itemTotal, $totalAmountFallback, $items, &$created, &$updated, &$orderId) {
            $order = Order::where('order_number', $orderSn)->first();

            if (!$order) {
                $order = new Order();
                $order->order_number = $orderSn;
                $created = true;
            } else {
                $updated = true;
            }

            // Di aplikasi ini, completion_date adalah target internal.
            // Untuk order Shopee, set default +1 hari biar UI timeline tetap masuk akal.
            $completionDate = $orderDate->copy()->addDay();

            $order->customer_name = $customerName;
            $order->customer_phone = $customerPhone;
            $order->customer_address = $customerAddress;
            $order->order_date = $orderDate;
            $order->completion_date = $completionDate;
            $order->status = $mappedStatus;
            $order->notes = $note !== '' ? $note : 'Synced from Shopee';
            $order->jenis_pengiriman = $shippingCarrier !== '' ? $shippingCarrier : '-';
            $order->jenis_transaksi = 'Shopee';

            // Simpan fallback; nanti akan ditimpa oleh seller_income dari escrow jika ada.
            $order->price = $itemTotal;
            $order->total_amount = $totalAmountFallback;

            $order->save();
            $orderId = $order->id;

            // Upsert order_items
            $seen = [];
            foreach ($items as $item) {
                $itemId = Arr::get($item, 'item_id');
                $modelId = Arr::get($item, 'model_id');
                $sku = Arr::get($item, 'item_sku') ?? Arr::get($item, 'model_sku') ?? Arr::get($item, 'model_sku') ?? null;

                $qty = (int) (Arr::get($item, 'model_quantity_purchased') ?? Arr::get($item, 'quantity') ?? 1);
                $qty = max(1, $qty);

                $unitPrice = Arr::get($item, 'model_discounted_price');
                if ($unitPrice === null) {
                    $unitPrice = Arr::get($item, 'model_original_price');
                }
                if ($unitPrice === null) {
                    $unitPrice = Arr::get($item, 'original_price');
                }
                $unitPrice = (float) ($unitPrice ?? 0);

                $name = (string) (Arr::get($item, 'item_name') ?? 'Item Shopee');
                $variation = (string) (Arr::get($item, 'model_name') ?? Arr::get($item, 'variation_name') ?? '');
                if ($variation !== '') {
                    $name .= ' - ' . $variation;
                }

                $key = implode(':', ['shopee', (string) $itemId, (string) $modelId, $name]);
                $seen[] = $key;

                $linkedProductId = null;
                try {
                    $linkedProductId = \App\Models\Product::where('external_platform', 'shopee')
                        ->where('external_shop_id', (int) $token->shop_id)
                        ->where('external_item_id', (int) $itemId)
                        ->value('id');
                } catch (\Throwable $e) {
                    // ignore
                }

                OrderItem::updateOrCreate([
                    'order_id' => $order->id,
                    'external_platform' => 'shopee',
                    'external_item_id' => $itemId,
                    'external_model_id' => $modelId,
                ], [
                    'product_id' => $linkedProductId,
                    'external_sku' => $sku,
                    'product_name' => $name,
                    'quantity' => $qty,
                    'price' => $unitPrice,
                    'notes' => $variation !== '' ? $variation : null,
                ]);
            }

            // Hapus item shopee yang sudah tidak ada di order_detail (jarang terjadi, tapi menjaga data rapi)
            if (!empty($items)) {
                $keepModelIds = array_values(array_filter(array_map(fn($it) => Arr::get($it, 'model_id'), $items)));
                if (!empty($keepModelIds)) {
                    OrderItem::where('order_id', $order->id)
                        ->where('external_platform', 'shopee')
                        ->whereNotIn('external_model_id', $keepModelIds)
                        ->delete();
                }
            }
        });

        // Sync potongan/fee (escrow) - dipisah dari transaction supaya tidak nge-lock DB lama.
        if ($orderId) {
            try {
                $order = Order::find($orderId);
                if ($order) {
                    $this->syncFinancial($token, $order, $orderSn);
                }
            } catch (\Throwable $e) {
                Log::warning('Shopee escrow sync failed', [
                    'order_sn' => $orderSn,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [$created, $updated];
    }

    private function syncFinancial(ShopeeToken $token, Order $order, string $orderSn): void
    {
        // Endpoint ini kadang butuh order sudah paid/processed. Jadi kalau gagal, kita skip saja.
        $resp = $this->client->getEscrowDetail($token, $orderSn);

        $detail = Arr::get($resp, 'escrow_detail');
        $detail = is_array($detail) ? $detail : $resp;

        $currency = (string) (Arr::get($detail, 'currency') ?? Arr::get($detail, 'currency_code') ?? '');

        // Coba baca beberapa kemungkinan nama field (beda region/versi)
        $buyerTotal = $this->firstNumber($detail, [
            'buyer_total_amount',
            'order_total_amount',
            'total_amount',
            'escrow_totalamount',
        ]);

        $shippingFeeBuyer = $this->firstNumber($detail, [
            'shipping_fee_buyer',
            'buyer_shipping_fee',
            'actual_shipping_fee',
            'estimated_shipping_fee',
        ]);

        $itemTotal = $this->firstNumber($detail, [
            'item_total_amount',
            'order_income',
        ]);

        $coinUsed = $this->firstNumber($detail, ['coin_used', 'escrow_coin', 'coin']);
        $voucherSeller = $this->firstNumber($detail, ['voucher_from_seller', 'seller_voucher', 'escrow_voucher']);
        $voucherShopee = $this->firstNumber($detail, ['voucher_from_shopee', 'shopee_voucher']);
        $promotion = $this->firstNumber($detail, ['promotion', 'escrow_promotion', 'discount']);

        $commissionFee = $this->firstNumber($detail, ['commission_fee', 'escrow_shopeecommission']);
        $serviceFee = $this->firstNumber($detail, ['service_fee']);
        $transactionFee = $this->firstNumber($detail, ['transaction_fee', 'credit_card_transaction_fee']);

        $sellerIncome = $this->firstNumber($detail, [
            'seller_income',
            'seller_total_income',
            'escrow_amount',
        ]);

        // Upsert ke tabel shopee_order_financials
        \App\Models\ShopeeOrderFinancial::updateOrCreate([
            'order_id' => $order->id,
        ], [
            'order_sn' => $orderSn,
            'shop_id' => (int) ($token->shop_id ?? 0) ?: null,
            'currency' => $currency !== '' ? $currency : null,
            'buyer_total_amount' => $buyerTotal,
            'shipping_fee_buyer' => $shippingFeeBuyer,
            'item_total_amount' => $itemTotal,
            'coin_used' => $coinUsed,
            'voucher_from_seller' => $voucherSeller,
            'voucher_from_shopee' => $voucherShopee,
            'promotion' => $promotion,
            'commission_fee' => $commissionFee,
            'service_fee' => $serviceFee,
            'transaction_fee' => $transactionFee,
            'seller_income' => $sellerIncome,
            'raw' => $detail,
        ]);

        // Update order.total_amount supaya laporan revenue pakai net (seller_income) kalau tersedia
        if (!is_null($sellerIncome) && (float) $sellerIncome > 0) {
            $order->total_amount = (float) $sellerIncome;
            $order->save();
        }
    }

    private function firstNumber(array $data, array $keys): ?float
    {
        foreach ($keys as $k) {
            if (Arr::has($data, $k)) {
                $v = Arr::get($data, $k);
                if ($v === null || $v === '') {
                    continue;
                }
                return (float) $v;
            }
        }
        return null;
    }

    private function extractRecipientInfo($recipient): array
    {
        if (!is_array($recipient)) {
            return [null, null];
        }

        $phone = Arr::get($recipient, 'phone') ?? Arr::get($recipient, 'phone_number');

        // Shopee sering punya full_address, atau komponen (city/state/district/town)
        $full = Arr::get($recipient, 'full_address');
        if (is_string($full) && trim($full) !== '') {
            return [$phone ? (string) $phone : null, trim($full)];
        }

        $parts = [];
        foreach (['address', 'district', 'city', 'state', 'region', 'zipcode', 'post_code'] as $k) {
            $p = Arr::get($recipient, $k);
            if (is_string($p) && trim($p) !== '') {
                $parts[] = trim($p);
            }
        }

        $addr = !empty($parts) ? implode(', ', $parts) : null;
        return [$phone ? (string) $phone : null, $addr];
    }

    private function mapStatus(string $shopeeStatus): string
    {
        $s = strtoupper($shopeeStatus);

        return match ($s) {
            'CANCELLED', 'IN_CANCEL', 'TO_RETURN' => 'cancelled',
            'COMPLETED' => 'completed',
            'SHIPPED', 'TO_CONFIRM_RECEIVE', 'RETRY_SHIP', 'PROCESSED' => 'in_progress',
            'UNPAID', 'READY_TO_SHIP' => 'pending',
            default => 'pending',
        };
    }
}
