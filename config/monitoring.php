<?php

return [
  /*
  |--------------------------------------------------------------------------
  | Nama tampilan per shop_id (opsional)
  |--------------------------------------------------------------------------
  */
    'shop_names' => [
        // (int) env('SHOPEE_SHOP_ID_A') => 'Toko Utama',
    ],

    'sku_classifier' => [
        'min_qty_for_star' => 3,
        'star_margin' => 0.15,
        'star_roas' => 2.0,
        'bleeder_margin' => 0.0,
        'fix_margin' => 0.08,
        'high_volume_qty' => 20,
    ],

    'actions' => [
        'roas_scale_up' => 2.5,
        'roas_cut' => 1.5,
        'roas_kill' => 1.0,
        'margin_healthy' => 0.15,
        'margin_low' => 0.05,
        'min_ads_spend_significant' => 50000,
        'price_increase_suggest_pct' => 12,
        'ads_cut_suggest_pct' => 50,
    ],

    'hpp_gate' => [
        'min_complete_pct' => 0.85,
        'block_recommendations_below_pct' => 0.70,
    ],

    'ad_budget_monthly' => [
        // shop_id => rupiah, contoh: 123456 => 5000000
    ],

    'cash_guard' => [
        'ads_spend_weeks_lookback' => 4,
        'safe_ads_multiplier' => 0.25,
    ],

    'roas_advisor' => [
        'safety_multiplier' => 1.25,
    ],

    'pricing' => [
        'margin_buffer_pct' => 10,
        'min_net_to_gross_ratio' => 0.55,
    ],

    'alerts' => [
        'email' => env('CEO_ALERT_EMAIL'),
    ],

    /** BCG funnel — trafik & konversi (setara template Excel BCG ROAS) */
    'bcg_funnel' => [
        'conversion_threshold' => (float) env('BCG_CONVERSION_THRESHOLD', 0.02),
        'traffic_mode' => env('BCG_TRAFFIC_MODE', 'median'), // median | fixed
        'traffic_fixed' => (int) env('BCG_TRAFFIC_FIXED', 100),
        /** Window sync otomatis (get_item_extra_info = 30 hari views) */
        'sync_days' => (int) env('BCG_SYNC_DAYS', 30),
    ],

    /** Estimasi dana dilepaskan jika belum di-import dari Data Income */
    'settlement' => [
        'hold_days_after_complete' => (int) env('SHOPEE_SETTLEMENT_HOLD_DAYS', 3),
    ],

    /** Rekap grid — jumlah bulan tampil */
    'rekap_months' => (int) env('MONITORING_REKAP_MONTHS', 12),

    'price_calculator' => [
        'default_admin_pct' => (float) env('PRICE_CALC_ADMIN_PCT', 0.18),
        'default_operational_pct' => (float) env('PRICE_CALC_OPS_PCT', 0.08),
        'default_target_net_pct' => (float) env('PRICE_CALC_TARGET_NET_PCT', 15),
    ],
];
