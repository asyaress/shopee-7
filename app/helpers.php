<?php

if (!function_exists('hub_rp')) {
    function hub_rp($amount, bool $signed = false): string
    {
        $n = (float) $amount;
        $prefix = $signed && $n < 0 ? '-' : '';
        return $prefix . 'Rp ' . number_format(abs($n), 0, ',', '.');
    }
}

if (!function_exists('hub_pct')) {
    function hub_pct(?float $ratio, int $decimals = 1): string
    {
        if ($ratio === null) {
            return '—';
        }
        return number_format($ratio * 100, $decimals, ',', '.') . '%';
    }
}

if (!function_exists('hub_num')) {
    function hub_num($value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }
}
