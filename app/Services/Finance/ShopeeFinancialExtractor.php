<?php

namespace App\Services\Finance;

use App\Models\ShopeeOrderFinancial;

/**
 * Extract Shopee financial numbers in a consistent way.
 */
class ShopeeFinancialExtractor
{
    public static function extract(?ShopeeOrderFinancial $fin): array
    {
        if (!$fin) {
            return self::empty();
        }

        $raw = $fin->raw;
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }
        if (!is_array($raw)) {
            $raw = [];
        }

        if (isset($raw['escrow_detail']) && is_array($raw['escrow_detail'])) {
            $raw = $raw['escrow_detail'];
        }

        $pay = data_get($raw, 'buyer_payment_info', []);
        $income = data_get($raw, 'order_income', []);

        $grossProduct = (float) data_get(
            $income,
            'order_selling_price',
            data_get($pay, 'merchant_subtotal', $fin->order_selling_price ?? 0)
        );

        $grossBuyer = (float) data_get(
            $income,
            'buyer_total_amount',
            data_get($pay, 'buyer_total_amount', $fin->buyer_total_amount ?? 0)
        );

        $feeAdmin = abs((float) data_get($income, 'commission_fee', $fin->commission_fee ?? 0));
        $feeProgramHemat = abs((float) data_get($income, 'shipping_seller_protection_fee_amount', 0));
        $feeService = abs((float) data_get($income, 'service_fee', $fin->service_fee ?? 0));

        $feeProcess = abs((float) data_get($income, 'seller_order_processing_fee', 0));
        if ($feeProcess <= 0 && !empty($fin->transaction_fee)) {
            $feeProcess = abs((float) $fin->transaction_fee);
        }

        $feeAms = abs((float) data_get($income, 'order_ams_commission_fee', 0));
        $feeCampaign = abs((float) data_get($income, 'campaign_fee', 0));
        $feePremi = abs((float) data_get($income, 'delivery_seller_protection_fee_premium_amount', 0));
        $feeBuyerTransaction = abs((float) data_get($income, 'buyer_transaction_fee', data_get($income, 'credit_card_transaction_fee', 0)));
        $feeSellerTransaction = abs((float) data_get($income, 'seller_transaction_fee', 0));

        $sellerDiscount = abs((float) data_get($income, 'seller_discount', data_get($income, 'order_seller_discount', 0)));
        $voucherSeller = abs((float) data_get($income, 'voucher_from_seller', data_get($pay, 'seller_voucher', $fin->voucher_from_seller ?? 0)));
        $refund = abs((float) data_get($income, 'seller_return_refund', data_get($income, 'drc_adjustable_refund', 0)));

        $feeBuyerPayment = abs((float) data_get($income, 'buyer_transaction_fee', data_get($income, 'credit_card_transaction_fee', 0)));

        $feeTotal = $feeAdmin + $feeProgramHemat + $feeService + $feeProcess
            + $feeAms + $feeCampaign + $feePremi + $feeSellerTransaction;

        $net = (float) data_get(
            $income,
            'escrow_amount_after_adjustment',
            data_get($income, 'escrow_amount', $fin->seller_income ?? 0)
        );

        return [
            'gross_product' => $grossProduct,
            'gross_buyer' => $grossBuyer,
            'fee_admin' => $feeAdmin,
            'fee_program_hemat' => $feeProgramHemat,
            'fee_service' => $feeService,
            'fee_process' => $feeProcess,
            'fee_ams' => $feeAms,
            'fee_campaign' => $feeCampaign,
            'fee_premi' => $feePremi,
            'fee_buyer_transaction' => $feeBuyerTransaction,
            'fee_seller_transaction' => $feeSellerTransaction,
            'seller_discount' => $sellerDiscount,
            'voucher_seller' => $voucherSeller,
            'refund' => $refund,
            'fee_buyer_payment' => $feeBuyerPayment,
            'fee_total' => $feeTotal,
            'net' => $net,
            'raw_ok' => !empty($income) || !empty($pay),
        ];
    }

    public static function empty(): array
    {
        return [
            'gross_product' => 0.0,
            'gross_buyer' => 0.0,
            'fee_admin' => 0.0,
            'fee_program_hemat' => 0.0,
            'fee_service' => 0.0,
            'fee_process' => 0.0,
            'fee_ams' => 0.0,
            'fee_campaign' => 0.0,
            'fee_premi' => 0.0,
            'fee_buyer_transaction' => 0.0,
            'fee_seller_transaction' => 0.0,
            'seller_discount' => 0.0,
            'voucher_seller' => 0.0,
            'refund' => 0.0,
            'fee_buyer_payment' => 0.0,
            'fee_total' => 0.0,
            'net' => 0.0,
            'raw_ok' => false,
        ];
    }

    /** @return array<string, string> key => label */
    public static function feeLabels(): array
    {
        return [
            'admin' => 'Biaya administrasi',
            'layanan' => 'Biaya layanan',
            'proses' => 'Biaya proses pesanan',
            'program_hemat' => 'Program hemat ongkir',
            'ams' => 'Biaya komisi AMS',
            'campaign' => 'Biaya kampanye',
            'premi' => 'Premi / proteksi pengiriman',
            'seller_transaction' => 'Biaya transaksi penjual',
            'buyer_transaction' => 'Biaya transaksi pembeli (info)',
            'seller_discount' => 'Diskon penjual',
            'voucher_seller' => 'Voucher penjual',
            'refund' => 'Pengembalian dana',
        ];
    }

    /** @return array<string, string> key => penjelasan singkat */
    public static function feeDescriptions(): array
    {
        return [
            'admin' => 'Komisi penjual Shopee (commission fee) dari escrow order.',
            'layanan' => 'Biaya layanan platform Shopee (service fee).',
            'proses' => 'Biaya pemrosesan transaksi per pesanan (seller order processing fee).',
            'program_hemat' => 'Kontribusi seller untuk program hemat ongkir / proteksi pengiriman.',
            'ams' => 'Komisi iklan Shopee AMS yang dibebankan ke order.',
            'campaign' => 'Biaya partisipasi kampanye/promo Shopee.',
            'premi' => 'Premi proteksi pengiriman seller (delivery protection).',
            'seller_transaction' => 'Biaya transaksi yang dibebankan ke penjual.',
            'buyer_transaction' => 'Biaya transaksi pembeli (informatif, dari escrow).',
            'seller_discount' => 'Diskon harga yang ditanggung penjual.',
            'voucher_seller' => 'Voucher tok o yang ditanggung penjual.',
            'refund' => 'Pengembalian dana / adjustment refund ke pembeli.',
        ];
    }

    /** @return array<string, float> */
    public static function mapFinToBreakdown(array $fin): array
    {
        return [
            'admin' => (float) ($fin['fee_admin'] ?? 0),
            'program_hemat' => (float) ($fin['fee_program_hemat'] ?? 0),
            'layanan' => (float) ($fin['fee_service'] ?? 0),
            'proses' => (float) ($fin['fee_process'] ?? 0),
            'ams' => (float) ($fin['fee_ams'] ?? 0),
            'campaign' => (float) ($fin['fee_campaign'] ?? 0),
            'premi' => (float) ($fin['fee_premi'] ?? 0),
            'seller_transaction' => (float) ($fin['fee_seller_transaction'] ?? 0),
            'buyer_transaction' => (float) ($fin['fee_buyer_transaction'] ?? 0),
            'seller_discount' => (float) ($fin['seller_discount'] ?? 0),
            'voucher_seller' => (float) ($fin['voucher_seller'] ?? 0),
            'refund' => (float) ($fin['refund'] ?? 0),
        ];
    }

    /** @return array<string, float> */
    public static function emptyBreakdown(): array
    {
        return array_fill_keys(array_keys(self::feeLabels()), 0.0);
    }
}
